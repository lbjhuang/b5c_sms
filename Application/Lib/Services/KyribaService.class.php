<?php

/**
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/5/17
 * Time: 10:58
 */
class KyribaService
{

    public $currency_map;//币种映射
    private $mail_host;
    private $mail_login_name;
    private $mail_login_password;
    private $mail_save_path;
    private $mail_charset;
    private $mail;

    private $error_info = [];
    private $payment_info;

    public $payment_audit_table;
    public $payment_audit_log_table;

    private static $status_map = [];//kyriba和erp付款状态映射关系

    private $model;
    const KYRIBA_USER_ID = 9999999;
    const KYRIBA_WORKING_CSV_DIR = 'working_csv';
    const KYRIBA_RECYCLE_CSV_DIR = 'recycle_csv';

    const TRANSFER_WAIT_PAY = 'N001940200'; // 待转账
    const TRANSFER_WAIT_REC = 'N001940300'; // 待收款
    const TRANSFER_SUCCESS = 'N001940400'; // 收款完成
    const TRANSFER_DELETE  = 'N001940502'; // 已删除
    const TRANSFER_WAIT_ACCOUNTING  = 'N001940501'; // 待会计审核

    public function __construct()
    {
        self::$status_map = [
            'Registered' => TbPurPaymentAuditModel::$status_no_billing,//待出账
            'Executed' => TbPurPaymentAuditModel::$status_finished,//完成
            'To be deleted' => TbPurPaymentAuditModel::$status_kyriba_not_pass,//Kyriba审核未通过
            'Rejected' => TbPurPaymentAuditModel::$status_payment_failed,//付款失败
        ];

        $this->mail_host = '{smtp.exmail.qq.com}';
        $this->mail_login_name = C('email_address');
        $this->mail_login_password = C('email_password');
        $this->mail_save_path = ATTACHMENT_DIR_EXCEL;
        $this->mail_charset = 'UTF-8';

        $this->payment_audit_table = M('payment_audit', 'tb_pur_');
        $this->payment_audit_log_table = M('payment_audit_log', 'tb_pur_');
        $this->model = new Model();
    }

