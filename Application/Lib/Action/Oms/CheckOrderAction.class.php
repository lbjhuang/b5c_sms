<?php
/**
 * 核单
 * User: b5m
 * Date: 2018/3/1
 * Time: 13:02
 */

class CheckOrderAction extends BaseAction
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
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        $this->accountLog = new TbWmsAccountBankLogModel();
        $this->mail       = new ExtendSMSEmail();
        $this->esClient   = new ESClientModel();
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        parent::_initialize();
        B('SYSOperationLog');
    }

    /**
     * 核单页面跳转
     */
    public function checkingList()
    {
        $this->display();
    }

    public function checkingDetail()
    {
        $this->display();
    }

    /**
     * 核单列表页
     */
    public function checkListData()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model  = new CheckOrderModel();
        $esData = $model->getListData($query);
        $query ['pageIndex']<0?$query ['pageIndex'] = 1:$query['pageIndex'] = $query ['pageIndex'];
        $query ['pageSize']?$size = $query ['pageSize']:$size = 20;
        $data ['pageIndex']       = $query ['pageIndex'];
        $data ['pageSize']        = $size;
        $data ['totalPage']       = ceil($model->total / $data ['pageSize']);
        $data ['pageData']        = $esData;
        $data ['parmeterMap']     = $query;
        $data ['totalCount']      = $model->total;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 获取需要扫描的数据
     * TRACKING_NUMBER 通过运单号|面单号获取数据
     */
    public function getScanData()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new CheckOrderModel();
        $esSearch = new EsSearchModel();
        $q = $esSearch
            ->where(['msOrd.wholeStatusCd' => ['and', 'N001820700']])
            ->where(['ordPackage.trackingNumber' => ['or', $query ['trackingNumber']]])
            ->where(['b5cOrderNo' => ['or', $query ['trackingNumber']]])
            ->where(['orderId' => ['or', $query ['trackingNumber']]])
            ->where(['packingNo' => ['or', $query ['trackingNumber']]])
            ->minimum()
            ->setMissing(['and', ['childOrderId']])
            ->getQuery();
        $esData = $model->esClient->search($q)['hits']['hits'];
        if (in_array($esData[0]["_source"]['bwcOrderStatus'], ['N000550900', 'N000551000', 'N000550300'])) {//关闭，取消 //待付款
            $ret = (new OrderBackModel())->preCheckAndDone(['ordId' => [$esData[0]["_source"]['b5cOrderNo']], 'preDone' => $query['preDone']]);
            $response = $this->formatOutput(3000, L('订单已取消/关闭/待付款，核单失败'), $ret);
            $this->ajaxReturn($response, 'json');
        }
        $check_repeat_data = $this->check_repeat_data($esData);
        if ($check_repeat_data) {
            $response = $this->formatOutput(3006, L('订单ID数据有重复，请先核对'), $check_repeat_data);
            $this->ajaxReturn($response, 'json');
        }
        //和数据库数据进行比对，防止es未更新导致的可以重复扫描核单问题
        $db_res = (new Model())->table('tb_op_order op')
            ->field('ord.WHOLE_STATUS_CD,op.ORDER_ID,op.PLAT_CD')
            ->join('tb_ms_ord ord on ord.THIRD_ORDER_ID = op.ORDER_ID AND ord.PLAT_FORM = op.PLAT_CD')
            ->where(['op.ORDER_ID'=>$esData[0]["_source"]['orderId'], 'PLAT_CD'=>$esData[0]["_source"]['platCd']])
            ->find();
        if ($db_res['WHOLE_STATUS_CD'] != 'N001820700') {
            //不是待核单状态更新es并报错
            $order_es_info[] = [
                'opOrderId' => $db_res['ORDER_ID'],
                'platCd'    => $db_res['PLAT_CD'],
            ];
            ApiModel::updateOrderFromEs($order_es_info, __FUNCTION__);
            $response = $this->formatOutput(3000, L('该订单已核单成功，请不要重复核单'), []);
            $this->ajaxReturn($response, 'json');
        }

        $data = $model->parseData($esData);
        $orderCenterService = new OrderCenterService();
        $data  = $orderCenterService->parseUpcMoreData($data, 2);
        $data ['data']['parmeterMap'] = $query;
        $data ['data']['total'] = count($esData);
        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);
        if ($data['code' != '2000']) {
            Logs($q, __FUNCTION__.'-search', 'fm');
        }


        $this->ajaxReturn($response, 'json');
    }

    // 根据id检测ES是否有订单重复数据
    public function check_repeat_data($data = [])
    {
        $res = false;
        if ($data) {
            $check_data = [];
            foreach ($data as $key => $value) {
                $check_data[] = $value['_source']['id'];
            }
            if (count($check_data) != count(array_unique($check_data))) {
                $res['pre_data'] = $check_data;
                $res['unique_data'] = array_unique($check_data);
            }
        }
        return $res;
    }

    /**
     * 扫描核单
     */
    public function scanCheckOrder()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new CheckOrderModel();
        $esSearch = new EsSearchModel();
