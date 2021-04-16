<?php

$db_config = include_once 'db_config.php';
$split_server = substr($_SERVER['SERVER_ADDR'],0,4);
if ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST'] || '10.8' == $split_server) {
    $define_config = include_once APP_PATH . 'Conf/config_online.php';
}elseif ('erp.gshopper.prod.com'== $_SERVER ['HTTP_HOST']) {
    $define_config = include_once APP_PATH . 'Conf/config_prod.php';
}elseif ('sms.test'== $_SERVER ['HTTP_HOST']) {
$define_config = include_once APP_PATH . 'Conf/config_local.php';
}else {
    $define_config = include_once APP_PATH . 'Conf/config_stage.php';
}
$define_config['VER_NUM'] = date('Ymd');
$config = array(
    //redis配置
    /*'DATA_CACHE_PREFIX' => 'erp_php_',//缓存前缀
    'DATA_CACHE_TYPE' => 'Redis',//默认动态缓存为Redis
    'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
    'REDIS_HOST' => '172.16.111.11', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
    'REDIS_PORT' => '6000',//端口号
    'REDIS_TIMEOUT' => '3600',//超时时间
    'REDIS_PERSISTENT' => false,//是否长连接 false=短连接
    'REDIS_AUTH' => null,//AUTH认证密码*/
    //'配置项'=>'配置值'
    'SESSION_TYPE'          => 'db',
    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL' =>'EMERG,ERR,INFO,DEBUG,SQL', // 只记录 EMERG ALERT CRIT ERR 错误
    'LOG_EXCEPTION_RECORD'  =>  false,    // 是否记录异常信息日志
    'APP_AUTOLOAD_PATH'     => '@.ToolClass,@.Model.Universal,@.Model.Orm,@.Model.Oms,@.Behaviors,@.Services,@.Repository,@.Formatters,@.Transformers,@.Model.Wechat,@.Model.Warehouse,@.Logic.Warehouse,@.Logic,@.Logic.Purchase,@.Model.Finance,@.Logic.Finance,@.Model.BTB,@.Model.Purchase,@.Model.Mongodb,@.Exports',// 自动加载机制的自动搜索路径,注意搜索顺序
    'PASSKEY' => '51CMS',
    'LOAD_EXT_FILE' => 'function',
    'USER_AUTH_ON' => true,//开启验证
    'USER_AUTH_TYPE'=>1, //认证类型
    'NOT_AUTH_MODULE'=>'Public,Api,AppDownload',//默认不需要认证的模块
    'USER_AUTH_GATEWAY'=>'?m=public&a=login',//默认的认证网关
    'USER_AUTH_MODEL'=>'Admin',    // 默认验证数据表模型
    'TMPL_L_DELIM' =>"<{",
    'TMPL_R_DELIM' => "}>",

  /*  'TMPL_ACTION_SUCCESS' => 'Public/success',*/

    //'TMPL_ACTION_SUCCESS' => 'Public/success',
    'SESSION_OPTIONS' => array('expire' => 604800),
    'USER_AUTH_KEY'=>'userId',  // 认证识别号
    'URL_MODEL' => 0,//REWRITE模式
    'LANG_SWITCH_ON' => true,   // 开启语言包功能
    'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
    'LANG_LIST'        => 'zh-cn,en-us,ko-kr,ja-jp', // 允许切换的语言列表 用逗号分隔

    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量
    'AUTH_CONFIG' => array(
        'AUTH_GROUP' => 'role', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'detail', //用户组明细表
        'AUTH_RULE' => 'rule', //权限规则表
        'AUTH_USER' => 'admin'//用户信息表
    ),
    'APP_GROUP_LIST' => 'Home,Guds,Universal,Logistics,Coupon,Question,Oms,Warehouse', //项目分组设定
    'DEFAULT_GROUP'  => 'Home', //默认分组
    'rule_white_list' => [
        'Home' => ['Stock' => ''],
        'Guds' => ['*'],
        'Universal' => ['*'],
        'Logistics' => ['*']
    ],
    'SYS_OPERATION_LOG_SAVE_PATTERN' => 'file',
    'TIMESTAP_AUTH' => [
        'USE' => true,
        'HOUR' => 24
    ],
    'FILTER_ACTIONS'=>[
        'tpl',
    ],
    'FILTER_GLOBAL_MODEL_ACTIONS'=>[
        'oms/commondata/commondata',
        'common/index/exchange_rate',
        'common/language/index',
        'oms/order/getsite',
        'oms/order/listmenu',
        'oms/patch/listmenu',
        'oms/order/storesget',
        'home/crontab/run',
        'home/stock/pagedata',
        'home/order_detail/warehouse_list',
    ],
    'session_pre' => '',
);
//这个文件jenkins没有过滤
return array_merge($config, $db_config, $define_config);
