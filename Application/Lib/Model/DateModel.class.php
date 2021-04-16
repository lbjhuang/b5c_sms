<?php
/**
 * User: yangsu
 * Date: 18/7/25
 * Time: 15:33
 */


class DateModel extends \Model
{
    public static function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = date($format, strtotime($date));
        if ($d > 1970) {
            return ture;
        }
        return false;
    }

    public static function toYmd($date, $type = 'underline')
    {
        switch ($type) {
            case   'underline':
                $date_type = 'Y-m-d';
                break;
            default:
                $date_type = 'Ymd';

        }
        return date($date_type, strtotime($date));
    }

    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    public static function getDateFromRange($startdate, $enddate){

        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        $days = ($etimestamp-$stimestamp)/86400+1;
        $date = array();
        for($i=0; $i<$days; $i++){
            $date[] = date('Y-m-d', $stimestamp+(86400*$i));
        }
        return $date;
    }

}