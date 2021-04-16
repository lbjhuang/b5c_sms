<?php

class CompensationService extends Service
{
    # sheet2
    protected $sheet1_cols_map = [
        'A' => 'b5c_order_no',
        'B' => 'reason_val',
    ];

    protected $field_name_map = [
        'b5c_order_no' => "订单号",
        'reason_val'   => "申请原因",
    ];

    protected $sheet2_cols_map = [
        'A' => 'reason_val',
        'B' => 'reason_cd',
    ];

    public $errors = [];

    public function __construct($model)
    {
        $this->repository = new CompensationRepository($model);
    }

    public function warehoustList()
    {

        return $this->repository->warehoustList();
    }

    public function CompenStatusList()
    {

        return $this->repository->CompenStatusList();
    }

    public function CompenReasonList()
    {

        return $this->repository->CompenReasonList();
    }


    public function getOrderDetail($b5cOrderId)
    {
        $fields = ['ORDER_ID', 'PLAT_CD'];
        $order = $this->repository->getOrderByB5cOrderId($b5cOrderId, $fields);
        if (empty($order)) {
            return false;
        }

        $post_data = new stdClass();
        $post_data->thr_order_id = $order['ORDER_ID'];
        $post_data->plat_code = $order['PLAT_CD'];

        $temp['opOrderId'] = $post_data->thr_order_id;
        $temp['platCd'] = $post_data->plat_code;
        // $updateEsData = ApiModel::publicProcess([$temp]);
        // Logs($updateEsData, 'es update msg');
        $OrderEsModel = new OrderEsModel();
        $res = $OrderEsModel->orderDetail($post_data);
        #订单不存在
        if (empty($res['data'][0]['order_id'])) {
            return false;
        }
        #订单状态为非出库
        if ($res['data'][0]['patch_data'][0]['send_status'] != '已出库') {
            return false;
        }
        $res = $this->getCostPrice($res);

        $data = [
            'b5c_order_no' => $res['data'][0]['order_id'], #订单号  全局唯一
            'order_id' => $res['data'][0]['third_party_order_id'], #第三方平台id 可能重复
            'waybill_number' => $res['data'][0]['patch_data'][0]['waybill_number'], #运单号
            'delivery_warehouse' => $res['data'][0]['patch_data'][0]['delivery_warehouse'], #仓库名称
            'delivery_warehouse_cd' => $res['data'][0]['warehouse'], #仓库cd
            'sendout_time' => $res['data'][0]['sendout_time'], #出库时间
            'plat_name' => $res['data'][0]['platform_name'], #站点名称
            'plat_cd' => $res['data'][0]['plat_cd'], #站点cd
            'shipping_methods' => $res['data'][0]['patch_data'][0]['shipping_methods'],#物流方式
            'country' => $res['data'][0]['country'],#收货国家
        ];

        $data['guds'] = [];
        foreach ($res['data'][0]['guds'] as $value) {
            $tmp = [];
            $tmp['b5c_sku_id'] = $value['b5cSkuId'];
            $tmp['sku_id'] = $value['skuId'];
            $tmp['spu_name_cn'] = $value['product_name'];
            $tmp['spu_name_en'] = $value['product_name_en'];
            $tmp['image_url'] = $value['guds_img_cdn_addr'];
            $tmp['upc_id'] = $value['barcode'];
            $tmp['opt_name_value_str'] = $value['optNameValueStr'];
            $tmp['specification'] = $value['specification'];
            $tmp['weight'] = $value['weight'];
            $tmp['item_count'] = $value['itemCount'];
            $tmp['cost_usd_price'] = $value['costUsdPrice'];
            $tmp['trading_currency'] = $res['data'][0]['trading_currency'];
            $tmp['trading_price'] = $value['itemPrice'];
            $data['guds'][] = $tmp;
        }
        $data['created_by'] = userName();
        return $data;
    }

