<?php
/**
 * User: b5m
 * Date: 2017/12/27
 * Time: 14:15
 */

class LocationAction extends BaseAction
{
	public function _initialize()
	{
		parent::_initialize();
		$_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
		if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
			$json_str = file_get_contents('php://input');
			$json_str = stripslashes($json_str);
			$_POST = json_decode($json_str, true);
			$_REQUEST = array_merge($_POST, $_GET);
		}
		$this->accountLog = new TbWmsAccountBankLogModel();
		$this->mail = new ExtendSMSEmail();
		$this->esClient = new ESClientModel();
		import('ORG.Util.Page');// 导入分页类
		header('Access-Control-Allow-Origin: *');
		header('Content-Type:text/html;charset=utf-8');
		parent::_initialize();
		B('SYSOperationLog');
	}

	/**
	 * 仓库管理首页
	 *
	 */
	public function index()
	{
		$model = new SlaveModel();

		$fields = '
			t1.warehouse_id,
			t1.id,
			t1.sku,
			t1.location_code,
			t1.location_code_back,
			t1.defective_location_code,
			t5.image_url as img,
			t6.spu_name as GUDS_NM
		';

		$locationModel = new LocationModel();
		$conditions = $locationModel->search(ZUtils::filterBlank($this->getParams()));

		$count = $model->field($fields)->table(B5C_DATABASE . '.tb_wms_location_sku t1')
			//->join('LEFT JOIN ' . PMS_DATABASE . '.product_attribute t2 ON t1.sku = t2.sku_id and SUBSTR(t1.sku, 1, 8) = t2.spu_id')
			//->join('LEFT JOIN ' . PMSSearchModel::nameDetailSql() . 't3 ON t2.name_id = t3.name_id')
			->join('LEFT JOIN ' . PMSSearchModel::skuUpcSql() . 't4 ON t1.sku = t4.sku_id')
			->join('LEFT JOIN ' . PMS_DATABASE . '.product_image t5 ON t4.spu_id = t5.spu_id AND t5.image_type = "N000080200"')
			->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't6 ON t4.spu_id = t6.spu_id')
			->where($conditions)
			->group('warehouse_id, sku, location_code')
			->select();
		$count = count($count);
		$page = new Page($count, 20);
		$ret = $model->field($fields)->table(B5C_DATABASE . '.tb_wms_location_sku t1')
			//->join('LEFT JOIN ' . PMS_DATABASE . '.product_attribute t2 ON t1.sku = t2.sku_id and SUBSTR(t1.sku, 1, 8) = t2.spu_id')
			//->join('LEFT JOIN ' . PMSSearchModel::nameDetailSql() . 't3 ON t2.name_id = t3.name_id')
			//->join('LEFT JOIN ' . PMSSearchModel::valueDetailSql() . 't4 ON t2.value_id = t4.value_id')
			->join('LEFT JOIN ' . PMSSearchModel::skuUpcSql() . 't4 ON t1.sku = t4.sku_id')
			->join('LEFT JOIN ' . PMS_DATABASE . '.product_image t5 ON t4.spu_id = t5.spu_id AND t5.image_type = "N000080200"')
			->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't6 ON t4.spu_id = t6.spu_id')
			->where($conditions)
			->group('warehouse_id, sku, location_code')
			->limit($page->firstRow, $page->listRows)
			->select();

		$show = $page->ajax_show('flip');
		$this->assignJson('warehouses', self::getWarehouseInfo());
		$sku = array_column($ret, 'sku');
		$attributes = SkuModel::getSkusInfo($sku, $appends = ['attributes']);
		$ret = array_map(function ($r) use ($attributes) {
			$r ['govm'] = $attributes ['attributes'][$r ['sku']];

			return $r;
		}, $ret);

		if (!$count) $count = 0;
		$this->assignJson('ret', $ret);
		$this->assignJson('page', $show);
		$this->assign('count', $count);

		if (IS_POST)
			$this->AjaxReturn(['ret' => $ret, 'page' => $show, 'count' => $count], 'success', 1);

		$this->display();
	}

	public function gudsOptsMerge($val)
	{
		$str = explode(';', $val);
		$opt = BaseModel::getGudsOpt();
		$shtml = '';
		$length = count($str);
		for ($i = 0; $i < $length; $i++) {
			if ($opt[$str[$i]]['OPT_CNS_NM'] and $opt[$str[$i]]['OPT_VAL_CNS_NM']) $shtml .= $opt[$str[$i]]['OPT_CNS_NM'] . ':' . $opt[$str[$i]]['OPT_VAL_CNS_NM'] . ' ';
			else $shtml .= $opt[$str[$i]]['OPT_VAL_CNS_NM'];
		}
		return $shtml;
	}

	public function test()
	{
		$query = ZUtils::filterBlank($this->getParams())['data']['query'];
		$gudsOpt = new TbMsGudsOptModel();
		$r = $gudsOpt->gudOptProperty($query ['skuId']);
		var_dump($r);
		exit;
		$this->ajaxReturn($r, 'json');
	}

	/**
	 * 获取仓库
	 *
	 */
	public static function getWarehouseInfo()
	{
		$model = new Model();
		$ret = $model->table('tb_wms_warehouse')->getField('id, CD, warehouse');
		$cd_model = M('ms_cmn_cd', 'tb_');
		foreach ($ret as &$v) {
		    if (empty($v['warehouse'])) {
                $v['warehouse'] = $cd_model->where(['CD' => $v['CD']])->getField('CD_VAL');
            }
        }
		return $ret;
	}

	/**
	 * 模板验证
	 *
	 */
	public function validate_template()
	{
		$name = I('post.data');
		$path = APP_PATH . 'Tpl/Home/Location';
		if (file_exists($path . '/' . $name))
			$this->AjaxReturn('', L('success'), 1);
		else
			$this->AjaxReturn('', L('文件不存在'), 0);
	}

	/**
	 * 模板下载
	 *
	 */
	public function download_location_template()
	{
		$name = I('get.name');
		$model = new FileDownloadModel();
		$model->path = APP_PATH . 'Tpl/Home/Location';
		$model->fname = $name;
		if (!$model->downloadFile()) {
			$this->AjaxReturn('', $name . L('下载失败，请重试'), 0);
		}
	}

	/**
	 * 批量导入货位
	 *
	 */
	public function import_location()
	{
		$model = new ImportLocationModel();
		if ($model->import()) {
			//$this->AjaxReturn(compressData($model->errorinfo), L('导入成功'), 1);
			$msg = L('导入成功');
			$isSuccess = true;
		} else {
			$errorData = null;
			foreach ($model->errorinfo as $k => $info) {
				foreach ($info as $rowIndex => $v) {
					$errorData [$rowIndex] .= trim($v) . '-';
				}
			}
			$data = [];
			foreach ($model->data as $k => $value) {
				$temp = [];
				foreach ($value as $field => $v) {
					$temp [$v ['db_field']] = $v ['value'];
					$temp ['error'] = rtrim($errorData [$k], '-');
				}
				$data [] = $temp;
			}
			$msg = L('导入失败');
			$isSuccess = false;
		}
		$isSuccess ? $this->assign('redirect', ZUtils::redirect_relay(U("Location/index"), 2)) : '';
		$this->assignJson('msg', $msg);
		$this->assignJson('errorData', $errorData);
		$this->assignJson('isTranslation', 1);
		$this->assignJson('isSuccess', $isSuccess);
		$this->assignJson('requestData', compressData($data));
		$this->assign('title', L('导入失败'));
		$this->display();
	}

	/**
	 * 更新货位
	 *
	 */
	public function update()
	{
        $params                             = $this->getParams();
        $params ['location_code']           = trim($params ['location_code']);
        $params ['location_code_back']      = trim($params ['location_code_back']);
        $params ['defective_location_code'] = trim($params ['defective_location_code']);
		if ($params) {
			$model = new Model();
//			if ($params ['location_code'] == $params ['location_code_back']) {
//				$this->AjaxReturn('', L('货位编码与备用货位编码重复'), 0);
//			}
			// 验证备用货位编码是否与主货位编码重复
//			if ($params ['edit_location_code_back'] != $params ['location_code_back']) {
//				if ($ret = $model->table('tb_wms_location_sku')->where("warehouse_id = %d and location_code = '%s'", [$params ['warehouse_id'], $params ['location_code_back']])->find()) {
//					$this->AjaxReturn($model->table('tb_wms_location_sku')->getLastSql(), L('备用货位编码') . ' [' . $params ['location_code_back'] . '] ' . L('与货位编码冲突'), 0);
//				}
//			}
//            if ($params ['edit_location_code'] != $params ['location_code']) {
//                // 验证主货位编码是否已使用
//                if ($model->where("warehouse_id = %d and location_code = '%s'", [$params ['warehouse_id'], $params ['location_code']])->find()) {
//                    $this->AjaxReturn('', L('货位') . '[' . $params ['location_code'] . ']' . L('已使用'), 0);
//                }
//            }
            $data ['id']                      = $params ['id'];
            $data ['location_code']           = $params ['location_code'];
            $data ['location_code_back']      = $params ['location_code_back'];
            $data ['defective_location_code'] = $params ['defective_location_code'];
			if ($model->table('tb_wms_location_sku')->save($data))
				$this->AjaxReturn('', L('修改成功'), 1);
			else
				$this->AjaxReturn('', L('修改失败，请稍后再试'), 0);
		}
		$this->AjaxReturn('', L('请求异常'), 0);
	}

	/**
	 * 删除货位
	 *
	 */
	public function delete()
	{
		$params = $this->getParams();
		$model = new ImportLocationModel();
		if ($model->where("id = %d", $params ['id'])->delete()) {
			$this->AjaxReturn('', L('删除成功'), 1);
		} else
			$this->AjaxReturn('', L('删除失败，请稍后再试'), 0);
	}

	/**
	 * 生成导出数据文件
	 *
	 */
	public function generate_export_file()
	{
		$model = new LocationModel();

		$fields = '
			t1.warehouse_id,
			t1.sku,
			t1.location_code,
			t1.location_code_back,
			t1.defective_location_code
		';

		$locationModel = new LocationModel();
		$conditions = $locationModel->search(ZUtils::filterBlank($this->getParams()));

		$ret = $model->field($fields)->table(B5C_DATABASE . '.tb_wms_location_sku t1')
			->join('LEFT JOIN ' . PMS_DATABASE . '.product_attribute t2 ON t1.sku = t2.sku_id and SUBSTR(t1.sku, 1, 8) = t2.spu_id')
			->join('LEFT JOIN ' . PMSSearchModel::nameDetailSql() . 't3 ON t2.name_id = t3.name_id')
			->join('LEFT JOIN ' . PMSSearchModel::valueDetailSql() . 't4 ON t2.value_id = t4.value_id')
			->join('LEFT JOIN ' . PMS_DATABASE . '.product_image t5 ON t2.spu_id = t5.spu_id AND t5.image_type = "N000080200"')
			->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't6 ON t2.spu_id = t6.spu_id')
			->group('warehouse_id, sku, location_code')
			->where($conditions)
			->select();
		
		if ($ret) {
			$warehouse = self::getWarehouseInfo();
			foreach ($ret as $key => &$value) {
				$value ['warehouse'] = $warehouse [$value ['warehouse_id']]['warehouse'];
				$value ['warehouse_code'] = $warehouse [$value ['warehouse_id']]['CD'];
				unset($value);
			}
			$exportModel = new ExportExcelModel();
			$exportModel->attributes = [
                'A' => ['name' => L('仓库名称'), 'field_name' => 'warehouse'],
                'B' => ['name' => L('仓库CODE值'), 'field_name' => 'warehouse_code'],
                'C' => ['name' => L('SKU编码'), 'field_name' => 'sku'],
                'D' => ['name' => L('正品货位编码'), 'field_name' => 'location_code'],
                'E' => ['name' => L('残次品货位编码'), 'field_name' => 'defective_location_code'],
                'F' => ['name' => L('备用货位编码'), 'field_name' => 'location_code_back'],
			];
			$exportModel->data = $ret;
			$fname = time() . '.xls';
			$filePath = ATTACHMENT_DIR_IMG . $fname;
			$exportModel->export($filePath);
			$this->AjaxReturn($fname, '', 1);
		} else
			$this->AjaxReturn('', L('无数据'), 0);
	}

	/**
	 * 生成错误报告文件
	 *
	 */
	public function generate_error_report_file()
	{
		if ($this->getParams()['data']) {
			$data = unCompressData($this->getParams()['data']);
			$model = new ExportExcelModel();
			$model->attributes = [
				'A' => ['name' => L('仓库名称'), 'field_name' => 'warehouse_name'],
				'B' => ['name' => L('仓库code值'), 'field_name' => 'warehouse_code'],
				'C' => ['name' => L('SKU编码'), 'field_name' => 'sku'],
				'D' => ['name' => L('货位编码'), 'field_name' => 'location_code'],
				'E' => ['name' => L('备用货位编码'), 'field_name' => 'location_code_back'],
				'F' => ['name' => L('错误提示'), 'field_name' => 'error'],
			];
			//$model->title = '错误报告';
			$model->data = $data;
			$fname = time() . '.xls';
			$filePath = ATTACHMENT_DIR_IMG . $fname;
			$model->export($filePath);
			$this->AjaxReturn($fname, 'success', 1);
		}
	}

	/**
	 * 下载错误报告文件
	 * 下载按条件导出文件
	 *
	 */
	public function download_file()
	{
		$model = new FileDownloadModel();
		$model->fname = $this->getParams() ['name'];
		$model->downloadFile();
	}

	public function logistics_company_info()
	{
		$this->display();
	}

	 //自有物流仓配置
	public function saveOwmLogistics() {
		try {
			$request_data = DataModel::getDataNoBlankToArr();
			$rClineVal = RedisModel::lock('owm_logistics_save' . $request_data['warehouse_code'], 10);

			if ($request_data) {
				$this->validateOwmLogisticsData($request_data);
			} else {
				throw new Exception('请求为空');
			}
			if (!$rClineVal) {
				throw new Exception('获取流水锁失败');
			}
			$res = DataModel::$success_return;
			$res['code'] = 200;
			$model = new \Model();
			$model->startTrans();
			(new ConfigurationService($model))->owmLogisticsSave($request_data);
			$model->commit();
			RedisModel::unlock('owm_logistics_save' . $request_data['warehouse_code']);
		} catch (Exception $exception) {
			$model->rollback();
			$res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}

	private function validateOwmLogisticsData($data) {
		$rules = [
			'warehouse_code'             => 'required|size:10',
			'logistics_company_code'     => 'required|size:10',
			'logistics_mode_id'             => 'required',
			'is_own_logistics_warehouse' => 'required|numeric',
			'id'                         => 'sometimes|required|numeric'
		];
		$custom_attributes = [
			'warehouse_code'             => '仓库',
			'logistics_company_code'     => '物流公司',
			'logistics_mode'             => '物流方式',
			'is_own_logistics_warehouse' => '自有物流仓',
		];
		$this->validate($rules, $data, $custom_attributes);
	}

	public function getOwnLogisticsDetail() {
		try {
			$request_data = DataModel::getDataNoBlankToArr();
			if (!isset($request_data['id'])) {
				throw new \Exception(L('参数错误'));
			}
			$res = DataModel::$success_return;
			$res['code'] = 200;
			$res['data'] = M('ms_logistics_own_config', 'tb_')->find($request_data['id']);
		} catch (Exception $exception) {
			$res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}

	public function deleteOwmLogistics() {
		try {
			$request_data = DataModel::getDataNoBlankToArr();
			$rClineVal = RedisModel::lock('owm_logistics_delete' . $request_data['id'], 10);

			if (!isset($request_data['id'])) {
				throw new \Exception(L('参数错误'));
			}
			if (!$rClineVal) {
				throw new Exception('获取流水锁失败');
			}
			$res = DataModel::$success_return;
			$res['code'] = 200;
			$model = new \Model();
			$model->startTrans();
			if (!M('ms_logistics_own_config', 'tb_')->delete($request_data['id'])) {
				throw new \Exception(L('删除失败')); 
			}
			$model->commit();
			RedisModel::unlock('owm_logistics_delete' . $request_data['id']);
		} catch (Exception $exception) {
			$model->rollback();
			$res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}

	public function getOwnLogisticsList() {
		try {
			$request_data = DataModel::getDataNoBlankToArr();
			
			$res = DataModel::$success_return;
			$res['code'] = 200;
			$res['data'] = (new ConfigurationService())->searchOwnLogisticsList($request_data);
		} catch (Exception $exception) {
			$res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}

	public function setting() {
		$this->display();
	}

	public function exportOwmLogistics() {
		try {
			$request_data['search'] = [
				'warehouse'        => I('warehouse'),
				'logistics_company' => I('logistics_company'),
				'logistics_mode'    => I('logistics_mode'),
			];
			$data = (new ConfigurationService())->searchOwnLogisticsList($request_data, true)['data'];
		$map = [
			['field_name' => 'number', 'name' => '编号'],
			['field_name' => 'warehouse_code_val', 'name' => '仓库'],
			['field_name' => 'logistics_company_code_val', 'name' => '物流公司'],
			['field_name' => 'logistics_mode', 'name' => '物流方式'],
			['field_name' => 'is_own_logistics_warehouse_val', 'name' => '自有物流仓'],
			['field_name' => 'updated_at', 'name' => '操作时间'],
			['field_name' => 'updated_by', 'name' => '操作人'],
        ];
        $this->exportCsv($data, $map);
		} catch (Exception $exception) {
			$res = $this->catchException($exception);
		}
	}
}