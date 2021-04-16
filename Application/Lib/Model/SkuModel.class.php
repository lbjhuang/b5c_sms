<?php

/**
 * User: yangsu
 * Date: 18/09/03
 * Time: 14:52
 */

@import("@.Model.PmsBaseModel");

/**
 * Class SkuModel
 */
class SkuModel extends Model
{
    /**
     * @var null
     */
    public static $PMS = null;
    /**
     * @var null
     */
    public static $db_name = null;

    /**
     * @param $check_data_arr
     *
     * @return bool
     */
    const first_key = 0;

    /**
     *
     */
    const english = 'N000920200';

    /**
     * @param $check_data_arr
     *
     * @return bool
     */
    public static function initInterceptor($check_data_arr)
    {
        foreach ($check_data_arr as $data_arr) {
            if (empty($data_arr) && !is_array($data_arr)) {
                return true;
            }
        }
    }

    /**
     *
     */
    public static function initModel()
    {
        if (empty(self::$PMS)) {
            $Model = new Model();
            self::$PMS = clone $Model;
            self::$PMS->db(1, 'PMS_DB');
            self::$db_name = C('PMS_DB.DB_NAME');
        }
    }

    /**
     *
     */
    public static function unsetModel()
    {
//        self::$PMS = null;
//        new Model(null, null, null);
    }

    /**
     *
     */
    public static function getInfos(
        $data_arr,
        $data_key = 'goods',
        $sku_key = 'sku_id',
        $appends = ['spu_name'],
        $alias = [],
        $language_type = null
    )
    {
        $all_skus = [];
        foreach ($data_arr as $key => $value) {
            $skus = array_column($value[$data_key], $sku_key);
            $all_skus = array_merge($all_skus, DataModel::toArray($skus));
        }
        if (empty($all_skus)) {
            return $data_arr;
        }
        $all_skus = array_unique($all_skus);
        foreach ($all_skus as $sku) {
            $data_sku['sku_id'] = $sku;
            $data_sku_arr[] = $data_sku;
        }
        $res_arr = self::getInfo($data_sku_arr, 'sku_id', $appends, $alias, $language_type);
        if (sha1(serialize($data_sku_arr)) == sha1(serialize($res_arr))) {
            return $data_arr;
        }
        unset($data_sku, $data_sku_arr, $all_skus, $skus);
        $all_sku_key = array_column($res_arr, 'sku_id');
        $res_key_arr = array_combine($all_sku_key, $res_arr);
        foreach ($data_arr as $key => $value) {
            foreach ($value[$data_key] as $temp_key => $temp_value) {
                foreach ($appends as $append) {
                    $value_name = $alias[$append] ? $alias[$append] : $append;
                    $data_arr[$key][$data_key][$temp_key][$value_name] = $res_key_arr[$temp_value[$sku_key]][$value_name];
                }
            }
        }
        return $data_arr;

    }

    /**
     * 商品名 spu_name，属性 attributes，图片 image_url，SKU数据表，SKU的基本属性 product_sku, 商品基础信息主表 product
     *
     * @param        $data_arr
     * @param string $sku_key
     * @param array $appends [商品名，属性，图片， ]
     * @param array $alias 别名
     * @param null $language_type
     *
     * @return mixed
     */
    public static function getInfo(
        $data_arr,
        $sku_key = 'sku_id',
        $appends = ['spu_name'],
        $alias = [],
        $language_type = null
    )
    {
        if (self::initInterceptor([$data_arr, $appends])) {
            return $data_arr;
        }
        $language_type = self::joinLanguageType($language_type);
        $sku_ids = array_column($data_arr, $sku_key);
        $sku_arr = array_unique($sku_ids);
        if (empty($sku_arr)) {
            return $data_arr;
        }
        self::initModel();
        $spu_key_values = self::getAllSpuKeyValue($sku_arr);
        if (empty($spu_key_values)) {
            return $data_arr;
        }
        $sku_all_data = self::getAppendDatas($appends,
            $language_type,
            $sku_ids,
            $spu_key_values
        );
        foreach ($data_arr as $key => $value) {
            $sku_id = $value[$sku_key];
            $spu_id = $spu_key_values[$sku_id];
            foreach ($appends as $append) {
                $sku_list = ['attributes', 'product_sku'];
                $append_idx_key = $spu_id;
                if (in_array($append, $sku_list)) {
                    $append_idx_key = $sku_id;
                }
                $value_name = $alias[$append] ? $alias[$append] : $append;
                switch ($append) {
                    case 'image_url':
                        $data_arr[$key]['show_img'] = false;
                    default:
                        $data_arr[$key][$value_name] = $sku_all_data[$append][$append_idx_key];
                }
            }
        }
        self::unsetModel();
        $data_arr = self::formatResponseData($data_arr);
        return $data_arr;
    }

