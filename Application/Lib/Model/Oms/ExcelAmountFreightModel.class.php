<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/7/10
 * Time: 18:29
 */
class ExcelAmountFreightModel extends BaseImportExcelModel
{
    protected $trueTableName = '';

    public function fieldMapping()
    {
        return [
            'B5C_ORDER_NO'            => ['field_name' => '订单号', 'required' => true],
            'pre_freight_currency'    => ['field_name' => '头程运费试算币种', 'required' => false],
            'pre_amount_freight'      => ['field_name' => '头程运费试算', 'required' => false],
            'insurance_currency'      => ['field_name' => '保险费用币种', 'required' => false],
            'insurance_fee'           => ['field_name' => '保险费', 'required' => false],
            'freight_currency'        => ['field_name' => '尾程运费试算币种', 'required' => false],
            'amount_freight'          => ['field_name' => '尾程运费试算', 'required' => false],
            'carry_tariff_currency'   => ['field_name' => '尾程物流派送关税币种', 'required' => false],
            'carry_tariff'            => ['field_name' => '尾程物流派送关税', 'required' => false],
            'vat_fee_currency'        => ['field_name' => 'VAT币种', 'required' => false],
            'vat_fee'                 => ['field_name' => 'VAT', 'required' => false],
            'paypal_fee_currency'     => ['field_name' => '支付手续费币种', 'required' => false],
            'paypal_fee'              => ['field_name' => '支付手续费', 'required' => false],
            'league_fee_currency'     => ['field_name' => '流量活动费用币种', 'required' => false],
            'league_fee'              => ['field_name' => '流量活动费用', 'required' => false],
            'subsidy_currency'        => ['field_name' => '活动补贴币种', 'required' => false],
            'subsidy'                 => ['field_name' => '活动补贴', 'required' => false],
            'pur_return_fee_currency' => ['field_name' => '采购返佣金币种', 'required' => false],
            'pur_return_fee'          => ['field_name' => '采购返佣金', 'required' => false],
            'sale_after_fee_currency' => ['field_name' => '售后费用币种', 'required' => false],
            'sale_after_fee'          => ['field_name' => '售后费用', 'required' => false], 
        ];
    }

    /**
     * @param int $row_index 行坐标
     * @param int $column_index 列坐标
     * @param string $value 值
     * @return null
     */
    public function valid($row_index, $column_index, $value)
    {
        parent::valid($row_index, $column_index, $value);
    }

    private $b5cOrderNo;
    private $ret;
    /**
     * 数据再组装
     * 对采购商进行组装，去重验证
     */
    public function packData()
    {
        $data = [];
        $trim = function ($val) {
            return preg_replace('/(^[\s\t\r\n　]+)|([\s\t\r\n　]+$)/u', '', $val);
        };
       

        $keys = array_keys($this->fieldMapping());
        foreach ($this->data as $index => $info) {
            
            if (!$info ['A']['value']) break;
            $tmp = null;
            $xkey = 'A';
            foreach ($keys as $v) {
                $tmp[$v] = $trim($info [$xkey++]['value']);
            }
            $tmp ['freight_type'] = 'N000179300';
            $this->b5cOrderNo [] = $tmp['B5C_ORDER_NO'];
            
            $data [] = $tmp;
        }
      
        $model = new TbOpOrdModel();
        $this->ret = $model->where(['B5C_ORDER_NO' => ['in', $this->b5cOrderNo]])->getField('B5C_ORDER_NO as b5cOrderNo, BWC_ORDER_STATUS as bwcOrderStatus, PLAT_CD as platCd, ORDER_ID as orderId');
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
        $model = new OutStorageModel();
        
        $result = $model->validateData($this->data, $this->ret, $this->title);//excel数据验证,未出库的订单禁止导入
        if ($result['code'] != 2000) {
            return $result;
        }
        $model->excelName = $this->saveName;
        $response = $model->amountFreight($this->data, $this->ret);

        return $response;
    }
}