    //推送银行需要的xml格式至ftp
    public function putXmlToFtp($data, $source_cd)
    {
        $data = ZUtils::filterBlank($data);
        Logs($data, __FUNCTION__, 'kyriba');
        $this->validatePutData($data);
        $this->validateStringLength($data);
        $data = CodeModel::autoCodeOneVal($data, ['trade_type_cd', 'payment_currency', 'collection_currency']);
        //转换银行规定xml格式的数组
        $xml_array = [
            'CstmrCdtTrfInitn' => [
                'PmtInf' => [
                    'PmtInfId' => '',
                    'ReqdExctnDt' => date('Y-m-d'),
                    'DbtrAcct' => [
                        'Id' => [
                            'Othr' => [
                                'Id' => $data['account_bank'] . $data['payment_currency_val'],
                            ],
                        ],
                    ],
                    'UltmtDbtr' => [
                        'Nm' => '',
                    ],
                    'CdtTrfTxInf' => [
                        'PmtId' => [
                            'EndToEndId' => $data['payment_audit_no'],
                        ],
                        'Cdtr' => [
                            'Nm' => $data['supplier_collection_account'],
                            'PstlAdr' => [
                                'StrtNm' => '',
                                'TwnNm' => '',
                                'PstCd' => '',
                                'CtrySubDvsn' => '',
                                'Ctry' => $data['country_short_name'],
                            ],
                            'Id' => [
                                'OrgId' => [
                                    'Othr' => [
                                        'Id' => '',
                                    ],
                                ],
                                'PrvtId' => [
                                    'Othr' => [
                                        'Id' => '',
                                        'SchmeNm' => [
                                            'Cd' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CdtrAgt' => [
                            'FinInstnId' => [
                                'Nm' => $data['supplier_opening_bank'],
                                'BIC' => $data['supplier_swift_code'],
                                'Othr' => [
                                    'Id' => $data['bank_settlement_code'],
                                    'SchmeNm' => [
                                        'Cd' => '',
                                    ],
                                ],
                                'PstlAdr' => [
                                    'StrtNm' => '',
                                    'TwnNm' => '',
                                    'PstCd' => '',
                                    'CtrySubDvsn' => '',
                                    'Ctry' => $data['bank_country_short_name'],
                                ],
                            ],
                            'BrnchId' => [
                                'Nm' => '',
                            ],
                        ],
                        'IntrmyAgt1' => [
                            'FinInstnId' => [
                                'Nm' => '',
                                'BIC' => '',
                                'Othr' => [
                                    'Id' => '',
                                    'SchmeNm' => [
                                        'Cd' => '',
                                    ],
                                ],
                                'PstlAdr' => [
                                    'Ctry' => '',
                                ],
                            ],
                        ],
                        'IntrmyAgt1Acct' => [
                            'Id' => [
                                'Othr' => [
                                    'Id' => '',
                                ],
                            ],
                        ],
                        'CdtrAcct' => [
                            'Id' => [
                                'Othr' => [
                                    'Id' => $data['supplier_card_number'],
                                    'SchmeNm' => [
                                        'Cd' => '2',
                                    ],
                                ],
                            ],
                            'Ccy' => $data['collection_currency_val'],
                            'Tp' => [
                                'Cd' => '',
                            ],
                        ],
                        'Amt' => [
                            'InstdAmt' => $data['payable_amount_after'],
                            'EqvtAmt' => [
                                'Amt' => '',
                                'CcyOfTrf' => $data['payment_currency_val'],
                            ],
                        ],
                        'XchgRateInf' => [
                            'XchgRate' => '',
                            'RateTp' => '',
                            'CtrctId' => '',
                        ],
                        'Purp' => [
                            'Cd' => '',
                        ],
                        'ChrgBr' => TbPurPaymentAuditModel::$commission_map[$data['commission_type']] ?: '',
                        'RltdRmtInf' => [
                            'RmtLctnMtd' => '',
                            'RmtLctnElctrncAdr' => '',
                        ],
                        'RgltryRptg' => [
                            'Dtls' => [
                                'Cd' => '',
                                'Tp' => '',
                                'Ctry' => '',
                                'Inf' => '',
                            ],
                            'DbtCdtRptgInd' => '',
                            'Authrty' => [
                                'Nm' => '',
                                'Ctry' => '',
                            ],
                        ],
                        'CustomAttributes' => [
                            'TrxnCode' => $data['trade_type_cd_val'],
                            'TrfRmtncIdntfr1' => '',
                            'TrfRmtncIdntfr2' => '',
                            'Cdtr' => [
                                'PstlAdr' => [
                                    'StrtNm2' => '',
                                ],
                                'NonResFlg' => '',
                                'Contact' => [
                                    'Nm' => '',
                                    'Dpt' => '',
                                    'Phn' => '',
                                    'Fax' => '',
                                    'Email' => '',
                                ],
                            ],
                            'CdtrAgt' => [
                                'FinInstnId' => [
                                    'PstlAdr' => [
                                        'StrtNm2' => '',
                                    ],
                                ],
                            ],
                            'XchgRateInf' => [
                                'CcyBuyFlg' => '',
                                'CcyBuyDt' => '',
                            ],
                            'CdtTrfBdgtCd' => '',
                            'CdtrRmndr' => [
                                'RmndrFlg' => '',
                            ],
                            'CdtrAgtRmndr' => [
                                'RmndrFlg' => '',
                                'RmndrMode' => '',
                                'RmndrAdr' => '',
                            ],
                            'ExclsvPmt' => '',
                            'ExctnExpdts' => '',
                            'ExctnRtgs' => '',
                            'FreeText1' => '',
                            'FreeText2' => '',
                            'FreeText3' => '',
                            'Invoice' => '',
                            'RgltryRptg' => [
                                'Dtls' => [
                                    'Inf2' => '',
                                ],
                            ],
                            'ErpAprvr' => '',
                            'PmtRsn2' => '',
                            'PmtRsn3' => '',
                            'PmtRsn4' => '',
                        ],
                        'RmtInf' => [
                            'Ustrd' => $data['confirmation_remark'],
                            'Strd' => [
                                'RfrdDocInf' => [
                                    'Tp' => [
                                        'CdOrPrtry' => [
                                            'Cd' => '',
                                        ],
                                    ],
                                    'Nb' => '',
                                    'RltdDt' => '',
                                ],
                                'RfrdDocAmt' => [
                                    'DuePyblAmt' => '',
                                    'RmtdAmt' => '',
                                ],
                                'CdtrRefInf' => [
                                    'Ref' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Document></Document>');

        arrayToXml($xml_array, $xml_data);
        $local_save_path = C('ftp_config')['local_save_path'];
        switch ($source_cd) {
            case TbPurPaymentAuditModel::$source_payable:
                //采购应付
                $file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY.null.null.xml";
                $encrypt_file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY.null.null.asc";//加密过的文件
                break;
            case TbPurPaymentAuditModel::$source_transfer_payable:
                //转账换汇
                $file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_T.null.null.xml";
                $encrypt_file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_T.null.null.asc";//加密过的文件
                break;
            case TbPurPaymentAuditModel::$source_transfer_payable_indirect:
                //转账换汇 关联交易（间接）
                $file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_R.null.null.xml";
                $encrypt_file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_R.null.null.asc";//加密过的文件
                break;
            case TbPurPaymentAuditModel::$source_general_payable:
                //一般付款
                if ($data['main_type'] == '一般付款') {
                    $file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY.null.null.xml";
                    $encrypt_file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY.null.null.asc";//加密过的文件
                } else if ($data['main_type'] == 'Kyriba维护收款方账户付款') {
                    $file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_R.null.null.xml";
                    $encrypt_file = "GSHOPPER.NC4.IMPORT.{$data['payment_audit_no']}.PY_TRANSFER.Z_IMP_PY_R.null.null.asc";//加密过的文件
                } else {
                    @SentinelModel::addAbnormal('kyriba：未知对接类型', $data['main_type'], [$data], 'kyriba_notice');
                    throw new \Exception(L('未知对接类型'));
                }
                break;
            default:
                @SentinelModel::addAbnormal('kyriba：未知付款单来源', $source_cd, [$data], 'kyriba_notice');
                throw new \Exception(L('未知付款单来源'));
                break;
        }
        $save_res = $xml_data->asXML($local_save_path . $file);
        if (!$save_res) {
            @SentinelModel::addAbnormal('kyriba：生成xml文件失败', $file, [$xml_array], 'kyriba_notice');
            throw new \Exception(L('生成xml文件失败'));
        }
        //加密
        $xml_content = file_get_contents($local_save_path . $file);
        try {
            $xml_encrypt_content = (new Gpg())->encrypt($xml_content);
        } catch (Exception $exception) {
            @SentinelModel::addAbnormal(__FUNCTION__. ' kyriba pgp 加密失败', $exception->getMessage(), [$xml_content],'kyriba_notice');
        }
        if (empty($xml_encrypt_content)) {
            @SentinelModel::addAbnormal('kyriba：加密xml文件失败', $file, [$xml_content], 'kyriba_notice');
            throw new \Exception(L('加密xml文件失败'));
        }
        if (!file_put_contents($local_save_path . $encrypt_file, $xml_encrypt_content)) {
            @SentinelModel::addAbnormal('kyriba：生成加密xml文件失败', $file, [$xml_content], 'kyriba_notice');
            throw new \Exception(L('生成加密xml文件失败'));
        }

        $upload_res = SftpModel::client()->put($encrypt_file);
        if (!$upload_res) {
            @SentinelModel::addAbnormal('kyriba：上传到ftp失败', $encrypt_file, [$encrypt_file], 'kyriba_notice');
            throw new \Exception(L('上传到ftp失败'));
        }
    }

    private function validatePutData($data)
    {
        if (trim($data['person_account']) == '员工个人账户') {
            if (empty($data['supplier_collection_account'])) throw new Exception(L('kyriba员工个人账户付款，员工号不能为空'));
        } else {
            if (empty($data['supplier_collection_account'])) throw new Exception(L('kyriba付款，收款账户名不能为空'));
            if (empty($data['country_short_name'])) throw new Exception(L('kyriba付款，收款人国家代码不能为空'));
            if (empty($data['supplier_opening_bank'])) throw new Exception(L('kyriba付款，收款账户开户行不能为空'));
//            if (empty($data['supplier_swift_code'])) throw new Exception(L('kyriba付款，收款银行SWIFT CODE不能为空'));
            if ($data['payment_channel_cd'] == 'N001000301' && $data['payment_way_cd'] == 'N003020001') { //支付渠道为银行 支付方式为转账
                //【该渠道收款账户名】【该渠道收款账号】都必填 新增验证逻辑 #10691 应付确认/付款申请提交增加校验
                //①【确认后-SWIFT CODE】和【确认后-收款银行本地结算代码】至少填一个，当然可以都填。
                //②【确认后-SWIFT CODE】如果填了，则必填8位或11位字符串。
                if (empty($data['supplier_swift_code']) && empty($data['bank_settlement_code'])) {
                    throw new Exception(L('【SWIFT CODE】和【收款银行本地结算代码】至少填一个'));
                }
                if (!empty($data['supplier_swift_code']) && !preg_match('/^.{8}$|^.{11}$/', $data['supplier_swift_code'])) {
                    throw new Exception(L('【SWIFT CODE】必填8位或11位字符串。'));
                };
            }
            //if (empty($data['bank_settlement_code'])) throw new Exception(L('kyriba付款，收款银行本地结算代码不能为空'));
            if (empty($data['bank_country_short_name'])) throw new Exception(L('kyriba付款，收款银行国家代码不能为空'));
            if (empty($data['supplier_card_number'])) throw new Exception(L('kyriba付款，收款银行账号不能为空'));
            if (empty($data['collection_currency'])) throw new Exception(L('kyriba付款，收款账号币种不能为空'));
        }
        if (empty($data['payable_date_after'])) throw new Exception(L('kyriba付款，付款日期不能为空'));
        if (empty($data['account_bank'])) throw new Exception(L('kyriba付款，付款账号不能为空'));
        if (empty($data['payment_audit_no'])) throw new Exception(L('kyriba付款，付款单号不能为空'));
        if (empty($data['payable_amount_after'])) throw new Exception(L('kyriba付款，支付金额不能为空'));
        if (empty($data['payment_currency'])) throw new Exception(L('kyriba付款，支付金额币种不能为空'));
        if (empty($data['trade_type_cd'])) throw new Exception(L('kyriba付款，交易类型不能为空'));
    }

    private function validateStringLength($data)
    {
        $error_str = '';
        $map = [
            'payable_date_after' => [10, '预计付款日期'],
            'account_bank' => [35, '付款银行账号'],
            'payment_audit_no' => [20, '付款单号'],
            'supplier_collection_account' => [100, '收款账户名'],
            'country_short_name' => [2, '收款人国家代码'],
            'supplier_opening_bank' => [100, '收款账户开户行'],
            'supplier_swift_code' => [11, '收款银行SWIFT CODE'],
            'bank_settlement_code' => [20, '收款银行本地结算代码'],
            'bank_country_short_name' => [2, '收款银行国家代码'],
            'supplier_card_number' => [35, '收款银行账号'],
            'collection_currency_val' => [3, '收款账号币种'],
            'payable_amount_after' => [15, '支付金额'],
            'payment_currency_val' => [3, '付款币种'],
            'trade_type_cd_val' => [4, '交易类型'],
            'confirmation_remark' => [140, '付款/出账确认备注']
        ];
        foreach ($map as $field => $value) {
            if (strLength($data[$field]) > $value[0]) {
                $error_str .= '字段' . $value[1] . '超过最大长度' . $value[0] . '字符，';
            }
        }
        if (!empty($error_str)) {
            $error_str .= '无法推送kyriba，请进行修改';
            throw new Exception(L($error_str));
        }
    }

    //kyriba回传邮件定时处理
    public function mailReceive($mails_ids = '')
    {
        try {
            $mailbox = new PhpImap\Mailbox(
                $this->mail_host,
                $this->mail_login_name,
                $this->mail_login_password,
                $this->mail_save_path,
                $this->mail_charset
            );
            if (empty($mails_ids)) {
                $date = date("Y-m-d");
                $mails_ids = $mailbox->searchMailbox('SINCE "' . $date . '"');
            } else {
                $mails_ids = explode(',', $mails_ids);//用于处理历史数据
            }
//            v($mails_ids);
            if (empty($mails_ids)) {
                return true;
            }
            $currency = (new TbMsCmnCdModel())->currency();
            $this->currency_map = array_column($currency, 'CD', 'CD_VAL');

            foreach ($mails_ids as $mail_id) {
                $this->mail = $mailbox->getMail((int)$mail_id, false);
                if (trim($this->mail->subject) != '付款交易反馈') {
                    continue;
                }
                if (!in_array(trim($this->mail->senderAddress), [C('email_address'), 'diamond.messenger@treasury-factory.com'])) {
                    continue;
                }
                if (!$this->mail->hasAttachments()) {
                    continue;
                }
                $flag = RedisModel::get_key($this->mail->senderAddress . $this->mail->date);
                if (!empty($flag)) {
                    Logs('redis lock', __FUNCTION__, 'kyriba');
                    continue;
                }
                foreach ($this->mail->getAttachments() as $attachment) {
                    $suffix = array_pop(explode('.', $attachment->name));
                    if (!in_array($suffix, ['xls', 'xlsx'])) {
                        Logs('not excel', __FUNCTION__, 'kyriba');
                        continue;
                    }
                    $content = $attachment->getContents();
                    $save_path = $this->mail_save_path . date('YmdHis') . $attachment->name;
                    if (!file_put_contents($save_path, $content)) {
                        @SentinelModel::addAbnormal('kyriba附件保存失败', $this->mail->date . '  ' . $attachment->name, [$attachment->name], 'kyriba_notice');
                        continue;
                    }
                    $this->readExcel($save_path);
                }
                if (!empty($this->error_info)) {
                    $msg = '';
                    foreach ($this->error_info as $error) {
                        $msg .= $error[0] . '，';
                    }
                    $msg = trim($msg, '，');
                    @SentinelModel::addAbnormal(
                        'kyriba邮件发送日期：' . $this->mail->date .
                        '，附件名称：' . $attachment->name .
                        '，出账失败付款单有',
                        $msg,
                        [$this->mail->date, $attachment->name, $this->error_info],
                        'kyriba_notice'
                    );
                    $this->error_info = [];
                } else {
                    //附件记录全部成功才不在读取
                    RedisModel::set_key($this->mail->senderAddress . $this->mail->date, 'true', null, 3600 * 24);
                }
            }
            return true;
        } catch (PhpImap\Exceptions\ConnectionException $ex) {
            @SentinelModel::addAbnormal('IMAP connection failed', $ex, [], 'kyriba_notice');
        }
    }

    public function mailReceiveTest($date = '')
    {
        try {
            if (empty($date)) {
                $date = date("Y-m-d");
            }
            $mailbox = new PhpImap\Mailbox(
                $this->mail_host,
                $this->mail_login_name,
                $this->mail_login_password,
                $this->mail_save_path,
                $this->mail_charset
            );
            $mails_ids = $mailbox->searchMailbox('SINCE "' . $date . '"');
            print_r(implode(',', $mails_ids));
            die;
            if (empty($mails_ids)) {
                return true;
            }

            foreach ($mails_ids as $mail_id) {
                $this->mail = $mailbox->getMail($mail_id, false);
                if (trim($this->mail->subject) != '付款交易反馈') {
                    continue;
                }
                if (!in_array(trim($this->mail->senderAddress), [C('email_address'), 'diamond.messenger@treasury-factory.com'])) {
                    continue;
                }
                if (!$this->mail->hasAttachments()) {
                    continue;
                }
                if (!empty($flag)) {
                    continue;
                }
                foreach ($this->mail->getAttachments() as $attachment) {
                    $suffix = array_pop(explode('.', $attachment->name));
                    if (!in_array($suffix, ['xls', 'xlsx'])) {
                        continue;
                    }
                    $content = $attachment->getContents();
                    $save_path = $this->mail_save_path . date('YmdHis') . $attachment->name;
                    if (!file_put_contents($save_path, $content)) {
                        @SentinelModel::addAbnormal('kyriba附件保存失败-test', $this->mail->date . '  ' . $attachment->name, [$attachment->name], 'kyriba_notice');
                        continue;
                    }
                }
            }
            v($mails_ids);
            return true;
        } catch (PhpImap\Exceptions\ConnectionException $ex) {
            @SentinelModel::addAbnormal('IMAP connection failed', $ex, [], 'kyriba_notice');
        }
    }

    public function readExcel($excel_path)
    {
        vendor("PHPExcel.PHPExcel");
        $input_file_type = PHPExcel_IOFactory::identify($excel_path);
        $obj_reader = PHPExcel_IOFactory::createReader($input_file_type);
        $obj_excel = $obj_reader->load($excel_path);
        $sheet = $obj_excel->getSheet(0);
        $max_row = $sheet->getHighestRow();
        for ($row = 7; $row <= $max_row; $row++) {
            $transaction_no = strtoupper(trim($sheet->getCellByColumnAndRow(0, $row)->getValue()));
            $billing_currency = strtoupper(trim($sheet->getCellByColumnAndRow(8, $row)->getValue()));
            $billing_currency_cd = $this->currency_map[$billing_currency];
            $billing_amount = trim($sheet->getCellByColumnAndRow(10, $row)->getValue());
            $payment_audit_no = trim($sheet->getCellByColumnAndRow(13, $row)->getValue());
            $status_name = $sheet->getCellByColumnAndRow(14, $row)->getValue();
            Logs([$payment_audit_no, $status_name], 'kyriba未转换状态数据：' . __FUNCTION__, 'kyriba');
            $status = self::$status_map[trim($status_name)];
            Logs([$payment_audit_no, $status], 'kyriba已转换状态数据：' . __FUNCTION__, 'kyriba');
            $billing_date = trim($sheet->getCellByColumnAndRow(12, $row)->getValue());

            $billing_data = [
                'transaction_no' => $transaction_no,
                'payment_audit_no' => $payment_audit_no,
                'billing_currency_cd' => $billing_currency_cd,
                'billing_amount' => $billing_amount,
                'billing_date' => $billing_date,
                'status' => $status,
            ];
            Logs($billing_data, 'kyriba 邮件反馈单据数据：' . __FUNCTION__, 'kyriba');
            if (!$this->billingHandle($billing_data)) {
                continue;
            }
        }
    }

    //出账处理
    private function billingHandle($billing_data)
    {
        if (empty($billing_data['payment_audit_no'])) {
            return false;
        }
        $this->payment_info = $this->payment_audit_table->where(['payment_audit_no' => $billing_data['payment_audit_no']])->find();
        if (empty($this->payment_info)) {
            return false;
        }
        if ($this->payment_info['is_direct_billing'] == 1) {
            //过滤在待kyriba接收状态确认出账的付款单
            return false;
        }
        if ($this->payment_info['pay_type'] != 1) {
            //排除执行测试环境的单子
            Logs($billing_data, '不是kyriba付款单：' . __FUNCTION__, 'kyriba');
            return false;
        }
        if (empty($billing_data['billing_currency_cd'])) {
            $this->error_info[] = ['付款单号：' . $billing_data['payment_audit_no'] . '，缺少或错误出账币种'];
            return false;
        }
        if (!is_numeric($billing_data['billing_amount'])) {
            $this->error_info[] = ['付款单号：' . $billing_data['payment_audit_no'] . '，出账金额格式错误'];
            return false;
        }
        if (empty($billing_data['status'])) {
            Logs($billing_data, 'kyriba付款单状态错误：' . __FUNCTION__, 'kyriba');
            return false;
        }
        $date = excelTime($billing_data['billing_date']);
        $billing_date = ($billing_data['billing_date'] - 25569) * 24 * 3600;
        $check_date = date('Y-m-d', $billing_date);
        if ($check_date != $date) {
            $this->error_info[] = ['付款单号：' . $billing_data['payment_audit_no'] . '，出账日期格式错误'];
            return false;
        }
        $billing_data['billing_date'] = $check_date;

        switch ($billing_data['status']) {
            case TbPurPaymentAuditModel::$status_no_billing:
                $res = $this->paymentSure($billing_data);
                break;
            case TbPurPaymentAuditModel::$status_finished:
                $res = $this->paymentFinished($billing_data);
                break;
            default:
                $res = $this->paymentFailed($billing_data);
                break;
        }
        if (!$res) {
            return false;
        }
        $status_name = TbPurPaymentAuditModel::$status_map[$billing_data['status']];
        $this->recordLog('同步kyriba回传邮件', $status_name, '');
        return true;
    }

    //付款单状态改成待出账
    private function paymentSure()
    {
        $this->payment_audit_table
            ->where(['id' => $this->payment_info['id']])
            ->save(['status' => TbPurPaymentAuditModel::$status_no_billing]);
        $status_name = TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_no_billing];
        $this->recordLog('kyriba接收成功', $status_name, '付款交易反馈同步成功');
    }

    //确认出账
    private function paymentFinished($billing_data)
    {
        try {
            if ($this->payment_info['status'] == TbPurPaymentAuditModel::$status_finished) {
                //已经处理过，不重复处理，也不报错
                return false;
            }
            if (!in_array($this->payment_info['status'], [TbPurPaymentAuditModel::$status_no_billing, TbPurPaymentAuditModel::$status_kyriba_wait_receive])) {
                return false;
            }
            $data = [
                'no_billing' => [],
                'already_billing' => [
                    'billing_amount' => $billing_data['billing_amount'],
                    'billing_fee' => 0,
                    'billing_voucher' => '',
                    'billing_date' => $billing_data['billing_date'],
                    'billing_currency_cd' => $billing_data['billing_currency_cd']
                ],
                'type' => 3,
                'payment_audit_id' => $this->payment_info['id'],
                'submit_type' => 1,
                'is_import' => 1,
                'is_kyriba' => 1,
                'commission_type' => $this->payment_info['commission_type'],
                'pay_com_cd' => $this->payment_info['pay_com_cd'],
                'trade_type_cd' => $this->payment_info['trade_type_cd']
            ];
            $model = new Model();
            $model->startTrans();
            switch ($this->payment_info['source_cd']) {
                case TbPurPaymentAuditModel::$source_transfer_payable:
                    //转账换汇来源
                    (new TransferPaymentService($model))->paymentSubmit($data);
                    break;
                case TbPurPaymentAuditModel::$source_general_payable:
                    //一般付款来源
                    (new GeneralPaymentService($model))->paymentSubmit($data);
                    break;
                case TbPurPaymentAuditModel::$source_payable:
                    //采购应付来源
                    (new PurPaymentService($model))->paymentSubmit($data);
                    break;
                default:
                    return false;
                    break;
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $msg = $exception->getMessage();
            $this->error_info[] = ['付款单号：' . $billing_data['payment_audit_no'] . '，' . $msg];
            return false;
        }
        return true;
    }

    private function paymentFailed($billing_data)
    {
        if ($this->payment_info['status'] == $billing_data['status']) {
            //已经处理过，不重复处理，也不报错
            return false;
        }
//        if ($this->payment_info['status'] != TbPurPaymentAuditModel::$status_no_billing) {
//            // $this->error_info[] = ['付款单号：'.$billing_data['payment_audit_no']. '，付款单状态不是待出账'];
//            //已经处理过，不重复处理，也不报错
//            return false;
//        }
        $res = $this->payment_audit_table->where(['payment_audit_no' => $billing_data['payment_audit_no']])->save(['status' => $billing_data['status']]);
        return $res;
    }

    //读取kriba回传信息
    public function sftpReceive()
    {
        list($download_success_file_path, $download_failed_file) = SftpModel::client()->downloadFile();
        if (!empty($download_failed_file)) {
            @SentinelModel::addAbnormal('下载sftp文件失败', '', $download_failed_file, 'kyriba_notice');
        }
        // $download_success_file_path[] = 'c:/img/test.csv';
        foreach ($download_success_file_path as $sftp_file => $save_file) {
            //pgp解密
            $content = file_get_contents($save_file);
            // $decrypt_content = $content;
            try {
                $decrypt_content = (new Gpg())->decrypt($content);
            } catch (Exception $exception) {
                @SentinelModel::addAbnormal(__FUNCTION__. ' kyriba pgp 解密失败', $exception->getMessage(), [$content],'kyriba_notice');
            }
            if (empty($decrypt_content)) {
                @SentinelModel::addAbnormal('kyriba回传文件解密失败', $save_file, [$save_file], 'kyriba_notice');
                continue;
            }
            $file_name = explode('.', $save_file)[0] . time() . '.csv';
            $csv_arr = csvStringToArray($decrypt_content, 30, ';');
            $fp = fopen($file_name, 'w');
            foreach ($csv_arr as $fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);
            if (!is_file($file_name)) {
                @SentinelModel::addAbnormal('kyriba回传文件保存到本地失败', $file_name, [$csv_arr], 'kyriba_notice');
                continue;
            }
            $handle = fopen($file_name, 'r');
            if ($handle !== false) {
                while ($data = fgetcsv($handle, 10000)) {
                    $this->receiveHandle($data);
//                    $content_arr[] = $data;
                }
            }
            //if (!SftpModel::client()->delete($sftp_file)) {
            //    @SentinelModel::addAbnormal('kyriba删除sftp文件失败', $sftp_file, [$sftp_file], 'kyriba_notice');
            //}
            fclose($handle);
//            unset($content_arr[0]);
//            $publish_res = $this->publisherReceiveContent($content_arr);
//            if ($publish_res) {
//                //加入了队列，删除sftp对应文件
//                if (!SftpModel::client()->delete($file)) {
//                    @SentinelModel::addAbnormal('kyriba删除sftp文件失败', $file, [$file],'kyriba_notice');
//                }
//            }
        }
        return true;
    }

    public function publisherReceiveContent($data)
    {
        $exchange_name = 'exchange_kyriba_receive';
        $queue_name = 'queue_kyriba_receive';
        $route_key_name = 'route_kyriba_receive';
        $service = new AmqpService();
        return $service->publisher($exchange_name, $queue_name, $route_key_name, $data);
    }

    public function consumeReceiveContent()
    {
        $exchange_name = 'exchange_kyriba_receive';
        $queue_name = 'queue_kyriba_receive';
        $route_key_name = 'route_kyriba_receive';
        $service = new AmqpService();
        return $service->consumer($exchange_name, $queue_name, $route_key_name);
    }

    //回单信息处理
    public function receiveHandle($data)
    {
        //解析csv内容
        $btc_code = trim($data[5]);
        $data_type = trim($data[10]);
        $direction = trim($data[12]);
        $reference_text = trim($data[25]);
        $complementary_information = trim($data[9]);
        if (strtoupper($btc_code) == 'INIT') {
            return true;
        }
        if (strtoupper($data_type) != 'TR') {
            return true;
        }
        if ($direction != 1) {
            return true;
        }
        $info = explode('/', $complementary_information);
        //$bank_reference_no = $info[array_search('REF', $info) + 1];//银行参考号
        // $bank_reference_no = trim($data[26]);//银行参考号
        $bank_reference_no = numToStr(trim($data[26]));//银行参考号
        $bank_payment_reason = $info[array_search('PY', $info) + 1];//付款原因
        $payment_audit_no = $reference_text;//付款单号
        if (empty($payment_audit_no)) {
            return true;
        }
        if (empty($bank_reference_no)) {
            return true;
        }

        $filed = [
            'pa.*',
            'cd.ETC2 as person_account',//员工个人账户
            'cd.ETC3 as main_type'//一般付款/Kyriba维护收款方账户付款等
        ];
        $payment_info = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->join('left join tb_ms_cmn_cd cd on cd.CD = gp.payment_type')
            ->where(['pa.payment_audit_no' => $payment_audit_no])
            ->find();
        if (empty($payment_info) || $payment_info['status'] != TbPurPaymentAuditModel::$status_finished) {
            Logs($data, __FUNCTION__ . '-付款单不存在或状态不对', 'kyriba');
            return true;
        }
        if ($payment_info['pay_type'] != 1) {
            return true;
        }
        if (!in_array($payment_info['source_cd'], [TbPurPaymentAuditModel::$source_payable, TbPurPaymentAuditModel::$source_general_payable])) {
            Logs($data, __FUNCTION__ . '-付款单来源错误', 'kyriba');
            return true;
        }
        if (!empty($payment_info['billing_voucher'])) {
            Logs($data, __FUNCTION__ . '-付款单已有出账凭证', 'kyriba');
            return true;
        }

        if ($payment_info['source_cd'] == TbPurPaymentAuditModel::$source_general_payable) {
            if (trim($payment_info['main_type']) == '不对接') {
                return true;
            }
        }
        //生成付款附件
        $pdf_file = $this->generatePaymentAttachment($payment_info, $bank_reference_no, $bank_payment_reason);
        if (empty($pdf_file)) {
            $billing_voucher = '';
            $transfer_voucher = '';
        } else {
            $billing_voucher = json_encode([['original_name' => $pdf_file, 'save_name' => $pdf_file]]);
            $transfer_voucher = json_encode([['name' => $pdf_file, 'savename' => $pdf_file]]);
        }

        $save_data = [
            'bank_reference_no' => $bank_reference_no,
            'bank_payment_reason' => $bank_payment_reason,
            'billing_voucher' => $billing_voucher
        ];
        $audit_res = $this->payment_audit_table
            ->where(['payment_audit_no' => $payment_audit_no])
            ->save($save_data);
        if (false === $audit_res) {
            @SentinelModel::addAbnormal('kyriba回单保存付款单失败', $payment_audit_no, [$save_data, $payment_audit_no], 'kyriba_notice');
            return false;
        }
        $save_data = [
            'bank_reference_no' => $bank_reference_no,
            'bank_payment_reason' => $bank_payment_reason,
            'transfer_voucher' => $transfer_voucher
        ];
        $transfer_res = M('fin_account_turnover', 'tb_')
            ->where(['transfer_no' => $payment_audit_no])
            ->save($save_data);
        if (false === $transfer_res) {
            @SentinelModel::addAbnormal('kyriba回单保存日记账失败', $payment_audit_no, [$save_data, $payment_audit_no], 'kyriba_notice');
            return false;
        }
        $status_name = TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_finished];
        $this->recordLog('同步kyriba银行对账单', $status_name, '');
        //发送邮件
        if ($payment_info['source_cd'] == TbPurPaymentAuditModel::$source_payable) {
            //采购应付
            $pur_info = (new PurRepository())->getOrderInfoByPaymentAuditIds($payment_info['id']);
            Logs($pur_info, __FUNCTION__, 'kyriba');
            $send_res = (new PurPaymentService())->sendPaidEmail($pur_info, A('CrontabHandle')->paid_email_content(APP_PATH . 'Tpl/Home/OrderDetail/paid_email.html'));
            return $send_res;
        } else {
            //一般付款
            $field = "pa.*, gp.payment_type";
            $general_info = $this->model->table('tb_pur_payment_audit pa')
                ->field($field)
                ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
                ->where(['pa.id' => $payment_info['id']])
                ->find();
            Logs($general_info, __FUNCTION__, 'kyriba');
            $send_res = (new GeneralPaymentService())->sendPaidEmail($general_info, A('CrontabHandle')->paid_email_content(APP_PATH . 'Tpl/Home/Finance/paid_general_email.html'));
            return $send_res;
        }
    }

    //生成付款附件
    private function generatePaymentAttachment($payment_info, $bank_reference_no, $bank_payment_reason)
    {
        $model = new Model();
        if ($payment_info['source_cd' == TbPurPaymentAuditModel::$source_payable]) {
            //采购应付
            $filed = [
                'pa.*',
                'ss.COMPANY_ADDR_INFO as supplier_reg_address',//供应商详细注册地址
                "CONCAT_WS('-', cs1.NAME, cs2.NAME, cs3.NAME) supplier_detail_address",//供应商详细地址
                "CONCAT_WS('-', ua1.zh_name, ua2.zh_name, ua3.zh_name) our_company_reg_address",//我方公司注册区域
                'cm.reg_address as our_company_reg_detail_address',//我方公司详细注册区域,
                '(pa.billing_amount + pa.billing_fee) as billing_total_amount'
            ];
            $row = $model->table('tb_pur_payment_audit pa')
                ->field($filed)
                ->join('left join tb_pur_payment pp on pa.id = pp.payment_audit_id')
                ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
                ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
                ->join('left join tb_crm_sp_supplier ss on od.supplier_new_id = ss.ID')
                ->join('left join tb_crm_site cs1 on ss.SP_ADDR1 = cs1.ID')
                ->join('left join tb_crm_site cs2 on ss.SP_ADDR3 = cs2.ID')
                ->join('left join tb_crm_site cs3 on ss.SP_ADDR4 = cs3.ID')
                ->join('left join tb_crm_company_management cm on cm.our_company_cd = pa.our_company_cd')
                ->join('left join tb_ms_user_area ua1 on ua1.id = cm.reg_country')
                ->join('left join tb_ms_user_area ua2 on ua2.id = cm.reg_province')
                ->join('left join tb_ms_user_area ua3 on ua3.id = cm.reg_city')
                ->where(['pa.id' => $payment_info['id']])
                ->find();
        } else {
            //一般付款
            $filed = [
                'pa.*',
                'ss.COMPANY_ADDR_INFO as supplier_reg_address',//供应商详细注册地址
                "CONCAT_WS('-', cs1.NAME, cs2.NAME, cs3.NAME) supplier_detail_address",//供应商详细地址
                "CONCAT_WS('-', ua1.zh_name, ua2.zh_name, ua3.zh_name) our_company_reg_address",//我方公司注册区域
                'cm.reg_address as our_company_reg_detail_address',//我方公司详细注册区域
                '(pa.billing_amount + pa.billing_fee) as billing_total_amount'
            ];
            $row = $model->table('tb_pur_payment_audit pa')
                ->field($filed)
                ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
                ->join('tb_crm_sp_supplier ss ON gp.supplier = ss.ID')
                ->join('left join tb_crm_site cs1 on ss.SP_ADDR1 = cs1.ID')
                ->join('left join tb_crm_site cs2 on ss.SP_ADDR3 = cs2.ID')
                ->join('left join tb_crm_site cs3 on ss.SP_ADDR4 = cs3.ID')
                ->join('left join tb_crm_company_management cm on cm.our_company_cd = pa.our_company_cd')
                ->join('left join tb_ms_user_area ua1 on ua1.id = cm.reg_country')
                ->join('left join tb_ms_user_area ua2 on ua2.id = cm.reg_province')
                ->join('left join tb_ms_user_area ua3 on ua3.id = cm.reg_city')
                ->where(['pa.id' => $payment_info['id']])
                ->find();
        }
        $row = CodeModel::autoCodeOneVal($row, ['our_company_cd', 'billing_currency_cd']);
        $row['billing_total_amount'] = number_format($row['billing_total_amount'], 2);
        $data = [
            'supplier_collection_account' => $row['supplier_collection_account'],
            'supplier_reg_address' => $row['supplier_reg_address'],
            'supplier_detail_address' => $row['supplier_detail_address'],
            'our_company_cd_val' => $row['our_company_cd_val'],
            'supplier_detail_address' => $row['supplier_detail_address'],
            'our_company_reg_detail_address' => $row['our_company_reg_detail_address'],
            'billing_date' => $row['billing_date'],
            'bank_reference_no' => $bank_reference_no,
            'payment_audit_no' => $row['payment_audit_no'],
            'billing_currency_cd_val' => $row['billing_currency_cd_val'],
            'billing_total_amount' => $row['billing_total_amount'],
            'supplier_card_number' => $row['supplier_card_number'],
            'bank_payment_reason' => $bank_payment_reason,
        ];
        $pdf_file_name = '';
        $res = ApiModel::generatePdfFile($data);
        if ($res['code'] != 200) {
            @SentinelModel::addAbnormal('kyriba生成付款凭证PDF失败', $payment_info['payment_audit_no'], [$data, $res], 'kyriba_notice');
        } else {
            //把凭证文件保存到本地
            $pdf_file_url = CMS_HOST . '/' . $res['path'];
            $pdf_content = file_get_contents($pdf_file_url);
//            $pdf_file_url = 'http://gscdn-stage.gshopper.com/cms/2020/03/691c308fe2b38ec9e6bbf2cb2d609cfd.jpeg';
//            $pdf_content = file_get_contents($pdf_file_url);
            $pdf_file_name = array_pop(explode('/', $pdf_file_url));
            if (!empty($pdf_content)) {
                $upload_res = file_put_contents(ATTACHMENT_DIR_IMG . $pdf_file_name, $pdf_content);
                if (!$upload_res) {
                    @SentinelModel::addAbnormal('kyriba保存付款凭证到本地失败', $payment_info['payment_audit_no'], [$upload_res, $pdf_file_url], 'kyriba_notice');
                }
            } else {
                @SentinelModel::addAbnormal('kyriba付款凭证PDF内容为空', $payment_info['payment_audit_no'], [$res, $payment_info], 'kyriba_notice');
            }
        }
        return $pdf_file_name;
    }

    //读取kyriba接收失败邮件
    public function receiveFailedMail()
    {

        try {
            $date = date("Y-m-d");
            $mailbox = new PhpImap\Mailbox(
                $this->mail_host,
                $this->mail_login_name,
                $this->mail_login_password,
                $this->mail_save_path,
                $this->mail_charset
            );
            $mails_ids = $mailbox->searchMailbox('SINCE "' . $date . '"');
            if (empty($mails_ids)) {
                return true;
            }
            foreach ($mails_ids as $mail_id) {
                $this->mail = $mailbox->getMail($mail_id, false);
                if (strtolower(trim($this->mail->subject)) != 'kyriba接收失败') {
                    continue;
                }
                if (!in_array(trim($this->mail->senderAddress), [C('email_address'), 'diamond.messenger@treasury-factory.com'])) {
                    continue;
                }
                if (!$this->mail->hasAttachments()) {
                    continue;
                }
                $flag = RedisModel::get_key($this->mail->senderAddress . $this->mail->date);
                if (!empty($flag)) {
                    continue;
                }
                foreach ($this->mail->getAttachments() as $attachment) {
                    $suffix = array_pop(explode('.', $attachment->name));
                    if (!in_array($suffix, ['csv'])) {
                        continue;
                    }
                    $content = $attachment->getContents();
                    $save_path = $this->mail_save_path . date('YmdHis') . $attachment->name;
                    if (!file_put_contents($save_path, $content)) {
                        @SentinelModel::addAbnormal('kyriba接收失败附件保存失败', $this->mail->date . '  ' . $attachment->name, [$attachment->name], 'kyriba_notice');
                        continue;
                    }
                    $this->readReceiveFailedCsv($save_path);
                }
                //7天内只读取一次
                RedisModel::set_key($this->mail->senderAddress . $this->mail->date, 'true', null, 3600 * 24 * 7);
            }
            return true;
        } catch (PhpImap\Exceptions\ConnectionException $ex) {
            @SentinelModel::addAbnormal('IMAP connection failed 2', $ex, [], 'kyriba_notice');
        }
    }

    //读取kyriba接收失败邮件附件内容
    public function readReceiveFailedCsv($csv_path)
    {
        $i = 0;
        $handle = fopen($csv_path, 'r');
        while (($data = fgetcsv($handle)) !== false) {
            $i++;
            if ($i < 4) {
                continue;
            }
            $payment_audit_no = trim(iconv('gb2312', 'utf-8', $data[19]));
            $error1 = trim(iconv('gb2312', 'utf-8', $data[22]));
            $error2 = trim(iconv('gb2312', 'utf-8', $data[23]));
            $error3 = trim(iconv('gb2312', 'utf-8', $data[24]));
            $where = ['payment_audit_no' => $payment_audit_no];
            $this->payment_info = $this->payment_audit_table->where($where)->find();
            if (empty($this->payment_info) || $this->payment_info['status'] != TbPurPaymentAuditModel::$status_kyriba_wait_receive) {
                continue;
            }
            $reason = '';
            if (!empty($error1)) {
                $reason = 'Error field:' . $error1 . ';';
            }
            if (!empty($error2)) {
                $reason .= 'Error description:' . $error2 . ';';
            }
            if (!empty($error3)) {
                $reason .= 'Original value:' . $error3 . ';';
            }
            if (!empty($reason)) {
                $reason = trim($reason, ';');
            }
            $this->payment_audit_table->where($where)->save([
                'status' => TbPurPaymentAuditModel::$status_kyriba_receive_failed,
                'receive_fail_reason' => $reason
            ]);
            $status_name = TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_kyriba_receive_failed];
            $this->recordLog('kyriba接收失败', $status_name, '同步kyriba接收失败邮件成功');
        }
        fclose($handle);
    }

    private function recordLog($operation_info, $status_name, $remark)
    {
        TbPurPaymentAuditLogModel::recordLog(
            $this->payment_info['id'],
            $operation_info,
            '',
            $status_name,
            $remark
        );
    }

    // kyriba 历史数据处理
    public function kyribaHistDataDeal()
    {
        // 获取csv文件内容，转化为待处理数组
        list($pay_operate_data, $transfer_pay_no_arr, $main_pay_transfer_no_arr, $receive_operate_data, $transfer_receiver_no_arr, $main_receive_transfer_no_arr) = $this->getKyHistData();

        // 将待处理数组划分为付款单数组以及收款单数组
        $this->dealKyHist($pay_operate_data, $transfer_pay_no_arr, $main_pay_transfer_no_arr, $receive_operate_data, $transfer_receiver_no_arr, $main_receive_transfer_no_arr);
        // 根据U列更新相关表的相关字段
        $response_data = DataModel::$success_return;
        return $response_data; 
    }

    public function dealKyHist($pay_operate_data, $transfer_pay_no_arr, $main_pay_transfer_no_arr, $receive_operate_data, $transfer_receiver_no_arr, $main_receive_transfer_no_arr)
    {

        // 收款
        $accTurnoverModel = M('fin_account_turnover', 'tb_');
        $accTransferModel = M('fin_account_transfer', 'tb_');
        $claimModel = M('fin_claim', 'tb_');
        if ($main_receive_transfer_no_arr) {
            foreach ($main_receive_transfer_no_arr as $value) {
                if ($value) {
                    $save = [];
                    $save['rec_actual_money'] = $receive_operate_data[$value]['account_amount'];
                    $res_trans = $accTransferModel->where(['transfer_no' => $value])->save($save);

                    $claim_save = [];
                    $claim_save['claim_amount'] = $receive_operate_data[$value]['account_amount'];
                    $res_claim = $claimModel->where(['order_no' => $value])->save($claim_save);

                    $turn_save = [];
                    $turn_save['amount_money'] = $receive_operate_data[$value]['account_amount'];
                    $turn_save['remitter_currency'] = valCd($receive_operate_data[$value]['account_currency_code']);
                    $turn_save['original_currency'] = $turn_save['remitter_currency'];
                    $turn_save['opp_company_name'] = $this->getOppositeAccountName($receive_operate_data[$value]['complementary_information']);
                    $turn_save['bank_reference_no'] = $receive_operate_data[$value]['statement_id'];
                    $turn_save['bank_rate'] = str_replace(',','.', $this->getItemValue($receive_operate_data[$value]['complementary_information'], 'ER'));
                    $res_turnover = $accTurnoverModel->where(['transfer_no' => $value])->save($turn_save);
                }
            }
        }

        // 付款
        if ($main_pay_transfer_no_arr) {
            foreach ($main_pay_transfer_no_arr as $value) {
                if ($value) {
                    $turn_save = [];
                    $turn_save['bank_reference_no'] = $pay_operate_data[$value]['statement_id'];
                    $turn_save['bank_rate'] = str_replace(',','.', $this->getItemValue($pay_operate_data[$value]['complementary_information'], 'ER'));
                    $res_turnover = $accTurnoverModel->where(['transfer_no' => $value])->save($turn_save);
                }
            }
        }      
    }

    public function getKyHistData()
    {
        if(isset($_FILES['excel']) && $_FILES['excel']['error'] == 0) {
            session_write_close();
            ini_set('date.timezone', 'Asia/Shanghai');
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['excel']['tmp_name'];  //导入的excel路径
            vendor("PHPExcel.PHPExcel");
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    echo 'no Excel';
                    return;
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
            for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                
                $data['account_currency_code'] = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue()); // 2
                $data['btc_code'] = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue()); // 5
                $data['data_type'] = trim((string)$PHPExcel->getActiveSheet()->getCell("K" . $currentRow)->getValue()); // 10
                $data['direction'] = trim((string)$PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue()); // 12 
                $data['complementary_information'] = trim((string)$PHPExcel->getActiveSheet()->getCell("J" . $currentRow)->getValue());  // 9
                $data['reference_text'] = trim((string)$PHPExcel->getActiveSheet()->getCell("Z" . $currentRow)->getValue()); // 25 
                $data['account_amount'] = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue()); // 1
                $data['statement_id'] = trim((string)$PHPExcel->getActiveSheet()->getCell("AA" . $currentRow)->getValue()); // 26
                $data['transfer_no'] = trim((string)$PHPExcel->getActiveSheet()->getCell("U" . $currentRow)->getValue()); // 

                if ($data['btc_code'] != 'INIT' && $data['data_type'] == 'TR' && $data['direction'] == '1' && !stripos($data['complementary_information'], 'PY/SALARY')) {
                    $pay_operate_data[trim($data['transfer_no'])] = $data;
                    $transfer_pay_no_arr[] = numToStr(trim($data['reference_text']));   //付款单数组，科学计数法转数字
                    $main_pay_transfer_no_arr[] = trim($data['transfer_no']);   //唯一的U列数组

                }

                if ($data['btc_code'] != 'INIT' && $data['data_type'] == 'TR' && $data['direction'] == '2') {
                    $receive_operate_data[trim($data['transfer_no'])] = $data;
                    $transfer_receiver_no_arr[] = numToStr(trim($data['reference_text']));  //收款单数组，科学计数法转数字
                    $main_receive_transfer_no_arr[] = trim($data['transfer_no']);   //唯一的U列数组
                }
            }
            return [$pay_operate_data, $transfer_pay_no_arr, $main_pay_transfer_no_arr, $receive_operate_data, $transfer_receiver_no_arr, $main_receive_transfer_no_arr];
        }
    }

    //读取kriba回传信息，同步银行回单返回数据
    public function sftpReceiveSynchronize()
    {
        list($download_success_file_path, $download_failed_file) = SftpModel::client()->downloadFile();
        if (!empty($download_failed_file)) {
            @SentinelModel::addAbnormal('下载sftp文件失败', '', $download_failed_file,'kyriba_notice');
        }
        if (empty($download_success_file_path)) {
            return ['result' => false];
        }
        //$download_success_file_path[] = "D:\BankActual_20201119_020107201605780476728.csv";
        //$download_success_file_path[] = "D:\BankActual_308.csv";
        foreach ($download_success_file_path as $sftp_file => $save_file) {
            //pgp解密
            $content = file_get_contents($save_file);
            $decrypt_content = $content;
            try {
               $decrypt_content = (new Gpg())->decrypt($content);
            } catch (Exception $exception) {
               @SentinelModel::addAbnormal(__FUNCTION__. ' kyriba pgp 解密失败', $exception->getMessage(), [$content],'kyriba_notice');
            }
            if (empty($decrypt_content)) {
               @SentinelModel::addAbnormal('kyriba回传文件解密失败', $save_file, [$save_file],'kyriba_notice');
               continue;
            }

            $file_name = explode('.', $save_file)[0]. time(). '.csv';
            $csv_arr = csvStringToArray($decrypt_content, 30, ';');
            $fp = fopen($file_name, 'w');
            foreach ($csv_arr as $fields) {
               fputcsv($fp, $fields);
            }
            Logs($file_name, __FUNCTION__.'save file name', 'kyriba');
            fclose($fp);
            if (!is_file($file_name)) {
               @SentinelModel::addAbnormal('kyriba回传文件保存到本地失败', $file_name, [$csv_arr],'kyriba_notice');
               continue;
            }
            $transfer_pay_no_arr = $main_pay_transfer_no_arr = $bank_ids = [];//置空初始化
            $transfer_receiver_no_arr = $main_receive_transfer_no_arr = [];//置空初始化
            $pay_operate_data = $receive_operate_data = [];//置空初始化
            $handle = fopen($file_name, 'r');
            if ($handle !== false) {
                while ($data = fgetcsv($handle, 10000)) {
                    //$data = eval('return '.iconv('gbk','utf-8',var_export($data,true)).';'); // 防止fgetcsv获取中文乱码 屏蔽此转换代码，因本身解密csv文件已经是utf-8格式，无需额外转换，否则会过滤掉部分符合条件的数组数据
                    if ($data[5] != 'INIT' && $data[10] == 'TR' && $data[12] == '1' && !stripos($data[9], 'PY/SALARY')) {
                        $pay_operate_data[trim($data[20])] = $data;
                        $transfer_pay_no_arr[] = numToStr(trim($data[25]));   //付款单数组，科学计数法转数字
                        $main_pay_transfer_no_arr[] = trim($data[20]);   //唯一的U列数组
                    }

                    if ($data[5] != 'INIT' && $data[10] == 'TR' && $data[12] == '2') {
                        $receive_operate_data[trim($data[20])] = $data;
                        $transfer_receiver_no_arr[] = numToStr(trim($data[25]));  //收款单数组，科学计数法转数字
                        $main_receive_transfer_no_arr[] = trim($data[20]);   //唯一的U列数组
                    }
                    $bank_ids[] = $data[1];
                }
            } else {
                Logs($file_name, __FUNCTION__.'读取文件失败', 'kyriba');
            }

            $exist_pay_turnover_arr = [];
            $turnover_model = new TbWmsAccountTurnoverModel();
            if (!empty($main_pay_transfer_no_arr)) {
                //查出已存在的付款日记账
                $exist_pay_turnover_data = $turnover_model->where(['transfer_no'=> ['in', $main_pay_transfer_no_arr], 'pay_or_rec'=>0])->field('transfer_no')->select();
                $exist_pay_turnover_arr = array_column($exist_pay_turnover_data,'transfer_no');
            } else {
                Logs('', __FUNCTION__.'符合条件的付款单数组为空', 'kyriba');
            }

            $my_bank_info = [];
            if (!empty($bank_ids)) {
                //银行信息
                $field     = ['id', 'company_code', 'account_bank', 'swift_code', 'open_bank'];
                $bank_info = M('fin_account_bank', 'tb_')->where(['id' => ['in', $bank_ids]])->field($field)->select();
                foreach ($bank_info as $bv) {
                    $my_bank_info[$bv['id']] = $bv;
                }
            } else {
               Logs('', __FUNCTION__.'银行id数组集为空', 'kyriba'); 
            }
            $this->doSynchronize($transfer_pay_no_arr, $transfer_receiver_no_arr, $exist_pay_turnover_arr, $pay_operate_data, $receive_operate_data, $my_bank_info, $turnover_model);
            fclose($handle);
        }
        return true;
    }


    //处理csv具体方法
    public function doSynchronize($transfer_pay_no_arr, $transfer_receiver_no_arr, $exist_pay_turnover_arr, $pay_operate_data, $receive_operate_data, $my_bank_info, $turnover_model)
    {
        //付款相关
        if(!empty($pay_operate_data)) {
            //已存在的到的付款单号过滤
            $exist_payment_no = $this->payment_audit_table->where(['payment_audit_no'=>['in', $transfer_pay_no_arr]])->field('payment_audit_no')->select();
            $exist_payment_no = array_column($exist_payment_no, 'payment_audit_no');

            //不在付款单表里则生成付款日记账
            foreach($pay_operate_data as $rv) {
                if(!in_array(trim(numToStr($rv[25])), $exist_payment_no) && !in_array(trim($rv[20]), $exist_pay_turnover_arr)){
                    //是否已存在，不存在则插入
                    $this->makeTurnoverPayData($rv, $my_bank_info, $turnover_model);
                }
            }
        } else {
            Logs('', __FUNCTION__.'付款数组集合为空', 'kyriba');
        }
        //收款相关
        if(!empty($receive_operate_data)){
            foreach($receive_operate_data as $rpk=>$rpv){
                $this_complement_data = $rpv[9]; // J列
                $py_match = $this->getItemValue($this_complement_data, 'PY'); // J列中/PY/后面匹配到关联交易类型的付款单号
                if(!empty($py_match)){
                    $py_match_arr[] = $py_match;
                }
                $receive_z_data[trim(numToStr($rpv[25]))] = $rpv;   //Z列校验转账换汇的关联
                $receive_py_data[$py_match] = $rpv;    //PY校验转账换汇的关联
            }

            //自动确认转账换汇的收款确认-情况一。Z列符合自动完成关联交易单的，进行自动完成操作
            $this->finishReceive($transfer_receiver_no_arr, $receive_z_data, $my_bank_info, $turnover_model,$unset_confirm);
            //自动确认转账换汇的收款确认-情况二。J列，PY中还有符合自动完成关联交易单的，再次进行自动完成操作
            if(!empty($py_match_arr)){
               $this->finishReceive($py_match_arr, $receive_py_data, $my_bank_info, $turnover_model, $unset_confirm);
            }
            
            //查询一遍已经插入的收款日记账记录，排除它
            $exist_receive_turnover_data =  $turnover_model->where(['transfer_no'=> ['in', array_keys($receive_operate_data)], 'pay_or_rec'=>1])->field('transfer_no')->select();
            $exist_receive_turnover_arr = array_column($exist_receive_turnover_data,'transfer_no');

            //剩下没转账换汇确认处理的数据, 全部写入收款日记账（自动生成收款日记账和待认领记录）
            foreach ($receive_operate_data as $rrk=>$rrv) {
                if(!in_array($rrk, $exist_receive_turnover_arr) && !in_array(trim(numToStr($rrv[25])), $unset_confirm)){
                    Logs($rrv, __FUNCTION__.'收款数据', 'kyriba');
                    $this->makeTurnoverReceiveData($rrv, $my_bank_info, $turnover_model);
                }
            }
        } else {
            Logs('', __FUNCTION__.'收款数据为空', 'kyriba');
        }
        return true;
    }


    //付款日记账流水写入
    public function makeTurnoverPayData($this_pay_operate, $my_bank_info, $turnover_model) {
        //表格数据中Z列数据用来匹配付款单号
        $this_complement_data = $this_pay_operate[9]; // J列
        $account_transfer_no = 'LS' . date('Ymd') . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
        $pay_or_rec = 0;  //收支状态（0：支出，1：收入）
        $transfer_type = TbWmsAccountTurnoverModel::PUR_POUNDAGE;  //收支方向：固定为付款手续费
        $transfer_no = trim($this_pay_operate[20]);  //transfer_no
        $currency_code = valCd($this_pay_operate[19]);  //币种
        $amount_money = $this_pay_operate[17];   //金额
        $company_code = $my_bank_info[$this_pay_operate[1]]['company_code'];   //我方银行关联的公司code
        $company_name = cdVal($my_bank_info[$this_pay_operate[1]]['company_code']);   //我方银行关联的公司名字
        $open_bank = addslashes($my_bank_info[$this_pay_operate[1]]['open_bank']);   //我方银行或分行名称
        $account_bank = $my_bank_info[$this_pay_operate[1]]['account_bank'];    //我方银行账号
        $swift_code = $my_bank_info[$this_pay_operate[1]]['swift_code'];    //我方银行swift_code账号
        $transfer_time = date("Y-m-d", strtotime($this_pay_operate[28]));   //发生日期
        $create_user = self::KYRIBA_USER_ID;   //创建人Kyriba
        $create_time = date("Y-m-d H:i:s", time());   //创建时间
        //$ref_match = $this->getItemValue($this_complement_data, 'REF');
        // $ref_match = $this_pay_operate[26];
        $ref_match = numToStr(trim($this_pay_operate[26])); // 银行参考号 ;
        $er_match = str_replace(',','.', $this->getItemValue($this_complement_data, 'ER')); // 银行汇率

        $py_match = $this->getItemValue($this_complement_data, 'PY');
        $oa_match_str = $this->getItemValue($this_complement_data, 'OA');
        $oa_match = str_replace(',','.', $oa_match_str);

        $cm_match = $this->getItemValue($this_complement_data, 'CM');
        if(empty($oa_match)){
            $original_currency = valCd($this_pay_operate[19]);    //原始付款币种
            $original_amount =  $this_pay_operate[17];  //原始付款金额
        }else{
            //金额格式化
            $oa_match = $this->getItemAmount($oa_match);
            $original_currency = valCd($oa_match[1]);    //原始付款币种
            $original_amount =  str_replace(',','',$oa_match[2]);  //原始付款金额
        }
        if(empty($cm_match)){
            $other_currency = valCd($this_pay_operate[19]);    //原始付款币种
            $other_cost =  0;  //原始付款金额
        }else{
            //金额格式化
            $cm_match = $this->getItemAmount($cm_match);
            $other_currency = valCd($cm_match[1]);    //其他费用币种
            $other_cost =  str_replace(',','.', $cm_match[2]);  //其他费用金额
        }

        $bank_reference_no = $ref_match;    //银行参考号
        $bank_payment_reason = addslashes($py_match);    //付款原因
        $remark = addslashes($py_match);    //备注
        $bank_rate = $er_match; // 银行汇率
        $sql = "
            INSERT INTO `tb_fin_account_turnover` (
               `account_transfer_no`,`remitter_currency`, `pay_or_rec`,`transfer_type`,`transfer_no`,`company_name`,`currency_code`,`amount_money`,`company_code`,`open_bank`,`account_bank`,`swift_code`,`transfer_time`,`create_user`,`create_time`,
                `original_currency`,`original_amount`,`other_currency`,`other_cost`,`bank_reference_no`,`bank_payment_reason`,`remark`, `bank_rate`
                )
                VALUES
                ('$account_transfer_no','$currency_code','$pay_or_rec','$transfer_type','$transfer_no','$company_name','$currency_code','$amount_money','$company_code','$open_bank',
                      '$account_bank','$swift_code','$transfer_time','$create_user','$create_time',
                '$original_currency','$original_amount','$other_currency','$other_cost','$bank_reference_no','$bank_payment_reason','$remark', '$bank_rate')
               ";
        $res =  $turnover_model->execute($sql);
        $insert_id = $turnover_model->getLastInsID();
        Logs([$insert_id, $res, $sql], __FUNCTION__.'付款sql', 'kyribadata');
        if($insert_id > 0){
            echo "direction 1 cell U : $transfer_no has been insert into database \n";
            return $insert_id;
        }
    }


    //收款日记账流水写入
    public function makeTurnoverReceiveData($this_receive_operate, $my_bank_info, $turnover_model){
        //表格数据中Z列数据用来匹配付款单号
        $this_complement_data = $this_receive_operate[9];
        $account_transfer_no = 'LS' . date('Ymd') . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
        $pay_or_rec = 1; //收支状态（0：支出，1：收入）
        $transfer_type = TbWmsAccountTurnoverModel::TRANSFER_REC;  //划转转入
        $transfer_no = trim($this_receive_operate[20]);  //transfer_no
        $currency_code = valCd($this_receive_operate[2]);  //币种
        //$amount_money = $this_receive_operate[17];   //金额
        $amount_money = $this->getRightFormatAmount($this_receive_operate[0]); //实际收款金额
        $company_code = $my_bank_info[$this_receive_operate[1]]['company_code'];   //我方银行关联的公司code
        $company_name = cdVal($my_bank_info[$this_receive_operate[1]]['company_code']);   //我方银行关联的公司名字
        $open_bank = addslashes($my_bank_info[$this_receive_operate[1]]['open_bank']);   //我方银行或分行名称
        $account_bank = $my_bank_info[$this_receive_operate[1]]['account_bank'];    //我方银行账号
        $swift_code = $my_bank_info[$this_receive_operate[1]]['swift_code'];    //我方银行swift_code账号
        $transfer_time = date("Y-m-d", strtotime($this_receive_operate[28]));   //发生日期
        $create_user = self::KYRIBA_USER_ID;   //创建人Kyriba
        $create_time = date("Y-m-d H:i:s", time());   //创建时间
        //$ref_match = $this->getItemValue($this_complement_data, 'REF');
        $er_match = str_replace(',','.', $this->getItemValue($this_complement_data, 'ER'));
        // $ref_match = $this_receive_operate[26]; // AA列
        $ref_match = numToStr(trim($this_receive_operate[26])); // 银行参考号 ;
        $py_match = $this->getItemValue($this_complement_data, 'PY');
        $cm_match = $this->getItemValue($this_complement_data, 'CM');
        $op_company_match = $this->getOppositeAccountName($this_complement_data);  //对方账户的公司名字
        $oa_match_str = $this->getItemValue($this_complement_data, 'OA');
        $oa_match = str_replace(',','.', $oa_match_str);
        if(empty($oa_match)){ // (J列)的/OA/后面&下一个/前面的内容（包括金额和币种）,如果没有/OA/则为Flow currency code(C列) & Flow amount(A列)
            $original_currency = valCd($this_receive_operate[2]);    //原始付款币种
            // $original_amount =  $this_receive_operate[17];  //原始付款金额
            $original_amount = $this->getRightFormatAmount($this_receive_operate[0]);
        }else{
            //金额格式化
            $oa_match = $this->getItemAmount($oa_match);
            $original_currency = valCd($oa_match[1]);    //原始付款币种
            $original_amount =  str_replace(',','',$oa_match[2]);  //原始付款金额
        }
        if(empty($cm_match)){
            $other_currency = valCd($this_receive_operate[2]);    //原始付款币种
            $other_cost =  0;  //原始付款金额
        }else{
            //金额格式化
            $cm_match = $this->getItemAmount($cm_match);
            $other_currency = valCd($cm_match[1]);    //其他费用币种
            $other_cost =  str_replace(',','.', $cm_match[2]);  //其他费用金额
        }
        $bank_reference_no = $ref_match;    //银行参考号
        //$bank_payment_reason = addslashes($py_match);    //付款原因
        $remark = addslashes($py_match);    //备注
        $bank_rate = $er_match; // 银行汇率
        $sql = "
            INSERT INTO `tb_fin_account_turnover` (
                `transfer_type`,`account_transfer_no`,`remitter_currency`,`pay_or_rec`,`company_name`,`transfer_no`,`currency_code`,`amount_money`,`company_code`,`open_bank`,`account_bank`,`swift_code`,`transfer_time`,`create_user`,`create_time`,
                `original_currency`,`original_amount`,`other_currency`,`other_cost`,`bank_reference_no`,`remark`,`opp_company_name`,`bank_rate`
                )
                VALUES
                ('$transfer_type','$account_transfer_no','$currency_code','$pay_or_rec','$company_name','$transfer_no','$currency_code','$amount_money','$company_code','$open_bank',
                      '$account_bank','$swift_code','$transfer_time','$create_user','$create_time',
                '$original_currency','$original_amount','$other_currency','$other_cost','$bank_reference_no','$remark','$op_company_match', '$bank_rate')
               ";

        $res =  $turnover_model->execute($sql);
        Logs([$res, $sql], __FUNCTION__.'收款sql', 'kyribadata');
        if($res > 0){
            $insert_id = $turnover_model->getLastInsID();
            echo "direction 2 cell U : $transfer_no has been insert into database \n";
            return $insert_id;
        }
    }


    //自动完成转账换汇审批的提交按钮
    public function finishReceive($transfer_receiver_no_arr, $receive_this_item_data, $my_bank_info, $turnover_model, &$unset_confirm)
    {
        $claim_model = new TbFinClaimModel();
        $waiting_confirm_transfer_data = $this->payment_audit_table->alias('a')
            ->where(['a.payment_audit_no' => ['in', $transfer_receiver_no_arr], 'a.source_cd' => ['in', [TbPurPaymentAuditModel::$source_transfer_payable, TbPurPaymentAuditModel::$source_transfer_payable_indirect]]])
            ->field('a.id as audit_id,a.payment_audit_no,b.*')
            ->join('right join tb_fin_account_transfer b on a.id = b.payment_audit_id')
            ->select();

        //能匹配到付款单关联的单号则自动确认转账换汇的收款
        foreach ($waiting_confirm_transfer_data as $wk => $wv) {
            $unset_confirm[] = $wv['payment_audit_no'];
            $this->receiveConfirm($turnover_model, $claim_model, $wv, $receive_this_item_data[$wv['payment_audit_no']], $my_bank_info);
        }
    }


    //转账换汇的确认收款操作，跟详情页点击提交是一样的逻辑
    public function receiveConfirm($turnover_model, $claim_model, $this_transfer_data, $this_receive_data, $my_bank_info){
        if ($this_transfer_data ['state'] == self::TRANSFER_WAIT_REC) {
            M()->startTrans();
            $update['state'] = self::TRANSFER_SUCCESS;
            $update['rec_transfer_time'] = date("Y-m-d", strtotime($this_receive_data[28])); //收款日期
            $update['rec_actual_money'] = $this->getRightFormatAmount($this_receive_data[0]); //实际收款金额
            $update['rec_reason'] = $this_receive_data[9]; //收款备注
            $update['current_step'] = 7;
            $res1 = M('fin_account_transfer','tb_')->where(['id'=>$this_transfer_data['id']])->save($update);
            if ($res1 > 0) {
                $res2 = $this->makeTurnoverReceiveData($this_receive_data, $my_bank_info, $turnover_model);
                if ($res2 == 0) {
                    M()->rollback();
                    return false;
                }
                $relation = [
                    'account_turnover_id' => $res2,
                    'order_type' => 'N001950400', //划转转入
                    'order_no' => $this_transfer_data['transfer_no'],
                    'claim_amount' => $this->getRightFormatAmount($this_receive_data[0]),
                    'created_at' => $this_transfer_data['audit_time'],
                    'created_by' => 'Kyriba',
                ];
                $res3 = $claim_model->add($relation);
                if ($res3 == 0) {
                    M()->rollback();
                    return false;
                } else {
                    M()->commit();
                    return true;
                }
            } else {
                M()->rollback();
                return false;
            }
        }
    }


    //校验获取项的数值
    public function getItemValue($str, $item){
        if(strpos($str, $item."//")){
            $match = [""];
        }else{
            $regex = "/(?<=$item\/).+?(?=\/)/";
            preg_match($regex, $str, $match);
        }
        return $match[0];
    }


    //金额的币种数值解析
    public function getItemAmount($str){
        $match = [];
        preg_match('/([A-Z]+)(.+)/', $str, $match);
        return $match;
    }

    //金额格式处理
    public function getRightFormatAmount($data)
    {
        if (false !== strpos($data, '+')) {
            $data = str_replace('+', '', $data);
        }
        if (false !== strpos($data, '-')) {
            $data = str_replace('-', '', $data);
        }
        return $data;
    }


    //获取对方账户公司名字
    public function getOppositeAccountName($str){
        // $opposite_account = $this->getItemValue($str, 'BO1');
        $info = explode('/', $str);
        $opposite_account = $info[array_search('BO2', $info) - 1];//有BO1，且有BO2，则为BO1后面&/BO2/前面的，没有BO2则取BO1后面的
        if (empty($opposite_account)) {
           $opposite_account = $this->getItemValue($str, 'BO1');
           if(empty($opposite_account)){
               $opposite_account = $this->getItemValue($str, 'BO');
               if(empty($opposite_account)){
                   $opposite_account = $this->getItemValue($str, 'EI');
                   if(empty($opposite_account)){
                       $opposite_account = $this->getItemValue($str, 'EI1');
                       if(empty($opposite_account)){
                           //$opposite_account = "等待Kyriba林熙确认";
                           $opposite_account = "";
                       }
                   }
               }
           } 
        }
        return $opposite_account;
    }


    //定期删除远程sftp asc文件
    public function deleteAscFile(){
        return SftpModel::client()->deleteSomeFile();
    }


    //Kyriba的 解密生成本地csv文件单独处理
    public function makeCsvFileFromAsc() {
        list($download_success_file_path, $download_failed_file) = SftpModel::client()->downloadFile();
        if (!empty($download_failed_file)) {
            @SentinelModel::addAbnormal('下载sftp文件失败', '', $download_failed_file,'kyriba_notice');
        }

        //新建csv存放目录
        $csv_working_dir = C('ftp_config')['local_save_path'].self::KYRIBA_WORKING_CSV_DIR;
        if(!is_dir($csv_working_dir)) {
            mkdir($csv_working_dir, 0777, true);
            chmod($csv_working_dir, 0777);
        }

        foreach ($download_success_file_path as $sftp_file => $save_file) {
            preg_match('/(?<=doc\/).*?(?=\.asc)/',$save_file,$match);
            $csv_name = $match[0].'.csv';
            $csv_full_name_path = $csv_working_dir.'/'.$csv_name;

            if(!file_exists($csv_full_name_path)) {
                $content = file_get_contents($save_file);
                try {
                    $decrypt_content = (new Gpg())->decrypt($content);
                } catch (Exception $exception) {
                    @SentinelModel::addAbnormal(__FUNCTION__. ' kyriba pgp 解密失败', $exception->getMessage(), [$content],'kyriba_notice');
                }
                if (empty($decrypt_content)) {
                    @SentinelModel::addAbnormal('kyriba回传文件解密失败', $save_file, [$save_file],'kyriba_notice');
                    continue;
                }
                $csv_arr = csvStringToArray($decrypt_content, 30, ';');
                $fp = fopen($csv_full_name_path, 'w');
                foreach ($csv_arr as $fields) {
                    fputcsv($fp, $fields);
                }
                fclose($fp);
                if (!is_file($csv_full_name_path)) {
                    @SentinelModel::addAbnormal('kyriba回传文件保存到本地csv失败', $csv_full_name_path, [$csv_arr],'kyriba_notice');
                    continue;
                }

                //删除本地asc文件
                //exec("rm -rf $save_file");
            }
        }
    }


    //读取csv，同步银行回单返回数据，重写本类的sftpReceiveSynchronize 方法
    public function sftpReceiveSynchronizeSubstitute()
    {
        //获取已经解密处理的csv文件
        $csv_files = $this->getCsvFiles();
        if(!empty($csv_files)) {
            foreach ($csv_files as $ck => $file) {
                $handle = fopen($file, 'r');
                if ($handle !== false) {
                    while ($data = fgetcsv($handle, 10000)) {
                        // $data = eval('return '.iconv('gbk','utf-8',var_export($data,true)).';');屏蔽此转换代码，因本身解密csv文件已经是utf-8格式，无需额外转换，否则会过滤掉部分符合条件的数组数据
                        if ($data[5] != 'INIT' && $data[10] == 'TR' && $data[12] == '1' && !stripos($data[9], 'PY/SALARY')) {
                            $pay_operate_data[trim($data[20])] = $data;
                            $transfer_pay_no_arr[] = trim($data[25])."\t";   //付款单数组
                            $main_pay_transfer_no_arr[] = trim($data[20]);   //唯一的U列数组
                        }

                        if ($data[5] != 'INIT' && $data[10] == 'TR' && $data[12] == '2') {
                            $receive_operate_data[trim($data[20])] = $data;
                            $transfer_receiver_no_arr[] = trim( $data[25]);  //收款单数组
                            $main_receive_transfer_no_arr[] = trim($data[20]);   //唯一的U列数组
                        }

                        $bank_ids[] = $data[1];
                    }
                }

                //查出已存在的付款日记账
                $turnover_model = new TbWmsAccountTurnoverModel();
                $exist_pay_turnover_data =  $turnover_model->where(['transfer_no'=> ['in', $main_pay_transfer_no_arr], 'pay_or_rec'=>0])->field('transfer_no')->select();
                $exist_pay_turnover_arr = array_column($exist_pay_turnover_data,'transfer_no');


                //银行信息
                $field = ['id', 'company_code', 'account_bank', 'swift_code', 'open_bank'];
                $bank_info = M('fin_account_bank', 'tb_')->where(['id'=>['in', $bank_ids]])->field($field)->select();
                foreach ($bank_info as $bv){
                    $my_bank_info[$bv['id']] = $bv;
                }
                $this->doSynchronize($transfer_pay_no_arr,$transfer_receiver_no_arr,$exist_pay_turnover_arr, $pay_operate_data, $receive_operate_data, $my_bank_info, $turnover_model);
                fclose($handle);
            }
        }
    }


    //读取csv，同步银行回单返回数据，重写本类的sftpReceive方法
    public function sftpReceiveSubstitute()
    {
        //获取已经解密处理的csv文件
        $csv_files = $this->getCsvFiles();
        if(!empty($csv_files)) {
            foreach ($csv_files as $ck => $file) {
                $handle = fopen($file, 'r');
                if ($handle !== false) {
                    while ($data = fgetcsv($handle, 10000)) {
                        $this->receiveHandle($data);
                    }
                }
                fclose($handle);
            }
        }
        return true;
    }


    //定期移动csv文件到回收站
    public function moveCsvToRecycle() {
        $csv_files = $this->getCsvFiles();
        $recycle_dir = C('ftp_config')['local_save_path'].self::KYRIBA_RECYCLE_CSV_DIR;
        if(!is_dir($recycle_dir)) {
            mkdir($recycle_dir, 0777, true);
            chmod($recycle_dir, 0777);
        }

        foreach ($csv_files as $ck=>$cv) {
            exec(" mv $cv $recycle_dir");
        }
    }


    //遍历需要处理的csv文件夹下的文件
    public function getCsvFiles() {
        $dir = C('ftp_config')['local_save_path'].self::KYRIBA_WORKING_CSV_DIR;
        $open_dir = opendir($dir);
        while (($file = readdir($open_dir)) !== false) {
            if(strrpos($file,'.csv') != false){
                $csv_files[] = $dir.'/'.$file;
            }
        }
        closedir($dir);
        return $csv_files;
    }


}