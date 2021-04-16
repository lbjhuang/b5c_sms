<?php
/**
 * 财报配置项
 * Class FinReportAction
 */

class  FinanceConfigAction extends ReportBaseAction
{

    public function  finance_config_list() {
        $this->display();
    }

    /**
     * 获取创建人
     */
    public function get_create_by(){
        $repository  = new  FinanceConfigRepository();
        $res = $repository->getCreateBy();
        return $this->ajaxReturn($res);
    }

    /**
     * 获取报告时间
     */
    public function get_fin_date(){
        $repository  = new  FinanceConfigRepository();
        $res = $repository->getfinDate();
        return $this->ajaxReturn($res);
    }

    /**
     * 列表数据
     */
    public function get_list() {
        $post_data = $this->jsonParams();
        $repository  = new  FinanceConfigRepository();
        $res = $repository->getList($post_data);
        return $this->ajaxReturn($res);
    }


    /**
     * 保存财报配置
     */
    public function finance_config_save(){

        $post_data = $this->jsonParams();
        $repository  = new  FinanceConfigRepository();
        $res = $repository->savaData($post_data,1);
        return $this->ajaxReturn($res);
    }

    /**
     * 保存财报配置
     */
    public function finance_config_issue(){
        $post_data = $this->jsonParams();
        $repository  = new  FinanceConfigRepository();
        $res = $repository->savaData($post_data,2);
        return $this->ajaxReturn($res);
    }
}