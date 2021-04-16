<?php

class InventoryFixDataAction extends InventoryBaseAction
{
	public function fixGoods()
	{
		$temp['skuId'] = '8117787503';
		$ret[] = $temp;
		$ret = SkuModel::getInfo($ret, 'skuId', ['spu_name', 'image_url', 'attributes'], ['spu_name' => 'gudsName', 'image_url' => 'imageUrl', 'attributes' => 'optAttr']);
		$ret = $ret[0];
		$goods_json = M('wms_inve_guds', 'tb_')->where(['id' => '3'])->getField('goods_json');
		$goods_arr = json_decode($goods_json, true);
		$goods_arr['gudsName'] = $ret['gudsName'];
		$goods_arr['imageUrl'] = $ret['imageUrl'];
		$goods_arr['optAttr'] = $ret['optAttr'];
		$goods_arr['price'] = is_null($goods_arr['price']) ? '0' : $goods_arr['price'];
		$save['goods_json'] = json_encode($goods_arr);
		$res = M('wms_inve_guds', 'tb_')->where(['id' => '3'])->save($save);
		p($res);
		if (false === $res) {
			echo M()->_sql();
		}
		
 	}

 	public function fixWarehouseAudit()
 	{
 	    // 操作人
 	    $sql = "UPDATE tb_con_division_warehouse SET inventory_operate_by = 'Mingyuan.Kang,Jay.Chen,Bill.Mi,Mons.Gao,Haven.He' WHERE (inventory_operate_by = '' OR inventory_operate_by IS NULL)";
 	    $res = M()->query($sql);
 	    p($res);
 	    
 	    // 负责人
 	    $sql = "UPDATE tb_con_division_warehouse SET inventory_by = 'Mingyuan.Kang' WHERE (inventory_by = '' OR inventory_by IS NULL)";
 	    $res = M()->query($sql);
 	    p($res);
        
 	}
}
