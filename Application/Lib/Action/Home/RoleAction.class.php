<?php
/**
 * 角色管理
 * User: muzhitao
 * Date: 2016/8/1
 * Time: 16:39
 */

class RoleAction extends BaseAction
{

    /**
     * 角色列表
     */
    public function role_list()
    {

        $Role = D("Role");
        $where = "ROLE_STATUS > 0";

        import('ORG.Util.Page');// 导入分页类
        $count = $Role->where($where)->count();
        $page = new Page($count, 15);
        $show = $page->show();
        $list = $Role->where($where)->order('ROLE_ID ASC')->limit($page->firstRow . ',' . $page->listRows)->select();
        // 记录总数
        $this->assign('count', $count);
        // 条件下的列表数据
        $this->assign('list', $list);
        // 分页
        $this->assign('pages', $show);

        $this->display();
    }

    /**
     * 新增角色
     */
    public function role_add()
    {

        if ($this->isPost()) {
            $Role = D("Role");
            $add_data = array(
                'ROLE_NAME' => I("post.role_name"),
                'ROLE_REMARK' => I("post.role_remark"),
                'ROLE_STATUS' => 1,
                'ROLE_ADDTIME' => time(),
            );
            $menu = I("post.rules");
            if (!empty($menu)) {
                $act = implode(',', $menu);
                $add_data['ROLE_ACTLIST'] = $act;
            } else {
                $add_data['ROLE_ACTLIST'] = NULL;
            }
            $result = $Role->add($add_data);
            if ($result) {
                $this->success('添加成功', U('Role/role_list'));
                exit();
            } else {
                $this->error('添加失败');
            }
        }

        //$menu_tree = $this->_get_node();
        $node_list = $this->returnNodes();
//        echo "<pre>";print_r($node_list);exit();

        //$this->assign('menu_tree', $menu_tree);
        $this->assign('node_list', $node_list);
        $this->display();
    }


    /**
     * @return array
     */
    public function _get_node()
    {

        $Node = D("Node");
        $modules = $rolemenu = array();
        $menu = $Node->where('STATUS = 1')->order('SORT DESC')->select();

        if (empty($menu)) {
            return $modules;
        }

        foreach ($menu as $key => $s) {
            if ($s['LEVEL'] == 2) {
                $modules[$s["CTL"]] = $s;
            } else {
                $rolemenu[] = $s;
            }
        }

        if (!empty($rolemenu)) {

            foreach ($rolemenu as $vs) {
                if (isset($modules[$vs['CTL']])) {
                    $modules[$vs['CTL']]['child'][] = $vs;
                } else {
                    $modules[]['child'] = array();
                }
            }
        }
        return $modules;
    }

