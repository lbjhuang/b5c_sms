<?php
/**
 * 现存量模型
 * User: b5m
 * Date: 2018/9/10
 * Time: 10:10
 */

class StandingExistingModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_batch';

    public $count;
    public $pageSize;
    public $pageIndex;
    public $amountMoney;//库存成本（含增值税）
    public $amountMoneyNoTax;//库存成本（不含增值税）
    public $amountUsdMoney;//库存成本（USD，不含增值税）
    public $amountUsdMoneyNoTax;//库存成本（USD，不含增值税）
    public $amountNumber;//库存件数
    public $amountSpu;//spu总数
    public $amountSku;//sku总数
    public $outgoingNum;
    public static $xchr;//汇率缓存

    public function __construct($name = '')
    {
        import('ORG.Util.Page');
        parent::__construct($name);
    }

    //在库天数级别
    public static $existed_days_level = [
        [
            'cd' => '1',
            'cdVal' => '<30天',
        ],
        [
            'cd' => '2',
            'cdVal' => '30-59天',
        ],
        [
            'cd' => '3',
            'cdVal' => '60-89天',
        ],
        [
            'cd' => '4',
            'cdVal' => '90-120天',
        ],
        [
            'cd' => '5',
            'cdVal' => '>120天',
        ],
    ];

    //在库天数级别
    public static $existed_days_level_map = [
        '1' => [
            'start' => '0',
            'end' => '29',
        ],
        '2' => [
            'start' => '30',
            'end' => '59',
        ],
        '3' => [
            'start' => '60',
            'end' => '89',
        ],
        '4' => [
            'start' => '90',
            'end' => '120',
        ],
        '5' => [
            'start' => '120',
            'end' => '9999',
        ],
    ];

    /**
     * 盘点商品列表数据、导出数据、批次数据
     *
     * @param array $params
     *
     * @return array
     */
    public function getInveData($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];

        $this
            ->subWhere('_string', 't11.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')//不显示在途库存
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            ->subWhere('t11.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']]);//销售团队
        if ($params ['mixedCode']) {
            if (strstr($params['mixedCode'], ',')) {
                $mixedCode = explode(',', $params['mixedCode']);
                $params['mixedCode'] = "'". implode("','", $mixedCode). "'";
            }
            $this->subWhere('_string', '(t1.SKU_ID like "' . $params['mixedCode'] . '%" or t1.SKU_ID in ('. $params['mixedCode']. '))');
        }

        // 是否展示无库存数据
        if ($params ['showAll'] == false) {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }
        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        $model = new SlaveModel();
        $fields = [
            't1.SKU_ID as skuId',//SKU
            't9.upc_id as upcId',//条形码
            't9.upc_more as upcMore',//条形码
            't9.spu_id as spuId',//spu
            'group_concat(t1.id separator \',\') as batchIds',
            'if(t1.vir_type="N002440400","残次品","正品") as productType',//商品类型
            'SUM(t1.total_inventory) as amountTotalNum',//在库库存
            'SUM(t1.available_for_sale_num) as amountSaleNum',//可售
            'SUM(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            'SUM(t1.total_inventory * unit_price) as amountMoney',//库存成本（含增值税）
            'SUM(t1.total_inventory * unit_price_usd) as amountUsdMoney',//库存成本（USD，含增值税）
            'SUM(t1.total_inventory * t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountMoneyNoTax',//库存成本（不含增值税）
            'SUM(t1.total_inventory * t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountUsdMoneyNoTax',//库存成本（USD，不含增值税）
        ];
        $query = $model->table(B5C_DATABASE . '.tb_wms_batch t1')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
            ->join("LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM ". PMS_DATABASE. ".product tab1 ) t10 ON t10.spu_id = t9.spu_id")
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t11 ON t1.bill_id = t11.id')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, sum(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')

            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->where(static::$where);
        $query1 = clone $query;
        $statistics = $query1->field($fields)
            ->group('t1.SKU_ID,t1.vir_type')
            ->select();
        if ($isExcel) {
            $ret = $query->field($fields)
                ->group('t1.SKU_ID,t1.vir_type')
                ->select();
        } else {
            $Page = new Page(count($statistics), $pageSize);
            $ret = $query->field($fields)
                ->group('t1.SKU_ID,t1.vir_type')
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        }
        
        $ret = SkuModel::getInfo($ret, 'skuId', ['spu_name', 'image_url', 'attributes'], ['spu_name' => 'gudsName', 'image_url' => 'imageUrl', 'attributes' => 'optAttr']);
        foreach ($ret as $k => &$v) {
            if($v['upcMore']) {
                $upc_more_arr = explode(',', $v['upcMore']);
                array_unshift($upc_more_arr, $v['upcId']);
                $v['upcId'] = implode(",\r", $upc_more_arr);
            }
        }
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = count($statistics);
        return $ret;
    }
    /**
     * 列表数据、导出数据、批次数据
     *
     * @param array $params
     *
     * @return array
     */
    public function getData($params)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];

        $this
            ->subWhere('_string', 't11.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')//不显示在途库存
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            ->subWhere('t11.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.purchase_order_no', ['like', $params ['purNum']])//采购单号
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']])//销售团队
            ->subWhere('t1.purchase_team_code', ['in', $params ['purTeam']])//采购团队
            ->subWhere('t11.CON_COMPANY_CD', ['in', $params ['company']])//所属公司
            ->subWhere('t2.pur_storage_date', ['xrange', [$params ['purDate'][0], $params ['purDate'][1]]])//采购时间
            ->subWhere('t1.create_time', ['xrange', [$params ['storageDate'][0], $params ['storageDate'][1]]])//入库时间
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]])//到期日
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]]);//展示无库存
        // 总在库天数level
        if ($params['existed_days_level']) {
            $existed_days_level = $params['existed_days_level'];
            if (!is_array($params['existed_days_level'])) {
                $existed_days_level = explode(',', $params['existed_days_level']);
            }
            $existed_days_where = [];
            foreach ($existed_days_level as $value) {
                if (isset(self::$existed_days_level_map[$value])) {
                    $existed_days = self::$existed_days_level_map[$value];
                    $start_existed_days = $existed_days['start'];
                    $end_existed_days = $existed_days['end'];
                    $existed_days_where[] = "((datediff(now(),t2.pur_storage_date) + 1) between {$start_existed_days} and {$end_existed_days})";
                }
            }
            if (!empty($existed_days_where)) {
                $where_str = "(" . implode(" OR ", $existed_days_where) . ")";
                $this->subWhere('_string', $where_str);
            }
        }
        // 总在库天数
        if ($params['existed_days'][0] !== '' && $params['existed_days'][1] !== '') {
            if (!is_null($params['existed_days'][0]) && !is_null($params['existed_days'][1])) {
                $this->subWhere('_string', "(datediff(now(),t2.pur_storage_date) + 1) between {$params['existed_days'][0]} and {$params['existed_days'][1]}");//展示无库存
            }
        }

        // 条形码，SKU
//        if ($params ['mixedCode']) {
//            $this->subWhere('_string', '(t1.SKU_ID like "%' . $params ['mixedCode'] . '%" or t9.upc_id = "' . $params ['mixedCode'] . '")');
//        }
        if ($params ['mixedCode']) {
            $search_sku = $params ['mixedCode'];
            $other_sku_ids = (new PmsBaseModel())->query("select sku_id from product_sku where (upc_id = '{$search_sku}' or FIND_IN_SET('{$search_sku}',upc_more))");
            $other_sku_ids = array_column($other_sku_ids, 'sku_id');
            if (!empty($other_sku_ids)) {
                $other_sku_ids_str = "('". implode("','", $other_sku_ids). "')";
                $this->subWhere('_string', '(t1.SKU_ID like "' . $search_sku . '%" or t1.SKU_ID in '. $other_sku_ids_str. ')');
            } else {
                $this->subWhere('_string', '(t1.SKU_ID like "' . $params ['mixedCode'] . '%")');
            }
        }
        if (!empty($params['gudsName'])) {//商品名称
            $sku_ids = SkuModel::titleToSku($params['gudsName']);
            $this->subWhere('t1.SKU_ID', ['in', $sku_ids]);
        }
        // 是否展示无库存数据
        if ($params ['showAll'] == false) {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }
        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        // 是否滞销
        /*if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'CASE WHEN t10.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) >= 90 WHEN t10.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) >= 150 END');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'CASE WHEN t10.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) < 90 WHEN t10.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) < 150 END');
        }*/

        // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
        if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) >= 180');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) < 180');
        }


        // 归属店铺
        /*if ($params['ascription_store']) {
            if (in_array('0',$params['ascription_store'])){
                $ascription_store = implode(',',$params['ascription_store']);
                $this->subWhere('_string', " ( t11.ascription_store IS NULL OR t11.ascription_store in ( {$ascription_store} ) )");
            }else{
                $this->subWhere('t11.ascription_store', ['in', $params['ascription_store']]);
            }
        }*/

        // 小团队
        if ($params['small_sale_team']) {
            if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = "('". implode("','", $params['small_sale_team']). "')";
                $this->subWhere('_string', " ( t1.small_sale_team_code IS NULL OR t1.small_sale_team_code = '' OR t1.small_sale_team_code in  {$small_sale_team})");
            }else{
                $this->subWhere('t1.small_sale_team_code', ['in', $params['small_sale_team']]);
            }
        }

        if (!empty($params['is_oem_brand'])) {//商品名称
            $this->subWhere('t7.is_oem_brand', ['in', $params['is_oem_brand']]);
        }

        $model = new Model();
        $fields = [
            't1.SKU_ID as skuId',//SKU
            't9.upc_id as upcId',//条形码
            't9.upc_more as upcMore',//条形码
            't9.spu_id as spuId',//spu
            'group_concat(t1.id separator \',\') as batchIds',
            'SUM(t1.total_inventory) as amountTotalNum',//在库库存
            'SUM(t1.available_for_sale_num) as amountSaleNum',//可售
            'SUM(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            'SUM(t1.total_inventory * unit_price) as amountMoney',//库存成本（含增值税）
            'SUM(t1.total_inventory * unit_price_usd) as amountUsdMoney',//库存成本（USD，含增值税）
            'SUM(t1.total_inventory * t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountMoneyNoTax',//库存成本（不含增值税）
            'SUM(t1.total_inventory * t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountUsdMoneyNoTax',//库存成本（USD，不含增值税）
            //'CASE WHEN t10.cat_level1 = 6 AND datediff(NOW(), t1.create_time) >= 90 THEN 1 WHEN t10.cat_level1 <> 6 AND datediff(NOW(), t1.create_time) >= 150 THEN 1 ELSE 2 END as isDrug',
            // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
            'CASE WHEN datediff(NOW(), t2.pur_storage_date) >= 180 THEN 1 ELSE 2 END as isDrug',
            't7.is_oem_brand'
        ];
        $query = $model->table(B5C_DATABASE . '.tb_wms_batch t1')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
