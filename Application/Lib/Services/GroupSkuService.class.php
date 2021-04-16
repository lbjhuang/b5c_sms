<?php

/**
 * User: yangsu
 * Date: 18/10/24
 * Time: 15:18
 */
class GroupSkuService extends Service
{


    /**
     * @var GroupSkuRepository
     */
    public $GroupSkuRepository;

    /**
     * GroupSkuService constructor.
     */
    public function __construct()
    {
        $this->GroupSkuRepository = new GroupSkuRepository();
    }

    /**
     * @param $data
     */
    public function getListDatas($request_data)
    {
        $where_arr = $this->joinListWhere($request_data['data']);
        $page = $request_data['page'];
        if (empty($page['page_count'])) {
            $page['page_count'] = 10;
        }
        if (empty($page['this_page'])) {
            $per_page = 0;
        } else {
            $per_page = $page['page_count'] * ($page['this_page'] - 1);
        }

        $where_other_arr['list_search'] = true; // 列表查询
        $db_arr = $this->GroupSkuRepository
            ->searchOrderList($where_arr,
                $per_page,
                $page['page_count'],
                $where_other_arr
            );
        $db_arr['this_page'] = $page['page_count'];
        $db_arr['page_count'] = $page['this_page'];
        $db_arr['data'] = SkuModel::getInfo($db_arr['data'],
            'sku_id',
            ['spu_name', 'image_url', 'product_sku'],
            ['spu_name' => 'sku_name', 'image_url' => 'img']);
        $db_arr['data'] = $this->groupBillToVal($db_arr['data']);
        $db_arr['data'] = $this->formatSkuUpcMore($db_arr['data']);

        return $db_arr;
    }

    /**
     * @param $request_data
     *
     * @return array
     */
    public function joinListWhere($temp_data)
    {
        $where_arr = [];
        $table_name = 'tb_wms_group_bill';
        if ($temp_data) {
            $search_arr = ['warehouse_cd', 'sale_team_cd', 'small_sale_team_code', 'small_sale_team_cd'];
            foreach ($temp_data as $key => $value) {
                if ($value && in_array($key, $search_arr)) {
                    if ($key === 'small_sale_team_cd') {
                        $key = 'small_sale_team_code';
                    }
                    $where_arr["{$table_name}.{$key}"] = ['IN', $value];
                } else {
                    switch ($key) {
                        case 'warehouse_type':
                            break;
                        case 'date_type':
                            $where_arr = $this->joinDateType($temp_data, $value, $where_arr, $table_name);
                            break;
                        case 'search_type':
                            if ($temp_data['search_value']) {
                                $where_arr = $this->searchValueToSku($value, $temp_data['search_value'], $table_name, $where_arr);
                            }
                            break;

                    }
                }
            }
        }
        return $where_arr;
    }

    /**
     * @param $value
     * @param $data
     * @param $table_name
     * @param $where_arr
     *
     * @return mixed
     */
    private function searchValueToSku($value, $data, $table_name, $where_arr)
    {
        switch ($value) {
            case 'group_sku_id':
                $where_arr["{$table_name}.sku_id"] = $data;
                break;
            case 'sku_name':
                $where_arr["{$table_name}.sku_id"] = ['IN', SkuModel::titleToSku($data)];
                break;
            case 'upc_id':
                $where_arr["{$table_name}.sku_id"] = ['IN', SkuModel::upcTosku($data, 'loose')];
                break;
            case 'group_bill_code':
                $where_arr["{$table_name}.bill_code"] = ['LIKE', "%{$data}%"];
                break;
        }
        return $where_arr;
    }

