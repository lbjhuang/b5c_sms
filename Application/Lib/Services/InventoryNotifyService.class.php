<?php

class InventoryNotifyService extends Service
{
	public static $_MODULE_INFO = [
		"1" => [
			'email' => [
				'title' => '发起了仓库盘点，请尽快审核',
			],
			'wx' => [
				'msg' => '有一个盘点单需要你审核',
			],
		],
		"2" => [
			'email' => [
				'title' => '发起了仓库盘点，请尽快审核',
			],
			'wx' => [
				'msg' => '有一个盘点单需要你审核',
			],
		],
		"3" => [
			'email' => [
				'title' => '你发起的仓库盘点需要进行复盘，请尽快前往ERP进行复盘',
			],
			'wx' => [
				'msg' => '有一个盘点单需要你复盘',
			],
		],
		"4" => [
			'email' => [
				'title' => '再次发起了仓库盘点审核，请尽快复核',
			],
			'wx' => [
				'msg' => '有一个盘点单需要你复核',
			],
		],
	];


	public function inveNotify($type, $data)
	{
		$inveInfo = (new InventoryRepository())->getInveInfo(['id' => $data['inve_id']], 'warehouse_cd, inve_no, created_by, created_at, id as inve_id');
		if(empty($inveInfo)) throw new Exception("盘点单{$data['inve_id']}信息缺失");
		list($authPeople, $msg) = $this->getNotifyPersonByType($type, $inveInfo);
		if (is_array($authPeople)) {
			foreach ($authPeople as $key => $value) {
				$this->inveWechatSend($msg['wx'], $value, $inveInfo);
				$this->inveEmailSend($msg['email'], $value, $inveInfo);
			}
		} else {
			$this->inveWechatSend($msg['wx'], $authPeople, $inveInfo);
			$this->inveEmailSend($msg['email'], $authPeople, $inveInfo);
		}
	}

	public function getNotifyPersonByType($type, $data)
	{
		switch (strval($type)) {
			case '1': // 盘点仓库负责人
				$msg = self::$_MODULE_INFO[$type];
				$msg['wx']['msg'] = $data['created_by'] . $msg['wx']['msg'];
				$authInfo = M('con_division_warehouse', 'tb_')->field('inventory_by')->where(['warehouse_cd' => $data['warehouse_cd']])->find();
				$authPeople = $authInfo['inventory_by'];
				break;
			case '2': // 盘点财务负责人
				$msg = self::$_MODULE_INFO[$type];
				list($amount, $finType) = (new InventoryService())->getDiffAmount($data['inve_id']); // 根据金额大小获取需要发送的人
				$authPeople = (new InventoryService())->getFinAuditByType($finType, $data);
				break;
			case '3': // 盘点仓库操作人
				$msg = self::$_MODULE_INFO[$type];
				$authPeople = $data['created_by'];

				break;
			case '4': // 盘点仓库负责人 盘点复核
				$msg = self::$_MODULE_INFO[$type];
				$msg['wx']['msg'] = $data['created_by'] . $msg['wx']['msg'];
				$authInfo = M('con_division_warehouse', 'tb_')->field('inventory_by')->where(['warehouse_cd' => $data['warehouse_cd']])->find();
				$authPeople = $authInfo['inventory_by'];

				break;
			
			default:
				# code...
				break;
		}
		if (strstr($authPeople, ',')) { // 有多个
			$authPeople = explode(',', $authPeople);
		}
		return [$authPeople, $msg];
	}

	public function inveEmailSend($msg, $user, $inveInfo)
	{
		$title = "盘点通知";
		$warehouse_cd = cdVal($inveInfo['warehouse_cd']);
		$emailContent = $msg['title'] . "<br/>盘点单号：{$inveInfo['inve_no']}<br/>盘点仓库：{$warehouse_cd}<br/>盘点操作人：{$inveInfo['created_by']}<br/>";
		$user = DataModel::userToEmail($user);
        $email = new SMSEmail();
        if(!$email->sendEmail($user, $title, $emailContent)) {
        	@SentinelModel::addAbnormal('盘点提醒邮件推送失败', $email->getError(), [$user, $title, $emailContent], 'inve_notice'); 
        }
	}

	public function inveWechatSend($msg, $authPeople, $inveInfo)
	{
        $sendWx = $this->getWxUserSql($authPeople);
        $data = ">**盘点通知** 
> 盘点发起人：<font color=info >created_by</font>
>盘点仓库：<font color=warning >warehouse_cd</font> 
>盘点单号  ：<font color=info >inve_no</font> 
>{$msg['msg']}，[查看详情](detail_url)";
		$tab_data = [
		    'url' => urldecode('/index.php?' . http_build_query(['m' => 'stock', 'a' => 'inventory_detail', 'idd' => $inveInfo['inve_id']])),
		    'name' => '盘点详情'
		];
		$replaceData = [
			'created_by' => $inveInfo['created_by'],
			'warehouse_cd' => cdVal($inveInfo['warehouse_cd']),
			'inve_no' => $inveInfo['inve_no'],
			'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
		];
		$data = $this->replace_template_var($data, $replaceData);
		$res = ApiModel::WorkWxSendMarkdownMessage($sendWx, $data);
		if (200000 != $res['code']) {
			@SentinelModel::addAbnormal('盘点通知微信发送消息失败', "{$sendWx}", [$res, $msg], 'inve_notice');	
		}
		Logs([$inveInfo, $authPeople, $res], __FUNCTION__, __CLASS__);
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

	public function replace_template_var($template, $data)
	{
	    if ($data) {
	        foreach ($data as $k => $v) {
	            $template = str_replace($k, $v, $template);
	        }
	    }
	    return $template;
	}
}