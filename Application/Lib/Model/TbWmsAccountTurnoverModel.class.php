<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/2/3
 * Time: 17:10
 */

class TbWmsAccountTurnoverModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_turnover';

    private $transferType;
    private $companyCode;
    private $openBank;
    private $accountBank;
    private $transferNo;
    private $createStartTime;
    private $createEndTime;
    private $createTime;
    private $payOrRec;
    private $amountMoney;
    private $currencyCode;
    private $transferTime;
    private $companyName;
    private $childTransferNo;
    public $params;

    private $accountTransferNo;
    private $oppCompanyName;
    private $oppOpenBank;
    private $oppAccountBank;
    private $transferVoucher;
    private $swiftCode;
    private $OppSwiftCode;
    private $remark;
    
//    private $createTime;
//    private $createUser;
    private $originalCurrency;
    private $originalAmount;
    private $otherCurrency;
    private $otherCost;
    private $remitterCurrency;
    private $remitterCost;
    
    private $code;

    const TRANSFER_PAY = 'N001950300';
    const TRANSFER_REC = 'N001950400'; //划转转入
    const PUR_POUNDAGE = 'N001950500';//付款手续费
    const PUR_LOAN = 'N001950100';//采购应付贷款
    const B2B_REC = 'N001950200';//B2B收款
    const TRANSFER_OUT = 0;
    const TRANSFER_IN  = 1;
    public static $alloExpenseCd = [
        'N001950606',
        'N001950605',
        'N001950604',
        'N001950603',
        'N001950602',
        'N001950601',
        'N001950500',
        'N001950607',
    ];//调拨费用code

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user', 'getName', Model::MODEL_INSERT, 'callback']
    ];
    
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];

    public function __set($name, $value)
    {
        $this->$name = $value;
        $name = $this->humpToLine($name);
        if ($value !== '' and !is_null($value) and $value !== false)
            $this->params[$name] = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public $totalAmountMoney;
    public $count;
    public $pageIndex;
    public $pageSize;

    /**
     * 数据获取
     */
    public function getList($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize'])  or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];
        
        $transactionType = $params ['transactionType'];

        //fields
        $field = [
            "t1.id as turnoverId",
            "t1.transfer_no as transferNo",//主订单号
            "t1.child_transfer_no as childTransferNo",
            "t1.transfer_type as transferType",
            "t1.amount_money as amountMoney",
            "t1.original_amount as originalAmount",
            "t1.company_code as companyCode",
            "t1.account_bank as accountBank",
            "t1.transfer_time as transferTime",//发生日期
            "t1.company_name as companyName",
            "t1.currency_code as currencyCode",
            "t1.open_bank as openBank",
            
            "t1.account_transfer_no as accountTransferNo",//流水号
            "t1.opp_company_name as oppCompanyName",
            "t1.opp_open_bank as oppOpenBank",
            "t1.opp_account_bank as oppAccountBank",
            "t1.create_time as createTime",
            "t1.collection_type as collectionType",
            "t1.trade_type as tradeType",
            "t1.bank_reference_no",
            "t1.bank_payment_reason",
            "t1.remark"
        ];
        //query
//        $this->subWhere('_string','t1.account_bank = t2.account_bank')
        $this->subWhere('t1.transfer_type', ['eq', $params ['transferType']])
            ->subWhere('t1.company_code', ['eq', $params ['companyCode']])
            ->subWhere('t1.account_bank', ['like', $params ['accountBank']])
            ->subWhere('t1.transfer_no', ['like', $params ['transferNo']])
            ->subWhere('t1.transfer_time', ['xrange', [$params ['transferStartTime'], $params ['transferEndTime']]])
        
            ->subWhere('t1.account_transfer_no', ['eq', $params ['accountTransferNo']])
            ->subWhere('t1.open_bank', ['like', $params ['openBank']])
            ->subWhere('t1.opp_company_name', ['like', $params ['oppCompanyName']])
            ->subWhere('t1.opp_open_bank', ['like', $params ['oppOpenBank']])
            ->subWhere('t1.opp_account_bank', ['like', $params ['oppAccountBank']])
            ->subWhere('t1.currency_code', ['like', $params ['currencyCode']])
            ->subWhere('t1.collection_type', ['eq', $params ['collectionType']])
            ->subWhere('t1.create_time', ['xrange', [$params ['createStartTime'], $params ['createEndTime']]])
            ->subWhere('t1.bank_reference_no', ['eq', $params ['bank_reference_no']]);
            if (!empty($transactionType )) {
                if ($transactionType != parent::$COLLECTION_TYPE) {
                    static::$where['t1.transfer_type'] = ['in', parent::getTransactionTypeCds('付款')];
                } else {
                    static::$where['t1.transfer_type'] = ['in', parent::getTransactionTypeCds('收款')];
                }
            }
            if ("" !== $params ['tradeType']) {
                static::$where['t1.trade_type'] = $params ['tradeType'];
            }
        
        //statistics
        $statisticsFields = [
            't1.amount_money',
            't1.currency_code',
            't1.transfer_time'
        ];
        $count = $this->field($statisticsFields)
//            ->table('tb_fin_account_turnover t1, tb_fin_account_bank t2')
            ->table('tb_fin_account_turnover t1')
            ->where(static::$where)
            //->limit($Page->firstRow, $Page->listRows)
            ->count();
        //page
        $Page  = new Page($count, $pageSize);
        //exec
        $subQuery = $this->field($field)
//            ->table('tb_fin_account_turnover t1, tb_fin_account_bank t2')
            ->table('tb_fin_account_turnover t1')
            ->where(static::$where);
        if ($isExcel == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->order('t1.id desc')->select();
        
        //判断是收款还是付款
        foreach ($ret as $k => &$v) {
            $v['transactionType'] = parent::checkTransactionType($v['transferType']);
            if (parent::isPaymentTransactionType($v['transferType'])) {
                $v['amountMoney'] = $v['originalAmount'];
            }
            $v['tradeType'] = empty($v['tradeType']) ? '否' : '是';
        }
        
        if ($isExcel)  {
            $ret = $this->filter($ret);
        }

        $this->count            = $count;
        $this->pageIndex        = $pageIndex;
        $this->pageSize         = $pageSize;

        return $ret;
    }

    /**
     * @param $data 转码
     * @return mixed
     */
    public function filter($data)
    {
        $data = array_map(function($r) {
            $r ['currencyCode'] = BaseModel::getCurrency() [$r ['currencyCode']];
            $r ['transferType'] = BaseModel::transferType() [$r ['transferType']];
            $r ['companyCode'] = BaseModel::ourCompany() [$r ['companyCode']];
            $r ['collectionType'] = BaseModel::getCollection() [$r ['collectionType']];

            return $r;
        }, $data);

        return $data;
    }

    public static $mappingValue;

    public function mappingValue()
    {
        if (static::$mappingValue)
            return static::$mappingValue;

        $ret = CommonDataModel::currency();
        $ret = array_column($ret, 'cdVal', 'cd');

        static::$mappingValue = $ret;

        return $ret;
    }

    public static $where;

    /**
     * 构建查询条件
     * @param mixed $str
     * @return array
     */
    public function subWhere($key, $str)
    {
        if (is_array($str)) {
            list($pattern, $val) = $str;
            if ($val) {
                switch ($pattern) {
                    case 'like':
                        static::$where [$key] = ['like', '%' . $val . '%'];
                        break;
                    case 'range':
                        list($f, $l) = $val;
                        if ($f and $l)
                            static::$where [$key] = [['gt', $f . ' 00:00:00'], ['lt', $l . ' 23:59:59'], 'and'];
                        if ($f and !$l)
                            static::$where [$key] = ['gt', $f . ' 00:00:00'];
                        if ($l and !$f)
                            static::$where [$key] = ['lt', $l . ' 23:59:59'];
                        break;
                    case 'xrange':
                        list($f, $l) = $val;
                        if ($f and $l)
                            static::$where [$key] = [['egt', $f . ' 00:00:00'], ['elt', $l . ' 23:59:59'], 'and'];
                        if ($f and !$l)
                            static::$where [$key] = ['egt', $f . ' 00:00:00'];
                        if ($l and !$f)
                            static::$where [$key] = ['elt', $l . ' 23:59:59'];
                        break;
                    default:
                        static::$where [$key] = $val;
                        break;
                }
            }
        } else {
            if ($str) {
                if (isset(static::$where [$key]))
                    static::$where [$key] .= ' and ' . $str;
                else
                    static::$where [$key] = $str;
            }
        }

        return $this;
    }

    /**
     * @var array
     * 币种汇率缓存
     */
    public static $rate   = [];
    public static $suffix = 'CNY';
    public static $infix  = '_XCHR_AMT_';

    /**
     * 汇率获取
     * @param string $currency 币种
     * @param date   $date     交易日期
     * @return float|null 返回汇率
     */
    public function getCurrencyRate($currency, $date)
    {
        $date = date('Ymd', strtotime($date));
        if (self::$rate [$currency]) {
            return self::$rate [$currency];
        }
        $model = new Model();
        $prefix = strtoupper($currency);
        $field = $prefix . self::$infix . self::$suffix;
        $conditions ['XCHR_STD_DT'] = ['eq', $date];

        $ret = $model->table('tb_ms_xchr')
            ->field($field)
            ->where($conditions)
            ->find();

        self::$rate [$currency] = $ret [$field];

        return $ret [$field];
    }
    
    /**
     * 收款流水录入
     * @param type $data
     * @return boolean
     * @throws Exception
     */
    public function receiptEntry($data) {
        if (is_array($data['transfer_voucher']) && !empty($data['transfer_voucher'])) {
            $data['transfer_voucher'] = json_encode($data['transfer_voucher'],JSON_UNESCAPED_UNICODE);//多图片二维数组转json
        }
        if (!$this->validateReceiptData($data)) {
            return false;
        }
        $data['account_transfer_no'] = 'LS'. date(Ymd). TbWmsNmIncrementModel::generateNo('LS');//生成流水号
        $data['transfer_type'] = self::B2B_REC;//B2B收款
        $data['swift_code'] = $this->code;
        $data['pay_or_rec'] = 1;
        if (!$this->create($data)) {
            $this->setError(L('创建数据失败'), 3001);
            return false;
        }
        $this->startTrans();
        if (!$this->add()) {
            $this->rollback();
            $this->setError(L('录入失败'), 3000);
            Logs(json_encode($data), __FUNCTION__.' fail', __CLASS__);
            return false;
        }
        $this->commit();
        return true;
    }
    
    /**
     * 收款流水数据验证
     * @param type $data 数据
     * @return boolean
     */
    public function validateReceiptData($data) {
        $rules = [
            'opp_company_name' => 'required|min:2|max:100',
            'company_name' => 'required',
            'company_code' => 'required',
            'open_bank' => 'required',
            'account_bank' => 'required',
            'transfer_voucher' => 'required',
            'transfer_time' => 'required|date',
            'collection_type' => 'required',
            'currency_code' => 'required',
            'amount_money' => 'required|numeric',
            'original_currency' => 'required',
            'original_amount' => 'required|numeric',
            'other_currency' => 'required',
            'other_cost' => 'required|numeric',
            'remitter_currency' => 'required',
            'remitter_cost' => 'required|numeric',
        ];
        $attributes = [
            'opp_company_name' => '付款账户名',
            'company_name' => '收款账户名',
            'company_code' => '收款账户code',
            'open_bank' => '收款银行',
            'account_bank' => '收款银行账号',
            'transfer_voucher' => '收款凭证',
            'transfer_time' => '收款日期',
            'collection_type' => '收款类型',
            'currency_code' => '我方实收金额币种',
            'amount_money' => '我方实收金额',
            'original_currency' => '原始金额币种',
            'original_amount' => '原始金额',
            'other_currency' => '其它费用币种',
            'other_cost' => '其它费用',
            'remitter_currency' => '汇款人费用币种',
            'remitter_cost' => '汇款人费用',
        ];
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
            $this->setError(ValidatorModel::getMessage(), 3002);
            return false;
        }
        
        //判断我方收款银行信息是否存在
        $account_bank = $data['account_bank'];
        $model = new Model();
        $bank_info = $model->table('tb_fin_account_bank t1')
            ->field(['t1.company_code,t1.swift_code, t2.CD_VAL as company_name'])
            ->join('left join tb_ms_cmn_cd t2 on t1.company_code = t2.CD')
            ->where(['t1.account_bank' => $account_bank])->find();
        
        if (empty($bank_info)) {
            $this->setError(L('我方收款银行账号不存在'), 3001);
            return false;
        }
        
        $transfer_time = $data['transfer_time'];
        $current_time = date('Y-m-d',time());
        if (strtotime($transfer_time) > strtotime($current_time)) {
            $this->setError(L('收款日期不能晚于当前日期'), 3003);
            return false;
        }
        
        $amount_money = $data['amount_money'];
        $original_amount = $data['original_amount'];
        $other_cost = $data['other_cost'];
        $remitter_cost = $data['remitter_cost'];
        if ($amount_money <= 0) {
            $this->setError(L('实收金额必须大于0'), 3004);
            return false;
        }
        if ($original_amount <= 0) {
            $this->setError(L('原始金额必须大于0'), 3005);
            return false;
        }
        if ($other_cost < 0) {
            $this->setError(L('其它费用必须大于等于0'), 3006);
            return false;
        }
        if ($remitter_cost < 0) {
            $this->setError(L('汇款人费用必须大于等于0'), 3007);
            return false;
        }
        
        $this->company_code = $bank_info['company_code'];
        $this->company_name = $bank_info['company_name'];
        $this->code = $bank_info['swift_code'];
        return true;
    }

    public function checkRemitter($ret)
    {
        // 付款方原始付款金额 original_amount
        // 收款方实收金额 amount_money
        // 其他费用 other_cost
        $cost = $ret['remitter_cost'];
        if ($ret['original_currency'] === $ret['currency_code']) { // 【付款方原始付款金额】-【收款方实收金额】-【其他费用】
            $cost = bcsub(bcsub($ret['original_amount'], $ret['amount_money'], 8), $ret['other_cost'], 8);
        } else { // 【付款方原始付款金额】/【银行汇率】-【收款方实收金额】-【其他费用】（保留小数点后2位）
            if ($ret['bank_rate']) {
                $cost = bcsub(bcsub(bcdiv($ret['original_amount'], $ret['bank_rate'], 8), $ret['amount_money'], 8), $ret['other_cost'], 8);
            }
        }
        return $cost;
    }

    
    /**
     * 获取日记账详情
     * @param type $request 请求参数
     * @return boolean
     */
    public function getTurnoverDetail($request) {
        $turnover_id = $request['turnover_id'];
        if (empty($turnover_id)) {
            $this->setError(L('流水id为空'), 3000);
            return false;
        }
        $field = [
            "t1.*",
            "t1.create_user as create_user_id",
            "a1.EMP_SC_NM AS create_user_name,a1.M_NAME",
        ];
        $where['t1.id'] = ['eq', $turnover_id];
        //得到日记账详情
        $ret = $this->field($field)
            ->table('tb_fin_account_turnover t1')
            ->join("left join bbm_admin a1 on t1.create_user = a1.M_ID")
            ->where($where)
            ->find();

        if($ret['create_user_id'] == KyribaService::KYRIBA_USER_ID){
            $ret['create_user'] = 'Kyriba';
            $ret['remitter_cost'] = $this->checkRemitter($ret); //根据币种确定汇款人金额
            $ret['remitter_currency'] = $ret['currency_code'];
        }
        if(!$ret['create_user']) $ret['create_user_name'] = $ret['M_NAME'];

        if ($ret) {
            //判断是收款还是付款
            $ret['transaction_type_nme'] = parent::checkTransactionType($ret['transfer_type']);//流水类型
            
            $currency = BaseModel::getCurrency();
            $transfer = BaseModel::transferType();
            $ret['currency_code_name'] =  $currency[$ret['currency_code']];
            $ret['original_currency_name'] =  $currency[$ret['original_currency']];//原始金额币种
            $ret['other_currency_name'] =  $currency[$ret['other_currency']]; //其它费用币种
            $ret['remitter_currencye_name'] =  $currency[$ret['remitter_currency']];//汇款人币种
            $ret['transfer_type_name'] = $transfer [$ret['transfer_type']];//收支方向
            $ret['collection_type_name'] = BaseModel::getCollection() [$ret['collection_type']];//预分方向
            $transfer_voucher = json_decode($ret['transfer_voucher'],true);
            if (!empty($transfer_voucher)) {
                //存储的字段不一样，要转成统一
                foreach ($transfer_voucher as $k => &$v) {
                    if (!empty($v['baseName'])) {
                        $voucher[] = ['name'=> $v['baseName'], 'savename'=>$v['saveName']];
                    }
                }
                if (!empty($voucher)) {
                    $ret['transfer_voucher'] = json_encode($voucher);
                }
            }
            $ret = CodeModel::autoCodeOneVal($ret, ['payment_channel_cd']);
            //得到日记账关联详情
            $field = [
                'claim_amount',
                'order_type',
                'order_no',
                'child_order_no',
                'sale_teams',
                'claim_amount',
                'created_by',
                'created_at',
                'department_1',
                'department_2',
                'department_id_1',
                'department_id_2',
            ];
            $yes_claim_amount = "0.00";
            $sale_teams = BaseModel::saleTeamCd();
            $claim = M('_fin_claim', 'tb_')->field($field)->where(['account_turnover_id' => $turnover_id])->select();
            foreach ($claim as $k => &$v) {
                $yes_claim_amount = bcadd($yes_claim_amount, $v['claim_amount'], 4);
                $v['sale_teams_name'] = $sale_teams[$v['sale_teams']];//销售团队
                $v['transfer_type_name'] = $transfer[$v['order_type']];
                
                if ($v['order_type'] == self::B2B_REC) {
                    //20190620改成（【认领金额】*【实际收款金额】）/【原始金额】
                    $v['claim_amount'] = bcdiv(bcmul($v['claim_amount'], $ret['amount_money'], 2), $ret['original_amount'] ,2);
                }
            }

            //收款流水显示删除按钮
            $ret['is_show_delbtn'] = true;
            $ret['is_pur_loan']    = false;

            if($ret['create_user_id'] == KyribaService::KYRIBA_USER_ID){

                $ret['yes_claim_amount'] = "0.00";//已关联金额
                $ret['no_claim_amount']  =  $ret['amount_money'];//待关联金额(收款方实收金额)
            }else{
                if (parent::isPaymentTransactionType($ret['transfer_type'])) {
                    $amount_money = $ret['original_amount'];//流水总金额
                    //采购应付贷款基本信息金额=原始付款金额
                    $ret['is_pur_loan']    = true;
                    $ret['is_show_delbtn'] = false;

                    //采购贷款已关联的金额 = 贷款+付款手续费
                    $yes_claim_amount = $ret['original_amount'];
                } else {
                    $amount_money = $ret['amount_money'];//流水总金额
                    //20190620改成（【认领金额】*【实际收款金额】）/【原始金额】
                    $yes_claim_amount = bcdiv(bcmul($yes_claim_amount, $ret['amount_money'], 2), $ret['original_amount'] ,2);
                }
                $ret['yes_claim_amount'] = $yes_claim_amount;//已关联金额
                $ret['no_claim_amount']  = bcsub($amount_money, $yes_claim_amount, 2);//待关联金额
                if ($ret['transfer_type'] == self::PUR_POUNDAGE) {
                    //采购应付-收款方实收金额为0
                    $ret['amount_money'] = 0.00;
                }
            }
            $ret['claim_info']       = $claim;
        }

        return $ret;
    }

    //删除收款流水
    public function deleteReceipt($request) {
        $field = [
            "t1.*",
            "cd1.CD_VAL as currency_code_name"
        ];
        $where = ['t1.id' => $request['id']];
        $receipt = $this->table('tb_fin_account_turnover t1')
            ->field($field)
            ->join('left join tb_ms_cmn_cd cd1 on t1.currency_code = cd1.CD')
            ->where($where)
            ->find();
        if (!$receipt || in_array($receipt['transfer_type'], self::$PAYMENT_TYPE)) {
            throw new Exception(L('未找到该日记账流水'));
        }
        $receipt['transfer_type_name'] =self::transferType()[$receipt['transfer_type']];
        $claim = M('fin_claim', 'tb_')->where(['account_turnover_id' => $receipt['id']])->find();
        if ($claim) {
            throw new Exception(L('该笔流水有关联信息，不能删除'));
        }
        $this->addSysDeleted($receipt, $request['reason']);

        if (!$this->delete($request['id'])) {
            throw new Exception(L('删除失败'));
        } else {
            $email = new SMSEmail();
            $title = '流水ID'. $receipt['account_transfer_no']. '已删除';
            $recipient = M('admin', 'bbm_')->where(['M_ID' => $receipt['create_user']])->getField('M_EMAIL');

            $templet = A('Finance')->receipt_email_content();
            $replace_arr = [
                '{title}' => $title,
                '{amount_money}' => $receipt['currency_code_name']. ' '.number_format($receipt['amount_money'], 2),
                '{transfer_type_name}' => $receipt['transfer_type_name'],
                '{create_time}' => $receipt['create_time'],
                '{deteled_time}' => $this->getTime(),
                '{deteled_by}' => DataModel::getUserNameById(session('user_id')),
                '{reason}' => $request['reason'],
            ];
            $content = strtr($templet,$replace_arr);
            $send_res = $email->sendEmail($recipient,$title,$content,'finance@gshopper.com');
            if(!$send_res) {
                throw new Exception(L('邮件发送失败'));
            }
            Logs(json_encode($receipt), '日记账流水删除', __CLASS__);
        }
    }

    /**
     * 删除日记账收款流水记录删除内容
     * @param $receipt 日记账流水记录
     * @param $reason 删除原因
     */
    private function addSysDeleted($receipt, $reason) {
        $user_name = DataModel::userNamePinyin();
        $data = [
            'key' => $receipt['id'],
            'reason' => $reason,
            'request_item' => 'ERP-财务管理-日记账',
            'table_name' => $this->trueTableName,
            'table_content_json' => DataModel::arrToJson($receipt),
            'created_by' => DataModel::getUserNameById($receipt['create_user']),
            'created_at' => $receipt['create_time'],
            'updated_by' => $user_name,
            'updated_at' => $this->getTime(),
            'deleted_by' => $user_name,
            'deleted_at' => $this->getTime(),
        ];

        if (!M('sys_deleted', 'tb_')->add($data)) {
            throw new Exception(L('日记账收款流水删除前保存数据失败'));
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

    //日志账写入
    public function thrTurnOver($params)
    {
        if (!empty($params)) {
            $handlingFee = $params['handlingFee'];
            $accountTransferNo = 'LS' . date(Ymd) . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
            if (is_numeric($handlingFee) && $handlingFee != '0.00') {
                $originalMoney = bcadd($params['amountMoney'], $handlingFee, 4);
            } else {
                $handlingFee = 0;
                $originalMoney = $params['amountMoney'];
            }
            if (is_array($params ['transferVoucher'])) {
                $transferVoucher = json_encode($params ['transferVoucher'], JSON_UNESCAPED_UNICODE);
            } else {
                $voucher['name']     = $params ['transferVoucher'];
                $voucher['savename'] = $params ['transferVoucher'];
                $vouchers[]          = $voucher;
                $transferVoucher     = json_encode($vouchers, JSON_UNESCAPED_UNICODE);
            }
            $this->transferType = $params ['transferType'];
            $this->companyCode = $params ['companyCode'];
            $this->openBank = $params ['openBank'];
            $this->accountBank = $params ['accountBank'];
            $this->transferNo = $params ['transferNo'];
            $this->transferTime = $params ['transferTime'];
            $this->amountMoney = $params['amountMoney'];
            $this->currencyCode = $params ['currencyCode'];
            $this->payOrRec = $params ['payOrRec'];
            $this->companyName = $params ['companyName'];
            $this->childTransferNo = $params ['childTransferNo'];
            $this->accountTransferNo = $accountTransferNo;
            $this->oppCompanyName = $params ['oppCompanyName'];
            $this->oppOpenBank = $params ['oppOpenBank'];
            $this->oppAccountBank = $params ['oppAccountBank'];
            $this->transferVoucher = $transferVoucher;
            $this->swiftCode = $params ['swiftCode'];
            $this->oppSwiftCode = $params ['oppSwiftCode'];
            $this->originalCurrency = $params ['currencyCode'];
            $this->originalAmount = $originalMoney;
            $this->otherCurrency = $params ['currencyCode'];
            $this->otherCost = $handlingFee;
            $this->remitterCurrency = $params ['currencyCode'];
            $this->createUser = $params ['createUser'];
            $this->createTime = $params ['createTime'];
            $this->remitterCost = 0.00;
            $this->create($this->params);
            return $this->add($this->params);
        }
    }

    /**采购付款撤回删除日记账及关联日记账
     * @param $payment_audit_no 付款单号
     * @return bool
     */
    public function deletePurTurnover($payment_audit_no) {
        $where = [
            'transfer_no' => $payment_audit_no,
            'transfer_type' => ['in',[self::PUR_LOAN, self::PUR_POUNDAGE]]
        ];
        $turnover_info = M('account_turnover','tb_fin_')->where($where)->find();
        if (false === M('account_turnover','tb_fin_')->delete($turnover_info['id'])) {
            throw new \Exception(L('删除日记账失败'));
        }
        $claim_model = M('fin_claim', 'tb_');
        $condition = [
            'account_turnover_id' => $turnover_info['id'],
            'order_type'          => ['in', [self::PUR_LOAN, self::PUR_POUNDAGE]]
        ];
        if (false === $claim_model->where($condition)->delete()) {
            throw new \Exception(L('删除日记账关联失败'));
        }
    }

    /**一般付款撤回删除日记账及关联日记账
     * @param $payment_audit_no 付款单号
     * @return bool
     */
    public function deleteGeneralTurnover($payment_audit_no) {
        $where = [
            'transfer_no' => $payment_audit_no,
//            'transfer_type' => ['in',[self::PUR_LOAN, self::PUR_POUNDAGE]],//这是采购的判断，不适用
        ];
        $turnover_info = M('account_turnover','tb_fin_')->where($where)->find();
        if (!M('account_turnover','tb_fin_')->delete($turnover_info['id'])) {
            throw new \Exception(L('删除日记账失败'));
        }
        $claim_model = M('fin_claim', 'tb_');
        $condition = [
            'account_turnover_id' => $turnover_info['id'],
//            'order_type'          => ['in', [self::PUR_LOAN, self::PUR_POUNDAGE]],//这是采购的判断，不适用
        ];
        $claim_info = $claim_model->where($condition)->find();
        if (!empty($claim_info) && !$claim_model->where($condition)->delete()) {
            throw new \Exception(L('删除日记账关联失败'));
        }
    }

    /**
     * 调拨付款单撤回删除日记账及关联日记账
     * @param $payment_audit_no
     * @throws Exception
     */
    public function deleteAlloTurnover($payment_audit_no) {
        $where = [
            'transfer_no' => $payment_audit_no,
            'transfer_type' => ['in',self::$alloExpenseCd]
        ];
        $turnover_info = M('account_turnover','tb_fin_')->where($where)->find();
        if (false === M('account_turnover','tb_fin_')->delete($turnover_info['id'])) {
            throw new \Exception(L('删除日记账失败'));
        }
        $claim_model = M('fin_claim', 'tb_');
        $condition = [
            'account_turnover_id' => $turnover_info['id'],
            'order_type'          => ['in', self::$alloExpenseCd]
        ];
        if (false === $claim_model->where($condition)->delete()) {
            throw new \Exception(L('删除日记账关联失败'));
        }
    }

    /**
     * 售后付款单撤回删除日记账及关联日记账
     * @param $payment_audit_no
     * @throws Exception
     */
    public function deleteRefundTurnover($payment_audit_no) {
        $where = [
            'transfer_no' => $payment_audit_no,
            'transfer_type' => ['in',self::$alloExpenseCd]
        ];
        $turnover_info = M('account_turnover','tb_fin_')->where($where)->find();
        if (false === M('account_turnover','tb_fin_')->delete($turnover_info['id'])) {
            throw new \Exception(L('删除日记账失败'));
        }
        $claim_model = M('fin_claim', 'tb_');
        $condition = [
            'account_turnover_id' => $turnover_info['id'],
            'order_type'          => ['in', self::$alloExpenseCd]
        ];
        if (false === $claim_model->where($condition)->delete()) {
            throw new \Exception(L('删除日记账关联失败'));
        }
    }

    /**
     * 售后付款单撤回删除日记账及关联日记账
     * @param $payment_audit_no
     * @throws Exception
     */
    public function deleteTransferTurnover($payment_audit_no) {
        $where = [
            'transfer_no'   => $payment_audit_no,
            'transfer_type' => self::TRANSFER_PAY
        ];
        $turnover_info = M('account_turnover','tb_fin_')->where($where)->find();
        if (false === M('account_turnover','tb_fin_')->delete($turnover_info['id'])) {
            throw new \Exception(L('删除日记账失败'));
        }
        $claim_model = M('fin_claim', 'tb_');
        $condition = [
            'account_turnover_id' => $turnover_info['id'],
            'order_type'          => self::TRANSFER_PAY
        ];
        if (false === $claim_model->where($condition)->delete()) {
            throw new \Exception(L('删除日记账关联失败'));
        }
    }
}