<?php
// 
// 
// 
// 
// 
// 
// 
// 
// 

/**
 * B2bData 类
 *
 * @category
 * @package
 * @subpackage
 * @author    huaxin
 */
class B2bData
{
    const Submit_default = 0;
    const Submit_draft = 1;
    const Submit_commit = 2;

    public static function getSubmitState($key = null)
    {
        $items = [
            self::Submit_draft => L('草稿'),
            self::Submit_commit => L('已提交'),
        ];
        return DataMain::getItems($items, $key);
    }

    public static function allSubmitState($key = null)
    {
        $items = [
            0 => L('全部'),
            self::Submit_draft => L('草稿'),
            self::Submit_commit => L('已提交'),
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     * @return array
     */
    public static function b2b_code_key($nm_val)
    {
        $code_state = B2bModel::get_code($nm_val);
        $code_state_arr = array_column($code_state, 'CD', 'CD_VAL');
        return $code_state_arr;
    }

    /**
     *  Find one basic order
     *
     */
    static public function oneOrderBasic($order_id)
    {
        $Model = new Model();
        $data = $Model
            ->lock(true)
            ->table('tb_b2b_order')
            ->where(array('ID' => $order_id))
            ->find();
        $data = is_array($data) ? $data : array();
        return $data;
    }

    /**
     *  Find one po of b2b
     *
     */
    static public function fetchOneOrder($order_id)
    {
        $Model = new Model();
        $data = array();
        $list = $Model
            ->table('tb_b2b_order')
            ->where('tb_b2b_order.ID = ' . $order_id)
            ->field('tb_b2b_info.*')
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_order.ID')
            ->select();
        $data = isset($list[0]) ? $list[0] : null;
        $data = is_array($data) ? $data : array();
        return $data;
    }

    /**
     * @param $order_data
     * @return mixed
     */
    static public function mail_data_for_summary($order_data)
    {
        $mail['PO_number'] = $order_data['PO_ID'];
        $mail['Client_name'] = $order_data['CLIENT_NAME'];
        $mail['Client_name_EN'] = TbCrmSpSupplierModel::clientNameToEn($order_data['CLIENT_NAME']);
        $mail['Applicable_contract'] = $order_data['contract'];
        $mail['Our_company'] = $order_data['our_company'];
        $mail['PO_time'] = $order_data['po_time'];
        $mail['Payment_cycle'] = B2bModel::get_code('period')[$order_data['BILLING_CYCLE_STATE'] - 1]['CD_VAL'];
        $node = json_decode($order_data['PAYMENT_NODE'], true);
        foreach ($node as $v) {
            $mail['Payment_node'] .= B2bModel::toNode($v, false) . '&nbsp;';
        }
        $mail['Invoice_tax_point'] = B2bModel::get_code('invioce')[$order_data['INVOICE_CODE']]['CD_VAL'] . '&nbsp;' . B2bModel::get_code('tax_point')[$order_data['TAX_POINT']]['CD_VAL'];
        $mail['Shipping_method'] = B2bModel::get_code_cd('N00153')[$order_data['DELIVERY_METHOD']]['CD_VAL'];
        $mail['Category'] = B2bModel::get_code_y('业务类型')[$order_data['business_type']]['CD_VAL'];
        $mail['Business_Direction'] = B2bModel::get_code_y('业务方向')[$order_data['business_direction']]['CD_VAL'];
        $mail['Target_city'] = B2bModel::join_ares($order_data['TARGET_PORT'], 2); // J2S
        $mail['Address'] = B2bModel::join_ares($order_data['TARGET_PORT'], 3, true);
        $mail['Sales_colleagues'] = $order_data['PO_USER'];
        $mail['Sales_team'] = B2bModel::get_code_y('销售团队')[$order_data['SALES_TEAM']]['CD_VAL'];
        $mail['PO_amount'] = B2bModel::update_currency($order_data['po_currency'], $mail['PO_time']) * $order_data['po_amount'];
        $mail['Expected_tax_refund_value'] = B2bModel::update_currency($order_data['backend_currency'], $mail['PO_time']) * $order_data['drawback_estimate'];
//        update_currency
        $mail['Estimated_cost_goods'] = B2bModel::update_currency($order_data['backend_currency'], $mail['PO_time']) * $order_data['backend_estimat']; // 2C
        $mail['Estimated_logistics_costs'] = B2bModel::update_currency($order_data['logistics_currency'], $mail['PO_time']) * $order_data['logistics_estimat'];

        $mail['Actual_tax_payable'] = $order_data['deducting_tax'] * B2bModel::update_currency($order_data['deducting_tax_currency'], $mail['PO_time']);

        $mail['Estimated_profit'] = round($mail['PO_amount'], 2) + round($mail['Expected_tax_refund_value'], 2) - round($mail['Estimated_cost_goods'], 2) - round($mail['Estimated_logistics_costs'], 2) - round($mail['Actual_tax_payable'], 2);
        $mail['Estimated_profit_margins'] = round(round($mail['Estimated_profit'], 2) / (round($mail['PO_amount'], 2) + round($mail['Expected_tax_refund_value'], 2)), 4) * 100;
        $king_arr = ['PO_amount', 'Expected_tax_refund_value', 'Estimated_cost_goods', 'Estimated_logistics_costs', 'Estimated_profit', 'Actual_tax_payable'];
        $mail = B2bModel::toKingArr($mail, $king_arr);
        $mail['Remarks'] = $order_data['remarks'];
        // trace($mail, '$mail');

        // v2 fields
        $mail['invoice_name'] = D("ZZmscmncd")->getNameFromCode($order_data['INVOICE_CODE']);
        $mail['tax_point'] = D("ZZmscmncd")->getNameFromCode($order_data['TAX_POINT']);

        $forecast = BaseCommon::one_order_calcu_f($order_data);
        $mail['forecast'] = $forecast;

        return $mail;
    }

    /**
     *  Renew by request
     * @param $byReq [ array: index re_type - string , index order_id - int ]
     *
     */
    static public function renew_calcu_tax($byReq)
    {
        $outputs = array();
        // renew type - [ sale_tax , drawback_estimate ]
        $re_type = isset($byReq['re_type']) ? $byReq['re_type'] : null;
        // id
        $order_id = isset($byReq['order_id']) ? $byReq['order_id'] : null;
        if (!$re_type) {
            $outputs['msg'] = 'no re_type';
            return $outputs;
        }
        if (!$order_id) {
            $outputs['msg'] = 'no id';
            return $outputs;
        }
        $poData = B2bData::fetchOneOrder($order_id);
        if (empty($poData)) {
            $outputs['msg'] = 'no data';
            return $outputs;
        }
        $order = $poData;

        $info = array();
        $info['fahuofangshi'] = $order['DELIVERY_METHOD'];
        $info['day'] = $order['po_time'];
        //cur
        $info['backend_currency'] = $order['backend_currency'];
        //sum - 商品成本
        $info['backend_estimat'] = $order['backend_estimat'];
        //cur
        $info['poAmountBz'] = $order['po_currency'];
        //sum - 销售收入
        $info['poAmount'] = $order['po_amount'];
        //tax code
        $info['tax_p_code'] = $order['TAX_POINT'];
        //cur
        $info['cur_tuishui'] = $order['cur_tuishui'];
        //need calculate - 退税
        $info['drawback_estimate'] = BaseCommon::change_tuishui($info);
        //cur
        $info['cur_saletax'] = $order['cur_saletax'];
        //need calculate - 销售端应缴税
        $info['sale_tax'] = BaseCommon::change_saletax($info);
        // var_dump($info);
        if ($re_type == 'sale_tax') {
            $fh_type = BaseCommon::chk_fahuo_type($info['fahuofangshi']);
            if ($fh_type === 1) {
                $where['ORDER_ID'] = $order_id;
                $save = array();
                $save['sale_tax'] = $info['sale_tax'];
                $Model = M();
                $res = $Model->table('tb_b2b_info')->where($where)->save($save);
                $outputs['result'] = $res;
            }
        }
        return $outputs;
    }

    /**
     *  Arrangement sku of po
     *
     */
    static public function sku_get_with_po($sku_get, $poData)
    {
        $is_err = 0;
        $err_msg = '';
        $skuData_arr = array();
        $order_num = 0;

        foreach ($sku_get as $v) {
            $skuData['ORDER_ID'] = $poData['ORDER_ID'];
            if (strlen($v->toskuid) != 10 && strlen($v->skuId) == 10) {
                $skuData['SKU_ID'] = $v->skuId;
                $skuData['sku_show'] = $v->skuId;
            } else {
                $skuData['SKU_ID'] = $v->toskuid;
                $skuData['sku_show'] = $v->toskuid;
            }
            $skuData['price_goods'] = ZFun::keepNumber($v->gudsPrice);
            Logs(ZFun::keepNumber($v->gudsPrice), 'ZFun::keepNumber($v->gudsPrice)');
            Logs($v->gudsPrice, '$v->gudsPrice');
            $order_num += $skuData['TOBE_DELIVERED_NUM'] = $skuData['required_quantity'] = ZFun::keepNumber($v->demand);
//            $skuData['tax_rebate_ratio'] = $v->drawback;
            $skuData['tax_rebate_ratio'] = $v->sku_drawback;
            $skuData['goods_title'] = $v->gudsName;
            $skuData['goods_info'] = $v->skuInfo;
            $skuData['purchasing_team'] = $v->purchasing_team;
            $skuData['introduce_team'] = $v->introduce_team;
            $skuData['currency'] = $poData['po_currency'];
            // percent
            $percent_sale = $v->percent_sale;
            $percent_purchasing = $v->percent_purchasing;
            $percent_introduce = $v->percent_introduce;

            $skuData['batch_id'] = $v->batch_id;
            $skuData['batch_code'] = $v->batch_code;
            $skuData['batch_json'] = $v->batch_json;

            $skuData['delivery_prices'] = json_encode($v->delivery_prices);

            $skuData['purchasing_currency'] = $v->purchasing_currency;
            $skuData['purchasing_price'] = $v->purchasing_price;
            $skuData['purchasing_num'] = $v->purchasing_num;
            $skuData['purchase_invoice_tax_rate'] = $v->purchase_invoice_tax_rate;
            $skuData['procurement_number'] = $v->procurement_number;
            /*$percent_sale = ZFun::roundPriceNum(ZFun::keepNumber($percent_sale));
            $percent_purchasing = ZFun::roundPriceNum(ZFun::keepNumber($percent_purchasing));
            $percent_introduce = ZFun::roundPriceNum(ZFun::keepNumber($percent_introduce));
            if($percent_sale+$percent_sale+$percent_sale!=100){
                $is_err = 1;
                $err_msg = 'percent not 100';
            }*/
            $skuData['percent_sale'] = $percent_sale;
            $skuData['percent_purchasing'] = $percent_purchasing;
            $skuData['percent_introduce'] = $percent_introduce;
            // json desc
            $jdesc = '';
            $jsonArr = array();
            $jsonArr['skuid'] = $skuData['SKU_ID'];
            $jsonArr['price'] = $skuData['price_goods'];
            $jsonArr['num'] = $skuData['required_quantity'];
            $jsonArr['total'] = $jsonArr['price'] * $jsonArr['num'];
            $skuData['jdesc'] = serialize($jsonArr);
            $skuData_arr[] = $skuData;
        }
        $ret = array();
        $ret['is_err'] = $is_err;
        $ret['err_msg'] = $err_msg;
        $ret['skuData_arr'] = $skuData_arr;
        $ret['order_num'] = $order_num;
        return $ret;
    }

    /**
     *  Add new po order by poData
     * @param array $poData
     * @return array ([id=>..],[data=>..])
     */
    static public function add_new_po_order($poData)
    {
        $poData['poNum'] = isset($poData['poNum']) ? $poData['poNum'] : null;
        $poData['submit_state'] = isset($poData['submit_state']) ? $poData['submit_state'] : null;
        $poData['submit_state'] = intval($poData['submit_state']);
        $poData['submit_state'] = array_key_exists($poData['submit_state'], self::getSubmitState()) !== false ? $poData['submit_state'] : self::Submit_draft;
        $newPoData = array();
        $newPoData['PO_ID'] = $poData['poNum'];
        $newPoData['submit_state'] = $poData['submit_state'];
        $newPoData['create_time'] = date('Y-m-d H:i:s');
        $newPoData['create_user'] = session('m_loginname');
        $Models = new Model();
        $order_id = $Models->table('tb_b2b_order')->data($newPoData)->add();
        $newPoData['ID'] = $order_id;
        $ret = array();
        $ret['order_id'] = $order_id;
        $ret['order_data'] = $newPoData;
        return $ret;
    }

    /**
     *  Edit po order by poData
     * @param
     * @return
     */
    static public function edit_po_order($poData, $edit_id)
    {
        $poDataEdit = $poData;
        if (isset($poDataEdit['poNum'])) {
            $poDataEdit['PO_ID'] = $poDataEdit['poNum'];
        }
        $poDataEdit['update_user'] = session('m_loginname');
        $poDataEdit['update_time'] = date('Y-m-d H:i:s');
        $poDataEdit = DataMain::fieldData($poDataEdit, 'tb_b2b_order');
        $Models = new Model();
        $status = $Models->table('tb_b2b_order')->where(array('ID' => $edit_id))->data($poDataEdit)->save();
        $ret = array();
        $ret['status'] = $status;
        $ret['order_id'] = $edit_id;
        $ret['order_data'] = null;
        return $ret;
    }

    /**
     *  Add po order profit by poData
     * @param array $poData
     * @return array
     */
    static public function add_po_profit($poData)
    {
        $profit = array();
        $profit['ORDER_ID'] = $poData['ID'];
        $Models = new Model();
        $tmp_id = $Models->table('tb_b2b_profit')->data($profit)->add();
        $ret = array();
        $ret['id'] = $tmp_id;
        $ret['data'] = $profit;
        return $ret;
    }

    /**
     *  Add po order info by poData or edit data
     * @param array     $poData
     * @param array|obj $poData_get
     * @return array
     */
    static public function add_po_info($poData, $poData_get)
    {
        $is_err = 0;
        $err_msg = '';
        $ret = array();
        $ret['id'] = null;
        $ret['data'] = null;
        $ret['is_err'] = $is_err;
        $ret['err_msg'] = $err_msg;

        if (is_array($poData_get)) {
            $poData_get = json_decode(json_encode($poData_get));
        }

        // check add or edit
        $edit_id = isset($poData_get->edit_id) ? $poData_get->edit_id : null;

        $poData['ORDER_ID'] = !empty($poData['ORDER_ID']) ? $poData['ORDER_ID'] : $poData['ID'];
        if (isset($poData['ID'])) {
            unset($poData['ID']);
        }
        if (!empty($poData_get->poNum)) {
            $poData['PO_ID'] = $poData_get->poNum;
        }
        // var_dump($poData); die();
        $poData['CLIENT_NAME'] = $poData_get->clientName;
        /*if ($poData_get->clientNameEn) {
            $poData['CLIENT_NAME_EN'] = $poData_get->clientNameEn[0];
        } else {
            $where_sp_name['SP_NAME'] = $poData_get->clientName;
            if (empty($Models)) {
                $Models = M();
            }
        }*/
        $poData['CLIENT_NAME_EN'] = TbCrmSpSupplierModel::clientNameToEn($poData_get->clientName);
        $poData['Business_License_No'] = $poData_get->busLice;
        $poData['other_income'] = ZFun::keepNumber($poData_get->otherIncome);

        $poData['contract'] = $poData_get->contract;
        $poData['our_company'] = $poData_get->ourCompany;
        $poData['SALES_TEAM'] = $poData_get->SALES_TEAM;
        $poData['PO_USER'] = $poData_get->lastname;
        // if (empty($poData['PO_USER'])) {
        //     $is_err = 1;
        //     $err_msg = '新增失败,PO信息未填写完全销售同事';
        //     $ret['is_err'] = $is_err;
        //     $ret['err_msg'] = $err_msg;
        //     return $ret;
        // }
        if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $poData['PO_USER'])) {
            if (count(B2bModel::get_user($poData['PO_USER'])) == 0) {
                $is_err = 1;
                $err_msg = L('新增失败,销售同事填写信息不存在ERP中');
                $ret['is_err'] = $is_err;
                $ret['err_msg'] = $err_msg;
                return $ret;
            }
        }
        $poData['po_amount'] = ZFun::keepNumber($poData_get->poAmount);
        $poData['PO_FILFE_PATH'] = $poData_get->IMAGEFILENAME;
        $poData['po_erp_path'] = $poData_get->po_erp_path;

        $poData['po_currency'] = $poData_get->BZ;
        $poData['po_time'] = $poData_get->poTime;

        $poData['INVOICE_CODE'] = $poData_get->invioce;
        $poData['TAX_POINT'] = $poData_get->tax_point;
        $poData['DELIVERY_METHOD'] = $poData_get->shipping;

        $poData['BILLING_CYCLE_STATE'] = $poData_get->cycleNum;
        $poData['PAYMENT_NODE'] = json_encode($poData_get->poPaymentNode);
        if (!empty($poData_get->poPaymentNode_edit)) {
            $poData['PAYMENT_NODE'] = json_encode($poData_get->poPaymentNode_edit);
        }
        $poData['TARGET_PORT'] = array();
        $poData['TARGET_PORT']['targetCity'] = $poData_get->detailAdd;
        $poData['TARGET_PORT']['country'] = $poData_get->country;
        $poData['business_type'] = $poData_get->business_type;
        $poData['business_direction'] = $poData_get->business_direction;
        $poData['TARGET_PORT']['city'] = $poData_get->city;
        $poData['TARGET_PORT']['province'] = $poData_get->province;
        $poData['TARGET_PORT']['stareet'] = $poData_get->province;
        $poData['TARGET_PORT'] = json_encode($poData['TARGET_PORT']);

        $poData['SALES_TEAM'] = $poData_get->saleTeam;

        $poData['remarks'] = $poData_get->remarks;
        $poData['tax_rebate_income'] = ZFun::keepNumber($poData_get->tax_rebate_income);

        $poData['backend_currency'] = $poData_get->backend_currency;
        $poData['backend_estimat'] = ZFun::keepNumber($poData_get->backend_estimat);
        $poData['logistics_currency'] = $poData_get->logistics_currency;
        $poData['logistics_estimat'] = ZFun::keepNumber($poData_get->logistics_estimat);
        $poData['drawback_estimate'] = ZFun::keepNumber($poData_get->drawback_estimate);

        if ($poData_get->deducting_tax && $poData_get->side_taxed) {
            $poData['deducting_tax_currency'] = $poData['po_currency'];
            $poData['side_taxed_currency'] = $poData_get->side_taxed_currency;
            $poData['deducting_tax'] = ZFun::keepNumber($poData_get->deducting_tax);
            $poData['side_taxed'] = ZFun::keepNumber($poData_get->side_taxed);
        }

        // check more info
        $poData = BaseCommon::checkPo($poData, $poData_get);
        $poData = DataMain::fieldData($poData, 'tb_b2b_info');
        $Models = new Model();
        if (empty($edit_id)) {
            $info_id = $Models->table('tb_b2b_info')->data($poData)->add();
        } else {
            $info_id = $edit_id;
            $status = $Models->table('tb_b2b_info')->where(array('ORDER_ID' => $edit_id))->data($poData)->save();
        }

        if (!$info_id) {
            $is_err = 1;
            $err_msg = L('新增失败,PO数据异常-信息重复');
            $ret['is_err'] = $is_err;
            $ret['err_msg'] = $err_msg;
            return $ret;
        }

        $ret['id'] = $info_id;
        $ret['data'] = $poData;
        return $ret;
    }

