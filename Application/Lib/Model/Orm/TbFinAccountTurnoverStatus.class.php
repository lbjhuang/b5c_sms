<?php

/**
 * User: yangsu
 * Date: 18/12/14
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;
/**
 * Class TbFinAccountTurnoverStatus
 * 
 * @property int $id
 * @property int $account_turnover_id
 * @property string $claim_status
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $tag_by
 * @property \Carbon\Carbon $tag_at
 *
 * @package App\Models
 */
class TbFinAccountTurnoverStatus extends ORM
{
	protected $table = 'tb_fin_account_turnover_status';

	protected $casts = [
		'account_turnover_id' => 'int'
	];

	protected $dates = [
		'tag_at'
	];

	protected $fillable = [
		'account_turnover_id',
		'claim_status',
		'created_by',
		'updated_by',
		'tag_by',
		'tag_at'
	];
}
