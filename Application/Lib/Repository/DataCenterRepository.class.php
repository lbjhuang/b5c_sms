<?php
/**
 * User: BenHuang
 * Email: benhuang1024@gmail.com
 * Date: 2020/5/14
 * Time: 15:31
 */


class DataCenterRepository extends Repository
{
    /**
     * @var Model
     */
    public $model;

    /**
     * DataCenterRepository constructor.
     */
    public function __construct()
    {
        $this->model = new Model(null, null, 'mt_db_config');
    }


    public function products(array $where, array $limit)
    {
        $where['data_hotness.model_version'] = 'embeddings_nn_3.2';
        $where_str = '1 = 1 ';
        if ($where['keyword']) {
            $where_str .= "AND product_detail.title ~*'.*{$where['keyword']}.*'";
            unset($where['keyword']);
        }
        $sql = $this->model->table('product_detail')
            ->field("
                    product_detail.item_id,
                    product_detail.country_code,
                    product_detail.thumb,
                    product_detail.attributes,
                    product_detail.title,
                    product_detail.cat_level_1 AS category,
                    product_detail.brand,
                    product_detail.platform_id,
                    platform.platform,
                    product_dynamic.price,
                    product_dynamic.reviews,
                    product_dynamic.rating,
                    product_dynamic.optional,
                    data_hotness.date,
                    round( CAST ( float8 ( LEAST ( data_hotness.hotness * 40, 100 ) ) AS NUMERIC ), 2 ) AS hotness                     
                    ")
            ->join("LEFT JOIN data_hotness ON product_detail.item_id = data_hotness.item_id 
                        AND product_detail.country_code = data_hotness.country_code 
                        AND product_detail.platform_id = data_hotness.platform_id")
            ->join("LEFT JOIN platform ON product_detail.platform_id = platform.ID")
            ->join("LEFT JOIN product_dynamic ON product_dynamic.ID = data_hotness.product_dynamic_id 
                        AND product_dynamic.platform_id = platform.id 
                        AND product_dynamic.country_code = product_detail.country_code")
            ->where($where)
            ->where($where_str, null, true);
        $count_sql = clone $sql;
        $res['pages']['count'] = $count_sql->count();
        $res['list'] = $sql
            ->order('data_hotness.hotness desc')
            ->limit($limit[0], $limit[1])
            ->select();
        return $res;
    }

    public function monthlyHotness(array $where)
    {
        $where['data_hotness.model_version'] = 'embeddings_nn_3.2';
        $res = $this->model->table('data_hotness')
            ->field("DATE,
                    round( CAST ( float8 ( LEAST ( data_hotness.hotness * 40, 100 ) ) AS NUMERIC ), 2 ) AS hotness")
            ->where($where)
            ->group('item_id,DATE,hotness')
            ->select();
        return $res;
    }

    public function getMom($item_search_sql_str, $date)
    {
        $sql = "SELECT 
                    data_hotness.item_id,
                    data_hotness.country_code,
                    data_hotness.platform_id,                    
                    product_dynamic.price,
                    product_dynamic.reviews,
                    product_dynamic.rating,
                    product_dynamic.optional
                FROM
                    data_hotness,
                    product_dynamic
                WHERE
                    data_hotness.product_dynamic_id = product_dynamic.id
                AND ($item_search_sql_str)
                AND data_hotness.DATE = '{$date}'";
        return $this->model->query($sql);
    }
}