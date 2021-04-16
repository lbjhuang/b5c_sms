<?php

/**
 * User: yangsu
 * Date: 17/11/16
 * Time: 10:45
 */
class RedisModel extends Model
{
    /**
     * @var
     */
    public static $client;

    /**
     * RedisModel constructor.
     */
    public function __construct()
    {

    }

    /**
     * @return \Predis\Client
     */
    public static function connect_init()
    {
        /*$redis = new \Redis;
        if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') {
            $redis->connect('127.0.0.1', '6379');
        } else {
            $redis->connect(REDIS_IP, REDIS_PORT);
        }*/
        $redis = self::client();
        return $redis;
    }

    /**
     * @return \Predis\Client
     */
    public static function client()
    {
        if (!self::$client) {
            $options      = ['cluster' => 'redis'];
            $servers      = C('REDIS_SERVER');
            self::$client = new Predis\Client($servers, $options);
        }
        return self::$client;
    }

    /**
     * @param $key
     * @param int $expire
     * @param bool $enable_compatible
     * @param null $conn
     *
     * @return bool
     */
    public static function lock($key, $expire = 10, $enable_compatible = false, $conn = null)
    {
        if (empty($conn)) {
            $conn = self::client();
        }
        if ($conn->ttl($key) == -1) {
            //没有设置锁过期时间，删除重新设置（兼容历史异常数据）
            $conn->del($key);
        }
        $is_lock   = $conn->setnx($key, time() + $expire);
        $lock_time = $conn->get($key);
        if ($is_lock) {
            $conn->expire($key, $expire);//设置锁的自动过期时间
            if (time() > $lock_time) {
                self::unlock($conn, $key);
                return true;
            }
        } elseif (true === $enable_compatible) {
            if ($lock_time) {
                return false;
            } else {
                $expire  += time();
                $is_lock = $conn->set($key, 0, 'EX', $expire);
            }
        }
        return $is_lock ? true : false;
    }

    public static function compatibleLock($key, $expire = 60, $conn = null)
    {
        $is_lock = false;
        if (empty($conn)) {
            $conn = self::client();
        }
        $lock_time = $conn->get($key);
        if (isset($lock_time)) {
            return false;
        } else {
            $expire  += time();
            $is_lock = $conn->set($key, $expire, 'EX', $expire);
        }
        return $is_lock ? true : false;
    }

