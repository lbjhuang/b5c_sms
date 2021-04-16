<?php

/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 13:33
 */


/**
 * Class WarehousingAction
 */
class WarehousingAction extends BaseAction
{
    /**
     * @var WarehousingService
     */
    protected $service;

    /**
     * @return bool|void
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->service = new WarehousingService();
    }

    /**
     *
     */
    public function returnOutList()
    {
        try {
            $data = DataModel::getDataNoBlankToArr();
            $data['search'] = $this->updateSearchData($data['search']);
            $this->checkReturnOutList($data);
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $this->service->returnOutList($data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function updateSearchData($data)
    {

        return $data;
    }

    /**
     * @param $data
     * @throws Exception
     */
    private function checkReturnOutList($data)
    {
        $rules = [
            'search.outbound_status' => 'numeric',
            "search.warehouse_cd_arr" => "array",
            "search.purchase_team_cd_arr" => "array",
            "search.our_company_cd_arr" => "array",
            "search.created_by" => "string",

            'page.per_page' => 'sometimes|required|numeric',
            'page.current_page' => 'sometimes|required|numeric',
        ];
        $customAttributes = [
            'search.outbound_status' => '出库状态',
            "search.warehouse_cd_arr" => "仓库",
            "search.purchase_team_cd_arr" => "采购团队",
            "search.our_company_cd_arr" => "我方公司",
            "search.created_by" => "发起人",

            'pages.per_page' => '每页数量',
            'pages.current_page' => '当前页数',
        ];
        $this->validate($rules, $data, $customAttributes);
    }


    /**
     *
     */
    public function returnDeliverDetails()
    {
        try {
            $data['id'] = I('id');
            $this->checkReturnDeliverDetails($data);
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $res['data'] = $this->service->returnDeliverDetails($data['id']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     * @throws Exception
     */
    private function checkReturnDeliverDetails($data)
    {
        $rules = [
            'id' => 'required|numeric'
        ];
        $customAttributes = [
            'id' => 'ID',
        ];
        $this->validate($rules, $data, $customAttributes);
        if (!$this->service->checkHasTbPurReturnId($data['id'])) {
            throw new Exception(L('采购退货 ID 不存在'));
        }
    }

    /**
     *
     */
    public function returnDeliveryConfirmation()
    {
        try {
            $Model = new Model();
            $data = DataModel::getDataNoBlankToArr()['data'];
            $this->checkReturnDeliveryConfirmation($data);
            $res = DataModel::$success_return;
            $res['code'] = 200000;
            $Model->startTrans();
            $res['data'] = $this->service->returnDeliveryConfirmation($data, $Model);
            (new TbPurActionLogModel())->addLog($data['relevance_id'], 'delivery_confirmation');
            D('Purchase/Ship', 'Logic')->updateShipStatus($data['relevance_id']);
            $Model->commit();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $Model->rollback();
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $data
     * @throws Exception
     */
    private function checkReturnDeliveryConfirmation($data)
    {
        $rules = [
            'id' => 'required|numeric',
            'relevance_id' => 'numeric',
            'return_no' => 'required|string',
            "logistics_information.logistics_number" => "required|string|max:255",
            "logistics_information.estimate_arrive_date" => "required|date",
            "logistics_information.estimate_logistics_cost_currency_cd" => "required|string|size:10",
            "logistics_information.estimate_logistics_cost" => "required|numeric",
            "logistics_information.estimate_other_cost_currency_cd" => "required|string|size:10",
            "logistics_information.estimate_other_cost" => "required|numeric",
        ];
        $customAttributes = [
            "relevance_id" => "采购关联id",
            "return_no" => "采购退货单号",
            "logistics_information.logistics_number" => "物流单号",
            "logistics_information.estimate_arrive_date" => "预计到达日期",
            "logistics_information.estimate_logistics_cost_currency_cd" => "预估物流币种",
            "logistics_information.estimate_logistics_cost" => "预估物流费用",
            "logistics_information.estimate_other_cost_currency_cd" => "预计其他费用币种",
            "logistics_information.estimate_other_cost" => "预计其他费用",
        ];
        $check_code_arr = [
            'estimate_logistics_cost_currency_cd' => 'N000590',
            'estimate_other_cost_currency_cd' => 'N000590',
        ];
        $this->validate($rules, $data, $customAttributes);
        $this->checkCodeRight($data['logistics_information'], $customAttributes, $check_code_arr);
        if (!$this->service->checkHasTbPurReturnId($data['id'])) {
            throw new Exception(L('采购退货 ID 不存在'));
        }
    }
}