<?php
/**
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/30
 * Time: 18:52
 */

class  ApiAction extends BaseAction
{
    private $_couponModel;

    public function _initialize()
    {
        $this->_couponModel = D('@Model/Coupon/Coupon');
    }

    /**
     * 统计优惠券发放数量
     */
    public function setSendedCouponCount()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
        empty($params) && $params = $_REQUEST;
        if (empty($params['couponId'])) {
            $result = ['code' => '400000030', 'msg' => 'params is  error! ', 'data' => null];
            $this->jsonOut($result);
        }
        $data = $this->_couponModel->getSendedCouponCount($params);
        if (empty($data['id'])) {
            $this->_couponModel->addSendedCouponCount($params);
        } else {
            $this->_couponModel->setSendedCouponCount($params);
        }
        $result = ['code' => '2000', 'msg' => 'success', 'data' => null];
        $this->jsonOut($result);
    }


    /**
     * 统计优惠券使用数量
     */
    public function setUsedCouponCount()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
        empty($params) && $params = $_REQUEST;
        if (empty($params['couponId'])) {
            $result = ['code' => '400000030', 'msg' => 'params is  error!', 'data' => null];
            $this->jsonOut($result);
        }
        $data = $this->_couponModel->getUsedCouponCount($params);
        if (empty($data['id'])) {
            $this->_couponModel->addUsedCouponCount($params);
        } else {
            $this->_couponModel->setUsedCouponCount($params);
        }
        $result = ['code' => '2000', 'msg' => 'success', 'data' => null];
        $this->jsonOut($result);
    }
}