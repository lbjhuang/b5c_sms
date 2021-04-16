<?php

class OmsAfterSaleService extends Service
{

    public $user_name;
    public $model;
    public $return_table;
    public $return_goods_table;
    public $reissue_table;
    public $reissue_goods_table;
    public $relevance_table;
    public $return_warehouse_table;
    public $order_guds_table;
    public $order_table;
    public $reissue_num_table;
    public $order_extend_table;
    public $order_refund_table;
    public $order_refund_detail_table;

    public $send_wx_msg_result; // 订单退款消息发送结果


    public $repository;

    public $is_batch; // 是否批量申请售后
    public $batch_line; // 批量申请售后的excel数据具体行数
    public $notice_msg; // 提示信息（仅用于批量excel上传申请售后提示错误用途）


    const STATUS_SUBMITTED = 'N002800001';//已提交
    const STATUS_RETURN_ING = 'N002800002';//退货中
    const STATUS_RETURN_SUCCESS = 'N002800003';//退货成功
    const STATUS_REFUSED_RETURN = 'N002800004';//拒绝退货
    const STATUS_SHIPPED = 'N002800005';//已发货
    const STATUS_FINISHED = 'N002800006';//已完成
    const STATUS_REFUSED = 'N002800007';//已拒绝
    const STATUS_CANCEL = 'N002800008';//已取消
    const STATUS_REFUND_ING = 'N002800009';//退款中
    const STATUS_REFUND_SUCCESS = 'N002800010';//已退款
    const STATUS_REFUND_WAIT_AUDIT = 'N002800013';//退款待审核
    const STATUS_REFUND_APPLY = 'N002800012';//申请退款
    const STATUS_REFUND_CANCEL = 'N002800014';//取消退款
    const STATUS_ONLY_REFUND_MONEY = 'N002800021';// 退款不退货

    const STATUS_RETURN_WAIT_USED = 'N002800020';//待使用
    const STATUS_RETURN_CANCEL = 'N002800017';//取消退货
    const STATUS_RETURN_WAIT_INVALID = 'N002800022';//无效

    const AUDIT_STATUS_WAIT = 'N003170003';//等待审核
    const AUDIT_STATUS_DRAFT = 'N003170001';//草稿
    const AUDIT_STATUS_TRASH = 'N003170002';//废弃
    const AUDIT_STATUS_PASS = 'N003170004';//审核通过
    const AUDIT_STATUS_NO_PASS = 'N003170005';//审核不通过
    const AUDIT_STATUS_PAYING = 'N003170006';//付款中
    const AUDIT_STATUS_CANCEL = 'N003170007';//退款取消
    const AUDIT_STATUS_SUCCESS = 'N003170008';//退款成功

    const RETURN_ORDER_GETTING = 'N003770001';//回邮单获取中
    const RETURN_ORDER_FAIL = 'N003770002';//回邮单获取失败
    const RETURN_ORDER_SUCCESS = 'N003770003';//回邮单获取成功
    const RETURN_ORDER_INVALID = 'N003770004';//无效回邮单
    const RETURN_ORDER_WAITING = 'N003770005';//等待中回邮单

    const REISSUE_TYPE = 'N003130001';//售后类型-补发
    const RETURN_TYPE = 'N003140001';//售后类型-退货
    const REFUND_TYPE = 'N003150001';//售后类型-退款

    const TYPE_RETURN = 'return';
    const TYPE_REISSUE = 'reissue';
    const TYPE_REFUND = 'refund';

    const SOURCE_TYPE_ERP = '0';//售后来源类型-ERP
    const SOURCE_TYPE_GP = '1';//售后来源类型-GP

    const REFUND_TYPE_SHIPPING = 'N003160001';//单独退运费

    const EPR_ADMIN_ID = '9186';

    //退货/补发新旧状态映射
    public static $status_map = [
        'N002800002' => ['N002800002', 'N002800001'],
        'N002800017' => ['N002800017', 'N002800008'],
        'N002800018' => ['N002800018', 'N002800007'],
        'N002800019' => ['N002800019'],
        'N002800003' => ['N002800003', 'N002800006'],

        'N002800005' => ['N002800005'],
        'N002800015' => ['N002800015', 'N002800008'],
        'N002800016' => ['N002800016', 'N002800006'],
    ];

    //易达仓库信息map
    public static $eda_warehouse_map = [
        'EDA Hamburg' => 'DE',
        '金鹰波兰仓' => 'PL',
        '易达意大利' => 'IT',
        '易达法国仓' => 'ER',
        '易达西班牙SPAIN' => 'ES',
    ];

    public static $after_sale_cancel_status = [
        '',
        null,
        'N002800008',//已取消
        'N002800014',//取消退款
        'N002800015',//取消补发
        'N002800017',//取消退货
        'N002800022',//无效
        'N003170007',//退款取消
    ];

    public function __construct($model)
    {
        $this->user_name                 = DataModel::userNamePinyin();
        $this->model                     = empty($model) ? new Model() : $model;
        $this->return_table              = M('op_order_return', 'tb_');
        $this->return_goods_table        = M('op_order_return_goods', 'tb_');
        $this->reissue_table             = M('op_order_reissue', 'tb_');
        $this->reissue_goods_table       = M('op_order_reissue_goods', 'tb_');
        $this->relevance_table           = M('op_order_after_sale_relevance', 'tb_');
        $this->return_warehouse_table    = M('op_order_return_warehouse', 'tb_');
        $this->order_guds_table          = M('op_order_guds', 'tb_');
        $this->order_table               = M('op_order', 'tb_');
        $this->reissue_num_table         = M('op_order_reissue_num', 'tb_');
        $this->order_refund_table        = M('op_order_refund', 'tb_');
        $this->order_refund_detail_table = M('op_order_refund_detail', 'tb_');
        $this->order_extend_table        = M('op_order_extend', 'tb_');

        $this->repository = new OmsAfterSaleRepository($this->model);
    }

    public function screenData($order_info = [], $user_data = [])
    {
        if (!$order_info || !$user_data) {
            return false;
        }
        $order_real_info = []; 
        $over_time = 86400;
        $need_update = false;
        foreach ($order_info as $key => $value) {
            $order_msg_send_expire_time = '';
            foreach ($value['user_name'] as $kk => $vv) {
                if (empty($user_data[$vv]) && $vv) { // 缓存中没有的话，从表里查，并重新缓存
                    $need_update = true;
                    $user_data[$vv] = M('admin','bbm_')->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')->where(['bbm_admin.M_NAME' => $vv])->getField('b.wid');
                }
                $order_msg_send_expire_time = RedisModel::get_key('erp_aftersale_refund_msg_' . $value['id'] . '_' . $user_data[$vv]);
                $now_time_str = time();
                if ($now_time_str < $order_msg_send_expire_time) {
                    $order_msg_send_expire_time = date('Y-m-d H:i:s', $order_msg_send_expire_time);
                    $this->send_wx_msg_result[] = "该订单号{$value['order_id']}，目前距离下次发送时间{$order_msg_send_expire_time}未到，请耐心等待";
                    continue;
                }
                $res = [];
                if (isset($user_data[$vv]) && !empty($user_data[$vv])){
                    $res = ApiModel::WorkWxSendMessage($user_data[$vv], "{$value['platform_country_code_val']}站点 对应的订单号‘{$value['order_id']}’ 于'{$value['created_at']}' 发起 售后退款，请前往“售后单列表”进行审核！如已审核请忽略。");
                    //企业微信审批卡片推送
                    $send_email    = [$vv . '@gshopper.com'];
                    $wx_return_res = (new OmsAfterSaleService(null))->bulidAfterSaleApproval($value['id'], $send_email);
                    if ($res['code'] === 200000){
                        $order_msg_send_expire_time = $now_time_str + $over_time;
                        RedisModel::set_key('erp_aftersale_refund_msg_' . $value['id'] . '_' . $user_data[$vv], $order_msg_send_expire_time, null, $over_time);
                        $res['send_user'] = $vv;
                    } else { // 针对辞职后有重新入职，导致原来的wid不可用，消息发送失败的特殊情况处理
                        $map = [];
                        $map['bbm_admin.M_NAME'] = $vv;
                        $map['b.wid'] = array('neq', $user_data[$vv]);
                        $user_data[$vv] = M('admin','bbm_')->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')->where($map)->getField('b.wid');
                        $order_msg_send_expire_time = RedisModel::get_key('erp_aftersale_refund_msg_' . $value['id'] . '_' . $user_data[$vv]);
                        if ($now_time_str < $order_msg_send_expire_time) {
                            $order_msg_send_expire_time = date('Y-m-d H:i:s', $order_msg_send_expire_time);
                            $this->send_wx_msg_result[] = "该订单号{$value['order_id']}，目前距离下次发送时间{$order_msg_send_expire_time}未到，请耐心等待";
                            continue;
                        }
                        if ($user_data[$vv]) {
                            $res = ApiModel::WorkWxSendMessage($user_data[$vv], "{$value['platform_country_code_val']}站点 对应的订单号‘{$value['order_id']}’ 于'{$value['created_at']}' 发起 售后退款，请前往“售后单列表”进行审核！如已审核请忽略。");
                            //企业微信审批卡片推送
                            $wx_return_res = (new OmsAfterSaleService(null))->bulidAfterSaleApproval($value['id'], $send_email);
                            if ($res['code'] === 200000){
                                $order_msg_send_expire_time = $now_time_str + $over_time;
                                RedisModel::set_key('erp_aftersale_refund_msg_' . $value['id'] . '_' . $user_data[$vv], $order_msg_send_expire_time, null, $over_time);
                                $res['send_user'] = $vv;
                            } else {
                                $res['order_id'] = $value['order_id'];
                            }
                        } else {
                            $res['order_id'] = $value['order_id'];
                        }
                    }
                    $this->send_wx_msg_result[] = $res;
                }else{
                    $this->send_wx_msg_result[] = "该ERP账号{$vv}，未绑定企业微信，订单号为{$value['order_id']}";
                }
            }
        }
        if ($need_update) { // 名单有变动，需要重新更新
            $user_info = M('admin','bbm_')
                            ->field('b.wid, bbm_admin.M_NAME')
                            ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
                            ->select();
            $user_info = array_column($user_info, 'wid', 'M_NAME');
            RedisModel::set_key('erp_work_wx_user', json_encode($user_info));
        }
        return false;
    }

    // 获取企业微信与后台账号映射关系
    public function getWorkWxUserInfo()
    {
        $user_info = [];
        $user_data = RedisModel::get_key('erp_work_wx_user');
        if ($user_data) {
            $user_info = json_decode($user_data, true);
        } else {
            $user_info = M('admin','bbm_')
                            ->field('b.wid, bbm_admin.M_NAME')
                            ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
                            ->select();
            $user_info = array_column($user_info, 'wid', 'M_NAME');
            RedisModel::set_key('erp_work_wx_user', json_encode($user_info));
        }
        
        return $user_info;
        
    }

    /**
     * 单条数据发送
     * type: 1为审核通过  2为审核不通过  3为撤销 4为退款完成  5为会计审核失败
     */
    public function screenDataByOne($order_info = [],$type = 1)
    {
        if (!$order_info) {
            return false;
        }
        $user_name = $order_info['created_by'];
        $wid = M('admin', 'bbm_')->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')->where(['bbm_admin.M_NAME' => $user_name])->getField('b.wid');
        switch ($type) {
            case 1:
                $message = "{$order_info['platform_country_code_val']}站点对应的订单号'{$order_info['order_id']}'于'{$order_info['audit_time']}' 由{$order_info['audit_user']}审核通过，备注／建议：{$order_info['audit_opinion']} 。等待财务部门操作中，请稍后";
                break;
            case 2:
                $message = "{$order_info['platform_country_code_val']}站点对应的订单号'{$order_info['order_id']}'于'{$order_info['audit_time']}' 由{$order_info['audit_user']}审核不通过，备注／建议：{$order_info['audit_opinion']} 。请留意！";
                break;
            case 3:
                $tmpArr = M('tb_op_order_refund_log')->where(['refund_id'=>$order_info['id'],'operation_info'=> '会计审核撤回'])->find();
                $remark = $tmpArr['remark'] ? $tmpArr['remark']:'';
                $created_by = $tmpArr['created_by'] ? $tmpArr['created_by'] : '';
                $created_at = $tmpArr['created_at'] ? $tmpArr['created_at'] : date('Y-m-d H:i:s');
                $message = "{$order_info['platform_country_code_val']}站点对应的订单号'{$order_info['order_id']}'于'{$created_at}' 由{$created_by}审核已撤销，备注／建议：{$remark} 。请留意！";
                break;
            case 4:
                $tmpArr = M('tb_op_order_refund_log')->where(['refund_id' => $order_info['id'], 'operation_info' => '确认出账'])->find();
                $remark = $tmpArr['remark'] ? $tmpArr['remark'] : '';
                $created_by = $tmpArr['created_by'] ? $tmpArr['created_by'] : '';
                $created_at = $tmpArr['created_at'] ? $tmpArr['created_at'] : date('Y-m-d H:i:s');
                $message = "{$order_info['platform_country_code_val']}站点对应的订单号'{$order_info['order_id']}'于'{$created_at}'退款成功，备注／建议：{$remark} 。请留意！";
                break;
            case 5:
                $tmpArr = M('tb_op_order_refund_log')->where(['refund_id' => $order_info['id'], 'operation_info' => '会计审核退回'])->find();
                $remark = $tmpArr['remark'] ? $tmpArr['remark'] : '';
                $created_by = $tmpArr['created_by'] ? $tmpArr['created_by'] : '';
                $created_at = $tmpArr['created_at'] ? $tmpArr['created_at'] : date('Y-m-d H:i:s');
                $message = "{$order_info['platform_country_code_val']}站点对应的订单号'{$order_info['order_id']}'于'{$created_at}'退款失败，备注／建议：{$remark} 。请留意！";
                break;
            default:
                # code...
                break;
        }
        
        if(empty($wid)){
            $this->send_wx_msg_result[] = "该ERP账号{ $user_name}，未绑定企业微信，订单号为{$order_info['order_id']}";
        }
        //替换特殊字符，避免发送失败
        $message = str_replace('+','＋',$message);
        $message = str_replace('/','／',$message);
        $message = str_replace('?','？',$message);
        $message = str_replace('%','％',$message);
        $message = str_replace('#','',$message);
        $message = str_replace('&','＆',$message);
        $message = str_replace('=', '=', $message);
        if (empty($message)) {
            $this->send_wx_msg_result[] = "订单号为{$order_info['order_id']},message为空";
        }
      
        
        $res = ApiModel::WorkWxSendMessage($wid,$message);
       
        $this->send_wx_msg_result[] = $res;
        if ($res['code'] != 200000) {
            // 针对辞职后有重新入职，导致原来的wid不可用，消息发送失败的特殊情况处理
            $map = [];
            $map['bbm_admin.M_NAME'] = $user_name;
            $map['b.wid'] = array('neq', $wid);
            $newUserWid = M('admin', 'bbm_')->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')->where($map)->getField('b.wid');
            if(empty($newUserWid)){
                return false;
            }
            $res = ApiModel::WorkWxSendMessage($newUserWid, $message);
            $this->send_wx_msg_result[] = $res;
        }
        
    }
    // 售后退款审核提醒
    public function after_sale_refund_wx_msg()
    {
        $where['r1.type'] = '3';
        $where['r2.status_code'] = self::STATUS_REFUND_WAIT_AUDIT;   
        $return_info = $this->model->table('tb_op_order_after_sale_relevance r1')
            ->field('r1.platform_country_code,r1.platform_code,r1.order_id, r1.type,r2.status_code,r1.created_by,r1.created_at,r2.id')
            ->join('left join tb_op_order_refund r2 on r1.after_sale_id = r2.id ')
            ->where($where)
            ->select();
        $return_info = CodeModel::autoCodeTwoVal($return_info, ['platform_country_code']);
        $after_refund_gp = CodeModel::getEtcKeyValue('N00325'); //GP
        $after_refund_tpp = CodeModel::getEtcKeyValue('N00326'); //TPP
        $user_data = $this->getWorkWxUserInfo(); 
        $return_info = $this->packageReturnInfo($return_info, $after_refund_gp, $after_refund_tpp); // 筛选获取配置正常的订单
        $return_info = $this->screenData($return_info, $user_data); // 消息发送
        $msg = $this->send_wx_msg_result;
        
        
        return $msg;
    }

    // 售后退款审核通过提醒
    public function after_sale_refund_pass_wx_msg($id,$type)
    {
        $where['r1.type'] = '3';
        $where['r2.id'] = $id;
        $where['r2.source_type'] = 0;
        $return_info = $this->model->table('tb_op_order_after_sale_relevance r1')
        ->field('r1.platform_country_code,r1.platform_code,r1.order_id, r1.type,r2.status_code,r1.created_by,r1.created_at,r2.id,r2.audit_opinion,r2.audit_user,r2.audit_time')
        ->join('left join tb_op_order_refund r2 on r1.after_sale_id = r2.id ')
        ->where($where)
        ->find();
        $return_info = CodeModel::autoCodeTwoVal([$return_info], ['platform_country_code'])[0];
       
        $return_info = $this->screenDataByOne($return_info,$type); // 消息发送
        $msg = $this->send_wx_msg_result;
        Logs(['id'=>$id,'type'=>$type,'msg'=> $msg,'return_info'=> $return_info],__CLASS__,__FUNCTION__);
        
    }
    
    // 组装各个订单需要发送消息的用户
    public function packageReturnInfo($return_info = [], $after_refund_gp = [], $after_refund_tpp = [])
    {
        if (!$return_info) {
            return false;
        }
        $return_real_info = [];
        foreach ($return_info as $key => $value) {
            $user_name = '';
            if ($value['platform_code'] === 'N002620800') { // GP订单
                if (!$after_refund_gp[$value['platform_country_code_val']]) {
                    $this->send_wx_msg_result[] = "未知站点{$value['platform_country_code']}-{$value['platform_country_code_val']}，请先在'GP_售后通知'配置(确保CODE开关为已开状态)，订单号为{$value['order_id']}";
                     continue;
                }
                $user_name = $after_refund_gp[$value['platform_country_code_val']];
            } else {
               if (!$after_refund_tpp[$value['created_by']]) {
                    $this->send_wx_msg_result[] = "订单号为{$value['order_id']}，未找到该运营用户{$value['created_by']}对应的运营相关组长或者leader，请先在'“TTP_售后通知配置'配置,或检查是否已关闭该CODE";
                    continue;
               }
               $user_name = $after_refund_tpp[$value['created_by']];
            }

            if (!$user_name) {
                continue;
            }
            $user_name = explode(',', $user_name);
            $value['user_name'] = $user_name;
            $return_real_info[] = $value;
        }
        return $return_real_info;
    }

