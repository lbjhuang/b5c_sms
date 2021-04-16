<?php
/**
 * User: yuanshixiao
 * Date: 2018/11/15
 * Time: 13:34
 */

class HrJobsAapi extends BaseAction
{
    public $name_jobs = 'Hr/HrJobs';

    public function __construct()
    {
        $m_jobs = D($this->name_jobs);
    }

    public function jobs_list() {
        import('ORG.Util.Page');
        $params = $_GET;
        $count  = D($this->name_jobs)->jobsCount($params);
        $page   = new Page($count,$params['rows']?:20);
        $list   = D($this->name_jobs)->jobsList($params,$page->firstRow.','.$page->listRows);
        $this->ajaxSuccess(['list'=>$list,'totalRows'=>$count]);
    }

    public function jobs_add() {
        $params = Mainfunc::getInputJson();
        $res = D($this->name_jobs)->jobsAdd($params);
        if($res) $this->ajaxSuccess();
        $this->ajaxError(D($this->name_jobs)->getError());
    }

    public function jobs_edit() {
        $params = Mainfunc::getInputJson();
        $res = D($this->name_jobs)->jobsEdit($params);
        if($res) $this->ajaxSuccess();
        $this->ajaxError(D($this->name_jobs)->getError());
    }

    public function jobs_del() {
        $params = Mainfunc::getInputJson();
        $res = D($this->name_jobs)->jobsDel($params);
        if($res) $this->ajaxSuccess();
        $this->ajaxError(D($this->name_jobs)->getError());
    }
}


