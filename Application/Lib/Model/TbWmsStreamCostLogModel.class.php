<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/10/18
 * Time: 15:07
 */

class TbWmsStreamCostLogModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_stream_cost_log';

    protected $_auto = [
        ['create_time', 'getTime', 3, 'callback']
    ];

    public $currencyRate = [
        'N000590200' => 'KRW',
        'N000590300' => 'CNY',
        'N000590400' => 'JPY'
    ];

    public $globalRate;

    public $exchangeRateUrl = 'http://3rd-biapi.izene.org/external/exchangeRate?';

    /**
     * 转换汇率
     * @param date $date
     */
    public function rateFunc($date)
    {
        $time = date('Y-m-d', strtotime($date));
        foreach ($this->currencyRate as $currencyCode => $currencyCharacterCode) {
            if (isset($this->globalRate [$time][$currencyCode]))
                continue;
            if (empty($currencyCharacterCode)) {
                continue;
            }
            $queryParams = [
                'date' => $time,
                'src_currency' => 'USD',
                'dst_currency' => strtoupper($currencyCharacterCode)
            ];

            $buildQuery = http_build_query($queryParams);
            $response = curl_get_json_get($this->exchangeRateUrl . $buildQuery);

            $this->globalRate [$time][$currencyCode] = json_decode($response, true) ['data'][0]['rate'];
        }
    }

    /**
     * @param mixed $params 数据
     */
    public function addRecording($params)
    {
        $purDate = array_column($params, 'pur_storage_date');

        foreach ($purDate as $key => $value) {
            $this->rateFunc($value);
        }

        $logServiceRateDate = array_column($params ,'service_and_log_rate_time');

        foreach ($logServiceRateDate as $key => $value) {
            $this->rateFunc($value);
        }

        $saveData = [];

        foreach ($params as $key => $value) {
            // 临时保存美元相关的费用
            $saveData [] = $this->create($value);
            foreach ($this->currencyRate as $currencyCode => $currencyCharacterCode) {
                $rate = 0;
                $rate = $this->globalRate [date('Y-m-d', strtotime($value ['pur_storage_date']))][$currencyCode];
                $changeValue = $value;
                $changeValue ['currency_id'] = $currencyCode;
                $changeValue ['po_cost'] = $value ['po_cost'] * $rate;
                $changeValue ['all_po_cost'] = $value ['all_po_cost'] * $rate;
                if (isset($value ['service_and_log_rate_time'])) {
                    $rate = $this->globalRate [date('Y-m-d', strtotime($value ['service_and_log_rate_time']))][$currencyCode];
                }
                $changeValue ['carry_cost'] = $value ['carry_cost'] * $rate;
                $changeValue ['all_carry_cost'] = $value ['all_carry_cost'] * $rate;
                $changeValue ['log_service_cost'] = $value ['log_service_cost'] * $rate;
                $changeValue ['all_log_service_cost'] = $value ['all_log_service_cost'] * $rate;
                $changeValue ['storage_log_cost'] = $value ['storage_log_cost'] * $rate;
                $changeValue ['all_storage_log_cost'] = $value ['all_storage_log_cost'] * $rate;

                $saveData [] = $this->create($changeValue);
            }
        }
        
        if ($saveData)
            $this->addAll($saveData);
    }
}