<?php
return array(
    // version serialize num
    'VER_NUM' => '201808311533',
    /* 状态值配置 */
    //订单状态
//    'N000550100'               => '待确认',
//    'N000550200'               => '确认中',
//    'N000550300'               => '待付款',
//    'N000550400'               => '待发货',
//    'N000550500'               => '待收货',
//    'N000550600'               => '已收货',
//    'N000550700'               => '已付尾款',
//    'N000550800'               => '交易成功',
//    'N000550900'               => '交易关闭',
//    'N000551000'               => '交易取消',
    'store' => [
        'N000830100' => 'B5C',
        'N000830200' => 'BHB',
        'N000830300' => 'Qoo10-SG',
        'N000830400' => 'Qoo10-KRS',
        'N000830500' => 'Qoo10-JP',
        'N000830600' => 'Wish',
        'N000830700' => 'Lazada-MY',
        'N000830800' => 'Lazada-ID',
        'N000830900' => 'Lazada-TH',
        'N000831000' => 'Lazada-PH',
        'N000831100' => 'Lazada-SG',
        'N000831200' => 'Ebay',
        'N000831300' => 'YT(羊驼)',
        'N000831400' => 'Gshopper-KR'
    ],
    //订单类型
    'N000620100' => '一般订单',
    'N000620200' => '求购订单',
    'N000620300' => '待确认的订单类型',
    'N000620400' => '一件代发订单',

    'shunfeng' => 'shunfeng',
    'shentong' => 'shentong',
    'tiantian' => 'tiantian',
    'quanfengkuaidi' => 'quanfengkuaidi',
    'ems' => 'ems',
    'yuantong' => 'yuantong',
    'zhongtong' => 'zhongtong',
    'yunda' => 'yunda',
    'globell' => 'globell',
    'gg' => 'GG Express',
    'N000700800' => '顺丰速运',
    'N000700100' => '申通快递',
    'N000700200' => '全峰快递',
    'N000700700' => '天天快递',
    'N000700600' => 'EMS',
    'N000700400' => '圆通速递',
    'N000700500' => '中通快递',
    'N000700300' => '韵达快递',
    'N000700900' => 'Globell',
    'N000701000' => 'GG Express',
    'N000701100' => 'DEPPON EXPRESS',
    'N000701200' => 'CJ대한통운(大韩通运)',
    'N000701300' => '佐川急便',
    'N000701400' => 'QXPRESS',
    '顺丰速运' => '顺丰速运',
    '申通快递' => '申通快递',
    '天天快递' => '天天快递',
    'EMS' => 'EMS',
    '圆通速递' => '圆通速递',
    '中通快递' => '中通快递',
    '韵达快递' => '韵达快递',
    '易邮宝' => '易邮宝',
    'YUNDA韵达快递' => '韵达快递',
    'YUNDA' => 'YUNDA',
    'Globell' => 'Globell',
    'GGExpress' => 'GG Express',
    'GG' => 'GG Express',
    //仓库
    'N000680100' => '国内仓',
    'N000680200' => '韩国仓',
    'N000680300' => '宁波保税仓',

    //货币单位
    'N000590100' => 'USD',
    'N000590200' => 'KRW',
    'N000590300' => 'RMB',
    'N000590400' => 'JPY',

    //采购订单审批状态
    "N001320100" => "草稿",
    "N001320200" => "审批中",
    "N001320300" => "审批完成",
    "N001320400" => "审批失败",


    "expe_company" => [
        "N000700100" => ["申通快递", "N000700100"],
        "N000700200" => ["全峰快递", "N000700200"],
        "N000700300" => ["韵达快递", "N000700300"],
        "N000700400" => ["圆通速递", "N000700400", "YTO圆通快递"],
        "N000700500" => ["中通快递", "N000700500"],
        "N000700600" => ["EMS", "N000700600"],
        "N000700700" => ["天天快递", "N000700700"],
        "N000700800" => ["顺丰速运", "N000700800"],
        "N000700900" => ["Globell", "N000700900"],
        "N000701000" => ["GG EXPRESS", "N000701000"],
        "N000701100" => ["DEPPON EXPRESS", "N000701100"],
        "N000701200" => ["CJ대한통운(大韩通运)", "N000701200"],
        "N000701300" => ["佐川急便", "N000701300"],
        "N000701400" => ["QXPRESS", "N000701400"],
    ],

    //物流状态
    "logistic_status" => [
        "N001270100" => "揽收",
        "N001270200" => "运输中(CN)",
        "N001270300" => "清关中",
        "N001270400" => "完成清关",
        "N001270500" => "派送中",
        "N001270600" => "正常签收",
        "N001270700" => "异常签收",
        "N001270800" => "运输中"
    ],

    //采购订单状态
    "purchase_order_status" => [
        "N001320100" => "草稿",
        "N001320200" => "审批中",
        "N001320300" => "审批完成",
        "N001320400" => "审批失败",
    ],

    //系统发送邮件配置
    "email_address" => "erpservice@gshopper.com",
    "email_password" => "Izene@123",
    "email_host" => "smtp.exmail.qq.com",
    "email_port" => 465,
    "email_user" => "ErpService",

    //审核人邮件地址
    "screening_email_address" => ["baisui@gshopper.com"],
    "screening_email_address_1688Taobao" => ["baisui@gshopper.com", "yuanshixiao@gshopper.com"],
    "screening_cc_email_address" => ["yuanshixiao@gshopper.com"],
    "screening_cc_email_address_1688Taobao" => ["726340047@qq.com"],
    "purchase_payable_notice_email" => ["baisui@gshopper.com", "yuanshixiao@gshopper.com"],
    "special_offer_notice_email" => ["baisui@gshopper.com"],
    "scm_ceo_email" => "feisong@gshopper.com",
    "scm_ceo_email_cc" => "fuming@gshopper.com",
    "finance_email" => "feisong@gshopper.com",

    //rabbit mq配置
    'rabbit_mq_config' => [
        'host' => 'stage-mq.izene.org',
        'port' => '5672',
        'login' => 'gsapp',
        'password' => 'gsappizene123',
        'vhost' => '/gs-gapp'
    ],
    //商品库请求的rabbit mq的配置
    'product_rabbit_mq_config' => [
        'CONNECT' => [
            'host' => '172.16.113.81',
            'port' => '5672',
            'login' => 'gssearch',
            'password' => 'gssearchizene123',
            'vhost' => '/gs-search'
        ],
        'ROUTEKEY' => 'Q-SEARCH-01-RK-01',
        'QUEUENAME' => 'Q-SEARCH-01',
        'EXCHANGENAME' => 'gshopper.search',
        'CONTENTENCODING' => 'UTF-8',
    ],

    // 供应商客户管理系统邮箱地址,测试环境
    'supplier_customer_sys_email_address' => 'erpservice@gshopper.com',
    // 供应商客户管理法务邮箱，测试环境
    'supplier_customer_forensic_email_address' => ['benyin@gshopper.com', 'yuanfeng@gshopper.com'],
    // 供应商客户管理法务跳转地址
    'redirect_audit_addr' => 'erp.gshopper.stage.com',
    // 是否开启抄送
    'is_start_cc' => false,
    // 是否开启密送
    'is_start_bcc' => false,
    //redis 配置
    //'tcp://172.16.111.64:7001',
    'REDIS_SERVER' => [
        '127.0.0.1:6379'
    ],
    'redis_batch_generate_url' => 'http://s.stage.com/check/reloadDeliveryData',
    'openApiUrl' => 'http://openapi.stage.co.kr',
    'thirdFileUpload' => array(
        'bsDomain' => 'http://10.8.6.6:8999/imageUpload',
        'tfsUrl' => 'http://upm01.b5m.com/',
        'userName' => 'duanyi',
        'topicName' => 'goods',
        'singleFileName' => 'file',
        'multiFileName' => 'files',
    ),
    'elasticsearch_conn_conf' => [
        'hosts' => [
            'http://es1.stage.com',
            'http://es2.stage.com',  // you du
            'http://es3.stage.com'  //
        ],
        'retries' => 0,
    ],
    'elasticsearch_search_conf' => [
        'erp' => [
            'index' => 'gs_log',
            'type' => 'gs_log'
        ]
    ],
    'app_push_key' => '5acc8b8df43e4862a0000060',
    'app_push_secret' => 'vquffxyvmaobotvyfw36rilmn494hkgl',

    //insight数据库配置
    'insight_db_config' => [
        'db_type' => 'pdo',
        'db_user' => 'erp',
        'db_pwd' => 'erpprod',
        'db_dsn' => 'pgsql:host=172.16.222.23;dbname=b5mrep',
    ],
    'mt_db_config' => [
        'db_type' => 'pdo',
        'db_user' => 'erp',
        'db_pwd' => 'izene@123',
        'db_dsn' => 'pgsql:host=10.9.5.52;dbname=mt',
    ],
    'b5cErpDBName' => 'b5c_stage',
    'b5cPmsDbName' => 'gshopper_pms_stage',

    'LOGISTIC_AGGREGATION_USER' => 'yangsu@gshopper.com',
    'LOGISTIC_AGGREGATION_CC' => ['due@gshopper.com', 'baisui@gshopper.com', 'feisong@gshopper.com', 'leshan@gshopper.com'],

    // 'WORK_WX_OPEN_FLAG' => true, // 测试环境默认关闭对涉及工单相关的企业微信推送
    'WORK_WX_OPEN_FLAG' => false, // 测试环境默认关闭对涉及工单相关的企业微信推送

    'DEFAULT_CRY' => '9203||leshan', // 默认实施人员
    'DEFAULT_PDT' => '9203||leshan', // 默认产品人员
    'DEFAULT_DEV' => '9628||mengyi', // 默认开发人员
    'DEFAULT_TST' => '9628||mengyi', // 默认测试人员

    # 订单列表可以导出发票的店铺ID集合
    "order_list_export_invoice_store_ids" => [
        '352','69','112','118','26','27','83','92','193','222','256','356','359','135','156','224','350','147','180','190','219','357','238','248', '51','90','361',
        '51','71','315', '104','13','367','368','366','248','352','363','371','372','446','447',
    ],

     #德国乐天二店 使用计数器代替订单号
    "order_list_export_invoice_counter_store_id" => [
        '353', '329'
    ],
    #德国乐天二店 计数器起始值
    "order_list_export_counter_start" => 8900,
);

