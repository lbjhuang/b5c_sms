<?php

/**
 * User: yangsu
 * Date: 18/1/12
 * Time: 14:21
 */
class ApiModel extends Model
{


    /**
     * @param     $url
     * @param     $data_json
     * @param int $connecttimeout
     * @param int $timeout
     *
     * @return mixed
     */
    public static function postRequestJson($url, $data_json, $connecttimeout = 50, $timeout = 50)
    {
        if (is_array($data_json)) {
            $data_json = json_encode($data_json);
        }
        return HttpTool::Curl_post_json($url, $data_json, $connecttimeout, $timeout);
    }

    private static function postRequest($url, $data, $connecttimeout = 50, $timeout = 50)
    {
        return HttpTool::curlReq($url, $data, $connecttimeout, $timeout);
    }

    private static function postRequestUrlencoded($url, $data)
    {
        return HttpTool::curlReq($url, http_build_query($data));
    }

    private static function addLogs($data)
    {
        $logs_arr = [

        ];

        Logs($logs_arr, __CLASS__, __FUNCTION__);
    }

    public static function test()
    {
        self::addLogs();
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public static function getRequest($url, $connecttimeout = 50, $timeout = 50)
    {
        return HttpTool::curlGet($url, $connecttimeout, $timeout);
    }

    /**
     * @param $sku
     * @param $sale_team_code
     *
     * @return mixed
     */
    public static function batchStockGet($sku, $sale_team_code)
    {
        $url = HOST_S_URL . '/s/b5c/batchStock?skuId=' . $sku . '&saleTeamCode=' . $sale_team_code;
        $res = HttpTool::curlGet($url);
        return $res;
    }

    /**
     * 释放占用
     *
     * @param $data
     *
     * @return mixed
     */
    public static function releaseOccupancy($data)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $res = HttpTool::Curl_post_json($url, $data);
        Logs([$url, $data, $res], __FUNCTION__, __CLASS__);
        return $res;
    }

