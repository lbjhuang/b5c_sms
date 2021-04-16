<?php
/**
 * 仓库出入库处理
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/7/3
 * Time: 17:32
 */

class WarehouseIOModel
{
    /**
     * 入库数据处理
     * @param mixed $response 出库数据
     * @return mixed streamId => batchId
     */
    public function storage($response)
    {
        $r = $saveBatchIdToStream = null;
        foreach ($response ['batch'] as $num => $value) {
            foreach ($value ['data'] as $key => $value) {
                $saveBatchIdToStream [$value ['streamId']] = $value ['id'];
            }
        }

        return $saveBatchIdToStream;
    }

    /**
     * 出库数据处理
     * @param mixed $response 入库数据
     * @param bool $returnData 是否返回stream数据
     * @return array|null
     */
    public function outgoing($response, $returnData = false)
    {
        $stream = $r = $saveData = null;
        if (!empty($response)) {
            // 单次SKU出库信息
            foreach ($response as $key => $value) {
                // 单次SKU涉及到多批次时的信息
                foreach ($value ['exportDetail'] as $batchKey => $batch) {
                    $tmp = null;
                    $tmp = $batch ['stream'];
                    // stream info
                    $r ['bill_id']               = $value ['exportRequest']['billId'];
                    $r ['line_number']           = $tmp ['lineNumber'];
                    $r ['goods_id']              = $tmp ['goodsId'];
                    $r ['GSKU']                  = $tmp ['gsku'];
                    $r ['should_num']            = $batch ['num'];
                    $r ['send_num']              = $batch ['num'];
                    $r ['warehouse_id']          = $tmp ['warehouseId'];
                    $r ['location_id']           = $tmp ['locationId'];
                    $r ['batch']                 = $tmp ['batch'];
                    $r ['deadline_date_for_use'] = date('Y-m-d', substr($tmp ['deadlineDateForUse'], 0, 10));
                    $r ['unit_price_usd']        = $tmp ['unitPriceUsd'];
                    $r ['unit_price']            = $tmp ['unitPrice'];
                    $r ['no_unit_price']         = $tmp ['noUnitPrice'];
                    $r ['taxes']                 = $tmp ['taxes'];
                    $r ['unit_money']            = $tmp ['unitPrice'] * $batch ['num'];
                    $r ['no_unit_money']         = $tmp ['noUnitPrice'] * $batch ['num'];
                    $r ['duty']                  = $tmp ['duty'];
                    $r ['currency_id']           = $tmp ['currencyId'];
                    $r ['give_status']           = $tmp ['giveStatus'];
                    $r ['add_time']              = $tmp ['addTime'];
                    $r ['digit']                 = $tmp ['digit'];
                    $r ['currency_time']         = $tmp ['currencyTime'];
                    $r ['up_flag']               = $tmp ['upFlag'];
                    $r ['GSKU_back']             = $tmp ['gskuBack'];
                    $r ['outgoing_type']         = $tmp ['outgoingType'];
                    $r ['reported_loss_reason']  = $tmp ['reportedLossReason'];
                    $r ['batch']                 = $batch ['batchId'];
                    $r ['pur_invoice_tax_rate']  = $tmp ['purInvoiceTaxRate'];
                    $r ['proportion_of_tax']     = $tmp ['proportionOfTax'];
                    $r ['storage_log_cost']      = $tmp ['storageLogCost'];
                    $r ['log_service_cost']      = $tmp ['logServiceCost'];
                    $r ['pur_storage_date']      = $tmp ['purStorageDate'];
                    $r ['create_time']           = date('Y-m-d H:i:s', time());
                    $r ['tag'] = 0;
                    $stream []     = $r;
                    $r             = null;
                    // bill batch info
                    $r ['num']     = $batch ['num'];
                    $r ['batchId'] = $batch ['batchId'];
                    $saveData [$value ['data']['billId']] [] = $r;
                    $r = null;
                }
            }
        }
        $model = new Model();
        if ($returnData) {
            return $stream;
        } else {
            if ($stream) {
                $tag = $model->table('tb_wms_stream')->addAll($stream);
            }
        }

        return $tag;
    }

