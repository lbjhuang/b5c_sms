<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 08 Mar 2019 10:42:33 +0800.
 */

namespace App\Models;

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbSysReview
 *
 * @property int $id
 * @property int $wechat_msg_id
 * @property int $order_id
 * @property string $review_no
 * @property string $order_no
 * @property string $review_type
 * @property int $review_status
 * @property array $allowed_man_json
 * @property array $detail_json
 * @property string $callback_function
 * @property array $req_json
 * @property array $res_json
 * @property \Carbon\Carbon $review_at
 * @property string $review_by
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbSysReview extends ORM
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $casts = [
        'allowed_man_json' => 'json',
        'detail_json' => 'json',
        'req_json' => 'json',
        'res_json' => 'json'
    ];

    protected $dates = [
        'review_at'
    ];

    protected $fillable = [
        'wechat_msg_id',
        'order_id',
        'order_no',
        'review_no',
        'review_type',
        'review_status',
        'allowed_man_json',
        'detail_json',
        'callback_function',
        'req_json',
        'res_json',
        'review_at',
        'review_by',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
