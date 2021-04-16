<?php

/**
 * 优惠券关联用户
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/21
 * Time: 18:17
 */
class CouponUserModel extends RelationModel
{
    protected $trueTableName = "tb_ms_coupon_user";
    protected $userTableName = "tb_ms_thrd_cust";

    /**
     * 添加优惠券用户信息
     * @param $params
     * @return mixed
     */
    public function addCouponUserData($params)
    {
        $data['coupon_id'] = $params['couponId'];
        $data['mobile'] = $params['mobile'];
        $data['email'] = $params['email'];
        $data['add_time'] = time();
        return $this->add($data);
    }

    /**
     * 通过优惠券id获取关联用户数据
     * @param $couponId
     */
    public function getUserDataByCouponId($couponId,$field='user_email')
    {
        $where['coupon_id'] = $couponId;
        return $this->field($field)->where($where)->select();
    }

    /**
     * 批量添加用户信息
     * @param $params
     */
    public function batchAddCouponUserData($params, $couponId)
    {
        $sql = "insert into " . $this->trueTableName . " (coupon_id,user_email,add_time) values ";
        foreach ($params as $v) {
            $sql .= "('" . $couponId . "','" . $v . "','" . time() . "'),";
        }
        $sql = rtrim($sql, ',');
        $this->execute($sql);
    }

    /**
     * 获取gshopperApp用户
     * @param $params
     * @return mixed
     */
    public function getGshopperUserData($params, $type, $offset, $limit)
    {
        $where = [];
        !empty($params['plat']) && $where[]['tc.parent_plat_cd'] = ['in', $params['plat']];
        !empty($params['email']) && $where[]['tc.CUST_EML'] = $params['email'];
        !empty($params['nickname']) && $where[]['tc.CUST_NICK_NM'] = $params['nickname'];
        !empty($params['mobile']) && $where[]['tc.origin_phone'] = $params['mobile'];
        if ($type == 'count') {
            return $this->table($this->userTableName.' as tc')->where($where)->count();
        }
        $field = 'tc.parent_plat_cd,tc.CUST_NICK_NM as nickName,tc.CUST_EML as email,tc.CUST_ID as userId,tc.origin_phone as mobile';
        return $this->table($this->userTableName . ' as tc')->field($field)->where($where)->limit($offset, $limit)->select();
    }

    /**
     * 获取用户总数
     * @param $params
     * @return mixed
     */
    public function getGshopperUserTotal($params)
    {
        $where['parent_plat_cd'] = ['in', $params['plat']];
        return $this->table($this->userTableName)->where($where)->count();
    }

    /**
     * 通过一定条件获取用户CUST_ID
     * @param $where
     * @param string $field
     * @return mixed
     */
    public function getUserCustIdByCondition($where, $field = "CUST_ID")
    {
        return $this->table($this->userTableName)->field($field)->where($where)->select();
    }

    /**
     * 通过email 或 mobile 获取用数据
     * @param $email
     * @param $mobile
     * @return mixed
     */
    public function getGshopperUserDataByEmailOrMobile($plat,$email,$mobile)
    {
        $field = 'tc.CUST_NICK_NM as nickName,tc.CUST_EML as email,tc.CUST_ID as userId,tc.origin_phone as mobile,tc.parent_plat_cd';
        $where  = $where1 = [];
        !empty($plat) && $where['tc.parent_plat_cd'] = ['in', $plat];
        !empty($email) && $where1['tc.CUST_EML'] = ['in',$email];
        !empty($mobile) && $where1['tc.origin_phone'] = ['in',$mobile];
        if(!empty($email) && !empty($mobile))
        {
            $where1['_logic'] = 'OR';
        }
        $where = array($where,$where1);
        return $this->table($this->userTableName . ' as tc')->field($field)->where($where)->select();
    }

    /**
     * 获取用户信息方法
     */
    public function getUserInfo($params,$type,$offset,$limit)
    {
        $where['coupon_id'] = $params['couponId'];
        !empty($params['email']) && $where['user_email'] = $params['email'];
        $subQuery = $this->field('user_email')->where($where)->buildSql();
        unset($where);
        $where[]['tc.CUST_EML'] = ['exp',"in (".$subQuery.")"];
        !empty($params['nickname']) && $where[]['tc.CUST_NICK_NM'] = $params['nickname'];
        !empty($params['mobile']) && $where[]['tc.origin_phone'] = $params['mobile'];
        if ($type == 'count') {
            return $this->table($this->userTableName . ' as tc')->join($this->dictTableName . ' as cc on tc.parent_plat_cd = cc.CD ')->where($where)->count();
        }
        $field = 'tc.CUST_NICK_NM as nickName,tc.CUST_EML as email,tc.CUST_ID as userId,tc.origin_phone as mobile,tc.parent_plat_cd';
        return $this->table($this->userTableName . ' as tc')->field($field)->where($where)->limit($offset,$limit)->select();
    }

    /**
     * 删除 优惠券关联用户数据
     * @param $couponId
     * @return mixed
     */
    public function delUserData($couponId)
    {
        $where['coupon_id'] = $couponId;
        return $this->where($where)->delete();
    }
}