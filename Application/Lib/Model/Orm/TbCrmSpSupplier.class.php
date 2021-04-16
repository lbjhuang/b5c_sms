<?php

/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbCrmSpSupplier
 *
 * @property int $ID
 * @property string $SP_NAME
 * @property string $SP_RES_NAME
 * @property string $SP_NAME_EN
 * @property string $SP_RES_NAME_EN
 * @property string $SP_CHARTER_NO
 * @property string $SP_ANNEX_NAME
 * @property string $SP_ANNEX_ADDR
 * @property string $COPANY_TYPE_CD
 * @property string $SP_ADDR1
 * @property string $SP_ADDR2
 * @property string $SP_ADDR3
 * @property string $SP_ADDR4
 * @property string $SP_YEAR_SCALE_CD
 * @property string $SP_CAT_CD
 * @property string $SP_TEAM_CD
 * @property string $SP_JS_TEAM_CD
 * @property string $SP_REMARK
 * @property bool $SP_STATUS
 * @property bool $DEL_FLAG
 * @property \Carbon\Carbon $CREATE_TIME
 * @property \Carbon\Carbon $UPDATE_TIME
 * @property int $CREATE_USER_ID
 * @property int $UPDATE_USER_ID
 * @property string $COMPANY_MARKET_INFO
 * @property string $COMPANY_ADDR_INFO
 * @property string $WEB_SITE
 * @property string $SALE_TEAM
 * @property string $DATA_MARKING
 * @property string $SP_ADDR5
 * @property string $SP_ADDR6
 * @property string $SP_ADDR7
 * @property string $SP_ADDR8
 * @property bool $AUDIT_STATE
 * @property bool $RISK_RATING
 * @property string $SP_ANNEX_NAME2
 * @property string $collection_account_name
 * @property string $cooperative_rating
 * @property int $receivables_day_avg
 * @property string $receivables_effciency_grade
 *
 * @package App\Models
 */
class TbCrmSpSupplier extends ORM
{
    protected $table = 'tb_crm_sp_supplier';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $casts = [
        'SP_STATUS' => 'bool',
        'DEL_FLAG' => 'bool',
        'CREATE_USER_ID' => 'int',
        'UPDATE_USER_ID' => 'int',
        'AUDIT_STATE' => 'bool',
        'RISK_RATING' => 'bool',
        'receivables_day_avg' => 'int'
    ];

    protected $dates = [
        'CREATE_TIME',
        'UPDATE_TIME'
    ];

    protected $fillable = [
        'SP_NAME',
        'SP_RES_NAME',
        'SP_NAME_EN',
        'SP_RES_NAME_EN',
        'SP_CHARTER_NO',
        'SP_ANNEX_NAME',
        'SP_ANNEX_ADDR',
        'COPANY_TYPE_CD',
        'SP_ADDR1',
        'SP_ADDR2',
        'SP_ADDR3',
        'SP_ADDR4',
        'SP_YEAR_SCALE_CD',
        'SP_CAT_CD',
        'SP_TEAM_CD',
        'SP_JS_TEAM_CD',
        'SP_REMARK',
        'SP_STATUS',
        'DEL_FLAG',
        'CREATE_TIME',
        'UPDATE_TIME',
        'CREATE_USER_ID',
        'UPDATE_USER_ID',
        'COMPANY_MARKET_INFO',
        'COMPANY_ADDR_INFO',
        'WEB_SITE',
        'SALE_TEAM',
        'DATA_MARKING',
        'SP_ADDR5',
        'SP_ADDR6',
        'SP_ADDR7',
        'SP_ADDR8',
        'AUDIT_STATE',
        'RISK_RATING',
        'SP_ANNEX_NAME2',
        'collection_account_name',
        'cooperative_rating',
        'receivables_day_avg',
        'receivables_effciency_grade'
    ];
}
