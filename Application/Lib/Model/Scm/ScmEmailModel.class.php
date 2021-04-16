<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2018/6/7
 * Time: 10:44
 */

@import("@.Action.Scm.DisplayAction");

class ScmEmailModel
{

    private static function send($address, $title, $content, $cc = null, $attachment = null, $priority = 3)
    {
        //并发单例有问题
        $mail = new SMSEmail();
        $mail->Priority = $priority;
        $result = $mail->sendEmail($address, $title, $content, $cc, $attachment);
        if (!$result) {
            $data = [
                'error' => [
                    'message' => '邮件发送失败' . $mail->getError(),
                    'info' => [
                        'address' => $address,
                        'title' => $title,
                        'content' => $content,
                        'cc' => $cc,
                        'attachment' => $attachment
                    ]
                ],
                'request' => $_REQUEST,
                'url' => $_SERVER['REQUEST_URI'],
                'user' => $_SESSION['m_loginname'],
                'time' => date('Y-m-d H:i:s'),
            ];
            $fileName = 'err_' . date('Ymd') . '.log';
        } else {
            $data = [
                'info' => [
                    'address' => $address,
                    'title' => $title,
                    'content' => $content,
                    'cc' => $cc,
                    'attachment' => $attachment
                ],
                'request' => $_REQUEST,
                'url' => $_SERVER['REQUEST_URI'],
                'user' => $_SESSION['m_loginname'],
                'time' => date('Y-m-d H:i:s'),
            ];
            $fileName = 'snd_' . date('Ymd') . '.log';
        }
        $filePath = '/opt/logs/logstash/scm/';
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        chmod($filePath, 0777);
        $logContent = '------------Log start(Email log)------------' . $data['time'] . PHP_EOL;
        $logContent .= json_encode($data, JSON_UNESCAPED_UNICODE);
        $logContent .= PHP_EOL . '------------------Log end------------------' . PHP_EOL . PHP_EOL;
        file_put_contents($filePath . $fileName, $logContent, FILE_APPEND);
    }

    /**获取销售领导的邮件地址
     * @param $cd string 采购团队cd
     * @return array
     */
    public static function getLeaderEmail($cd)
    {
//        return 'due@gshopper.com';//fixdue 改
        $leaders = TbMsCmnCdModel::getInstance()->cache(true, 300)->where(['CD' => $cd])->getField('ETC');
        return explode(',', strtolower(trim($leaders, ',')));
    }

    /**
     * 获取法务邮箱地址
     * @param $cd
     * @return array
     */
    public static function getJusticeEmail($cd)
    {
//        return 'due@gshopper.com';//fixdue 改
        $justices = TbMsCmnCdModel::getInstance()->cache(true, 300)->where(['CD' => $cd])->getField('ETC3');
        $justices_arr = explode(',', strtolower(trim($justices, ',')));
        $justices_email_arr = [];
        foreach ($justices_arr as $v) {
            $justices_email_arr[] = $v . '@gshopper.com';
        }
        return $justices_email_arr;
    }

    /**获取所有采购团队领导们的邮箱
     * @return mixed
     */
    public static function getPurLeadersEmail()
    {
        return C('scm_ceo_email') == 'Helen.Yuan@gshopper.com' ? 'purchasing@gshopper.com' : 'due@gshopper.com';//fixdue 改
        /*$leaders =  TbMsCmnCdModel::getInstance()->cache(true,300)->where(['CD' => ['like', 'N00129%'], 'USE_YN' => 'Y', 'ETC' => ['neq', '']])->getField('ETC', true);
        $ret = array_unique(array_reduce($leaders, function($sum, $v) {
            return array_merge(explode(',', trim($v, ',')), $sum);
        }, []));
        return array_values($ret);*/
    }

    /**获取采购领导邮箱
     * @param $pur_team
     * @return array
     */
    public static function getPurLeaderEmail($pur_team)
    {
        $leaders = TbMsCmnCdModel::getInstance()->cache(true, 300)->where(['CD' => $pur_team])->getField('ETC');
        return explode(',', strtolower(trim($leaders, ',')));
    }

    /**获取ceo邮件地址
     * @return string
     */
    public static function getCeoEmail()
    {
//        return 'baisui@gshopper.com';//fixdue 改
        return C('scm_ceo_email');
    }

    /**获取ceo邮件地址
     * @return string
     */
    public static function getCeoEmailCC()
    {
//        return 'due@gshopper.com';//fixdue 改
        return C('scm_ceo_email_cc');
    }

