<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 18/10/23
 * Time: 18:29
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsGroupBill
 *
 * @property int            $id
 * @property string         $bill_code
 * @property string         $sku_id
 * @property string         $warehouse_cd
 * @property string         $sale_team_cd
 * @property int            $all_num
 * @property int            $group_type
 * @property string         $audit_status
 * @property array          $sku_json
 * @property int            $is_audit
 * @property string         $audit_user
 * @property \Carbon\Carbon $audit_time
 * @property \Carbon\Carbon $created_at
 * @property string         $created_by
 * @property \Carbon\Carbon $updated_at
 * @property string         $updated_by
 *
 * @package App\Models
 */
class TbWmsGroupBill extends ORM
{
    protected $table = 'tb_wms_group_bill';

    protected $casts = [
        'all_num' => 'int',
        'group_type' => 'int',
        'is_audit' => 'int'
    ];

    protected $dates = [
        'audit_time'
    ];

    protected $fillable = [
        'bill_code',
        'sku_id',
        'warehouse_cd',
        'sale_team_cd',
        'all_num',
        'group_type',
        'audit_status',
        'sku_json',
        'is_audit',
        'audit_user',
        'audit_time',
        'created_by',
        'updated_by'
    ];

    public function TbWmsGroupStream()
    {
        return $this->hasMany(TbWmsGroupStream::class, 'group_bill_id');
    }

}
