<?php

/**
 * api for store admin   luoji
 * by huanzhu
 */
class StoreAapi extends Action
{
    private $nameStore = 'TbMsStore';
    protected $error_message = "";
    private function getJson()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        return $data;
    }

    //store list
    public function getList()
    {
        $m_store = D($this->nameStore);
        list($res,$count) = $m_store->getStoreList();
        $data = array(
            'data' => $res,
            'page' =>  ['total_rows'=>$count]
        );
        return $data;
    }

    /**
     * 添加与编辑
     * @return mixed
     */
    public function subStore()
    {
        $res = array(
            'code' => 200,
            'info' => '',
            'msg' => 'succee',
        );
        if (IS_POST) {
            try {
                M()->startTrans();
                $request_data = $this->getJson();
                if (empty($request_data)) {
                    throw new Exception('请求为空');
                } else {
                    $this->confirmValidate($request_data);
                }
                $m_store = D($this->nameStore);
                $res = $m_store->newAddStore($request_data);
                M()->commit();
            } catch (Exception $exception) {
                M()->rollback();
                $res = $this->catchException($exception);
                return $res;
            }
            return $res;
        }
    }

    /**
     * 详情
     * @return mixed
     */
    public function getStore()
    {
        $id = $_GET['id'];
        $url = $_GET['storeUrl'] ? $_GET['storeUrl'] : '';
        $m_store = D($this->nameStore);
        if (empty($id)){
            $outputs['code'] = 500;
            $outputs['msg'] = '参数有误';
            return $outputs;
        }
        $storeData = $m_store->getDetails($id,$url);
        return $storeData;
    }

    //auth
    public function subAuth()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->subAuthOperation();
        return $res;
    }

    //店铺支持的仓库
    public function getSupportWare()
    {
        $m_store = D($this->nameStore);
        $wareData = $m_store->getWareData();
        return $wareData;
    }

    //编辑仓库对店铺的支持情况(单点)
    public function editWareSupport()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->simpleWareSupport();
        return $res;
    }

    //店铺支持的物流
    public function getSupportLogistics()
    {
        $m_store = D($this->nameStore);
        $wareData = $m_store->getLogisticsData();
        return $wareData;
    }

    //编辑物流对店铺的支持情况(单点)
    public function editLogisticsSupport()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->simpleLogisticsSupport();
        return $res;
    }

    //店铺高级数据
    public function getOtherData()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->otherData();
        return $res;
    }
    //店铺高级数据->获取自动更新token数据
    public function getAutoTokenData()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->getAutoTokenData();
        return $res;
    }
    //编辑店铺自动更新token数据
    public function editAutoTokenData()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->editAutoTokenData();
        return $res;
    }
    public function getNextDate(){
        $m_store = D($this->nameStore);
        $res = $m_store->getNextDate();
        return $res;
    }
    //手动刷新token  =>立即执行
    public function refreshToken()
    {
       
        $m_store = D($this->nameStore);
        $res = $m_store->refreshToken();
        return $res;
    }
    //编辑高级配置
    public function editOtherData()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->editOther();
        return $res;
    }

    //批量编辑仓库支持
    public function batchEditWare()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->editBatch_WareSupport();
        return $res;
    }

    public function batchEditLogistic()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->editBatch_LogisticSupport();
        return $res;
    }

    /**
     * 获取正常使用的用户（启用）
     */
    public function getUserData(){
        $list = DataModel::getNormalUser('M_ID,M_NAME');
        return $list;
    }

    /**
     *  获取离职 可交接用户
     */
    public function getHrUserData(){
        $m_store = D($this->nameStore);
        $list = $m_store->getHrUserData();
        return $list;
    }
    /**
     * 确认店铺信息
     */
    public function affirm()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->affirm();
        return $res;
    }

    /**
     * 解密触发事件
     */
    public function decode()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->decode();
        return $res;
    }

    /**
     *  检查用户店铺数
     * @return mixed
     */
    public function examineShop()
    {
        $m_store = D($this->nameStore);
        $res = $m_store->examineShop();
        return $res;
    }

    /**
     * 一键交接
     * @return mixed
     */
    public function oneClick(){
        $m_store = D($this->nameStore);
        $res = $m_store->oneClick();
        return $res;
    }

    /**
     * 获取日志列表
     */
    public function getLogList(){
        $m_store = D($this->nameStore);
        list($res,$count) = $m_store->getLogList();
        if (isset($res['code']) && $res['code']== 500){
            return $res;
        }
        $data = array(
            'data' => $res,
            'page' =>  ['total_rows'=>$count]
        );
        return $data;
    }

    /**
     * 添加与编辑数据验证 (数据组装)
     * @throws Exception
     */
    private function confirmValidate($request_data){
        // 规则
        $rules['STORE_NAME'] = 'required';
        $rules['MERCHANT_ID'] = 'required';
        $rules['COUNTRY_ID'] = 'required';
        $rules['PLAT_CD'] = 'required';
        $rules['USER_ID'] = 'required';
        $rules['STORE_STATUS'] = 'required';
        $rules['store_by'] = 'required';
        $rules['IS_VAT'] = 'required';
        $rules['STORE_BACKSTAGE_URL'] = 'required';
        $rules['STORE_INDEX_URL'] = 'required';
        $rules['SALE_TEAM_CD'] = 'required';
        $rules['PLAT_NAME'] = 'required';
        $rules['STORE_PWD'] = 'required';
        $rules['default_timezone_cd'] = 'required';
        $rules['store_type_cd'] = 'required';

        $rules['up_shop_time'] = 'required';
        $rules['up_shop_num'] = 'required';
        $rules['proposer_email'] = 'required';
        $rules['proposer_phone'] = 'required';
        $rules['is_fee'] = 'required';
        $rules['credit_card_explain'] = 'required';
//        $rules['scan_time'] = 'required|integer|min:1';
//        $rules['scan_switch'] = 'required';

        // 提示信息
        $custom_attributes['STORE_NAME'] = '请填写店铺名称';
        $custom_attributes['MERCHANT_ID'] = '请填写店铺别名';
        $custom_attributes['STORE_PWD'] = '请填写店铺密码';
        $custom_attributes['COUNTRY_ID'] = '请填写国家';
        $custom_attributes['PLAT_CD'] = '请填写平台';
        $custom_attributes['USER_ID'] = '请填写负责人联系方式';
        $custom_attributes['STORE_STATUS'] = '请填写负责人联系方式';
        $custom_attributes['store_by'] = '请填写店铺负责人';
        $custom_attributes['IS_VAT'] = '请填写是否交VAT';
        $custom_attributes['STORE_BACKSTAGE_URL'] = '请填写店铺后台地址';
        $custom_attributes['STORE_INDEX_URL'] = '请填写店铺链接';
        $custom_attributes['SALE_TEAM_CD'] = '请填写销售团队';
        $custom_attributes['PLAT_NAME'] = '请填写平台名称';
        $custom_attributes['store_type_cd'] = '店铺类型';


        $custom_attributes['up_shop_time'] = '请填写开店日期';
        $custom_attributes['up_shop_num'] = '请填写开店账号';
        $custom_attributes['proposer_email'] = '请填写申请邮箱';
        $custom_attributes['proposer_phone'] = '请填写申请手机号码';
        $custom_attributes['is_fee'] = '请填写是否需押金或收取费用';
        $custom_attributes['credit_card_explain'] = '请填写信用卡绑定情况';
        $custom_attributes['default_timezone_cd'] = '请填写运营后台默认时区';
//        $custom_attributes['scan_time'] = '扫描时间';
//        $custom_attributes['scan_switch'] = '扫描时间开关';

        // 店铺类型≠自营店铺，则【注册公司】非必填
        if ($request_data['store_type_cd'] == 'N003700001'){
            $rules['company_cd'] = 'required';
            $custom_attributes['company_cd'] = '请填选择注册公司';
        }
        // 店铺类型=我方代发货店铺
        if ($request_data['store_type_cd'] == 'N003700002'){
            if (!is_numeric($request_data['reality_opt_store_id']) || $request_data['reality_opt_store_id'] <= 0){
                throw new Exception("只能输入正整数。否则禁止编辑");
            }
            if (!is_numeric($request_data['supplier_id'])){
                throw new Exception("请选择合作方公司");
            }
        }

        $this->validate($rules, $request_data, $custom_attributes);
    }


    protected function catchException($exception, $Model = null) {
        $res = DataModel::$error_return;
        if ($this->error_message) {
            $msg_arr = array_values($this->error_message);
            $res['msg'] = $res['info'] = $msg_arr[0][0];
        } else {
            $res['msg'] = $res['info'] = $exception->getMessage();
        }
        if ($exception->getCode()) $res['code'] = $exception->getCode();
        if ($Model) {
            $Model->rollback();
        }
        return $res;
    }

    /**
     * 验证数据
     * @param $rules
     * @param $data
     * @param $custom_attributes
     * @throws Exception
     */
    private function validate($rules, $data, $custom_attributes)
    {
        ValidatorModel::validate($rules, $data, $custom_attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $value) {
                throw new Exception(L($value[0]), 40001);
            }
        }
    }

    /**
     *  获取财务配置
     * @return mixed
     */
    public function getFinanceConfig(){
        $m_store = D($this->nameStore);
        $res = $m_store->getFinanceConfig();
        return $res;
    }

    /**
     *  编辑财务配置
     * @return mixed
     */
    public function editFinanceConfig(){
        $m_store = D($this->nameStore);
        $res = $m_store->editFinanceConfig();
        return $res;
    }
    /*
     * 获取账号渠道
     */
    public function getFinAccount(){
        $m_store = D($this->nameStore);
        $res = $m_store->getFinAccount();
        return $res;
    }


    /**
     *  通过销售团队获取销售小团队
     */
    public function getSellSmallTeame()
    {
        $res = array(
            'code' => 2000,
            'data' => '',
            'msg' => 'succee',
        );
        $post_data =  $this->getJson();
        if (!isset($post_data['code']) || empty($post_data['code'])) {
            $res['code'] = 4000;
            $res['msg'] = "参数异常";
            return $res;
        }
        $list = CodeModel::getSellSmallTeamCodeArr($post_data['code']);
        $res['data'] = $list;
        return $res;
    }
}
?>