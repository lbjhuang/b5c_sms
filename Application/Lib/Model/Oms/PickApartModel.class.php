<?php

/**
 * User: huanzhu
 * Date: 18/3/16
 * Time: 18:25
 */
class PickApartModel extends Model
{

    const  WAIT_PICKING_ORD = 'N001820600';     //待分拣状态
    const  WAIT_CHECK_ORD = 'N001820700'; //待核单状态
    private $index = 'es_order';
    private $es_model = '';
    private static $wms_warehouse = '';
    private $ms_ord = '';
    private $op_order = '';
    private $type = 'es_order';
    protected $trueTableName = 'tb_op_order';
    const APART_PROCESS = 'N002060200';     //分拣流程
    private $ord_package = '';
    private $order_log_model = "";


    /**
     * @param  search_condition 搜索条件
     * @return data  返回数据
     */
    private static function format_es_data($data = array())
    {
        $datas = $data['hits']['hits'];

        $func = function ($order_data) {
            return $order_data['_source'];
        };
        $res_data = array_map($func, $datas);
        return $res_data;
    }

    public function _initialize()
    {
        $this->es_model = new ESClientModel();
        $this->op_order = M("op_order", "tb_");
        $this->ord_package = M("ms_ord_package", "tb_");
        self::$wms_warehouse = M("wms_warehouse", "tb_");
        $this->ms_ord = M("ms_ord", "tb_");
        $this->order_log_model = M("ms_ord_hist", "sms_");
    }

    /**
     * @param  array $es_data 原始数据
     * @return arrat  $data  列表数据
     */
    private static function map_list_data($es_data = array())
    {
        foreach ($es_data as $es) {
            $editrInfo = json_decode($es['findOrderJson'], true);
            $editrInfo = $editrInfo['data']['warehouse'];
            foreach ($editrInfo as $info) {
                if ($es['warehouse'] == $info['cd']) {
                    $edit_info = $info['lgtModel'];
                }
            }
            $is_qoo = 0;
            if (in_array($es['platCd'],['N000830300','N000830400','N000830500']))  $is_qoo = 1;
            $map_list_data[] = [
                'platName'                 => $es['platName'],
                'platCd'                   => $es['platCd'],
                'storeName'                => $es['store']['storeName'],
                'b5cOrderNo'               => $es['b5cOrderNo'],
                'third_id'                 => $es['orderId'],
                'orderNo'                  => $es['orderNo'],
                'pickingNo'                => $es['msOrd'][0]['pickingNo'],
                'skuid'                    => self::get_skuid($es['ordGudsOpt']),
                'test'                     => $es['ordGudsOpt'],
                'gudsTypeNm'               => $es['gudsTypeNm'],
                'saleTeamCdNm'             => $es['store']['saleTeamCdNm'],
                'saleTeamCd'               => $es['store']['saleTeamCd'],
                'orderTime'                => $es['orderTime'],
                'warehouse'                => $es['warehouse'],
                'warehouseNm'              => $es['warehouseNm'],
                'logisticCd'               => $es['logisticCd'],
                'sendOrdStatus'            => $es['sendOrdStatus'],
                'logisticCdNm'             => $es['logisticCdNm'],
                'logisticModelIdNm'        => $es['logisticModelIdNm'],
                'logisticsSingleStatuCdNm' => $es['logisticsSingleStatuCdNm'],
                'logisticModelId'          => $es['logisticModelId'],
                'surfaceWayGetCd'          => $es['surfaceWayGetCd'],
                // 'surfaceWayGet'=>OrderPresentModel::change_code($es['surfaceWayGetCd']),
                "surfaceWayGetCdNm"        => $es['surfaceWayGetCdNm'],
                'trackingNumber'           => $es['ordPackage'][0]['trackingNumber'],
                'remarkMsg'                => $es['remarkMsg'],
                'use_remarks'              => $es ['shippingMsg'],
                //   'apart_error'=>$es['msOrd.msgCd1'],
                'ordPackage'               => $es['ordPackage'],
                'wholeStatusCd'            => $es['msOrd'][0]['wholeStatusCd'],
                'childOrderId'             => $es['childOrderId'],
                'json_info'                => $edit_info ? $edit_info : null,
                'operationType'            => $es['store']['operationType'],
                'fright'                   => $es['sendFreight'],
                'history_msg'              => OrderPresentModel::change_code($es['msOrd'][0]['msgCd1']),
                "after_sale_type"          => $es['afterSaleType'],
                'orderTime'                => $es['orderTime'],
                'payment_time'             => $es ['orderPayTime'],
                "shipping_time"            => $es['shippingTime'],
                "send_ord_time"            => $es['sendOrdTime'],
                "sendout_time"             => $es['msOrd'][0]['sendoutTime'],
                "packing_no"               => $es['packingNo'],
                "is_qoo"                   => $is_qoo,
                "addressUserName"          => $es['addressUserName'],
                "is_shopnc_order"          => strtolower($es['store']['beanCd']) == 'shopnc' ? 1 : 0,
            ];
        }

        return $map_list_data;
    }


