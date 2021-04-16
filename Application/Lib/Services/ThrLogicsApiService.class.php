<?php
class ThrLogicsApiService extends Service
{
	public $user_name;
	public $model;
	public function __construct($model)
	{
	    $this->model     = empty($model) ? new Model() : $model;
	    $this->user_name = DataModel::userNamePinyin();
	}

	public static $handle_type = 'N002850004'; // 手工运单类型

	// 校验
	public function check_valid_handle_order($request_data = [])
	{
		
		// 判断是否已经存在该订单号&店铺，如果已经存在，则不允许继续新增
		// 判断该订单和运单以及店铺有效性

		$logistics_model = M('logistics_thr_api', 'tb_ms_');
		$where['api_platform_cd'] = self::$handle_type;
		$where['order_no'] = $request_data['order_id'];
		$where['plat_cd'] = $request_data['plat_cd'];
		$where['deleted_at'] = ['EXP', 'IS NULL']; // 不要显示已删除的数据
		$res = $logistics_model->where($where)->find();
		if($res) return '1';
		$data['type'] = '1';
		$data['search_data'] = $request_data['order_id'];
		$data['plat_cd'] = $request_data['plat_cd'];
		$res = $this->get_handle_order_associate($data);
		if(!$res) return '2';
		if ($res !== $request_data['tracking_number']) {
			return '3';
		}

		return false;

	}
	public function manual_create($request_data = [])
	{
		
		$check_res = $this->check_valid_handle_order($request_data);
		if ($check_res) {
			if ($check_res == '1') $error_info = '手工添加中订单号（同一个店铺下）不能重复';
			if ($check_res == '2') $error_info = '该订单号/运单号不存在，请核实后再输入';
			if ($check_res == '3') $error_info = '该运单号不存在，请核实后再输入';

			throw new Exception($error_info);
		}
		// 更新两张表
		// tb_lgt_tracking 物流轨迹表
		$logistics_model = M('logistics_thr_api', 'tb_ms_');
				
		$add = [];
		$add['api_numbers'] = '0';
		$add['fee_status'] = '1';
		$add['api_request_status_cd'] = 'N003030009';
		$add['logistics_status_cd'] = $request_data['b5c_logistics_status'];
		$add['plat_cd'] = $request_data['plat_cd'];
		$add['api_platform_cd'] = self::$handle_type; // 手动添加
		$add['company_cd'] = $request_data['logistics_cd'];
		$add['order_no'] = $request_data['order_id'];
		$add['logistics_no'] = $request_data['tracking_number'];
		$add['remark'] = $request_data['remark'];
		$add['created_by'] = $this->user_name;
		$add['created_at'] = date("Y-m-d H:i:s", time());
		$res = $logistics_model->add($add);
		if ($res !== false) {
			$addData = [];
			$lgt_track_model = M('tracking', 'tb_lgt_');
			$real_company_details_model = M('real_company_details','tb_lgt_');
			$real_where['logistics_cd'] = $request_data['logistics_cd'];
			$real_where['store_cd'] = $request_data['store_cd'];

			$addData['create_time'] = date("Y-m-d H:i:s", time());
			$addData['source_type'] = '1';
			$addData['plat_cd'] = $request_data['plat_cd'];
			$addData['order_id'] = $request_data['order_id'];
			$addData['logistics_cd'] = $request_data['logistics_cd'];
			$addData['tracking_number'] = $request_data['tracking_number'];
			$addData['b5c_logistics_status'] = $request_data['b5c_logistics_status'];
			$addData['status'] = cdVal($request_data['b5c_logistics_status']);
			$carrier_code = $real_company_details_model->where($real_where)->getField('com_sort_name');
			if(!$carrier_code) $carrier_code = '';
			$addData['carrier_code'] = $carrier_code;
			foreach ($request_data['content_cn'] as $key => $value) {
				$addData['language'] = 'N000920100';
				$addData['status_description'] = $value['status_description'];
				$addData['date'] = $value['date'];
				$lgt_track_model->add($addData);
			}
			foreach ($request_data['content_en'] as $key => $value) {
				$addData['language'] = 'N000920200';
				$addData['status_description'] = $value['status_description'];
				$addData['date'] = $value['date'];
				$lgt_track_model->add($addData);
			}
			$logistics_model->commit();
		} else {

            throw new Exception($logistics_model->getError());
			$logistics_model->rollback();		}
		return $res;
	}

	public function get_handle_order_associate($request_data = [])
	{
		$ord_pkg_model = M('ord_package', 'tb_ms_');
		$field = ''; $where_field = ''; $where = [];
		switch (strval($request_data['type'])) {
			case '1': // 根据订单号
				$where_field = 'ORD_ID';
				$field = 'TRACKING_NUMBER';
				break;
			case '2': // 根据运单号
				$where_field = 'TRACKING_NUMBER';
				$field = 'ORD_ID';
				break;
		};
		$where[$where_field] = array('eq', $request_data['search_data']);
		$where['plat_cd'] = $request_data['plat_cd'];
		$res = $ord_pkg_model->where($where)->getField($field);
		return $res;
	}

