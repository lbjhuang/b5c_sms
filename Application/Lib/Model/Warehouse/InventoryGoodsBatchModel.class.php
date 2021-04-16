<?php
class InventoryGoodsBatchModel extends BaseModel
{

	protected $trueTableName = 'tb_wms_inve_guds_batch';
	protected $_auto = [
	    ['created_at', 'getTime', Model::MODEL_INSERT, 'callback'],
	    ['created_by', 'getLoginName', Model::MODEL_INSERT, 'callback'],
	    ['updated_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
	    ['updated_by', 'getLoginName', Model::MODEL_UPDATE, 'callback'],
	];
}