//            ->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't8 ON t9.spu_id = t8.spu_id')
//            ->join('left join ' . PMSSearchModel::spuUnitSql() . ' t10 ON t10.spu_id = t9.spu_id')
            ->join("LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM ". PMS_DATABASE. ".product tab1 ) t10 ON t10.spu_id = t9.spu_id")
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t11 ON t1.bill_id = t11.id')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, sum(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')

            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->join('left join ' . PMS_DATABASE . '.product_brand t7 ON t6.brand_id = t7.brand_id')
            ->where(static::$where);
        $query1 = clone $query;
        $statistics = $query1->field($fields)
            ->group('t1.SKU_ID')
            ->select();
        $Page = new Page(count($statistics), $pageSize);
        $ret = $query->field($fields)
            ->group('t1.SKU_ID')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
       // p($ret);die;
        $ret = SkuModel::getInfo($ret, 'skuId', ['spu_name', 'image_url', 'attributes'], ['spu_name' => 'gudsName', 'image_url' => 'imageUrl', 'attributes' => 'optAttr']);
        $skuIds = array_unique(array_filter(array_column($ret,'skuId')));
        $pmsProductSkuModel = D("Pms/PmsProductSku");
        $levelCatMap = $pmsProductSkuModel->getSkuIdsCatLevelName($skuIds);

        foreach ($ret as $k => &$v) {
            if($v['upcMore']) {
                $upc_more_arr = explode(',', $v['upcMore']);
                array_unshift($upc_more_arr, $v['upcId']);
                $v['upcId'] = implode(",\r", $upc_more_arr);
            }
            $v['sku_cat_level_name'] = isset($levelCatMap[$v['skuId']]) ? $levelCatMap[$v['skuId']] : '';
        }
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = count($statistics);
        // 统计总库存金额，库存数量，spu数，sku数
        $this->getAmountMoney($statistics);
        return $ret;
    }
    // 重写 getData 去除不必要的查询和统计
    public function getDataNew($params){
        $this
            ->subWhere('_string', 't11.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')//不显示在途库存
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            ->subWhere('t11.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.purchase_order_no', ['like', $params ['purNum']])//采购单号
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']])//销售团队
            ->subWhere('t1.purchase_team_code', ['in', $params ['purTeam']])//采购团队
            ->subWhere('t11.CON_COMPANY_CD', ['in', $params ['company']])//所属公司
            ->subWhere('t2.pur_storage_date', ['xrange', [$params ['purDate'][0], $params ['purDate'][1]]])//采购时间
            ->subWhere('t1.create_time', ['xrange', [$params ['storageDate'][0], $params ['storageDate'][1]]])//入库时间
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]])//到期日
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]]);//展示无库存
        // 总在库天数level
        if ($params['existed_days_level']) {
            $existed_days_level = $params['existed_days_level'];
            if (!is_array($params['existed_days_level'])) {
                $existed_days_level = explode(',', $params['existed_days_level']);
            }
            $existed_days_where = [];
            foreach ($existed_days_level as $value) {
                if (isset(self::$existed_days_level_map[$value])) {
                    $existed_days = self::$existed_days_level_map[$value];
                    $start_existed_days = $existed_days['start'];
                    $end_existed_days = $existed_days['end'];
                    $existed_days_where[] = "((datediff(now(),t2.pur_storage_date) + 1) between {$start_existed_days} and {$end_existed_days})";
                }
            }
            if (!empty($existed_days_where)) {
                $where_str = "(" . implode(" OR ", $existed_days_where) . ")";
                $this->subWhere('_string', $where_str);
            }
        }
        // 总在库天数
        if ($params['existed_days'][0] !== '' && $params['existed_days'][1] !== '') {
            if (!is_null($params['existed_days'][0]) && !is_null($params['existed_days'][1])) {
                $this->subWhere('_string', "(datediff(now(),t2.pur_storage_date) + 1) between {$params['existed_days'][0]} and {$params['existed_days'][1]}");//展示无库存
            }
        }

        if ($params ['mixedCode']) {
            $search_sku = $params ['mixedCode'];
            $other_sku_ids = (new PmsBaseModel())->query("select sku_id from product_sku where (upc_id = '{$search_sku}' or FIND_IN_SET('{$search_sku}',upc_more))");
            $other_sku_ids = array_column($other_sku_ids, 'sku_id');
            if (!empty($other_sku_ids)) {
                $other_sku_ids_str = "('". implode("','", $other_sku_ids). "')";
                $this->subWhere('_string', '(t1.SKU_ID like "' . $search_sku . '%" or t1.SKU_ID in '. $other_sku_ids_str. ')');
            } else {
                $this->subWhere('_string', '(t1.SKU_ID like "' . $params ['mixedCode'] . '%")');
            }
        }
        if (!empty($params['gudsName'])) {//商品名称
            $sku_ids = SkuModel::titleToSku($params['gudsName']);
            $this->subWhere('t1.SKU_ID', ['in', $sku_ids]);
        }
        // 是否展示无库存数据
        if ($params ['showAll'] == false) {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }
        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
        if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) >= 180');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) < 180');
        }
        // 销售小团队
        if ($params['small_sale_team']) {
            if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = "('". implode("','", $params['small_sale_team']). "')";
                $this->subWhere('_string', " ( t1.small_sale_team_code IS NULL OR t1.small_sale_team_code = '' OR t1.small_sale_team_code in  {$small_sale_team})");
            }else{
                $this->subWhere('t1.small_sale_team_code', ['in', $params['small_sale_team']]);
            }
        }
        if (!empty($params['is_oem_brand'])) {//商品名称
            $this->subWhere('t7.is_oem_brand', ['in', $params['is_oem_brand']]);
        }
        $model = new Model();
        $fields = [
            't1.SKU_ID as skuId',//SKU
            't9.upc_id as upcId',//条形码
            't9.upc_more as upcMore',//条形码
            't9.spu_id as spuId',//spu
            'group_concat(t1.id separator \',\') as batchIds',
            'SUM(t1.total_inventory) as amountTotalNum',//在库库存
            'SUM(t1.available_for_sale_num) as amountSaleNum',//可售
            'SUM(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            'SUM(t1.total_inventory * unit_price) as amountMoney',//库存成本（含增值税）
            'SUM(t1.total_inventory * unit_price_usd) as amountUsdMoney',//库存成本（USD，含增值税）
            'SUM(t1.total_inventory * t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountMoneyNoTax',//库存成本（不含增值税）
            'SUM(t1.total_inventory * t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0))) as amountUsdMoneyNoTax',//库存成本（USD，不含增值税）
            'CASE WHEN datediff(NOW(), t2.pur_storage_date) >= 180 THEN 1 ELSE 2 END as isDrug',
            't7.is_oem_brand'
        ];
        $query = $model->table(B5C_DATABASE . '.tb_wms_batch t1')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
            ->join("LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM ". PMS_DATABASE. ".product tab1 ) t10 ON t10.spu_id = t9.spu_id")
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t11 ON t1.bill_id = t11.id')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, sum(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')
            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->join('left join ' . PMS_DATABASE . '.product_brand t7 ON t6.brand_id = t7.brand_id')
            ->where(static::$where);
        $ret = $query->field($fields)
            ->group('t1.SKU_ID')
            ->select();

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

    /**
     * 统计库存总金额
     *
     * @param array $data
     */
    public function getAmountMoney($data)
    {
        $this->amountSpu = [];
        $callBack = function ($r, $b) {
            $this->amountMoneyNoTax += $r ['amountMoneyNoTax'];
            $this->amountMoney += $r ['amountMoney'];
            $this->amountUsdMoneyNoTax += $r ['amountUsdMoneyNoTax'];
            $this->amountUsdMoney += $r ['amountUsdMoney'];
            $this->amountNumber += $r ['amountTotalNum'];
            $this->amountSpu[] = $r['spuId'];
        };
        array_walk($data, $callBack);
        $this->amountSpu = count(array_unique($this->amountSpu));
        $this->amountSku = $this->count;
    }


    public function checkBatchData($params){

        static::$where = [];
        $model = new Model();
        $fields = [
            't1.SKU_ID as skuId',
            't1.id as batchId',
            't2.id as stream_id',
            't1.batch_code as batchCode',//批次号
            't1.batch_id as p_batch_id',//批次号
            't5.link_bill_id as link_bill_id',//关联单据
            't5.warehouse_id as warehouse',//仓库
            't1.channel',//渠道
            't5.CON_COMPANY_CD as ourCompany',//所属公司
            't1.sale_team_code as saleTeam',//销售团队
            't1.small_sale_team_code as smallSaleTeam', // 销售小团队
            't5.intro_team as introTeam',//介绍团队
            't5.procurement_number as purNum',//采购单号
            't1.purchase_team_code as purTeam',//采购团队
            't3.create_time as purDate',//采购时间
            't2.pur_storage_date as purStorageDate',//采购入库时间
            '(datediff(now(),t2.pur_storage_date) + 1) as existedDays',//总在库天数//t1.original_storage_time
            't1.create_time as addTime',//入库时间 //2019.2.15 修改为批次创建时间 #8805
            '(datediff(now(),t1.create_time) + 1) as currentExistedDays',//当前仓在库天数
            't1.deadline_date_for_use as deadLineDate',//到期日
            't1.total_inventory as amountTotalNum',//在库库存
            't1.available_for_sale_num as amountSaleNum',//可售
            '(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            't2.unit_price as unitPrice',//采购单价cny
            't2.unit_price_usd unitPriceUsd',//采购单价（采购币种USD，含增值税）
            't2.pur_invoice_tax_rate',
            'round(t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceNoTax',//采购单价（不含税, cny）
            'round(t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceUsdNoTax',//采购单价（采购币种USD，不含增值税）
            't1.total_inventory * t2.unit_price as amountCost',// 批次成本
            //'CASE WHEN t4.cat_level1 = 6 AND datediff(NOW(), t1.create_time) >= 90 THEN 1 WHEN t4.cat_level1 <> 6 AND datediff(NOW(), t1.create_time) >= 150 THEN 1 ELSE 2 END as isDrug',
            // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
            'CASE WHEN datediff(NOW(), t2.pur_storage_date) >= 180 THEN 1 ELSE 2 END as isDrug',
            't1.warehouse_cost as warehouseCost',// 仓储费用
            'ifnull(cl.all_carry_cost, 0) as carryCost',//运输费用
            'ifnull(cl.all_log_service_cost, 0) as logServiceCost',//服务费用
            'ifnull(cl.tariff_cost, 0) as tariff_cost',//关税
            'ifnull(cl.all_po_cost, 0) as poLogCost',//po内物流费用
            'if(t1.vir_type="N002440400","残次品","正品") as productType',//商品属性
            't1.vir_type',
            't2.add_time as act_time',
            't2.unit_price_origin',//采购单价（采购币种，含增值税）
            'round(t2.unit_price_origin / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unit_price_no_tax_origin',//采购单价（采购币种，不含增值税）
            't2.po_cost_origin',//PO内费用单价（采购币种）
            'cd.CD_VAL as pur_currency',//采购币种
            't5.ascription_store', // 归属店铺
            't7.is_oem_brand'//品牌类型
        ];

        $this->subWhere('_string', 't5.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            //->subWhere('t1.SKU_ID', $params ['mixedCode'])
            ->subWhere('t5.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.purchase_order_no', ['like', $params ['purNum']])//采购单号
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']])//销售团队
            ->subWhere('t1.purchase_team_code', ['in', $params ['purTeam']])//采购团队
            ->subWhere('t3.our_company', ['in', $params ['company']])//所属公司
            ->subWhere('t2.pur_storage_date', ['xrange', [$params ['purDate'][0], $params ['purDate'][1]]])//采购时间
            ->subWhere('t1.create_time', ['xrange', [$params ['storageDate'][0], $params ['storageDate'][1]]])//入库时间
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]])//到期日
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]]);//展示无库存
        // 总在库天数level
        if ($params['existed_days_level']) {
            $existed_days_level = $params['existed_days_level'];
            if (!is_array($params['existed_days_level'])) {
                $existed_days_level = explode(',', $params['existed_days_level']);
            }
            $existed_days_where = [];
            foreach ($existed_days_level as $value) {
                if (isset(self::$existed_days_level_map[$value])) {
                    $existed_days = self::$existed_days_level_map[$value];
                    $start_existed_days = $existed_days['start'];
                    $end_existed_days = $existed_days['end'];
                    $existed_days_where[] = "((datediff(now(),t2.pur_storage_date) + 1) between {$start_existed_days} and {$end_existed_days})";
                }
            }
            if (!empty($existed_days_where)) {
                $where_str = "(" . implode(" OR ", $existed_days_where) . ")";
                $this->subWhere('_string', $where_str);
            }
        }
        // 总在库天数
        if ($params['existed_days'][0] !== '' && $params['existed_days'][1] !== '') {
            if (!is_null($params['existed_days'][0]) && !is_null($params['existed_days'][1])) {
                $this->subWhere('_string', "(datediff(now(),t2.pur_storage_date) + 1) between {$params['existed_days'][0]} and {$params['existed_days'][1]}");//展示无库存
            }
        }

        if ($params['ascription_store']) { // 归属店铺
            $this->subWhere('t5.ascription_store', ['in', $params['ascription_store']]);
        }

        if ($params['small_sale_team']) { // 销售小团队
            if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = implode(',',$params['small_sale_team']);
                $this->subWhere('_string', " ( t1.small_sale_team_code IS NULL OR t1.small_sale_team_code in ( {$small_sale_team} ) )");
            }else{
                $this->subWhere('t1.small_sale_team_code', ['in', $params['small_sale_team']]);
            }
        }
        

