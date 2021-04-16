<?php
/**
 * User: yuanshixiao
 * Date: 2018/9/25
 * Time: 13:53
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class InvoiceLogic extends BaseLogic
{

    /**
     * 发票标记完结
     * @param $relevance_id
     * @return bool
     */
    public function invoiceEnd($relevance_id) {
        $relevance_model        = M('relevance_order','tb_pur_');
        $relevance_model->startTrans();
        //标记发货完成
        $relevance = $relevance_model->field('invoice_status,has_invoice_unconfirmed')->lock(true)->where(['relevance_id'=>$relevance_id])->find();
        if($relevance['invoice_status'] == 2 || $relevance['has_invoice_unconfirmed'] == 1) {
            $this->error = '订单已开票完成或有未确认的发票';
            $relevance_model->rollback();
            return false;
        }
        $res = $relevance_model->where(['relevance_id'=>$relevance_id])->save(['invoice_status'=>2]);
        if(!$res) {
            $this->error = '保存失败';
            $relevance_model->rollback();
            return false;
        }
        (new TbPurActionLogModel())->addLog($relevance_id);
        $relevance_model->commit();
        return true;
    }

    public function invoiceEndWithdraw($relevance_id) {
        $Model = new Model();
        $where_invoice['relevance_id'] = $relevance_id;
        $invoice_count = $Model->table('tb_pur_invoice')
            ->where($where_invoice)
            ->count();
        $invoice_status = 0;
        if (0 != $invoice_count) {
            $invoice_status = 1;
        }
        $relevance_model        = M('relevance_order','tb_pur_');
        $relevance_model->startTrans();
        //标记发货完成
        $res = $relevance_model->where(['relevance_id'=>$relevance_id])->save([
            'invoice_status'=>$invoice_status,
        ]);
        if(!$res) {
            $this->error = '保存失败';
            $relevance_model->rollback();
            return false;
        }
        (new TbPurActionLogModel())->addLog($relevance_id);
        $relevance_model->commit();
        return true;
    }

    /**
     * @param $id
     * @param $return_reason 退回原因
     * @return bool
     * 发票退回
     */
    public function invoiceReturn($id, $return_reason) {
        if(!$id) {
            $this->error = '参数异常';
            return false;
        }
        $invoice_m = new TbPurInvoiceModel();
        $invoice_m->startTrans();
        $invoice_info = $invoice_m->field('status,relevance_id')->lock(true)->where(['id'=>$id])->find();
        if($invoice_info['status'] != $invoice_m::$status_unconfirmed) {
            $invoice_m->rollback();
            $this->error = '只有待确认的发票可以退回';
            return false;
        }
        $res = $invoice_m->where(['id'=>$id])->save(['status'=>$invoice_m::$status_return, 'return_reason'=>$return_reason]);
        if(!$res) {
            $invoice_m->rollback();
            $this->error = '保存失败';
            return false;
        }
        $res_relevance = (new TbPurRelevanceOrderModel())->where(['relevance_id'=>$invoice_info['relevance_id']])->save(['has_invoice_unconfirmed'=>2]);
        if($res_relevance === false) {
            $invoice_m->rollback();
            $this->error = '采购单发票代办状态更新失败';
            return false;
        }
        (new TbPurActionLogModel())->addLog($invoice_info['relevance_id']);
        $invoice_m->commit();
        return true;
    }

    /**
     * 已确认发票退回
     * @param $id
     * @param $return_reason
     * @return bool
     * @author Wenbin.Wang
     */
    public function invoiceConfirmedReturn($id, $return_reason)
    {
        if ($id <= 0) {
            $this->error = '参数异常';
            return false;
        }

        $invoice_model = new TbPurInvoiceModel();
        $invoice = $invoice_model->field('id,status,relevance_id')->where(['id' => $id])->find();
        if ($invoice['status'] != $invoice_model::$status_confirmed) {
            $this->error = '该发票不是已确认的状态';
            return false;
        }

        $invoice_model->startTrans();
        $result = $invoice_model->where(['id' => $id])->save(['status' => $invoice_model::$status_return, 'return_reason' => $return_reason]);
        if ($result === false) {
            $invoice_model->rollback();
            $this->error = '退回失败';
            return false;
        }

        // 修改发票的采购发票确认情况为有已退回
        $result = (new TbPurRelevanceOrderModel())->where(['relevance_id' => $invoice['relevance_id']])->save(['has_invoice_unconfirmed' => 2]);
        if ($result === false) {
            $invoice_model->rollback();
            $this->error = '采购单发票代办状态更新失败';
            return false;
        }

        $relevance_order_model = new TbPurRelevanceOrderModel();
        $invoice_status = $relevance_order_model->where(['relevance_id' => $invoice['relevance_id']])->getField('invoice_status');
        $change_invoice_status = -1;
        $count = $invoice_model->where(['relevance_id' => $invoice['relevance_id'], 'status' => $invoice_model::$status_confirmed])->count();
        // 原采购单开票状态为【部分开票】，退回的已确认发票为当前采购单唯一已确认发票，则开票状态变更为【未开票】
        if ($invoice_status == TbPurRelevanceOrderModel::INVOICE_STATUS_PART && $count == 0) {
            $change_invoice_status = TbPurRelevanceOrderModel::INVOICE_STATUS_NOTYET;
        }

        // 原采购单开票状态为【已开票】
        if ($invoice_status == TbPurRelevanceOrderModel::INVOICE_STATUS_DONE) {
            if ($count > 0) { // 退回的已确认发票非当前采购单唯一已确认发票，则开票状态变更为【部分开票】
                $change_invoice_status = TbPurRelevanceOrderModel::INVOICE_STATUS_PART;
            } else { // 退回的已确认发票为当前采购单唯一已确认发票，则开票状态变更为【未开票】
                $change_invoice_status = TbPurRelevanceOrderModel::INVOICE_STATUS_NOTYET;
            }
        }

        // 需改变的状态如果设定了值，则进行变更
        if ($change_invoice_status !== -1) {
            $result = $relevance_order_model->where(['relevance_id' => $invoice['relevance_id']])->setField('invoice_status', $change_invoice_status);
            if ($result === false) {
                $invoice_model->rollback();
                $this->error = '采购单开票状态更新失败';
                return false;
            }
        }

        // 退回商品已开票金额
        $invoice_goods_model = new TbPurInvoiceGoodsModel();
        $invoice_goods = $invoice_goods_model
            ->join('LEFT JOIN tb_pur_goods_information gi ON tb_pur_invoice_goods.information_id = gi.information_id')
            ->where(['invoice_id' => $invoice['id']])
            ->getField('gi.information_id,gi.invoiced_money,tb_pur_invoice_goods.invoice_money');

        // 使用CASE...WHEN...THEN...语句来执行批量操作
        if ($invoice_goods !== null) {
            $ids = implode(',', array_keys($invoice_goods));
            $update_sql = 'UPDATE `tb_pur_goods_information` SET `invoiced_money` = CASE `information_id` ';

            foreach ($invoice_goods as $information_id => $goods) {
                $update_sql .= sprintf("WHEN %d THEN %d ", $information_id, $goods['invoiced_money'] - $goods['invoice_money']);
            }
            $update_sql .= "END WHERE `information_id` IN ($ids)";
            $result = $invoice_goods_model->execute($update_sql);

            if ($result === false) {
                $invoice_model->rollback();
                $this->error = '商品已开票金额退回失败';
                return false;
            }
        }

        (new TbPurActionLogModel())->addLog($invoice['relevance_id']);
        $invoice_model->commit();
        return true;
    }

    /**
     * @param $id
     * @return bool
     * 删除发票
     */
    public function invoiceDel($id) {
        if(!$id) {
            $this->error = '参数异常';
            return false;
        }
        $invoice_m = new TbPurInvoiceModel();
        $invoice_m->startTrans();
        $invoice_info = $invoice_m->field('relevance_id,status')->lock(true)->where(['id'=>$id])->find();
        if($invoice_info['status'] != $invoice_m::$status_return) {
            $invoice_m->rollback();
            $this->error = '只有退回的发票可以删除';
            return false;
        }
        $res_invoice = $invoice_m->where(['id'=>$id])->delete();
        $res_invoice_goods = (new TbPurInvoiceGoodsModel())->where(['invoice_id'=>$id])->delete();
        $res_relevance = (new TbPurRelevanceOrderModel())->where(['relevance_id'=>$invoice_info['relevance_id']])->save(['has_invoice_unconfirmed'=>0]);
        if($res_invoice === false || $res_invoice_goods === false || $res_relevance === false) {
            $invoice_m->rollback();
            $this->error = '发票删除失败';
            return false;
        }
        (new TbPurActionLogModel())->addLog($invoice_info['relevance_id']);
        $invoice_m->commit();
        return true;
    }

}