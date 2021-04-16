<?php
/**
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/5/15
 * Time: 16:38
 */

class FtpModel extends Model
{
    static $client;
    static $local_save_path;
    static $connection_id;
    static $config;

    public static function client($conn_name = 'default')
    {
        if (!self::$connection_id) {
            self::$local_save_path = C('ftp_config')['local_save_path'];
            self::$config = $config = C('ftp_config')[$conn_name];
            self::$connection_id = $connection_id = ftp_connect($config['host']);
            if (empty($connection_id)) {
                throw new Exception('ftp connection failed');
            }
            $login_result = ftp_login($connection_id, $config['username'], $config['password']);
            if (empty($login_result)) {
                throw new Exception('ftp login failed');
            }
        }
        return new static();
    }

    public function ftpPut ($file)
    {
        ftp_pasv(self::$connection_id, true);
        ftp_pwd(self::$connection_id);
        $result = ftp_put(self::$connection_id,  self::$config['ftp_upload_path']. $file, self::$local_save_path. $file, FTP_ASCII);
        ftp_close(self::$connection_id);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}