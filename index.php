<?php
require './Application/Common/syncsession.php';

// 维护模式
$maintenance_mode = @file_get_contents('/opt/logs/logstash/erp/maintenance.ini');
if ('true' == $maintenance_mode && 'm=Maintenance&a=off' != $_SERVER['QUERY_STRING']) {
    header("Location: http://{$_SERVER ['HTTP_HOST']}/maintenance.php");
    die();
}

if (ini_get('magic_quotes_gpc')) {
    function stripslashesRecursive(array $array){
        foreach ($array as $k => $v) {
            if (is_string($v)){
                $array[$k] = stripslashes($v);
            } else if (is_array($v)){
                $array[$k] = stripslashesRecursive($v);
            }
        }
        return $array;
    }
    $_GET = stripslashesRecursive($_GET);
    $_POST = stripslashesRecursive($_POST);
    $_REQUEST = stripslashesRecursive($_REQUEST);
}
ini_set('date.timezone','Asia/Shanghai');
define ( 'APP_NAME', 'Application' );
define( 'APP_SITE', getcwd());
define ( 'APP_PATH', APP_SITE.'/Application/' );
define ( 'APP_DEBUG', true );
define ( 'LOCAL_SESSION', APP_SITE.'/Application/JsonData/local_session.json' );//用于本地运行的固定session（避开同步session带来的性能损耗）
//define ( 'V', date('YmdHi') );
$split_server = substr($_SERVER['SERVER_ADDR'],0,4);
$_ENV["NOW_STATUS"] = 'stage';
if (substr(php_sapi_name(), 0, 3) == 'cli') {
    $depr = '/';
    $path = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:'';

    if(!empty($path)) {
        $params = explode($depr, trim($path,$depr));
    }
    //!empty($params)?$_GET['g']=array_shift($params):"";
    !empty($params)?$_GET['m'] = array_shift($params):"";
    !empty($params)?$_GET['a'] = array_shift($params):"";
    if(count($params)>1) {
        // 解析剩余参数 并采用GET方式获取
        @preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',$params));
    }
    if ('b5c.sms2.com'== $_SERVER ['HTTP_HOST'])
    {
        require APP_PATH.'Conf/stage.php';
    } elseif ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST'] || '10.8' == $split_server)
    {
        $_ENV["NOW_STATUS"] = 'online';
        require APP_PATH.'Conf/online.php';
    } elseif ('erp.gshopper.prod.com'== $_SERVER ['HTTP_HOST'])
    {
        require APP_PATH.'Conf/prod.php';
        $_ENV["NOW_STATUS"] = 'prod';

    } elseif ('erp.gshopper.stage.com'== $_SERVER ['HTTP_HOST'] || 'erpstage.gshopper.com'== $_SERVER ['HTTP_HOST'])
    {
        require APP_PATH.'Conf/stage.php';
        $_ENV["NOW_STATUS"] = 'stage';
    } else
    {
        require APP_PATH.'Conf/stage.php';
        $_ENV["NOW_STATUS"] = 'local';
    }
} else {
    /**
     * 环境设置
     */
    if ('b5c.sms2.com'== $_SERVER ['HTTP_HOST'])
    {
        require APP_PATH.'Conf/stage.php';
    } elseif ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST'] || '10.8' == $split_server  )
    {
        $_ENV['NOW_STATUS'] = 'online';
        require APP_PATH.'Conf/online.php';
    } elseif ('erp.gshopper.prod.com'== $_SERVER ['HTTP_HOST'])
    {
        $_ENV['NOW_STATUS'] = 'prod';
        require APP_PATH.'Conf/prod.php';
    } elseif ('erp.gshopper.stage.com'== $_SERVER ['HTTP_HOST'] || 'erpstage.gshopper.com'== $_SERVER ['HTTP_HOST'])
    {
        require APP_PATH.'Conf/stage.php';
        $_ENV["NOW_STATUS"] = 'stage';
    } else
    {
        require APP_PATH.'Conf/stage.php';
        $_ENV["NOW_STATUS"] = 'local';
    }
}

require './ThinkPHP/Extend/Vendor/autoload.php';
require './ThinkPHP/ThinkPHP.php';
