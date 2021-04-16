<?php

/**
 * store data model    data
 * by:    huanzhu
 * date: 2017/10/17
 */

use GuzzleHttp\Client;
class TbMsStoreModel extends BaseModel
{
    protected $trueTableName = 'tb_ms_store';
    protected $_validate = [
        ['STORE_NAME', 'require', '请填写店铺名称'],//默认情况下用正则进行验证
        ['MERCHANT_ID', 'require', '请填写店铺别名'],
        ['STORE_PWD', 'require', '请填写开店密码'],
        ['COUNTRY_ID', 'require', '请选择国家'],//默认情况下用正则进行验证
        ['PLAT_CD', 'require', '请选择平台'],//默认情况下用正则进行验证
        // ['SALE_TEAM_CD', 'require', '请选择销售团队'],//默认情况下用正则进行验证
        ['USER_ID', 'require', '请填写负责人联系方式'],//默认情况下用正则进行验证
        ['STORE_STATUS', 'require', '请填写负责人联系方式'],
        ['store_by', 'require', '请填写店铺负责人'],
        ['IS_VAT', 'require', '请选择是否交VAT'],

        // 店铺管理2.0
        ['up_shop_time', 'require', '请选择开店日期'],
        ['up_shop_num', 'require', '请填写店铺账号'],

        ['proposer_email', 'require', '请填写申请邮箱'],
        ['proposer_phone', 'require', '请填写申请手机号码'],
        ['proposer_by', 'require', '请选择申请人'],
        ['is_fee', 'require', '请填写是否需押金或收取费用'],
        ['credit_card_explain', 'require', '请填写信用卡绑定情况'],
        ['shop_manager_id', 'require', '	店铺负责人ID'],

    ];

    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback'],
    ];

    protected $_status_transform = [
        'STORE_STATUS' => [
            0 => '运营中',
            1 => '未运营',
        ],
        'OPERATION_TYPE' => [
            0 => 'B2C',
            1 => 'B2B2C',
            2 => 'B5C',
        ]
    ];


    private function getJson()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        return $data;
    }

    public function getParams()
    {
        return $_REQUEST;
    }

    //处理时间格式
    public function getKeyInfo($dataList = array())
    {
        foreach ($dataList as $k => $v) {

            $authStatus = json_decode($v['APPKES'], 1);
            //var_dump($authStatus);
            if (count($authStatus) > 0) {
                $dataList[$k]['STATUS'] = '已授权';
            } else {
                $dataList[$k]['STATUS'] = '未授权';
            }
            if ($v['SALE_TEAM_CD']) {
                if ($saleTeam = M('ms_cmn_cd', 'tb_')->where('CD=' . "'" . $v['SALE_TEAM_CD'] . "'")->field('CD_VAL,USE_YN,ETC')->cache(true, 3)->find()) {
                    $dataList[$k]['SALE_TEAM'] = $saleTeam['CD_VAL'];
                } else { // 多个销售team,逗号隔开返回
                    if (strpos($v['SALE_TEAM_CD'], ",")) { // 表明有多个
                        $sale_team_arr = explode(',', $v['SALE_TEAM_CD']);
                        foreach ($sale_team_arr as $key => $value) {
                            $saleTeamSingle = '';
                            $saleTeamSingle = M('ms_cmn_cd', 'tb_')->where('CD=' . "'" . $value . "'")->field('CD_VAL,USE_YN,ETC')->cache(true, 3)->find();
                            $dataList[$k]['SALE_TEAM'] .= $saleTeamSingle['CD_VAL'] . ',';
                        }
                        $dataList[$k]['CD_VAL'] = rtrim($dataList[$k]['SALE_TEAM'], ',');
                    }
                }
            }
            if ($v['PLAT_CD']) {
                if ($plat = M('ms_cmn_cd', 'tb_')->where('CD=' . "'" . $v['PLAT_CD'] . "'")->field('CD_VAL,USE_YN,ETC')->cache(true, 3)->find()) {
                    $dataList[$k]['PLAT_NAME'] = $plat['CD_VAL'];
                }
            }
            if ($v['AUTH_TIME'] == '0000-00-00 00:00:00') {
                $dataList[$k]['AUTH_TIME'] = '';
            }
        }
        return $dataList;
    }

    /**
     * 列表数据
     * @return array
     */
    public function getStoreList()
    {
        $data = $this->getJson();;
        $params = $data['search'];
        $where = $this->searchWhere($params);
        // 取消多级连表
        $count = M('store', 'tb_ms_')
            ->join("LEFT JOIN tb_ms_user_area ON tb_ms_store.COUNTRY_ID = tb_ms_user_area.id")
            ->where($where)
            ->count();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($data['pages']) && !empty($data['pages']['per_page']) && !empty($data['pages']['current_page'])){
            $pages = array(
                'per_page' =>$data['pages']['per_page'],
                'current_page' => $data['pages']['current_page']
            );
        }
        $list = M('store', 'tb_ms_')
            ->field("tb_ms_user_area.zh_name,tb_ms_store.*")
            ->join("LEFT JOIN tb_ms_user_area ON tb_ms_store.COUNTRY_ID = tb_ms_user_area.id")
            ->where($where)
            ->order('ID desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        $list = CodeModel::autoCodeTwoVal($list, ['PLAT_CD','company_cd', 'default_timezone_cd']);
       
        $userData = $this->getUserData();
        foreach ($list as &$value){
            $value['PLAT_NAME'] = $value['PLAT_CD_val'];
            $value['proposer_by_name'] = $userData[$value['proposer_by']];
            $value['handover_by_name'] = $userData[$value['handover_by']];
            $value['store_by_name'] =  $value['store_by'];
            if ($value['OPERATION_TYPE'] == '0') {
                $value['OPERATION_ZN_TYPE'] = 'B2C';
            } elseif ($value['OPERATION_TYPE'] == '1') {
                $value['OPERATION_ZN_TYPE'] = 'B2B2C';
            } else {
                $value['OPERATION_ZN_TYPE'] = 'B5C';
            }
            $value['company'] = $value['company_cd_val'];
            if (empty(json_decode($value['APPKES']))){
                $value['STATUS'] = "未授权";
            }else{
                $value['STATUS'] = "已授权";
            }
            $team_data = array();
            $sale_team_data = explode(',',$value['SALE_TEAM_CD']);
            foreach ($sale_team_data as $v){
                $temp_data = array(
                    'sale_team_cd' => $v
                );
                $team_data[] = $temp_data;
            }
            $team_data = CodeModel::autoCodeTwoVal($team_data,['sale_team_cd']);
            $value['SALE_TEAM'] = implode(',',array_column($team_data,'sale_team_cd_val'));
            $value['supWare'] = '上海一般贸...';
            $value['supLog'] = '出口易香港...';
        }
        return array($list,$count);
    }
    /**
     *  组装列表查询
     * @return array
     */
    private function searchWhere($params)
    {
        $where = array("1 = 1");
        if (is_array($params) && !empty($params)){
            //   国家
            if (isset($params['COUNTRY_ID']) && !empty($params['COUNTRY_ID'])){
                $where['COUNTRY_ID'] = array('in',explode(',',$params['COUNTRY_ID']));
            }
            //   平台
            if (isset($params['PLAT_CD']) && !empty($params['PLAT_CD'])){
                $where['PLAT_CD'] = array('in',explode(',',$params['PLAT_CD']));
            }
            //   销售团队
            if (isset($params['SALE_TEAM_CD']) && !empty($params['SALE_TEAM_CD'])){
                $team_cd = explode(',',$params['SALE_TEAM_CD']);
                $where_data = array();
                foreach ($team_cd as $value){
                    $where_data[] = array('like','%'.$value.'%');
                }
                array_push($where_data,'or');
                $where['SALE_TEAM_CD'] = $where_data;
            }
            //   店铺状态
            if (isset($params['store_status']) && $params['store_status'] != ""){
                $where['store_status'] = $params['store_status'];
            }
            //  授权
            if (isset($params['status']) && $params['status'] != ""){
                if ($params['status'] == 'none') $where['APPKES'] = array('in', array('', '{}'));
                if ($params['status'] == 'has') $where['APPKES'] = array('not in', array('', '{}'));
            }
            //   店铺名称
            if (isset($params['STORE_NAME']) && !empty($params['STORE_NAME'])){
                $name_data['STORE_NAME']  = array('like', '%'.$params['STORE_NAME'].'%');
                $name_data['MERCHANT_ID']  = array('like','%'.$params['STORE_NAME'].'%');
                $name_data['_logic'] = 'or';
                $where['_complex'] = $name_data;
            }
            //   店铺负责人
            if (isset($params['shop_manager_id']) && !empty($params['shop_manager_id'])){
                $store_by_data = array();
                $shop_manager_data = explode(',',$params['shop_manager_id']);
                foreach ( $shop_manager_data as $value){
                    $name = DataModel::getUserNameById($value);
                    array_push($store_by_data,$name);
                }
                $where['store_by'] = array('in',$store_by_data);
            }
            //   注册公司
            if (isset($params['company_cd']) && !empty($params['company_cd'])){
                $where['company_cd'] = array('in',explode(',',$params['company_cd']));
            }
            //   收入公司
            if (isset($params['income_company_cd']) && !empty($params['income_company_cd'])){
                $where['income_company_cd'] = array('in',explode(',',$params['income_company_cd']));
            }
            //   是否交VAT
            if (isset($params['IS_VAT']) &&  $params['IS_VAT'] != ""){
                $where['IS_VAT'] = $params['IS_VAT'];
            }

            //  运营人员
            if (isset($params['up_shop_num']) &&  $params['up_shop_num'] != ""){
                $where['up_shop_num']  = array('like','%'.$params['up_shop_num'].'%');
            }

            //  运营人员
            if (isset($params['USER_ID']) &&  $params['USER_ID'] != ""){
                $where['MERCHANT_ID']  = array('like','%'.$params['USER_ID'].'%');
            }

            //   交接人
            if (isset($params['handover_by']) && !empty($params['handover_by'])){
                $where['handover_by'] = array('in',explode(',',$params['handover_by']));
            }


            // 最近确认时间
            if (isset($params['recently_statr_time']) && isset($params['recently_end_time'])
                && !empty($params['recently_end_time']) &&  !empty($params['recently_statr_time'])){
                $where['recently_affirm_time'] = array('between',array($params['recently_statr_time'],$params['recently_end_time']));
            }

            // 店铺类型
            if (isset($params['store_type_cd']) && !empty($params['store_type_cd'])){
                $where['store_type_cd'] = array('in',explode(',',$params['store_type_cd']));
            }
            //   实际运营店铺ID
            if (isset($params['reality_opt_store_id']) && !empty($params['reality_opt_store_id'])){
                $where['reality_opt_store_id'] = $params['reality_opt_store_id'];
            }
            //   合作方公司名称
            if (isset($params['supplier_id']) && !empty($params['supplier_id'])){
                $where['supplier_id'] = array('in',explode(',',$params['supplier_id']));
            }

        }
        return $where;
    }

    /**
     * 添加与编辑店铺
     * @return bool
     * @throws Exception
     */
    public function newAddStore($request_data)
    {
        $request_data = CodeModel::autoCodeOneVal($request_data,['PLAT_CD']);
        // 通过ID 来验证编辑还是添加
        if (isset($request_data['ID']) && !empty($request_data['ID'])) {
            //编辑店铺校验店铺名+站点是否重复 排查自身ID
            $store_data = M("store", "tb_ms_")->field('ID')->where(['STORE_NAME' => $request_data['STORE_NAME'], 'PLAT_CD' => $request_data['PLAT_CD'], ['ID' => ['neq', $request_data['ID']]]])->find();
            if (!empty($store_data)){
                throw new Exception('店铺名+站点不可重复！');
            }

            $store_data = M("store", "tb_ms_")->field('ID,shop_manager_id,store_by,handover_by,STORE_PWD,default_timezone_cd')->where(['ID' => $request_data['ID']])->find();
            if (empty($store_data)){
                throw new Exception('店铺不存在');
            }
//            if(!empty($store_data['default_timezone_cd']) && $store_data['default_timezone_cd']!= $request_data['default_timezone_cd']){
//                throw new Exception('运营后台默认时区已填写，无法修改。');
//            }
            // 店铺负责人不同 交接
            $handover_by = 0;

            if (!empty($store_data['handover_by'])){
                $handover_by = $request_data['store_by'];
            }else {
                if ($store_data['store_by'] != DataModel::getUserIdByName($request_data['store_by'])) {
                    $handover_by = $request_data['store_by'];
                }
            }

            // 密码处理
            if ($request_data['STORE_PWD'] == "*****"){
                $request_data['STORE_PWD'] = $store_data['STORE_PWD'];
            }

            // 编辑店铺  组装数据  SALE_TEAM_CD
            $save_data = array(
                "STORE_NAME" => $request_data['STORE_NAME'],
                "STORE_PWD" => $request_data['STORE_PWD'],
                "PLAT_CD" => $request_data['PLAT_CD'],
                "USER_ID" => $request_data['USER_ID'],
//                "store_by" => DataModel::getUserNameById($request_data['store_by']),
//                "ORDER_SWITCH" => $request_data['ORDER_SWITCH'],
                "COUNTRY_ID" => $request_data['COUNTRY_ID'],
                "STORE_BACKSTAGE_URL" => $request_data['STORE_BACKSTAGE_URL'],
                "STORE_INDEX_URL" => $request_data['STORE_INDEX_URL'],
                "MERCHANT_ID" => $request_data['MERCHANT_ID'],
                "PRODUCT_DETAIL_URL_MARK" => $request_data['PRODUCT_DETAIL_URL_MARK'],
                "STORE_STATUS" => $request_data['STORE_STATUS'],
                "OPERATION_TYPE" => $request_data['OPERATION_TYPE'],
//                "SEND_ORD_TYPE" => $request_data['SEND_ORD_TYPE'],
                "DELIVERY_STATUS" => $request_data['DELIVERY_STATUS'],
                "IS_VAT" => $request_data['IS_VAT'],
                "company_cd" => $request_data['company_cd'],
                "PLAT_NAME" => $request_data['PLAT_CD_val'],
                "SALE_TEAM_CD" => $request_data['SALE_TEAM_CD'],

                "UPDATE_USER_ID" => $_SESSION['userId'],
                "UPDATE_TIME" => date("Y-m-d H:i:s"),


                "plat_explain" => $request_data['plat_explain'],
                "up_shop_time" => $request_data['up_shop_time'],
                "up_shop_num" => $request_data['up_shop_num'],
                "proposer_email" => $request_data['proposer_email'],
                "proposer_phone" => $request_data['proposer_phone'],
                "proposer_by" => $request_data['proposer_by'],
                "is_fee" => $request_data['is_fee'],
                "remark" => $request_data['remark'],
                "credit_card_explain" => $request_data['credit_card_explain'],
//                "shop_manager_id" => $request_data['shop_manager_id'],
                "handover_by" => $handover_by,
                "product_id" => $request_data['product_id'],
                "sell_small_team_cd" => $request_data['sell_small_team_cd'],
                "default_timezone_cd" => $request_data['default_timezone_cd'],
                "scan_time" => $request_data['scan_time'],
                "scan_switch" => $request_data['scan_switch'],

                "store_type_cd" => $request_data['store_type_cd'],
                "reality_opt_store_id" => $request_data['reality_opt_store_id'],
                "supplier_id" => $request_data['supplier_id'],
            );
            // 增加操作日志
            $store_log = new StoreLogService();
            $log_data = $store_log->getUpdateMessage("tb_ms_store",['ID'=>$request_data['ID']],$save_data,1,$request_data['ID']);


            $log_data = array_map(function ($value) {
                if ($value['field_name'] == '运营后台时间默认时区') {
                    #转换code
                    $tmpCode = CodeModel::getCodeKeyValArr([$value['front_value'], $value['later_value']]);
                    $value['front_value'] = !empty($tmpCode[$value['front_value']]) ? $tmpCode[$value['front_value']] : $value['front_value'];
                    $value['later_value'] = !empty($tmpCode[$value['later_value']]) ? $tmpCode[$value['later_value']] : $value['later_value'];
                }
                return $value;
            }, $log_data);
            $res = M("store", "tb_ms_")->where(['ID' => $request_data['ID']])->save($save_data);
            if ($res === false) {
                throw new Exception('修改店铺失败');
            }
            $res = M("store_sales", "tb_ms_")->where(['ID' => $request_data['ID']])->delete();
            if ($res === false) {
                throw new Exception('编辑处理销售团队有误');
            }
            // 添加日志
            if (!empty($log_data)){
                $ret = $store_log->addLog($log_data);
                if ($ret === false) {
                    throw new Exception('添加日志失败');
                }
            }
            $ID = $request_data['ID'];
        } else {
            //新增店铺校验店铺名+站点是否重复
            $store_data = M("store", "tb_ms_")->field('ID')->where(['STORE_NAME' => $request_data['STORE_NAME'], 'PLAT_CD' => $request_data['PLAT_CD']])->find();
            if (!empty($store_data)){
                throw new Exception('店铺名+站点不可重复！');
            }
            $add_data = array(
                "STORE_NAME" => $request_data['STORE_NAME'],
                "STORE_PWD" => $request_data['STORE_PWD'],
                "PLAT_CD" => $request_data['PLAT_CD'],
                "USER_ID" => $request_data['USER_ID'],
                "store_by" => DataModel::getUserNameById($request_data['store_by']),
//                "ORDER_SWITCH" => $request_data['ORDER_SWITCH'],
                "COUNTRY_ID" => $request_data['COUNTRY_ID'],
                "STORE_BACKSTAGE_URL" => $request_data['STORE_BACKSTAGE_URL'],
                "STORE_INDEX_URL" => $request_data['STORE_INDEX_URL'],
                "MERCHANT_ID" => $request_data['MERCHANT_ID'],
                "PRODUCT_DETAIL_URL_MARK" => $request_data['PRODUCT_DETAIL_URL_MARK'],
                "STORE_STATUS" => $request_data['STORE_STATUS'],
                "OPERATION_TYPE" => $request_data['OPERATION_TYPE'],
//                "SEND_ORD_TYPE" => $request_data['SEND_ORD_TYPE'],
                "DELIVERY_STATUS" => $request_data['DELIVERY_STATUS'],
                "IS_VAT" => $request_data['IS_VAT'],
                "company_cd" => $request_data['company_cd'],
                "PLAT_NAME" => $request_data['PLAT_CD_val'],
                "SALE_TEAM_CD" => $request_data['SALE_TEAM_CD'],

                "CREATE_USER_ID" => $_SESSION['userId'],
                "CREATE_TIME" => date("Y-m-d H:i:s"),

                "plat_explain" => $request_data['plat_explain'],
                "up_shop_time" => $request_data['up_shop_time'],
                "up_shop_num" => $request_data['up_shop_num'],
                "proposer_email" => $request_data['proposer_email'],
                "proposer_phone" => $request_data['proposer_phone'],
                "proposer_by" => $request_data['proposer_by'],
                "is_fee" => $request_data['is_fee'],
                "remark" => $request_data['remark'],
                "credit_card_explain" => $request_data['credit_card_explain'],
                "shop_manager_id" => $request_data['shop_manager_id'],
                "product_id" => $request_data['product_id'],
                "sell_small_team_cd" => $request_data['sell_small_team_cd'], 
                "default_timezone_cd" => $request_data['default_timezone_cd'],
                "scan_time" => $request_data['scan_time'],
                "scan_switch" => $request_data['scan_switch'],

                "store_type_cd" => $request_data['store_type_cd'],
                "reality_opt_store_id" => $request_data['reality_opt_store_id'],
                "supplier_id" => $request_data['supplier_id'],
            );
            $res = M("store", "tb_ms_")->add($add_data);
            if ($res === false) {
                throw new Exception('添加店铺失败');
            }
            $ID = $res;
        }
        $tema_data = explode(',',$request_data['SALE_TEAM_CD']);
        if (empty($tema_data)){
            throw new Exception('销售团队数据有误');
        }
        $tema_add_data = array();
        foreach ($tema_data as $value) {
            $data = array(
                'store_id' => $ID,
                'sale_team_cd' => $value,
                'created_at' => date('Y-m-d H:i:s'),
            );
            $tema_add_data[] = $data;
        }
        if (!empty($tema_add_data)){
            $res = M("store_sales", "tb_ms_")->addAll($tema_add_data);
        }
        if ($res === false) {
            throw new Exception('添加销售团队子表失败');

        }
        return array('id'=>$ID);
    }

    public function subAuthOperation()
    {
        $data = $this->getJson();
        $data = $data['param'];
        Mainfunc::SafeFilter($data);
        $authInfo = [
            'sellerId' => $data['SellerID'],
            'appSecret' => $data['AppSecret'],
            'access_token' => $data['AccessToken'],
            'clientId' => $data['ClientID'],
        ];
        $data['APPKES'] = json_encode($authInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (is_numeric($data['APPKES'])) {
            $outputs['code'] = 500;
            $outputs['msg'] = '授权信息格式不正确';
            return $outputs;
        }
        $authStatus = json_decode($data['APPKES'], 1);
        if (!$authStatus) {
            $outputs['code'] = 500;
            $outputs['msg'] = '授权信息格式不正确';
            return $outputs;
        }
        if ($data['APPKES'] == '') {
            $outputs['code'] = 500;
            $outputs['msg'] = '请填写API keys';
            return $outputs;
        }
        $data['AUTH_TIME'] = date('Y-m-d H:i:s');
        $data = $this->create($data, 1);
        if (!$this->save($data)) {
            $outputs['code '] = 500;
            $outputs['msg'] = 'no change auth';
        }
        $msg = '授权成功';
        return $msg;
    }

    //获取仓库数量
    private function getCount($isSupport, $condition, $searchVal)
    {
        $where = $this->getWhere($isSupport, $condition, $searchVal);
        $count = M('ms_cmn_cd', 'tb_')
            ->where($where)
            ->where("left(tb_ms_cmn_cd.CD,6)='N00068'")
            ->count();
        return $count;
    }

    private function getWhere($isSupport, $condition, $searchVal)
    {
        //$where['left(tb_ms_cmn_cd.CD),6'] = 'N00068';
        if ($condition == 'wareName' && !empty($searchVal)) {
            $where['tb_ms_cmn_cd.CD_VAL'] = array('like', '%' . $searchVal . '%');
        }
        return $where;
    }

    //仓库配置列表 warelist
    public function getWareData()
    {
        $isSupport = $_GET['isSupport'];
        $condition = $_GET['condition'];
        $searchVal = $_GET['searchVal'];
        $where = $this->getWhere($isSupport, $condition, $searchVal);
        $id = $_GET['id'];
        $pageSize = $_GET['pageSize'];
        $pagenow = $_GET['pagenow'];
        $pageStart = ($pagenow - 1) * $pageSize;
        $wareData = M('ms_cmn_cd', 'tb_')
            ->field('tb_ms_cmn_cd.CD,tb_ms_cmn_cd.CD_VAL,tb_ms_cmn_cd.ETC,tb_ms_cmn_cd.USE_YN,tb_wms_warehouse.city,tb_wms_warehouse.areas')
            ->join("tb_wms_warehouse on tb_wms_warehouse.CD=tb_ms_cmn_cd.CD")
            ->where($where)
            ->where("left(tb_ms_cmn_cd.CD,6)='N00068'")
            ->group('CD')
            //->limit($pageStart,$pageSize)
            ->select();
        //map
        /*$cityFun = function ($v){
             $cityData = M("crm_site","tb_")->field("NAME,LEVEL")->where('LEVEL =1 AND id='.$v)->find();

            return $cityData['NAME'];
        };*/

        foreach ($wareData as $k => $v) {
            $wareData[$k]['isSupport'] = 1;
            //仓库信息
            if (!empty($v['city'])) {
                $cityData = explode(",", $v['city']);
                foreach ($cityData as $k1 => $v1) {
                    if (empty($v1)) {
                        continue;
                    }
                    $city = M("crm_site", "tb_")->field("NAME,LEVEL")->where('LEVEL =1 AND id =' . $v1)->find();
                    if (!is_null($city['NAME'])) {
                        $belongArea = $city['NAME'];
                    }
                }
                $wareData[$k]['belongArea'] = $belongArea;
            }
            if (!empty($v['areas'])) {
                $areaData = explode(",", $v['areas']);
                $area123 = [];
                foreach ($areaData as $k2 => $v2) {

                    $areasData = M("ms_user_area", "tb_")->field("area_no,zh_name")->where('id =' . $v2)->find();
                    if (!is_null($areasData)) {
                        $area123[] = $areasData['zh_name'];
                    }


                }
                // var_dump($area);die;
                $areaArr = implode(",", $area123);
                //var_dump($areaArr);die;
                $wareData[$k]['supportArea'] = $areaArr;
                $areaArr = '';
            }
            //是否支持清况
            $unSupportWare = $this->field("WAREHOUSES")->where('ID=' . $id)->find();
            if (!empty($unSupportWare['WAREHOUSES'])) {
                $unSupportWareArr = explode(",", $unSupportWare['WAREHOUSES']);
                if (in_array($v['CD'], $unSupportWareArr)) {
                    $wareData[$k]['isSupport'] = 2;
                }
            }
        }
        // var_dump($wareData);die;

        if (!empty($isSupport)) {
            foreach ($wareData as $k => $v) {
                if ($v['isSupport'] != $isSupport) {
                    unset($wareData[$k]);
                }
            }
        }
        $pageStart = ($pagenow - 1) * $pageSize;

        $wareData = $this->getSearchResults($wareData, $condition, $searchVal);
        $count = count($wareData);

        $wareData = array_slice($wareData, $pageStart, $pageSize);
        $wareData[0]['count'] = $count;


        return $wareData;
    }

    /*条件搜索 支持的国家、所属地*/
    private function getSearchResults($wareData = array(), $condition = '', $searchVal = '')
    {
        //var_dump($wareData);die;
        if (!empty($condition) AND !empty($searchVal) AND $condition != 'wareName') {

            /*if ($condition=='supCountry') {*/
            if ($condition == 'supCountry') {
                $where['zh_name'] = array('like', '%' . $searchVal . '%');
                $chooseCountry = M("ms_user_area", "tb_")
                    ->field('area_no,id')
                    ->where($where)
                    ->select();
                $areasArr = array_column($chooseCountry, 'id');
            } else {
                $where['NAME'] = array('like', '%' . $searchVal . '%');
                $chooseCountry = M("crm_site", "tb_")
                    ->field('ID,LEVEL')
                    ->where($where)
                    ->select();
                $areasArr = array_column($chooseCountry, 'ID');
            }
            foreach ($wareData as $k => $v) {
                if ($condition == 'supCountry') {
                    $areasArrList = explode(",", $v['areas']);
                    $arr = array_intersect($areasArr, $areasArrList);
                } else {
                    $cityArrList = explode(",", $v['city']);
                    $arr = array_intersect($areasArr, $cityArrList);
                }
                if (empty($arr)) {
                    unset($wareData[$k]);
                }
            }
        }
        return $wareData;
    }


    //params数据
    private function getParamsData($id = '', $cd = '', $type = '')
    {
        $paramsData['P_ID1'] = $id;
        $paramsData['P_ID2'] = $cd;
        $paramsData['TYPE'] = $type;
        $paramsData['CREATE_TIME'] = date("Y-m-d H:i:s", time());
        $paramsData['CREATE_USER'] = $_SESSION['m_loginname'];
        return $paramsData;
    }

    //编辑仓库支持
    public function simpleWareSupport()
    {
        $id = $_GET['id'];
        $cd = $_GET['cd'];
        $isSupport = $_GET['isSupport'];
        $unSupportWare = $this->field("WAREHOUSES")->where('ID=' . $id)->find();
        $unSupportWareArr = [];
        if (!empty($unSupportWare['WAREHOUSES'])) {
            $unSupportWareArr = explode(",", $unSupportWare['WAREHOUSES']);
        }
        if ($isSupport == '2') {   //选项是否

            if (!in_array($cd, $unSupportWareArr)) {  //不存在,计入store与params
                $unSupportWareArr[] = $cd;
                $newUnsupportStr = implode(",", $unSupportWareArr);
                $data['WAREHOUSES'] = $newUnsupportStr;
                $this->startTrans();
                $res = $this->where('ID=' . $id)->save($data);  //更新store表s
                $type = 3;
                $paramsData = $this->getParamsData($id, $cd, $type);
                $paramsRes = M("ms_params", "tb_")->add($paramsData);
                if (!$paramsRes or !$res) {
                    $this->rollback();
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                $this->commit();
                return $paramsRes;
            } else {
                $outputs['code'] = 500;
                $outputs['msg'] = '未作改动';
                return $outputs;
            }
        } elseif ($isSupport == '1') {
            if (in_array($cd, $unSupportWareArr)) {
                #$unSupportWareArr中有多个相同的值需要unset
                while(($key = array_search($cd, $unSupportWareArr)) !== false){
                    unset($unSupportWareArr[$key]);
                }
                $newUnsupportStr = implode(",", $unSupportWareArr);
                $data['WAREHOUSES'] = $newUnsupportStr;

                $res = $this->where('ID=' . $id)->save($data);  //更新store表s
                $delRes = M("ms_params", "tb_")->where("P_ID1 = {$id} AND P_ID2= '{$cd}' AND TYPE=3")->delete();
                if (!$res) {
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                return $res;
            } else {
                $outputs['code'] = 500;
                $outputs['msg'] = '未作改动';
                return $outputs;
            }
        }
        //echo $isSupport;die;
        //return;
    }

    //获取店铺支持的仓库
    public function getSupportWare($id)
    {
        $allWare = M("ms_cmn_cd", "tb_")
            ->field("CD")
            ->where("left(tb_ms_cmn_cd.CD,6)='N00068'")
            ->select();
        //从store表获取不支持的仓库
        $unSupportWare = $this->field("WAREHOUSES")->where('ID=' . $id)->find();
        $allWare = array_column($allWare, 'CD');
        if (!empty($unSupportWare['WAREHOUSES'])) {
            $unSupportWareArr = explode(",", $unSupportWare['WAREHOUSES']);
            $supportWare = array_diff($allWare, $unSupportWareArr);
        }
        $allWare = !is_null($supportWare) ? $supportWare : $allWare;
        return $allWare;
    }

    //物流配置列表
    public function getLogisticsData()
    {
        LogsModel::initConfig(__CLASS__,true);
        Logs('act');
        $isSupport = $_GET['isSupport'];
        $condition = $_GET['condition'];
        $searchVal = $_GET['searchVal'];
        $id = $_GET['id'];
        $supportWare = $this->getSupportWare($id);  //获取支持的仓库
        $all_store_logistics = (new Model())->table('tb_ms_logistics_mode_info')
            ->field('logistics_mode_id')
            ->where(['store_id'=>$id])
            ->select();
        $array_column_logistics = array_column($all_store_logistics, 'logistics_mode_id');
        $allWare_logistics_mode = [];
        if (!empty($array_column_logistics)) {
            $where_logistics_mode['ID'] = ['IN', $array_column_logistics];
            $allWare_logistics_mode = D("Logistics/LogisticsMode")
                ->field("ID,POSTAGE_ID")
                ->where($where_logistics_mode)
                ->select();
        }
        $where_support_ware['P_ID2'] = ['IN', array_unique($supportWare)];
//        $where_support_ware['P_ID1'] = ['IN', array_unique($array_column_logistics)];
        $wareLogistics = M("ms_params", "tb_")
            ->where($where_support_ware)
            ->where("TYPE =5", null, true)
            ->select();
        $temp_ware_logistic = [];
        foreach ($wareLogistics as $logistic) {
            $temp_ware_logistic[$logistic['P_ID2']][] = $logistic['P_ID1'];
        }
        Logs(__LINE__);
        foreach ($supportWare as $key => $v) {
            if ($temp_ware_logistic[$v]) {
                foreach ($allWare_logistics_mode as $k1 => $v1) {
                    if (!is_null($v1['POSTAGE_ID']) && strlen($v1['POSTAGE_ID']) > 0) {
                        if (count(array_intersect(explode(",", $v1['POSTAGE_ID']), $temp_ware_logistic[$v])) > 0) {
                            $simple_Logistics[] = $v1;
                        }
                    }
                }
            }
        }
        Logs(__LINE__);
        Logs(['supportWare'=>$supportWare,'$allWare_logistics_mode'=>$allWare_logistics_mode]);
        $store_id = $_GET['id'];
        $LogisticsCode = array_unique(array_column($simple_Logistics, 'ID'));
        $all = [];
        if (!empty($LogisticsCode)) {
            $where['tb_ms_logistics_mode.ID'] = array('IN', (array)$LogisticsCode);
            $where['tb_ms_logistics_mode_info.id'] = array('EXP', 'IS NOT NULL');
            $data = M("ms_logistics_mode", "tb_")
                ->join("tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_logistics_mode.LOGISTICS_CODE")
                ->join("left join tb_ms_logistics_mode_info on tb_ms_logistics_mode_info.logistics_mode_id = tb_ms_logistics_mode.id AND tb_ms_logistics_mode_info.store_id = $store_id")
                ->field("tb_ms_logistics_mode.LOGISTICS_MODE,tb_ms_logistics_mode.ID as LOGISTICS_MODE_ID,tb_ms_logistics_mode_info.id AS mode_info_id,tb_ms_logistics_mode_info.order_amount_range,tb_ms_logistics_mode_info.recipient_country,tb_ms_cmn_cd.CD_VAL,tb_ms_cmn_cd.CD,tb_ms_logistics_mode.id,tb_ms_logistics_mode_info.cast_time,tb_ms_logistics_mode_info.cast_switch")
                ->order('tb_ms_cmn_cd.CD_VAL asc,tb_ms_logistics_mode.LOGISTICS_MODE asc,tb_ms_logistics_mode_info.order_amount_range asc')
                ->where($where)
                ->select();
            foreach ($data as $key => $val) {
                if ($val['order_amount_range']) {
                    $data[$key]['order_amount_range'] = json_decode($val['order_amount_range'], true);
                }
            }
            $all = $data;
        }
        $unSupportLog = $this->field("ID,LGT_MODES")->where("ID=" . $id)->find();
        if (!empty($unSupportLog['LGT_MODES'])) {
            $unSupportLogArr = explode(",", $unSupportLog['LGT_MODES']);
        }
        $unSupporFun = function ($v) use ($unSupportLogArr) {
            if (in_array($v['id'], $unSupportLogArr)) {
                $v['isSupport'] = '2';
            } else {
                $v['isSupport'] = '1';
            }
            return $v;
        };
        $allData = array_map($unSupporFun, $all);

        $pageSize = $_GET['pageSize'];
        $pageno = $_GET['pageno'];
        $pageStart = ($pageno - 1) * $pageSize;

        //var_dump($allData);die;
        $allData = $this->getLogisSearchResult($allData, $isSupport, $condition, $searchVal);
        $count = count($allData);

        $allData = array_slice($allData, $pageStart, $pageSize);
        $unSupportWare = $this->field("WAREHOUSES")->where('ID=' . $id)->find();

        if (empty($unSupportWare['WAREHOUSES'])) {
            $allData[0]['isAllWare'] = true;
        }
        $allData[0]['count'] = $count;
        Logs('end');
        return $allData;
    }

    //物流搜索
    private function getLogisSearchResult($allData = array(), $isSupport = '', $condition = '', $searchVal = '')
    {
        if (!empty($isSupport)) {
            foreach ($allData as $k => $v) {
                if ($v['isSupport'] != $isSupport) {
                    unset($allData[$k]);
                }
            }
        }
        if (!empty($condition) and !empty($searchVal)) {
            foreach ($allData as $k => $v) {
                if ($condition == 'logCompany') {
                    $res = strstr($v['CD_VAL'], $searchVal);
                    if (!$res) {
                        unset($allData[$k]);
                    }
                }
                if ($condition == 'logWay') {
                    $res = strstr($v['LOGISTICS_MODE'], $searchVal);
                    if (!$res) {
                        unset($allData[$k]);
                    }
                }
            }
        }
        return $allData;
    }

    //单点物流方式
    public function simpleLogisticsSupport()
    {
        if (IS_POST && !$_GET['id']) {
            $require_data = DataModel::getData(true);
            $order_amount_range = $require_data['order_amount_range'];
            $recipient_country = $require_data['recipient_country'];
            $LOGISTICS_MODE_ID = $require_data['LOGISTICS_MODE_ID'];
            if ($LOGISTICS_MODE_ID) {
                $Model = M();
                $Model->startTrans();
                if ($order_amount_range) {
                    if (is_array($order_amount_range)) {
                        $order_amount_range = json_encode($order_amount_range, true);
                    }
                    $save_oar['order_amount_range'] = $order_amount_range;
                    $where_oar['ID'] = $LOGISTICS_MODE_ID;
                    $oar_res = $Model->table('tb_ms_logistics_mode')->where($where_oar)->save($save_oar);
                }
                if ($recipient_country || $recipient_country == '') {
                    $save_rc['recipient_country'] = $recipient_country;
                    $where_rc['ID'] = $LOGISTICS_MODE_ID;
                    $rc_res = $Model->table('tb_ms_logistics_mode')->where($where_rc)->save($save_rc);
                }
                if ($oar_res || $rc_res) {
                    $Model->commit();
                } else {
                    $outputs['code'] = 500;
                    $outputs['msg'] = '未作改动';
                }
            } else {
                $outputs['code'] = 500;
                $outputs['msg'] = '未作改动';
            }
        } else {
            $id = $_GET['id'];  //店铺id
            $mid = $_GET['mid'];  //物流方式id
            //echo $mid;die;
            $isSupport = $_GET['isSupport'];
            $unSupportLog = $this->field("LGT_MODES")->where('ID=' . $id)->find();
            $unSupportLogArr = [];

            if (!empty($unSupportLog['LGT_MODES'])) {
                $unSupportLogArr = explode(",", $unSupportLog['LGT_MODES']);
            }
            if ($isSupport == '2') {   //置否
                if (!in_array($mid, $unSupportLogArr)) {
                    $unSupportLogArr[] = $mid;
                    $data['LGT_MODES'] = implode(",", $unSupportLogArr);
                    $this->startTrans();
                    $res = $this->where('ID=' . $id)->save($data);
                    $type = 4;
                    $paramsData = $this->getParamsData($id, $mid, $type);
                    $paramsRes = M("ms_params", "tb_")->add($paramsData);
                    if (!$res or !$paramsRes) {
                        $this->rollback();
                        $outputs['code'] = 500;
                        $outputs['msg'] = $this->getDbError();   //打印错误语句
                        return $outputs;
                    }
                    $this->commit();
                    return $paramsRes;
                } else {
                    $outputs['code'] = 500;
                    $outputs['msg'] = '未作改动';
                    return $outputs;
                }
            } elseif ($isSupport == '1') {
                // var_dump($unSupportLogArr);die;
                $this->startTrans();
                //echo "shanchu"; echo $mid; die;
                if (in_array($mid, $unSupportLogArr)) {
                    $key = array_search($mid, $unSupportLogArr);
                    unset($unSupportLogArr[$key]);
                    $newUnsupportStr = implode(",", $unSupportLogArr);
                    $data['LGT_MODES'] = $newUnsupportStr;
                    $res = $this->where('ID=' . $id)->save($data);  //更新store表
                    $delRes = M("ms_params", "tb_")->where("P_ID1 = '{$id}' AND P_ID2= '{$mid}' AND TYPE=4")->delete();
                    //echo M("ms_params","tb_")->_sql();die;
                    if (!$res or !$delRes) {
                        $this->rollback();
                        $outputs['code'] = 500;
                        $outputs['msg'] = M("ms_params", "tb_")->_sql();   //打印错误语句
                        return $outputs;
                    }
                    $this->commit();
                    return $res;
                } else {
                    $outputs['code'] = 500;
                    $outputs['msg'] = '未作改动';
                    return $outputs;
                }
            }
        }
    }

    //店铺高级配置数据
    public function otherData()
    {
        $id = $_GET['id'];
        $otherData = $this
            ->field("BEAN_CD,QUEUE_INFO,LAST_TIME_POINT,CRAWLER_STATUSES,PROXY,APPKES,ORDER_SWITCH,ID,is_auto_devliery as isAutoDevliery,refresh_token")
            ->where('ID=' . $id)
            ->find();
        //var_dump($otherData);die;
        if (!empty($otherData['QUEUE_INFO'])) {
            $queDataArr = json_decode($otherData['QUEUE_INFO'], true);
            $otherData['orderInvokeCount'] = $queDataArr['orderInvokeCount'];
            $otherData['itemInvokeCount'] = $queDataArr['itemInvokeCount'];
            $otherData['cron'] = $queDataArr['cron'];
            $otherData['queuePlaceholder'] = $queDataArr['queuePlaceholder'];
        }

        if (!empty($otherData['APPKES'])) {
            $appkesDataArr = json_decode($otherData['APPKES'], true);
            $otherData['sellerId'] = $appkesDataArr['sellerId'] ? $appkesDataArr['sellerId'] : $appkesDataArr['seller_id'];
            $otherData['clientId'] = $appkesDataArr['clientId'] ? $appkesDataArr['clientId'] : $appkesDataArr['client_id'];
            $otherData['appSecret'] = $appkesDataArr['appSecret'] ? $appkesDataArr['appSecret'] : $appkesDataArr['app_secret'];
            $otherData['accessToken'] = $appkesDataArr['accessToken'] ? $appkesDataArr['accessToken'] : $appkesDataArr['access_token'];
        }
        return $otherData;
    }
    //店铺高级配置数据->自动获取token数据
    public function getAutoTokenData(){
      
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if(empty($id)){
            $outputs['code'] = 500;
            $outputs['msg'] = "参数有误";   //打印错误语句
            return $outputs;
        }
        $tokenData = $this
            ->field("ID,refresh_token,token_update_frequency,token_update_time")
            ->where('ID=' . $id)
            ->find();
        $d = floor($tokenData['token_update_frequency'] / (3600 * 24));
        $h = floor(($tokenData['token_update_frequency'] % (3600 * 24)) / 3600);
        $m = floor((($tokenData['token_update_frequency'] % (3600 * 24)) % 3600) / 60);
        $s = $tokenData['token_update_frequency'] - $d * 24 * 3600 - $h * 3600 - $m * 60;
        $tokenData['d'] = $d;
        $tokenData['h'] = $h;
        $tokenData['m'] = $m;
        $tokenData['s'] = $s;
        return $tokenData;
    }
    //编辑高级配置->获取下次更新时间
    public function getNextDate()
    {
        
        $editOtherData = $this->getJson();
        if (empty($editOtherData['ID'])) {
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        if (empty($editOtherData['d']) && empty($editOtherData['h']) && empty($editOtherData['m']) && empty($editOtherData['s'])) {
            if (empty($storeData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = '时间不能为空';
                return $outputs;
            }
        }
       
        $newFrequency = $editOtherData['d'] * 24 * 60 * 60 + $editOtherData['h']  * 60 * 60 + $editOtherData['m']  * 60 + $editOtherData['s'];
       
        $storeData = $this->where('ID=' . $editOtherData['ID'])->find();
        if (empty($storeData)) {
            $outputs['code'] = 500;
            $outputs['msg'] = '店铺ID有误';
            return $outputs;
        }
        $oldTime = $storeData['token_update_time'];
        $oldFrequency = $storeData['token_update_frequency'];
        $data = [];
        
        if(empty($oldFrequency)){
            //第一次写入
            $data['nextDate'] = date('Y-m-d H:i:s',time()+ $newFrequency);
        }else{
            $data['nextDate'] = date('Y-m-d H:i:s', $newFrequency - $oldFrequency + strtotime($oldTime));
        }
        
        return $data;
    }

    //编辑高级配置
    public function editOther()
    {
        M()->startTrans();
        $editOtherData = $this->getJson();
        $editOtherData = $editOtherData['otherData'];
        if (empty($editOtherData['LAST_TIME_POINT'])) {
            $editOtherData['LAST_TIME_POINT'] = null;

        }
        $editOtherData['LAST_TIME_POINT'] = cutting_times($editOtherData['LAST_TIME_POINT']);
        $storeData = $this->where('ID=' . $editOtherData['ID'])->find();

        $queueArr = json_decode($storeData['QUEUE_INFO'], true);
        $queueArr['cron'] = $editOtherData['cron'];
        $queueArr['queuePlaceholder'] = $editOtherData['queuePlaceholder'];
        $queueArr['itemInvokeCount'] = $editOtherData['itemInvokeCount'];
        $queueArr['orderInvokeCount'] = $editOtherData['orderInvokeCount'];
        if (!(empty($queueArr['cron']) && empty($queueArr['queuePlaceholder']) && empty($queueArr['itemInvokeCount']) && empty($queueArr['orderInvokeCount']))) {
            $editOtherData['QUEUE_INFO'] = json_encode($queueArr);
        }

        $appkes = json_decode($storeData['APPKES'], true);
        $appkesArr['sellerId'] = $editOtherData['sellerId'];
        $appkesArr['clientId'] = $editOtherData['clientId'];
        $appkesArr['appSecret'] = $editOtherData['appSecret'];
        $appkesArr['accessToken'] = $editOtherData['accessToken'];

        //不覆盖java新增的字段
        if (isset($editOtherData['refresh_token'])) {
            $appkesArr['refreshToken'] = $editOtherData['refresh_token'];
        }
        if (isset($appkes['ourClientId'])) {
            $appkesArr['ourClientId'] = $appkes['ourClientId'];
        }
        $editOtherData['APPKES'] = json_encode($appkesArr);
        $editOtherData['is_auto_devliery'] = $editOtherData['isAutoDevliery'];
        $editData = $this->create($editOtherData);
       
        // 增加操作日志
        $store_log = new StoreLogService();
        $log_data = $store_log->getUpdateMessage("tb_ms_store",['ID'=>$editData['ID']],$editData,4,$editData['ID']);

        $res = $this->where('ID=' . $editData['ID'])->save($editData);
        if (!$res) {
            $outputs['code'] = 500;
            $outputs['msg'] = $this->getDbError();   //打印错误语句
            M()->rollback();
            return $outputs;
        }
       
        // 添加日志
        if (!empty($log_data)){
            $ret = $store_log->addLog($log_data);
            if ($ret === false) {
                $outputs['code'] = 500;
                $outputs['msg'] = "添加日志失败";
                M()->rollback();
                return $outputs;
            }
        }
        M()->commit();

        return $res;


    }
    //编辑高级配置->自动获取token数据
    public function editAutoTokenData(){
        M()->startTrans();
        $editOtherData = $this->getJson();
        if (empty($editOtherData['d']) && empty($editOtherData['h']) && empty($editOtherData['m']) && empty($editOtherData['s'])) {
            if (empty($storeData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = '时间不能为空';
                return $outputs;
            }
        }
        if (empty($editOtherData['ID'])  || empty($editOtherData['token_update_time'])) {
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        
        if(strtotime($editOtherData['token_update_time']) <= time()){
            if (empty($storeData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = '下次更新时间不能比当前时间小';
                return $outputs;
            }
        }
        $newFrequency = $editOtherData['d'] * 24 * 60 * 60 + $editOtherData['h']  * 60 * 60 + $editOtherData['m']  * 60 + $editOtherData['s'];
        $storeData = $this->where('ID=' . $editOtherData['ID'])->find();
        if(empty($storeData)){
            $outputs['code'] = 500;
            $outputs['msg'] = '店铺ID有误';
            return $outputs; 
        }
        $oldTime = $storeData['token_update_time'];
        $oldFrequency = $storeData['token_update_frequency'];
        if($oldTime){
            //以前曾编辑过 计算下次更新时间是否一致
            $oldTime = strtotime($oldTime);
            $newTime = strtotime($editOtherData['token_update_time']);
            //计算出上次执行的时间
            $lastTime = $oldTime - $oldFrequency;
            if(($lastTime + $newFrequency) != $newTime){
                $outputs['code'] = 500;
                $outputs['msg'] = '频率与计算出来的下次更新时间不一致';
                return $outputs;
            }
        }
        $update = [];
        $update['token_update_frequency'] = $newFrequency;
        $update['token_update_time'] = $editOtherData['token_update_time'];
        $update['ID'] = $editOtherData['ID'];
        // $editOtherData['token_update_frequency'] = $editOtherData['token_update_frequency'];
        // $editOtherData['token_update_time'] = date('Y-m-d H:i:s',strtotime($editOtherData['token_update_time']));
        $editData = $this->create($update);
        
        // 增加操作日志
        $store_log = new StoreLogService();
        
        $log_data = $store_log->getUpdateMessageByAutoToken("tb_ms_store", ['ID' => $editData['ID']], $editData, 4, $editData['ID']);
        
        $res = $this->where('ID=' . $editData['ID'])->save($editData);
        if (!$res) {
            $outputs['code'] = 500;
            $outputs['msg'] = $this->getDbError();   //打印错误语句
            M()->rollback();
            return $outputs;
        }

        // 添加日志
       
        if (!empty($log_data)) {
            $ret = $store_log->addLog($log_data);
            if ($ret === false) {
                $outputs['code'] = 500;
                $outputs['msg'] = "添加日志失败";
                M()->rollback();
                return $outputs;
            }
        }
        M()->commit();
        return $res;
    }
    //批量编辑仓库的支持
    public function editBatch_WareSupport()
    {
        $cdArr = $_GET['cdArr'];
        $cdArr = explode(",", $cdArr);
        $id = $_GET['id'];
        $batchSupport = $_GET['batchSupport'];
        $unSupportWare = $this->field("WAREHOUSES")->where('ID=' . $id)->find();
        $unSupportWareArr = [];   //选中的仓库
        if (!empty($unSupportWare['WAREHOUSES'])) {
            $unSupportWareArr = explode(",", $unSupportWare['WAREHOUSES']);
        }
        if ($batchSupport == '1') {   //选择支持
            $supportWareArr = array_intersect($cdArr, $unSupportWareArr);
            if (count($supportWareArr) > 0) {
                $resUnSupportWare = array_diff($unSupportWareArr, $supportWareArr);
                $storeData['WAREHOUSES'] = implode(",", $resUnSupportWare);
                $res = $this->where('ID=' . $id)->save($storeData);   //更新store
                $where['P_ID1'] = $id;
                $where['P_ID2'] = array("in", $supportWareArr);
                $delres = M("ms_params", "tb_")->where($where)->delete();
                if (!$res or !$delres) {
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                return $res;
            } else {
                return true;  //??需求变动
            }
        }
        if ($batchSupport == '2') {
            $moreWareArr = array_diff($cdArr, $unSupportWareArr);
            if (count($moreWareArr) > 0) {
                $resUnArr = array_merge($unSupportWareArr, $moreWareArr);
                $storeData['WAREHOUSES'] = implode(",", $resUnArr);
                $res = $this->where('ID=' . $id)->save($storeData);
                foreach ($moreWareArr as $v) {
                    $type = 3;
                    $data = $this->getParamsData($id, $v, $type);
                    $addData[] = $data;
                }
                $addRes = M("ms_params", "tb_")->addAll($addData);
                if (!$res or !$addRes) {
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                return $res;
            }
        }
    }

    //批量编辑物流的支持
    public function editBatch_LogisticSupport()
    {
        $cdArr = $_GET['cdArr'];
        $cdArr = explode(",", $cdArr);
        $id = $_GET['id'];
        $batchSupport = $_GET['batchSupport'];
        $unSupportLog = $this->field("LGT_MODES")->where('ID=' . $id)->find();

        $unSupportLogArr = [];   //选中的仓库
        if (!empty($unSupportLog['LGT_MODES'])) {
            $unSupportLogArr = explode(",", $unSupportLog['LGT_MODES']);
        }
        //var_dump($cdArr);die;
        if ($batchSupport == '1') {
            $supportLogArr = array_intersect($cdArr, $unSupportLogArr);
            // var_dump($supportLogArr);die;
            if (count($supportLogArr) > 0) {
                $resUnSupportWare = array_diff($unSupportLogArr, $supportLogArr);
                //var_dump($resUnSupportWare);die;
                $storeData['LGT_MODES'] = implode(",", $resUnSupportWare);
                $res = $this->where('ID=' . $id)->save($storeData);   //更新store
                $where['P_ID1'] = $id;
                $where['P_ID2'] = array("in", $supportLogArr);
                $delres = M("ms_params", "tb_")->where($where)->delete();
                if (!$res or !$delres) {
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                return $res;
            } else {
                return true;  //??需求变动
            }
        }

        if ($batchSupport == '2') {
            $moreLogArr = array_diff($cdArr, $unSupportLogArr);
            if (count($moreLogArr) > 0) {
                $resUnArr = array_merge($unSupportLogArr, $moreLogArr);
                $storeData['LGT_MODES'] = implode(",", $resUnArr);
                $res = $this->where('ID=' . $id)->save($storeData);
                foreach ($moreLogArr as $v) {
                    $type = 4;
                    $data = $this->getParamsData($id, $v, $type);
                    $addData[] = $data;
                }
                $addRes = M("ms_params", "tb_")->addAll($addData);
                if (!$res or !$addRes) {
                    $outputs['code'] = 500;
                    $outputs['msg'] = $this->getDbError();   //打印错误语句
                    return $outputs;
                }
                return $res;
            }
        }
    }
    //手动更新token
    public function refreshToken(){
       
        $data = $this->getJson();

        $id = !empty($data['id'])? ($data['id']):0;
     
        if (empty($id)) {
            //未配置url
            $outputs['code'] = 500;
            $outputs['msg'] = 'id有误';
            return $outputs;
        }
        $store = $this->where('ID=' . $id)->field("PLAT_CD,token_update_frequency,refresh_token,token_update_time,APPKES")->find();
        if(empty($store)){
            $outputs['code'] = 500;
            $outputs['msg'] = 'id有误';
            return $outputs;
        }
        
        if(empty($store['token_update_frequency']) || empty($store['refresh_token']) || empty($store['token_update_time'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '请编辑保存后再执行';
            return $outputs;
        }
        $oldToken = json_decode($store['APPKES'],1);
        $oldToken = !empty($oldToken['accessToken'])? $oldToken['accessToken']:'';
       
        $plat_cd = $store['PLAT_CD'];
        
        $plat_comment3 = M('ms_cmn_cd','tb_')->where(['CD'=>$plat_cd])->getField("ETC3");
        
        if(!in_array($plat_comment3,['N002620400', 'N002620500', 'N002625715'])){ #新增otto平台
            #平台不为wish or lazada 过滤  
            $outputs['code'] = 500;
            $outputs['msg'] = '店铺平台有误!';
            return $outputs;
        }
        if ($plat_comment3 == 'N002620400') $type = 'wish';
        if ($plat_comment3 == 'N002620500') $type = 'lazada';
        if ($plat_comment3 == 'N002625715') $type = 'otto';
        $url = !empty(C('auto_token_config')['url']) ? C('auto_token_config')['url'] : '';
        if (empty($url)) {
            //未配置url
            $outputs['code'] = 500;
            $outputs['msg'] = '未配置url';
            return $outputs;
        }
      
        try {
            $client = new Client([
                'timeout'  => 8.0, //超时处理
            ]);
            
            $post_data = [
                'storeId' => $id,
                // 'type'=>$type
            ];
           
            $response = $client->request('POST', $url, [
                'form_params' => $post_data
            ]);
           
            $body = $response->getBody();
            $content = $body->getContents();
            $content  = json_decode($content, 1);
           
            Logs(['url' => $url, 'post_data' => $post_data, 're' => $content], __FUNCTION__, __CLASS__);
            if (empty($content) || empty($content['code']) || $content['code'] != 200 || empty($content['token'])) {
                $outputs['code'] = 500;
                $outputs['msg'] = !empty($content['msg'])? $content['msg']:'刷新token接口请求错误！';
                return $outputs;
            };
            $update = [];
            
            $update['token_update_time'] = date("Y-m-d H:i:s",$store['token_update_frequency']+time());
            
            // $editOtherData['token_update_frequency'] = $editOtherData['token_update_frequency'];
            // $editOtherData['token_update_time'] = date('Y-m-d H:i:s',strtotime($editOtherData['token_update_time']));
          

            $this->where('ID=' . $id)->save($update);

            $newToken = $content['token'];
            #成功后进行日志记录
            #成功后进行更新时间刷新
            $logData =  [
                [
                    "store_id" => $id,
                    "module" => 4,
                    "field_name" => "立即执行=》token",
                    "front_value" =>  $oldToken,
                    "later_value" => $newToken,
                    "update_by" => userName(),
                    "update_at" => date('Y-m-d H:i:s')
                ],
                [
                    "store_id" => $id,
                    "module" => 4,
                    "field_name" => "立即执行=》下次更新时间",
                    "front_value" =>  $store['token_update_time'],
                    "later_value" => $update['token_update_time'],
                    "update_by" => userName(),
                    "update_at" => date('Y-m-d H:i:s')
                ],
            ];
            $store_log = new StoreLogService();
            $store_log->addLog($logData);
            $outputs['code'] = 200;
            $outputs['msg'] = 'success';
            return $outputs;
            #拿到接口返回的正常值
            
        } catch (\Exception $e) {
            //获取失败
            $err = [
                'errcode' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
            Logs(['url' => $url, 'post_data' => $post_data, 're' => $content, 'err' => $err], __FUNCTION__, __CLASS__);
            $outputs['code'] = 500;
            $outputs['msg'] = '刷新token接口请求错误！';
            return $outputs;
        }
    }
    /**
     * @param $data
     * @param $Model
     */
    private function deleteStoreSales($data, $Model)
    {
        if ($data['ID']) {
            $Model->table('tb_ms_store_sales')
                ->where(" store_id = {$data['ID']}")
                ->delete();
        }
    }

    /**
     * @param $do_sales_arr
     * @param $data
     * @param $Model
     *
     * @return mixed
     */
    private function addSalesArr($do_sales_arr, $data, $Model)
    {
        foreach ($do_sales_arr as $value) {
            $do_save_sales['store_id'] = $data['ID'];
            $do_save_sales['sale_team_cd'] = $value;
            $do_save_sales_arr[] = $do_save_sales;
        }
        return $Model->table('tb_ms_store_sales')->addAll($do_save_sales_arr);
    }

    /**
     *  获取用户数据键值对
     */
    private function getUserData(){
        $data = array();
        $list = DataModel::getNormalUser('M_ID,M_NAME');
        if (!empty($list) && is_array($list)){
            foreach ($list as $value){
                $data[$value['M_ID']] = $value['M_NAME'];
            }
        }
        return $data;
    }

    /**
     *  获取详情
     */
    public function getDetails($id,$url){

       
        $store_data = M('store','tb_ms_')
                        ->field('tb_ms_store.ID,STORE_NAME,STORE_PWD,MERCHANT_ID,PLAT_CD,USER_ID,store_by,PLAT_NAME,
                        SALE_TEAM_CD,STORE_INDEX_URL,PRODUCT_DETAIL_URL_MARK,COUNTRY_ID,STORE_BACKSTAGE_URL,STORE_STATUS,OPERATION_TYPE,
                        SEND_ORD_TYPE,DELIVERY_STATUS,IS_VAT,company_cd,plat_explain,up_shop_time,up_shop_num,proposer_email,
                        proposer_phone,proposer_by,is_fee,remark,credit_card_explain,recently_affirm_time,handover_by,shop_manager_id,
                        product_id,sell_small_team_cd,default_timezone_cd,scan_time,scan_switch,reality_opt_store_id,store_type_cd,supplier_id,tb_crm_sp_supplier.SP_NAME as supplier_id_val')
                        ->join('tb_crm_sp_supplier on tb_crm_sp_supplier.ID = tb_ms_store.supplier_id')
                        ->where(['tb_ms_store.ID'=>$id])
                        ->find();
        if (empty($store_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = 'error data';
            return $outputs;
        }
        $store_data = CodeModel::autoCodeOneVal($store_data, ['PLAT_CD','company_cd','store_type_cd','sell_small_team_cd', 'default_timezone_cd']);
        $store_data['PLAT_NAME'] = isset($store_data['PLAT_CD_val']) ? $store_data['PLAT_CD_val'] : "";
        $store_data['countryName'] = TbMsUserArea::find($store_data['COUNTRY_ID'],array('zh_name'))['zh_name'] ;
        $store_data['shop_manager_id'] = DataModel::getUserIdByName($store_data['store_by']) ;
       
        $team_data = array();
        $sale_team_data = explode(',',$store_data['SALE_TEAM_CD']);
        foreach ($sale_team_data as $v){
            $temp_data = array(
                'sale_team_cd' => $v
            );
            $team_data[] = $temp_data;
        }
        $team_data = CodeModel::autoCodeTwoVal($team_data,['sale_team_cd']);
        $store_data['saleTeamName'] = implode(',',array_column($team_data,'sale_team_cd_val'));

        if ($url != '') {
            $storeUrl = $store_data['STORE_INDEX_URL'];
            return $storeUrl;
        }

//        // 销售小团队验证  【是否是GP站点】
//        $code_one = CodeModel::getValue($store_data['PLAT_CD']);
//        if ($code_one && $code_one['ETC3'] == 'N002620800'){
//            $store_data['is_gp_plat'] = true;
//        }else{
//            $store_data['is_gp_plat'] = false;
//        }

        $userData = $this->getUserData();
        $store_data['proposer_by_name'] = $userData[$store_data['proposer_by']];
        $store_data['handover_by_name'] = $userData[$store_data['handover_by']];
        $store_data['store_by_name'] =  $store_data['store_by'];
        $store_data['STORE_PWD'] =  "";
        if (!empty($store_data['up_shop_time'])){
            $store_data['up_shop_time'] = date('Y-m-d',strtotime($store_data['up_shop_time']));
        }
        if (!empty($store_data['recently_affirm_time'])){
            $store_data['recently_affirm_time'] = date('Y-m-d',strtotime($store_data['recently_affirm_time']));
        }

        /***
         *  按钮显示逻辑
         * 不存在交接人  验证当前登录用户是否为店铺操作人  是  验证是否存在最近确认时间  不存在确认时间  显示按钮
         * 存在  验证最近确认时间间隔大于一个月  是 显示按钮仅   其他情况不显示
         * 存在交接人 验证当前登录用户是否为店铺交接人 是 显示按钮  其他情况不显示
         */
        $store_data['is_show_button'] = false;  // 是否显示按钮
        if (empty($store_data['handover_by'])){
            if ($store_data['store_by'] == $_SESSION['m_loginname'] ){
                if (empty($store_data['recently_affirm_time'])){
                    $store_data['is_show_button'] = true;
                }else{
                    if (strtotime("+1 month",strtotime($store_data['recently_affirm_time'])) <= time()){
                        $store_data['is_show_button'] = true;
                    }
                }
            }
        }else{
            if ($store_data['handover_by'] == $_SESSION['userId'] ){
                $store_data['is_show_button'] = true;  // 是否显示按钮
            }
        }
       
        return $store_data;
    }

    /**
     *  确认店铺信息
     */
    public function affirm(){
        $params = $this->getJson();
        if (!isset($params['id']) || empty($params['id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $store_data = M('store','tb_ms_')->where(['ID'=>$params['id']])->find();
        if (empty($store_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = '数据为空';
            return $outputs;
        }
        $is_show_button  = false;
        if (empty($store_data['handover_by'])){
            if ($store_data['store_by'] == $_SESSION['m_loginname'] ){
                if (empty($store_data['recently_affirm_time'])){
                    $is_show_button = true;
                }else{
                    if (strtotime("+1 month",strtotime($store_data['recently_affirm_time'])) <= time()){
                        $is_show_button = true;
                    }
                }
            }
        }else{
            if ($store_data['handover_by'] == $_SESSION['userId'] ){
                $is_show_button = true;
            }
        }
        if (!$is_show_button){
            $outputs['code'] = 500;
            $outputs['msg'] = '确认数据有误';
            return $outputs;
        }
        $save_data['recently_affirm_time'] = date("Y-m-d H:i:s");
        $save_data['UPDATE_USER_ID'] = $_SESSION['userId'];
        $save_data['UPDATE_TIME'] = date("Y-m-d H:i:s");
        if (!empty($store_data['handover_by'])){
            $save_data['shop_manager_id'] = $store_data['handover_by'];
            $save_data['handover_by'] = "";
            $save_data['store_by'] = DataModel::getUserNameById($store_data['handover_by']);
        }

        // 增加操作日志
        $store_log = new StoreLogService();
        $log_data = $store_log->getUpdateMessage("tb_ms_store",['ID'=>$params['id']],$save_data,1,$params['id']);

        $res = M('store','tb_ms_')->where(['ID'=>$params['id']])->save($save_data);
        if ($res === false){
            $outputs['code'] = 500;
            $outputs['msg'] = '修改失败';
            return $outputs;
        }

        // 添加日志
        if (!empty($log_data)){
            $ret = $store_log->addLog($log_data);
            if ($ret === false) {
                $outputs['code'] = 500;
                $outputs['msg'] = "添加日志失败";
                M()->rollback();
                return $outputs;
            }
        }

        return $res;
    }

    /**
     * 解密触发事件
     */
    public function decode(){
        $params = $this->getJson();
        if (!isset($params['id']) || empty($params['id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $store_data = M('store','tb_ms_')->where(['ID'=>$params['id']])->find();
        if (empty($store_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = '数据为空';
            return $outputs;
        }

        // 发送消息通知 店铺负责人
        if (empty($store_data['shop_manager_id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '店铺负责人不存在';
            return $outputs;
        }
        // 销售团队
        if (empty($store_data['SALE_TEAM_CD'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '销售团队不存在';
            return $outputs;
        }

        /**
         *
         * 配置管理-店铺管理-查看-解密的权限范围如下：
         *  1.店铺负责人的账号
         *  2.交接人的账号
         *  3.店铺关联销售团队对应数据字典Comment1对应的账号
         *  4.妙玉的账号
         *  5.宝琴的账号
         */

        $name_data = array('Kathy.Tang','Helen.Yuan',$store_data['store_by']);
        if (!empty($store_data['handover_by'])){
            $handover_by_name = DataModel::getUserNameById($store_data['handover_by']);
            array_push($name_data,$handover_by_name);
        }
        $sale_team = explode(',',$store_data['SALE_TEAM_CD']);
        if (!empty($sale_team)){
            $user_data = M('cmn_cd','tb_ms_')->field('M_ID,M_NAME')
                ->join(" inner join tb_hr_card on tb_hr_card.SC_EMAIL = tb_ms_cmn_cd.ETC")
                ->join(" inner join bbm_admin on bbm_admin.M_NAME = tb_hr_card.ERP_ACT")
                ->where(['CD'=>['in',$sale_team]])
                ->select();
            if (!empty($user_data)){
                $users_name = array_column($user_data,'M_NAME');
                $name_data = array_merge($name_data,$users_name);
            }
        }
        if (!in_array($_SESSION['m_loginname'],$name_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = '无权限';
            return $outputs;
        }
        
        if ($store_data['store_by'] != $_SESSION['m_loginname']){
            $user_name = $_SESSION['m_loginname'];
            $where = [
                'M_NAME' => $store_data['store_by']
            ];
            $user_data = M('admin','bbm_')
                        ->field('b.wid')
                        ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
                        ->where($where)
                        ->find();
            //var_dump( M('admin','bbm_')->getLastSql());die;
            if (isset($user_data['wid']) && !empty($user_data['wid'])){
                $wid = $user_data['wid'];
                $res = ApiModel::WorkWxSendMessage($wid, "用户 {$user_name} 刚刚查看了您的店铺 {$store_data["STORE_NAME"]} （编号：{$store_data["ID"]} ）的密码，请知悉");
                if ($res['code'] !== 200000){
                    $outputs['code'] = 500;
                    $outputs['msg'] = '消息发送失败';
                    return $outputs;
                }
            }else{
                $outputs['code'] = 500;
                $outputs['msg'] = '该ERP账号未绑定企业微信';
                return $outputs;
            }
        }
        return ['STORE_PWD' => $store_data['STORE_PWD']];
    }
    /**
     *  检查用户店铺数
     * @return mixed
     */
    public function examineShop(){
        $params = $this->getJson();
        if (!isset($params['ERP_ACT']) || empty($params['ERP_ACT'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $admin_data = M('admin')->field('M_ID,M_NAME')->where("M_NAME = '{$params['ERP_ACT']}'")->find();
        if (empty($admin_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = '数据有误';
            return $outputs;
        }
        $where['store_by'] = $admin_data['M_NAME'];
        $count = M('store','tb_ms_')->where($where)->count();
        $where['handover_by'] = 0;
        $no_handover_num = M('store','tb_ms_')->where($where)->count();
        if ($count > 0){
            $data = array(
                'total_no_handover' => $count,
                'in_handover_num' => $count - $no_handover_num,
                'no_handover_num' => $no_handover_num,
            );
        }else{
            $data = array(
                'total_no_handover' => 0,
                'in_handover_num' => 0,
                'no_handover_num' => 0 ,
            );
        }
        return $data;
    }

    /**
     * 一键交接
     * @return array
     */
    public function oneClick()
    {
        $params = $this->getJson();
        if (!isset($params['ERP_ACT']) || empty($params['ERP_ACT'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        if (!isset($params['handover_by']) || empty($params['handover_by'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $admin_data = M('admin')->field('M_ID,M_NAME')->where("M_NAME = '{$params['ERP_ACT']}'")->find();
        if (empty($admin_data)){
            $outputs['code'] = 500;
            $outputs['msg'] = '数据有误';
            return $outputs;
        }
        $M_ID = $admin_data['M_ID'];
        $M_NAME = $admin_data['M_NAME'];
        if ($params['handover_by'] == $M_ID){
            $outputs['code'] = 500;
            $outputs['msg'] = '交接人不能是本人';
            return $outputs;
        }
        $where = [
            'store_by' => $M_NAME
        ];
        $save_data = [
            'handover_by' => $params['handover_by']
        ];
        $ret = M('store','tb_ms_')->where($where)->save($save_data);
        if ($ret === false){
            $outputs['code'] = 500;
            $outputs['msg'] = '交接失败';
            return $outputs;
        }
        return $ret;
    }

    /**
     * 导出数据
     */
    public function export_data(){
        $data = $this->getParams();
        $data = $data['post_data'];
        $data = json_decode($data,true);
        $params = $data['search'];
        $where = $this->searchWhere($params);
        $list = M('store', 'tb_ms_')
            ->field("tb_ms_user_area.zh_name,tb_ms_store.*,tb_crm_sp_supplier.SP_NAME as supplier_id_val")
            ->join("LEFT JOIN tb_ms_user_area ON tb_ms_store.COUNTRY_ID = tb_ms_user_area.id")
            ->join("LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.ID = tb_ms_store.supplier_id")
            ->where($where)
            ->order('tb_ms_store.ID desc')
            ->select();
        $list = CodeModel::autoCodeTwoVal($list, ['PLAT_CD','company_cd','store_type_cd','income_company_cd', 'default_timezone_cd']);
        $userData = $this->getUserData();
        foreach ($list as &$value){
            $value['PLAT_NAME'] = $value['PLAT_CD_val'];
            $value['proposer_by_name'] = $userData[$value['proposer_by']];
            $value['handover_by_name'] = $userData[$value['handover_by']];
            $value['store_by_name'] =  $value['store_by'];
            if ($value['OPERATION_TYPE'] == '0') {
                $value['OPERATION_ZN_TYPE'] = 'B2C';
            } elseif ($value['OPERATION_TYPE'] == '1') {
                $value['OPERATION_ZN_TYPE'] = 'B2B2C';
            } else {
                $value['OPERATION_ZN_TYPE'] = 'B5C';
            }
            $value['company'] = $value['company_cd_val'];
            if (empty(json_decode($value['APPKES']))){
                $value['STATUS'] = "未授权";
            }else{
                $value['STATUS'] = "已授权";
            }

            $team_data = array();
            $sale_team_data = explode(',',$value['SALE_TEAM_CD']);
            foreach ($sale_team_data as $v){
                $temp_data = array(
                    'sale_team_cd' => $v
                );
                $team_data[] = $temp_data;
            }
            $team_data = CodeModel::autoCodeTwoVal($team_data,['sale_team_cd']);
            $value['SALE_TEAM'] = implode(',',array_column($team_data,'sale_team_cd_val'));
            $value['DELIVERY_STATUS'] = empty($value['DELIVERY_STATUS']) ? "未对接" : "对接";
            $value['IS_VAT'] = empty($value['IS_VAT']) ? "否" : "是";
            $value['STORE_STATUS'] = empty($value['STORE_STATUS']) ? "运营中" : "未运营";
            $value['up_shop_time'] = date('Y-m-d',strtotime($value['up_shop_time'])) ;
            $value['recently_affirm_time'] = !empty($value['recently_affirm_time']) ? date('Y-m-d',strtotime($value['recently_affirm_time'])) : "" ;

        }

        return $list;
    }

    /**
     *  日志列表
     * @return array
     */
    public function getLogList()
    {
        $data = $this->getJson();;
        $params = $data['search'];
        Mainfunc::SafeFilter($params);
        if ( !isset($params["store_id"])  || empty($params["store_id"])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数输入有误';
            return array($outputs,'');
        }
        $where['store_id'] = $params['store_id'];
        $count = M('store_log', 'tb_ms_')
            ->where($where)
            ->count();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($data['pages']) && !empty($data['pages']['per_page']) && !empty($data['pages']['current_page'])){
            $pages = array(
                'per_page' =>$data['pages']['per_page'],
                'current_page' => $data['pages']['current_page']
            );
        }
        $list = M('store_log', 'tb_ms_')
            ->where($where)
            ->order('ID desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        foreach ($list as &$value){
            switch ($value['module']){
                case 1:
                    $value['module'] = '基础配置';
                    break;
                case 2:
                    $value['module'] = '仓库配置';
                    break;
                case 3:
                    $value['module'] = '物流配置';
                    break;
                case 4:
                    $value['module'] = '高级配置';
                    break;
                case 5:
                    $value['module'] = '财务配置';
                    break;
                case 6:
                    $value['module'] = '自定义走仓配置';
                    break;
            }
        }
        return array($list,$count);
    }

    /**
     *  获取离职 可交接用用户
     */
    public function getHrUserData(){
        $where = [
            'IS_USE' => 0,
            'STATUS' => "在职",
            'M_STATUS' => array('NEQ',2)
        ];
        $data = M('admin','bbm_')
                ->field('M_ID, M_NAME')
                ->join('INNER JOIN tb_hr_card ON bbm_admin.M_NAME = tb_hr_card.ERP_ACT')
                ->where($where)
                ->select();
        return $data;
    }

    /**
     * 获取渠道账号
     * @return mixed
     */
    public function getFinAccount(){
        $where = [
            'state' => '1'
        ];
        $list= M('account_bank','tb_fin_')->field('id,account_bank')->where($where)->select();

        return $list;
    }

    /**
     *  获取财务详情
     */
    public function getFinanceConfig(){
        $params = $this->getJson();
        if (!isset($params['store_id']) || empty($params['store_id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数不能为空';
            return $outputs;
        }
        $ret = M('store','tb_ms_')->field('income_company_cd,STORE_NAME,MERCHANT_ID,USER_ID,store_by,shop_manager_id,OPERATION_TYPE,STORE_STATUS,tb_ms_user_area.zh_name,PLAT_NAME,fin_account_bank_id,account_bank')
            ->join('LEFT JOIN  tb_ms_user_area on tb_ms_user_area.id = tb_ms_store.COUNTRY_ID')
            ->join('LEFT JOIN  tb_fin_account_bank on tb_fin_account_bank.id = tb_ms_store.fin_account_bank_id')
            ->where(['tb_ms_store.ID' => $params['store_id']])->find();
        if (empty($ret)){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $ret['shop_manager_id'] = DataModel::getUserIdByName($ret['store_by']) ;

        $ret = CodeModel::autoCodeOneVal($ret,['income_company_cd']);
        $ret['OPERATION_TYPE_ZH'] = $this->_status_transform['OPERATION_TYPE'][$ret['OPERATION_TYPE']];
        $ret['STORE_STATUS_ZH'] = $this->_status_transform['STORE_STATUS'][$ret['STORE_STATUS']];
        return $ret;
    }

    /**
     * 编辑财务配置
     * @return mixed
     */
    public function editFinanceConfig(){
        $params = $this->getJson();
        if (!isset($params['store_id']) || empty($params['store_id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数不能为空';
            return $outputs;
        }
        /*if (!isset($params['fin_account_bank_id']) || empty($params['fin_account_bank_id'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数不能为空（关联渠道账号ID）';
            return $outputs;
        }*/

        /*if (!isset($params['income_company_cd']) || empty($params['income_company_cd'])){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数不能为空（收入记录公司）';
            return $outputs;
        }*/

        $ret = M('store','tb_ms_')->field('ID')
            ->where(['tb_ms_store.ID' => $params['store_id']])->find();
        if (empty($ret)){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $save_data = array(
            'fin_account_bank_id' => $params['fin_account_bank_id'],
            'income_company_cd' => $params['income_company_cd']
        );
        // 增加日志操作
        $store_log = new StoreLogService();
        $log_data = $store_log->getUpdateMessage("tb_ms_store",['ID'=>$params['store_id']],$save_data,5,$params['store_id']);

        M()->startTrans();
        $ret = M('store','tb_ms_')->where(['ID' => $params['store_id']])->save($save_data);
        if ($ret === false){
            $outputs['code'] = 500;
            $outputs['msg'] = '编辑失败';
            M()->rollback();
            return $outputs;
        }
        // 添加日志
        if (!empty($log_data)){
            $ret = $store_log->addLog($log_data);
            if ($ret === false) {
                $outputs['code'] = 500;
                $outputs['msg'] = "添加日志失败";
                M()->rollback();
                return $outputs;
            }
        }
        M()->commit();
        return $ret;
    }
}


/*sql:alter table tb_ms_store add STORE_BACKSTAGE_URL varchar(255)  DEFAULT NULL COMMENT '店铺后台地址';
alter table tb_ms_store modify column PRODUCT_DETAIL_URL_MARK varchar(1024) DEFAULT NULL comment '商品主链接';*/

?>


