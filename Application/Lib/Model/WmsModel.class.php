<?php

/* * 
 * 公共模型
 */

class WmsModel extends Model
{

    const ORDER_EXPORT = 'ORDER_EXPORT';//b2c单出库
    const EXCEL_EXPORT = 'EXCEL_EXPORT';//excel出库
    const B2B_EXPORT = 'B2B_EXPORT';//b2b出库
    const B2B_RETURN_WAREHOUSING = 'REIMPORT_FROM_OUT_STORAGE';//b2b出库
//    const HOST_API = HOST_URL_API;
    const HOST_API = HOST_URL_API;
    private $type;

    protected $autoCheckFields = false;

    private function saveLog(array $array, $type)
    {
        $filePath = '/opt/logs/logstash/erp/';
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        chmod($filePath, 0777);
        $fileName = 'erp_wms_' . date('Ymd') . '.log';
        $logContent = '------------Log start(' . $type . ')------------' . date('Y-m-d H:i:s') . PHP_EOL;
        $logContent .= json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $logContent .= PHP_EOL . '------------------Log end------------------' . PHP_EOL . PHP_EOL;
        file_put_contents($filePath . $fileName, $logContent, FILE_APPEND);
    }

    /**oms订单出库，发货
     *
     * @param $type string processCode (ORDER_EXPORT)
     * @param $data array
     *
     * @return mixed
     */
    public function b2cOutStorage($data)
    {
        $this->type = "b2cOutStorage";
        $url = self::HOST_API . '/batch/export2.json';
        $req = [
            'processCode' => self::ORDER_EXPORT,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req, $cookie = null, $timeout = 480);
        return $res;
    }

