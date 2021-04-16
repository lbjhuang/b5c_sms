<?php

/**
 * User: fuming
 * Date: 20/09/16
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;
/**
 * Class TbFinTaxRate
 * 
 * @property string $our_company_cd
 * @property int $country_id
 * @property string $vat_number
 * @property float $tax_rate
 *
 * @package App\Models
 */
class TbFinTaxRate extends ORM
{
	protected $table = 'tb_fin_tax_number';

	protected $casts = [
		'country_id' => 'int',
		'tax_rate'   => 'float'
	];

	protected $dates = [
	];

	protected $fillable = [
		'our_company_cd',
		'country_id',
		'vat_number',
		'tax_rate',
		'created_by',
		'created_at',
		'updated_by',
		'updated_at',
		'deleted_by',
		'deleted_at'
	];
}