    /**
     * @param $request_data
     *
     * @return array
     * @throws Exception
     */
    public function getGroupSkuNum($request_data)
    {
        list($param, $group_relationship) = $this->joinSearchBatchRequest($request_data);
        $params['batchStock'] = $param;
        $api_response_datas = ApiModel::batchStockPost(
            json_encode($params, JSON_UNESCAPED_UNICODE)
        );
        $api_response_datas = DataModel::jsonToArr($api_response_datas);
        if (2000 != $api_response_datas['code']) {
            throw new Exception('获取库存数据错误');
        }
        $data = $request_data;
        $data = CodeModel::autoCodeOneVal($data, ['sale_team_cd', 'warehouse_cd']);
        $data['child_sku'] = $this->assemblyGroupCoffcient($api_response_datas['data']['batchStock'], $group_relationship);
        $data['api_code'] = $api_response_datas['code'];
        $data['api_msg'] = $api_response_datas['msg'];
        $data['api_data'] = $api_response_datas['data'];
        $data['max_num'] = (int)$this->getMaxGroupNum($data['child_sku']);
        $data['group_sku_name'] = SkuModel::getSkusInfo([$data['group_sku_id']])['spu_name'][$data['group_sku_id']];
        return $data;
    }

    public function getGroupCancelSkuNum($request_data)
    {
        $list_request_data = $this->joinListRequest($request_data);
        $where_arr = $this->joinListWhere($list_request_data);
        //$where_other_arr['ascription_store'] = $request_data['ascription_store'];
        $where_other_arr['small_sale_team_code'] = $request_data['small_sale_team_code'] ? $request_data['small_sale_team_code'] : $request_data['small_sale_team_cd'];
        $res = $this->GroupSkuRepository->searchOrderList($where_arr, '', '', $where_other_arr);
        return $res['data'][0]['remaining_num'];
    }

    private function joinListRequest($request_data)
    {
        $temp_data['warehouse_cd'] = [$request_data['warehouse_cd']];
        $temp_data['sale_team_cd'] = [$request_data['sale_team_cd']];
        $temp_data['search_type'] = 'group_sku_id';
        $temp_data['search_value'] = $request_data['group_sku_id'];
        return $temp_data;
    }

    /**
     * @param $child_sku
     *
     * @return mixed
     */
    private function getMaxGroupNum($child_sku)
    {
        $temp_num_arr = [];
        foreach ($child_sku as $value) {
            if (0 == $value['coefficient'] || $value['num'] < 0) {
                continue;
            }
            $temp_num_arr[] = $value['num'] / $value['coefficient'];
        }
        return min($temp_num_arr);
    }

    /**
     * @param $api_datas
     * @param $group_relationship
     */
    private function assemblyGroupCoffcient($api_datas, $group_relationship)
    {
        $api_key_val = array_column($api_datas, 'availableForSale', 'skuId');
        foreach ($group_relationship as $value) {
            $data['sku_id'] = $value['sku_id'];
            $data['coefficient'] = (int)$value['number'];
            $data['num'] = empty($api_key_val[$data['sku_id']]) ? 0 : $api_key_val[$data['sku_id']];
            $datas[] = $data;
        }
        return SkuModel::getInfo($datas);
    }

    /**
     * @param $request_data
     *
     * @return array
     */
    private function joinSearchBatchRequest($request_data)
    {
        $sku_arr = $this->groupSkuToSku($request_data['group_sku_id']);
        if (empty($sku_arr)) {
            throw new Exception('错误的组合商品');
        }
        foreach ($sku_arr as $k => $v) {
            $res['skuId'] = $v['sku_id'];
            $res['saleTeamCode'] = $request_data['sale_team_cd'];
            $res['smallSaleTeamCode'] = $request_data['small_sale_team_cd'];
            $res['deliveryWarehouse'] = $request_data['warehouse_cd'];
            $res['ascriptionStore'] = $request_data['ascription_store'];
            $res['notAssignedSmallSaleTeamCode'] = $request_data['small_sale_team_cd'] ? '0' : '1';
            $res['type'] = '1'; // 只计算自己销售团队的
            $ress[] = $res;
        }
        return [$ress, $sku_arr];
    }

