<?php

/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 17/1/17
 * Time: 10:18
 */
class WarehouseModel extends RelationModel
{
    protected $trueTableName = 'tb_wms_warehouse';

    public $pageIndex;
    public $pageSize;
    public $count;

    public static function filterWarehouse($queryParams)
    {
        $queryParams ['type_cd'] and $where = ['type_cd' => ['in', $queryParams ['type_cd']]];
        if ($queryParams ['operator_cds']) {
            foreach ($queryParams ['operator_cds'] as $k => $v) {
                if ($k == 0) {
                    $where['_string'] .= 'operator_cds like "%' . $v . '%"';
                } else {
                    $where['_string'] .= ' or operator_cds like "%' . $v . '%"';
                }
            }
        }
        if ($queryParams['doc_exist'] == 2) {
            $where['t1.id'] = ['exp', 'is null'];
        }
        $where['cd.CD'] = ['like', 'N00068%'];
        $tmp = M('warehouse', 'tb_wms_')->alias('t1')
            ->field('cd.CD,cd.CD_VAL')
            ->join('right join tb_ms_cmn_cd cd on cd.CD=t1.CD')
            ->where($where)
            ->select();
        foreach ($tmp as $v) {
            $list[$v['CD']] = $v;
        }
        return $list;
    }

    // 获取英文国家地区字符串
    public function getEnglishPlace($warehouse)
    {
        $data = [];
        if ($warehouse) {
            // 获取国家区域映射关系数据
            $data = $warehouse;
            foreach ($warehouse as $key => $value) {
                $city_arr = explode(',', $value['city']);
                $place_en = '';
                foreach ($city_arr as $k => $v) {
                    $place = '';
                    $place = (new TbCrmSiteModel())->siteName($v, 'en');
                    $place_en .= $place . '-';
                }
                $data[$key]['place_en'] = $place_en;
            }
        }
        return $data;
        
    }
    public function data($queryParams)
    {
        import('ORG.Util.Page');
        $this->pageSize = 20;
        $this->pageIndex = $_GET ['p'] = $_POST ['p'] = 1;
        empty($queryParams ['pageSize'])  or $this->pageSize = $queryParams ['pageSize'];
        empty($queryParams ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $this->pageIndex = $queryParams ['pageIndex'];
        $queryParams['search_name'] == 'warehouse' and $queryParams['search_name'] = 'cd.CD_VAL';
        // 仓库相关信息
        $field = ['id','is_bonded', 'cd.CD_VAL warehouse', 'contacts', 'place', 'address', 'system_docking as systemDocking', 'auto_dispatch', 'auto_dispatch_delay', 't1.CD as warehouseCode', 'city', 'job_content as jobContent', 'sender', 'sender_zip_code', 'sender_phone_number', 'auto_group', 'phone', 'remarks', 'contract_no', 'cost_per_day', 'cost_currency', 'replace(`contract_start`, \'-\', \'.\') contract_start', 'replace(`contract_end`, \'-\', \'.\') contract_end','in_contacts', 'out_contacts', 'operator_cds', 'type_cd', 'cd.USE_YN as status', 'default_addr', 'address_en'];
        $this->subWhere('t1.CD', ['in', $queryParams ['warehouseCode']])
             ->subWhere($queryParams ['search_name'], ['like', $queryParams ['search_value']])
             ->subWhere($queryParams ['contact_name'], ['like', $queryParams ['contact_value']])
             ->subWhere('type_cd', ['in', $queryParams ['type_cd']])
             ->subWhere('cd.USE_YN', $queryParams ['status'] == '0' ? 'N' : 'Y');
        if ($queryParams ['operator_cds']) {
            foreach ($queryParams ['operator_cds'] as $k => $v) {
                if ($k == 0) {
                    static::$where['_string'] .= 'operator_cds like "%' . $v . '%"';
                } else {
                    static::$where['_string'] .= ' or operator_cds like "%' . $v . '%"';
                }
            }
        }
        // 是否为保税仓
        if ($queryParams['is_bonded'] != ''){
            $is_bonded = explode(',',$queryParams['is_bonded']);
            if (!empty($is_bonded) && is_array($is_bonded)){
                $this->subWhere('is_bonded',['in',$is_bonded]);
            }
        }

        $this->count = $this->alias('t1')
            ->join('left join tb_ms_cmn_cd cd on cd.CD=t1.CD')
            ->where(static::$where)
            ->count();
        $Page  = new Page($this->count, $this->pageSize);
        $this->alias('t1')->limit($Page->firstRow, $Page->listRows);
        $warehouse = $this->field($field)
            ->join('left join tb_ms_cmn_cd cd on cd.CD=t1.CD')
            ->where(static::$where)
            ->select();
        $warehouse = $this->getEnglishPlace($warehouse);
        unset($v);
        // 仓库 SKU 数量、库存数相关信息
        $field = ['IFNULL(sum(t1.total_inventory * t2.unit_price), 0)  as amountMoney',
            't3.warehouse_id as warehouseCode',
            'IFNULL(sum(t1.total_inventory), 0) as amountInventory',
            'IFNULL(COUNT(DISTINCT t2.GSKU), 0) as amountSku'];
        static::$where = $tmp = null;
        $ret = $this->getWarehouseNum();
        foreach ($ret as $key => $value) {
            $tmp [$value ['warehouseCode']] = $value;
        }
        $systemDocking = array_column(CommonDataModel::systemDocking(), 'cdVal', 'cd');
        $response = array_map(function($r) use ($tmp, $systemDocking) {
            $r ['systemDockingNm'] = $systemDocking [$r ['systemDocking']];
            $r ['auto_dispatch_nm'] = $r['auto_dispatch'] ? '启用' : '不支持';
            $r ['auto_group_nm'] = $r['auto_group'] ? '支持' : '不支持';
            if ($r ['jobContent']) {
                $r ['jobContent']  = explode(':', $r ['jobContent']);
            } else {
                $r ['jobContent'] = [];
            }
            $r ['amountMoney']     = $tmp [$r ['warehouseCode']]['amountMoney']?round($tmp [$r ['warehouseCode']]['amountMoney'],2):0;
            $r ['amountInventory'] = $tmp [$r ['warehouseCode']]['amountInventory']?round($tmp [$r ['warehouseCode']]['amountInventory'],2):0;
            $r ['amountSku']       = $tmp [$r ['warehouseCode']]['amountSku']?$tmp [$r ['warehouseCode']]['amountSku']:0;
//            $r['in_contacts'] = explode(',', $r['in_contacts']);
//            $r['out_contacts'] = explode(',', $r['out_contacts']);
            $r['status'] = $r['status'] == 'Y' ? 1 : 0;
            $r['status_nm'] = $r['status'] ? '启用' : '未启用';
            $r['type_cd_nm'] = cdVal($r['type_cd']);
            if ($r['operator_cds']) {
                $r['operator_cds'] = $r['operator_cds_nm'] = explode(',', $r['operator_cds']);
                foreach ($r['operator_cds_nm'] as &$v) {
                    $v = cdVal($v);
                }
                $r['operator_cds_nm'] = implode(',', $r['operator_cds_nm']);
            } else {
                $r['operator_cds_nm'] = '';
            }
            return $r;
        }, $warehouse);

        $this->notConfiguredWarehouse = $this->notConfiguredWarehouse(array_column($warehouse, 'warehouseCode'));

        return $response;
    }

    public $notConfiguredWarehouse = null;

    /**
     * 获取码表中所有的仓库，并排除掉已新增的仓库
     * @param mixed $warehouseCode 已配置的仓库code
     * @return mixed 返回码表中未配置的仓库code与仓库名称
     */
    public function notConfiguredWarehouse($warehouseCode, $flag = false)
    {
        $model = new Model();
        //$conditions []['CD'] = ['not in', $warehouseCode];
        $conditions []['CD'] = ['like', 'N00068%'];
        $conditions []['USE_YN'] = 'Y';
        if ($flag) $conditions []['CD'] = ['in', $warehouseCode];
        $field = [
            'CD as cd',
            'CD_VAL as cdVal'
        ];
        $ret = $model->table("tb_ms_cmn_cd")->field($field)->where($conditions)->select();

        foreach ($ret as $key => &$value) {
            if (in_array($value ['cd'], $warehouseCode)) {
                $value ['disabled'] = true;
            } else {
                $value ['disabled'] = false;
            }
        }

        return $ret;
    }

    public static $where;

    /**
     * 构建查询条件
     * @param mixed $str
     * @return mixed
     */
    public function subWhere($key, $str)
    {
        if (is_array($str)) {
            list($pattern, $val) = $str;
            if ($val != '') {
                switch ($pattern){
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
                    static::$where [$key] .= ' and ' . $str;
                } else {
                    static::$where [$key] = $str;
                }
            }
        }

        return $this;
    }

    public $page;

    public function listData($queryParams)
    {
        import('ORG.Util.Page');
        $this->pageSize = 10;
        $this->pageIndex = $_GET ['p'] = $_POST ['p'] = 1;
        empty($queryParams ['pageSize'])  or $this->pageSize = $queryParams ['pageSize'];
        empty($queryParams ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $this->pageIndex = $queryParams ['pageIndex'];

        $model = new Model();
        $fields = [
            't1.channel', // 渠道
            't3.STORE_NAME as storeName', // 店铺
            't1.order_id as orderId', // 订单编号
            't1.SKU_ID as skuId',     // SKU
            't4.warehouse_id as warehouse', // 锁定仓库
            't1.available_for_sale_num as allTotalInventory' // 锁定数量
        ];

        $this->subWhere('t1.channel', ['eq', $queryParams ['channel']])
             ->subWhere('t3.PLAT_CD', ['eq', $queryParams ['storeCode']])
             ->subWhere('t1.order_id', ['eq', $queryParams ['orderId']])
             ->subWhere('t1.batch_id', ['eq', $queryParams ['batchId']])
             ->subWhere('t1.SKU_ID', ['eq', $queryParams ['skuId']])
             ->subWhere('_string', 't1.available_for_sale_num > 0');

        $this->count = $model->table('tb_wms_batch_child t1')
            ->join('LEFT JOIN tb_wms_batch t2 ON t1.batch_id = t2.id')
            ->join('LEFT JOIN tb_ms_store t3 ON t1.store_id = t3.id')
            ->join('LEFT JOIN tb_wms_bill t4 ON t2.bill_id = t4.id')
            //->field($fields)
            ->where(static::$where)
            ->count();

        $Page = new Page($this->count, $this->pageSize);
        $show = $Page->ajax_show('filterSearchLock');

        $ret = $model->table('tb_wms_batch_child t1')
            ->join('LEFT JOIN tb_wms_batch t2 ON t1.batch_id = t2.id')
            ->join('LEFT JOIN tb_ms_store t3 ON t1.store_id = t3.id')
            ->join('LEFT JOIN tb_wms_bill t4 ON t2.bill_id = t4.id')
            ->field($fields)
            ->where(static::$where)
            ->limit($Page->firstRow, $Page->listRows)
            ->select();

        $this->page = $show;

        $ret = array_map(function ($r) {
            $r ['channel']   = BaseModel::getChannels() [$r ['channel']];
            $r ['warehouse'] = BaseModel::getWarehouseId() [$r ['warehouse']];
            return $r;
        }, $ret);

        return $ret;
    }

    /**
     * 数据处理
     * @param mixed $data
     * @return array
     */
    public function filter($data)
    {
        $ret = BaseModel::convertUnder($data);
        $inStorage = $this->inStorage();
        $tbGudsOpt = new TbMsGudsOptModel();
        $skus = array_unique(array_column($data, 'GSKU'));
        $imgs = $this->getGudsImg($skus);
        $users = $this->getUser();
        $ret = array_map(function($r) use ($inStorage, $tbGudsOpt, $imgs, $users) {
            // 出入库类型
            if ($r ['type'] == 1)
                $r ['type'] = L('入库');
            else
                $r ['type'] = L('出库');
            $r ['amountPrice']   = number_format($r ['sendNum'] * $r ['unitPrice'], 4);// 总价
            $r ['VALUATIONUNIT'] = $this->mappingValue() [$r ['VALUATIONUNIT']];// 单位
            $r ['warehouseId']   = $this->mappingValue() [$r ['warehouseId']];// 仓库
            $r ['billType']      = $this->mappingValue() [$r ['billType']];// 出入库类型
            $r ['SALETEAM']      = $this->mappingValue() [$r ['SALETEAM']];// 销售团队
            $r ['relationType']  = $this->mappingValue() [$r ['relationType']];// 关联单据
            $r ['GUDSOPTVALMPNG'] = $this->gudsOptsMerge($r ['GUDSOPTVALMPNG']);// 属性
            $r ['GUDSIMGCDNADDR'] = $imgs [$r ['GSKU']];// 图片
            $r ['zdUser']        =  $users[$r ['zdUser']];//操作人
            return $r;
        }, $ret);

        return $ret;
    }

    public function exportXls($queryParams)
    {
        include_once __DIR__ . DIRECTORY_SEPARATOR . 'Oms' . DIRECTORY_SEPARATOR . 'ExportModel.class.php';
        $queryParams ['pageSize'] = 99999;
        $data = $this->data($queryParams);
        $exportExcel = new ExportModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('编号'), 'field_name' => 'index'],
            $key++ => ['name' => L('仓库名称'), 'field_name' => 'warehouse'],
            $key++ => ['name' => L('总负责人'), 'field_name' => 'contacts'],
            $key++ => ['name' => L('入库负责人'), 'field_name' => 'in_contacts'],
            $key++ => ['name' => L('出库负责人'), 'field_name' => 'out_contacts'],
            $key++ => ['name' => L('地理位置'), 'field_name' => 'location'],//
            $key++ => ['name' => L('SKU数量'), 'field_name' => 'amountSku'],
            $key++ => ['name' => L('库存数量'), 'field_name' => 'amountInventory'],
            $key++ => ['name' => L('总库存成本（CNY）'), 'field_name' => 'amountMoney'],
            $key++ => ['name' => L('运营方'), 'field_name' => 'operator_cds_nm'],
            $key++ => ['name' => L('类型'), 'field_name' => 'type_cd_nm'],
            $key++ => ['name' => L('启用状态'), 'field_name' => 'status_nm'],
            $key =>   ['name' => L('是否保税仓'), 'field_name' => 'is_bonded']//
        ];
        $index = 1;
        foreach ($data as &$v) {
            $v['index'] = $index++;
            $v['location'] = $v['place'] . '-' . $v['address'];
            $v['contract_term'] = str_replace('-', '.', $v['contract_start']) . ' - ' . str_replace('-', '.', $v['contract_end']);
            $v['contract_term'] == ' - ' and $v['contract_term'] = '';
            $v['is_bonded'] = empty($v['is_bonded']) ? "否":"是";
        }
        unset($v);
        $exportExcel->data = $data;
        $exportExcel->export();
    }

