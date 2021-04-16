<?php
/**
 * User: yuanshixiao
 * Date: 2018/8/30
 * Time: 10:26
 */

class PmsOptionNameModel extends PmsBaseModel
{

    protected $trueTableName = 'option_name';

    public function test() {
        $res = $this->find();
        var_dump($res);
    }
}