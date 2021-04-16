<?php
/**
 * User: yuanshixiao
 * Date: 2018/4/20
 * Time: 17:14
 */

class UserAction extends CommonBaseAction
{
    /**
     * 查询用户
     */
    public function search_user() {
        $name   = I('request.name');
        $res    = M('admin')->field('M_ID id,M_NAME name,empl_id')->where(['M_NAME'=>['like',"%$name%"]])->select();
        $this->ajaxSuccess($res);
    }

    /**
     * 获取员工下拉框数据
     * @author Redbo He
     * @date 2021/1/27 11:05
     */
    public function staff_options()
    {
        $model = M("hr_card","tb_");
        $where =["STATUS" => "在职",];
        $emp_sc_name = I("get.emp_sc_name");
        if($emp_sc_name) {
           $where['EMP_SC_NM'] = ["like", "%{$emp_sc_name}%"];
        }
        $result = $model->field("ID as id ,EMP_SC_NM as emp_sc_name")->where($where)->order("WORK_NUM+0 ASC")->select();
        return $this->ajaxSuccess($result,"人员列表获取成功");
    }
}