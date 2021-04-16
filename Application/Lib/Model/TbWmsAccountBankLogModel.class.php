<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/2/3
 * Time: 16:14
 */

class TbWmsAccountBankLogModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_bank_log';
    private $orderNo;
    private $tag;
    public $params;

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user', 'getName', Model::MODEL_INSERT, 'callback']
    ];

    public function createLog($orderNo, $msg, $flag = null, $tag = 1)
    {
        $data ['order_no'] = $orderNo;
        $data ['msg']      = $msg;
        $data ['flag']     = $flag;
        $data ['tag']      = $tag;
        $ret = $this->create($data, 1);
        $this->add($ret);
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
}