<?php

/**
 * User: yangsu
 * Date: 18/2/24
 * Time: 11:08
 */
class OrderEsModel extends EsModel
{
    public $Model = null;
    public $b2c_plat_arr = [
        'N000831400',
        'N000834100',
        'N000834200',
    ];
    public $thirdDeliverStatus = [
        '6' => '作废请求中',
        '7' => '作废成功',
        '8' => '作废失败',
    ];

    /**
     * @param $search_val_get
     *
     * @return array
     */
    public static function str2arrSearch($search_val_get)
    {
        $search_value = trim($search_val_get, ',');
        $temp_arr = explode(',', $search_value);
        $temp_arr = array_map(function ($temp_v) {
            return trim($temp_v);
        }, $temp_arr);
        $temp_max = count($temp_arr);
        if ($temp_max > 100) {
            $temp_max = 100;
            $temp_arr = array_slice($temp_arr, 0, $temp_max);
        }
        return $temp_arr;
    }

    /**
     * @param      $post_data
     * @param bool $not_map
     *
     * @return mixed
     */
    public function lists($post_data, $not_map = false)
    {
        $page_data = $post_data->page;
        $page['size'] = $page_data->page_count;
        $page['from'] = $page_data->this_page == 1 ? 0 : ($page_data->this_page - 1) * $page['size'];
        $es_data = $this->search(
            $this->formatListsQuery($post_data->data),
            $page,
            $this->formatSort($post_data->data->sort, $post_data->data->sort_type));
        if ($not_map) return $es_data;
        $res = $this->mapListsKey([$es_data['hits']['hits'], $es_data['hits']['total']], $page);
        $res['data'] = (new OmsAfterSaleService())->orderShowAfterSale($res['data']);
        $res['data'] = (new OrderListService())->checkOrderListCanExportInvoice($res['data']);
        return $res;
    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    public function orderDetail($post_data)
    {
        $sort = ['opOrderGuds.gudsType' => 'asc'];
        $es_data = $this->getIdData($this->joinEsOrderId($post_data));
        $res = $this->mapOrderDetail($es_data);
        return $res;
    }

    /**
     * @param $res
     *
     * @return mixed
     */
    public function isPatchOrder($res)
    {
        $is_patch_order = 0;
        $order_id = $res['data'][0]['third_party_order_id'];
        $plat_cd = $res['data'][0]['plat_cd'];
        $model = M();
        //获取所有的地址校验配置-对应的物流模式
        $logistics_modes = CodeModel::getEtcKeyValue('N00343'); //GP
        $logistics_mode_ids = M('ms_logistics_mode', 'tb_')->field('id')->where(['LOGISTICS_MODE' => ['in', array_keys($logistics_modes)]])->select();//第三方平台
        $where['o.ORDER_ID'] = $order_id;
        $where['o.PLAT_CD'] = $plat_cd;
        $where['o.logistic_cd'] = 'N000708200'; //万邑通
        $where['o.logistic_model_id'] = ['in', array_column($logistics_mode_ids, 'id')]; //万邑通
        $list = $model->table('tb_op_order o')
            ->field('o.ORDER_ID')
            ->join('left join tb_ms_store s on o.STORE_ID = s.ID')
            ->join('left join tb_op_order_extend e on o.ORDER_ID = e.order_id and o.PLAT_CD = e.plat_cd')
            ->where($where)
            ->where(' (s.SEND_ORD_TYPE = 0 OR (s.SEND_ORD_TYPE = 1 AND o.send_ord_status != \'N001820100\' )) AND (  (o.B5C_ORDER_NO IS NULL)  ) AND ( o.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( o.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (o.CHILD_ORDER_ID IS NULL)  AND ( o.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true)
            ->find();
        if (!empty($list)) $is_patch_order = 1;
        return $is_patch_order;
    }

    /**
     * @param $es_arrs
     * @param $page_data
     *
     * @return mixed
     */
    public function mapListsKey($es_arrs, $page_data)
    {

        list($es_arr, $count) = $es_arrs;
        $sku_data = [];
        foreach ($es_arr as $es) {
            $sku_data = array_merge($sku_data, $es['_source']['opOrderGuds']);
        }
        $sku_data = SkuModel::getInfo($sku_data, 'b5cSkuId', ['spu_name', 'attributes', 'image_url']);
        foreach ($sku_data as $v) {
            $sku_data_res[$v['b5cSkuId']] = $v;
        }
        $replyStatus = array_column(CommonDataModel::replyStatus(), 'CD_VAL', 'CD');
        foreach ($es_arr as $es) {
            $es     = $es['_source'];
            $images = [];
            foreach ($es['products'] as $product) {
                $images[] = $product['thumbnail'];
            }
            $images = array_unique($images);
            $replyStatusCodes = array_unique(array_column($es['tbOpOrderReturns'], 'replyStatusCode'));
            if (in_array(OmsAfterSaleService::RETURN_ORDER_INVALID, $replyStatusCodes)) unset($replyStatusCodes[array_search(OmsAfterSaleService::RETURN_ORDER_INVALID, $replyStatusCodes)]);//移除无效回邮单状态
            if (in_array(OmsAfterSaleService::RETURN_ORDER_WAITING, $replyStatusCodes)) unset($replyStatusCodes[array_search(OmsAfterSaleService::RETURN_ORDER_WAITING, $replyStatusCodes)]);//移除等待中回邮单状态
            $replyStatusCode = array_pop($replyStatusCodes);
            $res_arr['data'][] = [
                "id" => $es["id"],
                "plat_cd" => $es["platCd"],
                "platform" => $es["platName"],
                "order_type" => $this->getOrderType($es['platCd']),
                "STORE_ID" => $es["storeId"],
                "shop" => $es['store']["storeName"],
                "sales_team" => $es['store']["saleTeamCdNm"],
                "sales_team_cd" => $es['store']["saleTeamCd"],
                "consignee_name" => $es["addressUserName"],
                "commodity" => $this->commodityJoin($es['opOrderGuds'], $sku_data_res), // array itemName
                "order_number" => $es["b5cOrderNo"],
                "third_order_number" => $es["orderId"],
                "third_party_order_number" => $es["orderNo"],
                "currency" => $es["payCurrency"],
                "price" => $es["payTotalPrice"],
                "order_time" => $es["orderTime"],
                "payment_time" => $es["orderPayTime"],
                'guds' => $this->buildListGuds($es['opOrderGuds'], $sku_data_res),
                "delivery_time" => $es['shippingTime'],
                "order_status" => $es["bwcOrderStatus"],
                "order_status_name" => $es["bwcOrderStatusNm"],
                "dispatch_status" => $es["wholeStatusCd"],
                "dispatch_status_name" => $es["wholeStatusCdNm"],
                "logisticsSingleStatuCd" => $es["logisticsSingleStatuCd"],////等待作废 状态 N002080700：等待作废结果  N002080800：作废结果已经产生
                "logisticsSingleStatuCdNm" => $es["logisticsSingleStatuCdNm"],
                "logistics_status" => $es["ordPackage"][0]["logisticStatus"], // 物流状态
                "logistics_status_name" => $es["ordPackage"][0]["logisticStatusNm"], // 物流状态
                "aftermarket_status" => $es["aftermarketStatus"], // 售后
                "warehouse" => $es["warehouse"],
                "warehouse_name" => $es["warehouseNm"],
                "logistics_type" => $es['logisticModelId'],
                "logistics_type_name" => $es['logisticModelIdNm'],
                "logistics_number" => $es["ordPackage"][0]["trackingNumber"],
                "remarks" => $es["remarkMsg"],
                "use_remarks" => $es["shippingMsg"],
                "opOrderGuds" => $es["opOrderGuds"],
                "freight" => $es["sendFreight"],
                "logistics_company" => $es["logisticCd"],
                "logistics_company_name" => $es["logisticCdNm"],
                "electronic_single" => null,
                "waybill_number" => $es["ordPackage"][0]['trackingNumber'],
                "send_ord_msg" => $es["sendOrdMsg"],
                "is_spilt" => 0,
                "is_sup_spilt" => 0,
                "pay_the_total_price" => $es['payTotalPrice'],
                "pay_total_price_dollar" => $es['payTotalPriceDollar'],
                "parentOrderId" => $es['parentOrderId'],
                "surfaceWayGetCd" => $es['surfaceWayGetCd'],
                "surfaceWayGetCdNm" => $es['surfaceWayGetCdNm'],
                "findOrderJson" => $es['findOrderJson'],
                "findOrderErrorType" => $es['findOrderErrorType'],
                "thirdDeliverStatus" => $es['thirdDeliverStatus'],
                "thirdDeliverStatusVal" => isset($this->thirdDeliverStatus[$es['thirdDeliverStatus']]) ? $this->thirdDeliverStatus[$es['thirdDeliverStatus']] : '',
                "addressUserCountryIdNm" => $es['addressUserCountryIdNm'],
                "logisticsSingleErrorMsg" => $this->joinLogisticsSingleErrorMsgJsonData($es),
                "isMark" => $es['isMark'],
                "orderOrigin" => $es['orderOriginNm'],
                "orderOriginVersion" => $es['orderOriginVersion'],
                "is_remote_area" => $es['isRemoteArea'],
                "receiver_email" => htmlspecialchars_decode($es["userEmail"], ENT_QUOTES),//收货人邮箱
                "after_sale_type" => $es['afterSaleType'],
                "after_sale_type" => $es['afterSaleType'],
                "shipping_time" => $es['shippingTime'],//发货时间
                "send_ord_time" => $es['sendOrdTime'],//派单时间
                "sendout_time" => $es['msOrd'][0]['sendoutTime'],//出库时间
                "address" => isset( $es['addressUserAddress1'] ) ? $es['addressUserAddress1'] : "", // 收件人地址
                "doorplate" => isset( $es['tbOpOrderExtend']['doorplate'] ) ? $es['tbOpOrderExtend']['doorplate'] : "", // 收件人地址中的门牌号
    //                "doorplate" => isset( $es['tbOpOrderExtend']['doorplate'] ) ? $es['tbOpOrderExtend']['doorplate'] : "", // 收件人地址中的门牌号
                "address2" => isset( $es['addressUserAddress2'] ) ? $es['addressUserAddress2'] : "", // 收件人地址2
                "third_deliver_status" => $es['thirdDeliverStatus'],
                "is_auto_get_no" => $es['tbOpOrderExtend']['isAutoGetNo'],
                "is_remote_area_val" => $es['isRemoteArea'],
                'order_id' => $es['orderId'],
                "reissue_status_cd" => $es['tbOpOrderExtend']['reissueStatusCd'],
                "return_status_cd" => $es['tbOpOrderExtend']['returnStatusCd'],
                "refund_status_cd" => $es['tbOpOrderExtend']['refundStatusCd'],
                "address_valid_res" => $es['tbOpOrderExtend']['addressValidRes'],
                "address_valid_status" => $es['tbOpOrderExtend']['addressValidStatus'],
                "replyOrderNo" => isset($es['tbOpOrderReturns']) && !empty($es['tbOpOrderReturns']) ? array_filter(array_column($es['tbOpOrderReturns'], 'replyOrderNo')) : [],
                "logisticsNo" => isset($es['tbOpOrderReturns']) && !empty($es['tbOpOrderReturns']) ? array_filter(array_column($es['tbOpOrderReturns'], 'logisticsNo')) : [],
                "replyStatusCode" => isset($es['tbOpOrderReturns']) && !empty($es['tbOpOrderReturns']) ? $replyStatusCode : '',
                "replyStatus" => isset($es['tbOpOrderReturns']) && !empty($es['tbOpOrderReturns']) ? $replyStatus[$replyStatusCode] : '',
                "child_order_id" => $es['childOrderId'],
                "images" => $images,
                "logistics_abnormal_status_name" => empty($es['tbOpOrderExtend']['logisticsAbnormalStatus']) ? '' : ($es['tbOpOrderExtend']['logisticsAbnormalStatus'] == '1' ? '扫描超时' : '投妥超时'),
                "is_shopnc_order" => strtolower($es['store']['beanCd']) == 'shopnc' ? 1 : 0,
                "brandType" => isset($es['brandType']) ? $es['brandType'] : '',

            ];
            $after_status_cds[] = $es['tbOpOrderExtend']['reissueStatusCd'];
            $after_status_cds[] = $es['tbOpOrderExtend']['returnStatusCd'];
            $after_status_cds[] = $es['tbOpOrderExtend']['refundStatusCd'];
        }
        $code_map = [];
        $after_status_cds = array_filter(array_unique($after_status_cds));
        if (!empty($after_status_cds)) {
            $code_map = M('cmn_cd','tb_ms_')->where(['CD'=>['IN',$after_status_cds]])->getField('CD,CD_VAL');
        }
        foreach ($res_arr['data'] as &$item) {
            $item['after_sale_type'] = implode(',', array_filter([
                $code_map[$item['reissue_status_cd']],
                $code_map[$item['return_status_cd']],
                $code_map[$item['refund_status_cd']]
            ]));
        }
        $res_arr['page'] = $this->joinPage($count, $page_data);
        return $res_arr;
    }

    /**
     * @todo 订单类型，
     *       参数名    类型    说明
     * transaction_number    string    交易号
     * total_price_of_goods    string    商品总价
     * trading_currency    string    交易币种
     * taxes_and_fees    string    税费
     * shipping_costs    string    运费
     * pay_the_total_price    string    支付总价
     * discounted_price    string    优惠金额
     * pay_the_total_price    string    支付总价
     *       buyer_id    string    买家身份证
     * zip_code    string    邮编
     * user_comments    string    用户备注
     *
     * @param $es_arrs
     *
     * @return mixed
     */
    private function mapOrderDetail($es_arrs)
    {
        $es = $es_arrs['_source'];

        $guds = $this->buildGuds2($es);
        //获取所有的地址校验配置-对应的物流模式
        $address_valid_conf = array_column(CommonDataModel::addressValidConf(), 'cdVal');

        // 店铺信息补充
        list($btc_order_type_cd,$btc_order_type_cd_val,$cooperative_info) = $this->disStore($es);


        $res_arr['data'][] = [
            "order_id" => $es["b5cOrderNo"],
            "plat_cd" => $es["platCd"],
            "order_time" => $es["orderTime"],
            "payment_time" => $es["orderPayTime"],
            // "delivery_time" => $es['msOrd'][0]['sendoutTime'],
            "delivery_time" => $es['shippingTime'],
            "order_type" => $this->getOrderType($es['platCd']), // 订单类型
            "third_party_order_status" => $es["orderStatus"],
            "order_status" => $es["bwcOrderStatusNm"],
            "sales_team_cd" => $es['store']["saleTeamCd"],
            "warehouse" => $es["warehouse"],
            //"order_status" => $es['msOrd'][0]["ordStatCdNm"],
            "platform_name" => $es["store"]["platCdNm"],
            "third_party_order_id" => $es["orderId"],
            "STORE_ID" => $es["storeId"],
            "store_name" => $es["store"]['storeName'],
            "third_party_order_number" => $es["orderNo"],
            "sales_team" => $es["store"]['saleTeamCdNm'],
            "order_source" => $this->zeroOrder($es["fileName"], $es['source']),
            "operational_notes" => $es["remarkMsg"], // 运营备注
            'guds' => $guds,
            "transaction_number" => $es['payTransactionId'],
            "total_price_of_goods" =>  $es['payItemPrice'], // 商品总价
            "trading_currency" => $es['payCurrency'],
            "pay_instalment_service_amount" => $es['payInstalmentServiceAmount'],//分期手续费
            "pay_wrapper_amount" => $es['payWrapperAmount'],//包装费
            "pay_settle_price" =>   $es['paySettlePrice'],//结算费
            "pay_settle_price_dollar" =>  $es['paySettlePriceDollar'],//结算费（美元）
            "taxes_and_fees" => $es['tariff'],//税费
            "shipping_tax" => $es['shippingTax'],//运费税费-AMAZON
            "promotion_discount_tax" => $es['promotionDiscountTax'],//优惠费税费-AMAZON
            "shipping_discount_tax" => $es['shippingDiscountTax'],//运费折扣税费-AMAZON
            "gift_wrap_tax" => $es['giftWrapTax'],//包装费税费-AMAZON
            "shipping_costs" => $es['payShipingPrice'],
            "pay_the_total_price" =>  $es['payTotalPrice'], // 商品总价
            "discounted_price" => $es['payVoucherAmount'],  // 优惠金额
            "promo_code" => $es['promoCode'],  // 优惠金额
            "pay_the_total_price_usd" =>  $es['payTotalPriceDollar'], // 商品总价
            "consignee_name" => htmlspecialchars_decode($es["addressUserName"], ENT_QUOTES),
            "receiver_phone_number" => htmlspecialchars_decode($es["addressUserPhone"], ENT_QUOTES),
            "receiver_tel_number" => htmlspecialchars_decode($es["receiverTel"], ENT_QUOTES),//收货人电话
            "receiver_email" => htmlspecialchars_decode($es["userEmail"], ENT_QUOTES),//收货人邮箱
            "isB2c" => OrderModel::isB2c($es["platCd"]),//是否b2c平台
            "buyer_id" => $this->decodeCardId($es["addressUserIdentityCard"], $es["platCd"]),//收货人身份证
            "zip_code" => htmlspecialchars_decode($es['addressUserPostCode'], ENT_QUOTES),
            "province" => htmlspecialchars_decode($es['addressUserProvinces'], ENT_QUOTES),
            // "country" => $es['addressUserCountry'],
            "country" => $es['addressUserCountryEdit'] ?: (htmlspecialchars_decode($es['addressUserCountry'], ENT_QUOTES) ?: $es['addressUserCountryCode']),
            "address" => htmlspecialchars_decode($es['addressUserAddress1'], ENT_QUOTES),
            "address2" => htmlspecialchars_decode($es['addressUserAddress2'], ENT_QUOTES),
            "address3" => htmlspecialchars_decode($es['addressUserAddress3'], ENT_QUOTES),
            "address4" => htmlspecialchars_decode($es['addressUserAddress4'], ENT_QUOTES),
            "address5" => htmlspecialchars_decode($es['addressUserAddress5'], ENT_QUOTES),
            "city" => htmlspecialchars_decode($es['addressUserCity'], ENT_QUOTES),
            "region" => htmlspecialchars_decode($es['addressUserRegion'], ENT_QUOTES),
            "user_comments" => $es['shippingMsg'],
            "orderOrigin" => $es['orderOriginNm'],
            "payAccountId" => $es['payAccountId'],
            "payMethod" => $es['payMethod'],
            "buyerUserIdentityName" => $es['buyerUserIdentityName'],
            "buyerUserIdentityCard" => $es['buyerUserIdentityCard'],
            "orderOriginVersion" => $es['orderOriginVersion'],
            "buyerMobile" => $es['buyerMobile'],
            "associateOrders" => $this->associateOrdersFormat($es['associateOrders']),
            "sendOrdStatus" => $es['sendOrdStatus'],
            "storeIndexUrl" => $this->joinUrl($es['store']['storeIndexUrl']),
            "patch_data" => $this->buildPatchData($es["orderId"], $es["platCd"]),
            "credit_card_no" => $es["tbOpOrderExtend"]['creditCardNo'],
            "credit_card_bill_address" => $es["tbOpOrderExtend"]['creditCardBillAddress'],
            "credit_card_phone_no" => $es["tbOpOrderExtend"]['telCode'].$es["tbOpOrderExtend"]['creditCardPhoneNo'],
            "credit_card_sms" => $this->getCardSms($es["tbOpOrderExtend"]['creditCardPhoneNo'], $es["orderId"]),
            "send_ord_time" => $es['sendOrdTime'],
            "sendout_time" => $es['msOrd'][0]['sendoutTime'],
            "doorplate" => isset($es['tbOpOrderExtend']['doorplate']) ? $es['tbOpOrderExtend']['doorplate'] : "",
            "total_deposit_amount" => $es['totalDepositAmount'],
            "refund_info" => $this->buildRefundInfo($es['tbOpOrderExtend']),
            "packing_no" => $es['packingNo'],
            "other_discounted_price" => $es['tbOpOrderExtend']['otherDiscountedPrice'],
            "platform_discount_price" => $es['tbOpOrderExtend']['platformDiscountPrice'],
            "seller_discount_price" => $es['tbOpOrderExtend']['sellerDiscountPrice'],
            "product_id" => M('store','tb_ms_')->field('product_id')->where("id = ".$es["storeId"])->find()['product_id'],
            "out_trade_no" => $es['outTradeNo'],
            "sub_addr_recipient_name" => $es['tbOpOrderExtend']['subAddrRecipientName'],//次要收货人姓名
            "sub_addr" => $es['tbOpOrderExtend']['subAddr'],//次要收货人地址
            "kr_customs_code" => $es['tbOpOrderExtend']['krCustomsCode'],//通行符

            "logistics_type_name" => $es['logisticModelIdNm'],
            "is_address_valid" => in_array($es['logisticModelIdNm'], $address_valid_conf) ? true : false,
            "address_valid_res" => $es['tbOpOrderExtend']['addressValidRes'],
            "address_valid_status" => $es['tbOpOrderExtend']['addressValidStatus'],
            "buyerUserId" => isset($es['tbOpOrderExtend']['buyerUserId']) ? $es['tbOpOrderExtend']['buyerUserId'] : "",

            'sell_small_team_cd' => $es['store']['sellSmallTeamCd'] ,  // 销售小团队CODE
            'sell_small_team' => isset($es['store']['sellSmallTeamCd']) && !empty($es['store']['sellSmallTeamCd'])
                ? CodeModel::getValue($es['store']['sellSmallTeamCd'])['CD_VAL'] : "",     // 销售小团队昵称
            "has_invoice_address" => (isset($es['tbOpBillingAddress']) && !empty($es['tbOpBillingAddress'])) ? 1 : -1,
            "invoice_city" => isset($es['tbOpBillingAddress']['city']) ? $es['tbOpBillingAddress']['city'] : "",
            "invoice_civility" => isset($es['tbOpBillingAddress']['civility']) ? $es['tbOpBillingAddress']['civility'] : "",
            "invoice_company" => isset($es['tbOpBillingAddress']['company']) ? $es['tbOpBillingAddress']['company'] : "",
            "invoice_country" => isset($es['tbOpBillingAddress']['country']) ? $es['tbOpBillingAddress']['country'] : "",
            "invoice_name" => isset($es['tbOpBillingAddress']['name']) ? $es['tbOpBillingAddress']['name'] : "",
            "invoice_phone" => isset($es['tbOpBillingAddress']['phone']) ? $es['tbOpBillingAddress']['phone'] : "",
            "invoice_apartment_number" => isset($es['tbOpBillingAddress']['apartment_number']) ? $es['tbOpBillingAddress']['apartment_number'] : "",
            "invoice_mobile" => isset($es['tbOpBillingAddress']['mobile']) ? $es['tbOpBillingAddress']['mobile'] : "",
            "invoice_state" => isset($es['tbOpBillingAddress']['state']) ? $es['tbOpBillingAddress']['state'] : "",
            "invoice_street" => isset($es['tbOpBillingAddress']['street']) ? $es['tbOpBillingAddress']['street'] : "",
            "invoice_street_1" => isset($es['tbOpBillingAddress']['street1']) ? $es['tbOpBillingAddress']['street1'] : "",
            "invoice_street_2" => isset($es['tbOpBillingAddress']['street2']) ? $es['tbOpBillingAddress']['street2'] : "",
            "invoice_street_3" => isset($es['tbOpBillingAddress']['street3']) ? $es['tbOpBillingAddress']['street3'] : "",
            "invoice_street_4" => isset($es['tbOpBillingAddress']['street4']) ? $es['tbOpBillingAddress']['street4'] : "",
            "invoice_province" => isset($es['tbOpBillingAddress']['province']) ? $es['tbOpBillingAddress']['province'] : "",
            "invoice_area" => isset($es['tbOpBillingAddress']['area']) ? $es['tbOpBillingAddress']['area'] : "",
            "invoice_zip_code" => isset($es['tbOpBillingAddress']['zip_code']) ? $es['tbOpBillingAddress']['zip_code'] : "",
            "btc_order_type_cd" => $btc_order_type_cd,                //  B2C订单类型CODE
            "btc_order_type_cd_val" => $btc_order_type_cd_val,    //  B2C订单类型值
            "cooperative_info" => $cooperative_info,               //  合作信息
            "is_shopnc_order"          => strtolower($es['store']['beanCd']) == 'shopnc' ? 1 : 0,
        ];
        if ($es['tbOpOrderExtend']['otherDiscountedPrice'] <= 0) {
            $res_arr['data']["other_discounted_price"] = null;
        }
        if ($es['tbOpOrderExtend']['platformDiscountPrice'] <= 0) {
            $res_arr['data']["platform_discount_price"] = null;
        }
        if ($es['tbOpOrderExtend']['sellerDiscountPrice'] <= 0) {
            $res_arr['data']["seller_discount_price"] = null;
        }
        $res_arr['data']["order_inc_id"] = $es['id'];
        return $res_arr;
    }

    private function disStore($es){
        $store_info = M('store','tb_ms_')->where(array('ID'=>$es["storeId"]))->find();
        $btc_order_type_cd = $es['tbOpOrderExtend']['btcOrderTypeCd'];
        $btc_order_type_cd_val = cdVal($btc_order_type_cd);
        $supplier_id_val = "";
        if (!empty($store_info['supplier_id'])){
            $supplier_info = M('sp_supplier','tb_crm_')->field('SP_NAME')->where(array('ID'=>$store_info['supplier_id']))->find();
            if ($supplier_info) $supplier_id_val = $supplier_info['SP_NAME'];
        }
        $cooperative_info = array(
            'reality_opt_store_id' => $store_info['reality_opt_store_id'],
            'supplier_id' =>  $store_info['supplier_id'],
            'supplier_id_val' =>  $supplier_id_val,
        );
        return array($btc_order_type_cd,$btc_order_type_cd_val,$cooperative_info);
    }

    public function getCardSms($phone_number, $order_id)
    {
        if (empty($phone_number)) {
            return null;
        }
        $data = [
            'key' => GP_SMS_KEY,
            'mobile' => $phone_number,
            'order_id' => $order_id,
        ];
        $res_api = ApiModel::getCardSms($data);
        return $res_api['recvInfo'];
    }

    private function associateOrdersFormat($associateOrders)
    {
        foreach ($associateOrders as &$v) {
            switch ($v['orderType']) {
                case -1:
                    $v['orderTypeNm'] = '母单';
                    break;
                case 0:
                    $v['orderTypeNm'] = '子单';
                    break;
                case 1:
                    $v['orderTypeNm'] = '补发单';
                    break;
                case 2:
                    $v['orderTypeNm'] = '退货单';
                    break;
                default:
                    break;
            }
        }
        return $associateOrders;
    }

    public function decodeCardId($cardid, $platCd, $mark = true)
    {
        if (OrderModel::isB2c($platCd)) {
            if ($cardid) {
                if (strpos($cardid, '*') === false) {
                    $old_cardid = $mark ? substr($cardid, 0, 3) . '*********' : $cardid;
                    $cardid = ZUtils::encodeDecodeId("dec", $cardid);
                    if (!preg_match("/^[\w\+=\-\_\/\\\|]+$/", $cardid)) {//解密失败
                        # 记录错误日志
                        $cardid = str2Utf8($cardid);
                        Logs(['json_data' => ['act' => 'dec', 'num' => $cardid], 'res' => $cardid], __FUNCTION__, __CLASS__);
                        $cardid = L('解密失败') . '：' . $old_cardid;
                        $mark = false;

                    }
                }
                if ($mark) $cardid = substr($cardid, 0, 3) . '*********';
            }
            return $cardid;
        } else {
            return htmlspecialchars_decode($cardid, ENT_QUOTES);
        }
    }

    /**
     * @param $guds
     * @param $payItemPrice    商品总价
     * @param $payTotalPrice   支付总价
     * @param $btc_order_type_cd   B2C订单类型
     * @return array
     */
    private function buildGuds2($es)
    {

        $guds = $es['opOrderGuds'];
        $payItemPrice  =  0;
        $sku_ids = array_column($guds, 'b5cSkuId');
        if (empty($sku_ids)) {
            return $guds;
        }
        $pms = new PmsBaseModel();
        $where['ps.sku_id'] = ['in', $sku_ids];
        $where['pbd.language'] = 'N000920100';
        $guds_data_tmp = $pms->table('product_sku ps')
            ->field("ps.sku_id,pd.spu_name as spu_name_en,pd2.spu_name as spu_name_cn,IF(ps.upc_more, REPLACE(CONCAT_WS(',',ps.upc_id,ps.upc_more),',',',\\r\\n'), ps.upc_id) as upc_id,
            ps.sku_width,ps.sku_height,ps.sku_weight,ps.sku_length,ps.hs_price,ps.hs_code,p.currency_type,p.is_group_sku,pbd.brand_name")
            ->join('left join product_detail pd on pd.spu_id=ps.spu_id and pd.language="N000920200"')
            ->join('left join product_detail pd2 on pd2.spu_id=ps.spu_id and pd2.language="N000920100"')
            ->join('left join product p on p.spu_id=ps.spu_id')
            //获取es订单的品牌中文信息
            ->join('left join product_brand_detail pbd on pbd.brand_id = p.brand_id')
            ->where($where)
            ->select();
        $guds_data_tmp = SkuModel::getInfo($guds_data_tmp, 'sku_id', ['spu_name', 'image_url', 'attributes'], ['spu_name' => 'spu_name']);
        $guds_data = array_combine(array_column($guds_data_tmp, 'sku_id'), $guds_data_tmp);
        foreach ($guds as &$v) {
            $gud = $guds_data[$v['b5cSkuId']];
            $v['GUDS_HS_CODE2'] = $gud['hs_code'];
            $v['barcode'] = $gud['upc_id'];
            $v['gudsImgCdnAddr'] = $gud['image_url'];
            $v['guds_img_cdn_addr'] = $gud['image_url'];
            $v['optNameValueStr'] = $gud['attributes'];
            $v['product_name'] = $gud['spu_name_cn'];
            $v['product_name_en'] = $gud['spu_name_en'];
            $v['specification'] = $gud['sku_length'] . '*' . $gud['sku_width'] . '*' . $gud['sku_height'];
            $v['spu_currency'] = cdVal($gud['currency_type']);
            $v['weight'] = $gud['sku_weight'];
            $v['is_group_sku'] = $gud['is_group_sku'];
            $v['brand_name'] = $gud['brand_name'];
        }
        return $guds;
    }

    private function buildListGuds($guds, $sku_data)
    {
        foreach ($guds as &$v) {
            $v['product_name'] = $sku_data[$v['b5cSkuId']]['spu_name'];
            $v['optNameValueStr'] = $sku_data[$v['b5cSkuId']]['attributes'];
            $v['image_url'] = $sku_data[$v['b5cSkuId']]['image_url'];
        }
        return $guds;
    }

    private function joinUrl($url)
    {
        if (false === strpos($url, 'http')) {
            $url = 'http://' . $url;
        }
        return $url;
    }

    private function zeroOrder($data, $source)
    {
        if ($source) return $source;
        $res = '拉单';
        if ($data) {
            $res = 'Excel 导入';
        }
        return $res;
    }

    public function mapOrderExport($res)
    {
        $es_arrs = $this->formatData($res);
        Logs('act', '', 'es_order');
        $es = $es_arrs['_source'];
        $res_arr['data'][] = [
            "order_id" => $es["b5cOrderNo"],
        ];
        Logs('end', '', 'es_order');
        return $res_arr;

    }


    /**
     * @param $data
     *
     * @return array
     */
    public function formatData($data)
    {
        return [$data['hits']['hits'], $data['hits']['total']];
    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    private function formatPage($post_data)
    {
        $page['size'] = $post_data->page_count;
        if ($post_data->this_page == 1) {
            $page['from'] = 0;
        } else {
            $page['from'] = ($post_data->this_page - 1) * $page['size'];
        }
        return $page;
    }

    /**
     * @param $post_data
     * @param $sort_type
     *
     * @return array
     */
    private function formatSort($post_data, $sort_type)
    {
        $sort = [
            'orderTime' => 'desc'
        ];
        if ($post_data) {
            unset($sort);
            $sort_map_arr = [
                'order_time'    => 'orderTime',
                'pay_time'      => 'orderPayTime',
                'send_time'     => 'shippingTime',
                'send_ord_time' => 'sendOrdTime',
                's_time4'  => 'msOrd.sTime4.keyword',
                'sendout_time'  => 'msOrd.sendoutTime.keyword',
            ];
            if (empty($sort_type) && !in_array($sort_type, ['desc', 'asc'])) {
                $sort_type = 'desc';
            }
            $sort = [
                $sort_map_arr[$post_data] => $sort_type
            ];
        }
        return $sort;
    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    private function formatListsQuery($post_data)
    {
        $es_where_arr = [
            'platform' => 'platCd',
            'order_status' => 'bwcOrderStatus',
            'order_source_status' => 'orderOrigin',
            'dispatch_status' => 'wholeStatusCd',
            'wholeStatusCd' => 'msOrd.wholeStatusCd',
            'sendOrdStatus' => 'sendOrdStatus',
            'do_dispatch_status' => 'sendOrdTypeCd',
            'do_dispatch_type' => 'findOrderErrorType',
            'logistics_status' => 'ordPackage.logisticStatus',
            'aftermarket' => '',
            'country' => 'addressUserCountryId',
            'shop' => 'storeId',
            'warehouse' => 'warehouse',  // 下发
            'logistics_company' => 'logisticCd',
            'logistics_method' => 'logisticModelId',

            'surfaceWayGetCd' => 'surfaceWayGetCd',
            'logisticsSingleStatuCd' => 'logisticsSingleStatuCd',

            'buyer_user_id' => 'tbOpOrderExtend.buyerUserId',
//           'brand_type' => 'brandType',
//            'logistics_abnormal_status' => 'tbOpOrderExtend.logisticsAbnormalStatus',
        ];

        $es_search_time_type = [
            'order_time'    => 'orderTime',
            'pay_time'      => 'orderPayTime',
            'send_time'     => 'shippingTime',
            'send_ord_time' => 'sendOrdTime',
            's_time4'       => 'msOrd.sTime4.keyword',
            'sendout_time'  => 'msOrd.sendoutTime',//由于es改了索引字段类型，sendoutTime由text改成了long，不支持用keyword查询
//            'sendout_time'  => 'msOrd.sendoutTime.keyword',

        ];

        $es_search_condition = [
            'order_id' => 'b5cOrderNo',
            'thr_order_id' => 'orderId',
            'thr_order_no' => 'orderNo',
            'receiver_phone' => 'addressUserPhone',
            'pay_the_serial_number' => 'payTransactionId',
            'pay_method' => 'payMethod',
            'receiver_tel' => 'receiverTel',
            'consignee_name' => 'addressUserName',
            'tracking_number' => 'ordPackage.trackingNumber',
            'sku_title' => 'productDetails.spuName',
            'sku_number' => 'opOrderGuds.b5cSkuId',
            'zip_code' => 'addressUserPostCode',
            "receiver_email" => 'userEmail',
            "address_valid_status" => 'tbOpOrderExtend.addressValidStatus',
            'sales_team' => 'store.saleTeamCd',
            'reply_status' => 'tbOpOrderReturns.replyStatusCode',
            'sell_small_team' => 'store.sellSmallTeamCd',
 //           'remark_msg' => 'shippingMsg',
 //           "is_remote_area" => 'isRemoteArea'
        ];
        if (isset($post_data->country)) {
            // $post_data->country = $this->toCountry($post_data->country);
        }
        //品牌类型参数组装 前端 [1,2,3,4,5] 对应后台 ['ODM', 'NON_ODM', 'ODM,MIXING', 'NON_ODM,MIXING', 'NON']
        /*if (isset($post_data->brand_type) && !empty($post_data->brand_type)) {
            $brand_type_map = ['', 'ODM', 'NON_ODM', 'ODM,MIXING', 'NON_ODM,MIXING', 'NON'];
            $brand_type = '';
            foreach ($post_data->brand_type as $key => $value) {
                if (isset($brand_type_map[$value])) {
                    //其他=NON:brandType索引为null
                    if ('NON' == $brand_type_map[$value]) {
                        $must_not = ' (brandType:(N002800014 OR (NOT brandType:?*)) ';
                    } else {
                        $brand_type = $brand_type ? $brand_type . ',' . $brand_type_map[$value] : $brand_type_map[$value];
                    }
                } else {
                    unset($post_data->brand_type[$key]);
                }
            }
            //存在其他的情况将品牌类型转换为 query查询语句
            if (isset($must_not)) {
                $must_not = !empty($brand_type) ? ' (brandType:(' . implode(',', array_unique(explode(',', $brand_type))) . ' OR (NOT brandType:?*))) ' :  ' (NOT brandType:?*) ';
                ($post_data->query_string) ?
                    $post_data->query_string .= ' AND ' . $must_not
                    : $post_data->query_string = $must_not;
                //移除构造器查询
                unset($post_data->brand_type);
            } else {
                if (!empty($brand_type)) $post_data->brand_type = array_unique(explode(',', $brand_type));
            }
        }*/

        //仓库参数组装 其他=NON:brandType索引为null
        if (isset($post_data->warehouse) && !empty($post_data->warehouse)) {
            //存在其他选项情况将仓库转换为 query查询语句
            if (in_array('NON', $post_data->warehouse)) {
                //有多个选项
                $must_not = count($post_data->warehouse) > 1 ? ' (warehouse:(' . implode(',', $post_data->warehouse) . ' OR (NOT warehouse:?*))) ' :  ' (NOT warehouse:?*) ';
                ($post_data->query_string) ?
                    $post_data->query_string .= ' AND ' . $must_not
                    : $post_data->query_string = $must_not;
                //移除构造器查询
                unset($post_data->warehouse);
            }
        }
        if ($post_data) {
            $query_arr = $this->esAndInOr($post_data, $es_where_arr);
        }
        //品牌类型参数组装 前端 [1,2,3,4,5] 对应后台 ['ODM', 'NON_ODM', 'ODM,MIXING', 'NON_ODM,MIXING', 'OTHER']
        if (isset($post_data->brand_type) && !empty($post_data->brand_type)) {
            $brand_type_map = ['', 'ODM', 'NON_ODM', 'ODM,MIXING', 'NON_ODM,MIXING', 'OTHER'];
            $should = [];
            foreach ($post_data->brand_type as $key => $value) {
                $term = [];
                if (isset($brand_type_map[$value])) {
                    if ($brand_type_map[$value] == 'ODM' || $brand_type_map[$value] == 'NON_ODM') {
                        $term = [['term' => ['brandTypeCount' => 1]]];
                    }
                    if ($brand_type_map[$value] == 'ODM,MIXING') {
                        $brand_type_map[$value] = 'ODM';
                        $term = [['range' => ['brandTypeCount' => ['gt' => 1]]]];
                    }
                    if ($brand_type_map[$value] == 'NON_ODM,MIXING') {
                        $brand_type_map[$value] = 'NON_ODM';
                        $term = [['range' => ['brandTypeCount' => ['gt' => 1]]]];
                    }
                    $term[] = ['term' => ['brandType.keyword' => $brand_type_map[$value]]];
                    //$query_arr['bool']['should'][] = ['bool' => ['must' => $term]];
                    $should[] = ['bool' => ['must' => $term]];
                }
            }
            $query_arr['bool']['must'][] = ['bool' => ['should' => $should]];
        }
        //$query_arr['bool']['filter'][] = ['term' => ['brandType.keyword' => 'NON_ODM']];
        //$query_arr['bool']['filter'][] = ['term' => ['brandType.keyword' => 'ODM']];

        if (isset($post_data->risk_value)) {
            if($post_data->risk_value == 1){
                ($post_data->query_string) ?
                    $post_data->query_string .= ' AND  tbOpOrderExtend.riskValue:>80'
                    : $post_data->query_string = '  tbOpOrderExtend.riskValue:>80';
            }
        }
        if (isset($post_data->address_valid_status) && $post_data->address_valid_status != '') {
            if (!is_array($post_data->address_valid_status)) $post_data->address_valid_status = [$post_data->address_valid_status];
            foreach ($post_data->address_valid_status as $key => $value) {
                $match_phrase[] = ['term' => [$es_search_condition['address_valid_status'] => $value]];
            }
            $query_arr['bool']['must'][] = ['bool' => ['should' => $match_phrase]];
        }
        // remark_haveremarkMsg
        if (isset($post_data->remark_have) && $post_data->remark_have == 1) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND  (remarkMsg:?* OR shippingMsg:?*) '
                : $post_data->query_string = '  (remarkMsg:?* OR shippingMsg:?*) ';
        }
        if (isset($post_data->hasDefaultWarehouse) && $post_data->hasDefaultWarehouse == 1) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND  hasDefaultWarehouse:1'
                : $post_data->query_string = '  hasDefaultWarehouse:1 ';
        }
        if (isset($post_data->thirdDeliverStatus) && $post_data->thirdDeliverStatus == 1) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND  thirdDeliverStatus:1'
                : $post_data->query_string = '  thirdDeliverStatus:1 ';
        }

        if (isset($post_data->sales_team) && !empty($post_data->sales_team)) {
            foreach ($post_data->sales_team as $key => $value) {
                $match_phrase[] = ['match_phrase' => [$es_search_condition['sales_team'] => $value]];
            }   
            $query_arr['bool']['must'][] = ['bool' => ['should' => $match_phrase]]; 
        }

        if (isset($post_data->sell_small_team) && !empty($post_data->sell_small_team)) {
            foreach ($post_data->sell_small_team as $key => $value) {
                $match_phrase[] = ['match_phrase' => [$es_search_condition['sell_small_team'] => $value]];
            }
            $query_arr['bool']['must'][] = ['bool' => ['should' => $match_phrase]];
        }

        if (isset($post_data->reply_status) && !empty($post_data->reply_status)) {
            foreach ($post_data->reply_status as $key => $value) {
                $match_phrase[] = ['match_phrase' => [$es_search_condition['reply_status'] => $value]];
            }
            $query_arr['bool']['must'][] = ['bool' => ['should' => $match_phrase]];
        }

        if ($post_data->search_time_type && ($post_data->search_time_left || $post_data->search_time_right)) {
            // search_time_type
            if ($post_data->search_time_left) $data_range['range'][$es_search_time_type[$post_data->search_time_type]]['gte'] = strtotime($post_data->search_time_left) . '000';
            //if ($post_data->search_time_right) $data_range['range'][$es_search_time_type[$post_data->search_time_type]]['lte'] = strtotime(date('Y-m-d', strtotime($post_data->search_time_right))) + ((24 * 60 * 60) - 1) . '999';
            if ($post_data->search_time_right) $data_range['range'][$es_search_time_type[$post_data->search_time_type]]['lte'] = strtotime($post_data->search_time_right) . '000'; // #9968 
            $query_arr['bool']['must'][] = $data_range;
        }
        $search_value = trim($post_data->search_value);
        if ($search_value) {
            $search_accurate_arr = ['zip_code', 'receiver_email', 'pay_method'];
            if ((($post_data->search_condition == 'thr_order_no' || $post_data->search_condition == 'order_id' || $post_data->search_condition == 'thr_order_id') && strpos($search_value, ',')) ||
                in_array($post_data->search_condition, $search_accurate_arr)
            ) {
                $k_v = self::orderAllSearch($post_data->search_value, $post_data->search_condition);
                $query_arr['bool']['must'][] = $k_v;
            } else if ($post_data->search_condition == 'remark_msg') {
                ($post_data->query_string) ?
                    $post_data->query_string .= ' AND  (remarkMsg:"'.'*' . $search_value . '*'.'" OR shippingMsg:"'.'*' . $search_value . '*'.'") '
                    : $post_data->query_string = '  (remarkMsg:"'.'*' . $search_value . '*'.'" OR shippingMsg:"'.'*' . $search_value . '*'.'") ';
            }else{
                $query_arr['bool']['must'][] = ['bool' => ['should' => ['wildcard' => [$es_search_condition[$post_data->search_condition] . '.keyword' => '*' . $search_value . '*']]]];

            }
        }
        if (!empty($post_data->recommend_type)) {
            ($post_data->query_string) ? $post_data->query_string .= ' AND' : '';
            $post_data->query_string .= ' (  ';
            $temp_string = '';
            foreach ($post_data->recommend_type as $recommend_type_value) {
                if ('recommend_system' == $recommend_type_value) {
                    $temp_string .= 'OR ( findOrderFailMsg:success  AND hasDefaultWarehouse:0 ) ';
                }
                if ('recommend_user' == $recommend_type_value) {
                    $temp_string .= 'OR  hasDefaultWarehouse:1 ';
                }
                if ('operation_assignment' == $recommend_type_value) {
                    $temp_string .= 'OR  hasDefaultWarehouse:2 ';
                }
            }
            $post_data->query_string .= trim($temp_string, 'OR');
            $post_data->query_string .= ' ) ';
        }
        if (!empty($post_data->patch_err)) {
            ($post_data->query_string) ? $post_data->query_string .= ' AND' : '';
            $post_data->query_string .= ' (  ';
            $temp_string = '';
            foreach ($post_data->patch_err as $patch_err_value) {
                if ('un_inventory' == $patch_err_value) {
                    $temp_string .= 'OR  sendOrdTypeCd:N002030700 ';
                }
                if ('un_warehouse_logistics' == $patch_err_value) {
                    $temp_string .= 'OR  (NOT (warehouse:?* AND logisticModelId:?* AND logisticCd:?*)) ';
                }
                if ('un_single_number' == $patch_err_value) {
                    $temp_string .= 'OR  surfaceWayGetCd:N002010100 ';
                    $post_data->must_not[] = 'ordPackage.trackingNumber';
                }
            }
            $post_data->query_string .= trim($temp_string, 'OR');
            $post_data->query_string .= ' ) ';
        }
        if ($post_data->mark_status && is_int($post_data->mark_status)) {
            ($post_data->query_string) ?
                $post_data->query_string .= " AND  isMark:$post_data->mark_status "
                : $post_data->query_string .= " isMark:$post_data->mark_status ";
        }

        if ($post_data->must_not) {
            if (is_array($post_data->must_not)) {
                foreach ($post_data->must_not as $must_val) {
                    $must_not_arr[] = ['exists' => ['field' => $must_val]];
                }

            }
            if (is_string($post_data->must_not)) {
                $must_not_arr[] = ['exists' => ['field' => $post_data->must_not]];
            }
            $query_arr['bool']['must_not'] = $must_not_arr;
        }
        if ($post_data->sku_is_null == 1) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND skuIsNull:1'
                : $post_data->query_string = '  skuIsNull:1 ';
        }
        if ($post_data->addressee_msg_null == 1) {
            ($post_data->query_string) ?
            $post_data->query_string .= ' AND NOT (addressUserCountryId:?* AND addressUserAddress1:?*)'
                : $post_data->query_string = '  NOT (addressUserCountryId:?* AND addressUserAddress1:?*) ';
        }

        if (!empty($post_data->is_count_kpi)) {
            $count_kip_where = [];
            $search_field = ['isOnSaleSnapshot', 'isEditedSnapshot', 'isOnSaleRecieverSnapshot'];
            foreach ($post_data->is_count_kpi as $v) {
                $count_kip_where[$search_field[$v]] = 0;
            }
            ($post_data->query_string) ? $post_data->query_string .= ' AND' : '';
            $post_data->query_string .= ' (  ';
            $temp_string = '';
            foreach ($count_kip_where as $key => $count_kpi_value) {
                $temp_string .= ' opOrderGuds.' . $key . ':' . $count_kpi_value . ' OR';
            }
            $post_data->query_string .= trim($temp_string, 'OR');
            $post_data->query_string .= ' ) ';
        }
        if (isset($post_data->is_remote_area) && $post_data->is_remote_area == 1) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND  isRemoteArea:1'
                : $post_data->query_string = '  isRemoteArea:1 ';
        }
        if (isset($post_data->is_apply_after_sale) && $post_data->is_apply_after_sale !== "") {
            if ($post_data->is_apply_after_sale) {
                ($post_data->query_string) ?
                    $post_data->query_string .= " AND isApplyAfterSale:$post_data->is_apply_after_sale"
                    : $post_data->query_string = " isApplyAfterSale:$post_data->is_apply_after_sale";
            } else {
                ($post_data->query_string) ?
                    $post_data->query_string .= ' AND  NOT isApplyAfterSale:1'
                    : $post_data->query_string = '  NOT isApplyAfterSale:1 ';
            }
        }
        if (!empty($post_data->logistics_abnormal_status)) {
            array_push($post_data->logistics_abnormal_status, 3);
            ($post_data->query_string) ? $post_data->query_string .= ' AND' : '';
            $post_data->query_string .= ' (  ';
            $temp_string = '';
            foreach ($post_data->logistics_abnormal_status as $value) {
                $temp_string .= ' tbOpOrderExtend.logisticsAbnormalStatus' . ':' . $value . ' OR';
            }
            $post_data->query_string .= trim($temp_string, 'OR');
            $post_data->query_string .= ' ) ';
        }

        // 增加是否为偏远地区 订单中心
        if (isset($post_data->is_remote_area_val) && preg_match("/^[0,1]$/",$post_data->is_remote_area_val)) {
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND  isRemoteArea:'.$post_data->is_remote_area_val
                : $post_data->query_string = '  isRemoteArea:'.$post_data->is_remote_area_val;
        }


        if (!empty($post_data->after_sale_type)) {
            ($post_data->query_string) ? $post_data->query_string .= " AND afterSaleType:$post_data->after_sale_type"
            : $post_data->query_string = " afterSaleType:$post_data->after_sale_type";
        }
        if (isset($post_data->third_deliver_status) && $post_data->third_deliver_status !== "") {
            ($post_data->query_string) ? $post_data->query_string .= " AND thirdDeliverStatus:$post_data->third_deliver_status"
                : $post_data->query_string = " thirdDeliverStatus:$post_data->third_deliver_status";
        }
        if (!empty($post_data->after_sale_status)) {
            $after_sale_status = (new OmsAfterSaleService())->getStatusMap($post_data->after_sale_status);
            $refund_where_str = $reissue_where_str = $return_where_str = " (";
            foreach ($after_sale_status as $item) {
                $refund_where_str .= "tbOpOrderExtend.refundStatusCd:{$item} OR ";
            }
            $refund_where_str = trim($refund_where_str, 'OR '). ')';

            foreach ($after_sale_status as $item) {
                $reissue_where_str .= "tbOpOrderExtend.reissueStatusCd:{$item} OR ";
            }
            $reissue_where_str = trim($reissue_where_str, 'OR '). ')';

            foreach ($after_sale_status as $item) {
                $return_where_str .= "tbOpOrderExtend.returnStatusCd:{$item} OR ";
            }
            $return_where_str = trim($return_where_str, 'OR '). ')';
            $com_str = '('.$refund_where_str. ' OR '. $return_where_str. ' OR '. $reissue_where_str. ')';
            ($post_data->query_string) ?
                $post_data->query_string .= " AND $com_str"
                : $post_data->query_string = "  $com_str";
        }
        //11350-待派单列表新增地址1作为筛选条件  addressUserAddress1 1:有值 2:没有值
        if (isset($post_data->addressUserAddress1) && !empty($post_data->addressUserAddress1)) {
            $addressUserAddress1 = ($post_data->addressUserAddress1 == 1) ? ' (addressUserAddress1:?*) ' : ' (NOT addressUserAddress1:?*) ';
            ($post_data->query_string) ?
                $post_data->query_string .= ' AND ' . $addressUserAddress1
                : $post_data->query_string = $addressUserAddress1;
        }
        if (!empty($post_data->not_after_sale_status)) {
            //  无售后退款 或者 售后退款状态为取消取消退款 或者存在运单号  其他排除派单页面
//            $query_arr['bool']['must'][] =   [
//                'bool' => ['should'=>[
//                    ['match'=>['tbOpOrderExtend.refundStatusCd'=>'N002800014']],
//                    ['bool'=>['must_not'=>['exists'=>['field'=>'tbOpOrderExtend.refundStatusCd']]]],
//                    ['bool'=>['must'=>[
//                        ['exists'=>['field'=>'ordPackage.trackingNumber']],
//                        ['match'=>['tbOpOrderExtend.refundStatusCd'=>'N002800009,N002800010,N002800011,N002800012,N002800013']]
//                    ]]],
//
//                ]]
//            ];
            $post_data->query_string.= " AND (tbOpOrderExtend.refundStatusCd:(N002800014 OR (NOT tbOpOrderExtend.refundStatusCd:?*)) 
            OR ((ordPackage.trackingNumber:?*) AND (tbOpOrderExtend.refundStatusCd:(N002800009, N002800010, N002800011, N002800012, N002800013))))";

        }
        if ($post_data->query_string) {
            $query_str['query_string']['query'] = $post_data->query_string;
            $query_arr['bool']['must'][] = $query_str;
        }
//        if (!empty($post_data->after_sale_status)) {
//            $query_arr['bool']['filter'] = [['terms'=>['tbOpOrderExtend.refundStatusCd'=>$post_data->after_sale_status]]];
//        }
        return $query_arr;

    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    private function joinEsOrderId($post_data)
    {
        return $post_data->plat_code . '_' . $post_data->thr_order_id;
    }


