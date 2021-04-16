<?php

class InventoryExcelModel extends BaseImportExcelModel
{
    public function fieldMapping()
    {
        return [
            'location_code' => ['field_name' => L('货位号'), 'key' => 'A', 'required' => true],
            'skuId' => ['field_name' => L('SKU编码'), 'key' => 'B', 'required' => true],
            'upcId' => ['field_name' => L('条形码'), 'key' => 'C', 'required' => false],
            'actual_num' => ['field_name' => L('实际盘点数量'), 'key' => 'D', 'required' => true],
            'sp_team_cd' => ['field_name' => L('采购团队CODE'), 'key' => 'E', 'required' => false],
            'con_company_cd' => ['field_name' => L('采购团队所属公司CODE'), 'key' => 'F', 'required' => false],
            'sale_team_cd' => ['field_name' => L('销售团队CODE'), 'key' => 'G', 'required' => true],
            'small_sale_team_cd' => ['field_name' => L('销售小团队CODE'), 'key' => 'H', 'required' => false],
            'pur_invoice_tax_rate' => ['field_name' => L('采购发票税率'), 'key' => 'I', 'required' => false],
            'proportion_of_tax' => ['field_name' => L('退税比率'), 'key' => 'J', 'required' => false],
            'goods_type_cd' => ['field_name' => L('商品类型'), 'key' => 'K', 'required' => true],
        ];
    }

    /**
     * @param int    $row_index    行坐标
     * @param int    $column_index 列坐标
     * @param string $value        值
     *
     * @return null
     */
    public function valid($row_index, $column_index, $value)
    {
        parent::valid($row_index, $column_index, $value);
    }

    public $cacheSku;

    public function getTitle($mode = null)
    {
        $fields = $this->fieldMapping();
        foreach ($this->firstCellRowData as $key => $value) {
            foreach ($fields as $k => $v) {
                if ($v['key'] == $key) {
                    $temp [$key] ['db_field'] = $k;
                    $temp [$key] ['required'] = $v['required'];
                }
            }
        }
        $this->title = $temp;
    }

