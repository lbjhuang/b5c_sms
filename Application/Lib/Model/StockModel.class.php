<?php

/**
 * User: yangsu
 * Date: 17/5/18
 * Time: 10:40
 */
class StockModel extends BaseModel
{
    const GSHOPPER_KR = 'N000831400';
    const B5C_CHANNEL = 'N000830100';
    public static $location = 0;
    public static $inventory_status = [
        '0' =>  '全部',
        '1' =>  '紧缺',
        '2' =>  '正常',
        '3' =>  '充足',
        '4' =>  '滞销',
        '5' =>  '滞销(待)',
    ];

    private $_requestGshopperData;
    public $store_id;
    public $channel;
    public $channel_sku_id;
    public $sku;
    public $isunlock = null;// 0锁定，1解锁
    public $total_lock_num; // 锁或者解锁的总量

    public static function get_show_warehouse()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        $where['is_show'] = 1;
        self::$location == 1 ? $where['location_switch'] = 1 : ''; //货位
        return $Warehouse->where($where)->getField('CD,company_id,warehouse');
    }

    public function getGshopperCode()
    {
        return [
            'N000834100', // CN
            'N000834200', // EN
            'N000834300', // JP
            'N000831400'  // KR
        ];
    }

    public static function get_all_warehouse($cache = false)
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        return $Warehouse->cache($cache)->getField('CD,company_id,warehouse');
    }