    /**
     * @param $post_data
     * @param $es_where_arr
     *
     * @return mixed
     */
    public function esAndInOr($post_data, $es_where_arr)
    {
        foreach ($es_where_arr as $key => $value) {
            if (is_array($post_data)) {
                $key_data = $post_data[$key];
            } else {
                $key_data = $post_data->$key;
            }
            if ($value && $value != '[]' && $key_data) {
                if(is_array($key_data)){
                    foreach ($key_data as $v) {
                        $k_v['bool']['should'][] = ['term' => [$value . '.keyword' => $v]];
                    }
                } elseif (is_string($key_data)) {
                    $k_v['bool']['should'][] = ['term' => [$value . '.keyword' => $key_data]];
                }
                if ($k_v) {
                    $query_arr['bool']['must'][] = $k_v;
                    unset($k_v);
                }
            }
        }
        return $query_arr;
    }

    /**
     * @param $count
     * @param $page_data
     *
     * @return mixed
     */
    private function joinPage($count, $page_data)
    {
        $page_arr['count'] = $count;
        if (empty($page_data)) {
            $page_arr['this_page'] = 1;
            $page_arr['page_count'] = 10;
        } else {
            $page_arr['this_page'] = $page_data['from'];
            $page_arr['page_count'] = $page_data['size'];
        }
        return $page_arr;
    }

