<?php

/**
 * User: yangsu
 * Date: 18/2/28
 * Time: 16:59
 */
class StatusModel extends Model
{
    public $httpStatus = [
        100 => '',
        200 => '',
        300 => '',
        400 => '',
        500 => '',
    ];
    public $behaviorStatus = [
        0 => 'error',
        1 => 'success',
    ];
    public $eventStatus = [
    ];

}