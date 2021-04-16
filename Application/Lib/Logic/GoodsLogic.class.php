<?php
/**
 * User: yuanshixiao
 * Date: 2018/1/16
 * Time: 13:52
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class GoodsLogic extends BaseLogic
{
    protected $error = '';

    public function getGoodsImg($sku_id) {
        if(is_array($sku_id)) {
            foreach ($sku_id as $v) {
                $sku_ids[] = ['sku_id'=>$v];
            }
            $goods_imgs_res = SkuModel::getInfo($sku_ids,'sku_id',['image_url']);

        }else {
            $goods_imgs_res = SkuModel::getInfo([['sku_id'=>$sku_id]],'sku_id',['image_url']);
        }
        $key = array_column($goods_imgs_res,'sku_id');
        $val = array_column($goods_imgs_res,'image_url');
        $res = array_combine($key,$val);
        return $res;
    }

    public function getGoods($search_id) {
        $goods_m                = D('Pms/PmsProductSku');
        $map['_string']         = "sku_id = '{$search_id}' or upc_id='{$search_id}' or FIND_IN_SET('{$search_id}',upc_more)";
        $map['review_states']   = 'N000420400';
        $map['sku_states']      = 1;
        $res = $goods_m
            ->join('left join product on product.spu_id=product_sku.spu_id AND product.supplier = \'N002680001\'')
            ->where($map)
            ->field('sku_id,upc_id bar_code,supplier supplier_cd')->find();
        if (empty($res)) {
            return false;
        }
        $res['supplier']    = cdVal($res['supplier_cd']);
        $res                = SkuModel::getInfo([$res],'sku_id',['spu_name','attributes','image_url'],['spu_name'=>'goods_name','attributes'=>'goods_attribute','image_url'=>'guds_img_cdn_addr'])[0];
        $standing           = M('center_stock','tb_wms_')
            ->field('sum(sale) as sale_num,sum(on_way) on_way_num')
            ->where(['SKU_ID'=>$res['sku_id']])
            ->group('SKU_ID')
            ->find();
        $res['sale_num']    = $standing['sale_num'];
        $res['on_way_num']  = $standing['on_way_num'];
        return $res;
    }

    public function getGoodsBySkuOrUpc($param) {
        $goods_m                = D('Pms/PmsProductSku');
        $map['_string']         = '';
        foreach ($param as $v) {
            if($v['sku_id']) {
                $map['_string'] .= "sku_id={$v['sku_id']} or ";
            }elseif($v['upc_id']) {
                $map['_string'] .= "upc_id={$v['upc_id']} or (FIND_IN_SET('{$v['upc_id']}',upc_more)) ";
            }else {
                $this->error = 'sku或条形码不能为空';
                return false;
            }
        }
        if(!$map['_string']) {
            $this->error = '查询条件必须';
            return false;
        }else {
            $map['_string'] = rtrim($map['_string'],' or ');
        }
        $map['review_states']   = 'N000420400';
        $map['sku_states']      = 1;
        $res = $goods_m
            ->join('left join product on product.spu_id=product_sku.spu_id')
            ->where($map)
            ->field("sku_id,IF(product_sku.upc_more, REPLACE(CONCAT_WS(',',product_sku.upc_id,product_sku.upc_more),',',',\\r\\n'), product_sku.upc_id) as upc_id")->select();
        if (empty($res)) {
            $this->error = '对应商品不存在';
            return false;
        }
        $res = SkuModel::getInfo($res,'sku_id',['spu_name','attributes','image_url']);
        return $res;
    }

    /**
     * 获取PMS中SKU配置的主图
     * @param $sku_id
     */
    public function getSkuImage($sku_id){
        $model = new  PmsBaseModel();
        $sku_image_info = $model->table('product_image')->field('image_url')->where(array('sku_id'=>$sku_id,'image_type'=>'N000080100'))->find();
        return $sku_image_info;
    }
}