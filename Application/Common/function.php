<?php
/**
 * Created by PhpStorm.
 * User: muzhitao
 * Date: 2016/4/7
 * Time: 21:17
 */
define('CURL_INFO');
function genCate($data, $pid = 0, $level = 0)
{
    $l = str_repeat("&nbsp;&nbsp;├", $level);
    $l = $l . '';
    static $arrcat = array();
    $arrcat = empty($level) ? array() : $arrcat;
    foreach ($data as $k => $row) {
        $row['showName'] = str_repeat("&nbsp;&nbsp;-&nbsp;&nbsp;", $row['LEVEL'] - 1) . $row['NAME'];
        if ($row['PID'] == $pid) {
            if ($pid == 0) {
                $row['value'] = $row['TITLE'];
                $row['level'] = $level;
                $row['disable'] = 1;
                $arrcat[] = $row;
            } else {
                $row['value'] = $l . $row['TITLE'];
                $row['level'] = $level;
                $row['disable'] = 0;
                $arrcat[] = $row;
            }

            genCate($data, $row['ID'], $level + 1);
        }
    }
    return $arrcat;
}

function get_supply_info()
{
    $str = '';
    $query_str = $_SERVER['QUERY_STRING'];
    parse_str($query_str, $query_arr); // 用于登录后跳到指定的erp页面，场景比如点击合同到期提醒邮件的查看，跳到合同详情页
    foreach ($query_arr as $key => $value) {
        if ($key !== 'm' && $key !== 'a') {
            $str .= '&' . $key . '=' . $value;
        }
    }
    if ($str) {
        $str .= '&type=first';
    }
    return $str;
}

function genCates($data, $pid = 1, $level = 0)
{
    if ($level < 2) {
        $l = str_repeat("&nbsp;&nbsp;├", $level);
    } else {
        $l = str_repeat("&nbsp;&nbsp;", $level);
    }

    $l = $l . '';
    static $arrcata = array();
    $arrcata = empty($level) ? array() : $arrcata;
    foreach ($data as $k => $row) {

        if ($row['PID'] == $pid) {
            if ($pid == 0) {
                $row['value'] = $row['NAME'];
                $row['level'] = $level;
                $row['disable'] = 1;
                $arrcata[] = $row;
            } else {
                $row['value'] = $l . $row['NAME'];
                $row['level'] = $level;
                $row['disable'] = 0;
                $arrcata[] = $row;
            }

            genCates($data, $row['ID'], $level + 1);
        }
    }
    return $arrcata;
}

function curl_get_json($url, $post, $cookie = '', $timeout = 60)
{
    $ch = curl_init($url); //请求的URL地址
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);          //单位 秒，也可以使用
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);//$data JSON类型字符串
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($post)));
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $data = curl_exec($ch);
    return $data;
}


function curl_get_json_time($url, $post, $time = 60)
{
    $ch = curl_init($url); //请求的URL地址
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time);          //单位 秒，也可以使用
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);//$data JSON类型字符串
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($post)));
    $data = curl_exec($ch);
    return $data;
}

function curl_get_json_get($url, $post)
{
    $ch = curl_init($url); //请求的URL地址
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);//$data JSON类型字符串
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($post)));
    $data = curl_exec($ch);
    return $data;
}


// curl 访问内部接口
//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
function curl_request($url, $post = '', $cookie = '', $returnCookie = 0, $time_out = 60)
{
    ini_set('max_input_nesting_level', 500);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://XXX");

    if ($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    if ($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);

    curl_setopt($curl, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    define('CURL_INFO', curl_getinfo($curl, CURLINFO_HTTP_CODE));
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $info;
    } else {
        return $data;
    }
}

function encode($string = '', $skey = 'cxphp')
{
    $strArr = str_split(base64_encode($string));
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key < $strCount && $strArr[$key] .= $value;
    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
}

function decode($string = '', $skey = 'cxphp')
{
    $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
    return base64_decode(join('', $strArr));
}

if (!function_exists('array_column')) {
    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                         a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                         the returned array. This value may be the integer key
     *                         of the column, or it may be the string key name.
     *
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();
        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }
        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }
        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string)$params[1] : null;
        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int)$params[2];
            } else {
                $paramsIndexKey = (string)$params[2];
            }
        }
        $resultArray = array();
        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;
            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string)$row[$paramsIndexKey];
            }
            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }
            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }
        return $resultArray;
    }
}

