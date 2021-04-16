<?php

/**
 * User: yangsu
 * Date: Tue, 14 Aug 2018 04:52:45 +0000.
 */
@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbMsLogisticsCompany
 *
 * @property int            $id
 * @property string         $logistios_company_cd
 * @property string         $forwarding_company_cd
 * @property string         $self_warehouse_cd_arr
 * @property string         $butt_item_cd
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbMsLogisticsCompany extends ORM
{
    protected $table = 'tb_ms_logistics_company';

    protected $fillable = [
        'logistios_company_cd',
        'forwarding_company_cd',
        'self_warehouse_cd_arr',
        'butt_item_cd_arr',
        'updated_user'
    ];

    public function tbMsLogisticsAccountInfo()
    {
        return $this->hasMany(TbMsLogisticsAccountInfo::class, 'logistics_company_id');
    }

}
