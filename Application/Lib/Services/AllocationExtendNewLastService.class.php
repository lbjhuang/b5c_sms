<?php

/**
 * User: yangsu
 * Date: 19/06/17
 * Time: 11:31
 */


/**
 * Class AllocationExtendNewService
 */
class AllocationExtendNewLastService extends Service
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

    /**
     * @var array
     */
    private $logistics_fee_maps = [
        'outbound_cost_cny' => '出库费用',
        'head_logistics_fee_cny' => '头程费用',
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
        $this->repository = new AllocationExtendNewLastRepository($external_model);
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
                'allo_out_team', 'allo_in_team']
        );
        if (empty($info)) {
            throw new Exception(L('查询异常，请检查调拨单 ID'));
        }
        $goods = CodeModel::autoCodeTwoVal(
            $this->repository->getAlloGoods($allo_id),
            ['state', 'tax_free_sales_unit_price_currency_cd', 'allo_out_warehouse_cd', 'allo_in_warehouse_cd',
                'planned_transportation_channel_cd',]
        );
        list($info['total_value_goods'], $batch_orders) = $this->getTotalValueGoods($info['allo_no']);
        $info['total_sales'] = (float)$this->getTotalSales($goods);
        $goods = SkuModel::getInfo($goods, 'sku_id',
            ['spu_name', 'attributes', 'image_url', 'product_sku',]
        );
        $allo_batch = $this->repository->transferProducts($allo_id);
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
        $allo_no = $this->repository->getAlloInfo($allo_id)['allo_no'];
        foreach ($occupy_db as $value) {
            $param[$allo_no . $value['vir_type'] . $value['SKU_ID']] = [
                "orderId" => $allo_no,
                "virType" => $value['vir_type'],
                "skuId" => $value['SKU_ID'],
            ];
        }
        Logs($occupy_db, __FUNCTION__, __CLASS__);
        if (empty($param)) {
            throw new Exception('无关联占用商品');
        }
        $param = array_values($param);
        $res_api = ApiModel::freeUpBatch($param);
        if (2000 != $res_api['code']) {
            throw new Exception(L('API 释放占用失败:') . $res_api['msg']);
        }
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
        if (1 != $this->repository->updateReviewAllo($allo_id, $type_cd, $updated_by)) {
            throw new Exception(L('处理失败,请检查调拨状态'));
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
    public function submitInStock($allo_id, $data, $Model)
    {
        if ('N001970603' !== $this->repository->getAlloStatus($allo_id) ||
            0 != $this->repository->getStockStatus($allo_id, 'in')) {
            throw new Exception(L('新调拨入库状态错误'));
        }
        $this->repository->external_model = $Model;
        $in_stocks_id = $this->repository->submitInStockInfo($allo_id, $data['logistics_information']);
        if (!$in_stocks_id) {
            throw new Exception(L('新调拨入库失败'));
        };
        if (1 > $this->repository->submitInStockGoods($allo_id, $in_stocks_id, $data['goods'])) {
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
                    //正品和残次分开判断
                    if ($temp_value['sum_authentic_products'] > $temp_value['sum_occupy_num']) {
                        throw new Exception("SKU{$temp_value['SKU_ID']}，正品累计出库数量大于当前需求值");
                    }
                    if (0 < $temp_value['sum_authentic_products'] && empty($res_sum_type['N002440100'])) {
                        throw new Exception("SKU{$temp_value['SKU_ID']}，正品累计出库数量大于当前需求值");
                    }
                    //正品和残次分开判断
                    if ($temp_value['sum_defective_products'] > $temp_value['sum_occupy_num']) {
                        throw new Exception("SKU{$temp_value['SKU_ID']}，残次品累计出库数量大于当前需求值");
                    }
                    if (0 < $temp_value['sum_defective_products'] && empty($res_sum_type['N002440400'])) {
                        throw new Exception("SKU{$temp_value['SKU_ID']}，残次品累计出库数量大于当前需求值");
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
        $this->releaseoCcupancy($allo_id);
        return $outbound_tag_completion;
    }

    /**
     * @param $allo_id
     * @param $reason_difference
     *
     * @return mixed
     * @throws Exception
     */
    public function inboundTagCompletion($allo_id, $reason_difference)
    {
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

}