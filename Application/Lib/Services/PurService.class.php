<?php

/**
 * User: fuming
 * Date: 19/02/25
 * Time: 18:31
 */

@import("@.Model.BaseModel");

/**
 * Class PurService
 */
class PurService extends Service
{

    const PUR_CLAIM_CODE = 'N002630200';//采购退款认领

    const OPERATION_CREATE_ORDER_CODE = 'N002870001'; // 订单创建
    const OPERATION_WAREHOUSE_END_REVOKE_CODE = 'N002870006'; // 撤回入库完结
    const OPERATION_WAREHOUSE_END_CODE = 'N002870005'; // 标记入库完结
    const OPERATION_CLAIM_DEL_CODE = 'N002870010'; // 采购退款认领记录删除 
    const OPERATION_CLAIM_EDIT_CODE = 'N002870009'; // 编辑采购退款认领(抵扣金，根据tb_fin_claim.id获取 claim_amount)
    const OPERATION_WAREHOUSE_CODE = 'N002870004'; // 采购入库确认（残次品）
    const OPERATION_CONVERSION_CODE = 'N002870011'; // 正品转残次（同步采购单）
    const OPERATION_CONVERSION_AGAIN_CODE = 'N002870012'; // 残次转正品（同步采购单）

    const OPERATION_SHIP_CODE = 'N002870002'; // 采购发货确认
    const OPERATION_SHIP_REVOKE_CODE = 'N002870013'; // 采购发货撤回

    const OPERATION_WAREHOUSE_NORMAL_CODE = 'N002870003'; // 采购入库确认 （正品）
    const OPERATION_ADVANCE_PAY_REVOKE_CODE = 'N002870014'; // 预付款付款撤回（应付状态从"已完成"撤回）
    const OPERATION_ADVANCE_PAY_CODE = 'N002870015'; // 预付款付款（应付单状态变为“已完成”）
    const OPERATION_RETURN_GOODS_CODE = 'N002870016'; // 采购退货出库（正品）
    const OPERATION_SHIP_END_CODE = 'N002870017'; // 标记发货完结
    const OPERATION_SHIP_END_REVOKE_CODE = 'N002870018'; // 撤回发货完结

    const STOCK_TYPE = 'N002440100'; // 正品
    const STOCK_SECONDS_TYPE = 'N002440400'; // 次品






    /**
     * @var PurRepository
     */
    private $PurRepository;

    private $user_name;
    public $model;
    /**
     * @var PurRepository
     */
    protected $repository;

    public function __construct($model)
    {
        $this->model      = empty($model) ? new Model() : $model;
        $this->repository = $this->PurRepository = new PurRepository();
        $this->user_name  = DataModel::userNamePinyin();
    }

    // 余额转账
    public function deductionTransfer($request, $model)
    {
        if (!$request['order_no']) {
            throw new \Exception('缺失采购单号参数~');
        }
        if (!$request['amount_deduction']) {
            throw new \Exception('缺失转账金额参数~');
        }
        if ($request['amount_deduction'] <= 0 ) {
            throw new \Exception('转账金额参数需大于0~');
        }
        $relevanceArr = $this->repository->getPurOrderFieldByMap(['pro.relevance_id'], ['t.procurement_number' => $request['order_no']]);
        $relevance_id = array_column($relevanceArr, 'relevance_id')[0];
        if (!$relevance_id) {
            throw new \Exception('采购单输入有误请核实~');
        }

        $addData['relevance_id'] = $relevance_id;
        $addData['amount_deduction'] = $request['amount_deduction'];
        $addData['deduction_type_cd'] = 'N002660202';
        $logic      = D('Purchase/Payment','Logic'); //生成余额明细, 扣减账户
        $res        = $logic->cutUseDeduction($addData);
        if($res === false) {
            ELog::add('余额扣减异常'.json_encode($addData).M()->getDbError(),ELog::ERR);
            throw new \Exception('余额扣减异常');
        }

        $operationAddInfo['main_id'] = $res;
        $operationAddInfo['clause_type'] = '';
        $operationAddInfo['money_type'] = '2';
        $operationAddInfo['action_type_cd'] = 'N002870023';
        $operationAddInfo['bill_no'] = '';
        $operationAddInfo['created_by'] = DataModel::userNamePinyin();
        $re = D('Scm/PurOperation')->add($operationAddInfo); //生成触发操作
        if ($re === false) {
            $report_res = [$addData, $operationAddInfo];
            ELog::add('余额扣减异常'.json_encode($report_res).M()->getDbError(),ELog::ERR);
            @SentinelModel::addAbnormal('采购触发操作生成抵扣/应付记录', '余额扣减异常', $report_res, 'scm_operation_notice');
            throw new \Exception('触发操作记录生成失败');
        }
        
        //生成返利赔偿明细
        //增加返利赔偿账户/增加返利赔偿账户金额
        $addCompenData['relevance_id'] = $relevance_id;
        $addCompenData['amount_deduction'] = $request['amount_deduction'];
        $addCompenData['order_no'] = $request['order_no'];
        $addCompenData['deduction_type_cd'] = 'N002660202';
        $comRes        = $logic->regardAsDeductionCompensation($addCompenData);
        if($comRes === false) {
            ELog::add('赔偿金余额生成异常'.json_encode($addCompenData).M()->getDbError(),ELog::ERR);
            throw new \Exception('赔偿金余额生成异常');
        }


        // 生成关联记录
        $relaData['ded_id'] = $res;
        $relaData['ded_com_id'] = $comRes;
        $relaData['created_by'] = DataModel::userNamePinyin();
        $relaRes = M('pur_deduction_compensation_relationship', 'tb_')->add($relaData);
        if ($relaRes === false) {
            ELog::add('赔偿金余额关联记录生成异常'.json_encode($relaData).M()->getDbError(),ELog::ERR);
            throw new \Exception('赔偿金余额关联记录生成异常');
        }
        return true;
        
    }

    public function getPurOrderFieldByMap($request)
    {
        $res = [];
        $map['pro.order_status'] = TbPurRelevanceOrderModel::$order_status['not_cancelled'];
        $map['t.supplier_new_id'] = $request['supplier_id'];
        $map['t.amount_currency'] = $request['amount_currency'];
        $map['t.our_company'] = $request['our_company'];
        $res = $this->repository->getPurOrderFieldByMap(['procurement_number'], $map);
        if ($res) {
            $res = array_column($res, 'procurement_number');
        }
        return $res;
    }

    public function getHistSmallTeamGoodsNums($small_team_code = '', $sku_id = '', $relevance_id = '', $procurement_number)
    {
        $sql = "SELECT
    wbo.occupy_num,wbo.vir_type
FROM
    tb_wms_batch_order wbo
    LEFT JOIN tb_wms_batch wb ON wbo.batch_id = wb.id
    LEFT JOIN tb_pur_return pr ON pr.return_no = wbo.ORD_ID
    LEFT JOIN tb_pur_return_order pro ON pro.return_id = pr.id 
WHERE
    ( wbo.vir_type = 'N002440100' OR wbo.vir_type = 'N002440400' ) 
AND 
    wbo.SKU_ID = '{$sku_id}' 
AND 
    wbo.operate_type = '5' 
AND 
    wb.small_sale_team_code = '{$small_team_code}' 
AND 
    pro.relevance_id = '{$relevance_id}'
AND
    wb.purchase_order_no = '{$procurement_number}'";

        $res = M()->query($sql);
        $data[self::STOCK_TYPE] = 0;
        $data[self::STOCK_SECONDS_TYPE] = 0;
        foreach ($res as $key => $value) {
            $data[$value['vir_type']] = $value['occupy_num'];
        }
        return $data;
    }
    public function getSmallTeamNormalGoodsNums($detail)
    {
        foreach ($detail['goods'] as $key => &$value) {
            $sell_team_arr = []; $goods_sell_team_arr = [];
            $sell_team = json_decode($value['information']['sell_small_team_json'], true); // 该订单该SKU所有小团队的订单数量
            $goods_sell_team = json_decode($value['sell_small_team_json'], true);  // 该订单该SKU所有小团队的历史正品采购入库数量和历史残次品采购入库数量
            foreach ($goods_sell_team as $kk => $vv) {
                $goods_sell_team_arr[$vv['small_team_code']] = $vv;
            }
            foreach ($sell_team as $k => $v) { // 该订单该SKU该小团队的（【订单数量】-【（订单）历史正品采购入库数量】-【（订单）历史残次品采购入库数量】+【（订单）历史正品采购退货出库数量】+【（订单）历史残次品采购退货出库数量】）
                // 获取该订单该SKU该小团队【（订单）历史正品采购退货出库数量】+【（订单）历史残次品采购退货出库数量】
                if (!$value['information']['sku_information'] || !$detail['relevance_id'] || !$detail['relevance']['orders']['procurement_number']) {
                    continue;
                }
                $histNumArr = $this->getHistSmallTeamGoodsNums($v['small_team_code'], $value['information']['sku_information'], $detail['relevance_id'], $detail['relevance']['orders']['procurement_number']);
                $value['default_goods_nums'][$v['small_team_code']] = $v['number'] - $goods_sell_team_arr[$v['small_team_code']]['warehouse_number'] - $goods_sell_team_arr[$v['small_team_code']]['warehouse_number_broken'] + $histNumArr[self::STOCK_TYPE] + $histNumArr[self::STOCK_SECONDS_TYPE];
            }
            
        }
        return $detail;
    }
    /**
     * @param $request_data
     * @return array
     */
    public function getOrderSelect($request_data)
    {
        $wheres = $this->joinOrderSelectWhere($request_data);
        if (count($wheres) <= 1) {
            //禁止搜索所有采购单，耗性能
            return null;
        }
        $res_db = $this->PurRepository->getOrderSelect($wheres);
        $res_db = $this->orderSelectConversion($res_db);
        return $res_db;
    }