    /**
     * B2B 理货残次品入库
     *
     * @param $data
     *
     * @return mixed
     */
    public function b2bReturnWarehousing($data)
    {
        $this->type = __FUNCTION__;
        $url = self::HOST_API . '/batch/reimport.json';
        $req = [
            'processCode' => self::B2B_RETURN_WAREHOUSING,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        return $res;
    }

    public function getResponse($url, $req, $cookie = null, $timeout = 60)
    {
        $t = microtime();
        $res = json_decode(curl_get_json($url, json_encode($req), $cookie, $timeout), true);
        $this->saveLog(['post' => $_REQUEST, 'url' => $url, 'req' => $req, 'res' => $res, 't' => microtime() - $t], $this->type);
        return $res;
    }

    public function invOutStorage($data)
    {
        $this->type = "invOutStorage";
        $url = self::HOST_API . '/batch/export2.json';
        $req = [
            'processCode' => self::EXCEL_EXPORT,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        ELog::add(['info' => '盘亏出库', 'request' => $req, 'response' => $res]);
        return $res;
    }

    public function invInStorage($data)
    {
        $this->type = "invInStorage";
        $url = self::HOST_API . '/batch/update_total.json';
        $req = $data;
        $res = $this->getResponse($url, $req);
        ELog::add(['info' => '盘盈入库', 'request' => $req, 'response' => $res]);
        return $res;
    }

    public function xlsOutStorage($data)
    {
        $this->type = "xlsOutStorage";
        $url = self::HOST_API . '/batch/export2.json';
        $req = [
            'processCode' => self::EXCEL_EXPORT,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        ELog::add(['info' => 'Excel导出', 'request' => $req, 'response' => $res]);
        return $res;
    }

    public function xlsInStorage($data)
    {
        $this->type = "xlsInStorage";
        $url = self::HOST_API . '/batch/update_total.json';
        $req = $data;
        $res = $this->getResponse($url, $req);
        ELog::add(['info' => 'Excel导入', 'request' => $req, 'response' => $res]);
        return $res;
    }

    public function purInStorage($data)
    {
        $this->type = "purInStorage";
        $url = self::HOST_API . '/batch/update_total.json';
        $req = $data;
        $res = $this->getResponse($url, $req);
        return $res;
    }

    public function b2bOutStorage($data)
    {
        $this->type = "b2bOutStorage";
        $url = self::HOST_API . '/batch/export2.json';
        $req = [
            'processCode' => self::B2B_EXPORT,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        return $res;
    }

    /**
     * 商品正次转换完结调用
     *
     * @param $data
     *
     * @return mixed
     */
    public function productTransferOver($data)
    {
        $this->type = "BATCH_CONVERT";
        $url = self::HOST_API . '/batch/convert.json';
        $req = [
            'processCode' => $this->type,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        return $res;
    }

    /**
     * 商品正次转换完结撤回，驳回
     *
     * @param $data
     *
     * @return mixed
     */
    public function productTransferAbandon($data)
    {
        $this->type = "RELEASE_ORDER";
        $url = self::HOST_API . '/erp_order/operate.json';
        $req = [
            'processCode' => $this->type,
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        return $res;
    }

    public function transferOutLibraryNew($data)
    {
        $url = self::HOST_API . '/batch/newAllocate_export.json';
        $req = [
            'data' => ['export' => $data]
        ];
        $res = $this->getResponse($url, $req, null, 60);
        return $res;

    }

    public function transferInLibraryNew($data)
    {
        $url = self::HOST_API . '/batch/newAllocate.json';
        $req = [
            'data' => [
                'allocate' => [
                    [
                        'lockCode' => substr(guid(), 0, 32),
                        'type' => 1,
                        'data' => $data,
                    ]
                ]
            ]
        ];
        $res = $this->getResponse($url, $req, null, 60);
        return $res;
    }

    //标记入库完结在途库存批次清空
    public function transferEndLibraryNew($purchaseOrderNo)
    {
        $url = self::HOST_API . '/batch/allocateEnd.json';
        $req = [
            'purchaseOrderNo' => $purchaseOrderNo
        ];
        $res = $this->getResponse($url, $req, null, 60);
        return $res;
    }

    //批次记录删除及残次品入库数量减少
    public function b2bBatchDelete($warehouse_list_id)
    {
        $batch_info = M('batch', 'tb_wms_')->where(['warehouseList_id' => $warehouse_list_id])->select();
        if (empty($batch_info)) {
            throw new Exception(L('未找到入库批次记录'));
        }
        $batch_ids = array_column($batch_info, 'id');
        $bill_ids = array_column($batch_info, 'bill_id');
        $stream_ids = array_column($batch_info, 'stream_id');
        $batch_res = M('batch', 'tb_wms_')->where(['id' => ['in', $batch_ids]])->delete();
        if (!$batch_res) {
            throw new Exception(L('删除批次失败'));
        }
        $bill_info = M('bill', 'tb_wms_')->where(['id' => ['in', $bill_ids]])->select();
        $bill_res = M('bill', 'tb_wms_')->where(['id' => ['in', $bill_ids]])->delete();
        if (!$bill_res) {
            throw new Exception(L('删除单据失败'));
        }
        $stream_info = M('stream', 'tb_wms_')->where(['id' => ['in', $stream_ids]])->select();
        $stream_res = M('stream', 'tb_wms_')->where(['id' => ['in', $stream_ids]])->delete();
        if (!$stream_res) {
            throw new Exception(L('删除流水数据失败'));
        }
        $stream_cost_info = M('stream_cost_log', 'tb_wms_')->where(['stream_id' => ['in', $stream_ids]])->select();
        if (!empty($stream_cost_info)) {
            $stream_cost_res = M('stream_cost_log', 'tb_wms_')->where(['stream_id' => ['in', $stream_ids]])->delete();
            if (!$stream_cost_res) {
                throw new Exception(L('删除出入库物流成本数据失败'));
            }
            Logs($stream_cost_info, __FUNCTION__ . 'stream cost record:', __CLASS__);
        }
        Logs($batch_info, __FUNCTION__ . 'batch record:', __CLASS__);
        Logs($bill_info, __FUNCTION__ . 'bill record:', __CLASS__);
        Logs($stream_info, __FUNCTION__ . 'stream record:', __CLASS__);
    }


    /**
     *  占用出库(关联交易批次)
     * @param $type string processCode (ORDER_EXPORT)
     * @param $data array
     *
     * @return mixed
     */
    public function disposeRelTransOrder($data)
    {
        $this->type = "b2cOutStorage";
        $url = self::HOST_API . '/batch/creatRelated.json';
        $req = [
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req, null, 120);
        return $res;
    }

    /**
     *  撤销 占用出库(关联交易批次)
     * @param $type string processCode (ORDER_EXPORT)
     * @param $data array
     *
     * @return mixed
     */
    public function delRelTransOrder($data)
    {
        $this->type = "b2cOutStorage";
        $url = self::HOST_API . '/batch/removeRelated.json';
        $req = [
            'processId' => guid(),
            'data' => $data
        ];
        $res = $this->getResponse($url, $req);
        return $res;
    }
}

