<?php
/**
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/6/04
 * Time: 16:38
 */

class SftpModel extends Model
{
    static $client;
    static $local_save_path;
    static $sftp;
    static $config;

    public static function client($conn_name = 'default')
    {
        if (!self::$sftp) {
            vendor('phpseclib.Net.SFTP');
            vendor('phpseclib.Crypt.RC4');
            vendor('phpseclib.Crypt.Rijndael');
            vendor('phpseclib.Crypt.Twofish');
            vendor('phpseclib.Crypt.Blowfish');
            vendor('phpseclib.Crypt.TripleDES');
            vendor('phpseclib.Crypt.AES');
            vendor('phpseclib.Crypt.Hash');
            vendor('phpseclib.Crypt.Random');
            vendor('phpseclib.Crypt.RSA');
            vendor('phpseclib.Math.BigInteger');

            self::$local_save_path   = C('ftp_config')['local_save_path'];
            self::$config = $config  = C('ftp_config')[$conn_name];

            self::$sftp = new Net_SFTP($config['host']);
            if (!self::$sftp->login($config['username'], $config['password'])) {
                @SentinelModel::addAbnormal('sftp login failed', $config['username'], [$config['username']],'kyriba_notice');
                throw new Exception('sftp login failed');
            }
        }
        return new static();
    }

    public function put ($file)
    {
        $fp = fopen(self::$local_save_path. $file, 'r');
        if (!$fp) {
            throw new Exception('open xml file failed');
        }
        $result = self::$sftp->put(self::$config['ftp_upload_path']. $file, $fp, NET_SFTP_LOCAL_FILE);
        fclose($fp);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function putOther ($file)
    {
        $fp = fopen(self::$local_save_path. $file, 'r');
        if (!$fp) {
            throw new Exception('open xml file failed');
        }
        $result = self::$sftp->put(self::$config['ftp_download_path']. $file, $fp, NET_SFTP_LOCAL_FILE);
        fclose($fp);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //保存sftp的文件到本地
    public function downloadFile ()
    {
        $download_path = self::$config['ftp_download_path'];
        $files_name = self::$sftp->nlist($download_path);
        $download_success_file_path = $download_failed_file = [];
        foreach ($files_name as $name) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            $res = self::$sftp->get($download_path. $name, self::$local_save_path. $name);
            if (!$res) {
                $download_failed_file[] = $name;
            } else {
                $download_success_file_path[$name] = self::$local_save_path. $name;
            }
        }
        return [$download_success_file_path, $download_failed_file];
    }

    //删除sftp文件
    public function delete ($name)
    {
        $path =  self::$config['ftp_download_path']. $name;
        if (self::$sftp->is_file($path)) {
            return self::$sftp->delete($path);
        }
        return false;
    }

    //定期删除asc文件
    public function deleteSomeFile(){
        $download_path = self::$config['ftp_download_path'];
        $files_name = self::$sftp->nlist($download_path);
        foreach ($files_name as $name) {
            if(strripos($name,'.asc') !==false){
                $this->delete($name);
            }
        }
    }
}