<?php

/**
 * Class OrderExportTemplateService
 */
class OrderExportTemplateService extends Service
{

    protected $repository;

    protected $model = "";
    protected $order_export_template = "";

    //  固定的信息 不参与SQL查询，后期做数据映射
    public static $fixed_product_info = array(
        'product_sku.spu_name_cn' => '中文名称',
        'product_sku.spu_name_en' => '英文名称',
        'product_sku.upc_id' => '条形码',
        'product_sku.attr_name_value' => '属性值',
        'product_sku.sku_size' => '规格',
        'product_sku.sku_weight' => '重量',
        'product_sku.thumbnail' => '商品图片',

    );
    public static $fixed_logistics_info = array(
        'product_sku.hs_code' => '海关申报编码',
    );

    // 包裹信息
    public static $package_info = array(
        'tb_op_order.B5C_ORDER_NO' => '订单号',
        'tb_op_order.ORDER_NO' => '平台订单号',
        'tb_op_order.ORDER_ID' => '平台订单ID',
        'tb_op_order.BWC_ORDER_STATUS' => '订单状态',
        'tb_op_order.ORDER_STATUS' => '平台订单状态',
        'tb_op_order_extend.btc_order_type_cd' => '订单类型',
        'tb_op_order.REMARK_MSG' => '运营备注',
        'tb_op_order.SOURCE' => '订单获取方式',
        'tb_ms_store.STORE_NAME' => '店铺名称',
        'tb_op_order_extend.refund_reason' => '退款原因',
        'tb_op_order.PLAT_CD' => '站点',
        'tb_op_order.PAY_TRANSACTION_ID' => '交易号',
        'tb_op_order.CHILD_ORDER_ID' => '订单ID/子订单ID'
    );
    // 时间信息
    public static $date_info = array(
        'tb_op_order.ORDER_TIME' => '下单时间',
        'tb_op_order.ORDER_PAY_TIME' => '付款时间',
        'tb_op_order.SHIPPING_TIME' => '平台发货时间',
        'tb_op_order.SEND_ORD_TIME' => '派单时间',
        'tb_ms_ord.sendout_time' => '出库时间',

    );
    // 金额信息
    public static  $amount_info = array(
        'tb_op_order.PAY_METHOD' => '支付类型',
        'tb_op_order.PAY_CURRENCY' => '交易币种',
        'tb_op_order.TARIFF' => '税费',
        'tb_op_order.PAY_SHIPING_PRICE' => '运费',
        'tb_op_order.PAY_TOTAL_PRICE' => '支付总价',
        'tb_op_order.PAY_VOUCHER_AMOUNT' => '优惠金额',
        'tb_op_order.PAY_SETTLE_PRICE' => '结算金额',
        'tb_op_order.PAY_TOTAL_PRICE_DOLLAR' => '支付总价（美金）',
        'tb_op_order.PAY_INSTALMENT_SERVICE_AMOUNT' => '手续费',
        'tb_op_order.PAY_SETTLE_PRICE_DOLLAR' => '结算金额（美金）',
        'tb_op_order.PAY_WRAPPER_AMOUNT' => '包装费',


    );
    // 商品信息
    public static  $product_info = array(
        'tb_op_order_guds.B5C_SKU_ID' => 'SKUID',
        'tb_op_order_guds.SKU_ID' => '平台SKUID',
        'tb_op_order_guds.ORDER_ITEM_ID' => '产品ID',
        'tb_op_order_guds.ITEM_NAME' => '平台名称',
        'tb_op_order_guds.ITEM_COUNT' => '数量',
        'tb_op_order_guds.CUSTOMS_PRICE' => '申报价格',
        'tb_op_order_guds.ITEM_PRICE' => '交易价格',
        'tb_op_order.WAREHOUSE' => '下发仓库',
    );
    // 买家信息
    public static  $buyer_info = array(
        'tb_op_order_extend.buyer_user_id' => '买家ID',
        'tb_op_order.ADDRESS_USER_NAME' => '收货人姓名',
        'tb_op_order.ADDRESS_USER_PHONE' => '收货人手机号码',
        'tb_op_order.ADDRESS_USER_IDENTITY_CARD' => '收货人身份证',
        'tb_op_order.RECEIVER_TEL' => '收货人电话',
        'tb_op_order.USER_EMAIL' => '收货人邮箱',
        'tb_op_order.ADDRESS_USER_COUNTRY' => '国家',
        'tb_op_order.ADDRESS_USER_PROVINCES' => '省份',
        'tb_op_order.ADDRESS_USER_CITY' => '市',
        'tb_op_order.ADDRESS_USER_REGION' => '区/县',
        'tb_op_order.ADDRESS_USER_POST_CODE' => '邮编',
        'tb_op_order.ADDRESS_USER_ADDRESS1' => '详细地址1',
        'tb_op_order.BUYER_USER_IDENTITY_CARD' => '买家身份证',
        'tb_op_order_extend.doorplate' => '门牌号',
        'tb_op_order_extend.kr_customs_code' => '通行符',
        'tb_op_order.SHIPPING_MSG' => '用户备注',

    );

