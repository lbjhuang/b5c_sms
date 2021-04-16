<?php

/**
 *  PromotionDemandRepository
 */


class PromotionDemandRepository extends Repository
{
    public $model;

    public function __construct()
    {
        parent::__construct();
    }
    public function getFind($condtion,$field="*"){
        $info_data = $this->model->table('tb_ms_promotion_demand')
            ->field($field)
            ->where($condtion)
            ->find();
        return $info_data;
    }

    public function getList($condtion,$field="tb_ms_promotion_demand.*", $limit = false, $order_by='tb_ms_promotion_demand.id desc')
    {
        $list = $this->model->table('tb_ms_promotion_demand')
            ->join('left join tb_ms_promotion_tag  on tb_ms_promotion_demand.tag_id = tb_ms_promotion_tag.id')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }

    public function add($insert_data){
        $res = $this->model->table('tb_ms_promotion_demand')->add($insert_data);
        return $res;
    }

    public function addAll($insert_data){
        $res = $this->model->table('tb_ms_promotion_demand')->addAll($insert_data);
        return $res;
    }

    public function update($condtion,$update_data){;
        $res = $this->model->table('tb_ms_promotion_demand')->where($condtion)->save($update_data);
        return $res;
    }
}