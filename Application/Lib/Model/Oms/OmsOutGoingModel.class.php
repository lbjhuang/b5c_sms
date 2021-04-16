<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/19
 * Time: 10:54
 */

class OmsOutGoingModel extends BaseModel
{
    const WAIT_OUTGOING = 'N001820800'; // 待出库
    const OUTGOING_COMPLETE = 'N001820900'; // 出库完成
    const CAN_WEIGHT_SHIPPING = 'N002060500';//仓库配置可称重发货
    const SURFACE_A_PICE = 'N002010100'; //面单获取方式为一件获取
    const SURFACE_DONOT_PICE = 'N002010200'; //无需面单
    const SURFACE_NE_PICE = 'N002010400'; //无需单号
    const SURFACE_GETED = 'N002080400'; //面单已获取
    const TRACKING_NOT_NULL = '';//无需面单是运单号不能为空
    const WAIT_SHIPPING = 'N000550400';
    const NEED_THIRD_VERIFICATION = 1; // 需要第三方验证
    const THIRD_SEND_GUDS_API_ADDR = '/op/third-deliver-goods'; //第三方发货接口地址

    public $total;
    public $esClient;
    public $responseStateCode;
    public $mode;//0 EXCEL发货，1批量发货，2称重发货
    public $autoSend = false;
    public $b5cOrderNo; //保存订单号与运单号的映射关系