    public function getOrderType($plat_cd)
    {
        if (in_array($plat_cd, $this->b2c_plat_arr)) return 'B2C';
        return 'B2B2C';
    }

    /**
     * @param $order
     * @param $plat_cd
     *
     * @return array
     */
    public function buildPatchData($order_id, $plat_cd)
    {
        $tables = '(tb_op_order AS t1,tb_op_order_guds AS t2,tb_ms_logistics_mode AS t6)';
        $wheres['t1.PLAT_CD'] = $plat_cd;
        $fields = 't1.ORDER_ID,t1.PARENT_ORDER_ID,t1.default_warehouse_logistics,t2.B5C_SKU_ID,t1.warehouse,t2.ITEM_COUNT,t1.b5c_logistics_cd,t1.logistic_cd,t1.logistic_model_id,t6.LOGISTICS_MODE,t4.TRACKING_NUMBER,t4.LOGISTIC_STATUS,t2.ITEM_PRICE,IFNULL(t3.WHOLE_STATUS_CD,t1.SEND_ORD_STATUS) AS send_status_cd';
        $res_db = M()
            ->table($tables)
            ->field($fields)
            ->where($wheres)
            ->where('t1.ORDER_ID = t2.ORDER_ID  AND  t1.PLAT_CD = t2.PLAT_CD AND t1.logistic_model_id = t6.ID AND (t1.PARENT_ORDER_ID = \'' . $order_id . '\' OR t1.ORDER_ID = \'' . $order_id . '\' )')
            ->join('LEFT JOIN tb_ms_ord AS t3 on  t1.ORDER_ID = t3.THIRD_ORDER_ID  AND t1.PLAT_CD = t3.PLAT_FORM')
            ->join('LEFT JOIN tb_ms_ord_package AS t4 on t1.ORDER_ID = t4.ORD_ID  AND t1.PLAT_CD = t4.plat_cd')
            ->order('t1.ORDER_ID desc')
            ->select();
        $res_db = SkuModel::getInfo($res_db, 'B5C_SKU_ID', ['spu_name']);
        $cd_arr = array_merge(
            B2bModel::get_code_cd('N00068'),
            B2bModel::get_code_cd('N00070'),
            B2bModel::get_code_cd('N00127'),
            B2bModel::get_code_cd('N00182'),
            B2bModel::get_code_cd('N00055')
        );
        $all_num = count($res_db);
        $f_num = M()->table('tb_op_order_guds')
            ->where('PLAT_CD = \'' . $plat_cd . '\' AND ORDER_ID = \'' . $order_id . '\'')
            ->count();
        foreach ($res_db as $value) {
            $res['suborder_id'] = $value['ORDER_ID'];
            $res['sku_id'] = $value['B5C_SKU_ID'];
            $res['PARENT_ORDER_ID'] = $value['PARENT_ORDER_ID'];
            $res['product_name'] = $value['spu_name'];
            $res['delivery_warehouse'] = $cd_arr[$value['warehouse']]['CD_VAL'];
            $res['the_number_issued'] = $value['ITEM_COUNT'];
            $res['logistics_company'] = $cd_arr[$value['logistic_cd']]['CD_VAL'];
            $res['shipping_methods'] = $value['LOGISTICS_MODE'];
            $res['waybill_number'] = $value['TRACKING_NUMBER'];
            $res['logistics_status'] = $cd_arr[$value['LOGISTIC_STATUS']]['CD_VAL'];
            $res['trading_price'] = $value['ITEM_PRICE'];
//            $send_status_cd = $value['WHOLE_STATUS_CD'] ?: $value['SEND_ORD_STATUS'];
            $res['send_status'] = $value['send_status_cd'] == 'N002080200' ? '待获取运单号' : $cd_arr[$value['send_status_cd']]['CD_VAL'];
            $res['default_warehouse_logistics'] = $value['default_warehouse_logistics'] ? $value['default_warehouse_logistics'] : '--'; // 三方物流信息
            if ($f_num == $all_num) {
                $res_arr[] = $res;
            } elseif ($order_id != $value['ORDER_ID']) {
                $res_arr[] = $res;
            }

        }

        return $res_arr;
    }

