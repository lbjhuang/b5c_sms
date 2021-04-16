<?php
class QuestionService 
{
	public static $STATUS_FOLLOWING = 'N001760100'; // 待跟进
	public static $STATUS_DESIGNING = 'N001760200'; // 待设计
	public static $STATUS_DONE 		= 'N001760300'; // 已解决
	public static $STATUS_CLOSED 	= 'N001760400'; // 已关闭
	public static $STATUS_UNDEFINED = 'N001760401'; // 待明确
	public static $STATUS_DEVING 	= 'N001760402'; // 待开发
	public static $STATUS_TESTING 	= 'N001760403'; // 待测试

	public static $DEFAULT_CRY = '1';
	public static $DEFAULT_PDT = '2';
	public static $DEFAULT_DEV = '3';
	public static $DEFAULT_TST = '4';



	public static $_MODULE_INFO = [
	    "1" => [
	        'module' => 'NoDeal', // 认定未解决 
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。',
	        'name' => '认定未解决',
	    ],
	    "2" => [
	        'module' => 'Close', // 关闭问题
	        'tmpl' => '问题Q@@YY@@（@@ZZ@@）已经被创建人关闭解决。',
	        'name' => '关闭问题', 
	    ],
	    "3" => [
	        'module' => 'AddRemark', // 提交补充描述 
	        'tmpl' => '问题Q@@YY@@（@@ZZ@@）有了补充描述@@CC@@。',
	        'name' => '提交补充描述',
	    ],
	    "4" => [
	        'module' => 'TranPdt', // 转产品
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。',
	        'name' => '转产品',
	    ],
	    "5" => [
	        'module' => 'TranTst', // 转测试
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。',
	        'name' => '转测试',
	    ],
	    "6" => [
	        'module' => 'TranCry', // 转实施
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。',
	        'name' => '转实施',
	    ],
	    "7" => [
	        'module' => 'Undefined', // 不明确
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）设置为了【不明确】，请进一步沟通或增加补充描述。备注：@@CC@@。',
	        'name' => '不明确',
	    ],
	    "8" => [
	        'module' => 'Deal', // 解决
	        'tmpl' => '问题Q@@YY@@（@@ZZ@@）已解决。备注@@CC@@。',
	        'name' => '解决',
	    ],
	    "9" => [
	        'module' => 'TranDev', // 转开发
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。禅道编号@@DD@@。',
	        'name' => '转开发',
	    ],
	    "10" => [
	        'module' => 'TranOthPdt', // 转其他产品
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。禅道编号@@DD@@。',
	        'name' => '转其他产品',
	    ],
	    "11" => [
	        'module' => 'TranOthDev', // 转其他开发
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。禅道编号@@DD@@。',
	        'name' => '转其他开发',
	    ],
	    "12" => [
	        'module' => 'TranOthTst', // 转其他测试
	        'tmpl' => '@@BB@@将问题Q@@YY@@（@@ZZ@@）转给了您。备注：@@CC@@。禅道编号@@DD@@。',
	        'name' => '转其他测试',
	    ],
	    "13" => [
	        'module' => 'Deal', // 上线
			'tmpl' => '问题Q@@YY@@（@@ZZ@@）已解决。备注@@CC@@。',
	        'name' => '上线',
	    ],
	    "14" => [
	        'module' => 'Create', // 创建工单 
	        'tmpl' => '用户@@BB@@反馈了新的问题Q@@YY@@：（@@ZZ@@）。',
	        'name' => '创建工单',
	    ],

	    "15" => [
	        'module' => 'Hurry', // 催一下 功能
	        'tmpl' => '用户@@BB@@就问题Q@@YY@@：@@ZZ@@催了你一下。',
	        'name' => '催一下',
	    ],
	    "16" => [
	        'module' => 'Save', // 保存
	        'tmpl' => '', // 保存功能不需要推送企业微信
	        'name' => '保存',
	    ],

	];

