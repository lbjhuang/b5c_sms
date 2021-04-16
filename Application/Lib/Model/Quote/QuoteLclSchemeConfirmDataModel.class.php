<?php
import('ORG.Util.Date');// 导入日期类

class QuoteLclSchemeConfirmDataModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_lcl_scheme_confirm_data';

    protected $fields = [
        'quote_lcl_id','quotation_id','checker_id','checked_by',
        'quotation_scheme_id', 'remark',
        'creator_id','created_by','operator_id','updated_by',
        'created_at','updated_at'
    ];

    protected $_validate = [
        ['quote_lcl_id', 'require', '拼柜报价单ID不能为空', 1, 'regex', 3],
        ['quotation_id', 'require', '报价单ID不能为空', 1, 'regex', 3],
        ['quotation_id', 'check_quotation_exist', '运营审核结果已存在', 1, 'callback',1],
        ['checker_id', 'require', '审核人ID不能为空', 1, 'regex', 3],
        ['quotation_scheme_id', 'require', '方案ID不能为空', 1, 'regex', 3],
    ];

    protected function check_quotation_exist($value) {
        $quote_lcl_id = I("post.quote_lcl_id");
        $res = $this->where([
            ["quote_lcl_id" =>  $quote_lcl_id],
            ["quotation_id" =>  $value],
        ])->find();
        if($res)
        {
            return false;
        }
        return true;
    }

    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        # 报价单状态
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();

        if(empty($data['checked_by'])) {
            $Admin = D("Admin");
            $checker =   $Admin->field("M_ID as id, M_NAME as name, M_MOBILE as mobile, M_EMAIL email")
                            ->where("M_ID = {$data['checker_id']}")->find();
            $data['checked_by'] = $checker ? $checker['name'] : '';
        }
    }

    protected function _before_write(&$data)
    {
        $date = new Date();
        # 报价单状态
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
        if(empty($data['checked_by'])) {
            $Admin = D("Admin");
            $checker =   $Admin->field("M_ID as id, M_NAME as name, M_MOBILE as mobile, M_EMAIL email")
                ->where("M_ID = {$data['checker_id']}")->find();
            $data['checked_by'] = $checker ? $checker['name'] : '';
        }
    }

}