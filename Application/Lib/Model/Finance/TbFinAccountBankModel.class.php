<?php

class TbFinAccountBankModel extends BaseModel
{
    protected $trueTableName = 'tb_fin_account_bank';

    public static function getCurrencyCdByBankAccount($bank_account_id)
    {
        return (new self())->where(['id'=>trim($bank_account_id)])->getField('currency_code');
    }
}