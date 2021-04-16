<?php
import('ORG.Util.Page');// 导入分页类
class SupplierAction extends BaseAction
{
    public function _initialize()
    {
        parent::_initialize();
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        header('Access-Control-Allow-Origin: *');
        B('SYSOperationLog');
    }

    /**
     * 供应商列表
     * 
     */
    public function index()
    {
        $model = D('TbCrmSpSupplier');
        $contract = D('TbCrmContract');
        $data = $model->parseFieldsMap();
        $condition = $model->searchModel($this->getParams());
        $this->getDataDirectory();
        import('ORG.Util.Page');// 导入分页类
        $count = $model->relation(true)->where($condition)->count();
        $page = new Page($count, 20);
        $show = $page->show();
        $ret = $model->relation(true)->where($condition)->order('tb_crm_sp_supplier.AUDIT_STATE,tb_crm_sp_supplier.ID desc')->limit($page->firstRow.','.$page->listRows)->select();

        // 审核权限
        !isset($this->access['Supplier/audit']) or $this->assign('auditRule', 'Supplier/audit');
        $this->assign('spTeamCd', BaseModel::spTeamCd());
        $this->assign('spJsTeamCd', BaseModel::spJsTeamCd());
        $this->assign('allUserInfo', BaseModel::getAdmin());
        $this->assign('allCountryInfo', BaseModel::getCountryInfo());
        $this->assign('count', $count);
        $this->assign('result', $ret);
        $this->assign('model', $model);
        $this->assign('contract', $contract);
        $this->assign('pages', $show);
        $this->assign('params', $this->getParams());
        $this->display();
    }

    public function supplierList()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        if ($query ['sign'] == $this->auth($query ['_t'], $query ['rand'])) {
            $model = new TbCrmSpSupplierModel();
            $field = [
                'id',
                'SP_NAME as spName',
                'SP_RES_NAME as spResName',
                'SP_NAME_EN as spNameEn',
                'SP_RES_NAME_EN as spResNameEn'
            ];
            $count = $model->field($field)->count();
            $ret = $model->field($field)->select();
            $data ['pageData']        = $ret;
            $data ['totalCount']      = $count;
            $code = 2000;
            $message = L('success');
        } else {
            $code = 3000;
            $message = L('请求异常，认证失败');
        }

