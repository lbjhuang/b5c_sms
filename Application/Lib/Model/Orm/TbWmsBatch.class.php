<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 18/11/01
 * Time: 10:29
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsBatch
 * 
 * @property int $id
 * @property string $SKU_ID
 * @property string $GUDS_ID
 * @property string $channel
 * @property string $CHANNEL_SKU_ID
 * @property int $bill_id
 * @property int $stream_id
 * @property string $stream_id_str
 * @property string $batch_code
 * @property string $purchase_order_no
 * @property string $purchase_team_code
 * @property string $sale_team_code
 * @property int $all_total_inventory
 * @property int $total_inventory
 * @property int $occupied
 * @property int $locking
 * @property int $available_for_sale_num
 * @property \Carbon\Carbon $deadline_date_for_use
 * @property bool $is_it_sensitive
 * @property \Carbon\Carbon $create_time
 * @property \Carbon\Carbon $original_storage_time
 * @property \Carbon\Carbon $update_time
 * @property int $create_user_id
 * @property int $update_user_id
 * @property bool $state
 * @property int $batch_id
 * @property int $all_available_for_sale_num
 * @property int $all_oversole_number
 * @property string $lock_code
 * @property int $up_flag
 * @property string $SKU_ID_back
 * @property string $COMPANY_CD
 * @property int $batch_id_from_onway
 * @property string $vir_type
 * @property float $warehouse_cost
 * @property float $warehouse_original_cost
 * @property float $service_cost
 * @property float $carry_cost
 * @property string $warehouse_cost_currency
 * @property \Carbon\Carbon $warehouse_cost_update_time
 *
 * @package App\Models
 */
class TbWmsBatch extends ORM
{
	protected $table = 'tb_wms_batch';
	public $timestamps = false;

	protected $casts = [
		'bill_id' => 'int',
		'stream_id' => 'int',
		'all_total_inventory' => 'int',
		'total_inventory' => 'int',
		'occupied' => 'int',
		'locking' => 'int',
		'available_for_sale_num' => 'int',
		'is_it_sensitive' => 'bool',
		'create_user_id' => 'int',
		'update_user_id' => 'int',
		'state' => 'bool',
		'batch_id' => 'int',
		'all_available_for_sale_num' => 'int',
		'all_oversole_number' => 'int',
		'up_flag' => 'int',
		'batch_id_from_onway' => 'int',
		'warehouse_cost' => 'float',
		'warehouse_original_cost' => 'float',
		'service_cost' => 'float',
		'carry_cost' => 'float'
	];

	protected $dates = [
		'deadline_date_for_use',
		'create_time',
		'original_storage_time',
		'update_time',
		'warehouse_cost_update_time'
	];

	protected $fillable = [
		'SKU_ID',
		'GUDS_ID',
		'channel',
		'CHANNEL_SKU_ID',
		'bill_id',
		'stream_id',
		'stream_id_str',
		'batch_code',
		'purchase_order_no',
		'purchase_team_code',
		'sale_team_code',
		'all_total_inventory',
		'total_inventory',
		'occupied',
		'locking',
		'available_for_sale_num',
		'deadline_date_for_use',
		'is_it_sensitive',
		'create_time',
		'original_storage_time',
		'update_time',
		'create_user_id',
		'update_user_id',
		'state',
		'batch_id',
		'all_available_for_sale_num',
		'all_oversole_number',
		'lock_code',
		'up_flag',
		'SKU_ID_back',
		'COMPANY_CD',
		'batch_id_from_onway',
		'vir_type',
		'warehouse_cost',
		'warehouse_original_cost',
		'service_cost',
		'carry_cost',
		'warehouse_cost_currency',
		'warehouse_cost_update_time'
	];
}
