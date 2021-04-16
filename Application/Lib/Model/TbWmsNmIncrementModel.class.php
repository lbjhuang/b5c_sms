<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/1/15
 * Time: 14:25
 */
class TbWmsNmIncrementModel extends BaseModel
{
    /**
     * @return mixed
     *
     */
    public static $cache;

    public static function init()
    {
        if (static::$cache) {
            return static::$cache;
        } else {
            static::$cache = new RedisCache();
            if (!is_object(static::$cache->cache)) {
                throw new Exception(L('初始化 redis 失败'));
            }
        }
    }

    /**
     * 递增序号生成，会根据传入的前缀再进行日期的组合去 redis 中查询是否存在，存在则返回 + 1，不存在则置为 0
     * @param string $prefixAllo redis key 的前缀
     * @return string $str 递增后的序号，只会返回 0-9999以内的，超出则抛出异常
     * @throws Exception 超出可递增范围抛出异常
     */
    public static function generateNo($prefixAllo)
    {
        self::init();
        $date = date('Ymd', time());
        $nm = static::$cache->cache->incr($prefixAllo . $date);
        $nm += 1;
        $len = strlen($nm);
        if ($len > 4) {
            throw new Exception(L('超出当日序号可生成的最大限制(9999)'));
        }
        $str = '';
        for ($i = $len; $i < 4; $i ++) {
            $str .= '0';
        }

        return $str .= $nm;
    }

    /**
     * 递增序号生成,自定义位数生成序列号
     * @param string $prefixAllo redis key 的前缀
     * @param string $num 生成的位数
     * @return string $str 递增后的序号，超出则抛出异常
     * @throws Exception 超出可递增范围抛出异常
     */
    public static function generateCustomNo($prefixAllo, $num = 3)
    {
        self::init();
        $date = date('Ymd', time());
        $nm = static::$cache->cache->incr($prefixAllo . $date);
        $nm += 1;
        $len = strlen($nm);
        if ($len > $num) {
            throw new Exception(L('超出当日序号可生成的最大限制'));
        }
        $str = '';
        for ($i = $len; $i < $num; $i ++) {
            $str .= '0';
        }

        return $str .= $nm;
    }
}