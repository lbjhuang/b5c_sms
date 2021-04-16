<?php

/**
 * 优惠券关联商品
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/21
 * Time: 18:13
 */
class CouponGudsModel extends RelationModel
{
    protected $trueTableName = "tb_ms_coupon_guds";
    protected $gshopperGudsTableName = "tb_ms_guds_store";
    protected $gshopperGudsOptTableName = "tb_ms_drguds_opt";

    /**
     * 添加优惠券关联商品信息
     * @param $params
     * @return mixed
     */
    public function addCouponGudsData($params)
    {
        $data['coupon_id'] = $params['couponId'];
        $data['guds_id'] = $params['gudsId'];
        $data['add_time'] = time();
        return $this->add($data);
    }

    /**
     * 批量添加优惠券关联商品信息
     * @param $params
     */
    public function batchAddCouponGudsData($params, $couponId)
    {
        $sql = "insert into " . $this->trueTableName . " (coupon_id,guds_id,add_time) values ";
        foreach ($params as $v) {
            $sql .= "('" . $couponId . "','" . $v . "','" . time() . "'),";
        }
        $sql = rtrim($sql, ',');
        $this->execute($sql);
    }

    /**
     * 获取商品信息
     * @param $params
     * @return mixed
     *
     */
    public function getGudsData($params, $type, $offset, $limit)
    {
        $where[]['gs.THIRD_GUDS_ID'] = ['exp', 'is not null'];
        !empty($params['skuId']) && $where[]['dop.SKU_ID'] = $params['skuId'];
        !empty($params['gudsId']) && $where[]['gs.THIRD_GUDS_ID'] = $params['gudsId'];
        !empty($params['gudsName']) && $where[]['gs.GUDS_NM'] = ['like',"%".$params['gudsName']."%"];
        $field = 'gs.THIRD_GUDS_ID as spuId,gs.GUDS_NM as gudsName,dop.SKU_ID as skuId';
        #$subQuery = $this->table($this->gshopperGudsTableName . " as gs")->join('LEFT JOIN ' . $this->gshopperGudsOptTableName . ' as do on gs.ID = do.GUDS_ID')->field($field)->where($where)->limit($offset, $limit)->buildSql();
        if ($type == 'count') {
            /*$sql = 'SELECT COUNT(*) as num FROM (' . $subQuery . ') AS T';
            return $this->query($sql);*/
            return $this->table($this->gshopperGudsTableName . " as gs")->join('LEFT JOIN ' . $this->gshopperGudsOptTableName . ' as dop on gs.ID = dop.GUDS_ID')->field($field)->where($where)->count();
        }
        $result = $this->table($this->gshopperGudsTableName . " as gs")->join('LEFT JOIN ' . $this->gshopperGudsOptTableName . ' as dop on gs.ID = dop.GUDS_ID')->field($field)->where($where)->limit($offset, $limit)->select();
        return $result;
    }

    /**
     * 获了商品总数
     * @param $params
     * @return mixed
     */
    public function getGudsTotal($params)
    {
        return $this->table($this->gshopperGudsTableName)->count();
    }

    /**
     * 获取商品信息方法
     */
    public function getGudsInfo($params, $type, $offset, $limit)
    {
        $where['coupon_id'] = $params['couponId'];
        !empty($params['gudsId']) && $where['guds_id'] = $params['gudsId'];
        $subQuery = $this->field('guds_id')->where($where)->buildSql();
        unset($where);
        $where[]['THIRD_GUDS_ID'] = ['exp', "in (" . $subQuery . ")"];
        !empty($params['skuId']) && $where[]['dop.SKU_ID'] = $params['skuId'];
        !empty($params['gudsName']) && $where[]['gs.GUDS_NM'] = ['like',"%".$params['gudsName']."%"];
        if ($type == 'count') {
            return $this->table($this->gshopperGudsTableName . " as gs")->join('LEFT JOIN ' . $this->gshopperGudsOptTableName . ' as dop on gs.ID = dop.GUDS_ID')->where($where)->count();
        }
        $field = 'gs.THIRD_GUDS_ID as spuId,gs.GUDS_NM as gudsName,dop.SKU_ID as skuId';
        return $this->table($this->gshopperGudsTableName . " as gs")->join('LEFT JOIN ' . $this->gshopperGudsOptTableName . ' as dop on gs.ID = dop.GUDS_ID')->field($field)->where($where)->limit($offset,$limit)->select();
    }

    /**
     * 通过优惠券id获取关联商品id
     * @return string
     */
    public function getProductIds($couponId, $field = "guds_id")
    {
        $where['coupon_id'] = $couponId;
        return $this->field($field)->where($where)->select();
    }
    /**
     * 删除 优惠券关联商品数据
     * @param $couponId
     * @return mixed
     */
    public function delGudsData($couponId)
    {
        $where['coupon_id'] = $couponId;
        return $this->where($where)->delete();
    }
}