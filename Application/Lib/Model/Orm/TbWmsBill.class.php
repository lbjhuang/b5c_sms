<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 18/9/27
 * Time: 19:52
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsBill
 * 
 * @property int $id
 * @property string $bill_id
 * @property string $link_bill_id
 * @property string $company_id
 * @property string $warehouse_id
 * @property string $bill_type
 * @property \Carbon\Carbon $bill_date
 * @property string $batch
 * @property int $user_id
 * @property int $bill_state
 * @property string $remarks
 * @property string $zd_user
 * @property \Carbon\Carbon $zd_date
 * @property string $qr_user
 * @property \Carbon\Carbon $qr_date
 * @property string $xg_user
 * @property \Carbon\Carbon $xg_date
 * @property int $is_show
 * @property string $channel
 * @property string $other_code
 * @property string $invoice
 * @property string $business
 * @property string $supplier
 * @property \Carbon\Carbon $due_date
 * @property \Carbon\Carbon $receivable_date
 * @property int $incidental
 * @property string $warehouse_rule
 * @property string $sale_no
 * @property float $purchase_logistics_cost
 * @property float $total_cost
 * @property string $SALE_TEAM
 * @property string $SP_TEAM_CD
 * @property string $CON_COMPANY_CD
 * @property string $procurement_number
 * @property string $batch_ids
 * @property string $relation_type
 * @property bool $type
 * @property string $vir_type
 * @property string $intro_team
 * @property string $intro_team_type
 *
 * @package App\Models
 */
class TbWmsBill extends ORM
{
	protected $table = 'tb_wms_bill';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'bill_state' => 'int',
		'is_show' => 'int',
		'incidental' => 'int',
		'purchase_logistics_cost' => 'float',
		'total_cost' => 'float',
		'type' => 'bool'
	];

	protected $dates = [
		'bill_date',
		'zd_date',
		'qr_date',
		'xg_date',
		'due_date',
		'receivable_date'
	];

	protected $fillable = [
		'bill_id',
		'link_bill_id',
		'company_id',
		'warehouse_id',
		'bill_type',
		'bill_date',
		'batch',
		'user_id',
		'bill_state',
		'remarks',
		'zd_user',
		'zd_date',
		'qr_user',
		'qr_date',
		'xg_user',
		'xg_date',
		'is_show',
		'channel',
		'other_code',
		'invoice',
		'business',
		'supplier',
		'due_date',
		'receivable_date',
		'incidental',
		'warehouse_rule',
		'sale_no',
		'purchase_logistics_cost',
		'total_cost',
		'SALE_TEAM',
		'SP_TEAM_CD',
		'CON_COMPANY_CD',
		'procurement_number',
		'batch_ids',
		'relation_type',
		'type',
		'vir_type',
		'intro_team',
		'intro_team_type'
	];
}
