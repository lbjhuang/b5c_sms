<?php
class CustomsService extends Service
{

    protected $return_status = array(
        1=>'待返回',
        2=>'成功',
        3=>'失败',
    );

    protected $order_type = array(
        1=>'新增',
        2=>'修改',
        3=>'删除',
    );
    protected $status = array(
        1=>'待申报',
        2=>'申报中',
        3=>'申报成功',
        4=>'申报失败',
        5=>'重新申报',
    );
    protected $paid_channel_name = array(
        'WechatPay'=>'微信',
        'Alipay'=>'支付宝',
    );


    public function checkChangeCustomsData($param, $info)
    {
        $saveData = [];
        $where['main_order_id'] = $param['orderId'];
        $where['plat_cd'] = $param['platCd'];
        $customsRepository = new CustomsRepository();
        if ($info['paid_custom_amount'] != $param['paid_custom_amount'] && isset($param['paid_custom_amount']) && !empty($param['paid_custom_amount'])) {
            $saveData['paid_custom_amount'] = $param['paid_custom_amount'];
        }
        if ($info['sub_order_id'] != $param['sub_order_id'] && isset($param['sub_order_id']) && !empty($param['sub_order_id'])) {
            $saveData['sub_order_id'] = $param['sub_order_id'];
        }
        if ($info['customs_place'] != $param['customs_place'] && isset($param['customs_place']) && !empty($param['customs_place'])) {
            $saveData['customs_place'] = $param['customs_place'];
        }
        if ($info['ebc_code'] != $param['ebc_code'] && isset($param['ebc_code']) && !empty($param['ebc_code'])) {
            $saveData['ebc_code'] = $param['ebc_code'];
        }
        if ($info['ebc_name'] != $param['ebc_name'] && isset($param['ebc_name']) && !empty($param['ebc_name'])) {
            $saveData['ebc_name'] = $param['ebc_name'];
        }
        if ($saveData) {
            $res = $customsRepository->updateInfo($where, $saveData);
            if ($res === false) {
                $lastsql = M()->_sql();
                throw new Exception("更新海关信息异常，sql：{$lastsql}");
            }
        }
    }
    // 重推
    public function repushOrder($param)
    {
        $customsRepository = new CustomsRepository();
        $where['t.main_order_id'] = $param['orderId'];
        $where['t.plat_cd'] = $param['platCd'];
        $field = 't.plat_cd, t.main_order_id, t.identity_check';
        $info = $customsRepository->getDataInfo($field, $where);
        if (empty($info)) {
            throw new Exception("平台{$param['platCd']}下该订单号{$param['orderId']}暂无记录，请先校验核对");
        }
        $this->checkChangeCustomsData($param, $info);
        $url = GENERAL_B5C . "/custom/queryDeclareOrder?orderId={$param['orderId']}&platCd={$param['platCd']}&invokeType=2";
        $res = ApiModel::crawler($url);
        if ($res !== 'success') { // 表明接口有问题
            throw new Exception('重推JAVA接口返回异常');
        }
        // 根据条件查询是否校验成功，如果失败，则微信监控发送消息
        $info = $customsRepository->getDataInfo($field, $where);
        $identity_check = $info['identity_check'];
        if ($identity_check !== 'T') {
            // 企业微信推送
            return $this->send_wx($info['plat_cd'], $info['main_order_id']);
        } else {
            return 'SUCCESS';
        }
    }

