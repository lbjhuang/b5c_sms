<?php

/**
 * User: yangsu
 * Date: 19/06/17
 * Time: 11:31
 */


import('ORG.Util.Date');// 导入日期类
/**
 * Class AllocationExtendNewService
 */
class AllocationExtendNewService extends Service
{
    /**
     * @see AllocationExtendNewRepository
     * @var AllocationExtendNewRepository
     */
    protected $repository;

    /**
     * @var array
     */
    private $service_fee_maps = [
        'work' => [
            'operating_expenses_cny' => '作业费用',
            'value_added_service_fee_cny' => '增值服务费',
        ],
        'out_stocks' => [
            'insurance_fee_cny' => '保险',
        ],
        'in_stocks' => [
            'shelf_cost_cny' => '上架费用',
            'value_added_service_fee_cny' => '增值服务费',
        ],
    ];
    private $logistics_state = [
        '订舱安排中',
        '已出库，待离港',
        '航行中',
        '清关中',
        '清关完成，待送仓',
        '已送仓，待上架',
        '上架中',
        '上架完成'
    ];
    /**
     * @var array
     */
    private $logistics_fee_maps = [
        'outbound_cost_cny' => '出库费用',
        'head_logistics_fee_cny' => '头程费用',
    ];

    public static $out_stocks_node_type_map = [
        2 => "已出库，待离港",
        3 => "航行中",
        4 => "清关中",
        5 => "清关完成，待送仓",
        6 => "已送仓，待上架",
        7 => "上架中",
        8 => "上架完成",
    ];

    public static $out_stock_node_types = [
        1 => "下单",
        2 => "出库",
        3 => "离港",
        4 => "到港",
        5 => "清关",
        6 => "送仓",
        7 => "开始上架",
        8 => "上架完成",
    ];

    public static $outStockLogisticsCustomAttributes = [
        "shipping_company_name"=> '船公司',
        'transport_company_id' => '运输公司',
        'outbound_cost_currency_cd' => '出库费用币种',
        'outbound_cost' => '出库费用',
        'head_logistics_fee_currency_cd' => '头程物流费用币种',
        'head_logistics_fee' => '头程物流费用',
        'have_insurance' => '有无保险',
        'insurance_claims_cd' => '保险理赔',
        'insurance_coverage_cd' => '保险范围',
        "oversea_in_storage_no"=> '海外仓入库单号',
        'insurance_fee_currency_cd' => '保险费用币种',
        'insurance_fee' => '保险费用',
        'tracking_number'=>'快递单号',
        'send_warehouse_way'=>'送仓方式',
        'planned_transportation_channel_cd'=>'运输渠道',
        'third_party_warehouse_entry_number'=> '入仓单号/So号',
        'customs_clear'=> '清关方式',
        'cube_feet_type'=> '计费重/材积类型',
        'cube_feet_val'=> '计费重/材积',
        'out_plate_number_type'=> '出库板数类型',
        'out_plate_number_val' => '出库板数',
        'cabinet_type' => '柜型',
        'cabinet_number' => '柜号',
        'strip_p_seal' => '封条',
        'insurance_type'=> '保险缴纳方',
        "insurance_claims_cd_val"=> '保险费率',
    ];
    /**
     * AllocationExtendNewService constructor.
     */
    const CNY_CD = 'N000590300';

    /**
     * AllocationExtendNewService constructor.
     */
    public function __construct($external_model = null)
    {
        $this->repository = new AllocationExtendNewRepository($external_model);
    }

    /**
     * @param $allo_id
     *
     * @return array
     * @throws Exception
     */
    public function getAlloDetail($allo_id)
    {
        $info = CodeModel::autoCodeOneVal(
            $this->repository->getAlloInfo($allo_id),
            ['state', 'allo_out_warehouse_cd', 'allo_in_warehouse_cd', 'planned_transportation_channel_cd',
                'allo_out_team', 'allo_in_team','small_team_cd']
        );
        if (empty($info)) {
            throw new Exception(L('查询异常，请检查调拨单 ID'));
        }
        $goods = CodeModel::autoCodeTwoVal(
            $this->repository->getAlloGoods($allo_id),
            ['state', 'tax_free_sales_unit_price_currency_cd', 'allo_out_warehouse_cd', 'allo_in_warehouse_cd',
                'planned_transportation_channel_cd',]
        );
        #transfer_authentic_products
        
        #获取库存归属的
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        $change_order_no = $AllocationExtendAttributionService->getAttrChangeOrderByAlloId($allo_id);
        
        
        if($change_order_no)  list($info['total_value_goods'], $batch_orders_attr) = $this->getTotalValueGoodsAttr($change_order_no);
        list($info['total_value_goods'], $batch_orders) = $this->getTotalValueGoods($info['allo_no']);
        if (count($batch_orders_attr) > 0) {
            
            $batch_orders = array_column($batch_orders, null, 'SKU_ID');
            
            $batch_orders_sku = array_column($batch_orders, 'SKU_ID');
            foreach($batch_orders_attr as $value){
                if(!in_array($value['SKU_ID'], $batch_orders_sku)){
                    $batch_orders[$value['SKU_ID']] = $value;
                }else{
                    $batch_orders[$value['SKU_ID']]['occupy_num']+=$value['occupy_num'];
                    $batch_orders[$value['SKU_ID']]['demand_allo_num'] += $value['demand_allo_num'];
                }
            }
            $batch_orders = array_values($batch_orders);
        }
        
        $info['total_sales'] = (float)$this->getTotalSales($goods);
       
        $goods = SkuModel::getInfo($goods, 'sku_id',
            ['spu_name', 'attributes', 'image_url', 'product_sku',]
        );
       
        $allo_batch = $this->repository->transferProducts($allo_id);
      
        if ($change_order_no)  $allo_batch_attr = $this->repository->transferProductsAtto($change_order_no);
        if(count($allo_batch_attr) > 0 ){
            // $allo_batch = array_column($allo_batch, null, 'SKU_ID');
            foreach ($allo_batch as $key => $value) {
                unset($allo_batch[$key]);
                $allo_batch[$value['SKU_ID']. $value['vir_type']] = $value;
            }
          
            $allo_batch_sku = array_column($allo_batch, 'SKU_ID');
            foreach ($allo_batch_attr as $value) {
                if (!in_array($value['SKU_ID'], $allo_batch_sku)) {
                    $allo_batch[$value['SKU_ID'] . $value['vir_type']] = $value;
                } else{
                    if(!empty($allo_batch[$value['SKU_ID'] . $value['vir_type']]['vir_type']) && $value['vir_type'] == $allo_batch[$value['SKU_ID'] . $value['vir_type']]['vir_type']){
                        $allo_batch[$value['SKU_ID'] . $value['vir_type']]['sum_occupy_num'] += $value['sum_occupy_num'];
                        $allo_batch[$value['SKU_ID']. $value['vir_type']]['demand_allo_num'] += $value['demand_allo_num'];
                    }else{
                        $allo_batch[$value['SKU_ID']. $value['vir_type']] = $value;
                    }
                    
                }
            }
            $allo_batch = array_values($allo_batch);
        }
        $LocationService = new LocationService();
        
        $goods = $this->assemblyGoodsCost($goods, $batch_orders, $allo_batch);
        
        $LocationService->obtain($info['allo_out_warehouse_cd'], array_column($goods, 'sku_id'), $goods);
        $out_stocks = SkuModel::getInfo(
            $this->repository->getOutStock($allo_id),
            'sku_id',
            ['spu_name', 'attributes', 'image_url', 'product_sku',]
        );
       
        $LocationService->obtain($info['allo_out_warehouse_cd'], array_column($out_stocks, 'sku_id'), $out_stocks);
       
        $in_stocks = SkuModel::getInfo(
            $this->repository->getInStock($allo_id),
            'sku_id',
            ['spu_name', 'attributes', 'image_url', 'product_sku',]
        );
        $LocationService->obtain($info['allo_out_warehouse_cd'], array_column($in_stocks, 'sku_id'), $in_stocks);
        $works = $this->repository->getWork($allo_id);
        Logs(['$goods' => $goods], __FUNCTION__, __CLASS__);
        #查一下出入库负责人  只有他们可以进行某些操作

        #查询调入仓库的调拨入库负责人 
        $allo_in_warehouse_user  = $this->repository->getWarehouse($info['allo_in_warehouse_cd'], 'transfer_warehousing_by');
        // $allo_out_warehouse_user  = $this->repository->getWarehouse($info['allo_out_warehouse_cd'], 'transfer_out_library_by');
        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);
        // $allo_out_warehouse_user = explode(',', $allo_out_warehouse_user);
        $is_auth_edit = 1;
        $is_auth_push_message = 1;
        if(!in_array(userName(), $allo_in_warehouse_user)){
            #无权限操作  编辑  更新节点  入库按钮  
            $is_auth_edit = 0;
        }
        if($info['create_user'] != userName()){
            #无权限操作催办按钮
            $is_auth_push_message = 0;
        }
        #为每个出库的sku统计入库数量
        #新增编辑字段判断、更新节点、入库、催办
        $in_stocks_num =  $this->repository->getNewInStock($allo_id, $out_stocks);
        $out_stocks_num =  $this->repository->getNewOutStock($allo_id);
       
        $in_stocks_num = array_column($in_stocks_num,null, 'out_stock_id_sku');
        $out_stocks_num = array_column($out_stocks_num, null, 'sku_id');

