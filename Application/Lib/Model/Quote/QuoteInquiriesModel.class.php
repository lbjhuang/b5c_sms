<?php


class QuoteInquiriesModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_inquiries';

    protected $fields = [
        "quotation_id","planned_transportation_channel_cd","transport_supplier_id",
        "expected_delivery_date","expected_warehousing_date",
        "creator_id","operator_id", "created_by","updated_by",
        "created_at","updated_at",
    ];

    // 自动校验规则
    protected $_validate = [
        ['planned_transportation_channel_cd', 'require', '计划运输渠道不能为空', 1, 'regex', 3],
       #  ['transport_supplier_id', 'require', '运输供应商ID不能为空', 1, 'regex', 3],
        ['expected_delivery_date', 'require', '期望出库日期不能为空！', 1, 'regex', 3],
        ['expected_delivery_date', 'check_date','期望出库日期格式有误',1,'callback'],
        ['expected_warehousing_date', 'require', '期望入库日期不能为空！', 1, 'regex', 3],
        ['expected_warehousing_date', 'check_date','期望入库日期格式有误',1,'callback'],
    ];

    /**
     * 校验要求完成时间格式
     * @param string $value
     * @return mixed
     * @author Redbo He
     * @date 2020/11/3  19:10
     */
    public function check_date($value)
    {
        if(date("Y-m-d", strtotime($value)) == $value) {
            return true;
        }
        return false;
    }



    /**
     * 添加前置函数
     * @param $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/4  20:43
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        $data['quotation_id'] = I('post.id');
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
        $data = array_only($data, $this->fields);
    }

    

}