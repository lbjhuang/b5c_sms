<?php

/**
 * Class OrderExportTemplateRepository
 */

class OrderExportTemplateRepository extends Repository
{
    public $model;

    public function __construct()
    {
        parent::__construct();
    }
    public function getFind($condtion,$field="*"){
        $info_data = $this->model->table('tb_op_order_export_template')
            ->field($field)
            ->where($condtion)
            ->find();
        return $info_data;
    }

    public function getList($condtion,$field="tb_op_order_export_template.*", $limit = false, $order_by='tb_op_order_export_template.id desc')
    {
        $list = $this->model->table('tb_op_order_export_template')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }

    public function add($insert_data){
        $res = $this->model->table('tb_op_order_export_template')->add($insert_data);
        return $res;
    }

    public function addAll($insert_data){
        $res = $this->model->table('tb_op_order_export_template')->addAll($insert_data);
        return $res;
    }

    public function update($condtion,$update_data){;
        $res = $this->model->table('tb_op_order_export_template')->where($condtion)->save($update_data);
        return $res;
    }
    public function delete($condtion){;
        $res = $this->model->table('tb_op_order_export_template')->where($condtion)->delete();
        return $res;
    }
}