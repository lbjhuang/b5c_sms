<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 16:19
 */

class RelevanceAction extends BaseAction
{
    public function order_detail() {
        $relevance_id   = $this->jsonParams()['relevance_id'];
        $res            = D('Purchase/Relevance','Logic')->orderDetail($relevance_id);
        $this->ajaxSuccess($res);
    }

    public function action_log() {
        $param          = $this->jsonParams();
        $_GET['p']      = $param['p'];
        $res            = D('Purchase/Relevance','Logic')->logList($param);
        $this->ajaxSuccess($res);
    }

    public function updatePartWarehouseStatus() {
        set_time_limit(0);
        $relevance_m    = D('TbPurRelevanceOrder');
        $warehouse_l    = D('Purchase/Warehouse','Logic');
        $relevance_arr  = $relevance_m
            ->where(['prepared_time'=>['gt','2018-08-01 00:00:00']])
            ->getField('relevance_id',true);
        foreach ($relevance_arr as $v) {
           $warehouse_l->updateRelevanceWarehouseStatus($v);
        }
        echo '处理完成';
    }

    public function old_virtual_warehouse_on_way() {
        $orders = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('t.relevance_id,t.prepared_by,t.prepared_time,a.procurement_number,a.tax_rate,a.amount_currency,a.supplier_id,a.warehouse,b.sell_team,a.payment_company,a.our_company')
            ->join('tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->where(['t.warehouse_status'=>['lt',2],'a.warehouse'=>'N000680800'])
            ->select();
        foreach ($orders as $order) {
            $bill_goods     = [];
            $order_goods    = M('goods_information','tb_pur_')
                ->alias('t')
                ->field('t.sku_information,t.unit_price,t.unit_expense,t.drawback_percent,t.goods_number,t.shipped_number,t.ship_end_number,t.return_number,t.return_number,sum(a.warehouse_number+a.warehouse_number_broken) warehouse_number')
                ->join('tb_pur_ship_goods a on a.information_id=t.information_id')
                ->where(['t.relevance_id'=>$order['relevance_id']])
                ->group('t.information_id')
                ->select();
            foreach ($order_goods as $v) {
                $goods                      = [];
                $goods['skuId']             = $v['sku_information'];
                $goods['price']             = $v['unit_price'];
                $goods['currencyId']        = $order['amount_currency'];
                $goods['currencyTime']      = date('Y-m-d H:i:s');
                $goods['num']               = $v['goods_number']+$v['return_number']-$v['warehouse_number']-$v['ship_end_number'];
                $goods['purInvoiceTaxRate'] = $order['tax_rate'] ? '0.'.substr(cdVal($order['tax_rate']),0,-1) : 0;
                $goods['proportionOfTax']   = substr(cdVal($v['drawback_percent']),0,-1)/100;
                $goods['storageLogCost']    = 0;
                $goods['logServiceCost']    = 0;
                $goods['poCurrency']        = $order['amount_currency'];
                $goods['poCost']            = $v['unit_expense'];
                $goods['purStorageDate']    = $order['prepared_time'];
                if($goods['num'])
                $bill_goods[]               = $goods;
            }
            if(!empty($bill_goods)) {
                //在途为调用接口，无法被事务回滚，放到最后处理
                $bill_data      = [
                    'bill' => [
                        'billType'          => 'N000940100',//收发类型，采购入库为N000940100固定不变
                        'relationType'      => 'N002350200',//业务单据的类型,采购单N002350200
                        'procurementNumber' => $order['procurement_number'], //b5c单号
                        'linkBillId'        => $order['procurement_number'], //b5c单号
                        'warehouseRule'     => 1, //是否入我方仓库
        //                'batch'           => $ship_info['bill_of_landing'],//批次，这个待定
                        'channel'           => 'N000830100',// 默认
                        'supplier'          => $order['supplier_id'],// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                        'warehouseId'       => $order['warehouse'],// 仓库id（码表或数据字典对应的值）
                        'saleTeam'          => $order['sell_team'],//销售团队
                        'spTeamCd'          => $order['payment_company'],//采购团队
                        'conCompanyCd'      => $order['our_company'],//我方公司
                        'virType'           => 'N002440200',//	入库类型 现货入库(N002440100)、在途入库(N002440200)
                        'operatorId'        => $_SESSION['userId'],//操作人id
                    ],
                    'guds' => $bill_goods
                ];
                $demand_code = substr($order['procurement_number'],0 ,-4);
                if(D('Scm/Demand')->isSell($demand_code)) {
                    $bill_data['bill']['type']          = 3;
                    $bill_data['bill']['processOnWay']  = 1;
                    $bill_data['bill']['orderId']       = $demand_code;
                }
                $res_j  = ApiModel::warehouse($bill_data);
                $res    = json_decode($res_j,true);
                if($res['code'] != 2000) {
                    if (D('Purchase','Logic')->checkHasPutRecord($bill_data)) {
                        ELog::add(['msg'=>'调用在途接口失败，已生成在途数据','request'=>$bill_data,'response'=>$res_j],ELog::INFO);
                    }else{
                        ELog::add(['msg'=>'调用在途接口失败','request'=>$bill_data,'response'=>$res_j],ELog::ERR);
                        M()->rollback();
                        $this->error = $res['msg'];
                    }
                }else {
                    ELog::add(['msg'=>'调用在途接口成功','request'=>$bill_data,'response'=>$res_j],ELog::INFO);
                }
            }
        }
    }

    public function order_export() {
        $relevance_l    = D('Purchase/ExportOrder','Logic');
        $params         = I('request.');
        $where          = A('Home/OrderDetail')->getConditions($params);
        $relevance_l->exportOrder($where);
    }
}