<?php
/**
 * User: yangsu
 * Date: 19/7/26
 * Time: 14:40
 */


class EsAction extends BaseAction
{
    public function getB2cOrder()
    {
        $order_es = null;
        if (IS_POST) {
            $order_id = trim(I('order_id'));
            $query['query_string']['query'] = "orderId:{$order_id}";
            $EsModel = new EsModel();
            $order_es = $EsModel->search($query)['hits']['hits'];
            if (empty($order_es)) {
                $order_es = null;
            }
        }
        $this->assign('order_es', $order_es);
        $this->assign('order_id', $order_id);
        $this->display('get_b2c_order');
    }

}