<?php

/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 17/1/16
 * Time: 15:20
 */
class GoodsModel extends RelationModel
{
    protected $trueTableName = "tb_wms_goods";

    /* protected $_link = array(
         'Stream' => array(
             'mapping_type' => HAS_MANY,
             'class_name' => 'Stream',
             'Condition' => 'GSKU '

         ),


     );*/

    /**
     * @param $sku_arr
     * @return array
     */
    public static function sku2Spu($sku_arr)
    {
        foreach ($sku_arr as $v) {
            $spu_arr[$v] = substr($v, 0, -2);
        }
        return $spu_arr;
    }

}