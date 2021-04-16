<?php

/**
 * 物流管理
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/0917
 * Time: 10:58
 */
class LogisticsService
{
    private $model;
    private $logistics_mode_table;

    public function __construct($model = "")
    {
        if (empty($model)) {
            $this->model = new Model();
        } else {
            $this->model = $model;
        }
        $this->logistics_mode_table = M('ms_logistics_mode', 'tb_');
    }

    //重置物流轨迹查询次数，触发换方式查询物流轨迹
    public function resetLogisticsSearchCount($logistics_mode_id)
    {
        $logistics_mode_info = $this->logistics_mode_table->find($logistics_mode_id);
        //数据范围-已出库列表订单
        $where = [
            'op.logistic_cd' => $logistics_mode_info['LOGISTICS_CODE'],
            'op.logistic_model_id' => $logistics_mode_id,
            'ord.WHOLE_STATUS_CD' => 'N001820900',//已出库
            'pac.PUSH_COUNT' => ['egt', 3],//三次以上表明需要换接口查询物流轨迹

        ];
        $total_reset_count = $this->model->table('tb_op_order op')
        ->join('inner join tb_ms_ord_package pac on pac.ORD_ID = op.ORDER_ID and pac.plat_cd = op.PLAT_CD')
        ->join('inner join tb_ms_ord ord on ord.ORD_ID = op.B5C_ORDER_NO')
        ->where($where)
        ->count('op.ID');
        $reset_limit_count = 500;//每次更新订单的数量
        if ($total_reset_count > 0 && $total_reset_count > $reset_limit_count) {
            $list = $this->model->table('tb_op_order op')
                ->field('pac.id')
                ->join('inner join tb_ms_ord_package pac on pac.ORD_ID = op.ORDER_ID and pac.plat_cd = op.PLAT_CD')
                ->join('inner join tb_ms_ord ord on ord.ORD_ID = op.B5C_ORDER_NO')
                ->where($where)
                ->limit($reset_limit_count)
                ->select();
            $package_ids = array_column($list, 'id');
            $reset_res = $this->model->table('tb_ms_ord_package')
                ->where(['id'=>['in', $package_ids]])
                ->save(['PUSH_COUNT' => 0]);
            if (false === $reset_res) {
                throw new \Exception(L('刷新失败'));
            }
            return [
                'total_reset_count' => $total_reset_count,//总需要重置的订单数
                'current_reset_count' => $reset_limit_count,//当前重置的订单数
                'over_reset_count' => $total_reset_count - $reset_limit_count,//剩余需要重置的订单数
            ];
        } else if ($total_reset_count > 0) {
            $list = $this->model->table('tb_op_order op')
                ->field('pac.id')
                ->join('inner join tb_ms_ord_package pac on pac.ORD_ID = op.ORDER_ID and pac.plat_cd = op.PLAT_CD')
                ->join('inner join tb_ms_ord ord on ord.ORD_ID = op.B5C_ORDER_NO')
                ->where($where)
                ->select();
            $package_ids = array_column($list, 'id');
            $reset_res = $this->model->table('tb_ms_ord_package')
                ->where(['id'=>['in', $package_ids]])
                ->save(['PUSH_COUNT' => 0]);
            if (false === $reset_res) {
                throw new \Exception(L('刷新失败'));
            }
            return [
                'total_reset_count' => $total_reset_count,//总需要重置的订单数
                'current_reset_count' => $total_reset_count,//当前重置的订单数
                'over_reset_count' => 0,//剩余需要重置的订单数
            ];
        }
    }
}