    /**获取用户邮箱
     * @param $user
     * @return mixed
     */
    public static function getUserEmail($user)
    {
//        return 'due@gshopper.com';//fixdue 改
        if (strpos($user, ',') !== false) {
            return array_map(function ($v) {
                return $v . '@gshopper.com';
            }, explode(',', $user));
        } else {
            return $user . '@gshopper.com';
        }

    }

    /**获取邮件内容按钮
     * @param $type
     * @param $id
     * @param $name
     * @param $step
     * @return string
     */
    public static function getDetailBtn($type, $id, $name = '点击查看（Click to view）')
    {
        if ($type == 'demand') {
            $detail_page = 'demands';
            $step = D('Demand')->where(['id' => $id])->getField('step');
            if (in_array($step, [DemandModel::$step['demand_submit'], DemandModel::$step['demand_approve'], DemandModel::$step['purchase_claim']])) {
                $detail_page = 'demand_draft';
            }
            $url = U('scm/display/' . $detail_page, ['type' => $id], true, false, true);
        } else {
            $url = U('scm/display/purchases', ['type' => $id], true, false, true);
        }
        return '<br/><br/><br/><a target="_blank" href="' . $url . '">' . $name . '</a>';
    }

    public static function getB2BBtn($id, $name = '查看B2B订单(view B2B order)')
    {
        $url = U('home/b2b/order_list', ['order_id' => $id], true, false, true);
        return '<a target="_blank" style="margin-left: 30px" href="' . $url . '#/b2bsend">' . $name . '</a>';
    }

    public static function getPurBtn($id, $name = '查看采购订单(view purchase order)')
    {
        $url = U('home/order_detail/order_detail', ['id' => $id], true, false, true);
        return '<a target="_blank" style="margin-left: 30px" href="' . $url . '">' . $name . '</a>';
    }

    public static function A($demand)
    {
        $to = self::getLeaderEmail($demand['sell_team']);
        $title = "销售需求审批提醒";
        $content = "有销售需求需要您审批；<br/>需求编号：{$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content);
    }

    public static function AA($demand)
    {
        $demand_type = $demand['demand_type'] != DemandModel::$demand_type_store ? '销售需求' : '热销品囤货需求';
        $demand_type_en = $demand['demand_type'] != DemandModel::$demand_type_store ? 'sales demand' : 'top sellers stock up demand';
        $to = self::getLeaderEmail($demand['sell_team']);
        $title = "销售需求提交通知 - Sales Demand Submission Notice";
        $content = "您的销售团队成员创建了一个新的{$demand_type}<br/>Your sales team member has created a new {$demand_type_en}<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content);
    }

    public static function B($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $title = '销售需求退回提醒';
        $content = "您的销售需求被退回。<br/>需求编号：{$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content);
    }


