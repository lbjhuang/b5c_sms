<?php

/**
 * User: yangsu
 * Date: 17/12/6
 * Time: 15:44
 */
class ToggleAction extends Action
{
    private static $toggle_path = '/Application/Conf/';
    private static $toggle_name = 'toggle.json';

    public function set()
    {
        $key = I('key');
        $value = I('value');
        $bool = I('bool');
        list($res, $toggle_config) = self::infile();
        $res[$key] = ($bool) ? (bool)$value : $value;
        $res_status = file_put_contents($toggle_config, json_encode($res));
        var_dump($res_status);
    }

    public static function get_key($key)
    {
        list($res, $toggle_config) = self::infile();
        return $res[$key];
    }

    private static function infile()
    {
        $toggle_config = getcwd() . self::$toggle_path . self::$toggle_name;
        $toggle_json = file_get_contents($toggle_config);
        return [json_decode($toggle_json, true), $toggle_config];
    }


    public function test()
    {
        var_dump(self::get_key('patch_sendOut'));
    }

}