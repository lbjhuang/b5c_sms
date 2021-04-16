<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 08 Mar 2019 10:44:30 +0800.
 */

namespace App\Models;

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbSysMessageWechat
 *
 * @property int $id
 * @property string $key_table_name
 * @property int $key_table_id
 * @property string $wechat_application_name
 * @property string $wechat_msg_type
 * @property array $receiver_by_json
 * @property string $api_request_url
 * @property array $api_request_body_json
 * @property int $wechat_response_errcode
 * @property array $api_response_json
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_by
 * @property string $deleted_at
 *
 * @package App\Models
 */
class TbSysMessageWechat extends ORM
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'tb_sys_message_wechat';

    protected $casts = [
        'key_table_id' => 'int',
        'receiver_by_json' => 'json',
        'api_request_body_json' => 'json',
        'wechat_response_errcode' => 'int',
        'api_response_json' => 'json'
    ];

    protected $fillable = [
        'key_table_name',
        'key_table_id',
        'wechat_application_name',
        'wechat_msg_type',
        'receiver_by_json',
        'api_request_url',
        'api_request_body_json',
        'wechat_response_errcode',
        'api_response_json',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
