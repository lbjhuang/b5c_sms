<?php
/**
 * 归属调拨
 * User: yangsu
 * Date: 2019/8/26
 * Time: 14:05
 */

/**
 * Class AllocationExtendAttributionAction
 */
class AllocationExtendAttributionAction extends BaseAction
{

    /**
     * @var AllocationExtendAttributionService
     */
    protected $service;

    /**
     * @return bool|void
     */
    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        } elseif (empty(DataModel::userNamePinyin())) {
            session('m_loginname', $_GET['user']);
        }
        $_REQUEST['transfer_type'] = 2;
        $_REQUEST['allo_type'] = 2;
        $this->service = new AllocationExtendAttributionService();
    }

    /**
     *
     */
    public function index()
    {
        try {
            $data = DataModel::getDataNoBlankToArr();
            $this->validateIndex($data);
            $res = DataModel::$success_return;
            list($res['body']['lists'], $res['body']['page']) = $this->service->index($data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateIndex($data)
    {
        $rule = [
            "search.review_type_cd" => "sometimes|string|size:10",
            "search.change_order_no" => "sometimes|string",
            "search.sku_id" => "sometimes|string",
            "search.change_type_cd" => "sometimes|string|size:10",
            "search.review_by" => "sometimes|string",
            "search.created_by" => "sometimes|string",
            "search.created_at" => "sometimes|date",

            'pages.per_page' => 'sometimes|required|numeric',
            'pages.current_page' => 'sometimes|required|numeric',
        ];
        $custom_attribute = [
            "search.review_type_cd" => "库存归属变更单状态",
            "search.change_order_no" => "库存归属变更单号",
            "search.change_type_cd" => "变更类型",
            "search.review_by" => "审核人",
            "search.created_by" => "发起人",
            "search.created_at" => "发起时间",

            'pages.per_page' => '每页数',
            'pages.current_page' => '当前页',
        ];
        $this->validate($rule, $data, $custom_attribute);
    }


    /**
     *
     */
    public function show($id = null, $is_return = false)
    {
        try {
            if ($id) {
                $allo_attribution_id = $id;
            } else {
                $allo_attribution_id = I('id');
            }
            $change_order_no = I('change_order_no');
            $this->validateShow($allo_attribution_id, $change_order_no);
            $res = DataModel::$success_return;
            $res['body'] = $this->service->show($allo_attribution_id, $change_order_no);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        if ($is_return) {
            return $res;
        }
        $this->ajaxReturn($res);
    }

    private function validateShow($data)
    {

    }

    /**
     *
     */
    public function approval()
    {
        try {
            $Model = M();
            $Model->startTrans();
            $data = DataModel::getDataNoBlankToArr();
            $this->validateApproval($data);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendAttributionService($Model);
            if (!$AllocationExtendNewService->isLastReviewer($data['id'])) {
                //不是最后一个审批人，进入下一个人审核
                $res['body'] = $AllocationExtendNewService->nextApproval($data);
            } else {
                $res['body'] = $AllocationExtendNewService->approval($data);
            }
            $Model->commit();
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    private function validateApproval($data)
    {
        $rule = [
            'id' => 'required|numeric',
            'review_type_cd' => 'required|string|size:10',
        ];
        $custom_attribute = [
            'id' => 'ID',
            'review_type_cd' => '审批状态',
        ];
        $this->validate($rule, $data, $custom_attribute);

        $attr_data = (new AllocationExtendAttributionRepository())->findAttrById($data['id']);
        if (strtolower($attr_data['review_by']) != strtolower(DataModel::userNamePinyin())) {
            //当前系统登录账号不是当前审核人
            throw new Exception(L('您不可以审批库存归属变更单' . $attr_data['change_order_no']));
        }
        if ($attr_data['allo_id']) {
            throw new Exception(L('调拨单关联的库存归属变更单不支持审核操作，归属变更单' . $attr_data['change_order_no']));
        }
        if ($attr_data['review_type_cd'] != TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT) {
            throw new Exception(L('库存归属变更单'. $attr_data['change_order_no']. '状态不符，请重新勾选！'));
        }
    }

    /**
     *
     */
    public function create_new_process()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->create_new_process();
    }

    /**
     *
     */
    public function show_allo_data()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->show_allo_data(true);
    }

    /**
     *
     */
    public function update_or_add_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->update_or_add_allo();
    }

    /**
     * 下载凭证
     */
    public function download_file()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->download_file();
    }
    
    public function StockOwnerChangeImportExcel()
    {
        if (IS_POST) {
            $AllocationExtendAction = new AllocationExtendAction();
            $params = $this->getParams();
            $model = new StockOwnerChangeImportExcelModel();
            $import = $model->import();
            if ($import ['code'] == 200) {
                // SKU 是否可调用、数量是否够调用验证
                foreach ($import ['data'] as $datum) {
                    $temp_imports[$datum['sku'] . $datum['batch_code']] = $datum;
                }
                $alloSku = array_column($import ['data'], 'sku');
                $params ['sku'] = $alloSku;
                // 保存调拨
                $r = $AllocationExtendAction->searchStockModel($params, true);

                $error = null;
                // 全部SKU都查询到
                if ($r ['count'] == count($import ['data']) || $r ['count'] > count($import ['data'])) {
                    foreach ($r ['ret'] as $key => $value) {
                        if ($value ['available_for_sale_num_total'] < $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num']) {
                            $error [$model->cacheSku [$value ['SKU_ID']]] = $value ['SKU_ID'] . ':当前需调拨的数量为：' . $import ['data'][$value ['SKU_ID']]['num'] . '  大于可调拨数量:' . $value ['available_for_sale_num_total'];
                        }
                    }
                } else {
                    $existSku = array_column($r ['ret'], 'SKU_ID');
                    $diff = array_diff($alloSku, (array)$existSku);
                    foreach ($diff as $key => $value) {
                        $error [$model->cacheSku [$value]] = $value . ':未查询到数据';
                    }
                }
                if ($error) {
                    $response = $this->formatOutput(300, L('导入失败'), $error);
                } else {
                    // 勾选调拨
                    $processChild = new TbWmsAlloProcessChildModel();
                    $saveData = null;
                    list($key, $uuid) = explode('_', $params ['token']);
                    $Model = new Model();
                    $where['uuid'] = $uuid;
                    $allo_process = $Model->table('tb_wms_allo_process')->where($where)->find();
                    foreach ($r ['ret'] as $key => $value) {
                        if ($value ['available_for_sale_num_total'] >= $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num'] && isset($temp_imports[$value['SKU_ID'] . $value['batch_code']])) {
                            $tmp = null;
                            $tmp ['uuid'] = $uuid;
                            $tmp ['sku_id'] = $value ['SKU_ID'];
                            $tmp ['out_team'] = $allo_process['attribution_team_cd'];
                            $tmp ['out_warehouse'] = $value['warehouse_cd'];
                            $tmp ['positive_defective_type_cd'] = $value ['vir_type_cd'];
                            $tmp ['num'] = $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num'];
                            $tmp ['batch_id'] = $value['batch_id'];
                            $tmp ['out_small_team'] = $value['small_sale_team_code'];
                            $saveData [] = $tmp;
                        }
                    }
                    $all_allo_process = $Model->table('tb_wms_allo_process_child')->where($where)->select();
                    if (false !== $processChild->addAll($saveData)) {
                        $where_delete['id'] = ['IN', array_column($all_allo_process, 'id')];
                        if (!empty($where_delete['id'])) {
                            $Model->table('tb_wms_allo_process_child')->where($where_delete)->delete();
                        }
                        $response = $this->formatOutput(200, L('已自动勾选需调拨的SKU'), null);
                    } else {
                        $response = $this->formatOutput(300, $processChild->getDbError(), null);
                    }
                }
            } else {
                $response = $this->formatOutput($import ['code'], L('导入失败'), $import ['data']);
            }

            $this->ajaxReturn($response, 'json');
        } else {
            $response = $this->formatOutput(300, L('请求异常'), null);

            $this->ajaxReturn($response, 'json');
        }
    }    
    /**
     *
     */
    public function importExcel()
    {
        if (IS_POST) {
            $AllocationExtendAction = new AllocationExtendAction();
            $params = $this->getParams();
            $model = new AlloImportExcelNewModel();
            $import = $model->import();
            if ($import ['code'] == 200) {
                // SKU 是否可调用、数量是否够调用验证
                foreach ($import ['data'] as $datum) {
                    $temp_imports[$datum['sku'] . $datum['vir_type']] = $datum;
                }
                $alloSku = array_column($import ['data'], 'sku');
                $params ['sku'] = $alloSku;
                // 保存调拨
                $r = $AllocationExtendAction->searchModel($params, true);
                $error = null;
                // 全部SKU都查询到
                if ($r ['count'] == count($import ['data']) || $r ['count'] > count($import ['data'])) {
                    foreach ($r ['ret'] as $key => $value) {
                        if ($value ['available_for_sale_num_total'] < $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num']) {
                            $error [$model->cacheSku [$value ['SKU_ID']]] = $value ['SKU_ID'] . ':当前需调拨的数量为：' . $import ['data'][$value ['SKU_ID']]['num'] . '  大于可调拨数量:' . $value ['available_for_sale_num_total'];
                        }
                    }
                } else {
                    $existSku = array_column($r ['ret'], 'SKU_ID');
                    $diff = array_diff($alloSku, (array)$existSku);
                    foreach ($diff as $key => $value) {
                        $error [$model->cacheSku [$value]] = $value . ':未查询到数据';
                    }
                }
                if ($error) {
                    $response = $this->formatOutput(300, L('导入失败'), $error);
                } else {
                    // 勾选调拨
                    $processChild = new TbWmsAlloProcessChildModel();
                    $saveData = null;
                    list($key, $uuid) = explode('_', $params ['token']);
                    $Model = new Model();
                    $where['uuid'] = $uuid;
                    $allo_process = $Model->table('tb_wms_allo_process')->where($where)->find();
                    foreach ($r ['ret'] as $key => $value) {
                        if ($value ['available_for_sale_num_total'] >= $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num']) {
                            $tmp = null;
                            $tmp ['uuid'] = $uuid;
                            $tmp ['sku_id'] = $value ['SKU_ID'];
                            $tmp ['out_team'] = $allo_process['attribution_team_cd'];
                            $tmp ['out_warehouse'] = $value['warehouse_cd'];
                            $tmp ['positive_defective_type_cd'] = $value ['vir_type_cd'];
                            $tmp ['num'] = $temp_imports[$value['SKU_ID'] . $value['batch_code']]['num'];
                            $tmp ['batch_id'] = $value['batch_id'];
                            $tmp ['out_small_team'] = $value['small_sale_team_code'];
                            $saveData [] = $tmp;
                        }
                    }
                    $all_allo_process = $Model->table('tb_wms_allo_process_child')->where($where)->select();
                    if ($processChild->addAll($saveData)) {
                        $where_delete['id'] = ['IN', array_column($all_allo_process, 'id')];
                        if (!empty($where_delete['id'])) {
                            $Model->table('tb_wms_allo_process_child')->where($where_delete)->delete();
                        }
                        $response = $this->formatOutput(200, L('已自动勾选需调拨的SKU'), null);
                    } else {
                        $response = $this->formatOutput(300, $processChild->getDbError(), null);
                    }
                }
            } else {
                $response = $this->formatOutput($import ['code'], L('导入失败'), $import ['data']);
            }

            $this->ajaxReturn($response, 'json');
        } else {
            $response = $this->formatOutput(300, L('请求异常'), null);

            $this->ajaxReturn($response, 'json');
        }
    }

    /**
     * 发起调拨
     */
    public function launch_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->launch_allo();
    }

    public function update_or_add_all_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        unset($_REQUEST['selected_state']);
        $AllocationExtendAction->update_or_add_all_allo();
    }

    /**
     * 批量审核
     */
    public function batchApproval()
    {
        $model = new Model();
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateBatchApproval($request_data);
            $rClineVal    = RedisModel::lock(__CLASS__.__FUNCTION__, 20);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 2000;
            $review_type_cd = $request_data['review_type_cd'];
            foreach ($request_data['ids'] as $id) {
                //由于java api不支持批量，所以单独事务依次处理
                $model->startTrans();
                $data = [
                    'id' => $id,
                    'review_type_cd' => $review_type_cd,
                ];
                if (!$this->service->isLastReviewer($data['id'])) {
                    //不是最后一个审批人，进入下一个人审核
                    $this->service->nextApproval($data);
                } else {
                    $this->service->approval($data);
                }
                $model->commit();
            }
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        RedisModel::unlock(__CLASS__.__FUNCTION__);
        $this->ajaxReturn($res);
    }

    private function validateBatchApproval($request_data)
    {
        if (empty($request_data['ids']) || !$request_data['review_type_cd']) {
            throw new Exception('请求参数有误');
        }
        $attr_data = (new AllocationExtendAttributionRepository())->getAttrByIds($request_data['ids']);
        foreach ($attr_data as $item) {
            if (strtolower($item['review_by']) != strtolower(DataModel::userNamePinyin())) {
                //当前系统登录账号不是当前审核人
                throw new Exception(L('您不可以审批库存归属变更单' . $item['change_order_no']));
            }
            if ($item['allo_id']) {
                throw new Exception(L('调拨单关联的库存归属变更单不支持审核操作，归属变更单' . $item['change_order_no']));
            }
            if ($item['review_type_cd'] != TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT) {
                throw new Exception(L('库存归属变更单'. $item['change_order_no']. '状态不符，请重新勾选！'));
            }
        }
    }

    public function cancel()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (empty($request_data['id'])) {
                throw new Exception('请求参数有误');
            }
            $rClineVal    = RedisModel::lock(__CLASS__.__FUNCTION__. $request_data['id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $model = new Model();
            $model->startTrans();
            $res         = DataModel::$success_return;
            $res['code'] = 2000;
            $res['data'] = $this->service->cancelAllocateAttribution($request_data['id']);
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        RedisModel::unlock(__CLASS__.__FUNCTION__. $request_data['id']);
        $this->ajaxReturn($res);
    }
}