    /*************************售后申请提交：start*************************/
    public function applySubmit($request_data)
    {
        $return_id = $reissue_id = 0;
        //需求#9557 售后单号要唯一且不含有字母
        $after_sale_no_return  = date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale');//退货售后单
        $after_sale_no_reissue = date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale');//补发售后单
        $order_no              = $request_data['order_info']['order_no'];
        $order_id              = $request_data['order_info']['order_id'];

        $platform_cd           = $request_data['order_info']['platform_cd'];
        $only_return_money = 0;
        if(!empty($request_data['return_info'])) {
            $base_info = $request_data['return_info']['base_info'];
            $only_return_money = isset($base_info['only_return_money']) ? $base_info['only_return_money'] : 0;
        }
        $order_info = $this->order_table
            ->field('ORDER_ID,ORDER_NO,BWC_ORDER_STATUS,PLAT_NAME,PLAT_CD')
            ->where(['ORDER_ID'=>$order_id, 'PLAT_CD'=>$platform_cd])
            ->find();
        if (!in_array($order_info['BWC_ORDER_STATUS'], ['N000550500', 'N000550600', 'N000550800', 'N000550400', 'N000550900', 'N000551000'])) {
            if ($this->is_batch !== true) {
                throw new \Exception(L("状态为：‘待收货’、‘已收货’、‘交易成功’、‘交易关闭’、‘交易取消’的订单才可以申请售后"));
            }
        }
        $after_sale_type = 1;//售后类型，默认是补发
        if (!empty($request_data['return_info']) && !empty($request_data['reissue_info'])) {
            //判断相同sku产品补发数不能大于退货数
            //判断退货和补发的商品编码必须相同
            $this->checkSameSkuNum($request_data['return_info'], $request_data['reissue_info']);
            $after_sale_type = 2;//换货
        }
        if (!empty($request_data['return_info'])) {
            $return_id = $this->returnApplySubmit($request_data['return_info'], $after_sale_no_return, $order_info);
        }

        if (!empty($request_data['reissue_info'])) {
            $reissue_id = $this->reissueApplySubmit($request_data['reissue_info'], $after_sale_no_reissue, $order_info, $after_sale_type);
        }
        //退货单与补发单关系记录
        $plat_country_cd = $order_info['PLAT_CD'];
        $plat_name       = explode('-', $order_info['PLAT_NAME']);
        $plat_cd         = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00262%"]])->getField('CD');
        if (!$plat_cd) {
            $plat_cd = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00083%"]])->getField('CD');//第三方平台
        }
        if ($return_id) {
            $add_data[] = [
                'after_sale_no'         => $after_sale_no_return,
                'order_id'              => $order_id,
                'order_no'              => $order_no,
                'type'                  => 1,
                'after_sale_id'         => $return_id,
                'created_by'            => $this->user_name,
                'updated_by'            => $this->user_name,
                'platform_code'         => $plat_cd,
                'platform_country_code' => $plat_country_cd
            ];
        }
        if ($reissue_id) {
            $add_data[] = [
                'after_sale_no'         => $after_sale_no_reissue,
                'order_id'              => $order_id,
                'order_no'              => $order_no,
                'type'                  => 2,
                'after_sale_id'         => $reissue_id,
                'created_by'            => $this->user_name,
                'updated_by'            => $this->user_name,
                'platform_code'         => $plat_cd,
                'platform_country_code' => $plat_country_cd
            ];
        }
        if (false == $this->relevance_table->addAll($add_data)) {
            throw new \Exception(L('售后单创建失败'));
        }

        if (!empty($request_data['reissue_info'])) {
            //已申请状态改成发货中
            $this->changeReissueStatusToShipped($after_sale_no_reissue, $reissue_id);
        }

        if (!empty($request_data['return_info'])) {
            //已申请状态改成退货中
            if(!$only_return_money) {
                $this->changeReturnStatus(['after_sale_no' => $after_sale_no_return], self::STATUS_RETURN_ING);
                $this->changeReturnGoodsStatus(['return_id' => $return_id], self::STATUS_RETURN_ING);
            }
        }

        //更新订单收入成本冲销状态状态（未冲销，已冲销）
        $this->upOrderChargeOffStatus($request_data['order_info'], 1);

        return [
            'return_id' => $return_id,
            'reissue_id' => $reissue_id,
        ];

    }

    /*************************易达回邮单退货售后申请提交：start*************************/
    public function reOrderApplySubmit($request_data)
    {
        $return_id = $reissue_id = 0;
        //需求#9557 售后单号要唯一且不含有字母
        $after_sale_no_return  = date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale');//退货售后单
        $order_no              = $request_data['order_info']['order_no'];
        $order_id              = $request_data['order_info']['order_id'];
        $platform_cd           = $request_data['order_info']['platform_cd'];
        if (strpos($request_data['warehouse_info'], '易达') === false) {
            throw new \Exception(L('仓库信息（收货方）必须是易达仓库'));
        }
        $where = ['warehouse' => $request_data['warehouse_info']];
        $warehouse = BaseModel::getReturnDeliveryWarehouse($where);
        if (empty($warehouse) || empty($warehouse[0]['THIRD_CD'])) {
            throw new \Exception(L('仓库信息（收货方）未查询到第三方仓库配置'));
        }
        $order_info = $this->order_table
            ->field('ORDER_ID,ORDER_NO,BWC_ORDER_STATUS,PLAT_NAME,PLAT_CD')
            ->where(['ORDER_ID'=>$order_id, 'PLAT_CD'=>$platform_cd])
            ->find();
        $this->model->startTrans();
        if (!empty($request_data['return_info'])) {
            $return_id = $this->returnApplySubmit($request_data['return_info'], $after_sale_no_return, $order_info, 1);
        }
        //退货单与补发单关系记录
        $plat_country_cd = $order_info['PLAT_CD'];
        $plat_name       = explode('-', $order_info['PLAT_NAME']);
        $plat_cd         = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00262%"]])->getField('CD');
        if (!$plat_cd) {
            $plat_cd = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00083%"]])->getField('CD');//第三方平台
        }
        if ($return_id) {
            $add_data[] = [
                'after_sale_no'         => $after_sale_no_return,
                'order_id'              => $order_id,
                'order_no'              => $order_no,
                'type'                  => 1,
                'after_sale_id'         => $return_id,
                'created_by'            => $this->user_name,
                'updated_by'            => $this->user_name,
                'platform_code'         => $plat_cd,
                'platform_country_code' => $plat_country_cd
            ];
        }
        if (false == $this->relevance_table->addAll($add_data)) {
            $this->model->rollback();
            throw new \Exception(L('售后单创建失败'));
        }

        if (!empty($request_data['return_info'])) {
            //已申请状态改成待使用
            $this->changeReturnStatus(['after_sale_no' => $after_sale_no_return], self::STATUS_RETURN_WAIT_USED);
            $this->changeReturnGoodsStatus(['return_id' => $return_id], self::STATUS_RETURN_WAIT_USED);
        }
        //更新订单收入成本冲销状态状态（未冲销，已冲销）
        $this->upOrderChargeOffStatus($request_data['order_info'], 1);
        //保存操作
        $this->model->commit();

        //易达回邮单创建
        $data = $request_data['customer_info'];
        $data['orderId'] = $order_id;
        $data['warehouseNo'] = $warehouse[0]['THIRD_CD'];
        $data['expressName'] = $request_data['express_name']; //物流公司
        $skus = [];
        foreach ($request_data['return_info']['goods_info'] as $item) {
            $list = [];
            $list['sku'] = $item['sku_id'];
            $list['count'] = $item['yet_return_num'];
            $list['ReturnService'] = json_decode($item['handle_type'], true);
            $skus[] = $list;
        }
        $data['skus'] = $skus;
        $data = BaseModel::convertUnder($data);//键字符处理
        $res = json_decode(ApiModel::addReturnOrder($data), true);
        if (!$res || $res['code'] != '200') {
            Logs($res, __FUNCTION__ . '易达回邮单创建api失败', __CLASS__);
            //创建回邮单api失败 无效回邮单
            $return_res = $this->return_table->where(['id' => $return_id])->save(['reply_order_no' => $res['data'], 'status_code' => self::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => self::RETURN_ORDER_INVALID]);
            if (!$return_res) {
                throw new \Exception(L('回邮单状态保存失败' . $return_res));
            }
            //创建回邮单api失败 删除无效回邮单
            /*$return_res = $this->return_table->where(['id' => $return_id])->delete();
            if (!$return_res) {
                throw new \Exception(L('易达回邮单创建api失败，售后单删除失败' . $return_res));
            }*/
            //回邮单获取中
            throw new \Exception(L('易达回邮单创建api失败，反馈信息：' . $res['msg']));
        } else {
            //成功获取回邮单  回邮单获取中
            $return_res = $this->return_table->where(['id' => $return_id])->save(['reply_order_no' => $res['data'], 'status_code' => self::STATUS_RETURN_WAIT_USED, 'reply_status_code' => self::RETURN_ORDER_GETTING]);
            if (!$return_res) {
                throw new \Exception(L('回邮单状态、回邮单号保存失败' . $return_res));
            }
        }
        return [
            'after_sale_no' => $after_sale_no_return,
            'order_id'      => $order_id,
            'order_no'      => $order_no,
            'platform_code' => $platform_cd
        ];
    }

    /*************************自动易达回邮单退货售后申请提交：start*************************/
    public function addReturnOrder($request_data)
    {
        $order_no              = $request_data['order_info']['order_no'];
        $order_id              = $request_data['order_info']['order_id'];
        $platform_cd           = $request_data['order_info']['platform_cd'];
        $return = M('op_order_return', 'tb_')->field('id,after_sale_no')
            ->where(['status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_INVALID,
                'order_id' => $order_id, 'platform_cd' => $platform_cd])->find();
        if (empty($return)) {
            return $this->reOrderApplySubmit($request_data);
        }
        if (strpos($request_data['warehouse_info'], '易达') === false) {
            throw new \Exception(L('仓库信息（收货方）必须是易达仓库'));
        }
        $where = ['warehouse' => $request_data['warehouse_info']];
        $warehouse = BaseModel::getReturnDeliveryWarehouse($where);
        if (empty($warehouse) || empty($warehouse[0]['THIRD_CD'])) {
            throw new \Exception(L('仓库信息（收货方）未查询到第三方仓库配置'));
        }
        $return_id = $return['id'];
        $after_sale_no_return = $return['after_sale_no'];
        //易达回邮单创建
        $data = $request_data['customer_info'];
        $data['orderId'] = $order_id;
        $data['warehouseNo'] = $warehouse[0]['THIRD_CD'];
        $data['expressName'] = $request_data['express_name']; //物流公司
        $skus = [];
        foreach ($request_data['return_info']['goods_info'] as $item) {
            $list = [];
            $list['sku'] = $item['sku_id'];
            $list['count'] = $item['yet_return_num'];
            $list['ReturnService'] = json_decode($item['handle_type'], true);
            $skus[] = $list;
        }
        $data['skus'] = $skus;
        $data = BaseModel::convertUnder($data);//键字符处理
        $res = json_decode(ApiModel::addReturnOrder($data), true);
        Logs([$res], 'addReturnOrder', 'addReturnOrder');
        if (!$res || $res['code'] != '200') {
            Logs($res, __FUNCTION__ . '易达回邮单创建api失败', __CLASS__);
            //创建回邮单api失败 无效回邮单
            $return_res = $this->return_table->where(['id' => $return_id])->save(['reply_order_no' => $res['data'], 'status_code' => self::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => self::RETURN_ORDER_INVALID]);
            if (!$return_res) {
                throw new \Exception(L('回邮单状态保存失败' . $return_res));
            }
            //回邮单获取中
            throw new \Exception(L('易达回邮单创建api失败，反馈信息：' . $res['msg']));
        } else {
            //成功获取回邮单  回邮单获取中
            $return_res = $this->return_table->where(['id' => $return_id])->save(['reply_order_no' => $res['data'], 'status_code' => self::STATUS_RETURN_WAIT_USED, 'reply_status_code' => self::RETURN_ORDER_GETTING]);
            if (!$return_res) {
                throw new \Exception(L('回邮单状态、回邮单号保存失败' . $return_res));
            }
        }
        return [
            'after_sale_no' => $after_sale_no_return,
            'order_id'      => $order_id,
            'order_no'      => $order_no,
            'platform_code' => $platform_cd
        ];
    }

    /**
     * 判断相同sku产品补发数不能大于退货数
     * 判断退货和补发的商品编码必须相同
     * @param type $return_info 退货信息
     * @param type $reissue_info 补发信息
     * @throws \Exception
     */
    private function checkSameSkuNum($return_info, $reissue_info)
    {
        $return_goods_info  = $return_info['goods_info'];
        $reissue_goods_info = $reissue_info['goods_info'];
        $return_map         = array_column($return_goods_info, 'yet_return_num', 'sku_id');
        $reissue_map        = array_column($reissue_goods_info, 'yet_reissue_num', 'sku_id');
        $return_res         = array_intersect_key($return_map, $reissue_map);
        $reissue_res        = array_intersect_key($reissue_map, $return_map);
        if (count($reissue_map) != count($return_map) || !empty(array_diff_key($return_map, $reissue_map))) {
            throw new \Exception(L('退货和补发的商品编码必须相同'));
        }
        foreach ($return_res as $sku_id => $num) {
            if ($reissue_res[$sku_id] > $num) {
                throw new \Exception(L('编码为：' . $sku_id . '的商品补发件数不能大于退货件数'));
            }
        }
    }

