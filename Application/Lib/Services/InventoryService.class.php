<?php

class InventoryService extends Service
{
	public function __construct()
	{
	    $this->repository = new InventoryRepository();
	}


	public function addLog($id, $msg)
	{
		$insertOneResult = MongoDbModel::client()->insertOne('tb_wms_inve_logs', [
		            'inve_id' => $id,
		            'msg' => $msg,
		            'time' => date('Y-m-d H:i:s', time()),
		            'user' => DataModel::userNamePinyin()
		 ]);
	}

	public function getInveIdByFinPerson($person)
	{
		// 获取财务确认中的盘点单
		// 进一步筛选，根据盘点单的商品价格获取对应的类型
		// 返回盘点id列表
		$where['status_cd'] = InventoryModel::INVE_FIN_CONFIRMING;
		$list = $this->repository->getInveInfoList($where, 'id');
		if ($list) {
			$inveIdArr = [];
			$list = array_column($list, 'id');
			foreach ($list as $key => $value) {
				$data = [];
				$data['inve_id'] = $value;
				list($amount, $type) = $this->getDiffAmount($value);
				$authPeople = $this->getFinAuditByType($type, $data);
				if (in_array($person, $authPeople)) {
					$inveIdArr[] = $value;
				}
			}
			return $inveIdArr ? implode("','", $inveIdArr) : $inveIdArr;
		} else {
			return [];
		}
	}

	public function getLog($data)
	{
		$res = MongoDbModel::client()->find('tb_wms_inve_logs',['inve_id'=> $data['id']]);
		$returnList = [];
		foreach ($res as $key => $value) {
			$returnList[$key]['inve_id'] = $value['inve_id'];
			$returnList[$key]['msg'] = $value['msg'];
			$returnList[$key]['time'] = $value['time'];
			$returnList[$key]['user'] = $value['user'];
		}
		return $returnList;
	}

	public function inveCreateAdjustSheet($data)
	{
		$res = true;
		// 盘点单是否已初始化生成调整表，标记下
		$has_create_diff = $this->repository->getInveInfo(['id' => $data['id']], 'has_create_diff')['has_create_diff'];
		if (!$has_create_diff) {
	        $trans = M();
	        $trans->startTrans();
	 		$resCreate = $this->repository->saveInve(['id' => $data['id']], ['has_create_diff' => '1']);
	 		if ($resCreate === false) {
	 			$trans->rollback();
	 			throw new Exception("保存变更盘点生成调整表数据失败");	
	 		}
	 		// 如果有盘亏，盘亏初始化数据
	 		$result = $this->repository->getInveSkuDetail(['wig.inve_id' => $data['id'], 'wig.type' => '2'],'wig.sku_id, wi.warehouse_cd, wig.diff_num, wig.id as inve_guds_id, wig.goods_type_cd');
	 		$model = new StandingExistingModel();
	 		$goodsType = InventoryModel::$goodsType;
	 		if ($result) {
	 			foreach ($result as $key => $value) {
	 				$params = []; $response = []; 
	 				$params['warehouse'][0] = $value['warehouse_cd'];
	 				$params['mixedCode'] = $value['sku_id'];
	 				$params['orderType'] = 'asc'; // 按批次最小的正序排列
	 				$params['productType'] = $goodsType[$value['goods_type_cd']]; // 商品类型
	 				$response = $model->getInveBatchData($params);
	 				if (!$response) {
	 					$trans->rollback();
	 					throw new Exception("该SKU{$value['sku_id']}，仓库CD为{$value['warehouse_cd']}的批次信息获取失败");
	 				}		
	 				$diff_num = $value['diff_num'];
	 				foreach ($response as $k => $v) {
	 					$temp = []; 
	 					$temp['batch_id'] = $v['batchId'];
	 					$temp['price'] = $v['unitPriceUsdNoTax'];
	 					$temp['inve_guds_id'] = $value['inve_guds_id'];
	 					$temp['type'] = '2'; // 目前只要盘亏初始化
	 					if ($diff_num - $v['amountSaleNum'] <= 0) {
	 						$temp['diff_num'] = $diff_num;
	 						if (false === $this->repository->saveInveGoodsBatchDiff('', $temp)) {
	 							$trans->rollback();
	 							$tempData['lastsql'] = M()->_sql();
	 							$tempData['dbErr'] = M()->getDbError();
	 							$tempData['msg'] = "生成初始化批次数据失败1";
	 							ELog::add($tempData, ELog::ERR);
	 							throw new Exception($tempData['msg']);
	 						}
	 						break;
	 					}
	 					$diff_num = $diff_num - $v['amountSaleNum'];
	 					$temp['diff_num'] = $v['amountSaleNum'];
	 					if (false === $this->repository->saveInveGoodsBatchDiff('', $temp)) {
	 						$trans->rollback();
	 						$tempData['lastsql'] = M()->_sql();
	 						$tempData['dbErr'] = M()->getDbError();
	 						$tempData['msg'] = "生成初始化批次数据失败2";
	 						ELog::add($tempData, ELog::ERR);
	 						throw new Exception($tempData['msg']);
	 					}
	 				}
	 			}
	 		}
			$trans->commit();
		}
		// 日志记录
		$this->addLog($data['id'], '生成调整表');
		return $res;
	}

	public function getInveButtonInfo($data)
	{
		// 是否已点击生成调整表
		$has_create_diff = $this->repository->getInveInfo(['id' => $data['id']], 'has_create_diff')['has_create_diff'];
		$returnData['create_diff_need_show'] = $has_create_diff ? 'N' : 'Y';
		// 是否已经盘盈操作过
		//该盘点id下所有盘盈type=1下是否还有status为0的，有则还需要调整价格和盘盈入库，否则需要隐藏按钮
		$has_inve_profit = $this->repository->getInveGoodsDiff(['inve_id' => $data['id'], 'status' => '0', 'type' => '1'], 'id');
		$returnData['inve_profit_need_show'] = 'N';
		if ($has_inve_profit) {
			$returnData['inve_profit_need_show'] = 'Y';
		}
		return $returnData;
	}

	public function inveExport($data)
	{
		$inve_export_l    = D('Warehouse/ExportInve','Logic');
		$param['warehouse'][] = $data['warehouse_cd']; 
		$param['mixedCode'] = $data['inve_sku'];
		$param['productType'] = $data['goods_type_cd'];
		if ($data['sale_team_cd']) {
			$param['saleTeam'][] = $data['sale_team_cd']; // 销售团队
		}
		$inve_export_l->exportInveGoods($param);
	}

	public function inveOperate($data)
	{
		$res = false;
		$trans_result = true;
        $trans = M();
        $trans->startTrans();
		switch ($data['type']) {
			case '1': // 盘盈
				$trans_result = $this->inveProfitOper($data);
				$msg = "盘盈入库";
				break;
			case '2': // 盘亏
				$trans_result = $this->inveLossOper($data);
				$msg = "盘亏出库";
				break;
			default:
				break;
		}
		$res = $this->checkInveFinishStatus($data);
		$inveLossAutoRes = $this->checkInveLossAutoRes($data); // 检查盘亏是否已经完成，没完成的需要初始化批次数据
		if ($trans_result === false || $res === false || $inveLossAutoRes === false) {
		    $trans->rollback();
		} else {
		    $trans->commit();
		}
		$this->addLog($data['id'], $msg);
		return $trans_result;
	}