    /**
     *  Check need data of po
     * @param
     * @param
     * @return array
     */
    static public function po_info_need_check($oneData, $order_base = array())
    {
        $ret = array();
        $ret['is_err'] = 0;
        $ret['err_msg'] = '';
        $ret['data'] = null;
        $is_err = 0;
        $err_msg = '';
        $poPaymentNode = isset($oneData['poPaymentNode']) ? $oneData['poPaymentNode'] : null;
        $poPaymentNode = $poPaymentNode ? $poPaymentNode : (isset($oneData['poPaymentNode_edit']) ? $oneData['poPaymentNode_edit'] : null);
        $per_count = 0;
        if ($poPaymentNode) {
            foreach ($poPaymentNode as $k => $v) {
                $per_count += $v['nodeProp'];
            }
        }
        if (!$is_err) {
            if ($per_count != 100) {
                $is_err = 1;
                $err_msg = L('Wrong-收款节点');
            }
        }
        // check necessary
        $chk_info = ['poNum', 'cycleNum', 'invioce', 'tax_point', 'shipping', 'country', 'province', 'detailAdd', 'saleTeam', 'backend_estimat', 'backend_currency', 'lastname', 'BZ', 'contract', 'clientName', 'ourCompany', 'poTime'];
        $chk_info[] = 'IMAGEFILENAME';
        $chk_info[] = 'drawback_estimate';
        $chk_info[] = 'sale_tax';
        $chk_info[] = 'business_type';
        $chk_info[] = 'poAmount';
        $num_info = ['drawback_estimate', 'otherIncome', 'sale_tax', 'logistics_estimat'];
        if (!$is_err) {
            foreach ($chk_info as $k => $v) {
                $is_ok = 0;
                $value = isset($oneData[$v]) ? $oneData[$v] : null;
                if (!empty($value)) {
                    $is_ok = 1;
                }
                if (!$is_ok) {
                    if (isset($value)) {
                        if (in_array($v, $num_info)) {
                            if ($value === '0' or $value === 0) {
                                $is_ok = 1;
                            }
                        }
                    }
                }
                if (!$is_ok) {
                    $is_err = 1;
                    $err_msg = L('Wrong') . ':' . $v;
                }
            }
        }

        $ret['data'] = $oneData;
        $ret['is_err'] = $is_err;
        $ret['err_msg'] = $err_msg;
        return $ret;
    }

