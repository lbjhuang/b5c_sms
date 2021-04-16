<?php

/**
 * User: huanzhu
 * Date: 18/2/26
 * Time: 18:25
 */

class OrderPresentModel extends BaseModel
{

    private static $type = 'es_order';
    private static $index = 'es_order';
    private static $order_log_model = '';
    const  ORDER_STATUS = 'N000550400';     //订单状态 (发货)
    const ORD_SUBMIT = 'GENERAL SYSTEM';
    const DETAIL_MSG = '预派单成功';

    /**
     * @param $order_no
     * @param $plat_cd
     *
     * @return mixed
     */
    private static function bwcStatusGet($order_no, $plat_cd)
    {
        $Model = M();
        $where['ORDER_ID'] = $order_no;
        $where['PLAT_CD'] = $plat_cd;
        $bwc_status = $Model->table('tb_op_order')->where($where)->getField('BWC_ORDER_STATUS');
        return $bwc_status;
    }

    public static function toDoFaceListGet($v, $Model = null)
    {
        $Model = new Model();
        $Patch = new PatchAction();
        $face_data = $Patch->faceOrderGet($v['logistics_type']);
        if ($v['third_order_number'] && $v['plat_cd'] && $face_data[0]['CD'] && $face_data[0]['CD_VAL']) {
            $where['ORDER_ID'] = $v['third_order_number'];
            $where['PLAT_CD'] = $v['plat_cd'];
            $save['SURFACE_WAY_GET_CD'] = $face_data[0]['CD'];
            $surface_way_get_cd = $Model->table('tb_op_order')
                ->where($where)
                ->getField('SURFACE_WAY_GET_CD');
            if (empty($surface_way_get_cd)) {
                $upd_res = $Model->table('tb_op_order')->where($where)->save($save);
                if ($upd_res) {
                    $v['surfaceWayGetCdNm'] = $face_data[0]['CD_VAL'];
                }
            }
            unset($where);
            unset($save);
        }
        return $v;
    }

    /**
     * @param $resNew
     * @param $k
     * @param $v
     * @param $Model
     *
     * @return mixed
     */
    private static function doFaceListGet($resNew, $k, $v, $Model)
    {
        $Patch = new PatchAction();
        $face_data = $Patch->faceOrderGet($resNew[$k]['recommendLogModeId']);
        $resNew[$k]['surfaceWayGetNm'] = $face_data[0]['CD_VAL'];
        if ($v['orderId'] && $v['platCd'] && $face_data[0]['CD'] && $face_data[0]['CD_VAL']) {
            $where['ORDER_ID'] = $v['orderId'];
            $where['PLAT_CD'] = $v['platCd'];
            $save['SURFACE_WAY_GET_CD'] = $face_data[0]['CD'];
            $surface_way_get_cd = $Model->table('tb_op_order')->where($where)->getField('SURFACE_WAY_GET_CD');
            if (empty($surface_way_get_cd)) $Model->table('tb_op_order')->where($where)->save($save);
            unset($where);
            unset($save);
        }
        return $resNew;
    }

    /**
     * @param $resNew
     * @param $findGetData
     * @param $relationData
     * @param $k
     * @param $v
     * @param $logisticsCodeData
     * @param $logisticsModeData
     * @param $Model
     *
     * @return array
     */
    private static function findFor($resNew, $findGetData, $relationData, $k, $v, $logisticsCodeData, $logisticsModeData, $Model)
    {
        foreach ($findGetData as $v1) {
            if ((is_null($relationData['WAREHOUSE']) or empty($relationData['WAREHOUSE']))) {   //无仓库值判断
                if ($v1['isShow'] == 1) {
                    $resNew[$k]['recommendWarehouseName'] = $v1['name'];
                    $resNew[$k]['recommendWarehouseCd'] = $v1['cd'];   //推荐下发仓库
                    $warehouse_save['WAREHOUSE'] = $relationData['WAREHOUSE'] = $v1['cd'];
                    $warehouseRes = M("op_order", "tb_")->where("PLAT_CD = " . "'" . $v['platCd'] . "'" . "AND ORDER_ID=" . "'" . $v['orderId'] . "'")->save($warehouse_save);
                }
            } else {
                $resNew[$k]['recommendWarehouseCd'] = $relationData['WAREHOUSE'];
                $Dictionary_data = D("Universal/Dictionary")->getDictionaryByCd($relationData['WAREHOUSE']);
                $resNew[$k]['recommendWarehouseName'] = $Dictionary_data[$relationData['WAREHOUSE']]['CD_VAL'];//推荐下发仓库
            }
            list($relationData, $LogCompany, $resNew, $ModeData) = self::lgtFor($resNew, $relationData, $k, $v, $logisticsCodeData, $logisticsModeData, $Model, $v1);
        }
        return array($v1, $relationData, $resNew, $Dictionary_data, $LogCompany, $ModeData);
    }

    /**
     * @param $resNew
     * @param $relationData
     * @param $k
     * @param $v
     * @param $logisticsCodeData
     * @param $logisticsModeData
     * @param $Model
     * @param $v1
     *
     * @return array
     */
    private static function lgtFor($resNew, $relationData, $k, $v, $logisticsCodeData, $logisticsModeData, $Model, $v1)
    {
        foreach ($v1['lgtModel'] as $v2) {
            if ($v2['isShow'] == 1) {
                if (is_null($relationData['logistic_cd']) or empty($relationData['logistic_cd'])) {
                    $logisticsCodeData['logistic_cd'] = $v2['logisticsCode'];
                    $logisticsCodeRes = M("op_order", "tb_")->where("PLAT_CD = " . "'" . $v['platCd'] . "'" . "AND ORDER_ID=" . "'" . $v['orderId'] . "'")->save($logisticsCodeData);
                    $LogCompany = D("Universal/Dictionary")->getDictionaryByCd($v2['logisticsCode']);
                    $resNew[$k]['recommendLogCompanyCd'] = $v2['logisticsCode'];  //物流公司code
                    $resNew[$k]['recommendLogCompany'] = $LogCompany[$v2['logisticsCode']]['CD_VAL']; //物流公司nm
                } else {
                    $resNew[$k]['recommendLogCompanyCd'] = $relationData['logistic_cd'];  //物流公司code
                    $LogCompany = D("Universal/Dictionary")->getDictionaryByCd($relationData['logistic_cd']);
                    $resNew[$k]['recommendLogCompany'] = $LogCompany[$relationData['logistic_cd']]['CD_VAL'];  //物流公司code
                }
                list($relationData, $resNew, $ModeData) = self::lgtMethodFor($resNew, $relationData, $k, $v, $logisticsModeData, $Model, $v2);
            }
        }
        return array($relationData, $LogCompany, $resNew, $ModeData);
    }

