|键|类型|说明|
|:-------|:-------|:-------|
| - data |object  | 无 |
| - - info |object  | 基础信息 |
| allo_no | string| 调拨单号 |
| state | string| 调拨状态 |
| allo_out_warehouse_cd | string| 调出仓库cd |
| allo_out_warehouse_cd_val | string| 调出仓库cd 值 |
| allo_in_warehouse_cd | string| 调入仓库 |
| allo_in_warehouse_cd_val | string| 无 |
| originating_warehouse_location | string| 始发仓库  |
| destination_warehouse_location | string| 目的仓库 |
| transfer_use_type | string| 调拨用途 |
| reviewer_by | string| 审核人 |
| task_launch_by | string| 作业确认责任人 |
| transfer_out_library_by | string| 出库确认责任人 |
| transfer_warehousing_by | string| 入库确认责任人 |
| use_fawang_logistics | string| 是否对接发网仓 |
| use_fawang_logistics_val | string| 是否对接发网仓值 |
| total_value_goods | string| 货值总额 |
| total_sales | string| 销售总额 |
| expected_date_delivery | string| 期望出库日期 |
| expected_warehousing_date | string| 期望入库日期 |
| planned_transportation_channel_cd | string| 计划运输渠道 |
| planned_transportation_channel_cd_val | string| 无 |
| - - profit |object  | 利润信息 |
| gross_profit | string| 毛利 |
| gross_profit_margin | string| 毛利率 |
| - - goods |object  | 商品信息 |
| sku_id | string| 无 |
| upc_id | string| 条码 |
| spu_name | string| 商品名 |
| attributes | string| 属性 |
| image_url | string| 图片 |
| average_price_goods_without_tax_cny | string| 平均不含税商品单价 |
| average_po_internal_cost_cny | string| 平均po内费用单价 |
| po_outside_cost_unit_price_cny | string| po外费用单价 |
| tax_free_sales_unit_price_currency_cd | string| 不含税销售单价币种 |
| tax_free_sales_unit_price_currency_cd_val | string|  |
| tax_free_sales_unit_price | string| 不含税销售单价 |
| transfer_authentic_products | string| 调拨正品数量 |
| transfer_defective_products | string| 调拨残次品数量 |
| weight | string| 重量 |
| estimated_total_weight | string| 预计总重 |
| number_boxes | string| 箱数 |
| number_per_box | string| 每箱个数 |
| case_number | string| 箱号 |
| box_length_and_width_cm | string| 箱子长宽高  |
| net_weight_kg | string| 净重 |
| number_authentic_outbound | string| 出库正品 |
| number_defective_outbound | string| 出库残次品 |
| remaining_outbound | string| 剩余出库 |
| number_authentic_warehousing | string| 入库正品 |
| number_defective_warehousing | string| 入库残次品 |
| remaining_inventory | string| 剩余入库 |
| - - work |object  | 作业 |
| beat_information | string| 打托信息 |
| - - job_photos |object  | 作业照片 |
| job_note | string| 作业备注 |
| operating_expenses_currency_cd | string| 作业费用币种 |
| operating_expenses_currency_cd_val | string| 无 |
| operating_expenses | string| 作业费用 |
| value_added_service_fee_currency_cd | string| 增值服务费币种 |
| value_added_service_fee_currency_cd_val | string| 无 |
| value_added_service_fee | string| 增值服务费 |
| - - out_stocks |object  | 出库 |
| - - - logistics_information |object  | 物流信息 |
| transport_company | string| 运输公司 |
| third_party_warehouse_entry_number | string| 第三方仓入仓号 |
| outbound_cost_currency_cd | string| 出库费用币种 |
| outbound_cost_currency_cd_val | string| 出库费用币种值 |
| outbound_cost | string| 出库费用 |
| head_logistics_fee_currency_cd | string| 头程物流费用币种 |
| head_logistics_fee_currency_cd_val | string| 头程物流费用币种值 |
| head_logistics_fee | string| 头程物流费用 |
| have_insurance | string| 有无保险 |
| insurance_claims_cd | string| 保险理赔cd |
| insurance_claims_cd_val | string|  |
| insurance_coverage_cd | string| 保险范围 |
| insurance_coverage_cd_val | string|  |
| insurance_fee_currency_cd | string| 保险费用币种 |
| insurance_fee_currency_cd_val | string| 无 |
| insurance_fee | string| 保险费用 |
| reason_difference | string| 差异原因 |
| - - - goods |object  | 商品 |
| sku_id | string| 无 |
| upc_id | string| 条码 |
| spu_name | string| 商品名 |
| attributes | string| 属性 |
| image_url | string| 图片 |
| average_price_goods_without_tax_cny | string| 平均不含税商品单价 |
| average_po_internal_cost_cny | string| 平均po内费用单价 |
| po_outside_cost_unit_price_cny | string| po外费用单价 |
| tax_free_sales_unit_price_currency_cd | string| 不含税销售单价币种 |
| tax_free_sales_unit_price_currency_cd_val | string|  |
| tax_free_sales_unit_price | string| 不含税销售单价 |
| transfer_authentic_products | string| 调拨正品数量 |
| transfer_defective_products | string| 调拨残次品数量 |
| weight | string| 重量 |
| estimated_total_weight | string| 预计总重 |
| number_boxes | string| 箱数 |
| number_per_box | string| 每箱个数 |
| case_number | string| 箱号 |
| box_length_and_width_cm | string| 箱子长宽高  |
| net_weight_kg | string| 净重 |
| number_authentic_outbound | string| 出库正品 |
| number_defective_outbound | string| 出库残次品 |
| remaining_outbound | string| 剩余出库 |
| number_authentic_warehousing | string| 入库正品 |
| number_defective_warehousing | string| 入库残次品 |
| remaining_inventory | string| 剩余入库 |
| this_out_authentic_products | string| 无 |
| this_out_defective_products | string| 无 |
| - - in_stocks |object  | 无 |
| - - - logistics_information |object  | 无 |
| tariff_currency_cd | string| 无 |
| tariff_currency_cd_val | string| 无 |
| tariff | string| 无 |
| shelf_cost_currency_cd | string| 无 |
| shelf_cost_currency_cd_val | string| 无 |
| shelf_cost | string| 无 |
| value_added_service_fee_currency_cd | string| 无 |
| value_added_service_fee_currency_cd_val | string| 无 |
| value_added_service_fee | string| 无 |
| - - - goods |object  | 无 |
| sku_id | string| 无 |
| upc_id | string| 无 |
| spu_name | string| 无 |
| attributes | string| 无 |
| image_url | string| 无 |