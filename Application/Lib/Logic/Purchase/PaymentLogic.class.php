<?php
/**
 * User: yuanshixiao
 * Date: 2018/9/25
 * Time: 13:53
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class PaymentLogic extends BaseLogic
{
    static $model;

    public function model() {
        if(self::$model) {
            return self::$model;
        }else {
            return self::$model = D('TbPurPayment');
        }
    }

    public function paymentConfirm($confrim_data) {
        if($confrim_data['has_account']) {
            $this->accountingConfirm($confrim_data['account_data']);
        }
    }

    public function accountingConfirm($confirm_data) {


    }

    // 算作抵扣金
    public function regardAsDeduction($param) {
        try {
            $model = new Model();
            $model->startTrans();
            $order = $model
                ->table('tb_pur_relevance_order')
                ->alias('t')
                ->field('supplier_id,supplier_id_en,sp_charter_no,our_company,amount_currency,procurement_number,supplier_new_id')
                ->join('tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['t.relevance_id'=>$param['relevance_id']])
                ->find();
            $deduction_param = [
                'sp_charter_no' => $order['sp_charter_no'],
                'supplier_name_cn' => $order['supplier_id'],
                'supplier_name_en' => $order['supplier_id_en'],
                'our_company_cd' => $order['our_company'],
                'our_company_name' => cdVal($order['our_company']),
                'deduction_currency_cd' => $order['amount_currency'],
                'deduction_amount' => $param['amount_deduction'],
                'remark' => $param['remark_deduction'],
                'order_no' => $order['procurement_number'],
                'turnover_type' => 2,
                'deduction_type_cd' => 'N002660100', // 多付未退款
                'deduction_voucher' => [["name"=>"","savename"=>""]],
                'supplier_new_id' => $order['supplier_new_id']
            ];
            $deduction_detail_id = (new PurService())->addDeductionAmount($deduction_param, $model);
            ELog::add('算作抵扣金的记录'.$deduction_param,ELog::INFO);
            $model->commit();
            return $deduction_detail_id;
        } catch (Exception $exception) {
            $model->rollback();
            $this->error = $exception->getMessage();
            ELog::add(['info'=>'算作抵扣金失败：'.$exception->getMessage(),'request'=>json_encode($deduction_param)],ELog::ERR);
            return false;
        }
    }

    // 扣减抵扣金
    public function cutUseDeduction($param)
    {
        $bool = false;
        if (empty($param)) return $bool; // 根据order_id获取相关信息
        $order = M('relevance_order', 'tb_pur_')
            ->alias('t')
            ->field('relevance_id,supplier_id,supplier_id_en,sp_charter_no,our_company,amount_currency,procurement_number,a.supplier_new_id')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->where(['t.relevance_id' => $param['relevance_id']])
            ->find();

        //获取供应商抵扣金账户 我方公司-供应商-币种
        // 优先根据供应商id来获取抵扣金账户
        $where = [
            'our_company_cd'  => $order['our_company'],
            'deduction_currency_cd'  => $order['amount_currency'],
        ];
        if (!empty($order['supplier_new_id'])) {
          $where['supplier_id'] = $order['supplier_new_id'];
        } else {
          $where['supplier_name_cn'] = $order['supplier_id'];
        }
        /*$deduction = M('deduction', 'tb_pur_')->where($where)->find();
        if (!$deduction) {
            ELog::add(['info'=>'扣减抵扣金失败：供应商抵扣金账户不存在','where_map'=>json_encode($where), 'request' => json_encode($param)],ELog::ERR); // 19542 抵扣金扣减逻辑补充
            return $bool;
        }*/
        /*if ($deduction['over_deduction_amount'] < $param['amount_deduction']) {
            $log_arr['over_deduction_amount'] = $deduction['over_deduction_amount'];
            $log_arr['amount_deduction'] = $param['amount_deduction'];
            ELog::add(['info'=>'扣减抵扣金失败：供应商账户余额小于当前抵扣金额','where_map'=>json_encode($log_arr), 'request' => json_encode($param)],ELog::ERR);
            return $bool;
        }*/

        $param_final['relevance_id'] = $order['relevance_id'];
        $param_final['amount_deduction'] = $param['amount_deduction'];
        $param_final['remark_deduction'] = $param['remark_deduction'] ? $param['remark_deduction'] : '';
        $param_final['deduction_type_cd'] = $param['deduction_type_cd'] ? $param['deduction_type_cd'] : 'N002660100'; // 多付未退款
        $param_final['voucher_deduction'] = json_encode([["name"=>"","savename"=>""]]);
        $deduction_detail_id = (new PurPaymentService())->useDeduction($param_final, true);
        if (!$deduction_detail_id) {
            ELog::add(['info'=>'扣减抵扣金失败：生成抵扣金详情记录失败','param_final'=>json_encode($param_final), 'request' => json_encode($param)],ELog::ERR);
            return $bool;
        }
        return $deduction_detail_id;
    }

    // 算作赔偿抵扣金
    public function regardAsDeductionCompensation($param) {
        try {
            $model = new Model();
            $model->startTrans();
            $order = $model
                ->table('tb_pur_relevance_order')
                ->alias('t')
                ->field('supplier_id,our_company,amount_currency,procurement_number,supplier_new_id')
                ->join('tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['t.relevance_id'=>$param['relevance_id']])
                ->find();
            $deduction_param = [
                'our_company_cd' => $order['our_company'],
                'deduction_currency_cd' => $order['amount_currency'],
                'deduction_amount' => $param['amount_deduction'],
                'remark' => $param['remark_deduction'],
                'order_no' => $order['procurement_number'],
                'turnover_type' => 2,
                'deduction_type_cd' => $param['deduction_type_cd'], // 余额转账
                'deduction_voucher' => [["name"=>"","savename"=>""]],
                'supplier_new_id' => $order['supplier_new_id']
            ];
            $deduction_detail_id = (new PurService())->addDeductionAmountCompensation($deduction_param, $model);
            ELog::add('算作赔偿返利抵扣金的记录'.$deduction_param,ELog::INFO);
            $model->commit();
            return $deduction_detail_id;
        } catch (Exception $exception) {
            $model->rollback();
            $this->error = $exception->getMessage();
            ELog::add(['info'=>'算作赔偿返利抵扣金失败：'.$exception->getMessage(),'request'=>json_encode($deduction_param)],ELog::ERR);
            return false;
        }
    }

    // 使用赔偿抵扣金
    public function cutUseDeductionCompensation($param)
    {
        $bool = false;
        if (empty($param)) return $bool;
        $param_final['relevance_id'] = $param['relevance_id'];
        $param_final['amount_deduction'] = $param['amount_deduction'];
        $param_final['remark_deduction'] = $param['remark_deduction'] ? $param['remark_deduction'] : '';
        $param_final['deduction_type_cd'] = 'N002870023'; // 余额转账
        $param_final['voucher_deduction'] = json_encode([["name"=>"","savename"=>""]]);
        $deduction_detail_id = (new PurPaymentService())->useDeductionCompensation($param_final);
        if (!$deduction_detail_id) {
            ELog::add(['info'=>'赔偿金处理失败：生成赔偿金详情记录失败','param_final'=>json_encode($param_final), 'request' => json_encode($param)],ELog::ERR);
            return $bool;
        }
        return $deduction_detail_id;
    }
}