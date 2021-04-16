<?php

/**
 * User: yangsu
 * Date: 19/05/06
 * Time: 18:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbConDivisionWarehouse
 * 
 * @property int $id
 * @property string $warehouse_cd
 * @property string $purchase_warehousing_by
 * @property string $transfer_warehousing_by
 * @property string $b2b_order_outbound_by
 * @property string $transfer_out_library_by
 * @property string $prchasing_return_by
 * @property string $task_launch_by
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbConDivisionWarehouse extends ORM
{
	use \Illuminate\Database\Eloquent\SoftDeletes;
	protected $table = 'tb_con_division_warehouse';

	protected $fillable = [
		'warehouse_cd',
		'purchase_warehousing_by',
		'transfer_warehousing_by',
		'b2b_order_outbound_by',
		'transfer_out_library_by',
		'prchasing_return_by',
		'inventory_by',
		'inventory_finance_by',
		'inventory_operate_by',
		'task_launch_by',
		'created_by',
		'updated_by',
		'deleted_by'
	];
}
