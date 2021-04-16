<?php

/**
 * User: yangsu
 * Date: 18/11/5
 * Time: 11:03
 */
class PendingOrderAction extends BasisAction
{
    public function pending_list()
    {
        if (IS_POST) {
            $data = $this->jsonParams(false);
            $OrderEsModel = new OrderEsModel();
            $data = PendingModel::filterListsData($data);
            $res = $OrderEsModel->lists($data);
            if ($res['data']) {
                $res['data'] = PatchModel::getStock($res['data']);
            } else {
                $res['data'] = [];
            }
            $this->ajaxSuccess($res);
        } else {
            $this->display();
        }
    }

    public function electronic_order() {
        try {
            $data = DataModel::getData(true)['data']['orders'];
            foreach ($data as $value) {
                $order_info[] = [
                    'order_id' => $value['thr_order_id'],
                    'plat_cd'  => $value['plat_cd'],
                ];
            }
            //判断是否退款
            $res_refund = (new OmsAfterSaleService())->checkOrderRefund($order_info);
            if (true !== $res_refund) {
                $this->ajaxRetrunRes($res_refund);
            }
            $res = (new PatchModel())->orderStatusUpd($data, true);
            //��������ˢ�¶���
            (new OmsService())->updateOrderFromEs($data,'thr_order_id','plat_cd');

            if ($res) {
                $return_data = $this->return_success;
            } else {
                $return_data = $this->return_error;
            }
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxRetrunRes($return_data);
    }

    public function return_to_patch() {
        try {
            $params = $this->jsonParams();
            PendingModel::returnToPatch($params['data']['orders']);

            //��������ˢ�¶���
            (new OmsService())->updateOrderFromEs($params['data']['orders'],'thr_order_id','plat_cd');

            $this->ajaxSuccess();
        }catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage());
        }
    }

}
