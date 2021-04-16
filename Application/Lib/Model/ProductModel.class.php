<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/9/3
 * Time: 14:06
 */
class ProductModel extends PmsBaseModel
{
    protected $trueTableName = 'product';

    /**
     * 判断是否是组合商品
     * @param $sku_or_spu
     * @return bool
     */
    public static function isGroupSku($sku_or_spu)
    {
        if (strpos($sku_or_spu, '9') === 0) {
           return true;
        }
        return false;
    }
}