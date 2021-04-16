<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 13:33
 */
class TbPurActionLogModel extends RelationModel
{
    public $action_info = [
        'order_add'                         => '创建采购订单',
        'order_update'                      => '编辑采购订单',
        'ship'                              => '操作发货',
        'warehouse'                         => '操作入库',
        'review'                            => '操作审批',
        'sendforreview'                     => '提交审批',
        'invoice_edit'                      => '编辑发票',
        'invoice_add'                       => '添加发票',
        'invoice_confirm'                   => '发票确认',
        'payable_confirm'                   => '付款金额确认',
        'payable_write_off'                 => '付款核销',
        'purchase_return'                   => '财务退回',
        'ship_end'                          => '标记发货完结',
        'warehouse_end'                     => '标记入库完结',
        'invoice_end'                       => '标记开票完结',
        'order_cancel'                      => '取消采购单',
        'ship_revoke'                       => '发货撤回',
        'payable_return_to_payment_confirm' => '应付撤回到付款确认',
        'delivery_confirmation' => '发货确认',
        'add_pur_claim'                     => '采购退款认领',
        'edit_pur_claim'                    => '编辑采购退款认领',
        'deleteclaim'                       => '删除采购退款认领',
        'initiate_return'                   => '发起退货',
        'delete_return'                     => '撤回退货请求',
        'return_tally'                      => '退货理货确认',
        'cancel_confirm'                    => '撤回应付到待确认',
        'reverse_ship_end'                  => '撤回发货完结',
        'invoice_return'                    => '发票退回',
        'invoice_confirmed_return'          => '已确认发票退回',
        'invoice_del'                       => '发票删除',
        'virtual_warehouse_ship_revoke'     => '虚拟仓发货撤回',
        'invoice_end_withdraw'     => '发票标记完结撤回',
        'invoiceEndWithdraw'     => '发票标记完结撤回',
        'invoiceendwithdraw'     => '发票标记完结撤回',
    ];

    protected $trueTableName    = 'tb_pur_action_log';

    public function addLog($id,$action='') {
        $log_info['relevance_id']   = $id;
        $log_info['user']           = session('m_loginname');
        $log_info['time']           = date('Y-m-d H:i:s');
        $log_info['info']           = $this->action_info[$action?$action:ACTION_NAME];
        $this->add($log_info);
    }

    public static function recordLog($relevance_ids,$operation_info, $date, $status_name, $remark) {
        if (empty($relevance_ids)) return;
        $ids = (array)$relevance_ids;
        $data = array_map(function($id) use ($operation_info, $date, $status_name, $remark) {
            return $log_info[] = [
                'relevance_id' => $id,
                'user' => session('m_loginname'),
                'time' => empty($date) ? date('Y-m-d H:i:s') : $date,
                'info' => $operation_info,
            ];
        }, $ids);
        (new self())->addAll($data);
    }
}
