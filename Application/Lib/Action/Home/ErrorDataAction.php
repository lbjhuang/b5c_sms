<?php
/**
 * Class ErrorDataAction
 */

class ErrorDataAction extends BaseAction
{
    private $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Model();
    }

    public function showErrorB2bNumber()
    {
        $sql = "SELECT
                    *
                FROM
                    (
                        SELECT
                            tb_b2b_doship.PO_ID,
                            tb_b2b_doship.todo_sent_num,
                            tb_b2b_doship.update_time,
                            SUM(
                                tb_b2b_goods.TOBE_DELIVERED_NUM
                            ) AS sum
                        FROM
                            tb_b2b_doship,
                            tb_b2b_goods
                        WHERE
                            tb_b2b_doship.ORDER_ID = tb_b2b_goods.ORDER_ID
                        GROUP BY
                            tb_b2b_goods.ORDER_ID
                    ) t1
                WHERE
                    t1.todo_sent_num != sum
                ";
        $db = $this->model->query($sql);
        if (!empty($db)) {
            @SentinelModel::addAbnormal('B2b 订单状态异常', 'B2b 订单状态异常', $db);
        }
    }
}