//        $q = $esSearch
//            ->where(['ordPackage.trackingNumber' => ['or', $query ['trackingNumber']]])
//            ->where(['b5cOrderNo' => ['or', $query ['trackingNumber']]])
//            ->where(['orderId' => ['or', $query ['trackingNumber']]])
//            ->getQuery();
        $q = $esSearch
            ->where(['msOrd.wholeStatusCd' => ['and', 'N001820700']])
            ->where(['ordPackage.trackingNumber' => ['or', $query ['trackingNumber']]])
            ->where(['b5cOrderNo' => ['or', $query ['trackingNumber']]])
            ->where(['orderId' => ['or', $query ['trackingNumber']]])
            ->where(['packingNo' => ['or', $query ['trackingNumber']]])
            ->minimum()
            ->setMissing(['and', ['childOrderId']])
            ->getQuery();

        $esData = $model->esClient->search($q)['hits']['hits'];
        $esData = $model->parseData($esData);

        $data = $model->contrastCsanOrder($esData ['data']['pageData'], $query ['scanData']);

        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);
        OrderModel::addLog(array_keys($query['scanData']),'N001820700','核单操作');
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 一键通过
     */
    public function oneKeyThrough()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new CheckOrderModel();
        $esSearch = new EsSearchModel();
        if (!empty($query ['ordId'])) {
            $q = $esSearch
                ->where(['msOrd.ordId' => ['and', $query ['ordId']]])
                ->getQuery();
            $esData = $model->esClient->search($q)['hits']['hits'];
            $esData = $model->parseData($esData);
            $data = $model->oneKeyThrough($esData);
        } else {
            $data ['code'] = 3003;
            $data ['info'] = L('无数据');
            $data ['data']['pageData'] = null;
            $data ['data']['parmeterMap'] = $query;
        }

        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 万能码通过
     */
    public function universalCodeThrough()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new CheckOrderModel();
        $esSearch = new EsSearchModel();
        $q = $esSearch
            ->where(['ordPackage.trackingNumber' => ['and', $query ['trackingNumber']]])
            ->getQuery();

        $esData = $model->esClient->search($q)['hits']['hits'];
        $esData = $model->parseData($esData);
        $data = $model->oneKeyThrough($esData);

        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 导出
     */
    public function export()
    {
        $query = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true)['data']['query']);
        $model  = new CheckOrderModel();
        $esData = $model->getListData($query, false, true);
        $exportExcel = new ExportExcelModel();
        $exportExcel->attributes = [
            'A' => ['name' => L('第三方订单ID'), 'field_name' => 'orderId'],
            'B' => ['name' => L('第三方订单编号'), 'field_name' => 'orderId'],
            'C' => ['name' => L('店铺'), 'field_name' => 'storeName'],
            'D' => ['name' => L('物流公司'), 'field_name' => 'expeCompany'],
            'E' => ['name' => L('物流单号'), 'field_name' => 'trackingNumber'],
            'F' => ['name' => L('商品标题'), 'field_name' => 'gudsNm'],
            'G' => ['name' => L('B5C SKU ID'), 'field_name' => 'skuIds'],
            'H' => ['name' => L('第三方SKU编号'), 'field_name' => 'skuIds'],
            'I' => ['name' => L('下单时间'), 'field_name' => 'orderTime'],
            'J' => ['name' => L('付款时间'), 'field_name' => 'orderPayTime'],
            'K' => ['name' => L('发货时间'), 'field_name' => 'shippingTime'],
            'L' => ['name' => L('商品SKU属性'), 'field_name' => 'gudsOptValMpngNm'],
            'M' => ['name' => L('收货人姓名'), 'field_name' => 'addressUserName'],
            'N' => ['name' => L('收货人电话'), 'field_name' => 'addressUserPhone'],
            'O' => ['name' => L('收货人固话'), 'field_name' => 'addressUserPhone'],
            'P' => ['name' => L('收货人地址'), 'field_name' => 'addressUserAddress1'],
            'Q' => ['name' => L('收货人地址2'), 'field_name' => 'addressUserAddress2'],
            'R' => ['name' => L('邮编'), 'field_name' => 'addressUserPostCode'],
            'S' => ['name' => L('买家手机'), 'field_name' => 'buyerMobile'],
            'T' => ['name' => L('买家固话'), 'field_name' => 'buyerTel'],
            'U' => ['name' => L('数量'), 'field_name' => 'occupyNum'],
            'V' => ['name' => L('单价'), 'field_name' => 'payPrice'],
            'W' => ['name' => L('备注'), 'field_name' => 'remarkMsg']
        ];
        $exportExcel->data = $esData;
        $exportExcel->export();
    }

    /**
     * 格式化输出
     * @param int    $code     状态码
     * @param string $info     提示信息
     * @param array  $data     返回数据
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
}