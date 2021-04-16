<?php

/**
 * 系统首页
 * Class IndexAction
 */
class IndexAction extends BaseAction {

    public function index() {


      
        $langArray = array(L('简体中文') => 'zh-cn',
            L('English') => 'en-us',
            L('한글') => 'ko-kr',
            L('日本語の') => 'ja-jp');
        $langSelect =  '<select id="langS" onchange="window.location=this.value;">';
        foreach($langArray as $k => $v){
            if(LANG_SET == $v){
                $langSelect .= '<option selected="selected" value="'.U('',['l'=>$v]).'">'.$k.'</option>';
                continue;
            }
            $langSelect .= '<option value="'.U('',['l'=>$v]).'">'.$k.'</option>';
        }
        $langSelect .= '</select>';
        $oa = '';
        $Admin = M('admin', 'bbm_');
        $role_id = $this->get_role_ID();
        if(in_array($role_id,$_SESSION['role_id']) && count($_SESSION['role_id']) == 1){
            // $oa = '请邮箱联系眷念：&lt;juannian@gshopper.com&gt;，抄送给 楚留香：&lt;chuliuxiang@gshopper.com&gt;，开放权限';
            $oa = '请邮箱联系Calvin Xie&lt;Calvin.Xie@gshopper.com&gt;& 部门领导的邮箱，抄送给 飞松（Adams Tan）：&lt;adams.tan@gshopper.com&gt;，描述需要开通的角色和工作内容范围，部门领导同意后，开放权限';
        }
        $_SESSION['oa'] = $oa;
        $tab_data = isset($_GET['tab_data']) ? $_GET['tab_data'] : [];
        if($tab_data)
        {
            $tab_data['url'] = urldecode($tab_data['url']);
        }
        $this->assign('languages', BaseModel::languages());
        $this->assign('current_language', LANG_SET);
        $this->assign('host',$_SERVER['HTTP_HOST']);
        $this->assign('user_name', session('m_loginname'));
        $this->assign('OPEN_HOST', OPEN_HOST);
        $this->assign('tab_data', $tab_data);
        $this->display();
    }

    //用于java监控项目请求确定此项目是否可以正常访问
    public function status()
    {
        $this->ajaxSuccess();
    }

    private function get_role_ID()
    {
        $Role = M('role', 'bbm_');
        $where['ROLE_NAME'] = 'OA用户';
        return $Role->where($where)->getField('ROLE_ID');
    }
}