    /**
     * @param $resNew
     * @param $relationData
     * @param $k
     * @param $v
     * @param $logisticsModeData
     * @param $Model
     * @param $v2
     *
     * @return array
     */
    private static function lgtMethodFor($resNew, $relationData, $k, $v, $logisticsModeData, $Model, $v2)
    {
        foreach ($v2['logisticsMethod'] as $v3) {
            if ($v3['isShow'] == 1) {
                if (is_null($relationData['logistic_model_id']) or empty($relationData['logistic_model_id'])) {
                    $logisticsModeData['logistic_model_id'] = $v3['id'];
                    $logisticsModeData['SEND_FREIGHT'] = $v3['fright'];
                    $logisticsModeRes = M("op_order", "tb_")->where("PLAT_CD = " . "'" . $v['platCd'] . "'" . "AND ORDER_ID=" . "'" . $v['orderId'] . "'")->save($logisticsModeData);   //接口获取值写去下发仓库
                    $resNew[$k]['recommendLogMode'] = $v3['logisticsMode'];  //物流公司code
                    $resNew[$k]['recommendLogModeId'] = $v3['id'];  //物流公司code
                    $resNew[$k]['recommendfright'] = $v3['fright'];  //物流公司code
                } else {
                    $resNew[$k]['recommendLogModeId'] = $relationData['logistic_model_id'];  //物流公司code
                    $resNew[$k]['recommendfright'] = $relationData['SEND_FREIGHT'];
                    if ($relationData['logistic_model_id']) {
                        $ModeData = D("Logistics/LogisticsMode")->field("LOGISTICS_MODE")->where("ID={$relationData['logistic_model_id']}")->find();
                        $resNew[$k]['recommendLogMode'] = $ModeData["LOGISTICS_MODE"];//物流公司
                    }
                    $resNew[$k]['recommendfright'] = $relationData['SEND_FREIGHT'];
                }
            }
            //    面单获取方式默认
            if (!empty($resNew[$k]['recommendLogModeId'])) {
                $resNew = self::doFaceListGet($resNew, $k, $v, $Model);
            }
        }
        return array($relationData, $resNew, $ModeData);
    }

    /**
     * @param $resNew
     * @param $v
     * @param $all_item_count
     * @param $relationData
     * @param $inventory
     * @param $k
     *
     * @return array
     */
    private static function gudsInfo($resNew, $v, $all_item_count, $relationData, $inventory, $k)
    {
        $stock_adequate = true;
        foreach ($v['opOrderGuds'] as $v1) {
            $all_item_count += $v1['itemCount'];

            if (!empty($relationData['WAREHOUSE'])) {   //查询库存信息
                $returnData = json_decode(curl_get_json_get(HOST_S_URL . "/s/b5c/batchStock?skuId=" . $v1['b5cSkuId'] . "&saleTeamCode=" . $v['store']['saleTeamCd'] . "&deliveryWarehouse=" . $relationData['WAREHOUSE']), true);
                $inventory['availableForSale'] = $returnData['data']['batchStock'][0]['availableForSale'] ? $returnData['data']['batchStock'][0]['availableForSale'] : null;
                if (is_null($inventory['availableForSale'])) {
                    $stock_adequate = false;
                }
            } else {
                $stock_adequate = false;
                $inventory['availableForSale'] = null;
            }
            $inventory['b5cSkuId'] = $v1['b5cSkuId'];
            $inventory['itemCount'] = $v1['itemCount'];
            if ($inventory['availableForSale'] < $inventory['itemCount']) {
                $stock_adequate = false;
            }
            $resNew[$k]['inventory'][] = $inventory;
        }
        return array($all_item_count, $relationData, $stock_adequate, $resNew);
    }

    public function _initialize()
    {
        $this->order_log_model = M("ms_ord_hist", "sms_");

    }

    /**
     * @var 搜索条件
     */
    private static $condition_change_data = [
        'order_id' => 'b5cOrderNo',
        'thr_order_id' => 'orderId',
        'thr_order_no' => 'orderNo',
        'receiver_phone' => 'addressUserPhone',
        'pay_the_serial_number' => 'payTransactionId',
        'receiver_tel' => 'receiverTel',
        'consignee_name' => 'addressUserName',
        'tracking_number' => 'ordPackage.trackingNumber',
        'sku_title' => 'msGuds.gudsNm',
        'sku_number' => 'opOrderGuds.b5cSkuId',
        'order_time' => 'orderTime',
        'pay_time' => 'orderPayTime',
        'send_time' => 'shippingTime',
        'send_ord_time' => 'sendOrdTime',
        's_time4' => 'msOrd.sTime4.keyword',
        'sendout_time' => 'msOrd.sendoutTime.keyword',
    ];

    protected $trueTableName = 'tb_op_order';

