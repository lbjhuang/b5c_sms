<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/29
 * Time: 9:48
 */

class SyncAction extends  Action{

    protected $res = array(
        'code' => 2000,
        'msg'  => "",
    );

    /**
     *  供应商同步
     */
    public function supplier(){
        $requert_data  =  file_get_contents('php://input');
        Logs("请求-".date("Y-m-d H:i:s")."----".$requert_data,__FUNCTION__,__CLASS__);
        $requert_data  =  json_decode($requert_data,true);
        try{
            $model = M();
            $model->startTrans();
            if (empty($requert_data) || !is_array($requert_data)){
                throw new Exception('参数不能为空');
            }
            $this->supplierValidate($requert_data);

            $insert_data_supplier = array();  // 供应商/B2B客户
            $SP_ADDR1  =  "";
            $SP_ADDR2  =  "";
            $SP_ADDR3  =  "";
            $SP_ADDR4  =  "";

            // 区域表转换  tb_ms_user_area -> tb_crm_site
            if ($requert_data['SP_ADDR1']){
                $SP_ADDR1 = $this->userAreaToSite($requert_data['SP_ADDR1']);
            }
            if ($requert_data['SP_ADDR2']){
                $SP_ADDR2 = $this->userAreaToSite($requert_data['SP_ADDR2']);
            }
            if ($requert_data['SP_ADDR3']){
                $SP_ADDR3 = $this->userAreaToSite($requert_data['SP_ADDR3']);
            }
            if ($requert_data['SP_ADDR4']){
                $SP_ADDR4 = $this->userAreaToSite($requert_data['SP_ADDR4']);
            }
            $insert_data_supplier['SP_CHARTER_NO'] = $requert_data['SP_CHARTER_NO'];   // 【营业执照/个人证件号】对应我方公司的【公司CODE】
            $insert_data_supplier['SP_NAME'] =  $insert_data_supplier['SP_RES_NAME'] = $requert_data['SP_NAME']; // 供应商名称】和【供应商简称】都对应我方公司的【公司名称（中文）】
            $insert_data_supplier['SP_NAME_EN'] = $insert_data_supplier['SP_RES_NAME_EN'] = $requert_data['SP_NAME_EN'];  // 【英文名称】和【英文简称】都对应我方公司的【公司名称（英文）】
            $insert_data_supplier['SP_ADDR1'] = $insert_data_supplier['SP_ADDR5'] =  $SP_ADDR1;  // 【注册地址-国别】和【办公地址-国别】都对应我方公司的【注册区域-国家】
            $insert_data_supplier['SP_ADDR2'] = $insert_data_supplier['SP_ADDR6'] = $SP_ADDR2;  // 【注册地址-国别】和【办公地址-国别】都对应我方公司的【注册区域-国家】
            $insert_data_supplier['SP_ADDR3'] = $insert_data_supplier['SP_ADDR7'] = $SP_ADDR3;  // 【注册地址-省】和【办公地址-省】都对应我方公司的【注册区域-省市】
            $insert_data_supplier['SP_ADDR4'] = $insert_data_supplier['SP_ADDR8'] = $SP_ADDR4;  // 【注册地址-市、县】和【办公地址-市、县】都对应我方公司的【注册区域-区县】
            $insert_data_supplier['COMPANY_ADDR_INFO'] = $requert_data['COMPANY_ADDR_INFO'];  // 【办公详细地址】对应我方公司的【注册地址-中文】
            $insert_data_supplier['COMPANY_MARKET_INFO'] = '-';  // 【公司与市场地位简述】为【-】
            $insert_data_supplier['COPANY_TYPE_CD'] = 'N001190600';  // 【企业类型】为【其他】
            $insert_data_supplier['SP_TEAM_CD'] = 'N001292400';  // 【采购团队】为【All-公共库存】
            $insert_data_supplier['SP_JS_TEAM_CD'] = 'N001302200';  // 【介绍团队】为【支持部门】
            $insert_data_supplier['SP_CAT_CD'] = 'N001510100';    // 【供货品类】为【其他/Others】
            $insert_data_supplier['SP_YEAR_SCALE_CD'] = 'N001200600';  // 【供应商年业务规模】为【>=$100M】
            $insert_data_supplier['DATA_MARKING'] = 0;  //  0：供应商，1：B2B客户

            $insert_data_supplier['AUDIT_STATE'] = '2';  // 自动同步到对方ERP生成并审核完成【供应商】
            $insert_data_supplier['CREATE_TIME'] = date("Y-m-d H:i:s",time());
            $insert_data_supplier['UPDATE_TIME'] = date("Y-m-d H:i:s",time());
            $insert_data_supplier['RISK_RATING'] = '1';  // 【风险评级】为【低风险】
            
            $res = $model->table('tb_crm_sp_supplier')->add($insert_data_supplier);
            if (!$res){
                throw new Exception('添加供应商异常');
            }
            // 供应商法务审核信息
            $insert_data_forensic = array();
            $insert_data_forensic['SP_CHARTER_NO'] =  $insert_data_supplier['SP_CHARTER_NO'] ;
            $insert_data_forensic['EST_TIME'] = $requert_data['EST_TIME'];  // 【成立时间】对应我方公司的【注册时间】
            $insert_data_forensic['LG_REP'] = $requert_data['LG_REP'];  // 【法人代表】对应我方公司的【法定代表人/董事/负责人】
            $insert_data_forensic['SHARE_NAME'] = $requert_data['SHARE_NAME'];  // 【股东名称】对应我方公司的【股东名称/公司】
            $insert_data_forensic['CURRENCY'] = 'N000590100';  // 【认缴资本-币种】为【USD】
            $insert_data_forensic['SUB_CAPITAL'] = '99999999';  // 【认缴资本-金额】为【99999999】
            $insert_data_forensic['IS_HAVE_NAGETIVE_INFO'] = '0';  // 【供应商年业务规模】为【>=$100M】
            $insert_data_forensic['RISK_RATING'] = '1';  // 【风险评级】为【低风险】
            $insert_data_forensic['CRM_CON_TYPE'] = 0;  // 所属合同类型，0-供应商，1-客户
            $res = $model->table('tb_ms_forensic_audit')->add($insert_data_forensic);
            if (!$res){
                throw new Exception('添加供应商法务审核异常');
            }

            $insert_data_supplier['DATA_MARKING'] = 1;
            $insert_data_supplier['SALE_TEAM'] = 'N001282300';
            $res = $model->table('tb_crm_sp_supplier')->add($insert_data_supplier);
            if (!$res){
                throw new Exception('添加B2B客户异常');
            }
            $insert_data_forensic['CRM_CON_TYPE'] = 1;
            $res = $model->table('tb_ms_forensic_audit')->add($insert_data_forensic);
            if (!$res){
                throw new Exception('添加B2B客户法务审核异常');
            }
            $model->commit();
        }catch (Exception $exception){
            $model->rollback();
            $this->res['code'] = 4000;
            $this->res['msg'] = $exception->getMessage();
        }
        Logs("响应-".date("Y-m-d H:i:s")."----".json_encode($this->res),__FUNCTION__,__CLASS__);
        $this->ajaxReturn($this->res);
    }

