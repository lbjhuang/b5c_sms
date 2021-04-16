<?php
/*
 *
 */
class ImprotBillAuditService extends Service
{

    protected $repository;
    protected $model = "";
    protected $improt_bill_audit_table = "";
    protected $improt_bill_audit_detail_table = "";
    protected $promotion_task_table = "";

    # 财务审核权限角色
    protected $finance_level_audit_by_role_map = [
        'stage' => [
            'gt5000' => [
                'two_level_role_id' => 133,
                'three_level_role_id' => 134,
            ],
            'lt5000' => [
                'one_level_role_id' => 132,
            ],
        ],
        'pro' => [
            'gt5000' => [
                'two_level_role_id' => 91,
                'three_level_role_id' => 92,
            ],
            'lt5000' => [
                'one_level_role_id' => 90,
            ],
        ],
    ];

    public function __construct($model)
    {
        $this->model = empty($model) ? new Model() : $model;
        $this->improt_bill_audit_table = M('improt_bill_audit', 'tb_wms_');
        $this->improt_bill_audit_detail_table = M('improt_bill_audit_detail', 'tb_wms_');
        $this->repository = new ImprotBillAuditRepository($this->model);
    }

    public function createAuditNo()
    {
        $audit_no = $this->improt_bill_audit_table->lock(true)->where(['audit_no' => ['like', 'CRKSP' . date('Ymd') . '%']])->order('id desc')->getField('audit_no');
        if ($audit_no) {
            $num = substr($audit_no, -4) + 1;
        } else {
            $num = 1;
        }
        $audit_no = 'CRKSP' . date('Ymd') . substr(10000 + $num, 1);
        return $audit_no;
    }

    /**
     *  添加数据【单条】
     */
    public function add($request_data)
    {
        $insert_data = array(
            'audit_no' => $this->createAuditNo(),
            'status_cd' => ImprotBillAuditAction::$status_await_sub_cd,
            'type_cd' => $request_data['type'],
            'create_by' => userName(),
            'create_at' => date('Y-m-d', time()),
            'team_cd' => $request_data['team_cd'],
        );
        $id = $this->repository->add($insert_data);
        if (!$id) {
            throw new Exception("创建EXCEL出入库流程失败");
        }
        $insert_data_log = array(
            'audit_no' => $insert_data['audit_no'],
            'message' => "发起EXCEL出入库审批",
            'create_by' => userName(),
            'create_at' => date('Y-m-d H:i:s', time()),

        );
        $res = $this->addLog($insert_data_log);
        if (!$res) {
            throw new Exception("添加日志失败");
        }
        return array('id' => $id, 'audit_no' => $insert_data['audit_no']);
    }

    public function addLog($insert_data)
    {
        $res = $this->repository->addLog($insert_data);
        return $res;
    }


    /**
     * 更新
     */
    public function update($condtion, $update_data)
    {
        $res = $this->repository->update($condtion, $update_data);
        return $res;
    }

    public function getFind($condtion, $field = "*")
    {
        $info_data = $this->repository->getFind($condtion, $field);
        return $info_data;
    }

    public function getDetailList($condtion, $field)
    {
        $list = $this->repository->getDetailList($condtion, $field);
        return $list;
    }

    public function getList($request_data)
    {
        $search_data = $request_data['search'];
        $pages_data = $request_data['pages'];
        if (empty($pages_data)) {
            $pages_data = array(
                'per_page' => 10,
                'current_page' => 1
            );
        }
        $condtion = $this->searchWhere($search_data);
        $count = $this->repository->getList($condtion, 'count(*) as total_rows');
        $limit = ($pages_data['current_page'] - 1) * $pages_data['per_page'] . ' , ' . $pages_data['per_page'];

        $field = "audit_no,status_cd,type_cd,sum_price_usd,create_by,team_cd";
        $list = $this->repository->getList($condtion, $field, $limit);
        $list = CodeModel::autoCodeTwoVal($list, ['status_cd', 'type_cd', 'team_cd']);
        return array(
            'datas' => $list,
            'page' => $count
        );
    }

