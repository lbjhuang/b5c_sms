<?php
/**
 * User: yangsu
 * Date: 18/6/4
 * Time: 16:24
 */

class JsonDataModel extends Model
{
    /**
     * @var string
     */
    public static $path = 'JsonData';

    /**
     * @param $file_name
     *
     * @return bool|string
     */
    private static function base($file_name)
    {
        $file_patch = APP_PATH . self::$path . '/' . $file_name;
        return file_get_contents($file_patch);
    }

    /**
     * scm create
     *
     * @return bool|string
     */
    public static function scmCreateB2b()
    {
        return self::base('scm_create_b2b.json');
    }

    public static function scmSendOut()
    {
        return self::base('scm_send_out.json');
    }

    /**
     * @return bool|string
     */
    public static function updateSkuInfo()
    {
        return self::base('update_sku_info.json');
    }

    /**
     * @return bool|string
     */
    public static function addWarehouseInfo()
    {
        return self::base('add_warehouse_info.json');
    }

    public static function __callStatic($name, $arguments)
    {
        $base_return = self::base($name.'.json');
        if ('array' == $arguments[0]) {
            $base_return = DataModel::jsonToArr($base_return);
        }
        return $base_return;
    }

}