if (!function_exists('get_client_browser')) {
    /**
     * 获取客户端浏览器类型
     *
     * @param string $glue 浏览器类型和版本号之间的连接符
     *
     * @return string|array 传递连接符则连接浏览器类型和版本号返回字符串否则直接返回数组 false为未知浏览器类型
     */
    function get_client_browser($glue = null)
    {
        $browser = array();
        $agent = $_SERVER['HTTP_USER_AGENT']; //获取客户端信息

        /* 定义浏览器特性正则表达式 */
        $regex = array(
            'ie' => '/(MSIE) (\d+\.\d)/',
            'chrome' => '/(Chrome)\/(\d+\.\d+)/',
            'firefox' => '/(Firefox)\/(\d+\.\d+)/',
            'opera' => '/(Opera)\/(\d+\.\d+)/',
            'safari' => '/Version\/(\d+\.\d+\.\d) (Safari)/',
        );
        foreach ($regex as $type => $reg) {
            preg_match($reg, $agent, $data);
            if (!empty($data) && is_array($data)) {
                $browser = $type === 'safari' ? array($data[2], $data[1]) : array($data[1], $data[2]);
                break;
            }
        }
        return empty($browser) ? false : (is_null($glue) ? $browser : implode($glue, $browser));
    }
}

function format_for_currency($str, $len = 2)
{
    if (!$str) return '0.00';
    list($r, $x) = explode('.', $str);
    if (!$x) {
        for ($i = 0; $i < $len; $i++) {
            $x .= '0';
        }
    } else {
        $x = substr($x, 0, $len);
    }
    $r = strrev($r);
    $nr = str_split($r, 3);

    $nrL = count($nr);
    $tmp = '';
    for ($i = $nrL - 1; $i >= 0; $i--) {
        $tmp .= strrev($nr [$i]) . ',';
    }
    $tmp = rtrim($tmp, ',');
    return $tmp . '.' . $x;
}

function create_guid($namespace = '')
{
    static $guid = '';
    $uid = uniqid("", true);
    $data = $namespace;
    $data .= $_SERVER['REQUEST_TIME'];
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['LOCAL_ADDR'];
    $data .= $_SERVER['LOCAL_PORT'];
    $data .= $_SERVER['REMOTE_ADDR'];
    $data .= $_SERVER['REMOTE_PORT'];
    $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
    $guid = substr($hash, 0, 8)
        .
        substr($hash, 8, 4)
        .
        substr($hash, 12, 4)
        .
        substr($hash, 16, 4)
        .
        substr($hash, 20, 12);
    return $guid;
}

function cutting_time($time)
{
    if (!$time) return;
    $unixTime = strtotime($time);
    return date('Y-m-d', $unixTime);
}

function cutting_times($time)
{
    if (!$time) return;
    $unixTime = strtotime($time);
    return date('Y-m-d H:i:s', $unixTime);
}

function cutting_timess($time)
{
    if (!$time) return;
    $unixTime = strtotime($time);
    return date('H:i', $unixTime);
}

function cutting_hour($time)
{
    if (!$time) return;
    $unixTime = strtotime($time);
    return date('H:i:s', $unixTime);
}

function exchangeRate($currency, $date = '')
{
    if ($currency == 'RMB') {
        return 1;
    }
    if (empty($currency)) {
        return false;
    }
    $date == '' ? $date = date('Y-m-d') : '';
    // $url = INSIGHT."/insight-backend/external/exchangeRate?date=$date&dst_currency=CNY&src_currency=$currency";
    $url = BI_API_REVEN . '/external/exchangeRate?date=' . $date . '&dst_currency=CNY&src_currency=' . $currency;
    $data = json_decode(file_get_contents($url), true);
    if ($data['success'] == true && $data['data'][0]['rate']) {
        return $data['data'][0]['rate'];
    } else {
        return false;
    }
}

