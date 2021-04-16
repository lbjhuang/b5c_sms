<?php

/**
 * User: yangsu
 * Date: 18/2/24
 * Time: 11:08
 */
include_once "BasisModel.php";

class OrderModel extends BasisModel
{
    /**
     * @return array
     */
    protected static $logisics_key = [
        'ORDER_ID' => 'A',
        'PLAT_CD' => 'B',
        'WAREHOUSE' => 'C',
        'logistic_cd' => 'D',
        'logistic_model_id' => 'E',
        'SURFACE_WAY_GET_CD' => 'F',
        'TRACKING_NUMBER' => 'G'
    ];

    /**
     * @return array
     */
    protected static $logisics_key_name = [
        'ORDER_ID' => '第三方订单号',
        'PLAT_CD' => '平台',
        'WAREHOUSE' => '下发仓库',
        'logistic_cd' => '物流公司',
        'logistic_model_id' => '物流方式',
        'SURFACE_WAY_GET_CD' => '面单获取方式',
        'TRACKING_NUMBER' => '运单号'
    ];
    /**
     * @var array
     */
    protected static $null_check = ['TRACKING_NUMBER'];

    /**
     * @var array
     */
    protected static $logisics_sort_key = [
        'B5C_ORDER_ID' => 'A',
        'TRACKING_NUMBER' => 'B'
    ];

    /**
     * @var array
     */
    protected static $logisics_sort_key_name = [
        'B5C_ORDER_ID' => '订单号',
        'TRACKING_NUMBER' => '运单号'
    ];

    /**
     * @name 导入运单
     * @var array
     */
    protected static $logisics_other_key = [
        'ORDER_ID' => 'A',
        'PLAT_CD' => 'B',
        'TRACKING_NUMBER' => 'C'
    ];

    /**
     * @name 导入运单
     * @var array
     */
    protected static $logisics_other_key_name = [
        'ORDER_ID' => '第三方订单号',
        'PLAT_CD' => '平台',
        'TRACKING_NUMBER' => '运单号'
    ];

    protected static $BWC_ORDER_STATUS_LIST = [

    ];
    // 订单列表根据各种条件获取对应的订单总金额（美元）
    public function getAmount($post_data = [])
    {
        $map = $this->getAmountWhere($post_data->data);
        if ($map['t.PARENT_ORDER_ID'][0] == 'exp' && $map['t.PARENT_ORDER_ID'][1] == 'is null' && count($map) == 1) { // 初始统计金额使用非实时缓存，以缩短请求时间
            $res = RedisModel::get_key('order_list_origin_amount', null);
            ELog::add(['msg'=>'获取订单列表初始金额','request'=>$map,'response'=>$res],ELog::INFO);
            if (!empty($res)) return $res;
        }
        $Model = new SlaveModel();
        $total = $Model->table('tb_op_order')
               ->alias('t')
               ->field('PAY_TOTAL_PRICE_DOLLAR')
               ->join('left join tb_op_order_guds as oog on t.ORDER_ID = oog.ORDER_ID AND t.PLAT_CD = oog.PLAT_CD')
               ->join('left join tb_ms_ord_package as mop on t.ORDER_ID = mop.ORD_ID AND t.PLAT_CD = mop.plat_cd')
               ->join('left join ' . PMS_DATABASE . '.product_sku as ps on oog.B5C_SKU_ID = ps.sku_id ')
               ->join('left join tb_ms_store AS ms on ms.ID = t.store_id')
               ->join('left join tb_ms_ord AS mo on t.B5C_ORDER_NO = mo.ORD_ID')
               ->where($map)->group('t.ORDER_ID, t.PLAT_CD')->select(); // 不直接用sum原因是tb_op_order_guds会有多个重复值
        $res = '0';
        //echo M()->_sql();die;
        if ($total) {
           $total = array_column($total, 'PAY_TOTAL_PRICE_DOLLAR');
           $res = array_sum($total);
        } 
        
        return $res;
    }

    public function ordAndInOr($post_data, $where_arr)
    {
        $where = [];
        // 一种是数组，一种是字符串
        $post_data = json_decode(json_encode($post_data), true);
        foreach ($where_arr as $key => $value) {
            if (isset($post_data[$key])) {
                if (is_array($post_data[$key]) && !empty($post_data[$key])) {
                    if ($key == 'sales_team') {
                        foreach ($post_data[$key] as $k => $v) {
                            $where['_string'] .= " FIND_IN_SET('".$v."',{$value}) OR";
                        }
                        $where['_string'] = rtrim($where['_string'], 'OR');
                    } else {
                        $where[$value] = array('in', $post_data[$key]);
                    }
                } else {
                    if ($key == 'is_apply_after_sale' && strval($post_data[$key]) === '0' ) {
                        $where[$value] = array('EXP', 'is null');
                    }
                    if (!empty($post_data[$key])) {
                        $where[$value] = array('eq', $post_data[$key]);
                    }
                }
            }
        }
        return $where;

    }

