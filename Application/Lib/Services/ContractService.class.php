<?php

class ContractService
{
	public $leaderType;
	public function __construct()
	{
	    $this->repository = new ContractRepository();
	}

	public function getFlowData($data)
	{

		$contractInfo = $this->repository->getInfo(['ID' => $data['contract_id']], 'created_by, leader_by, transfer_by, legal_by, finance_by,CON_COMPANY_CD,audit_status_cd,audit_status_sec_cd');
		$sealLegal = $this->getSealLegal($data['contract_id'], $contractInfo);
		$flowArr = [
			['msg' => "{$contractInfo['created_by']}发起流程", 'cd' => TbCrmContractModel::SUBMITTING],
			['msg' => "领导{$contractInfo['leader_by']}审批", 'cd' => TbCrmContractModel::LEADER_SUBMITTING]
		];
		$current_show_flag = 'N';
		if ($contractInfo['transfer_by']) {
			if ($contractInfo['audit_status_cd'] === TbCrmContractModel::TRANSFER_SUBMITTING) {
				$current_show_flag = 'Y';
			}
			$contractAuditInfo = $this->repository->getAuditInfo(['contract_id' => $data['contract_id'], 'type' => '2'],'created_by');
			foreach ($contractAuditInfo as $key => $value) {
				$flowArr[] = ['msg' => "转审人{$value['created_by']}审批", 'cd' => TbCrmContractModel::TRANSFER_SUBMITTING];
			}
			$flowArr[] = ['msg' => "转审人{$contractInfo['transfer_by']}审批", 'cd' => TbCrmContractModel::TRANSFER_SUBMITTING, 'current_show' => $current_show_flag];
		}
		$flowArr[] = ['msg' => "法务{$contractInfo['legal_by']}审批", 'cd' => TbCrmContractModel::LEGAL_SUBMITTING];
		$flowArr[] = ['msg' => "财务{$contractInfo['finance_by']}审批", 'cd' => TbCrmContractModel::FIN_SUBMITTING];

		$flowArr[] = ['msg' => "法务{$sealLegal}盖章", 'cd' => TbCrmContractModel::UPLOADING, 'cd_sec' => TbCrmContractModel::UPLOADING_FIRST];
		$flowArr[] = ['msg' => "{$contractInfo['created_by']}上传定稿合同", 'cd' => TbCrmContractModel::UPLOADING, 'cd_sec' => TbCrmContractModel::UPLOADING_SECOND];
		$flowArr[] = ['msg' => TbCrmContractModel::LEGAL_FILE_PERSON . "确认归档", 'cd' => TbCrmContractModel::UPLOADING, 'cd_sec' => TbCrmContractModel::UPLOADING_THIRD];
		$flowArr[] = ['msg' => "审批完成", 'cd' => TbCrmContractModel::FINISH];

		foreach ($flowArr as $key => $value) {
			$flowArr[$key]['current_show'] = $flowArr[$key]['current_show'] ? $flowArr[$key]['current_show'] : 'N';
			if ($value['cd'] === $contractInfo['audit_status_cd']) {
				if ($value['cd_sec']) {
					if ($value['cd_sec'] === $contractInfo['audit_status_sec_cd']) {
						$flowArr[$key]['current_show'] = 'Y';
					}
				} else {
					if ($current_show_flag === 'N') { // 除当前流程状态是转审人流程之外
						$flowArr[$key]['current_show'] = 'Y';
					}
				}
			}
		}
		return $flowArr;
	}

	public function getSealLegal($contractId, $contractInfo)
	{
		if ($contractInfo['CON_COMPANY_CD']) { // 目前产品要求暂时直接写死对应的通知人，后期改动频繁的话，可根据角色来获取对应的通知人
			$CON_COMPANY_CD = $contractInfo['CON_COMPANY_CD'];
		}
		elseif ($contractId) {
			$CON_COMPANY_CD = $this->repository->getInfo(['ID' => $contractId], 'CON_COMPANY_CD')['CON_COMPANY_CD'];
		}
		else {
			throw new Exception("缺失合同我方公司信息");
		}
		if (TbCrmContractModel::LEGAL_COM_NO == $CON_COMPANY_CD) {
			return TbCrmContractModel::SEAL_FIRST;	
		} else {
			return TbCrmContractModel::SEAL_SECOND;
		}
	}


	public function flow($data)
	{
		return $this->getFlowData($data);	
	}

	public function saveFileStatus($data)
	{
		$this->auth($data['contract_id']);
		$where['ID'] = $data['contract_id'];
		$save['file_status'] = '1';
		$save['audit_status_sec_cd'] = '';
		$save['audit_status_cd'] = TbCrmContractModel::FINISH;
		$res = $this->repository->saveContract($where, $save);
		if ($res !== false) {
			$this->addLog($data['contract_id'], '法务确认归档');
			(new NotifyService())->send($data['contract_id'], '11');
		}
		return $res;
	}


	public function saveSealStatus($data)
	{
		$this->auth($data['contract_id']);
		$where['ID'] = $data['contract_id'];
		$save['seal_status'] = $data['seal_status'];
		$save['audit_status_sec_cd'] = TbCrmContractModel::UPLOADING_SECOND;
		$res = $this->repository->saveContract($where, $save);
		if ($res !== false) {
			$this->addLog($data['contract_id'], '法务确认盖章');
			(new NotifyService())->send($data['contract_id'], '10');			
		}
		return false;
	}

	public function getAuditNameInfo($data)
	{
		$audit_status_cd = $this->repository->getInfo(['ID' => $data['contract_id']], 'audit_status_cd')['audit_status_cd'];
		$name = $this->repository->getInfo(['ID' => $data['contract_id']], 'created_by')['created_by'];
		switch ($audit_status_cd) {
			case TbCrmContractModel::LEADER_SUBMITTING:
				$person = $this->getAuditPeople(['name' => $name, 'type' => '1']);
				$type = '领导';
				break;
			case TbCrmContractModel::LEGAL_SUBMITTING:
				$person = $this->getAuditPeople(['name' => $name, 'type' => '2']);
				$type = '法务';
				break;
			case TbCrmContractModel::FIN_SUBMITTING:
				$person = $this->getAuditPeople(['name' => $name, 'type' => '3']);
				$type = '财务';
				break;	
			case TbCrmContractModel::TRANSFER_SUBMITTING:
				$person = $this->repository->getInfo(['ID' => $data['contract_id']], 'transfer_by')['transfer_by'];
				$type = '转审人';
				break;
			default:
				# code...
				break;
		}
		return ['type' => $type, 'person' => $person];
	}

