<?php
/**
 * User: yuanshixiao
 * Date: 2018/9/25
 * Time: 13:53
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ShipLogic extends BaseLogic
{
    private static $model;
    public $ship_info;
    /**
     * 结束发货
     * @param $relevance_id
     * @return bool
     */
    public function shipEnd($relevance_id) {
        $relevance_model        = M('relevance_order','tb_pur_');
        $relevance_model->startTrans();
        $reverse_ship_end = $relevance_model->where(['relevance_id'=>$relevance_id])->lock(true)->getField('reverse_ship_end');
        //标记发货完成
        $res_ship_status        = $relevance_model->where(['relevance_id'=>$relevance_id])->save(['ship_status'=>2]);
        if($res_ship_status == false) {
            $relevance_model->rollback();
            $this->error = '修改发货状态失败';
            return false;
        }
        //如果所有发货都已经入库则入库完成
        $not_warehoused = M('ship','tb_pur_')->where(['relevance_id'=>$relevance_id,'warehouse_status'=>['in',[0,2]]])->getField('id');
        if(!$not_warehoused) {
            $res_warehouse_status = $relevance_model->where(['relevance_id'=>$relevance_id])->save(['warehouse_status'=>2]);
            if($res_warehouse_status == false) {
                $relevance_model->rollback();
                $this->error = '修改入库状态失败';
                return false;
            }
        }
        $goods_onway = M('goods_information','tb_pur_')
            ->field('sku_information,goods_number-shipped_number-ship_end_number+return_number remainder')
            ->where(['relevance_id'=>$relevance_id,'_string'=>'goods_number-shipped_number-ship_end_number+return_number>0'])
            ->select();
        $res_goods = M('goods_information','tb_pur_')
            ->where(['relevance_id'=>$relevance_id])
            ->save(['ship_end_number'=>['exp','goods_number-shipped_number +return_number']]);
        if($res_goods === false) {
            $relevance_model->rollback();
            $this->error = '修改商品标记完结数量失败';
            return false;
        }
        //减少在途
        $pur_info = $relevance_model
            ->alias('t')
            ->field('a.*')
            ->join('tb_pur_order_detail a on a.order_id = t.order_id')
            ->where(['t.relevance_id'=>$relevance_id])
            ->find();
        //标记发货完结取消采购订单应付状态更新2019-09-19
        //判断应付
        /*$payment_status = D('TbPurPayment')->paymentStatusCheck($pur_info);
        $save_order['payment_status'] = $payment_status;
        $res_payment = D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save($save_order);
        if($res_payment === false) {
            $relevance_model->rollback();
            $this->error = '订单应付状态保存失败';
            return false;
        }*/

        // 生成抵扣记录
        $addDataInfo = [];
        $addDataInfo['clause_type'] = '7';
        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $pur_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870017', $relevance_id);
        if (!$pur_res) {
            $relevance_model->rollback();
            $this->error = '生成抵扣记录失败';
            return false;
        }
        // 标记发货完结时校验以前的发货完结操作记录：如果没有，则继续校验在途；如果有过，则不再校验在途，其他逻辑相同。
//        if($pur_info['warehouse'] != 'N000680800' && !$reverse_ship_end) {
        if(!$reverse_ship_end) {
            $order_info = $relevance_model
                ->alias('t')
                ->field('a.procurement_number,t.prepared_time')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['t.relevance_id'=>$relevance_id])
                ->find();

            $goods_end = [];
            foreach ($goods_onway as $v) {
                $goods_info['operatorId']       = $_SESSION['userId'];
                $goods_info['purchaseOrderNo']  = $order_info['procurement_number'];
                $goods_info['skuId']            = $v['sku_information'];
                $goods_info['num']              = $v['remainder'];
                $goods_end[]                    = $goods_info;
            }
            if(!empty($goods_end) && $order_info['prepared_time'] > '2018-09-29 10:06:00') {
                $is_old = false;                    
                if ($order_info['prepared_time'] < '2019-07-30 00:00:00') { // #9474 SKU在途库存不够，则对应SKU的在途数量自动扣减至0
                    $is_old = true;                    
                }

                $res_on_way = ApiModel::onWayEnd($goods_end, $is_old);
                if($res_on_way['code'] != 2000) {
                    $relevance_model->rollback();
                    $this->error = '在途扣减失败';
                    ELog::add(['info'=>$this->error,'request'=>$goods_end,'response'=>$res_on_way]);
                    return false;
                }
            }
        }

        (new TbPurActionLogModel())->addLog($relevance_id);
        $relevance_model->commit();
        return true;
    }

    /**
     * 撤回标记发货完结
     * @param $relevance_id
     * @return bool
     */
    public function reverseShipEnd($relevance_id) {
        $relevance_model        = M('relevance_order','tb_pur_');
        $relevance_model->startTrans();

        // 生成应付记录
        $addDataInfo = [];
        $addDataInfo['clause_type'] = '7';
        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $pur_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', 'N002870018', $relevance_id);
        if (!$pur_res) {
            $relevance_model->rollback();
            $this->error = '生成应付记录失败';
            return false;
        }

        $relevance_model->where(['relevance_id'=>$relevance_id])->lock(true)->getField('relevance_id');
        // 标记完结数量清空
        $res_goods = M('goods_information','tb_pur_')
            ->where(['relevance_id'=>$relevance_id])
            ->save(['ship_end_number'=>0]);
        if($res_goods === false) {
            $relevance_model->rollback();
            $this->error = '标记发货完结数量清空失败';
            return false;
        }
        // 更新采购单发货状态&入库状态
        if ($this->updateShipStatus($relevance_id) === false) {
            $relevance_model->rollback();
            $this->error = '更新采购单发货状态';
            return false;
        }
        if (D('Purchase/Warehouse','Logic')->updateRelevanceWarehouseStatus($relevance_id) === false) {
            $relevance_model->rollback();
            $this->error = '更新采购单入库状态';
            return false;
        }
        $rev_res = $relevance_model->where(['relevance_id' => $relevance_id])->save(['reverse_ship_end' => 1]);
        if($rev_res === false) {
            $relevance_model->rollback();
            $this->error = '采购表更新失败';
            return false;
        }
        // 记录撤回日志【撤回发货完结】
        (new TbPurActionLogModel())->addLog($relevance_id);
        $relevance_model->commit();
        return true;
    }

    public function shipNoticeEmail($ship_info,$order_id) {
        $email_address = (new TbMsCmnCdModel())->where(['CD'=>$ship_info['warehouse']])->getField('ETC3');
        if($email_address) {
            $order_detail   = M('order_detail','tb_pur_')->field('procurement_number,online_purchase_order_number')->where(['order_id'=>$order_id])->find();
            $title          = '供应商发货提醒';
            $content        = "采购单{$order_detail['procurement_number']}已发货。<br/>
            外部采购PO号：{$order_detail['online_purchase_order_number']}<br/>
            提单号/快递单号/车牌号：{$ship_info['bill_of_landing']}<br/>
            发货确认人：{$_SESSION['m_loginname']}";
            $email_m        = new SMSEmail();
            $email_address  = explode(',',$email_address);
            if(!$res = $email_m->sendEmail($email_address,$title,$content))
                $this->error = $email_m->getError();
            return $res;
        }else {
            return true;
        }
    }

    public function shipRevoke($ship_id) {
        $ship_m                 = $this->model();
        $ship_goods_m           = D('TbPurShipGoods');
        $relevance_m            = D('TbPurRelevanceOrder');
        $goods_information_m    = D('TbPurGoodsInformation');
        $ship_m->startTrans();
        $ship_info              = $this->ship_info = $this->model()->where(['id'=>$ship_id])->lock(true)->find();
        $ship_goods_info        = $ship_goods_m->where(['ship_id'=>$ship_id])->select();
        if(!$ship_info || $ship_info['warehouse_status'] != TbPurShipModel::$warehouse_status['to_do']) {
            $ship_m->rollback();
            $this->error = '状态异常';
            return false;
        }
        //删除发货记录
        $res_ship = $ship_m->where(['id'=>$ship_id])->delete();
        $res_ship_goods = $ship_goods_m->where(['ship_id'=>$ship_id])->delete();
        if(!$res_ship || !$res_ship_goods) {
            $this->error = '发货记录删除失败';
            $ship_m->rollback();
            return false;
        }

        //更新采购单发货状态
        $save_relevance['shipped_number'] = ['exp','shipped_number-'.$ship_info['shipping_number']];
        $ship_exists = $ship_m->where(['relevance_id'=>$ship_info['relevance_id']])->getField('id');
        $save_relevance['ship_status'] = $ship_exists ? 1 : 0;
        $res_relevance = $relevance_m->where(['relevance_id'=>$ship_info['relevance_id']])->save($save_relevance);
        if(!$res_relevance) {
            $this->error = '采购订单发货状态更新失败';
            $ship_m->rollback();
            return false;
        }

        $getShippedGoodsInfo = [];
        foreach ($ship_goods_info as $v) {
            $res_goods = $goods_information_m->where(['information_id'=>$v['information_id']])->setDec('shipped_number',$v['ship_number']);
            if($res_goods === false) {
                $ship_m->rollback();
                $this->error = '更新商品发货数量失败';
                return false;
            }
            $getShippedGoodsInfo[] = ['information_id' => $v['information_id'], 'ship_number' => $v['ship_number']];
        }

        // 生成抵扣记录
        $addDataInfo = [];
        $addDataInfo['detail'] = $getShippedGoodsInfo; // 本次发货数量 和采购价 用来获取付款规则公式的总金额
        $addDataInfo['class'] = __CLASS__;
        $addDataInfo['function'] = __FUNCTION__;
        $pur_res =D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870013', $ship_info['relevance_id']);
        if (!$pur_res) {
            $ship_m->rollback();
            return false;
        }

        $email_res = $this->shipRevokeEmail();
        if(!$email_res) {
            $ship_m->rollback();
            return false;
        }


        (new TbPurActionLogModel())->addLog($ship_info['relevance_id']);
        $ship_m->commit();
        return true;
    }

    public function shipRevokeEmail() {
        $to = (new TbMsCmnCdModel())->where(['CD'=>$this->ship_info['warehouse']])->getField('ETC3');
        if(!empty($to)) {
            $to             = explode(',', $to);
            $email_m        = new SMSEmail();
            $purchase_no    = D('TbPurRelevanceOrder')
                ->alias('t')
                ->join('inner join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['t.relevance_id'=>$this->ship_info['relevance_id']])
                ->getField('procurement_number');
            $title      = '供应商发货撤回提醒';
            $content    = "采购单号：{$purchase_no} ，发货编号：{$this->ship_info['warehouse_id']}，发货已撤回。
                <br/><br/>
                撤回操作人：{$_SESSION['m_loginname']}";
            $res = $email_m->sendEmail($to, $title, $content);
            if(!$res) {
                $this->error = '通知邮件发送失败,' . $email_m->getError();
            }
            return $res;
        }
        return true;
    }

    public function model() {
        if(self::$model) {
            return self::$model;
        }else {
            return D('TbPurShip');
        }
    }

    public function noticeThirdWarehouse($ship_info, $ship_goods) {
        $post_data = [
            'orderId'       => $ship_info['ship_code'],
            'warehouseCode' => $ship_info['warehouse'],
        ];
        foreach ($ship_goods as $v) {
            $goods = json_decode($v,true);
            foreach ($goods['number_info'] as $value) {
                if($value['number']) {
                    $post_data['skuInfoes'][] = [
                        'skuId' => $goods['sku_id'],
                        'quantity' => $value['number'],
                        'inventoryType' => 0,
                        'expireDate' => $value['expiration_date']
                    ];
                }
            }
        }
        $res = ApiModel::shipNoticeThirdWarehouse(['data'=>[$post_data]]);
        if($res['code'] == 2000) return true;
        $this->error = $res['data'][0]['msg'];
        return false;

    }

    public function getWarehousedNumber($information_id) {
        return D('TbPurShipGoods')
            ->where(['information_id'=>$information_id])
            ->group('information_id')
            ->getField('sum(warehouse_number+warehouse_number_broken)');
    }

    public function updateShipStatus($relevance_id) {
        $goods_info = D('TbPurRelevanceOrder')
            ->alias('t')
            ->field('goods_number,a.shipped_number,ship_end_number,return_number')
            ->join('tb_pur_goods_information a on a.relevance_id=t.relevance_id')
            ->where(['t.relevance_id'=>$relevance_id])
            ->group('a.information_id')
            ->select();
        $has_ship       = false;
        $has_remainder  = false;
        foreach ($goods_info as $v) {
            if($v['shipped_number'] > 0) // 已发货数量
                $has_ship = true;
            if($v['goods_number'] - $v['shipped_number'] - $v['ship_end_number'] + $v['return_number']  > 0) // 商品数量 - 已发货数量 - 发货标记完结数量 + 退货数量
                $has_remainder = true;
        }
        if(!$has_ship) {
            $save['ship_status'] = 0; // 待发货
        }elseif($has_remainder) {
            $save['ship_status'] = 1; // 部分发货
        }else {
            $save['ship_status'] = 2; // 发货完成
        }
        return D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save($save);
    }
}