<?php

/**
 * 订单入库模型
 *
 */
class TbWmsBillExtendModel extends BaseModel
{
    const WAREHOUSE_OUT = 'N000950600'; // 调拨出库
    const WAREHOUSE_IN  = 'N000940500'; // 调拨入库
    const IN_OUR_STORAGE = 1;//入库规则，真实入库
    const NO_IN_STORAGE  = 0;//虚拟入库，入库即出库，虚拟仓N000680800

    public $bill;//订单信息
    public $guds;//商品信息
    public $saleTeams = [];
    public $type = false;
    protected $trueTableName = 'tb_wms_bill';
    private $_msgCode;//消息编码
    private $_errorInfo;//错误信息
    private $_lastBillId;//最后一次插入的订单id
    private $_requestData;//请求接口的数据
    private $_depr = '+';//类型标识符号，+为入库，-为出库
    private $_lrSuccessCode = 2000;//接口返回成功的编码，固定不变
    private $_inCode;//入库类型的code值
    private $_outCode;//出库类型的code值
    private $_billId;//出入库单号bill_id系统生成
    private $_flag = false;//是否虚拟入库
    private $_allo_data = [];//调拨数据,保存出库的数据用于入库
    private $_lastBillIds;
    public $times;

    public function __construct()
    {
        parent::__construct();
        //初始化出入库code吗
        $this->outAndInCode();
    }

    public $bill_attributes = [
        ['bill_id', 'require' => 'in', 'message' => '出入库单号'],
        ['bill_type', 'require' => 'both', 'message' => '收发类别'],
        ['link_bill_id', 'require' => 'none', 'message' => 'B5C单号'],
        ['warehouse_rule', 'require' => 'in', 'message' => '入库规则'],// 数据库无对应字段
        ['batch', 'require' => 'in', 'message' => '入库批次号'],
        ['sale_no', 'require' => 'none', 'message' => '对应销售单号'],// 数据库无对应字段
        ['channel', 'require' => 'both', 'message' => '渠道'], // 目前数据库保存的是值，不是对应的编码，这里需要商定！！！
        ['supplier', 'require' => 'in', 'message' => '供应商名称'],
        ['purchase_logistics_cost', 'require' => 'none', 'message' => '采购端物流费用'],// 数据库无对应字段
        ['warehouse_id', 'require' => 'none', 'message' => '仓库ID'],
        ['total_cost', 'require' => 'in', 'message' => '入库总成本'],// 数据库无对应字段
        ['bill_state', 'require' => 'none', 'message' => '单据状态'],
        ['SALE_TEAM', 'require' => 'none', 'message' => '销售团队'],
        ['SP_TEAM_CD', 'require' => 'none', 'message' => '采购团队'],
        ['CON_COMPANY_CD', 'require' => 'in', 'message' => '我方公司（公司code）'],
        ['procurement_number', 'require' => 'none', 'message' => '采购单号'],
        ['third_order_id', 'require' => 'none', 'message' => '第三方订单id'],
    ];

    public $guds_attributes = [
        ['GSKU', 'require' => 'both', 'message' => 'GSKU(SKU)'],
        ['taxes', 'require' => 'both', 'message' => '税率'],
        ['should_num', 'require' => 'none', 'message' => '应发数量'],
        ['send_num', 'require' => 'both', 'message' => '实发数量'],
        ['deadline_date_for_use', 'require' => 'none', 'message' => '生产日期(物品校期有效型必填)'],
        ['price', 'require' => 'both', 'message' => '单价'],
        ['currency_id', 'require' => 'both', 'message' => '币种'],// 数据库无对应字段
        ['currency_time', 'require' => 'both', 'message' => '交易发生时间，对应币种的汇率时间'],// 数据库无对应字段
        ['batch', 'require' => 'none', 'nessage' => '批次'],
    ];