//        if ($params ['mixedCode']) {
//            $this->subWhere('_string', '(t1.SKU_ID like "%' . $params ['mixedCode'] . '%" or t9.upc_id = "' . $params ['mixedCode'] . '")');
//        }
        if ($params ['mixedCode']) {
            $search_sku = $params ['mixedCode'];
            $other_sku_ids = (new PmsBaseModel())->query("select sku_id from product_sku where (upc_id = '{$search_sku}' or FIND_IN_SET('{$search_sku}',upc_more))");
            $other_sku_ids = array_column($other_sku_ids, 'sku_id');
            if (!empty($other_sku_ids)) {
                $other_sku_ids_str = "('". implode("','", $other_sku_ids). "')";
                $this->subWhere('_string', '(t1.SKU_ID like "' . $search_sku . '%" or t1.SKU_ID in '. $other_sku_ids_str. ')');
            } else {
                $this->subWhere('_string', '(t1.SKU_ID like "' . $params ['mixedCode'] . '%")');
            }
        }
        if (!empty($params['gudsName'])) {//商品名称
            $sku_ids = SkuModel::titleToSku($params['gudsName']);
            $this->subWhere('t1.SKU_ID', ['in', $sku_ids]);
        }

        // 是否展示无库存数据
        if ($params ['showAll'] == true) {

        } else {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }

        // 是否滞销
        /*if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'CASE WHEN t4.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) >= 90 WHEN t4.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) >= 150 END');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'CASE WHEN t4.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) < 90 WHEN t4.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) < 150 END');
        }*/

        // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
        if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) >= 180');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) < 180');
        }

        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        $model = $model->table('tb_wms_batch t1')->field($fields)
            ->join('LEFT JOIN tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.cd = t2.currency_id')