function exchangeRateToUsd($currency, $date = '')
{
    $redis = RedisModel::connect_init();
    $currency = strtolower($currency);
    $date = $date ?: date('Ymd');
    $rates = json_decode($redis->get('xchr_' . $date), true);
    if (empty($rates)) {
        $Datetime = new Datetime($date);
        $reduce_date = $Datetime->modify("-1 day")->format("Ymd");
        $rates = json_decode($redis->get('xchr_' . $reduce_date), true);
    }
    switch ($currency) {
        case 'cny' :
            $rate = 1 / $rates['usdXchrAmtCny'];
            break;
        case 'usd' :
            $rate = 1;
            break;
        default:
            $rate = $rates[$currency . 'XchrAmtCny'] / $rates['usdXchrAmtCny'];
            break;
    }
    return $rate;
}

function exchangeRateToCNY($currency, $date = '')
{
    $redis = RedisModel::connect_init();
    $currency = strtolower($currency);
    $date = $date ?: date('Ymd');
    $rates = json_decode($redis->get('xchr_' . $date), true);
    if (empty($rates)) {
        $Datetime = new Datetime($date);
        $reduce_date = $Datetime->modify("-1 day")->format("Ymd");
        $rates = json_decode($redis->get('xchr_' . $reduce_date), true);
    }
    switch ($currency) {
        case 'cny' :
            $rate = 1;
            break;
        default:
            $rate = $rates[$currency . 'XchrAmtCny'];
            break;
    }
    return $rate;
}

function exchangeRateConversion($from_currency, $to_currency, $date = '')
{
    $from_rate = exchangeRateToCNY($from_currency, $date);
    $to_rate = exchangeRateToCNY($to_currency, $date);
    $rate = bcdiv($from_rate, $to_rate, 8);
    return $rate;
}

function getCodeValStr($cdStr)
{
    $cdStrNew = '';
    if (empty($cdStr)) {
        return $cdStr;
    }
    if (strstr($cdStr, ',')) {
        $cdStrArr = explode(',', $cdStr);
        foreach ($cdStrArr as $key => $value) {
            $cdStrNew .= cdVal($value) . ',';
        }
        $cdStrNew = rtrim($cdStrNew, ',');
    } else {
        $cdStrNew = cdVal($cdStr);
    }
    return $cdStrNew;
}

function cdVal($cd)
{
    if (empty($cd)) {
        return null;
    }
    $model = TbMsCmnCdModel::getInstance();
    return $model->cache(true, 300)->where(['CD' => $cd])->getField('CD_VAL');
}

function valCd($val)
{
    $model = TbMsCmnCdModel::getInstance();
    return $model->cache(true, 300)->where(['CD_VAL' => $val])->getField('CD');
}

function getHostInfo()
{
    $hostInfo = null;
    if ($hostInfo === null) {
        $secure = getIsSecureConnection();
        $http = $secure ? 'https' : 'http';
        if (isset($_SERVER['HTTP_HOST'])) {
            $hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
            $port = $secure ? getSecurePort() : $this->getPort();
            if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                $hostInfo .= ':' . $port;
            }
        }
    }

    return $hostInfo;
}

function getSecurePort()
{
    $secirePore = getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
    return $secirePore;
}

function getPort()
{
    $port = !getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
    return $port;
}

function getUrl()
{
    if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
        $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $requestUri = $_SERVER['REQUEST_URI'];
        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
        }
    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
        $requestUri = $_SERVER['ORIG_PATH_INFO'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }
    } else {
        throw new InvalidConfigException('Unable to determine the request URI.');
    }

    return $requestUri;
}