    public static function C($demand)
    {
        $sell_team = cdVal($demand['sell_team']);
        $to = self::getPurLeadersEmail();
        $title = '销售新需求提醒-Sales New Demand Reminder';
        $content = "{$sell_team} 提了一个销售需求，快去认领吧！<br/>{$sell_team} has proposed a sales demand, please go and claim it!<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}"
            . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content);
    }


    public static function D($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $cc = self::getUserEmail($demand['create_user']);
        $title = '采购认领提醒 - Purchase Claim Reminder';
        $content = "您的销售需求被认领啦！<br/>Your sales demand is claimed!<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content, $cc);
    }


    public static function E($quotation_list)
    {
        foreach ($quotation_list as $q) {
            $to = self::getUserEmail($q['purchaser']);
            $title = '报价中标提醒 - Quotation Accept Reminder';
            $content = "您的报价已中标。<br/>Your quotation has been accepted.<br/><br/>报价编号：{$q['quotation_code']}<br/>Quotation No.: {$q['quotation_code']}" . self::getDetailBtn('quotation', $q['id']);
            self::send($to, $title, $content);
        }
    }


    public static function F($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $title = '采购确认提醒';
        $content = "中标的采购已全部确认报价。<br/>需求编号：{$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content);
    }


    public static function G($demand)
    {
        Logs($demand, __FUNCTION__, __CLASS__);
        $to = self::getLeaderEmail($demand['sell_team']);
        $title = '！！！销售需求审批提醒 - Sales Demand Approval Reminder！！！';
        $type = $demand['is_spot'] ? '' : '已经确认报价的';
        $type_en = $demand['is_spot'] ? '' : ' with confirmed quotation';
        $content = "有{$type}销售需求需要您审批。<br/>Sales demand{$type_en} is required for your approval.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content, null, null, 1);
        @self::sendWeChatIOrG($demand, $to, 'sales_leadership_approval');
    }


    public static function H($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $cc = self::getUserEmail($demand['create_user']);
        $title = '销售需求退回提醒 - Sales Demand Return Reminder';
        $content = "您的销售需求被退回。<br/>Your sales demand is returned.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content, $cc);
    }

    /**生成邮件审批免登录授权码
     * @param $user
     * @return string
     */
    private static function keyGen($user)
    {
        $auth['user'] = $user;
        $auth['created_by'] = $_SESSION['m_loginname'];
        $auth['url'] = 'api/scm_ceo_approve';
        $auth['endtime'] = date('Y-m-d H:i:s', time() + 86400 * 2);
        $c = 0;
        do {
            $key = md5(uniqid(mt_rand()));
            $auth['key'] = $key;
            $c++;
            if ($c == 20) {
                break;
            }
        } while (!M('auth_key', 'tb_')->add($auth));
        return $key;
    }

    public static function I($demand)
    {
        Logs($demand, __FUNCTION__, __CLASS__);
        $to = self::getCeoEmail();
        $title = "！！！现金效率审批——订单审批需求编号：{$demand['demand_code']}！！！";
        $content = (new DisplayAction())->getCeoEmailContent($demand['id'], self::keyGen(explode('@', $to)[0]));
        self::send($to, $title, $content['ceo'], null, null, 1);
        @self::sendWeChatIOrG($demand, $to, 'ceo_approval');
        $to = self::getCeoEmailCC();
        self::send($to, $title, $content['cc']);
        D('Demand')->save(['id' => $demand['id'], 'ceo_email_send' => time()]);
    }

    public static function sendWeChatIOrG($demand, $send_msg_email, $callback_function)
    {
        $to = self::getCeoEmail();
        $data = (new DisplayAction())->getCeoEmailContent($demand['id'], self::keyGen(explode('@', $to)[0]), true);
        $wx_msg_res = (new ReviewMsgTpl())->sendWeChatSalesLeadershipApprovalOrCeo($data, $send_msg_email, $callback_function);
        Logs([$data, $wx_msg_res], __FUNCTION__, __CLASS__);
    }


    public static function J($demand, $q_list)
    {
        self::J1($demand);
        foreach ($q_list as $q) {
            self::J2($q);
        }
    }

    public static function J1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail($demand['seller']);
            $cc = self::getUserEmail($demand['create_user']);
            $title = 'PO上传提醒 - PO Upload Reminder';
            $content = "您的销售需求需要上传PO<br/>Your sales demand needs to upload PO<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
            self::send($to, $title, $content, $cc);
        }
    }

    public static function J2($q)
    {
        $to = self::getUserEmail($q['purchaser']);
        $cc = self::getUserEmail($q['create_user']);
        $title = 'PO上传提醒 - PO Upload Reminder';
        $content = "您的报价需要上传PO<br/>Your quotation needs to upload PO<br/><br/>报价编号：{$q['quotation_code']}<br/>Quotation No.: {$q['quotation_code']}" . self::getDetailBtn('quotation', $q['id']);
        self::send($to, $title, $content, $cc);
    }


    public static function K($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $cc = self::getLeaderEmail($demand['sell_team']);
        $title = '销售需求退回提醒 - Sales Demand Return Reminder';
        $content = "您的销售需求被现金效率退回。<br/>Your sales demand is returned by the CEO.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
        self::send($to, $title, $content, $cc);
    }


    public static function L($demand, $q_list)
    {
        #self::L1($demand);
        foreach ($q_list as $q) {
            self::L2($q);
        }
    }

    public static function L1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail(TbMsCmnCdModel::getLegalMan($demand['sell_team']));
            $title = '！！！PO审批提醒 - PO Approval Reminder！！！';
            $content = "有销售需求的PO需要您审批<br/>PO with sales demand needs your approval<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
            self::send($to, $title, $content, null, null, 1);
        }
    }

    public static function L2($q)
    {
        $to = self::getUserEmail(TbMsCmnCdModel::getLegalMan($q['purchase_team']));
        $title = '！！！PO审批提醒 - PO Approval Reminder！！！';
        $content = "有采购报价的PO需要您审批<br/>PO with purchase quotation needs your approval<br/><br/>报价编号：{$q['quotation_code']}<br/>Quotation No.: {$q['quotation_code']}" . self::getDetailBtn('quotation', $q['id']);
        self::send($to, $title, $content, null, null, 1);
    }


    public static function M1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail($demand['seller']);
            $cc = self::getUserEmail($demand['create_user']);
            $title = '法务审批失败提醒 - Reminder of Failed Legal Approval';
            $detail_btn = self::getDetailBtn('demand', $demand['id']);
            $content = "您的需求被法务退回<br/>Your demand is returned by legal<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
            self::send($to, $title, $content, $cc);
        }
    }


    public static function M2($quotation)
    {
        $to = self::getUserEmail($quotation['purchaser']);
        $cc = self::getUserEmail($quotation['create_user']);
        $title = '法务审批失败提醒 - Reminder of Failed Legal Approval';
        $detail_btn = self::getDetailBtn('quotation', $quotation['id']);
        $content = "您的报价被法务退回<br/>Your quotation is returned by legal<br/><br/>报价编号：{$quotation['quotation_code']}<br/>Quotation No.: {$quotation['quotation_code']}" . $detail_btn;
        self::send($to, $title, $content, $cc);
    }


    public static function N($demand, $q_list)
    {
        self::N1($demand);
        foreach ($q_list as $q) {
            self::N2($q);
        }
    }

    public static function N1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail(TbMsCmnCdModel::getLegalMan($demand['sell_team']));
            $title = 'PO盖章确认提醒';
            $content = "有销售需求的PO需要您盖章确认<br/>需求编号：{$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
            self::send($to, $title, $content);
        }
    }

    public static function N2($q)
    {
        $to = self::getUserEmail($q['legal_man']);
        $title = 'PO盖章确认提醒';
        $content = "有采购报价的PO需要您盖章确认<br/>报价编号：{$q['quotation_code']}" . self::getDetailBtn('quotation', $q['id']);
        self::send($to, $title, $content);
    }


    public static function O1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail($demand['seller']);
            $title = '法务盖章失败提醒';
            $detail_btn = self::getDetailBtn('demand', $demand['id']);
            $content = "您的需求被法务退回<br/>需求编号：{$demand['demand_code']}" . $detail_btn;
            self::send($to, $title, $content);
        }
    }


    public static function O2($quotation)
    {
        $to = self::getUserEmail($quotation['purchaser']);
        $title = '法务盖章失败提醒';
        $detail_btn = self::getDetailBtn('quotation', $quotation['id']);
        $content = "您的报价被法务退回<br/>报价编号：{$quotation['quotation_code']}" . $detail_btn;
        self::send($to, $title, $content);
    }


    public static function P($demand, $q_list)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail(TbMsCmnCdModel::getLegalMan($demand['sell_team']));
            $title = '！！！PO归档提醒！！！';
            $content = "有销售需求需要您PO归档<br/>需求编号：{$demand['demand_code']}" . self::getDetailBtn('demand', $demand['id']);
            self::send($to, $title, $content, null, null, 1);
        }
        foreach ($q_list as $q) {
            self::P2($q);
        }
    }

    public static function P2($quotation)
    {
        $to = self::getUserEmail($quotation['legal_man']);
        $title = '！！！PO归档提醒！！！';
        $content = "有采购报价需要您PO归档<br/>报价编号：{$quotation['quotation_code']}" . self::getDetailBtn('quotation', $quotation['id']);
        self::send($to, $title, $content, null, null, 1);
    }

    public static function Q1($demand)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail($demand['seller']);
            $title = 'PO归档失败提醒';
            $detail_btn = self::getDetailBtn('demand', $demand['id']);
            $content = "您的需求被法务退回<br/>需求编号：{$demand['demand_code']}" . $detail_btn;
            self::send($to, $title, $content);
        }
    }


    public static function Q2($quotation)
    {
        $to = self::getUserEmail($quotation['purchaser']);
        $title = 'PO归档失败提醒';
        $detail_btn = self::getDetailBtn('quotation', $quotation['id']);
        $content = "您的报价被法务退回<br/>报价编号：{$quotation['quotation_code']}" . $detail_btn;
        self::send($to, $title, $content);
    }


    public static function R($demand)
    {
        //fixdue 囤货采购订单
//        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
        $to = self::getUserEmail($demand['seller']);
        $title = '销售需求订单创建提醒';
        $detail_btn = self::getDetailBtn('demand', $demand['id']);
        $content = "您的销售需求已经全部审批通过，可以创建销售和采购订单啦！<br/>需求编号：{$demand['demand_code']}" . $detail_btn;
        self::send($to, $title, $content);
//        }
    }


    public static function S($demand, $q_list)
    {
        if ($demand['demand_type'] != DemandModel::$demand_type_store) {
            $to = self::getUserEmail($demand['seller']);
            $cc = self::getUserEmail($demand['create_user']);
            $title = 'B2B订单创建成功提醒 - B2B Order Creation Succeed Reminder';
            $detail_btn = self::getDetailBtn('demand', $demand['id'], '查看需求(view demand)');
            $content = "您的销售需求对应的B2B订单已经创建成功！快去查看吧。<br/>The B2B order corresponding to your demand has been created successfully! please go and view it.<br/><br/>需求编号&B2B订单编号：{$demand['demand_code']}<br/>Demand No. & B2B order No.: {$demand['demand_code']}" . $detail_btn . self::getB2BBtn($demand['b2b_order_id']);
            self::send($to, $title, $content, $cc);
        }
        foreach ($q_list as $q) {
            self::S2($q);
        }
    }

    public static function S2($quotation)
    {
        $to = self::getUserEmail($quotation['purchaser']);
        $title = '采购订单创建成功提醒 - Purchase Order Creation Succeed Reminder';
        $content = "您的报价对应的采购订单已经创建成功！快去查看吧。<br/>The purchase order corresponding to your quotation has been created successfully! please go and view it.<br/><br/>报价编号&采购订单编号：{$quotation['quotation_code']}<br/>Quotation No. & purchase order No.: {$quotation['quotation_code']}" . self::getDetailBtn('quotation', $quotation['id'], '查看报价(view quotation)') . self::getPurBtn($quotation['purchase_id']);
        self::send($to, $title, $content);
    }


    public static function T($demand, $q_list, $reason)
    {
        $title = '需求撤回提醒 - Demand Modification Reminder';
        $detail_btn = self::getDetailBtn('demand', $demand['id']);
        foreach ($q_list as $q) {
            $to = self::getUserEmail($q['purchaser']);
            $cc = self::getUserEmail($q['create_user']);
            $content = "您之前认领的销售需求已修改，对应的报价已被清除。<br/>Your previously claimed sales demand has been modified and the corresponding quotation has been cleared.<br/><br/>撤回原因：{$reason}<br/>Reason for withdrawal：{$reason}<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
            self::send($to, $title, $content, $cc);
        }
        switch ($demand['step']) {
            case DemandModel::$step['leader_approve']:
                $content = "需求{$demand['demand_code']}已撤回至草稿<br/>Demand {$demand['demand_code']} has been withdrawn to draft.<br/><br/>撤回原因：{$reason}<br/>Reason for withdrawal：{$reason}<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
                $to = self::getLeaderEmail($demand['sell_team']);
                self::send($to, $title, $content);
                break;
            case DemandModel::$step['ceo_approve'] :
                $content = "需求{$demand['demand_code']}已撤回至草稿<br/>Demand {$demand['demand_code']} has been withdrawn to draft.<br/><br/>撤回原因：{$reason}<br/>Reason for withdrawal：{$reason}<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
                $to = self::getCeoEmail();
                self::send($to, $title, $content);
                break;
            case DemandModel::$step['justice_approve'] :
                $content = "需求{$demand['demand_code']}已撤回至草稿<br/>Demand {$demand['demand_code']} has been withdrawn to draft.<br/><br/>撤回原因：{$reason}<br/>Reason for withdrawal：{$reason}<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
                $to = [];
                foreach ($q_list as $q) {
                    if ($q['chosen'] == QuotationModel::$chosen['chosen']) {
                        $to = self::getJusticeEmail($q['purchase_team']);
                    }
                }
                $to = array_merge($to, self::getJusticeEmail($demand['sell_team']));
                self::send(array_unique($to), $title, $content);
                break;
            default:
                break;
        }
    }


    public static function U($demand, $q_list)
    {
        foreach ($q_list as $q) {
            $to = self::getUserEmail($q['purchaser']);
            $cc = self::getUserEmail($q['create_user']);
            $title = '需求弃单提醒 - Demand Abandonment Reminder';
            $detail_btn = self::getDetailBtn('demand', $demand['id']);
            $content = "您之前认领的销售需求已弃单，对应的报价已被清除。<br/>The sales demand you have claimed previously has been discarded and the corresponding quotation has been cleared.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
            self::send($to, $title, $content, $cc);
        }
    }


    public static function V($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $cc = self::getUserEmail($demand['create_user']);
        $title = '报价申请修改提醒 - Quotation Application Modification Reminder';
        $detail_btn = self::getDetailBtn('demand', $demand['id']);
        $content = "您选择的报价申请修改。<br/>Quotation you select is applied for modification.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
        self::send($to, $title, $content, $cc);
    }


    public static function W($demand)
    {
        $to = self::getUserEmail($demand['seller']);
        $cc = self::getUserEmail($demand['create_user']);
        $title = '报价申请放弃提醒 - Quotation Application for Dropping Reminder';
        $detail_btn = self::getDetailBtn('demand', $demand['id']);
        $content = "您选择的报价申请放弃。<br/>Quotation you select is applied for dropping.<br/><br/>需求编号：{$demand['demand_code']}<br/>Demand No.: {$demand['demand_code']}" . $detail_btn;
        self::send($to, $title, $content, $cc);
    }


    public static function X($q_list)
    {
        foreach ($q_list as $q) {
            $to = self::getUserEmail($q['purchaser']);
            $cc = self::getUserEmail($q['create_user']);
            $title = '同意申请提醒 - Application Approval Reminder';
            $detail_btn = self::getDetailBtn('quotation', $q['id']);
            $content = "您的申请已被同意，目前您的报价已置为草稿状态。<br/>Your application has been approved and your quotation is in draft status currently.<br/><br/>您可以重新修改并提交报价，也可以删除报价。<br/>You can remodify and submit quotation or you can delete it.<br/><br/>销售人员将会根据您的操作结果重新进行选择。<br/>The salesperson will reselect based on the result of your operation.<br/><br/>报价编号：{$q['quotation_code']}<br/>Quotation No.: {$q['quotation_code']}" . $detail_btn;
            self::send($to, $title, $content, $cc);
        }
    }


    public static function Y($q_list)
    {
        foreach ($q_list as $q) {
            $to = self::getUserEmail($q['purchaser']);
            $cc = self::getUserEmail($q['create_user']);
            $title = '申请被拒绝提醒 - Rejected Application Reminder';
            $detail_btn = self::getDetailBtn('quotation', $q['id']);
            $content = "您的申请已被拒绝，如有疑问，请和销售人员线下沟通。<br/>Your application has been rejected. Any question, please contact the salesperson offline.<br/><br/>报价编号：{$q['quotation_code']}<br/>Quotation No.: {$q['quotation_code']}" . $detail_btn;
            self::send($to, $title, $content, $cc);
        }
    }


    public static function Z($quotation)
    {
        $to = self::getPurLeaderEmail($quotation['purchase_team']);
        $title = '采购方案确认提醒';
        $detail_btn = self::getDetailBtn('quotation', $quotation['id']);
        $content = "您的采购团队成员已确认采购方案<br/>报价编号：{$quotation['quotation_code']}" . $detail_btn;
        self::send($to, $title, $content);
    }

    public static function BB($demand)
    {
        $to = self::getCeoEmail();
        $title = "【审批通过】现金效率审批——订单审批需求编号：{$demand['demand_code']}";
        $content = (new DisplayAction())->getCeoEmailContent($demand['id'], '');
        $content = "<br/><b>审批通过</b><br/><br/>——————————————————————————————————————————————————————————————————————<br/>" . $content['cc'];
        self::send($to, $title, $content);
    }

    public static function CC($demand)
    {
        $to = self::getCeoEmail();
        $title = "【审批退回】现金效率审批——订单审批需求编号：{$demand['demand_code']}";
        $content = (new DisplayAction())->getCeoEmailContent($demand['id'], '');
        $content = "<br/><b>审批退回</b><br/><br/>——————————————————————————————————————————————————————————————————————<br/>" . $content['cc'];
        self::send($to, $title, $content);
    }

    public static function justiceAudit($to, $cc, $code, $proposal)
    {
        $title = "法务审核{$code}";
        $content = "您的订单{$code}上传的PO/合同已经通过法务的审核，请尽快打印邮件附件中的PO/合同，双方盖章后，提交给法务归档。<br/>
                  法务审核意见：<br/>
                  {$proposal}";
        self::send($to, $title, $content, $cc);
    }
}