<?php

/**
 *  PromotionDemandRepository
 */


class PromotionTaskRepository extends Repository
{
    public $model;

    public function __construct()
    {
        parent::__construct();
    }
    public function getFind($condtion,$field="*"){
        $info_data = $this->model->table('tb_ms_promotion_task')
            ->field($field)
            ->where($condtion)
            ->find();
        return $info_data;
    }

    public function getList($condtion,$field="*", $limit = false, $order_by='tb_ms_promotion_task.id desc')
    {
        $list = $this->model->table('tb_ms_promotion_task')
            ->join("INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_demand.promotion_demand_no = tb_ms_promotion_task.promotion_demand_no")
            ->join('LEFT JOIN  tb_ms_promotion_tag  on tb_ms_promotion_demand.tag_id = tb_ms_promotion_tag.id')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }

    /**
     * 预估总花费 USD  排序
     */
    public function getForecastSumPriceList($condtion,$field="*", $limit = false, $order_by='tb_ms_promotion_task.id desc'){
        $list = $this->model->table('tb_ms_promotion_task')
            ->join("INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_demand.promotion_demand_no = tb_ms_promotion_task.promotion_demand_no")
            ->join("LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_ms_promotion_task.forecast_sum_currency_cd")
            ->join('LEFT JOIN  tb_ms_promotion_tag  on tb_ms_promotion_demand.tag_id = tb_ms_promotion_tag.id')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }
    /**
     * 当前累计花费（USD） 排序
     */
    public function getNowTotalPriceList($condtion,$field="*", $limit = false, $order_by='tb_ms_promotion_task.id desc')
    {
        $list = $this->model->table('tb_ms_promotion_task')
            ->join("INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_demand.promotion_demand_no = tb_ms_promotion_task.promotion_demand_no")
            ->join("LEFT JOIN tb_general_payment_detail AS payment_detail_one ON tb_ms_promotion_task.promotion_task_no = payment_detail_one.relation_bill_no")
            ->join("LEFT JOIN tb_pur_payment_audit ON payment_detail_one.payment_audit_id = tb_pur_payment_audit.id")
            ->join("LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_pur_payment_audit.billing_currency_cd COLLATE utf8_general_ci")
            ->join('LEFT JOIN  tb_ms_promotion_tag  on tb_ms_promotion_demand.tag_id = tb_ms_promotion_tag.id')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->group('tb_ms_promotion_task.promotion_task_no')
            ->limit($limit)
            ->select();
        return $list;
    }




    public function getJoinFind($condtion,$field="*")
    {
        $info = $this->model->table('tb_ms_promotion_task')
            ->join("INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_demand.promotion_demand_no = tb_ms_promotion_task.promotion_demand_no")
            ->field($field)
            ->where($condtion)
            ->find();
        return $info;
    }

    public function add($insert_data){
        $res = $this->model->table('tb_ms_promotion_task')->add($insert_data);
        return $res;
    }

    public function addAll($insert_data){
        $res = $this->model->table('tb_ms_promotion_task')->addAll($insert_data);
        return $res;
    }

    public function update($condtion,$update_data){;
        $res = $this->model->table('tb_ms_promotion_task')->where($condtion)->save($update_data);
        return $res;
    }

    // 获取关联付款单
    public function getPaymentAudit($condtion,$field){

        $res = $this->model->table('tb_general_payment_detail as payment_detail_one')
            ->join("LEFT JOIN tb_pur_payment_audit ON payment_detail_one.payment_audit_id = tb_pur_payment_audit.id ")
            ->field($field)
            ->where($condtion)
            ->select();
        return $res;
    }
}