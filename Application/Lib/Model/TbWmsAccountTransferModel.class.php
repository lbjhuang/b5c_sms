<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/2/5
 * Time: 14:38
 */

class TbWmsAccountTransferModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_transfer';
    // 创建数据|更新数据
    private $payAccountBankId;
    private $recAccountBankId;
    private $amountMoney;
    private $reason;
    private $attachment;
    private $transferType;
    private $payCompanyName;
    private $recCompanyName;
    private $transferNo;
    private $auditStep;
    private $id;
    private $currentAuditStep;
    private $recReason;
    private $payReason;
    // 筛选条件
    private $state;
    private $payCompanyCode;
    private $payOpenBank;
    private $payAccountBank;
    private $recCompanyCode;
    private $recOpenBank;
    private $recAccountBank;
    private $currencyCode;
    private $minAmountMoney;
    private $maxAmountMoney;
    private $createStartTime;
    private $createEndTime;
    private $currentStep;
    private $identify;
    private $createUser;
    private $currentAuditor;
    // 付款
    private $payTransferTime;
    private $payActualMoney;
    private $payVoucherFile;
    // 收款
    private $recTransferTime;
    private $recActualMoney;
    private $recVoucherFile;
    private $accountTransferType;
    private $collectionCompanyClassCd;
    private $paymentCompanyClassCd;
    private $initState = 'N001940100';
    private $failState = 'N001940500';

    public $params;

    const TRANSFER_WAIT_PAY = 'N001940200'; // 待转账
    const TRANSFER_WAIT_REC = 'N001940300'; // 待收款
    const TRANSFER_SUCCESS = 'N001940400'; // 收款完成
    const TRANSFER_DELETE  = 'N001940502'; // 已删除
    const TRANSFER_WAIT_ACCOUNTING  = 'N001940501'; // 待会计审核

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user', 'getName', Model::MODEL_INSERT, 'callback'],
        ['audit_time', 'getTime', Model::MODEL_UPDATE, 'callback'],
        ['audit_user', 'getName', Model::MODEL_UPDATE, 'callback'],
        ['state', 'initState', Model::MODEL_INSERT, 'callback'],
    ];

    public function __construct()
    {
        parent::__construct();
        $userId   = $_SESSION['userId'];
        $userName = $_SESSION['m_loginname'];
        if (!$userId or !$userName) {
            throw new Exception(L('Access Denied'));
        }
        $model = new Model();
        $conditions ['M_ID']   = ['eq', $userId];
        $conditions ['M_NAME'] = ['eq', $userName];

        $this->identify = $model->table('bbm_admin')->where($conditions)->find();
        $this->getAllUserName();
    }

    public function initState()
    {
        return $this->initState;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
        $name = $this->humpToLine($name);
        if (!isset($this->params[$name]) and ($value !== '' and !is_null($value) and $value !== false))
            $this->params[$name] = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * 获取资金划转类型
     * @param string $payCompanyCode 付款公司CODE
     * @param string $recCompanyCode 收款公司CODE
     * @return string $transferType  资金划转类型
     */
    public function getTransferType($payCompanyCode, $payCurrencyCode, $recCompanyCode, $recCurrencyCode)
    {
        // 基础数据
        $model = new Model();
        $ret  = $model->field('CD as cd, ETC3 as etc3')->table('tb_ms_cmn_cd')->where('CD like "N00124%"')->select();
        $type = $model->table('tb_ms_cmn_cd')->where('CD like "N00199%"')->getField('CD_VAL as cdVal, CD as cd, ETC as etc');
        $ret  = array_column($ret, 'etc3', 'cd');
        // 换汇、转账定型
        $transferType = 0;
        if ($payCurrencyCode == $recCurrencyCode) {
            $transferType = '转账';
        } else {
            $transferType = '换汇';
        }
        // 地区定型、大陆、香港、海外
        if (($ret [$payCompanyCode] == $ret [$recCompanyCode]) and ($ret [$recCompanyCode] == 'CN')) {
            $placeType = '中国大陆';
        } elseif (($ret [$payCompanyCode] == $ret [$recCompanyCode]) and ($ret [$recCompanyCode] == 'HK')) {
            $placeType = '香港';
        } elseif (($ret [$payCompanyCode] == $ret [$recCompanyCode]) and ($ret [$recCompanyCode] != 'HK') and ($ret [$recCompanyCode] != 'CN')) {
            $placeType = '海外';
        } else {
            $placeType = '跨地区';
        }

        return $type [$placeType.$transferType];
    }

    /**
     * 用户验证，验证当前用户是否有权限进行审核
     * @param int $currentStep 当前审核第几步
     * @param int $auditStep   共需要审核几次
     * @throws Exception Access Denied
     * @return bool
     */
    public function checkAuditAuth($currentStep, $auditStep)
    {
        if ($this->identify)
        {
            // 获取第几审核人
            $auditPerson = CommonDataModel::auditPerson();
            $flag = false;
            $index = 1;
            foreach ($auditPerson as $key => $value) {
                if ($value ['SORT_NO'] == $currentStep and $this->identify ['M_NAME'] == explode('@', $value ['ETC'])[0]) {
                    $flag = true;
                }
                if ($index == $auditStep)
                    break;
                $index ++;
            }
            if ($flag)
                return true;
            else
                return false;
        }
        return false;
    }

    /**
     * 审核步骤
     * @param int $auditStep 需要几步
     * @return array 流程
     */
    public function auditStep($auditStep)
    {
        $model = new Model();
        $auditPerson = $model->field('CD as cd , ETC2 as cdVal, SORT_NO as sortNo')->table('tb_ms_cmn_cd')->where('CD LIKE "N002000%"')->order('SORT_NO asc')->select();
        $flow = $model->field('CD as cd , ETC2 as cdVal, SORT_NO as sortNo')->table('tb_ms_cmn_cd')->where('CD LIKE "N001940%"')->order('SORT_NO asc')->select();
        $result = [];
        foreach ($auditPerson as $key => $value) {
            if (count($result) < $auditStep)
            $result [$value ['sortNo']] = $value;
        }
        foreach ($flow as $key => $value) {
            $result [$value ['sortNo']] = $value;
        }
        foreach ($result as $key => &$value) {
            //移除审核失败code
            if ($value ['cd'] == $this->failState) {
                unset($result [$key]);
                continue;
            }
            //移除等待收款
            if ($value ['cd'] == self::TRANSFER_WAIT_REC) {
                unset($result [$key]);
                continue;
            }
            //移除已删除
            if ($value ['cd'] == self::TRANSFER_DELETE) {
                unset($result [$key]);
                continue;
            }
            $value ['state'] = 1;
            $value ['auditTime'] = '';
            if ($value ['etc'])
                //$value ['etc'] = explode('@', $value ['etc'])[0];
            unset($value);
        }
        ksort($result);
        $index = 0;
        foreach ($result as $key => $value) {
            $data [$index] = $value;
            $index ++;
        }
        return $data;
    }

    /**
     * 获得人物名
     *
     */
    public function getAuditName($id)
    {
        $model = new Model();
        return $model->table('bbm_admin')->where('M_ID = '.$id)->find() ['M_NAME'];
    }

    public $allUserName;
    /**
     * 获取所有人物名
     *
     */
    public function getAllUserName()
    {
        $model = new Model();
        $res = $model->table('bbm_admin')->getField('M_ID, M_NAME');
        $this->allUserName = $res;
        return $res;
    }

    /**
     * 根据人物名获取id
     */
    public function getUserIdByName($name)
    {
        $model = new Model();
        return $model->table('bbm_admin')->where('M_NAME = "'. $name . '"')->find() ['M_ID'];
    }


    /**
     * 获得币种
     *
     */
    public function getCurrencyVal($code)
    {
        $model = new Model();
        $ret = $model->field('CD_VAL as cdVal')->table('tb_ms_cmn_cd')->where('CD = "' . $code . '"')->find() ['cdVal'];
        return $ret;
    }

    /**
     * 获得当前是是否是在审核人列表中，并返回审核人审核节点编号
     */
    public function getAuditorStep()
    {
        $model = new Model();
        $ret = $model->field('ETC as etc, SORT_NO as sortNo')->table('tb_ms_cmn_cd')->where('CD like "N002000%"')->select();
        $currentUser = $_SESSION ['m_loginname'];
        $response = null;
        foreach ($ret as $key => $value) {
            if (explode('@', $value ['etc'])[0] == $currentUser) {
                $tmp ['canAuditStep'] = $value ['sortNo'];
                $response [] = $tmp;
                $tmp = null;
            }
        }
        return $response;
    }
}