	private function checkInveLossAutoRes($data)
	{
		$res = true;
		if ($data['type'] == '2') {
			// 获取盘亏中尚未调整完成的SKU，进行批次推荐数量初始化
			$inveLossInfo = $this->repository->getInveSkuDetail(['wig.inve_id' => $data['id'], 'wig.type' => '2', 'wig.status' => '0'],'wig.sku_id, wi.warehouse_cd, wig.diff_num, wig.id as inve_guds_id');
			$model = new StandingExistingModel();
			foreach ($inveLossInfo as $key => $value) {
				$params = []; $response = []; 
				$params['warehouse'][0] = $value['warehouse_cd'];
				$params['mixedCode'] = $value['sku_id'];
				$params['orderType'] = 'asc'; // 按批次最小的正序排列
				$response = $model->getBatchData($params);
				if (!$response) {
					$res = false;
					throw new Exception("该SKU{$value['sku_id']}，仓库CD为{$value['warehouse_cd']}的批次信息获取失败");
					break;
				}		
				$diff_num = $value['diff_num'];
				foreach ($response as $k => $v) {
					$temp = []; $inveGudsBatchId = '';
					$temp['batch_id'] = $v['batchId'];
					$temp['price'] = $v['unitPriceUsdNoTax'];
					$temp['inve_guds_id'] = $value['inve_guds_id'];
					$temp['type'] = '2'; // 目前只要盘亏初始化
					$inveGudsBatchId = $this->repository->getInveGoodsBatchDiff(['batch_id' => $v['batchId'], 'inve_guds_id' => $value['inve_guds_id'], 'type' => '2'], 'id')[0]['id'];
					if ($diff_num - $v['amountSaleNum'] <= 0) {
						$temp['diff_num'] = $diff_num;
						if (false === $this->repository->saveInveGoodsBatchDiff(['id' => $$inveGudsBatchId], $temp)) {
							$res = false;
							$tempData['lastsql'] = M()->_sql();
							$tempData['dbErr'] = M()->getDbError();
							$tempData['msg'] = "生成初始化批次数据失败3";
							ELog::add($tempData, ELog::ERR);
							throw new Exception($tempData['msg']);
						}
						break;
					}
					$diff_num = $diff_num - $v['amountSaleNum'];
					$temp['diff_num'] = $v['amountSaleNum'];
					if (false === $this->repository->saveInveGoodsBatchDiff(['id' => $$inveGudsBatchId], $temp)) {						
						$res = false;
						$tempData['lastsql'] = M()->_sql();
						$tempData['dbErr'] = M()->getDbError();
						$tempData['msg'] = "生成初始化批次数据失败4";
						ELog::add($tempData, ELog::ERR);
						throw new Exception($tempData['msg']);
					}
				}				
			}
		}
		return $res;
	}

	public function checkInveFinishStatus($data)
	{
		// 该盘点单SKU列表是否全部都已经调整完毕
		$resu = true;
		$res = $this->repository->getInveGoodsDiff(['status' => '0', 'inve_id' => $data['id']]);
		if (empty($res)) { // 表明已经全部调整完毕，则更改总的盘点状态和结束时间
			$resu = $this->repository->saveInve(['id' => $data['id']], ['status_cd' => InventoryModel::INVE_DONE, 'end_at' => date('Y-m-d H:i:s', time())]);
			if ($resu === false) {
				$tempData['lastsql'] = M()->_sql();
				$tempData['dbErr'] = M()->getDbError();
				$tempData['msg'] = "盘点单更新完结状态和完结时间失败";
				ELog::add($tempData, ELog::ERR);
				throw new Exception($tempData['msg']);
			}
		}
		return $resu;
	}

	public function inveProfitOper($data)
	{
		// 3.SKU差异值扣减
		// 2.组装入库接口数据格式
    	// 1.调用EXCEL入库接口
		$resData = [];
        $field = "wi.warehouse_cd, wig.sku_id, wig.diff_num as sku_diff_num, wig.id, wi.status_cd, wi.inve_no, wig.tax_free_pur_unit_price, wig.goods_json, wig.goods_type_cd";
        $where['wig.inve_id'] = $data['id'];
        $where['wig.type'] = '1';
        $resTempData = $this->repository->getInveSkuDetail($where, $field);
        if (!$resTempData) {
        	throw new Exception("该盘点单号没有需要盘盈入库的商品");
        }
        foreach ($resTempData as $key => $value) {
        	if ($value['status_cd'] !== InventoryModel::INVE_IN_ADJUST) { // 判断是否可以出库，根据状态 
        		$trans_result = false;
        		throw new Exception("盘点单号{$value['inve_no']}状态不是差异调整中，不允许盘盈入库");
        	}
        }
        foreach ($resTempData as $key => $value) {
        	// SKU差异值扣减
        	$res = false; $save = [];
        	$save['diff_num'] = '0';
        	$save['status'] = '1';
        	$res = $this->repository->saveInveGoods(['id' => $value['id']], $save);
        	if (false === $res) {
        		$tempData = $value;
        		$tempData['save'] = $save;
        		$tempData['dbErr'] = M()->getDbError();
        		$tempData['msg'] = "盘盈操作-差异商品SKU数量扣减失败";
        		ELog::add($tempData, ELog::ERR);
        		$trans_result = false;
        		throw new Exception($tempData['msg']);
        	}
        }
        $inveInData = $this->adjustStorageInData($resTempData);
        $re = false;
        $re = (new WmsModel())->invInStorage($inveInData);
        if (!$re || $re['code'] != 2000) {
        	$trans_result = false;
        	throw new Exception($re['msg']);
        }
        return $trans_result;
	}

	public function adjustStorageInData($data)
	{
		$tempData = [];
		$today = date('Y-m-d', time());
		$now = date('Y-m-d H:i:s', time()); // 采购入库时间，入库时间都统一默认为今天
		$ret = BaseModel::exchangeRate(InventoryModel::INVE_MONEY_CURRENCY, $now);
		$cnyRate = $ret['cny']; 
		if (!$cnyRate) {
			throw new Exception("盘盈入库失败-今日汇率获取失败");
		}
		foreach ($data as $key => $v) {
			$bill = []; $goodsType = '';
			$goodsType = InventoryModel::INVE_STOCK_TYPE;
			if ($v['goods_type_cd'] == InventoryModel::GOODS_TYPE_BROKEN) {
				$goodsType = InventoryModel::INVE_STOCK_TYPE_BROKEN;
			}
			$vv = json_decode($v['goods_json'], true);
			$bill['bill'] = [
			    'billType' => InventoryModel::INVE_STORAGE,
			    'relationType' => InventoryModel::INVE_IN_OUT_STORAGE_TYPE,
			    'warehouseId' => $v['warehouse_cd'],
			    'saleTeam' => $vv['sale_team_cd'],
			    // 'virType' => InventoryModel::INVE_STOCK_TYPE,
			    'virType' => $goodsType,
			    'processOnWay' => 0,
			    'operatorId' => $_SESSION['userId'],
			    'linkBillId' => '', // linkBillId貌似保存不起效果，用procurementNumber传一次
			    'type' => 1,
			    'procurementNumber' => $v['inve_no'],
			    'saleNo' => '',
			    'supplier' => '',
			    'spTeamCd' => $vv['sp_Team_cd'],
			    'conCompanyCd' => $vv['con_company_cd'],
			    'warehouseRule' => '',
			    'orderId' => guid(),
			    'purchaseOrderNo' => '',
			    'channel' => 'N000830100',
			    'billState' => 1,
			    'introTeam' => '',
			    'introTeamType' => '',
			    'excelName' => '',
			];
		    $bill['guds'][] = [
		        'gudsId' => SkuModel::getSpuId($v['sku_id']),
		        'smallSaleTeamCode' => $vv['small_sale_team_cd'],
		        'skuId' => $v['sku_id'],
		        'num' => (int)$v['sku_diff_num'],
		        'brokenNum' => 0,
		        'inStorageTime' => $today,
		        'deadlineDateForUse' => '',
		        'purStorageDate' => $now,
		        'currencyId' => InventoryModel::INVE_MONEY_CURRENCY,
		        'price' => $v['tax_free_pur_unit_price'] ? $v['tax_free_pur_unit_price'] : 0,
		        'currencyTime' => $today . ' 00:00:00',
		        'unitPrice' => $v['tax_free_pur_unit_price'] * $cnyRate ? $v['tax_free_pur_unit_price'] * $cnyRate : 0,
		        'unitPriceUsd' => $v['tax_free_pur_unit_price'] ? $v['tax_free_pur_unit_price'] : 0,
		        'storageLogCost' => 0,
		        'logServiceCost' => 0,
		        'carryCost' => 0,
		        'allStorageLogCost' => 0,
		        'allLogServiceCost' => 0,
		        'allCarryCost' => 0,
		        'logCurrency' => InventoryModel::INVE_MONEY_CURRENCY,
		        'logServiceCostCurrency' => InventoryModel::INVE_MONEY_CURRENCY,
		        'purInvoiceTaxRate' => $vv['pur_invoice_tax_rate'],
		        'proportionOfTax' => $vv['proportion_of_tax'],
		        'poCurrency' => '',
		        'poCost' => '',
		        'channel' => '',
		        'channelSkuId' => 0,
		        'importRemark' => '',
		    ];
			$tempData[] = $bill;
		}
		return $tempData;
	}