    /**
     * 沿用getOrderDetail方法逻辑
     * @param $b5cOrderIds
     * @return array|bool
     */
    public function getOrderDetails($b5cOrderIds)
    {
        $fields = ['ORDER_ID', 'PLAT_CD'];
        $orders = $this->repository->getOrderByB5cOrderIds($b5cOrderIds, $fields);
        if (empty($orders)) {
            return false;
        }
        foreach ($orders as $key => $order) {
            $post_data               = new stdClass();
            $post_data->thr_order_id = $order['ORDER_ID'];
            $post_data->plat_code    = $order['PLAT_CD'];

            $temp['opOrderId'] = $post_data->thr_order_id;
            $temp['platCd']    = $post_data->plat_code;
            $OrderEsModel = new OrderEsModel();
            $res = $OrderEsModel->orderDetail($post_data);
            #订单不存在
            if (empty($res['data'][0]['order_id'])) {
                continue;
            }
            #订单状态为非出库
            if ($res['data'][0]['patch_data'][0]['send_status'] != '已出库') {
                continue;
            }
            $res = $this->getCostPrice($res);

            $data[$key] = [
                'b5c_order_no'          => $res['data'][0]['order_id'], #订单号  全局唯一
                'order_id'              => $res['data'][0]['third_party_order_id'], #第三方平台id 可能重复
                'waybill_number'        => $res['data'][0]['patch_data'][0]['waybill_number'], #运单号
                'delivery_warehouse'    => $res['data'][0]['patch_data'][0]['delivery_warehouse'], #仓库名称
                'delivery_warehouse_cd' => $res['data'][0]['warehouse'], #仓库cd
                'sendout_time'          => $res['data'][0]['sendout_time'], #出库时间
                'plat_name'             => $res['data'][0]['platform_name'], #站点名称
                'plat_cd'               => $res['data'][0]['plat_cd'], #站点cd
                'shipping_methods'      => $res['data'][0]['patch_data'][0]['shipping_methods'],#物流方式
                'country'               => $res['data'][0]['country'],#收货国家
            ];

            $data[$key]['guds'] = [];
            foreach ($res['data'][0]['guds'] as $value) {
                $tmp                       = [];
                $tmp['b5c_sku_id']         = $value['b5cSkuId'];
                $tmp['sku_id']             = $value['skuId'];
                $tmp['spu_name_cn']        = $value['product_name'];
                $tmp['spu_name_en']        = $value['product_name_en'];
                $tmp['image_url']          = $value['guds_img_cdn_addr'];
                $tmp['upc_id']             = $value['barcode'];
                $tmp['opt_name_value_str'] = $value['optNameValueStr'];
                $tmp['specification']      = $value['specification'];
                $tmp['weight']             = $value['weight'];
                $tmp['item_count']         = $value['itemCount'];
                $tmp['cost_usd_price']     = $value['costUsdPrice'];
                $tmp['trading_currency']   = $res['data'][0]['trading_currency'];
                $tmp['trading_price']      = $value['itemPrice'];
                $data[$key]['guds'][]            = $tmp;
            }
            $data[$key]['created_by'] = userName();
        }
        return $data;
    }


    public function getCompen($where, $field)
    {
        return $this->repository->getCompen($where, $field);
    }