    /**
     *  Add po order receipt by poData
     * @param
     * @param
     * @return array
     */
    static public function add_po_receipt($poData, $poData_get)
    {
        if (is_array($poData_get)) {
            $poData_get = json_decode(json_encode($poData_get));
        }

        $Models = new Model();

        // check add or edit
        $edit_id = isset($poData_get->edit_id) ? $poData_get->edit_id : null;
        if ($edit_id) {
            $status = $Models->table('tb_b2b_receipt')->where(array('ORDER_ID' => $edit_id))->delete();
        }

        $receiptData['PO_ID'] = $poData['PO_ID'];

        $receiptData['ORDER_ID'] = $poData['ORDER_ID'];

        $receiptData['client_id'] = $poData['CLIENT_NAME'];

        $receiptData['sales_team_id'] = $poData['SALES_TEAM'];

        $receiptData['estimated_amount'] = $poData['po_amount'];

        $receiptData['invoice_type'] = $poData['INVOICE_CODE'];
        $receiptData['tax_point'] = $poData['TAX_POINT'];

        $receiptData['payment_account_type'] = $poData['BILLING_CYCLE_STATE'];

        $receiptData['sales_team_id'] = $poData['SALES_TEAM'];

        if ($poData_get->deducting_tax && $poData_get->side_taxed) {
            $receiptData['deducting_tax_currency'] = $poData['deducting_tax_currency'];
            $receiptData['side_taxed_currency'] = $poData['side_taxed_currency'];
            $receiptData['deducting_tax'] = $poData['deducting_tax'];
            $receiptData['side_taxed'] = $poData['side_taxed'];
        }

        // todo

        $payment_node = json_decode($poData['PAYMENT_NODE'], true);
        // 组装节点和比例
        if ($poData['BILLING_CYCLE_STATE'] == 4) {
            $receiptData['receiving_code'] = json_encode($payment_node[0]);
            $receiptData['overdue_statue'] = 0;
            // 计算帐期金额
            $receiptData['expect_receipt_amount'] = $payment_node[0]['nodeProp'] / 100 * $poData['po_amount'];
            $receipt_id = $Models->table('tb_b2b_receipt')->data($receiptData)->add();
        } else {
            for ($i = 0; $i < $poData['BILLING_CYCLE_STATE']; $i++) {

                $receiptData['receiving_code'] = json_encode($payment_node[$i]);
                $receiptData['overdue_statue'] = 0;
                // 计算帐期金额
                $receiptData['expect_receipt_amount'] = $payment_node[$i]['nodeProp'] / 100 * $poData['po_amount'];
                $receipt_id = $Models->table('tb_b2b_receipt')->data($receiptData)->add();
            }
        }
        // 增加退税
        if ($poData['tax_rebate_income'] > 0) {
            $receipt_taxes = $receiptData;
            unset($receipt_taxes['receiving_code']);
            unset($receipt_taxes['estimated_amount']);
            $transaction_type = self::b2b_code_key('transaction_type');
            $receipt_taxes['transaction_type'] = $transaction_type['退税'];
            $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['tax_rebate_income'];
            $receipt_id = $Models->table('tb_b2b_receipt')->data($receipt_taxes)->add();
        }
        if ($poData['drawback_estimate'] > 0) {
            $receipt_taxes = $receiptData;
            unset($receipt_taxes['receiving_code']);
            unset($receipt_taxes['estimated_amount']);
            $transaction_type = self::b2b_code_key('transaction_type');
            $receipt_taxes['transaction_type'] = $transaction_type['退税'];
            $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['drawback_estimate'];
            $receipt_id = $Models->table('tb_b2b_receipt')->data($receipt_taxes)->add();
        }

    }