	public function delete_thr_data($request_data = [])
	{
		// 根据id获取信息
		$logistics_model = M('logistics_thr_api', 'tb_ms_');
		$info = $logistics_model->find($request_data['id']);
		if (!$info) {
			throw new Exception('没找到该id对应的记录信息');
		}
		if ($info['api_platform_cd'] !== self::$handle_type) {
			throw new Exception('该id不是手工运单类型，无法删除');
		}
		// 更新删除信息
		$where['id'] = ['eq', $request_data['id']];
		$save['deleted_by'] = $this->user_name;
		$save['deleted_at'] = date('Y-m-d H:i:s', time());
		$res = $logistics_model->where($where)->save($save);
		if (false === $res) {
			throw new Exception('更新删除信息失败');
		}
		// 更新轨迹详情表，更改类型
		$lgt_track_model = M('tracking', 'tb_lgt_');
		$where_data['source_type'] = '1';
		$where_data['order_id'] = $info['order_no'];
		$where_data['plat_cd'] = $info['plat_cd'];
		$where_data['tracking_number'] = $info['logistics_no'];
		$save_data['source_type'] = '2';
		$data_res = $lgt_track_model->where($where_data)->save($save_data);
		if (false === $data_res) {
			throw new Exception('更新删除具体运单轨迹信息失败');
		}
		return true;

	}

	public function get_thr_List($request_data = [], $is_export = false)
	{
		$search_map = [
		    'api_platform_cd'        => 'api_platform_cd',
		    'logistics_status_cd'        => 'logistics_status_cd',
		    'company_cd'        => 'company_cd',
		    'updated_at'             => 'updated_at',
		];
		if ($request_data['search']['company']) {
			$Model = M();
			$where['CD_VAL'] = ['LIKE', $request_data['search']['company']];
			$cd = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where)->find();
			$request_data['search']['company_cd'] = $cd['CD'];
			unset($request_data['search']['company']);
		}
		$exact_search = ['api_platform_cd', 'company_cd', 'logistics_status_cd'];
		list($where, $limit)   = WhereModel::joinSearchTemp($request_data, $search_map, "", $exact_search);
		// 模糊查询，
		if (!empty($request_data['search']['value']) && is_array($request_data['search']['value'])) {
			foreach ($request_data['search']['value'] as $k => $v) {
				$logic = 'OR';
				if ($k == '0') $logic = ''; 
				$where['_string'] .= " {$logic} ( {$request_data['search']['type']} like '%{$v}%') ";
			}
		}
		$where['deleted_at'] = ['EXP', 'IS NULL']; // 不要显示已删除的数据

		$thr_api_model   = M('thr_api', 'tb_ms_logistics_');
		$amount_total = $thr_api_model->where($where)->sum('api_numbers');
		$pages['total']        = $thr_api_model->where($where)->count();
		$pages['current_page'] = empty($limit[0]) ? '0' : $limit[0];
		$pages['per_page']     = $limit[1];
		if ($is_export) {
			$list = $thr_api_model->where($where)->order('updated_at desc')->select();
		} else {
			$list = $thr_api_model->where($where)->limit($limit[0], $limit[1])->order('updated_at desc')->select();
		}
		if (!empty($list)) {
		    $list = CodeModel::autoCodeTwoVal(
		        $list, [
		        'company_cd',
		        'api_platform_cd',
		        'logistics_status_cd',
		        'api_request_status_cd'
		        ]
		    );
		}

		$tem = [];
		foreach ($list as $key => $item) {
			$item['fee'] = $item['fee_status'] === '1' ? '免费' : '收费';
			if ($item['api_platform_cd_val'] == 'Tracking More') {
				$item['fee'] = '0.01元/每单';
			}
			$tem[] = $item;
		}
		$list = $tem;
		$Date_1 			= 	$request_data['search']['updated_at']['start'];
		$Date_2 			= 	$request_data['search']['updated_at']['end'];
		$d1 				= 	strtotime($Date_1);
		$d2 				= 	strtotime($Date_2);
		$d2 				= 	$d2 + 60 * 60 * 24;
		$Days 				= 	round(($d2-$d1)/3600/24);
		$amount['average'] 	= 	round($pages['total'] / $Days);// 日均单 结果/查询时间天数
		$amount['total'] 	= 	$amount_total; // 调用api总次数
		return [
		    'data' 			=> 	$list,
		    'amount' 		=> 	$amount,
		    'pages' 		=> 	$pages
		];
	}
}