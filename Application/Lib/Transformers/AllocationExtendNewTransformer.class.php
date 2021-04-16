<?php
/**
 * User: yangsu
 * Date: 19/6/19
 * Time: 14:36
 */

class AllocationExtendNewTransformer extends Transformer
{
    private $show_info_keys = [
        "allo_no",
        "state",
        "state_val",
        "allo_out_warehouse_cd",
        "allo_out_warehouse_cd_val",
        "allo_in_warehouse_cd",
        "allo_in_warehouse_cd_val",
        "originating_warehouse_location",
        "destination_warehouse_location",
        "transfer_use_type",
        "reviewer_by",
        "task_launch_by",
        "transfer_out_library_by",
        "transfer_warehousing_by",
        "use_fawang_logistics",
        "total_value_goods",
        "total_sales",
        "expected_delivery_date",
        "expected_warehousing_date",
        "planned_transportation_channel_cd",
        "planned_transportation_channel_cd_val",
        "create_user",
        "create_time",
        "service_fee",
        "logistics_costs",
        "tariff_sum",
        "allo_out_team",
        "allo_out_team_val",
        "allo_in_team",
        "allo_in_team_val",
        "out_reason_difference",
        "in_reason_difference",
        'allo_out_status',
        'allo_in_status',
        'allo_out_status_val',
        'allo_in_status_val',
        'quote_no',
        'out_mark_by',
        'out_mark_at',
        'in_mark_by',
        'in_mark_at',
        'process_id',
        'small_team_cd_val',
        "is_optimize_team",
    ];

    private $show_goods_keys = [
        "sku_id",
        "upc_id",
        "spu_name",
        "attributes",
        "image_url",
        "average_price_goods_without_tax_cny",
        "average_po_internal_cost_cny",
        "po_outside_cost_unit_price_cny",
        "tax_free_sales_unit_price_currency_cd",
        "tax_free_sales_unit_price_currency_cd_val",
        "tax_free_sales_unit_price",
        "transfer_authentic_products",
        "transfer_defective_products",
        "weight",
        "estimated_total_weight",
        "number_boxes",
        "number_per_box",
        "case_number",
        "box_length_and_width_cm",
        "net_weight_kg",
        "number_authentic_outbound",
        "number_defective_outbound",
        "remaining_outbound",
        "number_authentic_warehousing",
        "number_defective_warehousing",
        "remaining_inventory",
        "location_code",
        "defective_location_code",
        "location_code_back",
    ];

