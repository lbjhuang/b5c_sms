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
 * Class TbWmsBatchOrder
 * 
 * @property int $id
 * @property string $ORD_ID
 * @property int $batch_id
 * @property int $batch_child_id
 * @property int $use_type
 * @property int $operate_Type
 * @property int $occupy_num
 * @property string $SKU_ID
 * @property string $GUDS_ID
 * @property string $delivery_warehouse
 * @property \Carbon\Carbon $create_time
 * @property \Carbon\Carbon $update_time
 * @property \Carbon\Carbon $oversale_time
 * @property \Carbon\Carbon $release_occupy_time
 * @property \Carbon\Carbon $occupy_time
 * @property \Carbon\Carbon $out_storage_time
 * @property int $create_user_id
 * @property int $update_user_id
 * @property string $oversold_type
 * @property bool $state
 * @property int $place_order_number
 * @property int $oversole_num
 * @property int $wms_batch_ord_id
 * @property string $sale_team_code
 * @property int $COMMIT_USER_ID
 * @property string $FILE_PATH
 * @property int $up_flag
 * @property string $SKU_ID_back
 * @property int $request_occupy_num
 * @property string $vir_type
 *
 * @package App\Models
 */
class TbWmsBatchOrder extends ORM
{
	protected $table = 'tb_wms_batch_order';
	public $timestamps = false;

	protected $casts = [
		'batch_id' => 'int',
		'batch_child_id' => 'int',
		'use_type' => 'int',
		'operate_Type' => 'int',
		'occupy_num' => 'int',
		'create_user_id' => 'int',
		'update_user_id' => 'int',
		'state' => 'bool',
		'place_order_number' => 'int',
		'oversole_num' => 'int',
		'wms_batch_ord_id' => 'int',
		'COMMIT_USER_ID' => 'int',
		'up_flag' => 'int',
		'request_occupy_num' => 'int'
	];

	protected $dates = [
		'create_time',
		'update_time',
		'oversale_time',
		'release_occupy_time',
		'occupy_time',
		'out_storage_time'
	];

	protected $fillable = [
		'ORD_ID',
		'batch_id',
		'batch_child_id',
		'use_type',
		'operate_Type',
		'occupy_num',
		'SKU_ID',
		'GUDS_ID',
		'delivery_warehouse',
		'create_time',
		'update_time',
		'oversale_time',
		'release_occupy_time',
		'occupy_time',
		'out_storage_time',
		'create_user_id',
		'update_user_id',
		'oversold_type',
		'state',
		'place_order_number',
		'oversole_num',
		'wms_batch_ord_id',
		'sale_team_code',
		'COMMIT_USER_ID',
		'FILE_PATH',
		'up_flag',
		'SKU_ID_back',
		'request_occupy_num',
		'vir_type'
	];
}
