<?php

//@import("@.Model.Orm.TbConDivisionOurCompany");
//@import("@.Model.Orm.TbConDivisionWarehouse");
//@import("@.Model.Orm.TbConDivisionClient");

//use Application\Lib\Model\Orm\TbConDivisionOurCompany;


class InventoryRepository extends Repository
{  

	public function initInveGoodsPhoto($inveId, $warehouse_cd, $otherParams)
	{
		$res = InventoryModel::getRealNumberStockInfo($otherParams['inve_sku'], $warehouse_cd, $otherParams);
		$res = $this->adjustInitData($res, $inveId);
		return M('wms_inve_guds_photo', 'tb_')->addAll($res);
	}

	public function getFinanceAudit($where)
	{
		return M('role r', 'bbm_')
			->join('left join bbm_admin_role bar ON bar.ROLE_ID = r.ROLE_ID')
			->join('left join bbm_admin ba ON ba.M_ID = bar.M_ID')
			->field('ba.M_NAME as name')
			->where($where)
			->select();	
	}

	public function getInveGoodsPhoto($where, $field)
	{
		$where['deleted_at'] = ['EXP', 'IS NULL'];
		$where['deleted_by'] = ['EXP', 'IS NULL'];
		$InventoryGoodsPhotoModel = D('warehouse/InventoryGoodsPhoto');
		return $InventoryGoodsPhotoModel->field($field)->where($where)->select();
	}
	public function adjustInitData($res, $inveId)
	{
		$data = [];
		foreach ($res as $key => $value) {
			$data[$key]['inve_id'] = $inveId;
			$data[$key]['amount_total_num'] = $value['amountTotalNum'];
			$data[$key]['amount_sale_num'] = $value['amountSaleNum'];
			$data[$key]['amount_occupied_num'] = $value['amountOccupiedNum'];
			$data[$key]['amount_locking_num'] = $value['amountLockingNum'];
			$data[$key]['created_by'] = DataModel::userNamePinyin();
			$data[$key]['sku_id'] = $value['skuId'];
			$data[$key]['goods_type_cd'] = $value['vir_type'] === 'N002440400' ? InventoryModel::GOODS_TYPE_BROKEN : InventoryModel::GOODS_TYPE_NORMAL; 
		}
		return $data;
	}

	public function inveCreate($data)
	{
		$inventoryModel = D('warehouse/Inventory');
		$inveNo = $inventoryModel->createInvNo(); // 获取盘点单号
		$data['inve_no'] = $inveNo;
		$autoData = $inventoryModel->create($data);
		return $inventoryModel->data($autoData)->add();
	}

	public function getInveBatchDetail($where, $field)
	{
		$where['wig.deleted_at'] = ['EXP', 'IS NULL'];
		$where['wig.deleted_by'] = ['EXP', 'IS NULL'];
		return M('wms_inventory wi', 'tb_')
		->join('left join tb_wms_inve_guds wig ON wig.inve_id = wi.id')
		->join('left join tb_wms_inve_guds_batch wigb ON wigb.inve_guds_id = wig.id')
		->field($field)
		->where($where)
		->select();
	}

	public function getInveSkuDetail($where, $field)
	{
		$where['wig.deleted_at'] = ['EXP', 'IS NULL'];
		$where['wig.deleted_by'] = ['EXP', 'IS NULL'];
		return M('wms_inventory wi', 'tb_')
		->join('left join tb_wms_inve_guds wig ON wig.inve_id = wi.id')
		->field($field)
		->where($where)
		->select();
	}

	public function getInveGoodsDiff($where, $field = '*')
	{
		$where['deleted_at'] = ['EXP', 'IS NULL'];
		$where['deleted_by'] = ['EXP', 'IS NULL'];
		$InventoryGoodsModel = D('warehouse/InventoryGoods');
		return $InventoryGoodsModel->field($field)->where($where)->select();
	}

	public function getInveGoodsBatchDiff($where, $field)
	{
		$where['deleted_at'] = ['EXP', 'IS NULL'];
		$where['deleted_by'] = ['EXP', 'IS NULL'];
		$InventoryGoodsModel = D('warehouse/InventoryGoodsBatch');
		return $InventoryGoodsModel->field($field)->where($where)->select();
	}

	public function getInveAudit($where, $field)
	{
		$inventoryModel = D('warehouse/InventoryAudit');
		return $inventoryModel->field($field)->where($where)->select();
	}
	
	public function getInveInfo($where, $field)
	{
		$inventoryModel = D('warehouse/Inventory');
		return $inventoryModel->field($field)->where($where)->find();
	}

	public function saveInveAudit($where, $save)
	{
		$inventoryModel = D('warehouse/InventoryAudit');
		if ($where) {
			$save['updated_by'] = DataModel::userNamePinyin();
			return $inventoryModel->where($where)->save($save);
		} else {
			$save = $inventoryModel->create($save);
			return $inventoryModel->add($save);
		}
	}

	public function saveInveGoodsBatchDiff($where, $save)
	{
		$InventoryGoodsModel = D('warehouse/InventoryGoodsBatch');
		if ($where['id']) {
			$save['updated_by'] = DataModel::userNamePinyin();
			return $InventoryGoodsModel->where($where)->save($save);
		} else {
			$save = $InventoryGoodsModel->create($save);
			return $InventoryGoodsModel->add($save);
		}
	}

	public function saveInveGoods($where, $save)
	{
		$InventoryGoodsModel = D('warehouse/InventoryGoods');
		if ($where['id']) {
			$save['updated_by'] = DataModel::userNamePinyin();
			return $InventoryGoodsModel->where($where)->save($save);
		} else {
			$save = $InventoryGoodsModel->create($save);
			return $InventoryGoodsModel->add($save);
		}
	}
	public function saveInve($where, $save)
	{
		$inventoryModel = D('warehouse/Inventory');
		$save['updated_by'] = DataModel::userNamePinyin();
		return $inventoryModel->where($where)->save($save);
	}
	private function joinDataPage($limit, $temp_model)
	{
	    $search_model = clone $temp_model;
	    $pages['total'] = $temp_model->count();
	    $pages['current_page'] = $limit[0];
	    $pages['per_page'] = $limit[1];
	    $res_db = $search_model->limit($limit[0], $limit[1])->order('created_at desc')->select();
	    return array($pages, $res_db);
	} 

	public function inveIndex($wheres, $limit)
	{
	    $temp_model = $this->model->table('tb_wms_inventory wi')
	        ->field('
	        	wi.id,
	        	wi.end_at,
	        	wi.created_at,
	        	wi.inve_no,
	        	wi.status_cd,
	        	wi.sec_status_cd,
	        	wi.created_by,
	        	wi.has_difference,
	        	wi.warehouse_cd,
	        	wi.goods_type_cd,
	        	wi.inve_sku,
	        	wi.sale_team_cd
	        	')
	        ->where($wheres);
	    list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
	    $res_db = CodeModel::autoCodeTwoVal($res_db, ['status_cd', 'sec_status_cd', 'warehouse_cd', 'sale_team_cd', 'goods_type_cd']);
	    return [$res_db, $pages];
	}

	public function getInveInfoList($where = [], $field = '*')
	{
		if (!$where) {
			return false;
		}
		return $this->model->table('tb_wms_inventory')->field($field)->where($where)->select();
	}

	public function getInveStatus($id)
	{
		if (!$id) {
			return false;
		}
		return $this->model->table('tb_wms_inventory')->where(['id' => $id])->getField('status_cd');
	}
	public function inveDelete($where, $data, $model)
	{
		return $model->table('tb_wms_inventory')->where($where)->save($data);
	}
}
