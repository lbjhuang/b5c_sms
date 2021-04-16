<?php
/**
 * 管理员控制器
 * User: muzhitao
 * Date: 2016/8/1
 * Time: 13:07
 */

class AdminAction extends BaseAction {

	/**
	 * 管理员列表
	 */
	public function admin_list() {
		$condition = array();
		$condition['M_STATUS'] = array('lt', 2);
        if (!IS_POST && 3 < count($_GET)) {
            $_POST = $_GET;
        }
		if (IS_POST || 3 < count($_GET)) {

			if ($_POST['login_name']) {
				$condition['M_NAME'] =array('like',"%".$_POST['login_name']."%");
			}
			if ($_POST['start_time'] && $_POST['end_time']) {
				$condition['M_ADDTIME'] = array(array('gt',strtotime($_POST['start_time']." 00:00:00")),array('lt',strtotime($_POST['end_time']." 23:59:59")));
			} elseif($_POST['start_time']) {
				$condition['M_ADDTIME'] = array('gt', strtotime($_POST['start_time']));
			} elseif($_POST['end_time']) {
				$condition['M_ADDTIME'] = array('lt', strtotime($_POST['end_time']));
			}
            if ($_POST['user_cn_name']) {
                $condition['EMP_SC_NM'] =array('like',"%".$_POST['user_cn_name']."%");
            }
            $role_ids = $_POST['role_ids'];
            if ($role_ids) {
                if (false !== strstr($role_ids,',')) {
                    $role_ids = explode(',', $role_ids);
                }else{
                    $role_ids = (array)$role_ids;
                }
                if (is_array($role_ids)) {
                    $condition['a.ROLE_ID'] =array('IN', $role_ids);
                }
            }
		}

		$Admin = M('Admin');
		import('ORG.Util.Page');// 导入分页类
        $Admin_index = clone $Admin;
		$count = count($Admin
            ->alias('t')
            ->field('t.M_ID')
            ->where($condition)
            ->join('left join bbm_admin_role a on a.M_ID=t.M_ID')
            ->join('left join bbm_admin_role c on c.M_ID=t.M_ID')
            ->join('left join (SELECT
                                M_ID,
                                GROUP_CONCAT(bbm_role.ROLE_NAME) AS ROLE_NAME,
                                GROUP_CONCAT(bbm_role.ROLE_NAME) AS ROLE_ID
                            FROM
                                bbm_admin_role,
                                bbm_role
                            WHERE
                                bbm_role.ROLE_ID = bbm_admin_role.ROLE_ID
                            GROUP BY
                                M_ID) AS role_names ON role_names.M_ID = t.M_ID')
            ->group('t.M_ID')->select());
		$page = new Page($count, 20);
        $page->parameter = $_POST;
		$show = $page->show();

		$list = $Admin_index
            ->alias('t')
            ->field('t.*,role_names.ROLE_ID,role_names.ROLE_NAME')
            ->where($condition)
            ->join('left join bbm_admin_role a on a.M_ID=t.M_ID')
            ->join('left join bbm_admin_role c on c.M_ID=t.M_ID')
            ->join('left join (SELECT
                                M_ID,
                                GROUP_CONCAT(bbm_role.ROLE_NAME) AS ROLE_NAME,
                                GROUP_CONCAT(bbm_role.ROLE_NAME) AS ROLE_ID
                            FROM
                                bbm_admin_role,
                                bbm_role
                            WHERE
                                bbm_role.ROLE_ID = bbm_admin_role.ROLE_ID
                            GROUP BY
                                M_ID) AS role_names ON role_names.M_ID = t.M_ID')
            ->order('M_ID DESC')
            ->group('t.M_ID')
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
		foreach ($list as &$v) {
		    $v['ROLE_ID'] = explode(',' , $v['ROLE_ID']);
        }
        $role               = D('Role');
        $role_list          = $role->where('ROLE_STATUS != 0',null,true)->select();
        $this->assign('init', $_POST);
        $this->assign('role_list', $role_list);
        // 记录总数
        $this->assign('count', $count);
		// 条件下的列表数据
		$this->assign('list', $list);
		// 分页
		$this->assign('pages', $show);
		$this->display();
	}