    public function compenCreate($params)
    {
        #检测参数
        $this->verificationCreate($params);
        #检测订单是否合法
        $order = $this->getOrderDetail($params['b5c_order_no']);
        if (!$order) {
            throw new Exception(L('查询失败，当前订单不满足赔付条件'));
        }
        $compen = $this->getCompen(['b5c_order_no' => $params['b5c_order_no'], 'deleted_by' => ['exp', 'IS NULL']]);
        if ($compen) {
            throw new Exception(L('查询失败，当前此订单已经在赔付处理'));
        }
        #创建赔付单号
        $compensateNo = $this->repository->createCompenNo();

        #处理主表数据
        $compenData = $order;
        $compenGudsData = $order['guds'];
        unset($compenData['guds']);
        if ($params['material_img_json'] && count($params['material_img_json']) > 0) {
            $compenData['is_post_img'] = 1;
            $compenData['material_img_json'] = json_encode($params['material_img_json']);
        }
        if ($params['material_remark']) {
            $compenData['material_remark'] = $params['material_remark'];
        }
        $compenData['sendout_time'] = date("Y-m-d H:i:s", substr($compenData['sendout_time'], 0, -3));
        $compenData['compensate_no'] = $compensateNo;
        $compenData['uid'] = $_SESSION['userId'];
        $compenData['reason_cd'] = $params['reason_cd'];
        $compenData['status_cd'] = 'N003880001';
        $compenData['compensate_no'] = $compensateNo;
        $compenData['created_by'] = userName();
        $compenData['created_at'] = date('Y-m-d H:i:s');
        unset($compenData['delivery_warehouse']);
        unset($compenData['plat_name']);
        #写入主表获取主键
        $compensateId = $this->repository->createCompen($compenData);
        if (!$compensateId) {
            throw new Exception(L('主表更新失败，请重试'));
        }
        $compenData['compen_id'] = $compensateId;
        $compenGudsData = array_map(function ($value) use ($compensateId) {
            $value['created_by'] = userName();
            $value['created_at'] = date('Y-m-d H:i:s');
            $value['compensate_id'] = $compensateId;
            return $value;
        }, $compenGudsData);
        $compenGudsRe = $this->repository->createCompenGuds($compenGudsData);
        if (!$compenGudsRe) {
            throw new Exception(L('赔付商品表更新失败，请重试'));
        }
        #日志
        $log = [];
        $log['content'] = '提交赔付申请';
        $log['created_by'] = userName();
        $log['created_at'] = date('Y-m-d H:i:s');
        $log['created_at'] = date('Y-m-d H:i:s');
        $log['compensate_id'] = $compensateId;
        $logRe = $this->addLog($log);
        if (!$logRe) {
            throw new Exception(L('日志写入失败，请重试'));
        }
        #发送msg
        #发送给物流赔付人员

        $wid = $this->repository->getWidByRole();
        $this->sendWxMsg($compenData, $compenData['status_cd'], $wid);


    }

    #构建列表搜索条件 搜索数据
    public function CompenList($params)
    {
        $search = [];
        if (!empty($params['order_id'])) $search['compen.order_id'] = $params['order_id'];
        if (!empty($params['b5c_order_no'])) $search['compen.b5c_order_no'] = $params['b5c_order_no'];
        if (!empty($params['created_by'])) $search['compen.created_by'] = $params['created_by'];
        if (!empty($params['status_cd'])) $search['compen.status_cd'] = ['in', $params['status_cd']];
        if (!empty($params['waybill_number'])) $search['compen.waybill_number'] = $params['waybill_number'];
        if (!empty($params['plat_cd'])) $search['compen.plat_cd'] = ['in', $params['plat_cd']];
        if (!empty($params['delivery_warehouse_cd'])) $search['compen.delivery_warehouse_cd'] = ['in', $params['delivery_warehouse_cd']];
        if (!empty($params['b5c_sku_id'])) $search['compen_guds.b5c_sku_id'] = $params['b5c_sku_id'];
        if (!empty($params['compensate_no'])) $search['compen.compensate_no'] = $params['compensate_no'];
        if (!empty($params['is_post_img'])) $search['compen.is_post_img'] = $params['is_post_img'];
        if (!empty($params['sendout_time']['start']) && !empty($params['sendout_time']['end']))
            $search['compen.sendout_time'] = ['between', [$params['sendout_time']['start'] . ' 00:00:00', $params['sendout_time']['end'] . ' 23:59:59']];
        if (!empty($params['created_at']['start']) && !empty($params['created_at']['end']))
            $search['compen.created_at'] = ['between', [$params['created_at']['start'] . ' 00:00:00', $params['created_at']['end'] . ' 23:59:59']];

        $pageData = [];
        $pageData['page'] = $params['page'] ? $params['page'] : 1;
        $pageData['page_size'] = $params['page_size'] ? $params['page_size'] : 20;
        $pageData['first_row'] = ($pageData['page'] - 1) * $pageData['page_size'];
        $field = ['compen.*'];
        $search['compen.deleted_at'] = ['exp', 'IS NULL'];
        $count = $this->repository->CompenListCount($search);
        $searchData = $this->repository->CompenList($search, $field, $pageData);
        #权限
        $uids = $this->repository->getUidByRole(['仓储物流组', 'B2C销售', 'B2B销售', '采购权限']);
        $uids = array_column($uids, 'M_ID');
        $has_delete_auth = 0;
        $has_edit_auth = 0;
        if (in_array($_SESSION['userId'], $uids)) {
            #有删除权限
            $has_delete_auth = 1;
            $has_edit_auth = 1;
        }
        $searchData = array_map(function ($value) use ($has_edit_auth) {
            if ($value['material_img_json']) $value['material_img_json'] = json_decode($value['material_img_json'], 1);
            if ($value['remark_json']) $value['remark_json'] = json_decode($value['remark_json'], 1);
            $value['has_edit_auth'] = $has_edit_auth;
            return $value;
        }, $searchData);
        $searchData = CodeModel::autoCodeTwoVal($searchData, ['plat_cd', 'delivery_warehouse_cd', 'reason_cd', 'status_cd']);

        return [
            'list' => $searchData,
            'button' => [
                'has_delete_auth' => $has_delete_auth
            ],
            'page' => [
                'count' => $count,
                'page_index' => $pageData['page'],
                'page_size' => $pageData['page_size'],
                'page_total' => ceil($count / $pageData['page_size'])
            ]
        ];
    }

