<?php

/**
 * User:
 * Date:
 * author:
 */
class HrAapi extends Action
{
    private $HrModel;

    public function __construct()
    {
        $this->HrModel = new TbHrModel();
    }

    public function responseData($code, $msg, $data)
    {
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        exit(json_encode($data));
    }

    /**
     * 人员展示、搜索
     *
     * @param 条件参数
     */
    public function showList($Keyword)
    {
        if ($ret = $this->HrModel->showPerson($Keyword)) {
            $code = 200;
            $msg = 'success';
        } else {
            $code = 500;
            $msg = 'error';
            $ret = '当前无人员信息';
        }
        $this->responseData($code, $msg, $ret);
    }

    /**
     * [Mycard]我的名片
     *
     */
    public function Mycard($data)
    {
        if ($res = $this->HrModel->cardData($data)) {
            $code = 200;
            $msg = 'success';
        } else {
            $code = 500;
            $msg = '显示失败';
        }
        $this->responseData($code, $msg, $res);
    }

    public function repetValidate($dataAll)
    {
        $model = D('TbHrEmpl');
        $Admin = D("Admin");
        if (empty($dataAll['card']['ERP_ACT'])) {
            $code = 50001001;
            $msg = 'error';
            $res = '请输入ERP账号';
            return $this->responseData($code, $msg, $res);
        }
        // check erp account

        $where = array(
            'M_NAME' => $dataAll['card']['ERP_ACT'],
            'M_STATUS' => ['neq', 2]
        );
        $detail = $Admin->where($where)->find();
        if ($detail) {
            $code = 50001001;
            $msg = 'error';
            $res = 'ERP账号重复(该用户已存在)';
            return $this->responseData($code, $msg, $res);
            exit();
        }
        $temp_check = $model->where(array("ERP_ACT" => $dataAll['card']['ERP_ACT']))->limit(1)->select();
        if ($temp_check) {
            $code = 50001001;
            $msg = 'error';
            $res = 'ERP账号重复(该用户已存在)';
            return $this->responseData($code, $msg, $res);
            exit();
        }
        // check hua ming
        $temp_check = $model->where(array("EMP_SC_NM" => $dataAll['card']['EMP_SC_NM']))->limit(1)->select();
        if ($temp_check) {
            $code = 50001000;
            $msg = 'error';
            $res = '花名已重复';
            return $this->responseData($code, $msg, $res);
            exit();
        }

        //check gong hao
        $temp_check = $model->where(array("WORK_NUM" => $dataAll['card']['WORK_NUM']))->limit(1)->select();
        if ($temp_check) {
            $code = 50001002;
            $msg = 'error';
            $res = '工号已重复';
            return $this->responseData($code, $msg, $res);
            exit();
        }

    }


    /**
     * 新增人员个人信息
     */
    public function addCustomer($data)
    {
        $data = json_decode($data['params'], true);
        $model = D('TbHrEmpl');
        $tmpData = $model->formatFields($data);

        $dataAll = $tmpData['dataAll'];
        $model->startTrans();

        if (!empty($dataAll['DEPT_NAME']) and !empty($dataAll['DEPT_GROUP'])) {
            $all_dept_info = D('TbHrDept')->gainDeptByCheifName($tmpData['dataAll']['DEPT_GROUP'], $tmpData['dataAll']['DEPT_NAME']);
            $tmp_dept_id = $all_dept_info['dept_group']['ID'];
        }
        if (!empty($dataAll['DEPT_NAME']) and empty($dataAll['DEPT_GROUP'])) {
            $all_dept_info = D('TbHrDept')->gainDeptByMagicStr($dataAll['DEPT_NAME']);
            $tmp_dept_id = $all_dept_info['ID'];
        }
        //var_dump($tmp_dept_id);die;
        if ($ret = $model->create($dataAll, 1)) {
            $this->repetValidate($dataAll);
            //生成账号数据
            $bbmAdminData = $model->getAdminData($dataAll);
            if (($adminok = D("Admin")->add($bbmAdminData)) && ($isok = $model->relation(true)->add($dataAll))) {
                $new_id = $model->getLastInsID();
                $res_dept = D('Hr/HrEmplDept')->addRelation($isok, $data['department']);//同步组织架构
                if (!$res_dept) {
                    $code = 500;
                    $msg = D('Hr/HrEmplDept')->getError();
                    $model->rollback();
                    return $this->responseData($code, 'errpr', $msg);
                    exit();
                }
                $cardData = D("TbHrCard")->where('ID=' . $new_id)->find();
                $new_id = $cardData['EMPL_ID'];   //get emplid
                $id_Data = [
                    'empl_id' => $new_id
                ];
                if ($result = D("Admin")->where('M_NAME=' . "'" . $bbmAdminData['M_NAME'] . "'")->save($id_Data) && D('AdminRole')->add(['M_ID' => $adminok, 'ROLE_ID' => 15])) {
                    $code = 200;
                    $msg = 'success';
                    $res = [
                        'lastInsertId' => $isok,
                        'res' => '新建成功',
                    ];
                    $model->commit();
                } else {
                    $code = 500;
                    $msg = 'add error';
                    $model->rollback();
                }
            }

        } else {
            $code = 500;
            $msg = 'error';
            $reason = $model->getError();
            $res = $reason;

        }
        return $this->responseData($code, $msg, $res);
        exit();
    }

