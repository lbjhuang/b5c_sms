<?php

/**
 * 订单入库模型
 *
 */
class TbWmsBillModel extends BaseModel
{
    private $_msgCode;//消息编码
    private $_errorInfo;//错误信息
    private $_requestData;//请求接口的数据
    private $_lrSuccessCode = 2000;//接口返回成功的编码，固定不变
    private $_billId;//出入库单号bill_id系统生成
    private $_allo_data = [];//调拨数据,保存出库的数据用于入库
    protected $trueTableName = 'tb_wms_bill';

    public function __construct()
    {
        parent::__construct();
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
        ['total_cost', 'require' => 'none', 'message' => '入库总成本'],// 数据库无对应字段
        ['bill_state', 'require' => 'none', 'message' => '单据状态'],
        ['SALE_TEAM', 'require' => 'none', 'message' => '销售团队'],
        ['SP_TEAM_CD', 'require' => 'none', 'message' => '采购团队'],
        ['CON_COMPANY_CD', 'require' => 'in', 'message' => '我方公司（公司code）'],
        ['procurement_number', 'require' => 'none', 'message' => '采购单号'],
        ['third_order_id', 'require' => 'none', 'message' => '第三方订单id'],
        ['relation_type', 'require' => 'none', 'message' => '出入库关联单据类型'],
        ['bill_id', 'require' => 'none', 'message' => '出入库单号'],
        ['vir_type', 'require' => 'none', 'message' => '现货入库(N002440100)、在途入库(N002440200)'],
        ['processOnWay', 'require' => 'in', 'message' => '是否处理在途批次， virType = N002440100时必填，0 不处理（excel入库） 1-处理（采购入库）']
    ];

    public $guds_attributes = [
        ['GSKU', 'require' => 'both', 'message' => 'GSKU(SKU)'],
        ['taxes', 'require' => 'none', 'message' => '税率'],
        ['should_num', 'require' => 'none', 'message' => '应发数量'],
        ['send_num', 'require' => 'both', 'message' => '实发数量'],
        ['deadline_date_for_use', 'require' => 'none', 'message' => '生产日期(物品校期有效型必填)'],
        ['price', 'require' => 'both', 'message' => '单价'],
        ['currency_id', 'require' => 'both', 'message' => '币种'],// 数据库无对应字段
        ['currency_time', 'require' => 'both', 'message' => '交易发生时间，对应币种的汇率时间'],// 数据库无对应字段
        ['batch', 'require' => 'none', 'nessage' => '批次'],
        ['pur_invoice_tax_rate', 'require' => 'none', 'message' => '采购发票税率'],
        ['proportion_of_tax', 'require' => 'none', 'message' => '退税比率'],
        ['pur_storage_date', 'require' => 'none', 'message' => '采购入库时间'],
        ['storage_log_cost', 'require' => 'none', 'message' => '入库物流费用单价'],
        ['log_service_cost', 'require' => 'none', 'message' => '物流服务费用单价'],
        ['po_cost', 'require' => 'none', 'message' => 'PO费用'],
        ['log_currency', 'require' => 'none', 'message' => '物流币种'],
        ['log_service_cost_currency', 'require' => 'none', 'message' => '服务费用币种,对应老版（service_currency）'],
        ['po_currency', 'require' => 'none', 'message' => 'PO费用币种'],
    ];

    /**
     * 错误码
     */
    public function msgCode()
    {
        return [
            '10000000' => L('操作完成'),
            '10000001' => L('订单或商品数据不能为空'),
            '10000010' => L('参数缺失'),
            '10000101' => L('订单写入失败'),
            '10000110' => L('接口数据写入失败'),
            '10000111' => L('出库成功'),
            '10001011' => L('虚拟入库成功(同时出库)'),
            '10001111' => L('虚拟出库失败'),
            '10011111' => L('虚拟入库失败'),
            '11111111' => L('出入库类型参数错误，未知类型'),
            '10001110' => L('调拨失败'),
            '40050000' => L('发生错误'),
            '40050001' => L('出库单生成失败，数据回滚'),
            '40050002' => L('出库单子数据生成失败，数据回滚'),
            '40050003' => L('入库单生成失败，数据回滚'),
            '40050004' => L('入库单子数据生成失败，数据回滚'),
            '11110000' => L('程序异常'),
            '11110111' => L('调拨成功'),
            '11111011' => L('更新远程批次库失败'),
            '40050005' => L('更新调拨表失败'),
            '40050006' => L('分批入库失败，未查询到上一入库单'),
            '40050007' => L('未查询到相关币种汇率'),
            '40050008' => L('虚拟库存占用失败'),
            '40050009' => L('接口地址不能为空'),
            '40050010' => L('汇率获取异常')
        ];
    }

