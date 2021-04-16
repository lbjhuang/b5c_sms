<?php

/**
 * PHP version 5.6
 * User: yangsu
 * Date: 18/9/27
 * Time: 19:52
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsStream
 *
 * @property int            $id
 * @property int            $bill_id
 * @property int            $line_number
 * @property int            $goods_id
 * @property string         $GSKU
 * @property int            $should_num
 * @property int            $send_num
 * @property string         $warehouse_id
 * @property int            $location_id
 * @property string         $batch
 * @property \Carbon\Carbon $deadline_date_for_use
 * @property float          $unit_price_usd
 * @property float          $unit_price
 * @property float          $no_unit_price
 * @property string         $taxes
 * @property float          $unit_money
 * @property float          $no_unit_money
 * @property int            $duty
 * @property string         $currency_id
 * @property string         $give_status
 * @property \Carbon\Carbon $add_time
 * @property string         $digit
 * @property \Carbon\Carbon $currency_time
 * @property int            $up_flag
 * @property string         $GSKU_back
 * @property bool           $outgoing_type
 * @property string         $reported_loss_reason
 * @property bool           $tag
 * @property float          $pur_invoice_tax_rate
 * @property float          $proportion_of_tax
 * @property float          $storage_log_cost
 * @property float          $log_service_cost
 * @property \Carbon\Carbon $pur_storage_date
 * @property string         $log_currency
 * @property \Carbon\Carbon $create_time
 *
 * @package App\Models
 */
class TbWmsStream extends ORM
{
    protected $table = 'tb_wms_stream';
    public $timestamps = false;

    protected $casts = [
        'bill_id' => 'int',
        'line_number' => 'int',
        'goods_id' => 'int',
        'should_num' => 'int',
        'send_num' => 'int',
        'location_id' => 'int',
        'unit_price_usd' => 'float',
        'unit_price' => 'float',
        'no_unit_price' => 'float',
        'unit_money' => 'float',
        'no_unit_money' => 'float',
        'duty' => 'int',
        'up_flag' => 'int',
        'outgoing_type' => 'bool',
        'tag' => 'bool',
        'pur_invoice_tax_rate' => 'float',
        'proportion_of_tax' => 'float',
        'storage_log_cost' => 'float',
        'log_service_cost' => 'float'
    ];

    protected $dates = [
        'deadline_date_for_use',
        'add_time',
        'currency_time',
        'pur_storage_date',
        'create_time'
    ];

    protected $fillable = [
        'bill_id',
        'line_number',
        'goods_id',
        'GSKU',
        'should_num',
        'send_num',
        'warehouse_id',
        'location_id',
        'batch',
        'deadline_date_for_use',
        'unit_price_usd',
        'unit_price',
        'no_unit_price',
        'taxes',
        'unit_money',
        'no_unit_money',
        'duty',
        'currency_id',
        'give_status',
        'add_time',
        'digit',
        'currency_time',
        'up_flag',
        'GSKU_back',
        'outgoing_type',
        'reported_loss_reason',
        'tag',
        'pur_invoice_tax_rate',
        'proportion_of_tax',
        'storage_log_cost',
        'log_service_cost',
        'pur_storage_date',
        'log_currency',
        'create_time'
    ];
}
