<?php
/**
 * 新调拨
 * User: yangsu
 * Date: 2019/6/12
 * Time: 16:05
 */
import('ORG.Util.Date');// 导入日期类
class AllocationExtendNewAction extends BaseAction
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
     * @return string
     */
    protected function getOutStockId()
    {
        $out_stock_id = $_GET['out_stock_id'];
        $out_stock_id or $out_stock_id = $_GET['out_stock_id'];
        return htmlspecialchars($out_stock_id);
    }

    /**
     * @return string
     */
    protected function getNodeId()
    {
        $node_id = $_GET['node_id'];
        $node_id or $node_id = $_GET['node_id'];
        return htmlspecialchars($node_id);
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
    #调拨导出  复用列表搜索
    public function export(){
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->newIndex(true);
    }
    public function checkExport()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->checkoutExport();
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
     * 创建新的流程(编辑调拨单)
     */
    public function create_edit_process()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->create_edit_process();
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
     * 流程创建后，页面内的数据
     */
    public function show_allo_data_edit()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->show_allo_data_edit();
    }

    /**
     * 编辑页面获取sku的占用数据
     */
    public function get_allo_batch()
    {
        $params = $this->getParams();
        $allo_id = $params ['allo_id'];
        $AllocationExtendNewService = new AllocationExtendNewService();
        list($allo, $allo_batch) = $AllocationExtendNewService->allo_batch_data($allo_id);
        $map = array_column($allo_batch, 'sum_occupy_num', 'SKU_ID');
        #查询调拨是否包含了归属占用 
        // $attr = $AllocationExtendNewService->getAttrByAlloId($allo_id);
        // if (count($attr) > 0) {
        //     foreach ($attr as $value) {
        //         $map[$value['sku_id']] = $value['num'];
        //     }
        // }
        $res = DataModel::$success_return;
        $res['data'] = $allo_batch;
        $res['allo'] = $allo;
        $res['map'] = $map;
        $this->ajaxReturn($res, 'json');
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
     * 调拨完结-调拨详情
     */
    public function transportation()
    {
        #完结页面-新旧流程各自显示不同模板
        $allo_id = $this->getAlloId();
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $allo_stock_in = M('wms_allo_new_in_stocks', 'tb_')->where(['allo_id' => $allo_id, 'out_stock_id' => ['EXP', 'is null']])->select();
        if ($allo['state'] == 'N001970400' && count($allo_stock_in) > 0) {
            $this->display('transportation_last');
        } else {
            $this->display('transportation');
        }
    }

    private function checkAlloStatus($allo_id)
    {
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $allo_stock_in = M('wms_allo_new_in_stocks', 'tb_')->where(['allo_id' => $allo_id, 'out_stock_id' => ['EXP', 'is null']])->select();
        if ($allo['state'] != 'N001970400' && count($allo_stock_in) > 0) {
            return false;
        }
        return true;
    }

    private function checkEndAlloStatus($allo_id)
    {
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $allo_stock_in = M('wms_allo_new_in_stocks', 'tb_')->where(['allo_id' => $allo_id, 'out_stock_id' => ['EXP', 'is null']])->select();
        if ($allo['state'] == 'N001970400' && count($allo_stock_in) > 0) {
            return true;
        }
        return false;
    }

    /**
     *调拨详情-新版
     */
    public function getAlloDetail($allo_id = null, $is_internal = false)
    {
        try {
            if (false == $is_internal) {
                $allo_id = $this->getAlloId();
            }
            $this->verificationAlloDetail($allo_id);

            #查询该调拨为新旧流程归属
            if (!$this->checkAlloStatus($allo_id)) {
                throw new Exception(L('该调拨单属于旧流程数据，请走旧流程'));
            }

            if ($this->checkEndAlloStatus($allo_id)) {

                #走旧版接口  展示详情
                $alloLast = new AllocationExtendNewLastAction();
                $alloLast->getAlloDetail($allo_id);
            }

            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
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
                $item['this_out_defective_products'] = $item['transfer_defective_products'] - $item['number_defective_outbound'] ?: 0;
                //【本次出库（正品）】默认值=【调拨数量（正品）】-【已出库（正品）】
                $item['this_out_authentic_products'] = $item['transfer_authentic_products'] - $item['number_authentic_outbound'] ?: 0;
                $absolute_value = 0;
                //【本次入库（残次品）】默认值=【已出库（残次品）】-【已入库（残次品）】，如果大于等于0则显示计算出来的值，如果为负数则显示0，且负数取绝对值记做b。
                $item['this_in_defective_products'] = $item['number_defective_outbound'] - $item['number_defective_warehousing'];
                if ($item['this_in_defective_products'] < 0) {
                    $absolute_value = abs($item['this_in_defective_products']);
                    $item['this_in_defective_products'] = 0;
                }
                //本次入库（正品）】默认值=【已出库（正品）】-【已入库（正品）】-b。
                $item['this_in_authentic_products'] = $item['number_authentic_outbound'] - $item['number_authentic_warehousing'] - $absolute_value ?: 0;
            }

            $AllocationExtendAttributionService = new AllocationExtendAttributionService();
            $attr_id = $AllocationExtendAttributionService->getAttrIdByAlloId($allo_id);
            $attr = $AllocationExtendAttributionService->show($attr_id);
            $res['data']['attr'] = [];
            if (!empty($attr['info']['review_type_cd']) && $attr['info']['review_type_cd'] != 'N003000002') {
                $res['data']['attr'] = $attr;
            }

            if ($is_internal) {
                return $res['data'];
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }


        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     * @param $AllocationExtendNewService
     * @param $allo_id
     * @see AllocationExtendNewService
     *
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

                    $child = M('wms_allo_child', 'tb_')->where(['allo_id' => $allo_id])->select();
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
     * 编辑调拨
     */
    public function editAlloDetail()
    {
        $AllocationExtendAction = new AllocationExtendAction();
        $AllocationExtendAction->editAlloDetail();
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
                    $child = M('wms_allo_child', 'tb_')->where(['allo_id' => $allo_id])->select();
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
            # 为不影响现有测试环境 先注释代码
            'work.total_box_num' => 'required|numeric',
            'work.total_volume' => 'required|numeric',
            'work.total_weight' => 'required|numeric',
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
            'work.total_weight' => '总重量',
            'work.total_volume' => '总体积',
            'work.total_box_num' => '总箱数',
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

            if (!RedisModel::lock('allo_id_' . $allo_id, 30)) {
                throw new Exception(L('allo_id_' . $allo_id . '调拨出库锁获取失败,该订单正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            if (count($data['goods']) < 200) {
                $this->verificationSubmitOutStock($allo_id, $data);
            }
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitOutStock($allo_id, $data, $Model);


            // // 出库 调拨单产生 出库费用 头程物流费 保险费用
            // $expensebill= new ExpensebillService();
            // $ret = $expensebill->outStockPayment($allo_id,$data['logistics_information']);
            // if (!$ret){
            //     throw new Exception('生成调拨应付失败');
            // }

            $Model->commit();
            RedisModel::unlock('allo_id_' . $allo_id);
            $this->addLog($allo_id, '确认出库');

        } catch (Exception $exception) {
            RedisModel::unlock('allo_id_' . $allo_id);
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }


    /**
     *出库时单独填写物流信息
     */
    public function submitOutStockLogistics()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $out_stock_id = $this->getOutStockId();
            $res = DataModel::$error_return;
            if (empty($allo_id) || empty($out_stock_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }
            if (!RedisModel::lock('out_stock_id' . $out_stock_id, 30)) {
                throw new Exception(L('out_stock_id' . $out_stock_id . '出库填写物流信息锁获取失败,该出库记录正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            # 校验独立接口校验
            $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
            if (!in_array($allo['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams)) {
                # 校验独立接口校验
                $data = $this->verificationSubmitOutStockLogistics($allo_id, $out_stock_id ,$data);
            }
            
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitOutStockLogistics($allo_id, $out_stock_id, $data, $Model);
            // 出库 调拨单产生 出库费用 头程物流费 保险费用
            //第一次修改才会生成账单

            if ($data['logistics_information']['outbound_cost_currency_cd']) {
                $expensebill = new ExpensebillService();
                $ret = $expensebill->outStockPayment($allo_id, $data['logistics_information']);
                if (!$ret) {
                    throw new Exception('生成调拨应付失败');
                }
            }


            $Model->commit();
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $this->addLog($allo_id, '出库填写物流信息');
        } catch (Exception $exception) {
            RedisModel::unlock('out_stock_id' . $out_stock_id);

            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     *出库-填写物流节点信息
     */
    public function submitOutStockLogisticsNode()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $out_stock_id = $this->getOutStockId();
            $res = DataModel::$error_return;
            if (empty($allo_id) || empty($out_stock_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }
            if (!RedisModel::lock('out_stock_id' . $out_stock_id, 10)) {
                throw new Exception(L('out_stock_id' . $out_stock_id . '物流节点锁获取失败,该出库记录正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            $data = $this->verificationSubmitOutStockLogisticsNode($allo_id, $out_stock_id, $data);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitOutStockNode($allo_id, $out_stock_id, $data, $Model);
            $Model->commit();
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $this->addLog($allo_id, '出库填写物流节点');
        } catch (Exception $exception) {
            // RedisLock::unlock('allo_id_' . $allo_id);
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     *出库-填写物流节点信息-编辑物流节点异常原因
     */
    public function submitOutStockLogisticsNodeReason()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $allo_id = $this->getAlloId();
            $node_id = $this->getNodeId();

            $res = DataModel::$error_return;
            if (empty($node_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }
            if (!RedisModel::lock('allo_id_node_id' . $allo_id . '_' . $node_id, 10)) {
                throw new Exception(L('allo_id_node_id' . $allo_id . '_' . $node_id . '物流节点异常原因锁获取失败,该节点正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            $data = $this->verificationSubmitOutStockLogisticsNodeReason($allo_id, $node_id, $data);

            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->submitOutStockNodeReason($data, $Model);

            $Model->commit();
            RedisModel::unlock('allo_id_node_id' . $allo_id . '_' . $node_id);
            $this->addLog($allo_id, '出库填写物流节点误差原因');
        } catch (Exception $exception) {
            // RedisLock::unlock('allo_id_' . $allo_id);
            RedisModel::unlock('allo_id_node_id' . $allo_id . '_' . $node_id);
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**
     *出库-轨迹备注
     */
    public function submitOutStockRemark()
    {
        try {


            $out_stock_id = $this->getOutStockId();
            $res = DataModel::$error_return;
            if (empty($out_stock_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }

            $data = DataModel::getDataNoBlankToArr();
            if (empty($data['remark'])) {
                $res['msg'] = '备注不能为空';
                $this->ajaxReturn($res);
            }
            $insert = [];
            $insert['remark'] = $data['remark'];
            $insert['created_by'] = userName();
            $insert['created_at'] = date('Y-m-d H:i:s');
            $insert['remark'] = $data['remark'];
            $insert['out_stock_id'] = $out_stock_id;
            $re = M('wms_allo_new_out_stocks_logistics_track_remark', 'tb_')->add($insert);
            if (!$re) {
                $res['msg'] = '备注失败，请重试';
                $this->ajaxReturn($res);
            }
            $res = DataModel::$success_return;
            $res['data'] = $re;


        } catch (Exception $exception) {
            // RedisLock::unlock('allo_id_' . $allo_id);

            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function getOutStockLogisticsRemark()
    {
        try {
            $out_stock_id = $this->getOutStockId();
            $res = DataModel::$error_return;
            if (empty($out_stock_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }
            $data = M('wms_allo_new_out_stocks_logistics_track_remark', 'tb_')->where(['out_stock_id' => $out_stock_id])->select();
            $res = DataModel::$success_return;
            $res['data'] = $data;

        } catch (Exception $exception) {

            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     *出库-获取物流节点信息
     */
    public function getOutStockLogisticsNode()
    {

        try {
            $Model = new Model();
            $allo_id = $this->getAlloId();
            $out_stock_id = $this->getOutStockId();
            $res = DataModel::$error_return;
            if (empty($allo_id) || empty($out_stock_id)) {
                $res['msg'] = '参数有误';
                $this->ajaxReturn($res);
            }

            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->getOutStockNode($allo_id, $out_stock_id, $Model);


        } catch (Exception $exception) {
            // RedisLock::unlock('allo_id_' . $allo_id);

            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }

    /**过滤无效参数
     * @param $allo_id
     * @param $out_stock_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationSubmitOutStockLogisticsNode($allo_id, $out_stock_id, $data)
    {
        #参数过滤
        $custom_attributes_plan = [
            'place_order_plan' => '下单预计时间',
            'out_stock_plan' => '出库预计时间',
            'depart_port_plan' => '离港预计时间',
            'arrival_port_plan' => '到港预计时间',
            'custom_clear_plan' => '清关预计时间',
            'send_warehouse_plan' => '送仓预计时间',
            'start_ground_plan' => '开始上架预计时间',
            'end_ground_plan' => '上架完成预计时间'

        ];
        $custom_attributes_operate = [
            'place_order_operate' => '下单操作时间',
            'out_stock_operate' => '出库操作时间',
            'depart_port_operate' => '离港操作时间',
            'arrival_port_operate' => '到港操作时间',
            'custom_clear_operate' => '清关操作时间',
            'send_warehouse_operate' => '送仓操作时间',
            'start_ground_operate' => '开始上架操作时间',
        ];
        #验证 allo_id 和 out_stock_id
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $out_stock = M('wms_allo_new_out_stocks', 'tb_')->where(['id' => $out_stock_id])->find();
        if (empty($allo) || empty($out_stock)) {
            throw new Exception(L('主参数有误'));
        }
        if ($out_stock['allo_id'] != $allo_id) {
            throw new Exception(L('主参数有误'));
        }
        $allo_in_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $allo['allo_in_warehouse']])->getField('transfer_warehousing_by');

        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);

        if (!in_array(userName(), $allo_in_warehouse_user)) {
            #无权限入库
            throw new Exception(L('您当前没有权限操作'));
        }


        #查询是否已经填写了预计时间  预计时间必须一次性全部填写完成
        $last_data = M('wms_allo_new_out_stocks_node', 'tb_')->where(['out_stock_id' => $out_stock_id, 'allo_id' => $allo_id])->order('type')->select();
        $is_cn_warehouse = M('wms_allo', 'tb_')->where(['id' => $allo_id])->getField('is_cn_warehouse');

        if ($is_cn_warehouse) {
            #国内仓不需要离港 到港 清关
            unset($custom_attributes_plan['depart_port_plan']);
            unset($custom_attributes_plan['arrival_port_plan']);
            unset($custom_attributes_plan['custom_clear_plan']);
            unset($custom_attributes_operate['depart_port_operate']);
            unset($custom_attributes_operate['arrival_port_operate']);
            unset($custom_attributes_operate['custom_clear_operate']);
        }
        $custom_attributes_operate_key = array_keys($custom_attributes_operate);
        $custom_attributes_plan_key = array_keys($custom_attributes_plan);

        $method = 1;
        if (empty($last_data)) {
            foreach ($custom_attributes_plan as $key => $value) {
                if (empty($data[$key])) {
                    throw new Exception(L($custom_attributes_plan[$key] . '不能为空'));
                }

            }
            foreach ($custom_attributes_plan_key as $key => $value) {
                #从第二个节点开始  当前节点小于上个节点  报错
                if ($key > 0 && !empty($data[$value]) && $data[$value] < $data[$custom_attributes_plan_key[$key - 1]]) {
                    throw new Exception(L($custom_attributes_plan[$value] . '不得小于上个节点'));
                }
                #预计时间小于当前时间   从第三个开始需要判断  前两个可自由填写时间 预估时间修改为无限制
                // if ($key > 1 && strtotime($data[$value]) < strtotime(date('Y-m-d'))) {
                //     throw new Exception(L($custom_attributes_plan[$value] . '不能小于当前时间'));
                // }
            }
            foreach ($custom_attributes_operate_key as $key => $value) {
                #操作时间不能大于当前时间  
                if (!empty($data[$value]) && strtotime($data[$value]) > strtotime(date('Y-m-d'))) {
                    throw new Exception(L($custom_attributes_operate[$value] . '不能大于当前时间'));
                }
                // if (!empty($data[$value]) && $key > 1 && strtotime($data[$value]) < strtotime(date('Y-m-d'))) {
                //     throw new Exception(L($custom_attributes_operate[$value] . '不能小于当前时间'));
                // }
                #从第二个节点开始  上一个节点为空  报错
                if ($key > 0 && !empty($data[$value]) && empty($data[$custom_attributes_operate_key[$key - 1]])) {
                    throw new Exception(L($custom_attributes_operate[$value] . '的上个节点不得为空'));
                }

                #从第二个节点开始  当前节点小于上个节点  报错
                if ($key > 0 && !empty($data[$value]) && $data[$value] < $data[$custom_attributes_operate_key[$key - 1]]) {

                    throw new Exception(L($custom_attributes_operate[$value] . '不得小于上个节点'));
                }
            }

            $method = 1;#插入
            $re = [];

            foreach ($custom_attributes_plan_key as $key => $value) {
                //组装数据
                $tmp = [];

                $tmp['node_plan'] = $data[$value];
                $tmp['out_stock_id'] = $out_stock_id;
                $tmp['allo_id'] = $allo_id;
                if ($is_cn_warehouse && $key > 1) {
                    $tmp['type'] = $key + 4;
                } else {
                    $tmp['type'] = $key + 1;
                }

                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['updated_at'] = date('Y-m-d H:i:s');

                $tmp['node_operate'] = $data[$custom_attributes_operate_key[$key]] ? $data[$custom_attributes_operate_key[$key]] : null;


                $re[] = $tmp;
            }

            if (empty(count($re))) {
                throw new Exception(L('无修改数据'));
            }

            return ['method' => $method, 'data' => $re];

        } else {
            #已存在  需要比对数据

            foreach ($custom_attributes_plan as $key => $value) {
                if ($data[$key]) {
                    unset($data[$key]);
                }
            }

            foreach ($custom_attributes_operate_key as $key => $value) {

                if (!$data[$value]) {
                    continue;
                }
                #操作时间不能大于当前时间  

                if (!empty($data[$value]) && strtotime($data[$value]) > strtotime(date('Y-m-d'))) {

                    throw new Exception(L($custom_attributes_operate[$value] . '不能大于当前时间'));
                }
                // if (!empty($data[$value]) && $key > 1 && strtotime($data[$value]) < strtotime(date('Y-m-d'))) {

                //     throw new Exception(L($custom_attributes_operate[$value] . '不能小于当前时间'));
                // }
                if ($last_data[$key]['node_operate'] && $last_data[$key]['node_operate'] != $data[$value]) {
                    throw new Exception(L($custom_attributes_operate[$value] . '不得修改'));
                }

                if ($last_data[$key]['node_operate']) {
                    #新增过的  不得修改
                    continue;
                }
                #从第二个节点开始  上一个节点为空  报错
                if ($key > 0 && (empty($last_data[$key - 1]['node_operate']) && empty($data[$custom_attributes_operate_key[$key - 1]]))) {
                    throw new Exception(L($custom_attributes_operate[$value] . '的上个节点不得为空'));
                }
                #从第二个节点开始 小于上一个节点
                if ($key > 0 && $last_data[$key - 1]['node_operate'] && $data[$value] < $last_data[$key - 1]['node_operate']) {
                    throw new Exception(L($custom_attributes_operate[$value] . '不得小于上个节点'));
                }
                if ($key > 0 && !empty($data[$custom_attributes_operate_key[$key - 1]]) && $data[$value] < $data[$custom_attributes_operate_key[$key - 1]]) {
                    throw new Exception(L($custom_attributes_operate[$value] . '不得小于上个节点'));
                }
            }

            $method = 2;
            #更新
            $re = [];
            foreach ($custom_attributes_operate_key as $key => $value) {

                if ($data[$value]) {
                    if ($is_cn_warehouse && $key > 1) {
                        $tmp['where'] = ['type' => $key + 4];
                    } else {
                        $tmp['where'] = ['type' => $key + 1];
                    }

                    $tmp['data'] = ['node_operate' => $data[$value]];
                    $re[] = $tmp;
                }
            }
            if (empty(count($re))) {
                throw new Exception(L('无修改数据'));
            }

            return ['method' => $method, 'data' => $re];


        }


    }


    private function verificationSubmitOutStockLogisticsNodeReason($allo_id, $node_id, $data)
    {
        #参数过滤
        $custom_attributes_plan = [
            'reason_type' => '问题方',
            'reason_cate' => '问题分类',

            // 'reason_val' => '清关预计时间',
            // 'resson_created_at' => '送仓预计时间',
        ];
        foreach ($custom_attributes_plan as $key => $value) {
            if (empty($data[$key])) {
                throw new Exception(L($value . '不能为空'));
            }
        }
        #验证主参数
        $node = M('wms_allo_new_out_stocks_node', 'tb_')->where(['id' => $node_id, 'allo_id' => $allo_id])->find();
        if (empty($node)) {
            throw new Exception(L('主参数有误'));
        }
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        if (empty($allo)) {
            throw new Exception(L('主参数有误'));
        }
        $allo_in_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $allo['allo_in_warehouse']])->getField('transfer_warehousing_by');
        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);
        if (!in_array(userName(), $allo_in_warehouse_user)) {
            #无权限入库
            throw new Exception(L('您当前没有权限操作'));
        }
        // if($node['resson_created_at']){
        //     throw new Exception(L('该问题已被编辑过，无法修改'));
        // }

        $re = [
            'node_id' => $node_id,
            'reason_type' => $data['reason_type'],
            'reason_cate' => $data['reason_cate'],
            'reason_val' => $data['reason_val'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $re;

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
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        if (empty($allo)) {
            throw new Exception(L('参数有误'));
        }

        $allo_out_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $allo['allo_out_warehouse']])->getField('transfer_out_library_by');

        $allo_out_warehouse_user = explode(',', $allo_out_warehouse_user);
        if (!in_array(userName(), $allo_out_warehouse_user)) {
            #无权限出库
            throw new Exception(L('您当前没有权限操作'));
        }
        // $allo_out_warehouse_user = explode(',', $allo_out_warehouse_user);
        $rules = [
            // 'logistics_information.transport_company_id' => 'required|numeric',
            // 'logistics_information.outbound_cost_currency_cd' => 'required|string|size:10',
            // 'logistics_information.outbound_cost' => 'required|numeric',
            // 'logistics_information.head_logistics_fee_currency_cd' => 'required|string|size:10',
            // 'logistics_information.head_logistics_fee' => 'required|numeric',
            // 'logistics_information.have_insurance' => 'required|numeric',
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
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    private function verificationSubmitOutStockLogistics($allo_id, $out_stock_id, $data)
    {
        $this->verificationAlloId($allo_id);

        $data = $data['logistics_information'];
        $last_data = M('wms_allo_new_out_stocks', 'tb_')->where(['id' => $out_stock_id])->find();
        $last_data_allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        if (empty($last_data_allo) || empty($last_data)) {
            throw new Exception(L('参数id有误'));
        }

        $allo_in_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $last_data_allo['allo_in_warehouse']])->getField('transfer_warehousing_by');

        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);

        if (!in_array(userName(), $allo_in_warehouse_user)) {
            #无权限入库
            throw new Exception(L('您当前没有权限操作'),4030);
        }
        $custom_attributes = AllocationExtendNewService::$outStockLogisticsCustomAttributes;

        $must_key = [
            'transport_company_id',
            // 'outbound_cost_currency_cd', 
            // 'outbound_cost',
            'head_logistics_fee_currency_cd',
           // 'head_logistics_fee',
            'have_insurance',
            // 'send_warehouse_way',
            'planned_transportation_channel_cd',
            'customs_clear',
            'cube_feet_type',
            'cube_feet_val',
            'out_plate_number_type',
            'cabinet_type',
            'insurance_type'

        ];
        if(in_array($last_data_allo['allo_in_team'], TbWmsAlloModel::$allot_optimize_teams)) {
            //$must_key = array_merge(['oversea_in_storage_no','shipping_company_name'],$must_key);
            $must_key = array_merge($must_key,['oversea_in_storage_no']);
            if($data['planned_transportation_channel_cd'] == 'N002820001') {
                $must_key = array_merge($must_key,['shipping_company_name']);
            }
        }
        $custom_attributes_key = array_keys($custom_attributes);
        // $this->validate($rules, $data, $custom_attributes);
        foreach ($custom_attributes as $key => $value) {
            if ($key != 'have_insurance' && in_array($key, $must_key) && (empty($data[$key]) || $data[$key] == '0.00')) {
                throw new Exception(L($custom_attributes[$key] . '不能为空'));
            }
            if ($key == 'have_insurance' && !in_array($data[$key], [0, 1])) {
                throw new Exception(L($custom_attributes[$key] . '不能为空'));
            }

        }

        if ($data['outbound_cost'] == '') {
            throw new Exception(L('出库费用不能为空'));
        }

        if ($data['outbound_cost'] > 0 && empty($data['outbound_cost_currency_cd'])) {
            throw new Exception(L('出库费用币种不能为空'));
        }

        if (empty($data['third_party_warehouse_entry_number'])) {
            throw new Exception(L('入仓单号/So号不能为空'));
        }
        if (in_array($data['planned_transportation_channel_cd'], ['N002820001', 'N002820003']) && empty($data['cabinet_number'])) {
            throw new Exception(L('柜号不能为空'));
        }

        // if (in_array($data['planned_transportation_channel_cd'], ['N002820001', 'N002820003']) && empty($data['strip_p_seal'])) {
        //     throw new Exception(L('封条不能为空'));
        // }
        if (in_array($data['out_plate_number_type'], [2]) && empty($data['out_plate_number_val'])) {
            throw new Exception(L('出库类型为散装时,板数不能为空'));
        }
        if (!in_array($data['insurance_type'], [1, 2, 3])) {
            throw new Exception(L('保险缴纳方类型有误'));
        }
        if ($data['insurance_type'] == 3) {
            $data['have_insurance'] = 0;
        } else {
            $data['have_insurance'] = 1;
        }
        if ($data['insurance_type'] != 3 && (empty($data['insurance_claims_cd_val']) || empty($data['insurance_coverage_cd']) || empty($data['insurance_fee_currency_cd']))) {
            throw new Exception(L('请完整填写保险信息'));
        }
        if ($data['insurance_claims_cd']) unset($data['insurance_claims_cd']);


        foreach ($data as $key => $value) {

            if (empty($value) || $value == '') {
                unset($data[$key]);
            }
            if (!in_array($key, $custom_attributes_key)) {
                unset($data[$key]);
            }
            if (!empty($last_data[$key]) && $last_data[$key] != '0.00') {
                unset($data[$key]);
            }
        }


        if (empty($last_data['cabinet_type']) && substr($data['cabinet_type'], 0, 6) != 'N00349') {
            throw new Exception(L('柜型code码有误'));
        }
        if (empty($last_data['customs_clear']) && substr($data['customs_clear'], 0, 6) != 'N00347') {
            throw new Exception(L('清关方式code码有误'));
        }
        if (empty($last_data['planned_transportation_channel_cd']) && substr($data['planned_transportation_channel_cd'], 0, 6) != 'N00282') {
            throw new Exception(L('运输渠道code码有误'));
        }

        if (empty($last_data['send_warehouse_way']) && !empty($data['send_warehouse_way']) && substr($data['send_warehouse_way'], 0, 6) != 'N00346') {
            throw new Exception(L('送仓方式不能为空'));
        }

        return ['logistics_information' => $data];
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
            $out_stock_id = $this->getOutStockId();
            if (empty($allo_id) || empty($out_stock_id)) {
                throw new Exception(L('主参数不能为空'));
            }
            if (!RedisModel::lock('allo_id_' . $allo_id, 30)) {
                throw new Exception(L('allo_id_' . $allo_id . '调拨入库锁获取失败，请稍后再试'));
            }

            $data = DataModel::getDataNoBlankToArr();
            if (count($data['goods']) < 200) {
                $this->verificationSubmitInStock($allo_id, $out_stock_id, $data);
            }

            $AllocationExtendNewService = new AllocationExtendNewService();
            $batch_ids = $AllocationExtendNewService->submitInStock($allo_id, $out_stock_id, $data, $Model);

            #是否本此入库后  sku全部入库完毕 更新维度为出库记录下的  本次入库完结
            $update_re_in_status = $AllocationExtendNewService->updateInStatus($allo_id, $out_stock_id, $Model);
            if ($update_re_in_status === false) {
                throw new Exception('更新入库状态失败');
            }


            // 入库 调拨单产生 上架费用 增值服务费 #需要等到该出库批次完全出库完  才出费用
            $expensebill = new ExpensebillService();
            $ret = $expensebill->inStockPayment($allo_id, $data['logistics_information']);
            if (!$ret) {
                throw new Exception('生成调拨应付失败');
            }
            $Model->commit();

            // 调拨入库产生关联交易订单
            $relatedTransaction = new RelatedTransactionService();
            $ret = $relatedTransaction->verifyCompany($batch_ids, $allo_id, $data);
            if ($ret['code'] != 200) {
                throw new Exception(L($ret['msg']));
            }
            if (!empty($ret['data'])) {
                // 调用接口  处理关联交易订单
                $ret = $relatedTransaction->disposeRelTransOrder($ret['data']);
                if ($ret['code'] != 200) {
                    throw new Exception(L($ret['msg']));
                }
            }
            //全部出库，并且全部入库完成，则触发消息提醒\
            $sum_allo_num = $AllocationExtendNewService->getAllGoodsAlloNum($allo_id);
            if ($AllocationExtendNewService->isAllGoodsStock($allo_id, $sum_allo_num) &&
                $AllocationExtendNewService->isAllGoodsInWarehouse($allo_id, $sum_allo_num)) {
                $last_data_allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
                $AllocationExtendNewService->sendWxMsg($last_data_allo, '正常入库');
            }
            RedisModel::unlock('allo_id_' . $allo_id);
            $this->addLog($allo_id, '确认入库');
            sleep(1);
        } catch (Exception $exception) {
            RedisModel::unlock('allo_id_' . $allo_id);
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
    private function verificationSubmitInStock($allo_id, $out_stock_id, &$data)
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
        $in_stock_attributes = [
            'send_warehouse_way' => '送仓方式',
            'planned_transportation_channel_cd' => '运输渠道',
            // 'third_party_warehouse_entry_number' => '入仓单号/So号',
            'customs_clear' => '清关方式',
            'cube_feet_type' => '计费重/材积类型',
            // 'cube_feet_val' => '计费重/材积',
            'out_plate_number_type' => '出库板数类型',
            // 'out_plate_number_val' => '出库板数',
            'cabinet_type' => '柜型',
            // 'cabinet_number' => '柜号',
            // 'strip_p_seal' => '封条'
        ];

        # 检测出库
        $out_stock_guds = M('wms_allo_new_out_stock_guds', 'tb_')->where(['allo_id' => $allo_id, 'out_stocks_id' => $out_stock_id])->select();
        $in_stock_guds = M('wms_allo_new_in_stock_guds', 'tb_')->where(['allo_id' => $allo_id, 'out_stock_id' => $out_stock_id])->group('sku_id')->field(['sum(this_in_authentic_products)' => 'this_in_authentic_products', 'sum(this_in_defective_products)' => 'this_in_defective_products', 'sku_id'])->select();
        if (empty($out_stock_guds)) {
            throw new Exception(L('参数有误'));
        }
        $out_stock_guds = array_column($out_stock_guds, null, 'sku_id');

        $out_num = array_sum(array_column($out_stock_guds, 'this_out_authentic_products')) + array_sum(array_column($out_stock_guds, 'this_out_defective_products'));
        $in_num = array_sum(array_column($in_stock_guds, 'this_in_authentic_products')) + array_sum(array_column($in_stock_guds, 'this_in_defective_products'));

        if ($in_num >= $out_num) {
            //出库的已经全部入库
            throw new Exception(L('该出库记录所绑定的商品已经全部入库'));
        }

        $in_stock_guds = array_column($in_stock_guds, null, 'sku_id');

        #前端传过来的入库正品次品的数量累计
        $tmpInStockNum = 0;
        foreach ($data['goods'] as $key => $datum) {
            if (0 == $datum['this_in_authentic_products'] && 0 == $datum['this_in_defective_products']) {
                unset($data['goods'][$key]);
            }
            if (empty($datum['sku_id'])) {
                unset($data['goods'][$key]);
            }
            $tmp_in_stock_all = $datum['this_in_authentic_products'] + $datum['this_in_defective_products'] + $in_stock_guds[$datum['sku_id']]['this_in_authentic_products'] + $in_stock_guds[$datum['sku_id']]['this_in_defective_products'];
            $tmp_out_stock_all = $out_stock_guds[$datum['sku_id']]['this_out_authentic_products'] + $out_stock_guds[$datum['sku_id']]['this_out_defective_products'];


            if ($tmp_in_stock_all > $tmp_out_stock_all) {
                throw new Exception(L('sku' . $datum['sku_id'] . '入库数量不能大于出库数量'));
            }
            $tmpInStockNum += $datum['this_in_authentic_products'];
            $tmpInStockNum += $datum['this_in_defective_products'];
            if (empty($out_stock_guds[$datum['sku_id']])) {
                throw new Exception(L('sku' . $datum['sku_id'] . '不存在于此次出库中'));
            }
        }

        #是否本次成功入库后  则所有产品入库完毕
        if ($tmpInStockNum < $out_num) {
            #未完全入库 则unset费用字段
            unset($data['logistics_information']);
        } else {
            $this->validate($rules, $data, $custom_attributes);
        }
        #判断状态是否为上架中  
        $out_stock = M('wms_allo_new_out_stocks', 'tb_')->where(['allo_id' => $allo_id, 'id' => $out_stock_id])->find();

        if (empty($out_stock)) {
            throw new Exception(L('参数有误'));
        }
        if (empty($out_stock['logistics_state']) || $out_stock['logistics_state'] != 7) {
            throw new Exception(L('物流轨迹必须为上架中才能开始入库'));
        }
        if (($out_stock['stock_in_state'])) {
            throw new Exception(L('入库已完成，无法继续入库'));
        }
        // foreach ($in_stock_attributes as $key=>$value){

        //     if(empty($out_stock[$key])){

        //         throw new Exception(L('物流信息不完整，无法入库'));
        //     }
        // }

        // if ($out_stock['send_warehouse_way'] == 1 && empty($out_stock['tracking_number'])) {
        //     throw new Exception(L('快递单号为空，无法入库'));
        // }
        // if ($out_stock['cabinet_type'] != 1 && empty($out_stock['third_party_warehouse_entry_number'])) {
        //     throw new Exception(L('入仓单号/So号为空，无法入库'));
        // }
        // if (in_array($out_stock['planned_transportation_channel_cd'], ['N002820001', 'N002820003']) && empty($out_stock['cabinet_number'])) {
        //     throw new Exception(L('柜号为空，无法入库'));
        // }
        // if (in_array($out_stock['planned_transportation_channel_cd'], ['N002820002', 'N002820004']) && empty($out_stock['strip_p_seal'])) {
        //     throw new Exception(L('封条为空，无法入库'));
        // }
        // if (in_array($out_stock['out_plate_number_type'], [2]) && empty($out_stock['out_plate_number_val'])) {
        //     throw new Exception(L('出库板数为空，无法入库'));
        // }
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
            $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
            if (empty($allo)) {
                throw new Exception(L('参数有误'));
            }

            $allo_out_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $allo['allo_out_warehouse']])->getField('transfer_out_library_by');

            $allo_out_warehouse_user = explode(',', $allo_out_warehouse_user);
            if (!in_array(userName(), $allo_out_warehouse_user)) {
                #无权限出库
                throw new Exception(L('您当前没有权限操作'));
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
     *入库完结
     */
    public function inboundTagCompletion()
    {
        try {
            $allo_id = $this->getAlloId();
            $out_stock_id = $this->getOutStockId();
            $reason_difference = DataModel::getDataNoBlankToArr()['in_reason_difference'];
            if (empty($reason_difference)) {
                throw new Exception(L('请填写入库差异原因'));
            }
            if (empty($out_stock_id) || empty($allo_id)) {
                throw new Exception(L('主参数不能为空'));
            }
            if (!RedisModel::lock('out_stock_id' . $out_stock_id, 30)) {
                throw new Exception(L('out_stock_id' . $out_stock_id . '入库完结时锁获取失败,该记录正在被处理中，请稍后再试'));
            }
            $last_data_allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();

            $allo_in_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $last_data_allo['allo_in_warehouse']])->getField('transfer_warehousing_by');

            $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);

            if (!in_array(userName(), $allo_in_warehouse_user)) {
                #无权限入库
                throw new Exception(L('您当前没有权限操作'));
            }
            $this->verificationAlloId($allo_id, $reason_difference);
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res = DataModel::$success_return;
            $res['data'] = (array)$AllocationExtendNewService->inboundTagCompletion($allo_id, $out_stock_id, $reason_difference);

            //本次调拨所有商品已出库，标记完结则触发消息提醒
            $sum_allo_num = $AllocationExtendNewService->getAllGoodsAlloNum($allo_id);
            if ($AllocationExtendNewService->isAllGoodsStock($allo_id, $sum_allo_num)) {
                $AllocationExtendNewService->sendWxMsg($last_data_allo, '入库完结');
            }
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $this->addLog($allo_id, '入库标记完成');
        } catch (Exception $exception) {
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    #催办发送微信消息
    public function remind()
    {
        $allo_id = $this->getAlloId();
        $out_stock_id = $this->getOutStockId();
        if (empty($out_stock_id) || empty($allo_id)) {
            throw new Exception(L('主参数不能为空'));
        }
        $update_re = M('wms_allo_new_out_stocks', 'tb_')->where(['id' => $out_stock_id])->save(['wechat_message_time' => time()]);
        if ($update_re === false) {
            throw new Exception(L('催办失败'));
        }
        $AllocationExtendNewService = new AllocationExtendNewService();
        $res = DataModel::$success_return;
        $res['data'] = (new ReviewMsgTpl())->sendWeChatReviewTransferNode(
            $AllocationExtendNewService->assemblyWeChatData($allo_id),
            '',
            $out_stock_id
        );
        $this->addLog($allo_id, '催办');
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
                    $upc_more_sku = [];
                    foreach ($r ['ret'] as $item) {
                        if (isset($item['upc_more']) && $item['upc_more']) {
                            $upc_more_arr = explode(',', $item['upc_more']);
                            foreach ($upc_more_arr as $upc) {
                                if (!$upc) continue;
                                $upc_more_sku[$upc] = $item['SKU_ID'];
                            }
                        }
                    }
                    if ($upc_more_sku) {
                        $skuUpc = $upc_more_sku + $skuUpc;
                    }
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
            if (!empty($value['upc_more'])) {
                $upc_more_arr = explode(',', $value['upc_more']);
                foreach ($upc_more_arr as $upc_id) {
                    $existSmallTeam[$upc_id][] = $value['small_sale_team_code'];
                    $existVirType[$upc_id][] = $value['vir_type'];
                }
                $existUpc = array_unique(array_merge($existUpc, $upc_more_arr));
            }
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
            $k = 'sku:' . $value['sku'] . '-商品类型:' . $value['vir_type'] . '-销售小团队:' . $value['small_sale_team_code'];

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
                $strLabel = '';
                if ($value['sku']) {
                    $strLabel = ' SKU:' . $value['sku'];
                }
                if ($value['upc']) {
                    $strLabel .= ' 条形码:' . $value['upc'];
                }
                $error [$this->cacheSkuNew[$k] . 'D'] = '导入的 ' . $strLabel . '的商品不符合调拨条件，请先核实';
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
            } elseif (isset($existData[$index2])) {
                if ($value ['available_for_sale_num_total'] < $existData[$index2]['num'] && $existData[$index2]['vir_type_cd'] == $value ['vir_type_cd']) {
                    $error [$existData[$index2]['key'] . 'B'] = $value ['upc'] . ':当前需调拨的数量为：' . $existData[$index2]['num'] . '  大于可调拨数量:' . $value ['available_for_sale_num_total'];
                }
                unset($existData[$index2]);
            }
        }
        return $error;
    }

    public function allo_bind_quote_no()
    {
        #  AllocationExtendModel
        $allocation_extend_model = D("AllocationExtend");
        $allo_id = I('post.allo_id');
        $quote_no = I('post.quote_no');
        if (empty($allo_id)) {
            return $this->ajaxError([], '调拨单记录ID不能为空');
        }
        if (empty($quote_no)) {
            return $this->ajaxError([], '报价单号不能为空');
        }
        $allow_data = $allocation_extend_model->where("id = {$allo_id}")->find();
        if (empty($allow_data)) {
            return $this->ajaxError([], '调拨单记录不存在，请检查');
        }
        if (!empty($allow_data['quote_no'])) {
            return $this->ajaxError([], '调拨单记录已绑定报价记录，不能重复绑定');
        }
        $allow_bind__quotation_states = $allocation_extend_model::$allow_bind__quotation_states;
        if (!in_array($allow_data['state'], $allow_bind__quotation_states)) {
            return $this->ajaxError([], '调拨单记录不存在，请检查');
        }

        $quotation_model = D("Quote/OperatorQuotation");
        $quotation = $quotation_model->where([['quote_no' => $quote_no]])->find();
        if (empty($quotation)) {
            return $this->ajaxError([], '报价记录不存在，请检查');
        }
        if (!in_array($quotation['status_cd'], [$quotation_model::STATUS_CD_FINISH])) {
            return $this->ajaxError([], '当前报价记录状态未完成，不能被绑定');
        }

        $date = new Date();
        $update_data = [
            'quote_no' => $quote_no,
            'update_user' => session('user_id'),
            'update_time' => $date->format(),
        ];
        $trans = M();
        $trans->startTrans();
        try {
            $res = $allocation_extend_model->where("id = {$allo_id}")->data($update_data)->save();
        } catch (\Exception $e) {
            $trans->rollback();
            Log::record("【调拨单绑定绑定报价单失败】" . $e->__toString(), Log::ERR);
            return $this->ajaxSuccess([], "服务器异常，报价单号绑定失败");
        }
        $trans->commit();
        return $this->ajaxSuccess([], "success");
    }

    
    public function verifyOutStockLogistics()
    {
        $allo_id = $this->getAlloId();
        $out_stock_id = $this->getOutStockId();
        $res = DataModel::$error_return;
        if(empty($allo_id) || empty($out_stock_id)){
            $res['msg'] = '参数有误';
            $this->ajaxError([], "参数有误,必传参数不能为空");
        }
        $where = [
            'allo_id' =>['eq', $allo_id],
            'id' => ['eq', $out_stock_id]
        ];
        $tb_wms_allo_new_out_stocks_model = M("wms_allo_new_out_stocks","tb_");
        $out_stock_info = $tb_wms_allo_new_out_stocks_model->where($where)->find();
        if(!$out_stock_info)
        {
            return $this->ajaxError([],"调拨入库物流信息不存在");
        }
        try 
        {
            $data = [
                'logistics_information' => $out_stock_info
            ];
            $this->verificationSubmitOutStockLogistics($allo_id, $out_stock_id ,$data);
        } 
        catch (\Exception $e) 
        {
           $msg = $e->getMessage();
           $code = $e->getCode();
           $code = $code ? $code : 3000;
           return $this->ajaxError([], $msg,$code);
        }
        $this->ajaxSuccess();
    }

    /**
     * 出库-保存物流节点信息
     * @author Redbo He
     * @date 2021/1/11 19:39
     */
    public function saveOutStockLogisticsNode()
    {
        try
        {
            $allo_id = $this->getAlloId();
            $out_stock_id = $this->getOutStockId();
            if (empty($allo_id) || empty($out_stock_id)) {
                $this->ajaxError([], '参数有误,必传参数不能为为空');
            }
            if (!RedisModel::lock('out_stock_id' . $out_stock_id, 10)) {
                throw new Exception(L('out_stock_id' . $out_stock_id . '物流节点锁获取失败,该出库记录正在被处理中，请稍后再试'));
            }
            $data = DataModel::getDataNoBlankToArr();
            $model = M();
            $model->startTrans();
            $this->verificationSaveOutStockLogisticsNode($allo_id, $out_stock_id, $data);
            $res = DataModel::$success_return;
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->saveOutStockLogisticsNode($allo_id, $out_stock_id, $data);
            $model->commit();
            $this->addLog($allo_id, '出库填写物流节点');
            RedisModel::unlock('out_stock_id' . $out_stock_id);
        }
        catch (Exception $e)
        {
            $model->rollback();
            RedisModel::unlock('out_stock_id' . $out_stock_id);
            $res = $this->catchException($e);
        }

        $this->ajaxReturn($res);
    }

    protected function verificationSaveOutStockLogisticsNode($allo_id, $out_stock_id, $data)
    {
        #验证 allo_id 和 out_stock_id
        $allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        if (empty($allo)) {
            throw new Exception(L('调拨信息不能够为空'));
        }
        $out_stock = M('wms_allo_new_out_stocks', 'tb_')->where(['id' => $out_stock_id])->find();
        if (empty($out_stock)) {
            throw new Exception(L('新调拨调拨信息为空'));
        }
        if ($out_stock['allo_id'] != $allo_id) {
            throw new Exception(L('主参数异常 请检查'));
        }
        # 操作权限判断
        $allo_in_warehouse_user = M('con_division_warehouse', 'tb_')->where(['warehouse_cd' => $allo['allo_in_warehouse']])->getField('transfer_warehousing_by');
        $allo_in_warehouse_user = explode(',', $allo_in_warehouse_user);
        if (!in_array(userName(), $allo_in_warehouse_user)) {
            #无权限入库
            throw new Exception(L('您当前没有权限操作'));
        }
        #查询是否已经填写了预计时间  预计时间必须一次性全部填写完成
        $last_data            = M('wms_allo_new_out_stocks_node', 'tb_')->where(['out_stock_id' => $out_stock_id, 'allo_id' => $allo_id])->order('type')->select();
        $out_stock_node_types = AllocationExtendNewService::$out_stock_node_types;
        $allot_optimize_teams = TbWmsAlloModel::$allot_optimize_teams;
        $attribute_index = 0;
        if (in_array($allo['allo_in_team'], $allot_optimize_teams)) {
            $attribute_index = 1;
        }
        $node_attributes_map = [
            [
                "node_plan"    => "预计时间",
                "node_operate" => "操作时间"
            ],
            [
                "node_plan"      => "报价时间",
                "scheduled_date" => "预计时间",
                "node_operate"   => "实际完成时间"
            ]
        ];
        $last_current_data = $last_data ? current($last_data) : [];
        if($last_current_data) {
            $attribute_index  = $last_current_data['data_type'];
        }
        $node_attributes     = $node_attributes_map[$attribute_index];
        # #国内仓不需要离港 到港 清关
        $is_cn_warehouse = $allo['is_cn_warehouse'];
        if ($is_cn_warehouse) {
            $out_stock_node_types = array_diff($out_stock_node_types, array_slice($out_stock_node_types, 2, 3, true));
        }
        # #从第二个节点开始  当前节点小于上个节点
        # 操作时间不能大于当前时间
        # 从第二个节点开始  上一个节点为空  报错
        # 从第二个节点开始  当前节点小于上个节点  报错
        $attribute_keys                    = array_keys($node_attributes);
        $node_plan_attribute_name          = $node_attributes['node_plan'];
        $node_operate_attribute_name       = $node_attributes['node_operate'];
        $scheduled_date_attribute_name       = $node_attributes['scheduled_date'];
        $out_stock_node_type_keys          = array_keys($out_stock_node_types);
        $out_stock_node_type_key_index_map = array_flip($out_stock_node_type_keys);
        $last_data = array_column($last_data,NULL, 'type');

        $date     = new Date();
        $now_date = $date->format("%Y-%m-%d");
        foreach ($out_stock_node_types as $key => $node_type) {
            $type_data_record = isset($last_data[$key]) ? $last_data[$key] : [];

            # node_plan 必填
            $node_plan_field_name = $node_type . $node_plan_attribute_name;
            $node_operate_date_field_name = $node_type . $node_operate_attribute_name;
            $node_scheduled_date_field_name = $node_type . $scheduled_date_attribute_name;

            if (empty($data[$key]['node_plan'])) {
                throw new Exception(L($node_plan_field_name . '不能为空'));
            }

            #  node_operate 操作时间不能大于当前时间
            if (!empty($data[$key]['node_operate']) && strtotime($data[$key]['node_operate']) > strtotime($now_date)) {
                throw new Exception(L($node_operate_date_field_name . '不能大于当前时间'));
            }

            # node_operate 一旦确立 无法修改
            if($type_data_record && $type_data_record['node_operate'] && $type_data_record['node_operate'] != $data[$key]['node_operate']) {
                throw new Exception(L($node_scheduled_date_field_name . '不得修改'));
            }

            #从第二个节点开始  当前节点小于上个节点  报错
            if ($key > 1)
            {
                $pre_index = $out_stock_node_type_keys[$out_stock_node_type_key_index_map[$key] - 1];
                # 预计时间 报价时间
                if ($data[$pre_index]['node_plan'] > $data[$key]['node_plan']) {
                    throw new Exception(L($node_plan_field_name . '不得小于上个节点'));
                }

                # nodeplan
                if ($data[$key]['node_operate'] && $data[$pre_index]['node_operate'] > $data[$key]['node_operate']) {
                    throw new Exception(L($node_operate_date_field_name . '不得小于上个节点'));
                }

                //  node_operate 从第二个节点开始  上一个节点为空  报错
                if ($data[$key]['node_operate'] && empty($data[$pre_index]['node_operate']))
                {
                    throw new Exception(L($node_operate_date_field_name. '的上个节点不得为空'));
                }

                # 离港到上架完成 且是调拨优化团队 校验 预计时间
                if($key > 2 && $is_optimize_team) {
                    # scheduled_date
                    if(empty($data[$key]['scheduled_date'])) {
                        throw new Exception(L($node_scheduled_date_field_name . '不能为空'));
                    }
                    if ($data[$pre_index]['scheduled_date'] > $data[$key]['scheduled_date']) {
                        throw new Exception(L($node_scheduled_date_field_name . '不得小于上个节点'));
                    }

//                    if($type_data_record && $type_data_record['node_operate'] && $type_data_record['scheduled_date'] != $data[$key]['scheduled_date']) {
//                        throw new Exception(L($node_scheduled_date_field_name . '不得修改'));
//                    }

                }
            }

        }
    }


    //时效列表展示
    public function skuEffectiveList(){
        $this->display();
    }

    //时效列表数据
    public function effectiveListData(){
        $params = $this->params();
        unset($params['transfer_type']);
        unset($params['allo_type']);
        $AllocationExtend_new_Service = new AllocationExtendNewService();
        $allocation_list = $AllocationExtend_new_Service->getEffectiveList($params);
        $this->ajaxSuccess($allocation_list);
    }


    //导出时效列表
    public function effectiveListExport(){
        $params = json_decode($_POST['export_params'], true);
        if(!empty($params['allo_no'])){
            $where['allo_no'] = ['in', $params['allo_no']];
        }
        if(!empty($params['transport_type'])){
            $where['transport_type'] = ['in', $params['transport_type']];
        }
        if(!empty($params['small_sale_team_code'])){
            $where['small_sale_team_code'] = ['in', $params['small_sale_team_code']];
        }
        if(!empty($params['allo_in_warehouse'])){
            $where['allo_in_warehouse'] = ['in', $params['allo_in_warehouse']];
        }
        if(!empty($params['allo_out_warehouse'])){
            $where['allo_out_warehouse'] = ['in', $params['allo_out_warehouse']];
        }
        if(!empty($params['transport_company'])){
            $where['transport_company'] =  ['in', $params['transport_company']];
        }
        if(!empty($params['lunch_start_time'])){
            $where['allo_create_time'] = array(array('egt', $params ['lunch_start_time'] . ' 00:00:00'), array('elt', $params ['lunch_end_time'] . ' 59:59:59'), 'and');
        }
        //完成时间
        if ($params ['finish_start_time']) {
            $where['allo_finish_time'] = array(array('egt', $params ['finish_start_time'] . ' 00:00:00'), array('elt', $params ['finish_end_time'] . ' 59:59:59'), 'and');
        }
        
        $where['sale_team'] = 'N001282800';
        $list =M('wms_sku_effective','tb_')->where($where)->order('allo_no desc')->select();
        $repository_class = new AllocationExtendNewRepository();
        $list = $repository_class->getSomeName($list,true);

        $exp_cell_name = [
            'allo_no' => '调拨单号',
            'sale_team_name' => '销售团队',
            'allo_out_warehouse_name' => '调出仓库',
            'allo_in_warehouse_name' => '调入仓库',
            'create_user' => '调拨发起人',
            'small_sale_team_name' => '所属小团队',
            'sku_id' => 'sku_id',
            'goods_name' => '商品名称',
            'batch_code' => '批次号',
            'amount' => '数量',
            'volume' => '体积(m³)',
            'weight' => '重量(kg)',
            'allo_create_time' => '调拨发起时间',
            'allo_out_time' => '调拨出库时间',
            'transport_company_name' => '运输公司',
            'transport_type_name' => '运输渠道',
            'allo_in_time' => '调拨入库时间',
            'purchase_in_time' => '采购入库时间',
            'in_warehouse_days' => '在库时间',
            'transport_days' => '运输时间',
            'all_days' => '总时间',
        ];

        $map = [];
        foreach ($exp_cell_name as $key => $value) {
            $map[] = ['field_name' => $key, 'name' => $value];
        }
        $this->exportCsv($list, $map,'导出');
        return true;
    }


    //列表初始条件数据
    public function getEffectiveListCondition(){
        $condition_data = BaseModel::getListCondition();
        $this->ajaxSuccess($condition_data);
    }



}