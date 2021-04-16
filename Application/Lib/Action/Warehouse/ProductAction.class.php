<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/15
 * Time: 14:06
 */

class ProductAction extends BaseAction
{
    public function conversion_list() {
        $params = $this->getParams();
        $logic  = new ConversionLogic();
        $res    = $logic->conversionList($params);
        $this->ajaxSuccess($res);
    }


    public function create_conversion() {
        $params = $this->getParams();
        $logic  = new ConversionLogic();
        $res    = $logic->createConversion($params);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function conversion_goods_search() {
        $params = $this->getParams();
        $logic  = new ConversionLogic();
        $res    = $logic->conversionGoodsSearch($params);
        $this->ajaxSuccess($res);
    }

    public function conversion_detail() {
        $params = $this->getParams();
        $logic  = new ConversionLogic();
        $res    = $logic->conversionDetail($params['id']);
        $this->ajaxSuccess($res);
    }

    public function approve()
    {
        $params = $this->getParams();
        $ret = ProductTransfer::approve($params);
        $this->ajaxReturn($ret);
    }
    public function revoke()
    {
        $params = $this->getParams();
        $ret = ProductTransfer::revoke($params);
        $this->ajaxReturn($ret);
    }
}