<?php

class PurOperationModel extends BaseModel
{
    protected $trueTableName = 'tb_pur_operation';

    public $START_TIME = '2019-12-30 14:55:00';//需求9798上线时间

    public $START_TIME_SEC = '2020-02-18 10:30:00'; //9953上线时间

    public $pur_info = [
        'N002870001' => ['name' => '订单创建', 'bill_no' => '', 'formula' => 'PO金额*该期比例'],
        'N002870002' => ['name' => '采购发货确认', 'bill_no' => '发货编号', 'formula' => '{∑本次发货每个SKU[发货数量*（含增值税采购单价+PO内费用单价)]}'],
        'N002870003' => ['name' => '采购入库确认（正品）', 'bill_no' => '入库单号', 'formula' => '{∑本次入库每个SKU[正品数量*（含增值税采购单价+PO内费用单价)]}'],
        'N002870004' => ['name' => '采购入库确认（残次品）', 'bill_no' => '入库单号', 'formula' => '∑本次入库每个SKU[残次品数量*（含增值税采购单价+PO内费用单价)]'],
        'N002870005' => ['name' => '标记入库完结', 'bill_no' => '发货编号', 'formula' => '∑[该发货单每个SKU(发货数量-该发货单入库数量）*（含增值税采购单价+PO内费用单价)]'],
        'N002870006' => ['name' => '撤回入库完结', 'bill_no' => '发货编号', 'formula' => '∑该发货单每个SKU[（发货数量-入库数量）*（（含增值税采购单价+PO内费用单价)]'],
        'N002870007' => ['name' => '退货理货确认', 'bill_no' => '采购退货单号', 'formula' => '每个采购单我方承担赔偿金额'],
        'N002870008' => ['name' => '采购退款认领提交', 'bill_no' => '流水ID', 'formula' => '每个采购单认领的金额'],
        'N002870009' => ['name' => '编辑采购退款认领', 'bill_no' => '流水ID', 'formula' => '新提交的认领金额（应付），原来的认领金额（抵扣金）'],
        'N002870010' => ['name' => '采购退款认领记录删除', 'bill_no' => '流水ID', 'formula' => '每个采购单删除的认领金额'],
        'N002870011' => ['name' => '正品转残次（同步采购单）', 'bill_no' => '转换单号', 'formula' => '每个采购单{∑每个SKU[本次转换成残次品数量*（含增值税采购单价+PO内费用单价)]}'],
        'N002870012' => ['name' => '残次转正品（同步采购单）', 'bill_no' => '转换单号', 'formula' => '每个采购单{∑每个SKU[本次转正品数量*（含增值税采购单价+PO内费用单价)]}'],
        'N002870013' => ['name' => '采购发货撤回', 'bill_no' => '', 'formula' => '∑本次撤回发货每个SKU[发货数量*（含增值税采购单价+PO内费用单价)]'],
        'N002870014' => ['name' => '预付款付款撤回', 'bill_no' => '应付单号', 'formula' => '本次撤回的应付单的(确认后应付金额+使用抵扣金金额）'],
        'N002870015' => ['name' => '预付款付款', 'bill_no' => '应付单号', 'formula' => '本应付单的(确认后应付金额+使用抵扣金金额）'],
        'N002870016' => ['name' => '采购退货出库（正品）', 'bill_no' => '采购退货单号', 'formula' => '每个采购单{∑每个SKU[退货出库正品数量*（含增值税采购单价+PO 内费用单价）]}'],
        'N002870017' => ['name' => '标记发货完结', 'bill_no' => '', 'formula' => '∑每个SKU[标记的发货数量*（含增值税采购单价+PO内费用单价)]'],
        'N002870018' => ['name' => '撤回发货完结', 'bill_no' => '', 'formula' => '∑每个SKU[撤回标记的发货数量*（含增值税采购单价+PO内费用单价)]'],
        'N002870023' => ['name' => '余额转账', 'bill_no' => '', 'formula' => ''],

    ];

    // 适用条款
    public $clause_type = [
        '1' => '预付款-指定时间',
        '2' => '预付款-订单创建后X天',
        '3' => '尾款-每次发货后X天付款',
        '4' => '尾款-每次入库后X天付款',
        '5' => '通用',
        '6' => '同时有预付款&尾款',
        '7' => '无尾款',
        '8' => '（尾款-每次入库后X天付款 or 无尾款）& （预付款≠100% or 无预付款）',
        '9' => '有预付款',
        '10'=> '尾款-每次发货后X天付款&无尾款',
        '11'=> '（尾款-每次入库后X天付款 or 无尾款）& （预付款=100%）', // 即无尾款，且只有一条预付款，且付款比例为100%
        '12'=> '尾款-每次入库后X天付款 & 无尾款'
    ];

