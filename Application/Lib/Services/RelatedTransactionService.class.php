
<?php
class RelatedTransactionService extends Service
{

    public  $out_stock_cd;
    public  $in_stock_cd;
    public  $wms_operating_cd;
    public  $wms_added_service_cd;
    public  $wms_outbound_cost_cd;
    public  $wms_head_logistics_cd;
    public  $wms_insurance_cd;
    public  $wms_shelf_cost_cd;


    public function __construct()
    {
        $this->out_stock_cd = 'N002870019';
        $this->in_stock_cd = 'N002870020';
        $this->wms_operating_cd = 'N001950601';
        $this->wms_added_service_cd = 'N001950602';
        $this->wms_outbound_cost_cd = 'N001950605';
        $this->wms_head_logistics_cd = 'N001950606';
        $this->wms_insurance_cd = 'N001950603';
        $this->wms_shelf_cost_cd = 'N001950604';
    }

    /**
     * 创建单号
     * @return string
     */
    public static function createPaymentNO()
    {
        $rel_trans_no = M('rel_trans', 'tb_fin_')->lock(true)->where(['rel_trans_no' => ['like', 'GLJY' . date('Ymd') . '%']])->order('id desc')->getField('rel_trans_no');
        if ($rel_trans_no) {
            $num = substr($rel_trans_no, -6) + 1;
        } else {
            $num = 1;
        }
        $rel_trans_no = 'GLJY' . date('Ymd') . substr(1000000 + $num, 1);
        return $rel_trans_no;
    }

    /**
     *  调拨入库确认
     */
    public function allotInStock()
    {

    }

    /**
     *  B2C销售出库
     */
    public function b2cSellOutStock(){

    }


