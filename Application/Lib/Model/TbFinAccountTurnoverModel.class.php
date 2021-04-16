<?php

class TbFinAccountTurnoverModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_turnover';

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
            $this->transfer_type       = $params ['transferType'];
            $this->company_code        = $params ['companyCode'];
            $this->open_bank           = $params ['openBank'];
            $this->account_bank        = $params ['accountBank'];
            $this->transfer_no         = $params ['transferNo'];
            $this->transfer_time       = $params ['transferTime'];
            $this->amount_money        = $params['amountMoney'];
            $this->currency_code       = $params ['currencyCode'];
            $this->pay_or_rec          = $params ['payOrRec'];
            $this->company_name        = $params ['companyName'];
            $this->child_transfer_no   = $params ['childTransferNo'];
            $this->account_transfer_no = $accountTransferNo;
            $this->opp_company_name    = $params ['oppCompanyName'];
            $this->opp_open_bank       = $params ['oppOpenBank'];
            $this->opp_account_bank    = $params ['oppAccountBank'];
            $this->transfer_voucher    = $transferVoucher;
            $this->swift_code          = $params ['swiftCode'];
            $this->opp_swift_code      = $params ['oppSwiftCode'];
            $this->original_currency   = $params ['currencyCode'];
            $this->original_amount     = $originalMoney;
            $this->other_currency      = $params ['currencyCode'];
            $this->other_cost          = $handlingFee;
            $this->remitter_currency   = $params ['currencyCode'];
            $this->create_user         = $params ['createUser'];
            $this->create_time         = $params ['createTime'];
            $this->remitter_cost       = 0.00;
            $this->payment_channel_cd  = $params ['paymentChannelCd'];
            $this->remark              = $params ['remark'];
            $this->trade_type          = $params ['tradeType'] ? : 0;
            return $this->add();
        }
    }

    //待分配方向的日记账列表数据
    public static function getWaitingReceiveList($params)
    {
        $where['collection_type'] = ['EXP','IS NULL'];
        $where['transfer_type'] = ['in', parent::getTransactionTypeCds('收款')];
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        !empty($params['transfer_time_start']) && $where['transfer_time'][] = ['egt', $params['transfer_time_start']];
        !empty($params['transfer_time_end']) && $where['transfer_time'][] = ['elt', $params['transfer_time_end']." 23:59:59"];
        !empty($params['create_time_start']) && $where['create_time'][] = ['egt', $params['create_time_start']];
        !empty($params['create_time_end']) && $where['create_time'][] = ['elt', $params['create_time_end']." 23:59:59"];
        !empty($params['company_code']) && $where['company_code'] =  $params['company_code'];
        !empty($params['opp_company_name']) && $where['opp_company_name'] = ['like', '%'.$params['opp_company_name'].'%'];
        !empty($params['open_bank']) && $where['open_bank'] = ['like', '%'.$params['open_bank'].'%'];
        !empty($params['account_bank']) && $where['account_bank'] = ['like', '%'.$params['account_bank'].'%'];

        $query =  M('account_turnover','tb_fin_')->field(['id','opp_company_name','company_code','open_bank','account_bank','currency_code','amount_money','transfer_time','create_time','remark'])->where($where);
        $query1 = clone $query;
        $total = $query1->count();
        $offset = ($params['page'] - 1) * $params['page_size'];
        //分页超出则置为第一页开始
        if($total /$params['page_size'] <= $params['page']){
            $offset = 0;
        }

        $list = $query->order('create_time desc')->limit($offset . ',' . $params['page_size'])->select();
        foreach ($list as &$lv){
            $lv['company_name'] = cdVal($lv['company_code']);
            $lv['currency_code'] = cdVal($lv['currency_code']);
            $lv['amount_money'] = number_format($lv['amount_money'],'2');
        }
        return ['code' => 2000, 'msg' => '查询成功', 'data' => [
            'list' => $list,
            'total' => (int) $total,
            'page' => $params['page'],
            'page_size' => $params['page_size'],
            'parameterMap'=>$params
        ]];
    }


    //添加备注
    public function addRemark($params){
        $update['our_remark'] = $params['our_remark'];
        $res =  M('account_turnover','tb_fin_')->where('id ='.$params['id'])->save($update);
        return $res;
    }
}