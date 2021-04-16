<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/9/5
 * Time: 15:06
 */
class PMSSearchModel
{
    /**
     * 设置相关语言，默认英语
     * @return array|mixed
     */
    public static function language()
    {
        $language = BaseModel::languages();
        $language = [
            $language [strtolower(LANG_SET)]['CD'], // 设定的语言
            'N000920200' // 默认英语
        ];

        return $language;
    }

    /**
     * 返回sku属性相关信息
     * @return mixed
     */
    public static function nameDetailSql()
    {
        $model = new Model();

        return $nameDetailSql = $model->field('tab1.name_id, SUBSTRING_INDEX(GROUP_CONCAT(tab1.name_detail ORDER BY ABS(right(tab1.language, 6) - 920200) desc), \',\', 1) name_detail')
            ->table(PMS_DATABASE . '.option_name_detail tab1')
            ->where(['tab1.language' => ['in', self::language()]])
            ->group('tab1.name_id')
            ->buildSql();
    }

    /**
     * 返回sku属性相关信息
     * @return mixed
     */
    public static function valueDetailSql()
    {
        $model = new Model();

        return $valueDetailSql = $model->field('tab1.value_id, SUBSTRING_INDEX(GROUP_CONCAT(tab1.value_detail ORDER BY ABS(right(tab1.language, 6) - 920200) desc), \',\', 1) value_detail')
            ->table(PMS_DATABASE . '.option_value_detail tab1 ')
            ->where(['tab1.language' => ['in', self::language()]])
            ->group('tab1.value_id')
            ->buildSql();
    }

    /**
     * 返回spu名称相关信息
     * @return mixed
     */
    public static function spuNameSql()
    {
        $model = new Model();

        return $spuNameSql = $model->field('tab1.spu_id, SUBSTRING_INDEX(GROUP_CONCAT(tab1.spu_name ORDER BY ABS(right(tab1.language, 6) - 920200) desc), \',\', 1) spu_name')
            ->table(PMS_DATABASE . '.product_detail tab1 ')
            ->where(['tab1.language' => ['in', self::language()]])
            ->group('tab1.spu_id')
            ->buildSql();
    }

    /**
     * 返回spu单位相关信息
     * @return mixed
     */
    public static function spuUnitSql()
    {
        $model = new Model();

        return $spuNameSql = $model->field('tab1.spu_id, tab1.charge_unit, tab1.cat_level1')
            ->table(PMS_DATABASE . '.product tab1 ')
            ->group('tab1.spu_id')
            ->buildSql();
    }

    /**
     * 返回sku条形码相关信息
     * @return mixed
     */
    public static function skuUpcSql()
    {
        $model = new Model();

        return $spuNameSql = $model->field('tab1.spu_id, tab1.sku_id, tab1.upc_id, tab1.sku_states,tab1.upc_more')
            ->table(PMS_DATABASE . '.product_sku tab1 ')
            //->where(['tab1.language' => ['in', self::language()]])
            ->group('tab1.sku_id')
            ->buildSql();
    }

    /**
     * 返回sku条形码相关信息and商品基础信息主表
     * @return mixed
     */
    public static function skuProduct($where)
    {
        $model = new Model();
        return $skuProduct = $model->field('tab1.spu_id, tab1.sku_id, tab1.upc_id, tab1.sku_states, tab2.supplier')
            ->table(PMS_DATABASE . '.product_sku tab1 ')
            ->where($where)
            //->where(['tab1.language' => ['in', self::language()]])
            ->join('left join ' . PMS_DATABASE . '.product tab2 on tab2.spu_id = tab1.spu_id')
            ->select();
    }

    // 根据spu名称返回对应的sku
    public static function getSkuBySpuName($name)
    {
        $where['pd.spu_name'] = array('like', '%' . $name . '%');
        $model = new Model();
        return $sku_arr = $model->table(PMS_DATABASE . '.product_detail pd ')
            ->field('ps.sku_id')
            ->where($where)
            ->join('left join ' . PMS_DATABASE . '.product_sku ps on ps.spu_id = pd.spu_id')
            ->select();
    }
}