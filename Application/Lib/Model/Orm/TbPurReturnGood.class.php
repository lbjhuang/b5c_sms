<?php

/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 13:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbPurReturnGood
 *
 * @property int $id
 * @property int $return_id
 * @property int $return_order_id
 * @property int $information_id
 * @property int $return_number
 * @property string $vir_type_cd
 * @property int $tally_number
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbPurReturnGood extends ORM
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $casts = [
        'return_id' => 'int',
        'return_order_id' => 'int',
        'information_id' => 'int',
        'return_number' => 'int',
        'tally_number' => 'int'
    ];

    protected $fillable = [
        'return_id',
        'return_order_id',
        'information_id',
        'return_number',
        'vir_type_cd',
        'tally_number',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function TbPurGoodsInformation()
    {
       return $this->hasOne(TbPurGoodsInformation::class, 'information_id', 'information_id');
    }
}
