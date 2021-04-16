<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/26
 * Time: 13:49
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ReturnLogic extends BaseLogic
{

    public function returnList($param) {
        import('ORG.Util.Page');
        $count  = D('Return')->returnCount($param);
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = D('Return')->returnList($param,$page->firstRow.','.$page->listRows);
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }


    /**
     * @param $param
     * @return bool
     * 商品查询
     */
    public function searchGoods($param) {
        $goods_l        = D('Goods','Logic');
        $goods  = $goods_l->getGoodsBySkuOrUpc($param['goods']);
        if(!$goods) {
            $this->error = $goods_l->getError();
            return false;
        }
        $goods              = $this->goodsCanReturn($param, $goods);
        return $goods;
    }

    /**
     * @param $param
     * @param $goods
     * @return bool
     * 查找可退货商品
     */
    private function goodsCanReturn($param, $goods) {
        $skus       = array_column($goods, 'sku_id');
        $goods      = array_combine($skus,$goods);
        $batch_m    = D('TbWmsBatch');
        $bach_where = [
            't.SKU_ID'                  => ['in',$skus],
            't.purchase_team_code'              => $param['purchase_team_cd'],
            'a.CON_COMPANY_CD'          => $param['our_company_cd'],
            'a.warehouse_id'            => $param['warehouse_cd'],
            'd.id'                      => $param['supplier_id'],
            't.vir_type'                => ['in',['N002440100','N002440400']],
            't.available_for_sale_num'  => ['gt',0]
        ];
        $bach_info = $batch_m
            ->alias('t')
            ->field('t.SKU_ID sku_id,e.information_id,t.vir_type vir_type_cd,case t.vir_type when "N002440100" then "正品" when "N002440400" then "残次品" end vir_type_val,t.purchase_order_no,c.relevance_id,sum(available_for_sale_num) available_for_return_num')
            ->join('inner join tb_wms_bill a on a.id=t.bill_id')
            ->join('inner join tb_pur_order_detail b on b.procurement_number=t.purchase_order_no')
            ->join('inner join tb_pur_relevance_order c on c.order_id=b.order_id')
//            ->join('inner join tb_crm_sp_supplier d on d.SP_CHARTER_NO=b.sp_charter_no')
            ->join('inner join tb_crm_sp_supplier d on d.ID = b.supplier_new_id')//改成用供应商id进行关联（营业执照号可以修改）
            ->join('inner join tb_pur_goods_information e on e.relevance_id=c.relevance_id and e.sku_information=t.SKU_ID')
            ->where($bach_where)
            ->group('t.SKU_ID,t.vir_type,c.relevance_id')
            ->select();
        if(!$bach_info) {
            $this->error = '未找到可退货商品';
            return false;
        }
        foreach ($bach_info as &$v) {
            $v = array_merge($goods[$v['sku_id']],$v);
        }
        return $bach_info;
    }

    public function initiateReturn($param) {
        $return_m       = D('Return');
        $return_goods_m = D('ReturnGoods');
        $return_order_m = D('ReturnOrder');
        $return_m->startTrans();
        $res_validate   = $this->returnGoodsValidate($param); // 校验退货商品
        if(!$res_validate) return false;
        $res_return     = $return_m->addReturn($param); // 保存退货信息
        if($res_return === false) {
            $this->error = $return_m->getError();
            return false;
        }
        $orders = array_unique(array_column($param['goods'],'relevance_id')); // 获取退货商品关联的订单id
        foreach ($orders as $v) {
            $res_order = $return_order_m->addReturnOrder($res_return, ['relevance_id'=>$v]); // 保存退货订单信息
            if($res_order) {
                foreach ($param['goods'] as $val) {
                    if($val['relevance_id'] == $v) {
                        $res_goods = $return_goods_m->addReturnGoods($res_return, $res_order, $val); // 保存退货商品信息
                        if(!$res_goods) {
                            $return_m->rollback();
                            $this->error = $return_goods_m->getError();
                            return false;
                        }
                        $res_goods_information = D('TbPurGoodsInformation')
                            ->where(['information_id'=>$val['information_id']])
                            ->setInc('return_number',$val['return_number']); // 增加退货商品数量
                        if(!$res_goods_information) {
                            $return_m->rollback();
                            $this->error = '保存商品总退货数失败';
                            return false;
                        }
                    }
                }
            }else {
                $return_m->rollback();
                $this->error = $return_order_m->getError();
                return false;
            }
            $res_relevance = D('TbPurRelevanceOrder')->where(['relevance_id'=>$v])->save(['has_return_goods'=>1]);
            if($res_relevance === false) {
                $return_m->rollback();
                $this->error = '更新采购单退货情况失败';
                return false;
            }
            $res_relevance_ship = D('Purchase/Ship','Logic')->updateShipStatus($v);
            if($res_relevance_ship === false) {
                $this->error = '更新采购单退货状态失败';
                M()->rollback();
                return false;
            }
            $res_relevance_warehouse = D('Purchase/Warehouse','Logic')->updateRelevanceWarehouseStatus($v);
            if($res_relevance_warehouse === false) {
                $this->error = '更新采购单退货状态失败';
                M()->rollback();
                return false;
            }
            D('TbPurActionLog')->addLog($v);
        }
        $param['order_id'] = $return_m->where(['id'=>$res_return])->getField('return_no');
        $res_occupy = $this->returnGoodsOccupyBatch($param); // 占用批次处理
        if(!$res_occupy) {
            $return_m->rollback();
            return false;
        }
        $return_m->commit();
        return $res_return;
    }

    public function returnGoodsValidate($param) {
        $error = [];
        foreach ($param['goods'] as $v) {
            $vir_type = cdVal($v['vir_type_cd']);
            if($v['return_number'] == 0) {
                $error[] = [
                    'procurement_number'    => $v['purchase_order_no'],
                    'sku_id'                => $v['sku_id'],
                    'vir_type'              => $vir_type,
                    'info'                  => '退货数不能为0'
                ];
            }else {
                $warehouse_number   = D('Purchase/Ship','Logic')->getWarehousedNumber($v['information_id']);
                $return_number      = $this->getReturnNumber($v['information_id']);
                if($warehouse_number-$return_number < $v['return_number'])
                    $error[] = [
                        'procurement_number'    => $v['purchase_order_no'],
                        'sku_id'                => $v['sku_id'],
                        'vir_type'              => $vir_type,
                        'info'                  => '超出退货上限数量'
                    ];
            }
        }
        if(!empty($error)) {
            $this->error    = '退货商品校验失败';
            $this->data     = $error;
            return false;
        }
        return true;
    }

    public function getReturnNumber($information_id) {
        return D('ReturnGoods')
            ->where(['information_id'=>$information_id])
            ->getField('sum(return_number)');
    }

    public function returnGoodsOccupyBatch($param) {
        $request = [];
        foreach ($param['goods'] as $v) {
            $goods_info = D('TbPurRelevanceOrder')
                ->alias('t')
                ->field('')
                ->join('tb_pur_order_detail a on a.order_id=t.order_id')
                ->join('tb_pur_goods_information b on b.relevance_id=t.relevance_id')
                ->where(['b.information_Id'=>$v['information_id']])
                ->find();
            $request[] = [
                "virType"           => $v['vir_type_cd'],
                "orderId"           => $param['order_id'],
                "skuId"             => $goods_info['sku_information'],
                "purchaseOrderNo"   => $goods_info['procurement_number'],
                "purchaseTeamCode"  => $param['purchase_team_cd'],
                "num"               => $v['return_number'],
                "operatorId"        => session('user_id'),
                "deliveryWarehouse" => $param['warehouse_cd']
            ];
        }
        $res = ApiModel::returnGoodsOccupyBatch($request);
        if($res['code'] == 2000) return true;
        $this->error    = $res['msg'];
        $this->data     = $res['data'];
        return false;
    }

    public function returnDetail($id) {
        $return_m           = D('Return');
        $return_order_m     = D('ReturnOrder');
        $return             = $return_m->returnDetail($id);
        $return['orders']   = $return_order_m->getReturnOrder($id);
        $return['goods']    = $return_order_m->getReturnOrderGoods($id);
        return $return;
    }

    public function tally($param) {
        $return_m       = D('Return');
        $return_order_m = D('ReturnOrder');
        $return_goods_m = D('ReturnGoods');
        M()->startTrans();
        $return_status = $return_m->lock(true)->where(['id'=>$param['id']])->getField('status_cd');
        if($return_status != $return_m::$return_status['to_tally']) {
            M()->rollback();
            $this->error = '状态异常';
            return false;
        }
        $param['status_cd'] = $return_m::$return_status['done'];
        $param['tally_by']  = session('m_loginname');
        $param['tally_at']  = date('Y-m-d H:i:s');
        if(!$return_m->create($param) || $return_m->save() === false) {
            $this->error = $return_m->getError() ? : '退货单信息保存失败';
            M()->rollback();
            return false;
        }
        foreach ($param['compensation'] as $v) {
            if(!$return_order_m->create($v) || $return_order_m->save() === false) {
                $this->error = $return_m->getError() ? : '退货商品信息保存失败';
                M()->rollback();
                return false;
            }
            // 获取关联单据号 return_order id -> return_id -> return.return_no
            $return_id = $return_order_m->where(['id'=>$v['id']])->getField('return_id');
            $return_no = $return_m->where(['id'=>$return_id])->getField('return_no');
            $relevance_id = $return_order_m->where(['id'=>$v['id']])->getField('relevance_id');
            // 生成应付记录
            $addDataInfo = [];
            $operation_cd = 'N002870007';
            $addDataInfo['amount_payable'] = $v['compensation'];
            $addDataInfo['clause_type'] = '5'; // 通用
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            $oper_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', $operation_cd, $relevance_id, $return_no);
            if (!$oper_res) {
                $this->error = '生成应付记录失败';
                M()->rollback();
                return false;
            }



//            $relevance_id = $return_order_m->where(['id'=>$v['id']])->getField('relevance_id');
//            $res_relevance  = $relevance_m->where(['relevance_id'=>$relevance_id])->save(['has_return_goods'=>1]);
//            if(!$res_relevance === false) {
//                $this->error = '采购单退货状态保存失败';
//                M()->rollback();
//                return false;
//            }
        }
        foreach ($param['goods'] as $v) {
            if(!$return_goods_m->create($v) || $return_goods_m->save() === false) {
                $this->error = $return_m->getError() ? : '退货订单信息保存失败';
                M()->rollback();
                return false;
            }
        }
        $relevance_arr = $return_order_m->where(['return_id'=>$param['id']])->getField('relevance_id',true);
        foreach ($relevance_arr as $v) {
            D('TbPurActionLog')->addLog($v);
        }
        $return_m->commit();
        return true;
    }

    public function deleteReturn($id) {
        $return_m       = D('Return');
        $return_order_m = D('ReturnOrder');
        $return_goods_m = D('ReturnGoods');
        M()->startTrans();
        $return_info = $return_m->field('status_cd,return_no')->lock(true)->where(['id'=>$id])->find();
        if($return_info['status_cd'] != ReturnModel::$return_status['to_out_storage']) {
            M()->rollback();
            $this->error = '状态异常';
            return false;
        }
        $return_goods = (new Model())->table('tb_pur_return_goods gi')
            ->field('gi.*, ro.relevance_id')
            ->join('left join tb_pur_return_order ro on gi.return_order_id = ro.id')
            ->where(['gi.return_id' => $id])
            ->select();
        foreach ($return_goods as $v) {
            $res_goods = D('TbPurGoodsInformation')
                ->where(['relevance_id'=>$v['relevance_id'], 'information_id'=>$v['information_id']])
                ->setDec('return_number',$v['return_number']);
            if($res_goods === false) {
                $this->error = '更新采购商品退货数量失败';
                M()->rollback();
                return false;
            }
        }
        $relevance_arr = $return_order_m->where(['return_id'=>$id])->getField('relevance_id',true);
        foreach ($relevance_arr as $v) {
            $res_relevance_ship = D('Purchase/Ship','Logic')->updateShipStatus($v);
            if($res_relevance_ship === false) {
                $this->error = '更新采购单发货状态失败';
                M()->rollback();
                return false;
            }
            $res_relevance_warehouse = D('Purchase/Warehouse','Logic')->updateRelevanceWarehouseStatus($v);
            if($res_relevance_warehouse === false) {
                $this->error = '更新采购单入库状态失败';
                M()->rollback();
                return false;
            }
            D('TbPurActionLog')->addLog($v);
        }
        $res_return = $return_m->where(['id'=>$id])->delete();
        $res_order  = $return_order_m->where(['return_id'=>$id])->delete();
        $res_goods  = $return_goods_m->where(['return_id'=>$id])->delete();
        foreach ($relevance_arr as $v) {
            if($this->updateHasReturn($v) === false) {
                $this->error = '更新采购单退货情况失败';
                M()->rollback();
                return false;
            }
        }
        if($res_return === false || $res_order === false || $res_goods === false) {
            $this->error = '删除失败';
            M()->rollback();
            return false;
        }
        $res_batch = ApiModel::freeUpBatch([['orderId'=>$return_info['return_no']]]);
        if($res_batch['code'] !== 2000) {
            $this->error = '释放占用失败'.$res_batch['msg'];
            M()->rollback();
            return false;
        }
        $return_m->commit();
        return true;
    }

    public function updateHasReturn($relevance_id) {
        $has_return = D('ReturnOrder')->where(['relevance_id'=>$relevance_id])->getField('id') ? true : false;
        $save['has_return_goods'] = $has_return ? 1 : 0;
        return D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save($save);
    }

    public function getPurchaseGoodsReturnNum($information_id) {
        $return_goods_m = D('ReturnGoods');
        return $return_goods_m
            ->where(['information_id'=>$information_id])
            ->group('information_id')
            ->getField('sum(return_number)');
    }

    public function getPurchaseGoodsReturnOutStoreNum($information_id) {
        $return_goods_m = D('Purchase/ReturnGoods');
        return $return_goods_m
            ->alias('t')
            ->join('tb_pur_return a on a.id=t.return_id')
            ->where(['information_id'=>$information_id,'a.status_cd'=>['neq','N002640100']])
            ->group('information_id')
            ->getField('sum(return_number)');
    }

    public function getPurchaseReturn($relevance_id) {
        $return_m   = D('Purchase/Return');
        return $return_m
            ->alias('t')
            ->field('t.*,a.compensation_currency_cd,a.compensation,
                sum(case when b.vir_type_cd="N002440100" then return_number else 0 end) return_number,
                sum(case when b.vir_type_cd="N002440100" then tally_number else 0 end) tally_number,
                sum(case when b.vir_type_cd="N002440400" then return_number else 0 end) return_number_broken,
                sum(case when b.vir_type_cd="N002440400" then tally_number else 0 end) tally_number_broken
                ')
            ->join('tb_pur_return_order a on a.return_id=t.id')
            ->join('tb_pur_return_goods b on b.return_order_id=a.id')
            ->where(['a.relevance_id'=>$relevance_id])
            ->group('t.id')
            ->select();
    }

    public function relevanceHasReturn($relevance_id) {
        $return_order_id = D('ReturnOrder')->where(['relevance_id'=>$relevance_id])->getField('id');
        return $return_order_id ? true : false;
    }

}