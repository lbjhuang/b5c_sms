<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/18
 * Time: 16:35
 */

class TbWmsAlloProcessModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_allo_process';
    public static $identifyName = 'identify';

    protected $_auto = [
        ['uuid', 'createGuid', '1', 'callback'],
        ['state', 1],
        ['create_user_id', 'getName', '1', 'callback'],
        ['create_time', 'getTime', '1', 'callback']
    ];

    public function __construct($name = '')
    {
        parent::__construct($name);
        static::init();
    }

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

    public function createGuid($namespace = '') {
        static $guid = '';
        $uid   = uniqid("", true);
        $data  = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['LOCAL_ADDR'];
        $data .= $_SERVER['LOCAL_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash  = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid  = substr($hash,  0,  8)
            . substr($hash,  8,  4)
            . substr($hash, 12,  4)
            . substr($hash, 16,  4)
            . substr($hash, 20, 12);
        return $guid;
    }

    /**
     * 根据系统生成的 uuid 与 md5 后的 request_uri 生成页面唯一 token
     * @param string $uuid 系统生成的 uuid
     * @return string $token 唯一token
     */
    public static function buildAccessToken($uuid)
    {
        static::init();
        $key = md5($_SERVER['REQUEST_URI']);
        RedisModel::set_key(self::$identifyName . '_' . $uuid, $uuid);
        $token = $key.'_'.$uuid;
        return $token;
    }

    /**
     * @param array $data 页面请求的数据必须包含 uuid
     * @param bool $bool 是否需要清除当前流程
     * @return bool
     */
    public static function checkToken($data, $bool = false) {
        list($key, $uuid)  =  explode('_',$data['token']);
        $token = RedisModel::get_key(self::$identifyName . '_' . $uuid);
        if (!isset($data ['token']) and !isset($token)) {
            return false;
        }
        if ($uuid and $token === $uuid) {
            if ($bool) {
                RedisModel::del_key($data ['token']);
                return true;
            }
            return true;
        }
        return false;
    }

    /**
     * 流程验证
     * @param $params
     * @return array
     *
     */
    public static function getProcessInfo($params)
    {
        $model = new Model();
        list($key, $uuid) = explode('_', $params ['token']);
        $processInfo = null;
        if ($uuid)
            $processInfo = $model->table('tb_wms_allo_process')->where("uuid = '%s' and state = 1", [$uuid])->find();
        return $processInfo;
    }

    /**
     * 关闭流程
     *
     */
    public static function closeProcess($uuid)
    {
        $model = new Model();
        if ($uuid) {
            $ret ['state'] = self::ALLO_STATE_OFF;
            $isok = $model->table('tb_wms_allo_process')->where("uuid = '%s'", [$uuid])->save($ret);
        }
        return $isok;
    }
}