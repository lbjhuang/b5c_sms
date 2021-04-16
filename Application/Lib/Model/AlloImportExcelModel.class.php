<?php

/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/23
 * Time: 16:08
 */


class AlloImportExcelModel extends BaseImportExcelModel
{
    public function fieldMapping()
    {
        return [
            'sku' => ['field_name' => L('SKU编码'), 'key' => 'A', 'required' => true],
            'num' => ['field_name' => L('调拨数量'), 'key' => 'B', 'required' => false],
        ];
    }

    /**
     * @param int    $row_index    行坐标
     * @param int    $column_index 列坐标
     * @param string $value        值
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
     *
     */
    public function getData()
    {
        $data = [];
        // read
        for ($this->excel->start_row; $this->excel->start_row <= $this->excel->max_row; $this->excel->start_row ++) {
            $data [$this->excel->start_row] = $this->getCellData($this->excel->start_row);
        }
        // save
        $r = [];
        foreach ($data as $key => $value) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp [$k]['db_field'] = $this->title [$k]['db_field'];
                if ($temp [$k]['db_field'] == 'sku' && isset($this->cacheSku [$v])) {
                    $this->errorinfo [$key.$k] = $v . L(':与[' . $this->cacheSku [$v] . ']存在重复项');
                } else {
                    $this->cacheSku [$v] = $key . $k;
                }
                $temp [$k]['value'] = $v;
            }
            $r [$key] = $temp;
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
                $tmp [$v ['db_field']] = $v ['value'];
                //$tmp [$v ['db_field']] = $v ['index'];
            }

            $data [$sku] = $tmp;
        }

        $this->data = $data;
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        ini_set('max_execution_time', 1800);
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