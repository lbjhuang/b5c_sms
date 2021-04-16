<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/15
 * Time: 16:19
 */

class ConversionLogic extends BaseLogic
{
    public function conversionList($params) {
        import('ORG.Util.Page');
        $params['p'] ? $_GET['p'] = $params['p']:'';
        $where  = $this->conversionListWhere($params);
        $count  = count(D('Conversion')
            ->alias('t')
            ->join('tb_scm_conversion_details scd on scd.conversion_id = t.id')
            ->join(PMS_DATABASE .'.product_sku ps on ps.sku_id = scd.sku_id')
            ->where($where)
            ->group('t.id')
            ->select());
        $page   = new Page($count,$params['rows'] ? $params['rows'] : 20);
        $list   = D('Conversion')
            ->alias('t')
            ->field('t.id,t.conversion_no,t.affect_supplier_settlement,t.need_reviewer,t.created_by,t.created_at,a.CD_VAL type,b.CD_VAL status,c.CD_VAL sales_team,d.CD_VAL warehouse')
            ->join('tb_ms_cmn_cd a on a.CD=t.type_cd')
            ->join('tb_ms_cmn_cd b on b.CD=t.status_cd')
            ->join('tb_ms_cmn_cd c on c.CD=t.sales_team_cd')
            ->join('tb_ms_cmn_cd d on d.CD=t.warehouse_cd')
            ->join('tb_scm_conversion_details scd on scd.conversion_id = t.id')
            ->join(PMS_DATABASE .'.product_sku ps on ps.sku_id = scd.sku_id')
            ->where($where)
            ->group('t.id')
            ->limit($page->firstRow.','.$page->listRows)
            ->order('t.id desc')
            ->select();
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function conversionListWhere($params) {
        $where = [];
        if(!empty($params['status_cd'])) $where['status_cd'] = $params['status_cd'];
        if(!empty($params['conversion_no'])) $where['conversion_no'] = ['like',"%{$params['conversion_no']}%"];
        if(!empty($params['type_cd'])) $where['type_cd'] = $params['type_cd'];
        if($params['affect_supplier_settlement'] !== '') $where['affect_supplier_settlement'] = $params['affect_supplier_settlement'];
        if(!empty($params['sales_team_cd'])) $where['sales_team_cd'] = $params['sales_team_cd'];
        if(!empty($params['created_by'])) $where['t.created_by'] = $params['created_by'];
        if($params['created_at_start'] && $params['created_at_end']) {
            $where['t.created_at'] = ['between',[$params['created_at_start'].' 00:00:00',$params['created_at_end'].' 23:59:59']];
        }elseif($params['created_at_start']) {
            $where['t.created_at'] = ['egt',$params['created_at_start'].' 00:00:00'];
        }elseif($params['created_at_end']) {
            $where['t.created_at'] = ['elt',$params['created_at_end'].' 23:59:59'];
        }
        if(!empty($params['need_reviewer'])) $where['need_reviewer'] = $params['need_reviewer'];
        // $params['sku_or_barcode'] = '8000516302';
        if($params['sku_or_barcode']) { // sku或条形码精确搜索
            $complex['ps.sku_id']    = $params['sku_or_barcode'];
            $complex['ps.upc_id']    = $params['sku_or_barcode'];
            $complex['_string'] = "FIND_IN_SET('{$params['sku_or_barcode']}',ps.upc_more)";
            $complex['_logic']      = 'or';
            $where['_complex']      = $complex;
        }
        return $where;
    }

    public function conversionDetail($id) {
        $conversion_m               = new ConversionModel();
        $conversion_detail_m        = new ConversionDetailModel();
        $conversion                 = $conversion_m->where(['id'=>$id])->find();
        $detail                     = $conversion_detail_m
            ->alias('t') 
            ->field("t.sku_id,c.spu_id,IF(c.upc_more, REPLACE(CONCAT_WS(',',c.upc_id,c.upc_more),',',',\\r\\n'), c.upc_id) as upc_id,
                c.upc_more,number,b.CD_VAL purchase_team,a.purchase_order_no,a.batch_code,deadline_date_for_use")
            ->join('tb_wms_batch a on a.id=t.batch_id')
            ->join('tb_ms_cmn_cd b on b.CD=a.purchase_team_code')
            ->join(PMS_DATABASE . '.product_sku c on c.sku_id=t.sku_id')
            ->where(['conversion_id' => $conversion['id']])
            ->select();
        $conversion['sales_team']   = cdVal($conversion['sales_team_cd']);
        $conversion['warehouse']    = cdVal($conversion['warehouse_cd']);
        $conversion['status']       = cdVal($conversion['status_cd']);
        $conversion['type']         = cdVal($conversion['type_cd']);
        $conversion['detail']       = SkuModel::getInfo($detail,'sku_id',['spu_name','attributes']);
        $conversion['can_approve']  = strtoupper($_SESSION['m_loginname']) == strtoupper($conversion['need_reviewer']);
        return $conversion;
    }

    public function createConversion($data)
    {
        M()->startTrans();
        $conversion_m           = new ConversionModel();
        $conversion_detail_m    = new ConversionDetailModel();
        $data['conversion_no']  = $this->createConversionNo();
        $data['need_reviewer']  = $this->getApproveUser($data['sales_team_cd']);
        if(!($conversion_m->create($data) && $conversion_id = $conversion_m->add())) {
            $this->error = $conversion_m->getError() ? : '转换单保存失败';
            M()->rollback();
            return false;
        }
        try {
            ProductTransfer::approveMsg($conversion_m->find($conversion_id),$data['detail']);
        }catch (Exception $exception) {
            $this->error = '发送审批消息失败：'.$exception->getMessage();
            return false;
        }
        if(!empty($data['detail'])) {
            $batch_lock = [];
            foreach ($data['detail'] as $v) {
                $v['conversion_id'] = $conversion_id;
                if(!($conversion_detail_m->create($v) && $conversion_detail_m->add())) {
                    $this->error = $conversion_detail_m->getError() ? : '转换单商品保存失败';
                    M()->rollback();
                    return false;
                }
                $batch_lock[] = [
                    'operatorId' => $_SESSION['user_id'],
                    'orderId' => $data['conversion_no'],
                    'skuId' => $v['sku_id'],
                    'batchCode' => $v['batch_code'],
                    'num' => $v['number'],
                ];
            }
            $res_batch = ApiModel::batchLock($batch_lock);
            if($res_batch['code'] != 2000) {
                $this->error = '批次锁定失败：'.$res_batch['msg'];
                M()->rollback();
                return false;
            }
        } else {
            $this->error = '转换商品不能为空';
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    public function getApproveUser($sale_team) {
        $approve_email = D('TbMsCmnCd')->where(['CD'=>$sale_team])->getField('ETC');
        return explode('@',$approve_email)[0];
    }

    public function createConversionNo() {
        $latest_no = (new ConversionModel())->lock(true)->order('id desc')->getField(['conversion_no']);
        if($latest_no && substr($latest_no,3,8) == date('Ymd')) {
            $no = 'ZHD'.(substr($latest_no,3)+1);
        }else {
            $no = 'ZHD'.date('Ymd').'0001';
        }
        return $no;
    }

    public function conversionGoodsSearch($params) {
        $where = $this->conversionGoodsSearchWhere($params);
        if(!$where) return false;
        $goods = M('batch','tb_wms_')
            ->alias('t')
            ->field("t.sku_id,b.spu_id,IF(b.upc_more, REPLACE(CONCAT_WS(',',b.upc_id,b.upc_more),',',',\\r\\n'), b.upc_id) as upc_id, stream_id,t.id bath_id,purchase_team_code,c.CD_VAL purchase_team,purchase_order_no,batch_code,deadline_date_for_use,available_for_sale_num")
            ->join('inner join tb_wms_bill a on a.id=t.bill_id')
            ->join(PMS_DATABASE . '.product_sku b on b.sku_id=t.sku_id')
            ->join('tb_ms_cmn_cd c on c.CD=t.purchase_team_code')
            ->where($where)
            ->select();
        //过滤组合商品
        $goods = array_filter($goods, function($item) {
            if (ProductModel::isGroupSku($item['sku_id'])) {
                return false;
            }
            return true;
        });
        $goods = array_values($goods);
        $goods = SkuModel::getInfo($goods,'sku_id',['spu_name','attributes']);
        return $goods;
    }

    public function conversionGoodsSearchWhere($params) {
        if(!$params['type_cd']) {
            $this->error = '转换类型必须';
            return false;
        }
        if(!$params['sales_team_cd']) {
            $this->error = '货物归属销售团队必须';
            return false;
        }
        if(!$params['warehouse_cd']) {
            $this->error = '货物归属仓库必须';
            return false;
        }

        switch ($params['type_cd']) {
            case ConversionModel::$type['quality_to_broken'] :
                $where['t.vir_type'] = 'N002440100';
                break;
            case ConversionModel::$type['broken_to_quality']:
                $where['t.vir_type'] = 'N002440400';
                break;
            default :
                $this->error = '转换类型异常';
                return false;
        }
        $where['sale_team_code'] = $params['sales_team_cd'];
        $where['warehouse_id'] = $params['warehouse_cd'];
        $where['available_for_sale_num'] = ['gt',0];
        if(!empty($params['purchase_no'])) $where['purchase_order_no'] = $params['purchase_no'];
        if(!empty($params['demand_no'])) $where['purchase_order_no'] = ['like','%'.$params['purchase_no'].'%'];
        ## sku 使用符合查询
        if(!empty($params['sku_id'])) {
            $complex['b.sku_id'] = $params['sku_id'];
            $complex['b.upc_id'] =  $params['sku_id'];
            $complex['_string'] = "FIND_IN_SET('{$params['sku_id']}',b.upc_more)";
            $complex['_logic'] = 'or';
            $where['_complex'] = $complex;
        }

        return $where;
    }

}