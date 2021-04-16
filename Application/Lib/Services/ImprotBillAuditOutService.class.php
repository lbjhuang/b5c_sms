<?php

/**
 * 出入库流程出库（重写）
 * Class ImprotBillAuditOutService
 */
class ImprotBillAuditOutService extends Service
{
    private $reqData;

    public $excel;
    public $data;
    public $title;
    public $errorinfo;
    public $saveName;
    public $excel_path;
    public $firstCellRowData;
    public $existingError;
    protected $autoCheckFields  =   false;

    /**
     * 匹配验证模式
     * @param $mode
     * @return mixed
     */
    public function fieldMapping($mode)
    {
        $rules = [
            'default' => [
                'skuId' => ['field_name' => '商品编码', 'required' => false],//1
                'upcId' => ['field_name' => '条形码', 'required' => false],//2
                'number' => ['field_name' => '数量', 'required' => true],//3
                'price' => ['field_name' => '单价', 'required' => false],//4
                'currency' => ['field_name' => '币种', 'required' => false],//5
                'money' => ['field_name' => '金额', 'required' => false],//6
                'deadLine' => ['field_name' => '到期日', 'required' => false],//7
                'storageWarehouse' => ['field_name' => '出库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => false],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队CODE', 'required' => false],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => false],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => false],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => false],//19
                'storageDate' => ['field_name' => '入库时间', 'required' => false],//20
                'storageMark' => ['field_name' => '出库原因CODE', 'required' => true],//21
                'batchCode' => ['field_name' => '批次编码', 'required' => false],//21
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//23
                'remark' => ['field_name' => '备注', 'required' => true],
                'responsible' => ['field_name' => '责任归属', 'required' => true],
            ],
            'purOrderNoEmpty' => [
                'skuId' => ['field_name' => '商品编码', 'required' => false],//1
                'upcId' => ['field_name' => '条形码', 'required' => false],//2
                'number' => ['field_name' => '数量', 'required' => true],//3
                'price' => ['field_name' => '单价', 'required' => false],//4
                'currency' => ['field_name' => '币种', 'required' => false],//5
                'money' => ['field_name' => '金额', 'required' => false],//6
                'deadLine' => ['field_name' => '到期日', 'required' => false],//7
                'storageWarehouse' => ['field_name' => '出库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => false],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队CODE', 'required' => false],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => false],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => false],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => false],//19
                'storageDate' => ['field_name' => '入库时间', 'required' => false],//20
                'storageMark' => ['field_name' => '出库原因CODE', 'required' => true],//21
                'batchCode' => ['field_name' => '批次编码', 'required' => false],//21
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//23
                'remark' => ['field_name' => '备注', 'required' => true],
                'responsible' => ['field_name' => '责任归属', 'required' => true],
            ],
            'skuIdEmpty' => [
                'skuId' => ['field_name' => '商品编码', 'required' => false],//1
                'upcId' => ['field_name' => '条形码', 'required' => true],//2
                'number' => ['field_name' => '数量', 'required' => true],//3
                'price' => ['field_name' => '单价', 'required' => false],//4
                'currency' => ['field_name' => '币种', 'required' => false],//5
                'money' => ['field_name' => '金额', 'required' => false],//6
                'deadLine' => ['field_name' => '到期日', 'required' => false],//7
                'storageWarehouse' => ['field_name' => '出库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => false],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队CODE', 'required' => false],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => false],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => false],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => false],//19
                'storageDate' => ['field_name' => '入库时间', 'required' => false],//20
                'storageMark' => ['field_name' => '出库原因CODE', 'required' => true],//21
                'batchCode' => ['field_name' => '批次编码', 'required' => false],//21
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//23
                'remark' => ['field_name' => '备注', 'required' => true],
                'responsible' => ['field_name' => '责任归属', 'required' => true],            ],
        ];

        return $rules [$mode];
    }

    /**
     * 获取标题与rules字段对应
     * @param string $mode
     */
    public function getTitle($mode = 'default')
    {
        $fields = $this->fieldMapping($mode);
        foreach ($this->firstCellRowData as $key => $value) {
            foreach ($fields as $k => $v) {
                if ($v ['field_name'] == $value) {
                    $temp [$key] ['db_field'] = $k;
                    $temp [$key] ['required'] = $v['required'];
                    $temp [$key] ['en_name'] = $value;
                }
            }
        }
        $this->title = $temp;
    }

    public static $baseCode;
    /**
     * 码表配置数据获取
     * 仓库CODE -
     * 采购团队CODE
     * 介绍团队CODE
     * 介绍团队类型CODE-
     * 采购团队公司CODE-
     * 销售小团队CODE
     */
    public function baseCode()
    {
        if (static::$baseCode) {
            return static::$baseCode;
        }
        $model = new Model();
        static::$baseCode = $model->table('tb_ms_cmn_cd')
            ->where('CD LIKE "N00124%" OR CD LIKE "N00323%" OR CD LIKE "N00068%" OR CD LIKE "N002460%" OR CD LIKE "N00129%" OR CD LIKE "N00128%" OR ( CD LIKE "N00095%" AND ETC3 = "EXCEL出入库") ')
            ->getField('CD, CD_VAL');

        return static::$baseCode;
    }

    /**
     * 获取EXCEL文档每行的数据
     */
    public function getData()
    {
        $data = [];
        // read
        for ($this->excel->start_row; $this->excel->start_row <= $this->excel->max_row; $this->excel->start_row ++) {
            $data [$this->excel->start_row] = $this->getCellData($this->excel->start_row);
        }
        // 基础CODE
        $this->baseCode();
        // save
        $r = [];
        foreach ($data as $key => $value) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp [$this->title [$k]['db_field']]['columnIndex'] = $k;
                $temp [$this->title [$k]['db_field']]['value'] = $v;
                $temp [$this->title [$k]['db_field']]['rowIndex'] = $key;
                // 默认数据设置
                $temp ['channel'] = ['value' => 'N000830100'];
                $temp ['billState'] = ['value' => 1];
                $temp ['userId'] = ['value' => BaseModel::getName()];
                $temp ['zdUser'] = ['value' => session('user_id')];
                $temp ['zdDate'] = ['value' => date('Y-m-d H:i:s', time())];
                $temp ['billDate'] = ['value' => date('Y-m-d', time())];
                $temp ['virType'] = ['value' => 'N002440100'];
            }
            $r [$key] = $temp;
        }
        $this->data = $r;
        unset($data, $r);
    }

    /**
     * 采购订单相关信息
     * @var
     */
    public $purOrderInfo;

    /**
     * 条形码相关信息
     */
    public $upcIdInfo;

    /**
     * 采购入库时间，用于汇率获取
     */
    public $purStorageDate;

    /**
     * 币种
     */
    public $curr;

    /**
     * 临时转换使用
     */
    public $tmpCurr;

    /**
     * 数据处理
     */
    public function processData()
    {
        $this->purOrderInfo   = array_column($this->data, 'purOrderNo');
        $this->upcIdInfo      = array_column($this->data, 'upcId');
        $this->purStorageDate = array_column($this->data, 'purStorageDate');
        $this->curr           = array_column($this->data, 'currency');
        // 数据验证
        foreach ($this->data as $key => $val) {
            $this->valid($key, $val);
        }
    }

    /**
     * 基础数据
     */
    public function basicsData()
    {
        $purOrderNos = array_unique(array_column($this->purOrderInfo, 'value'));
        $upcIds = array_unique(array_column($this->upcIdInfo, 'value'));

        $model = new Model();
        // 如果存在条形码，且没有SKU ID 获取与条形码关联的SKU ID，以进行数据替换
        if ($upcIds) {
            $this->upcIdInfo = $model->table(PMS_DATABASE . '.product_sku')
                ->where(['upc_id' => ['in', $upcIds]])
                ->getField('upc_id, sku_id as skuId');
            # 增加多sku 业务处理逻辑
            if(count($this->upcIdInfo) != count($upcIds))  {
                $map = [];
                $insets = [];
                foreach ($upcIds as $upcId) {
                    $insets[] = "FIND_IN_SET('{$upcId}',upc_more)";
                }
                $map["_string"] = implode(" OR " , $insets);
                $sku_upc_mores =$model->table(PMS_DATABASE . '.product_sku')->where($map)->getField('upc_more,sku_id as skuId');
                if($sku_upc_mores) {
                    $sku_upc_sku_map = [];
                    foreach ($sku_upc_mores as $upc_more => $sku_id) {
                        $upc_more_arr = explode(',', $upc_more);
                        foreach ($upc_more_arr as $upc) {
                            $sku_upc_sku_map[$upc] = $sku_id;
                        }
                    }
                    $upcIdInfo = $sku_upc_sku_map;
                    if($this->upcIdInfo) {
                        $upcIdInfo = array_unique(array_merge($sku_upc_sku_map,$this->upcIdInfo));
                    }
                    $this->upcIdInfo = $upcIdInfo;
                }
            }
        }
        // 如果有采购单，获取采购单数据，以进行数据替换
        if ($purOrderNos) {
            $temp = [];
            $purInfo = $model->table('tb_pur_relevance_order t1')
                ->join('tb_pur_order_detail t2 ON t1.order_id = t2.order_id')
                ->join('tb_pur_sell_information t3 ON t1.sell_id = t3.sell_id')
                ->join('tb_pur_goods_information t4 ON t1.relevance_id = t4.relevance_id')
                ->field([
                    't2.procurement_number as purOrderNo',//采购单号
                    't4.unit_price as unitPrice',// 单价
                    't2.currency',//币种
                    //'t2.amount',// 金额
                    't2.payment_company as purTeam',// 采购团队
                    't2.our_company as purTeamCompany',// 采购团队公司(CODE)
                    't3.sell_team as saleTeam',// 销售团队（CODE）
                    't2.tax_rate as purInvoiceTaxRate',// 采购发票税率
                    't4.sku_information as skuInformation'
                    //采购无退税比率
                ])
                ->where(['t2.procurement_number' => ['in', $purOrderNos]])
                ->select();
            if ($purInfo) {
                foreach ($purInfo as $key => $value) {
                    $temp [$value ['purOrderNo'] . $value ['skuInformation']] = $value;
                }
            }
            $this->purOrderInfo = $temp;
        }
    }

    /**
     * @param 行坐标 $rowIndex
     * @param 列坐标 $val
     * @return null|void
     */
    public function valid($rowIndex, $val)
    {
        // 基础必填验证
        foreach ($val as $fieldName => $data) {
            $this->validExtend($rowIndex, $data ['columnIndex'], $data ['value'], $fieldName);
        }

        // 采购单号为空时，必填校验
        if (empty($val ['purOrderNo']['value'])) {
            $this->getTitle('purOrderNoEmpty');
            foreach ($val as $fieldName => $data) {
                $this->validExtend($rowIndex, $data ['columnIndex'], $data ['value'], $fieldName);
            }
        }

        // SKU 为空时
        if (empty($val ['skuId']['value'])) {
            $this->getTitle('skuIdEmpty');
            foreach ($val as $fieldName => $data) {
                $this->validExtend($rowIndex, $data ['columnIndex'], $data ['value'], $fieldName);
            }
        }

        $is_check_small = false;
        foreach ($val as $fieldName => $data) {
            if ($fieldName === 'smallSaleTeam') {
                $is_check_small = true;
            }
        }
        if (!$is_check_small) {
            $this->errorinfo [][$rowIndex.$columnIndex] = L('缺失销售小团队字段，请确认是否为最新模板，建议重新下载最新模板');
        }
    }

    public function validateDate($date)
    {
        $format = 'Y-m-d H:i:s';
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) == $date) {
            return true;
        } else {
            $format = 'Y-m-d';
            $d = DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        }
    }

    public function validateCurrency($currency)
    {
        if (isset(BaseModel::getCurrencyFlip() [$currency])) {
            return true;
        }

        return false;
    }

    /**
     * @param $row_index     行坐标
     * @param $column_index  列坐标
     * @param $value         当前坐标对应的值
     * @param $fieldName     当前坐标对应的字段名
     */
    public function validExtend($row_index, $column_index, $value, $fieldName)
    {
        $flag = false;
        // 必填验证，或填了不满足规范的值
        if (($this->title [$column_index]['required'] and $value === '') or ($value !== '')) {
//            if ($this->title [$column_index]['required'] and $value != '') {
//                if ($fieldName == 'introTeam') {
//                    $flag = true;
//                }
//            }
            if ($this->title [$column_index]['required'] and $value === '') {
                if (!isset($this->existingError [$row_index.$column_index])) {
                    $this->existingError [$row_index.$column_index] = L($this->title [$column_index]['en_name'] . '必填');
                    $this->errorinfo [][$row_index.$column_index] = L($this->title [$column_index]['en_name'] . '必填');
                }
            } else {
                if (!isset($this->existingError [$row_index.$column_index])) {
                    $this->existingError [$row_index.$column_index] = '占位符';
                    switch ($fieldName) {
                        case 'storageWarehouse':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的仓库CODE');
                            break;
                        case 'introTeam':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的介绍团队CODE');
                            break;
                        case 'introType':
                            //if ($flag == true) {
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的介绍团队类型CODE');
                            //}
                            break;
                        case 'purTeamCompany':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的采购团队所属公司CODE');
                            break;
                        case 'purTeam':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的采购团队CODE');
                            break;
                        case 'storageMark':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的出库原因CODE');
                            break;
                        case 'saleTeam':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的销售团队CODE');
                            break;
                        case 'smallSaleTeam':
                            isset(static::$baseCode [$value]) or $this->errorinfo [][$row_index . $column_index] = L('不是有效的销售小团队CODE');
                            break;
                        case 'number':
                            $isInteger2 = function ($v) {
                                $i = intval($v);
                                if ("$i" == "$v") {
                                    return TRUE;
                                } else {
                                    return FALSE;
                                }
                            };
                            $isInteger2($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的数量（整数类型）');
                            break;
                        case 'purInvoiceTaxRate':
                            is_numeric($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的采购发票税率（整数或小数类型）');
                            break;
                        case 'proportionOfTax':
                            is_numeric($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的退税比率（整数或小数类型）');
                            break;
                        case 'storageLogCost':
                            is_numeric($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的出库物流费用单价（整数或小数类型）');
                            break;
                        case 'logServiceCost':
                            is_numeric($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的物流服务费用单价（整数或小数类型）');
                            break;
                        case 'price':
                            is_numeric($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的单价（整数或小数类型）');
                            break;
                        case 'purStorageDate':
                            $this->validateDate($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的采购出库时间');
                            break;
                        case 'storageDate':
                            $this->validateDate($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的出库时间');
                            break;
                        case 'currency':
                            $this->validateCurrency($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的币种信息');
                            break;
                        case 'deadLine':
                            $this->validateDate($value) or $this->errorinfo [][$row_index . $column_index] = L('请填写有效的到期日');
                            break;
                    }
                }
            }
        }
    }

    /**
     * 主出入库单
     * @var
     */
    public $bill;

    /**
     * 出入库子单
     * @var
     */
    public $stream;

    /**
     * 批次信息
     */
    public $batchCode;

    /**
     * 数据再组装
     * 对采购商进行组装，去重验证
     */
    public function packData()
    {
        $data = [];
        // 币种=>CODE
        $this->tmpCurr = array_flip(BaseModel::getCurrencyFlip());
        // CODE=>币种
        $flipCurrency = array_flip($this->tmpCurr);
        $outType =M()->table('tb_ms_cmn_cd')
            ->where(['CD' => ['like', 'N00095%'], 'USE_YN' => 'Y'])
            ->getField('CD,CD_VAL');
        // 数据补填
        $skuList = [];
        $batchCodeList = [];
        foreach ($this->data as $index => &$info) {
            //收发类别校验
            if (!isset($outType[$info['storageMark']['value']])) {
                if (!isset($this->existingError [$info ['storageMark']['columnIndex'].$info ['storageMark']['rowIndex']])) {
                    $this->existingError [$info ['storageMark']['columnIndex'].$info ['storageMark']['rowIndex']] = '占位符';
                    $this->errorinfo [][$info ['storageMark']['columnIndex'].$info ['storageMark']['rowIndex']] = L($this->title [$info ['storageMark']['columnIndex']]['en_name'] . '[' .$info ['storageMark']['value'] . ']' . '未获取到对应的出库原因');
                }
            }
            // SKU 不存在的情况
            if (empty($info ['skuId']['value'])) {
                if (isset($this->upcIdInfo [$info ['upcId']['value']])) {
                    $info ['skuId']['value'] = $this->upcIdInfo [$info ['upcId']['value']];
                } else {
                    if (!isset($this->existingError [$info ['upcId']['columnIndex'].$info ['upcId']['rowIndex']])) {
                        $this->existingError [$info ['upcId']['columnIndex'].$info ['upcId']['rowIndex']] = '占位符';
                        $this->errorinfo [][$info ['upcId']['columnIndex'].$info ['upcId']['rowIndex']] = L($this->title [$info ['upcId']['columnIndex']]['en_name'] . '[' .$info ['upcId']['value'] . ']' . '未获取到对应的SKU ID');
                    }
                }
            }
            if (!empty($info['remark']['value']) && mb_strlen($info['remark']['value']) > 200) {
                $this->existingError [$info ['remark']['columnIndex'].$info ['remark']['rowIndex']] = '备注';
                $this->errorinfo [][$info ['remark']['columnIndex'].$info ['remark']['rowIndex']] = L('字数超限，请重新编辑');
            }
            $price = $usdPrice = 0;
            if (empty($info ['purOrderNo']['value'])) {
                // 原始币种单价
                $price = $info ['price']['value'];
                // 采购团队
                $purTeam = $info ['purTeam']['value'];
                // 采购团队公司
                $purTeamCompany = $info ['purTeamCompany']['value'];
                // 销售团队
                $saleTeam = $info ['saleTeam']['value'];
                // 采购发票税率
                $purInvoiceTaxRate = $info ['purInvoiceTaxRate']['value'];
            } else {
                if (isset($this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']])) {
                    // 人民币单价
                    $price = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['unitPrice'];
                    // 采购团队
                    $purTeam = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purTeam'];
                    // 采购团队公司
                    $purTeamCompany = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purTeamCompany'];
                    // 销售团队
                    $saleTeam = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['saleTeam'];
                    // 采购发票税率
                    $purInvoiceTaxRate = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purInvoiceTaxRate']?BaseModel::purInvoiceTaxRate()[$this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purInvoiceTaxRate']] / 100:0;
                    // 商品币种
                    $currency = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency'];
                    // 物流币种
                    $logCurrency = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency'];
                } else {
                    if (!isset($this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']])) {
                        $this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = '占位符';
                        $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' .$info ['purOrderNo']['value'] . ']' . '未获取到相应的采购信息');
                    }
                }
            }
            $info ['price']['value']    = $price;
            $info ['currency']['value'] = $currency;
            $info ['purTeam']['value']  = $purTeam;
            $info ['purTeamCompany']['value'] = $purTeamCompany;
            $info ['saleTeam']['value'] = $saleTeam;
            $info ['purInvoiceTaxRate']['value'] = $purInvoiceTaxRate;
            $info ['logCurrency']['value'] = $logCurrency;
            //batchCode skuId 同时存在时则保存准备判断是否是残次品或现货库存
            if (!empty($info ['batchCode']['value']) && !empty($info ['skuId']['value'])) {
                $batchCode = $info ['batchCode']['value'];
                $sku = $info ['skuId']['value'];
                //根据sku查询出残次品或现货批次
                $batch = M()->table('tb_wms_batch')
                    ->where(['SKU_ID' => $sku, 'vir_type' => ['in', ['N002440100', 'N002440400']]])
                    ->getField('id');
                if (!$batch) {
                    $msg = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' . $sku . ']' . 'SKU信息错误');
                }
                //根据sku&batchCode查询出残次品或现货批次
                $batch = M()->table('tb_wms_batch')
                    ->field('id, SKU_ID, batch_code, vir_type')
                    ->where(['SKU_ID' => $sku, 'batch_code' => $batchCode, 'vir_type' => ['in', ['N002440100', 'N002440400']]])
                    ->find();
                if (!$batch) {
                    $msg = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' . $batchCode . ']' . '批次号信息错误');
                } else {
                    $info ['batchId']['value'] = $batch['id'];
                    $info ['virType']['value'] = $batch['vir_type'];
                }
                if ($msg) $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = $msg;
            }
            unset($info);
        }
        $this->curr = null;

        foreach ($this->data as $index => $info) {
            $billHash = $info['channel']['value']
                . $info ['storageWarehouse']['value']
                . $info ['storageMark']['value']
                . $info ['userId']['value']
                . $info ['billDate']['value']
                . $info ['saleTeam']['value']
                . $info ['purOrderNo']['value']
                . $info ['purTeam']['value'];
            $hash = md5($billHash);
            $price = $usdPrice = 0;
            if (empty($info ['purOrderNo']['value'])) {
                // 人民币单价
                $price = $info ['price']['value']
                    * $this->curr [$info ['purStorageDate']['value']][strtoupper($this->tmpCurr [$info ['currency']['value']])]['CNY'];
                // 美元单价
                $usdPrice = $info ['price']['value']
                    * $this->curr [$info ['purStorageDate']['value']][strtoupper($this->tmpCurr [$info ['currency']['value']])]['USD'];
                // 采购团队
                $purTeam = $info ['purTeam']['value'];
                // 采购团队公司
                $purTeamCompany = $info ['purTeamCompany']['value'];
                // 销售团队
                $saleTeam = $info ['saleTeam']['value'];
                // 采购发票税率
                $purInvoiceTaxRate = $info ['purInvoiceTaxRate']['value'];
            } else {
                if (isset($this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']])) {
                    // 人民币单价
                    $price = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['unitPrice']
                        * $this->curr [$info ['purStorageDate']['value']][$this->tmpCurr[$this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency']]]['CNY'];
                    // 美元单价
                    $usdPrice = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['unitPrice']
                        * $this->curr [$info ['purStorageDate']['value']][$this->tmpCurr[$this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency']]]['USD'];
                    // 采购团队
                    $purTeam = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purTeam'];
                    if (!$purTeam) {
                        if (!isset($this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']])) {
                            $this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = '占位符';
                            $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' .$info ['purOrderNo']['value'] . ']' . '未获取到采购团队信息');
                        }
                    }
                    // 采购团队公司
                    $purTeamCompany = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purTeamCompany'];
                    if (!$purTeamCompany) {
                        if (!isset($this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']])) {
                            $this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = '占位符';
                            $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' .$info ['purOrderNo']['value'] . ']' . '未获取到采购团队所属公司信息');
                        }
                    }
                    // 销售团队
                    $saleTeam = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['saleTeam'];
                    if (!$saleTeam) {
                        if (!isset($this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']])) {
                            $this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = '占位符';
                            $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' .$info ['purOrderNo']['value'] . ']' . '未获取到销售团队信息');
                        }
                    }
                    // 采购发票税率
                    $purInvoiceTaxRate = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purInvoiceTaxRate']?BaseModel::purInvoiceTaxRate()[$this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['purInvoiceTaxRate']] / 100:0;
                    // 商品币种
                    $currency = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency'];
                    // 物流币种
                    $logCurrency = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency'];
                } else {
                    if (!isset($this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']])) {
                        $this->existingError [$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = '占位符';
                        $this->errorinfo [][$info ['purOrderNo']['columnIndex'].$info ['purOrderNo']['rowIndex']] = L($this->title [$info ['purOrderNo']['columnIndex']]['en_name'] . '[' .$info ['purOrderNo']['value'] . ']' . '未获取到相应的采购信息');
                    }
                }
            }
            //Excel中带有batchCode同时是残次品或现货批次的单子追加batchId
            $batchId = isset($info ['batchId']['value']) ? $info ['batchId']['value'] : '';
            $virType = isset($info ['virType']['value']) ? $info ['virType']['value'] : '';
            $this->reqData[] = [
                'operatorId' => $_SESSION['userId'],
                'relationType' => '',
                'billType' => $info ['storageMark']['value'],
                'skuId' => $info ['skuId']['value'],
                'num' => $info ['number']['value'],
                'deliveryWarehouse' => $info ['storageWarehouse']['value'],
                'purchaseOrderNo' => $info ['purOrderNo']['value'],
                'purchaseTeamCode' => $purTeam,
                'saleTeamCode' => $saleTeam,
                'smallSaleTeamCode' => $info['smallSaleTeam']['value'],
                'batchId' => $batchId,
                'virtype' => $virType,
                'batchCode' => $info ['batchCode']['value'],
                'excelName' => session('m_loginname'). '_'. $this->saveName,
                'importRemark' => $info ['remark']['value'],
            ];
        }
    }
    /**
     * 加载EXCEL，生成excel对象
     *
     */
    public function loadExcel()
    {
        $fileModel = new FileUploadModel();
        $this->saveName = $fileModel->fileUploadExtend();
        //$filePath = $_FILES['file']['tmp_name'];
        $filePath = $fileModel->filePath . '/' . $this->saveName;
        $this->excel = new ExcelOperationModel($filePath);


        $this->excel_path = $filePath;
    }

    public function getFirstCellData()
    {
        $currentRow = 1;
        $this->firstCellRowData = $this->getCellData($currentRow);
    }

    public function getCellData($currentRow)
    {
        $base = $index = $point = 'A';
        for ($i = 1; $i <= $this->excel->max_column_int; $i ++) {
            $name [$index] = trim((string)$this->excel->sheet->getCell($index . $currentRow)->getValue());
            $index ++;
            if ($i % 26 == 0) {
                $index = $base . $point;
                $base++;
            }
        }
        return $name;
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '512M');
        $response = array(
            'code' => '2000',
            'info' => L('导入成功'),
            'data' => array(),
        );
        try {
            //加载excel
            $this->loadExcel();
            //获取标题
            $this->getFirstCellData();
            $this->getTitle();
            //数据加载
            $this->getData();
            //数据验证
            $this->processData();
            if ($this->errorinfo){
                $response['code'] = 4005;
                $response['info'] = L('导入失败-1');
                $response['data'] = $this->errorinfo;
                throw new Exception(L('导入失败-1'));
            }
            // 获取基础数据
            $this->basicsData();
            $this->packData();
            if ($this->errorinfo){
                $response['code'] = 4005;
                $response['info'] = L('导入失败-2');
                $response['data'] = $this->errorinfo;
                throw new Exception(L('导入失败-2'));
            }
            $response['data'] = array(
                'datas' => $this->data,
                'reqData' => $this->reqData,
            );
        } catch (\Exception $e) {
            if ($response['code'] != 4005){
                $response['code'] = 4000;
                $response['info'] = $e->getMessage();
                $response['data'] =  $e->getMessage();
            }
        }
        return $response;
    }

    /**
     *获取订单
     */
    public function get_bill_id($e, $get_date = null, $sale_team_nm)
    {
        $Bill = M('bill', 'tb_wms_');
        $date = date("Y-m-d");
        empty($get_date) ? '' : $date = $get_date;
        $where['bill_date'] = $date;

        $max_id = $Bill->where($where)->order('id')->limit(1)->count();
        $type = '';
        switch ($e) {
            case 'N000940100':
                $type = 'CGR';
                break;
            case 'N000940200':
                $type = 'THR';
                break;
            case 'N000941000':
                $type = 'THR';
                break;
            case 'N000940300':
                $type = 'QTR';
                break;
            case 'N000950100':
                $type = 'XSC';
                break;
            case 'N000950200':
                $type = 'BSC';
                break;
            case 'N000950300':
                $type = 'QTC';
                break;
            case 'N000940400':
                $type = 'PYR';
                break;
            case 'N000950400':
                $type = 'PKC';
                break;
            case 'N000950600':
                $type = 'DB';
                break;
            case 'N000950500':
                $type = 'DB';
                break;
            case 'N000950700':
                $type = 'TZKC';
                break;
            case 'N000950905': // from 9766,业务逻辑与【调整库存】N000950700相同
                $type = 'TZKC';
                break;
            case 'N000950906':
                $type = 'TZKC';
                break;
        }
        $date = date("Ymd");
        empty($get_date) ? '' : $date = date("Ymd", strtotime($get_date));
        $date = substr($date, 2);
        $wrate_id = $max_id + 1;
        $w_len = strlen($wrate_id);
        $b_id = '';
        if ($w_len < 4) {
            for ($i = 0; $i < 4 - $w_len; $i++) {
                $b_id .= '0';
            }
        }
        return $type . $sale_team_nm . $date . $b_id . $wrate_id;
    }

    public function writeData()
    {
        $user_name = session('m_loginname');
        $excel_name = $user_name. '_'. $this->saveName;

        $model = new Model();
        $model->startTrans();
        // bill写表获取billId
        foreach ($this->bill as $hash => $billData) {
            $billData['excel_name'] = $excel_name;//保存excel文件名
            $billId = $model->table('tb_wms_bill')->data($billData)->add();
            if ($billId) {
                foreach ($this->stream [$hash] as $key => &$stream) {
                    $stream ['bill_id'] = $billId;
                }
            } else {
                $model->rollback();
                throw new Exception($model->getDbError(), '3000');
            }
        }
        //stream写表
        foreach ($this->stream as $hash => $streamData) {
            foreach ($streamData as $key => $data) {
                $export = [
                    'skuId' => $data ['GSKU'],
                    'saleTeamCode' => $this->bill [$hash]['SALE_TEAM'],
                    'deliveryWarehouse' => $this->bill [$hash]['warehouse_id'],
                    'num' => (int)($data ['send_num']),
                    'purchaseOrderNo' => $this->bill [$hash]['procurement_number'],
                    'purchaseTeamCode' => $this->bill [$hash]['SP_TEAM_CD'],
                    'operatorId' => $_SESSION['user_id'],
                    'billId' => $data ['bill_id'],
                    'lineNumber' => (int)$data ['line_number'],
                    'batchCode' => $data ['batchCode'],
                    'orderId' => $this->bill [$hash]['bill_id'],

                ];
                $outStorageData [] = $export;
            }
        }
        $requestData = [
            'processCode' => 'EXCEL_EXPORT',
            'processId' => create_guid(),
            'data' => $outStorageData
        ];
        $requestModel = new ImportRequestModel();
        $requestModel->outStorage($requestData);
        if ($requestModel->getResponse()->code != 2000) {
            $model->rollback();
            throw new Exception($requestModel->getResponse()->msg, $requestModel->getResponse()->code);
        } else {
            $model->commit();
            // 生成出库子单
            $requestModel->outgoing($requestModel->getResponseData());
        }
    }
}