    // 返回code码
    const CODE_10000000 = 10000000;
    const CODE_10000001 = 10000001;
    const CODE_10000010 = 10000010;
    const CODE_10000101 = 10000101;
    const CODE_10000110 = 10000110;
    const CODE_10000111 = 10000111;
    const CODE_10001011 = 10001011;
    const CODE_10001111 = 10001111;
    const CODE_11111111 = 11111111;
    const CODE_10001110 = 10001110;
    const CODE_40050000 = 40050000;
    const CODE_40050001 = 40050001;
    const CODE_40050002 = 40050002;
    const CODE_40050003 = 40050003;
    const CODE_40050004 = 40050004;
    const CODE_11110000 = 11110000;
    const CODE_11110111 = 11110111;
    const CODE_11111011 = 11111011;
    const CODE_40050005 = 40050005;
    const CODE_40050006 = 40050006;
    const CODE_40050007 = 40050007;
    const CODE_40050008 = 40050008;
    const CODE_40050009 = 40050009;
    const CODE_40050010 = 40050010;

    private $_inCode;       // 入库类型的code值
    private $_outCode;      // 出库类型的code值
    private $_depr;         // 出入库类型1入，2出
    private $_flag = false; // 是否虚拟入库

    public $isVirtualDelivery = false;
    public $virtualDeliveryStorage = false;
    public $data;
    public $saleTeams;          // 销售团队code值
    public $type;               // b2b出入库设置使用，采购不使用该变量且不设置该变量
    public $batchInStorage = false; // 是否分批入库
    public $processOnWay;
    public $isStorageOcc; //是否入库即占用

    const STD_IN_STORAGE        = 1; // 入库
    const STD_OUT_OUTGOING      = 0; // 出库
    const IN_OUR_STORAGE        = 1; // 入库规则，真实入库
    const NO_IN_STORAGE         = 0; // 虚拟入库，入库即出库，虚拟仓
    const VIRTUAL_WAREHOUSE     = 'N000680800'; // 虚拟仓
    const VIRTUAL_STORAGE_CODE  = 'N000940100'; // 采购入库
    const VIRTUAL_OUTGOING_CODE = 'N000950100'; // 虚拟仓出库默认出库类型为销售出库
    const DIGIT_CURRENCY        = 4; // 金额小数点后面保留的位数
    const B2B_IN_STORAGE        = 1; // b2b 出入

    /**
     * 入库基础数据配置
     */
    public function setOptions()
    {
        //初始化入库、出库、销售团队 code 值
        $model = new Model();
        $this->_inCode   = $model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N000940%']])->getField('CD, CD_VAL');
        $this->_outCode  = $model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N000950%']])->getField('CD, CD_VAL');
        $this->saleTeams = $model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N001290%']])->getField('CD, CD_VAL');
        $billType        = $this->data ['bill']['bill_type'];// 出入库类型获取
        if (isset($this->_inCode[$billType])) {
            $this->_depr = static::STD_IN_STORAGE;
            $this->processOnWay = $this->data ['bill']['processOnWay'];
            $this->isStorageOcc = $this->data ['isStorageOcc'];
            unset($this->data ['bill']['processOnWay']);
        } else {
            $this->_depr = static::STD_OUT_OUTGOING;
        }
        // 虚拟仓出入库、需固定仓库为N000680800
        if ($this->_depr == static::STD_IN_STORAGE and $this->data ['bill']['warehouse_rule'] == self::NO_IN_STORAGE) {
            $this->_flag = true;
            $this->data ['bill']['warehouse_id'] = static::VIRTUAL_WAREHOUSE;
        }
        // b2b出入库设置使用，采购不使用该变量且不设置该变量
        if (isset($this->data ['type'])) {
            $this->type = $this->data ['type'];
        }
        // 设置是否是虚拟出入库,第一次如果是虚拟仓入库，需要将类型变换为采购入库，第二次请求为出库，将类型转换为销售出库
        if ($this->data ['bill']['warehouse_rule'] == self::NO_IN_STORAGE
            and $this->data ['bill']['warehouse_id'] == static::VIRTUAL_WAREHOUSE
            and $this->_depr == static::STD_IN_STORAGE) {
            $this->data ['bill']['bill_type'] = static::VIRTUAL_STORAGE_CODE;
        }
        // 是否是分批入库
        if (isset($this->data ['bill']['bill_id']))
            $this->batchInStorage = true;
    }