function getSameWeekLast($date = null)
{
    foreach ($date as $v) {

        $yearnow = date('Y');  //年
        $monthnow = date('m');  //月
        $timenow = time();
        $time = strtotime($v);
        $yearface = date('Y', $time);
        $monthface = date('m', $time);
        $weeknow = date('w');
        //echo $weeknow;
        $day = date('d');
        $dayface = date('d', $time);
        $distance = date('d', $time) - date('d');
        if ($yearnow == $yearface && $monthface == $monthnow && $timenow < $time && $distance <= 7 - $weeknow && $distance >= 0) {  //需求时间
            //echo date('d',$time);die;
            $resDate[] = $v;
        } elseif ($day > 24 && $day <= 31) {
            if ($dayface > 24) {
                $distance = $dayface - $day;
            } else {
                $distance = $dayface + (31 - $day);
            }
            if ($yearnow == $yearface && ($monthface == $monthnow || $monthface - 1 == $monthnow) && $timenow < $time && $distance <= 7 - $weeknow && $distance >= 0) {
                $resDate[] = $v;
            }
        }

    }

    return $resDate;
}

//excel导入日期格式处理  huanzhu
function excelTime($date, $time = false)
{
    if (function_exists('GregorianToJD')) {
        if (is_numeric($date)) {
            $jd = GregorianToJD(1, 1, 1970);
            $gregorian = JDToGregorian($jd + intval($date) - 25569);
            $date = explode('/', $gregorian);
            $date_str = str_pad($date [2], 4, '0', STR_PAD_LEFT)
                . "-" . str_pad($date [0], 2, '0', STR_PAD_LEFT)
                . "-" . str_pad($date [1], 2, '0', STR_PAD_LEFT)
                . ($time ? " 00:00:00" : '');
            return $date_str;
        }
    } else {
        $date = $date > 25568 ? $date + 1 : 25569;
        /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
        $ofs = (70 * 365 + 17 + 3) * 86400;
        $date = date("Y-m-d", ($date * 86400) - $ofs) . ($time ? " 00:00:00" : '');
    }
    return $date;
}


/**
 * @param $data
 * @param $function_name
 * @param $file_name
 */
function Logs()
{
    LogsModel::raise_args(func_get_args());
}

/**
 * 数据压缩
 * 序列化->Base64
 *
 * @param $data
 *
 * @return string $data
 */
function compressData($data)
{
    $data = serialize($data);
    $data = base64_encode($data);
    return $data;
}

/**
 * 数据解压
 * Base64->序列化
 *
 * @param string $data
 *
 * @return array
 */
function unCompressData($data)
{
    $data = base64_decode($data);
    $data = unserialize($data);
    return $data;
}

function isMobile()
{
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset ($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    return false;
}

function bankAccountFormat($number)
{
    return trim(preg_replace('/(\d{4})/', '$1 ', (string)$number));
}

function format_number($number, $decimals = 4)
{
    return number_format($number, $decimals, '.', ',');
}

/**
 * 实例化对应类
 *
 * @param       $service
 * @param array $parameters
 *
 * @return mixed
 */
function app($service, array $parameters = [])
{
    if ($parameters) {
        return new $service($parameters);
    }
    return new $service();
}

//二维数组根据key去重
function assoc_unique($arr, $key)
{
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (in_array($v[$key], $tmp_arr)) {
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    sort($arr);
    return $arr;
}

function v($data)
{
    echo '<pre>';
    var_dump($data);
    die;
}

function userName()
{
    return $_SESSION['m_loginname'];
}

function dateTime()
{
    return date('Y-m-d H:i:s');
}

//传递数据以易于阅读的样式格式化后输出
function p($data)
{
    // 定义样式
    $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data = $data ? 'true' : 'false';
    } elseif (is_null($data)) {
        $show_data = 'null';
    } else {
        $show_data = print_r($data, true);
    }
    $str .= $show_data;
    $str .= '</pre>';
    echo $str;
}

//根据当前时间戳生成毫秒级
function getMsec()
{
    list($s1, $s2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

function array_column_key($arr, $key)
{
    $columns = array_column($arr, $key);
    return array_combine($columns, $arr);
}

function vd($data)
{
    var_dump($data);
    Logs($data, __FUNCTION__, __FUNCTION__);
    die();
}

function isLocalEnv()
{
    if ($_ENV['NOW_STATUS'] == 'local') {
        return true;
    }
    return false;
}

//用于权限判断
function checkPermissions($action, $function)
{
    return (new BbmNodeModel())->checkPermissions($action, $function);
}

//数组转xml格式
function arrayToXml($data, &$xml_data)
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }
            $sub_node = $xml_data->addChild($key);
            arrayToXml($value, $sub_node);
        } else {
            $xml_data->addChild("$key", "$value");
        }
    }
}

