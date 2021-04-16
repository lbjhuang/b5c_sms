<?php
class ThrApiAction extends BaseAction
{
	public $thrApiService;
	public $model;
	public function _initialize() {
	    parent::_initialize();
	    $this->model = new \Model();
	    $this->thrApiService = new ThrLogicsApiService($this->model);
	}

	public function data_list()
	{
		$this->display();
	}

	public function get_list_data()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $res = DataModel::$success_return;
		    $res['data'] = $this->thrApiService->get_thr_List($request_data);
		} catch (Exception $exception) {
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);     
	}

	public function get_report_data()
	{
		$request_data = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true));
		$res = $this->thrApiService->get_thr_List($request_data, true);
		$list = $res['data'];
		$map = [
			['field_name' => 'order_no', 'name' => '第三方订单号'],
			['field_name' => 'logistics_no', 'name' => '物流运单号'],
			['field_name' => 'api_request_status_cd_val', 'name' => '查询结果'],
			['field_name' => 'logistics_status_cd_val', 'name' => '物流状态'],
			['field_name' => 'company_cd_val', 'name' => '物流公司'],
			['field_name' => 'updated_at', 'name' => '最新查询时间'],
			['field_name' => 'api_numbers', 'name' => 'API调用次数'],
			['field_name' => 'api_platform_cd_val', 'name' => 'API对接平台'],
			['field_name' => 'fee', 'name' => '费用'],
			['field_name' => 'remark', 'name' => '备注'],
		];

		$this->exportCsv($list, $map);
	}

	public function delete_data()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $res = DataModel::$success_return;
		    $res['data'] = $this->thrApiService->delete_thr_data($request_data);
		} catch (Exception $exception) {
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);     
	}

	public function manual_create()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $res = DataModel::$success_return;
		    $res['data'] = $this->thrApiService->manual_create($request_data);
		} catch (Exception $exception) {
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);     
	}

	// 根据订单号或运单号互搜
	public function get_order_associate()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $res = DataModel::$success_return;
		    $res['data'] = $this->thrApiService->get_handle_order_associate($request_data);
		} catch (Exception $exception) {
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);    
	}

	
}