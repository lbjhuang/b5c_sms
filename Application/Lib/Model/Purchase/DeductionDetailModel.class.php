<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 17:08
 */

class DeductionDetailModel extends Model
{
    protected $trueTableName = 'tb_pur_deduction_detail';

    static $order_type = [
        'purchase' => 'N001950100',
    ];

    static $turnover_type = [
        'use'       => 1,
        'create'    => 2,
    ];

    static $deduction_type = [
        'supplier_rebate'   => 'N002660200',
        'over_pay'          => 'N002660100',
    ];

}