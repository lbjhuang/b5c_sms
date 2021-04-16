<?php
/**
 * User: shenmo
 * Date: 2020/2/25
 * Time: 14:26
 */

class IncomeCostAction extends ReportBaseAction
{
    //收入成本报表
    public function incomeCost() {
        $this->display('incomeCost');
    }

    public function list_data($request_data = null) {
        $request_data ? $data = $request_data : $data = $this->jsonParams();
        $logic = D('Report/IncomeCost', 'Logic');
        $logic->listData($data);
        if ($request_data) {
            return $logic->getData();
        }
        
        $this->ajaxReturn($logic->getRet());
    }

    public function sum_data($request_data = null) {
        $request_data ? $data = $request_data : $data = $this->jsonParams();
        $logic = D('Report/IncomeCost', 'Logic');
        $logic->sumData($data);
//        if ($request_data) {
//            return $logic->getData();
//        }
        $this->ajaxReturn($logic->getRet());
    }

    public function export() {
        $data = $this->jsonParams()['params'];
        $logic = D('Report/IncomeCost', 'Logic');
        $logic->export($data);
    }
}