if (!function_exists('random_bytes')) {
    function random_bytes($length = 6)
    {
        $characters = '0123456789';
        $characters_length = strlen($characters);
        $output = '';
        for ($i = 0; $i < $length; $i++)
            $output .= $characters[rand(0, $characters_length - 1)];

        return $output;
    }
}

//csv格式字符串转数组
function csvStringToArray($csv_string, $length = 1, $delimiter = ",")
{
    if (empty($csv_string) || !is_string($csv_string)) {
        return [];
    }
    $csv_arr = str_getcsv($csv_string, $delimiter);
    return array_chunk($csv_arr, $length) ?: [];
}

//中英混合字符串长度判断（返回字符数）
function strLength($str, $charset = 'utf-8')
{
    if ($charset == 'utf-8') {
        $str = iconv('utf-8', 'gb2312', $str);
    }
    $num = strlen($str);
    $cn_num = 0;
    for ($i = 0; $i < $num; $i++) {
        if (ord(substr($str, $i + 1, 1)) > 127) {
            $cn_num++;
            $i++;
        }
    }
    $en_num = $num - ($cn_num * 2);
    return $en_num + $cn_num;
}

function isLoadedMonoLogDriver()
{
    return extension_loaded('mongodb') && false; 
}

function endLog($tag = 'app_end')
{
    if (APP_DEBUG) {
        trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G('app_beginStart', $tag . 'End', 6) . 's ]', '', 'INFO');
    }
}

//中英文逗号换行符空白等替换成应为逗号以便后期条件组装
function strReplaceComma($str){
    $condition_str =  preg_replace('/[\s,，\r\n]+/i', ',', $str);
    $condition_str = trim($condition_str,',');
    $condition_arr = explode(",", $condition_str);
    return $condition_arr;
}

if (!function_exists('isProductEnv')) {
    function isProductEnv()
    {
        $split_server = substr($_SERVER['SERVER_ADDR'],0,4);
        if ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST'] || '10.8' == $split_server) {
            return true;
        }
        return false;
    }

}


//科学计数法转数字
function numToStr($number) {
    if(stripos($number, 'e') === false) {
        //判断是否为科学计数法
        return $number;
    }

    if(!preg_match("/^([\\d.]+)[eE]([\\d\\-\\+]+)$/", str_replace(array(" ", ","), "", trim($number)), $matches)) {
        //提取科学计数法中有效的数据，无法处理则直接返回
        return $number;
    }

    //对数字前后的0和点进行处理，防止数据干扰，实际上正确的科学计数法没有这个问题
    $data = preg_replace(array("/^[0]+/"), "", rtrim($matches[1], "0."));
    $length = (int)$matches[2];
    if($data[0] == ".") {
        //由于最前面的0可能被替换掉了，这里是小数要将0补齐
        $data = "0{$data}";
    }

    //这里有一种特殊可能，无需处理
    if($length == 0) {
        return $data;
    }

    //记住当前小数点的位置，用于判断左右移动
    $dot_position = strpos($data, ".");
    if($dot_position === false) {
        $dot_position = strlen($data);
    }

    //正式数据处理中，是不需要点号的，最后输出时会添加上去
    $data = str_replace(".", "", $data);
    if($length > 0) {
        //如果科学计数长度大于0
        //获取要添加0的个数，并在数据后面补充
        $repeat_length = $length - (strlen($data) - $dot_position);
        if($repeat_length > 0) {
            $data .= str_repeat('0', $repeat_length);
        }
        //小数点向后移n位
        $dot_position += $length;
        $data = ltrim(substr($data, 0, $dot_position), "0").".".substr($data, $dot_position);
    } elseif($length < 0) {
        //当前是一个负数
        //获取要重复的0的个数
        $repeat_length = abs($length) - $dot_position;
        if($repeat_length > 0) {
            //这里的值可能是小于0的数，由于小数点过长
            $data = str_repeat('0', $repeat_length).$data;
        }
        $dot_position += $length;//此处length为负数，直接操作
        if($dot_position < 1) {
            //补充数据处理，如果当前位置小于0则表示无需处理，直接补小数点即可
            $data = ".{$data}";
        } else {
            $data = substr($data, 0, $dot_position).".".substr($data, $dot_position);
        }
    }

    if($data[0] == ".") {
        //数据补0
        $data = "0{$data}";
    }

    return trim($data, ".");
}
if(!function_exists('buildTree')) {

    /**
     * 无限极分类实现
     * @param  $list array 需要处理的数组
     * @param  $pk string 主id
     * @param  $pid string 父级id
     * @param  $child string 子级名称
     * @param  $root 顶级分类的值
     * @return Array
     */
     function buildTree($list, $pk='id', $pid='pid', $child='_child', $root=0){

        $tree = array();
        $packData = array();
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }

        foreach ($packData as $key=>$val){
            if($val[$pid] == $root){
                $tree[] = &$packData[$key];
            }else{
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }

        return $tree;
    }
}


