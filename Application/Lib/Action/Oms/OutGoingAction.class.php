<?php

/**
 * 出库
 * User: b5m
 * Date: 2018/3/1
 * Time: 13:02
 */
class OutGoingAction extends BaseAction
{
    private $accountLog;
    private $mail;
    private $esClient;
    protected $whiteList = [];

    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST    = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        $this->accountLog = new TbWmsAccountBankLogModel();
        $this->mail       = new ExtendSMSEmail();
        $this->esClient   = new ESClientModel();
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        } else {
            $_SESSION['userId'] = 0;//自动发货人校验
        }
        B('SYSOperationLog');
    }

    /**
     * 出库页面跳转
     */
    public function outList()
    {
        $this->display();
    }

    public function outListDetail()
    {
        $this->display();
    }

    /**
     * 出库列表页
     */
    public function outGoingListData()
    {
        $query  = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model  = new OmsOutGoingModel();
        $esData = $model->getListData($query);
        $query ['pageIndex'] < 0 ? $query ['pageIndex'] = 1 : $query['pageIndex'] = $query ['pageIndex'];
        $query ['pageSize'] ? $size = $query ['pageSize'] : $size = 20;
        $data ['pageIndex']   = $query ['pageIndex'];
        $data ['pageSize']    = $size;
        $data ['totalPage']   = ceil($model->total / $data ['pageSize']);
        $data ['pageData']    = $esData;
        $data ['parmeterMap'] = $query;
        $data ['totalCount']  = $model->total;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批量发货
     */
    public function mulDeliverGoods()
    {
        $query       = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model       = new OmsOutGoingModel();
        $model->mode = 1;

        //判断是否退款
        if (!empty($query['ordId'])) {
            $res_refund = (new OmsAfterSaleService())->checkOrderRefund($query['ordId'], 'b5c_order_no');
            if (true !== $res_refund) {
                $this->ajaxReturn($res_refund);
            }
        }

        if ('b08a8be1abd25efd858141757dbfc5c5' == $_GET['api']) {
            $model->autoSend = true;
        } elseif (!empty($query['ordId'])) {
            list($query['ordId'], $err_msg) = $this->limitUserLogistics($query['ordId']);
        }
        if (!empty($query['ordId'])) {
            $r = $model->mulDeliver($query);
        } else {
            $r['code'] = 2000;
            $r['info'] = '与用户指定仓库物流不符';
            $r['data'] = [];
        }
        $r['data'] = array_merge(array_values($r['data']), array_values($err_msg));
        $response  = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);

        $order_ids = [];
        foreach ($r ['data'] as $v) {
            if ($v['code'] != '2000') {
                continue;
            }
            $order_ids[] = $v['ordId'];
        }
       
        //补发单状态改变
        if ($order_ids) {
            (new OmsAfterSaleService())->changeReissueStatusToFinished($order_ids);
            
        }
        if ($order_ids) {
            #异步请求标记发货  根据 b5c_order_no
            $this->signSendOut($order_ids, 1);
        }
        //批量主动刷新订单
        (new OmsService())->saveAllOrderByB5cOrderNo($query['ordId'], __FUNCTION__);

        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['B5C_ORDER_NO'=> ['in',$query['ordId']]]);

        $this->ajaxReturn($response, 'json');
    }

    public function limitUserLogistics($orders)
    {
        $err_msg               = $orders_return = [];
        if (empty($orders)) {
            return [$orders_return, $err_msg];
        }
        $Model                 = new \Model();
        $where['B5C_ORDER_NO'] = ['IN', $orders];
        $where_string          = ' (
                            (
                                default_logistics_cd = logistic_cd
                                AND default_logistics_mode_id = logistic_model_id
                                AND default_warehouse = WAREHOUSE
                                AND has_default_warehouse = 1
                            )
                            OR (                           
                                has_default_warehouse <> 1
                            )
                        )';
        $allow_orders_db       = $Model->table('tb_op_order')
            ->field('B5C_ORDER_NO')
            ->where($where)
            ->where($where_string)
            ->select();
        $allow_orders          = array_column($allow_orders_db, 'B5C_ORDER_NO');
        foreach ($orders as $order) {
            if (in_array($order, $allow_orders)) {
                $orders_return[] = $order;
            } elseif ($order) {
                $err_msg[] = array(
                    'code'  => 40001,
                    'ordId' => $order,
                    'msg'   => '与用户指定仓库物流不符',
                );
            }
        }
        return [$orders_return, $err_msg];
    }

    /**
     * 直接出库，不走第三方发货接口
     */
    public function directOutgoing()
    {
        $query         = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model         = new OmsOutGoingModel();
        $model->mode   = 1;
        $model->direct = true;

        //判断是否退款
        if (!empty($query['ordId'])) {
            $res_refund = (new OmsAfterSaleService())->checkOrderRefund($query['ordId'], 'b5c_order_no');
            if (true !== $res_refund) {
                $this->ajaxReturn($res_refund);
            }
        }

        $r        = $model->mulDeliver($query);
        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);

        $order_ids = [];
        foreach ($r ['data'] as $v) {
            if ($v['code'] != '2000') {
                continue;
            }
            $order_ids[] = $v['ordId'];
        }
        //补发单状态改变
        if ($order_ids) {
            (new OmsAfterSaleService())->changeReissueStatusToFinished($order_ids);
        }

        //批量主动刷新订单
        (new OmsService())->saveAllOrderByB5cOrderNo($query['ordId'], __FUNCTION__);

        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['B5C_ORDER_NO' => ['in',$query['ordId']]]);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批量更新
     */
    public function mulUpdate()
    {
        $query = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new TbOpOrdModel();
        $sql   = $this->saveAll($query, $model, 'B5C_ORDER_NO');
        if ($model->execute($sql)) {
            $response = $this->formatOutput(2000, 'success', []);
        } else {
            $response = $this->formatOutput(3000, $model->getDbError(), []);
        }
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批量更新运单号
     */
    public function mulUpdateTrackingNo()
    {

    }

    /**
     * 面单获取
     */
    public function surfaceWay()
    {
        $query = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new OmsOutGoingModel();
        $r     = $model->getSurfaceWay($query ['orderIds']);

        $response = $this->formatOutput($r ['code'], $r ['msg'], $r ['data']);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批更新
     * @param array $datas 需要更新的数据集合
     * @param object $model 模型
     * @param string $pk 主键
     * @return string $sql
     */
    public function saveAll($datas, $model, $pk = '')
    {
        $sql   = '';
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
     * 测试上传EXCEL发货
     */
    public function uploadExcel()
    {
        if (IS_POST) {
            $deliverGoodsModel = new ExcelDeliverGoodsModel();
            $r                 = $deliverGoodsModel->import();
            $response          = $this->formatOutput($r ['code'], $r ['msg'], $r ['data']);

            $this->ajaxReturn($response, 'json');
        } else {
            $this->display();
        }
    }

    /**
     * EXCEL 发货
     */
    public function excelDeliverGoods()
    {

    }

    /**
     * 导出
     */
    public function export()
    {
        $query                   = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true)['data']['query']);
        $model                   = new OmsOutGoingModel();
        $esData                  = $model->getListData($query, false, 'excel');
        $exportExcel             = new ExportExcelModel();
        $key                     = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('站点名称'), 'field_name' => 'platName'],
            $key++ => ['name' => L('店铺名称'), 'field_name' => 'storeName'],
            $key++ => ['name' => L('店铺所属公司'), 'field_name' => 'company_name'],
            $key++ => ['name' => L('订单创建类型'), 'field_name' => 'source'],
            $key++ => ['name' => L('订单创建人'), 'field_name' => 'createUser'],
            $key++ => ['name' => L('第三方订单ID'), 'field_name' => 'orderId'],
            $key++ => ['name' => L('第三方订单号'), 'field_name' => 'orderNo'],
            $key++ => ['name' => L('ERP订单号'), 'field_name' => 'b5cOrderNo'],
            $key++ => ['name' => L('订单状态'), 'field_name' => 'bwcOrderStatusNm'],
            $key++ => ['name' => L('SKU ID'), 'field_name' => 'b5cSkuId'],
            $key++ => ['name' => L('第三方SKU ID'), 'field_name' => 'skuId'],
            $key++ => ['name' => L('SKU名称'), 'field_name' => 'skuNm'],
            $key++ => ['name' => L('SKU 属性'), 'field_name' => 'gudsOptValMpngNm'],
            $key++ => ['name' => L('币种'), 'field_name' => 'payCurrency'],
            $key++ => ['name' => L('商品成本价(USD)'), 'field_name' => 'costPrice'],
            $key++ => ['name' => L('商品采购公司'), 'field_name' => 'skuPurchasingCompany'],
            $key++ => ['name' => L('订单商品销售单价'), 'field_name' => 'itemPrice'],
            $key++ => ['name' => L('商品数量'), 'field_name' => 'itemCount'],
            $key++ => ['name' => L('订单商品总价'), 'field_name' => 'payItemPrice'],
            $key++ => ['name' => L('订单总优惠金额'), 'field_name' => 'payVoucherAmount'],
            $key++ => ['name' => L('订单运费'), 'field_name' => 'payShipingPrice'],
            $key++ => ['name' => L('订单包装费'), 'field_name' => 'payWrapperAmount'],
            $key++ => ['name' => L('订单分期总手续费'), 'field_name' => 'payInstalmentServiceAmount'],
            $key++ => ['name' => L('订单商品总税费'), 'field_name' => 'tariff'],
            $key++ => ['name' => L('订单优惠总税费'), 'field_name' => 'promotionDiscountTax'],
            $key++ => ['name' => L('订单运费折扣税费'), 'field_name' => 'shippingDiscountTax'],
            $key++ => ['name' => L('订单包装费税费'), 'field_name' => 'giftWrapTax'],
            $key++ => ['name' => L('订单支付总价'), 'field_name' => 'payTotalPrice'],
            $key++ => ['name' => L('结算费'), 'field_name' => 'paySettlePrice'],
            $key++ => ['name' => L('结算费（USD）'), 'field_name' => 'paySettlePriceDollar'],
            $key++ => ['name' => L('下单时间'), 'field_name' => 'orderTime'],
            $key++ => ['name' => L('付款时间'), 'field_name' => 'orderPayTime'],
            $key++ => ['name' => L('发货时间'), 'field_name' => 'shippingTime'],
            $key++ => ['name' => L('收货人姓名'), 'field_name' => 'addressUserName'],
            $key++ => ['name' => L('收货人手机'), 'field_name' => 'addressUserPhone'],
            $key++ => ['name' => L('收货人电话'), 'field_name' => 'receiverTel'],
            $key++ => ['name' => L('收货人邮箱'), 'field_name' => 'userEmail'],
            $key++ => ['name' => L('买家ID'), 'field_name' => 'buyer_user_id'],
            $key++ => ['name' => L('国家'), 'field_name' => 'addressUserCountryIdNm'],
            $key++ => ['name' => L('省'), 'field_name' => 'addressUserProvinces'],
            $key++ => ['name' => L('市'), 'field_name' => 'addressUserCity'],
            $key++ => ['name' => L('区（县）'), 'field_name' => 'addressUserRegion'],
            $key++ => ['name' => L('具体地址'), 'field_name' => 'addressUserAddress1'],
            $key++ => ['name' => L('邮编'), 'field_name' => 'addressUserPostCode'],
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouseNm'],
            $key++ => ['name' => L('物流公司'), 'field_name' => 'logisticCdNm'],
            $key++ => ['name' => L('物流方式'), 'field_name' => 'logisticModel'],
            $key++ => ['name' => L('物流单号'), 'field_name' => 'trackingNumber'],
            $key++ => ['name' => L('包裹号'), 'field_name' => 'PACKING_NO'],
            $key++ => ['name' => L('用户备注'), 'field_name' => 'shippingMsg'],
            $key   => ['name' => L('运营备注'), 'field_name' => 'remarkMsg'],
        ];
        $esData                  = array_map(function ($line) {
            $num = 0;
            if ($line ['occupyNum']) {
                foreach ($line ['occupyNum'] as $key => $batch) {
                    $num += array_sum(array_column($batch ['batch'], 'num'));
                }
            }
            $line ['occupyNum'] = $num;
            return $line;
        }, $esData);
        $exportExcel->data       = $esData;
        $exportExcel->export();
    }

    /**
     * 下载模板文件
     */
    public function downloadTemplate()
    {
        $name = 'DeliverTemplate.xlsx';
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Oms/OutGoing/' . $name;
        Http::download($filename, $filename);
    }

    /**
     * 称重发货展示页
     */
    public function weightshipping()
    {
        $this->display();
    }

    /**
     * 扫描面单数据获取
     */
    public function scanTracking()
    {
        $query                = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model                = new OmsOutGoingModel();
        $esData               = $model->getListData($query, false, 'tracking');
        $data ['pageData']    = $esData;
        $data ['parmeterMap'] = $query;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 称重发货
     */
    public function scanWeightShipping()
    {
        
        $query       = ZUtils::filterBlank($this->getParams()['data']['query']);
        $tracking_numbers = array_column($query, 'trackingNumber');
        if (!empty($tracking_numbers)) {
            $data = (new Model())->table('tb_ms_ord_package pac')
                ->field(['oo.ORDER_ID', 'oo.PLAT_CD'])
                ->join('inner join tb_op_order oo on oo.ORDER_ID = pac.ORD_ID and oo.PLAT_CD = pac.plat_cd')
                ->join('inner join tb_ms_ord ord on oo.B5C_ORDER_NO = ord.ORD_ID')
                ->where(['pac.TRACKING_NUMBER'=>['in',$tracking_numbers], 'ord.WHOLE_STATUS_CD'=>['neq','N001820900']]);
            foreach ($data as $value) {
                $order_info[] = [
                    'order_id' => $value['ORDER_ID'],
                    'plat_cd'  => $value['PLAT_CD'],
                ];
            }
            if (!empty($order_info)) {
                //判断是否退款
                $res_refund = (new OmsAfterSaleService())->checkOrderRefund($order_info);
                if (true !== $res_refund) {
                    $this->ajaxRetrunRes($res_refund);
                }
            }
           
            
        }
        
        $model       = new OmsOutGoingModel();
        $model->mode = 2;
        $r           = $model->getListData($query, false, 'weightShipping', true);
        $request = [];
        foreach ($query as $value) {
            $request[] = [
                'order_id' => $value['orderId'],
                'plat_cd' => $value['platCd'],
            ];
        }
        if ($request) {
            $this->signSendOut($request, 2);
        }
        
        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);
        OrderModel::addLog($query['ordId'], 'N001820800', '出库操作');
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 格式化输出
     * @param int $code 状态码
     * @param string $info 提示信息
     * @param array $data 返回数据
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg'  => $info,
            'data' => $data
        ];

        return $response;
    }

    #标记发货接口  可重复调用  参数可为 order_id&plat_cd  or  B5C_ORDER_NO  都可以标记唯一订单
    public function signSendOut($data, $searchType){
        $where_order_str = '';
        $requestOrderArr = [];
        if ($searchType == 2) {
            //根据order_id和plat_cd条件搜索订单
            foreach ($data as $v) {
                $where_order_str .= sprintf("(tb_op_order.ORDER_ID = '%s' and tb_op_order.PLAT_CD = '%s') or  ", $v['order_id'], $v['plat_cd']);
            }
            $where_order_str = trim($where_order_str, 'or ');
            $orders_info = M('op_order', 'tb_')
                    ->field(['tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_ms_store.BEAN_CD'])
                    ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
                    ->where($where_order_str)
                    ->select();
           
        } else {
            //根据b5c_order_no条件搜索订单
            $orders_info = M('op_order', 'tb_')
                    ->field(['tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_ms_store.BEAN_CD'])
                    ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
                    ->where(['tb_op_order.B5C_ORDER_NO' => ['IN', $data]])
                    ->select();
            
           
        }
        foreach ($orders_info as $item) {
            #只允许shop nc订单            本来想直接加在where条件里  但是该字段大小不统一  避免歧义还是交给php判断
            if(strtolower($item['BEAN_CD']) == 'shopnc') {
                $requestOrderArr[] = [
                    'ORDER_ID' => $item['ORDER_ID'],
                    'PLAT_CD' => $item['PLAT_CD'],
                    'orderId' => $item['ORDER_ID'],
                    'platCd' => $item['PLAT_CD'],
                    'order_key' => $item['ORDER_ID'] . $item['PLAT_CD']
                ];
            }
        }

        if (count($requestOrderArr) > 0) {
            $request = [];
            $request['data']['orders'] = $requestOrderArr;
            Logs(['request' => $request], __FUNCTION__, 'signSendOut');
            $response_res = ApiModel::thrSendOutNew(json_encode($request), 1, 1);
            Logs(['request' => $request, 'res' => $response_res], __FUNCTION__, 'signSendOut');
        }
    }
}