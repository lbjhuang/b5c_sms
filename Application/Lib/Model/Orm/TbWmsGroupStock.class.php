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
 * Class TbWmsGroupStock
 * 
 * @property int $id
 * @property int $group_stream_id
 * @property string $sku_id
 * @property int $all_num
 * @property int $available_num
 * @property int $close_num
 * @property int $lock_num
 * @property \Carbon\Carbon $created_at
 * @property string $created_by
 * @property \Carbon\Carbon $updated_at
 * @property string $updated_by
 *
 * @package App\Models
 */
class TbWmsGroupStock extends ORM
{
	protected $table = 'tb_wms_group_stock';

	protected $casts = [
		'group_stream_id' => 'int',
		'all_num' => 'int',
		'available_num' => 'int',
		'close_num' => 'int',
		'lock_num' => 'int'
	];

	protected $fillable = [
		'group_stream_id',
		'sku_id',
		'all_num',
		'available_num',
		'close_num',
		'lock_num',
		'created_by',
		'updated_by'
	];
}
