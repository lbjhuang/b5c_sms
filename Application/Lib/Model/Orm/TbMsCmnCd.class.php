<?php

/**
 * PHP version 5.6
 * User: tianrui
 * Date: 19/03/07
 * Time: 19:52
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

class TbMsCmnCd extends ORM
{
    protected $table = 'tb_ms_cmn_cd';

    /*protected $casts = [
        'cd_type_key' => 'int',
        'status' => 'int',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];*/

    protected $fillable = [
        'cd_type',
        'cd_type_key',
        'cd_type_name',
        'status',
    ];
}
