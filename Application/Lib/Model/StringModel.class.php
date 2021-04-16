<?php
/**
 * User: yangsu
 * Date: 18/7/31
 * Time: 15:19
 */

namespace Application\Lib\Model;


/**
 * Class StringModel
 * @package Application\Lib\Model
 */
class StringModel
{
    /**
     * 不换行空格填写
     * @param $string
     * @return string
     */
    public static function replaceNonBreakingSpace($string)
    {
        $clean_string = $string;
        $temp_string = urlencode($string);
        if (strpos($temp_string, '%C2%A0') !== false) {
            $clean_string = urldecode(str_replace('%C2%A0', '%20', $temp_string));
        }
        return $clean_string;
    }

    /**
     * @param $currency
     * @return string
     */
    public static function getXchrCurrency($currency)
    {
        return "CASE {$currency}
                WHEN 'USD' THEN tb_ms_xchr.USD_XCHR_AMT_CNY
                WHEN 'EUR' THEN tb_ms_xchr.EUR_XCHR_AMT_CNY
                WHEN 'HKD' THEN tb_ms_xchr.HKD_XCHR_AMT_CNY
                WHEN 'SGD' THEN tb_ms_xchr.SGD_XCHR_AMT_CNY
                WHEN 'AUD' THEN tb_ms_xchr.AUD_XCHR_AMT_CNY
                WHEN 'GBP' THEN tb_ms_xchr.GBP_XCHR_AMT_CNY
                WHEN 'CAD' THEN tb_ms_xchr.CAD_XCHR_AMT_CNY
                WHEN 'MYR' THEN tb_ms_xchr.MYR_XCHR_AMT_CNY
                WHEN 'DEM' THEN tb_ms_xchr.DEM_XCHR_AMT_CNY
                WHEN 'MXN' THEN tb_ms_xchr.MXN_XCHR_AMT_CNY
                WHEN 'THB' THEN tb_ms_xchr.THB_XCHR_AMT_CNY
                WHEN 'PHP' THEN tb_ms_xchr.PHP_XCHR_AMT_CNY
                WHEN 'IDR' THEN tb_ms_xchr.IDR_XCHR_AMT_CNY
                WHEN 'TWD' THEN tb_ms_xchr.TWD_XCHR_AMT_CNY
                WHEN 'VND' THEN tb_ms_xchr.VND_XCHR_AMT_CNY
                WHEN 'KRW' THEN tb_ms_xchr.KRW_XCHR_AMT_CNY
                WHEN 'JPY' THEN tb_ms_xchr.JPY_XCHR_AMT_CNY
                WHEN 'CNY' THEN tb_ms_xchr.CNY_XCHR_AMT_CNY
                WHEN 'NGN' THEN tb_ms_xchr.NGN_XCHR_AMT_CNY
                END";
    }

    public static function getXchrCurrencyField($currency)
    {
        switch ($currency) {
            case 'USD': return 'USD_XCHR_AMT_CNY';breack;
            case 'EUR': return 'EUR_XCHR_AMT_CNY';breack;
            case 'HKD': return 'HKD_XCHR_AMT_CNY';breack;
            case 'SGD': return 'SGD_XCHR_AMT_CNY';breack;
            case 'AUD': return 'AUD_XCHR_AMT_CNY';breack;
            case 'GBP': return 'GBP_XCHR_AMT_CNY';breack;
            case 'CAD': return 'CAD_XCHR_AMT_CNY';breack;
            case 'MYR': return 'MYR_XCHR_AMT_CNY';breack;
            case 'DEM': return 'DEM_XCHR_AMT_CNY';breack;
            case 'MXN': return 'MXN_XCHR_AMT_CNY';breack;
            case 'THB': return 'THB_XCHR_AMT_CNY';breack;
            case 'PHP': return 'PHP_XCHR_AMT_CNY';breack;
            case 'IDR': return 'IDR_XCHR_AMT_CNY';breack;
            case 'TWD': return 'TWD_XCHR_AMT_CNY';breack;
            case 'VND': return 'VND_XCHR_AMT_CNY';breack;
            case 'KRW': return 'KRW_XCHR_AMT_CNY';breack;
            case 'JPY': return 'JPY_XCHR_AMT_CNY';breack;
            case 'CNY': return 'CNY_XCHR_AMT_CNY';breack;
            case 'NGN': return 'NGN_XCHR_AMT_CNY';breack;
        }
    }

}