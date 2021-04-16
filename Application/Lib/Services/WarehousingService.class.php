<?php
/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 11:31
 */

/**
 * Class WarehousingService
 */
class WarehousingService extends Service
{
    /**
     * @var WarehousingRepository
     */
    protected $repository;

    /**
     * @var array
     */
    private $outbound_status_map = [
        '待出库',
        '已出库',
    ];

    private $product_information_arr = [
        'N002440100' => 'return_number_normal',
        'N002440400' => 'return_number_broken'
    ];

    /**
     *
     */
    const OUT_STOCK         = 'N000950800';
    const OUT_STOCK_RETURN  = 'N000950900';

    /**
     *
     */
    const PURCHASE_RETURN = 'N002350600';

    /**
     * WarehousingService constructor.
     */
    public function __construct()
    {
        $this->repository = new WarehousingRepository();
    }

    /**
     * @param array $data
     * @return array
     */
    public function returnOutList(array $data)
    {
        $db_res = [];
        list($wheres, $limits) = $this->joinReturnOutListSearchWhere($data);
        if ($data['search']['sku_or_barcode']) { // 精确查询sku_id或条形码
            $complex['ps.sku_id']    = $data['search']['sku_or_barcode'];
            $complex['ps.upc_id']    = $data['search']['sku_or_barcode'];
            $complex['_string'] = "FIND_IN_SET('{$data['search']['sku_or_barcode']}',ps.upc_more)";
            $complex['_logic']      = 'or';
            $wheres['_complex']      = $complex;
        }
        
        list($db_res['data'], $db_res['page']) = $this->repository->getReturnOutList($wheres, $limits);
        $db_res['data'] = CodeModel::autoCodeTwoVal($db_res['data'],
            ['status_cd', 'warehouse_cd', 'purchase_team_cd', 'our_company_cd',]
        );
        $db_res['data'] = $this->returnReceiveAddressMap($db_res['data']);
        return $db_res;
    }

    /**
     * @param $data
     * @return array
     */
    private function joinReturnOutListSearchWhere($data)
    {
        $search_map = [
            "status_cd" => "tb_pur_return.status_cd",
            "outbound_status" => "tb_pur_return.outbound_status",
            "return_no" => "tb_pur_return.return_no",
            "warehouse_cd_arr" => "tb_pur_return.warehouse_cd",
            "created_by" => "tb_pur_return.created_by",
            "supplier" => "tb_crm_sp_supplier.SP_NAME",
            "purchase_team_cd_arr" => "tb_pur_return.purchase_team_cd",
            "our_company_cd_arr" => "tb_pur_return.our_company_cd",
            "prchasing_return_by" => "tb_con_division_warehouse.prchasing_return_by"
        ];
        $search_accurate_arr = [
            "status_cd",
            "created_by",
            "outbound_status",
        ];
        return WhereModel::joinSearchTemp($data, $search_map, [], $search_accurate_arr);
    }

    /**
     * @param $id
     * @return \Illuminate\Support\Collection|null|class
     */
    public function checkHasTbPurReturnId($id)
    {
        return $this->repository->getTbPurReturnId($id);
    }

    /**
     * @param $id
     * @return array
     */
    public function returnDeliverDetails($id)
    {
        $db_res = $this->repository->getReturnDeliverDetails($id);
        $return_data = CodeModel::autoCodeOneVal($db_res,
            [
                'status_cd',
                'warehouse_cd',
                'purchase_team_cd',
                'our_company_cd',
                'estimate_logistics_cost_currency_cd',
                'estimate_other_cost_currency_cd'
            ]
        );
        $return_data['prchasing_return_by'] = $this->repository->getPrchasingReturnBy($return_data['warehouse_cd']);
        $return_data['outbound_status_val'] = $this->outbound_status_map[$return_data['outbound_status']];
        $return_data = $this->mapReturnDeliverDetails($return_data);
        return $return_data;
    }