//            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
//            ->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't10 ON t10.spu_id = t9.spu_id')
//            ->join('left join ' . PMSSearchModel::spuUnitSql() . ' t4 ON t4.spu_id = t9.spu_id')
            ->join('LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM '. PMS_DATABASE. '.product tab1) t4 ON t4.spu_id = t1.GUDS_ID')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t5 ON t1.bill_id = t5.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=t2.id and cl.currency_id = \'N000590300\'')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, SUM(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')
            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->join('left join ' . PMS_DATABASE . '.product_brand t7 ON t6.brand_id = t7.brand_id')

            ->where(static::$where)
            ->group('t1.id');
        $modelData = $model->select();
        $total = 0;
        $query = $model->_sql();
        if ($modelData){
            $total = count($modelData);
        }
        return array($total,$query);
    }

    public function getInveBatchData($params, $isExport = false)
    {
        static::$where = [];
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];
        $orderType = $params['orderType'];
        if (empty($params['orderType'])) { // 默认倒叙排列
            $orderType = 'desc';
        }
        $model = new SlaveModel();
        $fields = [
            't1.SKU_ID as skuId',
            't1.id as batchId',
            't2.id as stream_id',
            't1.batch_code as batchCode',//批次号
            't1.batch_id as p_batch_id',//批次号
            't5.link_bill_id as link_bill_id',//关联单据
            't5.warehouse_id as warehouse',//仓库
            't1.channel',//渠道
            't5.CON_COMPANY_CD as ourCompany',//所属公司
            't1.sale_team_code as saleTeam',//销售团队
            't1.small_sale_team_code as smallSaleTeam',//销售小团队
            't5.intro_team as introTeam',//介绍团队
            't1.purchase_order_no as purNum',//采购单号
            't1.purchase_team_code as purTeam',//采购团队
            't3.create_time as purDate',//采购时间
            't2.pur_storage_date as purStorageDate',//采购入库时间
            '(datediff(now(),t2.pur_storage_date) + 1) as existedDays',//总在库天数//t1.original_storage_time
            't1.create_time as addTime',//入库时间 //2019.2.15 修改为批次创建时间 #8805
            '(datediff(now(),t1.create_time) + 1) as currentExistedDays',//当前仓在库天数
            't1.deadline_date_for_use as deadLineDate',//到期日
            't1.total_inventory as amountTotalNum',//在库库存
            't1.available_for_sale_num as amountSaleNum',//可售
            '(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            't2.unit_price as unitPrice',//采购单价cny
            't2.unit_price_usd unitPriceUsd',//采购单价（采购币种USD，含增值税）
            't2.pur_invoice_tax_rate',
            'round(t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceNoTax',//采购单价（不含税, cny）
            'round(t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceUsdNoTax',//采购单价（采购币种USD，不含增值税）
            't1.total_inventory * t2.unit_price as amountCost',// 批次成本
            //'CASE WHEN t4.cat_level1 = 6 AND datediff(NOW(), t1.create_time) >= 90 THEN 1 WHEN t4.cat_level1 <> 6 AND datediff(NOW(), t1.create_time) >= 150 THEN 1 ELSE 2 END as isDrug',
            // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
            'CASE WHEN datediff(NOW(), t2.pur_storage_date) >= 180 THEN 1 ELSE 2 END as isDrug',
            't1.warehouse_cost as warehouseCost',// 仓储费用
            'ifnull(cl.all_carry_cost, 0) as carryCost',//运输费用
            'ifnull(cl.all_log_service_cost, 0) as logServiceCost',//服务费用
            'ifnull(cl.tariff_cost, 0) as tariff_cost',//关税
            'ifnull(cl.all_po_cost, 0) as poLogCost',//po内物流费用
            'if(t1.vir_type="N002440400","残次品","正品") as productType',//商品属性
            't1.vir_type',
            't2.add_time as act_time',
            't2.unit_price_origin',//采购单价（采购币种，含增值税）
            'round(t2.unit_price_origin / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unit_price_no_tax_origin',//采购单价（采购币种，不含增值税）
            't2.po_cost_origin',//PO内费用单价（采购币种）
            'cd.CD_VAL as pur_currency',//采购币种
            //'t5.ascription_store', // 归属店铺
            't7.is_oem_brand', // 归属店铺
        ];

        $this->subWhere('_string', 't5.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            ->subWhere('t5.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']]);//销售团队
        if ($params ['mixedCode']) {
            if (strstr($params['mixedCode'], ',')) {
                $mixedCode = explode(',', $params['mixedCode']);
                $params['mixedCode'] = "'". implode("','", $mixedCode). "'";
            }
            $this->subWhere('_string', '(t1.SKU_ID like "' . $params['mixedCode'] . '%" or t1.SKU_ID in ('. $params['mixedCode']. '))');
        }

        // 是否展示无库存数据
        if ($params ['showAll'] == true) {

        } else {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }

        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        $r = $model->table('tb_wms_batch t1')->field($fields)
            ->join('LEFT JOIN tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.cd = t2.currency_id')
            ->join('LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM '. PMS_DATABASE. '.product tab1) t4 ON t4.spu_id = t1.GUDS_ID')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t5 ON t1.bill_id = t5.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=t2.id and cl.currency_id = \'N000590300\'')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, SUM(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')
            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->join('left join ' . PMS_DATABASE . '.product_brand t7 ON t6.brand_id = t7.brand_id')
            ->where(static::$where)
            ->order('t1.batch_code '.$orderType)
            ->group('t1.id');

        $ret = $r->select();
        $AllocationExtendAttributionRepository = new AllocationExtendAttributionRepository();
        $store_list = $AllocationExtendAttributionRepository->getStoreName();
        foreach ($ret as &$r) {
            $log_svr_list = null;
            $r ['isDrug'] == 1 ? $r ['isDrug'] = 1 : $r ['isDrug'] = 0;
            $r ['warehouse'] ? $r ['warehouse'] = L(BaseModel::warehouseList()[$r ['warehouse']]['cdVal']) : '';
            if (isset(BaseModel::saleTeamCdExtend()[$r ['saleTeam']])) {
                $r ['saleTeam'] = L(BaseModel::saleTeamCdExtend()[$r ['saleTeam']]['CD_VAL']);
            }
            $r ['purTeam'] = BaseModel::spTeamCd()[$r ['purTeam']];
            if (isset(BaseModel::ourCompany() [$r ['ourCompany']])) {
                $r ['ourCompany'] = L(BaseModel::ourCompany()[$r ['ourCompany']]);
            }
            $r['introTeam'] = cdVal($r['introTeam']);
            $r['smallSaleTeam'] = cdVal($r['smallSaleTeam']);
            $r['smallSaleTeamVal'] = $r['smallSaleTeam'];
            if ($r['productType'] == '残次品') {
                $r['amountLockingNum'] = '-';
            }

            if ($isExport) {
                $r ['isDrug'] == 1 ? $r ['isDrug'] = L('滞销') : $r ['isDrug'] = L('未滞销');
                $r['is_oem_brand'] == '1' ? $r['is_oem_brand'] = L('是') : $r['is_oem_brand'] = L('否');
                $r['logServiceCost'] = round($r['logServiceCost'], 2);
                $r['carryCost'] = round($r['carryCost'], 2);
                $r['poLogCost'] = round($r['poLogCost'], 2);
                $logRate = self::getXchr('N000590100', $r['addTime']) ?: 1;
                $r['warehouseCost'] = round($r['warehouseCost'] * $logRate, 2);
            } else {
                $logRate = self::getXchr('N000590100', $r['addTime']) ?: 1;
                $r['logRate'] = $logRate;
                $r['logServiceCost0'] = round($r['log_service_cost'] * $logRate, 2);
                $r['storageLogCost0'] = round($r['storage_log_cost'] * $logRate, 2);
                $ls_params['list_type'] = 'log';
                $ls_params['stream_id'] = $r['stream_id'];
                $log_svr_list = $this->getLogServiceCostList($ls_params);
                $r['logServiceCost'] = round(array_sum(array_column($log_svr_list, 'log_service_cost')), 2);
                $r['carryCost'] = round(array_sum(array_column($log_svr_list, 'carry_cost')), 2);
                $r['poLogCost'] = round($log_svr_list [0]['po_cost'], 2);
                $wcl_params['batch_id'] = $r['batchId'];
                $warehouse_cost_list = $this->getWarehouseCostList($wcl_params);
                $r['warehouseCost'] = round(array_sum(array_column($warehouse_cost_list, 'warehouse_cost')), 2);
            }
        }
        unset($r);
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = 0;
        return $ret;
    }

    /**
     * 批次数据获取
     * 是否滞销是根据pms中product表中的cat_level1来判定，如果cat_level1=6则在库时长超过3个月就算滞销，其它则是超过5个月算滞销
     *
     * @param array $params
     * @param bool $isExport false 是否是导出
     *
     * @return mixed
     */
    public function getBatchData($params, $isExport = false)
    {
        static::$where = [];
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];
        $orderType = $params['orderType'];
        if (empty($params['orderType'])) { // 默认倒叙排列
            $orderType = 'desc';
        }
        $model = new Model();
        $fields = [
            't1.SKU_ID as skuId',
            't1.id as batchId',
            't2.id as stream_id',
            't1.batch_code as batchCode',//批次号
            't1.batch_id as p_batch_id',//批次号
            't5.link_bill_id as link_bill_id',//关联单据
            't5.warehouse_id as warehouse',//仓库
            't1.channel',//渠道
            't5.CON_COMPANY_CD as ourCompany',//所属公司
            't1.sale_team_code as saleTeam',//销售团队
            't1.small_sale_team_code as smallSaleTeam',//销售小团队
            't5.intro_team as introTeam',//介绍团队
            't1.purchase_order_no as purNum',//采购单号
            't1.purchase_team_code as purTeam',//采购团队
            't3.create_time as purDate',//采购时间
            't2.pur_storage_date as purStorageDate',//采购入库时间
            '(datediff(now(),t2.pur_storage_date) + 1) as existedDays',//总在库天数//t1.original_storage_time
            't1.create_time as addTime',//入库时间 //2019.2.15 修改为批次创建时间 #8805
            '(datediff(now(),t1.create_time) + 1) as currentExistedDays',//当前仓在库天数
            't1.deadline_date_for_use as deadLineDate',//到期日
            't1.total_inventory as amountTotalNum',//在库库存
            't1.available_for_sale_num as amountSaleNum',//可售
            '(t1.occupied + IFNULL(t12.childOccupied, 0)) as amountOccupiedNum',//占用
            'SUM(IFNULL(t12.childLocking, 0)) as amountLockingNum',//锁定
            't2.unit_price as unitPrice',//采购单价cny
            't2.unit_price_usd unitPriceUsd',//采购单价（采购币种USD，含增值税）
            't2.pur_invoice_tax_rate',
            'round(t2.unit_price / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceNoTax',//采购单价（不含税, cny）
            'round(t2.unit_price_usd / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unitPriceUsdNoTax',//采购单价（采购币种USD，不含增值税）
            't1.total_inventory * t2.unit_price as amountCost',// 批次成本
            //'CASE WHEN t4.cat_level1 = 6 AND datediff(NOW(), t1.create_time) >= 90 THEN 1 WHEN t4.cat_level1 <> 6 AND datediff(NOW(), t1.create_time) >= 150 THEN 1 ELSE 2 END as isDrug',
            // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
            'CASE WHEN datediff(NOW(), t2.pur_storage_date) >= 180 THEN 1 ELSE 2 END as isDrug',
            't1.warehouse_cost as warehouseCost',// 仓储费用
            'ifnull(cl.all_carry_cost, 0) as carryCost',//运输费用
            'ifnull(cl.all_log_service_cost, 0) as logServiceCost',//服务费用
            'ifnull(cl.tariff_cost, 0) as tariff_cost',//关税
            'ifnull(cl.all_po_cost, 0) as poLogCost',//po内物流费用
            'if(t1.vir_type="N002440400","残次品","正品") as productType',//商品属性
            't1.vir_type',
            't2.add_time as act_time',
            't2.unit_price_origin',//采购单价（采购币种，含增值税）
            'round(t2.unit_price_origin / (1+ifnull(t2.pur_invoice_tax_rate,0)),4) as unit_price_no_tax_origin',//采购单价（采购币种，不含增值税）
            't2.po_cost_origin',//PO内费用单价（采购币种）
            'cd.CD_VAL as pur_currency',//采购币种
            //'t5.ascription_store', // 归属店铺
            't7.is_oem_brand', // 归属店铺
        ];

        $this->subWhere('_string', 't5.type = 1')
            ->subWhere('_string', 't1.vir_type != "N002440200"')
            ->subWhere('_string', 't1.vir_type != "N002410200"')//不显示调拨在途
            //->subWhere('t1.SKU_ID', $params ['mixedCode'])
            ->subWhere('t5.warehouse_id', ['in', $params ['warehouse']])//仓库
            ->subWhere('t1.purchase_order_no', ['like', $params ['purNum']])//采购单号
            ->subWhere('t1.sale_team_code', ['in', $params ['saleTeam']])//销售团队
            ->subWhere('t1.purchase_team_code', ['in', $params ['purTeam']])//采购团队
            ->subWhere('t5.CON_COMPANY_CD', ['in', $params ['company']])//所属公司
            ->subWhere('t2.pur_storage_date', ['xrange', [$params ['purDate'][0], $params ['purDate'][1]]])//采购时间
            ->subWhere('t1.create_time', ['xrange', [$params ['storageDate'][0], $params ['storageDate'][1]]])//入库时间
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]])//到期日
            ->subWhere('t2.deadline_date_for_use', ['xrange', [$params ['dueDate'][0], $params ['dueDate'][1]]]);//展示无库存
        // 总在库天数level
        if ($params['existed_days_level']) {
            $existed_days_level = $params['existed_days_level'];
            if (!is_array($params['existed_days_level'])) {
                $existed_days_level = explode(',', $params['existed_days_level']);
            }
            $existed_days_where = [];
            foreach ($existed_days_level as $value) {
                if (isset(self::$existed_days_level_map[$value])) {
                    $existed_days = self::$existed_days_level_map[$value];
                    $start_existed_days = $existed_days['start'];
                    $end_existed_days = $existed_days['end'];
                    $existed_days_where[] = "((datediff(now(),t2.pur_storage_date) + 1) between {$start_existed_days} and {$end_existed_days})";
                }
            }
            if (!empty($existed_days_where)) {
                $where_str = "(" . implode(" OR ", $existed_days_where) . ")";
                $this->subWhere('_string', $where_str);
            }
        }
        // 总在库天数
        if ($params['existed_days'][0] !== '' && $params['existed_days'][1] !== '') {
            if (!is_null($params['existed_days'][0]) && !is_null($params['existed_days'][1])) {
                $this->subWhere('_string', "(datediff(now(),t2.pur_storage_date) + 1) between {$params['existed_days'][0]} and {$params['existed_days'][1]}");//展示无库存
            }
        }