    /**
     * 错误码
     *
     */
    public function msgCode()
    {
        return [
            '10000000' => '入库成功',
            '10000001' => '订单或商品数据不能为空',
            '10000010' => '参数缺失',
            '10000101' => '订单写入失败',
            '10000110' => '接口数据写入失败',
            '10000111' => '出库成功',
            '10001011' => '虚拟入库成功(同时出库)',
            '10001111' => '虚拟出库失败',
            '10011111' => '虚拟入库失败',
            '11111111' => '出入库类型参数错误，未知类型',
            '10001110' => '调拨失败',
            '40050000' => '发生错误',
            '40050001' => '出库单生成失败，数据回滚',
            '40050002' => '出库单子数据生成失败，数据回滚',
            '40050003' => '入库单生成失败，数据回滚',
            '40050004' => '入库单子数据生成失败，数据回滚',
            '11110000' => '程序异常',
            '11110111' => '调拨成功',
            '11111011' => '更新远程批次库失败',
            '40050005' => '更新调拨表失败',
        ];
    }

    /**
     * @return float
     *
     */
    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 生成唯一验证 hash
     *
     */
    public function GenerateHash($mode = 'md5', $str)
    {
        return hash($mode, $str);
    }

    /**
     * 出入库操作
     * 出库与入库操作类似，参数也类似，针对出库有不同的操作，需要扩展函数来包装它
     * @param $data 用户提交的数据，包含订单信息，与商品信息，是一个多维数组
     * @return Array 根据上面函数返回的状态判断操作是否成功
     */
    public function outAndInStorage($data = [])
    {
        try {
            if (!count($data ['data'], 1)) {
                //$this->_msgCode = 10000001;
                throw new InvalidArgumentException(L('无有效参数'));
            }
            // 判断是入库还是出库
            $this->checkOutOrInType($data ['bill_type']);
            foreach ($data ['data'] as $key => $value) {
                if (count($value ['bill']) == 0 or count($value ['guds']) == 0) continue;
                $hash = '';
                $hash .= $key;
                foreach ($value ['bill'] as $k => $v) {
                    $hash .= $k.$v;
                }
                $hash = $this->GenerateHash('md5', $hash);
                $this->bill [$hash] = $value ['bill'];
                $this->guds [$hash] = $value ['guds'];
            }
            // B2BC type 检查
            $this->checkWarehouseRule($data);
            $this->OpearationToDo();
            file_put_contents('/opt/logs/logstash/' . 'logstash_' . date('Ymd') . '_erp_json.log', $this->times, FILE_APPEND);
            if ($this->_depr == '+') $return = ['code' => 10000000, 'msg' => L('入库成功'), 'info' => 'success'];
            else $return = ['code' => 10000111, 'msg' => L('出库成功'), 'info' => 'success'];
        } catch (\Exception $e) {
            $return = ['code' => 40050000, 'msg' => L('发生错误'), 'info' => $e->getMessage()];
        }
        $this->_catchMe();
        return $return;
    }

    /**
     * 出入库单生成
     * 生成bill_id
     * @params $type 出入库类型 N000940500 调拨入库 N000950600 调拨出库
     */
    public function orderIOGenerate($params, $type = 'N000940500')
    {
        $sale_team = $params ['sale_team_code'];
        $warehouse = $params ['warehouse'];
        $stock = A('Home/Stock');
        // 出库单生成
        $this->bill ['bill_id'] = $stock->get_bill_id($type, '', $this->parseSaleTeam($sale_team));
        $this->bill ['bill_type'] = $type;
        $this->bill ['warehouse_rule'] = 2;
        $this->bill ['warehouse_id'] = $warehouse;
        $this->bill ['zd_user'] = $_SESSION['userId'];
        $this->bill ['zd_date'] = date('Y-m-d H:i:s', time());
        $this->bill ['bill_date'] = date('Y-m-d H:i:s', time());
        if ($id = $this->parseBill()) {
            return $this->_lastBillId = $id;
        }

        return false;
    }