    /**
     *  Edit po order receipt
     * @param
     * @param
     * @return array
     */
    static public function edit_po_receipt($poData, $poData_get)
    {
        $ret = array();
        $ret['data'] = array();
        $ret['status'] = 0;
        if (is_array($poData_get)) {
            $poData_get = json_decode(json_encode($poData_get));
        }
        $Models = new Model();
        // check add or edit
        $edit_id = isset($poData_get->edit_id) ? $poData_get->edit_id : null;
        if (!$edit_id) {
            return $ret;
        }

        // select exists
        $wheres = array();
        $wheres['ORDER_ID'] = $edit_id;
        $orders = array();
        $orders['ID'] = 'asc';
        $exist_list = $Models->table('tb_b2b_receipt')->where($wheres)->order($orders)->select();

        // sum edit list
        $receiptData = array();
        $receiptData['PO_ID'] = $poData['PO_ID'];
        $receiptData['ORDER_ID'] = $poData['ORDER_ID'];
        $receiptData['client_id'] = $poData['CLIENT_NAME'];
        $receiptData['sales_team_id'] = $poData['SALES_TEAM'];
        $receiptData['estimated_amount'] = $poData['po_amount'];
        $receiptData['invoice_type'] = $poData['INVOICE_CODE'];
        $receiptData['tax_point'] = $poData['TAX_POINT'];
        $receiptData['payment_account_type'] = $poData['BILLING_CYCLE_STATE'];
        $receiptData['sales_team_id'] = $poData['SALES_TEAM'];
        if ($poData_get->deducting_tax && $poData_get->side_taxed) {
            $receiptData['deducting_tax_currency'] = $poData['deducting_tax_currency'];
            $receiptData['side_taxed_currency'] = $poData['side_taxed_currency'];
            $receiptData['deducting_tax'] = $poData['deducting_tax'];
            $receiptData['side_taxed'] = $poData['side_taxed'];
        }
        $take_receipt_arr = array();
        $payment_node = json_decode($poData['PAYMENT_NODE'], true);
        // 组装节点和比例
        if ($poData['BILLING_CYCLE_STATE'] == 4) {
            $receiptData['receiving_code'] = json_encode($payment_node[0]);
            $receiptData['overdue_statue'] = 0;
            // 计算帐期金额
            $receiptData['expect_receipt_amount'] = $payment_node[0]['nodeProp'] / 100 * $poData['po_amount'];
            $receiptData['transaction_type'] = NULL;
            $take_receipt_arr[] = $receiptData;
        } else {
            for ($i = 0; $i < $poData['BILLING_CYCLE_STATE']; $i++) {
                $receiptData['receiving_code'] = json_encode($payment_node[$i]);
                $receiptData['overdue_statue'] = 0;
                // 计算帐期金额
                $receiptData['expect_receipt_amount'] = $payment_node[$i]['nodeProp'] / 100 * $poData['po_amount'];
                $receiptData['transaction_type'] = NULL;
                $take_receipt_arr[] = $receiptData;
            }
        }
        // 增加退税
        if ($poData['tax_rebate_income'] > 0) {
            $receipt_taxes = $receiptData;
            unset($receipt_taxes['receiving_code']);
            unset($receipt_taxes['estimated_amount']);
            $transaction_type = self::b2b_code_key('transaction_type');
            $receipt_taxes['transaction_type'] = $transaction_type['退税'];
            $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['tax_rebate_income'];
            $take_receipt_arr[] = $receipt_taxes;
        }
        if ($poData['drawback_estimate'] > 0) {
            $receipt_taxes = $receiptData;
            unset($receipt_taxes['receiving_code']);
            unset($receipt_taxes['estimated_amount']);
            $transaction_type = self::b2b_code_key('transaction_type');
            $receipt_taxes['transaction_type'] = $transaction_type['退税'];
            $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['drawback_estimate'];
            $take_receipt_arr[] = $receipt_taxes;
        }

        if ($take_receipt_arr) {
            $i = 0;
            foreach ($take_receipt_arr as $key => $val) {
                $one = isset($exist_list[$i]) ? $exist_list[$i] : null;
                // $is_edit = $one!==null?true:false;
                $edit_to_id = isset($one['ID']) ? $one['ID'] : null;
                if ($edit_to_id) {
                    $receipt_id = $Models->table('tb_b2b_receipt')->where(array('ID' => $edit_to_id))->data($val)->save();
                } else {
                    $receipt_id = $Models->table('tb_b2b_receipt')->data($val)->add();
                }
                unset($exist_list[$i]);
                $ret['data'][] = $receipt_id;
                ++$i;
            }
            foreach ($exist_list as $key => $val) {
                $edit_to_id = isset($val['ID']) ? $val['ID'] : null;
                $tmp_id = $Models->table('tb_b2b_receipt')->where(array('ID' => $edit_to_id))->delete();
            }
        }

        return $ret;
    }

