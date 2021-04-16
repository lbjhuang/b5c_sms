<?php
/**
 * 优惠券发送记录类
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/21
 * Time: 18:21
 */
class CouponReceiveModel extends RelationModel
{
    /**
     * 添加优惠券领取记录
     * @param $params
     * @return mixed
     */
    public function addCouponData($params)
    {
        $data['coupon_id'] = $params['conpouId'];
        $data['user_id'] = $params['userId'];
        $data['add_time'] = time();
        return $this->add($data);
    }
}