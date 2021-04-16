<?php

/**
 * User: yangsu
 * Date: 18/12/14
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbB2bClaimDeduction
 * 
 * @property int $id
 * @property int $claim_id
 * @property string $deduction_type
 * @property float $deduction_amount
 * @property string $credentials_show_name
 * @property string $credentials_path
 * @property string $instructions
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbB2BClaimDeduction extends ORM
{
	protected $table = 'tb_b2b_claim_deduction';

	protected $casts = [
		'claim_id' => 'int',
		'deduction_amount' => 'float'
	];

	protected $fillable = [
		'claim_id',
		'deduction_type',
		'deduction_amount',
		'credentials_show_name',
		'credentials_path',
		'instructions',
		'created_by',
		'updated_by'
	];
}
