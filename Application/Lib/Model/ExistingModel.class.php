<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/21
 * Time: 10:40
 */

class ExistingModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_batch';

    public function searchModel()
    {
        $model = new Model();

        $fields = [
            'batch.SKU_ID',
            'batch.bill_id',
            'SUM(batch.total_inventory) as amount',
            'SUM(batch.all_available_for_sale_num * stream.unit_price) as money',
            'SUM(batch.occupied) as all_occupied',
            'SUM(batch.locking) as all_locking',
            'SUM(batch.available_for_sale_num) as all_sale_num'
        ];
        $subQuery = $model
            ->field($fields)
            ->table('tb_wms_batch batch, tb_wms_stream stream')
            ->group('SKU_ID')
            ->where('batch.stream_id = stream.id and batch.total_inventory > 0')
            ->buildSql();

        $count = $model->table($subQuery. ' t1')
            ->join('left join tb_ms_guds_opt guds_opt on t1.SKU_ID = guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds guds on SUBSTR(t1.SKU_ID, 1, 8) = guds.GUDS_ID')
            ->join('left join tb_wms_bill bill on t1.bill_id = bill.id')
            ->where()
            ->count();
        $page = new Page($count, 20);

        $ret = $model->table($subQuery. ' t1')
            ->join('left join tb_ms_guds_opt guds_opt on t1.SKU_ID = guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds guds on SUBSTR(t1.SKU_ID, 1, 8) = guds.GUDS_ID')
            ->join('left join tb_wms_bill bill on t1.bill_id = bill.id')
            ->field('t1.*, guds_opt.GUDS_OPT_UPC_ID, guds.GUDS_NM, guds_opt.GUDS_OPT_VAL_MPNG, guds_opt.GUDS_OPT_CODE')
            ->where()
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $show = $page->ajax_show('filter_search');
        // 数据处理，增加商品主图与属性
        if ($ret) {
            $skuIds = array_column($ret, 'SKU_ID');
            $img = $this->getGudsImg($skuIds);
            foreach ($ret as $k => &$v) {
                $v ['GUDS_OPT_VAL_MPNG'] = $this->gudsOptsMerge($v ['GUDS_OPT_VAL_MPNG']);
                $v ['GUDS_IMG_CDN_ADDR'] = $img [$v ['SKU_ID']];
                unset($v); //删除指针
            }
        }
        return ['response' => $model->getLastSql(), 'page' => $show, 'count' => $count];
    }

    /**
     * 筛选条件处理
     *
     */
    public function processSearchConditions($params)
    {
        $conditions ['total_inventory'] = ['gt', 0];
    }

    /**
     * 商品图片获取
     * 与当前语言设置相符的，取当前语言相符的图片
     * 与当前语言不符的，取英语类型图片
     * 若英语图片不存在，则取任意存在的图片
     * @param array $skuIds 多个 sku_id
     * @return array 商品主图数组
     */
    public function getGudsImg($skuIds)
    {
        $tmp = [];
        $imgs = [];
        foreach ($skuIds as $k => $v) {
            $tmp [$v] = substr($v, 0, 8);
        }
        $language = BaseModel::languages()[LANG_SET]['CD'];
        $englishLanguage = 'N000920200';
        $model = new Model();
        $conditions['MAIN_GUDS_ID'] = ['in', $tmp];
        $conditions['GUDS_IMG_CD'] = ['eq', 'N000080200'];
        $ret = $model->table('tb_ms_guds_img')
            ->where($conditions)
            ->field(['MAIN_GUDS_ID', 'GUDS_IMG_CDN_ADDR', 'LANGUAGE'])
            ->select();
        // GUDS_ID 相关联语种图片
        foreach ($ret as $k => $v) {
            $imgs [$v ['MAIN_GUDS_ID']][] = $v;
        }
        // 每个 GUDS_ID 对应有多个图片
        foreach ($imgs as $k => $v) {
            $currentImgs [$k] = array_column($v, 'LANGUAGE');
        }
        // 按当前语种、英语、其他语种，进行语言图片保留，值保留一个图片
        foreach ($currentImgs as $k => $v) {
            if (in_array($language, $v)) {
                $retainLanguage [$k] = $language;
            } elseif (in_array($englishLanguage, $v)) {
                $retainLanguage [$k] = $englishLanguage;
            } else {
                $retainLanguage [$k] = $v [0];
            }
        }
        foreach ($imgs as $k => $v) {
            foreach ($v as $i => $j) {
                if ($j ['LANGUAGE'] == $retainLanguage [$k]) {
                    $_img [$k] = $j;
                    continue;
                }
            }
        }
        foreach ($tmp as $k => $v) {
            $new_retain [$k] = $_img [$v]['GUDS_IMG_CDN_ADDR'];
        }

        return $new_retain;
    }

    /**
     * 商品属性组装
     * @param string $code 属性编码
     * @return string 属性中文
     */
    public function gudsOptsMerge($code)
    {
        $str = explode(';', $code);
        $opt = BaseModel::getGudsOpt();
        $cn = '';
        $length = count($str);
        for ($i = 0; $i < $length; $i ++) {
            if ($opt[$str[$i]]['OPT_CNS_NM'] and $opt[$str[$i]]['OPT_VAL_CNS_NM']) $cn .= $opt[$str[$i]]['OPT_CNS_NM'] . ':' . $opt[$str[$i]]['OPT_VAL_CNS_NM'] . ' ';
            else $cn .=  $opt[$str[$i]]['OPT_VAL_CNS_NM'];
        }
        return $cn;
    }
}