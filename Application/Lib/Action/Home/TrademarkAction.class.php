<?php

/**
 * User: shenmo
 * Date: 19/07/25
 * Time: 11:00
 */
class TrademarkAction extends BaseAction
{
    protected $success_code = 200;
    protected $repository;

    /**
     * DivisionLaborAction constructor.
     */
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        parent::__construct();
        $this->service = new TrademarkService();
        $this->repository = new TrademarkRepository();
    }

    /**
     * @return string
     */
    protected function getTrademarkId()
    {
        $params = DataModel::getDataNoBlankToArr();
        $trademark_id = $params['id'];
        $trademark_id or $trademark_id = $params['trademark_id'];
        return $trademark_id;
    }

    /**
     * @name ODM商标列表分页
     */
    public function dataList()
    {
        try {
            $params = DataModel::getDataNoBlankToArr();
            //$data = $this->service->getTrademarkListNew($params);
            $data = $this->repository->getTrademarkListNew($params);
            $this->ajaxSuccess($data);
        } catch (Exception $exception) {
            $this->ajaxError($data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @name ODM商标详情
     * @param
     * @return data
     */
    public function getTrademarkDetail()
    {
        try {
            $res = DataModel::$success_return;
            $params = $this->params();
            $trademark_id = $this->getTrademarkId();
            if(empty($trademark_id)){
                $trademark_no = $params['trademark_no'];
            }
            $res['data'] = $this->service->getTrademarkDetail($trademark_id, $trademark_no);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @name ODM商标新建
     * @param
     */
    public function createTrademark()
    {
        try {
            $Model = new Model();
            $params = DataModel::getDataNoblankToArr();
            //同时创建商标基本信息、商标国家详细信息
            $this->verificationTrademarkInfo($params);
//            $this->checkTrademarkInfo($params);
            $res = $this->repository->createTrademarkAndDetail($params);
            $this->ajaxSuccess($res);
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
            $this->ajaxError($res, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @name ODM商标编辑
     * @param
     */
    public function updateTrademark()
    {
        try {
            $Model = new Model();
            $params = DataModel::getDataNoblankToArr();
            $this->verificationTrademarkInfo($params);
            $res = $this->repository->updateTrademarkAndDetail($params);
            $this->ajaxSuccess($res);
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
            $this->ajaxError($res, $exception->getMessage(), $exception->getCode());
        }
    }

    public function outputExcel()
    {
        $trademarkService = new TrademarkService();
        $trademarkService->getFinanceExcelAttr();
        $is_excel = 1;
        $params = DataModel::filterBlank($this->_param());
        $search = json_decode($params['post_data'], true);
        $data = $this->service->getTrademarkList($search, $is_excel);
        $trademarkService->outPutExcel(
            $trademarkService->exp_title,
            $trademarkService->exp_cell_name,
            $data
        );
    }

    public function outputExcelTemplate()
    {
        $name = 'trademark.xlsx';
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Home/Legal/' . $name;
        Http::download($filename, $filename);
    }

    public function importExcel()
    {
        try {
            if ($_FILES) {
                set_time_limit(0);
                ini_set('memory_limit', '512M');
                header("content-type:text/html;charset=utf-8");
                $filePath = $_FILES['file']['tmp_name'];
                vendor("PHPExcel.PHPExcel");
                $objPHPExcel = new PHPExcel();
                //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
                $PHPReader = new PHPExcel_Reader_Excel2007();
                if (!$PHPReader->canRead($filePath)) {
                    $PHPReader = new PHPExcel_Reader_Excel5();
                    if (!$PHPReader->canRead($filePath)) {
                        throw new Exception(L('请上传EXCEL文件'));
                    }
                }
                //读取Excel文件
                $PHPExcel = $PHPReader->load($filePath);
                //读取excel文件中的第一个工作表
                $sheet = $PHPExcel->getSheet(0);
                //取得最大的列号
                $allColumn = $sheet->getHighestColumn();
                //取得最大的行号
                $allRow = $sheet->getHighestRow();
                $trademark_arr = [];
                $err = [];
                $repository = new TrademarkRepository();
                $trademark_conf = $this->getTrademarkConf();
                $Model = new Model();
                $Model->startTrans();
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    $trademark_base = [];
                    $trademark_detail = [];
                    $trademark_base['trademark_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue()); //商标名称
                    $trademark_base['trademark_type'] = $type = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue()); //商标类型
                    $trademark_detail['company_code'] = $company_code = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue()); //注册公司
                    $trademark_detail['country_code']  = $country_code      = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue()); //注册国家
                    if (empty($type)) {
                        throw new Exception(L('商标类型必填'));
                    }
                    $trademark_type = $trademark_conf['trademark_type'][$type];
                    $trademark_base['trademark_type'] = $trademark_type;
                    if (empty($trademark_base['trademark_name'])) {
                        throw new Exception(L('商标名称必填'));
                    }
                    if (empty($trademark_type)) {
                        throw new Exception(L("'".$type."'商标类型不存在"));
                    }
                    $trademark_base['img_url'] = '';
                    //对香港、俄国做文案映射
                    if ($trademark_detail['country_code'] == '俄国') $trademark_detail['country_code'] = '俄罗斯';
                    if ($trademark_detail['country_code'] == '香港') $trademark_detail['country_code'] = '中国香港';
//                    if (!isset($trademark_conf['area_code'][$trademark_detail['country_code']])) $err['area'][] = $trademark_detail['country_code'];
//                    if (!isset($trademark_conf['company'][$trademark_detail['company_code']])) $err['company'][] = $trademark_detail['company_code'];
                    $company = strtoupper($trademark_detail['company_code']);
                    $country = $trademark_detail['country_code'];
                    $trademark_detail['company_code']        = isset($trademark_conf['company'][$company]) ? $trademark_conf['company'][$company] : '';
                    $trademark_detail['applicant_name']      = $trademark_detail['company_code'];
                    $trademark_detail['country_code']        = isset($trademark_conf['area_code'][$country]) ? $trademark_conf['area_code'][$country] : '';
                    $trademark_detail['apply_code']          = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue()); //申请号
                    $trademark_detail['applied_date']        = trim($PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue()); //申请日期

                    if (empty($company)) {
                        throw new Exception(L('注册公司必填'));
                    }
                    if (empty($trademark_detail['company_code'])) {
                        throw new Exception(L("'".$company."'注册公司不存在"));
                    }
                    if (empty($country)) {
                        throw new Exception(L('注册国家必填'));
                    }
                    if (empty($trademark_detail['country_code'])) {
                        throw new Exception(L("'".$country."'注册国家不存在"));
                    }
//                    if (!$repository->isUniqueTrademark(
//                        $trademark_base['trademark_name'],
//                        $trademark_base['trademark_type'],
//                        $trademark_detail['country_code'],
//                        $trademark_detail['company_code']
//                    )) {
//                        throw new Exception(
//                            $trademark_base['trademark_name'].'-'.
//                            $country_code.'-'.
//                            $company_code.'-'.
//                            $type.' 商标信息已经存在'
//                        );
//                    }

                    if (!empty($trademark_detail['applied_date'])) {
                        $trademark_detail['applied_date']    = gmdate('Y-m-d',\PHPExcel_Shared_Date::ExcelToPHP($trademark_detail['applied_date']));
                    }
                    $trademark_detail['international_type']  = trim((string)$PHPExcel->getActiveSheet()->getCell("G" . $currentRow)->getValue()); //国际分类
                    $trademark_detail['goods']               = trim((string)$PHPExcel->getActiveSheet()->getCell("H" . $currentRow)->getValue()); //商品/ 服务
                    $trademark_detail['initial_review_date'] = trim((string)$PHPExcel->getActiveSheet()->getCell("I" . $currentRow)->getValue()); //国际注册日期/初审公告日期
                    if (!empty($trademark_detail['initial_review_date'])) {
                        $trademark_detail['initial_review_date']    = gmdate('Y-m-d',\PHPExcel_Shared_Date::ExcelToPHP($trademark_detail['initial_review_date']));
                    }
                    $trademark_detail['trademark_type']      = $trademark_type; //商标类型
                    $trademark_detail['register_code']       = trim((string)$PHPExcel->getActiveSheet()->getCell("J" . $currentRow)->getValue()); //注册编号
                    $trademark_detail['register_date']       = trim((string)$PHPExcel->getActiveSheet()->getCell("K" . $currentRow)->getValue()); //注册日期
                    if (!empty($trademark_detail['register_date'])) {
                        $trademark_detail['register_date']    = gmdate('Y-m-d',\PHPExcel_Shared_Date::ExcelToPHP($trademark_detail['register_date']));
                    }

                    $trademark_detail['current_state']       = trim((string)$PHPExcel->getActiveSheet()->getCell("L" . $currentRow)->getValue()); //当前状态
                    $status = $trademark_conf['current_type'][$trademark_detail['current_state']];
                    if (!$status) throw new Exception(L("'".$trademark_detail['current_state']."'". '状态不存在'));
                    $trademark_detail['current_state'] = $status;
                    $trademark_detail['agent']               = trim((string)$PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue()); //代理/办理机构
                    $trademark_detail['remark']              = trim((string)$PHPExcel->getActiveSheet()->getCell("N" . $currentRow)->getValue()); //备注
                    $trademark['trademark_base']             = $trademark_base;
                    $trademark['trademark_detail']           = $trademark_detail;
                    $res = $this->service->createTrademarkAndDetailExport($trademark);
                    if (!$res) {
                        $err['trademark_name'][] = $trademark_base['trademark_name'];
                    }
                    $trademark_detail['trademark_name']      = $trademark_base['trademark_name'];
                    $trademark_arr[] = $trademark_detail;
                }
                if(empty($err)) {
                    $Model->commit();
                    $this->ajaxSuccess('','导入成功');
                }else {
                    $Model->rollback();
                    $err['area'] = array_unique($err['area']);
                    $err['company'] = array_unique($err['company']);
                    $this->ajaxError('','导入失败');
                }
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
            $this->ajaxError('',$exception->getMessage());
        }
    }

    public function getTrademarkConf()
    {
        $areaCode = CommonDataModel::areaCode();
        $return['area_code'] = array_flip(array_column($areaCode, 'NAME', 'id'));
        $company = CommonDataModel::company();
        $return['company'] = array_column($company, 'cd', 'cdVal');
        $trademark_type = CommonDataModel::trademarkType();
        $return['trademark_type_en'] = array_column($trademark_type, 'cd', 'cdVal_en');
        $return['trademark_type'] = array_column($trademark_type, 'cd', 'cdVal');
        $current_type = CommonDataModel::currentType();
        $return['current_type_en'] = array_column($current_type, 'cd', 'cdVal_en');
        $return['current_type'] = array_column($current_type, 'cd', 'cdVal');
        return $return;
    }

    public function verificationTrademarkInfo($params)
    {
        $rules = [
            'trademark_base.trademark_name' => 'required|string',
            'trademark_base.trademark_type' => 'required|string|size:10',
            'trademark_detail.current_state' => 'required',
        ];
        $custom_attributes = [
            'trademark_base.trademark_name' => '商标名称',
            'trademark_base.trademark_type' => '商标类型',
            'trademark_detail.trademark_type' => '注册状态',
        ];
        $this->validate($rules, $params, $custom_attributes);
    }

    public function checkTrademarkInfo($params)
    {
        $where['base.trademark_name']   = $params['trademark_base']['trademark_name'];
        $where['detail.company_code']   = $params['trademark_detail'][0]['company_code'];
        $where['detail.country_code']   = $params['trademark_detail'][0]['country_code'];
        $where['base.trademark_type'] = $params['trademark_base']['trademark_type'];
        //商标编辑 验证排除自身
        if ($params['trademark_base']['id']) {
            $where['base.id'] = ['neq', $params['trademark_base']['id']];
        }
        if ($trademark = $this->service->getTrademarkBase($where)) {
            throw new Exception(L('商标名称已存在'));
        }
    }

    public function file_upload() {
        $fd = new FileUploadModel();
        $res = $fd->uploadFileArr();
        if ($res) {
            $info = $res;
            $info[0]['show_img'] = ERP_URL. (new TrademarkRepository())->img_path. $info[0]['savename'];
            $this->ajaxReturn(1,$info,1);
        } else {
            $this->error($fd->error, '', true);
        }
    }

    #################################2021.3.2 11315需求对应的修改###########################

    //注册商标申请新建
    public function createRegisterTrademark()
    {
       $params = $this->params();
       //数据校验
       if(empty($params['trademark_name'])){
           $this->ajaxError('', '商标名称必须填写', '');
       }else{
           $is_same = $this->repository->checkSameName(trim($params['trademark_name']));
           if($is_same){
               $this->ajaxError('', '商标名称已存在，请更换', '');
           }
       }
       if(empty($params['country_id'])){
           $this->ajaxError('', '申请国家必选', '');
       }

       if(mb_strlen($params['trademark_name'],'utf-8') > 100){
           $this->ajaxError('', '商标名称字数不能超过100', '');
       }
       if(mb_strlen($params['remark'], 'utf-8') > 100){
           $this->ajaxError('', '备注字数不能超过100', '');
       }
       if(empty($params['image_urls'])){
           $this->ajaxError('', '请上传商品图片', '');
       }
        $res = $this->repository->addRegisterTrademark($params);
        if($res > 0){
            $this->ajaxSuccess($res,'添加成功','');
        }else{
            $this->ajaxError($res, '添加失败','');
        }
    }


    //注册商标申请列表
    public function registerList()
    {
        $params = $this->params();
        $register_list = $this->repository->registerList($params);
        $this->ajaxSuccess($register_list);
    }

    //发消息
    public function sendMessage($user_id, $column, $content){
        $wx_ids = M()->table('bbm_admin a')
            ->field('b.wid')
            ->join('left join tb_hr_empl_wx b on a.empl_id = b.uid')
            ->where(["a.$column" => $user_id])
            ->select();
        $wx_ids = trim(implode(array_column($wx_ids, 'wid'), '|'),'|');
        //发消息
        WxAboutModel::sendWxMsg($wx_ids, $content);
    }
    //注册商标单个信息获取
    public function editRegisterTrademarkShow()
    {
        $params = $this->params();
        $register_info= $this->repository->editRegisterTrademarkShow($params);
        $this->ajaxSuccess($register_info);
    }


    // 注册商标申请修改
    public function editRegisterTrademark()
    {
        $params = $this->params();
        $can_edit =$this->repository->canOperate($params['id'], TrademarkRepository::VERIFY_REJECT);
        if(!$can_edit){
            $this->ajaxError('', '驳回状态的注册单，才可以编辑', '');
        }
        //数据校验
        if(empty($params['trademark_name'])){
            $this->ajaxError('', '商标名称必须填写', '');
        }
        if(mb_strlen($params['trademark_name'],'utf-8') > 100){
            $this->ajaxError('', '商标名称字数不能超过100', '');
        }
        if(mb_strlen($params['remark'], 'utf-8') > 100){
            $this->ajaxError('', '备注字数不能超过100', '');
        }
        if(empty($params['country_id'])){
            $this->ajaxError('', '申请国家必选', '');
        }
        if(empty($params['image_urls'])){
            $this->ajaxError('', '请上传商品图片', '');
        }
        $res = $this->repository->editRegisterTrademark($params);
        if($res > 0){
            $this->ajaxSuccess($res,'修改成功','');
        }else{
            $this->ajaxError($res, '修改失败','');
        }
    }


    //注册商标申请审批
    public function changeStatus(){
        $params = $this->params();
        if(empty($params['ids'])){
            $this->ajaxError('','请勾选注册单号','');
        }
        $status = $params['status'];
        //提示信息
        $msg_arr = ['2'=>'无需审批，请重试！','3'=>'状态已审批，请重试！'];
        $status = $params['status'];
        if($status != TrademarkRepository::VERIFY_CANCEL){
            $check_res = $this->repository->checkAllStatus($params, $status);
            if(!empty($check_res) && isset($check_res['register_no'])){
                $this->ajaxError('',"注册单号{$check_res['register_no']}{$msg_arr[$status]}",'');
            }
        }else{
            $check_res['register_info'] = explode(',', $params['ids']);
        }

        $success = $this->repository->changeStatus($params, $check_res['register_info']);
        $success_ids = implode($success,',');
        $this->ajaxSuccess('',"操作成功的ID有$success_ids",'');

    }


    //判断是否可以添加ODM列表的信息
    public function canSubmitODM(){
        $params = $this->params();
        $id = $params['id'];
        if(empty($id)){
            $this->ajaxError('','请勾选注册单号','');
        }
        if(is_array($id) && count($id) > 1){
            $this->ajaxError('','仅支持对单个注册单进行操作','');
        }
        $can_operate = $this->repository->canOperate($id,TrademarkRepository::VERIFY_PASS);
        $can_odm = $this->repository->existOdm($id);
        if($can_operate && $can_odm){
            $this->ajaxSuccess('','能提交ODM信息','');
        }else{
            $this->ajaxError('','该注册申请不再通过状态或已经提交过ODM信息','');
        }
    }


    //ODM商标删除
    public function deleteODMData(){
        $params = $this->params();
        if(empty($params['ids'])){
            $this->ajaxError('','请勾选注册单号','');
        }
        $res = $this->repository->deleteODMData($params['ids']);
        if(!$res){
            $this->ajaxError('',"删除失败",'');
        }
        $this->ajaxSuccess('',"删除成功",'');
    }


    //商标的使用添加之前点击后的判断，成功则跳转到添加界面不成功则提示信息
    public function canSubmitTrademarkUse(){
        $params = $this->params();
        //数据校验
        $trademark_id = $params['trademark_id'];
        if(empty($trademark_id)){
            $this->ajaxError('','请勾选商标','');
        }
        if(is_array($trademark_id) && count($trademark_id) > 1){
            $this->ajaxError('','仅支持对单个商标进行使用','');
        }
        $can_use = $this->repository->canUse(trim($params['trademark_id']), TrademarkRepository::HAVE_REGISTER);
        if (!$can_use) {
            $this->ajaxError('', '当前商标未注册成功，不可使用！', '');
        }else{
            $this->ajaxSuccess('', '可以创建使用信息', '');
        }
    }


    //商标的使用添加
    public function useTrademarkAdd(){
        $params = $this->params();
        if(empty($params['trademark_no'])){
            $this->ajaxError('', '请传入商标编号', '');
        }
        if(empty($params['use_type'])){
            $this->ajaxError('', '请传入使用类型', '');
        }
        $res = $this->repository->addTrademarkUse($params);
        if($res > 0){
            $this->ajaxSuccess($res,'添加成功','');
        }else{
            $this->ajaxError($res, '添加失败','');
        }
    }


    //商标使用编辑
    public function useTrademarkEdit(){
        $params = $this->params();
        $can_edit = $this->repository->canUseTrademarkOperate($params['id'], TrademarkRepository::VERIFY_REJECT);
        if(!$can_edit){
            $this->ajaxError('', '驳回状态的申请单，才可以编辑', '');
        }
        $res = $this->repository->editTrademarkUse($params);
        if($res > 0){
            $this->ajaxSuccess($res,'修改成功','');
        }else{
            $this->ajaxError($res, '修改失败','');
        }
    }


    public function useTrademarkList(){
        $params = $this->params();
        $list = $this->repository->useTrademarkList($params);
        $this->ajaxSuccess($list,'获取成功','');
    }


    //使用商标的申请审批
    public function changeUseStatus(){
        $params = $this->params();
        if(empty($params['ids'])){
            $this->ajaxError('','请勾选审核单号','');
        }
        $status = $params['status'];
        //提示信息
        $msg_arr = ['2'=>'无需审批，请重试！','3'=>'状态已审批，请重试！'];
        $check_res = $this->repository->checkAllUseStatus($params, $status);
        if(!empty($check_res) && isset($check_res['use_no'])){
            $this->ajaxError('',"申请单号{$check_res['use_no']}{$msg_arr[$status]}",'');
        }
        $res = $this->repository->changeAllUseStatus($params);
        if(!$res){
            $this->ajaxError('',"操作失败",'');
        }
        $this->ajaxSuccess('',"操作成功",'');
    }


    //使用商标单个信息获取
    public function getOneTradeMarkUseData()
    {
        $params = $this->params();
        $register_info= $this->repository->getTradeMarkUseData($params);
        $this->ajaxSuccess($register_info);
    }


    //使用记录的添加
    public function useRecordAdd(){
        $params = $this->params();
        $res = $this->repository->useRecordAdd($params);
        if(!$res){
            $this->ajaxError('',"提交失败",'');
        }
        $this->ajaxSuccess('',"提交成功",'');
    }


    //获取一个商标的所有的使用记录
    public function getAllUseRecord(){
        $params = $this->params();
        $res = $this->repository->getAllUseRecord($params);
        $this->ajaxSuccess($res,"获取成功",'');
    }


    //获取一个供应商的关联的商标的使用记录
    public function getSupplierOrCustomerUseRecord(){
        $params = $this->params();
        $res = $this->repository->getSupplierUseRecord($params);
        $this->ajaxSuccess($res,"获取成功",'');
    }


    //合同列表输入供应商编号获取商标基本信息
    public function getTrademarkInfo(){
        $params = $this->params();
        $res = $this->repository->getTrademarkInfo($params);
        $this->ajaxSuccess($res,"获取成功",'');
    }


    public function getAUthFile(){
        $params = $this->params();
        $this->repository->getAUthFile($params);
    }


    //判断是否存在这个供应商
    public function isRealSupplier(){
        $params = $this->params();
        $res = $this->repository->isRealSupplier($params['id']);
        $this->ajaxSuccess($res,"存在该供应商",'');
    }

}
