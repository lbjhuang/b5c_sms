<?php
/**
 * 财报配置项
 * Class FinReportAction
 */

class  FinanceConfigIssueAction extends Action
{

    public function  info() {
        $this->display();
    }

    public function  info_save() {
        $this->display();
    }

    /**
     * 财报详情  保存
     */
    public function finance_config_detail_save(){
        $fin_report_id = $_GET['fin_report_id'];
        if (empty($fin_report_id)){
            return $this->ajaxReturn(array('code'=>4000,'msg'=>'参数有误'));
        }
        $repository  = new  FinanceConfigRepository();
        $condition = array(
            'tb_fin_report_config.id' => $fin_report_id,
            'tb_fin_report_config_detail.type' => 1
        );
        $fin_report_info =  $repository->getJoinList($condition);
        return $this->ajaxReturn(array('code'=>2000,'datas'=>$fin_report_info));
    }

    /**
     * 财报详情  发布
     */
    public function finance_config_detail_issue(){
        $fin_report_id = $_GET['fin_report_id'];
        if (empty($fin_report_id)){
            return $this->ajaxReturn(array('code'=>4000,'msg'=>'参数有误'));
        }
        $repository  = new  FinanceConfigRepository();
        $condition = array(
            'tb_fin_report_config.id' => $fin_report_id,
            'tb_fin_report_config_detail.type' => 2
        );
        $fin_report_info =  $repository->getJoinList($condition);
        return $this->ajaxReturn(array('code'=>2000,'datas'=>$fin_report_info));
    }
}