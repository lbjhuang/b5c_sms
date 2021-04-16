<?php


class CustomsRepository extends Repository
{

    public function updateInfo($where, $save)
    {
        return M('custom_ord_trade', 'tb_lgt_')->where($where)->save($save);
    }
    public function getDataInfo($field, $param)
    {
          return M('custom_ord_trade', 'tb_lgt_')
            ->alias('t')
            ->field($field)
            ->join("LEFT JOIN tb_lgt_custom_declare_record dr ON t.main_order_id = dr.main_order_id")
            ->where($param)
            ->find();

    }
    /**
     * 列表
     * @param $paymentId
     * @return mixed
     */
    public function getList($where,$pages)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $count = M('custom_ord_trade', 'tb_lgt_')
            ->join("LEFT JOIN tb_lgt_custom_declare_record ON tb_lgt_custom_ord_trade.main_order_id = tb_lgt_custom_declare_record.main_order_id")
            ->where($where)
            ->count();
        $list = M('custom_ord_trade', 'tb_lgt_')
            ->field('tb_lgt_custom_ord_trade.*,tb_lgt_custom_declare_record.out_request_no,tb_lgt_custom_declare_record.return_status')
            ->join("LEFT JOIN tb_lgt_custom_declare_record ON tb_lgt_custom_ord_trade.main_order_id = tb_lgt_custom_declare_record.main_order_id")
            ->where($where)
            ->order('tb_lgt_custom_ord_trade.id desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        return [$list, $count];
    }

    /**
     * 导出数据
     * @param $paymentId
     * @return mixed
     */
    public function getData($where)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $list = M('custom_ord_trade', 'tb_lgt_')
            ->field('tb_lgt_custom_ord_trade.*,tb_lgt_custom_declare_record.out_request_no,tb_lgt_custom_declare_record.return_status')
            ->join("LEFT JOIN tb_lgt_custom_declare_record ON tb_lgt_custom_ord_trade.main_order_id = tb_lgt_custom_declare_record.main_order_id")
            ->where($where)
            ->select();
        return $list;
    }










}