    /**
     * @param  array $search_condition [搜索條件]
     * @return [response]              es_query
     */
    private function es_search_conditon($search_condition = array())
    {
        $response['index'] = $this->index;
        $response['type'] = $this->type;
        $from = ($search_condition['page_now'] - 1) * $search_condition['page_size'];  //分頁
        $response['body']['query']['bool']['must'] = self::choose_search_condition($search_condition);
        $response['body']['query']['bool']['must_not'][] = ['exists' => ['field' => 'childOrderId']];  //不能存在子单
        if (isset($search_condition['remarkMsg']) && $search_condition['remarkMsg'] == 1) {
            $response['body']['query']['bool']['must'][]['query_string']['query'] = ' remarkMsg:?* OR shippingMsg:?* ';
        }
        $timeField = self::choose_time_condtion($search_condition);
        empty($timeField) or $response['body']['query']['bool']['filter']['range'] = $timeField;
        $orField = [
            ['match' => ['bwcOrderStatus.keyword' => 'N000550400']], ['match' => ['bwcOrderStatus.keyword' => 'N000550500']],
            ['match' => ['bwcOrderStatus.keyword' => 'N000550600']], ['match' => ['bwcOrderStatus.keyword' => 'N000550800']],
        ];

        empty($search_condition['after_sale_type']) or $response['body']['query']['bool']['must'][]['query_string']['query'] = "afterSaleType:{$search_condition['after_sale_type']}";

        $response['body']['query']['bool']['should'] = $orField;
        $response['body']['from'] = $from ? $from : 0;  //开始
        $response['body']['size'] = $search_condition['page_size'] ? $search_condition['page_size'] : 20;  //结束
        !empty($search_condition['sort_type']) or $search_condition['sort_type'] = 'orderTime';
        $sort_sc = 'desc';
        if ($search_condition['sort_type'] == 'remarkMsgPinyin') $sort_sc = 'asc';
        $response['body']['sort'] = [$search_condition['sort_type'] => $sort_sc];
        //var_dump(json_encode($response['body']));die;
        return $response;
    }