    /**
     *  组装列表查询
     * @return array
     */
    private function searchWhere($search_data)
    {
        $condtion = array("1 = 1");
        if (is_array($search_data) && !empty($search_data)) {
            // 流程发起人
            if (isset($search_data['create_by']) && !empty($search_data['create_by'])) {
                $condtion['create_by'] = array('in', $search_data['create_by']);
            }
            // 流程节点
            if (isset($search_data['status_cd']) && !empty($search_data['status_cd'])) {
                $condtion['status_cd'] = array('in', explode(',', $search_data['status_cd']));
            }
            // 出入库类型
            if (isset($search_data['type_cd']) && !empty($search_data['type_cd'])) {
                $condtion['type_cd'] = array('in', explode(',', $search_data['type_cd']));
            }

            // 审批单号
            if (isset($search_data['audit_no']) && !empty($search_data['audit_no'])) {
                $condtion['audit_no'] = array('in', explode(',', $search_data['audit_no']));
            }
            // 货值总额
            if (isset($search_data['sum_price_front']) && !empty($search_data['sum_price_front'])
                && $search_data['sum_price_back'] && $search_data['sum_price_back']
            ) {
                $condtion['sum_price_usd'] = array('between', array($search_data['sum_price_front'], $search_data['sum_price_back']));
            }
        }
        return $condtion;
    }


    /**
     *  发送企业微信通知【认领推广需求】
     */
    public function claimWxMessage($post_data)
    {
        $user_name = userName();
        $post_data = CodeModel::autoCodeTwoVal($post_data, ['promontion_currency_cd']);
        foreach ($post_data as $itme) {
            $promotion_task_url = ERP_URL . $this->get_promotion_task_url . $itme['promotion_task_no'];
            if ($itme['promotion_type_cd'] == 'N003610003') {
                // 推广内容类型为 SKU
                $promontion_price = number_format($itme['promontion_price'], 2);
                if ($itme['platform_cd'] == 'N002620800') {
                    // 平台为Gshopper
                    $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广价格: {$itme['promontion_currency_cd_val']} {$promontion_price}，推广任务 ID： {$itme['promotion_task_no']}，请尽快确认优惠码。<a href='{$promotion_task_url}'>【去查看】</a>";
                } else {
                    $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广价格: {$itme['promontion_currency_cd_val']} {$promontion_price}，推广任务 ID： {$itme['promotion_task_no']}，请尽快确保平台价格一致。<a href='{$promotion_task_url}'>【去查看】</a>";
                }
            } else {
                $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广任务 ID： {$itme['promotion_task_no']}。<a href='{$promotion_task_url}'>【去查看】</a>";
            }
            $this->workWxMessage($itme['send_by'], $message_string);
        }
    }