    /**
     * @param $resNew  原始数据格式
     *
     * @return data   接口返回数据
     */
    private static function mapListField($resNew)
    {
        $OrderEs = new OrderEsModel();
        foreach ($resNew as $es) {
            $mapListField[] = [
                'id' => $es['id'],
                'platName' => $es['platName'],
                'platCd' => $es['platCd'],
                "order_type" => $OrderEs->getOrderType($es['platCd']),
                'storeName' => $es['storeName'],
                'saleTeamName' => $es['store']['saleTeamCdNm'],
                'orderId' => $es['orderId'],
                'orderNo' => $es['orderNo'],
                'payCurrencyZn' => $es['payCurrencyZn'],
                'payTotalPrice' => $es['payTotalPrice'],
                'orderTime' => $es['orderTime'],
                'orderPayTime' => $es['orderPayTime'],
                'inventory' => $es['inventory'],  //商品库存信息
                'stock_adequate' => $es['stock_adequate'],//库存是否重足
                'recommendfright' => $es['recommendfright'],
                'recommendWarehouseName' => $es['recommendWarehouseName'],
                'recommendWarehouseCd' => $es['recommendWarehouseCd'],
                'recommendLogCompanyCd' => $es['recommendLogCompanyCd'],
                'recommendLogCompany' => $es['recommendLogCompany'],
                'recommendLogMode' => $es['recommendLogMode'],
                'recommendLogModeId' => $es['recommendLogModeId'],
                'remarkMsg' => $es['remarkMsg'],
                "use_remarks" => $es["shippingMsg"],
                'countryId' => $es['addressUserCountryId'],
                'storeId' => $es['storeId'],
                'nums' => $es['nums'],
                'trackingNumber' => $es['ordPackage'][0]['trackingNumber'],
                'orderTime' => $es['orderTime'],
                'abnormal_reason' => $es['abnormal_reason'],
                'is_spilt' => $es['is_spilt'],
                'is_sup_spilt' => $es['is_sup_spilt'],
                'payCurrencyZn' => $es['payCurrency'],
                'surfaceWay' => $es['surfaceWayGetNm'],
                'guds' => $OrderEs->buildGuds($es['opOrderGuds'], $es['gudsOpt'], $es['msGuds']),
                'surfaceWayGetCd' => $es['surfaceWayGetCd'],
                'operationType' => $es['store']['operationType'],
                "after_sale_type" => $es['afterSaleType'],
                "shipping_time" => $es['shippingTime'],
            ];
        }
        return $mapListField;
    }

    /**
     * @return 搜索结果
     */
    public function get_condition_data()
    {
        $result['presentType'] = self::presentType();  //预派单类型
        $conditionData = self::getSearchCondition();//筛选条件数据
        $result['conditionData'] = $conditionData;
        return $result;
    }

    /**
     * @return 数据结果
     */
    public static function parseParam()
    {
        $condition = Mainfunc::chooseParam('condition');
        $pageNow = $condition['pageNow'] ? $condition['pageNow'] : 1;  //当前页
        $pageSize = $condition['pageSize'] ? $condition['pageSize'] : 10;  //每页数
        $from = ($pageNow - 1) * $pageSize;
        $OrderEsModel = new OrderEsModel();
        $EsModel = new ESClientModel();
        $params = self::getParams($pageNow, $pageSize, $condition);   //搜索条件
        Logs($params, 'params', 'ES-search');
        $es_data = $EsModel->search($params);
        $totals = $es_data['hits']['total'];
        $res = $OrderEsModel->formatData($es_data);
        foreach ($res[0] as $k => $v) {
            $resNew[] = $v['_source'];
        }
        $resultNew = self::dealFormatData($resNew, $param, $condition);
        $resultNewData = self::mapListField($resultNew);
        $result['totals'] = $totals;
        $result['orderData'] = $resultNewData;  //订单数据
        return $result;

    }

    /**
     * @return 组装条件
     */
    public static function getSearchCondition()
    {
        $storeData = D("TbMsStore")->field("ID,STORE_NAME")->select();
        $logisticMode = D("Logistics/LogisticsMode")->field("ID,LOGISTICS_MODE")->select();
        $condition['store'] = $storeData;
        $condition['logisticMode'] = $logisticMode;
        $condition['country_data'] = M("crm_site", "tb_")->field("ID,NAME")->where("PARENT_ID=0")->order('sort asc')->select();
        return $condition;
    }

    private static function getParams($pageNow, $pageSize, $condition)
    {
        $condition = ZUtils::filterBlank($condition);
        $change_data_arr = self::$condition_change_data;  //转化
        $match = $field = [];
        empty($condition['platCd']) or $field[] = ['terms' => ['platCd.keyword' => $condition['platCd']]];
        empty($condition['countryId']) or $field[] = ['terms' => ['addressUserCountryId.keyword' => $condition['countryId']]]; //搜索国家
        empty($condition['storeId']) or $field[] = ['terms' => ['storeId.keyword' => $condition['storeId']]];  //搜索店铺
        $field[] = ['match' => ['sendOrdStatus' => 'N001820100']];   //派单状态为预派单状态
        empty($condition['warehouseCd']) or $field[] = ['terms' => ['warehouse.keyword' => $condition['warehouseCd']]];  //查询下发仓库
        empty($condition['logisticsCompany']) or $field[] = ['terms' => ['logisticCd.keyword' => $condition['logisticsCompany']]];  //查询物流公司
        empty($condition['logisticsMode']) or $field[] = ['terms' => ['logisticModelId.keyword' => $condition['logisticsMode']]];  //查询物流方式
        empty($condition['saleTeamCd']) or $field[] = ['terms' => ['store.saleTeamCd.keyword' => $condition['saleTeamCd']]];  //查询销售团队

        empty($condition['dispatch_status']) or $field[] = ['terms' => ['findOrderErrorType.keyword' => $condition['dispatch_status']]];  //错误信息/类型
        //时间筛选
        if ($condition['timeType'] && $condition['startTime'] && $condition['endTime']) {
            $timeRange = [
                $change_data_arr[$condition['timeType']] => [
                    'gte' => strtotime($condition['startTime']) . '000',
                    'lte' => strtotime(date('Y-m-d', strtotime($condition['endTime']))) + ((24 * 60 * 60) - 1) . '999'
                ]
            ];
            if ($timeRange) $params['body']['query']['bool']['filter']['range'] = $timeRange;
        }
        if (!empty($condition['search_condition']) and !empty($condition['search_value'])) {
            if (($condition['search_condition'] == 'order_id' || $condition['search_condition'] == 'thr_order_no') && strpos($condition['search_value'], ',')) {
                $k_v = OrderEsModel::orderAllSearch($condition['search_value'], $condition['search_condition']);
                $field[] = $k_v;
            } else {
                $field[] = ['wildcard' => [$change_data_arr[$condition['search_condition']] . ".keyword" => "*" . $condition['search_value'] . "*"]];  //模糊查询
            }
        }
        //$field[] = ['match' =>['store.sendOrdType'=>'0']];  //店铺支持预派单
        //$field[] = ['match' =>['sendOrdType'=>'0']];  //一键通过
        empty($field) or $params['body']['query']['bool']['must'] = $field;
        empty($condition['after_sale_type']) or $params['body']['query']['bool']['must'][]['query_string']['query'] = "afterSaleType:{$condition['after_sale_type']}";
        $params['body']['query']['bool']['must_not'][] = ['exists' => ['field' => 'b5cOrderNo']];  //未生成erp订单
        $params['body']['query']['bool']['must_not'][] = ['exists' => ['field' => 'childOrderId']];  //不能存在子单
        $orField = [ //预派单状态
            ['match' => ['bwcOrderStatus.keyword' => 'N000550400'],], ['match' => ['bwcOrderStatus.keyword' => 'N000550500'],],
            ['match' => ['bwcOrderStatus.keyword' => 'N000550600'],], ['match' => ['bwcOrderStatus.keyword' => 'N000550800'],],
        ];
        $params['body']['query']['bool']['should'] = $orField;
        $from = ($pageNow - 1) * $pageSize;
        $params['body']['from'] = $from;  //开始
        $params['body']['size'] = $pageSize;  //结束
        $condition['sortType'] = $condition['sortType'] ? $condition['sortType'] : 'orderTime';
        $params['body']['sort'] = [$change_data_arr[$condition['sortType']] => ['order' => 'desc']];   //?
        $params['index'] = self::$index;
        $params['type'] = self::$type;
        $query_string = 'bwcOrderStatus:(N000550600 OR N000550400 OR N000550500  OR N000550800) AND store.sendOrdType:1 AND sendOrdStatus:N001820100 AND NOT platCd:(N000831300 OR N000830100)';
        if ($condition['must_exit']) {
            ($query_string) ?
                $query_string .= ' AND  (remarkMsg:?* OR shippingMsg:?*) '
                : $query_string = '  (remarkMsg:?* OR shippingMsg:?*) ';
        }
        $query_str['query_string']['query'] = $query_string;
        $params['body']['query']['bool']['must'][] = $query_str;
        return $params;
    }