    /**
     * 出库数据处理
     * @param mixed $response 入库数据
     * @param bool $returnData 是否返回stream数据
     * @return array|null
     */
    public function outgoingExtend($response, $returnData = false)
    {
        $stream = $r = $saveData = null;
        if (!empty($response)) {
            // 单次SKU出库信息
            foreach ($response as $key => $value) {
                // 单次SKU涉及到多批次时的信息
                foreach ($value ['exportDetail'] as $batchKey => $batch) {
                    $tmp = null;
                    $tmp = $batch ['stream'];
                    // stream info
                    $r ['bill_id']               = $value ['data']['billId'];
                    $r ['line_number']           = $tmp ['lineNumber'];
                    $r ['goods_id']              = $tmp ['goodsId'];
                    $r ['GSKU']                  = $tmp ['gsku'];
                    $r ['should_num']            = $batch ['num'];
                    $r ['send_num']              = $batch ['num'];
                    $r ['warehouse_id']          = $tmp ['warehouseId'];
                    $r ['location_id']           = $tmp ['locationId'];
                    $r ['batch']                 = $tmp ['batch'];
                    $r ['deadline_date_for_use'] = date('Y-m-d', substr($tmp ['deadlineDateForUse'], 0, 10));
                    $r ['unit_price_usd']        = $tmp ['unitPriceUsd'];
                    $r ['unit_price']            = $tmp ['unitPrice'];
                    $r ['no_unit_price']         = $tmp ['noUnitPrice'];
                    $r ['taxes']                 = $tmp ['taxes'];
                    $r ['unit_money']            = $tmp ['unitMoney'];
                    $r ['no_unit_money']         = $tmp ['noUnitMoney'];
                    $r ['duty']                  = $tmp ['duty'];
                    $r ['currency_id']           = $tmp ['currencyId'];
                    $r ['give_status']           = $tmp ['giveStatus'];
                    $r ['add_time']              = $tmp ['addTime'];
                    $r ['digit']                 = $tmp ['digit'];
                    $r ['currency_time']         = $tmp ['currencyTime'];
                    $r ['up_flag']               = $tmp ['upFlag'];
                    $r ['GSKU_back']             = $tmp ['gskuBack'];
                    $r ['outgoing_type']         = $tmp ['outgoingType'];
                    $r ['reported_loss_reason']  = $tmp ['reportedLossReason'];
                    $r ['batch']                 = $batch ['batchId'];
                    $r ['pur_invoice_tax_rate']  = $tmp ['purInvoiceTaxRate'];
                    $r ['proportion_of_tax']     = $tmp ['proportionOfTax'];
                    $r ['storage_log_cost']      = $tmp ['storageLogCost'];
                    $r ['log_service_cost']      = $tmp ['logServiceCost'];
                    $r ['pur_storage_date']      = $tmp ['purStorageDate'];
                    $r ['create_time']           = date('Y-m-d H:i:s', time());
                    $r ['tag'] = 0;
                    $stream []     = $r;
                    $r             = null;
                    // bill batch info
                    $r ['num']     = $batch ['num'];
                    $r ['batchId'] = $batch ['batchId'];
                    $saveData [$value ['data']['billId']] [] = $r;
                    $r = null;
                }
            }
        }
        if ($returnData) {
            return $stream;
        } else {
            if ($stream) {
                $model = new Model();
                $tag = $model->table('tb_wms_stream')->addAll($stream);
            }
        }

        return $tag;
    }

    /**
     * 更新批次id到stream表
     * @param array $batch 批次相关数据
     */
    public function updateStream($batch)
    {
        $model = new Model();
        foreach ($batch as $streamId => $batchId) {
            $model->table('tb_wms_stream')->where('id = ' . $streamId)->save(['batch' => $batchId]);
        }
    }
}