	public function inveLossOper($data)
	{
    	// 2.批次调整值进行扣减
    	// 4.批次金额在原来基础上累加
    	// 3.SKU差异值扣减
    	// 出库
		$resData = [];
    	$field = "wi.warehouse_cd, wig.sku_id, wig.diff_num as sku_diff_num, wigb.diff_num, wigb.batch_id, wig.id, wigb.amount, wigb.price, wi.status_cd, wi.inve_no, wigb.id as inve_guds_batch_id";
    	$where['wig.inve_id'] = $data['id'];
    	$where['wig.type'] = '2';
    	$where['wigb.diff_num'] = array('neq', 0);
    	$resTempData = $this->repository->getInveBatchDetail($where, $field);
    	if (!$resTempData) {
    		throw new Exception("该盘点单号暂无盘亏出库数据");
    	}
    	foreach ($resTempData as $key => $value) {
    		if ($value['status_cd'] !== InventoryModel::INVE_IN_ADJUST) { // 判断是否可以出库，根据状态 
    			$trans_result = false;
    			throw new Exception("盘点单号{$value['inve_no']}状态不是差异调整中，不允许盘亏出库");
    		}
    		$resData[$value['id']][] = $value;
    	}
    	foreach ($resData as $k => $v) {
    		$descNum = 0;
    		foreach ($v as $key => $value) {
    			$save = []; $re = false;
    			$save['amount'] = $value['amount'] + $value['diff_num'] * $value['price'];
    			$save['diff_num'] = '0';
    			$re = $this->repository->saveInveGoodsBatchDiff(['id' => $value['inve_guds_batch_id']], $save);
    			if (false === $re) {
    				$tempData = $value;
    				$tempData['id'] = $value['id'];
    				$tempData['save'] = $save;
    				$tempData['dbErr'] = M()->getDbError();
    				$tempData['msg'] = "盘亏操作-差异商品批次数量扣减失败";
    				ELog::add($tempData, ELog::ERR);
    				$trans_result = false;
    				throw new Exception($tempData['msg']);
    			}
    			$descNum += $value['diff_num'];
    			$skuDiffNum = $value['sku_diff_num'];
    		}
    		$leftNum = $skuDiffNum - $descNum;
    		if ($leftNum < 0) {
    			$trans_result = false;
    			throw new Exception("SKU差异值扣减小于批次差异扣减值总和");
    		}
    		$saveInvGoods = [];
    		$saveInvGoods['diff_num'] = $leftNum;
    		if ($leftNum == 0) {
    			$saveInvGoods['status'] = '1'; // 表明该SKU已经差异调整完毕
    		}
    		$res = false;
    		$res = $this->repository->saveInveGoods(['id' => $k], $saveInvGoods);
    		if (false === $res) {
    			$tempData = $v;
    			$tempData['id'] = $k;
    			$tempData['save'] = $saveInvGoods;
    			$tempData['dbErr'] = M()->getDbError();
    			$tempData['msg'] = "盘亏操作-差异商品SKU数量扣减失败";
    			ELog::add($tempData, ELog::ERR);
    			$trans_result = false;
    			throw new Exception($tempData['msg']);
    		}
    	}
    	$inveOutData = $this->getVirTypeInfo($resTempData);
    	$inveOutData = $this->adjustStorageData($inveOutData);
    	$re = false;
    	$re = (new WmsModel())->invOutStorage($inveOutData);
    	if (!$re || $re['code'] != 2000) {
    		$trans_result = false;
    		throw new Exception($re['msg']);
    	}
        return $trans_result;
	}

	private function getVirTypeInfo($data)
	{
		$tempData = [];
		$batch_m = M('wms_batch', 'tb_');
		foreach ($data as $key => $value) {
			if (empty($value['batch_id'])) {
				continue;
			}
			$tempData[$key] = $value;
			$tempData[$key]['virtype'] = $batch_m->where(['id' => $value['batch_id']])->getField('vir_type');
		}
		return $tempData;
	}

	public function adjustStorageData($data)
	{
		$res = [];
		foreach ($data as $key => $value) {
			$tempData = [];
			$tempData['operatorId'] = DataModel::userId();
			$tempData['billType'] = InventoryModel::INVE_OUTGOING;
			$tempData['deliveryWarehouse'] = $value['warehouse_cd'];
			$tempData['virtype'] = $value['virtype'];
			$tempData['skuId'] = $value['sku_id'];
			$tempData['num'] = $value['diff_num'];
			$tempData['batchId'] = $value['batch_id'];
			$tempData['orderId'] = $value['inve_no'];
			$tempData['relationType'] = InventoryModel::INVE_IN_OUT_STORAGE_TYPE;
			$res[] = $tempData;
		}
		return $res;
	}

	public function saveInveGoodsBatchDiff($data)
	{
		foreach ($data as $k => $v) {
			foreach ($v as $key => $value) {
				$tempValue = [];
				$tempValue['inve_guds_id'] = $value['inve_guds_id'];
				$tempValue['type'] = '2';
				$tempValue['diff_num'] = $value['diff_num'];
				$tempValue['batch_id'] = $value['batch_id'];
				$tempValue['price'] = $value['price'];
				if (!$value['id']) {
					$value['id'] = $this->repository->getInveGoodsBatchDiff(['batch_id' => $value['batch_id'], 'inve_guds_id' => $value['inve_guds_id'], 'type' => '2'],'id')[0]['id'];
				}
				if(false === $this->repository->saveInveGoodsBatchDiff(['id' => $value['id']], $tempValue)) {
					$tempData = $value;
					$tempData['dbErr'] = M()->getDbError();
					$tempData['msg'] = "保存盘亏商品批次{$value['batch_id']}失败{$value['inve_guds_id']}";
					ELog::add($tempData, ELog::ERR);
					throw new Exception($tempData['msg']);
				}
			}
		}	
		return true;
	}
	public function saveInveGoodsDiff($data)
	{
		foreach ($data as $key => $value) {
			$res = false;
			$res = $this->repository->saveInveGoods(['id' => $value['id']], ['tax_free_pur_unit_price' => $value['price']]);
			if (false === $res) {
				$tempData = $value;
				$tempData['dbErr'] = M()->getDbError();
				$tempData['msg'] = "保存盘盈商品{$value['id']}价格{$value['price']}失败";
				ELog::add($tempData, ELog::ERR);
				throw new Exception($tempData['msg']);
			}
		}
		return true;
	}

	public function saveInveGoodsDiffRemark($data)
	{
		foreach ($data as $key => $value) {
			$res = false;
			$res = $this->repository->saveInveGoods(['id' => $value['id']], ['remark' => $value['remark']]);
			if (false === $res) {
				$tempData = $value;
				$tempData['dbErr'] = M()->getDbError();
				$tempData['msg'] = "保存差异商品{$value['id']}备注信息{$value['remark']}失败";
				ELog::add($tempData, ELog::ERR);
				throw new Exception($tempData['msg']);
			}
		}
		return true;
	}