    /**
     * @param $group_sku_id
     *
     * @return mixed
     */
    private function groupSkuToSku($group_sku_id)
    {
        return $this->GroupSkuRepository
            ->getGroupSkuToSku($group_sku_id);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function createGroupOrder($data)
    {
        $params = $this->assemblyGroupCreateData($data);
        $api_response = ApiModel::createGroupSku($params);
        return $api_response;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function assemblyGroupCreateData($data)
    {
        $param['skuId'] = $data['sku_id'];
        $param['warehouseCd'] = $data['warehouse_cd'];
        $param['saleTeamCd'] = $data['sale_team_cd'];
        $param['smallSaleTeamCode'] = $data['smallSaleTeamCode'] ? $data['smallSaleTeamCode'] : $data['small_sale_team_cd'];
        $param['num'] = $data['num'];
        $param['skuJson'] = $this->assemblyGroupSkuJson($data['sku_id']);
        $param['ascriptionStore'] = $data['ascription_store'] ? $data['ascription_store'] : '';
        $param['operatorName'] = DataModel::userNamePinyin();
        return $param;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function assemblyGroupSkuJson($group_sku)
    {
        $sku_arr = $this->GroupSkuRepository->getGroupSkuToSku($group_sku);
        foreach ($sku_arr as $value) {
            $temp_data['childSkuId'] = $value['sku_id'];
            $temp_data['num'] = $value['number'];
            $data[] = $temp_data;
        }
        return json_encode($data);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function cancelGroupOrder($data)
    {
        $params = $this->assemblyGroupCreateData($data);
        unset($params['skuJson']);
        $api_response = ApiModel::cancelGroupSku($params);
        return $api_response;
    }

    /**
     * @param $data
     * @param $audit_status
     *
     * @return mixed
     * @throws Exception
     */
    public function auditGroupOrder($data, $audit_status)
    {
        $this->checkSetGroupBill($data['group_bill_id']);
        switch ($audit_status) {
            case 'adopt':
                /*$adopt_status = 'N002470200';
                $this->checkAuditStatus($data['audit_status'], $adopt_status);*/
                $params = $this->assemblyAuditParams($data);
                $api_response = ApiModel::adoptGroupOrder($params);
                break;
            case 'reject':
                /*$reject_status = 'N002470300';
                $this->checkAuditStatus($data['audit_status'], $reject_status);*/
                $params = $this->assemblyAuditParams($data);
                $api_response = ApiModel::rejectGroupOrder($params);
                break;
        }
        return $api_response;
    }

    /**
     * @param $group_bill_id
     *
     * @throws Exception
     */
    private function checkSetGroupBill($group_bill_id)
    {
        if (!$this->GroupSkuRepository->getGroupBillIdCount($group_bill_id)) {
            throw new Exception('无效的组合单据 ID');
        }
    }


    /**
     * @param $request_audit_status
     * @param $need_audit_status
     *
     * @throws Exception
     */
    private function checkAuditStatus($request_audit_status, $need_audit_status)
    {
        if ($request_audit_status != $need_audit_status) {
            throw new Exception('审核状态异常');
        }
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function assemblyAuditParams($data)
    {
        $res['billId'] = $data['group_bill_id'];
        $res['auditStatus'] = $data['audit_status'];
        $res['operatorName'] = DataModel::userNamePinyin();
        return $res;
    }

    /**
     * @param $request_data
     *
     * @return array|mixed
     */
    public function getGroupSkuDetaileds($request_data)
    {
        if ($request_data['list_search'] && is_array($request_data['list_search'])) {
            $where_arr = $this->joinListWhere($request_data['list_search']);
            foreach ($where_arr as $temp_key => $temp_value) {
                switch ($temp_key) {
                    case 'tb_wms_group_bill.bill_code':
                        $request_data['bill_code'] = $where_arr['tb_wms_group_bill.bill_code'];
                        break;
                    case  'tb_wms_group_bill.created_at':
                        $request_data = $this->timeToOrmTime($request_data, $where_arr, 'created_at', 'tb_wms_group_bill');
                        break;
                    case  'tb_wms_group_bill.audit_time':
                        $request_data = $this->timeToOrmTime($request_data, $where_arr, 'audit_time', 'tb_wms_group_bill');
                        break;

                }
            }
        }
        $res = $this->GroupSkuRepository
            ->getDetaileds($request_data);
        $res = $this->groupBillToVal($res);
        return $res;
    }

    /**
     * @param $res
     *
     * @return mixed
     */
    private function typeToVal($res)
    {
        $type_arr = [
            'group_type' => ['组合打包', '组合拆包'],
            'is_audit' => ['否', '是'],
        ];
        foreach ($type_arr as $key => $value) {
            if (isset($res[$key])) {
                $res[$key . '_val'] = $value[$res[$key]];
            }
        }
        return $res;
    }

    /**
     * @param $res
     * @param $value
     *
     * @return array|mixed
     */
    private function groupBillToVal($res)
    {
        foreach ($res as &$value) {
            $value = $this->typeToVal($value);
        }
        unset($value);
        $res = CodeModel::autoCodeTwoVal($res, ['warehouse_cd', 'sale_team_cd', 'audit_status', 'small_sale_team_cd', 'small_sale_team_code']);
        return $res;
    }

    /**
     * @param $request_data
     * @param $value
     * @param $where_arr
     * @param $table_name
     *
     * @return mixed
     */
    private function joinDateType($temp_data, $value, $where_arr, $table_name)
    {
        if ($value) {
            switch ($value) {
                case 'created_at':
                    $where_arr = WhereModel::getBetweenDate(
                        $temp_data['time_begin'],
                        $temp_data['time_end'],
                        $where_arr,
                        "{$table_name}.created_at"
                    );
                    break;
                case 'audit_time':
                    $where_arr = WhereModel::getBetweenDate(
                        $temp_data['time_begin'],
                        $temp_data['time_end'],
                        $where_arr,
                        "{$table_name}.audit_time"
                    );
                    break;
            }
        }
        return $where_arr;
    }

    /**
     * @param $require
     */
    public function outputExcel($require)
    {
        $where_arr = $this->joinExcelWhere($require);
        $exp_title = '单据明细';
        $exp_cell_name = $this->GroupSkuRepository
            ->order_list_column;
        $exp_table_data = $this->GroupSkuRepository
            ->listExcelData($where_arr);
        $exp_table_data = SkuModel::getInfo($exp_table_data,
            'sku_id',
            ['spu_name', 'image_url', 'product_sku'],
            ['spu_name' => 'sku_name', 'image_url' => 'img']);
        $exp_table_data = $this->groupBillToVal($exp_table_data);
        $exp_table_data = $this->formatSkuUpcMore($exp_table_data);

        $this->joinUpcInfo($exp_table_data);
        parent::outputExcel(
            $exp_title,
            $exp_cell_name,
            $exp_table_data
        );
    }

    /**
     * @param $require
     *
     * @return array
     */
    public function joinExcelWhere($require)
    {
        $where_arr = $this->joinListWhere($require);
        return $where_arr;
    }

    /**
     * @param $exp_table_data
     */
    private function joinUpcInfo($exp_table_data)
    {
        array_filter($exp_table_data, function (&$temp) {
            $temp['upc_id'] = $temp['product_sku']['upc_id'];
            return $temp;
        });
    }

    public function formatSkuUpcMore(array $list) {

        foreach ($list as &$item) {
            if(isset($item['product_sku']) && $item['product_sku']['upc_more']) {
                $upc_more_arr = explode(',', $item['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $item['product_sku']['upc_id']);
                $item['product_sku']['upc_id'] = implode(",\r\n", $upc_more_arr);
            }
        }
        return $list;
    }

}