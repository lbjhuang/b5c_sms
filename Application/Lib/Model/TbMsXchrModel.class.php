<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 14:01
 */
class TbMsXchrModel extends RelationModel
{
    protected $trueTableName = 'tb_ms_xchr';

    /**
     * @param string $date
     * @return mixed
     */
    public function currency_rates($date = '') {
        if(!$date) {
            $date = date('Ymd');
        }
        $rates = $this->where(['XCHR_STD_DT'=>$date])->find();
        foreach ($rates as $k => $v) {
            if(substr($k,-13) == '_XCHR_AMT_CNY') {
                $curr_rates[substr($k,0,3)] = $v;
            }
        }
        $curr_rates['CNY'] = 1;
        return $curr_rates;
    }
}
