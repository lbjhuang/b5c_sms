<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/4/28
 * Time: 15:10
 */

class OutStorageModel
{
    const OUTGOING_STORAGE = 'N001820900'; // 已出库

    private $esClient;
    public $total;

    public $excelName;

    public function __construct()
    {
        $this->esClient = new ESClientModel();
    }


    public function checkData($params)
    {
        $esModel = new EsSearchModel();
        $params ['pageIndex'] - 1 < 0 ? $pageIndex = 0 : $pageIndex = $params ['pageIndex'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $esModel
            ->sort([$params ['sort'] => 'desc'])
            ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
            ->setDefaultNotNull(['not', [$params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
            ->setMissing(['and', ['childOrderId']])
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->where(['tbMsLgtTrck.lgtType' => ['and', $params ['loginStatus']]])
            ->where(['store.deliveryStatus' => ['and', $params ['deliveryStatus']]])
            ->where(['storeId' => ['and', $params ['platCd']]])
            //->where(['orderNo' => ['and', $params ['orderNo']]])
            ->where(['platCd' => ['and', $params ['platForm']]])
            ->where(['ordPackage.trackingNumber' => ['and', $params ['trackingNumber']]])
            ->where(['msOrd.wholeStatusCd' => ['and', self::OUTGOING_STORAGE]])
            ->where(['warehouse' => ['and', $params ['warehouseCode']]])
            ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
            ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
            ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
            ->where(['freightType' => ['and', $params ['freightType']]])
            ->where(['orderId' => ['and', $params ['orderId']]])
            ->where(['addressUserCountryId' => ['and', $params ['country']]])
            ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
            ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left']) ? (string)strtotime($params ['search_time_left']) . '000' : '', 'lte' => strtotime($params ['search_time_right']) ? (string)strtotime($params ['search_time_right']) . '000' : '']]]);
        //->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
        if ($params ['search_condition'] == 'orderId' or $params ['search_condition'] == 'orderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } elseif ($params ['search_condition'] == 'b5cOrderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } else {
            $search_array = ['userEmail'];
            if (in_array($params ['search_condition'], $search_array)) {
                $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        }

        $esModel->page(0, 0);
        $q = $esModel->getQuery();
        $response = $this->esClient->search($q);
        $total = $response ['hits']['total'];
        if ($params ['search_condition'] == 'orderId' or $params ['search_condition'] == 'orderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } elseif ($params ['search_condition'] == 'b5cOrderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } else {
            $search_array = ['userEmail'];
            if (in_array($params ['search_condition'], $search_array)) {
                $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        }
        $q = $esModel->getQuery();
        //筛选字段
        $q['body']['size'] = $total;
        $q['body']['_source'] = [
            'includes' => [
                'storeId',
                'store.platName',
                'store.storeName',
                'orderNo',
                'bwcOrderStatusNm',
                'payShipingPrice',
                'payCurrency',
                'ordPackage.trackingNumber',
                'payTotalPrice',
                'orderTime',
                'orderPayTime',
                'shippingTime',
                'addressUserName',
                'addressUserPhone',
                'receiverTel',
                'addressUserCountryIdNm',
                'addressUserProvinces',
                'addressUserCity',
                'addressUserRegion',
                'addressUserAddress1',
                'addressUserPostCode',
                'warehouseNm',
                'logisticCdNm',
                'logisticModelIdNm',
                'remarkMsg',
                'shippingMsg',
                'b5cOrderNo',
                'fileName',
                'createUser',
                "payInstalmentServiceAmount",
                "payWrapperAmount",
                "paySettlePrice",
                "paySettlePriceDollar",
                "tariff",
                "shippingTax",
                "promotionDiscountTax",
                "shippingDiscountTax",
                "giftWrapTax",
                "userEmail",
                "freightType",
                "amountFreight",
                'freightCurrency',
                'carryTariffCurrency',
                'carryTariff',
                'subsidyCurrency',
                'subsidy',
                'preFreightCurrencyNm',
                'preAmountFreight',
                'vatFeeCurrencyNm',
                'vatFee',
                'paypalFeeCurrencyNm',
                'paypalFee',
                'leagueFeeCurrencyNm',
                'leagueFee',
                'purReturnFeeCurrencyNm',
                'purReturnFee',
                'insuranceCurrency',
                'insuranceFee',
                'packingNo',
                'opOrderGuds.b5cSkuId',
                'opOrderGuds.orderId',
                'opOrderGuds.skuId',
                'opOrderGuds.itemPrice',
                'opOrderGuds.itemCount',
                'opOrderGuds.payVoucherAmount',
                'opOrderGuds.costUsdPrice',
                'opOrderGuds.skuPurchasingCompany',
                'tbOpOrderExtend.saleAfterFeeCny'
            ]
        ];

        $response = $this->esClient->search($q);
        $total = $response ['hits']['total'];
        $query = json_encode($q);

        return array($total, $query);

    }

    /**
     * 出库数据获取
     *
     * @param array $params 页面参数
     * @param bool $isPage 是否是分页
     * @param bool $isExportExcel 是否是数据导出
     */
    public function data($params, $isPage = true, $isExportExcel = false)
    {
        $esModel = new EsSearchModel();
        $params ['pageIndex'] - 1 < 0 ? $pageIndex = 0 : $pageIndex = $params ['pageIndex'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        if ('msOrd.sTime4.keyword' == $params ['search_time_type']) {
//            $params ['search_time_type'] = 'msOrd.sendoutTime.keyword';
        }
        if ('msOrd.sendoutTime.keyword' == $params ['search_time_type']) {
            //由于es改了索引字段类型，sendoutTime由text改成了long，不支持用keyword查询
            $params ['search_time_type'] = 'msOrd.sendoutTime';
        }
        if (!empty($params ['logistics_abnormal_status'])) {
            array_push($params ['logistics_abnormal_status'], 3);
        }
        $esModel
            ->sort([$params ['sort'] => 'desc'])
            ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
            ->setDefaultNotNull(['not', [$params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
            ->setMissing(['and', ['childOrderId']])
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->where(['tbMsLgtTrck.lgtType' => ['and', $params ['loginStatus']]])
            ->where(['store.deliveryStatus' => ['and', $params ['deliveryStatus']]])
            ->where(['storeId' => ['and', $params ['platCd']]])
            //->where(['orderNo' => ['and', $params ['orderNo']]])
            ->where(['platCd' => ['and', $params ['platForm']]])
            ->where(['ordPackage.trackingNumber' => ['and', $params ['trackingNumber']]])
            ->where(['msOrd.wholeStatusCd' => ['and', self::OUTGOING_STORAGE]])
            ->where(['warehouse' => ['and', $params ['warehouseCode']]])
            ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
            ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
            ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
            ->where(['freightType' => ['and', $params ['freightType']]])
            ->where(['orderId' => ['and', $params ['orderId']]])
            ->where(['addressUserCountryId' => ['and', $params ['country']]])
            ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
            ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left']) ? (string)strtotime($params ['search_time_left']) . '000' : '', 'lte' => strtotime($params ['search_time_right']) ? (string)strtotime($params ['search_time_right']) . '000' : '']]])
            ->where(['tbOpOrderExtend.logisticsAbnormalStatus' => ['and', $params ['logistics_abnormal_status']]], false);
        //->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
        if ($params ['search_condition'] == 'orderId' or $params ['search_condition'] == 'orderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } elseif ($params ['search_condition'] == 'b5cOrderNo') {
            if (is_array($params ['search_value'])) {
                $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        } else {
            $search_array = ['userEmail'];
            if (in_array($params ['search_condition'], $search_array)) {
                $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
            } else {
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        }
        if ($isPage) {
            $q = $esModel->page($pageIndex, $size)->getQuery();
            $response = $this->esClient->search($q);
            $this->total = $response ['hits']['total'];
            $pageData = $this->template($response ['hits']['hits']);
        } else {
            $esModel->page(0, 0);
            $q = $esModel->getQuery();
            $response = $this->esClient->search($q);
            $total = $response ['hits']['total'];
            if ($isExportExcel) {
                if ($params ['search_condition'] == 'orderId' or $params ['search_condition'] == 'orderNo') {
                    if (is_array($params ['search_value'])) {
                        $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
                    } else {
                        $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
                    }
                } elseif ($params ['search_condition'] == 'b5cOrderNo') {
                    if (is_array($params ['search_value'])) {
                        $esModel->where([$params ['search_condition'] => ['and', $params ['search_value']]]);
                    } else {
                        $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
                    }
                } else {
                    $search_array = ['userEmail'];
                    if (in_array($params ['search_condition'], $search_array)) {
                        $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
                    } else {
                        $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
                    }
                }

                $q = $esModel->getQuery();
                //筛选字段
                $q['body']['size'] = $total;
                $q['body']['_source'] = [
                    'includes' => [
                        'platCd',
                        'storeId',
                        'store.platName',
                        'store.storeName',
                        'orderNo',
                        'bwcOrderStatusNm',
                        'payShipingPrice',
                        'payCurrency',
                        'ordPackage.trackingNumber',
                        'payTotalPrice',
                        'orderTime',
                        'orderPayTime',
                        'shippingTime',
                        'addressUserName',
                        'addressUserPhone',
                        'receiverTel',
                        'addressUserCountryIdNm',
                        'addressUserProvinces',
                        'addressUserCity',
                        'addressUserRegion',
                        'addressUserAddress1',
                        'addressUserPostCode',
                        'warehouseNm',
                        'logisticCdNm',
                        'logisticModelIdNm',
                        'remarkMsg',
                        'shippingMsg',
                        'b5cOrderNo',
                        'fileName',
                        'createUser',
                        "payInstalmentServiceAmount",
                        "payWrapperAmount",
                        "paySettlePrice",
                        "paySettlePriceDollar",
                        "tariff",
                        "shippingTax",
                        "promotionDiscountTax",
                        "shippingDiscountTax",
                        "giftWrapTax",
                        "userEmail",
                        "freightType",
                        "amountFreight",
                        'freightCurrency',
                        'carryTariffCurrency',
                        'carryTariff',
                        'subsidyCurrency',
                        'subsidy',
                        'preFreightCurrencyNm',
                        'preAmountFreight',
                        'vatFeeCurrencyNm',
                        'vatFee',
                        'paypalFeeCurrencyNm',
                        'paypalFee',
                        'leagueFeeCurrencyNm',
                        'leagueFee',
                        'purReturnFeeCurrencyNm',
                        'purReturnFee',
                        'insuranceCurrency',
                        'insuranceFee',
                        'packingNo',
                        'opOrderGuds.b5cSkuId',
                        'opOrderGuds.orderId',
                        'opOrderGuds.skuId',
                        'opOrderGuds.itemPrice',
                        'opOrderGuds.itemCount',
                        'opOrderGuds.payVoucherAmount',
                        'opOrderGuds.costUsdPrice',
                        'opOrderGuds.skuPurchasingCompany',
                        'tbOpOrderExtend.buyerUserId',
                        'tbOpOrderExtend.logisticsAbnormalStatus',
                        'tbOpOrderExtend.saleAfterFeeCny',
                    ]
                ];
                Logs($q, __FUNCTION__, 'fm');
                $response = $this->esClient->search($q);
                $response = $response ['hits']['hits'];
                Logs($response, __FUNCTION__.'----data', 'fm');
                $pageData = $this->templateExcel($response);
                $response = null;
            }
        }

        return $pageData;
    }

    /**
     * EXCEL 数据导出模板
     */
    public function templateExcel(&$response)
    {
        $sku_arr = [];
        $store_ids = [];
        foreach (DataModel::toYield($response) as $key => $value) {
            $esData = $value ['_source'];
            $store_ids[] = $esData['storeId'];
            foreach ($esData ['opOrderGuds'] as $op => $gud) {
                $sku_arr[] = $gud['b5cSkuId'];
                $pageData [] = [
                    'platName'                   => $esData ['store']['platName'],//平台名称
                    'storeName'                  => $esData ['store']['storeName'],//店铺名称
                    'orderId'                    => $gud ['orderId'],//['name' => L('第三方订单ID'), 'field_name' => ''],
                    'orderNo'                    => $esData ['orderNo'],//['name' => L('第三方订单号'), 'field_name' => ''],
                    'bwcOrderStatusNm'           => $esData ['bwcOrderStatusNm'],//['name' => L('订单状态'), 'field_name' => ''],
                    'b5cSkuId'                   => $gud ['b5cSkuId'],//['name' => L('SKU ID'), 'field_name' => ''],
                    'skuId'                      => $gud ['skuId'],//['name' => L('第三方SKU ID'), 'field_name' => ''],
                    //'skuNm' => $gud ['spu_name'],//['name' => L('SKU名称'), 'field_name' => ''],
                    //'gudsOptValMpngNm' => $gud ['sku_opt'],//['name' => L('SKU 属性'), 'field_name' => ''],
                    'payCurrency'                => $esData ['payCurrency'],//['name' => L('币种'), 'field_name' => ''],
                    'itemPrice'                  => $gud ['itemPrice'],//['name' => L('商品单价'), 'field_name' => ''],
                    'itemCount'                  => $gud ['itemCount'],//['name' => L('商品数量'), 'field_name' => ''],
                    'payItemPrice'               => $gud ['itemPrice'] * $gud ['itemCount'],//['name' => L('商品总价'), 'field_name' => ''],
                    'payVoucherAmount'           => $gud ['payVoucherAmount'],//['name' => L('优惠金额'), 'field_name' => ''],
                    'payShipingPrice'            => $esData ['payShipingPrice'],//['name' => L('运费'), 'field_name' => ''],
                    'payTotalPrice'              => $esData ['payTotalPrice'],//['name' => L('支付总价'), 'field_name' => ''],
                    'orderTime'                  => $esData ['orderTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['orderTime'], 0, -3)) : '', //下单时间
                    'orderPayTime'               => $esData ['orderPayTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['orderPayTime'], 0, -3)) : '', //付款时间
                    'shippingTime'               => $esData ['shippingTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['shippingTime'], 0, -3)) : '', //发货时间
                    'addressUserName'            => $esData ['addressUserName'], //收货人姓名
                    'addressUserPhone'           => $esData ['addressUserPhone'],//['name' => L('收货人手机'), 'field_name' => ''],
                    'receiverTel'                => $esData ['receiverTel'],//['name' => L('收货人电话'), 'field_name' => ''],
                    'addressUserCountryIdNm'     => $esData ['addressUserCountryIdNm'],//['name' => L('国家'), 'field_name' => ''],
                    'addressUserProvinces'       => $esData ['addressUserProvinces'],//['name' => L('省'), 'field_name' => ''],
                    'addressUserCity'            => $esData ['addressUserCity'],//['name' => L('市'), 'field_name' => ''],
                    'addressUserRegion'          => OrderModel::needShowRegion($esData ['platCd']) ? $esData ['addressUserRegion'] : '', // 区县
                    'addressUserAddress1'        => strip_tags($esData ['addressUserAddress1']),//['name' => L('具体地址'), 'field_name' => ''],
                    'addressUserPostCode'        => $esData ['addressUserPostCode'],//['name' => L('邮编'), 'field_name' => ''],
                    'warehouseNm'                => $esData['warehouseNm'],//['name' => L('仓库'), 'field_name' => ''],
                    'logisticCdNm'               => $esData ['logisticCdNm'],//['name' => L('物流公司'), 'field_name' => ''],
                    'logisticModel'              => $esData ['logisticModelIdNm'],//物流方式
                    'trackingNumber'             => $esData ['ordPackage'][0]['trackingNumber'],//['name' => L('物流单号'), 'field_name' => ''],
                    'remarkMsg'                  => $esData ['remarkMsg'],//['name' => L('运营备注'), 'field_name' => '']
                    'shippingMsg'                => $esData ['shippingMsg'],//['name' => L('用户备注'), 'field_name' => '']
                    'b5cOrderNo'                 => $esData ['b5cOrderNo'],//['name' => L('erp订单号'), 'field_name' => '']
                    'source'                     => $esData ['fileName'] ? 'Excel 导入' : '自动拉单',//['name' => L('来源'), 'field_name' => '']
                    'createUser'                 => $esData ['createUser'],//['name' => L('创建人'), 'field_name' => '']
                    'costPrice'                  => $gud ['costUsdPrice'],//['name' => L('成本'), 'field_name' => '']
                    'skuPurchasingCompany'       => $gud ['skuPurchasingCompany'],//['name' => L('采购公司'), 'field_name' => '']
                    "payInstalmentServiceAmount" => $esData['payInstalmentServiceAmount'],//分期手续费
                    "payWrapperAmount"           => $esData['payWrapperAmount'],//包装费
                    "paySettlePrice"             => $esData['paySettlePrice'],//结算费
                    "paySettlePriceDollar"       => $esData['paySettlePriceDollar'],//结算费（美元）
                    "tariff"                     => $esData['tariff'],//税费
                    "shippingTax"                => $esData['shippingTax'],//运费税费-AMAZON
                    "promotionDiscountTax"       => $esData['promotionDiscountTax'],//优惠费税费-AMAZON
                    "shippingDiscountTax"        => $esData['shippingDiscountTax'],//运费折扣税费-AMAZON
                    "giftWrapTax"                => $esData['giftWrapTax'],//包装费税费-AMAZON
                    "userEmail"                  => $esData["userEmail"],//收货人邮箱
                    "estimatedFreight"           => $esData["freightType"] == 'N000179100' ? $esData["amountFreight"] : '',//估重运费
                    "trueFreight"                => $esData["freightType"] == 'N000179200' ? $esData["amountFreight"] : '',//实重运费
                    "importFreight"              => $esData["freightType"] == 'N000179300' ? $esData["amountFreight"] : '',//导入运费
                    'freight_currency'           => cdVal($esData ['freightCurrency']) ? cdVal($esData ['freightCurrency']) : ($esData ['freightCurrency'] ?: 'CNY'), // 尾程运费试算币种
                    'carry_tariff_currency'      => cdVal($esData ['carryTariffCurrency']) ? cdVal($esData ['carryTariffCurrency']) : $esData ['carryTariffCurrency'], // 尾程关税币种
                    'carry_tariff'               => $esData ['carryTariff'], // 尾程关税
                    'subsidy_currency'           => cdVal($esData ['subsidyCurrency']) ? cdVal($esData ['subsidyCurrency']) : $esData ['subsidyCurrency'], // 平台补贴币种
                    'subsidy'                    => $esData ['subsidy'], // 平台补贴
                    'preFreightCurrency'         => $esData ['preFreightCurrencyNm'] ? $esData ['preFreightCurrencyNm'] : $esData ['preFreightCurrency'], // 头程运费币种
                    'preAmountFreight'           => $esData ['preAmountFreight'], // 头程运费
                    'vatFeeCurrency'             => $esData ['vatFeeCurrencyNm'] ? $esData ['vatFeeCurrencyNm'] : $esData ['vatFeeCurrency'], // VAT币种
                    'vatFee'                     => $esData ['vatFee'], // VAT
                    'paypalFeeCurrency'          => $esData ['paypalFeeCurrencyNm'] ? $esData ['paypalFeeCurrencyNm'] : $esData ['paypalFeeCurrency'], // 支付手续费币种
                    'paypalFee'                  => $esData ['paypalFee'], // 支付手续费
                    'leagueFeeCurrency'          => $esData ['leagueFeeCurrencyNm'] ? $esData ['leagueFeeCurrencyNm'] : $esData ['leagueFeeCurrency'], // 流量活动费用币种
                    'leagueFee'                  => $esData ['leagueFee'], // 流量活动费用
                    'purReturnFeeCurrency'       => $esData ['purReturnFeeCurrencyNm'] ? $esData ['purReturnFeeCurrencyNm'] : $esData ['purReturnFeeCurrency'], // 采购返佣金币种
                    'purReturnFee'               => $esData ['purReturnFee'], // 采购返佣金
                    'insuranceCurrency'          => cdVal($esData ['insuranceCurrency']) ? cdVal($esData ['insuranceCurrency']) : $esData ['insuranceCurrency'], //保险费用币种
                    'insuranceFee'               => $esData ['insuranceFee'], // 保险费
                    'PACKING_NO'                 => $esData ['packingNo'],
                    'storeId'                    => $esData['storeId'],
                    "buyerUserId"                => $esData['tbOpOrderExtend']['buyerUserId'],
                    "logistics_abnormal_status"  => $esData['tbOpOrderExtend']['logisticsAbnormalStatus'],
                    "saleAfterFeeCny"            => $esData['tbOpOrderExtend']['saleAfterFeeCny'] ? ''.$esData['tbOpOrderExtend']['saleAfterFeeCny'] : '',
                ];
            }
            unset($response[$key]);
        }
        $pageData = SkuModel::getInfo($pageData, 'b5cSkuId', ['spu_name', 'attributes'], ['spu_name' => 'skuNm', 'attributes' => 'gudsOptValMpngNm']);
        $sku_infos = SkuModel::getSkusInfo($sku_arr, ['product_sku']);
        if ($sku_infos) {
            foreach ($pageData as &$datum) {
                $datum['sku_weight'] = $sku_infos['product_sku'][$datum['b5cSkuId']]['sku_weight'];
                $datum['sku_size'] = $sku_infos['product_sku'][$datum['b5cSkuId']]['sku_length'] . '*' . $sku_infos['product_sku'][$datum['b5cSkuId']]['sku_width'] . '*' . $sku_infos['product_sku'][$datum['b5cSkuId']]['sku_height'];
            }
        }
        Logs('$sku_infos',$sku_infos);
        unset($datum);
        $gud = 0;
        if ($store_ids) {
            $store_ids   = array_unique($store_ids);
            $model       = new Model();
            $company_map = $model->table('tb_ms_store s')
                ->join('left join tb_ms_cmn_cd cd on s.company_cd = cd.CD')
                ->where(['s.ID' => ['in', $store_ids]])
                ->getField('s.ID,cd.CD_VAL');
            foreach ($pageData as &$item) {
                $item['company_name'] = $company_map[$item['storeId']];
            }
            unset($store_ids);
            unset($company_map);
        }
        return $pageData;
    }

    /**
     * 数据模板
     *
     * @param array $response 返回的数据
     *
     * @return array
     */
    public function template($response)
    {
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $pageData [] = [
                'id' => $esData ['id'],
                'platName' => $esData ['platName'], // 平台名
                'storeName' => $esData ['store']['storeName'],// 店铺名
                'b5cOrderNo' => $esData ['b5cOrderNo'], // 订单号
                'bwcOrderStatusNm' => $esData ['bwcOrderStatusNm'], // 订单状态
                'orderId' => $esData ['orderId'], // 第三方订单号
                'orderNo' => $esData ['orderNo'], // 第三方订单号
                'pickingNo' => $esData ['msOrd'][0]['pickingNo'], // 拣货号
                'weight' => $esData ['msOrd'][0]['weighing'] ? $esData ['msOrd'][0]['weighing'] : 0, // 实重
                //'freight'         => $esData ['msOrd'][0]['freight']?$esData ['msOrd'][0]['freight']:0, // 运费实算
                'freight' => $esData ['amountFreight'] ? $esData ['amountFreight'] : 0, // 尾程运费实算
                'freight_currency' => cdVal($esData ['freightCurrency']) ? cdVal($esData ['freightCurrency']) : ($esData ['freightCurrency'] ?: 'CNY'), // 尾程运费试算币种
                'carry_tariff_currency' => cdVal($esData ['carryTariffCurrency']) ? cdVal($esData ['carryTariffCurrency']) : $esData ['carryTariffCurrency'], // 尾程关税币种
                'carry_tariff' => $esData ['carryTariff'], // 尾程关税
                'subsidy_currency' => cdVal($esData ['subsidyCurrency']) ? cdVal($esData ['subsidyCurrency']) : $esData ['subsidyCurrency'], // 平台补贴币种
                'subsidy' => $esData ['subsidy'], // 平台补贴
                'preFreightCurrency' => $esData ['preFreightCurrencyNm'] ? $esData ['preFreightCurrencyNm'] : $esData ['preFreightCurrency'], // 头程运费币种
                'preAmountFreight' => $esData ['preAmountFreight'], // 头程运费
                'vatFeeCurrency' => $esData ['vatFeeCurrencyNm'] ? $esData ['vatFeeCurrencyNm'] : $esData ['vatFeeCurrency'], // VAT币种
                'vatFee' => $esData ['vatFee'], // VAT
                'paypalFeeCurrency' => $esData ['paypalFeeCurrencyNm'] ? $esData ['paypalFeeCurrencyNm'] : $esData ['paypalFeeCurrency'], // 支付手续费币种
                'paypalFee' => $esData ['paypalFee'], // 支付手续费
                'leagueFeeCurrency' => $esData ['leagueFeeCurrencyNm'] ? $esData ['leagueFeeCurrencyNm'] : $esData ['leagueFeeCurrency'], // 流量活动费用币种
                'leagueFee' => $esData ['leagueFee'], // 流量活动费用
                'purReturnFeeCurrency' => $esData ['purReturnFeeCurrencyNm'] ? $esData ['purReturnFeeCurrencyNm'] : $esData ['purReturnFeeCurrency'], // 采购返佣金币种
                'purReturnFee' => $esData ['purReturnFee'], // 采购返佣金
                'freightTypeNm' => $esData ['freightTypeNm'],//运费类型
                'skuIds' => $this->getSkuIds($esData ['ordGudsOpt']), // 商品编码
                'ordGudsOpt' => $esData ['ordGudsOpt'], // 商品编码
                'warehouseNm' => $esData ['warehouseNm'],//下发仓库
                'warehouse' => $esData ['warehouse'],//仓库code
                'expeCompany' => $esData ['logisticCdNm'],//物流公司
                'expeCompanyCd' => $esData ['logisticCd'],//物流公司CD
                'logisticModel' => $esData ['logisticModelIdNm'],//物流方式
                'logisticModelId' => $esData ['logisticModelId'],//物流方式id
                'surfaceWayGetCdNm' => $esData ['surfaceWayGetCdNm'],//面单
                'surfaceWayGetCd' => $esData ['surfaceWayGetCd'],//面单
                'trackingNumber' => $esData ['ordPackage'][0]['trackingNumber'], // 运单号
                'remarkMsg' => $esData ['remarkMsg'], // 备注
                'msgCd1' => $esData ['msOrd']['msgCd1'], //核单异常
                'platCd' => $esData ['store']['platCd'],
                'findOrderJson' => json_decode($esData ['findOrderJson'], true),
                'use_remarks' => $esData ['shippingMsg'],
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'],
                'deliveryStatus' => $esData ['store']['deliveryStatus'],
                //'amountFreight' => $esData['msOrd'][0]['amountFreight']
                'after_sale_type' => $esData ['afterSaleType'],
                'insuranceFee' => $esData ['insuranceFee'] ? $esData ['insuranceFee'] : 0, //保险费用
                'insuranceCurrency' => cdVal($esData ['insuranceCurrency']) ? cdVal($esData ['insuranceCurrency']) : $esData ['insuranceCurrency'], //保险费用币种
                "order_time"    => $esData["orderTime"],
                "payment_time"  => $esData["orderPayTime"],
                "shipping_time" => $esData['shippingTime'],
                "send_ord_time" => $esData['sendOrdTime'],
                "sendout_time"  => $esData['msOrd'][0]['sendoutTime'],
                "logistics_abnormal_status" => $esData['tbOpOrderExtend']['logisticsAbnormalStatus'],
                "is_shopnc_order"          => strtolower($esData['store']['beanCd']) == 'shopnc' ? 1 : 0,
                "saleAfterFeeCurrencyCny"  => $esData['tbOpOrderExtend']['saleAfterFeeCurrencyCny'],
                "saleAfterFeeCny"  => $esData['tbOpOrderExtend']['saleAfterFeeCny'],
            ];
        }
        $pageData = SkuModel::joinPmsSkuInfo($pageData);
        return $pageData;
    }

    /**
     * 商品编码
     *
     * @param array $data 商品信息
     *
     * @return array $skuIds 商品编码
     */
    public function getSkuIds($data)
    {
        $skuIds = null;
        foreach ($data as $key => $value) {
            if ($value ['gudsNm']) {
                $skuIds [] = $value ['gudsNm'] . ' X ' . $value ['ordGudsQty'];
            } else {
                $skuIds [] = $value ['gudsOptId'] . ' X ' . $value ['ordGudsQty'];
            }
        }

        return $skuIds;
    }

    /**
     * 运费结算
     *
     * @param array $data
     * @param array $logData
     *
     * @return mixed
     */
    public function amountFreight($data, $logData = null)
    {
        if (empty($data)) {
            $response ['code'] = 3000;
            $response ['info'] = L('数据不能为空');
            $response ['data'] = null;
        } else {
            $model = new TbOpOrdModel();
            $fail = [];
            $failMessage = [];

            //需要校验新逻辑的字段
            $check_fee = ['pre_amount_freight' => '头程运费试算', 'insurance_fee' => '保险费', 'amount_freight' => '尾程运费试算', 'carry_tariff' => '尾程物流派送关税', 'vat_fee' => 'VAT', 'paypal_fee' => '支付手续费', 'league_fee' => '流量活动费用', 'subsidy' => '活动补贴', 'pur_return_fee' => '采购返佣金'];
            $currency_key_map = ['pre_amount_freight' => 'pre_freight_currency', 'insurance_fee' => 'insurance_currency', 'amount_freight' => 'freight_currency'];
            foreach ($data as $v) {
                #/^\d+(\.{0,1}\d+){0,1}$/
                #!preg_match("/^\d+(\.{0,1}\d+){0,1}$/", $v['sale_after_fee'])
                #都为空  不写入
                $extendsUpdate = [];
                if(empty($v['sale_after_fee_currency']) && $v['sale_after_fee'] === ''){
                    unset($v['sale_after_fee_currency']);
                    unset($v['sale_after_fee']);
                }else if(empty($v['sale_after_fee_currency']) || $v['sale_after_fee'] < 0 || !preg_match("/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/", trim($v['sale_after_fee']))){
                    #至少有一个不为空 
                    #只有两个都不为空 且 合法  才写入
                    unset($v['sale_after_fee_currency']);
                    unset($v['sale_after_fee']);
                    $failMessage[] = $v['B5C_ORDER_NO'].'：售后费用填写非法';
                    $fail[] = $v['B5C_ORDER_NO'];
                    continue;
                }else{
                    $extendsUpdate['sale_after_fee_currency_cny'] = 'CNY';
                    $extendsUpdate['sale_after_fee_cny'] =  exchangeRateConversion($v['sale_after_fee_currency'],'CNY') * $v['sale_after_fee'];
                    $extendsUpdate['sale_after_fee_currency'] = $v['sale_after_fee_currency'];
                    $extendsUpdate['sale_after_fee'] = $v['sale_after_fee'];
                    unset($v['sale_after_fee_currency']);
                    unset($v['sale_after_fee']);


                }
                //属于需要校验新逻辑的字段 #11333 已出库费用导入逻辑的修改 币种和金额并存 非负数即保存
                foreach ($v as $key => $value) {
                    if (isset($check_fee[$key])) {
                        $currency_key = $key . '_currency';
                        if (isset($currency_key_map[$key])) $currency_key = $currency_key_map[$key];
                        if(empty($v[$currency_key]) && $v[$key] === ''){
                            unset($v[$currency_key]);
                            unset($v[$key]);
                        }else if(empty($v[$currency_key]) || $value < 0 || !preg_match("/^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/", trim($v[$key]))){
                            #至少有一个不为空
                            #只有两个都不为空 且 合法  才写入
                            #只有两个都不为空 且 合法 且 非负数  才写入
                            unset($v[$currency_key]);
                            unset($v[$key]);
                            $failMessage[] = $v['B5C_ORDER_NO'].'：' . $check_fee[$key] . '填写非法';
                            $fail[] = $v['B5C_ORDER_NO'];
                            continue;
                        }
                    }
                }

                $ret_extends = true;
                if(count($extendsUpdate) > 0){
                    $ret_extends =  M()->table('tb_op_order_extend')->where(['order_id' => $logData[$v['B5C_ORDER_NO']]['orderId'], 'plat_cd' => $logData[$v['B5C_ORDER_NO']]['platCd']])->save($extendsUpdate);
                }

                $ret = $model->where(['B5C_ORDER_NO' => $v['B5C_ORDER_NO']])->save($v);
                if ($ret === false || $ret_extends === false) {
                    $fail[] = $v['B5C_ORDER_NO'];
                    $failMessage[] = $v['B5C_ORDER_NO'].'：数据库更新失败';
                }
            }
            if (empty($fail)) {
                $response ['code'] = 2000;
                $response ['info'] = L('操作完成');
                $response ['data'] = null;

            } else {
                if (count($fail) < count($data)) {
                    $response ['code'] = 3000;
                    $response ['info'] = implode('; ', $failMessage);
                    $response ['data'] = null;
                } else {
                    $response ['code'] = 3000;
                    $response ['info'] = implode('; ', $failMessage);
                    $response ['data'] = null;
                }
            }
            //更新日志
            #只有更新成功的才写日志
            foreach($logData as $key=>$value){
                if(in_array($value['b5cOrderNo'],$fail)){
                   unset($logData[$key]);
                }
            }


            $this->writeToLog($data, $logData);
        }


        return $response;
    }

    /**
     * @param $rdata
     * @param $logData
     */
    public function writeToLog($rdata, $logData)
    {
        $user_name = session('m_loginname');
        $excel_name = $user_name . '_' . $this->excelName;//保存上传的excel名

        $b5cOrd = null;
        foreach ($rdata as $key => $value) {
            $b5cOrd [$value ['B5C_ORDER_NO']] = $value;
        }
        $i = 1;

        foreach ($logData as $b5cOrder => $ord) {
            $i++;
            // $t = time() + $i + rand(0, 10);
            $t = time();
            $sr ['ORD_NO'] = $ord ['orderId'];
            $sr ['ORD_HIST_SEQ'] = $t;
            $sr ['ORD_STAT_CD'] = $ord ['bwcOrderStatus'];
            $sr ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $sr ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', $t);
            $sr ['updated_time'] = date('Y-m-d H:i:s', $t);
            $lnk = $b5cOrd [$b5cOrder];
            $sr ['ORD_HIST_HIST_CONT'] = L('运费、关税、补贴导入：') . '('
                . ($lnk['pre_amount_freight'] ? " 头程运费：{$ord['pre_freight_currency_val']} {$lnk['pre_amount_freight']}" : '')
                . ($lnk['amount_freight'] ? " 尾程运费：{$ord['freight_currency_val']} {$lnk['amount_freight']}" : '')
                . ($lnk['carry_tariff'] ? " 尾程物流关税：{$ord['carry_tariff_currency_val']} {$lnk['carry_tariff']}" : '')
                . ($lnk['subsidy'] ? " 平台补贴：{$ord['subsidy_currency_val']} {$lnk['subsidy']}" : '')
                . ($lnk['vat_fee'] ? " VAT费用：{$ord['vat_fee_currency_val']} {$lnk['vat_fee']}" : '')
                . ($lnk['paypal_fee'] ? " 支付手续费：{$ord['paypal_fee_currency_val']} {$lnk['paypal_fee']}" : '')
                . ($lnk['league_fee'] ? " 流量活动费用：{$ord['league_fee_currency_val']} {$lnk['league_fee']}" : '')
                . ($lnk['pur_return_fee'] ? " 采购返佣金：{$ord['pur_return_fee_currency_val']} {$lnk['pur_return_fee']}" : '')
                . ')' . ' Excel名：' . $excel_name;
            $sr ['plat_cd'] = $ord ['platCd'];
            $saveLogsData [] = $sr;
        }
        if ($saveLogsData) {
            $model = new Model();
            $model->table('sms_ms_ord_hist')->addAll($saveLogsData);
        }

        return;
    }

    /**
     * 批更新
     *
     * @param array $datas 需要更新的数据集合
     * @param object $model 模型
     * @param string $pk 主键
     *
     * @return string $sql
     */
    public function saveAll($datas, $model, $pk = '')
    {
        $sql = '';
        $lists = [];
        isset($pk) or $pk = $model->getPk();
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk == $key) {
                    if (is_numeric($value))
                        $ids [] = '"' . $value . '"';
                    else
                        $ids [] = '"' . $value . '"';
                } else {
                    $lists [$key] .= sprintf("WHEN '%s' THEN '%s'", $data [$pk], $value);
                }
            }
        }

        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }

        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN ( %s )", $model->getTableName(), rtrim($sql, ','), $pk, implode(',', $ids));
        return $sql;
    }

    /**
     * 导入数据验证
     *
     * @param type $data
     *
     * @return array
     */
    public function validateData(&$data, &$logData, $title)
    {
        $response ['code'] = 2000;
        if (count($title) != 21) {
            $response ['code'] = 3000;
            $response ['info'] = L('模板错误，请下载最新模板');
            $response ['data'] = null;
            return $response;
        }
        if (empty($data)) {
            $response ['code'] = 3000;
            $response ['info'] = L('数据不能为空');
            $response ['data'] = null;
            return $response;
        }
        $order_ids = [];
        $currency = M('ms_cmn_cd', 'tb_')->where(['CD' => ['like', 'N00059%'], 'USE_YN' => 'Y'])
            ->getField('CD_VAL,CD');
        $errs = [];
        $validate = function (&$v, $fee, $cur, $name) use ($currency, &$logData, &$errs) {
            if ($v[$fee] === "" && $v[$cur] !== "") {
                $errs[] = "{$v['B5C_ORDER_NO']} {$name}金额必填";
                return;
            }
            if ($v[$fee] !== '' && $v[$cur] === '') {
                $errs[] = "{$v['B5C_ORDER_NO']} {$name}币种必填";
                return;
            }
            if ($v[$fee] != '') {
                if (!is_numeric($v[$fee])) {
                    $errs[] = "{$v['B5C_ORDER_NO']} {$name}格式错误";
                    return;
                }
                $v[$cur] = strtoupper($v[$cur]);
                if (isset($currency[$v[$cur]])) {
                    $logData[$v['B5C_ORDER_NO']][$cur . '_val'] = $v[$cur];
                    $v[$cur] = $currency[$v[$cur]];
                } else {
                    $errs[] = "{$v['B5C_ORDER_NO']} {$name}币种不存在";
                    return;
                }
            } else {
                unset($v[$fee]);
                unset($v[$cur]);//unset($v['freight_type']);
            }
            return;
        };
        foreach ($data as &$v) {
            if (!isset($logData[$v['B5C_ORDER_NO']])) {
                $errs[] = $v['B5C_ORDER_NO'] . ' 订单号不存在';
                break;
            }
            $order_ids[] = $v['B5C_ORDER_NO'];
            if ($v['amount_freight'] == '') {
                unset($v['freight_type']);
            }
            $validate($v, 'pre_amount_freight', 'pre_freight_currency', '头程运费试算');
            $validate($v, 'amount_freight', 'freight_currency', '尾程运费试算');
            $validate($v, 'carry_tariff', 'carry_tariff_currency', '尾程物流派送关税');
            $validate($v, 'subsidy', 'subsidy_currency', '活动补贴');
            $validate($v, 'vat_fee', 'vat_fee_currency', 'VAT费用');
            $validate($v, 'paypal_fee', 'paypal_fee_currency', '支付手续费');
            $validate($v, 'league_fee', 'league_fee_currency', '流量活动费用');
            $validate($v, 'pur_return_fee', 'pur_return_fee_currency', '采购返佣金');
            $validate($v, 'insurance_fee', 'insurance_currency', '保险费');
        }
        if ($errs) {
            $response ['code'] = 3001;
            $response ['info'] = $errs[0];
            return $response;
        }
        $where = [
            'ORD_ID' => ['in', $order_ids],
            'WHOLE_STATUS_CD' => ['neq', 'N001820900',]//出库状态
        ];

        $list = TbMsOrdModel::getOutOrders($where);//查找未出库的订单
        if ($list) {
            $msg = '订单号为：';
            foreach ($list as $v) {
                $msg .= $v['ORD_ID'] . ',';
            }
            $msg .= '未出库';
            $response ['code'] = 3001;
            $response ['info'] = L($msg);
            $response ['data'] = null;
        }
        return $response;
    }

    public function getCompenListByB5cOrderNo($ids)
    {
        return M()
            ->table('tb_order_wms_compensation')
            ->where(['b5c_order_no' => ['in', $ids], 'deleted_by' => ['exp', 'IS NULL']])
            ->select();
    }
}