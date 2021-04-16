<?php

/**
 * User: yangsu
 * Date: 18/2/27
 * Time: 19:04
 */
class DataModel extends Model
{

    /**
     * @var array
     */
    public static $error_return = [
        'code' => 400,
        'info' => 'error',
        'msg' => 'error',
        'data' => '',
    ];

    /**
     * @var array
     */
    public static $success_return = [
        'code' => 200,
        'info' => 'success',
        'msg' => 'success',
        'data' => '',
    ];

    /**
     * @param bool $obj2arr
     * @param null $params
     *
     * @return mixed
     */
    public static function getData($obj2arr = true, $params = null)
    {
        
        $data = file_get_contents("php://input", 'r');
        if ($params) return json_decode($data, $obj2arr)[$params];
        return json_decode($data, $obj2arr);
    }

    /**
     * @param null $params
     *
     * @return mixed
     */
    public static function getDataToArr($params = null)
    {
        return self::getData($obj2arr = true, $params);
    }

    /**
     * @param null $params
     *
     * @return string|被过滤参数可为字符串、数组
     */
    public static function getDataNoBlankToArr($params = null)
    {
        return self::filterBlank(self::getDataToArr($params));
    }


    /**
     * @param        $data_arr
     * @param        $key_name
     * @param array $null_check
     * @param string $order_key
     *
     * @return array
     */
    public static function checkNull($data_arr, $key_name, $null_check = [], $order_key = 'ORDER_ID')
    {
        foreach ($data_arr as $key => $value) {
            foreach ($value as $k => $val) {
                if (empty($val) && !in_array($k, $null_check)) {
                    $res['msg'] = $key_name[$k] . L('必填项为空');
                    $res['orderId'] = $value[$order_key];
                    $error_arr[] = $res;
                    unset($data_arr[$key]);
                }
            }

//            // 验证站点CODE
//            if (!CodeModel::getValue($value['PLAT_CD'])){
//                $res['msg'] = L('站点CODE异常');
//                $res['orderId'] = $value[$order_key];
//                $error_arr[] = $res;
//                unset($data_arr[$key]);
//            }

            //加锁//待派单物流编辑
            if ($value['ORDER_ID'] && $value['PLAT_CD'] && !RedisLock::lock($value['ORDER_ID'] . '_' . $value['PLAT_CD'], 30)) {
                $res['msg'] = L('订单锁获取失败');
                $res['orderId'] = $value[$order_key];
                $error_arr[] = $res;
                unset($data_arr[$key]);
            }
        }
        //加锁//待分拣物流编辑
        $orders = (new OrderBackModel())->orders(['ordId' => array_column($data_arr, 'B5C_ORDER_ID')]);
        foreach ($data_arr as $v) {
            if (!RedisLock::lock($value['orderId'] . '_' . $value['platCd'], 30)) {
                $res['msg'] = L('订单锁获取失败');
                $res['orderId'] = $value[$order_key];
                $error_arr[] = $res;
                unset($data_arr[$key]);
            }
        }
        if (!isset($error_arr)) $error_arr = array();
        return [$data_arr, $error_arr];
    }

    /**
     * @param      $data_arr
     * @param      $error_arr
     * @param int $orderlist_success
     * @param null $type
     *
     * @return mixed
     */
    public static function showRetrunJoin($data_arr, $error_arr, $orderlist_success = 0, $error_arr_null = null)
    {
        if (empty($error_arr)) {
            $body['message_orders'] = count($data_arr);
        } else {
            $body['message_orders'] = count($data_arr) + count($error_arr);
        }
        $body['orderlist_num'] = $body['message_orders'];
        $body['orderlist_false'] = count($error_arr);
        $body['orderlist_success'] = count($orderlist_success);
        $body['message_orders'] = $error_arr;
        if (1 == $body['orderlist_num']) {
            $res['msg'] = $body['message_orders'][0]['msg'];
        }
        $res['body'] = $body;
        return $res;
    }

