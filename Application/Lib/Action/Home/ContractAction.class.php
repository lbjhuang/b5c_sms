<?php

use GuzzleHttp\Client;

class ContractAction extends BaseAction
{
    #合同字段映射
    #接口=>erp
    private static $contract_mapping = [
        '合同类型' => 'HTLX',
        '合同名称' => 'CON_NAME',
        '对方营业执照号/个人证件号' => 'CGBUSINESSLICENSE',
        '对方公司名称' => 'SP_NAME',
        '合同开始日期' => 'PERIOD_FROM',
        '合同结束日期' => 'PERIOD_TO',
        // '我方公司' => 'GSMC',#无法匹配
        '是否自动续约' => 'SFZDXY',
        '对方开户行' => 'CGGYSKHH',
        '对方银行账号' => 'CGYHZH',
        '对方SWIFT CODE' => 'CGSWIFTCODE',
        '对方联系人' => 'CGDFLXR',
        '对方电子邮箱' => 'CGEMAIL',
        '对方联系电话' => 'CGLXDH',
        '其他（重大不利条款等）' => 'REMARK',
        '对方电子邮箱'=> 'CGEMAIL',
       
    ];
    #合同类型映射关系   接口=>erp
    private static $contract_type_mapping = [
        '商品采购合同' => ['商品采购合同','商品采购'],
        '自用品采购合同' => ['自用品采购合同', '自用品采购'],
        '销售合同' => ['销售合同'],
        '服务合同（我方付费）' => ['服务合同（我方付费）', '服务（我方付费）', '服务合同(我方付费)', '服务(我方付费)'],
        '服务合同（对方付费）' => ['服务合同（对方付费）', '服务合同(对方付费)', '服务（对方付费）', '服务(对方付费)',],
        '租赁合同' => ['租赁合同'],
        '居间合同' => ['居间合同'],
        '内部股权变动' => ['内部股权变动'],
        '内部资金调配' => ['内部资金调配'],
        '内部关联交易' => ['内部关联交易'],
        '其他合同' => ['其他合同'],


    ];
    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        B('SYSOperationLog');
    }
// ==新版合同流程 #11006-法务合同审批流程20201124START======================================================
    public function contract_new()
    {
        $this->display();
    }
    public function contract_view()
    {
        $this->display();
    }
    public function log()
    {
        $this->display();
    }
    public function log_new()
    {
        $this->display();
    }


