<?php

/**
 * 优惠券信息
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/20
 * Time: 18:43
 */
class CouponModel extends RelationModel
{
    protected $trueTableName = "tb_ms_coupon";
    protected $shopTableName = "tb_ms_store";
    protected $usedCountTableName = "tb_ms_coupon_used_count";
    protected $sendedCountTableName = "tb_ms_coupon_sended_count";
    protected $dictTableName = "tb_ms_cmn_cd";

    /**
     * 查询优惠券
     * @param $params
     * @param $type
     * @param $offset
     * @param $limit
     * @param array $order
     * @return array
     */
    public function getConpouList($params, $type, $offset, $limit, $order = array('id' => 'DESC'))
    {
        $where = array();
        if ($params['status'] != '-1') {
            $where[$this->trueTableName . '.status'] = $params['status'];
        }
        if (!empty($params['id'])) {
            $where[$this->trueTableName . '.id'] = $params['id'];
        }
        if (!empty($params['title'])) {
            $where[$this->trueTableName . '.title'] = $params['title'];
        }
        if (!empty($params['type'])) {
            $where[$this->trueTableName . '.coupon_type'] = $params['type'];
        }
        if (!empty($params['name'])) {
            $where[$this->trueTableName . '.creator_name'] = $params['name'];
        }
        if (!empty($params['sendWay'])) {
            $where[$this->trueTableName . '.send_way'] = $params['sendWay'];
        }
        $field = $this->trueTableName . '.*,'  . $this->sendedCountTableName . '.total as sendedNum';
        if ($type == 'count') {
            return $this->where($where)->count();
        }
        $subQuery = $this->field($field)
            ->join(' LEFT JOIN ' . $this->sendedCountTableName . ' ON ' . $this->trueTableName . '.id =' . $this->sendedCountTableName . '.coupon_id')
            ->where($where)
            ->order($order)
            ->limit($offset, $limit)
            ->buildSql();
        $result = $this->query($subQuery);
        return $result;

    }

    /**
     * 获取优惠券详情
     * @param $couponId
     * @return mixed
     */
    public function getCouponDetail($couponId)
    {
        $where['id'] = $couponId;
        return $this->where($where)->find();
    }

    /**
     * 添加优惠券信息
     * @param $params
     * @return mixed
     */
    public function addCouponData($params)
    {
        $data['title'] = $params['title'];
        $data['coupon_type'] = $params['couponType'];
        $data['status'] = $params['status'];
        $data['creator_name'] = $params['creatorName'];
        $data['creator_id'] = $params['creatorId'];
        $data['send_way'] = $params['sendWay'];
        $data['send_object'] = $params['sendObject'];
        $data['order_num'] = $params['orderNum'];
        $data['shop'] = $params['shop'];
        $data['threshold'] = $params['threshold'];
        $data['threshold_condition'] = $params['thresholdCondition'];
        $data['proportion'] = $params['proportion'];
        $data['max_amount'] = $params['maxAmount'];
        $data['used_time_type'] = $params['timeType'];
        $data['used_time_value'] = $params['timeValue'];
        $data['superposition_rule'] = $params['superpositionRule'];
        $data['use_range'] = $params['useRange'];
        $data['add_time'] = $params['addTime'];
        $data['user_total'] = $params['userTotal'];
        $data['guds_total'] = $params['gudsTotal'];
        $data['start_time'] = 0;
        $data['update_time'] = 0;
        return $this->add($data);
    }

