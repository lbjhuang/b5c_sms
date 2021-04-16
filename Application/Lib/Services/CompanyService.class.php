<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/29
 * Time: 9:48
 */
class CompanyService extends Service
{
    public $user_name;
    public $model;
    public function __construct($model)
    {
        $this->model     = empty($model) ? new Model() : $model;
        $this->user_name = DataModel::userNamePinyin();
        $this->com_cd    = 'N00124';
    }

    /**
     * 公司资质列表
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function getQualificationList($request_data, $is_excel = false)
    {
        $search_map = [
            'number'           => 'number',
            'our_company_code' => 'our_company_code',
            'name'             => 'name',
        ];
        $exact_search = ['number', 'our_company_code'];
        list($where, $limit)   = WhereModel::joinSearchTemp($request_data, $search_map, "", $exact_search);
        $qualification_model   = M('qualification', 'tb_crm_');
        $pages['total']        = $qualification_model->where($where)->count();
        $pages['current_page'] = empty($limit[0]);
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $list = $qualification_model->where($where)->limit($limit[0], $limit[1])->order('updated_at desc')->select();
        } else {
            $list = $qualification_model->where($where)->order('updated_at desc')->select();
        }
        if (!empty($list)) {
            $list = CodeModel::autoCodeTwoVal(
                $list, [
                'our_company_code'
                ]
            );
        }
        return [
            'data' => $list,
            'pages' => $pages
        ];
    }

    /**
     * 保存公司资质
     * @param $request_data
     * @throws Exception
     */
    public function saveQualification($request_data)
    {
        $qualification_model = M('qualification', 'tb_crm_');
        if(!$qualification_model->create($request_data)) {
            throw new \Exception(L('创建公司资质数据失败'));
        }
        if ($request_data['issue_date']) {
            $qualification_model->issue_date = date('Y年m月d日',strtotime($request_data['issue_date']));
        }
        if ($request_data['expire_date']) {
            $qualification_model->expire_date = date('Y年m月d日',strtotime($request_data['expire_date']));
        }
        if ($request_data['renew_date']) {
            $qualification_model->renew_date = date('Y年m月d日',strtotime($request_data['renew_date']));
        }
        $qualification_model->updated_by = $this->user_name;
        $qualification_model->attachment = json_encode($request_data['attachment'], JSON_UNESCAPED_UNICODE);
        if ($request_data['id']) {
            $this->isUniqueQualificationName($request_data, $request_data['id']);
            if (false === $qualification_model->save()) {
                throw new \Exception(L('公司资质编辑失败'));
            }
        } else {
            $this->isUniqueQualificationName($request_data);
            $number                          = 'GSZZ' .date(Ymd). TbWmsNmIncrementModel::generateCustomNo('qualification', 4);
            $qualification_model->created_by = $qualification_model->updated_by;
            $qualification_model->number     = $number;
            if (!$qualification_model->add()) {
                throw new \Exception(L('公司资质添加失败'));
            }
        }
    }

    /**
     * 判断资质名称是否唯一
     * @param $name
     * @param $id
     * @throws Exception
     */
    private function isUniqueQualificationName($request_data, $id)
    {
        $qualification_model = M('qualification', 'tb_crm_');
        $where['name']             = $request_data['name'];
        $where['our_company_code'] = $request_data['our_company_code'];
        if ($id) {
            $where['id'] = ['neq', $id];
        }
        $res = $qualification_model->where($where)->find();
        if ($res) {
            throw new \Exception(L('资质名称已经存在'));
        }
    }

    public function deleteQualification($id)
    {
        $qualification_model = M('qualification', 'tb_crm_');
        $db_res = $qualification_model->find($id);
        if (!$qualification_model->delete($id)) {
            throw new \Exception(L('删除失败'));
        }
        Logs($db_res, __FUNCTION__, 'normal');
    }