// ==新版合同流程 #11006 法务合同审批流程 END===========================================================================

    /**
     * 合同列表
     *
     */
    public function index()
    {
        $params = $this->getParams();
        $preParams = $params;
        $contract = D('TbCrmContract');
        $this->getDataDirectory();
        import('ORG.Util.Page');// 导入分页类
        $whereIdx = 0;
        $condition = $contract->searchModel($params);
        // 假如不是法务角色的用户登录，默认筛选条件是，查找他作为发起人的合同，或他作为领导的合同，或他作为财务审核的合同列表，并且其余筛选条件都建立在该筛选条件之上
        $legalArr = (new ContractService())->getLegalPeople();
        $userName = DataModel::userNamePinyin();

        if (!in_array($userName, $legalArr)) {
            $map['leader_by'] = $userName;
            $map['finance_by'] = $userName;
            $map['created_by'] = $userName;
            $map['_logic'] = 'or';
            $condition['_complex'][$whereIdx++] = $map; 
            unset($map);
        }
        // 多个时移除掉待上传合同状态主状态
        if ($params ['audit_status_sec_cd']) {
            $params['audit_status_cd'] = str_replace(',' . TbCrmContractModel::UPLOADING, "", $params['audit_status_cd']);
            $params['audit_status_cd'] = str_replace(TbCrmContractModel::UPLOADING . ',', "", $params['audit_status_cd']);
        }

        if ($params ['audit_status_sec_cd'] && $params['audit_status_cd']) {
            if (TbCrmContractModel::UPLOADING === $params['audit_status_cd']) {
                $condition ['tb_crm_contract.audit_status_sec_cd'] = ['in', $params ['audit_status_sec_cd']];
            } else {
                $maps = [];
                $maps['tb_crm_contract.audit_status_sec_cd'] = ['in', $params ['audit_status_sec_cd']];
                $maps['tb_crm_contract.audit_status_cd'] = ['in', $params ['audit_status_cd']];
                $maps['_logic'] = 'or';
                $condition['_complex'][$whereIdx++] = $maps;
            }
        } elseif ($params ['audit_status_sec_cd']) {
            $condition ['tb_crm_contract.audit_status_sec_cd'] = ['in', $params ['audit_status_sec_cd']];
        } elseif ($params ['audit_status_cd']) {
            $condition ['tb_crm_contract.audit_status_cd'] = ['in', $params ['audit_status_cd']];
        }
        

        if(isset($condition['tb_crm_contract.SP_NAME'])){
            #存在供应商搜索   解决供应商修改名称后无法与合同进行名称关联
           
            $tmpWhere = "(tb_crm_sp_supplier.SP_NAME like '%s' or (tb_crm_sp_supplier.ID is null  and tb_crm_contract.SP_NAME like '%s') )";
            $tmpWhereValue = [$condition['tb_crm_contract.SP_NAME'][1], $condition['tb_crm_contract.SP_NAME'][1]];
            unset($condition['tb_crm_contract.SP_NAME']);

        }
        $count = $contract->join("left join tb_crm_sp_supplier as tb_crm_sp_supplier on tb_crm_sp_supplier.SP_CHARTER_NO =  tb_crm_contract.SP_CHARTER_NO AND tb_crm_contract.CRM_CON_TYPE = tb_crm_sp_supplier.DATA_MARKING ")->where($condition);
        if($tmpWhere){
            $count = $count->where($tmpWhere,$tmpWhereValue)->count();
        } else {
            $count = $count->count();
        }
        $page = new Page($count, 20);
        $show = $page->show();
        $ret = $contract->join("left join tb_crm_sp_supplier as tb_crm_sp_supplier on tb_crm_sp_supplier.SP_CHARTER_NO =  tb_crm_contract.SP_CHARTER_NO AND tb_crm_contract.CRM_CON_TYPE = tb_crm_sp_supplier.DATA_MARKING ")->where($condition)->limit($page->firstRow.','.$page->listRows)->field('tb_crm_contract.*,tb_crm_sp_supplier.SP_NAME as REAL_SP_NAME')->order('tb_crm_contract.CREATE_TIME desc');
        if ($tmpWhere) {
            $ret = $ret->where($tmpWhere, $tmpWhereValue)->select();
        } else {
            $ret = $ret->select();
        }
        // echo M()->_sql();
        if(count($ret)>0){
            foreach($ret as &$value){
                if(!empty($value['REAL_SP_NAME'])){
                    $value['SP_NAME'] = $value['REAL_SP_NAME'];
                }
                unset($value['REAL_SP_NAME']);
            }
            $ret = CodeModel::autoCodeTwoVal($ret, ['audit_status_cd']);
        }
        
        $this->assign('allUserInfo', BaseModel::getAdmin());
        $this->assign('allCountryInfo', BaseModel::getCountryInfo());
        $this->assign('count', $count);
        $this->assign('result', $ret);
        $this->assign('model', $contract);
        $this->assign('pages', $show);
        $this->assign('params', $preParams);

        $this->display();
    }

    /**
     * 根据合同编号查询是否有相应的合同
     *
     */
    public function search_contracct_by_con_no($conNo, $type, $isSelectInfo)
    {
        // 如果只是查询
        if ($isSelectInfo) return false;
        $model = D('TbCrmContract');
        $ret = $model->where('CON_NO ="' . $conNo . '" and CRM_CON_TYPE = ' . $type)->find();
        if ($ret) return $ret;
        return false;
    }
    /**
     * 过滤企业微信接口的数据
     *
     *
     */
    private function filter_wechat_contract($data){
        try{
            $apply_data = $data['info']['apply_data']['contents'];
            #获取微信Id
            $new_contract = [];
            $wechat = $data['info']['applyer']['userid'];
            
            $erp_name = M('admin ', 'bbm_')
                ->alias('admin')
                ->where(['wx.wid'=> $wechat])
                ->join('left join tb_hr_empl_wx as wx  on admin.empl_id = wx.uid')
                ->getField('admin.M_NAME');
            $new_contract['LASTNAME'] = $erp_name ? $erp_name:'';
            $contract_mapping_key = array_keys(self::$contract_mapping);
            
            foreach($apply_data as $value){
                if(in_array($value['title'][0]['text'], $contract_mapping_key)){
                    if($value['title'][0]['text'] == '合同类型'){
                        $tmp_contract_type = $value['value']['selector']['options'][0]['value'][0]['text'];
                        $model = M('_ms_cmn_cd', 'tb_');
                        $conditions['CD'] = ['like', '%N00123%'];
                        $conditions['CD_VAL'] = ['in', self::$contract_type_mapping[$tmp_contract_type]];
                        $ret = $model->where($conditions)->getField('ETC');
                        $new_contract[self::$contract_mapping[$value['title'][0]['text']]] = $ret;
                        
                    }elseif($value['title'][0]['text'] == '合同开始日期' || $value['title'][0]['text'] == '合同结束日期'){
                        $new_contract[self::$contract_mapping[$value['title'][0]['text']]] = date('Y-m-d H:i:s', $value['value']['date']['s_timestamp']);
                    } elseif ($value['title'][0]['text'] == '是否自动续约') {
                        $tmp_is_renewal = $value['value']['selector']['options'][0]['value'][0]['text'] == '是' ? 0 : 1;

                        $new_contract[self::$contract_mapping[$value['title'][0]['text']]] = $tmp_is_renewal;
                    }
                    else{
                        $new_contract[self::$contract_mapping[$value['title'][0]['text']]] =  $value['value']['text'];
                    }
                    
                }
            }
            return $new_contract;
            
        }catch(\Exception $e){
            return false;
        }
        
    }

    /**
     * 检查OA系统中，是否存在该合同
     *
     */
    public function check_contract()
    {
       
        
        $CON_NO = $this->getParams()['CON_NO'];
        $type = $this->getParams()['type'];
        $isSelectInfo = $this->getParams()['isSelectInfo'];
        // 首先检查是否在系统中存在该合同
        if ($this->search_contracct_by_con_no($CON_NO, $type, $isSelectInfo)) {
            $this->ajaxReturn('编号为：' . $CON_NO .' 的合同已存在，请修改', '', 0);
        }
        // 首先检查合同编号是否在SMS系统中存在
        $model = M('_crm_contract', 'tb_');
        $con = $model->where('CON_NO = "' . $CON_NO . '"and CRM_CON_TYPE = ' . $type)->find();
        if ($isSelectInfo) {
            $data ['DFGSMCKESHANG']     = $con ['SP_NAME'];                     // 供应商名称
            $data ['SFZDXY']            = $con ['IS_RENEWAL'];                  // 是否自动续约
            $data ['GSMC']              = $con ['CON_COMPANY_CD'];              // 我方公司
            $data ['HTLX']              = $con ['CON_TYPE'];                    // 合同类型
            $data ['CGBUSINESSLICENSE'] = $con ['SP_CHARTER_NO'];               // 营业执照号
            $data ['LASTNAME']          = explode('-', $con ['CONTRACTOR'])[1]; // 签约人
            $data ['SQR']               = explode('-', $con ['CONTRACTOR'])[0]; // 签约人编号
            $data ['PERIOD_FROM']       = $con ['START_TIME'];                  // 开始时间
            $data ['PERIOD_TO']         = $con ['END_TIME'];                    // 结束时间
            $data ['CGGYSKHH']          = $con ['SP_BANK_CD'];                  // 供应商开户行
            $data ['CGYHZH']            = $con ['BANK_ACCOUNT'];                // 银行账号
            $data ['CGSWIFTCODE']       = $con ['SWIFT_CODE'];                  // Swift Code
            $data ['CGDFLXR']           = $con ['CONTACT'];                     // 对方联系人
            $data ['CGEMAIL']           = $con ['CON_EMAIL'];                   // 电子邮箱
            $data ['CGLXDH']            = $con ['CON_PHONE'];                   // 联系手机
            $data ['CGLXDH']            = $con ['CON_PHONE'];                   // 联系手机
            $data ['CONTRACT_TYPE']     = $con ['CONTRACT_TYPE'];               // 是否长期合同
            $this->ajaxReturn($data, '', 1);
        }
        $data = $this->pullWeChatContract($CON_NO);

        if ($data) {
            //对接口返回的值进行过滤整合
            $data = $this->filter_wechat_contract($data);
            if(!$data){
                $this->ajaxReturn('企业微信接口返回有误', '企业微信接口返回有误', 0);
            }
        }else{
            $data = $this->pullContract($CON_NO);
        }

        
        
        if ($data) {
            $this->ajaxReturn($data, '', 1);
        } else {
            $this->ajaxReturn('未查询到编号为：' . $CON_NO . ' 的合同', '未查询到编号为：' . $CON_NO .' 的合同', 0);
        }
    }

    /**
     * 从企业微信拉取合同
     *
     */
    public function pullWeChatContract($CON_NO){
        try{
            $client = new Client([
                'timeout'  => 4.0,//超时处理
            ]);
            $url = 'http://api.izene.org/wx/getoadetail.php';
            $post_data = ['spno' => $CON_NO];
           
            $response = $client->request('POST', $url, [
                'form_params' => $post_data
            ]);
            
           
            $body = $response->getBody();
            $content = $body->getContents();
            $content  = json_decode($content,1);
            
            Logs(['url' => $url, 'post_data' => $post_data,'re' => $content], __FUNCTION__, __CLASS__);
            if(empty($content) || $content['errcode'] != '0'){
                return false;
            };
            #拿到接口返回的正常值
            return $content;


        }catch(\Exception $e){
            //获取失败
            $err = [
                'errcode' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
            Logs(['url' => $url, 'post_data' => $post_data, 're' => $content,'err'=> $err], __FUNCTION__, __CLASS__);
            return false;
        }
    }
    /**
     * 合同拉取
     */
    public function pullContract($CON_NO = null)
    {
        if (empty($CON_NO)) {
            $CON_NO = I('CON_NO');
        }
        $oci = new MeBYModel();
        $sql = "SELECT * FROM ECOLOGY.FORMTABLE_MAIN_91 a left join ECOLOGY.HRMRESOURCE b on a.SQR = b.ID  WHERE DJBH='" . $CON_NO . "'";
        $ret = $oci->testQuery($sql);
        if ($ret) {
            $data = $ret [0];
            $searchCompanyNameSql = "SELECT fm.COMPANY FROM ECOLOGY.MMP fm WHERE ID = '" . $data['DFGSMCKESHANG'] . "'";
            $companyName = $oci->testQuery($searchCompanyNameSql);
            if ($companyName) {
                $data ['DFGSMCKESHANG'] = $companyName[0]['COMPANY'];
            }
        } else {
            $ret = $data = null;
            $sql = "SELECT * FROM ECOLOGY.FORMTABLE_MAIN_124 a left join ECOLOGY.HRMRESOURCE b on a.SQR = b.ID  WHERE DJBH='" . $CON_NO . "'";
            $ret = $oci->testQuery($sql);
            if ($ret) {
                $data = $ret [0];
                $searchCompanyNameSql = "SELECT fm.COMPANY FROM ECOLOGY.MMP fm WHERE ID = '" . $data['DFGSMCKESHANG'] . "'";
                $companyName = $oci->testQuery($searchCompanyNameSql);
                if ($companyName) {
                    $data ['DFGSMCKESHANG'] = $companyName[0]['COMPANY'];
                }
            }  else {
                $ret = $data = null;
                $sql = "SELECT * FROM ECOLOGY.FORMTABLE_MAIN_150 a left join ECOLOGY.HRMRESOURCE b on a.SQR = b.ID  WHERE DJBH='" . $CON_NO . "'";
                $ret = $oci->testQuery($sql);
                if ($ret) {
                    $data = $ret [0];
                    $searchCompanyNameSql = "SELECT fm.COMPANY FROM ECOLOGY.MMP fm WHERE ID = '" . $data['DFGSMCKESHANG'] . "'";
                    $companyName = $oci->testQuery($searchCompanyNameSql);
                    if ($companyName) {
                        $data ['DFGSMCKESHANG'] = $companyName[0]['COMPANY'];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 上传合同
     *
     */
    public function upload_contract()
    {
       
        $this->getDataDirectory();
        $edit_url = 'Supplier/upload_contract';
        $contract = D("TbCrmContract");
       
        if (IS_POST) {
            if (!$data = $contract->create($this->getParams(), 1)) {
                $this->ajaxReturn($contract->getError(), '上传合同失败', 0);
            } else {
                if ($contract->data($data)->add()) {
                    $this->ajaxReturn(null, '上传合同成功', 1);
                }
            }
        }

        if ($ID = $this->getParams()['ID']) {
            $result = $contract->find($ID);
            $this->assign('result', $result);
        }

        $this->display('contract');
    }

    /**
     * 查看合同
     *
     */
    public function show_contract()
    {
        $this->getDataDirectory();
        $contract = D("TbCrmContract");

        if ($ID = $this->getParams()['ID']) {
            $result = $contract->find($ID);
            $this->assign('result', $result);
        }

        $this->display();
    }

    /**
     * 更新合同
     *
     */
    public function update_contract()
    {
        $this->getDataDirectory();
        $contract = D("TbCrmContract");
        $edit_url = 'Supplier/update_contract';
        if (IS_POST) {
            $contract->create($_POST, 0);
            if ($contract->where('ID =' . $_POST['ID'])->save() === false) {
                $this->ajaxReturn($contract->getError(), '更新失败', 0);
            } else {
                $this->ajaxReturn('更新成功', '更新成功', 1);
            }
        }

        if ($ID = $this->getParams()['ID']) {
            $result = $contract->find($ID);
            $this->assign('result', $result);
        }
        $this->assign('edit_url', $edit_url);
        $this->display('contract');
    }

    /**
     * 查看
     *
     */
    public function show()
    {
        $ourCompany = BaseModel::conCompanyCd();
        $this->getDataDirectory();

        $model = D('TbCrmContract');
        #$ret = $model->find($this->getParams()['ID']);
        #修改为读取供应商表的名称字段
        $ret = $model->join("left join tb_crm_sp_supplier as tb_crm_sp_supplier on tb_crm_sp_supplier.SP_CHARTER_NO =  tb_crm_contract.SP_CHARTER_NO")
        ->where(['tb_crm_contract.ID'=> $this->getParams()['ID']])
        ->field('tb_crm_contract.*,tb_crm_sp_supplier.SP_NAME as REAL_SP_NAME')
        ->find();
        if($ret['REAL_SP_NAME']){
            $ret['SP_NAME'] = $ret['REAL_SP_NAME'];
            unset($ret['REAL_SP_NAME']);
        }
        $this->assign('contract_agreement', BaseModel::contractAgreement());
        $this->assign('result', $ret);
        $this->display();
    }

    public function get_province()
    {
        $data = BaseModel::getProvince(1);
        $info = [
            'msg' => 'success',
        ];
        $this->ajaxReturn($data, $info, 1);
    }

    public function get_city()
    {
        $parent_ids = $this->getParams()['id'];
        $data = BaseModel::getCity($parent_ids);
        $info = [
            'msg' => 'success',
        ];
        $this->ajaxReturn($data, $info, 1);
    }

    public function get_county()
    {
        $parent_ids = $this->getParams()['id'];
        $data = BaseModel::getCounty($parent_ids);
        $info = [
            'msg' => 'success',
        ];
        $this->ajaxReturn($data, $info, 1);
    }

    /**
     * 获取基础配置数据
     *
     */
    public function getDataDirectory()
    {
        $spTeamCd = BaseModel::spTeamCd();
        $spJsTeamCd = BaseModel::spJsTeamCd();
        $copanyTypeCd = BaseModel::conType();
        $spYearScaleCd = BaseModel::spYearScaleCd();
        $isAutoRenew = BaseModel::isAutoRenew();
        $country = BaseModel::getCountry();
        $this->assign('isAutoRenew', $isAutoRenew);
        $this->assign('spTeamCd', $spTeamCd);
        $this->assign('spJsTeamCd', $spJsTeamCd);
        $this->assign('copanyTypeCd', $copanyTypeCd);
        $this->assign('spYearScaleCd', $spYearScaleCd);
        $this->assign('country', $country);
    }

    /**
     * 文件上传
     *
     */
    public function upload_file()
    {
        if ($_FILES) {
            $fd = new FileUploadModel();
            if ($fd->uploadFile()) {
                exit(json_encode(['name' => $fd->save_name]));
            }
        }
    }

    /**
     * 供应商批量导入
     *
     */
    public function mult_import_contract()
    {
        $params = $this->getParams();
        if ($params ['check_contract_type'] == 0) {
            $this->assign('title', '供应商合同导入');
            // 供应商合同导入
            $im = new ImportMulSupplierContractModel();
            $ret = $im->import();
        } else {
            $this->assign('title', '客户合同导入');
            // 客户合同导入
            $im = new ImportMulCustomerContractModel();
            $ret = $im->import();
        }
        $error = '';
        if ($im->errorinfo) {
            foreach ($im->errorinfo as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $error .= $v;
                    }
                } else {
                    $error .= $value;
                }
            }
        }
        if ($ret ['state'] == 1) {
            $this->AjaxReturn('', L('上传成功'), 1);
            //$this->assign('show', true);
            //$this->assign('ret_supplier', $ret ['msg']);
        } else {
            $this->AjaxReturn('', L('上传失败') . $error, 0);
            //$this->assign('show', false);
            //$this->assign('errorinfo', $ret ['msg']);
        }

        //$this->display('Log/mul_import');
    }
}