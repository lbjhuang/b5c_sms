<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 16:19
 */

class InvoiceAction extends BaseAction
{
    public function invoice_return() {
        $id     = $this->jsonParams()['id'];
        $return_reason     = $this->jsonParams()['return_reason'];
        $logic  = new InvoiceLogic();
        $res    = $logic->invoiceReturn($id, $return_reason);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function invoice_confirmed_return()
    {
        $id     = I('post.id');
        $return_reason     = I('post.return_reason');
        $logic  = new InvoiceLogic();
        $res    = $logic->invoiceConfirmedReturn($id, $return_reason);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function invoice_del() {
        $id     = $this->jsonParams()['id'];
        $logic  = new InvoiceLogic();
        $res    = $logic->invoiceDel($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }
}