    /**
     * @param  string $ORDER_ID 订单id
     * @param  string $PLAT_CD 平台cd
     *
     * @return order_data   原始订单数据
     */
    private static function getPresentRelationVal($ORDER_ID = '', $PLAT_CD = '')
    {
        $tableModel = M("op_order", "tb_");
        (empty($ORDER_ID) or empty($PLAT_CD)) or $ord_data = $tableModel
            ->field("WAREHOUSE,logistic_cd,logistic_model_id,SURFACE_WAY_GET_CD,SEND_FREIGHT")
            ->where("ORDER_ID=" . "'" . $ORDER_ID . "'" . "AND PLAT_CD=" . "'" . $PLAT_CD . "'")
            ->find();

        return $ord_data;
    }

    /**
     * @param   $sent_data [原接口返回值]
     *
     * @return  $data          [接口返回数组数据]
     */
    private static function get_sent_data($sent_data)
    {
        $findData = json_decode($sent_data, true);
        $findGetData = $findData['data']['warehouse'] ? $findData['data']['warehouse'] : null;
        return $findGetData;
    }

    /**
     * 码表code转换
     *
     * @param  string $code code值
     *
     * @return data       返回value
     */
    public static function change_code($code = '')
    {
        $data = D("Universal/Dictionary")->getDictionaryByCd($code);
        return $data[$code]['CD_VAL'];
    }

    public function getEditData()
    {
        $condition = Mainfunc::chooseParam();
        $where['PLAT_CD'] = $condition['platCd'];
        $where['ORDER_ID'] = $condition['orderId'];
        $data['where'] = $where;
        is_null($condition['choose_warehouse_cd']) or $save['WAREHOUSE'] = $condition['choose_warehouse_cd'];
        is_null($condition['choose_logCompany_cd']) or $save['logistic_cd'] = $condition['choose_logCompany_cd'];
        is_null($condition['choose_logMode_id']) or $save['logistic_model_id'] = $condition['choose_logMode_id'];
        is_null($condition['fright']) or $save['SEND_FREIGHT'] = $fright;
        $data['save'] = $save;
        return $data;
    }

    public static function get_child_order_id($ORDER_ID)
    {
        $m = M("op_order", "tb_");
        $where['PARENT_ORDER_ID'] = array('EQ', $ORDER_ID);
        $order_data = $m->field("PARENT_ORDER_ID,CHILD_ORDER_ID,ORDER_ID")->where($where)->order("ID desc")->find();
        if ($order_data) {
            $temp_arr = explode($order_data, '_');
            $code = $ORDER_ID . '_' . ((int)end($temp_arr) + 1);
        } else {
            $code = $ORDER_ID . '_1';
        }
        return $code;
    }

    /**
     * @param $skuid  skuid
     *
     * @return guds_feature   商品特性
     */
    public static function get_guds_features($sku_id)
    {
        $guds_feature = array_unique(array_column((new PmsBaseModel())->table('product_customs')->field("property_code")->where("sku_id='{$sku_id}' and property_value = 1")->select(), "property_code"));
        if (!is_null($guds_feature)) {
            $func = function ($val) {
                $vals = self::change_code($val);
                if (!is_null($vals)) return $vals;
            };
            $guds_feature = array_map($func, $guds_feature);
            $guds_feature = implode(",", $guds_feature);
        }
        $guds_feature = $guds_feature ? $guds_feature : null;
        return $guds_feature;
    }