    /**
     * @param $data
     * @return array
     */
    private function joinOrderSelectWhere($data)
    {
        $map = [
            'client_name' => 'tb_pur_order_detail.supplier_id',
            'account_turnover_id' => 'tb_fin_claim.account_turnover_id',
            'sale_pur_person' => 'tb_pur_relevance_order.prepared_by'
        ];
        $like_keys = [
            'client_name',
        ];
        $wheres = DataModel::joinDbWheres($data, $map, $like_keys);
        /*if ($data['search_type'] && $data['search_value']) {
            switch ($data['search_type']) {
                case 'PO_ID':
                    $where_order_key = 'tb_pur_order_detail.procurement_number';
                    break;
                case 'THR_PO_ID':
                    $where_order_key = 'tb_pur_order_detail.online_purchase_order_number';
                    break;
            }
            if ($where_order_key) {
                $wheres[$where_order_key] = ['LIKE', "%{$data['search_value']}%"];
            }
        }*/
        if ($data['search_value']) {
            $conditions['tb_pur_order_detail.procurement_number']           = $data['search_value'];
            $conditions['tb_pur_order_detail.online_purchase_order_number'] = $data['search_value'];
            $conditions['_logic'] = 'or';
            $wheres['_complex']   = $conditions;
        }
        return $wheres;
    }

    /**
     * @param $res_db
     * @return array
     */
    private function orderSelectConversion($res_db)
    {
        foreach ($res_db as $key => &$value) {
            if ($value['claim_id']) {
                unset($res_db[$key]);
            }
            $value['claim_code'] = self::PUR_CLAIM_CODE;
        }
        $res_db = array_values($res_db);
        $res_db = CodeModel::autoCodeTwoVal($res_db, [
            'amount_currency',
            'payment_company',
            'claim_code',
            'sell_team'
        ]);
        $res_db = $this->orderStatusToVal($res_db);
        return $res_db;
    }

    /**
     * @param $res_db
     * @param bool $is_one
     * @return mixed
     */
    public function orderStatusToVal($res_db, $is_one = false)
    {
        $maps = [
            'payment_nature' => [
                0 => '',
                1 => '对公',
                2 => '对私',
            ],
            'contract_information' => [
                -1 => '无合同',
                0 => '无合同',
                1 => '有合同',
            ],
            'warehouse_status' => [
                '待入库',
                '部分入库',
                '入库完成',
            ],
            'payment_status' => [
                '待付款',
                '部分付款',
                '付款完成',
            ],
            'has_refund' => [
                '无退款',
                '有退款',
            ],
            'has_return_goods' => [
                '无退货',
                '有退货',
            ],
            'claim_code' => [
                self::PUR_CLAIM_CODE => '采购退款认领'
            ],
            'payable_status' => TbPurPaymentAuditModel::$status_map,

            'fee_status' => [
                '待确认',
                '待确认付款账户',
                '待出账',
                '已完成',
                '待审核',
                '已删除',
                '待业务审核',
            ],
            'pay_remainder' => [
                '不需要支付',
                '继续支付',
                '本次结束',
            ],
        ];
        return $this->statusToValMap($res_db, $maps, $is_one);
    }

    /**
     * @param $res_db
     * @param $is_one
     * @param $maps
     * @param $value
     * @return array
     */
    private function statusToValMap($res_db, $maps, $is_one)
    {
        $map_keys = array_keys($maps);
        if ($is_one) {
            $res_db = $this->statusKeyMapForeach($map_keys, $res_db, $maps);
        } else {
            foreach ($res_db as &$value) {
                $value = $this->statusKeyMapForeach($map_keys, $value, $maps);
            }
        }
        return $res_db;
    }

    /**
     * @param $map_keys
     * @param $value
     * @param $maps
     * @return mixed
     */
    private function statusKeyMapForeach($map_keys, $value, $maps)
    {
        foreach ($map_keys as $map_key) {
            if (false !== $value[$map_key] && $maps[$map_key][$value[$map_key]]) {
                $value[$map_key . '_val'] = $maps[$map_key][$value[$map_key]];
            }
        }
        return $value;
    }

    public function updatePurRelevanceOrderRefund($order_id)
    {
        $has_refund = (bool)$this->repository->checkPurchaseRefundStatus($order_id);
        return $this->repository->updatePurRelevanceOrderRefund($order_id, $has_refund);
    }

