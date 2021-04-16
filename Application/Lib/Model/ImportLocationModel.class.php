<?php

/**
 * 仓库货位
 *
 */
class ImportLocationModel extends BaseImportExcelModel
{

    protected $trueTableName = 'tb_wms_location_sku';
    private $_warehouseInfo;
    private $_updateData;
    private $_insertData;
    private $_locationData;
    private $_locationWriteData;

    protected $_auto = [
//        ['CREATE_TIME', 'getTime', Model::MODEL_INSERT, 'callback'],
//        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
//        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
//        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback'],
//        ['CON_STAT', '1', Model::MODEL_INSERT],
//        ['CRM_CON_TYPE', 1, Model::MODEL_INSERT],
    ];

    public function fieldMapping()
    {
        return [
            'warehouse_name'          => ['field_name' => L('仓库名称'), 'required' => true],
            'warehouse_code'          => ['field_name' => L('仓库CODE值'), 'required' => true],
            'sku'                     => ['field_name' => L('SKU编码'), 'required' => true],
            'location_code'           => ['field_name' => L('正品货位编码'), 'required' => false],
            'location_code_back'      => ['field_name' => L('备用货位编码'), 'required' => false],
            'defective_location_code' => ['field_name' => L('残次品货位编码'), 'required' => false],
        ];
    }

    /**
     * 校验是否不能为空
     * @param string $row_index 行坐标
     * @param string $column_index 列坐标
     * @param $value 值
     * @return null
     */
    public function valid($row_index, $column_index, $value)
    {
        if ($this->title [$column_index]['required'] and empty($value))
            $this->record($row_index, $this->title [$column_index]['en_name'] . '('.L('必填').')');
    }

    /**
     * 代码层数据过滤
     * part1: 通过仓库组装各个仓库相关的数据
     * part2: SKU、货位编码、唯一性验证。备用货位与货位编码唯一性验证
     * part3: 写入错误日志
     */
    public function filterCodeData()
    {
        $this->_warehouseInfo = [];
        $warehouseColIdx = $skuColIdx = $locationColIdx = $locationBackColIdx = $uniqueError = $defectiveLocationColIdx = null;
        foreach ($this->title as $k => $v) {
            switch ($v ['db_field']) {
                case 'warehouse_code':
                    $warehouseColIdx = $k;
                    break;
                case 'sku':
                    $skuColIdx = $k;
                    break;
                case 'location_code':
                    $locationColIdx = $k;
                    break;
                case 'location_code_back':
                    $locationBackColIdx = $k;
                    break;
                case 'defective_location_code':
                    $defectiveLocationColIdx = $k;
                    break;
            }
        }
        foreach ($this->data as $rowIndex => &$value) {
            $value ['id'] = false;
            $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['sku'][$rowIndex] = $value [$skuColIdx]['value'];
            if ($value [$locationColIdx]['value'])
                $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['location_code'][$rowIndex] = $value [$locationColIdx]['value'];
            if ($value [$locationBackColIdx]['value'])
                $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['location_code_back'][$rowIndex] = $value [$locationBackColIdx]['value'];
            if ($value [$defectiveLocationColIdx]['value'])
                $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['defective_location_code'][$rowIndex] = $value [$defectiveLocationColIdx]['value'];
            $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['location_code_b'][$rowIndex]           = $value [$locationColIdx]['value'];
            $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['location_code_back_b'][$rowIndex]      = $value [$locationBackColIdx]['value'];
            $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['defective_location_code_b'][$rowIndex] = $value [$defectiveLocationColIdx]['value'];
            $this->_warehouseInfo [$value [$warehouseColIdx]['value']]['rows'][]                               = $rowIndex;
            unset($value);
        }
        // 关闭 location_code 货位唯一验证
        //$needUniqueField = ['sku', 'location_code'];
        $needUniqueField = ['sku'];
        // start filter
        // 2018-1-9 新逻辑，增加同一仓库，同一 SKU 可以存放在多个货位
        foreach ($this->_warehouseInfo as $warehouseCode => $info) {
//            if ($exi = array_intersect($info ['location_code_back'], $info ['location_code']))
//                $uniqueError [$warehouseCode]['location_code_back'] = $exi;
            foreach ($info as $columnKey => $columnValue) {
                if (in_array($columnKey, $needUniqueField)) {
                    $uniqueValue = array_unique($columnValue);
                    if (count($columnValue) != count($uniqueValue)) {
                        $ret = array_diff_assoc($columnValue, $uniqueValue);
                        if ($ret)
                            $uniqueError [$warehouseCode][$columnKey] = $ret;
                    }
                }
            }
        }
        // error process
        if ($uniqueError) {
            $columnMap = $this->fieldMapping();
            foreach ($uniqueError as $warehouseCode => $info) {
                foreach ($info as $columnName => $value) {
                    foreach ($value as $rowIndex => $v) {
                        if ($columnName == 'location_code_back')
                            $this->record($rowIndex, $columnMap [$columnName]['field_name'] . L('与货位编码重复(文件中重复)'));
                        else
                            $this->record($rowIndex, $columnMap [$columnName]['field_name'] . L('重复(文件中重复)'));
                    }
                }
            }
        }
    }

