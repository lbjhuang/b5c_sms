<?php

/**
 * PHP version 5.5
 * User: yangsu
 * Date: 18/10/23
 * Time: 18:29
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbWmsGroupStream
 * 
 * @property int $id
 * @property int $group_bill_id
 * @property string $sku_id
 * @property int $self_num
 * @property int $from_batch
 * @property int $to_batch
 * @property int $parent_group_stream_id
 * @property \Carbon\Carbon $created_at
 * @property string $created_by
 * @property \Carbon\Carbon $updated_at
 * @property string $updated_by
 *
 * @package App\Models
 */
class TbWmsGroupStream extends ORM
{
	protected $table = 'tb_wms_group_stream';

	protected $casts = [
		'group_bill_id' => 'int',
		'self_all_num' => 'int',
		'from_batch' => 'int',
		'to_batch' => 'int',
		'parent_group_stream_id' => 'int'
	];

	protected $fillable = [
		'group_bill_id',
		'sku_id',
		'self_all_num',
		'from_batch',
		'to_batch',
		'parent_group_stream_id',
		'created_by',
		'updated_by'
	];

    public function TbWmsGroupBill()
    {
        return $this->belongsTo(TbWmsGroupBill::class, 'group_bill_id');
    }

}