    /**
     * @param  array $search_condition [搜索条件]
     * @return res_data                 [返回数据列表]
     */
    public function lists($search_condition = array())
    {
        $search_assembly_condition = $this->es_search_conditon($search_condition);
        //$search_assembly_condition = $this->dateIntervalCheck($search_assembly_condition);
        $data = $this->es_model->search($search_assembly_condition);
        $totals = $data['hits']['total'];
        $es_data = self::format_es_data($this->es_model->search($search_assembly_condition));
        $res_data = self::map_list_data($es_data);
        $data['res_data'] = $res_data;
        $data['totals'] = $totals;
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function dateIntervalCheck($data)
    {
        $orderTime = $data['body']['query']['bool']['filter']['range']['orderTime'];
        if ($orderTime['lte'] && $orderTime['gte'] == $orderTime['lte']) $data['body']['query']['bool']['filter']['range']['orderTime']['lte'] = substr($orderTime['lte'], 0, -1) . '0';
        return $data;
    }

    /**
     * @param  array $guds 商品信息
     * @return skuid        商品编码
     */
    private static function get_skuid($guds = array())
    {
        $func = function ($guds_info) {
            return $guds_info['gudsOptId'];
        };
        $skuid = array_map($func, $guds);
        return $skuid;
    }

    /**
     * @param  array $search_condition 搜索条件
     * @return array   field            拼接条件
     */
    private static function choose_search_condition($search_condition = array())
    {
        $field = [];

        /*固定条件*/
        $field[] = ['exists' => ['field' => 'b5cOrderNo']];
        //$field[] = ['exists'=>['field' => 'msOrd']];
        $field[] = ['match' => ['msOrd.wholeStatusCd' => self::WAIT_PICKING_ORD]];   //派单状态为待分拣
        /*搜索条件*/
        empty($search_condition['apart_error']) or $field[] = ["exists" => ["field" => "msOrd.msgCd1"]];
        empty($search_condition['surface_method']) or $field[] = ["match" => ['surfaceWayGetCd.keyword' => $search_condition['surface_method']]];
        empty($search_condition['plat_cd']) or $field[] = ['terms' => ['platCd.keyword' => $search_condition['plat_cd']]];
        empty($search_condition['package_type']) or $field[] = ['terms' => ['gudsType' => $search_condition['package_type']]];
        empty($search_condition['remark_msg']) or $field[] = ['exists' => ['field' => 'remarkMsg']];  //是否有备注
        empty($search_condition['country_id']) or $field[] = ['terms' => ['addressUserCountryId.keyword' => $search_condition['country_id']]];
        empty($search_condition['store_id']) or $field[] = ['terms' => ['storeId.keyword' => $search_condition['store_id']]];
        empty($search_condition['warehouse_cd']) or $field[] = ['terms' => ['warehouse.keyword' => $search_condition['warehouse_cd']]];
        empty($search_condition['logistics_company_cd']) or $field[] = ['terms' => ['logisticCd.keyword' => $search_condition['logistics_company_cd']]];
        empty($search_condition['logistics_mode']) or $field[] = ['terms' => ['logisticModelId.keyword' => $search_condition['logistics_mode']]];
        empty($search_condition['sale_team_cd']) or $field[] = ['terms' => ['store.saleTeamCd.keyword' => $search_condition['sale_team_cd']]];
        if (in_array($search_condition['search_condition'],['userEmail'])) {
            $field[] = ['terms' => ['userEmail.keyword' => [$search_condition['search_value']]]];
        }
        $condition = $search_condition;
        if (($condition['search_condition'] == 'b5cOrderNo' || $condition['search_condition'] == 'orderNo') && strpos($condition['search_value'], ',')) {
            $k_v = OrderEsModel::orderAllSearch($condition['search_value'], $condition['search_condition']);
            $field[] = $k_v;

        } else {
            if(!in_array($search_condition['search_condition'],['userEmail'])){
                (empty($search_condition['search_condition']) or empty($search_condition['search_value'])) or $field[] = ['wildcard' => [$search_condition['search_condition'] . ".keyword" => "*" . $search_condition['search_value'] . "*"]];
            }
        }
        unset($condition);
        return $field;
    }

    /**
     * @param  [type] $condition 时间参数
     * @return data              返回结果
     */
    private static function choose_time_condtion($condition)
    {
        if ($condition['search_time_type'] && $condition['start_time'] && $condition['end_time']) {
            $timeRange = [
                $condition['search_time_type'] => [
                    'gte' => strtotime($condition['start_time']) . '000',
                    'lte' => strtotime($condition['end_time']) . '000',
                ]
            ];
        } else if ($condition['time_type'] && $condition['start_time'] && $condition['end_time']) {
            $timeRange = [
                $condition['time_type'] => [
                    'gte' => strtotime($condition['start_time']) . '000',
                    'lte' => strtotime($condition['end_time']) . '000',
                ]
            ];
        }
        $timeRange = $timeRange ? $timeRange : [];
        return $timeRange;
    }

    /**
     * @param  string $order_id b5c订单id
     * @return [type]           [description]
     */
    public function get_surface_data($order_data = array())
    {
        $plat_cd = array_column($order_data, "plat_cd");
        $order_id = array_column($order_data, "order_id");
        $where_package['ORD_ID'] = array("in", $order_id);
        $where_op['ORDER_ID'] = array("in", $order_id);        //op_order表条件
        $where_op['PLAT_CD'] = array("in", $plat_cd);
        $where_ms['THIRD_ORDER_ID'] = array("in", $order_id);   //ms_order表条件
        $where_ms['PLAT_FORM'] = array("in", $plat_cd);
        $surface_data = $this->ord_package->field("template,template_type")->where($where_package)->select();
        foreach ($surface_data as &$type_file) {
            if ($type_file['template_type'] == '3') {
                $direct_down_filename = ATTACHMENT_DIR_LOGISTIC . $type_file['template'];
                $type_file['template'] = base64_encode($direct_down_filename);
            }
            if ($type_file['template_type'] == '1') {
                $type_file['template'] = json_decode($type_file['template']);
                if (is_array($type_file['template'])) {
                    $type_file['template'] = $type_file['template'][0];
                } else {
                    $type_file['template'] = $type_file['template'];
                }
            }
        }
        $op_order_data = $this->field("WAREHOUSE")->where($where_op)->select();
        $res = self::check_data($surface_data, $op_order_data);
        $status['WHOLE_STATUS_CD'] = self::WAIT_CHECK_ORD;
        /*if (!*/  //$res_update = $this->ms_ord->where($where_ms)->save($status);  //  ) {
        /*$data = array("code"=>500,"msg"=>L("核单状态更新失败"));
       return $data;*/
        // }
        return $res;
    }

    /**
     * @param  string $order_id b5c订单id
     * @return [type]           [description]
     */
    public function get_surface_simple_data($order_data = array())
    {
        $plat_cd = $_GET['plat_cd'];
        $order_id = $_GET['order_id'];
        $where_package['ORD_ID'] = $order_id;
        $where_op['ORDER_ID'] = $order_id;        //op_order表条件
        $where_op['PLAT_CD'] = $plat_cd;
        $where_ms['THIRD_ORDER_ID'] = array("in", $order_id);   //ms_order表条件
        $where_ms['PLAT_FORM'] = array("in", $plat_cd);
        $op_order_data = $this->field("WAREHOUSE")->where($where_op)->select();
        $res = self::check_data($surface_data, $op_order_data);
        if (!is_null($res['code'])) return $res;
        $surface_data = $this->ord_package->field("template,template_type")->where($where_package)->find();
        if ($surface_data['template_type'] == '2' or $surface_data['template_type'] == '3') {
            OrderPresentModel::get_log_data($order_id, '面单打印成功', $plat_cd, 'N001820600');
            $res = $this->base64($surface_data, $where_ms);
            if ($res['code']) return $res['msg'];
        } elseif ($surface_data['template_type'] == '1') {
            $surface_data['template'] = json_decode($surface_data['template']);
            if (is_array($surface_data['template'])) {
                $surface_data['template'] = $surface_data['template'][0];
            } else {
                $surface_data['template'] = $surface_data['template'];
            }
            OrderPresentModel::get_log_data($order_id, '面单打印成功', $plat_cd, 'N001820600');
            $printAction = new PickApartAction();
            $printAction->print_change_status($where_ms, 'model');
            include './Application/Tpl/Oms/Public/print/print.html';
            die;
        } else {
            $data['MSG_CD1'] = 'N002090700';
            $data['WHOLE_STATUS_CD'] = self::WAIT_PICKING_ORD;
            $surface_status = $this->field("LOGISTICS_SINGLE_STATU_CD")->where($where_op)->find();
            $history_res = $this->ms_ord->where($where_ms)->save($data);
            OrderPresentModel::get_log_data($order_id, '面单打印失败', $plat_cd, 'N001820600');
            return OrderPresentModel::change_code($surface_status['LOGISTICS_SINGLE_STATU_CD']);
        }
    }


    /**
     * @param  输出pdf文件
     * @return [type]      [description]
     */
    public function base64($res = array(), $where_ms = array())
    {
        import('ORG.Net.Http');
        $direct_down_filename = ATTACHMENT_DIR_LOGISTIC . $res['template'];
        $printAction = new PickApartAction();
        if ($res['template_type'] == '3') {
            $type = mime_content_type($direct_down_filename);
            if ($type == 'application/pdf' and file_exists($direct_down_filename)) {   //拿到存在的pdf文件
                $printAction->print_change_status($where_ms, 'model');
                Header("Content-type:application/pdf");//直接输出显示pdf格式图片
                echo file_get_contents($direct_down_filename);
                exit();
            } else {
                $data = array("code" => 500, "msg" => L("面单文件路径不存在"));
                return $data;
            }
        } else {
            Header("Content-type:application/pdf");//直接输出显示pdf格式图片
            $change_status_res = $printAction->print_change_status($where_ms, 'model');

            $pdf = base64_decode($res['template']);
            echo $pdf;
            exit();
        }
    }

    /**
     * 打印面单状态判断
     * @return [type] [description]
     */
    private static function check_data($surface_data = array(), $op_order_data = array())
    {

        foreach ($op_order_data as $surface) {
            if (is_null($surface['WAREHOUSE'])) {
                $data = array("code" => 500, "msg" => L("订单无下发仓库,不在待打印面单状态"));
                return $data;
            }
            $job_content = self::$wms_warehouse->field("job_content")->where("CD='{$surface['WAREHOUSE']}'")->find();
            $job_content_arr = explode(",", $job_content['job_content']);
            if (!in_array(self::APART_PROCESS, $job_content_arr)) {
                //$data = array("code"=>500,"msg"=>L("订单下发仓库未配置分拣流程"));
                //return $data;
            }
        }
        $warehouse = array_unique(array_column($op_order_data, "WAREHOUSE"));
        if (count($warehouse) > 1) {
            $data = array("code" => 500, "msg" => L("订单不属于一个仓库，不可打印面单"));
            return $data;
        }
        return $surface_data;
    }


}