<?php

class DeductionCompensationDetailModel extends Model
{
    protected $trueTableName = 'tb_pur_deduction_compensation_detail';


    static $turnover_type = [
        'use'       => 1,
        'create'    => 2,
    ];

    static $deduction_type = [
        'supplier_rebate'   => 'N002660200',
        'over_pay'          => 'N002660100',
    ];

}