    // 订单列表条件筛选
    public function getAmountWhere($post_data = [])
    {
        // tb_ms_ord_package mop
        // tb_ms_store  ms
    // product_detail pd
        // tb_op_order_guds oog
        // tb_ms_ord mo

        $es_where_arr = [
            'platform' => 't.PLAT_CD',
            'order_status' => 't.BWC_ORDER_STATUS',
            'dispatch_status' => 't.SEND_ORD_STATUS',
            'order_source_status' => 't.order_origin',
            'logistics_status' => 'mop.LOGISTIC_STATUS',
            'country' => 't.ADDRESS_USER_COUNTRY_ID',
            'shop' => 't.STORE_ID',
            'warehouse' => 't.WAREHOUSE',
            'logistics_company' => 't.logistic_cd',
            'logistics_method' => 't.logistic_model_id',
            'sales_team' => 'ms.SALE_TEAM_CD',
            'is_remote_area_val' => 't.is_remote_area',
            'is_apply_after_sale' => 't.is_apply_after_sale',
        ];

        $es_search_time_type = [
            'order_time'    => 't.ORDER_TIME',
            'pay_time'      => 't.ORDER_PAY_TIME',
            'send_time'     => 't.SHIPPING_TIME',
            'send_ord_time' => 't.SEND_ORD_TIME',
            'sendout_time'  => 'mo.sendout_time',
        ];

        $es_search_condition = [
            "receiver_email" => 't.USER_EMAIL',
            'order_id' => 't.B5C_ORDER_NO',
            'thr_order_id' => 't.ORDER_ID',
            'thr_order_no' => 't.ORDER_NO',
            'receiver_phone' => 't.ADDRESS_USER_PHONE',
            'pay_the_serial_number' => 't.PAY_TRANSACTION_ID',
            'pay_method' => 't.PAY_METHOD',
            'receiver_tel' => 't.RECEIVER_TEL',
            'consignee_name' => 't.ADDRESS_USER_NAME',
            'tracking_number' => 'mop.TRACKING_NUMBER',
            'sku_title' => 'pd.spu_name',
            'sku_number' => 'oog.B5C_SKU_ID',
            'zip_code' => 't.ADDRESS_USER_POST_CODE',
        ];
        $query_arr = [];
        if ($post_data) {
            $query_arr = $this->ordAndInOr($post_data, $es_where_arr);
        }
        $query_arr['t.PARENT_ORDER_ID'] = array('exp', 'is null'); // 只查母单的金额，不考虑子单，否则会统计重复


        $whereIdx = 0;
        // remark_haveremarkMsg
        if (isset($post_data->remark_have) && $post_data->remark_have == 1) {
            $keyCondArr = [];
            $keyCondArr["t.REMARK_MSG"] = array('exp','is not null');
            $keyCondArr["t.SHIPPING_MSG"] = array('exp','is not null');
            $keyCondArr["_logic"] = 'or';
            $query_arr["_complex"][$whereIdx++] = $keyCondArr;
        }

        if (!empty($post_data->is_count_kpi)) {
            $search_field = ['oog.is_on_sale_snapshot', 'oog.is_edited_snapshot', 'oog.is_on_sale_reciever_snapshot'];
            foreach ($post_data->is_count_kpi as $v) {
                $countKpi[$search_field[$v]] = array('eq', '0');
            }
            $countKpi["_logic"] = 'or';
            $query_arr["_complex"][$whereIdx++] = $countKpi;
        }


        // 时间类型搜索框
        if ($post_data->search_time_type && ($post_data->search_time_left || $post_data->search_time_right)) {

            if ($post_data->search_time_left == $post_data->search_time_right) {
                $post_data->search_time_left = date('Y-m-d', strtotime("$post_data->search_time_left"));
                $post_data->search_time_right = date('Y-m-d', strtotime("$post_data->search_time_right"));
            }
            if ($post_data->search_time_right && $post_data->search_time_left) $query_arr[$es_search_time_type[$post_data->search_time_type]] = array('BETWEEN', $post_data->search_time_left.",".$post_data->search_time_right);

            if ($post_data->search_time_left && !$post_data->search_time_right) $query_arr[$es_search_time_type[$post_data->search_time_type]] = array('egt', $post_data->search_time_left);

            if ($post_data->search_time_right && !$post_data->search_time_left) $query_arr[$es_search_time_type[$post_data->search_time_type]] = array('elt', $post_data->search_time_right);
        }


        // 模糊搜索
        $search_value = trim($post_data->search_value);
        if ($search_value) {
            $search_accurate_arr = ['zip_code', 'receiver_email', 'pay_method'];
            if ((($post_data->search_condition == 'thr_order_no' || $post_data->search_condition == 'order_id' || $post_data->search_condition == 'thr_order_id') && strpos($search_value, ',')) ||
                in_array($post_data->search_condition, $search_accurate_arr)
            ) { // 精确匹配
                $query_arr[$es_search_condition[$post_data->search_condition]] = array('in', explode(',', $search_value));
            } else { // 模糊匹配
                if ($post_data->search_condition == 'sku_title') { // 根据值查询获取sku
                    $sku_arr = PMSSearchModel::getSkuBySpuName($search_value);
                    if ($sku_arr) {
                        $sku_arr = array_column($sku_arr, 'sku_id');
                        $query_arr[$es_search_condition['sku_number']] = array('in', $sku_arr);
                    }
                } else {
                    $query_arr[$es_search_condition[$post_data->search_condition]] = array('like', "%" . $search_value . "%");
                }
            }
        }

        // SKU 未必配
        if ($post_data->sku_is_null == 1) {
            // oog表里么有记录 ， 要么有记录，但是B5C_SKU_ID为空 
            $query_arr['oog.B5C_SKU_ID'] = ['EXP', 'IS NULL'];
        }


        return $query_arr;

    }



    /**
     * @param        $find_data_arr
     * @param string $order_id
     * @param string $plat_cd
     *
     * @return mixed
     */
    public static function recommendSearch($find_data_arr, $order_id = 'orderId', $plat_cd = 'platCd')
    {
        foreach ($find_data_arr['data'] as &$value) {
            foreach (DataModel::toYield($value['data']['warehouse']) as $warehouse) {
                foreach (DataModel::toYield($warehouse['lgtModel']) as $lgt_model) {
                    foreach (DataModel::toYield($lgt_model['logisticsMethod']) as $logistics_method) {
                        $res[] = $warehouse['cd'] . $lgt_model['logisticsCode'] . $logistics_method['id'];
                    }
                }
            }
            $find_search_arr[$value[$order_id] . $value[$plat_cd]] = $res;
            unset($value);
        }
        unset($res);
        return $find_search_arr;
    }

    /**
     * @param $b5c_order_arr
     *
     * @throws Exception
     */
    private static function theckData($b5c_order_arr)
    {
        if (empty($b5c_order_arr)) {
            throw new Exception(L('无请求数据'));
        }
    }

    /**
     * @param $save_res
     *
     * @throws Exception
     */
    private static function checkSaveErr($save_res)
    {
        if (empty($save_res)) {
            throw new Exception(L('更改异常'));
        }
    }


