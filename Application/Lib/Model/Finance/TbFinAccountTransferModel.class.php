<?php

class TbFinAccountTransferModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_transfer';

    public static $status_wait_collection = 'N001940300';//待收款
    public static $status_wait_pay = 'N001940200';//待付款
    public static $status_wait_accounting = 'N001940501';//待会计审核
    public static $status_audit_fail = 'N001940500';//审批失败
}