	public static $_USER_TYPE = [
		"1" => [ // 产品
			'246', // CLX 
			'253', // GP
			'260', // ERP PM
			'261', // Insight PM
			'262', // UI
		],
		"2" => [ // 开发
			'205', // Big Data & AI
			'247', // GP Client 
			'255', // JAVA
			'256', // PHP
			'257', // Frontend
			'258', // MTI

		],
		"3" => [ // 测试
			'248', // GP 
			'259', // ERP
		],
	];

	public function __construct($model = '')
	{
	    if ($model) {
	        $this->model = $model;
	    } else {
	        $this->model = new Model();
	    }
	    $this->user_name = DataModel::userNamePinyin();
	    $this->config_table = M('ms_question_config','tb_');
	}

	// 展示数据
	public function getDefaultUserInfo()
	{
		$res = $this->config_table->field('user_id, type')->order('type')->select();
		$adminModel = M('admin'); $dataRes = [];
		foreach ($res as $key => $value) {
			$whereMap = [];
			$whereMap['M_ID'] = $value['user_id'];
			$admin_name = $adminModel->where($whereMap)->getField('M_NAME');
			$dataRes[$key]['user_id'] = $value['user_id'];
			$dataRes[$key]['name'] = $admin_name;
		}
		return $dataRes; 
	}

	// 获取默认人员配置
	public function getDefaultUser($type)
	{
		$map['type'] = $type;
		$user_id = $this->config_table->where($map)->getField('user_id');
		// 根据user_id 获取对应的名称
		if ($user_id) {
			$whereMap['M_ID'] = $user_id;
			$adminModel = M('admin');
			$admin_name = $adminModel->where($whereMap)->getField('M_NAME');
			$return_data['name'] = $admin_name;
			$return_data['user_id'] = $user_id;
			return $return_data;
		}
		return '';
	}

	// 新增/编辑保存 默认人员配置
	public function saveQuestionConfig($request_data)
	{
		$this->model->startTrans();
		// 根据类型去查找，如果没有该类型的值，则新增，否则，更新即可
		foreach ($request_data as $key => $value) {
			$map = [];$user_id = ''; $save_data = [];
			if (!$value['user_id']) {
				throw new \Exception(L('默认人员不可为空'));
			}
			if (!$value['type']) {
				throw new \Exception(L('默认人员类型不可为空'));
			}
			$map['type'] = $value['type'];
			$user_id = $this->config_table->where($map)->getField('user_id');
			if ($user_id) {
				$save_data = [
				    'user_id' 		=> $value['user_id'],
				    'updated_by'    => $this->user_name,
				];
				if (false === $this->config_table->where(['type' => $value['type']])->save($save_data)) {
					$save_data['sql'] = M()->_sql();
				    Logs(json_encode($save_data), __FUNCTION__ . ' fail', __CLASS__);
				    $this->model->rollback();
				    throw new \Exception(L('编辑默认人员配置失败'));
				}
			} else { // 新增
				$save_data = [
		        	'type'			=> $value['type'],
		        	'user_id' 		=> $value['user_id'],
		            'created_by'         => $this->user_name,
		        ];
		        if (!$this->config_table->add($save_data)) {
		        	$save_data['sql'] = M()->_sql();
		            Logs(json_encode($save_data), __FUNCTION__ . ' fail', __CLASS__);
		            $this->model->rollback();
		            throw new \Exception(L('配置默认人员新增失败'));
		        }
			}
		}
		$this->model->commit();
		return false;
	}


	public function getWxUserSql($name = '')
	{
		$model = M(); $res = false;
		$sql = "SELECT b.wid FROM bbm_admin as a left join tb_hr_empl_wx as b on a.empl_id = b.uid WHERE ( a.M_NAME = '{$name}' )";
		$res = $model->query($sql);
		if ($res) {
			$res = $res[0]['wid'];
		}
		return $res;
	}

