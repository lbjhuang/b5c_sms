<?php

/**
 * 日志展示
 *
 */
class UserLogAction extends BaseAction
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        import('ORG.Util.Page');// 导入分页类
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
    }

    public function test()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model  = new UserLogModel();
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
     * 日志列表
     *
     */
    public function index()
    {
        $query = ZUtils::filterBlank($this->getParams());
        $_GET ['p'] = $_POST ['p'] = $query ['p'];
        $model  = new UserLogModel();
        $esData = $model->getListData($query);

        $count = $model->total;
        $result = $esData;
        $page = new Page($count, 20);
        $show = $page->ajax_show('flip');
        if (IS_POST) {
            $this->AjaxReturn(['result' => $result, 'page' => $show, 'count' => $count], 'success', 1);
        }
        $this->assignJson('page', $show);
        $this->assignJson('node', $this->getNodes());
        $this->assignJson('result', $result);
        $this->assignJson('systemSource'
            , BaseModel::systemSource());
        $this->assignJson('logType', BaseModel::logType());
        $this->assign('count', $count);
        $this->assign('params', $query);
        $this->display();
    }

    /**
     * @param $source 系统来源
     * @return array
     * 系统来源处理,选择来源,切换 index type
     */
    public function parseSystemSource($source)
    {
        return BaseModel::esSearchConf($source);
    }

    /**
     * 处理页面参数
     * @param $params
     * @return array
     *
     */
    public function parseRequestParams($params)
    {
        $query = ZUtils::filterBlank($params);

        $match = $fields = [];
        empty($query ['ip'])       or $fields [] = ['match' => ['ip'       =>  $query ['ip']]];
        empty($query ['user'])     or $fields [] = ['match' => ['user'     => $query ['user']]];

        if ($query ['noteType']) {
            if (array_flip(BaseModel::logType())[$query ['noteType']])
                $fields [] = ['match' => ['noteType' => array_flip(BaseModel::logType())[$query ['noteType']]]];
            else
                $fields [] = ['match' => ['noteType' => $query ['noteType']]];
        }
        empty($query ['model'])    or $fields [] = ['match' => ['model'    => $query ['model']]];
        empty($query ['action'])   or $fields [] = ['match' => ['action'   => $query ['action']]];
        if (!$query ['source']) $query ['source'] = 'N001950500';
        empty($query ['source'])   or $fields [] = ['match' => ['source'   =>  $query ['source']]];
        empty($fields) or $match ['query']['bool']['must'] = $fields;
        if ($query ['startTime'] and $query ['endTime']) {
            $query ['startTime'] = $query ['startTime'].' 00:00:00';
            $query ['endTime']   = $query ['endTime'] . ' 23:59:59';
            $q = [
                'cTimeStamp' => [
                    'gte' => strtotime($query ['startTime']),
                    'lte' => strtotime($query ['endTime'])
                ]
            ];
            if ($q)  $match ['query']['bool']['filter']['range'] = $q;
        }

        $size = 20;
        if ($params ['p'])
            $_GET ['p'] = $_POST ['p'] = $params ['p'];
        else
            $_GET ['p'] = 0;
        $body = [
            'from' => (($_GET ['p']-1) < 0 ? 0:($_GET ['p']-1)) * $size,
            'size' => $size,
            'sort' => [
                ['cTimeStamp' => 'desc'],
                '_score'
            ]
        ];
        if ($match) {
            $body = array_merge($body, $match);
        }

        $conf = $this->parseSystemSource($query ['source']);
        $params = [
            'index' => $conf ['index'],
            'type' => $conf ['type'],
            'body' => $body
        ];

        return $params;
    }

    /**
     * 获得权限节点
     *
     */
    public function getNodes()
    {
        $model = new Model();
        $ret = $model->table('bbm_node')->getField('ID, TITLE, NAME');
        return $ret;
    }

    public function menu_stat()
    {
        $this->display();
    }

    public function module_list()
    {
        $logic = D('Report/UserLog', 'Logic');
        $this->ajaxReturn(['code' => 200, 'data' => $logic->moduleList(), 'msg' => 'success']);
    }

    public function action_list()
    {
        $logic = D('Report/UserLog', 'Logic');
        $this->ajaxReturn(['code' => 200, 'data' => $logic->actionList(ZUtils::filterBlank($this->getParams())), 'msg' => 'success']);
    }

    public function menu_stat_list()
    {
        $logic = D('Report/UserLog', 'Logic');
        $data = $logic->listData(ZUtils::filterBlank($this->getParams()));
        $this->ajaxReturn(['code' => 200, 'data' => $data, 'msg' => 'success']);
    }

    public function testMsg()
    {
        $wechat = new WechatMsg();
        $wechat->create(['tousers' => ['redbo.he@gshopper.com'], 'msgtype' => 'textcard', 'appName' => 'ERP', 'textcard' => ['title' => 'test due','description' => '<div class="gray">2016年9月26日</div> <div class="normal">恭喜你抽中iPhone 7一台，领奖码：xxxx</div><div class="highlight">请于2016年10月10日前联系行政同事领取</div>', 'url' => ReviewModel::getBtnUrl('hahaha'), 'btntxt' => '查看详情']]);
        $res = $wechat->send();
    }

    public function testReview()
    {
        if (!RedisLock::lock("due-lock")) {
            die('redis locked error');
        }
        $review = [
            'review_type' => 'WMS',
            'order_id' => 56,
            'order_no' => 'DB201903070002',
            'allowed_man_json' => ['due', 'yansu'],
            'detail_json' => [
                'data' => [
                    'team' => '不变',
                    'warehouse' => '宝山仓->香港',
                    'apply' => 'feisong',
                    'apply_time' => '2019-03-09 12:12:12',
                ],
                'keys' => [
                    'team' => '团队',
                    'warehouse' => '仓库',
                    'apply' => '发起人',
                    'apply_time' => '发起时间',
                ],
                'config' => [
                    'view_type' => 'allo',
                    'agree_btn' => 1,
                    'refuse_btn' => 1,
                    'refuse_text' => 0,
                    'agree_text' => 0,
                    'detail_btn' => 1,
                    'detail_url' => 'http://www.scmgit.com/index.php?m=allocation_extend&a=show&id=1739&storageKey=storage0003'
                ]
            ],
            'callback_function' => 'db_exam',
        ];
        $msg = [
            'tousers' => ['due@gshopper.com'],
            'textcard' => [
                'title' => '调拨申请审批',
                'description' => '<div class="gray">调拨单号：DBXXXXXXX</div> <div class="normal">团队：不变</div><div class="highlight">仓库：宝山仓->香港</div><div class="gray">发起人：feisong</div><div class="gray">发起时间：2019-03-09 12:12:12</div>',
                'btntxt' => '查看详情'
            ]
        ];
        (new ReviewMsg())->create($review)->send($msg);
        RedisLock::unlock();
    }

    public function testTr()
    {
//        ProductTransfer::purConvertHandle(1, 'N002720001');
        ProductTransfer::approveMsg(M('conversion', 'tb_scm_')->find(56), M('conversion_details', 'tb_scm_')->where(['conversion_id' => 56])->select());
    }
}