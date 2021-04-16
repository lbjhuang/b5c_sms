<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/14
 * Time: 15:19
 */

class CheckOrderModel extends BaseModel
{

    const WAIT_CHECK_ORD = 'N001820700'; // 带核单
    const WAIT_OUTGO_ORD = 'N001820800'; // 待出库
    public $total;
    public $esClient;
    public $responseStateCode;

    protected $autoCheckFields = false;

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        $this->esClient = new ESClientModel();
    }

    /**
     * 异常信息返回
     * @param $code
     * @return mixed
     */
    public function msgMap($code)
    {
        $map = [
            2000 => L('成功'),
            3001 => L('写入拣货表失败'),
            3002 => L('写入拣货商品表失败'),
            3003 => L('无数据'),
            3004 => L('数据提取失败'),
            3005 => L('当前订单状态不能核单'),
            3006 => L('更新订单状态失败'),
            3007 => L('无订单号，流程异常'),
            3008 => L('拣货单号缺失'),
            3009 => L('SKU不存在'),
            3010 => L('SKU扫描数量不一致')
        ];

        return $map [$code];
    }

    /**
     * 拣货列表数据
     * @param array $params 查询条件
     * @param bool  $isPage 是否是分页
     * @param bool  $isExportExcel 是否是导出数据
     * @return array $response ES 数据
     */
    public function getListData($params, $isPage = true, $isExportExcel = false)
    {
        $esModel = new EsSearchModel();
        $params ['pageIndex'] - 1 < 0 ? $pageIndex = 0:$pageIndex = $params ['pageIndex'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize']:$size = 20;
        $q = $esModel
            ->sort([$params ['sort'] => 'desc'])
            ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
            ->setDefaultNotNull(['not', [$params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
            ->setMissing(['and', ['childOrderId']])
            ->page($pageIndex, $size)
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->where(['orderNo' => ['and', $params ['orderNo']]])
            //->where(['b5cOrderNo' => ['and', $params ['ordId']]])
            ->where(['packageType' => ['and', $params ['gudsType']]])
            ->where(['storeId' => ['and', $params ['platCd']]])
            ->where(['platCd' => ['and', $params ['platForm']]])
            ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_CHECK_ORD]])
            ->where(['warehouse' => ['and', $params ['warehouseCode']]])
            ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
            ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
            ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
            ->where(['orderId' => ['and', $params ['orderId']]])
            ->where(['addressUserCountryId' => ['and', $params ['country']]])
            ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
            //->where(['store.platForm' => ['and', $params ['platForm']]])
            ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left'])?(string)strtotime($params ['search_time_left']).'000':'', 'lte' => strtotime($params ['search_time_right'])?(string)strtotime($params ['search_time_right']).'000':'']]]);
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
            if (in_array($params ['search_condition'],$search_array)) {
                $esModel->where([$params ['search_condition'] => ['and', [$params ['search_value']]]]);
            }else{
                $esModel->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
            }
        }
        if ($isPage) {
            $q = $esModel->page($pageIndex, $size)->getQuery();
            $response = $this->esClient->search($q);
            $pageData = $this->template($response ['hits']['hits']);
            $this->total = $response ['hits']['total'];
        } else {
            $q = $esModel->getQuery();
            $response = $this->esClient->search($q);
            if ($isExportExcel) {
                $esModel
                    ->sort([$params ['sort'] => 'desc'])
                    ->setDefault(['and', ['msOrd', 'msOrd.ordId', $params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
                    ->setDefaultNotNull(['not', [$params ['remarkMsg']?'remarkMsg':'', $params ['msgCd1']?'msgCd1':'']])
                    ->setMissing(['and', ['childOrderId']])
                    ->page($pageIndex, $response ['hits']['total'])
                    ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
                    //->where(['b5cOrderNo' => ['and', $params ['ordId']]])
                    ->where(['packageType' => ['and', $params ['gudsType']]])
                    ->where(['storeId' => ['and', $params ['platCd']]])
                    ->where(['orderNo' => ['and', $params ['orderNo']]])
                    ->where(['platCd' => ['and', $params ['platForm']]])
                    ->where(['msOrd.wholeStatusCd' => ['and', self::WAIT_CHECK_ORD]])
                    ->where(['warehouse' => ['and', $params ['warehouseCode']]])
                    ->where(['logisticCd' => ['and', $params ['b5cLogisticsCd']]])
                    ->where(['logisticModelId' => ['and', $params ['logisticModel']]])
                    ->where(['store.saleTeamCd' => ['and', $params ['saleTeamCd']]])
                    ->where(['orderId' => ['and', $params ['orderId']]])
                    ->where(['addressUserCountryId' => ['and', $params ['country']]])
                    ->where(['afterSaleType' => ['and', $params ['after_sale_type']]])
                    //->where(['store.platForm' => ['and', $params ['platForm']]])
                    ->where([$params ['search_time_type'] => ['range', ['gte' => strtotime($params ['search_time_left'])?(string)strtotime($params ['search_time_left']).'000':'', 'lte' => strtotime($params ['search_time_right'])?(string)strtotime($params ['search_time_right']).'000':'']]])
                    ->where([$params ['search_condition'] => ['like', $params ['search_value']]])
                    ->getQuery();
                $q = $esModel->getQuery();
                $response = $this->esClient->search($q);
                $response = $response ['hits']['hits'];
                $pageData = $this->excelExportDataModel($response);
            }
        }

        return $pageData;
    }

    public function template($response)
    {
        foreach ($response as $key => $value) {
            $esData = $value ['_source'];
            $pageData [] = [
                'platName'                 => $esData ['platName'], // 平台名
                'storeName'                => $esData ['store']['storeName'],// 店铺名
                'b5cOrderNo'               => $esData ['b5cOrderNo'], // 订单号
                'orderId'                  => $esData ['orderId'], // 第三方订单号
                'orderNo'                  => $esData ['orderNo'], // 第三方订单号
                'pickingNo'                => $esData ['msOrd'][0]['pickingNo'], // 拣货号
                'skuIds'                   => $this->getSkuIds($esData ['ordGudsOpt']), // 商品编码
                'ordGudsOpt'               => $esData ['ordGudsOpt'], // 商品编码
                'warehouseNm'              => $esData ['warehouseNm'],//下发仓库
                'expeCompany'              => $esData ['logisticCdNm'],//物流公司
                'logisticModel'            => $esData ['logisticModelIdNm'],//物流方式
                'surfaceWayGetCdNM'        => $esData ['surfaceWayGetCdNm'],//面单
                'surfaceWayGetCd'          => $esData ['surfaceWayGetCd'],//面单
                'trackingNumber'           => $esData ['ordPackage'][0]['trackingNumber'], // 运单号
                'remarkMsg'                => $esData ['remarkMsg'], // 备注
                'msgCd1'                   => $esData ['msOrd']['msgCd1'], //核单异常
                'platCd'                   => $esData ['store']['platCd'],
                'use_remarks'              => $esData ['shippingMsg'],
                'logisticsSingleStatuCdNm' => $esData ['logisticsSingleStatuCdNm'],
                "after_sale_type"          => $esData['afterSaleType'],
                "order_time"               => $esData["orderTime"],
                "payment_time"             => $esData["orderPayTime"],
                "shipping_time"            => $esData['shippingTime'],
                "send_ord_time"            => $esData['sendOrdTime'],
                "sendout_time"             => $esData['msOrd'][0]['sendoutTime'],
                "is_shopnc_order"          => strtolower($esData['store']['beanCd']) == 'shopnc' ? 1 : 0,
            ];
        }
        $pageData = SkuModel::joinPmsSkuInfo($pageData);
        return $pageData;
    }

    /**
     * 商品编码
     * @param array $data 商品信息
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

    public $saveLogOrder = null;
    public $platCd = null;
    /**
     * 处理根据运单号获取的数据
     * @param array $data
     * @param bool isCheck
     * @return array
     */
    public function parseData($data, $isCheck = false)
    {
        try{
            $gudsOpt = [];
            if ($data) {
                $model = new Model();
                $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920200';
                // 根据 sku 将当前订单下的所有 sku 归类
                foreach ($data as $i => $o) {
                    OrderEsModel::insertSkuNmOpt($o ['_source'], $language_type);
                    if (!$this->checkStatus($o ['_source']['msOrd'][0]['wholeStatusCd'], $o ['_source']['msOrd'][0]['ordStatCd'])) {
                        Logs($data, __FUNCTION__.'-data', 'fm');
                        $error [] = $o ['_source']['msOrd'][0]['ordId'];
                        continue;
                    } else {
                        $this->saveLogOrder [$o ['_source']['msOrd'][0]['ordId']] = $o ['_source']['orderId'];
                        $s = null;
                        $this->ordIds [] = $o ['_source']['msOrd'][0]['ordId'];
                        $this->platCd [$o ['_source']['msOrd'][0]['ordId']] = $o ['_source']['platCd'];
                    }
                    //OMS的UPC编码从PMS读取
                    $skuIds = array_column($o ['_source']['productSkues'], 'skuId');
                    $upcIdInfo = $model->table(PMS_DATABASE . '.product_sku')
                        ->where(['sku_id' => ['in', $skuIds]])
                        ->getField('sku_id, upc_id as upcId');
                    // 数量获取，根据到期日的不同,仓库，多个相同的 sku 需要单独拆分成多条数据
                    foreach ($o ['_source']['batchOrder'] as $key => $value) {
                        $deadLineDataForUse = '';
                        $res = explode('.', $value ['deadlineDateForUse']);
                        if (count($res) > 1) {
                            $deadLineDataForUse = $res [0];
                            $deadLineDataForUse = date('Y-m-d', strtotime($deadLineDataForUse));
                        }
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['platName'] = $o['_source']['platName'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['storeName'] = $o['_source']['store']['storeName'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['orderId'] = $o['_source']['orderId'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['skuId'] = $value ['skuId'];


                        $gudsOpt [$value ['ordId']][$value ['skuId']]['occupyNum'] += $value ['occupyNum'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['ordId'] = $value ['ordId'];
                        $gudsOpt [$value ['ordId']][$value ['skuId']]['warehouseCode'] = $value ['deliveryWarehouseEtc'];
                        // 获取商品条形码
                        foreach ($o ['_source']['productSkues'] as $key2 => $value2) {
                            if ($value ['skuId'] == $value2['skuId']) {
                                //$gudsOpt [$value ['ordId']][$value ['skuId']]['gudsOptUpcId'] = $value2 ['upcId'];
                                //OMS的UPC编码从PMS读取
                                $gudsOpt [$value ['ordId']][$value ['skuId']]['gudsOptUpcId'] = $upcIdInfo[$value ['skuId']];
                            }

                        }
                        //商品名
                        foreach ($o['_source']['opOrderGuds'] as $k2 => $v2) {
                            if ($v2['b5cSkuId'] == $value ['skuId']) {
                                $gudsOpt [$value ['ordId']][$value ['skuId']]['gudsNm'] = $v2 ['spu_name'];
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
                    Logs($error, __FUNCTION__.'-error', 'fm');
                    $this->responseStateCode = 3005;
                    $responseData = $error;
                    throw new Exception(L('error'));
                }
                if ($gudsOpt) {
                    $this->responseStateCode = 2000;
                    $responseData = $gudsOpt;
                } else
                    $this->responseStateCode = 3004;

            } else {
                $this->responseStateCode = 3003;
            }
        } catch(Exception $e) {

        }
        $r ['pageData'] = $responseData;

        $response ['code'] = $this->responseStateCode;
        $response ['info'] = L($this->msgMap($this->responseStateCode));
        $response ['data'] = $r;
        return $response;
    }
    public $ordIds = null;
    /**
     * 扫描后的数据与扫描前的数据作对比
     * @param array  $beforeScan
     * @param array  $afterScan
     * @return array
     */
    public function contrastCsanOrder($beforeScan, $afterScan)
    {
        $this->responseStateCode = null;
        $this->ordIds = null;
        try {
            $this->startTrans();
            if (empty($beforeScan) or empty($afterScan)) {
                $this->responseStateCode = 3003;
                throw new Exception($this->msgMap($this->responseStateCode));
            } else {
                foreach ($afterScan as $b5cOrderNo => $orderInfo) {
                    if (!isset($beforeScan [$b5cOrderNo])) {
                        $this->responseStateCode = 3003;
                        throw new Exception($this->msgMap($this->responseStateCode));
                    }
                    foreach ($orderInfo as $sku => $info) {
                        if (!isset($beforeScan [$b5cOrderNo][$sku])) {
                            $skuNotExists [] = $sku;
                            $this->responseStateCode [] = 3009;
                            continue;
                        }
                        if ($beforeScan [$b5cOrderNo][$sku]['occupyNum'] != $info ['scanNums']) {
                            $skuNumDiff [] = $sku;
                            $this->responseStateCode [] = 3010;
                            continue;
                        }
                    }
                    $this->ordIds [] = $b5cOrderNo;
                }
                if ($skuNotExists or $skuNumDiff) {
                    $r ['skuNotExists'] = $skuNotExists;
                    $r ['skuNumDiff']   = $skuNumDiff;
                    $this->updateMsOrd($this->ordIds, false);
                } else {
                    // 匹配完成，数据更新
                    $this->updateMsOrd($this->ordIds);
                    $this->responseStateCode = 2000;
                }
                $this->commit();
            }

        } catch (Exception $e) {
            $this->rollback();
        }

        $response ['code'] = $this->responseStateCode;
        $response ['info'] = $this->parseException();
        $response ['data'] = $r;

        return $response;
    }

    /**
     * 处理扫描后数据对比后的异常报错
     */
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
     * 更新 tb_ms_ord 订单核单状态、拣货时间
     * @param array $ordId 订单号
     * @param bool $isOk 是否完成扫码
     * @return array
     * @throws Exception
     */
    public function updateMsOrd($ordId = null, $isOk = true)
    {
        $model = new Model();
        if ($this->ordIds) {
            $conditions ['tb_ms_ord.ORD_ID'] = ['in', $this->ordIds];
            $isOk?$ret ['WHOLE_STATUS_CD'] = SELF::WAIT_OUTGO_ORD:SELF::WAIT_CHECK_ORD;
            $isOk?$ret ['MSG_CD1'] = 'N002020400':null;
            $ret ['S_TIME1']         = date('Y-m-d H:i:s', time());
            $ret ['PICKING_NO']      = $this->pickingNo;
            if (!$model->table('tb_ms_ord')->where($conditions)->save($ret)) {
                $this->responseStateCode = 3006;
                throw new Exception(L($this->msgMap($this->responseStateCode)));
            } else {
                // 写入历史
                /*$data = null;
                foreach ($this->ordIds as $key => $value) {
                    $tmp = null;
                    $tmp ['ORD_NO'] = $this->saveLogOrder [$value];
                    $tmp ['ORD_HIST_SEQ'] = time();
                    $tmp ['ORD_STAT_CD'] = $isOk?SELF::WAIT_OUTGO_ORD:SELF::WAIT_CHECK_ORD;
                    $tmp ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
                    $tmp ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
                    $tmp ['ORD_HIST_HIST_CONT'] = $isOk?'核单通过':'核单未通过';
                    $tmp ['plat_cd'] = $this->platCd [$value];
                    $data [] = $tmp;
                }
                if (!SmsMsOrdHistModel::writeMulHist($data)) {
                    $this->responseStateCode = 3007;
                    throw new Exception(L($this->msgMap($this->responseStateCode)));
                }*/
            }
        } else {
            $this->responseStateCode [] = 3007;
            throw new Exception(L($this->msgMap($this->responseStateCode)));
        }
    }

    /**
     * 一键通过、万能码通过
     * @param array $data 订单数据
     * @param string $type  一键通过、万能码通过
     * @return array
     */
    public function oneKeyThrough($data, $type = null)
    {
        $this->ordIds = null;
        if ($data ['code'] != 2000) {
            $response ['code'] = $data ['code'];
            $response ['info'] = $data ['info'];
            $response ['data'] = $data ['data'];
        } else {
            foreach ($data ['data']['pageData'] as $ordId => $skuInfo) {
                $this->ordIds [] = $ordId;
            }
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

    /**
     * 可扫描核单状态
     */
    public function canScanStatus()
    {
        return [
            'N000550400',
            'N000550500',
            'N000550600',
            'N000550800'
        ];
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
        if ($status != SELF::WAIT_CHECK_ORD)
            return false;
        if (!in_array($ordStatCd, $this->canScanStatus()))
            return false;

        return true;
    }
}