<?php
/**
 * User: yangsu
 * Date: 19/6/26
 * Time: 14:32
 */


class Formatter
{
    protected function numberFormat(&$data, $decimals = 2)
    {
        if (is_string($data) || is_int($data) || is_float($data)) {
            $data = number_format($data, $decimals);
        }
    }

    protected function numberFormatArray($data, $decimals = 2, $conversion_array = [])
    {
        foreach ($data as &$datum) {
            foreach ($datum as $key => &$value) {
                if (is_int($value) || is_float($value) || in_array($key, $conversion_array)) {
                    $value = number_format($value, $decimals);
                }
            }
        }
        return $data;
    }
}