function arrayMultiSort($array, $keys, $sort = SORT_DESC) {
    $keysValue = [];
    foreach ($array as $k => $v) {
        $keysValue[$k] = $v[$keys];
    }
    array_multisort($keysValue, $sort, $array);
    return $array;
}


if(!function_exists("array_only")) {
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

function str2Utf8($str) {
    $encode = mb_detect_encoding($str, array('CP936', "ASCII","GB2312","GBK",'UTF-8','BIG5'));
    if ($encode == 'UTF-8') {
        return $str;
    } elseif ($encode == 'CP936') {
        return iconv('utf-8', 'latin1//IGNORE', $str);
    } else {
        return mb_convert_encoding($str, 'UTF-8', $encode);
    }
}

if(!function_exists("sstrlen")) {
    function sstrlen($str,$charset = 'utf-8') {
        $n = 0; $p = 0; $c = '';
        $len = strlen($str);
        if($charset == 'utf-8') {
            for($i = 0; $i < $len; $i++) {
                $c = ord($str{$i});
                if($c > 252) {
                    $p = 5;
                } elseif($c > 248) {
                    $p = 4;
                } elseif($c > 240) {
                    $p = 3;
                } elseif($c > 224) {
                    $p = 2;
                } elseif($c > 192) {
                    $p = 1;
                } else {
                    $p = 0;
                }
                $i+=$p;$n++;
            }
        } else {
            for($i = 0; $i < $len; $i++) {
                $c = ord($str{$i});
                if($c > 127) {
                    $p = 1;
                } else {
                    $p = 0;
                }
                $i+=$p;$n++;
            }
        }
        return $n;
    }
}


if(!function_exists("array_get")) {

    function array_get($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return value($default);
            }

            $array = $array[$segment];
        }
        return $array;
    }
}

function getImage($url,$save_dir='',$filename='')
{
    if(empty($save_dir)){
        return false;
    }
    if(empty($filename)){
        $filename=time().rand(1000, 9999). '.jpg';
    }
    if(0!==strrpos($save_dir,'/')){
        $save_dir.='/';
    }
    //创建保存目录
    if(!is_dir($save_dir)){
        mkdir($save_dir,0777,true);
    }
    //获取远程文件所采用的方法
    $ch=curl_init();
    $timeout=300;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //这个是重点,规避ssl的证书检查。
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $img=curl_exec($ch);
    curl_close($ch);
    //文件大小
    $fp=@fopen($save_dir.$filename,'a');
    fwrite($fp,$img);
    fclose($fp);
    unset($img,$url);
    return $save_dir. $filename;
}