        $response = $this->formatOutput($code, $message, $data);
        $this->ajaxReturn($response, 'json');
    }

    private $_token = 'GSHOPPER_API_AUTHOR';

    /**
     * @param string $_t   时间戳
     * @param int    $rand 随机数
     * @return string
     */
    private function auth($_t, $rand)
    {
        if (time() - $_t < 30) {
            $r ['timeStamp'] = $_t;
        } else {
            $r ['timeStamp'] = time();
        }
        $r ['rand']      = $rand;
        $r ['token']     = $this->_token;
        sort($r, SORT_STRING);
        $str = implode($r);
        $sign = sha1($str);
        $sign = md5($sign);
        $sign = strtoupper($sign);

        return $sign;
    }
    
    /**
     * 新增供应商
     * 
     */
    public function newly_added()
    {
        $this->getDataDirectory();
        $params = $this->getParams();
        $params = $this->trimParmas($params);
        $edit_url = 'Supplier/newly_added';
        if (IS_POST) {
            $supplier = D('TbCrmSpSupplier');
            if ($this->search_supplier_by_sp_no($params['SP_CHARTER_NO'])) {
                exit(json_encode(['status' => 0, 'msg' => '该供应商已存在，请修改营业执照号', 'data' => '该供应商已存在，请修改营业执照号']));   
            }
            if ($_FILES) {
                // 图片上传
                $fd = new FileUploadModel();
                if ($_FILES['SP_ANNEX_ADDR'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR'])) {
                    $params ['SP_ANNEX_NAME'] = $fd->info [0]['savename'];
                    $params ['SP_ANNEX_ADDR'] = $fd->filePath;
                } else {
                    if (!$fd->error) $fd->error = '必须上传主体营业执照';
                    exit(json_encode(['data' => $fd->error, 'msg' => '新增供应商失败', 'status' => 0]));
                }
                if ($_FILES['SP_ANNEX_ADDR2'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR2'])) {
                    $params ['SP_ANNEX_NAME2'] = $fd->info [0]['savename'];
                }
            }
            $SP_TEAM_CD = $params['SP_TEAM_CD'];
            if ($SP_TEAM_CD) {
                $temp = null;
                foreach ($SP_TEAM_CD as $k => $v) {
                    $temp .= $v . ',';
                }
                $temp = rtrim($temp, ',');
            }
            if ($temp) {
                $params['SP_TEAM_CD'] = $temp;
            }
            if ($params['secretary_company_telephone']){
                $params['secretary_company_telephone'] = implode(',',$params['secretary_company_telephone']);
            }
            if ($params['agency_company_telephone']){
                $params['agency_company_telephone'] = implode(',',$params['agency_company_telephone']);
            }
            //过滤供应商名称前后空格
            if ($params['SP_NAME']) $params['SP_NAME'] = trim($params['SP_NAME']);
            if (!$data = $supplier->create($params, 1)) {
                //FileUploadModel::unlinkFile($params ['SP_ANNEX_NAME']);
                exit(json_encode(['data' => $supplier->getError(), 'msg' => '新增供应商失败', 'status' => 0]));
            } else {
                if ($id = $supplier->data($data)->add()) {
                    $log = A('Home/Log');
                    $m = new BaseModel();
                    $m->supplierSendMail($data);
                    if ($m->checkNeedAudit($data['SP_ADDR3'])) {
                        $message = '新增供应商成功，已发送邮件提醒法务审核!';
                    } else {
                        $message = '新增供应商成功，非大陆客户暂不需审核!';
                    }
                    //同步英文翻译配置 SP_NAME SP_RES_NAME_EN
                    $language_data[] = ['element' => $data['SP_NAME'], 'type' => 'N000920200', 'translation_content' => $data['SP_NAME_EN']];
                    if (!(new LanguageModel())->saveAllTrans($language_data)) {
                        exit(json_encode(['status' => 0, 'msg' => '新建供应商同步英文翻译配置失败', 'id' => $id]));
                    } else {
                        //英文语言刷新
                        $language = new LanguageModel();
                        $language->flushEnCache();
                    }
                    $log->index($data['SP_CHARTER_NO'], 0, $message);
                    exit(json_encode(['status' => 1, 'msg' => $message, 'id' => $id]));
                } else {
                    exit(json_encode(['status' => 0, 'msg' => '新增供应商失败']));
                }
            }
        }
        $this->assign('access', $this->access);
        $this->assign('must_need_upload_file', 1);
        $this->assign('title', '新增供应商');

        $this->display("add_supplier");
    }
    /**
     * 更新供应商
     * 
     */
    public function update_supplier()
    {
        $this->getDataDirectory();
        $supplier = D("TbCrmSpSupplier");
        $edit_url = 'Supplier/update_supplier';
        
        // 更新供应商
        if (IS_POST) {
            $_POST = $this->trimParmas($_POST);
            $ret = $supplier->updateSupplierOrCustomer();
            exit(json_encode($ret));
        }
        // 获取供应商数据
        if ($ID = $this->getParams()['ID']) {
            $result = $supplier->find($ID);
            $this->assign('result', $result);
        }
        $this->assign('access', $this->access);
        $this->assign('must_need_upload_file', 0);
        $this->assign('title', '更新供应商');
        $this->assign('edit_url', $edit_url);
        $this->display('add_supplier');
    }
    
    
    /**
     * 删除供应商
     * 
     */
    public function del_supplier()
    {
        $id = $this->getParams()['ID'];
        if ($id) {
            $model = D('TbCrmSpSupplier');
            $ret = $model->relation(true)->find($id);
            if ($ret['contracts']) {
                $data = '删除失败';
                $info = [
                    'msg' => '删除失败，该供应商已签订合同',
                ];
                $this->ajaxReturn($data, $info, 0);
            }
            if ($model->delete($id)) {
                $audit = M('_ms_forensic_audit', 'tb_');
                $audit->where('CRM_CON_TYPE = 0 and SP_CHARTER_NO = "' . $ret ['SP_CHARTER_NO'] . '"')->delete();
                $info = [
                    'msg' => '删除成功',
                ];
                $this->ajaxReturn($data, $info, 1);
            } else {
                $info = [
                    'msg' => '删除失败'
                ];
                $this->ajaxReturn($data, $info, 0);
            }
        }
        $info = [
            'msg' => '访问异常'
        ];
        $this->ajaxReturn($data, $info, 0);
    }
    
    /**
     * 法务审核
     * 
     */
    public function audit()
    {
        $supplier = D('TbCrmSpSupplier');
        $ret = $supplier->relation(true)->find($this->getParams()['ID']);
        $this->getDataDirectory();
        $nagetiveOptions = BaseModel::getNagetiveOptions();
        if (IS_AJAX) {
            $model = D("TbMsForensicAudit");
            $post = $this->getParams();
            // 如果有负面信息
            if ($post['IS_HAVE_NAGETIVE_INFO'] == 1) {
                $checkedNagetiveOptions = $post ['C_NAGETIVE_OPTIONS']; // 负面信息选项
                $list = explode(',', $checkedNagetiveOptions);
            }
            $temp = [];
            foreach ($list as $k => $v) {
                $temp ['TIME_' . $v] = $post ['TIME_' . $v];
                $temp ['DUC_' . $v] = $post ['DUC_' . $v];
            }
            
            $post ['C_NAGETIVE_VAL'] = json_encode($temp);
            
            if ($isok = $model->where('CRM_CON_TYPE = 0 and SP_CHARTER_NO = "' . $post['SP_CHARTER_NO'] .'"')->find()) exit(json_encode(['data' => '审核失败，重复审核', 'msg' => '审核失败，重复审核', 'status' => 0]));
            if (!$data = $model->create($post, 1)) {
                exit(json_encode(['data' => $model->getError(), 'msg' => '法务审核失败', 'status' => 0]));
            } else {
                if ($model->data($data)->add()) {
                    $supplier = M('_crm_sp_supplier', 'tb_');
                    $ret = $supplier->where('DATA_MARKING = 0 and SP_CHARTER_NO = "'. $post['SP_CHARTER_NO'] .'"')->find();
                    $ret ['AUDIT_STATE'] = 2;
                    $ret ['RISK_RATING'] = $post['RISK_RATING'];
                    $supplier->save($ret);
                    $sdata = [];
                    foreach ($data as $key => $value) {
                        if (!$value) $sdata [$key] = null;
                        $sdata [$key] = $value;
                    }
                    $log = A('Home/Log');
                    $log->index($data['SP_CHARTER_NO'], 0, '法务审核完成');
                    $supplier_id = M('sp_supplier', 'tb_crm_')->where(['SP_CHARTER_NO'=>$post['SP_CHARTER_NO']])->getField('ID');
                    exit(json_encode(['status' => 1, 'msg' => '法务审核完成','data' =>['id'=>$supplier_id]]));
                } else {
                    exit(json_encode(['data' => $model->getError(), 'status' => 0, 'msg' => '法务审核失败']));
                }
            }
        }
        $this->assign('isHaveNagetive', BaseModel::isHaveNagetive());
        $this->assign('riskRating', BaseModel::riskRating());
        $this->assign('currency', BaseModel::getCurrencyExtend()); // 币种
        $this->assign('creditGrade', BaseModel::getCreditGrade()); // 信用评级
        $this->assign('nagetiveOptions', $nagetiveOptions); // 负面信息选项
        $this->assign('auditGradeStandard', BaseModel::auditGradeStandard()); // 评级标准
        $this->assign('result', $ret);
        $this->display();
    }
    
    /**
     * 法务审核更新
     * 
     */
    public function update_audit()
    {   
        $supplier = D('TbCrmSpSupplier');
        $id = $this->getParams()['ID'];
        $ret = $supplier->relation(true)->find($id);
        $this->getDataDirectory();
        $nagetiveOptions = BaseModel::getNagetiveOptions();
        if (IS_POST) {
            $model = D("TbMsForensicAudit");
            $post = $this->getParams();
            // 如果有负面信息
            if ($post['IS_HAVE_NAGETIVE_INFO'] == 1) {
                $temp = [];
                $checkedNagetiveOptions = $post ['C_NAGETIVE_OPTIONS']; // 负面信息选项
                $list = explode(',', $checkedNagetiveOptions);
                foreach ($list as $k => $v) {
                    $temp ['TIME_' . $v] = $post ['TIME_' . $v];
                    $temp ['DUC_' . $v] = $post ['DUC_' . $v];
                }
                $post ['C_NAGETIVE_VAL'] = json_encode($temp);
            } else {
                $post ['C_NAGETIVE_VAL'] = '';
            }
            $data = $model->create($post, 2);
            if ($model->save()) {
                $log = A('Home/Log');
                $log->index($data['SP_CHARTER_NO'], 0, '更新审核完成');
                exit(json_encode(['status' => 1, 'msg' => '更新审核完成', 'ID' => $this->getParams()['parentId']]));
            } else {
                exit(json_encode(['data' => $model->getError(), 'status' => 0, 'msg' => '更新审核失败']));
            }
        }
        $this->assign('isHaveNagetive', BaseModel::isHaveNagetive());
        $this->assign('riskRating', BaseModel::riskRating());
        $this->assign('currency', BaseModel::getCurrencyExtend()); // 币种
        $this->assign('creditGrade', BaseModel::getCreditGrade()); // 信用评级
        $this->assign('nagetiveOptions', $nagetiveOptions); // 负面信息选项
        $this->assign('auditGradeStandard', BaseModel::auditGradeStandard()); // 评级标准
        $this->assign('result', $ret);
        $this->assign('parentId', $id);
        $this->display();
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
            $params = $this->getParams();
            if ($_FILES) {
                // 图片上传
                $fd = new FileUploadModel();
                if ($_FILES['SP_ANNEX_ADDR1'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR1'])) {
                    foreach ($fd->info as $key => $value) {
                        $finfo [] = [
                            'contract_agreement' => $params ['contract_agreement'][$key],
                            'file_name' => $value ['savename'],
                            'upload_name' => $_FILES['SP_ANNEX_ADDR1']['name'][$key]
                        ];
                    }
                    $params ['SP_ANNEX_ADDR1'] = json_encode($finfo);
                } else {
                    $this->AjaxReturn($fd->error, L('新增供应商失败'), 0);
                }
                if ($_FILES['SP_ANNEX_ADDR2'] and $fd->saveFile($_FILES['SP_ANNEX_ADDR2'])) {
                    $params ['SP_ANNEX_ADDR2'] = $fd->info [0]['savename'];
                }
            }
            //查询合同表中是否已有该合同
            $model = M('_crm_contract', 'tb_');
            $con = $model->where('CON_NO = "' . $params['CON_NO'] . '"')->find();
            if ($con) $this->ajaxReturn('编号为：' . $params['CON_NO'] .' 的合同已存在，请修改', '', 0);
            //根据模型进行数据验证
            if (!$data = $contract->create($params, 1)) {
                $this->ajaxReturn($contract->getError(), '上传合同失败', 0);
            } else {
                if ($params ['NEED_ADD_AUDIT'] == 0) {
                    
                } else {
                    //供应商审核信息检查
                    $model = M('_ms_forensic_audit', 'tb_');
                    $ret = $model->where('SP_CHARTER_NO = "' . $params['SP_CHARTER_NO'] . '"')->find();
                    if (!$ret) $this->ajaxReturn('该供应商审核未通过，请核对', '上传合同失败', 0);
                }
                //手机号加密
                if ($data ['CON_PHONE']) {
                    $data ['BAK_CON_PHONE'] = $data ['CON_PHONE'];
                    $con_phone_ret = CrypMobile::enCryp($data ['CON_PHONE']);
                    if ($con_phone_ret ['code'] == 200) $data ['CON_PHONE'] = $con_phone_ret ['data'];
                }
                //固话加密
                if ($data ['CON_TEL']) {
                    $data ['BAK_CON_TEL'] = $data ['CON_TEL'];
                    $con_tel_ret = CrypMobile::enCryp($data ['CON_TEL']);
                    if ($con_tel_ret ['code'] == 200) $data ['CON_TEL'] = $con_tel_ret ['data'];
                }
                //写进数据库
                if ($result = $contract->relation(true)->add($data)) {
                    $log = A('Home/Log');
                    $log->index($params['SP_CHARTER_NO'], 0, '上传合同成功');
                    $this->ajaxReturn($result, '上传合同成功', 1);
                } else {
                    $this->ajaxReturn($contract->getError(), '上传合同失败', 0);
                }
            }
        }
        if ($ID = $this->getParams()['ID']) {
            $result = $contract->find($ID);
            $this->assign('result', $result);
        }
        $this->assign('must_need_upload_file', 1);
        $this->assign('edit_url', $edit_url);
        $this->assign('chinaMainlandAndHMT', BaseModel::regionalClassification());
        $this->assign('contract_agreement', BaseModel::contractAgreement());
        $this->assign('isSelectInfo', false);
        $this->assign('title', '上传合同');
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
        $this->assign('contract_agreement', BaseModel::contractAgreement());
        $this->assign('title', '合同详情');
        
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
            if (strtotime($_POST['START_TIME']) >= strtotime($_POST['END_TIME'])) {
                $this->ajaxReturn('合同开始时间不得大于结束时间', '更新失败', 0);
            }
            $ret = $contract->find($_POST['ID']);
            if ($_FILES) {
                // 图片上传
                $fd = new FileUploadModel();
                if ($_FILES['SP_ANNEX_ADDR1']) {
                    if ($fd->saveFile($_FILES['SP_ANNEX_ADDR1'])) {
                        foreach ($fd->info as $key => $value) {
                            $finfo [] = [
                                'contract_agreement' => $_POST ['contract_agreement'][$key],
                                'file_name' => $value ['savename'],
                                'upload_name' => $_FILES['SP_ANNEX_ADDR1']['name'][$key]
                            ];
                        }
                        // 数据库中已存在的
                        $already_existed = json_decode($ret ['SP_ANNEX_ADDR1'], true);
                        // 经过页面编辑后剩下的文件
                        $already_exist = explode(",", $_POST ['already_exist']);
                        // 将页面编辑后删除掉的数据从数据库中存有的数据中剔除掉
                        foreach ($already_existed as $k => &$v) {
                            if (!in_array($v ['file_name'], $already_exist)) {
                                unset($already_existed[$k]);
                            }
                            foreach ($finfo as $s => $j) {
                                if ($j ['upload_name'] == $v ['upload_name']) unset($already_existed[$k]);
                            }
                        }
                        $_POST ['SP_ANNEX_ADDR1'] = json_encode(array_merge($finfo, (array)$already_existed));
                    }else {
                        $this->AjaxReturn($fd->error, L('文件上传失败'), 0);
                    }
                } else {
                    $already_existed = json_decode($ret ['SP_ANNEX_ADDR1'], true);
                    if ($_POST ['already_exist']) {
                        $already_exist = explode(",", $_POST ['already_exist']);
                        foreach ($already_existed as $k => &$v) {
                            if (!in_array($v ['file_name'], $already_exist)) unset($already_existed[$k]);
                        }
                        $_POST ['SP_ANNEX_ADDR1'] = json_encode($already_existed);
                    } else {
                        unset($_POST['SP_ANNEX_ADDR1']);
                    }
                }
                if ($_FILES['SP_ANNEX_ADDR2']) {
                    if ($fd->saveFile($_FILES['SP_ANNEX_ADDR2'])) {
                        $_POST ['SP_ANNEX_ADDR2'] = $fd->info [0]['savename'];
                    } else {
                        unset($_POST['SP_ANNEX_ADDR2']);
                    }
                } else {
                    unset($_POST['SP_ANNEX_ADDR2']);
                }
            } else {
                $already_existed = json_decode($ret ['SP_ANNEX_ADDR1'], true);
                if ($_POST ['already_exist']) {
                    $already_exist = explode(",", $_POST ['already_exist']);
                    foreach ($already_existed as $k => &$v) {
                        if (!in_array($v ['file_name'], $already_exist)) unset($already_existed[$k]);
                    }
                    $_POST ['SP_ANNEX_ADDR1'] = json_encode($already_existed);
                } else {
                    $_POST['SP_ANNEX_ADDR1'] = NULL;
                }
                unset($_POST ['SP_ANNEX_ADDR2']);
            }
            // 是否是长期合同，1为长期合同
            if ($_POST['CONTRACT_TYPE'] == 'N001800200') {
                $_POST['END_TIME'] = Null;
            }
            if ($_POST ['CON_PHONE']) {
                $_POST ['BAK_CON_PHONE'] = $_POST ['CON_PHONE'];
                $con_phone_ret = CrypMobile::enCryp($_POST ['CON_PHONE']);
                if ($con_phone_ret ['code'] == 200) {
                    $_POST ['CON_PHONE'] = $con_phone_ret ['data'];
                }
            }
            if ($_POST ['CON_TEL']) {
                $_POST ['BAK_CON_TEL'] = $_POST ['CON_TEL'];
                $con_tel_ret = CrypMobile::enCryp($_POST ['CON_TEL']);
                if ($con_tel_ret ['code'] == 200) {
                    $_POST ['CON_TEL'] = $con_tel_ret ['data'];
                }
            }
            $data = $contract->create($_POST, 0);
            if ($contract->save($data) === false) {
                $this->ajaxReturn($contract->getError(), '更新失败', 0);
            } else {
                $log = A('Home/Log');
                $log->index($_POST['SP_CHARTER_NO'], 0, '更新合同成功');
                $this->ajaxReturn($_POST ['ID'], '更新成功', 1);
            }
        }
        if ($ID = $this->getParams()['ID']) {
            $result = $contract->find($ID);
            $this->assign('result', $result);
        }
        $this->assign('is_hidden', $this->getParams()['is_hidden']);
        $this->assign('must_need_upload_file', 0);
        $this->assign('chinaMainlandAndHMT', BaseModel::regionalClassification());
        $this->assign('isSelectInfo', true);
        $this->assign('edit_url', $edit_url);
        $this->assign('title', '更新合同');
        $this->assign('contract_agreement', BaseModel::contractAgreement());
        $this->display('contract');
    }
    
    /**
     * 根据执照号查询是否有相应的供应商
     * 
     */
    public function search_supplier_by_sp_no($spNo)
    {
        $model = D('TbCrmSpSupplier');
        $ret = $model->relation(true)->where('SP_CHARTER_NO="' . $spNo . '" and DATA_MARKING = 0')->find();
        if ($ret) return $ret;
        return false;
    }

    /**
     * 根据ID查询是否有相应的供应商
     * 
     */
    public function search_supplier_by_id($ID)
    {
        $model = D('TbCrmSpSupplier');
        $ret = $model->relation(true)->where(['ID' => $ID])->find();
        if ($ret) return $ret;
        return false;
    }

    public function adjustSupplierData($res)
    {
        $res['SP_YEAR_SCALE_CD_val'] = htmlspecialchars_decode(BaseModel::spYearScaleCd()[$res['SP_YEAR_SCALE_CD']]); // 年业务规模
        $res['cooperative_rating_val'] = BaseModel::getCooperativeRating()[$res['cooperative_rating']]; // 合作评级
        $res['RISK_RATING_val'] = BaseModel::riskRating()[$res['RISK_RATING']]; // 风险评级
        $res['register_address'] = BaseModel::getLocalName()[$res['SP_ADDR1']] . '-' . BaseModel::getLocalName()[$res['SP_ADDR3']] . '-' . BaseModel::getLocalName()[$res['SP_ADDR4']]; // 注册地址
        $res['office_address'] = BaseModel::getLocalName()[$res['SP_ADDR5']] . '-' . BaseModel::getLocalName()[$res['SP_ADDR7']] . '-' . BaseModel::getLocalName()[$res['SP_ADDR8']]; // 办公地址
        $res['SP_TEAM_CD_val'] = getCodeValStr($res['SP_TEAM_CD']); // 采购团队
        $res['COPANY_TYPE_CD_val'] = getCodeValStr($res['COPANY_TYPE_CD']); // 企业类型
        return CodeModel::autoCodeOneVal($res, ['SP_JS_TEAM_CD', 'SALE_TEAM']);
    }

    
    /**
     * 动态加载供应商模块
     * 
     */
    public function autoload_supplier()
    {
        if (IS_AJAX || IS_POST) {
            // 如果查询到供应商，则加载显示供应商模块
            if (!empty($this->getParams()['supplier_id']) && $ret = $this->search_supplier_by_id($this->getParams()['supplier_id'])) {
                if (empty($ret)) {
                    $this->ajaxReturn('未查询到该供应商', '', 0);
                } elseif (strval($ret['AUDIT_STATE']) === '1') {
                    $this->ajaxReturn('当前id对应的公司（供应商/B2B客户）未审核，请先审核', $ret, 0);
                } else {
                    $ret = $this->adjustSupplierData($ret);
                    $this->ajaxReturn('已获取到供应商信息', $ret, 1);
                }
            }
            elseif (!empty($this->getParams()['sp_charter_no']) && $ret = $this->search_supplier_by_sp_no($this->getParams()['sp_charter_no'])) {
                $this->ajaxReturn('已获取到供应商信息', $ret, 1);
            }
            else {// 未查询到供应商，则显示新增供应商模块
                $this->ajaxReturn('未查询到该供应商', '', 0);
            }  
        } else {
            $this->ajaxReturn('异常请求', '', 0);
        }
    }
    
    /**
     * 查看
     * 
     */
    public function show()
    {
        $ourCompany = BaseModel::conCompanyCd();
        $this->getDataDirectory();
        
        $model = D('TbCrmSpSupplier');
        $ret = $model->relation(true)->where('ID = "' . $this->getParams()['ID'] . '"')->find();
        $ret['account_currency'] = $ret['ACCOUNT_CURRENCY'];
        $ret['account_type'] = $ret['ACCOUNT_TYPE'];
        $ret['SWIFT_CODE'] = $ret['contracts'][0]['SWIFT_CODE'];
        $ret['SP_BANK_CD'] = $ret['contracts'][0]['SP_BANK_CD'];
        $ret['collection_account_name'] = $ret['contracts'][0]['collection_account_name'];
        $ret['BANK_ACCOUNT'] = $ret['contracts'][0]['BANK_ACCOUNT'];
        $ret = CodeModel::autoCodeTwoVal([$ret], ['account_currency', 'account_type'])[0];
        $this->assign('allUserInfo', BaseModel::getAdmin());
        $this->assign('ourCompany', $ourCompany);
        $this->assign('result', $ret);
        $this->assign('audit', $ret['audit']);
        $this->assign('model', $model);
        $this->assign('title', '供应商详情');
        $logField = 'ORD_HIST_REG_DTTM, ORD_STAT_CD, ORD_HIST_WRTR_EML, ORD_HIST_HIST_CONT';
        $ModelLog = M('ms_ord_hist', 'sms_');
        $logList = $ModelLog->field($logField)->where('ORD_NO = "'. $ret['SP_CHARTER_NO'] .'"')->order('ORD_HIST_SEQ desc')->select();
        $this->assign('logList', $logList);
        $this->assign('contract_agreement', BaseModel::contractAgreement());
        
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
     * 合同下载
     * 
     */
    public function contract_download()
    {
        // SP_ANNEX_ADDR1 合同地址
        $model = M('_crm_contract', 'tb_');
        $ret = $model->where("id = %d", [$_GET['ID']])->find();
        $file_name = $_GET ['name'];
        $type = strval($_GET['type']);
        if ($ret['SP_ANNEX_ADDR3'] && $type === '3') {
            $data = json_decode($ret ['SP_ANNEX_ADDR3'], true);
            $data = array_column($data, 'file_name');
            if (in_array($file_name, $data)) {
                $fd = new FileDownloadModel();
                $fd->fname = $file_name;
                try{
                    if (!$fd->downloadFile()) {
                        $this->error("文件不存在");
                    }
                }catch (exception $e) {
                    $this->error('文件不存在');
                }
            }
        }
        elseif ($ret['SP_ANNEX_ADDR4'] && $type === '4') {
            $data = json_decode($ret ['SP_ANNEX_ADDR4'], true);
            $data = array_column($data, 'file_name');
            if (in_array($file_name, $data)) {
                $fd = new FileDownloadModel();
                $fd->fname = $file_name;
                try{
                    if (!$fd->downloadFile()) {
                        $this->error("文件不存在");
                    }
                }catch (exception $e) {
                    $this->error('文件不存在');
                }
            }
        }
        elseif ($ret ['SP_ANNEX_ADDR1']) {
            $data = json_decode($ret ['SP_ANNEX_ADDR1'], true);
            $data = array_column($data, 'file_name');
            if (in_array($file_name, $data)) {
                $fd = new FileDownloadModel();
                $fd->fname = $file_name;
                try{
                    if (!$fd->downloadFile()) {
                        $this->error("文件不存在");
                    }
                }catch (exception $e) {
                    $this->error('文件不存在');
                }
            }
        }
        return false;
    }

    /**
     * 下载关联公司营业执照
     *
     */
    public function company_file_download()
    {
        // SP_ANNEX_ADDR1 合同地址
        $model = M('_crm_sp_supplier', 'tb_');
        $ret = $model->where('ID = ' . $_GET['ID'])->find();
        if ($ret and $ret['SP_ANNEX_NAME2']) {
            $fd = new FileDownloadModel();
            $fd->fname = $ret['SP_ANNEX_NAME2'];
            try{
                if (!$fd->downloadFile()) {
                    $this->error("文件不存在");
                }
            }catch (exception $e) {
                $this->error('文件不存在');
            }
        }

        return false;
    }
    
    /**
     * 下载主体营业执照
     * 
     */
    public function business_license_download()
    { 
        // SP_ANNEX_ADDR1 合同地址
        $model = M('_crm_sp_supplier', 'tb_');
        $ret = $model->where('ID = ' . $_GET['ID'])->find();
        if ($ret and $ret['SP_ANNEX_NAME']) {
            $fd = new FileDownloadModel();
            $fd->fname = $ret['SP_ANNEX_NAME'];
            try{
                if (!$fd->downloadFile()) {
                    $this->error("文件不存在");
                }
            }catch (exception $e) {
                $this->error('文件不存在');
            }
        }
        
        return false;
    }
    
    /**
     * 名片下载
     * 
     */
    public function business_card_download()
    {
        // SP_ANNEX_ADDR1 合同地址
        $model = M('_crm_contract', 'tb_');
        $ret = $model->where('ID = ' . $_GET['ID'])->find();
        if ($ret and $ret['SP_ANNEX_ADDR2']) {
            $fd = new FileDownloadModel();
            $fd->fname = $ret['SP_ANNEX_ADDR2'];
            try{
                if (!$fd->downloadFile()) {
                    $this->error("文件不存在");
                }
            }catch (exception $e) {
                $this->error('文件不存在');
            }
        }
        
        return false;
    }
    
    /**
     * 供应商批量导入
     * 
     */
    public function mult_import_supplier()
    {
        $im = new ImportMulSupplierModel();
        $ret = $im->import();
        $this->assign('title', '批量导入供应商');
        if ($ret ['state'] == 1) {
            $this->assign('show', true);
            $this->assign('ret_supplier', $ret ['msg']);
        } else {
            $this->assign('show', false);
            $this->assign('errorinfo', $ret ['msg']);
        }
        
        $this->display('Log/mul_import');
    }
    
    /**
     * 测试基类
     * 
     */
    public function test_import_excel()
    {
        $im = new BaseImportExcelModel();
        $ret = $im->import();
        echo '<pre/>';var_dump($im->data);exit;
    }

    /**
     * 模板下载
     *
     */
    public function template_download()
    {
        $file_name = $_GET ['name'];
        $fd = new FileDownloadModel();
        $fd->path = APP_PATH . 'Tpl/Home/Supplier';
        $fd->fname = $file_name;
        try{
            if (!$fd->downloadFile()) {
                $this->error("文件不存在");
            }
        }catch (exception $e) {
            $this->error('文件不存在');
        }
    }

    public function supplier_options_list()
    {
        $model = D('TbCrmSpSupplier');
        $condition = $model->searchModel($this->getParams());
        $count = $model->where($condition)->count();
        $page_size = I('get.size',50);
        $page       = new Page($count,$page_size);// 实例化分页类 传入总记录数和每页显示的记录数
        $data['page'] = [
            'total'          => $count,
            'total_page'     => $page->get_totalPages(),
            'per_page_size'  => $page_size,
            'now_page'       => I('get.p',1),
        ];
        $list = $model
                ->field("ID, SP_NAME")
                ->where($condition)
                ->order('tb_crm_sp_supplier.AUDIT_STATE,tb_crm_sp_supplier.ID desc')
                ->limit($page->firstRow.','.$page->listRows)->select();
        $data['list'] = $list;
        return $this->ajaxSuccess($data);
    }

    /**
     * 供应商列表数据导出
     * @author Redbo He
     * @date 2021/3/16 16:11
     */
    public  function supplier_export()
    {
        #( CRM_CON_TYPE = 0 AND SP_CHARTER_NO='N001243900' )
        # DATA_MARKING = 0  供应硬

        $model = D('TbCrmSpSupplier');
        $conditions = [];
        # 仅查所有的供应商
        $sp_addr1 = I('get.sp_addr1','1'); # 默认国家 中国
        $sp_addr3 = I('get.sp_addr3','507'); # 默认相关

        # 只查询供应商

        $conditions["tb_crm_sp_supplier.DATA_MARKING"] = ['eq',0];
        if($sp_addr1) {
            $conditions["tb_crm_sp_supplier.SP_ADDR1"] = ['eq',$sp_addr1];
        }
        if($sp_addr3) {
            $conditions["tb_crm_sp_supplier.SP_ADDR3"] = ['eq',$sp_addr3];
        }

        $result = $model
                ->field([
                    'tb_crm_sp_supplier.ID',
                    'tb_crm_sp_supplier.SP_NAME',
                    'tb_crm_sp_supplier.SP_RES_NAME',
                    'tb_crm_sp_supplier.SP_NAME_EN',
                    'tb_crm_sp_supplier.SP_RES_NAME_EN',
                    'tb_crm_sp_supplier.SP_CHARTER_NO',
                    'tb_crm_sp_supplier.DATA_MARKING',
                    'tb_crm_sp_supplier.SP_TEAM_CD',
                    'tb_crm_sp_supplier.SP_ADDR1',
                    'tb_crm_sp_supplier.SP_ADDR2',
                    'tb_crm_sp_supplier.SP_ADDR3',
                    'tb_crm_sp_supplier.SP_ADDR4',
                    'tb_crm_sp_supplier.SP_ADDR5',
                    'tb_crm_sp_supplier.SP_ADDR6',
                    'tb_crm_sp_supplier.SP_ADDR7',
                    'tb_crm_sp_supplier.SP_ADDR8',
                    'tb_crm_sp_supplier.CREATE_USER_ID',
                    'tb_ms_forensic_audit.EST_TIME',
                    'tb_ms_forensic_audit.SUB_CAPITAL',
                    'tb_ms_forensic_audit.LG_REP',
                    'tb_ms_forensic_audit.SHARE_NAME',
                    'tb_ms_forensic_audit.CREDIT_SCORE',
                    'tb_ms_forensic_audit.CREDIT_GRADE',
                    'tb_ms_forensic_audit.RISK_RATING',
                    'tb_ms_forensic_audit.CURRENCY',
                    'COUNT(tb_crm_contract.id) as contract_count'
                    ])
                ->join(" left join tb_ms_forensic_audit on tb_crm_sp_supplier.SP_CHARTER_NO = tb_ms_forensic_audit.SP_CHARTER_NO 
         AND tb_ms_forensic_audit.CRM_CON_TYPE = 0 ")
                ->join(" LEFT JOIN tb_crm_contract on tb_crm_sp_supplier.SP_CHARTER_NO =  tb_crm_contract.SP_CHARTER_NO  AND  tb_crm_contract.CRM_CON_TYPE = 0 
		AND  tb_crm_contract.SP_CHARTER_NO is not null and tb_crm_contract.SP_CHARTER_NO != \"\" ")
                ->where($conditions)
                ->group("tb_crm_sp_supplier.ID")
                ->order("tb_crm_sp_supplier.ID DESC")
                ->having("contract_count >=1")
                ->select();
        if($result)
        {
            # 币种与 地址数据从新组装
            $address_fields = ["SP_ADDR1", "SP_ADDR2", "SP_ADDR3", "SP_ADDR4", "SP_ADDR5", "SP_ADDR6", "SP_ADDR7", "SP_ADDR8"];
            $address_ids  = [];
            foreach ($address_fields as $field) {
                $field_res= array_unique(array_column($result,$field));
                if($field_res)
                {
                    $address_ids = array_unique(array_merge($field_res, $address_ids));
                }
            }
            $address_ids = array_filter($address_ids);

            $address_map = [];
            if($address_ids) {
                $crm_site_model = M("crm_site","tb_");
                $address_map= $crm_site_model->where([
                    "ID" => ['in', $address_ids]
                ])->getField("ID,NAME");
            }
            $result   = CodeModel::autoCodeTwoVal($result, ["CURRENCY","CREDIT_GRADE"]);
            $spTeamCd =  BaseModel::spTeamCd();
            $create_user_ids = array_filter( array_column($result,'CREATE_USER_ID'));
            $admin_users = [];
            if($create_user_ids) {
                $admin_users = M("admin","bbm_")->where([
                    "M_ID" => ["in", $create_user_ids]
                ])->getField("M_ID,M_NAME");
                ;
            }


            $riskRating = BaseModel::riskRating();
            foreach ($result as $k => $item) {
                $result[$k]['registered_address'] = $address_map[$item['SP_ADDR1']] . $address_map[$item['SP_ADDR3']] . $address_map[$item['SP_ADDR4']];;
                $result[$k]['work_address']       = $address_map[$item['SP_ADDR5']] . $address_map[$item['SP_ADDR7']] . $address_map[$item['SP_ADDR8']];
                # SUB_CAPITAL
                # CURRENCY_val
                $result[$k]['SUB_CAPITAL_val'] = '';
                $result[$k]['RISK_RATING_VAL'] = isset($riskRating['RISK_RATING']) ? $riskRating['RISK_RATING'] : '';
                $result[$k]['EST_TIME'] = isset($item['EST_TIME']) ? date("Y-m-d", strtotime($item['EST_TIME'])) : '';
                if($item['SUB_CAPITAL']) {
                    $result[$k]['SUB_CAPITAL_val'] =  $result[$k]['CURRENCY_val'] . ' ' . $item['SUB_CAPITAL'];
                }
                
                if($item['SP_TEAM_CD']) {
                    $sp_teams = explode(",", $item['SP_TEAM_CD']);
                    $sp_team_names = array_map(function($v) use($spTeamCd){
                        return isset($spTeamCd[$v]) ? $spTeamCd[$v] : '';
                    }, $sp_teams);
                    $result[$k]['SP_TEAM_CD_val'] = implode(",", $sp_team_names);
                }

                # CREATE_USER_ID
                $result[$k]['CREATE_USER_NAME'] = '';
                if($item['CREATE_USER_ID']) {
                    $result[$k]['CREATE_USER_NAME'] = isset($admin_users[$item['CREATE_USER_ID']]) ? $admin_users[$item['CREATE_USER_ID']] : "";
                }
                #
            }
        }
        $export_data = [$result];
        $quotation_export = new SupplierExport();
        $quotation_export->setData($export_data)
            ->download();






    }
}