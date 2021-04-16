<?php

/**
 * User: b5m
 * Date: 2019/07/12
 * Time: 16:08
 */


class StockOwnerChangeImportExcelModel extends BaseImportExcelModel
{
    public function fieldMapping()
    {
        return [
            'sku' => ['field_name' => L('SKU编码'), 'key' => 'A', 'required' => true],
            'batch_code' => ['field_name' => L('批次号'), 'key' => 'B', 'required' => true],
            'num' => ['field_name' => L('调拨数量'), 'key' => 'C', 'required' => false],
        ];
    }

    /**
     * @param int $row_index 行坐标
     * @param int $column_index 列坐标
     * @param string $value 值
     *
     * @return null
     */
    public function valid($row_index, $column_index, $value)
    {
        parent::valid($row_index, $column_index, $value);
    }

    public $cacheSku;

    public function getTitle($mode = null)
    {
        $fields = $this->fieldMapping();
        foreach ($this->firstCellRowData as $key => $value) {
            foreach ($fields as $k => $v) {
                if ($v['key'] == $key) {
                    $temp [$key] ['db_field'] = $k;
                    $temp [$key] ['required'] = $v['required'];
                    $temp [$key] ['en_name'] = $value;
                }
            }
        }
        $this->title = $temp;
    }

    /**
     * 读取excel中的数据
     */
    public function getData()
    {
        $data = [];
        // read
        for ($this->excel->start_row; $this->excel->start_row <= $this->excel->max_row; $this->excel->start_row++) {
            $cell_data = $this->getCellData($this->excel->start_row);
            if (empty($cell_data['A'])) {
                continue;
            }
            $data [$this->excel->start_row] = $cell_data;
        }
        // save
        $r = [];
        foreach ($data as $key => $value) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp [$k]['db_field'] = $this->title [$k]['db_field'];
                $temp [$k]['value'] = $v;
            }
            if (isset($this->cacheSku [$temp['A']['value'] . $temp['B']['value']])) {
                $this->errorinfo [$key . 'A'] = $key . 'A' . L(':与[' . $this->cacheSku [$temp['A']['value'] . $temp['B']['value']] . ']存在重复项');
            }
            $r [$key] = $temp;
            $this->cacheSku [$temp['A']['value'] . $temp['B']['value']] = $key . 'A';
        }
        $this->data = $r;
        unset($data, $r);
    }

    /**
     * 数据再组装
     * 对采购商进行组装，去重验证
     */
    public function packData()
    {
        $data = [];
        foreach ($this->data as $key => $value) {
            $tmp = $sku = null;
            foreach ($value as $r => $v) {
                if ($v ['db_field'] == 'sku') {
                    $sku = $v ['value'];
                }
                if ($v ['db_field'] == 'batch_code') {
                    $batch = $v ['value'];
                }
                $tmp [$v ['db_field']] = $v ['value'];
                //$tmp [$v ['db_field']] = $v ['index'];
            }
            if (empty($tmp['sku']) || empty($tmp['batch_code'])) {
                continue;
            }
            $data [$sku . $batch] = $tmp;
        }

        $this->data = $data;
    }

    /**
     * 导入主入口函数
     */
    public function import()
    {
        ini_set('max_execution_time', 18000);
        ini_set('memory_limit', '512M');
        parent::import();
        $this->packData();

        if ($this->errorinfo) {
            $code = 300;
            $data = $this->errorinfo;
        } else {
            $code = 200;
            $data = $this->data;
        }

        return [
            'code' => $code,
            'data' => $data
        ];
    }
}