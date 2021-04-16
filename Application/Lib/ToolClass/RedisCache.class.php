<?php

/**
 * redis 操作类
   $cache = new RedisCache();
   $cache->mset(['DATA_PREFIX_TEST_DATA_01' => 'DATA_PREFIX_TEST_DATA_001', 'DATA_PREFIX_TEST_DATA_02' => 'DATA_PREFIX_TEST_DATA_002', 'DATA_PREFIX_TEST_DATA_03' => 'DATA_PREFIX_TEST_DATA_003']);
   $cache->set('DATA_PREFIX_TEST_DATA', [OBJECT, ARRAY]);
   $cache->mget(['DATA_PREFIX_TEST_DATA_01', 'DATA_PREFIX_TEST_DATA_02', 'DATA_PREFIX_TEST_DATA_03']);
   $cache->get('DATA_PREFIX_TEST_DATA')
 * 此配置用于局部调试
 * $defaultOptions = [
        'host' => '172.16.111.11',
        'port' => 6000,
        'timeout' => 60,
        'persistent' => false
    ];
 * 线上环境与测试环境在配置文件中写入配置如下
 * C('REDIS_HOST') host
 * C('REDIS_PORT') port
 * C('DATA_CACHE_TIMEOUT') timeout
 * C('DATA_CACHE_PREFIX') prefix
 */
class RedisCache
{
    public $cache;
    public $cacheType = 'redis';
    public $defaultOptions = null;
    
    public function __construct($defaultOptions)
    {
        $this->cache = RedisModel::connect_init();
        //$this->cache = Cache::getInstance($this->cacheType, $defaultOptions);
    }
    
    /**
     * @param $key 
     * @param $value
     * @param $expire 缓存时间,如果不设置会直接读取C('DATA_CACHE_TIME')
     */
    public function set($key, $value, $expire = null)
    {
        $this->cache->set($key, $value, $expire = null);
    }
    
    /**
     * @param String $key
     * @return String
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }
    
    /**
     * @param array $data
     * @return bool
     */
    public function mulSet(Array $data)
    {
        return $this->cache->mset($data);
    }
    
    /**
     * @param array $keys
     * @return array
     */
    public function mulGet($keys)
    {
        return $this->cache->mget($keys);
    }
    
    /**
     * 删除缓存
     * @access public
     * @param String $name 缓存变量名
     * @return bool
     */
    public function rm($name) {
        return $this->cache->rm($name);
    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     */
    public function clear() {
        return $this->cache->clear();
    }

    /**
     * @return String
     */
    public function getError()
    {
        return $this->cache->getLastError();
    }
}