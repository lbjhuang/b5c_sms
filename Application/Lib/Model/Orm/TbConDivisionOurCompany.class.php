<?php

namespace Application\Lib\Model\Orm;
/**
 * Created by fuming.
 * Date: Mon, 19 Aug 2019 02:45:10 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbConDivisionOurCompany
 * 
 * @property int $id
 * @property string $our_company_cd
 * @property string $payment_manager_by
 * @property string $invoice_person_charge_by
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbConDivisionOurCompany extends ORM
{
	use \Illuminate\Database\Eloquent\SoftDeletes;
	protected $table = 'tb_con_division_our_company';

	protected $fillable = [
		'our_company_cd',
		'payment_manager_by',
		'invoice_person_charge_by',
		'created_by',
		'updated_by',
		'deleted_by',
        'b2b_manager_by',
	];
}