    /**
     * @param $data
     * @return array
     */
    private function mapReturnDeliverDetails($data)
    {
        $temp_data = [
            'basic_information' => $data,
            'product_information' => $data['tb_pur_return_good'],
            'relevance_id' => $data['tb_pur_return_order']['relevance_id'],
            'delivery_information' => [
                "receiver" => $data['receiver'],
                "receiver_contact_number" => $data['receiver_contact_number'],
                "receive_address_country" => $data['receive_address_country'],
                "receive_address_province" => $data['receive_address_province'],
                "receive_address_area" => $data['receive_address_area'],
                "receive_address_detail" => $data['receive_address_detail']
            ],
            'logistics_information' => [
                "logistics_number" => $data['logistics_number'],
                "estimate_arrive_date" => $data['estimate_arrive_date'],
                "estimate_logistics_cost_currency_cd_val" => $data['estimate_logistics_cost_currency_cd_val'],
                "estimate_logistics_cost" => $data['estimate_logistics_cost'],
                "estimate_other_cost_currency_cd_val" => $data['estimate_other_cost_currency_cd_val'],
                "estimate_other_cost" => $data['estimate_other_cost']
            ],
            'log_information' => [
                "created_by" => $data['created_by'],
                "create_time" => $data['create_time'],
                "out_of_stock_user" => $data['out_of_stock_user'],
                "out_of_stock_time" => $data['out_of_stock_time']
            ],

        ];
        $temp_data['delivery_information'] = $this->returnReceiveAddressMap([$temp_data['delivery_information']])[0];
        $temp_data['product_information'] = $this->mapProductInformation($temp_data['product_information']);
        $temp_data['basic_information']['supplier'] = $data['tb_crm_sp_supplier']['SP_NAME'];
        unset($temp_data['basic_information']['tb_pur_return_good'], $temp_data['basic_information']['tb_crm_sp_supplier']);
        return $temp_data;
    }