    /**
     *  Add po order goods sku by poData
     * @param
     * @param
     * @return array
     */
    static public function add_po_goods_skus($poData, $skuData_get)
    {
        $is_err = 0;
        $err_msg = '';
        $ret = array();
        $ret['id'] = null;
        $ret['data'] = null;
        $ret['is_err'] = $is_err;
        $ret['err_msg'] = $err_msg;

        $Models = new Model();

        if (is_array($poData_get)) {
            $poData_get = json_decode(json_encode($poData_get));
        }
        if (is_array($skuData_get)) {
            $skuData_get = json_decode(json_encode($skuData_get));
        }

        if ($poData['ORDER_ID']) {
            $status = $Models->table('tb_b2b_goods')->where(array('ORDER_ID' => $poData['ORDER_ID']))->delete();
        }
        $skuData_arr = array();
        $order_num = null;
        $sku_add = null;
        if ($poData['ORDER_ID']) {
            $sku_get = $skuData_get;
            $tmp = B2bData::sku_get_with_po($sku_get, $poData);
            $skuData_arr = $tmp['skuData_arr'];
            $order_num = $tmp['order_num'];
            $sku_add = $Models->table('tb_b2b_goods')->addAll($skuData_arr);
            // 发货
            // $do_status = 1;
            if ($sku_add) {
                $res['code'] = 200;
                $res['info'] = L('新增成功');
                // 预期时间
                // ...
            } else {
                $is_err = 1;
                $err_msg = L('新增失败，商品或PO数据异常');
                $ret['is_err'] = $is_err;
                $ret['err_msg'] = $err_msg;
                return $ret;
            }
        } else {
            $is_err = 1;
            $err_msg = L('新增失败,PO数据异常-重复');
            $ret['is_err'] = $is_err;
            $ret['err_msg'] = $err_msg;
            return $ret;
        }

        $ret['skuData_arr'] = $skuData_arr;
        $ret['order_num'] = $order_num;
        $ret['sku_add'] = $sku_add;
        return $ret;
    }

