<?php

/**
 * User: yangsu
 * Date: Tue, 14 Aug 2018 07:46:18 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbMsLogisticsAccountInfo
 *
 * @property int            $id
 * @property int            $logistics_company_id
 * @property string         $account_name
 * @property string         $account_password
 * @property string         $token
 * @property string         $our_sigin_company_cd
 * @property \Carbon\Carbon $contract_validity_act_at
 * @property \Carbon\Carbon $contract_validity_end_at
 * @property int            $is_enable
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbMsLogisticsAccountInfo extends ORM
{
    protected $table = 'tb_ms_logistics_account_info';

    protected $casts = [
        'logistics_company_id' => 'int',
        'is_enable' => 'int'
    ];

    protected $dates = [
        'contract_validity_act_at',
        'contract_validity_end_at'
    ];

    protected $hidden = [

    ];

    protected $fillable = [
        'logistics_company_id',
        'account_name',
        'account_password',
        'login_username',
        'login_password',
        'token',
        'our_sigin_company_cd',
        'contract_validity_act_at',
        'contract_validity_end_at',
        'is_enable'
    ];

    public function tbMsLogisticsCompany()
    {
        return $this->belongsTo(TbMsLogisticsCompany::class, 'logistics_company_id');
    }
}