    /**
     * @param $skuid         skuid
     * @param $saleTeamCode  销售团队
     *
     * @return data   库存数
     */
    public function get_inventory($skuid, $saleTeamCode = '', $deliveryWarehouse = '')
    {

        if (is_array($skuid)) {
            foreach ($skuid as $sku) {
                $data = curl_get_json_get(HOST_S_URL . "/s/b5c/batchStock?skuId=" . $sku . "&saleTeamCode=" . $saleTeamCode . "&deliveryWarehouse=" . $deliveryWarehouse);
                $data = json_decode($data, true);
                $res_data[$sku] = $data['data']['batchStock'][0]['availableForSale'];
            }
        } else {
            $data = curl_get_json_get(HOST_S_URL . "/s/b5c/batchStock?skuId=" . $skuid . "&saleTeamCode=" . $saleTeamCode . "&deliveryWarehouse=" . $deliveryWarehouse);
            $data = json_decode($data, true);
            $res_data = $data['data']['batchStock'][0]['availableForSale'];
        }
        return $res_data;
    }

    /**
     * @param $condition  销售团队、平台、订单号
     *
     * @return data   拆单仓库父级数据
     */
    public function get_split_order_data($condition = array())
    {
        $simple_child_infos = [];
        $guds_model = M("op_order_guds", "tb_");
        $parent_order_data = $this->field("ORDER_ID,FIND_ORDER_JSON,CHILD_ORDER_ID,STORE_ID,WAREHOUSE,PLAT_CD")
            ->where($condition)
            ->find();

        $saleTeamCode = D("TbMsStore")->field("SALE_TEAM_CD")->where("ID='{$parent_order_data['STORE_ID']}'")->find();

        //10459 订单拆单筛选仓库调整接口获取
//        $warehouse_data = array_column(self::get_sent_data($parent_order_data['FIND_ORDER_JSON']), 'name', 'cd');  //推荐下发仓库信息
        $request_data = [
            [
                'thr_order_id' => $parent_order_data['ORDER_ID'],
                'plat_cd' => $parent_order_data['PLAT_CD'],
            ],
        ];
        import('ORG.Util.String');
        $uuid = LogsModel::$uuid = String::uuid();
        list($request_data, $data_key_arr) = PatchModel::filterRecommend($request_data, $uuid);
        $res = ApiModel::recommend($request_data,110);
        if ($res) {
            if (is_string($res['data'])) {
                $res['data'] = json_decode($res['data'], true)['data'];
            }
            foreach ($res['data'] as $key => $val) {
                if ($val['code'] != 2000) {
                    $err['orderId'] = $val['orderId'];
                    $err['platCd'] = $val['platCd'];
                    $err_order[] = $err;
                    unset($res['data'][$key]);
                }
            }
        }else{
            $err_order = [
                [
                    'orderId' => $parent_order_data['ORDER_ID'],
                    'platCd' => $parent_order_data['PLAT_CD'],
                ],
            ];
        }
        if ($err_order) {
            $info_res = PatchModel::patchRecommend($err_order);
            if (empty($info_res) && !is_array($info_res)) {

            } else {
                foreach ($info_res['data'] as $v) {
                    if ($data_key_arr && is_array($data_key_arr)) {
                        foreach ($v['data']['warehouse'] as $temp_k => $temp_v) {
                            if ($temp_v['cd'] != $data_key_arr[$v['orderId'] . $v['platCd']]) {
                                unset($v['data']['warehouse'][$temp_k]);
                            }
                        }
                        $v['data']['warehouse'] = array_values($v['data']['warehouse']);
                    }
                    $res['data'][] = $v;
                }
                $res['data'] = array_values($res['data']);
            }
        }
        $warehouse_data = array_column($res['data'][0]['data']['warehouse'], 'name', 'cd');

        $simple_child_infos['child_order_id'] = self::get_child_order_id($condition['ORDER_ID']);
        $simple_child_infos['parent_order_id'] = $condition['ORDER_ID'];
        $simple_child_infos['plat_cd'] = $condition['PLAT_CD'];
        $order_guds_info = $guds_model->field("ID,B5C_SKU_ID,ITEM_NAME,ITEM_COUNT")->where("ORDER_ID='{$parent_order_data['ORDER_ID']}' AND PLAT_CD='{$parent_order_data['PLAT_CD']}'")->select(); //订单商品信息
        if (empty($warehouse_data) && $parent_order_data) {
            $warehouse_data[$parent_order_data['WAREHOUSE']] = self::change_code($parent_order_data['WAREHOUSE']);
        }
        $order_guds_info = SkuModel::getInfo($order_guds_info, 'B5C_SKU_ID', ['spu_name']);
        foreach ($order_guds_info as $guds) {
            $simple_child_info['all_warehouse_data'] = $warehouse_data;
            $guds_SKU_info = (new PmsBaseModel())->table('product_sku')->where("sku_id=" . "'" . $guds['B5C_SKU_ID'] . "'")->find(); //商品sku信息
            $simple_child_info['sku_id'] = $guds['B5C_SKU_ID'];
            $simple_child_info['guds_id'] = $guds['ID'];
            $simple_child_info['item_name'] = $guds['ITEM_NAME'];
            $simple_child_info['item_name'] = $guds['spu_name'];
            $simple_child_info['saleTeamCode'] = $saleTeamCode['SALE_TEAM_CD'];
            $simple_child_info['weight'] = $guds_SKU_info['sku_weight'];  //重量
            $simple_child_info['volume'] = $guds_SKU_info['sku_length'] * $guds_SKU_info['sku_width'] * $guds_SKU_info['sku_height'];
            $simple_child_info['guds_features'] = static::get_guds_features($guds['B5C_SKU_ID']);
            $simple_child_info['need_count'] = $guds['ITEM_COUNT'];
            $simple_child_info['recom_warehouse_cd'] = $warehouse_data ? $parent_order_data['WAREHOUSE'] : null;
            $warehouse_name = self::change_code($parent_order_data['WAREHOUSE']);
            $simple_child_info['recom_warehouse_name'] = $warehouse_data ? $warehouse_name : null;
            empty($parent_order_data['WAREHOUSE']) or $simple_child_info['inventory'] = self::get_inventory($guds['B5C_SKU_ID'], $saleTeamCode['SALE_TEAM_CD'], $parent_order_data['WAREHOUSE']);
            $simple_child_guds_info[] = $simple_child_info;
        }

        $simple_child_infos['guds'] = $simple_child_guds_info;
        return $simple_child_infos;

    }