    /**
     * 我方公司列表
     * @param $request_data
     * @return array
     */
    public function companyManagementList($request_data,$is_export = false)
    {
        $search_map = [
            "our_company_cd"                => "our_company_cd",
            "company_business_status_cd"    => "company_business_status_cd",
            "reg_country"                   => "reg_country",
            "reg_province"                  => "reg_province",
            "reg_city"                      => "reg_city",
        ];
          
        $exact_search = ['our_company_cd', 'company_business_status_cd', 'reg_country', 'reg_province', 'reg_city'];
        list($where, $limit)   = WhereModel::joinSearchTemp($request_data, $search_map, "", $exact_search);
        $where['_string'] = '';
        $searchData = $request_data['search'];

        if ($searchData['legal_name']) {
            $where['_string'] .= " (legal_name like '%{$searchData["legal_name"]}%' OR legal_alias_name like '%{$searchData["legal_name"]}%')";
        }
        if ($request_data['search']['supervisor_name']) {
            $string = '';
            if ($where['_string']) {
                $string = 'AND';
            }
            $where['_string'] .= $string . " (supervisor_name like '%{$searchData["supervisor_name"]}%' OR supervisor_alias_name like '%{$searchData["supervisor_name"]}%')";
        }
        if (!$where['_string']) {
            unset($where['_string']);
        }

        // 股东
        $com_srh_model = M('company_shareholder', 'tb_crm_');
        if ($searchData['shareholder_name']){
            $condtion_cmn = array(
                'CD' => array('like','N00124%'),
                'CD_VAL' => array('like','%'.$searchData['shareholder_name'].'%'),
            );
            $cmn_data = M('cmn_cd','tb_ms_')->where($condtion_cmn)->field('CD')->select();
            $condtion = array();
            if (!empty($cmn_data)){
                $shareholder_names = implode("','",array_column($cmn_data,'CD'));
                $where_arr = array();
                foreach ($cmn_data as $key => $itme){
                    array_push($where_arr,array('EQ',$itme['CD']));
                }
                array_push($where_arr, array('like','%'.$searchData['shareholder_name'].'%'));
                array_push($where_arr, "or");
                $condtion['shareholder_name'] = $where_arr;
            }else{
                $condtion['shareholder_name'] = array('like','%'.$searchData['shareholder_name'].'%');

            }
            $shareholder_data = $com_srh_model->where($condtion)->field('company_management_id')->select();
            if (!empty($shareholder_data)){
                $where['id'] = array('in',array_column($shareholder_data,'company_management_id'));
            }
        }
        $company_management_model   = M('company_management', 'tb_crm_');
        if ($is_export){
            $list = $company_management_model->where($where)->order('reg_country desc')->select();
            if (!empty($list)) {

                $supplier_list = M('sp_supplier','tb_crm_')->field('ID,SP_NAME')->select();
                $supplier_data = array();
                if ($supplier_list){
                    foreach ($supplier_list as $item){
                        $supplier_data[$item['ID']] = $item['SP_NAME'];

                    }
                }
                $list = CodeModel::autoCodeTwoVal(
                    $list, [
                        'our_company_cd',
                        'company_business_status_cd',
                        'reg_amount_cd',
                    ]
                );
                // 获取国家二字码，获取国家，省份，地区名称 键值对处理foreach处理 （缓存处理）
                foreach ($list as $key => $value) {
                    $list[$key]['two_char'] = $this->getAreaNameByID($value['reg_country'])['two_char'];
                    $list[$key]['reg_country'] = $this->getAreaNameByID($value['reg_country'])['zh_name'];
                    $list[$key]['reg_province'] = $this->getAreaNameByID($value['reg_province'])['zh_name'];
                    $list[$key]['reg_city'] = $this->getAreaNameByID($value['reg_city'])['zh_name'];
                    $shareholder_data_new = array();
                    $shareholder_data = $com_srh_model->where(array('company_management_id'=>$value['id']))->field('shareholder_name')->select();
                    if (!empty($shareholder_data)){
                        $shareholder_data = CodeModel::autoCodeTwoVal($shareholder_data,['shareholder_name']);
                        foreach ($shareholder_data as $datum){
                            if (isset($datum['shareholder_name_val']) && !empty($datum['shareholder_name_val'])){
                                array_push($shareholder_data_new,$datum['shareholder_name_val']);
                            }else{
                                if (!empty($datum['shareholder_name'])){
                                    array_push($shareholder_data_new,$datum['shareholder_name']);
                                }
                            }
                        }
                    }
                    $shareholder_name = "";
                    if ($shareholder_data_new){
                        $shareholder_name = implode(',',$shareholder_data_new);
                    }
                    $list[$key]['shareholder_name']  = $shareholder_name;
                    $list[$key]['reg_area'] = $list[$key]['reg_country'].'-'.$list[$key]['reg_province'].'-'.$list[$key]['reg_city'];
                    $list[$key]['reg_amount'] = $list[$key]['reg_amount_cd_val'].' '.$list[$key]['reg_amount'];
                    if (!empty($list[$key]['legal_alias_name'])){
                        $list[$key]['legal_name'] = $list[$key]['legal_name'].'（'.$list[$key]['legal_alias_name'].'）';
                    }

                    if (!empty($list[$key]['supervisor_alias_name'])){
                        $list[$key]['supervisor_name'] = $list[$key]['supervisor_name'].'（'.$list[$key]['supervisor_alias_name'].'）';
                    }
                    $list[$key]['secretary_company_name'] = isset($supplier_data[ $list[$key]['secretary_company_sp_id'] ] ) ? $supplier_data[$list[$key]['secretary_company_sp_id']] : "";
                    $list[$key]['agency_company_name'] = isset($supplier_data[ $list[$key]['agency_company_sp_id'] ] ) ? $supplier_data[$list[$key]['agency_company_sp_id']] : "";

                    // Q-916 我方公司导出时，注册地址有的显示1，
                    if ($value['reg_country'] == 1 && $value['reg_city'] != 2027) {
                        $list[$key]['reg_address_new'] = $value['reg_address'];
                    }else{
                        $list[$key]['reg_address_new'] = $value['reg_address_en'];
                    }
                }
            }

            $exp_cell_name = [
                'id' => '编号',
                'reg_area' => '注册区域',
                'our_company_cd_val' => '公司名称',
                'register_time' => '成立时间',
                'company_business_status_cd_val' => '工商登记状态',
                'company_no' => 'Company No.',
                'reg_address_new' => '注册地址',
                'reg_amount' => '注册资本',
                'legal_name' => '法定代表人/董事/负责人',
                'shareholder_name' => '股东',
                'supervisor_name' => '监事',
                'secretary_company_name' => '秘书公司',
                'agency_company_name' => '代理记账公司',
            ];
            $exp_table_data = $list;
            $this->outputCsv('company_management_list_', $exp_cell_name,  $exp_table_data);
            exit;
        }else{
            $pages['total']        = $company_management_model->where($where)->count();
            $pages['current_page'] = empty($limit[0]);
            $pages['per_page']     = $limit[1];
            $list = $company_management_model->where($where)->limit($limit[0], $limit[1])->order('company_business_status_cd,updated_at desc')->select();
            if (!empty($list)) {
                $list = CodeModel::autoCodeTwoVal(
                    $list, [
                        'our_company_cd',
                        'company_business_status_cd',
                        'reg_amount_cd',
                    ]
                );
                // 获取国家二字码，获取国家，省份，地区名称 键值对处理foreach处理 （缓存处理）
                foreach ($list as $key => $value) {
                    $list[$key]['two_char'] = $this->getAreaNameByID($value['reg_country'])['two_char'];
                    $list[$key]['reg_country'] = $this->getAreaNameByID($value['reg_country'])['zh_name'];
                    $list[$key]['reg_province'] = $this->getAreaNameByID($value['reg_province'])['zh_name'];
                    $list[$key]['reg_city'] = $this->getAreaNameByID($value['reg_city'])['zh_name'];
                    $shareholder_data_new = array();


                    $shareholder_data = $com_srh_model->where(array('company_management_id'=>$value['id']))->field('shareholder_name')->select();
                    if (!empty($shareholder_data)){
                        $shareholder_data = CodeModel::autoCodeTwoVal($shareholder_data,['shareholder_name']);
                        foreach ($shareholder_data as $datum){
                            if (isset($datum['shareholder_name_val']) && !empty($datum['shareholder_name_val'])){
                                array_push($shareholder_data_new,$datum['shareholder_name_val']);
                            }else{
                                if (!empty($datum['shareholder_name'])){
                                    array_push($shareholder_data_new,$datum['shareholder_name']);
                                }
                            }
                        }
                    }
                    $shareholder_name = "";
                    if ($shareholder_data_new){
                        $shareholder_name = implode(',',$shareholder_data_new);
                    }
                    $list[$key]['shareholder_name']  = $shareholder_name;
                    $list[$key]['sort_num'] = 0;
                    if (  $list[$key]['company_business_status_cd'] == 'N002950005'){
                        $list[$key]['sort_num'] = 1;
                    }
                }
            }
            ;
            $list = $this->arraySort($list,'sort_num',SORT_ASC);
            return [
                'data' => $list,
                'pages' => $pages
            ];
        }
    }