	public function getLegalPeople()
	{
		$res = $this->repository->getLegalAudit();
		return array_column($res, 'name');

	}

	public function getLeaderByDeptId($deptId, $name)
	{
		// 该用户属于多个部门的，返回自己
		/*本部门有领导
			单个
				发起人是领导
					往上一级父部门找，直到找到结果或部门为Gshopper结束
				发起人不是领导
					END
			多个
				发起人是领导
					发起人是该部门最大领导？
						是
							往上一级父部门找，直到找到结果或部门为Gshopper结束
						否
							找该领导上一个级别
				发起人不是领导，找级别最小的
					END
		本部门没有领导
			往上一父部门找
				父部门有领导
					单个
						END
					多个
						找级别最小的
				父部门没有领导
					往上一级父部门找，直到找到结果或部门为Gshopper结束*/
		$deptNums = 0;
		$deptNums = $this->repository->getDeptInfoByDeptName(['hc.ERP_ACT' => $name], 'hed.ID1');
		$countDeptNums = strval(count($deptNums));
		if ($countDeptNums === '0') {
			throw new Exception("该用户{$name}尚未找到对应的部门，请先核实");
		}
		if ($countDeptNums !== '1') { // 该用户属于多个部门的，返回自己 from产品
			$this->leaderType = '6-该用户属于多个部门，返回自己';
			return $name;
		}
		$res = []; $count = 0;
		$res = $this->repository->getLeaderByDeptId($deptId);
		if (strval($res[0]['DEPT_LEVEL']) === '0') {
			$this->leaderType = '1-该用户的部门属于顶级部门';
			return $res[0]['ERP_ACT'];
		}
		if ($res) {
			$count = count($res);
			if ($count === 1) {
				$res = $res[0];
				if (strval($res['ERP_ACT']) === $name) {
					return $this->getLeaderByDeptId($res['PAR_DEPT_ID'], $name);
				}
				$this->leaderType = '2-本部门有且只有一个领导，且该用户不是领导';
				return $res['ERP_ACT'];
			} else {
				$leaderArr = array_column($res, 'ERP_ACT');
				if (in_array($name, $leaderArr)) {
					if ($leaderArr[$count - 1] === $name) {
						return $this->getLeaderByDeptId($res[$count - 1]['PAR_DEPT_ID'], $name);
					}
					$this->leaderType = '3-本部门有且多个领导，该用户属于领导之一';
					return $leaderArr[array_search($name, $leaderArr) + 1];
				}
				$this->leaderType = '4-本部门有且多个领导，该用户不属于领导之一';
				return $leaderArr[0];
			}
		} else {
			$parentId = $this->repository->getDeptInfoByDeptId($deptId, 'PAR_DEPT_ID');
			$res = $this->repository->getLeaderByDeptId($parentId);
			if (!$res) {
				$parentSecId = $this->repository->getDeptInfoByDeptId($parentId, 'PAR_DEPT_ID');
				return $this->getLeaderByDeptId($parentSecId, $name);
			}
			$this->leaderType = '5-本部门没有领导，往上级获取领导';
			return $res[0]['ERP_ACT'];
		}
		return false;
	}

	public function getAuditPeople($data)
	{
		// 获取类型
		switch (strval($data['type'])) {
			case '1': // 领导
				// 根据名称找对应的部门ID
				$deptId = $this->repository->getDeptIdByName($data['name']);
				if (!$deptId) {
					throw new Exception("无法找到账号为{$data['name']}的部门ID{$deptId}，请先核实");
				}
				$auditPerson = $this->getLeaderByDeptId($deptId, $data['name']);
				$lastSql = M()->_sql();
				ELog::add(['msg'=>'获取领导','request'=> [$deptId, $data, $lastSql],'response'=>$auditPerson],ELog::INFO);
				break;
			case '2': // 法务
				$deptId = $this->repository->getDeptIdByName($data['name']);
				if (!$deptId) {
					throw new Exception("无法找到账号为{$data['name']}的部门ID{$deptId}，请先核实");
				}
				$auditPerson = $this->repository->getDeptInfoByDeptId($deptId);
				break;
			case '3': // 获取财务
				$auditPerson = $this->repository->getFinanceAudit();
				break;	
			default:
				break;
		}
		return $auditPerson;
	}

	public function getAuditInfo($data)
	{
		$res = $this->repository->getAuditInfo(['contract_id' => $data['contract_id']], 'id,contract_id,type,remark,created_by');
		return $this->adjustAuditData($res);
	}

