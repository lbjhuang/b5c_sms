<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/11
 * Time: 16:41
 */

class StockExtendModel extends BaseModel
{
    protected $trueTableName = 'tb_ms_user_area';

    /**
     * 获得所有的国家
     * @return array 返回全球所有的国家
     */
    public function getAllCountry()
    {
        $conditions = [
            'area_type' => 1
        ];

        return $this->where($conditions)
                    ->field('id, zh_name, two_char, three_char, en_name, rank')
                    ->order('rank asc')
                    ->select();
    }

    /**
     * 过滤掉存在的国家
     * @param array $existing  已支持的收货国家
     * @param array $countries  全球所有国家
     * @return array $countries  过滤后剩余的国家
     */
    public function filterExistingCountry($existing, $countries)
    {
        $existingIds = array_column($existing, 'area');

        foreach ($countries as $k => &$v) {
            if (in_array($v ['id'], $existingIds)) unset($countries [$k]);
            unset($v);
        }

        return array_merge($countries, []);
    }
}