    /**
     *  Edit po order goods sku by poData
     * @param
     * @param
     * @return array
     */
    static public function edit_po_goods_skus($poData, $skuData_get)
    {
        $is_err = 0;
        $err_msg = '';
        $ret = array();
        $ret['id'] = null;
        $ret['data'] = null;
        $ret['is_err'] = $is_err;
        $ret['err_msg'] = $err_msg;

        if (is_array($skuData_get)) {
            $skuData_get = json_decode(json_encode($skuData_get));
        }

        $Models = new Model();
        $edit_id = isset($poData['ORDER_ID']) ? $poData['ORDER_ID'] : null;
        // select exists
        $wheres = array();
        $wheres['ORDER_ID'] = $edit_id;
        $orders = array();
        $orders['ID'] = 'asc';
        $exist_list = $Models->table('tb_b2b_goods')->where($wheres)->order($orders)->select();

        $skuData_arr = array();
        $order_num = null;
        $sku_add = null;
        if ($poData['ORDER_ID']) {
            $sku_get = $skuData_get;
            $tmp = B2bData::sku_get_with_po($sku_get, $poData);
            $skuData_arr = $tmp['skuData_arr'];
            $order_num = $tmp['order_num'];
            // check save sku list
            $sku_add = false;
            if ($skuData_arr) {
                $i = 0;
                foreach ($skuData_arr as $key => $val) {
                    $one = isset($exist_list[$i]) ? $exist_list[$i] : null;
                    $edit_to_id = isset($one['ID']) ? $one['ID'] : null;
                    if ($edit_to_id) {
                        $tmp_id = $Models->table('tb_b2b_goods')->where(array('ID' => $edit_to_id))->data($val)->save();
                    } else {
                        $tmp_id = $Models->table('tb_b2b_goods')->data($val)->add();
                    }
                    unset($exist_list[$i]);
                    $sku_add = true;
                    ++$i;
                }
                foreach ($exist_list as $key => $val) {
                    $edit_to_id = isset($val['ID']) ? $val['ID'] : null;
                    $tmp_id = $Models->table('tb_b2b_goods')->where(array('ID' => $edit_to_id))->delete();
                }
            }
            if ($sku_add) {
                $res['code'] = 200;
                $res['info'] = L('新增成功');
            } else {
                $is_err = 1;
                $err_msg = L('新增失败，商品或PO数据异常');
                $ret['is_err'] = $is_err;
                $ret['err_msg'] = $err_msg;
                return $ret;
            }
        } else {
            $is_err = 1;
            $err_msg = L('新增失败,PO数据异常-重复');
            $ret['is_err'] = $is_err;
            $ret['err_msg'] = $err_msg;
            return $ret;
        }

        $ret['skuData_arr'] = $skuData_arr;
        $ret['order_num'] = $order_num;
        $ret['sku_add'] = $sku_add;
        return $ret;
    }

