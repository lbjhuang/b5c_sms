<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 10:56
 */

class SynExcelAction extends ReportBaseAction
{

    public function syn_excel_list() {
        $this->display();
    }

    public function list_data() {
        import('ORG.Util.Page');
        $params = $this->getParams();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($params['pages']) && !empty($params['pages']['per_page']) && !empty($params['pages']['current_page'])){
            $pages = array(
                'per_page' =>$params['pages']['per_page'],
                'current_page' => $params['pages']['current_page']
            );
        }
        $dataService = new DataService();
        list($list, $count) = $dataService->getList($params,$pages);
        if (empty($count)) {
            $list = [];
        }
        $data = ['data' => $list, 'page' => ['total_rows' => $count]];
        $this->ajaxSuccess($data);

    }
}