    public function upload($file)
    {
        // check and fix ext - start
        if (!empty($_FILES)) {
            foreach ($_FILES as $k => $v) {
                $image = new cls_image();
                $check_ext = $image->get_img_resource($v['type']);
                if (strpos($v['name'], '.') === false) {
                    if ($check_ext)
                        $_FILES[$k]['name'] = $_FILES[$k]['name'] . '.' . $check_ext;
                }
            }
        }
        //var_dump($_FILES);die;
        // check and fix ext - end
        $filename = '';
        foreach ($file as $key => $value) {
            $filename = $key;
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        //'exts' => array('jpg', 'gif', 'png', 'jpeg'),
        //$upload->maxSize = -1;// 设置附件上传大小
        if ($file['perCardPic'] || $file['graCert'] || $file['degCert']) {
            $type = array('jpg', 'gif', 'png', 'jpeg', 'pdf', 'dot', 'dotx', 'docm', 'doc', 'docx', 'dotm', 'xml');
        }
        if ($file['resume'] || $file['learnProve']) {
            $type = array('pdf', 'dot', 'dotx', 'docm', 'doc', 'docx', 'dotm', 'xml', 'jpg', 'gif', 'png', 'jpeg');
        }
        $upload->allowExts = $type;// 设置附件上传类型
        if (!file_exists(ATTACHMENT_DIR)) {
            mkdir(ATTACHMENT_DIR);
        }
        $upload->savePath = ATTACHMENT_DIR;// 设置附件上传目录
        $name = $filename;
        $upload->thumbExt = 'png';
        if (!$upload->upload()) {   // 上传错误提示错误信息
            $code = 500;
            $msg = 'error';
            $res = [
                'name' => $name,
                'res' => $upload->getErrorMsg(),
            ];

        }
        if ($info = $upload->getUploadFileInfo()) {
            $temp_pic = $_FILES['croppedImage']['tmp_name'];
            $filePath = $info[0]['savepath'];
            $fileName = $info[0]['name'];
            $savename = $info[0]['savename'];
            $code = 200;
            $msg = 'success';
            $res = [
                'filePath' => $filePath,
                'filename' => $fileName,
                'savename' => $savename,
                'name' => $name,
            ];
        }
        return $this->responseData($code, $msg, $res);
        exit();
    }

    public function editTrack($data)
    {
        $m = M('hr_empl_child', 'tb_');
        $emplcount = $m->field('EMPL_ID')->where('EMPL_ID=' . $id)->count();

        if ($emplcount >= 20) {
            $code = 500;
            $msg = 'error';
            $res = '重复提交';
            return $this->responseData($code, $msg, $res);
            exit();
        }
        foreach ($data as $key => $value) {
            $data = json_decode($value, true);
        }
        $id = $data['lastInsertId'];
        foreach ($data as $k => $v) {               //优化批量添加
            if ($k == 'contract') {
                foreach ($v as $index => $val) {
                    $contract[] = array(
                        'V_STR1' => $val['conCompany'],
                        'V_STR2' => $val['natEmploy'],
                        'V_DATE1' => cutting_time($val['trialEndTime']),
                        'V_DATE2' => cutting_time($val['conStartTime']),
                        'V_DATE3' => cutting_time($val['conEndtTime']),
                        'TYPE' => 3,
                        'EMPL_ID' => $id,
                    );
                }
                $res = D('TbHrEmplChild')->addAll($contract);
            }
            if ($k == 'reward') {
                foreach ($v as $index => $val) {
                    $reward[] = array(
                        'V_STR1' => $val['rewardName'],
                        'V_STR10' => $val['rewardContent'],
                        'TYPE' => 7,
                        'EMPL_ID' => $id,
                    );
                }
                $res = D('TbHrEmplChild')->addAll($reward);
            }

            if ($k == 'promo') {
                foreach ($v as $index => $val) {
                    $promo[] = array(
                        'V_STR1' => $val['promoType'],
                        'V_DATE1' => cutting_time($val['promoTime']),
                        'V_STR10' => $val['promoContent'],
                        'TYPE' => 8,
                        'EMPL_ID' => $id,
                    );
                }
                $res = D('TbHrEmplChild')->addAll($promo);
            }

            if ($k == 'interArr') {
                foreach ($v as $index => $val) {
                    $interArr[] = array(
                        'V_STR1' => $val['interType'],
                        'V_DATE1' => cutting_time($val['interTime']),
                        'V_STR2' => $val['interObj'],
                        'V_STR3' => $val['interPerson'],
                        'V_STR4' => $val['interContent'],
                        'V_STR5' => $val['afterCase'],
                        'TYPE' => 9,
                        'EMPL_ID' => $id,
                    );
                }
                $res = D('TbHrEmplChild')->addAll($interArr);
            }
            if ($k == 'paperMiss') {
                foreach ($v as $index => $val) {
                    $paperMiss[] = array(
                        'V_DATE1' => cutting_time($val['paperMissTime']),
                        'V_STR10' => $val['paperMissCon'],
                        'TYPE' => 10,
                        'EMPL_ID' => $id,
                    );
                }
                $res = D('TbHrEmplChild')->addAll($paperMiss);
            }
        }

        if ($res) {
            $code = 200;
            $msg = 'success';
            $res = array(
                'res' => '创建成功',
            );
        } else {
            $code = 500;
            $msg = 'error';
            $res = '添加失败';
        }
        return $this->responseData($code, $msg, $res);
        exit();
    }

    /**
     *状态更改
     */
    public function statusChange($params)
    {
        //var_dump($params);die;
        $selectedID = $params['EMPL_ID'];
        $m = M('hr_card', 'tb_');
        $dept_model = M('hr_empl_dept', 'tb_');

        if ($params['perJobDate']) {
            //var_dump($selectedID);
            $time['PER_JOB_DATE'] = cutting_time($params['perJobDate']);
            $time['COMPANY_AGE'] = $params['COMPANY_AGE'];
            $res = $m->where('EMPL_ID=' . $selectedID)->save($time);
        }
        if ($params['deptName']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('DEPT_NAME', $params['deptName']);
        }
        if ($params['deptId']) {
            $res = $dept_model->where('ID2=' . $selectedID)->setField('ID1', $params['deptId']);
        }

        if ($params['emplGroup']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('DEPT_GROUP', $params['emplGroup']);
        }
        if ($params['jobCd']) {
            $jobcd['JOB_CD'] = $params['jobCd'];
            $jobcd['JOB_EN_CD'] = $params['JOB_EN_CD'];
            $res = $m->where('EMPL_ID=' . $selectedID)->save($jobcd);
        }
        if ($params['JobEnCd']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('JOB_EN_CD', $params['JobEnCd']);
        }
        if ($params['workPlace']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('WORK_PALCE', $params['workPlace']);
        }
        if ($params['directLeader']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('DIRECT_LEADER', $params['directLeader']);
        }
        if ($params['departHead']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('DEPART_HEAD', $params['departHead']);
        }
        if ($params['dockingHr']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('DOCKING_HR', $params['dockingHr']);
        }
        if ($params['rank']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('RANK', $params['rank']);
        }
        if ($params['status']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('STATUS', $params['status']);
        }
        if ($params['jobTypeCd']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('JOB_TYPE_CD', $params['jobTypeCd']);
        }
        if ($params['sex'] != '') {
            $sex['SEX'] = $params['sex'];
            $res = $m->where('EMPL_ID=' . $selectedID)->save($sex);
        }
        if ($params['perIsSmoking']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('PER_IS_SMOKING', $params['perIsSmoking']);
        }
        if ($params['perIsMarried']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('PER_IS_MARRIED', $params['perIsMarried']);
        }
        if ($params['perPolitical']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('PER_POLITICAL', $params['perPolitical']);
        }
        if ($params['hosehold']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('HOUSEHOLD', $params['hosehold']);
        }
        if ($params['perNational']) {
            $res = $m->where('EMPL_ID=' . $selectedID)->setField('PER_NATIONAL', $params['perNational']);
        }
        //echo $m->_sql();die;
        $code = 200;
        $msg = 'success';
        $res = '修改状态成功';

        return $this->responseData($code, $msg, $res);

    }

    /**
     * 获取下拉框数据
     */
    public function acquireData($data)
    {
        //dump($data);die;
        $selectCity = $data['city'];
        $m = M('ms_cmn_cd', 'tb_');
        $acqData = $m->field('CD_VAL,ETC,CD,USE_YN')->select();
        //可被选择的岗位

        foreach ($acqData as $key => $value) {
            $code = substr($value['CD'], 0, 6);
            if ($value['USE_YN'] == 'Y') {
                switch ($code) {
                    case 'N00155':
                        $workPlace[] = $value;
                        break;
                    case 'N00156':
                        $status[] = $value;
                        break;
                    case 'N00157':
                        $jobCdType[] = $value;
                        break;
                    case 'N00158':
                        $langa[] = $value;
                        break;
                    case 'N00159':
                        $ThingName[] = $value;
                        break;
                    case 'N00160':
                        $recordType[] = $value;
                        break;
                    case 'N00161':
                        $sayType[] = $value;
                        break;
                    case 'N00162':
                        $rank[] = $value;
                        break;
                    case 'N00166':
                        $married[] = $value;
                        break;
                    case 'N00167':
                        $pot[] = $value;
                        break;
                    case 'N00163':
                        $adminJob1[] = $value;
                        break;
                    case 'N00164':
                        $adminJob2[] = $value;
                        break;
                    case 'N00165':
                        $adminJob3[] = $value;
                        break;
                    case 'N00170':
                        $peoples[] = $value;
                        break;
                    case 'N00169':
                        $employNat[] = $value;
                        break;
                    case 'N00168':
                        $eduback[] = $value;
                        break;
                    case 'N00171':
                        $validateRes[] = $value;
                        break;
                    case 'N00172':
                        $recruitStatus[] = $value;
                        break;
                    case 'N00174':
                        $recruitSource[] = $value;
                        break;
                    case 'N00083':
                        $storePlat[] = $value;
                        break;
                    case 'N00128':
                        $saleTeam[] = $value;
                        break;
                    case 'N00190':
                        $companyInfo[] = $value;
                        break;
                    case 'N00191':
                        $contractStatus[] = $value;
                        break;
                    case 'N00124':
                        $ourCompany[] = $value;
                        break;
                    case 'N00337':
                        $defaultTimezone[] = $value;
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
//        //常规职位选择
//                switch ($code) {
//                case 'N00163':
//                    $job1[] = $value;
//                    break;
//                case 'N00164':
//                    $job2[] = $value;
//                    break;
//                case 'N00165':
//                    $job3[] = $value;
//                    break;
//
//                    default:
//                        # code...
//                        break;
//                }


//    }
//        //常规数据
//        $job = array();
//        if($job1) $job = $job1;
//        $jobzh = $data['jobzh'];  //选中的中文职位
//        if ($jobzh) {
//            $joben = $m->field('ETC')->where('CD_VAL='."'".$jobzh."'")->find();
//        }
//        if ($job2) {
//            foreach ($job2 as  $value) {
//                array_push($job, $value);
//            }
//        }
//        if ($job3) {
//            foreach ($job3 as  $value) {
//                array_push($job, $value);
//            }
//        }
        $job = D('TbHrJobs')->field('ID,CD_VAL,ETC,USE_YN')->select();
//        foreach ($jobTableData as $value) {
//            array_unshift($job, $value);
//        }

        $leader = M('hr_card', 'tb_')->field('EMP_SC_NM')->select();   //所有员工信息
        $provincedata = M('crm_site', 'tb_')->field('ID,NAME')->where('PARENT_ID=1')->select();
        $proId = $data['proId'];
        if ($proId) {
            $citydata = M('crm_site', 'tb_')->field('ID,NAME')->where('PARENT_ID=' . $proId)->select();
        }
        $cityId = $data['cityId'];
        if ($cityId) {
            $areadata = M('crm_site', 'tb_')->field('ID,NAME')->where('PARENT_ID=' . $cityId)->select();
        }
        $deptData = D('TbHrDept')->field('ID,DEPT_NM')->where("DELETED_BY IS NULL")->select();
        $deptTopData = D("TbHrDept")->field("ID,DEPT_NM")->where("(DEPT_LEVEL = 0 OR DEPT_LEVEL = 1) AND DELETED_BY IS NULL")->select();
        $currentAccount = $_SESSION['m_loginname'];

        //国家数据
        $countryData = M('ms_user_area', 'tb_')->field("id,zh_name,parent_no,rank,area_no")->where('area_type=1 AND parent_no = 0')->order('rank asc')->select();

        //岗位管理职位数据
        $adminJob = array();
        if ($adminJob1) $adminJob = $adminJob1;

        if ($adminJob2) {
            foreach ($adminJob2 as $value) {
                array_push($adminJob, $value);
            }
        }
        if ($adminJob3) {
            foreach ($adminJob3 as $value) {
                array_push($adminJob, $value);
            }
        }
        $jobTableData = D('TbHrJobs')->field('CD_VAL,ETC,USE_YN')->where("USE_YN='Y'")->select();
        foreach ($jobTableData as $value) {
            array_unshift($adminJob, $value);
        }


        $acquireData = array(
            'workPlace' => $workPlace,
            'status' => $status,
            'jobCdType' => $jobCdType,
            'langa' => $langa,
            'ThingName' => $ThingName,
            'recordType' => $recordType,
            'sayType' => $sayType,
            'rank' => $rank,
            'job' => $job,
            'leader' => $leader,
            'provincedata' => $provincedata,
            'citydata' => $citydata,
            'areadata' => $areadata,
            'joben' => $joben,
            'married' => $married,
            'pot' => $pot,
            'peoples' => $peoples,
            'employNat' => $employNat,
            'validateRes' => $validateRes,
            'employNat' => $employNat,
            'recruitStatus' => $recruitStatus,
            'deptData' => $deptData,
            'NAME1' => $currentAccount,
            'recruitSource' => $recruitSource,
            'storePlat' => $storePlat,
            'saleTeam' => $saleTeam,
            'countryData' => $countryData,
            'adminJob' => $adminJob,
            'companyInfo' => $companyInfo,
            'contractStatus' => $contractStatus,
            'deptTopData' => $deptTopData,    //顶级部门  0,1
            'ourCompany' => $ourCompany,
            'defaultTimezone' => $defaultTimezone
        );
        return $acquireData;
    }

    /**
     * 编辑名片信息
     *
     * @return [type] [description]
     */
    public function changeCard($data)
    {
        $data = json_decode($data['params'], true);
        $id = $data['emplid'];
        $model_empl = D('TbHrEmpl');
        // start trans
        $model_empl->startTrans();
        $tmpData = $model_empl->formatFields($data);
        //校验数据,给数组加一个is_error条件
        $oldEmplData = D('TbHrEmpl')->where('ID=' . $id)->find();
        $oldScNm = $oldEmplData['ERP_ACT'];
        $nowAccount = $_SESSION['m_loginname'];
        if ($tmpData['is_error'] == 1 && $oldScNm == $nowAccount) {
            $code = 500;
            $msg = 'error';
            $res = $tmpData['msg'];
            return $this->responseData($code, $msg, $res);
        }
        $newScNm = $tmpData['dataAll']['EMP_SC_NM'];
        $newStatus = $tmpData['dataAll']['STATUS'];
        $editEmpl = D('TbHrCard')->where("EMPL_ID={$id}")->find();
        $editStauts = $editEmpl['STATUS'];
        $scNm = D('TbHrCard')->where("EMP_SC_NM='$newScNm' AND STATUS!='离职'")->select();
        if ($newStatus == '在职' && $editStauts == '离职') {
            if ($scNm) {
                $code = 500;
                $msg = 'error';
                $res = '花名重复';
                return $this->responseData($code, $msg, $res);
            }
        }
        if ($newStatus == '离职') {
            $save_close['IS_USE'] = 1;
            $save_close['M_STATUS'] = 2;
            $save_close['M_DELETED'] = DateModel::now();
        }else{
            $save_close['IS_USE'] = 0;
            $save_close['M_STATUS'] = 0;
            $save_close['M_DELETED'] = DateModel::now();
        }
        if ($save_close) {
            $close_status = M('admin')->where("M_NAME = '{$editEmpl['ERP_ACT']}'")
                ->save($save_close);
            if (false === $close_status) {
                $code = 500;
                $msg = 'error';
                $res = L('更改 ERP 帐号失败');
                return $this->responseData($code, $msg, $res);
            }
        }
        $data1 = $tmpData['dataAll'];

        $old_data = M('hr_empl_child', 'tb_')->where('TYPE  in (0,1,2,4,5,11,12) AND EMPL_ID = ' . $id)->select();
        $old_ids = array_column($old_data, 'ID');
        $old_max = max($old_ids);

        // start update part 0001
        $model_empl->create($data1, Model::MODEL_UPDATE);
        // check edit
        if ($model_empl->getError()) {
            $errTxt = $model_empl->getError();
            $code = 500;
            $msg = 'error';
            $res = '编辑失败' . $errTxt;
            return $this->responseData($code, $msg, $res);
        }

        // $this->editRepetValidate($data1);
        $data1['ID'] = $id;
        unset($data1['ERP_PWD']);
        unset($data1['card']['ERP_PWD']);
        if ($old_ids) {
            $where = 'id in(' . implode(',', $old_ids) . ')';
            $resDel = M('hr_empl_child', 'tb_')->where($where)->delete();
        }
        $result = $model_empl->relation(true)->where('ID=' . $id)->save($data1);
        //编辑bbmadmin
        $adminData = D("TbHrEmpl")->getAdminData($data1);

        unset($adminData['ROLE_ID']);
        $res = D("admin")->where('empl_id=' . $id)->find();

        if ($res) {
            unset($adminData['M_ADDTIME']);
            $adminData['M_UPDATED'] = time();
            if (!$res = D("admin")->where('empl_id=' . $id)->save($adminData)) {
                $code = 500;
                $msg = 'error';
                $res = "edit account error 账号重复";
                return $this->responseData($code, $msg, $res);
            }
        }

        if (!$result) {
            $errTxt = $model_empl->getError();
            $code = 500;
            $msg = 'error';
            $res = '校验编辑失败,' . $errTxt;
            return $this->responseData($code, $msg, $res);
        }


        $res_dept = D('Hr/HrEmplDept')->addRelation($id, $data['department']);
        if (!$res_dept) {
            $errTxt = D('Hr/HrEmplDept')->getError();
            $code = 500;
            $msg = 'error';
            $res = '校验编辑失败,' . $errTxt;
            return $this->responseData($code, $msg, $res);
        }

        /*
        $tmpData = D('TbHrEmpl')->fmtDeptByEmpl($data1);
        // check department
        $dept_id = null;
        $dept_id = $dept_id ? $dept_id : (empty($tmpData['format_DEPT_GROUP_id']) ? null : $tmpData['format_DEPT_GROUP_id']);
        $dept_id = $dept_id ? $dept_id : (empty($tmpData['format_DEPT_NAME_id']) ? null : $tmpData['format_DEPT_NAME_id']);
        // relate employee and department
        $status = D('TbHrDept')->matchEmployeeToDepartment($id,$dept_id);
        */

        //校验
        //var_dump($data1);die;
        $this->editRepetValidate($data1);


        $code = 200;
        $msg = 'success';
        $res = '编辑成功';
        $model_empl->commit();
        return $this->responseData($code, $msg, $res);
        die();
        // end - up is new func
    }

    public function editRepetValidate($dataAll)
    {
        $model = D('TbHrEmpl');
        // check erp account
        $temp_check = $model->where(array("ERP_ACT" => $dataAll['card']['ERP_ACT']))->count();
        if ($temp_check == '2') {
            $code = 50001001;
            $msg = 'error';
            $res = 'ERP账号重复(该用户已存在)';
            $model->rollback();
            return $this->responseData($code, $msg, $res);
            exit();
        }
        // check hua ming
        $temp_check = $model->where(array("EMP_SC_NM" => $dataAll['card']['EMP_SC_NM']))->count();
        if ($temp_check === '2') {
            $code = 50001000;
            $msg = 'error';
            $res = '花名已重复';
            $model->rollback();
            return $this->responseData($code, $msg, $res);
            exit();
        }
        //check gong hao
        $temp_check = $model->where(array("WORK_NUM" => $dataAll['card']['WORK_NUM']))->count();
        if ($temp_check === '2') {
            $code = 50001002;
            $msg = 'error';
            $res = '工号已重复';
            $model->rollback();
            return $this->responseData($code, $msg, $res);
            exit();
        }
    }

    public function changeTrack($data)
    {
        foreach ($data as $key => $value) {
            $data = json_decode($value, true);
        }
        $conInfo = $data['contract'];
        $reward = $data['reward'];
        $promo = $data['promo'];
        $paper = $data['paperMiss'];
        $inter = $data['interArr'];
        $hrRecord = $data['hrRecord'];
        $id = $data['emplid'];
        $m = M('hr_empl_child', 'tb_');
        $m->startTrans();
        $resDel = $m->where('TYPE  in (3,7,8,9,10,13) AND EMPL_ID = ' . $id)->delete();
        foreach ($conInfo as $key => $value) {
            $value3 = array();
            $value3['V_STR1'] = $value['conCompany'];
            $value3['V_STR2'] = $value['natEmploy'];
            $value3['V_DATE1'] = cutting_time($value['trialEndTime']);
            $value3['V_DATE2'] = cutting_time($value['conStartTime']);
            $value3['V_DATE3'] = cutting_time($value['conEndTime']);
            $value3['V_STR3'] = $value['conStatus'];

            $value3['TYPE'] = 3;
            $value3['EMPL_ID'] = $id;
            $res3 = $m->add($value3);
        }


        foreach ($reward as $key => $value) {
            $value7 = array();
            $value7['V_STR1'] = $value['rewardName'];
            $value7['V_STR10'] = $value['rewardContent'];
            $value7['TYPE'] = 7;
            $value7['EMPL_ID'] = $id;
            $res7 = $m->add($value7);


        }
        foreach ($promo as $key => $value) {
            $value8 = array();
            $value8['V_STR1'] = $value['promoType'];
            $value8['V_DATE1'] = cutting_time($value['promoTime']);
            $value8['V_STR10'] = $value['promoContent'];
            $value8['TYPE'] = 8;
            $value8['EMPL_ID'] = $id;
            $res8 = $m->add($value8);
            //$res8 = $m->where('TYPE =8 AND EMPL_ID='.$id)->save($value8);

        }
        foreach ($paper as $key => $value) {
            $value10 = array();
            $value10['V_DATE1'] = cutting_time($value['paperMissTime']);
            $value10['V_STR10'] = $value['paperMissCon'];
            $value10['TYPE'] = 10;
            $value10['EMPL_ID'] = $id;
            $res10 = $m->add($value10);
            //$res10 = $m->where('TYPE =10 AND EMPL_ID='.$id)->save($value10);

        }
        foreach ($inter as $key => $value) {
            $value9 = array();
            $value9['V_STR1'] = $value['interType'];
            $value9['V_DATE1'] = cutting_times($value['interTime']);
            $value9['V_STR2'] = $value['interObj'];
            $value9['V_STR3'] = $value['interPerson'];
            $value9['V_STR4'] = $value['interContent'];
            $value9['V_STR5'] = $value['afterCase'];
            $value9['TYPE'] = 9;
            $value9['EMPL_ID'] = $id;
            $res9 = $m->add($value9);
            //$res9 = $m->where('TYPE =9 AND EMPL_ID='.$id)->save($value9);
        }

//var_dump($hrRecord);die;
        foreach ($hrRecord as $key => $value) {
            $value11['V_STR1'] = $value['reContent'];
            $value11['V_DATE1'] = cutting_time($value['reTime']);
            $value11['TYPE'] = 13;
            $value11['EMPL_ID'] = $id;
            $res11 = $m->add($value11);
        }

        if ($res3 && $res7 && $res8 && $res9 && $res10) {
            $m->commit();
            $code = 200;
            $msg = 'success';
            $res = '编辑成功';
            return $this->responseData($code, $msg, $res);
            die;
        } else {
            $m->rollback();
            $code = 500;
            $msg = 'error';
            $res = '编辑失败';
            return $this->responseData($code, $msg, $res);
            die;
        }

    }

    public function address($data)
    {

        if ($data['areaNo']) {
            $params = $data['areaNo'];
            $data = curl_request(HOST_URL_API . "/index/area.json?parentNo={$params}");
        } else {
            $data = curl_request(HOST_URL_API . "/index/area.json?parentNo=1");
        }
        return $data;
    }

    public function export($id)
    {
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->createSheet();//创建新的内置表
        $objPHPExcel->setActiveSheetIndex(1);//把新创建的sheet设定为当前活动sheet
        $objSheet = $objPHPExcel->getActiveSheet();//获取当前活动sheet
        $objSheet->setTitle("员工导出数据");//给当前活动sheet起个名称
        $arrId = explode(',', $id);
        $m = M('hr_card', 'tb_');
        $dataEmpl = array();
        foreach ($arrId as $v) {
            $dataEmpl[] = $m->where('EMPL_ID=' . $v)->find();
        }

        $objSheet->setCellValue("A1", "工号")->setCellValue("B1", "入职时间")->setCellValue("C1", "司龄")->setCellValue("D1", "花名")->setCellValue("E1", "中文职位")
            ->setCellValue("F1", "部门")->setCellValue("G1", "组别")->setCellValue("H1", "工作地点")->setCellValue("I1", "身份证号")->setCellValue("J1", "出生日期")
            ->setCellValue("K1", "性别")->setCellValue("L1", "户籍")->setCellValue("M1", "手机号码")->setCellValue("N1", "毕业院校")->setCellValue("O1", "学历");//填充数据
        $j = 2;
        foreach ($dataEmpl as $key => $val) {
            if ($val['SEX'] == '1') {
                $val['SEX'] = '女';
            } else {
                $val['SEX'] = '男';
            }
            if ($val['PER_JOB_DATE'] == '0000-00-00 00:00:00') {
                $val['PER_JOB_DATE'] = '';
            } else {
                $val['PER_JOB_DATE'] = substr($val['PER_JOB_DATE'], 0, 10);
            }
            if ($val['PER_BIRTH_DATE'] == '0000-00-00 00:00:00') {
                $val['PER_BIRTH_DATE'] = '';
            } else {
                $val['PER_BIRTH_DATE'] = substr($val['PER_BIRTH_DATE'], 0, 10);
            }

            $objSheet->setCellValueExplicit("A" . $j, $val['WORK_NUM'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue("B" . $j, $val['PER_JOB_DATE'])->setCellValue("C" . $j, $val['COMPANY_AGE'] . "年")
                ->setCellValue("D" . $j, $val['EMP_SC_NM'])->setCellValue("E" . $j, $val['JOB_CD'])->setCellValue("F" . $j, $val['DEPT_NAME'])
                ->setCellValue("G" . $j, $val['DEPT_GROUP'])->setCellValue("H" . $j, $val['WORK_PALCE'])->setCellValueExplicit("I" . $j, (string)$val['PER_CART_ID'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue("J" . $j, $val['PER_BIRTH_DATE'])->setCellValue("K" . $j, $val['SEX'])->setCellValue("L" . $j, $val['PER_RESIDENT'])
                ->setCellValue("M" . $j, $val['PER_PHONE'])->setCellValue("N" . $j, $val['GRA_SCHOOL'])->setCellValue("O" . $j, $val['EDU_BACK']);
            $j++;
        }
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');//生成excel文件
        //$objWriter->save($dir."/export_1.xls");//保存文件
        function browser_export($type, $filename)
        {
            if ($type == "Excel5") {
                header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
            } else {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
            }
            header('Content-Disposition: attachment;filename="' . $filename . '"');//告诉浏览器将输出文件的名称
            header('Cache-Control: max-age=0');//禁止缓存
        }

        browser_export('Excel5', 'empl.xls');//输出到浏览器
        return $objWriter->save("php://output");
    }

    /**
     *  HR筛选导出选项
     *
     */
    public function exportHrByManual()
    {
        // req data
        $ids = $_REQUEST['EMPL_ID'];
        $need_info = $_REQUEST['need_info'];    //导出的字段
        $need_info = @json_decode($need_info);
        $need_info = ZFun::arrNoEmptyMix($need_info);   //防空数组
        // doing
        $arrId = explode(',', $ids);
        $arrId = ZFun::arrNoEmptyMix($arrId);
        $m = D('TbHrCard');
        $dataEmpl = array();
        foreach ($arrId as $v) {
            #取部门字段
            // $dataEmpls = $m->where('EMPL_ID=' . $v)->find();
            $dataEmpls = $m->field('tb_hr_card.*,group_concat(b.DEPT_NM) as DEPT_NAME_REAL')
            ->join('left join tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_id')
            ->join('left join tb_hr_dept b on b.ID=a.ID1')
            ->where('tb_hr_card.EMPL_ID=' . $v)
            ->group('EMPL_ID')
            ->find();
            $dataEmpls['DEPT_NAME'] = $dataEmpls['DEPT_NAME_REAL'];
            unset($dataEmpls['DEPT_NAME_REAL']);
           
            $dataEmpl[] = $dataEmpls;
        }
        $all_child_list = D('TbHrEmplChild')->listByIds($arrId);
        //gainKeyInfo()    时间处理
        //var_dump($dataEmpl);die();
        // prepare excel
        vendor("PHPExcel.PHPExcel");
        $objectPHPExcel = new PHPExcel ();
        $objectPHPExcel->setActiveSheetIndex(0);
        $objectPHPExcel->getActiveSheet()->setTitle('员工导出数据');
        $objSheet = $objectPHPExcel->getActiveSheet();//获取当前活动sheet

        $filename = 'empl.xls';
        if (empty($dataEmpl)) {
            header('Content-Type:application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '"');//告诉浏览器将输出文件的名称
            $objWriter = PHPExcel_IOFactory::createWriter($objectPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            return null;
        }

        $line = 1;
        $i = 0;
        foreach ($need_info as $key => $val) {   //val字段名,传入模型方法中找到对应的值
            $col_cell = PHPExcel_Cell::stringFromColumnIndex($i);    //超过26行返回AA、AB...
            $objSheet->getStyle($col_cell)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);   //设置每个字段文本格式
            $use_cell = PHPExcel_Cell::stringFromColumnIndex($i) . $line;
            $use_val = $m->findKeyLable($val);
            $objSheet->setCellValue($use_cell, $use_val);   //写数据
            ++$i;
        }
        $line = 2;
        foreach ($dataEmpl as $k_data => $v_data) {
            $v_data['children_list'] = isset($all_child_list[$v_data['EMPL_ID']]) ? $all_child_list[$v_data['EMPL_ID']] : null;
            if ($all_child_list === null) {
                $v_data['children_list'] = D('TbHrEmplChild')->gainEmplInfoByType($v_data['EMPL_ID']);
            }
            $i = 0;
            foreach ($need_info as $key => $val) {
                $use_cell = PHPExcel_Cell::stringFromColumnIndex($i) . $line;
                $use_val = $m->findKeyData($val);
                $use_val = $m->gainKeyInfo($v_data, $use_val);
                // $objSheet->setCellValue($use_cell,$use_val);
                $objSheet->setCellValueExplicit($use_cell, $use_val, PHPExcel_Cell_DataType::TYPE_STRING);
                ++$i;
            }
            ++$line;
        }
        // var_dump($need_info);
        //die();
        header('Content-Type:application/vnd.ms-excel');
        header('Cache-Control: max-age=0');//禁止缓存
        header('Content-Disposition: attachment;filename="' . $filename . '"');//告诉浏览器将输出文件的名称
        $objWriter = PHPExcel_IOFactory::createWriter($objectPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    public function delete($data)
    {
        $id = $data['emplId'];
        $account = $data['erpAct'];
        //关联删除
        $res = D('TbHrEmpl')->relation(true)->delete($id);
        //账号同时删除 (!待验证)
        $adminRes = D('admin')->where("M_NAME=" . "'" . $account . "'")->delete();

        if ($res) {
            $code = 200;
            $msg = 'success';
            $res = '删除成功';
        } else {
            $code = 500;
            $msg = 'error';
            $res = '删除失败';
        }
        return $this->responseData($code, $msg, $res);
    }

    //change password
    public function person_changePwd($erpPwd, $erpAct, $erpid)
    {
        $data = D("TbHrEmpl")->getNewPwd($erpPwd, $erpAct, $erpid);
        $res = D("TbHrEmpl")->relation(true)->where("ID=" . $erpid)->save($data['tmp']);
        $adminRes = D('admin')->where('M_NAME=' . "'" . $erpAct . "'")->save($data['adminData']);
        $code = 200;
        $msg = 'success';
        $res = '修改成功';
        return $this->responseData($code, $msg, $res);
    }


    //reset password
    public function person_emailResetPwd($erpAct, $erpid, $EmpScNm)
    {
        $password = D("TbHrEmpl")->getRandPwd();
        $personData = D("TbHrEmpl")->where('ID=' . $erpid)->find();
        $address = $personData['SC_EMAIL'];  //邮箱
        $SCName = $personData['EMP_SC_NM'];  //花名
        $title = "密码重置 - {$SCName} - " . date("Y-m-d");
        $content = D("TbHrEmpl")->getEmailContent($SCName, $password, $EmpScNm);
        if (empty($address)) {
            $code = 500;
            $msg = 'error';
            $res = '该用户暂未设置花名邮箱，请填写!';
            return $this->responseData($code, $msg, $res);
        }

        //修改密码
        $data = D("TbHrEmpl")->getNewPwd($password, $erpAct, $erpid);
        $res = D("TbHrEmpl")->relation(true)->where("ID=" . $erpid)->save($data['tmp']);
        $adminRes = D('admin')->where('M_NAME=' . "'" . $erpAct . "'")->save($data['adminData']);

        $email = new SMSEmail();
        if ($emailRes = $email->sendEmail($address, $title, $content) && $res && $adminRes) {
            $code = 200;
            $msg = 'success';
            $res = '邮件已发送';
            return $this->responseData($code, $msg, $res);
        } else {
            $code = 500;
            $msg = 'error';
            $res = '重置失败,请检查花名邮箱';
            return $this->responseData($code, $msg, $res);
        }
    }

    //bussiness card
    public function person_business_card()
    {
        $prepared_act = $_REQUEST['prepared_by'];
        if ($data = D("TbHrEmpl")->getBusinessData($prepared_act)) {
            $code = 200;
            $msg = 'success';
            $res = $data;
        } else {
            $code = 500;
            $msg = 'error';
            $res = [];
        }
        return $this->responseData($code, $msg, $res);
    }

    public function deptData()
    {
        $m = D('TbHrDept');
        $topDept = $m->where('PAR_DEPT_ID=0')->select();
        $arrId = array_column($topDept, 'ID');
        $deptData = array();
        foreach ($arrId as $key => $value) {
            $deptData[] = $m->where('PAR_DEPT_ID=' . $value)->select();
        }

    }


    //edit、add
    public function addRec($data)
    {
        if ($data['IS_NOT_ARRANGE']) {
            $data['JOB_TIME2'] = null;
        }
        $m = D('TbHrResume');

        $data['JOB_DATE2'] = cutting_time($data['JOB_DATE2']) ? cutting_time($data['JOB_DATE2']) : '';
        $data['JOB_TIME2'] = cutting_timess($data['JOB_TIME2']) ? cutting_timess($data['JOB_TIME2']) : '';
        $data['JOB_DATE1'] = cutting_time($data['JOB_DATE1']) ? cutting_time($data['JOB_DATE1']) : '';
        $data['JOB_TIME1'] = cutting_timess($data['JOB_TIME1']) ? cutting_timess($data['JOB_TIME1']) : '';
        $id = $data['ID'];
        if (!$data['ID']) {     //新建
            //var_dump($data);die;
            if ($data = $m->create($data, 1)) {
                if (count($data['NAME2']) > 0) {
                    $arr = $data['NAME2'];
                    $nameStr = implode($arr, ',');   //面试官
                    $data['NAME2'] = $nameStr;
                }
                $m->startTrans();
                $resOperationData = D('TbHrResumeOperationLog')->create($data, 1);
                $resOperationData['RESUME_ID'] = $data['ID'];

                if ($validateTel = $m->where("TEL=" . "'" . $data['TEL'] . "'")->find() and $data['TEL'] != '') {
                    $code = 500;
                    $msg = "error";
                    $res = "该号码已有简历信息";
                    return $this->responseData($code, $msg, $res);
                }
                if ($m->add($data)) {
                    $resumeId = $m->getLastInsID();
                    $resOperationData['RESUME_ID'] = $resumeId;
                    if (D('TbHrResumeOperationLog')->add($resOperationData)) {
                        $code = 200;
                        $msg = $resumeId;
                        $res = '新建成功';
                        $m->commit();
                    } else {
                        $code = 500;
                        $res = '日志保存失败';
                    }
                } else {
                    $code = 500;
                    $msg = 'error';
                    $res = '新建失败';
                }
            } else {
                $code = 500;
                $msg = 'error';
                $res = $m->getError();
                $m->rollback();
            }
        } else {
            if ($data['IS_NOT_ARRANGE']) {
                $data['JOB_DATE2'] = '';
                $data['JOB_TIME2'] = '';
            }
            if ($data = D('TbHrResume')->create($data, 1)) {
                $data['NAME2'] = implode($data['NAME2'], ',');
                unset($data['CREATE_TIME']);
                $selfData = $m->where('ID=' . $id)->find();

                if ($selfData['TEL'] != $data['TEL']) {
                    if ($validateTel = $m->where("TEL=" . "'" . $data['TEL'] . "'")->find() and $data['TEL'] != '') {
                        $code = 500;
                        $msg = "error";
                        $res = "该号码已有简历信息";
                        return $this->responseData($code, $msg, $res);
                    }
                }
                if ($m->where('ID=' . $id)->save($data)) {
                    $resOperationData = D('TbHrResumeOperationLog')->create($data, 1);
                    $resOperationData['RESUME_ID'] = $data['ID'];
                    $resOperationData['ID'] = '';
                    if ($selfData['JOB_MSG'] != $data['JOB_MSG'] or $selfData['STATUS'] != $data['STATUS']) {
                        if (D('TbHrResumeOperationLog')->add($resOperationData)) {
                            $code = 200;
                            $msg = $id;
                            $res = '修改成功';
                        } else {
                            $code = 500;
                            $msg = 'error';
                            $res = '操作日志未记录';
                        }
                    }
                    $code = 200;
                    $msg = $id;
                    $res = '修改成功';
                } else {
                    $code = 500;
                    $msg = 'error';
                    $res = '未做修改';
                }
            } else {
                $code = 500;
                $msg = 'error';
                $res = $m->getError();
                $m->rollback();
            }

        }

        return $this->responseData($code, $msg, $res);
        exit();
    }


    public function recruitData($keywords)
    {
        $recruitData = D('TbHrResume')->showResume($keywords);
        //var_dump($recruitData);die;
        if ($recruitData) {
            $code = 200;
            $msg = $recruitData['count'];
            unset($recruitData['count']);
            $res = $recruitData;
        } else {
            $code = 500;
            $msg = 'error';
            $res = '无数据';
        }
        return $this->responseData($code, $msg, $res);
        exit();
    }


    public function test()
    {
        echo "string";
        var_dump($status);
        die;
    }

}