    /**
     * 虚拟出库时，需要改变出入库类型字段
     * @var
     */
    public function changeBillType()
    {
        $this->bill ['bill_type']      = static::VIRTUAL_OUTGOING_CODE;
        $this->bill ['warehouse_rule'] = 5;
        if (stripos($this->bill ['link_bill_id'], 'RN') === 0) {
            $this->bill ['link_bill_id'] = explode('-', $this->bill ['link_bill_id'])[0];
            $this->bill ['relation_type'] = "N002350400";
        }
        $this->_depr                   = static::STD_OUT_OUTGOING;
    }

    public $bill;//订单信息
    public $guds;//商品信息
    public $operatorId;//操作人

    /**
     * @param mixed $data 数据
     * @return array
     */
    public function outAndInStorage($data)
    {
        try {
            $this->data = $data;
            // 基础数据配置
            $this->setOptions();
            // 设置出入库单数据与子表数据
            is_null($this->data ['bill']) or $this->bill = $this->data ['bill'];
            is_null($this->data ['guds']) or $this->guds = $this->data ['guds'];
            is_null($this->data ['operatorId']) or $this->operatorId = $this->data['operatorId'];
            is_null($this->data ['operatorId']) or $_SESSION ['userId'] = $this->data['operatorId'];
            if (!$this->bill ['vir_type']) {
                $this->bill ['vir_type'] = 'N002440100';
            }
            // 虚拟仓出入库
            if ($this->_flag) {
                // 虚拟入库
                $this->storage();
                // 出库时，修改某些内容
                $this->changeBillType();
                // 虚拟出库
                $this->virtualOutGoing();
                // 接口返回
                return ['code' => static::CODE_10001011, 'msg' => $this->msgCode()[static::CODE_10001011], 'outgoingNo' => $this->outgoingNo, 'batchSku' => $this->batchSku];
            } else {
                switch ($this->_depr) {
                    case static::STD_IN_STORAGE:
                        // 真实入库
                        $this->storage();
                        break;
                    case static::STD_OUT_OUTGOING:
                        // 真实出库
                        $this->outgoing();
                        break;
                }
                // 接口返回
                return ['code' => static::CODE_10000000, 'msg' => $this->msgCode()[static::CODE_10000000], 'outgoingNo' => $this->outgoingNo, 'batchSku' => $this->batchSku];
            }
        } catch (WarehouseException $e) {
            return ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'outgoingNo' => $this->outgoingNo];
        }
    }

    public $batchSku = null;

    /**
     * 入库
     */
    public function storage()
    {
        // 入库接口地址
        $url = OLD_HOST_URL_API . '/batch/update_total.json';
        // 开启事物
        $this->startTrans();
        // 设置常用数据
        $this->setHeader();
        // 数据过滤
        $this->_validata();
        // 处理入库单
        $this->parseBill();
        // 处理出库单
        $this->parseGuds();
        // 接口请求
        $this->sendRequest($url);
        // 日志记录
        $this->_catchMe();
        if ($this->getResponseData() ['code'] == $this->_lrSuccessCode) {
            $this->batchSku($this->getResponseData() ['data']['batch']);
            $this->commit();
            $warehouse = new WarehouseIOModel();
            $batch = $warehouse->storage($this->getResponseData() ['data']);
            $warehouse->updateStream($batch);
            // 出入库物流成本流水表
            $costLogModel = new TbWmsStreamCostLogModel();
            $costLogModel->addRecording($this->streamCostLogData);
        } else {
            $this->rollback();
            throw new WarehouseException(L($this->getResponseData() ['msg']), $this->getResponseData() ['code']);
        }
    }

    /**
     * 获取SKU生成的批次
     * @param $data
     */
    public function batchSku($data)
    {
        $tmp = null;
        foreach ($data as $key => $value) {
            foreach ($value ['data'] as $i => $batch) {
                $tmp [$batch ['skuId']] = $batch ['id'];
            }
        }

        $this->batchSku = $tmp;
    }

    /**
     * 正常出库
     */
    public function outgoing()
    {
        // 出库接口地址
        $url = $url = HOST_URL_API . '/batch/export.json';
        // 开启事物
        $this->startTrans();
        // 设置常用数据
        $this->setHeader();
        // 数据过滤
        $this->_validata();
        // 处理入库单
        $this->parseBill();
        // 处理出库单
        $this->parseGuds();
        // 接口请求
        if ($this->type == static::B2B_IN_STORAGE) {
            $url = OLD_HOST_URL_API . '/batch/export2.json';
        }
        $this->sendRequest($url);
        // 日志记录
        $this->_catchMe();
        if ($this->type == static::B2B_IN_STORAGE) {
            if ($this->getResponseData() ['code'] == $this->_lrSuccessCode) {
                $warehouse = new WarehouseIOModel();
                $responseData = $this->getResponseData();
                $responseData ['data']['billId'] = $this->_lastBillId;
                $stream = $warehouse->outgoingExtend($responseData ['data'], true);
                // 设置出库单基础信息
                $this->setHeader();
                // 处理出库单
                $this->parseBill();
                $stream = array_map(function($r) {
                    $r ['bill_id'] = $this->_lastBillId;
                    return $r;
                }, $stream);
                $model = new Model();
                if ($model->table('tb_wms_stream')->addAll($stream)) {
                    $this->commit();
                } else {
                    $this->rollback();
                    throw new WarehouseException(L('出库单子数据生成失败，数据回滚'), static::CODE_40050002);
                }
            } else {
                throw new WarehouseException(L($this->getResponseData() ['msg']), $this->getResponseData() ['code']);
            }
        } else {
            if ($this->getResponseData() ['code'] == $this->_lrSuccessCode) {
                $warehouse = new WarehouseIOModel();
                if ($warehouse->outgoing($this->getResponseData()['data']['export'])) {
                    $this->commit();
                } else {
                    $this->rollback();
                    throw new WarehouseException(L('出库单子数据生成失败，数据回滚'), static::CODE_40050002);
                }
            } else {
                $this->rollback();
                throw new WarehouseException(L($this->getResponseData() ['msg']), $this->getResponseData() ['code']);
            }
        }
    }

    /**
     * 虚拟出库，先走占用，再走订单出库
     */
    public function virtualOutGoing()
    {
        // 虚拟库存占用接口地址
        $url = OLD_HOST_URL_API . '/process/public_process.json';
        // 占用流程
        $occupy = $tmp = null;
        $orderId = create_guid();
        $request ['processCode'] = "BATCH_OCCUPY_PROCESS";
        $request ['processId'] = $orderId;
        foreach ($this->getResponseData() ['data']['batch'] as $num => $batch) {
            $tmp ['orderId'] = $orderId;
            foreach ($batch ['data'] as $key => $value) {
                $tmp ['batches'][] = [
                    'batchId' => $value ['id'],
                    'num' => $value ['allAvailableForSaleNum']
                ];
            }
            $occupy [] = $tmp;
            $tmp = null;
        }
        $request ['data']['occupy'] = $occupy;
        $this->_setRequestData($request);
        // 调用占用接口
        $this->sendRequest($url);
        // 占用日志记录
        $this->_catchMe();
        // 接口返回处理
        if ($this->getResponseData() ['code'] == $this->_lrSuccessCode) {
            // 虚拟库存占用成功，开始走订单出库
            $request = null;
            $request ['processCode'] = "ORDER_EXPORT";
            $request ['processId']   = create_guid();
            $request ['data'][]      = [
                'operatorId' => $_SESSION ['userId'],
                'orderId'    => $orderId
            ];
            $this->_setRequestData($request);
            // 虚拟仓库订单出库地址
            $url = OLD_HOST_URL_API . '/batch/export2.json';
            $this->sendRequest($url);
            $this->_catchMe();
            if ($this->getResponseData() ['code'] == $this->_lrSuccessCode) {
                $warehouse = new WarehouseIOModel();
                $stream = $warehouse->outgoingExtend($this->getResponseData() ['data'], true);
                $this->startTrans();
                // 设置出库单基础信息
                $this->setHeader();
                // 处理出库单
                $this->parseBill();
                $stream = array_map(function($r) {
                    $r ['bill_id'] = $this->_lastBillId;
                    return $r;
                }, $stream);
                $model = new Model();
                if ($model->table('tb_wms_stream')->addAll($stream)) {
                    $this->commit();
                } else {
                    $this->rollback();
                    throw new WarehouseException(L('出库单子数据生成失败，数据回滚'), static::CODE_40050002);
                }
            } else {
                throw new WarehouseException(L($this->getResponseData() ['msg']), $this->getResponseData() ['code']);
            }
        } else {
            throw new WarehouseException(L('虚拟库存占用失败，无法出库'), static::CODE_40050008);
        }
    }

    /**
     * 入库接口请求
     * @param string $url 接口地址
     * @throws WarehouseException
     */
    public function sendRequest($url)
    {
        if (is_null($url) or empty($url)) {
            throw new WarehouseException(L('接口地址不能为空'), static::CODE_40050009);
        }
        $ret = curl_get_json($url, json_encode($this->getRequestData()));
        $ret = json_decode($ret, true);
        $this->setResponseData($ret);
        $this->setRequestUrl($url);
    }

    public $outgoingNo; // 分批入库时用到，跟随接口返回

    /**
     * 设置写入时必须设置的数据
     */
    public function setHeader()
    {
        // 生成出入库单号
        $stock = A('Home/Stock');
        if (!isset($this->bill ['bill_id']))
            $this->bill ['bill_id'] = $stock->get_bill_id($this->bill ['bill_type'], $this->bill ['bill_date']);
        $this->outgoingNo         = $this->bill ['bill_id']; // 出入库单号
        $this->bill ['zd_user']   = $_SESSION ['userId']; // 制单人（创建人）
        $this->bill ['zd_date']   = date('Y-m-d H:i:s', time()); // 创建时间，年月日时分秒
        $this->bill ['bill_date'] = date('Y-m-d', time()); // 创建日期，年月日
        $this->bill ['type']      = $this->_depr; // 出入库
    }

    private $_lastBillId;//最后一次插入的订单id

    /**
     * 订单处理
     * @throws WarehouseException
     * @return 成功返回插入数据id，失败返回false
     */
    public function parseBill()
    {
        // 分批入库逻辑
        if ($this->batchInStorage) {
            $ret = $this->where(['bill_id' => ['eq', $this->bill ['bill_id']]])->find();
            if ($ret) {
                $this->_lastBillId = $ret ['id'];
            } else {
                throw new WarehouseException(L('分批入库时，未查询到上一次入库单'), static::CODE_40050006);
            }
        } else {
            // b2c
            $third_order_id = $this->bill['third_order_id'];
            unset($this->bill['third_order_id']);
            if ($this->add($this->bill)) {
                $this->bill ['third_order_id'] = $third_order_id;
                $this->_lastBillId = $this->getLastInsID();
            } else {
                throw new WarehouseException(L('入库单写入失败') . $this->getDbError(), static::CODE_40050003);
            }
        }
    }

    public $streamCostLogData;

    /**
     * 商品处理
     * 商品处理，计算商品的基本数据，写入商品表
     * @throws WarehouseException
     */
    public function parseGuds()
    {
        if ($this->type === static::B2B_IN_STORAGE)
            return $this->b2bCustom();
        $header ['data']['batch'] = [];
        $request ['billId']   = $this->_lastBillId;
        $request ['lockCode'] = create_guid();
        $request ['type'] = 0; // 0普通入库，1调拨出入库
        $request ['data'] = [];
        $stream = M('_wms_stream', 'tb_');
        $flag = true;
        $warehouse_id = $this->bill ['warehouse_id'];
        foreach ($this->guds as $key => &$value) {
            $ret = BaseModel::exchangeRate($value ['currency_id'], $value ['currency_time']);
            if (!$ret ['cny'] or !$ret ['usd']) {
                $code = static::CODE_40050010;
                $message = L('汇率获取失败');
                throw new WarehouseException($message, $code);
            }
            if ($value ['log_service_cost'] > 0) {
                // 服务费用、按采购入库时间取汇率
                $serviceRate = BaseModel::exchangeRate($value ['log_service_cost_currency'], $value ['pur_storage_date']);
                if (!$serviceRate ['cny'] or !$serviceRate ['usd']) {
                    $code = static::CODE_40050010;
                    $message = L('服务费用汇率获取失败');
                    throw new WarehouseException($message, $code);
                }
            }
            if ($value ['storage_log_cost'] > 0) {
                // 物流费用、按采购入库时间取汇率
                $logRate = BaseModel::exchangeRate($value ['log_currency'], $value ['pur_storage_date']);
                if (!$logRate ['cny'] or !$logRate ['usd']) {
                    $code = static::CODE_40050010;
                    $message = L('物流汇率获取失败');
                    throw new WarehouseException($message, $code);
                }
            }
            if ($value ['po_cost'] > 0) {
                // PO费用、按采购创建时间取汇率
                $poRate = BaseModel::exchangeRate($value ['po_currency'], $value ['currency_time']);
                if (!$poRate ['cny'] or !$poRate ['usd']) {
                    $code = static::CODE_40050010;
                    $message = L('PO费用汇率获取失败');
                    throw new WarehouseException($message, $code);
                }
            }
            $value ['unit_price']     = bcmul($value ['price'], $ret ['cny'], static::DIGIT_CURRENCY);//含税RMB单价
            $value ['unit_price_usd'] = bcmul($value ['price'], $ret ['usd'], static::DIGIT_CURRENCY);
            $value ['bill_id']        = $this->_lastBillId;
            $value ['no_unit_price']  = bcsub($value ['price'], bcmul($value ['price'], $value ['taxes'], static::DIGIT_CURRENCY), static::DIGIT_CURRENCY);//不含税单价
            $value ['unit_money']     = bcmul($value ['unit_price'], $value ['send_num'], static::DIGIT_CURRENCY);//含税金额
            $value ['no_unit_money']  = bcmul($value ['no_unit_price'],  $value ['send_num'], static::DIGIT_CURRENCY);//去税金额
            $value ['duty']           = bcsub($value ['unit_money'], $value ['no_unit_money'], static::DIGIT_CURRENCY);//税额
            $value ['add_time']       = $value ['pur_storage_date'];//交易发生时间
            $value ['warehouse_id']   = $warehouse_id;
            //删除单价
            unset($value ['price']);
            if ($this->_depr == static::STD_OUT_OUTGOING) {
                $data = null;
                $data ['gudsId']              = substr($value ['GSKU'], 0, -2);
                $data ['skuId']               = trim($value ['GSKU']);
                $data ['num']                 = (int)($value ['send_num']);
                $data ['operatorId']          = $_SESSION['userId'];
                $data ['type']                = 1;
                $data ['saleTeamCode']        = $this->bill ['SALE_TEAM'];
                $data ['billId']              = $this->_lastBillId;
                $data ['deliveryWarehouse']   = $this->bill ['warehouse_id'];
                $data ['orderId']             = $this->bill ['third_order_id'];
                $request ['data']['export'][] = $data;
            } else {
                $value ['all_carry_cost'] = $value ['storage_log_cost'] * $logRate ['usd'];
                $value ['all_storage_log_cost'] = $value ['storage_log_cost'] * $logRate ['usd'];
                $value ['all_log_service_cost'] = $value ['log_service_cost'] * $serviceRate ['usd'];
                $value ['all_po_cost'] = $value ['po_cost'] * $poRate ['usd'];
                $value ['carry_cost'] = $value ['storage_log_cost'] * $logRate ['usd'];
                $value ['po_cost'] = $value ['po_cost'] * $poRate ['usd'];
                $value ['log_service_cost'] = $value ['log_service_cost'] * $serviceRate ['usd'];
                $value ['storage_log_cost'] = $value ['storage_log_cost'] * $logRate ['usd'];
                if (!$stream_id = $stream->add($value)) {
                    if ($this->_depr == static::STD_IN_STORAGE) {
                        $code    = static::CODE_40050004;
                        $message = L('入库单子数据写入失败');
                    } else {
                        $code    = static::CODE_40050002;
                        $message = L('出库单子数据写入失败');
                    }
                    throw new WarehouseException($message, $code);
                }

                $this->streamCostLogData [] = [
                    'stream_id' => $stream_id,
                    'log_service_cost' => $value ['log_service_cost'],
                    'storage_log_cost' => $value ['storage_log_cost'],
                    'carry_cost' => $value ['storage_log_cost'],
                    'all_log_service_cost' => $value ['log_service_cost'],
                    'all_storage_log_cost' => $value ['storage_log_cost'],
                    'all_carry_cost' => $value ['storage_log_cost'],
                    'po_cost' => $value ['po_cost'] ,
                    'all_po_cost' => $value ['po_cost'],
                    'currency_id' => 'N000590100', // 美元币种
                    'pur_storage_date' => $value ['currency_time'], // 转换日期
                    'service_and_log_rate_time' => $value ['pur_storage_date'],
                ];

                $data = null;
                $data ['gudsId']             = substr($value ['GSKU'], 0, -2);
                $data ['skuId']              = trim($value ['GSKU']);
                $data ['num']                = (int)($value ['send_num']);
                $data ['deadlineDateForUse'] = $value ['deadline_date_for_use'];
                $data ['operatorId']         = $_SESSION['userId'];
                $data ['purchaseOrderNo']    = $this->bill ['procurement_number'];
                $data ['saleTeamCode']       = $this->bill ['SALE_TEAM'];
                $data ['purchaseTeamCode']   = $this->bill ['SP_TEAM_CD'];
                $data ['orderId']            = $this->bill ['third_order_id'];
                $data ['channel']            = $this->bill ['channel'];
                $data ['type']               = $this->type;
                $data ['channelSkuId']       = 0;
                $data ['streamId']           = $stream_id;
                $data ['deliveryWarehouse']  = $this->bill ['warehouse_id'];
                $data ['virType']            = $this->bill ['vir_type'];
                $data ['processOnWay']       = $this->processOnWay;
                $request ['data'][]          = $data;
            }
            unset($value);
            $data = null;
        }

        if ($this->_depr == static::STD_OUT_OUTGOING) {
            $header = $request;
        } else {
            $header['data']['batch'][] = $request;
        }

        $this->_setRequestData($header);
    }

    /**
     * B2B接口专用
     *
     */
    public function b2bCustom()
    {
        $header ['processCode'] = 'B2B_EXPORT';
        $header ['processId']   = create_guid();
        $header ['data'] = $tmp = null;
        foreach ($this->guds as $key => &$value) {
            $tmp = null;
            $tmp ['operatorId']   = $this->operatorId?$this->operatorId:$_SESSION ['userId'];
            $tmp ['orderId']      = $this->bill ['third_order_id'];
            $tmp ['skuId']        = trim($value ['GSKU']);
            $tmp ['saleTeamCode'] = trim($this->bill ['SALE_TEAM']);
            $tmp ['deliveryWarehouse'] = trim($this->bill ['warehouse_id']);
            $tmp ['num']          = trim($value ['send_num']);
            $data [] = $tmp;
        }
        $header ['data'] = $data;
        $this->_setRequestData($header);
        return;
        foreach ($this->guds as $key => &$value) {
            $data = null;
            $data ['skuId']          = trim($value ['GSKU']);
            $data ['gudsId']         = substr($value ['GSKU'], 0, -2);
            $data ['orderId']        = $this->bill ['link_bill_id'];
            $data ['saleTeamCode']   = $this->bill ['SALE_TEAM'];
            $data ['operatorId']     = (int)$_SESSION['userId'];
            $data ['num']            = (int)($value ['send_num']);
            $data ['type']           = (int)$this->type;
            $data ['channel']        = $this->bill ['channel'];
            $data ['billId']         = $this->_lastBillId;
            $data ['deliveryWarehouse'] = $this->bill ['warehouse_id'];
            $data ['thirdOrderId']   = $this->bill ['third_order_id'];
            $rdata [] = $data;
        }
        $header ['data']['export'] = $rdata;
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

    /**
     * 设置请求数据
     * @param mixed $requestData 请求接口的数据
     */
    private function _setRequestData($requestData)
    {
        $this->_requestData = $requestData;
    }

    public function getRequestData()
    {
        return $this->_requestData;
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
        if ($this->_errorInfo) return false;
        return true;
    }
}

class WarehouseException extends \Exception
{

}