<?php
// jenkins 文件覆盖
return array(
    // version serialize num
    'VER_NUM' => '201705180152',
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
    'N000620100'               => '一般订单',
    'N000620200'               => '求购订单',
    'N000620300'               => '待确认的订单类型',
    'N000620400'               => '一件代发订单',

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
    'N000680100'               => '国内仓',
    'N000680200'               => '韩国仓',
    'N000680300'               => '宁波保税仓',

    //货币单位
    'N000590100'               => 'USD',
    'N000590200'               => 'KRW',
    'N000590300'               => 'RMB',
    'N000590400'               => 'JPY',

    //采购订单审批状态
    "N001320100" => "草稿",
    "N001320200" => "审批中",
    "N001320300" => "审批完成",
    "N001320400" => "审批失败",


    "expe_company"=>[
        "N000700100"=>["申通快递","N000700100"],
        "N000700200"=>["全峰快递","N000700200"],
        "N000700300"=>["韵达快递","N000700300"],
        "N000700400"=>["圆通速递","N000700400","YTO圆通快递"],
        "N000700500"=>["中通快递","N000700500"],
        "N000700600"=>["EMS","N000700600"],
        "N000700700"=>["天天快递","N000700700"],
        "N000700800"=>["顺丰速运","N000700800"],
        "N000700900"=>["Globell","N000700900"],
        "N000701000"=>["GG EXPRESS","N000701000"],
        "N000701100"=>["DEPPON EXPRESS","N000701100"],
        "N000701200"=>["CJ대한통운(大韩通运)","N000701200"],
        "N000701300"=>["佐川急便","N000701300"],
        "N000701400"=>["QXPRESS","N000701400"],
    ],

    //物流状态
    "logistic_status"=>[
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
    "purchase_order_status"=>[
        "N001320100" => "草稿",
        "N001320200" => "审批中",
        "N001320300" => "审批完成",
        "N001320400" => "审批失败",
    ],

    //系统发送邮件配置
    "email_address"     => "erpservice@gshopper.com",
    "email_password"    => "Izene@123",
    "email_host"        => "smtp.exmail.qq.com",
    "email_port"        => 465,
    "email_user"        => "ErpService",

    //审核人邮件地址
    "screening_email_address"               => ["feihong@gshopper.com"],
    "screening_email_address_1688Taobao"    => ["lengan@gshopper.com","miaoyu@gshopper.com"],
    "screening_cc_email_address"            => ["miaoyu@gshopper.com","finance@gshopper.com"],
    "screening_cc_email_address_1688Taobao" => ["finance@gshopper.com"],
    "purchase_payable_notice_email"         => ["finance@gshopper.com"],
    "special_offer_notice_email"            => ["specialoffer@gshopper.com"],
    "scm_ceo_email"                         => "Helen.Yuan@gshopper.com",
    "scm_ceo_email_cc"                      => "xinfan@gshopper.com",
    "finance_email"                         => "finance@gshopper.com",

    //rabbit mq配置
    'rabbit_mq_config' => [
        'host' => '10.8.5.20',
        'port' => '5672',
        'login' => 'gsapp',
        'password' => 'gsappizene123',
        'vhost'=>'/gs-gapp'
    ],
    
    //商品库请求的rabbit mq的配置
    'product_rabbit_mq_config' => [
        'CONNECT' => [
            'host' => '10.80.5.60',
            'port' => '5672',
            'login' => 'gssearch',
            'password' => 'gssearchizene123',
            'vhost' => '/gs-search'
        ],
        'ROUTEKEY' => 'Q-SEARCH-01',
        'QUEUENAME' => 'Q-SEARCH-01',
        'EXCHANGENAME' => 'esExchange',
        'CONTENTENCODING' => 'UTF-8',
    ],
    
    // 供应商客户管理系统邮箱地址，上线环境
    'supplier_customer_sys_email_address' => 'erpservice@gshopper.com',
    // 供应商客户管理法务邮箱，上线环境
    'supplier_customer_forensic_email_address' => 'Legal@gshopper.com',
    // 供应商客户管理法务跳转地址
    'redirect_audit_addr' => 'erp.gshopper.com',
    // 是否开启抄送
    'is_start_cc' => true,
    // 是否开启密送
    'is_start_bcc' => true,
    //redis 配置
    'REDIS_SERVER'=>[
        'tcp://10.30.99.78:6000',
        'tcp://10.30.99.78:6001',
        'tcp://10.30.99.80:6002',
        'tcp://10.30.99.80:6003',
        'tcp://10.30.99.147:6004',
        'tcp://10.30.99.147:6005',
    ],
    'redis_batch_generate_url' => 'http://s.b5m.com/check/reloadDeliveryData',
    'openApiUrl'=>'http://openapi.gshopper.com',
    
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
            'http://10.8.5.84:9200',
            #'http://10.8.5.85:9200',
            'http://10.8.5.184:9200',
            'http://10.8.5.185:9200',
            'http://10.8.5.186:9200',
        ],
        'retries' => 1,
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
        'db_type'       => 'pdo',
        'db_user'       => 'erp',
        'db_pwd'        => 'erp@123',
        'db_dsn'        => 'pgsql:host=10.8.5.61;dbname=b5mrep',
    ],
    'bi_db_config' => [
        'db_type'       => 'pdo',
        'db_user'       => 'bi',
        'db_pwd'        => 'izene123',
        'db_dsn'        => 'pgsql:host=10.8.5.61;dbname=b5mrep',
    ],
    'mt_db_config' => [
        'db_type' => 'pdo',
        'db_user' => 'erp',
        'db_pwd' => 'izene@123',
        'db_dsn' => 'pgsql:host=10.9.5.52;dbname=mt',
    ],
    'b5cErpDBName' => 'b5m_b5c',
    'b5cPmsDbName' => 'gshopper_pms',

    // 'LOGISTIC_AGGREGATION_USER' => 'fulfillment-center@gshopper.com',
    // 'LOGISTIC_AGGREGATION_CC' => ['business@gshopper.com', 'chuliuxiang@gshopper.com'],
    'LOGISTIC_AGGREGATION_USER' => 'leshan@gshopper.com',
    'LOGISTIC_AGGREGATION_CC' => ['yangsu@gshopper.com', 'feisong@gshopper.com'],

     'WORK_WX_OPEN_FLAG' => true, // 测试环境默认关闭对涉及工单相关的企业微信推送

     'DEFAULT_CRY' => '9626||Habit.Wang', // 默认实施人员眷念
     'DEFAULT_PDT' => '308||Adams.Tan', // 默认产品人员飞松
     'DEFAULT_DEV' => '80||Ben.Huang', // 默认开发人员杨素
     'DEFAULT_TST' => '400||Jeli.Fang', // 默认测试人员百岁

    //GP订单派单后12小时未出库邮件提醒
    'gp_send_reminder_email' => [
        'recipient' => ['logistics@gshopper.com'],
        'cc'        => ['systempmeditor@gshopper.com'],
    ],

    // 合同临期提醒邮件
    'CONTRACT_EXPIRE_REMIND' => [
        'recipient' => ['weslie.li@gshopper.com', 'zijian@gshopper.com', 'adams.tan@gshopper.com', 'legal@gshopper.com']
    ],

    //万邑通地址校验配置
    'address_valid_config' => [
        'api_url'       => 'http://openapi.winit.com.cn/openapi/service',
        'action'        => 'winit.tools.address.isValid',
        'app_key'       => 'yefan@gshopper.com',
        'token'         => 'E97D0047F3CCD4867D8028AB987DE6DC',
        'platform'      => 'GP_ERP',
        'client_id'     => 'OTJHMGQZMDETOGQ0NS00ZGUYLWJJZWMTZGM4YMNJOGFJMZC2',
        'client_secret' => 'MZK2YMRMMWITMDKXOC00Y2RILWIZM2UTMJM3YTBLOGIXYJM2MZGZMDGXMDGWOTI0MTCYODM=',
        'format'        => 'json',
        'language'      => 'zh_CN',
        'sign_method'   => 'md5',
        'version'       => '1.0',
    ],

    'ftp_config' => [
        'local_save_path' => '/opt/b5c-disk/doc/',
        'default' => [
            'host'            => '174.128.8.32',
            'username'        => 'GSHOPPER',
            'password'        => 'xyyrxc9Kx.E1',
            'ftp_upload_path' => '/in/',
            'ftp_download_path' => '/out/'
        ],
    ],

    'amqp_config' => [
        'default' => [
            'host'     => '10.8.5.20',
            'port'     => '5672',
            'user'     => 'gsapp',
            'password' => 'gsapp',
            'vhost'    => '/gs-gapp'
        ]
    ],
    'auto_token_config' => [
        'url'     => 'http://general.b5cai.com/token/refresh',
    ],
    # 订单列表可以导出发票的店铺ID集合
    "order_list_export_invoice_store_ids" => [
        '118','112','69','323','258','256','239','226','224','222','219','193','190','180','156','147','135','92','83','27','26','248','95','252','321','320','13','245','51','90','252','387','388',
    ],

    //德国乐天 计数器起始值及前缀数字设置
    "invoice_store_counter_map" => [
        '245' => [
            'start_counter' => 8900
        ],
        '95' => [
            'start_counter' => 30000,
            'prefix_number' => 3
        ],
        '248' => [
            'start_counter' => 10000,
            'prefix_number' => 1
        ],
    ],
  

);

