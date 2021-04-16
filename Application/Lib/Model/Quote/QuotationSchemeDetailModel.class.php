<?php
import('ORG.Util.Date');// 导入日期类

class QuotationSchemeDetailModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quotation_scheme_detail';

    protected $fields = [
        "quotation_scheme_id","transport_supplier_id","transportation_channel_cd",
        "logistics_currency_cd","logistics_cost","insurance_currency_cd",
        "insurance_cost", "predict_currency_cd","predict_cost",
        "delivery_date","hours_underway_date",
        "stuffing_type_cd","quotation_ids","remark",
        "creator_id","operator_id","created_by",
        "updated_by","created_at","updated_at",
    ];

    protected $_validate = [
        ['transport_supplier_id', 'require', '运输公司不能为空！', 1, 'regex', 3],
        ['transportation_channel_cd', 'require', '运输渠道不能为空！', 1, 'regex', 3],
        ['logistics_currency_cd', 'require', '物流币种不能为空！', 1, 'regex', 3],

        ['logistics_cost', 'require', '物流费用不能为空！', 1, 'regex', 3],
        ['logistics_cost', 'currency', '物流费用必须是货币格式！', 1, 'regex', 3],

        ['insurance_currency_cd', 'require', '保险币种不能为空！', 1, 'regex', 3],
        ['insurance_cost', 'require', '保险费用不能为空！', 1, 'regex', 3],
        ['insurance_cost', 'currency', '保险费用必须是货币格式！', 1, 'regex', 3],

        ['predict_currency_cd', 'require', '预计币种不能为空！', 1, 'regex', 3],
        ['predict_cost', 'require', '预计费用不能为空！', 1, 'regex', 3],
        ['predict_cost', 'currency', '保险费用必须是货币格式！', 1, 'regex', 3],

        ['delivery_date', 'require', '出库日期不能为空！', 1, 'regex', 3],
        ['delivery_date', 'check_date','出库日期格式有误',1,'callback'],
        ['hours_underway_date', 'require', '航行时间不能为空！', 1, 'regex', 3],
        ['hours_underway_date', 'check_date','航行时间格式有误',1,'callback'],

        ['stuffing_type_cd', 'require', '装柜类型不能为空！', 1, 'regex', 3],
        # ['quotation_ids', 'require', '报价单记录不能为空！', 1, 'regex', 3],
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

    public function _before_insert(&$data, $option)
    {
        $date = new Date();
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
        if(isset($data['quotation_ids']) && is_array($data['quotation_ids'])) {
            $data['quotation_ids'] = implode(',', $data['quotation_ids']);
        }
    }

    protected function _before_write(&$data)
    {
        $date = new Date();
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
        if(isset($data['quotation_ids']) && is_array($data['quotation_ids'])) {
            $data['quotation_ids'] = implode(',', $data['quotation_ids']);
        }

    }





}