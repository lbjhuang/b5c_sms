<?php
/**
 * 汇率获取
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/6/28
 * Time: 11:18
 */

class BaseCurrencyModel extends AbstractRequestModel
{
    public $currency;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取汇率
     * @param string $currency 被转换币种
     * @param string $targetCurrency 目标币种
     * @param string $transactionDate 交易日期
     */
    public function rate($currency, $targetCurrency, $transactionDate)
    {
        $currency       = strtolower($currency);
        $targetCurrency = strtolower($targetCurrency);
        $key = $currency . '-' . $targetCurrency;
        if (isset($this->currency [$key]))
            return $this->currency [$key];

        $requestData ['date'] = '2018-03-22';
        $requestData ['src_currency'] = 'USD';
        $requestData ['dst_currency'] = 'CNY';

        $submit = $this->submitRequest('dashboard-backend/external/exchangeRate', $requestData);
        var_dump($submit->getResponseData());exit;
    }
}