    /**
     * 修改优惠券信息
     * @param $params
     * @return mixed
     */
    public function updateCouponData($params)
    {
        $where['id'] = $params['couponId'];
        !empty($params['title']) && $data['title'] = $params['title'];
        !empty($params['couponType']) && $data['coupon_type'] = $params['couponType'];
        if ($params['status'] !== '' && isset($params['status'])) {
            $data['status'] = $params['status'];
        }
        !empty($params['sendWay']) && $data['send_way'] = $params['sendWay'];
        !empty($params['sendObject']) && $data['send_object'] = $params['sendObject'];
        if ($params['orderNum'] !== '' && isset($params['orderNum'])) {
            $data['order_num'] = $params['orderNum'];
        }
        !empty($params['shop']) && $data['shop'] = $params['shop'];
        if ($params['threshold'] !== '' && isset($params['threshold'])) {
            $data['threshold'] = $params['threshold'];
        }
        if ($params['thresholdCondition'] !== '' && isset($params['thresholdCondition'])) {
            $data['threshold_condition'] = $params['thresholdCondition'];
        }
        if ($params['proportion'] !== '' && isset($params['proportion'])) {
            $data['proportion'] = $params['proportion'];
        }
        if ($params['maxAmount'] !== '' && isset($params['maxAmount'])) {
            $data['max_amount'] = $params['maxAmount'];
        }
        !empty($params['timeType']) && $data['used_time_type'] = $params['timeType'];
        !empty($params['timeValue']) && $data['used_time_value'] = $params['timeValue'];
        if ($params['superpositionRule'] !== '' && isset($params['superpositionRule'])) {
            $data['superposition_rule'] = $params['superpositionRule'];
        }
        !empty($params['useRange']) && $data['use_range'] = $params['useRange'];
        if ($params['userTotal'] !== '' && isset($params['userTotal'])) {
            $data['user_total'] = $params['userTotal'];
        }
        if ($params['gudsTotal'] !== '' && isset($params['gudsTotal'])) {
            $data['guds_total'] = $params['gudsTotal'];
        }
        !empty($params['startTime']) && $data['start_time'] = $params['startTime'];
        !empty($params['updateTime']) && $data['update_time'] = $params['updateTime'];
        return $this->where($where)->save($data);
    }

    /**
     * 获取店铺信息
     * @return mixed
     */
    public function getShopData()
    {
        $where[]['STORE_NAME'] = ['like', 'Gshopper-%'];
        return $this->table($this->shopTableName)->where($where)->getField('PLAT_CD,ID,STORE_NAME,MERCHANT_ID');
    }

    /**
     *通过店铺id获取店铺内容
     */
    public function getShopNameById($shopIds)
    {
        $where[]['ID'] = ['in', $shopIds];
        return $this->table($this->shopTableName)->field('PLAT_CD,ID,STORE_NAME,MERCHANT_ID')->where($where)->select();
    }


    /**
     * 统计优惠券发放数量
     * @param $params
     */
    public function setSendedCouponCount($params)
    {
        $this->table($this->sendedCountTableName)->where('coupon_id =' . $params['couponId'])->setInc('total', 1);
    }
    /**
     * 统计优惠券发放数量
     * @param $params
     */
    public function getSendedCouponCount($params)
    {
        return $this->table($this->sendedCountTableName)->where('coupon_id =' . $params['couponId'])->find();
    }
    /**
     * 统计优惠券发放数量
     * @param $params
     */
    public function addSendedCouponCount($params)
    {
        $data['coupon_id'] = $params['couponId'];
        $data['total'] = 1;
        return $this->table($this->sendedCountTableName)->add($data);
    }

    /**
     * 统计优惠券使用数量
     * @param $params
     */
    public function getUsedCouponCountByIds($ids)
    {
        $where[]['coupon_id'] = ['in',$ids];
        return $this->table($this->usedCountTableName)->where($where)->group('coupon_id')->getField('coupon_id,count(total) as sendNum');
    }

    /**
     * 通过店铺信息获取平台信息
     * @param $stores
     * @return mixed
     */
    public function getPlatCdByStore($stores)
    {
        $where[]['ID'] = ['in', $stores];
        return $this->table($this->shopTableName)->where($where)->getField('PLAT_CD,ID,STORE_NAME,MERCHANT_ID');
    }
    /**
     *获取平台数据
     */
    public function getPlatData()
    {
        $where[]['CD'] = ['like','%N00083%'];
        $where[]['CD_VAL'] = ['like','Gshopper-%'];
        return $this->table($this->dictTableName)->where($where)->getField('CD,CD_VAL');
    }

}