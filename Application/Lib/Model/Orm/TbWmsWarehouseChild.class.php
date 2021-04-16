<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 19/3/1
 * Time: 16:52
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsWarehouseChild
 * 
 * @property int $id
 * @property string $cd
 * @property string $warehouse_name
 * @property int $feature
 * @property string $instance_of
 * @property string $real_in_storage
 *
 * @package App\Models
 */
class TbWmsWarehouseChild extends ORM
{
	protected $table = 'tb_wms_warehouse_child';
	public $timestamps = false;

	protected $casts = [
		'feature' => 'int'
	];

	protected $fillable = [
		'cd',
		'warehouse_name',
		'feature',
		'instance_of',
		'real_in_storage'
	];
}
