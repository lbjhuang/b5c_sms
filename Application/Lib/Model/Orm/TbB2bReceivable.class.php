<?php

/**
 * User: yangsu
 * Date: 18/12/14
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbB2bReceivable
 * 
 * @property int $id
 * @property int $order_id
 * @property string $receivable_status
 * @property float $order_account
 * @property float $actual_collection
 * @property float $current_receivable
 * @property string $verification_by
 * @property \Carbon\Carbon $verification_at
 * @property string $cancel_note
 * @property string $submit_by
 * @property \Carbon\Carbon $submit_at
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbB2BReceivable extends ORM
{
	protected $table = 'tb_b2b_receivable';

	protected $casts = [
		'order_id' => 'int',
		'order_account' => 'float',
		'actual_collection' => 'float',
		'current_receivable' => 'float'
	];

	protected $dates = [
		'verification_at',
		'submit_at'
	];

	protected $fillable = [
		'order_id',
		'receivable_status',
		'order_account',
		'actual_collection',
		'current_receivable',
		'verification_by',
		'verification_at',
		'cancel_note',
		'submit_by',
		'submit_at',
		'created_by',
		'updated_by'
	];
}