    public static function releaseOccupancyOrder($order_batch_id)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'BATCH_RELEASE_OCCUPY_PROCESS';
        $data['processId'] = uuid();
        $data['data']['releaseOccupy'][0]['orderId'] = $order_batch_id;
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([$url, $data, $res], __FUNCTION__, __CLASS__);
        return $res;
    }

    public static function homeTransfer($data)
    {
        $url = HOST_URL_API . '/batch/ascription.json';
        $response_json = self::postRequestJson($url, $data);
        $response = json_decode($response_json, true);
        Logs(['url' => $url, 'data' => $data, 'res' => $response], __FUNCTION__, __CLASS__);
        return $response;
    }
    public static function homeTransferAttr($data)
    {
        $url = HOST_URL_API . '/batch/ascription.json';
        $data['handleType'] = 2;
        $response_json = self::postRequestJson($url, $data);
        $response = json_decode($response_json, true);
        Logs(['url' => $url, 'data' => $data, 'res' => $response], __FUNCTION__, 'attr_last_api');
        return $response;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function batchStockPost($data)
    {
        $url = API_SEARCH . '/s/b5c/batchStock';
        $res = HttpTool::Curl_post_json($url, $data);
        Logs($data, '$data', 'batchStockPostapi');
        Logs($url, '$url', 'batchStockPostapi');
        Logs($res, '$res', 'batchStockPostapi');
        return $res;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function batchOccupy($data)
    {
        $url = HOST_URL_API . '/batch/update_occupy.json';
        $res = HttpTool::Curl_post_json($url, $data);
        return $res;
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    public static function b2bOrderData($order_id)
    {
        $url = SMS2_URL . '/index.php?m=b2b&a=b2bOrderData&api=b08a8be1abd25efd858141757dbfc5c5&order_id=' . $order_id;
        if ('sms2.b5c.com' == $_SERVER ['HTTP_HOST']) $url = 'http://sms2.b5c.com/index.php?m=b2b&a=b2bOrderData&api=b08a8be1abd25efd858141757dbfc5c5&order_id=' . $order_id;
        $res = HttpTool::CurlGet($url);
        $res = json_decode($res, true);
        Logs($url, 'url', 'b2b');
        return $res;
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    public static function updateShipAllPower($ship_id)
    {
        $url = SMS2_URL . '/index.php?m=b2b&a=updateShipAllPower&api=b08a8be1abd25efd858141757dbfc5c5&ship_id=' . $ship_id;
        if ('sms2.b5c.com' == $_SERVER ['HTTP_HOST']) {
            $url = 'http://sms2.b5c.com/index.php?m=b2b&a=updateShipAllPower&api=b08a8be1abd25efd858141757dbfc5c5&ship_id=' . $ship_id;
        }
        $res = HttpTool::CurlGet($url);
        $res = json_decode($res, true);
        Logs($url, 'updateShipAllPower', 'b2b');
        return $res;
    }

    /**
     * @param $data
     * @param $timeout
     *
     * @return mixed
     */
    public static function electronicOrder($data, $timeout = 6)
    {
        $url = ELECTRONIC_HOST . '/lgt/print-elec';
        $res = HttpTool::Curl_post_json($url, json_encode($data, JSON_UNESCAPED_UNICODE), $timeout, $timeout);
        return json_decode($res, true);

    }

    /**
     * @param     $data
     * @param int $timeout
     *
     * @return mixed
     */
    public static function recommend($data, $timeout = 120)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = HttpTool::Curl_post_json($url, $json_data, $timeout, $timeout);
        Logs(['url' => $url, 'json_data' => $json_data, 'res' => $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);

    }

    /**
     * @param array $order_arr
     *
     * @return mixed
     */
    public static function publicProcess(array $order_arr, $timeout = 1)
    {
        import('ORG.Util.String');
        if (0 == RedisModel::get_key('erp_switch_update_order_from_es')) {
            return [];
        }
        $data_arr = [
            'processCode' => 'PROCESS_ES_ORDER_REFRESH',
            'processId' => String::uuid(true),
            'data' => $order_arr
        ];
        $url = HOST_S_URL . '/process/public_process.json';
        $res = HttpTool::Curl_post_json($url, json_encode($data_arr, JSON_UNESCAPED_UNICODE), $timeout, $timeout);
        Logs(['url' => $url, 'data_arr' => $data_arr, 'res' => $res,], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    /**
     * 批次占用
     *
     * @param $batch
     *
     * @return mixed
     */
    public static function occupyBatch($batch, $type = 0)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'BATCH_OCCUPY_PROCESS';
        $data['data']['occupy'] = [$batch];
        if ($type == 1) {
            $data['processCode'] = 'ONWAYALLO_OCCUPY_PROCESS';
            $data['data']['occupy'] = $batch;
        }
        if ($type == 2) {
            $data['processCode'] = 'BATCH_OCCUPY_V2_PROCESS';
            $data['data']['occupy'] = $batch;
        }
        
        $data['processId'] = create_guid();
       
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
       
        ELog::add(['info' => '批次占用请求', 'type' => $data['processCode'], 'request' => $data, 'response' => $res]);
        Logs(['url' => $url, 'data' => $data, 'res' => $res,], __FUNCTION__, 'allo_java_api');
        return $res;
    }

    public static function returnGoodsOccupyBatch($param)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'REFUND_OCCUPY_PROCESS';
        $data['processId'] = create_guid();
        $data['data']['occupy'] = $param;
        $data_json = json_encode($data);
        $res = HttpTool::Curl_post_json($url, $data_json);
        Logs(['url' => $url, 'data' => $data_json, 'res' => $res,], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    /**
     * 指定在途
     *
     * @param $batch
     *
     * @return mixed
     */
    public static function occupyOn($batch)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'BATCH_OCCUPY_PROCESS';
        $data['processId'] = create_guid();
        $data['data']['occupy'] = [$batch];
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
        return $res;
    }

    /**
     * 根据订单id释放批次
     *
     * @param $order_id
     *
     * @return mixed
     */
    public static function freeUpBatch($param)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'BATCH_RELEASE_OCCUPY_PROCESS';
        $data['processId'] = create_guid();
        $data['data']['releaseOccupy'] = $param;
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
        ELog::add(['info' => 'SCM解除占用', 'request' => $data, 'response' => $res]);
        return $res;
    }
    /**
     * 出库完结解除占用  修改use_type = 10
     *
     * @param $order_id
     *
     * @return mixed
     */
    public static function freeUpBatchAllo($param)
    {
        $url = HOST_URL_API . '/process/public_process.json';
        $data['processCode'] = 'BATCH_RELEASE_OCCUPY_V2_PROCESS';
        $data['processId'] = create_guid();
        $data['data']['releaseOccupy'] = $param;
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
        Logs(['url' => $url, 'data' => $data_json, 'res' => $res,], __FUNCTION__, __CLASS__);
        ELog::add(['info' => 'SCM解除占用', 'request' => $data, 'response' => $res]);
        return $res;
    }

    /**
     * 查询商品在每个仓库库存
     *
     * @param $param
     *
     * @return mixed
     */
    public static function batchStock($param)
    {
        $url = HOST_URL_API . '/batch/orderStock/search.json';
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($param)), true);
        return $res;
    }

    /**
     * 根据商品和仓库返回应占用批次
     *
     * @param $param
     *
     * @return mixed
     */
    public static function batchSearch($param)
    {
        $url = HOST_URL_API . '/batch/search.json';
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($param)), true);
        return $res;
    }

    /**
     * 在途标记结束
     *
     * @param $param
     * @param $is_old // 是否是小于2019-07-30 00:00:00的老订单
     *
     * @return mixed
     */
    public static function onWayEnd($param, $is_old = false)
    {
        $url = HOST_URL_API . '/batch/mark.json';
        if ($is_old) {
            $url = HOST_URL_API . '/batch/OldMark.json';
        }

        $data['processCode'] = 'BATCH_MARK';
        $data['processId'] = create_guid();
        $data['data'] = $param;
        $res = json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
        ZUtils::saveLog(['req' => $data, 'res' => $res, 'url' => $url], '扣减在途');
        return $res;
    }

    /**
     * @param $purchase_order_no
     *
     * @return mixed
     */
    public static function onWayRemove($purchase_order_no)
    {
        $url = HOST_URL_API . '/batch/remove.json';
        $data['processCode'] = 'ONWAY_REMOVE';
        $data['processId'] = create_guid();
        $data['data'] = [['operatorId' => $_SESSION['userId'], 'purchaseOrderNo' => $purchase_order_no]];
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        return $res;
    }

    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function goOutBatch($data_json)
    {
        $url = SMS2_URL . 'index.php?g=oms&m=OutGoing&a=mulDeliverGoods&api=b08a8be1abd25efd858141757dbfc5c5';
        $res = json_decode(HttpTool::Curl_post_json($url, $data_json,200,200), true);
        Logs($url, '$url');
        return $res;
    }

    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function return_to_patch($data_json)
    {
        $url = SMS2_URL . 'index.php?g=OMS&m=TrackingNo&a=return_to_patch&api=b08a8be1abd25efd858141757dbfc5c5';
        $res = json_decode(HttpTool::Curl_post_json($url, $data_json,200,200), true);
        Logs($url, '$url');
        return $res;
    }

    /**
     * Test
     * http://10.8.2.91:8082/thirdApi/external/revenueSplit?date=2018-05-01&erp_purchase_code=N001291000&erp_intro_code=N001301300&erp_sales_code=N001281000
     *
     * @param $data
     *
     * @return mixed
     */
    public static function revenueSplit($data)
    {
        $url = BI_API_REVEN . '/external/revenueSplit?date=' . $data['date'] . '&erp_purchase_code=' . $data['purchase_code'] . '&erp_intro_code=' . $data['intro_code'] . '&erp_sales_code=' . $data['sales_code'] . '';
        Logs($url, '$url');
        $res = json_decode(HttpTool::curlGet($url), true);
        return $res;
    }

    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function thrSendOut($data_json, $connecttimeout = 5,$timeout = 5)
    {
        $url = THIRD_DELIVER_GOODS . '/op/third-deliver-goods';
        $res = HttpTool::Curl_post_json($url, $data_json, 8, 8);//5s改8,api请求较长
        Logs([$url, $data_json, $res], 'signSendOut', 'thrSendOut');
        self::addLogs();
        return json_decode($res, true);
    }
    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function thrSendOutNew($data_json, $connecttimeout = 5,$timeout = 5)
    {
        $url = THIRD_DELIVER_GOODS . '/op/third-deliver-goods';
        $res = HttpTool::Curl_post_json($url, $data_json, $connecttimeout, $timeout);//5s改8,api请求较长
        Logs([$url, $data_json, $res], 'signSendOut', 'thrSendOut');
        self::addLogs();
        return json_decode($res, true);
    }
    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function notThirdDeliverGoods($data)
    {
        //确认无需标记发货
        $url = HOST_URL . '/erp_order/notThirdDeliverGoods.json';
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([$url, $data_json, $res], 'notThirdDeliverGoods', 'notThirdDeliverGoods');
        return $res;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function winitVoidWarehouse($data)
    {
        //作废出库
        $url = THIRD_DELIVER_GOODS . '/lgt/handleVoidoutbound';
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([$url, json_encode($data), $res], 'handleVoidoutbound', 'handleVoidoutbound');
        return $res;
    }

    /**
     * @param $data_json
     *
     * @return mixed
     */
    public static function getCustomsApi($data_json)
    {
        $url = API_SEARCH . '/s/b5c/batchPrice';
        return json_decode(HttpTool::Curl_post_json($url, $data_json), true);
    }

    /**
     * @param $file_name
     *
     * @return mixed
     */
    public static function waterMark($file_name)
    {
        $url = HOST_URL_API . '/file/watermarkPdf.json?name=' . $file_name;
        return json_decode(HttpTool::curlGet($url), true);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function updatePmsGoods($data)
    {
        $url = PMS_HOST . '/product/updateSkuLWH';
        return json_decode(HttpTool::Curl_post_json($url, json_encode($data)), true);
    }

    /**
     * @param $data_json
     *
     * @return mixed|string
     */
    public static function addWarehouseInfo($data_json)
    {
        $url = 'http://erp.gshopper.com/index.php?m=Store&a=warehouseConfigAdd';
        return curl_request($url, $data_json, 'PHPSESSID=d1145fl569tarkr9euqdrf6r16; think_language=zh-CN');
    }


    /**
     * @param $data_arr
     *
     * @return mixed
     */
    public static function deleteB2b2cOrders($data_arr)
    {
        $data_json = json_encode($data_arr, JSON_UNESCAPED_UNICODE);
        $key = md5(substr(strtolower(md5($data_json . $data_arr['requestAt'])), 2));
        $url = HOST_URL_API . '/exec/deleteOrder.json?sha1key=' . $key;
        $res = json_decode(HttpTool::Curl_post_json($url, $data_json, 120, 120), true);
        Logs([$data_json, $url, $res], 'deleteB2b2cOrders', 'Api');
        return $res;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public static function createGroupSku(array $data)
    {
        $data_json = DataModel::arrToJson($data);
        $url = HOST_URL_API . '/combo/create.json';
        $res_json = self::postRequestJson($url, $data_json);
        Logs([$data_json, $url, $res_json], 'createGroupSku', 'Api');
        $reqsponse_data = json_decode($res_json, true);
        return $reqsponse_data;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public static function cancelGroupSku(array $data)
    {
        $data_json = DataModel::arrToJson($data);
        $url = HOST_URL_API . '/combo/cancel.json';
        $res_json = self::postRequestJson($url, $data_json);
        Logs([$data_json, $url, $res_json], 'cancelGroupSku', 'Api');
        $reqsponse_data = json_decode($res_json, true);
        return $reqsponse_data;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public static function adoptGroupOrder(array $data)
    {
        $data_json = DataModel::arrToJson($data);
        $url = HOST_URL_API . '/combo/create/audit.json';
        $res_json = self::postRequestJson($url, $data_json);
        Logs([$data_json, $url, $res_json], 'adoptGroupOrder', 'Api');
        $reqsponse_data = json_decode($res_json, true);
        return $reqsponse_data;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public static function rejectGroupOrder(array $data)
    {
        $data_json = DataModel::arrToJson($data);
        $url = HOST_URL_API . '/combo/cancel/audit.json';
        $res_json = self::postRequestJson($url, $data_json);
        Logs([$data_json, $url, $res_json], 'rejectGroupOrder', 'Api');
        $reqsponse_data = json_decode($res_json, true);
        return $reqsponse_data;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public static function warehouse(array $data)
    {
        $url = HOST_URL_API . '/batch/update_total.json';
        $res = HttpTool::Curl_post_json($url, json_encode($data), 20, 180);
        return $res;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function pullSingle($data)
    {
        $url_param = http_build_query($data);
        $url = GENERAL_B5C . '/op/crawler?' . $url_param;
//        $url = 'http://172.16.8.72:8081'. '/op/crawler?' . $url_param;
        $res = self::getRequest($url, 3, 3);
        ELog::add(['info' => '拉单日志记录', 'url' =>$url, 'res' => $res]);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return $res;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function shipNoticeThirdWarehouse($data)
    {
        $data_json = json_encode($data);
        $url = THIRD_DELIVER_GOODS . '/warehouse/inStorage';
        Logs($url, 'url', 'purchaseShip');
        Logs($data_json, 'noticeThirdWarehouseRequest', 'purchaseShip');
        $res = HttpTool::Curl_post_json($url, $data_json, 20, 120);
        Logs($res, 'noticeThirdWarehouseRes', 'purchaseShip');
        return json_decode($res, true);
    }

    /**
     * @param $user_ids
     * @param $message_string
     *
     * @return mixed
     */
    public static function WorkWxSendMessage($user_ids, $message_string)
    {
        $url = "http://cms.gshopper.com/wechatSendMessage/user_arr/{$user_ids}/send_message/{$message_string}";
        $res = self::getRequest($url, 3, 3);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }
    /**
     * @param $user_ids
     * @param $message_string
     *
     * @return mixed
     */
    public static function WorkWxSendMessagePost($user_ids, $message_string)
    {
        $url = "http://cms.gshopper.com/sendWechatMessage";
        $data['user_arr'] = $user_ids;
        $data['send_message'] = $message_string;

        $res = HttpTool::Curl_post_json($url, json_encode($data));
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }
    /**
     * @param $user_ids
     * @param $message_string
     *
     * @return mixed
     */
    public static function WorkWxMessage($user_ids, $message_string)
    {
        $WechatMsg = new WechatMsg();
        $res = $WechatMsg->sendText($user_ids . "@gshopper.com", L($message_string));
        Logs([$user_ids, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    // 获取企业微信用户（根据部门id）
    public static function WorkWxGetUserNameByDeptId($dept_id)
    {
        $url = CMS_HOST . "/wxuser/dept_ids/{$dept_id}";
        $res = self::getRequest($url, 3, 3);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    /**
     * @param $user_ids
     * @param $message_string
     *
     * @return mixed
     */
    public static function batchLock($data)
    {
        $url = HOST_URL_API . '/batch/lock.json';
        $request = [
            "processCode" => "LOCK_FROM_SALE",
            "processId" => uuid(),
            "data" => $data
        ];
        $request_json = json_encode($request);
        $res = HttpTool::Curl_post_json($url, $request_json, 20, 60);
        Logs([$url, $request, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    public static function sendFaWang($data)
    {
        $url = HOST_URL_API . '/batch/fawang.json';
        $request = $data;
        $res = self::postRequestJson($url, json_encode($request));
        Logs([$url, $request, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    public static function getCardSms($data)
    {
        $url = GP_SMS_URL . '/sms/get_sms_order.php';
        $res = self::postRequestUrlencoded($url, $data);
        Logs([$url, $data, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    public static function sendCardSms($data)
    {
        $url = GP_SMS_URL . '/sms/put_queue.php';
        $res = self::postRequestUrlencoded($url, $data);
        Logs([$url, $data, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }


    /**
     * @name 删除收入报表
     *
     * @param $bill_id
     * @param $stream_id
     *
     * @return mixed
     */
    public static function deletIncomeDataById($bill_id, $stream_id)
    {
        $url = HOST_S_URL . '/check/deletIncomeDataById?billNo=' . $bill_id . '-' . $stream_id;
        $res = HttpTool::curlGet($url);
        return json_decode($res, true);
    }

    //主动刷新订单
    public static function updateOrderFromEs($order_info, $tag, $connecttimeout = 1)
    {
        if (0 == RedisModel::get_key('erp_switch_update_order_from_es')) {
            return [];
        }
        $url = HOST_S_URL . '/b5c/order/saveAllOrder';
        $data['processCode'] = 'PROCESS_ES_ORDER_REFRESH';
        $data['processId'] = uuid();
        $data['data'] = $order_info;
        $res = HttpTool::Curl_post_json($url, json_encode($data), $connecttimeout, $connecttimeout);
        $res = json_decode($res, true);
        Logs([$url, $data, $res], __FUNCTION__ . '-' . $tag, 'fm');
        return $res;
    }

    //从待获取运单号退回待派单
    public static function backToDispatch($data, $tag)
    {
        $url = HOST_URL . '/erp_order/backToDispatch.json';
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([$url, $data, $res], __FUNCTION__ . '-' . $tag, __CLASS__);
        return $res;
    }

    public static function removeBtch($purchase_order_no)
    {
        $url = HOST_URL . '/batch/removeBtch.json';
        $data['processCode'] = 'ONWAY_REMOVE';
        $data['processId'] = uuid();
        $data['data'][] = [
            'operatorId' => DataModel::userId(),
            'purchaseOrderNo' => $purchase_order_no
        ];
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([$url, $data, $res], __FUNCTION__, 'fm');
        return $res;
    }

    public static function crawler($url)
    {
        $res = self::getRequest($url, 2, 2);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return $res;
    }

    /**
     * @param $params
     *
     * @name 查询收入成本聚合数据
     * @return mixed
     */
    public static function getIncomeCostSum($params = [])
    {
        $url = API_SEARCH . '/search/api/getData';
        $data['processCode'] = 'income2_data_handle';
        $data['processId'] = uuid();
        $data['data'] = $params;
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        Logs($data, '$data', 'getIncomeCostapi');
        Logs($url, '$url', 'getIncomeCostapi');
        Logs($res, '$res', 'getIncomeCostapi');
        $res = json_decode($res, true);
        return $res;
    }

    /**
     * @param array $params
     * @param null  $uuid
     *
     * @return mixed
     */
    public static function getIncomeCostList($params = [],$uuid = null)
    {
        if (empty($uuid)) {
            $uuid =  uuid();
        }
        $url = API_SEARCH . '/search/api/getPage';
        $data['processCode'] = 'income2_data_handle';
        $data['processId'] = $uuid;
        $data['data'] = $params;
        $res = HttpTool::Curl_post_json($url, json_encode($data));
        $res = json_decode($res, true);
        Logs([
            'request'=>$data,
            'url'=>$url,
            'response'=>[
                'currentPage'=>$res['currentPage'],
                'totalCount'=>$res['totalCount'],
                'totalPage'=>$res['totalPage']
            ]
        ], 'income cost data', 'getIncomeCostListapi');
        return $res;
    }



    public static function haiguan179Callback($params = [])
    {
        $url = GENERAL_B5C . '/haiguan/platDataOpen ';
        $res = HttpTool::curlForm($url, $params);
        return $res;
    }
    /**
     * @param $params
     *
     * @name 海库回调
     * @return mixed
     */
    public static function haiKuCallback($params = [])
    {
        $url = GENERAL_B5C . '/lgt/HkReceiveTrackingNum';
        $data['request'] = $params;
        $res = HttpTool::curlXml($url, $data);
        return $res;
    }

    public static function mergeRequest()
    {
        $url = ERP_URL . '/index.php?m=Tool&a=mergeRequest&api=b08a8be1abd25efd858141757dbfc5c5';
        return self::getRequest($url, 1, 1);
    }

    public static function mergeSplitResultQuery()
    {
        $url = ERP_URL . '/index.php?m=Tool&a=mergeSplitResultQuery&api=b08a8be1abd25efd858141757dbfc5c5';
        return self::getRequest($url, 1, 1);
    }

    public static function selectBiPO()
    {
        $url = ERP_URL . '/index.php?m=Tool&a=childBiSelectPO&api=b08a8be1abd25efd858141757dbfc5c5';
        return self::getRequest($url, 1, 1);
    }

    public function selectBiSend()
    {
        $url = ERP_URL . '/index.php?m=Tool&a=childBiSelectSend&api=b08a8be1abd25efd858141757dbfc5c5';
        return self::getRequest($url, 1, 1);
    }
    public static function addressValid($param = [])
    {
        /*$param = [
            'city'    => 'Hasbergen',
            'country' => 'DE',
            'houseNo' => '25',
            'street'  => 'Tecklenb3urger Str.',
            'zipcode' => '49205',
        ];
        $date = '2015-06-16 00:19:26';*/
        $date = date('Y-m-d H:i:s');
        $data = [
            'city'      => $param['city'],
            'country'   => $param['country'],
            'houseNo'   => $param['houseNo'],
            'street'    => $param['street'],
            'zipcode'   => $param['zipcode'],
            'timestamp' => $date,
        ];
        list($userSign, $clientSign) = self::getSign($data);
        $config = C('address_valid_config');// 万邑通地址校验配置

        unset($data['timestamp']);
        $request = [
            'action'      => $config['action'],
            'app_key'     => $config['app_key'],
            'client_id'   => $config['client_id'],
            'client_sign' => $clientSign,
            'data'        => $data,
            'format'      => $config['format'],
            'language'    => $config['language'],
            'platform'    => $config['platform'],
            'sign'        => $userSign,
            'sign_method' => $config['sign_method'],
            'timestamp'   => $date,
            'version'     => $config['version'],
        ];
        $url = $config['api_url'];
        $response_json = self::postRequestJson($url, $request);
        return $response_json;
    }

    //获取万邑通用户签名
    public static function getSign($data)
    {
        $config = C('address_valid_config');// 万邑通地址校验配置
        $sign = self::getCommonSign($data);
        //获取万邑通用户签名
        $userSign = strtoupper(md5($config['token'] . $sign . $config['token']));
        $sign = self::getCommonSign($data);
        //获取万邑通应用签名
        $clientSign = strtoupper(md5($config['client_secret'] . $sign . $config['client_secret']));
        return [$userSign, $clientSign];
    }

    //获取万邑通公用签名部分
    public static function getCommonSign($data)
    {
        $date = $data['timestamp'];
        unset($data['timestamp']);
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $config = C('address_valid_config');// 万邑通地址校验配置
        $sign   = 'action' . $config['action'] . 'app_key' . $config['app_key'] . 'data' . $data . 'format' . $config['format'] .
            'platform' . $config['platform'] . 'sign_method' . $config['sign_method'] . 'timestamp' . $date . 'version' . $config['version'];
        return $sign;
    }

    /**
     * 生成pdf文件
     * @param $params
     * @return mixed
     */
    public static function generatePdfFile($params)
    {
        $url = CMS_HOST . '/tcpdf/create';
        $res = HttpTool::Curl_post_json($url, json_encode($params));
        $res = json_decode($res, true);
        return $res;
    }

    /**
     * 获取线上code type
     * @return mixed
     */
    public static function getOnlineCodeTypes()
    {
        $url = 'http://erp.gshopper.com/index.php?m=api&a=getCodeTypes';
        $res = HttpTool::curlGet($url);
        Logs($res, __FUNCTION__, 'fm');
        $res = json_decode($res, true);
        return $res;
    }

    /**
     * @param $params
     *
     * @name 获取运输方式
     * @return mixed
     */
    public static function getShippingType($app_keys)
    {
        $url_str = API_CRAWLER;
        if ('online' == $_ENV["NOW_STATUS"]) {
            $url_str = GENERAL_B5C;
            $params = $app_keys;
        } else {
            $data['clientId'] = 'clientId';
            $data['appSecret'] = 'appSecret';
            $data['refreshToken'] = 'refreshToken';
            $params = json_encode($data);
        }
        $url = $url_str . '/aliExpress/listlogisticsservice';
        $res = HttpTool::Curl_post_json($url, $params);
        Logs($params, '$params', 'getIncomeCostapi');
        Logs($url, '$url', 'getIncomeCostapi');
        Logs($res, '$res', 'getIncomeCostapi');
        $res = json_decode($res, true);
        //解析一次未解析成数组
        if (is_string($res) || !is_array($res)) {
            $res = json_decode($res, true);
        }
        return $res;
    }


    /**
     * 企业微信发送markdown微信消息
     * @param $user_ids
     * @param string $content markdown 格式内容
     * @author Redbo He
     * @date 2020/11/17 17:41
     */
    public static function WorkWxSendMarkdownMessage($user_ids, $content)
    {

        $url = CMS_HOST . '/sendWechatMessage';
        $user_arr = is_array($user_ids) ? implode(',', $user_ids) : $user_ids;
        $data = [
            'msgtype' => 'markdown',
            "user_arr" => $user_arr,
            'content' => $content,
        ];
        $res = self::postRequestJson($url,$data);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        $res = json_decode($res, true);
        return $res;
    }

    /**
     * 企业微信发送 监控警告 消息
     * @param $user_ids
     * @param $message_string
     * @return mixed
     * @author Redbo He
     * @date 2020/12/3 12:08
     */
    public static  function WorkWxSendWarnMessage($user_ids, $message_string)
    {
        $url = CMS_HOST . "/wechatSendMessage/user_arr/{$user_ids}/send_message/{$message_string}/message_group/MonitoringAlarm";
        $res = self::getRequest($url, 3, 3);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }

    public static function WorkWxSendWarnMarkdownMessage($user_ids, $content)
    {
        $url = CMS_HOST . '/sendWechatMessage';
        $user_arr = is_array($user_ids) ? implode(',', $user_ids) : $user_ids;
        $data = [
            'message_group' => 'MonitoringAlarm',
            'msgtype'     => 'markdown',
            "user_arr"    => $user_arr,
            'content'     => $content,
        ];
        $res = self::postRequestJson($url,$data);
        Logs([$url, $res], __FUNCTION__, __CLASS__);
        $res = json_decode($res, true);
        return $res;
    }

    /**
     * 易达回邮单创建
     * @param array $data
     *
     * @return mixed
     */
    public static function addReturnOrder(array $data)
    {
        $url = THIRD_DELIVER_GOODS . '/op/addReturnOrder';
        $data_json = json_encode($data);
        $res = HttpTool::Curl_post_json($url, $data_json, 5, 5);
        Logs([$url, $data_json, $res], 'trackingNO', 'addReturnOrder');
        self::addLogs();
        return $res;
    }

    /**
     * 易达回邮单处理方式
     *
     * @return mixed
     */
    public static function getReturnService()
    {
        $url = THIRD_DELIVER_GOODS . '/op/getReturnService';
        $data_json = json_encode(['order_id' => '111']);
        $res = HttpTool::Curl_post_json($url, $data_json, 5, 5);
        Logs([$url, $data_json, $res], 'trackingNO', 'getReturnService');
        self::addLogs();
        return json_decode($res, true);
    }

    /**
     * Java报文日志创建
     * @param array $data
     *
     * @return mixed
     */
    public static function addMessageLog(array $data)
    {
        $url = MESSAGE_LOG_URL . '/kafka/send';
        $data_json = json_encode($data);
        $res = HttpTool::Curl_post_json($url, $data_json, 5, 5);
        Logs([$url, $data_json, $res], 'kafkaSend', 'changeOrderAfterSaleStatus');
        return $res;
    }
}