    public function send_wx($plat_cd = '', $order_id = '')
    {
        // 根据plat_cd获取对应的code
        // 根据code值获取名单
        // 获取多个wid
        $customs_code = CodeModel::getEtcKeyValue('N00342');
        $plat_cd_name = cdVal($plat_cd);
        $wx_name = $customs_code[$plat_cd_name];
        $wx_name = explode(',', $wx_name);
        $where['bbm_admin.M_NAME'] = array('in', $wx_name);
        $user_info = M('admin','bbm_')
                        ->field('b.wid, bbm_admin.M_NAME')
                        ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
                        ->where($where)
                        ->group('b.uid')
                        ->select(); // 防止多个相同账号的情况（比如个别用户先辞职后重新入职，tb_hr_empl_wx.uid有出现一对多的情况）
        $user_info = array_column($user_info, 'wid', 'M_NAME');
        $is_fail = false;
        foreach ($wx_name as $key => $value) {
            $res = ApiModel::WorkWxSendMessage($user_info[$value], "{$value}您好，{$plat_cd_name}店铺的订单号:{$order_id} 对应的购买者身份证信息和支付者的身份证信息不一致，会影响海关清关，请检查处理！");
            if ($res['code'] === 200000){
                $res['send_user'] = $value;
            } else {
                $res['order_id'] = $order_id;
                // 表明发送失败，需要哨兵提醒
                @SentinelModel::addAbnormal('海关重推消息发送', $res['errmsg'], [$value, $user_info[$value], $plat_cd_name, $order_id, $res], 'customs_notice');
                $is_fail = true;
            }
            Logs(json_encode($res), __FUNCTION__.'----customwxsend', 'tr');
        }
        if ($is_fail === false) {
            return '推送成功，消息提醒成功';
        }
        return '推送成功';
    }


    /***
     * 列表
     * @param $where
     */
    public function getList($params,$pages)
    {
        $customsRepository = new CustomsRepository();
        $where = $this->paramsWhere($params);
        list($data, $count) = $customsRepository->getList($where,$pages);
        // 组装数据
        $list = array();
        foreach ($data as $itme){
            $temp = array();
            $temp['id'] = $itme['id'];
            $temp['return_status'] = $this->return_status[$itme['return_status']];     // 请求是否成功  1-待返回，2-成功，3-失败
            $temp['req_time'] = $itme['req_time'];     // 请求时间
            $temp['order_id'] = $itme['order_id'];         // 订单编号
            $temp['customs_place'] = $itme['customs_place'];  // 海关编号
            $temp['ebc_code'] = $itme['ebc_code'];  // 商户备案号
            $temp['last_modified_time'] = $itme['last_modified_time'];  // 最后更新时间
            $temp['paid_custom_amount'] = $itme['paid_custom_amount'];  // 金额
            $temp['out_trade_no'] = $itme['out_trade_no'];  // 支付交易号
            $temp['paid_channel_name'] = $this->paid_channel_name[$itme['paid_channel_name']];  // 支付公司
            $temp['note'] = $itme['note'];  // 备注
            $temp['identity_check'] = $itme['identity_check']; //身份核验
            $temp['status'] = $this->status[$itme['status']];
            $temp['plat_cd'] = $itme['plat_cd']; // 平台
            $temp_data =  array(
                'out_request_no' => $itme['out_request_no'],  //  报关请求号
                'custom_declare_no' => $itme['custom_declare_no'],  //  支付宝报关号| 微信支付订单号
                'out_trade_no' => $itme['out_trade_no'],  // 支付单据号
                'count' => 1,  // 笔数
                'customs_place' => $itme['customs_place'],    // 海关编号
                'ebc_code' => $itme['ebc_code'],    // 商户备案号
                'is_split' =>  $itme['is_split'] == 'F' ? "否":"是",    // 是否拆单
                'paid_custom_amount' => $itme['paid_custom_amount'],  // 报关金额 |应付金额
                'custom_currency' => $itme['custom_currency'],    // 币种
                'ebc_name' => $itme['ebc_name'],   // 商户备案名
                'note' => $itme['note'], // 备注
                'status' => $this->status[$itme['status']], //
                'order_type' => $itme['order_type'], // 当前状态
                'paid_channel_name' => $this->paid_channel_name[$itme['paid_channel_name']],  // 支付公司
                'last_modified_time' => $itme['last_modified_time'],  // 最后更新时间
                'customs_code' => $itme['customs_code'],  // 海关返回结果码  | 微信详情：状态码
                'return_info' => $itme['return_info'],  // 海关返回结果描述  | 申报结果说明
                'return_time' => $itme['return_time'],  // 海关回执时间
                'sub_order_id' => $itme['sub_order_id'],  // 拆单时商户子订单号  | 商户子订单号
                'winxin_order_id' => "",  // 微信子订单号
                'ver_dept' => $itme['ver_dept'],    // 验核机构
                'transport_fee' => $itme['transport_fee'], // 物流费
                'paid_guds_amount' => $itme['paid_guds_amount'],  //商品价格
                'identity_check' => $itme['identity_check'],  //订购人和支付人身份信息校验结果
                'duty' => $itme['duty'],     // 关税
                'pay_transaction_id' => $itme['pay_transaction_id'],     // 验核机构交易流水号
            );;
            $temp['chidren'][] = $temp_data;
            $list[] = $temp;
        }
        return [$list, $count];
    }



