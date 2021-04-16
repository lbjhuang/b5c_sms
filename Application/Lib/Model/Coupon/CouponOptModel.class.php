<?php
/**
 * 优惠券操作记录
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/21
 * Time: 18:19
 */
class CouponOptModel extends RelationModel
{
    protected $trueTableName = "tb_ms_coupon_opt";
    /**
     * 添加优惠券领取记录
     * @param $params
     * @return mixed
     */
    public function addCouponOptData($params)
    {
        $data['coupon_id'] = $params['couponId'];
        $data['opt_type'] = $params['optType'];
        $data['opt_user_name'] = $params['optUserName'];
        $data['opt_user_id'] = $params['optUserId'];
        $data['add_time'] = time();
        return $this->add($data);
    }

    /**
     * 获取优惠券操作记录
     * @param $couponId
     * @return mixed
     */
    public function  getCouponOptData($couponId)
    {
        $where['coupon_id'] = $couponId;
        return $this->where($where)->select();
    }
}