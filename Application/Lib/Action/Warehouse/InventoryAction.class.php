<?php

class InventoryAction extends InventoryBaseAction
{
	public function __construct()
	{
	    parent::__construct();
	    $this->service = new InventoryService();
	}


	public function getInveIdByFinPerson()
	{
		try {
			$request_data = DataModel::getDataNoBlankToArr();
		    $response_data = $this->service->getInveIdByFinPerson($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}
	public function getRolePerson()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->getRolePerson($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}
	public function getLog()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->getLog($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}
	public function getFinAuditType()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->getFinAuditType($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function inveCreateAdjustSheet()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
			if (!RedisModel::lock('inve_create_adjust_id' . $request_data['data']['id'], 30)) {
			    throw new Exception(L('inve_create_adjust_id' . $request_data['data']['id'] . '此按钮只需点击一次即可，无需重新点击'));
			}
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->inveCreateAdjustSheet($request_data['data']);
			RedisModel::unlock('inve_create_adjust_id' . $request_data['data']['id']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
			RedisModel::unlock('inve_create_adjust_id' . $request_data['data']['id']);
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}	

	public function getInveButtonInfo()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->getInveButtonInfo($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function inveExport()
	{
		try {
			$request_data = json_decode($_POST['export_params'], true);
			$this->checkInveGoods($request_data['data']);
		    $response_data = $this->service->inveExport($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function inveOperate()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveOperate($request_data['data']);
		    $response_data = $this->service->inveOperate($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function checkInveOperate($data)
	{
		$rules = [
			'id' => 'required|string',
		    'type' => 'required|string',
		];
		$attributes = [
			'id' => '盘点ID',
		    'type' => '操作类型'
		];
		$this->validate($rules, $data, $attributes);
	}

	public function saveInveGoodsBatchDiff()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkSaveInveGoodsBatchDiff($request_data['data']);
		    $response_data = $this->service->saveInveGoodsBatchDiff($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function checkSaveInveGoodsBatchDiff($data)
	{

	    foreach ($data as $k => $v) {
	    	foreach ($v as $key => $value) {
	    		$rules["{$k}.{$key}.inve_guds_id"] = 'required|string';
	    		$rules["{$k}.{$key}.diff_num"] = 'required|string';
	    		$rules["{$k}.{$key}.batch_id"] = 'required|string';
	    		$rules["{$k}.{$key}.price"] = 'required|string';
	    		$attributes["{$k}.{$key}.inve_guds_id"] = "盘点差异商品（SKU维度）对应的ID";
	    		$attributes["{$k}.{$key}.diff_num"] = "差异商品调整数量";
	    		$attributes["{$k}.{$key}.batch_id"] = "差异商品批次号";
	    		$attributes["{$k}.{$key}.price"] = "差异商品批次USD不含税采购单价";
	    	}
	    }

		ValidatorModel::validate($rules, $data, $attributes);
		$message = ValidatorModel::getMessage();
		if ($message && strlen($message) > 0) {
		    $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
		    foreach ($this->error_message as $key => $value) {
		        throw new Exception($value[0], 40001);
		    }
		}
	}

	public function saveInveGoodsDiff()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkSaveInveGoodsDiff($request_data['data']);
		    $response_data = $this->service->saveInveGoodsDiff($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function saveInveGoodsDiffRemark()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkSaveInveGoodsDiffRemark($request_data['data']);
		    $response_data = $this->service->saveInveGoodsDiffRemark($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}
	public function checkSaveInveGoodsDiffRemark($data)
	{

	    foreach ($data as $key => $value) {
	        $rules["{$key}.id"] = 'required|string';
	        $rules["{$key}.remark"] = 'required|string';
	        $attributes["{$key}.id"] = "盘点差异商品ID";
	        $attributes["{$key}.remark"] = "查询差异商品备注";
	    }

		ValidatorModel::validate($rules, $data, $attributes);
		$message = ValidatorModel::getMessage();
		if ($message && strlen($message) > 0) {
		    $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
		    foreach ($this->error_message as $key => $value) {
		        throw new Exception($value[0], 40001);
		    }
		}
	}
	public function checkSaveInveGoodsDiff($data)
	{

	    foreach ($data as $key => $value) {
	        $rules["{$key}.id"] = 'required|string';
	        $rules["{$key}.price"] = 'required|string';
	        $attributes["{$key}.id"] = "盘点差异商品ID";
	        $attributes["{$key}.price"] = "查询差异商品价格";
	    }

		ValidatorModel::validate($rules, $data, $attributes);
		$message = ValidatorModel::getMessage();
		if ($message && strlen($message) > 0) {
		    $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
		    foreach ($this->error_message as $key => $value) {
		        throw new Exception($value[0], 40001);
		    }
		}
	}

	// 获取差异商品信息
	public function getInveGoodsDiff()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkGetInveGoodsDiff($request_data['data']);
		    $response_data = $this->service->getInveGoodsDiff($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function checkGetInveGoodsDiff($data)
	{
		$rules = [
		    'id' => 'required|string',
		    'type' => 'required|string',
		];
		$attributes = [
		    'id' => '盘点ID',
		    'type' => '查询差异商品类型'
		];
		$this->validate($rules, $data, $attributes);
	}


	public function getInveBaseInfo()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveBaseInfo($request_data['data']);
		    $response_data = $this->service->getInveBaseInfo($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function checkInveBaseInfo($data)
	{
		$rules = [
		    'id' => 'required|string'
		];
		$attributes = [
		    'id' => '盘点ID',
		];
		$this->validate($rules, $data, $attributes);
	}

	public function inveImport()
	{
		try {
		    // $request_data = DataModel::getDataNoBlankToArr();
		    $model = new InventoryExcelModel();
		    $response_data = $model->import($_POST);
		    if ($response_data['code'] == 200) {
		    	$this->ajaxSuccess($response_data);
		    } else {
		    	$this->ajaxError($response_data['data'], L('导入失败，请先核实数据'));
		    }
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function checkGetInveAudit($data)
	{
		$rules = [
		    'inve_id' => 'required|string',
		    'type' => 'required|string'
		];
		$attributes = [
		    'inve_id' => '盘点ID',
		    'type' => '审核类型'
		];
		$this->validate($rules, $data, $attributes);
	}
	public function getInveAudit()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkGetInveAudit($request_data['data']);
		    $response_data = $this->service->getInveAudit($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function inveFinConfirm()
	{
		$trans_result = true;
        $trans = M();
        $trans->startTrans();
        try {
        	$request_data = DataModel::getDataNoBlankToArr();
        	$this->checkInveAuditConfirm($request_data['data']);
        	$response_data = $this->service->inveAuditConfirm($request_data['data']);
        } catch (Exception $ex) {
            $trans_result = false;
            Log::record("== 盘点审核更新/新增失败 ==" . $ex->__toString(), Log::ERR); 
            Log::record($ex->getMessage(), Log::SQL);
        }
        if ($trans_result === false) {
            $trans->rollback();
            $this->ajaxError($response_data, $ex->getMessage(), $ex->getCode());
        } else {
            $trans->commit();
            $this->ajaxSuccess($response_data);
        }
	}
	public function inveAuditConfirm()
	{
		$trans_result = true;
        $trans = M();
        $trans->startTrans();
        try {
        	$request_data = DataModel::getDataNoBlankToArr();
    		if (!RedisModel::lock('inve_audit_key' . $request_data['data']['inve_id'], 300)) {
    		    throw new Exception(L('inve_audit_key' . $request_data['data']['inve_id'] . '此按钮只需点击一次即可，无需重新点击'));
    		}
        	$this->checkInveAuditConfirm($request_data['data']);
        	$response_data = $this->service->inveAuditConfirm($request_data['data']);
        	RedisModel::unlock('inve_audit_key' . $request_data['data']['inve_id']);
        } catch (Exception $ex) {
            $trans_result = false;
            Log::record("== 盘点审核更新/新增失败 ==" . $ex->__toString(), Log::ERR); 
            Log::record($ex->getMessage(), Log::SQL);
            RedisModel::unlock('inve_audit_key' . $request_data['data']['inve_id']);
        }
        if ($trans_result === false) {
            $trans->rollback();
            $this->ajaxError($response_data, $ex->getMessage(), $ex->getCode());
        } else {
            $trans->commit();
            $this->ajaxSuccess($response_data);
        }
	}

	public function checkInveAuditConfirm($data)
	{
		$rules = [
		    'inve_id' => 'required|string',
		    'type' => 'required|string',
		    'remark' => 'required|string'
		];
		$attributes = [
		    'inve_id' => '盘点ID',
		    'type' => '审核类型',
		    'remark' => '审核差异结果'
		];
		$this->validate($rules, $data, $attributes);
	}

	public function inveAuditApply()
	{
		try {
			$request_data = DataModel::getDataNoBlankToArr();
    		if (!RedisModel::lock('inve_audit_apply_key' . $request_data['data']['inve_id'], 300)) {
    		    throw new Exception(L('inve_audit_apply_key' . $request_data['data']['inve_id'] . '此按钮只需点击一次即可，无需重新点击'));
    		}
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveAuditApply($request_data['data']);
		    $response_data = $this->service->inveAuditApply($request_data['data']);
		    RedisModel::unlock('inve_audit_apply_key' . $request_data['data']['inve_id']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    RedisModel::unlock('inve_audit_apply_key' . $request_data['data']['inve_id']);
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}	
	}

	public function checkInveAuditApply($data)
	{
		$rules = [
		    'inve_id' => 'required|string'
		];
		$attributes = [
		    'inve_id' => '盘点ID',
		];
		$this->validate($rules, $data, $attributes);
	}
	public function saveInveGoods()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    //$this->checkInveGoods($request_data['data']);
		    $response_data = $this->service->saveInveGoods($request_data['data']);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}	
	}

	// 获取盘点商品
	public function getInveGoods()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveGoods($request_data['search']);
		    $response_data = $this->service->getInveGoods($request_data);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	private function checkInveGoods($data)
	{
		$rules = [
		    'warehouse_cd' => 'required|string|size:10',
		];
		$attributes = [
		    'warehouse_cd' => '仓库',
		];
		$this->validate($rules, $data, $attributes);
	}

	private function checkInveGoodsBatch($data)
	{
		$rules = [
		    'warehouse_cd' => 'required|string|size:10',
		    'sku_id' => 'required|string'
		];
		$attributes = [
		    'warehouse_cd' => '仓库',
		    'sku_id' => 'SKU_ID'
		];
		$this->validate($rules, $data, $attributes);
	}


	// 获取盘点商品批次
	public function getInveGoodsBatch()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $this->checkInveGoodsBatch($request_data['search']);
		    $response_data = $this->service->getInveGoodsBatch($request_data);
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function inveIndex()
	{
	    try {
	        $request_data = DataModel::getDataNoBlankToArr();
	        $request_data = $this->checkInveIndex($request_data);
	        $response_data = $this->service->inveIndex($request_data);
	        $this->ajaxSuccess($response_data);
	    } catch (Exception $exception) {
	        $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
	    }
	}

	public function checkInveIndex($data)
	{
		if ($data['search']['sec_status_cd'] === InventoryModel::INVE_FIN_CONFIRM_NONEED) { // 无需确认时查询时需移除主状态“财务确认中”，因为页面上只有筛选该状态才允许展示“无需确认”，实际上无需确认不属于财务确认中，只属于调整差异中和已完成
			unset($data['search']['status_cd']);
		}
		return $data;
	}

	public function inveCreate()
	{
		$trans_result = true;
        $trans = M();
        $trans->startTrans();
        try {
        	$request_data = DataModel::getDataNoBlankToArr();
    		if (!RedisModel::lock('inve_create_key_' . $request_data['data']['warehouse_cd'], 300)) {
    		    throw new Exception(L('inve_create_key_' . $request_data['data']['warehouse_cd'] . '此按钮只需点击一次即可，无需重新点击'));
    		}
        	$response_data = $this->service->inveCreate($request_data['data']);
        	RedisModel::unlock('inve_create_key_' . $request_data['data']['warehouse_cd']);
        } catch (Exception $ex) {
            $trans_result = false;
            Log::record("== 盘点新增失败 ==" . $ex->__toString(), Log::ERR); 
            Log::record($ex->getMessage(), Log::SQL);
            RedisModel::unlock('inve_create_key_' . $request_data['data']['warehouse_cd']);
        }
        if ($trans_result === false) {
            $trans->rollback();
            $this->ajaxError($response_data, $ex->getMessage(), $ex->getCode());
        } else {
            $trans->commit();
            $this->ajaxSuccess($response_data);
        }	
	}

	public function getInveStatus()
	{
		try {
		    $response_data = $this->service->getStatusList();
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}

	public function getOwnWarehouse()
	{
		try {
		    $response_data = $this->service->getOwnWarehouse();
		    $this->ajaxSuccess($response_data);
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}


	public function inveDelete()
	{
		
		$trans = M();
		$trans->startTrans();
		try {
			$id = !empty($_POST['id']) ? $_POST['id']:0;
			if(empty($id)){
				throw new Exception(L('id非法'));
			}
			$inveStatus = $this->service->getInveStatus($id);

			if(!$inveStatus || !in_array($inveStatus,['N003520001','N003520002','N003520003','N003520004', 'N003520005'])){
				throw new Exception(L('盘点单状态非法'));
			}
			if ($inveStatus == 'N003520005') {
				throw new Exception(L('当前勾选盘点单已完成，不允许取消'));
			}
			
			$re = $this->service->inveDelete($id,$trans);
			
			if($re === false){
				$trans->rollback();
				throw new Exception(L('取消失败，请重试'));
			}
			$trans->commit();
			$this->ajaxSuccess([]); 
		} catch (Exception $exception) {
			$trans->rollback();
			$this->ajaxError([], $exception->getMessage(), $exception->getCode());
		}
	}
}