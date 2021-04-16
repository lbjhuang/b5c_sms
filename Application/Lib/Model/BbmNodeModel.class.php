<?php
/**
 * User: YangSu
 * Date: 19/1/7
 * Time: 10:34
 */


class BbmNodeModel extends \BaseModel
{
    private $model;

    protected $autoCheckFields  =   false;

    public function __construct()
    {
        $this->model = new \Model();
    }

    public function getNodeInfo($ctl, $act)
    {
        $where['CTL'] = $ctl;
        $where['ACT'] = $act;
        return $this->model->table('bbm_node')
            ->where($where)
            ->find();
    }

    /**
     * 用于权限判断
     * @param $action 控制器名
     * @param $function 方法名
     * @return bool
     */
    public function checkPermissions($action, $function)
    {
        if (!$action || !$function) {
            return false;
        }
        $node = M('node', 'bbm_')->where(['CTL'=>$action, 'ACT'=>$function])->find();
        if (empty($node)) {
            return false;
        }
        $permissions = $this->model->table('bbm_admin_role admin_role')
            ->field('role.ROLE_ACTLIST, role.ROLE_ID')
            ->join('left join bbm_role role on admin_role.ROLE_ID = role.ROLE_ID')
            ->where(['admin_role.M_ID' => DataModel::userId()])
            ->select();
        if (empty($permissions)) {
            return false;
        }
//        if ($permissions['ROLE_ID'] == 1) {
//            //超级管理员
//            return true;
//        }
        $permissions_arr = [];
        $permissions_str = '';
        foreach ($permissions as $item) {
            $permissions_str .= $item['ROLE_ACTLIST']. ',';
        }
        $permissions_arr = array_unique(explode(',', trim($permissions_str, ',')));
        if (!in_array($node['ID'], $permissions_arr)) {
            return false;
        }
        return true;
    }
}