    // 根据列表数据，添加预付款和尾款等相关信息
    
    // type 1:应付， 2:抵扣金
    public function getAssemOperationInfo($res = null, $type = '1')
    {
        if (!$res) {
            return [];
        }

        $payment_id_arr = [];
        $payment_id_arr = array_column($res, 'id');
        $purOperationWheres['main_id'] = array('in', $payment_id_arr);
        $purOperationWheres['money_type'] = array('eq', $type);
        $pur_operation_info = 'main_id, action_type_cd, bill_no, clause_type';
        $pur_operation_arr = $this->field($pur_operation_info)->where($purOperationWheres)->select();
        
        $pur_operation_assem_arr = [];
        foreach ($pur_operation_arr as $key => $value) {
            $pur_operation_assem_arr[$value['main_id']] = $value;
        }
        $clause_type_arr = $this->clause_type;
        $pur_info_arr = $this->pur_info;

        foreach ($res as $key => $value) {
            $res[$key]['action_type_cd'] = $pur_operation_assem_arr[$value['id']]['action_type_cd'] ? $pur_operation_assem_arr[$value['id']]['action_type_cd'] : '';
            $res[$key]['action_type_cd_val'] = $res[$key]['action_type_cd'] ? cdVal($res[$key]['action_type_cd']) : '';
            $res[$key]['clause'] = $clause_type_arr[$pur_operation_assem_arr[$value['id']]['clause_type']] ? $clause_type_arr[$pur_operation_assem_arr[$value['id']]['clause_type']] : '';
            $bill = '';
            $res[$key]['bill_no'] = '';
            $bill = $pur_operation_assem_arr[$value['id']]['bill_no'];
            if ($bill) {
                $bill_str = $pur_info_arr[$pur_operation_assem_arr[$value['id']]['action_type_cd']];
                $res[$key]['bill_no_label'] = $bill_str['bill_no'];
                $res[$key]['bill_no'] = $bill;
            }
        }

        return $res;
    }

