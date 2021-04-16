<?php

/**
 * 合同模型
 * 
 */
class TbCrmContractModel extends BaseModel
{
    const SUBMITTING = 'N003660001'; // 待提交合同
    const LEADER_SUBMITTING = 'N003660002'; // 待领导审批 
    const LEGAL_SUBMITTING = 'N003660003'; // 待法务审批
    const TRANSFER_SUBMITTING = 'N003660004'; // 待转审人审批
    const FIN_SUBMITTING = 'N003660005'; // 待财务审批 
    const UPLOADING = 'N003660006'; // 待上传合同 
    const FINISH = 'N003660007'; // 审批已完成 
    const CANCEL = 'N003660008'; // 审批驳回 
    const CANCEL_SEC = 'N003660009'; // 审批取消 

    const UPLOADING_FIRST = 'N003760001'; // 待法务盖章
    const UPLOADING_SECOND = 'N003760002'; // 待业务上传定稿合同
    const UPLOADING_THIRD = 'N003760003'; // 待法务确认归档
    
    const LEGAL_COM = 'N001242600'; // 深圳载盈信息技术有限公司
    const LEGAL_COM_NO = '942'; 
    
    const SEAL_FIRST = 'Viona.Li'; // Viona.Li
    const SEAL_SECOND = 'Ruby.Wang'; // Johanna.Tang
    const LEGAL_FILE_PERSON = 'Ruby.Wang'; // Ruby.Wang
    protected $trueTableName = 'tb_crm_contract';

    protected $_validate = [
        //['CON_NO', 'require', '请输入合同编号'],
        //['CON_NAME', 'require', '请输入合同简称'],
    ];





    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback'],
        //['CON_STAT', '1', Model::MODEL_INSERT], // 新增合同草稿状态，所以该类型允许为空
        ['created_by', 'getLoginName', Model::MODEL_INSERT, 'callback'],
        ['updated_by', 'getLoginName', Model::MODEL_BOTH, 'callback'],
        
