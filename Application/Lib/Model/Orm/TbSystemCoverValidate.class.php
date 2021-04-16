<?php

/**
 * User: yangsu
 * Date: Tue, 21 Aug 2018 03:34:40 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;

/**
 * Class TbSystemCoverValidate
 *
 * @property int    $id
 * @property string $order_id
 * @property string $plat_cd
 * @property int    $sorts_id
 * @property array  $validate_json
 *
 * @package App\Models
 */
class TbSystemCoverValidate extends ORM
{
    protected $casts = [
        'sorts_id' => 'int',
        'validate_json' => 'json'
    ];

    protected $fillable = [
        'order_id',
        'plat_cd',
        'sorts_id',
        'validate_json',
        'influence_table_name',
    ];
}
