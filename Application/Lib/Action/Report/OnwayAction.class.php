<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 10:56
 */

class OnwayAction extends ReportBaseAction
{

    public function onway_list() {
        $this->display();
    }

    public function list_data() {
        $data = $this->params();
        $logic = D('Report/Onway', 'Logic');
        $logic->listData($data);
        $this->ajaxReturn($logic->getRet());
    }

    public function export() {
        $data = json_decode($this->params()['export_params'], true);
        $data['page_size'] = -1;
        $logic = D('Report/Onway', 'Logic');
        $logic->export($data);
    }
}