        #查一下调拨数量
        $allo_batch_key = [];
        // $allo_batch_key = array_column($allo_batch,null, 'SKU_ID');
        foreach($allo_batch as $k1=>$v1){
            $allo_batch_key[$v1['SKU_ID'].$v1['vir_type']] = $v1;
        }
       
        #查询系统预估上架时间  实时计算
        $node_system_plat_time = [

        ];
        $info['is_optimize_team'] = 0;
        if(in_array($info['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams))
        {
            $info['is_optimize_team'] = 1;
        }
        foreach($out_stocks as $key=>$value){
           
            $out_stocks[$key]['is_auth_edit'] = $is_auth_edit;
            $out_stocks[$key]['is_auth_push_message'] = $is_auth_push_message;
            if(!empty($in_stocks_num[$value['out_stocks_id'] . '-' . $value['sku_id']])){
                #已入库
                $out_stocks[$key]['in_sum'] = $in_stocks_num[$value['out_stocks_id'] . '-' . $value['sku_id']]['in_authentic_sum'] + $in_stocks_num[$value['out_stocks_id'] . '-' . $value['sku_id']]['in_defective_sum'];
                #差异数量
                $out_stocks[$key]['differ_num'] = $value['this_out_authentic_products'] + $value['this_out_defective_products'] - $out_stocks[$key]['in_sum'];
            } else {
                $out_stocks[$key]['in_sum'] = 0;
                $out_stocks[$key]['differ_num'] = $value['this_out_authentic_products'] + $value['this_out_defective_products'];
            }
            $out_stocks[$key]['ori_logistics_state'] = $value['logistics_state'];
            #编辑按钮=》  状态为上架完成  无法继续编辑物流信息   或者完整编辑过  不可再编辑
          
            if ($value['logistics_state'] == 8 || $value['send_warehouse_way'] == 'N003460002' || ($value['send_warehouse_way'] == 'N003460001' && !empty($value['tracking_number'])) ) {
                
                $out_stocks[$key]['is_edit'] = 0;
               
            } else {
                $out_stocks[$key]['is_edit'] = 1;
            }

          
            //填写过物流信息才能更新节点 且状态为未完结
            if($value['transport_company_id'] > 0 && $value['logistics_state'] != 8){
                $out_stocks[$key]['node_update'] = 1;
            }else{
                $out_stocks[$key]['node_update'] = 0;
            }
            #状态为上架中才能入库
            if ($value['logistics_state'] == 7) {
                $out_stocks[$key]['in_stock'] = 1;
            } else {
                $out_stocks[$key]['in_stock'] = 0;
            }
            #状态为完结   不可点击催办   0为禁用  1为可点击  2为已催办
            if($value['logistics_state'] == 8){
                $out_stocks[$key]['send_wechat_message'] = 0;
            }else if(!$value['wechat_message_time'] || date('Y-m-d', $value['wechat_message_time']) != date('Y-m-d') ){
                $out_stocks[$key]['send_wechat_message'] = 1;
            }else{
                $out_stocks[$key]['send_wechat_message'] = 2;
            }
           
            if(!empty($allo_batch_key[$v1['SKU_ID'] . 'N002440100'])){
                $out_stocks[$key]['transfer_authentic_products'] = $allo_batch_key[$v1['SKU_ID'] . 'N002440100']['sum_occupy_num']; //调拨正品
            }else{
                $out_stocks[$key]['transfer_authentic_products'] = 0;
            }
           
            if (!empty($allo_batch_key[$v1['SKU_ID'] . 'N002440400'])) {
               
                $out_stocks[$key]['transfer_defective_products'] = $allo_batch_key[$v1['SKU_ID'] . 'N002440400']['sum_occupy_num']; //调拨次品
            } else {
                $out_stocks[$key]['transfer_defective_products'] = 0;
            }
           
            #本次出库 = 调拨-已出库
            $out_stocks[$key]['this_out_authentic_products'] = $out_stocks[$key]['transfer_authentic_products'] -  $out_stocks_num[$value['sku_id']]['authentic_sum'];
            $out_stocks[$key]['this_out_defective_products'] = $out_stocks[$key]['transfer_defective_products'] -  $out_stocks_num[$value['sku_id']]['defective_products'];
            
            #已出库_全部
            $out_stocks[$key]['number_authentic_outbound'] = $out_stocks_num[$value['sku_id']]['authentic_sum']; //已出库正品
            $out_stocks[$key]['number_defective_outbound'] = $out_stocks_num[$value['sku_id']]['defective_products']; //已出库次品

            #已出库_本次出库记录下的(入库页面的已出库显示这个)
            $out_stocks[$key]['number_authentic_outbound_this'] = $value['this_out_authentic_products']; //已出库正品
            $out_stocks[$key]['number_defective_outbound_this'] = $value['this_out_defective_products']; //已出库次品

            $out_stocks[$key]['number_authentic_warehousing'] = $in_stocks_num[$value['out_stocks_id'] . '-' . $value['sku_id']]['in_authentic_sum']; //已入库正品
            $out_stocks[$key]['number_defective_warehousing'] = $in_stocks_num[$value['out_stocks_id'] . '-' . $value['sku_id']]['in_defective_sum'];//已入库次品

       
            #本次入库   当前已经出库的记录下的数目-已经入库
            $out_stocks[$key]['this_in_authentic_products'] = $value['this_out_authentic_products'] - $out_stocks[$key]['number_authentic_warehousing']; //本次入库正品
            $out_stocks[$key]['this_in_defective_products'] = $value['this_out_defective_products'] - $out_stocks[$key]['number_defective_warehousing']; //本次入库次品

            #由于出库不会出次品  只有入库会出次品  所以累加
            $out_stocks[$key]['this_in_authentic_products'] = $out_stocks[$key]['this_in_authentic_products'];
            $out_stocks[$key]['this_in_defective_products'] = $out_stocks[$key]['this_in_defective_products'];
            $out_stocks[$key]['planned_transportation_channel_cd_val'] = $out_stocks[$key]['planned_transportation_channel_cd'];
            if($out_stocks[$key]['logistics_state'] > 1){
                $out_stocks[$key]['logistics_state'] = $this->logistics_state[$out_stocks[$key]['logistics_state'] - 1];
            }else{
                $out_stocks[$key]['logistics_state'] = '';
            }
           
            if($out_stocks[$key]['logistics_state'] == '已出库，待离港' && $info['is_cn_warehouse'] == 1){
                $out_stocks[$key]['logistics_state'] = '已出库，运输中';
            }

            $out_stocks[$key]['transport_company_id'] = !empty($out_stocks[$key]['transport_company_id']) ? $out_stocks[$key]['transport_company_id'] : '';
            $out_stocks[$key]['have_insurance'] = !empty($out_stocks[$key]['have_insurance']) ? $out_stocks[$key]['have_insurance'] : '';
            $out_stocks[$key]['out_plate_number_type'] = !empty($out_stocks[$key]['out_plate_number_type']) ? $out_stocks[$key]['out_plate_number_type'] : '';
            $out_stocks[$key]['out_plate_number_val'] = !empty($out_stocks[$key]['out_plate_number_val']) ? $out_stocks[$key]['out_plate_number_val'] : '';
            

            if(!empty($node_system_plat_time[$value['out_stock_id']])){
                $out_stocks[$key]['node_system_plan_time'] =  $node_system_plat_time[$value['out_stock_id']];
            }else{
                //获取
                $out_stocks[$key]['node_system_plan_time'] = $this->getSystemEndNodeTime($allo_id, $value['out_stock_id']);
                $node_system_plat_time[$value['out_stock_id']] =  $out_stocks[$key]['node_system_plan_time'];

            }

            #根据 调拨id 出库记录Id  sku查找入库记录集合
            $out_stocks[$key]['in_stock_list'] = $this->repository->getNewInStockListBySku($allo_id,$value['out_stock_id'],$value['sku_id']);
            if($out_stocks[$key]['insurance_claims_cd_val']) {
                unset($out_stocks[$key]['insurance_claims_cd']);
            }
            $out_stocks[$key]['is_optimize_team'] = 0;
            if($info['is_optimize_team']) {
                $out_stocks[$key]['is_optimize_team'] = 1;
            }
        }
       
        $out_stocks = CodeModel::autoCodeTwoVal($out_stocks, [
            'send_warehouse_way', 'cabinet_type', 'customs_clear', 'planned_transportation_channel_cd_val'
        ]);

        $res = [
            "info" => $info,
            "goods" => $goods,
            "profit" => [
                "gross_profit" => "",
                "gross_profit_margin" => "",
            ],
            "work" => CodeModel::autoCodeOneVal($works,
                ['operating_expenses_currency_cd', 'value_added_service_fee_currency_cd']
            ),
            "out_stocks" => CodeModel::autoCodeTwoVal($out_stocks,
                ['transport_company_cd', 'outbound_cost_currency_cd', 'insurance_claims_cd',
                    'insurance_coverage_cd', 'insurance_fee_currency_cd', 'head_logistics_fee_currency_cd']),
            "in_stocks" => CodeModel::autoCodeTwoVal($in_stocks,
                ['shelf_cost_currency_cd', 'value_added_service_fee_currency_cd', 'tariff_currency_cd']),
        ];
        return $res;
    }
    /**
     * @param $allo_id
     * @param $out_stock_id
     * @param array
     *
     * @return mixed
     */
    private function getSystemEndNodeTime($allo_id, $out_stock_id)
    {
        $Model = new Model();
        $this->repository->external_model = $Model;
        #获取上架完成系统预估时间
        $re = $this->repository->getOutStockNode($allo_id, $out_stock_id, $Model);
        # $re = array_column($re,NULL,'type');
        #计算系统预估时间
        foreach ($re as $key => $value) {
          
            #第二个开始且上个节点填写了操作时间 得出系统预估时间
            if ($key > 0 && $re[$key - 1]['node_operate']) {
                $re[$key]['node_system_plan'] = date('Y-m-d', (strtotime($re[$key - 1]['node_operate']) + (strtotime($re[$key]['node_plan']) - strtotime($re[$key - 1]['node_plan']))));
            }
            if ($key > 0 && empty($re[$key - 1]['node_operate']) && !empty($re[$key - 1]['node_system_plan'])) {
                $re[$key]['node_system_plan'] = date('Y-m-d', (strtotime($re[$key - 1]['node_system_plan']) + (strtotime($re[$key]['node_plan']) - strtotime($re[$key - 1]['node_plan']))));
            }
        }
        $re = array_column($re,NULL ,'type');
        $finish_node = isset($re[8]) ? $re[8] : [];
        return ($finish_node ) ?  $finish_node['data_type'] ==1 ? $finish_node['scheduled_date'] : $finish_node['node_system_plan'] : '';
    }

    /**
     * @param $allo_id
     *
     * @return array
     * @throws Exception
     */
    public function allo_batch_data($allo_id)
    {
        $allo = $this->repository->getAlloInfo($allo_id);
        $allo_batch = $this->repository->transferProducts($allo_id);
        return [$allo,$allo_batch];
    }

    /**
     * @param $allo_id
     *
     * @return array
     * @throws Exception
     */
    public function get_allo_batch($allo_id)
    {
        $allo_batch = $this->repository->transferProducts($allo_id);
        return $allo_batch;
    }
    public function transferProductsAtto($allo_id)
    {
        $allo_batch = [];
        $change_order_no = M('wms_allo_attribution', 'tb_')->where(['allo_id'=>$allo_id, 'deleted_by'=> array('EXP', 'IS NULL')])->getField('change_order_no');
        if($change_order_no){
            $allo_batch = $this->repository->transferProductsAtto($change_order_no);
            
        }
        return $allo_batch; 
        
    }
    public function getAttrByAlloId($allo_id){
        return $this->repository->getAttrByAlloId($allo_id);
    }
    /**
     * @param $goods
     * @param $batch_orders
     * @param $allo_batchs
     *
     * @return mixed
     */
    private function assemblyGoodsCost($goods, $batch_orders, $allo_batchs)
    {
       
        foreach ($allo_batchs as $allo_batch) {
            $temp_allo_batch[$allo_batch['SKU_ID']][$allo_batch['vir_type']] = $allo_batch['sum_occupy_num'];
        }
        foreach ($batch_orders as $batch_order) {
            $sku_cost_map[$batch_order['SKU_ID']] = $batch_order;
            //            $sku_cost_map[$batch_order['SKU_ID']]['po_outside_cost_unit_price_cny'] = $this->getPoOutsideCostUnitPriceCny($batch_order);
        }
        foreach ($goods as &$good) {
            $good['average_price_goods_without_tax_cny'] = number_format($sku_cost_map[$good['sku_id']]['average_price_goods_without_tax_cny'], 2);
            $good['average_po_internal_cost_cny'] = number_format($sku_cost_map[$good['sku_id']]['average_po_internal_cost_cny'], 2);
            $good['po_outside_cost_unit_price_cny'] = number_format($sku_cost_map[$good['sku_id']]['po_outside_cost_unit_price_cny'], 2);
            $good['transfer_authentic_products'] = (float)$temp_allo_batch[$good['sku_id']]['N002440100'];
            $good['transfer_defective_products'] = (float)$temp_allo_batch[$good['sku_id']]['N002440400'];
        }
        return $goods;
    }

    /**
     * @param $data
     *
     * @return float|int
     */
    private function getPoOutsideCostUnitPriceCny($data)
    {
        return ($data['log_service_cost'] + $data['carry_cost'] + $data['warehouse_cost']) * $data['occupy_num'] / $data['demand_allo_num'];
    }

    /**
     * @param $allo_no
     *
     * @return array
     */
    private function getTotalValueGoods($allo_no)
    {
        $batch_orders = $this->repository->getBatchOrders($allo_no);
        return [array_sum(array_column($batch_orders, 'total_value_good')), $batch_orders];
    }
    private function getTotalValueGoodsAttr($change_order_no)
    {
        $batch_orders = $this->repository->getBatchOrdersAttr($change_order_no);
        return [array_sum(array_column($batch_orders, 'total_value_good')), $batch_orders];
    }
    
    /**
     * @param $allo_details
     *
     * @return float|int
     */
    private function getTotalSales($allo_details)
    {
        return array_sum(array_column($allo_details, 'total_sales_sku'));
    }

    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    public function editAlloInfo($allo_id, $data, $Model)
    {
        $res = $this->repository->editAlloInfo($allo_id, $data, $Model);
        return $res;
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    public function updateStatusAllo($allo_id, $data)
    {
        switch ($data['type']) {
            case 'submit':
                $res_db = $this->repository->submitAllo($allo_id);
                $this->addLog($allo_id, L('创建调拨单'));
                break;
            case 'delete':
                $res_db = $this->repository->deleteAllo($allo_id);
                $this->releaseOccupancy($allo_id);
                # 删除调拨归属库存变更
                $this->deleteAlloAttribution($allo_id);
                $this->addLog($allo_id, L('删除调拨单'));
                break;
        }
        if (1 !== $res_db) {
            throw new Exception(L('处理失败,请检查状态'));
        }
    }

    /**
     * @param $allo_id
     *
     * @throws Exception
     */
    private function releaseoCcupancy($allo_id)
    {
        $occupy_db = $this->repository->getRemainAlloOccupy($allo_id);
        $occupu_db_attr = $this->repository->getRemainAlloOccupyAttr($allo_id);
        $allo_no = $this->repository->getAlloInfo($allo_id)['allo_no'];
        foreach ($occupy_db as $value) {
            $param[$allo_no . $value['vir_type'] . $value['SKU_ID']] = [
                "orderId" => $allo_no,
                "virType" => $value['vir_type'],
                "skuId" => $value['SKU_ID'],
            ];
        }
        $change_order_no = !empty($occupu_db_attr[0]['change_order_no'])? $occupu_db_attr[0]['change_order_no']:0;
        Logs($occupy_db, __FUNCTION__, __CLASS__);
        if (empty($param) && count($occupu_db_attr) < 1) {
            throw new Exception('无关联占用商品');
        }
        if(!empty($param)){
            $param = array_values($param);
            $res_api = ApiModel::freeUpBatch($param);
            if (2000 != $res_api['code']) {
                throw new Exception(L('API 释放占用失败:') . $res_api['msg']);
            }
        }
       
        if(!empty($change_order_no)){
            $response_api = ApiModel::releaseOccupancyOrder($change_order_no);
            if (2000 != $response_api['code']) {
                throw new Exception(L('库存归属占用取消失败 API') . $response_api['msg']);
            }
        }
        
    }
    /**
     * @param $allo_id 出库完结解除占用
     *
     * @throws Exception
     */
    private function releaseoCcupancyAllo($allo_id)
    {
        $occupy_db = $this->repository->getRemainAlloOccupy($allo_id);
        $occupu_db_attr = $this->repository->getRemainAlloOccupyAttr($allo_id);
        $allo_no = $this->repository->getAlloInfo($allo_id)['allo_no'];
        foreach ($occupy_db as $value) {
            $param[$allo_no . $value['vir_type'] . $value['SKU_ID']] = [
                "orderId" => $allo_no,
                "virType" => $value['vir_type'],
                "skuId" => $value['SKU_ID'],
            ];
        }
        $change_order_no = !empty($occupu_db_attr[0]['change_order_no']) ? $occupu_db_attr[0]['change_order_no'] : 0;
        Logs($occupy_db, __FUNCTION__, __CLASS__);
        if (empty($param) && count($occupu_db_attr) < 1) {
            throw new Exception('无关联占用商品');
        }
        if (!empty($param)) {
            $param = array_values($param);
            $res_api = ApiModel::freeUpBatchAllo($param);
            if (2000 != $res_api['code']) {
                throw new Exception(L('API 释放占用失败:') . $res_api['msg']);
            }
        }

        if (!empty($change_order_no)) {
            $response_api = ApiModel::releaseOccupancyOrder($change_order_no);
            if (2000 != $response_api['code']) {
                throw new Exception(L('库存归属占用取消失败 API') . $response_api['msg']);
            }
        }
    }
    /**
     * 编辑调拨
     * @param $params
     *
     * @throws Exception
     */
    public function editAlloDetail($params)
    {
        if (!$processInfo = TbWmsAlloProcessModel::getProcessInfo($params)) {
            throw new Exception(L('流程已失效，请重新编辑'));
        }
        $uuid = $processInfo ['uuid'];
        if (!(new TbWmsAlloModel())->getProcessChildData($uuid)) {
            throw new Exception(L('异常：[当前流程无调拨数据更改，不能提交]'));
        }
        //先释放占用
        $this->releaseoCcupancy($params['allo_id']);
        $this->addLog($params['allo_id'], L('释放占用'));
        //编辑调拨并重新占用
        $model = new TbWmsAlloModel();
        $ret = $model->editAllo($params);
        return $ret;
    }


    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    public function updateReviewAllo($allo_id, $data, $Model)
    {
        $this->repository->external_model = $Model;
        switch ($data['type']) {
            case 0:
                $type_cd = 'N001970500';
                $wechat_review_status = 2;
                $this->checkReviewPermission($allo_id);
                break;
            case 1:
                $type_cd = 'N001970602';
                $wechat_review_status = 1;
                $this->checkReviewPermission($allo_id);
                break;
            case 2:
            case 3:
                $type_cd = 'N001970601';
                break;
        }
        if ($data['user']) {
            $updated_by = $data['user'];
        } else {
            $updated_by = DataModel::userNamePinyin();
        }
        $attr_id = 0;
        $attr_status = "";
         #将库存归属占用转为调拨且同意
        if(1 === $wechat_review_status || 2 === $wechat_review_status){
          
            $review_type_cd = $wechat_review_status == 1? "N003000002": "N003000003";
            $AllocationExtendAttributionService = new AllocationExtendAttributionService();
            $attr = $AllocationExtendAttributionService->getAttrByAlloId($allo_id);
            $attr_id = $attr['id'];
            $attr_status = $attr['review_type_cd'];
            if($attr_id && $attr_status == 'N003000001'){
                //审核库存归属单
                $attr_data = ['id' => $attr_id, 'review_type_cd' => $review_type_cd];
                $AllocationExtendAttributionService->approvalByAllocation($attr_data);
            }
           
        }
        
        if (1 != $this->repository->updateReviewAllo($allo_id, $type_cd, $updated_by)) {
            throw new Exception(L('处理失败,请检查调拨状态'));
        }
        if (1 === $wechat_review_status){
            #发送api请求
          
           
            if ($attr_id && $attr_status == 'N003000001') {
               
                $allo_attribution = $this->repository->attribution($attr_id);
                $allo_no = $this->repository->getAlloNoById($allo_id);
                $apiData = $this->ascriptionRecordGenerateApi($allo_attribution,$allo_no);
                #回写批次 start
                
                $reply = [];
                $updateData = [];
                foreach($apiData['data'] as $key=>$value){
                    if(in_array($value['skuId'], $reply)){
                        $updateData[$value['skuId']]['batchId'] .= ','.$value['batchId'];
                       
                    }else{
                        $updateData[$value['skuId']] = $value;
                        array_push($reply, $value['skuId']);
                    }

                }

                $this->repository->updateAlloChild($updateData,$allo_id);

            }
            
            

        }
        if (isset($wechat_review_status) && in_array($wechat_review_status, [1, 2])) {
            if (false === $this->repository->updateReviewAlloWeChat($allo_id, $wechat_review_status, $updated_by)) {
                Logs([$allo_id, $wechat_review_status, $updated_by], __FUNCTION__, __CLASS__);
                throw new Exception(L('微信处理失败,请检查调拨状态'));
            }
        }
       



        if (2 === $wechat_review_status) {
            $this->releaseoCcupancy($allo_id);
        }
        if (1 === $wechat_review_status) {
            $this->hairlineDelivery($allo_id);
        }
    }
    private function ascriptionRecordGenerateApi($allo_attribution,$allo_no)
    {
        switch ($allo_attribution['change_type_cd']) {
            case 'N002990001':
                $type = 3;
                break;
            case 'N002990002':
                $type = 1;
                break;
            case 'N002990003':
                $type = 2;
                break;
            case 'N002990005':
                $type = 4;
                break;
        }
        $data = [
            "orderId" => $allo_attribution['change_order_no'],
            "type" => $type,
            "style" => $allo_attribution['new'],
            "operatorId" => DataModel::userId(),
            "alloOrderId"=> $allo_no
        ];
       
        $response_api = ApiModel::homeTransferAttr($data);
        
        if (2000 != $response_api['code']) {
            throw new Exception(L('订单审批失败 API ') . $response_api['msg']);
        }
        return $response_api;
    }
    /**
     * @param $allo_id
     */
    private function checkReviewPermission($allo_id)
    {
        $allo_review_by = $this->repository->getAlloReviewBy($allo_id);
        if (empty($allo_review_by) && 'erpadmin' !== strtolower(DataModel::userNamePinyin())) {
            throw new Exception(L('无审批权限'));
        }
    }

    /**
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    public function submitWork($data, $Model)
    {
        $this->repository->external_model = $Model;
        if (1 !== $this->repository->updateAlloWorkStatus($data['work'])) {
            throw new Exception(L('处理工作失败,请检查调拨状态'));
        };
        if (1 > $this->repository->addtWork($data['work'])) {
            throw new Exception(L('插入工作失败'));
        };
        $this->repository->submitWorkGoods($data['work']['allo_id'], $data['goods']);
    }

    private function hairlineDelivery($allo_id)
    {
        $allo_info = $this->repository->getAlloInfo($allo_id);
        $goods = $this->repository->getAlloGoods($allo_id);
        if (null !== $allo_info['use_fawang_logistics'] && (1 == $allo_info['use_fawang_logistics'] || 0 == $allo_info['use_fawang_logistics'])) {
            foreach ($goods as $good) {
                $temp_sku_list[] = [
                    "b5cSkuId" => $good['sku_id'],
                    "quantity" => $good['demand_allo_num'],
                ];
            }
            $data = [
                "orderId" => $allo_info['allo_no'],
                "warehouse" => $allo_info['allo_out_warehouse'],
                "isnotNet" => $allo_info['use_fawang_logistics'],
                "skuList" => $temp_sku_list,
            ];
            $Model = new Model();
            $where['deleted_by'] = ['EXP', 'IS NULL'];
            $filter_orders = $Model->table('tb_wms_allo_filters')
                ->field('filter_order')
                ->where($where)
                ->order('id desc')
                ->limit(200)
                ->select();
            if (!in_array($allo_info['allo_no'], array_column($filter_orders, 'filter_order'))) {
                $res_api = ApiModel::sendFaWang($data);
                if (2000 != $res_api['code']) {
                    @SentinelModel::addAbnormal($allo_info['allo_no'], 'API 发网请求异常:' . $res_api['data'], $res_api['msg'] . $res_api['data']);
                    throw new Exception(L('API 发网请求异常:') . $res_api['data']);
                }
            } else {
                @SentinelModel::addAbnormal($allo_info['allo_no'], '跳过发网请求', '');
            }
        }
    }

    /**
     * @param $allo_id
     *
     * @throws Exception
     */
    public function waitingAssignmentWithdrawn($allo_id)
    {
        if (false === $this->repository->deleteWork($allo_id)) {
            throw new Exception('删除作业单失败');
        }
        if (1 != $this->repository->waitingAssignmentWithdrawn($allo_id)) {
            throw new Exception('撤回失败请检查状态');
        }
    }
    /**更新出库物流信息
     * @param $allo_id
     *
     * @throws Exception
     */
    public function submitOutStockLogistics($allo_id, $out_stock_id,$data, $Model)
    {
        
        $this->repository->external_model = $Model;
       
        
        $re = $this->repository->submitOutStockLogisticsInfo($allo_id,$out_stock_id ,$data['logistics_information']);
        if ($re === false) {
            throw new Exception(L('更新出库物流信息失败'));
        };
      
    }
    /**获取出库物流节点
     * @param $allo_id
     *
     * @throws Exception
     */
    public function getOutStockNode($allo_id, $out_stock_id, $Model)
    {
        
        $this->repository->external_model = $Model;
        $re = $this->repository->getOutStockNode($allo_id, $out_stock_id);
        $node_id = array_column($re, 'id');

        #获取误差原因
        $reason_last = $this->repository->getOutStockNodeReason($node_id);
        $reason = [];
        foreach ($reason_last as $value) {
            if (isset($reason[$value['node_id']])) {
                $reason[$value['node_id']][] = $value;
            } else {
                $reason[$value['node_id']] = [];
                $reason[$value['node_id']][] = $value;
            }
        }



        #是否出入库都为国内仓库 1为是 0为否 默认为0
        $last_data_allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $is_cn_warehouse = $last_data_allo ? $last_data_allo['is_cn_warehouse'] : 0;

      
        $allo_in_warehouse_user  = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $last_data_allo['allo_in_warehouse']])->getField('transfer_warehousing_by');

        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);
        $is_auth = 0;
        if (in_array(userName(), $allo_in_warehouse_user)) {
            #无权限编辑和
            $is_auth = 1;
        }
        $is_optimize_team     = 0;
        if (in_array($last_data_allo['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams)) {
            $is_optimize_team = 1;
        }
        # 初次进入 需要获取报价和出库的数据记录
        if(empty($re) && $is_optimize_team) {

        }

        #计算系统预估时间
        foreach ($re as $key=>$value){
            $re[$key]['is_unusual'] = 0;
            $re[$key]['is_auth'] = $is_auth;
            #第二个开始且上个节点填写了操作时间 得出系统预估时间
            if ($key > 0 && $re[$key - 1]['node_operate']) {
                $re[$key]['node_system_plan'] = date('Y-m-d', (strtotime($re[$key - 1]['node_operate']) + (strtotime($re[$key]['node_plan']) - strtotime($re[$key - 1]['node_plan']))));
            }
            if ($key > 0 && empty($re[$key - 1]['node_operate']) && !empty($re[$key - 1]['node_system_plan'])) {
                $re[$key]['node_system_plan'] = date('Y-m-d', (strtotime($re[$key - 1]['node_system_plan']) + (strtotime($re[$key]['node_plan']) - strtotime($re[$key - 1]['node_plan']))));

            }

            #计算出是否误差异常
            if (!empty($re[$key]['node_operate']) && strtotime($re[$key]['node_operate']) >  strtotime($re[$key]['node_plan'])) {
                $re[$key]['is_unusual'] = 1;
            }
            if(empty($re[$key]['node_system_plan'])){
                $re[$key]['node_system_plan'] = '';
            }
            $re[$key]['reason'] = $reason[$value['id']] ? $reason[$value['id']] : [];

        }

        $data_date = $this->getOutStockLogisticsNodeInitDate($allo_id, $out_stock_id);
        $info = [
            'is_cn_warehouse' => $is_cn_warehouse,
        ];
        $info = array_merge($info, $data_date);
        $is_optimize_team     = 0;
        if (in_array($last_data_allo['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams)) {
            $is_optimize_team = 1;
        }
        $info['show_new_table'] = 0;
        if(is_null($re)) {
            if($is_optimize_team) {
                $info['show_new_table'] = 1;
            }
        }
        else {
            $current_item = current($re);
            if($current_item['data_type']) {
                $info['show_new_table'] = 1;
            }
            #
            $last_in_stock = $this->getLastInStockRecord( $out_stock_id);
            if($last_in_stock) {
                $re2 = array_column($re,NULL,'type');
                if(isset($re2['8'])) {
                    $re2[8]['node_operate'] = date("Y-m-d", strtotime($last_in_stock['created_at']));
                }
                $re = array_values($re2);
            }
        }
        $out_stock_node_types = self::$out_stock_node_types;
        if ($is_cn_warehouse) {
            $out_stock_node_types = array_diff($out_stock_node_types, array_slice($out_stock_node_types, 2, 3, true));
        }

        return ['list' => $re, 'info' => $info, 'node_types' => $out_stock_node_types];
       
    }
    /**更新出库物流节点
     * @param $allo_id
     *
     * @throws Exception
     */
    public function submitOutStockNode($allo_id, $out_stock_id, $data, $Model)
    {

        $this->repository->external_model = $Model;
        $re = $this->repository->submitOutStockNode($allo_id, $out_stock_id, $data);
        if ($re === false) {
            throw new Exception(L('更新出库物流节点失败'));
        };
    }
    /**更新出库物流节点-异常原因
     * @param $allo_id
     *
     * @throws Exception
     */
    public function submitOutStockNodeReason($data, $Model)
    {

        $this->repository->external_model = $Model;
        $re = $this->repository->submitOutStockNodeReason($data);
        if ($re === false) {
            throw new Exception(L('更新出库物流节点失败'));
        };
    }
    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    public function submitOutStock($allo_id, $data, $Model)
    {
        if ('N001970603' !== $this->repository->getAlloStatus($allo_id) ||
            0 != $this->repository->getStockStatus($allo_id, 'out')) {
            throw new Exception(L('新调拨出库状态错误'));
        }
        $this->repository->external_model = $Model;
        $out_stock_id = $this->repository->submitOutStockInfo($allo_id, $data['logistics_information']);
        if (!$out_stock_id) {
            throw new Exception(L('新调拨出库失败'));
        };
        if (1 > $this->repository->submitOutStockGoods($allo_id, $out_stock_id, $data['goods'])) {
            throw new Exception(L('新调拨出库商品失败'));
        }
        $this->updateStockStatus($allo_id, 'out');
        $api_request_data = $this->joinOutApiRequestData($allo_id, $data);
        $res = (new WmsModel())->transferOutLibraryNew($api_request_data);
        Logs(['front_request' => $data, 'request' => $api_request_data, 'response' => $res], __FUNCTION__, __CLASS__);
        if (2000 != $res['code'] || 2000 != $res['data'][0]['code']) {
            @SentinelModel::addAbnormal($allo_id, '新调拨出库API异常' . ':' . $res['msg'], $res);
            throw new Exception(L('新调拨出库API异常') . ':' . $res['msg']);
        }
        $bill_ids = array_column([0]['data']['exportDetail'], 'billId');
        
        foreach ($res['data'] as $temp_data) {
            foreach ($temp_data['data']['exportDetail'] as $temp_datum) {
                $bill_ids[] = $temp_datum['billId'];
            }
        }
        $this->repository->updateOutBillId($out_stock_id, $bill_ids);
        //出库完成发送消息提醒
        $AllocationExtendNewService = new AllocationExtendNewService();
        $res = DataModel::$success_return;
        $res['data'] = (new ReviewMsgTpl())->sendWeChatReviewTransferNode(
            $AllocationExtendNewService->assemblyWeChatData($allo_id),
            '',
            $out_stock_id,
            2
        );
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function joinOutApiRequestData($allo_id, $data)
    {
        $allo_info = $this->repository->getAlloInfo($allo_id);
        $goods = SkuModel::getInfo($data['goods'], 'sku_id', ['product']);
        $out_librarys = [];
        $user_id = DataModel::userId();
        foreach ($goods as $good) {
            if (($good['this_out_authentic_products'] + $good['this_out_defective_products']) == 0) {
                //排除出库数量为0的记录调用接口
                continue;
            }
            $temp = [
                "skuId" => $good['sku_id'],
                "gudsId" => $good['product']['spu_id'],
                "orderId" => $allo_info['allo_no'],
                "isUseSendNet" => 2,
                "operatorId" => $user_id,
                "num" => (int)$good['this_out_authentic_products'],
                "releaseNum" => 0,
                "brokenNum" => (int)$good['this_out_defective_products'],
                "releaseBrokenNum" => 0,
                "deliveryWarehouse" => $allo_info['allo_out_warehouse'],
                "saleTeamCode" => $allo_info['allo_in_team'],
                //                'deadlineDateForUse' => null,
            ];
            $out_librarys[] = $temp;
        }
        return $out_librarys;
    }

    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    public function submitInStock($allo_id, $out_stock_id,$data, $Model)
    {
        if ('N001970603' !== $this->repository->getAlloStatus($allo_id) ||
            0 != $this->repository->getStockStatus($allo_id, 'in')) {
            throw new Exception(L('新调拨入库状态错误'));
        }
        $this->repository->external_model = $Model;
        $in_stocks_id = $this->repository->submitInStockInfo($allo_id,$out_stock_id, $data['logistics_information']);
        if (!$in_stocks_id) {
            throw new Exception(L('新调拨入库失败'));
        };
       
        if (1 > $this->repository->submitInStockGoods($allo_id, $in_stocks_id, $out_stock_id, $data['goods'])) {
            throw new Exception(L('新调拨入库商品失败'));
        }
       
       
        $this->updateStockStatus($allo_id, 'in');
        $api_request_data = $this->joinInApiRequestData($allo_id, $data);
        $res = (new WmsModel())->transferInLibraryNew($api_request_data);
        Logs(['front_request' => $data, 'request' => $api_request_data, 'response' => $res], __FUNCTION__, __CLASS__);
        if (2000 != $res['code'] || 2000 != $res['data']['allocate'][0]['code']) {
            @SentinelModel::addAbnormal($allo_id, '新调拨入库API异常:' . $res['msg'], $res);
            throw new Exception(L('新调拨出库API异常') . ':' . $res['msg']);
        }
        foreach ($res['data']['allocate'][0]['data'] as $temp_bill_sku) {
            foreach ($temp_bill_sku['data'] as $temp_bill_data) {
                $bill_ids[] = $temp_bill_data['billId'];
                $batch_ids[] = $temp_bill_data['batchId'];
            }
        }
        $this->repository->updateInBillId($in_stocks_id, $bill_ids);
        $this->repository->updateOutAndWorkState($allo_id);
        return $batch_ids;
    }
    public function updateInStatus($allo_id, $out_stock_id, $model)
    {
        # 检测出库
        $out_stock_guds = $model->table('tb_wms_allo_new_out_stock_guds')->where(['allo_id' => $allo_id, 'out_stocks_id' => $out_stock_id])->select();
        $in_stock_guds = $model->table('tb_wms_allo_new_in_stock_guds')->where(['allo_id' => $allo_id, 'out_stock_id' => $out_stock_id])->select();

        if (empty($out_stock_guds)) {
            throw new Exception(L('参数有误'));
        }
        // $out_stock_guds = array_column($out_stock_guds, null, 'sku_id');
        // $in_stock_guds = array_column($in_stock_guds, null, 'sku_id');
        $out_num = array_sum(array_column($out_stock_guds, 'this_out_authentic_products')) + array_sum(array_column($out_stock_guds, 'this_out_defective_products'));
        $in_num = array_sum(array_column($in_stock_guds, 'this_in_authentic_products')) + array_sum(array_column($in_stock_guds, 'this_in_defective_products'));
       
        
        if ($in_num >= $out_num) {
           
            //出库的已经全部入库
            $re1 =  $model->table('tb_wms_allo_new_out_stocks')->where(['id' => $out_stock_id])->save(['stock_in_state' => 1, 'logistics_state'=>8]);
            $re3 =  $model->table('tb_wms_allo_new_out_stocks_node')->where(['out_stock_id' => $out_stock_id,'allo_id'=> $allo_id, 'type' => 8])->save(['node_operate' => date('Y-m-d')]);
           

            #更新物流状态为上架完成
            // $re2 = M('wms_allo_new_out_stocks_node', 'tb_')->where(['allo_id' => $allo_id, 'out_stock_id' => $out_stock_id])->save(['node_operate' => date('Y-m-d H:i:s')]);
            if($re1 == false ||  $re3 == false){
                return false;
            }
         
        }else{
            return true;
        }
    }
   
    
    /**
     * @param $allo_id
     * @param $data
     *
     * @return array
     */
    private function joinInApiRequestData($allo_id, $data)
    {
        $allo_info = $this->repository->getAlloInfo($allo_id);
        $goods = SkuModel::getInfo($data['goods'], 'sku_id', ['product']);
        $good_fees = $this->getInStockGoodFee($allo_id, $data);
        $in_librarys = [];
        $user_id = DataModel::userId();
        foreach ($goods as $good) {
            $temp = [
                "skuId" => $good['sku_id'],
                "gudsId" => $good['product']['spu_id'],
                "num" => $good['this_in_authentic_products'],
                "brokenExportNum" => $good['this_in_defective_products'],
                "operatorId" => $user_id,
                "teamIn" => $allo_info['allo_in_team'],
                "teamOut" => $allo_info['allo_out_team'],
                "warehouseIn" => $allo_info['allo_in_warehouse_cd'],
                "warehouseOut" => $allo_info['allo_out_warehouse_cd'],
                "orderId" => $allo_info['allo_no'],

                "operationServiceCost" => $good_fees[$good['sku_id']]['operationServiceCost'],
                "inAppreciationServiceCost" => $good_fees[$good['sku_id']]['inAppreciationServiceCost'],
                "opAppreciationServiceCost" => $good_fees[$good['sku_id']]['opAppreciationServiceCost'],
                "insuranceServiceCost" => $good_fees[$good['sku_id']]['insuranceServiceCost'],
                "shelfServiceCost" => $good_fees[$good['sku_id']]['shelfServiceCost'],
                "exportCarryCost" => $good_fees[$good['sku_id']]['exportCarryCost'],
                "headerCarryCost" => $good_fees[$good['sku_id']]['headerCarryCost'],
                "tariffCost" => $good_fees[$good['sku_id']]['tariffCost'],

                "operationServiceCurrency" => self::CNY_CD,
                "insuranceServiceCurrency" => self::CNY_CD,
                "shelfServiceCurrency" => self::CNY_CD,
                "exportCarryCurrency" => self::CNY_CD,
                "headerCarryCurrency" => self::CNY_CD,
                "tariffCurrency" => self::CNY_CD,
                "inAppreciationServiceCurrency" => self::CNY_CD,
                "opAppreciationServiceCurrency" => self::CNY_CD,
            ];
            $in_librarys[] = $temp;
        }
        return $in_librarys;
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @return array
     */
    public function getInStockGoodFee($allo_id, $data)
    {
        $goods = $data['goods'];
        $acquisition_fee_details = $this->getFeeDetailDB($allo_id, 0);
        $acquisition_fee_details['sum_out_stock']['insurance_fee_cny'] = array_sum(array_values(array_column($acquisition_fee_details['out_stocks'], 'insurance_fee_cny', 'out_stocks_id')));
        $acquisition_fee_details['sum_out_stock']['outbound_cost_cny'] = array_sum(array_values(array_column($acquisition_fee_details['out_stocks'], 'outbound_cost_cny', 'out_stocks_id')));
        $acquisition_fee_details['sum_out_stock']['head_logistics_fee_cny'] = array_sum(array_values(array_column($acquisition_fee_details['out_stocks'], 'head_logistics_fee_cny', 'out_stocks_id')));

        $acquisition_fee_details['sum_in_stock']['shelf_cost_cny'] = $data['logistics_information']['shelf_cost'] * ExchangeRateModel::conversion($data['logistics_information']['shelf_cost_currency_cd'], 'N000590300');
        $acquisition_fee_details['sum_in_stock']['tariff_cost'] = $data['logistics_information']['tariff'] * ExchangeRateModel::conversion($data['logistics_information']['tariff_currency_cd'], 'N000590300');
        $acquisition_fee_details['sum_in_stock']['op_appreciation_service'] = $data['logistics_information']['value_added_service_fee'] * ExchangeRateModel::conversion($data['logistics_information']['value_added_service_fee_currency_cd'], 'N000590300');
        $fee_temps = [];
        foreach ($goods as $good) {
            $sku_proportion_temp = 1;
            $fee_temps[$good['sku_id']] = [
                "operationServiceCost" => $acquisition_fee_details['work']['operating_expenses_cny'] * $sku_proportion_temp,
                "opAppreciationServiceCost" => $acquisition_fee_details['work']['value_added_service_fee_cny'] * $sku_proportion_temp,

                "insuranceServiceCost" => $acquisition_fee_details['sum_out_stock']['insurance_fee_cny'] * $sku_proportion_temp,
                "exportCarryCost" => $acquisition_fee_details['sum_out_stock']['outbound_cost_cny'] * $sku_proportion_temp,
                "headerCarryCost" => $acquisition_fee_details['sum_out_stock']['head_logistics_fee_cny'] * $sku_proportion_temp,

                "shelfServiceCost" => $acquisition_fee_details['sum_in_stock']['shelf_cost_cny'] * $sku_proportion_temp,
                "tariffCost" => $acquisition_fee_details['sum_in_stock']['tariff_cost'] * $sku_proportion_temp,
                "inAppreciationServiceCost" => $acquisition_fee_details['sum_in_stock']['op_appreciation_service'] * $sku_proportion_temp,
            ];
        }
        return $fee_temps;
    }

    /**
     *
     */
    private function getSkuProportion()
    {


    }


    /**
     * @param $allo_id
     * @param $type
     *
     * @return bool
     * @throws Exception
     */
    public function updateStockStatus($allo_id, $type)
    {
        if (empty($allo_id) || empty($type)) {
            return false;
        }
        $allo_no = $this->repository->getAlloInfo($allo_id)['allo_no'];
        switch ($type) {
            case 'in':
                $res_sum_db = $this->repository->checkInStockNum($allo_id, $allo_no);
                $type_msg = '入库';
                break;
            case 'out':
                $res_sum_db = $this->repository->checkOutStockNum($allo_id, $allo_no);
                $type_msg = '出库';
                break;
        }
        
        foreach ($res_sum_db as $value) {
            $res_sum_type_db[$value['allo_no']][$value['vir_type']] = $value;
        }
        
        $type_arr = ['N002440100', 'N002440400'];
        if ('out' == $type) {
            foreach ($res_sum_type_db as $res_sum_type) {
                foreach ($type_arr as $type_value) {
                    $temp_value = $res_sum_type[$type_value];
                    #正品
                    if($type_value == 'N002440100'){
                        if ($temp_value['sum_authentic_products'] > $temp_value['sum_occupy_num']) {
                            throw new Exception("SKU{$temp_value['SKU_ID']}，正品累计出库数量大于当前需求值");
                        }
                        if (0 < $temp_value['sum_authentic_products'] && empty($res_sum_type['N002440100'])) {
                            throw new Exception("SKU{$temp_value['SKU_ID']}，正品累计出库数量大于当前需求值");
                        }
                    }
                    #次品
                    if ($type_value == 'N002440400') {
                        if ($temp_value['sum_defective_products'] > $temp_value['sum_occupy_num']) {
                            throw new Exception("SKU{$temp_value['SKU_ID']}，残次品累计出库数量大于当前需求值");
                        }
                        if (0 < $temp_value['sum_defective_products'] && empty($res_sum_type['N002440400'])) {
                            throw new Exception("SKU{$temp_value['SKU_ID']}，残次品累计出库数量大于当前需求值");
                        }
                    }
                   
                  
                }
            }
        }
        $this->repository->updateAlloInStatus($allo_id);
        $update_stock_status = $this->repository->updateStockStatus($allo_id, $type);
        if (false === $update_stock_status) {
            @SentinelModel::addAbnormal('调拨邮件出入库状态更新', $allo_id . '异常', $update_stock_status, 'transfer_by');
        }
        return $update_stock_status;
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function getLog($allo_id)
    {
        return $this->repository->getLogs($allo_id);
    }

    /**
     * @param $allo_id
     * @param $reason_difference
     *
     * @return mixed
     * @throws Exception
     */
    public function outboundTagCompletion($allo_id, $reason_difference)
    {
        $outbound_tag_completion = $this->repository->outboundTagCompletion($allo_id, $reason_difference);
        if (false === $outbound_tag_completion) {
            throw new Exception(L('标记出库失败'));
        }
        $this->releaseoCcupancyAllo($allo_id);
        return $outbound_tag_completion;
    }

    /**
     * @param $allo_id
     * @param $reason_difference
     *
     * @return mixed
     * @throws Exception
     */
    public function inboundTagCompletion($allo_id,$out_stock_id, $reason_difference)
    {
        $out_stock_re = M('wms_allo_new_out_stocks','tb_')
        ->where(['allo_id'=>$allo_id,'id'=> $out_stock_id])
        ->save(['logistics_state' => 8, 'stock_in_state' => 1, 'reason_difference' => $reason_difference, 'in_stock_complete_at'=>date('Y-m-d H:i:s'), 'in_stock_complete_by'=>userName()]);
        $out_stock_re_node =  M('wms_allo_new_out_stocks_node', 'tb_')->where(['out_stock_id' => $out_stock_id, 'allo_id' => $allo_id, 'type' => 8])->save(['node_operate' => date('Y-m-d')]);
        if (false === $out_stock_re) {
            throw new Exception(L('标记入库失败'));
        }
        if (false === $out_stock_re_node) {
            throw new Exception(L('标记入库失败'));
        }
        #原本入库完结针对的是调拨单  现在主题修改为针对每次出库记录下的入库完结  
        #必须全部出库完  且之前的出库单都已经入库完毕了  才可以进行此调用

        #查询出库状态
        $allo_out_status_arr = M('wms_allo_new_status', 'tb_')->where(['allo_id'=>$allo_id])->find();
        $allo_out_status = $allo_out_status_arr['allo_out_status'];
        
        if($allo_out_status != 1){
            Logs(['reason_difference' => $reason_difference, 'res' => '出库id'. $allo_id.'未完全出库'], __FUNCTION__, __CLASS__);
            return $out_stock_re;
        }
        
        if ($allo_out_status_arr['allo_in_status'] == 1) {
            Logs(['reason_difference' => $reason_difference, 'res' => '出库id' . $allo_id . '已经出库完结'], __FUNCTION__, __CLASS__);
            throw new Exception(L('该出库记录已经入库标记完结'));
        }
        #查询是否之前的出库单都已经入库完毕了
       
        $out_stock_all = M('wms_allo_new_out_stocks', 'tb_')->where(['allo_id' => $allo_id])->count();
        $out_stock_has_in_stock = M('wms_allo_new_out_stocks', 'tb_')->where(['allo_id' => $allo_id, 'logistics_state'=>8, 'stock_in_state'=>1])->count();
       
      
        #存在未入库完毕的出库记录
        if($out_stock_all != $out_stock_has_in_stock ){
            Logs(['reason_difference' => $reason_difference, 'res' => '出库id' . $allo_id . '未完全入库'], __FUNCTION__, __CLASS__);
            return $out_stock_re;
        }
        $inbound_tag_completion = $this->repository->inboundTagCompletion($allo_id, $reason_difference);
        if (false === $inbound_tag_completion) {
            throw new Exception(L('标记入库失败'));
        }
        $api_request_data = $this->repository->getAlloNo($allo_id);
        //调拨入库完结api
        $res = (new WmsModel())->transferEndLibraryNew($api_request_data);
        Logs(['front_request' => $reason_difference, 'request' => $api_request_data, 'response' => $res], __FUNCTION__, __CLASS__);
        if (2000 != $res['code']) {
            @SentinelModel::addAbnormal($allo_id, '标记入库完成API异常', $res);
            throw new Exception(L('标记入库完成API异常') . ':' . $res['msg']);
        }
        return $inbound_tag_completion;
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function getAlloSaleLeader($allo_id)
    {
        return $this->repository->getAlloSaleLeader($allo_id);
    }

    /**
     * @param $allo_id
     *
     * @return array
     */
    public function assemblyWeChatData($allo_id)
    {
        $allo_we_chat_info = array_values($this->repository->getAlloWeChatInfo($allo_id))[0];
        $allo_we_chat_info['warehouse_from_to'] = $allo_we_chat_info['allo_out_warehouse_val']
            . '--->'
            . $allo_we_chat_info['allo_in_warehouse_val'];
        return ['allo' => $allo_we_chat_info];
    }

    /**
     * @return array
     */
    public function getTransportCompany()
    {
        $transport_companys = $this->repository->getTransportCompany();
        return (array)$transport_companys;
    }

    /**
     * @param $allo_id
     * @param $log_msg
     *
     * @return mixed
     */
    public function addLog($allo_id, $log_msg)
    {
        return $this->repository->saveLog($allo_id, $log_msg);
    }

    /**
     * @param $allo_id
     *
     * @return array
     */
    public function acquisitionFeeDetails($allo_id)
    {
        $fee_dbs = (new AllocationExtendNewTransformer())->transformAlloInfo(
            $this->getFeeDetailDB($allo_id)
        );
        return [
            'service_fee' => $this->getServiceFee($fee_dbs),
            'logistics_costs' => $this->getLogisticsCosts($fee_dbs),
            'tariff_sum' => $this->getTariffSum($fee_dbs),
        ];
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function getServiceFee($data)
    {
        foreach ($this->service_fee_maps as $fee_key => $fee_map) {
            foreach ($fee_map as $key => $value) {
                $temp_fee = $data[$fee_key];
                $switch_key = $fee_key . '-' . $key;
                switch ($switch_key) {
                    case 'in_stocks-shelf_cost_cny':
                    case 'in_stocks-value_added_service_fee_cny':
                    case 'out_stocks-insurance_fee_cny':
                        foreach ($temp_fee as $temp_fee_value) {
                            $temp =
                                [
                                    'fee_name' => $value,
                                    'fee_amount' => (float)$temp_fee_value['logistics_information'][$key],
                                ];
                            $temps[] = $temp;
                        }
                        break;
                    case 'work-operating_expenses_cny':
                    case 'work-value_added_service_fee_cny':
                        $temp =
                            [
                                'fee_name' => $value,
                                'fee_amount' => (float)$temp_fee[$key],
                            ];
                        $temps[] = $temp;
                }
            }
        }
        return $temps;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function getLogisticsCosts($data)
    {
        $temps = [];
        foreach ($data['out_stocks'] as $datum) {
            foreach ($this->logistics_fee_maps as $key => $value) {
                $temp =
                    [
                        'fee_name' => $value,
                        'fee_amount' => (float)$datum['logistics_information'][$key],
                    ];
                $temps[] = $temp;
            }
        }
        return $temps;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function getTariffSum($data)
    {
        $temps = [];
        foreach ($data['in_stocks'] as $datum) {
            $temp =
                [
                    'fee_name' => '关税',
                    'fee_amount' => (float)$datum['logistics_information']['tariff_cny'],
                ];
            $temps[] = $temp;
        }
        return $temps;
    }

    /**
     * @param $allo_id
     *
     * @return array
     */
    public function sendWorkWeChatMsg($allo_id)
    {
        $res_db = $this->repository->sendWorkWeChatMsg($allo_id);
        $warehouse_by_array = explode(',', $res_db['ETC']);
        $WechatMsg = new WechatMsg();
        $init_language = LanguageModel::getCurrent();
        $work_place = TbHrCardModel::getCardWorkPalce($warehouse_by_array);
        foreach ($warehouse_by_array as $value) {
            LanguageModel::setCurrent(ReviewMsg::getPathToLang($work_place[$value]));
            if ('stage' == $_ENV["NOW_STATUS"]) {
                $value = 'yangsu@gshopper.com';
            }
            $WechatMsg->sendText($value, L("调拨单") . $res_db['allo_no'] . L("已经通过审核，可以向仓库发起作业要求了。"));
            LanguageModel::setCurrent($init_language);
        }
        return $warehouse_by_array;
    }

    public function sendReviewWeChatMsg($data_user, $allo_id, $state)
    {
        $WechatMsg = new WechatMsg();
        $allo_no = $this->repository->getAlloInfo($allo_id)['allo_no'];
        $user = $data_user ? $data_user : DataModel::userNamePinyin();
        switch ($state) {
            case 0:
                $msg_content = L("您已拒绝调拨单号:") . $allo_no . L("的调拨申请。");
                break;
            case  1:
                $msg_content = L("您已通过调拨单号:") . $allo_no . L("的调拨申请。");
                break;
        }
        $WechatMsg->sendText($user . "@gshopper.com", $msg_content);
    }

    /**
     * @param $allo_id
     * @param $is_used_amount
     *
     * @return array
     */
    private function getFeeDetailDB($allo_id, $is_used_amount = null)
    {
        return [
            'work' => $this->repository->getWork($allo_id, $is_used_amount),
            'out_stocks' => $this->repository->getOutStock($allo_id, $is_used_amount),
            'in_stocks' => $this->repository->getInStock($allo_id),
        ];
    }

    /**
     * 保存物流节点数据
     * @param int $allo_id
     * @param int $out_stock_id
     * @param array $data
     * @author Redbo He
     * @date 2021/1/12 15:47
     */
    public  function saveOutStockLogisticsNode($allo_id, $out_stock_id, $data)
    {
        $last_data  = M('wms_allo_new_out_stocks_node', 'tb_')->where(['out_stock_id' => $out_stock_id, 'allo_id' => $allo_id])->order('type')->select();
        $last_data = array_column($last_data, NULL , 'type');
        $insert_data = $update_data = [];
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $is_optimize_team     = 0;
        if (in_array($allo['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams)) {
            $is_optimize_team = 1;
        }
        $date     = new Date();
        foreach ($data as $type =>  $item)
        {
            $last_data_item = isset($last_data[$type]) ? $last_data[$type] : [];
            if($last_data_item)
            {
                $update_data[] = [
                    'id' => $last_data_item['id'],
                    'node_plan' => $item['node_plan'],
                    'node_operate' =>  (isset($item['node_operate'])  && $item['node_operate']) ? $item['node_operate'] : NULL,
                    'scheduled_date' => (isset($item['scheduled_date'])  && $item['scheduled_date']) ? $item['scheduled_date'] : NULL,
                    'type'    => $type,
                    'updated_at' => $date->format()
                ];
                if(isset($item['node_operate']) && $item['node_operate']) {
                    $temp['scheduled_date'] = $item['node_operate'];
                }
            }
            else
            {
                $data_type = $is_optimize_team ? 1 : 0;
                $insert_data[] = [
                    'out_stock_id' => $out_stock_id,
                    'allo_id' => $allo_id,
                    'node_plan' => $item['node_plan'],
                    'scheduled_date' => (isset($item['scheduled_date'])  && $item['scheduled_date']) ? $item['scheduled_date'] : NULL,
                    'node_operate'   =>  (isset($item['node_operate'])  && $item['node_operate']) ? $item['node_operate'] : NULL,
                    'type'           => $type,
                    'data_type'      => $data_type,
                    'created_at'     => $date->format(),
                    'updated_at'     => $date->format()

                ];
            }
        }

        $model = M();
        if($update_data) {
            $update_sql = BatchUpdate::getBatchUpdateSql("tb_wms_allo_new_out_stocks_node", $update_data, 'id');
            $res = $model->execute($update_sql);
        }
        if($insert_data) {
            $res = $model->table('tb_wms_allo_new_out_stocks_node')->addAll($insert_data);
        }
        if($res === false) {
            return false;
        }

        #更新物流轨迹
        $tmpWhere = [
            'allo_id' => $allo_id,
            'out_stock_id' => $out_stock_id,
            'node_operate' =>['gt',0]
        ];

        $type = $model->table('tb_wms_allo_new_out_stocks_node')->where($tmpWhere)->order('type desc')->getField('type');
        return $model->table('tb_wms_allo_new_out_stocks')->where(['id' => $out_stock_id])->save(['logistics_state' => $type]);
    }

    /**
     * 获取数据运输节点初始化数据
     * @param $allo_id
     * @param $out_stock_id
     * @author Redbo He
     * @date 2021/1/12 19:28
     */
    public function getOutStockLogisticsNodeInitDate($allo_id, $out_stock_id)
    {
        # 获取调拨审核通过时间
        # tb_wms_allo_attribution review_at
        $allo_attribution_model = M("wms_allo_attribution","tb_");
        $allo_attribution  = $allo_attribution_model->where(['allo_id' => ['eq', $allo_id]])->find();
        if($allo_attribution) {
            $review_date = $allo_attribution['review_at'];
            $review_date = $review_date ? date("Y-m-d", strtotime($review_date)) : NULL;
        }

        if (is_null($review_date)) {
            $wms_allo_new_logs_model = M("wms_allo_new_logs",'tb_');
            $log = $wms_allo_new_logs_model->where([
                'allo_id' => ['eq', $allo_id,],
                'operation_detail' => ['eq',"审核通过"]
            ])->find();
            $review_date = null;
            if($log)
            {
                $review_date =   date("Y-m-d", strtotime($log['created_at']));
            }
        }


        # 出库记录的出库时间
        # tb_wms_allo_new_out_stocks created_at
        $wms_allo_new_out_stock_model = M("wms_allo_new_out_stocks","tb_");
        $allo_out_stock = $wms_allo_new_out_stock_model->where(['allo_id' => ['eq', $allo_id]])->find();
        $out_stock_datetime = $allo_out_stock ?  $allo_out_stock['created_at'] : NULL;
        $out_stock_date = $out_stock_datetime ? date("Y-m-d", strtotime($out_stock_datetime)) : null;

        ## 组装返回数据
        return [
            'review_date' =>$review_date,
            'out_stock_date' => $out_stock_date
        ];

    }

    /**
     * 获取最新的入库记录数据
     * @param $out_stock_id
     * @author Redbo He
     * @date 2021/1/15 10:47
     */
    public function getLastInStockRecord($out_stock_id)
    {
        $wms_allo_new_out_stock_model = M("wms_allo_new_out_stocks","tb_");
        # ->alias("a")
        $sub_query = $wms_allo_new_out_stock_model
                            ->field("b.allo_id, b.out_stock_id, b.created_at")
                            ->alias("a")
                            ->join("inner join tb_wms_allo_new_in_stocks as b on a.id = b.out_stock_id ")
                            ->where([
                                'a.id' => ['eq', $out_stock_id],
                                'a.stock_in_state' => ['eq', 1], # 已完结
                            ])
                            ->order("b.id desc")
                            ->limit(100)
                            ->select(false)
        ;
        $result =  M()->table($sub_query . " as tmp")->field("*")
            ->group("tmp.out_stock_id")
            ->find()
        ;
        return $result;

    }


    //时效列表数据生成
    public function makeInEffectiveData(){
        $this->repository->makeInEffective();
    }


    //获取时效数据
    public function getEffectiveList($params){
        $list = $this->repository->getEffectiveList($params);
        return $list;
    }

    /**
     * @param $allo_id
     * @author Redbo He
     * @date 2021/3/4 15:10
     */
    public function deleteAlloAttribution($allo_id)
    {
        $wms_allo = D("TbWmsAllo");
        $allo = $wms_allo = $wms_allo->find($allo_id);
        $wms_allo_attribution_model = M("wms_allo_attribution","tb_");
        $allo_attribution = $wms_allo_attribution_model->where([
                'allo_id' => ['eq', $allo_id]
        ])->find();

        $date     = new Date();
        if($allo && $allo_attribution) {
            $update_data = [
                'updated_by' => DataModel::userNamePinyin(),
                'deleted_by' =>  DataModel::userNamePinyin(),
                'updated_at' => $date->format(),
                'deleted_at' => $date->format(),
            ];
            # 更新调拨库存变更信息
            $wms_allo_attribution_model->where([
                'allo_id' => ['eq', $allo_id]
            ])->save($update_data);
            $wms_allo_attribution_sku_model = M("wms_allo_attribution_sku","tb_");
            $wms_allo_attribution_sku_model->where([
                'allo_attribution_id' => $allo_attribution['id']
            ])->save($update_data);
            return true;
        }
        return false;
    }

    //获取本次调拨所有商品调拨数量总和
    public function getAllGoodsAlloNum ($allo_id)
    {
        $allo_guds = M('wms_allo_child', 'tb_')
            ->field(['SUM(demand_allo_num) as sum_allo_num'])
            ->where(['allo_id' => $allo_id])
            ->select();
        $sum_allo_num = $allo_guds[0]['sum_allo_num'];//所有需要调拨的商品数量总和
        return $sum_allo_num ? : 0;
    }

    //判断本次调拨所有商品是否全部已出库
    public function isAllGoodsStock($allo_id, $sum_allo_num)
    {
        $out_stock_guds = M('wms_allo_new_out_stock_guds', 'tb_')
            ->field(['SUM(this_out_authentic_products + this_out_defective_products) as sum_stock_num'])
            ->where(['allo_id' => $allo_id])
            ->select();

        $sum_stock_num = $out_stock_guds[0]['sum_stock_num'];//所有已出库商品数量总和
        if ($sum_allo_num == $sum_stock_num) {
            //全部已出库
            return true;
        }
        return false;
    }

    //判断本次调拨所有商品是否全部已入库
    public function isAllGoodsInWarehouse($allo_id, $sum_allo_num)
    {
        $in_warehouse_guds = M('wms_allo_new_in_stock_guds', 'tb_')
            ->field(['SUM(this_in_authentic_products + this_in_defective_products) as sum_in_warehouse_num'])
            ->where(['allo_id' => $allo_id])
            ->select();

        $sum_in_warehouse_num = $in_warehouse_guds[0]['sum_in_warehouse_num'];//所有已入库商品数量总和
        if ($sum_allo_num == $sum_in_warehouse_num) {
            //全部已入库
            return true;
        }
        return false;
    }

    public function sendWxMsg($allocate_data, $in_warehouse_type_name)
    {
        $wid = $this->repository->getWidByIds($allocate_data['create_user']);
        $in_warehouse_time = dateTime();
        $content = "><font color=warning >**调拨入库通知**</font>
>调拨单号：<font color=comment >{$allocate_data['allo_no']}</font>
>入库时间：<font color=comment >{$in_warehouse_time}</font>
>入库类型：<font color=comment >{$in_warehouse_type_name}</font>
>如需查看详情，请点击[查看详情](detail_url)";
        $tab_data = [
            'url' => urldecode('/index.php?' . http_build_query(['m' => 'AllocationExtendNew', 'a' => 'transportation', 'id' => $allocate_data['id']])),
            'name' => '调拨单详情'
        ];
        $replaceData = [
            'title' => '调拨单详情',
            'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
        ];
        $data = $this->replace_template_var($content, $replaceData);
        $res = ApiModel::WorkWxSendMarkdownMessage($wid, $data);
        logs(['wid' => $wid, 'content' => $content, 'res' => $res], __function__, 'allocateSendMsg');
    }

    protected function replace_template_var($template, $data)
    {
        if ($data) {
            foreach ($data as $k => $v) {
                $template = str_replace($k, $v, $template);
            }
        }
        return $template;
    }
}