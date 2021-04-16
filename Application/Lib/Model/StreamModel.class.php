<?php

/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 17/1/16
 * Time: 14:39
 */
class StreamModel extends RelationModel
{
    protected $trueTableName = "tb_wms_stream";

    protected $_link = [
//        'Goods' => [
//            'mapping_type' => BELONGS_TO ,
//            'class_name' => 'Goods',
//
//        ],
//        'Warehouses' => [
//            'mapping_type' => BELONGS_TO ,
//            'class_name' => 'Warehouse',
//        ],
//        'gud' => [
//            'mapping_type' => HAS_ONE,
//            'class_name' => 'TbMsGuds',
//            'foreign_key' => 'id',
//            'relation_foreign_key' => 'bill_id',
//            'mapping_name' => 'bill',
//            'mapping_key' => 'bill_id',
//            'mapping_fields' => 'bill_id, link_bill_id, warehouse_id, bill_type, bill_date',
//            'condition' => 'tb_wms_stream.GSKU'
//        ],
//        'bill'  => [
//            'mapping_type' => HAS_ONE,
//            'class_name' => 'TbWmsBill',
//            'foreign_key' => 'id',
//            'relation_foreign_key' => 'bill_id',
//            'mapping_name' => 'bill',
//            'mapping_key' => 'bill_id',
//            'mapping_fields' => 'bill_id, link_bill_id, warehouse_id, bill_type, bill_date'
//        ],
//        'batch' => [
//            'mapping_type' => HAS_ONE,
//            'class_name' => 'TbWmsBatch',
//            'foreign_key' => 'stream_id',
//            'relation_foreign_key' => 'id',
//            'mapping_name' => 'batch',
//            'mapping_key' => 'id',
//            'mapping_fields' => 'batch_code'
//            //'condition' => 'tb_crm_contract.CRM_CON_TYPE = 0 and tb_crm_contract.SP_CHARTER_NO is not null and SP_CHARTER_NO != ""',
//        ]
    ];

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        import('ORG.Util.Page');
        parent::__construct($name, $tablePrefix, $connection);
    }

    public $count;
    public $pageSize;
    public $pageIndex;
    public $skuTotal;
    public $outgoingCost;
    public $instorageCost;
    public $instorageNum;
    public $outgoingNum;

    /**
     * 校验出入库单数据条数
     * @param array 查询条件
     * @return array 数据集
     */
    public function checkOrderStorage($params)
    {
        $params['skuUpcType']  or $params['skuUpcType'] = "t1.GSKU";
        $field = [
            'CONCAT(t2.bill_id, "_", t1.id) as child_bill_id',//出入库编号
            //出入库类别(动态获取)
            't2.warehouse_id',//仓库
            't1.GSKU',//SKU编码
            't5.upc_more', # 更多条形码
            't5.upc_id as GUDS_OPT_UPC_ID',//条形码
            //'GROUP_CONCAT(DISTINCT CONCAT(t6.name_detail, \':\', t7.value_detail )  SEPARATOR \';\') as GUDS_OPT_VAL_MPNG',//属性
            't3.batch_code',//批次号
            't3.deadline_date_for_use',//到期日
            't1.send_num',//数量
            't8.charge_unit as VALUATION_UNIT',//单位
            't1.unit_price',//人民币含税单价
            'st2.pur_invoice_tax_rate',//采购发票税率
            't1.unit_price / (1+ifnull(st2.pur_invoice_tax_rate,0)) unit_price_no_tax', //采购单价（不含税）
            't1.unit_price_usd / (1+ifnull(st2.pur_invoice_tax_rate,0)) unit_price_usd_no_tax',//采购单价（不含税）
            //成本(动态计算)
            't2.zd_user',//操作人
            't2.zd_date',//操作时间
            't3.sale_team_code as SALE_TEAM',//销售团队
            't3.small_sale_team_code as SMALL_SALE_TEAM',//销售小团队
            'cd.CD_VAL as billType',// t2.bill_type，收发类别
            't2.relation_type',//关联类型
            'if(t2.link_b5c_no is not null and t2.link_b5c_no <> "",concat(t2.link_b5c_no,"；",t2.link_bill_id), t2.link_bill_id) as link_bill_id',//关联单据号
            't2.link_bill_id as link_bill_id_ori',//关联单据号1
            't2.link_b5c_no',//关联单据号2
            't1.id',
            't1.bill_id',
            't2.bill_id as p_bill_id',
            't2.type',
            't2.vir_type',
            'ifnull(cl.po_cost * t1.send_num, 0) as po_cost ',//po费用
            'ifnull(cl.log_service_cost * t1.send_num , 0) as log_service_cost ',//物流服务费
            'ifnull(cl.carry_cost * t1.send_num , 0) as carry_cost',//运输费
            'ifnull(cl.tariff_cost * t1.send_num , 0) as tariff_cost_sum',//关税sum
            't3.warehouse_cost * t1.send_num as warehouse_cost ',//仓库成本
            'if(t3.vir_type = "N002440400", "残次品", "正品") as productType',//商品类型
            't2.CON_COMPANY_CD as our_company', // 我方公司
            't1.add_time',
            //'t2.ascription_store',
            't3.id as batch_id',
            't1.import_remark',
            'cd2.CD_VAL as currency',
            't3.original_storage_time',
            't1.unit_price_usd',
            't1.unit_price_origin', //采购单价（采购币种，含增值税）
            'round(t1.unit_price_origin / (1+ifnull(t1.pur_invoice_tax_rate,0)),4) as unit_price_no_tax_origin',//采购单价（采购币种，不含增值税）

        ];
        $link_bill_search = '';
        if ($params['linkBillId']) {#关联单据号
            $link_bill_search  = " AND ( t2.link_bill_id LIKE '{$params['linkBillId']}%' OR t2.link_b5c_no LIKE '{$params['linkBillId']}%' )";
        }
        $this->subWhere('_string', "t1.tag = 0 AND t2.vir_type != 'N002440200' {$link_bill_search} ")
            //->subWhere('t3.small_sale_team_code', ['in', $params['small_sale_team']])
            ->subWhere('t3.batch_code', $params['batchCode'])
            ->subWhere('t2.bill_id', ['likeLeft', $params['childBillId']])#出入库编号
            //->subWhere($params['skuUpcType'], ['likeLeft', $params['skuUpcValue']])#sku编码\
            ->subWhere('t2.vir_type', ['in', $params['virType']])
            //->subWhere('t2.ascription_store', ['in', $params['ascription_store']])
            ->subWhere('t2.zd_date', ['xrange', [$params['zdDate'][0], $params['zdDate'][1]]]);

        if (!empty($params['saleTeam'])){
            $this->subWhere('t3.sale_team_code', ['in', $params['saleTeam']]); //#销售团队类型
        }
        if ($params['ourCompany']){
            $this->subWhere('t2.CON_COMPANY_CD', ['in', $params['ourCompany']]);
        }
        if ($params['billType']){
            $this->subWhere('t2.bill_type', ['in', $params['billType']]);   #出入库类型
        }
        if ($params['relationType']){
            $this->subWhere('t2.relation_type', ['in', $params['relationType']]);
        }
        if ($params['warehouse']){
            $this->subWhere('t2.warehouse_id', ['in', $params['warehouse']]);#仓库
        }
        
        #sku编码\
        if($params['skuUpcType'] && $params['skuUpcValue']) {
            $params['skuUpcValue'] = trim($params['skuUpcValue']);
            if ($params['skuUpcType'] == 't5.upc_id') {
                $complex[$params['skuUpcType']] = ['like', "{$params['skuUpcValue']}%"];
                $complex['_string']             = "FIND_IN_SET('{$params['skuUpcValue']}',t5.upc_more)";
                $complex['_logic']              = 'or';
                self::$where['_complex']        = $complex;
            } else {
                if (strlen($params['skuUpcValue']) == 10) {
                    //等于sku的长度，精确匹配
                    $this->subWhere($params['skuUpcType'], ['eq', $params['skuUpcValue']]);#sku编码\
                } else {
                    $this->subWhere($params['skuUpcType'], ['likeLeft', $params['skuUpcValue']]);#sku编码\
                }
            }

        }

        if ($params['small_sale_team']) {
            if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = "('". implode("','", $params['small_sale_team']). "')";
                $this->subWhere('_string', " ( t3.small_sale_team_code IS NULL OR t3.small_sale_team_code = '' OR t3.small_sale_team_code in ( {$small_sale_team} ) )");
            }else{
                $this->subWhere('t3.small_sale_team_code', ['in', $params['small_sale_team']]);
            }
        }
        if ($params['GUDSNM']) {
            $sku_ids = SkuModel::titleToSku($params['GUDSNM']);
            $this->subWhere('t3.SKU_ID', ['in', $sku_ids]);
        }

        if ($params['productType']) {
            if ($params['productType'] == 2) {
                $this->subWhere('t3.vir_type', 'N002440400');
            } else {
                self::$where['t3.vir_type'] = ['neq', 'N002440400'];
            }
        }
        //statistics
        empty($params['type']) or static::$where ['t2.type'] = ['in', $params['type']] ;

        if ($params['zdUser']) {
            $model = new Model();
            $u = $model->table('bbm_admin')->field('M_ID')->where(['M_NAME' => ['like', '%'.$params['zdUser'] . '%']])->find();
            if (empty($u)){
                self::$where['t2.zd_user'] = ['eq', ''];
            }else{
                self::$where['t2.zd_user'] = ['eq', $u['M_ID']];
            }
        }

        if (!empty($params['product_key'])) {
            list($bill_ids, $sku_id) = $this->getProductKeyRelBillIds($params['product_key']);
            self::$where['t2.id'] = ['in', $bill_ids];
            self::$where['t1.GSKU'] = $sku_id;
        }

        $model = new SlaveModel();
        if ($params['calcStat']) {
            $statisticsFields = [
                'count(DISTINCT t1.id) as amount',
                'count(DISTINCT t1.GSKU) as sku_total',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd END)) as outgoing',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0)) END ) ) AS outgoingNoTax',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num END)) as outgoing_num',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd END)) as instorage',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0))  END ) ) AS instorageNoTax',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num END)) as instorage_num',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num * t1.unit_price_usd  END)) as onway',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0))  END ) ) AS onwayNoTax',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num END)) as onway_num',
            ];
            $statistics = $model->field($statisticsFields)
                ->table(B5C_DATABASE . '.tb_wms_stream t1')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
                ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
                ->join('left join ' . B5C_DATABASE . '.tb_pur_order_detail t6 ON t2.procurement_number = t6.procurement_number')
                ->where(static::$where)
                ->select();
            $ret = [];
            $ret['skuTotal'] = $statistics [0]['sku_total']?$statistics [0]['sku_total']:0;
            $ret['outgoingCost'] = number_format($statistics [0]['outgoing'], 3)?format_number($statistics [0]['outgoing']):0;
            $ret['outgoingCostNoTax'] = number_format($statistics[0]['outgoingNoTax'], 3) ? format_number($statistics[0]['outgoingNoTax']) : 0;
            $ret['instorageCost'] = number_format($statistics [0]['instorage'], 3)?format_number($statistics [0]['instorage']):0;
            $ret['instorageCostNoTax'] = number_format($statistics[0]['instorageNoTax'], 3) ? format_number($statistics[0]['instorageNoTax']) : 0;
            $ret['instorageNum'] = $statistics [0]['instorage_num']?number_format($statistics [0]['instorage_num']):0;
            $ret['outgoingNum'] = $statistics [0]['outgoing_num']?number_format($statistics [0]['outgoing_num']):0;
            $ret['onway'] = $statistics [0]['onway']?format_number($statistics [0]['onway']):0;
            $ret['onwayNoTax'] = $statistics[0]['onwayNoTax'] ? format_number($statistics[0]['onwayNoTax']) : 0;
            $ret['onwayNum'] = $statistics [0]['onway_num']?number_format($statistics [0]['onway_num']):0;
            return $ret;
        }
        $check_where = array (
            '_string' => 't1.tag = 0',
            't2.vir_type' =>
                array (
                    0 => 'in',
                    1 =>
                        array (
                            0 => 'N002440100',
                        ),
                ),
        );
        if (serialize(static::$where) == serialize($check_where)) {
            $temp_where['t1.tag'] = 0;
            $temp_where['t2.vir_type'] = 'N002440100'; // 现货库存
            $count = $model->table(B5C_DATABASE . '.tb_wms_stream t1')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
                ->where($temp_where)
                ->count();
        }else{

            $count = $model->table(B5C_DATABASE . '.tb_wms_stream t1')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
//                ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
                ->where(static::$where)
                ->count();
        }
        //exec
        $subQuery = $model->field($field)
            ->table(B5C_DATABASE . '.tb_wms_bill t2')
            // STRAIGHT_JOIN 优化t2为驱动表，去除file_sort
            ->join('STRAIGHT_JOIN ' . B5C_DATABASE . '.tb_wms_stream t1 ON t1.bill_id = t2.id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_stream_cost_log cl ON cl.stream_id = t1.id and cl.currency_id=\'N000590300\'')
            ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
            ->join('left join ' . PMS_DATABASE . '.product t8 ON t8.spu_id = t5.spu_id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_stream st2 ON st2.id = t3.stream_id')
            ->join('left join ' . B5C_DATABASE . '.tb_ms_cmn_cd cd ON cd.cd = t2.bill_type')
            ->join('left join ' . B5C_DATABASE . '.tb_ms_cmn_cd cd2 ON cd2.cd = t1.currency_id')
            ->where(static::$where)
            //数据量太大不加条数限制无法执行
            ->limit(0, 10)
            ->order('t2.id desc');
        $ret = $subQuery->select();
        $total = $count;
        $query = $subQuery->_sql();
        //截去限制条数的语句
        $query = substr($query, 0, -12);
        return array($total,$query);
    }

    /**
     * 出入库单数据
     * @param $params
     * @param bool $isExport
     * @param bool $isCacheTotalCount 是否缓存总条数
     * @return array|mixed
     */
    public function orderStorage($params, $isExport = false, $isCacheTotalCount = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        $params['skuUpcType']  or $params['skuUpcType'] = "t1.GSKU";
        is_null($params['pageSize'])  or $pageSize = $params['pageSize'];
        is_null($params['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params['pageIndex'];

        $field = [
            'CONCAT(t2.bill_id, "_", t1.id) as child_bill_id',//出入库编号
            //出入库类别(动态获取)
            't2.procurement_number',// 采购单号
            't2.warehouse_id',//仓库
            't1.GSKU',//SKU编码
            't5.upc_more', # 更多条形码
            't5.upc_id as GUDS_OPT_UPC_ID',//条形码
            //'GROUP_CONCAT(DISTINCT CONCAT(t6.name_detail, \':\', t7.value_detail )  SEPARATOR \';\') as GUDS_OPT_VAL_MPNG',//属性
            't3.batch_code',//批次号
            't3.deadline_date_for_use',//到期日
            't1.send_num',//数量
            't8.charge_unit as VALUATION_UNIT',//单位
            't1.unit_price',//人民币含税单价
            'st2.pur_invoice_tax_rate',//采购发票税率
            't1.unit_price / (1+ifnull(st2.pur_invoice_tax_rate,0)) unit_price_no_tax', //采购单价（不含税）
            't1.unit_price_usd / (1+ifnull(st2.pur_invoice_tax_rate,0)) unit_price_usd_no_tax',//采购单价（不含税）
            //成本(动态计算)
            't2.zd_user',//操作人
            't2.zd_date',//操作时间
            't3.sale_team_code as SALE_TEAM',//销售团队
            't3.small_sale_team_code as SMALL_SALE_TEAM',//销售小团队
            'cd.CD_VAL as billType',// t2.bill_type，收发类别
            't2.relation_type',//关联类型
            'if(t2.link_b5c_no is not null and t2.link_b5c_no <> "",concat(t2.link_b5c_no,"；",t2.link_bill_id), t2.link_bill_id) as link_bill_id',//关联单据号
            't2.link_bill_id as link_bill_id_ori',//关联单据号1
            't2.link_b5c_no',//关联单据号2
            't1.id',
            't1.bill_id',
            't2.bill_id as p_bill_id',
            't2.type',
            't2.vir_type',
            'ifnull(cl.po_cost * t1.send_num, 0) as po_cost ',//po费用
            'ifnull(cl.log_service_cost * t1.send_num , 0) as log_service_cost ',//物流服务费
            'ifnull(cl.carry_cost * t1.send_num , 0) as carry_cost',//运输费
            'ifnull(cl.tariff_cost * t1.send_num , 0) as tariff_cost_sum',//关税sum
            't3.warehouse_cost * t1.send_num as warehouse_cost ',//仓库成本
            'if(t3.vir_type = "N002440400", "残次品", "正品") as productType',//商品类型
            't2.CON_COMPANY_CD as our_company', // 我方公司
            't1.add_time',
            //'t2.ascription_store',
            't3.id as batch_id',
            't1.import_remark',

            'cd2.CD_VAL as currency',
            't3.original_storage_time',
            't1.unit_price_usd',
            't1.unit_price_origin', //采购单价（采购币种，含增值税）
            'round(t1.unit_price_origin / (1+ifnull(t1.pur_invoice_tax_rate,0)),4) as unit_price_no_tax_origin',//采购单价（采购币种，不含增值税）
            't9.is_oem_brand',
            't2.excel_name',
            't2.bill_type as bill_type_cd'
        ];
        $link_bill_search = '';
        if ($params['linkBillId']) {#关联单据号
        $link_bill_search  = " AND ( t2.link_bill_id LIKE '{$params['linkBillId']}%' OR t2.link_b5c_no LIKE '{$params['linkBillId']}%' )";
        }
        $this->subWhere('_string', "t1.tag = 0 AND t2.vir_type != 'N002440200' {$link_bill_search} ")
            //->subWhere('t3.small_sale_team_code', ['in', $params['small_sale_team']])
            ->subWhere('t3.batch_code', $params['batchCode'])
            ->subWhere('t2.bill_id', ['likeLeft', $params['childBillId']])#出入库编号
            //->subWhere($params['skuUpcType'], ['likeLeft', $params['skuUpcValue']])#sku编码\
            ->subWhere('t2.vir_type', ['in', $params['virType']])
            //->subWhere('t2.ascription_store', ['in', $params['ascription_store']])
            ->subWhere('t2.zd_date', ['xrange', [$params['zdDate'][0], $params['zdDate'][1]]]);
        if (!empty($params['saleTeam'])){
            $this->subWhere('t3.sale_team_code', ['in', $params['saleTeam']]); //#销售团队类型
        }
        if ($params['ourCompany']){
            $this->subWhere('t2.CON_COMPANY_CD', ['in', $params['ourCompany']]);
        }
        if ($params['billType']){
            $this->subWhere('t2.bill_type', ['in', $params['billType']]);   #出入库类型
        }
        if ($params['relationType']){
            $this->subWhere('t2.relation_type', ['in', $params['relationType']]);
        }
        if ($params['warehouse']){
            $this->subWhere('t2.warehouse_id', ['in', $params['warehouse']]);#仓库
        }
            #sku编码\
           if($params['skuUpcType'] && $params['skuUpcValue']) {
               $params['skuUpcValue'] = trim($params['skuUpcValue']);
               if ($params['skuUpcType'] == 't5.upc_id') {
                   $complex[$params['skuUpcType']] = ['like', "{$params['skuUpcValue']}%"];
                   $complex['_string']             = "FIND_IN_SET('{$params['skuUpcValue']}',t5.upc_more)";
                   $complex['_logic']              = 'or';
                   self::$where['_complex']        = $complex;
               } else {
                   if (strlen($params['skuUpcValue']) == 10) {
                       //等于sku的长度，精确匹配
                       $this->subWhere($params['skuUpcType'], ['eq', $params['skuUpcValue']]);#sku编码\
                   } else {
                       $this->subWhere($params['skuUpcType'], ['likeLeft', $params['skuUpcValue']]);#sku编码\
                   }
               }

           }

        if ($params['small_sale_team']) {
            if (in_array('0',$params['small_sale_team'])){
                $small_sale_team = "('". implode("','", $params['small_sale_team']). "')";
                $this->subWhere('_string', " ( t3.small_sale_team_code IS NULL OR t3.small_sale_team_code = '' OR t3.small_sale_team_code in ( {$small_sale_team} ) )");
            }else{
                $this->subWhere('t3.small_sale_team_code', ['in', $params['small_sale_team']]);
            }
        }
        if ($params['GUDSNM']) {
            $sku_ids = SkuModel::titleToSku($params['GUDSNM']);
            $this->subWhere('t3.SKU_ID', ['in', $sku_ids]);
        }
      
        if ($params['productType']) {
            if ($params['productType'] == 2) {
                $this->subWhere('t3.vir_type', 'N002440400');
            } else {
                self::$where['t3.vir_type'] = ['neq', 'N002440400'];
            }
        }
        //statistics
        empty($params['type']) or static::$where ['t2.type'] = ['in', $params['type']] ;

        if ($params['zdUser']) {
            $model = new Model();
            $u = $model->table('bbm_admin')->field('M_ID')->where(['M_NAME' => ['like', '%'.$params['zdUser'] . '%']])->find();
            if (empty($u)){
                self::$where['t2.zd_user'] = ['eq', ''];
            }else{
                self::$where['t2.zd_user'] = ['eq', $u['M_ID']];
            }
        }
        
        if (!empty($params['product_key'])) {
           list($bill_ids, $sku_id) = $this->getProductKeyRelBillIds($params['product_key']);
            self::$where['t2.id'] = ['in', $bill_ids];
            self::$where['t1.GSKU'] = $sku_id;
        }
//        is_import :::是否导入   0 否  1 是
//        is_replace ::: 是否一件代发（相当于是否是采购入库）  0 否  1 是
        if ($params['is_import'] == 1 ){
            $this->subWhere('_string',' t2.excel_name is not null ');
            if ($params['is_replace'] == 1){
                self::$where['t2.bill_type'] = 'N000940100';
            }elseif ($params['is_replace'] === 0 || $params['is_replace'] === '0'){
                self::$where['t2.bill_type'] = array('NEQ','N000940100');
            }
        }elseif ($params['is_import'] === 0 ||$params['is_import'] === '0' ){
            $this->subWhere('_string',' t2.excel_name is null ');
        }
        
        if ($params['is_oem_brand']) {
            $this->subWhere('t9.is_oem_brand',  ['in', $params['is_oem_brand']]);
        }
        $model = new SlaveModel();
        if ($params['calcStat']) {
            //数据汇总逻辑
            //20201211改成统计除在途库存外数据（未改之前只统计现货类型）
            $statisticsFields = [
                'count(DISTINCT t1.id) as amount',
                'count(DISTINCT t1.GSKU) as sku_total',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd END)) as outgoing',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0)) END ) ) AS outgoingNoTax',
                'SUM((CASE WHEN t2.type = 0 AND t2.vir_type != "N002440200" THEN t1.send_num END)) as outgoing_num',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd END)) as instorage',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0))  END ) ) AS instorageNoTax',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type != "N002440200" THEN t1.send_num END)) as instorage_num',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num * t1.unit_price_usd  END)) as onway',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num * t1.unit_price_usd / (1+ifnull(t1.pur_invoice_tax_rate,0))  END ) ) AS onwayNoTax',
                'SUM((CASE WHEN t2.type = 1 AND t2.vir_type = "N002440200" THEN t1.send_num END)) as onway_num',
            ];
            $statistics = $model->field($statisticsFields)
                ->table(B5C_DATABASE . '.tb_wms_stream t1')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
                ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
                ->join('left join ' . B5C_DATABASE . '.tb_pur_order_detail t6 ON t2.procurement_number = t6.procurement_number')
                ->where(static::$where)
                ->select();
            $ret = [];
            $ret['skuTotal'] = $statistics [0]['sku_total']?$statistics [0]['sku_total']:0;
            $ret['outgoingCost'] = number_format($statistics [0]['outgoing'], 3)?format_number($statistics [0]['outgoing']):0;
            $ret['outgoingCostNoTax'] = number_format($statistics[0]['outgoingNoTax'], 3) ? format_number($statistics[0]['outgoingNoTax']) : 0;
            $ret['instorageCost'] = number_format($statistics [0]['instorage'], 3)?format_number($statistics [0]['instorage']):0;
            $ret['instorageCostNoTax'] = number_format($statistics[0]['instorageNoTax'], 3) ? format_number($statistics[0]['instorageNoTax']) : 0;
            $ret['instorageNum'] = $statistics [0]['instorage_num']?number_format($statistics [0]['instorage_num']):0;
            $ret['outgoingNum'] = $statistics [0]['outgoing_num']?number_format($statistics [0]['outgoing_num']):0;
            $ret['onway'] = $statistics [0]['onway']?format_number($statistics [0]['onway']):0;
            $ret['onwayNoTax'] = $statistics[0]['onwayNoTax'] ? format_number($statistics[0]['onwayNoTax']) : 0;
            $ret['onwayNum'] = $statistics [0]['onway_num']?number_format($statistics [0]['onway_num']):0;
            return $ret;
        }
        $check_where = array (
            '_string' => 't1.tag = 0',
            't2.vir_type' =>
                array (
                    0 => 'in',
                    1 =>
                        array (
                            0 => 'N002440100',
                        ),
                ),
        );
        if (serialize(static::$where) == serialize($check_where)) {
            $temp_where['t1.tag'] = 0;
            $temp_where['t2.vir_type'] = 'N002440100'; // 现货库存
            $count = $model->table(B5C_DATABASE . '.tb_wms_stream t1')
                ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
                ->where($temp_where)
                ->count();
        }else{
            $redis = RedisModel::connect_init();
            $cache_count  = $redis->get('StockListDataCount');

            if (count(static::$where) == 1 && trim(static::$where['_string']) == trim("t1.tag = 0 AND t2.vir_type != 'N002440200'") && $cache_count > 0 && $isCacheTotalCount) {
                #搜索参数  使用缓存
                $count = $cache_count;
               
            } else {
                $count = $model->table(B5C_DATABASE . '.tb_wms_stream t1')
                    ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
                    ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
                    ->join('left join ' . PMS_DATABASE . '.product t8 ON t8.spu_id = t5.spu_id')
                    ->join('left join ' . PMS_DATABASE . '.product_brand t9 ON t8.brand_id = t9.brand_id')
                    ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
                    ->where(static::$where)
                    ->count();
            }

        }
        $Page  = new Page($count, $pageSize);
        //exec
        $subQuery = $model->field($field)
            ->table(B5C_DATABASE . '.tb_wms_bill t2')
            // STRAIGHT_JOIN 优化t2为驱动表，去除file_sort
            ->join('STRAIGHT_JOIN ' . B5C_DATABASE . '.tb_wms_stream t1 ON t1.bill_id = t2.id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_stream_cost_log cl ON cl.stream_id = t1.id and cl.currency_id=\'N000590300\'')
            ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
            ->join('left join ' . PMS_DATABASE . '.product t8 ON t8.spu_id = t5.spu_id')
            ->join('left join ' . PMS_DATABASE . '.product_brand t9 ON t8.brand_id = t9.brand_id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_stream st2 ON st2.id = t3.stream_id')
            ->join('left join ' . B5C_DATABASE . '.tb_ms_cmn_cd cd ON cd.cd = t2.bill_type')
            ->join('left join ' . B5C_DATABASE . '.tb_ms_cmn_cd cd2 ON cd2.cd = t1.currency_id')
            ->where(static::$where)
            ->order('t2.id desc');
        if ($isExport == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->select();
        $ret = SkuModel::getInfo($ret, 'GSKU', ['spu_name','image_url','attributes'],['spu_name'=>'GUDS_NM','image_url'=>'GUDSIMGCDNADDR','attributes'=>'GUDS_OPT_VAL_MPNG']);
        $ret = $this->filter($ret);

        $this->pageIndex     = $pageIndex;
        $this->pageSize      = $pageSize;
        $this->count         = $count;

        return $ret ? $ret : [];
    }

    public static $where;
    /**
     * 构建查询条件
     * @param mixed $str
     * @return array
     */
    public function subWhere($key, $str)
    {
        if (is_array($str)) {
            list($pattern, $val) = $str;
            if ($val) {
                switch ($pattern){
                    case 'in':
                        static::$where [$key] = ['in', $val];
                        break;
                    case 'like':
                        static::$where [$key] = ['like', '%' . $val . '%'];
                        break;
                    case 'likeLeft':
                        static::$where[$key] = ['like', $val . '%'];
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
                        static::$where [$key] = $val;
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

    /**
     * 商品属性
     * @param $val
     * @return string
     */
    public function gudsOptsMerge($val)
    {
        $str = explode(';', $val);
        $opt = BaseModel::getGudsOpt();
        $shtml = '';
        $length = count($str);
        for ($i = 0; $i < $length; $i++) {
            if ($opt[$str[$i]]['OPT_CNS_NM'] and $opt[$str[$i]]['OPT_VAL_CNS_NM']) $shtml .= $opt[$str[$i]]['OPT_CNS_NM'] . ':' . $opt[$str[$i]]['OPT_VAL_CNS_NM'] . ' ';
            else $shtml .= $opt[$str[$i]]['OPT_VAL_CNS_NM'];
        }
        return $shtml;
    }

    /**
     * 商品图片获取
     * 与当前语言设置相符的，取当前语言相符的图片
     * 与当前语言不符的，取英语类型图片
     * 若英语图片不存在，则取任意存在的图片
     */
    public function getGudsImg($sku_ids)
    {
        $tmp = [];
        $imgs = [];
        foreach ($sku_ids as $k => $v) {
            $tmp [$v] = substr($v, 0, 8);
        }
        $language = BaseModel::languages()[LANG_SET]['CD'];
        $englishLanguage = 'N000920200';
        $model = new Model();
        $conditions['MAIN_GUDS_ID'] = ['in', $tmp];
        $conditions['GUDS_IMG_CD'] = ['eq', 'N000080200'];
        $ret = $model->table('tb_ms_guds_img')
            ->where($conditions)
            ->field(['MAIN_GUDS_ID', 'GUDS_IMG_CDN_ADDR', 'LANGUAGE'])
            ->select();
        // GUDS_ID 相关联语种图片
        foreach ($ret as $k => $v) {
            $imgs [$v ['MAIN_GUDS_ID']][] = $v;
        }
        // 每个 GUDS_ID 对应有多个图片
        foreach ($imgs as $k => $v) {
            $currentImgs [$k] = array_column($v, 'LANGUAGE');
        }

        // 按当前语种、英语、其他语种，进行语言图片保留，值保留一个图片
        foreach ($currentImgs as $k => $v) {
            if (in_array($language, $v)) {
                $retainLanguage [$k] = $language;
            } elseif (in_array($englishLanguage, $v)) {
                $retainLanguage [$k] = $englishLanguage;
            } else {
                $retainLanguage [$k] = $v [0];
            }
        }
        foreach ($imgs as $k => $v) {
            foreach ($v as $i => $j) {
                if ($j ['LANGUAGE'] == $retainLanguage [$k]) {
                    $_img [$k] = $j;
                    continue;
                }
            }
        }
        foreach ($tmp as $k => $v) {
            $new_retain [$k] = $_img [$v]['GUDS_IMG_CDN_ADDR'];
        }

        return $new_retain;
    }

    /**
     * 数据处理
     * @param mixed $data
     * @return array
     */
    public function filter($data)
    {
        $ret = BaseModel::convertUnder($data);
        $users = $this->getUser();
        $AllocationExtendAttributionRepository = new AllocationExtendAttributionRepository();
        $store_list = $AllocationExtendAttributionRepository->getStoreName();

        foreach ($ret as &$r) {

            // 是否属于一件代发单据
            if (!empty($r['excelName']) && $r['billTypeCd'] == 'N000940100'){
                $r['is_replace'] = 1;
            }else{
                $r['is_replace'] = 0;
            }

            // 出入库类型
            if ($r['relationType'] == 'N002350705'){
                $r['linkBillId'] = $r['linkBillIdOri'];
                $r['linkB5cNo'] = "";
            }
            $r ['type'] = $r ['type'] == 1 ? L('入库') : L('出库');
            $r['originalStorageTime'] = date('Ymd', strtotime($r['originalStorageTime']));

            // $tmpUnitPriceLast =  $r['unitPrice'] * exchangeRateConversion('CNY', $r['currency'], $r['originalStorageTime']);
            $tmpUnitPriceUSD = $r['unitPriceUsd'];
            // $tmpUnitPriceNoTaxLast = $r['unitPriceNoTax'] * exchangeRateConversion('CNY', $r['currency'], $r['originalStorageTime']);
            $tmpUnitPriceNoTaxUSD  = $r['unitPriceUsdNoTax'];

            $r['unitPriceLast'] = number_format($r['unitPriceOrigin'], 4); //采购单价(含增值税) 乘以汇率得出原币种价格
            $r['unitPriceUSD'] = number_format($tmpUnitPriceUSD, 4); //采购单价(含增值税)美元价格
            $r['unitPriceNoTaxLast'] = number_format($r['unitPriceNoTaxOrigin'], 4); //采购单价(不增值税) 乘以汇率得出原币种价格
            $r['unitPriceNoTaxUSD'] = number_format($tmpUnitPriceNoTaxUSD, 4); //采购单价(不含增值税) 乘以汇率得出美元价格
            $r['unitPriceNoTax'] = number_format($r['unitPriceNoTax'], 4);
            $r['unitPrice'] = number_format($r['unitPrice'], 4);



            $r['amountPrice']   = number_format($r['sendNum'] * str_replace(',', '', $r['unitPrice']), 4); // 总价,采购成本（含增值税）
            $r['amountPriceLast'] = number_format($r['sendNum'] *  str_replace(',', '', $r['unitPriceLast']), 4); //总价,采购成本（含增值税）（原币种）
            $r['amountPriceUSD'] = number_format($r['sendNum'] * str_replace(',', '', $r['unitPriceUSD']), 4); //总价,采购成本（含增值税）（美元）
            $r['amountPriceNoTax']   = number_format($r['sendNum'] * str_replace(',', '', $r['unitPriceNoTax']), 4); // 总价,采购成本（不含增值税）
            $r['amountPriceNoTaxLast'] = number_format($r['sendNum'] * str_replace(',', '', $r['unitPriceNoTaxLast']), 4); //总价,采购成本（含增值税）（原币种）
            $r['amountPriceNoTaxUSD'] = number_format($r['sendNum'] * str_replace(',', '', $r['unitPriceNoTaxUSD']), 4);//总价,采购成本（含增值税）（美元）
            
            unset($r['unitPriceOrigin']);
            unset($r['unitPriceNoTaxOrigin']);
            
            $r ['VALUATIONUNIT'] = $this->mappingValue() [$r ['VALUATIONUNIT']];// 单位
            $r ['warehouseId']   = $this->mappingValue() [$r ['warehouseId']];// 仓库
            $r ['SALETEAM']      = $this->mappingValue() [$r ['SALETEAM']];// 销售团队
            $r ['SMALLSALETEAM']      = $this->mappingValue() [$r ['SMALLSALETEAM']];// 销售团队
            $r ['ourCompany']      = $this->mappingValue() [$r ['ourCompany']];// 我方公司
            $r ['relationType']  = $this->mappingValue() [$r ['relationType']];// 关联单据
            $r ['zdUser']        =  $users[$r ['zdUser']];//操作人
            $r ['virType']       = $this->mappingValue() [$r ['virType']];
//            $r['usdXchr'] = StandingExistingModel::getXchr('N000590100', $r['addTime']);
//            $r['warehouseCost'] = round($r['warehouseCost'] * $r['usdXchr'], 2);
            $r['ascription_store_val'] = $store_list[$r['ascriptionStore']];
            # $r['GUDSOPTUPCID'] = "\t".$r['GUDSOPTUPCID']."\t";
            if($r['upcMore']) {
                $upc_more_arr = explode(',', $r['upcMore']);
                array_unshift( $upc_more_arr,$r['GUDSOPTUPCID']);
                $r['GUDSOPTUPCID'] = implode("\r,", $upc_more_arr);
            }
        }
        unset($r);
       
        return $ret;
    }

    public function getUser()
    {
        $model = new Model();
        $ret = $model->table('bbm_admin')->getField('M_ID, M_NAME');
        return $ret;
    }

    public function langSet()
    {
        $ret = $this->table('tb_ms_cmn_cd')
            ->where('cd like "N000920%"')
            ->getField('ETC2 as etc2, CD as cd');

        return $ret [LANG_SET];
        //echo LANG_SET;exit;
    }

    public static $mappingValue;

    public function mappingValue()
    {
        if (static::$mappingValue)
            return static::$mappingValue;

        $condition ['_string'] = 'CD like "N000950%" OR ';//出库类型
        $condition ['_string'] .= 'CD like "N000940%" OR ';//入库类型
        $condition ['_string'] .= 'CD like "N00068%" OR ';//仓库
        $condition ['_string'] .= 'CD like "N00128%" OR ';//销售团队
        $condition ['_string'] .= 'CD like "N00323%" OR ';//销售小团队
        $condition ['_string'] .= 'CD like "N00069%" OR ';//单位
        $condition ['_string'] .= 'CD like "N000920%" OR ';//语言类型
        $condition ['_string'] .= 'CD like "N002350%" OR ';//单据类型
        $condition ['_string'] .= 'CD like "N00124%" OR ';// 我方公司类型
        $condition ['_string'] .= 'CD like "N00244%"';  // 现货入库、在途入库


        $ret = $this->table('tb_ms_cmn_cd')
            ->where($condition)
            ->getField('CD as cd, CD_VAL as cdVal');
        static::$mappingValue = $ret;

        return $ret;
    }

    /**
     * 入库类型
     * @return array
     */
    public function inStorage()
    {
        return [
            'N000940100',
            'N000940200',
            'N000940300',
            'N000940400',
            'N000940500'
        ];
    }

    /**
     * 构建子查询
     * @param string $tableName
     * @param mixed  $field
     * @param mixed  $where
     */
    public function subQuery($tableName, $field = '', $where = '')
    {
        return $this->table($tableName)->field($field)->where($where)->buildSql();
    }

    public function getProductKeyRelBillIds($product_key)
    {
        $product_key_arr = explode('-', trim($product_key));
        $sku_id          = $product_key_arr[0];
        if (!is_numeric($sku_id)) {
            return false;
        }
        $table = $this->getTable($sku_id);
        $model = new Model();
        $model->db(1, 'DATA_DB');
        $key_info = $model->query("select * from $table where product_key = '{$product_key}'");
        $product_key_ids = array_column($key_info, 'id');

        $where_str = '(';
        $bill_ids = $group_bill_ids = [];
        foreach ($product_key_ids as $product_key_id) {
            $where_str .= "(product_key_action_id <= $product_key_id and product_key_end_id >= $product_key_id) or ";
        }
        if (!empty($where_str)) {
            $where_str = trim($where_str, 'or '). ") and sku_id = '{$sku_id}'";
            $map_info = $model->query("select * from product_key_map where $where_str");
            $bill_ids = array_column($map_info, 'bill_id');
        }
        $group_product_key = $model->query("select * from group_key_map where product_key = '{$product_key}'");
        if (!empty($group_product_key)) {
            $where_str = '(';
            foreach ($group_product_key as $value) {
                $product_key_id = $value['id'];
                $group_sku_id   = $value['group_sku_id'];
                $where_str .= "(product_key_action_id <= $product_key_id and product_key_end_id >= $product_key_id) or ";
            }
            if (!empty($where_str)) {
                $where_str = trim($where_str, 'or '). ") and group_sku_id = '{$group_sku_id}'";
                $group_map_info = $model->query("select * from group_key_map where $where_str");
                $group_bill_ids = array_column($group_map_info, 'bill_id');
            }
        }
        return [array_merge($bill_ids, $group_bill_ids), $sku_id];
    }

    private function getTable($sku_id)
    {
        return 'product_key_0'. substr($sku_id, -3, 1);
    }
}