    /**
     *  Del po about relation and back
     * @param
     * @param
     * @return
     */
    static public function del_po_about($order_id)
    {
        $Models = new Model();
        $ship_id = $Models->table('tb_b2b_ship_list')->where('order_id = ' . $order_id)->getField('ID');
        $ship_id = intval($ship_id);
        $mark_arr = array();
        $table_arr = [
            'tb_b2b_doship',
            'tb_b2b_goods',
            'tb_b2b_info',
            'tb_b2b_log',
            'tb_b2b_profit',
            'tb_b2b_receipt',
            'tb_b2b_warehouse_list'
        ];
        $mark_arr['tb_b2b_ship_goods'] = $Models->table('tb_b2b_ship_goods')->where('SHIP_ID = ' . $ship_id)->select();
        $mark_arr['tb_b2b_warehousing_goods'] = $Models->table('tb_b2b_warehousing_goods')->where('SHIP_ID = ' . $ship_id)->select();
        foreach ($table_arr as $v) {
            $table = $v;
            $where_o = array();
            $where_o['ORDER_ID'] = $order_id;
            $mark_arr[$table] = $Models->table($table)->where($where_o)->select();
        }
        $mark_arr['tb_b2b_order'] = $Models->table('tb_b2b_order')->where('ID = ' . $order_id)->select();
        $mark_arr['tb_b2b_ship_list'] = $Models->table('tb_b2b_ship_list')->where('order_id = ' . $order_id)->select();
        // back mark_arr
        $save_data = array();
        $save_data['ORDER_ID'] = $order_id;
        $save_data['bk_info'] = serialize($mark_arr);
        $save_data['add_time'] = time();
        $tmp = $Models->table('tb_b2b_history_order')->data($save_data)->add();

        // do delete
        $mark_arr = array();
        $mark_arr['tb_b2b_ship_goods'] = $Models->table('tb_b2b_ship_goods')->where('SHIP_ID = ' . $ship_id)->delete();
        $mark_arr['tb_b2b_warehousing_goods'] = $Models->table('tb_b2b_warehousing_goods')->where('SHIP_ID = ' . $ship_id)->delete();
        foreach ($table_arr as $v) {
            $table = $v;
            $where_o = array();
            $where_o['ORDER_ID'] = $order_id;
            $mark_arr[$table] = $Models->table($table)->where($where_o)->delete();
        }
        $mark_arr['tb_b2b_order'] = $Models->table('tb_b2b_order')->where('ID = ' . $order_id)->delete();
        $mark_arr['tb_b2b_ship_list'] = $Models->table('tb_b2b_ship_list')->where('order_id = ' . $order_id)->delete();


        return $mark_arr;
    }