	public function getUserByWx($type)
	{
		if (empty($type)) {
			return false;
		}
		$dept_arr = self::$_USER_TYPE[$type];
		if (count($dept_arr) == 0) {
			return false;
		}
		$res = []; $user_arr = [];
		foreach ($dept_arr as $key => $value) {
			$res = ApiModel::WorkWxGetUserNameByDeptId($value);
			if (0 === $res['errcode']) {
				if (count($res['userlist'] != 0)) {
					foreach ($res['userlist'] as $k => $v) {
						$user_arr[] = $v['userid'];
					}
				}
			}
		}
		$user_arr = array_unique(array_filter($user_arr));

		// 根据huaming获取对应的id和名称
		return $this->getAdminInfo($user_arr);
	}

	// 获取erp用户键值对
	public function getAdminInfo($user_arr = [])
	{
		$erp_user_arr = [];
		if (count($user_arr) == 0) {
			return false;
		}
		$adminModel = M('admin');
		$emplModel = M('empl_wx', 'tb_hr_');
		foreach ($user_arr as $key => $value) {
			if ($value) {
				$re = $adminModel->field('M_ID,M_NAME')->where("huaming = '{$value}'")->select();
				if (!$re) { // 部分是字符串，如 qy017c2d864e19890028197035ad，而不是花名简拼
					$uid = $emplModel->where("wid = '{$value}'")->max('uid'); // 处理部分wid相同，多条记录的问题（部分数据uid为0）
					if ($uid) {
						$re = $adminModel->field('M_ID,M_NAME')->where("empl_id = '{$uid}'")->select();
					}
				}
			}
			if ($re) {
				$re = $re[0];
				$erp_user_arr[$re['M_ID']] = $re['M_NAME'];
			}
		}
		return $erp_user_arr;

	}

	public function getArrByStr($user_str = '')
	{
		$user_name_arr = []; $user = [];
		if (!strstr($user_str, ',')) {
		    $user_name_arr[] = $user_str;
		} else {
		    $user = explode(',', $user_str);
		    $user_name_arr = array_unique(array_filter($user));
		}
		return $user_name_arr;
	}

