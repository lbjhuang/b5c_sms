<?php
/**
 * User: yuanshixiao
 * Date: 2018/1/16
 * Time: 13:52
 */

class SpecialOfferLogic
{
    protected $error = '';
    public $imported_goods = [];

    public function importGoods($goods) {
        $currency_cd    = (new TbMsCmnCdModel())->getCdY(TbMsCmnCdModel::$currency_cd_pre);
        $currency       = [];
        foreach ($currency_cd as $v) {
            $currency[] = $v['CD_VAL'];
        }
        $skus                   = [];
        $res                    = true;
        $authorization_and_link = TbPurSpecialOfferGoodsModel::$authorization_and_link;
        foreach ($goods as $k => &$v) {
            $msg                    = '';
            $goods_info = D('Goods','Logic')->getGoods($v['gsku']);
            if ($goods_info) {
                $v['goods_name']         = $goods_info['goods_name'];
                $v['sku']                = $goods_info['sku_id'];
                $v['goods_attribute']    = $goods_info['goods_attribute'];
                $v['gudsImgCdnAddr']     = $goods_info['guds_img_cdn_addr'];
            } else {
                $msg .= '商品信息有误（商品ID重复，或商品不存在，或者为空）；';
                $res = false;
            }
            if(!in_array($v['currency'],$currency)) {
                $msg .= '币种信息有误（币种不能是模板列表以外的币种，不能为空；）';
                $res = false;
            }
            if(!is_numeric($v['price']) || $v['price']<=0) {
                $msg .= '商品价格有误（比如价格为0、为负或者为空）；';
                $res = false;
            }
            if(!(is_numeric($v['number']) && !strstr($v['number'],'.')) || $v['number']<=0) {
                $msg .= '商品数量有误（比如价格为0/小数/负数、或者为空）；';
                $res = false;
            }
            if(!in_array($v['auth_and_link'],$authorization_and_link)) {
                $msg .= '授权和链路有误（智能填写：有授权 或 无授权有链路 或无授权无链路）；';
                $res = false;
            }
            $v['msg'] = $msg;
        }
        $this->imported_goods = $goods;
        return $res;
    }

    public function getError() {
        return $this->error;
    }

}