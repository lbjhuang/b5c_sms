<?php

/**
 * User: yangsu
 * Date: 18/3/12
 * Time: 11:01
 */
class ExcelModel
{
    private $mapping_where_arr = [
        "platform" => "tb_op_order.PLAT_CD",
        "mark_status" => "tb_op_order.is_mark",
        "order_status" => "tb_op_order.BWC_ORDER_STATUS",
        "order_source_status" => "tb_op_order.order_origin",
        // "dispatch_status" => "tb_op_order.SEND_ORD_STATUS", //
        "do_dispatch_status" => "tb_op_order.SEND_ORD_TYPE_CD", //
        "find_order_error_type" => "tb_op_order.FIND_ORDER_ERROR_TYPE",
        "surfaceWayGetCd" => "tb_op_order.surface_way_get_cd",
        "logisticsSingleStatuCd" => "tb_op_order.LOGISTICS_SINGLE_STATU_CD",
        // "logistics_status" => "",// 物流状态
        // "aftermarket" => "", // 售后状态
        // "sort" => "",
        // "remark_have" => "REMARK_STAT_CD",
        "country" => "tb_op_order.ADDRESS_USER_COUNTRY_ID",
        "shop" => "tb_op_order.STORE_ID",
        "warehouse" => "tb_op_order.WAREHOUSE",
        "logistics_company" => "logistic_cd",
        "logistics_method" => "tb_op_order.logistic_model_id",

        "is_remote_area" => "tb_op_order.is_remote_area",

        "is_remote_area_val" => "tb_op_order.is_remote_area",

        "buyer_user_id" => "tb_op_order_extend.buyer_user_id",

        "search_time_type" => [
            'order_time'    => 'tb_op_order.ORDER_TIME',
            'pay_time'      => 'tb_op_order.ORDER_PAY_TIME',
            'send_time'     => 'tb_op_order.SHIPPING_TIME', // SEND_ORD_TIME
            'send_ord_time' => 'tb_op_order.SEND_ORD_TIME',
            's_time4'       => 'tb_ms_ord.S_TIME4',
            'sendout_time'       => 'tb_ms_ord.sendout_time',
        ],
        "search_condition" => [
            'order_id' => 'tb_op_order.B5C_ORDER_NO',
            'thr_order_id' => 'tb_op_order.ORDER_ID',
            'thr_order_no' => 'tb_op_order.ORDER_NO',
            'receiver_phone' => 'tb_op_order.ADDRESS_USER_PHONE',
            'pay_the_serial_number' => 'tb_op_order.PAY_TRANSACTION_ID',
            'receiver_tel' => 'tb_op_order.RECEIVER_TEL',
            'consignee_name' => 'tb_op_order.ADDRESS_USER_NAME',
            'tracking_number' => 'tb_ms_ord_package.TRACKING_NUMBER',
            'sku_title' => 'tb_ms_guds.GUDS_NM',
            'sku_number' => 'tb_op_order_guds.B5C_SKU_ID',
            'zip_code' => 'tb_op_order.ADDRESS_USER_POST_CODE',
            'receiver_email' => 'tb_op_order.USER_EMAIL',
            'pay_method' => 'tb_op_order.PAY_METHOD'
        ]
    ];

