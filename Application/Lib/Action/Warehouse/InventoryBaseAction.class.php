<?php

class InventoryBaseAction extends BaseAction
{
	// 方法如果在权限列表中，则需要做权限校验，注意填写的名称皆为小写，而不是驼峰命名
	public $authList = [
		'inventory_by' => [ // 盘点仓库负责人
			'inveauditconfirm'
		],
		/*'inventory_finance_by' => [ // 盘点财务负责人
			'invefinconfirm'
		],*/
		'inventory_operate_by' => [ // 盘点仓库操作人/发起人
			'invecreate',
			'inveexport',
			'saveinvegoods',
			'inveauditapply',
		]
	];

	public function __construct()
	{
		if ($_ENV["NOW_STATUS"] !== 'local') {
			$this->checkAuth($this->authList);
		}
		if (!DataModel::userNamePinyin() && $_ENV["NOW_STATUS"] !== 'local') {
			return $this->ajaxError('','获取用户名失败，请重新登录');
		}
	}

	public function checkAuth($authList)
	{		
		foreach ($authList as $key => $value) { // 获取该盘点仓库对应的各个权限人
			if (in_array(ACTION_NAME, $value)) {
				$userName = DataModel::userNamePinyin();
				$requestData = DataModel::getDataNoBlankToArr()['data'];
				$warehouse_cd = $requestData['warehouse_cd'] ? $requestData['warehouse_cd'] : $requestData['base_data']['warehouse_cd'];
				$warehouse_cd = $warehouse_cd ? $warehouse_cd : json_decode($_POST['export_params'], true)['data']['warehouse_cd'];
				if (!$warehouse_cd) {
					if (!$requestData['inve_id']) {
						return $this->ajaxError('','缺失仓库参数或盘点id，无法获取对应的仓库的权限人信息');
					}
					$warehouse_cd = (new InventoryRepository())->getInveInfo(['id' => $requestData['inve_id']], 'warehouse_cd')['warehouse_cd'];
					if (!$warehouse_cd) {
						return $this->ajaxError($warehouse_cd,'缺失仓库参数，无法获取对应的仓库的权限人信息');
					}
				}
				$authInfo = M('con_division_warehouse', 'tb_')->field($key)->where(['warehouse_cd' => $warehouse_cd])->find();
				if (!strstr($authInfo[$key], $userName)) {
					$log = [];
					$log['lastsql'] = M()->_sql();
					$log['key'] = $key;
					$log['res'] = strstr($authInfo[$key], $userName);
					$log['authInfo'] = $authInfo;
					$log['warehouse_cd'] = $warehouse_cd;
					$log['action_name'] = ACTION_NAME;
					ELog::add('该用户无操作权限：'.json_encode($log).M()->getDbError(),ELog::ERR);
					return $this->ajaxError($userName,'无权限操作');
				}
			}
		}
	}
}