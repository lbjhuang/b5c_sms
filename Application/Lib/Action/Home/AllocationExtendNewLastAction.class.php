<?php
/**
 * 新调拨
 * User: yangsu
 * Date: 2019/6/12
 * Time: 16:05
 */

class AllocationExtendNewLastAction extends BaseAction
{
    /**
     * @var array
     */
    private $allow_operation_arr = ['save', 'submit'];

    /**
     * @return bool|void
     */
    private $fee_types = [
        'service_fee',
        'logistics_costs',
        'tariff_sum',
    ];

    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        } elseif (empty(DataModel::userNamePinyin())) {
            session('m_loginname', $_GET['user']);
        }
        $_REQUEST['transfer_type'] = 1;
        $_REQUEST['allo_type'] = 1;
    }

    /**
     * @return string
     */
    protected function getAlloId()
    {
        $allo_id = $_GET['id'];
        $allo_id or $allo_id = $_GET['allo_id'];
        return htmlspecialchars($allo_id);
    }

    /**
     * @param $id
     *
     * @throws Exception
     */
    private function verificationAlloId($allo_id)
    {
        if (empty($allo_id)) {
            throw new Exception(L('调拨 ID 不能为空'));
        }
    }

    /**
     * 库存调拨
     * 每个sku有多少个销售团队就展示多少条数据
     */
    public function index()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->newIndex();
    }

    /**
     * 查看详情
     */
    public function show()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->show();
    }

    /**
     * 拒绝调拨
     */
    public function disagree()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->disagree();
    }

    /**
     * 撤回
     */
    public function remove()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->remove();
    }

    /**
     * 创建新的流程(新建调拨单)
     */
    public function create_new_process()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->create_new_process();
    }

    /**
     * 流程创建后，页面内的数据
     */
    public function show_allo_data()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->show_allo_data();
    }

    /**
     * 修改或给该流程新增子数据
     */
    public function update_or_add_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->update_or_add_allo();
    }

    /**
     * 全部调拨
     */
    public function update_or_add_all_allo()
    {

        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->update_or_add_all_allo();
    }

    /**
     * 返回上一步
     * 关闭当前流程
     */
    public function lastStep()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->lastStep();
    }

    /**
     * 打印拣货单
     */
    public function print_picking_list()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->print_picking_list();
    }

    /**
     * 发起调拨
     */
    public function launch_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->launch_allo();
    }

    /**
     * 撤回调拨
     */
    public function receive()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->receive();
    }

    /**
     * 同意调拨
     */
    public function agree()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->agree();
    }

    /**
     * 入库确认
     */
    public function confirm_storage()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->confirm_storage();
    }

    /**
     * 出库确认
     */
    public function confirm_outgoing()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->confirm_outgoing();
    }


    /**
     * 下载凭证
     */
    public function download_file()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->download_file();
    }

    /**
     * 出入库订单获取
     */
    public function orderVoucher()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->orderVoucher();
    }

    /**
     * 出库展示
     */
    public function out_or_allo()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->out_or_allo();
    }

    /**
     *
     */
    public function getAlloDetail($allo_id = null, $is_internal = false)
    {
        try {
            if (false == $is_internal) {
                $allo_id = $this->getAlloId();
            }
            
            $this->verificationAlloDetail($allo_id);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewLastService();
            $temp_data = (new AllocationExtendNewTransformer())->transformAlloInfo(
                $AllocationExtendNewService->getAlloDetail($allo_id)
            );
            Logs(['$temp_data' => $temp_data], __FUNCTION__, __CLASS__);
            $this->getFeeSummary($temp_data, $AllocationExtendNewService, $allo_id);
            Logs(['$temp_data' => $temp_data], __FUNCTION__, __CLASS__);
            $res['data'] = (new AllocationExtendNewFormatter())->formatterAlloInfo($temp_data);
            Logs(['$temp_data' => $temp_data], __FUNCTION__, __CLASS__);

            foreach ($res['data']['goods'] as &$item) {
                //【本次出库（残次品）】默认值=【调拨数量（残次品）】-【已出库（残次品）】
                $item['this_out_defective_products'] = $item['transfer_defective_products'] - $item['number_defective_outbound'] ? : 0;
                //【本次出库（正品）】默认值=【调拨数量（正品）】-【已出库（正品）】
                $item['this_out_authentic_products'] = $item['transfer_authentic_products'] - $item['number_authentic_outbound'] ? : 0;

                $absolute_value = 0;
                //【本次入库（残次品）】默认值=【已出库（残次品）】-【已入库（残次品）】，如果大于等于0则显示计算出来的值，如果为负数则显示0，且负数取绝对值记做b。
                $item['this_in_defective_products'] = $item['number_defective_outbound'] - $item['number_defective_warehousing'];
                if ($item['this_in_defective_products'] < 0) {
                    $absolute_value = abs($item['this_in_defective_products']);
                    $item['this_in_defective_products'] = 0;
                }
                //本次入库（正品）】默认值=【已出库（正品）】-【已入库（正品）】-b。
                $item['this_in_authentic_products'] = $item['number_authentic_outbound'] - $item['number_authentic_warehousing'] - $absolute_value ? : 0;
            }

//        $goods['total_stock_defective_num'] = array_sum(array_column($goods, 'stock_defective_num')) ? : 0;
//        $goods['total_stock_authentic_num'] = array_sum(array_column($goods, 'stock_authentic_num')) ? : 0;

            if ($is_internal) {
                return $res['data'];
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @see AllocationExtendNewService
     *
     * @param $data
     * @param $AllocationExtendNewService
     * @param $allo_id
     */
    private function getFeeSummary(&$data, $AllocationExtendNewService, $allo_id)
    {
        $acquisition_fee_details = $AllocationExtendNewService->acquisitionFeeDetails($allo_id);
        $data['info']['service_fee'] = array_sum(
            array_column($acquisition_fee_details['service_fee'], 'fee_amount')
        );
        $data['info']['logistics_costs'] = array_sum(
            array_column($acquisition_fee_details['logistics_costs'], 'fee_amount')
        );
        $data['info']['tariff_sum'] = array_sum(
            array_column($acquisition_fee_details['tariff_sum'], 'fee_amount')
        );
    }

    /**
     * @param $allo_id
     *
     * @throws Exception
     */
    private function verificationAlloDetail($allo_id)
    {
        $this->verificationAlloId($allo_id);
    }

    /**
     *
     */
    public function editAlloInfo()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $data = DataModel::getDataNoBlankToArr();
            if ('save' != $data['type']) {
                $this->verificationEditAlloInfo($allo_id, $data);
            }
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->editAlloInfo($allo_id, $data, $Model);
            $Model->commit();
            switch ($data['type']) {
                case 'submit':
                    (new ReviewMsgTpl())->sendWeChatReviewTransfer(
                        $AllocationExtendNewService->assemblyWeChatData($allo_id),
                        $AllocationExtendNewService->getAlloSaleLeader($allo_id)
                    );
                    $this->sendReviewMail($allo_id);
                    $log_msg = '提交审核';
                    break;
                case 'save':
                    $log_msg = '保存草稿';
                    break;
            }
            $this->addLog($allo_id, $log_msg);
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationEditAlloInfo($allo_id, $data)
    {
        $this->verificationAlloId($allo_id);
        if (empty($allo_id)) {
            throw new Exception(L('调拨 ID 异常'));
        }
        if (!in_array($data['type'], $this->allow_operation_arr)) {
            throw new Exception(L('调拨状态错误'));
        }
        $rules = [
            'type' => 'required|string',
            'info.expected_delivery_date' => 'required|date',
            'info.expected_warehousing_date' => 'required|date',
            'info.planned_transportation_channel_cd' => 'required|string|size:10',
        ];
        foreach ($data['goods'] as $key => $val) {
            $rules['goods.' . $key . '.sku_id'] = 'sometimes|string|size:10';
            $rules['goods.' . $key . '.tax_free_sales_unit_price_currency_cd'] = 'sometimes|string|size:10';
            $rules['goods.' . $key . '.tax_free_sales_unit_price'] = 'sometimes|numeric';
        }
        $custom_attributes = [
            'type' => '操作类型',
            'info.expected_delivery_date' => '期望出库日期',
            'info.expected_warehousing_date' => '期望入库日期',
            'info.planned_transportation_channel_cd' => '计划运输渠道',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function updateStatusAllo()
    {
        try {
            $allo_id = $this->getAlloId();
            $data = DataModel::getDataNoBlankToArr();
            $this->verificationUpdateStatusAllo($allo_id, $data);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->updateStatusAllo($allo_id, $data);

            switch ($data['type']) {
                case 'submit':
                    (new ReviewMsgTpl())->sendWeChatReviewTransfer(
                        $AllocationExtendNewService->assemblyWeChatData($allo_id),
                        $AllocationExtendNewService->getAlloSaleLeader($allo_id)
                    );
                    $this->sendReviewMail($allo_id);
                    $log_msg = '提交审核';
                    break;
                case 'save':
                    $log_msg = '保存草稿';
                    break;
                case 'delete':
                    $log_msg = '删除';
                    break;
            }
            $this->addLog($allo_id, $log_msg);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }


    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationUpdateStatusAllo($allo_id, $data)
    {
        $this->verificationAlloId($allo_id);
        if ($allo_id !== $data['id']) {
            throw new Exception(L('调拨 ID 异常'));
        }
        $temp_allow_operation_arr = ['delete', 'submit'];
        if (!in_array($data['type'], $temp_allow_operation_arr)) {
            throw new Exception(L('调拨状态错误'));
        }
    }

    /**
     *
     */
    public function updateReviewAllo()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            if ($_GET['api']) {
                $data['type'] = $_GET['type'];
                $data['user'] = $_GET['user'];
            } else {
                $data = DataModel::getDataNoBlankToArr();
            }
            $this->verificationUpdateReviewAllo($allo_id, $data);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService($Model);
            $res['data'] = $AllocationExtendNewService->updateReviewAllo($allo_id, $data);
            switch ($data['type']) {
                case 0:
                    $log_msg = '审核拒绝';
                    $AllocationExtendNewService->sendReviewWeChatMsg($data['user'], $allo_id, 0);
                    break;
                case 1:
                    $log_msg = '审核通过';
                    $AllocationExtendNewService->sendReviewWeChatMsg($data['user'], $allo_id, 1);
                    $AllocationExtendNewService->sendWorkWeChatMsg($allo_id);
                    break;
                case 2:
                    $log_msg = '退回待提交';
                    break;
            }
            $this->addLog($allo_id, $log_msg);
            $Model->commit();
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        if ($_GET['api']) {
            if (200 == $res['code']) {
                $res['msg'] = '审核成功';
            }
            echo iconv('utf-8', 'gbk', $res['msg']);
        } else {
            $this->ajaxReturn($res);
        }
    }


    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationUpdateReviewAllo($allo_id, $data)
    {
        $this->verificationAlloId($allo_id);

        $temp_allow_operation_arr = ['0', '1', '2'];
        if (!in_array($data['type'], $temp_allow_operation_arr)) {
            throw new Exception(L('审批操作状态错误'));
        }
    }

    /**
     *
     */
    public function submitWork()
    {
        try {
            set_time_limit(600);
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $data = DataModel::getDataNoBlankToArr();
            if (count($data['goods']) < 200) {
                $this->verificationSubmitWork($allo_id, $data);
            }
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitWork($data, $Model);
            $Model->commit();
            $this->addLog($allo_id, '确认仓库作业信息');
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationSubmitWork($allo_id, $data)
    {
        $this->verificationAlloId($allo_id);
        if ($allo_id !== $data['work']['allo_id']) {
            throw new Exception(L('调拨 ID 异常'));
        }
        if (!is_array($data['work']['job_photos'])) {
            throw new Exception(L('作业照片非正常格式数据'));
        }
        $rules = [
            'work.allo_id' => 'required|numeric',
            'work.beat_information' => 'required',
            'work.job_photos' => 'required',
            'work.job_note' => 'required',
            'work.operating_expenses_currency_cd' => 'required|string|size:10',
            'work.operating_expenses' => 'required|numeric',
            'work.value_added_service_fee_currency_cd' => 'required|string|size:10',
            'work.value_added_service_fee' => 'required|numeric',
        ];

        foreach ($data['goods'] as $key => $datum) {
            $rules['goods.' . $key . '.sku_id'] = 'required|string|size:10';
//            $rules['goods.' . $key . '.number_boxes'] = 'required|numeric';
//            $rules['goods.' . $key . '.number_per_box'] = 'sometimes|string|max:255';
//            $rules['goods.' . $key . '.case_number'] = 'sometimes|string|max:255';
//            $rules['goods.' . $key . '.box_length_and_width_cm'] = 'sometimes|string|max:255';
//            $rules['goods.' . $key . '.net_weight_kg'] = 'sometimes|numeric';
        }
        $custom_attributes = [
            'work.allo_id' => '调拨 id',
            'work.beat_information' => '打托信息',
            'work.job_photos' => '作业照片',
            'work.job_note' => '作业备注',
            'work.operating_expenses_currency_cd' => '作业费用币种',
            'work.operating_expenses' => '作业费用',
            'work.value_added_service_fee_currency_cd' => '增值服务费币种',
            'work.value_added_service_fee' => '增值服务费',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function waitingAssignmentWithdrawn()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $this->verificationWaitingAssignmentWithdrawn($allo_id);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService($Model);
            $res['data'] = $AllocationExtendNewService->waitingAssignmentWithdrawn($allo_id);
            $this->addLog($allo_id, '待作业撤回待提交');
            $Model->commit();
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $allo_id
     *
     * @throws Exception
     */
    private function verificationWaitingAssignmentWithdrawn($allo_id)
    {
        $this->verificationAlloId($allo_id);
    }

    /**
     *
     */
    public function getTransportCompany()
    {
        try {
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->getTransportCompany();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     *
     */
    public function submitOutStock()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            if (!RedisLock::lock('allo_id_' . $allo_id, 30)) {
                throw new Exception(L('allo_id_' . $allo_id . '调拨出库锁获取失败,该订单正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            if (count($data['goods']) < 200) {
                $this->verificationSubmitOutStock($allo_id, $data);
            }
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitOutStock($allo_id, $data, $Model);

            // 出库 调拨单产生 出库费用 头程物流费 保险费用
            $expensebill= new ExpensebillService();
            $ret = $expensebill->outStockPayment($allo_id,$data['logistics_information']);
            if (!$ret){
                throw new Exception('生成调拨应付失败');
            }

            $Model->commit();
            $this->addLog($allo_id, '确认出库');

        } catch (Exception $exception) {
            RedisLock::unlock('allo_id_' . $allo_id);
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationSubmitOutStock($allo_id, &$data)
    {
        $this->verificationAlloId($allo_id);
        $rules = [
            'logistics_information.transport_company_id' => 'required|numeric',
            'logistics_information.outbound_cost_currency_cd' => 'required|string|size:10',
            'logistics_information.outbound_cost' => 'required|numeric',
            'logistics_information.head_logistics_fee_currency_cd' => 'required|string|size:10',
            'logistics_information.head_logistics_fee' => 'required|numeric',
            'logistics_information.have_insurance' => 'required|numeric',
            /*   'logistics_information.insurance_claims_cd' => 'required|string|size:10',
               'logistics_information.insurance_coverage_cd' => 'required|string|size:10',
               'logistics_information.insurance_fee_currency_cd' => 'required|string|size:10',
               'logistics_information.insurance_fee' => 'required|numeric',*/
        ];
        foreach ($data['goods'] as $key => $datum) {
            $rules['goods.' . $key . '.sku_id'] = 'required|numeric';
            $rules['goods.' . $key . '.this_out_authentic_products'] = 'sometimes|numeric';
            $rules['goods.' . $key . '.this_out_defective_products'] = 'sometimes|numeric';
        }
        $custom_attributes = [
            'logistics_information.transport_company_id' => '运输公司',
            'logistics_information.outbound_cost_currency_cd' => '出库费用币种',
            'logistics_information.outbound_cost' => '出库费用',
            'logistics_information.head_logistics_fee_currency_cd' => '头程物流费用币种',
            'logistics_information.head_logistics_fee' => '头程物流费用',
            'logistics_information.have_insurance' => '有无保险',
            'logistics_information.insurance_claims_cd' => '保险理赔',
            'logistics_information.insurance_coverage_cd' => '保险范围',
            'logistics_information.insurance_fee_currency_cd' => '保险费用币种',
            'logistics_information.insurance_fee' => '保险费用',
        ];
        $this->validate($rules, $data, $custom_attributes);
        foreach ($data['goods'] as $key => $datum) {
            if (0 == $datum['this_out_authentic_products'] && 0 == $datum['this_out_defective_products']) {
                unset($data['goods'][$key]);
            }
        }
        if (empty($data['goods'])) {
            throw new Exception(L('请求出库数量错误'));
        }
    }


    /**
     *
     */
    public function submitInStock()
    {
        try {
            $res = DataModel::$success_return;
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            if (!RedisLock::lock('allo_id_' . $allo_id, 30)) {
                throw new Exception(L('allo_id_' . $allo_id . '调拨入库锁获取失败，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            if (count($data['goods']) < 200) {
                $this->verificationSubmitInStock($allo_id, $data);
            }
            $AllocationExtendNewService = new AllocationExtendNewService();
            $batch_ids = $AllocationExtendNewService->submitInStock($allo_id, $data, $Model);

            // 入库 调拨单产生 上架费用 增值服务费
            $expensebill= new ExpensebillService();
            $ret = $expensebill->inStockPayment($allo_id,$data['logistics_information']);
            if (!$ret){
                throw new Exception('生成调拨应付失败');
            }
            $Model->commit();

            // 调拨入库产生关联交易订单
            $relatedTransaction = new RelatedTransactionService();
            $ret = $relatedTransaction->verifyCompany($batch_ids,$allo_id,$data);
            if ($ret['code'] != 200){
                throw new Exception(L( $ret['msg']));
            }
            if (!empty($ret['data'])){
                  // 调用接口  处理关联交易订单
                $ret = $relatedTransaction->disposeRelTransOrder($ret['data']);
                if ($ret['code'] != 200){
                    throw new Exception(L( $ret['msg']));
                }
            }
            $this->addLog($allo_id, '确认入库');
            sleep(1);
        } catch (Exception $exception) {
            RedisLock::unlock('allo_id_' . $allo_id);
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationSubmitInStock($allo_id, &$data)
    {
        $this->verificationAlloId($allo_id);
        $rules = [
            'logistics_information.tariff_currency_cd' => 'required|string|size:10',
            'logistics_information.tariff' => 'required|numeric',
            'logistics_information.shelf_cost_currency_cd' => 'required|string|size:10',
            'logistics_information.shelf_cost' => 'required|numeric',
            'logistics_information.value_added_service_fee_currency_cd' => 'required|string|size:10',
            'logistics_information.value_added_service_fee' => 'required|numeric',
        ];
        foreach ($data['goods'] as $key => $datum) {
            $rules['goods.' . $key . '.sku_id'] = 'required|string|size:10';
            $rules['goods.' . $key . '.this_in_authentic_products'] = 'sometimes|numeric';
            $rules['goods.' . $key . '.this_in_defective_products'] = 'sometimes|numeric';
        }
        $custom_attributes = [
            'logistics_information.tariff_currency_cd' => '关税币种',
            'logistics_information.tariff' => '关税',
            'logistics_information.shelf_cost_currency_cd' => '上架费用币种',
            'logistics_information.shelf_cost' => '上架费用',
            'logistics_information.value_added_service_fee_currency_cd' => '增值服务费币种',
            'logistics_information.value_added_service_fee' => '	增值服务费',
        ];
        $this->validate($rules, $data, $custom_attributes);
        foreach ($data['goods'] as $key => $datum) {
            if (0 == $datum['this_in_authentic_products'] && 0 == $datum['this_in_defective_products']) {
                unset($data['goods'][$key]);
            }
        }
        if (empty($data['goods'])) {
            throw new Exception(L('请求入库数量错误'));
        }
    }

    /**
     * @param $allo_id
     * @param $log_msg
     */
    public function addLog($allo_id, $log_msg)
    {
        $AllocationExtendNewService = new AllocationExtendNewService();
        $AllocationExtendNewService->addLog($allo_id, $log_msg);
    }

    /**
     *
     */
    public function getLog()
    {
        try {
            $allo_id = $this->getAlloId();
            $this->verificationAlloId($allo_id);
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res = DataModel::$success_return;
            $res['data'] = (array)$AllocationExtendNewService->getLog($allo_id);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }


    /**
     *
     */
    public function outboundTagCompletion()
    {
        try {
            $allo_id = $this->getAlloId();
            $reason_difference = DataModel::getDataNoBlankToArr()['out_reason_difference'];
            if (empty($reason_difference)) {
                throw new Exception(L('请填写出库差异原因'));
            }
            $this->verificationAlloId($allo_id, $reason_difference);
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res = DataModel::$success_return;
            $res['data'] = (array)$AllocationExtendNewService->outboundTagCompletion($allo_id, $reason_difference);
            $this->addLog($allo_id, '出库标记完成');
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     *
     */
    public function inboundTagCompletion()
    {
        try {
            $allo_id = $this->getAlloId();
            $reason_difference = DataModel::getDataNoBlankToArr()['in_reason_difference'];
            if (empty($reason_difference)) {
                throw new Exception(L('请填写入库差异原因'));
            }
            $this->verificationAlloId($allo_id, $reason_difference);
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res = DataModel::$success_return;
            $res['data'] = (array)$AllocationExtendNewService->inboundTagCompletion($allo_id, $reason_difference);
            $this->addLog($allo_id, '入库标记完成');
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function showSendReviewMail()
    {
        $allo_id = $_GET['allo_id'] ? $_GET['allo_id'] : 1161;
        $AllocationExtendNewService = new AllocationExtendNewService();
        $allo_detail = $AllocationExtendNewService->getAlloDetail($allo_id);
        $data = (new AllocationExtendNewTransformer())->transformAlloInfo($allo_detail);
        $review_url = [
            'agree' => ERP_URL . "/index.php?m=AllocationExtendNew&a=updateReviewAllo&api=b08a8be1abd25efd858141757dbfc5c5&id={$allo_id}&user={$data['info']['reviewer_by']}&type=1",
            'disagree' => ERP_URL . "/index.php?m=AllocationExtendNew&a=updateReviewAllo&api=b08a8be1abd25efd858141757dbfc5c5&id={$allo_id}&user={$data['info']['reviewer_by']}&type=0",
        ];
        if (0 == $data['info']['transfer_use_type']) {
            $data['info']['transfer_use_type_val'] = '销售';
        }
        $this->assign('data', $data);
        $this->assign('review_url', $review_url);
        if (1 == $allo_detail['info']['transfer_use_type']) {
//            $template = 'Mail/send_review_mail_unsell';
            $template = 'Mail/send_review_mail';
        } else {
            $template = 'Mail/send_review_mail';
        }
        $this->display($template);
    }

    /**
     * @param $allo_id
     */
    public function sendReviewMail($allo_id, $user = null)
    {
        try {
            $AllocationExtendNewService = new AllocationExtendNewService();
            $allo_detail = $AllocationExtendNewService->getAlloDetail($allo_id);
            $data = (new AllocationExtendNewTransformer())->transformAlloInfo($allo_detail);
            $review_url = [
                'agree' => ERP_URL . "/index.php?m=AllocationExtendNew&a=updateReviewAllo&api=b08a8be1abd25efd858141757dbfc5c5&id={$allo_id}&user={$data['info']['reviewer_by']}&type=1",
                'disagree' => ERP_URL . "/index.php?m=AllocationExtendNew&a=updateReviewAllo&api=b08a8be1abd25efd858141757dbfc5c5&id={$allo_id}&user={$data['info']['reviewer_by']}&type=0",
            ];
            if (0 == $data['info']['transfer_use_type']) {
                $data['info']['transfer_use_type_val'] = '销售';
            }
            $this->assign('data', $data);
            $this->assign('review_url', $review_url);
            if (1 == $allo_detail['info']['transfer_use_type']) {
                $template = 'Mail/send_review_mail';
            } else {
                $template = 'Mail/send_review_mail';
            }
            $mail_message = $this->fetch($template);
            Logs($mail_message, __FUNCTION__, __CLASS__);
            if (empty($user)) {
                $user = $data['info']['reviewer_by'];
            }
            $mail_data = [
                'user' => $user . '@gshopper.com',
                'cc' => null,
                'title' => '调拨审核邮件',
                'message' => $mail_message,
            ];
            if ('erpadmin' == strtolower($data['info']['reviewer_by'])) {
                $mail_data['user'] = 'yangsu@gshopper.com';
            }
            $res['data'] = MailModel::sendMail($mail_data);
            if (false === $res['data']) {
                throw new Exception(L('审核邮件发送异常'));
            }
            $log_msg = '审核邮件发送';
        } catch (Exception $exception) {
//            @SentinelModel::addAbnormal('调拨邮件发送失败', $allo_id . '异常', $res, 'transfer_by');
            Logs($res, __FUNCTION__, __CLASS__);
            $log_msg = '审核邮件发送异常';
            $res = $this->catchException($exception);
        }
        $this->addLog($allo_id, $log_msg);
    }

    public function acquisitionFeeDetails()
    {
        try {
            $allo_id = $this->getAlloId();
            $fee_type = I('fee_type');
            $this->verificationAcquistionFeeDetails($allo_id, $fee_type);
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res = DataModel::$success_return;
            $acquisition_fee_details = $AllocationExtendNewService->acquisitionFeeDetails($allo_id);
            $res['data'] = (array)$acquisition_fee_details[$fee_type];
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function verificationAcquistionFeeDetails($allo_id, $fee_type)
    {
        $this->verificationAlloId($allo_id);
        if (!in_array($fee_type, $this->fee_types)) {
            throw new Exception(L('请输入正确的费用类型'));
        }
    }

    public $cacheSku = null;
    public $cacheSkuNew = null;
    public function importExcel()
    {
        if (IS_POST) {
            $AllocationExtendAction = new AllocationExtendAction();
            $params = $this->getParams();
            $model = new AlloImportExcelNewModel();
            $import = $model->import();
            $this->cacheSku = $model->cacheSku;
            $this->cacheSkuNew = $model->cacheSkuNew;
            if ($import ['code'] == 200) {
                $error = null;
                // SKU 是否可调用、数量是否够调用验证
                $alloSku = array_column($import ['data'], 'sku');
                $GUDS_OPT_UPC_ID = array_column($import ['data'], 'GUDS_OPT_UPC_ID');
                $params ['sku'] = $alloSku;
                $params ['GUDS_OPT_UPC_ID'] = $GUDS_OPT_UPC_ID;
                // 保存调拨
                unset($params['sell_small_team_cd']); // 筛选条件只有调拨直接用途，销售团队，调出仓库，调入仓库（不包括销售小团队）
                $r = $AllocationExtendAction->searchModel($params, true);

                //导入模板改版 第一二行为提示、标题
                if ($import ['data'][0]['sku'] == 'SKU编码') array_shift($import ['data']);
                $error = $this->checkoutImport($import ['data'], $r ['ret'], $error);
                // 全部SKU都查询到
                $positiveCodes = CodeModel::getPositive();
                if ($error) {
                    $response = $this->formatOutput(300, L('导入失败'), $error);
                } else {
                    // 勾选调拨
                    $processChild = new TbWmsAlloProcessChildModel();
                    $saveData = null;
                    $skuUpc = array_column($r ['ret'], 'SKU_ID', 'upc');
                    list($key, $uuid) = explode('_', $params ['token']);
                    foreach ($import ['data'] as $key => $value) {
                        $tmp = null;
                        $tmp ['uuid'] = $uuid;
                        $tmp ['sku_id'] = $value['sku'] ? $value['sku'] : $skuUpc[$value['GUDS_OPT_UPC_ID']];
                        $tmp ['out_team'] = $params['out_team'];
                        $tmp ['out_warehouse'] = $params['out_warehouse'];
                        //$tmp ['out_store'] = $value['ascription_store'] == '无' ? '' : $value['ascription_store'];
                        $tmp ['out_store'] = '';
                        $tmp ['out_small_team'] = $value['small_sale_team_code'] == '无' ? '' : $value['small_sale_team_code'];
                        $tmp ['positive_defective_type_cd'] = $positiveCodes[$value['vir_type']];
                        $tmp ['num'] = $value['num'];
                        $saveData [] = $tmp;
                    }
                    $Model = new Model();
                    $where['uuid'] = $uuid;
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

    public function checkoutImport($importData, $checkData, $error = null)
    {
        $params = $this->getParams();
        //导入模板改版 第一二行为提示、标题
        if ($importData[0]['sku'] == 'SKU编码') array_shift($importData);
        //调拨直接用途 销售不能导入残次品
        $transfer_use_type = $params['transfer_use_type'];
        $virType = array_column($importData, 'vir_type');
        if ($transfer_use_type == 0 && in_array('残次品', $virType)) {
            $error [$this->cacheSku ['残次品']] = '销售调拨不能导入残次品';
        }

        //查询的sku和upc
        $existData = $existUpc = $existSku = $existStore = $existVirType = $existSmallTeam = [];
        foreach ($checkData as $value) {
            if (!empty($value['SKU_ID'])) {
                //$existStore[$value['SKU_ID']][] = $value['ascription_store'];
                $existSmallTeam[$value['SKU_ID']][] = $value['small_sale_team_code'];                
                $existVirType[$value['SKU_ID']][] = $value['vir_type'];
            }
            if (!empty($value['upc'])) {
                //$existStore[$value['upc']][] = $value['ascription_store'];
                $existSmallTeam[$value['upc']][] = $value['small_sale_team_code'];
                $existVirType[$value['upc']][] = $value['vir_type'];
            }
            $existSku[] = $value['SKU_ID'];
            $existUpc[] = $value['upc'];
        }
        $existSkuUpc = array_flip(array_merge($existSku, $existUpc));
        // 获取销售团队下的销售小团队
        $find_small_team['ETC'] = $params['out_team'];
        $find_small_team['CD_NM'] = '销售小团队';
        $small_team_arr = CodeModel::getCdByEtc($find_small_team);
        foreach ($importData as $key => $value) {
            // 判断小团队的值是否属于该销售团队

            if (empty($value['sku']) && empty($value['GUDS_OPT_UPC_ID'])) {
                $error [$this->cacheSku [$value['sku']]] = 'SKU与UPC必须存在一个';
            }
            if (!empty($value['sku']) && !isset($existSkuUpc[$value['sku']])) {
                $error [$this->cacheSku [$value['sku']]] = $value['sku'] . ':未查询到数据';
            }
            if (!empty($value['GUDS_OPT_UPC_ID']) && !isset($existSkuUpc[$value['GUDS_OPT_UPC_ID']])) {
                $error [$this->cacheSku [$value['GUDS_OPT_UPC_ID']]] = $value['GUDS_OPT_UPC_ID'] . ':未查询到数据';
            }
            $positiveCodes = CodeModel::getPositive();
            $value['vir_type_cd'] = $positiveCodes[$value['vir_type']];
            $value['upc'] = $value['GUDS_OPT_UPC_ID'];
            if ($value['sku']) {
                $index = 'sku';
            } else {
                $index = 'upc';
            }
            //同时判断sku 商品类型 归属店铺（替换为小团队）
            //$k = 'sku:'.$value['sku'] . '-商品类型:' . $value['vir_type'] . '-归属店铺:' . $value['ascription_store'];
            $k = 'sku:'.$value['sku'] . '-商品类型:' . $value['vir_type'] . '-销售小团队:' . $value['small_sale_team_code'];

            if (!in_array($value['vir_type_cd'], $existVirType[$value['sku']]) && !in_array($value['vir_type_cd'], $existVirType[$value['upc']])) {
                $error [$this->cacheSkuNew[$k] . 'C'] = ($index == 'upc' ? '条形码' : $index) . ':' . $value[$index] . '没有' . $value['vir_type'];
            }
            if ($value['small_sale_team_code'] !== '无') {
                // 判断是否为该销售团队的小团队
                if (!in_array($value['small_sale_team_code'], $small_team_arr)) {
                    $error [$this->cacheSkuNew[$k] . 'D'] = '该SKU的归属销售小团队' . $value['small_sale_team_code'] . '不属于该调出销售团队，请核对小团队名称是否正确';
                }
            }
            //$ascription_store = $value['ascription_store'] == '无' ? null : $value['ascription_store'];
            $small_sale_team_code = $value['small_sale_team_code'] == '无' ? null : $value['small_sale_team_code'];
            /*if (!in_array($ascription_store, $existStore[$value['sku']]) && !in_array($ascription_store, $existStore[$value['upc']])) {
                $error [$this->cacheSkuNew [$k] . 'D'] = '没有归属店铺' . $value['ascription_store'];
            }*/
            if (!in_array($small_sale_team_code, $existSmallTeam[$value['sku']]) && !in_array($small_sale_team_code, $existSmallTeam[$value['upc']])) {
                $error [$this->cacheSkuNew[$k] . 'D'] = '归属销售小团队' . $value['small_sale_team_code'] . '不在该页面的筛选条件下的结果范围中';
            }
            $value['key'] = $key + 3;
            $existData[$value[$index] . $value['vir_type_cd'] . $value['small_sale_team_code']] = $value;
        }
        foreach ($checkData as $key => $value) {
            //$value['ascription_store'] = !$value['ascription_store'] ? '无' : $value['ascription_store'];
            $value['small_sale_team_code'] = !$value['small_sale_team_code'] ? '无' : $value['small_sale_team_code'];
            $index1 = $value['SKU_ID'] . $value['vir_type'] . $value['small_sale_team_code'];
            $index2 = $value['upc'] . $value['vir_type'] . $value['small_sale_team_code'];
            if (isset($existData[$index1])) {
                if ($value ['available_for_sale_num_total'] < $existData[$index1]['num'] && $existData[$index1]['vir_type_cd'] == $value ['vir_type_cd']) {
                    $error [$existData[$index1]['key'] . 'A'] = $value ['SKU_ID'] . ':当前需调拨的数量为：' . $existData[$index1]['num'] . '  大于可调拨数量:' . $value ['available_for_sale_num_total'];
                }
                unset($existData[$index1]);
            } elseif  (isset($existData[$index2])) {
                if ($value ['available_for_sale_num_total'] < $existData[$index2]['num'] && $existData[$index2]['vir_type_cd'] == $value ['vir_type_cd']) {
                    $error [$existData[$index2]['key'] . 'B'] = $value ['upc'] . ':当前需调拨的数量为：' . $existData[$index2]['num'] . '  大于可调拨数量:' . $value ['available_for_sale_num_total'];
                }
                unset($existData[$index2]);
            }
        }
        return $error;
    }

}