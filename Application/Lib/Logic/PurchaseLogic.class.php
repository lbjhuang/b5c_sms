<?php
/**
 * User: yuanshixiao
 * Date: 2017/12/6
 * Time: 14:21
 */

class PurchaseLogic
{
    public $relevance;
    public $order;
    public $goods;
    public $ship;
    public $ship_goods;
    public $invoice;
    public $payment;
    protected $failure_reason;
    public $res_orders  = [];
    public $has_failure = false;
    public $has_success = false;
    static $info_empty_reason = [
        'procurement_number'    => '订单为空',
        'number'                => '数量为空',
        'sku_id'                => '商品编码为空',
        'warehouse'             => '仓库为空',
        'arrival_date_actual'   => '制单日期为空',
    ];
    static $date_different_reason       = '日期不一致';
    static $warehouse_different_reason  = '仓库不一致';
    static $no_order_reason             = '订单不存在';
    static $no_ship_reason              = '该订单没有待入库发货';
    static $update_part_columns         = [
        'procurement_number',
        'payment_company',
        'our_company',
        'delivery_type',
        'sell_number',
        'sell_team',
        'sell_mode',
        'business_type',
    ];

    public function init() {
    }

    public function excelWarehouse($all_goods) {
        foreach ($all_goods as $procurement_number => $order_goods) {
            if(!$this->excelWarehouseValidate($order_goods)) {
                $this->has_failure = true;
                $this->res_orders[$procurement_number] = $this->res_goods;
                $this->res_goods = [];
            }else {
                $this->excelWarehouseEachOrder($procurement_number,$order_goods);
                if(!empty($this->res_goods)) {
                    $this->res_orders[$procurement_number] = $this->res_goods;
                    $this->res_goods = [];
                }
            }
        }
        if(!$this->has_success) {
            return 0;
        }elseif(!$this->has_failure) {
            return 1;
        }else {
            return 2;
        }
    }

    public function excelWarehouseValidate($order_goods) {
        $this->date         = current($order_goods)['arrival_date_actual'];
        $this->warehouse    = current($order_goods)['warehouse'];
        $has_different_warehouse    = false;
        $has_different_date         = false;
        $has_empty_column           = false;
        foreach ($order_goods as $sku => &$goods) {
            if(!$this->checkColumn($goods)) {
                $has_empty_column = true;
                $goods['error_info'] = $this->failure_reason;
            }
            if($goods['warehouse'] != $this->warehouse) {
                $has_different_warehouse = true;
            }
            if($goods['arrival_date_actual'] != $this->date) {
                $has_different_date = true;
            }
        }
        if(!$has_empty_column) {
            if($has_different_warehouse) {
                foreach ($order_goods as &$goods) {
                    $goods['error_info'] = self::$warehouse_different_reason;
                }
            }elseif($has_different_date) {
                foreach ($order_goods as &$goods) {
                    $goods['error_info'] = self::$date_different_reason;
                }
            }
        }
        $this->res_goods = $order_goods;
        if($has_empty_column || $has_different_date || $has_different_warehouse) {
            return false;
        }else {
            return true;
        }
    }

    public function checkColumn($data) {
        $this->failure_reason       = '';
        $has_empty                  = false;
        foreach ($data as $k => $v) {
            if(!$v) {
                $this->failure_reason = self::$info_empty_reason[$k];
                $has_empty = true;
                break;
            }
        }
        if($has_empty) {
            return false;
        }else {
            return true;
        }
    }