    // 物流报关信息
    public static  $logistics_info = array(
        'tb_op_order.logistic_cd' => '物流公司',
        'tb_op_order.logistic_model_id' => '物流方式',
        'tb_ms_ord_package.TRACKING_NUMBER' => '运单号',
        'tb_op_order.PACKING_NO' => '包装号',
        'tb_ms_ord_package.LOGISTIC_STATUS' => '物流状态',
        'tb_op_order.SEND_ORD_STATUS' => '派单状态',

    );
    // 业务信息
    public static  $business_info = array(
        'tb_ms_store.SALE_TEAM_CD' => '销售团队',
        'tb_ms_store.sell_small_team_cd' => '销售小团队',
    );

    // 必须要的字段，用于数据处理
    public static $must_field = array(
        'tb_op_order.ADDRESS_USER_COUNTRY_EDIT' => '国家',
        'tb_op_order.ADDRESS_USER_COUNTRY_CODE' => '国家',
        'tb_op_order.FILE_NAME' => '导入文件名',
        'tb_ms_ord.WHOLE_STATUS_CD' => '订单整体状态',
        'tb_op_order.PARENT_ORDER_ID' => '母单ID',
    );

    public function __construct($model)
    {
        $this->model = empty($model) ? new Model() : $model;
        $this->promotion_demand_table = M('order_export_template','tb_op_');
        $this->repository = new OrderExportTemplateRepository($this->model);
    }



    /**
     *  添加数据【单条】
     */
    public function add($insert_data){
        $res = $this->repository->add($insert_data);
        return $res;
    }
    /**
     * 更新
     */
    public function update($condtion,$update_data){
        $res = $this->repository->update($condtion,$update_data);
        return $res;
    }

    public function getFind($condtion, $field = "*"){
        $info_data = $this->repository->getFind($condtion,$field);
        return $info_data;
    }
    public function getList($request_data){
        $search_data = $request_data['search'];
        $condtion = $this->searchWhere($search_data);
        $field = 'tb_op_order_export_template.name,
        tb_op_order_export_template.id,
        tb_op_order_export_template.field_json,
        tb_op_order_export_template.create_by,
        tb_op_order_export_template.create_at,
        tb_op_order_export_template.update_by,
        tb_op_order_export_template.update_at
        ';
        $list = $this->repository->getList($condtion,$field);
        if ($list){
            foreach ($list as &$value){
                $value['field_str'] = implode(',',array_column(json_decode($value['field_json'],true),'name_cn'));
                $value['field_json'] = json_decode($value['field_json'],true);
            }
        }
        return $list;
    }
    /**
     *  组装列表查询
     * @return array
     */
    private function searchWhere($search_data)
    {
        $condtion = array("1 = 1");
        return $condtion;
    }