	// 定稿合同
	public function uploadAuditContract($params)
	{
		$this->auth($params['contract_id']);
		if ($_FILES) {
		    // 图片上传
		    $fd = new FileUploadModel();
		    $TbCrmContractModel = D('TbCrmContract');
		    if ($_FILES['SP_ANNEX_ADDR1'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR1'])) {
		        foreach ($fd->info as $key => $value) {
		            $finfo [] = [
		                'contract_agreement' => $params ['contract_agreement'][$key], // 合同协议类型（主协议&&补充协议）
		                'file_name' => $value ['savename'],
		                'upload_name' => $_FILES['SP_ANNEX_ADDR1']['name'][$key]
		            ];
		        }
		        $save['SP_ANNEX_ADDR1'] = json_encode($finfo);
		        $save['audit_status_sec_cd'] = TbCrmContractModel::UPLOADING_THIRD;
		        $contractNo = $this->repository->getInfo(['ID' => $params['contract_id']])['CON_NO'];
		        if (!$contractNo) {
		        	$save['CON_NO'] = $TbCrmContractModel->createContractNo();
		        }
		        if ($this->repository->saveContract(['ID' => $params['contract_id']], $save)) {
		        	$this->addLog($params['contract_id'], '业务上传定稿合同');
		        	(new NotifyService())->send($params['contract_id'], '9');
		        }
		    } else {
		        throw new Exception("定稿合同上传失败，参数缺失");
		    }
		} else {
		        throw new Exception("定稿合同上传失败，请先上传文件");
		}
	}

	public function adjustAuditData($data)
	{
		$temp = [];
		foreach ($data as $key => $value) {
			$temp[$key] = $value;
			$temp[$key]['type'] = TbCrmContractModel::$auditType[$value['type']];
		}
		return $temp;
	}

	public function addLog($id, $msg)
	{
		if (isLocalEnv()) {
		    //本地调试不做日志记录
		    return true;
		}
		$insertOneResult = MongoDbModel::client()->insertOne('tb_crm_contract_log', [
		            'contract_id' => strval($id),
		            'msg' => $msg,
		            'time' => date('Y-m-d H:i:s', time()),
		            'user' => DataModel::userNamePinyin()
		 ]);
		return $insertOneResult;
	}

	public function getLog($data)
	{
		$res = MongoDbModel::client()->find('tb_crm_contract_log',['contract_id'=> strval($data['contract_id'])]);
		$returnList = [];
		foreach ($res as $key => $value) {
			$returnList[$key]['contract_id'] = $value['contract_id'];
			$returnList[$key]['msg'] = $value['msg'];
			$returnList[$key]['time'] = $value['time'];
			$returnList[$key]['user'] = $value['user'];
		}
		return $returnList;
	}

	public function auth($contract_id)
	{
		$authInfo = $this->repository->getInfo(['ID' => $contract_id], 'audit_status_cd, created_by, transfer_by, leader_by, legal_by, finance_by, ID, audit_status_sec_cd');
		if (!$authInfo) {
			throw new Exception("暂无法获取合同ID为{$contract_id}的操作人数据，请先核实");
		}
		switch ($authInfo['audit_status_cd']) {
			case TbCrmContractModel::SUBMITTING:
				$auth = $authInfo['created_by'];
				break;
			case TbCrmContractModel::LEADER_SUBMITTING:
				// $auth = $authInfo['leader_by'];
				$auth = $this->getAuditNameInfo(['contract_id' => $contract_id])['person'];
				break;
			case TbCrmContractModel::LEGAL_SUBMITTING:
				//$auth = $authInfo['legal_by'];
				$legalArr = $this->getLegalPeople();
				$auth = implode(',', $legalArr);
				break;
			case TbCrmContractModel::TRANSFER_SUBMITTING:
				$auth = $authInfo['transfer_by'];
				break;
			case TbCrmContractModel::FIN_SUBMITTING:
				//$auth = $authInfo['finance_by'];
				$auth = $this->getAuditNameInfo(['contract_id' => $contract_id])['person'];
				break;
			case TbCrmContractModel::UPLOADING:
				if ($authInfo['audit_status_sec_cd'] === TbCrmContractModel::UPLOADING_FIRST) {
					$legalArr = $this->getLegalPeople();
					$auth = implode(',', $legalArr);
				}
				if ($authInfo['audit_status_sec_cd'] === TbCrmContractModel::UPLOADING_SECOND) {
					$auth = $authInfo['created_by'];
				}
				if ($authInfo['audit_status_sec_cd'] === TbCrmContractModel::UPLOADING_THIRD) {
					$legalArr = $this->getLegalPeople();
					$auth = implode(',', $legalArr);
				}
				break;
			default:
				# code...
				break;
		}
		$user = DataModel::userNamePinyin();
		if (!$user) {
			throw new Exception("暂无法获取账号，请先重新登录");
		}
		if (!$auth) {
			throw new Exception("暂无法获取有权限的操作人数据，请先核实{$authInfo['audit_status_cd']}");
		}
		if (false === strstr($auth, $user)) {
			throw new Exception("该登录账号{$user},没有权限操作，请用{$auth}账号进行操作");
		}
	}



	public function saveLegalFile($params)
	{
		$res = false;
		if (!$params['ID']) {
			throw new Exception("待盖章合同上传失败，参数缺失{$params['ID']}");
		}
		if ($_FILES && $params['ID']) {
		    // 图片上传
		    $save = [];
		    $fd = new FileUploadModel();
		    if ($_FILES['SP_ANNEX_ADDR4'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR4'])) {
		        foreach ($fd->info as $key => $value) {
		            $finfo [] = [
		                'contract_agreement' => $params ['contract_agreement'][$key], // 合同协议类型（主协议&&补充协议）
		                'file_name' => $value ['savename'],
		                'upload_name' => $_FILES['SP_ANNEX_ADDR4']['name'][$key]
		            ];
		        }
		        $save['SP_ANNEX_ADDR4'] = json_encode($finfo);
		        $res = M('crm_contract', 'tb_')->where(['ID' => $params['ID']])->save($save);
		        if ($res === false) {
		        	ELog::add(['msg'=>'更新法务待盖章合同失败','params'=>$params, 'sql' => M()->getDbError()], ELog::INFO);
		        	throw new Exception('更新法务待盖章合同失败');
		        }
		    } else {
		        throw new Exception("待盖章合同上传失败，参数缺失");
		    } 
		}
		if (strval($params['is_synchro']) === 'Y') {
			$save = [];
			$SP_ANNEX_ADDR3 = M('crm_contract', 'tb_')->where(['ID' => $params['ID']])->getField('SP_ANNEX_ADDR3');
			$save['SP_ANNEX_ADDR4'] = $SP_ANNEX_ADDR3;
			$res = M('crm_contract', 'tb_')->where(['ID' => $params['ID']])->save($save);
			if ($res === false) {
				ELog::add(['msg'=>'更新法务待盖章合同失败','params'=>$params, 'sql' => M()->getDbError()], ELog::INFO);
				throw new Exception('更新法务待盖章合同失败');
			}
			$this->addLog($params['ID'], '同步合同');
		}
		return $res;
	}
	public function audit($data)
	{
		$this->auth($data['contract_id']);
		list($save, $auditSave, $msg, $sendType, $auditDelSave) = $this->getSaveInfo($data);
		if (empty($save)) throw new Exception("type值传参有误，请先核实，type:{$data['type']} -- id:{$data['contract_id']}");
		$res = $this->repository->saveContract(['ID' => $data['contract_id']], $save);
		if ($auditSave) $resSec = $this->repository->saveContractAudit('',$auditSave);
		if ($auditDelSave) $this->repository->saveContractAudit($data['contract_id'], $auditDelSave);
		if ($sendType) $resMsg =  (new NotifyService())->send($data['contract_id'], $sendType);
		$this->addLog($data['contract_id'], $msg);
		return [true, $msg];
	}

	public function checkLegalPerson($person)
	{
		$res = $this->getLegalPeople();
		if (!in_array($person, $res)) {
			throw new Exception("该用户{$person}不在法务部门的员工列表中，请先核实");
		}
	}

	public function checkPerson($person)
	{
		// 检查该名字是否正确（admin表登录名称）
		$res = M('admin')->where(['M_NAME'=> $person])->getField('M_ID');
		if (!$res) {
			throw new Exception("转审人不存在{$person}");	
		}
		// 不允许本身自己
		$loginName = DataModel::userNamePinyin();
		if ($loginName === $person) {
			throw new Exception("禁止转审给本人");
		}
	}

	public function getSaveInfo($data)
	{
		$audit_status_cd = $this->repository->getInfo(['ID' => $data['contract_id']], 'audit_status_cd')['audit_status_cd'];
		$created_by = $this->repository->getInfo(['ID' => $data['contract_id']], 'created_by')['created_by'];
		if (empty($audit_status_cd)) throw new Exception("无法获取该合同id{$data['contract_id']}的审核状态");
		$auditSave = $save = []; $sendType = '';
		switch ($audit_status_cd) {
			case TbCrmContractModel::SUBMITTING:
				break;
			case TbCrmContractModel::LEADER_SUBMITTING:
				if ($data['type'] == '1') {
					$msg = '领导审批驳回';
					$save['audit_status_cd'] = TbCrmContractModel::CANCEL;
				}	
				if ($data['type'] == '2') {
					$msg = '领导审批通过';
					$save['audit_status_cd'] = TbCrmContractModel::LEGAL_SUBMITTING;
					$save['legal_by'] = $this->getAuditPeople(['name' => $created_by, 'type' => '2']);
					$sendType = '2';
				}
				if ($data['type'] == '4') {
					$msg = '领导审批退回';					
					$save['audit_status_cd'] = TbCrmContractModel::SUBMITTING;
					$sendType = '6';
					$auditDelSave['deleted_at'] = date('Y-m-d H:i:s', time());
					$auditDelSave['deleted_by'] = DataModel::userNamePinyin();
				}
				if ($data['type'] == '3') {
					$this->checkPerson($data['transfer_by']);
					$msg = '发起转审';
					$save['audit_status_cd'] = TbCrmContractModel::TRANSFER_SUBMITTING;
					$save['transfer_by'] = $data['transfer_by'];
					$sendType = '7';
				} 
				$type = '4';
				break;
			case TbCrmContractModel::LEGAL_SUBMITTING:
				if ($data['type'] == '1') {
					$msg = '法务审批驳回';
					$save['audit_status_cd'] = TbCrmContractModel::CANCEL;
				} 
				if ($data['type'] == '2') {
					$msg = '法务审批通过';
					$save['audit_status_cd'] = TbCrmContractModel::FIN_SUBMITTING;
					$save['finance_by'] = $this->getAuditPeople(['name' => $created_by, 'type' => '3']);
					$sendType = '3';
				} 
				$type = '1';
				break;
			case TbCrmContractModel::TRANSFER_SUBMITTING:
				if ($data['type'] == '1') {
					$msg = '转审人审批驳回';
					$save['audit_status_cd'] = TbCrmContractModel::CANCEL;
				} 
				if ($data['type'] == '2') {
					$msg = '转审人审批通过';
					$save['audit_status_cd'] = TbCrmContractModel::LEGAL_SUBMITTING;
					$save['legal_by'] = $this->getAuditPeople(['name' => $created_by, 'type' => '2']);
					$sendType = '2';
				} 
				if ($data['type'] == '3') {
					$this->checkPerson($data['transfer_by']);
					$msg = '发起转审';
					$save['audit_status_cd'] = TbCrmContractModel::TRANSFER_SUBMITTING;
					$save['transfer_by'] = $data['transfer_by'];
					$sendType = '7';
				}
				if ($data['type'] == '4') {
					$msg = '转审人审批退回';					
					$save['audit_status_cd'] = TbCrmContractModel::SUBMITTING;
					$sendType = '6';
					$auditDelSave['deleted_at'] = date('Y-m-d H:i:s', time());
					$auditDelSave['deleted_by'] = DataModel::userNamePinyin();
				} 
				$type = '2';
				break;
			case TbCrmContractModel::FIN_SUBMITTING:
				if ($data['type'] == '1') {
					$msg = '财务审批驳回';
					$save['audit_status_cd'] = TbCrmContractModel::CANCEL;
				} 
				if ($data['type'] == '2') {
					$msg = '财务审批通过';
					$save['audit_status_cd'] = TbCrmContractModel::UPLOADING;
					$save['audit_status_sec_cd'] = TbCrmContractModel::UPLOADING_FIRST;
					$save['file_by'] = TbCrmContractModel::LEGAL_FILE_PERSON;
					$save['seal_by'] = $this->getSealLegal($data['contract_id']);
					$sendType = '4,8';
				} 
				$type = '3';
				break;
			default:
				# code...
				break;
		}
		if ($type) $auditSave['type'] = $type; // 审核类型（财务审核，法务审核，转审, 领导审批）
		if ($data['remark'] && $type) $auditSave['remark'] = $data['remark']; 
		if ($auditSave && $data['remark']) $auditSave['contract_id'] = $data['contract_id'];
		return [$save, $auditSave, $msg, $sendType, $auditDelSave]; 
		
	}

	// 获取下拉相关信息
	public function getAuditCommonData()
	{
		$res = [];
		$res['isAutoRenew'] = BaseModel::isAutoRenew(); // 是否自动续约
		$res['conType'] = BaseModel::conType(); // 合作类型 
		$res['contractAgreement'] = BaseModel::contractAgreement(); // 合同类型
		$res['ourCompany'] = BaseModel::conCompanyCdNew(); // 我方公司  
		$res['contractState'] = BaseModel::contractState(); // 合同状态  
		$res['getCurrencyExtend'] = BaseModel::getCurrencyExtend(); // 货币单位 
		$res['hasTax'] = BaseModel::hasTax(); // 是否含税  
		$res['invoiceType'] = BaseModel::invoiceType(); // 发票类型
		$res['conDateType'] = BaseModel::conDateType(); // 合同时间类型（一次性，长期，年度）

		return $res;
	}
	public function getContractInfo($data)
	{
		$contract = D("TbCrmContract");
		$res = $contract->find($data['contract_id']);
		$res = $this->adjustContractData($res);
		return $res;
	}

	public function adjustContractData($data)
	{
		$data['finalizeContract'] = $data['SP_ANNEX_ADDR3'];
		$data['legalFinalizeContract'] = $data['SP_ANNEX_ADDR4'];
		$data['IS_RENEWAL'] = is_null($data['IS_RENEWAL']) ? '' : intval($data['IS_RENEWAL']);
		$data['contractAgreement'] = is_null($data['contractAgreement']) ? '' : intval($data['contractAgreement']);
		$data['CONTRACT_TYPE'] = is_null($data['CONTRACT_TYPE']) ? '' : intval($data['CONTRACT_TYPE']);
		$data['have_tax'] = is_null($data['have_tax']) ? '' : intval($data['have_tax']);
		if (strlen($data['CON_TYPE']) > 0 ) {
			$data['CON_TYPE'] = intval($data['CON_TYPE']);
		}
		//$data['IS_RENEWAL'] = BaseModel::isAutoRenew()[$data['IS_RENEWAL']]; // 是否自动续约
		//$data['CON_TYPE'] = BaseModel::conType()[$data['CON_TYPE']]; // 合作类型 
		//$data['contractAgreement'] = BaseModel::contractAgreement(); // 合同类型
		//$data['CON_COMPANY_CD'] = $data['CON_COMPANY_CD'] ? BaseModel::conCompanyCdNew()[$data['CON_COMPANY_CD']] : ''; // 我方公司  
		//$data['CON_STAT'] = BaseModel::contractState()[$data['CON_STAT']]; // 合同状态  
		//$data['currency_cd'] = BaseModel::getCurrencyExtend()[$data['currency_cd']]; // 货币单位 
		//$data['have_tax'] = BaseModel::hasTax()[$data['have_tax']]; // 是否含税  
		//$data['invoice_type_cd'] = BaseModel::invoiceType()[$data['invoice_type_cd']]; // 发票类型
		//$data['CONTRACT_TYPE'] = BaseModel::conDateType()[$data['CONTRACT_TYPE']]; // 合同时间类型（一次性，长期，年度）
		$data['audit_person'] = $this->getAuditPersonByStatus($data);
		return $data;
	}

	public function getAuditPersonByStatus($data) 
	{		
		$person = '';
		switch ($data['audit_status_cd']) {
			case TbCrmContractModel::SUBMITTING: // 草稿
				break;
			case TbCrmContractModel::LEADER_SUBMITTING: // 领导审批
				$res['type'] = '1';
				$res['name'] = $data['created_by'];
				$person = $this->getAuditPeople($res);
				break;
			case TbCrmContractModel::LEGAL_SUBMITTING: // 法务审批
				$res['type'] = '2';
				$res['name'] = $data['created_by'];
				$person = $this->getAuditPeople($res);
				break;
			case TbCrmContractModel::FIN_SUBMITTING: // 法务审批
				$res['type'] = '3';
				$person = $this->getAuditPeople($res);
				break;
			default:
				# code...
				break;
		}
		return $person;
	}

	public function getParamField()
	{
		$field = 'cc.*, cc.ID as contract_id, css.*, mfa.*';
		return $field;
	}

	public function adjustCreateData($data)
	{
		if ($data['START_TIME'] === '') {
			unset($data['START_TIME']);
		}
		if ($data['END_TIME'] === '') {
			unset($data['END_TIME']);
		}
		if ($data['IS_RENEWAL'] === '') {
			unset($data['IS_RENEWAL']);
		}
		if ($data['CONTRACT_TYPE'] === '') {
			unset($data['CONTRACT_TYPE']);
		}
		if ($data['have_tax'] === '') {
			unset($data['have_tax']);
		}
		if ($data['amount'] === '') {
			unset($data['amount']);
		}
		if ($data['supplier_id'] === '') {
			unset($data['supplier_id']);
		}
		if ($data['supplier_id']) {
			$data['SP_CHARTER_NO'] = M('crm_sp_supplier', 'tb_')->where(['ID' => $data['supplier_id']])->getField('SP_CHARTER_NO');
		}
		return $data;
	}

	public function saveContractFile($params, $contract)
	{
		if ($params['ID']) {
			$ret = $contract->find($_POST['ID']);
			if ($_FILES) {
			    $fd = new FileUploadModel();
			    if ($_FILES['SP_ANNEX_ADDR3']) {
			        if ($fd->saveFile($_FILES['SP_ANNEX_ADDR3'])) {
			            foreach ($fd->info as $key => $value) {
			                $finfo [] = [
			                    'contract_agreement' => $_POST ['contract_agreement'][$key],
			                    'file_name' => $value ['savename'],
			                    'upload_name' => $_FILES['SP_ANNEX_ADDR3']['name'][$key]
			                ];
			            }
			            // 数据库中已存在的
			            $already_existed = json_decode($ret ['SP_ANNEX_ADDR3'], true);
			            // 经过页面编辑后剩下的文件
			            $already_exist = explode(",", $_POST ['already_exist']);
			            // 将页面编辑后删除掉的数据从数据库中存有的数据中剔除掉
			            foreach ($already_existed as $k => &$v) {
			                if (!in_array($v ['file_name'], $already_exist) && !in_array($v ['upload_name'], $already_exist)) {
			                    unset($already_existed[$k]);
			                }
			                foreach ($finfo as $s => $j) {
			                    if ($j ['upload_name'] == $v ['upload_name']) unset($already_existed[$k]);
			                }
			            }
			            $_POST ['SP_ANNEX_ADDR3'] = json_encode(array_merge($finfo, (array)$already_existed));
			        }else {
			            $this->AjaxReturn($fd->error, L('文件上传失败'), 0);
			        }
			    } else {
			        $already_existed = json_decode($ret ['SP_ANNEX_ADDR3'], true);
			        if ($_POST ['already_exist']) {
			            $already_exist = explode(",", $_POST ['already_exist']);
			            foreach ($already_existed as $k => &$v) {
			                if (!in_array($v ['file_name'], $already_exist) && !in_array($v ['upload_name'], $already_exist)) unset($already_existed[$k]);
			            }
			            $_POST ['SP_ANNEX_ADDR3'] = json_encode($already_existed);
			        } else {
			            unset($_POST['SP_ANNEX_ADDR3']);
			        }
			    }
			} else {
			    $already_existed = json_decode($ret ['SP_ANNEX_ADDR3'], true);
			    if ($_POST ['already_exist']) {
			        $already_exist = explode(",", $_POST ['already_exist']);
			        foreach ($already_existed as $k => &$v) {
			            if (!in_array($v ['upload_name'], $already_exist) && !in_array($v ['file_name'], $already_exist)) unset($already_existed[$k]);
			        }
			        $_POST ['SP_ANNEX_ADDR3'] = json_encode($already_existed);
			    } else {
			        $_POST['SP_ANNEX_ADDR3'] = NULL;
			    }
			}
		} else {
			if ($_FILES) {
			    $fd = new FileUploadModel();
			    if ($_FILES['SP_ANNEX_ADDR3'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR3'])) {
			        foreach ($fd->info as $key => $value) {
			            $finfo [] = [
			                'contract_agreement' => $params ['contract_agreement'][$key], // 合同协议类型（主协议&&补充协议）
			                'file_name' => $value ['savename'],
			                'upload_name' => $_FILES['SP_ANNEX_ADDR3']['name'][$key]
			            ];
			        }
			        $_POST['SP_ANNEX_ADDR3'] = json_encode($finfo);
			    } else {
			    	$_POST['SP_ANNEX_ADDR3'] = NULL;
			    }
			}
		} 

	}

	public function createContractInfo($params)
	{
		$contract = D("TbCrmContract");
		if (IS_POST) {
			if ($params['type'] == '3') {
				if ($params['ID']) {
					$data['audit_status_cd'] = TbCrmContractModel::CANCEL_SEC;
					$saveRes = $this->repository->saveContract(['ID' => $params['ID']], $data);
					if ($saveRes === false) {
						throw new Exception("合同审批发起操作失败-3".M()->getDbError());
					}
					return $params['ID'];
				}
			} else {
					$this->saveContractFile($params, $contract);
				    
				     //根据模型进行数据验证
				    if (!$data = $contract->create($params, 1)) {
				    	throw new Exception("合同流程发起失败".$contract->getError());
				    } else {
				    	    $data = $this->adjustCreateData($data);
				    	    $data['SP_ANNEX_ADDR3'] = $_POST['SP_ANNEX_ADDR3'];	    
				        	$needSend = false; 
				        	switch (strval($params['type'])) {
				        		case '1': // 草稿
				        			$logMsg = '保存合同草稿';
				        			$data['audit_status_cd'] = TbCrmContractModel::SUBMITTING;
				        			break;
				        		case '2': // 发起
				        			$logMsg = '发起合同审批';
				        			$data['manager'] = $data['CONTRACTOR'];
				        			$data['audit_status_cd'] = TbCrmContractModel::LEADER_SUBMITTING;
				        			$data['seal_by'] = TbCrmContractModel::SEAL_SECOND;
				        			if ($data['CON_COMPANY_CD'] == TbCrmContractModel::LEGAL_COM_NO) {
				        				$data['seal_by'] = TbCrmContractModel::SEAL_FIRST;
				        			}
				        			$needSend = true;
				        			break;
				        		case '3': // 取消
				        			//$logMsg = '发起人取消合同审批';
				        			$data['audit_status_cd'] = TbCrmContractModel::CANCEL;
				        			break;
				        		default:
				        			# code...
				        			break;
				        	}
				        	
				        	if (strval($params['type']) === '2') {
				        		// 发起时必填合同联系方式（手机+固话）
				        		//手机号加密
				        		$data ['BAK_CON_PHONE'] = $data ['CON_PHONE'];
				        		if ($data ['CON_PHONE']) {
				        		    $con_phone_ret = CrypMobile::enCryp($data ['CON_PHONE']);
				        		    if ($con_phone_ret ['code'] == 200) $data ['CON_PHONE'] = $con_phone_ret ['data'];
				        		}
				        		//固话加密
				        		$data ['BAK_CON_TEL'] = $data ['CON_TEL'];
				        		if ($data ['CON_TEL']) {
				        		    $con_tel_ret = CrypMobile::enCryp($data ['CON_TEL']);
				        		    if ($con_tel_ret ['code'] == 200) $data ['CON_TEL'] = $con_tel_ret ['data'];
				        		}
				        		if (is_null($data['BAK_CON_PHONE']) || trim($data['BAK_CON_PHONE']) == '') {
				        			throw new Exception("联系电话不可为空或仅空格填入");
				        		}
				        		if (is_null($data['BAK_CON_TEL']) || trim($data['BAK_CON_TEL']) == '') {
				        			throw new Exception("联系固定电话不可为空或仅空格填入");
				        		}
				        	} else {
				        		$data['BAK_CON_TEL'] = $data['CON_TEL'];
				        		$data['BAK_CON_PHONE'] = $data['CON_PHONE'];
				        	}
				        	if (!empty($params['ID'])) { // 编辑
				        		$this->auth($params['ID']);
				        		$saveRes = $this->repository->saveContract(['ID' => $params['ID']], $data);
				        		$contract_id = $params['ID'];
				        	} else { // 新增
				        		
				        		$data['leader_by'] = $this->getAuditPeople(['name' => DataModel::userNamePinyin(), 'type' => '1']);
				        		$data['legal_by'] = $this->getAuditPeople(['name' => DataModel::userNamePinyin(), 'type' => '2']);
				        		$data['finance_by'] = $this->getAuditPeople(['name' => DataModel::userNamePinyin(), 'type' => '3']);
				        		$data['manager'] = $data['CONTRACTOR'];
				        		$data['file_by'] = TbCrmContractModel::LEGAL_FILE_PERSON;
				        		$data['CON_NO'] = $contract->createContractNo();
				        		if(!empty($params['trademark_no'])){
				        		    //校验下是否存在商标编号
                                    $trademark_id = M('trademark_base','tb_')->where(['trademark_no'=>$params['trademark_no']])->find();
                                    if(empty($trademark_id)){
                                        throw new Exception("请输入正确的供应商编号");
                                    }else{
                                        $data['trademark_no'] = $params['trademark_no'];
                                    }
                                }
				        		//写进数据库
				        		$contract_id = $contract->relation(true)->add($data);

				        	}
				        	if ($contract_id === false) {
				        		throw new Exception("合同审批发起操作失败".M()->getDbError());
				        	}

				        	if ($needSend) {
				        		$sendRes = (new NotifyService())->send($contract_id, '1');
				        		if ($sendRes === false) {
				        			throw new Exception("发送审批通知消息失败");
				        		}
				        	}
				        	if ($logMsg) {
				        		$this->addLog($contract_id, $logMsg);
				        	}
				    }
				    return $contract_id;
			}
		}
	}

	public function sendEmailNew()
	{
		// 获取数据集
		$res = $this->getNewEmailData();
		// 改造数据集
		$res = $this->getPackageDataNew($res);
		// 发送邮件
		return $mailRes = $this->sendNew($res);
	}
	public function sendEmail()
	{
		// 获取数据集
		$res = $this->getEmailData();
		// 改造数据集
		$res = $this->getPackageData($res);
		// 发送邮件
		return $mailRes = $this->send($res);
	}

	public function sendNew($expTableData)
	{
									
		$expCellName = [
		    ['CON_NO','合同编号',20],
		    ['CON_NAME','合同简称',20],
		    ['REAL_SP_NAME','合作方名称',20],
		    ['CON_TYPE','合同类型',20],
		    ['CON_COMPANY_CD_VAL','我方公司',20],
		    ['SP_TEAM_CD','合作方所属团队',20],
		    ['CONTRACTOR','签约人',20],
		    ['START_TIME','起始时间',20],
		    ['END_TIME','结束时间',20],
		    ['IS_RENEWAL','自动续约',20],
		    ['CREATE_USER_ID','归档人',20],
		    ['CREATE_TIME','归档时间',20],
		    ['ID', '操作', 20]
		];
		$hasSend = true;
		foreach ($expTableData as $key => $value) {
			$title = '合同临期提醒';
			$message = $this->getMessage($expCellName,$value);
			$expTitle = '合同临期提醒' . date("YmdHis") . $key;
			$user = DataModel::userToEmail($key);
			$attachment = $this->exportListExcel($expTitle,$expCellName,$value);
			$sendRes = (new SMSEmail())->sendEmail($user, $title, $message, '',$attachment);
			if (false === $sendRes) {
				$hasSend = false;
				@SentinelModel::addAbnormal('合同临期提醒邮件推送失败', $email->getError(), [$user, $title, $message, $attachment], 'contract_email_remind'); 
			}
		}	
		return $hasSend;	
	}

	public function send($expTableData)
	{
									
		$expCellName = [
		    ['CON_NO','合同编号',20],
		    ['CON_NAME','合同简称',20],
		    ['REAL_SP_NAME','合作方名称',20],
		    ['CON_TYPE','合同类型',20],
		    ['CON_COMPANY_CD_VAL','我方公司',20],
		    ['SP_TEAM_CD','合作方所属团队',20],
		    ['CONTRACTOR','签约人',20],
		    ['START_TIME','起始时间',20],
		    ['END_TIME','结束时间',20],
		    ['IS_RENEWAL','自动续约',20],
		    ['CREATE_USER_ID','归档人',20],
		    ['CREATE_TIME','归档时间',20],
		    ['ID', '操作', 20]
		];
		$email = new SMSEmail();
		$user = C('CONTRACT_EXPIRE_REMIND')['recipient'];// 收件人
		$title = '合同临期提醒';
		$message = $this->getMessage($expCellName,$expTableData);
		$expTitle = '合同临期提醒' . date("YmdHis");
		$attachment = $this->exportListExcel($expTitle,$expCellName,$expTableData);
		$sendRes = $email->sendEmail($user, $title, $message, '',$attachment);
		if (false === $sendRes) {
			@SentinelModel::addAbnormal('合同临期提醒邮件推送失败', $email->getError(), [$user, $title, $message, $attachment], 'contract_email_remind'); 
		}
	}

	public function exportListExcel($expTitle,$expCellName,$expTableData){
	    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
	    $fileName = $xlsTitle;
	    $cellNum = count($expCellName);
	    $dataNum = count($expTableData);

	    vendor("PHPExcel.PHPExcel");
	    $objPHPExcel = new PHPExcel();
	    // 居中
	    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal('center');
	    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical('center');
	    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

	    for($i=0;$i<$cellNum;$i++){
	        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
	        $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setWidth($expCellName[$i][2]);
	    }
	    for($i=0;$i<$dataNum;$i++){
	        for($j=0;$j<$cellNum;$j++){
	            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2),  ' '.$expTableData[$i][$expCellName[$j][0]]);
	            $objPHPExcel->getActiveSheet(0)->getStyle($cellName[$j].($i+2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
	        }
	    }
	    ob_start();
	    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	    $objWriter->save('php://output');
	    $content = ob_get_contents();
	    ob_end_clean();
	    $expTitle = '/opt/b5c-disk/excel/'. $expTitle . '.xls';
	    file_put_contents($expTitle, $content);
	    return $expTitle;
	}

	public function getMessage($expCellName,$expTableData){

		$message = '';
        $tableHeaderTdStyle="table__hearder-td";
        $tableBodyTdStyle="table__body-td";
        $style = $this->getEmailStyle();
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        print_r($cellNum."  ");
        print_r($dataNum);
        $message = '<p>以下合同30天内将会过期，请及时续签。</p><table width="100%" border="0"  cellspacing="1" cellpadding="6">';
        $message.= '<tr>';
        for($i=0;$i<$cellNum;$i++){
            $message.= '<td class='.$tableHeaderTdStyle.'>'.$expCellName[$i][1].'</td>';
        }
        $message.= '</tr>';
        for($i=0;$i<$dataNum;$i++){
            $message.= '<tr>';
            for($j=0;$j<$cellNum;$j++){
            	if ($expCellName[$j][0] === 'ID') {
            		$message.= "<td class='".$tableBodyTdStyle."'><a href='{$expTableData[$i][$expCellName[$j][0]]}'>查看</a></td>";
            	} else {
                	$message.= '<td class='.$tableBodyTdStyle.'>'.$expTableData[$i][$expCellName[$j][0]].'</td>';
            	}
            }
            $message.= '</tr>';
        }
        $message .= '</table>';
        return $message.$style;
	}

	public function getEmailData()
	{
		$conditions = [];
		$contractModel = M('contract', 'tb_crm_');
		// 处理时间
		$conditions['END_TIME'] = ['between', [date('Y-m-d H:i:s', strtotime(date('Y-m-d'))), date('Y-m-d H:i:s', strtotime('+30 day', strtotime(date('Y-m-d'))))]];
		$conditions['IS_RENEWAL'] = '1'; 
		$res = $contractModel
		->alias('cc')
		->field('cc.ID, cc.END_TIME, cc.IS_RENEWAL, cc.START_TIME, cc.CON_TYPE, cc.CREATE_USER_ID, cc.CREATE_TIME, cc.CONTRACTOR, cc.CON_COMPANY_CD,  css.SP_NAME AS REAL_SP_NAME, cc.CON_NO, cc.CON_NAME')
		->join('left join tb_crm_sp_supplier AS css ON cc.SP_CHARTER_NO = css.SP_CHARTER_NO')
		->where($conditions)
		->select();
		return $res;
	}

	public function getNewEmailData()
	{
		$conditions = [];
		$contractModel = M('contract', 'tb_crm_');
		// 处理时间
		$conditions['END_TIME'] = ['between', [date('Y-m-d H:i:s', strtotime(date('Y-m-d'))), date('Y-m-d H:i:s', strtotime('+60 day', strtotime(date('Y-m-d'))))]];
		$conditions['IS_RENEWAL'] = '1';
		$conditions['manager'] = ['EXP', 'IS NOT NULL']; 
		$conditions['audit_status_cd'] = TbCrmContractModel::FINISH;
		$res = $contractModel
		->alias('cc')
		->field('cc.ID, cc.END_TIME, cc.IS_RENEWAL, cc.START_TIME, cc.CON_TYPE, cc.CREATE_USER_ID, cc.CREATE_TIME, cc.CONTRACTOR, cc.CON_COMPANY_CD,  css.SP_NAME AS REAL_SP_NAME, cc.CON_NO, cc.CON_NAME, cc.manager')
		->join('left join tb_crm_sp_supplier AS css ON cc.SP_CHARTER_NO = css.SP_CHARTER_NO')
		->where($conditions)
		->select();
		return $res;
	}

	public function getPackageData($res = [])
	{
		$allUserInfo = BaseModel::getAdmin();
		$conType = BaseModel::conType();
		$isRenewal = BaseModel::isAutoRenew();
		$company = BaseModel::conCompanyCd();
		$realData = [];
		foreach ($res as $key => $value) {
			$realData[$key]['START_TIME'] = cutting_time($value['START_TIME']);
			$realData[$key]['END_TIME'] = cutting_time($value['END_TIME']);
			$realData[$key]['CON_TYPE'] = $conType[$value['CON_TYPE']];
			$realData[$key]['IS_RENEWAL'] = $isRenewal[$value['IS_RENEWAL']];
			$realData[$key]['CREATE_USER_ID'] = $allUserInfo[$value['CREATE_USER_ID']];
			$realData[$key]['CREATE_TIME'] = $value['CREATE_TIME'];
			$realData[$key]['CONTRACTOR'] = $value['CONTRACTOR'];
			$realData[$key]['CON_COMPANY_CD_VAL'] = $company[$value['CON_COMPANY_CD']];
			$realData[$key]['CON_NO'] = $value['CON_NO'];
			$realData[$key]['CON_NAME'] = $value['CON_NAME'];
			$realData[$key]['REAL_SP_NAME'] = $value['REAL_SP_NAME'];
			$realData[$key]['ID'] = ERP_URL . 'index.php?m=index&a=index&source=email&actionType=contract&id=' . $value['ID'];
			$realData[$key]['SP_TEAM_CD'] = '-'; // 还是按合同列表的逻辑，该字段为写死字段，按产品FS要求先不改动，保持跟列表原样
		}
		return $realData;
	}

	public function getPackageDataNew($res = [])
	{
		$allUserInfo = BaseModel::getAdmin();
		$conType = BaseModel::conType();
		$isRenewal = BaseModel::isAutoRenew();
		$company = BaseModel::conCompanyCd();
		$realData = [];
		foreach ($res as $key => $value) {
			if (!$value['manager']) {
				continue;
			}
			$temp = [];
			$temp['START_TIME'] = cutting_time($value['START_TIME']);
			$temp['END_TIME'] = cutting_time($value['END_TIME']);
			$temp['CON_TYPE'] = $conType[$value['CON_TYPE']];
			$temp['IS_RENEWAL'] = $isRenewal[$value['IS_RENEWAL']];
			$temp['CREATE_USER_ID'] = $allUserInfo[$value['CREATE_USER_ID']];
			$temp['CREATE_TIME'] = $value['CREATE_TIME'];
			$temp['CONTRACTOR'] = $value['CONTRACTOR'];
			$temp['CON_COMPANY_CD_VAL'] = $company[$value['CON_COMPANY_CD']];
			$temp['CON_NO'] = $value['CON_NO'];
			$temp['CON_NAME'] = $value['CON_NAME'];
			$temp['REAL_SP_NAME'] = $value['REAL_SP_NAME'];
			$temp['ID'] = ERP_URL . 'index.php?m=index&a=index&source=email&actionType=contract&id=' . $value['ID'];
			$temp['SP_TEAM_CD'] = '-'; // 还是按合同列表的逻辑，该字段为写死字段，按产品FS要求先不改动，保持跟列表原样
			$realData[$value['manager']][] = $temp;
			unset($temp);
		}
		return $realData;
	}

	public function getEmailStyle()
	{
	    $style ='<style type="text/css"> 
	             table{ 
	             background-color: #cccccc !important;
	             margin-top: 13px;
	             }
	             td{ 
	             	background-color:#ffffff;
	                height:25px;
	                line-height:150%;
	             }
	            .table__hearder-td{
	            background:#e9faff !important;
	            color:#255e95;
	            font-family: 微软雅黑;
	            font-weight: bold;
	            font-size: 16px;
	            }
	             .table__hearder-td:nth-of-type(1){ width: 120px}
	             .table__hearder-td:nth-of-type(2){ width: 120px}
	             .table__hearder-td:nth-of-type(3){ width: 120px}
	             .table__hearder-td:nth-of-type(4){ max-width: 10%; min-width: 180px;}
	             .table__hearder-td:nth-of-type(5){ max-width: 10%; min-width: 180px;}
	             .table__hearder-td:nth-of-type(6){ width: 140px}
	             .table__hearder-td:nth-of-type(7){ width: 140px}
	             .table__hearder-td:nth-of-type(8){ width: 80px}
	             .table__hearder-td:nth-of-type(9){ width: 80px}
	             .table__hearder-td:nth-of-type(10){ width: 120px}

	            .table__body-td{
	            background:#f3f3f3 !important;
	            white-space: normal;
	            word-break: break-all;
	            }
	            </style>';
	    return $style;
	}
}