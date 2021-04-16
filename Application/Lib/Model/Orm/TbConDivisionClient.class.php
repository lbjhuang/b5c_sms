<?php

/**
 * User: yangsu
 * Date: 19/05/06
 * Time: 18:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbConDivisionClient
 * 
 * @property int $id
 * @property int supplier_id
 * @property string $sales_assistant_by
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbConDivisionClient extends ORM
{
	use \Illuminate\Database\Eloquent\SoftDeletes;
	protected $table = 'tb_con_division_client';

	protected $fillable = [
		'supplier_id',
		'sales_assistant_by',
		'created_by',
		'updated_by',
		'deleted_by'
	];
}