    /**
     * 参数验证
     * @param $request_data
     * @throws Exception
     */
    public function supplierValidate($requert_data) {
        $rules = array(
            'SP_CHARTER_NO' => 'required',
            'SP_NAME' => 'required',
            'SP_NAME_EN' => 'required',
            'SP_ADDR1' => 'required',
            'SP_ADDR2' => 'required',
//            'SP_ADDR3' => 'required',
//            'SP_ADDR4' => 'required',
            'COMPANY_ADDR_INFO' => 'required',
            'EST_TIME' => 'required',
            'LG_REP' => 'required',
            'SHARE_NAME' => 'required',
        );
        $custom_attributes = array(
            'SP_CHARTER_NO' => '公司CODE',
            'SP_NAME' => '公司名称（中文）',
            'SP_NAME_EN' => '公司名称（英文）',
            'SP_ADDR1' => '注册区域-国家',
            'SP_ADDR2' => '注册区域-国家',
//            'SP_ADDR3' => '注册区域-省市',
//            'SP_ADDR4' => '注册区域-区县',
            'COMPANY_ADDR_INFO' => '注册地址-中文',
            'EST_TIME' => '注册时间',
            'LG_REP' => '法定代表人/董事/负责人',
            'SHARE_NAME' => '股东名称/公司'
        );
        $this->validate($rules,$requert_data,$custom_attributes);
    }

    /**
     *  B2B客户同步
     */
    public function btbCustomer(){

    }


















    /**
     * @param $rules
     * @param $data
     * @param $custom_attributes
     * @throws Exception
     */
    public function validate($rules, $data, $custom_attributes)
    {

        ValidatorModel::validate($rules, $data, $custom_attributes);
        $message = ValidatorModel::getMessage();

        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $value) {
                throw new Exception(L($value[0]), 40001);
            }
        }
    }

    /**
     * ERP中存在两张区域表
     * tb_ms_user_area->tb_crm_site  通过地区名称相互ID转换  【无关联返回空】
     */
    public function userAreaToSite($area_id){
        $site_id = "";
        $area_info = M('user_area','tb_ms_')->field('zh_name')->where(array('id'=>$area_id))->find();
        if ($area_info){
            $site_info =  M('site','tb_crm_')->field('ID')->where(array('NAME'=>$area_info['zh_name']))->find();
            if ($site_info)  $site_id = $site_info['ID'];
        }
        return $site_id;
    }

    /**
     * ERP中存在两张区域表
     * tb_crm_site->tb_ms_user_area  通过地区名称相互ID转换 【无关联返回空】
     */
    public function siteToUserArea($site_id){
        $area_id = "";
        $site_info =  M('site','tb_crm_')->field('NAME')->where(array('ID'=>$site_id))->find();
        if ($site_info){
            $area_info = M('user_area','tb_ms_')->field('id')->where(array('zh_name'=>$site_info['NAME']))->find();
            if ($area_info)  $area_id = $area_info['id'];
        }
        return $area_id;
    }

}