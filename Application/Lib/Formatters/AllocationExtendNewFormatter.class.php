<?php
/**
 * User: yangsu
 * Date: 19/6/26
 * Time: 14:32
 */

class AllocationExtendNewFormatter extends Formatter
{
    public function formatterAlloInfo($data)
    {
        $data['info']['total_value_goods'] = number_format($data['info']['total_value_goods'], 2);
        $data['info']['total_sales'] = number_format($data['info']['total_sales'], 2);
        $data['info']['service_fee'] = number_format($data['info']['service_fee'], 2);
        $data['info']['logistics_costs'] = number_format($data['info']['logistics_costs'], 2);
        $data['info']['tariff_sum'] = number_format($data['info']['tariff_sum'], 2);
        $data['info']['transfer_use_type_val'] = CodeModel::getTransferUseTypeCode()[$data['info']['transfer_use_type']];
        $data['info']['allo_out_status_val'] = CodeModel::getAlloOutStatusCode()[$data['info']['allo_out_status']]['CD_VAL'];
        $data['info']['allo_in_status_val'] = CodeModel::getAlloOutStatusCode()[$data['info']['allo_in_status']]['CD_VAL'];
        $data['info']['transfer_use_type_val'] = CodeModel::getTransferUseTypeCode()[$data['info']['transfer_use_type']];
        $data['out_stocks'] = (array)$data['out_stocks'];
        $data['in_stocks'] = (array)$data['in_stocks'];
        $data['work']['operating_expenses_cny'] = number_format($data['work']['operating_expenses_cny'], 2);
        $this->numberFormat($data['work']['value_added_service_fee_cny']);
        return $data;
    }
}