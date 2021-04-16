<?php
/**
 * 日记账关联模型
 * User: fuming
 * Date: 2018/12/12
 */

class TbFinClaimModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_claim';

    protected $_auto = [
        ['created_at', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['created_by', 'getName', Model::MODEL_INSERT, 'callback'],
        ['updated_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
        ['updated_by', 'getName', Model::MODEL_UPDATE, 'callback']
    ];
    
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];
    const B2B_REC = 'N001950200';//B2B收款
    const PUR_LOAN = 'N001950100';//采购贷款

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
            "t1.account_transfer_no as accountTransferNo",
            "t1.transfer_type as transferType",
            "c1.account_turnover_id as accountTurnoverId",
            "c1.order_no as orderNo",
            "c1.child_order_no as childOrderNo",
            "c1.sale_teams as saleTeams",
            "t1.currency_code as currencyCode",
            "c1.claim_amount as claimAmount",
            "t1.company_name as companyName",
            "t1.open_bank as openBank",
            "t1.account_bank as accountBank",
            "t1.opp_company_name as oppCompanyName",
            "t1.opp_open_bank as oppOpenBank",
            "t1.opp_account_bank as oppAccountBank",
            "t1.transfer_time as transferTime",
            
            "t1.original_amount as originalAmount",
            "t1.amount_money as amountMoney",
            "c1.order_type as orderType",
            
            "c1.created_at as createAt",
            "c1.created_by as createdBy",
            "cd1.CD_VAL as transferName",//收支方向
            "cd2.CD_VAL as saleTeamsName",//销售团队
            "cd3.CD_VAL as currencyName",//币种
            "t1.bank_reference_no",
            "t1.bank_payment_reason",
            "t1.remark"
        ];
        //query
        //        $this->subWhere('_string','t1.account_bank = t2.account_bank')
        $this->subWhere('c1.order_type', ['eq', $params ['transferType']])
            ->subWhere('t1.company_code', ['eq', $params ['companyCode']])
            ->subWhere('t1.account_bank', ['like', $params ['accountBank']])
            ->subWhere('t1.transfer_no', ['like', $params ['transferNo']])
            ->subWhere('t1.transfer_time', ['xrange', [$params ['transferStartTime'], $params ['transferEndTime']]])
            ->subWhere('t1.open_bank', ['like', $params ['openBank']])
            ->subWhere('t1.account_transfer_no', ['eq', $params ['accountTransferNo']])
            ->subWhere('t1.opp_company_name', ['like', $params ['oppCompanyName']])
            ->subWhere('t1.opp_open_bank', ['like', $params ['oppOpenBank']])
            ->subWhere('t1.opp_account_bank', ['like', $params ['oppAccountBank']])
            ->subWhere('t1.currency_code', ['like', $params ['currencyCode']])
            ->subWhere('c1.order_no', ['eq', $params ['orderNo']])
            ->subWhere('c1.child_order_no', ['eq', $params ['childOrderNo']])
            ->subWhere('c1.sale_teams', ['eq', $params ['saleTeams']])
            ->subWhere('c1.created_by', ['eq', $params ['createdBy']])
            ->subWhere('c1.created_at', ['xrange', [$params ['createStartTime'], $params ['createEndTime']]])
            ->subWhere('t1.bank_reference_no', ['eq', $params ['bank_reference_no']]);
            if (!empty($transactionType )) {
                if ($transactionType != parent::$COLLECTION_TYPE) {
                    static::$where['t1.transfer_type'] = ['in', parent::$PAYMENT_TYPE];//收款
                } else {
                    static::$where['t1.transfer_type'] = ['not in', parent::$PAYMENT_TYPE];//付款
                }
            }
            
        $join1 = 'left join tb_fin_account_turnover t1 on c1.account_turnover_id = t1.id';
        $join2 = 'left join tb_ms_cmn_cd cd1 on c1.order_type = cd1.CD';//查找收支方向
        $join3 = 'left join tb_ms_cmn_cd cd2 on c1.sale_teams = cd2.CD';//查找销售团队
        $join4 = 'left join tb_ms_cmn_cd cd3 on t1.currency_code = cd3.CD';//查找币种
        $count = $this->table('tb_fin_claim c1')->join($join1)->join($join2)->join($join3)->join($join4)->where(static::$where) ->count();
        $Page  = new Page($count, $pageSize);
        //exec
        $subQuery = $this->field($field)
            ->table('tb_fin_claim c1')
            ->join($join1)
            ->join($join2)
            ->join($join3)
            ->join($join4)
            ->where(static::$where);
        if ($isExcel == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->order('c1.id desc')->select();
        
        //判断是收款还是付款
        foreach ($ret as $k => &$v) {
            $v['transactionType'] = parent::checkTransactionType($v['transferType']);
            if ($v['orderType'] == self::B2B_REC) {
                //20190620改成（【认领金额】*【实际收款金额】）/【原始金额】
                $v['claimAmount'] = bcdiv(bcmul($v['claimAmount'], $v['amountMoney'], 2), $v['originalAmount'] ,2);
            }
//            if (in_array($v['transferType'], self::$PAYMENT_TYPE)) {
//                //采购贷款展示贷款+贷款手续费的金额
//                $claims = $this->where(['account_turnover_id' => $v['accountTurnoverId']])->select();
//                if (count($claims) > 1 && $v['orderType'] == self::PUR_LOAN) {
//                    $totalMoney = 0.00;
//                    foreach ($claims as $claim) {
//                        $totalMoney = bcadd($totalMoney, $claim['claim_amount'], 2);
//                    }
//                    $v['claimAmount'] = $totalMoney;
//                }
//            }
        }

        $this->count            = $count;
        $this->pageIndex        = $pageIndex;
        $this->pageSize         = $pageSize;
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
     * 日记账关联写入采购应付记录
     * @param $turnover_id
     * @param $pur_info
     * @return bool
     */
    public function addPurToTurnoverRelation($turnover_id, $pur_info) {
//        if (!$this->validateRelationData($params)) {
//            return false;
//        }
        $data = [];
        array_map(function($params) use ($turnover_id, &$data){
            if($params['expense'] && $params['expense'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950500',//采购付款手续费
                    'order_id'            => $params['order_id'],
                    'order_no'            => $params['procurement_number'],
                    'child_order_no'      => $params['payment_no'],
                    'sale_teams'          => $params['sell_team'],
                    'claim_amount'        => $params['expense'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
            if($params['amount_account'] && $params['amount_account'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950100',//采购贷款
                    'order_id'            => $params['order_id'],
                    'order_no'            => $params['procurement_number'],
                    'child_order_no'      => $params['payment_no'],
                    'sale_teams'          => $params['sell_team'],
                    'claim_amount'        => $params['amount_account'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
        }, $pur_info);
        if (true !== $turnover_id && !empty($data)) return $this->addAll($data);
        return true;
    }

    /**
     * 日记账关联写入一般付款应付记录
     * @param $turnover_id
     * @param $pur_info
     * @return bool
     */
    public function addGeneralToTurnoverRelation($turnover_id, $pur_info) {
//        if (!$this->validateRelationData($params)) {
//            return false;
//        }
        $data = [];
        array_map(function($params) use ($turnover_id, &$data){
            if($params['billing_fee'] && $params['billing_fee'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950500',//一般付款手续费
                    'order_id'            => 0,
                    'order_no'            => $params['payment_no'],
                    'child_order_no'      => '',
                    'sale_teams'          => '',
                    'department_1'        => $params['department_1'],
                    'department_2'        => $params['department_2'],
                    'department_id_1'     => $params['department_id_1'],
                    'department_id_2'     => $params['department_id_2'],
                    'claim_amount'        => round($params['subtotal'] / $params['payable_amount_after'] * $params['billing_fee'], 4),
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
            if($params['billing_amount'] && $params['billing_amount'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => $this->getOrderTypeByCode($params['payment_type_val'], $params['subdivision_type_val']),//一般付款
                    'order_id'            => 0,
                    'order_no'            => $params['payment_no'],
                    'child_order_no'      => '',
                    'sale_teams'          => '',
                    'department_1'        => $params['department_1'],
                    'department_2'        => $params['department_2'],
                    'department_id_1'     => $params['department_id_1'],
                    'department_id_2'     => $params['department_id_2'],
                    'claim_amount'        => round($params['subtotal'] / $params['payable_amount_after'] * $params['billing_amount'], 4),
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
        }, $pur_info);
        if (true !== $turnover_id && !empty($data)) {
            $res = $this->addAll($data);
            if (!$res) {
                @SentinelModel::addAbnormal('kyriba生成日记账关联失败',$turnover_id,[$data,[$this->getLastSql()],$pur_info],'kyriba_notice');
            }
            return $res;
        }
        return true;
    }

    //根据code获取收支方向
    ////PaymentTypeAndSubdivisionType
    public function getOrderTypeByCode($payment_type_val, $subdivision_type_val)
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $where['CD_VAL'] = $payment_type_val . '-' . $subdivision_type_val;
        $ret = $model->field('CD')->where($where)->find();
        return $ret['CD'];
    }

    //数据验证
    public function validateRelationData($data) {
        $rules = [
            'turnoverId' => 'required|integer',
            'orderId' => 'required',
            'transferNo' => 'required',
            'childTransferNo' => 'required',
            'saleTeams' => 'required',
            'transferType' => 'required',
        ];
        $attributes = [
            'turnoverId' => '日记账id',
            'orderId' => '订单id',
            'transferNo' => '主订单号',
            'childTransferNo' => '子订单号',
            'saleTeams' => '销售团队',
            'transferType' => '收支方向',
        ];
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
//            $this->setError(ValidatorModel::getMessage(), 3002);
            $this->setError(L('写入日记账关联验证失败'), 3002);
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

    /**
     * 日记账关联写入调拨应付记录
     * @param $turnover_id
     * @param $all_info
     * @return bool
     */
    public function addAlloToTurnoverRelation($turnover_id, $all_info) {
        $data = [];
        array_map(function($params) use ($turnover_id, &$data){
            if($params['expense'] && $params['expense'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950500',//付款手续费
                    'order_id'            => $params['id'],
                    'order_no'            => $params['allo_no'],
                    'child_order_no'      => $params['payment_no'],
                    'sale_teams'          => $params['allo_in_team'],
                    'claim_amount'        => $params['expense'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
            if($params['amount_account'] && $params['amount_account'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => $params['cost_sub_cd'],
                    'order_id'            => $params['id'],
                    'order_no'            => $params['allo_no'],
                    'child_order_no'      => $params['payment_no'],
                    'sale_teams'          => $params['allo_in_team'],
                    'claim_amount'        => $params['amount_account'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
        }, $all_info);
        if (true !== $turnover_id && !empty($data)) return $this->addAll($data);
        return true;
    }



    /**
     * 日记账关联写入售后单记录
     * @param $turnover_id
     * @param $refund_info
     * @return bool
     */
    public function addRefundToTurnoverRelation($turnover_id, $refund_info) {
        $data = [];
        array_map(function($params) use ($turnover_id, &$data){
            if($params['expense'] && $params['expense'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950500',//付款手续费
                    'order_id'            => $params['id'],
                    'order_no'            => $params['order_no'],
                    'child_order_no'      => $params['after_sale_no'],
                    'sale_teams'          => $params['refund_in_team'],
                    'claim_amount'        => $params['expense'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
            if($params['amount_account'] && $params['amount_account'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950607',
                    'order_id'            => $params['id'],
                    'order_no'            => $params['order_no'],
                    'child_order_no'      => $params['after_sale_no'],
                    'sale_teams'          => $params['refund_in_team'],
                    'claim_amount'        => $params['amount_account'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
        }, $refund_info);
        if (true !== $turnover_id && !empty($data)) return $this->addAll($data);
        return true;
    }

    /**
     * 日记账关联写入转账换汇记录
     * @param $turnover_id
     * @param $transfer_info
     * @return bool
     */
    public function addTransferToTurnoverRelation($turnover_id, $transfer_info) {
        $data = [];
        array_map(function($params) use ($turnover_id, &$data){
            if($params['billing_fee'] && $params['billing_fee'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950500',//付款手续费
                    'order_id'            => 0,
                    'order_no'            => $params['transfer_no'],
                    'child_order_no'      => '',
                    'sale_teams'          => '',
                    'claim_amount'        => $params['billing_fee'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
            if($params['billing_amount'] && $params['billing_amount'] > 0) {
                $data[] = [
                    'account_turnover_id' => $turnover_id,
                    'order_type'          => 'N001950300',//划转转出
                    'order_id'            => 0,
                    'order_no'            => $params['transfer_no'],
                    'child_order_no'      => '',
                    'sale_teams'          => '',
                    'claim_amount'        => $params['billing_amount'],
                    'created_at'          => $params['billing_at'],
                    'created_by'          => $params['billing_by'],
                ];
            }
        }, $transfer_info);
        if (true !== $turnover_id && !empty($data)) return $this->addAll($data);
        return true;
    }




}