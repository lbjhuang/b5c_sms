<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/1/16
 * Time: 16:56
 */

class TimeStapModel
{
    public static function authTimestap($params)
    {
        if (C('TIMESTAP_AUTH') ['USE']) {
            $sign = compressData($params ['token'].$params ['timestap']);
            if ($sign == $params ['sign']) {
                if (((time() - $params ['timestap']) / 3600) > C('TIMESTAT_AUTH') ['HOUR']) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}