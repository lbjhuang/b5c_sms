<?php



/**
 * User: yansu
 * Date: 19/5/9
 * Time: 11:24
 */
class TodoModel extends Model
{
    /**
     * @var
     */
    public static $model;

    /**
     * @var array
     */
    public static $column_map = [
        'pending_payment_amount_to_be_confirmed' => [
            'payment_no'  => '应付单号',
            'supplier_id' => '供应商名称',
            'prepared_by' => '创建人',
            'create_time' => '创建时间',
            'detail_url'  => 'index.php?m=order_detail&a=payable_detail&id=',
            'title'       => '采购应付详情',
            'translate'   => ['supplier_id'],
        ],
        'pending_accounting_audit' => [
            'payment_audit_no' => '付款单号',
            'our_company_cd_val' => '我方公司',
            'payment_channel_cd_val' => '支付渠道',
            'source_cd_val' => '来源',
            'payable_currency_cd_val' => '币种',
            'payable_amount_after' => '应付金额',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=finance&a=pay_order_detail&payment_audit_id=',
            'title'      => '付款单详情',
            'translate'  => ['our_company_cd_val', 'payment_channel_cd_val', 'source_cd_val', 'payable_currency_cd_val'],
        ],
        'pending_purchase_payment_amount' => [
            'payment_audit_no' => '付款单号',
            'our_company_cd_val' => '我方公司',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=finance&a=pay_order_detail&payment_audit_id=',
            'title'      => '付款单详情',
            'translate'  => ['our_company_cd_val'],
        ],
        'pending_payment_amount_out_confirmed' => [
            'payment_audit_no' => '付款单号',
            'our_company_cd_val' => '我方公司',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=finance&a=pay_order_detail&payment_audit_id=',
            'title'      => '付款单详情',
            'translate'  => ['our_company_cd_val'],
        ],
        'wms_pending_to_be_confirmed' => [
            'payment_no'      => '费用单号',
            'cost_sub_cd_val' => '费用细分',
            'created_by'      => '创建人',
            'created_at'      => '创建时间',
            'detail_url'      => 'index.php?g=logistics&m=Expensebill&a=fee_detail&fee_id=',
            'title'           => '费用单详情',
            'translate'       => ['cost_sub_cd_val'],
        ],
        'wms_pending_business_audit' => [
            'payment_no'      => '费用单号',
            'expense_sure_user' => '费用确认责任人',
            'cost_sub_cd_val' => '费用细分',
            'created_by'      => '创建人',
            'created_at'      => '创建时间',
            'detail_url'      => 'index.php?g=logistics&m=Expensebill&a=fee_detail&fee_id=',
            'title'           => '费用单详情',
            'translate'       => ['cost_sub_cd_val'],
        ],
        'pending_purchase_confirmation' => [
            'procurement_number' => '采购单号',
            'online_purchase_order_number' => '采购PO单号',
            'supplier_id' => '供应商名称',
            'prepared_by' => '创建人',
            'create_time' => '创建时间',
            'detail_url'  => '/index.php?m=order_detail&a=ship&id=',
            'title'       => '采购订单详情',
            'translate'   => ['supplier_id'],
        ],
        'pending_purchase_warehousing' => [
            'warehouse_id' => '发库编号',
            'online_purchase_order_number' => '采购PO单号',
            'bill_of_landing' => '提单号',
            'supplier_id' => '供应商名称',
            'prepared_by' => '创建人',
            'create_time' => '创建时间',
            'detail_url'  => 'index.php?m=order_detail&a=warehouse_detail&id=',
            'title'       => '采购入库详情',
            'translate'   => ['supplier_id'],
        ],
        'waiting_for_purchase' => [
            'warehouse_id' => '发货编号',
            'online_purchase_order_number' => '采购PO单号',
            'bill_of_landing' => '提单号',
            'supplier_id' => '供应商名称',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url'  => 'index.php?m=order_detail&a=warehouse_detail&id=',
            'title'       => '采购入库详情',
            'translate'   => ['supplier_id'],
        ],
        'pending_confirmation_of_purchase_invoice' => [
            'procurement_number'           => '采购单号',
            'online_purchase_order_number' => '采购PO单号',
            'supplier_id'                  => '供应商名称',
            'payment_status'               => '应付状态',
            'on_way_invoice_amount'        => '在途发票金额',
            'prepared_by'                  => '创建人',
            'create_time'                  => '创建时间',
            'detail_url'                   => 'index.php?m=order_detail&a=invoice_info&relevance_id=',
            'title'                        => '采购发票详情',
            'translate'                    => ['supplier_id', 'payment_status'],
        ],
        'pending_purchase_invoice' => [
            'action_no'             => '操作编号',
            'invoice_no'            => '发票号',
            'supplier_id'           => '供应商名称',
            'payment_status'        => '应付状态',
            'on_way_invoice_amount' => '在途发票金额',
            'create_user'           => '创建人',
            'create_time'           => '创建时间',
            'detail_url'            => 'index.php?m=order_detail&a=invoice_detail&id=',
            'title'                 => '采购发票详情',
            'translate'             => ['supplier_id', 'payment_status'],
        ],
        'pending_confirmation_purchase_return_tally' => [
            'return_no'  => '采购退货单号',
            'warehouse_cd_val' => '仓库',
            'SP_NAME'    => '供应商',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=order_detail&a=return_detail&do=do&id=',
            'title'      => '采购退货详情',
            'translate'  => ['warehouse_cd_val', 'SP_NAME'],
        ],
        'pending_b2b_order_delivery' => [
            'PO_ID' => 'B2B订单号',
            'THR_PO_ID' => '销售PO单号',
            'CLIENT_NAME' => '客户',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=b2b&a=do_ship&order_id=',
            'title' => 'B2B 发货详情',
            'translate'  => ['CLIENT_NAME'],
        ],
        'waiting_for_the_b2b_order_to_be_shipped' => [
            'PO_ID' => 'B2B订单号',
            'THR_PO_ID' => '销售PO单号',
            'CLIENT_NAME' => '客户',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=b2b&a=do_ship_show&order_id=',
            'title' => 'B2B 发货详情',
            'translate'  => ['CLIENT_NAME'],
        ],
        'pending_b2b_order_receipts' => [
            'PO_ID' => 'B2B订单号',
            'THR_PO_ID' => '销售PO单号',
            'CLIENT_NAME' => '客户',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=b2b&a=receivable_detail&order_id=',
            'title' => 'B2B 应收详情',
            'translate'  => ['CLIENT_NAME'],
        ],
        'pending_b2b_to_be_written_off' => [
            'PO_ID' => 'B2B订单号',
            'THR_PO_ID' => '销售PO单号',
            'CLIENT_NAME' => '客户',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=b2b&a=receivable_detail&order_id=',
            'title' => 'B2B 应收详情',
            'translate'  => ['CLIENT_NAME'],
        ],


        'pending_purchase_return' => [
            'return_no' => '采购退货单号',
            'warehouse_cd_val' => '仓库',
            'SP_NAME' => '供应商',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=stock&a=returned_detail&type=undefined&id=',
            'title' => '退货出库详情',
            'translate'  => ['warehouse_cd_val', 'SP_NAME'],
        ],
        'waiting_for_the_purchase_of_the_return_of_the_goods' => [
            'return_no' => '采购退货单号',
            'warehouse_cd_val' => '仓库',
            'SP_NAME' => '供应商',
            'created_by' => '创建人',
            'created_at' => '创建时间',
            'detail_url' => 'index.php?m=stock&a=returned_detail&type=view&id=',
            'title' => '退货出库详情',
            'translate'  => ['warehouse_cd_val', 'SP_NAME'],
        ],
        'to_be_confirmed_and_transferred_to_the_task' => [
            'allo_no' => '调拨单号',
            'allo_out_warehouse' => '调出仓库',
            'allo_in_warehouse' => '调入仓库',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'sku_launch_count' => '调拨总件数',
            'detail_url' => 'index.php?m=allocation_extend&a=confirm_task&id=',
            'title' => '调拨详情',
            'translate'  => ['allo_out_warehouse', 'allo_in_warehouse'],
        ],
        'to_be_confirmed_and_transferred_out_of_the_library_old' => [
            'allo_no' => '调拨单号',
            'allo_out_warehouse' => '调出仓库',
            'allo_in_warehouse' => '调入仓库',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=allocation_extend&a=confirm_outgoing&id=',
            'title' => '调拨详情',
            'translate'  => ['allo_out_warehouse', 'allo_in_warehouse'],
        ],
        'to_be_confirmed_to_transfer_to_the_warehouse_old' => [
            'allo_no' => '调拨单号',
            'allo_out_warehouse' => '调出仓库',
            'allo_in_warehouse' => '调入仓库',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=allocation_extend&a=confirm_storage&id=',
            'title' => '调拨详情',
            'translate'  => ['allo_out_warehouse', 'allo_in_warehouse'],
        ],
        'to_be_confirmed_and_transferred_out_of_the_library' => [
            'allo_no' => '调拨单号',
            'allo_out_warehouse' => '调出仓库',
            'allo_in_warehouse' => '调入仓库',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'sku_launch_count' => '调拨总件数',
            'detail_url' => 'index.php?m=allocation_extend&a=confirm_outgoing&id=',
            'title' => '调拨详情',
            'translate'  => ['allo_out_warehouse', 'allo_in_warehouse'],
        ],
        'to_be_confirmed_to_transfer_to_the_warehouse' => [
            'allo_no' => '调拨单号',
            'allo_out_warehouse' => '调出仓库',
            'allo_in_warehouse' => '调入仓库',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'sku_launch_count' => '调拨总件数',
            'detail_url' => 'index.php?m=allocation_extend&a=confirm_storage&id=',
            'title' => '调拨详情',
            'translate'  => ['allo_out_warehouse', 'allo_in_warehouse'],
        ],
        'pending_offer' => [
            'demand_code' => '需求编号',
            'sell_team_val' => '销售团队',
            'seller' => '销售人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=demands&storageKey=storage0001&type=',
            'title' => '需求详情',
            'translate'  => ['sell_team_val'],
        ],
        'sales_leadership_review_needs' => [
            'demand_code' => '需求编号',
            'sell_team_val' => '销售团队',
            'seller' => '销售人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=demands&storageKey=storage0001&type=',
            'title' => '需求详情',
            'translate'  => ['sell_team_val'],
        ],
        'pending_up_sales_order' => [
            'demand_code' => '需求编号',
            'sell_team_val' => '销售团队',
            'seller' => '销售人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=demands&storageKey=storage0001&type=',
            'title' => '需求详情',
            'translate'  => ['sell_team_val'],
        ],
        'pending_up_purchase_order' => [
            'quotation_code' => '报价编号',
            'purchase_team_val' => '采购团队',
            'purchaser' => '采购人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=purchases&type=',
            'title' => '报价详情',
            'translate'  => ['purchase_team_val'],
        ],
        'pending_sales_order' => [
            'demand_code' => '需求编号',
            'sell_team_val' => '销售团队',
            'seller' => '销售人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=demands&storageKey=storage0001&type=',
            'title' => '需求详情',
            'translate'  => ['sell_team_val'],
        ],
        'pending_purchase_order' => [
            'quotation_code' => '报价编号',
            'purchase_team_val' => '采购团队',
            'purchaser' => '采购人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?g=scm&m=display&a=purchases&type=',
            'title' => '需求详情',
            'translate'  => ['sell_team_val'],
        ],
        'work_order_dealing' => [ // 待处理问题
            'id' => '问题编号',
            'status_val' => '状态',
            'wo_title' => '标题',
            'module_name' => '所在模块',
            'question_user_name' => '创建人',
            'add_time' => '创建时间',
            'detail_url' => 'index.php?g=question&m=question&a=questionDetail&id=',
            'title' => '工单详情',
            'translate'  => ['status_val', 'wo_title', 'module_name'],
        ],
        'work_order_accepting' => [ // 待验收问题
            'id' => '问题编号',
            'status_val' => '状态',
            'wo_title' => '标题',
            'module_name' => '所在模块',
            'question_user_name' => '创建人',
            'add_time' => '创建时间',
            'detail_url' => 'index.php?g=question&m=question&a=questionDetail&id=',
            'title' => '工单详情',
            'translate'  => ['status_val', 'wo_title', 'module_name'],
        ],

        'configure_the_store_affirm' => [
            'plat_name' => '平台',
            'store_name' => '店铺名称',
            'zh_name' => '国家',
            'recently_affirm_time' => '最近确认日期',
            'up_shop_num' => '店铺账号',
            'detail_url' => 'index.php?m=store&a=detail&id=',
            'title' => '待确认店铺信息准确性',
            'translate'  => ['store_name', 'plat_name', 'zh_name'],
        ],

        'configure_the_store_handover' => [
            'plat_name' => '平台',
            'store_name' => '店铺名称',
            'zh_name' => '国家',
            'recently_affirm_time' => '最近确认日期',
            'up_shop_num' => '店铺账号',
            'store_by' => '原店铺负责人',
            'detail_url' => 'index.php?m=store&a=detail&id=',
            'title' => '待确认店铺信息准确性',
            'translate'  => ['store_name', 'plat_name', 'zh_name'],
        ],

        'configure_the_store_income_company' => [
            'plat_name' => '平台',
            'store_name' => '店铺名称',
            'store_by' => '店铺负责人',
            'detail_url' => 'index.php?m=store&a=detail_finance&id=',
            'title' => '待确认店铺收入记录公司',
            'translate'  => ['store_name', 'plat_name', 'zh_name'],
        ],

        'configure_the_store_account_bank' => [
            'plat_name' => '平台',
            'store_name' => '店铺名称',
            'store_by' => '店铺负责人',
            'detail_url' => 'index.php?m=store&a=detail_finance&id=',
            'title' => '待确认店铺收款账号',
            'translate'  => ['store_name', 'plat_name'],
        ],

        'the_requirements_assigned_to_me' => [
            'title'=>'指派给我的需求',
            'storyVersion' => '任务#变动',
            'name' => '需求#标题',
            'openedDate' => '创建时间',
            'estStarted' => '预计开始日期',
            'deadline' => '预计结束时间',
            'status' => '当前状态',
            'assignedTo' => '当前指派',
            'realStarted' => '实际开始时间',
            'detail_url' => 'http://pm.dev.gshopper.com/index.php?m=task&f=view&t=html&id=',
            'translate'  => ['name', 'status'],
        ],

        'gp_order_deal' => [
            'title'=>'Gshopper平台超过24h未派单订单',
            // 站点、平台订单号、商品、下单时间
            'PLAT_NAME' => '站点',
            'ORDER_ID' => '平台订单号',
            'goods_name' => '商品',
            'ORDER_TIME' => '下单时间',
            'detail_url' => '/index.php?g=OMS&m=Order&a=orderDetail&order_no=',
            'translate'  => ['PLAT_NAME', 'goods_name'],
        ],

        //待审核资金划转
        'auditing_transfer_fund' => [
            'transfer_no' => '资金划转编号',
            'pay_company_name' => '转出公司',
            'rec_company_name' => '转入公司',
            'amount_money' => '划转金额',
            'currency_code_val' => '币种',
            'use' => '用途',
            'reason' => '备注',
            'create_user_nm' => '申请人',
            'create_time' => '创建时间',
            'detail_url' => 'index.php?m=finance&a=transferApp&storageKey=storage0002&id=',
            'title' => '转账换汇',
            'translate'  => ['pay_company_name', 'rec_company_name'],
        ],

        //待业务审核平台账单
        'business_audit_bill' => [
            'detail_url_key' => '账单ID',
            'site_name' => '平台',
            'platform_name' => '站点',
            'store_name' => '店铺',
            'import_man' => '导入人',
            'import_time' => '导入时间',
            'detail_url' => 'index.php?m=finance&a=platform_bill_list',
            'title' => '平台账单列表',
            'translate'  => ['site_name', 'platform_name', 'store_name'],
        ],

        //待财务审核账单
        'finance_audit_bill' => [
            'detail_url_key' => '账单ID',
            'site_name' => '平台',
            'platform_name' => '站点',
            'store_name' => '店铺',
            'import_man' => '导入人',
            'import_time' => '导入时间',
            'detail_url' => 'index.php?m=finance&a=platform_bill_list',
            'title' => '平台账单列表',
            'translate'  => ['site_name', 'platform_name', 'store_name'],
        ],

        //待审核TPP退款申请
        'auditing_tpp_refund_apply' => [
            'detail_url_key' => '售后单号',
            'platform_code_val' => '平台',
            'platform_country_code_val' => '站点',
            'store_name' => '店铺',
            'refund_amount' => '支付金额',
            'amount_currency_cd_val' => '币种',
            'created_at' => '申请时间',
            'detail_url' => 'index.php?g=OMS&m=AfterSale&a=refund_detail&after_sale_no=',
            'title' => '退款审核',
            'translate'  => ['platform_code_val', 'platform_country_code_val', 'store_name'],
        ],

        //待审核GP退款申请
        'auditing_gp_refund_apply' => [
            'detail_url_key' => '售后单号',
            'platform_code_val' => '平台',
            'platform_country_code_val' => '站点',
            'store_name' => '店铺',
            'refund_amount' => '支付金额',
            'amount_currency_cd_val' => '币种',
            'created_by' => '申请人',
            'created_at' => '申请时间',
            'detail_url' => 'index.php?g=OMS&m=AfterSale&a=refund_detail&after_sale_no=',
            'title' => '退款审核',
            'translate'  => ['platform_code_val', 'platform_country_code_val', 'store_name', 'created_by'],
        ],
        // 盘点
        'inve_auditing' => [
            'inve_no' => '盘点单号',
            'warehouse_cd_val' => '盘点仓库',
            'status_cd_val' => '盘点状态',
            'created_by' => '盘点操作人',
            'detail_url' => 'index.php?m=stock&a=inventory_detail&idd=',
            'title' => '盘点差异待审核',
            'translate'  => ['status_cd_val', 'warehouse_cd_val'],
        ],
        'inve_fin_confirming' => [
            'inve_no' => '盘点单号',
            'warehouse_cd_val' => '盘点仓库',
            'sec_status_cd_val' => '盘点状态',
            'created_by' => '盘点操作人',
            'detail_url' => 'index.php?m=stock&a=inventory_detail&idd=',
            'title' => '盘点差异待确认',
            'translate'  => ['sec_status_cd_val', 'warehouse_cd_val'],
        ],
        'inve_auditing_again' => [
            'inve_no' => '盘点单号',
            'warehouse_cd_val' => '盘点仓库',
            'sec_status_cd_val' => '盘点状态',
            'created_by' => '盘点操作人',
            'detail_url' => 'index.php?m=stock&a=inventory_detail&idd=',
            'title' => '盘点差异待复核',
            'translate'  => ['warehouse_cd_val', 'sec_status_cd_val'],
        ],
        'inve_checking_again' => [
            'inve_no' => '盘点单号',
            'warehouse_cd_val' => '盘点仓库',
            'sec_status_cd_val' => '盘点状态',
            'created_by' => '盘点操作人',
            'detail_url' => 'index.php?m=stock&a=inventory_detail&idd=',
            'title' => '盘点差异待复盘',
            'translate'  => ['warehouse_cd_val', 'sec_status_cd_val'],
        ],
        'wait_logistics_quote' => [
            'sort_no'    => '序号',
            'quote_no'   => '报价单号',
            'created_by' => '报价发起人',
            'created_at' => '报价发起人',
            'detail_url' => 'index.php?m=quote&a=quotation_management_detail&id=',
            'title'      => '编辑报价',
            'translate'   => ['quote_no'],
        ],
        'wait_operator_confirm_quote' => [
            'sort_no'    => '序号',
            'quote_no'   => '报价单号',
            'created_by' => '报价发起人',
            'created_at' => '报价发起人',
            'detail_url' => 'index.php?m=quote&a=quotation_management_detail&id=',
            'title'      => '编辑报价',
            'translate'   => ['quote_no'],
        ],
        'wait_logistics_twice_quote' => [
            'sort_no'    => '序号',
            'quote_no'   => '报价单号',
            'created_by' => '报价发起人',
            'created_at' => '报价发起人',
            'detail_url' => 'index.php?m=quote&a=quotation_management_detail&id=',
            'title'      => '编辑报价',
            'translate'   => ['quote_no'],
        ],
        'wait_logistics_twice_confirm_quote' => [
            'sort_no'    => '序号',
            'quote_no'   => '报价单号',
            'created_by' => '报价发起人',
            'created_at' => '报价发起人',
            'detail_url' => 'index.php?m=quote&a=quotation_management_detail&id=',
            'title'      => '编辑报价',
            'translate'   => ['quote_no'],
        ],
        'wait_operator_twice_confirm_quote' => [
            'sort_no'    => '序号',
            'quote_no'   => '报价单号',
            'created_by' => '报价发起人',
            'created_at' => '报价发起人',
            'detail_url' => 'index.php?m=quote&a=quotation_management_detail&id=',
            'title'      => '编辑报价',
            'translate'   => ['quote_no'],
        ],
        'promotion_approver' => [
            'M_NAME' => '申请晋升员工',
            'DEPT_NM' => '部门',
            'current_job_name' => '职位',
            'promotion_job_name' => '晋升后职位',
            'promotion_no' => '晋升加薪单号',
            'promotion_raise_type_cd_val' => '类型',
            'detail_url' => 'index.php?m=hr&a=promotionDetail&id=',
            'title' => '待审核晋升单',
            'translate'  => [],
        ],

        'legal_leader_audit' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待领导(转审人)审批合同',
        ],
        'legal_legal_audit' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待法务审批合同',
        ],
        'legal_fin_audit' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待财务审批合同',
        ],
        'legal_upload' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待法务盖章',
        ],
        'legal_upload_sec' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待上传定稿合同',
        ],
        'legal_upload_thr' => [
            'CON_NO' => '合同编号',
            'detail_url' => 'index.php?m=contract&a=contract_view&ID=',
            'CON_COMPANY_CD_val' => '我方公司',
            'SP_NAME'=> '合作公司',
            'created_by' => '申请人',
            'title' => '待法务确认归档',
        ],
       //待确认优惠码
        'await_affirm_coupon' => [
            'promotion_task_no' => '推广任务ID',
            'create_by' => '认领人',
            'create_at' => '认领时间',
            'product_name' => '商品名称',
            'title' => '待确认优惠码',
            'detail_url' => 'index.php?m=management&a=promotion_task_list',
        ],

        //待推广任务
        'await_promotion_task' => [
            'promotion_task_no' => '推广任务ID',
            'create_by' => '需求人',
            'promotion_type_cd_val' => '推广内容类型',
            'channel_platform_name' => '推广平台',
            'channel_medium_name' => '推广媒介',
            'title' => '待推广任务',
            'detail_url' => 'index.php?m=management&a=promotion_task_list',
        ],

        // 查看EXCEL出入库
        'await_improt_bill_audit' => [
            'audit_no' => '审批编号',
            'create_by' => '发起人',
            'create_at' => '发起时间',
            'title' => '查看EXCEL出入库',
            'detail_url' => '/index.php?m=stock',
        ],

        //待现金效率审批
        'waiting_demand_cash_apply' => [
            'demand_code' => '需求编号',
            'sell_team_val' => '销售团队',
            'seller' => '销售人员',
            'create_user' => '创建人',
            'create_time' => '创建时间',
            'title' => '需求详情',
            'detail_url' => 'index.php?g=scm&m=display&a=demands&storageKey=storage0001&type=',
        ],


    ];

    /**
     * @return array
     */
    private static function init()
    {
        $Model = new Model();
        $user = DataModel::userNamePinyin();
        $gshopper_pms = PMS_DATABASE;
        return array($Model, $user, $gshopper_pms);
    }

    public static function legal_leader_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
            (  
                (cc.leader_by = '$user' AND cc.audit_status_cd = 'N003660002')
                OR
                (cc.audit_status_cd = 'N003660004' AND cc.transfer_by = '$user')
            )
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        return $Model->query($sql);
    }
    public static function legal_legal_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
            (
                (cc.audit_status_cd = 'N003660003' AND cc.legal_by = '$user')
            )
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        // p($sql);
        return $Model->query($sql);
    }
    public static function legal_fin_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
                cc.finance_by = '$user'
            AND
                cc.audit_status_cd = 'N003660005'
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        //p($sql);
        return $Model->query($sql);
    }
    public static function legal_upload()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
                cc.seal_by = '$user'
            AND
                cc.audit_status_cd = 'N003660006'
            AND
                cc.audit_status_sec_cd = 'N003760001'
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        //p($sql);
        return $Model->query($sql);
    }
    public static function legal_upload_sec()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
                cc.created_by = '$user'
            AND
                cc.audit_status_cd = 'N003660006'
            AND
                cc.audit_status_sec_cd = 'N003760002'
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        //p($sql);
        return $Model->query($sql);
    }
    public static function legal_upload_thr()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "
            SELECT
            cc.CON_NO,
            cc.ID as detail_url_key,
            cc.created_by,
            css.SP_NAME,
            cmn.CD_VAL as CON_COMPANY_CD_val
            FROM tb_crm_contract cc
            LEFT JOIN tb_crm_sp_supplier css ON css.ID = cc.supplier_id
            LEFT JOIN tb_ms_cmn_cd cmn ON cmn.ETC = cc.CON_COMPANY_CD
            WHERE
                cc.file_by = '$user'
            AND
                cc.audit_status_cd = 'N003660006'
            AND
                cc.audit_status_sec_cd = 'N003760003'
            AND 
                cmn.CD like 'N00124%'
            AND
                cmn.USE_YN = 'Y'
            ORDER
                BY cc.ID DESC;
        ";
        //p($sql);
        return $Model->query($sql);
    }

    public static function inve_auditing()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            wi.inve_no,
            wi.id AS detail_url_key,
            wi.warehouse_cd, 
            wi.status_cd
            wi.created_by
        FROM tb_wms_inventory wi
        LEFT JOIN tb_con_division_warehouse dw ON wi.warehouse_cd = dw.warehouse_cd
        WHERE
            FIND_IN_SET( 
                '$user',
                dw.inventory_by
            )
            AND
            wi.sec_status_cd = 'N003520010'
            AND 
            wi.status_cd = 'N003520002'
        ";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['warehouse_cd', 'status_cd']);

    }

    public static function inve_fin_confirming()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $inveIdList = (new InventoryService())->getInveIdByFinPerson($user);
        if ($inveIdList) {
            $sql = "SELECT
                wi.inve_no,
                wi.id AS detail_url_key,
                wi.warehouse_cd, 
                wi.sec_status_cd,
                wi.created_by
            FROM tb_wms_inventory wi
            WHERE
                wi.id in  ('$inveIdList')
                AND
                wi.status_cd = 'N003520003'
            ";  
            return CodeModel::autoCodeTwoVal($Model->query($sql),['warehouse_cd', 'sec_status_cd']);
        } else {
            return [];
        }
    }

    public static function inve_auditing_again()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            wi.inve_no,            
			wi.id AS detail_url_key,
            wi.warehouse_cd, 
            wi.sec_status_cd,
            wi.created_by
        FROM tb_wms_inventory wi
        LEFT JOIN tb_con_division_warehouse dw ON wi.warehouse_cd = dw.warehouse_cd
        WHERE
            FIND_IN_SET( 
                '$user',
                dw.inventory_by
            )
            AND
            wi.sec_status_cd = 'N003520011'
            AND
            wi.status_cd = 'N003520002'
        ";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['warehouse_cd', 'sec_status_cd']);

    }

    public static function inve_checking_again()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            wi.inve_no,
            wi.id AS detail_url_key,
            wi.warehouse_cd, 
            wi.sec_status_cd,
            wi.created_by
        FROM tb_wms_inventory wi
        LEFT JOIN tb_con_division_warehouse dw ON wi.warehouse_cd = dw.warehouse_cd
        WHERE
            FIND_IN_SET( 
                '$user',
                dw.inventory_operate_by
            )
            AND
            wi.sec_status_cd = 'N003520007'
            AND
            wi.status_cd = 'N003520001'
        ";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['warehouse_cd', 'sec_status_cd']);

    }

    public static function gp_order_deal()
    {
        //站点、平台订单号、商品、下单时间
        //派单状态=待派单/待预排 & 订单归属平台=Gshopper & 订单下单时间距查询待办时间超过24h & 该订单对应的GP站点的负责人包含该用户
        list($Model, $user, $gshopper_pms) = self::init();
        $date  =  date("Y-m-d H:i:s",strtotime("-1 day",time()));
        $sql = "SELECT
                oo.ORDER_ID as detail_url_key,
                oo.PLAT_NAME,
                oo.PLAT_CD,
                oo.ORDER_NO,
                oo.ORDER_TIME,
                j.spu_name as goods_name
            FROM tb_op_order oo
            LEFT JOIN tb_op_order_guds og ON og.ORDER_ID = oo.ORDER_ID
            LEFT JOIN {$gshopper_pms}.product_sku i ON og.B5C_SKU_ID = i.sku_id
            LEFT JOIN {$gshopper_pms}.product_detail j ON j.spu_id = i.spu_id
            LEFT JOIN tb_ms_cmn_cd cc ON cc.CD_VAL = oo.PLAT_NAME 
            WHERE
                (
                    oo.SEND_ORD_STATUS IN ('N001820100', 'N001821000')
                )
            AND (
                    oo.BWC_ORDER_STATUS = 'N000550400'
                )
            AND 
            FIND_IN_SET( 
                '$user',
                cc.ETC
            )
            AND (
                oo.PLAT_NAME like 'Gshopper-%'
            )
            AND (
                cc.CD like 'N00325%'
            )
            AND (
                oo.PARENT_ORDER_ID is NULL
            )
            AND (oo.ORDER_TIME  <=   '{$date}')
            GROUP BY oo.ORDER_ID
            ORDER BY oo.ORDER_TIME DESC";   
        // p($sql);die;
        $query = $Model->query($sql);
        $opOrderMdoel = M('order', 'tb_op_');
        $newQuery = [];
        foreach ($query as $key => $value) {
            $whereMap = [];
            $value['ORDER_ID'] = $value['detail_url_key'];
            $whereMap['PARENT_ORDER_ID'] = $value['detail_url_key'];
            $send_ord_status = $opOrderMdoel->where($whereMap)->getField('SEND_ORD_STATUS'); // 移除拆单情况下，出现母单状态符合，子单状态不符合的订单
            if (!empty($send_ord_status) && ($send_ord_status !== 'N001820100' && $send_ord_status !== 'N001821000')) {
                continue;
            }
            $value['detail_url_key'] = $value['detail_url_key'] . '&thrId=' . $value['ORDER_NO'] . '&platCode=' . $value['PLAT_CD'];
            unset($value['ORDER_NO']);
            unset($value['PLAT_CD']);
            $newQuery[] = $value;
        }
        // p($newQuery);die;
        return $newQuery;
    }

    public static function the_requirements_assigned_to_me()
    {
        $zt = new ZtTaskService();
        $status_mapping = $zt->status_mapping;
        $name = $_SESSION['m_login_huaming'];
        $list = $zt->show($name);
        $data = array();
        if (!empty($list)){
            foreach ($list as $key => $value ){
                if ($key == $name  && !empty($value['data'])){
                    foreach ($value['data'] as $k => $v){
                        $data[] = array(
                            'id' => $v['id'],
                            'storyVersion' => $v['id'].'-'.$v['storyVersion'],
                            'name' => '需求#'.$v['name'],
                            'openedDate' => $v['openedDate'],
                            'estStarted' => $v['estStarted'],
                            'deadline' => $v['deadline'],
                            'status' => $status_mapping[$v['status']],
                            'assignedTo' => $v['assignedTo'],
                            'realStarted' => strtotime($v['realStarted']) > 0  ? $v['realStarted'] : "",
                            'detail_url_key' => $v['id'],
                        );
                    }
                }
            }
        }
        return $data;
    }



    public static function configure_the_store_affirm()
    {
        //10348 待办事项6.0 新增筛选STORE_STATUS = 0 店铺状态 运营中
        list($Model, $user, $gshopper_pms) = self::init();
        $date  =  date("Y-m-d H:i:s",strtotime("-1 month",time()));
        $sql = "SELECT
                tb_ms_store.ID as detail_url_key,
                STORE_NAME AS store_name,
                PLAT_NAME AS plat_name,
                zh_name,
                recently_affirm_time,
                up_shop_num
            FROM
                `tb_ms_store`
            LEFT JOIN tb_ms_user_area ON tb_ms_store.COUNTRY_ID = tb_ms_user_area.id
            WHERE
                store_by =  '{$_SESSION['m_loginname']}'
            AND STORE_STATUS = 0
            AND (recently_affirm_time  <=   '{$date}'   or recently_affirm_time IS NULL)
            AND (handover_by = 0 or handover_by IS NULL)
            ORDER BY
                tb_ms_store.ID DESC";
        //var_dump($sql);die;
        $query = $Model->query($sql);
        foreach ($query as $key => &$value) {
            if (!empty($value['recently_affirm_time'])){
                $value['recently_affirm_time'] = date('Y-m-d', strtotime($value['recently_affirm_time']));
            }
        }
        return $query;
    }


    public static function configure_the_store_handover()
    {
        //10348 待办事项6.0 新增筛选STORE_STATUS = 0 店铺状态 运营中
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                tb_ms_store.ID as detail_url_key,
                STORE_NAME AS store_name,
                PLAT_NAME AS plat_name,
                zh_name,
                recently_affirm_time,
                up_shop_num,
                store_by
            FROM
                `tb_ms_store`
            LEFT JOIN tb_ms_user_area ON tb_ms_store.COUNTRY_ID = tb_ms_user_area.id
            WHERE
                handover_by =  {$_SESSION['userId']}
            AND STORE_STATUS = 0
            ORDER BY
                tb_ms_store.ID DESC";
        $query = $Model->query($sql);
        foreach ($query as $key => &$value) {
            if (!empty($value['recently_affirm_time'])){
                $value['recently_affirm_time'] = date('Y-m-d', strtotime($value['recently_affirm_time']));
            }
        }
        return $query;
    }

    //待确认店铺收入记录公司
    public static function configure_the_store_income_company()
    {
        //10348 待办事项7.0 店铺收入记录公司=空
        list($Model, $user, $gshopper_pms) = self::init();
        //只有登录用户为 Astor.Zhang
        if ($user != 'Astor.Zhang') return [];
        $sql = "SELECT
                tb_ms_store.ID as detail_url_key,
                STORE_NAME AS store_name,
                PLAT_NAME AS plat_name,
                store_by
            FROM
                `tb_ms_store`
            WHERE
                (income_company_cd = '' or income_company_cd is null)
            ORDER BY
                tb_ms_store.ID DESC";
        //var_dump($sql);die;
        $query = $Model->query($sql);
        return $query;
    }

    //待确认店铺收款账号
    public static function configure_the_store_account_bank()
    {
        //10348 待办事项7.0 店铺收款账户=空
        list($Model, $user, $gshopper_pms) = self::init();
        //只有登录用户为 Lily.Ji
        if ($user != 'Lily.Ji') return [];
        $date  =  date("Y-m-d H:i:s",strtotime("-1 month",time()));
        $sql = "SELECT
                tb_ms_store.ID as detail_url_key,
                STORE_NAME AS store_name,
                PLAT_NAME AS plat_name,
                store_by
            FROM
                `tb_ms_store`
            WHERE
                (fin_account_bank_id = 0)
            ORDER BY
                tb_ms_store.ID DESC";
        //var_dump($sql);die;
        $query = $Model->query($sql);
        return $query;
    }

    public static function work_order_dealing()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                        t.id AS detail_url_key,
                        t.status,
                        t.title As wo_title,
                        bn.NAME as module_name,
                        t.question_user_name,
                        t.add_time
                 FROM tb_ms_question t
                 left join bbm_node bn on t.module_name = bn.ID
                 WHERE
                 is_delete != '1'
                 AND 
                 FIND_IN_SET( 
                     '$user',
                     t.opt_user_name
                 )
                 GROUP BY detail_url_key
                 ORDER BY t.add_time DESC";
        $query = CodeModel::autoCodeTwoVal($Model->query($sql),['status']);
        foreach ($query as $key => &$value) {
            $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            $value['id'] = 'Q ' . $value['detail_url_key'];
        }

        return $query;
    }

    public static function work_order_accepting()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                        t.id AS detail_url_key,
                        t.status,
                        t.title AS wo_title,
                        bn.NAME as module_name,
                        t.question_user_name,
                        t.add_time
                 FROM tb_ms_question t
                 left join bbm_node bn on t.module_name = bn.ID
                 WHERE 
                 is_delete != '1'
                 AND
                 ( t.question_user_name = '$user')
                 AND
                 ( t.status = 'N001760300')
                 GROUP BY detail_url_key
                 ORDER BY t.add_time DESC";
        $query = CodeModel::autoCodeTwoVal($Model->query($sql),['status']);
        foreach ($query as $key => &$value) {
            $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            $value['id'] = 'Q ' . $value['detail_url_key'];
        }

        return $query;
    }

    /**
     * @return mixed
     */
    public static function pending_payment_amount_to_be_confirmed()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                        t.id AS detail_url_key,
                        t.payment_no,
                        b.supplier_id,
                        a.prepared_by,
                        t.create_time
                 FROM tb_pur_payment t
                 left join tb_pur_relevance_order a on t.relevance_id=a.relevance_id
                 left join tb_pur_order_detail b on b.order_id=a.order_id
                 left join tb_pur_sell_information c on c.sell_id=a.sell_id
                 left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=b.our_company
                 WHERE ( a.prepared_by = '{$user}' )
                  AND ( t.status = '0' ) AND ( `order_status` = 'N001320300' )
                 ORDER BY t.create_time DESC";
        return $Model->query($sql);
    }

    //采购应付-待审核
    public static function pending_accounting_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT 	
                    pa.id AS detail_url_key,
                    pa.payment_audit_no,
                    pa.our_company_cd,
                    pa.payment_channel_cd,
                    pa.source_cd,
                    pa.payable_currency_cd,
                    pa.payable_amount_after,
                    pa.created_by,
                    pa.created_at
                 FROM tb_pur_payment_audit pa
                 LEFT JOIN tb_con_division_our_company oc ON pa.our_company_cd = oc.our_company_cd
                 WHERE ( pa.current_audit_user = '{$user}' )
                  AND ( status = '4' )
                 ORDER BY created_at DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['our_company_cd', 'payment_channel_cd', 'source_cd', 'payable_currency_cd']);
    }

    /**
     * 采购应付-待付款
     * @return mixed
     */
    public static function pending_purchase_payment_amount()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT 	
                    pa.id AS detail_url_key,
                    pa.payment_audit_no,
                    pa.our_company_cd,
                    pa.source_cd,
                    pa.created_by,
                    pa.created_at
                 FROM tb_pur_payment_audit pa
                 LEFT JOIN tb_con_division_our_company oc ON pa.our_company_cd = oc.our_company_cd
                 WHERE (FIND_IN_SET('{$user}',oc.payment_manager_by)) 
                  AND ( status = '1' )
                 ORDER BY created_at DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['our_company_cd']);
    }

    /**
     * @return mixed
     */
    public static function pending_payment_amount_out_confirmed()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT 	
                    pa.id AS detail_url_key,
                    pa.payment_audit_no,
                    pa.our_company_cd,
                    pa.created_by,
                    pa.created_at
                 FROM tb_pur_payment_audit pa
                 LEFT JOIN tb_con_division_our_company oc ON pa.our_company_cd = oc.our_company_cd
                 WHERE ( FIND_IN_SET('{$user}',oc.payment_manager_by))
                  AND ( status = '2' )
                 ORDER BY created_at DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql),['our_company_cd']);
    }

    /**
     * @return mixed
     */
    public static function pending_purchase_confirmation()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.relevance_id  AS detail_url_key,
                    a.procurement_number,
                    a.online_purchase_order_number,
                    a.supplier_id,
                    t.prepared_by,
                    a.create_time
                FROM
                    tb_pur_relevance_order t
                LEFT JOIN tb_pur_order_detail a ON a.order_id = t.order_id
                LEFT JOIN tb_pur_sell_information b ON b.sell_id = t.sell_id
                LEFT JOIN tb_pur_goods_information c ON c.relevance_id = t.relevance_id
                WHERE
                    (
                        `order_status` IN ('N001320300')
                    )
                AND (
                    `ship_status` = '0'
                    OR `ship_status` = '1'
                )
                AND (t.prepared_by = '{$user}') 
                GROUP BY t.relevance_id
                ORDER BY  a.create_time  DESC             
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_purchase_warehousing()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id  AS detail_url_key,
                    t.warehouse_id,
                    b.online_purchase_order_number,
                    t.bill_of_landing,
                    b.supplier_id,
                    t.create_user,
                    a.prepared_by,
                    t.create_time
                FROM
                    tb_pur_ship t
                LEFT JOIN tb_pur_relevance_order a ON a.relevance_id = t.relevance_id
                LEFT JOIN tb_pur_order_detail b ON b.order_id = a.order_id
                LEFT JOIN tb_pur_ship_goods d ON d.ship_id = t.id
                LEFT JOIN tb_pur_goods_information e ON e.information_id = d.information_id                
                LEFT JOIN tb_con_division_warehouse ON tb_con_division_warehouse.warehouse_cd = t.warehouse
                WHERE
                    (t.warehouse_status = '0' OR t.warehouse_status = '2')
                AND (
                    FIND_IN_SET('{$user}',tb_con_division_warehouse.purchase_warehousing_by)
                )
                GROUP BY t.id
                ORDER BY  t.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function waiting_for_purchase()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT 
                    t.id  AS detail_url_key,
                    t.warehouse_id,
                    b.online_purchase_order_number,
                    t.bill_of_landing,
                    b.supplier_id,
                    t.create_user,
                    t.create_time
                FROM tb_pur_ship t 
                left join tb_pur_relevance_order a on a.relevance_id=t.relevance_id 
                left join tb_pur_order_detail b on b.order_id=a.order_id 
                left join tb_pur_ship_goods d on d.ship_id=t.id left join tb_pur_goods_information e on e.information_id=d.information_id
                
                WHERE ( t.warehouse_status = '0' OR t.warehouse_status = '2' ) AND ( `prepared_by` = '{$user}' )  
                GROUP BY t.id 
                ORDER BY  t.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_confirmation_of_purchase_invoice()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT 
                    t.relevance_id  AS detail_url_key,
                    a.procurement_number,
                    a.online_purchase_order_number,
                    a.supplier_id,
                    t.prepared_by,
                    a.create_time,
                    t.payment_status,
                    t.relevance_id,
                    a.amount_currency
                FROM tb_pur_relevance_order t 
                left join tb_pur_order_detail a on a.order_id=t.order_id left join tb_pur_invoice b on b.relevance_id=t.relevance_id WHERE ( `invoice_status` = '0' OR `invoice_status` = '1'  ) AND ( t.`prepared_by` =  '{$user}' ) AND ( `order_status` = 'N001320300' )
                GROUP BY t.relevance_id
                 ORDER BY  a.create_time DESC
                ";
        $query = $Model->query($sql);
        $query = CodeModel::autoCodeTwoVal($query, ['amount_currency']);
        $relevance_ids = array_column($query, 'relevance_id');
        if (empty($query) || empty($relevance_ids)) {
            return [];
        }
        $invoice_amount_info = (new PurService())->getOnWayInvoiceAmount($relevance_ids);
        foreach ($query as &$value) {
            $value['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$value['payment_status']];
            $value['on_way_invoice_amount'] = $value['amount_currency_val'].' '.$invoice_amount_info[$value['relevance_id']];
        }
        return $query;
    }

    /**
     * @return mixed
     */
    public static function pending_purchase_invoice()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                   b.id  AS detail_url_key,
                   b.action_no,
                   b.invoice_no,
                   a.supplier_id,
                   b.create_user,
                   b.create_time,
                   t.payment_status,
                   t.relevance_id,
                   a.amount_currency
                FROM
                    tb_pur_relevance_order t
                LEFT JOIN tb_pur_order_detail a ON a.order_id = t.order_id
                LEFT JOIN tb_pur_invoice b ON b.relevance_id = t.relevance_id
                LEFT JOIN tb_con_division_our_company ON tb_con_division_our_company.our_company_cd = a.our_company
                WHERE
                    (
                        t.has_invoice_unconfirmed = '1'
                    )
                AND  FIND_IN_SET('{$user}',tb_con_division_our_company.invoice_person_charge_by)
                AND (
                    `order_status` = 'N001320300'
                )
                GROUP BY b.action_no
                ORDER BY  b.create_time DESC
                ";
        $query = $Model->query($sql);
        $query = CodeModel::autoCodeTwoVal($query, ['amount_currency']);
        $relevance_ids = array_column($query, 'relevance_id');
        if (empty($relevance_ids)) {
            return [];
        }
        $invoice_amount_info = (new PurService())->getOnWayInvoiceAmount($relevance_ids);

        foreach ($query as &$value) {
            $action_nos = json_decode($value['invoice_no'], true);
            $value['invoice_no'] = trim(implode(',', array_column($action_nos, 'no')));
            $value['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$value['payment_status']];
            $value['on_way_invoice_amount'] = $value['amount_currency_val'].' '.$invoice_amount_info[$value['relevance_id']];
        }
        return $query;
    }

    /**
     * @return mixed
     */
    public static function pending_confirmation_purchase_return_tally()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id  AS detail_url_key,
                    t.return_no,
                   b.CD_VAL AS warehouse_cd_val,
                   e.SP_NAME,
                   t.created_by,
                   t.created_at
                FROM
                    tb_pur_return t
                LEFT JOIN tb_ms_cmn_cd a ON a.CD = t.status_cd
                LEFT JOIN tb_ms_cmn_cd b ON b.CD = t.warehouse_cd
                LEFT JOIN tb_ms_cmn_cd c ON c.CD = t.purchase_team_cd
                LEFT JOIN tb_ms_cmn_cd d ON d.CD = t.our_company_cd
                LEFT JOIN tb_crm_sp_supplier e ON e.ID = t.supplier_id
                LEFT JOIN tb_ms_user_area f ON f.area_no = t.receive_address_country
                LEFT JOIN tb_ms_user_area g ON g.area_no = t.receive_address_province
                LEFT JOIN tb_ms_user_area h ON h.area_no = t.receive_address_area
                WHERE
                    (t.status_cd = 'N002640200')
                AND (t.created_by = '{$user}')
                 ORDER BY  t.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return int
     */
    public static function pending_b2b_order_delivery()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    tb_b2b_doship.ORDER_ID AS detail_url_key,
                    tb_b2b_doship.PO_ID,
                    tb_b2b_info.THR_PO_ID,
                    tb_b2b_info.CLIENT_NAME,
                    tb_b2b_info.PO_USER,
                    tb_b2b_info.create_time,
                    tb_sell_demand.create_user
                FROM
                    (
                        SELECT
                            t3.ID
                        FROM
                            (
                                SELECT
                                    tb_b2b_doship.ID
                                FROM
                                    (
                                        SELECT
                                            tb_b2b_doship.ID
                                        FROM
                                            (
                                                `tb_b2b_doship`,
                                                `tb_b2b_ship_list`,
                                                `tb_b2b_ship_goods`,
                                                `tb_wms_batch_order`,
                                                `tb_wms_batch`,
                                                `tb_con_division_warehouse`
                                            )
                                        WHERE
                                            (
                                                tb_b2b_ship_list.ORDER_ID = tb_b2b_doship.ORDER_ID
                                                AND tb_b2b_ship_list.ID = tb_b2b_ship_goods.SHIP_ID
                                                AND tb_wms_batch_order.ORD_ID = tb_b2b_doship.PO_ID
                                                AND tb_wms_batch.id = tb_wms_batch_order.batch_id
                                                AND tb_b2b_ship_goods.DELIVERY_WAREHOUSE = tb_con_division_warehouse.warehouse_cd
                                                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.b2b_order_outbound_by )
                                            )
                                        GROUP BY
                                            tb_b2b_doship.ID
                                    ) AS t1,
                                    `tb_b2b_doship`
                                WHERE
                                    tb_b2b_doship.ID = t1.ID
                                UNION
                                    SELECT
                                        tb_b2b_doship.ID
                                    FROM
                                        (
                                            SELECT
                                                tb_b2b_doship.ID
                                            FROM
                                                (
                                                    `tb_b2b_doship`,
                                                    `tb_wms_batch_order`,
                                                    `tb_wms_batch`,
                                                    `tb_con_division_warehouse`
                                                )
                                            WHERE
                                                (
                                                    tb_wms_batch_order.use_type = 1
                                                    AND tb_wms_batch_order.batch_id = tb_wms_batch.id
                                                    AND tb_wms_batch.vir_type = 'N002440100'
                                                    AND tb_b2b_doship.PO_ID = tb_wms_batch_order.ORD_ID
                                                    AND tb_wms_batch_order.delivery_warehouse = tb_con_division_warehouse.warehouse_cd
                                                    AND FIND_IN_SET('{$user}',tb_con_division_warehouse.b2b_order_outbound_by )
                                                )
                                            GROUP BY
                                                tb_b2b_doship.ID
                                        ) AS t2,
                                        `tb_b2b_doship`
                                    WHERE
                                        tb_b2b_doship.ID = t2.ID
                            ) AS t3
                    ) AS t4,
                    `tb_b2b_doship`
                LEFT JOIN tb_b2b_info ON tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID
                LEFT JOIN tb_sell_demand ON tb_b2b_info.PO_ID = tb_sell_demand.demand_code
                WHERE
                    (
                        tb_b2b_doship.shipping_status IN ('1','2')
                    )
                AND (tb_b2b_doship.ID IN (t4.ID))
                GROUP BY tb_b2b_doship.ID
                ORDER BY  tb_b2b_info.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function waiting_for_the_b2b_order_to_be_shipped()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    tb_b2b_doship.ORDER_ID AS detail_url_key,
                    tb_b2b_info.PO_ID,
                    tb_b2b_info.THR_PO_ID,
                    tb_b2b_info.CLIENT_NAME,
                    tb_sell_demand.create_user,
                    tb_b2b_info.create_time
                FROM
                    `tb_b2b_doship`
                LEFT JOIN tb_b2b_info ON tb_b2b_info.ORDER_ID = tb_b2b_doship.ORDER_ID
                LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1
                LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID
                LEFT JOIN tb_sell_demand  ON tb_sell_demand.demand_code = tb_b2b_info.PO_ID
                WHERE
                    (
                        tb_b2b_doship.shipping_status = '1' OR tb_b2b_doship.shipping_status = '2'
                    )
                AND (
                    FIND_IN_SET('{$user}',tb_con_division_client.sales_assistant_by)
                    OR tb_b2b_info.PO_USER = '{$user}'
                )
                ORDER BY  tb_b2b_info.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_b2b_order_receipts()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $now_date = date('Y-m-d') . ' 00:00:00';
        $sql = "SELECT
                    tb_b2b_receivable.order_id  AS detail_url_key,
                    tb_b2b_info.PO_ID,
                    tb_b2b_info.THR_PO_ID,
                    tb_b2b_info.CLIENT_NAME,
                    tb_b2b_info.PO_USER,
                    tb_sell_demand.create_user,
                    tb_b2b_info.create_time
                FROM
                    (
                        tb_b2b_receivable,
                        tb_b2b_info,
                        tb_b2b_order
                    )
                LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME
                AND tb_crm_sp_supplier.DATA_MARKING = 1
                LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID
                LEFT JOIN tb_sell_demand  ON tb_sell_demand.demand_code = tb_b2b_info.PO_ID
                WHERE
                    tb_b2b_receivable.receivable_status IN ('N002540100')
                AND tb_b2b_receivable.created_at >= '2018-08-01 00:00:00'
                AND tb_b2b_info.start_reminding_date_receipt <= '{$now_date}'
                AND (
                    tb_b2b_receivable.order_id = tb_b2b_order.id
                    AND tb_b2b_info.ORDER_ID = tb_b2b_order.id
                    AND (
                        tb_b2b_info.PO_USER = '{$user}'
                        OR  
                        FIND_IN_SET('{$user}',tb_con_division_client.sales_assistant_by)
                    )
                )
                ORDER BY  tb_b2b_info.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return int
     */
    public static function pending_b2b_to_be_written_off()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        if ('qiushuang' != $user) {
//            return [];
        }
        $sql = "SELECT
                    tb_b2b_receivable.order_id AS detail_url_key,           
                    tb_b2b_info.PO_ID,
                    tb_b2b_info.THR_PO_ID,
                    tb_b2b_info.CLIENT_NAME,
                    tb_b2b_info.PO_USER,
                    tb_sell_demand.create_user,
                    tb_b2b_info.create_time
                     FROM (tb_b2b_receivable,`tb_b2b_info`,tb_b2b_order) 
                      LEFT JOIN tb_sell_demand  ON tb_sell_demand.demand_code = tb_b2b_info.PO_ID
                     WHERE ( tb_b2b_receivable.receivable_status IN ('N002540200') ) 
                     AND (  tb_b2b_receivable.created_at >= '2018-08-01 00:00:00'  ) 
                     AND ( tb_b2b_receivable.order_id = tb_b2b_order.id
                      AND tb_b2b_info.ORDER_ID = tb_b2b_order.id ) 
                      AND FIND_IN_SET('{$user}',tb_b2b_info.verification_leader_by)
                     ORDER BY  tb_b2b_info.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_purchase_return()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                   tb_pur_return.id AS detail_url_key,
                   tb_pur_return.return_no,
                   tb_ms_cmn_cd.CD_VAL AS warehouse_cd_val,
                   tb_crm_sp_supplier.SP_NAME,
                   tb_pur_return.created_by,
                   tb_pur_return.created_at                   
                FROM
                    (
                        tb_pur_return,
                        tb_crm_sp_supplier,
                        tb_con_division_warehouse
                    )
                    LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_pur_return.warehouse_cd
                WHERE                    
                        tb_pur_return.outbound_status = '0'                    
                AND (
                    tb_pur_return.supplier_id = tb_crm_sp_supplier.ID
                    AND tb_crm_sp_supplier.DATA_MARKING = 0
                )
                AND tb_con_division_warehouse.warehouse_cd = tb_pur_return.warehouse_cd
                AND tb_ms_cmn_cd.CD = tb_pur_return.warehouse_cd
                AND 
                FIND_IN_SET('{$user}',tb_con_division_warehouse.prchasing_return_by)
                ORDER BY
                    tb_pur_return.created_at DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function waiting_for_the_purchase_of_the_return_of_the_goods()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    tb_pur_return.id AS detail_url_key,
                    tb_pur_return.return_no,
                    tb_ms_cmn_cd.CD_VAL AS warehouse_cd_val,
                    tb_crm_sp_supplier.SP_NAME,
                    tb_pur_return.created_by,
                    tb_pur_return.created_at
                FROM
                    (
                        tb_pur_return,
                        tb_crm_sp_supplier
                    )
                LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_pur_return.warehouse_cd
                WHERE                  
                        tb_pur_return.outbound_status = '0'
                AND (
                    tb_pur_return.created_by = '{$user}'
                )
                AND (
                    tb_pur_return.supplier_id = tb_crm_sp_supplier.ID
                    AND tb_crm_sp_supplier.DATA_MARKING = 0
                )
                ORDER BY
                    tb_pur_return.created_at DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function to_be_confirmed_and_transferred_out_of_the_library_old()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t3.id AS detail_url_key,
                    t3.allo_no,
                    cd1.CD_VAL AS allo_out_warehouse,
                    cd2.CD_VAL AS allo_in_warehouse,
                    t3.create_time,
                    bbm_admin.M_NAME AS create_user
                FROM
                    ((
                        SELECT
                            t2.id,
                            t2.allo_no,
                            t2.allo_type,
                            t2.allo_in_team,
                            t2.allo_in_warehouse,
                            t2.allo_out_team,
                            t2.allo_out_warehouse,
                            t2.create_time,
                            t2.create_user,
                            t2.update_time,
                            t2.state,
                            transfer_type,
                            t2.deleted_by,
                            t2.deleted_at,
                            (
                                SELECT
                                    GROUP_CONCAT(sku_id)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku
                        FROM
                            tb_wms_allo t2
                    ) t3,
                    tb_con_division_warehouse
                )
                LEFT JOIN tb_ms_cmn_cd AS cd1 ON t3.allo_out_warehouse = cd1.CD
                LEFT JOIN tb_ms_cmn_cd AS cd2 ON t3.allo_in_warehouse = cd2.CD
                LEFT JOIN bbm_admin ON bbm_admin.M_ID = t3.create_user
                WHERE
                    (`state` = 'N001970200')
                AND `transfer_type` =  0
                AND tb_con_division_warehouse.warehouse_cd = t3.allo_out_warehouse
                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.transfer_out_library_by )
                AND t3.deleted_by IS NULL
                AND t3.deleted_at IS NULL
                ORDER BY t3.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function to_be_confirmed_to_transfer_to_the_warehouse_old()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t3.id  AS detail_url_key,
                    t3.allo_no,
                    cd1.CD_VAL AS allo_out_warehouse,
                    cd2.CD_VAL AS allo_in_warehouse,
                    t3.create_time,
                    bbm_admin.M_NAME AS create_user
                FROM
                    ((
                        SELECT
                            t2.id,
                            t2.allo_no,
                            t2.allo_type,
                            t2.allo_in_team,
                            t2.allo_in_warehouse,
                            t2.allo_out_team,
                            t2.allo_out_warehouse,
                            t2.create_time,
                            t2.create_user,
                            t2.update_time,
                            t2.state,
                            transfer_type,
                             t2.deleted_by,
                            t2.deleted_at,
                            (
                                SELECT
                                    GROUP_CONCAT(sku_id)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku
                        FROM
                            tb_wms_allo t2
                    ) t3,
                    tb_con_division_warehouse
                )
                LEFT JOIN tb_ms_cmn_cd AS cd1 ON t3.allo_out_warehouse = cd1.CD
                LEFT JOIN tb_ms_cmn_cd AS cd2 ON t3.allo_in_warehouse = cd2.CD
                LEFT JOIN bbm_admin ON bbm_admin.M_ID = t3.create_user
                WHERE
                    (`state` = 'N001970300')
                AND `transfer_type` =  0
                AND tb_con_division_warehouse.warehouse_cd = t3.allo_in_warehouse
                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.transfer_warehousing_by)
                AND t3.deleted_by IS NULL
                AND t3.deleted_at IS NULL
                ORDER BY t3.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function to_be_confirmed_and_transferred_to_the_task()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t3.id AS detail_url_key,
                    t3.allo_no,
                    cd1.CD_VAL AS allo_out_warehouse,
                    cd2.CD_VAL AS allo_in_warehouse,
                    t3.create_time,
                    bbm_admin.M_NAME AS create_user,
                    t3.sku_launch_count,
                    t3.state,
                    cd3.CD_VAL AS state_code,
                    t3.transfer_type
                FROM
                    ((
                        SELECT
                            t2.id,
                            t2.allo_no,
                            t2.allo_type,
                            t2.allo_in_team,
                            t2.allo_in_warehouse,
                            t2.allo_out_team,
                            t2.allo_out_warehouse,
                            t2.create_time,
                            t2.create_user,
                            t2.update_time,
                            t2.state,
                            transfer_type,
                            t2.deleted_by,
                            t2.deleted_at,
                            (
                                SELECT
                                    GROUP_CONCAT(sku_id)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku,
                            (
                                SELECT
                                    sum(demand_allo_num)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku_launch_count
                        FROM
                            tb_wms_allo t2
                    ) t3,
                    tb_con_division_warehouse
                )
                LEFT JOIN tb_ms_cmn_cd AS cd1 ON t3.allo_out_warehouse = cd1.CD
                LEFT JOIN tb_ms_cmn_cd AS cd2 ON t3.allo_in_warehouse = cd2.CD
                LEFT JOIN tb_ms_cmn_cd AS cd3 ON t3.state = cd3.CD
                LEFT JOIN bbm_admin ON bbm_admin.M_ID = t3.create_user
                WHERE
                    (`state` = 'N001970602')
                AND `allo_type` = 1
                AND `transfer_type` = 1
                AND tb_con_division_warehouse.warehouse_cd = t3.allo_out_warehouse
                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.task_launch_by )
                AND t3.deleted_by IS NULL
                AND t3.deleted_at IS NULL
                ORDER BY t3.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function to_be_confirmed_and_transferred_out_of_the_library()
    {
        //调拨状态 运输中
        //出库状态 未完成
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t3.id AS detail_url_key,
                    t3.allo_no,
                    cd1.CD_VAL AS allo_out_warehouse,
                    cd2.CD_VAL AS allo_in_warehouse,
                    t3.create_time,
                    bbm_admin.M_NAME AS create_user,
                    t3.sku_launch_count,
                    t3.state,
                    cd3.CD_VAL AS state_code,
                    t3.transfer_type
                FROM
                    ((
                        SELECT
                            t2.id,
                            t2.allo_no,
                            t2.allo_type,
                            t2.allo_in_team,
                            t2.allo_in_warehouse,
                            t2.allo_out_team,
                            t2.allo_out_warehouse,
                            t2.create_time,
                            t2.create_user,
                            t2.update_time,
                            t2.state,
                            transfer_type,
                            t2.deleted_by,
                            t2.deleted_at,
                            (
                                SELECT
                                    GROUP_CONCAT(sku_id)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku,
                            (
                                SELECT
                                    sum(demand_allo_num)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku_launch_count
                        FROM
                            tb_wms_allo t2
                    ) t3,
                    tb_con_division_warehouse
                )
                LEFT JOIN tb_wms_allo_new_status AS ans ON t3.id = ans.allo_id
                LEFT JOIN tb_ms_cmn_cd AS cd1 ON t3.allo_out_warehouse = cd1.CD
                LEFT JOIN tb_ms_cmn_cd AS cd2 ON t3.allo_in_warehouse = cd2.CD
                LEFT JOIN tb_ms_cmn_cd AS cd3 ON t3.state = cd3.CD
                LEFT JOIN bbm_admin ON bbm_admin.M_ID = t3.create_user
                WHERE
                    (`state` = 'N001970603')
                AND ans.allo_out_status = 0
                AND tb_con_division_warehouse.warehouse_cd = t3.allo_out_warehouse
                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.transfer_out_library_by )
                AND t3.deleted_by IS NULL
                AND t3.deleted_at IS NULL
                ORDER BY t3.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function to_be_confirmed_to_transfer_to_the_warehouse()
    {
        //调拨状态 运输中
        //入库状态 未完成
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t3.id  AS detail_url_key,
                    t3.allo_no,
                    cd1.CD_VAL AS allo_out_warehouse,
                    cd2.CD_VAL AS allo_in_warehouse,
                    t3.create_time,
                    bbm_admin.M_NAME AS create_user,
                    t3.sku_launch_count,
                    t3.state,
                    cd3.CD_VAL AS state_code,
                    t3.transfer_type
                FROM
                    ((
                        SELECT
                            t2.id,
                            t2.allo_no,
                            t2.allo_type,
                            t2.allo_in_team,
                            t2.allo_in_warehouse,
                            t2.allo_out_team,
                            t2.allo_out_warehouse,
                            t2.create_time,
                            t2.create_user,
                            t2.update_time,
                            t2.state,
                            transfer_type,
                            t2.deleted_by,
                            t2.deleted_at,
                            (
                                SELECT
                                    GROUP_CONCAT(sku_id)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku,
                            (
                                SELECT
                                    sum(demand_allo_num)
                                FROM
                                    tb_wms_allo_child t1
                                WHERE
                                    t1.allo_id = t2.id
                            ) AS sku_launch_count
                        FROM
                            tb_wms_allo t2
                    ) t3,
                    tb_con_division_warehouse
                )
                LEFT JOIN tb_wms_allo_new_status AS ans ON t3.id = ans.allo_id
                LEFT JOIN tb_ms_cmn_cd AS cd1 ON t3.allo_out_warehouse = cd1.CD
                LEFT JOIN tb_ms_cmn_cd AS cd2 ON t3.allo_in_warehouse = cd2.CD
                LEFT JOIN tb_ms_cmn_cd AS cd3 ON t3.state = cd3.CD
                LEFT JOIN bbm_admin ON bbm_admin.M_ID = t3.create_user
                WHERE
                    (`state` = 'N001970603')
                AND ans.allo_in_status = 0
                AND tb_con_division_warehouse.warehouse_cd = t3.allo_in_warehouse
                AND FIND_IN_SET('{$user}',tb_con_division_warehouse.transfer_out_library_by)
                AND t3.deleted_by IS NULL
                AND t3.deleted_at IS NULL
                ORDER BY t3.create_time DESC";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_offer()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = " SELECT
                            t.id  AS detail_url_key,
                            t.demand_code,
                            tb_ms_cmn_cd.CD_VAL AS sell_team_val,
                            t.seller,
                            t.create_user,
                            t.create_time
                        FROM
                            tb_sell_demand t
                        LEFT JOIN tb_sell_quotation a ON a.demand_id = t.id
                        LEFT JOIN tb_sell_demand_goods b ON b.demand_id = t.id
                        LEFT JOIN tb_sell_demand_profit h ON h.demand_id = t.id                      
                        LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = t.customer
                        LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID
                        LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = t.sell_team
                        WHERE
                            (t.step IN ('N002120400'))
                        AND (t.STATUS IN ('N002130300'))
                        AND (
                            `seller` = '{$user}'
                            OR FIND_IN_SET('{$user}',tb_con_division_client.sales_assistant_by)
                        )
                        GROUP BY t.id
                        ORDER BY t.create_time DESC
                        ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function sales_leadership_review_needs()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id  AS detail_url_key,
                    t.demand_code ,
                     d.CD_VAL sell_team_val,
                     `seller`,
                     t.create_user,
                    t.create_time
                FROM
                    tb_sell_demand t
                LEFT JOIN tb_sell_quotation a ON a.demand_id = t.id
                LEFT JOIN tb_sell_demand_goods b ON b.demand_id = t.id
                LEFT JOIN tb_ms_cmn_cd c ON c.CD = t.demand_type
                LEFT JOIN tb_ms_cmn_cd d ON d.CD = t.sell_team
                LEFT JOIN tb_ms_cmn_cd e ON e.CD = t.step
                LEFT JOIN tb_ms_cmn_cd f ON f.CD = t. STATUS
                LEFT JOIN tb_ms_cmn_cd g ON g.CD = t.sell_currency
                LEFT JOIN tb_sell_demand_profit h ON h.demand_id = t.id
                LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = customer
                AND tb_crm_sp_supplier.DATA_MARKING = 1
                LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID                
                WHERE
                    (t.step IN('N002120700'))
                AND (t.STATUS IN('N002130300'))
                AND  ( d.ETC LIKE '%{$user}%' )      
                GROUP BY t.id
                ORDER BY t.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_up_sales_order()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id AS detail_url_key,
                    t.demand_code,
                    d.CD_VAL sell_team_val,
                    `seller`,
                    t.create_user,
                    t.create_time
                FROM
                    tb_sell_demand t
                LEFT JOIN tb_sell_quotation a ON a.demand_id = t.id
                LEFT JOIN tb_sell_demand_goods b ON b.demand_id = t.id
                LEFT JOIN tb_ms_cmn_cd c ON c.CD = t.demand_type
                LEFT JOIN tb_ms_cmn_cd d ON d.CD = t.sell_team
                LEFT JOIN tb_ms_cmn_cd e ON e.CD = t.step
                LEFT JOIN tb_ms_cmn_cd f ON f.CD = t. STATUS
                LEFT JOIN tb_ms_cmn_cd g ON g.CD = t.sell_currency
                LEFT JOIN tb_sell_demand_profit h ON h.demand_id = t.id
                LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = customer
                AND tb_crm_sp_supplier.DATA_MARKING = 1
                LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID
                LEFT JOIN {$gshopper_pms}.product_sku i ON b.sku_id = i.sku_id
                LEFT JOIN {$gshopper_pms}.product_detail j ON j.spu_id = i.spu_id
                AND j. LANGUAGE IN ('N000920100', 'N000920200')
                WHERE
                    (t.step IN('N002120900'))
                AND (t.STATUS IN('N002130300'))
                AND (
                    (
                        seller = '{$user}'
                        OR tb_con_division_client.sales_assistant_by = '{$user}'
                    )
                )
                GROUP BY
                    t.id
                ORDER BY
                    t.id DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_up_purchase_order()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id AS detail_url_key,
                    t.quotation_code,
                    cd.CD_VAL AS purchase_team_val,
                    t.purchaser,
                    t.create_user,
                    t.create_time
                FROM
                    tb_sell_quotation t
                LEFT JOIN tb_sell_demand a ON a.id = t.demand_id
                LEFT JOIN tb_sell_quotation_goods b ON b.quotation_id = t.id
                LEFT JOIN tb_sell_demand_goods c ON c.id = b.demand_goods_id
                LEFT JOIN {$gshopper_pms}.product_sku d ON d.sku_id = c.sku_id
                LEFT JOIN {$gshopper_pms}.product_detail e ON e.spu_id = d.spu_id
                AND e. LANGUAGE IN ('N000920100', 'N000920200')
                LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = t.purchase_team
                WHERE
                    (`invalid` = 0)
                AND (t. STATUS IN('N002150300'))
                AND (t.step IN('N002120900'))
                AND (`purchaser` = '{$user}')
                GROUP BY t.id
                ORDER BY t.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_sales_order()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id AS detail_url_key,
                    t.demand_code ,
                    cd.CD_VAL AS sell_team_val,
                    `seller`,
                    t.create_user,
                    t.create_time
                FROM
                    tb_sell_demand t
                LEFT JOIN tb_sell_quotation a ON a.demand_id = t.id
                LEFT JOIN tb_sell_demand_goods b ON b.demand_id = t.id
                LEFT JOIN tb_sell_demand_profit h ON h.demand_id = t.id
                LEFT JOIN {$gshopper_pms}.product_sku i ON b.sku_id = i.sku_id
                LEFT JOIN {$gshopper_pms}.product_detail j ON j.spu_id = i.spu_id
                AND j. LANGUAGE IN ('N000920100', 'N000920200')
                LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = t.sell_team
                WHERE
                    (t.step IN('N002121000'))
                AND (t. STATUS IN('N002130300'))
                AND (t.legal_man = '{$user}')
                GROUP BY t.id
                ORDER BY t.create_time DESC
                ";
        return $Model->query($sql);
    }

    /**
     * @return mixed
     */
    public static function pending_purchase_order()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    t.id AS detail_url_key,
                    t.quotation_code,
                    cd.CD_VAL AS purchase_team_val,
                    t.purchaser,
                    t.create_user,
                    t.create_time
                FROM
                    tb_sell_quotation t
                LEFT JOIN tb_sell_demand a ON a.id = t.demand_id
                LEFT JOIN tb_sell_quotation_goods b ON b.quotation_id = t.id
                LEFT JOIN tb_sell_demand_goods c ON c.id = b.demand_goods_id
                LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = t.purchase_team
                WHERE
                    (`invalid` = 0)
                AND (t. STATUS IN('N002150300'))
                AND (t.step IN('N002121000'))
                AND (
                    t.id IN (
                        SELECT
                            quotation_id
                        FROM
                            tb_sell_action_log
                        WHERE
                            USER = '{$user}'
                        AND quotation_id > 0
                        AND info IN (
                            '需求侧-法务审批',
                            '需求侧-法务盖章',
                            '需求侧-归档PO',
                            '报价侧-法务审批',
                            '报价侧-法务盖章',
                            '报价测-归档PO'
                        )
                    )
                )
                GROUP BY t.id
                ORDER BY t.create_time DESC
                ";
        return $Model->query($sql);
    }

    //待确认调拨费用付款
    public static function wms_pending_to_be_confirmed()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                        t.id AS detail_url_key,
                        t.payment_no,
                        t.cost_sub_cd,
                        t.created_at,
                        t.created_by
                 FROM tb_wms_payment t
                 LEFT JOIN tb_wms_allo wa ON t.allo_id = wa.id
                 LEFT JOIN tb_ms_cmn_cd cd ON wa.allo_in_team = cd.CD
                 WHERE ( t.status = '0' )
                 AND FIND_IN_SET('{$user}', cd.ETC2)
                 ORDER BY t.created_at DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql), ['cost_sub_cd']);
    }

    //待审核调拨费用付款
    public static function wms_pending_business_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $user = $user . '@gshopper.com';
        $sql = "SELECT
                        t.id AS detail_url_key,
                        t.payment_no,
                        t.cost_sub_cd,
                        t.created_at,
                        t.created_by,
                        cd.ETC2 as expense_sure_user
                 FROM tb_wms_payment t
                 LEFT JOIN tb_wms_allo wa ON t.allo_id = wa.id
                 LEFT JOIN tb_ms_cmn_cd cd ON wa.allo_in_team = cd.CD
                 WHERE ( t.status = '6' )
                 AND FIND_IN_SET('{$user}', cd.ETC)
                 ORDER BY t.created_at DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql), ['cost_sub_cd']);
    }

    //待审核资金划转
    public static function auditing_transfer_fund()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $user = $user . '@gshopper.com';
        $sql = "SELECT
                    t1.id AS detail_url_key,
                    `transfer_no`,
                    `pay_company_name`,
                    `rec_company_name`,
                    `currency_code`,
                    `amount_money`,
                    `create_user`,
                    `create_time`,
                    `use`,
                    `reason`,
                    `create_user_nm`
                FROM
                    tb_fin_account_transfer t1
                LEFT JOIN (
                    SELECT
                        *
                    FROM
                        `tb_ms_cmn_cd`
                    WHERE
                        (cd LIKE 'N002000%')
                ) t2 ON t1.current_step = t2.SORT_NO
                WHERE
                    (`state` IN('N001940100'))
                AND FIND_IN_SET('{$user}', ETC)
                ORDER BY
                    t1.id DESC";
        return CodeModel::autoCodeTwoVal($Model->query($sql), ['currency_code']);
    }

    //待业务审核平台账单
    public static function business_audit_bill()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            b1.platform_bill_no AS detail_url_key,
            b1.platform_code,
            b1.site_code,
            b1.store_id,
            b1.created_by AS import_man,
            b1.created_at AS import_time,
            b1.business_audit_man,
            b1.finance_audit_man,
            b1.business_audit_time,
            b1.finance_audit_time,
            b1.bill_status,
            cd1.CD_VAL as platform_name,
            cd2.CD_VAL as site_name,
            s1.STORE_NAME AS store_name
        FROM
            tb_platform_bill b1
        LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = b1.platform_code
        LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = b1.site_code
        LEFT JOIN tb_ms_store s1 ON s1.id = b1.store_id
        WHERE
            (
                b1.bill_status IN ('N003180001')
            )
        AND (
            b1.business_audit_charge_man = '" . $user . "'
        )
        ORDER BY
            b1.id DESC;";
        return $Model->query($sql);
    }

    //待财务审核账单
    public static function finance_audit_bill()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            b1.platform_bill_no AS detail_url_key,
            b1.platform_code,
            b1.site_code,
            b1.store_id,
            b1.created_by AS import_man,
            b1.created_at AS import_time,
            b1.business_audit_man,
            b1.finance_audit_man,
            b1.business_audit_time,
            b1.finance_audit_time,
            b1.bill_status,
            cd1.CD_VAL as platform_name,
            cd2.CD_VAL as site_name,
            s1.STORE_NAME AS store_name
        FROM
            tb_platform_bill b1
        LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = b1.platform_code
        LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = b1.site_code
        LEFT JOIN tb_ms_store s1 ON s1.id = b1.store_id
        WHERE
            (
                b1.bill_status IN ('N003180002')
            )
        AND (
            b1.finance_audit_charge_man = '" . $user . "'
        )
        ORDER BY
            b1.id DESC;";
        return $Model->query($sql);
    }

    //待审核GP退款申请
    public static function auditing_gp_refund_apply()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        //获取用户的GP_售后通知配置平台站点
        $after_sale_notice_platform = self::after_sale_notice_platform($user);
        $sql = "SELECT
			r2.after_sale_no AS detail_url_key,
            r1.order_id,
            r2.order_no,
			r1.platform_code,
			r1.platform_country_code,
			tb_ms_store.STORE_NAME AS store_name,
			gg2.amount_currency_cd,
			gg2.refund_amount,
			r2.created_at,
			r2.created_by,
			r2.audit_status_cd
		FROM
			tb_op_order_after_sale_relevance r1
		LEFT JOIN tb_op_order oo ON oo.ORDER_ID = r1.order_id
		LEFT JOIN tb_op_order_refund r2 ON r1.after_sale_id = r2.id
		LEFT JOIN tb_op_order_refund_detail gg2 ON r2.id = gg2.refund_id
		LEFT JOIN tb_ms_store ON tb_ms_store.ID = oo.STORE_ID
		WHERE
			r1.platform_code IN ('N002620800')
		AND r1.platform_country_code IN ('" . implode("','", $after_sale_notice_platform) ."')
		AND r2.status_code IN ('N002800013')
		AND r1.type = 3
		ORDER BY
			created_at DESC
        ";
        return CodeModel::autoCodeTwoVal($Model->query($sql), ['platform_code', 'platform_country_code', 'amount_currency_cd', 'audit_status_cd']);
    }

    //获取用户的GP_售后通知配置平台站点
    public static function after_sale_notice_platform($user)
    {
        $where ['CD']     = ['like', 'N00325%'];
        $where ['USE_YN'] = ['eq', 'Y'];
        $where_str = "FIND_IN_SET('{$user}', ETC)";
        $model = M('_ms_cmn_cd', 'tb_');
        $after_sale_notice_conf = $model->field('CD_VAL')
            ->where($where_str, null, true)
            ->where($where)
            ->select();
        if (empty($after_sale_notice_conf)) return [];
        $conditions ['CD']     = ['like', 'N000837%'];
        $conditions ['CD_VAL'] = ['in', array_column($after_sale_notice_conf, 'CD_VAL')];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD')
            ->where($conditions)
            ->select();
        return array_column($ret, 'CD');
    }

    //待审核TPP退款申请
    public static function auditing_tpp_refund_apply()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        //获取用户的GP_售后通知配置平台站点
        $platform = self::getUnGshopperPlatform();
        $sql = "SELECT
			r2.after_sale_no AS detail_url_key,
            r1.order_id,
            r2.order_no,
			r1.platform_code,
			r1.platform_country_code,
			tb_ms_store.STORE_NAME AS store_name,
			gg2.amount_currency_cd,
			gg2.refund_amount,
			r2.created_at,
			r2.created_by,
			r2.audit_status_cd
		FROM
			tb_op_order_after_sale_relevance r1
		LEFT JOIN tb_op_order oo ON oo.ORDER_ID = r1.order_id
		LEFT JOIN tb_op_order_refund r2 ON r1.after_sale_id = r2.id
		LEFT JOIN tb_op_order_refund_detail gg2 ON r2.id = gg2.refund_id
		LEFT JOIN tb_ms_store ON tb_ms_store.ID = oo.STORE_ID
		WHERE
			r1.platform_code IN ('" . implode("','", $platform) ."')
		AND r2.status_code IN ('N002800013')
		AND r1.type = 3
		AND tb_ms_store.store_by = '" . $user . "'
		ORDER BY
			created_at DESC
        ";
      
        return CodeModel::autoCodeTwoVal($Model->query($sql), ['platform_code', 'platform_country_code', 'amount_currency_cd', 'audit_status_cd']);
    }
    public static function promotion_approver(){
        $emplId = M('admin', 'bbm_')->where(['M_ID' => DataModel::userId()])->getField('empl_id');

        //        $approverListId = M('hr_promotion_approver', 'tb_')->where(['approver_id' => $emplId, 'result' => 0])->group('promotion_id')->select();
        //        $promotionIds = [];
        //        $promotionIds = array_column($approverListId, 'promotion_id');
        // promotion_raise_type_cd_val
        $data =  M('hr_promotion', 'tb_')
            ->field('tb_hr_promotion.promotion_no,tb_hr_promotion.id as detail_url_key,bbm_admin.M_NAME,tb_hr_dept.DEPT_NM,jobs1.CD_VAL as current_job_name,jobs2.CD_VAL as promotion_job_name,approver.type as approver_status,tb_hr_promotion.status,cd2.CD_VAL as promotion_raise_type_cd_val')
            ->join('bbm_admin on bbm_admin.empl_id = tb_hr_promotion.empl_id')
            ->join('tb_hr_dept on tb_hr_dept.ID = tb_hr_promotion.dept_id')
            ->join('tb_hr_jobs as jobs1 on jobs1.ID = tb_hr_promotion.current_job_id')
            ->join('tb_hr_jobs as jobs2 on jobs2.ID = tb_hr_promotion.promotion_job_id')
            ->join('tb_ms_cmn_cd  on tb_ms_cmn_cd.CD = tb_hr_promotion.status')
            ->join('tb_ms_cmn_cd  as cd2 on cd2.CD = tb_hr_promotion.promotion_raise_type_cd')
            ->join('tb_hr_promotion_approver as approver on approver.promotion_id = tb_hr_promotion.id')
            ->where(['approver.approver_id' => $emplId, 'approver.result'=> 0, 'tb_hr_promotion.status' => ['IN', ['N003630001', 'N003630002', 'N003630003']]])
            ->select();


        foreach($data as $key=>$value){
            if($value['approver_status'] == 'N003630002' && $value['approver_status'] != $value['status']){
                unset($data[$key]);
            }
        }
        return $data;
        // return ($Model->query($sql)
    }
    //获取非Gshopper平台站点
    public static function getUnGshopperPlatform()
    {
        $conditions ['CD']     = [['like', 'N00262%'], ['neq', 'N002620800']];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD')
            ->where($conditions)
            ->select();
        return array_column($ret, 'CD');
    }

    public static function wait_logistics_quote()
    {
        $user_id = DataModel::userId();
        $model = D("Quote/OperatorQuotation");
        ## 判断是否有权限 查询角色
        $service = new QuotationService();
        $directors   = $service->getQuoteDirectors();
        $director_ids = array_column($directors,'id');
        $data = [];
        if(in_array($user_id,$director_ids)) {
            $quotationRepository = new QuotationRepository();
            $where = [
                'a.status_cd'   => ['eq', $model::STATUS_CD_WAIT_QUOTE],
            ];
            $data =   $quotationRepository->get_quote_to_list($where,0);
        }
        return $data;
    }

    public static function wait_operator_confirm_quote()
    {
        $user_id = DataModel::userId();
        $model = D("Quote/OperatorQuotation");
        $quotationRepository = new QuotationRepository();
        $status = $model::STATUS_CD_WAIT_CONFIRM;
        $where = [
            'a.creator_id'  => ['eq', $user_id],
            'a.status_cd'   => ['eq', $status],
        ];
        return  $quotationRepository->get_quote_to_list($where,0);
    }

    public static function wait_logistics_twice_quote()
    {
        $user_id = DataModel::userId();
        $model = D("Quote/OperatorQuotation");
        ## 判断是否有权限 查询角色
        $service = new QuotationService();
        $directors   = $service->getQuoteDirectors();
        $director_ids = array_column($directors,'id');
        $data = [];
        if(in_array($user_id,$director_ids)) {
            $quotationRepository = new QuotationRepository();
            $where = [
                'a.status_cd'   => ['eq',  $model::STATUS_CD_WAIT_QUOTE],
                'a.is_twice_quote'   => ['eq', $model::IS_TWICE_QUOTE_YES],
            ];
            $data = $quotationRepository->get_quote_to_list($where, $model::IS_TWICE_QUOTE_YES);
        }
        return $data;
    }

    public static function wait_operator_twice_confirm_quote()
    {

        $user_id = DataModel::userId();
        $model = D("Quote/OperatorQuotation");
        $quotationRepository = new QuotationRepository();
        $where = [
            'a.creator_id'     => ['eq', $user_id],
            'a.status_cd'       => ['eq', $model::STATUS_CD_WAIT_CONFIRM],
            'a.is_twice_quote'  => ['eq', $model::IS_TWICE_QUOTE_YES],
        ];
        return  $quotationRepository->get_quote_to_list($where, $model::IS_TWICE_QUOTE_YES);
    }

    // 待确认优惠码
    public static function await_affirm_coupon(){
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
            tb_ms_promotion_task.promotion_demand_no,
            tb_ms_promotion_task.promotion_task_no,
            tb_ms_promotion_task.create_by,
            tb_ms_promotion_task.create_at,
            tb_ms_promotion_demand.product_name 
        FROM
            tb_ms_promotion_task
            INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_task.promotion_demand_no = tb_ms_promotion_demand.promotion_demand_no 
        WHERE
            tb_ms_promotion_task.status_cd = 'N003600004'
            AND tb_ms_promotion_demand.create_by = '" . $user . "'
            ";
        $list = $Model->query($sql);
        if ($list){
            foreach ($list as $key => $value){
                $list[$key]['detail_url_key'] = '&status_cd=N003600004&demand_create_by='.$user;
            }
        }
        return $list;
    }

    // 待确认优惠码
    public static function await_promotion_task(){
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
           tb_ms_promotion_task.promotion_task_no,
            tb_ms_promotion_demand.create_by,
            tb_ms_promotion_demand.promotion_type_cd,
            tb_ms_promotion_task.channel_platform_name,
            tb_ms_promotion_task.channel_medium_name
        FROM
            tb_ms_promotion_task
            INNER JOIN tb_ms_promotion_demand ON tb_ms_promotion_task.promotion_demand_no = tb_ms_promotion_demand.promotion_demand_no 
        WHERE
            tb_ms_promotion_task.status_cd = 'N003600003'
            AND tb_ms_promotion_task.create_by = '" . $user . "'
            ";
        $list = $Model->query($sql);
        if ($list){
            $list = CodeModel::autoCodeTwoVal($list,['promotion_type_cd']);
            foreach ($list as $key => $value){
                $list[$key]['detail_url_key'] = '&status_cd=N003600003&create_by='.$user;
            }
        }
        return $list;
    }

    public static function await_improt_bill_audit()
    {
        list($Model, $user, $gshopper_pms) = self::init();
        $sql = "SELECT
                    audit_no,
                    create_by,
                    create_at,
                    type_cd
                FROM
                    `tb_wms_improt_bill_audit` 
                WHERE
                    new_audit_by = '" . $user . "'
               AND  status_cd in ('N003670002','N003670006')     
               ";
        $list = $Model->query($sql);
        if ($list){
            foreach ($list as $key => $value){
                if ($value['type_cd'] == 'N003680002'){
                    $list[$key]['detail_url_key'] = '&a=put_warehouse_approval_view&type='.$value['type_cd']."&auditNo=".$value['audit_no'];
                }else{
                    $list[$key]['detail_url_key'] = '&a=out_warehouse_approval_view&type='.$value['type_cd']."&auditNo=".$value['audit_no'];
                }

            }
        }
        return $list;

    }

    /**
     * 待现金效率审批，放到妙玉todo里面
     * @return array
     */
    public static function waiting_demand_cash_apply()
    {
        $data = [];
        //妙玉的需要获取待现金效率审批的所有数据
        if(in_array($_SESSION['user_id'], [130])){
            $sql = "select a.id as detail_url_key,a.demand_code,a.seller,a.sell_team,b.CD_VAL as sell_team_val,a.create_user,a.create_time from tb_sell_demand a
                    left join tb_ms_cmn_cd b on a.sell_team = b.CD where a.step = 'N002120800'";
            $data = M('sell_demand','tb_')->query($sql);
        }
        return $data;
    }

}