    /**
     * @param        $date
     * @param string $format
     *
     * @return bool
     */
    public static function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public static function isJson($string)
    {
        if (is_array($string) || is_object($string) || is_null($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function obj2arr($data)
    {
        return json_decode(json_encode($data), true);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function arr2obj($data)
    {
        return json_decode(json_encode($data));
    }

    /**
     * @param $data
     *
     * @return array
     */
    public static function toArray($data)
    {
        if (empty($data) || !is_array($data)) {
            return (array)null;
        }
        return (array)$data;
    }

    /**
     * @param $data
     *
     * @return object
     */
    public static function toObject($data)
    {
        if (empty($data) || !is_object($data)) {
            return (object)null;
        }
    }

    /**
     * @param $data
     * @param $changes_arr
     *
     * @return mixed
     */
    public static function forceToArr($data, $changes_arr)
    {
        foreach ($changes_arr as $change) {
            if (!is_array($data[$change])) {
                $data[$change] = (array)null;
            }
        }
        return $data;
    }


    /**
     * @param $array
     * @param $key
     *
     * @return array|bool
     */
    public static function upDimension($array, $key)
    {
        $all_keys = array_column($array, $key);
        if (count($all_keys) == count(array_values($array))) {
            return array_combine($all_keys, $array);
        }
        return [];
    }

    /**
     * @param $data
     *
     * @return Generator
     */
    public static function toYield($data)
    {
        foreach ($data as $value) {
            yield $value;
        }
    }

    /**
     * @param $data
     *
     * @return string|被过滤参数可为字符串、数组
     */
    public static function filterBlank($data)
    {
    
        return ZUtils::filterBlank($data);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function jsonToArr($data)
    {
        if (is_array($data)) {
            return $data;
        }
        return json_decode($data, true);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function arrToJson($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed
     */
    public static function userNamePinyin()
    {
        return session('m_loginname');
    }

    public static function userHuaMing()
    {
        return $_SESSION['m_login_huaming'];
    }

    /**
     * @return mixed
     */
    public static function userId()
    {
        return session('user_id');
    }

    /**
     * @param $data
     * @param null $key
     *
     * @return float|int
     */
    public static function percentageToDecimal($data, $key = null)
    {
        if (!$key && is_string($data)) {
            return self::toDecimal($data);
        }
        if ($key && is_array($data)) {
            foreach ($data as &$datum) {
                $datum[$key . '_decimal'] = self::toDecimal($datum[$key]);
            }
        }
        return $data;
    }

    /**
     * @param $data
     *
     * @return float|int
     */
    private static function toDecimal($data)
    {
        return str_replace('%', '', $data) / 100;
    }

    /**
     * @param $uncamelized_words
     * @param string $separator
     *
     * @return string
     */
    public function camelize($uncamelized_words, $separator = '_')
    {
        $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
    }

    /**
     * @param $request_data
     * @param $maps
     * @param null $like_keys
     *
     * @return array
     */
    public static function joinDbWheres($request_data, $maps, $like_keys = null)
    {
        $wheres = [];
        foreach ($request_data as $key => $value) {
            if ($value && $maps[$key]) {
                if (in_array($key, $like_keys)) {
                    $wheres[$maps[$key]] = ['LIKE', "%{$value}%"];
                } else {
                    $wheres[$maps[$key]] = $value;
                }
            }
        }
        return $wheres;
    }

    /**
     * @param $str
     *
     * @return mixed
     */
    public static function cleanLineFeed($str)
    {
        return str_replace(PHP_EOL, ' ', $str);
    }

    public static function getUserNameById($id)
    {
        return M('admin', 'bbm_')->where(['M_ID' => $id])->cache(true, 3)->getField('M_NAME');
    }

    public static function getUserScNameById($id)
    {
        return M('admin', 'bbm_')->where(['M_ID' => $id])->getField('EMP_SC_NM');
    }

    public static function ajaxReturn($data, $type = '', $options = null)
    {
        if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $json_encode = json_encode($data, $options);
                exit($json_encode);
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[C('VAR_JJSON_NUMERIC_CHECK SONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler . '(' . json_encode($data) . ');');
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :
                // 用于扩展其他返回格式数据
                tag('ajax_return', $data);
        }
    }

    /**
     * @param $data
     * @param int $digit
     * @param bool $is_traversing
     *
     * @return array
     */
    public static function toNumberFormat($data, $digit = 2, $is_traversing = false)
    {
        $data = array_map(function ($value) use ($digit, $is_traversing) {
            if (is_numeric($value)) {
                $value = number_format($value, $digit);
            }
            if ($is_traversing && is_array($value)) {
                $value = self::toNumberFormat($value, $digit, $is_traversing);
            }
            return $value;
        }, $data);
        return $data;
    }

    /**
     * @param $data
     *
     * @return false|string
     */
    public static function toString($data)
    {
        if (is_string($data)) {
            return $data;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function emailToUser($data)
    {
        if (is_string($data)) {
            if (false !== strstr($data, '@')) {
                $data = explode('@', $data)[0];
            }
        }
        if (is_array($data)) {
            foreach ($data as &$datum) {
                if (false !== strstr($data, '@')) {
                    $datum = explode('@', $datum)[0];
                }
            }
        }
        return $data;
    }

    public static function userToEmail($data)
    {
        if (is_string($data)) {
            $data = $data . '@gshopper.com';
        }
        if (is_array($data)) {
            foreach ($data as &$datum) {
                $data = $data . '@gshopper.com';
            }
        }
        return $data;
    }

    public static function unking($data)
    {
        return str_replace(',', '', $data);
    }

    //格式化数组金额（用千分位分割金额）
    public static function formatAmount($data, $decimals = 2)
    {
        $result = [];
        foreach ($data as $key => $item) {
            if (is_numeric($item) && preg_match('/\.+/', '\'' . $item . '\'')) {
                $item = number_format($item, $decimals);
            }
            if (is_array($item)) {
                $item = self::formatAmount($item, $decimals);
            }
            $result[$key] = $item;
        }
        return $result;
    }

    // 判断是否是正整数
    public static function checkPositiveInteger($num)
    {
        if (preg_match("/^[1-9][0-9]*$/" ,$num)){
          return true;
        }else{
          return false;
        }
    }

    /**
     * 获取所有正常在使用的用户信息
     */
    public static function getNormalUser($field)
    {
        $where['IS_USE'] = 0;
        $where['M_STATUS'] = array('NEQ', 2);
        $data = M('admin', 'bbm_')->field($field)->where($where)->select();
        return $data;
    }

    /**
     * @param $userName
     *
     * @return mixed
     */
    public static function getUserIdByName($userName)
    {
        return M('admin', 'bbm_')->where(['M_NAME' => $userName])->getField('M_ID');
    }

}