	// 每个工作日早上九点发送发送通知
	public function send_all_wx_msg()
	{
		// 判断是否需要开启
		// 获取全部工单的当前应处理人数组
		// 循环应处理人数组，判断问题是否在其里面，如果是，归入到该处理人数组中
		// 循环应处理人，并循环拼接处理微信模板，发送消息
		if (C('WORK_WX_OPEN_FLAG') === false) {
			return true;
		}
					
		$deal_user_str = ''; $deal_user_arr = []; $deal_arr = [];
		$WechatMsg = new WechatMsg();
		$questionModel = M('question', 'tb_ms_');
		$map['question_user_id'] = array('neq', '');
		$map['is_delete'] = array('eq', '0');
		$res = $questionModel->field('id, opt_user_name, title')->where($map)->select(); // 获取所有工单信息

		if ($res) {
			foreach ($res as $key => $value) {
				$deal_user_str .= ',' . $value['opt_user_name']; // 获取所有的应处理人
			}
			if ($deal_user_str) {	
				$deal_user_arr = $this->getArrByStr($deal_user_str); // 将所有的应处理人字符串换成数组
				foreach ($deal_user_arr as $key => $value) {
					foreach ($res as $k => $v) {
						if (strstr($v['opt_user_name'], $value)) { // 循环遍历，形成 应处理人【工单id】 => 工单标题 的结构
							$deal_arr[$value][$v['id']] = $v['title'];
						}
					}
				}
				if (count($deal_arr) != 0) {
					$msg_result = true; $msg_fail_send = [];
					foreach ($deal_arr as $key => $value) {
						// 获取对应的模板
						$msg = "今日应跟进问题：%0a"; // %0a 用于url传输换行，\r\n 会报错
						foreach ($value as $k => $v) {
							$msg .= 'Q' . $k . '：' . $v . "；%0a"; // 将该应处理人旗下的所有问题集中到一起
						}
						$wx_result = false;
						$wx_result = $this->getWxResult($key, $msg);
						if (!$wx_result) {
							$msg_result = false;
							$msg_fail_send[] = $key;
						}
					}
					if (!$msg_result) {
						Logs($msg_fail_send, __CLASS__, __FUNCTION__);
						throw new Exception('发送微信消息缺失发送用户，具体请看日志');
					}
					return $msg_result;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	public function getWxResult($value = '', $msg = '')
	{
		try {
			if(!$value) return false;
			$name = ''; $wx_result = true;
			$name = $this->getWxUserSql($value);
			if(!$name) return false;
			$msg = str_replace(array("\r\n", "\r", "\n"), "", $msg); // 去掉换行等转义符导致发送失败的问题
			$msg = str_replace("/", "\\", $msg); // 去掉转移符
			$res = ApiModel::WorkWxSendMessage($name, $msg);
			if (200000 != $res['code']) {
				@SentinelModel::addAbnormal('工单发送失败具体名单', "{$value}-{$name}", [$res, $msg], 'question_notice');	
				$wx_result = false;
			}
		} catch (Exception $e) {
			$info = $e->getMessage();
			p($info);die;
		}
		
		return $wx_result;
	}


	// 微信推送
	public function sendWx($user_arr = [], $data = [])
	{
		$WechatMsg = new WechatMsg();
		$result['res'] = true;
		$result['data'] = [];

		$msg = $this->getWxTpml($data); // 获取模板
		if (is_array($user_arr) && count($user_arr) != 0) {
			foreach ($user_arr as $key => $value) {
				$wx_result = false;
				$wx_result = $this->getWxResult($value, $msg);
				if (!$wx_result) {
					$result['data'][] = $value;
					$result['res'] = false;
				}
			}
		} else {
			$result['res'] = false;
			$result['data'] = 'not fund person need to send';
		}
		return $result;
	}

	// 获取消息模板
	public function getWxTpml($data)
	{
		$replace_arr = array('@@BB@@', '@@YY@@', '@@ZZ@@', '@@CC@@');
		$replace_after_arr = array($_SESSION['m_loginname'], $data['id'], $data['info']['title'], $data['content']);
		if ($data['project_no']) {
			$replace_arr[] = '@@DD@@';
			$replace_after_arr[] = $data['project_no'];
		} else {
			$replace_arr[] = '禅道编号@@DD@@。';
			$replace_after_arr[] = '';
		}

		return str_replace($replace_arr, $replace_after_arr, self::$_MODULE_INFO[$data['type']]['tmpl']);
	}

	// 企业微信发送
	public function dealWxSend($wx_user, $data = [])
	{
		if (C('WORK_WX_OPEN_FLAG') === false) { // 测试环境取消消息推送
			//return true;
		}
		if ($wx_user && !is_array($wx_user)) {
			$wx_user = array($wx_user);
		}
		if (!$wx_user || count($wx_user) === 0) {
			$res['res'] = false;
			$res['question_id'] = $data['id'];
			$res['wx_user'] = $wx_user;
			Logs($res, __CLASS__, __FUNCTION__);
			@SentinelModel::addAbnormal('工单消息发送用户缺失', '用户为空', [$res, $data], 'question_notice');
		}
		$res = $this->sendWx($wx_user, $data);		
		if (!$res['res']) {
			$res['question_id'] = $data['id'];
			Logs($res, __CLASS__, __FUNCTION__);
			@SentinelModel::addAbnormal('工单有发送失败名单', "工单ID{$data['id']}", [$res, $data, $wx_user], 'question_notice');
		}
		return $res['res'];
	}

	// 判断是否是包含英文逗号的字符串，如果是，拆成数组返回
	public function changeFormat($data)
	{
	    $temp_data = [];
	    if (!strstr($data, ',')) {
	        $temp_data[] = $data;
	        return $temp_data;
	    }
	    $user = explode(',', $data);
	    $temp_data = array_unique(array_filter($user));
	    return $temp_data;
	}

	// 组装筛选条件
	public function getFindInSetString($param)
	{
	    $string = '';
	    $hist_arr = $this->changeFormat($param['handler_id']);
	    foreach ($hist_arr as $key => $value) {
	        $string .= "FIND_IN_SET({$value}, {$param['field']}) OR ";
	    }
	    $string = rtrim($string, 'OR ');
	    return $string;
	}




}