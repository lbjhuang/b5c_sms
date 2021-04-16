<?php
/**
 * User: yuanshixiao
 * Date: 2017/8/15
 * Time: 14:32
 */

class TbPurSpecialOfferGoodsModel extends RelationModel
{
    protected $tablePrefix  = 'tb_pur_';
    protected $tableName    = 'special_offer_goods';

    public $_validate = [
    ];

    public static $authorization_and_link = [
        '1' => '无授权有链路',
        '2' => '无授权无链路',
        '0' => '有授权'
    ];

    public function getGoods($special_id) {
        $goods = $this->where(['special_offer_id'=>$special_id])->select();
        $goods = SkuModel::getInfo($goods,'sku_id',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'gudsImgCdnAddr','attributes'=>'goods_attribute']);
        return $goods;
    }
}