    /**
     * DB 层数据过滤
     *
     */
    public function filterDbData()
    {
        $warehouseCodes = [];
        foreach ($this->_warehouseInfo as $k => $value) {
            $warehouseCodes [] = $k;
        }
        $error = [];
        if ($warehouseCodes) {
            $ret = $this->getExistedWarehouseInfo($warehouseCodes);
            if ($ret) {
                foreach ($ret as $key => $value) {
                    $warehouseInfo [$value ['warehouse_id']] [] = $value;
                }
                foreach ($warehouseInfo as $warehouseId => $info) {
                    $tmp = [];
                    foreach ($info as $key => $v) {
                        $tmp [$v ['sku']] = $v;
                    }
                    $exportData     = $this->_warehouseInfo [$this->warehouseCodeId [$warehouseId]];
                    $dbSku          = array_column($info, 'sku');
                    $exSku          = $exportData ['sku'];
                    $dbLocation     = array_column($info, 'location_code');
                    $exLocation     = $exportData ['location_code'];
                    $exLocationBack = $exportData ['location_code_back'];
                    if ($sectSku = array_intersect($exSku, $dbSku)) {
                        foreach ($sectSku as $rowIndex => $value) {
                            $this->data [$rowIndex]['id'] = $tmp [$value]['id'];
                            //if ($exportData ['location_code_b'][$rowIndex] != '' and $tmp [$value]['location_code'] != '') {
                                //$error ['sku'][$rowIndex] = $value;
                            //} else {
                            //   continue;
                            //}
                        }
                    }
                    $dbLocation = array_unique($dbLocation);
                    $exLocation = array_unique($exLocation);
                    if (array_diff($exLocation, $dbLocation)) {
                        $this->_locationWriteData [$warehouseId] = array_diff($exLocation, $dbLocation);
                    }
                    //$error ['location_code'] = array_intersect($exLocation, $dbLocation);
//                    $error ['location_code_back'] = array_intersect($exLocationBack, $dbLocation);//取消备用货位和正品货位编码不能一样判断
                }
            }
        }
        if ($error) {
            $columnMap = $this->fieldMapping();
            foreach ($error as $field => $info) {
                foreach ($info as $rowIndex => $value) {
                    if ($field == 'location_code_back')
                        $this->record($rowIndex, $columnMap [$field]['field_name'] . L('与货位编码重复(数据库中重复)'));
                    else {
                        $this->record($rowIndex, $columnMap [$field]['field_name'] . L('重复(数据库中重复)'));
                    }
                }
            }
        }
    }