    /**
     * @param $data
     * @return array
     */
    private function mapProductInformation($data)
    {
        $data = array_map(function ($value) {
            $value['sku_code'] = $value['tb_pur_goods_information']['sku_information'];
            unset($value['tb_pur_goods_information']);
            return $value;
        }, $data);
        $temp_data = [];
        $product_information_arr = $this->product_information_arr;
        $product_information_arr_val = array_values($product_information_arr);
        foreach ($data as $datum) {
            $key_num = $product_information_arr[$datum['vir_type_cd']];
            if (!isset($temp_data[$datum['sku_code']])) {
                $temp_data[$datum['sku_code']] = $datum;
                foreach ($product_information_arr_val as $value) {
                    $temp_data[$datum['sku_code']][$value] = 0;
                }
            }
            $temp_data[$datum['sku_code']][$key_num] += (int)$datum['return_number'];
        }
        $temp_data = SkuModel::getInfo($temp_data,
            'sku_code',
            ['spu_name', 'attributes', 'image_url', 'product_sku'],
            ['spu_name' => 'product_name', 'attributes' => 'product_attribute', 'image_url' => 'product_picture',]);
        $temp_data = array_map(function ($value) {
            $value['bar_code'] = $value['product_sku']['upc_id'];
            if($value['product_sku']['upc_more']) {
                $upc_more_arr = explode(',', $value['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $value['product_sku']['upc_id']);
                $value['bar_code'] = implode(",\r\n", $upc_more_arr);
            }
            unset($value['product_sku']);
            return $value;
        }, $temp_data);
        return array_values($temp_data);
    }

    /**
     * @param $data
     * @param $Model
     * @return mixed
     * @throws Exception
     */
    public function returnDeliveryConfirmation($data, $Model)
    {
        $save_arr = $this->joinConfirmationSaveArr($data);
        $update_return_delivery_confirmation = $this->repository
            ->updateReturnDeliveryConfirmation($data['id'], $save_arr, $Model);
        if (false !== $update_return_delivery_confirmation) {
            $this->getDeductionByReturnID($data); // 必须放在调取java接口前面，否则一旦生成抵扣有问题会回滚，但是java那边无法回滚数据
            $api_res = $this->out_stock($data['return_no']);
            $one_order = $api_res['data'][0];
            Logs([$data, $api_res], __CLASS__, __FUNCTION__);
            if (!$api_res) {
                @SentinelModel::addAbnormal('退货出库', '调用出库接口异常', $api_res, 'oms_notice');
                throw new  Exception(L('调用出库接口无响应'));
            }
            if (2000 != $api_res['code'] || 2000 != $one_order['code']) {
                throw new  Exception(L('调用接口出库返回错误') . $one_order['msg']);
            }
        }
        return $update_return_delivery_confirmation;
    }

    public function getDeductionByReturnID($data)
    {
        //N002440100:return_number_normal N002440400:return_number_broken
        //2019-08-26 逻辑调整采购退款出库只有残次品则不生成抵扣记录，区分退货出库（正品）与（残次品）
        $model = new Model();
        $return_goods = $model->table('tb_pur_return_goods')->where(['return_id' => $data['id'], 'vir_type_cd' => 'N002440100'])->select();
        if (!$return_goods) {
            return true;
        }
        // 生成抵扣记录
        $addDataInfo = [];
        $addDataInfo['money_id'] = $data['id'];
        $addDataInfo['relevance_id'] = $data['relevance_id'];
        $addDataInfo['clause_type'] = '5';
        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $return_no = M('return', 'tb_pur_')->where(['id' => $data['id']])->getField('return_no');
        $pur_res =D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870016', $data['relevance_id'], $return_no);
        if (!$pur_res) {
            throw new  Exception(L('生成抵扣记录失败'));
        }
        return $pur_res;
    }

    /**
     * @param $data
     * @return array
     */
    private function joinConfirmationSaveArr($data)
    {
        $save_arr = [
            'id' => $data['id'],
            "logistics_number" => $data['logistics_information']['logistics_number'],
            "estimate_arrive_date" => $data['logistics_information']['estimate_arrive_date'],
            "estimate_logistics_cost_currency_cd" => $data['logistics_information']['estimate_logistics_cost_currency_cd'],
            "estimate_logistics_cost" => $data['logistics_information']['estimate_logistics_cost'],
            "estimate_other_cost_currency_cd" => $data['logistics_information']['estimate_other_cost_currency_cd'],
            "estimate_other_cost" => $data['logistics_information']['estimate_other_cost'],
        ];
        return $save_arr;
    }

    /**
     * @param $data
     * @return array
     */
    private function returnReceiveAddressMap($data)
    {
        $area_nos = array_merge(
            array_column($data, 'receive_address_country'),
            array_column($data, 'receive_address_province'),
            array_column($data, 'receive_address_area')
        );
        $area_arr_col = array_column($this->repository->getTbMsUserArea($area_nos), 'zh_name', 'area_no');
        $outbound_status_map = $this->outbound_status_map;
        $data = array_map(function ($value) use ($area_arr_col, $outbound_status_map) {
            $value['receive_address_country_val'] = $area_arr_col[$value['receive_address_country']];
            $value['receive_address_province_val'] = $area_arr_col[$value['receive_address_province']];
            $value['receive_address_area_val'] = $area_arr_col[$value['receive_address_area']];
            $value['outbound_status_val'] = $outbound_status_map[$value['outbound_status']];
            return $value;
        }, $data);
        return $data;
    }

    /**
     * @param $order_id
     * @return mixed
     */
    private function out_stock($order_id)
    {
        $request_data[] = [
            'operatorId' => DataModel::userId(),
            'orderId' => $order_id,
            'relationType' => self::PURCHASE_RETURN,
            'billType' => self::OUT_STOCK_RETURN
        ];
        return (new WmsModel())->b2cOutStorage($request_data);
    }
}