	public function getInveGoodsDiff($data)
	{
		$where = $this->getWhereMap($data);
		$resData = $this->repository->getInveGoodsDiff($where);
		$resData = $this->getRealNumberInfo($resData, $data);
		$resData = $this->adjustGoodsData($resData);
		return $this->adjustInveGoodsDiff($resData, $data['type']);
	}
	

	// 获取SKU实时的在库库存，可售库存，占用库存等
	public function getRealNumberInfo($reData, $data)
	{
		$resData = [];
		$inveInfoRes = $this->repository->getInveInfo(['id' => $data['id']], 'warehouse_cd, sale_team_cd, goods_type_cd');
		$otherParams['sale_team_cd'] = $inveInfoRes['sale_team_cd'];
		$otherParams['goods_type_cd'] = $inveInfoRes['goods_type_cd'];
		foreach ($reData as $key => $value) {
			$response = $goodsArr = [];
			$otherParams['goods_type_cd'] = $value['goods_type_cd'];
			$response = InventoryModel::getRealNumberStockInfo($value['sku_id'], $inveInfoRes['warehouse_cd'], $otherParams)[0];
			$goodsArr = json_decode($value['goods_json'], true);
			$goodsArr['amountTotalNum'] = !empty($value['is_new']) ? 0 : $response['amountTotalNum'] ? $response['amountTotalNum'] : '0';
			$goodsArr['amountSaleNum'] = !empty($value['is_new']) ? 0 : $response['amountSaleNum'] ? $response['amountSaleNum'] : '0';
			$goodsArr['amountOccupiedNum'] = !empty($value['is_new']) ? 0 : $response['amountOccupiedNum'] ? $response['amountOccupiedNum'] : '0';
			$goodsArr['amountLockingNum'] = !empty($value['is_new']) ? 0 : $response['amountLockingNum'] ? $response['amountLockingNum'] : '0';
			$resData[$key] = $value;
			$resData[$key]['origin_num'] = !empty($value['is_new']) ? 0 : $value['origin_num'];
			$resData[$key]['goods_json'] = json_encode($goodsArr); // 放到goods_json里面
		}
		return $resData;
	}

	public function adjustGoodsData($data)
	{
		$tempData = [];
		$status = InventoryModel::$status;
		$type = InventoryModel::$type;
		foreach ($data as $key => $value) {
			$diffNum = 0;
			$tempData[$key] = $value;
			$tempData[$key]['inve_result'] = $type[$value['type']] . "（{$status[$value['status']]}）";
			$diffNum = $value['origin_num'] - $value['actual_num'];
			$tempData[$key]['diff_amount'] = $value['tax_free_pur_unit_price'] * abs($diffNum);
			$tempData[$key]['goods_type_cd_val'] = $value['goods_type_cd'] ? cdVal($value['goods_type_cd']) : '';
		}
		return $tempData;
	}

	public function adjustInveGoodsDiff($data, $type)
	{
		$adjustData = []; $adjustDataProfit = []; $adjustDataLoss = [];
		foreach ($data as $key => $value) {
			$adjustData[$key] = array_merge((array)$value, (array)json_decode($value['goods_json']));
			if ($type == '5') {
				if ($value['type'] == '1') {
					$adjustDataProfit[] = $adjustData[$key];
				}
				if ($value['type'] == '2') {
					$adjustDataLoss[] = $adjustData[$key];
				}
			}
		}
		if ($type == '5') {
			$adjustDataNew['all'] = $adjustData;
			$adjustDataNew['profit'] = $adjustDataProfit;
			$adjustDataNew['loss'] = $adjustDataLoss;
			return $adjustDataNew;
		}
		return $adjustData;
	}

	public function getWhereMap($data)
	{
		$map = [];
		switch ($data['type']) {
			case '1': // 盘点-盘点差异商品信息展示
				$map['inve_id'] = $data['id'];
				break;
			case '2': // 盘点-盘点盘亏商品调整信息展示
				$map['inve_id'] = $data['id'];
				$map['type'] = '2';
				break;
			case '3': // 盘点-盘点盘盈商品调整信息展示
				$map['inve_id'] = $data['id'];
				$map['type'] = '1';
				break;
			case '4': // 盘点-盘点盘盈EXCEL差异商品信息展示
				$map['inve_id'] = $data['id'];
				$map['type'] = '1';
				$map['is_new'] = '1';
				break;
			case '5': // 盘点-盘点差异商品信息展示 + 单独盘盈 + 单独盘亏
				$map['inve_id'] = $data['id'];
				break;
			default:
				# code...
				break;
		}
		return $map;
	}

	public function getInveAudit($data)
	{
		$field = 'remark,type,inve_id, created_by as name';
		if ($data['type'] == '6') { // 获取全部
			unset($data['type']);
		}
		return $this->repository->getInveAudit($data, $field);
	}

	public function adjustBaseData($data)
	{
		foreach ($data as $key => $value) {
			if ($key == 'inve_sku') {
				$data[$key] = $value ? $value : '全部SKU';
			}
			if ($key == 'sale_team_cd_val') {
				$data[$key] = $value ? $value : '全团队';
			}
		}
		return $data;
	}
	public function getInveBaseInfo($data)
	{
		$field = 'status_cd, sec_status_cd, warehouse_cd, id, inve_no, created_by, created_at, end_at, sale_team_cd, goods_type_cd, inve_sku';
		$res_db = $this->repository->getInveInfo(['id' => $data['id']], $field);
		$res_db = CodeModel::autoCodeOneVal($res_db, ['status_cd', 'warehouse_cd', 'sec_status_cd', 'sale_team_cd', 'goods_type_cd']);
		$res_db = $this->adjustBaseData($res_db);
		return $res_db;
	}
	public function getDiffAmount($inveId)
	{
		$type = '';
		$info = $this->repository->getInveGoodsDiff(['inve_id' => $inveId], 'origin_num,actual_num,tax_free_pur_unit_price');
		$amount = 0;
		foreach ($info as $key => $value) {
			$amount =  bcadd($amount, bcmul(abs($value['origin_num'] - $value['actual_num']), $value['tax_free_pur_unit_price'], 8), 2);
		}
		$res = bccomp($amount, 5000, 4); // 判断是否大于5000
		if ($res === 1) { // 等于1表示大于5000
			$type = '3';
		} else {
			$re = bccomp($amount, 500, 4); // 判断是否大于500
			if ($re === 1) { // 大于500
				$type = '2';
			} else {
				$type = '1';
			}
		}
		return [$amount, $type];
	}

	public function getFinAuditByType($type, $data)
	{
		$people = ''; $peopleName = '';
		switch (strval($type)) {
			case '1': // 小于等于500 高级财务专员
				$where['r.ROLE_NAME'] = ['eq', '高级财务专员'];
				$people = $this->repository->getFinanceAudit($where);
				break;
			case '2': // 大于500，小于等于5000 财务经理
				$where['r.ROLE_NAME'] = ['eq', '财务经理'];
				$people = $this->repository->getFinanceAudit($where);
				break;
			case '3': // 大于5000 财务总监 高级财务总监
				if (!empty($data['id'])) {
					$inveId = $data['id'];
				}
				if (!empty($data['inve_id'])) {
					$inveId = $data['inve_id'];
				}
				if (!$inveId) {
					throw new Exception("缺失盘点ID，请核实{$data['id']}-{$data['inve_id']}-{$inveId}");
				}
				$res = $this->repository->getInveAudit(['inve_id' => $inveId, 'type' => '4'], 'id'); // 财务总监审批通过后才需要高级财务总监审批
				if ($res) {
					$where['r.ROLE_NAME'] = ['eq', '高级财务总监'];
				} else {
					$where['r.ROLE_NAME'] = ['eq', '财务总监'];
				}
				$people = $this->repository->getFinanceAudit($where);
				break;
			default:
				# code...
				break;
		}
		if ($people) {
			$peopleName = array_column($people, 'name');
		}
		Logs([$res, $people, $type, $data, $where, $peopleName], __FUNCTION__, 'tr');
		return $peopleName;
	}