    public function excelWarehouseEachOrder($procurement_number,$order_goods) {
        $this->order        = M('order_detail','tb_pur_')->where(['procurement_number'=>$procurement_number])->find();
        $this->relevance    = M('relevance_order','tb_pur_')->where(['order_id'=>$this->order['order_id']])->find();
        $ships              = M('ship','tb_pur_')->where(['relevance_id'=>$this->relevance['relevance_id'],'warehouse_status'=>0])->select();
        /*
        if(!$this->order) {
            $this->failure_reason = self::$no_order_reason;
            return false;
        }
        if(!$ships) {
            $this->failure_reason = self::$no_ship_reason;
            return false;
        }
        */
        $goods_numbers = [];
        foreach ($order_goods as $goods) {
            if($goods_numbers[$goods['sku_id']]) {
                $goods_numbers[$goods['sku_id']] += $goods['number'];
            }else {
                $goods_numbers[$goods['sku_id']] = $goods['number'];
            }
        }
        $out_of_number = false;
        $succeed_number = [];
        foreach ($ships as $k => $v) {
            $this->ship         = $v;
            $this->ship_goods   = M('ship_goods','tb_pur_')
                ->field('t.*,a.sku_information')
                ->alias('t')
                ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                ->where(['ship_id'=>$v['id']])
                ->select();
            $success_number     = $succeed_number;
            foreach ($this->ship_goods as $ship_goods) {
                $success_number[$ship_goods['sku_information']] = (isset($success_number[$ship_goods['sku_information']])?$success_number[$ship_goods['sku_information']]:0)+$ship_goods['ship_number'];
                if(!($goods_numbers[$ship_goods['sku_information']] && ($goods_numbers[$ship_goods['sku_information']] >= $success_number[$ship_goods['sku_information']]))) {
                    $out_of_number = true;
                }
            }
            if(!$out_of_number) {
                if($res = $this->warehouseBrief()) {
                    $this->has_success = true;
                    $succeed_number = $success_number;
                }else {
                    break;
                }
            }else {
                break;
            }
        }
        foreach ($order_goods as &$goods) {
            isset($succeed_number[$goods['sku_id']])?:$succeed_number[$goods['sku_id']]=0;
            $failure_number = $goods_numbers[$goods['sku_id']] - $succeed_number[$goods['sku_id']];
            if($failure_number == 0) {
                $goods['error_info'] = "成功";
            }else {
                $this->has_failure = true;
                $goods['error_info'] = "成功{$succeed_number[$goods['sku_id']]}个，失败{$failure_number}个";
            }
        }
        $this->res_goods = $order_goods;
    }

    public function warehouseBrief() {
        $warehouse['id']                        = $this->ship['id'];
        $warehouse['sale_no_correct']           = $this->ship['sale_no'];
        $warehouse['need_warehousing_correct']  = 1;
        $warehouse['warehouse_correct']         = $this->warehouse;
        $warehouse['arrival_date_actual']       = $this->date;
        foreach ($this->ship_goods as $v) {
            $goods_w['warehouse_number']        = $v['ship_number'];
            $goods_w['number_info_warehouse']   = $v['number_info_ship'];
            $warehouse['goods'][$v['id']]       = $goods_w;
            $warehouse['warehouse_number']      += $v['ship_number'];
        }
        M()->startTrans();
        if($res = $this->warehouse($warehouse)) {
            M()->commit();
            return true;
        }else {
            M()->rollback();
            return false;
        }
    }

    /**
     * 根据发货id入库
     * @param $id
     * @return bool
     */
    public function warehouseVirtual($id) {
        $warehouse['ship_id']                   = $id;
        $warehouse['warehouse_number_broken']   = 0;
        $goods                  = M('ship_goods','tb_pur_')->where(['ship_id'=>$id])->select();
        foreach ($goods as $v) {
            $goods_w['warehouse_number']            = $v['ship_number'];
            $goods_w['warehouse_number_broken']     = 0;
            $goods_w['number_info_warehouse']       = $v['number_info_ship'];
            $warehouse['goods'][$v['id']]           = $goods_w;
            $warehouse['warehouse_number']          += $v['ship_number'];
        }
        if(!$this->warehouse($warehouse)) {
            return false;
        }
        return true;
    }

    /**
     * 入库
     * @param $warehouse_save
     * @return bool
     * 迁移到purchaseLogic,防止未知调用，作为跳板
     */
    public function warehouse($warehouse_save) {
        $logic = D('Purchase/Warehouse','Logic');
        $res = $logic->warehouse($warehouse_save);
        if($res === false)
            $this->error = $logic->getError();
        return $res;
    }