    /**
     * @param $condition  拆单数据
     *
     * @return data   拆单子订单数据、订单关联商品数据
     */
    public function deal_submit_order($condition = array(), $order_id = '', $plat_cd = '')
    {
        $all_child_order = [];
        $now_all_guds_data = [];
        $all_child_order_extend = [];
        $guds_model = M("op_order_guds", "tb_");
        $data['CHILD_ORDER_ID'] = implode(",", array_column($condition, "child_order_id"));   //母单下的子单id,逗号连接
        $parent_order_change_res = $this->where("PLAT_CD='{$plat_cd}' AND ORDER_ID='{$order_id}'")->save($data);  //修改母单的子订单id
        $child_basis_data = $this->where("PLAT_CD='{$plat_cd}' AND ORDER_ID='{$order_id}'")->find();
        $child_basis_extend_data = M("order_extend", "tb_op_")->where("PLAT_CD='{$plat_cd}' AND ORDER_ID='{$order_id}'")->find();  //  追加扩展表数据 （order_extend）
        unset($child_basis_data['CHILD_ORDER_ID']);  //去除子订单下的子订单号
        unset($child_basis_data['ID']);
        foreach ($condition as $childInfo) {
            $child_basis_data['ORDER_ID'] = $childInfo['child_order_id'];
            $child_basis_data['WAREHOUSE'] = $childInfo['warehouse'];
            //$child_basis_data['CHILD_ORDER_ID'] = $childInfo['child_order_id'];
            $child_basis_data['PARENT_ORDER_ID'] = $order_id;
            if ('N002010100' == $child_basis_data['SURFACE_WAY_GET_CD']) {
                $child_basis_data['LOGISTICS_SINGLE_STATU_CD'] = 'N002080100';
                $child_basis_data['LOGISTICS_SINGLE_ERROR_MSG'] = L('拆单子单，请重新获取面单');
            }
            // $child_basis_data = $this->cleanInit($order_id, $child_basis_data);
            foreach ($childInfo['guds'] as $guds_info) {
                $basis_guds_data = [];
                $basis_guds_data = $guds_model->where("ID='{$guds_info['id']}'")->find();
                unset($basis_guds_data['ID']);
                if (!is_null($basis_guds_data)) {
                    $basis_guds_data['ITEM_COUNT'] = $guds_info['item_count'];
                    $basis_guds_data['ORDER_ID'] = $childInfo['child_order_id'];
                    $now_all_guds_data[] = $basis_guds_data;  //拆单商品数据
                }
            }
            $all_child_order[] = $child_basis_data;  //拆单子订单数据

            //  扩展表数据
            if (!empty($child_basis_extend_data)){
                $temp_data = array(
                    'order_id' => $childInfo['child_order_id'],
                    'plat_cd' => $child_basis_extend_data['plat_cd'],
                    'doorplate' => $child_basis_extend_data['doorplate'],
                    'other_discounted_price' => $child_basis_extend_data['other_discounted_price'],
                    'platform_discount_price' => $child_basis_extend_data['platform_discount_price'],
                    'seller_discount_price' => $child_basis_extend_data['seller_discount_price'],
                    //次要收货人姓名|次要收货人地址|通行符 追加到子单中
                    'sub_addr_recipient_name' => $child_basis_extend_data['sub_addr_recipient_name'],
                    'sub_addr' => $child_basis_extend_data['sub_addr'],
                    'kr_customs_code' => $child_basis_extend_data['kr_customs_code'],
                    'btc_order_type_cd' => $child_basis_extend_data['btc_order_type_cd'],
                );
                $all_child_order_extend[] = $temp_data;
            }

        }
        $datas['guds'] = $now_all_guds_data;  //商品数据
        $datas['order'] = $all_child_order;
        $datas['order_extend'] = $all_child_order_extend;
        return $datas;

    }

    /**
     * @param $condition  子订单数据
     *
     * @return
     */
    public function validate_split_data($condition = array())
    {
        foreach ($condition as $k => $spilt_order) {
            if ($k === 0 && !RedisLock::lock($spilt_order['order_id'] . '_' . $spilt_order['plat_cd'])) {
                return ["code" => 50001, "msg" => L("订单锁获取失败")];
            }
            $SEND_ORD_STATUS = M('tb_op_order')->where(['ORDER_ID' => $spilt_order['order_id'], 'PLAT_CD' => $spilt_order['plat_cd']])->getField('SEND_ORD_STATUS');
            //待派单，待预排，派单失败校验
            if (!in_array($SEND_ORD_STATUS, ['N001821000', 'N001820100', 'N001820300'])) {
                return ["code" => 50001, "msg" => L("派单状态异常")];
            }
            if (is_null($spilt_order['warehouse'])) {
                $data = array("code" => 50001, "msg" => L("子订单无下发仓库"));
                return $data;
            }
            if (is_null($spilt_order['order_id'])) {
                $data = array("code" => 50001, "msg" => L("主单不存在"));
                return $data;
            }
            if (is_null($spilt_order['plat_cd'])) {
                $data = array("code" => 50001, "msg" => L("平台不存在"));
                return $data;
            }
            foreach ($spilt_order['guds'] as $key => $guds) {

                if (is_null($guds['inventory'])) {
                    // $data = array("code" => 50001, "msg" => "订单" . $spilt_order['child_order_id'] . "存在无可售数量的商品");
                    // return $data;
                }
                if ((empty($guds['item_count']) or is_null($guds['item_count'])) && $guds['item_count'] !== "0") {
                    // $data = array("code"=>50001,"msg"=>"订单".$spilt_order['child_order_id']."未填写拆分数量,请填写");
                    // return $data;
                    unset($condition[$k]['guds'][$key]);
                }
                if ($guds['item_count'] > $guds['inventory']) {
                    // $data = array("code" => 50001, "msg" => L("商品拆分数量不可大与该商品可售数量"));
                    // return $data;
                }
                if ($guds['item_count'] > $guds['need_items']) {
                    $data = array("code" => 50001, "msg" => L("商品拆分数量不可大与该商品需求数的较小值"));
                    return $data;
                }
            }
        }
        /*$guds_info = array_column($condition, 'guds');
        var_dump($guds_info);die;*/

        return $condition;
    }


