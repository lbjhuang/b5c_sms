<?php
/**
 * User: yuanshixiao
 * Date: 2019/3/12
 * Time: 18:15
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ExportOrderLogic extends BaseLogic
{

    protected $sheet                        = ['订单汇总信息','付款信息','发货信息','入库信息', '收发票信息', '退款信息', '退货信息'];
    protected $sheet_en                     = ['order','payment','ship','warehouse', 'invoice', 'refund', 'return'];
    protected $cell_order                   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN'];
    protected $cell_name_order              = ['采购单号','采购PO单号','订单币种','采购类型','供应商','我方公司','采购合同','PO金额（订单币种）','销售团队','采购团队','采购同事','发票类型','发票税率','交货方式','付款账期','预计到货日期','采购网站','下单账号','PO内费用（订单币种）','应付状态','发货状态','入库状态','开票状态','退货状态','退款状态','备注','累计付款金额（订单币种）','累计退款金额（订单币种）','累计发货金额（订单币种）','累计入库金额（订单币种）','累计退货金额（订单币种）','累计收发票金额（订单币种）','累计发票冲销金额（订单币种）','综合付款金额（订单币种）','综合入库金额（订单币种）','综合收发票金额（订单币种）','在途库存金额（订单币种）','在途发票金额（订单币种）','创建人','创建时间'];
    protected $cell_value_key_order         = ['procurement_number','online_purchase_order_number','amount_currency','purchase_type','supplier_id','our_company','contract_number','amount','sell_team','payment_company','prepared_by','invoice_type','tax_rate','delivery_type','payment_type','arrival_date','online_purchase_website','online_purchase_account','expense','payment_status','ship_status','warehouse_status','invoice_status','has_return_goods','has_refund','remark','money_paid','money_refund','money_ship','money_warehouse','money_return','money_invoice','money_invoice_write_off','money_paid_over_all','money_warehouse_over_all','money_invoice_over_all','money_on_way','money_invoice_on_way','prepared_by','prepared_time'];
    protected $cell_payment                 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA','AB'];
    protected $cell_name_payment            = ['应付单号','采购单号','采购PO单号','订单币种','应付状态','付款账期','本期节点','付款账户名','付款银行/平台名称','付款账号','付款账号币种','收款账户名','收款银行/平台名称','收款银行SWIFT CODE','收款银行账号','预计付款日期','应付确认人','应付确认时间','确认前-本期应付金额（订单币种）','确认后-本期应付金额（订单币种）','付款确认人','付款确认时间','出账确认人','出账确认时间','实际出账日期','扣款金额（付款账号币种）','手续费（付款账号币种）','备注'];
    protected $cell_value_key_payment       = ['payment_no','procurement_number','online_purchase_order_number','amount_currency','status','payment_type','payment_period','our_company','open_bank','payment_our_bank_account','payment_currency_cd','supplier_account','supplier_bank','supplier_code','supplier_number','payable_date_after','confirm_user','confirm_time','amount_payable','amount_confirm','payment_by','payment_at','billing_by','billing_at','billing_date','amount_account','expense','confirm_remark'];
    protected $cell_ship                    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];
    protected $cell_name_ship               = ['发货编号','采购单号','采购PO单号','订单币种','提单号','发货确认人','发货确认时间','发货数量','累计发货金额（订单币种）','发往仓库','发货时间','预计入库时间','发货备注','发货单入库状态','已入库合格品数量','已入库残次品数量','已入库总数量','已入库库存金额（订单币种）'];
    protected $cell_value_key_ship          = ['warehouse_id','procurement_number','online_purchase_order_number','amount_currency','bill_of_landing','create_user','create_time','shipping_number','ship_amount','warehouse','shipment_date','arrival_date','remark','warehouse_status','warehouse_number','warehouse_number_broken','warehouse_number_total','warehouse_amount'];
    protected $cell_warehouse               = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
    protected $cell_name_warehouse          = ['入库单号','发货单号','采购单号','采购PO单号','订单币种','本次入库合格品数量','本次入库残次品数量','本次入库总数量','本次入库金额（订单币种）','PO之外的物流费用（预估）币种','PO之外的物流费用（预估）金额','PO之外的服务费用（预估）币种','PO之外的服务费用（预估）金额'];
    protected $cell_value_key_warehouse     = ['warehouse_code','warehouse_id','procurement_number','online_purchase_order_number','amount_currency','warehouse_number','warehouse_number_broken','warehouse_number_total','warehouse_amount','log_currency','storage_log_cost','service_currency','log_service_cost'];
    protected $cell_invoice                 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N','o'];
    protected $cell_name_invoice            = ['操作编号','采购单号','采购PO单号','订单币种','发票状态','发票号','发票抬头','发票类型','税点','发票总金额（订单币种）','备注','提交人','提交时间','财务确认人','财务确认时间'];
    protected $cell_value_key_invoice       = ['action_no','procurement_number','online_purchase_order_number','amount_currency','status','invoice_no','invoice_title','invoice_type','tax_rate','invoice_money','remark','create_user','create_time','confirm_user','confirm_time'];
    protected $cell_refund                  = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    protected $cell_name_refund             = ['流水ID','采购单号','采购PO单号','订单币种','本次认领退款金额（订单币种）','认领人','认领时间'];
    protected $cell_value_key_refund        = ['account_transfer_no','procurement_number','online_purchase_order_number','amount_currency','claim_amount','created_by','created_at'];
    protected $cell_return                  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];
    protected $cell_name_return             = ['采购退货单号','采购单号','采购PO单号','订单币种','仓库','退货状态','退货数量','退货金额','收货人','收货人联系电话','收货地址','物流单号','预计到达日期','有无差异','是否需要我方赔偿（承担）差异','赔偿（承担）金额（订单币种）','理货说明','退货发起人','退货发起时间','退货出库确认人','退货出库确认时间','退货理货确认人','退货理货确认时间'];
    protected $cell_value_key_return        = ['return_no','procurement_number','online_purchase_order_number','amount_currency','warehouse','status','return_number','return_amount','receiver','receiver_contact_number','receive_address','logistics_number','estimate_arrive_date','has_difference','need_bear_difference','compensation','tally_remark','created_by','created_at','out_of_stock_user','out_of_stock_time','tally_by','tally_at'];

    public function exportOrder($where) {
        set_time_limit(0);
        vendor('PHPExcel.PHPExcel');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array( 'memoryCacheSize' => '512MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);
        $fileName = '采购订单'.time().'.xls';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $objExcel = new PHPExcel();

        foreach ($this->sheet as $k => $v) {
            if($k != 0)
                $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($k)->setTitle($v);
        }

        foreach ($this->sheet_en as $k => $v) {
            $cell_name_key = 'cell_name_'.$v;
            foreach ($this->$cell_name_key as $key => $val) {
                $objExcel->setActiveSheetIndex($k)->setCellValue($this->cell_order[$key] . '1', $val);
            }
        }

        $current_id             = 0;
        $current_row_order      = 0;
        $current_row_payment    = 0;
        $current_row_ship       = 0;
        $current_row_warehouse  = 0;
        $current_row_invoice    = 0;
        $current_row_refund     = 0;
        $current_row_return     = 0;

        while (true) {
            //采购单
            $current_row_order++;
            $where['t.relevance_id'] = ['gt', $current_id];
            $order = $this->getOrder($where);
            if(!$order) break;
            $current_id = $order['relevance_id'];
            $goods      = D('TbPurGoodsInformation')
                ->alias('t')
                ->field('t.*,sum(b.warehouse_number) warehouse_number,sum(b.warehouse_number_broken) warehouse_number_broken')
                ->where(["relevance_id"=>$order['relevance_id']])
                ->join('tb_pur_ship_goods b on b.information_id=t.information_id')
                ->where(['relevance_id'=>$order['relevance_id']])
                ->group('t.information_id')
                ->select();
            foreach ($goods as $k => $v) {
                $goods[$k]['return_number']             = D('Purchase/Return','Logic')->getPurchaseGoodsReturnNum($v['information_id']);
                $goods[$k]['return_out_store_number']   = D('Purchase/Return','Logic')->getPurchaseGoodsReturnOutStoreNum($v['information_id']);
            }
            //付款
            $payment    = D('TbPurPayment')
                ->alias('t')
                ->field('t.*,a.open_bank,pa.payment_at,pa.payment_by,pa.billing_at,pa.billing_by,
                    pa.billing_date,pa.payment_our_bank_account,pa.payment_currency_cd,pa.payable_date_after,pa.supplier_opening_bank as supplier_bank,pa.supplier_collection_account as supplier_account,pa.supplier_card_number as supplier_number,pa.supplier_swift_code as supplier_code')
                ->join('left join tb_pur_payment_audit pa on t.payment_audit_id = pa.id')
                ->join('left join tb_fin_account_bank a on a.account_bank=pa.payment_our_bank_account')
                ->where(['relevance_id'=>$order['relevance_id']])
                ->group('t.id')
                ->select();
            $ship       = D('TbPurShip')
                ->alias('t')
                ->field('t.*,t.warehouse_number+t.warehouse_number_broken warehouse_number_total,sum(a.ship_number*(b.unit_price+b.unit_expense)) ship_amount,sum((a.warehouse_number+a.warehouse_number_broken)*(b.unit_price+b.unit_expense)) warehouse_amount')
                ->join('tb_pur_ship_goods a on a.ship_id=t.id')
                ->join('tb_pur_goods_information b on b.information_id=a.information_id')
                ->where(['t.relevance_id'=>$order['relevance_id']])
                ->group('t.id')
                ->select();
            $warehouse  = M('warehouse','tb_pur_')
                ->alias('t')
                ->field('t.warehouse_code,b.warehouse_id,t.warehouse_number,t.warehouse_number_broken,t.warehouse_number+t.warehouse_number_broken warehouse_number_total,sum((a.warehouse_number+a.warehouse_number_broken)*(d.unit_price+d.unit_expense)) warehouse_amount,log_currency,storage_log_cost,service_currency,log_service_cost')
                ->join('tb_pur_warehouse_goods a on a.warehouse_id=t.id')
                ->join('tb_pur_ship b on b.id=t.ship_id')
                ->join('tb_pur_ship_goods c on c.id=a.ship_goods_id')
                ->join('tb_pur_goods_information d on d.information_id=c.information_id')
                ->where(['b.relevance_id'=>$order['relevance_id']])
                ->group('t.id')
                ->select();
            $invoice    = D('TbPurInvoice')->where(['relevance_id'=>$order['relevance_id']])->select();
            $refund     = D('TbFinClaim')
                ->field('t.id,a.account_transfer_no,a.currency_code,t.claim_amount,t.created_by,created_at')
                ->alias('t')
                ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
                ->where(['t.order_id'=>$order['order_id'],['t.order_type'=>'N001950600']])
                ->select();
            $return     = $this->getReturn($order['relevance_id']);

            //订单金额计算
            $money_paid = 0;
            foreach ($payment as $v) {
                $money_paid = bcadd($money_paid, bcmul($v['amount_account'],$v['exchange_tax_account'],8), 8);
            }

            $money_refund   = 0;
            foreach ($refund as $v) {
                $return_rate = exchangeRateConversion(cdVal($v['currency_code']), cdVal($order['amount_currency']), str_replace('-','',$order['sou_time']));
                $money_refund = bcadd($money_refund, bcmul($v['claim_amount'], $return_rate, 8), 8);
            }
            $money_paid_over_all = bcsub($money_paid, $money_refund, 8);

            $money_warehouse    = 0;
            $money_ship         = 0;
            $money_return       = 0;
            foreach ($goods as $v) {
                $money_warehouse    = bcadd($money_warehouse, bcmul($v['warehouse_number'] + $v['warehouse_number_broken'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);
                $money_ship         = bcadd($money_ship, bcmul($v['shipped_number'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);
                $money_return       = bcadd($money_return, bcmul($v['return_out_store_number'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);
            }
            $money_warehouse_over_all = bcsub($money_warehouse, $money_return, 8);

            $money_invoice              = 0;
            $money_invoice_write_off    = 0;
            foreach ($invoice as $v) {
                if($v['status'] == 1) {
                    $money_invoice = bcadd($money_invoice, $v['invoice_money'], 8);
                }
            }
            $money_invoice_over_all = bcsub($money_invoice, $money_invoice_write_off, 8);
            $money_on_way           = bcsub($money_paid_over_all, $money_warehouse_over_all, 8);
            $money_invoice_on_way   = bcsub($money_paid_over_all, $money_invoice_over_all, 8);

            $finance = [];
            $finance_keys = [
                'money_paid',
                'money_refund',
                'money_paid_over_all',
                'money_warehouse',
                'money_ship',
                'money_return',
                'money_warehouse_over_all',
                'money_invoice',
                'money_invoice_write_off',
                'money_invoice_over_all',
                'money_on_way',
                'money_invoice_on_way',
            ];
            foreach ($finance_keys as $v) {
                $order[$v] = bcadd($$v,0,2);
            }
            $order      = $this->orderFormat($order);
            $payment    = $this->paymentFormat($payment);
            $ship       = $this->shipFormat($ship);
            $warehouse  = $this->warehouseFormat($warehouse);
            $invoice    = $this->invoiceFormat($invoice);
            $refund     = $this->refundFormat($refund,$order);
            $return     = $this->returnFormat($return);

            //插入表格
            foreach ($this->cell_value_key_order as $k => $v) {
                $objExcel->setActiveSheetIndex(0)->setCellValue($this->cell_order[$k] . ($current_row_order+1), $order[$v]."\t");
            }
            foreach ($payment as $v_payment) {
                $current_row_payment++;
                foreach ($this->cell_value_key_payment as $k => $v) {
                    $objExcel->setActiveSheetIndex(1)->setCellValue($this->cell_payment[$k] . ($current_row_payment+1), (isset($v_payment[$v]) ? $v_payment[$v] : $order[$v])."\t");
                }
            }
            //发货
            foreach ($ship as $v_ship) {
                $current_row_ship++;
                foreach ($this->cell_value_key_ship as $k => $v) {
                    $objExcel->setActiveSheetIndex(2)->setCellValue($this->cell_ship[$k] . ($current_row_ship+1), (isset($v_ship[$v]) ? $v_ship[$v] : $order[$v])."\t");
                }
            }
            //入库
            foreach ($warehouse as $v_warehouse) {
                $current_row_warehouse++;
                foreach ($this->cell_value_key_warehouse as $k => $v) {
                    $objExcel->setActiveSheetIndex(3)->setCellValue($this->cell_warehouse[$k] . ($current_row_warehouse+1), (isset($v_warehouse[$v]) ? $v_warehouse[$v] : $order[$v])."\t");
                }
            }
            //收发票
            foreach ($invoice as $v_invoice) {
                $current_row_invoice++;
                foreach ($this->cell_value_key_invoice as $k => $v) {
                    $objExcel->setActiveSheetIndex(4)->setCellValue($this->cell_invoice[$k] . ($current_row_invoice+1), (isset($v_invoice[$v]) ? $v_invoice[$v] : $order[$v])."\t");
                }
            }
            //退款
            foreach ($refund as $v_refund) {
                $current_row_refund++;
                foreach ($this->cell_value_key_refund as $k => $v) {
                    $objExcel->setActiveSheetIndex(5)->setCellValue($this->cell_refund[$k] . ($current_row_refund+1), (isset($v_refund[$v]) ? $v_refund[$v] : $order[$v])."\t");
                }
            }
            //退货
            foreach ($return as $v_return) {
                $current_row_return++;
                foreach ($this->cell_value_key_return as $k => $v) {
                    $objExcel->setActiveSheetIndex(6)->setCellValue($this->cell_return[$k] . ($current_row_return+1), (isset($v_return[$v]) ? $v_return[$v] : $order[$v])."\t");
                }
            }
        }
        $objExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    protected function exportOrderSheet() {

    }

    protected function getOrder($where) {
        $model = new SlaveModel();
        return $model->table('tb_pur_relevance_order t')
            ->field('
                t.relevance_id,t.order_id,t.prepared_by,t.prepared_time,order_status,ship_status,warehouse_status,payment_status,invoice_status,has_refund,has_return_goods,t.sou_time,
                a.procurement_number,a.online_purchase_order_number,a.business_type,a.supplier_id,a.our_company,a.amount_currency,a.amount,a.purchase_type,a.payment_company,contract_number,a.tax_rate,a.payment_type,a.delivery_type,a.arrival_date,a.invoice_type,a.online_purchase_website,a.online_purchase_account,a.expense,a.order_remark,a.supplier_collection_account,a.supplier_opening_bank,a.supplier_swift_code,a.supplier_card_number,
                b.supp_id,b.sell_team,b.sell_number,sell_money,curr,seller,
                d.total_profit_margin,d.cash_efficiency,
                substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name
            ')
            ->join('tb_pur_order_detail a on a.order_id = t.order_id ')
            ->join('tb_pur_sell_information b on b.sell_id = t.sell_id ')
            ->join('tb_pur_goods_information c on c.relevance_id = t.relevance_id')
            ->join('tb_pur_predict_profit d on d.predict_id = t.predict_id ')
            ->join('tb_wms_batch e on e.purchase_order_no=a.procurement_number')
            ->join('tb_wms_batch_order f on f.batch_id=e.id')
            ->join('tb_b2b_doship g on g.PO_ID=f.ORD_ID')
            ->join(PMS_DATABASE.'.product_sku pa on c.sku_information=pa.sku_id')
            ->join(PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->group('t.relevance_id')
            ->where($where)
            ->find();
    }

    protected function orderFormat($order) {
        if(!$order) {
            return false;
        }
        $ship_status = [
            0 => '待发货',
            1 => '部分发货',
            2 => '发货完成',
        ];
        $warehouse_status = [
            0 => '待入库',
            1 => '部分入库',
            2 => '入库完成',
        ];
        $invoice_status = [
            0 => '待开票',
            1 => '部分开票',
            2 => '开票完成',
        ];
        $payment_status = [
            0 => '待付款',
            1 => '部分付款',
            2 => '付款完成',
        ];
        $order['ship_status']       = $ship_status[$order['ship_status']];
        $order['warehouse_status']  = $warehouse_status[$order['warehouse_status']];
        $order['invoice_status']    = $invoice_status[$order['invoice_status']];
        $order['payment_status']    = $payment_status[$order['payment_status']];
        $order['amount_currency']   = cdVal($order['amount_currency']);
        $order['order_status']      = cdVal($order['order_status']);
        $order['purchase_type']     = cdVal($order['purchase_type']);
        $order['payment_company']   = cdVal($order['payment_company']);
        $order['our_company']       = cdVal($order['our_company']);
        $order['sell_team']         = cdVal($order['sell_team']);
        $order['invoice_type']      = cdVal($order['invoice_type']);
        $order['tax_rate']          = cdVal($order['tax_rate']);
        $order['delivery_type']     = cdVal($order['delivery_type']);
        switch ($order['payment_type']) {
            case 0:
                $order['payment_type']    = '按指定时间付款';
                break;
            case 1:
                $order['payment_type']    = '按实际情况付款';
                break;
            default:
                break;
        }
        $order['online_purchase_website']   = cdVal($order['online_purchase_website']);
        $order['has_refund']                = $order['has_refund'] ? '有退款' : '无退款';
        $order['has_return_goods']          = $order['has_return_goods'] ? '有退货' : '无退货';
        return $order;
    }

    protected function paymentFormat($payment){
        foreach ($payment as $k => $v) {
            $payment[$k]['status']          = TbPurPaymentModel::$status_name[$v['status']];
            $payment[$k]['our_company']     = cdVal($v['our_company']);
            if($v['amount_payable_split']) $payment[$k]['amount_payable'] = $v['amount_payable_split'];
        }
        return $payment;
    }

    protected function shipFormat($ship){
        foreach ($ship as $k => $v) {
            $ship[$k]['warehouse_status'] = TbPurShipModel::$warehouse_status_val[$v['warehouse_status']];
            $ship[$k]['warehouse'] = cdVal($v['warehouse']);
        }
        return $ship;
    }

    protected function warehouseFormat($warehouse){
        foreach ($warehouse as $k => $v) {
            $warehouse[$k]['log_currency'] = cdVal($v['log_currency']);
            $warehouse[$k]['service_currency'] = cdVal($v['service_currency']);
        }
        return $warehouse;
    }

    protected function invoiceFormat($invoice){
        foreach ($invoice as $k => $v) {
            $invoice[$k]['status']          = TbPurInvoiceModel::$status[$v['status']];
            $invoice[$k]['invoice_type']    = cdVal($v['invoice_type']);
            $invoice[$k]['tax_rate']        = cdVal($v['tax_rate']);
            $invoice_no                     = [];
            foreach (json_decode($v['invoice_no'],true) as $val) {
                $invoice_no[] = $val['no'];
            }
            $invoice[$k]['invoice_no']  = implode(',',$invoice_no);
        }
        return $invoice;
    }

    protected function refundFormat($refund,$order) {
        foreach ($refund as $k => $v) {
            $rate = exchangeRateConversion(cdVal($v['currency_code']),$order['amount_currency'],str_replace('-','',$order['sou_time']));
            $refund[$k]['claim_amount'] = bcmul($v['claim_amount'],$rate,2);
        }
        return $refund;
    }

    protected function getReturn($relevance_id) {
        $return_m   = D('Purchase/Return');
        return $return_m
            ->alias('t')
            ->field('t.*,a.compensation_currency_cd,a.compensation,sum(b.return_number) return_number,sum(tally_number) tally_number,sum(b.return_number*(c.unit_price+c.unit_expense)) return_amount,d.zh_name receive_address_country,e.zh_name receive_address_province,f.zh_name receive_address_area')
            ->join('tb_pur_return_order a on a.return_id=t.id')
            ->join('tb_pur_return_goods b on b.return_order_id=a.id')
            ->join('tb_pur_goods_information c on c.information_id=b.information_id')
            ->join('tb_ms_user_area d on d.area_no=t.receive_address_country')
            ->join('tb_ms_user_area e on e.area_no=t.receive_address_province')
            ->join('tb_ms_user_area f on f.area_no=t.receive_address_area')
            ->where(['a.relevance_id'=>$relevance_id])
            ->group('a.id')
            ->select();
    }

    protected function returnFormat($return){
        foreach ($return as $k => $v) {
            $return[$k]['receive_address']      = "{$v['receive_address_country']}-{$v['receive_address_province']}-{$v['receive_address_area']}";
            $return[$k]['has_difference']       = $v['has_difference'] ? '有差异' : '无差异';
            $return[$k]['need_bear_difference'] = $v['need_bear_difference'] ? '需要' : '不需要';
            $return[$k]['warehouse']            = cdVal($v['warehouse_cd']);
            $return[$k]['status']               = cdVal($v['status_cd']);
            if($v['status_cd'] == 'N002640100') $return[$k]['return_amount'] = 0;
        }
        return $return;
    }

    protected function getPayment($relevance_id) {
        $payment = D('TbPurPayment')
            ->where(['relevance_id'=>$relevance_id])->select();
    }
}