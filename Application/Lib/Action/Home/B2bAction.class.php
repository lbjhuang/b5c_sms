<?php

/**
 * User: yangsu
 * Date: 17/5/25
 * Time: 13:33
 * Editor: huaxin
 */
import("@.ToolClass.B2b.BaseCommon");
import("@.ToolClass.B2b.B2bData");
import("@.ToolClass.B2b.B2bSearch");

class B2bAction extends BaseAction
{
    /**
     * @var string
     */
    public $error_message = '';
    /**
     * @var int
     */
    public $code = 0;
    /**
     * @var null
     */
    public $Model = null;
    /**
     * @var array
     */
    public $mail = [];
    /**
     * @var string
     */
    protected $pk = 'ID';
    /**
     * @var string
     */
    private $url = 'ng-public/index';
    /**
     * @var
     */
    private $server;

    /**
     * @var array
     */
    private $returndata = [
        'data' => null,
        'info' => null,
        'status' => null,
    ];

    /**
     * @var array
     */
    public $action = [
        'shipping_status' => 0,
        'CLIENT_NAME' => '',
        'PO_ID' => '',
        'delivery_warehouse_code' => '',
        'sales_team_code' => '',
        'orderId' => 'THR_PO_ID',
        'lately_time_action' => '',
        'lately_time_end' => '',
        'warehouse' => ''
    ];
    /**
     * @var array
     */
    public $action_warehousing = [
        'warehouse' => '',
        'status' => '',
        'PO_ID' => '',
        'CLIENT_NAME' => '',
        'SALES_TEAM' => ''
    ];

    /**
     * @var array
     */
    public $action_warehouse = [
        'shipping_status' => 0,
        'CLIENT_NAME' => '',
        'PO_ID' => '',
        'delivery_warehouse_code' => '',
        'sales_team_code' => ''
    ];

    /**
     * @var array
     */
    public $action_gathering = [
        'gathering' => '',
        'main_gathering' => '',
        'CLIENT_NAME' => '',
        'PO_ID' => '',
        'sales_team_code' => '',
        'transaction_type' => '',
        'unconfirmed' => '',
        'orderId' => 'THR_PO_ID'
    ];

    /**
     * @var array
     */
    public $number_th = ['1st', '2nd', '3rd'];

    /**
     * @var string
     */
    public $_pre_mail = '@gshopper.com';

    /**
     *
     */
    const B2B_CLAIM_CODE = '';//B2B收款认领
    /**
     *
     */
    const PUR_REFUND_CLAIM_CODE = 'N002630200';//采购退款认领
    const GP_BIG_ORDER_CLAIM_CODE = 'N002630201';//GP大额订单认领


    /**
     * @return bool|void
     */
    const B2B_SEND_OUT_CD = 'N002350700';

    const CUSTOMER_WAREHOUSING_CD = 'N000941000';

    const SPOT_STOCK_CD = 'N002440400';

    public function _initialize()
    {
        date_default_timezone_set("Asia/Shanghai");
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        }
        header('Access-Control-Allow-Origin: *');
        $this->assign('scope', 'b2b');
    }

    /**
     *  Default empty api
     */
    public function get_empty()
    {
        $this->ajaxReturn(array());
    }

    /**
     * 订单列表
     */
    public function order_list()
    {
        $this->assign('title', 'B2B订单');
        $this->display($this->url);
    }

    /**
     * 订单新增
     */
    public function order_add()
    {
        $this->assign('title', 'B2B订单新增');
        $this->display($this->url);
    }

    /**
     * 初始化
     */
    public function init()
    {
        $data_cache = RedisModel::get_key('b2b_cd_init');
        if (!$data_cache) {
            $data['currency'] = B2bModel::get_currency();
            $data['tax_rebate_ratio'] = $this->rm_sign(B2bModel::get_code('采购税率'));
            $data['all_warehouse'] = StockModel::get_all_warehouse();
            $data['Country'] = BaseModel::getCountry();
            $data['sales_team'] = B2bModel::get_code_y('销售团队', true);
            $data['shipping_method'] = B2bModel::get_shipping_method();
            $data['payment_node'] = [
                'node_type' => B2bModel::get_code_y('node_type'),
                'node_date' => B2bModel::get_code('node_date'),
                'node_is_workday' => B2bModel::get_code('node_is_workday'),
                'node_prop' => B2bModel::get_code('node_prop')
            ];
            $data['payment_cycle'] = B2bModel::get_code('period');
            $data['shipping'] = B2bModel::get_code_cd('N00153', true);
            $data['invioce'] = B2bModel::get_code('invioce');
            $data['tax_point'] = $this->un_sign(B2bModel::get_code('tax_point'));
            $data['number_th'] = $this->number_th;
            $data['currency_bz'] = B2bModel::get_code('currency_bz');
            $data['allPurchasingArr'] = B2bModel::get_code_y('采购团队');
            $data['allIntroduceArr'] = B2bModel::get_code_y('介绍团队');
            $data['wfgs'] = B2bModel::get_code('我方公司');
            $data['business_type'] = B2bModel::get_code_y('业务类型');
            $data['business_direction'] = B2bModel::get_code_y('业务方向');
            $data['user'] = B2bModel::get_user();
            $data['invoice_point'] = B2bModel::get_invoice_point();
            RedisModel::set_key('b2b_cd_init', json_encode($data));
        } else {
            $data = json_decode($data_cache, true);
        }
        $this->ajaxReturn($data, '', 1);
    }

    /**
     * @param $e
     *
     * @return mixed
     */
    public function un_sign($e)
    {
        foreach ($e as &$v) {
            $v['CD_VAL'] = trim($v['CD_VAL'], '%');
        }
        return $e;
    }

    /**
     * 获取PO信息
     */
    public function get_po_data()
    {

        $CON_NO = $this->getParams()['CON_NO'];
        if (!B2bModel::search_contracct_by_con_no($CON_NO)) {
//            $this->ajaxReturn('编号为：' . $CON_NO . ' 的合同不存在SMS2中，请修改', '', 0);
        }
        $oci = new MeBYModel();
        $sql = "SELECT a.*,b.*,doc.IMAGEFILENAME FROM ECOLOGY.FORMTABLE_MAIN_91 a 
                left join ECOLOGY.HRMRESOURCE b on a.SQR = b.ID
                LEFT JOIN ECOLOGY.DOCIMAGEFILE doc on a.FJSC = doc.DOCID 
                WHERE DJBH='" . $CON_NO . "'";
        $checkSql = "SELECT wr.STATUS FROM ECOLOGY.FORMTABLE_MAIN_91 fm 
                    LEFT JOIN ECOLOGY.WORKFLOW_REQUESTBASE wr on fm.REQUESTID = wr.REQUESTID 
                    WHERE DJBH = '" . $CON_NO . "'";
        $checkRet = $oci->testQuery($checkSql);
        if ($checkRet[0]['STATUS'] != '结束') $this->ajaxReturn('编号为：' . $CON_NO . ' 的合同不存在或尚未完成审核，请修改', '', 0);
        $ret = $oci->testQuery($sql);
        if ($ret) {
            $data = $ret [0];
            $companyCd = BaseModel::conCompanyCd();
            $data['GSMC'] = $companyCd[$data['GSMC']];
            $data = $this->join_po_data($data);
            $this->ajaxReturn($data, '', 1);
        } else {
            $this->ajaxReturn('未查询到编号为：' . $CON_NO . ' 的合同，请修改', '', 0);
        }
    }

    /**
     * @param $e
     *
     * @return mixed
     */
    private function join_po_data($e)
    {
        $e['YF'] = isset($e['YF']) ? $e['YF'] : trim($e['ECHO_COMPANY']);
        $e['CGBUSINESSLICENSE2'] = isset($e['CGBUSINESSLICENSE2']) ? $e['CGBUSINESSLICENSE2'] : trim($e['BUSINESSLICENSEFILLEDINBYLEGAL']);
        return $e;
    }

    /**
     * @param null $scm_data
     *
     * @return mixed
     */
    public function scmCreate($scm_data = null)
    {
        try {
            if (empty($scm_data)) {
                if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
                    $scm_data = json_decode(JsonDataModel::scmCreateB2b(), true);
                } else {
                    throw new Exception(L('数据缺失'), 400);
                }
            }
            Logs(json_encode($scm_data), 'scmCreate', 'scm');
            foreach ($scm_data['poData']['poPaymentNode'] as &$value) {
                $value['nodeType'] = str_replace("N00220", "N00139", $value['nodeType']);
                unset($value);
            }
            foreach ($scm_data['skuData'] as $key => $value) {
                foreach ($value['batch_prices'] as $k => $v) {
                    if (strlen($v['purchasing_currency'] != 10)) {
                        $state_arr = array_flip(ExchangeRateModel::getAllKeyValue());
                        if ($state_arr[$v['purchasing_currency']]) {
                            $scm_data['skuData'][$key]['batch_prices'][$k]['purchasing_currency'] = $state_arr[$v['purchasing_currency']];
                        }
                    }
                }
            }
            $this->scmCreateCheck($scm_data);
            $scm_data = $this->scmDataFilter($scm_data);
            $scm_data = $this->arrayToObject($scm_data);
           
            $save_order = $this->save_order($scm_data);
            Logs($save_order, 'save_order');
            return $save_order;
        } catch (Exception $exception) {
            $res['body'] = $this->error_message;
            $res['msg'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
            Logs($res, 'error_create_res', 'scm');
            @SentinelModel::addAbnormal('SCM 请求创建 B2B 订单', $scm_data['poData']['poNum'] . '异常', $res, 'b2b_notice');
            if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
                $this->ajaxReturn($res);
                die();
            }
            Logs($res, 'error_create_res', 'scm');
            return $res;
        }
    }

    /**
     * @param $order_id
     * @param $Models
     */
    private function createReceivableOrder($order_id, $Models)
    {
        $add_data['ORDER_ID'] = $order_id;
        $add_data['updated_by'] = $add_data['created_by'] = DataModel::userNamePinyin();
        $add_data['updated_at'] = $add_data['created_at'] = DateModel::now();
        $Models->table('tb_b2b_receivable')->add($add_data);
    }


    /**
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    private function scmDataFilter($data)
    {
        foreach ($data['skuData'] as $key => $val) {
            $val['sku_show'] = $val['skuId'];
            if (is_array($val['delivery_prices'])) {
                $data = $this->percentDataCreate($data, $val, 'delivery_prices');
            }
            if (is_array($val['batch_prices'])) {
                $data = $this->percentDataCreate($data, $val, 'batch_prices');
            }
        }
        $data['skuData'] = $data['skuDataNew'];
        if ('[]' == $data['poData']['poScanner']) {
            unset($data['poData']['poScanner']);
        }
        $data['poData']['clientNameEn'] = array($data['poData']['clientNameEN']);
        unset($data['skuDataNew']);
        return $data;
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function scmCreateCheck($data)
    {
        $rules = $this->createDataCheckJoin($data);
        ValidatorModel::validate($rules, $data);
        unset($rules);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $key => $value) {
                if (strstr($key, 'batch_prices') !== false) {
                    throw new Exception(L('批次数据异常'), 40001);
                }
                if (strstr($key, 'poData') !== false) {
                    throw new Exception(L('订单数据异常'), 40001);
                }
                if (strstr($key, 'skuData') !== false) {
//                    throw new Exception(L('商品信息异常'), 40001);
                    throw new Exception($value[0], 40001);
                }
            }
            throw new Exception(L('请求数据错误'), 40001);

        }
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    private function createDataCheckJoin($data)
    {
        $no_required_arr = ['thrPoNum', 'clientNameEn', 'Remarks', 'busLice', 'poScanner', 'targetCity',
            'city', 'street', 'cur_other', 'scm_order_meta', 'contract', 'province'];
        foreach ($data['poData'] as $key => $val) {
            if (!in_array($key, $no_required_arr)) {
                $rules['poData.' . $key] = 'required';
            }
        }
        $rules['poData.poAmount'] = 'required|numeric';
        $rules['poData.poTime'] = 'date';
        $rules['poData.BZ'] = 'string|min:10|max:10';
        $rules['poData.shipping'] = 'string|min:10|max:10';
        $rules['poData.side_taxed_currency'] = 'string|min:10|max:10';
        $rules['poData.saleTeam'] = 'string|min:10|max:10';
        $rules['poData.invioce'] = 'string|min:10|max:10';
        $rules['poData.tax_point'] = 'string|min:10|max:10';
        $rules['poData.backend_currency'] = 'string|min:10|max:10';
        $rules['poData.logistics_currency'] = 'string|min:10|max:10';
        $rules['poData.business_direction'] = 'string|min:10|max:10';
        $rules['poData.business_type'] = 'string|min:10|max:10';
        $rules['poData.cur_saletax'] = 'min:10|max:10';
        foreach ($data['poData']['poPaymentNode'] as $key => $val) {
            $rules['poData.poPaymentNode.' . $key . '.nodei'] = 'required|numeric';
            $rules['poData.poPaymentNode.' . $key . '.nodeType'] = 'required|string|min:10|max:10';
            $rules['poData.poPaymentNode.' . $key . '.nodeDate'] = 'required|string|min:10|max:10';
            $rules['poData.poPaymentNode.' . $key . '.nodeWorkday'] = 'required|numeric';
            $rules['poData.poPaymentNode.' . $key . '.nodeProp'] = 'required|numeric';
        }
        $rules['skuData'] = 'required';

        foreach ($data['skuData'] as $key => $val) {
            if (!count($val['delivery_prices']) && !count($val['batch_prices'])) {
                throw new Exception(L($key . ' SKU 信息缺失'), 400404);
            }
            $rules['skuData.' . $key . '.gudsName'] = 'required';
            $rules['skuData.' . $key . '.skuInfo'] = 'required';
            $rules['skuData.' . $key . '.skuId'] = 'required|string|min:10|max:10';
            $rules['skuData.' . $key . '.gudsPrice'] = 'required';
            $rules['skuData.' . $key . '.gudsPrice'] = array('regex:/^(\d|[1-9]\d+)(\.\d+)?$/');
            $rules['skuData.' . $key . '.demand'] = 'required|numeric';
            // $rules['skuData.' . $key . '.subTotal'] = 'string|min:3|max:10';
            $rules['skuData.' . $key . '.drawback'] = 'numeric';
            $rules['skuData.' . $key . '.estimateDrawback'] = 'numeric';
            $rules['skuData.' . $key . '.GUDS_OPT_ORG_PRC'] = 'required|numeric';
            $rules['skuData.' . $key . '.STD_XCHR_KIND_CD'] = 'required|string|min:10|max:10';
            $rules['skuData.' . $key . '.toskuid'] = 'required|string|min:10|max:10';
            if (count($val['delivery_prices'])) {
                foreach ($val['delivery_prices'] as $k => $v) {
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.purchasing_team'] = 'required|string|min:10|max:10';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.introduce_team'] = 'required|string|min:10|max:10';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.purchasing_currency'] = 'required|string|min:10|max:10';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.purchasing_price'] = 'required|numeric';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.purchasing_num'] = 'required|numeric';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.sku_drawback'] = 'required|numeric';
                    $rules['skuData.' . $key . '.delivery_prices.' . $k . '.procurement_number'] = 'required|string|min:1|max:30';
                }
            }
            if (count($val['batch_prices'])) {
                foreach ($val['batch_prices'] as $k => $v) {
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.purchasing_team'] = 'string|min:10|max:10';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.introduce_team'] = 'string|min:10|max:10';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.purchasing_currency'] = 'required|string|min:10|max:10';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.purchasing_price'] = 'required|numeric';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.purchasing_num'] = 'required|numeric';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.sku_drawback'] = 'numeric';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.batch_id'] = 'required|numeric';
                    $rules['skuData.' . $key . '.batch_prices.' . $k . '.batch_code'] = 'required|numeric';
                }
            }

        }
        return $rules;
    }

    /**
     * 保存订单
     *
     * @param null $scm_data
     *
     * @return mixed
     */
    public function save_order($scm_data = null)
    {
        $data = array();
        if (IS_POST || !empty($scm_data)) {
            if ($scm_data) {
                $post = $scm_data;
                $tpl_path = '../Home/B2b/order_mail_v2';
            } else {
                $tpl_path = 'order_mail_v2';
                $post = B2bModel::get_data();
            }

            $poData_get = $post->poData;

            $Models = new Model();
            $Models->startTrans();
            $receiptData['PO_ID'] = $poData['PO_ID'] = $poData_get->poNum;

            $poData['create_time'] = date('Y-m-d H:i:s');
            $poData['create_user'] = session('m_loginname');
            if ($Models->table('tb_b2b_order')->where("PO_ID = '" . $poData['PO_ID'] . "'")->count()) {
                $res['code'] = 400;
                $res['error'] = '';
                $res['info'] = '订单重复';
                if (!empty($scm_data)) {
                    return $res;
                }
                $this->ajaxReturn($res, $res['info'], $res['code']);
            }
            $profit['ORDER_ID'] = $res['order_id'] = $receiptData['ORDER_ID'] = $poData['ORDER_ID'] = $Models->table('tb_b2b_order')->data($poData)->add();
            // check basic order insert ok or not
            if (!$poData['ORDER_ID']) {
                $res['code'] = 400;
                $res['error'] = '';
                $res['info'] = '新增失败,' . $Models->getDbError();
                if (!empty($scm_data)) {
                    return $res;
                }
                $this->ajaxReturn($res, $res['info'], $res['code']);
            }
            $Models->table('tb_b2b_profit')->data($profit)->add();
           
            list($poData, $receiptData) = $this->joinPoDataCreate($poData_get, $poData, $receiptData, $Models);

            if (empty($poData['PO_USER'])) {
                $res['code'] = 402;
                $res['error'] = $poData['PO_USER'];
                $res['info'] = '新增失败,PO信息未填写完全';
                $Models->rollback();
                if (!empty($scm_data)) {
                    return $res;
                }
                $this->ajaxReturn($res, $res['info'], $res['code']);

            }
            if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $poData['PO_USER'])) {
                    if (count(B2bModel::get_user($poData['PO_USER'])) == 0) {
                        $res['code'] = 401;
                        $res['error'] = $poData['PO_USER'];
                        $res['info'] = '新增失败,销售同事填写信息不存在ERP中';
                        $Models->rollback();
                        $this->ajaxReturn($res, $res['info'], $res['code']);
                    }
            }
           
            list($poData, $receiptData) = $this->joinPoDataTwo($poData_get, $poData, $receiptData);

            if ($poData_get->deducting_tax && $poData_get->side_taxed) {
                $receiptData['deducting_tax_currency'] = $poData['deducting_tax_currency'] = $poData['po_currency'];
                $receiptData['side_taxed_currency'] = $poData['side_taxed_currency'] = $poData_get->side_taxed_currency;
                $receiptData['deducting_tax'] = $poData['deducting_tax'] = $this->unking($poData_get->deducting_tax);
                $receiptData['side_taxed'] = $poData['side_taxed'] = $this->unking($poData_get->side_taxed);
            }

            // check more info
            $poData = BaseCommon::checkPo($poData, $poData_get);
            
            $poData = DataMain::fieldData($poData, 'tb_b2b_info');
            $poData['THR_PO_ID'] = $poData_get->thrPoNum;
            $poScanner = $poData_get->poScanner;
            if ($poScanner) {
                if (is_object($poScanner) || is_array($poScanner)) {
                    $poScanner = (array)$poScanner;
                    $poData['po_erp_path'] = $poData['PO_FILFE_PATH'] = json_encode($poScanner, JSON_UNESCAPED_UNICODE);
                } else {
                    $poData['po_erp_path'] = $poData['PO_FILFE_PATH'] = $poScanner;
                }
            }
            if ($poData_get->Remarks) {
                $poData['remarks'] = $poData_get->Remarks;
            }
           
            $info_id = $Models->table('tb_b2b_info')->data($poData)->add();
            
//            add receipt
            $payment_node = json_decode($poData['PAYMENT_NODE'], true);
//                组装节点和比例
            if ($poData['BILLING_CYCLE_STATE'] == 4) {
                $receiptData['receiving_code'] = json_encode($payment_node[0]);
                $receiptData['overdue_statue'] = 0;
//                计算帐期金额
                $receiptData['expect_receipt_amount'] = $payment_node[0]['nodeProp'] / 100 * $poData['po_amount'];
                $receipt_id = $Models->table('tb_b2b_receipt')->data($receiptData)->add();
            } else {
                for ($i = 0; $i < $poData['BILLING_CYCLE_STATE']; $i++) {

                    $receiptData['receiving_code'] = json_encode($payment_node[$i]);
                    $receiptData['overdue_statue'] = 0;
//                计算帐期金额
                    $receiptData['expect_receipt_amount'] = $payment_node[$i]['nodeProp'] / 100 * $poData['po_amount'];
                    $receipt_id = $Models->table('tb_b2b_receipt')->data($receiptData)->add();
                }
            }
//            增加退税
            if (!$scm_data && $poData['tax_rebate_income'] > 0) {
                $receipt_taxes = $receiptData;
                unset($receipt_taxes['receiving_code']);
                unset($receipt_taxes['estimated_amount']);
                $transaction_type = $this->get_code_key('transaction_type');
                $receipt_taxes['transaction_type'] = $transaction_type['退税'];
                $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['tax_rebate_income'];
                $receipt_id = $Models->table('tb_b2b_receipt')->data($receipt_taxes)->add();
            }
            if (!$scm_data && $poData['drawback_estimate'] > 0) {
                $receipt_taxes = $receiptData;
                unset($receipt_taxes['receiving_code']);
                unset($receipt_taxes['estimated_amount']);
                $transaction_type = $this->get_code_key('transaction_type');
                $receipt_taxes['transaction_type'] = $transaction_type['退税'];
                $receipt_taxes['expect_receipt_amount'] = $receipt_taxes['estimated_amount'] = $poData['drawback_estimate'];
                $receipt_id = $Models->table('tb_b2b_receipt')->data($receipt_taxes)->add();
            }

//            add order
            if (!$info_id) {
                $res['code'] = 402;
                $res['error'] = $info_id;
                $res['info'] = '新增失败,PO数据异常-信息重复';
                $Models->rollback();
            }
            if ($poData['ORDER_ID']) {
                $sku_get = $post->skuData;
                Logs($sku_get, 'skuData');
                $tmp = B2bData::sku_get_with_po($sku_get, $poData);
                $skuData_arr = $tmp['skuData_arr'];
                $order_num = $tmp['order_num'];
                $sku_add = $Models->table('tb_b2b_goods')->addAll($skuData_arr);
                $do_status = $this->do_list_sync($poData, $skuData_arr, $order_num);
                if ($sku_add && $do_status) {
                    $res['code'] = 200;
                    $res['info'] = '新增成功';
                    $this->upd_gather_date($poData['ORDER_ID'], $Models);
                    $this->order_mail_send($poData, $Models, $tpl_path, $Models);
                    $this->createReceivableOrder($profit['ORDER_ID'], $Models);
                    $Models->commit();
                    B2bModel::addLog($poData['ORDER_ID'], 200, '创建订单');
                } else {
                    $res['code'] = 401;
                    $res['info'] = '新增失败，商品或PO数据异常';
                    $res['sku_add'] = $sku_add;
                    $res['do_status'] = $do_status;
                    $Models->rollback();
                }
            } else {
                $res['code'] = 400;
                $res['error'] = $info_id;
                $res['info'] = '新增失败,PO数据异常-重复';
                $Models->rollback();
            }
            if ($res['code'] != 400) B2bModel::addLog($poData['ORDER_ID'], $res['code'], $res['info']);
        }
        if (!empty($scm_data)) {
            return $res;
        }
        $this->ajaxReturn($res, $res['info'], $res['code']);
    }


    /**
     *  草稿
     *  do 3 actions : add draft po, edit draft po, edit publish po.
     */
    public function save_draft()
    {
        // rqeuest
        $req = Mainfunc::getInputJson();
        $poData_get = isset($req['poData']) ? $req['poData'] : null;
        $skuData_get = isset($req['skuData']) ? $req['skuData'] : null;
        // edit id
        $edit_id = isset($poData_get['edit_id']) ? $poData_get['edit_id'] : null;
        // $edit_id = $edit_id?$edit_id:(isset($_REQUEST['edit_id'])?$_REQUEST['edit_id']:null);
        // publish
        $edit_is_publish = isset($_REQUEST['edit_is_publish']) ? $_REQUEST['edit_is_publish'] : null;
        // data
        $poData = array();
        // outputs
        $outputs = array();
        $outputs['data'] = null;
        $outputs['info'] = null;
        $outputs['status'] = null;
        if (empty($poData_get['poNum'])) {
            $outputs['info'] = L('No po num');
            $this->ajaxReturn($outputs);
        }

        $Models = new Model();
        $Models->startTrans();

        // check publish
        $order_base = array();
        if ($edit_id) {
            // check draft state
            $order_base = B2bData::oneOrderBasic($edit_id);
            if ($order_base['submit_state'] == 2) {
                $edit_is_publish = 1;
            }
        }

        // edit order which it it publish
        if ($edit_is_publish == 1) {
            $edit_data = B2bData::fetchOneOrder($edit_id);
            if (empty($edit_data)) {
                $outputs['info'] = 'Wrong po id';
                $this->ajaxReturn($outputs);
            }
            if ($edit_data['PO_ID'] != $poData_get['poNum']) {
                // check repeat
                $avail = B2bSearch::usablePoId($poData_get['poNum'], $edit_id);
                if (!$avail) {
                    $outputs['info'] = L('抱歉-PO-ID-重复');
                    $this->ajaxReturn($outputs);
                }
            }
            // check publish state
            $can_edit = B2bData::gain_publish_change($edit_id);
            if (!$can_edit) {
                $outputs['info'] = L('抱歉-目前状态不允许修改-[只允许该订单已提交但是未在系统上发货、也没有提交收款、提交退税信息]');
                $this->ajaxReturn($outputs);
            }
            if (isset($poData_get['submit_state'])) {
                if ($poData_get['submit_state'] != 2) {
                    $outputs['info'] = L('抱歉-状态错误-请刷新重试');
                    $this->ajaxReturn($outputs);
                }
            }
            // edit db - order
            $poEditInfo = $poData_get;
            $res = B2bData::edit_po_order($poEditInfo, $edit_id);
            $order_id = $edit_id;
            $poData = $edit_data;
            // edit db - order info
            $check = B2bData::po_info_need_check($poData_get, $order_base);
            if ($check['is_err']) {
                $outputs['info'] = $check['err_msg'];
                $this->ajaxReturn($outputs);
            }
            $res = B2bData::add_po_info($poData, $poData_get);
            $poData = $res['data'];
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // draft not receipt - by publish - edit
            $tmp = B2bData::edit_po_receipt($poData, $poData_get);

            // edit db - sku data
            $res = B2bData::edit_po_goods_skus($poData, $skuData_get);
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            $skuData_arr = $res['skuData_arr'];
            $order_num = $res['order_num'];
            $sku_add = $res['sku_add'];
            // doship
            $do_status = B2bData::do_add_doship($poData, $skuData_arr, $order_num);

            if ($sku_add && $do_status) {
                $this->upd_gather_date($poData['ORDER_ID']);

            }

            // add db - log
            B2bModel::addLog($poData['ORDER_ID'], 200, L('操作成功'));

            $Models->commit();
            $outputs['status'] = 200;
            $outputs['order_id'] = $order_id;
            $outputs['info'] = L('操作成功');
            $outputs['data']['order_id'] = $order_id;
            $this->ajaxReturn($outputs);

            die();
            // end
        }

        // new
        if (empty($edit_id)) {
            // add db - order
            $res = B2bData::add_new_po_order($poData_get);
            $order_id = $res['order_id'];
            $poData = $res['order_data'];
            if (!$order_id) {
                $outputs['info'] = L('新增失败') . $Models->getDbError();
                $this->ajaxReturn($outputs);
            }
            // add db - order profit
            $res = B2bData::add_po_profit($poData);
            // add db - order info
            $res = B2bData::add_po_info($poData, $poData_get);
            $poData = $res['data'];
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // var_dump($res);
            // draft not receipt

            // add db - sku data
            $res = B2bData::add_po_goods_skus($poData, $skuData_get);
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // var_dump($res);
            // add db - log
            B2bModel::addLog($poData['ORDER_ID'], 200, L('新增草稿成功'));

            $Models->commit();
            $outputs['status'] = 200;
            $outputs['info'] = L('操作成功');
            $outputs['order_id'] = $order_id;
            $this->ajaxReturn($outputs);
            // end
        }

        // edit
        if ($edit_id) {
            $edit_data = B2bData::fetchOneOrder($edit_id);
            if (empty($edit_data)) {
                $outputs['info'] = 'Wrong po id';
                $this->ajaxReturn($outputs);
            }
            if ($edit_data['PO_ID'] != $poData_get['poNum']) {
                // check repeat
                $avail = B2bSearch::usablePoId($poData_get['poNum'], $edit_id);
                if (!$avail) {
                    $outputs['info'] = L('抱歉-PO-ID-重复');
                    $this->ajaxReturn($outputs);
                }
            }
            // check draft state
            $order_base = B2bData::oneOrderBasic($edit_id);
            if ($order_base['submit_state'] != 1) {
                $outputs['info'] = L('抱歉-不能更改-不是草稿状态');
                $this->ajaxReturn($outputs);
            }
            // edit db - order
            $res = B2bData::edit_po_order($poData_get, $edit_id);
            $order_id = $edit_id;
            $poData = $edit_data;
            // edit db - order profit
            // none
            // edit db - order info
            $res = B2bData::add_po_info($poData, $poData_get);
            $poData = $res['data'];
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // edit db - sku data
            $res = B2bData::add_po_goods_skus($poData, $skuData_get);
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // add db - log
            B2bModel::addLog($poData['ORDER_ID'], 200, L('修改草稿成功'));

            $Models->commit();
            $outputs['status'] = 200;
            $outputs['info'] = L('操作成功');
            $outputs['order_id'] = $order_id;
            $this->ajaxReturn($outputs);
            // end
        }

    }

    /**
     * Get proportion data to BI API
     */
    public function getProportion()
    {
        $post_data = B2bModel::get_data('params', true);
        $res = B2bModel::joinProportionReq($post_data);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     *  正式提交
     */
    public function save_o_publish()
    {
        // rqeuest
        $req = Mainfunc::getInputJson();
        $poData_get = isset($req['poData']) ? $req['poData'] : null;
        $skuData_get = isset($req['skuData']) ? $req['skuData'] : null;
        // edit id
        $edit_id = isset($poData_get['edit_id']) ? $poData_get['edit_id'] : null;
        // $edit_id = $edit_id?$edit_id:(isset($_REQUEST['edit_id'])?$_REQUEST['edit_id']:null);
        // publish
        $edit_is_publish = isset($_REQUEST['edit_is_publish']) ? $_REQUEST['edit_is_publish'] : null;

        // outputs
        $outputs = array();
        $outputs['data'] = null;
        $outputs['info'] = null;
        $outputs['status'] = null;
        if (empty($poData_get['poNum'])) {
            $outputs['info'] = L('No po num');
            $this->ajaxReturn($outputs);
        }

        if (empty($edit_id)) {
            $outputs['info'] = L('参数错误');
            $this->ajaxReturn($outputs);
        }

        $Models = new Model();
        $Models->startTrans();

        // like edit , and do some publish code.
        // edit
        if ($edit_id) {
            $edit_data = B2bData::fetchOneOrder($edit_id);
            if (empty($edit_data)) {
                $outputs['info'] = L('Wrong po id');
                $this->ajaxReturn($outputs);
            }
            if ($edit_data['PO_ID'] != $poData_get['poNum']) {
                // check repeat
                $avail = B2bSearch::usablePoId($poData_get['poNum'], $edit_id);
                if (!$avail) {
                    $outputs['info'] = L('抱歉-PO-ID-重复');
                    $this->ajaxReturn($outputs);
                }
            }
            // check draft state
            $order_base = B2bData::oneOrderBasic($edit_id);
            if ($order_base['submit_state'] != 1) {
                $outputs['info'] = L('抱歉-不能更改-不是草稿状态');
                $this->ajaxReturn($outputs);
            }
            // edit db - order
            $poEditInfo = $poData_get;
            $poEditInfo['submit_state'] = B2bData::Submit_commit;
            $res = B2bData::edit_po_order($poEditInfo, $edit_id);
            $order_id = $edit_id;
            $poData = $edit_data;
            // edit db - order profit
            // none
            // edit db - order info
            $check = B2bData::po_info_need_check($poData_get);
            if ($check['is_err']) {
                $outputs['info'] = $check['err_msg'];
                $this->ajaxReturn($outputs);
            }
            $res = B2bData::add_po_info($poData, $poData_get);
            $poData = $res['data'];
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            // draft not receipt
            $tmp = B2bData::add_po_receipt($poData, $poData_get);

            // edit db - sku data
            $res = B2bData::add_po_goods_skus($poData, $skuData_get);
            if ($res['is_err'] == 1) {
                $outputs['info'] = $res['err_msg'];
                $this->ajaxReturn($outputs);
            }
            $skuData_arr = $res['skuData_arr'];
            $order_num = $res['order_num'];
            $sku_add = $res['sku_add'];
            // doship
            $status = $Models->table('tb_b2b_doship')->where(array('ORDER_ID' => $edit_id))->delete();
            $do_status = $this->do_list_sync($poData, $skuData_arr, $order_num);
            if ($sku_add && $do_status) {
                $this->upd_gather_date($poData['ORDER_ID']);
                $status_mail = $this->order_mail_send($poData, $Models, 'order_mail_v2');
            }
            $status_mail = isset($status_mail) ? $status_mail : null;
            if (!$status_mail) {
                $outputs['info'] = L('抱歉-发送邮件失败-请重试');
                $this->ajaxReturn($outputs);
            }
            // add db - log
            B2bModel::addLog($poData['ORDER_ID'], 200, L('正式提交成功'));

            $Models->commit();
            $outputs['status'] = 200;
            $outputs['order_id'] = $order_id;
            $outputs['info'] = L('正式提交成功');
            $outputs['data']['order_id'] = $order_id;
            $this->ajaxReturn($outputs);
            // end
        }

    }

    /**
     *  Del po of b2b - draft
     */
    public function del_o_b2b()
    {
        $Models = new Model();
        $Models->startTrans();
        $del_id = isset($_REQUEST['del_id']) ? $_REQUEST['del_id'] : null;
        $order_id = $del_id;
        if ($del_id) {
            // check publish state
            $can_edit = B2bData::gain_publish_change($order_id);
            if (!$can_edit) {
                $outputs['info'] = L('抱歉-目前状态不允许操作-[只允许该订单已提交但是未在系统上发货、也没有提交收款、提交退税信息]');
                echo $outputs['info'];
                die();
            }
            // check it is draft
            $order_base = B2bData::oneOrderBasic($order_id);
            if ($order_base['submit_state'] != 1) {
                $outputs['info'] = L('抱歉-目前状态不是草稿');
                echo $outputs['info'];
                die();
            }
            $one_data = B2bData::fetchOneOrder($del_id);
            if ($one_data) {
                $tmp = B2bData::del_po_about($order_id);
                $Models->commit();
            }
        }
        $url_go = U('B2b/order_list');
        js_redirect($url_go);
    }

    /**
     *  Del po of b2b - published
     */
    public function del_o_b2b_published()
    {
        $Models = new Model();
        $Models->startTrans();
        $del_id = isset($_REQUEST['del_id']) ? $_REQUEST['del_id'] : null;
        $order_id = $del_id;
        if ($del_id) {
            // check publish state
            $can_edit = B2bData::gain_publish_change($order_id);
            if (!$can_edit) {
                $outputs['info'] = L('抱歉-目前状态不允许操作-[只允许该订单已提交但是未在系统上发货、也没有提交收款、提交退税信息]');
                echo $outputs['info'];
                die();
            }
            // check it is draft
            $order_base = B2bData::oneOrderBasic($order_id);
            if ($order_base['submit_state'] != 2) {
                $outputs['info'] = L('抱歉-目前状态error');
                echo $outputs['info'];
                die();
            }
            $one_data = B2bData::fetchOneOrder($del_id);
            if ($one_data) {
                $tmp = B2bData::del_po_about($order_id);
                $Models->commit();
            }
        }
        $url_go = U('B2b/order_list');
        js_redirect($url_go);
    }

    /**
     *  Calculate the price of forecast
     */
    public function calcu_forecast()
    {
        $info = BaseCommon::calcu_f($_REQUEST);
        $res = array();
        $res['code'] = 200;
        $res['error'] = '';
        $res['info'] = $info;
        $this->ajaxReturn($res);
    }

    /**
     *  Show mail of order
     */
    public function show_order_mail()
    {
        import("@.ToolClass.B2b.B2bData");
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;
        $poData = B2bData::fetchOneOrder($order_id);
        if (empty($poData)) {
            echo '<pre>';
            echo 'Please check order id , not exists.';
            echo '</pre>';
        }
        // var_dump($poData);
        $mailData = B2bData::mail_data_for_summary($poData);
        $mailData['forecast'] = BaseCommon::fmt_the_forecast($mailData['forecast']);
        echo '<pre>';
        print_r($poData);
        print_r($mailData);
        echo '</pre>';
        $this->assign('mail', $mailData);
        $message = $this->fetch('B2B/order_mail_v2');
        echo $message;
    }

    /**
     *  Renew forecast
     *  Api for update one (sale_tax/drawback_estimate)
     *  e.g.:  /index.php?m=b2b&a=renew_calcu_forecast&re_type=sale_tax&order_id=***
     */
    public function renew_calcu_forecast()
    {
        // renew type - [ sale_tax , drawback_estimate ]
        $re_type = isset($_REQUEST['re_type']) ? $_REQUEST['re_type'] : null;
        // id
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;
        $outputs = B2bData::renew_calcu_tax($_REQUEST);
        echo json_encode($outputs);
        die();
    }

    /**
     *  Renew forecast by max
     *  e.g.:  /index.php?m=b2b&a=renew_calcu_forecast_old&re_type=sale_tax&max_order_id=***
     */
    public function renew_calcu_forecast_old()
    {
        $outputs = array();
        $re_type = isset($_REQUEST['re_type']) ? $_REQUEST['re_type'] : null;
        $max_order_id = isset($_REQUEST['max_order_id']) ? $_REQUEST['max_order_id'] : null;
        $max_order_id = intval($max_order_id);
        $max = 10000;
        $i = 0;
        if ($max_order_id > 0) {
            while ($max_order_id > 0) {
                $arr = array();
                $arr['order_id'] = $max_order_id;
                $arr['re_type'] = $re_type;
                $res = B2bData::renew_calcu_tax($arr);
                $arr['res'] = $res;
                $outputs['data'][] = $arr;
                $max_order_id--;
                $outputs['i'] = $i;
                // check max
                ++$i;
                if ($i > $max) {
                    break(1);
                }
            }
        }
        echo json_encode($outputs);
    }


    /**
     * 展示订单列表
     */
    public function show_list($join_search = null)
    {
        if (empty($join_search)) {
            $post = $this->_param();
        } else {
            $post = $join_search;
        }
        list($where, $Order) = $this->joinShowListWhere($post);

        $Order = new SlaveModel();
        $where_string = '';
        if (empty($join_search) && $post['PO_USER'] && $where['tb_b2b_info.PO_USER']) { // 订单列表中，去掉原有模糊查询销售人员逻辑，替换为精确匹配销售人员或销售助理名称(销售助理可能有多个，以逗号隔开)
            unset($where['tb_b2b_info.PO_USER']);
            $where_string .= "(tb_b2b_info.PO_USER = '{$post['PO_USER']}' OR FIND_IN_SET ('{$post['PO_USER']}',tb_con_division_client.sales_assistant_by))";
        }
        $where['tb_b2b_info.id'] = ['GT', 0];
        $sql = $Order->table('tb_b2b_order')->where($where)
            ->where($where_string, null, true)
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_order.ID')
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_order.ID')
            ->join('left join tb_b2b_receivable on tb_b2b_receivable.order_id = tb_b2b_order.ID')
            ->join('left join tb_wms_batch_order on tb_wms_batch_order.ORD_ID = tb_b2b_order.PO_ID AND tb_wms_batch_order.use_type IN (1,2)')
            ->join('left join tb_wms_batch on tb_wms_batch_order.batch_id = tb_wms_batch.id')
            ->join('left join tb_wms_bill on tb_wms_bill.id = tb_wms_batch.bill_id')
            ->join('left join tb_pur_order_detail on tb_pur_order_detail.procurement_number = tb_wms_bill.procurement_number')
            ->group('tb_b2b_order.ID');
        if (isset($where['tb_b2b_info.CLIENT_NAME']) || !empty($post['PO_USER'])) {
            $sql->join('tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }
        $sql_clone1 =  clone $sql;
        $sql_clone2 =  clone $sql;

        $search_all_orders = $sql->field('tb_b2b_order.id')->select();
        unset($where['tb_b2b_info.id']);
        if ($join_search) {
            return $search_all_orders;
        }

        if (!empty($where['tb_wms_bill.procurement_number']) || !empty($where['tb_pur_order_detail.online_purchase_order_number'])) {
            $count = count($sql_clone1->field('tb_b2b_order.ID')->select());
            $Page = new Page($count, 10);
            $show = $Page->show();
            $data['order'] = $sql_clone2
                ->field('tb_b2b_order.*,tb_b2b_info.*,
                tb_b2b_doship.order_num,tb_b2b_doship.shipping_status,
                tb_b2b_receivable.receivable_status,
                tb_wms_bill.procurement_number
                ')
                ->order('tb_b2b_order.ID desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $other_sql = $Order->table('tb_b2b_order')->where($where)
                ->where($where_string, null, true)->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_order.ID')
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_order.ID')
                ->join('left join tb_b2b_receivable on tb_b2b_receivable.order_id = tb_b2b_order.ID');
            if (isset($where['tb_b2b_info.CLIENT_NAME']) || !empty($post['PO_USER'])) {
                $other_sql->join('left join tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                    ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
            }
            $other_sql_clone = clone $other_sql;
            $count = $other_sql->count();
            $Page = new Page($count, 10);
            $show = $Page->show();
            $data['order'] = $other_sql_clone
                ->field('tb_b2b_order.*,tb_b2b_info.*,tb_b2b_doship.order_num,tb_b2b_doship.shipping_status,tb_b2b_receivable.receivable_status')
                ->order('tb_b2b_order.ID desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        $data['order'] = BaseCommon::fmtOrderList($data['order']);
        // get all goods title
        $order_id_arr = array_column($data['order'], 'ORDER_ID');
        $order_goods = [];
        if ($order_id_arr) {
            $Goods = M('goods', 'tb_b2b_');
            $where_goods['ORDER_ID'] = array('IN', $order_id_arr);
            $order_goods = $Goods->field('ORDER_ID,SKU_ID,goods_title')->where($where_goods)->select();
        }
        if ($order_goods) {
            $order_goods = SkuModel::getInfo($order_goods, 'SKU_ID', ['spu_name'], ['spu_name' => 'goods_title']);
        }
        foreach ($order_goods as $v) {
            $arr[$v['ORDER_ID']][] = $v['goods_title'];
        }
        $data['goods'] = $arr;
        $node_date = B2bModel::get_code('node_date');
        $data['count_where'] = count($where);
        if (!count($where)) {
            $data['period'] = B2bModel::get_code('period'); //帐期
            $data['now_state'] = B2bModel::get_code('now_state');
            $data['search_type_arr'] = B2bModel::$search_type;

            $data['sales_team'] = B2bModel::get_sales_team();
            $data['currency'] = B2bModel::get_code('기준환율종류코드', true);

            $data['order_fh'] = B2bModel::get_code_lang('order_fh'); //发货
            $data['warehouse_state'] = B2bModel::get_code_lang('warehouse_state'); //入库
            $data['order_sk'] = B2bModel::get_code_lang('order_sk'); //收款
            $data['order_ts'] = B2bModel::get_code_lang('order_ts'); //税率

            $data['yq_arr'] = B2bModel::get_code('overdue');
            $data['return_goods_status'] = TbMsCmnCdModel::getInstance()->getCdKey(TbMsCmnCdModel::$b2b_order_return_goods_status_cd_pre);
            //submit_state
            $data['submit_state'] = B2bData::allSubmitState();
            $data['receivable_status_arr'] = CodeModel::getCodeKeyValArr(['N00254']);
        }
        $data['action'] = $this->action;

        $data['count'] = $count;
        if ($search_all_orders && $post['is_show_summary']) {
            $data = $this->assemblyExcelToList($search_all_orders, $data);
        }

        $data['order'] = CodeModel::autoCodeTwoVal($data['order'], ['TAX_POINT']);

        $data['order'] = DataModel::percentageToDecimal($data['order'], 'TAX_POINT_val');
        $data['order'] = array_map(function ($value) {
            $value['po_amount_excluding_tax'] = sprintf("%0.2f", $value['po_amount'] / (1 + $value['TAX_POINT_val_decimal']));
            if (!$value['po_amount_excluding_tax']) {
                $value['po_amount_excluding_tax'] = 0.00;
            }
            return $value;
        }, $data['order']);
        $this->ajaxReturn($data, '', 1);
    }

    /**
     * 预估毛利/逾期
     *
     * @param      $order_id
     * @param      $node_date
     * @param null $type
     *
     * @return mixed
     */
    private function get_ygml($order_id, $node_date, $type = null)
    {
        $order_id = trim($order_id);
        $data = $this->exchange_rete_calculation($order_id);
        $ml['yg'] = $data['profit']['H'];
        // v2
        $profit_pre = isset($data['info'][0]['profit_pre']) ? $data['info'][0]['profit_pre'] : null;
        $profit_pre = is_array($profit_pre) ? $profit_pre : array();
        $ml['yg'] = Mainfunc::pricePretty($profit_pre['gross_interest_rate'] * 100);
        $ml['sj'] = $data['profit']['W'];
        return $ml;
    }

    /**
     * 逾期效验
     *
     * @param      $order_id
     * @param      $node
     * @param      $check_data
     * @param      $node_date
     * @param      $t_date
     * @param null $transaction_type
     * @param null $get_date
     *
     * @return bool
     */
    public function check_yq($order_id, $node, $check_data, $node_date, $t_date, $transaction_type = null, $get_date = null)
    {
        if ($transaction_type) {
            trace($transaction_type, '$transaction_type');
        }
        $node = json_decode($node, true);
        $times = null; // 处理时间
        foreach ($node as $v) {
            switch ($v['nodeType']) {
                case 'N001390100':
                    $type = '合同';
                    $times = $check_data['po_time'];
                    break;
                case 'N001390200':
                    $type = '发货';
                    $times = $check_data['DELIVERY_TIME'];
                    break;
                case 'N001390400':
                    $type = '入库';
                    $times = $check_data['WAREING_DATE'];
                    break;
                case 0:
                    $type = '合同';
                    $times = $check_data['po_time'];
                    break;
                case 1:
                    $type = '发货';
                    $times = $check_data['DELIVERY_TIME'];
                    break;
                case 2:
                    $type = '入港';
                    $times = $check_data['Estimated_arrival_DATE'];
                    break;
                case 3:
                    $type = '入库';
                    $times = $check_data['WAREING_DATE'];
                    break;
                case 4:
                    $type = '月结';
                    $times = $check_data['WAREING_DATE'];
                    break;
                default:
            }
            if ($get_date) return $times;
            if (empty($t_date)) break;
            if (empty($times)) break;
            $times = date('Y-m-d', strtotime($times));
            if ($t_date) $now_time = date('Y-m-d', strtotime($t_date . " -" . $node_date[$v['nodeDate']]['CD_VAL'] . "day"));

            if ($times > $now_time) {
                return true;  //逾期
                break;
            }
        }
        return false;
    }


    /**
     * 获取订单详情
     */
    public function order_content()
    {
        $get_data = $this->_param();
        $order_id = trim($get_data['order_id']);
        $data = $this->exchange_rete_calculation($order_id);
        $all_ship_excluding_tax = $this->assemblyShipExcludingTax([$get_data['order_id']]);
        $all_ship_excluding_tax_key_val = $this->assemblyTaxMapping($all_ship_excluding_tax);
        $data['ship'] = $this->joinShipExcludingData($data['ship'], $all_ship_excluding_tax_key_val);
        $data['info'] = $this->infoFilter($data['info']);
        $data['info'][0]['CLIENT_NAME_EN'] = TbCrmSpSupplierModel::clientNameToEn($data['info'][0]['CLIENT_NAME']);
        $data['info'] = CodeModel::autoCodeTwoVal($data['info'], ['TAX_POINT']);
        $data['info'][0]['TAX_POINT_val_decimal'] = DataModel::percentageToDecimal($data['info'][0]['TAX_POINT_val']);
        $data['info'][0]['po_amount_excluding_tax'] = number_format($data['info'][0]['po_amount'] / (1 + $data['info'][0]['TAX_POINT_val_decimal']), 2);

        $data['goods'] = $this->goodsFilter($data['goods'], $data['info'][0]['TAX_POINT_val_decimal']);
        $data['goods'] = SkuModel::getInfo(
            $data['goods'],
            'SKU_ID',
            ['spu_name', 'image_url', 'product_sku', 'attributes'],
            ['spu_name' => 'goods_title',
                'image_url' => 'guds_img_cdn_addr',
                'attributes' => 'product_attribute'
            ]
        );
        $data['goods'] = SkuModel::getTableAttr($data['goods']);
        foreach ($data['goods'] as $k =>  $item) {
            if(isset($item['product_sku']) && isset($item['product_sku']['upc_more']) &&
             !empty($item['product_sku']['upc_more'])) {
                $upc_more_arr = explode(',', $item['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $item['product_sku']['upc_id']);
                $data['goods'][$k]['bar_code'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
            }
        }

        $data = $this->getOrderDetailCodeData($data);
        if ($data) {
            $res['code'] = 200;
        } else {
            $res['code'] = 400;
            $res['info'] = '数据查询失败';
        }
        $B2bServer = new B2bService();
        $data = $B2bServer->expansionOrderDetail($data);
        $this->ajaxReturn($data, $res['info'], $res['code']);
    }

    /**
     * @param array $order_ids
     * @param bool $is_group_all
     * @param bool $is_all_order
     *
     * @return array|mixed
     */
    public function assemblyShipExcludingTax(array $order_ids, $is_group_all = false, $is_all_order = false)
    {
        $group_string_ship = 'tb_b2b_goods.ORDER_ID, tb_b2b_ship_list.ID';
        if ($is_group_all) {
            $where_ship_group = null;
            $group_string_ship = 'tb_b2b_goods.ORDER_ID';
        } else {
            $where_ship_group = 'AND t11.ship_list_id = t12.ship_list_id';
        }
        if (false === $is_all_order && false == $order_ids) {
            return [];
        }
        if (!$is_all_order) {
            $order_ids_str = WhereModel::arrayToInString($order_ids);
            $order_where = " AND tb_b2b_goods.ORDER_ID IN ({$order_ids_str})";
        }
        $Model = new SlaveModel();
        $sql = "SELECT
                    t11.*, t12.sum_shipping_revenue,
                    t12.sum_shipping_revenue_excludeing_tax,
                    t12.sum_shipping_revenue_excludeing_tax_cny
                FROM
                    (
                        SELECT
                            tb_b2b_goods.order_id,
                            tb_b2b_goods.ID,
                            tb_b2b_goods.price_goods,
                            tb_b2b_goods.ID AS goods_id,
                            tb_b2b_goods.purchase_invoice_tax_rate,
                            tb_b2b_order.PO_ID,
                            tb_b2b_ship_list.id AS ship_list_id,
                            SUM(
                                tb_wms_stream.unit_price * tb_wms_stream.send_num
                            ) AS sum_shipping_cost,
                            SUM(
                                (
                                    tb_wms_stream.unit_price * tb_wms_stream.send_num
                                ) / (
                                    1 + (
                
                                        IF (
                                            tb_ms_cmn_cd.CD_VAL,
                                            REPLACE (tb_ms_cmn_cd.CD_VAL, '%', ''),
                                            0
                                        ) / 100
                                    )
                                )
                            ) AS sum_shipping_cost_excludeing_tax
                        FROM
                            (
                                tb_b2b_ship_list,
                                tb_wms_bill,
                                tb_wms_stream,
                                tb_b2b_order,
                                (
                                    SELECT
                                        *
                                    FROM
                                        tb_b2b_goods
                                    GROUP BY
                                        order_id,
                                        sku_id
                                ) AS tb_b2b_goods
                            )
                        LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_b2b_goods.purchase_invoice_tax_rate
                        WHERE
                            tb_b2b_order.ID = tb_b2b_goods.ORDER_ID
                        $order_where
                        AND tb_b2b_ship_list.ORDER_ID = tb_b2b_goods.ORDER_ID
                        AND (
                         tb_b2b_ship_list.order_batch_id = tb_wms_bill.link_bill_id
                         OR
                         (tb_b2b_ship_list.out_bill_id = tb_wms_bill.bill_id
                         AND (tb_wms_bill.bill_type IN (
                            'N000950100',
                            'N000950200',
                            'N000950300',
                            'N000950400',
                            'N000950500',
                            'N000950600',
                            'N000950700',
                            'N000950905',
                            'N000950906'
                        ) OR tb_wms_bill.type = 0)))
                        AND tb_wms_stream.bill_id = tb_wms_bill.id
                        AND tb_wms_stream.GSKU = tb_b2b_goods.sku_id
                        AND tb_wms_bill.warehouse_id = tb_b2b_ship_list.warehouse
                        GROUP BY
                            $group_string_ship
                    ) AS t11,
                    (
                        SELECT
                            tb_b2b_goods.order_id,
                            tb_b2b_goods.ID,
                            tb_b2b_goods.price_goods,
                            tb_b2b_goods.ID AS goods_id,
                            tb_b2b_goods.purchase_invoice_tax_rate,
                            tb_b2b_info.PO_ID,
                            tb_b2b_info.po_time,
                            tb_b2b_goods.currency,
                            tb_b2b_ship_list.id AS ship_list_id,
                            tb_b2b_ship_list.warehouse,
                            SUM(
                              
                                            tb_b2b_goods.price_goods * tb_b2b_ship_goods.DELIVERED_NUM
                                ) AS sum_shipping_revenue,
                            SUM(
                                (
                              
                                    tb_b2b_goods.price_goods * tb_b2b_ship_goods.DELIVERED_NUM
                                ) / (
                                    1 + (
                
                                        IF (
                                            cd_2.CD_VAL,
                                            REPLACE (cd_2.CD_VAL, '%', ''),
                                            0
                                        ) / 100
                                    )
                                )
                            ) AS sum_shipping_revenue_excludeing_tax,
                            SUM(
                                (
                              
                                    tb_b2b_goods.price_goods * tb_b2b_ship_goods.DELIVERED_NUM * 
                                    (CASE cd_3.CD_VAL
                                        WHEN 'USD' THEN tb_ms_xchr.USD_XCHR_AMT_CNY
                                        WHEN 'EUR' THEN tb_ms_xchr.EUR_XCHR_AMT_CNY
                                        WHEN 'HKD' THEN tb_ms_xchr.HKD_XCHR_AMT_CNY
                                        WHEN 'SGD' THEN tb_ms_xchr.SGD_XCHR_AMT_CNY
                                        WHEN 'AUD' THEN tb_ms_xchr.AUD_XCHR_AMT_CNY
                                        WHEN 'GBP' THEN tb_ms_xchr.GBP_XCHR_AMT_CNY
                                        WHEN 'CAD' THEN tb_ms_xchr.CAD_XCHR_AMT_CNY
                                        WHEN 'MYR' THEN tb_ms_xchr.MYR_XCHR_AMT_CNY
                                        WHEN 'DEM' THEN tb_ms_xchr.DEM_XCHR_AMT_CNY
                                        WHEN 'MXN' THEN tb_ms_xchr.MXN_XCHR_AMT_CNY
                                        WHEN 'THB' THEN tb_ms_xchr.THB_XCHR_AMT_CNY
                                        WHEN 'PHP' THEN tb_ms_xchr.PHP_XCHR_AMT_CNY
                                        WHEN 'IDR' THEN tb_ms_xchr.IDR_XCHR_AMT_CNY
                                        WHEN 'TWD' THEN tb_ms_xchr.TWD_XCHR_AMT_CNY
                                        WHEN 'VND' THEN tb_ms_xchr.VND_XCHR_AMT_CNY
                                        WHEN 'KRW' THEN tb_ms_xchr.KRW_XCHR_AMT_CNY
                                        WHEN 'JPY' THEN tb_ms_xchr.JPY_XCHR_AMT_CNY
                                        WHEN 'CNY' THEN tb_ms_xchr.CNY_XCHR_AMT_CNY
                                        END)
                                     
                                ) / (
                                    1 + (
                
                                        IF (
                                            cd_2.CD_VAL,
                                            REPLACE (cd_2.CD_VAL, '%', ''),
                                            0
                                        ) / 100
                                    )
                                )
                            ) AS sum_shipping_revenue_excludeing_tax_cny
                        FROM
                            (
                                tb_b2b_ship_list,
                                tb_b2b_ship_goods,
                                tb_b2b_info,
                                tb_b2b_goods,
                                tb_ms_xchr
                            )
                        LEFT JOIN tb_ms_cmn_cd AS cd_2 ON cd_2.CD = tb_b2b_info.TAX_POINT
                        LEFT JOIN tb_ms_cmn_cd AS cd_3 ON cd_3.CD = tb_b2b_goods.currency
                        WHERE
                            tb_b2b_info.ORDER_ID = tb_b2b_goods.ORDER_ID
                        $order_where
                        AND tb_b2b_ship_list.order_id = tb_b2b_goods.ORDER_ID
                        AND tb_b2b_ship_goods.goods_id = tb_b2b_goods.ID
                        AND tb_b2b_ship_goods.SHIP_ID = tb_b2b_ship_list.ID
                        AND tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(
                                tb_b2b_info.po_time,
                                '%Y%m%d'
                                )
                        GROUP BY
                            $group_string_ship
                    ) AS t12
                WHERE
                    t11.order_id = t12.order_id
                $where_ship_group";
        $sql = DataModel::cleanLineFeed($sql);
        $res_db = $Model->query($sql);
        return $res_db;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function infoFilter($data)
    {
        $temp_data = $data[0];
        if (DataModel::isJson($temp_data['po_erp_path'])) {
            $temp_data['PO_FILFE_PATH'] = $temp_data['po_erp_path'] = json_decode($temp_data['po_erp_path'], true);
        } elseif (strpos($temp_data['po_erp_path'], ',')) {
            $temp_data['po_erp_path'] = explode(',', $temp_data['po_erp_path']);
        }
        $B2bServer = new B2bService();
        $temp_data['whole_vat'] = $B2bServer->getB2bDetailWholeVat($temp_data['ORDER_ID'],
            $temp_data['drawback_estimate'], $temp_data['po_time'], $temp_data['backend_currency'],
            $temp_data);
        $data[0] = $temp_data;
        return $data;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function goodsFilter($data, $tax_point_val_decimal)
    {
        $data = $this->joinExcludingTax($data,
            'purchase_invoice_tax_rate',
            'price_goods', $tax_point_val_decimal);

        if ($data[0]['delivery_prices']) {
            $cd_arr = ['N00130', 'N00128', 'N00129', 'N00059'];
            $team_arr = CodeModel::getCodeKeyValArr($cd_arr, null);
            $data = CodeModel::autoCodeTwoVal($data, ['purchase_invoice_tax_rate']);

            $data = $this->joinExcludingTax($data,
                'purchase_invoice_tax_rate',
                'purchasing_price');
            $B2bService = new B2bService();
            foreach ($data as $k => $v) {
                if (empty($v['batch_id'])) {
                    $v['purchase_order_no'] = $v['procurement_number'];
                    $v['purchase_po_number'] = null;
                } else {
                    $db_purchase = $B2bService->getPurchaseOrder(null, [$v['batch_id']])[0];
                    $v['purchase_order_no'] = $db_purchase['purchase_order_no'];
                    $v['purchase_po_number'] = $db_purchase['purchase_po_number'];
                }
                $v['purchasing_team_val'] = $team_arr[$v['purchasing_team']];
                $v['purchasing_currency_val'] = $team_arr[$v['purchasing_currency']];
                $v['introduce_team_val'] = $team_arr[$v['introduce_team']];
                $v['purchasing_num'] = $v['required_quantity'];
                $v['purchasing_num_show'] = round($v['purchasing_num'] * $v['price_goods'], 4);
                if ($v['percent_sale'] + $v['percent_purchasing'] + $v['percent_introduce'] <= 1) {
                    $v['percent_sale'] = $v['percent_sale'] * 100;
                    $v['percent_purchasing'] = $v['percent_purchasing'] * 100;
                    $v['percent_introduce'] = $v['percent_introduce'] * 100;
                }
                if (empty($data_new[$v['SKU_ID']])) {
                    $data_new[$v['SKU_ID']] = $v;
                    $data_new[$v['SKU_ID']]['delivery_prices'] = array();
                } else {
                    $data_new[$v['SKU_ID']]['required_quantity'] += $v['required_quantity'];
                    $data_new[$v['SKU_ID']]['SHIPPED_NUM'] += $v['SHIPPED_NUM'];
                    $data_new[$v['SKU_ID']]['is_inwarehouse_num'] += $v['is_inwarehouse_num'];
                }
                $data_new[$v['SKU_ID']]['delivery_prices'][] = $v;
            }
            $data_new = array_values($data_new);
        }
        if (empty($data_new)) $data_new = $data;
        $data_new = array_map(function ($valuse) {
            $valuse['inbound_difference'] = $valuse['required_quantity'] - ($valuse['normal_goods'] + $valuse['normal_cargo']);
            return $valuse;
        }, $data_new);
        return $data_new;
    }


//    发货

    /**
     * add todoing ship list
     *
     * @param $o    order_id
     * @param $g    goods_arr
     */
    private function do_list_sync($o, $g, $sum)
    {
        $data['ORDER_ID'] = $o['ORDER_ID'];
        $data['PO_ID'] = $o['PO_ID'];
        $data['CLIENT_NAME'] = $o['CLIENT_NAME'];
        $data['delivery_warehouse_code'] = $this->join_ware_arr_code($o['ORDER_ID']);
        $data['target_port'] = $o['TARGET_PORT'];

        $data['todo_sent_num'] = $data['order_num'] = $sum;
        $data['sent_num'] = 0;

        $data['order_date'] = $o['po_time']; // po time to order date
        $data['sales_team_code'] = $o['SALES_TEAM'];
        // 发货状态
        $data['shipping_status'] = 1;
        $Models = new Model();
        // $Models->startTrans();
        $doship_id = $Models->table('tb_b2b_doship')->data($data)->add();
        return $doship_id;
    }

    /**
     * 增加自身订单仓库CODE
     */
    public function join_ware_arr_code($order_id = null)
    {
        $arr[] = ['ORDER_ID' => $order_id];
        // $arr = $this->get_warehouse($arr);
        foreach ($arr[0]['warehouse'] as $v) {
            $wareshouse_arr .= empty($wareshouse_arr) ? $v->scalar : ',' . $v->scalar;
        }
        return $wareshouse_arr;
    }


    /**do_ship_list
     * 待发货列表
     */
    public function do_ship_list()
    {
        $model = new Model();
        $Doship = M('doship', 'tb_b2b_');
        $getdata = $this->_param();
        $this->action['shipping_status'] = empty($getdata['shipping_status']) ? 0 : $getdata['shipping_status'];
        $this->action['CLIENT_NAME'] = empty($getdata['CLIENT_NAME']) ? '' : $getdata['CLIENT_NAME'];
        $this->action['PO_ID'] = empty($getdata['PO_ID']) ? '' : $getdata['PO_ID'];
        $this->action['delivery_warehouse_code'] = empty($getdata['delivery_warehouse_code']) ? '' : $getdata['delivery_warehouse_code'];
        $this->action['sales_team_code'] = empty($getdata['sales_team_code']) ? '' : $getdata['sales_team_code'];
        $this->action['lately_time_action'] = $getdata['lately_time_action'];
        $this->action['lately_time_action'] = $getdata['lately_time_action'];
        $this->action['expect_goods_time'] = $getdata['expect_goods_time'];
        $this->action['sales_assistant_by'] = $getdata['sales_assistant_by'];
        $this->action['delivery_by'] = $getdata['delivery_by'];
        $this->action['sku_or_barcode'] = $getdata['sku_or_barcode'];

        $where = B2bModel::joinwhere($getdata, 'do_ship_list');
        if ($getdata['expect_goods_time']) {
            if (is_array($getdata['expect_goods_time'])) {
                $where = WhereModel::getBetweenDate($getdata['expect_goods_time']['start'], $getdata['expect_goods_time']['end'], $where, 'tb_b2b_info.delivery_time');
            } else {
                $where['tb_b2b_info.delivery_time'] = $getdata['expect_goods_time'];
            }
        }
        if ($getdata['sku_or_barcode']) {
            $complex['ps.sku_id'] = $getdata['sku_or_barcode'];
            $complex['ps.upc_id'] = $getdata['sku_or_barcode'];
            $complex['_string'] = "FIND_IN_SET('{$getdata['sku_or_barcode']}',upc_more)";
            $complex['_logic'] = 'or';
            $where['_complex'] = $complex;
        }
        $warehouse_arr = [];
        if ($getdata['delivery_warehouse_code']) {
            $warehouse_arr[] = $getdata['delivery_warehouse_code'];
        }
        if ($getdata['delivery_by']) {
            $warehouse_arr = $this->getOutBoundByWarehouse($getdata['delivery_by']);
            if ($getdata['delivery_warehouse_code']) {
                array_push($warehouse_arr, $getdata['delivery_warehouse_code']);
            }
        }
        $page_num = I('page_num') > 0 ? I('page_num') : 10;
        if ($getdata['PO_ID']) {
            $po_ids = strReplaceComma($getdata['PO_ID']);
            //$po_ids = array_slice(explode(',', $getdata['PO_ID']), 0, 10);
        }
        /*switch ($getdata['orderId']) {
            case 'THR_PO_ID':
                $this->action['orderId'] = 'THR_PO_ID';
                if (isset($po_ids)) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
                } elseif ($getdata['PO_ID']) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('LIKE', B2bModel::check_v($getdata['PO_ID'], 'LIKE'));
                }
                unset($where['tb_b2b_doship.PO_ID']);
                break;
            case 'PO_ID':
                if (isset($po_ids)) {
                    unset($where['tb_b2b_doship.PO_ID']);
                    $where['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
                }
                $this->action['orderId'] = 'PO_ID';
                break;
        }*/

        if (isset($po_ids)) {
            $condition['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
            $condition['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
            unset($where['tb_b2b_doship.PO_ID']);
        } else if ($getdata['PO_ID']) {
            unset($where['tb_b2b_doship.PO_ID']);
            $condition['tb_b2b_info.THR_PO_ID'] = B2bModel::check_v($getdata['PO_ID'], '');
            $condition['tb_b2b_info.PO_ID'] = B2bModel::check_v($getdata['PO_ID'], '');
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
        }
        import('ORG.Util.Page');

        if (!empty($warehouse_arr)) {
            $where_string = " tb_b2b_doship.ID IN (t4.ID)";
            if ($getdata['sales_assistant_by']) {
                $where_string .= " AND (tb_b2b_info.PO_USER = '{$getdata['sales_assistant_by']}' OR tb_con_division_client.sales_assistant_by = '{$getdata['sales_assistant_by']}')";
            }
            $model = new Model();
            $table_str = $this->getWarehouseSearchTableStr($warehouse_arr);
            $sql = $model->table($table_str)
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID ')
                ->join('left join tb_con_division_warehouse on tb_con_division_warehouse.warehouse_cd = tb_b2b_doship.ORDER_ID ')
                ->join('left join tb_b2b_goods bg on bg.ORDER_ID = tb_b2b_doship.ORDER_ID')
                ->join(PMS_DATABASE . '.product_sku ps on ps.sku_id = bg.sku_id')
                ->where($where)
                ->where($where_string, null, true)
                ->group('tb_b2b_doship.ID');

            if (!empty($getdata['CLIENT_NAME']) || !empty($getdata['sales_assistant_by'])) {
                $sql->join('tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                    ->join('tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
            }
            $sql_clone1 = clone $sql;

            $count = count($sql->field('tb_b2b_doship.ID')->select());

            $Page = new Page($count, $page_num);
            $Page->page_num = $page_num;
            $show = $Page->show();
            $doship_list = $sql_clone1
                ->field('tb_b2b_doship.*,tb_b2b_info.po_time,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.SALES_TEAM,tb_b2b_info.TARGET_PORT')
                ->order('tb_b2b_doship.ID DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $where_string = '';
            if ($getdata['sales_assistant_by']) {
                $where_string = " (tb_b2b_info.PO_USER = '{$getdata['sales_assistant_by']}' OR tb_con_division_client.sales_assistant_by = '{$getdata['sales_assistant_by']}')";
            }
            $other_sql = $model->table('tb_b2b_doship')
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID ')
                ->join('left join tb_b2b_goods bg on bg.ORDER_ID = tb_b2b_doship.ORDER_ID')
                ->join(PMS_DATABASE . '.product_sku ps on ps.sku_id = bg.sku_id')
                ->where($where)
                ->where($where_string, null, true)
                ->group('tb_b2b_doship.ID');


            if (!empty($getdata['CLIENT_NAME']) || !empty($getdata['sales_assistant_by'])) {
                $other_sql->join('tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                    ->join('tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
            }
            $other_sql_clone = clone $other_sql;

            $count = count($other_sql->select());

            $Page = new Page($count, $page_num);
            $Page->page_num = $page_num;
            $show = $Page->show();
            $doship_list = $other_sql_clone
                ->field('tb_b2b_doship.*,tb_b2b_info.po_time,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.SALES_TEAM,tb_b2b_info.TARGET_PORT')
                ->order('tb_b2b_doship.ID DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }

        // $doship_list = $this->get_warehouse($doship_list);
        $this->assign('doship_list', B2bModel::set_json($doship_list));

        $initdata['sales_team'] = B2bModel::get_sales_team();
        $initdata['warehouse_cd_arr'] = CodeModel::getCodeKeyValArr(['N00068']);
        $initdata['show_warehouse'] = StockModel::get_show_warehouse();
        $initdata['area'] = B2bModel::get_area();
        $this->assign('initdata', B2bModel::set_json($initdata));

        $this->assign('all_warehouse', B2bModel::set_json(StockModel::get_all_warehouse()));
        $this->assign('ship_state', B2bModel::set_json(B2bModel::get_code_lang('ship_state')));
        $this->assign('action', B2bModel::set_json($this->action));
        $this->assign('count', B2bModel::set_json($count));
        $this->assign('pages', $show);
        $this->display();
    }

    private function getOutBoundByWarehouse($b2b_order_outbound_by)
    {
        $Model = new Model();
        $where['b2b_order_outbound_by'] = $b2b_order_outbound_by;
        $db_arr = $Model->table('tb_con_division_warehouse')
            ->field('warehouse_cd')
            ->where($where)
            ->select();
        return array_column($db_arr, 'warehouse_cd');
    }

    /**
     * 待发货子列表
     */
    public function do_ship()
    {
        $order_id = I('order_id');
        $Doship = M('doship', 'tb_b2b_');
        $where['tb_b2b_doship.ORDER_ID'] = $order_id;
        $doship = $Doship->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID')
            ->field('tb_b2b_doship.*,tb_b2b_info.PO_ID,tb_b2b_info.THR_PO_ID,tb_b2b_info.REMARKS,tb_b2b_info.DELIVERY_METHOD,tb_b2b_info.PO_USER,tb_b2b_info.po_time')
            ->find();
        $Goods = M('goods', 'tb_b2b_');
        /*
         *
         *  N000830100 is b5c
        */
        $doship_goods = $Goods->where('tb_b2b_goods.ORDER_ID = \'' . $order_id . '\'')
            ->field('tb_b2b_goods.ID,tb_b2b_goods.ORDER_ID,tb_b2b_goods.SKU_ID,tb_b2b_goods.sku_show,tb_b2b_goods.goods_title,tb_b2b_goods.goods_info,tb_b2b_goods.warehouse_code,tb_b2b_goods.price_goods,sum(tb_b2b_goods.required_quantity) as required_quantity,sum(tb_b2b_goods.SHIPPED_NUM) as SHIPPED_NUM,sum(tb_b2b_goods.TOBE_DELIVERED_NUM) as TOBE_DELIVERED_NUM,sum(tb_b2b_goods.is_inwarehouse_num) as is_inwarehouse_num,tb_b2b_goods.is_tax_rebate,tb_b2b_goods.tax_rebate_ratio,tb_b2b_goods.purchasing_team,tb_b2b_goods.introduce_team,tb_b2b_goods.is_inwarehouse_num,tb_b2b_goods.currency,tb_b2b_goods.SKU_ID_back,tb_b2b_goods.sku_show_back,tb_b2b_goods.jdesc,tb_b2b_goods.percent_sale,tb_b2b_goods.percent_purchasing,tb_b2b_goods.percent_introduce,tb_b2b_goods.purchasing_price,tb_b2b_goods.purchasing_currency,tb_b2b_goods.purchasing_num,tb_b2b_goods.delivery_prices,tb_b2b_goods.batch_id,tb_b2b_goods.batch_code,tb_b2b_goods.batch_json,sc.sale')
            ->join('left join (select SKU_ID,channel,sale from tb_wms_center_stock  where tb_wms_center_stock.channel = \'N000830100\' ) sc on sc.SKU_ID = tb_b2b_goods.SKU_ID')
            ->group('tb_b2b_goods.SKU_ID')
            ->select();
        foreach ($doship_goods as $d) {
            $gudsId[] = substr($d['SKU_ID'], 0, -2);
        }

        /*$Guds = M('guds', 'tb_ms_');
        $where_guds['GUDS_ID'] = array('in', $gudsId);
        $guds = $Guds->where($where_guds)->field('GUDS_ID,DELIVERY_WAREHOUSE')->select();
        $guds_col = array_column($guds, 'DELIVERY_WAREHOUSE', 'GUDS_ID');
        $hwarehouse = array_column($guds, 'DELIVERY_WAREHOUSE', 'DELIVERY_WAREHOUSE');*/

        $set_goods_batch = B2bModel::set_json(B2bModel::batchStockGet($doship_goods, $doship['sales_team_code']));
        $goods_batch_arr = json_decode($set_goods_batch, true);
        foreach ($doship_goods as &$d) {
            // $d['DELIVERY_WAREHOUSE'] = $guds_col[substr($d['SKU_ID'], 0, -2)];
            $d['goods'] = $goods_batch_arr[$d['SKU_ID']];
            if (empty($d['goods'])) {
                $d['goods'] = [];
            }
            $d['purchasing_team_val'] = json_decode(RedisModel::get_key('CMN_CD_' . $d['purchasing_team']), true)['cdVal'];
        }

        $doship_goods = SkuModel::getInfo($doship_goods, 'sku_show',
            ['spu_name', 'attributes', 'image_url', 'product_sku'],
            ['spu_name' => 'goods_title', 'attributes' => 'goods_info', 'image_url' => 'guds_img_cdn_addr']
        );
        $doship_goods = SkuModel::getTableAttr($doship_goods);

        foreach ($doship_goods as $k =>  $item) {
            if(isset($item['product_sku']) && isset($item['product_sku']['upc_more']) &&
             !empty($item['product_sku']['upc_more'])) {
                $upc_more_arr = explode(',', $item['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $item['product_sku']['upc_id']);
                $doship_goods[$k]['bar_code'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
            }
        }

        $require['order_id'] = $order_id;
        $sku_batch = $this->getSkuOccupy($require);
        if ($sku_batch['code'] = 200000) {
            $doship_goods = $this->joinGoodsBatch($doship_goods, $sku_batch);
        }
        Logs($doship_goods, $doship_goods, 'do_ship');
        $initdata['area'] = B2bModel::get_area();
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');

        // order data
        $order_data = B2bData::oneOrderBasic($doship['ORDER_ID']);
        $doship['fmt_order_data'] = $order_data;

        $this->assign('goods_batch', $set_goods_batch);
        $this->assign('doship', B2bModel::set_json($doship));
        $this->assign('hwarehouse', B2bModel::set_json([]));
        $this->assign('currency', B2bModel::set_json(B2bModel::get_currency()));
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('all_warehouse', B2bModel::set_json(StockModel::get_all_warehouse()));
        $this->assign('cd_warehouse', B2bModel::set_json(B2bModel::get_code_cd('N00068')));
        $this->assign('doship_goods', B2bModel::set_json($doship_goods));
        $this->display();
    }

    /**
     * @param $doship_goods
     * @param $sku_batch
     *
     * @return mixed
     */
    private function joinGoodsBatch($doship_goods, $sku_batch)
    {
        Logs(json_encode($doship_goods), 'joinGoodsBatch');
        foreach ($doship_goods as $key => $value) {
            $warehouse_occupy_arr = array_column($sku_batch['data'][$value['SKU_ID']], 'occupy_num', 'delivery_warehouse');
            foreach ($value['goods'] as $k => $v) {
                $doship_goods[$key]['goods'][$k]['availableForSale'] += $warehouse_occupy_arr[$v['deliveryWarehouse']];
            }
            unset($warehouse_occupy_arr);
        }
        return $doship_goods;
    }

    /**
     *
     */
    public function do_ship_confirm()
    {
        $this->do_ship_data();
        $this->display();
    }

    /**
     * 待发货详情
     */
    public function do_ship_show()
    {
        $this->all_ship_data();
        $this->display();
    }

    /**
     * @todo 出库负责人
     */
    public function all_ship_data()
    {
        //        多操作人？
        $order_id = I('order_id');
        $Doship = M('doship', 'tb_b2b_');
        $where['tb_b2b_doship.ORDER_ID'] = $order_id;
        $doship = $Doship->where($where)
            ->field('tb_b2b_doship.*,
            tb_b2b_info.delivery_time,
            tb_b2b_info.our_company,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.REMARKS,
            tb_b2b_info.DELIVERY_METHOD,
            tb_b2b_info.PO_USER,
            tb_b2b_info.po_time,
            tb_b2b_info.po_currency,
            tb_b2b_order.make_send_by,
            tb_b2b_order.make_send_at,
            tb_con_division_client.sales_assistant_by,
            tb_b2b_ship_list.*')
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID')
            ->join('left join tb_b2b_order on tb_b2b_order.ID = tb_b2b_doship.ORDER_ID')
            ->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
            ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID')
            ->join('left join (select sum(power_all) as power_all_sum,sum(LOGISTICS_COSTS) as logistics_costs_sum,LOGISTICS_CURRENCY ,SUBMIT_TIME,AUTHOR,order_id from tb_b2b_ship_list group by order_id order by ID desc) tb_b2b_ship_list on tb_b2b_ship_list.order_id = tb_b2b_doship.ORDER_ID')
            ->find();
        $doship = CodeModel::autoCodeOneVal($doship, ['po_currency']);
        $Goods = M('goods', 'tb_b2b_');
        $doship_goods = $Goods->where('tb_b2b_goods.ORDER_ID = \'' . $order_id . '\'')
            ->field('tb_b2b_goods.ID,tb_b2b_goods.ORDER_ID,tb_b2b_goods.SKU_ID,tb_b2b_goods.sku_show,tb_b2b_goods.goods_title,tb_b2b_goods.goods_info,tb_b2b_goods.warehouse_code,tb_b2b_goods.price_goods,sum(tb_b2b_goods.required_quantity) as required_quantity,sum(tb_b2b_goods.SHIPPED_NUM) as SHIPPED_NUM,sum(tb_b2b_goods.TOBE_DELIVERED_NUM) as TOBE_DELIVERED_NUM,sum(tb_b2b_goods.is_inwarehouse_num) as is_inwarehouse_num,tb_b2b_goods.is_tax_rebate,tb_b2b_goods.tax_rebate_ratio,tb_b2b_goods.purchasing_team,tb_b2b_goods.introduce_team,tb_b2b_goods.is_inwarehouse_num,tb_b2b_goods.currency,tb_b2b_goods.SKU_ID_back,tb_b2b_goods.sku_show_back,tb_b2b_goods.jdesc,tb_b2b_goods.percent_sale,tb_b2b_goods.percent_purchasing,tb_b2b_goods.percent_introduce,tb_b2b_goods.purchasing_price,tb_b2b_goods.purchasing_currency,tb_b2b_goods.purchasing_num,tb_b2b_goods.delivery_prices,tb_b2b_goods.batch_id,tb_b2b_goods.batch_code,tb_b2b_goods.batch_json,sc.sale')
            ->join('left join (select SKU_ID,channel,sale from tb_wms_center_stock  where tb_wms_center_stock.channel = \'N000830100\' ) sc on sc.SKU_ID = tb_b2b_goods.SKU_ID')
            ->group('tb_b2b_goods.SKU_ID')
            ->select();

        $initdata['area'] = B2bModel::get_area();
        $initdata['currency'] = B2bModel::get_currency();
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');

        $doship_goods = SkuModel::getInfo($doship_goods, 'sku_show',
            ['spu_name', 'attributes', 'image_url', 'product_sku'],
            ['spu_name' => 'goods_title', 'attributes' => 'goods_info', 'image_url' => 'guds_img_cdn_addr']
        );
        $doship_goods = SkuModel::getTableAttr($doship_goods);

        foreach ($doship_goods as $k =>  $item) {
            if(isset($item['product_sku']) && isset($item['product_sku']['upc_more']) &&
             !empty($item['product_sku']['upc_more'])) {
                $upc_more_arr = explode(',', $item['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $item['product_sku']['upc_id']);
                $doship_goods[$k]['bar_code'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
            }
        }

        $this->assign('doship_goods', B2bModel::set_json($doship_goods));

        $Ship_list = M('ship_list', 'tb_b2b_');
        $where_ship['DOSHIP_ID'] = $doship['ID'];
        $ship_list = $Ship_list->where($where_ship)->select();
        $Ship_goods = M('ship_goods', 'tb_b2b_');

        if (count($ship_list)) {
            $where_goods['SHIP_ID'] = array('IN', array_column($ship_list, 'ID'));
            $ship_goods = $Ship_goods->where($where_goods)
                ->join('left join (select SKU_ID,goods_title,goods_info from tb_b2b_goods group by SKU_ID)  tb_b2b_goods on tb_b2b_goods.SKU_ID = tb_b2b_ship_goods.SHIPPING_SKU')
                ->join('left join (select SKU_ID,sale from tb_wms_center_stock  where channel = \'N000830100\'  )  tb_wms_center_stock  on tb_wms_center_stock.SKU_ID =  tb_b2b_ship_goods.SHIPPING_SKU')
                ->select();
            $arr = array();

            $ship_goods = SkuModel::getInfo($ship_goods, 'SKU_ID',
                ['spu_name', 'attributes', 'image_url', 'product_sku'],
                ['spu_name' => 'goods_title', 'attributes' => 'goods_info', 'image_url' => 'guds_img_cdn_addr']
            );
            $ship_goods = SkuModel::getTableAttr($ship_goods);

            foreach ($ship_goods as $v) {
                $arr[$v['SHIP_ID']][] = $v;
            }
        }

        foreach ($ship_list as &$v) {
            if ($arr[$v['ID']]) $v['goods'] = $arr[$v['ID']];
        }
        $ship_list = $this->updateShipListData($ship_list);
        $doship['shipping_status_nm'] = B2bModel::get_code_lang('ship_state')[$doship['shipping_status']]['CD_VAL'];

        $all_excluding_taxs = $this->assemblyTaxMapping(
            $this->assemblyShipExcludingTax([$doship['order_id']], true),
            'order_id'
        );
        $doship = $this->joinShipExcludingData($doship, $all_excluding_taxs, 'ORDER_ID', false);

        $this->assign('doship', B2bModel::set_json($doship));
        $this->assign('ship_list', B2bModel::set_json($ship_list));
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('currency', B2bModel::set_json(B2bModel::get_currency()));
        $all_warehouse = DataModel::toArray(StockModel::get_all_warehouse());
        $this->assign('all_warehouse', B2bModel::set_json($all_warehouse));
    }

    /**
     *
     */
    public function show_ship_data()
    {
        //        多操作人？
        $order_id = I('order_id');
        $Doship = M('doship', 'tb_b2b_');
        $where['tb_b2b_doship.ORDER_ID'] = $order_id;
        $doship = $Doship->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID')
            ->join('left join (select sum(power_all) as power_all_sum,sum(LOGISTICS_COSTS) as logistics_costs_sum,LOGISTICS_CURRENCY ,SUBMIT_TIME,AUTHOR,order_id from tb_b2b_ship_list group by order_id order by ID desc) tb_b2b_ship_list on tb_b2b_ship_list.order_id = tb_b2b_doship.ORDER_ID')
            ->field('tb_b2b_doship.*,tb_b2b_info.REMARKS,tb_b2b_info.DELIVERY_METHOD,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_ship_list.*')
            ->find();

        $Goods = M('goods', 'tb_b2b_');
        $doship_goods = $Goods->where('tb_b2b_goods.ORDER_ID = \'' . $order_id . '\'')
            ->join('left join (select SKU_ID,channel,sale from tb_wms_center_stock  where tb_wms_center_stock.channel = \'N000830100\' ) sc on sc.SKU_ID = tb_b2b_goods.SKU_ID')
            ->field('tb_b2b_goods.*,sc.sale')
            ->select();

        /*$Guds = M('guds', 'tb_ms_');
        $substr_sku = function ($v) {
            return substr($v['SKU_ID'], 0, -2);
        };
        $all_guds = array_map($substr_sku, $doship_goods);
        $where_guds['GUDS_ID'] = array('in', $all_guds);
        $guds = $Guds->field('GUDS_ID,DELIVERY_WAREHOUSE')->where($where_guds)->select();
        $guds_col = array_column($guds, 'DELIVERY_WAREHOUSE', 'GUDS_ID');
        foreach ($doship_goods as $k => &$v) {
            $v['DELIVERY_WAREHOUSE'] = $guds_col[substr($v['SKU_ID'], 0, -2)];
        }*/
        $initdata['area'] = B2bModel::get_area();
        $initdata['currency'] = B2bModel::get_currency();

        $this->assign('doship', B2bModel::set_json($doship));
        $this->assign('ship_list', B2bModel::set_json($doship_goods));
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('currency', B2bModel::set_json(B2bModel::get_currency()));
        $this->assign('all_warehouse', B2bModel::set_json(StockModel::get_all_warehouse()));
    }

    /**
     *
     */
    public function do_ship_data()
    {
        //        多操作人？
        $order_id = I('order_id');
        $Doship = M('doship', 'tb_b2b_');
        $where['tb_b2b_doship.ORDER_ID'] = $order_id;
        $doship = $Doship->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID')
            ->join('left join (select sum(power_all) as power_all_sum,sum(LOGISTICS_COSTS) as logistics_costs_sum,LOGISTICS_CURRENCY ,SUBMIT_TIME,AUTHOR,order_id from tb_b2b_ship_list group by order_id order by ID desc) tb_b2b_ship_list on tb_b2b_ship_list.order_id = tb_b2b_doship.ORDER_ID')
            ->field('tb_b2b_doship.*,tb_b2b_info.REMARKS,tb_b2b_info.DELIVERY_METHOD,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_ship_list.*')
            ->find();

        $Ship_list = M('ship_list', 'tb_b2b_');
        $where_ship['DOSHIP_ID'] = $doship['ID'];
        $ship_list = $Ship_list->where($where_ship)->select();
        $Ship_goods = M('ship_goods', 'tb_b2b_');
        if (count($ship_list)) {
            $where_goods['SHIP_ID'] = array('IN', array_column($ship_list, 'ID'));
            $ship_goods = $Ship_goods->where($where_goods)
                ->join('left join (select SKU_ID,goods_title,goods_info from tb_b2b_goods group by SKU_ID)  tb_b2b_goods on tb_b2b_goods.SKU_ID = tb_b2b_ship_goods.SHIPPING_SKU')
                ->join('left join (select SKU_ID,sale from tb_wms_center_stock  where channel = \'N000830100\'  )  tb_wms_center_stock  on tb_wms_center_stock.SKU_ID =  tb_b2b_ship_goods.SHIPPING_SKU')
                ->select();
            $arr = array();
            foreach ($ship_goods as $v) {
                $arr[$v['SHIP_ID']][] = $v;
            }
        }

        foreach ($ship_list as &$v) {
            if ($arr[$v['ID']]) $v['goods'] = $arr[$v['ID']];
        }

        $initdata['area'] = B2bModel::get_area();
        $initdata['currency'] = B2bModel::get_currency();
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');

        $this->assign('doship', B2bModel::set_json($doship));
        $this->assign('ship_list', B2bModel::set_json($ship_list));
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('currency', B2bModel::set_json(B2bModel::get_currency()));
        $this->assign('all_warehouse', B2bModel::set_json(StockModel::get_all_warehouse()));
    }

    /**
     *
     */
    public function b2bOrderData()
    {
        echo json_encode(B2bModel::orderData($_GET['order_id']), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 发货保存
     */
    public function save_ship($test_data = null)
    {
        try {
            $req = Mainfunc::getInputJson();
            $order_id = $req['params']['goods_info'][0]['ORDER_ID'];
            $old_data = $req['params']['doship']['fmt_order_data'];
            $is_skit = $req['params']['is_skit'];

            $rClineVal = RedisModel::lock('order_id' . $order_id, 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            if ($order_id && $old_data && (!isset($is_skit) || true !== $is_skit)) {
                $changed = BaseCommon::order_changed_by_old($order_id, $old_data);
                if ($changed['is_err']) {
                    $info = '操作失败' . "[{$changed['msg']}]";
                    $res['status'] = 0;
                    $res['data'] = null;
                    $res['info'] = $info;
                    $this->ajaxReturn($res);
                }
            }

            LogsModel::initConfig('b2b_save_ship');
            if (APP_STATUS == 'stage' && $test_data) {
                $post = $test_data->params;
            } else {
                if (!isset($is_skit) || true !== $is_skit) {
                    $this->checkRepeatOrderOperation();
                }
                $post = B2bModel::get_data('params');
            }
            $this->checkWarehouseSendNetIsOne($post);
            Logs($post, 'require_post');
            $Model = new Model();
            $this->Model = $Model;
            //        add ship list in goods
            $Model->startTrans();
            $doship_id = $post->doship_id;
            $order_batch_id = B2bModel::order_batch_id_join($post->doship->PO_ID, $doship_id);
            $goods_batch_json = $post->goods_batch_json;
            $order_id = $post->goods_info[0]->ORDER_ID;
            list($ship_id_arr, $return_goto, $ships_map) = $this->warehouseListCreate($post, $doship_id,
                $order_id, $order_batch_id,
                $goods_batch_json,
                $Model);
            if ($return_goto == 'ajaxretrun') {
                goto  ajaxretrun;
            }
            $msg = '发货信息异常';
            $status = 0;
            if ($ship_id_arr) {
                $ship_ids = array_column($ship_id_arr, 'ship_id', 'DELIVERY_WAREHOUSE');
                $w_ids = array_column($ship_id_arr, 'w_id', 'DELIVERY_WAREHOUSE');
                $upd_sum = 0;
                $doship_goods = $post->goods_info;
                foreach ($doship_goods as $val) {
                    foreach ($val->goods as $v) {
                        if ($v->DELIVERED_NUM > 0) {
                            $v->GOODS_ID = $val->ID;
                            if (empty($v->skuId) && !empty($val->SKU_ID)) {
                                $v->skuId = $val->SKU_ID;
                            }
                            $goods_batch_arr[$v->skuId . $v->deliveryWarehouse] = $v;
                        }
                    }
                }
                // $post = $this->filterSaveGoods($post);
                list($upd_sum, $goods_data, $goods_dataw, $return_goto, $response_msg) = $this->warehouseGoodsCreate(
                    $goods_batch_arr,
                    $ship_ids,
                    $w_ids,
                    $upd_sum,
                    $Model,
                    $order_id);
                if ($return_goto == 'ajaxretrun') {
                    $msg = $response_msg;
                    goto  ajaxretrun;
                }
                Logs($goods_data, '$goods_data');
                if ($goods_data) {
                    if ($upd_sum) {
                        $doship_data = $Model->table('tb_b2b_doship')
                            ->where('ORDER_ID = ' . $order_id)
                            ->field('delivery_warehouse_code,sent_num,todo_sent_num')
                            ->select();
                        $doship_data = $doship_data[0];
                        $num_sum['sent_num'] = $doship_data['sent_num'] + $upd_sum;
                        $num_sum['todo_sent_num'] = $doship_data['todo_sent_num'] - $upd_sum;
                        $num_sum['update_time'] = date("Y-m-d H:i:s");
                        $ship_state_arr = $this->get_code_key('ship_state');
                        $num_sum = $this->ship_state_upd($num_sum, $order_id, $Model);
                        if ($num_sum['todo_sent_num'] < 0) {
                            $Model->rollback();
                            $msg = '待发数据不足';
                            goto ajaxretrun;
                        }
                        $Model->table('tb_b2b_doship')->where('ORDER_ID = ' . $order_id)->save($num_sum);
                        $res = $Model->table('tb_b2b_ship_goods')->addAll($goods_data);
                        $res_w = $Model->table('tb_b2b_warehousing_goods')->addAll($goods_dataw);
                        if ($res && $res_w) {
                            //                       out Warehouse//
                            if (isset($is_skit) && true === $is_skit) {
                                $warehouse_status['code'] = 2000;
                            } else {
                                $warehouse_status = $this->occupyAndOutWarehouse($goods_batch_arr,
                                    $post->doship->PO_ID,
                                    $order_batch_id,
                                    $goods_batch_json,
                                    $post->doship->sales_team_code,
                                    $ships_map
                                );
                            }
                            Logs($warehouse_status, '$warehouse_status');
                            if ($warehouse_status['code'] != 2000) {
                                $Model->rollback();
                                $msg = '错误CODE:' . $warehouse_status['code'] . ',' . $warehouse_status['msg'] . ',' . $warehouse_status['info'];
                            } else {
                                // upd ship list  power all
                                list($ship_type, $all_upd_ship_lists) = $this->sync_ship_power_all($goods_data, $order_id);
                                $Model->commit();
                                if ($num_sum['todo_sent_num'] == 0) {
                                    $require['order_id'] = $order_id;
                                    $res = $this->signSendOut($require);
                                    Logs($res, 'signSendOut');
                                }
                                $this->checkOrUpdateWarehouseListStatus($order_id, $Model);

                                foreach ($all_upd_ship_lists as $ship_id) {
                                    $ship_list = $this->updateShipAllPower($ship_id);
                                    Logs($ship_list);
                                }
                                $this->updateOrderReceivableAccount($order_id);
                                $msg = 'success';
                                $status = 1;
                            }
                        } else {
                            $Model->rollback();
                            $msg = 'error';
                        }
                    } else {
                        $Model->rollback();
                        $msg = '错误订单数量';
                    }
                } else {
                    $Model->rollback();
                    $msg = '商品信息缺失';
                }
            }
            RedisModel::unlock('order_no' . $order_id);
            ajaxretrun:
            $this->ajaxReturn($res, $msg, $status);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxReturn($res);
        }
    }

    /**
     * @param $data
     */
    private function checkWarehouseSendNetIsOne($data)
    {
        $ship_data_arr = DataModel::obj2arr($data)['ships'];
        $warehouse_arr = array_column($ship_data_arr, 'warehouse');
        $B2bService = new B2bService();
        $send_net_arr = $B2bService->getSendNetWarehouseCds();
        $sen_net_count = count(array_intersect($warehouse_arr, $send_net_arr));
        if ($sen_net_count) {
            $res['status'] = 0;
            $res['data'] = null;
            if (1 < count($warehouse_arr)) {
                $res['info'] = '操作失败：' . "包含发网仓必须单独发货";
                $this->ajaxReturn($res);
            }
            foreach ($ship_data_arr as $value) {
                if (is_null($value['is_use_send_net'])) {
                    $res['info'] = '操作失败：' . "是否使用发网物流必选";
                    $this->ajaxReturn($res);
                }
            }
        }
    }

    /**
     * @param $post
     *
     * @return mixed
     */
    private function filterSaveGoods($post)
    {
        $post_arr = DataModel::obj2arr($post);
        $where['ORDER_ID'] = $post['doship']['ORDER_ID'];
        $goods_tobe_num = $this->Model->table('tb_b2b_goods')
            ->field('ID,TOBE_DELIVERED_NUM')
            ->where($where)
            ->select();
        $goods_tobe_num_key_val = array_column($goods_tobe_num, 'TOBE_DELIVERED_NUM', 'ID');
        foreach ($post_arr['goods_info'] as $value) {
            if ($this->checkDeliveredNumBeYond($value, $goods_tobe_num_key_val)) {

            }
        }

        return DataModel::arr2obj($post_arr);
    }

    /**
     * @param $data
     * @param $goods_tobe_num_key_val
     *
     * @return bool
     */
    private function checkDeliveredNumBeYond($data, $goods_tobe_num_key_val)
    {
        $one_goods_tobe_num = array_sum($goods_tobe_num_key_val($data['ID']));
        $all_goods_delivered_num = array_sum(array_column($data['goods'], 'DELIVERED_NUM'));
        if ($one_goods_tobe_num < $all_goods_delivered_num) {
            return true;
        }
        return false;
    }


    /**
     * Release occupancy to order batch
     *
     * @param $order_batch_id
     */
    public function releaseOccupancy($order_batch_id)
    {
        $require['processCode'] = 'BATCH_RELEASE_OCCUPY_PROCESS';
        $require['processId'] = uuid();
        $require['data']['releaseOccupy'][0]['orderId'] = $order_batch_id;
        $res = ApiModel::releaseOccupancy(json_encode($require));
        Logs($res, 'releaseOccupancy', 'b2b');
        return $res;
    }


    /**
     * @param      $goods
     * @param      $order_batch_id
     * @param null $goods_batch_json
     *
     * @return Exception|void
     */
    private function occupyAndOutWarehouse($goods, $order_id, $order_batch_id, $goods_batch_json = null, $sale_team, $ships_map = [])
    {
        $goods_arr = B2bModel::obj2arr($goods);
        Logs($goods_arr, '$goods_arr');
        $occupy_res = null;
        $res_s = $this->outWarehouse($order_batch_id, $goods_arr, $occupy_res, $order_id, $sale_team, $ships_map);
        if (200 == $res_s['code']) $res_s['code'] = 2000;
        return $res_s;
    }

    /**
     * @param $goods
     * @param $order_batch_id
     *
     * @return mixed
     * @throws Exception
     */
    private function defaultBatch($goods, $order_batch_id)
    {
        $batch_data = B2bModel::batchOccupyJoin($goods, $order_batch_id);
        $res = B2bModel::checkOrder($goods, $order_batch_id);
        if (200 != $res['state']) return $res;
        Logs($batch_data, 'batch_data', 'b2b_batch');
        $batchOccupyJson = ApiModel::batchOccupy($batch_data);
        Logs($batchOccupyJson, '$batchOccupyJson', 'b2b_batch');
        $batchOccupy = json_decode($batchOccupyJson, true);
        if (2000 != $batchOccupy['code']) {
            throw new \Exception($batchOccupy['msg'], (int)$batchOccupy['code']);
        }
        return $batchOccupy;
    }

    /**
     * @param      $order_batch_id
     * @param null $goods_arr
     * @param null $res
     *
     * @return array|null
     */
    private function outWarehouse($order_batch_id, $goods_arr, $res = null, $order_id, $sale_team, $ships_map = [])
    {

        $res = B2bModel::go_batch($order_batch_id, $goods_arr, $order_id, $sale_team, $ships_map);
        return $res;
    }


    /**
     * 入库列表
     */
    public function warehousing_list()
    {

        $initdata['all_warehouse'] = StockModel::get_all_warehouse();
        $initdata['sales_team'] = B2bModel::get_sales_team();
        $initdata['area'] = B2bModel::get_area();
        $initdata['warehousing_state'] = B2bModel::get_code_lang('warehousing_state');
        $this->assign('initdata', B2bModel::set_json($initdata));
//
        $getdata = $this->_param();

        $this->action['warehouse'] = empty($getdata['warehouse']) ? '' : $getdata['warehouse'];
        $this->action['PO_ID'] = empty($getdata['PO_ID']) ? '' : $getdata['PO_ID'];
        $this->action['CLIENT_NAME'] = empty($getdata['CLIENT_NAME']) ? '' : $getdata['CLIENT_NAME'];
        $this->action['SALES_TEAM'] = empty($getdata['SALES_TEAM']) ? '' : $getdata['SALES_TEAM'];
        $this->action['AUTHOR'] = empty($getdata['AUTHOR']) ? '' : $getdata['AUTHOR'];
        $this->action['DOSHIP_ID'] = empty($getdata['DOSHIP_ID']) ? '' : $getdata['DOSHIP_ID'];
        $this->action['BILL_LADING_CODE'] = empty($getdata['BILL_LADING_CODE']) ? '' : $getdata['BILL_LADING_CODE'];
        $this->action['sales_assistant_by'] = empty($getdata['sales_assistant_by']) ? '' : $getdata['sales_assistant_by'];
        $this->action['sku_or_barcode'] = empty($getdata['sku_or_barcode']) ? '' : $getdata['sku_or_barcode'];

        if ($getdata['S'] || $getdata['U']) {
            $submit_time = [$getdata['S'], $getdata['U']];
        }
        $this->action['SUBMIT_TIME'] = empty($submit_time) ? '' : $submit_time;
        $where = B2bModel::joinwhere($getdata, 'b2bwarehousing');

        if ($getdata['sku_or_barcode']) { // sku或条形码精确搜索
            $complex['ps.sku_id'] = $getdata['sku_or_barcode'];
            $complex['ps.upc_id'] = $getdata['sku_or_barcode'];
            $complex['_string'] = "FIND_IN_SET('{$getdata['sku_or_barcode']}',ps.upc_more)";
            $complex['_logic'] = 'or';
            $where['_complex'] = $complex;
        }
        $where_string = '';
        if ($getdata['sales_assistant_by']) { // 销售或销售助理
            $where_string = "  (tb_b2b_info.PO_USER = '{$getdata['sales_assistant_by']}' OR tb_con_division_client.sales_assistant_by = '{$getdata['sales_assistant_by']}')";
        }
        if ($getdata['PO_ID']) {
            $po_ids =  strReplaceComma($getdata['PO_ID']);
        }
        /*switch ($getdata['orderId']) {
            case 'THR_PO_ID':
                $this->action['orderId'] = 'THR_PO_ID';
                if (isset($po_ids)) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
                } elseif ($getdata['PO_ID']) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('LIKE', B2bModel::check_v($getdata['PO_ID'], 'LIKE'));
                }
                unset($where['tb_b2b_info.PO_ID']);
                break;
            case 'PO_ID':
                if (isset($po_ids)) {
                    unset($where['tb_b2b_info.PO_ID']);
                    $where['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
                }
                $this->action['orderId'] = 'PO_ID';
                break;
        }*/
        if (isset($po_ids)) {
            $condition['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
            $condition['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
            unset($where['tb_b2b_info.PO_ID']);
        } else if ($getdata['PO_ID']) {
            unset($where['tb_b2b_info.PO_ID']);
            $condition['tb_b2b_info.THR_PO_ID'] = B2bModel::check_v($getdata['PO_ID'], '');
            $condition['tb_b2b_info.PO_ID'] = B2bModel::check_v($getdata['PO_ID'], '');
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
        }
        if ($getdata['status'] === '0' || !empty($getdata['status'])) {
            $this->action['status'] = $getdata['status'];
            if ($getdata['status'] != 0) $where['tb_b2b_warehouse_list.status'] = $getdata['status'];
            if ($getdata['status'] == 1) $where['tb_b2b_warehouse_list.status'] = $getdata['status'] - 1;
        } else {
            $this->action['status'] = 0;
        }
        $Warehouse_list = M('warehouse_list', 'tb_b2b_');
        import('ORG.Util.Page');
        $sql = $Warehouse_list->where($where)
            ->where($where_string, null, true)
            ->join('tb_b2b_info on tb_b2b_info.ORDER_ID =  tb_b2b_warehouse_list.ORDER_ID')
            ->join('tb_b2b_doship on tb_b2b_doship.ID =  tb_b2b_warehouse_list.DOSHIP_ID')
            ->join('tb_b2b_ship_list on tb_b2b_ship_list.ID =  tb_b2b_warehouse_list.SHIP_LIST_ID')
            ->join('tb_b2b_warehousing_goods wg on wg.warehousing_id =  tb_b2b_warehouse_list.ID')
            ->join(PMS_DATABASE . '.product_sku ps on ps.sku_id = wg.warehouse_sku');

        if (isset($where['tb_b2b_info.CLIENT_NAME']) || !empty($getdata['sales_assistant_by'])) {
            $sql->join('tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }
        $sql_clone = clone $sql;
        $count_result = $sql->order('tb_b2b_warehouse_list.ID desc')
            ->field('tb_b2b_warehouse_list.ID')
            ->group('tb_b2b_warehouse_list.ID')
            ->select();
        $count = count($count_result);
        $count = empty($count) ? '0' : $count;
        $Page = new Page($count, 10);
        $show = $Page->show();
        $data = $sql_clone->order('tb_b2b_warehouse_list.ID desc')
            ->field('tb_b2b_warehouse_list.*,tb_b2b_info.THR_PO_ID,tb_b2b_info.CLIENT_NAME,tb_b2b_info.PO_ID,tb_b2b_info.TARGET_PORT,tb_b2b_info.po_time,tb_b2b_doship.sent_num,tb_b2b_ship_list.SHIPMENTS_NUMBER,tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.BILL_LADING_CODE')
            ->group('tb_b2b_warehouse_list.ID')
            ->limit($Page->firstRow . ',' . $Page->listRows)->select();
//        $datas = $this->get_warehouse($data);
        $datas = $data;
        $this->assign('data', B2bModel::set_json($datas));
        $this->assign('action', B2bModel::set_json($this->action));
        $this->assign('count', $count);
        $this->assign('page', $show);


        $this->display();
    }

    /**
     * 获取入库数据
     */
    public function get_warehousing()
    {
        $order_id = I('ORDER_ID');
        $ID = I('ID');
        $Model = new Model();
        $where['tb_b2b_warehouse_list.ORDER_ID'] = $order_id;
        if ($ID) $where['tb_b2b_warehouse_list.ID'] = $ID;
        $warehousing_info = $Model->table('tb_b2b_warehouse_list')
            ->field('tb_b2b_info.*,
            sum(tb_b2b_warehouse_list.SHIPMENTS_NUMBER) as SHIPMENTS_NUMBER_all,
            tb_b2b_warehouse_list.*,tb_b2b_warehouse_list.ID AS warehouse_list_id,
            sum(WAREHOUSEING_NUM) as WAREHOUSEING_NUMS,
            sum(DEVIATION_NUM) as DEVIATION_NUMS,sum(AGAIN_WAREING_NUM) as AGAIN_WAREING_NUMS,
            sum(RECOVE_MONEY) as RECOVE_MONEYS,
            tb_b2b_ship_list.power_all as power_all_sum,
            tb_b2b_ship_list.LOGISTICS_COSTS as logistics_costs_sum,
            tb_b2b_ship_list.LOGISTICS_CURRENCY,
            tb_b2b_ship_list.order_id,
            tb_b2b_ship_list.SUBMIT_TIME')
            ->join('tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_warehouse_list.ORDER_ID')
            ->join('left join tb_b2b_ship_list on tb_b2b_ship_list.ID = tb_b2b_warehouse_list.SHIP_LIST_ID')
            ->where($where)
            ->group('tb_b2b_warehouse_list.ORDER_ID')
            ->find();
        if (!empty($warehousing_info)) {
            $warehousing_info['receivable_status'] = M('receivable', 'tb_b2b_')->where(['order_id' => $warehousing_info['order_id']])->getField('receivable_status');
        }
//        get ship list
        $ship_list = $this->get_warehousing_goods($order_id, $ID);
        $initdata['all_warehouse'] = StockModel::get_all_warehouse();
        $initdata['sales_team'] = B2bModel::get_sales_team();
        $initdata['deviation_cause'] = B2bModel::get_code('deviation_cause');
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');
        $initdata['is_no_arr'] = array_column(B2bModel::get_code('is_no'), 'CD_VAL');
        $initdata['area'] = B2bModel::get_area();
        $initdata['currency'] = B2bModel::get_currency();
        if (!DataModel::isJson($ship_list[0]['file_name'])) {
            $temp['name'] = $ship_list[0]['file_name'];
            $temp['save_name'] = $ship_list[0]['VOUCHER_ADDRESS'];
            $temp_arr[] = $temp;
            $ship_list[0]['file_name'] = DataModel::arrToJson($temp_arr);
        }
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('url', '&ORDER_ID=' . $order_id . '&ID=' . $ID);
        $warehousing_info = CodeModel::autoCodeOneVal($warehousing_info, ['return_warehouse_cd', 'tally_type_cd']);
        $ship_list = CodeModel::autoCodeTwoVal($ship_list, ['return_warehouse_cd']);
        $this->assign('warehousing_info', B2bModel::set_json($warehousing_info));
        $this->assign('ship_list', B2bModel::set_json($ship_list));
    }

    /**
     * 理货确认
     */
    public function warehousing_confirm()
    {
        $this->get_warehousing();
        $this->display();
    }

    /**
     * 理货详情
     */
    public function warehousing_detail()
    {
        $this->get_warehousing();
        $this->display();

    }

    /**
     * 理货详情
     */
    public function warehousing_show()
    {
        $this->get_warehousing();
        $this->display();

    }


    /**
     *保存理货确认
     */
    public function warehouseing_save()
    {
        try {
            $order_id = $_GET['ORDER_ID'];
            $locking = RedisModel::lock('b2b_order_' . $order_id, 10);
            if (!$locking) {
                throw new  Exception(L('请勿重复点击'));
            }
            $url = 'ORDER_ID=' . $order_id . '&ID=' . $_GET['ID'];
            if ($_FILES['file']['name']) {
                // 图片上传
                $fd = new FileUploadModel();
                $ret = $fd->uploadFile();
                if ($ret && $fd->save_name) {
                    $save_arr = explode(',', $fd->save_name);
                    foreach ($save_arr as $key => $value) {
                        $temp['name'] = $_FILES['file']['name'][$key];
                        $temp['save_name'] = $value;
                        $temp_arr[] = $temp;
                    }
                    $save_list['file_name'] = DataModel::arrToJson($temp_arr);
                }
            }
            $get_data = $this->_param();
            $get_data_arr = DataModel::obj2arr($get_data);
            if (empty($get_data['WAREING_DATE']) && $_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
                $get_data_arr = JsonDataModel::warehousing_b2b('array');
            }
            $this->verifyWarehousingTallyData($get_data_arr);
            if (!DataModel::validateDate($get_data['WAREING_DATE'], 'Y-m-d')) {
                unset($get_data['WAREING_DATE']);
            }
            $this->module = $model = new Model();
            $model->startTrans();
            $data = null;
            $info = L('理货确认成功');
            $status = 1;
            $list_res_state = 0;
//        更新物流成本
            $wareshousing_goods = json_decode($get_data['wareshousing_goods']);
            $where_wl['ID'] = $get_data['ship_list_id'];
            $save_wl['LOGISTICS_CURRENCY'] = $get_data['LOGISTICS_CURRENCY'];
            $save_wl['LOGISTICS_COSTS'] = $this->unking($get_data['logistics_costs_sum']);
            $save_wl['power_all'] = $this->unking($get_data['power_all_sum']);
//            $wl_upd = $model->table('tb_b2b_ship_list')->where($where_wl)->save($save_wl);
            foreach ($wareshousing_goods->goods as $k => $v) {
                $model->table('tb_b2b_warehousing_goods')
                    ->where('ship_id = ' . $v->ID)
                    ->getField('warehousing_id');
                $where['ID'] = $v->ID;
                $save['DELIVERED_NUM'] = $v->DELIVERED_NUM;
                $save['DEVIATION_NUM'] = $v->DEVIATION_NUM;
                $save['DEVIATION_REASON'] = $v->DEVIATION_REASON;
                $save['OR_AGAIN_WAREING'] = $v->OR_AGAIN_WAREING;
                $save['AGAIN_WAREING_NUM'] = $v->AGAIN_WAREING_NUM;
                $save['RECOVE_MONEY'] = $v->RECOVE_MONEY;
                $save['recove_curreny'] = $v->recove_curreny;
                $save['end_amount'] = $v->end_amount;
                $save['defective_stored'] = $v->defective_stored;
                $save['incomplete_number'] = $v->incomplete_number;
                $save['other_number'] = $v->other_number;
                $save['REMARKS'] = $v->REMARKS;
                $save_list['tally_type_cd'] = 'N002780001';
                $save_list['SUBMIT_TIME'] = $save['SUBMIT_TIME'] = date('Y-m-d H:i:s');
                $save_list['WAREING_DATE'] = $save['WAREING_DATE'] = $get_data['WAREING_DATE'];
                //if ($save['DELIVERED_NUM']) {
                    $save_list['submit_user'] = $save['SUBMIT_USER_ID'] = empty(session('m_loginname')) ? 'admin_null' : session('m_loginname');
                //}
                $goods_res = $model->table('tb_b2b_warehousing_goods')
                    ->where($where)
                    ->save($save);
                $where_order['ID'] = $v->warehousing_id;
                $model->table('tb_b2b_warehouse_list')->where($where_order)->setInc('WAREHOUSEING_NUM', $save['DELIVERED_NUM']);
                $model->table('tb_b2b_warehouse_list')->where($where_order)->setInc('DEVIATION_NUM', $save['DEVIATION_NUM']);
                $model->table('tb_b2b_warehouse_list')->where($where_order)->setInc('AGAIN_WAREING_NUM', $save['AGAIN_WAREING_NUM']);
                $recove_money = $model->table('tb_b2b_warehouse_list')->where($where_order)->getField('RECOVE_MONEY');
                $ship_list_id = $model->table('tb_b2b_warehouse_list')->where($where_order)->getField('SHIP_LIST_ID');
                $save_list['RECOVE_MONEY'] = round(($recove_money + $save['RECOVE_MONEY']), 6);
                $warehousing_state = $this->get_code_key('warehousing_state');
                $save_list['status'] = $warehousing_state['已确认'];
                $save_list['recove_curreny'] = $save['recove_curreny'];
                $save_list['tally_statement'] = $get_data['tally_statement'];
                $save_list['return_warehouse_cd'] = $get_data['return_warehouse_cd'];
                $where_goods['ORDER_ID'] = $order_id;
                $where_goods['SKU_ID'] = $v->warehouse_sku;
                $where_goods['ID'] = $v->goods_id;
                $model->table('tb_b2b_goods')->where($where_goods)->setInc('is_inwarehouse_num', $save['DELIVERED_NUM']);
                $list_res = $model->table('tb_b2b_warehouse_list')->where($where_order)->save($save_list);
                if ($list_res && $list_res_state == 0) {
                    $list_res_state = 1;
                }
//            增加待收款单据
                if (!$goods_res || !$list_res_state) {
                    $model->rollback();
                    $info = L('理货确认失败');
                    $status = 0;
                    break;
                    goto ajaxreturn;
                }
            }
            if ($get_data_arr['return_warehouse_cd']) {
                $api_res = (new WmsModel())->b2bReturnWarehousing(
                    $this->assembleReturnWarehousingData($get_data_arr)
                );
                if (2000 != $api_res['code']) {
                    throw new  Exception(L('接口入库失败：' . $api_res['msg']));
                }
            }
            $model->commit();
            $this->upd_warehosing_status($_GET['ORDER_ID']);
            B2bModel::addLog($order_id, $status, '理货', "发货子单号:{$ship_list_id}");
            ajaxreturn:
            if ($status == 1) {
                $this->success($info, U('warehousing_show', $url), 2);
                $this->upd_gather_date($order_id);
                $this->upd_profit_logistics($order_id);
                $this->upd_profit_defective($order_id);
                $this->updateOrderReceivableAccount($_GET['ORDER_ID']);
            } else {
                B2bModel::addLog($order_id, $status, $info);
                $this->error($info, U('warehousing_detail', $url), 2);
            }
        } catch (Exception $exception) {
            if ($model) {
                $model->rollback();
            }
            $this->error($exception->getMessage(), U('warehousing_detail', $url), 2);
        }
    }

    public function warehouse_return()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        try {
            $service->warehouseReturn($params);
        } catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage());
        }
        $this->ajaxSuccess();
    }

    /**
     * @param $data
     * @param $SHIP_LIST_ID
     *
     * @return mixed
     * @throws Exception
     */
    private function assembleReturnWarehousingData($data)
    {
        $data['wareshousing_goods'] = DataModel::jsonToArr($data['wareshousing_goods']);
        $ship_id = $data['wareshousing_goods']['goods'][0]['ship_id'];
        $temp_res['virType'] = self::SPOT_STOCK_CD;
        $temp_res['billType'] = self::CUSTOMER_WAREHOUSING_CD;
        $temp_res['relationType'] = self::B2B_SEND_OUT_CD;
        $temp_res['deliveryWarehouse'] = $data['return_warehouse_cd'];
        $temp_res['operatorId'] = DataModel::userId();
        $temp_res['orderId'] = $this->getShipBatchOrderIdToB($ship_id);
        $temp_res['warehouseListId'] = $data['wareshousing_goods']['ID'];
        foreach ($data['wareshousing_goods']['goods'] as $wareshousing_good) {
            if (0 < $wareshousing_good['incomplete_number']) {
                $temp_res['data'][] = [
                    "orderId" => $this->getShipBatchOrderIdToB($wareshousing_good['ship_id']),
                    'linkBillId' => $this->getLinkBillIdFromShipListId($wareshousing_good['ship_id']),
                    "skuId" => $wareshousing_good['sku_show'],
                    "num" => $wareshousing_good['incomplete_number']
                ];
            }
        }
        return $temp_res;
    }

    /**
     * @param $ship_id
     *
     * @return mixed
     * @throws Exception
     */
    private function getShipBatchOrderIdToB($ship_id)
    {
        $ship = $this->getWhereBillIdFromShipId($ship_id);
        $order_id = $this->module->table('tb_wms_bill,tb_wms_stream,tb_wms_batch_order')
            ->where("(tb_wms_bill.link_bill_id = '{$ship['link_bill_id']}' OR tb_wms_bill.bill_id = '{$ship['link_bill_id']}')", null, true)
            ->where('tb_wms_bill.id = tb_wms_stream.bill_id AND tb_wms_batch_order.id = tb_wms_stream.tb_wms_batch_order_id', null, true)
            ->group('tb_wms_stream.tb_wms_batch_order_id')
            ->getField('tb_wms_batch_order.ORD_ID');
        if (empty($order_id)) {
            throw new Exception(L("获取对应已出库批次数据失败{$ship_id}"));
        }
        return $order_id;
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function verifyWarehousingTallyData($data)
    {

        if (0 < count(array_column($data['goods'], 'incomplete_number')) && empty($data['return_warehouse_cd'])) {
            throw new Exception(L('SKU 有残次品数量，退货入库仓库必填'));
        }
        if (!empty($data['return_warehouse_cd']) && 'N00068' != substr($data['return_warehouse_cd'], 0, 6)) {
            throw new Exception(L('退货入库仓库请选择正确 CODE'));
        }
        $warehouse_cd_y_arr = array_column(CodeModel::getCodeArr(['N00068'], 'Y'), 'CD');
        if ($data['return_warehouse_cd'] && !in_array($data['return_warehouse_cd'], $warehouse_cd_y_arr)) {
            throw new Exception(L('退货入库仓库已关闭,请选择已开启仓库'));
        }
        $data['wareshousing_goods'] = DataModel::jsonToArr($data['wareshousing_goods']);
        foreach ($data['wareshousing_goods']['goods'] as $wareshousing_good) {
            if ($wareshousing_good['TOBE_WAREHOUSING_NUM'] < $wareshousing_good['DELIVERED_NUM'] + $wareshousing_good['incomplete_number']) {
                throw new Exception(L($wareshousing_good['sku_show'] . '【合格品数量】+【残次品数量 】不能超过【发货数】'));
            }
        }
    }

    /**
     *  更新订单物流成本
     *
     * @param null $order_id
     *
     * @return mixed
     */
    public function upd_profit_logistics($order_id = null)
    {
        $Model = M();
        $recove_all = $Model->table('tb_b2b_ship_list')->where('order_id = ' . $order_id)->field('ID,LOGISTICS_CURRENCY,LOGISTICS_COSTS,DELIVERY_TIME')->select();
        $RECOVE_MONEY_ALL = 0;
        foreach ($recove_all as $v) {
            if ($v['LOGISTICS_COSTS'] == 0) break;
            $exchange = B2bModel::update_currency($v['LOGISTICS_CURRENCY'], date('Y-m-d', strtotime($v['DELIVERY_TIME'])));
            $RECOVE_MONEY_ALL += $exchange * $v['LOGISTICS_COSTS'];
        }
        $usd_recove['logistics_currency'] = 'N000590100';
        $usd_recove['logistics_costs'] = $RECOVE_MONEY_ALL;
        $save_status = $Model->table('tb_b2b_profit')->where('ORDER_ID = ' . $order_id)->save($usd_recove);
        return $save_status;
    }

    /**
     * 更新订单残次品
     *
     * @param null $order_id
     */
    public function upd_profit_defective($order_id = null)
    {
        $Model = M();
        $warehouse_id_all = $Model->table('tb_b2b_warehouse_list')->where('ORDER_ID = ' . $order_id)->field('ID')->select();
        $warehouse_id_all = array_column($warehouse_id_all, 'ID');
        $where_warhouse['t1.warehousing_id'] = array('IN', $warehouse_id_all);
        $goods_all = $Model->table('tb_b2b_warehousing_goods as t1,tb_b2b_ship_goods as t2')->field('t1.warehouse_sku,t1.defective_stored,t2.power')->where('t1.ship_id = t2.SHIP_ID AND t1.goods_id = t2.goods_id')->where($where_warhouse)->select();
//        $sku_arr = array_column($goods_all, 'warehouse_sku');
//        $where_sku['SKU_ID'] = array('IN', $sku_arr);
//        $all_sku_power = $Model->table('tb_wms_power')->where($where_sku)->field('SKU_ID,weight')->select();
//        $power_sku = array_column($all_sku_power, 'weight', 'SKU_ID');
        $num_all = $all_sum = 0;
        foreach ($goods_all as $v) {
            $all_sum += $v['defective_stored'] * $v['power'];
            $num_all += $v['defective_stored'];
        }
        $usd_recove['recoverable_amount'] = $all_sum;
        $usd_recove['defective_currency'] = 'N000590100';
        $usd_recove['defective_num'] = $num_all;
        $save_status = $Model->table('tb_b2b_profit')->where('ORDER_ID = ' . $order_id)->save($usd_recove);
        return $save_status;
    }

    /**
     * 同步订单理货确认状态
     *
     * @param $order_id
     */
    public function upd_warehosing_status($order_id)
    {
        $warehousing_state = 1;
        $Model = M();
        $warehousing_arr = $Model->table('tb_b2b_warehouse_list')
            ->field('ID,status')
            ->where('ORDER_ID = ' . $order_id)
            ->select();
        if ($warehousing_arr) {
            $warehousing_state = $this->play_status($warehousing_arr, 2, 'status');
        }
        $this->upd_order_node($order_id, $warehousing_state, 'warehousing_state');
    }

    /**
     * 同步订单收款状态
     *
     * @param $order_id
     */
    public function upd_receipt_status($order_id)
    {
        $receipt_status = 1;
        $Model = M();
//        货款
        $receipt_status_arr = $Model->table('tb_b2b_receipt')->field('ID,receipt_operation_status')->where('ORDER_ID = ' . $order_id . ' AND transaction_type IS NULL')->select();
        if ($receipt_status_arr) {
            $receipt_status = $this->play_status($receipt_status_arr, 1, 'receipt_operation_status');
        }
//        $this->upd_order_node($order_id, $receipt_status, 'receipt_state');
//        退税
        $tax_rebate_state_arr = $Model->table('tb_b2b_receipt')->field('ID,receipt_operation_status')->where('ORDER_ID = ' . $order_id . ' AND transaction_type = 1')->select();
        if ($tax_rebate_state_arr) {
            $receipt_status = $this->play_status($tax_rebate_state_arr, 1, 'receipt_operation_status');
        }
        $this->upd_order_node($order_id, $receipt_status, 'tax_rebate_state');
    }

    /**
     * 收款列表
     */
    public function gathering_list()
    {
        $receipt = M('receipt', 'tb_b2b_');
        $getdata = $this->_param();
        $this->action_gathering['sales_team_code'] = empty($getdata['sales_team_id']) ? '' : $getdata['sales_team_id'];
        $this->action_gathering['CLIENT_NAME'] = empty($getdata['client_id']) ? '' : $getdata['client_id'];
        $this->action_gathering['PO_ID'] = empty($getdata['PO_ID']) ? '' : $getdata['PO_ID'];
        $this->action_gathering['transaction_type'] = empty($getdata['transaction_type']) ? '' : $getdata['transaction_type'];
        $this->action_gathering['unconfirmed'] = empty($getdata['unconfirmed_state']) ? '' : $getdata['unconfirmed_state'];
        $this->action_gathering['main_gathering'] = empty($getdata['main_gathering_state']) ? '' : $getdata['main_gathering_state'];
        $where = B2bModel::joinwhere($getdata, 'b2bgathering');
        switch ($getdata['orderId']) {
            case 'THR_PO_ID':
                $this->action_gathering['orderId'] = 'THR_PO_ID';
                if ($getdata['PO_ID']) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('LIKE', B2bModel::check_v($getdata['PO_ID'], 'LIKE'));
                }
                unset($where['tb_b2b_receipt.PO_ID']);
                break;
            case 'PO_ID':
                $this->action_gathering['orderId'] = 'PO_ID';
                break;
        }
        if (strlen_utf8($getdata['main_gathering_state']) > 0) {
            $where['tb_b2b_receipt.main_receipt_operation_status'] = $getdata['main_gathering_state'];
            if ($getdata['main_gathering_state'] == 0) {
                $this->action_gathering['main_gathering'] = '0';
            }
        } else {
            unset($where['tb_b2b_receipt.main_receipt_operation_status']);
        }
        if (strlen_utf8($getdata['unconfirmed_state']) > 0) {
            $where['tb_b2b_receipt.main_unconfirmed_state'] = $getdata['unconfirmed_state'];
            if ($getdata['unconfirmed_state'] == 0) {
                $this->action_gathering['unconfirmed'] = '0';
            }
        } else {
            unset($where['tb_b2b_receipt.main_unconfirmed_state']);
        }
        if ($getdata['transaction_type'] === '0' || !empty($getdata['receipt_operation_status'])) {
            unset($where['tb_b2b_receipt.transaction_type']);
            $where['tb_b2b_receipt.transaction_type'] = array(array('NEQ', 1), array('EXP', 'IS NULL'), 'or');
        }
        $where['tb_b2b_receipt.P_ID'] = array('EXP', 'IS NULL');
        $count = $receipt
            ->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join (select max(WAREING_DATE) as WAREING_DATE,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
            ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by order_id order by ID desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
            ->field('tb_b2b_receipt.*,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_doship.shipping_status,tb_b2b_doship.update_time,tb_b2b_warehouse_list.status,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_ship_list.SUBMIT_TIME')
            ->count();
        if (!$count) {
            $count = 0;
        }
        import('ORG.Util.Page');
        $Page = new Page($count, 10);
        $show = $Page->show();
        $gathering_list = $receipt
            ->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join (select max(WAREING_DATE) as WAREING_DATE,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
            ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by order_id order by ID desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
            ->order('tb_b2b_receipt.expect_receipt_date desc,tb_b2b_receipt.transaction_type asc,tb_b2b_receipt.ID desc')
            ->field('tb_b2b_receipt.*,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_doship.shipping_status,tb_b2b_doship.update_time,tb_b2b_warehouse_list.status,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_ship_list.SUBMIT_TIME')
            ->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //        合同 oa_TIME , 发货后update_time -> DELIVERY_TIME, 到港后 Estimated_arrival_DATE >.<,入库后 WAREING_DATE
        $initdata['sales_team'] = B2bModel::get_sales_team();
        $initdata['number_th'] = $this->number_th;
        $initdata['node_is_workday'] = B2bModel::get_code('node_is_workday');
        $initdata['node_type'] = B2bModel::get_code('node_type');
        $initdata['node_date'] = B2bModel::get_code('node_date');
        $initdata['main_gathering_state'] = B2bModel::get_code_lang('main_gathering_state');
        $initdata['unconfirmed_state'] = B2bModel::get_code_lang('unconfirmed_state');
        $gathering_list = (new B2bService())->checkOverdueDay($gathering_list,
            'expect_receipt_date',
            'overdue_statue',
            'overdue_day',
            true);
        $this->assign('action', B2bModel::set_json($this->action_gathering));
        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('gathering_list', B2bModel::set_json($gathering_list));
        $this->assign('page', $show);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 更新订单自身所有预期时间
     *
     * @param $order_id
     *
     * @return bool
     */
    public function upd_gather_date($order_id, $Models)
    {
        $Receipt = M('receipt', 'tb_b2b_');
        $receipt_arr = $Receipt->where('tb_b2b_receipt.ORDER_ID = ' . $order_id . ' AND tb_b2b_receipt.transaction_type IS NULL')
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join (select max(WAREING_DATE) as WAREING_DATE,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
            ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by order_id order by Estimated_arrival_DATE desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
            ->field('tb_b2b_receipt.*,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_doship.shipping_status,tb_b2b_doship.update_time,tb_b2b_warehouse_list.status,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_ship_list.SUBMIT_TIME')
            ->select();

        $node_date = B2bModel::get_code('node_date');
        if (!count($receipt_arr)) return false;
        foreach ($receipt_arr as $v) {
            if ($v['transaction_type'] != 1) {
                $check_data['po_time'] = $v['po_time'];
                $check_data['SUBMIT_TIME'] = $v['SUBMIT_TIME'];
                $check_data['DELIVERY_TIME'] = $v['DELIVERY_TIME'];
                $check_data['Estimated_arrival_DATE'] = $v['Estimated_arrival_DATE'];
                $check_data['WAREING_DATE'] = $v['WAREING_DATE'];
                unset($node);
                $node[] = json_decode($v['receiving_code'], true);
                $res_date = $this->check_yq($v['id'], json_encode($node), $check_data, null, null, null, true);
                if ($res_date != '0000-00-00' && $res_date) {
                    $res_date_rear = date('Y-m-d', strtotime($res_date . " +" . $node_date[$node[0]['nodeDate']]['CD_VAL'] . " day"));
                    $data[] = ['ID' => $v['ID'], 'expect_receipt_date' => $res_date_rear, 'overdue_date' => $res_date_rear];
                    $datas[] = ['ID' => $v['ID'], 'expect_receipt_date' => $res_date_rear, 'overdue_date' => $res_date_rear, 'actual_receipt_date' => $v['actual_receipt_date']];
                }
            }
        }
        if (!count($data)) return false;
        $ress = $this->saveAll($data, 'tb_b2b_receipt', $Models);
        if (!class_exists('OverdueAction')) {
            require_once APP_PATH . 'Lib/Action/Home/OverdueAction.class.php';
        }
        foreach ($datas as $v) {
            //           更新逾期状态
            OverdueAction::actual_overdue_upd($v['ID'], $v['expect_receipt_date'], $Receipt, true);
            if ($v['actual_receipt_date']) {
                OverdueAction::actual_overdue_upd($v['ID'], $v['actual_receipt_date'], $Receipt);
            }
        }
    }

    /**
     * 收款下载
     */
    public function gathering_down()
    {
        $receipt = M('receipt', 'tb_b2b_');
        $receipt_arr = $receipt
            ->field('tb_b2b_receipt.*,tb_b2b_info.THR_PO_ID,tb_b2b_info.our_company')
            ->join('left join tb_b2b_info ON tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->select();
        $this->down_existing($receipt_arr);
    }


    /**
     * 收款确认
     */
    public function gathering_detail()
    {
        $main_id = $_REQUEST['main_id'];
        $gathering = $this->gatheringDataJoin($initdata = null);
        $this->assign('gathering', B2bModel::set_json($gathering));

        $this->assign('main_id', $main_id);
        $show = I('show') ? I('show') : 0;
        $this->assign('show', $show);

        $this->display();
    }

    /**
     *
     */
    public function gathering_all_detail()
    {
        $id = I('id');
        $main_id = $_REQUEST['main_id'];
        $receipt = M('receipt', 'tb_b2b_');
        $model = M();
        $gathering = $receipt->where('tb_b2b_receipt.id = ' . $id)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_profit on tb_b2b_profit.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join (select max(WAREING_DATE) as WAREING_DATE ,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
            ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by tb_b2b_ship_list.order_id order by tb_b2b_ship_list.ID desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
            ->field('tb_b2b_receipt.*,tb_b2b_receipt.actual_payment_amount as actual_payment_amount_z,tb_b2b_receipt.expect_receipt_amount as actual_payment_amount,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_info.DELIVERY_METHOD,tb_b2b_info.po_currency,tb_b2b_info.CLIENT_NAME,tb_b2b_info.our_company as our_company_info,tb_b2b_info.our_company as company_our,tb_b2b_receipt.company_our as company_our_receipt,tb_b2b_doship.update_time,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_profit.delivery_currency,tb_b2b_profit.cost_delivery,tb_b2b_profit.logistics_currency,tb_b2b_profit.logistics_costs,tb_b2b_profit.defective_num,tb_b2b_profit.defective_currency,tb_b2b_profit.recoverable_amount')
            ->find();
        $gathering['all_node'] = $receipt->where('ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND transaction_type IS NULL')->order('ID')->field('receiving_code')->select();
        // 计算总额
        $gathering_arr = $receipt->where('ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND transaction_type IS NULL')->order('ID')->field('receipt_operation_status')->select();
        list($gathering['all_order_money'], $gathering['all_money'], $gathering['all_money_po']) = $this->allMoneyGet($receipt, $gathering);
        $gathering['all_diff'] = $this->all_diff($gathering);
        /*foreach ($gathering_arr as $v) {
            $gathering['all_money'] += $v['actual_payment_amount'];
        }*/
        //  期数判断
        $node_count = count($gathering['all_node']);
        $arr_receving = json_decode($gathering['receiving_code'], true);
        $is_end = 0;
        if ($node_count == $arr_receving['nodei'] + 1) $is_end = 1;
        // 控制收款节点
        $node_status = true;
        if ($node_count > 1 && $arr_receving['nodei'] != 0) {
            if ($gathering_arr[$arr_receving['nodei'] - 1]['receipt_operation_status'] == 0) $node_status = false;
        }
//        交换子节点数据
        if ($gathering['transaction_type'] == 2) {
            $do = $gathering['collect_this'];
            $gathering['collect_this'] = $gathering['expect_receipt_amount'];
            $gathering['actual_payment_amount'] = $gathering['expect_receipt_amount'] = $do;
        }

        // format gathering info
        $gathering = BaseCommon::fmt_gathering($gathering);
        // order data
        $order_data = B2bData::oneOrderBasic($gathering['ORDER_ID']);
        $gathering['fmt_order_data'] = $order_data;

        $gathering['todo_order_amount'] = $this->todoOrderAmount($receipt, $gathering);
        trace($gathering['todo_order_amount'], '$gathering[\'todo_order_amount\']');
        $gathering['todo_order_amount'] = ($gathering['todo_order_amount']) ? $gathering['todo_order_amount'] : 0;
//       add record of receipts
        $gathering_list = B2bModel::gathering_list($main_id, $model);
        Logs($gathering_list, 'gathering_list', 'b2b');
        $initdata['node_status'] = $node_status;
        $initdata['is_end'] = $is_end;
        $initdata['number_th'] = $this->number_th;
        $initdata['node_is_workday'] = B2bModel::get_code('node_is_workday');
        $initdata['node_type'] = B2bModel::get_code('node_type');
        $initdata['node_date'] = B2bModel::get_code('node_date');
        $initdata['period'] = B2bModel::get_code('period');
        $initdata['invioce'] = B2bModel::get_code('invioce');
        $initdata['tax_point'] = $this->un_sign(B2bModel::get_code('tax_point'));
        $initdata['wfgs'] = B2bModel::get_code('我方公司');
        $initdata['currency'] = B2bModel::get_code('기준환율종류코드', true);
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');
        $initdata['gathering'] = B2bModel::get_code_lang('gathering');
        $initdata['main_gathering_state'] = B2bModel::get_code_lang('main_gathering_state');

        $gathering = (new B2bService())->checkOverdueDay([$gathering], 'expect_receipt_date')[0];

        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('gathering', B2bModel::set_json($gathering));
        $this->assign('deviation_cause', B2bModel::set_json(B2bModel::get_code('deviation_cause')));
        $this->assign('or_invoice_arr', B2bModel::set_json(array_column(B2bModel::get_code('or_invoice_arr'), 'CD_VAL')));
        $this->assign('url', '&id=' . $id);

        $this->assign('main_id', $main_id);
        $receivablesIdGet = B2bModel::receivablesIdGet($main_id, $model);
        $receivables_id = ($receivablesIdGet) ? $receivablesIdGet : $main_id;
        $this->assign('receivables_id', $receivables_id);
        $confirmAll = B2bModel::confirmAll($gathering_list);
        $this->assign('confirm_all', ($confirmAll || $receivables_id == $main_id) ? 1 : 0);
        $this->assign('gathering_list', B2bModel::set_json($gathering_list));
        $this->display();
    }

    /**
     * 收款保存
     */
    public function gathering_save()
    {
        // check po change or not
        $check_po_changed = 0;
        $Model = M();
        $Model->startTrans();
        $gathering = isset($_REQUEST['gathering']) ? $_REQUEST['gathering'] : null;
        $gathering = json_decode($gathering, true);
        $order_id = $gathering['ORDER_ID'];
        $old_data = $gathering['fmt_order_data'];
        $changed = BaseCommon::order_changed_by_old($order_id, $old_data);
        if ($changed['is_err']) {
            $info = '收款操作失败' . "[{$changed['msg']}]";
//            todo
//            $this->error($info, U('B2b/gathering_list'), 2);
        }
        $get_main_id = $_GET['main_id'];
        $url = '&id=' . $_GET['id'] . '&main_id=' . $get_main_id;
        if ($_FILES['file']['name'] || $_FILES['receivefile']['name']) {
            // 图片上传
            $fd = new FileUploadModel();
            $ret = $fd->uploadFile();
            if ($ret) {
                if ($_FILES['receivefile']['size'] > 0 && $_FILES['file']['size'] > 0) {
                    $save_arr = explode(',', $fd->save_name);
                    $save['file_path'] = $save_arr[0];
                    $save['file_name'] = $_FILES['file']['name'];
                    $save['receive_file_path'] = $save_arr[1];
                    $save['receive_file_name'] = $_FILES['receivefile']['name'];
                } elseif ($_FILES['file']['size'] > 0) {
                    $save['file_path'] = $fd->save_name;
                    $save['file_name'] = $_FILES['file']['name'];
                } elseif ($_FILES['receivefile']['size'] > 0) {
                    $save['receive_file_path'] = $fd->save_name;
                    $save['receive_file_name'] = $_FILES['receivefile']['name'];
                }
            } else {
                $this->error("保存失败：上传凭证文件失败," . $fd->error, U('gathering_detail', $url), 2);
            }
        }
        $get_data = $this->_param();
        $post = json_decode($get_data['gathering'], true);
        $data = null;
        $info = '收款保存失败';
        $status = 0;
        $Receipt = M('receipt', 'tb_b2b_');
        if ($post) {
            $h8 = 3600 * 8;
            try {
                $this->checkData($post);
                list($save, $actual_receipt_date, $post, $expect_receipt_amount) = $this->saveData($get_main_id, $save, $post, $h8);
            } catch (Exception $exception) {
                $this->error($exception->getMessage(), U('gathering_detail', $url), 2);
            }
            if ($post['actual_receipt_date'] != '-') {
//           更新逾期状态
                OverdueAction::actual_overdue_upd($post['ID'], $actual_receipt_date, $Receipt);
            }
            //  期数判断
            $node_count = count($post['all_node']);
            $arr_receving = json_decode($post['receiving_code'], true);
            $is_end = 0;
            if ($node_count == $arr_receving['nodei'] + 1) $is_end = 1;
            if (!empty($post['completion_date_end']) && $expect_receipt_amount > 0) {
                $completion_date_end = gmdate('Y-m-d H:i:s', strtotime($post['completion_date_end']) + $h8);
                $save['received_this'] = $post['actual_payment_amount'];
                $save['completion_date_end'] = $completion_date_end;
            }
            if ($is_end == 1) {
                $save_profit['delivery_currency'] = $post['delivery_currency'];
                $save_profit['cost_delivery'] = $post['cost_delivery'];
                $save_profit['logistics_currency'] = $post['logistics_currency'];
                $save_profit['logistics_costs'] = $post['logistics_costs'];
                $save_profit['defective_num'] = $post['defective_num'];
                $save_profit['defective_currency'] = $post['defective_currency'];
                $save_profit['recoverable_amount'] = $post['recoverable_amount'];
                $profit = $Model->table('tb_b2b_profit')->where('ORDER_ID = ' . $post['ORDER_ID'])->save($save_profit);
                // 更新所有数据
                $save_receipt['side_taxed_currency'] = $post['side_taxed_currency'];
                $save_receipt['side_taxed'] = $post['side_taxed'];
                $save_receipt['deducting_tax_currency'] = $post['deducting_tax_currency'];
                $save_receipt['deducting_tax'] = $post['deducting_tax'];
                $receipt = $Model->table('tb_b2b_receipt')->where('ORDER_ID = ' . $post['ORDER_ID'])->save($save_receipt);
            }
            $receipt = $Receipt->where(' ID = ' . $post['ID'])->save($save);
            if ($receipt) {
                try {
                    $this->receiptStateUpd($order_id, $get_main_id, $Model);
                    $this->orderConfirmedStateUpd($get_main_id, $Model);
                    $data = $receipt;
                    $info = '收款保存成功';
                    $status = 1;
                } catch (\Exception $e) {
                    $Model->rollback();
                }

            }
        }
        B2bModel::addLog($post['ORDER_ID'], $status, $info);
        if ($status == 1) {
            $this->upd_receipt_status($_GET['ORDER_ID']);
            B2bModel::receivableRemindMailSend($post, $this->fetch('receivableRemindMail'));
            $Model->commit();
            $this->success($info, U('gathering_detail', $url), 2);
        } else {
            $Model->rollback();
            $this->error($info, U('gathering_detail', $url), 2);
        }
    }

    /**
     * @param $order_id
     * @param $main_id
     * @param $Model
     */
    public function receiptStateUpd($order_id, $main_id, $Model)
    {
        $main_state_upd = B2bModel::mainOrderStateUpd($order_id, $main_id, $Model);
        if ($main_state_upd) {
            B2bModel::orderStateUpd($order_id, $Model);
            // B2bModel::orderOverdueStatueUpd($order_id, $Model);
            OverdueAction::actual_overdue_order_upd($order_id);
        }
    }

    /**
     *
     */
    public function gathering_edit()
    {
        $id = I('id');
        $main_id = $_REQUEST['main_id'];
        if (!IS_POST) {
            $gathering = $this->gatheringDataJoin($initdata = null);
            $gathering['actual_payment_amount'] = $gathering['actual_payment_amount_z'];
            $gathering['company_our'] = $gathering['company_our_receipt'];
            $this->assign('gathering', B2bModel::set_json($gathering));
            $this->assign('completion_end', ($gathering['completion_date_end']) ? 1 : 0);
            $this->assign('main_id', $main_id);
            $this->display();
        } else {

            // check po change or not
            $check_po_changed = 0;
            $gathering = isset($_REQUEST['gathering']) ? $_REQUEST['gathering'] : null;
            $gathering = json_decode($gathering, true);
            $order_id = $gathering['ORDER_ID'];
            $old_data = $gathering['fmt_order_data'];
            $changed = BaseCommon::order_changed_by_old($order_id, $old_data);
            if ($changed['is_err']) {
                $info = '操作失败' . "[{$changed['msg']}]";
//            todo
//            $this->error($info, U('B2b/gathering_list'), 2);
            }
            $url = '&id=' . $_GET['id'];
            if ($_FILES['file']['name'] || $_FILES['receivefile']['name']) {
                // 图片上传
                $fd = new FileUploadModel();
                trace($_FILES, '$_FILES');
                $ret = $fd->uploadFile();
                if ($ret) {
                    if ($_FILES['receivefile']['size'] > 0 && $_FILES['file']['size'] > 0) {
                        $save_arr = explode(',', $fd->save_name);
                        $save['file_path'] = $save_arr[0];
                        $save['file_name'] = $_FILES['file']['name'];
                        $save['receive_file_path'] = $save_arr[1];
                        $save['receive_file_name'] = $_FILES['receivefile']['name'];
                    } elseif ($_FILES['file']['size'] > 0) {
                        $save['file_path'] = $fd->save_name;
                        $save['file_name'] = $_FILES['file']['name'];
                    } elseif ($_FILES['receivefile']['size'] > 0) {
                        $save['receive_file_path'] = $fd->save_name;
                        $save['receive_file_name'] = $_FILES['receivefile']['name'];
                    }

                } else {
                    // $this->error("保存失败：上传凭证文件失败," . $fd->error, U('gathering_detail', $url), 2);
                }
            }
            $get_data = $this->_param();
            $post = json_decode($get_data['gathering'], true);
            $data = null;
            $info = '保存失败';
            $status = 0;
            $Receipt = M('receipt', 'tb_b2b_');
            if ($post) {
                $get_main_id = $_GET['main_id'];
                $h8 = 3600 * 8;
                try {
                    $this->checkData($post);
                    list($save, $actual_receipt_date, $post, $expect_receipt_amount) = $this->saveData($get_main_id, $save, $post, $h8);
                } catch (Exception $exception) {
                    $this->error($exception->getMessage(), U('gathering_detail', $url), 2);
                }
                unset($save['create_time']);
                if ($post['actual_receipt_date'] != '-') {
//           更新逾期状态
                    OverdueAction::actual_overdue_upd($post['ID'], $actual_receipt_date, $Receipt);
                }
                //  期数判断
                $node_count = count($post['all_node']);
                $arr_receving = json_decode($post['receiving_code'], true);
                $is_end = 0;
                if ($node_count == $arr_receving['nodei'] + 1) $is_end = 1;

                $completion_date_end = ($post['completion_date_end']) ? gmdate('Y-m-d H:i:s', strtotime($post['completion_date_end']) + $h8) : NULL;
                $save['completion_date_end'] = $completion_date_end;
                // $save['received_this'] = $post['actual_payment_amount'];
                $Model = M();
                if ($is_end == 1) {
                    $save_profit['delivery_currency'] = $post['delivery_currency'];
                    $save_profit['cost_delivery'] = $post['cost_delivery'];
                    $save_profit['logistics_currency'] = $post['logistics_currency'];
                    $save_profit['logistics_costs'] = $post['logistics_costs'];
                    $save_profit['defective_num'] = $post['defective_num'];
                    $save_profit['defective_currency'] = $post['defective_currency'];
                    $save_profit['recoverable_amount'] = $post['recoverable_amount'];
                    $profit = $Model->table('tb_b2b_profit')->where('ORDER_ID = ' . $post['ORDER_ID'])->save($save_profit);
                    // 更新所有数据
                    $save_receipt['side_taxed_currency'] = $post['side_taxed_currency'];
                    $save_receipt['side_taxed'] = $post['side_taxed'];
                    $save_receipt['deducting_tax_currency'] = $post['deducting_tax_currency'];
                    $save_receipt['deducting_tax'] = $post['deducting_tax'];
                    $receipt = $Model->table('tb_b2b_receipt')->where('ORDER_ID = ' . $post['ORDER_ID'])->save($save_receipt);
                }
                Logs($post, 'post_data_gatheringEdit', 'b2b');
                Logs($save, 'save_data_gatheringEdit', 'b2b');
                $receipt = $Receipt->where(' ID = ' . $post['ID'])->save($save);

                if ($receipt) {
                    try {
                        $this->receiptStateUpd($order_id, $get_main_id, $Model);
                        $this->orderConfirmedStateUpd($get_main_id, $Model);
                    } catch (\Exception $e) {

                    }
                    $data = $receipt;
                    $info = '保存成功';
                    $status = 1;
                }
            }
            B2bModel::addLog($post['ORDER_ID'], $status, $info);
            if ($status == 1) {
                $this->upd_receipt_status($_GET['ORDER_ID']);
                B2bModel::receivableRemindMailSend($post, $this->fetch('receivableRemindMail'));
                $this->success($info, U('gathering_detail', $url), 2);
            } else {
                $this->error($info, U('gathering_detail', $url), 2);
            }
        }
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    public function checkData($data)
    {
        if ($_POST['completion_end'] != 0 && (empty($data['completion_date_end']) || !$this->validateDate($data['completion_date_end']))) {
            throw new Exception('预计收款时间缺失');
        }
        if (empty($data['actual_receipt_date']) || !$this->validateDate($data['actual_receipt_date'])) {
            throw new Exception('实际收款时间缺失');
        }
        if (empty($data['actual_payment_amount'])) {
            throw new Exception('金额缺失');
        }
    }

    /**
     * @param $date
     * @param string $format
     *
     * @return bool
     */
    public function validateDate($date, $format = 'Y')
    {
        $d = date($format, strtotime($date));
        if ($d > 1970) {
            return ture;
        }
        return false;
    }

    /**
     *
     */
    public function gathering_confirm()
    {
        $id = I('id');
        $order_id = I('order_id');
        $get_main_id = $_REQUEST['main_id'];
        $url = '&id=' . $_GET['id'];
        try {
            $model = M();
            $this->checkConfirmStatus($_GET['id'], $model);
            $res = B2bModel::gatheringConfirm($id);
            Logs($res, '$res');
            // if (!$res) throw new \Exception(L('确认失败'));
            if (I('cost_delivery') > 0) {
                list($profit_res, $info_res) = B2bModel::receiptOrderEnd($order_id, $_GET, $model);
            }
            $post = $_GET;
            $expect_receipt_amount = $post['expect_receipt_amount'] - $post['actual_payment_amount'];
            if (('null' != $post['completion_date_end']) && !empty($post['completion_date_end']) && $expect_receipt_amount > 0) {
                $post['ID'] = $post['id'];
                $post['ORDER_ID'] = $post['order_id'];
                $chirld_gather_id = B2bModel::chirld_gather_add($post, $expect_receipt_amount);
            }
            Logs($post, 'post', 'b2b');
            Logs($expect_receipt_amount, 'expect_receipt_amount', 'b2b');
            $this->receiptStateUpd($order_id, $get_main_id, $model);
            $this->orderConfirmedStateUpd($get_main_id, $model);
            if ($this->checkIsOrderReceiptStatus($post['order_id'], $model)) {
                $all_receipt_status = 2;
            } else {
                $all_receipt_status = 1;
            }
            $this->updateB2bOrderStatus($post['order_id'], $model, $all_receipt_status);
            $this->success(L('确认成功'), U('gathering_detail', $url), 2);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), U('gathering_detail', $url), 2);
        }
    }

    /**
     * @param $order_id
     * @param $model
     *
     * @throws Exception
     */
    private function checkConfirmStatus($order_id, $model)
    {
        if (empty($order_id)) {
            throw new Exception(L('订单号缺失'));
        }
        $where['ID'] = $order_id;
        $res_db = $model->table('tb_b2b_receipt')
            ->field('ID,unconfirmed_state')
            ->where($where)
            ->find();
        if (empty($res_db)) {
            throw new Exception(L('订单号异常'));
        }
        if (1 == $res_db['unconfirmed_state']) {
            throw new Exception(L('订单号已确认'));
        }
    }

    /**
     * @param $order_id
     * @param $model
     *
     * @return bool
     */
    public function checkIsOrderReceiptStatus($order_id, $model)
    {
        $where_receipt['ORDER_ID'] = $order_id;
        $where_receipt['P_ID'] = array('EXP', 'IS NULL');
        $where_receipt['main_receipt_operation_status'] = array('NEQ', 1);
        $uncollected_goods_count = $model->table('tb_b2b_receipt')
            ->where($where_receipt)
            ->count();
        if (!$uncollected_goods_count) {
            return true;
        }
        return false;
    }

    /**
     * @param $order_id
     * @param $model
     * @param $all_receipt_status
     */
    public function updateB2bOrderStatus($order_id, $model, $all_receipt_status)
    {
        $where_order['ID'] = $order_id;
        $save_order['receipt_state'] = $all_receipt_status;
        $model->table('tb_b2b_order')
            ->where($where_order)
            ->save($save_order);

    }


    /**
     *
     */
    public function gathering_return()
    {
        $post = B2bModel::get_data(null, true, true);
        $res['data'] = $post;
        try {
            $ress = B2bModel::gatheringReturn($post);
            if (!$ress) throw new \Exception('退回失败');
            B2bModel::receivableReturnMailSend($res['data'], $this->fetch('receivableReturnMail'));
            $res['msg'] = '退回成功';
            $res['state'] = 200;
        } catch (\Exception $e) {
            $res['msg'] = $e;
            $res['state'] = 400;
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }


    /**
     * 日志列表
     */
    public function log_list()
    {
        $order_id = I('order_id');
        $order_url = U('b2b/order_list') . '&order_id=' . $order_id . '#/b2bsend';
        $Log = M('log', 'tb_b2b_');
        $where['ORDER_ID'] = $order_id;
        $where['COUNT'] = ['EXP', 'IS NOT NULL'];
        $logs = $Log->where($where)
            ->select();

        $this->assign('logs', $logs);
        $this->assign('order_url', $order_url);
        $this->display();
    }

    /**
     * 版本
     */
    public function v()
    {
        $v = 1.1;
        die($v);
    }

    /**
     * 返回订单所有数据
     *
     * @param $order_id
     *
     * @return mixed
     */
    public function goods_all_powers($order_id)
    {
        $all_power = 0;
        $Ship_list = M('ship_list', 'tb_b2b_');
        $all_power = $Ship_list->field('sum(power_all) as power_all')
            ->where('order_id = ' . $order_id)
            ->select();
        return $all_power[0]['power_all'];
    }


    /**
     * 更新权值求和
     *
     * @param $goods
     *
     * @return bool
     */
    public function sync_ship_power_all($goods, $order_id, $is_scm = false)
    {
        $model = M();
        Logs($goods, 'sync_ship_power_all');
        Logs($order_id, 'order_id');
        $Ship_list = M('ship_list', 'tb_b2b_');
        foreach ($goods as $v) {
            if ($is_scm) {
                $ship_list = $Ship_list->where('ID = ' . $v['SHIP_ID'])->setInc('power_all', $v['power']);
            } else {
                $all_upd_ship_lists[] = $v['SHIP_ID'];
            }
            if (!$ship_list) {
                goto return_data;
            }
            $save_profit['cost_delivery'] = array('exp', 'cost_delivery + ' . $v['power']);
            $save_profit['delivery_currency'] = 'N000590100';
            $profit_upd = $model->table('tb_b2b_profit')->where('ORDER_ID = ' . $order_id)->save($save_profit);
            Logs($profit_upd, '$profit_upd');
            Logs($v, '$v');
        }
        return_data:
        return [true, $all_upd_ship_lists];
    }

    /**
     * @param null $ship_id
     *
     * @return bool
     */
    public function updateShipAllPower($ship_id = null)
    {
        if (empty($ship_id)) {
            $ship_id = I('ship_id');
        }
        if (!$this->Model) {
            $this->Model = new Model();
        }
        $save_all_power['power_all'] = $this->getShipGoodsPower($ship_id);
        if (!$ship_id || !$save_all_power['power_all']) {
            return false;
        }
        $wher_ship['ID'] = $ship_id;
        $this->Model->table('tb_b2b_ship_list')
            ->where($wher_ship)
            ->save($save_all_power);
        return true;
    }

    /**
     * @param $ship_id
     *
     * @return float|int
     */
    private function getShipGoodsPower($ship_id)
    {
        $field = '(t3.send_num * t3.unit_price) AS count_num';
        $res_unit_price_db = $this->Model->table('tb_wms_bill as t1,tb_b2b_ship_list as t2,tb_wms_stream as t3')
            ->field($field)
            ->where("t1.id = t3.bill_id AND t2.warehouse = t1.warehouse_id  AND t2.order_batch_id = t1.link_bill_id AND t2.ID = {$ship_id} ")
            ->select();
        $count_sum = array_sum(array_column($res_unit_price_db, 'count_num'));
        return $count_sum;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function arrayToObject($data)
    {
        $data = json_decode(json_encode($data));
        return $data;
    }


    /**
     * get sku in power value
     *
     * @param $sku
     * @param $date
     *
     * @return int
     */
    private function get_power($sku, $date = null)
    {
        $power = 0;
        $Power = M('power', 'tb_wms_');
        $power_now = $Power->where('SKU_ID = ' . $sku)->getField('weight');
        if ($power_now) $power = $power_now;
        return $power;
    }

    /**
     *  获取总价
     *
     * @param $e
     *
     * @return int
     */
    private function get_sale_income($e)
    {
        return $this->join_income($e, 'actual_payment_amount');
    }

    /**
     * 获取残次品成本
     *
     * @param $e
     *
     * @return int
     */
    private function get_defective_cost($e)
    {
        $Model = M();
        $warehouse_goods = $Model
            ->table('tb_b2b_warehouse_list')
            ->field('tb_b2b_warehousing_goods.warehouse_sku,tb_b2b_warehousing_goods.defective_stored,tb_b2b_ship_list.DELIVERY_TIME')
            ->join('left join tb_b2b_warehousing_goods on tb_b2b_warehousing_goods.warehousing_id =  tb_b2b_warehouse_list.ID')
            ->join('left join tb_b2b_ship_list on tb_b2b_ship_list.ID =  tb_b2b_warehouse_list.SHIP_LIST_ID')
            ->where('tb_b2b_warehouse_list.ORDER_ID = ' . $e)
            ->select();
        $all_cost = 0;
        if (count($warehouse_goods)) {
            $sku_arr = array_column($warehouse_goods, 'warehouse_sku');
            $where_power['SKU_ID'] = array('IN', $sku_arr);
            $power_arr = $Model->table('tb_wms_power')
                ->field('SKU_ID,weight')
                ->where($where_power)
                ->select();
            $power_key_arr = array_column($power_arr, 'weight', 'SKU_ID');
            foreach ($warehouse_goods as $v) {
                $cost['RECOVE_MONEY'] = $v['defective_stored'] * $power_key_arr[$v['warehouse_sku']];
                $cost['bz'] = 'N000590100';
                $cost['DELIVERY_TIME'] = $v['DELIVERY_TIME'];
                $cost_all[] = $cost;
            }
            $all_cost = $this->join_income($cost_all, 'RECOVE_MONEY', 'bz', 'DELIVERY_TIME');
        }
        return $all_cost;
    }

    /**
     * 获取残次品
     *
     * @param $e
     *
     * @return int
     */
    private function get_imperfections_income($e)
    {
        $Warehouse_list = M('warehouse_list', 'tb_b2b_');
        $warehouse_list = $Warehouse_list->where('ORDER_ID = ' . $e)->field('RECOVE_MONEY,recove_curreny,WAREING_DATE')->select();
        return $this->join_income($warehouse_list, 'RECOVE_MONEY', 'recove_curreny', 'WAREING_DATE');
    }

    /**
     * 物流成本
     *
     * @param $e
     *
     * @return int
     */
    private function get_logistics_income($e)
    {
        return $this->join_income($e, 'LOGISTICS_COSTS', 'LOGISTICS_CURRENCY');
    }

    /**
     * 最后核算时间
     */
    private function get_end_time($id)
    {
        $Model = M('receipt', 'tb_b2b_');
        $where['ORDER_ID'] = $id;
        $where['actual_payment_amount'] = array('exp', 'is not null');
        $tb_b2b_receipt = $Model->where($where)->order('ID desc')->getField('create_time');
        return $tb_b2b_receipt;
    }


    /**
     * 获取backend商品采购价
     *
     * @param $e
     *
     * @return int
     */
    private function get_backend_estimat($e, $time)
    {
        $Model = new Model();
        $allsum = 0;
        foreach ($e as $v) {
            $guds_opt_org_prc = $Model->table('tb_ms_guds_opt')->where('GUDS_OPT_ID = ' . $v->skuId)->getField('GUDS_OPT_ORG_PRC');
            $std_xchr_kind_cd = $Model->table('tb_ms_guds')->where('GUDS_ID = ' . substr($v->skuId, 0, -2))->getField('STD_XCHR_KIND_CD');
            $sum = $guds_opt_org_prc * B2bModel::update_currency($std_xchr_kind_cd, $time) * $v->demand;
            $allsum += empty($sum) ? 0 : $sum;
        }
        return $allsum;
    }

    /**
     * 清理缓存
     */
    public function clean_cache()
    {
        /*$get_path = empty(I('get_path')) ? '/Temp' : I('get_path');
        $path = APP_PATH . '/Runtime' . $get_path;
        $dirarr = scandir($path);
        foreach ($dirarr as $v) {
            if ($v != '.' && $v != '..') {
                unlink($path . '/' . $v);
            }
            Logs($v, 'v', 'del');
        }
        Logs($path, 'path', 'del');
        Logs($dirarr, '$dirarr', 'del');*/
        if (RedisModel::del_key('erp_node_cache')) {
            $this->success('del cache success', U('Menu/menu_list'));
        }
    }

    /**
     * 测试
     */
    public function test()
    {
        $this->updateStartRemindingDateReceipt(1324);
    }


//    array to object

    /**
     * @param $e
     * @param string $k
     *
     * @return array
     */
    private function arr2obj($e, $k = '')
    {
        foreach ($e as $v) {
            $arr[] = [$k => $v];
        }
        return $arr;
    }


    /**
     * @param $e
     */
    private function get_doship($e)
    {

    }

    /**
     * @param $e
     *
     * @return mixed
     */
    private function unking($e)
    {
        return str_replace(',', '', $e);
    }

//    ship list and goods

    /**
     * @param $order_id
     *
     * @return mixed
     */
    private function get_list_goods($order_id)
    {
        $Model = new Model();
        $ship_list = $Model->table('tb_b2b_ship_list')
            ->where('order_id = ' . $order_id)
            ->select();
        foreach ($ship_list as &$v) {
            $v['goods'] = $Model->table('tb_b2b_ship_goods')
                ->where('SHIP_ID = ' . $v['ID'])
                ->join('left join tb_b2b_goods on tb_b2b_goods.ORDER_ID = ' . $order_id . ' AND tb_b2b_goods.SKU_ID = tb_b2b_ship_goods.SHIPPING_SKU ')
                ->field('tb_b2b_ship_goods.*,tb_b2b_goods.goods_title,tb_b2b_goods.goods_info')
                ->select();
        }
        return $ship_list;
    }

//    ship list and goods

    /**
     * @param $order_id
     * @param null $ID
     *
     * @return mixed
     */
    private function get_warehousing_goods($order_id, $ID = null)
    {
        $Model = new Model();
        $where['tb_b2b_warehouse_list.order_id'] = $order_id;
        if ($ID) $where['tb_b2b_warehouse_list.ID'] = $ID;
        $ship_list = $Model->table('tb_b2b_warehouse_list')
            ->field('tb_b2b_warehouse_list.*,
            tb_b2b_ship_list.BILL_LADING_CODE,
            tb_b2b_ship_list.warehouse as ship_warehouse,
            tb_b2b_ship_list.DELIVERY_TIME as ship_delivery_time,
            tb_b2b_ship_list.Estimated_arrival_DATE,
            tb_b2b_ship_list.REMARKS,
            tb_b2b_ship_list.power_all,
            tb_b2b_ship_list.DELIVERY_TIME')
            ->join('left join tb_b2b_ship_list on  tb_b2b_ship_list.ID = tb_b2b_warehouse_list.SHIP_LIST_ID  ')
            ->where($where)
            ->select();
        foreach ($ship_list as $v) {
            $v_arr[] = $v['ID'];
        }
        $where_goods['warehousing_id'] = array('in', $v_arr);
        $v_goods = $Model->table('tb_b2b_warehousing_goods')
            ->field('tb_b2b_warehousing_goods.*,
                tb_b2b_warehousing_goods.DELIVERED_NUM as DELIVERED_NUM_z,
                tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM as DELIVERED_NUM,
                tb_b2b_goods.goods_title,tb_b2b_goods.goods_info,
                tb_b2b_goods.price_goods,
                (tb_b2b_goods.price_goods/(1 + (IF(tb_ms_cmn_cd.CD_VAL, replace(tb_ms_cmn_cd.CD_VAL, \'%\', \'\'), 0) / 100))) AS  price_goods_no_tax,
                tb_b2b_goods.jdesc')
            ->join('left join tb_b2b_goods on tb_b2b_goods.ORDER_ID = ' . $order_id . ' AND tb_b2b_goods.SKU_ID = tb_b2b_warehousing_goods.warehouse_sku AND tb_b2b_goods.ID = tb_b2b_warehousing_goods.goods_id ')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_b2b_goods.purchase_invoice_tax_rate')
            ->where($where_goods)
            ->select();
        foreach ($v_goods as $k => $v) {
            $jdesc = isset($v['jdesc']) ? $v['jdesc'] : null;
            $arr = unserialize($jdesc);
            $arr['price'] = isset($arr['price']) ? $arr['price'] : null;
            if ($arr['price'] !== null) {
                $v_goods[$k]['price_goods'] = $arr['price'];
            }
        }
        foreach ($v_goods as $k => &$v) {
            if (is_null($v['end_amount'])) {
                $v['end_amount'] = $v['DELIVERED_NUM'] * $v['price_goods'];
            }
        }

        $v_goods = SkuModel::getInfo($v_goods, 'sku_show',
            ['spu_name', 'attributes', 'image_url', 'product_sku'],
            ['spu_name' => 'goods_title', 'attributes' => 'goods_info', 'image_url' => 'guds_img_cdn_addr']
        );
        $v_goods = SkuModel::getTableAttr($v_goods);
        foreach ($v_goods as $k =>  $item) {
            if(isset($item['product_sku']) && isset($item['product_sku']['upc_more']) &&
             !empty($item['product_sku']['upc_more'])) {
                $upc_more_arr = explode(',', $item['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $item['product_sku']['upc_id']);
                $v_goods[$k]['bar_code'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
            }
        }

        $ship_list[0]['goods'] = $v_goods;
        return $ship_list;
    }

    /**
     * @param        $e
     * @param        $key
     * @param null $bz
     * @param string $time
     *
     * @return int
     */
    private function join_income($e, $key, $bz = null, $time = 'DELIVERY_TIME')
    {
        $sum = 0;
        foreach ($e as $v) {
            if (empty($bz)) {
                $sum += $v[$key];
            } else {
                $sum += $v[$key] * B2bModel::update_currency($v[$bz], date('Y-m-d', strtotime($v[$time])));
            }
        }
        return $sum;
    }

    /**
     * @return array
     */
    private function get_code_key($nm_val)
    {
        $code_state = B2bModel::get_code($nm_val);
        $code_state_arr = array_column($code_state, 'CD', 'CD_VAL');
        return $code_state_arr;
    }

    /**
     * @param $e
     */
    private function down_existing($e)
    {
        $expTitle = "应收款单";
        $expCellName = array(
            array('ID', '收款单号'),
            array('PO_ID', 'B2B订单号'),
            array('THR_PO_ID', 'PO单号'),
            array('our_company', '我方公司'),
            array('client_id', '客户'),
            array('kxlx', '款项类型'),
            array('receiving_code', '收款节点与比例'),
            array('expect_receipt_amount', '预期金额'),
            array('expect_receipt_date', '预期收款时间'),
            array('receipt_operation_status', '收款状态'),
            array('yqqk', '逾期情况'),
            array('sales_team_id', '销售'),
        );

//        join exp excel
        foreach ($e as $key => $val) {
            $join_data['ID'] = $val['ID'];
            $join_data['PO_ID'] = $val['PO_ID'];
            $join_data['THR_PO_ID'] = $val['THR_PO_ID'];
            $join_data['our_company'] = $val['our_company'];
            $join_data['client_id'] = $val['client_id'];
            $join_data['kxlx'] = empty($val['receiving_code']) ? '退税' : '货款';
            $join_data['receiving_code'] = $val['receiving_code'];
            $join_data['expect_receipt_amount'] = $val['expect_receipt_amount'];
            $join_data['expect_receipt_date'] = $val['expect_receipt_date'];
            $join_data['receipt_operation_status'] = $val['receipt_operation_status'];
            $join_data['yqqk'] = $val['yqqk'];
            $join_data['sales_team_id'] = $val['sales_team_id'];
            $expTableData[] = $join_data;
        }
        $excel = new StockAction();
        $excel->exportExcel($expTitle, $expCellName, $expTableData);
    }

    /**
     * 清理关闭订单
     */
    public function click_err_close()
    {
        $arr = [];
        foreach ($arr as $v) {
            $result = json_decode(curl_request($v), 1);
            print_r($result);
        }
    }

    /**
     * 利润计算
     *
     * @param      $order_id
     * @param null $arr
     *
     * @return mixed
     */
    private function exchange_rete_calculation($order_id, $arr = null)
    {
        // $data = RedisModel::get_key('b2b_exchange_rete_calculation_' . $order_id);
        if (empty($data)) {
            $Model = new Model();
            $data['info'] = $Model->table('tb_b2b_order')->where('tb_b2b_order.ID = ' . $order_id)
                ->field('tb_b2b_order.*,tb_b2b_info.*')
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_order.ID')
                ->select();
            $data['info'][0]['rebate_rate'] = $data['info'][0]['rebate_rate'] * 100 . '%';
            $data['goods'] = $Model
                ->table('tb_b2b_goods')
                ->field('tb_b2b_goods.ID,
                tb_b2b_goods.ORDER_ID,
                tb_b2b_goods.SKU_ID,
                tb_b2b_goods.sku_show,
                tb_b2b_goods.goods_title,
                tb_b2b_goods.goods_info,
                tb_b2b_goods.warehouse_code,
                tb_b2b_goods.price_goods,
                tb_b2b_goods.required_quantity,
                tb_b2b_goods.is_tax_rebate,
                tb_b2b_goods.tax_rebate_ratio,
                tb_b2b_goods.purchasing_team,
                tb_b2b_goods.introduce_team,
                tb_b2b_goods.SHIPPED_NUM,
                tb_b2b_goods.TOBE_DELIVERED_NUM,
                tb_b2b_goods.is_inwarehouse_num,
                tb_b2b_goods.currency,
                tb_b2b_goods.jdesc,
                tb_b2b_goods.percent_sale,
                tb_b2b_goods.percent_purchasing,
                tb_b2b_goods.percent_introduce,
                tb_b2b_goods.purchasing_price,
                tb_b2b_goods.purchasing_currency,
                tb_b2b_goods.purchasing_num,
                tb_b2b_goods.delivery_prices,
                tb_b2b_goods.procurement_number,
                tb_b2b_goods.batch_id,
                tb_b2b_goods.purchase_invoice_tax_rate,
                IFNULL(SUM(tb_b2b_warehousing_goods.DELIVERED_NUM),0) AS normal_goods,
                IFNULL(SUM(tb_b2b_warehousing_goods.incomplete_number),0) AS normal_cargo
                ')
                ->join('LEFT JOIN tb_b2b_warehousing_goods ON tb_b2b_goods.ID = tb_b2b_warehousing_goods.goods_id ')
                ->where('tb_b2b_goods.ORDER_ID = ' . $order_id)
                ->group('tb_b2b_goods.ID')
                ->select();
            $data['info'] = BaseCommon::fmt_orderinfo_list($data['info']);
            $data['info'] = BaseCommon::state_order_list($data['info']);
            $data['goods'] = BaseCommon::fmt_goods_list($data['goods']);//        物流
            $data['ship'] = $Model->table('tb_b2b_ship_list')
                ->field('tb_b2b_ship_list.*,
                tb_ms_cmn_cd.CD_VAL AS issue_warehouse,
                tb_b2b_warehouse_list.status AS warehousing_state,
                tb_b2b_warehouse_list.WAREING_DATE AS tally_time,
                tb_b2b_warehouse_list.ID as warehouse_list_id,
                IFNULL(SUM((tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM - tb_b2b_warehousing_goods.DELIVERED_NUM)*tb_b2b_goods.price_goods),0) AS damage_charge
                ')
                ->join('tb_b2b_warehouse_list on tb_b2b_warehouse_list.SHIP_LIST_ID = tb_b2b_ship_list.ID')
                ->join('tb_b2b_warehousing_goods on tb_b2b_warehouse_list.ID = tb_b2b_warehousing_goods.warehousing_id')
                ->join('tb_b2b_goods on tb_b2b_goods.ID = tb_b2b_warehousing_goods.goods_id')
                ->join('tb_ms_cmn_cd on tb_ms_cmn_cd.cd = tb_b2b_ship_list.warehouse')
                ->where('tb_b2b_ship_list.order_id = ' . $order_id)
                ->group('tb_b2b_ship_list.ID')
                ->select();//        收款
            $data['ship'] = array_map(function ($value) {
                if (0 == $value['warehousing_state']) {
                    unset($value['damage_charge']);
                }
                return $value;
            }, $data['ship']);
            $data['return'] = $Model->table('tb_b2b_return')
                ->alias('t')
                ->field('id,return_no,a.CD_VAL status,b.CD_VAL warehouse,t.created_by,t.created_at,t.warehoused_by,t.warehoused_at')
                ->join('tb_ms_cmn_cd a on a.CD=t.status_cd')
                ->join('tb_ms_cmn_cd b on b.CD=t.warehouse_cd')
                ->where(['order_id' => $order_id])
                ->select();
            $B2bService = new B2bService();
            $data['ship'] = $B2bService->orderStatusToVal($data['ship']);
            $data['receipt'] = $Model->table('tb_b2b_receipt')->where('tb_b2b_receipt.ORDER_ID = ' . $order_id)
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
                ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
                ->join('left join (select max(WAREING_DATE) as WAREING_DATE,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
                ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by order_id order by Estimated_arrival_DATE desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
                ->field('tb_b2b_receipt.*,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_doship.shipping_status,tb_b2b_doship.update_time,tb_b2b_warehouse_list.status,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_ship_list.SUBMIT_TIME')
                ->select();//        利润
            $data['receipt'] = (new B2bService())->checkOverdueDay($data['receipt'], 'expect_receipt_date');

            $data['profit']['YGDQ'] = number_format($data['info'][0]['deducting_tax'] * B2bModel::update_currency($data['info'][0]['deducting_tax_currency'], $data['info'][0]['po_time']), 2);// 抵扣
            $data['profit']['A'] = number_format($data['info'][0]['po_amount'] * (B2bModel::update_currency($data['info'][0]['po_currency'], $data['info'][0]['po_time'])), 2);//PO金额
            //        $data['profit']['B'] = number_format($data['info'][0]['tax_rebate_income'] * (B2bModel::update_currency($data['info'][0]['po_currency'], $data['info'][0]['po_time'])), 2);//退税收入
            $data['profit']['B'] = number_format($data['info'][0]['drawback_estimate'] * (B2bModel::update_currency($data['info'][0]['backend_currency'], $data['info'][0]['po_time'])), 2);
            $data['profit']['C'] = number_format($this->unking($data['profit']['A']) + $this->unking($data['profit']['B']), 2);//总收入
            $data['profit']['D'] = number_format($data['info'][0]['backend_estimat'] * (B2bModel::update_currency($data['info'][0]['backend_currency'], $data['info'][0]['po_time'])), 2);
            $data['profit']['E'] = number_format($data['info'][0]['logistics_estimat'] * (B2bModel::update_currency($data['info'][0]['logistics_currency'], $data['info'][0]['po_time'])), 2);// 预估物流成本
            $data['profit']['F'] = number_format($this->unking($data['profit']['D']) + $this->unking($data['profit']['E']) + $this->unking($data['profit']['YGDQ']), 2);//预估总成本
            $data['profit']['G'] = number_format($this->unking($data['profit']['C']) - $this->unking($data['profit']['F']), 2);//预估利润
            $data['profit']['H'] = round($this->unking($data['profit']['G']) / $this->unking($data['profit']['C']), 4) * 100;// 预估利润率
            $data['profit']['order_time'] = $data['info'][0]['create_time'];// 生成时间
            $data['profit']['SJDQ'] = number_format($data['receipt'][0]['deducting_tax'] * B2bModel::update_currency($data['receipt'][0]['deducting_tax_currency'], $data['receipt'][0]['actual_receipt_date']), 2);// 实际抵扣
            $data['profit']['I'] = number_format(($this->get_sale_income($data['receipt']) * (B2bModel::update_currency($data['info'][0]['po_currency'], $data['info'][0]['po_time']))), 2);//实际销售收入$this->unking($data['profit']['B'])
            $data['profit']['J'] = $data['profit']['B'];//$data['profit']['K'] = number_format($this->get_imperfections_income($order_id), 2); // 残次品
            $data['profit']['K'] = number_format($this->get_defective_cost($order_id), 2);// 可在入库残次品成本
            $data['profit']['L'] = number_format($this->unking($data['profit']['I']) + $this->unking($data['profit']['J']) + $this->unking($data['profit']['K']), 2);
            $data['profit']['M'] = number_format($this->goods_all_powers($order_id) * (B2bModel::update_currency('N000590100', date('Y-m-d', strtotime($data['ship'][0]['DELIVERY_TIME'])))), 2);// 实际商品成本
            $data['profit']['N'] = number_format($this->get_logistics_income($data['ship']), 2);//物流成本
            $data['profit']['U'] = number_format($this->unking($data['profit']['M']) + +$this->unking($data['profit']['SJDQ']) + $this->unking($data['profit']['N']) - $this->unking($data['profit']['K']), 2);// 实际总成本
            $data['profit']['V'] = number_format($this->unking($data['profit']['L']) - $this->unking($data['profit']['U']), 2);
            $data['profit']['create_time'] = $this->get_end_time($order_id);
            if (!$data['profit']['create_time']) {
                $data['profit']['W'] = '-';
            } else {
                $data['profit']['W'] = round($this->unking($data['profit']['V']) / $this->unking($data['profit']['L']), 4) * 100;
            }
            $data = BaseCommon::fmt_order_profit($data);
            // RedisModel::set_key('b2b_exchange_rete_calculation_' . $order_id, json_encode($data, JSON_UNESCAPED_UNICODE), null, 10);
        } else {
            $data = json_decode($data, true);
        }
        return $data; // 最后核算时间
    }

    /**
     * @param        $order_id
     * @param        $node
     * @param string $state 订单状态
     *
     * @return bool
     */
    private function upd_order_node($order_id, $node, $state = 'order_state', $Model = null)
    {
        if (empty($Model)) {
            $Model = M();
        }
        $save[$state] = $node;
        $order = $Model->table('tb_b2b_order')->where('ID = ' . $order_id)->save($save);
        return $order;
    }

    /**
     * @param $e
     *
     * @return mixed
     */
    private function rm_sign($e)
    {
        foreach ($e as &$v) {
            $v['CD_VAL'] = rtrim($v['CD_VAL'], '%');
        }
        return $e;
    }

    /**
     * 合同查询
     */
    public function get_ht()
    {
        $sp_charter_no = trim(I('sp_charter_no'));
        $like_no = trim(I('like_no'));
        $Model = M();
        if ($like_no == 1) {
            $where_s['tb_crm_sp_supplier.SP_NAME'] = array('eq', $sp_charter_no);
        } else {
            $where_s['tb_crm_sp_supplier.SP_NAME'] = array('like', '%' . $sp_charter_no . '%');
        }
        $where_s['tb_crm_sp_supplier.DATA_MARKING'] = array('eq', '1');
        $contract = $Model->table('tb_crm_sp_supplier')
            ->field('tb_crm_contract.CON_NO,tb_crm_contract.CON_COMPANY_CD,tb_crm_sp_supplier.SP_NAME,tb_crm_sp_supplier.SP_NAME_EN')
            ->join('left join tb_crm_contract on tb_crm_contract.SP_CHARTER_NO = tb_crm_sp_supplier.SP_CHARTER_NO')
            ->order('tb_crm_contract.CREATE_TIME desc')
            ->where($where_s)->select();
        $data['contract'] = $contract;
        $data['contract_key'] = array_unique(array_column($contract, 'SP_NAME'));
        $data['contract_en_name'] = array_unique(array_column($contract, 'SP_NAME_EN'));
        $data['c2c_data'] = array_column($contract, 'CON_COMPANY_CD', 'CON_NO');
        $data['cd_company'] = BaseModel::conCompanyCd();
        $this->ajaxReturn($data, '', 1);
    }

    /**
     * 获取币种
     */
    public function get_currency_backend()
    {
        echo B2bModel::update_currency(I('currency'), I('date'), I('dst_currency'));
    }

    /**
     * 获取币种
     * RMB to CNY
     */
    public function get_currency_backend_gathering()
    {
        $post = B2bModel::get_data();
        if ($post->dst_currency == 'RMB') $post->dst_currency = 'CNY';
        $key = sha1("$post->currency.$post->date.$post->dst_currency");
        $update_currency = RedisModel::get_key($key);
        if (empty($update_currency)) {
            $update_currency = B2bModel::update_currency($post->currency, $post->date, $post->dst_currency);
            RedisModel::set_key($key, $update_currency, null, 50);
        }
        Logs([$key, $update_currency]);
        echo $update_currency;
    }

    /**
     * 导入商品
     */
    public function importGoods()
    {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error('请上传EXCEL文件', '', true);
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $expe = [];
        $goods_info_url = U('Stock/Searchguds', '', '', false, true);
        $msg = '';
        $skus = [];
        $all_warehouse = StockModel::get_all_warehouse();
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $temp = [];
            $search = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $price = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $number = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $res = json_decode(curl_request($goods_info_url, ['GSKU' => $search]), true);
            $res_json['sku'] = $search;
            $res_json['price'] = (float)$price;
            $res_json['number'] = (float)$number;
            $res_jsons[] = $res_json;
            if (!$search || $res['status'] == 0) {
                $error = true;
                $msg .= "第{($currentRow+1)}行商品不存在<br />";
                $err_json[($currentRow + 1)][] = '商品不存在';
            } else {
                $sku = $res['info'][0]['GUDS_OPT_ID'];
                //if ($key = array_search($sku, $skus)) {
                //$error = true;
                //$msg .= "第{$currentRow}行与第{$key}行商品重复<br />";
                //$err_json[$currentRow][] = "与第{$key}行商品重复";
                //}
                $skus[$currentRow] = $res['info'][0]['GUDS_OPT_ID'];
            }
            if (!is_numeric($price) || $price <= 0) {
                $error = true;
                $msg .= "第{($currentRow+1)}行商品价格有误（比如数量中含0、或者为负数）<br />";
                $err_json[($currentRow + 1)][] = '商品价格有误（比如数量中含0、或者为负数）';
            }
            if (!is_numeric($number) || strstr($number, '.') || $number <= 0) {
                $error = true;
                $msg .= "第{($currentRow+1)}行商品数量有误（比如数量中含小数、或者小于1）<br />";
                $err_json[($currentRow + 1)][] = '商品数量有误（比如数量中含小数、或者小于1）';

            }

            if ($sku && $price && $number) {
                $temp['search'] = $search;
                $temp['sku'] = $sku;
                $temp['now_number'] = $currentRow - 1;
                $temp['price'] = (float)$price;
                $temp['number'] = (float)$number;
                $temp['goods_money'] = (float)$price * (float)$number;
                $temp['goods_name'] = $res['info'][0]['Guds']['GUDS_NM'];
                $temp['warehouse'] = $all_warehouse[$res['info'][0]['Guds']['DELIVERY_WAREHOUSE']]['warehouse'];
                $temp['guds_img'] = $res['info'][0]['Img'];
                $temp['val_str'] = $res['info']['opt_val'][0]['val'];
                $temp['drawback'] = B2bModel::TO_CD_VAL($res['info'][0]['Guds']['RETURN_RATE']);
                $temp['STD_XCHR_KIND_CD'] = $res['info'][0]['Guds']['STD_XCHR_KIND_CD'];
                $temp['GUDS_OPT_ORG_PRC'] = $res['info'][0]['GUDS_OPT_ORG_PRC'];
                $expe[] = $temp;
            }
        }
        if ($error) {
            $err_res['err_json'] = $err_json;
            $err_res['res_jsons'] = $res_jsons;
            $this->error(json_encode($err_res), '', true);
        } else {
            $this->success($expe, '', true);
        }
    }

    /**
     *
     */
    public function down_err_goods()
    {
        $data = $this->_param();
        $data = json_decode($data['json_res'], true);
        $expTitle = "异常商品信息";
        $expCellName = array(
            array('SKU_ID', '商品编码/条形码(SKUID、Bar/JAN code)'),
            array('price', '单价(Unit Price)'),
            array('number', '数量（Quantity）'),
            array('Notice', '提示/Notice'),
        );
//        join exp excel
        foreach ($data['res_jsons'] as $key => $val) {
            $join_data['SKU_ID'] = $val['sku'];
            $join_data['price'] = $val['price'];
            $join_data['number'] = $val['number'];
            $join_data['Notice'] = $this->join_notice($data['err_json'][$key + 3]);
            $expTableData[] = $join_data;
        }
        $Stock = new StockAction();
        $Stock->exportExcel($expTitle, $expCellName, $expTableData);
    }

    /**
     * @param $e
     *
     * @return string
     */
    private function join_notice($e)
    {
        return implode(",", $e);
    }


    /**
     *
     */
    public function importPo()
    {
        header("content-type:text/html;charset=utf-8");
        if ($_FILES['size'] > 20971520) {
            $info = '保存失败：上传文件大于20m';
            $this->error($info, 0, true);
        }
        $filePath = $_FILES['file']['tmp_name'];
        if ($_FILES['file']['name']) {
            // 图片上传
            $fd = new FileUploadModel();
            if ($_SERVER['SERVER_NAME'] == '172.16.13.57' || $_SERVER['SERVER_NAME'] == 'sms2.b5c.com') $fd->filePath = __DIR__;
            $ret = $fd->uploadFile();
            if ($ret) {
                $save_list['VOUCHER_ADDRESS'] = $fd->save_name;
                $save_list['file_name'] = $_FILES['file']['name'];
                $this->success($save_list, 1, true);
            } else {
                $info = '保存失败：上传文件失败' . $fd->error;
                $this->error($info, 0, true);
            }
        }

    }

    /**
     * 批量更新
     *
     * @param $datas
     * @param $model
     *
     * @return false|int
     */
    public function saveAll($datas, $model, $Models)
    {
        $sql = ''; //Sql
        $lists = []; //记录集$lists
        $pk = $this->getPk();//获取主键
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk === $key) {
                    $ids[] = $value;
                } else {
                    $lists[$key] .= sprintf("WHEN %u THEN '%s' ", $data[$pk], $value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s )', strtolower($model), rtrim($sql, ','), $pk, implode(',', $ids));
        if (empty($Models)) {
            $Models = M();
        }
        return $Models->execute($sql);
    }

    /**
     * @return string
     */
    public function getPk()
    {
        return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
    }

    /**
     * @return string
     */
    public function setPk($pk)
    {
        return  $this->pk = isset($pk) ? $pk : $this->pk;
    }

    /**
     * @param      $order_id
     * @param      $key
     * @param      $state
     * @param bool $open_log
     *
     * @return mixed
     */
    public function upd_order_state($order_id, $key, $state, $open_log = false)
    {
        $Model = M();
        $where['ID'] = $order_id;
        $data[$key] = $state;
        $order_state = $Model->table('tb_b2b_order')->where($where)->data($data);
        if ($open_log) B2bModel::addLog($order_id, $order_state, '更新订单状态');
        return $order_state;
    }


    /**
     *
     */
    public function test_curl()
    {
        $url_test = 'sms2.b5c.com/index.php?m=b2b&a=push_order_test';
        $connect = Array(
            'v' => 'v1.1',
            'ip' => '127.0.0.1',
            'appKey' => 'BWMNB',
            'sessionKey' => '2015BWMNBAV1',
            'datetime' => date('YmdHis', time()),
        );
//        $request = $this->Curl_post_test($url_test, $connect);
        $request = $this->curl_test($url_test, $connect);
        echo '<pre />';
        var_dump($request);
        echo '<br />';
        var_dump(json_decode($request));
    }

    /**
     *
     */
    public function push_order_test()
    {
        $arr['post'] = $_POST;
        $arr['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
        $arr['v'] = $_POST['v'];
        echo json_encode($arr);
    }

    /**
     * @param $url
     * @param $data
     */
    public function curl_test($url, $data)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_exec($ch);
    }

    /**
     *
     */
    public function data_to_code()
    {
        $M = M();

        $info['DELIVERY_METHOD'] = [
            'FOB' => 'N001170200',
            'CIF' => 'N001170300',
            '一般贸易' => 'N001530500',
            '一般 贸易' => 'N001530500',
            'KR DOMESTIC' => 'N001530700',
            '其他/OTHERS' => 'N001530600',
        ];
        $info['PAYMENT_NODE']['nodeType'] = [
            'N001390100', 'N001390200', 'N001390300', 'N001390400', 'N001390500'
        ];
        $info['PAYMENT_NODE']['nodeDate'] = [
            'N001420100', 'N001420300', 'N001420400', 'N001420500', 'N001420600', 'N001420800', 'N001421000', 'N001421100', 'N001421200', 75, 'N001421300', 'N001421400', 0];

        $info['INVOICE_CODE'] = ["N001350400", "N001350200", "N001350100"];
        $info['TAX_POINT'] = ['N001340600', 'N001340500', 'N001340400', 'N001340300', 'N001340200', 'N001340100'];
        $info_upd = ['ID', 'DELIVERY_METHOD', 'PAYMENT_NODE', 'INVOICE_CODE', 'TAX_POINT'];
        $infos = $M->field($info_upd)->table('tb_b2b_info')->select();
        unset($info_upd[0]);
        echo '<pre>';

        foreach ($infos as $val) {
            foreach ($val as $k => $v) {
                if (strlen($val['TAX_POINT']) < 3) {
                    if ($k == 'PAYMENT_NODE') {
                        $un_v = json_decode($v, true);

                        foreach ($un_v as &$m) {
                            $m['nodeType'] = $info['PAYMENT_NODE']['nodeType'][$m['nodeType']];
                            $m['nodeDate'] = $info['PAYMENT_NODE']['nodeDate'][$m['nodeDate']];
                        }
                        $arr[$k] = json_encode($un_v);
                    } elseif (in_array($k, $info_upd)) {
                        $arr[$k] = $info[$k][$v];
                    }
                } else {
                    trace($v, '$v');
                }
            }
            if (strlen($val['tax_point']) < 3) {
                $res = $M->table('tb_b2b_info')->where('ID = ' . $val['ID'])->save($arr);

            }
        }

        $recepit_arr['receiving_code']['nodeType'] = [
            'N001390100', 'N001390200', 'N001390300', 'N001390400', 'N001390500'
        ];
        $recepit_arr['receiving_code']['nodeDate'] = [
            'N001420100', 'N001420300', 'N001420400', 'N001420500', 'N001420600', 'N001420800', 'N001421000', 'N001421100', 'N001421200', 75, 'N001421300', 'N001421400', 0];
        $recepit_arr['invoice_type'] = ["N001350400", "N001350200", "N001350100"];
        $recepit_arr['tax_point'] = ['N001340600', 'N001340500', 'N001340400', 'N001340300', 'N001340200', 'N001340100'];
        $recepit_upd = ['ID', 'receiving_code', 'invoice_type', 'tax_point'];
        $recepit = $M->field($recepit_upd)->table('tb_b2b_receipt')->select();

        unset($recepit_upd[0]);
        unset($arr);
        foreach ($recepit as $val) {
            foreach ($val as $k => $v) {
                if (strlen($val['tax_point']) < 3) {
                    if ($k == 'receiving_code') {
                        $m = json_decode($v, true);
                        $m['nodeType'] = $recepit_arr['receiving_code']['nodeType'][$m['nodeType']];
                        $m['nodeDate'] = $recepit_arr['receiving_code']['nodeDate'][$m['nodeDate']];
                        $arr[$k] = json_encode($m);
                    } elseif (in_array($k, $recepit_upd)) {
                        $arr[$k] = $recepit_arr[$k][$v];
                    }
                } else {
                    trace($v, '$v');
                }
            }
            if (strlen($val['tax_point']) < 3) {
                $res = $M->table('tb_b2b_receipt')->where('ID = ' . $val['ID'])->save($arr);
            }

        }

    }


    /**
     * @param $url
     * @param $data
     *
     * @return mixed
     */
    public function Curl_post_test($url, $data)
    {
        // 建立CURL連線
        $ch = curl_init();

        // 設定擷取的URL網址
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:application/x-www-form-urlencoded"));
//        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:application/json"));
//        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:multipart/form-data"));

        //設定要傳的 變數A=值A & 變數B=值B (中間要用&符號串接)
        $PostData = $data;

        //設定CURLOPT_POST 為 1或true，表示要用POST方式傳遞
        curl_setopt($ch, CURLOPT_POST, 1);
        //CURLOPT_POSTFIELDS 後面則是要傳接的POST資料。

        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);

        // 執行
        $temp = curl_exec($ch);
        return $temp;
        // 關閉CURL連線
        curl_close($ch);
    }

    /**
     * @param $warehousing_arr
     * @param $key_num
     * @param $column
     *
     * @return int
     */
    private function play_status($warehousing_arr, $key_num, $column)
    {
        $key = array_column($warehousing_arr, $column);
        $status_sum = array_sum($key);
        $status_count = count($key);
        if ($status_count > 0 && ($key_num * $status_count == $status_sum)) {
            return $status = 2;
        }
        return $status = 1;
    }

    /**
     * @param $poData
     * @param $Models
     * @param $tpl_path
     *
     * @return mixed
     */
    private function order_mail_send($poData, $Models, $tpl_path)
    {
        if (!class_exists('OverdueAction')) {
            require_once APP_PATH . 'Lib/Action/Home/OverdueAction.class.php';
        }
        $mailData = B2bData::mail_data_for_summary($poData);
        $mailData['forecast'] = BaseCommon::fmt_the_forecast($mailData['forecast']);
        $mailData['PO_number'] = $poData['THR_PO_ID'] ? $poData['THR_PO_ID'] : '无';
        $this->assign('mail', $mailData);
        $message = $this->fetch($tpl_path);
        $user_arr = B2bModel::get_code_y('销售团队')[$poData['SALES_TEAM']]['ETc'];
        if (strpos($user_arr, ',') !== false) {
            $user_arr = explode(',', $user_arr);
            $user = $user_arr[0];
            unset($user_arr[0]);
            $cc_arr = $user_arr;
        } else {
            $user = $user_arr;
        };
        if (session('m_loginname')) $cc_arr[] = session('m_loginname') . $this->_pre_mail;
        $cc_arr[] = $poData['PO_USER'] . $this->_pre_mail;
        $cc_arr = array_unique($cc_arr);
        $cc_repeat_key = array_search($user, $cc_arr);
        if ($cc_repeat_key) {
            unset($cc_arr[$cc_repeat_key]);
        }
        // debug
        if (APP_STATUS == 'stage') {
            $tmp = array($poData['PO_ID'], $message, $user, $cc_arr);
            Log::write('--debug_mail--' . json_encode($tmp),'INFO');
            // return null;
        }
        if (defined('APP_SEND_MAIL_TEST') and APP_SEND_MAIL_TEST) {
            $user = APP_SEND_MAIL_TEST;
            $cc_arr = null;
        }
        $msg_mail = OverdueAction::order_mail_send($poData['PO_ID'], $poData['THR_PO_ID'], $message, $user, $cc_arr);

        return $msg_mail;
    }

    /**
     * @param $initdata
     *
     * @return mixed
     */
    private function gatheringDataJoin($initdata)
    {
        $id = I('id');
        $receipt = M('receipt', 'tb_b2b_');
        $gathering = $receipt->where('tb_b2b_receipt.id = ' . $id)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_doship on tb_b2b_doship.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join tb_b2b_profit on tb_b2b_profit.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->join('left join (select max(WAREING_DATE) as WAREING_DATE ,status,ORDER_ID from tb_b2b_warehouse_list group by ORDER_ID) as tb_b2b_warehouse_list on tb_b2b_warehouse_list.ORDER_ID = tb_b2b_receipt.ORDER_ID ')
            ->join('left join (select tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_ship_list.order_id,max(tb_b2b_ship_list.Estimated_arrival_DATE) as Estimated_arrival_DATE,max(tb_b2b_ship_list.DELIVERY_TIME) as DELIVERY_TIME from tb_b2b_ship_list group by tb_b2b_ship_list.order_id order by tb_b2b_ship_list.ID desc ) as tb_b2b_ship_list on tb_b2b_ship_list.order_id =  tb_b2b_receipt.ORDER_ID ')
            ->field('tb_b2b_receipt.*,tb_b2b_receipt.actual_payment_amount as actual_payment_amount_z,tb_b2b_receipt.expect_receipt_amount as actual_payment_amount,tb_b2b_info.THR_PO_ID,tb_b2b_info.PO_USER,tb_b2b_info.po_time,tb_b2b_info.DELIVERY_METHOD,tb_b2b_info.po_currency,tb_b2b_info.CLIENT_NAME,tb_b2b_info.sale_tax,tb_b2b_info.our_company as our_company_info,tb_b2b_info.our_company as company_our,tb_b2b_receipt.company_our as company_our_receipt,tb_b2b_doship.update_time,tb_b2b_ship_list.Estimated_arrival_DATE,tb_b2b_ship_list.DELIVERY_TIME,tb_b2b_warehouse_list.WAREING_DATE,tb_b2b_ship_list.SUBMIT_TIME,tb_b2b_profit.delivery_currency,tb_b2b_profit.cost_delivery,tb_b2b_profit.logistics_currency,tb_b2b_profit.logistics_costs,tb_b2b_profit.defective_num,tb_b2b_profit.defective_currency,tb_b2b_profit.recoverable_amount')
            ->find();
        $gathering['all_node'] = $receipt->where('ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND transaction_type IS NULL')->order('ID')->field('receiving_code')->select();
        // 计算总额
        $gathering_arr = $receipt->where('ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND transaction_type IS NULL')->order('ID')->field('receipt_operation_status')->select();
        list($gathering['all_order_money'], $gathering['all_money'], $gathering['all_money_po']) = $this->allMoneyGet($receipt, $gathering);

        $gathering['all_diff'] = $this->all_diff($gathering);
        /*foreach ($gathering_arr as $v) {
            $gathering['all_money'] += $v['actual_payment_amount'];
        }*/
        //  期数判断
        $node_count = count($gathering['all_node']);
        $arr_receving = json_decode($gathering['receiving_code'], true);
        $is_end = 0;
        if ($node_count == $arr_receving['nodei'] + 1) $is_end = 1;
        // 控制收款节点
        $node_status = true;
        if ($node_count > 1 && $arr_receving['nodei'] != 0) {
            if ($gathering_arr[$arr_receving['nodei'] - 1]['receipt_operation_status'] == 0) $node_status = false;
        }
//        交换子节点数据
        if ($gathering['transaction_type'] == 2) {
            $do = $gathering['collect_this'];
            $gathering['collect_this'] = $gathering['expect_receipt_amount'];
            $gathering['actual_payment_amount'] = $gathering['expect_receipt_amount'] = $do;
        }

        // format gathering info
        $gathering = BaseCommon::fmt_gathering($gathering);
        // order data
        $order_data = B2bData::oneOrderBasic($gathering['ORDER_ID']);
        $gathering['fmt_order_data'] = $order_data;
        $gathering['todo_order_amount'] = $this->todoOrderAmount($receipt, $gathering);
        $gathering['todo_order_amount'] = ($gathering['todo_order_amount']) ? $gathering['todo_order_amount'] : 0;

        $update_currency = B2bModel::update_currency('N000590300', $gathering['po_time']);
        Logs($gathering['cost_delivery'], '$gathering[\'cost_delivery\']', 'b2b');
        $gathering['cost_delivery'] = $gathering['cost_delivery_usd'] = number_format($gathering['cost_delivery'] * $update_currency, 2);
        $gathering['recoverable_amount'] = $gathering['recoverable_amount_usd'] = number_format($gathering['recoverable_amount'] * $update_currency, 2);

        $initdata['node_status'] = $node_status;
        $initdata['is_end'] = $is_end;
        $initdata['number_th'] = $this->number_th;
        $initdata['node_is_workday'] = B2bModel::get_code('node_is_workday');
        $initdata['node_type'] = B2bModel::get_code('node_type');
        $initdata['node_date'] = B2bModel::get_code('node_date');
        $initdata['period'] = B2bModel::get_code('period');
        $initdata['invioce'] = B2bModel::get_code('invioce');
        $initdata['tax_point'] = $this->un_sign(B2bModel::get_code('tax_point'));
        $initdata['wfgs'] = B2bModel::get_code('我方公司');
        $initdata['currency'] = B2bModel::get_code('기준환율종류코드', true);
        $initdata['shipping'] = B2bModel::get_code_cd('N00153');
        $initdata['gathering'] = B2bModel::get_code_lang('gathering');
        $initdata['main_gathering_state'] = B2bModel::get_code_lang('main_gathering_state');

        $this->assign('initdata', B2bModel::set_json($initdata));
        $this->assign('deviation_cause', B2bModel::set_json(B2bModel::get_code('deviation_cause')));
        $this->assign('or_invoice_arr', B2bModel::set_json(array_column(B2bModel::get_code('or_invoice_arr'), 'CD_VAL')));
        $this->assign('url', '&id=' . $id);
        $gathering = (new B2bService())->checkOverdueDay([$gathering], 'expect_receipt_date')[0];

        return $gathering;
    }

    /**
     *
     */
    public function batchDataGet()
    {
        $params_arr = B2bModel::get_data('params', true);
        $sku = $params_arr['sku'];
        $ware_house = $params_arr['ware_house'];
        $sales_team_code = $params_arr['sales_team_code'];
        $purchase_team_code = $params_arr['purchasing_team'];
        $res['data'] = B2bModel::appointBatchGet($sku, $ware_house, $sales_team_code, $purchase_team_code);
        if (count($res['data'])) {
            $res['state'] = 20000;
            $res['msg'] = 'success';
        } else {
            Logs($res['data'], '$res[\'data\']', 'b2b_batchDataGet');
            $res['state'] = 40000;
            $res['msg'] = '无对应批次';
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $vw
     */
    private function unset_bill_data($vw)
    {
        unset($vw->BILL_LADING_CODE);
        unset($vw->DELIVERY_WAREHOUSE);
        unset($vw->DELIVERY_TIME);
        unset($vw->Estimated_arrival_DATE);
        unset($vw->LOGISTICS_CURRENCY);
        unset($vw->LOGISTICS_COSTS);
        unset($vw->REMARKS);
        unset($vw->SUBMIT_TIME);
        unset($vw->is_use_send_net);
    }

    /**
     * @param $ship_ids
     * @param $v
     * @param $data
     * @param $dataw
     * @param $w_ids
     *
     * @return array
     */
    private function goods_data_join($ship_ids, $v, $data, $dataw, $w_ids)
    {
        $data['SHIP_ID'] = $ship_ids[$v->deliveryWarehouse];
        $dataw['ship_id'] = $ship_ids[$v->deliveryWarehouse];
        $dataw['warehousing_id'] = $w_ids[$v->deliveryWarehouse];
        $dataw['warehouse_sku'] = $data['SHIPPING_SKU'] = $v->skuId;
        $dataw['sku_show'] = $data['sku_show'] = $v->skuId;

        $data['SHIPPED_NUM'] = $v->SHIPPED_NUM;
        $dataw['TOBE_WAREHOUSING_NUM'] = $data['DELIVERED_NUM'] = $v->DELIVERED_NUM;
        $data['power'] = 0;

        $dataw['DELIVERY_WAREHOUSE'] = $data['DELIVERY_WAREHOUSE'] = $v->deliveryWarehouse;
        $dataw['REMARKS'] = $data['REMARKS'] = $v->REMARKS;
        $dataw['goods_id'] = $data['goods_id'] = $v->GOODS_ID;

        return array($data, $dataw);
    }

    /**
     * @param $num_sum
     * @param $doship_data
     * @param $ship_state_arr
     * @param $order_id
     *
     * @return mixed
     */
    private function ship_state_upd($num_sum, $order_id, $Model = null)
    {
        if ($num_sum['todo_sent_num'] != 0 && $num_sum['sent_num'] > 0) {
            $num_sum['shipping_status'] = 2; // 部分发货
            $upd_order_node_status = $this->upd_order_node($order_id, 1, 'order_state', $Model);

        }
        if ($num_sum['todo_sent_num'] == 0) {
            $num_sum['shipping_status'] = 3; // 已发货
            $upd_order_node_status = $this->upd_order_node($order_id, 2, 'order_state', $Model); // 发货完成
            $this->updateStartRemindingDateReceipt($order_id, $Model);
        }
        return $num_sum;
    }

    /**
     * @param $receipt
     * @param $gathering
     *
     * @return array
     */
    private function allMoneyGet($receipt, $gathering)
    {
        $orderData = $receipt->where('main_id = ' . $gathering['main_id'] . ' AND ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND (transaction_type IS NULL OR transaction_type != 1) AND receipt_operation_status = 1 AND unconfirmed_state = 1')
            ->field('actual_payment_amount as unit_price,actual_receipt_date as currency_time')
            ->select();
        $res = B2bModel::currencyUpd($orderData, $gathering['po_currency']);
        $array_sum = array_sum(array_column($res, 'rmb_unit_price'));

        $array_sum_po = $receipt->where('main_id = ' . $gathering['main_id'] . ' AND ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND (transaction_type IS NULL OR transaction_type != 1) AND receipt_operation_status = 1')->order('ID')->sum('actual_payment_amount');
        $array_sum_po = empty($array_sum_po) ? 0 : $array_sum_po;

        $order_all_money_data = $receipt->where('ORDER_ID = ' . $gathering['ORDER_ID'] . ' AND (transaction_type IS NULL OR transaction_type != 1) AND receipt_operation_status = 1 AND unconfirmed_state = 1')
            ->field('actual_payment_amount as unit_price,actual_receipt_date as currency_time')
            ->select();
        if ($order_all_money_data) {
            $all_order_money = B2bModel::currencyUpd($order_all_money_data, $gathering['po_currency']);
            $all_order_money = array_sum(array_values(array_column($all_order_money, 'rmb_unit_price')));
        } else {
            $all_order_money = (int)null;
        }
        Logs($array_sum, 'array_sum', 'b2b');
        Logs($all_order_money, 'all_order_money', 'b2b');
        return [$all_order_money, $array_sum, $array_sum_po];
    }

    /**
     * @param $gathering
     *
     * @return mixed
     */
    private function all_diff($gathering)
    {
        return number_format(($gathering['expect_receipt_amount'] - $gathering['received_this']) * B2bModel::update_currency($gathering['po_currency'], $gathering['po_time']), 2);
    }

    /**
     * @param $get_main_id
     * @param $save
     * @param $post
     * @param $h8
     *
     * @return array
     */
    private function saveData($get_main_id, $save, $post, $h8)
    {
        $save['main_id'] = $get_main_id;
        $save['actual_payment_amount'] = $post['actual_payment_amount'];
        $actual_receipt_date = gmdate('Y-m-d H:i:s', strtotime($post['actual_receipt_date']) + $h8);
        if (strtotime($actual_receipt_date) > strtotime('today')) {
            throw new Exception(L('日期超过当天'));
        }
        if ($post['actual_receipt_date'] == '-' || empty($post['actual_receipt_date'])) {
            $actual_receipt_date = date('Y-m-d H:i:s');
        }
        $save['actual_receipt_date'] = $actual_receipt_date;
        $save['DEVIATION_REASON'] = $post['DEVIATION_REASON'];
        $save['invoice_status'] = $post['invoice_status'];
        $save['company_our'] = $post['company_our'];
        $save['receipt_serial_number'] = $post['receipt_serial_number'];
        $save['remarks'] = $post['remarks'];
        $save['create_time'] = date('Y-m-d H:i:s');
        $gathering_arr = $this->get_code_key('gathering');
        $save['receipt_operation_status'] = $gathering_arr['待收款'];   // 实际为收款code映射为 +1
        $save['operator_id'] = empty(session('m_loginname')) ? 'admin_null' : session('m_loginname');
        $expect_receipt_amount = $post['expect_receipt_amount'] - $post['actual_payment_amount'];
        return array($save, $actual_receipt_date, $post, $expect_receipt_amount);
    }

    /**
     * @param $receipt
     * @param $gathering
     *
     * @return mixed
     */
    private function todoOrderAmount($receipt, $gathering)
    {
        if (!$gathering['main_id']) return 0;
        return $receipt->where('(ORDER_ID = ' . $gathering['main_id'] . ' OR main_id = ' . $gathering['main_id'] . ') AND unconfirmed_state = 0  AND receipt_operation_status = 1')->getField('actual_payment_amount');
    }

    /**
     * @param      $main_id
     * @param      $model
     * @param null $order_id
     */
    public function orderConfirmedStateUpd($main_id, $model, $order_id = null)
    {
        $model = ($model) ? $model : M();
        $gathering_list = B2bModel::gathering_list($main_id, $model);
        $res_arr = array_column($gathering_list, 'unconfirmed_state');
        if (array_sum($res_arr) != count($res_arr)) {
            $save['main_unconfirmed_state'] = 1;
        } else {
            $save['main_unconfirmed_state'] = 0;
        }
        $model->table('tb_b2b_receipt')->where("ID = $main_id")->save($save);
    }

    /**
     * @param $save_wl
     * @param $save_profit
     * @param $order_id
     */
    private function countSkuUpd($save_wl, $save_profit, $order_id)
    {
        $model = M();
        //  更新统计表
        $save_profit['cost_delivery'] = array('exp', 'cost_delivery + ' . $save_wl['power_all']);
        $save_profit['delivery_currency'] = 'N000590100';
        $profit_upd = $model->table('tb_b2b_profit')->where('ORDER_ID = ' . $order_id)->save($save_profit);
    }

    /**
     * @param $poData_get
     * @param $poData
     * @param $receiptData
     *
     * @return array
     */
    private function joinPoDataCreate($poData_get, $poData, $receiptData, $Models)
    {
        $receiptData['client_id'] = $poData['CLIENT_NAME'] = $poData_get->clientName;
        /*if ($poData_get->clientNameEn[0]) {
            $poData['CLIENT_NAME_EN'] = $poData_get->clientNameEn[0];
        } elseif ($poData_get->clientNameEn) {
            $poData['CLIENT_NAME_EN'] = $poData_get->clientNameEn;
        } else {
            $poData['CLIENT_NAME_EN'] = TbCrmSpSupplierModel::clientNameToEn($poData_get->clientName);
        }*/
        TbCrmSpSupplierModel::clientNameToEn($poData_get->clientName);
        $poData['Business_License_No'] = $poData_get->busLice;
        $poData['other_income'] = $this->unking($poData_get->otherIncome);
        $poData['contract'] = $poData_get->contract;
        $poData['our_company'] = $poData_get->ourCompany;
        $poData['our_company_cd'] = $poData_get->ourCompanyCd;
        $receiptData['sales_team_id'] = $poData['SALES_TEAM'] = $poData_get->SALES_TEAM;
        $poData['PO_USER'] = $poData_get->lastname;
        $poData['delivery_time'] = $poData_get->delivery_time;
        $poData['rebate_rate'] = $poData_get->rebate_rate;
        $poData['rebate_amount'] = $poData_get->rebate_amount;
        //预估物流成本(logistics_estimat，insight计算使用所需，需求：11408 物流成本和返利金新增字段区分
        $poData['logistics_estimat_cost'] = $poData_get->logistics_estimat_cost;
        return array($poData, $receiptData);
    }

    /**
     * @param $poData_get
     * @param $poData
     * @param $receiptData
     *
     * @return array
     */
    private function joinPoDataTwo($poData_get, $poData, $receiptData)
    {
        $receiptData['estimated_amount'] = $poData['po_amount'] = $this->unking($poData_get->poAmount);
        $poData['PO_FILFE_PATH'] = $poData_get->IMAGEFILENAME;
        $poData['po_erp_path'] = $poData_get->po_erp_path;
//            $poData['po_currency'] = B2bModel::currency_po_to_erp($poData_get->BZ);
        $poData['po_currency'] = $poData_get->BZ;
        $poData['po_time'] = $poData_get->poTime;

        $receiptData['invoice_type'] = $poData['INVOICE_CODE'] = $poData_get->invioce;
        $receiptData['tax_point'] = $poData['TAX_POINT'] = $poData_get->tax_point;
        $poData['DELIVERY_METHOD'] = $poData_get->shipping;

        $poData['scm_send_type'] = $poData_get->scm_send_type;
        $poData['scm_order_meta'] = $poData_get->scm_order_meta;
        $poData['scm_order_note'] = $poData_get->scm_order_note;

        $receiptData['payment_account_type'] = $poData['BILLING_CYCLE_STATE'] = $poData_get->cycleNum;
        $poData['PAYMENT_NODE'] = json_encode($poData_get->poPaymentNode);

        $poData['TARGET_PORT']['targetCity'] = $poData_get->detailAdd;
        $poData['TARGET_PORT']['country'] = $poData_get->country;
        $poData['business_type'] = $poData_get->business_type;
        $poData['business_direction'] = $poData_get->business_direction;
        $poData['TARGET_PORT']['city'] = $poData_get->city;
        $poData['TARGET_PORT']['province'] = $poData_get->province;
        $poData['TARGET_PORT']['stareet'] = $poData_get->province;
        $poData['TARGET_PORT'] = json_encode($poData['TARGET_PORT']);

        $receiptData['sales_team_id'] = $poData['SALES_TEAM'] = $poData_get->saleTeam;

        $poData['remarks'] = $poData_get->remarks;
        $poData['tax_rebate_income'] = $this->unking($poData_get->tax_rebate_income);

        //           商品币种  预估商品成本 。物流币种。预估物流成本
//            $poData['backend_estimat'] = $this->get_backend_estimat($post->skuData, $poData_get->poTime);
        $poData['backend_currency'] = $poData_get->backend_currency;
        $poData['backend_estimat'] = $this->unking($poData_get->backend_estimat);
        $poData['logistics_currency'] = $poData_get->logistics_currency;
        $poData['logistics_estimat'] = $this->unking($poData_get->logistics_estimat);
        $poData['drawback_estimate'] = $this->unking($poData_get->drawback_estimate);
        return array($poData, $receiptData);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function scmSendOut(array $data = [])
    {
        try {
            if (empty($data)) {
                if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
                    $data = json_decode(JsonDataModel::scmSendOut(), true);
                } else {
                    throw new Exception(L('数据缺失'), 400);
                }
            }
            LogsModel::initConfig('scm');
            Logs(json_encode($data), 'scmSendOut');
//            $this->sendOutDataCheck($data);
            $lock_key = 'scmSendOut_' . $data['order_info']['po_id'];
            $time = 3 + (int)count($data['goods']);
            if ($time > 60) {
                $time = 60;
            }
            $rClineVal = RedisModel::lock($lock_key, $time);
            if ($rClineVal) {
                $this->Model = new Model();
                $this->Model->startTrans();
                list($order_id, $user, $doship_id, $order_batch_id) = $this->orderInfoInit($data);
                list($ship_info, $vw) = $this->shipListAdd($data, $order_id, $user, $doship_id, $order_batch_id);
                $ship_info = $this->warehouseListAdd($ship_info, $vw);
                list($upd_sum, $goods_sku_arr) = $this->b2bGoodsDeduction($data, $order_id);
                $this->doshipUpdate($order_id);
                list($goods_data, $goods_dataw) = $this->goodsDataJoinScm($data, $ship_info, $goods_sku_arr, $order_id);
                $this->allGoodsAdd($goods_data, $goods_dataw);
                // $goods_data = B2bModel::power_upd($goods_data, $order_batch_id, $this->Model);
                $this->sync_ship_power_all($goods_data, $order_id, true);
                $this->updateOrderReceivableAccount($order_id);
                $res['msg'] = 'success';
                $res['code'] = 200;
                $this->Model->commit();
                B2bModel::addLog($order_id, 200, '发货');
                RedisModel::unlock($data['order_info']['po_id']);
            } else {
                throw  new Exception($data['order_info']['po_id'] . L('订单锁定中'));
            }
        } catch (\Exception $exception) {
            if ($this->Model) $this->Model->rollback();
            $res['body'] = $this->error_message;
            $res['data'] = $data;
            $res['msg'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
            $res['data'] = $data;
            Logs($res, 'error_sendout_res');
            @SentinelModel::addAbnormal('SCM 请求发货 B2B 订单', $data['order_info']['po_id'] . '异常', $res, 'b2b_notice');
        }
        return $res;
    }

    /**
     * @param $data_res
     * @param $ship_info
     * @param $goods_sku_arr
     * @param $order_id
     *
     * @return array
     * @throws Exception
     */
    public function goodsDataJoinScm($data_res, $ship_info, $goods_sku_arr, $order_id)
    {
        $po_time = $this->Model->table('tb_b2b_info')->where("ORDER_ID = '$order_id'")->getField('po_time');
        foreach ($data_res['goods'] as $v) {
            $dataw['ship_id'] = $data['SHIP_ID'] = $ship_info['ship_id'];
            $dataw['warehousing_id'] = $ship_info['warehousing_id'];
            $dataw['sku_show'] = $data['sku_show'] = $dataw['warehouse_sku'] = $data['SHIPPING_SKU'] = $v['sku_id'];
            $data['SHIPPED_NUM'] = $goods_sku_arr[$v['sku_id']]['SHIPPED_NUM'];//弃用
            $dataw['DELIVERY_WAREHOUSE'] = $data['DELIVERY_WAREHOUSE'] = 'N000680800';
            $dataw['TOBE_WAREHOUSING_NUM'] = $data['DELIVERED_NUM'] = $v['delivered_num'];
            $dataw['REMARKS'] = $data['REMARKS'] = $data_res['logictics']['remarks'];
            $dataw['goods_id'] = $data['goods_id'] = $goods_sku_arr[$v['sku_id']][0]['ID'];
            $rate = B2bModel::update_currency($v['sku_currency'], $po_time, 'CNY');
            if (!$rate) {
                @SentinelModel::addAbnormal('B2B虚拟仓发货成本汇率','获取异常', [$po_time, $rate, $v], 'b2b_notice');
            }
            $exchange_rate = $v['sku_price'] * $rate;

            //20201230注释如下内容，因为采购单价为0是正常的，b2b成本为0也是正常，所以取消如下限制
//            if (empty($exchange_rate) || $exchange_rate <= 0) {
//                throw new Exception(L('成本异常'), 50003);
//            } else {
//                $data['power'] = round(($exchange_rate * $data['DELIVERED_NUM']), 4);
//            }
            $data['power'] = round(($exchange_rate * $data['DELIVERED_NUM']), 4);
            $goods_data[] = $data;
            $goods_dataw[] = $dataw;
            unset($data);
            unset($dataw);
        }
        return array($goods_data, $goods_dataw);
    }

    /**
     * @param $po_id
     *
     * @return mixed
     * @throws Exception
     */
    public function po2OrderId($po_id)
    {
        $po2OrderId = B2bModel::po2OrderId($po_id, $this->Model);
        if (empty($po2OrderId)) {
            throw new Exception(L('order is null'), 401);
        }
        return $po2OrderId;
    }

    /**
     * @param $post
     * @param $doship_id
     * @param $order_id
     * @param $order_batch_id
     * @param $goods_batch_json
     * @param $Model
     * @param $ship_id
     * @param $ship_id_arr
     *
     * @return array
     */
    private function warehouseListCreate($post, $doship_id, $order_id, $order_batch_id, $goods_batch_json, $Model)
    {
        foreach ((array)$post->ships as $v) {
            if ($v->BILL_LADING_CODE || ($v->DELIVERY_TIME && $v->Estimated_arrival_DATE)) {
                $v->DOSHIP_ID = (int)$doship_id;
                $v->AUTHOR = session('m_loginname');
                $v->order_id = $order_id;
                $v->order_batch_id = $order_batch_id;
                $v->goods_batch_json = json_encode($goods_batch_json, JSON_UNESCAPED_UNICODE);
                $v->SUBMIT_TIME = date('Y-m-d H:i:s');
                $v->LOGISTICS_COSTS = ExchangeRateModel::conversion($v->LOGISTICS_CURRENCY, 'N000590100', $v->SUBMIT_TIME) * $v->LOGISTICS_COSTS;
                $v->LOGISTICS_CURRENCY = 'N000590100';
                unset($v->GUDS_ID);
                trace($v, '$v');
                $ship_id['ship_id'] = $Model->table('tb_b2b_ship_list')->add((array)$v);
                $ships_map[$v->warehouse] = $v->is_use_send_net ? $v->is_use_send_net : 0;
                unset($v->order_batch_id);
                unset($v->goods_batch_json);
                $vw = $v;
                $this->unset_bill_data($vw);
                $vw->SHIP_LIST_ID = $ship_id['ship_id'];
                $ship_id['w_id'] = $Model->table('tb_b2b_warehouse_list')->add((array)$vw);
                if (!$ship_id['w_id']) {
                    $Model->rollback();
                    $msg = '生成入库单异常';
                    B2bModel::addLog($order_id, 0, $msg);
                    $return_goto = 'ajaxretrun';
                }
                $ship_id['DELIVERY_WAREHOUSE'] = $v->warehouse;
                $ship_id_arr[] = $ship_id;
                B2bModel::addLog($order_id, 1, '进行发货' . $order_batch_id);
            }
        }
        return [$ship_id_arr, $return_goto, $ships_map];
    }

    /**
     * 注意有两份改动
     *
     * @param $goods_batch_arr
     * @param $ship_ids
     * @param $w_ids
     * @param $upd_sum
     * @param $Model
     * @param $order_id
     *
     * @return array
     */
    private function warehouseGoodsCreate($goods_batch_arr, $ship_ids, $w_ids, $upd_sum, $Model, $order_id)
    {
        $return_goto = '';
        $goods_dataw = [];
        $goods_data = [];
        $where['ORDER_ID'] = $order_id;
        $goods_tobe_num = $this->Model->table('tb_b2b_goods')
            ->field('ID,SKU_ID,TOBE_DELIVERED_NUM,SHIPPED_NUM')
            ->where($where)
            ->select();
        foreach ($goods_tobe_num as $tobe_num_key => $tobe_num_val) {
            $goods_delivered_num_key_val[$tobe_num_val['SKU_ID']][$tobe_num_val['ID']] = $tobe_num_val['TOBE_DELIVERED_NUM'];
            $goods_shipped_num_key_val[$tobe_num_val['SKU_ID']][$tobe_num_val['ID']] = $tobe_num_val['SHIPPED_NUM'];
        }
        foreach ($goods_batch_arr as $v) {
            if ($v->DELIVERED_NUM) {
                list($data, $dataw) = $this->goods_data_join($ship_ids, $v, $data = null, $dataw = null, $w_ids);
                $upd_sum += $data['DELIVERED_NUM'];
                $goods_data[] = $data;
                $goods_dataw[] = $dataw;
                if ($data['DELIVERED_NUM'] <= $goods_delivered_num_key_val[$v->skuId][$v->GOODS_ID]) {
                    $goods_num = $Model->table('tb_b2b_goods')
                        ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v->skuId . ' AND ID = ' . $v->GOODS_ID)
                        ->field('SHIPPED_NUM,TOBE_DELIVERED_NUM')
                        ->select();
                    $goods_num = $goods_num[0];
                    $num_sum_sku['SHIPPED_NUM'] = $goods_num['SHIPPED_NUM'] + $data['DELIVERED_NUM'];
                    $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_num['TOBE_DELIVERED_NUM'] - $data['DELIVERED_NUM'];

                    $sku_save = $Model->table('tb_b2b_goods')
                        ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v->skuId . ' AND ID = ' . $v->GOODS_ID)
                        ->save($num_sum_sku);
                    $goods_delivered_num_key_val[$v->skuId][$v->GOODS_ID] -= $data['DELIVERED_NUM'];
                    $goods_shipped_num_key_val[$v->skuId][$v->GOODS_ID] += $data['DELIVERED_NUM'];
                } else {
                    // 注意有两份改动
                    foreach ($goods_delivered_num_key_val[$v->skuId] as $goods_key => $goods_value) {
                        if ($data['DELIVERED_NUM'] && $data['DELIVERED_NUM'] > $goods_value && $goods_value != 0) {
                            $num_sum_sku['SHIPPED_NUM'] = $goods_shipped_num_key_val[$v->skuId][$goods_key] + $goods_value;
                            $num_sum_sku['TOBE_DELIVERED_NUM'] = 0;
                            $sku_save_goods[] = $Model->table('tb_b2b_goods')
                                ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v->skuId . ' AND ID = ' . $goods_key)
                                ->save($num_sum_sku);
                            $data['DELIVERED_NUM'] -= $goods_value;
                            unset($goods_delivered_num_key_val[$v->skuId][$goods_key]);
                            $goods_shipped_num_key_val[$v->skuId][$goods_key] += $goods_value;
                            Logs([$data['DELIVERED_NUM'], $num_sum_sku], 'num_sum_sku');
                        } elseif ($data['DELIVERED_NUM'] && $data['DELIVERED_NUM'] <= $goods_value) {
                            $num_sum_sku['SHIPPED_NUM'] = $goods_shipped_num_key_val[$v->skuId][$goods_key] + $data['DELIVERED_NUM'];
                            $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_value - $data['DELIVERED_NUM'];
                            $sku_save_goods[] = $Model->table('tb_b2b_goods')
                                ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v->skuId . ' AND ID = ' . $goods_key)
                                ->save($num_sum_sku);
                            $goods_delivered_num_key_val[$v->skuId][$goods_key] -= $data['DELIVERED_NUM'];
                            $goods_shipped_num_key_val[$v->skuId][$goods_key] += $data['DELIVERED_NUM'];
                            $data['DELIVERED_NUM'] = 0;
                            Logs([$data['DELIVERED_NUM'], $num_sum_sku], 'all_num_sum_sku');
                        }
                    }
                }
                if (!$sku_save && count($sku_save_goods) != array_sum($sku_save_goods)) {
                    $Model->rollback();
                    $msg = 'SKU error';
                    $return_goto = 'ajaxretrun';
                    throw new Exception(L('创建理货商品信息失败'));
                }
                unset($num_sum_sku);
            }
        }
        return array($upd_sum, $goods_data, $goods_dataw, $return_goto, $msg);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function sendOutDataCheck($data)
    {
        $rules = $this->sendOutRulesJoin($data);
        ValidatorModel::validate($rules, $data);
        unset($rules);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            throw new Exception(L('请求参数异常'), 40002);
        }
        if (array_sum(array_column($data['goods'], 'delivered_num')) != $data['logictics']['shipments_number']) {
            throw new Exception(L('shipments_number have error'), 400101);
        };

    }

    /**
     * @return array
     */
    private function sendOutRulesJoin($data)
    {
        $rules = [
            "order_info.po_id" => "required",
            "order_info.supplier_id" => "required",
            "order_info.introduce_team" => "required|string|size:10",
            "order_info.purchasing_team" => "required|string|size:10",
            "order_info.out_bill_id" => "required",

            "logictics.delivery_time" => "required",
            // "logictics.logistics_currency" => "required_unless:logictics.logistics_costs,0",
            "logictics.logistics_costs" => "required",
            "logictics.shipments_number" => "required",
        ];
        foreach ($data['goods'] as $key => $val) {
            $rules['goods.' . $key . '.sku_id'] = 'required|string|min:10|max:10';
            $rules['goods.' . $key . '.delivered_num'] = 'required';
            $rules['goods.' . $key . '.sku_price'] = 'required|numeric';
            $rules['goods.' . $key . '.sku_currency'] = 'required|string|min:10|max:10';
        }
        return $rules;
    }

    /**
     * @param $data
     * @param $order_id
     * @param $user
     * @param $doship_id
     * @param $order_batch_id
     *
     * @return array
     */
    private function shipListAdd($data, $order_id, $user, $doship_id, $order_batch_id)
    {
        $v['order_id'] = $order_id;
        $v['DOSHIP_ID'] = $doship_id;
        $v['DELIVERY_TIME'] = $data['logictics']['delivery_time'];
        $v['Estimated_arrival_DATE'] = $data['logictics']['estimated_arrival_date'];
        $v['BILL_LADING_CODE'] = $data['logictics']['bill_lading_code'];
        $v['SHIPMENTS_NUMBER'] = $data['logictics']['shipments_number'];
        $v['LOGISTICS_CURRENCY'] = $data['logictics']['logistics_currency'];
        $v['LOGISTICS_COSTS'] = $data['logictics']['logistics_costs'];
        $v['REMARKS'] = $data['logictics']['remarks'];
        $v['AUTHOR'] = $user;
        $v['order_batch_id'] = $order_batch_id;
        $v['SUBMIT_TIME'] = date('Y-m-d H:i:s', time());
        $v['warehouse'] = 'N000680800';
        $v['out_bill_id'] = $data['order_info']['out_bill_id'];
        $ship_info['ship_id'] = $this->Model->table('tb_b2b_ship_list')->add($v);
        $v = $this->shipList2WarehouseList($v);
        return [$ship_info, $v];
    }

    /**
     * @param $order_id
     *
     * @return mixed
     * @throws Exception
     */
    private function doshipIdGet($order_id)
    {
        $doship_id = $this->Model->table('tb_b2b_doship')->where('ORDER_ID = ' . $order_id)->getField('ID');
        if (empty($order_id)) {
            throw new Exception(L('doship id is null'), 40401);
        }
        return $doship_id;
    }

    /**
     * @param $ship_info
     * @param $vw
     *
     * @return mixed
     * @throws Exception
     */
    private function warehouseListAdd($ship_info, $vw)
    {
        $vw['SHIP_LIST_ID'] = $ship_info['ship_id'];
        $ship_info['warehousing_id'] = $this->Model->table('tb_b2b_warehouse_list')->add($vw);
        if (empty($ship_info['warehousing_id'])) {
            throw new Exception(L('创建理货单失败'), 402);
        }
        return $ship_info;
    }

    /**
     * @param $data
     * @param $order_id
     * @param int $upd_sum
     *
     * @return array
     * @throws Exception
     */
    private function b2bGoodsDeduction($data, $order_id, $upd_sum = 0)
    {
        Logs($data, __FUNCTION__);
        $where['ORDER_ID'] = $order_id;
        $where['TOBE_DELIVERED_NUM'] = array('gt', 0);
        $goods_num_arr = $this->Model->table('tb_b2b_goods')
            ->field('SKU_ID,ID,SHIPPED_NUM,TOBE_DELIVERED_NUM')
            ->where($where)
            ->order('TOBE_DELIVERED_NUM ASC')
            ->select();

        if (empty($goods_num_arr)) {
            Logs(['where' => $where], __FUNCTION__);
            throw new Exception(L('介绍，采购团队对应订单信息不存在'), 40403);
        }
        foreach ($goods_num_arr as $goods_value) {
            $goods_sku_arr[$goods_value['SKU_ID']][] = $goods_value;
        }
        foreach ($data['goods'] as $v) {
            if (array_sum(array_column($goods_sku_arr[$v['sku_id']], 'TOBE_DELIVERED_NUM')) < $v['delivered_num']) {
                throw new Exception($v['sku_id'] . L('待发数量不足'), 40301);
            }
            $goods_num = $goods_sku_arr[$v['sku_id']][0];
            if ($v['delivered_num'] <= $goods_num['TOBE_DELIVERED_NUM']) {
                $num_sum_sku['SHIPPED_NUM'] = $goods_num['SHIPPED_NUM'] + $v['delivered_num'];
                $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_num['TOBE_DELIVERED_NUM'] - $v['delivered_num'];
                $where['SKU_ID'] = $v['sku_id'];
                $where['ID'] = $goods_num['ID'];
                $sku_save = $this->Model->table('tb_b2b_goods')
                    ->where($where)
                    ->save($num_sum_sku);
                if (!$sku_save) {
                    Logs([
                        'sku_save' => $sku_save,
                        'where' => $where,
                        'num_sum_sku' => $num_sum_sku,
                    ], __FUNCTION__);
                    throw new Exception(L('商品更新错误'), 403);
                }
                unset($where);
                unset($num_sum_sku);
                $upd_sum += $v['delivered_num'];
                $goods_sku_arr[$v['sku_id']][0]['TOBE_DELIVERED_NUM'] -= $v['delivered_num'];
                $goods_sku_arr[$v['sku_id']][0]['SHIPPED_NUM'] += $v['delivered_num'];
            } else {
                // 注意有两份改动
                foreach ($goods_sku_arr[$v['sku_id']] as $goods_key => $goods_value) {
                    if ($goods_value['TOBE_DELIVERED_NUM'] == 0) continue;
                    if ($v['delivered_num'] && $v['delivered_num'] > $goods_value['TOBE_DELIVERED_NUM']) {
                        $num_sum_sku['SHIPPED_NUM'] = $goods_value['SHIPPED_NUM'] + $goods_value['TOBE_DELIVERED_NUM'];
                        $num_sum_sku['TOBE_DELIVERED_NUM'] = 0;
                        $sku_save_goods[] = $this->Model->table('tb_b2b_goods')
                            ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v['sku_id'] . ' AND ID = ' . $goods_value['ID'])
                            ->save($num_sum_sku);
                        $v['delivered_num'] -= $goods_value['TOBE_DELIVERED_NUM'];
                        $goods_sku_arr[$v['sku_id']][$goods_key]['TOBE_DELIVERED_NUM'] = 0;
                        $upd_sum += $goods_value['TOBE_DELIVERED_NUM'];
                        Logs(['delivered_num' => $v['delivered_num'], 'num_sum_sku' => $num_sum_sku], __FUNCTION__);
                    } elseif ($v['delivered_num'] && $v['delivered_num'] <= $goods_value['TOBE_DELIVERED_NUM']) {

                        $num_sum_sku['SHIPPED_NUM'] = $goods_value['SHIPPED_NUM'] + $v['delivered_num'];
                        $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_value['TOBE_DELIVERED_NUM'] - $v['delivered_num'];
                        $sku_save_goods[] = $this->Model->table('tb_b2b_goods')
                            ->where('ORDER_ID = ' . $order_id . ' AND SKU_ID = ' . $v['sku_id'] . ' AND ID = ' . $goods_value['ID'])
                            ->save($num_sum_sku);
                        $goods_sku_arr[$v['sku_id']][$goods_key]['SHIPPED_NUM'] += $v['delivered_num'];
                        $goods_sku_arr[$v['sku_id']][$goods_key]['TOBE_DELIVERED_NUM'] -= $v['delivered_num'];
                        $v['delivered_num'] = 0;
                        $upd_sum += $v['delivered_num'];
                        Logs(['delivered_num' => $v['delivered_num'], 'num_sum_sku' => $num_sum_sku], __FUNCTION__);
                    }
                }
            }
            if (!$sku_save && count($sku_save_goods) != array_sum($sku_save_goods)) {
                throw new Exception(L('更新SKU异常'));
            }

        }
        return [$upd_sum, $goods_sku_arr];

    }

    /**
     * @param $order_id
     * @param $upd_sum
     *
     * @throws Exception
     */
    private function doshipUpdate($order_id)
    {
        $doship_data = $this->Model->table('tb_b2b_doship')
            ->field('delivery_warehouse_code,sent_num,todo_sent_num')
            ->where('ORDER_ID = ' . $order_id)
            ->find();
        $goods = $this->Model->table('tb_b2b_goods')
            ->field('SUM(SHIPPED_NUM) AS sent_num,SUM(TOBE_DELIVERED_NUM) AS todo_sent_num')
            ->where('ORDER_ID = ' . $order_id)
            ->group('ORDER_ID')
            ->find();
        $num_sum['sent_num'] = $goods['sent_num'];
        $num_sum['todo_sent_num'] = $goods['todo_sent_num'];
        $num_sum['update_time'] = date("Y-m-d H:i:s");
        $num_sum = $this->ship_state_upd($num_sum, $order_id);
        if ($num_sum['todo_sent_num'] < 0) {
            throw new Exception(L('待发数据不足'), 405);
        }
        Logs(['num_sum' => $num_sum, 'doship_data' => $doship_data], __FUNCTION__);
        $doship_save = $this->Model->table('tb_b2b_doship')->where('ORDER_ID = ' . $order_id)->save($num_sum);
        if (empty($doship_save)) {
            throw new Exception(L('待发数据保存异常'), 406);
        }
    }

    /**
     * @param $goods_data
     * @param $goods_dataw
     *
     * @throws Exception
     */
    public function allGoodsAdd($goods_data, $goods_dataw)
    {
        $res = $this->Model->table('tb_b2b_ship_goods')->addAll($goods_data);
        $res_w = $this->Model->table('tb_b2b_warehousing_goods')->addAll($goods_dataw);
        if (!$res || !$res_w) {
            Logs($res, '$res', 'scm');
            Logs($res_w, '$res_w', 'scm');
            throw new Exception(L('出库单商品信息保存失败'), 409);
        }
    }

    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    private function orderInfoInit($data)
    {
        $order_id = $this->po2OrderId($data['order_info']['po_id']);
        $user = session('m_loginname');
        $doship_id = $this->doshipIdGet($order_id);
        $order_batch_id = B2bModel::order_batch_id_join($data['order_info']['po_id'], $doship_id);
        return array($order_id, $user, $doship_id, $order_batch_id);
    }

    /**
     * @param $v
     *
     * @return mixed
     */
    private function shipList2WarehouseList($v)
    {
        unset($v['DELIVERY_TIME']);
        unset($v['Estimated_arrival_DATE']);
        unset($v['BILL_LADING_CODE']);
        unset($v['LOGISTICS_CURRENCY']);
        unset($v['LOGISTICS_COSTS']);
        unset($v['REMARKS']);
        unset($v['order_batch_id']);
        unset($v['out_bill_id']);
        return $v;
    }

    /**
     * @param $data
     * @param $val
     * @param $price_key
     *
     * @return mixed
     * @throws Exception
     */
    private function percentDataCreate($data, $val, $price_key)
    {
        foreach ($val[$price_key] as $k => &$v) {
            $sku_data_val = $val;
            $sku_data_val['purchasing_team'] = $v['purchasing_team'];
            $sku_data_val['introduce_team'] = $v['introduce_team'];
            $sku_data_val['purchasing_currency'] = $v['purchasing_currency'];
            $sku_data_val['purchasing_price'] = $v['purchasing_price'];
            $sku_data_val['purchase_invoice_tax_rate'] = $v['purchase_invoice_tax_rate'];
            $sku_data_val['procurement_number'] = $v['procurement_number'];
            $sku_data_val['demand'] = $v['purchasing_num'];
            $sku_data_val['sku_drawback'] = $v['sku_drawback'];
            if ($v['batch_id']) {
                $sku_data_val['batch_id'] = $v['batch_id'];
                $sku_data_val['batch_code'] = $v['batch_code'];
            }
            if (empty($v['purchasing_team']) && !empty($v['batch_id'])) {
                $sku_data_val['percent_purchasing'] = 100;
                $sku_data_val['percent_introduce'] = 0;
                $sku_data_val['percent_sale'] = 0;
            } else {
                $data_temp['date'] = $data['poData']['poTime'];
                $data_temp['purchase_code'] = $v['purchasing_team'];
                $data_temp['intro_code'] = $v['introduce_team'];
                $data_temp['sales_code'] = $data['poData']['saleTeam'];
                if (empty($data_temp['intro_code'])) {
                    $data_temp['intro_code'] = 'N001301200';
                }
                if (empty($data_temp['purchase_code'])) {
                    $data_temp['purchase_code'] = 'N001292400';
                }
                $revenueSplitArr = ApiModel::revenueSplit($data_temp);
                if ($revenueSplitArr['code'] == 200) {
                    if (!empty($revenueSplitArr['data'])) {
                        $sku_data_val['percent_sale'] = trim($revenueSplitArr['data'][0]['split_rule']['team_1_value'], '%');
                        $sku_data_val['percent_purchasing'] = trim($revenueSplitArr['data'][0]['split_rule']['team_2_value'], '%');
                        $sku_data_val['percent_introduce'] = trim($revenueSplitArr['data'][0]['split_rule']['team_3_value'], '%');

                        if ($sku_data_val['percent_sale'] + $sku_data_val['percent_purchasing'] + $sku_data_val['percent_introduce'] <= 1) {
                            $sku_data_val['percent_sale'] = $sku_data_val['percent_sale'] * 100;
                            $sku_data_val['percent_purchasing'] = $sku_data_val['percent_purchasing'] * 100;
                            $sku_data_val['percent_introduce'] = $sku_data_val['percent_introduce'] * 100;
                        }
                    } else {
                        $sku_data_val['percent_purchasing'] = 0;
                        $sku_data_val['percent_introduce'] = 0;
                        $sku_data_val['percent_sale'] = 0;
                    }
                } else {
                    $this->error_message = [$revenueSplitArr, $data_temp];
                    throw new Exception(L('获取分成异常'), 5003);
                }
            }
            $data['skuDataNew'][] = $sku_data_val;
        }
        return $data;
    }

    /**
     *
     */
    public function updateSkuInfo()
    {
        $require_data = DataModel::getData(true);
        try {
            if (empty($require_data) && $_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
                $require_data = json_decode(JsonDataModel::updateSkuInfo(), true);
            }
            $this->updateSkuDataCheck($require_data);
            $Model = M();
            $Model->startTrans();
            $where_goods['ID'] = array('IN', array_column($require_data['data'], 'good_id'));
            $team_arr = $Model->table('tb_b2b_goods')
                ->field('ID,SKU_ID,ORDER_ID,purchasing_team,introduce_team')
                ->where($where_goods)
                ->select();
            if (empty($team_arr)) {
                throw new Exception(L('无对应数据'), 5003);
            }
            $err_msg = null;
            foreach ($team_arr as $value) {
                $data_temp['date'] = $require_data['info']['date'];
                $data_temp['sales_code'] = $require_data['info']['sales_team'];
                $data_temp['purchase_code'] = $value['purchasing_team'];
                $data_temp['intro_code'] = $value['introduce_team'];

                $revenueSplitArr = ApiModel::revenueSplit($data_temp);
                if ($revenueSplitArr['code'] == 200) {
                    if (empty($revenueSplitArr['data'])) {
                        $this->error_message = $revenueSplitArr;
                        throw new Exception(L('获取分成为空'), 40004);
                    }
                    $v['percent_sale'] = trim($revenueSplitArr['data'][0]['split_rule']['team_1_value'], '%');
                    $v['percent_purchasing'] = trim($revenueSplitArr['data'][0]['split_rule']['team_2_value'], '%');
                    $v['percent_introduce'] = trim($revenueSplitArr['data'][0]['split_rule']['team_3_value'], '%');
                    if ($v['percent_sale'] + $v['percent_purchasing'] + $v['percent_introduce'] <= 1) {
                        $v['percent_sale'] = $v['percent_sale'] * 100;
                        $v['percent_purchasing'] = $v['percent_purchasing'] * 100;
                        $v['percent_introduce'] = $v['percent_introduce'] * 100;
                    }
                    $where_id['ID'] = $value['ID'];
                    $upd_res = $Model->table('tb_b2b_goods')->where($where_id)->save($v);
                    if (!$upd_res) {
                        $err_msg .= $value['ID'] . ' ';
                    }
                    $v['SKU'] = $value['SKU_ID'];
                    $v['order_id'] = $value['ORDER_ID'];
                    $v['good_id'] = $value['ID'];
                    $response_body[] = $v;
                    unset($v);
                } else {
                    $this->error_message = [$revenueSplitArr, $data_temp];
                    throw new Exception(L('获取分成异常'), 5003);
                }
            }
            $Model->commit();
            $res['body'] = $response_body;
            $res['msg'] = 'success';
            if ($err_msg) {
                $res['msg'] = $err_msg . L('数据未改动');
            }
            $res['code'] = 200;
        } catch (Exception $exception) {
            if ($Model) $Model->rollback();
            $res['body'] = $this->error_message;
            $res['msg'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function updateSkuDataCheck($data)
    {
        $rules = $this->updateDataRulesJoin($data);
        ValidatorModel::validate($rules, $data);
        unset($rules);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            Logs($message, '$message');
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            throw new Exception(L('请求参数异常'), 40001);
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function updateDataRulesJoin($data)
    {
        $rules = array();
        foreach ($data['data'] as $key => $val) {
            $rules['data.' . $key . '.order_id'] = 'required|numeric';
            $rules['data.' . $key . '.good_id'] = 'required|numeric';
            $rules['data.' . $key . '.type'] = 'required|string';
        }
        $rules['info.sales_team'] = 'required|string|min:10|max:10';
        $rules['info.date'] = 'required';
        return $rules;
    }

    /**
     *
     */
    public function delPoOrderAllInfo()
    {
        $data = DataModel::getDataToArr();
        try {
            Logs($data, __FUNCTION__, __CLASS__);
            $Model = M();
            $Model->startTrans();
            if (!$data || !$data['po_id']) {
                throw new Exception('请求不存在');
            }
            $data['key_time'] = date('Y-m-d H:i:s');
            $data['del_user'] = session('m_loginname');
            $where_po['PO_ID'] = $data['po_id'];
            $order_id = $Model->table('tb_b2b_order')->where($where_po)->getField('ID');
            if (empty($order_id)) {
                throw new Exception('请求订单号不存在');
            }
            $this->checkReceiptClaim($order_id);
            $this->releaseOccupancy($data['po_id']);
            $where_id['ID'] = $where['ORDER_ID'] = $order_id;
            $save['doship'] = $Model->table('tb_b2b_doship')->where($where)->select();
            $save['goods'] = $Model->table('tb_b2b_goods')->where($where)->select();
            $save['info'] = $Model->table('tb_b2b_info')->where($where)->select();
            $save['order'] = $Model->table('tb_b2b_order')->where($where_id)->select();
            $save['profit'] = $Model->table('tb_b2b_profit')->where($where)->select();
            $save['receipt'] = $Model->table('tb_b2b_receipt')->where($where)->select();
            $save['ship_list'] = $Model->table('tb_b2b_ship_list')->where($where)->select();
            $save['receivable'] = $Model->table('tb_b2b_receivable')->where($where)->select();
            if ($save['ship_list']) {
                $where_ship['SHIP_ID'] = array('IN', array_column($save['ship_list'], 'ID'));
                $save['ship_goods'] = $Model->table('tb_b2b_ship_goods')->where($where_ship)->select();
            }
            $save['warehouse_list'] = $Model->table('tb_b2b_warehouse_list')->where($where)->select();
            if ($save['warehouse_list']) {
                $where_warehousing_goods['warehousing_id'] = array('IN', array_column($save['warehouse_list'], 'ID'));
                $save['warehousing_goods'] = $Model->table('tb_b2b_warehousing_goods')->where($where_warehousing_goods)->select();
            }
            $save['po_id'] = $save['info'][0]['PO_ID'];
            foreach ($save as $key => $value) {
                if ($value) {
                    $save[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
            $save['order_id'] = $data['order_id'];
            $save['del_user'] = $data['del_user'];
            $save['key_time'] = $data['key_time'];
            $save['create_time'] = date('Y-m-d H:i:s');
            $add_res = $Model->table('tb_b2b_del')->add($save);
            if (!$add_res) {
                throw new Exception('保存删除数据失败');
            }
            // del
            $del_res['tb_b2b_doship'] = $Model->table('tb_b2b_doship')->where($where)->delete();
            $del_res['tb_b2b_goods'] = $Model->table('tb_b2b_goods')->where($where)->delete();
            $del_res['tb_b2b_info'] = $Model->table('tb_b2b_info')->where($where)->delete();
            $del_res['tb_b2b_order'] = $Model->table('tb_b2b_order')->where($where_id)->delete();
            $del_res['tb_b2b_profit'] = $Model->table('tb_b2b_profit')->where($where)->delete();
            $del_res['tb_b2b_receipt'] = $Model->table('tb_b2b_receipt')->where($where)->delete();
            $del_res['tb_b2b_receivable'] = $Model->table('tb_b2b_receivable')->where($where)->delete();
            if (!empty($save['ship_list'])) {
                $del_res['tb_b2b_ship_list'] = $Model->table('tb_b2b_ship_list')->where($where)->delete();
                $del_res['tb_b2b_ship_goods'] = $Model->table('tb_b2b_ship_goods')->where($where_ship)->delete();
            }
            if (!empty($save['tb_b2b_warehouse_list'])) {
                $del_res['tb_b2b_warehouse_list'] = $Model->table('tb_b2b_warehouse_list')->where($where)->delete();
                $del_res['tb_b2b_warehousing_goods'] = $Model->table('tb_b2b_warehousing_goods')->where($where_warehousing_goods)->delete();
            }
            foreach ($del_res as $k => $val) {
                if (!$val) {
                    throw new Exception('删除失败' . $k);
                }
            }
            $Model->commit();
            $res = DataModel::$success_return;
            $res['msg'] = '删除订单成功';
        } catch (Exception $exception) {
            $Model->rollback();
            $res = DataModel::$error_return;
            $res['msg'] = $exception->getMessage();
            if ($exception->getCode()) $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $order_id
     *
     * @throws Exception
     */
    private function checkReceiptClaim($order_id)
    {
        if (TbFinClaim::where('order_id', $order_id)
            ->where('order_type', 'N001950200')
            ->count()) {
            throw new Exception(L('已存在认领单'));
        }
    }

    /**
     * @param $post
     *
     * @return array
     */
    private function joinShowListWhere($post)
    {
        $where = null;
        if (!empty($post['goods_title_info'])) {
            switch ($post['search_type']) {
                case 'SKU_ID':
                    $post['SKU_ID'] = trim($post['goods_title_info']);
                    break;
                case 'bar_code':
                    $SKU_ID = SkuModel::upcTosku(trim($post['goods_title_info']));
                    $post['SKU_ID'] = $SKU_ID;
                    break;
                case 'goods_title':
                    // $post['goods_title'] = trim($post['goods_title_info']);
                    $sku_arr = SkuModel::titleToSku($post['goods_title_info']);
                    if ($sku_arr) {
                        $Models = new Model();
                        $where_sku['SKU_ID'] = array('in', $sku_arr);
                        $subQuery = $Models->table('tb_b2b_goods')
                            ->field('ORDER_ID')
                            ->where($where_sku)
                            ->select(false);
                        $where['_string'] = " tb_b2b_order.ID in " . $subQuery . " ";
                        unset($post['goods_title_info']);
                    }
                    break;
            }
        }
        if (!empty($post['yq'])) {
            $post['order_overdue_statue'] = trim($post['yq']);
        }
        $where = B2bModel::joinwhere($post, 'b2blist', $where);
        $Order = M('b2b_order', 'tb_');
        import('ORG.Util.Page');
//        where is why ?
        if ($post['yq'] == 0 && isset($post['yq'])) {
            $where['tb_b2b_order.order_overdue_statue'] = trim($post['yq']);
        } else {
            if (empty($post['yq'])) unset($where['tb_b2b_order.order_overdue_statue']);
        }

        if ($where['tb_b2b_warehouse_list.warehouse_state']) {
            $warehouse_states = [];
            foreach ($where['tb_b2b_warehouse_list.warehouse_state'][1] as $value) {
                $warehouse_states[] = $value - 1;
            }
            $where['tb_b2b_order.warehousing_state'] = array('IN', $warehouse_states);
            unset($where['tb_b2b_warehouse_list.warehouse_state']);
        }
//       mapping order_state to ship_state
        if ($where['tb_b2b_ship_list.order_fh']) {
            foreach ($where['tb_b2b_ship_list.order_fh'][1] as $value) {
                if ($value - 1 >= 0) {
                    $order_state[] = $value - 1;
                }
            }
            $where['tb_b2b_order.order_state'] = array('IN', $order_state);
            unset($where['tb_b2b_ship_list.order_fh']);
        }
        // 应收。。。
        if ($where['tb_b2b_receivable.order_sk']) {
            $where['tb_b2b_receivable.receivable_status'] = $where['tb_b2b_receivable.order_sk'];
            unset($where['tb_b2b_receivable.order_sk']);
        }
        if ($where['tb_b2b_receipt.order_ts']) {
            $where['tb_b2b_order.tax_rebate_state'] = array('EQ', $where['tb_b2b_receipt.order_ts'][1] - 1);
            unset($where['tb_b2b_receipt.order_ts']);
        }
        // 提交状态
        if (!empty($post['submit_state'])) {
            $where['submit_state'] = $post['submit_state'];
        }

        if (!empty($post['procurement_number'])) {
            $where['tb_wms_bill.procurement_number'] = $post['procurement_number'];
        }
        if (!empty($post['procurement_po'])) {
            $where['tb_pur_order_detail.online_purchase_order_number'] = $post['procurement_po'];
        }
        if (!empty($post['PO_ID'])) {
            $po_ids = strReplaceComma($post['PO_ID']);
            //$po_ids = array_slice($po_ids, 0, 10);
        }

        /*switch ($post['orderId']) {
            case 'THR_PO_ID':
                $this->action['orderId'] = 'THR_PO_ID';
                if (isset($po_ids)) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
                } elseif ($post['PO_ID']) {
                    $where['tb_b2b_info.THR_PO_ID'] = array('LIKE', B2bModel::check_v($post['PO_ID'], 'LIKE'));
                }
                unset($where['tb_b2b_order.PO_ID']);
                break;
            case 'PO_ID':
                if (isset($po_ids)) {
                    unset($where['tb_b2b_order.PO_ID']);
                    $where['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
                }
                $this->action['orderId'] = 'PO_ID';
                break;
        }*/
        if (isset($po_ids)) {
            $condition['tb_b2b_info.THR_PO_ID'] = array('IN', $po_ids);
            $condition['tb_b2b_info.PO_ID'] = array('IN', $po_ids);
            $condition['tb_b2b_order.PO_ID'] = array('IN', $po_ids);
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
            unset($where['tb_b2b_order.PO_ID']);
        } else if ($post['PO_ID']) {
            unset($where['tb_b2b_order.PO_ID']);
            $condition['tb_b2b_info.THR_PO_ID'] = B2bModel::check_v($post['PO_ID'], '');
            $condition['tb_b2b_info.PO_ID'] = B2bModel::check_v($post['PO_ID'], '');
            $condition['tb_b2b_order.PO_ID'] = B2bModel::check_v($post['PO_ID'], '');
            $condition['_logic'] = 'or';
            $where['_complex'] = $condition;
        }

        if ($post['orderId']) {
            $this->action['orderId'] = $post['orderId'];
        }
        // filter again
        $where = B2bSearch::arrange_again_po($where, $post);
        return array($where, $Order);
    }


    /**
     *
     */
    public function patch_data_excel()
    {
        $order_id_arr = $this->getOrderIdData();
        $data_arr = [];
        if ($order_id_arr) {
            $Model = M();
            $where_goods_id['tb_b2b_goods.ORDER_ID'] = $where_order_id['tb_b2b_doship.ORDER_ID'] = array('IN', $order_id_arr);
            $field = 'tb_b2b_ship_list.ID AS ship_list_id,tb_b2b_doship.ORDER_ID,tb_b2b_doship.PO_ID,tb_b2b_doship.order_num,tb_b2b_doship.todo_sent_num,tb_b2b_doship.sent_num,sum_cost,"CNY" AS currency,tb_b2b_info.our_company,tb_b2b_info.THR_PO_ID,tb_b2b_doship.update_time';
            $data_arr = $Model->table('tb_b2b_doship')
                ->field($field)
                ->join('left join (select ID,sum(power_all) as sum_cost,order_id from tb_b2b_ship_list group by order_id order by ID desc) tb_b2b_ship_list on tb_b2b_ship_list.order_id = tb_b2b_doship.ORDER_ID')
                ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID')
                ->where($where_order_id)
                ->group('tb_b2b_doship.ORDER_ID')
                ->select();
            $goods_arr = $Model->table('tb_b2b_goods,tb_ms_cmn_cd')
                ->field('tb_b2b_goods.ORDER_ID,tb_ms_cmn_cd.CD_VAL as po_currency,SUM(tb_b2b_goods.price_goods*tb_b2b_goods.SHIPPED_NUM) as po_sale')
                ->where($where_goods_id)
                ->where('tb_ms_cmn_cd.CD = tb_b2b_goods.currency')
                ->group('tb_b2b_goods.ORDER_ID')
                ->select();
            $goods_key = array_flip(array_column($goods_arr, 'ORDER_ID'));
            foreach ($data_arr as $key => $value) {
                $data_arr[$key]['po_sale'] = $goods_arr[$goods_key[$value['ORDER_ID']]]['po_sale'];
                $data_arr[$key]['po_currency'] = $goods_arr[$goods_key[$value['ORDER_ID']]]['po_currency'];
            }

            $all_excluding_taxs = $this->assemblyTaxMapping(
                $this->assemblyShipExcludingTax([$order_id_arr], true, true),
                'order_id'
            );
            $data_arr = $this->joinShipExcludingData($data_arr, $all_excluding_taxs, 'ORDER_ID');
        }
        list($xlsData, $xlsName, $xlsCell) = $this->joinExcelData($data_arr);
        if (!class_exists('ExcelModel')) {
            include_once APP_PATH . 'Lib/Model/Oms/ExcelModel.class.php';
        }
        $B2bService = new B2bService();
        $B2bService->outputExcel($xlsName, $xlsCell, $xlsData);
    }

    /**
     * @param $data_arr
     *
     * @return array
     */
    private function joinExcelData($data_arr)
    {
        $xlsData = $data_arr;
        $xlsName = "B2B订单发货列表导出";
        $xlsCell = array(
            array('PO_ID', 'B2B订单号'),
            array('THR_PO_ID', 'PO单号'),
            array('our_company', '我方公司'),
            array('order_num', '订单商品数'),
            array('sent_num', '订单已发数量'),
            array('todo_sent_num', '剩余待发数量'),
            array('sum_shipping_cost', '发货成本（含增值税）'),
            array('sum_shipping_cost_excludeing_tax', '发货成本（不含增值税）'),
//            array('sum_cost', '已发货成本（含税）'),
            array('currency', '成本币种'),
            array('sum_shipping_revenue', '发货收入（含增值税）'),
            array('sum_shipping_revenue_excludeing_tax', '发货收入（不含增值税）'),
//            array('po_sale', '已发货对应销售金额（含税）'),
            array('po_currency', '销售币种'),
            array('update_time', '最近发货时间'),
        );
        return array($xlsData, $xlsName, $xlsCell);
    }

    /**
     * @return array
     */
    private function getOrderIdData()
    {
        $Doship = M('doship', 'tb_b2b_');
        $getdata = $this->_param();
        $this->action['shipping_status'] = empty($getdata['shipping_status']) ? 0 : $getdata['shipping_status'];
        $this->action['CLIENT_NAME'] = empty($getdata['CLIENT_NAME']) ? '' : $getdata['CLIENT_NAME'];

        $this->action['delivery_warehouse_code'] = empty($getdata['delivery_warehouse_code']) ? '' : $getdata['delivery_warehouse_code'];
        $this->action['sales_team_code'] = empty($getdata['sales_team_code']) ? '' : $getdata['sales_team_code'];
        $where = B2bModel::joinwhere($getdata, 'do_ship_list');
        switch ($getdata['orderId']) {
            case 'THR_PO_ID':
                $this->action['orderId'] = empty($getdata['PO_ID']) ? '' : $getdata['PO_ID'];
                if ($getdata['PO_ID']) {
                    $where['THR_PO_ID'] = array('LIKE', B2bModel::check_v($getdata['PO_ID'], 'LIKE'));
                }
                unset($where['PO_ID']);
                unset($where['tb_b2b_doship.PO_ID']);
                break;
            case 'PO_ID':
                $this->action['orderId'] = empty($getdata['PO_ID']) ? '' : $getdata['PO_ID'];
                break;
        }

        $doship_list = $Doship
            ->field('tb_b2b_doship.ORDER_ID')
            ->where($where)
            ->join('left join tb_b2b_info on tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID ')
            ->order('tb_b2b_doship.ID DESC')
            ->select();
        $order_id_arr = array_column($doship_list, 'ORDER_ID');
        return $order_id_arr;
    }

    /**
     * @param null $require_data_req
     *
     * @return array
     */
    public function signSendOut($require_data_req = null)
    {
        $this->Model = M();
        $this->Model->startTrans();
        try {
            if (empty($require_data_req)) {
                $require_data = DataModel::getData();
            } else {
                $require_data = $require_data_req;
            }
            $this->checkSkuOccupyRequire($require_data);
            $order_id = $require_data['order_id'];

            $this->checkOrderHas($order_id);
            $this->updateOrderStatus($order_id, $require_data_req);
            $this->setDoSendOutZero($order_id);
            $this->releaseOrderOccupy($order_id);
            $res = $this->return_success;
            $this->Model->commit();
            B2bModel::addLog($order_id, 200, '标记发货完结');
        } catch (Exception $exception) {
            $this->Model->rollback();
            $res = $this->return_error;
            $res['data'] = $this->error_message;
            $res['msg'] = $exception->getMessage();
            $res['file'] = $exception->getFile();
            $code = $exception->getCode();
            if ($code) {
                $res['code'] = $code;
            }
        }
        if (!empty($require_data_req)) {
            return $res;
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $order_id
     *
     * @throws Exception
     */
    public function checkOrderHas($order_id)
    {
        $res = $this->Model->table('tb_b2b_order')
            ->where('ID = ' . $order_id)
            ->count();
        if (!$res) {
            throw new Exception(L('订单异常'));
        }
    }


    /**
     * @param $order_id
     * @param null $require_data_req
     */
    public function updateOrderStatus($order_id, $require_data_req = null)
    {
        $find_doship = $this->Model->table('tb_b2b_doship')
            ->where("ORDER_ID = {$order_id} AND shipping_status = 3")
            ->find();
        if (empty($find_doship)) {
            $save_doship['shipping_status'] = 3;
            $update_doship = $this->Model->table('tb_b2b_doship')
                ->where('ORDER_ID = ' . $order_id)
                ->save($save_doship);
        } else {
            $update_doship = true;
        }
        $find_order = $this->Model->table('tb_b2b_order')
            ->where("ID = {$order_id} AND order_state = 2")
            ->find();
        if (empty($find_order)) {
            $save_order['order_state'] = 2;
            if (null === $require_data_req) {
                $save_order['make_send_by'] = DataModel::userNamePinyin();
                $save_order['make_send_at'] = date('Y-m-d H:i:s');
            }
            $update_order = $this->Model->table('tb_b2b_order')
                ->where('ID = ' . $order_id)
                ->save($save_order);
        } else {
            $update_order = true;
        }
        if (empty($update_doship) || empty($update_order)) {
            Logs([$update_doship, $update_order], 'updateOrderStatus');
//            throw new Exception(L('更改订单状态失败'));
        }
    }

    /**
     * @param $order_id
     *
     * @throws Exception
     */
    public function setDoSendOutZero($order_id)
    {
        if (empty($this->Model)) {
            $this->Model = M();
        }
        $save_doship['todo_sent_num'] = 0;
        $save_doship['update_time'] = date("Y-m-d H:i:s");
        $find_doship = $this->Model->table('tb_b2b_doship')
            ->where("ORDER_ID = {$order_id} AND todo_sent_num = 0")
            ->find();
        if (empty($find_doship)) {
            $update_doship = $this->Model->table('tb_b2b_doship')
                ->where('ORDER_ID = ' . $order_id)
                ->save($save_doship);
        } else {
            $update_doship = true;
        }

        $find_num = $this->Model->table('tb_b2b_goods')
            ->where("ORDER_ID = {$order_id} AND TOBE_DELIVERED_NUM != 0")
            ->find();
        if ($find_num) {
            $save_goods['TOBE_DELIVERED_NUM'] = 0;
            $update_goods = $this->Model->table('tb_b2b_goods')
                ->where('ORDER_ID = ' . $order_id)
                ->save($save_goods);
        } else {
            $update_goods = true;
        }
        if (empty($update_doship) || empty($update_goods)) {
            throw new Exception(L('置空待发货数量失败'));
        }
    }

    /**
     * @param $order_id
     *
     * @throws Exception
     */
    public function releaseOrderOccupy($order_id)
    {
        $require['order_id'] = $order_id;
        $occupy_data = $this->getSkuOccupy($require, 'all');
        if ($occupy_data['code'] == 200000) {
            $res = $this->Model->table('tb_b2b_order')
                ->field('PO_ID')
                ->where('ID = ' . $order_id)
                ->find();
            $releaseOccupancy = $this->releaseOccupancy($res['PO_ID']);
            $require_res = json_decode($releaseOccupancy, true);
            if ($require_res['code'] != 2000) {
                throw new Exception(L('订单占用取消失败'));
            }
        } else {
            Logs('订单占用查询失败', 'releaseOrderOccupy', 'signSendOut');
            // throw new Exception(L('订单占用查询失败'));
        }
    }


    /**
     * @param null $require_data_temp
     * @param string $take_type
     *
     * @return array
     */
    public function getSkuOccupy($require_data_temp = null, $take_type = 'spot')
    {
        try {
            if (empty($require_data_temp)) {
                $require_data = DataModel::getData();
            } else {
                $require_data = $require_data_temp;
            }
            $this->checkSkuOccupyRequire($require_data);
            $order_id = $require_data['order_id'];
            $this->Model = new Model();
            $where['tb_b2b_goods.order_id'] = $order_id;
            $table_string = "(( SELECT * FROM tb_b2b_goods where tb_b2b_goods.order_id = {$order_id}  GROUP BY tb_b2b_goods.SKU_ID) AS tb_b2b_goods,tb_b2b_order,tb_wms_batch_order,tb_ms_cmn_cd,tb_wms_batch)";
            $where_str = 'tb_b2b_order.ID = tb_b2b_goods.ORDER_ID 
                AND tb_wms_batch_order.use_type = 1 
                AND tb_wms_batch_order.batch_id = tb_wms_batch.id 
                AND tb_b2b_order.PO_ID =  tb_wms_batch_order.ORD_ID 
                AND tb_ms_cmn_cd.CD = tb_wms_batch_order.delivery_warehouse
                AND tb_b2b_goods.SKU_ID = tb_wms_batch_order.SKU_ID
                ';
            if ('spot' == $take_type) {
                $where_str .= ' AND tb_wms_batch.vir_type = \'N002440100\'';
            }
            $res_db = $this->Model->table($table_string)
                ->field('tb_b2b_goods.ID,tb_b2b_goods.batch_id,tb_wms_batch_order.SKU_ID,tb_wms_batch_order.SKU_ID AS skuId,tb_wms_batch_order.delivery_warehouse,tb_wms_batch_order.delivery_warehouse AS deliveryWarehouse,tb_wms_batch_order.sale_team_code AS saleTeamCode,tb_ms_cmn_cd.CD_VAL AS delivery_warehouse_name,sum(tb_wms_batch_order.occupy_num) AS occupy_num,concat(tb_wms_batch_order.SKU_ID,tb_wms_batch_order.delivery_warehouse) as sku_warehouse_key ')
                ->where($where)
                ->where($where_str, null, true)
                ->group('tb_wms_batch_order.SKU_ID,tb_wms_batch_order.delivery_warehouse')
                ->select();
            if (empty($require_data_temp)) {
                $LocationService = new LocationService();
                $LocationService->warehouse_key = 'delivery_warehouse';
                $LocationService->sku_key = 'SKU_ID';
                $LocationService->obtainEnumerate($res_db);
            }
            if (empty($res_db)) {
                throw new Exception(L('无查询结果'));
            }
            foreach ($res_db as $value) {
                $value['availableForSale'] = 0;
                $value['SHIPPED_NUM'] = 0;
                $response_data[$value['SKU_ID']][] = $value;
            }
            $res = $this->return_success;
            $res['data'] = $response_data;
        } catch (Exception $exception) {
            $res = $this->return_error;
            $res['data'] = $this->error_message;
            $res['msg'] = $exception->getMessage();
            if ($exception->getCode()) $res['code'] = $exception->getCode();
        }
        if ($require_data_temp) {
            return $res;
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $require
     *
     * @throws Exception
     */
    private function checkSkuOccupyRequire($require)
    {
        $rules = [
            'order_id' => 'required|numeric',
        ];
        $attributes = ['order_id' => '订单 ID'];
        ValidatorModel::validate($rules, $require, $attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            throw new Exception(L('请求异常'), 40001);
        }
    }

    /**
     * @param $res
     *
     * @return array
     */
    private function checkRepeatOrderOperation($res)
    {
// check po change or not
        $req = Mainfunc::getInputJson();
        $order_id = $req['params']['goods_info'][0]['ORDER_ID'];
        $old_data = $req['params']['doship']['fmt_order_data'];
        $changed = BaseCommon::order_changed_by_old($order_id, $old_data);
        if ($changed['is_err']) {
            $info = '操作失败' . "[{$changed['msg']}]";
            $res['status'] = 0;
            $res['data'] = null;
            $res['info'] = $info;
            $this->ajaxReturn($res);
        }
        // check end
    }


    /**
     * @param null $po_id
     *
     * @return array
     */
    public function checkResidualSend($po_id = null)
    {
        try {
            if (empty($po_id)) {
                $po_id = DataModel::getData()['po_id'];
            }
            if (empty($po_id)) {
                throw new Exception('PO ID 缺失');
            }
            $Model = new Model();
            $where['PO_ID'] = $po_id;
            $res_body = $Model->table('tb_b2b_goods,tb_b2b_order')
                ->field('sku_show AS SKU_ID,SUM(TOBE_DELIVERED_NUM) AS ALL_TOBE_DELIVERED_NUM')
                ->where($where)
                ->where('tb_b2b_order.ID = tb_b2b_goods.ORDER_ID')
                ->group('sku_show')
                ->select();
            if (empty($res_body)) {
                throw new Exception('查询结果为空', 400404);
            }
            $res = $this->return_success;
            $res['data'] = $res_body;
        } catch (Exception $exception) {
            $res = $this->return_error;
            $res['info'] = $exception->getMessage();
            $err_code = $exception->getCode();
            if ($err_code) $res['code'] = $err_code;
        }
        $this->ajaxReturn($res);
        if (empty($po_id)) {
        } else {
            return $res;
        }
    }

    /**
     * @param $ship_list
     */
    private function updateShipListData($ship_list)
    {
        $ship_list_id_arr = array_column($ship_list, 'ID');
        $repair = new RepairAction();
        $ship_list_id_str = $repair->joinBillIdStr($ship_list_id_arr);
        $Model = new Model();
        // (t3.unit_price/(1 + (IF(tb_ms_cmn_cd.CD_VAL, replace(tb_ms_cmn_cd.CD_VAL, '%', ''), 0) / 100))) AS  unit_price_no_tax,
        if ($ship_list_id_str) {
            $sql = "SELECT
                SKU_ID,
                SKU_ID AS SHIPPING_SKU,
                SKU_ID AS sku_show,
                DELIVERED_NUM,
                unit_price,
                unit_price_no_tax,
                power,
                power_no_tax,
                order_batch_id,
                concat(order_batch_id,t4.warehouse) as bill_key,
                batch
            FROM
                (
                    SELECT
                        t3.GSKU AS SKU_ID,
                        t3.send_num AS DELIVERED_NUM,
                        t3.unit_price,
                        (t3.unit_price/(1+ifnull(t3.pur_invoice_tax_rate,0))) AS  unit_price_no_tax,
                        (t3.unit_price * t3.send_num) AS power,
                        ((t3.unit_price * t3.send_num)/(1 + (IF(tb_ms_cmn_cd.CD_VAL, replace(tb_ms_cmn_cd.CD_VAL, '%', ''), 0) / 100))) AS  power_no_tax,
                        t2.order_batch_id,
                        t2.warehouse,
                        t3.batch                      
                    FROM
                        (tb_wms_bill AS t1,
                        tb_b2b_ship_list AS t2,
                        tb_wms_stream AS t3,
                        tb_b2b_ship_goods AS t4,
                        tb_b2b_goods AS t5)
                    left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = t5.purchase_invoice_tax_rate
                    WHERE
                        (
                            t1.id = t3.bill_id                            
                            AND t2.warehouse = t1.warehouse_id
                            AND t2.ID IN ({$ship_list_id_str})                            
                            AND (( t1.link_bill_id IS NOT NULL
                              AND t2.order_batch_id = t1.link_bill_id
                            )OR (t2.out_bill_id IS NOT NULL
                            AND t1.bill_id  = t2.out_bill_id 
                            ))
                            AND (t1.bill_type IN (
                            'N000950100',
                            'N000950200',
                            'N000950300',
                            'N000950400',
                            'N000950500',
                            'N000950600',
                            'N000950700',
                            'N000950905',
                            'N000950906'
                        ) OR t1.type = 0)
                            AND t4.SHIP_ID = t2.ID
                            AND t4.sku_show = t3.GSKU
                            AND t4.goods_id = t5.ID
                        )
                 ) AS t4
            ";
            $all_goods_db = $Model->query($sql);
        }
        foreach ($all_goods_db as $k => $v) {
            $all_goods_up_arr[$v['bill_key']][] = $v;
        }

        foreach ($ship_list as $key => $list) {
            if ($all_goods_up_arr[$list['order_batch_id'] . $list['warehouse']]) {
                if (empty($ship_list[$key]['all_power'])) {
                    $ship_list[$key]['all_power'] = 0;
                }
                $ship_list[$key]['all_power'] += array_sum(array_column($all_goods_up_arr[$list['order_batch_id'] . $list['warehouse']], 'power'));
                $ship_list[$key]['power_no_tax_all'] += array_sum(array_column($all_goods_up_arr[$list['order_batch_id'] . $list['warehouse']], 'power_no_tax'));
                $ship_list[$key]['goods'] = $all_goods_up_arr[$list['order_batch_id'] . $list['warehouse']];
            }
        }
        $ship_list = SkuModel::getInfos($ship_list, 'goods', 'sku_show',
            ['spu_name', 'attributes', 'image_url', 'product_sku'],
            ['spu_name' => 'goods_title', 'attributes' => 'goods_info', 'image_url' => 'guds_img_cdn_addr']
        );
        foreach ($ship_list as $key => $list) {
            $ship_list[$key]['goods'] = SkuModel::getTableAttr($ship_list[$key]['goods']);
        }
        return $ship_list;
    }

    /**
     * @param $order_id
     * @param $where
     * @param $Model
     */
    private function checkOrUpdateWarehouseListStatus($order_id, $Model)
    {
        $where['ORDER_ID'] = $order_id;
        $where['status'] = 2;
        $count_db = $Model->table('tb_b2b_warehouse_list')
            ->where($where)
            ->count();
        if ($count_db) {
            $this->upd_warehosing_status($order_id);
        }
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function orderExcelExport()
    {
        $export_params = $_POST['export_params'];
        $data = json_decode($export_params, true);
        $ids = $this->getOrderIds($data);
        $B2bService = new B2bService();
        $B2bService->orderExcelExport($ids);
    }

    /**
     * @return mixed
     */
    private function getOrderIds($data)
    {
        $show_list = $this->show_list($data);
        return array_column($show_list, 'id');
    }

    /**
     * @return array
     */
    public function getDeliveryMailCount()
    {
        $res = [];
        try {
            $B2bService = new B2bService();
            $res = $B2bService->getDeliveryMail();
        } catch (Exception $exception) {
            Logs($exception->getMessage(), 'getDeliveryMailCount', 'MailData');
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getTallyMailCount()
    {
        $res = [];
        try {
            $B2bService = new B2bService();
            $res = $B2bService->getTallyMail();
        } catch (Exception $exception) {
            Logs($exception->getMessage(), 'getTallyMailCount', 'MailData');
        }
        return $res;
    }

    /**
     * @return string
     */
    private function getWarehouseSearchTableStr($warehouse_arr)
    {
        $warehouse_string = WhereModel::arrayToInString($warehouse_arr);
        return "    (
        SELECT
            t3.ID
        FROM
            (
                SELECT
                    tb_b2b_doship.ID
                FROM
                    (
                        SELECT
                            tb_b2b_doship.ID
                        FROM
                            (
                                `tb_b2b_doship`,
                                `tb_b2b_ship_list`,
                                `tb_b2b_ship_goods`,
                                `tb_wms_batch_order`,
                                `tb_wms_batch`
                            )
                        WHERE
                            (
                                tb_b2b_ship_list.ORDER_ID = tb_b2b_doship.ORDER_ID
                                AND tb_b2b_ship_list.ID = tb_b2b_ship_goods.SHIP_ID
                                AND tb_wms_batch_order.ORD_ID = tb_b2b_doship.PO_ID
                                AND tb_wms_batch.id = tb_wms_batch_order.batch_id
                                AND tb_b2b_ship_goods.DELIVERY_WAREHOUSE IN ({$warehouse_string})                           
                            )
                        GROUP BY
                            tb_b2b_doship.ID
                    ) AS t1,
                    `tb_b2b_doship`
                WHERE
                    tb_b2b_doship.ID = t1.ID
                UNION
                    SELECT
                        tb_b2b_doship.ID
                    FROM
                        (
                            SELECT
                                tb_b2b_doship.ID
                            FROM
                                (
                                    `tb_b2b_doship`,
                                    `tb_wms_batch_order`,
                                    `tb_wms_batch`
                                )
                            WHERE
                                (
                                    tb_wms_batch_order.use_type = 1
                                    AND tb_wms_batch_order.batch_id = tb_wms_batch.id
                                    AND tb_wms_batch.vir_type = 'N002440100'
                                    AND tb_b2b_doship.PO_ID = tb_wms_batch_order.ORD_ID
                                    AND tb_wms_batch_order.delivery_warehouse IN ({$warehouse_string})                                      
                                )
                            GROUP BY
                                tb_b2b_doship.ID
                        ) AS t2,
                        `tb_b2b_doship`
                    WHERE
                        tb_b2b_doship.ID = t2.ID
            ) AS t3
    ) AS t4,
    `tb_b2b_doship`";
    }

    /**
     * @param $data
     *
     * @return array|float|int
     */
    private function joinExcludingTax(array $data, $key, $amount_key, $def_val_decimal)
    {
        $data = DataModel::percentageToDecimal($data, $key . '_val');
        $data = array_map(function ($value) use ($key, $amount_key, $def_val_decimal) {
            if ($def_val_decimal) {
                $value[$amount_key . '_excluding_tax'] = sprintf("%0.2f", $value[$amount_key] / (1 + $def_val_decimal));
            } else {
                $value[$amount_key . '_excluding_tax'] = sprintf("%0.2f", $value[$amount_key] / (1 + $value[$key . '_val_decimal']));
            }
            return $value;
        }, $data);
        return $data;
    }

    /**
     * @param $all_ship_excluding_tax
     * @param string $temp_key
     * @param null $all_ship_excluding_tax_key_val
     *
     * @return null
     */
    public function assemblyTaxMapping($all_ship_excluding_tax,
                                       $temp_key = 'ship_list_id',
                                       $all_ship_excluding_tax_key_val = null)
    {
        foreach ($all_ship_excluding_tax as $value) {
            $all_ship_excluding_tax_key_val[$value[$temp_key]] = $value;
        }
        return $all_ship_excluding_tax_key_val;
    }

    /**
     * @param $data
     * @param $all_ship_excluding_tax_key_val
     * @param string $ship_id_key
     * @param bool $is_two_arr
     *
     * @return mixed
     */
    public function joinShipExcludingData($data, $all_ship_excluding_tax_key_val, $ship_id_key = 'ID', $is_two_arr = true)
    {
        if ($is_two_arr) {
            foreach ($data as &$datum) {
                $datum = $this->exchangeShipExcludingData($all_ship_excluding_tax_key_val, $ship_id_key, $datum);
            }
        } else {
            $data = $this->exchangeShipExcludingData($all_ship_excluding_tax_key_val, $ship_id_key, $data);
        }
        return $data;
    }

    /**
     * @param $all_ship_excluding_tax_key_val
     * @param $ship_id_key
     * @param $datum
     *
     * @return mixed
     */
    private function exchangeShipExcludingData($all_ship_excluding_tax_key_val, $ship_id_key, $datum)
    {
        $datum['sum_shipping_cost'] = sprintf("%.2f",
            $all_ship_excluding_tax_key_val[$datum[$ship_id_key]]['sum_shipping_cost']);
        $datum['sum_shipping_cost_excludeing_tax'] = sprintf("%.2f",
            $all_ship_excluding_tax_key_val[$datum[$ship_id_key]]['sum_shipping_cost_excludeing_tax']);
        $datum['sum_shipping_revenue'] = sprintf("%.2f",
            $all_ship_excluding_tax_key_val[$datum[$ship_id_key]]['sum_shipping_revenue']);
        $datum['sum_shipping_revenue_excludeing_tax'] = sprintf("%.2f",
            $all_ship_excluding_tax_key_val[$datum[$ship_id_key]]['sum_shipping_revenue_excludeing_tax']);
        $datum['sum_shipping_revenue_excludeing_tax_cny'] = sprintf("%.2f",
            $all_ship_excluding_tax_key_val[$datum[$ship_id_key]]['sum_shipping_revenue_excludeing_tax_cny']);
        return $datum;
    }

    /**
     *
     */
    public function receipt_claim_list()
    {
        $this->display();
    }

    /**
     *
     */
    public function receipt_claim_detail()
    {
        $this->display();
    }

    /**
     *
     */
    public function receivable_list()
    {
        $this->display();
    }

    /**
     *
     */
    public function claimList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->checkClaimListData($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $B2bService->searchClaimList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkClaimListData($data)
    {
        $rules = [
            'search.claim_status' => 'sometimes|required|string|min:10|max:10',
            'search.created_at' => 'sometimes|required|array',
            'search.created_at.start' => 'sometimes|required|date',
            'search.created_at.end' => 'sometimes|required|date',

            'pages.per_page' => 'sometimes|required|numeric',
            'pages.current_page' => 'sometimes|required|numeric',
        ];
        $attributes = [
            'search.claim_status' => '认领状态',
            'search.created_at.start' => '开始时间',
            'search.created_at.end' => '结束时间',
            'pages.per_page' => '每页数量',
            'pages.current_page' => '当前页数',
        ];
        $this->validate($rules, $data, $attributes);
    }

    /**
     *
     */
    public function claimDetail()
    {
        try {
            $request_data['account_transfer_no'] = I('get.account_transfer_no');
            $request_data['claim_id'] = I('get.claim_id');
            $this->checkClaimDetail($request_data);
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $B2bService->getClaimDetail($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    public function checkClaimDetail($data)
    {
        $rules = [
            'account_transfer_no' => 'required|string|size:14',
            'claim_id' => 'numeric',
        ];
        $custom_attributes = [
            'account_transfer_no' => '流水号',
            'claim_id' => '认领 ID',
        ];

        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function orderSelectSearch()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $claim_type = $request_data['claim_type'];
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            if ($request_data) {
                $this->checkOrderSelectRequest($request_data, $claim_type);
            }
            if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                $PurService = new PurService();
                $res['data'] = DataModel::toArray(
                    $PurService->getOrderSelect($request_data)
                );
            } else if ($claim_type == self::B2B_CLAIM_CODE) {
                $B2bService = new B2bService();
                $res['data'] = DataModel::toArray(
                    $B2bService->getOrderSelect($request_data)
                );
            }else {
                //其它类型认领收款
            }

        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     * @param string $claim_type
     *
     * @throws Exception
     */
    private function checkOrderSelectRequest($data, $claim_type = "")
    {
        $rules = [
            'search_type' => 'sometimes|required',
            'search_value' => 'sometimes|required',
            'client_name' => 'sometimes|required',
            'account_turnover_id' => 'required|numeric',
            'sale_pur_person' => 'sometimes|required',
        ];
        if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
            $custom_attributes = [
                'client_name' => '供应商名称',
                'account_turnover_id' => '流水单 ID',
                'sale_pur_person' => '采购同事',
            ];
        } else if ($claim_type == self::B2B_CLAIM_CODE) {
            $custom_attributes = [
                'client_name' => '客户名称',
                'account_turnover_id' => '流水单 ID',
                'sale_pur_person' => '销售同事',
            ];
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function claimSubmit()
    {
        try {
            $Model = new \Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('account_transfer_no_' . $request_data['account_transfer_id'], 10);

            if ($request_data) {
                $this->checkClaimSubmit($request_data);
                $this->checkClaimSum($request_data, $Model);
            } else {
                throw new Exception('请求为空');
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $Model->startTrans();
            foreach ($request_data['orders'] as $datum) {
                switch ($datum['claim_type']) {
                    //采购认领退款
                    case self::PUR_REFUND_CLAIM_CODE:
                        $addDataInfo['clause_type'] = '5';
                        $addDataInfo['class'] = __CLASS__;
                        $addDataInfo['function'] = __FUNCTION__;
                        // 根据tb_pur_relevance_order.order_id 获取relevance_id
                        $relevance_id = M('relevance_order', 'tb_pur_')->where(['order_id' => $datum['order_info']['ORDER_ID']])->getField('relevance_id');
                        if (!empty($datum['order_info']['claim_id'])) {
                            $operation_cd = 'N002870009'; // 编辑
                            $addDataInfo['money_id'] = $datum['order_info']['claim_id'];
                            $res_info = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', $operation_cd, $relevance_id, $request_data['account_transfer_no']);

                            $addDataInfo['amount_payable'] = $datum['order_info']['summary_amount'];
                            //$resSec = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', $operation_cd, $relevance_id, $request_data['account_transfer_no']);
                            $resSec = $this->checkClaimData($datum, true);
                            if (!$res_info) {
                                throw new Exception('生成算作抵扣金记录失败');
                            }
                            if (!$resSec) {
                                throw new Exception('生成使用抵扣金记录失败');
                            }

                        } else {
                            $operation_cd = 'N002870008'; // 新增
                            $addDataInfo['amount_payable'] = $datum['order_info']['summary_amount'];
                            //$res_info = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', $operation_cd, $relevance_id, $request_data['account_transfer_no']);
                            $res_info = $this->checkClaimData($datum, true);
                            if (!$res_info) {
                                throw new Exception('生成使用抵扣金记录失败');
                            }
                        }
                        break;
                    //GP认领剩余数量校验
                    case self::GP_BIG_ORDER_CLAIM_CODE:
                        $remain_amount = $this->getGPRemainAmount($datum);
                        if (round($remain_amount,2) < 0.00) {
                            throw new Exception($datum['order_info']['order_no'].'的GP订单剩余应收金额不能小于0');
                        }else if(round($remain_amount,2) == 0.00){
                            //如果存在GP订单的剩余应收为0 则标记待发货
                            $finish = $this->finishGP($Model,$datum);
                            if ($finish['code'] != 200) {
                                throw new Exception($finish['msg'].': '.$datum['order_info']['order_no'] . '的GP订单完成应收失败，没有改成待发货');
                            }
                        }
                        break;
                }
            }
            $B2bService->postClaimSubmit($request_data, $Model);
            $Model->commit();
            foreach ($request_data['orders'] as $datum) {
                switch ($datum['claim_type']) {
                    case self::B2B_CLAIM_CODE:
                        $update_order_status = $this->updateOrderReceivableAccount($datum['order_info']['ORDER_ID']);
                        $associated_msg = null;
                        if (!empty($datum['order_info']['claim_id'])) {
                            $log_msg = '编辑收款认领记录';
                            $associated_msg = "流水ID：{$request_data['account_transfer_no']}";
                        } else {
                            $log_msg = '收款认领';
                        }
                        B2bModel::addLog($datum['order_info']['ORDER_ID'], 200, $log_msg, $associated_msg);
                        break;
                    case self::PUR_REFUND_CLAIM_CODE:
                        $update_order_status = (new PurService())->updatePurRelevanceOrderRefund($datum['order_info']['ORDER_ID']);
                         break;
                }
                Logs([$datum['claim_type'], $update_order_status], __CLASS__, __FUNCTION__);
            }
            RedisModel::unlock('account_transfer_no_' . $request_data['account_transfer_id']);
        } catch (Exception $exception) {
            $Model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //需求9588-
    public function checkClaimData($claimData, $allow_less_than_zero = false)
    {
        $bool = false;
        if (empty($claimData)) return $bool;
        $order = M('relevance_order', 'tb_pur_')
            ->alias('t')
            ->field('relevance_id,supplier_id,supplier_id_en,sp_charter_no,our_company,amount_currency,procurement_number,supplier_new_id')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->where(['t.order_id' => $claimData['order_info']['ORDER_ID']])
            ->find();

        //获取供应商抵扣金账户 我方公司-供应商-币种
        $where = [
            'our_company_cd'  => $order['our_company'],
            'deduction_currency_cd'  => $order['amount_currency'],
        ];
        if ($order['supplier_new_id']) {
            $where['supplier_id'] = $order['supplier_new_id'];
        } else {
            $where['supplier_name_cn'] = $order['supplier_id'];
        }
        $deduction = M('deduction', 'tb_pur_')->where($where)->find();

        /*if (!$deduction) { 
            throw new \Exception(L('供应商抵扣金账户不存在')); // 19542 抵扣金扣减逻辑补充
        }*/
        if ($deduction['over_deduction_amount'] < $claimData['order_info']['summary_amount'] && !$allow_less_than_zero) {
            throw new \Exception(L('供应商账户余额小于当前抵扣金额'));
        }

        $param['relevance_id'] = $order['relevance_id'];
        $param['amount_deduction'] = $claimData['order_info']['summary_amount'];
        $param['remark_deduction'] = '采购退款认领，使用抵扣金';
        $param['deduction_type_cd'] = 'N002660100'; // 多付未退款
        $param['voucher_deduction'] = json_encode([["name"=>"","savename"=>""]]);
        $deduction_detail_id = (new PurPaymentService())->useDeduction($param, $allow_less_than_zero);
        if (!$deduction_detail_id) throw new Exception('使用抵扣金失败');
        return $deduction_detail_id;
    }
    /**
     * @param $data
     * @param $Model
     * @param int $sum
     * @param array $order_ids
     *
     * @throws Exception
     */
    private function checkClaimSum($data, $Model, $sum = 0, $order_ids = [])
    {
        foreach ($data['orders'] as $datum) {
            $sum += $datum['order_info']['claim_amount'];
            if ($datum['order_info']['ORDER_ID']) {
                $order_ids[] = $datum['order_info']['ORDER_ID'];
            }
        }
        if ($order_ids) {
//            $where['tb_fin_claim.order_id'] = ['NOT IN', $order_ids];
            $temp_where_string = WhereModel::arrayToInString($order_ids);
            $temp_where_string = "tb_fin_claim.order_id NOT IN ({$temp_where_string})";
        }
        $where['tb_fin_account_turnover.id'] = $data['account_transfer_id'];

        $res = $Model->table('tb_fin_account_turnover')
            ->field('tb_fin_account_turnover.id,
            tb_fin_account_turnover.original_amount,
            SUM(tb_fin_claim.claim_amount) AS sum_claim_amount')
            ->join("LEFT JOIN tb_fin_claim ON tb_fin_claim.account_turnover_id = tb_fin_account_turnover.id
             AND tb_fin_claim.order_type IN('N001950200', 'N001950600', 'N001950656')
             AND {$temp_where_string} ")
            ->where($where)
            ->find();
        if (0 > bcsub((float)$res['original_amount'] - ((float)$res['sum_claim_amount'] + (float)$sum))) {
            Logs([$res['original_amount'], $res['sum_claim_amount'], $sum, $res['sum_claim_amount'] + $sum, [$temp_where_string, $where]], __CLASS__, __FUNCTION__);
            throw new Exception('认领金额超出流水金额');
        }
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkClaimSubmit($data)
    {
        $rules = [
            'account_transfer_no' => 'required',
            'account_transfer_id' => 'required|numeric',
//            'sale_team' => 'required|string|size:10',
            'orders' => 'required|array',
        ];
        foreach ($data['orders'] as $key => $value) {
            $this->checkClaimType($value['claim_type']);
            if($value['claim_type'] != self::GP_BIG_ORDER_CLAIM_CODE){
                $rules["orders.{$key}.order_info"] = 'required|array';
                $rules["orders.{$key}.order_info.claim_id"] = 'sometimes|required|numeric';
                $rules["orders.{$key}.order_info.PO_ID"] = 'required|string';
                $rules["orders.{$key}.order_info.ORDER_ID"] = 'required|string';
                $rules["orders.{$key}.order_info.claim_amount"] = 'required|numeric|min:0';

                if ($value['claim_type'] == self::B2B_CLAIM_CODE) {
                    $rules["orders.{$key}.order_info.sale_team"] = 'sometimes|required|string|size:10';
                }

                $rules["orders.{$key}.deductions"] = 'sometimes|required|array';
                foreach ($value['deductions'] as $dedu_key => $dedu_value) {
                    $rules["orders.{$key}.deductions.{$dedu_key}.deduction_type"] = 'sometimes|required|size:10';
                    $rules["orders.{$key}.deductions.{$dedu_key}.deduction_amount"] = 'sometimes|required|numeric|min:0.01';
                }
            }else{
                $rules["orders.{$key}.order_info.order_no"] = 'required|string';
                $rules["orders.{$key}.order_info.order_id"] = 'required|string';
            }

            if (empty($value['deductions'])) {
                if ($value['order_info']['claim_amount'] <= 0) {
                    throw new Exception(L('认领金额要大于0'));
                }
            }
        }
        $custom_attributes = [
            'account_transfer_no' => '流水NO',
            'account_transfer_id' => '流水ID',
            'sale_team' => '销售团队',
            'orders' => '订单',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function updateClaimStatus()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->checkUpdateClaimStatusRequest($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['body'] = $B2bService->updateClaimStatus($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkUpdateClaimStatusRequest($data)
    {
        $rules = [
            "account_turnover_id" => "required|numeric",
            "claim_status" => "required|string|size:10"
        ];
        $custom_attributes = [
            "account_turnover_id" => "流水单 ID",
            "claim_status" => "完结状态"
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function deleteClaim()
    {
        $Model = new Model();
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $claim_type = $request_data['claim_type'];

            if ($request_data) {
                $claim_id = $request_data['claim_id'];
                $this->checkDeleteClaimRequest($request_data);
                $this->checkClaimType($claim_type);
                if($claim_type == B2bAction::GP_BIG_ORDER_CLAIM_CODE){
                    //检测流水和订单状态
                    $res = $this->checkOrderAndClaimStatus($claim_id);
                    if(!$res){
                        throw new Exception('订单不是待付款且流水状态不是未完结的流水是不允许删除的');
                    }
                }

            }
            $B2bService = new B2bService($claim_type);
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $where['id'] = $request_data['claim_id'][0];
            if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                $where['order_type'] = 'N001950600'; // 采购退款
            }else if ($claim_type == B2bAction::GP_BIG_ORDER_CLAIM_CODE) {
                $where['order_type'] = 'N001950656'; // GP收款
            } else {
                $where['order_type'] = 'N001950200'; // 默认是B2B收款
            }
            $order_id = $Model->table('tb_fin_claim')->where($where)->getField('order_id');
            $account_turnover_id = $Model->table('tb_fin_claim')->where($where)->getField('account_turnover_id');

            // 获取流水单号
            $where_account['id'] = $account_turnover_id;
            $account_transfer_no = $Model->table('tb_fin_account_turnover')->where($where_account)->getField('account_transfer_no');

            $Model->startTrans();
            if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                // 根据order_id 获取 relevance_id
                $addDataInfo['class'] = __CLASS__;
                $addDataInfo['function'] = __FUNCTION__;
                $addDataInfo['money_id'] = $claim_id[0];
                $addDataInfo['clause_type'] = '5';
                $whereRelOrder['order_id'] = $order_id;
                $relevance_id = $Model->table('tb_pur_relevance_order')->where($whereRelOrder)->getField('relevance_id');
                $poRes = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870010', $relevance_id, $account_transfer_no);

                if (!$poRes) {
                    throw new Exception('生成抵扣金记录失败');
                }
            }

            $res['body'] = $B2bService->deleteClaimAll($claim_id, $Model);
            $Model->commit();

            $adn_msg = "流水ID：{$account_transfer_no}";
            B2bModel::addLog($order_id, 200, '删除收款认领记录', $adn_msg);
            switch ($claim_type) {
                case self::B2B_CLAIM_CODE:
                    $update_order_status = $this->updateOrderReceivableAccount($order_id);
                    break;
                case self::PUR_REFUND_CLAIM_CODE:
                    $update_order_status = (new PurService())->updatePurRelevanceOrderRefund($order_id);
                    break;
            }
            Logs([$claim_type, $update_order_status], __CLASS__, __FUNCTION__);

        } catch (Exception $exception) {
            $Model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkDeleteClaimRequest($data)
    {
        $rules = [
            'claim_id' => 'required|array',
        ];
        $custom_attributes = [
            'claim_id' => '流水关联ID',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function receivableList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->checkReceivableListData($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $B2bService->receivableList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkReceivableListData($data)
    {
        $rules = [
            'search.receivable_status' => 'sometimes|required|array',
            'search.order_type' => 'sometimes|required|string',
            'search.CLIENT_NAME' => 'sometimes|required|string',
            'search.SALES_TEAM' => 'sometimes|required|array',
            'search.PO_USER' => 'sometimes|required|string',
            'search.verification_by' => 'sometimes|required|string',
            'search.verification_at' => 'sometimes|required|array',
            'search.created_at' => 'sometimes|required|array',

            'pages.per_page' => 'sometimes|required|numeric',
            'pages.current_page' => 'sometimes|required|numeric',
        ];
        $custom_attributes = [
            'search.receivable_status' => '认领状态',
            'search.order_type' => '订单状态',
            'search.CLIENT_NAME' => '客户',
            'search.SALES_TEAM' => '销售团队',
            'search.PO_USER' => '销售同事',
            'search.verification_by' => '核销人',
            'search.verification_at' => '核销日期',
            'search.created_at' => '创建时间',

            'pages.per_page' => '每页数量',
            'pages.current_page' => '当前页数',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function submitCheckSales()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->checkSubmitCheckSales($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $number_influences = $B2bService->updateSubmitCheckSales($request_data);
            $res['msg'] = L('提交成功');
            $res['body'] = L('更改影响条数:') . $number_influences;
            $log_msg = '提交核销';
            if (false === $request_data['']) {
                $log_msg = L('撤回到待提交状态');
            }
            B2bModel::addLog($request_data['order_id'], 200, $log_msg);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkSubmitCheckSales($data)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'submit_check' => 'boolean',
            'remark' => 'max:255',
        ];
        $custom_attributes = [
            'order_id' => 'B2B 订单',
            'submit_check' => '提交审核状态',
            'remark' => '备注',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function checkSales()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->checkCheckSalesRequest($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $number_check = $B2bService->updateCheckSales($request_data);
            $res['msg'] = '审核成功';
            $res['body'] = "审核更改条数" . $number_check;
            $log_msg = '核销';
            if (false === $request_data['']) {
                $log_msg = '撤回到待核销状态';
            }
            B2bModel::addLog($request_data['order_id'], 200, $log_msg);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkCheckSalesRequest($data)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'check' => 'boolean',
            'rate_losses' => 'required|numeric',
        ];
        $custom_attributes = [
            'order_id' => 'B2B 订单',
            'check' => '提交核销状态',
            'rate_losses' => '应收金额',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function receivableDetail()
    {
        try {
            $request_data = I();
            if ($request_data) {
                $this->checkReceivableDetail($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $B2bService->getReceivableDetail($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function checkReceivableDetail($data)
    {
        $rules = [
            'order_id' => 'required|numeric',
        ];
        $custom_attributes = [
            'order_id' => 'B2B 订单',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function claimListExport()
    {
        try {
            $request_data = $_POST['export_params'];
            $request_data = DataModel::jsonToArr($request_data);
            if ($request_data) {
//                $this->checkClaimListData($request_data);
            }
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $data = $B2bService->receivableList($request_data, true);
            $B2bService->buildAndExecutionReceivableListExport($data['data']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $order_id
     * @param null $update_key
     *
     * @return bool
     */
    public function updateOrderReceivableAccount($order_id, $update_key = null)
    {
        $B2bService = new B2bService();
        (new B2bReceivableService())->updateWholeOrder($order_id);
        return $B2bService->updateOrderReceivableAccount($order_id, $update_key);
    }

    /**
     *
     */
    public function syncReceiptToReceivable()
    {
        $B2bService = new B2bService();
        $res = $B2bService->syncReceiptToReceivable();
        var_dump($res);
    }

    /**
     * @param $search_all_orders
     * @param $data
     *
     * @return mixed
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    private function assemblyExcelToList($search_all_orders, $data)
    {
        $B2bService = new B2bService();
        $ids = array_column($search_all_orders, 'id');
        $excel_data = $B2bService->orderExcelExport($ids, false);
        $data['sum_shipping_cost_excludeing_tax'] = number_format(
            array_sum(array_column($excel_data['order_info'], 'sum_shipping_cost_excludeing_tax')),
            2);
        $data['sum_shipping_revenue_excludeing_tax_cny'] = number_format(
            array_sum(array_column($excel_data['order_info'], 'sum_shipping_revenue_excludeing_tax_cny')),
            2);
        $join_receivable_where['tb_b2b_receivable.order_id'] = ['IN', $ids];
        list(, , $data['sum_current_receivable_cny']) = (new B2bRepository())->getReceivableList($join_receivable_where, 1, false);
        $data['sum_current_receivable_cny'] = number_format($data['sum_current_receivable_cny'], 2);
        return $data;
    }

    /**
     *
     */
    public function getPurchaseOrder()
    {
        try {
            $order_id = I('order_id');
            $B2bServer = new B2bService();
            $res = DataModel::$success_return;
            $res['data'] = $B2bServer->getPurchaseOrder($order_id);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        DataModel::ajaxReturn($res, 'JSON', JSON_NUMERIC_CHECK);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function getOrderDetailCodeData($data)
    {
        $data['sales_team'] = B2bModel::get_sales_team();
        $data['number_th'] = $this->number_th;
        $data['node_is_workday'] = B2bModel::get_code('node_is_workday');
        $data['node_type'] = B2bModel::get_code('node_type');
        $data['node_date'] = B2bModel::get_code('node_date');
        $data['invioce'] = B2bModel::get_code('invioce');
        $data['tax_point'] = $this->un_sign(B2bModel::get_code('tax_point'));
        $data['period'] = B2bModel::get_code('period');
        $data['or_invoice_arr'] = B2bModel::get_code('or_invoice_arr');
        $data['warehousing_state'] = B2bModel::get_code('warehousing_state');
        $data['business_type'] = B2bModel::get_code('业务类型', true);
        $data['business_direction'] = B2bModel::get_code('业务方向', true);
        $data['currency_bz'] = B2bModel::get_code('currency_bz');
        $data['shipping'] = B2bModel::get_code_cd('N00153');
        return $data;
    }

    /**
     *
     */
    public function delStagePo()
    {
        if ($_POST['po_id'] && $_POST['key_time'] && $_POST['del_user']) {
            $Model = M();
            $Model->startTrans();
            $where_po['PO_ID'] = $_POST['po_id'];
            $order_id = $Model->table('tb_b2b_order')->where($where_po)->getField('ID');
            if (empty($order_id)) {
                die('PO ID err');
            }
            $where_id['ID'] = $where['ORDER_ID'] = $order_id;
            $save['doship'] = $Model->table('tb_b2b_doship')->where($where)->select();
            $save['goods'] = $Model->table('tb_b2b_goods')->where($where)->select();
            $save['info'] = $Model->table('tb_b2b_info')->where($where)->select();
            $save['order'] = $Model->table('tb_b2b_order')->where($where_id)->select();
            $save['profit'] = $Model->table('tb_b2b_profit')->where($where)->select();
            $save['receipt'] = $Model->table('tb_b2b_receipt')->where($where)->select();
            $save['ship_list'] = $Model->table('tb_b2b_ship_list')->where($where)->select();
            if ($save['ship_list']) {
                $where_ship['SHIP_ID'] = array('IN', array_column($save['ship_list'], 'ID'));
                $save['ship_goods'] = $Model->table('tb_b2b_ship_goods')->where($where_ship)->select();
            }
            $save['warehouse_list'] = $Model->table('tb_b2b_warehouse_list')->where($where)->select();
            if ($save['warehouse_list']) {
                $where_warehousing_goods['warehousing_id'] = array('IN', array_column($save['warehouse_list'], 'ID'));
                $save['warehousing_goods'] = $Model->table('tb_b2b_warehousing_goods')->where($where_warehousing_goods)->select();
            }
            $save['po_id'] = $save['info'][0]['PO_ID'];
            foreach ($save as $key => $value) {
                if ($value) {
                    $save[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
            $save['order_id'] = $_POST['order_id'];
            $save['del_user'] = $_POST['del_user'];
            $save['key_time'] = $_POST['key_time'];
            $save['create_time'] = date('Y-m-d H:i:s');
            $add_res = $Model->table('tb_b2b_del')->add($save);
            if (!$add_res) {
                $Model->rollback();
                die('add_res err');
            }
            // del
            $del_res['tb_b2b_goods'] = $Model->table('tb_b2b_goods')->where($where)->delete();
            $del_res['tb_b2b_info'] = $Model->table('tb_b2b_info')->where($where)->delete();
            $del_res['tb_b2b_order'] = $Model->table('tb_b2b_order')->where($where_id)->delete();
            $del_res['tb_b2b_profit'] = $Model->table('tb_b2b_profit')->where($where)->delete();
            if (!empty($save['receipt'])) {
                $del_res['tb_b2b_receipt'] = $Model->table('tb_b2b_receipt')->where($where)->delete();
            }
            if (!empty($save['doship'])) {
                $del_res['tb_b2b_doship'] = $Model->table('tb_b2b_doship')->where($where)->delete();
            }
            if (!empty($save['ship_list'])) {
                $del_res['tb_b2b_ship_list'] = $Model->table('tb_b2b_ship_list')->where($where)->delete();
                $del_res['tb_b2b_ship_goods'] = $Model->table('tb_b2b_ship_goods')->where($where_ship)->delete();
            }
            if (!empty($save['tb_b2b_warehouse_list'])) {
                $del_res['tb_b2b_warehouse_list'] = $Model->table('tb_b2b_warehouse_list')->where($where)->delete();
                $del_res['tb_b2b_warehousing_goods'] = $Model->table('tb_b2b_warehousing_goods')->where($where_warehousing_goods)->delete();
            }
            foreach ($del_res as $k => $val) {
                if (!$val) {
                    $Model->rollback();
                    die($k . ' del err');
                }
            }
            $Model->commit();
            var_dump($del_res);
            die('success');
        }
        die('no data');
    }

    /**
     *
     */
    public function getSendNetWarehouseCds()
    {
        try {
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $res['data'] = $B2bService->getSendNetWarehouseCds();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $claim_type
     *
     * @throws Exception
     */
    private function checkClaimType($claim_type)
    {
        if (!in_array($claim_type, [self::B2B_CLAIM_CODE, self::PUR_REFUND_CLAIM_CODE,self::GP_BIG_ORDER_CLAIM_CODE])) {
            throw new \Exception(L('未知收款类型'));
        }
    }

    /**
     * @param $ship_id
     *
     * @return mixed
     * @throws Exception
     */
    private function getWhereBillIdFromShipId($ship_id)
    {
        $where['tb_b2b_ship_list.ID'] = $ship_id;
        $ship = $this->module->table('tb_b2b_ship_list')
            ->field('IFNULL(out_bill_id,order_batch_id) AS link_bill_id')
            ->where($where)
            ->find();
        if (empty($ship)) {
            throw new Exception(L('无对应出库单数据'));
        }
        return $ship;
    }

    /**
     * @param $ship_id
     * @param $where
     *
     * @return mixed
     * @throws Exception
     */
    private function getLinkBillIdFromShipListId($ship_id)
    {
        $link_bill_id = $this->getWhereBillIdFromShipId($ship_id)['link_bill_id'];
        return $this->module->table('tb_wms_bill')
            ->where("((tb_wms_bill.link_bill_id = '{$link_bill_id}' OR tb_wms_bill.bill_id = '{$link_bill_id}') AND tb_wms_bill.type = 0)", null, true)
            ->getField('tb_wms_bill.link_bill_id');
    }

    public function withdrawVirtualSendOut()
    {
        try {
            $po_id = I('po_id');
            $ship_id = I('ship_id');
            $Model = new Model();
            $Model->startTrans();
            if (empty($ship_id)) {
                $order_batch_id = I('order_batch_id');
                $ship_id = $Model->table('tb_b2b_ship_list')
                    ->where(['order_batch_id' => $order_batch_id])
                    ->getField('ID');
            }
            if (empty($po_id) || empty($ship_id)) {
                throw new Exception('请求为空');
            }
            $where_ship['ID'] = $ship_id;
            $res_ship = $Model->table('tb_b2b_ship_list')
                ->where($where_ship)
                ->delete();
            $where_ship_goods['SHIP_ID'] = $ship_id;
            $get_ship_goods = $Model->table('tb_b2b_ship_goods')
                ->where($where_ship_goods)
                ->select();
            Logs([$get_ship_goods], __FUNCTION__, __CLASS__);
            $do_ship_id = $Model->table('tb_b2b_doship')
                ->where(['PO_ID' => $po_id])
                ->getField('ID');
            $where_set_doship['ID'] = $do_ship_id;
            foreach ($get_ship_goods as $get_ship_good) {
                $where_set_goods['ID'] = $get_ship_good['goods_id'];
                $upd_goods_ship = $Model->table('tb_b2b_goods')->where($where_set_goods)->setDec('SHIPPED_NUM', $get_ship_good['DELIVERED_NUM']);
                $upd_goods_tobe = $Model->table('tb_b2b_goods')->where($where_set_goods)->setInc('TOBE_DELIVERED_NUM', $get_ship_good['DELIVERED_NUM']);
                $upd_doship_ship = $Model->table('tb_b2b_doship')->where($where_set_doship)->setDec('sent_num', $get_ship_good['DELIVERED_NUM']);
                $upd_doship_tobe = $Model->table('tb_b2b_doship')->where($where_set_doship)->setInc('todo_sent_num', $get_ship_good['DELIVERED_NUM']);
                if (false === $upd_goods_ship || false === $upd_goods_tobe || false === $upd_doship_ship || false === $upd_doship_tobe) {
                    throw new Exception('更新商品异常');
                }

            }
            $res_ship_goods = $Model->table('tb_b2b_ship_goods')
                ->where($where_ship_goods)
                ->delete();
            $where_warehouse['SHIP_LIST_ID'] = $ship_id;
            $res_warehouse = $Model->table('tb_b2b_warehouse_list')
                ->where($where_warehouse)
                ->delete();
            $where_warehouse_goods['ship_id'] = $ship_id;
            $res_warehouse_goods = $Model->table('tb_b2b_warehousing_goods')
                ->where($where_warehouse_goods)
                ->delete();
            $B2bAction = new B2bAction();
            $order_id = $Model->table('tb_b2b_order')->where(['PO_ID' => $po_id])->getField('ID');
            $res = $B2bAction->updateOrderReceivableAccount($order_id);
            if (false === $res_ship || false === $res_ship_goods || false === $res_warehouse || false === $res_warehouse_goods) {
                throw new Exception('数据处理异常');
            }
            $todo_sent_num = $Model->table('tb_b2b_doship')
                ->where($where_set_doship)
                ->getField('todo_sent_num');
            if (0 === $todo_sent_num) {
                $status_order = $Model->table('tb_b2b_order')->where(['PO_ID' => $po_id])->save(['order_state' => 0]);
                $status_doship = $Model->table('tb_b2b_doship')->where($where_set_doship)->save(['shipping_status' => 1]);
                if (false === $status_order || false === $status_doship) {
                    throw  new Exception('更新状态失败');
                }
            }
            $Model->commit();
            var_dump($res);
        } catch (Exception $exception) {
            if ($Model) $Model->rollback();
            echo $exception->getMessage();
        }
    }

    /**
     * @param $order_id
     * @param null $Model
     *
     * @return mixed
     */
    private function updateStartRemindingDateReceipt($order_id, $Model = null)
    {
        if (empty($Model)) {
            $Model = M();
        }
        $where_string = 'tb_b2b_info.CLIENT_NAME = tb_crm_sp_supplier.SP_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1 ';
        $delayed = $Model->table('tb_b2b_info,tb_crm_sp_supplier')
            ->where('tb_b2b_info.ORDER_ID = ' . $order_id)
            ->where($where_string, null, true)
            ->getField('IFNULL(tb_crm_sp_supplier.PAYMENT_TIME,0) AS PAYMENT_TIME');
        $delayed = (int)$delayed;
        $save['start_reminding_date_receipt'] = date('Y-m-d', strtotime("+{$delayed} day"));
        $order = $Model->table('tb_b2b_info')->where('ORDER_ID = ' . $order_id)->save($save);
    }


    public function order_return_goods_detail()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        $detail = $service->orderReturnGoodsDetail($params);
        $this->ajaxSuccess($detail);
    }

    /**
     * 退货列表
     */
    public function return_goods_list()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        $list = $service->returnGoodsList($params);
        $this->ajaxSuccess($list);
    }

    public function return_goods_detail()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        $detail = $service->returnGoodsDetail($params['id']);
        $this->ajaxSuccess($detail);
    }

    /**
     * 发起退货
     */
    public function return_goods()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        try {
            $res = $service->returnGoods($params);
            $this->ajaxSuccess($res);
        } catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage());
        }
    }

    /**
     * 退货入库
     */
    public function return_goods_warehouse()
    {
        $params = $this->jsonParams();
        $service = new B2bService();
        try {
            $service->returnGoodsWarehouse($params);
        } catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage());
        }
        $this->ajaxSuccess();
    }

    public function withdrawEndShipment()
    {
        try {
            $order_id = DataModel::getDataNoBlankToArr()['order_id'];
            $B2bService = new B2bService();
            $res = DataModel::$success_return;
            $this->checkReceivableStatus($order_id);
            $res['data'] = $B2bService->withdrawEndShipment($order_id);
            $res['msg'] = L('影响行数：') . (int)$res['data'];
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function checkReceivableStatus($order_id)
    {
        if (empty($order_id)) {
            throw new Exception(L('订单 ID 不能为空'));
        }
        $TbB2BReceivableData = TbB2BReceivable::where('order_id', $order_id)
            ->first();
        $tobe_submitted_cd = 'N002540100';
        if (empty($TbB2BReceivableData->receivable_status)) {
            throw  new Exception(L('订单 ID 非正常 ID'));
        }
        if ($tobe_submitted_cd !== $TbB2BReceivableData->receivable_status) {
            throw  new Exception(L('请撤回应收状态到待提交，再撤回标记完结'));
        }

    }

    //理货撤回
    public function warehouseRevokeSubmit()
    {
        try {
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('b2b_order_id' . $request_data['order_id'], 10);

            if (!$request_data['warehouse_list_id'] || !$request_data['order_id']) {
                throw new Exception(L('请求参数不全'));
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            $B2bService = new B2bService('', $model);
            $B2bService->warehouseRevokeSubmit($request_data['warehouse_list_id'], $request_data['order_id']);
            $model->commit();
            RedisModel::unlock('b2b_order_id' . $request_data['order_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function tallySheetRelBatch()
    {
        $list = (new Model())->table('tb_b2b_warehouse_list wl')
            ->field('wl.*, ord.PO_ID')
            ->join('left join tb_b2b_order ord on wl.ORDER_ID = ord.ID')
            ->where(['wl.status' => 2])
            ->order('wl.ID desc')
            ->select();
        $goods_model = M('warehousing_goods', 'tb_b2b_');
        $bill_model = M('bill', 'tb_wms_');
        $batch_model = M('batch', 'tb_wms_');
        $admin_model = M('admin', 'bbm_');
        foreach ($list as $v) {
            if ($v['SHIPMENTS_NUMBER'] == $v['WAREHOUSEING_NUM']) {
                continue;
            }
            if (!$v['PO_ID']) {
                continue;
            }
            $incomplete_num = $goods_model->where(['warehousing_id' => $v['ID']])->sum('incomplete_number');
            if ($incomplete_num <= 0) {
                continue;
            }

            $user_id = $admin_model->where(['M_NAME' => $v['submit_user']])->getField('M_ID');
            if (!$user_id) {
                continue;
            }
            if (empty($v['SUBMIT_TIME'])) {
                continue;
            }
            $start_time = strtotime("{$v['SUBMIT_TIME']}");
            $end_time = $start_time + 60;
            $end_date = date('Y-m-d H:i:s', $end_time);
            $where = [
                'link_bill_id' => $v['PO_ID'],
                'bill_type' => 'N000941000',
                'relation_type' => 'N002350700',
                'type' => 1,
                'zd_user' => $user_id,
                'zd_date' => ['between', [$v['SUBMIT_TIME'], $end_date]]
            ];
            if ($v['return_warehouse_cd']) {
                $where['warehouse_id'] = $v['return_warehouse_cd'];
            }
            $bill_info = $bill_model->field('id')->where($where)->select();
            if (empty($bill_info)) {
                continue;
            }
            $bill_ids = array_column($bill_info, 'id');
            $res = $batch_model->where(['bill_id' => ['in', $bill_ids]])->save(['warehouseList_id' => $v['ID']]);
            if (false === $res) {
                Logs($bill_ids, __FUNCTION__ . 'bill id', __CLASS__);
                Logs($v['ID'], __FUNCTION__ . 'warehouse list id', __CLASS__);
            }
        }
        echo 'success';
    }

    //虚拟仓发货记录撤回
    public function b2bVirtualWarehouseRevoke()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $out_bill_id = $request_data['out_bill_id'];
            $type = $request_data['type'];
            $B2bService = new B2bService();
            $res = $B2bService->b2bVirtualWarehouseRevoke($out_bill_id, $type);
            $this->ajaxReturn($res);
        } catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage());
        }
    }

    //供应商名称空格制表符过滤
    public function supplierDataFix()
    {
        $num = I('get.num');
        //各种空格
        $columns = ['SP_NAME', 'SP_NAME_EN', 'SP_RES_NAME', 'SP_RES_NAME_EN'];
        $or_string = $this->getTrimColumnSqls($columns);
        $_string = '';
        if (!empty($or_string)) {
            $_string .= implode(' OR ', $or_string);
        }
        $model = M('_crm_sp_supplier', 'tb_');
        $ret = $model->field('ID, SP_NAME, SP_NAME_EN, SP_RES_NAME, SP_RES_NAME_EN')->where($_string)->select();
        $data = [];
        foreach ($ret as $key => $item) {
            if ($key >= $num) {
                break;
            }
            $ids[] = $item['ID'];
            $data[$key]['ID'] = $item['ID'];
            foreach ($columns as $k => $v) {
                $data[$key][$v] = addslashes(trim($item[$v]));
            }
        }
        $res = $this->saveAll($data, 'tb_crm_sp_supplier', $model);
        ELog::add(['info'=>'供应商名称空格制表符过滤tb_crm_sp_supplier','ret'=>$ids,'sql'=>$model->getLastSql(),'response'=>$res]);

        //采购单关联供应商数据过滤
        $columns = ['supplier_id', 'supplier_id_en'];
        $or_string = $this->getTrimColumnSqls($columns);
        $_string = '';
        if (!empty($or_string)) {
            $_string .= implode(' OR ', $or_string);
        }
        $model = M('_pur_order_detail', 'tb_');
        $ret = $model->field('order_id, supplier_id, supplier_id_en')->where($_string)->select();
        $data = [];
        $ids = [];
        foreach ($ret as $key => $item) {
            if ($key >= $num) {
                break;
            }
            $data[$key]['order_id'] = $item['order_id'];
            $ids[] = $item['order_id'];
            foreach ($columns as $k => $v) {
                $data[$key][$v] = isset($item[$v]) ? addslashes(trim($item[$v])) : addslashes($item[$v]);
            }
        }
        //设置批量更新的主键
        $r = $this->setPk('order_id');
        $res1 = $this->saveAll($data, 'tb_pur_order_detail', $model);
        ELog::add(['info'=>'供应商名称空格制表符过滤tb_pur_order_detail','ret'=>$ids,'sql'=>$model->getLastSql(),'response'=>$res1]);
        $list['tb_crm_sp_supplier'] = $res;
        $list['tb_pur_order_detail'] = $res1;
        $this->ajaxSuccess($list);
    }

    //配置需要过滤空格的字段SQL
    public function getTrimColumnSqls($columns)
    {
        if (is_array($columns)) {
            $data = [];
            foreach ($columns as $key => $item) {
                if (is_array($item)) {
                    $data = array_merge($data, $this->getTrimColumnSqls($item));
                } else {
                    $data = array_merge($data, $this->formatColumnSpaceSql($item));
                }
            }
            return $data;
        } else {
            return $this->formatColumnSpaceSql($columns);
        }
    }

    //配置需要过滤空格的字段SQL
    public function formatColumnSpaceSql($column)
    {
        $trim_sql = [];
        $trim_sql[] = " (" . $column . " LIKE '% ') ";
        $trim_sql[] = " (" . $column . " LIKE ' %') ";
        $trim_sql[] = " (" . $column . " LIKE '%\t%') ";
        $trim_sql[] = " (" . $column . " LIKE '%\n%') ";
        $trim_sql[] = " (" . $column . " LIKE '%\r%') ";
        return $trim_sql;
    }

    public function getGPOrders(){
        $request_data = $this->params();
        $B2bService = new B2bService();
        $res= $B2bService->getGPOrderSelect($request_data);
        $this->ajaxSuccess($res);
    }

    //删除之前检测订单状态和流水状态
    public function checkOrderAndClaimStatus($claim_id){
        $claim_account_turnover = M('fin_claim','tb_')->alias('a')
            ->field('b.claim_status,c.BWC_ORDER_STATUS')
            ->join('left join tb_fin_account_turnover_status b on a.account_turnover_id = b.account_turnover_id')
            ->join('left join tb_op_order c on a.order_id = c.ORDER_ID and a.order_no = c.ORDER_NO')
            ->where(['a.id'=>['in',$claim_id]])->find();
        //未完结和待付款同时满足
        if(($claim_account_turnover['claim_status'] == 'N002550100' || $claim_account_turnover['claim_status'] == '') && $claim_account_turnover['BWC_ORDER_STATUS'] == 'N000550300'){
            return true;
        }
        return false;
    }

    //检查GP订单的剩余应收是多少
    public function getGPRemainAmount($gp_claim_data){
        $order_id = $gp_claim_data['order_info']['order_id'];
        $order_no = $gp_claim_data['order_info']['order_no'];
        $this_receive = $gp_claim_data['order_info']['summary_amount'];

        //编辑
        if(!empty($gp_claim_data['order_info']['claim_id'])){
            $claim_id = $gp_claim_data['order_info']['claim_id'];
            $this_claim = M('fin_claim','tb_')->field('summary_amount')->where(['id'=>$claim_id])->find();
            $this_amount = $this_claim['summary_amount'];
            $check_sql = "select a.PAY_TOTAL_PRICE, a.PAY_TOTAL_PRICE - ifnull(sum(b.summary_amount),0) -$this_receive + $this_amount as remain_amount from tb_op_order a left join tb_fin_claim b on a.ORDER_ID = b.order_id and a.ORDER_NO = b.order_no  where a.ORDER_ID = $order_id and a.ORDER_NO = '{$order_no}'";
        }else{
            $check_sql = "select a.PAY_TOTAL_PRICE, a.PAY_TOTAL_PRICE - ifnull(sum(b.summary_amount),0) -$this_receive as remain_amount from tb_op_order a left join tb_fin_claim b on a.ORDER_ID = b.order_id and a.ORDER_NO = b.order_no  where a.ORDER_ID = $order_id and a.ORDER_NO = '{$order_no}'";
        }

        $res = M()->query($check_sql);
        return $res[0]['remain_amount'];
    }

    public function testFinish(){
        $model = $Model = new \Model();
        $gp_claim_data['order_info']['order_id'] = 1094;
        $gp_claim_data['order_info']['order_no'] = "GTL290034242";
        $this->finishGP($model, $gp_claim_data);
    }


    public function finishGP($model, $gp_claim_data) {
        $where['ORDER_ID'] = $gp_claim_data['order_info']['order_id'];
        $where['ORDER_NO'] = $gp_claim_data['order_info']['order_no'];
        $where['PLAT_CD'] = $gp_claim_data['order_info']['plat_cd'];
        $where['BWC_ORDER_STATUS'] = 'N000550300'; //待付款
        $order_info = $model->table('tb_op_order')->field('ORDER_ID,PLAT_CD,ORDER_NO')->where($where)->find();
        //$order_info = ['ORDER_ID' => '1087', 'PLAT_CD' => 'N000834100', 'ORDER_NO' => 'GTL290034235'];
        //print_r($order_info);die;
        $api_res = $this->changeOrderStatus($order_info);
        $api_result = json_decode($api_res,true);
        //记录请求GP的日志
        Logs([$order_info, $api_result], __FUNCTION__ . '----GP res', 'requestGP');
        if($api_result['code'] == '200'){
            $save['BWC_ORDER_STATUS'] = 'N000550400'; //待发货
            $save['ORDER_STATUS'] = '待发货'; //待发货
            $res = $model->table('tb_op_order')->where($where)->save($save);
            if (!empty($res)) {
                $msg = "该笔订单GP已经推送成已收款，ERP状态已经改成待发货，且收款认领已完成";
                OrderLogModel::addLog($order_info['ORDER_ID'], $order_info['PLAT_CD'], $msg);
            }
        }
        return $api_result;
    }


    //获取GP店铺名字和id
    public function getGPStoreData() {
        $gp_cds = CodeModel::getGpPlatCds();
        $gp_stores =M('ms_store','tb_')->field('ID as id,STORE_NAME as store_name')->where(['PLAT_CD'=>['in', $gp_cds]])->select();
        $this->ajaxSuccess($gp_stores);
    }

    public function changeOrderStatus($order_info){
        $request_data['order_sn'] = $order_info['ORDER_NO'];
        $request_data['checked'] = 1;
        $request_data['checked_content'] = "";
        $request_data['timestamp'] = time();
        $request_data['sign'] = $this->makeSign($request_data);
        $api_url = SHOPNC_URL."/shop/index.php?act=erp_api&op=checkApi";
        $response = curl_get_json_get($api_url, json_encode($request_data));
        return $response;
    }

    public function makeSign($post){
        ksort($post);
        $params = "";
        foreach($post as $k => $v){
            //如果$vshi 多维数组，需要转成json成字符串
            if (is_array($v)) {
                $v = json_encode($v);
            }
            $params .= '&'.$k.'='.$v;
        }

        $params .= '&key=' . ERP_SECRET;
        $params = trim($params,'&');

        $sign2 = strtolower(md5(strtolower($params)));
        return $sign2;
    }

    //检查GP订单的剩余应收是多少
    public function getGPRemainAmountInterface(){
        $params = $this->params();
        $order_id = $params['order_id'];
        $order_no = $params['order_no'];
        $claim_id = $params['claim_id'];
        $this_receive = $params['summary_amount'];

        $this_claim = M('fin_claim','tb_')->field('summary_amount')->where(['id'=>$claim_id])->find();
        $this_amount = $this_claim['summary_amount'];
        $check_sql = "select a.PAY_TOTAL_PRICE, a.PAY_TOTAL_PRICE - ifnull(sum(b.summary_amount),0) -$this_receive + $this_amount as remain_amount from tb_op_order a left join tb_fin_claim b on a.ORDER_ID = b.order_id and a.ORDER_NO = b.order_no  where a.ORDER_ID = $order_id and a.ORDER_NO = '{$order_no}'";
        $res = M()->query($check_sql);
        return $this->ajaxSuccess(['remain_total'=>$res[0]['remain_amount']]);
    }

    /**
     * 同步订单售后状态
     */
    public function change_order_after_sale_status()
    {
        $params = $this->params();
        //指定更新order_id 多个用','分隔
        if (!empty($params['order_id'])) {
            $order_ids = explode(',', $params['order_id']);
            $where = ['tb_op_order.ORDER_ID' => ['IN', $order_ids]];
        } else {
            //未发货的shopNC订单  待付款 待发货 交易超时自动取消 tb_ms_store BEAN_CD ShopNC
            //售后状态 status_code 退款中 完成退款 退款待审核 取消退款 审核状态 audit_status_cd 审核通过_可撤销 审核不通过 付款中 退款取消 退款成功
            $where['tb_ms_store.BEAN_CD'] = 'ShopNC';
            $where['tb_op_order_refund.source_type'] = 1; //标识为GP
            $where['tb_op_order_refund.status_code'] = ['IN', ['N002800009', 'N002800010', 'N002800013', 'N002800014']];
            $where['tb_op_order_refund.audit_status_cd'] = ['IN', ['N003170004', 'N003170005', 'N003170006', 'N003170007', 'N003170008']];
            $where['tb_op_order_refund.updated_at'] = ['gt', date("Y-m-d H:i:s",strtotime("-2 minute"))]; //2分钟之内有改动 定时任务两分钟一次
            //$where['tb_op_order.BWC_ORDER_STATUS'] = ['IN', ['N000550300', 'N000550400', 'N000551001']];
        }
        $res_arr = M('op_order', 'tb_')
            ->field(['tb_op_order.ORDER_ID,tb_op_order.ORDER_NO,tb_op_order.PLAT_CD,tb_op_order.BWC_ORDER_STATUS,tb_ms_store.BEAN_CD,tb_op_order_refund.status_code,tb_op_order_refund.audit_status_cd,tb_op_order_refund.audit_opinion,tb_op_order_refund.source_type,tb_pur_payment_audit.confirmation_remark'])
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->join('left join tb_op_order_refund on tb_op_order.ORDER_ID = tb_op_order_refund.order_id and tb_op_order.PLAT_CD = tb_op_order_refund.platform_cd')
            ->join('LEFT JOIN tb_pur_payment_audit ON tb_pur_payment_audit.id = tb_op_order_refund.payment_audit_id')
            ->where($where)
            ->order('tb_op_order_refund.id desc')
            ->group('tb_op_order.ORDER_ID,tb_op_order.PLAT_CD')
            ->limit(50)
            ->select();
        //var_dump($res_arr);exit;
        Logs($res_arr, 'res_change_order_after_sale_status');
        if ($res_arr) {
            $requre_arr = $data = [];
            foreach ($res_arr as $item) {
                $api_res = $this->changeOrderAfterSaleStatus($item);
                $item['res'] = $api_res;
                $requre_arr[] = $api_result = json_decode($api_res,true);
                if (200 == $api_result['code'] || 2000 == $api_result['code']) {
                    $success[] = $item;
                } else {
                    $item['msg'] = 'erp退款处理通知接口请求失败' . $api_result['msg'];
                    $error[] = $item;
                }
                sleep(1);
            }
            //订单更新日志
            $order_log_m = new OrderLogModel();
            if (isset($success) && !empty($success)) {
                $msg = 'erp退款处理通知接口请求成功';
                $order_log_m->addAllLog($success, $msg);
            }
            //订单更新日志
            if (isset($error) && !empty($error)) {
                $msg = 'erp退款处理通知接口请求失败';
                $order_log_m = new OrderLogModel();
                $order_log_m->addAllLog($error, $msg);
            }
            Logs($requre_arr, 'requre_arr');
        } else {
            $requre_arr = 'null order';
        }
        return $requre_arr;
    }

    public $audit_status_map = [
        'N003170004' => 3,
        'N003170005' => 4,
        'N003170006' => 5,
        'N003170007' => 6,
        'N003170008' => 8,
    ];

    //同步订单售后状态
    public function changeOrderAfterSaleStatus($order_info){
        $request_data['order_sn'] = $order_info['ORDER_NO'];
        //审核状态 audit_status_cd 审核不通过 退款取消 退款成功
        $request_data['checked'] = in_array($order_info['audit_status_cd'], ['N003170005', 'N003170007', 'N003170008']) ? ($order_info['audit_status_cd'] == 'N003170008' ? 1 : 2) : 0;
        $request_data['erp_state'] = isset($this->audit_status_map[$order_info['audit_status_cd']]) ? $this->audit_status_map[$order_info['audit_status_cd']] : '';
        $request_data['admin_message'] = isset($order_info['confirmation_remark']) && !empty($order_info['confirmation_remark']) ? $order_info['confirmation_remark'] : $order_info['audit_opinion'];
        $request_data['timestamp'] = time();
        $request_data['sign'] = $this->makeSign($request_data);
        $api_url = SHOPNC_URL."/shop/index.php?act=erp_api&op=refundCheck";
        $data_json = json_encode($request_data);
        $response = HttpTool::Curl_post_json($api_url, $data_json);
        Logs([$api_url, $data_json, $response], 'refundCheck', 'changeOrderAfterSaleStatus');
        $data = [
            'topic' => 'crawler_log_n1p1',
            'msg' => [
                'orderId' => $order_info['ORDER_ID'],
                'logType' => 'crawler',
                'requestType' => 'GP新后台对接退款流程(未发货状态前)',
                'remark' => $order_info['ORDER_ID'],
                'requestContent' => $data_json,
                'responsesContent' => $response,
                'server' => '101.226.208.8',
                'url' => $api_url,
            ],
        ];
        $res = ApiModel::addMessageLog($data);
        return $response;
    }
}

