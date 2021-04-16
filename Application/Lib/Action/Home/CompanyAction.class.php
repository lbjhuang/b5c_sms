<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/28
 * Time: 11:20
 */

class CompanyAction extends BaseAction {

    public $companyService;
    public $model;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \Model();
        $this->companyService = new CompanyService($this->model);
    }

    public function qualificationList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->companyService->getQualificationList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function saveQualification()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateQualificationData($request_data);
            } else {
                throw new Exception(L('请求为空'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->companyService->saveQualification($request_data);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateQualificationData($data) {
        //需求9775-资质证照管理移除到期日、资质附件必填限制
        /*if (!$data['is_long_time']) {
            if (!$data['expire_date']) {
                throw new Exception(L('到期日 字段必须填写'));
            }
        }*/
        if ($data['issue_date']) {
            if ($data['issue_date'] > date('Y-m-d',time())) {
                throw new Exception(L('发证日必须小于等于当前日期'));
            }
        }
        $rules = [
            'our_company_code'         => 'required|size:10',
            'name'                     => 'required',
            //'attachment'               => 'required|array'
        ];
        $custom_attributes = [
            'our_company_code'         => '我方公司',
            'name'                     => '资质名称',
            //'attachment'               => '资质附件',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    public function deleteQualification()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (!$request_data['id']) {
                throw new Exception(L('请求为空'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $this->companyService->deleteQualification($request_data['id']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //资质列表
    public function qualification_list() {
        $this->display();
    }

    //资质新增
    public function qualification_add() {
        $this->display();
    }

    //资质编辑
    public function qualification_edit() {
        $this->display();
    }

    /*我方公司信息管理========================================================================================*/
    public function managementlist()
    {
        $this->display();
    }

    // 初始化我方公司数据
    public function initCompanyData()
    {
        header("content-type:text/html;charset=utf-8");
        $this->companyService->fixShareholderTruename();
        echo "success"; // 修复初始化时，股东（个人）信息错误
        /*try {
            $filePath = $_FILES['file']['tmp_name'];
            vendor("PHPExcel.PHPExcel");
            $objPHPExcel = new PHPExcel();
            $PHPReader = new PHPExcel_Reader_Excel2007();
            $PHPReader->canRead($filePath);           
            $PHPExcel = $PHPReader->load($filePath); //读取Excel文件  
            $sheet = $PHPExcel->getSheet(0); //读取excel文件中的第一个工作表
            $allColumn = $sheet->getHighestColumn(); //取得最大的列号
            $allRow = $sheet->getHighestRow(); //取得最大的行号
            if (!$allColumn || !$allRow) {
                throw new Exception(L('文件获取数据有误'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $excel_model = $PHPExcel->getActiveSheet();
            $this->model->startTrans();
            $this->companyService->getInitCompanyData($excel_model, $allColumn, $allRow);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);*/
    }

    // 列表接口
    public function companyManagementList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->companyService->companyManagementList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }


    /**
     *  导出
     */
    public function exportManagement(){
        try {
            if (!isset($_POST['export_params']) || empty($_POST['export_params'])){
                throw new Exception('参数异常');
            }
            $request_data = json_decode($_POST['export_params'],true);
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->companyService->companyManagementList($request_data,true);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }


    // 新增/编辑我方公司信息 
    public function create()
    {
        if ($_POST) {
            try {
                $request_data = DataModel::getDataNoBlankToArr();
                if ($request_data) {
                    $this->validateOurCompanyData($request_data);
                } else {
                    throw new Exception(L('请求为空'));
                }
                $res = DataModel::$success_return;
                $res['code'] = 200;
                $this->model->startTrans();
                $res['data'] = $this->companyService->saveCompanyInfo($request_data);
                $this->model->commit();
            } catch (Exception $exception) {
                $this->model->rollback();
                $res = $this->catchException($exception);
            }
            $this->ajaxReturn($res);
        }
        $this->display();
    }
    // 我方公司详情
    public function detail()
    {
        if ($_POST) {
            try {
                $request_data = DataModel::getDataNoBlankToArr();
                if ($request_data['id']) {
                    $res = DataModel::$success_return;
                    $res['data'] = $this->companyService->getCompanyDetail($request_data);
                } else {
                    $res = DataModel::$error_return;
                    throw new Exception(L('请求为空'));
                }
            } catch (Exception $exception) {
                $res = $this->catchException($exception);
            }
            $this->ajaxReturn($res);
        }
        $this->display();
    }
    // 根据真名获取花名
    public function getAliasNameByTrueName()
    {
        $name   = I('request.EMP_NM');
        $res = DataModel::$success_return;
        if (!$name) {
            $res = DataModel::$error_return;
            $res['info'] = '请求名称不可为空';
        } else {
            $res['data'] = M('empl', 'tb_hr_')->where(['EMP_NM'=>['eq',"{$name}"]])->getField('EMP_SC_NM');
        }
        $this->ajaxReturn($res);
    }

    // 我方公司信息保存校验
    private function validateOurCompanyData($data) {
        if ($data['company_business_status_cd'] === 'N002950001') {
            if (!$data['oa_no']) {
                throw new Exception(L('OA编号字段必须填写'));
            }
            if (!$data['reg_country'] && !$data['reg_province'] && !$data['reg_city']) {
                throw new Exception(L('注册区域字段至少需要填写注册国家'));
            }
            if (!$data['reg_address']) {
                throw new Exception(L('注册区域字段必须填写'));
            }
            if (!$data['legal_name']) {
                throw new Exception(L('法定代表人/董事/负责人字段必须填写'));
            }
        }
        $rules = [
            'our_company_name'              => 'required',
            'register_time'              => 'required',
            'company_no'              => 'required',
            'company_business_status_cd'    => 'required|size:10',
        ];
        $custom_attributes = [
            'our_company_name'              => '我方公司名称',
            'register_time'              => '注册时间',
            'company_no'              => 'Company No.',
            'company_business_status_cd'    => '工商登记状态',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     * 获取地址
     */
    public function get_area() {
        $parent_no  = I('request.parent_no');
        $is_id = I('is_id');
        if (strval($is_id) === 'Y' && $parent_no) {
            // 表明parent_no传的是id,而不是area_no，需要根据id获取area_no
            $address_info = (new AreaModel())->getAreaById($parent_no);
            $parent_no = $address_info['area_no'];
        }
        if (!$parent_no) {
            $country_ids = $this->companyService->getRegisteredCountryIds();
            $address    = (new AreaModel())->getRegisteredCountry($country_ids);
        } else {
            $address    = (new AreaModel())->getChildrenArea($parent_no);
        }
        $this->ajaxReturn(['data'=>$address,'msg'=>'','code'=>2000]);
    }

    /*我方公司信息管理================================================================================ end*/


    public function getSupplier(){
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['COPANY_TYPE_CD'])){
            $this->ajaxReturn(['data'=>[],'msg'=>'参数异常','code'=>40000]);
        }
        if ($request_data['COPANY_TYPE_CD'] == 'N001190902'){
            $field = 'ID as supplier_id,SP_NAME as sp_name,secretary_company_telephone as company_telephone';
        }else if ($request_data['COPANY_TYPE_CD'] == 'N001190903'){
            $field = 'ID as supplier_id,SP_NAME as sp_name,agency_company_telephone as company_telephone';
        }else{
            $this->ajaxReturn(['data'=>[],'msg'=>'参数异常','code'=>400001]);
        }
        $list = M('sp_supplier','tb_crm_')
            ->where(array('COPANY_TYPE_CD'=>array('like','%'.$request_data['COPANY_TYPE_CD'].'%')))
            ->field($field)
            ->select();
        $this->ajaxReturn(['data'=>$list,'msg'=>'','code'=>2000]);
    }

   public function getOurCompany(){
        $list = M('cmn_cd','tb_ms_')
            ->join('LEFT JOIN tb_crm_company_management ON tb_ms_cmn_cd.CD = tb_crm_company_management.our_company_cd')
            ->field('CD,CD_VAL,ETC,ETC2,company_business_status_cd')
            ->where(" tb_ms_cmn_cd.CD like 'N00124%' AND tb_ms_cmn_cd.USE_YN='Y' AND (  ETC5 != '1' OR ETC5 IS NULL )")
            ->select();
       $list = CodeModel::autoCodeTwoVal($list,['company_business_status_cd']);
       foreach ($list as $key => $value){
           if (isset($list[$key]['company_business_status_cd']) && !empty($list[$key]['company_business_status_cd'])){
               $list[$key]['CD_VAL'] = $list[$key]['CD_VAL'].'（'.$list[$key]['company_business_status_cd_val'].'状态）';
           }
       }
       $this->ajaxReturn(['data'=>$list,'msg'=>'','code'=>2000]);
   }
}