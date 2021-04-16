<?php

/**
 * 优惠券关联商品
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/21
 * Time: 18:13
 */
class CouponProcessRecordModel extends RelationModel
{
    protected $trueTableName = "tb_ms_coupon_process_record";

    /**
     * 添加优惠券请求gshopper记录
     * @param $params
     * @return mixed
     */
    public function addCouponProcessRecordData($params)
    {
        $data['coupon_id'] = $params['couponId'];
        $data['thr_id'] = $params['thrdId'];
        $data['plat_cd'] = $params['platCode'];
        $data['add_time'] = time();
        $data['update_time'] = 0;
        return $this->add($data);
    }

    /**
     * 修改优惠券请求gshopper记录
     * @param $params
     * @return mixed
     */
    public function updateCouponProcessRecordData($params)
    {
        $where['coupon_id'] = $params['couponId'];
        $where['plat_cd'] = $params['platCode'];
        !empty($params['thrdId']) && $data['thr_id'] = $params['thrdId'];
        $data['update_time'] = time();
        return $this->where($where)->save($data);
    }

    /**
     * 通过优惠券id 和 平台 获取第三方id
     * @param $couponId
     * @param $plat
     * @return mixed
     */
    public function getCouponProcessRecordByCondition($couponId, $plat)
    {
        $where['coupon_id'] = $couponId;
        $where['plat_cd'] = $plat;
        return $this->field('thr_id,coupon_id')->where($where)->find();
    }

}