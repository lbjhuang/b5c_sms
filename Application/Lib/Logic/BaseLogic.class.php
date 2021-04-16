<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/13
 * Time: 9:40
 */

class BaseLogic
{
    protected $code = 3000;
    protected $error = '';
    public $data = [];

    public function __construct()
    {
    }

    public function getError()
    {
        return $this->error;
    }

    public function getData()
    {
        return $this->data;
    }

    /**获取json返回结果；
     * @return array
     */
    public function getRet()
    {
        return ['data' => $this->data, 'msg' => L($this->error ?: 'Success'), 'code' => $this->code];
    }

}