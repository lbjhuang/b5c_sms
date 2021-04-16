<?php

/**
 * User: yansu
 * Date: 18/9/12
 * Time: 11:24
 */
class ExchangeRateModel extends Model
{
    /**
     * @return array
     */
    public static function getAllKeyValue()
    {
        $all_code = CodeModel::getCodeKeyValArr(['N00059'], 'Y');
        return $all_code;
    }

    /**
     * @param $from_currency_cd
     * @param $to_currency_cd
     * @param null $date
     * @param bool $is_code
     *
     * @return bool
     */
    public static function conversion($from_currency_cd, $to_currency_cd, $date = null, $is_code = true)
    {
        if (is_null($date)) {
            $date = DateModel::now();
        }
        if (!($from_currency_cd && $to_currency_cd && $date)) {
            return false;
        }
        if ($is_code) {
            $currency_cd_arr = self::getAllKeyValue();
            $to_currency = $currency_cd_arr[$to_currency_cd];
            $from_currency = $currency_cd_arr[$from_currency_cd];
        } else {
            $to_currency = $to_currency_cd;
            $from_currency = $from_currency_cd;
        }
        $date = DateModel::toYmd($date, 'underline');
        $url = BI_API_REVEN . '/external/exchangeRate?date=' . $date . '&dst_currency=' . $to_currency . '&src_currency=' . $from_currency;
        $currency_key = sha1($url);
        Logs([$url, $currency_key], 'currency_key', __CLASS__);
        $currency_response = RedisModel::get_key($currency_key);
        if (empty($currency_response)) {
            $currency_response = file_get_contents($url);
            RedisModel::set_key($currency_key, $currency_response, null, 1800);
        }
        $data = json_decode($currency_response, true);
        if ($data['success'] == true && $data['data'][0]['rate']) {
            return $data['data'][0]['rate'];
        } else {
            return false;
        }
    }

    public static function cnyToUsd($date)
    {
        return self::conversion('CNY', 'USD', $date, false);
    }

}
