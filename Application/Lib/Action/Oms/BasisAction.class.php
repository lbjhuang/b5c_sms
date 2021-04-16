<?php

/**
 * User: yangsu
 * Date: 18/3/5
 * Time: 16:33
 */
class BasisAction extends BaseAction
{
    public $model;
    public $error_msg;
    public $uuid;

    public $return_data = [
        'status' => 400000,
        'info' => '无数据',
        'data' => ''
    ];
    public $return_success = [
        'status' => 200000,
        'info' => '成功',
        'data' => ''
    ];
    public $return_error = [
        'status' => 400000,
        'info' => '无数据',
        'data' => ''
    ];
    public $return_failed = [
        'status' => 400000,
        'info' => '更新失败',
        'data' => ''
    ];
    /**
     * @param $res_data
     *
     * @return mixed
     */
    protected function ajaxRetrunRes($res_data)
    {
        if (is_string($res_data)) {
            echo $res_data;
            die();
        }
        return $this->ajaxReturn($res_data['data'], $res_data['info'], $res_data['status']);
    }

    /**
     * @param $res
     */
    protected function ajaxRetrunOriginal($res)
    {
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($res);
    }

    /**
     * @param $res
     */
    protected function returnFooter($res)
    {
        if ($res) {
            $return_data = $this->return_success;
            $return_data['data'] = $res;
        } else {
            $return_data = $this->return_error;
        }
        $return_data['info'] = L($return_data['info']);
        $this->ajaxRetrunRes($return_data);
    }

    /**
     * @param        $require_data
     * @param        $id_key
     * @param        $table_name
     * @param string $where_join
     * @param null $Model
     * @param        $del_key
     *
     * @return array
     */
    public function deleteData($require_data, $id_key, $table_name, $where_join = '', $Model = null, $del_key = 'id')
    {
        if (!$Model) {
            $Model = new Model();
        }
        if ($id_key) {
            $array_column = array_column($require_data, $id_key);
        } else {
            $array_column = $require_data;
        }
        $where_del[$del_key] = array('IN', $array_column);
        $Model->table($table_name)->where($where_del);
        if ($where_join) {
            $Model->where($where_join, null, true);
        }
        $res_data = $Model->delete();
        if ($res_data) {
            $res = DataModel::$success_return;
            $res['info'] = "删除 $res_data 条";
        } else {
            $res = DataModel::$error_return;
            $res['info'] = "待删除数据异常";
        }
        return $res;
    }
}