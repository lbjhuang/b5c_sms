<?php

/**
 * Rbac 权限控制公共类 封装一些和权限相关的公共方法
 * Class RbacService
 */
class RbacService extends Service
{

    /**
     * 获取某个角色的用户信息
     * @param $role_id
     * @author Redbo He
     * @date 2021/3/17 16:58
     */
    public function getRoleAdminUsers($role_id)
    {
        if(empty($role_id)) return false;
        $model = M("admin","bbm_");
        $m_admins = $model->join(" inner join bbm_admin_role on bbm_admin.M_ID = bbm_admin_role.M_ID")
            ->where([
                "bbm_admin_role.ROLE_ID" => $role_id,
                "bbm_admin.M_STATUS" => ['lt', 2]
            ])
            ->field([
                "bbm_admin.M_ID",
                "bbm_admin.M_NAME",
                "bbm_admin_role.ROLE_ID",
            ])
            ->select();
        ;
        return $m_admins;
    }
}