    protected $autoCheckFields  =   false;

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        $this->esClient = new ESClientModel();
    }

    /**
     * 拣货列表数据
     *
     * @param array $params 查询条件
     * @param bool $isPage 是否分页，不分页则查询全部数据
     * @param string $model 模型名 excel|weightShipping excel为excel发货模型，weight为称重发货数据模型
     * @param bool $isShipping 是否发货
     *
     * @return array $response ES 数据
     */
    public function getListData($params, $isPage = true, $model = 'excel', $isShipping = false)
    {
        $esModel = new EsSearchModel();
        $params ['pageIndex'] - 1 < 0 ? $pageIndex = 0 : $pageIndex = $params ['pageIndex'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        if (isset($params ['weight']) and $params ['weight'] == 1) {
            $params ['weight'] = 1;
        } elseif (isset($params ['weight']) and $params ['weight'] == 2) {
            $params ['weight'] = 2;
        }
        $esModel
            ->sort([$params ['sort'] => 'desc'])
            ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '', $params ['weight'] == 2 ? 'msOrd.weighing' : '']])
            ->setDefaultNotNull(['not', [$params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
            ->setMissing(['and', ['childOrderId', $params ['weight'] == 1 ? 'msOrd.weighing' : '']])
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->where(['packageType' => ['and', $params ['gudsType']]])
            ->where(['storeId' => ['and', $params ['platCd']]])
            ->where(['platCd' => ['and', $params ['platForm']]])
            ->where(['bwcOrderStatus' => ['and', $params ['bwcOrderStatus']]])
            ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_OUTGOING]])
            ->where(['warehouse' => ['and', $params ['warehouseCode']]])
            ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
            ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
            ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
            ->where(['orderId' => ['and', $params ['orderId']]])
            ->where(['addressUserCountryId' => ['and', $params ['country']]])
            ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
            ->where(['thirdDeliverStatus' => ['and', $params ['third_deliver_status']]]) //需求11199待出库页面增加标记发货状态选项
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
        if ($isPage) {
            $q = $esModel->page($pageIndex, $size)->getQuery();
        } else {
            $q = $esModel->getQuery();
        }
        $response = $this->esClient->search($q);
        $this->total = $response ['hits']['total'];
        $response = $response ['hits']['hits'];
        if ($response and $isPage) {
            $pageData = $this->listDataModel($response);
        } elseif ($model == 'excel') {
            //由于 ES 未设置分页时，每次只能返回10条数据，所以需要获取最大行，再一次性导出
            $esModel
                ->sort([$params ['sort'] => 'desc'])
                ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
                ->setDefaultNotNull(['not', [$params ['remarkMsg'] ? 'remarkMsg' : '', $params ['msgCd1'] ? 'msgCd1' : '']])
                ->setMissing(['and', ['childOrderId']])
                ->page(0, $this->total)
                ->where(['b5cOrderNo' => ['and', $params ['ordId']]])
                ->where(['packageType' => ['and', $params ['gudsType']]])
                ->where(['storeId' => ['and', $params ['platCd']]])
                ->where(['platCd' => ['and', $params ['platForm']]])
                ->where(['bwcOrderStatus' => ['and', $params ['bwcOrderStatus']]])
                ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_OUTGOING]])
                ->where(['warehouse' => ['and', $params ['warehouseCode']]])
                ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
                ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
                ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
                ->where(['orderId' => ['and', $params ['orderId']]])
                ->where(['addressUserCountryId' => ['and', $params ['country']]])
                ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
                ->where(['thirdDeliverStatus' => ['and', $params ['thirdDeliverStatus']]]) //需求11199待出库页面增加标记发货状态选项
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
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
            $q = $esModel->getQuery();
            $response = $this->esClient->search($q);
            $response = $response ['hits']['hits'];
            $pageData = $this->excelExportDataModel($response);
        } elseif ($model == 'weightShipping') {
            $esModel
                ->sort([$params ['sort'] => 'desc'])
                //->page(0, count(array_unique(array_column($params, 'trackingNumber'))))
                ->page(0, 999)   //由于ES未设置分页 默认十条数据
                ->minimum()
                ->setMissing(['and', ['childOrderId']])
                ->where(['ordPackage.trackingNumber' => ['or', array_unique(array_column($params, 'trackingNumber'))]])
                ->where(['packingNo' => ['or', array_unique(array_column($params, 'trackingNumber'))]]);
            $q = $esModel->getQuery();
            $response = $this->esClient->search($q);
            $response = $response ['hits']['hits'];
            //根据选择的订单号、平台过滤ES订单
            $response = $this->filterShipping($response, $params);
            $pageData = $this->weightShipping($response, $params, $isShipping, $model);
        }elseif ($model == 'tracking'){
            $esModel
                ->sort([$params ['sort'] => 'desc'])
                //->page(0, count(array_unique(array_column($params, 'trackingNumber'))))
                ->page(0, 999)   //由于ES未设置分页 默认十条数据
                ->minimum()
                ->setMissing(['and', ['childOrderId']])
                ->where(['ordPackage.trackingNumber' => ['or', array_unique(array_column($params, 'trackingNumber'))]])
                ->where(['packingNo' => ['or', array_unique(array_column($params, 'trackingNumber'))]]);
            $q = $esModel->getQuery();
            $response = $this->esClient->search($q);
            $response = $response ['hits']['hits'];
            $pageData = $this->weightShipping($response, $params, $isShipping, $model);
        }
        return $pageData;
    }

    /**
     * 根据选择的订单号、平台过滤ES订单
     *
     * @param  array $response ES 数据
     * @param  array $params 请求 数据
     *
     * @return array $response 返回的数据
     */
    public function filterShipping($response, $params)
    {
        $data = [];
        foreach ($params as $key => $value) {
            $data[$value['platCd'] . '_' . $value['orderId']] = $value['platCd'] . '_' . $value['orderId'];
        }
        foreach ($response as $key => $value) {
            if (!isset($data[$value['_source']['platCd'] . '_' . $value['_source']['orderId']])) {
                unset($response[$key]);
            }
        }
        return $response;
    }

    /**
     * 列表数据模型
     *
     * @param  array $response ES 数据
     *
     * @return array $pageData 返回的数据
     */
    public function listDataModel($response)
    {
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $pageData [] = [
                'id'                       => $esData ['id'],
                'platName'                 => $esData ['platName'], // 平台名
                'storeName'                => $esData ['store']['storeName'],// 店铺名
                'bwcOrderStatusNm'         => $esData ['bwcOrderStatusNm'], // 订单状态
                'b5cOrderNo'               => $esData ['b5cOrderNo'], // 订单号
                'orderNo'                  => $esData ['orderNo'], // 订单号
                'orderId'                  => $esData ['orderId'], // 第三方订单号
                'pickingNo'                => $esData ['msOrd'][0]['pickingNo'], // 拣货号
                'skuIds'                   => $this->getSkuIds($esData ['ordGudsOpt']), // 商品编码
                'ordGudsOpt'               => $esData ['ordGudsOpt'], // 商品编码
                'warehouseNm'              => $esData ['warehouseNm'],//下发仓库
                'warehouse'                => $esData ['warehouse'],//仓库code
                'expeCompany'              => $esData ['logisticCdNm'],//物流公司
                'expeCompanyCd'            => $esData ['logisticCd'],//物流公司CD
                'logisticModel'            => $esData ['logisticModelIdNm'],//物流方式
                'logisticModelId'          => $esData ['logisticModelId'],//物流方式id
                'surfaceWayGetCdNm'        => $esData ['surfaceWayGetCdNm'],//面单
                'surfaceWayGetCd'          => $esData ['surfaceWayGetCd'],//面单
                'trackingNumber'           => $esData ['ordPackage'][0]['trackingNumber'], // 运单号
                'remarkMsg'                => $esData ['remarkMsg'], // 备注
                'use_remarks'              => $esData ['shippingMsg'],
                'msgCd1'                   => $esData ['msOrd']['msgCd1'], //核单异常
                'platCd'                   => $esData ['store']['platCd'],
                'findOrderJson'            => json_decode($esData ['findOrderJson'], true),
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'],
                'deliveryStatus'           => $esData ['store']['deliveryStatus'],
                'weight'                   => $esData ['msOrd'][0]['weighing'],
                "after_sale_type"          => $esData['afterSaleType'],
                "order_time"               => $esData["orderTime"],
                "payment_time"             => $esData["orderPayTime"],
                "shipping_time"            => $esData['shippingTime'],
                "send_ord_time"            => $esData['sendOrdTime'],
                "sendout_time"             => $esData['msOrd'][0]['sendoutTime'],
                "buyer_user_id"              => $esData['tbOpOrderExtend']['buyerUserId'],
                "is_shopnc_order"          => strtolower($esData['store']['beanCd']) == 'shopnc' ? 1 : 0,
                "third_deliver_status"     => $esData['thirdDeliverStatus'],
            ];
        }
        $pageData = SkuModel::joinPmsSkuInfo($pageData);
        return $pageData;
    }

    /**
     * EXCEL导出数据模型
     *
     * @param  array $response ES 数据
     *
     * @return array $pageData 返回的数据
     */
    public function excelExportDataModel($response)
    {
        $store_ids = [];
        $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920200';
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $store_ids[] = $esData['storeId'];
            OrderEsModel::insertSkuNmOpt($esData, $language_type);
            foreach ($esData ['opOrderGuds'] as $op => $gud) {
                $pageData [] = [
                    'platName' => $esData ['store']['platName'],//平台名称
                    'storeName' => $esData ['store']['storeName'],//店铺名称
                    'orderId' => $gud ['orderId'],//['name' => L('第三方订单ID'), 'field_name' => ''],
                    'orderNo' => $esData ['orderNo'],//['name' => L('第三方订单号'), 'field_name' => ''],
                    'bwcOrderStatusNm' => $esData ['bwcOrderStatusNm'],//['name' => L('订单状态'), 'field_name' => ''],
                    'b5cSkuId' => $gud ['b5cSkuId'],//['name' => L('SKU ID'), 'field_name' => ''],
                    'skuId' => $gud ['skuId'],//['name' => L('第三方SKU ID'), 'field_name' => ''],
                    'skuNm' => $gud ['spu_name'],//['name' => L('SKU名称'), 'field_name' => ''],
                    'gudsOptValMpngNm' => $gud ['sku_opt'],//['name' => L('SKU 属性'), 'field_name' => ''],
                    'payCurrency' => $esData ['payCurrency'],//['name' => L('币种'), 'field_name' => ''],
                    'itemPrice' => $gud ['itemPrice'],//['name' => L('商品单价'), 'field_name' => ''],
                    'itemCount' => $gud ['itemCount'],//['name' => L('商品数量'), 'field_name' => ''],
                    'payItemPrice' => $gud ['itemPrice'] * $gud ['itemCount'],//['name' => L('商品总价'), 'field_name' => ''],
                    'payVoucherAmount' => $gud ['payVoucherAmount'],//['name' => L('优惠金额'), 'field_name' => ''],
                    'payShipingPrice' => $esData ['payShipingPrice'],//['name' => L('运费'), 'field_name' => ''],
                    'payTotalPrice' => $esData ['payTotalPrice'],//['name' => L('支付总价'), 'field_name' => ''],
                    'orderTime' => $esData ['orderTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['orderTime'], 0, -3)) : '', //下单时间
                    'orderPayTime' => $esData ['orderPayTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['orderPayTime'], 0, -3)) : '', //付款时间
                    'shippingTime' => $esData ['shippingTime'] ? date('Y-m-d H:i:s', mb_substr($esData ['shippingTime'], 0, -3)) : '', //发货时间
                    'addressUserName' => $esData ['addressUserName'], //收货人姓名
                    'addressUserPhone' => $esData ['addressUserPhone'],//['name' => L('收货人手机'), 'field_name' => ''],
                    'receiverTel' => $esData ['receiverTel'],//['name' => L('收货人电话'), 'field_name' => ''],
                    'addressUserCountryIdNm' => $esData ['addressUserCountryIdNm'],//['name' => L('国家'), 'field_name' => ''],
                    'addressUserProvinces' => $esData ['addressUserProvinces'],//['name' => L('省'), 'field_name' => ''],
                    'addressUserCity' => $esData ['addressUserCity'],//['name' => L('市'), 'field_name' => ''],
                    'addressUserRegion' => OrderModel::needShowRegion($esData ['platCd']) ? $esData ['addressUserRegion'] : '',//['name' => L('区'), 'field_name' => ''],
                    'addressUserAddress1' => strip_tags($esData ['addressUserAddress1']),//['name' => L('具体地址'), 'field_name' => ''],
                    'addressUserPostCode' => $esData ['addressUserPostCode'],//['name' => L('邮编'), 'field_name' => ''],
                    'warehouseNm' => $esData['warehouseNm'],//['name' => L('仓库'), 'field_name' => ''],
                    'logisticCdNm' => $esData ['logisticCdNm'],//['name' => L('物流公司'), 'field_name' => ''],
                    'logisticModel' => $esData ['logisticModelIdNm'],//物流方式
                    'trackingNumber' => $esData ['ordPackage'][0]['trackingNumber'],//['name' => L('物流单号'), 'field_name' => ''],
                    'remarkMsg' => $esData ['remarkMsg'],//['name' => L('运营备注'), 'field_name' => '']
                    'shippingMsg' => $esData ['shippingMsg'],//['name' => L('用户备注'), 'field_name' => '']
                    'b5cOrderNo' => $esData ['b5cOrderNo'],//['name' => L('erp订单号'), 'field_name' => '']
                    'source' => $esData ['fileName'] ? 'Excel 导入' : '自动拉单',//['name' => L('来源'), 'field_name' => '']
                    'createUser' => $esData ['createUser'],//['name' => L('创建人'), 'field_name' => '']
                    'costPrice' => $gud ['costUsdPrice'],//['name' => L('成本'), 'field_name' => '']
                    'skuPurchasingCompany' => $gud ['skuPurchasingCompany'],//['name' => L('采购公司'), 'field_name' => '']
                    "payInstalmentServiceAmount" => $esData['payInstalmentServiceAmount'],//分期手续费
                    "payWrapperAmount" => $esData['payWrapperAmount'],//包装费
                    "paySettlePrice" => $esData['paySettlePrice'],//结算费
                    "paySettlePriceDollar" => $esData['paySettlePriceDollar'],//结算费（美元）
                    "tariff" => $esData['tariff'],//税费
                    "shippingTax" => $esData['shippingTax'],//运费税费-AMAZON
                    "promotionDiscountTax" => $esData['promotionDiscountTax'],//优惠费税费-AMAZON
                    "shippingDiscountTax" => $esData['shippingDiscountTax'],//运费折扣税费-AMAZON
                    "giftWrapTax" => $esData['giftWrapTax'],//包装费税费-AMAZON
                    "userEmail" => $esData["userEmail"],//收货人邮箱
                    'PACKING_NO' => $esData ['packingNo'],
                    'storeId' => $esData['storeId'],
                    "buyer_user_id" => $esData['tbOpOrderExtend']['buyerUserId'],
                ];
            }
        }
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

    public $b5cOrderNoWeight;
    private $preShipping;

    /**
     * 称重模型
     *
     * @param array $response ES data
     * @param array $params 运单号、重量、运费
     * @param bool $isShipping 是否发货
     * @param string $model weightShipping称重发货|tracking扫描
     *
     * @return array
     */
    public function weightShipping($response, $params, $isShipping, $model)
    {
        $params = array_column($params, 'weight', 'trackingNumber');
        $checkOrder = [];
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $trackingNumber = $esData ['ordPackage'][0]['trackingNumber'];
            $weight = isset($params [$trackingNumber]) ? $params [$trackingNumber] : $params [$esData ['packingNo']];
            $pageData [] = [
                'trackingNumber' => $trackingNumber, // 运单号
                'bwcOrderStatusNm' => $esData ['bwcOrderStatusNm'], // 订单状态
                'b5cOrderNo' => $esData ['b5cOrderNo'], // 订单号
                'orderId' => $esData ['orderId'], // 第三方订单号
                'orderNo' => $esData ['orderNo'], // 第三方订单号
                'logisticModel' => $esData ['logisticModelIdNm'],//物流方式
                'ordStatCdNm' => $esData ['msOrd'][0]['ordStatCdNm'],//状态
                'platCd' => $esData ['store']['platCd'],//平台Cd
                'platName' => $esData ['platName'],//平台名称
                'storeName' => $esData ['store']['storeName'],//店铺名称
                "patch_data" => (new OrderEsModel())->buildPatchData($esData["orderId"], $esData["platCd"]),
                'opOrderGuds' => $esData ['opOrderGuds'],//商品详情
                'weight' => $weight,
                'ordStatCd' => $esData ['bwcOrderStatus'],//$esData ['msOrd'][0]['ordStatCd'],
                'jobContent' => $esData ['wmsWarehouse']['jobContent'],
                'surfaceWayGetCd' => $esData ['surfaceWayGetCd'], // 面单获取方式
                'logisticsSingleStatuCd' => $esData ['logisticsSingleStatuCd'], //面单获取状态
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'], //面单获取状态
                'packingNo' => $esData ['packingNo'],
                'storeId' => $esData ['storeId'],
                'warehouse' => $esData ['warehouse'] //面单获取状态
            ];
            $this->b5cOrderNoWeight [$esData ['b5cOrderNo']] = $weight;
            if ($esData['storeId'] != 158) {
                $checkOrder[] = $esData['b5cOrderNo'];
            }
        }
        $response = $this->calculationShipping($pageData) ['data'];
        foreach ($response as $key => $value) {
            $response [$value ['data']['orderId']] = $value;
            unset($response [$key]);
        }
        $this->warehousesConf();
        $OutGoing = new OutGoingAction();
        //list($normal_orders, $error_orders) = $OutGoing->limitUserLogistics(array_column($pageData, 'b5cOrderNo'));
        //如果是店铺158 Aliexpress-载盈-Global-新消费电子专营店 不经过这个逻辑
        list($normal_orders, $error_orders) = $OutGoing->limitUserLogistics($checkOrder);
        //获取GP平台
        $plat_cd_data = CodeModel::getSiteCodeArr("N002620800");
        $gp_plat = $plat_cd_data ? array_column($plat_cd_data,'CD') : [];
        $pageData = array_map(function ($arr) use ($response, $normal_orders, $gp_plat, $model) {
            $arr ['ordStatCd'] = trim($arr ['ordStatCd']);
            //如果是店铺158 Aliexpress-载盈-Global-新消费电子专营店 不经过这个逻辑
            if ($arr ['storeId'] != '158') {
                if (!in_array($arr['b5cOrderNo'], $normal_orders)) {
                    $arr ['code'] = 3000;
                    $arr ['msg'] = '[' . L('与用户指定仓库物流不符') . ']';
                }
            }
            if ($arr ['surfaceWayGetCd'] == self::SURFACE_NE_PICE) {
                $arr ['code'] = 2000;
                $arr ['msg'] = '[' . L($arr ['logisticsSingleStatuCdNm']) . ']';
            }
            if ($arr ['surfaceWayGetCd'] == self::SURFACE_NE_PICE) {
                $arr ['code'] = 2000;
                $arr ['msg'] = '[' . L($arr ['logisticsSingleStatuCdNm']) . ']';
            }
            if ($arr ['surfaceWayGetCd'] == SELF::SURFACE_A_PICE and $arr ['logisticsSingleStatuCd'] == SELF::SURFACE_GETED) {
                $arr ['code'] = 2000;
                $arr ['msg'] = '[' . L($arr ['logisticsSingleStatuCdNm']) . ']';
            }
            if ($arr ['surfaceWayGetCd'] == SELF::SURFACE_DONOT_PICE and empty($arr ['trackingNumber']) and empty($arr ['packingNo'])) {
                $arr ['code'] = 3000;
                $arr ['msg'] = '[' . L('无需面单时，运单号和包装号必须存在一样') . ']';
            }
            //GP的订单必须含有运单号才能出库
            if (in_array($arr ['platCd'], $gp_plat) && empty($arr ['trackingNumber'])) {
                $arr ['code'] = 3000;
                $arr ['msg'] = '[' . L('Gshopper的订单必须有运单号才能出库！') . ']';
            }
            if (!in_array(SELF::CAN_WEIGHT_SHIPPING, $this->delWarehouses [$arr ['warehouse']]['jobContent'])) {
                $arr ['code'] = 3000;
                $arr ['msg'] = '[' . L('仓库不支持称重') . ']';
            }
            $isSendState = $model == 'tracking' ? $this->isTrackingState() : $this->isSendState();
            if (!in_array($arr ['ordStatCd'], $isSendState)) {
                $arr ['code'] = 3000;
                $arr ['msg'] ? $arr ['msg'] .= '[' . L('订单不满足发货状态') . ']' : $arr ['msg'] = '[' . L('订单不满足发货状态') . ']';
            }
            if ($arr ['code'] != 3000) {
                $arr ['code'] = $response [$arr ['orderId']]['code'];
                $arr ['msg'] = $response [$arr ['orderId']]['msg'];
                $arr ['freight'] = $response [$arr ['orderId']]['data']['freight'];
            }
            unset($arr ['ordStatCd']);
            unset($arr ['jobContent']);
            unset($arr ['surfaceWayGetCd']);
            unset($arr ['logisticsSingleStatuCd']);
            unset($arr ['logisticsSingleStatuCdNm']);
            return $arr;
        }, $pageData);
        $this->preShipping = $pageData;
        if ($isShipping) {
            foreach ($pageData as $key => $value) {
                $ordIds [] = $value ['b5cOrderNo'];
            }
            $r ['ordId'] = $ordIds;
            $response = $this->mulDeliver($r, $isShipping);
            return $response;
        }
        return $pageData;
    }

    public $delWarehouses;

    /**
     * 获取仓库配置信息
     */
    public function warehousesConf()
    {
        $model = new WarehouseModel();
        $fields = [
            'CD as cd',
            'job_content as jobContent'
        ];
        $warehouses = $model
            ->field($fields)
            ->where('is_show = 1')
            ->select();
        foreach ($warehouses as $key => $value) {
            $value ['jobContent'] = explode(':', $value ['jobContent']);
            $this->delWarehouses [$value ['cd']] = $value;
        }
    }

    /**
     * 运费计算
     *
     * @param array $requestData 请求的数据
     *
     * @return array
     */
    public function calculationShipping($requestData)
    {
        foreach ($requestData as $key => $value) {
            $data [] = [
                'orderId' => $value ['orderId'],
                'platCd' => $value ['platCd'],
                'weight' => $value ['weight'],
            ];
        }
        $r = [
            'processCode' => 'CALCULATE_LOGISTICS_FEE',
            'processId' => create_guid(),
            'data' => $data
        ];
        $response = $this->authWeight($r);
        return $response;
    }

    /**
     * 获得数量
     */
    public function getOccupyNum($data)
    {
        $num = 0;
        foreach ($data as $key => $value) {
            $num += $value ['occupyNum'];
        }
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
     * 异常信息返回
     *
     * @param $code
     *
     * @return mixed
     */
    public function msgMap($code)
    {
        $map = [
            2000 => L('成功'),
            3001 => L('写入拣货表失败'),
            3002 => L('写入拣货商品表失败'),
            3003 => L('无订单数据'),
            3004 => L('无可发货数据'),
            3005 => L('出库单子数据写入失败'),
            3006 => L('占用出库失败'),
            3007 => L('无订单号，流程异常'),
            3008 => L('拣货单号缺失'),
            3009 => L('写入历史记录表失败'),
            3010 => L('部分出库成功'),
            3011 => L('出库失败'),
            3012 => L('出库成功'),
            3020 => L('占用出库异常'),
            3021 => L('第三方发货验证接口异常'),
            3022 => L('第三方未发货')
        ];

        return $map [$code];
    }

    /**
     * 获得所有店铺的对接状态
     */
    public function getStoreDeliveryStatus()
    {
        $model = new Model();
        $ret = $model->table('tb_ms_store')
            ->getField('ID as id, DELIVERY_STATUS as deliveryStatus');

        return $ret;
    }

    public $rabbitQueueData;

    // 获取订单类型
    public function getOrderType($data, $source)
    {
        if ($source) return $source;
        $res = 'pull_order'; // 拉单
        if ($data) {
            $res = 'excel_import';
        }
        return $res;
    }

    /**
     * 订单数据获取
     *
     * @param array $ordId
     *
     * @return array
     */
    public function getOrderData($ordId)
    {
        $ordId = array_filter($ordId, function ($v) {
            return strlen($v) > 4;
        });
        $esModel = new EsSearchModel();
        $q = $esModel
            ->page(0, count($ordId))
            ->where(['b5cOrderNo' => ['and', $ordId]])
            ->setMissing(['and', ['childOrderId']])
            ->getQuery();
        $orders = $this->esClient->search($q) ['hits']['hits'];
        if ($orders) {
            $storeDeliveryStatus = $this->getStoreDeliveryStatus();
            foreach ($orders as $key => $value) {
                $value = $value ['_source'];
                $order [$value ['b5cOrderNo']]['warehouse'] = $value ['warehouse'];
                $order [$value ['b5cOrderNo']]['ordId'] = $value ['b5cOrderNo'];
                $order [$value ['b5cOrderNo']]['orderNo'] = $value ['orderNo'];
                $order [$value ['b5cOrderNo']]['thirdOrderId'] = $value ['orderId'];
                $order [$value ['b5cOrderNo']]['platCd'] = $value ['store']['platCd'];
                $order [$value ['b5cOrderNo']]['ordStatCd'] = $value ['msOrd'][0]['ordStatCd'];
                $order [$value ['b5cOrderNo']]['wholeStatusCd'] = $value ['msOrd'][0]['wholeStatusCd'];
                $order [$value ['b5cOrderNo']]['deliverStatus'] = $storeDeliveryStatus[$value ['storeId']];
                $order [$value ['b5cOrderNo']]['bwcOrderStatus'] = $value ['bwcOrderStatus'];
                $order [$value ['b5cOrderNo']]['orderId'] = $value ['orderId'];
                $order [$value ['b5cOrderNo']]['logisticsSingleStatuCd'] = $value ['logisticsSingleStatuCd'];
                $order [$value ['b5cOrderNo']]['parentOrderId'] = $value ['parentOrderId'];
                $order [$value ['b5cOrderNo']]['code'] = static::PROCESS_CODE_10000;
                $order [$value ['b5cOrderNo']]['msg'] = null;
                $order [$value ['b5cOrderNo']]['surfaceWayGetCd'] = $value ['surfaceWayGetCd'];
                $order [$value ['b5cOrderNo']]['order_source'] = $this->getOrderType($value['fileName'], $value['source']);
                $order [$value ['b5cOrderNo']]['storeId'] = $value['storeId'];
                $order [$value ['b5cOrderNo']]['packingNo'] = $value ['packingNo'];//包装号

                // 如果是excel导入，如果存在运单号则更新运单号
                if ($this->mode == 0) {
                    // 如果excel中填入了运单号，则进行运单号的覆盖
                    if (isset($this->b5cOrderNo [$value ['b5cOrderNo']])) {
                        $order [$value ['b5cOrderNo']]['trackingNumber'] = $this->b5cOrderNo [$value ['b5cOrderNo']];
                    } else {
                        $order [$value ['b5cOrderNo']]['trackingNumber'] = $value ['ordPackage'][0]['trackingNumber'];
                    }
                } else {
                    $order [$value ['b5cOrderNo']]['trackingNumber'] = $value ['ordPackage'][0]['trackingNumber'];
                }
                $this->rabbitQueueData [$value ['b5cOrderNo']]['expeCompany'] = $value ['ordPackage'][0]['expeCompany'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['expeCode'] = $value ['ordPackage'][0]['expeCode'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['trackingNumber'] = $value ['ordPackage'][0]['trackingNumber'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['updatedTime'] = $value ['ordPackage'][0]['updatedTime'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['orderId'] = $value ['orderId'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['platForm'] = $value ['msOrd'][0]['platForm'];
                $this->rabbitQueueData [$value ['b5cOrderNo']]['ordStatCd'] = $value ['msOrd'][0]['ordStatCd'];
                $r = null;

                foreach ($value ['batchOrder'] as $key => $batchOrder) {
                    $r [$batchOrder ['skuId']]['upcId'] = $value['productSkues'][$key]['upcId'];
                    $r [$batchOrder ['skuId']]['skuId'] = $batchOrder ['skuId'];
                    $r [$batchOrder ['skuId']]['gudsId'] = $batchOrder ['gudsId'];
                    $r [$batchOrder ['skuId']]['orderId'] = $batchOrder ['ordId'];
                    $r [$batchOrder ['skuId']]['type'] = 0;
                    $r [$batchOrder ['skuId']]['operatorId'] = $_SESSION['userId'];
                    $r [$batchOrder ['skuId']]['saleTeamCode'] = $value ['store']['saleTeamCd'];
                    $r [$batchOrder ['skuId']]['num'] += $batchOrder ['occupyNum'];
                    //$r ['deliveryWarehouse'] = $value ['warehouse'];
                    $order [$value ['b5cOrderNo']]['batch'] = $r;

                }
            }
        }

        return $order;
    }

    public $orders;

    /**
     * 获取订单
     *
     * @param $query
     */
    public function wholeOrders($query)
    {
        $this->orders = $this->getOrderData($query ['ordId']);
    }

    const PROCESS_CODE_10000 = 10000; //未处理
    const PROCESS_CODE_10001 = 10001; //主订单已发货成功，子订单默认第三方发货完成
    const PROCESS_CODE_10002 = 10002; //第三方发货成功
    const PROCESS_CODE_10003 = 10003; //父订单状态不满足发货状态
    const PROCESS_CODE_10004 = 10004; //第三方发货接口通信失败
    const PROCESS_CODE_10005 = 10005; //占用出库接口通信失败
    const PROCESS_CODE_10006 = 10006; //占用出库成功
    const PROCESS_CODE_10007 = 10007; //生成出库单失败
    const PROCESS_CODE_10008 = 10008; //生成出库单子数据失败
    const PROCESS_CODE_10009 = 10009; //生成出入库单成功
    const PROCESS_CODE_10010 = 10010; //入库单子数据写入成功
    const PROCESS_CODE_10011 = 10011; //入库单子数据写入失败
    const PROCESS_CODE_10012 = 10012; //占用出库失败
    const PROCESS_CODE_10020 = 10020; //无下发从仓库
    const PROCESS_CODE_10021 = 10021; //第三方发货失败
    const PROCESS_CODE_10022 = 10022; //无需面单时，运单号不能为空
    const PROCESS_CODE_10023 = 10023; //不满足生成出入库单条件，进入下一流程
    const PROCESS_CODE_10024 = 10024; //称重校验失败
    const PROCESS_CODE_10025 = 10025; //订单已关闭
    const PROCESS_CODE_10026 = 10026; //订单已取消
    const PROCESS_CODE_10027 = 10027; //订单待付款
    const PROCESS_CODE_10028 = 10028; //出库错误
    const PROCESS_CODE_10029 = 10029; //占用出库(关联交易批次)接口通信失败
    const PROCESS_CODE_10030 = 10030; //占用出库(关联交易批次)失败
    const PROCESS_CODE_10031 = 10031; //占用出库(关联交易批次)成功
    const PROCESS_CODE_10032 = 10032; //占用出库(关联交易批次)错误
    const PROCESS_CODE_10033 = 10033; //Gshopper的订单必须有运单号才能出库！
    const PROCESS_CODE_2000 = 2000;   //发货成功

    public function code()
    {
        return [
            10000 => L('未处理'),
            10001 => L('主订单已发货成功，子订单默认第三方发货完成'),
            10002 => L('第三方发货成功'),
            10003 => L('父订单状态不满足发货状态'),
            10004 => L('第三方发货接口通信失败'),
            10005 => L('占用出库接口通信失败'),
            10006 => L('占用出库成功'),
            10007 => L('生成出库单失败(已成功出掉库存)'),
            10008 => L('生成出库单子数据失败'),
            10009 => L('生成出入库单成功'),
            10010 => L('入库单子数据写入成功'),
            10011 => L('入库单子数据写入失败'),
            10012 => L('占用出库失败'),
            10020 => L('无下发从仓库'),
            10021 => L('第三方发货失败'),
            10022 => L('无需面单时，运单号不能为空'),
            10023 => L('不满足生成出入库单条件，进入下一流程'),
            10024 => L('称重校验失败'),
            10025 => L('订单已关闭'),
            10026 => L('订单已取消'),
            10027 => L('订单待付款'),
            10028 => L('出库错误'),
            10029 => L('占用出库(关联交易批次)接口通信失败'),
            10030 => L('占用出库(关联交易批次)失败'),
            10031 => L('占用出库(关联交易批次)成功'),
            10032 => L('占用出库(关联交易批次)错误'),
            2000 => L('发货成功'),
        ];
    }

    public $direct = false;

    /**
     * 批量发货
     *
     * @param array $query 出库数据
     * @param bool $isWeight 是否称重发货
     *
     * @return array
     */
    public function mulDeliver($query, $isWeight = false)
    {
        ini_set('max_execution_time', 540);
        $this->orders = null;
        //订单数据获取
        $this->wholeOrders($query);
        $error_orders = [];
        //$orders = array_column($this->orders, 'ordId');
        $orders = [];
        //如果是店铺158 Aliexpress-载盈-Global-新消费电子专营店 不经过这个逻辑
        foreach ($this->orders as $key => $value) {
            if ($value['storeId'] != '158') {
                $orders[] = $value['ordId'];
            }
        }
        $OutGoing = new OutGoingAction();
        $outbound_uri = '/index.php?g=oms&m=OutGoing&a=directOutgoing';
        if ($outbound_uri != $_SERVER['REQUEST_URI'] && !empty($orders)) {
            list($normal_orders, $error_orders) = $OutGoing->limitUserLogistics($orders);
            $this->orders = array_filter($this->orders, function ($value) use ($normal_orders) {
                if (in_array($value['ordId'], $normal_orders)) {
                    return true;
                }
            });
        }
        if ($this->orders) {
            //0：必要的参数过滤
            if ($this->autoSend == false) {
                $this->orders = array_map(function ($order) {
//                if (empty($order ['Warehouse']) or is_null($order ['Warehouse'])) {
//                    $order ['code'] = static::PROCESS_CODE_10020;
//                    $order ['msg']  = $this->code() [static::PROCESS_CODE_10020];
//                }
                    if ($order ['surfaceWayGetCd'] == SELF::SURFACE_DONOT_PICE and empty($order ['trackingNumber']) and $this->autoSend == false) {
                        /*$order ['code'] = static::PROCESS_CODE_10022;
                        $order ['msg'] = $this->code() [static::PROCESS_CODE_10022];*/
                    }
                    return $order;
                }, $this->orders);
            }
            //获取GP平台
            $plat_cd_data = CodeModel::getSiteCodeArr("N002620800");
            $gp_plat = $plat_cd_data ? array_column($plat_cd_data,'CD') : [];
            //出库校验订单状态是否为关闭，取消，待付款,争用订单锁
            $close_orders = $cancle_orders = $topay_orders = $d = [];
            $service = new Service();
            foreach ($this->orders as $k => $v) {

                if (!$service->isMayOperationOrders( array( 'ORDER_ID'=>$v['thirdOrderId'] ,'PLAT_CD'=>$v['platCd']),true,false)){
                    $d[] = ['code' => self::PROCESS_CODE_10000, 'ordId' => $v['ordId'], 'msg' => L('代销售订单禁止出库操作')];
                    unset($this->orders[$k]);
                    continue;
                }

                if (!RedisLock::lock($v['thirdOrderId'] . '_' . $v['platCd'], 60)) {//获取锁
                    $d[] = ['code' => self::PROCESS_CODE_10000, 'ordId' => $v['ordId'], 'msg' => L('订单锁获取失败')];
                    unset($this->orders[$k]);
                    continue;
                }
                //并发下es数据延迟解决
                $wholeStatusCd = M('ms_ord', 'tb_')->where(['ORD_ID' => $v['ordId']])->getField('WHOLE_STATUS_CD');
                if ($wholeStatusCd != 'N001820800') {//非待出库
                    $d[] = ['code' => self::PROCESS_CODE_10003, 'ordId' => $v['ordId'], 'msg' => L('派单状态错误')];
                    unset($this->orders[$k]);
                    continue;
                }
                //GP的订单必须含有运单号才能出库
                if (in_array($v['platCd'], $gp_plat) && empty($v['trackingNumber'])) {
                    $d[] = ['code' => self::PROCESS_CODE_10033, 'ordId' => $v['ordId'], 'msg' => L('Gshopper的订单必须有运单号才能出库！')];
                    unset($this->orders[$k]);
                    continue;
                }
                if ($v['bwcOrderStatus'] == 'N000550900') {//关闭
                    $v['b5cOrderNo'] = $v['ordId'];
                    $close_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10025, 'ordId' => $v['ordId'], 'msg' => L('订单已关闭')];
                    unset($this->orders[$k]);
                }
                if ($v['bwcOrderStatus'] == 'N000551000') {//关闭
                    $v['b5cOrderNo'] = $v['ordId'];
                    $cancle_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10026, 'ordId' => $v['ordId'], 'msg' => L('订单已取消')];
                    unset($this->orders[$k]);
                }
                if ($v['bwcOrderStatus'] == 'N000550300') {//待付款
                    $v['b5cOrderNo'] = $v['ordId'];
                    $topay_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10027, 'ordId' => $v['ordId'], 'msg' => L('订单待付款')];
                    unset($this->orders[$k]);
                }
            }
            //批量发货的订单都不满足条件 直接返回报错
            if (empty($this->orders)) {
                $response ['code'] = 2000;
                $response ['data'] = $d;
                $response ['info'] = 'success';
                RedisLock::unlock();
                return $response;
            }
            if ($query['preDone'] == 1) {
                $backModel = new OrderBackModel();
                $backModel->backPatchStatus($topay_orders);//库存占用释放&派单状态更新为待派单
                $backModel->backCancleStatus($close_orders);//库存占用释放&派单状态更新为订单取消
                $backModel->backCancleStatus($cancle_orders);//库存占用释放&派单状态更新为订单取消
            }
            /*$order [$value ['b5cOrderNo']]['deliverStatus']          = $storeDeliveryStatus[$value ['storeId']];
            $order [$value ['b5cOrderNo']]['bwcOrderStatus']*/

            //1：是否是称重发货出库，如果是，需要验证运费是否计算成功
            foreach ($this->orders as $key => $value) {
                static::record($value ['ordId'], "开始处理订单：[{$value ['ordId']}]--第三方订单号为：[{$value ['thirdOrderId']}]--日期：[" . date('Y-m-d H:i:s', time()) . "]");
                static::record($value ['ordId'], $this->autoSend ? L("脚本自动出库") : L("人工操作出库"));
            }
            if ($isWeight and $this->autoSend == false) {
                //通过预运费计算，获得运费计算失败的订单
                foreach ($this->preShipping as $key => $value) {
                    static::record($value ['b5cOrderNo'], '称重校验：' . $value ['msg']);
                    if ($value ['code'] != 2000) {
                        //关闭运费计算验证，默认运费计算失败的订单为未处理的订单
                        $this->orders [$value ['b5cOrderNo']]['code'] = static::PROCESS_CODE_10000;
                        //$this->orders [$value ['b5cOrderNo']]['code'] = static::PROCESS_CODE_10024;
                        //$this->orders [$value ['b5cOrderNo']]['msg'] = $value ['msg'];
                    }
                }
            }
            //2：是否已对接第三方发货，如果是，需将发货状态同步到第三方(批量处理订单时，可同时存在已对接与未对接第三方发货店铺的订单)
            foreach ($this->orders as $key => $value) {
                //这里只对未处理的并且已配置第三方对接的订单处理，如果在称重环节已处理code码不为1000

                if ($value ['code'] == static::PROCESS_CODE_10000 and $value ['deliverStatus'] == self::NEED_THIRD_VERIFICATION) {
                    if ($value['order_source'] !== 'excel_import' && $value['order_source'] !== 'Excel 导入') {  // 订单方式不是excel导入 #9743
                        if (substr($value['thirdOrderId'], 0, 4) != '0000' && substr($value['thirdOrderId'], 0, 2) != 'AS') { 
                        // 订单类型是“拉单”或者“GP订单”，且订单号前四位不是“0000”开头 或 “AS”开头的(这种是补发单不需要调接口)，才需调用第三方标记发货接口
                            static::record($value ['ordId'], '进入第三方处理列表');
                            $thirdRequestData [] = $value;
                        }
                    }
                }
            }
            
            if ($this->autoSend == false and $this->direct == false)
                $this->thirdDeliverGoods($thirdRequestData);
            //3：占用出库,9000为第三方发货接口返回成功的订单
            foreach ($this->orders as $key => $order) {
                if ($this->autoSend) {
                    static::record($order ['ordId'], '自动出库---跳过参数过滤验证');
                    static::record($order ['ordId'], '自动出库---跳过称重计算验证');
                    static::record($order ['ordId'], '自动出库---跳过第三方发货验证');
                }
                if ($order ['code'] == static::PROCESS_CODE_10001 or $order ['code'] == static::PROCESS_CODE_10002 or $order ['code'] == static::PROCESS_CODE_10000) {
                    static::record($order ['ordId'], '进入占用出库列表');
                    //出库接口调用参数
                    $occupyRequestData [] = [
                        'operatorId' => $_SESSION['userId'],
                        'orderId' => $order ['ordId'],
                        'relationType' => 'N002350300',
                        'billType' => 'N000950100',
                    ];

                    // 需要验证的批次信息
                    $parameCompany [] = [
                        'ordId' => $order['ordId'],
                        'orderNo' => $order['orderNo'],
                        'orderId' => $order['orderId'],
                        'storeId' => $order['storeId'],
                        'batch' => $order['batch']
                    ];
                } else {
                    static::record($order ['ordId'], '不满足占用出库条件，已被过滤。原因：' . $order ['msg']);
                    continue;
                }
            }

            // 根据占用批次归属公司和店铺对应【收入记录公司】比较，不一样的，则针对批次粒度自动发起关联交易
            $relTransRequestData = $this->verifyCompany($parameCompany);
            if (!empty($relTransRequestData)){
                //  调用接口  处理关联交易订单
                 $this->disposeRelTransOrder($relTransRequestData);
            }

            //4：调用出库接口
            $this->storageDelivery($occupyRequestData);

            //5：更新订单状态（含父订单的且父订单状态为待发货且当前子订单出库成功需要更新该父订单主状态）
            $this->updateOrderState();

            //B5C订单ID
            $b5cOrderNos = array_column($occupyRequestData, 'orderId');
            if($b5cOrderNos){
                (new OmsService())->saveAllOrderByB5cOrderNo($b5cOrderNos, __FUNCTION__);
            }

            //6：日志写入(1:流程日志（数据库） 2：订单处理日志（文件）)
            $this->orderLog();
            //7：数据返回
            static::saveLog();
            $response ['code'] = 2000;
            $response ['info'] = 'success';
            foreach ($this->orders as $key => $value) {
                $tmp ['code'] = $value ['code'];
                $tmp ['ordId'] = $value ['ordId'];
                $tmp ['msg'] = $value['msg'];
                $d [] = $tmp;
            }
            $response ['data'] = $d;
            $response['req'] = $occupyRequestData;
            RedisLock::unlock();
        } else {
            $response ['code'] = 2000;
            $response ['info'] = 'fail';
            $d = [];
            $error_orders_arr = array_column($error_orders, 'ordId');
            foreach ($query ['ordId'] as $key => $value) {
                if (!in_array($value, $normal_orders) && !in_array($value, $error_orders_arr)) {
                    $tmp ['code'] = 3000;
                    $tmp ['ordId'] = $value;
                    $tmp ['msg'] = L('未查询到订单');
                    $d [] = $tmp;
                }
            }
            $response ['data'] = $d;
        }
        $response['data'] = array_merge(array_values($response['data']), array_values($error_orders));
        return $response;
    }

    /**
     * 主订单未发货状态
     */
    public function notSendState()
    {
        return [
            'N000550400'
        ];
    }

    /**
     * 主订单已成功的状态
     */
    public function isSendState()
    {
        return [
            //'N000550400',
            'N000550500',
            'N000550600',
            'N000550800'
        ];
    }

    /**
     * 主订单扫描称重的状态
     */
    public function isTrackingState()
    {
        return [
            'N000550400',
            'N000550500',
            'N000550600',
            'N000550800'
        ];
    }

    /**
     * @var 父订单已经发过货的订单
     */
    public $doNotNeedRequestThirdApiOrders;

    /**
     * general 接口验证订单是否可以发货
     * 所有的订单，除了父订单已经是发货完成的子订单不需要更新主单状态以外，其它的都需要更新主单的主状态
     *
     * @param array $requestData
     *
     * @return null
     */
    public function thirdDeliverGoods($requestData)
    {
        //第三方发货接口地址
        $url = THIRD_DELIVER_GOODS . static::THIRD_SEND_GUDS_API_ADDR;
        //不能发货的订单
        $canNotSendOrder = null;
        //订单与顶三方订单的映射关系，该数组下的BWC订单需要更新主订单状态
        $thirdOrderIdMapOfOrdId = null;
        if ($requestData) {
            foreach ($requestData as $key => $value) {
                $thirdOrderIdMapOfOrdId [$value ['thirdOrderId']] = $value ['ordId'];
                static::record($value ['ordId'], '开始处理第三方发货订单');
                //是否有父订单号
                if (isset($value ['parentOrderId'])) {
                    //主订单的状态已为待发货后的状态时，不需要再走第三方发货接口
                    if (in_array($this->authParentOrderId($value ['parentOrderId']), $this->notSendState())) {
                        static::record($value ['ordId'], '该订单号的父订单未发货，开始第三方发货接口流程');
                        $thirdOrderIdMapOfOrdId [$value ['parentOrderId']] = $value ['ordId'];
                        $tmp ['orderId'] = $value ['thirdOrderId'];
                        $tmp ['platCd'] = $value ['platCd'];
                    } elseif (in_array($this->authParentOrderId($value ['parentOrderId']), $this->isSendState())) {
                        $this->doNotNeedRequestThirdApiOrders [] = $value ['ordId'];
                        static::record($value ['ordId'], '该订单号的父订单已发货成功，子订单默认第三方发货完成');
                        $this->orders [$value ['ordId']]['code'] = static::PROCESS_CODE_10001;
                        $this->orders [$value ['ordId']]['msg'] = L('主订单已发货成功，子订单默认第三方发货完成');
                    } else {
                        static::record($value ['ordId'], '该订单号的父订单状态为：[' . static::orderState() [$this->authParentOrderId($value ['parentOrderId'])] . '] 不能发货');
                        $canNotSendOrder [] = $value ['ordId'];
                        $this->orders [$value ['ordId']]['code'] = static::PROCESS_CODE_10003;
                        $this->orders [$value ['ordId']]['msg'] = L('该订单父订单状态为') . ' [' . static::orderState()[$this->authParentOrderId($value ['parentOrderId'])] . '] 不能发货';
                        static::record($value ['ordId'], "当前订单[{$value ['ordId']}]流程结束");
                    }
                } else {
                    if (in_array($value ['bwcOrderStatus'], $this->isSendState())) {
                        static::record($value ['ordId'], '无父订单号且订单状态为:' . static::orderState() [$value ['bwcOrderStatus']] . '无需走第三方发货');
                    } else {
                        static::record($value ['ordId'], '无父订单号，开始第三方发货接口流程');
                        //无父订单号，独立的子单
                        $tmp ['orderId'] = $value ['thirdOrderId'];
                        $tmp ['platCd'] = $value ['platCd'];
                    }
                }
                if ($tmp) {
                    $requestThirdData ['data']['orders'][] = $tmp;
                    $tmp = null;
                }
            }
            //获取第三方订单信息
            if ($requestThirdData) {
                $ret = json_decode(curl_get_json($url, json_encode($requestThirdData)), true);
                $this->setRequestData($requestThirdData);
                $this->_catchMe();
                if ($ret === false or $ret === null) {
                    foreach ($requestData as $key => $value) {
                        static::record($value ['ordId'], '第三方发货接口通信失败');
                        $this->orders [$value ['ordId']]['code'] = static::PROCESS_CODE_10004;
                        $this->orders [$value ['ordId']]['msg'] = L('第三方发货接口通信失败');
                    }
                } else {
                    $thirdOrdersInfo = $ret ['data']['orders'];
                    foreach ($thirdOrdersInfo as $key => $order) {
                        if ($order ['stat'] == true) {
                            static::record($thirdOrderIdMapOfOrdId [$order ['orderId']], '第三方发货成功');
                            $this->orders [$thirdOrderIdMapOfOrdId [$order ['orderId']]]['code'] = static::PROCESS_CODE_10002;
                            $this->orders [$thirdOrderIdMapOfOrdId [$order ['orderId']]]['msg'] = L('第三方发货成功');
                        } else {
                            static::record($thirdOrderIdMapOfOrdId [$order ['orderId']], $order ['orderMsg'] ? $order ['orderMsg'] : '第三方接口无任何信息返回');
                            $this->orders [$thirdOrderIdMapOfOrdId [$order ['orderId']]]['code'] = self::PROCESS_CODE_10021;
                            $this->orders [$thirdOrderIdMapOfOrdId [$order ['orderId']]]['msg'] = $order ['orderMsg'] ? $order ['orderMsg'] : '第三方接口无任何信息返回';
                        }
                    }
                }
            }
        }

        return;
    }

    public static $orderState;

    /**
     * 获取订单所有的状态
     *
     * @return mixed
     */
    public static function orderState()
    {
        if (static::$orderState)
            return static::$orderState;

        $model = new Model();
        static::$orderState = $model->table('tb_ms_cmn_cd')->where('CD like "N000550%"')->getField('CD, CD_VAL');

        return static::$orderState;
    }

    /**
     * @var 静态缓存主订单状态
     */
    public static $bwcOrderStatus;

    /**
     * 如果存在父订单号，需要查询父订单号是否是已发货
     *
     * @param string $parentOrderId 父订单号
     *
     * @return bool  是否已发货
     */
    public function authParentOrderId($parentOrderId)
    {
        if (static::$bwcOrderStatus [$parentOrderId])
            return static::$bwcOrderStatus [$parentOrderId];

        $model = new Model();
        $ret = $model->table('tb_op_order')->where(['ORDER_ID' => ['eq', $parentOrderId]])->getField('BWC_ORDER_STATUS');
        static::$bwcOrderStatus [$parentOrderId] = $ret;

        return $ret;
    }

    public $generateBaseStreamInfo;

    /**
     * 占用出库
     *
     * @param array $requestData 出库的数据
     *
     * @return null
     */
    public function storageDelivery($requestData)
    {
        if ($requestData) {
            foreach ($requestData as $k => $v) {
                if (!$v['orderId']) {
                    static::record($v ['orderId'], '占用出库校验失败');
                    $this->orders [$v ['orderId']]['code'] = static::PROCESS_CODE_10005;
                    $this->orders [$v ['orderId']]['msg'] = L('订单id不存在');
                    unset($requestData[$k]);
                }
                if ( $this->orders [$v ['orderId']]['code'] == static::PROCESS_CODE_10029
                || $this->orders [$v ['orderId']]['code'] == static::PROCESS_CODE_10030
                || $this->orders [$v ['orderId']]['code'] == static::PROCESS_CODE_10032){
                    static::record($v ['orderId'], '占用出库校验失败: '.$this->orders [$v ['orderId']]['msg']);
                    unset($requestData[$k]);
                }
            }
            $response = (new WmsModel())->b2cOutStorage(array_values($requestData));
            if ($response == false or $response == null) {
                foreach ($requestData as $key => $value) {
                    static::record($value ['orderId'], '占用出库接口通信失败');
                    $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10005;
                    $this->orders [$value ['orderId']]['msg'] = L('占用出库接口通信失败');
                }
                @SentinelModel::addAbnormal('占用出库接口通信失败','占用出库接口通信失败');
            } else {
                if ($response['code'] == 2000) {
                    $response = $response ['data'];
                    foreach ($response as $key => $value) {
                        static::record($value ['data'], $value ['msg']);
                        if ($value ['code'] == 2000) {
                            static::record($value ['data'], '占用出库成功');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_2000;
                            $this->orders [$value ['data']['orderId']]['msg'] = $this->code() [static::PROCESS_CODE_10006];
                        } else {
                            static::record($value ['data'], '占用出库失败');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_10012;
                            $this->orders [$value ['data']['orderId']]['msg'] = L('占用出库失败') . '：' . $value ['msg'];
                        }
                        unset($response [$key]);
                    }
                } else {
                    static::record($response, '占用出库4000错误');
                    foreach ($requestData as $key => $value) {
                        $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10005;
                        $this->orders [$value ['orderId']]['msg'] = L('占用出库错误') . '：' . $response['msg'];
                    }
                }
            }
        }

        return;
    }

    /**
     * GAPP平台
     *
     * @return array 返回需要推送到RABBIT中的平台CODE码
     */
    public function gAppPlat()
    {
        return [
            'N000834100',
            'N000834200',
            'N000834300',
            'N000831400'
        ];
    }

    /**
     * 内部处理异常状态映射值
     *
     * @param string 代码内部code
     *
     * @return string 返回定义的code
     */
    public function errorCodeMap($error)
    {
        $map = [
            0 => [
                static::PROCESS_CODE_10012 => 0,//Excel发货失败
                static::PROCESS_CODE_10004 => 4,//第三方状态未同步
                static::PROCESS_CODE_10023 => 1,//批量发货失败
                static::PROCESS_CODE_10024 => 1,//批量发货失败
                static::PROCESS_CODE_2000 => 5,//发货成功
            ],
            1 => [
                static::PROCESS_CODE_10012 => 1,//批量发货失败
                static::PROCESS_CODE_10004 => 4,//第三方状态未同步
                static::PROCESS_CODE_10023 => 1,//批量发货失败
                static::PROCESS_CODE_10024 => 1,//批量发货失败
                static::PROCESS_CODE_2000 => 5,//发货成功
            ],
            2 => [
                static::PROCESS_CODE_10012 => 2,//称重自动出库调用失败
                static::PROCESS_CODE_10004 => 4,//第三方状态未同步
                static::PROCESS_CODE_10023 => 1,//批量发货失败
                static::PROCESS_CODE_10024 => 1,//批量发货失败
                static::PROCESS_CODE_2000 => 5,//发货成功
            ],
            3 => [
                static::PROCESS_CODE_10012 => 2,//脚本自动出库调用失败
                static::PROCESS_CODE_10004 => 4,//第三方状态未同步
                static::PROCESS_CODE_10023 => 1,//批量发货失败
                static::PROCESS_CODE_10024 => 1,//批量发货失败
                static::PROCESS_CODE_2000 => 5,//发货成功
            ]
        ];

        return $map [$this->mode][$error];
    }

    public function getModeErrorCode($mode = null)
    {
        $map = [
            0 => 'N002091100',//Excel发货失败
            1 => 'N002091000',//批量发货失败
            2 => 'N002091200',//称重自动出库调用失败
            3 => 'N002091200',//脚本自动出库调用失败
            4 => 'N002090900', //第三方状态未同步
            5 => 'N2000'
        ];
        if (is_null($mode))
            return $map [$this->mode];
        else
            return $map [$mode];
    }

    public function codeMsg($code)
    {
        $map = [
            'N002091100' => L('Excel发货失败'),
            'N002091000' => L('批量发货失败'),
            'N002091200' => L('自动出库调用失败'),
            'N002090900' => L('第三方状态未同步'),
            'N2000' => L('发货成功')
        ];

        return $map [$code];
    }

    /**
     * 更新订单状态(订单状态更新流程不可控)
     */
    public function updateOrderState()
    {
        $freight = array_column($this->preShipping, 'freight', 'trackingNumber');
        $disposeData = [];
        foreach ($this->orders as $key => $order) {
            static::record($order ['ordId'], '开始更新订单状态');
            //已经处理成功的订单
            if ($order ['code'] == static::PROCESS_CODE_2000) {

                static::record($order ['ordId'], '订单已出库完成，进入订单更新列表');
                if (isset($order ['parentOrderId'])) {
                    $parentOrderId [] = $order ['parentOrderId'];
                }
                if ($order ['ordId']) {
                    $allOrderId [] = $order ['ordId'];
                }
                $tmp ['MSG_CD1'] = null;
                $tmp ['WHOLE_STATUS_CD'] = self::OUTGOING_COMPLETE;
                $tmp ['S_TIME4'] = date('Y-m-d H:i:s', time());
                $tmp ['ORD_STAT_CD'] = $order ['ordStatCd'] == 'N000550400' ? 'N000550500' : $order ['ordStatCd'];
                $tmp ['ORD_ID'] = $order ['ordId'];
                if ($this->b5cOrderNoWeight [$order ['ordId']]) {
                    $tmp ['weighing'] = $this->b5cOrderNoWeight [$order ['ordId']];
                }
                $tmp ['freight'] = $freight [$order ['trackingNumber']] ? $freight [$order ['trackingNumber']] : 0;
                $tmp ['freight'] = $freight [$order ['trackingNumber']] ? $freight [$order ['trackingNumber']] : 0;
                if ($this->mode == 0) {
                    $p ['ORD_ID'] = $order ['thirdOrderId'];
                    $p ['TRACKING_NUMBER'] = $this->b5cOrderNo [$order ['ordId']];
                    $package [] = $p;
                }
                $saveMsOrdData [] = $tmp;
                $tmp = $p = null;
            } else {
                static::record($order ['ordId'], $order ['msg'] ? $order ['msg'] : $this->code() [$order ['code']]);
                $r ['MSG_CD1'] = $this->getModeErrorCode($this->errorCodeMap($order ['code']));
                $r ['ORD_ID'] = $order['ordId'];
                if ($this->b5cOrderNoWeight [$order ['ordId']]) {
                    $r ['weighing'] = $this->b5cOrderNoWeight [$order ['ordId']];
                }
                $r ['freight'] = $freight [$order ['trackingNumber']] ? $freight [$order ['trackingNumber']] : 0;
                $brokenOrder [] = $r;
                $r = null;
                //  出库异常   处理关联交易订单记录
                if (isset($this->orders [$order['ordId']]['relTransNo'])
                    && !empty($this->orders [$order['ordId']]['relTransNo'])){
                    RelatedTransactionService::batchDelete($this->orders [$order['ordId']]['relTransNo'],1);
                    $disposeData [] = $this->orders [$order['ordId']]['relTransData'];
                }
            }
        }
        //  调用接口撤销关联交易订单相关数据
        if (!empty($disposeData)){
            $this->delRelTransOrder($disposeData);
        }


        //saveAll返回更新的条数，更新订单主状态与派单状态
        if ($saveMsOrdData and $allOrderId) {
            //更新msOrd订单
            $this->saveAll($saveMsOrdData, new TbMsOrdModel(), 'ORD_ID');
            $saveOpOrdData = array_map(function ($opOrder) {
                $tmp = null;
                $tmp ['B5C_ORDER_NO'] = $opOrder ['ORD_ID'];
                if ($opOrder ['freight']) {
                    $tmp ['amount_freight'] = $opOrder ['freight'];
                    $tmp ['freight_type'] = 'N000179200';
                }
                return $tmp;
            }, $saveMsOrdData);
            //更新opOrder表实重运费
            $this->saveAll($saveOpOrdData, new TbOpOrdModel(), 'B5C_ORDER_NO');
            $opOrderModel = new TbOpOrdModel();
            //查询父订单BWC单号
            $uniqueParentOrderId = array_unique($parentOrderId);
            if ($uniqueParentOrderId) {
                $parentOrder = $opOrderModel
                    ->field('BWC_ORDER_STATUS as bwcOrderStatus, B5C_ORDER_NO as b5cOrderNo')
                    ->where(['ORDER_ID' => ['in', $uniqueParentOrderId]])
                    ->select();
                if ($parentOrder) {
                    $parentOrderBwcOrdId = array_column($parentOrder, 'b5cOrderNo');
                    $allOrderId = array_merge($parentOrderBwcOrdId, $allOrderId);
                }
            }
            //更新op订单状态
            $ret = $opOrderModel
                ->field('BWC_ORDER_STATUS as bwcOrderStatus, B5C_ORDER_NO as b5cOrderNo')
                ->where(['B5C_ORDER_NO' => ['in', $allOrderId]])
                ->select();
            foreach ($ret as $key => $value) {
                #兼容线上待发货的订单状态   待发货 or  处理中
                if ($value ['bwcOrderStatus'] == static::WAIT_SEND_GUD || $value['bwcOrderStatus'] == static::DEAL_GUD) {
                    $needSaveBwcOrder [] = $value ['b5cOrderNo'];
                }
            }
            if ($needSaveBwcOrder) {
                $this->table('tb_op_order')
                    ->where(['B5C_ORDER_NO' => ['in', $needSaveBwcOrder]])
                    ->save(['BWC_ORDER_STATUS' => static::WAIT_REC_GUD]);
            }
            //更新tb_ms_ord_package
            if ($package) {
                // $this->saveAll($package, new TbMsOrdPackageModel(), 'ORD_ID');
            }

        }
        //保存失败的订单
        if ($brokenOrder) {
            $this->saveAll($brokenOrder, new TbMsOrdModel(), 'ORD_ID');
        }
        foreach ($this->orders as $key => $order) {
            static::record($order ['ordId'], '订单状态更新完成');
            if (in_array($order ['platCd'], $this->gAppPlat())) {
                static::record($order ['ordId'], '订单需要推送至GAPP');
                $gappReqeustData [] = $order ['ordId'];
            }
        }
        //Gshopper-JP,Gshopper-EN,Gshopper-CN,Gshopper-KR这四个店铺需要发送rabbitMq消息
        //if ($gappReqeustData)
        //    $this->rabbitQueue($gappReqeustData);
    }

    /**
     * 订单日志
     */
    public function orderLog()
    {
        $i = 1;
        foreach ($this->orders as $key => $order) {
            static::record($order ['ordId'], '记录操作日志');
            static::record($order ['ordId'], '流程结束', 2);
            $i++;
            $t = time() + $i + rand(0, 10);
            $r ['ORD_NO'] = $order ['thirdOrderId'];
            $r ['ORD_HIST_SEQ'] = $t;
            $r ['ORD_STAT_CD'] = $order ['bwcOrderStatus'];
            $r ['ORD_HIST_WRTR_EML'] = $this->autoSend ? 'AUTOMATIC DELIVERY SYSTEM' : $_SESSION['m_loginname'];
            $r ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', $t);
            $r ['updated_time'] = date('Y-m-d H:i:s', $t);
            if ($order ['code'] == static::PROCESS_CODE_2000)
                $r ['ORD_HIST_HIST_CONT'] = '发货成功';
            else
                $r ['ORD_HIST_HIST_CONT'] = '发货失败' . '-' . $order ['msg'] ? $order ['msg'] : $this->code()[$order ['code']];
            $r ['plat_cd'] = $order ['platCd'];
            $saveLogsData [] = $r;
        }

        if ($saveLogsData) {
            $model = new Model();
            $model->table('sms_ms_ord_hist')->addAll($saveLogsData);
        }
    }

    /**
     * 记录日志
     *
     * @param array $orders 第三方出库成功
     */
    public function writeToLog($orders)
    {
        $i = 1;
        foreach ($orders as $key => $value) {
            $i++;
            $t = time() + $i + rand(0, 10);
            $sr ['ORD_NO'] = $value ['thirdOrderId'];
            $sr ['ORD_HIST_SEQ'] = $t;
            $sr ['ORD_STAT_CD'] = $value ['bwcOrderStatus'];
            $sr ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $sr ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', $t);
            $sr ['updated_time'] = date('Y-m-d H:i:s', $t);
            if (isset($value ['thirdVerification']) and $value ['thirdVerification'] == false) {
                $sr ['ORD_HIST_HIST_CONT'] = L('发货失败') . '-' . $value ['thirdVerificationMsg'];
            }
            if (isset($value ['occupyVerification']) and $value ['occupyVerification'] == false) {
                $sr ['ORD_HIST_HIST_CONT'] = L('发货失败') . '-' . $value ['occupyVerificationMsg'];
            }
            if ($value ['thirdVerification'] and $value ['occupyVerification']) {
                $sr ['ORD_HIST_HIST_CONT'] = L('发货成功');
            }
            if ($value ['occupyVerification']) {
                $sr ['ORD_HIST_HIST_CONT'] = L('发货成功');
            }
            $sr ['plat_cd'] = $value ['platCd'];
            $saveLogsData [] = $sr;
        }
        if ($saveLogsData) {
            $model = new Model();
            $model->table('sms_ms_ord_hist')->addAll($saveLogsData);
        }

        return;
    }

    /**
     * 出入库单生成
     *
     * @param $warehouse
     * @param string $type
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function generateDeliverOrder($order, $type = 'N000950100')
    {
        $prefix = 'XSC';
        $inc = TbWmsNmIncrementModel::generateNo($prefix);
        $billModel = new TbWmsBillModel();
        // 出库单生成
        $bill ['bill_id'] = $prefix . date('ymd', time()) . $inc;
        $bill ['bill_type'] = $type;
        $bill ['warehouse_rule'] = 2;
        $bill ['warehouse_id'] = $order ['warehouse'];
        $bill ['zd_user'] = $_SESSION['userId'];
        $bill ['zd_date'] = date('Y-m-d H:i:s', time());
        $bill ['bill_date'] = date('Y-m-d H:i:s', time());
        $bill ['relation_type'] = 'N002350300';
        $bill ['link_bill_id'] = $order ['orderNo'];
        $bill ['link_b5c_no'] = $order ['ordId'];
        if ($id = $billModel->add($bill)) {
            return $id;
        } else {
            $this->responseStateCode = 3007;
        }

        return false;
    }

    public $streamInfo = null;

    /**
     * 出库单子数据
     *
     * @param $ordId
     * @param $billId
     *
     * @return bool|mixed|null
     */
    public function generateDeliverOrderStream($ordId, $billId)
    {
        $model = new Model();
        $stream = $this->generateBaseStreamInfo [$ordId];
        foreach ($stream as $key => $value) {
            foreach ($value as $k => $v) {
                $r ['bill_id'] = $billId;
                $r ['line_number'] = $v ['stream']['lineNumber'];
                $r ['goods_id'] = $v ['stream']['goodsId'];
                $r ['GSKU'] = $v ['stream']['gsku'];
                $r ['should_num'] = $v ['num'];
                $r ['send_num'] = $v ['num'];
                $r ['warehouse_id'] = $v ['stream']['warehouseId'];
                $r ['location_id'] = $v ['stream']['locationId'];
                $r ['batch'] = $v ['stream']['batch'];
                $r ['deadline_date_for_use'] = date('Y-m-d', substr($v ['stream'] ['deadlineDateForUse'], 0, 10));
                $r ['unit_price_usd'] = $v ['stream']['unitPriceUsd'];
                $r ['unit_price'] = $v ['stream']['unitPrice'];
                $r ['no_unit_price'] = $v ['stream']['noUnitPrice'];
                $r ['taxes'] = $v ['stream']['taxes'];
                $r ['unit_money'] = $v ['stream']['unitPrice'] * $v ['num'];
                $r ['no_unit_money'] = $v ['stream']['noUnitPrice'] * $v ['num'];
                $r ['duty'] = $v ['stream']['duty'];
                $r ['currency_id'] = $v ['stream']['currencyId'];
                $r ['give_status'] = $v ['stream']['giveStatus'];
                $r ['add_time'] = date('Y-m-d H:i:s', substr($v ['stream']['addTime'], 0, 10));
                $r ['digit'] = $v ['stream']['digit'];
                $r ['currency_time'] = date('Y-m-d H:i:s', substr($v ['stream']['currencyTime'], 0, 10));
                $r ['up_flag'] = $v ['stream']['upFlag'];
                $r ['GSKU_back'] = $v ['stream']['gskuBack'];
                $r ['outgoing_type'] = $v ['stream']['outgoingType'];
                $r ['reported_loss_reason'] = $v ['stream']['reportedLossReason'];
                $r ['batch'] = $v ['batchId'];
                $r ['tag'] = 0;
                $streams [] = $r;
                $r = $tmp = null;
            }
        }

        return $streams;

        $ret = $this->getStreamInfo($ordId);
        if ($ret) {
            foreach ($ret as $k => &$value) {
                $value ['send_num'] = $value ['real_occupy_num'];
                $value ['bill_id'] = $billId;
                $value ['no_unit_price'] = bcsub($value ['unit_price'], bcmul($value ['unit_price'], $value ['taxes'], 2), 2);//不含税单价
                $value ['unit_money'] = bcmul($value ['unit_price'], $value ['send_num'], 2);//含税金额
                $value ['no_unit_money'] = bcmul($value ['no_unit_price'], $value ['send_num'], 2);//去税金额
                $value ['unit_price_usd'] = bcmul($value ['no_unit_price'], $value ['send_num'], 2);//去税金额
                $value ['duty'] = bcsub($value ['unit_money'], $value ['no_unit_money'], 2);
                $value ['add_time'] = Date('Y-m-d H:i:s', time());//添加时间
                unset($value ['id']);
                unset($value ['real_occupy_num']);
                $this->streamInfo [] = $value;
            }
            return $ret;
        } else {
            return null;
        }
    }

    /**
     * 根据b5c订单号获取入库数据
     *
     * @param  string $ordId b5c订单号
     *
     * @return null|array $ret  结果集
     */
    public function getStreamInfo($ordId)
    {
        $ret = $this->table('tb_wms_stream t1, tb_wms_batch_order t2, tb_wms_batch t3')
            ->field('t1.*, t2.occupy_num as real_occupy_num')
            ->where('t2.batch_id = t3.id and t3.stream_id = t1.id')
            ->where(['t2.ORD_ID' => ['eq', $ordId]])
            ->select();

        return $ret;
    }

    /**
     * 处理出库失败的订单
     *
     * @param array $data b5c订单号
     * @param string $state 状态码
     *
     * @return bool 是否更新
     */
    public function parseDeliverFailOrder($data, $state = 'N002091000')
    {
        foreach ($data as $key => $value) {
            $tmp ['MSG_CD1'] = $state;
            $tmp ['ORD_ID'] = $value;
            if ($this->b5cOrderNoWeight [$value]) {
                $tmp ['weighing'] = $this->b5cOrderNoWeight [$value];
            }
            $saveMsOrdData [] = $tmp;
            $tmp = null;
        }
        if ($saveMsOrdData)
            $this->saveAll($saveMsOrdData, new TbMsOrdModel(), 'ORD_ID');
    }

    const WAIT_SEND_GUD = 'N000550400'; //待发货
    const DEAL_GUD = 'N000551004'; //处理中
    const WAIT_REC_GUD = 'N000550500'; //待收货

    /**
     * 处理出库成功的订单，清除异常信息
     *
     * @param array $ordIds b5c订单号
     *
     * @return bool 是否更新
     */
    public function parseDeliverSuccessOrder($ordIds)
    {
        foreach ($ordIds as $key => $value) {
            $tmp ['MSG_CD1'] = null;
            $tmp ['WHOLE_STATUS_CD'] = self::OUTGOING_COMPLETE;
            $tmp ['S_TIME4'] = date('Y-m-d H:i:s', time());
            $tmp ['ORD_STAT_CD'] = $this->rabbitQueueData [$value]['ordStatCd'] == 'N000550400' ? 'N000550500' : $this->rabbitQueueData [$value]['ordStatCd'];
            $tmp ['ORD_ID'] = $value;
            if ($this->b5cOrderNoWeight [$value]) {
                $tmp ['weighing'] = $this->b5cOrderNoWeight [$value];
            }
            $saveMsOrdData [] = $tmp;
            $tmp = null;
        }
        if ($saveMsOrdData)
            $this->saveAll($saveMsOrdData, new TbMsOrdModel(), 'ORD_ID');

        $ret = $this->table('tb_op_order')
            ->field('BWC_ORDER_STATUS, B5C_ORDER_NO')
            ->where(['B5C_ORDER_NO' => ['in', $ordIds]])
            ->select();
        foreach ($ret as $key => $value) {
            if ($value ['BWC_ORDER_STATUS'] == static::WAIT_SEND_GUD) {
                $needSaveBwcOrder [] = $value ['B5C_ORDER_NO'];
            }
        }
        if ($needSaveBwcOrder) {
            $this->table('tb_op_order')
                ->where(['B5C_ORDER_NO' => ['in', $needSaveBwcOrder]])
                ->save(['BWC_ORDER_STATUS' => static::WAIT_REC_GUD, 'SHIPPING_TIME' => date('Y-m-d H:i:s', time())]);
        }
        // 发送消息
        $this->rabbitQueue($ordIds);
    }

    /**
     * 批更新
     *
     * @param array $data 要更新保存的数据
     * @param object $model 对象模型
     * @param string $pk 更新所依据的字段
     *
     * @return string $sql 一条可执行的sql
     */
    public function saveAll($data, $model, $pk = '')
    {
        $sql = '';
        $lists = [];
        isset($pk) or $pk = $model->getPk();
        foreach ($data as $val) {
            foreach ($val as $key => $value) {
                if ($pk == $key) {
                    if (is_numeric($value))
                        $ids [] = '"' . $value . '"';
                    else
                        $ids [] = '"' . $value . '"';
                } else {
                    $lists [$key] .= sprintf("WHEN '%s' THEN '%s'", $val [$pk], $value);
                }
            }
        }

        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        if (empty($sql)) {
            return [];
        }
        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN ( %s )", $model->getTableName(), rtrim($sql, ','), $pk, implode(',', $ids));

        return $model->execute($sql);
    }

    /**
     * 占用出库
     *
     * @param array $requestData
     *
     * @return array
     */
    public function occupyDeliver($requestData)
    {
        $url = HOST_URL_API . '/batch/exportNew.json';
        $ret = curl_get_json($url, json_encode($requestData));
        $this->setResponseData(array_merge((array)(json_decode($ret, true)), ['response_from_api_addr' => $url]));
        return json_decode($ret, true);
    }

    /**
     * 面单获取
     */
    public function getSurfaceWay($query)
    {
        $request = null;
        $esModel = new EsSearchModel();
        $q = $esModel
            ->where(['b5cOrderNo' => ['and', $query]])
            //->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_OUTGOING]])
            ->getQuery();
        $response = $this->esClient->search($q)['hits']['hits'];
        foreach ($response as $key => $val) {
            if (isset($val ['thirdParentOrderId'])) {
                $tmp = null;
                $tmp ['thirdOrderId'] = null;
                $tmp ['childOrderId'] = $val ['_source']['msOrd'][0]['thirdOrderId'];
                $tmp ['platCd'] = $val ['_source']['store']['platCd'];
                $tmp ['b5cLogisticsCd'] = $val ['_source']['b5cLogisticsCd'];
                $tmp ['serviceCode'] = null;
                $tmp ['location'] = null;
                $request ['data']['orders'][] = $tmp;
            } else {
                $tmp = null;
                $tmp ['thirdOrderId'] = $val ['_source']['msOrd'][0]['thirdOrderId'];
                $tmp ['childOrderId'] = null;
                $tmp ['platCd'] = $val ['_source']['store']['platCd'];
                $tmp ['b5cLogisticsCd'] = $val ['_source']['b5cLogisticsCd'];
                $tmp ['serviceCode'] = null;
                $tmp ['location'] = null;
                $request ['data']['orders'][] = $tmp;
            }
        }
        $r = $this->surfaceInterface($request);
        $success = $fail = null;
        if ($r) {
            if ($r ['code'] == 2000) {
                foreach ($r ['data'] as $key => $val) {
                    $tmp = null;
                    if ($val ['code'] == 2000) {
                        $tmp ['template'] = $val ['template'];
                        $tmp ['orderId'] = $val ['orderId'];
                        $tmp ['trackingNo'] = $val ['trackingNo'];
                        $tmp ['msg'] = $val ['msg'];
                        $success [] = $tmp;
                    } else {
                        $tmp ['msg'] = $val ['msg'];
                        $fail [] = $tmp;
                    }
                }
                $response ['code'] = $r ['code'];
                $response ['msg'] = $r ['msg'];
                $response ['data'] = ['success' => $success, 'fail' => $fail];
            } else {
                $response ['code'] = $r ['code'];
                $response ['msg'] = $r ['msg'];
                $response ['data'] = $r ['data'];
            }
        } else {
            $response ['code'] = 3000;
            $response ['msg'] = L('获取面单失败');
            $response ['data'] = null;
        }

        return $response;
    }

    /**
     * general 面单接口
     *
     * @param array $requestData
     *
     * @return array
     */
    public function surfaceInterface($requestData)
    {
        $url = 'http://172.16.13.114/lgt/print-elec';
        $ret = curl_get_json($url, json_encode($requestData));
        $this->setResponseData(array_merge((array)(json_decode($ret, true)), ['response_from_api_addr' => $url]));
        return json_decode($ret, true);
    }

    /**
     * 称重数据验证
     */
    public function authWeight($requestData)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $ret = curl_get_json($url, json_encode($requestData));
        $this->setResponseData(array_merge((array)(json_decode($ret, true)), ['response_from_api_addr' => $url]));
        return json_decode($ret, true);
    }

    /**
     * Q 消息队列
     * 通知 GAPP 出库减库存
     *
     * @param array $ordId BWC_ORDER_NO
     */
    public function rabbitQueue($ordId)
    {
        $queue = new RabbitMqModel();
        $queue->exchangeName = 'gshopperExchange';
        $queue->routeKey = 'statusOrder';
        $queue->queueName = 'Q-B5C2GS-02';
        date_default_timezone_set('UTC');
        foreach ($ordId as $key => $value) {
            $order = null;
            $order = $this->rabbitQueue($value);
            $orders [$order ['platForm']][] = [
                "thirdOrdId" => $order ['orderId'],
                "ordId" => $value,
                "ordStatCd" => $order ['ordStatCd'] == 'N000550400' ? 'N000550500' : $order ['ordStatCd'],
                "msg" => "发货",
                "expeCompany" => $order ['expeCompany'],
                "trackingNumber" => $order ['trackingNumber'],
                "expeCode" => $order ['expeCode'],
                "expeDate" => date('Y-m-d\TH:i:s\Z', $order ['updatedTime']),
            ];
        }

        foreach ($orders as $platForm => $requestData) {
            $msg = [
                "platCode" => $platForm,
                "processId" => create_guid(),
                "data" => [
                    "orders" => $requestData
                ]
            ];
            $queue->setData($msg);
            $isok = $queue->submit();
            static::record($requestData ['ordId'], $isok ? '已成功推送至GAPP下的Mq队列' : '未成功推送至GAPP下的Mq队列');
        }
        date_default_timezone_set('PRC');
    }

    public static $log;

    public static function record($key, $msg, $mode = null)
    {
        if (!isset(static::$log [$key]) and $mode == null) {
            $mode = 1;
        } elseif ($mode == null) {
            $mode = 3;
        }
        if ($mode == 1)
            $msg = '┏----------' . $msg . '----------┓';
        elseif ($mode == 2)
            $msg = '┗----------' . $msg . '----------┛';
        else
            $msg = '┠' . $msg;

        static::$log [$key][] = $msg;
    }

    public static function saveLog()
    {
        $now = date('Ymd', time());
        $destination = '/opt/logs/logstash/' . $now . '_sendGuds.log';
//        foreach (static::$log as $key => $value) {
//            $now = date('Y-m-d H:i:s', time());
//            error_log($now.' '.get_client_ip().' '.$_SERVER['REQUEST_URI']."\r\n".print_r($value, true)."\r\n", 3, $destination , '');
//        }
        file_put_contents($destination, date('Y-m-d H:i:s', time()) . ' ' . get_client_ip() . ' - ' . $_SERVER ['SERVER_ADDR'] . ' ' . $_SERVER['REQUEST_URI'] . "\n" . print_r(self::$log, true), FILE_APPEND);
        static::$log = array();
    }

    /**
     *  获取店铺收入记录公司
     */
    private function getStoreIncomeCompany($storeIds)
    {
        $list = M('store','tb_ms_')->field('ID,income_company_cd')->where(['ID'=>['in',$storeIds]])->select();
        $data = array();
        if (!empty($list)){
            foreach ($list as $value){
                $data[$value['ID']] = $value;
            }
        }
        return $data;
    }

    /**
     *  获取批次所属公司
     */
    private function getBatchBelongCompany($ordIds){
        $list = M('batch_order','tb_wms_')
            ->field('tb_wms_batch.batch_code,CON_COMPANY_CD as our_company,tb_wms_batch_order.ORD_ID as ordId ,
             tb_wms_batch_order.SKU_ID as skuId ,occupy_num,tb_wms_stream.unit_price,tb_wms_stream.unit_price_origin,
             tb_wms_stream.currency_id,
             tb_wms_batch_order.id,tb_wms_batch_order.batch_id')
            ->join('LEFT JOIN tb_wms_batch ON tb_wms_batch_order.batch_id = tb_wms_batch.id')
            ->join('LEFT JOIN tb_wms_bill ON tb_wms_batch.bill_id = tb_wms_bill.id')
            ->join('LEFT JOIN tb_wms_stream ON tb_wms_stream.id = tb_wms_batch.stream_id')
            ->where(['tb_wms_batch_order.ORD_ID'=>['in',$ordIds]])
            ->select();
        $data = array();
        if (!empty($list)){
            foreach ($list as $value){
                $data[$value['ordId'].'_'.$value['skuId']] = $value;
            }
        }
        return $data;
    }

    /**
     * 验证  根据占用批次归属公司和店铺对应【收入记录公司】比较，不一样的，则针对批次粒度自动发起关联交易
     * @param $verifyData
     */
    public function verifyCompany($verifyData){
        $data = array();
        if (!empty($verifyData)){
            $trigger_type = 'N003220001';
            $batchBelongCompanyData = $this->getBatchBelongCompany(array_column($verifyData,'ordId'));  // 批次归属公司
            $storeIncomeCompanyData = $this->getStoreIncomeCompany(array_column($verifyData,'storeId'));  // 店铺收入记录公司
            foreach ($verifyData as $value){
                $is_succeed = true;  //  记录以订单为维度 处理全部SKU批次数据 （存在一个批次处理异常 改验证订单失败 出库异常）
                static::record($value['ordId'], '验证批次归属公司与收入记录公司是否一致');
                $tmpe = array();
                foreach ($value['batch'] as $k => $v){
                    if (isset($batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['our_company']) && !empty($batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['our_company'])
                        && isset($storeIncomeCompanyData[$value['storeId']]['income_company_cd']) && !empty($storeIncomeCompanyData[$value['storeId']]['income_company_cd'])
                        && $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['our_company'] != $storeIncomeCompanyData[$value['storeId']]['income_company_cd']){
                        static::record($value['ordId'], '自动发起关联交易SKU'.$v['skuId']);
                        $sku_quantity = $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['occupy_num'];
                        $unit_price_origin = $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['unit_price_origin'];
                        $unit_price_origin = !empty($unit_price_origin) ? $unit_price_origin : 0;
                        $rel_price = $sku_quantity * $unit_price_origin * 1.01  ;
                        $upcId = SkuModel::getUpcId($v['skuId']);
                        $saveData = array(
                            'rel_trans_no' => RelatedTransactionService::createPaymentNO(),
                            'ord_id' => $value['ordId'],
                            'order_no' =>  $value['orderNo'],
                            'order_id' =>  $value['orderId'],
                            'trigger_type' => $trigger_type,
                            'pur_company_cd' => $storeIncomeCompanyData[$value['storeId']]['income_company_cd'],
                            'sell_company_cd' => $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['our_company'],
                            'sku_id' => $v['skuId'],
                            'upc_id' => $upcId,
                            'sku_quantity' => $sku_quantity,
                            'rel_currency_cd' => $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['currency_id'],
                            'rel_price' => $rel_price,
                            'rel_time' => date('Y-m-d H:i:s'),
                            'operation_user' => DataModel::userNamePinyin(),
                            'create_by' => DataModel::userNamePinyin(),
                            'create_at' => date('Y-m-d H:i:s'),
                        );
                        $ret = M('rel_trans','tb_fin_')->add($saveData);
                        static::record($v['orderId'], '自动创建关联交易订单数据包:'.json_encode($saveData));
                        if (!$ret){
                            static::record($v['orderId'], '自动创建关联交易订单失败:'.json_encode($v));
                            static::record($v['orderId'], '自动创建关联交易订单失败:'.M()->_sql());
                            $is_succeed = false;
                            $this->orders [$v['orderId']]['code'] = static::PROCESS_CODE_10030;
                            $this->orders [$v['orderId']]['msg'] = "自动创建关联交易订单失败";
                            break;
                        }else{
                            $tmpe[] = array(
                                'relatOrderId' => $saveData['rel_trans_no'],
                                'batchId' =>  $batchBelongCompanyData[$v['orderId'].'_'.$v['skuId']]['batch_id'],
                            );
                        }
                    }
                }

                if (!empty($tmpe) && $is_succeed){
                    $data_tmp = array(
                        'orderId' => $value['ordId'],
                        'spTeamCd' => $saveData['sell_company_cd'],
                        'conCompanyCd' => $saveData['pur_company_cd'],
                        'oprateId' => DataModel::userId(),
                        'type' => $trigger_type,
                        'data' => $tmpe
                    );
                    // 记录该订单产生多条关联交易订单记录
                    $this->orders [$value['ordId']]['relTransNo'] = array_column($tmpe,'relatOrderId');
                    $this->orders [$value['ordId']]['relTransData'] = $data_tmp;
                    $data[] = $data_tmp;
                }
            }
        }
        return $data;
    }

    /**
     * 调用接口 处理关联交易订单
     * @param $rel_trans
     */
    public function disposeRelTransOrder($requestData){
        if ($requestData) {
            $response = (new WmsModel())->disposeRelTransOrder($requestData);
            if ($response == false or $response == null) {
                foreach ($requestData as $key => $value) {
                    static::record($value ['orderId'], '占用出库(关联交易批次)接口通信失败');
                    $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10029;
                    $this->orders [$value ['orderId']]['msg'] = L('占用出库(关联交易批次)接口通信失败');
                }
            } else {
                if ($response['code'] == 2000) {
                    $response = $response ['data'];
                    foreach ($response as $key => $value) {
                        static::record($value ['data'], $value ['msg']);
                        if ($value ['code'] == 2000) {
                            static::record($value ['data']['orderId'], '占用出库(关联交易批次)成功');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_10031;
                            $this->orders [$value ['data']['orderId']]['msg'] = $this->code() [static::PROCESS_CODE_10031];
                        } else {
                            static::record($value ['data']['orderId'], '占用出库(关联交易批次)失败');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_10030;
                            $this->orders [$value ['data']['orderId']]['msg'] = L('占用出库(关联交易批次)失败') . '：' . $value ['msg'];
                        }
                        unset($response [$key]);
                    }
                } else {
                    static::record($response, '占用出库(关联交易批次)4000错误');
                    foreach ($requestData as $key => $value) {
                        $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10032;
                        $this->orders [$value ['orderId']]['msg'] = L('占用出库(关联交易批次)错误') . '：' . $response['msg'];
                    }
                }
            }
        }
        return;
    }

    /**
     * 调用接口 处理关联交易订单(失败订单)
     * @param $rel_trans
     */
    public function delRelTransOrder($requestData){

        if ($requestData) {
            $response = (new WmsModel())->delRelTransOrder($requestData);
            if ($response == false or $response == null) {
                foreach ($requestData as $key => $value) {
                    static::record($value ['orderId'], '撤回占用出库(关联交易批次)接口通信失败');
                }
            } else {
                if ($response['code'] == 2000) {
                    $response = $response ['data'];
                    foreach ($response as $key => $value) {
                        static::record($value ['data'], $value ['msg']);
                        if ($value ['code'] != 2000) {
                            static::record($value ['data']['orderId'], '撤回占用出库(关联交易批次)成功');
                            $relTransNo = $this->orders [$value ['data']['orderId']]['relTransNo'];
                            RelatedTransactionService::batchDelete([$relTransNo],2);
                        }
                    }
                } else {
                    static::record($response, '撤回占用出库(关联交易批次)4000错误');
                    foreach ($requestData as $key => $value) {
                        static::record($value ['data']['orderId'], '撤回占用出库(关联交易批次)4000错误');
                    }
                }
            }
        }
        return;
    }

    //8017 B2C自动出库，内部关联交易单重新生成
    //从mulDeliver方法改造而来
    public function outgoingRepair($query, $isWeight = false)
    {
        ini_set('max_execution_time', 180);
        $this->orders = null;
        //订单数据获取
        $this->wholeOrders($query);
        $error_orders = [];
        $orders = array_column($this->orders, 'ordId');
        $OutGoing = new OutGoingAction();
        $outbound_uri = '/index.php?g=oms&m=OutGoing&a=directOutgoing';
        if ($outbound_uri != $_SERVER['REQUEST_URI'] && !empty($orders)) {
            list($normal_orders, $error_orders) = $OutGoing->limitUserLogistics($orders);
            $this->orders = array_filter($this->orders, function ($value) use ($normal_orders) {
                if (in_array($value['ordId'], $normal_orders)) {
                    return true;
                }
            });
        }
        if ($this->orders) {
            //出库校验订单状态是否为关闭，取消，待付款,争用订单锁
            $close_orders = $cancle_orders = $topay_orders = $d = [];
            foreach ($this->orders as $k => $v) {
                if (!RedisLock::lock($v['thirdOrderId'] . '_' . $v['platCd'], 60)) {//获取锁
                    $d[] = ['code' => self::PROCESS_CODE_10000, 'ordId' => $v['ordId'], 'msg' => L('订单锁获取失败')];
                    unset($this->orders[$k]);
                    continue;
                }
                if ($v['bwcOrderStatus'] == 'N000550900') {//关闭
                    $v['b5cOrderNo'] = $v['ordId'];
                    $close_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10025, 'ordId' => $v['ordId'], 'msg' => L('订单已关闭')];
                    unset($this->orders[$k]);
                }
                if ($v['bwcOrderStatus'] == 'N000551000') {//关闭
                    $v['b5cOrderNo'] = $v['ordId'];
                    $cancle_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10026, 'ordId' => $v['ordId'], 'msg' => L('订单已取消')];
                    unset($this->orders[$k]);
                }
                if ($v['bwcOrderStatus'] == 'N000550300') {//待付款
                    $v['b5cOrderNo'] = $v['ordId'];
                    $topay_orders[] = $v;
                    $d[] = ['code' => self::PROCESS_CODE_10027, 'ordId' => $v['ordId'], 'msg' => L('订单待付款')];
                    unset($this->orders[$k]);
                }
            }
//            if ($query['preDone'] == 1) {
//                $backModel = new OrderBackModel();
//                $backModel->backPatchStatus($topay_orders);//库存占用释放&派单状态更新为待派单
//                $backModel->backCancleStatus($close_orders);//库存占用释放&派单状态更新为订单取消
//                $backModel->backCancleStatus($cancle_orders);//库存占用释放&派单状态更新为订单取消
//            }
            /*$order [$value ['b5cOrderNo']]['deliverStatus']          = $storeDeliveryStatus[$value ['storeId']];
            $order [$value ['b5cOrderNo']]['bwcOrderStatus']*/

            //1：是否是称重发货出库，如果是，需要验证运费是否计算成功
            foreach ($this->orders as $key => $value) {
                static::record($value ['ordId'], "开始处理订单：[{$value ['ordId']}]--第三方订单号为：[{$value ['thirdOrderId']}]--日期：[" . date('Y-m-d H:i:s', time()) . "]");
                static::record($value ['ordId'], $this->autoSend ? L("脚本自动出库") : L("人工操作出库"));
            }
            if ($isWeight and $this->autoSend == false) {
                //通过预运费计算，获得运费计算失败的订单
                foreach ($this->preShipping as $key => $value) {
                    static::record($value ['b5cOrderNo'], '称重校验：' . $value ['msg']);
                    if ($value ['code'] != 2000) {
                        //关闭运费计算验证，默认运费计算失败的订单为未处理的订单
                        $this->orders [$value ['b5cOrderNo']]['code'] = static::PROCESS_CODE_10000;
                        //$this->orders [$value ['b5cOrderNo']]['code'] = static::PROCESS_CODE_10024;
                        //$this->orders [$value ['b5cOrderNo']]['msg'] = $value ['msg'];
                    }
                }
            }
            //2：是否已对接第三方发货，如果是，需将发货状态同步到第三方(批量处理订单时，可同时存在已对接与未对接第三方发货店铺的订单)
            foreach ($this->orders as $key => $value) {
                //这里只对未处理的并且已配置第三方对接的订单处理，如果在称重环节已处理code码不为1000

                if ($value ['code'] == static::PROCESS_CODE_10000 and $value ['deliverStatus'] == self::NEED_THIRD_VERIFICATION) {
                    if ($value['order_source'] !== 'excel_import' && $value['order_source'] !== 'Excel 导入') {  // 订单方式不是excel导入 #9743
                        if (substr($value['thirdOrderId'], 0, 4) != '0000' && substr($value['thirdOrderId'], 0, 2) != 'AS') {
                            // 订单类型是“拉单”或者“GP订单”，且订单号前四位不是“0000”开头 或 “AS”开头的(这种是补发单不需要调接口)，才需调用第三方标记发货接口
                            static::record($value ['ordId'], '进入第三方处理列表');
                            $thirdRequestData [] = $value;
                        }
                    }
                }
            }

//            if ($this->autoSend == false and $this->direct == false)
//                $this->thirdDeliverGoods($thirdRequestData);
            //3：占用出库,9000为第三方发货接口返回成功的订单
            foreach ($this->orders as $key => $order) {
                if ($this->autoSend) {
                    static::record($order ['ordId'], '自动出库---跳过参数过滤验证');
                    static::record($order ['ordId'], '自动出库---跳过称重计算验证');
                    static::record($order ['ordId'], '自动出库---跳过第三方发货验证');
                }
                if ($order ['code'] == static::PROCESS_CODE_10001 or $order ['code'] == static::PROCESS_CODE_10002 or $order ['code'] == static::PROCESS_CODE_10000) {
                    static::record($order ['ordId'], '进入占用出库列表');
                    //出库接口调用参数
                    $occupyRequestData [] = [
                        'operatorId' => $_SESSION['userId'],
                        'orderId' => $order ['ordId'],
                        'relationType' => 'N002350300',
                        'billType' => 'N000950100',
                    ];

                    // 需要验证的批次信息
                    $parameCompany [] = [
                        'ordId' => $order['ordId'],
                        'orderNo' => $order['orderNo'],
                        'orderId' => $order['orderId'],
                        'storeId' => $order['storeId'],
                        'batch' => $order['batch']
                    ];
                } else {
                    static::record($order ['ordId'], '不满足占用出库条件，已被过滤。原因：' . $order ['msg']);
                    continue;
                }
            }

            // 根据占用批次归属公司和店铺对应【收入记录公司】比较，不一样的，则针对批次粒度自动发起关联交易
            $relTransRequestData = $this->verifyCompany($parameCompany);
            if (!empty($relTransRequestData)){
                //  调用接口  处理关联交易订单
                $this->disposeRelTransOrderRepair($relTransRequestData);
            }

//            //4：调用出库接口
//            $this->storageDelivery($occupyRequestData);
//
//            //5：更新订单状态（含父订单的且父订单状态为待发货且当前子订单出库成功需要更新该父订单主状态）
//            $this->updateOrderState();
//
//            //B5C订单ID
//            $b5cOrderNos = array_column($occupyRequestData, 'orderId');
//            if($b5cOrderNos){
//                (new OmsService())->saveAllOrderByB5cOrderNo($b5cOrderNos, __FUNCTION__);
//            }

            //6：日志写入(1:流程日志（数据库） 2：订单处理日志（文件）)
            $this->orderLog();
            //7：数据返回
            static::saveLog();
            $response ['code'] = 2000;
            $response ['info'] = 'success';
            foreach ($this->orders as $key => $value) {
                $tmp ['code'] = $value ['code'];
                $tmp ['ordId'] = $value ['ordId'];
                $tmp ['msg'] = $value['msg'];
                $d [] = $tmp;
            }
            $response ['data'] = $d;
            $response['req'] = $occupyRequestData;
            RedisLock::unlock();
        } else {
            $response ['code'] = 2000;
            $response ['info'] = 'fail';
            $d = [];
            $error_orders_arr = array_column($error_orders, 'ordId');
            foreach ($query ['ordId'] as $key => $value) {
                if (!in_array($value, $normal_orders) && !in_array($value, $error_orders_arr)) {
                    $tmp ['code'] = 3000;
                    $tmp ['ordId'] = $value;
                    $tmp ['msg'] = L('未查询到订单');
                    $d [] = $tmp;
                }
            }
            $response ['data'] = $d;
        }
        $response['data'] = array_merge(array_values($response['data']), array_values($error_orders));
        return $response;
    }

    /**
     * 调用接口 处理关联交易订单
     * @param $rel_trans
     */
    public function disposeRelTransOrderRepair($requestData){
        if ($requestData) {
            $response = (new WmsModel())->disposeRelTransOrder($requestData);
            if ($response == false or $response == null) {
                foreach ($requestData as $key => $value) {
                    static::record($value ['orderId'], '占用出库(关联交易批次)接口通信失败');
                    $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10029;
                    $this->orders [$value ['orderId']]['msg'] = L('占用出库(关联交易批次)接口通信失败');
                }
            } else {
                if ($response['code'] == 2000) {
                    $response = $response ['data'];
                    foreach ($response as $key => $value) {
                        static::record($value ['data'], $value ['msg']);
                        if ($value ['code'] == 2000) {
                            $m = new Model();
                            //B2C出库stream.batch关联新生成的内部关联交易入库批次id
                            $rel_no = $requestData[0]['data'][0]['relatOrderId'];
                            $b5c_order_no = $requestData[0]['orderId'];
                            $batch_id = $m->table('tb_wms_bill bi')
                                ->join('tb_wms_batch wb on wb.bill_id = bi.id')
                                ->where(['bi.link_bill_id'=>$rel_no,'bill_type'=>'N000941004'])
                                ->getField('wb.id');
                            if (!empty($batch_id)) {
                                $bill_id = M('wms_bill','tb_')->where(['link_b5c_no'=>$b5c_order_no,'bill_type'=>'N000950100'])->getField('id');
                                //关联B2C出库批次为内部关联交易批次
                                $save_res = M('wms_stream','tb_')->where(['bill_id'=>$bill_id])->save(['batch'=>$batch_id]);
                                if (!$save_res) {
                                    Logs('save stream:'.M('wms_stream','tb_')->getLastSql(), __FUNCTION__, 'fm2');
                                }
                                //新生成的内部关联交易入库批次库存清零（B2C已出库）
                                $save_res2 = M('wms_batch','tb_')->where(['id'=>$batch_id])->save([
                                    'total_inventory'=>0,
                                    'occupied'=>0,
                                    'locking'=>0,
                                    'available_for_sale_num'=>0,
                                    'all_available_for_sale_num'=>0,
                                ]);
                                if (!$save_res2) {
                                    Logs('save batch:'.M('wms_batch','tb_')->getLastSql(), __FUNCTION__, 'fm2');
                                }
                                //占用改出库
                                $save_res3 = M('wms_batch_order','tb_')->where(['ORD_ID'=>$b5c_order_no])->save(['use_type'=>2]);
                                if (!$save_res3) {
                                    Logs('save batch order:'.M('wms_batch_order','tb_')->getLastSql(), __FUNCTION__, 'fm2');
                                }
                            } else {
                                Logs('get batch id :'.$m->getLastSql(), __FUNCTION__, 'fm2');
                            }

                            static::record($value ['data']['orderId'], '占用出库(关联交易批次)成功');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_10031;
                            $this->orders [$value ['data']['orderId']]['msg'] = $this->code() [static::PROCESS_CODE_10031];
                        } else {
                            static::record($value ['data']['orderId'], '占用出库(关联交易批次)失败');
                            $this->orders [$value ['data']['orderId']]['code'] = static::PROCESS_CODE_10030;
                            $this->orders [$value ['data']['orderId']]['msg'] = L('占用出库(关联交易批次)失败') . '：' . $value ['msg'];
                        }
                        unset($response [$key]);
                    }
                } else {
                    static::record($response, '占用出库(关联交易批次)4000错误');
                    foreach ($requestData as $key => $value) {
                        $this->orders [$value ['orderId']]['code'] = static::PROCESS_CODE_10032;
                        $this->orders [$value ['orderId']]['msg'] = L('占用出库(关联交易批次)错误') . '：' . $response['msg'];
                    }
                }
            }
        }
        return;
    }

}