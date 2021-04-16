<?php
/**
 *
 */

/**
 * Class DataCenterService
 */
class DataCenterService extends Service
{
    /**
     * @var DataCenterRepository
     */
    protected $repository;

    /**
     * DataCenterService constructor.
     */
    public function __construct()
    {
        $this->repository = new DataCenterRepository();
    }

    public function products(array $request_data)
    {

        $request_data = $this->joinProductSearch($request_data);
        $pages = [
            'per_page' => $request_data['pageSize'],
            'current_page' => $request_data['page']
        ];
        $this->repository = new DataCenterRepository();
        $search_map = [
            'categoryId' => 'product_detail.cat_level_1',
            'date' => 'data_hotness.date',
            'month' => 'month',
            'platforms' => 'product_detail.platform_id',
            'country_code' => 'product_detail.country_code',
        ];
        $search_accurate_arr = ['date'];
        list($where, $limit) = WhereModel::joinSearchTemp(['search' => $request_data, 'pages' => $pages], $search_map, [], $search_accurate_arr);
        if ($request_data['keyword']) {
            $where['keyword'] = $request_data['keyword'];
        }
        $res = $this->repository->products($where, $limit);
        list($res, $item_search_sql_str) = $this->productOutputFormat($res);
        $res['list'] = $this->pushMom(
            $res['list'],
            $this->getMom($item_search_sql_str, $where['data_hotness.date'])
        );
        $res['pages']['per_page'] = $pages['per_page'];
        $res['pages']['current_page'] = $pages['current_page'];
        return $res;
    }

    private function pushMom($data, $moms)
    {
        foreach ($data as $key => $datum) {
            $mom = $moms[$datum['item_id'] . $datum['country_code'] . $datum['platform_id']];
            if (empty($mom['min_bsr'])) {
                $data[$key]['bsr_mom'] = '-';
                $data[$key]['bsr_mom_rate'] = '-';
            } elseif (0 === $mom['min_bsr']) {
                $data[$key]['bsr_mom'] = '0';
                $data[$key]['bsr_mom_rate'] = '-';
            } else {
                $data[$key]['bsr_mom'] = -((float)$datum['min_bsr']['rank'] - (float)$mom['min_bsr']['rank']);
                $data[$key]['bsr_mom_rate'] = number_format((abs($datum['min_bsr']['rank'] - $mom['min_bsr']['rank']) / $mom['min_bsr']['rank']) * 100,2);
                $data[$key]['bsr_mom_rate'] .= '%';
            }
            $data[$key]['review_mom'] = $datum['reviews'] - $mom['reviews'];
            $data[$key]['rating_mom'] = $datum['rating'] - $mom['rating'];
        }
        return $data;
    }

    /**
     * @param $item_search_sql_str
     * @param $data_hotness_date
     * @param array $res
     *
     * @return array
     */
    private function getMom($item_search_sql_str, $data_hotness_date, $res = [])
    {
        $temp_res = $this->repository->getMom(
            $item_search_sql_str,
            date('Y-m-d', strtotime($data_hotness_date) - 24 * 60 * 60)
        );
        foreach ($temp_res as $temp_re) {
            $temp_optional = json_decode($temp_re['optional'], true);
            $temp_re['min_bsr'] = $this->getMinBSR($temp_optional['BestSellersTable']);
            $res[$temp_re['item_id'] . $temp_re['country_code'] . $temp_re['platform_id']] = $temp_re;
        }
        return $res;
    }

    /**
     * @param array $temp_optional
     *
     * @return array
     */
    private function getMinBSR(array $temp_optional)
    {
        $temp_flip = array_flip($temp_optional);
        $rank = min(array_keys($temp_flip));
        $response = [
            "rank" => $rank,
            "name" => $temp_flip[$rank],
        ];
        return $response;
    }

    public function getMonthlyHotness($request)
    {
        $where['item_id'] = $request['item_id'];
        $where['platform_id'] = $request['platform_id'];
        $where['country_code'] = $request['country_code'];
        $act_date = date('Y-m-d', strtotime($request['date']) - (29 * 86400));
        $end_date = $request['date'];
        $where['data_hotness.date'] = ['BETWEEN', [$act_date, $end_date]];
        $DataCenterRepository = new DataCenterRepository();
        $res_monthly_hotness = $DataCenterRepository->monthlyHotness($where);
        $date_interval = DateModel::getDateFromRange($act_date, $end_date);
        $date_key_map = array_combine(array_column($res_monthly_hotness, 'date'), array_values($res_monthly_hotness));
        foreach ($date_interval as $value) {
            if (empty($date_key_map[$value])) {
                $date_key_map[$value] = [
                    'date' => $value,
                    'hotness' => 0,
                ];
            }
        }
        ksort($date_key_map);
        return array_values($date_key_map);
    }

    /**
     * @param $res
     * @param null $item_search_sql_str
     *
     * @return array
     */
    public function productOutputFormat($res, $item_search_sql_str = null)
    {
        foreach (DataModel::toYield($res['list']) as $key => $value) {
            $temp_optional = json_decode($value['optional'], true);
            $temp_attributes = json_decode($value['attributes'], true);
            $res['list'][$key]['bsr_list'] = $temp_optional['BestSellersTable'];
            $res['list'][$key]['min_bsr'] = $this->getMinBSR($temp_optional['BestSellersTable']);
            $res['list'][$key]['sales'] = number_format($temp_optional['discount']['discountPrice'], 2, 4);
            if (true === $temp_optional['fba']) {
                $res['list'][$key]['fulfillment'] = 'FBA';
            } elseif (false === $temp_optional['fba']) {
                $res['list'][$key]['fulfillment'] = 'FBM';
            } else {
                $res['list'][$key]['fulfillment'] = '-';
            }
            if ($temp_attributes['details']['Date First Available']) {
                $res['list'][$key]['pub_date'] = $temp_attributes['details']['Date First Available'];
            } else {
                $res['list'][$key]['pub_date'] = null;
            }
            $res['list'][$key]['store_link'] = $temp_attributes['link'];
            unset($res['list'][$key]['optional'], $res['list'][$key]['attributes']);
            $item_search_sql_str .= "OR (data_hotness.item_id = '{$res['list'][$key]['item_id']}' AND data_hotness.country_code = '{$res['list'][$key]['country_code']}' AND data_hotness.platform_id = '{$res['list'][$key]['platform_id']}')";
        }
        return [$res, trim($item_search_sql_str, 'OR')];
    }

    /**
     * @param array $request_data
     *
     * @return array
     */
    public function joinProductSearch(array $request_data)
    {
        $request_data['keyword'] = trim($request_data['keyword']);
        if ($request_data['platforms']) {
            $request_data['platforms'] = WhereModel::stringToInArray(($request_data['platforms']));
        }
        if ($request_data['country_code']) {
            $request_data['country_code'] = WhereModel::stringToInArray(strtolower($request_data['country_code']));
        }
        if ($request_data['categoryId']) {
            $request_data['categoryId'] = WhereModel::stringToInArray(($request_data['categoryId']));
            foreach ($request_data['categoryId'] as &$value) {
                switch ($value) {
                    case 3:
                        $value = 'Collectibles';
                        break;
                    case 1089:
                        $value = 'Sports & Outdoors';
                        break;
                }
            }
        }
        return $request_data;
    }
}