    /**
     * 计算单个商品入库成本，公式(E*B/F)+{（E*B/G）*（D*G/C+H）}/F简化为E*B/F*(1+D/C+H/G), 注：公式来源于产品提供的入库成本均摊表格
     */
    public function allWarehouseCost($ship_id,$warehouse) {
        if($warehouse['warehouse_extra_cost'] && $warehouse['warehouse_extra_cost_currency']) {
            $warehouse_cost_currency = M('cmn_cd','tb_ms_')->where(['CD'=>$warehouse['warehouse_extra_cost_currency']])->getField('CD_VAL');
            $tax_rate = exchangeRate($warehouse_cost_currency,$warehouse['arrival_date_actual']);
            if(!$tax_rate) {
                $this->error = '获取入库额外费用汇率失败';
            }
            $cost_warehouse_rmb = $warehouse['warehouse_extra_cost']*$tax_rate;
        }else {
            $cost_warehouse_rmb = 0;
        }
        $ship = $this
            ->field('t.*,a.CD_VAL currency')
            ->alias('t')
            ->where(['id'=>$ship_id])
            ->join('left join tb_ms_cmn_cd a on a.CD = t.extra_cost_currency')
            ->find();
        if(!$ship) {
            $this->error = '发货信息不存在';
            return false;
        }
        //计算发货费用
        $cost_ship                  = $ship['extra_cost'];
        $cost_currency_ship         = $ship['currency'];
        if($ship['shipment_date'] == '' || $ship['shipment_date'] == '0000-00-00') {
            $ship_date = date('Y-m-d');
        }else {
            $ship_date = $ship['shipment_date'];
        }
        if($cost_ship && $cost_currency_ship) {
            $tax_rate_ship = exchangeRate($cost_currency_ship,$ship_date);
            if(!$tax_rate_ship) {
                $this->error = '获取发货汇率失败';
                return false;
            }
            $cost_ship_rmb = $cost_ship*$tax_rate_ship;
        }else {
            $cost_ship_rmb = 0;
        }
        //计算入库费用
        $relevance_id = $ship['relevance_id'];
        $order = M('order_detail','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id = t.order_id')
            ->where(['relevance_id'=>$relevance_id])
            ->find();
        //所有费用总和
        $goods_all      = M('goods_information','tb_pur_')->where(['relevance_id'=>$relevance_id])->getField('information_id,unit_price',true);
        $goods_ship     = M('ship_goods','tb_pur_')->where(['ship_id'=>$ship_id])->select();
        //计算发货商品价值总金额
        $amount_ship_rmb    = 0;
        foreach ($goods_ship as $k => $v) {
            $goods_ship[$k]['price_rmb'] = $goods_all[$v['information_id']]*$order['amount_currency_rate'];
            $goods_ship[$k]['money'] = $money = $goods_ship[$k]['price_rmb']*$v['ship_number'];
            $amount_ship_rmb += $money;
        }
        $cost_amount_proportion_order   = $order['logistics_rmb']/($order['amount_rmb']-$order['logistics_rmb']);
        $cost_amount_proportion_ship    = ($cost_ship_rmb+$cost_warehouse_rmb)/$amount_ship_rmb;
        $proportion_all                 = 1+$cost_amount_proportion_order+$cost_amount_proportion_ship;
        foreach ($goods_ship as $k => $v) {
            $goods_ship[$k]['warehouse_cost'] = round($proportion_all*$v['price_rmb']*$v['ship_number']/$warehouse['goods'][$v['id']]['warehouse_number'],2);
        }
        return $goods_ship;
    }