    public function compenEdit($params)
    {

        $id = $params['id'];
        $update = [];
        $compen = $this->getCompen(['id' => $id, 'deleted_by' => ['exp', 'IS NULL']]);
        if (!$compen) {
            throw new Exception(L('该赔付单不存在'));
        }


        if ($params['status_cd'] == 'N003880009' && !preg_match('/^\d+(\.\d+)?$/', $params['compersate_amount'])) {
            throw new Exception(L('实际索赔金额必填'));
        }
        #后续状态不为索赔中和 索赔成功  清空金额

        if (in_array($compen['status_cd'], ['N003880009', 'N003880007']) && $params['status_cd'] && !in_array($params['status_cd'], ['N003880009', 'N003880007'])) {
            $update['compersate_estimate_amount'] = null;
            $update['compersate_amount'] = null;
        }

        if ($compen['material_img_json'] && empty($params['material_img_json'])) {
            throw new Exception(L('上传文件传输有误'));
        }

        if ($params['reason_cd']) $update['reason_cd'] = $params['reason_cd'];
        if ($params['status_cd']) $update['status_cd'] = $params['status_cd'];
        if ($params['material_img_json']) {
            $update['material_img_json'] = json_encode($params['material_img_json']);
            if (count($params['material_img_json']) > 0) {
                $update['is_post_img'] = 1;
            } else {
                $update['is_post_img'] = 0;
            }
        }
        $update['material_remark'] = $params['material_remark'];
        if (in_array($params['status_cd'], ['N003880009', 'N003880007'])) {
            if ($params['compersate_estimate_amount']) $update['compersate_estimate_amount'] = $params['compersate_estimate_amount'];
            if ($params['compersate_amount']) $update['compersate_amount'] = $params['compersate_amount'];
        }

        $update['remark_json'] = json_encode($params['remark_json']);
        $update['updated_by'] = userName();
        $update['updated_at'] = date('Y-m-d H:i:s');
        $re = $this->repository->compenUpdate($update, ['id' => $id]);
        if ($re === false) {
            throw new Exception(L('更新失败，请重试'));
        }
        if ($params['status_cd'] && $params['status_cd'] != $compen['status_cd']) {
            #记录状态变化
            #写日志
            $log = [];
            $compen = CodeModel::autoCodeOneVal(
                [
                    'last_status_cd' => $compen['status_cd'],
                    'now_status_cd' => $params['status_cd'],
                ]
                ,
                ['last_status_cd', 'now_status_cd']);
            $log['content'] = $compen['last_status_cd_val'] . '状态变更为' . $compen['now_status_cd_val'];
            $log['created_by'] = userName();
            $log['created_at'] = date('Y-m-d H:i:s');
            $log['created_at'] = date('Y-m-d H:i:s');
            $log['compensate_id'] = $id;
            $logRe = $this->addLog($log);
            if (!$logRe) {
                throw new Exception(L('日志写入失败，请重试'));
            }
            //改成只要状态变化就进行通知
            $compen = $this->repository->getCompen(['id' => $id]);
            if (in_array($params['status_cd'], ['N003880001'])) {
                //待处理状态通知角色人员
                $wid = $this->repository->getWidByRole();
                $compen['compen_id'] = $compen['id'];
                $this->sendWxMsg($compen, $params['status_cd'], $wid);
            } else {
                $wid = $this->getWidByUid($compen['uid']);
                $compen['compen_id'] = $compen['id'];
                $this->sendWxMsg($compen, $params['status_cd'], $wid);
            }
        }
    }

