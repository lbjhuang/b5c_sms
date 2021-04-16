<?php

class FinanceNotifyService
{

	public function getSendInfo($type, $id)
	{
		$msg = $this->sendType[$type]; $user = [];
		switch (strval($type)) {
			case TbPurPaymentAuditModel::$return_type_before_confirmed:
				$auditInfo = (new FinanceRepository())->getAuditInfo(['id' => $id], 'created_by, payment_audit_no, id, snapshot_audit_user, accounting_audit_user');
				$msg = $auditInfo['created_by'] . "的" . $auditInfo['payment_audit_no'] . "付款单，内容有变动，请查阅相关内容。";
				if (strstr($auditInfo['accounting_audit_user'], $auditInfo['snapshot_audit_user'])) {
					$user = explode('->', trim(explode($auditInfo['snapshot_audit_user'], $auditInfo['accounting_audit_user'])[0], '->'));
				}
				break;
			case TbPurPaymentAuditModel::$return_type_after_confirmed:
				$auditInfo = (new FinanceRepository())->getAuditInfo(['id' => $id], 'created_by, payment_audit_no, id, payment_audit_no_old, accounting_audit_user');
				$msg = "{$auditInfo['created_by']} 的 {$auditInfo['payment_audit_no_old']} 付款单付款失败，已重新提交，
新的付款单号为 {$auditInfo['payment_audit_no']}，请查阅相关内容。";
				$user = explode('->', $auditInfo['accounting_audit_user']);
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
		$auditInfo = (new FinanceRepository())->getAuditInfo(['id' => $id], '');
		foreach ($typeArr as $key => $value) {
			list($msg, $user) = $this->getSendInfo($value, $id);
			$title = '财务审批流程通知';
			if ($user && $msg && $auditInfo) {
				foreach ($user as $v) {
					$this->sendWxMsg($title, $v, $msg, $auditInfo);
				}
			}
		}
	}


    public function sendWxMsg($title, $user, $msg, $auditInfo)
	{
        $sendWx = $this->getWxUserSql($user);
        $data = ">**title** 
>{$msg}
>[查阅](detail_url)";
		$tab_data = [
		    'url' => urldecode('/index.php?' . http_build_query(['m' => 'finance', 'a' => 'general_payment_detail', 'payment_audit_id' => $auditInfo['id'], 'source_cd' => 'N003010004'])),
		    'name' => '一般付款单详情'
		];
		$replaceData = [
			'title' => $title,
			'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
		];
		$data = $this->replace_template_var($data, $replaceData);
		$res = ApiModel::WorkWxSendMarkdownMessage($sendWx, $data);
		if (200000 != $res['code']) {
			@SentinelModel::addAbnormal('财务通知微信发送消息失败', "{$sendWx}", [$res, $msg], 'fin_flow_notice');	
		}
		Logs(json_encode([$user, $auditInfo, $res, $msg]), __FUNCTION__.'----企业微信发送日志', 'tr');
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