    /***
     * 列表
     * @param $where
     */
    public function getList($where,$pages)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $count = M('rel_trans', 'tb_fin_')
            ->where($where)
            ->count();
        $list = M('rel_trans', 'tb_fin_')
            ->field('rel_trans_no,trigger_type,sell_company_cd,pur_company_cd,sku_id
                ,upc_id,sku_quantity,rel_currency_cd,rel_price,rel_time,operation_user')
            ->where($where)
            ->order('tb_fin_rel_trans.id desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name','attributes'],['spu_name'=>'GUDS_NM','attributes'=>'GUDS_OPT_VAL_MPNG']);
        $list = CodeModel::autoCodeTwoVal($list,['trigger_type','sell_company_cd','pur_company_cd','rel_currency_cd']);
        foreach ($list as &$value){
            $value['rel_price'] = number_format($value['rel_price'], 2);
        }
        return [$list, $count];
    }

    /***
     *  详情
     * @param $where
     */
    public function getDetails()
    {

    }

    /**
     * 列表查询 where 组装
     * @param $params
     */
    public function mergeWhere($params)
    {
        $where['status'] = 0;
        //  关联交易订单号
        if (isset($params['rel_trans_no']) && !empty($params['rel_trans_no'])) {
            $where['rel_trans_no'] = $params['rel_trans_no'];
        }
        // 销售公司 多选
        if (isset($params['sell_company_cd']) && !empty($params['sell_company_cd'])) {
            $data = explode(',', $params['sell_company_cd']);
            $where['sell_company_cd'] = array('in', $data);
        }
        // 采购公司 多选
        if (isset($params['pur_company_cd']) && !empty($params['pur_company_cd'])) {
            $data = explode(',', $params['pur_company_cd']);
            $where['pur_company_cd'] = array('in', $data);
        }
        // SKU_UCP
        if (isset($params['sku_upc']) && !empty($params['sku_upc'])) {
            $orWhere = array(
                'sku_id' => array('like', $params['sku_upc']),
                'upc_id' => array('like', $params['sku_upc']),
                '_logic' => 'or'
            );
            $where['_complex'] = $orWhere;
        }
         // 交易时间段
        if (isset($params['rel_time_start']) && !empty($params['rel_time_start'])
            && isset($params['rel_time_end']) && !empty($params['rel_time_end'])) {
            $rel_time_end = date("Y-m-d H:i:s",strtotime($params['rel_time_end']."+1 day") - 1);
            $where['rel_time'] = array('between',array($params['rel_time_start'],$rel_time_end));
        }
        // 操作人
        if (isset($params['operation_user']) && !empty($params['operation_user'])) {
            $data = explode(',', $params['operation_user']);
            $where['operation_user'] = array('in', $data);
        }
        // 商品名称
        if (isset($params['GUDS_NM']) && !empty($params['GUDS_NM'])) {
            $sku_ids = SkuModel::titleToSku($params['GUDS_NM']);
            if (!isset($where['sku_id']) || empty($where['sku_id'])){
                $where['sku_id'] = array('in',$sku_ids);
            }
        }

        return $where;
    }

    /**
     * 增加操作日志
     */
    public function addLog(){

    }

    /**
     *  获取日志
     */
    public function getLog()
    {

    }

    /**
     * 批量删除关联交易记录
     * @param $rel_trans_ids
     * @return mixed
     */
    public static function batchDelete($rel_trans_on,$status){
        $save_data = array(
            'status' => $status,
            'delete_by' => DataModel::userNamePinyin(),
            'delete_at' => date('Y-m-d H:i:s'),
        );
        $ret = M('rel_trans', 'tb_fin_')->where(['rel_trans_no'=>['in',$rel_trans_on]])->save($save_data);
        return $ret;
    }


    /***
     *  导出数据
     * @param $where
     */
    public function getExportList($where)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $list = M('rel_trans', 'tb_fin_')
            ->field('rel_trans_no,trigger_type,sell_company_cd,pur_company_cd,sku_id
                ,upc_id,sku_quantity,rel_currency_cd,rel_price,rel_time,operation_user')
            ->where($where)
            ->select();
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name','attributes'],['spu_name'=>'GUDS_NM','attributes'=>'GUDS_OPT_VAL_MPNG']);
        $list = CodeModel::autoCodeTwoVal($list,['trigger_type','sell_company_cd','pur_company_cd','rel_currency_cd']);
        foreach ($list as &$value){
            $value['rel_price'] = number_format($value['rel_price'], 2);
        }
        return $list;
    }

    /**
     * 根据调入仓库获取采购
     */
    public function getPurCompanyCd($warehouse_code){
        $pur_company_cd = "";
        if (!$warehouse_code){
            return $pur_company_cd;
        }
        $warehouse  = M('warehouse','tb_wms_')->field('is_bonded,city')->where(['CD'=>$warehouse_code])->find();
        if (!$warehouse){
            return $pur_company_cd;
        }
        $city =  explode(',',$warehouse['city']);
        $where['tb_crm_site.id'] =  array('in',$city);
        $city_data = M('site','tb_crm_')
            ->field('NAME')
            ->where($where)
            ->select();
        if (!$city_data){
            return $pur_company_cd;
        }
        $name = array_column($city_data,'NAME');;
        //中国、日本、韩国、德国、澳大利亚、香港
        if (in_array('中国',$name)){
            // 是否为香港
            if (in_array('香港',$name)){
                $pur_company_cd = 'N001240100';
            }else{
                // 是否为保税仓
                if ($warehouse['is_bonded']){
                    $pur_company_cd = 'N001240100';
                }else{
                    $pur_company_cd = 'N001240500';
                }
            }
        }else if(in_array('日本',$name)){
            $pur_company_cd = 'N001241000';
        }else if(in_array('韩国',$name)){
            $pur_company_cd = 'N001242300';
        }else if(in_array('德国',$name)){
            $pur_company_cd = 'N001243100';
        }else if(in_array('澳大利亚',$name)){
            $pur_company_cd = 'N001244713';
        }else{
            $pur_company_cd = "N001240100";
        }
        return $pur_company_cd;

    }

    /**
     * 根据批次号获取销售公司
     */
    public function getSellCompanyCd($batch_ids){

        $where['tb_wms_batch.id'] =  array('in',$batch_ids);
        $sell_company_cd = M('batch','tb_wms_')
            ->field('tb_wms_batch.id as batch_id,
                CON_COMPANY_CD AS our_company,
                tb_wms_batch.SKU_ID AS skuId,
                tb_wms_stream.send_num as occupy_num,
                tb_wms_stream.unit_price_origin,
                tb_wms_stream.unit_price,
                tb_wms_stream.currency_id')
            ->join('LEFT JOIN tb_wms_bill ON tb_wms_batch.bill_id = tb_wms_bill.id')
            ->join('LEFT JOIN tb_wms_stream ON tb_wms_stream.bill_id = tb_wms_bill.id')
            ->where($where)
            ->select();
        return $sell_company_cd;
    }

    /**
     * 验证  根据占用批次归属公司和需求调入仓库对应的卖给的【我方公司】
    *  比较，不一样的，则针对批次粒度自动发起关联交易
     * @param $verifyData
     */
    public function verifyCompany($batch_ids,$allo_id,$data)
    {
        $result = array(
            'code' => 200,
            'msg' => "",
            'data' => [],
        );
        $allo = M('allo', 'tb_wms_')->where(['id' => $allo_id])->find();
        $sell_company_cd = $this->getSellCompanyCd($batch_ids); // 数组
        $pur_company_cd = $this->getPurCompanyCd($allo['allo_in_warehouse']);  // 单个
        if (!empty($sell_company_cd) && !empty($pur_company_cd)) {
            $trigger_type = 'N003220002';
            $tmpe = array();
            $sku_upc = array();
            foreach ($data['goods'] as $value){
                $sku_upc[$value['sku_id']] = $value['upc_id'];
            }
            foreach ($sell_company_cd as $key => $value) {
                if ($pur_company_cd != $value['our_company']) {
                    $sku_quantity = $value['occupy_num'];
//                    $unit_price = $value['unit_price'];
                    $unit_price = $value['unit_price_origin'];
                    $rel_price = $sku_quantity * $unit_price * 1.01;
                    $saveData = array(
                        'rel_trans_no' => RelatedTransactionService::createPaymentNO(),
                        'ord_id' => $allo['allo_no'],
                        'order_no' => $allo['allo_no'],
                        'order_id' => $allo['id'],
                        'trigger_type' => $trigger_type,
                        'pur_company_cd' => $pur_company_cd,
                        'sell_company_cd' => $value['our_company'],
                        'sku_id' => $value['skuId'],
                        'upc_id' => isset($sku_upc[$value['skuId']]) ? $sku_upc[$value['skuId']] : $value['skuId'],
                        'sku_quantity' => $sku_quantity,
                        'rel_currency_cd' => $value['currency_id'],
                        'rel_price' => $rel_price,
                        'rel_time' => date('Y-m-d H:i:s'),
                        'operation_user' => userName(),
                        'create_by' => userName(),
                        'create_at' => date('Y-m-d H:i:s'),
                    );
                    $ret = M('rel_trans', 'tb_fin_')->add($saveData);
                    //var_dump(M()->_sql());die;
                    if ($ret === false) {
                        $result['code'] = 400;
                        $result['msg'] = '自动创建关联交易订单失败';
                        return $result;
                    } else {
                        $tmpe[] = array(
                            'relatOrderId' => $saveData['rel_trans_no'],
                            'batchId' => $value['batch_id'],
                        );
                    }

                }
            }
            if (!empty($tmpe)) {
                $result['data'][] = array(
                    'orderId' => $allo['allo_no'],
                    'spTeamCd' => $saveData['sell_company_cd'],
                    'conCompanyCd' => $saveData['pur_company_cd'],
                    'oprateId' => DataModel::userId(),
                    'type' => $trigger_type,
                    'data' => $tmpe
                );
            }
            return $result;
        }
    }
    /**
     * 调用接口 处理关联交易订单
     * @param $rel_trans
     */
    public function disposeRelTransOrder($requestData){
        $result = array(
            'code' => 200,
            'msg' => "",
            'data' => [],
        );
        if ($requestData) {
            $response = (new WmsModel())->disposeRelTransOrder($requestData);
            if ($response == false or $response == null) {
                foreach ($requestData as $key => $value) {
                    $result['code'] = 400;
                    $result['msg'] = L('调拨入库(关联交易批次)接口通信失败');
                    $rel_trans_on = array_column($value['data'],'relatOrderId');
                    $this->batchDelete($rel_trans_on,3);
                }
            } else {
                if ($response['code'] == 2000) {
                    $response = $response ['data'];
                    foreach ($response as $key => $value) {
                        if ($value ['code'] != 2000) {
                            $rel_trans_on = array_column($value['data'], 'orderId');
                            $this->batchDelete($rel_trans_on, 3);
                        }
                    }
                } else {
                    foreach ($requestData as $key => $value) {
                        $result['code'] = 400;
                        $result['msg'] = L('调拨入库(关联交易批次)接口通信返回失败');
                        $rel_trans_on = array_column($value['data'], 'orderId');
                        $this->batchDelete($rel_trans_on, 3);
                    }
                }
            }
        }
        return $result;
    }
}