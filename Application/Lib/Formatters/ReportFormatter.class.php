<?php
/**
 * User: yangsu
 * Date: 19/08/21
 * Time: 14:32
 */


class ReportFormatter extends Formatter
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function b2bReceivable($data)
    {
        return $this->numberFormatArray($data, 2,
            ['initial_receivabl', 'remaining_receivabl', 'remaining_receivabl_cny', 'remaining_receivabl_usd',]
        );
    }
}