    /**
     *  发送企业微信通知
     */
    public function workWxMessage($user_ids, $message_string)
    {
        $WechatMsg = new WechatMsg();
        $res = $WechatMsg->sendTextNew($user_ids . "@gshopper.com", $message_string);
        Logs([$user_ids, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    public function delDetail($contion)
    {
        $res = $this->repository->delDetail($contion);
        return $res;
    }

    public function addAllDetail($insert_data)
    {
        $res = $this->repository->addAllDetail($insert_data);
        return $res;
    }

    public function getLogList($contion)
    {
        $res = $this->repository->getLogList($contion);
        return $res;
    }


    public function WorkWxSendMarkdownMessage($send_data_wx, $audit_no)
    {
        Logs([$audit_no, $send_data_wx], __FUNCTION__, __CLASS__);
        if (!empty($send_data_wx)) {
            $wx_info = M('empl_wx','tb_hr_')->field('bbm_admin.M_NAME,tb_hr_empl_wx.wid ')
                ->join('LEFT JOIN bbm_admin ON bbm_admin.empl_id = tb_hr_empl_wx.uid')
                ->where(array('bbm_admin.M_NAME'=>$send_data_wx['send_by']))->find();
            if ($wx_info){
                $send_email = $wx_info['wid'];
            }else{
                $send_email = $send_data_wx['send_by'];
            }
            $data = ">**{$send_data_wx['title']}** 
>{$send_data_wx['subhead']}
>发起人：<font color=info >{$send_data_wx['create_by']}</font> 
>审批单号：<font color=warning >{$send_data_wx['audit_no']}</font> 
>审批发起时间：<font color=info >{$send_data_wx['create_at']}</font> 
>请尽快处理。 
>如需查看详情，请点击：[报价详情]({$send_data_wx['url_detail']})";
            $res = ApiModel::WorkWxSendMarkdownMessage($send_email, $data);
            Logs([$send_email, $data, $res], __FUNCTION__, __CLASS__);
        }
    }

    public function sendEmailMessage($send_data_em)
    {
        if (!empty($send_data_em)) {
            $email = new SMSEmail();
            $res = $email->sendEmail($send_data_em['to_email'], $send_data_em['title'], $send_data_em['content']);
            Logs([$send_data_em, $res], __FUNCTION__, __CLASS__);
        }
    }

    public function teamVal($condition, $field)
    {
        $res = M('cmn_cd', 'tb_ms_')->field($field)->where($condition)->select();
        return $res;
    }

    /**
     *  获取审批领导负责人
     */
    public function getCreateBy($audit_no){
        $list = M('admin','bbm_')
                ->field('bbm_admin.M_ID,
                  bbm_admin.M_NAME,
                  tb_hr_empl_dept.ID1,
                  tb_hr_empl_dept.ID2')
                ->join('LEFT JOIN tb_hr_empl_dept ON bbm_admin.empl_id = tb_hr_empl_dept.ID2')
                ->where(array('bbm_admin.M_ID'=>$_SESSION['userId']))
                ->select();
        $sql = M()->_sql();
        if (count($list) > 1){
            // 身兼多职 领导审批人就是自己
            $create_by = userName();
        }else if ( count($list) == 1){
            $leader = $this->getLeaderByDeptId($list[0]['ID1']);
            if (!$leader){
                throw new Exception('查询领导审批人异常-部门');
            }
            $create_by = $leader[0]['M_NAME'];
        }else{
            throw new Exception('查询领导审批人异常-负责人');
        }
        Logs([$audit_no,$list,$sql,$leader],__FUNCTION__,__CLASS__);
        return $create_by;
    }


    private function getLeaderByDeptId($deptId)
    {


        $dept_data = $this->repository->getLevelDept();
        $allDept = $this->getParentTree($dept_data, $deptId, 1);

        #去除gshopper
        unset($allDept[0]);
        $allDept = array_reverse($allDept);

        #找出自己所属部门 以及之上的所有领导
        $leader = [];
        foreach ($allDept as $value) {
            $tmp = $this->repository->summaryPeopleInDept($value['ID']);
            $tmp = array_reverse($tmp);
            $leader = array_merge((array)$leader, (array)$tmp);

        }
        return $leader;
    }


    public function getParentTree($arr, $id, $sort = 0)
    {
        $par_arr = array();

        while ($id != 0) {
            $tmp = $id;
            foreach ($arr as $v) {
                if ($v['ID'] == $id) {
                    $par_arr[] = $v;
                    $id = $v['PAR_DEPT_ID'];
                    break;
                }
            }
            if ($tmp == $id) {
                #找不到父级 终止
                $id = 0;
            }
        }
        if ($sort) $par_arr = array_reverse($par_arr);
        return $par_arr;
    }


    public function getLevelFinanceAuditBy($type = "gt5000", $level = "two")
    {
        $level_env_map = isProductEnv() ? 'pro' : 'stage';
        $level_audit_role_map  = $this->finance_level_audit_by_role_map[$level_env_map];
        if(empty($level_audit_role_map)) return false;
        $type_role_map = isset($level_audit_role_map[$type]) ? $level_audit_role_map[$type] : [];
        if(empty($type_role_map)) return false;
        $role_map_key = $level . "_level_role_id";
        $role_id = isset($type_role_map[$role_map_key]) ? $type_role_map[$role_map_key] : "";
        if(empty($role_id)) return false;

        # 查询角色用户 多个去最新的的那个
        $rbacService = new RbacService();
        $role_admins = $rbacService->getRoleAdminUsers($role_id);
        if(empty($role_admins)) return false;
        return  current($role_admins)["M_NAME"];
    }
}