    private function getOrderGoodsMsg($order_id)
    {
        $Model = M();
        $res_arr = $Model->table('tb_op_order_guds')
            ->field('tb_ms_guds.GUDS_CNS_NM')
            ->where('ORDER_ID = ' . $order_id)
            ->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = SUBSTR(tb_op_order_guds.B5C_SKU_ID,1,8)')
            ->select();
        return $res_arr;
    }

    /**
     * @param $guds_arr
     * @param $guds_opt_arr
     * @param $ms_guds
     *
     * @return array
     */
    public function buildGuds($guds_arr, $guds_opt_arr, $ms_guds)
    {
        if ($guds_opt_arr) {
            $guds_opt_sku_arr = array_column($guds_opt_arr, 'gudsOptId');
            $sku_key_arr = array_flip($guds_opt_sku_arr);
            $ms_guds_key = array_column($ms_guds, 'gudsNm', 'gudsId');
            $Model = M();

            $all_b5c_sku = array_column($guds_arr, 'b5cSkuId');
            $where_main_guds['tb_ms_guds_opt.GUDS_OPT_ID'] = array('IN', $all_b5c_sku);
            // $lang_en = 'N000920200';
            // $where_main_guds['tb_ms_guds.LANGUAGE'] = $lang_en;
            $en_nm_res = M()->table('tb_ms_guds AS t1,tb_ms_guds AS t2,tb_ms_guds_opt')
                ->field('t1.MAIN_GUDS_ID,t1.GUDS_NM,tb_ms_guds_opt.GUDS_OPT_ID,tb_ms_guds_opt.GUDS_HS_CODE2,t2.GUDS_NM AS EN_GUDS_NM')
                ->where($where_main_guds)
                ->where('t1.GUDS_ID = tb_ms_guds_opt.GUDS_ID AND t2.MAIN_GUDS_ID = t1.MAIN_GUDS_ID AND t2.LANGUAGE = \'N000920200\'', null, true)
                ->select();
            $en_nm_res_key_val = array_column($en_nm_res, 'EN_GUDS_NM', 'GUDS_OPT_ID');
            $hs_code_res_key_val = array_column($en_nm_res, 'GUDS_HS_CODE2', 'GUDS_OPT_ID');

            foreach ($guds_arr as &$value) {
                $value['weight'] = $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptWeight'];
                $value['specification'] = $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptLength']
                    . '*' . $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptWidth']
                    . '*' . $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptHeight'];
                $value['barcode'] = $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptUpcId'];
                // $value['costPrice'] = $guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsOptOrgPrc'];
                $value['product_name'] = $ms_guds_key[$guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsId']];
                $value['product_name_en'] = $en_nm_res_key_val[$value['b5cSkuId']];
                $value['GUDS_HS_CODE2'] = $hs_code_res_key_val[$value['b5cSkuId']];
                $value['spu_currency'] = $Model->table('tb_ms_guds,tb_ms_cmn_cd')
                    ->where('tb_ms_guds.GUDS_ID = ' . substr($value['b5cSkuId'], 0, -2))
                    ->where('tb_ms_guds.STD_XCHR_KIND_CD = tb_ms_cmn_cd.CD', null, true)
                    ->getField('CD_VAL');
                $value['optNameValueStr'] = $this->gudsOptGet($guds_opt_arr[$sku_key_arr[$value['b5cSkuId']]]['gudsId'], $value['b5cSkuId']);
            }
            $guds_arr = B2bModel::goodsImgAdd($guds_arr, 'b5cSkuId');
        }
        return $guds_arr;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function toCountry($data)
    {
        foreach ($data as $value) {
            $join_country = BaseModel::join_country($value);
            if ($join_country) {
                foreach ($join_country as $val) {
                    $join_country_arr[] = $val;
                }
            } else {
                $join_country_arr[] = $value;
            }
        }
        return $join_country_arr;
    }

    /**
     * @param $es
     *
     * @return array
     */
    private function commodityJoin($guds, $sku_data)
    {
        return array_map(function ($v) use ($sku_data) {
            return $sku_data[$v['b5cSkuId']]['spu_name'];
        }, $guds);
    }

    /**
     * @param $mainGudsId
     *
     * @return array|bool
     */
    private function gudsOptGet($mainGudsId, $skuId)
    {
        $optNameValueStr = '';
        if (empty($mainGudsId) || empty($skuId)) {
            return $optNameValueStr;
        }
        if (!class_exists('GudsOptionModel')) {
            require_once APP_PATH . 'Lib/Model/Guds/GudsOptionModel.class.php';
        }
        if (!class_exists('OptionMapModel')) {
            require_once APP_PATH . 'Lib/Model/Guds/OptionMapModel.class.php';
        }
        $GudsOptionModel = new GudsOptionModel();
        $OptionMap = new OptionMapModel();
        $params['mainGudsId'] = $mainGudsId;
        $params['optionId'] = $skuId;

        $optionGroup = $GudsOptionModel->getGudsOptions($params);
        $skuMaps = $OptionMap->getOptionMaps($optionGroup);//SKU属性映射关系表。
        $allOptions = $OptionMap->getOptionByCodeMap($skuMaps, LANG_CODE);
        foreach ($optionGroup as $key => $opt) {
            $optMap = $GudsOptionModel->parseOptionMap($opt['GUDS_OPT_VAL_MPNG']);
            $optNameValueStr = '';
            foreach ($optMap as $nameCode => $valueCode) {
                $optNameValueStr .= explode('/', $allOptions[$nameCode]['ALL_VAL'])[1] . '：' . explode('/', $allOptions[$valueCode]['ALL_VAL'])[1] . '<BR/>';
            }
            $optNameValueStr = trim($optNameValueStr, '<BR/>');
        }
        return $optNameValueStr;
    }

    /**
     * @param $post_data
     * @param $search_key
     *
     * @return mixed
     */
    public static function orderAllSearch($search_val_get, $search_key)
    {
        switch ($search_key) {
            case 'thr_order_id':
                $es_key = 'orderId';
                break;
            case 'thr_order_no':
                $es_key = 'orderNo';
                break;
            case 'order_id':
                $es_key = 'b5cOrderNo';
                break;
            case 'orderNo':
                $es_key = 'orderNo';
                break;
            case 'b5cOrderNo':
                $es_key = 'b5cOrderNo';
                break;
            case 'zip_code':
                $es_key = 'addressUserPostCode';
                break;
            case 'receiver_email':
                $es_key = 'userEmail';
                break;
            case 'pay_method':
                $es_key = 'payMethod';
                break;
        }
        $temp_arr = self::str2arrSearch($search_val_get);
        $k_v['bool']['should'][] = ['terms' => [$es_key . '.keyword' => $temp_arr]];
        return $k_v;
    }

    /**
     * @param $es
     *
     * @return mixed
     */
    private function joinLogisticsSingleErrorMsgJsonData($es)
    {
        if (DataModel::isJson($es['logisticsSingleErrorMsg'])) {
            $es['logisticsSingleErrorMsg'] = json_decode($es['logisticsSingleErrorMsg'], true)['errMsg'];
        }
        return $es['logisticsSingleErrorMsg'];
    }

    /**获取商品名和属性
     *
     * @param      $es
     * @param      $language_type
     * @param null $map ['spu_name' => 'gudNm', 'opt' => 'gudOpt']
     */
    public static function insertSkuNmOpt(&$es, $language_type, $map = null)
    {
        $nmKey = $map['spu_name'] ?: 'spu_name';
        $optKey = $map['sku_opt'] ?: 'sku_opt';
        $productSkus = array_combine(array_column($es['productSkues'], 'skuId'), array_column($es['productSkues'], 'spuId'));
        foreach ($es['productDetails'] as $v) {
            $spus[$v['spuId'] . ''][$v['language']] = $v;
        }
        foreach ($es['optionVos'] as $v) {
            $sku_opts[$v['skuId'] . ''][$v['language']][] = $v['nameDetail'] . '：' . $v['valueDetail'];
        }
        foreach ($es['opOrderGuds'] as &$v) {
            $spu = $productSkus[$v['b5cSkuId']];
            $v[$nmKey] = ($spus[$spu][$language_type]['spuName'] ?: $spus[$spu]['N000920200']['spuName']);
            $v[$optKey] = implode('；', $sku_opts[$v['b5cSkuId']][$language_type]);
        }
        unset($v);
    }

    /**
     *  组装订单退款信息
     */
    public function buildRefundInfo($orderExtend)
    {
        $refund_info = array();
        if (!empty($orderExtend['refundCommAmount']) || !empty($orderExtend['refundTotalAmount'])){
            $orderExtend = CodeModel::autoCodeOneVal($orderExtend,['commCurrencyCd','totalCurrencyCd']);
            $refund_info[] = array(
                'refund_comm_amount' => !empty(($orderExtend['refundCommAmount'])) ? abs($orderExtend['refundCommAmount']) : "",
                'comm_currency_cd_val' => $orderExtend['commCurrencyCd'],
                'refund_total_amount' => !empty(($orderExtend['refundTotalAmount'])) ? abs($orderExtend['refundTotalAmount']) : "",
                'total_currency_cd_val' => $orderExtend['totalCurrencyCd'],
                'refund_reason' => $orderExtend['refundReason'],
                'syn_at' => $orderExtend['synAt'],
            );
        }
        return $refund_info;
    }

    /**
     * 获取es订单的销售额
     * @author Redbo He
     * @date 2021/1/6 16:23
     */
    public function getEsAmount($post_data)
    {
        $es_data = $this->getDataAggregation($this->formatListsQuery($post_data->data),"payTotalPriceDollar","sum");
        $aggregations = isset($es_data['aggregations']) ? $es_data['aggregations'] : [];
        $res = ($aggregations && isset($aggregations['sum_payTotalPriceDollar']) ) ? $aggregations['sum_payTotalPriceDollar']['value'] : 0;
        return number_format($res, 2,'.', '');
    }
}