    /**
     * 返回后台节点数据
     * @param boolean $tree 是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
     * @retrun array
     *
     * 注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
     *
     */
    protected function returnNodes($tree = true)
    {
        load('extend');
        static $tree_nodes = array();
        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }
        if ((int)$tree) {
            $list = M('Node')->field('ID,PID,NAME,CTL,ACT,REMARK')->order('SORT desc')->select();
            /*foreach ($list as $key => $value) {
                if( stripos($value['url'],MODULE_NAME)!==0 ){
                    $list[$key]['url'] = MODULE_NAME.'/'.$value['url'];
                }
            }*/
            $nodes = list_to_tree($list, $pk = 'ID', $pid = 'PID', $child = 'operator', $root = 0);
            foreach ($nodes as $key => $value) {
                if (!empty($value['operator'])) {
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        } else {
            $nodes = M('Menu')->field('NAME,CTL,ACT,REMARK,PID')->order('SORT desc')->select();
            /*foreach ($nodes as $key => $value) {
                if( stripos($value['url'],MODULE_NAME)!==0 ){
                    $nodes[$key]['url'] = MODULE_NAME.'/'.$value['url'];
                }
            }*/
        }
        $tree_nodes[(int)$tree] = $nodes;
        return $nodes;
    }

    /**
     * 编辑角色
     */
    public function role_edit()
    {
        if ($this->isGet()) {
            $rid = I("get.role_id");
            $Role = D("Role");
            $detail = $Role->find($rid);
            //echo "<pre>";print_r($detail);exit();

            $act_list = $detail['ROLE_ACTLIST'];
            $act_arr = explode(",", $act_list);
            //$menu_tree = $this->_get_node();
            $node_list = $this->returnNodes();
            //echo "<pre>";print_r($node_list);exit();
            //echo "<pre>";print_r($detail);exit();

            $this->assign('node_list', $node_list);
            $this->assign('act_arr', $act_arr);
            $this->assign('detail', $detail);
            //$this->assign('super', $super);
            $this->display();
        }

        if ($this->isPost()) {
            $Role = D("Role");
            $rid = I("post.role_id");
            $save_data = array(
                'ROLE_NAME' => I("post.role_name"),
                'ROLE_REMARK' => I("post.role_remark"),
                'ROLE_STATUS' => I("post.role_status", 1),
                'ROLE_UPDATED' => time(),
            );

            $menu = I("post.rules");
            if ($menu) {
                $act = implode(',', $menu);
                $save_data['ROLE_ACTLIST'] = $act;
            } else {
                $save_data['ROLE_ACTLIST'] = NULL;
            }

            $result = $Role->where('ROLE_ID = ' . $rid)->save($save_data);
        
            if ($result) {
                #标记需要刷新权限的用户
                $mids = M('admin_role')->where(['ROLE_ID' => $rid])->getField('M_ID', true);
                $mids = array_unique($mids);
                foreach($mids as $uid){
                    $tmp = RedisModel::client()->hgetall('uid_session_id_' . $uid);
                    if(count($tmp) > 0){
                        RedisModel::client()->del('refresh_role_session_id_' . $uid);
                        RedisModel::client()->hmset('refresh_role_session_id_' . $uid, $tmp);
                    }
                    
                }
                $this->success('编辑成功', U("Role/role_list"));
                exit;
            } else {
                $this->error('编辑失败');
            }
        }

    }

    /**
     *
     */
    public function role_dele()
    {

        if ($this->isPost()) {
            $id = $this->_post('id');

            $save_data = array(
                'ROLE_STATUS' => 0,
                'ROLE_DELETED' => time()
            );
            $model = new Model();
            $model->startTrans();
            $role = D("Role");
            $result = $role->where("ROLE_ID = " . $id)->save($save_data);
            if ($result) {
                if (false === $this->removeAllUserRole($id)) {
                    $model->rollback();
                    $this->ajaxjReturn(0, '移除用户权限失败', 0);
                }
                $model->commit();
                $this->ajaxReturn(0, '删除成功', 1);
            }
            $model->rollback();
            $this->ajaxjReturn(0, '删除失败', 0);
        }
        $this->ajaxjReturn(0, '非法操作', 0);
    }

    /**
     * @param $role_id
     * @return mixed
     */
    private function removeAllUserRole($role_id)
    {
        $Model = new Model();
        return $Model->table('bbm_admin_role')->where(['ROLE_ID' => $role_id])->delete();
    }

    /**
     *
     */
    public function getRoleUsers()
    {
        try {
            $role_id = DataModel::getDataNoBlankToArr('ROLE_ID');
            if (empty($role_id)) {
                $role_id = I('ROLE_ID');
            }
            if (empty($role_id)) {
                $role_id = I('get.ROLE_ID');
            }
            if (empty($role_id)) {
                throw new Exception(L("无请求数据"));
            }
            $Model = new Model();
            $m_ids = $Model->table('bbm_admin_role,bbm_admin')
                ->field('bbm_admin.M_NAME')
                ->where(['bbm_admin_role.ROLE_ID' => $role_id])
                ->where('bbm_admin_role.M_ID = bbm_admin.M_ID', null, true)
                ->group('bbm_admin_role.M_ID')
                ->select();
            if (false !== $m_ids && $m_ids) {
                $res_data = [
                    'user_count' => count($m_ids),
                    'user_names' => array_column($m_ids, 'M_NAME')
                ];
                $this->ajaxSuccess($res_data);
            } else {
                $res_data = [
                    'user_count' => 0,
                    'user_names' => []
                ];
                $this->ajaxSuccess($res_data);
            }
        } catch (Exception $exception) {
            $this->ajaxError($res_data, $exception->getMessage());
        }
    }

    /**
     *
     */
    public function roleListData()
    {
        $role = D("Role");
        $role_list = $role->field('ROLE_ID,ROLE_NAME')->where('ROLE_STATUS != 0', null, true)->select();
        $this->ajaxSuccess($role_list);
    }
}