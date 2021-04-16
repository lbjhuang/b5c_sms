<?php

/**
 * 供应商模型
 *
 */
class TbCrmSpSupplierModel extends BaseModel
{
    const NOT_AUDIT = 3; // 无需审核
    const IS_AUDIT_YES = 2; // 已审核
    const IS_AUDIT_NO = 1; // 未审核
    const DATA_MARKING = 0; // 供应商类型，为1是客户管理类型

    protected $trueTableName = 'tb_crm_sp_supplier';

    protected $_validate = [
        ['SP_NAME', 'require', '请输入供应商名称'],
        ['SP_CHARTER_NO', 'require', '请输入营业执照号'],
        ['COPANY_TYPE_CD', 'require', '请选择企业类型']
    ];

    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback'],
        ['DEL_FLAG', '1', Model::MODEL_INSERT],
        ['SP_STATUS', '1', Model::MODEL_INSERT],
    ];

    protected $_map = [
        'ID' => 'ID',
        //'SP_NAME' => 'SP_NAME',
    ];

    protected $_link = [
        'TbCrmContract' => [
            'mapping_type' => HAS_MANY,
            'class_name' => 'TbCrmContract',
            'foreign_key' => 'SP_CHARTER_NO',
            'relation_foreign_key' => 'SP_CHARTER_NO',
            'mapping_name' => 'contracts',
            'mapping_key' => 'SP_CHARTER_NO',
            'condition' => 'tb_crm_contract.CRM_CON_TYPE = 0 and tb_crm_contract.SP_CHARTER_NO is not null and SP_CHARTER_NO != ""',
        ],
        'TbMsForensicAudit' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbMsForensicAudit',
            'foreign_key' => 'SP_CHARTER_NO',
            'relation_foreign_key' => 'SP_CHARTER_NO',
            'mapping_name' => 'audit',
            'mapping_key' => 'SP_CHARTER_NO',
            'condition' => 'CRM_CON_TYPE = 0',
        ]
    ];

    public $attributes = [
        'ID' => 'ID',
        'SP_NAME' => '供应商名称',
        'SP_ADDR1' => '国别',
        'SP_TEAM_CD' => '采购团队',
        'CREATE_TIME' => '创建时间',
        'AUDIT_STATE' => '审核状态',
        'RISK_RATING' => '风险评级',
        'audit_time' => '审核时间',
        'CREATE_USER_ID' => '创建人',
        'contract' => '合同数',
        'COUNT_ORDERS' => '总订单数',
        'ING_ORDER' => '进行中订单',
        //'TOTAL_MONEY' => '总金额',
        //'ING_MONEY' => '进行中金额',
        //'SP_JS_TEAM_CD' => '介绍团队',
        //'CREATE_USER_ID' => '创建人',

    ];

    public function searchModel($params)
    {
        if ($params ['ID']) {
            $conditions ['tb_crm_sp_supplier.ID'] = $params['ID'];
        }
        if ($params ['SP_JS_TEAM_CD']) {
            $conditions ['tb_crm_sp_supplier.SP_JS_TEAM_CD'] = $params['SP_JS_TEAM_CD'];
        }
        if ($params ['SP_NAME']) {
            $where ['tb_crm_sp_supplier.SP_NAME'] = ['like', '%' . $params ['SP_NAME'] . '%'];
            $where ['tb_crm_sp_supplier.SP_NAME_EN'] = ['like', '%' . $params ['SP_NAME'] . '%'];
            $where['_logic'] = 'or';
            $conditions['_complex'] = $where;
        }
        if ($params ['CON_NO']) {
            $conditions ['tb_crm_contract.CON_NO'] = $params['CON_NO'];
        }
        if ($params ['SP_CHARTER_NO']) {
            $conditions ['tb_crm_sp_supplier.SP_CHARTER_NO'] = $params['SP_CHARTER_NO'];
        }
        if ($params ['SP_TEAM_CD']) {
            $conditions ['tb_crm_sp_supplier.SP_TEAM_CD'] = ['like', '%' . $params ['SP_TEAM_CD'] . '%'];
        }
        if ($params ['SP_ADDR1']) {
            $conditions ['tb_crm_sp_supplier.SP_ADDR1'] = $params['SP_ADDR1'];
        }
        if ($params ['AUDIT_STATE']) {
            $conditions ['AUDIT_STATE'] = $params['AUDIT_STATE'];
        }
        if ($params ['RISK_RATING']) {
            $conditions ['RISK_RATING'] = $params['RISK_RATING'];
        }
        if ($params ['CREATE_USER_ID']) {
            $model = M('_admin', 'bbm_');
            $where ['M_NAME'] = ['like', '%' . $params ['CREATE_USER_ID'] . '%'];
            $ret = $model->field('M_ID')->where($where)->select();
            $ret = array_column($ret, 'M_ID');
            $conditions ['CREATE_USER_ID'] = ['in', $ret];
        }
        if ($params ['cooperative_rating']) {
            $conditions ['cooperative_rating'] = $params ['cooperative_rating'];
        }

        // 企业类型
        if ($params['company_type']){
            $company_type = explode(',',$params['company_type']);
            $temp_data = array();
            foreach ($company_type as  $value){
                array_push($temp_data,array('like','%'.$value.'%'));
            }
            array_push($temp_data,'or');
            $conditions['COPANY_TYPE_CD'] = $temp_data;
        }
        $conditions ['DATA_MARKING'] = self::DATA_MARKING;

        return $conditions;
    }

    /**
     * 更新供应商信息
     *
     */
    public function updateSupplierOrCustomer()
    {
        if ($_FILES) {
            $fd = new FileUploadModel();
            if ($_FILES['SP_ANNEX_ADDR'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR'])) {
                $_POST ['SP_ANNEX_NAME'] = $fd->info [0]['savename'];
                $_POST ['SP_ANNEX_ADDR'] = $fd->filePath;
            }
            if ($_FILES['SP_ANNEX_ADDR2'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR2'])) {
                $_POST ['SP_ANNEX_NAME2'] = $fd->info [0]['savename'];
            }
        } else {
            unset($_POST['SP_ANNEX_NAME']);
            unset($_POST['SP_ANNEX_NAME2']);
        }
        if ($_POST['SP_TEAM_CD']) {
            $temp = null;
            foreach ($_POST['SP_TEAM_CD'] as $k => $v) {
                $temp .= $v . ',';
            }
            $_POST['SP_TEAM_CD'] = rtrim($temp, ',');
        }

        if ($_POST['secretary_company_telephone']){
            $_POST['secretary_company_telephone'] = implode(',',$_POST['secretary_company_telephone']);
        }else{
            $_POST['secretary_company_telephone'] = "";
        }
        if ($_POST['agency_company_telephone']){
            $_POST['agency_company_telephone'] = implode(',',$_POST['agency_company_telephone']);
        }else{
            $_POST['agency_company_telephone'] = "";
        }

        $ret = $this->relation(true)->find($_POST['ID']);
        $historySpCharterNo = $ret['SP_CHARTER_NO'];
        $newSpCharterNo = $_POST['SP_CHARTER_NO'];

        $histroySupplierName = $ret['SP_NAME']; // 原来供应商名称
        $newSupplierName = $_POST['SP_NAME']; // 新供应商名称
        $this->startTrans();
        $logC = '';
        try {
            if ($historySpCharterNo != $newSpCharterNo) {
                // 营业执照号发生变化，则更新审核信息与合同的营业执照号
                // 查询新营业执照号是否已存在
                if ($this->where('SP_CHARTER_NO = "' . $newSpCharterNo . '" and DATA_MARKING = ' . self::DATA_MARKING)->find()) {
                    throw new \Exception('该营业执照号已存在，请更换');
                }
                // 如果已审核，则更新审核信息的营业执照号
                if ($ret ['AUDIT_STATE'] == self::IS_AUDIT_YES) {
                    $model = D('TbMsForensicAudit');
                    if (!$model->updateAuditSpCharterNo($historySpCharterNo, self::DATA_MARKING, $newSpCharterNo)) {
                        throw new \Exception('更新审核信息失败:' . $model->getError());
                    }
                }
                // 如果有合同，且更换了营业执照号，则同时更新合同的营业执照号
                if ($ret ['contracts']) {
                    $m = D('TbCrmContract');
                    if (!$m->updateContractSpCharterNo($historySpCharterNo, self::DATA_MARKING, $newSpCharterNo)) {
                        throw new \Exception('更新合同信息失败:' . $m->getError());
                    }
                }
                // 更新日志信息
                $log = A('Home/Log');
                if ($isok = $log->updateLog($historySpCharterNo, self::DATA_MARKING, $newSpCharterNo) != true) {
                    // throw new \Exception('更新日志失败:' . $isok);
                    $logC .= '更新日志失败';
                }
                $logC .= ' 营业执照号成功';
            }
            // 判断供应商名称是否变更，如果变更则需要同时将采购单里的供应商名称和抵扣金里的供应商名称，
            if ($histroySupplierName != $newSupplierName && $newSupplierName) {
                $logC .= " 供应商名称更新成功，由【{$histroySupplierName}】 改为 【{$newSupplierName}】";
                $supplierInfo['supplier_name_cn'] = $newSupplierName;
                $supplierInfo['supplier_name_en'] = $_POST['SP_NAME_EN'];
                $supplierResult = $this->changeSupplierName($_POST['ID'], $supplierInfo);
                if (!$supplierResult) {
                    throw new \Exception('更新供应商名称失败:' . $m->getError());
                }
            }
            // 并记录日志
            // 更新供应商
            $this->create($_POST, 2);
            if ($this->where('ID =' . $_POST['ID'])->save() === false) {
                throw new \Exception('更新失败:' . $this->getError());
                $this->rollback();
            } else {
                $this->commit();
                if ($errMsg = $this->insertLog($newSpCharterNo, self::DATA_MARKING, '更新供应商成功' . $logC) !== true) {
                    throw new \Exception('更新失败(日志写入失败):' . $errMsg);
                }
            }
            //同步英文翻译配置 SP_NAME SP_RES_NAME_EN
            $language_data[] = ['element' => $_POST['SP_NAME'], 'type' => 'N000920200', 'translation_content' => $_POST['SP_NAME_EN']];
            if (!(new LanguageModel())->saveAllTrans($language_data)) {
                throw new \Exception('编辑供应商同步英文翻译配置失败');
            } else {
                //英文语言刷新
                $language = new LanguageModel();
                $language->flushEnCache();
            }
            return ['status' => 1, 'msg' => '更新成功', 'data' => $_POST];
        } catch (\Exception $e) {
            $this->rollback();
            return ['status' => 0, 'msg' => '更新失败', 'data' => $e->getMessage()];
        }
    }

    // 根据供应商名称，更新采购供应商和抵扣金供应商表里的供应商名称
    public function changeSupplierName($supplierId, $supplierInfo)
    {
        if (!$supplierId || !$supplierInfo) {
            return false;
        }
        $deductionModel = M('pur_deduction', 'tb_'); // 抵扣金
        $orderDetailModel = M('pur_order_detail', 'tb_'); // 抵扣金
        $mapWhere['supplier_id'] = $supplierId;
        $res = $deductionModel->where($mapWhere)->save($supplierInfo);
        if (false === $res) {
            $logArr['mapWhere'] = $mapWhere;
            $logArr['supplierInfo'] = $supplierInfo;
            $logArr['lastsql'] = M()->_sql(); 
            Logs(['$temp_data' => $logArr], __FUNCTION__.'----temp', 'tr');
            return false;
        }
        $map['supplier_new_id'] = $supplierId;
        $saveData['supplier_id'] = $supplierInfo['supplier_name_cn'];
        $saveData['supplier_id_en'] = $supplierInfo['supplier_name_en'];
        $re = $orderDetailModel->where($map)->save($saveData);
        if (false === $re) {
            $logArr['mapWhere'] = $map;
            $logArr['supplierInfo'] = $saveData; 
            $logArr['lastsql'] = M()->_sql(); 
            Logs(['$temp_data' => $logArr], __FUNCTION__.'----temp', 'tr');
            return false;
        }
        return true;

    }



    /**
     * @param $client_name
     *
     * @return mixed
     */
    public static function clientNameToEn($client_name)
    {
        $model = new Model();
        $where_sp_name['SP_NAME'] = $client_name;
        $client_name_en = $model->table('tb_crm_sp_supplier')
            ->where($where_sp_name)->getField('SP_NAME_EN');
        return $client_name_en;
    }
}