    //申请退货单提交
    private function returnApplySubmit($request_data, $after_sale_no, $order_info, $type = 0)
    {
        //退货单主表数据添加
        $order_id    = $order_info['ORDER_ID'];
        $order_no    = $order_info['ORDER_NO'];
        $platform_cd = $order_info['PLAT_CD'];
        $only_return_money = isset($request_data['base_info']['only_return_money']) ? $request_data['base_info']['only_return_money'] : 0;
        $status_code =  self::STATUS_SUBMITTED;
        if($only_return_money) {
            $status_code = self::STATUS_ONLY_REFUND_MONEY;
        }
        if (!$this->return_table->create($request_data['base_info'])) {
            throw new \Exception(L('创建退货单数据失败'));
        }
        $this->return_table->created_by    = $this->return_table->updated_by = $this->user_name;
        $this->return_table->after_sale_no = $after_sale_no;
        $this->return_table->status_code   = $status_code;
        $this->return_table->order_no      = $order_no;
        $this->return_table->order_id      = $order_id;
        $this->return_table->platform_cd   = $platform_cd;
        if (1 == $type) $this->return_table->reply_status_code  = self::RETURN_ORDER_GETTING; //OTTO申请售后回邮单获取状态默认回邮单获取中

        if (!$return_id = $this->return_table->add()) {
            throw new \Exception(L('退货单申请失败'));
        }
        //退货单子表数据添加
        $goods_info = $request_data['goods_info'];
        foreach ($goods_info as &$value) {
            $value['return_id']   = $return_id;
            $value['created_by']  = $value['updated_by'] = $this->user_name;
            $value['status_code'] = $status_code;
            $order_goods_num      = $this->getOrderGoodsNum($order_id, $platform_cd, $value['sku_id'], self::TYPE_RETURN);//获取订单商品数量
            if (!$order_goods_num) {
                if ($this->is_batch !== true) {
                    throw new \Exception(L('订单未找到该商品或商品数量小于1'));
                }
            }
            $value['order_goods_num']    = $order_goods_num;
            $value['over_return_num']    = $this->getOverReturnNum($order_id, $platform_cd, $value);
            $value['over_warehouse_num'] = $value['yet_return_num'];

            if ($this->isGift($order_id, $platform_cd, $value['sku_id'])) {
                $value['is_gift'] = 1;
            } else {
                $value['is_gift'] = 0;
            }
        }
        $flag = array_column($goods_info, 'is_gift');
        array_multisort($flag, SORT_DESC, $goods_info);
        if (false == $this->return_goods_table->addAll($goods_info)) {
            throw new \Exception(L('退货单商品添加失败'));
        }
        $map = ['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd];
        $parent_order_id = $this->order_table->where($map)->getField('PARENT_ORDER_ID');
        if ($parent_order_id) {
            if (false === $this->order_table->where(['ORDER_ID' => $parent_order_id])->save(['is_apply_after_sale' => 1, 'UPDATE_TIME' => dateTime()])) {
                throw new \Exception(L('标记父订单已申请售后失败'));
            }
        }
        if (false === $this->order_table->where($map)->save(['is_apply_after_sale' => 1, 'UPDATE_TIME' => dateTime()])) {
            throw new \Exception(L('标记订单已申请售后失败'));
        }
        return $return_id;
    }

    public function isGift ($order_id, $platform_cd, $sku_id)
    {
        return $this->order_guds_table->where(['ORDER_ID'=>$order_id, 'PLAT_CD'=>$platform_cd, 'B5C_SKU_ID'=>$sku_id])->getField('guds_type');
    }

    /**
     * 计算剩余可退货件数
     * @param type $order_id 订单id
     * @param type $platform_cd 平台cd
     * @param type $goods_info 商品信息
     * @return type
     * @throws \Exception
     */
    private function getOverReturnNum($order_id, $platform_cd, $goods_info)
    {
        $sku_id          = $goods_info['sku_id'];
        $yet_return_num  = $goods_info['yet_return_num'];
        $order_goods_num = $goods_info['order_goods_num'];
        $where           = [
            'oor.order_id'    => $order_id,
            'oor.platform_cd' => $platform_cd,
            'org.sku_id'      => $sku_id,
            'org.status_code' => ['neq', self::STATUS_CANCEL],
            //排除 售后状态 取消退货、无效的售后单
            'oor.status_code' => ['not in', [self::STATUS_RETURN_CANCEL, self::STATUS_RETURN_WAIT_INVALID]]
        ];
        $return_info     = $this->model->table('tb_op_order_return oor')
            ->field('SUM(org.yet_return_num-org.refuse_warehouse_num) AS all_return_num')
            ->join('left join tb_op_order_return_goods org on oor.id = org.return_id ')
            ->where($where)
            ->select();
        $all_return_num  = $return_info[0]['all_return_num'];

        if (bcadd($all_return_num, $yet_return_num) > $order_goods_num) {
            if ($this->is_batch !== true) {
                throw new \Exception(L('商品编码为：' . $sku_id . '的商品退货件数不能大于订单可退货件数'));
            }
        }
        return bcsub($order_goods_num, $all_return_num);
    }

    /**
     * 补发单提交
     * @param $request_data 补发数据
     * @param $after_sale_no 售后单号
     * @param $order_info 订单信息
     * @param $after_sale_type 售后类型
     * @return mixed
     * @throws Exception
     */
    private function reissueApplySubmit($request_data, $after_sale_no, $order_info, $after_sale_type)
    {
        $order_id    = $order_info['ORDER_ID'];
        $order_no    = $order_info['ORDER_NO'];
        $platform_cd = $order_info['PLAT_CD'];
        if (!$this->reissue_table->create($request_data['base_info'])) {
            throw new \Exception(L('创建补发单数据失败'));
        }
        $reissue_no                         = getMsec();
        $this->reissue_table->created_by    = $this->return_table->updated_by = $this->user_name;
        $this->reissue_table->after_sale_no = $after_sale_no;
        $this->reissue_table->reissue_no    = $reissue_no;
        $this->reissue_table->status_code   = self::STATUS_SUBMITTED;
        $this->reissue_table->order_no      = $order_no;
        $this->reissue_table->order_id      = $order_id;
        $this->reissue_table->platform_cd   = $platform_cd;
        if (!$reissue_id = $this->reissue_table->add()) {
            throw new \Exception(L('补发单申请失败'));
        }
        $all_sku_ids   = array_column($request_data['goods_info'], 'sku_id');//提交的所有sku
        $order_sku_ids = $this->searchOrderSkuIds($order_id, $platform_cd); //在订单中的sku
        $gift_sku_ids  = array_diff($all_sku_ids, $order_sku_ids);//和订单sku不同的sku
        $ex_sku_ids    = array_diff($all_sku_ids, $gift_sku_ids);//和订单sku相同的sku

        foreach ($request_data['goods_info'] as &$value) {
            if (in_array($value['sku_id'], $gift_sku_ids)) {
                $gift_goods_info[] = $value;
            } else if (in_array($value['sku_id'], $ex_sku_ids)) {
                $ex_goods_info[] = $value;
            }
        }

        if (!empty($ex_goods_info)) {
            foreach ($ex_goods_info as &$value) {
                $value['reissue_id']  = $reissue_id;
                $value['created_by']  = $value['updated_by'] = $this->user_name;
                $value['status_code'] = self::STATUS_SUBMITTED;
                $order_goods_num      = $this->getOrderGoodsNum($order_id, $platform_cd, $value['sku_id'], self::TYPE_REISSUE);//获取订单商品数量
                if (!$order_goods_num) {
                    throw new \Exception(L('订单未找到该商品或商品数量小于1'));
                }
                $value['order_goods_num'] = $order_goods_num;
                $over_reissue_num         = $this->getOverReissueNum($order_id, $platform_cd, $value['sku_id'], $order_goods_num, $value['yet_reissue_num']);
                if ($over_reissue_num < 0) {
                    $value['over_reissue_num'] = 0;
                } else {
                    $value['over_reissue_num'] = $over_reissue_num;
                }
            }
            if (!$this->reissue_goods_table->addAll($ex_goods_info)) {
                throw new \Exception(L('补发单商品添加失败'));
            }
        }
        if (!empty($gift_goods_info)) {
            //补发sku和订单sku不同时，补发数依次按顺序扣减各个和订单sku相同的sku补发数
            foreach ($gift_goods_info as $key => &$value) {
                $value['is_gift']     = 1;
                $value['reissue_id']  = $reissue_id;
                $value['created_by']  = $value['updated_by'] = $this->user_name;
                $value['status_code'] = self::STATUS_SUBMITTED;
                $order_goods_num      = $this->getOrderGoodsNum($order_id, $platform_cd, $value['sku_id'], self::TYPE_REISSUE);//获取订单商品数量
                if (!$order_goods_num) {
                    throw new \Exception(L('订单未找到该商品或商品数量小于1'));
                }
                $value['order_goods_num'] = $order_goods_num;
                $over_reissue_num         = $this->getOverReissueNum($order_id, $platform_cd, $value['sku_id'], $order_goods_num);
                if ($over_reissue_num < 0) {
                    $value['over_reissue_num'] = 0;
                } else {
                    $value['over_reissue_num'] = $over_reissue_num;
                }
                $sum = 0;

                if (!$id = $this->reissue_goods_table->add($value)) {
                    throw new \Exception(L('补发单商品添加失败'));
                }
                foreach ($order_sku_ids as $k => &$v) {
                    $temp_count = $value['yet_reissue_num'] - $sum;
                    $over_num   = $this->getOverReissueNum($order_id, $platform_cd, $v);
                    if ($over_num <= 0) {
                        continue;
                    }
                    if ($over_num < $temp_count) {
                        unset($order_sku_ids[$k]);
                        $num      = $over_num;
                        $sum      += $num;
                        $data_num = [
                            'order_no'         => $order_no,
                            'order_id'         => $order_id,
                            'platform_cd'      => $platform_cd,
                            'sku_id'           => $v,
                            'num'              => $num,
                            'created_by'       => $this->user_name,
                            'updated_by'       => $this->user_name,
                            'reissue_id'       => $reissue_id,
                            'reissue_goods_id' => $id,
                        ];
                        if (!$this->reissue_num_table->add($data_num)) {
                            throw new \Exception(L('记录补发数量失败'));
                        }
                        continue;
                    } else if ($over_num == $temp_count) {
                        unset($order_sku_ids[$k]);
                        $num      = $over_num;
                        $sum      += $num;
                        $data_num = [
                            'order_no'         => $order_no,
                            'order_id'         => $order_id,
                            'platform_cd'      => $platform_cd,
                            'sku_id'           => $v,
                            'num'              => $num,
                            'created_by'       => $this->user_name,
                            'updated_by'       => $this->user_name,
                            'reissue_id'       => $reissue_id,
                            'reissue_goods_id' => $id,
                        ];
                        if (!$this->reissue_num_table->add($data_num)) {
                            throw new \Exception(L('记录补发数量失败'));
                        }
                        break;
                    } else if ($over_num > $temp_count) {
                        $num      = $value['yet_reissue_num'];
                        $data_num = [
                            'order_no'         => $order_no,
                            'order_id'         => $order_id,
                            'platform_cd'      => $platform_cd,
                            'sku_id'           => $v,
                            'num'              => $num,
                            'created_by'       => $this->user_name,
                            'updated_by'       => $this->user_name,
                            'reissue_id'       => $reissue_id,
                            'reissue_goods_id' => $id,
                        ];
                        if (!$this->reissue_num_table->add($data_num)) {
                            throw new \Exception(L('记录补发数量失败'));
                        }
                        break;
                    }

                }
            }
        }

        //oms创建该补发订单
        $new_id = $this->createReissueOrder($order_id, $platform_cd, $reissue_no, $request_data, $after_sale_type);

        //新订单和补发单进行id关联
        $new_order_id = $this->order_table->where(['ID' => $new_id])->getField('ORDER_ID');
        if (false === $this->reissue_table->where(['id' => $reissue_id])->save(['reissue_order_id' => $new_order_id])) {
            throw new \Exception(L('补发单和订单ID关联失败'));
        }
        $map = ['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd];
        $parent_order_id = $this->order_table->where($map)->getField('PARENT_ORDER_ID');
        if ($parent_order_id) {
            if (false === $this->order_table->where(['ORDER_ID' => $parent_order_id])->save(['is_apply_after_sale' => 1, 'UPDATE_TIME' => dateTime()])) {
                throw new \Exception(L('标记父订单已申请售后失败'));
            }
        }
        if (false === $this->order_table->where($map)->save(['is_apply_after_sale' => 1, 'UPDATE_TIME' => dateTime()])) {
            throw new \Exception(L('标记订单已申请售后失败'));
        }
        return $reissue_id;
    }

    /**
     * 计算剩余可补发件数
     * @param  [type] $platform_cd
     * @param  [type] $order_id        [第三方订单id]
     * @param  [type] $sku_id          [skud_id]
     * @param  string $order_goods_num [订单商品数量]
     * @param  string $submit_num [提交补发数量]
     * @return [type]                  [description]
     */
    private function getOverReissueNum($order_id, $platform_cd, $sku_id, $order_goods_num = 0, $submit_num = 0)
    {
        if (!$order_goods_num) {
            $order_goods_num = $this->getOrderGoodsNum($order_id, $platform_cd, $sku_id);
        }
        if (is_array($sku_id)) {
            $where = [
                'oor.order_id'    => $order_id,
                'oor.platform_cd' => $platform_cd,
                'org.sku_id'      => ['in', $sku_id],
                'org.status_code' => ['neq', self::STATUS_CANCEL]
            ];
            $map   = [
                'order_id'    => $order_id,
                'platform_cd' => $platform_cd,
                'sku_id'      => ['in', $sku_id],
                'status_code' => ['neq', self::STATUS_CANCEL]
            ];

        } else {
            $where = [
                'oor.order_id'    => $order_id,
                'oor.platform_cd' => $platform_cd,
                'org.sku_id'      => $sku_id,
                'org.status_code' => ['neq', self::STATUS_CANCEL]
            ];
            $map   = [
                'order_id'    => $order_id,
                'platform_cd' => $platform_cd,
                'sku_id'      => $sku_id,
                'status_code' => ['neq', self::STATUS_CANCEL]
            ];
        }
        $all_reissue_num = $this->model->table('tb_op_order_reissue oor')
            ->join('left join tb_op_order_reissue_goods org on oor.id = org.reissue_id ')
            ->where($where)
            ->sum('org.yet_reissue_num');
        $all_other_num   = $this->reissue_num_table->where($map)->sum('num');
        return $order_goods_num - $all_reissue_num - $all_other_num;
    }

    /**
     * oms创建补发订单
     * @param $platform_cd
     * @param $order_ori_id 第三方订单id
     * @param $reissue_no 补发单号（新生成订单的第三方订单号）
     * @param $data 补发数据
     * @param $after_sale_type 售后类型
     * @return mixed
     * @throws Exception
     */
    public function createReissueOrder($order_ori_id, $platform_cd, $reissue_no, $data, $after_sale_type)
    {
        $base_info  = $data['base_info'];
        $goods_info = $data['goods_info'];

        $country      = trim($base_info['country_id']);
        $condition    = [
            'zh_name'    => $country,
            '_logic'     => 'OR',
            'two_char'   => $country,
            '_logic'     => 'OR',
            'three_char' => $country,
            '_logic'     => 'OR',
            'en_name'    => $country,
        ];
        $address_info = M('ms_user_area', 'tb_')->where($condition)->find();
        if (empty($address_info)) {
            $address_info['zh_name'] = $address_info['two_char'] = $country;
            $address_info['id']      = 0;
            Logs($condition, __FUNCTION__ . " 补发单号：$reissue_no", __CLASS__);
        } else {
            $address_info['zh_name'] = $country;
        }
        $is_remote_area = 0;
        $area_conf      = M('op_area_configuration', 'tb_')
            ->where(['country_id' => $address_info['id'], 'prefix_postal_code' => $base_info['postal_code']])
            ->find();

        if ($area_conf) {
            $is_remote_area = 1;
        }
        $date  = dateTime();
        $order = $this->order_table->where(['ORDER_ID' => $order_ori_id, 'PLAT_CD' => $platform_cd])->find();
        if (empty($order)) {
            throw new \Exception(L('订单未找到'));
        }
        $order_id = '0000' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('reissue-order', 5);
        unset($order['ID']);
        $order['ORDER_ID']                  = $order_id;
        $order['USER_EMAIL']                = $base_info['email'];
        $order['ADDRESS_USER_NAME']         = $base_info['receiver_name'];
        $order['ADDRESS_USER_PHONE']        = $base_info['receiver_phone'];
        $order['RECEIVER_TEL']              = NULL;
        $order['ADDRESS_USER_COUNTRY']      = $address_info['zh_name'];
        $order['ADDRESS_USER_COUNTRY_EDIT'] = $address_info['zh_name'];
        $order['ADDRESS_USER_COUNTRY_ID']   = $address_info['id'];
        $order['ADDRESS_USER_COUNTRY_CODE'] = $address_info['two_char'];
        $order['ADDRESS_USER_CITY']         = $base_info['city_id'];
        $order['ADDRESS_USER_PROVINCES']    = $base_info['province_id'];
        $order['ADDRESS_USER_ADDRESS1']     = $base_info['address'];
        $order['ADDRESS_USER_POST_CODE']    = $base_info['postal_code'];

        $order['ORDER_NO'] = $reissue_no;
        $order['B5C_ORDER_NO']      = NULL;
        $order['SEND_ORD_STATUS']   = 'N001821000';
        $order['ORDER_STATUS']      = 'N000550400';
        $order['BWC_ORDER_STATUS']  = 'N000550400';
        $order['ORDER_UPDATE_TIME'] = $date;
        $order['ORDER_TIME']        = $date;
        $order['ORDER_PAY_TIME']    = $date;
        $order['ORDER_CREATE_TIME'] = $date;
        // $order['PAY_CURRENCY']               = '';
        $order['PAY_ITEM_PRICE']                = 0;
        $order['PAY_TOTAL_PRICE']               = 0;
        $order['PAY_SHIPING_PRICE']             = 0;
        $order['PAY_SETTLE_PRICE']              = 0;
        $order['PAY_SETTLE_PRICE_DOLLAR']       = 0;
        $order['PAY_VOUCHER_AMOUNT']            = 0;
        $order['PAY_WRAPPER_AMOUNT']            = 0;
        $order['PAY_INSTALMENT_SERVICE_AMOUNT'] = 0;
        $order['amount_freight']                = 0;
        $order['pre_amount_freight']            = 0;
        $order['PAY_PRICE']                     = NULL;
        $order['UPDATE_TIME']                   = $date;
        $order['REFUND_STAT_CD']                = NULL;
        $order['REMARK_MSG']                    = NULL;
        $order['SHIPPING_MSG']                  = NULL;
        $order['SHIPPING_TIME']                 = NULL;
        $order['PAY_TOTAL_PRICE_DOLLAR']        = 0;
        $order['updated_at']                    = $date;
        $order['ORDER_CREATE_TIME']             = $date;
        $order['is_remote_area']                = $is_remote_area;
        $order['indicator_update_time']         = NULL;
        $order['PARENT_ORDER_ID']               = NULL;
        $order['CHILD_ORDER_ID']                = NULL;
        $order['after_sale_type']               = $after_sale_type;
        $order['SEND_ORD_TIME']                 = '0000-00-00 00:00:00';

        //一键获取订单，面单获取状态改为待获取
        $order['SURFACE_WAY_GET_CD'] == 'N002010100' && $order['LOGISTICS_SINGLE_STATU_CD'] = 'N002080100';

        $res_order = $this->order_table->add($order);
        if (!$res_order) {
            throw new \Exception(L('添加OMS订单失败'));
        }
        //ebay站点/店铺
        $ebay_sites = TbMsCmnCdModel::getSiteCdsByETC3(TbMsCmnCdModel::$platform_ebay);
        $model = new PmsBaseModel();
        foreach ($goods_info as $value) {
            if (is_numeric($value['sku_id'])){
                $sku_info = $model->table('product_sku')->where(array('sku_id'=> $value['sku_id']))->find();
                if (empty($sku_info)){
                    throw new \Exception(L('补发单商品信息不存在'));
                }
            }else{
                throw new \Exception(L('SKU_ID填写有误'));
            }
            $order_guds = $this->order_guds_table->where(['ORDER_ID' => $order_ori_id, 'PLAT_CD' => $platform_cd, 'B5C_SKU_ID' => $value['sku_id']])->find();
            if ($order_guds) {
                unset($order_guds['ID']);
                $order_guds['ORDER_ID']   = $order_id;
                $order_guds['ITEM_COUNT'] = $value['yet_reissue_num'];
                $order_guds['ITEM_PRICE'] = 0;
            } else {
                //是赠品
                $order_guds = $this->order_guds_table->where(['ORDER_ID' => $order_ori_id, 'PLAT_CD' => $platform_cd])->find();
                unset($order_guds['ID']);
                $order_guds['ORDER_ID']      = $order_id;
                $order_guds['ITEM_COUNT']    = $value['yet_reissue_num'];
                $order_guds['ITEM_PRICE']    = 0;
                $order_guds['PAID_PRICE']    = 0;
                $order_guds['CURRENCY']      = '';
                $order_guds['CREATE_AT']     = $order_guds['UPDATE_AT'] = dateTime();
                $order_guds['B5C_SKU_ID']    = $value['sku_id'];
                $order_guds['CUSTOMS_PRICE'] = 0;
            }
            //ebay平台指定需要16位item_id
            if (in_array($order_guds['PLAT_CD'], $ebay_sites)) {
                $order_guds['item_id'] = uniqid('eby');
            } else {
                $order_guds['item_id'] = uuid();
            }
            $add[] = $order_guds;
        }
        $res_goods = $this->order_guds_table->addAll($add);
        if (!$res_goods) {
            throw new \Exception(L('添加OMS订单商品失败'));
        }
        $order_extends = [
            'order_id'   =>$order_id,
            'plat_cd'    => $order['PLAT_CD'],
            'created_by' => DataModel::userNamePinyin(),
            'created_at' => $date,
            'updated_by' => DataModel::userNamePinyin(),
            'updated_at' => $date,
        ];
        $res_extends = $this->order_extend_table->add($order_extends);
        if(!$res_extends) {
            throw new \Exception(L('订单扩展表添加失败'));
        }

        return $res_order;
    }
    /*************************售后申请提交：end*************************/

    /*************************改变售后单状态：start*************************/
    private function changeReturnStatus($where, $status_code)
    {
        if (empty($where)) {
            throw new \Exception(L('条件不能为空'));
        }
        $return_info = $this->return_table->where($where)->find();
        $platform_cd = $this->relevance_table->where(['after_sale_id'=>$return_info['id'], 'type'=>1])->getField('platform_country_code');
        $res = $this->return_table->where($where)->save(['status_code' => $status_code]);
        if (false === $res) {
            throw new \Exception(L('退货单总状态修改失败'));
        }
        if (!$return_info['order_id'] || !$platform_cd) {
            throw new \Exception(L('退货单平台参数缺失'));
        }
        $this->synOrderAfterSaleStatus($return_info['order_id'],$platform_cd,$status_code,self::RETURN_TYPE);
    }

    private function changeReturnGoodsStatus($where, $status_code, $is_strict = true)
    {
        if ($is_strict) {
            if (empty($where)) {
                throw new \Exception(L('条件不能为空'));
            }
            $res = $this->return_goods_table->where($where)->save(['status_code' => $status_code]);
            if (false === $res) {
                throw new \Exception(L('退货单状态修改失败'));
            }
        } else {
            $res = $this->return_goods_table->where($where)->save(['status_code' => $status_code]);
            if (false === $res) {
                Logs($where, __FUNCTION__ . ' status change fail', __CLASS__);
            }
        }
    }

    //改变补发单状态to发货中
    private function changeReissueStatusToShipped($after_sale_no, $reissue_id)
    {
        $res_reissue = $this->reissue_table->where(['after_sale_no' => $after_sale_no])->save(['status_code' => self::STATUS_SHIPPED]);
        if (false === $res_reissue) {
            throw new \Exception(L('补发单总状态修改失败'));
        }
        $res_goods = $this->reissue_goods_table->where(['reissue_id' => $reissue_id])->save(['status_code' => self::STATUS_SHIPPED]);
        if (false === $res_goods) {
            throw new \Exception(L('补发单状态修改失败'));
        }
        $reissue_info = $this->relevance_table->where(['after_sale_id'=>$reissue_id, 'type'=>2])->find();
        if (!$reissue_info['order_id'] || !$reissue_info['platform_country_code']) {
            throw new \Exception(L('补发单平台参数缺失'));
        }
        $this->synOrderAfterSaleStatus($reissue_info['order_id'],$reissue_info['platform_country_code'],self::STATUS_SHIPPED,self::REISSUE_TYPE);
    }

    //OMS订单出库，改变补发单的状态
    public function changeReissueStatusToFinished($b5c_order_no)
    {
        foreach ($b5c_order_no as &$no) {
            $order_info = $this->order_table->field('PARENT_ORDER_ID,PLAT_CD')->where(['B5C_ORDER_NO' => $no])->find();
            if (empty($order_info)) {
                continue;
            }
            $parent_status = $this->order_table->where(['ORDER_ID'=>$order_info['PARENT_ORDER_ID'], 'PLAT_CD'=>$order_info['PLAT_CD']])->getField('BWC_ORDER_STATUS');
            if ($parent_status == 'N000550500') {
                continue;
            }
            unset($no);
        }
        $reissue_nos = $this->order_table->where(['B5C_ORDER_NO' => ['in', $b5c_order_no]])->getField('ORDER_NO', true);
        $where       = ['r.reissue_no' => ['in', $reissue_nos]];
        $reissue_info = $this->model->table('tb_op_order_after_sale_relevance rel')
            ->field('rel.platform_country_code,r.*')
            ->join('left join tb_op_order_reissue r on r.id = rel.after_sale_id and rel.type = 2')
            ->where($where)
            ->select();
        $reissue_ids = array_column($reissue_info, 'id');
        if (empty($reissue_ids)) {
            return true;
        }
        $res_reissue = $this->reissue_table->where($where)->save(['status_code' => self::STATUS_FINISHED]);
        if (false != $res_reissue) {
            foreach ($reissue_info as $item) {
                $this->synOrderAfterSaleStatus($item['order_id'],$item['platform_country_code'],self::STATUS_FINISHED,self::REISSUE_TYPE, false);
            }
            $res_goods = $this->reissue_goods_table->where(['reissue_id' => ['in', $reissue_ids]])->save(['status_code' => self::STATUS_FINISHED]);
            if (false == $res_goods) {
                Logs($reissue_ids, __FUNCTION__ . ' reissue_id fail', __CLASS__);
            }

        } else {
            Logs($reissue_nos, __FUNCTION__ . ' reissue_no fail', __CLASS__);
        }
        return true;
    }
    /*************************改变售后单状态：end*************************/

    /*************************各种展示信息：start*************************/

    //售后申请-售后单信息
    /**
     * @param $order_id
     * @param $platform_cd
     * @return mixed
     */
    public function getApplyDetail($order_id, $platform_cd)
    {
        $order_info  = $this->getOrderInfo($order_id, $platform_cd);
        $order_info  = SkuModel::productInfo($order_info);
        $detail_info = $order_info;
        foreach ($detail_info as &$value) {
            list($yet_return_num, $yet_reissue_num) = $this->countYetApplyNum($order_id, $platform_cd, $value['sku_id']);
            $goods_return_num         = $this->getOrderGoodsNum($order_id, $platform_cd, $value['sku_id'], self::TYPE_RETURN);
            $goods_reissue_num        = $this->getOrderGoodsNum($order_id, $platform_cd, $value['sku_id'], self::TYPE_REISSUE);
            $value['order_goods_num'] = $goods_return_num;
            $value['over_return_num'] = $goods_return_num - $yet_return_num;

            $value['over_reissue_num'] = $goods_reissue_num - $yet_reissue_num;
        }

        return $detail_info;
    }

    /**
     * ps:待优化
     * 计算已经退货的数量及已经补发的数量
     * @param  [type] $order_id
     * @param  [type] $platform_cd
     * @param  [type] $sku_id
     * @return [type]
     */
    public function countYetApplyNum($order_id, $platform_cd, $sku_id)
    {
        $order_count = $this->order_table->where(['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd])->count();
        if ($order_count > 1) {
            //有拆单
            $return_info    = $this->model->table('tb_op_order_return oor')
                ->field('SUM(yet_return_num - refuse_warehouse_num) AS yet_return_num')
                ->join('left join tb_op_order_return_goods org on oor.id = org.return_id')
                ->where(['oor.platform_cd' => $platform_cd, 'org.sku_id' => $sku_id])
                ->where(['oor.order_id' => $order_id])
                ->where(['org.status_code' => ['neq', self::STATUS_CANCEL]])
                //排除 售后状态 取消退货、无效的售后单
                ->where(['oor.status_code' => ['not in', [self::STATUS_RETURN_CANCEL, self::STATUS_RETURN_WAIT_INVALID]]])
                ->select();
            $yet_return_num = $return_info[0]['yet_return_num'];

            $yet_reissue_num = $this->model->table('tb_op_order_reissue oor')
                ->join('left join tb_op_order_reissue_goods org on oor.id = org.reissue_id')
                ->where(['oor.platform_cd' => $platform_cd, 'org.sku_id' => $sku_id])
                ->where(['oor.order_id' => $order_id])
                ->where(['org.status_code' => ['neq', self::STATUS_CANCEL]])
                ->sum('yet_reissue_num');

            $map  = [
                'order_id'    => $order_id,
                'platform_cd' => $platform_cd,
                'sku_id'      => $sku_id,
                'status_code' => ['neq', self::STATUS_CANCEL]
            ];
            $other_reissue_num = $this->reissue_num_table->where($map)->sum('num');
            $yet_reissue_num   = $yet_reissue_num + $other_reissue_num;
        } else {
            //无拆单
            $return_info    = $this->model->table('tb_op_order_return oor')
                ->field('SUM(yet_return_num - refuse_warehouse_num) AS yet_return_num')
                ->join('left join tb_op_order_return_goods org on oor.id = org.return_id')
                ->where(['oor.platform_cd' => $platform_cd, 'org.sku_id' => $sku_id])
                ->where(['oor.order_id' => $order_id])
                ->where(['org.status_code' => ['neq', self::STATUS_CANCEL]])
                //排除 售后状态 取消退货、无效的售后单
                ->where(['oor.status_code' => ['not in', [self::STATUS_RETURN_CANCEL, self::STATUS_RETURN_WAIT_INVALID]]])
                ->select();
            $yet_return_num = $return_info[0]['yet_return_num'];

            $yet_reissue_num = $this->model->table('tb_op_order_reissue oor')
                ->join('left join tb_op_order_reissue_goods org on oor.id = org.reissue_id')
                ->where(['oor.platform_cd' => $platform_cd, 'org.sku_id' => $sku_id])
                ->where(['oor.order_id' => $order_id])
                ->where(['org.status_code' => ['neq', self::STATUS_CANCEL]])
                ->sum('yet_reissue_num');

            $map  = [
                'order_id'    => $order_id,
                'platform_cd' => $platform_cd,
                'sku_id'      => $sku_id,
                'status_code' => ['neq', self::STATUS_CANCEL]
            ];
            $other_reissue_num = $this->reissue_num_table->where($map)->sum('num');
            $yet_reissue_num   = $yet_reissue_num + $other_reissue_num;
        }

        $yet_return_num  = empty($yet_return_num) ? 0 : $yet_return_num;
        $yet_reissue_num = empty($yet_reissue_num) ? 0 : $yet_reissue_num;
        return [$yet_return_num, $yet_reissue_num];
    }

    //获取退货单详情(获取物流轨迹)
    public function getReturnDetail($after_sale_no = "", $order_id = "", $platform_cd = "")
    {
        $where = [];
        if ($after_sale_no) {
            $where['oor.after_sale_no'] = $after_sale_no;
        }
        if ($order_id) {
            $where['oor.order_id'] = $order_id;
            $where['oor.platform_cd'] = $platform_cd;
        }
        $where['ooasr.type'] = 1;
        list($db_res, $pages) = $this->repository->searchReturnList($where, [], false);
        $db_res = CodeModel::autoCodeTwoVal($db_res, [
            'logistics_fee_currency_code',
            'service_fee_currency_code',
            'warehouse_code',
            'status_code'
        ]);
        $db_res = SkuModel::productInfo($db_res, 'sku_id');
        foreach ($db_res as &$value) {
            if ($value['status_code'] == self::STATUS_REFUSED || !empty($after_sale_no)) {
                $value['refuse_warehouse_reason'] = $this->return_warehouse_table
                    ->where(['return_goods_id' => $value['return_goods_id']])->order('id desc')->getField('refuse_reason');
            }
        }
        return $db_res;
    }

    //获取补发单详情
    public function getReissueDetail($after_sale_no = "", $order_id = "", $platform_cd = "")
    {
        $where = [];
        if ($after_sale_no) {
            $where['oor.after_sale_no'] = $after_sale_no;
        }
        if ($order_id) {
            $where['oor.order_id'] = $order_id;
            $where['oor.platform_cd'] = $platform_cd;
        }
        $field  = "oor.*, oor.status_code AS total_status_code, org.id as reissue_goods_id,org.upc_id, org.sku_id,
         org.yet_reissue_num, org.status_code, org.over_reissue_num, org.order_goods_num";
        $db_res = $this->model->table('tb_op_order_reissue oor')
            ->field($field)
            ->join('left join tb_op_order_reissue_goods org on oor.id = org.reissue_id')
            ->where($where)
            ->select();
        if (empty($db_res)) {
            $db_res = [];
        }

        $result = CodeModel::autoCodeTwoVal($db_res, [
            'logistics_fee_currency_code',
            'service_fee_currency_code',
            'warehouse_code',
            'status_code'
        ]);
        $result = SkuModel::productInfo($result);
        foreach ($result as &$value) {
            $value['address'] = $value['country_id'] . ' ' . $value['province_id'] . ' ' . $value['city_id'] . ' ' . $value['address'];
        }
        return $result;
    }

    //售后列表
    public function getList($request_data, $is_excel = false)
    {
        $search_map = [
            'platform_cd'           => 'r1.platform_code',
            'platform_country_code' => 'r1.platform_country_code',
            'after_sale_no'         => 'r2.after_sale_no',
            'sku_id'                => 'r2.sku_id',
            'order_no'              => 'r2.order_no',
            'created_at'            => 'r2.created_at',
        ];
        list($where, $limit) = WhereModel::joinSearchStr($request_data, $search_map, '', ['all' => true]);
        $where_return_str = $where_reissue_str = $where_refund_str = $where;
        $audit_status_cd  = $request_data['search']['audit_status_cd'];
        $after_sale_type  = $request_data['search']['after_sale_type'];
        $status           = $request_data['search']['after_sale_status'];
        $shop             = $request_data['search']['selectedShops'];  // 店铺筛选

        // 售后类型改为多选
        if (!empty($after_sale_type)) {
            $type_data = array(1, 2, 3);
            $intersect = array_diff($type_data, $after_sale_type);  // 差集
            if (!empty($intersect)) {
                foreach ($intersect as $value) {
                    if ($value == 1) {
                        if ($where_return_str) {
                            $where_return_str .= " AND 1 != 1";
                        } else {
                            $where_return_str .= "  1 != 1";
                        }
                    }
                    if ($value == 2) {
                        if ($where_reissue_str) {
                            $where_reissue_str .= " AND 1 != 1";
                        } else {
                            $where_reissue_str .= "  1 != 1";
                        }
                    }
                    if ($value == 3) {
                        if ($where_refund_str) {
                            $where_refund_str .= " AND 1 != 1";
                        } else {
                            $where_refund_str .= "  1 != 1";
                        }
                    }
                }
            }
        }
        foreach ($status as $key => $value) {
            if (!empty($value)) {
                //根据售后状态组装不同售后类型查询条件
                $after_sale_status = $this->getStatusMap($value);
                $after_sale_status = "( '" . implode("','", array_values($after_sale_status)) . "' )";
                switch ($key) {
                    case self::REISSUE_TYPE:
                        if ($where_reissue_str) {
                            $where_reissue_str .= " AND r2.status_code IN {$after_sale_status}";
                        } else {
                            $where_reissue_str .= " r2.status_code IN {$after_sale_status}";
                        }
                        break;
                    case self::RETURN_TYPE:
                        if ($where_return_str) {
                            $where_return_str .= " AND r2.status_code IN {$after_sale_status}";
                        } else {
                            $where_return_str .= " r2.status_code IN {$after_sale_status}";
                        }
                        break;
                    case self::REFUND_TYPE:
                        if ($where_refund_str) {
                            $where_refund_str .= " AND r2.status_code IN {$after_sale_status}";
                        } else {
                            $where_refund_str .= " r2.status_code IN {$after_sale_status}";
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        //退货售后单不显示回邮单获取失败的单子
        if (!empty($where_return_str)) {
            $where_return_str = 'WHERE ' . $where_return_str . ' AND r1.type = 1 AND ((r2.reply_status_code != \'' . self::RETURN_ORDER_INVALID . '\' AND r2.reply_status_code != \'' . self::RETURN_ORDER_WAITING . '\' ) OR r2.reply_status_code is null ) ';
        } else {
            $where_return_str = 'WHERE r1.type = 1 AND (  (r2.reply_status_code != \'' . self::RETURN_ORDER_INVALID . '\' AND r2.reply_status_code != \'' . self::RETURN_ORDER_WAITING . '\' ) or r2.reply_status_code is null)';
        }
        if (!empty($where_reissue_str)) {
            $where_reissue_str = 'WHERE ' . $where_reissue_str . ' AND r1.type = 2';
        } else {
            $where_reissue_str = 'WHERE r1.type = 2';
        }
        if (!empty($where_refund_str)) {
            $where_refund_str = 'WHERE ' . $where_refund_str . ' AND r1.type = 3';
        } else {
            $where_refund_str = 'WHERE r1.type = 3';
        }


        if ($audit_status_cd == 1) {
            $where_refund_str .= '  AND r1.id = 0  ';
        } else {
            if ($audit_status_cd) {
                $where_return_str  .= ' AND 1 != 1';
                $where_reissue_str .= ' AND 1 != 1';
                $where_refund_str  .= " AND r2.audit_status_cd = '{$audit_status_cd}'";
            }
        }
        if (!empty($shop)) {
            $shop_str          = implode(',', $shop);
            $where_return_str  .= " AND oo.STORE_ID in  (  {$shop_str}  ) ";
            $where_reissue_str .= " AND oo.STORE_ID in  (  {$shop_str}  ) ";
            $where_refund_str  .= " AND oo.STORE_ID in  (  {$shop_str}  ) ";
        }
        list($res_db, $pages) = $this->repository->searchList($where_return_str, $where_reissue_str, $where_refund_str, $limit, $is_excel);
        $res_db     = SkuModel::productInfo($res_db, true, 'upc_str');//获取商品信息
        $res_db     = CodeModel::autoCodeTwoVal($res_db, ['status_code','amount_currency_cd']);
        $reason_map = TbMsCmnCdModel::getRefundReason();//退款理由映射
        $start      = ($request_data['pages']['current_page'] - 1) * $request_data['pages']['per_page'] + 1;
        $where_str  = '';
        if (empty($res_db)) {
            return [
                'data'  => $res_db,
                'pages' => $pages
            ];
        }
        foreach ($res_db as &$value) {
            $value['number'] = $start;
            $start++;
            $num_arr = explode(',', $value['num_str']);
            foreach ($value['product_info'] as $key => &$product) {
                $product['num']             = $num_arr[$key];
                $product['pay_currency']    = $value['pay_currency'];
                $product['pay_total_price'] = $value['pay_total_price'];
            }
            $value['is_show_all_sku'] = false;
            $value['reason']          = $reason_map[$value['reason']] ?: $value['reason'];
            return [
                'data'  => $res_db,
                'pages' => $pages
            ];
        }
    }
    /*************************各种展示信息：end*************************/

    //取消退货单
    /**
     * @param $request_data
     * @throws Exception
     */
    public function cancelReturn($request_data)
    {
        if ($request_data['after_sale_no']) {
            $return_info = $this->model->table('tb_op_order_after_sale_relevance rel')
                ->field('rel.platform_country_code, r.*')
                ->join('left join tb_op_order_return r on r.id = rel.after_sale_id and rel.type = 1')
                ->where(['r.after_sale_no' => $request_data['after_sale_no']])
                ->find();
            if ($return_info['status_code'] == self::STATUS_FINISHED) {
                throw new \Exception(L('退货已入库，禁止取消售后'));
            }
            $where['return_id'] = $return_info['id'];
            if (false === $this->return_table->where(['after_sale_no' => $request_data['after_sale_no']])->save(['status_code' => self::STATUS_CANCEL])) {
                throw new \Exception(L('取消退货单失败'));
            }
            $this->changeReissueAfterSaleType($return_info, self::TYPE_RETURN);
            $this->synOrderAfterSaleStatus($return_info['order_id'],$return_info['platform_country_code'],self::STATUS_CANCEL,self::RETURN_TYPE);
        } else {
            $where['id'] = $request_data['return_goods_id'];
            $goods_info  = $this->return_goods_table->where($where)->find();
            if ($goods_info['status_code'] == self::STATUS_FINISHED) {
                throw new \Exception(L('该条记录退货单已入库，禁止取消退货售后'));
            }
            //子退货单全部取消改变退货单总状态
            $count = $this->return_goods_table->where(['return_id' => $goods_info['return_id'], 'status_code' => ['neq', self::STATUS_CANCEL]])->count();
            if ($count <= 1) {
                if (false === $this->return_table->where(['id' => $goods_info['return_id']])->save(['status_code' => self::STATUS_CANCEL])) {
                    throw new \Exception(L('退货单总状态取消失败'));
                }
                $return_info = $this->return_table->where(['id' => $goods_info['return_id']])->find();
                $this->changeReissueAfterSaleType($return_info, self::TYPE_RETURN);

                $platform_cd = $this->relevance_table->where(['after_sale_id'=>$return_info['id'],'type'=>1])->getField('platform_country_code');
                $this->synOrderAfterSaleStatus($return_info['order_id'],$platform_cd,self::STATUS_CANCEL,self::RETURN_TYPE);
            }
        }
        if (false === $this->return_goods_table->where($where)->save(['status_code' => self::STATUS_CANCEL])) {
            throw new \Exception(L('取消失败'));
        }
    }

    //取消补发单
    public function cancelReissue($request_data)
    {
        if ($request_data['after_sale_no']) {
            //取消整个售后补发
            $map                 = ['after_sale_no' => $request_data['after_sale_no']];
            $reissue_info        = $this->reissue_table->where($map)->find();
            $order_id            = $reissue_info['reissue_order_id'];
            $platform_cd = $this->relevance_table->where(['after_sale_id'=>$reissue_info['id'],'type'=>2])->getField('platform_country_code');
            $where['reissue_id'] = $reissue_info['id'];

            //取消OMS订单
            $this->cancelOrder($order_id, $platform_cd);

            if (false === $this->reissue_table->where($map)->save(['status_code' => self::STATUS_CANCEL])) {
                throw new \Exception(L('取消补发单失败'));
            }
            if (false === $this->reissue_num_table->where($where)->save(['status_code' => self::STATUS_CANCEL])) {
                throw new \Exception(L('取消补发单失败'));
            }
            $this->changeReissueAfterSaleType($reissue_info, self::TYPE_REISSUE);

            $platform_cd = $this->relevance_table->where(['after_sale_id'=>$reissue_info['id'],'type'=>2])->getField('platform_country_code');
            $this->synOrderAfterSaleStatus($order_id,$platform_cd,self::STATUS_CANCEL,self::REISSUE_TYPE);
        } else {
            //取消子补发
            $where['id']  = $request_data['reissue_goods_id'];
            $goods_info   = $this->reissue_goods_table->where($where)->find();
            $reissue_info = $this->reissue_table->where(['id' => $goods_info['reissue_id']])->find();
            $order_id     = $reissue_info['reissue_order_id'];
            $platform_cd  = $this->relevance_table->where(['after_sale_id'=>$reissue_info['id'],'type'=>2])->getField('platform_country_code');

            //删除订单商品
            $this->deleteOrderGuds($order_id, $platform_cd, $goods_info['sku_id']);

            //子补发单全部取消改变补发单总状态
            $count = $this->reissue_goods_table->where(['reissue_id' => $goods_info['reissue_id'], 'status_code' => ['neq', self::STATUS_CANCEL]])->count();
            if ($count <= 1) {
                if (false === $this->reissue_table->where(['id' => $goods_info['reissue_id']])->save(['status_code' => self::STATUS_CANCEL])) {
                    throw new \Exception(L('补发单总状态取消失败'));
                }
                $this->changeReissueAfterSaleType($reissue_info, self::TYPE_REISSUE);

                $this->synOrderAfterSaleStatus($order_id,$platform_cd,self::STATUS_CANCEL,self::REISSUE_TYPE);
            }
            if (false === $this->reissue_num_table->where(['reissue_goods_id' => $request_data['reissue_goods_id']])->save(['status_code' => self::STATUS_CANCEL])) {
                throw new \Exception(L('取消补发单失败'));
            }
        }

        if (false === $this->reissue_goods_table->where($where)->save(['status_code' => self::STATUS_CANCEL])) {
            throw new \Exception(L('取消失败'));
        }
    }

    /**
     * 更改补发单的售后状态（补发/补货/NULL）
     * @param $data 售后补发/退货数据
     * @param $type 补发/退货类型
     * @throws Exception
     */
    public function changeReissueAfterSaleType($data, $type)
    {
        if ($type == self::TYPE_RETURN) {
            $where        = [
                'after_sale_no' => $data['after_sale_no'],
                'order_id'      => $data['order_id'],
                'order_no'      => $data['order_no'],
                'status_code'   => ['neq', self::STATUS_CANCEL]
            ];
            $reissue_info = $this->reissue_table->where($where)->find();
            if (!empty($reissue_info)) {
                $map = [
                    'ORDER_ID' => $reissue_info['reissue_order_id'],
                    'ORDER_NO' => $reissue_info['reissue_no'],
                ];
                if (false === $this->order_table->where($map)->save(['after_sale_type' => 1])) {
                    throw new \Exception(L('改变补发单售后类型失败'));
                }
            }
        } else if ($type == self::TYPE_REISSUE) {
            $map = [
                'ORDER_ID' => $data['reissue_order_id'],
                'ORDER_NO' => $data['reissue_no'],
            ];
            if (false === $this->order_table->where($map)->save(['after_sale_type' => NULL])) {
                throw new \Exception(L('改变补发单售后类型失败'));
            }
        }
    }

    /*************************退货单入库相关：start*************************/

    //退货待入库列表
    public function getWaitReturnList($request_data, $is_excel = false)
    {
        $search_map         = [
            'sku_id'             => 'org.sku_id',
            'upc_id'             => 'org.upc_id',
            'order_no'           => 'oor.order_no',
            'logistics_no'       => 'oor.logistics_no',
            'created_by'         => 'oor.created_by',
            'created_at'         => 'oor.created_at',
            'after_sale_no'      => 'oor.after_sale_no',
            'logistics_way_code' => 'oor.logistics_way_code',
            'warehouse_code'     => 'org.warehouse_code',
        ];
        $search_type['all'] = true;
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        $where['oor.is_end']             = 0;
        $where['org.over_warehouse_num'] = ['gt', 0];
        $where['org.status_code']        = ['IN', [
            'N002800001',
            'N002800002',
            'N002800003',
            'N002800005'
        ]];
        $where['ooasr.type']             = ['eq', 1];
        $order_by   = $request_data['search']['order_by'] ? $search_map[$request_data['search']['order_by']] : 'id';
        $order_type = $request_data['search']['order_type'] ? ' ' . $request_data['search']['order_type'] : ' desc';
        list($res_db, $pages) = $this->repository->searchReturnList($where, $limit, $is_excel, $order_by . $order_type);

        $order_data = array();
        if ($res_db){
            $order_temp  =  M('order','tb_op_')->field('ORDER_ID as order_id,PAY_CURRENCY AS pay_currency,PAY_TOTAL_PRICE AS pay_total_price')->where(['ORDER_ID'=>array('in',array_column($res_db,'order_id'))])->select();
            if ($order_temp){
                foreach ($order_temp as $key=>$value){
                    $tmep = array(
                        'pay_currency' => $value['pay_currency'],
                        'pay_total_price' => $value['pay_total_price'],
                    );
                    $order_data[$value['order_id']] = $tmep;
                }
            }
        }
        foreach ($res_db as &$item){
            $item['pay_currency'] = isset($order_data[$item['order_id']]['pay_currency']) ? $order_data[$item['order_id']]['pay_currency'] : "";
            $item['pay_total_price'] = isset($order_data[$item['order_id']]['pay_total_price']) ? $order_data[$item['order_id']]['pay_total_price'] : "";
        }



        if ($res_db) {
            $res_db = CodeModel::autoCodeTwoVal($res_db, ['warehouse_code']);
        }
        return [
            'data'  => $res_db,
            'pages' => $pages
        ];
    }

    //退货待入库详情页面信息
    public function getReturnWarehouseDetail($return_goods_id)
    {
        $where['org.id'] = $return_goods_id;
        $field = "oor.*, org.id as return_goods_id, org.upc_id, org.sku_id,
            org.order_goods_num, org.over_return_num, org.over_warehouse_num,
            org.yet_return_num, org.refuse_warehouse_num, org.warehouse_code, org.status_code";

        $db_res = $this->model->table('tb_op_order_return_goods org')
            ->field($field)
            ->join('left join tb_op_order_return oor on org.return_id = oor.id')
            ->where($where)
            ->find();
        if ($db_res) {
            $db_res = CodeModel::autoCodeOneVal($db_res, [
                'logistics_fee_currency_code',
                'service_fee_currency_code',
                'warehouse_code'
            ]);
            $db_res = SkuModel::productInfo($db_res);
        }
        return $db_res;
    }

    //退货待入库商品详情信息
    public function getReturnWarehouseGoods($return_goods_id)
    {
        $where['org.id'] = $return_goods_id;
        $field = "oor.*, org.id as return_goods_id, org.upc_id, org.sku_id, org.order_goods_num,
            orw.warehouse_num_broken, orw.warehouse_num,r.PAY_CURRENCY currency,r.PAY_TOTAL_PRICE pay_total_price,
             org.warehouse_code, org.status_code, store.STORE_NAME store_name";

        $db_res = $this->model->table('tb_op_order_return_goods org')
            ->field($field)
            ->join('left join tb_op_order_return oor on org.return_id = oor.id')
            ->join('left join tb_op_order_return_warehouse orw on orw.return_goods_id = org.id')
            ->join('left join tb_op_order r on r.order_id = oor.order_id')
            ->join('left join tb_ms_store store on r.store_id = store.ID')
            ->where($where)
            ->find();
        if ($db_res) {
            $db_res = CodeModel::autoCodeOneVal($db_res, [
                'logistics_fee_currency_code',
                'service_fee_currency_code',
                'warehouse_code'
            ]);
            $db_res = SkuModel::productInfo($db_res);
        }
        return $db_res;
    }

    //退货入库提交
    public function returnWarehouseSubmit($request_data)
    {
        $this->updateOverWarehouseNum($request_data);
        if (!$this->return_warehouse_table->create($request_data)) {
            throw new \Exception(L('创建退货入库数据失败'));
        }
        $this->return_warehouse_table->type       = 0;
        $this->return_warehouse_table->created_by = $this->return_warehouse_table->updated_by = $this->user_name;
        if (!$this->return_warehouse_table->add()) {
            throw new \Exception(L('退货入库失败'));
        }
        $where_return['id']       = $request_data['return_id'];
        $where_return_goods['id'] = $request_data['return_goods_id'];

        //调用java退货入库
        $return_info = $this->getReturnWarehouseDetail($request_data['return_goods_id']);
        $where_order = [
            'ORDER_ID' => $return_info['order_id'],
            'PLAT_CD'  => $return_info['platform_cd']
        ];
        $order_info  = $this->order_table->field('ORDER_ID,B5C_ORDER_NO')->where($where_order)->find();
        if (empty($return_info) || !$order_info['B5C_ORDER_NO']) {
            throw new \Exception(L('退货入库信息不全'));
        }
        $pur_info = $this->model->table('tb_wms_batch_order wbo')
            ->field('wb.sale_team_code, wb.small_sale_team_code, rel.creation_time, wb.batch_code,wbi.procurement_number')
            ->join('left join tb_wms_batch wb on wbo.batch_id = wb.id')
            ->join('left join tb_wms_bill wbi on wb.bill_id = wbi.id')
            ->join('left join tb_pur_order_detail pod on wbi.procurement_number = pod.procurement_number')
            ->join('left join tb_pur_relevance_order rel on pod.order_id = rel.order_id')
            ->where(['wbo.ORD_ID' => $order_info['B5C_ORDER_NO'], 'wbo.SKU_ID' => $return_info['sku_id']])
            ->where(['wb.SKU_ID' => $return_info['sku_id']])
            ->order('wb.batch_code asc')
            ->find();
        $broken_num = empty($request_data['warehouse_num_broken']) ? 0 : $request_data['warehouse_num_broken'];

        $yet_return_num = $this->return_goods_table->where(['return_id' => $return_info['id']])->sum('yet_return_num');
        if (!$yet_return_num) {
            throw new \Exception(L('售后数量异常'));
        }
        $service_fee = $logistics_fee = 0;
        if ($return_info['service_fee'] > 0) {
            $service_fee = $return_info['service_fee'] / $yet_return_num;
        }
        if ($return_info['logistics_fee'] > 0) {
            $logistics_fee = $return_info['logistics_fee'] / $yet_return_num;
        }
        $data = [
            [
                "bill" => [
                    'orderId'           => $order_info['B5C_ORDER_NO'],
                    'billType'          => 'N000941000',
                    'relationType'      => 'N002350300',
                    'warehouseId'       => $return_info['warehouse_code'],
                    'saleTeam'          => $pur_info['sale_team_code'],
                    // 'saleTeam'       => 'N001281500',
                    'virType'           => 'N002440100',
                    'processOnWay'      => '0',
                    'operatorId'        => DataModel::userId() ? : 9186,
                    'saleNo'            => $return_info['order_no'] . '；' . $return_info['after_sale_no'],
                    'procurementNumber' => $pur_info['procurement_number']
                ],
                'guds' => [
                    [
                        'skuId'                  => $return_info['sku_id'],
                        'num'                    => $request_data['warehouse_num'],
                        'shouldNum'              => $return_info['over_return_num'],
                        'brokenNum'              => $broken_num,
                        'currencyTime'           => $pur_info['creation_time'],
                        'logServiceCostCurrency' => $return_info['service_fee_currency_code'],
                        'logServiceCost'         => $service_fee,
                        'logCurrency'            => $return_info['logistics_fee_currency_code'],
                        'carryCost'              => $logistics_fee,
                        'smallSaleTeamCode'      => $pur_info['small_sale_team_code'],
                    ]
                ]
            ]
        ];
        Logs($data, __FUNCTION__ . '入库数据', __CLASS__);
        $res = json_decode(ApiModel::warehouse($data), true);
        if ($res['code'] != '2000') {
            Logs($res, __FUNCTION__ . '入库api返回失败', __CLASS__);
            throw new \Exception(L($res['msg']));
        }
        $over_warehouse_num = $this->return_goods_table->where($where_return_goods)->sum('over_warehouse_num');
        if ($over_warehouse_num <= 0) {
            //单个商品全部入库，则已完成
            $this->changeReturnGoodsStatus($where_return_goods, self::STATUS_FINISHED, false);
        }
    }

    /**
     * 全部入库或单个sku全部入库，状态完成
     * @param $return_id
     * @param $return_goods_id
     * @param bool $is_end
     */
    public function updateWarehouseStatus($return_id, $return_goods_id, $is_end = false)
    {
        $goods_info = $this->return_goods_table->find($return_goods_id);
        if ($goods_info['over_warehouse_num'] <= 0) {
            $where_return_goods['id'] = $return_goods_id;
            $this->changeReturnGoodsStatus($where_return_goods, self::STATUS_FINISHED);
        }
        if ($is_end) {
            $where_return['id'] = $return_id;
            $this->changeReturnStatus($where_return, self::STATUS_FINISHED);
        }
    }

    //拒绝入库提交
    public function refuseReturnWarehouseSubmit($request_data)
    {
        $this->updateOverWarehouseNum($request_data, true);
        if (!$this->return_warehouse_table->create($request_data)) {
            throw new \Exception(L('创建拒绝退货入库数据失败'));
        }
        $this->return_warehouse_table->type       = 1;
        $this->return_warehouse_table->created_by = $this->return_warehouse_table->updated_by = $this->user_name;
        if (!$id = $this->return_warehouse_table->add()) {
            throw new \Exception(L('拒绝退货入库失败'));
        }
    }

    /**
     * 全部拒绝入库则更改状态为已拒绝
     * @param type $return_goods_id
     */
    public function updateRefusedStatus($return_id, $return_goods_id, $is_end = false)
    {
        $goods_info = $this->return_goods_table->find($return_goods_id);
        if ($goods_info['over_warehouse_num'] <= 0) {
            $where_return_goods['id'] = $return_goods_id;
            $this->changeReturnGoodsStatus($where_return_goods, self::STATUS_REFUSED_RETURN);
            $this->changeReturnGoodsStatus($where_return_goods, self::STATUS_REFUSED);
        }
        $yet_warehouse_num = $this->return_goods_table->where(['return_id' => $return_id])->sum('yet_warehouse_num');
        if ($is_end && $yet_warehouse_num <= 0) {
            //全部拒绝
            $where_return['id'] = $return_id;
            $this->changeReturnStatus($where_return, self::STATUS_REFUSED_RETURN);
            $this->changeReturnStatus($where_return, self::STATUS_REFUSED);
        } else if ($is_end && $yet_warehouse_num > 0) {
            //部分拒绝，部分入库，则已完成
            $where_return['id'] = $return_id;
            $this->changeReturnStatus($where_return, self::STATUS_FINISHED);
        }
    }

    /**
     * 计算剩余可入库件数
     * @param $data
     * @param bool $is_refuse
     * @throws Exception
     */
    private function updateOverWarehouseNum($data, $is_refuse = false)
    {
        $yet_warehouse_num = 0;
        if (!$is_refuse) {
            $yet_warehouse_num = $data['warehouse_num'] + $data['warehouse_num_broken'];
        } else {
            $yet_warehouse_num = $data['warehouse_num_refuse'];
        }

        $return_goods_id    = $data['return_goods_id'];
        $where['id']        = $return_goods_id;
        $over_warehouse_num = $this->return_goods_table->where($where)->getField('over_warehouse_num');//剩余可入库数

        //拒绝入库数不算入库数
        $warehouse_info = $this->return_warehouse_table
            ->field('(SUM(IFNULL(warehouse_num,0) + IFNULL(warehouse_num_broken,0))) AS all_warehouse_num')
            ->where(['return_goods_id' => $return_goods_id])
            ->select();
        $all_num        = $yet_warehouse_num + $warehouse_info['all_warehouse_num'];//当前入库数+已经入库数
        if ($all_num > $over_warehouse_num) {
            throw new \Exception(L('入库数量大于可入库数量'));
        }
        $num = $over_warehouse_num - $yet_warehouse_num;

        if (!$this->return_goods_table->where($where)->save(['over_warehouse_num' => $num])) {
            throw new \Exception(L('更新剩余入库数失败'));
        }
        if (!$is_refuse) {
            if (!$this->return_goods_table->where($where)->setInc('yet_warehouse_num', $yet_warehouse_num)) {
                throw new \Exception(L('更新已入库数失败'));
            }
        } else {
            if (!$this->return_goods_table->where($where)->setInc('refuse_warehouse_num', $yet_warehouse_num)) {
                throw new \Exception(L('更新拒绝入库数失败'));
            }
        }

    }

    //退货单全部拒绝入库或者全部入库，标记退货单为完结
    public function tagEnd($return_id)
    {
        $over_warehouse_num = $this->return_goods_table->where(['return_id' => $return_id])->sum('over_warehouse_num');
        if ($over_warehouse_num <= 0) {
            //全部入库标记售后为完结
            if (!$this->return_table->where(['id' => $return_id])->save(['is_end' => 1])) {
                throw new \Exception(L('标记退货完结失败'));
            }
            return true;
        }
        return false;
    }

    //已退货入库列表
    public function getReturnWarehouseList($request_data, $is_excel = false)
    {
        $search_map         = [
            'sku_id'             => 'org.sku_id',
            'upc_id'             => 'org.upc_id',
            'order_no'           => 'orr.order_no',
            'logistics_no'       => 'orr.logistics_no',
            'created_by'         => 'orr.created_by',
            'created_at'         => 'orr.created_at',
            'after_sale_no'      => 'orr.after_sale_no',
            'logistics_way_code' => 'orr.logistics_way_code',
        ];
        $search_type['all'] = true;
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        list($db_res, $pages) = $this->repository->searchReturnWarehouseList($where, $limit, $is_excel);

        $db_res = CodeModel::autoCodeTwoVal($db_res, [
            'logistics_fee_currency_code',
            'service_fee_currency_code',
            'warehouse_code'
        ]);
        $db_res = SkuModel::productInfo($db_res);
        return [
            'data'  => $db_res,
            'pages' => $pages
        ];
    }
    /*************************退货单入库相关：end*************************/

    /*************************公共方法：start*************************/
    /**
     * 搜索商品
     * @param $request_data
     * @return mixed
     * @throws Exception
     */
    public function searchProducts($request_data)
    {
        $data = $request_data['search'];
        if (empty($data)) {
            return null;
        }
        if ($data['type'] == self::TYPE_RETURN) {
            $sku_ids = $this->searchOrderSkuIds($data['order_id'], $data['platform_cd']);
            if (empty($sku_ids)) {
                throw new \Exception(L('订单未找到该商品'));
            }
        }
        if (!is_numeric($data['sku_id'])){
            return  $res_return['data']  = [];
        }
        $condition['product_sku.sku_id'] = $data['sku_id'];
        $res_db                          = SkuModel::getProductInfo($condition);
        $res_return['data']              = $res_db;
        return $res_return;
    }

    /*************************公共方法：end*************************/

    /*****************************订单相关：start*************************/

    /**
     * 根据商品sku获取订单商品数量
     * @param $order_id
     * @param $platform_cd
     * @param $sku_id
     * @param string $type
     * @return int
     */
    public function getOrderGoodsNum($order_id, $platform_cd, $sku_id, $type = "")
    {
        if (is_array($sku_id)) {
            $num = $this->model->table('tb_op_order op_order')
                ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID')
                ->where(['op_order.PLAT_CD' => $platform_cd, 'guds.B5C_SKU_ID' => ['in', $sku_id]])
                ->where(['op_order.ORDER_ID' => $order_id])
                ->sum('ITEM_COUNT');
        } else {
            $num = $this->model->table('tb_op_order op_order')
                ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID')
                ->where(['op_order.PLAT_CD' => $platform_cd, 'guds.B5C_SKU_ID' => $sku_id])
                ->where(['op_order.ORDER_ID' => $order_id])
                ->sum('ITEM_COUNT');
            if ($type == self::TYPE_REISSUE) {
                if ($num <= 0) {
                    $num = $this->model->table('tb_op_order op_order')
                        ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID')
                        ->where(['op_order.PLAT_CD' => $platform_cd, 'op_order.ORDER_ID' => $order_id])
                        ->sum('ITEM_COUNT');
                }
            }
        }
        return empty($num) ? 0 : $num;
    }


    //根据订单号获取订单中所有商品sku id
    public function searchOrderSkuIds($order_id, $platform_cd)
    {
        return array_column($this->getOrderInfo($order_id, $platform_cd), 'sku_id');
    }

    private function cancelOrder($order_id, $platform_cd)
    {
        $where      = ['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd];
        $order_info = $this->order_table->field('CHILD_ORDER_ID, SEND_ORD_STATUS')->where($where)->find();
        if (!empty($order_info['CHILD_ORDER_ID'])) {
            throw new \Exception(L('订单已经拆单，无法取消'));
        }
        if ($order_info['SEND_ORD_STATUS'] != 'N001821000') {
            throw new \Exception(L('订单已经派单，无法取消'));
        }
        if (M('ms_ord_package', 'tb_')->where(['ORD_ID' => $order_id, 'plat_cd' => $platform_cd])->getField('TRACKING_NUMBER')) {
            throw new \Exception(L('订单运单号不为空，禁止取消'));
        }
        if (false === $this->order_table->where($where)->save(['SEND_ORD_STATUS' => 'N001821100'])) {
            throw new \Exception(L('取消OMS生成的订单失败'));
        }

        $msg = '补发订单取消成功';
        OrderPresentModel::get_log_data($order_id, $msg, $platform_cd);
    }

    private function deleteOrderGuds($order_id, $platform_cd, $sku_id)
    {
        $where      = ['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd];
        $order_info = $this->order_table->field('CHILD_ORDER_ID, SEND_ORD_STATUS')->where($where)->find();
        if (!empty($order_info['CHILD_ORDER_ID'])) {
            throw new \Exception(L('订单已经拆单，无法取消'));
        }
        if ($order_info['SEND_ORD_STATUS'] != 'N001821000') {
            throw new \Exception(L('订单已经派单，无法取消'));
        }

        if (M('ms_ord_package', 'tb_')->where(['ORD_ID' => $order_id, 'plat_cd' => $platform_cd])->getField('TRACKING_NUMBER')) {
            throw new \Exception(L('订单运单号不为空，禁止取消'));
        }

        $count = $this->order_guds_table->where(['ORDER_ID' => $order_id])->count();
        if ($count <= 1) {
            //剩下最后一个商品就不删除了
            if (false === $this->order_table->where($where)->save(['SEND_ORD_STATUS' => 'N001821100'])) {
                throw new \Exception(L('取消OMS生成的订单失败'));
            }

            $msg = '补发订单取消成功';
            OrderPresentModel::get_log_data($order_id, $msg, $platform_cd);

            return true;
        }
        $condition  = [
            'ORDER_ID'   => $order_id,
            'B5C_SKU_ID' => $sku_id,
        ];
        $order_guds = $this->order_guds_table->where($condition)->find();

        Logs($order_guds, __FUNCTION__, __CLASS__);
        if (!$this->order_guds_table->where($condition)->delete()) {
            throw new \Exception(L('删除订单商品失败'));
        }

        $msg = '补发订单商品删除，sku:' . $sku_id;
        OrderPresentModel::get_log_data($order_id, $msg, $platform_cd);
    }

    public function getOrderInfo($order_id, $platform_cd)
    {
        $field  = 'op_order.ORDER_NO as order_no,op_order.USER_EMAIL as email,ord.WHOLE_STATUS_CD as whole_status_cd,
            op_order.SEND_ORD_STATUS as send_ord_status,op_order.ADDRESS_USER_NAME as receiver_name,op_order.ADDRESS_USER_PHONE as receiver_phone,
            op_order.ADDRESS_USER_COUNTRY as country,op_order.ADDRESS_USER_COUNTRY_CODE as country_code,op_order.ADDRESS_USER_CITY as city,
            op_order.ADDRESS_USER_PROVINCES as province,op_order.ADDRESS_USER_ADDRESS1 as address,op_order.ADDRESS_USER_ADDRESS2 as address2,
            op_order.ADDRESS_USER_POST_CODE as postal_code,op_order.RECEIVER_TEL as receiver_tel,op_order.WAREHOUSE as warehouse,guds.guds_type,
            guds.B5C_SKU_ID as sku_id,SUM(guds.item_count) AS order_goods_num,e.doorplate';
        $db_res = $this->model->table('tb_op_order op_order')
            ->field($field)
            ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID AND op_order.PLAT_CD = guds.PLAT_CD')
            ->join('left join tb_ms_ord ord on op_order.ORDER_ID = ord.THIRD_ORDER_ID  AND op_order.PLAT_CD = ord.PLAT_FORM')
            ->join('left join tb_op_order_extend e on op_order.ORDER_ID = e.order_id and op_order.PLAT_CD = e.plat_cd')
            ->where(['op_order.ORDER_ID' => $order_id, 'op_order.PLAT_CD' => $platform_cd])
            ->order('guds.guds_type asc')
            ->group('guds.B5C_SKU_ID')
            ->select();
        return $db_res;
    }

    public function getOrderWarehouse($order_id, $platform_cd)
    {
        return $this->order_table->where(['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd])->getField('WAREHOUSE');
    }

    /*****************************订单相关：end*************************/

    //补发单拆单
    public function splitReissue($data)
    {
        $order_id      = $data[0]['order_id'];
        $platform_cd   = $data[0]['plat_cd'];
        $order_no      = $this->order_table->where(['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd])->getField('ORDER_NO');
        $reissue_info  = $this->reissue_table->where(['reissue_no' => $order_no, 'platform_cd' => $platform_cd])->find();
        if (!$reissue_info) {
            return true;
        }

        $this->model->startTrans();
        $collection = implode(';', array_column($data, 'child_order_id'));
        if (!$this->reissue_table->where(['id' => $reissue_info['id']])->save(['child_order_no' => $collection])) {
            $this->model->rollback();
            return false;
        }
        $this->model->commit();
        return true;
    }

    //补发单取消拆单
    public function cancelSplitReissue($order_id, $platform_cd)
    {
        $reissue_info = $this->reissue_table->where(['reissue_order_id' => $order_id, 'platform_cd' => $platform_cd])->find();
        if ($reissue_info) {
            if (false === $this->reissue_table->where(['reissue_order_id' => $order_id, 'platform_cd' => $platform_cd])->save(['child_order_no' => NULL])) {
                return false;
            }
        }
        return true;
    }

    //OMS删除/取消订单，改变售后补发单状态为已取消
    public function cancelReissueByOrder($order_ids)
    {
        $reissue_info = $this->model->table('tb_op_order_after_sale_relevance rel')
            ->field('rel.platform_country_code,r.*')
            ->join('left join tb_op_order_reissue r on r.id = rel.after_sale_id and rel.type = 2')
            ->where(['r.reissue_order_id' => ['in', $order_ids]])
            ->select();
        if (empty($reissue_info)) {
            return;
        }
        $reissue_ids = array_column($reissue_info, 'id');
        $this->reissue_table->where(['reissue_order_id' => ['in', $order_ids]])->save(['status_code' => self::STATUS_CANCEL]);
        $this->reissue_goods_table->where(['reissue_id' => ['in', $reissue_ids]])->save(['status_code' => self::STATUS_CANCEL]);
        $this->reissue_num_table->where(['reissue_id' => ['in', $reissue_ids]])->save(['status_code' => self::STATUS_CANCEL]);
        foreach ($reissue_info as $item) {
            $this->synOrderAfterSaleStatus($item['order_id'],$item['platform_country_code'],self::STATUS_CANCEL,self::REISSUE_TYPE,false);
        }
    }

    /*****************************退款*****************************/

    /**
     * 退款申请/审核页数据
     * @param $request_data
     * @return mixed
     */
    public function getRefundApplyDetail($request_data)
    {
        $order_id      = $request_data['order_id'];
        $platform_cd   = $request_data['platform_cd'];
        $after_sale_no = $request_data['after_sale_no'];
        $order_info  = $this->getRefundOrderInfo($order_id, $platform_cd);
        $order_info  = SkuModel::productInfo($order_info);
        foreach ($order_info as &$value) {
            list($yet_return_num, $yet_reissue_num) = $this->countYetApplyNum( $order_id, $platform_cd, $value['sku_id']);
            $value['over_return_num'] = $value['order_goods_num'] - $yet_return_num;
            $value['yet_return_num']  = $yet_return_num;
        }
        $detail_info['order_info'] = $order_info;
        $detail_info['child_order_info'] = [];
        $detail_info['has_child_order'] = -1;
        if (isset($order_info[0]['CHILD_ORDER_ID']) && $order_info[0]['CHILD_ORDER_ID']) {
            $detail_info['child_order_info'] = $this->getRefundOrderInfo($order_id, $platform_cd, 2);
            $detail_info['child_order_info'] = $order_info  = SkuModel::productInfo($detail_info['child_order_info']);
            $detail_info['has_child_order'] = 1;
        }
        $refund_info = $this->getHistoryRefundInfo($order_id, $platform_cd, $after_sale_no);
        $detail_info['refund_info'] =$refund_info;
        if (!$after_sale_no && !in_array(0, array_column($refund_info, 'type'))) {
            $detail_info['refund_info'][] = [
                'type'                   => 0,
                'order_pay_date'         => $order_info[0]['order_pay_date'],
                'current_date'           => $order_info[0]['current_date'],
                'pay_method'             => $order_info[0]['pay_method'],
                'refund_user_name'       => $order_info[0]['company_code_val'],
                'refund_account'         => $order_info[0]['refund_account'],
                'refund_amount'          => $order_info[0]['refund_amount'],
                'amount_currency_cd_val' => $order_info[0]['amount_currency_cd'],
                'created_by'             => DataModel::userNamePinyin(),
                'is_history'             => false
            ];
            $detail_info['audit_status_cd'] = '';
        } else {
            $detail_info['audit_status_cd'] = $refund_info[0]['audit_status_cd'];
        }
        $detail_info['attachment'] = $refund_info[0]['attachment'];
        $detail_info['apply_opinion'] = '';
        $detail_info['audit_opinion'] = '';
        $detail_info['pay_method'] = $order_info[0]['pay_method'];
        $detail_info['pay_channel'] = $order_info[0]['default_pay_channel'];
        $detail_info['refund_child_order'] = $refund_info[0]['refund_child_order'];
//        $detail_info['apply_opinion'] = $refund_info[0]['apply_opinion'];
//        $detail_info['audit_opinion'] = $refund_info[0]['audit_opinion'];
        if ($after_sale_no) {
            $detail_info['after_sale_id'] = $refund_info[0]['after_sale_id'];
            $detail_info['payment_audit_id'] = $refund_info[0]['payment_audit_id'];
        }

        return $detail_info;
    }

    /**
     * 退款详情页
     * @param $request_data
     * @return mixed
     */
    public function getRefundDetail($request_data)
    {
        $order_id      = $request_data['order_id'];
        $platform_cd   = $request_data['platform_cd'];
        $after_sale_no = $request_data['after_sale_no'];
        $order_info  = $this->getRefundOrderInfo($order_id, $platform_cd);
        $order_info  = SkuModel::productInfo($order_info);
        foreach ($order_info as &$value) {
            list($yet_return_num, $yet_reissue_num) = $this->countYetApplyNum($order_id, $platform_cd, $value['sku_id']);
            $value['over_return_num'] = $value['order_goods_num'] - $yet_return_num;
            $value['yet_return_num']  = $yet_return_num;
        }
        $detail_info['order_info'] = $order_info;
        $refund_info = $this->getHistoryRefundInfo($order_id, $platform_cd, $after_sale_no);
        $detail_info['refund_info'] = $refund_info[0];
        $detail_info['refund_info']['remark_msg'] = $order_info[0]['remark_msg'];

        $detail_info['log_info'] = (new TbOpOrderRefundLogModel())->where(['refund_id'=>$refund_info[0]['after_sale_id']])->select();

        return $detail_info;
    }

    private function getHistoryRefundInfo($order_id, $platform_cd, $after_sale_no)
    {
        $query = $this->model->table('tb_op_order_refund r')
            ->field('r.id as after_sale_id,rd.*,rd.id as refund_detail_id, true as is_history,r.audit_status_cd,r.*,store.STORE_NAME as store_name')
            ->join('left join tb_op_order_refund_detail rd on r.id = rd.refund_id')
            ->join('left join tb_ms_store store on r.store_id = store.ID')
            ->where(['r.order_id' => $order_id, 'r.platform_cd' => $platform_cd])
            ->where(['r.audit_status_cd'=>['neq', self::AUDIT_STATUS_TRASH]]);
        if ($after_sale_no) {
            $query->where(['r.after_sale_no' => $after_sale_no]);
        }
        $db_res = $query->select();
        $db_res = CodeModel::autoCodeTwoVal($db_res, ['refund_channel_cd', 'amount_currency_cd', 'sales_team_cd', 'refund_reason_cd']);
        $reason_map = TbMsCmnCdModel::getRefundReason();
        foreach ($db_res as &$item) {
            $item['refund_reason_cd_val'] = $reason_map[$item['refund_reason_cd']] ? : $item['refund_reason_cd'];
        }
        return $db_res;
    }

    //比较退款金额和订单总金额大小
    private function compareRefundAndOrderAmount($request_data)
    {
        $order_id    = $request_data['order_id'];
        $platform_cd = $request_data['platform_cd'];
        foreach ($request_data['refund_info'] as $item) {
            $query = $this->model->table('tb_op_order_refund r')
                ->field('sum(rd.refund_amount) as total_refund_amount, op.PAY_TOTAL_PRICE as pay_total_price')
                ->join('inner join tb_op_order_refund_detail rd on r.id = rd.refund_id')
                ->join('inner join tb_op_order op on op.ORDER_ID = r.order_id and op.PLAT_CD = r.platform_cd')
                ->where(['r.order_id' => $order_id, 'r.platform_cd' => $platform_cd])
                ->where(['r.audit_status_cd' => ['not in', [self::AUDIT_STATUS_TRASH, self::AUDIT_STATUS_CANCEL, self::AUDIT_STATUS_NO_PASS]]]);
            if ($item['refund_reason_cd'] == self::REFUND_TYPE_SHIPPING) {
                //单独退运费
                $query->where(['rd.refund_reason_cd' => self::REFUND_TYPE_SHIPPING]);
            } else {
                $query->where(['rd.refund_reason_cd' => ['neq', self::REFUND_TYPE_SHIPPING]]);
            }
            $db_res = $query->select();
            if (empty($db_res)) {
                throw new \Exception(L('售后或订单信息异常'));
            }
            if ($db_res[0]['total_refund_amount'] + $item['refund_amount'] > $db_res[0]['pay_total_price']) {
                throw new \Exception(L('该订单所有退款金额不能大于订单总金额'));
            }
        }
    }

    public function getRefundOrderInfoOld($order_id, $platform_cd)
    {
        $field  = 'op_order.ORDER_NO as order_no,guds.B5C_SKU_ID as sku_id,
            SUM(guds.ITEM_COUNT) AS order_goods_num,op_order.PAY_TRANSACTION_ID AS trade_no,
            op_order.ORDER_PAY_TIME as order_pay_date,op_order.PAY_CURRENCY as amount_currency_cd,
            op_order.PAY_TOTAL_PRICE as refund_amount,CURRENT_TIMESTAMP() as "current_date",bank.company_code,bank.account_bank';
        $db_res = $this->model->table('tb_op_order op_order')
            ->field($field)
            ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID and op_order.PLAT_CD = guds.PLAT_CD')
            ->join('left join tb_ms_store store on op_order.STORE_ID = store.ID')
            ->join('left join tb_fin_account_bank bank on store.fin_account_bank_id = bank.id')
            ->where(['op_order.ORDER_ID' => $order_id, 'op_order.PLAT_CD' => $platform_cd])
            ->order('guds.guds_type asc')
            ->group('guds.B5C_SKU_ID')
            ->select();
        $db_res = CodeModel::autoCodeTwoVal($db_res, ['company_code']);
        return $db_res;
    }

    public function getRefundOrderInfo($order_id, $platform_cd, $type = 1)
    {
        $column = $type == 2 ? 'op_order.PARENT_ORDER_ID' : 'op_order.ORDER_ID';
        $group = $type == 2 ? 'guds.B5C_SKU_ID, op_order.ORDER_ID' : 'guds.B5C_SKU_ID';
        $field  = 'op_order.ORDER_NO as order_no,op_order.ORDER_ID,op_order.CHILD_ORDER_ID,guds.B5C_SKU_ID as sku_id,
            SUM(guds.ITEM_COUNT) AS order_goods_num,SUM(guds.ITEM_PRICE) as item_price,op_order.PAY_TRANSACTION_ID AS trade_no,
            op_order.ORDER_PAY_TIME as order_pay_date,op_order.PAY_CURRENCY as amount_currency_cd,op_order.PAY_METHOD as pay_method,
            op_order.PAY_TOTAL_PRICE as refund_amount,CURRENT_TIMESTAMP() as "current_date",bank.company_code,bank.account_bank,tb_ms_cmn_cd.ETC2 as default_pay_channel,op_order.REMARK_MSG as remark_msg,store.STORE_NAME as store_name';
        $db_res = $this->model->table('tb_op_order op_order')
            ->field($field)
            ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID and op_order.PLAT_CD = guds.PLAT_CD')
            ->join('left join tb_ms_store store on op_order.STORE_ID = store.ID')
            ->join('left join tb_fin_account_bank bank on store.fin_account_bank_id = bank.id')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD_VAL = op_order.pay_method and tb_ms_cmn_cd.USE_YN = "Y" and tb_ms_cmn_cd.CD like "N00336%" and ETC2 <> ""')
            ->where([$column => $order_id, 'op_order.PLAT_CD' => $platform_cd])
            ->order('guds.guds_type asc')
            ->group($group)
            ->select();
        $db_res = CodeModel::autoCodeTwoVal($db_res, ['company_code']);
        return $db_res;
    }
    public function getRefundOrderStatus($order_id, $platform_cd)
    {
        return $this->model->table('tb_op_order')->where(['ORDER_ID' => $order_id, 'PLAT_CD' => $platform_cd])->find();
    }

    /**
     * 退款提交
     * @param $request_data
     * @param $source_type 售后来源类型
     * @return string
     * @throws Exception
     */
    public function refundApplySubmit($request_data, $source_type = 0)
    {
        $order_no        = $request_data['order_no'];
        $order_id        = $request_data['order_id'];
        $platform_cd     = $request_data['platform_cd'];
        $audit_status_cd = $request_data['audit_status_cd'];
        $request_no      = $request_data['after_sale_no'];
        $user_name       = $request_data['refund_info'][0]['created_by'];
        # 数据来源判断
        $data_source     =isset($request_data['source']) ? $request_data['source'] : '';
        if($data_source && $data_source == 'amazon')
        {
            $status_code = self::STATUS_REFUND_SUCCESS;
        }
        else
        {
            $status_code = $this->getAfterSaleStatus($audit_status_cd);
        }
        if ($request_no) {
            $attachment = json_encode($request_data['attachment']);
            $res = $this->order_refund_table->where(['after_sale_no'=>$request_no])->save([
                'audit_status_cd' => 'N003170003',
                'status_code' => $status_code,
                'attachment'  => $attachment
            ]);
            if (false === $res) {
                throw new \Exception(L('提交申请失败'));
            }
            $res = $this->refundApplyEdit($request_data);
            if (false === $res) {
                throw new \Exception(L('编辑退款详情失败'));
            }
            $this->synOrderAfterSaleStatus($order_id,$platform_cd,$status_code,self::REFUND_TYPE);
            return $request_no;
        }
        $request_data['attachment'] = json_encode($request_data['attachment']);
//        $this->deleteRefundInfo($request_no);
        //过滤已经取消的退款单
        foreach ($request_data['refund_info'] as $k => $v) {
            if (isset($v['id'])) {
                unset($request_data['refund_info'][$k]);
            }
        }
        $request_data['refund_info'] = array_values($request_data['refund_info']);
        $this->checkRefundInfo($request_data);
        $this->compareRefundAndOrderAmount($request_data);//判断退款金额不能大于订单总金额
        $order_info = $this->repository->getOrderInfo($order_id, $platform_cd);
        if (($order_info[0]['CHILD_ORDER_ID'] || $order_info[0]['PARENT_ORDER_ID']) && in_array($order_info[0]['PLAT_CD'], CodeModel::getGpPlatCds())) {
            throw new \Exception(L('已拆单订单不能申请退款'));
        }
        if (!$this->checkOrderStatus($order_info[0]['SEND_ORD_STATUS'], $order_info[0]['BWC_ORDER_STATUS'])) {
            throw new \Exception(L('订单状态不满足退款要求'));
        }
        $plat_country_cd = $order_info[0]['PLAT_CD'];
        $plat_name       = explode('-', $order_info[0]['PLAT_NAME']);
        $plat_cd = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00262%"]])->getField('CD');
        if (!$plat_cd) {
            $plat_cd = M('ms_cmn_cd', 'tb_')->where(['CD_VAL' => $plat_name[0], 'CD' => ['like', "N00083%"]])->getField('CD');//第三方平台
        }
        $count = count($request_data['refund_info']);
        for($i = 0; $i<$count; $i++) {
            if ($audit_status_cd == self::AUDIT_STATUS_DRAFT && ($count == 1 || $request_data['refund_info'][$i]['type'] == 0)) {
                $after_sale_no = $request_no ? : date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale');
            } else {
                $after_sale_no = date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale');
            }
            if (!$this->order_refund_table->create($request_data)) {
                throw new \Exception(L('退款数据创建失败'));
            }
            
            $hash = strtolower(hash('sha256', $order_info[0]['PLAT_CD'] . $order_info[0]['ADDRESS_USER_NAME']));
           
            $this->order_refund_table->status_code   = $status_code;
            $this->order_refund_table->created_by    = $this->user_name ? : $user_name;
            $this->order_refund_table->updated_by    = $this->user_name ? : $user_name;
            $this->order_refund_table->after_sale_no = $after_sale_no;
            $this->order_refund_table->store_id      = $order_info[0]['STORE_ID'];
            $this->order_refund_table->sku_ids       = implode(',', array_column($order_info, 'B5C_SKU_ID'));
            $this->order_refund_table->goods_nums    = implode(',', array_column($order_info, 'ITEM_COUNT'));
            $this->order_refund_table->source_type   = $source_type;
            $this->order_refund_table->user_identifier_hash   = $hash;
           
            if (!$refund_id = $this->order_refund_table->add()) {
                throw new \Exception(L('退款创建失败'));
            }
            if (0 === strpos($request_data['refund_info'][$i]['amount_currency_cd'], 'N00')) {
                $amount_currency_cd = $request_data['refund_info'][$i]['amount_currency_cd'];
            } else {
                $amount_currency_cd = $request_data['refund_info'][$i]['amount_currency_cd'];
                $amount_currency_cd = TbMsCmnCdModel::getCurrencyCdByVal($amount_currency_cd);
                if (!$amount_currency_cd) {
                    throw new \Exception(L('未找到订单支付币种'));
                }
            }
            $request_data['refund_info'][$i]['amount_currency_cd'] = $amount_currency_cd;
            $request_data['refund_info'][$i]['refund_id']  = $refund_id;
            $request_data['refund_info'][$i]['updated_by'] = $this->user_name ? : $user_name;
            if (!$this->order_refund_detail_table->add($request_data['refund_info'][$i])) {
                throw new \Exception(L('退款详情创建失败'));
            }

            $add_data = [
                'after_sale_no'         => $after_sale_no,
                'order_id'              => $order_id,
                'order_no'              => $order_no,
                'type'                  => 3,
                'after_sale_id'         => $refund_id,
                'created_by'            => $this->user_name ? : $user_name,
                'updated_by'            => $this->user_name ? : $user_name,
                'platform_code'         => $plat_cd,
                'platform_country_code' => $plat_country_cd,
                'source_type'           => $source_type,
            ];
            if (false == $this->relevance_table->add($add_data)) {
                throw new \Exception(L('售后单创建失败'));
            }
            $this->synOrderAfterSaleStatus($order_id,$platform_cd,$status_code,self::REFUND_TYPE);
            TbOpOrderRefundLogModel::recordLog($refund_id,'申请退款','退款待审核',$request_data['apply_opinion']);
        }
        return $after_sale_no;
    }

    /**
     * 退款编辑（草稿)
     * @param $request_data
     * @return string
     * @throws Exception
     */
    public function refundApplyEdit($request_data)
    {
        $count = count($request_data['refund_info']);
        for($i = 0; $i<$count; $i++) {
            $id = $request_data['refund_info'][$i]['id'];
            if (!$id) {
                throw new \Exception(L('未找到退款详情ID'));
            }
            if (0 === strpos($request_data['refund_info'][$i]['amount_currency_cd'], 'N00')) {
                $amount_currency_cd = $request_data['refund_info'][$i]['amount_currency_cd'];
            } else {
                $amount_currency_cd = $request_data['refund_info'][$i]['amount_currency_cd'];
                $amount_currency_cd = TbMsCmnCdModel::getCurrencyCdByVal($amount_currency_cd);
                if (!$amount_currency_cd) {
                    throw new \Exception(L('未找到订单支付币种'));
                }
            }
            $request_data['refund_info'][$i]['amount_currency_cd'] = $amount_currency_cd;
            $request_data['refund_info'][$i]['updated_by'] = $this->user_name;
            if (false === $this->order_refund_detail_table->where(['id'=> $id])->save($request_data['refund_info'][$i])) {
                throw new \Exception(L('退款详情编辑失败'));
            }
        }
        return true;
    }

    /**
     * 检查订单状态是否符合退款
     * @param $send_order_status
     * @param $order_status
     * @return bool
     */
    public function checkOrderStatus($send_order_status, $order_status)
    {
        //  10735 订单状态_处理中支持售后退款 - N000551004 订单状态 为处理中的都可以
        switch ($send_order_status) {
            case 'N001820100':
            case 'N001820900':
            case 'N001821100':
                //待预派
                //已出库
                //订单取消
                if (!in_array($order_status, ['N000550300','N000550400','N000550500','N000550600','N000550800','N000550900','N000551000','N000551004'])) {
                    return false;
                }
                break;
            case 'N001821000':
                //待派单
                if (!in_array($order_status, ['N000550400','N000550900','N000551000','N000551004'])) {
                    return false;
                }
                break;
            case 'N001820500':
            case 'N001820600':
            case 'N001820700':
            case 'N001820800':
                //待拣货
                //待分拣
                //待核单
                //待出库
                if (!in_array($order_status, ['N000550400','N000550500','N000551004'])) {
                    return false;
                }
                break;
        }
        return true;
    }

    private function deleteRefundInfo($after_sale_no)
    {
        if (empty($after_sale_no)) {
            return;
        }
        $refund_info   = $this->order_refund_table->where(['after_sale_no' => $after_sale_no])->find();
        $refund_detail = $this->order_refund_detail_table->where(['refund_id' => $refund_info['id']])->find();
        $del_refund    = $this->order_refund_table->where(['after_sale_no' => $after_sale_no])->delete();
        $del_detail    = $this->order_refund_detail_table->where(['refund_id' => $refund_info['id']])->delete();
        if (!$del_refund || !$del_detail ) {
            throw new \Exception(L('售后单删除失败'));
        }
        Logs($refund_info, __FUNCTION__, 'fm');
        Logs($refund_detail, __FUNCTION__, 'fm');
    }

    /**
     * 检查退款单条数
     * @param $data
     * @throws Exception
     */
    private function checkRefundInfo($data)
    {
        $refund_info = $data['refund_info'];
        $reasons = array_column($refund_info, 'refund_reason_cd');
        if (count($refund_info) > 2) {
            throw new \Exception(L('退款记录最多只能添加两条'));
        }
        if (count($refund_info) == 2) {
            if (count(array_unique($reasons)) == 1) {
                throw new \Exception(L('两条退款不能是相同赔付原因'));
            }
            if (!in_array(self::REFUND_TYPE_SHIPPING, $reasons)) {
                throw new \Exception(L('至少要有一条赔付原因为单独退运费的记录'));
            }
        }
    }

    /**
     * 审核退款单
     * @param $data
     * @throws Exception
     */
    public function auditRefund($data)
    {
        $refund_info = $this->repository->getRefundInfoById($data['after_sale_id']);
        switch ($data['audit_status_cd']) {
            case self::AUDIT_STATUS_PASS:
                if ($refund_info['audit_status_cd'] != self::AUDIT_STATUS_WAIT) {
                    throw new \Exception(L('审核状态已更新'));
                }
                if (!$refund_info['trade_no'] && !$refund_info['refund_account']) {
                    throw new \Exception(L('订单交易号/账号至少有一个不能为空'));
                }
                $payment_audit_id = (new B2CPaymentService())->createPaymentAuditBill($refund_info);
                if (!$payment_audit_id) {
                    throw new \Exception(L('付款单创建失败'));
                }
                TbOpOrderRefundLogModel::recordLog($refund_info['refund_id'],'审核成功','退款中',$data['audit_opinion']);
                break;
            case self::AUDIT_STATUS_NO_PASS:
                TbOpOrderRefundLogModel::recordLog($refund_info['refund_id'],'审核失败','取消退款',$data['audit_opinion']);
                break;
            case self::AUDIT_STATUS_TRASH:
                TbOpOrderRefundLogModel::recordLog($refund_info['refund_id'],'退款废弃','取消退款',$data['audit_opinion']);
                break;
        }
        $status_code = $this->getAfterSaleStatus($data['audit_status_cd']);
        $save = [
            'audit_status_cd'  => $data['audit_status_cd'],
            'audit_opinion'    => $data['audit_opinion'],
            'payment_audit_id' => $payment_audit_id,
            'status_code'       => $status_code,
            'audit_user'       => DataModel::userNamePinyin(),
            'audit_time'       => dateTime(),
        ];
        $res = $this->order_refund_table->where(['id' => $data['after_sale_id']])->save($save);
        if (!$res) {
            throw new \Exception(L('审核失败'));
        }
        $this->synOrderAfterSaleStatus($refund_info['order_id'],$refund_info['platform_cd'],$status_code,self::REFUND_TYPE);
    }

    /**
     * 根据退款审核状态，获取售后状态
     * @param $audit_status_cd
     * @return string
     * @throws Exception
     */
    private function getAfterSaleStatus($audit_status_cd)
    {
        switch ($audit_status_cd) {
            case self::AUDIT_STATUS_WAIT:
                $status_code = self::STATUS_REFUND_WAIT_AUDIT;
                break;
            case self::AUDIT_STATUS_DRAFT:
                $status_code = self::STATUS_REFUND_APPLY;
                break;
            case self::AUDIT_STATUS_TRASH:
                $status_code = self::STATUS_REFUND_CANCEL;
                break;
            case self::AUDIT_STATUS_PASS:
                $status_code = self::STATUS_REFUND_ING;
                break;
            case self::AUDIT_STATUS_NO_PASS:
                $status_code = self::STATUS_REFUND_CANCEL;
                break;
            default:
                throw new \Exception(L('未知审核类型'));
                break;
        }
        return $status_code;
    }

    /**
     * 订单售后状态同步
     * @param $order_id
     * @param $platform_cd
     * @param $status 售后状态
     * @param $type 补发/退货/退款
     * @param $type 补发/退货/退款
     * @param bool $is_throw
     * @throws Exception
     */
    private function synOrderAfterSaleStatus($order_id, $platform_cd, $status, $type, $is_throw = true)
    {
        $model = M('order_extend', 'tb_op_');
        $where = ['order_id'=>$order_id, 'plat_cd'=>$platform_cd];
        $extends = $model->where($where)->find();
        $save_data = [
            'order_id' => $order_id,
            'plat_cd' => $platform_cd,
        ];
        switch ($type) {
            case self::RETURN_TYPE:
                $save_data['return_status_cd'] = $status;
                break;
            case self::REISSUE_TYPE:
                $save_data['reissue_status_cd'] = $status;
                break;
            case self::REFUND_TYPE:
                $save_data['refund_status_cd'] = $status;
                break;
            default:
                if ($is_throw) {
                    throw new \Exception(L('未知售后类型'));
                }
                break;
        }
        if (empty($extends)) {
            $res = $model->add($save_data);
        } else {
            $res = $model->where($where)->save($save_data);
        }
        if(false === $res) {
            if ($is_throw) {
                throw new \Exception(L('更新订单售后状态失败'));
            } else {
                Logs([$where,$save_data,$res], __FUNCTION__, 'fm');
            }
        }
    }

    /**
     * @param $payment_audit_id
     * @param $status 售后单状态
     * @param $order_status 订单状态
     */
    public function synOrderStatus($payment_audit_id, $status, $order_status)
    {
        $refund_info = $this->order_refund_table->where(['payment_audit_id'=>$payment_audit_id])->find();
        if (!empty($order_status)) {
            $this->synDeliveryStatus($refund_info,$order_status);
        }
        $this->synOrderAfterSaleStatus($refund_info['order_id'],$refund_info['platform_cd'],$status, self::REFUND_TYPE);
    }

    /**
     * 根据退款单更新订单状态
     * @param $refund_info 退款单信息
     * @param $order_status 订单状态
     * @throws Exception
     */
    public function synDeliveryStatus ($refund_info, $order_status)
    {
        $where = ['o.ORDER_ID'=>$refund_info['order_id'], 'o.PLAT_CD'=>$refund_info['platform_cd']];
        $order_info = $this->model->table('tb_op_order o')
            ->field('BWC_ORDER_STATUS,refund_delivery_status')
            ->join('left join tb_op_order_extend e on o.ORDER_ID = e.order_id and o.PLAT_CD = e.plat_cd')
            ->where($where)
            ->find();
        if ($order_status != 'restore') {
            if ($order_status == 'N000550900') {
                //N000550900（交易关闭）表示退款完成，需要更新派单状态为已取消
                $res1 = $this->order_table ->where(['ORDER_ID'=>$refund_info['order_id'], 'PLAT_CD'=>$refund_info['platform_cd']]) ->save([
                    'BWC_ORDER_STATUS' => $order_status,
                    'SEND_ORD_STATUS'  => 'N001821100'//订单取消
                ]);
            } else {
                $res1 = $this->order_table ->where(['ORDER_ID'=>$refund_info['order_id'], 'PLAT_CD'=>$refund_info['platform_cd']]) ->save([
                    'BWC_ORDER_STATUS' => $order_status,
                ]);
            }
            $res2 = $this->order_extend_table ->where(['order_id'=>$refund_info['order_id'], 'plat_cd'=>$refund_info['platform_cd']]) ->save([
                'refund_delivery_status' => $order_info['BWC_ORDER_STATUS']
            ]);
            if (false === $res1 || false === $res2) {
                throw new \Exception(L('更新订单状态失败'));
            }
        } else {
            //付款单撤回操作
            if (!empty($order_info['refund_delivery_status'])) {
                $res = $this->order_table->where(['ORDER_ID'=>$refund_info['order_id'], 'PLAT_CD'=>$refund_info['platform_cd']])->save([
                    'BWC_ORDER_STATUS' => $order_info['refund_delivery_status'],
                ]);
                if (false === $res) {
                    throw new \Exception(L('更新订单状态失败'));
                }
            }
        }
    }

    /**
     * 订单展示售后状态
     * @param $order_info
     * @return mixed
     */
    public function orderShowAfterSale($order_info)
    {
        if (empty($order_info)) {
            return $order_info;
        }
        $reissue_map = $return_map = $refund_map = $nos = [];
        $where_str = '(';
        foreach ($order_info as $item) {
            $where_str .= "(rel.order_id = '{$item['order_id']}' and rel.platform_country_code = '{$item['plat_cd']}') or ";
        }
        $where_str = trim($where_str, 'or '). ')';
        $reissue_info = $this->repository->getReissueInfos($where_str);
        $return_info = $this->repository->getReturnInfos($where_str);
        $refund_info = $this->repository->getRefundInfos($where_str);
        foreach ($reissue_info as $value) {
            $reissue_map[$value['order_id'].$value['platform_country_code']] = $value['after_sale_no'];
        }
        foreach ($return_info as $value) {
            $return_map[$value['order_id'].$value['platform_country_code']] = $value['after_sale_no'];
        }
        foreach ($refund_info as $value) {
            $refund_map[$value['order_id'].$value['platform_country_code']] = $value['after_sale_no'];
        }
        foreach ($order_info as &$item) {
            $nos = [];
            if (!empty($reissue_map[$item['order_id'].$item['plat_cd']])) {
                $nos[] = $reissue_map[$item['order_id'].$item['plat_cd']];
            }
            if (!empty($return_map[$item['order_id'].$item['plat_cd']])) {
                $nos[] = $return_map[$item['order_id'].$item['plat_cd']];
            }
            if (!empty($refund_map[$item['order_id'].$item['plat_cd']])) {
                $nos[] = $refund_map[$item['order_id'].$item['plat_cd']];
            }
            rsort($nos,SORT_NUMERIC);
            $item['after_sale_no'] = $nos[0];
        }
        return $order_info;
    }

    /**
     * 新旧code映射
     * @param $new_status 新增加的退货/补发code
     * @return array
     */
    public function getStatusMap($new_status)
    {
        $new_status = (array) $new_status;
        foreach ($new_status as $item) {
            if (self::$status_map[$item]) {
                $old_status[]  = self::$status_map[$item];
            } else {
                $old_status[] = [$item];
            }
        }
        $return = [];
        array_walk_recursive($old_status, function ($v) use (&$return) {
            $return[] = $v;
        });
        return array_unique($return);
    }

    /**
     * 判断订单是否发起了退款
     * @param $data
     * @return bool
     */
    public function checkOrderRefund($data, $search_key = 'order_id')
    {
        $where_refund_str = $where_order_str = $error_msg = "";
        if ($search_key == 'order_id') {
            //根据order_id和plat_cd条件搜索订单
            foreach ($data as $v) {
                $where_order_str .= sprintf("(ORDER_ID = '%s' and PLAT_CD = '%s') or ",$v['order_id'], $v['plat_cd']);
            }
            $where_order_str = trim($where_order_str, 'or ');
            $orders_info = $this->order_table->field(['PARENT_ORDER_ID,PLAT_CD'])->where($where_order_str)->select();
            foreach ($orders_info as $item) {
                if (empty($item['PARENT_ORDER_ID'])) {
                    continue;
                }
                $data[] = [
                    'order_id' => $item['PARENT_ORDER_ID'],
                    'plat_cd' => $item['PLAT_CD']
                ];
            }
        } else {
            //根据b5c_order_no条件搜索订单
            $orders_info = $this->order_table->field(['PARENT_ORDER_ID,ORDER_ID,PLAT_CD'])->where(['B5C_ORDER_NO'=>['IN',$data]])->select();
            $data = [];
            foreach ($orders_info as $item) {
                $data[] = [
                    'order_id' => $item['ORDER_ID'],
                    'plat_cd' => $item['PLAT_CD']
                ];
                if (!empty($item['PARENT_ORDER_ID'])) {
                    $data[] = [
                        'order_id' => $item['PARENT_ORDER_ID'],
                        'plat_cd' => $item['PLAT_CD']
                    ];
                }
            }
        }
        $where['status_code'] = ['not in',['N002800008','N002800014']];
        foreach ($data as $v) {
            $where_refund_str .= sprintf("(order_id = '%s' and platform_cd = '%s') or ",$v['order_id'], $v['plat_cd']);
        }
        $where['_string'] = trim($where_refund_str, 'or ');
        $list = $this->order_refund_table->where($where)->group('order_id,platform_cd')->select();
        if (empty($list)) {
            return true;
        }
        $orders_no = [];
        foreach ($list as $item) {
            $orders_no[] = ['order_no' => $item['order_no']];
           $error_msg .=  $item['order_no']. '、';
        }
        $error_msg = trim($error_msg, '、'). L('母单属于退款售后单，不允许派单/开单/拣货/分拣/核单/出库操作！');
        $res['info']   = $res['msg'] = $error_msg;
        $res['status'] = $res['state'] = $res['code'] = 4003;
        $res['data']   = $orders_no;
        return $res;
    }

    /**
     * 判断订单是否是万邑通 and 物流下单成功
     * @param $data
     * @param $search_key
     * @return bool
     */
    public function checkOrderVoidWarehouse($data, $search_key = 'order_id')
    {
        //$where['o.logistic_cd'] = 'N000708200'; //万邑通
        $where['op.REFERENCE_NO'] = [['EXP', 'IS not NULL'], ['neq', ''], 'and'];//有值
        $where['o.LOGISTICS_SINGLE_STATU_CD'] = ['in', ['N002080400', 'N002080600']]; //已获取 等待获取运单号
        $where_str = "";
        foreach ($data as $v) {
            $where_str .= sprintf("(o.ORDER_ID = '%s' and o.PLAT_CD = '%s') or ",$v[$search_key], $v['plat_cd']);
        }
        $where['_string'] = trim($where_str, 'or ');
        $orders_info = $this->model->table('tb_op_order o')
            ->field('o.ORDER_ID,o.PLAT_CD,o.BWC_ORDER_STATUS,o.logistic_cd')
            ->join('left join tb_ms_ord_package op on o.ORDER_ID = op.ORD_ID AND o.PLAT_CD = op.plat_cd')
            ->where($where)->select();
        if (empty($orders_info)) {
            throw new \Exception(L('当前勾选订单物流下单未成功，请直接操作【退回待派单】'));
        }
        if (array_unique(array_column($orders_info, 'logistic_cd')) != ['N000708200']) {
            throw new \Exception(L('作废功能仅适用于万邑通仓库，请重试！'));
        }
        return $orders_info;
    }

    /**
     * 过滤已经发起售后的订单
     * @param $b5c_order_nos
     * @return bool
     */
    public function filterOrderRefund($b5c_order_nos)
    {
        $where_refund_str = $where_order_str = $error_msg = "";
        $orders_info = $this->order_table->field(['PARENT_ORDER_ID,ORDER_ID,PLAT_CD,B5C_ORDER_NO'])->where(['B5C_ORDER_NO'=>['IN',$b5c_order_nos]])->select();
        $data = [];
        foreach ($orders_info as $item) {
            $data[] = [
                'order_id' => $item['ORDER_ID'],
                'plat_cd' => $item['PLAT_CD']
            ];
            if (!empty($item['PARENT_ORDER_ID'])) {
                $data[] = [
                    'order_id' => $item['PARENT_ORDER_ID'],
                    'plat_cd' => $item['PLAT_CD']
                ];
            }
        }
        $where['status_code'] = ['not in',['N002800008','N002800014']];
        foreach ($data as $v) {
            $where_refund_str .= sprintf("(order_id = '%s' and platform_cd = '%s') or ",$v['order_id'], $v['plat_cd']);
        }
        $where['_string'] = trim($where_refund_str, 'or ');
        $refund_info = $this->order_refund_table->where($where)->group('order_id,platform_cd')->select();

        $order_map =  $orders_no = [];
        foreach ($orders_info as $item) {
            if (!empty($item['PARENT_ORDER_ID'])) {
                $order_map[$item['PARENT_ORDER_ID']. $item['PLAT_CD']] = $item['B5C_ORDER_NO'];
            } else {
                $order_map[$item['ORDER_ID']. $item['PLAT_CD']] = $item['B5C_ORDER_NO'];
            }
        }
        foreach ($refund_info as $item) {
            if (isset($order_map[$item['order_id']. $item['platform_cd']])) {
                Logs($item, __FUNCTION__.'已经发起售后单不允许自动发货', __CLASS__);
                unset($order_map[$item['order_id']. $item['platform_cd']]);
            }
        }
        return array_values($order_map);
    }

    /**
     * 校验地址
     * @param $data
     * @param $flag
     * @return bool
     */
    public function checkOrderAddressValid($data, $flag = false)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //获取所有的地址校验配置-对应的物流模式
        $Logistics_mode_ids = self::AddressValidLogisticsModeId();
        $where['o.logistic_cd'] = 'N000708200'; //万邑通
        $where['o.logistic_model_id'] = ['in', $Logistics_mode_ids]; //万邑通
        $where['e.address_valid_status'] = ['lt', 2];
        $where_str = $error_msg = "";
        foreach ($data as $v) {
            $where_str .= sprintf("(o.ORDER_ID = '%s' and o.PLAT_CD = '%s') or ",$v['order_id'], $v['plat_cd']);
        }
        $where['_string'] = trim($where_str, 'or ');
        $list = $this->model->table('tb_op_order o')
            ->field('o.ORDER_ID,o.PLAT_CD,o.BWC_ORDER_STATUS,o.ADDRESS_USER_CITY,o.ADDRESS_USER_COUNTRY,o.ADDRESS_USER_COUNTRY_EDIT,o.ADDRESS_USER_COUNTRY_CODE,
            o.ADDRESS_USER_ADDRESS1,o.ADDRESS_USER_POST_CODE,e.doorplate,e.id,e.address_valid_status,e.address_valid_res')
            ->join('left join tb_op_order_extend e on o.ORDER_ID = e.order_id and o.PLAT_CD = e.plat_cd')
            ->where($where)->select();
        if (empty($list)) {
            return ['res' => true];
        }
        $error_info = $data = [];
        $bool = true;
        //验证结果入库
        $save_all = [];
        //$res = GuzzleModel::addressVaild($list);
        foreach (DataModel::toYield($list) as $k => $item) {
            if (!$item['id'] || $item['address_valid_status'] == 1) {
                $bool = false;
                $error_info['err_order'][] = $item['ORDER_ID'];
                continue;
            }
            $bool = false;
            $error_info['err_order'][] = $item['ORDER_ID'];
            continue;
            $param = [
                'city'    => htmlspecialchars_decode($item['ADDRESS_USER_CITY'], ENT_QUOTES),
                "country" => $item['ADDRESS_USER_COUNTRY_EDIT'] ?: (htmlspecialchars_decode($item['ADDRESS_USER_COUNTRY'], ENT_QUOTES) ?: $item['ADDRESS_USER_COUNTRY_CODE']),
                'houseNo' => $item['doorplate'],
                'street'  => htmlspecialchars_decode($item['ADDRESS_USER_ADDRESS1'], ENT_QUOTES),
                'zipcode' => htmlspecialchars_decode($item['ADDRESS_USER_POST_CODE'], ENT_QUOTES),
            ];
            $res = ApiModel::addressValid($param);
            if ($res) {
                $res_data = json_decode($res, true);
                if ($res_data['code'] == '0') {
                    $save['address_valid_status'] =  2;
                    if (strpos($res_data, 'false') !== false) {
                        $bool = false;
                        $error_info['err_order'][] = $item['ORDER_ID'];
                        $save['address_valid_status'] =  1;
                    }
                } else {
                    //接口请求失败 false
                    $bool = false;
                    $error_info['err_order'][] = $item['ORDER_ID'];
                    $save['address_valid_status'] =  1;
                }
                $save['id'] = $item['id'];
                $save['address_valid_res'] = $res;
                $save_all[] = $save;
            }
        }
        $msg = '待派单列表页收货地址校验';
        $order_log_m = new OrderLogModel();
        $order_log_m->addAllLog($list, $msg);
        //地址校验
        $error_info['res'] = $bool;
        $order_extend_model = M('order_extend', 'tb_op_');
        if (!empty($save_all)) {
            $res = $this->model->execute(TempImportExcelModel::saveAllExtend($save_all, $order_extend_model, $pk = 'id', ['id']));
        }

        if (!$bool) return $error_info;
        return true;
    }

    /**
     * 校验地址
     * @param $data
     * @return bool
     */
    public static function AddressValidLogisticsModeId()
    {
        //获取所有的地址校验配置-对应的物流模式
        $address_valid_conf = CommonDataModel::addressValidConf();
        if (empty($address_valid_conf)) {
            throw new \Exception(L('地址校验配置缺失'));
        }
        $logistics_mode_ids = M('ms_logistics_mode', 'tb_')->field('id')->where(['LOGISTICS_MODE' => ['in', array_column($address_valid_conf, 'cdVal')]])->select();//第三方平台
        if (empty($logistics_mode_ids)) {
            throw new \Exception(L('地址校验配置-对应的物流模式缺失'));
        }
        return array_column($logistics_mode_ids, 'id');
    }

    /**
     * 校验地址
     * @param $data
     * @return bool
     */
    public function orderAddressValid($data)
    {
        //获取所有的地址校验配置-对应的物流模式
        $Logistics_mode_ids = self::AddressValidLogisticsModeId();
        $where['o.logistic_cd'] = 'N000708200'; //万邑通
        $where['o.logistic_model_id'] = ['in', $Logistics_mode_ids]; //万邑通
        $where['_string'] = sprintf("(o.ORDER_ID = '%s' and o.PLAT_CD = '%s')",$data['order_id'], $data['plat_cd']);
        $list = $this->model->table('tb_op_order o')
            ->field('o.ADDRESS_USER_CITY,o.ADDRESS_USER_COUNTRY,o.ADDRESS_USER_COUNTRY_EDIT,o.ADDRESS_USER_COUNTRY_CODE,
            o.ADDRESS_USER_ADDRESS1,o.ADDRESS_USER_POST_CODE,e.doorplate,e.id')
            ->join('left join tb_op_order_extend e on o.ORDER_ID = e.order_id and o.PLAT_CD = e.plat_cd')
            ->where($where)->find();
        if (empty($list)) {
            return ['res' => true];
        }
        $param = [
            'city'    => htmlspecialchars_decode($list['ADDRESS_USER_CITY'], ENT_QUOTES),
            "country" => $list['ADDRESS_USER_COUNTRY_EDIT'] ?: (htmlspecialchars_decode($list['ADDRESS_USER_COUNTRY'], ENT_QUOTES) ?: $list['ADDRESS_USER_COUNTRY_CODE']),
            'houseNo' => $list['doorplate'],
            'street'  => htmlspecialchars_decode($list['ADDRESS_USER_ADDRESS1'], ENT_QUOTES),
            'zipcode' => htmlspecialchars_decode($list['ADDRESS_USER_POST_CODE'], ENT_QUOTES),
        ];
        $res = ApiModel::addressValid($param);
        $msg = '待派单详情页收货地址校验。请求：' . json_encode($param) . '响应：' . $res;
        $order_log_m = new OrderLogModel();
        $order_log_m->addLog($data['order_id'], $data['plat_cd'], $msg);
        //验证结果入库
        //地址校验
        $status = false;
        if ($res) {
            $save['address_valid_status'] = 1;
            if (strpos($res, 'true') !== false) {
                $save['address_valid_status'] = 2;
                $status = true;
            }
            $save['address_valid_res'] = $res;
            $res1 = M('order_extend', 'tb_op_')->where(['id' =>  $list['id']])->save($save);
        } else {
            $res = '{"code" : "-1","msg" : "收货地址校验API无响应！请稍后再试。","data" : ""}';
        }
        $return['res'] = $status;
        $return['data'] = $res;
        return $return;
    }

    public function getAddressValidLogisticsModeId() {
        $address_valid_conf = CommonDataModel::addressValidConf();
        return M('ms_logistics_mode', 'tb_')->where(['LOGISTICS_MODE' => ['in', [array_column($address_valid_conf, 'cdVal')]]])->getField('ID');//
    }

    // 检查订单是否是售后状态（补发，退款，退货）
    public function checkOrderAfterSales($data)
    {
        $where_str = "";
        foreach ($data as $v) {
            $where_str .= sprintf("(order_id = '%s' and platform_country_code = '%s') or ",$v['order_id'], $v['plat_cd']);
        }
        $where['_string'] = trim($where_str, 'or ');
        $list = $this->relevance_table->where($where)->select();
        if (empty($list)) {
            return true;
        }
        $result = [];
        foreach ($list as $key => $value) {
            $where = [];
            $res = 0;
            $where['id'] = $value['after_sale_id'];
            $where['status_code'] = ['not in',['N002800008','N002800014', 'N002800020', 'N002800022']]; // 剔除取消退款、无效、待使用的售后单
            switch ($value['type']) {
                case '1':
                    $res = $this->return_table->where($where)->count('id');
                    break;
                case '2':
                    $res = $this->reissue_table->where($where)->count('id');
                    break;
                case '3':
                    $res = $this->order_refund_table->where($where)->count('id');
                    break;
                default:
                    # code...
                    break;
            }
            if ($res > 0) {
               $result[] = ['order_id' => $value['order_id'], 'plat_cd' => $value['platform_country_code'], 'orderkey' => $value['platform_country_code'] . $value['order_id']]; 
            }
        }
        return $result;
    }

    /**
     * 售后审核推送微信卡片消息
     * @param $id 售后单id
     * @param $send_email 要推送的账号
     */
    public function bulidAfterSaleApproval($id, $send_email) {
        $send_data = $this->getAfterSaleWechatApprovalData($id);
        $wx_return_res = (new ReviewMsgTpl())->sendWeChatAfterSaleApproval($send_data, $send_email, 'after_sales_audit');
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    /**
     *  发送企业微信通知
     */
    public function  workWxMessage($user_ids,$message_string){
        $WechatMsg = new WechatMsg();
        $res = $WechatMsg->sendTextNew($user_ids . "@gshopper.com",$message_string);
        Logs([$user_ids, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    /**
     * 售后审核推送微信卡片消息数据
     * @param $id
     * @return array
     */
    private function getAfterSaleWechatApprovalData($id)
    {
        $Model = new \Model();
        $order_refund = $Model->field('after_sale_no,order_id,order_no,platform_cd')->table('tb_op_order_refund')->where(['id' => $id])->find();
        return $this->getRefundDetail($order_refund);
    }

    /**
     * 退货入库推送微信卡片消息
     * @param $data 请求参数 订单id 平台cd return_goods_id
     */
    public function ReturnWarehouseApproval($data) {
        $dept_names = $this->getDeptByOrder($data);
        $send_email = $this->getUserEmailsByDept($dept_names);
        if (!empty($send_email)) {
            //$send_email    = ['cuncheng.xiao@gshopper.com', 'sellers.shen@gshopper.com'];
            return (new OmsAfterSaleService(null))->bulidReturnWarehouseApproval($data, $send_email);
        }
        return false;
    }

    /**
     * 根据订单销售小团队获取部门
     * @param $data 请求参数 订单id 平台cd
     */
    public function getDeptByOrder($data)
    {
        //根据退货入库商品id查询订单信息
        $where['org.id'] = $data['return_goods_id'];
        $order_info = $this->model->table('tb_op_order_return_goods org')
            ->field('r.ORDER_ID,r.B5C_ORDER_NO,store.sell_small_team_cd,store.SALE_TEAM_CD')
            ->join('left join tb_op_order_return oor on org.return_id = oor.id')
            ->join('left join tb_op_order r on r.ORDER_ID = oor.order_id and r.PLAT_CD = oor.platform_cd')
            ->join('left join tb_ms_store store on r.store_id = store.ID')
            ->where($where)->find();
        //没有销售小团队则使用销售团队
        if (empty($order_info['sell_small_team_cd']) && empty($order_info['SALE_TEAM_CD']) ) {
            return [];
        }
        $team_cds = empty($order_info['sell_small_team_cd']) ? explode(',', $order_info['SALE_TEAM_CD']) : explode(',', $order_info['sell_small_team_cd']);
        $dept_names = $this->model->table('tb_ms_cmn_cd cd1')->field('cd2.ETC')
            ->join('left join tb_ms_cmn_cd cd2 on cd1.CD_VAL = cd2.CD_VAL')
            ->where(['cd1.CD' => ['in', $team_cds], ['cd2.CD' => ['like', 'N00396%']], 'cd2.USE_YN' => 'Y'])->select();

        return $dept_names;
    }

    #获取指定部门下的人员id
    public function getScEmailByDeptName($dept_name){
        return M('hr_empl_dept', 'tb_')
            ->field('bbm_admin.M_ID,bbm_admin.M_NAME,card.SC_EMAIL')
            ->join('tb_hr_dept on tb_hr_dept.ID = tb_hr_empl_dept.ID1')
            ->join('bbm_admin on bbm_admin.empl_id = tb_hr_empl_dept.ID2')
            ->join('LEFT JOIN tb_hr_card card ON card.EMPL_ID = tb_hr_empl_dept.ID2')
            ->where(['tb_hr_dept.DEPT_NM' => $dept_name, 'tb_hr_dept.DELETED_BY' => ['EXP', 'IS NULL']])
            ->select();
    }

    #获取指定部门下的人员id
    public function addChildDept($dept_names){
        $childDept = $this->model->table('tb_hr_dept thd1')->field('thd2.DEPT_NM')
            ->join('INNER JOIN tb_hr_dept thd2 on thd2.PAR_DEPT_ID = thd1.ID')
            ->where(['thd1.DEPT_NM' => ['in', $dept_names], 'thd1.DELETED_BY' => ['EXP', 'IS NULL'], 'thd2.DELETED_BY' => ['EXP', 'IS NULL']])
            ->select();
        if (empty($childDept)) {
            return $dept_names;
        } else {
            $childDept = $this->addChildDept(array_column($childDept, 'DEPT_NM'));
            return array_merge($dept_names, $childDept);
        }
    }

    /**
     * 根据部门名称查询部门下的员工邮箱
     * @param $dept_names
     */
    public function getUserEmailsByDept($dept_names)
    {
        //没有配置销售团队、销售小团队与部门关联code
        if (empty($dept_names)) {
            return [];
        }
        $send_email = [];
        $dept_names = array_column($dept_names, 'ETC');
        //追加子级部门
        $allDeptNames = $this->addChildDept($dept_names);
        //var_dump($allDeptNames);
        foreach ($allDeptNames as $item) {
            $user_infos = $this->getScEmailByDeptName($item);
            if (!empty($user_infos)) {
                $send_email = array_unique(array_merge($send_email, array_column($user_infos, 'SC_EMAIL')));
            }
        }
        //var_dump($send_email);exit;
        return $send_email;
    }

    /**
     * 退货入库推送微信卡片消息
     * @param $data 退货入库参数
     * @param $send_email 要推送的账号
     */
    public function bulidReturnWarehouseApproval($data, $send_email)
    {
        $send_data = $this->getReturnWarehouseWechatApprovalData($data);
        $wx_return_res = (new ReviewMsgTpl())->sendWeChatReturnWarehouseApproval($send_data, $send_email, 'return_warehouse_notice');
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    /**
     * 退货入库推送微信卡片消息数据
     * @param $data
     * @return array
     */
    private function getReturnWarehouseWechatApprovalData($data)
    {
        $return_info = $this->getReturnWarehouseGoods($data['return_goods_id']);
        $return_info['warehouse_num_broken'] = $data['warehouse_num_broken'];
        $return_info['warehouse_num'] = $data['warehouse_num'];
        return ['return_info' => $return_info];
    }

    /**
     * 退货单自动入库
     * @param $return_id
     * @return mixed
     * @author Redbo He
     * @date 2020/12/10 11:07
     */
    public function returnAutoWarehouse($return_id)
    {
        $return_model = M('op_order_return', 'tb_');
        # 查询退货单信息 退货单订单商品信息 （来自哪个平台） 值针对平台退货
        $result = $return_model->alias("a")
                        ->field("a.id,a.order_id,a.platform_cd,a.order_no,	org.id AS return_goods_id,org.sku_id, org.order_goods_num,org.over_return_num")
                        ->join("LEFT JOIN tb_op_order_return_goods org ON a.id = org.return_id")
                        ->where(['a.id' => ['eq', $return_id]])
                        ->select();
        $result_flag = false;
        if($result)
        {
            # TODO 判断是否是运行自动退货入库平台 如果是
            $allow_cds = ["N002621800"];
            $allow_plat_cds = CodeModel::getSiteCodeArr($allow_cds);
            $allow_plat_cds = array_column($allow_plat_cds,'CD');
            $afterSaveAction = new AfterSaleAction();
            $result_flag = true;
            ##
            $user_id = DataModel::userId();
            $user_change = 0;
            if(is_null($user_id)) {
                # 设置 session 用户I
                session('user_id', self::EPR_ADMIN_ID);
                $user_change = 1;
            }
            foreach ($result as $item)
            {
                if(in_array($item['platform_cd'], $allow_plat_cds))
                {
                    $request_data = [
                        'return_id'       => $item['id'],
                        'return_goods_id' => $item['return_goods_id'],
                        'sku_id'          => $item['sku_id'],
                        'warehouse_num'   => $item['over_return_num'],# 正品数量
                        'warehouse_num_broken' => 0 , # 残次品数量
                    ];
                    try
                    {
                        $afterSaveAction->validateReturnWarehouseData($request_data);
                        $this->returnWarehouseSubmit($request_data);
                        $is_end = $this->tagEnd($request_data['return_id']);
                        $this->updateWarehouseStatus($request_data['return_id'], $request_data['return_goods_id'], $is_end);
                    }
                    catch (Exception $e)
                    {
                        $result_flag = false;
                        Log::record("【退货单自动入库失败】".$e->__toString(), Log::ERR);
                        if(is_null($user_id)) {
                            # 设置 session 用户I
                            session('user_id', null);
                        }
                        throw new \Exception("退货单自动入库失败");
                        break;
                    }
                }
            }
            if($user_change)
            {
                session('user_id',null);
            }
        }
        return $result_flag;
    }

    /**
     *  检查订单收入成本冲销状态状态（未冲销，已冲销）
     * @return bool|int
     */
    public function checkOrderChargeOffStatus()
    {
        $num = 1000;
        $where['ord.WHOLE_STATUS_CD'] = OmsOutGoingModel::OUTGOING_COMPLETE; //派单状态 已出库
        $where['e.charge_off_status'] = 0; //收入成本冲销状态状态 未冲销
        $where1=array('r1.type'=>3,'r2.audit_status_cd'=>self::AUDIT_STATUS_SUCCESS,'_logic'=>'and'); //完成退款的退款售后单
        $where2=array('r1.type'=>1,'_complex'=>$where1,'_logic'=>'or'); //退货售后单
        $where['_complex'] = $where2;
        //未拆单订单出库
        $result = $this->model->table('tb_op_order_after_sale_relevance r1')
            ->field('e.id,op.PLAT_CD,op.ORDER_ID')
            ->join('left join tb_op_order_refund r2 on r1.after_sale_id = r2.id ')
            ->join('inner join tb_op_order op on op.ORDER_ID = r1.order_id and op.PLAT_CD = r1.platform_country_code')
            ->join('left join tb_ms_ord ord on op.ORDER_ID = ord.THIRD_ORDER_ID')
            ->join('left join tb_op_order_extend e on op.ORDER_ID = e.order_id and op.PLAT_CD = e.plat_cd')
            ->where($where)
            ->limit($num)
            ->group('op.PLAT_CD,op.ORDER_ID')
            ->select();
        if (!empty($result)) {
            $last_sql = $this->model->getLastSql();
            $sql = "UPDATE tb_op_order_extend,
                 (
                     " . $last_sql . "
                ) AS t3
                SET tb_op_order_extend.charge_off_status = 1
                WHERE
                    tb_op_order_extend.order_id = t3.ORDER_ID
                    AND tb_op_order_extend.plat_cd = t3.PLAT_CD";
            $res = $this->model->execute($sql);
            Logs(json_encode($res), 'order_extend_upd_db', 'checkOrderChargeOffStatus');
        }

        //拆单订单出库
        $result = $this->model->table('tb_op_order_after_sale_relevance r1')
            ->field('e.id,op.PLAT_CD,op.ORDER_ID,ord.THIRD_ORDER_ID')
            ->join('left join tb_op_order_refund r2 on r1.after_sale_id = r2.id ')
            ->join('inner join tb_op_order op on op.ORDER_ID = r1.order_id and op.PLAT_CD = r1.platform_country_code')
            ->join('left join tb_ms_ord ord on op.ORDER_ID = ord.THIRD_PARENT_ORDER_ID and FIND_IN_SET(ord.THIRD_ORDER_ID,r2.refund_child_order)') //兼容出库后拆单和未拆单的订单
            ->join('left join tb_op_order_extend e on ord.THIRD_ORDER_ID = e.order_id and op.PLAT_CD = e.plat_cd')
            ->where($where)
            ->limit($num)
            ->group('op.PLAT_CD,ord.THIRD_ORDER_ID') //兼容出库后拆单和未拆单的订单
            ->select();
        if (empty($result)) {
            return true;
        }
        $last_sql = $this->model->getLastSql();
        $sql = "UPDATE tb_op_order_extend,
                 (
                     " . $last_sql . "
                ) AS t3
                SET tb_op_order_extend.charge_off_status = 1
                WHERE
                    tb_op_order_extend.order_id = t3.THIRD_ORDER_ID
                    AND tb_op_order_extend.plat_cd = t3.PLAT_CD";
        $res = $this->model->execute($sql);
        Logs(json_encode($res), 'order_extend_upd_db', 'checkOrderChargeOffStatus');
        return $res;
    }

    /**
     * 更新订单收入成本冲销状态状态（未冲销，已冲销）
     * @param $data
     * @param $type 1:退货;0:退款
     *
     * @return bool|int
     * @throws Exception
     */
    public function upOrderChargeOffStatus($data, $type = 0)
    {
        $where['op.ORDER_ID'] = $data['order_id']; //订单号
        $where['op.PLAT_CD'] = $data['platform_cd']; //平台
        $where1=array('r1.type'=>3,'r2.audit_status_cd'=>self::AUDIT_STATUS_SUCCESS,'_logic'=>'and'); //完成退款的退款售后单
        $where2=array('r1.type'=>1,'_complex'=>$where1,'_logic'=>'or'); //退货售后单
        $where['_complex'] = $where2;
        //完成退款的退款售后单or退货售后单
        $result = $this->model->table('tb_op_order_after_sale_relevance r1')
            ->field('op.PLAT_CD,op.ORDER_ID,op.CHILD_ORDER_ID,group_concat(refund_child_order) refund_child_order')
            ->join('left join tb_op_order_refund r2 on r1.after_sale_id = r2.id ')
            ->join('inner join tb_op_order op on op.ORDER_ID = r1.order_id and op.PLAT_CD = r1.platform_country_code')
            ->group('op.PLAT_CD,op.ORDER_ID')
            ->order('r2.created_at')
            ->where($where)
            ->find();
        if (empty($result)) {
            return true;
        }
        $orderWhere['op.ORDER_ID'] = $where['op.ORDER_ID'];
        $orderWhere['op.PLAT_CD'] = $data['platform_cd']; //平台
        $orderWhere['ord.WHOLE_STATUS_CD'] = OmsOutGoingModel::OUTGOING_COMPLETE; //派单状态 已出库
        $orderWhere['e.charge_off_status'] = 0; //收入成本冲销状态状态 未冲销
        //退款进行转换处理 选择指定的子单 拆单后的订单出库表只记录子单 
        if (!empty($result['CHILD_ORDER_ID']) && 1 != $type) {
            $order_ids = explode(',', $result['CHILD_ORDER_ID']);
            $refund_child_order = explode(',', $result['refund_child_order']);
            $order_ids = array_intersect($order_ids, $refund_child_order);
            $orderWhere['op.ORDER_ID'] = ['in', $order_ids];
        }
        //收入成本冲销状态状态=未冲销and派单状态=已出库
        $op_order = $this->model->table('tb_op_order op')
            ->field('e.id,op.PLAT_CD,op.ORDER_ID,op.CHILD_ORDER_ID')
            ->join('left join tb_ms_ord ord on op.B5C_ORDER_NO = ord.ORD_ID')
            ->join('left join tb_op_order_extend e on op.ORDER_ID = e.order_id and op.PLAT_CD = e.plat_cd')
            ->where($orderWhere)->select();
        if (!empty($op_order)) {
            $extendWhere['id'] = ['in', array_column($op_order, 'id')];
            $res = $this->model->table('tb_op_order_extend')->where($extendWhere)->save(['charge_off_status' => 1]);
            Logs(json_encode($res), 'order_extend_upd_db', 'checkOrderChargeOffStatus');
            if (!$res) {
                throw new \Exception(L('订单extend收入成本冲销状态状态失败'));
            }
            return $res;
        }
        return true;
    }

    //获取售后取消相关状态（包括补发、退货、退款）
    public function getCancelStatus()
    {
        return self::$after_sale_cancel_status;
    }
}