	public function getFinAuditType($data)
	{
		list($amount, $type) = $this->getDiffAmount($data['id']);
		switch (strval($type)) {
			case '1':
				$res = '1';
				break;
			case '2':
				$res = '1';
				break;
			case '3':
				$result = $this->repository->getInveAudit(['inve_id' => $data['id'], 'type' => '4'], 'id');
				$res = '2';
				if ($result) { // 表明财务总监审批已填写意见，还剩高级财务总监填写意见
					$res = '3';
				}
				break;
			default:
				# code...
				break;
		}
		return $res;
	}

	public function getWxUserSql($name = '')
	{
		$model = M(); $res = false;
		$sql = "SELECT b.wid FROM bbm_admin as a left join tb_hr_empl_wx as b on a.empl_id = b.uid WHERE ( a.M_NAME = '{$name}' )";
		$res = $model->query($sql);
		if ($res) {
			$res = $res[0]['wid'];
		}
		Logs([$name, $sql, $res], __FUNCTION__, 'tr');
		return $res;
	}

	public function checkAuthFinPeople($authPeople)
	{
		$loginer = DataModel::userNamePinyin();
		if (!in_array($loginer, $authPeople)) {
			throw new Exception("{$loginer}不在操作权限人员范围内");
		}
	}

	public function getRolePerson($data)
	{
		list($amount, $type) = $this->getDiffAmount($data['id']);
		$res['finPerson'] = $this->getFinAuditByType($type, $data)[0];
		$inveInfo = $this->repository->getInveInfo(['id' => $data['id']], 'warehouse_cd');
		$authInfo = M('con_division_warehouse', 'tb_')->field('inventory_by')->where(['warehouse_cd' => $inveInfo['warehouse_cd']])->find();
		$authPeople = $authInfo['inventory_by'];
		$res['invePerson'] = $authPeople;
		return $res;
	}
	public function inveAuditConfirm($data)
	{
		if ($data['type'] == '1') { // 审核确认
			$saveInve['status_cd'] = InventoryModel::INVE_FIN_CONFIRMING; // 二期改版，需要通过财务审核
			$saveInve['sec_status_cd'] = InventoryModel::INVE_FIN_CONFIRM_IN;
			(new InventoryNotifyService())->inveNotify('2', ['inve_id' => $data['inve_id']]);
			$msg = '审核通过';
		}

		if ($data['type'] == '2') { // 待复核
			$saveInve['status_cd'] = InventoryModel::INVE_IN_CHECK;
			$saveInve['sec_status_cd'] = InventoryModel::INVE_IN_CHECK_SEC;
			$saveInve['has_check'] = '1';
			(new InventoryNotifyService())->inveNotify('3', ['inve_id' => $data['inve_id']]);
			$msg = '待复核';
		}

		if ($data['type'] == '3') { // 财务审核确认 金额小于5000
			list($amount, $type) = $this->getDiffAmount($data['inve_id']);
			$saveInve['status_cd'] = InventoryModel::INVE_IN_ADJUST;
			$authPeople = $this->getFinAuditByType($type, $data);
			$this->checkAuthFinPeople($authPeople);
			$msg = '财务确认';
		}

		if ($data['type'] == '4') { // 财务审核确认，金额大于5000 财务总监审批意见
			list($amount, $type) = $this->getDiffAmount($data['inve_id']);
			$authPeople = $this->getFinAuditByType($type, $data);
			$this->checkAuthFinPeople($authPeople);
			(new InventoryNotifyService())->inveNotify('2', ['inve_id' => $data['inve_id']]);
			$msg = '财务总监确认';
		}

		if ($data['type'] == '5') { // 财务审核确认，金额大于5000 高级财务总监审批意见
			list($amount, $type) = $this->getDiffAmount($data['inve_id']);
			$authPeople = $this->getFinAuditByType($type, $data);
			$this->checkAuthFinPeople($authPeople);
			$saveInve['status_cd'] = InventoryModel::INVE_IN_ADJUST;
			$msg = '高级财务总监确认';
		}

		$save['inve_id'] = $data['inve_id'];
		$save['remark'] = $data['remark'];
		$save['type'] = $data['type'];
		if (false === $this->repository->saveInveAudit('', $save)) {
			throw new Exception("审核保存审核内容失败~");
		};
		if (false === $this->repository->saveInve(['id' => $data['inve_id']], $saveInve)) {
			throw new Exception("审核保存更新盘点主表状态失败~");
		}
		$this->addLog($data['inve_id'], $msg);
		return true;
	}

	public function inveAuditApply($data)
	{
		$checkStatus = $this->repository->getInveInfo(['id' => $data['inve_id']], 'has_check');
		$checkDiff = $this->repository->getInveGoodsDiff(['inve_id' => $data['inve_id']], 'id');
		$checkStatus = $checkStatus['has_check'];
		$where['id'] = $data['inve_id'];
		$save['has_difference'] = !empty($checkDiff) ? '1' : '0';
		if (!$save['has_difference']) {
			$save['status_cd'] = InventoryModel::INVE_DONE;
			$save['end_at'] = date('Y-m-d H:i:s', time());
		} else {
			$save['status_cd'] = InventoryModel::INVE_IN_AUDIT;
			$save['sec_status_cd'] = $checkStatus == '1' ? InventoryModel::INVE_IN_AUDIT_SEC : InventoryModel::INVE_IN_AUDIT_FIRST;
		}
		$res = $this->repository->saveInve($where, $save);
		if (false === $res) {
			throw new Exception("发起差异审核保存更改状态失败~");
		}
		$inveNotifyType = $checkStatus == '1' ? '4' : '1';
		$this->addLog($data['inve_id'], '发起差异审核');
		(new InventoryNotifyService())->inveNotify($inveNotifyType, ['inve_id' => $data['inve_id']]);
		return $res;
	}

