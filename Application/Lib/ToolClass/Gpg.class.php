<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/27
 * Time: 11:29
 */
class Gpg
{
    public $public_key;
    private $private_key;

    public function __construct()
    {
        if ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST']) {
            $this->public_key = file_get_contents(APP_SITE. '/resources/key/kyriba-online-pgp-public-key.txt');
            $this->private_key = file_get_contents(APP_SITE. '/resources/key/erp-online-pgp-private-key.txt');
        } else {
            $this->public_key = file_get_contents(APP_SITE. '/resources/key/kyriba-stage-pgp-public-key.txt');
            $this->private_key = file_get_contents(APP_SITE. '/resources/key/erp-stage-pgp-private-key.txt');
        }
    }

    /**
     * 公钥加密
     * @param $text
     * @return mixed
     */
    public function encrypt($text)
    {
        putenv("GNUPGHOME=/tmp");
        $gpg = gnupg_init();
        gnupg_seterrormode($gpg, GNUPG_ERROR_EXCEPTION);
        $key_info = gnupg_import($gpg, $this->public_key);//导入公钥
        Logs($key_info, __FUNCTION__. ' encrypt key info', 'pgp');

        gnupg_addencryptkey($gpg, $key_info['fingerprint']);
        $encrypt_text = gnupg_encrypt($gpg, $text);
        if ($encrypt_text === false) {
           return false;
        }
        Logs([], __FUNCTION__. ' encrypt success', 'pgp');
        return $encrypt_text;
    }

    /**
     * 私钥解密
     * @param $text
     * @return mixed
     */
    public function decrypt($text)
    {
        putenv("GNUPGHOME=/tmp");
        $gpg = gnupg_init();
        gnupg_seterrormode($gpg, GNUPG_ERROR_EXCEPTION);
        $key_info = gnupg_import($gpg, $this->private_key);//导入私钥
        Logs($key_info, __FUNCTION__. ' decrypt key info', 'pgp');

        gnupg_adddecryptkey($gpg, $key_info['fingerprint']);
        $decrypt_text = gnupg_decrypt($gpg, $text);

        if ($decrypt_text === false) {
           return false;
        }
        Logs([], __FUNCTION__. ' decrypt success', 'pgp');
        return $decrypt_text;
    }
}