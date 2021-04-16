<?php

class ContractRepository extends Repository
{  

	public function getDeptInfoByDeptName($where, $field)
	{
		return M('hr_empl_dept hed', 'tb_')
			->join('left join tb_hr_card hc on hc.EMPL_id = hed.ID2')
			->join('left join tb_hr_dept hd on hd.ID = hed.ID1')
			->field($field)
			->where($where)
			->select();	
	}
	public function getContractInfo($where, $field)
	{
		return M('crm_contract cc', 'tb_')
		->join('left join tb_crm_sp_supplier css ON cc.supplier_id = css.ID')
		->join('left join tb_ms_forensic_audit mfa ON mfa.SP_CHARTER_NO = css.SP_CHARTER_NO')
		->field($field)
		->where($where)
		->select();
	}
	public function getFinanceAudit()
	{
		$where['r.ROLE_NAME'] = ['eq', '合同审批财务人员'];
		return M('role r', 'bbm_')
			->join('left join bbm_admin_role bar ON bar.ROLE_ID = r.ROLE_ID')
			->join('left join bbm_admin ba ON ba.M_ID = bar.M_ID')
			->field('ba.M_NAME')
			->where($where)
			->getField('ba.M_NAME as name');	
	}

	public function getLegalAudit()
	{
		$where['r.ROLE_NAME'] = ['eq', '法务同事'];
		$where['ba.M_NAME'] = ['exp', 'IS NOT NULL'];
		return M('role r', 'bbm_')
			->join('left join bbm_admin_role bar ON bar.ROLE_ID = r.ROLE_ID')
			->join('left join bbm_admin ba ON ba.M_ID = bar.M_ID')
			->field('ba.M_NAME as name')
			->where($where)
			->select();	
	}

	public function getDeptInfoByDeptId($deptId, $field = 'LEGAL_PERSON')
	{
		// 根据部门ID获取部门法务
		$res = false;
		$res = M('hr_dept', 'tb_')->where(['ID' => $deptId])->getField($field);
		if ($res) {
			if (strstr($res, ' ') && $field == 'LEGAL_PERSON') {
				$re = explode(' ',$res);
				$res = $re[0] . '.' . $re[1];
			}
		}
		return $res;
	}

	public function getLeaderByDeptId($deptId)
	{
		// tb_hr_empl_dept ID1 = $deptId TYPE = 0
		$where['hed.ID1'] = $deptId;
		$where['hc.STATUS'] = '在职';
		$where['hed.TYPE'] = '0'; // type 为0才是领导
		return M('hr_empl_dept hed', 'tb_')
			->join('left join tb_hr_card hc on hc.EMPL_id = hed.ID2')
			->join('left join tb_hr_dept hd on hd.ID = hed.ID1')
			->field('hed.SORT, hed.ID2, hc.ERP_ACT, hd.DEPT_LEVEL, hd.PAR_DEPT_ID')
			->where($where)
			->order('hed.SORT desc, hed.ID2 desc')
			->select();
	}
	public function getDeptIdByName($name)
	{
		$where['hc.ERP_ACT'] = ['eq', $name];
		$where['hc.STATUS'] = '在职';
		return M('hr_card hc', 'tb_')
			->join('left join tb_hr_empl_dept hed on hed.ID2 = hc.EMPL_id')
			->join('left join tb_hr_dept hd on hd.ID = hed.ID1')
			->where($where)
			->getField('hd.ID');
	}

	public function getAuditPeople($where, $field)
	{
		// 法务负责人
		// 名称->empl_id admin表
		// tb_hr_empl_dept.ID2 获取部门ID1,
		// tb_hr_dept.ID获取上级部门PAR_DEPT_ID
		// tb_hr_dept.ID 获取 LEGAL_PERSON
		
		// 领导？？？


		/*M('tb_hr_card hc')
		 ERP_ACT
		->join('tb_hr_empl_dept a on a.ID2 = hc.EMPL_id')
		->join('tb_hr_dept b on b.ID=a.ID1')
		->where()*/
	}
	public function getAuditInfo($where, $field)
	{
		$where['deleted_at'] = ['EXP', 'IS NULL'];
		$where['deleted_by'] = ['EXP', 'IS NULL'];
		return M('crm_contract_audit', 'tb_')
		->field($field)
		->where($where)
		->select();
	}
	public function saveContract($where, $save)
	{
		$contractModel = M('crm_contract', 'tb_');
		if ($where) {
			$res = $contractModel->where($where)->save($save);
		} else {
			$res = $contractModel->add($save);
		}
		return $res;
	}

	public function saveContractAudit($where, $save)
	{
		$auditModel = M('crm_contract_audit', 'tb_');
		if ($where) {
			$save['updated_by'] = DataModel::userNamePinyin();
			$res = $auditModel->where($where)->save($save);
		} else {
			$save['created_by'] = DataModel::userNamePinyin();
			$res = $auditModel->add($save);
		}
		return $res;
	}

	public function getInfo($where, $field)
	{
		return M('crm_contract', 'tb_')
		->field($field)
		->where($where)
		->find();
	}
}