    public function arraySort($array, $keys, $sort = SORT_DESC) {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * 根据ID获取地方（国家，省份，地区的名称）
     * @param $id
     * @return array
     */
    public function getAreaNameByID($id)
    {
        $area_info = S("user_area_{$id}");
        if (!$area_info) {
            $area_info = M('user_area', 'tb_ms_')->field('zh_name, two_char')->where(['id' => $id])->find();
            $res = S("user_area_{$id}", $area_info);
        }
        return $area_info;
    }

    /**
     * 判断我方公司名称是否唯一&oa编号是否符合要求
     * @param $name
     * @param $id
     * @throws Exception
     */
    private function isUniqueOurCompanyName($request_data, $id)
    {
        // 校验【公司名称】是否有和已有的CODE TYPE=N00124（我方公司）中的某项CODE VALUE重复。
        // 校验【OA编号】是否和已有的某CODE TYPR=N00124（我方公司）中的某项 Comment1重复（如果都为空不算重复）即tb_crm_company_management.oa_no不重复
        $cmn_model  = M('cmn_cd', 'tb_ms_');
        $area_model = M('user_area', 'tb_ms_');
        $our_company_info_model = M('company_management', 'tb_crm_');
        $need_add_flag = 0; // 是否需要新增我方公司CD
        if ($id) { // 编辑

            // 根据id ,获取相关信息，包括公司名称CD和oa编号
            $our_company_info = $our_company_info_model->field('our_company_cd, oa_no, reg_country, company_business_status_cd')->where(['id' => $id])->find();
            if (!$our_company_info) {
                throw new \Exception(L('我方公司信息不存在'));
            }
            // 根据公司名称cd取名称
            $com_name = $cmn_model->where(['CD' => $our_company_info['our_company_cd']])->getField('CD_VAL');
            
            if ($our_company_info['oa_no'] && $request_data['oa_no']) { // 原来oa有值时，且目前传的oa有值
                if (strval($our_company_info['oa_no']) !== strval($request_data['oa_no'])) {
                    $whereMap['id'] = array('neq', $id);
                    $whereMap['oa_no'] = array('eq', $request_data['oa_no']);
                    $exist_id = $our_company_info_model->where($whereMap)->getField('id');   
                    if ($exist_id) {
                        throw new \Exception(L('该OA编号已经存在'));
                    }
                }
            }

            if ($com_name !== $request_data['our_company_name']) { //名称不相同时才需要进一步确认
                $com_name = $cmn_model->where(['CD_VAL' => $request_data['our_company_name']])->getField('CD');
                if ($com_name) {
                    $com_name_type = substr($com_name, 0, 6); // 获取code type是否为我方公司的类型
                    if (strval($com_name_type) === 'N00124') {
                        throw new \Exception(L('该我方公司名称已经存在'));
                    }
                }
                // $need_add_flag = 1; #9719 我方公司编辑优化 编辑历史我方公司名字，不新增CODE，而是直接同步更改原有CODE对应的我方公司名字
                $save_info['CD_VAL'] = $request_data['our_company_name'];
            }
            
        } else { // 新增
            if ($request_data['oa_no']) {
                $whereMap['oa_no'] = array('eq', $request_data['oa_no']);
                $exist_id = $our_company_info_model->where($whereMap)->getField('id');   
                if ($exist_id) {
                    throw new \Exception(L('该OA编号已经存在'));
                }
            }

            $com_name = $cmn_model->where(['CD_VAL' => $request_data['our_company_name']])->getField('CD');
            if ($com_name) {
                $com_name_type = substr($com_name, 0, 6); // 获取code type是否为我方公司的类型
                if (strval($com_name_type) === 'N00124') {
                    throw new \Exception(L('该我方公司名称已经存在'));
                }
            }
            $need_add_flag = 1;
        }
        if ($need_add_flag) { // 新增我方公司code

            $condtion['CD'] = array("like","%".$this->com_cd."%");
            $lastCD = $cmn_model->where($condtion)->max('CD');
            $lastCDNum = substr($lastCD, 6, 4);
            $cd_type_num = str_pad((int)$lastCDNum + 1, 4, "0", STR_PAD_LEFT);
            if ((int)$cd_type_num > 9999) {
                throw new Exception(L('亲，该code type下提交的'.$value['CD_VAL'].'（含）的值无法继续新增，因为超过上限（9999）啦'));
            }

    /*      提交校验通过后的业务逻辑：
            生成新的公司CODE&公司记录，写入CODE表。
            根据【工商登记状态】设置CODE的Avaliable状态。【有效】N002950001对应On，其他都对应Off。
            把【OA编号】写入Comment1。
            根据【注册区域】找到对应的区域二字码，写入Comment3。*/    

            $two_char = $area_model->where(['id' => $request_data['reg_country']])->getField('two_char');
            $add_info['USE_YN'] = 'N';
            if ($request_data['company_business_status_cd'] === 'N002950001') {
                $add_info['USE_YN'] = 'Y';
            }
            $add_info['CD'] = $this->com_cd . $cd_type_num;
            $add_info['CD_NM'] = '我方公司';
            $add_info['CD_VAL'] = $request_data['our_company_name'];
            $add_info['ETC'] = $request_data['oa_no'];
            $add_info['ETC3'] = $two_char;
            $add_info['created_by'] = DataModel::userNamePinyin();
            $add_info['created_at'] = DateModel::now();
            $new_code = $cmn_model->add($add_info);
            if (!$new_code) {
                throw new Exception(L('新增保存code值失败'));
            }
            return $add_info['CD'];
            // 返回新的code码
        }

        // （编辑）与原来的值不一样，则需要在原来的code上对oa值修改
        if ($our_company_info['oa_no'] !== $request_data['oa_no']) { 
            $save_info['ETC'] = $request_data['oa_no'];
        }
        // 二字码不一样修改
        if ($our_company_info['reg_country'] !== $request_data['reg_country']) { 
            $two_char = $area_model->where(['id' => $request_data['reg_country']])->getField('two_char');
            $save_info['ETC3'] = $two_char;
        }

        // 开启状态是否需要变更
        if (($request_data['company_business_status_cd'] === 'N002950001' && $our_company_info['company_business_status_cd'] !== 'N002950001')) {
            $save_info['USE_YN'] = 'Y';
        }
        if (($request_data['company_business_status_cd'] !== 'N002950001' && $our_company_info['company_business_status_cd'] === 'N002950001')) {
            $save_info['USE_YN'] = 'N';
        }

        if ($save_info) { //更新码表以及公司管理表
            $save_info['updated_at'] = DateModel::now();
            $save_info['updated_by'] = DataModel::userNamePinyin();
            $res = $cmn_model->where(['CD' => $our_company_info['our_company_cd']])->save($save_info);  
            if (!$res) {
                throw new Exception(L('编辑保存更新oa失败'));
            }
        }
        
        return false; 
    }

    // 我方公司详情
    public function getCompanyDetail($request_data)
    {
        // 基本信息获取
        $company_model = M('company_management', 'tb_crm_');
        $company_shareholder_model = M('company_shareholder', 'tb_crm_');

        $data['management_info'] = $company_model->find($request_data['id']);
        if (!$data['management_info']) {
            throw new Exception(L('该id下我方公司信息为空'));
        }
        $data['management_info'] = CodeModel::autoCodeOneVal($data['management_info'], ['our_company_cd', 'company_business_status_cd', 'reg_amount_cd']);
        if (!$data['management_info']['our_company_cd_val']) { // 兼容code“N”的情况
            $data['management_info']['our_company_cd_val'] = cdVal($data['management_info']['our_company_cd']);
        }

        $area_info = $this->getAreaNameByID($data['management_info']['reg_country']);



        if ($data['management_info']['secretary_company_sp_id']){
            $company_sp_data = explode(',',$data['management_info']['secretary_company_sp_id']);
            $supplier_list = M('sp_supplier','tb_crm_')
                ->field('ID as id ,SP_NAME as sp_name,secretary_company_telephone as company_telephone')
                ->where(array('ID'=>array('in',$company_sp_data)))
                ->select();
            $data['management_info']['secretary_company_sp_id'] = $supplier_list;
        }

        if ($data['management_info']['agency_company_sp_id']){
            $company_sp_data = explode(',',$data['management_info']['agency_company_sp_id']);
            $supplier_list = M('sp_supplier','tb_crm_')
                ->field('ID as id ,SP_NAME as sp_name,agency_company_telephone as company_telephone')
                ->where(array('ID'=>array('in',$company_sp_data)))
                ->select();
            $data['management_info']['agency_company_sp_id'] = $supplier_list;
        }


        $data['management_info']['reg_country_id'] = $data['management_info']['reg_country'];
        $data['management_info']['reg_city_id'] = $data['management_info']['reg_city'];
        $data['management_info']['reg_province_id'] = $data['management_info']['reg_province'];
        $data['management_info']['two_char'] = $area_info['two_char'];
        $data['management_info']['reg_country'] = $area_info['zh_name'];
        $data['management_info']['reg_city'] = $this->getAreaNameByID($data['management_info']['reg_city'])['zh_name'];
        $data['management_info']['reg_province'] = $this->getAreaNameByID($data['management_info']['reg_province'])['zh_name'];

        // 股东信息
        $shareholder_info = $company_shareholder_model->where(['company_management_id' => $request_data['id']])->select();

        foreach ($shareholder_info as $key => $value) {
            $shareholder_info[$key]['shareholder_com_name'] = '';
            if ($value['type_cd'] === 'N002960001') { // 当股东类型为公司时，返回公司名称，方便前端使用
                $shareholder_info[$key]['shareholder_com_name'] = cdVal($value['shareholder_name']);
            }
        }
        $data['management_info']['shareholder_info'] = $shareholder_info; 

        if ($request_data['only_show_edit'] !== 'Y') { // 表明此为详情页面（非编辑页面），还需获取其他信息
            $com_cd = $data['management_info']['our_company_cd'];
            if (!$com_cd) {
                throw new Exception(L('缺失公司code')); 
            }
            
            $data['qualification_info'] = $this->getQualificationByCom($com_cd); // 资质信息
            $data['bankaccount_info'] = $this->getBankaccountByCom($com_cd); // 账户信息
            $data['store_info'] = $this->getStoreByCom($com_cd); // 店铺信息
            $data['contract_info'] = $this->getContractByCom($com_cd); // 合同信息
       
        }

        return $data;
    }

    public function getContractByCom($our_company_code)
    {
        $contract_model = D('TbCrmContract');   
        $field = 'CON_NO, ID, CON_NAME, SP_NAME, CON_TYPE, CONTRACTOR, IS_RENEWAL, START_TIME, CREATE_USER_ID, END_TIME, CREATE_TIME, SP_ANNEX_ADDR1'; 
        $cmn_model = D('TbMsCmnCd');
        $company_oa = $cmn_model->where(['CD' => $our_company_code])->getField('ETC'); // 根据code获取oa值
        $data = $contract_model->field($field)->where(['CON_COMPANY_CD' => $company_oa])->select();
        if (!empty($data)) {
            $user_info = BaseModel::getAdmin();
            $con_type_info = BaseModel::conType();
            foreach ($data as $key => $value) {
                $data[$key]['file_name'] = '';
                if ($value['SP_ANNEX_ADDR1']) {
                    $annex = json_decode($value['SP_ANNEX_ADDR1'], true);   
                    $data[$key]['file_name'] = $annex['file_name'];
                }
                $data[$key]['IS_RENEWAL'] = $value['IS_RENEWAL'] ? '否' : '是';
                $data[$key]['CREATE_USER_ID'] = $user_info[$value['CREATE_USER_ID']];
                $data[$key]['Team'] = '-'; // 参照contract/index 页面以数据表，并没有这个字段的内容，默认为‘-’
                $data[$key]['CON_TYPE'] = $con_type_info[$value['CON_TYPE']];
            }    
        }
        
        return $data;
    }

    public function getQualificationByCom($our_company_code)
    {
        $qualification_model = M('qualification', 'tb_crm_');
        $data = $qualification_model->where(['our_company_code' => $our_company_code])->select();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $data[$key]['expire_date'] = $data[$key]['is_long_time'] ? '长期' : $data[$key]['expire_date'];
            }
        }
        
