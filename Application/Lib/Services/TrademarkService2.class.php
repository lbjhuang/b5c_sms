<?php

/**
 * User: shenmo
 * Date: 19/07/25
 * Time: 18:31
 */


/**
 * Class TrademarkService
 */
class TrademarkService extends Service
{
    /**
     * @see TrademarkRepository
     * @var TrademarkRepository
     */
    protected $repository;

    /**
     * TrademarkService constructor.
     */
    public function __construct($external_model = null)
    {
        $this->repository = new TrademarkRepository($external_model);
    }

    /**
     * @param $data
     * @param $is_excel
     *
     * @return array
     * @throws Exception
     */
    public function getTrademarkList($data, $is_excel = null)
    {
        $search_map = [
            'country_code' => 'tb_trademark_detail.country_code',
            'company_code' => 'tb_trademark_detail.company_code',
            'current_state' => "tb_trademark_detail.current_state",
            'current_step' => "tb_trademark_detail.current_step",
            'register_code' => 'tb_trademark_detail.register_code',
            'trademark_name' => 'tb_trademark_base.trademark_name',
            'trademark_id' => 'tb_trademark_base.id',
            'international_type' => 'tb_trademark_detail.international_type',
        ];
        list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map, [], ['trademark_id', 'international_type', 'country_code', 'company_code', 'current_state','current_step']);
        list($res_return['data'], $res_return['pages']) = $this->repository->getTrademarkList($wheres, $limit, $is_excel);
        $res_return['data'] = CodeModel::autoCodeTwoVal($res_return['data'], ['trademark_type']);
        if ($is_excel) {
            return $res_return['data'];
        }
        return $res_return;
    }

    /**
     * @param $data
     * @param $is_excel
     *
     * @return array
     * @throws Exception
     */
    public function getTrademarkListNew($data, $is_excel = null)
    {
        $trademarkBaseList = $this->getTrademarkList($data, 1);
        $all_trademark_ids = array_unique(array_column($trademarkBaseList, 'id'));
        //总记录数
        $pages['total'] = count($all_trademark_ids);
        //获取商标基本信息 分页基准
        $search_map = [
            'country_code' => 'tb_trademark_detail.country_code',
            'company_code' => 'tb_trademark_detail.company_code',
            'current_state' => "tb_trademark_detail.current_state",
            'register_code' => 'tb_trademark_detail.register_code',
            'current_step' => 'tb_trademark_detail.current_step',
            'international_type' => 'tb_trademark_detail.international_type',
        ];
        list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map, [], ['trademark_id', 'country_code', 'company_code', 'current_state', 'current_step']);
        //截取满足条件的商标ID
        //$trademark_ids = array_slice($all_trademark_ids, $limit[0], $limit[1]);
        //$wheres['tb_trademark_base.id'] = ['IN', $trademark_ids];
        $res_return['data'] = $this->repository->getTrademarkListNew($wheres,$limit);

        if ($is_excel) {
            return $res_return['data'];
        }
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];
        $res_return['pages'] = $pages;
        $trademarkList = [];
        foreach ($res_return['data'] as $key => $val) {
            if (!in_array($val['id'], $trademarkList)) {
                $trademarkList[] = $val['id'];
            }
            if ($res_return['data'][$key]['applied_date'] == '0000-00-00') {
                $res_return['data'][$key]['applied_date'] = '';
            }

//            $res_return['data'][$key]['goods'] = (array)$this->formatGoodsData($res_return['data'][$key]['goods']);
            $index = array_search($val['id'], $trademarkList) + 1;
            $res_return['data'][$key]['no'] = $res_return['pages']['current_page'] + $index;
        }
        return $res_return;
    }

    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     * @name 格式化商品/服务数据
     */
    public function formatGoodsData($data)
    {
        if (strpos($data, '；') !== false || strpos($data, '，') !== false || strpos($data, ',') !== false || strpos($data, ';') !== false) {
            $replace_str = [',', '，', '；'];
            $data = trim(str_replace($replace_str, ';', $data), ';');
            $data = explode(';', $data);
        }
        return $data;
    }

    /**
     * @param $trademark_id
     *
     * @return array
     * @throws Exception
     */
    public function getTrademarkDetail($trademark_id)
    {
        return $this->repository->getTrademarkBaseAndDetail($trademark_id);
    }

    /**
     * @param $where
     *
     * @return array
     * @throws Exception
     */
    public function getTrademarkBase($where)
    {
        return $this->repository->getTrademarkBase($where);
    }


    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    public function createTrademarkAndDetail($data)
    {
        return $this->repository->createTrademarkAndDetail($data);
    }


    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    public function createTrademarkAndDetailExport($data)
    {
        return $this->repository->createTrademarkAndDetailExport($data);
    }


    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    public function updateTrademarkAndDetail($data)
    {
        return $this->repository->updateTrademarkAndDetail($data);
    }

    /**
     *
     */
    public function getFinanceExcelAttr()
    {
        $attr_arr = [
            'exp_title',
            'exp_cell_name'
        ];
        array_map(function ($temp) {
            $this->$temp = $this->getFinanceAttr($temp);
        }, $attr_arr);
    }

    /**
     * @param $attr
     *
     * @return mixed
     */
    public function getFinanceAttr($attr)
    {
        return $this->repository->$attr;
    }
}