<?php
/**
 * User: yangsu
 * Date: 2019/3/21
 * Time: 15:59
 */

class ReviewMsgTpl
{

    /**
     *
     */
    private function checkSendWeChatCard($data)
    {

    }

    /**
     * @param array $temp_data
     * @param array $description_data
     *
     * @return array|bool
     */
    public function sendWeChatCard(array $temp_data, array $description_data)
    {
        try {
            $this->checkSendWeChatCard($temp_data);
            $init_language = LanguageModel::getCurrent();
            $cards_key_val = TbHrCardModel::getCardWorkPalce($temp_data['send_msg_emails']);
            $ReviewMsg = new ReviewMsg();
           
            foreach ($temp_data['send_msg_emails'] as $value) {
               
                $temp_language = ReviewMsg::getPathToLang($cards_key_val[$value]);
                
                LanguageModel::setCurrent($temp_language);
                foreach ($temp_data['card_msg'] as $key => $datum) {

                    $datum = eval('return ' . $datum . ';');
                    $data_temp[$key] = $datum;
                }
                $temp_msg = [
                    'tousers' => [$value],
                    'textcard' => $data_temp,
                ];
                
                $sends[] = $ReviewMsg->create($temp_data['review'])->send($temp_msg);
            }
            
            LanguageModel::setCurrent($init_language);
        } catch (Exception $exception) {
            $sends = false;
        }
        return $sends;
    }
    public function sendWeChatCardNode(array $temp_data, array $description_data)
    {
        try {
            $this->checkSendWeChatCard($temp_data);
            $init_language = LanguageModel::getCurrent();
            $cards_key_val = TbHrCardModel::getCardWorkPalce($temp_data['send_msg_emails']);
            $ReviewMsg = new ReviewMsg();
            foreach ($temp_data['send_msg_emails'] as $value) {
                $temp_language = ReviewMsg::getPathToLang($cards_key_val[$value]);
                LanguageModel::setCurrent($temp_language);
                foreach ($temp_data['card_msg'] as $key => $datum) {
                    $datum = eval('return ' . $datum . ';');
                    $data_temp[$key] = $datum;
                }
                $temp_msg = [
                    'tousers' => [$value],
                    'textcard' => $data_temp,
                ];
                $sends[] = $ReviewMsg->create($temp_data['review'])->sendNode($temp_msg);
            }
            LanguageModel::setCurrent($init_language);
        } catch (Exception $exception) {
            $sends = false;
        }
        return $sends;
    }
    /**
     * @param $data
     * @param $send_msg_email
     * @param $callback_function
     *
     * @return array|bool
     */
    public function sendWeChatSalesLeadershipApprovalOrCeo($data, $send_msg_email, $callback_function)
    {
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $detail_json = $this->joinSalesLeaderDetail($data, $callback_function);
        $temp_data['review'] = [
            'review_type' => 'SCM',
            'order_id' => $data['demand']['id'],
            'order_no' => $data['demand']['demand_code'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $temp_data['card_msg'] = [
            'title' => 'L(\'销售需求审批\')',
            'description' => '"<div class=\"normal\">" . L("需求编号") . "：".$description_data["demand"]["demand_code"]."</div>
<div class=\"normal\">" . L("需求类型") . "：".$description_data["demand"]["demand_type_val"]."</div>
<div class=\"normal\">" . L("销售团队") . "：".$description_data["demand"]["sell_team_val"]."</div>
<div class=\"normal\">" . L("销售人员") . "：".$description_data["demand"]["seller"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $data);
    }

    /**
     * @name 售后审核推送卡片
     * @param $data
     * @param $send_msg_email
     * @param $callback_function
     *
     * @return array|bool
     */
    public function sendWeChatAfterSaleApproval($data, $send_msg_email, $callback_function)
    {
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $detail_json = $this->joinSalesLeaderDetail($data, $callback_function);
        $temp_data['review'] = [
            'review_type' => 'SCM',
            'after_sale_id' => $data['refund_info']['id'],
            'after_sale_no' => $data['refund_info']['after_sale_no'],
            'order_no' => $data['order_info'][0]['order_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $data["refund_info"]["store_name"] = $data["refund_info"]["store_name"] ? $data["refund_info"]["store_name"] : $data["order_info"][0]["store_name"];
        $temp_data['card_msg'] = [
            'title' => 'L(\'售后退款申请\')',
            'description' => '"<div class=\"normal\">" . L("店铺") . "：".$description_data["refund_info"]["store_name"]."</div>
<div class=\"normal\">" . L("订单号") . "：".$description_data["refund_info"]["order_no"]."</div>
<div class=\"normal\">" . L("售后单号") . "：".$description_data["refund_info"]["after_sale_no"]."</div>
<div class=\"normal\">" . L("售后原因") . "：".$description_data["refund_info"]["refund_reason_cd_val"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $data);
    }

    /**
     * @name 退货入库推送微信卡片消息
     * @param $data
     * @param $send_msg_email
     * @param $callback_function
     *
     * @return array|bool
     */
    public function sendWeChatReturnWarehouseApproval($data, $send_msg_email, $callback_function)
    {
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $detail_json = $this->joinSalesLeaderDetail($data, $callback_function);
        $temp_data['review'] = [
            'review_type' => 'SCM',
            'return_goods_id' => $data['return_info']['id'],
            'after_sale_no' => $data['return_info']['after_sale_no'],
            'order_no' => $data['return_info']['order_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $temp_data['card_msg'] = [
            'title' => 'L(\'售后退货入库通知\')',
            'description' => '"<div class=\"normal\">" . L("店铺") . "：".$description_data["return_info"]["store_name"]."</div>
<div class=\"normal\">" . L("售后单号") . "：".$description_data["return_info"]["after_sale_no"]."</div>
<div class=\"normal\">" . L("退入仓库") . "：".$description_data["return_info"]["warehouse_code_val"]."</div>
<div class=\"normal\">" . L("入库商品") . "：".$description_data["return_info"]["sku_id"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $data);
    }

    /**
     * @param $data
     * @param $callback_function
     *
     * @return mixed
     */
    private function joinSalesLeaderDetail($data, $callback_function)
    {
        $detail_json = $data;
        switch ($detail_json['demand']['is_spot']) {
            case 0:
                $detail_json['ce']['net_profit'] = $detail_json['ce']['net_profit_total'];
                $detail_json['ce']['net_profit_rate'] = $detail_json['ce']['net_profit_rate_total'];
                break;
            case 1:
                $detail_json['ce']['net_profit'] = $detail_json['ce']['net_profit_spot'];
                $detail_json['ce']['net_profit_rate'] = $detail_json['ce']['net_profit_rate_spot'];
                break;
            case 2:
                break;
            case 3:
                break;
        }
        $detail_json['demand']['net_profit'] = $detail_json['ce']['net_profit'];
        $detail_json['demand']['net_profit_rate'] = $detail_json['ce']['net_profit_rate'];


        $detail_json['count_type'] = $callback_function;
        $detail_json['config'] = [
            'view_type' => 'allo',
            'agree_btn' => 1,
            'refuse_btn' => 1,
            'refuse_text' => 1,
            'agree_text' => 0,
            'detail_btn' => 0,
        ];
        return $detail_json;
    }


    /**
     * 资金划转审批
     *
     * @param $data
     * @param $send_msg_email
     * @param $callback_function
     *
     * @return array|bool
     */
    public function sendWeChatTurnoverApproval($data, $send_msg_email, $callback_function)
    {
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $detail_json = $data;
        $detail_json['count_type'] = $callback_function;
        $detail_json['config'] = [
            'view_type' => 'allo',
            'agree_btn' => 1,
            'refuse_btn' => 1,
            'refuse_text' => 1,
            'agree_text' => 1,
            'detail_btn' => 0,
        ];
        $temp_data['review'] = [
            'review_type' => 'FIN',
            'order_id' => $data['base_info']['id'],
            'order_no' => $data['base_info']['transfer_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $temp_data['card_msg'] = [
            'title' => 'L(\'资金划转审批\')',
            'description' => '"<div class=\"normal\">" . L("资金划转编号") . "：".$description_data["base_info"]["transfer_no"]."</div>
<div class=\"normal\">" . L("申请人") . "：".$description_data["base_info"]["create_user_nm"]."</div>
<div class=\"normal\">" . L("申请时间") . "：".$description_data["base_info"]["create_time"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $data);
    }

    /**
     * 一般付款审批
     *
     * @param $data
     * @param $send_msg_email
     * @param $callback_function
     *
     * @return array|bool
     */
    public function sendWeChatGeneralApproval($data, $send_msg_email, $callback_function)
    {
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $detail_json = $data;
        $temp_data['review'] = [
            'review_type' => 'FIN',
            'order_id' => $data['base_info']['id'],
            'order_no' => $data['base_info']['payment_audit_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        // 默认模块
        $description = '"<div class=\"normal\">" . L("付款单号") . "：".$description_data["base_info"]["payment_audit_no"]."</div>
<div class=\"normal\">" . L("来源") . "：".$description_data["base_info"]["source_cd_val"]."</div>
<div class=\"normal\">" . L("付款金额") . "：".$description_data["base_info"]["payable_currency_cd_val"] . " " .$description_data["base_info"]["payable_amount"]."</div>"';
        //  一般付款申请
        if (isset($data['base_info']['source_cd']) && $data['base_info']['source_cd'] == TbPurPaymentAuditModel::$source_general_payable ){
            $description = '"<div class=\"normal\">" . L("付款单号") . "：".$description_data["base_info"]["payment_audit_no"]."</div>
<div class=\"normal\">" . L("来源") . "：".$description_data["base_info"]["source_cd_val"]."</div>
<div class=\"normal\">" . L("付款类型") . "：".$description_data["base_info"]["payment_type_val"]."</div>
<div class=\"normal\">" . L("付款金额") . "：".$description_data["base_info"]["payable_currency_cd_val"] . " " .$description_data["base_info"]["payable_amount"]."</div>
<div class=\"normal\">" . L("实际费用申请人") . "：".$description_data["base_info"]["actual_fee_applicant"]."</div>"';
        }
        $temp_data['card_msg'] = array();
        $temp_data['card_msg'] = [
            'title' => 'L(\'付款申请审批\')',
            'description' => $description,
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $data);
    }

    public function sendWeChatReviewTransfer($data, $send_msg_email, $callback_function = 'new_transfer_approval')
    {
        if (false !== strstr($send_msg_email, ',')) {
            $temp_data['send_msg_emails'] = explode(',', $send_msg_email);
        }
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $AllocationExtendNewAction = new AllocationExtendNewAction();
        $detail_json = $AllocationExtendNewAction->getAlloDetail($data['allo']['id'], true);
        $temp_data['review'] = [
            'review_type' => 'WMS',
            'order_id' => $data['allo']['id'],
            'order_no' => $data['allo']['allo_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $temp_data['card_msg'] = [
            'title' => 'L(\'调拨申请\')',
            'description' => '"<div class=\"normal\">" . L("调拨单号") . "：".$description_data["allo"]["allo_no"]."</div>
<div class=\"normal\">" . L("团队") . "：".$description_data["allo"]["allo_in_team_val"]."</div>
<div class=\"normal\">" . L("仓库") . "：".$description_data["allo"]["warehouse_from_to"]."</div>
<div class=\"normal\">" . L("发起人") . "：".$description_data["allo"]["create_user"]."</div>
<div class=\"normal\">" . L("发起时间") . "：".$description_data["allo"]["create_time"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
       
        return $this->sendWeChatCard($temp_data, $data);
    }
    public function sendWeChatReviewTransferByAttr($data, $send_msg_email, $callback_function = 'new_transfer_approval')
    {
        if (false !== strstr($send_msg_email, ',')) {
            $temp_data['send_msg_emails'] = explode(',', $send_msg_email);
        }
       
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $AllocationExtendNewAction = new AllocationExtendNewAction();
        $detail_json = $AllocationExtendNewAction->getAlloDetail($data['allo']['id'], true);
        $attr = M('wms_allo_attribution', 'tb_')->where(['allo_id'=>$data['allo']['id'],['updated_by'=>['EXP','is null']]])->find();
        $temp_data['review'] = [
            'review_type' => 'WMS',
            'order_id' => $data['allo']['id'],
            'order_no' => $data['allo']['allo_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $to_user = explode('@', $temp_data['send_msg_emails'][0])[0];
        $emplId = M('admin', 'bbm_')->where(['M_NAME' => $to_user])->getField('empl_id');
        $wid = M('hr_empl_wx', 'tb_')->where(['uid'=>$emplId])->getField('wid');
        $name = explode('.', explode('@', $temp_data['send_msg_emails'][0])[0])[0];
        $createName = M('admin', 'bbm_')->where(['M_ID' => $data["allo"]["create_user"]])->getField('M_NAME');
      
        $detail_url = ERP_HOST . '/index.php?' . http_build_query(['m' => 'allocation_extend_new', 'a' => 'allot_check', 'type' => '1', 'id' => $data['allo']['id']]);
        $data = "企业微信通知 
TO $name
当前". $createName . "发起了调拨单，占用了销售团队的公共库存，请尽快前往调拨单进行库存归属变更和调拨的合并审批
调拨单号：". $data["allo"]["allo_no"]. "
调拨发起人：$createName
库存归属变更单号：". $attr["change_order_no"]."
如需查看详情，请点击：[查看详情]($detail_url)";
        $res = ApiModel::WorkWxSendMarkdownMessage($wid, $data);
        
     
    }
    public function sendWeChatReviewTransferNode($data, $send_msg_email,$out_stock_id, $type = 1, $callback_function = 'new_transfer_approval')
    {
        $first_id = M('wms_allo_new_out_stocks', 'tb_')->where(['allo_id' => $data['allo']['id']])->order('id')->find()['id'];
        $allo = M('wms_allo', 'tb_')->where(['id' => $data['allo']['id']])->find();
        $warehouse = $allo['allo_in_warehouse'];
        $email = M('con_division_warehouse', 'tb_')->alias('ware')->join("left join bbm_admin as admin on ware.transfer_warehousing_by = admin.M_NAME")
        ->where(['ware.warehouse_cd' => $warehouse])
            ->getField('M_EMAIL');
        $send_msg_email = $send_msg_email ? $send_msg_email : $email;
        // $send_msg_email = 'zitai.chen@gshopper.com';
        if (false !== strstr($send_msg_email, ',')) {
            $temp_data['send_msg_emails'] = explode(',', $send_msg_email);
        }
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $AllocationExtendNewAction = new AllocationExtendNewAction();
        $detail_json = $AllocationExtendNewAction->getAlloDetail($data['allo']['id'], true);
        $temp_data['review'] = [
            'review_type' => 'WMS',
            'order_id' => $data['allo']['id'],
            'order_no' => $data['allo']['allo_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        if($type == 1){
            $temp_data['card_msg'] = [
                'title' => 'L(\'催办提醒\')',
                'description' => '"<div class=\"normal\">" . L("调拨单号") . "：' . $data['allo']['allo_no'] . '"."</div>
<div>" . L("团队") . "：".$description_data["allo"]["allo_in_team_val"]."</div>
<div>" . L("发起人") . "：' . userName() . '"."    ".L("发起时间") . "：' . date('Y-m-d H:i:s') . '"."</div>
<div>" . L("出库记录") . "：出库记录' . ($first_id - $out_stock_id + 1) . '"."</div>"',
                'btntxt' => 'L(\'查看详情\')',
            ];
        }else{
            $temp_data['card_msg'] = [
                'title' => 'L(\'更新物流提醒\')',
                'description' => '"<div class=\"normal\">" . L("调拨单号") . "：' . $data['allo']['allo_no'] . '"."</div>
<div>" . L("团队") . "：".$description_data["allo"]["allo_in_team_val"]."</div>
<div>" . L("发起人") . "：' . userName() . '"."    ".L("发起时间") . "：' . date('Y-m-d H:i:s') . '"."</div>
<div>" . L("出库记录") . "：有新的出库记录"."</div>"',
                'btntxt' => 'L(\'查看详情\')',
            ];
        }
        
        return $this->sendWeChatCardNode($temp_data, $data);
    }
    public function sendWeChatAttributionTransfer($data, $send_msg_email, $callback_function = 'attribution_transfer_approval')
    {
        $send_msg_email = DataModel::userToEmail($send_msg_email);
        if (false !== strstr($send_msg_email, ',')) {
            $temp_data['send_msg_emails'] = explode(',', $send_msg_email);
        }
        $temp_data['send_msg_emails'] = (array)$send_msg_email;
        $AllocationExtendAttribution = new AllocationExtendAttributionAction();
        $detail_json = $AllocationExtendAttribution->show($data['id'], true);
        $temp_data['review'] = [
            'review_type' => 'WMS',
            'order_id' => $data['id'],
            'order_no' => $detail_json['body']['info']['change_order_no'],
            'allowed_man_json' => (array)DataModel::emailToUser($send_msg_email),
            'detail_json' => $detail_json,
            'callback_function' => $callback_function,
        ];
        $temp_data['card_msg'] = [
            'title' => 'L(\'库存归属变更申请\')',
            'description' => '"<div class=\"normal\">" . L("库存归属变更单号") . "：".$description_data["info"]["change_order_no"]."</div>
<div class=\"normal\">" . L("变更类型") . "：".$description_data["info"]["change_type_cd_val"]."</div>
<div class=\"normal\">" . L("归属销售团队") . "：".$description_data["info"]["attribution_team_cd_val"]."</div>
<div class=\"normal\">" . L("变更前归属") . "：".$description_data["info"]["old_val"]."</div>
<div class=\"normal\">" . L("变更后归属") . "：".$description_data["info"]["new_val"]."</div>
<div class=\"normal\">" . L("发起人") . "：".$description_data["log"]["created_by"]."</div>
<div class=\"normal\">" . L("发起时间") . "：".$description_data["log"]["created_at"]."</div>"',
            'btntxt' => 'L(\'查看详情\')'
        ];
        return $this->sendWeChatCard($temp_data, $detail_json['body']);
    }
}