        return $data;
    }

    public function getStoreByCom($our_company_code)
    {

        $model = new TbMsStoreModel();
        $field = 't.ID, t.PLAT_NAME, t.STORE_NAME, t.MERCHANT_ID, t.STORE_INDEX_URL, t.STORE_STATUS, ua.zh_name';
        $where['t.company_cd'] = $our_company_code;
        $data = $model
            ->alias('t')
            ->field($field)
            ->join('tb_ms_cmn_cd cd2 on cd2.CD=t.company_cd')
            ->join('tb_ms_user_area ua on ua.id=t.COUNTRY_ID')
            ->where($where)
            ->select();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $data[$key]['STORE_ZN_STATUS'] = $value['STORE_STATUS'] ? '未运营' : '运营中';
            }
        }
        
        return $data;
    }

    public function getBankaccountByCom($our_company_code)
    {
        $model = new TbWmsAccountBankModel();
        $data = $model->where(['company_code' => $our_company_code])->select();
        if (!empty($data)) {
            $data = CodeModel::autoCodeTwoVal(
                $data, ['account_type', 'currency_code']
            );
            foreach ($data as $key => $value) {
                $data[$key]['status_name'] = $data[$key]['state'] == '1' ? '启用' : '停用';
            }
        }
        return $data;
    }

    // 根据名称获取地方id
    private function getAreaIDByName($id, $need_two_char, $model)
    {
        if (!$id) {
            return false;
        }
        $field = 'id';
        if ($need_two_char) {
            $field = 'id, two_char';
        }
        if (!$model) {
            $model = M('user_area', 'tb_ms_');
        }

        $area_info = $model->field($field)->where(['zh_name' => $id] )->find();

        return $area_info;
    }

    // 根据花名获取真名
    private function getTrueNameByAlias($name, $model)
    {
        if (!$name) {
            return false;
        }
        if (!$model) {
            $model = D('empl', 'tb_hr_');
        }
        if ($name === 'Vladimir Skrynnik') { // 个别特殊名字处理
            return $name;
        }

        $res = $model->field('EMP_NM')->where(['EMP_SC_NM' => $name])->find();
        return $res['EMP_NM'];

    }

    // 修复股东信息真名数据
    public function fixShareholderTruename()
    {
        $TbHrEmplMode = M('empl', 'tb_hr_');   
        $TbCrmCompanyShareholderModel = M('company_shareholder', 'tb_crm_'); 
        $info = $TbCrmCompanyShareholderModel->field('id, shareholder_name_alias')->where(['type_cd' => 'N002960002'])->select();
        foreach ($info as $key => $value) {
            $true_name = $this->getTrueNameByAlias($value['shareholder_name_alias'], $TbHrEmplMode); 
            $save['shareholder_name'] = $true_name ? $true_name : '';
            $res = $TbCrmCompanyShareholderModel->where(['id' => $value['id']])->save($save);
            if (false === $res) {
                p($value['id']);
                p($value['shareholder_name_alias']);
                throw new \Exception(L('修改我方公司股东信息数据失败'));
            }
            unset($save);
        }

    }

    // 初始化我方公司数据
    public function getInitCompanyData($excel_model, $allColumn, $allRow)
    {
        // 组装数组
            // 根据code
                // 工商状态处理
                // 注册区域处理（国家）（二字码）
                // 注册区域国家 转id
                // 注册区域（省/州）转id
                // 注册区域（市）转id
                // 注册资本币种转code
                // 法人特殊处理（Vladimir Skrynnik）
                // 法人花名查真名
                // 股东花名查真名
                // 监事花名查真名
        // 插入数据库
            // cmn表（更新/新增）
            // 我方公司表（新增）
                
            // 我方公司股东表（新增）
        $TbHrEmplMode = M('empl', 'tb_hr_');
        $TbMsCmnCdModel = M('cmn_cd', 'tb_ms_');
        $TbMsUserAreaModel = M('user_area', 'tb_ms_');
        $TbCrmCompanyManagementModel = M('company_management', 'tb_crm_'); 
        $TbCrmCompanyShareholderModel = M('company_shareholder', 'tb_crm_'); 


        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {

            $code                           = trim((string)$excel_model->getCell('A' . $currentRow)->getValue());
            $company_business_status_cd     = trim((string)$excel_model->getCell('B' . $currentRow)->getValue());
            $reg_country                    = trim((string)$excel_model->getCell('C' . $currentRow)->getValue());
            $reg_province                   = trim((string)$excel_model->getCell('D' . $currentRow)->getValue());
            $reg_city                       = trim((string)$excel_model->getCell('E' . $currentRow)->getValue());
            $reg_address                    = trim((string)$excel_model->getCell('F' . $currentRow)->getValue());
            $reg_amount_cd                  = trim((string)$excel_model->getCell('G' . $currentRow)->getValue());
            $reg_amount                     = trim((string)$excel_model->getCell('H' . $currentRow)->getValue());
            $legal_alias_name               = trim((string)$excel_model->getCell('I' . $currentRow)->getValue());
            $type_cd                        = trim((string)$excel_model->getCell('J' . $currentRow)->getValue());
            $shareholder_name_alias         = trim((string)$excel_model->getCell('K' . $currentRow)->getValue());
            $supervisor_alias_name          = trim((string)$excel_model->getCell('L' . $currentRow)->getValue());
            $remark                         = trim((string)$excel_model->getCell('M' . $currentRow)->getValue());


            if (strlen($shareholder_name_alias) == '10' && strstr($shareholder_name_alias, "N00")) { // 股东类型是公司   
                $shareholder_name = $shareholder_name_alias;
                $shareholder_name_alias = '';
            }     
            $code_info = $TbMsCmnCdModel->where(['CD' => $code])->find(); // 根据code获取cmn信息

            $company_business_status = 'N002950001' ? 'Y' : 'N';
            $area_info = $this->getAreaIDByName($reg_country, true, $TbMsUserAreaModel);
            $reg_country = $area_info['id'];
            $two_char = $area_info['two_char'];

            $reg_city = $this->getAreaIDByName($reg_city, false, $TbMsUserAreaModel)['id'];
            $reg_province = $this->getAreaIDByName($reg_province, false, $TbMsUserAreaModel)['id'];
            $legal_name = $this->getTrueNameByAlias($legal_alias_name, $TbHrEmplMode); 
            $shareholder_name = $shareholder_name ? $shareholder_name : $this->getTrueNameByAlias($shareholder_name_alias, $TbHrEmplMode); 
            $supervisor_name = $this->getTrueNameByAlias($supervisor_alias_name, $TbHrEmplMode); 

            $com_info = []; $cmn_save = []; $shareholder_info = [];
            $cmn_save['CD'] = $code;
            $cmn_save['USE_YN'] = $company_business_status;
            $cmn_save['two_char'] = $two_char;
            if (false === $TbMsCmnCdModel->save($cmn_save)) {
                p($code);
                p($cmn_save);
                echo M()->_sql();
                throw new \Exception(L('修改我方公司字典数据失败'));
            }
            
            $com_info['our_company_cd'] = $code;
            $com_info['company_business_status_cd'] = $company_business_status_cd;
            $com_info['reg_province'] = $reg_province;
            $com_info['reg_city'] = $reg_city;
            $com_info['reg_country'] = $reg_country;
            $com_info['reg_amount'] = $reg_amount;
            $com_info['reg_amount_cd'] = valCd($reg_amount_cd);
            $com_info['legal_name'] = $legal_name ? $legal_name : $legal_alias_name;
            $com_info['supervisor_name'] = $supervisor_name ? $supervisor_name : '';
            $com_info['remark'] = $remark;
            $com_info['legal_alias_name'] = $legal_alias_name;
            $com_info['supervisor_alias_name'] = $supervisor_alias_name;
            $com_info['created_by'] = 'system';

            $company_management_id = $TbCrmCompanyManagementModel->add($com_info);
            if (!$company_management_id) {
                p($code);
                p($com_info);
                echo M()->_sql();
                throw new \Exception(L('创建我方公司基本数据失败'));
            }
            if ($type_cd) {
                $shareholder_info['company_management_id'] = $company_management_id;
                $shareholder_info['type_cd'] = $type_cd;
                $shareholder_info['shareholder_name_alias'] = $shareholder_name_alias;
                $shareholder_info['shareholder_name'] = $shareholder_name;
                $shareholder_info['created_by'] = 'system';
                if (!$TbCrmCompanyShareholderModel->add($shareholder_info)) {
                    p($code);
                    p($shareholder_info);
                    echo M()->_sql();
                    throw new \Exception(L('创建我方公司股东数据失败'));
                }
            }
        }
    }



    /**
     * 保存我方公司信息
     * @param $request_data
     * @throws Exception
     */
    public function saveCompanyInfo($request_data)
    {
        // 调整使用事务处理
        $com_model = $this->model->table('tb_crm_company_management');
        if(!$com_model->create($request_data)) {
            throw new \Exception(L('创建我方公司数据失败'));
        }
        $our_company_code = $this->isUniqueOurCompanyName($request_data, $request_data['id']);

        //增加我方公司名称(英文)
        $com_model->our_company_en = $request_data['our_company_en'];
        $com_model->company_business_status_cd = $request_data['company_business_status_cd'];
        $com_model->oa_no = $request_data['oa_no'];
        $com_model->reg_province = $request_data['reg_province'] ? $request_data['reg_province'] : null;
        $com_model->reg_city = $request_data['reg_city'] ? $request_data['reg_city'] : null;
        $com_model->reg_country = $request_data['reg_country'] ? $request_data['reg_country'] : null;
        $com_model->reg_address = $request_data['reg_address'];
        //增加注册具体地址(英文)
        $com_model->reg_address_en = $request_data['reg_address_en'];
        $com_model->reg_amount_cd = $request_data['reg_amount_cd'];
        $com_model->reg_amount = $request_data['reg_amount'];

        $com_model->legal_name = $request_data['legal_name'];
        $com_model->legal_alias_name = $request_data['legal_alias_name'];
        $com_model->supervisor_name = $request_data['supervisor_name'];
        $com_model->supervisor_alias_name = $request_data['supervisor_alias_name'];
        $com_model->register_time = $request_data['register_time'];
        $com_model->company_no = $request_data['company_no'];
        $com_model->secretary_company_sp_id = $request_data['secretary_company_sp_id'];
        $com_model->agency_company_sp_id = $request_data['agency_company_sp_id'];

        if ($request_data['id']) {
            $com_model->id = $request_data['id'];
            $com_model->updated_by = $this->user_name;
            if ($our_company_code) {
                $com_model->our_company_cd = $our_company_code;
            }

            if (false === $com_model->save()) {
                throw new \Exception(L('我方公司信息编辑失败'));
            }
        } else {
            $com_model->our_company_cd = $our_company_code;
            $com_model->created_by = $this->user_name;
            $com_man_id = $com_model->add();
            if (!$com_man_id) {
                throw new \Exception(L('我方公司信息新增失败'));
            }
        }
        $srh_info_arr = [];
        $SHARE_NAME_DATA = array();
        if ($request_data['shareholder_info']) {
            // 先清空，后补充
            if (!$com_man_id && $request_data['id']) {
                $srh_count = $this->model->table('tb_crm_company_shareholder')->where(['company_management_id' => $request_data['id']])->count();
                if ($srh_count && strval($srh_count) !== '0') {
                 $del_res = $this->model->table('tb_crm_company_shareholder')->where(['company_management_id' => $request_data['id']])->delete();
                     if (false === $del_res) {
                         p($request_data['id']);
                         throw new \Exception(L('我方公司信息股东信息删除失败'));
                     }
                }
            }   

            foreach ($request_data['shareholder_info'] as $key => $value) {

                if (!$value['shareholder_name'] || !$value['type_cd']) {
                    continue;
                    // throw new \Exception(L('我方股东公司信息参数缺失')); // 因为不是必填选项  
                }

                if (isset($value['shareholder_name'])) {
                    if (in_array($value['shareholder_name'], $srh_info_arr)) {
                        throw new \Exception(L('我方股东名称重复'));
                    }
                    $srh_info_arr[] = $value['shareholder_name'];
                }
                $save_si_info['type_cd'] = $value['type_cd'];
                $save_si_info['shareholder_name'] = $value['shareholder_name'];
                $save_si_info['created_by'] = DataModel::userNamePinyin();
                $save_si_info['created_at'] = DateModel::now();
                $save_si_info['company_management_id'] = $com_man_id ? $com_man_id : $request_data['id'];
                $save_si_info['shareholder_name_alias'] = $value['shareholder_name_alias'] ? $value['shareholder_name_alias'] : '';

                if ($value['type_cd'] == 'N002960002'){
                    array_push($SHARE_NAME_DATA,$value['shareholder_name']);
                }else if ($value['type_cd'] == 'N002960001'){

                    array_push($SHARE_NAME_DATA,cdVal($value['shareholder_name']));
                }
                $add_id = $this->model->table('tb_crm_company_shareholder')->add($save_si_info);
                if (!$add_id) {
                    throw new \Exception(L('我方股东公司信息新增失败'));
                }
                unset($save_si_info);
            } 
            unset($srh_info_arr);   
        }
        //同步英文翻译配置 our_company_name our_company_en
        $language_data[] = ['element' => $request_data['our_company_name'], 'type' => 'N000920200', 'translation_content' => $request_data['our_company_en']];
        if (!(new LanguageModel())->saveAllTrans($language_data)) {
            throw new \Exception(L('保存我方公司同步英文翻译配置失败'));
        }
        // 创建我方公司-----同步ERP供应商或b2b客户
        if (!$request_data['id']) {
            $sync_data = array();
            $sync_data['SP_CHARTER_NO'] = $our_company_code;
            $sync_data['SP_NAME'] = $request_data['our_company_name'];
            $sync_data['SP_NAME_EN'] = isset($request_data['our_company_en']) ? $request_data['our_company_en'] : "-";
            $sync_data['SP_ADDR1'] = $request_data['reg_country'];
            $sync_data['SP_ADDR2'] = $request_data['reg_country'];
            $sync_data['SP_ADDR3'] = $request_data['reg_province'];
            $sync_data['SP_ADDR4'] = $request_data['reg_city'];
            $sync_data['COMPANY_ADDR_INFO'] = $request_data['reg_address'];
            $sync_data['EST_TIME'] = $request_data['register_time'];
            $sync_data['LG_REP'] = isset($request_data['legal_name']) ? $request_data['legal_name'] : "-";
            if (!empty($SHARE_NAME_DATA)){
                $sync_data['SHARE_NAME'] = implode(",", $SHARE_NAME_DATA);
            }else{
                $sync_data['SHARE_NAME'] = "-";
            }
            $url = SYNC_ERP . '/index.php?m=sync&a=supplier';
            Logs("请求-" . date("Y-m-d H:i:s") . "---" . json_encode($sync_data) . '---' . $url, __FUNCTION__, __CLASS__);
            $res = HttpTool::Curl_post_json($url, json_encode($sync_data));
            Logs("响应-" . date("Y-m-d H:i:s") . "----" . $res, __FUNCTION__, __CLASS__);
            $res = json_decode($res, true);
            if ($res) {
                if (!isset($res['code']) || $res['code'] != 2000) {
                    throw new Exception("同步ERP供应商或B2B客户异常：" . $res['code']);
                }
            } else {
                throw new Exception("同步ERP供应商或B2B客户网络异常");
            }
        }

        return $com_man_id ? $com_man_id : $request_data['id'];
    }

    public function getRegisteredCountryIds()
    {
        $com_model = M('company_management', 'tb_crm_');
        return array_unique($com_model->getField('reg_country', true));
    }
}