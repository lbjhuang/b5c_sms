<?php
/**
 * User: yuanshixiao
 * Date: 2018/9/25
 * Time: 13:53
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class WarehouseLogic extends BaseLogic
{
    /**
     * 结束入库
     * @param $ship_id
     * @return bool
     */
    public function warehouseEnd($ship_id) {
        $ship_model        = M('ship','tb_pur_');
        $relevance_model   = M('relevance_order','tb_pur_');
        $ship_model->startTrans();
        //标记发货完成
        $res_ship_status    = $ship_model->where(['id'=>$ship_id])->save(['warehouse_status'=>1]);
        if($res_ship_status == false) {
            $ship_model->rollback();
            $this->error = '修改入库状态失败';
            return false;
        }

        //如果所有发货都已经入库则入库完成
        $order_info = $relevance_model
            ->alias('t')
            ->field('t.ship_status,a.procurement_number,t.relevance_id,t.prepared_time')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_ship b on b.relevance_id=t.relevance_id')
            ->where(['b.id'=>$ship_id])
            ->find();
        if($order_info['ship_status'] == 2) {
            $not_warehoused = M('ship','tb_pur_')->where(['relevance_id'=>$order_info['relevance_id'],'warehouse_status'=>['in',[0,2]]])->getField('id');
            if(!$not_warehoused) {
                $res_warehouse_status = $relevance_model->where(['relevance_id'=>$order_info['relevance_id']])->save(['warehouse_status'=>2]);
                if($res_warehouse_status === false) {
                    $ship_model->rollback();
                    $this->error = '修改入库状态失败';
                    return false;
                }
            }
        }

        $warehouse_id = $ship_model->where(['id'=>$ship_id])->getField('warehouse_id');
        $addDataInfo['money_id'] = $ship_id;
        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $op_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870005', $order_info['relevance_id'], $warehouse_id);
        if (!$op_res) {
            $ship_model->rollback();
            $this->error = '生成抵扣金记录失败';
            @SentinelModel::addAbnormal('采购入库标记完结-生成抵扣金记录失败', $order_info['procurement_number'], [$addDataInfo, $order_info,$warehouse_id], 'pur_notice');
            return false;
        }

        // 需求8945要求标记完结不扣减在途
        //需求 9131 采购在途优化
        $warehouse = $relevance_model
            ->alias('t')
            ->join('tb_pur_order_detail a on a.order_id = t.order_id')
            ->where(['t.relevance_id'=>$order_info['relevance_id']])
            ->getField('warehouse');
        if($warehouse != 'N000680800') {
            $goods = M('ship', 'tb_pur_')
                ->alias('t')
                ->field('sku_information,a.ship_number-a.warehouse_number-a.warehouse_number_broken remainder')//原本没有减已入库残次品，20190730改成要减残次品
                ->join('left join tb_pur_ship_goods a on a.ship_id=t.id')
                ->join('left join tb_pur_goods_information b on b.information_id=a.information_id')
                ->where(['t.id' => $ship_id, '_string' => 'a.ship_number-a.warehouse_number-a.warehouse_number_broken>0'])//原本没有减已入库残次品，20190730改成要减残次品
                ->select();
            $goods_end = [];
            foreach ($goods as $v) {
                $goods_info['operatorId'] = $_SESSION['userId'];
                $goods_info['purchaseOrderNo'] = $order_info['procurement_number'];
                $goods_info['skuId'] = $v['sku_information'];
                $goods_info['num'] = $v['remainder'];
                $goods_end[] = $goods_info;
            }
            if (!empty($goods_end) && $order_info['prepared_time'] > '2018-09-29 10:06:00') {
                $is_old = false;                    
                if ($order_info['prepared_time'] < '2019-07-30 00:00:00') { // #9474 SKU在途库存不够，则对应SKU的在途数量自动扣减至0
                    $is_old = true;                    
                }
                $res_on_way = ApiModel::onWayEnd($goods_end, $is_old);
                ELog::add(['info' => '调用在途扣减接口', 'error' => $this->error, 'request' => $goods_end, 'response' => $res_on_way], ELog::INFO);
                if ($res_on_way['code'] != 2000) {
                    $ship_model->rollback();
                    $this->error = '在途扣减失败';
                    @SentinelModel::addAbnormal('采购入库标记完结-调用在途扣减接口失败', $order_info['procurement_number'], [$res_on_way, $goods_end,$is_old], 'pur_notice');
                    return false;
                }
            }
        }
        (new TbPurActionLogModel())->addLog($order_info['relevance_id']);
        $ship_model->commit();
        return true;

    }
    // 撤回入库完结
    public function warehouseEndRevoke($ship_id) {
        $ship_m = D('TbPurShip');
        $ship_m->startTrans();
        $ship   = $ship_m->lock(true)->where(['id'=>$ship_id])->find();
        //发货单状态为已入库并且发货数大于入库总数则为标记入库完结的入库单，可撤销标记完结
        if(!$ship || $ship['warehouse_status'] != TbPurShipModel::$warehouse_status['done'] || !($ship['shipping_number'] > $ship['warehouse_number'] + $ship['warehouse_number_broken'])) {
            $this->error = '发货单不存在或非标记完结发货单';
            return false;
        }
        $save['warehouse_status'] = ($ship['warehouse_number'] || $ship['warehouse_number_broken']) ? 2 : 0;
        $res_ship_status    = $ship_m->where(['id'=>$ship_id])->save($save);
        if($res_ship_status == false) {
            $ship_m->rollback();
            $this->error = '修改入库状态失败';
            return false;
        }
        $res_relevance = $this->updateRelevanceWarehouseStatus($ship['relevance_id']);
        if($res_relevance === false) {
            $ship_m->rollback();
            $this->error = '修改入采购单状态失败';
            return false;
        }

        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $addDataInfo['money_id'] = $ship_id; //用来获取应付金额
        $op_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', 'N002870006', $ship['relevance_id'], $ship['warehouse_id']);
        if ($op_res === false) {
            $ship_m->rollback();
            $this->error = '生成应付记录失败';
            return false;
        }
        $ship_m->commit();
        return true;
    }

    /**
     * 入库
     * @param $warehouse_save
     * @return bool
     */
    public function warehouse($warehouse_save) {
        if(!$warehouse_save['warehouse_number'] && !$warehouse_save['warehouse_number_broken']) {
            $this->error = '入库数量不能为0';
            return false;
        }
        $model          = new Model();
        $model->startTrans();
        $ship_m         = new TbPurShipModel();
        $ship_info      = $model->lock(true)->table('tb_pur_ship')->where(['id'=>$warehouse_save['ship_id']])->find();
        $order_info     = $model
            ->table('tb_pur_order_detail t')
            ->join('left join tb_pur_relevance_order a on a.order_id = t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id = a.sell_id')
            ->where(['relevance_id'=>$ship_info['relevance_id']])
            ->find();
        if(!$ship_info || $ship_info['warehouse_status'] == 1) {
            $this->error = '发货信息不存在或已经入库';
            return false;
        }
        $warehouse_status = D('TbMsCmnCd')->where(['CD'=>$ship_info['warehouse']])->getField('USE_YN');
        if($warehouse_status == 'N') {
            $this->error = '入库仓库已禁用';
            return false;
        }
        $warehouse_save['warehouse_user']   = $_SESSION['m_loginname'];
        $warehouse_save['warehouse_time']   = date('Y-m-d H:i:s');
        //保存入库信息
        $warehouse_id       = $model->table('tb_pur_warehouse')->add($warehouse_save);
        if(!$warehouse_id) {
            M()->rollback();
            $this->error = '入库失败:入库信息保存失败';
            return false;
        }

        //更新发货表入库状态、入库数量
        $ship_save['warehouse_number']          = $ship_info['warehouse_number'] + $warehouse_save['warehouse_number'];
        $ship_save['warehouse_number_broken']   = $ship_info['warehouse_number_broken'] +$warehouse_save['warehouse_number_broken'];
        if($ship_info['shipping_number'] == ($ship_save['warehouse_number']+$ship_save['warehouse_number_broken'])) {
            $ship_save['warehouse_status'] = 1;
        }else {
            $ship_save['warehouse_status'] = 2;
        }
        $res_ship = $model->table('tb_pur_ship')->where(['id'=>$warehouse_save['ship_id']])->save($ship_save);
        if($res_ship === false) {
            M()->rollback();
            $this->error = '入库失败:更新入库状态失败';
            return false;
        }
        $shipments_number   = 0;
        $shipped_goods = [];
        foreach ($warehouse_save['goods'] as $k => $v) {
            if($v['warehouse_number'] || $v['warehouse_number_broken']) {
                $shipped_goods[] = ['goods_id' => $k, 'warehouse_number' => $v['warehouse_number'], 'warehouse_number_broken' => $v['warehouse_number_broken']];
                $goods_info = $model
                    ->table('tb_pur_ship_goods t')
                    ->field('a.sku_information,a.unit_price,t.ship_number,t.warehouse_number,t.warehouse_number_broken,drawback_percent,a.information_id,a.unit_expense, t.sell_small_team_json')
                    ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                    ->where(['t.id'=>$k])
                    ->find();
                if($v['warehouse_number'] < 0 || $v['warehouse_number_broken']< 0) {
                    M()->rollback();
                    $this->error = '入库商品数量不能为负';
                    return false;
                }
                if($goods_info['ship_number'] < $goods_info['warehouse_number'] + $goods_info['warehouse_number_broken'] + $v['warehouse_number'] + $v['warehouse_number_broken']) {
                    M()->rollback();
                    $this->error = '入库商品数量异常';
                    return false;
                }
                $goods['skuId']                  = $goods_info['sku_information'];
                $goods['price']                  = $goods_info['unit_price'];
                $goods['currencyId']             = $order_info['amount_currency'];
                $goods['currencyTime']           = $order_info['creation_time'];
                $goods['purInvoiceTaxRate']      = substr(cdVal($order_info['tax_rate']),0,-1)/100;
                $goods['proportionOfTax']        = substr(cdVal($goods_info['drawback_percent']),0,-1)/100;
                $goods['purStorageDate']         = date('Y-m-d H:i:s');
                $goods['logCurrency']            = $warehouse_save['log_currency'];
                $goods['logServiceCostCurrency'] = $warehouse_save['service_currency'];
                $goods['poCurrency']             = $order_info['amount_currency'];
                $goods['poCost']                 = $goods_info['unit_expense'];
                $number_info                     = json_decode($v['number_info_warehouse'],true);
                foreach ($number_info as $value) {
                    if($value['number'] || $value['broken_number']) {
                        $goods['num']                   = $value['number'] ? : 0;
                        $goods['brokenNum']             = $value['broken_number'] ? : 0;
                        $goods['deadlineDateForUse']    = $value['expiration_date'];
                        $goods['smallSaleTeamCode']     = $value['small_team_code'];    
                        $bill_goods[]                   = $goods;
                    }
                }
                //在途数据
                $on_way['SKU_ID']       = $goods_info['sku_information'];
                $on_way['TYPE']         = 1;
                $on_way['on_way']       = $goods_info['ship_number'];
                $on_way['on_way_money'] = round($goods_info['ship_number']*$goods_info['unit_price']*$order_info['amount_currency_rate'],2);
                $on_way_all[]           = $on_way;
                $b2b_ship_goods[]       = [
                    'sku_id'        => $goods_info['sku_information'],
                    'delivered_num' => $goods_info['ship_number'],
                    'sku_price'     => $goods_info['unit_price'],
                    'sku_currency'  => $order_info['amount_currency']
                ];
                $shipments_number   += $goods_info['ship_number'];
                //邮件发货数据
                $warehouse_number[$goods_info['information_id']]        = $v['warehouse_number'];
                $warehouse_number_broken[$goods_info['information_id']] = $v['warehouse_number_broken'];
                //更新商品体积重量
                $data[$goods_info['sku_information']] = [
                    'length'    => $v['length'],
                    'height'    => $v['height'],
                    'width'     => $v['width'],
                    'weight'    => $v['weight']
                ];

                //发货商品更新入库数
                // 更新该SKU下所有小团队的入库正品数量和次品数量
                $update_sell_small_team_arr = [];
                $update_sell_small_team_arr = json_decode($goods_info['sell_small_team_json'], true); // 原来的小团队信息
                $update_sell_small_team_arr_list = array_column($update_sell_small_team_arr, 'small_team_code');
                foreach ($update_sell_small_team_arr as $ke => $ve) {
                    // 循环,相同的小团队数量叠加
                    foreach ($number_info as $ki => $vi) {
                        if ($vi['small_team_code'] === $ve['small_team_code']) {
                            $vv['small_team_code'] = $vi['small_team_code'];
                            $vv['warehouse_number'] = $ve['warehouse_number'] + $vi['number'];
                            $vv['warehouse_number_broken'] = $ve['warehouse_number_broken'] + $vi['broken_number'];
                            $update_sell_small_team_arr[$ke] = $vv;
                        }
                    }
                }

                // 新的补充小团队
                foreach ($number_info as $ky => $vy) {
                    if (!in_array($vy['small_team_code'], $update_sell_small_team_arr_list) && isset($vy['small_team_code'])) {
                        $vv = [];
                        $vv['small_team_code'] = $vy['small_team_code'];
                        $vv['warehouse_number'] = $vy['number'] ? : 0;
                        $vv['warehouse_number_broken'] = $vy['broken_number'] ? : 0;
                        $update_sell_small_team_arr[] = $vv;
                    }
                }
                $update_ship_goods_info = [
                    'warehouse_number'          => ['exp','warehouse_number+'.$v['warehouse_number']],
                    'warehouse_number_broken'   => ['exp','warehouse_number_broken+'.$v['warehouse_number_broken']],
                    'sell_small_team_json'      => json_encode($update_sell_small_team_arr) 
                ];
                $res                = $model->table('tb_pur_ship_goods')->where(['id'=>$k])->save($update_ship_goods_info);
                if($res === false) {
                    M()->rollback();
                    $this->error = '入库失败:商品入库数更新失败';
                    return false;
                }
                //保存入库商品
                $v['warehouse_id']  = $warehouse_id;
                $v['ship_goods_id'] = $k;
                if(!($model->table('tb_pur_warehouse_goods')->create($v) && $model->add())) {
                    M()->rollback();
                    $this->error = '入库失败:商品信息保存失败';
                    return false;
                }
                $location = [
                    'warehouse_code'              => $ship_info['warehouse'],
                    'sku'                         => $goods_info['sku_information'],
                    'location_code'               => trim($v['location_code']),
                    'defective_location_code'     => trim($v['defective_location_code']),
                ];
                $location_arr[] = $location;
            }
        }
        $locationService = new LocationService();
        $res_location = $locationService->recordLocationBatch($location_arr);
        if(!$res_location) {
            Logs(json_encode($location_arr), __FUNCTION__.'----fix--1', 'tr');
            $this->error = L('货位保存失败');
            M()->rollback();
            return false;
        }

        $res_option         = ApiModel::updatePmsGoods($data);
        if($res_option['code'] != 2000) {
            ELog::add(
                [
                    'info' => '更新商品体积、重量失败',
                    'request' => $data,
                    'response' => $res_option
                ],
                ELog::ERR
            );
        }

        //如果已经全部发货,并且其他发货已经入库，则修改入库状态为入库完成
        if($order_info['ship_status'] == 2) {
            $count = $model->table('tb_pur_ship')->where(['warehouse_status'=>['in',[0,2]],'relevance_id'=>$order_info['relevance_id']])->count();
            if($count > 0) {
                $relevance_save['warehouse_status'] = 1;
            }else {
                $relevance_save['warehouse_status'] = 2;
            }
        }else {
            $relevance_save['warehouse_status'] = 1;
        }
        $res = M('relevance_order','tb_pur_')->where(['relevance_id'=>$order_info['relevance_id']])->save($relevance_save);
        if($res === false) {
            M()->rollback();
            $this->error = '更新订单发货状态失败';
            return false;
        }



        //创建应付
        $clauseInfo = (new Model())->table('tb_pur_clause')->where(['purchase_id' => $ship_info['relevance_id']])->getField('id'); // 根据报价单id（仅限创建采购单时用）或采购单id获取条款信息，有信息记录，走新流程，没有，则走旧流程
        if (!$clauseInfo) {
            if(!(new TbPurPaymentModel())->createPayableByWarehouse($warehouse_id)) {
                M()->rollback();
                $this->error = '创建应付失败';
                return false;
            }
        }

        //  调整顺序
        // 处理 入库单处理ID
        $Ids = array();
        if ($clauseInfo) { // 有新的适用条款时，走新流程生成应付/抵扣记录
            // 应付记录生成 采购入库确认（正品）
            $warehouse_code = '';
            $addDataInfo['detail'] = $shipped_goods; // 本次发货数量 和采购价 用来获取付款规则公式的总金额
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            if ($warehouse_save['warehouse_number']) {
                $resPay = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', 'N002870003', $ship_info['relevance_id'], $warehouse_code);
                array_push($Ids,$resPay);
                if (!$resPay) {
                    M()->rollback();
                    $this->error = '创建应付金失败';
                    return false;
                }
            }

            // 采购入库确认（残次品）
            if ($warehouse_save['warehouse_number_broken']) {
                $resDeduction = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870004', $ship_info['relevance_id'], $warehouse_code);
                if ( $resDeduction != 'init_id' ){
                    array_push($Ids,$resDeduction);
                }
                if (!$resDeduction) {
                    M()->rollback();
                    $this->error = '创建抵扣金失败';
                    return false;
                }
            }
        }


        //入库为调用接口，无法被事务回滚，放到最后处理
        $ship_money = 0;
        foreach ($bill_goods as $v) {
            $ship_money += $v['price']*($v['num']+$v['brokenNum']);
        }
        foreach ($bill_goods as $k => $v) {
            $bill_goods[$k]['storageLogCost']    = round($v['price'] / $ship_money * $warehouse_save['storage_log_cost'], 2);
            $bill_goods[$k]['logServiceCost']    = round($v['price'] / $ship_money * $warehouse_save['log_service_cost'], 2);
            $bill_goods[$k]['carryCost']         = $bill_goods[$k]['storageLogCost'];//运输费用
            $bill_goods[$k]['allStorageLogCost'] = $warehouse_save['storage_log_cost'];//总物流费用
        }
        $demand_code = substr($order_info['procurement_number'],0 ,-4);
        $bill_data      = [
            'bill' => [
                'billType'          => 'N000940100',//收发类型，采购入库为N000940100固定不变
                'relationType'      => 'N002350200',//业务单据的类型,采购单N002350200
                'virType'           => 'N002440100',//现货入库(N002440100)、在途入库(N002440200)
                'channel'           => 'N000830100',// 默认
                'procurementNumber' => $order_info['procurement_number'], //采购单号
                'linkBillId'        => $order_info['procurement_number'], //采购单号
                'orderId'           => $demand_code, //B2B单号
                'warehouseRule'     => $ship_info['need_warehousing'], //是否入我方仓库
                'batch'             => $ship_info['bill_of_landing'],//批次，这个待定
                'saleNo'            => $ship_info['sale_no'],// 数据库无对应字段
                'supplier'          => $order_info['supplier_id'],// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                'warehouseId'       => $ship_info['warehouse'],// 仓库id（码表或数据字典对应的值）
                'saleTeam'          => $order_info['sell_team'],//销售团队
                'spTeamCd'          => $order_info['payment_company'],//采购团队
                'conCompanyCd'      => $order_info['our_company'],//我方公司
                'operatorId'        => $_SESSION['userId'],//操作人id
            ],
            'guds' => $bill_goods
        ];
        if($ship_info['need_warehousing']) {
            $bill_data['bill']['processOnWay'] = 1; //普通采购入库
            if(
                M('b2b_order','tb_')->where(['PO_ID'=>$demand_code])->getField('ID') &&
                M('b2b_doship','tb_')->where(['PO_ID'=>$demand_code])->getField('shipping_status') != 3
            ) {
                $bill_data['bill']['type']  = 3;
            }
        }else {
            $introduce_team = M('sp_supplier', 'tb_crm_')
                ->where(['SP_CHARTER_NO' => $order_info['sp_charter_no'], 'DATA_MARKING' => 0])
                ->getField('SP_JS_TEAM_CD');
            $b2b_ship_data = [
                'order_info' => [
                    "po_id" => $ship_info['sale_no'],
                    "supplier_id" => 'supplier_id',
                    "purchasing_team" => $order_info['payment_company'],
                    "introduce_team" => $introduce_team
                ],
                'goods' => $b2b_ship_goods,
                'logictics' => [
                    "bill_lading_code" => $ship_info['bill_of_landing'],
                    "delivery_time" => $ship_info['shipment_date'],
                    "estimated_arrival_date" => $ship_info['arrival_date'],
                    "logistics_currency" => $ship_info['extra_cost_currency'],
                    "logistics_costs" => $ship_info['extra_cost'],
                    "shipments_number" => $shipments_number,
                    "remarks" => $ship_info['remark']
                ]
            ];
            if (!$this->b2bShipCheck($b2b_ship_data, $order_info['procurement_number'])) {
                return false;
            }
            $bill_data['bill']['processOnWay'] = 2; //虚拟仓采购入库
        }

        //20201229修改，b2b虚拟仓发货，放在采购入库之前执行（原本放在采购入库之后执行，这样存在b2b发货异常，采购入库不能撤回问题）
        if(!$ship_info['need_warehousing'] )  {
            if (!$this->b2bShip($b2b_ship_data)) {
                M()->rollback();
                return false;
            }
        }

        $res_j  = ApiModel::warehouse($bill_data);
        $res    = json_decode($res_j,true);
        ELog::add(['msg'=>'调用入库接口'.($res['code'] == 2000 ? '成功' : '失败'), 'request'=>$bill_data,'response'=>$res_j],ELog::INFO);
        if($res['code'] != 2000) {
            M()->rollback();
            $this->error = '入库'.$res['msg'];
            return false;
        }

        //20201229修改，查找B2B发货出库单据数据，更新b2b发货单数据
        (new B2bService())->updateShipOutBillId($ship_info['sale_no'], $res['data'][0]['exportBillNo']);

        //以下保存失败不回滚
        //  处理 应付及抵扣金金额详情表 中 入库单号  tb_pur_operation
        $bill_no = $res['data'][0]['importBillNo'];
        if (!empty($Ids) && is_array($Ids) && !empty($bill_no)){
            $ret = D('Scm/PurOperation')->updateBillNo($Ids,$bill_no);
            ELog::add(['msg'=>'更新入库单号  ID组'.json_encode($Ids).'  入库单号 '.$bill_no],ELog::INFO);

        }

        M('warehouse','tb_pur_')->where(['id'=>$warehouse_id])->save(['warehouse_code'=>$res['data'][0]['importBillNo']]);

        if(ACTION_NAME == 'warehouse') {
            (new TbPurActionLogModel())->addLog($ship_info['relevance_id']);
        }
        M()->commit();
        $goods_email = $model
            ->table('tb_pur_goods_information t')
            ->field('t.information_id,t.sku_information,pa.upc_id,goods_number,sum(b.warehouse_number) warehoused_number,sum(b.warehouse_number_broken) warehoused_number_broken')
            ->join('left join tb_pur_ship_goods a on a.information_id=t.information_id')
            ->join('left join tb_pur_warehouse_goods b on b.ship_goods_id=a.id')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on t.sku_information=pa.sku_id')
            ->group('t.information_id')
            ->where(['t.relevance_id'=>$order_info['relevance_id']])
            ->select();
        $goods_email = SkuModel::getInfo($goods_email,'sku_information',['spu_name','image_url','attributes']);

        //邮件信息
        $receiver[]         = $order_info['prepared_by'];
        $cc[]               = $order_info['seller'];
        $receiver_demand = D('Scm/Demand')
            ->field('create_user,seller')
            ->where(['demand_code'=>$demand_code])
            ->find();
        $receiver[] = $receiver_demand['create_user'];
        $cc[]       = $receiver_demand['seller'];
        //预定转占用邮件
        $occupy_orders   = $res['data'][0]['onWayOrdIds'];
        if($occupy_orders) {
            $receiver_occupy = D('Scm/Demand')
                ->alias('t')
                ->field('create_user,seller,b.sales_assistant_by')
                ->join('tb_crm_sp_supplier a on a.SP_CHARTER_NO = t.customer_charter_no and a.SP_STATUS=1 and a.DEL_FLAG=1 and DATA_MARKING=1')
                ->join('tb_con_division_client b on b.supplier_id = a.ID')
                ->where(['demand_code'=>['in',$occupy_orders]])
                ->select();
            foreach ($receiver_occupy as $v) {
                $receiver[] = $v['create_user'];
                $cc[]       = $v['seller'];
                if($v['sales_assistant_by']) {
                    foreach (explode(',',$v['sales_assistant_by']) as $val) {
                        $receiver[] = $val;
                    }
                }
            }
        }
        $receiver   = array_unique($receiver);
        $cc         = array_unique($cc);
        foreach ($receiver as $k => $v) {
            if($v) {
                $receiver[$k] = $v . '@gshopper.com';
            }else {
                unset($receiver[$k]);
            }
        }
        foreach ($cc as $k => $v) {
            if($v) {
                $cc[$k] = $v . '@gshopper.com';
            }else {
                unset($cc[$k]);
            }
        }
        $warehouse_email    = M('cmn_cd','tb_ms_')->where(['CD'=>$ship_info['warehouse']])->getField('ETC3');
        if($warehouse_email) $cc = array_merge($cc, explode(',' , $warehouse_email));
        $this->email_info = [
            'title'                     => "采购单{$order_info['procurement_number']}入库提醒",
            'purchase_no'               => $order_info['procurement_number'],
            'warehouse'                 => $ship_info['warehouse'],
            'receiver'                  => $receiver,
            'cc'                        => $cc,
            'goods'                     => $goods_email,
            'warehouse_number'          => $warehouse_number,
            'warehouse_number_broken'   => $warehouse_number_broken
        ];
        return true;
    }


    public function b2bShip($b2b_ship_data) {
        $res = A('Home/B2b')->scmSendOut($b2b_ship_data);
        if($res['code'] != 200) {
            ELog::add(['info'=>'B2B虚拟仓发货失败','request'=>$b2b_ship_data,'response'=>$res]);
            $this->error = 'B2B发货失败,'.$res['msg'];
            return false;
        }
        return true;
    }

    public function b2bShipCheck($b2b_ship_data, $purchase_order_no) {
        if(!D('Scm/Demand')->isSell($b2b_ship_data['order_info']['po_id'])) {
            $this->error = '无关联需求或需求类型不为销售订单';
            return false;
        }
        $cookie = '';
        foreach ($_COOKIE as $k => $v) {
            $cookie .= $k.'='.$v.';';
        }
        $url                = ERP_HOST.U('b2b/checkResidualSend');
        //$url                = 'http://erp.gshopper.stage.com/index.php?m=b2b&a=checkResidualSend';
        $residual_request   = ['po_id'=>$b2b_ship_data['order_info']['po_id']];
        $res_residual       = curl_get_json($url,json_encode($residual_request),$cookie);
        $residual_send      = json_decode($res_residual,true);
        if($residual_send['code'] != 200000) {
            $this->error = 'B2B待发货数量查询失败';
            ELog::add(['info'=>'B2B未发货数查询失败，'.$residual_send['msg'],'request'=>$residual_request,'response'=>$residual_send],ELog::ERR);
            return false;
        }
        $residual   = array_column($residual_send['data'],null,'SKU_ID');
        $occupy     = M('batch','tb_wms_')
            ->alias('t')
            ->join('tb_wms_batch_order a on a.batch_id=t.id')
            ->where(['t.purchase_order_no'=>$purchase_order_no,'t.vir_type'=>'N002440200','a.use_type'=>1,'a.ORD_ID'=>$b2b_ship_data['order_info']['po_id']])
            ->getField('t.SKU_ID,a.occupy_num',true);
        foreach ($b2b_ship_data['goods'] as $v) {
            if($v['delivered_num'] > $residual[$v['sku_id']]['ALL_TOBE_DELIVERED_NUM']) {
                $this->error = $v['sku_id'].'发给客户数量大于对应B2B订单未发货数';
                return false;
                break;
            }
            if($v['delivered_num'] > $occupy[$v['sku_id']]) {
                $this->error = $v['sku_id'].'发给客户数量大于对应B2B订单占用的数量';
                return false;
                break;
            }
        }

        if($this->getError()) return false;
        return true;
    }

    public function updateRelevanceWarehouseStatus($relevance_id) {
        $relevance_m        = D('TbPurRelevanceOrder');
        $ship_m             = D('TbPurShip');
        $ship_status        = $relevance_m->where(['relevance_id'=>$relevance_id])->getField('ship_status');
        $has_warehouse      = $ship_m->where(['relevance_id'=>$relevance_id,'warehouse_status'=>['neq',0]])->getField('id') ? true :false;
        $has_not_warehouse  = $ship_m->where(['relevance_id'=>$relevance_id,'warehouse_status'=>['neq',1]])->getField('id') ? true :false;
        switch ($ship_status) {
            case 0:
                $warehouse_status = 0;
                break;
            case 1:
                $warehouse_status = $has_warehouse ? 1 : 0;
                break;
            case 2:
                $warehouse_status = !$has_not_warehouse ? 2 : ($has_warehouse ? 1 : 0);
                break;
            default :
                $this->error = '发货状态异常';
                return false;
        }
        return $relevance_m->where(['relevance_id'=>$relevance_id])->save(['warehouse_status'=>$warehouse_status]);
    }

    public function goodsConversion($data) {
        M()->startTrans();
        foreach ($data as $v) {
            if($v['bill_id'] && $v['sku_id'] && $v['number'] && $v['type']) {
                switch ($v['type']) {
                    case ConversionModel::$type['quality_to_broken'] :
                        $save = [
                            'warehouse_number' => ['exp','warehouse_number-'.$v['number']],
                            'warehouse_number_broken' => ['exp','warehouse_number_broken+'.$v['number']],
                        ];
                        break;
                    case ConversionModel::$type['broken_to_quality'] :
                        $save = [
                            'warehouse_number' => ['exp','warehouse_number+'.$v['number']],
                            'warehouse_number_broken' => ['exp','warehouse_number_broken-'.$v['number']],
                        ];
                        break;
                    default :
                        $this->error = '参数异常';
                        M()->rollback();
                        return false;
                }
                $ship_m             = new TbPurShipModel();
                $ship_goods_m       = new TbPurShipGoodsModel();
                $warehouse_m        = M('warehouse','tb_pur_');
                $warehouse_goods_m  = M('warehouse_goods','tb_pur_');
                $warehouse_info = $ship_m
                    ->alias('t')
                    ->field('t.id ship_id,a.id ship_goods_id,b.id warehouse_id,c.id warehouse_goods_id')
                    ->join('left join tb_pur_ship_goods a on a.ship_id=t.id')
                    ->join('inner join tb_pur_warehouse b on b.ship_id=t.id')
                    ->join('inner join tb_pur_warehouse_goods c on c.warehouse_id=b.id and c.ship_goods_id=a.id')
                    ->join('inner join tb_pur_goods_information d on d.information_id=a.information_id')
                    ->where(['b.warehouse_code'=>$v['bill_id'],'d.sku_information'=>$v['sku_id']])
                    ->find();
                if (empty($warehouse_info)) {
                    $this->error = '参数异常';
                    M()->rollback();
                    return false;
                }
                $res_ship               = $ship_m->where(['id'=>$warehouse_info['ship_id']])->save($save);
                $res_ship_goods         = $ship_goods_m->where(['id'=>$warehouse_info['ship_goods_id']])->save($save);
                $res_warehouse          = $warehouse_m->where(['id'=>$warehouse_info['warehouse_id']])->save($save);
                $res_warehouse_goods    = $warehouse_goods_m->where(['id'=>$warehouse_info['warehouse_goods_id']])->save($save);
                if(!($res_ship && $res_ship_goods && $res_warehouse && $res_warehouse_goods)) {
                    ZUtils::saveLog(['where' => ['id'=>$warehouse_info['ship_id']], 'save_data' => $save], '采购单更新-ship');
                    ZUtils::saveLog(['where' => ['id'=>$warehouse_info['ship_goods_id']], 'save_data' => $save], '采购单更新-ship_goods');
                    ZUtils::saveLog(['where' => ['id'=>$warehouse_info['warehouse_id']], 'save_data' => $save], '采购单更新-warehouse');
                    ZUtils::saveLog(['where' => ['id'=>$warehouse_info['warehouse_goods_id']], 'save_data' => $save], '采购单更新-warehouse_goods');
                    $this->error = '保存失败';
                    M()->rollback();
                    return false;
                }
            }
        }
        M()->commit();
        return true;
    }

}