	public function getMaxBatchInfo($sku_id, $warehouse_cd)
	{
		if ($warehouse_cd) {
			$addSql = "AND ( t5.warehouse_id IN ( '{$warehouse_cd}' ) )";
		}
		$sql = "
			SELECT
				t5.CON_COMPANY_CD AS ourCompany,
				t1.sale_team_code AS saleTeam,
				t1.small_sale_team_code AS smallSaleTeam,
				t1.purchase_team_code AS purTeam,
				t2.pur_invoice_tax_rate,
				t2.proportion_of_tax,
				round( t2.unit_price_usd / ( 1+ ifnull( t2.pur_invoice_tax_rate, 0 ) ), 4 ) AS unitPriceUsdNoTax
			FROM
				tb_wms_batch t1
				LEFT JOIN tb_wms_stream t2 ON t1.stream_id = t2.id
				LEFT JOIN tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number
				LEFT JOIN tb_ms_cmn_cd cd ON cd.cd = t2.currency_id
				LEFT JOIN tb_wms_bill t5 ON t1.bill_id = t5.id
				LEFT JOIN tb_wms_stream_cost_log cl ON cl.stream_id = t2.id 
				AND cl.currency_id = 'N000590300'
				LEFT JOIN (
				SELECT
					tab2.batch_id,
					sum( tab2.occupied ) AS childOccupied,
					SUM( tab2.available_for_sale_num ) AS childLocking,
					tab2.SKU_ID 
				FROM
					tb_wms_batch_child tab2 
				GROUP BY
					tab2.batch_id,
					tab2.SKU_ID 
				) t12 ON t1.id = t12.batch_id 
				AND t1.SKU_ID = t12.SKU_ID 
			WHERE
				(
					t5.type = 1 
					AND t1.vir_type != 'N002440200' 
					AND t1.vir_type != 'N002410200' 
					AND ( t1.SKU_ID LIKE '{$sku_id}%' ) 
					AND t1.total_inventory > 0 
				) 
				{$addSql} 
			GROUP BY
				t1.id 
			ORDER BY
			t1.batch_code DESC limit 1
		";
		$response = M()->query($sql);
		return $response[0];
	}
	public function saveInveGoods($data)
	{
		$baseInfo = $data['base_data'];
		$goodsData = $data['goods_data'];
		// 现有的SKU更新或新增
		foreach ($goodsData['data'] as $key => $value) {
			$tempData = []; $goodsJson = []; $inveGudsId = ''; $delSave = []; $delRes = ''; 
			$tempData['inve_id'] = $baseInfo['inve_id'];
			$tempData['status'] = '0';
			$tempData['origin_num'] = $value['origin_num'];
			$tempData['actual_num'] = $value['actual_num'];
			$tempData['diff_num'] = $value['diff_num'];
			$tempData['sku_id'] = $value['skuId'];
			$tempData['is_new'] = '0';
			if ($value['goods_type_cd'] == '1') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_NORMAL;
			} elseif ($value['goods_type_cd'] == '2') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_BROKEN;
			} else {
				if ($value['goods_type_cd'] == InventoryModel::GOODS_TYPE_NORMAL) {
					$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_NORMAL;
				}
				elseif ($value['goods_type_cd'] == InventoryModel::GOODS_TYPE_BROKEN) {
					$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_BROKEN;
				} else {
					throw new Exception("商品类型传值有误{$value['goods_type_cd']}");
				}
			}
			if ($value['origin_num'] < $value['actual_num']) { 
				$tempData['type'] = '1';  // 盘盈
			}
			if ($value['origin_num'] > $value['actual_num']) {
				$tempData['type'] = '2';  // 盘亏
			}
			if (!$value['id']) {
				$inveGudsId = $this->repository->getInveGoodsDiff(['inve_id' => $baseInfo['inve_id'], 'sku_id' => $value['skuId'], 'goods_type_cd' => $tempData['goods_type_cd']], 'id')[0]['id'];
				$value['id'] = $inveGudsId;
			}
			if ($value['diff_num'] == 0 || ($value['origin_num'] == $value['actual_num'])) {
				if (!$value['id']) {
					continue; // 差异值为0的新的商品不用加到数据库，跳过
				} else {
					// 表明需要重新调整，删掉原来的差异商品数据
					$delSave['deleted_at'] = date('Y-m-d H:i:s');
					$delSave['deleted_by'] = DataModel::userNamePinyin();
					$delRes = $this->repository->saveInveGoods(['id' => $value['id']], $delSave);
					if ($delRes === false) {
						$tempData['dbErr'] = M()->getDbError();
						$tempData['msg'] = "删除调整差异商品失败{$value['id']}-{$value['skuId']}";
						ELog::add($tempData, ELog::ERR);
						throw new Exception($tempData['msg']);
					}
					ELog::add(['inve_id' => $baseInfo['inve_id'], 'msg' => "操作人调整原来差异商品数量",'request' => $delSave,'delRes'=> $delRes, 'value' => $value, 'id' => $value['id']],ELog::INFO);
					continue;
				}
			}
			$maxBatchInfo = [];
			$maxBatchInfo = $this->getMaxBatchInfo($value['skuId'], $baseInfo['warehouse_cd']);
			$goodsJson['sp_team_cd'] = $maxBatchInfo['purTeam'];
			$goodsJson['con_company_cd'] = $maxBatchInfo['ourCompany'];
			$goodsJson['proportion_of_tax'] = $maxBatchInfo['proportion_of_tax'];
			$goodsJson['pur_invoice_tax_rate'] = $maxBatchInfo['pur_invoice_tax_rate'];
			$goodsJson['sale_team_cd'] = $maxBatchInfo['saleTeam'];
			$goodsJson['small_sale_team_cd'] = $maxBatchInfo['smallSaleTeam'];
			$goodsJson['price'] = $maxBatchInfo['unitPriceUsdNoTax'] ? $maxBatchInfo['unitPriceUsdNoTax'] : 0;
			$tempData['tax_free_pur_unit_price'] = $maxBatchInfo['unitPriceUsdNoTax'] ? $maxBatchInfo['unitPriceUsdNoTax'] : 0;
			$goodsJson['location_code'] = $value['location_code'];
			$goodsJson['upcId'] = $value['upcId'];
			$goodsJson['amountTotalNum'] = $value['amountTotalNum'];
			$goodsJson['amountSaleNum'] = $value['amountSaleNum'];
			$goodsJson['amountOccupiedNum'] = $value['amountOccupiedNum'];
			$goodsJson['amountLockingNum'] = $value['amountLockingNum'];
			$goodsJson['gudsName'] = $value['gudsName'];
			$goodsJson['imageUrl'] = $value['imageUrl'];
			$goodsJson['optAttr'] = $value['optAttr'];
			$goodsJson = json_encode($goodsJson, JSON_UNESCAPED_UNICODE);
			$tempData['goods_json'] = $goodsJson;
			ELog::add(['inve_id' => $baseInfo['inve_id'], 'msg' => "操作人调整原来差异商品数量-数量调整",'request' => $tempData, 'value' => $value, 'id' => $value['id'], 'user' => DataModel::userNamePinyin()],  ELog::INFO);
			if(false === $this->repository->saveInveGoods(['id' => $value['id']], $tempData)) {
				$tempData['dbErr'] = M()->getDbError();
				$tempData['msg'] = "SKU{$value['skuId']}保存盘点商品失败";
				ELog::add($tempData, ELog::ERR);
				throw new Exception($tempData['msg']);
			}
		}
		// 导入的非该仓库的新的SKU
		foreach ($goodsData['import_data'] as $key => $vv) {
			$tempData = []; $goodsJson = []; $maxBatchInfo = []; $delSave = []; $delRes = ''; $delResSec = ''; $inveGudsId = '';
			if (!$vv['skuId']) {
				$vv['skuId'] = $vv['sku_id'];
			}
			if ($vv['goods_type_cd'] == '1') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_NORMAL;
			} elseif ($vv['goods_type_cd'] == '2') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_BROKEN;
			} else {
				if ($vv['goods_type_cd'] == InventoryModel::GOODS_TYPE_NORMAL) {
					$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_NORMAL;
				}
				elseif ($vv['goods_type_cd'] == InventoryModel::GOODS_TYPE_BROKEN) {
					$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_BROKEN;
				} else {
					throw new Exception("商品类型传值有误{$vv['goods_type_cd']}");
				}
			}
			if (!$vv['id']) {
				$inveGudsId = $this->repository->getInveGoodsDiff(['inve_id' => $baseInfo['inve_id'], 'sku_id' => $vv['skuId'], 'goods_type_cd' => $tempData['goods_type_cd']], 'id')[0]['id'];
				$vv['id'] = $inveGudsId;
			}

			if ($vv['diff_num'] == 0 || ($vv['origin_num'] == $vv['actual_num'])) {
				if (!$vv['id']) {
					continue; // 差异值为0的新的商品不用加到数据库，跳过
				} else {
					// 表明需要重新调整，删掉原来的差异商品数据
					$delSave['deleted_at'] = date('Y-m-d H:i:s');
					$delSave['deleted_by'] = DataModel::userNamePinyin();
					$delRes = $this->repository->saveInveGoods(['id' => $vv['id']], $delSave);
					if ($delRes === false) {
						$tempData['dbErr'] = M()->getDbError();
						$tempData['msg'] = "删除调整差异商品失败{$vv['id']}-{$vv['skuId']}";
						ELog::add($tempData, ELog::ERR);
						throw new Exception($tempData['msg']);
					}
					continue;
				}
			}
			if ($vv['origin_num'] > $vv['actual_num']) {
				throw new Exception("SKU{$vv['skuId']}盘点数量有误，应盘{$vv['origin_num']}, 实盘{$vv['actual_num']}，请先核实");
			}
			if ($vv['goods_type_cd'] == '1') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_NORMAL;
			}
			if ($vv['goods_type_cd'] == '2') {
				$tempData['goods_type_cd'] = InventoryModel::GOODS_TYPE_BROKEN;
			}
			$maxBatchInfo = $this->getMaxBatchInfo($vv['skuId']);
			$tempData['inve_id'] = $baseInfo['inve_id'];
			$tempData['status'] = '0';
			$tempData['type'] = '1';
			$tempData['origin_num'] = $vv['origin_num'];
			$tempData['actual_num'] = $vv['actual_num'];
			$tempData['diff_num'] = $vv['diff_num'];
			$tempData['sku_id'] = $vv['skuId'];
			$tempData['is_new'] = '1';
			$tempData['tax_free_pur_unit_price'] = $maxBatchInfo['unitPriceUsdNoTax'] ? $maxBatchInfo['unitPriceUsdNoTax'] : 0;
			$goodsJson['sp_team_cd'] = $vv['sp_team_cd'];
			$goodsJson['con_company_cd'] = $vv['con_company_cd'];
			$goodsJson['sale_team_cd'] = $vv['sale_team_cd'];
			$goodsJson['small_sale_team_cd'] = $vv['small_sale_team_cd'];
			$goodsJson['pur_invoice_tax_rate'] = $vv['pur_invoice_tax_rate'];
			$goodsJson['proportion_of_tax'] = $vv['proportion_of_tax'];
			$goodsJson['location_code'] = $vv['location_code'];
			$goodsJson['upcId'] = $vv['upcId'];
			$goodsJson['amountTotalNum'] = $vv['amountTotalNum'];
			$goodsJson['amountSaleNum'] = $vv['amountSaleNum'];
			$goodsJson['amountOccupiedNum'] = $vv['amountOccupiedNum'];
			$goodsJson['amountLockingNum'] = $vv['amountLockingNum'];
			$goodsJson['gudsName'] = $vv['gudsName'];
			$goodsJson['imageUrl'] = $vv['imageUrl'];
			$goodsJson['optAttr'] = $vv['optAttr'];	
			$goodsJson['sp_team_cd'] = $vv['sp_team_cd'];
			$goodsJson['con_company_cd'] = $vv['con_company_cd'];
			$goodsJson['proportion_of_tax'] = $vv['proportion_of_tax'];
			$goodsJson['pur_invoice_tax_rate'] = $vv['pur_invoice_tax_rate'];
			$goodsJson['sale_team_cd'] = $vv['sale_team_cd'];
			$goodsJson['small_sale_team_cd'] = $vv['small_sale_team_cd'];
			$goodsJson['price'] = $maxBatchInfo['unitPriceUsdNoTax'] ? $maxBatchInfo['unitPriceUsdNoTax'] : 0;
			$goodsJson = json_encode($goodsJson, JSON_UNESCAPED_UNICODE);
			$tempData['goods_json'] = $goodsJson;
			
			if(false === $this->repository->saveInveGoods(['id' => $vv['id']], $tempData)) {
				$tempData['dbErr'] = M()->getDbError();
				$tempData['msg'] = "EXCEL导入的SKU{$vv['skuId']}保存盘点商品失败~";
				ELog::add($tempData, ELog::ERR);
				throw new Exception($tempData['msg']);
			}
			ELog::add(['inve_id' => $baseInfo['inve_id'], 'msg' => "操作人调整原来差异商品数量-数量调整-EXCEL导入",'request' => $tempData, 'vv' => $vv, 'id' => $vv['id'], 'user' => DataModel::userNamePinyin()],ELog::INFO);
		}
		$this->addLog($baseInfo['inve_id'], '录入盘点情况');
	}

	private function joinTempSaveData($datum, $temp_data)
	{
	    if (empty($datum['id'])) {
	        $temp_data['created_by'] = DataModel::userNamePinyin();
	    }
	    $temp_data['updated_by'] = DataModel::userNamePinyin();
	    return $temp_data;
	}


	public function getInveGoodsBatch($data)
	{
		$search = $data['search'];
		$params['warehouse'][0] = $search['warehouse_cd'];
		$params['pageIndex'] = '1';
		$params['pageSize'] = '1000'; // PRD中批次无分页，参照现存量接口的传参 
		$params['mixedCode'] = $search['sku_id'];
		$params['saleTeam'][] = $search['sale_team_cd'];
		$params['productType'] = $search['goods_type']; // 商品类型
		$params['orderType'] = 'asc'; // 按批次最小的正序排列

		$model = new StandingExistingModel();
		$response = $model->getInveBatchData($params);
		$response = $this->adjustInveGoodsBatch($response, $search['inve_guds_id']);
		$res_return ['data'] = $response;
		return $res_return;
	}

	public function adjustInveGoodsBatch($response, $gudsId)
	{
		if ($gudsId) {
			// 获取该盘点单差异商品批次列表
			$resData = $this->repository->getInveGoodsBatchDiff(['inve_guds_id' => $gudsId], 'id, diff_num, amount, batch_id');
			$resData = array_column($resData, NULL, 'batch_id');
			foreach ($response as $key => $value) {
				$response[$key]['diffNum'] = $resData[$value['batchId']]['diff_num'] ? $resData[$value['batchId']]['diff_num'] : '0';
				$response[$key]['amount'] = $resData[$value['batchId']]['amount'] ? $resData[$value['batchId']]['amount'] : '0';
				$response[$key]['inveGudsBatchId'] = $resData[$value['batchId']]['id'];
			}
		}
		return $response;
	}
	public function getInveGoods($data)
	{
		$search = $data['search'];
		$page = $data['page'];
		$params['warehouse'][0] = $search['warehouse_cd'];
		if ($search['sale_team_cd']) {
			$params['saleTeam'][0] = $search['sale_team_cd'];
		}
		$params['productType'] = $search['goods_type'];
		$params['mixedCode'] = $search['inve_sku'];
		$params['pageIndex'] = $page['current_page'];
		$params['pageSize'] = $page['per_page'];
		$model = new StandingExistingModel();
		$response = $model->getInveData($params);

		$response = $this->adjustInvGoodsData($response, $search['warehouse_cd'], $search['inve_id']);
		$pages ['total'] = $model->count;
		$pages ['current_page'] = $page['current_page'];
		$pages ['per_page'] = $page['per_page'];
		$res_return['pages'] = $pages;
		$res_return ['data'] = $response;
		return $res_return;
	}

	public function adjustDataByGoodsType($data, $skuName = 'sku_id', $productTypeName = 'goods_type_cd')
	{
		$temp = [];
		foreach ($data as $key => $value) {
				$temp[$value[$skuName] . '-' . $value[$productTypeName]] = $value;	
		}
		return $temp;
	}

	public function adjustInvGoodsData($response, $warehouse_cd, $inve_id)
	{
		if (!$response || !$warehouse_cd) {
			return null;
		}
		$tempData = []; $resData = [];
		// 根据仓库CD值获取仓库id值
		$warehouse_id = WarehouseModel::getWarehouseIdByCode($warehouse_cd);
		if (!$warehouse_id) {
			throw new Exception("根据仓库CD值{$warehouse_cd}无法获取到对应的仓库ID，无法获取货位信息");
		}
		if ($inve_id) {
			// 获取该盘点单差异商品列表
			$resData = $this->repository->getInveGoodsDiff(['inve_id' => $inve_id]);
			$resData = $this->adjustDataByGoodsType($resData);
			// $resData = array_column($resData, NULL, 'sku_id');
			// 获取快照信息
			$resPhotoData = $this->repository->getInveGoodsPhoto(['inve_id' => $inve_id], 'sku_id, amount_total_num, amount_sale_num, amount_occupied_num, amount_locking_num, goods_type_cd');
			// $resPhotoData = array_column($resPhotoData, NULL, 'sku_id');
			$resPhotoData = $this->adjustDataByGoodsType($resPhotoData);
		}
		$locationRepository = new LocationRepository();
		foreach ($response as $key => $value) {
			$goodsTypeCd = ''; $productTypeValue = '';
			if ($value['productType'] == '正品') {
				$goodsTypeCd = InventoryModel::GOODS_TYPE_NORMAL;
				$productTypeValue = '1';
			}
			if ($value['productType'] == '残次品') {
				$goodsTypeCd = InventoryModel::GOODS_TYPE_BROKEN;
				$productTypeValue = '2';
			}
			$tempData[$key] = $value;
			$tempData[$key]['productTypeValue'] = $productTypeValue;
			$tempData[$key]['amountLockingNum'] = $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd] ? $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd]['amount_locking_num'] : $value['amountLockingNum'];
			$tempData[$key]['amountSaleNum'] = $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd] ? $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd]['amount_sale_num'] : $value['amountSaleNum'];
			$tempData[$key]['amountTotalNum'] = $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd] ? $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd]['amount_total_num'] : $value['amountTotalNum'];
			$tempData[$key]['amountOccupiedNum'] = $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd] ? $resPhotoData[$value['skuId'] . '-' . $goodsTypeCd]['amount_occupied_num'] : $value['amountOccupiedNum'];
			$tempData[$key]['location_code'] = $this->getInveGoodsLocationCodeByWarehouse($warehouse_id, $value['skuId'], $locationRepository, $value['productType']);
			$tempData[$key]['actual_num'] = $resData[$value['skuId'] . '-' . $goodsTypeCd] ? $resData[$value['skuId'] . '-' . $goodsTypeCd]['actual_num'] : $value['amountTotalNum'];
			$tempData[$key]['origin_num'] = $resData[$value['skuId'] . '-' . $goodsTypeCd] ? $resData[$value['skuId'] . '-' . $goodsTypeCd]['origin_num'] : $value['amountTotalNum']; // 只要查询到有值即可返回（包含0）
			$tempData[$key]['diff_num'] = $resData[$value['skuId'] . '-' . $goodsTypeCd] ? $resData[$value['skuId'] . '-' . $goodsTypeCd]['diff_num'] : '0';
			$tempData[$key]['id'] = $resData[$value['skuId'] . '-' . $goodsTypeCd]['id'] ? $resData[$value['skuId'] . '-' . $goodsTypeCd]['id'] : '';
		}
		return $tempData;
	}

	public function getInveGoodsLocationCodeByWarehouse($warehouseId, $skuId, $locationRepository, $productType)
	{
		if (!$locationRepository) {
			$locationRepository = new LocationRepository();
		}
		$localinfo = [];
		$localinfo = $locationRepository->getLocationCodeByWarehouse($warehouseId, $skuId);
		$locationCode = $localinfo['location_code'];
		// 根据正次品来获取对应的货位
		if ($productType === '残次品') {
			$locationCode = $localinfo['defective_location_code'];
		}	
		return $locationCode;
	}

	public function inveIndex($data)
	{
		$preData = $data;
		$search_map = [
		    'end_at' => 'wi.end_at',
		    'created_at' => 'wi.created_at',
		    'inve_no' => 'wi.inve_no',
		    'status_cd' => 'wi.status_cd',
		    'sec_status_cd' => 'wi.sec_status_cd',
		    'created_by' => 'wi.created_by',
		    'has_difference' => 'wi.has_difference',
		    'warehouse_cd' => 'wi.warehouse_cd',
		    'sale_team_cd' => 'wi.sale_team_cd',
		    'inve_sku' => 'wi.inve_sku',
		    'goods_type_cd' => 'wi.goods_type_cd',

		];
		$map = [
			'status_cd',
			'sec_status_cd',
			'warehouse_cd',
			'inve_no',
			'has_difference',
			'goods_type_cd',
			'sale_team_cd'
		];
		if ($data['search']['sale_team_cd'] === 'all') {
			unset($data['search']['sale_team_cd']);
		}
		if ($data['search']['inve_sku'] === 'all') {
			unset($data['search']['inve_sku']);
		}
		list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map, '', $map);
		if ($preData['search']['inve_sku'] == 'all') {
			$wheres['inve_sku'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		}
		if ($preData['search']['sale_team_cd'] == 'all') {
			$wheres['sale_team_cd'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		}
		$wheres['deleted_by'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		$wheres['deleted_at'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		
		list($res_return['data'], $res_return['pages']) = $this->repository->inveIndex($wheres, $limit);
		$res_return['data'] = $this->adjustInvData($res_return['data']);
		return $res_return;
	}

	public function adjustInvData($data)
	{
		if ($data) {
			foreach ($data as $key => &$value) {
				$value['has_difference'] = $value['has_difference'] ? '有' : '无';
				$value['inve_sku'] = $value['inve_sku'] ? $value['inve_sku'] : '全SKU';
				$value['goods_type_cd_val'] = $value['goods_type_cd_val'] ? $value['goods_type_cd_val'] : '全品类';
				$value['sale_team_cd_val'] = $value['sale_team_cd_val'] ? $value['sale_team_cd_val'] : '全团队';
			}
		}
		return $data;
	}

	public function addOtherParams($data, $addData)
	{
		if ($data['sale_team_cd']) {
			$addData['sale_team_cd'] = $data['sale_team_cd'];
		}
		if ($data['inve_sku']) {
			$addData['inve_sku'] = $data['inve_sku'];
		}
		if ($data['goods_type_cd']) {
			$addData['goods_type_cd'] = $data['goods_type_cd'];
		} else {
			$addData['goods_type_cd'] = InventoryModel::GOODS_TYPE_ALL;
		}
		return $addData;
	}
	public function inveCreate($data)
	{
		$this->checkInveCreate($data); // 该仓库若有未完结的，不允许创建新盘点单
		$addData = [
			'status_cd' => InventoryModel::INVE_IN_CHECK,
			'sec_status_cd' => InventoryModel::INVE_IN_CHECK_FIRST,
			'warehouse_cd' => $data['warehouse_cd'],
		];
		$addData = $this->addOtherParams($data, $addData);
		$createId = $this->repository->inveCreate($addData);
		if ($createId === false) {
			throw new Exception("创建盘点单失败");
		}
		$initRes = $this->repository->initInveGoodsPhoto($createId, $addData['warehouse_cd'], $addData);
		if ($initRes === false) {
			throw new Exception("创建盘点单初始化商品快照失败");	
		}
		$this->addLog($createId, '发起盘点');
		return $createId;
	}

	public function checkInveCreate($data)
	{
		if (!$data['warehouse_cd']) {
			throw new Exception("仓库不可为空");
		}
		// 判断下是否已经存在盘点状态不是已完成的，则不可继续创建盘点单
		$where = ['warehouse_cd' => $data['warehouse_cd'], 'status_cd' => ['neq', InventoryModel::INVE_DONE]];
		$where['deleted_by'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		$where['deleted_at'] = array(array('EXP', 'IS NULL'), array('eq', ''), 'or');
		$res = $this->repository->getInveInfo($where, 'id');
		if ($res['id']) { 
			throw new Exception("该仓库还在盘点中，请先完结后再继续创建盘点单");
		}
	}
	public function getStatusList()
	{
		return InventoryModel::getStatusList();
	}

	// 后续如果新增多个，可以通过分工仓库配置中，包含盘点负责人不为空找到相应的仓库CD
	// 盘点二期扩展到“一般仓”范围
	public function getOwnWarehouse()
	{
        $param['type_cd'][0] = 'N002590100'; 
        $result = WarehouseModel::filterWarehouse($queryParams);
        $result = array_column($result, 'CD_VAL', 'CD');
		return $result;
	}
	#获取盘点单状态
	public function getInveStatus($id){
		return $this->repository->getInveStatus($id);
	}
	#删除盘点单
	public function inveDelete($id,$model){
		$where = ['id' => $id];
		$data = ['deleted_by' => userName(), 'deleted_at' =>date('Y-m-d H:i:s')];
		return $this->repository->inveDelete($where, $data, $model);
	}
}