    /**
     *  添加数据【单条】
     */
    public function del($condtion){
        $res = $this->repository->delete($condtion);
        return $res;
    }

    /**
     * 权限验证
     * @param $condtion
     */
    public function auth($condtion){
        $info = $this->getFind($condtion,'create_by');
        if ($info) if ($info['create_by'] == userName()) return true;
        return false;
    }


    public function export($data){

        $excelModel = new ExcelModel();
        list($where, $where_temp) = $excelModel->buildWhere($data['wheres']);
        if (!empty($data['ids'])) {
            $order_map = M('order','tb_op_')->where(['ID' => ['in', $data['ids']]])->getField('ID, CHILD_ORDER_ID');
            $child_order_ids = [];
            $data['ids'] = array_filter($data['ids'], function($id) use ($order_map, &$child_order_ids) {
                if (empty($order_map[$id])) {
                    return true;
                }
                $child_order_ids = array_merge($child_order_ids, explode(',',$order_map[$id]));
                return false;
            });
            if (!empty($child_order_ids)) {
                $child_ids   = M('order','tb_op_')->where(['ORDER_ID' => ['IN', $child_order_ids]])->getField('ID', true);
                $data['ids'] = array_merge($data['ids'], $child_ids);
            }
            $where['tb_op_order.ID'] = array('in', $data['ids']);
        }
        // 模板ID
        $export_template_id = $data['export_template_id'];
        $export_template_info = $this->getFind(array('id'=>$export_template_id));
        if (empty($export_template_info)){
            throw new Exception('请选择模板！');
        }
        $field_data = json_decode($export_template_info['field_json'],true);
        if (!$field_data) {
            throw new Exception('模板可选字段异常');
        }

        // 导出参数
        $fields = array_merge(
            array_change_key_case(OrderExportTemplateService::$package_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$date_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$amount_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$product_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$buyer_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$logistics_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$business_info,CASE_LOWER),
            array_change_key_case(OrderExportTemplateService::$must_field,CASE_LOWER)
        );
        $fields = array_unique(array_keys($fields));
        $field_str = implode(',',$fields);
        $product_sku_en = array_keys(array_merge(self::$fixed_product_info,self::$fixed_logistics_info));
        $fixed_fields = ""; //  固定值
        foreach ($product_sku_en as $val){
            $fixed_fields .= "'".$val."',";
        }
        $field_str = $fixed_fields . $field_str;
        $model = new SlaveModel();   // 使用从库查询
        $model->table('tb_op_order');
        $model->field("count(*) as c");
        // 按SKU维度导出
        $model->join("LEFT JOIN tb_op_order_guds ON tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD");
        // 订单扩展表
        $model->join("LEFT JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd");
        // 店铺表
        $model->join("LEFT JOIN tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID");
        // 物流包裹
        $model->join("LEFT JOIN tb_ms_ord_package ON tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd");
        // 派单
        $model->join(" LEFT JOIN  tb_ms_ord ON tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD");
        $model->where($where);
        $model->whereString($where_temp);
        $model->whereString("( tb_op_order.PARENT_ORDER_ID IS NOT NULL OR ( tb_op_order.PARENT_ORDER_ID IS NULL AND tb_op_order.CHILD_ORDER_ID IS NULL ))   ");
        $data = $model->select();
        $query_count = $data[0]['c'];
        if ($query_count == 0){
            throw new Exception('至少选择一个订单！');
        }elseif ($query_count > 300000){
            throw new Exception('满足当前筛选数据超过30万条，暂不支持导出');
        }
        $query = $model->_sql();
        $query = str_replace('count(*) as c',$field_str,$query);
        $dataService = new DataService();
        $excel_name = DataModel::userNamePinyin() . "-订单列表按模板导出-" . time() . '.csv';
        $res = $dataService->addOne($query, 5, $excel_name, $query_count,$export_template_id);
        return $res;
    }
}