    /**
     * @param         $order_no
     * @param  string $msg 操作详细内容
     * @param         $plat_cd
     * @param         $str
     *
     * @internal param $ [type] $order_no 订单号
     */
    public static function get_log_data($order_no, $msg = '', $plat_cd, $str = '')
    {
        $bwc_status = self::bwcStatusGet($order_no, $plat_cd);
        $log['ORD_NO'] = $order_no;
        $log['ORD_HIST_SEQ'] = time();
        $log['ORD_STAT_CD'] = $bwc_status;  //订单状态
        $log['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
        $log['ORD_HIST_REG_DTTM'] = date("Y-m-d H:i:s", time());
        $log['ORD_HIST_HIST_CONT'] = $msg;
        $log['updated_time'] = date("Y-m-d H:i:s", time());
        $log['plat_cd'] = $plat_cd;
        $add_res = M("ms_ord_hist", "sms_")->add($log);
    }

    /**
     *
     */
    public static function triggerUpdLogin($resNew)
    {
        // self::dealFormatData($resNew,null);
    }

    /**
     * @param   $resNew 查询数据
     * @param   $param  查询条件
     *
     * @return  $resnew  转换结果
     */
    private static function dealFormatData($resNew, $param)
    {
        $Model = M();
        foreach ($resNew as $k => $v) {
            $relationData = self::getPresentRelationVal($v['orderId'], $v['platCd']);
            $resNew[$k]['orderNo'] = $v['orderNo'];
            if (!empty($v['platCd'])) {
                $platCd = D("Universal/Dictionary")->getDictionaryByCd($v['platCd']);
                $resNew[$k]['platName'] = $platCd[$v['platCd']]['CD_VAL'];
            }
            if (!empty($v['storeId'])) {
                $storeId = D("TbMsStore")->where("ID='{$v['storeId']}'")->field("STORE_NAME")->find();
                $resNew[$k]['storeName'] = $storeId['STORE_NAME'];
            }
            if (!empty($v['payCurrency'])) {
                $payCurrency = D("Universal/Dictionary")->getDictionaryByCd($v['payCurrency']);
                $resNew[$k]['payCurrencyZn'] = $payCurrency[$v['payCurrency']]['CD_VAL'];
            }
            if (!empty($relationData['logistic_model_id']) || $v['findOrderErrorType'] != 'N002050100') {  //不正常,有错误信息,走字段
                $resNew[$k]['abnormal_reason'] = ($v['findOrderErrorType'] == 'N002050100') ? '' : self::change_code($v['findOrderErrorType']);
                $resNew[$k]['recommendWarehouseCd'] = $relationData['WAREHOUSE'];
                $Dictionary_data = D("Universal/Dictionary")->getDictionaryByCd($relationData['WAREHOUSE']);
                $resNew[$k]['recommendWarehouseName'] = $Dictionary_data[$relationData['WAREHOUSE']]['CD_VAL'];//推荐下发仓库
                $resNew[$k]['recommendLogCompanyCd'] = $relationData['logistic_cd'];  //物流公司code
                $LogCompany = D("Universal/Dictionary")->getDictionaryByCd($relationData['logistic_cd']);
                $resNew[$k]['recommendLogCompany'] = $LogCompany[$relationData['logistic_cd']]['CD_VAL'];  //物流公司code
                $resNew[$k]['recommendLogModeId'] = $relationData['logistic_model_id'];  //物流公司code
                $resNew[$k]['recommendfright'] = $relationData['SEND_FREIGHT'];
                if ($relationData['logistic_model_id']) {
                    $ModeData = D("Logistics/LogisticsMode")->field("LOGISTICS_MODE")->where("ID={$relationData['logistic_model_id']}")->find();
                    $resNew[$k]['recommendLogMode'] = $ModeData["LOGISTICS_MODE"];//物流公司
                }
                //    面单获取方式默认
                if (!empty($resNew[$k]['recommendLogModeId'])) {
                    $resNew = self::doFaceListGet($resNew, $k, $v, $Model);
                }
            } else {
                $findGetData = self::get_sent_data($v['findOrderJson']);
                list($v1, $relationData, $resNew, $Dictionary_data, $LogCompany, $ModeData) = self::findFor($resNew, $findGetData, $relationData, $k, $v, null, null, $Model);
            }
            if (!empty($v['opOrderGuds'])) {
                $inventory = [];
                $all_item_count = 0;
                list($all_item_count, $relationData, $stock_adequate, $resNew) = self::gudsInfo($resNew, $v, $all_item_count, $relationData, $inventory, $k);
                if (!$stock_adequate) {
                    $resNew[$k]['stock_adequate'] = 0;
                } else {
                    $resNew[$k]['stock_adequate'] = 1;
                }
                !is_null($resNew[$k]['recommendWarehouseName']) or $resNew[$k]['stock_adequate'] = 3;
                $all_item_count > 1 ? $resNew[$k]['is_sup_spilt'] = 1 : $resNew[$k]['is_sup_spilt'] = 0;   //拆单情况
            }
            if (!is_null($v['parentOrderId'])) {
                $resNew[$k]['is_spilt'] = 1;    //有父单,拆单状态为已拆单
                /*$all_child_order_id = M()->table("tb_op_order")->field("ORDER_ID")->where("PARENT_ORDER_ID='{$v["parentOrderId"]}'")->select(); //所有子单id
                foreach ($all_child_order_id as  $child_ord_id) {
                    $child_id_status = M("op_order","tb_")->field("B5C_ORDER_NO")->where("ORDER_ID='{$child_ord_id["ORDER_ID"]}'")->find();
                    if (!is_null($child_id_status['B5C_ORDER_NO'])) {
                         $resNew[$k]['is_spilt'] =0;
                    }
                }*/
                $resNew[$k]['is_sup_spilt'] = 0;   //有父订单号,为子单              不支持拆单
            } else {
                $resNew[$k]['is_spilt'] = 0;
            }
            //$v['surfaceWayGetCd'] = $v['surfaceWayGetCd']?$v['surfaceWayGetCd']:'N002010200';
            //$resNew[$k]['surfaceWayGetCd'] = $v['surfaceWayGetCd'];
            if ($v['surfaceWayGetCd']) {
                $resNew[$k]['surfaceWayGetNm'] = self::change_code($v['surfaceWayGetCd']);
            }

        }
        return $resNew;
    }

    /**
     * 取消拆单接口处理
     *
     * @param string $platCd 订单
     * @param string $parentOrderId 父订单ID
     *
     * @return mixed 返回接口处理接口
     */
    public function cancellationDis($platCd, $parentOrderId)
    {
        $url = HOST_URL . '/erp_order/operate.json';
        $requestData = [
            'processCode' => 'CANCEL_SEPARATE_ORDER',
            'processId' => create_guid(),
            'data' => [
                [
                    'platCd' => $platCd,
                    'parentOrderId' => $parentOrderId
                ]
            ]
        ];
        $this->setRequestUrl($url);
        $this->setRequestData($requestData);
        $response = curl_get_json($url, json_encode($this->getRequestData()));
        $response = json_decode($response, true);
        $this->setResponseData($response);
        $this->_catchMe();
        return $response;
    }

    /**
     * @param $order_id
     * @param $child_basis_data
     *
     * @return mixed
     */
    private function cleanInit($order_id, $child_basis_data)
    {
        $child_basis_data['WAREHOUSE'] = null;
        $child_basis_data['logistic_cd'] = null;
        $child_basis_data['logistic_model_id'] = null;
        $child_basis_data['FIND_ORDER_JSON'] = null;
        $child_basis_data['FIND_ORDER_ERROR_TYPE'] = null;
        $child_basis_data['FIND_ORDER_INVOKE_TIMES'] = 0;
        $child_basis_data['FIND_ORDER_FAIL_MSG'] = null;
        $child_basis_data['SURFACE_WAY_GET_CD'] = null;
        return $child_basis_data;
    }

    /**
     * 批量更新面单获取方式
     *
     * @param $data
     * @param null $Model
     *
     * @return mixed
     */
    public static function batchToDoFaceListGet($data, $Model = null)
    {
        $Model = new Model();
        $ids = array_column($data, 'id');
        if (empty($ids)) {
            return $data;
        }
        $orders = $Model->table('tb_op_order')
            ->field('ID,ORDER_ID,PLAT_CD,SURFACE_WAY_GET_CD')
            ->where(['ID' => ['in', $ids]])
            ->select();
        foreach ($orders as $key => $order) {
            //已经有面单获取方式则不更新
            if (!empty($order['SURFACE_WAY_GET_CD'])) {
                unset($orders[$key]);
            }
        }
        if (empty($orders)) {
            return $data;
        }
        $logistics_types = array_unique(array_column($data, 'logistics_type'));//物流方式id
        $face_data = PatchModel::batchFaceOrderGet($logistics_types);//物流方式集合
        $temp_data = array_combine($ids, $data);
        foreach ($orders as $key => &$item) {
            $temp_logistics_type = array_values(array_sort($face_data[$temp_data[$item['ID']]['logistics_type']]));
            $way_get_cd = $temp_logistics_type[0];//物流方式对应的面单获取方式
            if (empty($way_get_cd)) {
                unset($orders[$key]);
                continue;
            }
            $item['SURFACE_WAY_GET_CD'] = $way_get_cd;
            unset($temp_logistics_type);
        }
        if (empty($orders)) {
            return $data;
        }
        $surface_code = CommonDataModel::surfaceWayGet();
        $surface_code = array_combine(array_column($surface_code, 'cd'), array_column($surface_code, 'cdVal'));
        $excel_model = new TempImportExcelModel();
        $order_model = M('order', 'tb_op_');
        $res = $order_model->execute($excel_model->saveAll($orders, $order_model, $pk = 'ID'));//批量更新面单获取方式
        if ($res) {
            foreach ($data as &$item) {
                $temp_logistics_type = array_values(array_sort($face_data[$item['logistics_type']]));
                $item['surfaceWayGetCdNm'] = $surface_code[$temp_logistics_type[0]];
                unset($temp_logistics_type);
            }
        }
        unset($temp_data);
        return $data;
    }


    //拆单：查出所有的记录，指定某个id应该更新成子单号是什么。
    public function updateDelivery($order_model, $condition){
        $where = ['order_id'=>$condition[0]['order_id'], 'plat_cd'=>$condition[0]['plat_cd']];
        $delivery_model = M('op_order_delivery','tb_');
        $delivery_list = $delivery_model->where($where)->field('id,b5c_sku_id')->select();
        foreach ($delivery_list as $dk=>$dv){
             $new_delivery[$dv['b5c_sku_id']][] = $dv;
        }

        $sku_split_num = [];
        foreach ($condition as $ck=>$cv){
            $sku_split_num[$cv['guds'][0]['sku_id']][$cv['child_order_id']] = $cv['guds'][0]['item_count'];
        }

        //分隔数组,一个子单号分配到哪些id，再批量更新
        foreach ($new_delivery as $nk=>$nv){
            $start = 0;
            foreach($sku_split_num[$nk] as $child_id=>$num){
                $waiting_delivery[$child_id] = array_slice($new_delivery[$nk], $start, $num);
                $start = $start + $num;
            }
        }

        foreach($waiting_delivery as $wk=>$wv){
            $update_where = ['id'=>['in',array_column($wv,'id')]];
            $update['child_order_id'] = $wk;
            $res =  $delivery_model->where($update_where)->save($update);
            if($res <= 0) {
                return false;
            }

        }
        return true;
    }


    //取消拆单步骤：处理delivery的子单字段
    public function cancelDeliverySplit($condition){
        $order_id = $condition['order_id'];
        preg_match("/(.+)(_\d+)$/i", $order_id,$matches);
        $find_order_id = empty($matches) ? $order_id : $matches[1];
        $where = ['order_id'=>$find_order_id, 'plat_cd'=>$condition['plat_cd']];
        $delivery_data = M('op_order_delivery','tb_')->where($where)->select();
        $flag = true;
        if(!empty($delivery_data)){
            $res = M('op_order_delivery','tb_')->where($where)->save(['child_order_id'=>""]);
            if($res <= 0) {
                $flag = false;
            }
        }
        return $flag;
    }

}