    /**
     * 出入库单子数据生成
     * @params $stream_data 接口返回的批次数据，包含stream信息与bill信息
     */
    public function orderIOChildGenerate($stream_data)
    {
        $parent_batch_data = [];
        $stream_ids = [];
        foreach ($stream_data[0]['data'] as $k => $v) {
            $tmp = [];
            $tmp ['batchId'] = $v ['id'];
            $tmp ['sendNum'] = $v ['availableForSaleNum'];
            $tmp ['parentStreamId'] = $v ['parentBatch']['streamId'];
            $stream_ids [] = $v ['parentBatch']['streamId'];
            $parent_batch_data [$v ['parentBatch']['streamId']] = $tmp;
        }
        // 获取所有stream_ids相关的基础信息
        $stream = M('_wms_stream', 'tb_');
        $where ['id'] = ['in', $stream_ids];
        $streams = $stream->where($where)->select();
        if (!$streams) return false;
        // 更新stream的基础数据，并删除id值
        // 写入stream表，生成stream_id
        foreach ($streams as $k => &$value) {
            $tmpGuds = [];
            $history_id = null;
            $history_id = $value ['id'];
            $tmpGuds ['send_num'] = $value ['send_num'] = $parent_batch_data [$value ['id']]['sendNum'];
            $tmpGuds ['bill_id'] = $value ['bill_id'] = $this->_lastBillId;
            $tmpGuds ['no_unit_price'] = $value ['no_unit_price'] = bcsub($value ['unit_price'], bcmul($value ['unit_price'], $value ['taxes'], 2), 2);//不含税单价
            $tmpGuds ['unit_money'] = $value ['unit_money'] = bcmul($value ['unit_price'], $value ['send_num'], 2);//含税金额
            $tmpGuds ['no_unit_money'] = $value ['no_unit_money'] = bcmul($value ['no_unit_price'],  $value ['send_num'], 2);//去税金额
            $tmpGuds ['duty'] = $value ['duty'] = bcsub($value ['unit_money'], $value ['no_unit_money'], 2);//税额
            $tmpGuds ['add_time'] = $value ['add_time'] = Date('Y-m-d H:i:s', time());//添加时间
            unset($value ['id']);
            $new_stream_id = $stream->add($value);
            $this->guds [] = $tmpGuds;
            $parent_batch_data [$history_id]['new_bill_id'] = $this->_lastBillId;
            $parent_batch_data [$history_id]['new_stream_id'] = $new_stream_id;
        }
        // streams数据
        if (empty($parent_batch_data)) return false;
        else return $parent_batch_data;
    }

    /**
     * 更新批次表
     * @params 处理后的批次数据，包含批次id与新生成的bill_id，stream_id
     */
    public function updateBatchData($new_batchs)
    {
        $batch = M('_wms_batch', 'tb_');
        $flag = true;
        foreach ($new_batchs as $k => $v) {
            $data ['bill_id'] = $v ['new_bill_id'];
            $data ['stream_id'] = $v ['new_stream_id'];
            if ($isok = $batch->data($data)->where('id = ' . $v ['batchId'])->save()) {
                $flag = true;
            } else {
                false;
            }
        }

        return $flag;
    }

    /**
     * 更新调拨表，将生成的bill_id写入调拨表中
     * @param string $guid
     * @param string $type 出入库类型
     * @return boolean 是否更新成功
     */
    public function updateAllo($guid, $type)
    {
        $model = M('_wms_allocation', 'tb_');
        $ret = $model->where('guid_map = "' . $guid . '"')->find();
        $data = null;
        $type == SELF::WAREHOUSE_IN?$data ['in_storage_id'] = $this->_lastBillId:$data ['out_storage_id'] = $this->_lastBillId;
        if ($model->data($data)->where('guid_map = "' . $ret ['guid_map'] . '"')->save()) {
            return ['state' => 1, 'error' => null];
        }
        return ['state' => 0, 'error' => $model->getError()];
    }