    /**
     *
     */
    public function lists()
    {
        $es = new EsModel();
        $res = $es->search();
        return $res;
    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    public static function joinInfoUpd($post_data)
    {
        foreach ($post_data as $key => $value) {
            $res['ADDRESS_USER_' . $key] = str_replace('<br/><br/><br/><br/>', '', trim($value));
        }
        $res['RECEIVER_TEL'] = $post_data['RECEIVER_TEL'];
        $res['USER_EMAIL'] = $post_data['USER_EMAIL'];
        return $res;
    }

    /**
     * @param $datas
     *
     * @return array
     */
    public static function closeord($datas)
    {
        //不能关闭的订单状态
        $not_close_status = [
            'N000550900',
            'N000551000',
            'N000550300'
        ];
        $Model = M();
        $save_op['BWC_ORDER_STATUS'] = $save_ms['ORD_STAT_CD'] = 'N000550900';
        foreach ($datas as $value) {
            if (!in_array($value['plat_cd'], $not_close_status)) {
                $where['ORDER_ID'] = $value['thr_order_id'];
                $where['PLAT_CD'] = $value['plat_cd'];
                $res['size'] = $Model->table('tb_op_order')
                    ->where($where)
                    ->save($save_op);
                if ($value['order_id']) {
                    $guds_all = $Model->table('tb_ms_ord_guds_opt')
                        ->field('GUDS_ID,GUDS_OPT_ID,ORD_ID')
                        ->where("ORD_ID = '" . $value['order_id'] . "'")
                        ->select();
                    foreach ($guds_all as $k => $v) {
                        $cancel_data['gudsId'] = $v['GUDS_ID'];
                        $cancel_data['skuId'] = $v['GUDS_OPT_ID'];
                        $cancel_data['orderId'] = $v['ORD_ID'];
                        $cancel_datas[] = $cancel_data;
                    }
                }
                $res['msg'] = $value['thr_order_id'] . '订单关闭中';
                if ($res > 0) $res['msg'] = $value['thr_order_id'] . '订单关闭成功';
            } else {
                $res['msg'] = $value['thr_order_id'] . '订单状态不能被修改';
            }
            $res_arr[] = $res;
        }
        if (count($cancel_datas) > 0) {
            $cancel_res = OrdersModel::cancel_order($cancel_datas);
            Logs($cancel_res, 'cancel_res');
        }
        Logs($datas, '$datas');
        self::addLog(null, 'N000550900', '订单关闭', $datas);
        return $res_arr;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function links($data)
    {
        $Model = M();
        $gudWhere['ORDER_ID'] = $data['thr_order_id'];
        $gudWhere['PLAT_CD'] = $data['plat_cd'];
        $gudField = 'B5C_SKU_ID,SKU_ID,PRODUCT_DETAIL_URL';
        $gud_list = $Model
            ->table('tb_op_order_guds')
            ->field($gudField)
            ->where($gudWhere)->select();
        $STORE_ID = $data['STORE_ID'];
        foreach ($gud_list as $key => $value) {
            if (!$gud_list[$key]['PRODUCT_DETAIL_URL']) {
                $product_detail_url_mark = $Model->table('tb_ms_store')
                    ->where('ID =' . $STORE_ID)->getField('PRODUCT_DETAIL_URL_MARK');
                if ($product_detail_url_mark) {
                    $where_relation['store_id'] = $STORE_ID;
                    $where_relation['third_sku_id'] = $value['SKU_ID'];
                    $where_relation['b5c_sku_id'] = $value['B5C_SKU_ID'];
                    $product_detail_url_id = $Model->table('tb_ms_sku_relation')
                        ->where($where_relation)->getField('PRODUCT_DETAIL_URL_ID');
                    if (!$product_detail_url_id) {
                        unset($where_relation['third_sku_id']);
                        $product_detail_url_id_t = $Model
                            ->table('tb_ms_sku_relation')
                            ->field('PRODUCT_DETAIL_URL_ID')
                            ->where($where_relation)->limit(1)->find();
                        $product_detail_url_id = $product_detail_url_id_t['PRODUCT_DETAIL_URL_ID'];
                    }
                    if ($product_detail_url_id) {
                        $url = sprintf($product_detail_url_mark, $product_detail_url_id);
                        $gud_list[$key]['PRODUCT_DETAIL_URL'] = $url;
                    } else {
                        $gud_list[$key]['PRODUCT_DETAIL_URL'] = null;
                    }
                }
            } elseif (($gud_list[$key]['PRODUCT_DETAIL_URL'] && stristr($gud_list[$key]['PRODUCT_DETAIL_URL'], ':') === false)) {
                $product_detail_url_mark = $Model->table('tb_ms_store')
                    ->where('ID =' . $STORE_ID)
                    ->getField('PRODUCT_DETAIL_URL_MARK');
                $url = sprintf($product_detail_url_mark, $gud_list[$key]['PRODUCT_DETAIL_URL']);
                $gud_list[$key]['PRODUCT_DETAIL_URL'] = $url;
            }
        }
        return $gud_list;
    }

    /**
     * @param null $order_arr
     * @param      $state_code
     * @param      $msg_count
     * @param null $thr_order_arr
     */
    public static function addLog($order_arr = null, $state_code, $msg_count, $thr_order_arr = null)
    {
        $Model = M();
        $thr_ord_key = 'THIRD_ORDER_ID';
        $plat_from_key = 'PLAT_FORM';
        if ($order_arr) {
            $where['ORD_ID'] = array('IN', $order_arr);
            $dataMap = $Model->table('tb_ms_ord')
                ->field('THIRD_ORDER_ID,PLAT_FORM')
                ->where($where)
                ->select();
        } elseif ($thr_order_arr) {
            $thr_ord_key = 'thr_order_id';
            $plat_from_key = 'plat_cd';
            $dataMap = $thr_order_arr;
        }
        foreach ($dataMap as $value) {
            $excel_log ['ORD_NO'] = $value[$thr_ord_key];
            $excel_log ['plat_cd'] = $value[$plat_from_key];
            $excel_log ['ORD_HIST_SEQ'] = time();
            $excel_log ['ORD_STAT_CD'] = $state_code;
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $excel_log ['ORD_HIST_HIST_CONT'] = $msg_count;
            $excel_log_arr[] = $excel_log;
        }
        SmsMsOrdHistModel::writeMulHist($excel_log_arr);
    }

    /**
     * @param $post_data
     *
     * @return array
     */
    private static function faceAloneWhereJoin($post_data)
    {
        $where = OrdersModel::initWhere();
        foreach ($post_data['data']['orders'] as $value) {
            $where .= " OR ORD_ID = '" . $value['thr_order_id'] .
                "'  AND PLAT_CD = '" . $value['plat_from'] . "'";
        }
        return array($where, $value);
    }

    /**
     * @param $post_data
     *
     * @return mixed
     */
    public static function faceAloneJoin($post_data)
    {
        list($where, $value) = self::faceAloneWhereJoin($post_data);
        $Model = M();
        $field = 'ORD_ID,plat_cd,template_type,template';
        $res = $Model->table('tb_ms_ord_package')
            ->field($field)->where($where)->select();
        $count = 0;
        foreach ($res as $value) {
            if (!empty($value['template_type']) && !empty($value['template'])) {

                switch ($value['template_type']) {
                    case 1:
                        $data['type'] = 'html';
                        $data['count'] = $value['template'];
                        break;
                    case 2:
                        $data['type'] = 'base';
                        $data['count'] = $value['template'];
                        break;
                    case 3:
                        $data['type'] = 'image';
                        $data['path'] = self::imgToPath($value['template']);
                        break;
                }
                $data['ORD_ID'] = $value['ORD_ID'];
                $data['plat_cd'] = $value['plat_cd'];
                $data['one_key'] = $data['ORD_ID'] . $data['plat_cd'];
                if ($post_data['show'] != 1) {
                    $where_ms['THIRD_ORDER_ID'] = $data['ORD_ID'];   //ms_order表条件
                    $where_ms['PLAT_FORM'] = $data['plat_cd'];
                    if (M('ms_ord', 'tb_')->where($where_ms)->getField('WHOLE_STATUS_CD') != 'N001820600') {
                        $data = ["code" => 500, "msg" => L("当前状态不允许此操作")];
                        OrderPresentModel::get_log_data($value['ORD_ID'], '面单打印失败，订单状态已更新', $value['plat_cd'], 'N001820600');
                        continue;
                    }
                    OrderPresentModel::get_log_data($value['ORD_ID'], '面单打印', $value['plat_cd'], 'N001820600');
                    if ($data['path'] || $data['count']) {
                        $printAction = new PickApartAction();
                        $printAction->print_change_status($where_ms, 'model');
                        $count += 1;
                    }
                } elseif ($data['path'] || $data['count']) {
                    $count += 1;
                }
                $data_arrays[] = $data;
            }
        }
        $one_key_arr = array_flip(array_column($data_arrays, 'one_key'));
        $meta_data = $post_data['data']['orders'];
        foreach ($meta_data as $k => $v) {
            $data_arrays_sort[] = $data_arrays[$one_key_arr[$v['thr_order_id'] . $v['plat_from']]];
        }
        $res_return['data'] = $data_arrays_sort;
        $res_return['count'] = $count;
        return $res_return;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private static function imgToPath($data)
    {
        $res_arr = explode(',', $data);
        foreach ($res_arr as $value) {
            $str = '/Application/Runtime/Data/logistic/' . $value;
            $path_new = APP_SITE . $str;
            $path = ATTACHMENT_DIR_LOGISTIC . $value;
            if (file_exists($path_new)) {
                $res[] = $str;
            } elseif (file_exists($path) && !file_exists($path_new)) {
                self::cloneImg($path, $path_new);
                $res[] = $str;
            }
        }
        return $res;
    }


    /**
     * @param $path
     * @param $path_new
     */
    private static function cloneImg($path, $path_new)
    {
        file_put_contents($path_new, file_get_contents($path));
    }

    /**
     * @param $where
     *
     * @return mixed
     */
    public static function findOrderErrorUpd($where)
    {
        $Model = M();
        $save['FIND_ORDER_ERROR_TYPE'] = 'N002050200';
        $res = $Model->table('tb_op_order')->where($where)->save($save);
        return $res;
    }

    /**
     * @param      $filePath
     * @param null $type
     *
     * @return mixed|void
     * @throws PHPExcel_Reader_Exception
     */
    public static function logisicsEditData($filePath, $type = null)
    {
        Logs('logisicsEditDataAct');
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $sheet = $PHPExcel->getSheet(0);   //获取第一个sheet
        list($data_arr, $error_arr_null) = DataModel::checkNull(
            ExcelModel::dataJoin($PHPExcel, $sheet->getHighestRow(), self::$logisics_key),
            self::$logisics_key_name,
            self::$null_check,
            'ORDER_ID'
        );
        $model = M();
        $error_arr_info = [];
        list($data_arr, $error_arr_info) = self::cn2Data($data_arr, $model, $error_arr_info);
        Logs('inRecommendAct');
        list($data_arr, $error_arr_info) = self::inRecommend($data_arr, $model, $error_arr_info);
        Logs('inRecommendEnd');


        $user = session('m_loginname');
        $file_name = $user . '_logisics_' . date("YmdHis", time());
        Logs('logisicsUpdateAct');
       
        $orderlist_success = self::logisicsUpdate($data_arr, $model, $file_name);
        Logs('logisicsUpdateEnd');
        //运单模板编辑  编辑仓库物料sheet没有数据则读取运单模板sheet
        if (empty($data_arr)) {
            //只处理导入运单模板 第三单元格内容为
            $column_name = (string)$PHPExcel->getActiveSheet()->getCell("C1")->getValue();
            if ($column_name == '运单号（必填）') {
                list($data_arr, $error_arr_null) = DataModel::checkNull(
                    ExcelModel::dataJoin($PHPExcel, $sheet->getHighestRow(), self::$logisics_other_key),
                    self::$logisics_other_key_name,
                    self::$null_check,
                    'ORDER_ID'
                );
                if (!empty($data_arr)) {
                    list($data_arr, $error_arr_info) = self::checkData($data_arr, $model, $error_arr_info);
                    $file_name = $user . '_logisics_excel_' . date("YmdHis", time());
                    Logs('logisicsExcelUpdateAct');
                    $orderlist_success = self::logisicsUpdate($data_arr, $model, $file_name);
                    Logs('logisicsExcelUpdateEnd');
                }
            }
        }
        /*foreach ($data_arr as $item) {
            $order_info[] = [
                'opOrderId' => $item['ORDER_ID'],
                'platCd'    => $item['PLAT_CD'],
            ];
        }
        if (count($order_info) > 1) {
            //批量主动刷新订单
            ApiModel::saveAllOrder($order_info, __FUNCTION__);
        }*/
        if ($orderlist_success) {
            if (!file_exists('/opt/b5c-disk/excel')) {
                mkdir("/opt/b5c-disk/excel");
            }
            $destination = "/opt/b5c-disk/excel/" . $file_name . '.xlsx';    //同一时间上传的exccel表名相同
            move_uploaded_file($filePath, $destination);
        }
        Logs('joinReturn');
        $res = DataModel::showRetrunJoin($data_arr, array_merge($error_arr_null, $error_arr_info), $orderlist_success, $error_arr_null);
        Logs('logisicsEditDataEnd');
        RedisLock::unlock();
        return $res;
    }

    /**
     * @param $data_arr
     * @param $Model
     *
     * @return mixed
     */
    protected static function cn2Data($data_arr, $Model, $error_arr_info)
    {
        $where['CD'] = array('like', array('N00068%', 'N00070%', 'N00201%'), 'OR');
        $where['USE_YN'] = 'Y';
        $cd_arr = $Model->table('tb_ms_cmn_cd')->field('CD,CD_VAL')->where($where)->select();
        $where_mode['LOGISTICS_MODE'] = array('in', array_column($data_arr, 'logistic_model_id'));
        $where_mode['IS_ENABLE'] = 1;
        $where_mode['IS_DELETE'] = 0;
        $model_arr = $Model->table('tb_ms_logistics_mode')->field('ID,LOGISTICS_MODE')->where($where_mode)->select();;
        $model_arr = array_column($model_arr, 'ID', 'LOGISTICS_MODE');
        $cd_arr = array_column($cd_arr, 'CD', 'CD_VAL');
        $service = new Service();
        foreach ($data_arr as $key => $value) {
            $data_arr[$key]['WAREHOUSE'] = $cd_arr[$value['WAREHOUSE']];
            $data_arr[$key]['logistic_cd'] = $cd_arr[$value['logistic_cd']];
            $data_arr[$key]['SURFACE_WAY_GET_CD'] = $cd_arr[$value['SURFACE_WAY_GET_CD']];
            $data_arr[$key]['logistic_model_id'] = $model_arr[$value['logistic_model_id']];
            foreach ($data_arr[$key] as $k => $v) {
                if (empty($v) && $k !== 'TRACKING_NUMBER') {
                    $res['msg'] = self::$logisics_key_name[$k] . '异常';
                    $res['orderId'] = $data_arr[$key]['ORDER_ID'];
                    $error_arr_info[] = $res;
                    unset($data_arr[$key]);
                    break;
                }
            }
            if (!$service->isMayOperationOrders( array( 'ID'=>$value['ORDER_ID'] , 'PLAT_CD' => $value['PLAT_CD']) ,true,false)){
                $res['msg'] = "代销售订单禁止EXCEL编辑操作";
                $res['orderId'] = $data_arr[$key]['ORDER_ID'];
                $error_arr_info[] = $res;
                unset($data_arr[$key]);
                break;
            }
        }
        return [$data_arr, $error_arr_info];
    }

    /**
     * @param $data_arr
     * @param $Model
     *
     * @return array
     */
    public static function inRecommend($data_arr, $Model = null, $error_arr_info)
    {
        $warehouse_key = 'WAREHOUSE';
        $logistic_cd_key = 'logistic_cd';
        $logistic_model_id_key = 'logistic_model_id';
        if ($data_arr[0]['orderId'] && $data_arr[0]['platCd']) {
            $order_key = 'orderId';
            $plat_key = 'platCd';
        } elseif ($data_arr[0]['ORDER_ID'] && $data_arr[0]['PLAT_CD']) {
            $order_key = 'ORDER_ID';
            $plat_key = 'PLAT_CD';
        } elseif ($data_arr[0]['thr_order_id'] && $data_arr[0]['plat_cd']) {
            $order_key = 'thr_order_id';
            $plat_key = 'plat_cd';
            $warehouse_key = 'delivery_warehouse_code';
            $logistic_cd_key = 'logistics_company_code';
            $logistic_model_id_key = 'shipping_methods_code';
        }
        $where = '1 != 1';
        $where_new = '( 1 != 1';
        foreach ($data_arr as $value) {
            $err['orderId'] = $value[$order_key];
            $err['platCd'] = $value[$plat_key];
            $where .= " OR (ORDER_ID ='" . $err['orderId'] . "' AND PLAT_CD = '" . $err['platCd'] . "') ";
            $where_new .= " OR (tb_op_order.ORDER_ID ='" . $err['orderId'] . "' AND tb_op_order.PLAT_CD = '" . $err['platCd'] . "') ";
            $err_order[] = $err;
        }
        $where_new .= ' ) ';

        Logs('patchRecommendAct');
        //10170 OMS - 移除订单仓库物流编辑推荐验证
        if (!empty($err_order)) {
//            $order_db_arr = PatchModel::patchRecommend($err_order);
//            Logs('patchRecommendEnd');
//            $find_search_arr = self::recommendSearch($order_db_arr);
//            unset($order_db_arr);
//            Logs('joinSearch');
        }
        if (empty($Model)) {
            $Model = M();
        }
        if ('1 != 1' != $where) {
            $status_res = $Model->table('tb_op_order')
                ->field('ORDER_ID,PLAT_CD,B5C_ORDER_NO,concat(ORDER_ID,PLAT_CD) AS ORDER_KEY,BWC_ORDER_STATUS')
                ->where($where)
                ->select();
            $status_arr = array_column($status_res, 'B5C_ORDER_NO', 'ORDER_KEY');
            self::$BWC_ORDER_STATUS_LIST = array_column($status_res, 'BWC_ORDER_STATUS', 'ORDER_KEY');
            
        }
        Logs('SearchDataIn');
        $shopnc_order_data = PatchModel::verifysShopncOrderMult($where_new);
        foreach ($data_arr as $key => $value) {
            if ($order_key != 'thr_order_id' && $status_arr[$value[$order_key] . $value[$plat_key]]) {
                $res['msg'] = '订单已生成我方订单';
                $res['orderId'] = $value[$order_key];
                $error_arr_info[] = $res;
                unset($data_arr[$key]);
            }

            $orderId = $value[$order_key];
            $platCd = $value[$plat_key];
            $data_arr[$key]['is_shopnc_order'] = $is_shopnc_order = in_array($orderId.'_'.$platCd,$shopnc_order_data) ? true : false;

//            if (!in_array($value[$warehouse_key] . $value[$logistic_cd_key] . $value[$logistic_model_id_key],
//                $find_search_arr[$value[$order_key] . $value[$plat_key]])) {
//                $res['msg'] = '派单推荐结果不包含';
//                $res['orderId'] = $value[$order_key];
//                $error_arr_info[] = $res;
//                // Logs($find_search_arr, 'find_search_arr', 'logisticEdit');
//                Logs($value[$warehouse_key] . $value[$logistic_cd_key] . $value[$logistic_model_id_key], 'patch_key', 'logisticEdit');
//                unset($data_arr[$key]);
//            }
        }
        if (!isset($error_arr_info)) $error_arr_info = array();
        return [$data_arr, $error_arr_info];
    }
   
    /**
     * @name 导入运单模板数据校验
     * @param $data_arr
     * @param $Model
     *
     * @return mixed
     */
    protected static function checkData($data_arr, $Model, $error_arr_info)
    {
        foreach ($data_arr as $key => $value) {
            $where['SEND_ORD_STATUS'] = ['in', ['N001820100', 'N001821000']];
            $where['ORDER_ID'] = $value['ORDER_ID'];
            $where['PLAT_CD'] = $value['PLAT_CD'];
            $opOrder = $Model->table('tb_op_order')->where($where)->find();
            $data_arr[$key]['WAREHOUSE'] = $opOrder['WAREHOUSE'];
            $data_arr[$key]['logistic_cd'] = $opOrder['logistic_cd'];
            $data_arr[$key]['SURFACE_WAY_GET_CD'] = $opOrder['SURFACE_WAY_GET_CD'];
            $data_arr[$key]['logistic_model_id'] = $opOrder['logistic_model_id'];
            //校验面单获取方式
            if ($opOrder['SURFACE_WAY_GET_CD'] == 'N002010100') {
                $res['msg'] = ' “一键获取” 的订单不允许编辑 ';
                $res['orderId'] = $value['ORDER_ID'];
                $error_arr_info[] = $res;
                unset($data_arr[$key]);
                break;
            }
            //校验
            if (empty($opOrder['WAREHOUSE']) || empty($opOrder['logistic_cd'])) {
                $res['msg'] = '订单要有对应的仓库和物流方式才允许添加对应的运单号';
                $res['orderId'] = $value['ORDER_ID'];
                $error_arr_info[] = $res;
                unset($data_arr[$key]);
                break;
            }
            //已标记发货
            if ($opOrder['THIRD_DELIVER_STATUS'] == '1') {
                $res['msg'] = ' 已标记发货的单子不允许批量excel编辑';
                $res['orderId'] = $value['ORDER_ID'];
                $error_arr_info[] = $res;
                unset($data_arr[$key]);
                break;
            }
        }
        if (!isset($error_arr_info)) $error_arr_info = array();
        return [$data_arr, $error_arr_info];
    }

    /**
     * @param $data_arr
     * @param $Model
     *
     * @return array
     */
    protected static function logisicsUpdate($data_arr, $Model, $file_name)
    {
        // $Model->startTrans();
        $user = session('m_loginname');
        $LogService = new LogService();

        // $value[$order_key] . $value[$plat_key]

        #根据code查找GP店铺code
        $shopCodeArr = array_column(CodeModel::getSiteCodeArr('N002620800'), 'CD');

        foreach ($data_arr as $value) {
//            $save['ORDER_ID'] = $value['ORDER_ID'];
//            $save['PLAT_CD'] = $value['PLAT_CD'];
            empty($value['WAREHOUSE']) or $save['WAREHOUSE'] = $value['WAREHOUSE'];
            empty($value['logistic_cd']) or $save['logistic_cd'] = $value['logistic_cd'];
            empty($value['logistic_model_id']) or $save['logistic_model_id'] = $value['logistic_model_id'];
            empty($value['SURFACE_WAY_GET_CD']) or $save['SURFACE_WAY_GET_CD'] = $value['SURFACE_WAY_GET_CD'];
            $where['SEND_ORD_STATUS'] = ['in', ['N001820100', 'N001821000']];
            $where_pack['ORD_ID'] = $where['ORDER_ID'] = $value['ORDER_ID'];
            $where_pack['plat_cd'] = $where['PLAT_CD'] = $value['PLAT_CD'];
            $tmpStatus = self::$BWC_ORDER_STATUS_LIST[$value['ORDER_ID'] . $value['PLAT_CD']];
            if($value['TRACKING_NUMBER'] && self::$BWC_ORDER_STATUS_LIST[$value['ORDER_ID'] . $value['PLAT_CD']] == 'N000550400' && in_array($value['PLAT_CD'], $shopCodeArr)){
                $save['BWC_ORDER_STATUS'] = 'N000551004';
                $tmpStatus = 'N000551004';
            }
            $update_msg = $LogService->getUpdateMessage('tb_op_order', $where, $save);
            $res['op'] = $Model->table('tb_op_order')
                ->where($where)
                ->save($save);
            $save_pack['TRACKING_NUMBER'] = $value['TRACKING_NUMBER'];
            if (!empty($value['TRACKING_NUMBER']) && $res['op'] !== false) {
                if (!$Model->table('tb_ms_ord_package')->where($where_pack)->count()) {
                    $save_pack['ORD_ID'] = $value['ORDER_ID'];
                    $save_pack['plat_cd'] = $value['PLAT_CD'];
                    $res['pack'] = $Model->table('tb_ms_ord_package')->add($save_pack);
                } else {
                    $update_msg .= $LogService->getUpdateMessage('tb_ms_ord_package', $where_pack, $save_pack);
                    $res['pack'] = $Model->table('tb_ms_ord_package')->where($where_pack)->save($save_pack);
                }
            } else {
                $res['pack'] = false;
            }
            if (!empty($update_msg)) {
                OrderPresentModel::get_log_data($value['ORDER_ID'], $update_msg, $value['PLAT_CD']);
            }
            OrderPresentModel::get_log_data($value['ORDER_ID'], $user . L('订单仓库物流信息编辑') . '-[' . $file_name . '.xlsx]', $value['PLAT_CD']);
            $setSystemSort = SystemSortModel::setSystemSort($value['ORDER_ID'], $value['PLAT_CD']);
            if ($setSystemSort) {
                OrderLogModel::addLog($value['ORDER_ID'], $value['PLAT_CD'], L('更新限制替换锁定成功'));
            }
            $res_arr[] = $res;
            unset($save_pack);
        }
        // $Model->commit();
        if (!isset($res_arr)) $res_arr = array();
        return $res_arr;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public static function storesGet($data)
    {
        $res = [];
        if (!empty($data['plat_form']) && count($data['plat_form'])) {
            $where['PLAT_CD'] = array('in', $data['plat_form']);
        } else {
            $where = '1 = 1';
        }
        $Model = M();
        $res = $Model->table('tb_ms_store')->field('ID AS CD,ID,STORE_NAME,STORE_NAME AS CD_VAL')->where($where)->select();
        return $res;
    }

    /**
     * @param $filePath
     *
     * @return mixed
     */
    public static function logisicsSortEdit($filePath)
    {
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $sheet = $PHPExcel->getSheet(0);   //获取第一个sheet
        list($data_arr, $error_arr_null) = DataModel::checkNull(
            ExcelModel::dataJoin($PHPExcel, $sheet->getHighestRow(), self::$logisics_sort_key),
            self::$logisics_sort_key_name, 'ORDER_ID',
            'B5C_ORDER_ID'
        );
        $Model = M();
        $user = session('m_loginname');
        $file_name = $user . '_logisics_' . date("YmdHis", time());
        foreach ($data_arr as $key => $value) {
            $where['t2.ORD_ID'] = $value['B5C_ORDER_ID'];
            $suraface_data = $Model->table('tb_ms_ord t2')
                ->field('t2.ORD_ID,t1.ORDER_ID,t1.PLAT_CD,t2.PLAT_FORM,t2.THIRD_ORDER_ID,t1.SURFACE_WAY_GET_CD,t2.WHOLE_STATUS_CD')
                ->join('LEFT JOIN tb_op_order AS t1 on t1.B5C_ORDER_NO = t2.ORD_ID')
                ->where($where)
                ->find();
            if (empty($suraface_data)) {
                $res['msg'] = '无对应订单信息';
                $res['orderId'] = $value['B5C_ORDER_ID'];
                unset($data_arr[$key]);
                $error_arr_info[] = $res;
                continue;
            }
            $where_pack['ORD_ID'] = $suraface_data['THIRD_ORDER_ID'];
            $where_pack['plat_cd'] = $suraface_data['PLAT_FORM'];
            if (empty($suraface_data['SURFACE_WAY_GET_CD'])) {
                $res['msg'] = '无对应面单获取方式';
                $res['orderId'] = $value['B5C_ORDER_ID'];
                unset($data_arr[$key]);
                $error_arr_info[] = $res;
            } elseif ('N001820600' != $suraface_data['WHOLE_STATUS_CD']) {
                $res['msg'] = '订单状态非待分拣';
                $res['orderId'] = $value['B5C_ORDER_ID'];
                unset($data_arr[$key]);
                $error_arr_info[] = $res;
            } elseif ('N002010200' != $suraface_data['SURFACE_WAY_GET_CD']) {
                $res['msg'] = '推荐类型非无需面单';
                $res['orderId'] = $value['B5C_ORDER_ID'];
                unset($data_arr[$key]);
                $error_arr_info[] = $res;
            } elseif (!empty($value['TRACKING_NUMBER'])) {
                $save_pack['TRACKING_NUMBER'] = $value['TRACKING_NUMBER'];
                if (!$Model->table('tb_ms_ord_package')->where($where_pack)->count()) {
                    $save_pack['ORD_ID'] = $suraface_data['THIRD_ORDER_ID'];
                    $save_pack['plat_cd'] = $suraface_data['PLAT_FORM'];
                    $res['pack'] = $Model->table('tb_ms_ord_package')->add($save_pack);
                } else {
                    $update_msg = (new LogService)->getUpdateMessage('tb_ms_ord_package', $where_pack, $save_pack);
                    $res['pack'] = $Model->table('tb_ms_ord_package')->where($where_pack)->save($save_pack);
                }
                if (!empty($update_msg)) {
                    OrderPresentModel::get_log_data($where_pack['ORD_ID'], $update_msg, $where_pack['plat_cd']);
                }
                if (false !== $res['pack'] && $res['pack'] > 0) {
                    OrderPresentModel::get_log_data($where_pack['ORD_ID'], $user . L('订单物流信息编辑') . '-[' . $file_name . '.xlsx]', $where_pack['plat_cd']);
                }
                $setSystemSort = SystemSortModel::setSystemSort($where_pack['ORD_ID'], $where_pack['plat_cd']);
                if ($setSystemSort) {
                    OrderLogModel::addLog($value['ORDER_ID'], $value['PLAT_CD'], L('更新限制替换锁定成功'));
                }
                $res_arr[] = $res;
            }
            unset($save_pack);
        }

        //批量主动刷新订单
        $b5cOrderNos = array_column($data_arr, 'B5C_ORDER_NO');
        (new OmsService())->saveAllOrderByB5cOrderNo($b5cOrderNos, __FUNCTION__);

        $orderlist_success = $res_arr;
        if (!empty($orderlist_success)) {
            if (!file_exists('/opt/b5c-disk/excel')) {
                mkdir("/opt/b5c-disk/excel");
            }
            $destination = "/opt/b5c-disk/excel/" . $file_name . '.xlsx';    //同一时间上传的exccel表名相同
            move_uploaded_file($filePath, $destination);
        }
        if (!is_array($error_arr_info)) $error_arr_info = [];
        $res = DataModel::showRetrunJoin($data_arr, array_merge($error_arr_null, $error_arr_info), $orderlist_success, $error_arr_null);
        RedisLock::unlock();
        return $res;
    }

    public static function invoice($data)
    {
        $Model = M();
        $where['tb_op_order.B5C_ORDER_NO'] = array('IN', $data['b5c_order_no']);
        $field = 'tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_op_order.SEND_FREIGHT,tb_op_order.ADDRESS_USER_ADDRESS1,tb_op_order.ADDRESS_USER_ADDRESS2,tb_op_order.ADDRESS_USER_ADDRESS3,tb_op_order.ADDRESS_USER_ADDRESS4,tb_op_order.ADDRESS_USER_ADDRESS5,tb_op_order.voucher_code,tb_op_order.B5C_ORDER_NO,tb_op_order.order_time,tb_op_order.address_user_name,tb_op_order.Warehouse,tb_op_order.PAY_TOTAL_PRICE,tb_op_order.PAY_VOUCHER_AMOUNT,tb_op_order.SEND_FREIGHT,tb_op_order.PAY_CURRENCY,tb_op_order.pay_price,tb_op_order.pay_item_price,
        tb_op_order_guds.B5C_SKU_ID,tb_op_order_guds.SKU_ID,tb_op_order_guds.cost_price,tb_op_order_guds.third_party_sales_price,tb_op_order_guds.item_price,
        tb_ms_ord_package.TRACKING_NUMBER,
        product_sku.purchase_price,
        tb_ms_store.USER_ID,
        tb_wms_warehouse.place,tb_wms_warehouse.address,tb_wms_warehouse.contacts,tb_wms_warehouse.sender_zip_code,tb_wms_warehouse.place,tb_wms_warehouse.phone';
        $res = $Model->table('tb_op_order')->field($field)->where($where)
            ->join('left join tb_op_order_guds on tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->join('left join ' . PMS_DATABASE . '.product_sku on tb_op_order_guds.B5C_SKU_ID = product_sku.sku_id ')
            ->join('left join tb_wms_warehouse on tb_wms_warehouse.CD = tb_op_order.warehouse ')
            ->join('left join tb_ms_store on tb_ms_store.ID = tb_op_order.store_id')
            ->select();
//        $cd_val = B2bModel::get_code_cd('N00059');
        if ($res) {
            $res = SkuModel::getInfo($res, 'B5C_SKU_ID', ['spu_name']);
            foreach ($res as $val) {
//                $info["order_invoice_information"] = $val['voucher_code'];
                $info["order_invoice_information"] = sprintf('%06d', substr($val['ORDER_ID'], -6));
                $info["order_number"] = $val["B5C_ORDER_NO"];
                $info["order_time"] = $val['order_time'];
                $info["name_consignee"] = $val['address_user_name'];
                $info["invoice_date"] = date('Y-m-d', strtotime($val['order_time']) + (7 * 24 * 3600));
                $info["waybill_number"] = $val["TRACKING_NUMBER"];

                $info["operational_contact_number"] = $val['USER_ID'];
                $info["seller_contact_number"] = $val['phone'];
                $info["eller_return_address"] = $val['place'] . ' ' . $val['address'];
                $info["contacts"] = $val['contacts'];
                $info["sender_zip_code"] = $val['sender_zip_code'];
                $info["place"] = explode('-', $val['place'])[0];
                $info["phone"] = $val['phone'];

                $info["seller_shipping_address"] = OrdersModel::joinAddress($val);
                $info["payment_price"] = $val['PAY_TOTAL_PRICE'];
                $info["discount_prices"] = $val['PAY_VOUCHER_AMOUNT'];
                $info["freight"] = $val["SEND_FREIGHT"];
                $info["other_fixed_copy"] = null;
                $info["pay_currency"] = $val['PAY_CURRENCY'];
                $info["pay_price"] = $val['pay_price'];
                $info["pay_item_price"] = $val['pay_item_price'];

                $guds['product_name'] = $val['spu_name'];
                $guds['sku_id'] = $val['B5C_SKU_ID'];
                $guds['third_sku_id'] = $val['SKU_ID'];
                $guds['commodity_prices'] = $val['purchase_price'];
                $guds['commodity_payment_price'] = $val['item_price'];
                $order_arr[$val['ORDER_ID'] . $val['PLAT_CD']] = $info;
                $order_arr[$val['ORDER_ID'] . $val['PLAT_CD']]['guds'][] = $guds;
                $order_key[$val['ORDER_ID'] . $val['PLAT_CD']] = $val['B5C_ORDER_NO'];
            }
            if ($order_arr) {
                foreach ($order_arr as $key => $value) {
                    $orders_arr[$order_key[$key]] = $value;
                }
            }
        } else {
            $orders_arr = [];
        }
        $datas['datas'] = $orders_arr;
        return $datas;
    }

    public static function deleteB5cOrderNo($b5c_order_arr)
    {
        try {
            $Model = M();
            $Model->startTrans();
            self::theckData($b5c_order_arr);
            $where['B5C_ORDER_NO'] = array('IN', $b5c_order_arr);
            // $where['SEND_ORD_STATUS'] = array('IN', ['N001820100', 'N001821000', 'N001820300']);
            $res = $Model->table('tb_op_order')
                ->field('ID,ORDER_ID,PLAT_CD,SURFACE_WAY_GET_CD')
                ->where($where)
                ->select();
            foreach ($res as $value) {
                OrderPresentModel::get_log_data($value['ORDER_ID'], '订单退回：移除已派单存储 B5C 订单', $value['PLAT_CD']);
                //删除运单号
                $where_pack['ORD_ID'] = $value['ORDER_ID'];
                $where_pack['plat_cd'] = $value['PLAT_CD'];
                $res_pack = M('ord_package', 'tb_ms_')->where($where_pack)->delete();
                if ($res_pack === false) {
                    $Model->rollback();
                    return false;
                }
                //一键获取改为待获取
                if ($value['SURFACE_WAY_GET_CD'] == 'N002010100') {
                    $res_order = M('order', 'tb_op_')->where(['ID' => $value['ID']])->save(['LOGISTICS_SINGLE_STATU_CD' => 'N002080100']);
                    if ($res_order === false) {
                        $Model->rollback();
                        return false;
                    }
                }

            }
            $save['B5C_ORDER_NO'] = NULL;
            $save['SEND_ORD_STATUS'] = 'N001821000';
            $save['BWC_ORDER_STATUS'] = 'N000550400';
            $save['SEND_ORD_TIME'] = '0000-00-00 00:00:00'; // 退回待派单，移除原来派单时间
            $save_res = $Model->table('tb_op_order')
                ->field('ORDER_ID,PLAT_CD')
                ->where($where)
                ->save($save);
            self::checkSaveErr($save_res);
            $Model->commit();
        } catch (Exception $exception) {
            $Model->rollback();
        }
    }

    public static function isB2c($platCD)
    {
        //return in_array($platCD, ['N000834200', 'N000834100', 'N000834300', 'N000831400']) ? 1 : 0;//us,cn,jp,kr
        return in_array($platCD, CodeModel::getGpPlatCds()) ? 1 : 0;// 是否GP平台

    }

    // 应产品卓平要求，由于不确定非GP订单的区县数据准确性，非GP订单区县不展示，不允许编辑，导出区县展示为空，需求号#10679，为了后期导出若需要恢复数据展示，外面再封装一层
    public static function needShowRegion($platCD)
    {
        return self::isB2c($platCD);
    }

    /**
     * 更新母订单UPDATE_TIME时间，触发ES更新母订单
     *
     * @param $where
     */
    public static function triggerESUpdateParentOrder($where)
    {
        $model = M('op_order', 'tb_');
        $orders = $model->field('PARENT_ORDER_ID, PLAT_CD')->where($where)->select();
        foreach ($orders as $order) {
            if (!$order['PARENT_ORDER_ID']) {
                continue;
            }
            $parent_order_ids[] = $order['PARENT_ORDER_ID'];
            $check_parent[] = $order['PARENT_ORDER_ID'] . $order['PLAT_CD'];
        }
        if (!empty($parent_order_ids)) {
            $parent_orders = $model->field('ID,ORDER_ID, PLAT_CD')->where(['ORDER_ID' => ['in', $parent_order_ids]])->select();
        }

        //根据ORDER_ID和PLAT_CD确定唯一订单，排除不符合订单
        foreach ($parent_orders as &$item) {
            if (!in_array($item['ORDER_ID'] . $item['PLAT_CD'], $check_parent)) {
                unset($item);
            }
        }
        $parent_ids = array_column($parent_orders, 'ID');
        if (empty($parent_ids)) {
            return;
        }
        $res = $model->where(['ID' => ['in', $parent_ids]])->save(['UPDATE_TIME' => dateTime()]);
        if (!$res) {
            Logs($parent_ids, __FUNCTION__, 'fm');
        }
    }
}