    #删除
    public function compenDelete($params)
    {
        $ids = $params['ids'];
        if (empty($ids)) throw new Exception(L('id不能为空'));
        $where['id'] = ['in', $ids];
        $data['deleted_by'] = userName();
        $data['deleted_at'] = date('Y-m-d H:i:s');
        $re = $this->repository->compenUpdate($data, $where);
        if ($re === false) {
            throw new Exception(L('删除失败请重试'));
        }

    }

    #检测过滤create数据
    public function verificationCreate($params)
    {

        $rules = [
            'reason_cd' => 'required|string|size:10',
            'b5c_order_no' => 'required|string',
        ];
        $custom_attributes = [
            'reason_cd' => '申请原因',
            'b5c_order_no' => '订单id',
        ];
        $this->validate($rules, $params, $custom_attributes);
    }

    /**
     * @param $rules
     * @param $data
     * @param $custom_attributes
     *
     * @throws Exception
     */
    public function validate($rules, $data, $custom_attributes)
    {

        ValidatorModel::validate($rules, $data, $custom_attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $value) {
                throw new Exception(L($value[0]), 40001);
            }
        }
    }

    #获取成本价格
    public function getCostPrice($res)
    {
        import("@.Action.Home.OrdersAction");
        $Orders = new OrdersAction();
        $res['data'][0]['guds'] = $Orders->getCustoms($res['data'][0]['guds'], null, $res['data'][0]['order_id'], 'skuId', 'costPrice');
        return $res;
    }

    public function addLog($data)
    {
        return $this->repository->addLog($data);
    }

    public function compenLog($params)
    {
        $id = !empty($params['id']) ? $params['id'] : 0;
        if (!$id) {
            throw new Exception(L('id有误'));
        }
        $where['compensate_id'] = $id;
        return $this->repository->compenLog($where);
    }

    public function compenDetail($params)
    {
        $id = !empty($params['id']) ? $params['id'] : 0;
        if (!$id) {
            throw new Exception(L('id有误'));
        }
        $whereCompent['id'] = $id;
        $whereCompentGuds['compensate_id'] = $id;
        $whereCompent['deleted_by'] = ['exp', 'is null'];
        $compent = $this->repository->getCompen($whereCompent);
        if (empty($compent)) {
            throw new Exception(L('id有误'));
        }
        $compentGuds = $this->repository->compenGuds($whereCompentGuds);
        $compent = CodeModel::autoCodeOneVal($compent, ['plat_cd', 'delivery_warehouse_cd', 'reason_cd', 'status_cd']);
        $compent['material_img_json'] = json_decode($compent['material_img_json'], 1);
        $tmpImgJson = [];
        foreach ($compent['material_img_json'] as $key=>$value) {
            #兼容旧数据
            if (empty($value['name']) && empty($value['savename'])) {
                $tmpImgJson[$key]['name'] = $value;
                $tmpImgJson[$key]['savename'] = $value;
            }

        }
        if(count($tmpImgJson) > 0){
            $compent['material_img_json'] = $tmpImgJson;
        }
        $compent['remark_json'] = json_decode($compent['remark_json'], 1);
        $compent['guds'] = $compentGuds;
        return $compent;
    }

    public function compenExport($params)
    {
        if (empty($params['ids'])) {
            throw new Exception(L('id有误'));
        }
        $data = $this->repository->getExportListById($params['ids']);
        $data = CodeModel::autoCodeTwoVal($data, ['plat_cd', 'delivery_warehouse_cd', 'reason_cd', 'status_cd']);
        $export = new CompenExport();
        $export->setData(['data' => $data])
            ->download();
    }

    public function getWidByUid($user_ids)
    {
        return $this->repository->getWidByUid($user_ids);
    }

    public function sendWxMsg($data, $status, $wid)
    {
        $compen_id = $data['id'];
        $tab_data = [
            'url' => urldecode('/index.php?' . http_build_query(['g' => 'Oms', 'm' => 'compensation', 'a' => 'detail', 'id' => $compen_id])),
            'name' => '查看赔付单'
        ];
        $replaceData = [
            'title' => '异常订单赔付通知事项',
            'detail_url' => ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]),
        ];
        if ($status == 'N003880001') {
            //待处理状态特殊处理
            unset($data['id']);
            foreach ($wid as $value) {
                $name =  $value['M_NAME'];
                $content = ">
><font color=warning >异常订单赔付通知事项</font>
>TO：<font color=info >{$name}</font>
><font color=comment >新增一个异常赔付单需要你处理，请知悉</font>
>赔付单号：<font color=comment >compensate_no</font>
>发起人：<font color=comment >created_by</font>
>发起时间：<font color=comment >created_at</font>
>如需查看详情，请点击：[查看详情](detail_url)";

                $content = $this->replace_template_var($content, $replaceData);
                $content = $this->replace_template_var($content, $data);
                $res = ApiModel::WorkWxSendMarkdownMessage([$value['wid']], $content);
                logs(['wid' => $value, 'content' => $content, 'res' => $res], __function__, 'CompenSendMsg');
            }
            return;
        } else  if (!$status) {
            //手动触发通知
            $content = ">
>TO：<font color=info >created_by</font>
><font color=warning >赔付物流人员提醒你上传文件，请知悉</font>
>赔付单号：<font color=comment >compensate_no</font>
>发起人：<font color=comment >created_by</font>
>发起时间：<font color=comment >created_at</font>
>如需查看详情，请点击：[查看详情](detail_url)";
        } else {
            $status_name = TbMsCmnCdModel::getVal($status);
            $content = ">
>TO：<font color=info >created_by</font>
><font color=warning >赔付单已经在{$status_name}，请知悉</font>
>赔付单号：<font color=comment >compensate_no</font>
>发起人：<font color=comment >created_by</font>
>发起时间：<font color=comment >created_at</font>
>如需查看详情，请点击：[查看详情](detail_url)";
        }
        $content = $this->replace_template_var($content, $replaceData);
        unset($data['id']);
        $content = $this->replace_template_var($content, $data);
        $res = ApiModel::WorkWxSendMarkdownMessage($wid, $content);
        logs(['wid' => $wid, 'content' => $content, 'res' => $res], __function__, 'CompenSendMsg');
    }

    public function replace_template_var($template, $data)
    {
        if ($data) {
            foreach ($data as $k => $v) {
                $template = str_replace($k, $v, $template);
            }
        }
        return $template;
    }

    /**
     * 批量导入
     * @return array
     * @throws Exception
     */
    public  function excelImportCompensate()
    {
        /**
         * 处理 excel 文件数据
         */
        if(!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            throw new Exception(L('请上传excel文件'));
        }
        session_write_close();
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                throw new Exception(L('excel文件读取失败'));
            }
        }
        $PHPExcel = $PHPReader->load($filePath); // 文件名称
        # 读取每个工作簿的内容
        $list = $this->getExcelSheetData($PHPExcel->getSheet(0), $this->sheet1_cols_map);
        $reason_cds = $this->getExcelSheetData($PHPExcel->getSheet(1), $this->sheet2_cols_map);

        # 数据基础校验
        if(empty($list)) {
            throw new Exception(L('excel 导入数据为空，请检查excel'));
        }
        if (count($list) > 500) {
            throw new Exception(L('导入订单不能超过500条，请检查excel'));
        }
        if(empty($reason_cds)) {
            throw new Exception(L('excel sheet2 导入数据为空，请检查excel sheet2 数据'));
        }
        $reason_cds_map = array_column($reason_cds,"reason_cd","reason_val");
        # 数据校验
        # 校验 ①订单是否为空、②订单是否存在  ③ 订单是否已经创建赔付单
        $b5c_order_nos = array_column($list,'b5c_order_no');

        $this->validateCompensateExcelData($list, $reason_cds_map, $b5c_order_nos);
        if (!empty($this->errors))  return $this->errors;
        # 执行数据插入程序
        foreach ($list as $item) {
            $params = [
                'b5c_order_no' => $item['b5c_order_no'],
                'reason_cd' => $reason_cds_map[$item['reason_val']],
            ];
            $this->compenCreate($params);
        }
    }

    /**
     * excel数据验证
     * @param $list excel内容数据
     * @param $reason_cds_map 申请原因code映射
     * @param $b5c_order_nos ERP订单号
     * @return array
     */
    private function validateCompensateExcelData($list, $reason_cds_map, $b5c_order_nos)
    {
        $orders = $order_wms_compensations  =  [];
        # 查询订单订单数据
            # 订单数据查询 这部分数据需要在修改
        $op_order_model = D("TbOpOrd");
        $orders = $op_order_model->where(
            [
                "B5C_ORDER_NO" => ["in", $b5c_order_nos]
            ]
        )->field([
            "tb_op_order.ID","tb_op_order.ORDER_ID","tb_op_order.PLAT_CD",
            "tb_op_order.B5C_ORDER_NO",
        ])->select();
        $orders = array_column($orders, NULL,'B5C_ORDER_NO');
        # 赔付单数据查询
        $order_wms_compensation_model = D("Oms/OrderWmsCompensation");
        $order_wms_compensations = $order_wms_compensation_model->field(["id","compensate_no",'b5c_order_no'])
            ->where([
                "b5c_order_no" => ["in", $b5c_order_nos],
                "deleted_at" => ["exp", 'IS NULL'],
            ])
            ->select();
        $order_wms_compensations = array_column($order_wms_compensations,NULL,'b5c_order_no');
        # 基础数据校验
        $order_no_count_values = array_count_values(array_column($list,'b5c_order_no'));
        foreach ($list as  $row => $item)
        {
            $col = 'A';
            foreach ($item as $field =>  $val)
            {
                $tmp = [
                    'row'  => $row,
                    'col'  => $col,
                    'cell' => $col. $row,
                    "val"  => $val,
                ];
                $field_name = isset($this->field_name_map[$field]) ? $this->field_name_map[$field] : "";
                # 判断值是否为空
                if(empty($val)) {
                    $tmp['msg'][] = "[{$field_name}]". "值不能为空";
                }
                # 判断 b5c_order_no  reason_val
                if($field == 'b5c_order_no')
                {
                    if($order_no_count_values[$val] >  1) {
                        $tmp['val'] = $val;
                        $tmp['msg'][] = "[{$field_name}]值： {$val}重复，请检查";
                    }
                    # 判断字段订单号是否存在
                    if(!isset($orders[$val]))
                    {
                        $tmp['msg'][] = "[{$field_name}]". "值：{$val}不存在";
                    }
                    # 判断赔付单是否存储
                    if(isset($order_wms_compensations[$val])) {
                        $tmp['msg'][] = "[{$field_name}]". "值：{$val}已存在";
                    }
                }
                # 申请原因
                if ($field  == 'reason_val') {
                    #
                    if(!isset($reason_cds_map[$val])) {
                        $tmp['msg'][] = "[{$field_name}]". "值：{$val}异常，请检查";
                    }
                }
                if($tmp['msg']) {
                    $tmp['msg_string'] = implode(",\r\n", $tmp['msg']);
                    $this->errors[] = $tmp;
                }
                $col ++ ;
            }
        }
        return $this->errors;
    }

    /**
     * 读取excel sheet 工作簿的内容
     * @param $sheet
     * @param $cols_map
     * @param int $start_line
     * @return array
     * @author Redbo He
     * @date 2021/3/23 10:18
     */
    protected function getExcelSheetData($sheet, $cols_map, $start_line = 2)
    {
        $rows = $sheet->getHighestRow();//行数
        $cols = $sheet->getHighestColumn();//列数
        $list = [];
        for ($row = $start_line; $row <= $rows; $row++){ //行数是以第2行开始
            for ($col = 'A'; $col <= $cols; $col++) {
                $column_name = isset($cols_map[$col]) ? $cols_map[$col] : '';
                $val = $sheet->getCell($col . $row)->getValue();
                $list[$row][$column_name] = $val;
            }
        }
        return $list;
    }

    /**
     * 一键生成发票
     * @param $request_data
     * @throws Exception
     */
    public function oneKeyGenerateInvoice($request_data)
    {
        if (empty($request_data['compensate_ids'])) {
            throw new Exception(L('请勾选赔付单进行一键生成发票'));
        }
        if (count($request_data['compensate_ids']) > 20) {
            throw new Exception(L('最多支持20条一键生成发票'));
        }
        $data = [];
        $compensation_data = $this->repository->getExportListById($request_data['compensate_ids']);
        $export_date = date('Y/m/d');
        foreach ($compensation_data as $item) {
            if (!$item['trading_price']) {
                $item['trading_price'] = $item['cost_usd_price'];
            }
            if ($item['trading_price']) {
                //应产品要求，交易价格/成本价格需要除以1.21并且保留2位小数
                $item['trading_price'] = bcdiv($item['trading_price'], 1.21, 2);
            }
            if ($item['weight']) $item['weight'] = $item['weight'] / 1000;//转成kg
            $item['export_date'] = $export_date;
            //收货人地址组装
            $temp_address = [
                $item['ADDRESS_USER_ADDRESS1'],
                $item['ADDRESS_USER_ADDRESS2'],
                $item['ADDRESS_USER_CITY'],
                $item['ADDRESS_USER_PROVINCES'],
                $item['ADDRESS_USER_COUNTRY'],
            ];
            $temp_address = implode(',', array_filter($temp_address));
            $item['address'] = $temp_address;

            //
            $item['netto_price'] = bcmul($item['item_count'], $item['trading_price'], 2);//Gesamtpreis字段
            $item['mwst_price'] =  bcmul($item['netto_price'], 0.21, 2);//Mwst字段
            $item['total_price'] = bcadd($item['netto_price'], $item['mwst_price'], 2);//Brutto-Gesamtpreis字段
            $data[$item['compensate_id']][] = $item;
        }
        $data = array_values($data);
        $export = new CompenInvoiceExport();
        $export->deleteTmpFiles();//先清理上次生成的excel、图片、压缩包
        foreach ($data as $item) {
            //每次生成excel需要清空上次生成的数据所有需要重新new（待优化）
            //发票excel
            $export1 = new CompenInvoiceExport();
            $export1->setData($item)->generateInvoice('invoice-'. $item[0]['compensate_no']. '-01');
            //商品excel
            $export2 = new CompenInvoiceExport();
            $export2->setData($item)->generateInvoiceGoods('invoice-'. $item[0]['compensate_no']. '-02');
        }
        $export->packInvoiceExcelFile();//打包生成好的所有excel文件
        $export->downloadPackage();//压缩包下载
    }

    /**
     * 上传文件提醒
     * @param $request_data
     * @throws Exception
     */
    public function remindUploadFiles($request_data)
    {
        $compensate_data = $this->repository->getCompen(['id' => $request_data['compensate_id']]);
        $wid = $this->repository->getWidByUid($compensate_data['uid']);
        if (empty($compensate_data)) {
            throw new Exception(L('赔付单为空'));
        }
        if (empty($wid)) {
            throw new Exception(L('企业微信id为空'));
        }
        $this->sendWxMsg($compensate_data, '', $wid);
    }

}
