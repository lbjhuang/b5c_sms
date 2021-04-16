<?php
/**
 * User: yangsu
 * Date: 18/8/13
 * Time: 15:45
 */


class TbMsLogisticsCompanyModel extends DBModel
{
    protected $trueTableName = 'tb_ms_logistics_company';

    public function getAllList()
    {
        $model = new Model();
        $this->where['tb_ms_cmn_cd.CD'] = array('like', "N00070%");
        $this->where['tb_ms_cmn_cd.USE_YN'] = 'Y';
        $res = $model->table('tb_ms_cmn_cd')
            ->field('tb_ms_logistics_company.*,tb_ms_cmn_cd.CD AS logistios_company_cd')
            ->join('left join tb_ms_logistics_company on tb_ms_logistics_company.logistios_company_cd = tb_ms_cmn_cd.CD')
            ->where($this->where)
            ->order('tb_ms_cmn_cd.CD DESC')
            ->limit($this->this_page, $this->page_count)
            ->select();
        return $res;
    }
}