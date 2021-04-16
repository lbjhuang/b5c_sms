<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/15
 * Time: 14:14
 */

class GoodsAction extends Action
{
    /**
     * 由/home/stock/searchguds 修改精简而来
     */
    public function get_goods() {
        $search_id  = I('request.search_id');
        $res        = D('Goods','Logic')->getGoods($search_id);
        if($res) {
            //#10800-供应链管理商品图片显示优化
            $sku_image_info =  D('Goods','Logic')->getSkuImage($search_id);
            if ($sku_image_info){
                $res['guds_img_cdn_addr'] = $sku_image_info['image_url'];
            }
            $this->ajaxReturn(['data'=>$res, 'msg'=>'success', 'code'=>2000]);
        }else {
            $this->ajaxReturn(['data'=>[], 'msg'=>'无可用商品信息', 'code'=>3000]);
        }
    }

    public function goods_batch() {
        $sku        = I('request.sku_id');
        $sell_team  = I('request.sell_team');
        $res        = D('TbWmsBatch')->getBatch($sku,$sell_team);
        $this->ajaxReturn(['data'=>$res, 'msg'=>'success', 'code'=>2000]);
    }
}