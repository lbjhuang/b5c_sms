<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/9/20
 * Time: 13:44
 */

class ExcelImportBillModel extends BaseImportExcelModel
{
    protected $autoCheckFields = false;

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
                'storageWarehouse' => ['field_name' => '入库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => false],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队CODE', 'required' => false],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => true],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => true],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => true],
                'storageDate' => ['field_name' => '入库时间', 'required' => true],//19
                'storageMark' => ['field_name' => '入库原因CODE', 'required' => true],//20
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//21
                'remark' => ['field_name' => '备注', 'required' => true]//22 10766 调拨单待提交页面更改调拨商品 需求补充备注必填 20201126


            ],
            'purOrderNoEmpty' => [
                'skuId' => ['field_name' => '商品编码', 'required' => false],//1
                'upcId' => ['field_name' => '条形码', 'required' => false],//2
                'number' => ['field_name' => '数量', 'required' => true],//3
                'price' => ['field_name' => '单价', 'required' => true],//4
                'currency' => ['field_name' => '币种', 'required' => true],//5
                'money' => ['field_name' => '金额', 'required' => true],//6
                'deadLine' => ['field_name' => '到期日', 'required' => false],//7
                'storageWarehouse' => ['field_name' => '入库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => true],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队CODE', 'required' => true],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => false],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => false],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => true],
                'storageDate' => ['field_name' => '入库时间', 'required' => true],//19
                'storageMark' => ['field_name' => '入库原因CODE', 'required' => true],//20
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//21
                'remark' => ['field_name' => '备注', 'required' => true]//22 10766 调拨单待提交页面更改调拨商品 需求补充备注必填 20201126

            ],
            'skuIdEmpty' => [
                'skuId' => ['field_name' => '商品编码', 'required' => false],//1
                'upcId' => ['field_name' => '条形码', 'required' => true],//2
                'number' => ['field_name' => '数量', 'required' => true],//3
                'price' => ['field_name' => '单价', 'required' => false],//4
                'currency' => ['field_name' => '币种', 'required' => false],//5
                'money' => ['field_name' => '金额', 'required' => false],//6
                'deadLine' => ['field_name' => '到期日', 'required' => false],//7
                'storageWarehouse' => ['field_name' => '入库仓库CODE', 'required' => true],//8
                'purTeam' => ['field_name' => '采购团队CODE', 'required' => false],//9
                'purTeamCompany' => ['field_name' => '采购团队所属公司CODE', 'required' => false],//10
                'saleTeam' => ['field_name' => '销售团队', 'required' => false],//11
                'purOrderNo' => ['field_name' => '采购单号', 'required' => false],//12
                'introTeam' => ['field_name' => '介绍团队CODE', 'required' => false],//13
                'introType' => ['field_name' => '介绍团队类型CODE', 'required' => false],//14
                'purInvoiceTaxRate' => ['field_name' => '采购发票税率', 'required' => false],//15
                'proportionOfTax' => ['field_name' => '退税比率', 'required' => false],//16
                'storageLogCost' => ['field_name' => '入库物流费用单价', 'required' => false],//17
                'logServiceCost' => ['field_name' => '物流服务费用单价', 'required' => false],//18
                'purStorageDate' => ['field_name' => '采购入库时间', 'required' => true],
                'storageDate' => ['field_name' => '入库时间', 'required' => true],//19
                'storageMark' => ['field_name' => '入库原因CODE', 'required' => true],//20
                'smallSaleTeam' => ['field_name' => '销售小团队CODE', 'required' => false],//21
                'remark' => ['field_name' => '备注', 'required' => true]//22 10766 调拨单待提交页面更改调拨商品 需求补充备注必填 20201126
            ],
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
     * 仓库CODE-N00068-warehouse
     * 采购团队CODE-N00129-purTeam
     * 介绍团队CODE-N00129|N00128-introTeam
     * 介绍团队类型CODE-N002460-purTeamType
     * 采购团队公司CODE-N00124-company
     * 入库原因CODE-N000940-storageMark
     * 销售小团队CODE-N00323-smallSaleTeam
     * 销售小团队和销售团队映射CODE-N00323-smallSaleTeamRelationship
     */
    public function baseCode()
    {
        if (static::$baseCode) {
            return static::$baseCode;
        }

        $model = new Model();
        static::$baseCode ['company']     = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00124%"')->getField('CD, CD_VAL');
        static::$baseCode ['warehouse']   = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00068%" and USE_YN = "Y"')->getField('CD, CD_VAL');
        static::$baseCode ['purTeamType'] = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N002460%"')->getField('CD, CD_VAL');
        static::$baseCode ['introTeam']   = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00128%" OR CD LIKE "N00129"')->getField('CD, CD_VAL');
        static::$baseCode ['purTeam']     = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00129%"')->getField('CD, CD_VAL');
        static::$baseCode ['saleTeam']    = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00128%"')->getField('CD, CD_VAL');
        static::$baseCode ['storageMark'] = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N000940%"')->getField('CD, CD_VAL');
        static::$baseCode ['smallSaleTeam'] = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00323%"')->getField('CD, CD_VAL');
        static::$baseCode ['smallSaleTeamRelationship'] = $model->table('tb_ms_cmn_cd')->where('CD LIKE "N00323%"')->getField('CD, ETC');

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
                if ($this->title [$k]['db_field'] == 'introType' and $this->title [$k]['required'] == true) {
                    $flag = true;
                }
            }
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
                $flag = false;
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

        // 针对小团队是否对应销售团队特殊处理
        $columnIndex = $saleTeam = $smallSaleTeam = '';
        $is_check_small = false;
        foreach ($val as $fieldName => $data) {
            if ($fieldName === 'saleTeam') {
                $saleTeam = $data['value'];
            }
            if ($fieldName === 'smallSaleTeam') {
                $smallSaleTeam = $data['value'];
                $columnIndex = $data['columnIndex'];
                $is_check_small = true;
            }
        }
        if (!$is_check_small) {
            $this->errorinfo [][$rowIndex.$columnIndex] = L('缺失销售小团队字段，请确认是否为最新模板，建议重新下载最新模板');
        }
        if ($smallSaleTeam) {
            if (isset(static::$baseCode ['smallSaleTeamRelationship'][$smallSaleTeam]) && static::$baseCode ['smallSaleTeamRelationship'][$smallSaleTeam] !== $saleTeam) {
                $this->errorinfo [][$rowIndex.$columnIndex] = L('不是有效的销售小团队CODE，没有找到对应映射的销售团队');
            }
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

    public function validExtend($row_index, $column_index, $value, $fieldName)
    {
        $db_field = $this->title [$column_index]['db_field'];//重写该方法的时候，必须保留这一句
        $pos = $row_index.$column_index;
        $flag = false;
        // 必填验证，或填了不满足规范的值
        if (($this->title [$column_index]['required'] and $value === '') or ($value !== '')) {
            if ($this->title [$column_index]['required'] and $value != '') {
                if ($fieldName == 'introType') {
                    $flag = true;
                }
            }
            if ($this->title [$column_index]['required'] and $value === '') {
                if (!isset($this->existingError [$pos])) {
                    $this->existingError [$pos] = L($this->title [$column_index]['en_name'] . '必填');
                    $this->errorinfo [][$pos] = L($this->title [$column_index]['en_name'] . '必填');
                }
            } else {
                if (!isset($this->existingError [$pos])) {
                    $this->existingError [$pos] = '占位符';
                    switch ($fieldName) {
                        case 'storageWarehouse':
                            isset(static::$baseCode ['warehouse'][$value]) or $this->errorinfo [][$pos] = L('不是有效的仓库CODE或仓库未启用');
                            break;
                        case 'introTeam':
                            isset(static::$baseCode ['introTeam'][$value]) or $this->errorinfo [][$pos] = L('不是有效的介绍团队CODE');
                            break;
                        case 'introType':
                            if ($flag == true) {
                                isset(static::$baseCode ['purTeamType'][$value]) or $this->errorinfo [][$pos] = L('不是有效的介绍团队类型CODE');
                            }
                            break;
                        case 'purTeamCompany':
                            isset(static::$baseCode ['company'][$value]) or $this->errorinfo [][$pos] = L('不是有效的采购团队所属公司CODE');
                            break;
                        case 'purTeam':
                            isset(static::$baseCode ['purTeam'][$value]) or $this->errorinfo [][$pos] = L('不是有效的采购团队CODE');
                            break;
                        case 'storageMark':
                            isset(static::$baseCode ['storageMark'][$value]) or $this->errorinfo [][$pos] = L('不是有效的入库原因CODE');
                            break;
                        case 'saleTeam':
                            isset(static::$baseCode ['saleTeam'][$value]) or $this->errorinfo [][$pos] = L('不是有效的销售团队CODE');
                            break;
                        case 'smallSaleTeam':
                            isset(static::$baseCode ['smallSaleTeam'][$value]) or $this->errorinfo [][$pos] = L('不是有效的销售小团队CODE');
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
                            $isInteger2($value) or $this->errorinfo [][$pos] = L('请填写有效的数量（整数类型）');
                            break;
                        case 'purInvoiceTaxRate':
                            ($value >= 0 && is_numeric($value)) or $this->errorinfo [][$pos] = L('请填写有效的采购发票税率（整数或小数类型）');
                            break;
                        case 'proportionOfTax':
                            ($value >= 0 && is_numeric($value)) or $this->errorinfo [][$pos] = L('请填写有效的退税比率（整数或小数类型）');
                            break;
                        case 'storageLogCost':
                            is_numeric($value) or $this->errorinfo [][$pos] = L('请填写有效的入库物流费用单价（整数或小数类型）');
                            break;
                        case 'logServiceCost':
                            is_numeric($value) or $this->errorinfo [][$pos] = L('请填写有效的物流服务费用单价（整数或小数类型）');
                            break;
                        case 'price':
                            is_numeric($value) or $this->errorinfo [][$pos] = L('请填写有效的单价（整数或小数类型）');
                            break;
                        case 'purStorageDate':
                            $this->validateDate($value) or $this->errorinfo [][$pos] = L('请填写有效的采购入库时间');
                            break;
                        case 'storageDate':
                            $this->validateDate($value) or $this->errorinfo [][$pos] = L('请填写有效的入库时间');
                            break;
                        case 'deadLine':
                            $this->validateDate($value) or $this->errorinfo [][$pos] = L('请填写有效的到期日');
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

    /**接口调用数据
     * @var
     */
    public $reqData;

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
        // 数据补填
        foreach ($this->data as $index => &$info) {
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
            if (empty($info['purInvoiceTaxRate']['value']) && $info['purInvoiceTaxRate']['value'] == '') {
                $this->existingError [$info ['purInvoiceTaxRate']['columnIndex'].$info ['purInvoiceTaxRate']['rowIndex']] = '占位符';
                $this->errorinfo [][$info ['purInvoiceTaxRate']['columnIndex'].$info ['purInvoiceTaxRate']['rowIndex']] = L('采购发票税率必填');
            }
            if (empty($info['proportionOfTax']['value']) && $info['proportionOfTax']['value'] == '') {
                $this->existingError [$info ['proportionOfTax']['columnIndex'].$info ['proportionOfTax']['rowIndex']] = '占位符';
                $this->errorinfo [][$info ['proportionOfTax']['columnIndex'].$info ['proportionOfTax']['rowIndex']] = L('退税比率必填');
            }
            if (empty($info ['purOrderNo']['value'])) {
                if (empty($info ['purTeamCompany']['value'])) {
                    if (!isset($this->existingError [$info ['purTeamCompany']['columnIndex'].$info ['purTeamCompany']['rowIndex']])) {
                        $this->existingError [$info ['purTeamCompany']['columnIndex'].$info ['purTeamCompany']['rowIndex']] = '占位符';
                        $this->errorinfo [][$info ['purTeamCompany']['columnIndex'].$info ['purTeamCompany']['rowIndex']] = L( '未填写采购单号时采购团队所属公司必填');
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
                // EXCEL 导入时如果没有采购单号，则物流费用币种与商品单价币种相同
                $currency = $logCurrency = $flipCurrency [$info ['currency']['value']];
            } else {
                if (isset($this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']])) {
                    // 人民币单价
                    $price = $this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['unitPrice'];
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
            $info ['price']['value']    = $price;
            $info ['currency']['value'] = $currency;
            $info ['purTeam']['value']  = $purTeam;
            $info ['purTeamCompany']['value'] = $purTeamCompany;
            $info ['saleTeam']['value'] = $saleTeam;
            $info ['purInvoiceTaxRate']['value'] = $purInvoiceTaxRate;
            $info ['logCurrency']['value'] = $logCurrency;
            unset($info);
        }

        $this->curr = array_column($this->data, 'currency');
        // 获取汇率
        if ($this->purStorageDate and $this->curr) {
            $temp = [];
            foreach ($this->purStorageDate as $key => $value) {
                if (isset($temp [$value ['value']] [strtoupper($this->tmpCurr[$this->curr [$key]['value']])])) {
                    continue;
                }
                $ret = BaseModel::exchangeRate($this->curr [$key]['value'], $value ['value']);
                $temp [$value ['value']] [strtoupper($this->tmpCurr[$this->curr [$key]['value']])] = [
                    'CNY' => $ret ['cny'],
                    'USD' => $ret ['usd']
                ];
                if (!$ret ['cny'] or !$ret ['usd']) {
                    $this->errorinfo [][$this->curr [$key] ['rowIndex'] . $this->curr [$key] ['columnIndex']] = L('币种：') . strtoupper($this->tmpCurr[$this->curr [$key]['value']]) . L('获取') . $value ['value'] . L('日的汇率失败');
                }
            }
            $this->curr = $temp;
        }
        foreach ($this->data as $index => $info) {
            // SKU 不存在的情况
            $billHash = $info['channel']['value']
                . $info ['storageWarehouse']['value']
                . $info ['storageMark']['value']
                . $info ['userId']['value']
                . $info ['billDate']['value']
                . $info ['saleTeam']['value']
                . $info ['purOrderNo']['value']
                . $info ['purTeam']['value'];
            $hash = md5($billHash);
            $price = $usdPrice = $usdRate = 0;
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
                // EXCEL 导入时如果没有采购单号，则物流费用币种与商品单价币种相同
                if (isset($this->tmpCurr [$info ['currency']['value']])) {
                    $currency = $logCurrency = $info ['currency']['value'];
                } else {
                    $this->errorinfo [][$info ['currency']['columnIndex'].$info ['currency']['rowIndex']] = L($this->title [$info ['currency']['columnIndex']]['en_name'] . '[' .$info ['currency']['value'] . ']' . '未获取到相对应的币种CODE');
                }
                $usdRate = $this->curr [$info ['purStorageDate']['value']][strtoupper($this->tmpCurr [$info ['currency']['value']])]['USD'];
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
                $usdRate = $this->curr [$info ['purStorageDate']['value']][$this->tmpCurr[$this->purOrderInfo [$info ['purOrderNo']['value'] . $info ['skuId']['value']]['currency']]]['USD'];
            }
            $this->bill [$hash] = [
                'warehouse_id'   => $info ['storageWarehouse']['value'],
                'bill_type'      => $info ['storageMark']['value'],
                'CON_COMPANY_CD' => $purTeamCompany,
                'SALE_TEAM'      => $saleTeam,
                //'SMALL_SALE_TEAM'=> $info ['smallSaleTeam']['value'],
                'bill_state'     => $info ['billState']['value'],
                'user_id'        => $info ['userId']['value'],
                'zd_user'        => $info ['zdUser']['value'],
                'zd_date'        => $info ['zdDate']['value'],
                'bill_date'      => $info ['billDate']['value'],
                'bill_id'        => $this->get_bill_id($info ['storageMark']['value'], $info ['billDate']['value']),
                'channel'        => $info ['channel']['value'],
                'procurement_number' => $info ['purOrderNo']['value'],
                'SP_TEAM_CD'     => $purTeam,
                'vir_type'       => $info ['virType']['value'],
                'intro_team'     => $info ['introTeam']['value'],
                'intro_team_type'=> $info ['introType']['value'],
                'type'           => 1,
            ];
            $this->stream [$hash][] = [
                'GSKU'                  => $info ['skuId']['value'],
                'small_sale_team'       => $info ['smallSaleTeam']['value'],
                //'GUDS_OPT_ID'           => $info ['upcId']['value'],
                'deadline_date_for_use' => $info ['deadLine']['value'],
                'add_time'              => $info ['storageDate']['value'],
                'currency_time'         => date('Y-m-d', strtotime($info ['purStorageDate']['value'])),
                'currency_id'           => $currency,//币种
                'log_currency'          => $logCurrency,
                'should_num'            => $info ['number']['value'],
                'send_num'              => $info ['number']['value'],
                'unit_price'            => $price,// 单价
                'unit_price_usd'        => $usdPrice,//含税美元单价
                'price'                 => $info ['price']['value'],//表格填写的原始单价
                'unit_money'            => $price * $info ['number']['value'],
                'line_number'           => $index,
                'create_time'           => date('Y-m-d H:i:s', time()),
                'pur_invoice_tax_rate'  => $purInvoiceTaxRate,//采购发票税率
                'proportion_of_tax'     => $info ['proportionOfTax']['value']?$info ['proportionOfTax']['value']:0,//退税比率
                'storage_log_cost'      => $info ['storageLogCost']['value'] * $usdRate,
                'log_service_cost'      => $info ['logServiceCost']['value'] * $usdRate,
                'pur_storage_date'      => $info ['purStorageDate']['value'],
                'carry_cost'            => $info ['storageLogCost']['value'] * $usdRate,
                'log_service_cost_currency' => $currency,
                'all_storage_log_cost'  => $info ['storageLogCost']['value'] * $usdRate,
                'all_log_service_cost'  => $info ['logServiceCost']['value'] * $usdRate,
                'all_carry_cost'        => $info ['storageLogCost']['value'] * $usdRate,
                'remark'                => $info['remark']['value']
            ];
            
            unset($billHash);
        }
        $excel_name = session('m_loginname'). '_'. $this->saveName;
        //请求数据组装
        foreach ($this->bill as $k => $v) {
            $bill['bill'] = [
                'billType' => $v['bill_type'],
                'relationType' => '',
                'warehouseId' => $v['warehouse_id'],
                'saleTeam' => $v['SALE_TEAM'],
                //'smallSaleTeamCode' => $v['SMALL_SALE_TEAM'],
                'virType' => $v['vir_type'],
                'processOnWay' => 0,
                'operatorId' => $_SESSION['userId'],
                'linkBillId' => '',
                'type' => $v['type'],
                'procurementNumber' => $v['procurement_number'],
                'saleNo' => $v['procurement_number'],
                'supplier' => '',
                'spTeamCd' => $v['SP_TEAM_CD'],
                'conCompanyCd' => $v['CON_COMPANY_CD'],
                'warehouseRule' => '',
                'orderId' => guid(),
                'purchaseOrderNo' => $v['procurement_number'],
                'channel' => $v['channel'],
                'billState' => $v['bill_state'],
                'introTeam' => $v['intro_team'],
                'introTeamType' => $v['intro_team_type'],
                'excelName' => $excel_name,
            ];
            $bill['guds'] = [];
            foreach ($this->stream [$k] as $vv) {
                $bill['guds'][] = [
                    'gudsId' => SkuModel::getSpuId($vv['GSKU']),
                    'smallSaleTeamCode' => $vv['small_sale_team'],
                    'skuId' => $vv['GSKU'],
                    'num' => $vv['send_num'],
                    'brokenNum' => 0,
                    'inStorageTime' => $vv['add_time'],
                    'deadlineDateForUse' => $vv['deadline_date_for_use'],
                    'purStorageDate' => $vv['pur_storage_date'],
                    'currencyId' => $vv['currency_id'],
                    'price' => $vv ['price'],//表格填写的原始单价
                    'currencyTime' => $vv['currency_time'] . ' 00:00:00',
                    'unitPrice' => $vv['unit_price'],
                    'unitPriceUsd' => $vv['unit_price_usd'],
                    'storageLogCost' => $vv['storage_log_cost'],
                    'logServiceCost' => $vv['log_service_cost'],
                    'carryCost' => $vv['carry_cost'],
                    'allStorageLogCost' => $vv['all_storage_log_cost'],
                    'allLogServiceCost' => $vv['all_log_service_cost'],
                    'allCarryCost' => $vv['all_carry_cost'],
                    'logCurrency' => 'N000590100',
                    'logServiceCostCurrency' => 'N000590100',
                    'purInvoiceTaxRate' => $vv['pur_invoice_tax_rate'],
                    'proportionOfTax' => $vv['proportion_of_tax'],
                    'poCurrency' => '',
                    'poCost' => '',
                    'channel' => $bill['channel'],
                    'channelSkuId' => 0,
                    'importRemark' => $vv['remark'],
                ];
            }
            $this->reqData[] = $bill;
        }
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        $this->basicsData();
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '512M');
        parent::import();
        if ($this->errorinfo) {
            $response ['code'] = 3000;
            $response ['info'] = L('导入失败');
            $response ['data'] = $this->errorinfo;
        } else {
            // 获取基础数据
            $this->basicsData();
            $this->packData();
            if ($this->errorinfo) {
                $response ['code'] = 3000;
                $response ['info'] = L('导入失败');
                $response ['data'] = $this->errorinfo;
            } else {
                $res = (new WmsModel())->xlsInStorage($this->reqData);
                if (!$res || $res['code'] != 2000) {
                    $response ['code'] = $res['code'];
                    $response ['info'] = L($res['msg']);
                    $response ['data'] = $res['data'];
                } else {
                    $response ['code'] = 2000;
                    $response ['info'] = L('成功导入：') .($this->excel->max_row - 1). L('条');
                    $response ['data'] = null;
                }
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

    /**
     * @var 出入库物流成本流水表
     */
    public $streamCostLogData = null;

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
            $tmp_header = $tmp_body = null;
            foreach ($streamData as $key => $data) {
                $streamId = $model->table('tb_wms_stream')->data($data)->add();
                // 在上面的逻辑中，已经将其全部转换为了美元单价
                $this->streamCostLogData [] = [
                    'stream_id' => $streamId,
                    'log_service_cost' => $data ['log_service_cost'],
                    'storage_log_cost' => $data ['storage_log_cost'],
                    'carry_cost' => $data ['carry_cost'],
                    'all_log_service_cost' => $data ['all_log_service_cost'],
                    'all_storage_log_cost' => $data ['all_storage_log_cost'],
                    'all_carry_cost' => $data ['all_carry_cost'],
                    'currency_id' => 'N000590100', // 美元币种
                    'pur_storage_date' => $data ['pur_storage_date'], // 转换日期
                    'po_cost' => 0,
                    'all_po_cost' => 0
                ];
                if (!$streamId) {
                    $model->rollback();
                    throw new Exception($model->getDbError(), '3000');
                }
                $tmp_header = [
                    'billId'   => $data ['bill_id'],
                    'lockCode' => create_guid(),
                    'type'     => 0,
                    'data'     => []
                ];
                $tmp_body [] = [
                    'gudsId'             => substr($data ['GSKU'], 0, 8),
                    'skuId'              => $data ['GSKU'],
                    'originalStorageTime'=> $data ['pur_storage_date'],
                    'inStorageTime'      => $data ['add_time'],
                    'num'                => $data ['send_num'],
                    'deadlineDateForUse' => $data ['deadline_date_for_use'],
                    'operatorId'         => $_SESSION['userId'],
                    'purchaseOrderNo'    => (string)$this->bill [$hash]['procurement_number'],
                    'purchaseTeamCode'   => $this->bill [$hash]['SP_TEAM_CD'],
                    'channel'            => $this->bill [$hash]['channel'],
                    'ChannelSkuId'       => 0,
                    'streamId'           => $streamId,
                    'saleTeamCode'       => $this->bill [$hash]['SALE_TEAM'],
                    'deliveryWarehouse'  => $this->bill [$hash]['warehouse_id'],
                    'virType'            => 'N002440100',
                    'processOnWay'       => 0
                ];
            }
            $tmp_header ['data'] = $tmp_body;
            $requestData['data']['batch'][] = $tmp_header;
        }
        $requestModel = new ImportRequestModel();
        $requestModel->inStorage($requestData);
        if ($requestModel->getResponse()->code != 2000) {
            $model->rollback();
            throw new Exception($requestModel->getResponse()->msg, $requestModel->getResponse()->code);
        } else {
            $model->commit();
            // 更新费用到出入库物流成本流水表
            $costLogModel = new TbWmsStreamCostLogModel();
            $costLogModel->addRecording($this->streamCostLogData);
            // 得到需要更新的批次与stream,bill相关联的信息
            $tmp = null;
            foreach ($requestModel->getResponseData()->batch as $key => $batchAll) {
                foreach ($batchAll->data as $k => $batch) {
                    $tmp [$batch->id] = [
                        'streamId' => $batch->streamId,
                        'billId'   => $batch->billId
                    ];
                }
            }
            if ($tmp) {
                $m = new Model();
                foreach ($tmp as $batchId => $value) {
                    $m->table('tb_wms_stream')->where(['id' => ['eq', $value ['streamId']], 'bill_id' => ['eq', $value ['billId']]])->save(['batch' => $batchId]);
                }
            }
        }
    }
}

class ImportRequestModel extends AbstractRequestModel
{
    /**
     * 入库
     * @param mixed $requestData 请求数据
     */
    public function inStorage($requestData)
    {
        $requestUri = 'batch/update_total.json';

        $this->submitRequest($requestUri, $requestData);
    }
}