    /**
     * @param $append
     * @param $sku_ids
     * @param $spu_key_values
     * @param $language_type
     *
     * @return array|mixed
     */
    private static function getSkuAllData($append, $sku_ids, $spu_key_values, $language_type)
    {
        switch ($append) {
            case 'spu_name':
                $temp_data = self::getSpuNames($spu_key_values, $language_type);
                break;
            case 'attributes':
                $temp_data = self::getSpuAttributes($sku_ids, $language_type);
                break;
            case 'image_url':
                $temp_data = self::getImageUrls($spu_key_values, $language_type);
                break;
            case 'product_sku':
                $temp_data = self::getPmsTables($sku_ids, 'product_sku', 'sku_id');
                break;
            case 'product':
                $temp_data = self::getPmsTables($spu_key_values, 'product', 'spu_id');
                break;
            case 'brand_name':
                $temp_data = self::getBrandName($spu_key_values);
                break;
        }
        return $temp_data;
    }

    /**
     * @param        $sku_arr
     * @param        $table_name
     * @param string $type
     *
     * @return array
     */
    private static function getPmsTables($sku_arr, $table_name, $type = 'sku_id')
    {
        $where[$type] = array('IN', array_values(array_unique($sku_arr)));
        $res = self::$PMS->table($table_name)
            ->where($where)
            ->select();
        $res_return = [];
        foreach ($res as $temp_value) {
            $res_return[$temp_value[$type]] = $temp_value;
        }
        return DataModel::toArray($res_return);
    }

    /**
     * @param $sku_arr
     *
     * @return array
     */
    protected static function getAllSpuKeyValue($sku_arr)
    {
        $where_sku['sku_id'] = array('IN', $sku_arr);
        $table = self::$db_name . '.product_sku';
        $res_db = self::$PMS->table($table)
            ->where($where_sku)
            ->cache(true, 3)
            ->select();
        if (empty($res_db)) {
            return (array)null;
        }
        $spu_arr = array_column($res_db, 'spu_id', 'sku_id');
        return $spu_arr;
    }


    /**
     * @param $sku_ids
     * @param $spu_key_values
     * @param $language_type
     *
     * @return mixed
     */
    private static function getSpuNames($spu_key_values, $language_type)
    {
        $where['spu_id'] = array('IN', array_unique(array_filter(array_values($spu_key_values))));
        $where['language'] = array('IN', $language_type);
        $table = self::$db_name . '.product_detail';
        $res = self::$PMS->table($table)
            ->field('spu_id,spu_name,MAX(`language`) AS max_language')
            ->where($where)
            ->group('spu_id,language')
            ->having('max_language = `language`')
            ->cache(true, 3)
            ->select();
        $res = array_column($res, 'spu_name', 'spu_id');
        return DataModel::toArray($res);
    }

    private static function getBrandName($spu_arr, $language_type = 'N000920100')
    {
        $where['product.spu_id'] = array('IN', array_values(array_unique($spu_arr)));
        $where_string = "product.brand_id = product_brand_detail.brand_id AND product_brand_detail.language = '{$language_type}'";
        $res = self::$PMS->table('product,product_brand_detail')
            ->field('product_brand_detail.brand_name,product.spu_id')
            ->where($where)
            ->where($where_string, null, true)
            ->cache(true, 3)
            ->select();
        $res_return = array_column($res, 'brand_name', 'spu_id');
        return DataModel::toArray($res_return);
    }

    /**
     * @param $spu_key_values
     * @param $language_type
     *
     * @return array
     */
    private static function getImageUrls($spu_key_values, $language_type)
    {
        $where['spu_id'] = array('IN', array_values($spu_key_values));
        // $where['language'] = array('IN', $language_type);
        $where['image_type'] = 'N000080200';
        $table = self::$db_name . '.product_image';
        $res = self::$PMS->table($table)
            ->field('spu_id,image_url')
            ->where($where)
            ->cache(true, 3)
            ->select();
        $res = array_column($res, 'image_url', 'spu_id');
        return DataModel::toArray($res);
    }

