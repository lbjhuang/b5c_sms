<?php

/**
 * 拣货接口
 * User: b5m
 * Date: 2018/2/1
 * Time: 18:55
 */
class PickingAction extends BaseAction
{
    protected $whiteList = [];

    /**
     * 路由跳转方法
     *
     */
    public function pickingList()
    {
        $this->display();
    }

    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        parent::_initialize();
        B('SYSOperationLog');
    }

    /**
     * 拣货列表页
     */
    public function pickingListData()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new PickingModel();
        $esData = $model->getListData($query);

        $query ['pageIndex'] < 0 ? $query ['pageIndex'] = 1 : $query['pageIndex'] = $query ['pageIndex'];
        $query ['pageSize'] ? $size = $query ['pageSize'] : $size = 20;
        $data ['pageIndex'] = $query ['pageIndex'];
        $data ['pageSize'] = $size;
        $data ['totalPage'] = ceil($model->total / $data ['pageSize']);
        $data ['pageData'] = $esData;
        $data ['parmeterMap'] = $query;
        $data ['totalCount'] = $model->total;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 打印拣货单页面跳转
     */
    public function printOrder()
    {
        $this->display('printOrder');
    }


    /**
     * 打印拣货单页面跳转
     */
    public function pickingResult()
    {
        $this->display('pickingResult');
    }

    /**
     * 拣货单预览数据
     */
    public function previewOrder()
    {
        $params   = ZUtils::filterBlank($this->getParams()['data']['query']);
        $params['isExternal'] = $this->getParams()['data']['query']['isExternal'];

        $model    = new PickingModel();
        $esSearch = new EsSearchModel();
        $q        = $esSearch
            ->where(['b5cOrderNo' => ['and', $params ['ordId']]])
            ->page(0, count($params ['ordId']))
            ->setMissing(['and', ['childOrderId']])
            ->getQuery();
        $data     = $model->esClient->search($q);
        $data     = $model->parseData($data ['hits']['hits'], false, $params ['isExternal']);
        $orderCenterService = new OrderCenterService();
        $type = isset($params ['isExternal']) ? $params ['isExternal'] : 1;
        $data     = $orderCenterService->parseUpcMoreData($data,$type);
        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);

        $this->ajaxReturn($response, 'json');
    }

    public function testGetAll()
    {
        $esSearch = new EsSearchModel();
        $q = $esSearch->getQuery();
        $model = new PickingModel();
        var_dump($model->esClient->search($q) ['hits']['hits']);
    }

    /**
     * 打印拣货单
     */
    public function printOrders()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new PickingModel();
        $esSearch = new EsSearchModel();
        $q = $esSearch
            ->where(['b5cOrderNo' => ['and', $params ['ordId']]])
            ->page(0, count($params ['ordId']))
            ->setDefault(['and', ['msOrd']])
            ->getQuery();
        $model->pickingNo = $params ['pickingNo'];
        $data = $model->esClient->search($q);
        $data = $model->parseData($data ['hits']['hits'], true);
        $response = $this->formatOutput($data ['code'], $data ['info'], $data ['data']);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 拣货一键通过
     */
    public function oneKeyThrough()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new PickingModel();
        $esSearch = new EsSearchModel();
        $q = $esSearch
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->setMissing(['and', ['childOrderId']])
            ->setDefault(['and', ['msOrd']])
            ->getQuery();

        $data = $model->esClient->search($q);
        $data = $model->parseData($data ['hits']['hits'], false);
        $r = $model->oneKeyThrough($data);
        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 操作日志
     *
     */
    public function operationLog()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $_GET ['p'] = $_POST ['p'] = $params ['pageIndex'] == Null ? 1 : $params ['pageIndex'];

        $fields = [
            "create_user",
            "create_time",
            "msg",
        ];

        $model = new TbWmsAccountBankLogModel();
        $model->orderNo = $params ['orderNo'] ? ['eq', $params ['orderNo']] : '';

        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $count = $model->where($model->params)->count();

        $page = new Page($count, $size);
        $ret = $model->field($fields)->where($model->params)->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();
        $allUserName = TbWmsAccountTransferModel::getAllUserName();
        foreach ($ret as $key => &$value) {
            $value ['create_user'] = $allUserName [$value ['create_user']];
            unset($value);
        }
        $data ['pageNo'] = $_GET ['p'];
        $data ['pageSize'] = $size;
        $data ['totalPage'] = ceil($count / $data ['pageSize']);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $count;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 拣货导出
     */
    public function export()
    {
        $query = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true)['data']['query']);
        $model  = new PickingModel();
        $esData = $model->getListData($query, false, 'excel');
        $exportExcel = new ExportExcelModel();
        $exportExcel->attributes = [
            'A' => ['name' => L('站点'), 'field_name' => 'platName'],
            'B' => ['name' => L('店铺'), 'field_name' => 'storeName'],
            'C' => ['name' => L('订单号'), 'field_name' => 'b5cOrderNo'],
            'D' => ['name' => L('第三方订单号'), 'field_name' => 'orderNo'],
            'E' => ['name' => L('商品名称'), 'field_name' => 'name'],
            'F' => ['name' => L('商品编码'), 'field_name' => 'skuIds'],
            'G' => ['name' => L('包裹类型'), 'field_name' => 'gudsType'],
            'H' => ['name' => L('销售团队'), 'field_name' => 'saleTeam'],
            'I' => ['name' => L('下单时间'), 'field_name' => 'orderTime'],
            'J' => ['name' => L('下发仓库'), 'field_name' => 'warehouseNm'],
            'K' => ['name' => L('物流公司'), 'field_name' => 'expeCompany'],
            'L' => ['name' => L('物流方式'), 'field_name' => 'logisticModel'],
            'M' => ['name' => L('面单'), 'field_name' => 'surfaceWayGetCd'],
            'N' => ['name' => L('运单号'), 'field_name' => 'trackingNumber'],
            'O' => ['name' => L('包裹号'), 'field_name' => 'PACKING_NO'],
            'P' => ['name' => L('备注'), 'field_name' => 'remarkMsg'],
            'Q' => ['name' => L('拣货异常'), 'field_name' => 'msgCd1'],
            'R' => ['name' => L('买家ID'), 'field_name' => 'buyer_user_id']
        ];
        $exportExcel->data = $esData;
        $exportExcel->export();
    }

    /**
     * 格式化输出
     * @param int    $code 状态码
     * @param string $info 提示信息
     * @param array  $data 返回数据
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg' => $info,
            'data' => $data
        ];

        return $response;
    }

}