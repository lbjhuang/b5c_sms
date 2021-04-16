<?php

/**
 * User: b5m
 * Date: 2019/07/12
 * Time: 16:08
 */


class AlloImportExcelNewModel extends BaseImportExcelModel
{
    protected $autoCheckFields  =   false;

    public function fieldMapping()
    {
        return [
            'sku' => ['field_name' => L('SKU编码'), 'key' => 'A', 'required' => false],
            'GUDS_OPT_UPC_ID' => ['field_name' => L('条形码'), 'key' => 'B', 'required' => false],
            'vir_type' => ['field_name' => L('商品类型'), 'key' => 'C', 'required' => true],
            'small_sale_team_code' => ['field_name' => L('销售小团队'), 'key' => 'D', 'required' => true],
            //'ascription_store' => ['field_name' => L('归属店铺id'), 'key' => 'D', 'required' => true],
            'num' => ['field_name' => L('调拨数量'), 'key' => 'E', 'required' => true],
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

    //重写Excel导入规则 第二行为标题
    public $firstCellRowData;

    public function getFirstCellData()
    {
        $currentRow = 2;
        $this->firstCellRowData = $this->getCellData($currentRow);
    }

    public $cacheSku;
    //判断sku 商品类型 店铺 去重
    public $cacheSkuNew;

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
            //错误模板缺少字段
            if (!(isset($cell_data['A']) && isset($cell_data['B']) && isset($cell_data['C']) && isset($cell_data['D']) && isset($cell_data['E']))) {
                $this->errorinfo [][$this->excel->start_row . 'A' . $this->excel->start_row . 'B'] = '模板错误';
            }
            //整行没有数据
            if (empty($cell_data['A']) && empty($cell_data['B']) && empty($cell_data['C']) && empty($cell_data['D'])) {
                continue;
            }
            //sku和条形码无数据
            if (empty($cell_data['A']) && empty($cell_data['B'])) {
                $this->errorinfo [][$this->excel->start_row . 'A' . $this->excel->start_row . 'B'] = 'SKU和条形码必须存在一个';
                continue;
            }
            //同时判断sku 商品类型 归属店铺（换成了销售小团队）
            $index = 'sku:'.$cell_data['A'] . '-商品类型:' . $cell_data['C'] . '-销售小团队:' . $cell_data['D'];
            if (isset($this->cacheSkuNew[$index])) {
                $this->errorinfo[][$this->excel->start_row . 'A' . $this->excel->start_row . 'B' . $this->excel->start_row . 'C'] = L('[' . $index . ']存在重复项');
            } else {
                $this->cacheSkuNew[$index] = $this->excel->start_row;
            }
            $data [$this->excel->start_row] = $cell_data;
        }

        // save
        $r = [];
        foreach ($data as $key => $value) {
            $temp = [];
            foreach ($value as $k => $v) {
                $temp[$k]['db_field'] = $this->title[$k]['db_field'];
                //需求9696导入Excel sku和upc任意一个存在
                //该逻辑移除需同时判断sku 商品类型 归属店铺
                /*if ($temp [$k]['db_field'] == 'sku' && isset($this->cacheSku [$v]) && $v) {
                    $this->errorinfo [][$key . $k] = $v . L(':与[' . $this->cacheSku [$v] . ']存在重复项');
                } else {
                    $this->cacheSku [$v] = $key . $k;
                }*/
                // 判断小团队名称合法性 CD映射，并且将小团队名称替换为CD值
                if ($temp[$k]['db_field'] == 'small_sale_team_code') {
                    if ($v !== '无' && $v !== '销售小团队') { // 第二行是“销售小团队”，该值需要排除
                        $v = valCd($v);
                    }
                }
                $temp [$k]['value'] = $v;
                $this->cacheSku[$v] = $key . $k;
            }
            $r[$key] = $temp;
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
            $tmp = $sku = $upc = null;
            foreach ($value as $r => $v) {
                if ($v ['db_field'] == 'sku') {
                    $sku = $v ['value'];
                }
                if ($v ['db_field'] == 'GUDS_OPT_UPC_ID') {
                    $upc = $v ['value'];
                }
                $tmp [$v ['db_field']] = $v ['value'];
                //$tmp [$v ['db_field']] = $v ['index'];
            }
            if (empty($tmp['sku']) && empty($tmp['GUDS_OPT_UPC_ID'])) {
                continue;
            }

            /*if (!empty($tmp['sku'])) {
                $data [$sku] = $tmp;

            } else {
                $data [$upc] = $tmp;
            }*/
            $data [] = $tmp;
        }

        $this->data = $data;
    }

    /**
     * 导入主入口函数
     */
    public function import()
    {
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '512M');
        parent::import();
        $this->packData();

        if ($this->errorinfo) {
            $code = 300;
            $err = [];
            //对错误提示降维
            foreach ($this->errorinfo as $key => $value) {
                foreach ($value as $k => $v) {
                    $err[$k] = $v;
                }
            }
            $data = $err;
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