<?php

/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbPurReturn
 *
 * @property int $id
 * @property string $return_no
 * @property string $status_cd
 * @property int outbound_status
 * @property string $warehouse_cd
 * @property string $purchase_team_cd
 * @property string $create_user
 * @property \Carbon\Carbon $create_time
 * @property int $supplier_id
 * @property string $our_company_cd
 * @property string $receiver
 * @property string $receiver_contact_number
 * @property int $receive_address_country
 * @property int $receive_address_province
 * @property int $receive_address_area
 * @property string $receive_address_detail
 * @property string $logistics_number
 * @property \Carbon\Carbon $estimate_arrive_date
 * @property string $estimate_logistics_cost_currency_cd
 * @property float $estimate_logistics_cost
 * @property string $estimate_other_cost_currency_cd
 * @property float $estimate_other_cost
 * @property string $out_of_stock_user
 * @property \Carbon\Carbon $out_of_stock_time
 * @property bool $has_difference
 * @property bool $need_bear_difference
 * @property array $tally_voucher_json
 * @property string $tally_remark
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbPurReturn extends ORM
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'tb_pur_return';

    protected $casts = [
        'supplier_id' => 'int',
        'outbound_status' => 'int',
        'receive_address_country' => 'int',
        'receive_address_province' => 'int',
        'receive_address_area' => 'int',
        'estimate_logistics_cost' => 'float',
        'estimate_other_cost' => 'float',
        'has_difference' => 'bool',
        'need_bear_difference' => 'bool',
        'tally_voucher_json' => 'json',
        'estimate_arrive_date' => 'date:Y-m-d'
    ];

    protected $dates = [
        'create_time',
        'out_of_stock_time'
    ];

    protected $fillable = [
        'return_no',
        'status_cd',
        'outbound_status',
        'warehouse_cd',
        'purchase_team_cd',
        'create_user',
        'create_time',
        'supplier_id',
        'our_company_cd',
        'receiver',
        'receiver_contact_number',
        'receive_address_country',
        'receive_address_province',
        'receive_address_area',
        'receive_address_detail',
        'logistics_number',
        'estimate_arrive_date',
        'estimate_logistics_cost_currency_cd',
        'estimate_logistics_cost',
        'estimate_other_cost_currency_cd',
        'estimate_other_cost',
        'out_of_stock_user',
        'out_of_stock_time',
        'has_difference',
        'need_bear_difference',
        'tally_voucher_json',
        'tally_remark',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function TbCrmSpSupplier()
    {
        return $this->hasOne(TbCrmSpSupplier::class, 'ID', 'supplier_id');
    }

    public function TbPurReturnGood()
    {
        return $this->hasMany(TbPurReturnGood::class, 'return_id');
    }

    public function TbPurReturnOrder()
    {
        return $this->hasOne(TbPurReturnOrder::class, 'return_id');
    }
}
