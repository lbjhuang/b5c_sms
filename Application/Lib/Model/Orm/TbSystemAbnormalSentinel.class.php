<?php
/**
 * User: yangsu
 * Date: 19/3/1
 * Time: 10:34
 **/

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbSystemAbnormalSentinel
 *
 * @property int $id
 * @property string $key
 * @property string $msg
 * @property array $content_json
 * @property string $processing_status
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 * @property string noticed_by
 * @property string noticed_at
 * @property string noticed_msg_json
 *
 * @package App\Models
 */
class TbSystemAbnormalSentinel extends ORM
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'tb_system_abnormal_sentinels';
    public $incrementing = false;

    protected $casts = [
        'id' => 'int',
        'content_json' => 'json',
        'noticed_msg_json' => 'json'
    ];

    protected $fillable = [
        'key',
        'msg',
        'content_json',
        'noticed_msg_json',
        'processing_status',
        'created_by',
        'updated_by',
        'deleted_by',
        'noticed_by',
        'noticed_at'
    ];
}
