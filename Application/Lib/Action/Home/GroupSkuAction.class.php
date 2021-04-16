<?php
/**
 * User: yangsu
 * Date: 18/10/24
 * Time: 15:30
 */

class GroupSkuAction extends BaseAction
{
    /**
     * @var GroupSkuService
     */
    public $GroupSkuService;
    public $request_data;

    public function lists()
    {
        $this->display();
    }

    /**
     * @return bool|void
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->GroupSkuService = new GroupSkuService();
        $this->request_data = $this->filterData(DataModel::getDataToArr());
    }

    /**
     *
     */
    public function getLists()
    {
        try {
            $GLOBALS['act'] = microtime();
            $request_data = $this->request_data;
            $this->checkSearchList($request_data);
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->GroupSkuService
                ->getListDatas($request_data);
        } catch (Exception $exception) {
            $res = $this->assemblyCatchRes($exception, $this->error_data);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     */
    private function checkSearchList($data)
    {
        $rules = [
            'data.time_begin' => 'date',
            'data.time_end' => 'date',
        ];
        $attributes = [
            'data.time_begin' => '开始时间',
            'data.time_end' => '结束时间',
        ];
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
            throw new Exception(ValidatorModel::getMessage());
        }
    }

    /**
     *
     */
    public function getGroupSkuNum()
    {
        try {
            $request_data = $this->request_data;
            $this->checkSearchGroupSku($request_data);
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->GroupSkuService->getGroupSkuNum($request_data);
            if (1 == $request_data['is_cancel']) {
                $res['data']['max_num'] = 0;
                $res['data']['max_num'] = $this->GroupSkuService->getGroupCancelSkuNum($request_data);
            }
        } catch (Exception $exception) {
            $res = $this->assemblyCatchRes($exception, $this->error_data);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     */
    private function checkSearchGroupSku($data)
    {


        if ($data['is_cancel'] === 0) { // #9485 打包选择SKU时归属店铺必填---已去除归属店铺
            $rules = [
                'group_sku_id' => 'required|numeric|min:9000000000|max:9999999999',
                'warehouse_cd' => 'required|string|min:10|max:10',
                'sale_team_cd' => 'required|string|min:10|max:10',
                //'small_sale_team_cd' => 'required|string|min:10|max:10',
                'is_cancel' => 'numeric',
                //'ascription_store' => 'required|numeric'
            ];

            $attributes = [
                'group_sku_id' => '组合 SKU',
                'warehouse_cd' => '仓库',
                'sale_team_cd' => '销售团队',
                //'small_sale_team_cd' => '销售小团队',
                'is_cancel' => '取消',
                //'ascription_store' => '归属店铺'
            ];
        } else {
            $rules = [
                'group_sku_id' => 'required|numeric|min:9000000000|max:9999999999',
                'warehouse_cd' => 'required|string|min:10|max:10',
                'sale_team_cd' => 'required|string|min:10|max:10',
                'is_cancel' => 'numeric',
            ];

            $attributes = [
                'group_sku_id' => '组合 SKU',
                'warehouse_cd' => '仓库',
                'sale_team_cd' => '销售团队',
                'is_cancel' => '取消'
            ];
        }
        $this->validateInCapture($data, $rules, $attributes);
    }

    /**
     *
     */
    public function getGroupSkuDetaileds()
    {
        try {
            $this->checkSearchGroupSku($this->request_data);
            $res = $this->return_success;
            unset($res['info']);
            $res['data'] = $this->GroupSkuService
                ->getGroupSkuDetaileds($this->request_data);
        } catch (Exception $exception) {
            $res = $this->assemblyCatchRes($exception, $this->error_data);
        }
        $this->ajaxReturn($res);
    }

    /**
     *
     */
    public function createGroupOrder()
    {
        try {
            $is_cancel = 0; // 是否拆单
            $this->checkCreatOrCancelRequest($this->request_data, $is_cancel);
            $api_res = $this->GroupSkuService->createGroupOrder($this->request_data);
            if (empty($api_res)) {
                throw new Exception('请求处理返回异常');
            }
            $res = $api_res;
            if (2000 == $res['code']) {
                list($res['api_msg'], $res['msg']) = [$res['msg'], '提交成功'];
            } else {
                list($res['api_msg'], $res['msg']) = [
                    $res['msg'],
                    $res['msg'] . '提交失败'
                ];
            }
            $res['is_api'] = true;
        } catch (Exception $exception) {
            if (!empty($api_res)) {
                $error_data = $api_res;
            }
            if (!empty($this->error_data)) {
                $error_data = $this->error_data;
            }
            $res = $this->assemblyCatchRes($exception, $error_data);
        }
        $this->ajaxReturn($res);
    }

    private function checkCreatOrCancelRequest($data, $is_cancel)
    {
        if ($is_cancel === 0) { // #9485 打包选择SKU时归属店铺必填
            $rules = [
                'sku_id' => 'required|numeric|min:9000000000|max:9999999999',
                'warehouse_cd' => 'required|string|min:10|max:10',
                'sale_team_cd' => 'required|string|min:10|max:10',
                //'small_sale_team_cd' => 'required|string|min:10|max:10',
                'num' => 'required',
                //'ascription_store' => 'sometimes|numeric'
            ];
            $attributes = [
                'group_sku_id' => '组合 SKU',
                'warehouse_cd' => '仓库',
                'sale_team_cd' => '销售团队',
                //'small_sale_team_cd' => '销售小团队',
                'num' => '组合数量',
                //'ascription_store' => '归属店铺'
            ];
        } else {
            $rules = [
                'sku_id' => 'required|numeric|min:9000000000|max:9999999999',
                'warehouse_cd' => 'required|string|min:10|max:10',
                'sale_team_cd' => 'required|string|min:10|max:10',
                'num' => 'required',
            ];
            $attributes = [
                'group_sku_id' => '组合 SKU',
                'warehouse_cd' => '仓库',
                'sale_team_cd' => '销售团队',
                'num' => '组合数量',
            ];
        }

        $this->validateInCapture($data, $rules, $attributes);
    }

    /**
     *
     */
    public function cancelGroupOrder()
    {
        try {
            $is_cancel = 1; // 是否拆包
            $this->checkCreatOrCancelRequest($this->request_data, $is_cancel);
            $api_res = $this->GroupSkuService->cancelGroupOrder($this->request_data);
            if (empty($api_res)) {
                throw new Exception('请求处理返回异常');
            }
            $res = $api_res;
        } catch (Exception $exception) {
            if (!empty($api_res)) {
                $error_data = $api_res;
            }
            if (!empty($this->error_data)) {
                $error_data = $this->error_data;
            }
            $res = $this->assemblyCatchRes($exception, $error_data);
        }
        $this->ajaxReturn($res);

    }

    /**
     *
     */
    public function adoptGroupOrder()
    {
        try {
            $this->checkAuditGroupOrder($this->request_data);
            $api_res = $this->GroupSkuService->auditGroupOrder($this->request_data, 'adopt');
            if (empty($api_res)) {
                throw new Exception('请求处理返回异常');
            }
            $res = $api_res;
            if (2000 == $res['code']) {
                list($res['api_msg'], $res['msg']) = [$res['msg'], '审核成功'];
            } else {
                list($res['api_msg'], $res['msg']) = [$res['msg'], '审核失败'];
            }
        } catch (Exception $exception) {
            $res = $this->assemblyCatchRes($exception, $this->error_data);
        }
        $this->ajaxReturn($res);
    }

    /**
     *
     */
    public function rejectGroupOrder()
    {
        try {
            $this->checkAuditGroupOrder($this->request_data);
            $api_res = $this->GroupSkuService->auditGroupOrder($this->request_data, 'reject');
            if (empty($api_res)) {
                throw new Exception('请求处理返回异常');
            }
            $res = $api_res;
        } catch (Exception $exception) {
            $res = $this->assemblyCatchRes($exception, $this->error_data);
        }
        $this->ajaxReturn($res);
    }

    private function checkAuditGroupOrder($data)
    {
        $rules = [
            'group_bill_id' => 'required|numeric',
            'audit_status' => 'required|string|min:10|max:10',
        ];
        $attributes = [
            'group_bill_id' => '组合单号',
            'audit_status' => '审批状态',
        ];
        $this->validateInCapture($data, $rules, $attributes);
    }

    /**
     *
     */
    public function exportExcel()
    {
        $data = I('post_data');
        $request_data = DataModel::jsonToArr(htmlspecialchars_decode($data))['data'];
        $this->GroupSkuService
            ->outputExcel($request_data);
    }

}