    /***
     * 导出数据
     * @param $where
     */
    public function getData($params)
    {
        $customsRepository = new CustomsRepository();
        $where = $this->paramsWhere($params);
        $list = $customsRepository->getData($where);
        foreach ($list as &$itme){
            $itme['count'] = 1;
            $itme['status'] = $this->status[$itme['status']];
            $itme['is_split'] = $itme['is_split'] == 'F' ? "否":"是";
        }
        if ($params['paid_channel_name'] == 'Alipay'){
            // 支付宝
            $xlsName = 'alipay_海关清关_'.date("Ymd");
            $xlsCell = [
                ['out_request_no', '报关请求号'],
                ['custom_declare_no', '支付宝报关号'],
                ['out_trade_no', '支付单据号'],
                ['customs_place', '海关编号'],
                ['ebc_code', '商户备案号'],
                ['ebc_name', '商户备案名'],
                ['paid_custom_amount', '报关金额'],
                ['is_split', '是否拆单'],
                ['sub_order_id', '拆单时商户子订单号'],
                ['status', '当前状态'],
                ['note', '备注'],
                ['last_modified_time', '最后更新时间'],
                ['customs_code', '海关返回结果码'],
                ['return_info', '海关返回结果描述'],
                ['return_time', '海关回执时间'],
            ];

        }else if ($params['paid_channel_name'] == 'WechatPay'){
            // 微信
            $xlsName = 'aweixin_海关清关_'.date("Ymd");
            $xlsCell = [
                ['custom_declare_no', '微信支付订单号'],
                ['count', '笔数'],
                ['sub_order_id', '商户子订单号'],
                ['winxin_order_id', '微信子订单号'],
                ['ebc_code', '商户海关备案号'],
                ['customs_place', '海关'],
                ['custom_currency', '币种'],
                ['paid_custom_amount', '应付金额'],
                ['duty', '关税'],
                ['transport_fee', '物流费'],
                ['paid_guds_amount', '商品价格'],
                ['customs_code', '状态码'],  //
                ['return_info', '申报结果说明'],
                ['last_modified_time', '最后更新时间'],
                ['identity_check', '订购人和支付人身份信息校验结果'],
                ['ver_dept', '验核机构'],
                ['pay_transaction_id', '验核机构交易流水号'],
            ];
        }else{
            var_dump("导出失败，未选择支付公司");
            die;
        }
        return [$list, $xlsCell,$xlsName];
    }

    /**
     * 列表查询 where 组装
     * @param $params
     */
    public function paramsWhere($params)
    {
        $where = array();
        if (isset($params['paid_channel_name']) && !empty($params['paid_channel_name'])) {
            $where['paid_channel_name'] =  array('like','%'.$params['paid_channel_name'].'%');
        }
        if (isset($params['order_id']) && !empty($params['order_id'])) {
            $where['order_id'] =  array('like','%'.$params['order_id'].'%');
        }
        if (isset($params['out_trade_no']) && !empty($params['out_trade_no'])) {
            $where['tb_lgt_custom_ord_trade.out_trade_no'] = array('like','%'.$params['out_trade_no'].'%');
        }
        if (isset($params['start_time']) && !empty($params['start_time'])
            && isset($params['end_time']) && !empty($params['end_time'])){
            $where['tb_lgt_custom_ord_trade.req_time'] = array('between',array($params['start_time'],date("Y-m-d H:i:s",strtotime($params['end_time']) + 3600 * 24 -1 )));
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['id'] =  array('in',$params['id']);
        }

        return $where;
    }
}