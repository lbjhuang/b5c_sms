<?php

/**
 * User: shenmo
 * Date: 20/12/8
 * Time: 11:25
 */
include_once "BasisModel.class.php";

/**
 * Class TrackingNoModel
 */
class TrackingNoModel extends BasisModel
{
    protected $autoCheckFields = false;

    //店铺编号：199 派单的库存占用，可占用的范围需要在现有的基础上增加一层过滤：库存归属公司必须=这个店铺的注册公司。
    //store 199 乐天1店的注册公司
    public static $store = [
        199
    ];
    #需要改变订单状态的id
    public static $CHANGE_BWC_ORDER_STATUS = [];
   
    /**
     * @var array
     */
    protected static $list_cd_arr = [
        'channel' => 'N00083',
        /*'dispatch_status' => 'N00182',
        'logistics_status' => 'N00127',
        'aftermarket_status' => 'N00108',*/
        'sort' => '',
        // 'country_status' => 'N00173',
        'shop_status' => '',
        'warehouse_status' => 'N00068',
        'logistics_company_status' => 'N00070', // 物流公司
        // 'shipping_methods_status' => 'N00173', // 物流方式
        'sales_team_status' => 'N00128',
        'waitpre_status' => 'N00205',
        'down_query_status' => '',
        'do_dispatch_status' => 'N00203',
        'surfaceWayGetStatus' => 'N00201',
        'logisticsSingleStatus' => 'N00208',
        'do_dispatch_type' => 'N00205',
        'recommend_type' => '',
        'patch_err' => '',
        'mark_status' => '',
        'order_status' => '',
        'site_cd' => 'N00262',

        'address_valid_conf' => 'N00343',

        'sell_small_team' => 'N00323',

    ];

    const GC_AUTO_GET_NO_KEY = 'auto-get-gc-face-order';//谷仓自动获取单号集合key
    const GC_AUTO_SEND_COUNT = 'gc-auto-send-count';//谷仓自动获取单号次数

    /**
     * @var array
     */
    protected static $lis_value_arr = [
        'sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'pending_sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_ord_time" => [
                "CD" => "send_ord_time",
                "CD_VAL" => "按派单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'patch_sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'down_query_status' => [
            "order_id" => [
                "CD" => "order_id",
                "CD_VAL" => "订单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "thr_order_id" => [
                "CD" => "thr_order_id",
                "CD_VAL" => "第三方订单ID",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "thr_order_no" => [
                "CD" => "thr_order_no",
                "CD_VAL" => "第三方订单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_phone" => [
                "CD" => "receiver_phone",
                "CD_VAL" => "收货人手机号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_the_serial_number" => [
                "CD" => "pay_the_serial_number",
                "CD_VAL" => "支付流水号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_method" => [
                "CD" => "pay_method",
                "CD_VAL" => "支付类型",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_tel" => [
                "CD" => "receiver_tel",
                "CD_VAL" => "收货人电话",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "consignee_name" => [
                "CD" => "consignee_name",
                "CD_VAL" => "收货人姓名",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_email" => [
                "CD" => "receiver_email",
                "CD_VAL" => "收货人邮箱",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "tracking_number" => [
                "CD" => "tracking_number",
                "CD_VAL" => "运单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sku_title" => [
                "CD" => "sku_title",
                "CD_VAL" => "SKU 标题",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sku_number" => [
                "CD" => "sku_number",
                "CD_VAL" => "SKU 编号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "zip_code" => [
                "CD" => "zip_code",
                "CD_VAL" => "邮编",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
        ],
        'do_patch_type' => [
            "normal" => [
                "CD" => "normal",
                "CD_VAL" => "正常",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "incomplete_information" => [
                "CD" => "incomplete_information",
                "CD_VAL" => "信息不全",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_delivery_warehouse" => [
                "CD" => "no_delivery_warehouse",
                "CD_VAL" => "无下发仓库",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_hair_logistics" => [
                "CD" => "no_hair_logistics",
                "CD_VAL" => "无下发物流",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_shipping_template" => [
                "CD" => "no_shipping_template",
                "CD_VAL" => "无运费模板",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sheet_purchase_failed" => [
                "CD" => "sheet_purchase_failed",
                "CD_VAL" => "面单获取失败",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "inventory_shortage" => [
                "CD" => "inventory_shortage",
                "CD_VAL" => "库存不足",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
        ],
        'recommend_type' => [
            "recommend_system" => [
                "CD" => "recommend_system",
                "CD_VAL" => "系统推荐",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "recommend_user" => [
                "CD" => "recommend_user",
                "CD_VAL" => "用户推荐",
                "SORT_NO" => "1",
                "ETc" => ""
            ],
            "operation_assignment" => [
                "CD" => "operation_assignment",
                "CD_VAL" => "运营指派",
                "SORT_NO" => "2",
                "ETc" => ""
            ],
        ],
        'patch_err' => [
            "un_inventory" => [
                "CD" => "un_inventory",
                "CD_VAL" => "库存不足",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "un_warehouse_logistics" => [
                "CD" => "un_warehouse_logistics",
                "CD_VAL" => "无仓库物流",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "un_single_number" => [
                "CD" => "un_single_number",
                "CD_VAL" => "未获取运单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'mark_status' => [
            "0" => [
                "CD" => "0",
                "CD_VAL" => "未标记订单",
                "SORT_NO" => "1",
                "ETc" => ""
            ],
            "1" => [
                "CD" => "1",
                "CD_VAL" => "已标记订单",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'order_status' => [
            "N000550400" => [
                "CD" => "N000550400",
                "CD_VAL" => "待发货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550500" => [
                "CD" => "N000550500",
                "CD_VAL" => "待收货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550600" => [
                "CD" => "N000550600",
                "CD_VAL" => "已收货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550800" => [
                "CD" => "N000550800",
                "CD_VAL" => "交易成功",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
        ]
    ];

}