        /*['START_TIME', null, Model::MODEL_BOTH],
        ['END_TIME', null, Model::MODEL_BOTH],
        ['IS_RENEWAL', null, Model::MODEL_BOTH],
        ['CONTRACT_TYPE', null, Model::MODEL_BOTH],
        ['have_tax', null, Model::MODEL_BOTH],
        ['amount', null, Model::MODEL_BOTH],
        ['supplier_id', null, Model::MODEL_BOTH],*/
    ];

    protected $_link = [
        'TbCrmSpSupplier' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbCrmSpSupplier',
            'foreign_key' => 'SP_CHARTER_NO',
            'relation_foreign_key' => 'SP_CHARTER_NO',
            'mapping_name' => 'supplier',
            'mapping_key' => 'SP_CHARTER_NO'
        ],
        'TbMsForensicAudit' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbMsForensicAudit',
            'foreign_key' => 'SP_CHARTER_NO',
            'relation_foreign_key' => 'SP_CHARTER_NO',
            'mapping_name' => 'audit',
            'mapping_key' => 'SP_CHARTER_NO'
        ]
    ];


    public $attributes = [
        'CON_NO' => '合同号/OA流程号',
        'CONTRACTOR' => '签约人',
        'CON_NAME' => '合同简称',
        'CON_STAT' => '合同状态',
        'START_TIME' => '合同起始时间',
        'END_TIME' => '合同结束时间',
        'IS_RENEWAL' => '是否自动续约',
        'CREATE_TIME' => '提交时间',

    ];

    public $attributesExtends = [
        'CON_NO' => '合同编号',
        'CON_NAME' => '合同简称',
        'SP_NAME' => '合作方名称',
        'CON_TYPE' => '合同类型',
        'CON_COMPANY_CD' => '我方公司',
        'Team' => '合作方所属团队',
        'CONTRACTOR' => '签约人',
        'manager' => '合同负责人',
        'START_TIME' => '起始时间',
        'END_TIME' => '结束时间',
        'IS_RENEWAL' => '自动续约',
        'CREATE_USER_ID' => '归档人',
        'CREATE_TIME' => '归档时间',
        'audit_status_cd' => '审批流程节点'
    ];

    public static $auditType = [ // （1表示法务审核，2表示转审人审核，3表示财务审核） 
        '1' => '法务',
        '2' => '转审人',
        '3' => '财务',
        '4' => '领导'
    ];


    public function createContractNo()
    {
        // 先获取时间是今天的最新的合同单号的后四位数，加1
        // 判断下是否已达到9999最大限制
        $date = date("Y-m-d");
        $where['CREATE_TIME'] = ['gt', $date];
        $max_id = self::where($where)->count();
        if ($max_id == 9999) {
            throw new Exception(L('无法创建合同编号，今日数量已达上限~'));
        }
        $date = date("Ymd", strtotime($date));
        $wrate_id = $max_id + 1;
        $w_len = strlen($wrate_id);
        $b_id = '';
        if ($w_len < 4) {
            for ($i = 0; $i < 4 - $w_len; $i++) {
                $b_id .= '0';
            }
        }
        return $date . $b_id . $wrate_id;
    }

    public function searchModel($params)
    {
        if ($params ['CON_NO']) {
            $conditions ['CON_NO'] = $params['CON_NO'];
        }
        if ($params ['manager']) {
            $conditions ['tb_crm_contract.manager'] = $params ['manager'];
        }
        if ($params ['CON_NAME']) {
            $conditions ['tb_crm_contract.CON_NAME'] = ['like', '%' . $params ['CON_NAME'] . '%'];
        }
        if ($params ['SP_NAME']) {
            $conditions ['tb_crm_contract.SP_NAME'] = ['like', '%' . $params ['SP_NAME'] . '%'];
        }
        if ($params ['CON_TYPE']) {
            $conditions ['CON_TYPE'] = $params ['CON_TYPE'] - 1;
        }
        if ($params ['CON_COMPANY_CD']) {
            $conditions ['CON_COMPANY_CD'] = $params ['CON_COMPANY_CD'] - 1;
        }
        if ($params ['SP_TEAM_CD']) {
            $conditions ['SP_TEAM_CD'] = $params ['SP_TEAM_CD'];
        }
        if ($params ['CONTRACTOR']) {
            $conditions ['CONTRACTOR'] = ['like', '%' . $params ['CONTRACTOR'] . '%'];
        }
        if ($params ['TIME_TYPE']) {
            switch ($params ['TIME_TYPE']) {
                case 1:
                    $conditions ['tb_crm_contract.CREATE_TIME'] = [['gt', $params ['CONTRACT_START_TIME'] . ' 00:00:00'], ['lt', $params ['CONTRACT_END_TIME'] . ' 23:59:59']];
                    break;
                case 2:
                    $conditions ['START_TIME'] = [['gt', $params ['CONTRACT_START_TIME'] . ' 00:00:00'], ['lt', $params ['CONTRACT_END_TIME'] . ' 23:59:59']];
                    break;
                case 3:
                    $conditions ['END_TIME'] = [['gt', $params ['CONTRACT_START_TIME'] . ' 00:00:00'], ['lt', $params ['CONTRACT_END_TIME'] . ' 23:59:59']];
                    break;
            }
        }
        // 营业执照号
        if ($params ['SP_CHARTER_NO']) {
            $conditions ['tb_crm_sp_supplier.SP_CHARTER_NO'] = ['like', '%' . $params ['SP_CHARTER_NO'] . '%'];
        }

        //$conditions ['DATA_MARKING'] = 0;
        return $conditions;
    }

    /**
     * 更新合同营业执照号
     * @param $spNo 营业执照号
     * @param $type 0 供应商, 1 客户管理
     * @param $nSpNo 新营业执照号
     * @return bool
     */
    public function updateContractSpCharterNo($spNo, $type, $nSpNo)
    {
        $ret = $this->where('CRM_CON_TYPE = ' . $type . ' and SP_CHARTER_NO = "' . $spNo . '"')->select();
        if ($ret) {
            $ret = array_column($ret, 'ID');
            $where ['ID'] = ['in', $ret];
            //$data ['ID'] = $ret ['ID'];
            $data ['SP_CHARTER_NO'] = $nSpNo;
            if ($this->where($where)->save($data)) {
                return true;
            }
        }
        return false;
    }

    /**
     * LE01 formtable_main_91  常用
     * LE03 formtable_main_124 日本
     * LE08 formtable_main_150 韩国
     * @param string $type 合同类型
     * @param string $DJBH 合同编号
     * @return mixed
     */
    public function mapFunc($DJBH)
    {
        $oci = new MeBYModel();
        $mapTable = [
            'FORMTABLE_MAIN_91',
            'FORMTABLE_MAIN_124',
            'FORMTABLE_MAIN_150',
        ];
        foreach ($mapTable as $key => $table) {
            $model = new Model();
            $subQuery = $model->table('ECOLOGY.' . $table . ' t1')
                ->join('LEFT JOIN ECOLOGY.HRMRESOURCE t2 ON t1.SQR = t2.ID')
                ->where(['t1.DJBH' => $DJBH])
                ->buildSql();
            $ret = $oci->testQuery($subQuery);
            if ($ret) {
                $code = 2000;
                $msg  = L('success');
                $data = $ret [0];
                break;
            }
        }

        if ($code != 2000) {
            $code = 3000;
            $msg  = L('未查询到合同信息');
            $data = null;
        }

        $response = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];

        return $response;
    }

    /**
     * 检查是否存在相同的合同编号
     */
    public function checkExists($DJBH)
    {

    }

    /**
     * 中国以及常用合同
     * @param string $DJBH 合同编号
     * @return mixed
     */
    public static function formtableMain91($DJBH)
    {

    }

    /**
     * 日本契约合同
     * @param string $DJBH 合同编号
     * @return mixed
     */
    public static function formtableMain124($DJBH)
    {

    }

    /**
     * 韩国合同
     * @param string $DJBH 合同编号
     * @return mixed
     */
    public static function formtableMain150($DJBH)
    {

    }
}