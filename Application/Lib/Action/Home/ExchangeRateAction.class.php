<?php
/**
 * User: yangsu
 * Date: 18/12/21
 * Time: 15:30
 */

class ExchangeRateAction extends BaseAction
{
    public function _initialize()
    {
        if ('13014b1c785fb73b' != I('key')) {
            parent::_initialize();
        }
    }

    /**
     *
     */
    public function conversion()
    {
        $conversion = ExchangeRateModel::conversion(
            I('src_currency'),
            I('dst_currency'),
            I('date'),
            $is_code = false);
        $res = DataModel::$success_return;
        $res['data']['rate'] = $conversion;
        $this->ajaxReturn($res);
    }

}