    /**
     * @param $sku_ids
     * @param $language_type
     *
     * @return array
     */
    private static function getSpuAttributes($sku_ids, $language_type)
    {

        $where['option_name_detail.language'] = array('IN', $language_type);;
        $where['product_attribute.sku_id'] = array('IN', array_unique(array_values($sku_ids)));
        $tables = sprintf("(%s.product_attribute,%s.option_name_detail,%s.option_value_detail)",
            self::$db_name,
            self::$db_name,
            self::$db_name
        );
        $ress = self::$PMS->table($tables)
            ->field('product_attribute.sku_id,option_name_detail.name_detail,
            option_value_detail.value_detail,option_name_detail.language')
            ->where($where)
            ->where("product_attribute.name_id = option_name_detail.name_id 
            AND product_attribute.value_id = option_value_detail.value_id
            AND option_name_detail.language = option_value_detail.language",
                null,
                true)
            ->cache(true, 3)
            ->select();
        $res_return_ones = $res_return = [];
        foreach ($ress as $temp_value) {
            if (!empty($temp_value['name_detail']) && !empty($temp_value['value_detail']) && $temp_value['value_detail'] !== 'null' && $temp_value['name_detail'] !== 'null') { // name 和 value都不为空且不为null情况下，返回才有意义
                $res_return[$temp_value['sku_id']][$temp_value['language']] .= sprintf("%s:%s,", $temp_value['name_detail'], $temp_value['value_detail']);
            }
        }

        $max_lang = max($language_type);
        foreach ($res_return as $key => $value) {
            if ($value[$max_lang]) {
                $res_return_ones[$key] = trim($value[$max_lang], ',');
            } else {
                $res_return_ones[$key] = trim($value[self::english], ',');
            }
        }
        return DataModel::toArray($res_return_ones);
    }

    /**
     * @param $appends
     * @param $language_type
     * @param $sku_ids
     * @param $spu_key_values
     * @param $sku_all_data
     */
    private static function getAppendDatas($appends, $language_type, $sku_ids, $spu_key_values)
    {
        foreach ($appends as $append) {
            $sku_all_data[$append] = self::getSkuAllData($append, $sku_ids, $spu_key_values, $language_type);
        }
        return $sku_all_data;
    }

    /**
     * @param      $title_key
     * @param bool $only
     *
     * @return array
     */
    public static function titleToSku($title_key, $only = false)
    {
        $sku_arr = [];
        if (empty($title_key)) {
            return $sku_arr;
        }
        self::initModel();
        $sku_arr = self::getAllTitleSku($title_key, $only);
        self::unsetModel();
        if (empty($sku_arr)) {
            $sku_arr = [$title_key];
        }
        return $sku_arr;
    }

    /**
     * @param $title_key
     * @param $only
     *
     * @return array
     */
    private static function getAllTitleSku($title_key, $only)
    {
        $where['product_detail.spu_name'] = array('like', "%{$title_key}%");
        $tables = sprintf("(%s.product_detail,%s.product_sku)",
            self::$db_name,
            self::$db_name
        );
        $res = self::$PMS->table($tables)
            ->field('product_sku.sku_id')
            ->where($where)
            ->where("product_detail.spu_id = product_sku.spu_id", null, true)
            ->order('product_sku.sku_id asc')
            ->cache(true, 3)
            ->select();
        if ($only) {
            return $res[0]['sku_id'];
        } else {
            return DataModel::toArray(array_column($res, 'sku_id'));
        }
    }

    /**
     * @param $data
     * @param $table_name
     *
     * @return mixed
     */
    public static function getTableAttr($data, $table_name = 'product_sku', $attr = ['upc_id' => 'bar_code'])
    {
        if ($data[self::first_key][$table_name]) {
            foreach ($data as $key => $value) {
                foreach ($attr as $temp_key => $temp_value) {
                    $data[$key][$temp_value] = $value[$table_name][$temp_key];
                }
            }
        }
        return $data;
    }

    /*
     * @param $language_type
     *
     * @return array|mixed|string
     */
    private static function joinLanguageType($language_type)
    {
        if (!$language_type) {
            $english_language_type = self::english;
            $china_language_type = 'N000920100';
            $language_type = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : $english_language_type;
            if ($english_language_type != $language_type && $china_language_type != $language_type) {
                $language_type = (array)$language_type;
                array_push($language_type, $english_language_type);
            } else {
                $language_type = (array)$language_type;
            }
        }
        return $language_type;
    }

    /**
     * @param $data_arr
     *
     * @return mixed
     */
    private static function formatResponseData($data_arr)
    {
        $first_key = 0;
        $changes_arr = ['product_sku', 'product'];
        $array_keys = array_keys($data_arr[$first_key]);
        if (in_array('product_sku', $array_keys) || in_array('product', $array_keys)) {
            foreach ($data_arr as $temp_key => $temp_value) {
                $data_arr[$temp_key] = DataModel::forceToArr($temp_value, $changes_arr);
            }
        }
        return $data_arr;
    }

    /**
     * @param        $upc_id
     * @param string $type
     *
     * @return array
     */
    public static function upcTosku($upc_id, $type = 'strict')
    {
        self::initModel();
        $where['upc_id'] = $upc_id;
        # 新增多sku 查询
        $where['_string'] = "FIND_IN_SET('{$upc_id}',upc_more)";
        $where['_logic']    = 'or';
        switch ($type) {
            case 'strict':
                return self::$PMS->table('product_sku')
                    ->where($where)
                    ->getField('sku_id');
                break;
            case 'loose':
                return array_column(self::$PMS->table('product_sku')
                    ->field('sku_id')
                    ->where($where)
                    ->select(), 'sku_id');
                break;
            case 'strict_mixing':
                $sku_id = self::$PMS->table('product_sku')
                    ->where($where)
                    ->getField('sku_id');
                if (empty($sku_id)) {
                    return $upc_id;
                }
                return $sku_id;
                break;
        }
    }

    /**
     * @param       $sku_arr
     * @param array $appends
     * @param       $language_type
     *
     * @return array|mixed
     */
    public static function getSkusInfo($sku_arr, $appends = ['spu_name'], $language_type = null)
    {
        $language_type = self::joinLanguageType($language_type);
        $sku_ids = $sku_arr;
        $sku_arr = array_unique($sku_ids);
        if (empty($sku_arr)) {
            return $sku_arr;
        }
        self::initModel();
        $spu_key_values = self::getAllSpuKeyValue($sku_arr);
        if (empty($spu_key_values)) {
            return $sku_arr;
        }
        $sku_all_data = self::getAppendDatas($appends,
            $language_type,
            $sku_ids,
            $spu_key_values
        );
        foreach ($spu_key_values as $key => $value) {
            $sku_all_data['spu_name'][$key] = $sku_all_data['spu_name'][$value];
        }
        return $sku_all_data;
    }

    /**
     * @param $pageData
     * @param $value
     *
     * @return array
     */
    public static function joinPmsSkuInfo($pageData)
    {
        $pageData = self::getInfos(
            $pageData,
            'ordGudsOpt',
            'gudsOptId',
            ['spu_name'],
            ['spu_name' => 'gudsNm']
        );
        $OutStorageModel = new OutStorageModel();
        foreach ($pageData as $key => &$value) {
            $value['skuIds'] = $OutStorageModel->getSkuIds($value['ordGudsOpt']);
        }
        return $pageData;
    }

    /**
     * @param array $sku_arr
     *
     * @return mixed
     */
    public static function getGroupSku(array $sku_arr = [])
    {
        self::initModel();
        $where['product.is_group_sku'] = 1;
        if ($sku_arr) {
            $where['product_sku.spu_id'] = ['IN', $sku_arr];
        }
        return self::$PMS->table('product_sku,product')
            ->field('product_sku.sku_id,product_sku.spu_id')
            ->where($where)
            ->where('product_sku.spu_id = product.spu_id', null, true)
            ->select();

    }

    public static function getSpuId($sku)
    {
        self::initModel();
        return self::$PMS->table('product_sku')
            ->where(['sku_id' => $sku])
            ->getField('spu_id');
    }

    public static function getUpcId($sku)
    {
        self::initModel();
        return self::$PMS->table('product_sku')
            ->where(['sku_id' => $sku])
            ->getField('upc_id');
    }

    public static function getGroupSkuMap($sku_arr)
    {
        if (is_array($sku_arr)) {
            $where['cb_sku_id'] = ['IN', $sku_arr];
        } else {
            $where['cb_sku_id'] = $sku_arr;
        }
        self::initModel();
        return self::$PMS->table('product_combine_map')
            ->field('cb_sku_id,sku_id,number')
            ->where($where)
            ->select();
    }

    public static function getSplitSkuIds($sku_ids)
    {
        if (is_array($sku_ids)) {
            $where['sku_id'] = ['IN', $sku_ids];
        } else {
            $where['sku_id'] = $sku_ids;
        }
        $where['sku_apart_status'] = 1;

        self::initModel();
        return self::$PMS->table('product_sku')->where($where)->getField('sku_id', true);
    }

    /**
     * 获取PMS商品信息
     *
     * @param $data 获取商品信息数据
     * @param bool $is_two_dimension 返回的商品信息是否组装为二维数组
     * @param string $where_key 从$data中提取的字段
     * @param string $where_table 根据$where_key，需要查询的表
     *
     * @return mixed
     */
    public static function productInfo($data, $is_two_dimension = false, $where_key = 'sku_id', $where_table = 'product_sku')
    {
        $where_val = [];
        if (count($data) == count($data, COUNT_RECURSIVE)) {
            $where_val = [$data[$where_key]];
        } else {
            foreach ($data as &$value) {
                $where_val = array_merge($where_val, explode(',', $value[$where_key]));
            }
            $where_val = array_unique($where_val);
        }
        if (empty($where_val)) {
            return $data;
        }
        $condition[$where_table . '.sku_id'] = ['in', $where_val];
        $db_res = self::getProductInfo($condition);
        if (empty($db_res)) {
            return $data;
        }
        $result = [];
        foreach ($db_res as $k => &$v) {
            if ($v['sale_states'] == 1) {
                $v['sale_states_val'] = '启用';
            } else {
                $v['sale_states_val'] = '停用';
            }
            $result[$v['sku_id']] = $v;
        }
        if (count($data) != count($data, COUNT_RECURSIVE)) {
            //二维数组组装
            foreach ($data as &$value) {
                $sku_arr = explode(',', $value[$where_key]);
                if ($is_two_dimension) {
                    foreach ($sku_arr as $v) {
                        $value['product_info'][] = $result[$v];
                    }
                } else {
                    $value['product_info'] = $result[$sku_arr[0]];
                }
            }
        } else {
            //一维数组组装
            $sku_arr = explode(',', $data[$where_key]);
            if ($is_two_dimension) {
                foreach ($sku_arr as $v) {
                    $data['product_info'][] = $result[$v];
                }
            } else {
                $data['product_info'] = $result[$sku_arr[0]];
            }
        }
        return $data;
    }

    /**
     * 获取PMS商品信息
     *
     * @param $condition
     *
     * @return mixed
     */
    public static function getProductInfo($condition)
    {
        $condition['option_name_detail.language'] = ['in', ['N000920100']];
        $condition['option_value_detail.language'] = ['in', ['N000920100']];
        $condition['product_detail.language'] = ['in', ['N000920100']];
        $field = "product_sku.upc_id,product_sku.real_price AS price, product.thumbnail, product_sku.sku_id, product_detail.spu_name,product_sku.sku_states as sale_states,
            CONCAT_WS(':',
                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        option_name_detail.name_detail ORDER BY 
                        ABS(
                            right(
                                option_name_detail.language, 6
                            )
                            - 920200
                        ) desc
                     ), ',', 1
                ),
                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        option_value_detail.value_detail ORDER BY 
                        ABS(
                            right(
                                option_value_detail.language, 6
                            )
                            - 920200
                        ) desc
                     ), ',', 1
                )
            ) AS product_attr";
        self::initModel();
        $db_res = self::$PMS
            ->table('product')
            ->field($field)
            ->join('left join product_detail on product.spu_id = product_detail.spu_id')
            ->join('left join product_sku on product.spu_id = product_sku.spu_id')
            ->join('left join product_attribute on product_sku.sku_id = product_attribute.sku_id')
            ->join('left join option_name_detail on product_attribute.name_id = option_name_detail.name_id')
            ->join('left join option_value_detail on product_attribute.value_id = option_value_detail.value_id')
            ->where($condition)
            ->group('sku_id')
            ->select();
        return $db_res;
    }

    public static function getSkuNames($sku_arr, $field, $lang = 'N000920100')
    {
        $where['product_detail.language'] = $lang;
        $where['product_sku.sku_id'] = array('in', $sku_arr);
        $table = self::$db_name . '.product_sku';
        $table2 = self::$db_name . '.product_detail';
        $res = self::$PMS->table($table)
            ->field($field)
            ->join("LEFT JOIN {$table2} ON {$table}.spu_id = {$table2}.spu_id ")
            ->where($where)
            ->cache(true, 3)
            ->select();
        return $res;
    }

}