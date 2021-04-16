<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/2
 * Time: 10:46
 */
class PickingModel extends BaseModel
{
    const WAIT_PRINT_MORDER = 'N001820600'; // 待打印面单
    const WAIT_PICKING_ORD  = 'N001820500'; // 待拣货
    private $esClient;
    private $_prefix = 'JH';
    private $_limiter = '-';
    private $ordId;
    private $buyerMobile;
    private $sort;
    protected $trueTableName = 'tb_ms_ord_picking';
    public $searchFields = [];
    public $pickingNo = '';
    public $query = null;
    public $attributes = [
        'platForm'      => ['pattern' => 'match', 'aliasName' => 'msOrd.platForm'],//平台渠道
        'ordId'         => ['pattern' => 'terms', 'aliasName' => 'msOrd.ordId'],
        'addressUserCountryId' => ['pattern' => 'match', 'aliasName' => 'addressUserCountryId'],//国家
        'platCd'        => ['pattern' => 'match', 'aliasName' => 'platCd'],//店铺
        'warehouseCode' => ['pattern' => 'match', 'aliasName' => 'warehouseCode'],//仓库
        'b5cLogisticsCd'=> ['pattern' => 'match', 'aliasName' => 'opOrder.b5cLogisticsCd'],//物流公司
        'logisticModel' => ['pattern' => 'match', 'aliasName' => 'logisticModel'],//物流方式
        'saleTeamCd'    => ['pattern' => 'match', 'aliasName' => 'store.saleTeamCd'],//销售团队
    ];

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        $this->esClient = new ESClientModel();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        if ($value !== false and !empty($value)) {
            if ($this->attributes [$name]) {
                switch($this->attributes [$name]['pattern']) {
                    case 'match': // 单数匹配
                        $this->$name = [$this->attributes [$name]['pattern'] => ["{$this->attributes [$name]['aliasName']}" => $value]];
                        array_push($this->searchFields, $this->$name);
                        break;
                    case 'terms': // 数组型匹配
                        $this->$name = [$this->attributes [$name]['pattern'] => ["{$this->attributes [$name]['aliasName']}" => $value]];
                        array_push($this->searchFields, $this->$name);
                        break;
                    case 'range': // 范围型匹配
                        $this->$name = [$this->$name];
                        break;
                }
            }
        }
    }

    public $body;
    /**
     * 参数处理
     * @param array $query 搜索参数
     * @return array $request 组装后的请求参数
     */
    public function parseParams($query)
    {
        $query ['pageSize'] ? $size = $query ['pageSize']:$size = 20;
        $query ['pageIndex'] - 1 < 0 ? $pageIndex = 0:$pageIndex = $query ['pageIndex'] - 1;
        $conditions ['query']['bool']['must'] = $this->searchFields;
        $conditions ['query']['bool']['must'][] = ['exists' => ['field' => 'msOrd']];
        if ($conditions)
            $body = array_merge((array)$this->body, $conditions);
        $conf = BaseModel::esSearchConf('es_order');
        $request = [
            'index' => $conf ['index'],
            'type' => $conf ['type'],
            'body' => $body
        ];
        return $request;
    }

    public $total;
    /**
     * 拣货列表数据
     * @param array $params 查询条件
     * @return array $response ES 数据
     */
    public function getListData($params, $isPage = true, $model = 'excel')
    {
        $esModel = new EsSearchModel();
        $params ['pageIndex'] - 1 < 0 ? $pageIndex = 0:$pageIndex = $params ['pageIndex'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize']:$size = 20;
        $q = $esModel
            ->sort([$params ['sort'] => 'desc'])
            ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
            ->setDefaultNotNull(['not', [$params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
            ->setMissing(['and', ['childOrderId']])
            //->page($pageIndex, $size)
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            //->where(['orderNo' => ['and', $params ['orderNo']]])
            ->where(['gudsType' => ['and', $params ['packageType']]])
            ->where(['storeId' => ['and', $params ['platCd']]])
            ->where(['platCd' => ['and', $params ['platForm']]])
            ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_PICKING_ORD]])
            ->where(['surfaceWayGetCd' => ['and', $params['surface_method']]])  //追加获取面单筛选项
            ->where(['warehouse' => ['and', $params ['warehouseCode']]])
            ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
            ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
            ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
            ->where(['orderId' => ['like', $params ['orderId']]])
            ->where(['addressUserCountryId' => ['and', $params ['country']]])
            ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
            ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left'])?(string)strtotime($params ['search_time_left']).'000':'', 'lte' => strtotime($params ['search_time_right'])?(string)strtotime($params ['search_time_right']).'000':'']]]);
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
            if (in_array($params ['search_condition'],$search_array)) {
                $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
            }else{
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        }

        if (in_array(0, $params ['packageType']) or in_array(1, $params ['packageType'])) {
            $esModel->sort(['ordGudsOpt.gudsOptId' => 'desc']);
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
        } else {
            $q = $esModel
                ->sort([$params ['sort'] => 'desc'])
                ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
                ->setDefaultNotNull(['not', [$params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
                ->setMissing(['and', ['childOrderId']])
                ->page(0, $this->total)
                ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
                ->where(['gudsType' => ['and', $params ['packageType']]])
                ->where(['storeId' => ['and', $params ['platCd']]])
                ->where(['orderNo' => ['and', $params ['orderNo']]])
                ->where(['platCd' => ['and', $params ['platForm']]])
                ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_PICKING_ORD]])
                ->where(['warehouse' => ['and', $params ['warehouseCode']]])
                ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
                ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
                ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
                ->where(['orderId' => ['like', $params ['orderId']]])
                ->where(['addressUserCountryId' => ['and', $params ['country']]])
                ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
                ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left'])?(string)strtotime($params ['search_time_left']).'000':'', 'lte' => strtotime($params ['search_time_right'])?(string)strtotime($params ['search_time_right']).'000':'']]]);
                //->where([$params ['search_condition'] => ['like', $params ['search_value']]])
                //->getQuery();
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
                if (in_array($params ['search_condition'],$search_array)) {
                    $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
                }else{
                    $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
                }
            }
            $response = $this->esClient->search($q->getQuery());
            $response = $response ['hits']['hits'];
            $pageData = $this->excelListDataModel($response);
        }
        return $pageData;
    }

    public function listDataModel($response)
    {
        $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920200';
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $guds = $this->getSkuIds2($esData, $language_type);
            $pageData [] = [
                'platName'                 => $esData ['platName'],
                'storeName'                => $esData ['store']['storeName'],
                'b5cOrderNo'               => $esData ['b5cOrderNo'],
                'orderNo'                  => $esData ['orderNo'],
                'orderId'                  => $esData ['orderId'],
                'skuIds'                   => $guds['id'],
                'name'                     => $guds['name'],
                'gudsType'                 => $esData ['gudsTypeNm'],//包裹类型
                'saleTeam'                 => $esData ['store']['saleTeamCdNm'],
                'warehouseNm'              => $esData ['warehouseNm'],//下发仓库
                'expeCompany'              => $esData ['logisticCdNm'],//物流公司
                'logisticModel'            => $esData ['logisticModelIdNm'],//物流方式
                'surfaceWayGetCd'          => $esData ['surfaceWayGetCdNm'],//面单
                'trackingNumber'           => $esData ['ordPackage'][0]['trackingNumber'],
                'remarkMsg'                => $esData ['remarkMsg'],
                'msgCd1'                   => $esData ['msOrd']['msgCd1'],//拣货异常
                'platCd'                   => $esData ['store']['platCd'],
                'use_remarks'              => $esData ['shippingMsg'],
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'],
                "after_sale_type"          => $esData['afterSaleType'],
                'orderTime'                => $esData ['orderTime'],
                'payment_time'             => $esData ['orderPayTime'],
                "shipping_time"            => $esData['shippingTime'],
                "send_ord_time"            => $esData['sendOrdTime'],
                "sendout_time"             => $esData['msOrd'][0]['sendoutTime'],
                "buyer_user_id"              => $esData['tbOpOrderExtend']['buyerUserId'],
                "is_shopnc_order"          => strtolower($esData['store']['beanCd']) == 'shopnc' ? 1 : 0,
            ];
        }

        return $pageData;
    }

    public function excelListDataModel($response)
    {
        $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920200';
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $guds = $this->getSkuIds2($esData, $language_type);
            $pageData [] = [
                'platName'   => $esData ['platName'],
                'storeName'  => $esData ['store']['storeName'],
                'b5cOrderNo' => $esData ['b5cOrderNo'],
                'orderNo'    => $esData ['orderNo'],
                //'skuIds'     => $this->getSkuIds2($esData, $language_type),
                'name'       => $guds['name'],
                'skuIds'     => $guds['id'],
                'gudsType'   => $esData ['gudsTypeNm'],//包裹类型
                'saleTeam'   => $esData ['store']['saleTeamCdNm'],
                'warehouseNm' => $esData ['warehouseNm'],//下发仓库
                'orderTime'  => date('Y-m-d H:i:s', mb_substr($esData ['orderTime'], 0, -3)),
                'expeCompany'=> $esData ['logisticCdNm'],//物流公司
                'logisticModel' => $esData ['logisticModelIdNm'],//物流方式
                'surfaceWayGetCd' => $esData ['surfaceWayGetCdNm'],//面单
                'trackingNumber' => $esData ['ordPackage'][0]['trackingNumber'],
                'PACKING_NO'     => $esData ['packingNo'],
                'remarkMsg'     => $esData ['remarkMsg'],
                'msgCd1'     => $esData ['msOrd']['msgCd1'],//拣货异常
                'platCd' => $esData ['store']['platCd'],
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'],
                "buyer_user_id" => $esData['tbOpOrderExtend']['buyerUserId'],
            ];
        }

        return $pageData;
    }


    /**获取商品名数组
     * @param $es
     */
    private function getSkuIds2($es, $language_type)
    {
        $productSkus = array_combine(array_column($es['productSkues'], 'skuId'), array_column($es['productSkues'], 'spuId'));
        $spus = [];
        foreach ($es['productDetails'] as $v) {
            $spus[$v['spuId' . '']][$v['language']] = $v;
        }
        $guds = [];
        foreach ($es['opOrderGuds'] as $v) {
            $spu = $productSkus[$v['b5cSkuId']];
            //$guds[] = ($spus[$spu][$language_type]['spuName'] ?: $spus[$spu]['N000920200']['spuName']) . ' X ' . $v['itemCount'];
            $guds['name'][] = ($spus[$spu][$language_type]['spuName'] ?: $spus[$spu]['N000920200']['spuName']) . ' X ' . $v['itemCount'];
            $guds['id'][] = $v['b5cSkuId'] . ' X ' . $v['itemCount'];
        }
        return $guds;
    }

    /**
     * 设置排序
     * @param array $params 排序参数
     * @return array $sorts 排序值
     */
    public function setSort($params)
    {
        $sort = [
            'orderTime' => 'orderTime',//下单时间
            'payDttm' => 'msOrd.payDttm',//付款时间
        ];
        if ($params) {
            if (is_array($params)) {
                foreach ($params as $key => $value) {
                    $this->body ['sort'] = [
                        [$sort[$value] => 'desc'],
                    ];
                }
            } else {
                $this->body ['sort'][] = $params;
            }
        }
    }

    /**
     * 设置默认值
     */
    public function setDefault()
    {
        $this->searchFields [] = ['exists' => ['field' => 'msOrd']];
        $this->searchFields [] = ['exists' => ['field' => 'msOrd.ordId']];
    }

    /**
     * 翻页设置
     * @param int $fromSize 从第几条开始
     * @param int $size     每页展示多少条
     */
    public function setPage($fromSize = 0, $size = 20)
    {
        $this->body = [
            'from' => $fromSize,
            'size' => $size
        ];
    }

    /**
     * 异常信息返回
     * @param $code
     * @return mixed
     */
    public function msgMap($code)
    {
        $map = [
            1000 => L('未查询到订单数据'),
            2000 => L('成功'),
            3001 => L('写入拣货表失败'),
            3002 => L('写入拣货商品表失败'),
            3003 => L('无数据'),
            3004 => L('数据提取失败'),
            3005 => L('当前订单状态不能拣货'),
            3006 => L('更新订单状态失败'),
            3007 => L('无订单号，流程异常'),
            3008 => L('拣货单号缺失'),
            3009 => L('写入历史记录表失败')
        ];

        return $map [$code];
    }

    public $ordIds = null;
    public $isPirnt= false;
    private $saveLogOrder = null;
    /**
     * 数据处理
     * @param $data
     * @param bool $isCheck 是否打印
     * @param bool $isExternal 是否是外部请求，用于分拣单
     * @return array
     */
    public function parseData($data, $isCheck = false, $isExternal = false)
    {
        try{
            $gudsOpt = [];
            if ($data) {
                $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920200';
                // 根据 sku 将当前订单下的所有 sku 归类
                foreach ($data as $i => $o) {
                    OrderEsModel::insertSkuNmOpt($o ['_source'], $language_type);
                    if (!$this->checkStatus($o ['_source']['msOrd'][0]['wholeStatusCd'], $o ['_source']['msOrd'][0]['ordStatCd']) and $isExternal == false) {
                        $error [] = $o ['_source']['msOrd'][0]['ordId'];
                        continue;
                    } else {
                        $this->ordIds [] = $o ['_source']['msOrd'][0]['ordId'];
                        $this->saveLogOrder [$o ['_source']['msOrd'][0]['ordId']] = $o ['_source']['orderId'];
                    }
                    // 数量获取，根据到期日的不同,仓库，多个相同的 sku 需要单独拆分成多条数据
                    foreach ($o ['_source']['batchOrder'] as $key => $value) {
                        $deadLineDataForUse = '';
                        $res = explode('.', $value ['deadlineDateForUse']);
                        if (count($res) > 1) {
                            $deadLineDataForUse = $res [0];
                            $deadLineDataForUse = date('Y-m-d', strtotime($deadLineDataForUse));
                        }
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['occupyNum'][$deadLineDataForUse] += $value ['occupyNum'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['ordId'] = $value ['ordId'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['warehouseCode'] = $value ['deliveryWarehouseEtc'];
                        // 获取商品条形码
                        foreach ($o ['_source']['productSkues'] as $key2 => $value2) {
                            if ($value ['skuId'] == $value2['skuId']) {
                                $gudsOpt [$value ['ordId']][$value ['skuId']]['gudsOptUpcId'] = $value2 ['upcId'];
                            }
                        }
                        foreach ($o['_source']['opOrderGuds'] as $k2 => $v2) {
                            if ($value ['skuId'] == $v2['b5cSkuId']) {
                                $gudsOpt [$value ['ordId']][$value ['skuId']]['gudsNm']  = $v2 ['spu_name'];
                                $gudsOpt [$value ['ordId']][$value ['skuId']]['gudsOpt'] = $v2 ['sku_opt'];
                            }
                        }
                    }
                    // 货位数据取出
                    if ($o ['_source']['boxNameMap']) {
                        foreach ($o ['_source']['boxNameMap'] as $key => $value) {
                            foreach ($value as $k => $v) {
                                unset($o ['_source']['boxNameMap'][$key][$k]);
                                $o ['_source']['boxNameMap'][$key][$v ['sku']] = $v;
                            }
                        }
                    }
                    // 仓里、货位获取
                    foreach ($o ['_source']['batchOrder'] as $key => $value) {
                        if (isset($value ['skuId'])) {
                            // 仓库
                            $gudsOpt [$value ['ordId']][$value ['skuId']]['warehouseCode'] = $value['deliveryWarehouse'];
                            // 货位
                            $gudsOpt [$value ['ordId']][$value ['skuId']]['locationCode'] = $o ['_source']['boxNameMap'][$value ['deliveryWarehouse']][$value ['skuId']]['locationCode'];
                            $gudsOpt [$value ['ordId']][$value ['skuId']]['locationCodeBack'] = $o ['_source']['boxNameMap'][$value ['deliveryWarehouse']][$value ['skuId']]['locationCodeBack'];
                        }
                    }
                }
                if ($error) {
                    $this->responseStateCode = 3005;
                    $responseData = $error;
                    throw new Exception(L('error'));
                }

                if ($isExternal) {
                    $this->responseStateCode = 2000;
                    $response ['code'] = $this->responseStateCode;
                    $response ['info'] = L($this->msgMap($this->responseStateCode));
                    $response ['data'] = $gudsOpt;

                    return $response;
                }
                // 数据预处理,用于展示
                $this->preprocessData($gudsOpt);
                // 打印时
                if ($isCheck) {
                    $this->isPirnt = true;
                    if ($gudsOpt) {
                        $this->startTrans();
                        if (empty($this->pickingNo)) {
                            $this->responseStateCode = 3008;
                            throw new Exception(L('无拣货单号'));
                        }
                        // 拣货数据写入
                        $responseData = $this->writePicking();
                        // 更新订单状态
                        $responseData = $this->updateMsOrd();
                        $this->commit();
                        $this->responseStateCode = 2000;
                    } else {
                        $this->responseStateCode = 3004;
                    }
                } else {
                    $this->getPickingNo();
                    // 非打印时
                    if ($gudsOpt) {
                        $this->responseStateCode = 2000;
                        $responseData = $this->_previewData;
                    } else
                        $this->responseStateCode = 3004;
                }
            } else {
                $this->responseStateCode = 1000;
            }
        } catch(Exception $e) {
            $this->rollback();
        }
        if ($responseData) {
            $data = SkuModel::getInfo(array_values($responseData), 'skuId', ['spu_name', 'attributes'], ['spu_name' => 'gudsNm', 'attributes' => 'optVal']);
            $responseData = array_combine(array_keys($responseData), $data);
        }
        $r ['pageData'] = $responseData;
        $r ['pickingNo'] = $this->pickingNo;
        $r ['pickingTime'] = date('Y'.L('年').'m'.L('月').'d'. L('日') . ' ' .'H:i:s', time());

        $response ['code'] = $this->responseStateCode;
        $response ['info'] = L($this->msgMap($this->responseStateCode));
        $response ['data'] = $this->locationSort($r);

        return $response;
    }

    /**
     * 商品属性
     * @param $val
     * @return string
     */
    public function gudsOptsMerge($val)
    {
        $str = explode(';', $val);
        $opt = BaseModel::getGudsOpt();
        $shtml = '';
        $length = count($str);
        for ($i = 0; $i < $length; $i++) {
            if ($opt[$str[$i]]['OPT_CNS_NM'] and $opt[$str[$i]]['OPT_VAL_CNS_NM']) $shtml .= $opt[$str[$i]]['OPT_CNS_NM'] . ':' . $opt[$str[$i]]['OPT_VAL_CNS_NM'] . ' ';
            else $shtml .= $opt[$str[$i]]['OPT_VAL_CNS_NM'];
        }
        return $shtml;
    }

    /**
     * 货位排序
     * @param array $data 已经处理过的数据
     * @return array 排序后的数据
     */
    public function locationSort(&$data)
    {
        $tmp = null;
        foreach ($data ['pageData'] as $key => $value) {
            $tmp [$key] = $value ['locationCode'];
        }
        uasort($tmp, function ($b, $a) {
            return strnatcmp($a, $b);
        });
        foreach ($tmp as $key => $value) {
            $tmp [$key] = $data ['pageData'][$key];
        }
        $data ['pageData'] = $tmp;

        return $data;
    }

    /**
     * 可打印拣货单的订单状态
     */
    public function canPrintStatus()
    {
        return [
            'N000550400',
            'N000550500',
            'N000550600',
            'N000550800'
        ];
    }

    /**
     * 一键通过、万能码通过
     * @param array $data 订单数据
     * @param string $type  一键通过、万能码通过
     * @return array
     */
    public function oneKeyThrough($data, $type = null)
    {
        if ($data ['code'] != 2000) {
            $response ['code'] = $data ['code'];
            $response ['info'] = $data ['info'];
            $response ['data'] = $data ['data'];
        } else {
            $this->startTrans();
            try {
                $this->updateMsOrd($this->ordIds, true);
                $this->commit();
            } catch (Exception $e) {
                $this->rollback();
            }
            $response ['code'] = $this->responseStateCode;
            $response ['info'] = $this->parseException();
            $response ['data'] = $data ['data']['pageData'];
        }

        return $response;
    }

    public function parseException()
    {
        $info = null;
        if (is_array($this->responseStateCode)) {
            foreach ($this->responseStateCode as $key => $value) {
                $info .= '[' . $this->msgMap($value) . ']';
            }
        } else {
            $info = $this->msgMap($this->responseStateCode);
        }

        return $info;
    }

    /**
     * 更新 tb_ms_ord 订单拣货状态、拣货时间
     * @param $ordId 订单号
     * @return array
     * @throws Exception
     */
    public function updateMsOrd($ordId)
    {
        //拣货号写入
        $ret ['WHOLE_STATUS_CD'] = SELF::WAIT_PRINT_MORDER;
        $ret ['S_TIME1']         = date('Y-m-d H:i:s', time());
        $ret ['PICKING_NO']      = $this->pickingNo;
        $conditions ['tb_ms_ord.ORD_ID'] = ['in', $this->ordIds];
        $model = new Model();
        $model->table('tb_ms_ord')->where($conditions)->save($ret);
        //一键通过
        $oneKeyThrough = new OrderOneKeyThroughModel();
        $oneKeyThrough->requestType = false;
        $r = $oneKeyThrough->main(['ordId' => $this->ordIds]);
        return $r;
    }

    /**
     * 获取拣货单号
     */
    public function getPickingNo()
    {
        $inc = TbWmsNmIncrementModel::generateNo($this->_prefix . $this->_limiter . date('Ymd', time()));
        $this->pickingNo = $this->_prefix . $this->_limiter . date('Ymd', time()) . $this->_limiter . $inc;
    }

    public $responseStateCode;
    private $_previewData = null;
    private $_pickingGuds = null;

    /**
     * 数据预处理
     * @param $data ES数据
     */
    public function preprocessData($data)
    {
        $previewData = null;
        $pickingGuds = null;
        foreach ($data as $b5cOrderId => $skuInfo) {
            foreach ($skuInfo as $skuId => $info) {
                foreach ($info ['occupyNum'] as $deadLine => $num) {
                    unset($info ['occupyNum']);
                    unset($info ['ordId']);
                    $previewData [$skuId . '-' . $deadLine] = array_merge($info, ['nums' => $num + (int)$previewData [$skuId . '-' . $deadLine]['nums'], 'pickingNo' => $this->pickingNo, 'skuId' => $skuId, 'deadlineDateForUse' => $deadLine]);
                    $pickingGuds [$skuId . '-' . $deadLine][] = ['order_id' => $b5cOrderId, 'sku' => $skuId, 'num' => $num, 'picking_id' => null];
                }
            }
        }
        $this->_previewData = $previewData;
        $this->_pickingGuds = $pickingGuds;
    }

    /**
     * 拣货写入
     * @return bool 是否成功写入
     * @throws Exception
     */
    public function writePicking()
    {
        $previewData = $this->_previewData;
        $pickingGuds = $this->_pickingGuds;
        if ($previewData) {
            foreach ($previewData as $key => &$value) {
                $id = null;
                $m = null;
                $m = $this->create(BaseModel::hump($value), 1);
                $id = $this->add($m);
                if ($id) {
                    $value['id'] = $id;
                } else {
                    $this->responseStateCode = 3001;
                    throw new Exception(3001);
                }
                unset($value);
            }
            $gudsSaveData = null;
            foreach ($previewData as $key => $value) {
                foreach ($pickingGuds [$key] as $k => &$v) {
                    $v ['picking_id'] = $value ['id'];
                    $gudsSaveData [] = $v;
                    unset($v);
                }
            }
            $pickingGudsModel = new Model();
            if (!$pickingGudsModel->table('tb_ms_ord_picking_guds')->addAll($gudsSaveData)) {
                $this->responseStateCode = 3002;
                throw new Exception(3002);
            }
        } else {
            return false;
        }

        return $previewData;
    }

    /**
     * 状态检查
     * 1：待拣货状态
     * 2：待发货、待收货、已收货、交易成功
     * 3：没有拣货单号
     * 4：下发仓库配置了该拣货流程
     */
    public function checkStatus($status, $ordStatCd)
    {
        if ($status != SELF::WAIT_PICKING_ORD)
            return false;
        if (!in_array($ordStatCd, $this->canPrintStatus()))
            return false;

        return true;
    }
}