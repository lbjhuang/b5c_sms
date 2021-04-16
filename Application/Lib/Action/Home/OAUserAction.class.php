<?php

/**
 * User: yangsu
 * Date: 17/5/16
 * Time: 13:15
 */
class OAUserAction extends BaseAction
{
    public function __construct()
    {
    }

    public function index()
    {
        $this->syncuser();
    }

    public function syncuser()
    {
        echo '<pre>';
        $role_id = $this->get_role_ID();
        if ($role_id) {
            $sql = "SELECT ID,LOGINID,PASSWORD FROM ECOLOGY.HRMRESOURCE WHERE STATUS IN (0,1,2,3)";
            $oci = new MeBYModel();
            $oa_arr = $oci->query($sql);
//        sms2 user
            $Admin = M('admin', 'bbm_');
            $admin_arr = $Admin->where('`M_STATUS` <> 2 AND `IS_USE` = 0')->field('M_ID,M_NAME,M_PASSWORD,oa_user_state,oa_id')->select();
            $admin_oa_arr = $Admin->where('`M_STATUS` <> 2 AND `IS_USE` = 0 AND `oa_user_state` = 1')->field('M_ID,M_NAME,M_PASSWORD,oa_user_state,oa_id')->select();
            $admin_unoa_arr = $Admin->where('`M_STATUS` <> 2 AND `IS_USE` = 0 AND `oa_user_state` = 0')->field('M_ID,M_NAME,M_PASSWORD,oa_user_state,oa_id')->select();
            $oa_arr_loginid = array_column($oa_arr, 'LOGINID');
            $admin_arr_name = array_column($admin_arr, 'M_NAME');
            $admin_arr_name_flip = array_flip($admin_arr_name);
            $user_intersect = array_intersect($oa_arr_loginid, $admin_arr_name);
            $admin_oa_arr_name = array_column($admin_oa_arr, 'M_NAME');
            $user_oa_intersect = array_intersect($oa_arr_loginid, $admin_oa_arr_name);
            $admin_unoa_arr_name = array_column($admin_unoa_arr, 'M_NAME');
            $user_nooa_intersect = array_intersect($oa_arr_loginid, $admin_unoa_arr_name);
//      left diff to add
            $add_arr = array_diff($oa_arr_loginid, $user_intersect);
            trace($add_arr,'$add_arr');
            if (count($add_arr) > 0) {
                foreach ($add_arr as $k => $v) {
                    $user_data['M_NAME'] = $oa_arr[$k]['LOGINID'];
                    $user_data['M_PASSWORD'] = $oa_arr[$k]['PASSWORD'];
                    $user_data['oa_user_state'] = 1;
                    $user_data['oa_id'] = $oa_arr[$k]['ID'];

                    $user_data['M_STATUS'] = 1;
                    $user_data['IS_USE'] = 0;
                    $user_data['ROLE_ID'] = $role_id;
                    $user_data_arr[] = $user_data;
                }

                $Admin->addAll($user_data_arr);
                echo 'add datas ' . count($user_data_arr);
            }
//      upd pass
            trace($user_intersect,'$user_intersect');
            if (count($user_intersect) > 0) {
                foreach ($user_intersect as $k => $v) {
//            check pass
                    if ($admin_arr[$admin_arr_name_flip[$v]]['M_PASSWORD'] != $oa_arr[$k]['PASSWORD']) {
                        $Admin->M_PASSWORD = $oa_arr[$k]['PASSWORD'];
                        $Admin->oa_user_state = 1;
                        $Admin->where('M_NAME = \'' . $oa_arr[$k]['LOGINID'] . '\'')->save();
                        echo 'upd pass ' . $oa_arr[$k]['LOGINID'] . '<br>';
                    }
                }


            }
//      right diff to deld
            $right_diff_arr = array_diff($admin_oa_arr_name, $user_oa_intersect);
            trace($right_diff_arr,'$right_diff_arr');
            if (count($right_diff_arr) > 0) {
                $where_del['M_NAME'] = array('in', $right_diff_arr);
                $Admin->M_STATUS = 2;
//                $Admin->where($where_del)->save();
                $Admin->where($where_del)->delete();
                echo 'del user';
                print_r($right_diff_arr);
            }
        } else {
            echo 'role_id is null';
        }
//        $this->display();
    }


    private function get_role_ID()
    {
        $Role = M('role', 'bbm_');
        $where['ROLE_NAME'] = 'OA用户';
        return $Role->where($where)->getField('ROLE_ID');
    }

    public function clear_group_oa()
    {
        $Model = M();
//        delete don't oa user
        $del = $Model->table('bbm_admin')->where('oa_user_state != 1')->delete();
        $del_status = $Model->table('bbm_admin')->where('M_STATUS = 2')->delete();
        $del_all = $Model->execute('DELETE FROM bbm_admin WHERE M_ID IN ( SELECT ml.M_ID AS M_ID FROM ( SELECT b.oa_user_state, b.M_LOGINTIME, b.oa_id, b.ROLE_ID, b.M_NAME, b.M_ID, b.M_STATUS FROM ( SELECT * FROM bbm_admin GROUP BY m_name HAVING count(m_name) > 1 ) a, bbm_admin b WHERE b.M_NAME = a.M_NAME ) ml GROUP BY ml.M_NAME )');
        var_dump($del);
        var_dump($del_status);
        var_dump($del_all);
    }
}