<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/2/2
 * Time: 16:19
 */

class TbWmsAccountBankModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_bank';

    private $companyCode;
    private $accountClassCd;
    private $accountBank;
    private $accountBankNo;
    private $currencyCode;
    private $state;
    private $swiftCode;
    private $bsbNo;
    private $reason;
    private $id;
    public $params;

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user', 'getName', Model::MODEL_INSERT, 'callback'],
        ['update_time', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['update_user', 'getName', Model::MODEL_BOTH, 'callback'],
    ];

    public function __set($name, $value)
    {
        $this->$name = $value;
        $name = $this->humpToLine($name);
//        if (!isset($this->params[$name]) and ($value !== '' and !is_null($value) and $value !== false))
            $this->params[$name] = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * 获取账户id
     * @param string $companyCode 公司CODE
     * @param string $openBank    开户行
     * @param string $accountBank 银行账户
     * @param string $accountClassCd 公司账户归属
     * @return array   $id        开户信息
     */
    public function getAccountBankInfo($companyCode, $openBank, $accountBank, $accountClassCd = '')
    {
        if ($accountClassCd == 'N003510001') {
            $conditions ['company_code'] = ['eq', $companyCode];
        } else {
            $conditions ['supplier_id'] = ['eq', $companyCode];
        }
        $conditions ['open_bank']    = ['eq', $openBank];
        $conditions ['account_bank'] = ['eq', $accountBank];
        $ret = $this->where($conditions)->find();
        return $ret;
    }
}