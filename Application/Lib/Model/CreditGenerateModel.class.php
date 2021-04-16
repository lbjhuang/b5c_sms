<?php
/**
 * 不要使用
 *
 * 加密解密
 *
 * @author Neo.Du
 * <baifeng@gshopper.com>
 */

class CreditGenerateModel extends Model
{
//不同环境设置不同的key
    public static $key = 'GSHOPPRTSTAGE';

//加密
    public static function encrypt($data)
    {
        $secret_key = self::getSecretKey('GSHOPPRT_KEY');
        $key = md5($secret_key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = null;
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key{$x};
            $x++;
        }
        $str = null;
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
        }
        return base64_encode($str);
    }

//解密
    public static function decrypt($data)
    {
        $secret_key = self::getSecretKey('GSHOPPRT_KEY');
        $key = md5($secret_key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        $char = null;
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = null;
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }

    /**
     * @param $secret_key
     */
    private static function getSecretKey($secret_key)
    {
        $key = C($secret_key);
        return $key;
    }
}