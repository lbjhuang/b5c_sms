<?php

/**
 * User: yangsu
 * Date: 19/08/12
 * Time: 11:31
 */


/**
 * Class ReportService
 */
class ReportService extends Service
{

    /**
     * @param $data
     *
     * @return mixed
     */
    public function b2bReceivable($data, $is_excel = false)
    {
        $B2bReceivableService = new B2bReceivableService();
        return $B2bReceivableService->getList($data, $is_excel);
    }

    public function updateFromToday()
    {
        $B2bReceivableService = new B2bReceivableService();
        $B2bReceivableService->updateFromToday();
    }

    public function b2bReceivableExport($data, $file_name = null)
    {
        if ($file_name) {
            $Model = new Model();
            $Model->table('tb_excel_b2b_report')
                ->add(['file_name' => $file_name]);
            $file_name = '/opt/b5c-disk/excel/' . $file_name;
        }
        list($res, $temp_res, $temp_res1, $temp_res2) = $this->b2bReceivable($data, true);
        $res = CodeModel::autoCodeTwoVal($res, ['order_currency_cd', 'SALES_TEAM']);
        $exp_cell_name = [
            ["b2b_order_no", L("B2B订单号")],
            ["po_no", L("po单号")],
            ["ship_no", L("发货单号")],
            ["client_name", L("客户名称")],
            ["our_company", L("我方公司")],
            ["SALES_TEAM_val", L("销售团队")],
            ["po_user", L("销售同事")],
            ["order_currency_cd_val", L("订单币种")],
            ["initial_receivabl", L("初始应收(订单币种)")],
            ["remaining_receivabl", L("剩余应收(订单币种)")],
            ["remaining_receivabl_cny", L("剩余应收(CNY)")],
            ["remaining_receivabl_usd", L("剩余应收(USD)")],
            ["due_date", L("应收产生日期")],
            ["from_today", L("距今天数")],
        ];
        
        $this->outputExcel(L('应收报表导出'), $exp_cell_name, $res, $file_name);
    }

    //现存量导出
    public function existingStockExport($params, $file_name = null)
    {
        set_time_limit(0);
        $params = ZUtils::filterBlank($params['post_data']);
        if ($file_name) {
            (new TbExcelReport())->addReportExcel($file_name, TbExcelReport::EXISTING_STOCK);
            $file_name = '/opt/b5c-disk/excel/' . $file_name;
        }
        $model = new StandingExistingModel();
        $response = $model->getBatchData(json_decode($params, true), true);

        $skus = array_column($response, 'skuId');
        $ret = SkuModel::getSkusInfo($skus, $appends = ['spu_name', 'attributes', 'product_sku'], 'N000920100');

        $response = array_map(function ($r) use ($ret) {
            $r ['upcId'] = $ret ['product_sku'][$r ['skuId']]['upc_id'];
            if($ret ['product_sku'][$r ['skuId']]['upc_more']) {
                $upc_more_arr = explode(',', $ret ['product_sku'][$r ['skuId']]['upc_more']);
                array_unshift($upc_more_arr, $r['upcId']);
                $r['upcId'] = implode(',', $upc_more_arr);
            }
            
            $r ['attr'] = $ret ['attributes'][$r ['skuId']];
            $r ['gudsNm'] = $ret ['spu_name'][$ret ['product_sku'][$r ['skuId']]['spu_id']];
            return $r;
        }, $response);
        $exp_cell_name = [
            ['skuId', L('SKU编码')],
            ['upcId', L('条形码')],
            ['gudsNm', L('商品名称')],
            ['attr', L('属性')],
            ['warehouse', L('仓库')],
            ['batchCode', L('批次号')],
            ['ourCompany', L('所属公司')],
            //['ascription_store_val', L('归属店铺')],
            ['saleTeam', L('销售团队')],
            ['smallSaleTeam', L('销售小团队')],
            ['purNum', L('采购单号')],
            ['purTeam', L('采购团队')],
            ['purStorageDate', L('采购入库时间')],
            ['addTime', L('入库时间')],
            ['isDrug', L('是否滞销')],
            ['existedDays', L('总在库天数')],
            ['currentExistedDays', L('当前仓库在库天数')],
            ['deadLineDate', L('到期日')],
            ['amountTotalNum', L('在库库存')],
            ['amountSaleNum', L('可售')],
            ['amountOccupiedNum', L('占用')],
            ['amountLockingNum', L('锁定')],
            ['unitPrice', L('采购单价（CNY，含增值税）')],
            ['unitPriceUsd', L('采购单价（USD，含增值税）')],
            ['unitPriceNoTax', L('采购单价（CNY，不含增值税）')],
            ['unitPriceUsdNoTax', L('采购单价（USD，不含增值税）')],
            ['poLogCost', L('PO内费用单价（CNY）')],
            ['logServiceCost', L('服务费用单价（CNY）')],
            ['carryCost', L('运输费用单价（CNY）')],
            ['warehouseCost', L('仓储费用单价（CNY）')],
            ['pur_currency', L('采购币种')],
            ['unit_price_origin', L('采购单价（采购币种，含增值税）')],
            ['unit_price_no_tax_origin', L('采购单价（采购币种，不含增值税）')],
            ['po_cost_origin', L('PO内费用单价（采购币种）')],
            ['productType', L('商品类型')],
            ['is_oem_brand', L('是否ODM')],
        ];
        $this->outputExcel(L('现存量导出'), $exp_cell_name, $response, $file_name);
    }

}