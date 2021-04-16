<?php
/**
 * User: yuanshixiao
 * Date: 2018/8/30
 * Time: 9:56
 */


class PmsBaseModel extends Model
{

    protected $autoCheckFields = false;

    public function __construct()
    {
        parent::__construct('','','PMS_DB');
    }

    public static function getLangCondition() {
        $english_code           = TbMsCmnCdModel::$language_english_cd;
        return LANG_CODE == $english_code ? [LANG_CODE] : [LANG_CODE,$english_code];
    }
}