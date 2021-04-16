<?php

/**
 * 地址模型
 * 
 */
class TbCrmSiteModel extends BaseModel
{
    
    protected $trueTableName = 'tb_crm_site';

    public function getChildrenAddress($parent_id = 0) {
        $field = $parent_id ? 'ID, NAME, PARENT_ID' : 'ID, CONCAT(RES_NAME, NAME) AS NAME, PARENT_ID';
        return $this->field($field)->where(['PARENT_ID'=>$parent_id])->order('SORT')->select();
    }

    public function siteName($id = 0, $type = 'cn') {
        if($id) {
            $site = $this->field('NAME,RES_NAME,PARENT_ID,NAME_EN')->where(['ID'=>$id])->find();
            if ($type = 'en') {
                return $site['PARENT_ID'] ? $site['NAME_EN'] : '(' . $site['RES_NAME'] . ')' .$site['NAME_EN'];
            } else {
                return $site['PARENT_ID'] ? $site['NAME'] : $site['RES_NAME'].$site['NAME'];
            }
        }else {
            return '';
        }
    }

}