//        if ($params['ascription_store']) { // 归属店铺
//            $this->subWhere('t5.ascription_store', ['in', $params['ascription_store']]);
//        }

        // 处理小团队问题
        // 小团队
        if ($params['small_sale_team']) {
	        if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = "('". implode("','", $params['small_sale_team']). "')";
	            $this->subWhere('_string', " ( t1.small_sale_team_code IS NULL OR t1.small_sale_team_code = '' OR t1.small_sale_team_code in ( {$small_sale_team} ) )");
	        }else{
	            $this->subWhere('t1.small_sale_team_code', ['in', $params['small_sale_team']]);
	        }
        }
        // 归属店铺
        /*if ($params['ascription_store']) {
            if (in_array('0',$params['ascription_store'])){
                $ascription_store = implode(',',$params['ascription_store']);
                $this->subWhere('_string', " ( t5.ascription_store IS NULL OR t5.ascription_store in ( {$ascription_store} ) )");
            }else{
                $this->subWhere('t5.ascription_store', ['in', $params['ascription_store']]);
            }
        }*/

//        if ($params ['mixedCode']) {
//            $this->subWhere('_string', '(t1.SKU_ID like "%' . $params ['mixedCode'] . '%" or t9.upc_id = "' . $params ['mixedCode'] . '")');
//        }
        if ($params ['mixedCode']) {
            $search_sku = $params ['mixedCode'];
            $other_sku_ids = (new PmsBaseModel())->query("select sku_id from product_sku where (upc_id = '{$search_sku}' or FIND_IN_SET('{$search_sku}',upc_more))");
            $other_sku_ids = array_column($other_sku_ids, 'sku_id');
            if (!empty($other_sku_ids)) {
                $other_sku_ids_str = "('". implode("','", $other_sku_ids). "')";
                $this->subWhere('_string', '(t1.SKU_ID like "' . $search_sku . '%" or t1.SKU_ID in '. $other_sku_ids_str. ')');
            } else {
                $this->subWhere('_string', '(t1.SKU_ID like "' . $params ['mixedCode'] . '%")');
            }
        }
        if (!empty($params['gudsName'])) {//商品名称
            $sku_ids = SkuModel::titleToSku($params['gudsName']);
            $this->subWhere('t1.SKU_ID', ['in', $sku_ids]);
        }

        // 批次号查询
        if (!empty($params['batchCode'])) {
            $this->subWhere('_string', 't1.batch_code = '.$params['batchCode']);
        }

        // 是否展示无库存数据
        if ($params ['showAll'] == true) {

        } else {
            $this->subWhere('_string', 't1.total_inventory > 0');
        }

        // 是否滞销
        /*if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'CASE WHEN t4.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) >= 90 WHEN t4.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) >= 150 END');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'CASE WHEN t4.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) < 90 WHEN t4.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) < 150 END');
        }*/

        // 是否滞销 需求10351库存出库顺序与滞销判断规则调整：新规则-所有批次，采购入库时间距今超过6个月的算滞销
        if ($params ['isDrug'] == 1)
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) >= 180');
        if ($params ['isDrug'] == 2) {
            $this->subWhere('_string', 'datediff(NOW(), t2.pur_storage_date) < 180');
        }

        //残次品筛选
        if ($params['productType']) {
            $params['productType'] == 1 ? $this->subWhere('t1.vir_type', ['neq', 'N002440400']) : $this->subWhere('t1.vir_type', 'N002440400');
        }

        $r = $model->table('tb_wms_batch t1')->field($fields)
            ->join('LEFT JOIN tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.cd = t2.currency_id')
