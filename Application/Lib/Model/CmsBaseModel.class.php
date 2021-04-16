<?php
/**
 * CMS 数据库连接类
 * Class PmsBaseModel
 */


class CmsBaseModel extends Model
{

    protected $autoCheckFields = false;

    public function __construct()
    {
        parent::__construct('','','CMS_DB');
    }

}