    /**
     * 包装入库数据
     * 因为接口那边在入库的时候，已经对批次进行划分与库存扣减了，所以只需要调拨入库，不需要调拨出库。数据只需要组装一次
     *
     */
    public function warehouseInData($params)
    {
        $header ['data']['batch'] = [];
        $rdata ['billId'] = null;// 调拨的时候为空
        $rdata ['lockCode'] = $params ['guid'];
        $rdata ['type'] = 1; // 1 调拨出入库
        $rdata ['data'] = [];// 数据结构体
        $batchs = $params ['batchs'];            // 页面整体提交的数据
        $sale_team = $params ['sale_team_code']; // 新销售团队将替换掉原销售团队
        $warehouse = $params ['warehouse'];
        if (!$batchs) return false;
        foreach ($batchs as $key => $value) {
            if ($value ['launch_num'] == 0) continue;
            $data = null;
            $data ['gudsId']   = substr($value ['SKU_ID'], 0, -2);
            $data ['skuId']    = trim($value ['SKU_ID']);
            $data ['num'] = (int)($value ['launch_num']);
            $data ['deadlineDateForUse'] = null;
            $data ['operatorId'] = $value ['userId']?$value ['userId']:$_SESSION['userId'];
            $data ['purchaseOrderNo'] = null;
            $data ['newSaleTeamCode'] = $sale_team;
            $data ['oldSaleTeamCode'] = $value ['sale_team_code'];
            $data ['purchaseTeamCode'] = null;
            $data ['channel'] = null;
            $data ['channelSkuId'] = null;
            $data ['streamId'] = null;
            $data ['deliveryWarehouse'] = $warehouse;
            $rdata ['data'][] = $data;
        }
        $header ['data']['batch'][] = $rdata;
        $this->_setRequestData($header);
    }

    /**
     * 销售团队简写处理
     * @params $saleTeamCode 销售团队编码
     */
    public function parseSaleTeam($saleTeamCode)
    {
        return explode('-', $this->saleTeams [$saleTeamCode])[0];
    }

    public function RecordAction($msg)
    {
        //$this->times = date('Y-m-d H:i:s') . '-' . $msg . '-' . $this->microtime_float() . '\r\n';
    }

    /**
     * 出入库操作
     *
     */
    public function OpearationToDo()
    {
        $this->startTrans();
        $stock = A('Home/Stock');

        foreach ($this->bill as $key => &$value) {
            $value ['bill_id'] = $stock->get_bill_id($this->bill ['bill_type'], $this->bill ['bill_date']);
            $value ['zd_user'] = session('m_loginname');
            $value ['zd_date'] = date('Y-m-d H:i:s', time());
            $value ['bill_date'] = date('Y-m-d');
        }
        $this->RecordAction('入库单数据开始写入');
        if (!$this->WriteBill()) {
            $this->rollback();
            throw new \Exception(L('出入库单数据写入失败'));
            return false;
        }
        $this->RecordAction('入库单数据写入完成');
        $this->RecordAction('出入库单关联数据写入');
        if (!$this->WriteGuds()) {
            $this->rollback();
            throw new \Exception(L('出入库单关联数据写入失败'));
            return false;
        }
        $this->RecordAction('出入库单关联数据写入完成');
        $this->RecordAction('接口调用');
        $ret = $this->multParse();
        if ($ret['code'] == $this->_lrSuccessCode) {
            if ($ret ['data']['export']) {
                foreach ($ret ['data']['export'] as $key => $value) {
                    $saveData [$value ['data']['billId']] [] = $value ['data']['exportDetail'];
                }
                if ($saveData) {
                    foreach ($saveData as $key => $value) {
                        $data = [];
                        $data ['batch_ids'] = json_encode($value);
                        $this->where('id = ' . $key)->save($data);
                    }
                }
            }
        } else {
            $this->rollback();
            throw new \Exception(L('Response: ' . $ret ['msg']));
            return false;
        }
        $this->RecordAction('接口调用完成');
        $this->commit();
        return true;
    }

    /**
     * bill 表写入
     *
     */
    public function WriteBill()
    {
        foreach ($this->bill as $key => &$value) {
            $third_order_id = $value ['third_order_id'];
            unset($value['third_order_id']);
            $lastId = $this->add($value);
            if (!$lastId) {
                return false;
            }
            $this->_lastBillIds [$key] = $lastId;
            $value ['third_order_id'] = $third_order_id;
        }
        return true;
    }

