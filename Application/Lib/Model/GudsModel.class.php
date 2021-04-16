<?php
/**
 * Created by PhpStorm.
 * User: muzhitao
 * Date: 2016/8/2
 * Time: 13:35
 */
//namespace Application\Model;
//use Think\Model\RelationModel;
class GudsModel extends RelationModel
{
    protected $trueTableName = "tb_ms_guds";
    protected $_link = array(
        'Opt' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'Opt',
        ),
        'Img' => array(
            'mapping_type' => HAS_ONE,
            'class_name' => 'Img',
            'condition' => 'GUDS_IMG_CD = "N000080200"',
        ),
    );

    public static function getAllGudsOpts($sku_arr)
    {
        $opt_name_value_arr = [];
        if (empty($sku_arr)) {
            return $opt_name_value_arr;
        }
        if (!class_exists('GudsOptionModel')) {
            require_once APP_PATH . 'Lib/Model/Guds/GudsOptionModel.class.php';
        }
        if (!class_exists('OptionMapModel')) {
            require_once APP_PATH . 'Lib/Model/Guds/OptionMapModel.class.php';
        }
        $GudsOptionModel = new GudsOptionModel();
        $OptionMap = new OptionMapModel();
        $Model = M();
        $sku_arr = array_unique($sku_arr);
        $where_sku_arr['GUDS_OPT_ID'] = array('in', $sku_arr);
        $optionGroup = $Model->table('tb_ms_guds_opt')
            ->field('GUDS_ID,GUDS_OPT_ID,GUDS_OPT_VAL_MPNG')
            ->where($where_sku_arr)
            ->select();
        $skuMaps = $OptionMap->getOptionMaps($optionGroup);//SKU属性映射关系表。
        $allOptions = $OptionMap->getOptionByCodeMap($skuMaps, LANG_CODE);
        foreach ($optionGroup as $key => $opt) {
            $optMap = $GudsOptionModel->parseOptionMap($opt['GUDS_OPT_VAL_MPNG']);
            $optNameValueStr = '';
            foreach ($optMap as $nameCode => $valueCode) {
                $optNameValueStr .= explode('/', $allOptions[$nameCode]['ALL_VAL'])[1] . '：' . explode('/', $allOptions[$valueCode]['ALL_VAL'])[1] . '<BR/>';
            }
            $optNameValueStr = trim($optNameValueStr, '<BR/>');
            $opt_name_value_arr[$opt['GUDS_OPT_ID']] = $optNameValueStr;
        }
        return $opt_name_value_arr;
    }
}