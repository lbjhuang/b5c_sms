<?php

/**
 * Class PromotionTagRepository
 */


class PromotionTagRepository extends Repository
{
    public $model;

    public function __construct()
    {
        parent::__construct();
    }
    public function getFind($condtion,$field="*"){
        $info_data = $this->model->table('tb_ms_promotion_tag')
            ->field($field)
            ->where($condtion)
            ->find();
        return $info_data;
    }

    public function getList($condtion,$field="*", $limit = false, $order_by='id desc')
    {
        $list = $this->model->table('tb_ms_promotion_tag')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }

    public function add($insert_data){
        $res = $this->model->table('tb_ms_promotion_tag')->add($insert_data);
        return $res;
    }

    public function addAll($insert_data){
        $res = $this->model->table('tb_ms_promotion_tag')->addAll($insert_data);
        return $res;
    }

    public function update($condtion,$update_data){;
        $res = $this->model->table('tb_ms_promotion_tag')->where($condtion)->save($update_data);
        return $res;
    }
}