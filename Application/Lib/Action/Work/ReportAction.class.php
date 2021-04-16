<?php
/**
 * User: yuanshixiao
 * Date: 2018/6/13
 * Time: 15:00
 */

class ReportAction extends BaseAction
{
    public function weekly_report_list() {
        import('ORG.Util.Page');
        $param  = $this->params();
        $count  = (new WorkReportModel())->reportCount($param);
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = (new WorkReportModel())->reportList($param,$page->firstRow.','.$page->listRows);
        $this->ajaxSuccess(['list'=>$list,'page'=>['total_rows'=>$count]]);
    }

    public function weekly_report_save() {
        $param = I('post.');
        $report_l = D('Work/WorkReport','Logic');
        $res = $report_l->saveWeeklyReport($param);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$report_l->getError());
        }

    }

    public function weekly_report_detail() {
        $id = I('request.id');
        $detail = (new WorkReportModel())->where(['id'=>$id])->find();
        $this->ajaxSuccess($detail);
    }
}