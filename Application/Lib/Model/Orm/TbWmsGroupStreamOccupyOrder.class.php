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
 * Class TbWmsGroupStreamOccupyOrder
 * 
 * @property int $id
 * @property int $tb_wms_group_stream_id
 * @property int $tb_wms_batch_order_id
 * @property int $item_num
 * @property \Carbon\Carbon $created_at
 * @property string $created_by
 * @property \Carbon\Carbon $updated_at
 * @property string $updated_by
 *
 * @package App\Models
 */
class TbWmsGroupStreamOccupyOrder extends ORM
{
	protected $table = 'tb_wms_group_stream_occupy_order';

	protected $casts = [
		'tb_wms_group_stream_id' => 'int',
		'tb_wms_batch_order_id' => 'int',
		'item_num' => 'int'
	];

	protected $fillable = [
		'tb_wms_group_stream_id',
		'tb_wms_batch_order_id',
		'item_num',
		'created_by',
		'updated_by'
	];
}