    //导出在途库存占用列表
    public static function exportOnway($data)
    {
        include_once __DIR__ . DIRECTORY_SEPARATOR . 'Oms' . DIRECTORY_SEPARATOR . 'ExportModel.class.php';
        $exportExcel = new ExportModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('编号'), 'field_name' => 'index'],
            $key++ => ['name' => L('在途类型'), 'field_name' => 'onway_type'],
            $key++ => ['name' => L('单据号'), 'field_name' => 'link_bill_id'],
            $key++ => ['name' => L('SKU编码'), 'field_name' => 'sku_id'],
            $key++ => ['name' => L('条形码'), 'field_name' => 'upc_id'],
            $key++ => ['name' => L('商品名称'), 'field_name' => 'spu_name'],
            $key++ => ['name' => L('商品属性'), 'field_name' => 'attributes'],
            $key++ => ['name' => L('批次号'), 'field_name' => 'batch_code'],
            $key++ => ['name' => L('在途总数'), 'field_name' => 'total_inventory'],
            $key++ => ['name' => L('可预订数量'), 'field_name' => 'available_for_sale_num'],
            $key++ => ['name' => L('已预订数量'), 'field_name' => 'occupied'],//
            $key++ => ['name' => L('采购币种'), 'field_name' => 'pur_currency'],//
            $key++ => ['name' => L('采购单价（采购币种,含增值税）'), 'field_name' => 'unit_price_origin'],
            $key++ => ['name' => L('采购单价（CNY,含增值税）'), 'field_name' => 'unit_price'],
            $key++ => ['name' => L('采购单价（USD，含增值税）'), 'field_name' => 'unit_price_usd'],
            $key++ => ['name' => L('退税比例'), 'field_name' => 'proportion_of_tax'],
            $key++ => ['name' => L('待入仓库'), 'field_name' => 'warehouse'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'sell_team'],
            $key++ => ['name' => L('采购团队'), 'field_name' => 'purchase_team'],
            $key => ['name' => L('预计入库日期'), 'field_name' => 'arrive_time']//
        ];
        $index = 1;
        foreach ($data as &$v) {
            $v['index'] = $index++;
            $v['proportion_of_tax'] = $v['proportion_of_tax'] * 100 . '%';
        }
        unset($v);
        $exportExcel->data = $data;
        $exportExcel->export();
    }

    /**在途占用列表
     * @param $queryParams
     * @return array
     */
    public static function onwayList($params, $flag = null)
    {
        $where = [];
        $where['wb.vir_type'] = ['in', ['N002440200', 'N002410200']];
        $where['wb.total_inventory'] = ['gt', 0];
        //调拨在途改版展示在途总数大于零的记录
        $or_string[] = '(( wb.total_inventory > 0 AND wb.vir_type <> "N002410200") OR ((ba.available_for_sale_num + ba.occupied) > 0 AND wb.vir_type = "N002410200"))';
        empty($params['page']) and $params['page'] = 1;
        empty($params['pageSize']) and $params['pageSize'] = 20;
        //非采购在途，调拨在途则
        empty($params['onway_type']) || $params['onway_type'] != 'N002410300' or $where['wb.id'] = $params['onway_type'];
        if ($params['onway_type'] == 'N002410200' || $params['onway_type'] == 'N002410100') {
            $where['wb.vir_type'] = $params['onway_type'];
        }
        if ($params['onway_type'] == 'N002410100') {
            $where['wb.vir_type'] = 'N002440200';
        }
        empty($params['pur_no']) or $or_string[] = '(( wbill.link_bill_id = "' . $params['pur_no'] . '") OR (ba.allo_no = "' . $params['pur_no'] . '"))';

        empty($params['sku_upc_id']) or $where['_string'] = "(wb.SKU_ID={$params['sku_upc_id']} or sku.upc_id={$params['sku_upc_id']} or FIND_IN_SET('{$params['sku_upc_id']}',sku.upc_more))";
        if (!empty($params['sku_name'])) {
            $sku_ids = SkuModel::titleToSku($params['sku_name']);
            $where['wb.SKU_ID'] = ['in', $sku_ids];
        }
        $where_string = '';
        if (!empty($params['warehouse'])) {
            $or_string[] = '(( wbill.warehouse_id = "' . $params['warehouse'] . '" AND wb.vir_type <> "N002410200") OR (wa.allo_in_warehouse = "' . $params['warehouse'] . '" AND wb.vir_type = "N002410200"))';
        }
        empty($params['sell_team']) or $where['wb.sale_team_code'] = $params['sell_team'];
        //批次号只筛选调拨在途
        empty($params['batch_code']) or ($where['wb.vir_type'] = 'N002410200' AND $where['wb.batch_code'] = $params['batch_code']);
        empty($params['purchase_team']) or $where['wb.purchase_team_code'] = $params['purchase_team'];
        if (!empty($params['arrive_time'])) {
            $or_string[] = '(( quo.arrive_time >= "' . $params['arrive_time'][0] . ' 00:00:00" AND quo.arrive_time <= "' . $params['arrive_time'][1] . ' 23:59:59" AND wb.vir_type <> "N002410200")
            OR ( wa.expected_warehousing_date >= "' . $params['arrive_time'][0] . ' 00:00:00" AND wa.expected_warehousing_date <= "' . $params['arrive_time'][1] . ' 23:59:59" AND wb.vir_type = "N002410200"))';
        }
        //复合查询
        if (!empty($or_string)) {
            $where_string .= implode(' AND ', $or_string);
        }
        $query = M('wms_batch', 'tb_')->alias('wb')
            ->join('left join tb_wms_bill wbill on wbill.id=wb.bill_id')
            ->join('left join tb_sell_quotation quo on quo.quotation_code=wbill.link_bill_id')
            ->join('left join tb_pur_order_detail od on od.procurement_number=wbill.link_bill_id')
            ->join('left join tb_pur_relevance_order rel on rel.order_id=od.order_id')
            ->join('left join tb_pur_goods_information gi on gi.relevance_id=rel.relevance_id and gi.sku_information=wb.sku_id')
            ->join('left join ' . PMS_DATABASE . '.product_sku sku on sku.sku_id=wb.sku_id')
            ->join('left join tb_wms_batch_allo ba on ba.batch_id=wb.id')
            ->join('left join tb_wms_allo wa on wa.allo_no = ba.allo_no')
            ->where($where)
            ->where($where_string, null, true);
        $query1 = clone $query;
        $query2 = clone $query;
        $ret['total'] = (int) $query1->count();
        $ret['page'] = $params['page'];
        $offset = ($params['page'] - 1) * $params['pageSize'];
        $ret['pageSize'] = $params['pageSize'];
        $ret['sum_data'] = $query2->field([
            'sum(if(wb.vir_type = \'N002410200\', (ba.available_for_sale_num + ba.occupied), wb.total_inventory)) as total',
            'sum(if(wb.vir_type = \'N002410200\', ba.available_for_sale_num, wb.available_for_sale_num)) as available',
            'sum(if(wb.vir_type = \'N002410200\', ifnull(ba.occupied, 0), ifnull(wb.occupied, 0))) as occupied',
            '(select ifnull(sum(return_number), 0) from tb_pur_return_goods rg where rg.information_id = gi.information_id) as return_number',
            'sum(if(wb.vir_type = \'N002410200\', (ba.available_for_sale_num + ba.occupied), wb.total_inventory) * ws.unit_price) as onway_money',
            'sum(if(wb.vir_type = \'N002410200\', (ba.available_for_sale_num + ba.occupied), wb.total_inventory) * ws.unit_price_usd) as onway_money_usd', //美元在途金额
        ])
            ->join('left join tb_wms_stream ws on ws.id=wb.stream_id')
            ->find();