	/**
	 * 新增管理员
	 */
	public function admin_add() {

		$role = D('Role');
		$role_list = $role->select();

		if ($this->isPost()) {

			$Admin = D("Admin");

			$where = array(
				'M_NAME'   => I("post.login_name"),
				'M_STATUS' => ['neq',2]
			);
			$detail = $Admin->where($where)->find();

			/* 判断当前输入的用户名是否存在 */
			if ($detail) {
				$this->ajaxReturn(0, '用户名已存在', "n");
			}

			$add_data = array(
				'M_NAME' => I("post.login_name"),
				'M_PASSWORD' => md5(I("post.newpassword").C("PASSKEY")),
				'EMP_SC_NM' =>I("post.EMP_SC_NM"),
				'M_SEX' => I("post.m_sex"),
				'M_MOBILE' => I("post.m_mobile"),
				'M_EMAIL' => I("post.email"),
				'M_REMARK' => I("post.m_remark"),
				'ROLE_ID' => I("post.role_id"),
				'M_ADDTIME' => time(),
			);

			$result = $Admin->add($add_data);
                        /*获得新插入的用户id*/
                        $userid = $Admin->getLastInsID();
                        $user_detail = D("Detail");
                        $add_data = array(
				'uid' => $userid,
				'group_id' => I("post.role_id"),
			);
                        $result = $user_detail->add($add_data);
			if ($result) {
				$this->ajaxReturn(0, '添加成功', "y");
				//$this->success("添加成功", U("Admin/admin_list"));
			} else {
				$this->ajaxReturn(0, '添加失败', "n");
				//$this->error("添加失败");
			}
		}

		$this->assign('role_list', $role_list);
		$this->display();
	}


	/**
	 * 更新管理员的状态操作
	 */
	public function update_admin_status() {

		// 判断是否来源POST请求
		if (IS_POST) {

			$u_id = I('post.u_id');

			// 如果参数不能为空
			if (empty($u_id)) {
				$this->ajaxReturn(0, '参数不能为空', 0);
			}

			$Admin = M('Admin');
			$status = I('post.is_use');
			if ($status == 1) {
				$data['IS_USE'] = 1;
			} else{
				$data['IS_USE'] = 0;
			}

			$Admin->where('M_ID = '.$u_id)->save($data);
			$this->ajaxReturn(0, '更新成功', 1);
		}
	}

	/**
	 * 编辑信息
	 */
	public function admin_edit() {
		$admin_m        = D("Admin");
        $admin_role_m   = D("AdminRole");
        if ($this->isPost()) {
            $m_uid = I("post.m_uid");

            $detaail = $admin_m->find($m_uid);

            $add_data = array(
                //'M_NAME' => I("post.login_name"),
                //'M_SEX' => I("post.m_sex"),
                //'EMP_SC_NM'=>I("post.EMP_SC_NM"),
                //'M_MOBILE' => I("post.m_mobile"),
                //'M_EMAIL' => I("post.email"),
                'M_REMARK' => I("post.m_remark"),
                //'ROLE_ID' => I("post.role_id"),
                'M_UPDATED' => time(),
                'M_STATUS' => 1
            );
            if (!empty(I("post.login_name"))) {
                $add_data['M_NAME'] = I("post.login_name");
            }
            if (!empty(I("post.m_sex"))) {
                $add_data['M_SEX'] = I("post.m_sex");
            }
            if (!empty(I("post.EMP_SC_NM"))) {
                $add_data['EMP_SC_NM'] = I("post.EMP_SC_NM");
            }
            if (!empty(I("post.email"))) {
                $add_data['M_EMAIL'] = I("post.email");
            }
            if (!empty(I("post.m_mobile"))) {
                $add_data['M_MOBILE'] = I("post.m_mobile");
            }


            if ($detaail['empl_id']!=='0') {
                //$this->ajaxReturn(0, '该账户为员工账号,请在人事管理上修改账号信息,数据将同步更新', 1);
                //$emplData['ERP_ACT'] = $add_data['M_NAME'];
                //$emplData['SEX'] = $add_data['M_SEX'];
                //$emplData['EMP_SC_NM'] = $add_data['EMP_SC_NM'];
                //$emplData['PER_PHONE'] = $add_data['M_MOBILE'];
                //$emplData['SC_EMAIL'] = $add_data['M_EMAIL'];
                //$emplData['EMP_SC_NM'] = $add_data['EMP_SC_NM'];
                //$emplData['EMP_SC_NM'] = $add_data['EMP_SC_NM'];
                //$emplRes = D("TbHrEmpl")->where('ID='.$detaail['empl_id'])->save($emplData);
                //$cardRes = D("TbHrCard")->where('EMPL_ID='.$detaail['empl_id'])->save($emplData);
            }

            $admin_m->startTrans();
            $res_admin = $admin_m->where('M_ID = '. $m_uid)->save($add_data);
            if ($res_admin === false ) {
                $admin_m->rollback();
                Elog::add(['info'=>'用户信息保存失败','log'=>M()->getDbError()]);
                $this->ajaxReturn(0, '用户信息保存失败', 0);
            }
            $role_id        = I("post.role_id");
            if(empty($role_id)) {
                $this->ajaxReturn(0, '角色不能为空', 0);
            }
            $role_old       = $admin_role_m->where(['M_ID'=>$m_uid])->getField('ROLE_ID',true);
            $res_role_del   = $admin_role_m->where(['M_ID'=>$m_uid,'ROLE_ID'=>['not in',$role_id]])->delete();
            if ($res_role_del=== false ) {
                $admin_m->rollback();
                Elog::add(['info'=>'老角色删除失败','log'=>M()->getDbError()]);
                $this->ajaxReturn(0, '老角色删除失败', 0);
            }
            $data_role      = [];
            foreach ($role_id as $v) {
                if(!in_array($v,$role_old))
                    $data_role[] = ['M_ID'=>$m_uid,'ROLE_ID'=>$v] ;
            }
            if(!empty($data_role)) {
                $res_role_add = $admin_role_m->addAll($data_role);
                if ($res_role_add=== false ) {
                    $admin_m->rollback();
                    Elog::add(['info'=>'新增角色失败','log'=>M()->getDbError()]);
                    $this->ajaxReturn(0, '新增角色失败', 0);
                }
            }
            $admin_m->commit();
            #刷新权限
            $tmp = RedisModel::client()->hgetall('uid_session_id_' . $m_uid);
            if (count($tmp) > 0) {
                RedisModel::client()->del('refresh_role_session_id_' . $m_uid);
                RedisModel::client()->hmset('refresh_role_session_id_' . $m_uid, $tmp);
            }
            $this->ajaxReturn(0, '编辑成功', 1);
        }else {
            $uid                = I("get.m_id");
            $role               = D('Role');
            $role_list          = $role->where('ROLE_STATUS != 0',null,true)->select();
            $detail             = $admin_m->find($uid);
            $detail['ROLE_ID']  = D('admin_role')->where(['M_ID'=>$detail['M_ID']])->getField('ROLE_ID',true);
            $this->assign('role_list', $role_list);
            $this->assign('detail', $detail);
            $this->display();
        }
	}


