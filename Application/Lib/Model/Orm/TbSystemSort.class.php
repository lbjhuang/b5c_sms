<?php

/**
 * User: yangsu
 * Date: Tue, 21 Aug 2018 03:18:06 +0000.
 */

@import("@.Model.ORM");

use Application\Lib\Model\ORM;


/**
 * Class TbSystemSort
 *
 * @property int    $id
 * @property string $sort_value
 *
 * @package App\Models
 */
class TbSystemSort extends ORM
{
    protected $fillable = [
        'sort_value'
    ];
}
