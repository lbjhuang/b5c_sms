<?php
/**
 * User: mark.zhong
 * Date: 2020/06/10
 * Time: 14:56
 */


class SlaveModel extends Model
{

    protected $autoCheckFields = false;

    public function __construct()
    {
        parent::__construct('','','ERP_SLAVE_DB');
    }
}