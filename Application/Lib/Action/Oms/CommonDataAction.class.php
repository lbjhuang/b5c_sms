<?php
/**
 * 拣货接口
 * User: b5m
 * Date: 2018/2/1
 * Time: 18:55
 */


class CommonDataAction extends BaseAction
{
    protected $whiteList = [];

    /**
     * 路由跳转方法
     *
     */
    public function pickingList()
    {
        $this->display();
    }

    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        if ('commondata' != ACTION_NAME) {
            parent::_initialize();
        }
        B('SYSOperationLog');
    }

    /**
     * 公共数据接口
     *
     */
    public function commonData()
    {
        $commonType ['currency'] = 'CommonDataModel::currency';
        $commonType ['currency_open'] = 'CommonDataModel::currencyOpen'; //开启币种
        $commonType ['company'] = 'CommonDataModel::company';
        $commonType ['transfer'] = 'CommonDataModel::transfer';
        $commonType ['account'] = 'CommonDataModel::account';
        $commonType ['turnOver'] = 'CommonDataModel::turnOver';
        $commonType ['accountListState'] = 'CommonDataModel::accountListState';
        $commonType ['saleTeams'] = 'CommonDataModel::saleTeams';
        $commonType ['stores'] = 'CommonDataModel::stores';
        $commonType ['countries'] = 'CommonDataModel::country';
        $commonType ['warehouses'] = 'CommonDataModel::warehouses';
        $commonType ['platform'] = 'CommonDataModel::platform';
        $commonType ['gudsType'] = 'CommonDataModel::gudsType';
        $commonType ['pickingSortType'] = 'CommonDataModel::pickingSortType';
        $commonType ['logisticsCompany'] = 'CommonDataModel::logisticsCompany';
        $commonType ['logisticsType'] = 'CommonDataModel::logisticsType';
        $commonType ['pickingTimeRangeIndex'] = 'CommonDataModel::pickingTimeRangeIndex';
        $commonType ['surfaceWayGet'] = 'CommonDataModel::surfaceWayGet';
        $commonType ['failStatus'] = 'CommonDataModel::failStatus';
        $commonType ['systemDocking'] = 'CommonDataModel::systemDocking';
        $commonType ['logicStatus'] = 'CommonDataModel::logicStatus';
        $commonType ['weight'] = 'CommonDataModel::weight';
        $commonType ['upcSku'] = 'CommonDataModel::upcSku';
        $commonType ['relationType'] = 'CommonDataModel::relationType';
        $commonType ['outgoingType'] = 'CommonDataModel::outgoingType';
        $commonType ['outStorage'] = 'CommonDataModel::outStorage';
        $commonType ['inStorage'] = 'CommonDataModel::inStorage';
        $commonType ['freightType'] = 'CommonDataModel::freightType';
        $commonType ['users'] = 'CommonDataModel::users';
        $commonType ['area'] = 'CommonDataModel::area';
        $commonType ['jobContent'] = 'CommonDataModel::jobContent';
        $commonType ['forwarding_company_cd'] = 'CommonDataModel::forwardingCompanyCd';
        $commonType ['butt_item_cd'] = 'CommonDataModel::buttItemCd';
        $commonType ['purTeams'] = 'CommonDataModel::purTeams';
        $commonType ['virType'] = 'CommonDataModel::virType';
        $commonType ['inventory_type'] = 'CommonDataModel::inventoryType';
        $commonType ['bwcOrderStatus'] = 'CommonDataModel::bwcOrderStatus';
        $commonType ['purchasingTeam'] = 'CommonDataModel::purchasingTeam';
        $commonType ['warehouse_operator'] = 'CommonDataModel::warehouseOperator';
        $commonType ['warehouse_type'] = 'CommonDataModel::warehouseType';
        $commonType ['site_cd'] = 'CommonDataModel::siteCd';
        $commonType ['goods_type'] = 'CommonDataModel::goodsTypeCd';
        $commonType ['planned_transportation_channel_cds'] = 'CommonDataModel::plannedTransportationChannelCds';
        $commonType ['insurance_claims_cd_map'] = 'CommonDataModel::getInsuranceClaimsCdMap';
        $commonType ['insurance_coverage_cd_map'] = 'CommonDataModel::getInsuranceCoverageCdMap';
        $commonType ['trademark'] = 'CommonDataModel::trademark';
        $commonType ['trademark_type'] = 'CommonDataModel::trademarkType';
        $commonType ['current_type'] = 'CommonDataModel::currentType';
        $commonType ['area_code'] = 'CommonDataModel::areaCode'; //国家
        $commonType ['existed_days_level'] = 'CommonDataModel::existedDaysLevel'; //在库天数级别
        $commonType ['review_type'] = 'CommonDataModel::reviewType';
        $commonType ['change_type'] = 'CommonDataModel::changeType';
        $commonType ['accounting_return_reason'] = 'CommonDataModel::accountingReturnReason';
        $commonType ['bill_status'] = 'CommonDataModel::billStatus';
        $commonType ['sale_channel'] = 'CommonDataModel::saleChannel';
        $commonType ['income_cost_sale_channel'] = 'CommonDataModel::incomeCostSourceChannelCode';
        $commonType ['transaction_type'] = 'CommonDataModel::transactionType';
        $commonType ['collection_account_type'] = 'CommonDataModel::collectionAccountType';
        $commonType ['commission_type'] = 'CommonDataModel::commissionType';
        $commonType ['settlement_type'] = 'CommonDataModel::settlementType';
        $commonType ['procurement_nature'] = 'CommonDataModel::procurementNature';
        $commonType ['invoice_information'] = 'CommonDataModel::invoiceInformation';
        $commonType ['invoice_type'] = 'CommonDataModel::invoiceType';
        $commonType ['bill_information'] = 'CommonDataModel::billInformation';
        $commonType ['payment_type'] = 'CommonDataModel::paymentType';
        $commonType ['relation_bill_type'] = 'CommonDataModel::relationBillType';
        $commonType ['payment_channel'] = 'CommonDataModel::paymentChannel';
        $commonType ['payment_method'] = 'CommonDataModel::paymentMethod';
        $commonType ['address_valid_conf'] = 'CommonDataModel::addressValidConf';
        $commonType ['send_warehouse_way'] = 'CommonDataModel::sendWarehouseWay';
        $commonType ['cabinet_type'] = 'CommonDataModel::cabinetType';
        $commonType ['customs_clear'] = 'CommonDataModel::customsClear';
        $commonType ['company_type'] = 'CommonDataModel::companyTypeCd';
        $commonType ['declareType'] = 'CommonDataModel::declareType';
        $commonType ['isElectric'] = 'CommonDataModel::isElectric';
        $commonType ['quoteStatus'] = 'CommonDataModel::quoteStatus';
        $commonType ['quoteLclStatus'] = 'CommonDataModel::quoteLclStatus';
        $commonType ['quoteType'] = 'CommonDataModel::quoteType';
        $commonType ['quoteIntentionType'] = 'CommonDataModel::quoteIntentionType';
        $commonType ['logisticsSupplier'] = 'CommonDataModel::logisticsSupplier';
        $commonType ['stuffingType'] = 'CommonDataModel::stuffingType';
        $commonType ['promotion_staus'] = 'CommonDataModel::promotionStausCd';
        $commonType ['return_warehouses'] = 'CommonDataModel::return_warehouses'; //退货仓库
        $commonType ['return_service'] = 'CommonDataModel::return_service'; //易达回邮单退货服务编号映射
        $commonType ['reply_status'] = 'CommonDataModel::replyStatus'; //易达回邮单获取状态映射
        $commonType ['coupon_source'] = 'CommonDataModel::couponSource';
        $commonType ['reply_order_warehouse'] = 'CommonDataModel::replyOrderWarehouse'; //OTTO_回邮单_仓库配置
        $commonType ['reply_order_express'] = 'CommonDataModel::replyOrderExpress'; //OTTO_回邮单_快递公司
        $commonType ['quote_small_teams'] = 'CommonDataModel::QuoteSmallTeams'; // 报价管理小程序

        $_request = $this->getParams();
        $params = ZUtils::filterBlank($_request['data']['query']);
        $type = $_request['data']['type'];
        foreach ($params as $key => $bool) {
            if ($bool) {
                switch ($key) {
                    case 'pickingSortType' :
                        $response [$key] = call_user_func_array($commonType [$key], [$type]);
                        break;
                    case 'pickingTimeRangeIndex':
                        $response [$key] = call_user_func_array($commonType [$key], [$type]);
                        break;
                    case 'area':
                        $response [$key] = call_user_func_array($commonType [$key], [$_request ['data']['query']['parentId']]);
                        break;
                    default:
                        $response [$key] = call_user_func_array($commonType [$key], []);
                        break;
                }
            }
        }

        $response = $this->formatOutput(2000, 'success', $response);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 订单状态回退
     * 1: 订单状态必须满足待拣货、待分拣、待核单
     * 2：根据订单的仓库，获取该仓库下是否已配置上诉流程
     * 3：状态回退只能往前回退，不能往下回退
     */
    public function orderReBack()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $orderBackModel = new OrderBackModel();
        $r = $orderBackModel->main($params);

        if ($r['code'] == 2000) {
            foreach ($r['data']['pageData'] as $key => $value) {
                if ($value['code'] == 2000 && $params['state'] == 'N001821000') {
                    $ms_ord_arr[] = $key;
                }
            }
            if (!empty($ms_ord_arr)) {
                Logs($ms_ord_arr, 'ms_ord_arr', 'ReBack');
                OrderModel::deleteB5cOrderNo($ms_ord_arr);

            }
        }
        //批量主动刷新订单
        (new OmsService())->saveAllOrderByB5cOrderNo($params['ordId'], __FUNCTION__);
        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['B5C_ORDER_NO'=>['in',$params['ordId']]]);
        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 一键通过
     * 1：订单状态必须满足待拣货、待分拣、待核单
     * 2：根据订单的仓库，获取该仓库下是否已配置上诉流程
     */
    public function oneKeyThrough()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        //判断是否退款
        if (!empty($params['ordId'])) {
            $res_refund = (new OmsAfterSaleService())->checkOrderRefund($params['ordId'], 'b5c_order_no');
            if (true !== $res_refund) {
                $this->ajaxReturn($res_refund);
            }
        }
        $orderOneKeyThroughtModel = new OrderOneKeyThroughModel();
        $r = $orderOneKeyThroughtModel->main($params);

        //批量主动刷新订单
        (new OmsService())->saveAllOrderByB5cOrderNo($params['ordId'], __FUNCTION__);

        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['B5C_ORDER_NO'=>['in',$params['ordId']]]);

        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 打印拣货单，打印分拣单，核单，出库/批量发货/称重发货前订单状态校验
     */
    public function preCheckAndDone()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $res = (new OrderBackModel())->preCheckAndDone($params);
        $this->ajaxReturn($this->formatOutput($res['code'], $res['msg'], $res['data']));
    }

    /**
     * 格式化输出
     *
     * @param int    $code 状态码
     * @param string $info 提示信息
     * @param array  $data 返回数据
     *
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg' => $info,
            'data' => $data
        ];

        return $response;
    }
}