//  渠道
    public static function get_all_channel()
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = SALE_CHANNEL;
        return $Cmn_cd->cache(300)->where($where)->getField('CD,CD_VAL,ETc');
    }

    /**
     * 锁库存
     * @param $params 参数数组
     * @return array $return
     */
    public function parseLock($params)
    {
        $url = HOST_URL_API . '/batch/muti_lock.json';
        // 锁库成功之后，如果是Gshopper相关的数据，再去请求OpenApi
        $requestLockData = $this->setLockRequestData($params);
        $responseLockData = json_decode(curl_get_json($url, json_encode($requestLockData)), 1);
        $msg = '';
        if ($responseLockData['code'] == 2000) {
            if (in_array($this->channel, $this->getGshopperCode())) {
                $responseGshopperData = $this->requestGshopper($params);
                if ($responseGshopperData['code'] != 2000) {
                    $return = [
                        'status'    => 'n',
                        'code'      => $responseGshopperData['code'],
                        'msg'       => $responseGshopperData['msg'],
                        'curl_data' => $responseGshopperData['data'],
                        'data'      => $responseGshopperData,
                        'url'       => $url,
                        'post_msg'  => $this->_requestGshopperData
                    ];
                    $msg .= '[GshopperApp 解锁失败---' . $responseGshopperData['msg'] . ']';
                }
            }
            $return = ['status' => 'y', 'msg' => $this->isunlock?'解锁成功' . $msg:'锁定成功' . $msg, 'data' => $params, 'code' => $responseLockData['code'], 'post_data' => $requestLockData];
        } else {
            $return = ['status' => 'n', 'msg' => $responseLockData['msg'], 'data' => $responseLockData['data'],  'code' => $responseLockData['code'], 'curl_data' => $responseLockData['data'], 'url' => $url];
        }

        $request ['requestGshopperData'] = $this->_requestGshopperData;
        $request ['requestLockData']     = $requestLockData;
        $response ['responseGshopperData'] = $responseGshopperData;
        $response ['lockResponseData']   = $responseLockData;
        $this->setRequestData($request);
        $this->setResponseData($response);
        $this->_catchMe();
        return $return;
    }

    /**
     * 取得Gshopper-KR可售库存
     * @param $sku_id  SKU_ID
     * @return int $solds;
     */
    public function getSolds($sku_id)
    {
        $standing = M('_wms_standing',  'tb_');
        if (!is_array($sku_id)) {
            $fields = 'total_inventory';
            $conditions = [
                'channel' => [
                    'eq',
                    SELF::B5C_CHANNEL,
                ],
                'SKU_ID' => [
                    'eq',
                    $sku_id,
                ]
            ];
        } else {
            $fields = 'total_inventory, SKU_ID';
            $conditions = [
                'channel' => [
                    'eq',
                    SELF::B5C_CHANNEL,
                ],
                'SKU_ID' => [
                    'in',
                    $sku_id,
                ]
            ];
        }

        $solds = $standing->where($conditions)->getField($fields);//可售
        return $solds;
    }

    /**
     * 获取商品属性
     * Gshopper-KR相关
     * @param $channel_sku_id 渠道skuid
     * @return 商品属性信息 $opts
     */
    public function getDrgudsOpt($channel_sku_id)
    {
        $drguds = M('drguds_opt', 'tb_ms_');
        if (is_array($channel_sku_id)) {
            $where ['tb_ms_drguds_opt.SKU_ID'] = ['in', $channel_sku_id];
        } else {
            $where ['tb_ms_drguds_opt.SKU_ID'] = $channel_sku_id;
        }
        $fields = 'tb_ms_drguds_opt.GUDS_ID,tb_ms_drguds_opt.GUDS_OPT_ID,tb_ms_drguds_opt.SKU_ID,tb_ms_drguds_opt.THRD_SKU_ID,tb_ms_guds_store.THRD_GUDS_ID';
        $opts = $drguds->where($where)
            ->join('left join tb_ms_guds_store on tb_ms_guds_store.ID = tb_ms_drguds_opt.GUDS_ID')
            ->getField($fields);

        return $opts;
    }

    /**
     * 设置请求的参数
     * @params $params 锁库数据
     * @return $responseGshopperData 接口返回数据
     */
    public function requestGshopper($params)
    {
        $opts  = $this->getDrgudsOpt($this->channel_sku_id);
        // 并组成以channel_sku_id为key、商品信息为value的数组
        if ($opts) {
            foreach ($opts as $key => $value) {
                $tmp [$value['SKU_ID']] = $value;
            }
        }
        // 获取锁定数量
        $sum = 0;
        foreach ($params as $k => $v) {
            $sum += $v->locks;
        }
        $opts = $tmp;
        $requestData ["platCode"] = $this->channel;
        $requestData ["processId"] = create_guid();
        $requestData ["data"]["stocks"] = [];
        // 组装发出请求的数据
        $r = [
            "gudsId"          => $opts[$this->channel_sku_id]['GUDS_ID'],
            "thrdGudsId"      => $opts[$this->channel_sku_id]['THRD_GUDS_ID'],
            "skuId"           => $opts[$this->channel_sku_id]['SKU_ID'],
            "thrdSkuId"       => $opts[$this->channel_sku_id]['THRD_SKU_ID'],
            "stockCount"      => $sum,
            "totalStockCount" => $this->getSolds($this->sku),
            "status"          => $this->isunlock
        ];
        $requestData ["data"]["stocks"] [] = $r;
        $this->_requestGshopperData = $requestData;
        $sys_log['get_msg']  = $requestData;
        $sys_log['get_url']  = $url = GSHOPPER . '/product/allotProductStock.json';
        $sys_log['get_data'] = $responseData = curl_get_json($url, json_encode($requestData));
        $sys_log['get_asyn'] = $responseData = json_decode($responseData, 1);
        $sys_log['time']     = date("Y-m-d H:i:s");

        return $responseData;
    }

    /**
     * 设置请求的参数
     * @params $locks 锁定的数据
     * @return $requestLockData 请求的数据 isunlock 0是入库 1是出库
     */
    public function setLockRequestData($locks)
    {
        $sum = 0;
        $pre = $this->isunlock?'-':'+';
        $requestData ['data']['lock'] = [];
        foreach ($locks as $key => $value) {
            if ($this->isunlock) { // 出库
                $tmp = [
                    "channel"      => $value->channel,
                    "channelSkuId" => $value->channel_sku_id,
                    "storeId"      => $value->store_id,
                    "batchId"      => (int)$value->batch_id,
                    "num"          => (int)($pre . $value->locks),
                    "operatorId"   => (int)BaseModel::getName(),
                    'saleTeamCode' => $value->sale_team_code,
                    'childId'      => $value->id
                ];
            } else { // 入库
                $tmp = [
                    "channel"      => $this->channel,
                    "channelSkuId" => $this->channel_sku_id,
                    "storeId"      => $this->store_id,
                    "batchId"      => $this->isunlock?(int)$value->batch_id:(int)$value->id,
                    "num"          => (int)($pre . $value->locks),
                    "operatorId"   => (int)BaseModel::getName(),
                    'saleTeamCode' => $value->sale_team_code
                ];

            }
            $sum += $value->locks;
            $requestData ['data']['lock'][] = $tmp;
        }
        $this->total_lock_num = $sum;
        return $requestData;
    }

    /**
     * 根据条件获取可售库存
     * @param $where
     * @return array
     */
    public static function getAvailableForSaleStock($where)
    {
        if (empty($where)) return [];
        $where['t11.type']           = 1;
        $where['t1.vir_type']        = ['not in', ['N002440200','N002440400','N002410200']];//在途库存/残次品库存/调拨在途
        $where['t1.total_inventory'] = ['gt', 0];
        $fields = [
            't1.SKU_ID AS skuId',
            't1.sale_team_code AS saleTeamCode',
            't11.warehouse_id AS deliveryWarehouse',
            't1.available_for_sale_num AS availableForSale',//可售
            't11.ascription_store AS ascriptionStore',
            't11.CON_COMPANY_CD',//库存归属公司
            't1.small_sale_team_code as sellSmallTeamCd',   //  批次所属销售小团队
            'datediff(NOW(), t2.pur_storage_date) AS unsalable' // 滞销天数
        ];
        $res =  (new Model())->table(B5C_DATABASE . '.tb_wms_batch t1')
            ->field($fields)
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_stream t2 ON t1.stream_id = t2.id')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_pur_order_detail t3 ON t1.purchase_order_no = t3.procurement_number')
//            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = t1.SKU_ID')
//            ->join('LEFT JOIN (
//                SELECT tab1.spu_id, tab1.charge_unit, tab1.cat_level1
//                FROM '
//                .PMS_DATABASE.'.product tab1 GROUP BY tab1.spu_id ) t4 ON t4.spu_id = t9.spu_id')
            ->join('LEFT JOIN ' . B5C_DATABASE . '.tb_wms_bill t11 ON t1.bill_id = t11.id')
            ->join('LEFT JOIN (SELECT tab2.batch_id, sum(tab2.occupied) as childOccupied, sum(tab2.available_for_sale_num) AS childLocking, tab2.SKU_ID FROM tb_wms_batch_child tab2 GROUP BY tab2.batch_id, tab2.SKU_ID ) t12 ON t1.id = t12.batch_id AND t1.SKU_ID = t12.SKU_ID')
            ->where($where)
            ->select();
//        var_dump(M()->_sql());die;
        return $res;
    }
}