    // 各种触发事件生成应付/抵扣金记录，并记录触发操作
    /**
    * $addData 需要添加数据（用于生成应付/抵扣金记录）
    * $type 类型 应付：1，抵扣金：2
    * $action_type_cd 触发操作CD, N00287,如N002870001为订单创建',
    * $relevance_id tb_pur_relevance_order.relevance_id 用来查询条款类型tb_pur_clause，触发事件为订单创建时，传quotation_id
    * $bill_no 关联订单号 可以为空
    **/
    public function DealTriggerOperation($addData, $type, $action_type_cd, $relevance_id = '', $bill_no = '')
    {
        $action_type_arr = ['N002870001', 'N002870011', 'N002870012']; //需要根据$relevance_id判断是新流程还是旧流程，如果是旧流程，无需生成对应的抵扣或应付（因为正品转次品，次品转正品涉及多个采购单，另外处理，创建订单情况特殊，没有此id，下面单独处理）
        if (!in_array($action_type_cd, $action_type_arr)) {
          $clauseWhere['purchase_id'] = array('eq', $relevance_id);
          $clause_info_check = (new Model())->table('tb_pur_clause')->field('id')->where($clauseWhere)->select(); // 获取条款信息
          if (count($clause_info_check) === 0) {
            //表明是历史数据，并没有条款规则，无需处理
              return true;
          }
        }
        //需求变更 9448采购结算改版2.16追加无尾款 2019-08-15
        $no_fund_of_end = $this->checkHasFundOfEnd($relevance_id); //是否是无尾款
        //需求9798-无尾款应付、抵扣生成逻辑调整-新增：上线时间后的采购单生效
        $is_after = $this->checkAfterCreateTime($relevance_id); //创建时间是否晚于上线时间
        $is_after_sec = $this->checkAfterCreateTime($relevance_id, $this->START_TIME_SEC); //创建时间是否晚于上线时间

        $payment_m = new TbPurPaymentModel();
        if ($relevance_id && empty($addData['clause_type']) && !$no_fund_of_end) { // 只有适用条款是通用或同时有预付款&尾款，该字段$addData['clause_type']才会有值 追加非无尾款
            if ($action_type_cd === 'N002870001' ) { // 只有订单创建时，才用报价id获取相应条款信息，因为还没创建relevance_id
                $clauseWhere['quotation_id'] = $addData['quotation_id'];
                unset($addData['quotation_id']);
            } else {
                $clauseWhere['purchase_id'] = array('eq', $relevance_id);
            }
            $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type, action_type_cd, days, percent, pre_paid_date')->where($clauseWhere)->select(); // 获取条款信息

            if (count($clauseInfo) === 0) { // 表明是历史数据，并没有条款规则，无需处理
                return true;
            } else {
            $add_res_id = 'init_id';
            //金额类型，1:预付款，2:尾款
            $clause_types = array_column($clauseInfo, 'clause_type');
            foreach ($clauseInfo as $key => $value) { // 条款信息预付款可以有多条记录

                   $clause_type = ''; // 具体类型（1：预付款-指定时间，2：预付款-订单创建后X天，3：尾款-每次发货后X天付款:4：尾款-每次入库后X天付款:5：通用）
                   if ($value['clause_type'] === '1') { // 预付款
                       if ($value['action_type_cd'] === 'N002860001') {
                           $clause_type = '2'; // 订单创建后
                       }
                       if ($value['action_type_cd'] === 'N002860002') {
                           $clause_type = '1'; // 指定日期
                       }
                   } elseif ($value['clause_type'] === '2') { // 尾款
                       if ($value['action_type_cd'] === 'N001390200') { 
                           $clause_type = '3'; // 每次发货后
                       }
                       if ($value['action_type_cd'] === 'N001390400') {
                           $clause_type = '4'; // 每次入库后
                       }
                   }


                   if ($type === '1') { // 应付
                       $addData['relevance_id'] = $relevance_id;
                       switch (strval($action_type_cd)) {
                           case 'N002870001': // 订单创建
                               if ($value['clause_type'] === '1') { // (预付款)订单创建后 只适用于预付款
                                   $addData['amount_payable'] = $addData['amount'] * $value['percent']/100;
                                   if (!empty($value['days'])) { // 表明为订单创建后X天付款
                                       $addData['payable_date'] = date("Y-m-d", strtotime("+{$value['days']} days", time()));
                                   } else {
                                       $addData['payable_date'] = $value['pre_paid_date'];
                                   }
                                   $add_res_id = $this->addInfo($addData, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;

                           case 'N002870002': // 采购发货确认
                               if ($clause_type === '3') { // 采购发货确认 只有当条款为尾款时，才需要生成应付记录
                                   // 获取商品金额
                                   $amount = M('ship_goods','tb_pur_')
                                       ->alias('t')
                                       ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                                       ->where(['ship_id'=>$addData['ship_id']])
                                       ->sum('t.ship_number*(a.unit_price+a.unit_expense)');

                                   $addData['amount'] = $amount;                        
                                   $addData['amountSearch']['action_type_cd'] = $action_type_cd;
                                   $addData['amountSearch']['detail'] = $addData['detail'];
                                   $addData['amountSearch']['relevance_id'] = $relevance_id;
                                   $addData['amount_payable'] = (new PurService())->getOperationAmount($addData['amountSearch']);
                                   unset($addData['amountSearch']);
                                   $addData['payable_date'] = date("Y-m-d", strtotime("+{$value['days']} days", time()));
                                   $add_res_id = $this->addInfo($addData, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;

                           case 'N002870006': // 撤回入库完结
                               if ($clause_type === '3') {
                                   $getAmount['action_type_cd'] = $action_type_cd;
                                   $getAmount['money_id'] = $addData['money_id'];
                                   unset($addData['money_id']);
                                   $param['amount_deduction'] =  (new PurService())->getOperationAmount($getAmount);// 获取抵扣金金额
                                   $param['relevance_id'] = $relevance_id;
                                   $type = '3'; // 由生成应付转为扣减抵扣金 #10144
                                   $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;

                           case 'N002870003': // 采购入库确认（正品）
                               if ($clause_type === '4') {

                                   foreach ($addData['detail'] as $key => $v) {
                                       $addData['detail'][$key]['number'] = $addData['detail'][$key]['warehouse_number'];
                                   }
                                   $amountSearch['detail'] = $addData['detail'];
                                   $amountSearch['action_type_cd'] = $action_type_cd;
                                   $amountSearch['relevance_id'] = $relevance_id;
                                   $addData['amount_payable'] = (new PurService())->getOperationAmount($amountSearch);
                                   $addData['payable_date'] = date("Y-m-d", strtotime("+{$value['days']} days", time()));
                                   unset($addData['detail']);
                                   $clause_type = 8;//（尾款-每次入库后X天付款 or 无尾款）& （预付款!=100% or 无预付款）即尾款-每次入库后X天付款
                                   $add_res_id = $this->addInfo($addData, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;

                           default:
                               # code...
                               break;
                       }
                   }
                   else if ($type === '2') { // 抵扣金

                       switch (strval($action_type_cd)) {
                           case 'N002870005': // 标记入库完结
                               //需求变更：9448 采购结算改版2.16 由原条件 尾款-每次发货号..追加无尾款
                               if ($clause_type === '3') {
                                   $getAmount['action_type_cd'] = $action_type_cd;
                                   $getAmount['money_id'] = $addData['money_id'];
                                   $param['amount_deduction'] =  (new PurService())->getOperationAmount($getAmount);// 获取抵扣金金额
                                   $param['relevance_id'] = $relevance_id;
                                   //$clause_type = 10;
                                   $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;
                           case 'N002870013': // 采购发货撤回
                              if ($clause_type === '3') {
                                  $getAmount['action_type_cd'] = $action_type_cd;
                                  $getAmount['detail'] = $addData['detail'];
                                  $param['amount_deduction'] =  (new PurService())->getOperationAmount($getAmount);// 获取抵扣金金额
                                  $param['relevance_id'] = $relevance_id;
                                  $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no); 
                              }
                              break;
                           case 'N002870004': // 采购入库确认（残次品）
                               if ($clause_type === '3') {
                                   foreach ($addData['detail'] as $key => $value) {
                                       $addData['detail'][$key]['number'] = $addData['detail'][$key]['warehouse_number_broken'];
                                   }
                                   $getAmount['action_type_cd'] = $action_type_cd;
                                   $getAmount['detail'] = $addData['detail'];
                                   $param['amount_deduction'] = (new PurService())->getOperationAmount($getAmount);
                                   $param['relevance_id'] = $relevance_id;
                                   //$clause_type = 10;
                                   $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;
                           /*case 'N002870016': // 采购退货出库（正品）移到通用
                               if ($clause_type === '3') {
                                   $param['action_type_cd'] = $action_type_cd;
                                   $param['amount_deduction'] = (new PurService())->getOperationAmount($addData);
                                   //$clause_type = 10;
                                   $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;*/
                           case 'N002870015': // 预付款付款
                               //创建时间是否晚于上线时间
                               if (!$is_after) {//创建时间晚于上线时间执行新逻辑
                                   break;
                               }
                               if ($value['clause_type'] === '1') { //只适用于预付款
                                   $param['relevance_id'] = $relevance_id;
                                   $clause_type = '6';
                                   $add_res_id = $this->addInfo($param, $type, $action_type_cd, $clause_type, $bill_no);
                               }
                               break;
                       }
                   }
                }
                return $add_res_id ? $add_res_id : ''; 
            }

        } else {   // 适用于通用的条款 或同时有预付款和尾款 追加无尾款

            if ($type === '1') { // 应付
                if (!$addData['payable_date']) {
                  $addData['payable_date'] = date("Y-m-d");
                }
                $addData['relevance_id'] = $relevance_id;
            }

            $clause_type = '5';
            switch (strval($action_type_cd)) {
                case 'N002870016': // 退货出库确认（正品）#10626 
                    if ($type === '2') {
                        $addData['action_type_cd'] = $action_type_cd;
                        $return_goods_res = (new PurService())->getOperationAmount($addData);
                        if ($return_goods_res) {
                          $is_error = '';
                          foreach ($return_goods_res as $key => $value) {
                              $addData['relevance_id'] = $key;
                              $addData['amount_deduction'] = $value;
                              $operation_id = $this->addInfo($addData, $type, $action_type_cd, '5', $bill_no);
                              if (!$operation_id) {
                                $is_error = true;
                              }
                          }
                          if ($is_error) { // 表明其中有生成失败
                            return false;
                          }
                          return $operation_id;
                        } else { // 说明没有符合的金额（可能都是历史采购单）
                          return true;
                        }
                    }
                    break;
                case 'N002870004': // 采购入库确认（残次品） (仅历史数据会用到 #9953)
                    //无尾款
                    if ($no_fund_of_end) {
                        foreach ($addData['detail'] as $key => $value) {
                            $addData['detail'][$key]['number'] = $addData['detail'][$key]['warehouse_number_broken'];
                        }
                        $getAmount['action_type_cd'] = $action_type_cd;
                        $getAmount['detail'] = $addData['detail'];
                        $addData['amount_deduction'] = (new PurService())->getOperationAmount($getAmount);
                        $addData['relevance_id'] = $relevance_id;
                        $clause_type = 10;
                    }
                    break;
                case 'N002870005': // 标记入库完结 (仅历史数据会用到 #9953)
                    //需求变更：9448 采购结算改版2.16 由原条件 尾款-每次发货号..追加无尾款
                    //无尾款
                    if ($no_fund_of_end) {
                        $getAmount['action_type_cd'] = $action_type_cd;
                        $getAmount['money_id'] = $addData['money_id'];
                        $addData['amount_deduction'] =  (new PurService())->getOperationAmount($getAmount);// 获取抵扣金金额
                        $addData['relevance_id'] = $relevance_id;
                        $clause_type = 10;
                    }
                    break;

                case 'N002870006': // 撤回入库完结 (仅历史数据会用到 #9953)
                    //无尾款
                    if ($no_fund_of_end) {
                        $getAmount['action_type_cd'] = $action_type_cd;
                        $getAmount['money_id'] = $addData['money_id'];
                        unset($addData['money_id']);
                        $addData['amount_payable'] = (new PurService())->getOperationAmount($getAmount);
                        $addData['payable_date'] = date("Y-m-d");
                        $clause_type = 10;

                    }
                    break;
                /*case 'N002870016': // 采购退货出库（正品） (仅历史数据会用到 #9953)
                    //无尾款
                    if ($no_fund_of_end) {
                        $addData['action_type_cd'] = $action_type_cd;
                        $addData['amount_deduction'] = (new PurService())->getOperationAmount($addData);
                        $clause_type = 10;
                    }
                    break;*/
                case 'N002870007': // 退货理货确认 
                    break;
                
                case 'N002870008': //采购退款认领提交
                    break;

                case 'N002870009': // 编辑采购退款认领
                    if ($type === '2') {
                        $addData['action_type_cd'] = $action_type_cd;
                        $addData['amount_deduction'] = (new PurService())->getOperationAmount($addData);
                        $addData['relevance_id'] = $relevance_id;
                        unset($addData['money_id']);
                        unset($addData['action_type_cd']);
                    }
                    break;
                case 'N002870012': // 残转正
                    if ($type === '1') {
                        $getAmount['detail'] = $addData['detail'];
                        $getAmount['action_type_cd'] = $action_type_cd;
                        $res = (new PurService())->getOperationAmount($getAmount);
                        unset($addData['detail']);
                        if ($res) {
                          $is_error = '';
                          foreach ($res as $key => $value) {
                              $addData['relevance_id'] = $key;
                              $addData['amount_payable'] = $value;
                              $operation_id = $this->addInfo($addData, $type, $action_type_cd, '5', $bill_no);
                              if (!$operation_id) {
                                $is_error = true;
                              }
                          }
                          if ($is_error) { // 表明其中有生成失败
                            return false;
                          }
                          return $operation_id;
                        } else { // 说明没有符合的金额（可能都是历史采购单）
                          return true;
                        }
                    }
                    break;
                case 'N002870010': // 采购退款认领记录删除
                    $getAmount['action_type_cd'] = $action_type_cd;
                    $getAmount['money_id'] = $addData['money_id'];
                    $addData['relevance_id'] = $relevance_id;
                    $addData['amount_deduction'] = (new PurService())->getOperationAmount($getAmount);
                    break;

                case 'N002870011': // 正品转残次（同步采购单）
                    if ($type === '2') {
                      $getAmount['detail'] = $addData['detail'];
                      $getAmount['action_type_cd'] = $action_type_cd;
                      $res = (new PurService())->getOperationAmount($getAmount);
                      if ($res) {
                        $is_error = '';
                        foreach ($res as $key => $value) {
                          $addData['relevance_id'] = $key;
                          $addData['amount_deduction'] = $value;
                          $operation_id = $this->addInfo($addData, $type, $action_type_cd, '5', $bill_no);
                          if (!$operation_id) {
                            $is_error = true;
                          }
                        }
                        if ($is_error) { // 表明其中有生成失败
                          return false;
                        }
                        return $operation_id;
                      } else { // 说明没有符合的金额（可能都是历史采购单）
                        return true;
                      } 
                    }
                    break;

                case 'N002870014': // 预付款付款撤回（应付状态从"已完成"撤回）
                    $clauseWhere['clause_type'] = 1;
                    $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type, action_type_cd, days, percent, pre_paid_date')->where($clauseWhere)->select(); // 获取条款信息
                    if (count($clauseInfo) === 0) { // 表明并没有预付款条款规则，无需处理
                        return true;
                    }
                    $clause_type = '6';
                    if ($is_after) {//创建时间是否晚于上线时间 晚于则走新逻辑，只要有预付款即可
                        $clause_type = '9';
                    }
                    // #10144 应付改为扣减抵扣金
                    $type = 3;
                    $addData['relevance_id'] = $relevance_id;
                    $addData['amount_deduction'] = $addData['amount_payable'];
                    break;
                case 'N002870015': // 预付款付款
                    $clause_type = '6';
                    if ($is_after) {//创建时间是否晚于上线时间 晚于则走新逻辑
                        $clause_type = '9';
                        $clauseWhere['clause_type'] = 1;
                        $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type, action_type_cd, days, percent, pre_paid_date')->where($clauseWhere)->select(); // 获取条款信息
                        if (count($clauseInfo) === 0) { // 表明并没有预付款条款规则，无需处理
                            return true;
                        }
                    }
                    $addData['relevance_id'] = $relevance_id;
                    break;
                case 'N002870017': // 标记发货完结
                    if ($no_fund_of_end) {
                        $addData['relevance_id'] = $relevance_id;
                        $addData['action_type_cd'] = $action_type_cd;
                        $addData['amount_deduction'] = (new PurService())->getOperationAmount($addData);
                        $clause_type = 7;
                    }
                    break;
                case 'N002870018': // 撤回发货完结
                    if ($no_fund_of_end) {//无尾款
                        //生成应付单
                        $param['relevance_id'] =  $relevance_id;
                        $param['action_type_cd'] = $action_type_cd;
                        $addData['amount_payable'] = (new PurService())->getOperationAmount($param);
                        $addData['payable_date'] = date("Y-m-d");
                        $clause_type = 7;
                    }
                    break;
                case 'N002870003': // 采购入库确认（正品）
                    if (!$is_after) {//创建时间是否晚于上线时间
                        break;
                    }
                    if ($no_fund_of_end) {//无尾款， 个别情况需要走扣减抵扣金，不走应付流程#10144
                        $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type, action_type_cd, days, percent, pre_paid_date')->where($clauseWhere)->select(); // 获取条款信息
                        $clause_count = count($clauseInfo);
                        if (count($clause_count) === 0) { // 表明是历史数据，并没有条款规则，无需处理
                            return true;
                        }
                        if ($clause_count === 1) { #10144 仅有一条预付款且比例为100%时，需要走扣减抵扣金流程
                          foreach ($addData['detail'] as $key => $v) {
                              $addData['detail'][$key]['number'] = $addData['detail'][$key]['warehouse_number'];
                          }
                          $amountSearch['detail'] = $addData['detail'];
                          $amountSearch['relevance_id'] = $relevance_id;
                          $amountSearch['action_type_cd'] = $action_type_cd;
                          $addData['amount_deduction'] = (new PurService())->getOperationAmount($amountSearch);
                          $addData['relevance_id'] = $relevance_id;
                          $addData['action_type_cd'] = $action_type_cd;
                          $clause_type = '11';
                          $type = '3'; // 扣减抵扣金

                        } else { // 保持原有逻辑
                          $clause_type = 8;
                          $is_error = '';
                          foreach ($addData['detail'] as $key => $v) {
                              $addData['detail'][$key]['number'] = $addData['detail'][$key]['warehouse_number'];
                          }
                          $amountSearch['detail'] = $addData['detail'];
                          $amountSearch['action_type_cd'] = $action_type_cd;
                          $amountSearch['relevance_id'] = $relevance_id;
                          $addData['amount_payable'] = (new PurService())->getOperationAmount($amountSearch);
                          $addData['payable_date'] = date("Y-m-d");
                          $addData['relevance_id'] = $relevance_id;
                          // unset($addData['detail']); 放到addInfo里面来处理，否则多条记录的话，会导致detail无信息
                          $add_res_id = $this->addInfo($addData, $type, $action_type_cd, $clause_type, $bill_no);
                          if (!$add_res_id) {
                              $is_error = true;
                          }
                          if ($is_error) { // 表明其中有生成失败
                              return false;
                          }
                          return $add_res_id;
                        }
                        
                    }
                    break;
                default:
                    # code...
                    break;
            }
            return $this->addInfo($addData, $type, $action_type_cd, $clause_type, $bill_no);
        }
    }




    // 检测该条款是否符合满足有预付款和尾款的情况
    public function checkHasAdvanceAndEnd($relevance_id)
    {
      if (!$relevance_id) {
        return false;
      }
      $clauseWhere['purchase_id'] = array('eq', $relevance_id);
      $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type')->where($clauseWhere)->select(); // 获取条款信息 // 只有包含预付款和尾款信息时，才需要生成对应的记录
      //即至少有两条记录，而且必须要有一条为尾款信息
      $res = false;
      if (count($clauseInfo) >= '2') {
        foreach ($clauseInfo as $key => $value) {
          if ($value['clause_type'] === '2') {
            $res = true;
            break;
          }
        }
      }

      return $res;
    }

    public function addInfo($addData, $type, $action_type_cd, $clause_type = '5', $bill_no)
    {
        $type = strval($type);
        $clause_type = strval($clause_type);
        $class_name = $addData['class'];
        $function_name = $addData['function'];
        $is_after = $this->checkAfterCreateTime($addData['relevance_id']); //创建时间是否晚于上线时间
        $is_after_sec = $this->checkAfterCreateTime($addData['relevance_id'], $this->START_TIME_SEC); //创建时间是否晚于上线时间


        if ($clause_type == '6' && !$is_after) {
          $res = $this->checkHasAdvanceAndEnd($addData['relevance_id']); // 不符合要求，直接返回init_id
          if (!$res) {
            return 'init_id';
          }
        }
        if ($is_after_sec && $clause_type == '7' && in_array($action_type_cd, ['N002870017', 'N002870018'])) {
          return 'init_id'; // 上线之后的采购单，走无尾款的，要去掉
        }
        if ($is_after_sec && $clause_type == '10' && in_array($action_type_cd, ['N002870006', 'N002870005', 'N002870004'])) { // 上线之后的采购单，走无尾款的，要去掉
          return 'init_id';
        }
        if ($type!== '3' && !$is_after_sec && in_array($action_type_cd, ['N002870006', 'N002870005', 'N002870004'])) { // 历史数据保留原来模式
          $clause_type = '10';
        }


        unset($addData['clause_type']);
        unset($addData['class']);
        unset($addData['function']);
        unset($addData['detail']);

        if ($type == '1') { // 生成应付
            $payment_m = new TbPurPaymentModel();
            $addData['payment_no']      = $payment_m->createPaymentNO();
            $addData['update_time'] = date("Y-m-d H:i:s");
            unset($addData['money_id']);
            unset($addData['amountSearch']);
            unset($addData['ship_id']);
            if ($addData['amount_payable'] <= 0) { // 金额为0时，不需要生成应付记录
              return 'init_id';
            }
            $res = $payment_m->add($addData);
            if(!$res) {
                ELog::add('生成应付数据失败：'.json_encode($addData).M()->getDbError(),ELog::ERR);
                return false;
            }
            //使用采购单order_id查询采购单付款状态
            $order = D('TbPurRelevanceOrder')->where(['relevance_id'=>$addData['relevance_id']])->find();
            if(empty($order)) {
                ELog::add('查询采购单失败：'.json_encode($order).M()->getDbError(),ELog::ERR);
                return false;
            }
            $res_payment = $this->updateOrderPaymentStatus($order['order_id']);
            if($res_payment === false) {
                return false;
            }
        }

        if ($type === '2') { // 生成抵扣
            $logic      = D('Purchase/Payment','Logic');
            if ($addData['amount_deduction'] <= 0) { // 金额为0时，不需要生成抵扣金记录
              return 'init_id';
            }
            $res        = $logic->regardAsDeduction($addData);
            if(!$res) {
                ELog::add('生成抵扣金数据失败：'.json_encode($addData).M()->getDbError(),ELog::ERR);
                return false;
            }
        }

        if ($type === '3') { // 生成扣减抵扣
          $logic      = D('Purchase/Payment','Logic');
          if ($addData['amount_deduction'] <= 0) { // 金额为0时，不需要生成抵扣金记录
            return 'init_id';
          }
          $res        = $logic->cutUseDeduction($addData);
          if(!$res) {
              ELog::add('生成扣减抵扣金记录失败：'.json_encode($addData).M()->getDbError(),ELog::ERR);
              return false;
          }
          $type = '2'; // tb_pur_operation表只记录抵扣金/应付大类，扣减抵扣属于抵扣金类型
        }

        // 根据应付id，生成触发操作记录tb_pur_operation
        $operationAddInfo['main_id'] = $res;
        $operationAddInfo['clause_type'] = $clause_type;
        $operationAddInfo['money_type'] = $type;
        $operationAddInfo['action_type_cd'] = $action_type_cd;
        $operationAddInfo['bill_no'] = $bill_no;
        $operationAddInfo['created_by'] = DataModel::userNamePinyin();
        $res = $this->add($operationAddInfo);
        if (!$res) {
            $report_res = [$addData, $type, $action_type_cd, $clause_type, $bill_no];
            ELog::add('采购触发操作生成抵扣/应付记录：'.json_encode($report_res).M()->getDbError(),ELog::ERR);
            // Logs($report_res, $class_name, $function_name);
            @SentinelModel::addAbnormal('采购触发操作生成抵扣/应付记录', $action_type_cd . ' : ' . $bill_no . '异常', $report_res, 'scm_operation_notice');
        }
        return $res;
    }

    // 采购单是否正常
    public function checkHasNotCancel($relevance_id)
    {
      if (!$relevance_id) {
        return false;
      }
      $order_status = D('TbPurRelevanceOrder')->where(['relevance_id' => $relevance_id])->getField('order_status');
      if ($order_status === 'N001320300') {
        return true;
      } 
      return false; 
    }

    /**
     * @name 采购单是否无尾款
     * @param $relevance_id
     * @return bool
     */
    public function checkHasFundOfEnd($relevance_id)
    {
        $clauseWhere['purchase_id'] = array('eq', $relevance_id);
        $clauseInfo = (new Model())->table('tb_pur_clause')->field('clause_type')->where($clauseWhere)->select(); // 获取条款信息
        //金额类型，1:预付款，2:尾款
        $clause_types = array_column($clauseInfo, 'clause_type');
        //需求变更 9448采购结算改版2.16追加无尾款 2019-08-15
        //只有预付款没有尾款 则是无尾款
        $no_fund_of_end = false; //是否是无尾款
        if (in_array('1', $clause_types) && !(in_array('2', $clause_types))) {
            $no_fund_of_end = true; // 无尾款
        }
        return $no_fund_of_end;
    }

    /**
     * @name 上线采购单创建时间是否晚于时间
     * @param $relevance_id
     * @return bool
     */
    public function checkAfterCreateTime($relevance_id, $start_time = '')
    {
        //需求9798-无尾款应付、抵扣生成逻辑调整-新增：上线时间后的采购单生效
        if (!$start_time) {
            $start_time = $this->START_TIME;
        }
        $order = D('TbPurRelevanceOrder')->where(['relevance_id' => $relevance_id])->find();
        $is_after = false; //不晚于
        if ($start_time < $order['prepared_time']) {
            $is_after = true; //晚于
        }
        return $is_after;
    }

    //更新采购单付款状态
    public function updateOrderPaymentStatus($order_id)
    {

        $order['payment_type'] = 1;
        $order['order_id'] = $order_id;
        $payment_status = D('TbPurPayment')->paymentStatusCheck($order);
        $save_order['payment_status'] = $payment_status;
        $res_payment = D('TbPurRelevanceOrder')->where(['order_id'=>$order_id])->save($save_order);
        ELog::add('订单应付状态保存反馈：'.json_encode($res_payment));
        if($res_payment === false) {
            ELog::add('订单应付状态保存失败：'.json_encode($save_order).M()->getDbError(),ELog::ERR);
        }
        return $res_payment;
    }

    // 应付及抵扣金金额详情表 入库单号
    public function updateBillNo($Ids,$bill_no){
        $where['id'] = array('in',$Ids);
        $objectModel = M('operation','tb_pur_');
        $res = $objectModel->where($where)->save(['bill_no'=> $bill_no]);
        return $res;
    }
}