    public function updatePart($update_data) {
        if(!$this->updatePartValidate($update_data)) {
            $this->error = '参数异常';
            return false;
        }
        $order_info = (new TbPurRelevanceOrderModel())->where(['relevance_id'=>$update_data['relevance_id']])->find();
        if(!$order_info) {
            $this->error = '采购订单不存在';
            return false;
        }
        //是否有操作应付
        $payment = M('payment','tb_pur_')->where(['relevance_id'=>$update_data['relevance_id']])->select();
        $has_payment = false;
        foreach ($payment as $v) {
            if($v['status'] > 0) {
                $has_payment = true;
                break;
            }
        }
        //是否有操作发票
        $payment = M('invoice','tb_pur_')->where(['relevance_id'=>$update_data['relevance_id']])->select();
        if($payment) {
            $has_invoice = true;
        }else {
            $has_invoice = false;
        }
        if($order_info['ship_status'] || $has_payment || $has_invoice) {
            $this->error = '订单已经有发货、付款、发票等操作信息，不能修改或重置';
            return false;
        }
        M()->startTrans();
        $res_order  = (new TbPurOrderDetailModel()) ->where(['order_id'=>$order_info['order_id']])->save($update_data);
        if($res_order === false) {
            $this->error = '保存采购信息失败';
            M()->rollback();
            return false;
        }
        $res_sell   = (new TbPurSellInformationModel()) ->where(['sell_id'=>$order_info['sell_id']])->save($update_data);
        if($res_sell === false) {
            $this->error = '保存销售信息失败';
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    public function updatePartValidate($update_data) {
        foreach ($update_data as $k => $v) {
            if(!in_array($k,self::$update_part_columns) && $k != 'relevance_id') {
                return false;
            }
            return true;
        }
    }

    public function getError() {
        return $this->error;
    }

    public function resetToDraft($relevance_id) {
        $model = (new TbPurRelevanceOrderModel());
        $order_info = $model->where(['relevance_id'=>$relevance_id])->find();
        if(!$order_info) {
            $this->error = '采购订单不存在';
            return false;
        }
        //是否有操作应付
        $payment = M('payment','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        $has_payment = false;
        foreach ($payment as $v) {
            if($v['status'] > 0) {
                $has_payment = true;
                break;
            }
        }
        //是否有操作发票
        $payment = M('invoice','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        if($payment) {
            $has_invoice = true;
        }else {
            $has_invoice = false;
        }
        if($order_info['ship_status'] || $has_payment || $has_invoice) {
            $this->error = '订单已经有发货、付款、发票等操作信息，不能修改或重置';
            return false;
        }
        M()->startTrans();
        $res_payment = M('payment','tb_pur_')->where(['relevance_id'=>$relevance_id])->delete();
        if($res_payment === false) {
            M()->rollback();
            $this->error = '删除应付数据失败';
            return false;
        }
        $res_approve = M('approve','tb_pur_')->where(['relevance_id'=>$relevance_id])->save(['status'=>1]);
        if($res_approve === false) {
            M()->rollback();
            $this->error = '审批设置失败';
            return false;
        }
        $res_relevance = $model->where(['relevance_id'=>$relevance_id])->save(['order_status'=>'N001320100']);
        if($res_relevance === false) {
            M()->rollback();
            $this->error = '保存订单状态失败';
            return false;
        }
        M()->commit();
        return true;
    }

    //是否可退回
    public function canReturn($relevance_id) {
        $has_paid               = (new TbPurPaymentModel())->hasPaid($relevance_id);
        $has_shipped            = (new TbPurShipModel())->hasShipment($relevance_id);
        $has_confirmed_invoice  = (new TbPurInvoiceModel())->hasConfirmedInvoice($relevance_id);
        if($has_paid || $has_shipped || $has_confirmed_invoice) {
            return false;
        }else {
            return true;
        }
    }

    public function returnToDraft($relevance_id) {
        $relevance_m        = new TbPurRelevanceOrderModel();
        $order_m            = new TbPurOrderDetailModel();
        $this->relevance    = $relevance_m->where(['relevance_id'=>$relevance_id])->find();
        $this->order        = $order_m->where(['order_id'=>$this->relevance['order_id']])->find();
        if(!$this->canReturn($relevance_id)) {
            $this->error = L('此订单有已付款/已发货/或者确认了发票信息的情况，无法退回');
            return false;
        }
        M()->startTrans();
        $res_invoice    = (new TbPurInvoiceModel())->deleteOrderInvoice($relevance_id);
        if($res_invoice === false) {
            $this->error = '删除发票失败';
            M()->rollback();
            return false;
        }
        $res_payment    = (new TbPurPaymentModel())->deleteOrderPayment($relevance_id);
        if($res_payment === false) {
            $this->error = '删除应付失败';
            M()->rollback();
            return false;
        }
        $res_status = $relevance_m->where(['relevance_id'=>$relevance_id])->save(['order_status'=>$relevance_m::$status_draft,'has_invoice_unconfirmed'=>0]);
        if(!$res_status) {
            $this->error = '保存订单状态失败';
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    public function scmOrderAdd($add_data, $quotation_id) {
        $order_detail = M('order_detail','tb_pur_'); //实例化采购信息表
        if($add_data['contract_number']) {
            $add_data['has_contract']                   = 1;
            $contract                                   = M('contract','tb_crm_')->where(['CON_NO'=>$add_data['contract_number']])->find();
            $add_data['supplier_opening_bank']          = $contract['SP_BANK_CD'];
            $add_data['supplier_collection_account']    = $contract['collection_account_name'];
            $add_data['supplier_card_number']           = $contract['BANK_ACCOUNT'];
            $add_data['supplier_swift_code']            = $contract['SWIFT_CODE'];
        }else {
            $add_data['has_contract'] = 0;
        }
        if($add_data['procurement_number']) {
            if($order_detail->where(['procurement_number'=>$add_data['procurement_number']])->find()) {
                $this->error = L('PO单号或采购单号') . $add_data['procurement_number'] .L( '已经创建过订单，请勿重复创建');
                return false;
            }
        }else {
            $this->error(L('请填写PO单号或采购单号'));
        }

//        if($add_data['payment_info']) {
//            $add_data['payment_info'] = json_encode($add_data['payment_info']);
//        }else {
//            $purchase_info['payment_info'] = '';
//        }

        // 供应商（自己填的，一般没有营业执照号）判断是否存在，如果没有，需要新增一条记录
        //1.线上采购订单创建时，如果产生了新名字的供应商，即记录在供应商列表新增一条数据。
        //2.新增的此类供应商，记录创建时间=供应商记录生成的时间(生成抵扣金时创建供应商的时间)，创建人=采购订单的采购同事。(admin.id)
        
        $spSupplier_id = $add_data['supplier_new_id'];
        if (!$spSupplier_id) { // 表明报价单中没有该供应商
            $crmSpSupplierModel = M('crm_sp_supplier','tb_');
            if ($add_data['supplier_id']) {
                $spSupplier_id = $crmSpSupplierModel->where(['SP_NAME' => $add_data['supplier_id']])->getField('ID');
            } else {
                $this->error = '该报价单缺少填写供应商名称';
                return false;
            }
            if (!$spSupplier_id) { // 表明是新的供应商，需要新增
                $addSupplierData = [];
                $adminModel = M('admin', 'bbm_');
                $search_name = 'huaming';
                if (strpos($add_data['prepared_by'], '.') !== false) { // 部分采购单是以英文名如Weslis.Li存进，而不是花名
                    $search_name = 'M_NAME';
                }
                $addSupplierData['COMPANY_MARKET_INFO']         = '';
                $addSupplierData['UPDATE_USER_ID']              = 0;
                $addSupplierData['SP_ADDR1']                    = '';
                $addSupplierData['SP_CHARTER_NO']               = '';
                $addSupplierData['DEL_FLAG']                    = '1';
                $addSupplierData['SP_STATUS']                   = '1';
                $addSupplierData['AUDIT_STATE']                 = '3';
                $addSupplierData['SP_CHARTER_NO_TYPE']          = '1';
                $addSupplierData['COPANY_TYPE_CD']              = 'N001190800';
                $addSupplierData['SP_NAME']                     = $add_data['supplier_id'];
                $addSupplierData['CREATE_TIME']                 = date("Y-m-d H:i:s",time());
                $addSupplierData['UPDATE_TIME']                 = $addSupplierData['CREATE_TIME'];
                $addSupplierData['CREATE_USER_ID']              = $adminModel->where([$search_name => $add_data['prepared_by']])->getField('M_ID');
                $spSupplier_id = $crmSpSupplierModel->add($addSupplierData);
                if (false === $spSupplier_id) {
                    $this->error = '供应商'.$add_data['supplier_id'].'新增失败,保存失败';
                    return false;
                }
            }
        }
        

        $add_data['supplier_new_id'] = $spSupplier_id;
        $add_data['create_time']            = date("Y-m-d H:i:s",time());
        $add_data['supplier_invoice_title'] = $add_data['supplier_id'];;
        $model = new Model();
        $model->startTrans();
        $order_id                   = M('order_detail','tb_pur_')->add($add_data); //采购信息
        $drawback_id                = M('drawback_information','tb_pur_')->add($add_data); //退税信息
        $sell_id                    = M('sell_information','tb_pur_')->add($add_data); //退税信息
        //下面将上面的每张表汇总到关联的订单表中
        $prepared_by                = $add_data['prepared_by'];
        $prepared_time              = date("Y-m-d H:i:s",time());
        $add_data['prepared_time']  = $prepared_time; //加入制单时间
        $creation_time              = date("Y-m-d H:i:s",time()); //该时间是向订单关联总表中插入使用的，是为了方便搜索使用
        $creation_times             = date("Y-m-d H:i:s",time()); //该时间是向采购应付表中插入使用的，是为了方便搜索使用
        $sou_time                   = date("Y-m-d",time()); //搜索的时间，时间与制单时间保持一致，格式为年月日
        $add_data['sou_time']       = $sou_time;
        $add_data['creation_times'] = $creation_times;
        $number_total               = $add_data['number_total'];//接收商品数量的合计
        $money_total                = $add_data['money_total'];//接收商品金额的合计
        $money_total_rmb            = $add_data['money_total_rmb'];//接收合计的外币金额换算成人民币的金额
        $real_total_rate            = $add_data['amount_currency_rate']; //用于保存当天的真实汇率
        $collect_order              = compact("order_id","sou_time","drawback_id","sell_id","prepared_by","prepared_time","receipt_number","number_total","money_total","payable_id","creation_time","money_total_rmb","show_total_rate","real_total_rate"); //所有订单ID打包汇总插入关联的订单表中
        $collect_order['last_update_time']  = date('Y-m-d H:i:s');
        $collect_order['last_update_user']  = $_SESSION['m_loginname'];
        $collect_order['prepared_by']       = $add_data['prepared_by'];
        $relevance_id                       = D('TbPurRelevanceOrder')->add($collect_order); //将相关信息向订单关联总表中插入
        foreach ($add_data['goods'] as $k => $v) {
            $add_data['goods'][$k]['relevance_id'] = $relevance_id;
        }
        $goods_add_res = M('goods_information','tb_pur_')->addAll($add_data['goods']);

        if(!($order_id&&$drawback_id&&$relevance_id&&$goods_add_res&&$sell_id)){
            $this->error = '保存失败';
            $model->rollback();
            return false;
        }
        if(!($res_a = $this->scmAutoApprove($relevance_id, $quotation_id))) {
            $this->error = '保存失败';
            $model->rollback();
            return false;
        }

        


        // 更新采购适用条款表tb_pur_clause，tb_pur_relevance_order.relevance_id 
        $purClauseSaveInfo['purchase_id'] = $relevance_id;
        $clauseInfo = M('clause', 'tb_pur_')->where(['quotation_id' => $quotation_id])->find();
        if ($clauseInfo) { // 为了兼容旧的报价单，只有新的适用条款才需要更新
            $purClaRes = M('clause', 'tb_pur_')->where(['quotation_id' => $quotation_id])->save($purClauseSaveInfo);
            if (!$purClaRes) {
                $this->error = '更新采购适用条款表关联采购id,保存失败';
                $model->rollback();
                return false;
            }
        }


        $bill_goods = [];
        foreach ($add_data['goods'] as $v) {
            $goods                      = [];
            $goods['skuId']             = $v['sku_information'];
            $goods['price']             = $v['unit_price'];
            $goods['currencyId']        = $add_data['amount_currency'];
            $goods['currencyTime']      = date('Y-m-d H:i:s');
            $goods['num']               = $v['goods_number'];
            $goods['purInvoiceTaxRate'] = $add_data['tax_rate'] ? '0.'.substr(cdVal($add_data['tax_rate']),0,-1) : 0;
            $goods['proportionOfTax']   = substr(cdVal($v['drawback_percent']),0,-1)/100;
            $goods['storageLogCost']    = 0;
            $goods['logServiceCost']    = 0;
            $goods['poCurrency']        = $add_data['amount_currency'];
            $goods['poCost']            = $v['unit_expense'];
            $goods['purStorageDate']    = date('Y-m-d H:i:s');
            $bill_goods[]               = $goods;
        }

        //在途为调用接口，无法被事务回滚，放到最后处理
        $bill_data      = [
            'bill' => [
                'billType'          => 'N000940100',//收发类型，采购入库为N000940100固定不变
                'relationType'      => 'N002350200',//业务单据的类型,采购单N002350200
                'procurementNumber' => $add_data['procurement_number'], //b5c单号
                'linkBillId'        => $add_data['procurement_number'], //b5c单号
                'warehouseRule'     => 1, //是否入我方仓库
//                'batch'           => $ship_info['bill_of_landing'],//批次，这个待定
                'saleNo'            => $add_data['sale_no'],// 数据库无对应字段
                'channel'           => 'N000830100',// 默认
                'supplier'          => $add_data['supplier_id'],// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                'warehouseId'       => $add_data['warehouse'],// 仓库id（码表或数据字典对应的值）
                'saleTeam'          => $add_data['sell_team'],//销售团队
                'spTeamCd'          => $add_data['payment_company'],//采购团队
                'conCompanyCd'      => $add_data['our_company'],//我方公司
                'virType'           => 'N002440200',//	入库类型 现货入库(N002440100)、在途入库(N002440200)
                'operatorId'        => $_SESSION['userId'],//操作人id
            ],
            'guds' => $bill_goods
        ];
        $demand_code = substr($add_data['procurement_number'],0 ,-4);
        if(D('Scm/Demand')->isSell($demand_code)) {
            $bill_data['bill']['type']          = 3;
            $bill_data['bill']['processOnWay']  = 1;
            $bill_data['bill']['orderId']       = $demand_code;
        }
        $res_j  = ApiModel::warehouse($bill_data);
        $res    = json_decode($res_j,true);
        if($res['code'] != 2000) {
            if ($this->checkHasPutRecord($bill_data)) {
                ELog::add(['msg'=>'调用在途接口失败，已生成在途数据','request'=>$bill_data,'response'=>$res_j],ELog::INFO);
            }else{
                ELog::add(['msg'=>'调用在途接口失败','request'=>$bill_data,'response'=>$res_j],ELog::ERR);
                M()->rollback();
                $this->error = $res['msg'];
                return false;
            }
        }else {
            ELog::add(['msg'=>'调用在途接口成功','request'=>$bill_data,'response'=>$res_j],ELog::INFO);
        }
        (new TbPurActionLogModel())->addLog($relevance_id);

        $model->commit();
        return $relevance_id;
    }

    public function checkHasPutRecord($bill_data)
    {
        $where_string = ' 1 != 1';
        foreach ($bill_data['guds'] as  $value) {
            $where_string .= " OR (purchase_order_no = '{$bill_data['bill']['procurementNumber']}' AND SKU_ID = {$value['skuId']})";
        }
        $Model = M();
        $db_count = $Model->table('tb_wms_batch')
            ->where($where_string, null, true)
            ->count();
        if ($db_count == count($bill_data['guds'])) {
            return true;
        }
        return false;
    }

    public function scmAutoApprove($relevance_id, $quotation_id) {
        $goods = M('goods_information','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        $order = M('order_detail','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')
            ->where(['relevance_id'=>$relevance_id])
            ->find();
        $model = new Model();
        $model->startTrans();
        $relevance_res                  = D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save(['order_status'=>'N001320300']);
        if($relevance_res) {
            (new TbPurActionLogModel())->addLog($relevance_id);
            $clauseInfo = (new Model())->table('tb_pur_clause')->where(['quotation_id' => $quotation_id])->getField('id'); // 根据报价单id（仅限创建采购单时用）或采购单id获取条款信息，有信息记录，走新流程，没有，则走旧流程
            if ($clauseInfo) {

                $addData['class'] = __CLASS__;
                $addData['function'] = __FUNCTION__;
                $addData['quotation_id'] = $quotation_id;
                $addData['amount'] = $order['amount'];
                $res = D('Scm/PurOperation')->DealTriggerOperation($addData, '1', 'N002870001', $order['relevance_id']);
                if (!$res) {
                    $this->error = '生成应付数据失败';
                    $model->rollback();
                }
            } else { //兼容旧流程的付款周期以及付款时间

                $payment_m = new TbPurPaymentModel();
                if($order['payment_type'] == 0) { 
                    if($order['payment_period']) {
                        $payment_info = json_decode($order['payment_info'],true);
                        foreach($payment_info as $k => $v) {
                            $payable['relevance_id']    = $order['relevance_id'];
                            $payable['payable_date']    = $v['payment_date'];
                            $payable['amount']          = $order['amount'];
                            $payable['amount_payable']  = $payable['amount']*$v['payment_percent']/100;
                            $payable['payment_period']  = "第{$k}期-{$v['payment_percent']}%";
                            $payable['payment_no']      = $payment_m->createPaymentNO();
                            $payable['update_time']     = date("Y-m-d H:i:s");
                            $res                        = $payment_m->add($payable);
                            if(!$res) {
                                ELog::add('生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
                            }
                        }
                    }
                }else {
                    $payment_info = json_decode($order['payment_info'],true);
                    foreach ($payment_info as $k => $v) {
                        if($v['payment_node'] == 'N001390100') {
                            $payable['relevance_id']   = $order['relevance_id'];
                            $payable['payable_date']   = date('Y-m-d',strtotime($order['prepared_time'])+$v['payment_days']*24*3600);
                            $payable['amount']         = $order['amount'];
                            $payable['amount_payable'] = $payable['amount']*$v['payment_percent']/100;
                            $payable['payment_period'] = "第{$k}期-"
                                .cdVal($v['payment_node'])
                                .$v['payment_days']
                                .TbPurOrderDetailModel::$payment_day_type[$v['payment_day_type']]
                                .$v['payment_percent'].'%';
                            $payable['payment_no']     = $payment_m->createPaymentNO();
                            $payable['update_time']    = date("Y-m-d H:i:s");
                            $res = $payment_m->add($payable);
                            if(!$res) {
                                $this->error = '应付保存失败';
                                ELog::add('生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
                            }
                        }
                    }
                }
            }
            /*
            //增加商品在途
            foreach ($goods as $v) {
                $on_way['SKU_ID']       = $v['sku_information'];
                $on_way['TYPE']         = 0;
                $on_way['on_way']       = $v['goods_number'];
                $on_way['on_way_money'] = round($v['goods_number']*$v['unit_price']*$order['amount_currency_rate'],2);
                $on_way_all[]           = $on_way;
            }
            $res_arr = (new TbWmsStandingModel())->onWayAndOnWayMoney($on_way_all);
            if($res_arr['code'] != 10000111) {
                $this->error = '增加商品在途失败';
                $model->commit();
                ELog::add(['msg'=>'增加在途数据失败','request'=>$on_way_all,'res'=>$res_arr],ELog::ERR);
                return false;
            }
            */
        }else {
            $this->error = '订单置为审批通过失败';
            $model->rollback();
        }
        if($this->error) return false;
        $model->commit();
        return true;
    }

    public function b2bShip($b2b_ship_data) {
        if(!$this->b2bShipCheck($b2b_ship_data)) {
            return false;
        }
        $res = A('Home/B2b')->scmSendOut($b2b_ship_data);
        if($res['code'] != 200) {
            ELog::add(['info'=>'B2B虚拟仓发货失败','request'=>$b2b_ship_data,'response'=>$res]);
            $this->error = 'B2B发货失败,'.$res['msg'];
            return false;
        }
        return true;
    }

    public function b2bShipCheck($b2b_ship_data) {
        if(!D('Scm/Demand')->isSell($b2b_ship_data['order_info']['po_id'])) {
            $this->error = '无关联需求或需求类型不为销售订单';
            return false;
        }
        $cookie = '';
        foreach ($_COOKIE as $k => $v) {
            $cookie .= $k.'='.$v.';';
        }
        $url                = U('b2b/checkResidualSend','',true,false,true);
        //$url                = 'http://erp.gshopper.stage.com/index.php?m=b2b&a=checkResidualSend';
        $residual_request   = ['po_id'=>$b2b_ship_data['order_info']['po_id']];
        $res_residual       = curl_get_json($url,json_encode($residual_request),$cookie);
        $residual_send      = json_decode($res_residual,true);
        if($residual_send['code'] != 200000) {
            $this->error = 'B2B待发货数量查询失败';
            ELog::add(['info'=>'B2B未发货数查询失败，'.$residual_send['msg'],'request'=>$residual_request,'response'=>$residual_send],ELog::ERR);
            return false;
        }
        $residual = array_column($residual_send['data'],null,'SKU_ID');
        foreach ($b2b_ship_data['goods'] as $v) {
            if($v['delivered_num'] > $residual[$v['sku_id']]['ALL_TOBE_DELIVERED_NUM']) {
                $this->error = $v['sku_id'].'发给客户数量大于对应B2B订单未发货数';
                return false;
                break;
            }
        }
        if($this->getError()) return false;
        return true;
    }
}