//        $ret['sum_data']['total'] += $ret['sum_data']['return_number'];
//        $ret['sum_data']['available'] += $ret['sum_data']['return_number'];
        $query3 = $query->field(['wb.id as batch_id',
        'ws.proportion_of_tax',
        'if(wb.vir_type = \'N002410200\', \'调拨在途\', \'采购在途\') as onway_type',
        'if(wb.vir_type = \'N002410200\', (ba.allo_no), wbill.link_bill_id) as link_bill_id',
        'if(wb.vir_type = \'N002410200\', wb.batch_code, \'\') as batch_code',
        'wb.vir_type',
        '(select ifnull(sum(return_number), 0) from tb_pur_return_goods rg where rg.information_id = gi.information_id) as return_number',
        'gi.information_id',
        'wb.sku_id',
        //'sku.upc_id',
        'IF(sku.upc_more, REPLACE(CONCAT_WS(\',\',sku.upc_id,sku.upc_more),\',\',\',\\r\\n\'), sku.upc_id) as upc_id',
        //'wb.total_inventory',
        'if(wb.vir_type = \'N002410200\', (ba.available_for_sale_num + ba.occupied), wb.total_inventory) as total_inventory',
        'if(wb.vir_type = \'N002410200\', ba.available_for_sale_num, wb.available_for_sale_num) as available_for_sale_num',
        'if(wb.vir_type = \'N002410200\', ifnull(ba.occupied, 0), ifnull(wb.occupied, 0)) as occupied',
        'ws.unit_price',
        'ws.unit_price_origin', //原币种单价金额
        'ws.unit_price_usd', //美元单价金额
        'if(wb.vir_type = \'N002410200\', cd5.CD_VAL, cd4.CD_VAL) as pur_currency', //【采购币种】对应在途类型=采购在途的库存，为该行采购单的币种；对应在途类型=调拨在途的库存，为该SKU该批次对应来源采购单的币种
        'if(wb.vir_type = \'N002410200\', (
        select cd4.CD_VAL from tb_ms_cmn_cd cd4 where cd4.CD=wa.allo_in_warehouse
        ), cd1.CD_VAL) as warehouse',
        'wb.sale_team_code',
        'cd2.CD_VAL as sell_team',
        'wb.purchase_team_code',
        'cd3.CD_VAL as purchase_team',
        'if(wb.vir_type = \'N002410200\', (
        wa.expected_warehousing_date
        ), quo.arrive_time) as arrive_time',
        ])
            ->join('left join tb_wms_stream ws on ws.id=wb.stream_id')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD=wbill.warehouse_id')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD=wb.sale_team_code')
            ->join('left join tb_ms_cmn_cd cd3 on cd3.CD=wb.purchase_team_code')
            ->join('left join tb_ms_cmn_cd cd4 on cd4.CD=od.amount_currency')
            ->join('LEFT JOIN tb_ms_cmn_cd cd5 ON cd5.cd = ws.currency_id');
        if ($flag != 1) {
            $query3->limit($offset . ',' . $params['pageSize']);
        }
        $ret['list'] = $query3->select();
        $ret['list'] = SkuModel::getInfo($ret['list'], 'sku_id', ['spu_name', 'attributes', 'image_url']);
        /*foreach ($ret['list'] as &$v) {
            $v['total_inventory'] += $v['return_number'];
            $v['available_for_sale_num'] += $v['return_number'];
        }*/
        return $ret;
    }

    public static function onwayOccupiedList($params)
    {
        $where['batch_id'] = $params['batch_id'];
        $where['occupy_num'] = ['gt', 0];
        $where['use_type'] = 1;

        //区分调拨在途
        $batch = M('wms_batch', 'tb_')->field('vir_type')->where(['id' => $params['batch_id']])->find();
        if ($batch['vir_type'] == 'N002410200') {
            if (!empty($params['link_bill_id'])) {
                $where['allo_no'] = $params['link_bill_id'];
            }
            unset($where['batch_id']);
            $where['wb.batch_id'] = $params['batch_id'];
            $list = M('wms_batch_allo', 'tb_')->alias('wb')
                ->field('\'销售需求\' as occupied_type,o.ORD_ID as ord_id,o.occupy_num as occupy_num')
                ->join('left join tb_wms_batch_order o on o.batch_allo_id=wb.id')
                ->where($where)
                ->select();
        } else {
            $list = M('wms_batch_order', 'tb_')->alias('wb')
                ->field('\'销售需求\' as occupied_type,ord_id,occupy_num')
                ->where($where)
                ->select();
        }
        return $list;
    }

    /**
     * @return array|mixed
     */
    private function getWarehouseNum()
    {
        $pms_database = PMS_DATABASE;
        $b5c_database = B5C_DATABASE;
        $search_warehouse_sql = "SELECT
                    COUNT(skuId) AS amountSku,
                    warehouse AS warehouseCode,
                    SUM(amountTotalNum) AS amountInventory,
                    SUM(amountMoney) AS amountMoney,
                    SUM(amountMoneyNoTax) AS amountMoneyNoTax
                FROM
                    (
                        SELECT
                            t1.SKU_ID AS skuId,
                            t11.warehouse_id AS warehouse,
                            SUM(t1.total_inventory) AS amountTotalNum,
                            SUM(
                                t1.total_inventory * unit_price
                            ) AS amountMoney,
                            SUM(
                                t1.total_inventory * t2.unit_price / (
                                    1 + ifnull(t2.pur_invoice_tax_rate, 0)
                                )
                            ) AS amountMoneyNoTax
                        FROM
                            {$b5c_database}.tb_wms_batch t1
                        LEFT JOIN {$b5c_database}.tb_wms_stream t2 ON t1.stream_id = t2.id
                        LEFT JOIN {$b5c_database}.tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number
                        LEFT JOIN (
                            SELECT
                                tab1.spu_id,
                                tab1.sku_id,
                                tab1.upc_id,
                                tab1.sku_states
                            FROM
                                {$pms_database}.product_sku tab1
                            GROUP BY
                                tab1.sku_id
                        ) t9 ON t9.sku_id = t1.SKU_ID
                        LEFT JOIN (
                            SELECT
                                tab1.spu_id,
                                SUBSTRING_INDEX(
                                    GROUP_CONCAT(
                                        tab1.spu_name
                                        ORDER BY
                                            ABS(
                                                RIGHT (tab1. LANGUAGE, 6) - 920200
                                            ) DESC
                                    ),
                                    ',',
                                    1
                                ) spu_name
                            FROM
                                {$pms_database}.product_detail tab1
                            WHERE
                                (
                                    tab1. LANGUAGE IN ('N000920700', 'N000920200')
                                )
                            GROUP BY
                                tab1.spu_id
                        ) t8 ON t9.spu_id = t8.spu_id
                        LEFT JOIN (
                            SELECT
                                tab1.spu_id,
                                tab1.charge_unit,
                                tab1.cat_level1
                            FROM
                                {$pms_database}.product tab1
                            GROUP BY
                                tab1.spu_id
                        ) t10 ON t10.spu_id = t9.spu_id
                        LEFT JOIN {$b5c_database}.tb_wms_bill t11 ON t1.bill_id = t11.id
                        LEFT JOIN (
                            SELECT
                                tab2.batch_id,
                                sum(tab2.occupied) AS childOccupied,
                                sum(
                                    tab2.available_for_sale_num
                                ) AS childLocking,
                                tab2.SKU_ID
                            FROM
                                tb_wms_batch_child tab2
                            GROUP BY
                                tab2.batch_id,
                                tab2.SKU_ID
                        ) t12 ON t1.id = t12.batch_id
                        AND t1.SKU_ID = t12.SKU_ID
                        WHERE
                            (
                                t11.type = 1
                                AND t1.vir_type != \"N002440200\"
                                AND t1.vir_type != \"N002410200\"
                                AND t1.total_inventory > 0
                            )
                        GROUP BY
                            t1.SKU_ID,
                            t11.warehouse_id
                    ) AS t111
                GROUP BY
                    t111.warehouse";
        $Model = new Model();
        $warehouse_db_res = $Model->query(DataModel::cleanLineFeed($search_warehouse_sql));
        return $warehouse_db_res;
    }

    /**
     * 仓库停用逻辑：
     * 1.该仓库现存量现货库存数量（包括正品和残次品，不包括在途库存）为0。
     * 2.没有入库仓库为该仓库，且状态为【待审核】或【待出库】或【待入库】的调拨单。
     * @param $warehouse
     * @return bool
     */
    public static function checkCanDisable($warehouse)
    {
        $total_inventory = M('batch', 'tb_wms_')->alias('ba')
            ->join('left join tb_wms_bill bi on bi.id=ba.bill_id')
            ->where(['bi.warehouse_id' => $warehouse, 'bi.type' => 1, 'ba.vir_type' => ['neq', 'N002440200']])
            ->getField('sum(ba.total_inventory)');
        if ($total_inventory != 0) {
            return false;
        }
        $exist_allo = M('allo', 'tb_wms_')
            ->where(['state' => ['in', ['N001970100', 'N001970200', 'N001970300']], 'allo_in_warehouse' => $warehouse])
            ->getField('id');
        if ($exist_allo) {
            return false;
        }
        return true;
    }


    /**在途占用列表
     * @param $queryParams
     * @return array
     */
    public static function safeStockList($params, $flag = null)
    {
        $where = [];
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        empty($params['warehouse_cd']) or $where_strings[] = ' s.warehouse_cd in ("' . implode('","', $params['warehouse_cd']) . '")';
        empty($params['spu_name']) or $where_strings[] = ' j.spu_name like "%' . $params['spu_name'] . '%"';
        empty($params['sku_id']) or $where_strings[] = "(i.sku_id = {$params['sku_id']} or i.upc_id = {$params['sku_id']} or FIND_IN_SET('{$params['sku_id']}',i.upc_more))";
        if (isset($params['stock_status']) && !empty($params['stock_status'])) {
            //状态 -1：当前库存小于设定安全库存
            if (in_array(-1, $params['stock_status'])) {
                $or_strings[] = "(s.current_stock < s.set_safety_stock)";
            }
            //状态 1：当前库存大于或等于设定安全库存
            if (in_array(1, $params['stock_status'])) {
                $or_strings[] = "(s.current_stock >= s.set_safety_stock)";
            }
            if (isset($or_strings) && !empty($or_strings)) {
                $where_strings[] = '(' . implode(' or ', $or_strings) . ')';
            }
        }
        if (isset($where_strings) && !empty($where_strings)) {
            $where_string = implode(' and ', $where_strings);
        }
        $query = M('wms_safety', 'tb_')->alias('s')
            ->field("s.*, IF(i.upc_more, REPLACE(CONCAT_WS(',',i.upc_id,i.upc_more),',',',\\r\\n'), i.upc_id) as upc_id, d.CD_VAL as warehouse_name")
            ->join('left join '.PMS_DATABASE.'.product_sku i on i.sku_id=s.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail j on j.spu_id=i.spu_id')
            ->join('left join tb_ms_cmn_cd d on d.CD=s.warehouse_cd')
            ->where($where)
            ->where($where_string,null,true)
            ->group('s.id')
            ->order('s.sell_by_thirty desc');
        $sql = $query->buildSql();
        $offset = ($params['page'] - 1) * $params['page_size'];
        if ($flag == 1) {
            $list = M()->table($sql.' a')->select();
            return SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes', 'image_url']);
        };
        $ret['list'] = M()->table($sql.' a')->limit($offset . ',' . $params['page_size'])->select();
        $ret['list'] = SkuModel::getInfo($ret['list'], 'sku_id', ['spu_name', 'attributes', 'image_url']);
        $ret['total'] = (int) M()->table($sql.' a')->count();
        $ret['page'] = $params['page'];
        $ret['page_size'] = $params['page_size'];
        return $ret;
    }

    //导出安全库存列表
    public static function exportSafeStock($data)
    {
        include_once __DIR__ . DIRECTORY_SEPARATOR . 'Oms' . DIRECTORY_SEPARATOR . 'ExportModel.class.php';
        $exportExcel = new ExportModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouse_name'],
            $key++ => ['name' => L('SKU编码'), 'field_name' => 'sku_id'],
            $key++ => ['name' => L('条形码'), 'field_name' => 'upc_id'],
            $key++ => ['name' => L('商品名称'), 'field_name' => 'spu_name'],
            $key++ => ['name' => L('商品属性'), 'field_name' => 'attributes'],
            //$key++ => ['name' => L('商品图片'), 'field_name' => 'image_url'],
            $key++ => ['name' => L('7天日均动销'), 'field_name' => 'sell_by_seven'],
            $key++ => ['name' => L('30天日均动销'), 'field_name' => 'sell_by_thirty'],
            $key++ => ['name' => L('90天日均动销'), 'field_name' => 'sell_by_ninety'],

            $key++ => ['name' => L('采购提前期'), 'field_name' => 'pur_lead_time'],
            $key++ => ['name' => L('推荐安全库存'), 'field_name' => 'recommend_safety_stock'],
            $key++ => ['name' => L('设定安全库存'), 'field_name' => 'set_safety_stock'],

            $key++ => ['name' => L('在途库存'), 'field_name' => 'onway_stock'],
            $key++ => ['name' => L('在库库存'), 'field_name' => 'real_stock'],
            $key++ => ['name' => L('在库库存周转天数'), 'field_name' => 'trunover_for_real'],
            $key++ => ['name' => L('总库存'), 'field_name' => 'current_stock'],
            $key++ => ['name' => L('总库存周转天数'), 'field_name' => 'trunover_for_current'],

            //$key++ => ['name' => L('安全库存'), 'field_name' => 'safety_stock'],
            //$key   => ['name' => L('当前库存'), 'field_name' => 'current_stock']//
        ];
        $data = array_map(function($value) {
            $value['sell_by_thirty'] = (int) $value['sell_by_thirty'];
            $value['pur_lead_time'] = (int) $value['pur_lead_time'];
            $value['recommend_safety_stock'] = (int) $value['recommend_safety_stock'];
            $value['set_safety_stock'] = (int) $value['set_safety_stock'];
            $value['current_stock'] = (int) $value['current_stock'];
            return $value;
        }, $data);
        $exportExcel->data = $data;
        $exportExcel->export();
    }

    public function warehouse($params)
    {
        //一般仓 type_cd N002590100
        isset($params['type_cd'] ) or $params['type_cd'] = 'N002590100';
        $warehouse = WarehouseModel::filterWarehouse($params);
        $list = $this->notConfiguredWarehouse(array_column($warehouse, 'CD'), true);
        return $list;
    }

    // 根据CD值获取仓库ID
    public function getWarehouseIdByCode($code)
    {
        return (new WarehouseModel())->where(['CD' => $code])->getField('id');
    }
}