//            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
//            ->join('LEFT JOIN ' . PMSSearchModel::spuNameSql() . 't10 ON t10.spu_id = t9.spu_id')
//            ->join('left join ' . PMSSearchModel::spuUnitSql() . ' t4 ON t4.spu_id = t9.spu_id')
            ->join('LEFT JOIN ( SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1 FROM '. PMS_DATABASE. '.product tab1) t4 ON t4.spu_id = t1.GUDS_ID')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t5 ON t1.bill_id = t5.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=t2.id and cl.currency_id = \'N000590300\'')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, SUM(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')
            ->join('left join ' . PMS_DATABASE . '.product t6 ON t6.spu_id = t1.GUDS_ID')
            ->join('left join ' . PMS_DATABASE . '.product_brand t7 ON t6.brand_id = t7.brand_id')
            ->where(static::$where)
            ->order('t1.batch_code '.$orderType)
            ->group('t1.id');

        if ($isExport) {
            $ret = $r
                ->select();
            /*foreach ($ret as $v) {
                $batches[] = ['batch_id' => $v['batchId'], 'stream_id' => $v['stream_id'], 'link_bill_id' => $v['link_bill_id'], 'p_batch_id' => $v['p_batch_id']];
                $stream_ids[] = $v['stream_id'];
            }
            $inList = [];
            $outList = [];
            $this->getLogServiceCostListAll($stream_ids, $inList, $outList);*/
        } else {
            $ret = $r->select();
        }

        // 获取所有SKU_ID的四级类目信息列表
        $skuIds = array_unique(array_filter(array_column($ret, 'skuId')));
        $levelCatMap = D('Pms/PmsProductSku')->getSkuIdsCatLevelNameList($skuIds);

        $AllocationExtendAttributionRepository = new AllocationExtendAttributionRepository();
        $store_list = $AllocationExtendAttributionRepository->getStoreName();
        foreach ($ret as &$r) {
            $r ['purTeamCd'] = $r ['purTeam'];
            $r['introTeamCd'] = $r['introTeam'];
            $r ['ourCompanyCd'] = $r ['ourCompany'];
            $r ['saleTeamCd'] = $r ['saleTeam'];
            $r ['smallSaleTeamCd'] = $r['smallSaleTeam'];
            $log_svr_list = null;
            $r ['isDrug'] == 1 ? $r ['isDrug'] = 1 : $r ['isDrug'] = 0;
            $r ['warehouse'] ? $r ['warehouse'] = L(BaseModel::warehouseList()[$r ['warehouse']]['cdVal']) : '';

            if (isset(BaseModel::saleTeamCdExtend()[$r ['saleTeam']])) {
                $r ['saleTeam'] = L(BaseModel::saleTeamCdExtend()[$r ['saleTeam']]['CD_VAL']);
            }
            $r ['purTeam'] = BaseModel::spTeamCd()[$r ['purTeam']];
            if (isset(BaseModel::ourCompany() [$r ['ourCompany']])) {
                $r ['ourCompany'] = L(BaseModel::ourCompany()[$r ['ourCompany']]);
            }
            $r['introTeam'] = cdVal($r['introTeam']);
            $r['smallSaleTeam'] = cdVal($r['smallSaleTeam']);
            $r['smallSaleTeamVal'] = $r['smallSaleTeam'];
            //$r['ascription_store_val'] = $store_list[$r['ascription_store']];
            if ($r['productType'] == '残次品') {
                $r['amountLockingNum'] = '-';
            }

            // 组装四级类目信息
            $r['cat_level1_name'] = isset($levelCatMap[$r['skuId']]['cat_level1_name']) ? $levelCatMap[$r['skuId']]['cat_level1_name'] : '';
            $r['cat_level2_name'] = isset($levelCatMap[$r['skuId']]['cat_level2_name']) ? $levelCatMap[$r['skuId']]['cat_level2_name'] : '';
            $r['cat_level3_name'] = isset($levelCatMap[$r['skuId']]['cat_level3_name']) ? $levelCatMap[$r['skuId']]['cat_level3_name'] : '';
            $r['cat_level4_name'] = isset($levelCatMap[$r['skuId']]['cat_level4_name']) ? $levelCatMap[$r['skuId']]['cat_level4_name'] : '';

            if ($isExport) {
                $r ['isDrug'] == 1 ? $r ['isDrug'] = L('滞销') : $r ['isDrug'] = L('未滞销');
                $r['is_oem_brand'] == '1' ? $r['is_oem_brand'] = L('是') : $r['is_oem_brand'] = L('否');
//                $this->getLogServiceCostListXls($r, $outList, $inList);
                $r['logServiceCost'] = round($r['logServiceCost'], 2);
                $r['carryCost'] = round($r['carryCost'], 2);
                $r['poLogCost'] = round($r['poLogCost'], 2);
                $logRate = self::getXchr('N000590100', $r['addTime']) ?: 1;
                $r['warehouseCost'] = round($r['warehouseCost'] * $logRate, 2);
            } else {
                /*$warehouseCostRate = self::getXchr('N000590100', $r['addTime']);
                $r['warehouseCostRate'] = $warehouseCostRate;
                $r['warehouseCost'] = round($r['warehouseCost'] * $warehouseCostRate, 2);*/

                $logRate = self::getXchr('N000590100', $r['addTime']) ?: 1;
                $r['logRate'] = $logRate;
                $r['logServiceCost0'] = round($r['log_service_cost'] * $logRate, 2);
                $r['storageLogCost0'] = round($r['storage_log_cost'] * $logRate, 2);
                $ls_params['list_type'] = 'log';
                $ls_params['stream_id'] = $r['stream_id'];
                $log_svr_list = $this->getLogServiceCostList($ls_params);
                $r['logServiceCost'] = round(array_sum(array_column($log_svr_list, 'log_service_cost')), 2);
                $r['carryCost'] = round(array_sum(array_column($log_svr_list, 'carry_cost')), 2);
                $r['poLogCost'] = round($log_svr_list [0]['po_cost'], 2);
                $wcl_params['batch_id'] = $r['batchId'];
                $warehouse_cost_list = $this->getWarehouseCostList($wcl_params);
                $r['warehouseCost'] = round(array_sum(array_column($warehouse_cost_list, 'warehouse_cost')), 2);
            }
        }
        unset($r);
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = 0;
        return $ret;
    }

    public static function getXchr($cur_code, $date)
    {
        $ymd = date('Ymd', strtotime($date));
        if (!self::$xchr[$ymd . $cur_code]) {
            self::$xchr[$ymd . $cur_code] = BaseModel::exchangeRate($cur_code, $date);
        }
        return self::$xchr[$ymd . $cur_code][BaseModel::CURRENT_CURRENCY_LOCAL];
    }

    /**
     * 占用查询
     *
     * @param $params
     *
     * @return
     */
    public function occupyData($params)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];

        $model = new Model();
        $this->subWhere('_string', 't1.use_type = 1')
            ->subWhere('_string', 't1.vir_type <> "N002440200"')
            ->subWhere('t1.SKU_ID', $params ['mixedCode'])
            ->subWhere('t1.batch_id', $params ['batchId'])
            ->subWhere('t1.batch_id', ['in', $params ['batchIds']])
            ->subWhere('t1.ORD_ID', $params ['ordId']);

        $field = [
            't1.ORD_ID as ordId',
            't2.ORDER_ID as orderId',
            't1.occupy_num batchAmountOccupiedNum',
            'tb_wms_allo.id AS allo_id',
            'tb_wms_allo.transfer_type',
            'cd_1.CD_VAL AS allo_state_val',
        ];

        $count = $model->table('tb_wms_batch_order t1')
            ->join('LEFT JOIN tb_op_order t2 ON t1.ORD_ID = t2.B5C_ORDER_NO')
            ->where(static::$where)
            ->count();
        $page = new Page($count, $pageSize);
        $ret = $model->table('tb_wms_batch_order t1')
            ->field($field)
            ->join('LEFT JOIN tb_op_order t2 ON t1.ORD_ID = t2.B5C_ORDER_NO')
            ->join('LEFT JOIN tb_wms_allo ON tb_wms_allo.allo_no = t1.ORD_ID')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD  = tb_wms_allo.state')
            ->where(static::$where)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        //batch child 占用附加
        /*$batchChildOccupy = M('batch_child', 'tb_wms_')
            ->field('"batchChild" as ordId,sum(occupied) as batchAmountOccupiedNum, SUM(available_for_sale_num) AS childLocking')
            ->where(['batch_id' => $params['batchId']])
            ->group('batch_id')
            ->find();
        if ($batchChildOccupy['batchAmountOccupiedNum']) {
            $ret[] = $batchChildOccupy;
        }*/

        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = $count;

        return $ret;
    }

    /**
     * 锁定查询
     *
     * @param $params
     *
     * @return
     */
    public function lockingData($params)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];

        $model = new Model();
        $fields = [
            't1.channel', // 渠道
            't3.STORE_NAME as storeName', // 店铺
            't1.order_id as orderId', // 订单编号
            't1.SKU_ID as skuId',     // SKU
            't4.warehouse_id as warehouse', // 锁定仓库
            't1.available_for_sale_num as allTotalInventory' // 锁定数量
        ];

        $this->subWhere('t1.channel', ['eq', $params ['channel']])
            ->subWhere('t3.PLAT_CD', ['eq', $params ['storeCode']])
            ->subWhere('t1.order_id', ['eq', $params ['orderId']])
            ->subWhere('t1.batch_id', ['eq', $params ['batchId']])
            ->subWhere('t1.batch_id', ['in', $params ['batchIds']])
            ->subWhere('t1.SKU_ID', ['eq', $params ['mixedCode']])
            ->subWhere('_string', 't1.available_for_sale_num > 0');

        $count = $model->table('tb_wms_batch_child t1')
            ->join('LEFT JOIN tb_wms_batch t2 ON t1.batch_id = t2.id')
            ->join('LEFT JOIN tb_ms_store t3 ON t1.store_id = t3.id')
            ->join('LEFT JOIN tb_wms_bill t4 ON t2.bill_id = t4.id')
            //->field($fields)
            ->where(static::$where)
            ->count();

        $page = new Page($count, $pageSize);

        $ret = $model->table('tb_wms_batch_child t1')
            ->join('LEFT JOIN tb_wms_batch t2 ON t1.batch_id = t2.id')
            ->join('LEFT JOIN tb_ms_store t3 ON t1.store_id = t3.id')
            ->join('LEFT JOIN tb_wms_bill t4 ON t2.bill_id = t4.id')
            ->field($fields)
            ->where(static::$where)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $ret = array_map(function ($r) {
            $r ['channel'] = BaseModel::getChannels() [$r ['channel']];
            $r ['warehouse'] = BaseModel::getWarehouseId() [$r ['warehouse']];
            return $r;
        }, $ret);

        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->count = $count;

        return $ret;
    }

    public static $where;

    /**
     * 构建查询条件
     *
     * @param mixed $str
     *
     * @return array
     */
    public function subWhere($key, $str)
    {
        if (is_array($str)) {
            list($pattern, $val) = $str;
            if ($val != '') {
                switch ($pattern) {
                    case 'like':
                        static::$where [$key] = ['like', '%' . $val . '%'];
                        break;
                    case 'range':
                        list($l, $r) = $val;
                        if ($l and $r)
                            static::$where [$key] = [['gt', $l . ' 00:00:00'], ['lt', $r . ' 23:59:59'], 'and'];
                        if ($l and !$r)
                            static::$where [$key] = ['gt', $l . ' 00:00:00'];
                        if (!$l and $r)
                            static::$where [$key] = ['lt', $r . ' 23:59:59'];
                        break;
                    case 'xrange':
                        list($l, $r) = $val;
                        if ($l and $r)
                            static::$where [$key] = [['egt', $l . ' 00:00:00'], ['elt', $r . ' 23:59:59'], 'and'];
                        if ($l and !$r)
                            static::$where [$key] = ['egt', $l . ' 00:00:00'];
                        if (!$l and $r)
                            static::$where [$key] = ['elt', $r . ' 23:59:59'];
                        break;
                    default:
                        if (!empty($val) and !is_null($val))
                            static::$where [$key] = [$pattern, $val];
                        break;
                }
            }
        } else {
            if ($str != '') {
                if (isset(static::$where [$key])) {
                    static::$where [$key] = static::$where [$key] . ' and ' . $str;
                } else {
                    static::$where [$key] = $str;
                }
            }
        }

        return $this;
    }

    public function getLogServiceCostListAll(&$stream_ids, &$inList, &$outList)
    {
        $tmp = M('wms_stream', 'tb_')->alias('st')
            ->field('st.id as stream_id,ba.id as batch_id,bi.bill_id,bi.link_bill_id,st.add_time,ifnull(cl.carry_cost, 0) as carry_cost,ifnull(cl.log_service_cost, 0) as log_service_cost,ba.batch_id as p_batch_id,ifnull(cl.po_cost, 0) as po_cost')
            ->join('left join tb_wms_bill bi on bi.id=st.bill_id')
            ->join('left join tb_wms_batch ba on ba.stream_id=st.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=st.id and cl.currency_id = \'N000590300\'')
            ->where($where['st.id'] = ['in', $stream_ids])
            ->select();
        foreach ($tmp as $v) {
            $inList[$v['batch_id']] = $v;
        }
        $tmp = M('wms_stream', 'tb_')->alias('st')
            ->field('st.id as stream_id,bi.link_bill_id,st.batch as batch_id,bi.bill_id,st.add_time,ifnull(cl.carry_cost, 0) as carry_cost,ifnull(cl.log_service_cost, 0) as log_service_cost,ifnull(cl.po_cost, 0) as po_cost')
            ->join('left join tb_wms_batch ba on ba.id=st.batch')
            ->join('left join tb_wms_bill bi on st.bill_id=bi.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=st.id and cl.currency_id = \'N000590300\'')
            ->where(['bi.type' => 0, 'bi.relation_type' => 'N002350100'])
            ->select();
        foreach ($tmp as $v) {
            $outList[$v['batch_id'] . $v['link_bill_id']] = $v;
        }
    }

    public function getLogServiceCostListXls(&$val, &$inList, &$outList, $batch_id = null)
    {
        $batch_id = $batch_id ?: $val['batch_id'];
        $tmp = $inList[$batch_id];
        $val['carryCost'] += round($tmp['carry_cost'], 2);
        $val['logServiceCost'] += round($tmp['log_service_cost'], 2);
        $val['poCost'] += round($tmp['po_cost'], 2);
        if ($tmp['p_batch_id']) {
            $tmp2 = $outList[$tmp['p_batch_id'] . $tmp['link_bill_id']];
            $val['carryCost'] += round($tmp2['carry_cost'], 2);
            $val['logServiceCost'] += round($tmp2['log_service_cost'], 2);
            $val['poCost'] += round($tmp2['po_cost'], 2);
            $this->getLogServiceCostListXls($val, $inList, $outList, $tmp['p_batch_id']);
        }
    }

    /**服务和物流费用列表
     *
     * @param $params
     * @param array $ret
     * @param int $first
     *
     * @return array
     */
    public function getLogServiceCostList($params, &$ret = [], $first = 1)
    {
        empty($params['stream_id']) or $where['st.id'] = $params['stream_id'];
        if (!$first) {
            empty($params['batch_id']) or $where['ba.id'] = $params['batch_id'];
        }
        //入库
        $tmp = M('wms_stream', 'tb_')->alias('st')
            ->field('st.id as stream_id,bi.bill_id,ba.state,bi.link_bill_id,st.add_time,ifnull(cl.carry_cost, 0) as carry_cost,ifnull(cl.log_service_cost, 0) as log_service_cost,ba.batch_id as p_batch_id,xchr.USD_XCHR_AMT_CNY as usd_xchr,ifnull(cl.po_cost, 0) as po_cost,cl.operation_service_cost,cl.in_appreciation_service_cost,cl.op_appreciation_service_cost,cl.insurance_service_cost,cl.shelf_service_cost,cl.export_carry_cost,cl.header_carry_cost')
            ->join('left join tb_wms_bill bi on bi.id=st.bill_id')
            ->join('left join tb_wms_batch ba on ba.stream_id=st.id')
            ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=st.id and cl.currency_id = \'N000590300\'')
            ->join('left join tb_ms_xchr xchr on xchr.XCHR_STD_DT=date_format(ifnull(ba.create_time, now()), \'%Y%m%d\')')
            ->where($where)
            ->find();
        if ($tmp) $ret[] = $tmp;
        if ($tmp['p_batch_id'] && $tmp['state'] == 1) {
            //出库
            if ($params['list_type'] == 'log') {
                $tmp2 = M('wms_stream', 'tb_')->alias('st')
                    ->field('st.id as stream_id,bi.bill_id,st.add_time,ifnull(cl.carry_cost, 0) as carry_cost,ifnull(cl.log_service_cost, 0) as log_service_cost, ifnull(cl.po_cost, 0) as po_cost,cl.operation_service_cost,cl.in_appreciation_service_cost,cl.op_appreciation_service_cost,cl.insurance_service_cost,cl.shelf_service_cost,cl.export_carry_cost,cl.header_carry_cost')//,xchr.USD_XCHR_AMT_CNY as usd_xchr
                    ->join('left join tb_wms_batch ba on ba.id=st.batch')
                    ->join('left join tb_wms_bill bi on st.bill_id=bi.id')
                    ->join('left join tb_wms_stream_cost_log cl on cl.stream_id=st.id and cl.currency_id = \'N000590300\'')
//                    ->join('left join tb_ms_xchr xchr on xchr.XCHR_STD_DT=date_format(ifnull(ba.create_time, now()), \'%Y%m%d\')')
                    ->where(['bi.link_bill_id' => $tmp['link_bill_id'], 'bi.type' => 0, 'st.batch' => $tmp['p_batch_id']])
                    ->find();
                if ($tmp2) $ret[] = $tmp2;
            }
            $this->getLogServiceCostList(['batch_id' => $tmp['p_batch_id'], 'list_type' => $params['list_type']], $ret, 0);
        }
        if ($first) {
            if ($params['bill_id']) {
                $ret = array_values(array_filter($ret, function ($v) use ($params) {
                    return $v['bill_id'] == $params['bill_id'];
                }));
            }
            foreach ($ret as $k => &$v) {
                $v['carry_cost'] = round($v['carry_cost'], 2);
                $v['log_service_cost'] = round($v['log_service_cost'], 2);
            }
            $ret = $this->splitStreamCost($ret, $params['list_type']);
        }
        return $ret;
    }

    private function splitStreamCost($data, $type)
    {
        switch ($type) {
            case 'service':
                $fees = [
                    'operation_service_cost' => '作业费用',
                    'in_appreciation_service_cost' => '增值服务费用',
                    'op_appreciation_service_cost' => '增值服务费用',
                    'insurance_service_cost' => '保险',
                    'shelf_service_cost' => '上架费用',
                ];
                break;
            case 'log':
                $fees = [
                    'export_carry_cost' => '出库费用',
                    'header_carry_cost' => '头程费用',
                ];
                break;
        }
        foreach ($data as $datum) {
            $temp_do = [];
            foreach ($fees as $log_fee_key => $log_fee_value) {
                if ($datum[$log_fee_key]) {
                    $temp_datum = [
                        'bill_id' => $datum['bill_id'],
                        'cost_breakdown_type' => $log_fee_value,
                        'carry_cost' => $datum[$log_fee_key],
                        'log_service_cost' => $datum[$log_fee_key],
                    ];
                    $temp_do[] = $temp[] = $temp_datum;
                }
            }
            if (empty($temp_do)) {
                $datum['cost_breakdown_type'] = null;
                $temp[] = $datum;
            }
        }
        return $temp;
    }

    public function getExistingList($params)
    {

        $model = new Model();
        $batch_where['SKU_ID'] = $params['sku_id'];
        $batch_db = $model->table('tb_wms_batch')
            ->field('id,stream_id,batch_id')
            ->where($batch_where)
            ->select();
        $batch_arr = array_combine(array_column($batch_db, 'id'), array_values($batch_db));
        $get_allos = $this->getAlloRecursive($batch_arr, $params['batch_id']);
        $stream_ids = array_column($get_allos, 'stream_id');
        if (empty($stream_ids)) {
            return [];
        }
        $stream_where['tb_wms_stream_cost_log.stream_id'] = ['IN', $stream_ids];
        $stream_where['tb_wms_stream_cost_log.currency_id'] = 'N000590300';
        $tariff_costs = $model->table('tb_wms_bill,tb_wms_stream,tb_wms_stream_cost_log')
            ->field([
                "tb_wms_bill.bill_id AS 'order_id'",
                "'关税' AS cost_breakdown_type",
                "tb_wms_stream_cost_log.tariff_cost AS amortization_fee_amount"
            ])
            ->where($stream_where)
            ->where('tb_wms_bill.id = tb_wms_stream.bill_id AND tb_wms_stream.id = tb_wms_stream_cost_log.stream_id', null, true)
            ->select();
        return $tariff_costs;
    }

    private function getAlloRecursive($batch_db, $batch_id, &$get_allos = [])
    {
        $parent_batch_id = $batch_db[$batch_id]['batch_id'];
        $get_allos[] = $batch_db[$batch_id];
        if ($parent_batch_id) {
            $this->getAlloRecursive($batch_db, $parent_batch_id, $get_allos);
        }
        return $get_allos;
    }


    public function getWarehouseCostList($params, &$ret = [], $first = 1)
    {
        empty($params['batch_id']) or $where['ba.id'] = $params['batch_id'];
        $bo_id = $params['p_bo_id'] ?: 'null';
        $field = $first ? 'ifnull(ba.warehouse_cost,0)-ifnull(ba.warehouse_original_cost,0)' : $params['p_final_cost'] . '-ifnull(ba.warehouse_original_cost,0)';
        $tmp = M('wms_batch', 'tb_')->alias('ba')
            ->field('ba.id as batch_id,cd.CD_VAL as warehouse,ba.batch_id as p_batch_id,' . $field . ' as warehouse_cost,ifnull(ba.warehouse_original_cost,0) as p_final_cost,ifnull(ba.warehouse_original_cost,0) as warehouse_original_cost,cd1.CD_VAL as warehouse_cost_currency_val,ba.warehouse_cost_currency,date_format(st.add_time, \'%Y.%m.%d\') as start_date,date_format(bo.out_storage_time, \'%Y.%m.%d\') as end_date,bo2.id as p_bo_id,st.add_time')//xchr.USD_XCHR_AMT_CNY as usd_xchr
            ->join('left join tb_wms_bill bi ON bi.id = ba.bill_id')
            ->join('left join tb_wms_stream st ON st.id = ba.stream_id')
            ->join('left join tb_wms_batch_order bo on bo.id=\'' . $bo_id . '\'')
            ->join('left join tb_wms_batch_order bo2 on bo2.ORD_ID=bi.link_bill_id and bo2.batch_id=ba.batch_id')//调拨父批次出库占用batchOrder
            ->join('left join tb_ms_cmn_cd cd ON cd.CD = bi.warehouse_id')
            ->join('left join tb_ms_cmn_cd cd1 ON cd1.CD = ba.warehouse_cost_currency')
            ->where($where)
            ->find();
        if ($tmp) $ret[] = $tmp;
        if ($tmp['p_batch_id']) {
            $this->getWarehouseCostList(['batch_id' => $tmp['p_batch_id'], 'p_bo_id' => $tmp['p_bo_id'], 'p_final_cost' => $tmp['p_final_cost']], $ret, 0);
        }
        if ($first) {
            foreach ($ret as $k => &$v) {
                if ($k == 0) $v['end_date'] = date('Y.m.d');
                $v['usd_xchr'] = self::getXchr('N000590100', $v['add_time']);
                $v['warehouse_cost'] = round($v['warehouse_cost'] * $v['usd_xchr'], 2);
                //组合商品入库加上起初成本
                if (!isset($ret[$k + 1])) {
                    $v['warehouse_original_cost_cny'] = round($v['warehouse_original_cost'] * $v['usd_xchr'], 2);
                    $v['warehouse_cost'] += $v['warehouse_original_cost_cny'];
                }
            }
            if ($params['warehouse']) {
                $ret = array_values(array_filter($ret, function ($v) use ($params) {
                    return stripos($v['warehouse'], $params['warehouse']) !== false;
                }));
            }
        }
        return $ret;
    }

    public function getOrderUrl($params)
    {
        $order_no = $params['order'];
        $prefix = strtoupper(substr($order_no, 0, 4));
        switch ($prefix) {
            case 'DB20':
                $url = 'allocation_extend/show';
                $id = M('allo', 'tb_wms_')->where(['allo_no' => $order_no])->getField('id');
                $args = ['id' => $id];
                break;
            case 'RN20':
                $url = 'b2b/order_list#/b2bsend';
                $id = M('order', 'tb_b2b_')->where(['PO_ID' => $order_no])->getField('ID');
                $args = ['order_id' => $id];
                if (!$id) {
                    $url = 'scm/display/demands';
                    $id = M('demand', 'tb_sell_')->where(['demand_code' => $order_no])->getField('ID');
                    $args = ['type' => $id];
                }
                break;
            case 'GSPT':
                $url = 'OMS/Order/orderDetail';
                $order = M('order', 'tb_op_')->field('ORDER_ID,PLAT_CD')->where(['B5C_ORDER_NO' => $order_no])->find();
                $args = ['thrId' => $order['ORDER_ID'], 'platCode' => $order['PLAT_CD']];
                break;
            case 'CGTH':
                $url = 'order_detail/return_detail';
                $id = M('return', 'tb_pur_')->where(['return_no' => $order_no])->getField('id');
                $args = ['id' => $id];
                break;
        }
        return [$url, $args];
    }

    public function exportCsv(&$data, $map, $excel_name, $bool = false)
    {
        $filename = '' . $excel_name . '' . date('YmdHis') . '.csv'; //设置文件名
        header('Content-Type: text/csv');
        header("Content-type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename=".$filename);
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        $title_name = array_column($map, 'name');
        if (empty($title_name)) {
            $title_name = array_column($map, 0);//兼容格式
        }
        fputcsv($out, $title_name);
        $fields = array_column($map, 'field_name');
        if (empty($fields)) {
            $fields = array_column($map, 1);
        }
        foreach ($data as $row) {
            $line = array_map(function ($field) use ($row, $bool) {
                //导出CSV文件去除双引号
                if (is_numeric($row[$field]) || $bool == true) {
                    return (string)$row[$field];
                }
                return (string)$row[$field] . "\t";
            }, $fields);
            fputcsv($out, $line);
        }
        fclose($out);
    }
}