    private $show_out_stocks_info_keys = [
        "out_stock_id",
        "transport_company_id",
        "transport_company_id_val",
        "third_party_warehouse_entry_number",
        "outbound_cost_currency_cd",
        "outbound_cost_currency_cd_val",
        "outbound_cost",
        "head_logistics_fee_currency_cd",
        "head_logistics_fee_currency_cd_val",
        "head_logistics_fee",
        "have_insurance",
        "insurance_claims_cd",
        "insurance_claims_cd_val",
        "insurance_coverage_cd",
        "insurance_coverage_cd_val",
        "insurance_fee_currency_cd",
        "insurance_fee_currency_cd_val",
        "insurance_fee",
        "insurance_fee_cny",
        "outbound_cost_cny",
        "head_logistics_fee_cny",
        "bill_id",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "tracking_number",
        "send_warehouse_way",
        "planned_transportation_channel_cd",
        "planned_transportation_channel_cd_val",
        "customs_clear",
        "cube_feet_type",
        "cube_feet_val",
        "out_plate_number_type",
        "out_plate_number_val",
        "cabinet_type",
        "cabinet_number",
        "strip_p_seal",
        "ori_logistics_state",
        "logistics_state",
        "stock_in_state",
        "reason_difference",
        "in_stock_complete_at",
        "in_stock_complete_by",
        "in_sum",
        "differ_num",
        "is_edit",
        "node_update",
        "in_stock",
        "send_wechat_message",
        "is_auth_edit",
        "is_auth_push_message",
        "node_system_plan_time",
        "insurance_type",
        "is_optimize_team",
        "oversea_in_storage_no",
        "shipping_company_name",
        "send_warehouse_way_val",
        'cabinet_type_val',
        'customs_clear_val',
        'planned_transportation_channel_cd_val_val'
    ];
    private $show_out_stocks_goods_keys = [
        "sku_id",
        "upc_id",
        "spu_name",
        "attributes",
        "image_url",
        "this_out_authentic_products",
        "this_out_defective_products",
        "location_code",
        "defective_location_code",
        "location_code_back",
        "in_sum",
        "differ_num",
        "transfer_authentic_products",
        "transfer_defective_products",
        "this_out_authentic_products",
        "this_out_defective_products",
        "number_authentic_outbound",
        "number_defective_outbound",
        "number_authentic_outbound_this",
        "number_defective_outbound_this",
        "number_authentic_warehousing",
        "number_defective_warehousing",
        "this_in_authentic_products",
        "this_in_defective_products",
        "in_stock_list",
        
    ];
    private $show_in_stocks_info_keys = [
        "tariff_currency_cd",
        "tariff_currency_cd_val",
        "tariff",
        "shelf_cost_currency_cd",
        "shelf_cost_currency_cd_val",
        "shelf_cost",
        "value_added_service_fee_currency_cd",
        "value_added_service_fee_currency_cd_val",
        "value_added_service_fee",
        "shelf_cost_cny",
        "value_added_service_fee_cny",
        "tariff_cny",
        "bill_id",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
    private $show_in_stocks_goods_keys = [
        "sku_id",
        "upc_id",
        "spu_name",
        "attributes",
        "image_url",
        "this_in_authentic_products",
        "this_in_defective_products",
        "location_code",
        "defective_location_code",
        "location_code_back",
    ];

    public function transformAlloInfo($data)
    {

        $temp_res = $this->assemblyInfo($data);

        $temp_res = $this->assemblyGoods($data, $temp_res);
      
        $temp_res['profit']['gross_profit'] = number_format(
            $temp_res['info']['total_sales'] - $temp_res['info']['total_value_goods'],
            2);
        if (0 == $temp_res['info']['total_value_goods']) {
            $temp_res['profit']['gross_profit_margin'] = '∞';
            $temp_res['profit']['gross_profit_margin_symbol'] = null;
        } else {
            $temp_res['profit']['gross_profit_margin'] = number_format(
                DataModel::unking($temp_res['profit']['gross_profit']) / DataModel::unking($temp_res['info']['total_sales']) * 100,
                2);
            $temp_res['profit']['gross_profit_margin_symbol'] = '%';
        }
        $temp_res['work'] = $data['work'];
        $temp_res = $this->outStocksTransformer($data, $temp_res);
        $temp_res = $this->inStocksTransformer($data, $temp_res);
        return $temp_res;
    }

    /**
     * @param $data
     * @param $temp_res
     *
     * @return mixed
     */
    private function assemblyInfo($data)
    {
        foreach ($this->show_info_keys as $show_info_key) {
            if ('transfer_use_type' == $show_info_key && $data['info']['transfer_use_type']) {
                $temp_res['info']['transfer_use_type'] = $data['info']['transfer_use_type'];
                $temp_res['info']['transfer_use_type_val'] = CodeModel::getTransferUseTypeCode()[$data['info']['transfer_use_type']];
            } else {
                $temp_res['info'][$show_info_key] = $data['info'][$show_info_key];
            }
        }
        $temp_res['info']['use_fawang_logistics_val'] = CodeModel::getSendNetCode()[$temp_res['info']['use_fawang_logistics']];
        $transfer_authentic_products_array = array_column($data['goods'], 'transfer_authentic_products');
        $temp_res['info']['sum_transfer_authentic_products'] = array_sum(array_map(function ($datum) {
                return DataModel::unking($datum);
            }, $transfer_authentic_products_array)
        );
        $transfer_defective_products_array = array_column($data['goods'], 'transfer_defective_products');
        $temp_res['info']['sum_transfer_defective_products'] = array_sum(array_map(function ($datum) {
                return DataModel::unking($datum);
            }, $transfer_defective_products_array)
        );
        return $temp_res;
    }

    /**
     * @param $data
     * @param $temp_res
     *
     * @return mixed
     */
    private function assemblyGoods($data, $temp_res)
    {

        foreach ($data['goods'] as $datum) {
            $temp_datum = [];
            foreach ($this->show_goods_keys as $show_goods_key) {
                $temp_datum[$show_goods_key] = $datum[$show_goods_key];
            }
            $temp_datum['upc_id'] = $datum['product_sku']['upc_id'];
            if($datum['product_sku']['upc_more']) {
                $upc_more_arr = explode(',', $datum['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $temp_datum['upc_id']);
                $temp_datum['upc_id'] = implode(",\r", $upc_more_arr);
            }
            $temp_datum['weight'] = $datum['product_sku']['sku_weight'] / 1000;
            $temp_datum['estimated_total_weight'] = $temp_datum['weight'] * $datum['demand_allo_num'];
            $temp_res['goods'][] = $temp_datum;
        };
        return $temp_res;
    }

    /**
     * @param $data
     * @param $temp_res
     *
     * @return array
     */
    private function outStocksTransformer($data, $temp_res)
    {   
       
        foreach ($data['out_stocks'] as $datum) {
            $temp_datum = [];
            foreach ($this->show_out_stocks_info_keys as $show_out_stocks_info_key) {
                if ('bill_id' == $show_out_stocks_info_key) {
                    $out_stock_array = explode(',', $datum[$show_out_stocks_info_key]);
                    $temp_res['out_stocks'][$datum['out_stocks_id']]['logistics_information'][$show_out_stocks_info_key] = implode(',', array_unique($out_stock_array));
                }else{
                    $temp_res['out_stocks'][$datum['out_stocks_id']]['logistics_information'][$show_out_stocks_info_key] = $datum[$show_out_stocks_info_key];
                }
                
            }
            foreach ($this->show_out_stocks_goods_keys as $show_out_stocks_goods_key) {
                $temp_datum[$show_out_stocks_goods_key] = $datum[$show_out_stocks_goods_key];
            }
            $temp_datum['upc_id'] = $datum['product_sku']['upc_id'];
            if($datum['product_sku']['upc_more']) {
                $upc_more_arr = explode(',', $datum['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $temp_datum['upc_id']);
                $temp_datum['upc_id'] = implode(",\r", $upc_more_arr);
            }

            $temp_res['out_stocks'][$datum['out_stocks_id']]['goods'][] = $temp_datum;
        };
        $temp_res['out_stocks'] = array_values($temp_res['out_stocks']);
        return $temp_res;
    }

    /**
     * @param $data
     * @param $temp_res
     *
     * @return mixed
     */
    private function inStocksTransformer($data, $temp_res)
    {
        foreach ($data['in_stocks'] as $datum) {
            $temp_datum = [];
            foreach ($this->show_in_stocks_info_keys as $show_in_stocks_info_key) {
                if ('bill_id' == $show_in_stocks_info_key) {
                    $in_stock_array = explode(',', $datum[$show_in_stocks_info_key]);
                    $temp_res['in_stocks'][$datum['in_stocks_id']]['logistics_information'][$show_in_stocks_info_key] = implode(',', array_unique($in_stock_array));
                } else {
                    $temp_res['in_stocks'][$datum['in_stocks_id']]['logistics_information'][$show_in_stocks_info_key] = $datum[$show_in_stocks_info_key];
                }
            }
            foreach ($this->show_in_stocks_goods_keys as $show_in_stocks_goods_key) {
                $temp_datum[$show_in_stocks_goods_key] = $datum[$show_in_stocks_goods_key];
            }
            $temp_datum['upc_id'] = $datum['product_sku']['upc_id'];
            $temp_res['in_stocks'][$datum['in_stocks_id']]['goods'][] = $temp_datum;
        };
        $temp_res['in_stocks'] = array_values($temp_res['in_stocks']);
        return $temp_res;
    }
}