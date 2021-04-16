<?php

/**
 * Class PatchExtendModel
 */
class PatchExtendModel extends AbstractRequestModel
{
    /**
     * 取消拆单接口
     * @param $data array
     * @return mixed
     */
    public function cancellationOrder($data)
    {
        $requestUri = 'erp_order/operate.json';

        foreach ($data as $key => $value) {
            $tmp [] = [
                'platCd' => $value ['PLAT_CD'],
                'orderId' => $value ['ORDER_ID']
            ];
        }
        $requestData = [
            'processCode' => 'RELEASE_ORDER',
            'processId' => create_guid(),
            'data' => $tmp
        ];
        $this->submitRequest($requestUri, $requestData);
    }

    /**
     * 拆单
     * @param array $data
     */
    public function dismantling($data)
    {
        $requestUri = 'erp_order/operate.json';

        foreach ($data as $key => $value) {
            $guds = null;
            foreach ($value ['guds'] as $i => $v) {
                $guds [] = [
                    'sku' => $v ['sku_id'],
                    'num' => $v ['item_count'],
                    'deliveryWarehouse' => $value ['warehouse']
                ];

            }
            $r [$value ['order_id'] . $value ['plat_cd']]['platCd'] = $value ['plat_cd'];
            $r [$value ['order_id'] . $value ['plat_cd']]['parentOrderId'] = $value ['order_id'];
            $r [$value ['order_id'] . $value ['plat_cd']]['order'][] = [
                'orderId' => $value ['child_order_id'],
                'orderGuds' => $guds,
            ];
        }
        foreach ($r as $p => $v) {
            $rst [] = $v;
        }
        $requestData = [
            'processCode' => 'SEPARATE_ORDER',
            'processId' => create_guid(),
            'data' => $rst
        ];
        $this->submitRequest($requestUri, $requestData);
    }

    /**
     * Excel 入库
     * @param array $data
     */
    public function excelImport($data)
    {
        $requestUri = 'batch/update_total.json';

        $requestData = $data;

        $this->submitRequest($requestUri, $requestData);
    }

    /**
     * Excel 出库
     * @param array $data
     */
    public function excelExport($data)
    {
        $requestUri = 'batch/export2.json';

        $requestData = [
            'processCode' => 'EXCEL_EXPORT',
            'processId' => create_guid(),
            'data' => $data
        ];

        $this->submitRequest($requestUri, $requestData);
    }
}