    /**
     * @param $conn
     * @param $key
     *
     * @return mixed
     */
    public static function unlock($key)
    {
        return self::client()->del($key);
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return mixed
     */
    public static function get_key_api($key, $redis = null)
    {
        $host_url = HOST_URL;
        if ($host_url == 'HOST_URL') $host_url = 'http://b5caiapi.stage.com';
        $url      = $host_url . '/public/getValueFromRedis.json?key=' . $key;
        $res_json = curl_request($url);
        $res      = json_decode($res_json, true);
        return $res['data'];
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return mixed
     */
    public static function get_key_plat_api($key, $redis = null)
    {
        $url = HOST_S_URL . '/check/getRedisValueByKey.json?key=' . $key;
        Logs($url, '$url', 'url');
        $res_json = curl_request($url);
        $res      = json_decode($res_json, true);
        return json_decode($res['value']);
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return mixed
     */
    public static function get_key_es_api($key, $redis = null)
    {
        $url = HOST_S_URL . '/s/b5c/batchStock?skuId=' . $key;
        Logs($url, '$url', 'url');
        $res_json = curl_request($url);
        Logs($res_json, '$res_json', 'url');
        $res = json_decode($res_json, true);
        Logs($res, '$res', 'url');
        $res_warehouse = array_column($res['data']['batchStock'], 'deliveryWarehouse');
        Logs($res_warehouse, '$res_warehouse', 'url');
        return $res_warehouse;
    }

    /**
     * @param      $key_arr
     * @param null $redis
     *
     * @return array
     */
    public static function get_key_list_api($key_arr, $redis = null)
    {
        foreach ($key_arr as $val) {
            $res[] = self::get_key_es_api($val);
        }
        return $res;
    }

    /**
     * @param      $key_arr
     * @param null $redis
     *
     * @return array
     */
    public static function get_key_list($key_arr, $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->getMultiple($key_arr);
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return bool|string
     */
    public static function get_key($key, $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->get($key);
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return string
     */
    public static function get_keys($key, $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->keys($key);
    }

    /**
     * @param        $key
     * @param        $value
     * @param null $redis
     * @param int $timeout
     * @param string $expireResolution
     *
     * @return mixed
     */
    public static function set_key($key, $value, $redis = null, $timeout = null, $expireResolution = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        if ($timeout === -1) {
            return $redis->set($key, $value);
        }
        if (empty($timeout)) {
            $timeout = 3600;
        }
        if ($timeout) {
            if (!$expireResolution) $expireResolution = 'EX';
//            $timeout += time();
            return $redis->set($key, $value, $expireResolution, $timeout);
        }
    }

    /**
     * @param $key
     * @param $redis
     *
     * @return int
     */
    public static function del_key($key, $redis)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->del($key);
    }

    /**
     * @param null $redis
     */
    public static function close($redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->close();
    }

    /**
     * @return string
     */
    public static function getLastError($redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->getLastError();
    }

    /**
     * @param $key
     * @param array $static_call_back_func_arr 回调必须静态函数，且通过 func_get_args 接收索引数组
     * @param null $redis_conn
     *
     * @return bool|false|mixed|string
     */
    public static function getKeyOrStaticCallback($key, array $static_call_back_func_arr, $redis_conn = null)
    {
        if (!$redis_conn) {
            $redis_conn = self::connect_init();
        }
        $resource = self::get_key($key, $redis_conn);
        if (!$resource) {
            $call_back_data = forward_static_call_array($static_call_back_func_arr, [$key]);
            if (is_array($call_back_data) || is_object($call_back_data)) {
                $call_back_data = json_encode($call_back_data);
            }
            RedisModel::set_key($key, $call_back_data, $redis_conn, 3600);
            $resource = $call_back_data;
        }
        return $resource;
    }

    /**
     * @return false|string
     */
    public static function testGetRedisValueByKeyAPI()
    {
        $res['msg'] = 'success';
        $rand       = rand(0, 3);
        switch ($rand) {
            case 0:
                $res['value'] = ['N000680300'];
                break;
            case 1:
                $res['value'] = ['N000680300', 'N000680100'];
                break;
            case 2:
                $res['value'] = ['N000680100'];
                break;
            case 3:
                $res['value'] = ['N000680200', 'N000680100'];
                break;
            default:
                $res['value'] = ['N000680200'];
        }
        return json_encode($res);
    }

    public static function push($key, $value, $path = 'right', $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        if ($path == 'left') {
            return $redis->lPush($key, $value);
        } else {
            return $redis->rPush($key, $value);
        }
    }

    public static function pop($key,$path='right', $redis = null){
        $redis = $redis ? $redis : self::connect_init();
        if($path == 'left' ){
            return $redis->lPop($key);
        } else{
            return $redis->rPop($key);
        }
    }


    /**
     * @param      $key
     * @param      $field
     *
     * @return string
     */
    public static function hget($key, $field,$redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->hget($key, $field);
    }

    /**
     * @param      $key
     * @param      $field
     * @param      $value
     *
     * @return bool
     */
    public static function hset($key, $field,$value, $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->hset($key, $field, $value);
    }

    /**
     * @param      $key
     * @param null $redis
     *
     * @return bool|array
     */
    public static function hkeys($key, $redis = null)
    {
        $redis = $redis ? $redis : self::connect_init();
        return $redis->hkeys($key);
    }
}

/**
 * Class RedisLock
 * User: due
 * Date: 18/12/17
 * Time: 17:56
 */
class RedisLock
{
    private $keys = [];
    private static $lock;
    private $redis;

    public function __construct()
    {
        $this->redis = RedisModel::client();
    }

    public function __destruct()
    {
        self::finalUnlock();
    }

    /**
     * @return RedisLock
     */
    private static function getLock()
    {
        if (!self::$lock) {
            self::$lock = new RedisLock();
        }
        return self::$lock;
    }

    /**
     * 可重入redis锁
     *
     * @param $key string 锁key
     * @param $exp int 超时
     *
     * @return int 加锁计数
     */
    public static function lock($key, $exp = 20)
    {
        $lock = self::getLock();
        if (isset($lock->keys[$key])) {
            return $lock->redis->setex($key, $exp, ++$lock->keys[$key]);
        } else {
            $count = 10;
            do {
                $locked = $lock->redis->setnx($key, 1);
                if ($locked) {
                    $lock->redis->expire($key, $exp);
                    $lock->keys[$key] = 1;
                    return $locked;
                }
                usleep(100);
            } while (--$count > 0);
            return $locked;
        }

    }

    private static function finalUnlock()
    {
        $lock = self::getLock();
        if (!$lock->keys) return 0;
        $ret = 0;
        foreach ($lock->keys as $key => $c) {
            $ret += $lock->redis->del($key);
            unset($lock->keys[$key]);
        }
        return $ret;
    }

    /**
     * @return int
     */
    public static function unlock()
    {
        $lock = self::getLock();
        if (!$lock->keys) return 0;
        $ret = 0;
        foreach ($lock->keys as $key => &$c) {
            if (--$c === 0) {
                $ret += $lock->redis->del($key);
                unset($lock->keys[$key]);
            }
        }
        return $ret;
    }
}