<?php

class QuotePackingInformationModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_packing_information';

    protected $fields = [
        "quotation_id","allo_no",
        "total_box_num","total_volume","total_weight","allo_in_warehouse",
        "allo_in_warehouse_address","allo_out_warehouse","allo_out_warehouse_address",
        "declare_type_cd","declare_type_remark","is_electric_cd","creator_id","operator_id",
        "created_by","updated_by","created_at","updated_at",
    ];

    // 自动校验规则
    protected $_validate = [
        ['total_box_num', 'require', '总箱数不能为空', 1, 'regex', 3],
        ['total_volume', 'require', '总体积不能为空', 1, 'regex', 3],
        ['total_weight', 'require', '总重量不能为空', 1, 'regex', 3],
        ['allo_in_warehouse', 'require', '调入仓库不能为空', 1, 'regex', 3],
        ['allo_out_warehouse', 'require', '调出仓库不能为空', 1, 'regex', 3],
        ['declare_type_cd', 'require', '报关方式不能为空', 1, 'regex', 3],
        ['is_electric_cd', 'require', '是否带电不能为空', 1, 'regex', 3],
    ];

    /**
     * 插入前置函数
     * @param array $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/4  20:47
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        $data['quotation_id'] = I('post.id');
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
    }


    protected function _before_write(&$data) {
        $date = new Date();
        $data['quotation_id'] = I('post.id');
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
    }


    

}