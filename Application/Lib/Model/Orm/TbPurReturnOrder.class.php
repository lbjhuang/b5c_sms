<?php

/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbPurReturnOrder
 * 
 * @property int $id
 * @property int $return_id
 * @property int $relevance_id
 * @property float $compensation
 * @property string $compensation_currency_cd
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbPurReturnOrder extends ORM
{
	use \Illuminate\Database\Eloquent\SoftDeletes;
	protected $table = 'tb_pur_return_order';

	protected $casts = [
		'return_id' => 'int',
		'relevance_id' => 'int',
		'compensation' => 'float'
	];

	protected $fillable = [
		'return_id',
		'relevance_id',
		'compensation',
		'compensation_currency_cd',
		'created_by',
		'updated_by',
		'deleted_by'
	];
}
