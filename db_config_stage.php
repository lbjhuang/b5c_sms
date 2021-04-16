<?php

return array(
    /* 数据库设置 */
    'DB_TYPE'               => 'mysql',     // 数据库类型
    'DB_HOST'               => 'mysql.stage.com', // 服务器地址
    'DB_NAME'               => 'b5c_stage',          // 数据库名
    'DB_USER'               => 'b5c',      // 用户名
    'DB_PWD'                => 'b5c',          // 密码
    'DB_PORT'               => '',        // 端口
    'DB_PREFIX'             => 'bbm_',    // 数据库表前缀
    'DB_FIELDTYPE_CHECK'    => false,       // 是否进行字段类型检查
    'DB_FIELDS_CACHE'       => true,        // 启用字段缓存
    'DB_CHARSET'            => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE'        => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE'        => false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM'         => 1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO'           => '', // 指定从服务器序号
    'DB_SQL_BUILD_CACHE'    => false, // 数据库查询的SQL创建缓存
    'DB_SQL_BUILD_QUEUE'    => 'file',   // SQL缓存队列的缓存方式 支持 file xcache和apc
    'DB_SQL_BUILD_LENGTH'   => 20, // SQL缓存的队列长度
    'DB_SQL_LOG'            => false, // SQL执行日志记录
    'PMS_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => 'mysql.stage.com', // 服务器地址
        'DB_NAME'               => 'gshopper_pms_stage',          // 数据库名
        'DB_USER'               => 'b5c',      // 用户名
        'DB_PWD'                => 'b5c',          // 密码
        'DB_PORT'               => '',        // 端口
    ),
    'CMS_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => 'mysql.stage.com', // 服务器地址
        'DB_NAME'               => 'gshopper_cms_stage',          // 数据库名
        'DB_USER'               => 'b5c',      // 用户名
        'DB_PWD'                => 'b5c',          // 密码
        'DB_PORT'               => '',        // 端口
    ),
    'ERP_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => 'mysql.stage.com', // 服务器地址
        'DB_NAME'               => 'b5c_stage',          // 数据库名
        'DB_USER'               => 'b5c',      // 用户名
        'DB_PWD'                => 'b5c',          // 密码
        'DB_PORT'               => '',        // 端口
    ),
     'ZT_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => '10.8.6.251', // 服务器地址
        'DB_NAME'               => 'zentao_dev',          // 数据库名
        'DB_USER'               => 'zentao',      // 用户名
        'DB_PWD'                => 'zentao123',          // 密码
        'DB_PORT'               => '',        // 端口
    ),
    'DATA_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => 'mysql.stage.com', // 服务器地址
        'DB_NAME'               => 'gshopper_data_stage',          // 数据库名
        'DB_USER'               => 'b5c',      // 用户名
        'DB_PWD'                => 'b5c',          // 密码
        'DB_PORT'               => '',        // 端口
    ),
    'ERP_SLAVE_DB' =>array(
        'DB_TYPE'               => 'mysql',     // 数据库类型
        'DB_HOST'               => 'mysql.stage.com', // 服务器从库地址
        'DB_NAME'               => 'b5c_stage',          // 数据库名
        'DB_USER'               => 'b5c',      // 用户名
        'DB_PWD'                => 'b5c',          // 密码
        'DB_PORT'               => '3306',        // 端口
    ),
    'MONGODB' => [
        'DRIVER'   => 'mongodb',
        'HOST'     => '172.16.111.15',
        'PORT'     => '27017',
        'DATABASE' => 'b5c_stage',
        'USERNAME' => '',
        'PASSWORD' => '',
    ],
);

?>