    /**
     * guds 表写入
     *
     */
    public function WriteGuds()
    {
        $header ['data']['export'] = [];
        $stream = M('_wms_stream', 'tb_');
        $flag = true;
        $guds = [];
        $requestData = [];
        foreach ($this->guds as $key => &$value) {
            foreach ($value as $k => &$v) {
                $v ['bill_id'] = $this->_lastBillIds [$key];
                $v ['unit_price'] = $v ['price'];//含税单价
                $v ['no_unit_price'] = bcsub($v ['price'], bcmul($v ['price'], $v ['taxes'], 2), 2);//不含税单价
                $v ['unit_money'] = bcmul($v ['unit_price'], $v ['send_num'], 2);//含税金额
                $v ['no_unit_money'] = bcmul($v ['no_unit_price'],  $v ['send_num'], 2);//去税金额
                $v ['duty'] = bcsub($v ['unit_money'], $v ['no_unit_money'], 2);//税额
                $v ['add_time'] = Date('Y-m-d H:i:s', time());//添加时间
                $guds [] = $v;
                unset($v);
            }
            unset($value);
        }
        if ($stream->addAll($guds)) {
            foreach ($this->guds as $key => $g) {
                foreach ($g as $k => $value) {
                    $data = null;
                    $data ['skuId'] = trim($value ['GSKU']);
                    $data ['gudsId'] = substr($value ['GSKU'], 0, -2);
                    $data ['orderId'] = $this->bill [$key]['link_bill_id'];
                    $data ['saleTeamCode'] = $this->bill [$key]['SALE_TEAM'];
                    $data ['operatorId'] = (int)$_SESSION['userId'];
                    $data ['num'] = (int)($value ['send_num']);
                    $data ['type'] = (int)$this->type;
                    $data ['channel'] = $this->bill [$key]['channel'];
                    $data ['billId'] = $this->_lastBillIds [$key];
                    $data ['deliveryWarehouse'] = $this->bill [$key]['warehouse_id'];
                    if ($this->type == 0) $data ['thirdOrderId'] = $this->bill [$key]['third_order_id'];
                    $requestData [] = $data;
                }
            }
            $header ['data']['export'] = $requestData;
            $this->_requestData = $header;
            $this->setRequestData($header);
            return true;
        }
        return false;
    }

