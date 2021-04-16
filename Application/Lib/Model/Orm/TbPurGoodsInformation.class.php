<?php

/**
 * User: yangsu
 * Date: 19/2/28
 * Time: 10:14
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbPurGoodsInformation
 * 
 * @property int $information_id
 * @property string $sku_information
 * @property string $goods_name
 * @property string $invoice_name
 * @property string $goods_attribute
 * @property string $valuation_unit
 * @property string $unit_price
 * @property string $unit_price_not_contain_tax
 * @property string $hotness
 * @property string $goods_number
 * @property string $drawback_percent
 * @property int $shipped_number
 * @property string $goods_money
 * @property string $goods_money_not_contain_tax
 * @property float $unit_expense
 * @property float $invoiced_money
 * @property string $remark
 * @property \Carbon\Carbon $create_time
 * @property string $create_user
 * @property \Carbon\Carbon $update_time
 * @property string $update_user
 * @property int $relevance_id
 * @property int $payable_id
 * @property string $search_information
 * @property string $sku_information_back
 * @property string $search_information_back
 *
 * @package App\Models
 */
class TbPurGoodsInformation extends ORM
{
	protected $table = 'tb_pur_goods_information';
	protected $primaryKey = 'information_id';
	public $timestamps = false;

	protected $casts = [
		'shipped_number' => 'int',
		'unit_expense' => 'float',
		'invoiced_money' => 'float',
		'relevance_id' => 'int',
		'payable_id' => 'int'
	];

	protected $dates = [
		'create_time',
		'update_time'
	];

	protected $fillable = [
		'sku_information',
		'goods_name',
		'invoice_name',
		'goods_attribute',
		'valuation_unit',
		'unit_price',
		'unit_price_not_contain_tax',
		'hotness',
		'goods_number',
		'drawback_percent',
		'shipped_number',
		'goods_money',
		'goods_money_not_contain_tax',
		'unit_expense',
		'invoiced_money',
		'remark',
		'create_time',
		'create_user',
		'update_time',
		'update_user',
		'relevance_id',
		'payable_id',
		'search_information',
		'sku_information_back',
		'search_information_back'
	];
}
