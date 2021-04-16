<?php
class BatchPackageImportCollectionModel extends PackageImportCollectionModel
{
    protected $images = [];
    protected $images_path = [];
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];

    // 按订单支付，用平台订单号 batchorderpay，按转账，用付款单号 batchtransfer, 用订单交易号 batchtradeno
    public $order_field = ['batchorderpay' => 'platform_order_no', 'batchtransfer' => 'payment_audit_no', 'batchtradeno' => 'trade_no'];
    public function import($mode = '') 
    {
        try {
            //解压
            $content = $this->unPack();
        } catch (\Exception $e) {
           $this->setError($e->getMessage(),'3003');
           return false;
        }

        if (!$this->checkUnzipFormat($content)) {
            //判断解压后的格式
            return false;
        }
        $excel_path = $this->getUnPackExcelPath($content);//获取解压后的excel路径

        if(!$excel_path) {
            $this->setError('未找到excel文件',3002);
            return false;
        }

        $this->images = $this->getUnPackVoucher($content);//获取解压后的凭证文件
        $this->loadExcel($this->unpack_path.$excel_path);
        try {
            //获取标题
            $this->getFirstCellData();
            $this->getTitle($mode);
            //数据加载
            $this->getData();
            //数据验证
            $this->processData();
            // 校验是否有多余没有对应上的图片
            $this->checkImages();
        } catch (\Exception $e) {
            $this->setError('获取Excel表格数据失败','3003');
            return false;
        }

        if ($this->errorinfo) {
            foreach ($this->errorinfo[0] as $k => $v) {
                $this->setError($k.':'.$v, '3003');
                return false;
            }
        } else {
            return $this->writeData($mode);
        }
    }


    // 校验是否有没有对应上的图片素材
    public function checkImages()
    {
        $images_arr = $this->images;
        if (!$images_arr) {
            $this->setError(L('请上传凭证文件库'), '3001');
            return false;
        }
        $rowData = $this->getRowData();
        unset($rowData['1']); unset($rowData['2']);
        foreach ($images_arr as $key => $value) {
            // 判断图片是否在第一列excel里，如果没有，则表明该凭证名称有问题，需要重新处理
            foreach ($rowData as $rkey => $rvalue) {
                $is_match = false;
                if (strpos($value, (string)$rvalue) === 0) {
                    $is_match = true;
                    break;
                }
            }
            if (!$is_match) {
                $this->setError(L("该图片名称{$value}在excel中没有找到对应的付款单号（或平台订单号）"), '3011');
                return false;
            }
        }
    }
    /**数据更新
     * @return bool
     * @throws Exception
     */
    public function writeData($mode = '') 
    {
        try {
            $db_data     = [];
            $model       = M('pur_payment_audit', 'tb_');
            $primary_key = $this->order_field[$mode];
            $currency    = array_flip(BaseModel::getCurrency());
            $request_data_pur = array();
            $request_data_refund = array();
            $model->startTrans();
            foreach ($this->data as $key => $value) {

                if ($key < 3) {
                    continue;
                }
                if ($value['B']['db_field'] != 'payment_account_id') {
                    $model->rollback();
                    $this->setError(L('批量核销模板错误！请重新下载核销模板。'), '3007');
                    return false;
                }
                if ($value['C']['db_field'] != 'fund_allocation_contract_no') {
                    $model->rollback();
                    $this->setError(L('请下载最新模板进行导入。'), '3007');
                    return false;
                }
                $add = [];
                $is_same = true;
                $fund_allocation_contract_nos = [];
                foreach ($value as $v) {
                    if (!$v['db_field']) {
                        continue;
                    }
                    // 校验数据表中是否有该条记录
                    if ($v['db_field'] == $primary_key) {
                        if ($primary_key == 'trade_no') {
                            $where = [
                                $primary_key => $v['value'],
                                'status' => ['neq', TbPurPaymentAuditModel::$status_deleted],
                                'payment_way_cd' => TbPurPaymentAuditModel::$way_trade_no
                            ];
                            $res_info = $model->where($where)->select();
                            if (count($res_info) > 1) {
                                $model->rollback();
                                $this->setError(L('交易号'.$v['value'].'，对应多个付款单，识别失败'), '3007');
                                return false;
                            }
                            $res_info = $res_info[0];
                        } else {
                            $res_info = $model->where([$primary_key => $v['value'], 'status'=>['neq', TbPurPaymentAuditModel::$status_deleted]])->find();
                        }
                        if (!$res_info) {
                            $model->rollback();
                            $this->setError(L('付款单号（或平台订单号或交易号）:' . $v['value'] . '不存在'), '3007');
                            return false;
                        }
                        if ($res_info['status'] != TbPurPaymentAuditModel::$status_no_payment) {
                            $model->rollback();
                            $this->setError(L('付款单号（或平台订单号或交易号）:' . $v['value'] . '状态不是待付款，禁止导入'), '3007');
                            return false;
                        }
                        if ($res_info['status'] == TbPurPaymentAuditModel::$status_finished) {
                            $model->rollback();
                            $this->setError(L('付款单号（或平台订单号或交易号）:' . $v['value'] . '状态已完成，禁止导入'), '3007');
                            return false;
                        }
                    }
                    // 时间格式处理
                    if ($v['db_field'] == 'billing_date') {
                        $billing_date = ($v['value'] - 25569) * 24 * 3600;
                        $date         = excelTime($v['value']);
                        $check_date   = date('Y-m-d', $billing_date);
                        if ($check_date != $date) {
                            $model->rollback();
                            $this->setError(L('日期格式错误'), 3009);
                            return false;
                        }

                        $add[$v['db_field']] = date('Y-m-d', $billing_date);
                    }

                    // 币种校验
                    if ($v['db_field'] == 'billing_currency_cd') {
                        if (!$currency[$v['value']]) {
                            $model->rollback();
                            $this->setError(L('未知币种:' . $v['value']), '3005');
                            return false;
                        }
                        $add[$v['db_field']] = $currency[$v['value']];
                    }

                    // 付款账户id
                    if ($v['db_field'] == 'payment_account_id') {
                        $paymentAccount = $model->table('tb_fin_account_bank ab')
                            ->field('ab.company_code,ab.account_class_cd,ab.account_bank,ab.supplier_id,cc.CD,cc.CD_VAL,css.SP_NAME')
                            ->join('left join tb_ms_cmn_cd cc on cc.CD = ab.company_code')
                            ->join('left join tb_crm_sp_supplier css on css.ID = ab.supplier_id')
                            ->where(['ab.id' => $v['value']])->find();
                        if (!$paymentAccount) {
                            $model->rollback();
                            $this->setError(L('付款账户id不存在:' . $v['value']), '3005');
                            return false;
                        }
                        if ($paymentAccount['account_class_cd'] == 'N003510002') {
                            $paymentAccount['company_code'] = $paymentAccount['supplier_id'];
                        }
                        //批量核销表格上传的付款账户ID的我方公司与付款单的我方公司不一致时，需要查询资金调配合同
                        if ($paymentAccount['company_code'] != $res_info['our_company_cd']) {
                            $is_same = false;
                            //需要查询付款单我方公司与付款账户我方公司的资金调配合同
                            $param = ['PAY_COMPANY_CD' => $paymentAccount['company_code'], 'CON_COMPANY_CD' => $res_info['our_company_cd']];
                            $fund_allocation_contract_nos = array_column(CommonDataModel::fundAllocationContract($param), 'CON_NO');
                            if (empty($fund_allocation_contract_nos)) {
                                $model->rollback();
                                $this->setError(L('付款单:' . $res_info['payment_audit_no'] . '我方公司与付款账户ID：' . $v['value'] . '我方公司不同，未查询到资金调配合同。请先完善相关合同。'), '3005');
                                return false;
                            }
                        }
                        $add[$v['db_field']] = $v['value'];
                        $add['payment_our_bank_account'] = $paymentAccount['account_bank'];//付款银行账号
                        $add['company_code'] = $paymentAccount['company_code'];
                    }

                    // 资金调配合同编号 批量核销表格上传的付款账户ID的我方公司与付款单的我方公司不一致时 校验资金调配合同 否则忽略资金调配合同
                    if (!$is_same && $v['db_field'] == 'fund_allocation_contract_no') {
                        //付款单我方公司与付款账户我方公司不同：资金调配合同必填
                        if (empty($v['value'])) {
                            $model->rollback();
                            $this->setError(L('付款单我方公司与付款账户我方公司不同：资金调配合同必填。'), '3007');
                            return false;
                        }
                        //【资金调配合同】不属于两个我方公司
                        if (!in_array($v['value'], $fund_allocation_contract_nos)) {
                            $model->rollback();
                            $this->setError(L('资金调配合同' . $v['value'] . '不属于付款单:' . $res_info['payment_audit_no'] . '我方公司与付款账户ID：' . $value['B']['value'] . '我方公司'), '3007');
                            return false;
                        }
                        $add[$v['db_field']] = $v['value'];
                    }

                    // 金额校验
                    if ($v['db_field'] == 'billing_fee' || $v['db_field'] == 'billing_amount') {
                        if (!is_numeric($v['value']) || $v['value'] < 0) {
                            $model->rollback();
                            $this->setError(L('金额不能小于0'), '3008');
                            return false;
                        }
                        $add[$v['db_field']] = $v['value'];
                    }


                    if (!isset($add[$v['db_field']]) && $v['db_field'] != 'fund_allocation_contract_no') {
                        $add[$v['db_field']] = $v['value'];
                    }
                }

                $billing_voucher = $this->matchVoucher($key, $add[$primary_key]);
                if (!$billing_voucher) {
                    return false;
                }
                if ($res_info['source_cd'] === TbPurPaymentAuditModel::$source_b2c_payable){
                    $request_data_refund[] = [
                        'billing_amount'           => $add['billing_amount'],
                        'billing_fee'              => $add['billing_fee'],
                        'billing_voucher'          => $billing_voucher,
                        'billing_date'             => $add['billing_date'],
                        'confirmation_remark'      => $add['confirmation_remark'],
                        'billing_currency_cd'      => $add['billing_currency_cd'],
                        'id'                       => $res_info['id'],
                        'payment_our_bank_account' => $add['payment_our_bank_account'],
                        'payment_account_id'       => $add['payment_account_id'],
                        'fund_allocation_contract_no' => $add['fund_allocation_contract_no'],
                        'pay_com_cd'               => $add['company_code'],
                    ];
                } else if ($res_info['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                    // 一般付款
                    $request_data_general[] = [
                        'billing_amount'           => $add['billing_amount'],
                        'billing_fee'              => $add['billing_fee'],
                        'billing_voucher'          => $billing_voucher,
                        'billing_date'             => $add['billing_date'],
                        'confirmation_remark'      => $add['confirmation_remark'],
                        'billing_currency_cd'      => $add['billing_currency_cd'],
                        'id'                       => $res_info['id'],
                        'payment_our_bank_account' => $add['payment_our_bank_account'],
                        'payment_account_id'       => $add['payment_account_id'],
                        'fund_allocation_contract_no' => $add['fund_allocation_contract_no'],
                        'pay_com_cd'               => $add['company_code'],

                    ];
                }else{
                    $request_data_pur[] = [
                        'billing_amount'           => $add['billing_amount'],
                        'billing_fee'              => $add['billing_fee'],
                        'billing_voucher'          => $billing_voucher,
                        'billing_date'             => $add['billing_date'],
                        'confirmation_remark'      => $add['confirmation_remark'],
                        'billing_currency_cd'      => $add['billing_currency_cd'],
                        'id'                       => $res_info['id'],
                        'payment_our_bank_account' => $add['payment_our_bank_account'],
                        'payment_account_id'       => $add['payment_account_id'],
                        'fund_allocation_contract_no' => $add['fund_allocation_contract_no'],
                        'pay_com_cd'               => $add['company_code'],

                    ];
                }
//                $add['billing_voucher'] = $billing_voucher;
//                $add['updated_at']      = $this->getTime();
//                $add['updated_by']      = $_SESSION['m_loginname'];
//                $db_data                = ZUtils::filterBlank($add);
//                if (false === $model->where([$primary_key => $add[$primary_key]])->save($db_data)) {
//                    $this->setError('批量核销失败', '3003');
//                    $db_data['base_info'] = '模式是' . $mode . '，更新ID字段是' . $primary_key . '，值为【 ' . $add[$primary_key] . '】';
//                    Log(json_encode($db_data), '付款单号批量核销失败', __CLASS__);
//                    $model->rollback();
//                    return false;
//                }
            }
            if (empty($request_data_pur) && empty($request_data_refund) && empty($request_data_general)){
                throw new Exception("EXCEL内容为空");
            }
            // 一般付款
            if(!empty($request_data_general)){
                (new GeneralPaymentService())->batchPaymentSubmit($request_data_general);
            }
            // 采购
            if(!empty($request_data_pur)){
                (new PurPaymentService())->batchPaymentSubmit($request_data_pur);
            }
            // B2c退款
            if(!empty($request_data_refund)){
                (new B2CPaymentService())->batchPaymentSubmit($request_data_refund);
            }
            $model->commit();
            return true;
        } catch (\Exception $e) {
            $model->rollback();
            $this->setError($e->getMessage(),'3003');
            return false;
        }
    }

    /**
     * 加载EXCEL，生成excel对象
     *
     */
    public function loadExcel($excel_path)
    {
        $this->excel = new PackageExcelOperationModel($excel_path);
    }

    public function fieldMapping($mode = null)
    {
        $rules =  [
            'batchorderpay' => [
                'platform_order_no' => ['field_name' => '平台订单号', 'required' => true],
                'payment_account_id' => ['field_name' => '付款账户ID', 'required' => true],
                'fund_allocation_contract_no' => ['field_name' => '资金调配合同编号', 'required' => false],
                'billing_currency_cd' => ['field_name' => '扣款币种', 'required' => true],
                'billing_amount' => ['field_name' => '扣款金额', 'required' => true],
                'billing_fee' => ['field_name' => '扣款手续费', 'required' => true],
                'billing_date' => ['field_name' => '出账日期', 'required' => true],
                'confirmation_remark' => ['field_name' => '备注', 'required' => false],
            ],
            'batchtransfer' => [
                'payment_audit_no' => ['field_name' => '付款单号', 'required' => true],
                'payment_account_id' => ['field_name' => '付款账户ID', 'required' => true],
                'fund_allocation_contract_no' => ['field_name' => '资金调配合同编号', 'required' => false],
                'billing_currency_cd' => ['field_name' => '扣款币种', 'required' => true],
                'billing_amount' => ['field_name' => '扣款金额', 'required' => true],
                'billing_fee' => ['field_name' => '扣款手续费', 'required' => true],
                'billing_date' => ['field_name' => '出账日期', 'required' => true],
                'confirmation_remark' => ['field_name' => '备注', 'required' => false],
            ],
            'batchtradeno' => [
                'trade_no' => ['field_name' => '交易号', 'required' => true],
                'payment_account_id' => ['field_name' => '付款账户ID', 'required' => true],
                'fund_allocation_contract_no' => ['field_name' => '资金调配合同编号', 'required' => false],
                'billing_currency_cd' => ['field_name' => '扣款币种', 'required' => true],
                'billing_amount' => ['field_name' => '扣款金额', 'required' => true],
                'billing_fee' => ['field_name' => '扣款手续费', 'required' => true],
                'billing_date' => ['field_name' => '出账日期', 'required' => true],
                'confirmation_remark' => ['field_name' => '备注', 'required' => false],
            ],
        ];
        return $rules[$mode];
    }

    public function getTitle($mode = null)
    {
        $fields = $this->fieldMapping($mode);
        foreach ($this->firstCellRowData as $key => $value) {
            foreach ($fields as $k => $v) {
                if ($v ['field_name'] == $value) {
                    $temp [$key] ['db_field'] = $k;
                    $temp [$key] ['required'] = $v['required'];
                    $temp [$key] ['en_name'] = $value;
                }
            }
        }
        $this->title = $temp;
    }

    public function getUnPackExcelPath($content) {
        foreach ($content as $key => $value) {
            if ($value === -1) {
                continue;
            }
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['xlsx', 'xls'])) {
                return $key;
            }
        }
        return null;
    }

    /**获取压缩包里的凭证文件
     * @param $content
     * @return array
     */
    public function getUnPackVoucher($content) {
        $images = [];
        foreach ($content as $key => $value) {
            if ($value === -1) {
                continue;
            }
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['jpeg', 'jpg', 'png', 'pdf'])) {
                $this->images_path[] = $this->unpack_path.$key;
                $images[] = array_pop(explode('/', $key));
            }
        }
        return $images;
    }

    /**获取excel表关联的凭证文件
     * @param $order_id 付款单号/平台订单号
     * @param $line 行号
     * @return bool|string
     */
    private function matchVoucher($line, $order_id) {
        if (empty($this->images)) {
            $this->setError(L('请上传凭证文件库'), '3001');
            return false;
        }
        $image_arr = [];
        foreach ($this->images as $key => $image) {
            if (strpos($image, (string)$order_id) === 0) {
                $image_name = uniqid('receipt_', true) . '.jpg';
                $copy_res = copy($this->images_path[$key], ATTACHMENT_DIR_IMG . $image_name);
                if (!$copy_res) {
                    $this->setError(L('凭证文件复制失败'), '3002');
                    return false;
                }
                $image_arr[] = ['original_name' => $image, 'save_name' => $image_name];
            }
        }
        if (empty($image_arr)) {
            $this->setError(L('第'. $line. '行，付款单号（或平台订单号）为' . $order_id . '，缺失对应的文件，请上传凭证文件，或者检查凭证文件格式是否正确，凭证文件格式要求请查看导入说明'), '3002');
            return false;
        }
        return json_encode($image_arr);
    }

    private function checkUnzipFormat($content) {
        if (empty($content) || !is_array($content)) {
            $this->setError('未找到录入数据', '3001');
            return false;
        }

        $excel_count = 0;
        $image_count = 0;
        foreach ($content as $key => $value) {
            if (!$value) {
                continue;
            }
            //判断excel个数是否为1
            $file_arr = explode('.', $key);
            if (empty($file_arr)) {
                continue;
            }
            $suffix = array_pop($file_arr);
            if (in_array($suffix, ['xlsx', 'xls'])) {
                $excel_count++;
            } else if (in_array($suffix, ['png', 'jpg', 'jpeg', 'gif'])) {
                $image_count++;
            }
        }

        if ($excel_count != 1) {
            $this->setError('压缩文件只能包含一个excel文件', '3001');
            return false;
        }
        if ($image_count == 0) {
            $this->setError('压缩文件里未找到凭证图片', '3001');
            return false;
        }
        return true;
    }

    public function setError($msg, $code) {
        $this->error_info = [
            'msg' => $msg,
            'code' => $code,
            'data' => [],
        ];
    }

}