    // 校验参数 && 是否符合条件
    public function checkVaild($request_data)
    {
        if (!$request_data) {
            throw new \Exception('缺失参数~');
        }
        if (!$request_data['action_type_cd']) {
            throw new \Exception('触发操作不可为空~');
        }

        if ($request_data['money_type']) { // 仅用于检测是否需要弹窗显示金额
            // 历史数据不需要弹窗展示(待定，目前指的是使用条款不是通用时，需要检测)
            // 不符合适用条件的，不需要弹窗展示
            // 目前需要适用条件的有
            /*
            n  采购预付应付撤回（已完成——>待付款）payment_id
            n  采购预付应付确认（待付款——>已完成&待出账——>已完成）payment_id 需要同时有预付和尾款

            n  撤回入库完结（尾款-每次发货后 X 天付款）ship_id
            n  标记入库完结（尾款-每次发货后 X 天付款）ship_id
            n  采购发货撤回（尾款-每次发货后 X 天付款）ship_id
            n  采购入库确认（残次品）（尾款-每次发货后 X 天付款）ship_id
            */
            $is_show = '1';
            if (!isset($request_data['relevance_id'])) {
                $tbWmsPayment = D('tbWmsPayment')->where(['payment_audit_id' => $request_data['payment_audit_id']])->find();
                $request_data['relevance_id'] = $tbWmsPayment['relevance_id'];
            }
            $is_after = D('Scm/PurOperation')->checkAfterCreateTime($request_data['relevance_id']); //创建时间是否晚于上线时间
            switch ($request_data['action_type_cd']) {
                case self::OPERATION_CONVERSION_CODE; // 正品转残次（同步采购单
                case self::OPERATION_CONVERSION_AGAIN_CODE; // 残传正（同步采购单）
                    // 正次品转换（不需要同步采购单的，无需弹窗）
                    if (strval($request_data['affect_supplier_settlement']) !== '1') {
                        $is_show = '0';   
                    }
                    break;
                // 根据relevance_id 获取相关条款
                case self::OPERATION_RETURN_GOODS_CODE; //采购退款出库
                    $clauseInfo = M('clause', 'tb_pur_')->where(['purchase_id' => $request_data['relevance_id']])->select();
                    if (!$clauseInfo) {
                        $is_show = '0';
                        break;
                    }

                    $model = new Model();
                    //N002440100:return_number_normal N002440400:return_number_broken
                    //2019-08-23 产品调整需求:采购退款出库只有残次品则不显示弹框
                    $return_goods = $model->table('tb_pur_return_goods')->where(['return_id' => $request_data['money_id'], 'vir_type_cd' => 'N002440100'])->select();
                    if (!$return_goods) {
                        $is_show = '0';
                        break;
                    }

                    $is_show = '1';

                    

                    // 尾款-每次发货后X天付款 符合弹窗条件 改为通用
                    /*$end_fund_ship_flag = false;
                    foreach ($clauseInfo as $key => $value) {
                        if ($value['clause_type'] === '2' && $value['action_type_cd'] === 'N001390200') {
                            $end_fund_ship_flag = true;
                            break;
                        }
                    }
                    $purOperationModel = D('Scm/PurOperation');
                    $is_after_sec = $purOperationModel->checkAfterCreateTime($request_data['relevance_id'], $purOperationModel->START_TIME_SEC);
                    if (!$is_after_sec) { // 旧订单走旧逻辑 #9953
                        // 无尾款 符合弹窗条件
                        $no_fund_of_end = $purOperationModel->checkHasFundOfEnd($request_data['relevance_id']); //是否是无尾款 移除无尾款条件 from#9953
                        if (!$no_fund_of_end && !$end_fund_ship_flag) { // 不符合时，不弹窗
                            $is_show = '0';
                        }
                    } else {
                        if (!$end_fund_ship_flag) { // 不符合时，不弹窗
                            $is_show = '0';
                        }
                    }*/
                    
                    
                    break;

                // 根据ship_id 获取适用条款
                case self::OPERATION_WAREHOUSE_NORMAL_CODE: // 采购入库确认（正品） 【尾款-每次入库后X天付款 & 无尾款】
                    if (!$is_after) {//创建时间晚于上线时间 直接显示弹框
                        break;
                    }
                    $clauseInfo = $this->repository->getClauseInfoByOperID($request_data['money_id'], 'tb_pur_ship');
                    $relevance_id = $clauseInfo[0]['purchase_id'];
                    $is_show = '0';
                    if ($clauseInfo) {
                        $no_fund_of_end = D('Scm/PurOperation')->checkHasFundOfEnd($relevance_id); //是否是无尾款
                        //无尾款则显示弹窗
                        if ($no_fund_of_end == true) {
                            $is_show = '1';
                            break;
                        }
                        foreach ($clauseInfo as $key => $value) {
                            if ($value['clause_type'] === '2' && $value['action_type_cd'] === 'N001390400') {
                                $is_show = '1';
                                break;
                            }
                        }
                    }
                    break;
                case self::OPERATION_WAREHOUSE_END_REVOKE_CODE; // 撤回入库完结
                case self::OPERATION_WAREHOUSE_END_CODE; // 标记入库完结
                case self::OPERATION_WAREHOUSE_CODE; // 采购入库确认（残次品）
                    $clauseInfo = $this->repository->getClauseInfoByOperID($request_data['money_id'], 'tb_pur_ship');
                    $relevance_id = $clauseInfo[0]['purchase_id'];
                    $is_show = '0';
                    if ($clauseInfo) {
                        $purOperationModel = D('Scm/PurOperation');
                        $is_after_sec = $purOperationModel->checkAfterCreateTime($relevance_id, $purOperationModel->START_TIME_SEC);
                        if (!$is_after_sec) {
                            $no_fund_of_end = $purOperationModel->checkHasFundOfEnd($relevance_id); //是否是无尾款
                            //无尾款则显示弹窗 去掉无尾款from #9953
                            if ($no_fund_of_end == true) {
                                $is_show = '1';
                                break;
                            }
                        }
                        
                      foreach ($clauseInfo as $key => $value) {
                        if ($value['clause_type'] === '2' && $value['action_type_cd'] === 'N001390200') {
                          $is_show = '1';
                          break;
                        }
                      }
                    }
                    break;
                case self::OPERATION_SHIP_REVOKE_CODE; // 采购发货撤回
                    $clauseInfo = $this->repository->getClauseInfoByOperID($request_data['money_id'], 'tb_pur_ship');
                    $relevance_id = $clauseInfo[0]['purchase_id'];
                    $is_show = '0';
                    if ($clauseInfo) {
                        foreach ($clauseInfo as $key => $value) {
                            if ($value['clause_type'] === '2' && $value['action_type_cd'] === 'N001390200') {
                              $is_show = '1';
                              break;
                            }
                        }
                    }
                    break;

                // 根据应付id获取适用条款
                case self::OPERATION_ADVANCE_PAY_CODE; //需求9798抵扣金【预付款付款（应付单状态变为“已完成”）】，适用条款由【同时有预付款&尾款】变更为【有预付款】
                case self::OPERATION_ADVANCE_PAY_REVOKE_CODE; //需求9798抵扣金【 预付款付款撤回（应付状态从"已完成"撤回）】，适用条款由【同时有预付款&尾款】变更为【有预付款】
                    $is_show = '0';
                    $clauseInfo = $this->repository->getClauseInfoByOperID($request_data['money_id'], 'tb_pur_payment'); // 需要满足有预付款的情况，并且该应付是由订单创建触发产生的预付应付
                    if (!$is_after) {//创建时间晚于上线时间 走新逻辑
                        if ($clauseInfo) {
                            if (count($clauseInfo) >= '1') {
                                foreach ($clauseInfo as $key => $value) {
                                    $action_type_cd = M('pur_operation', 'tb_')->where(['main_id' => $request_data['money_id']])->getField('action_type_cd');
                                    if ($action_type_cd == self::OPERATION_CREATE_ORDER_CODE) {
                                        $is_show = '1';
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    if ($clauseInfo) {
                        if (count($clauseInfo) >= '2') {
                          foreach ($clauseInfo as $key => $value) {
                            if ($value['clause_type'] === '2') {
                                $action_type_cd = M('pur_operation', 'tb_')->where(['main_id' => $request_data['money_id']])->getField('action_type_cd');
                                if ($action_type_cd == self::OPERATION_CREATE_ORDER_CODE) {
                                    $is_show = '1';
                                }
                                break;
                            }
                          }
                        }
                    }
                    break;
                case self::OPERATION_SHIP_END_CODE;//标记发货完结
                case self::OPERATION_SHIP_END_REVOKE_CODE;//撤回发货完结
                    $purOperationModel = D('Scm/PurOperation');
                    $is_show = '0';
                    $is_after_sec = $purOperationModel->checkAfterCreateTime($request_data['relevance_id'], $purOperationModel->START_TIME_SEC);
                    if (!$is_after_sec) { // 仅限历史订单
                        $no_fund_of_end = $purOperationModel->checkHasFundOfEnd($request_data['relevance_id']); //是否是无尾款
                        //无尾款则显示弹窗
                        if ($no_fund_of_end == true) {
                            $is_show = '1';
                        }
                    }
                    
                    break;
                default:
                    # code...
                    break;
            }


        }

        return $is_show;
    }

    // 统一格式返回
    public function retOperationAmountFormat($amount, $type, $currency = '', $is_show = '0')
    {
        if (!$type) { // 没有传生成金额类型，表明是内部调用，只需获取金额即可
            return $amount;
        }
        if (empty($amount)) { // 金额为0不需要弹窗展示
           $is_show = '0';
           $result = [
                'is_show' => $is_show,
                'pre_pay_info' => [],
                'end_pay_info' => [],
           ];
           return $result;
        }

        // 获取币种数组
        $cd_arr = ['N00059'];
        $moneyArr = CodeModel::getCodeKeyValArr($cd_arr, null);

        if (is_array($amount)) { // 生成多个采购单对应的金额
            $newAmount = '';
            $res_info = [];
            $is_show = '0';
            foreach ($amount as $key => $value) {
                if ($value) {
                    $res_info[] = ['currency_type' => $moneyArr[$currency[$key]], 'amount' => $value];
                    $is_show = '1';
                }
            }
        } else {
            $currency = $moneyArr[$currency];
            $res_info = ['currency_type' => $currency, 'amount' => $amount];
        }


        $result  = [];

        switch (strval($type)) {
            case '1':
                $result = [
                    'is_show' => $is_show,
                    'pre_pay_info' => $res_info,
                ];
                break;
            case '2':
                $result = [
                    'is_show' => $is_show,
                    'end_pay_info' => $res_info,
                ];
                break;
            
            default:
                # code...
                break;
        }
        
        return $result;  
    }
    // 获取抵扣金或应付金额
    public function getShipGoodsAmountSum($ship_id, $type, $is_show)
    {
        $res = $this->repository->getShipGoodsAmountSum($ship_id);

        if (!$res) {
            return false;
        }

        $amount_in_ship = '0.00';
        //同一个采购订单多个商品分别计算在求和
        //2019-08-23 产品调整需求 标记发货完结时入库数量需要加上残次品入库数量
        foreach ($res as $v) {
            $amount_in_ship = bcadd($amount_in_ship, bcmul(bcadd($v['unit_price'], $v['unit_expense'], 8), ($v['ship_number'] - $v['warehouse_number'] - $v['warehouse_number_broken']), 8), 2);//
        }

        return $this->retOperationAmountFormat($amount_in_ship, $type, $res[0]['amount_currency'], $is_show);     
    }

    // 获取累计生成预付款应付金额
    public function advanceAmountCal($relevance_id)
    {
        if(!$relevance_id) return false;
        $info = $this->repository->advanceAmountCal($relevance_id);
        return $info['advanceAmount'];
    }
    // 根据触发操作获取对应的抵扣金或应付金额
    public function getOperationAmount($request_data)
    {
        try {
        $is_show = $this->checkVaild($request_data);
        if ($is_show == '0') {
            if ($request_data['money_type']) { // 用于弹窗显示
                $result = [
                     'is_show' => $is_show,
                     'pre_pay_info' => [],
                     'end_pay_info' => [],
                ];
                return $result;
            } else { // 用于接口调用
                $result = '0';
                return $result;
            }
        }

        switch (strval($request_data['action_type_cd'])) {
            case self::OPERATION_RETURN_GOODS_CODE; //采购退款出库
                if (!$request_data['money_id'] || !$request_data['relevance_id']) {
                    throw new \Exception('采购退款出库id或relevance_id不可为空~');
                }
                // 获取采购退款出库的数量和单价
                $return_info = $this->repository->getReturnOrderInfoByReturnID($request_data['money_id']);
                $amount = $currency = [];
                foreach ($return_info as $k => $v) {
                    $amount[$v['relevance_id']] = bcadd($amount[$v['relevance_id']], bcmul(bcadd($v['unit_price'], $v['unit_expense'], 8), $v['return_number'], 8), 2);
                    $currency[$v['relevance_id']] = $v['amount_currency'];     
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $currency, $is_show);
                break;
            case self::OPERATION_ADVANCE_PAY_CODE: // 预付款付款
                if (!$request_data['money_id']) { // tb_pur_payment.id
                    throw new \Exception('应付记录id不可为空~');
                }
                $payInfo = $this->repository->getInfoByPaymentID($request_data['money_id']);
                $finalAmount = $payInfo['use_deduction'] ? bcadd($payInfo['amount_confirm'], $payInfo['amount_deduction'], 2) : $payInfo['amount_confirm'];
                $res = $this->retOperationAmountFormat($finalAmount, $request_data['money_type'], $payInfo['amount_currency'], $is_show);
                break;
            case self::OPERATION_ADVANCE_PAY_REVOKE_CODE: // 预付款付款撤回（应付状态从"已完成"撤回）
                if (!$request_data['money_id']) { // tb_pur_payment.id
                    throw new \Exception('应付记录id不可为空~');
                }
                $payInfo = $this->repository->getInfoByPaymentID($request_data['money_id']);
                $finalAmount = $payInfo['use_deduction'] ? bcadd($payInfo['amount_confirm'], $payInfo['amount_deduction'], 2) : $payInfo['amount_confirm'];
                $res = $this->retOperationAmountFormat($finalAmount, $request_data['money_type'], $payInfo['amount_currency'], $is_show);
                break;
            case self::OPERATION_SHIP_REVOKE_CODE: // 采购发货撤回
                if (!$request_data['detail'] && !$request_data['money_id']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                if ($request_data['money_id']) {
                    $res = $this->repository->getShipGoodsAmountSum($request_data['money_id']);
                    $amount_in_ship = '0.00';
                    //同一个采购订单多个商品分别计算在求和
                    //2019-08-23 产品调整需求 标记发货完结时入库数量需要加上残次品入库数量
                    foreach ($res as $v) {
                        $amount_in_ship = bcadd($amount_in_ship, bcmul(bcadd($v['unit_price'], $v['unit_expense'], 8), ($v['ship_number'] - $v['warehouse_number'] - $v['warehouse_number_broken']), 8), 2);//
                    }
                    $res = $this->retOperationAmountFormat($amount_in_ship, $request_data['money_type'], $res[0]['amount_currency'], $is_show);  
                } else {
                    $amount = '0.00'; // 总(本次发货数量*采购价),根据information_id获取对应的单价
                    foreach ($request_data['detail'] as $key => $value) {
                        $price = '0.00';
                        $priceInfo = $this->repository->getPriceByInformationID($value['information_id']);
                        $amount    = bcadd($amount, bcmul($value['ship_number'], bcadd($priceInfo['unit_price'], $priceInfo['unit_expense'], 8), 8), 2);
                    }

                    $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo['amount_currency'], $is_show);
                }

                break;
            case self::OPERATION_SHIP_CODE: // 采购发货确认
                if (!$request_data['detail']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                $amount = '0.00'; // 总(本次发货数量*采购价),根据information_id获取对应的单价
                foreach ($request_data['detail'] as $key => $value) {
                    $price = '0.00';
                    $priceInfo = $this->repository->getPriceByInformationID($value['information_id']);
                    $amount    = bcadd($amount, bcmul($value['ship_number'], bcadd($priceInfo['unit_price'], $priceInfo['unit_expense'], 8), 8), 2);
                }              
                /*$advanceAmount = $this->advanceAmountCal($request_data['relevance_id']); // 累计生成预付款
                $finalAmount = bcsub($amount, $advanceAmount, 2);*/
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo['amount_currency'], $is_show);
                break;

            case self::OPERATION_WAREHOUSE_END_REVOKE_CODE: // 撤回入库完结
                if (!$request_data['money_id']) {
                    throw new \Exception('money_id不可为空~');
                }
                // 根据tb_pur_ship_goods.ship_id获取对应的商品sku和information_id含税价格
                $res = $this->getShipGoodsAmountSum($request_data['money_id'], $request_data['money_type'], $is_show);
                break;

            case self::OPERATION_WAREHOUSE_NORMAL_CODE: // 采购入库确认（正品）
                if (!$request_data['detail']) {
                    throw new \Exception('商品详情信息不可为空~');
                }                
                $amount = '0.00'; // 根据goods_id获取到采购价，然后乘以数量，汇总求和
                foreach ($request_data['detail'] as $key => $value) {
                    $priceInfo = $this->repository->getPriceByGoodsID($value['goods_id']);
                    $amount = bcadd($amount, bcmul(bcadd($priceInfo['unit_price'], $priceInfo['unit_expense'], 8), $value['number'], 8), 2);
                }

                // 累计生成预付款
                /*$advanceAmount = $this->advanceAmountCal($request_data['relevance_id']);
                $finalAmount = bcsub($amount, $advanceAmount, 2);*/
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo['amount_currency'], $is_show);
                break;

            case self::OPERATION_WAREHOUSE_END_CODE: // 标记入库完结
                if (!$request_data['money_id']) {
                    throw new \Exception('money_id不可为空~');
                }
                // 根据tb_pur_ship_goods.ship_id获取对应的商品sku和information_id含税价格
                $res = $this->getShipGoodsAmountSum($request_data['money_id'], $request_data['money_type'], $is_show);
                break;

            case self::OPERATION_CLAIM_DEL_CODE: // 采购退款认领记录删除
                if (!$request_data['money_id']) {
                    throw new \Exception('money_id不可为空~');
                }
                // 根据tb_fin_claim.id 获取claim_amount
                $re = $this->repository->getFinClaim($request_data['money_id']);
                $res = $this->retOperationAmountFormat($re['summary_amount'], $request_data['money_type'], $re['amount_currency'], $is_show);
                break;

            case self::OPERATION_CLAIM_EDIT_CODE: // 编辑采购退款认领(抵扣金，根据tb_fin_claim.id获取 claim_amount)
                if (!$request_data['money_id']) {
                    throw new \Exception('money_id不可为空~');
                }
                // 根据tb_fin_claim.id 获取claim_amount
                $re = $this->repository->getFinClaim($request_data['money_id']);
                $res = $this->retOperationAmountFormat($re['summary_amount'], $request_data['money_type'], $re['amount_currency'], $is_show);
                break;

            case self::OPERATION_WAREHOUSE_CODE:  // 采购入库确认（残次品）      本次入库残次品数量*采购价
                if (!$request_data['detail']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
               
                $amount = '0.00';  // 根据goods_id获取到采购价，然后乘以数量，汇总求和
                foreach ($request_data['detail'] as $key => $value) {
                    $priceInfo = $this->repository->getPriceByGoodsID($value['goods_id']);
                    $amount = bcadd($amount, bcmul(bcadd($priceInfo['unit_price'], $priceInfo['unit_expense'], 8), $value['number'], 8), 2);
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo['amount_currency'], $is_show);
                break;

            case self::OPERATION_CONVERSION_CODE: // 正品转残次（同步采购单
                if (!$request_data['detail']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                $amount = [];
                $currency = [];
                //本次转换成残次品数量*采购价
                foreach ($request_data['detail'] as $key => $value) {
                    $priceInfo = '';
                    $priceInfo = $this->repository->getPriceByStreamID($value['stream_id']);
                    $relevance_id_info = $this->repository->getRelevanceIdByStreamID($value['stream_id']);
                    $relevance_id = $relevance_id_info['relevance_id'];
                    if ($relevance_id) {
                        $clauseWhere['purchase_id'] = array('eq', $relevance_id);
                        $clause_info_check = (new Model())->table('tb_pur_clause')->field('id')->where($clauseWhere)->select(); // 获取条款信息（有新的适用条款才需要生成应付/抵扣记录）
                        if (count($clause_info_check)) {
                            $amount[$relevance_id] = bcadd($amount[$relevance_id], bcmul(bcadd($priceInfo['po_cost'], $priceInfo['unit_price_origin'], 8), $value['number'], 8), 2);
                            $currency[$relevance_id] = $priceInfo['currency_id'];
                        }
                        unset($clauseWhere);
                    }
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $currency, $is_show);
                break;


            case self::OPERATION_CONVERSION_AGAIN_CODE: // 残传正（同步采购单）
                if (!$request_data['detail']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                $amount = [];
                $currency = [];
                //本次转换成残次品数量*采购价
                foreach ($request_data['detail'] as $key => $value) {
                    $priceInfo = '';
                    $priceInfo = $this->repository->getPriceByStreamID($value['stream_id']);
                    $relevance_id_info = $this->repository->getRelevanceIdByStreamID($value['stream_id']);
                    $relevance_id = $relevance_id_info['relevance_id'];
                    if ($relevance_id) {
                        $clauseWhere['purchase_id'] = array('eq', $relevance_id);
                        $clause_info_check = (new Model())->table('tb_pur_clause')->field('id')->where($clauseWhere)->select(); // 获取条款信息（有新的适用条款才需要生成应付/抵扣记录）
                        if (count($clause_info_check)) {
                            $amount[$relevance_id] = bcadd($amount[$relevance_id], bcmul(bcadd($priceInfo['po_cost'], $priceInfo['unit_price_origin'], 8), $value['number'], 8), 2);
                            $currency[$relevance_id] = $priceInfo['currency_id'];
                        }
                        unset($clauseWhere);
                    }
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $currency, $is_show);
                break;

            case self::OPERATION_SHIP_END_CODE: // 标记发货完结
                if (!$request_data['relevance_id']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                $amount = '0.00';
                $priceInfo = $this->repository->getPriceByRelevanceID($request_data['relevance_id']); // 根据relevance_id获取到采购价，然后乘以数量，汇总求和
                // 累计生成总的应付费用
                foreach ($priceInfo as $key => $value) {
                    //标记的发货数量 * （含增值税采购单价 + PO内费用单价）| 标记的发货数量 = 商品数量 + 退货数量 - 已发货数量
                    $ship_end_number = bcsub(bcadd($value['goods_number'], $value['return_number'], 8), $value['shipped_number'], 8);
                    $amount = bcadd($amount, bcmul(bcadd($value['unit_price'], $value['unit_expense'], 8), $ship_end_number, 8), 2);
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo[0]['amount_currency'], $is_show);
                break;

            case self::OPERATION_SHIP_END_REVOKE_CODE: // 撤回发货完结
                if (!$request_data['relevance_id']) {
                    throw new \Exception('商品详情信息不可为空~');
                }
                $amount = '0.00';
                $priceInfo = $this->repository->getPriceByRelevanceID($request_data['relevance_id']); // 根据relevance_id获取到采购价，然后乘以数量，汇总求和
                // 累计生成总的抵扣费用
                foreach ($priceInfo as $key => $value) {
                    //撤回标记的发货数量 * （含增值税采购单价 + PO内费用单价）
                    //$ship_end_number = bcsub(bcadd($value['goods_number'], $value['return_number'], 8), $value['shipped_number'], 8);
                    $ship_end_number = $value['ship_end_number'];
                    $amount = bcadd($amount, bcmul(bcadd($value['unit_price'], $value['unit_expense'], 8), $ship_end_number, 8), 2);
                }
                $res = $this->retOperationAmountFormat($amount, $request_data['money_type'], $priceInfo[0]['amount_currency'], $is_show);
                break;

            default:
                # code...
                    break; 
        }
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        return $res;
    }

    /****************start:采购抵扣金相关***
    *****************/
    const TURNOVER_USE_DEDUCTION_AMOUNT = 1;//使用抵扣金
    const TURNOVER_AS_DEDUCTION_AMOUNT = 2;//算作抵扣金

    // 根据采购单号获取供应商ID
    private function getSupplierNewIdByOrderNo($order_no, $model)
    {
        if (!$order_no) {
            return false;
        }
        $where = [];
        $where['procurement_number'] = $order_no;
        return $model->table('tb_pur_order_detail')->where($where)->getField('supplier_new_id');
    }

    // 生成赔偿返利抵扣金
    public function addDeductionAmountCompensation($data, $model) 
    {
        $supplier_id = null;
        $this->validateAddDeductionAmount($data);
        $supplier_id = $data['supplier_new_id'];
        if (!$supplier_id) { // 根据采购单id获取对应的供应商id
            $supplier_id = $this->getSupplierNewIdByOrderNo($data['order_no'], $model);
            if (!$supplier_id) {
                $log_msg['last_sql'] = M()->_sql();
                $log_msg['request']['data'] = $data;
                Logs(json_encode($log_msg), __FUNCTION__.'----debug11', 'tr');
                throw new \Exception(L('没有找到该供应商名称对应的ID')); 
            }
        }
        $data['supplier_id'] = $supplier_id;
        unset($data['sp_charter_no']);
        unset($data['supplier_new_id']);
        //更新抵扣金账户金额
        $deduction_id = $this->saveDeductionAmountAccountCompensation($data, $model);
        if (!$deduction_id) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '返利赔偿抵扣金账户金额更新失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug12', 'tr');
            throw new \Exception(L('抵扣金账户金额更新')); 
        }

        $voucher = $data['deduction_voucher'];
        if (is_array($voucher) && !empty($voucher)) {
            $data['deduction_voucher'] = json_encode($voucher,JSON_UNESCAPED_UNICODE);
        }
        $data['deduction_id'] = $deduction_id;
        $data['created_by'] = $data['updated_by'] = $this->user_name;
        if (!$model->table('tb_pur_deduction_compensation_detail')->create($data)) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '创建返利赔偿抵扣金详情数据失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug13', 'tr');
            throw new \Exception(L('创建返利赔偿抵扣金详情数据失败'));
        }
        if (!$deduction_detail_id = $model->table('tb_pur_deduction_compensation_detail')->add()) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '算作返利赔偿抵扣金失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug14', 'tr');
            Logs(json_encode($data), __FUNCTION__.' fail', __CLASS__);
            throw new \Exception(L('算作返利赔偿抵扣金失败'));
        }
        return $deduction_detail_id;
    }

    private function saveDeductionAmountAccountCompensation($data, $model) {
        //$this->checkSupplierInfo($data['supplier_id'], $data['supplier_name_cn']);
        $where = [
            'our_company_cd' => $data['our_company_cd'],
            'deduction_currency_cd' => $data['deduction_currency_cd'],
            'supplier_id' => $data['supplier_id'],
            'order_no' => $data['order_no']
        ];

        $deduction = $model->table('tb_pur_deduction_compensation')->where($where)->find();
        if ($deduction) {
            $deduction_id = $deduction['id'];
            if ($data['turnover_type'] == self::TURNOVER_AS_DEDUCTION_AMOUNT) {
                $save_data = [
                    'over_deduction_amount' => $deduction['over_deduction_amount'] + $data['deduction_amount'],
                    'unused_deduction_amount' => $deduction['unused_deduction_amount'] + $data['deduction_amount'],
                    'updated_by' => $this->user_name
                ];
            } else if ($data['turnover_type'] == self::TURNOVER_USE_DEDUCTION_AMOUNT) {
                if ($deduction['over_deduction_amount'] < $data['deduction_amount']) {
                    throw new \Exception(L('供应商赔偿及返利账户余额小于当前抵扣金额'));
                }
                $save_data = [
                    'over_deduction_amount' => $deduction['over_deduction_amount'] - $data['deduction_amount'],
                    'used_deduction_amount' => $deduction['used_deduction_amount'] + $data['deduction_amount'],
                    'updated_by' => $this->user_name
                ];
            } else {
                throw new \Exception(L('未知进出账类型'));
            }
            $res = $model->table('tb_pur_deduction_compensation')->where($where)->save($save_data);
            if (!$res) {
                throw new \Exception(L('更新供应商返利赔偿金额信息失败'));
            }
        } else {
            $add_data = $data;
            $add_data['over_deduction_amount'] = $data['deduction_amount'];
            if ($data['turnover_type'] == self::TURNOVER_AS_DEDUCTION_AMOUNT) {
                $add_data['unused_deduction_amount'] = $data['deduction_amount'];
                $add_data['over_deduction_amount'] = $data['deduction_amount'];
            }/* else if ($data['turnover_type'] == self::TURNOVER_USE_DEDUCTION_AMOUNT) {
                $add_data['used_deduction_amount'] = $data['deduction_amount'];
                $add_data['over_deduction_amount'] = 0 - $data['deduction_amount']; // 允许扣减为负数
            }*/
            $add_data['created_by'] = $add_data['updated_by'] = $this->user_name;
            if (!$model->table('tb_pur_deduction_compensation')->create($add_data)) {
                throw new \Exception(L('创建供应商赔偿返利抵扣金数据失败'));
            }
            $deduction_id = $model->table('tb_pur_deduction_compensation')->add();
            if (false === $deduction_id) {
                ELog::add('创建返利及赔偿抵扣金供应商账户失败'.json_encode($add_data).M()->getDbError(),ELog::ERR);
                throw new \Exception(L('创建返利及赔偿抵扣金供应商账户失败'));
            }
        }
        return $deduction_id;
    }

    /**算作抵扣金
     * @param $data
     * @param $model
     * @param $allow_less_than_zero 是否允许扣减抵扣金为零，默认不允许
     * @return mixed
     * @throws Exception
     */
    public function addDeductionAmount($data, $model, $allow_less_than_zero = false) {
        // 鉴于目前有部分供应商没有营业执照号且不在crm_sp_supplier表里，无法根据其获取对应的supplier_id，从而无法生成抵扣金和更新抵扣金账户
        // 新增补充该情况下的更新抵扣金账户逻辑，根据供应商中文名称来更新，没有该供应商名称则新增（crm_sp_supplier表新增记录）
        // 2020-02-27 弃用根据营业执照号来确定供应商id，改为用供应商中文名称来确定 #9936
        $supplier_id = null;
        $this->validateAddDeductionAmount($data, $allow_less_than_zero);
        $supplier_id = $data['supplier_new_id'];
        /*if (!$supplier_id) { // 历史数据处理 // 屏蔽，因为名称不是唯一，且可以修改
            $supplier_id = M('crm_sp_supplier', 'tb_')->where(['SP_NAME' => $data['supplier_name_cn']])->getField('ID');
        }*/
        /*if (!empty($data['sp_charter_no'])) {
            $supp_map['SP_CHARTER_NO'] = $data['sp_charter_no'];
            $supp_map['DATA_MARKING']  = '0';  // 只有供应商这种类型，才有抵扣金（区别于B2B客户）
            $supplier_id               = M('crm_sp_supplier', 'tb_')->where($supp_map)->getField('ID');
        }*/
        if (!$supplier_id) { // 新增供应商
            // 根据采购单id获取对应的供应商id
            $supplier_id = $this->getSupplierNewIdByOrderNo($data['order_no'], $model);
            if (!$supplier_id) {
                $log_msg['last_sql'] = M()->_sql();
                $log_msg['request']['data'] = $data;
                Logs(json_encode($log_msg), __FUNCTION__.'----debug1', 'tr');
                throw new \Exception(L('没有找到该供应商名称对应的ID')); 
            }
             
                /*$addSupplierData = [];
                $addSupplierData['SP_NAME'] = $data['supplier_name_cn'];
                $addSupplierData['COPANY_TYPE_CD'] = 'N001190800';
                $addSupplierData['SP_CHARTER_NO_TYPE'] = '1';
                $addSupplierData['SP_STATUS'] = '1';
                $addSupplierData['DEL_FLAG'] = '1';
                $supplier_id = M('crm_sp_supplier', 'tb_')->add($addSupplierData);
                if (!$supplier_id) {
                    throw new \Exception(L('供应商新增失败了'));  
                }*/
        }
        $data['supplier_id'] = $supplier_id;
        unset($data['sp_charter_no']);
        unset($data['supplier_new_id']);

        //更新抵扣金账户金额
        $deduction_id = $this->saveDeductionAmountAccount($data, $model, $allow_less_than_zero);
        if (!$deduction_id) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '抵扣金账户金额更新失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug2', 'tr');
            throw new \Exception(L('抵扣金账户金额更新')); 
        }

        $voucher = $data['deduction_voucher'];
        if (is_array($voucher) && !empty($voucher)) {
            $data['deduction_voucher'] = json_encode($voucher,JSON_UNESCAPED_UNICODE);
        }
        $data['deduction_id'] = $deduction_id;
        $data['order_type_cd'] = 'N001950100';
        $data['created_by'] = $data['updated_by'] = $this->user_name;
        if ($data['remark'] === '历史异常抵扣金处理') {
            $data['created_by'] = '';
            $data['updated_by'] = '';
        }
        if (!$model->table('tb_pur_deduction_detail')->create($data)) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '创建抵扣金详情数据失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug3', 'tr');
            throw new \Exception(L('创建抵扣金详情数据失败'));
        }
        if (!$deduction_detail_id = $model->table('tb_pur_deduction_detail')->add()) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['data'] = $data;
            $log_msg['result']['msg'] = '算作抵扣金失败';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug4', 'tr');
            Logs(json_encode($data), __FUNCTION__.' fail', __CLASS__);
            throw new \Exception(L('算作抵扣金失败'));
        }
        return $deduction_detail_id;
    }

    /**抵扣金账户金额更新
     * @param $data
     * @param $model
     * @return mixed
     * @throws Exception
     */
    private function saveDeductionAmountAccount($data, $model, $allow_less_than_zero = false) {
        // $this->checkSupplierInfo($data['supplier_id'], $data['supplier_name_cn']);
        $where = [
            'our_company_cd' => $data['our_company_cd'],
            'deduction_currency_cd' => $data['deduction_currency_cd'],
            'supplier_id' => $data['supplier_id'],
        ];

        $deduction = $model->table('tb_pur_deduction')->where($where)->find();
        if ($deduction) {
            $deduction_id = $deduction['id'];
            if ($data['turnover_type'] == self::TURNOVER_AS_DEDUCTION_AMOUNT) {
                $save_data = [
                    'over_deduction_amount' => $deduction['over_deduction_amount'] + $data['deduction_amount'],
                    'unused_deduction_amount' => $deduction['unused_deduction_amount'] + $data['deduction_amount'],
                    'updated_by' => $this->user_name
                ];
            } else if ($data['turnover_type'] == self::TURNOVER_USE_DEDUCTION_AMOUNT) {
                if ($deduction['over_deduction_amount'] < $data['deduction_amount'] && !$allow_less_than_zero) {
                    throw new \Exception(L('供应商账户余额小于当前抵扣金额'));
                }
                $save_data = [
                    'over_deduction_amount' => $deduction['over_deduction_amount'] - $data['deduction_amount'],
                    'used_deduction_amount' => $deduction['used_deduction_amount'] + $data['deduction_amount'],
                    'updated_by' => $this->user_name
                ];
            } else {
                throw new \Exception(L('未知进出账类型'));
            }
            $res = $model->table('tb_pur_deduction')->where($where)->save($save_data);
            if (!$res) {
                throw new \Exception(L('更新供应商金额信息失败'));
            }
        } else {
            $add_data = $data;
            $add_data['over_deduction_amount'] = $data['deduction_amount'];
            if ($data['turnover_type'] == self::TURNOVER_AS_DEDUCTION_AMOUNT) {
                $add_data['unused_deduction_amount'] = $data['deduction_amount'];
                $add_data['over_deduction_amount'] = $data['deduction_amount'];
            } else if ($data['turnover_type'] == self::TURNOVER_USE_DEDUCTION_AMOUNT) {
                $add_data['used_deduction_amount'] = $data['deduction_amount'];
                $add_data['over_deduction_amount'] = 0 - $data['deduction_amount']; // 允许扣减为负数
            }
            $add_data['created_by'] = $add_data['updated_by'] = $this->user_name;
            if (!$model->table('tb_pur_deduction')->create($add_data)) {
                throw new \Exception(L('创建抵扣金数据失败'));
            }
            if (!$deduction_id = $model->table('tb_pur_deduction')->add()) {
                Logs(json_encode($data), __FUNCTION__.' fail', __CLASS__);
                throw new \Exception(L('创建抵扣金供应商账户失败'));
            }
        }
        return $deduction_id;
    }

    private function checkSupplierInfo($supplier_id, $supplier_name_cn)
    {
        $info = M('sp_supplier', 'tb_crm_')->find($supplier_id);
        if (trim($supplier_name_cn) != trim($info['SP_NAME'])) {
            $log_msg['last_sql'] = M()->_sql();
            $log_msg['request']['supplier_id'] = $supplier_id;
            $log_msg['request']['supplier_name_cn'] = $supplier_name_cn;
            $log_msg['request']['info'] = $info;
            $log_msg['result']['msg'] = '供应商id与名称不对应';
            Logs(json_encode($log_msg), __FUNCTION__.'----debug5', 'tr');
            @SentinelModel::addAbnormal('生成供应商抵扣金异常', 'supplier_id:'.$supplier_id.'&&SP_NAME:'.$info['SP_NAME'].'&&supplier_name_cn:'.$supplier_name_cn, [$info],'pur_notice');
            throw new \Exception(L('供应商id与名称不对应'));
        }
    }

    private function validateAddDeductionAmount($data, $allow_less_than_zero = false) {
        $rules = [
            //'sp_charter_no' => 'required',
            'our_company_cd' => 'required|string|size:10',
            'deduction_currency_cd' => 'required|string|size:10',
            'deduction_type_cd' => 'sometimes|required|string|size:10',
            'deduction_amount' => 'required|numeric',
            'deduction_voucher' => 'required',
            'order_no' => 'required',
            'turnover_type' => 'required|numeric',
        ];

        $attributes = [
            //'sp_charter_no' => '供应商营业执照',
            'our_company_cd' => '我方公司',
            'deduction_currency_cd' => '抵扣币种',
            'deduction_type_cd' => '抵扣类型',
            'deduction_amount' => '抵扣金额',
            'deduction_voucher' => '凭证',
            'order_no' => '采购单号',
            'turnover_type' => '进出账类型',
        ];
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
            $message = ValidatorModel::getMessage();
            if ($message && strlen($message) > 0) {
                $error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
                foreach ($error_message as $value) {
                    throw new Exception(L($value[0]));
                }
            }
        }

        if ($data['deduction_amount'] <= 0 && !$allow_less_than_zero) {
            throw new \Exception(L('抵扣金额不能小于0'));
        }
    }

    public function changeSupplierName($res = [])
    {
        if (!$res) {
            return $res;
        }
        // 根据供应商id，取得供应商相关信息
        $supplierModel = M('crm_sp_supplier', 'tb_');
        foreach ($res as $key => $value) {
            $map = [];
            $re = [];
            if ($value['supplier_id']) {
                $map['ID'] = $value['supplier_id'];
                $re = $supplierModel->field('SP_NAME, SP_NAME_EN')->where($map)->find();
                $res[$key]['supplier_name_cn'] = $re['SP_NAME'];
                $res[$key]['supplier_name_en'] = $re['SP_NAME_EN'];
            }
        }
        return $res;
    }
    /**
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function getDeductionList($request_data, $is_excel = false) {
        $search_map = [
            'supplier_id' => 'supplier_id',
            'our_company_cd' => 'our_company_cd',
            'deduction_currency_cd' => 'deduction_currency_cd',
        ];
        $search_type = ['supplier_id', 'our_company_cd', 'deduction_currency_cd'];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        list($res_db, $pages) = $this->PurRepository->getDeductionList($where, $limit, $is_excel);
        $res_db = CodeModel::autoCodeTwoVal($res_db, ['deduction_currency_cd']);
        $res_db = $this->changeSupplierName($res_db);
        return [
            'data' => $res_db,
            'pages' => $pages
        ];
    }

    public function getDeductionCompensationList($request_data, $is_excel = false) {
        $search_map = [
            'supplier_id' => 'supplier_id',
            'our_company_cd' => 'our_company_cd',
            'deduction_currency_cd' => 'deduction_currency_cd',
            'order_no' => 'order_no'
        ];
        $search_type = ['supplier_id', 'our_company_cd', 'deduction_currency_cd'];
        $is_excel = $is_excel ? $is_excel : false;
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        if ($request_data['others']['need_greater_zero']) {
            $where['over_deduction_amount'] = array('gt' , '0');
        }
        list($res_db, $pages) = $this->PurRepository->getDeductionCompensationList($where, $limit, $is_excel);
        $res_db = CodeModel::autoCodeTwoVal($res_db, ['deduction_currency_cd', 'our_company_cd']);
        $res_db = $this->changeSupplierName($res_db);
        return [
            'data' => $res_db,
            'pages' => $pages
        ];
    }

    /**
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function getDeductionDetail($request_data, $is_excel = false) {
        if ($request_data['search']['deduction_id']) {
            $search_map = [
                'deduction_id' => 'deduction_id',
            ];
        } else {
            $search_map = [
                'order_no' => 'order_no',
            ];
        }
        $search_type = ['deduction_id', 'order_no'];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        $where['is_revoke'] = ['neq', 1];
        list($res_db, $pages) = $this->PurRepository->getDeductionDetail($where, $limit, $is_excel);
        if (count($res_db) !== 0) {
            $deduction_count = $res_db['deduction_count'];
            unset($res_db['deduction_count']);
            $res_db = D('Scm/PurOperation')->getAssemOperationInfo($res_db, '2');
            $res_db['deduction_count'] = $deduction_count;
            $res_db = CodeModel::autoCodeTwoVal($res_db, ['deduction_type_cd']);

        }

        return [
            'data' => $res_db,
            'pages' => $pages
        ];
    }

    public function getDeductionCompensationDetail($request_data, $is_excel = false) {
        if ($request_data['search']['deduction_id']) {
            $search_map = [
                'deduction_id' => 'deduction_id',
            ];
        }
        $search_type = ['deduction_id'];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        $where['is_revoke'] = ['neq', 1];
        list($res_db, $pages) = $this->PurRepository->getDeductionCompensationDetail($where, $limit, $is_excel);
        if (count($res_db['list']) !== 0) {
            $res_db['list'] = CodeModel::autoCodeTwoVal($res_db['list'], ['deduction_type_cd']);
        }
        $res_db['list'] = $this->getVoucher($res_db['list']);
        $res_db['list'] = $this->getUseDeductionStr($res_db['list']);
        return [
            'data' => $res_db,
            'pages' => $pages
        ];
    }

    public function getUseDeductionStr($res = [])
    {
        foreach ($res as $key => &$vv) {
            if ($vv['turnover_type'] == '1') {
                $vv['deduction_type_cd_val'] = '应付单使用'; 
            }
        }
        return $res;
    }

    public function getVoucher($res = [])
    {
        foreach ($res as $key => &$value) {
            $deduction_voucher_name_arr = [];
            $deduction_voucher_name_arr = json_decode($value['deduction_voucher'], true);
            $value['deduction_voucher_name_str'] = '';
            foreach ($deduction_voucher_name_arr as $k => $v) {
                if ($v['name']) {
                    $value['deduction_voucher_name_str'] .= $v['name'] . ',';
                }
            }
            if ($value['deduction_voucher_name_str']) {
                $value['deduction_voucher_name_str'] = rtrim($value['deduction_voucher_name_str'], ',');
            }
        }
        return $res;
    }

    /**返利赔偿金算作/使用抵扣金撤回
     * @param $deduction_detail_id
     * @param $model
     * @throws Exception
     */
    public function cancelDeductionAmountCompensation($deduction_detail_id, $model) {
        if (empty($model)) {
            $model = $this->model;
        }
        if (!$deduction_detail_id) {
            throw new \Exception(L('未找到该条抵扣金详情记录ID'));
        }
        $deduction_detail = $model->table('tb_pur_deduction_compensation_detail')->find($deduction_detail_id);
        if (empty($deduction_detail)) {
            throw new \Exception(L('未找到该条抵扣金详情记录'));
        }
        $where = [
            'id' => $deduction_detail['deduction_id']
        ];
        $deduction_amount = $deduction_detail['deduction_amount'];

        switch ($deduction_detail['turnover_type']) {
            //抵扣金使用撤回
            case self::TURNOVER_USE_DEDUCTION_AMOUNT:
                $inc_res = $model->table('tb_pur_deduction_compensation')->where($where)->setInc('over_deduction_amount', $deduction_amount);
                $dec_res = $model->table('tb_pur_deduction_compensation')->where($where)->setDec('used_deduction_amount', $deduction_amount);
                if (false === $inc_res || false === $dec_res) {
                    Logs(json_encode($where).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                    throw new \Exception(L('采购应付抵扣金撤回失败'));
                }
                break;
            //抵扣金算作撤回
            case self::TURNOVER_AS_DEDUCTION_AMOUNT:
                $deduction = $model->table('tb_pur_deduction_compensation')->where($where)->find();
                if (($deduction['over_deduction_amount'] < $deduction_amount || $deduction['unused_deduction_amount'] < $deduction_amount)) {
                    throw new \Exception(L('供应商返利及赔偿金账户余额小于当前取消金额，取消失败'));
                }
                $dec_res1 = $model->table('tb_pur_deduction_compensation')->where($where)->setDec('over_deduction_amount', $deduction_amount);
                $dec_res2 = $model->table('tb_pur_deduction_compensation')->where($where)->setDec('unused_deduction_amount', $deduction_amount);
                if (false === $dec_res1 || false === $dec_res2) {
                    Logs(json_encode($where).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                    throw new \Exception(L('采购应付抵扣金取消失败'));
                }

                // 若为余额转账取消，余额账户需要增加，操作记录删除，余额明细删除
                if ($deduction_detail['deduction_type_cd'] === 'N002660202') {
                    // 根据返利赔偿金明细账户id获取余额账户明细id，再根据此获取余额账户id
                    list($ded_id, $ded_detail_id) = $this->getAmountAccount($deduction_detail_id, $model);
                    if (false === $ded_id || false === $ded_detail_id) {
                        Logs(json_encode($deduction_detail_id).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                        throw new \Exception(L('未找到该余额转账对应的id'));
                    }
                    $dedWhere['id'] = $ded_id;
                    $ded_res1 = $model->table('tb_pur_deduction')->where($dedWhere)->setInc('over_deduction_amount', $deduction_amount);
                    $ded_res2 = $model->table('tb_pur_deduction')->where($dedWhere)->setDec('used_deduction_amount', $deduction_amount);
                    if (false === $ded_res1 || false === $ded_res2) {
                        Logs(json_encode($dedWhere).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                        throw new \Exception(L('采购余额取消余额部分数据更新失败'));
                    }
                    $deduDetailRes = $model->table('tb_pur_deduction_detail')
                        ->where(['id' => $ded_detail_id])
                        ->save([
                            'is_revoke' => 1,
                            'deleted_by' => $this->user_name,
                            'deleted_at' => date('Y-m-d H:i:s'),
                        ]);
                    if (false === $deduDetailRes) {
                        throw new \Exception(L('取消失败'));
                    }
                    // 关联关系删除
                    $dedRelationRes = $model->table('tb_pur_deduction_compensation_relationship')
                        ->where(['ded_id' => $ded_detail_id, 'ded_com_id' => $deduction_detail_id])
                        ->save([
                            'deleted_by' => $this->user_name,
                            'deleted_at' => date('Y-m-d H:i:s'),
                        ]);
                    if (false === $dedRelationRes) {
                        Logs($ded_detail_id.'-'.$deduction_detail_id, __FUNCTION__.' fail', __CLASS__);
                        throw new \Exception(L('取消失败,关联关系删除失败'));
                    }
                    // 操作记录删除
                    $operRes = $model->table('tb_pur_operation')
                        ->where(['main_id' => $ded_detail_id, 'action_type_cd' => 'N002870023'])
                        ->delete();
                    if (false === $operRes) {
                        Logs($ded_detail_id, __FUNCTION__.' fail', __CLASS__);
                        throw new \Exception(L('取消失败,触发操作记录删除失败'));
                    }
                }
                break;
            default:
                throw new \Exception(L('未知进出账类型'));
                break;
        }

        $res = $model->table('tb_pur_deduction_compensation_detail')
            ->where(['id' => $deduction_detail_id])
            ->save([
                'is_revoke' => 1,
                'deleted_by' => $this->user_name,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
        if (false === $res) {
            throw new \Exception(L('取消失败'));
        }
    }


    public function getAmountAccount($deduction_detail_id = '', $model)
    {
        $resReturn = [false, false];
        if (empty($model)) {
            $model = $this->model;
        }
        $where['deleted_by'] = ['EXP', 'IS NULL'];
        $where['ded_com_id'] = $deduction_detail_id;
        $ded_detail_id = $model->table('tb_pur_deduction_compensation_relationship')->where($where)->getField('ded_id');
        if (!$ded_detail_id) {
            Logs(json_encode($where), __FUNCTION__.' debug1', __CLASS__);
            return $resReturn;
        }
        $whereMap['id'] = $ded_detail_id;
        $deduction_id = $model->table('tb_pur_deduction_detail')->where($whereMap)->getField('deduction_id');
        if (!$deduction_id) {
            Logs(json_encode($whereMap), __FUNCTION__.' debug2', __CLASS__);
            return $resReturn;   
        }
        return [$deduction_id, $ded_detail_id];
    }

    /**算作/使用抵扣金撤回
     * @param $deduction_detail_id
     * @param $model
     * @throws Exception
     */
    public function cancelDeductionAmount($deduction_detail_id, $model, $allow_less_than_zero = false) {
        if (empty($model)) {
            $model = $this->model;
        }
        if (!$deduction_detail_id) {
           throw new \Exception(L('未找到该条抵扣金详情记录ID')); 
        }
        $deduction_detail = $model->table('tb_pur_deduction_detail')->find($deduction_detail_id);
        if (empty($deduction_detail)) {
            throw new \Exception(L('未找到该条抵扣金详情记录'));
        }
        $where = [
            'id' => $deduction_detail['deduction_id']
        ];
        $deduction_amount = $deduction_detail['deduction_amount'];
        switch ($deduction_detail['turnover_type']) {
            //抵扣金使用撤回
            case self::TURNOVER_USE_DEDUCTION_AMOUNT:
                $inc_res = $model->table('tb_pur_deduction')->where($where)->setInc('over_deduction_amount', $deduction_amount);
                $dec_res = $model->table('tb_pur_deduction')->where($where)->setDec('used_deduction_amount', $deduction_amount);
                if (false === $inc_res || false === $dec_res) {
                    Logs(json_encode($where).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                    throw new \Exception(L('采购应付抵扣金撤回失败'));
                }
                break;
            //抵扣金算作撤回
            case self::TURNOVER_AS_DEDUCTION_AMOUNT:
                $deduction = $model->table('tb_pur_deduction')->where($where)->find();
                if (!$allow_less_than_zero && ($deduction['over_deduction_amount'] < $deduction_amount || $deduction['unused_deduction_amount'] < $deduction_amount)) {
                    throw new \Exception(L('供应商账户余额小于当前取消金额，取消失败'));
                }
                $dec_res1 = $model->table('tb_pur_deduction')->where($where)->setDec('over_deduction_amount', $deduction_amount);
                $dec_res2 = $model->table('tb_pur_deduction')->where($where)->setDec('unused_deduction_amount', $deduction_amount);
                if (false === $dec_res1 || false === $dec_res2) {
                    Logs(json_encode($where).'-'.$deduction_amount, __FUNCTION__.' fail', __CLASS__);
                    throw new \Exception(L('采购应付抵扣金取消失败'));
                }
                break;
            default:
                throw new \Exception(L('未知进出账类型'));
                break;
        }

        $res = $model->table('tb_pur_deduction_detail')
            ->where(['id' => $deduction_detail_id])
            ->save([
                'is_revoke' => 1,
                'deleted_by' => $this->user_name,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$res) {
            throw new \Exception(L('取消失败'));
        }
    }
    /****************end:采购抵扣金相关********************/

    /**
     * 获取采购已发货商品、库存信息
     * @param $ship_id 发货id
     * @return mixed
     */
    public function getShipInfo($ship_id)
    {
        $locationService = new LocationService();
        $model  = new TbPurShipModel();
        $detail = $model->relation(true)->where(['id'=>$ship_id])->find();
        foreach ($detail['goods'] as $k => $v) {
            $sku_info = D('Pms/PmsProductSku')
                ->alias('t')
                ->field('is_shelf_life,upc_id,upc_more')
                ->join('left join product a on a.spu_id=t.spu_id')
                ->where(['sku_id'=>$v['information']['sku_information']])
                ->find();
            $detail['goods'][$k]['information']['is_shelf_life']    = $sku_info['is_shelf_life'] ? 'Y' : 'N';
            $detail['goods'][$k]['information']['upc_id']           = $sku_info['upc_id'];
            if($sku_info['upc_more']) {
                $upc_more_arr = explode(',', $sku_info['upc_more']);
                array_unshift($upc_more_arr, $sku_info['upc_id']);
                $detail['goods'][$k]['information']['upc_id'] = implode(",<br/>", $upc_more_arr); # 返回br标签 前端显示换行 
            }
            
            $detail['goods'][$k]['information'] = SkuModel::getInfo([$detail['goods'][$k]['information']],'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute'])[0];
            $location_info = M('location_sku','tb_wms_')
                ->alias('t')
                ->join('left join tb_wms_warehouse a on a.id = t.warehouse_id')
                ->where(['t.sku'=>$v['information']['sku_information'],'a.CD'=>$detail['warehouse']['CD']])
                ->find();
            $detail['goods'][$k]['location'] = $location_info['location_code'];
            $detail['goods'][$k]['defective_location_code'] = $location_info['defective_location_code'];

            $parameter = D('Pms/PmsProductSku')->field('sku_length length,sku_height height,sku_weight weight,sku_width width')->where(['sku_id'=>$v['information']['sku_information']])->find();
            foreach ($parameter as $key => $value) {
                $detail['goods'][$k][$key] = $value;
            }
        }
        return $detail;
    }


    /**
     * 获取在途发票金额
     * @param $relevance_ids
     * @return string
     */
    public function getOnWayInvoiceAmount($relevance_ids)
    {
        $relevance_ids = array_unique((array)$relevance_ids);
        $over_paid_amount   = $this->repository->getOverPaidAmount($relevance_ids);
        $all_invoice_amount = (new TbPurInvoiceModel)->getInvoiceTotalAmount($relevance_ids);

        foreach ($over_paid_amount as $key => $item) {
            $result[$key] =  bcsub($item, $all_invoice_amount[$key], 2) ? : 0.00;
        }
        if ($diff = array_diff_key($all_invoice_amount, $over_paid_amount)) {
            foreach ($diff as $key => $item) {
                $result[$key] =  -$item;
            }
        }
        return $result;
    }
}