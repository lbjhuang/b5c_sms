<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 10:56
 */

class IncomeAction extends ReportBaseAction
{

    public function income_list() {
        $this->display();
    }

    public function list_data($request_data = null) {
        $request_data ? $data = $request_data : $data = $this->params();
        $logic = D('Report/Income', 'Logic');
        $logic->listData($data);
        if ($request_data) {
            return $logic->getData();
        }
        $this->ajaxReturn($logic->getRet());
    }

    public function export() {
        $data = json_decode($this->params()['export_params'], true);
        $logic = D('Report/Income', 'Logic');
        $logic->export($data);
    }
}