    /**
     * 入库和出库的判断依据
     * 将出库与入库存为两个数组
     */
    public function outAndInCode()
    {
        $model = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] =  '入库类型';
        $this->_inCode = array_column($model->field('CD, CD_VAL')->where($where)->select(), 'CD_VAL', 'CD');
        $where['CD_NM'] = '出库类型';
        $this->_outCode = array_column($model->field('CD, CD_VAL')->where($where)->select(), 'CD_VAL', 'CD');
        $this->saleTeams = array_column($model->where('CD like "N001290%"')->select(), 'CD_VAL', 'CD');
    }

    /**
     * 订单处理
     * @return 成功返回插入数据id，失败返回false
     */
    public function parseBill()
    {
        $third_order_id = $this->bill['third_order_id'];
        unset($this->bill['third_order_id']);
        if ($this->add($this->bill)) {
            $this->bill ['third_order_id'] = $third_order_id;
            return $this->_lastBillId = $this->getLastInsID();
        }
        return false;
    }

    /**
     * @param $data
     *
     */
    public function checkWarehouseRule($data)
    {
        if (isset($data ['type'])) {
            $this->type = $data ['type'];
        }
    }

    /**
     * 数据出入库类型判断
     * 根据用户传递的bill_type参数，调整$this->_depr为正还是负，如果用户传递的参数在已知参数中不存在则抛出异常
     */
    public function checkOutOrInType($bill_type)
    {
        if (isset($this->_inCode[$bill_type])) {
            $this->_depr = '+';
        } elseif (isset($this->_outCode[$bill_type])) {
            $this->_depr = '-';
        } else {
            $this->_msgCode = 11111111;
            return false;
        }
        return true;
    }

    /**
     * 检查用户传递的仓库是否真实存在，并且启用
     *
     */
    public function checkWarehouse($warehouse_id)
    {
        $ret = BaseModel::getCd('N000680');
        if ($ret [$warehouse_id]) return true;
        return false;
    }

    /**
     * 虚拟入库时，同时出库需要匹配出相对应的出入类型
     * @param $bill_type 入库类型
     * @param $bill_type 出库类型
     * @return $code CD值
     */
    public function matchStorageType($bill_type)
    {
        $cd_val = $this->_inCode [$bill_type];
        $ncd_val = mb_substr($cd_val, 0, -2, 'utf-8') . '出库';
        $code = 'N000950100'; //默认为其他出库
        return $code;
    }

    /**
     * $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm;
     * 并发请求,商品入库存，访问java接口
     *
     */
    public function multParse()
    {
        if ($this->type === false) {
            $url = HOST_URL_API . '/batch/update_total.json';
            $ret = curl_get_json($url, json_encode($this->_requestData));
            trace($url,'requestUrl');
            trace($this->_requestData,'requestData');
            trace($ret,'responseData');
            //$ret = ZWebHttp::multiRequest($data);// 并发操作curl_get_json
            return json_decode($ret, true);
        } else {
            return $this->mulOut();
        }
    }

    /**
     * redis 缓存请求更新
     *
     */
    public function requestUpdateRedisCache()
    {
        curl_get_json(C('redis_batch_generate_url'));
    }

    /**
     *
     *
     */
    public function mulOut()
    {
        $url = HOST_URL_API . '/batch/export.json';
        $ret = curl_get_json($url, json_encode($this->_requestData));
        $this->setResponseData(array_merge((array)(json_decode($ret, true)), ['response_from_api_addr' => $url]));
        return json_decode($ret, true);
    }

    /**
     * 消息回送
     * @return code code值，预先定义。msg 信息类型文本提示。info 具体提示
     */
    public function parseInfo()
    {
        if ($this->_msgCode == '40050000') return ['code' => $this->_msgCode, 'msg' => $this->_errorInfo, 'info' => $this->_errorInfo];
        else return ['code' => $this->_msgCode, 'msg' => $this->msgCode()[$this->_msgCode], 'info' => $this->_errorInfo];
    }

    /**
     * 设置请求数据
     * @param $requstData 请求接口的数据
     */
    private function _setRequestData($requestData)
    {
        $this->_requestData = $requestData;
    }

    /**
     * 数据验证
     * 对订单与商品必填的数据进行校验，若有缺失则返回相对应的错误信息
     * @return boolean
     */
    private function _validata()
    {
        if ($this->_depr == '+') $flag = 'in';
        else $flag = 'out';
        $warehouse = [];
        foreach ($this->bill_attributes as $k => $value) {
            if ($value [0] == 'warehouse_id') $warehouse = $value;
            if (!isset($this->bill[$value [0]]) and ($value ['require'] == $flag or $value ['require'] == 'both')) $this->_errorInfo ['bill'][$value [0]] = $value ['message'];
        }
        foreach ($this->guds as $s => $j) {
            foreach ($this->guds_attributes as $k => $value) {
                if (!isset($this->guds [$s][$value [0]]) and ($value ['require'] == $flag or $value ['require'] == 'both')) $this->_errorInfo ['guds'][$s][$value [0]] = $value ['message'];
            }
        }
        if (!$this->checkWarehouse($this->bill['warehouse_id']) and ($warehouse ['require'] == 'in' or $warehouse ['require'] == 'both')) $this->_errorInfo ['warehouse_id'] = '仓库无效';
        if (!$this->checkOutOrInType($this->bill['bill_type'])) $this->_errorInfo ['warehouse_id'] = '出入库类型无效';
        if ($this->_errorInfo) return false;
        return true;
    }
}