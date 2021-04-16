<?php

class RepeatPayAction extends BasisAction
{

    public $repeatPayService;
    public $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model               = new \Model();
        $this->repeatPayService = new RepeatPayService($this->model);
    }

    public function index()
    {
        $this->display('DuplicatePaymentOrder/duplicate-payment-order-list');
    }

    public function lists()
    {
        try {
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->repeatPayService->getRepeatList();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function update()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->valid_param($request_data);
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->repeatPayService->changeRepeatStatus($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function valid_param($data = [])
    {
        if (empty($data['orderIds'])) {
            throw new Exception('订单号为空');
        }
    }
}