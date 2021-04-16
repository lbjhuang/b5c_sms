<?php
//本地环境根据是否有local_session.json文件判断是否执行如下行为
if ($_ENV['NOW_STATUS'] != 'local' && !is_file(LOCAL_SESSION)) {
    return array(
        // 添加下面一行定义即可
        'app_begin' => array('SyncSessionPull', 'CheckLang', 'CustomMongoDbLogger'),
        'app_end' => array('SYSOperationLog', 'SyncSessionPush')
    );
} else {
    return array(
        'app_begin' => array('CheckLang', 'CustomMongoDbLogger'),
        'app_end' => array('SYSOperationLog')
    );
}