    /**
     *  Check publish whether it can be changed
     *
     *
     * @return
     */
    static public function gain_publish_change($order_id)
    {
        $check = self::gain_receipt_status($order_id);
        $ret = 0;
        if ($check['is_shipped'] == 0 and $check['is_receipts'] == 0 and $check['is_tax'] == 0) {
            $ret = 1;
        }
        return $ret;
    }

    /**
     *  Check receipt status
     * @param
     *
     * @return
     */
    static public function gain_receipt_status($order_id)
    {
        $ret = array();
        $ret['already_receipts'] = 0;
        $ret['partial_receipts'] = 0;
        $ret['already_tax'] = 0;
        $ret['partial_tax'] = 0;
        $ret['already_shipped'] = 0;
        $ret['partial_shipped'] = 0;
        $Models = new Model();
        // select sum
        $wheres = array();
        $wheres['ID'] = $order_id;
        $infos = $Models->table('tb_b2b_order')->field("
            sum( if(receipt_state=2,'1','0') ) AS already_receipts
            , sum( if(receipt_state=1,'1','0') ) AS partial_receipts
            , sum( if(tax_rebate_state=2,'1','0') ) AS already_tax
            , sum( if(tax_rebate_state=1,'1','0') ) AS partial_tax
            , sum( if(order_state=2,'1','0') ) AS already_shipped
            , sum( if(order_state=1,'1','0') ) AS partial_shipped
        ")->where($wheres)->find();
        $ret['already_shipped'] = $infos['already_shipped'];
        $ret['partial_shipped'] = $infos['partial_shipped'];
        $ret['already_receipts'] = $infos['already_receipts'];
        $ret['partial_receipts'] = $infos['partial_receipts'];
        $ret['already_tax'] = $infos['already_tax'];
        $ret['partial_tax'] = $infos['partial_tax'];
        // state-发货
        $ret['is_shipped'] = ($ret['already_shipped'] || $ret['partial_shipped']) ? 1 : 0;
        // state-收款
        $ret['is_receipts'] = ($ret['already_receipts'] || $ret['partial_receipts']) ? 1 : 0;
        // state-退税
        $ret['is_tax'] = ($ret['already_tax'] || $ret['partial_tax']) ? 1 : 0;
        return $ret;
    }

    /**
     * @param $order_id_arr
     * @return array
     */
    static public function gain_receipt_status_arr($order_id_arr)
    {
        $ret = array();
        $ret['already_receipts'] = 0;
        $ret['partial_receipts'] = 0;
        $ret['already_tax'] = 0;
        $ret['partial_tax'] = 0;
        $ret['already_shipped'] = 0;
        $ret['partial_shipped'] = 0;
        $Models = new Model();
        // select sum
        $wheres = array();
        $wheres['ID'] = array('IN', $order_id_arr);
        $infos_arr = $Models->table('tb_b2b_order')->field("
            sum( if(receipt_state=2,'1','0') ) AS already_receipts
            , sum( if(receipt_state=1,'1','0') ) AS partial_receipts
            , sum( if(tax_rebate_state=2,'1','0') ) AS already_tax
            , sum( if(tax_rebate_state=1,'1','0') ) AS partial_tax
            , sum( if(order_state=2,'1','0') ) AS already_shipped
            , sum( if(order_state=1,'1','0') ) AS partial_shipped
            ,ID
        ")->where($wheres)->select();
        foreach ($infos_arr as $infos) {
            $ret['already_shipped'] = $infos['already_shipped'];
            $ret['partial_shipped'] = $infos['partial_shipped'];
            $ret['already_receipts'] = $infos['already_receipts'];
            $ret['partial_receipts'] = $infos['partial_receipts'];
            $ret['already_tax'] = $infos['already_tax'];
            $ret['partial_tax'] = $infos['partial_tax'];
            // state-发货
            $ret['is_shipped'] = ($ret['already_shipped'] || $ret['partial_shipped']) ? 1 : 0;
            // state-收款
            $ret['is_receipts'] = ($ret['already_receipts'] || $ret['partial_receipts']) ? 1 : 0;
            // state-退税
            $ret['is_tax'] = ($ret['already_tax'] || $ret['partial_tax']) ? 1 : 0;
            $ret_arr[$infos['ID']] = $ret;
        }

        return $ret_arr;
    }

    /**
     * Add doship
     * @param $o    poData
     * @param $g    goods_arr
     * @param $sum  order_num
     */
    static public function do_add_doship($poData, $goods_arr, $sum)
    {
        $o = $poData;
        $obj = A('Home/B2b');
        $Models = new Model();
        $status = $Models->table('tb_b2b_doship')->where(array('ORDER_ID' => $o['ORDER_ID']))->delete();

        $data['ORDER_ID'] = $o['ORDER_ID'];
        $data['PO_ID'] = $o['PO_ID'];
        $data['CLIENT_NAME'] = $o['CLIENT_NAME'];
        $data['delivery_warehouse_code'] = $obj->join_ware_arr_code($o['ORDER_ID']);
        $data['target_port'] = $o['TARGET_PORT'];

        $data['todo_sent_num'] = $data['order_num'] = $sum;
        $data['sent_num'] = 0;

        $data['order_date'] = $o['po_time']; // po time to order date
        $data['sales_team_code'] = $o['SALES_TEAM'];
        // 发货状态
        $data['shipping_status'] = 0;
        $Models = new Model();
        $doship_id = $Models->table('tb_b2b_doship')->data($data)->add();

        return $doship_id;
    }


}



