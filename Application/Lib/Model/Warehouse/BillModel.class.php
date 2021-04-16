<?php
/**
 * 生成调拨、采购、b2c、出入库管理上传EXCEL的出入库单
 * User: b5m
 * Date: 2018/7/2
 * Time: 10:08
 */
class BillModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_bill';

    public $bill;

    /**
     * 数据添加
     * @return mixed 返回写入的id
     */
    public function bill()
    {
        $id = $this->add($this->bill);
        if ($id != false) {
            return $id;
        } else {
            return false;
        }
    }
}