	/**
	 * 删除管理员 逻辑删除 更改状态
	 */
	public function delete_admin() {

		// 判断数据来源是否正常
		if (IS_POST) {
			$uid = I('post.u_id');

			// 参数不能为空
			if (empty($uid)) {
				$this->ajaxReturn(0, '参数不能为空', 0);
			}
			
			$Admin = M('Admin');
			$detail = $Admin->find($uid);
			if ($detail['empl_id']!=='0') {
				$this->ajaxReturn(1, '该账户为员工账户,删除请在人员管理上删除', 0);
			}else{
					if (!is_array($uid)) {
					$detail = $Admin->find($uid);

					// 如果数据不存在
					if(empty($detail)) {
						$this->ajaxReturn(0, '数据不存在', 1);
					}
					$save_data = array(
						'M_STATUS' => 2,
						'M_DELETED' => time(),
					);
					$Admin->where("M_ID =".$uid)->save($save_data);
				}else{
					$uids = implode(',', $uid);
					$res = $Admin->delete($uids);
					
								
					
				}

				$this->ajaxReturn(0, '删除成功', 1);
			}
			
		}
	}

	/**
	 * 修改密码
	 */
	public function admin_password() {

		$Admin = D("Admin");

            /* 获取用户的详情 */
            if ($this->isGet()) {

                $mid = I("get.m_id");
                $detail = $Admin->find($mid);

                $this->assign('detail', $detail);
                $this->display();
            }

            /* 编辑提交的数据 */
            if ($this->isPost()) {
                /*if($Admin->where('M_NAME = \''.session('m_loginname').'\'')->getField('oa_user_state') == 1) {
                    $this->ajaxReturn(0,'OA用户请在OA上修改，稍等后数据同步', 0);
                }*/
                $m_id = I("post.m_id");
                $detail = $Admin->find($m_id);
                if ($detail['empl_id']!=='0') {
                	$this->ajaxReturn(0,'该账户为员工账号,请在人事管理上修改密码,数据将同步更新', 1);
                }else{
                $m_id = I("post.m_id");
                $save_data = array(
                    'M_PASSWORD' => md5(I("post.passwords").C("PASSKEY")),
                    'M_STATUS' => 1,
                    'M_UPDATED' => time()
                );

                $reuslt = $Admin->where('M_ID = '. $m_id)->save($save_data);
                if ($reuslt) {
                    $this->ajaxReturn(0, '修改成功,请重新登录',1);
                } else {
                    $this->ajaxReturn(0,'修改失败，请重新修改', 0);
                }
            }
        }


	}

    /**
     * 管理员下拉框信息
     * @author Redbo He
     * @date 2021/1/4 15:58
     */
    public function admin_options() {
        $where = [
            'M_STATUS' => ['elt', 1]
        ];

        $name = I("get.name");
        if($name) {
            $where["M_NAME"] = ["like" , "%{$name}%"];
        }
        $admin = M('Admin');
        $admin_list = $admin->field("M_ID as id,M_NAME as name,empl_id,M_STATUS as status")
            ->where($where)->order("M_ID DESC")
            ->select();
        return $this->ajaxSuccess($admin_list);
    }
}