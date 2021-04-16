<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 18/9/28
 * Time: 13:53
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbMsOrdPackage
 * 
 * @property string $ORD_ID
 * @property \Carbon\Carbon $SYS_REG_DTTM
 * @property \Carbon\Carbon $SYS_CHG_DTTM
 * @property string $GOODS_LIST
 * @property string $TRACKING_NUMBER
 * @property string $EXPE_COMPANY
 * @property \Carbon\Carbon $SUBSCRIBE_TIME
 * @property \Carbon\Carbon $updated_time
 * @property string $EXPE_CODE
 * @property int $PUSH_FLAG
 * @property int $PUSH_COUNT
 * @property string $LOGISTIC_STATUS
 * @property string $template
 * @property \Carbon\Carbon $KD100_SUBSCRIBE_TIME
 * @property string $REFERENCE_NO
 * @property bool $template_type
 * @property string $plat_cd
 *
 * @package App\Models
 */
class TbMsOrdPackage extends ORM
{
    protected $table = 'tb_ms_ord_package';
    public $timestamps = false;

    protected $casts = [
        'PUSH_FLAG' => 'int',
        'PUSH_COUNT' => 'int',
        'template_type' => 'bool'
    ];

    protected $dates = [
        'SYS_REG_DTTM',
        'SYS_CHG_DTTM',
        'SUBSCRIBE_TIME',
        'updated_time',
        'KD100_SUBSCRIBE_TIME'
    ];

    protected $fillable = [
        'ORD_ID',
        'SYS_REG_DTTM',
        'SYS_CHG_DTTM',
        'GOODS_LIST',
        'TRACKING_NUMBER',
        'EXPE_COMPANY',
        'SUBSCRIBE_TIME',
        'updated_time',
        'EXPE_CODE',
        'PUSH_FLAG',
        'PUSH_COUNT',
        'LOGISTIC_STATUS',
        'template',
        'KD100_SUBSCRIBE_TIME',
        'REFERENCE_NO',
        'template_type',
        'plat_cd'
    ];
}
