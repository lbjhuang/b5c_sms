<?php

class NotifyService
{
	public $sendType = [
		'1' => "发起了一份合同审批，请尽快前往ERP审批",  // 待领导审批
		'2' => "发起了一份合同审批，请尽快前往ERP审批", // 待法务审批
		'3' => "发起了一份合同审批，请尽快前往ERP审批", // 待财务审批
		'4' => "你提交的合同已经审批通过，请尽快到法务部找leader_seal_by盖章归档", // 合同发起人
		'5' => "你所审核的合同已经财务审批通过，请尽快联系业务同事进行定稿合同上传", // 审批法务同事
		'6' => "你发起的合同审批，被退回，请知悉", // 领导/转审人退回后
		'7' => "（领导）转审给你一份合同审批，请尽快前往ERP审批",
		'8' => "发起的合同审批，需要盖章",
		'9' => "发起了一份合同审批，请尽快前往ERP确认归档",
		'10' => "你发起的合同审批，已经盖过章，请尽快前往erp上传盖章版定稿合同",
		'11' => "你发起的合同审批，已经审批完成，请知悉",

	];

	public function getSendInfo($type, $id)
	{
		$msg = $this->sendType[$type];
		switch (strval($type)) {
			case '1':
				$created_by = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$msg = $created_by . $this->sendType[$type];
				$user = (new ContractService())->getAuditPeople(['type' => '1', 'name' => $created_by]);
				break;
			case '2':
				$created_by = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$msg = $created_by . $this->sendType[$type];
				$user = (new ContractService())->getAuditPeople(['type' => '2', 'name' => $created_by]);
				break;
			case '3':
				$created_by = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$msg = $created_by . $this->sendType[$type];
				$user = (new ContractService())->getAuditPeople(['type' => '3']);
				break;
			case '4':
				$user = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$leader_seal_by = (new ContractService())->getSealLegal($id);
				$msg = str_replace("leader_seal_by", $leader_seal_by, $msg);
				break;
			case '5':
				$map['contract_id'] = $id;
				$map['type'] = ['in', '1,2'];
				$userInfo = (new ContractRepository())->getAuditInfo($map, 'created_by');
				$user = array_column($userInfo, 'created_by');
				$msg = $this->sendType[$type];
				break;
			case '6':
				$user = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				break;
			case '7':
				$leader_by = (new ContractRepository())->getInfo(['ID' => $id], 'leader_by')['leader_by'];
				$msg = $leader_by . $this->sendType[$type];
				$user = (new ContractRepository())->getInfo(['ID' => $id], 'transfer_by')['transfer_by'];
				break;
			case '8':
				$created_by = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$msg = $created_by . $this->sendType[$type];
				$user = (new ContractService())->getSealLegal($id);
				break;
			case '9':
				$created_by = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				$msg = $created_by . $this->sendType[$type];
				$user = TbCrmContractModel::LEGAL_FILE_PERSON;
				break;
			case '10':
				$user = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				break;
			case '11':
				$user = (new ContractRepository())->getInfo(['ID' => $id], 'created_by')['created_by'];
				break;
		}
		return [$msg, $user];
	}

	
	public function send($id, $type)
	{
		if (strstr($type, ',')) {
			$typeArr = explode(',', $type);
		} else {
			$typeArr[] = $type; 
		}
		$contractInfo = (new ContractRepository())->getInfo(['ID' => $id], 'created_by, CON_NO, CON_NAME, CREATE_TIME, ID');
		foreach ($typeArr as $key => $value) {
			list($msg, $user) = $this->getSendInfo($value, $id);
			$title = '合同审批流程通知';
			$this->sendWxMsg($title, $user, $msg, $contractInfo);
			//$user = DataModel::userToEmail($user);
			//$this->sendEmail($title, $user, $msg);
		}
	}

	public function sendEmail($title, $user, $msg)
	{
		return MailModel::mail_send($title, $msg, null, $user);
	}

    public function sendWxMsg($title, $user, $msg, $contractInfo)
	{
        $sendWx = $this->getWxUserSql($user);
        $data = ">**title** 
>{$msg}
>合同编号  ：<font color=info >CON_NO</font> 
>合同名称：<font color=warning >CON_NAME</font> 
>发起时间：<font color=info >CREATE_TIME</font>
>如需查看详情，，请点击[查看详情](detail_url)";
		$tab_data = [
		    'url' => urldecode('/index.php?' . http_build_query(['m' => 'contract', 'a' => 'contract_view', 'ID' => $contractInfo['ID']])),
		    'name' => '合同详情'
		];
		$replaceData = [
			'title' => $title,
			'CON_NO' => $contractInfo['CON_NO'],
			'CON_NAME' => $contractInfo['CON_NAME'],
			'CREATE_TIME' => $contractInfo['CREATE_TIME'],
			'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
		];
		$data = $this->replace_template_var($data, $replaceData);
		$res = ApiModel::WorkWxSendMarkdownMessage($sendWx, $data);
		if (200000 != $res['code']) {
			@SentinelModel::addAbnormal('合同流程审批通知微信发送消息失败', "{$sendWx}", [$res, $msg], 'contract_audit_notice');	
		}
		Logs(json_encode([$contractInfo, $user, $res, $msg]), __FUNCTION__.'----企业微信发送日志', 'tr');
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