    /**
     * 获取已存在仓库信息
     * @param array $warehouseCodes 仓库编码
     * @return array $ret
     */
    public function getExistedWarehouseInfo($warehouseCodes)
    {
        $warehouseIds = $this->transWarehouseCodes($warehouseCodes);
        // 过滤仓库
        foreach ($warehouseCodes as $key => $warehouseCode) {
            if (empty($warehouseIds [$warehouseCode])) {
                foreach ($this->_warehouseInfo [$warehouseCode]['rows'] as $k => $idx) {
                    $this->record($idx, L('仓库编码相关联仓库不存在'));
                }
            }
        }
        // 如果仓库 sku 无相关数据，则只需要修改重复项即可写入数据库
        $ret = $this->field(['sku', 'warehouse_id', 'location_code', 'location_code_back', 'id'])
            ->where(['warehouse_id' => ['in', $warehouseIds]])
            ->select();
        return $ret;
    }

    public $warehouseCodeId = [];
    /**
     * 仓库 CODE 码转仓库 ID
     * @param array $warehouseCodes 仓库编码
     * @return array $ret 仓库编码相关信息
     */
    public function transWarehouseCodes($warehouseCodes)
    {
        $model = new Model();
        $ret = $model->table('tb_wms_warehouse')
            ->where(['CD' => ['in', $warehouseCodes]])
            ->getField('CD, id');
        $this->warehouseCodeId = array_flip($ret);
        return $ret;
    }

    /**
     * 数据打包
     * $data 为要写入数据库的数据
     * $update 为要更新数据库的数据
     * $location 为要写入货位表的数据
     */
    public function packData()
    {
        $data = [];
        foreach ($this->data as $index => $info) {
            $temp = [];
            foreach ($info as $key => $value) {
                if ($value) {
                    if ($value ['db_field'] == 'warehouse_code') {
                        $temp ['warehouse_id'] = array_search($value ['value'], $this->warehouseCodeId);
                    } elseif ($value ['db_field'] == 'warehouse_name') {
                        continue;
                    } elseif ($key == 'id') {
                        $temp [$key] = $value;
                    } else {
                        $temp [$value ['db_field']] = $value ['value'];
                    }
                }
            }
            if ($info ['id'] === false) {
                $data [] = $temp;
            } else {
                $update[] = $temp;
            }
        }
        $this->_insertData = $data;
        $this->_updateData = $update;
    }

    /**
     * 错误记录
     * @param $key 异常数据
     * @param string $message 提示信息
     * @param string $format 格式化数据
     * @return null
     */
    public function record($key, $message = '', $format = '[%s]')
    {
        $this->errorinfo [][$key] = sprintf($format, $message);
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        parent::import();
        $this->filterCodeData();
        $this->filterDbData();
        if (!$this->errorinfo) {
            $this->packData();
            $this->startTrans();
            try {
                // 货位写入
                if ($this->_locationWriteData) {
                    foreach ($this->_locationWriteData as $warehouseId => $locations) {
                        foreach ($locations as $key => $location)
                        $data [] = [
                            'warehouse_id' => $warehouseId,
                            'location_code' => $location
                        ];
                    }
                    $model = new Model();
                    if (!$model->table('tb_wms_location')->addAll($data, ['table' => 'tb_wms_location'], true)) {
                        throw new Exception(L('货位写入失败：') . $model->getDbError());
                    }
                }
                // 更新
                if ($this->_updateData) {
                    if (!$this->addAll($this->_updateData, [], true))
                        throw new Exception(L('更新失败，请稍后再试'));
                }
                if ($this->_insertData) {
                    if (!$this->addAll($this->_insertData))
                        throw new Exception(L('写入失败，请稍后再试'));
                }
                $this->commit();
                return true;
            } catch (\Exception $e) {
                $this->rollback();
                return false;
            }
        } else {
            return false;
        }
    }
}