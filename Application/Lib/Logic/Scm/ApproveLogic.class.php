<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/12
 * Time: 14:19
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ApproveLogic extends BaseLogic
{

    public function demandApprove($id,$status,$reason=null) {
        $demand_m   = D('Scm/Demand');
        $approve_m  = D('Approve');
        //检查需求状态
        $demand_status  = $demand_m->where(['id'=>$id])->getField('status');
        if($demand_status != 1) {
            $this->error = '订单状态异常';
            return false;
        }
        M()->startTrans();
        $res_approve    = $approve_m->add(['claim_id'=>$id,'type'=>0,'status'=>$status,'reason'=>$reason,'user'=>session('m_loginname')]);
        $res_demand = $demand_m->where(['id'=>$id])->save(['status'=>2]);
        if(!$res_approve || !$res_demand) {
            M()->rollback();
            $this->error = '审批失败';
            return false;
        }
        M()->commit();
        return true;
    }

}