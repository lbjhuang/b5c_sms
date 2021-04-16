<?php
/**
 * User: yangsu
 * Date: 2018/9/03
 * Time: 16:30
 */

class PmsProductSkuModel extends PmsBaseModel
{

    protected $trueTableName = 'product_sku';

    /**
     * 批量获取 sku_id 分类层级名称
     * @author Redbo He
     * @date 2021/3/19 10:52
     */
    public function getSkuIdsCatLevelName($skuIds, $language = 'N000920100')
    {
        # PMS_DATABASE
        $result = [];

        if($skuIds)
        {
            $model = M();
            $sku_cat_data = $model->table(PMS_DATABASE. ".product_sku as a ")
                ->join("inner join ". PMS_DATABASE ." .product as b  on a.spu_id = b.spu_id")
                # ->join("inner join ". PMS_DATABASE .".product_category_detail as c on b.")
                ->field([
                    "a.sku_id",
                    "b.spu_id",
                    "b.cat_level1",
                    "b.cat_level2",
                    "b.cat_level3",
                    "b.cat_level4",
                ])
                ->where([
                    "a.sku_id" => ['in', $skuIds]
                ])
                ->select();
            $cat_ids = [];
            if($sku_cat_data)
            {

                foreach ($sku_cat_data as $sku_cat)
                {
                    $cat_ids[] = $sku_cat['cat_level1'];
                    $cat_ids[] = $sku_cat['cat_level2'];
                    $cat_ids[] = $sku_cat['cat_level3'];
                    $cat_ids[] = $sku_cat['cat_level4'];
                }
                $cat_ids = array_unique(array_filter($cat_ids));
                if($cat_ids)
                {

                    $cat_data = $model->table(PMS_DATABASE. ".product_category_detail")
                        ->where([
                            'cat_id' => ['in', $cat_ids],
                            "language" => ['eq', $language]
                        ])
                        ->field("cat_id,cat_name")
                        ->select();
                    $sku_cat_data = array_column($sku_cat_data,NULL,'sku_id');
                    $cat_data     = array_column($cat_data,'cat_name','cat_id');
                    foreach ($sku_cat_data as $sku_id =>  $sku_item) {
                        $cat_tmp  = [
                            'cat_level1_name' => isset($cat_data[$sku_item['cat_level1']]) ? $cat_data[$sku_item['cat_level1']] : '',
                            'cat_level2_name' => isset($cat_data[$sku_item['cat_level2']]) ? $cat_data[$sku_item['cat_level2']] : '',
                            'cat_level3_name' => isset($cat_data[$sku_item['cat_level3']]) ? $cat_data[$sku_item['cat_level3']] : '',
                            'cat_level4_name' => isset($cat_data[$sku_item['cat_level4']]) ? $cat_data[$sku_item['cat_level4']] : '',
                        ];
                        $result[$sku_id] = implode(">", array_filter(array_values($cat_tmp)));
                    }
                }




            }
        }
        return $result;
    }

    /**
     * 批量获取 sku_id 分类层级名称列表
     * @param array  $skuIds SKU_ID数组
     * @param string $language 指定语言
     * @author Wenbin.Wang
     * @return array
     */
    public function getSkuIdsCatLevelNameList($skuIds = [], $language = 'N000920100')
    {
        $result = $catIds = [];

        if (empty($skuIds)) return $result;

        $model = M();
        $skuCatData = $model->table(PMS_DATABASE . '.product_sku as a')
            ->join('inner join ' . PMS_DATABASE . '.product AS b ON a.spu_id = b.spu_id')
            ->where([
                'a.sku_id' => ['in', $skuIds]
            ])
            ->getField('a.sku_id,b.spu_id,b.cat_level1,b.cat_level2,b.cat_level3,b.cat_level4');

        if (empty($skuCatData)) return $result;

        foreach ($skuCatData as $cat) {
            $catIds[] = $cat['cat_level1'];
            $catIds[] = $cat['cat_level2'];
            $catIds[] = $cat['cat_level3'];
            $catIds[] = $cat['cat_level4'];
        }
        $catIds = array_unique(array_filter($catIds));

        if (empty($catIds)) return $result;

        $catData = $model->table(PMS_DATABASE. '.product_category_detail')
            ->where([
                'cat_id'    => ['in', $catIds],
                'language'  => ['eq', $language]
            ])
            ->getField('cat_id,cat_name');

        foreach ($skuCatData as $skuId => $item) {
            $result[$skuId] = [
                'cat_level1_name' => isset($catData[$item['cat_level1']]) ? $catData[$item['cat_level1']] : '',
                'cat_level2_name' => isset($catData[$item['cat_level2']]) ? $catData[$item['cat_level2']] : '',
                'cat_level3_name' => isset($catData[$item['cat_level3']]) ? $catData[$item['cat_level3']] : '',
                'cat_level4_name' => isset($catData[$item['cat_level4']]) ? $catData[$item['cat_level4']] : ''
            ];
        }

        return $result;
    }
}