    /**
     * 读取excel中的数据
     *
     */
    public function getData()
    {
        $data = [];
        $this->excel->max_column_int = '11';
        // read
        for ($this->excel->start_row; $this->excel->start_row <= $this->excel->max_row; $this->excel->start_row ++) {
            $data [$this->excel->start_row] = $this->getCellData($this->excel->start_row);
        }
        // save
        $r = [];
        foreach ($data as $key => $value) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp [$k]['db_field'] = $this->title [$k]['db_field'];
                if ($temp [$k]['db_field'] == 'skuId' && isset($this->cacheSku [$v])) {
                    $this->errorinfo [$key.$k] = $v . L(':与[' . $this->cacheSku [$v] . ']存在重复项');
                } else {
                    $this->cacheSku [$v] = $key . $k;
                }
                $temp [$k]['value'] = $v;
            }
            $r [$key] = $temp;
        }
        $this->data = $r;
        unset($data, $r);
    }

    public function getWarehouseArrList($warehouse_cd)
    {
        $where['USE_YN'] = "Y";
        $where['CD'] = array(array('like','N00068%'), array('neq', $warehouse_cd), 'and'); 
        $res = M('ms_cmn_cd', 'tb_')->field('CD')->where($where)->select();
        $res = array_column($res, 'CD');
        return implode("','",$res);

    }

    // 根据现存量sku维度的sql改造
    public function getSkuInfoByWarehouseCd($warehouse_cd, $sku, $isAll = false)
    {
        if (!$isAll) {
            $addSql = " AND t1.total_inventory > 0 ";
        }
        $sql = "SELECT
    t1.SKU_ID AS skuId,
    t9.upc_id AS upcId,
    t9.upc_more AS upcMore
FROM
    tb_wms_batch t1
    LEFT JOIN (
    SELECT
        tab1.spu_id,
        tab1.sku_id,
        tab1.upc_id,
        tab1.sku_states,
        tab1.upc_more 
    FROM
        ". PMS_DATABASE .".product_sku tab1 
    GROUP BY
        tab1.sku_id 
    ) t9 ON t9.sku_id = t1.SKU_ID
    LEFT JOIN tb_wms_bill t11 ON t1.bill_id = t11.id
WHERE
    ( t11.type = 1 AND t1.vir_type != 'N002440200' AND t1.vir_type != 'N002410200' AND ( t1.SKU_ID = '{$sku}' ) ) 
    AND (
        t11.warehouse_id IN (
            '{$warehouse_cd}' 
        ) 
    )
    ".$addSql." 
GROUP BY
    t1.SKU_ID 
    LIMIT 0,1";
        $ret = M()->query($sql);
        $ret = SkuModel::getInfo($ret, 'skuId', ['spu_name', 'image_url', 'attributes'], ['spu_name' => 'gudsName', 'image_url' => 'imageUrl', 'attributes' => 'optAttr']);
        foreach ($ret as $k => &$v) {
            if($v['upcMore']) {
                $upc_more_arr = explode(',', $v['upcMore']);
                array_unshift($upc_more_arr, $v['upcId']);
                $v['upcId'] = implode(",\r", $upc_more_arr);
            }
        }
        return $ret;
    }

    public function checkSmallTeamCode($tmp)
    {
        $res = true;
        if ($tmp['small_sale_team_cd']) {
            $smallTeamArr = CodeModel::getSellSmallTeamCodeArr($tmp['sale_team_cd']);
            if (!$smallTeamArr) {
                return false;
            }
            $smallTeamArr = array_column($smallTeamArr, 'CD');
            if (!in_array($tmp['small_sale_team_cd'], $smallTeamArr)) {
                return false;
            }
        }
        return $res;
    }

    /**
     * 数据再组装
     */
    public function packData($request_data)
    {
        $data = [];
        $warehouse_cd = $request_data['warehouse_cd'];
        $warehouseArr = $this->getWarehouseArrList($warehouse_cd);
        foreach ($this->data as $key => $value) {
            $tmp = $sku = null;
            foreach ($value as $r => $v) {
                if ($v ['db_field'] == 'skuId') {
                    $sku = $v ['value'];
                }
                if ($v ['db_field'] == 'actual_num') {
                    if (!DataModel::checkPositiveInteger($v['value'])) {
                        $this->errorinfo [$key.$r] = "该SKU实际盘点数量{$v['value']}请填写正整数";
                    }
                }
                $tmp [$v ['db_field']] = $v ['value'];
            }
            if (!$this->checkSmallTeamCode($tmp)) {
                $this->errorinfo[$key] = "销售小团队{$tmp['small_sale_team_cd']}不属于该销售团队{$tmp['sale_team_cd']}，请先核实";
            }
            if ($_POST['inve_sku']) {
                if (!strstr($_POST['inve_sku'], $tmp['skuId'])) {
                    $this->errorinfo[$key] = "导入的SKU值{$tmp['skuId']}不属于该盘点单SKU盘点范围{$_POST['inve_sku']}，请先核实";
                }
            }
            /*if ($_POST['sale_team_cd']) {
                if (!strstr($_POST['sale_team_cd'], $tmp['sale_team_cd'])) {
                    $this->errorinfo[$key] = "导入的销售团队值{$tmp['sale_team_cd']}不属于该盘点单销售团队盘点范围{$_POST['sale_team_cd']}，请先核实";
                }
            }*/
            $response = [];  $respon = [];
            // 采购团队，采购团队所属公司，采购发票税率，退税比率
            $response = $this->getSkuInfoByWarehouseCd($warehouse_cd, $tmp['skuId'], true); // 看盘点仓库中，是否存在无库存的sku
            if ($response) {
                $respon = (new InventoryRepository())->getInveGoodsPhoto(['inve_id' => $_POST['inve_id'], 'sku_id' => $tmp['skuId'], 'goods_type_cd' => $tmp['goods_type_cd']], 'id'); // 根据sku + 商品类型 维度来判断是否存在
                //$respon = $this->getSkuInfoByWarehouseCd($warehouse_cd, $tmp['skuId']);
                if ($respon) {
                    $this->errorinfo [$key] = "该SKU该商品类型已存在该仓库中(数量大于0)无需额外导入";
                }
            } else { // 本仓没有看外仓
                $response = $this->getSkuInfoByWarehouseCd($warehouseArr, $tmp['skuId'], true); // 指系统中，除去盘点仓库之外的仓库，是否存在该sku
                if (!$response) {
                    $this->errorinfo [$key] = "该SKU格式有误，请先核实(本仓与外仓皆没有该SKU)";
                }
                
            }
            $response = $response[0];
            if ($response && $tmp['upcId']) {
                if (!strstr($response['upcId'], $tmp['upcId'])) {
                    $this->errorinfo [$key] = "该SKU的条形码有误，请先核实";
                }
            }
            
            $tmp['amountTotalNum'] = 0;
            $tmp['amountSaleNum'] = 0;
            $tmp['amountOccupiedNum'] = 0;
            $tmp['amountLockingNum'] = 0;
            $tmp['origin_num'] = 0; // 该仓库本身没有该SKU或SKU值数量为0，而实际盘点出该SKU该仓库不为0，所以这几个值默认填0即可
            $tmp['imageUrl'] = $response['imageUrl']; // 图片
            $tmp['gudsName'] = $response['gudsName'];
            $tmp['optAttr'] = $response['optAttr'];
            $tmp['sku_id'] = $sku;
            $tmp = $this->getDefaultData($tmp);
            $data [$sku] = $tmp;
        }
        $this->data = $data;
    }

    public function getDefaultData($tmp)
    {
        if (!$tmp['sp_team_cd'] || !$tmp['con_company_cd'] || !$tmp['pur_invoice_tax_rate'] || !$tmp['proportion_of_tax']) {
            $maxBatchRes = (new InventoryService())->getMaxBatchInfo($tmp['skuId']);
        }
        $tmp['sp_team_cd'] = $tmp['sp_team_cd'] ? $tmp['sp_team_cd'] : $maxBatchRes['purTeam'];
        $tmp['con_company_cd'] = $tmp['con_company_cd'] ? $tmp['con_company_cd'] : $maxBatchRes['ourCompany'];
        $tmp['pur_invoice_tax_rate'] = $tmp['pur_invoice_tax_rate'] ? $tmp['pur_invoice_tax_rate'] : $maxBatchRes['pur_invoice_tax_rate'];
        $tmp['proportion_of_tax'] = $tmp['proportion_of_tax'] ? $tmp['proportion_of_tax'] : $maxBatchRes['proportion_of_tax'];
        return $tmp;
    } 

    /**
     * 导入主入口函数
     *
     */
    public function import($request_data)
    {
        ini_set('max_execution_time', 18000);
        ini_set('memory_limit', '512M');
        parent::import();
        $this->packData($request_data);

        if ($this->errorinfo) {
            $code = 300;
            $data = $this->errorinfo;
        } else {
            $code = 200;
            $data = $this->data;
        }

        return [
            'code' => $code,
            'data' => $data
        ];
    }
}