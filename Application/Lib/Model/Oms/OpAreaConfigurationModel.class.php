<?php

import('ORG.Util.Date');// 导入日期类
class OpAreaConfigurationModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_op_area_configuration';

    protected $fields = [
        "country_id","description","prefix_postal_code","logistics_company", "logistics_mode",
        "created_by","created_at","updated_by","updated_at", "deleted_by","deleted_at",
    ];

    # 批量验证
    protected $patchValidate = true; # 批量更


    /**
     * 批量插入钩子函数
     * @param $data
     * @author Redbo He
     * @date 2021/2/3 13:11
     */
    protected function _before_write(&$data)
    {
        $date = new Date();
        $data['created_by'] = session('m_loginname');
        $data['updated_by'] = session('m_loginname'); 
        $data['created_at'] = $date->format();
    }





}