    /**
     * 检查导出数量
     * @param $data
     * @param $type
     * @param null $where_insert
     * @return mixed
     */
    public function checkExportOrder($data, $type, $where_insert = null){
        $model = new SlaveModel();
        list($where, $where_temp) = $this->buildWhere($data['wheres']);
        if (!empty($data['ids'])) {
            $order_map = $model->table('tb_op_order')->where(['ID' => ['in', $data['ids']]])->getField('ID, CHILD_ORDER_ID');
            $child_order_ids = [];
            $data['ids'] = array_filter($data['ids'], function($id) use ($order_map, &$child_order_ids) {
                if (empty($order_map[$id])) {
                    return true;
                }
                $child_order_ids = array_merge($child_order_ids, explode(',',$order_map[$id]));
                return false;
            });
            if (!empty($child_order_ids)) {
                $child_ids   = $model->table('tb_op_order')->where(['ORDER_ID' => ['IN', $child_order_ids]])->getField('ID', true);
                $data['ids'] = array_merge($data['ids'], $child_ids);
            }
            $where['tb_op_order.ID'] = array('in', $data['ids']);
        }
        $model->table('tb_op_order')->field('
               count(*)
             ')->where($where)
            ->order($data['sort']);
        $model
            ->join('left join tb_ms_ord on tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD ')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID AND tb_op_order_guds.PLAT_CD = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD  = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_cmn_cd as cd1 on cd1.CD  = tb_ms_store.SALE_TEAM_CD')
            ->join('left join tb_ms_cmn_cd as cd2 on cd2.CD  = tb_ms_store.company_cd')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id  = tb_op_order.ADDRESS_USER_COUNTRY_ID')
            ->join('left join ' . PMS_DATABASE . '.product_sku on tb_op_order_guds.B5C_SKU_ID = product_sku.sku_id ')
            ->join('left join ' . PMS_DATABASE . '.product on product.spu_id = product_sku.spu_id')
            ->join('left join ' . PMS_DATABASE . '.product_brand on product_brand.brand_id = product.brand_id')
            ->join('left join tb_op_order_extend on tb_op_order_extend.order_id = tb_op_order.ORDER_ID AND tb_op_order_extend.plat_cd = tb_op_order.PLAT_CD');

        if ($where_insert) {
            $model->where($where_insert, null, true);
        }
        if (!empty($where_temp)) {
            $model->where($where_temp, null, true);
        }
        switch ($type) {
            case 'patch':
                $model->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001820100\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true);
                break;
            case 'do_patch':
                $model->where(' tb_ms_store.SEND_ORD_TYPE = 1  AND ( tb_op_order.SEND_ORD_STATUS = \'N001820100\' ) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND (  (tb_op_order.CHILD_ORDER_ID IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550400\',\'N000550500\',\'N000550600\',\'N000550800\') ) ', null, true);
                break;
            case 'pending':
                $model->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001821101\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS = \'N001821101\') AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true);
                break;
            default:
                $model->where(' ( tb_op_order.PARENT_ORDER_ID is not NULL  or (tb_op_order.PARENT_ORDER_ID is NULL and tb_op_order.CHILD_ORDER_ID is NULL)) ', null, true);
        }
        $modelData = $model->select();
        $query =$model->_sql();
        $field = '
            ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID) AS ORDER_ID,
            tb_op_order.PLAT_CD,
            tb_ms_cmn_cd.CD_VAL AS PLAT_CD_NM,
            cd1.CD_VAL AS SALE_TEAM_CD_NM,
            tb_op_order.logistic_cd,
            tb_op_order.ORDER_TIME,
            tb_op_order.ORDER_PAY_TIME,
            tb_op_order.BWC_ORDER_STATUS,
            tb_op_order.SHIPPING_TIME,
            tb_op_order_guds.SKU_ID,
            tb_ms_store.STORE_NAME,
            tb_ms_store.MERCHANT_ID,
            tb_op_order.SHIPPING_DELIVERY_COMPANY,
            tb_ms_ord_package.TRACKING_NUMBER,
            tb_op_order_guds.ITEM_NAME,
            tb_op_order_guds.B5C_SKU_ID,
            tb_op_order_guds.SKU_MESSAGE,
            tb_op_order.ADDRESS_USER_NAME,
            tb_op_order.ADDRESS_USER_PHONE,
            tb_op_order.ADDRESS_USER_ADDRESS1,
            tb_op_order.WAREHOUSE,
            tb_op_order.PAY_TOTAL_PRICE,
            tb_op_order.PAY_TOTAL_PRICE_DOLLAR,
            tb_op_order.logistic_model_id,
            tb_op_order.RECEIVER_TEL,
            tb_op_order.ADDRESS_USER_ADDRESS_MSG,
            tb_op_order.ADDRESS_USER_ADDRESS3,
            tb_op_order.ADDRESS_USER_POST_CODE,
            tb_op_order.BUYER_MOBILE,
            tb_op_order.BUYER_TEL,
            tb_op_order_guds.ITEM_COUNT,
            tb_op_order_guds.ITEM_PRICE,
            tb_op_order.PAY_ITEM_PRICE,
            ifnull(tb_op_order.PAY_SHIPING_PRICE,"-") as PAY_SHIPING_PRICE,
            ifnull(tb_op_order.PAY_VOUCHER_AMOUNT,"-") as PAY_VOUCHER_AMOUNT,
            tb_op_order.PAY_CURRENCY,
            tb_op_order.SHIPPING_MSG,
            tb_op_order.REMARK_MSG,
            tb_op_order.ADDRESS_USER_COUNTRY_ID,
            tb_op_order.ADDRESS_USER_PROVINCES,
            tb_ms_user_area.zh_name,
            tb_ms_user_area.two_char,
            tb_op_order.ADDRESS_USER_CITY,
            tb_op_order.ADDRESS_USER_REGION,
            tb_op_order.ADDRESS_USER_ADDRESS1,
            tb_op_order.BUYER_USER_IDENTITY_CARD,
            tb_op_order.ADDRESS_USER_IDENTITY_CARD,
            tb_op_order.ORDER_NO,
            if(tb_op_order.PARENT_ORDER_ID is null or tb_op_order.PARENT_ORDER_ID="", "", tb_op_order.ORDER_ID) as CHILD_ORDER_ID,
            tb_op_order.FILE_NAME,
            tb_op_order.CREATE_USER,
            tb_op_order.B5C_ORDER_NO,
            tb_op_order_guds.cost_usd_price,
            tb_op_order_guds.sku_purchasing_company,
            ifnull(tb_op_order.TARIFF, "-") as TARIFF,
            ifnull(tb_op_order.PAY_INSTALMENT_SERVICE_AMOUNT, "-") as PAY_INSTALMENT_SERVICE_AMOUNT,
            ifnull(tb_op_order.PAY_WRAPPER_AMOUNT, "-") as PAY_WRAPPER_AMOUNT,
            ifnull(tb_op_order.PAY_SETTLE_PRICE, "-") as PAY_SETTLE_PRICE,
            ifnull(tb_op_order.PAY_SETTLE_PRICE_DOLLAR, "-") as PAY_SETTLE_PRICE_DOLLAR,
            ifnull(tb_op_order.SHIPPING_TAX, "-") as SHIPPING_TAX,
            ifnull(tb_op_order.PROMOTION_DISCOUNT_TAX, "-") as PROMOTION_DISCOUNT_TAX,
            ifnull(tb_op_order.SHIPPING_DISCOUNT_TAX, "-") as SHIPPING_DISCOUNT_TAX,
            ifnull(tb_op_order.GIFT_WRAP_TAX, "-") as GIFT_WRAP_TAX,
            tb_op_order.USER_EMAIL,
            tb_op_order.PAY_ACCOUNT_ID,
            tb_op_order.PAY_METHOD,
            tb_op_order.PAY_TRANSACTION_ID,
            tb_op_order.SEND_ORD_TIME,
            tb_op_order.is_remote_area,
            tb_ms_ord.sendout_time,
            tb_op_order_extend.refund_comm_amount,
            tb_op_order_extend.refund_total_amount,
            tb_op_order_extend.refund_reason,
            tb_op_order_extend.syn_at,
            IFNULL(tb_ms_ord.WHOLE_STATUS_CD,tb_op_order.SEND_ORD_STATUS) AS WHOLE_STATUS_CD,
            tb_op_order.PACKING_NO,
            cd2.CD_VAL AS company_name,
            tb_op_order_extend.reissue_status_cd,
            tb_op_order_extend.return_status_cd,
            tb_op_order_extend.refund_status_cd,
            BUYER_USER_IDENTITY_NAME,
            tb_op_order_extend.other_discounted_price,
            tb_op_order_extend.platform_discount_price,
            tb_op_order_extend.seller_discount_price,
            tb_op_order_extend.buyer_user_id,
            tb_op_order_extend.charge_off_status,
            product_sku.upc_id,
            product_sku.upc_more,
            product_brand.is_oem_brand';
        $query = str_replace('count(*)',$field,$query);
        $total = 0;
        if ($modelData){
            $total = $modelData[0]['count(*)'];
        }
        return array($total,$query);
    }
    /**
     * @param      $data
     * @param      $type
     * @param null $where_insert
     *
     * @return array
     */
    public function exportOrder($data, $type, $where_insert = null, $num_offset = 0, $num_length = 6000)
    {
        //导出Excel
        $model = new SlaveModel();
        list($where, $where_temp) = $this->buildWhere($data['wheres']);
        if (!empty($data['ids'])) {
            $order_map = $model->table('tb_op_order')->where(['ID' => ['in', $data['ids']]])->getField('ID, CHILD_ORDER_ID');
            $child_order_ids = [];
            $data['ids'] = array_filter($data['ids'], function($id) use ($order_map, &$child_order_ids) {
                if (empty($order_map[$id])) {
                    return true;
                }
                $child_order_ids = array_merge($child_order_ids, explode(',',$order_map[$id]));
                return false;
            });
            if (!empty($child_order_ids)) {
                $child_ids   = $model->table('tb_op_order')->where(['ORDER_ID' => ['IN', $child_order_ids]])->getField('ID', true);
                $data['ids'] = array_merge($data['ids'], $child_ids);
            }
            $where['tb_op_order.ID'] = array('in', $data['ids']);
        }
        $model->table('tb_op_order')
            ->field('
            ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID) AS ORDER_ID,
            tb_op_order.PLAT_CD,
            tb_ms_cmn_cd.CD_VAL AS PLAT_CD_NM,
            cd1.CD_VAL AS SALE_TEAM_CD_NM,
            tb_op_order.logistic_cd,
            tb_op_order.ORDER_TIME,
            tb_op_order.ORDER_PAY_TIME,
            tb_op_order.BWC_ORDER_STATUS,
            tb_op_order.SHIPPING_TIME,
            tb_op_order_guds.SKU_ID,
            tb_ms_store.STORE_NAME,
            tb_ms_store.MERCHANT_ID,
            tb_ms_store.sell_small_team_cd,
            tb_op_order.SHIPPING_DELIVERY_COMPANY,
            tb_ms_ord_package.TRACKING_NUMBER,
            tb_op_order_guds.ITEM_NAME,
            tb_op_order_guds.B5C_SKU_ID,
            tb_op_order_guds.SKU_MESSAGE,
            tb_op_order.ADDRESS_USER_NAME,
            tb_op_order.ADDRESS_USER_PHONE,
            tb_op_order.ADDRESS_USER_ADDRESS1,
            tb_op_order.WAREHOUSE,
            tb_op_order.PAY_TOTAL_PRICE,
            tb_op_order.PAY_TOTAL_PRICE_DOLLAR,
            tb_op_order.logistic_model_id,
            tb_op_order.RECEIVER_TEL,
            tb_op_order.ADDRESS_USER_ADDRESS_MSG,
            tb_op_order.ADDRESS_USER_ADDRESS3,
            tb_op_order.ADDRESS_USER_POST_CODE,
            tb_op_order.BUYER_MOBILE,
            tb_op_order.BUYER_TEL,
            tb_op_order_guds.ITEM_COUNT,
            tb_op_order_guds.ITEM_PRICE,
            tb_op_order.PAY_ITEM_PRICE,
            ifnull(tb_op_order.PAY_SHIPING_PRICE,"-") as PAY_SHIPING_PRICE,
            ifnull(tb_op_order.PAY_VOUCHER_AMOUNT,"-") as PAY_VOUCHER_AMOUNT,
            tb_op_order.PAY_CURRENCY,
            tb_op_order.SHIPPING_MSG,
            tb_op_order.REMARK_MSG,
            tb_op_order.ADDRESS_USER_COUNTRY_ID,
            tb_op_order.ADDRESS_USER_PROVINCES,
            tb_ms_user_area.zh_name,
            tb_ms_user_area.two_char,
            tb_op_order.ADDRESS_USER_CITY,
            tb_op_order.ADDRESS_USER_REGION,
            tb_op_order.ADDRESS_USER_ADDRESS1,
            tb_op_order.ADDRESS_USER_ADDRESS2,
            tb_op_order.BUYER_USER_IDENTITY_CARD,
            tb_op_order.ADDRESS_USER_IDENTITY_CARD,
            tb_op_order.ORDER_NO,
            if(tb_op_order.PARENT_ORDER_ID is null or tb_op_order.PARENT_ORDER_ID="", "", tb_op_order.ORDER_ID) as CHILD_ORDER_ID,
            tb_op_order.FILE_NAME,
            tb_op_order.CREATE_USER,
            tb_op_order.B5C_ORDER_NO,
            tb_op_order_guds.cost_usd_price,
            tb_op_order_guds.sku_purchasing_company,
            ifnull(tb_op_order.TARIFF, "-") as TARIFF,
            ifnull(tb_op_order.PAY_INSTALMENT_SERVICE_AMOUNT, "-") as PAY_INSTALMENT_SERVICE_AMOUNT,
            ifnull(tb_op_order.PAY_WRAPPER_AMOUNT, "-") as PAY_WRAPPER_AMOUNT,
            ifnull(tb_op_order.PAY_SETTLE_PRICE, "-") as PAY_SETTLE_PRICE,
            ifnull(tb_op_order.PAY_SETTLE_PRICE_DOLLAR, "-") as PAY_SETTLE_PRICE_DOLLAR,
            ifnull(tb_op_order.SHIPPING_TAX, "-") as SHIPPING_TAX,
            ifnull(tb_op_order.PROMOTION_DISCOUNT_TAX, "-") as PROMOTION_DISCOUNT_TAX,
            ifnull(tb_op_order.SHIPPING_DISCOUNT_TAX, "-") as SHIPPING_DISCOUNT_TAX,
            ifnull(tb_op_order.GIFT_WRAP_TAX, "-") as GIFT_WRAP_TAX,
            tb_op_order.USER_EMAIL,
            tb_op_order.PAY_ACCOUNT_ID,
            tb_op_order.PAY_METHOD,
            tb_op_order.PAY_TRANSACTION_ID,
            tb_op_order.SEND_ORD_TIME,
            tb_op_order.is_remote_area,
            tb_ms_ord.sendout_time,
            tb_op_order_extend.refund_comm_amount,
            tb_op_order_extend.refund_total_amount,
            tb_op_order_extend.refund_reason,
            tb_op_order_extend.syn_at,
            IFNULL(tb_ms_ord.WHOLE_STATUS_CD,tb_op_order.SEND_ORD_STATUS) AS WHOLE_STATUS_CD,
            tb_op_order.PACKING_NO,
            cd2.CD_VAL AS company_name,
            tb_op_order_extend.reissue_status_cd,
            tb_op_order_extend.return_status_cd,
            tb_op_order_extend.refund_status_cd,
            BUYER_USER_IDENTITY_NAME,
            tb_op_order_extend.other_discounted_price,
            tb_op_order_extend.platform_discount_price,
            tb_op_order_extend.seller_discount_price,
            tb_op_order_extend.sub_addr_recipient_name,
            tb_op_order_extend.sub_addr,
            tb_op_order_extend.kr_customs_code,
            tb_op_order_extend.doorplate,
            tb_op_order_extend.buyer_user_id,
            tb_op_order_extend.charge_off_status,
            product_sku.upc_id,
            product_sku.upc_more,
            product_brand.is_oem_brand
        ')->where($where)
            ->order($data['sort']);
        $model->table('tb_op_order')
            ->join('left join tb_ms_ord on tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD ')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID AND tb_op_order_guds.PLAT_CD = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD  = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_cmn_cd as cd1 on cd1.CD  = tb_ms_store.SALE_TEAM_CD')
            ->join('left join tb_ms_cmn_cd as cd2 on cd2.CD  = tb_ms_store.company_cd')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id  = tb_op_order.ADDRESS_USER_COUNTRY_ID')
            ->join('left join ' . PMS_DATABASE . '.product_sku on tb_op_order_guds.B5C_SKU_ID = product_sku.sku_id ')
            ->join('left join ' . PMS_DATABASE . '.product on product.spu_id = product_sku.spu_id')
            ->join('left join ' . PMS_DATABASE . '.product_brand on product_brand.brand_id = product.brand_id')
            ->join('left join tb_op_order_extend on tb_op_order_extend.order_id = tb_op_order.ORDER_ID AND tb_op_order_extend.plat_cd = tb_op_order.PLAT_CD');

        if ($where_insert) {
            $model->where($where_insert, null, true);
        }
        if (!empty($where_temp)) {
            $model->where($where_temp, null, true);
        }
        switch ($type) {
            case 'patch':
                $model->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001820100\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\',\'N000551004\') ) AND ( tb_op_order.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )
                 AND ((tb_op_order_extend.refund_status_cd = \'N002800014\' OR tb_op_order_extend.refund_status_cd = \'\' OR tb_op_order_extend.refund_status_cd is null)
		OR ((tb_ms_ord_package.TRACKING_NUMBER != \'\' OR tb_ms_ord_package.TRACKING_NUMBER is not null)
		AND (tb_op_order_extend.refund_status_cd in (\'N002800009\', \'N002800010\', \'N002800011\', \'N002800012\', \'N002800013\'))))
                 ', null, true);
                break;
            case 'do_patch':
                $model->where(' tb_ms_store.SEND_ORD_TYPE = 1  AND ( tb_op_order.SEND_ORD_STATUS = \'N001820100\' ) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND (  (tb_op_order.CHILD_ORDER_ID IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550400\',\'N000550500\',\'N000550600\',\'N000550800\') ) ', null, true);
                break;
            case 'pending':
                $model->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001821101\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS = \'N001821101\') AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true);
                break;
            case 'tracking_no':
                $model->where(' (tb_op_order.send_ord_status = \'N001820200\' and tb_ms_ord.WHOLE_STATUS_CD = \'N001821102\') ', null, true);
                break;
            default:
                //$model->where(' tb_op_order.PARENT_ORDER_ID IS NULL ', null, true);
                $model->where(' ( tb_op_order.PARENT_ORDER_ID is not NULL  or (tb_op_order.PARENT_ORDER_ID is NULL and tb_op_order.CHILD_ORDER_ID is NULL)) ', null, true);
        }

        $model->limit($num_offset, $num_length);
        $modelData = $model->select();
       
        $xlsData = $this->dataFormat($modelData);
        $xlsData = SkuModel::getInfo($xlsData, 'B5C_SKU_ID', ['spu_name', 'attributes','brand_name'], ['spu_name' => 'GUDS_NM', 'attributes' => 'GUDS_OPT_NM','brand_name'=>'brand_name']);
        $xls_nums = count($xlsData);
        if ($xls_nums > $num_length) {
            $xlsData = array_slice($xlsData, 0, $num_length);
        }
        $is_excel = L('Excel 导入');
        $is_pull = L('自动拉单');
        $xlsData = CodeModel::autoCodeTwoVal($xlsData,['sell_small_team_cd']);
      
        foreach ($xlsData as &$v) {
            $v['SOURCE'] = $v['FILE_NAME'] ? $is_excel : $is_pull;
            $v['ADDRESS_USER_ADDRESS1'] = strip_tags($v['ADDRESS_USER_ADDRESS1']);
            $v['SEND_ORD_TIME'] = (strtotime($v['SEND_ORD_TIME']) <= 0) ?  ""  :  $v['SEND_ORD_TIME'];
            $v['is_remote_area'] = $v['is_remote_area'] ? '是' : '否';
            $v['syn_at'] = (strtotime($v['syn_at']) <= 0) ?  ""  :  $v['syn_at'];
            $v['refund_comm_amount'] = !empty($v['refund_comm_amount']) ? abs($v['refund_comm_amount']) : '';
            $v['refund_total_amount'] = !empty($v['refund_total_amount']) ? abs($v['refund_total_amount']) : '';
            $after_status_cds[] = $v['reissue_status_cd'];
            $after_status_cds[] = $v['return_status_cd'];
            $after_status_cds[] = $v['refund_status_cd'];
            $v['other_discounted_price'] = number_format( $v['other_discounted_price'] ,2);
            $v['platform_discount_price'] = number_format( $v['platform_discount_price'] ,2);
            $v['seller_discount_price'] = number_format( $v['seller_discount_price'] ,2);
            $v['ADDRESS_USER_REGION'] = OrderModel::needShowRegion($v['PLAT_CD']) ? $v['ADDRESS_USER_REGION'] : '';
            $v['charge_off_status'] = $v['charge_off_status'] == 1 ? '已冲销' : '未冲销';
            $v['is_odm'] = isset($v['is_oem_brand']) ? ($v['is_oem_brand'] == 1 ? 'ODM' : '非ODM') : (!empty($v['B5C_SKU_ID']) ? '其他' : '');
            $v['upc_id'] = !empty($v['upc_more']) ? $v['upc_id'] . ',' . $v['upc_more'] : $v['upc_id'];
            $v['GUDS_NM'] = isset($v['GUDS_NM']) && !empty($v['GUDS_NM']) ? $v['brand_name'].'|'.$v['GUDS_NM'] : "";
        }
        $after_status_cds = array_filter(array_unique($after_status_cds));
        $code_map = M('cmn_cd','tb_ms_')->where(['CD'=>['IN',$after_status_cds]])->getField('CD,CD_VAL');
        foreach ($xlsData as &$item) {
            $item['after_sale_type'] = implode(',', array_filter([
                $code_map[$item['reissue_status_cd']],
                $code_map[$item['return_status_cd']],
                $code_map[$item['refund_status_cd']]
            ]));
        }
        $xlsName = "订单";
        $xlsCell = array(
            array('PLAT_CD_NM', '站点名称'),
            array('STORE_NAME', '店铺名称'),
            array('MERCHANT_ID', '店铺别名'),
            array('SALE_TEAM_CD_NM', '销售团队'),
            array('sell_small_team_cd_val', '销售小团队'),
            array('company_name', '店铺所属公司'),
            array('SOURCE', '订单创建类型'),
            array('CREATE_USER', '订单创建人'),
            array('ORDER_ID', '第三方订单ID'),
            array('ORDER_NO', '第三方订单号'),
            array('CHILD_ORDER_ID', '子订单号'),
            array('B5C_ORDER_NO', 'ERP订单号'),
            array('BWC_ORDER_STATUS', '订单状态'),
            array('WHOLE_STATUS_CD', '派单状态'),
            array('B5C_SKU_ID', 'SKU ID'),
            array('SKU_ID', '第三方SKU ID'),
            array('GUDS_NM', 'SKU 名称'),
            array('GUDS_OPT_NM', 'SKU 属性'),
            array('sku_weight', '商品重量'),
            array('sku_size', '规格'),
            array('PAY_CURRENCY', '币种'),
            array('cost_usd_price', '商品成本价(USD)'),
            array('sku_purchasing_company', '商品采购公司'),
            array('ITEM_PRICE', '订单商品销售单价'),
            array('ITEM_COUNT', '商品数量'),
            array('PAY_ITEM_PRICE', '订单商品总价'),
            array('other_discounted_price', '其他优惠'),
            array('seller_discount_price', '商家优惠'),
            array('platform_discount_price', '平台优惠'),
            array('PAY_VOUCHER_AMOUNT', '订单优惠金额'),
            array('PAY_SHIPING_PRICE', '订单运费'),
            array('PAY_WRAPPER_AMOUNT', '订单包装费'),
            array('PAY_INSTALMENT_SERVICE_AMOUNT', '订单分期总手续费'),
            array('TARIFF', '订单商品总税费'),
            array('PROMOTION_DISCOUNT_TAX', '订单优惠总税费'),
            array('SHIPPING_DISCOUNT_TAX', '订单运费折扣税费'),
            array('GIFT_WRAP_TAX', '订单包装费税费'),
            array('PAY_TOTAL_PRICE', '订单支付总价'),
            array('PAY_TOTAL_PRICE_DOLLAR', '订单支付总价（USD）'),
            array('PAY_SETTLE_PRICE', '结算费'),
            array('PAY_SETTLE_PRICE_DOLLAR', '结算费（USD）'),
            array('ORDER_TIME', '下单时间'),
            array('ORDER_PAY_TIME', '付款时间'),
            array('SHIPPING_TIME', '发货时间'),
            array('SEND_ORD_TIME', '派单时间'),
            array('sendout_time', '出库时间'),
            array('ADDRESS_USER_NAME', '收货人姓名'),

            array('ADDRESS_USER_IDENTITY_CARD', '收货人身份证号码'),
            array('BUYER_USER_IDENTITY_NAME', '身份证姓名'),
            array('ADDRESS_USER_PHONE', '收货人手机'),
            array('RECEIVER_TEL', '收货人电话'),
            array('USER_EMAIL', '收货人邮箱'),
            array('buyer_user_id', '买家ID'),
            array('zh_name', '国家'),
            array('ADDRESS_USER_PROVINCES', '省'),
            array('ADDRESS_USER_CITY', '市'),
            array('ADDRESS_USER_REGION', '区（县）'),
            array('ADDRESS_USER_ADDRESS1', '地址一'),
            array('ADDRESS_USER_ADDRESS2', '地址二'),
            array('doorplate', '门牌号'),
            array('sub_addr_recipient_name', '次要收货人姓名'),
            array('sub_addr', '次要收货人地址'),
            array('kr_customs_code', '通行符'),
            array('ADDRESS_USER_POST_CODE', '邮编'),
            array('WAREHOUSE', '仓库'),
            array('logistic_cd', '物流公司'),
            array('logistic_model_id', '物流方式'),
            array('TRACKING_NUMBER', '物流单号'),
            array('PACKING_NO', '包裹号'),
            array('SHIPPING_MSG', '用户备注'),
            array('REMARK_MSG', '运营备注'),
            array('PAY_ACCOUNT_ID', '账户名称'),
            array('PAY_METHOD', '支付类型'),
            array('PAY_TRANSACTION_ID', '交易号'),
            array('is_remote_area', '是否偏远地区'),
            array('refund_comm_amount', '退款发起商品金额'),
            array('refund_total_amount', '退款发起总金额'),
            array('refund_reason', '退款原因'),
            array('syn_at', '同步时间'),
            array('after_sale_type', '售后状态'),
            array('charge_off_status', '收入成本冲销状态'),
            array('two_char', '国家（二字码）'),
            array('ITEM_NAME', '平台名称'),
            array('is_odm', '品牌类型'),
            array('upc_id', '条形码'),

            /*array('CHILD_ORDER_ID', '子单号'),
            array('GUDS_NM', '商品标题'),
            array('SKU_MESSAGE', '商品SKU属性'),
            array('ADDRESS_USER_ADDRESS1', '收货人地址'),
            array('BWC_ORDER_STATUS', '订单状态'),
            array('WHOLE_STATUS_CD', '派单状态'),
            array('ADDRESS_USER_PHONE', '买家手机'),
            array('BUYER_TEL', '买家固话'),*/

        );
        $to_html_special_arr = [
            'ADDRESS_USER_NAME',
            'ADDRESS_USER_PHONE',
            'RECEIVER_TEL',
            'USER_EMAIL',
            'zh_name',
            'ADDRESS_USER_PROVINCES',
            'ADDRESS_USER_CITY',
            'ADDRESS_USER_REGION',
            'ADDRESS_USER_ADDRESS1',
            'ADDRESS_USER_POST_CODE',
            'SHIPPING_MSG',
        ];
        $xlsData = array_map(function ($value) use ($to_html_special_arr) {
            foreach ($to_html_special_arr as $v) {
                $value[$v] = htmlspecialchars_decode($value[$v], ENT_QUOTES);
            }
            if (OrderModel::isB2c($value['PLAT_CD'])) {
//                $value['ADDRESS_USER_IDENTITY_CARD'] = '***';
                $carId = (new OrderEsModel())->decodeCardId($value['ADDRESS_USER_IDENTITY_CARD'], $value['PLAT_CD'], false);
                $value['ADDRESS_USER_IDENTITY_CARD'] = $carId;
            }
            return $value;
        }, $xlsData);
        $sku_arr = array_column($xlsData,'B5C_SKU_ID');
        $sku_infos = SkuModel::getSkusInfo($sku_arr, ['product_sku']);
        if ($sku_infos) {
            foreach ($xlsData as &$datum) {
                $datum['sku_weight'] = $sku_infos['product_sku'][$datum['B5C_SKU_ID']]['sku_weight'];
                $datum['sku_size'] = $sku_infos['product_sku'][$datum['B5C_SKU_ID']]['sku_length'] . '*' . $sku_infos['product_sku'][$datum['B5C_SKU_ID']]['sku_width'] . '*' . $sku_infos['product_sku'][$datum['B5C_SKU_ID']]['sku_height'];
            }
        }
        return array($xlsName, $xlsCell, $xlsData);
    }

    public function buildWhere($where_arr)
    {
        $twoDimension = ['search_time_type', 'search_condition', 'search_value'];
        $mapping_arr_key = array_keys($this->mapping_where_arr);
        $status_arr = '';
        $where_insert = '';
        foreach ($where_arr as $key => $value) {
            if (!empty($key) && !empty($value)) {
                if (in_array($key, $mapping_arr_key) && !in_array($key, $twoDimension)) {
                    $where[$this->mapping_where_arr[$key]] = array('IN', (array)$value);
                }
                if ('sales_team' == $key) {
                    $where_insert_temp = '';
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    foreach ($value as $temp_value){
                        if (!empty($where_insert_temp)) $where_insert_temp .= 'OR';
                        $where_insert_temp .= "  tb_ms_store.SALE_TEAM_CD LIKE '%{$temp_value}%' ";
                    }
                    $where_insert .= " ( {$where_insert_temp} ) ";
                }
                if ('sell_small_team' == $key) {
                    $where_insert_temp = '';
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    foreach ($value as $temp_value){
                        if (!empty($where_insert_temp)) $where_insert_temp .= 'OR';
                        $where_insert_temp .= "  tb_ms_store.sell_small_team_cd LIKE '%{$temp_value}%' ";
                    }
                    $where_insert .= " ( {$where_insert_temp} ) ";
                }

                if ('dispatch_status' == $key) {
                    $status_arr = implode("','", $value);
                }
                if ('search_condition' == $key && !empty($where_arr['search_value'])) {
                    if (strpos($where_arr['search_value'], ',')) {
                        $where[$this->mapping_where_arr[$key][$value]] = array('IN', OrderEsModel::str2arrSearch($where_arr['search_value']));
                    } elseif ($value == 'zip_code') {
                        $where[$this->mapping_where_arr[$key][$value]] = trim($where_arr['search_value']);
                    } else {
                        $where[$this->mapping_where_arr[$key][$value]] = array('LIKE', '%' . trim($where_arr['search_value']) . '%');
                        if ($value == 'sku_title') {
                            $where[$this->mapping_where_arr['search_condition']['sku_number']] = array('IN', SkuModel::titleToSku($where_arr['search_value']));
                            unset($where['tb_ms_guds.GUDS_NM']);
                        }
                    }
                }
                if ('search_time_type' == $key && ($where_arr['search_time_left'] || $where_arr['search_time_right'])) {
                    $where[$this->mapping_where_arr[$key][$value]] = array(
                        array('egt', trim($where_arr['search_time_left'])),
                        array('elt', trim($where_arr['search_time_right'])),
                    );
                }
                if ('sku_is_null' == $key && $value == 1) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $where_insert .= " (  tb_op_order_guds.B5C_SKU_ID = '' OR tb_op_order_guds.B5C_SKU_ID IS NULL ) ";
                }

                if ('addressee_msg_null' == $key && $value == 1) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $where_insert .= " (  tb_op_order.ADDRESS_USER_COUNTRY_ID = '' OR tb_op_order.ADDRESS_USER_COUNTRY_ID IS NULL  OR  tb_op_order.ADDRESS_USER_ADDRESS1 = '' OR  tb_op_order.ADDRESS_USER_ADDRESS1 IS NULL) ";
                }

                if ('recommend_type' == $key) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $temp_string = '';
                    foreach ($value as $recommend_type_value) {
                        if ('recommend_system' == $recommend_type_value) {
                            $temp_string .= "OR (tb_op_order.FIND_ORDER_JSON != '' AND tb_op_order.has_default_warehouse = 0) ";
                        }
                        if ('recommend_user' == $recommend_type_value) {
                            $temp_string .= "OR tb_op_order.has_default_warehouse = 1 ";
                        }
                        if ('operation_assignment' == $recommend_type_value) {
                            $temp_string .= "OR tb_op_order.has_default_warehouse = 2 ";
                        }
                    }
                    $where_insert .= trim($temp_string, 'OR');
                }
                if ('patch_err' == $key) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $temp_string = '';
                    foreach ($value as $patch_err_value) {
                        if ('un_inventory' == $patch_err_value) {
                            $temp_string .= "OR tb_op_order.SEND_ORD_TYPE_CD = 'N002030700' ";
                        }
                        if ('un_warehouse_logistics' == $patch_err_value) {
                            $temp_string .= "OR (  tb_op_order.warehouse = ''  OR  tb_op_order.logistic_model_id = '' OR  tb_op_order.logistic_model_id IS NULL) ";
                        }
                        if ('un_single_number' == $patch_err_value) {
                            $temp_string .= "OR (tb_op_order.SURFACE_WAY_GET_CD = 'N002010100' AND tb_ms_ord_package.TRACKING_NUMBER IS NULL) ";

                        }
                    }
                    $where_insert .= trim($temp_string, 'OR');
                }
            }
        }
        if (!empty($status_arr)) {
            $status_arr = trim($status_arr, ',');
            $where_insert .= " ((tb_op_order.SEND_ORD_STATUS IN ('$status_arr') AND ( tb_ms_ord.WHOLE_STATUS_CD IS NULL  OR tb_ms_ord.WHOLE_STATUS_CD = '') ) OR (tb_ms_ord.WHOLE_STATUS_CD IN ('$status_arr') AND ( tb_op_order.ORDER_ID IS NOT NULL OR tb_op_order.ORDER_ID = '' ))) ";
        }
        if (!empty($where_arr['remark_have']) && $where_arr['remark_have'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.REMARK_MSG != ''  OR  tb_op_order.SHIPPING_MSG != '') ";
        }
        if (!empty($where_arr['hasDefaultWarehouse']) && $where_arr['hasDefaultWarehouse'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.has_default_warehouse = 1) ";
        }
        if (!empty($where_arr['thirdDeliverStatus']) && $where_arr['thirdDeliverStatus'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.THIRD_DELIVER_STATUS = 1) ";
        }
        if (!empty($where_arr['after_sale_type'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (tb_op_order.after_sale_type = {$where_arr['after_sale_type']}) ";
        }
        if (isset($where_arr['is_apply_after_sale']) && "" !== $where_arr['is_apply_after_sale']) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            if ($where_arr['is_apply_after_sale']) {
                $where_insert .= " (tb_op_order.is_apply_after_sale = {$where_arr['is_apply_after_sale']}) ";
            } else {
                $where_insert .= " (tb_op_order.is_apply_after_sale IS NULL) ";
            }
        }
        if (isset($where_arr['third_deliver_status']) && "" !== $where_arr['third_deliver_status']) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (tb_op_order.THIRD_DELIVER_STATUS = {$where_arr['third_deliver_status']}) ";
        }
        if (!empty($where_arr['after_sale_status'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $after_sale_status = (new OmsAfterSaleService())->getStatusMap($where_arr['after_sale_status']);
            $refund_where_str = $reissue_where_str = $return_where_str = " (";
            foreach ($after_sale_status as $item) {
                $refund_where_str .= "tb_op_order_extend.refund_status_cd = '{$item}' OR ";
            }
            $refund_where_str = trim($refund_where_str, 'OR '). ')';

            foreach ($after_sale_status as $item) {
                $reissue_where_str .= "tb_op_order_extend.reissue_status_cd = '{$item}' OR ";
            }
            $reissue_where_str = trim($reissue_where_str, 'OR '). ')';

            foreach ($after_sale_status as $item) {
                $return_where_str .= "tb_op_order_extend.return_status_cd = '{$item}' OR ";
            }
            $return_where_str = trim($return_where_str, 'OR '). ')';
            $com_str = ' ('.$refund_where_str. ' OR '. $return_where_str. ' OR '. $reissue_where_str. ') ';
            $where_insert .= $com_str;
        }
        if (!empty($where_arr['address_valid_status'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $address_valid_status = "('".implode("','",$where_arr['address_valid_status']). "')";
            $where_insert .= " ((tb_op_order_extend.address_valid_status IN {$address_valid_status}) ";
        }
        if (!empty($where_arr['is_odm'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $is_odm = "('".implode("','",$where_arr['is_odm']). "')";
            $where_insert .= " ((tb_op_order_extend.is_odm IN {$is_odm}) ";
        }

        //仓库参数组装 其他=NON:brandType索引为null
        if (isset($where_arr['warehouse']) && !empty($where_arr['warehouse'])) {
            //存在其他选项情况将仓库转换为 query查询语句
            if (in_array('NON', $where_arr['warehouse'])) {
                //有多个选项
                if (!empty($where_insert)) $where_insert .= 'AND';
                if (count($where_arr['warehouse']) > 1) {
                    unset($where_arr['warehouse'][array_search('NON', $where_arr['warehouse'])]);
                    $warehouse = "('".implode("','",$where_arr['warehouse']). "')";
                    $where_insert .= " ((tb_op_order.WAREHOUSE IN {$warehouse}) OR (tb_op_order.WAREHOUSE is NULL)) ";
                    //移除构造器查询
                } else {
                    $where_insert .= " (tb_op_order.WAREHOUSE is NULL) ";
                }
                unset($where['tb_op_order.WAREHOUSE']);
            }
        }
        return [$where, $where_insert];
    }

    /**
     * @param $xlsData
     *
     * @return mixed
     */
    private function dataFormat($xlsData)
    {
        list($logistic_arrary, $warehouse_arrary, $logistic_arrary_key, $warehouse_arrary_key, $cd_key) = $this->cdDataGet();
        $logistic_model_key_arr = array_unique(array_column($xlsData, 'logistic_model_id'));
        if ($logistic_model_key_arr) {
            $where['ID'] = array('in', $logistic_model_key_arr);
            $where['IS_DELETE'] = 0;
            $where['IS_ENABLE'] = 1;
            $logistic_model_arr = M()->table('tb_ms_logistics_mode')
                ->field('ID,LOGISTICS_MODE')
                ->where($where)
                ->select();
            $logistic_model_db_key_arr = array_column($logistic_model_arr, 'ID');
            $logistic_model_keyval_arr = array_column($logistic_model_arr, 'LOGISTICS_MODE', 'ID');
        }
        foreach ($xlsData as &$value) {
            if (in_array($value['BWC_ORDER_STATUS'], $cd_key['order_status_key'])) {
                $value['BWC_ORDER_STATUS'] = $cd_key['order_status_arr'][$value['BWC_ORDER_STATUS']]['CD_VAL'];
            }
            if (in_array($value['WHOLE_STATUS_CD'], $cd_key['whole_status_key'])) {
                $value['WHOLE_STATUS_CD'] = $cd_key['whole_status_arr'][$value['WHOLE_STATUS_CD']]['CD_VAL'];
            }
            if (in_array($value['logistic_cd'], $logistic_arrary_key)) {
                $value['logistic_cd'] = $logistic_arrary[$value['logistic_cd']]['CD_VAL'];
            }
            if (in_array($value['WAREHOUSE'], $warehouse_arrary_key)) {
                $value['WAREHOUSE'] = $warehouse_arrary[$value['WAREHOUSE']]['CD_VAL'];
            }
            if (in_array($value['logistic_model_id'], $logistic_model_db_key_arr)) {
                $value['logistic_model_id'] = $logistic_model_keyval_arr[$value['logistic_model_id']];
            }
            $value['SOURCE'] = $value['FILE_NAME'] ? L('excel导入') : L('自动拉单');
            $value['ADDRESS_USER_ADDRESS1'] = strip_tags($value['ADDRESS_USER_ADDRESS1']);
        }
        unset($value);
        return $xlsData;
    }

    /**
     * @param $PHPExcel
     * @param $allRow
     * @param $array_key
     *
     * @return array
     */
    public static function dataJoin($PHPExcel, $allRow, $array_key)
    {
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {    //从第二行数据开始读取
            foreach ($array_key as $key => $value) {
                $res[$key] = $PHPExcel->getActiveSheet()->getCell($value . $currentRow)->getValue();
                if (is_object($res[$key])) {
                    $res[$key] = (string)$res[$key];
                }
                if (strpos($res[$key], 'VLOOKUP') !== 0) {
                    // $res[$key] = $PHPExcel->getActiveSheet()->getCell($value . $currentRow)->getCalculatedValue();
                }
            }
            $res_arr[] = $res;
        }
        return $res_arr;
    }

    /**
     * @return array
     */
    private function cdDataGet()
    {
        $logistic_arrary = B2bModel::get_code_cd('N00070');
        $warehouse_arrary = B2bModel::get_code_cd('N00068');
        $logistic_arrary_key = array_column($logistic_arrary, 'CD');
        $warehouse_arrary_key = array_column($warehouse_arrary, 'CD');
        $cd_key['whole_status_arr'] = B2bModel::get_code_cd('N00182');
        $cd_key['order_status_arr'] = B2bModel::get_code_cd('N00055');
        $cd_key['whole_status_key'] = array_column($cd_key['whole_status_arr'], 'CD');
        $cd_key['order_status_key'] = array_column($cd_key['order_status_arr'], 'CD');
        return array($logistic_arrary, $warehouse_arrary, $logistic_arrary_key, $warehouse_arrary_key, $cd_key);
    }

    public static function exportExcel($xlsName, $xlsCell, $xlsData)
    {
        $Orders = A('Home/Orders');
        $Orders->exportExcel_self($xlsName, $xlsCell, $xlsData);
    }

    public static function getExportCsvString($xlsData, $to_str_arr = [], $to_special_arr = [])
    {
        $string = '';
        foreach (DataModel::toYield($xlsData) as $key => $value) {
            foreach (DataModel::toYield($value) as $k => $val) {
                // $value[$k] = iconv('utf-8', 'UTF-16LE', $value[$k]);
                if ($to_str_arr && in_array($k, $to_str_arr)) {
                    $value[$k] = "'" . $value[$k];
                }
                /*if ($to_special_arr && in_array($k, $to_special_arr)) {
                    $value[$k] = htmlspecialchars_decode($value[$k], ENT_QUOTES);
                }*/
            }
            if ($value) {
                // $value = str_replace(array("\r\n", "\r", "\n"),'',$value);
                $value = str_replace(',','，',$value);  // 逗号为分隔符
                $string .= '"';
                $string .= implode('","', $value) . '"' . "\n";
            }
            unset($xlsData[$key]);
        }
        unset($xlsData);
        return $string;
    }

    public static function exportCsv($xlsName, $string)
    {
        $filename = $xlsName . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo chr(0xEF).chr(0xBB).chr(0xBF);
        echo $string;
    }
}