<?php 

class TbHrEmplModel extends BaseModel
{
	
    protected $trueTableName = 'tb_hr_empl';
    
    protected $_link = [
    	'child' => [
    		'mapping_type' => HAS_MANY,
            'class_name' => 'TbHrEmplChild',
            'foreign_key' => 'EMPL_ID',     //关联模型的字段
            'relation_foreign_key' => 'ID',  //自己的关联字段
            'mapping_name' => 'empl_child', //存放关联模型数据的键,用来获取数据
    	],
    	'card' =>[
    		'mapping_type' => HAS_ONE,
    		'class_name' => 'TbHrCard',
    		'foreign_key' => 'EMPL_ID',
    		'relation_foreign_key' => 'ID',
    		'mapping_name' => 'card',
    	]
    ];
    
    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];

    protected $_validate = [
        ['WORK_NUM','require','请输入工号'],//默认情况下用正则进行验证
        ['EMP_SC_NM','require','请输入花名'],//默认情况下用正则进行验证
        ['PER_JOB_DATE','require','请输入入职时间'],//默认情况下用正则进行验证
//        ['DEPT_NAME','require','请输入部门'],//默认情况下用正则进行验证
        //['DEPT_GROUP','require','请输入组别'],//默认情况下用正则进行验证
        ['COMPANY_AGE','require','请输入司龄'],//默认情况下用正则进行验证
        ['JOB_ID','require','请选择职位'],//默认情况下用正则进行验证
        ['JOB_CD','require','请输入职中文职位'],//默认情况下用正则进行验证
        ['JOB_EN_CD','require','请输入英文职位'],//默认情况下用正则进行验证
        ['WORK_PALCE','require','请输入工作地点'],//默认情况下用正则进行验证
//        ['DIRECT_LEADER','require','请输入直接领导'],//默认情况下用正则进行验证
//        ['DEPART_HEAD','require','请输入部门总监'],//默认情况下用正则进行验证
//        ['DOCKING_HR','require','请输入对接hr'],//默认情况下用正则进行验证
        ['ERP_ACT','require','请输入ERP账号'],//默认情况下用正则进行验证
        //['RANK','require','请输入职级'],//默认情况下用正则进行验证
        ['ERP_PWD','require','请输入ERP密码'],//默认情况下用正则进行验证     IS_FILED
        ['SC_EMAIL','require','请输入花名邮箱'],//默认情况下用正则进行验证
        ['STATUS','require','请输入状态'],//默认情况下用正则进行验证     IS_FILED
        //['IS_FILED','require','请选择档案是否归档']//默认情况下用正则进行验证
    ];

    /**
     *  Employee status
     *
     */
    public static function getStatusForEmployee($key=null){
        $items = D("ZZmscmncd")->getValueFromPrev('N00156');
        return DataMain::getItems($items, $key);
    }

    /**
     *  Format department by employee
     *    
     *  @param  
     *  @return 
     *
     */
    public function fmtDeptByEmpl($datainfo){
        if(empty($datainfo)){
            return $datainfo;
        }
        // format temp necessary data
        $datainfo['format_DEPT_NAME_id'] = null;
        $datainfo['format_DEPT_GROUP_id'] = null;
        // judge int or str
        $datainfo['DEPT_NAME']  = isset($datainfo['DEPT_NAME'])?$datainfo['DEPT_NAME']:'';
        $datainfo['DEPT_GROUP'] = isset($datainfo['DEPT_GROUP'])?$datainfo['DEPT_GROUP']:'';
        $old = $datainfo;
        $is_id_1 = is_numeric($datainfo['DEPT_NAME']);
        $is_id_2 = is_numeric($datainfo['DEPT_GROUP']);
        if($is_id_1 or $is_id_2){
            // check DEPT_GROUP
            if(!empty($datainfo['DEPT_GROUP'])){
                $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_GROUP']);
                // set back data
                $datainfo['DEPT_GROUP'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                $datainfo['format_DEPT_GROUP_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                $parent_id = isset($dept_info['PAR_DEPT_ID'])?$dept_info['PAR_DEPT_ID']:null;
                $parent_info = D('TbHrDept')->gainSimpleOneDept($parent_id);
                $parent_n = isset($parent_info['DEPT_NM'])?$parent_info['DEPT_NM']:null;
                $datainfo['DEPT_NAME'] = $parent_n?$parent_n:$datainfo['DEPT_NAME'];
                $datainfo['format_DEPT_NAME_id'] = isset($parent_info['ID'])?$parent_info['ID']:null;
            }
            // check DEPT_NAME
            if(!empty($datainfo['DEPT_NAME'])){
                if(!$datainfo['format_DEPT_NAME_id']){
                    $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_NAME']);
                    // set back data
                    $datainfo['DEPT_NAME'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                    $datainfo['format_DEPT_NAME_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                }
            }
        }else{
            // try to match name and parent name
            if($datainfo['DEPT_GROUP'] and $datainfo['DEPT_NAME']){
                $all_dept_info = D('TbHrDept')->gainDeptCheckCheifName($datainfo['DEPT_GROUP'],$datainfo['DEPT_NAME']);
                $datainfo['DEPT_GROUP'] = isset($all_dept_info['dept_group']['DEPT_NM'])?$all_dept_info['dept_group']['DEPT_NM']:'';
                $datainfo['DEPT_NAME'] = isset($all_dept_info['dept_name']['DEPT_NM'])?$all_dept_info['dept_name']['DEPT_NM']:'';
                $datainfo['format_DEPT_NAME_id'] = isset($all_dept_info['dept_name']['ID'])?$all_dept_info['dept_name']['ID']:'';
                $datainfo['format_DEPT_GROUP_id'] = isset($all_dept_info['dept_group']['ID'])?$all_dept_info['dept_group']['ID']:'';
            }
            // check DEPT_NAME
            if(!empty($datainfo['DEPT_NAME'])){
                if(!$datainfo['format_DEPT_NAME_id']){
                    $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_NAME']);
                    // set back data
                    $datainfo['DEPT_NAME'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                    $datainfo['format_DEPT_NAME_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                }
            }
            // not Upper and lower levels
            if(empty($datainfo['DEPT_NAME']) or empty($datainfo['DEPT_GROUP'])){
                $dept_info = D('TbHrDept')->gainDeptByMagicStr($old['DEPT_NAME']);
                $datainfo['DEPT_NAME'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                $datainfo['format_DEPT_NAME_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                $dept_info = D('TbHrDept')->gainDeptByMagicStr($old['DEPT_GROUP']);
                $datainfo['DEPT_GROUP'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                $datainfo['format_DEPT_GROUP_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
            }
        }

        return $datainfo;
    }

    /**
     *  Format department of employee
     *    
     *  @param  
     *  @return 
     *
     */
    public function fmtDeptOfEmpl($datainfo){
        if(empty($datainfo)){
            return $datainfo;
        }
        // format temp necessary data
        $datainfo['format_DEPT_NAME_id'] = null;
        $datainfo['format_DEPT_GROUP_id'] = null;
        // judge int or str
        $datainfo['DEPT_NAME']  = isset($datainfo['DEPT_NAME'])?$datainfo['DEPT_NAME']:'';
        $datainfo['DEPT_GROUP'] = isset($datainfo['DEPT_GROUP'])?$datainfo['DEPT_GROUP']:'';

        $is_id_1 = is_numeric($datainfo['DEPT_NAME']);
        $is_id_2 = is_numeric($datainfo['DEPT_GROUP']);
        if($is_id_1 or $is_id_2){
            // check DEPT_GROUP
            if(!empty($datainfo['DEPT_GROUP'])){
                //$dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_GROUP']);
                // set back data
                $datainfo['DEPT_GROUP'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                $datainfo['format_DEPT_GROUP_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                $parent_id = isset($dept_info['PAR_DEPT_ID'])?$dept_info['PAR_DEPT_ID']:null;
               // $parent_info = D('TbHrDept')->gainSimpleOneDept($parent_id);
                $parent_n = isset($parent_info['DEPT_NM'])?$parent_info['DEPT_NM']:null;
                $datainfo['DEPT_NAME'] = $parent_n?$parent_n:$datainfo['DEPT_NAME'];
                $datainfo['format_DEPT_NAME_id'] = isset($parent_info['ID'])?$parent_info['ID']:null;
            }
            // check DEPT_NAME
            if(!empty($datainfo['DEPT_NAME'])){
                if(!$datainfo['format_DEPT_NAME_id']){
                   // $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_NAME']);
                    // set back data
                    $datainfo['DEPT_NAME'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                    $datainfo['format_DEPT_NAME_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                }
            }
        }else{
            // try to match name and parent name
            if($datainfo['DEPT_GROUP'] and $datainfo['DEPT_NAME']){
                $all_dept_info = D('TbHrDept')->gainDeptByCheifName($datainfo['DEPT_GROUP'],$datainfo['DEPT_NAME']);
                //if(!$all_dept_info) $datainfo = false;   return $datainfo;
                //var_dump($all_dept_info);die;
                $datainfo['DEPT_GROUP'] = isset($all_dept_info['dept_group']['DEPT_NM'])?$all_dept_info['dept_group']['DEPT_NM']:'';
                $datainfo['DEPT_NAME'] = isset($all_dept_info['dept_name']['DEPT_NM'])?$all_dept_info['dept_name']['DEPT_NM']:'';
                $datainfo['format_DEPT_NAME_id'] = isset($all_dept_info['dept_name']['ID'])?$all_dept_info['dept_name']['ID']:'';
                $datainfo['format_DEPT_GROUP_id'] = isset($all_dept_info['dept_group']['ID'])?$all_dept_info['dept_group']['ID']:'';
            }
            // check DEPT_NAME
            if(!empty($datainfo['DEPT_NAME'])){
                if(!$datainfo['format_DEPT_NAME_id']){
                    $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_NAME']);
                    // set back data
                    $datainfo['DEPT_NAME'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                    $datainfo['format_DEPT_NAME_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                }
            }
            if(!empty($datainfo['DEPT_GROUP'])){
                if(!$datainfo['format_DEPT_GROUP_id']){
                    $dept_info = D('TbHrDept')->gainDeptByMagicStr($datainfo['DEPT_GROUP']);
                    // set back data
                    $datainfo['DEPT_GROUP'] = isset($dept_info['DEPT_NM'])?$dept_info['DEPT_NM']:null;
                    $datainfo['format_DEPT_GROUP_id'] = isset($dept_info['ID'])?$dept_info['ID']:null;
                }
            }
        }
        /*if ((!is_null($datainfo['format_DEPT_NAME_id']) and !is_null($datainfo['format_DEPT_GROUP_id'])) or (is_null($datainfo['format_DEPT_NAME_id']) and !is_null($datainfo['format_DEPT_GROUP_id']))  ) {
            $data = $this->getEmpl_deptData($datainfo['format_DEPT_GROUP_id']);
        }
        if (!is_null($datainfo['format_DEPT_NAME_id']) and is_null($datainfo['format_DEPT_GROUP_id'])) {
            $data = $this->getEmpl_deptData($datainfo['format_DEPT_NAME_id']);
        }*/
        return $datainfo;
    }


    /*按照工号排序(小的在前面)*/
    public function getMeetPersonData()
    {
        $MeetPersonData = $this
        ->field("EMP_SC_NM,WORK_NUM")
        ->order("WORK_NUM asc")->select();
        return $MeetPersonData;
    }
    /*靠前显示hr部门人员信息*/
    public function getWaitPersonData()
    {
        $waitData = $this
        ->join("tb_hr_empl_dept on tb_hr_empl.ID=tb_hr_empl_dept.ID2")
        ->join("tb_hr_dept on tb_hr_empl_dept.ID1=tb_hr_dept.ID")
        ->where("tb_hr_empl_dept.TYPE=1")
        ->field("tb_hr_empl.EMP_SC_NM")
        ->select();
        $waitPersonData = $waitData?$waitData:null;
        return $waitPersonData;
    }

    /**
     *  In all , check and make data
     *  @param  $data  array
     *  @param  $old_data  mix
     *  @return array
     *
     */
    public function formatFields($data, $old_data=array()){
            if ($data['picName']) {

                $datainfo['PIC'] = $data['picName'];         //入职照片

            }
            //var_dump($data);die;
            $datainfo['WORK_NUM'] = $data['workNum'];   //工号
            $datainfo['EMP_SC_NM'] = $data['EmpScNm'];  //花名
            $datainfo['PER_JOB_DATE']= cutting_time($data['perJobDate'])?cutting_time($data['perJobDate']):'';  //入职时间
            $datainfo['DEPT_NAME'] = $data['deptName']; //部门
            $datainfo['DEPT_GROUP'] =$data['deptGroup'];
            $datainfo['COMPANY_AGE'] = $data['companyAge'];  //司龄
            $datainfo['JOB_ID'] = $data['JOB_ID'];      //职位
            $datainfo['JOB_CD'] = $data['jobCd'];      //职位
            $datainfo['JOB_EN_CD'] = $data['JobEnCd'];    //英文职位
            $datainfo['WORK_PALCE'] = $data['workPlace'];   //工作地点
            $datainfo['DIRECT_LEADER'] = $data['directLeader'];  //直接领导
            $datainfo['DEPART_HEAD'] = $data['departHead'];  //部门总监
            $datainfo['DOCKING_HR'] = $data['dockingHr'];  //对接hr
            $datainfo['STATUS'] = $data['status'];   //状态
            $datainfo['EMP_NM'] = $data['empNm'];
            $datainfo['IS_FIRST_COM'] = $data['isFirstCom'];
            $datainfo['DEP_JOB_DATE'] =cutting_time($data['depJobDate'])?cutting_time($data['depJobDate']):'';  //离职时间
            $datainfo['DEP_JOB_NUM'] =$data['depJobNum'];
            $datainfo['ERP_ACT'] = $data['erpAct'];
            $datainfo['ERP_PWD'] = $data['erpPwd'];
            //md5+密钥加密
            if(!empty($data['erpPwd'])){
                $datainfo['ERP_PWD'] = md5($data['erpPwd']);    
            }else{
                 $datainfo['ERP_PWD'] = '';
            }
            
            

            $datainfo['PER_PHONE'] = $data['prePhone']; //手机号
            $datainfo['OFF_TEL'] = $data['offTel'];  //分机号
            $datainfo['JOB_TYPE_CD'] = $data['jobTypeCd'];  //职位类别
            $datainfo['PER_CART_ID'] = $data['perCartId'];   //身份证号

            //$datainfo['SEX'] = $data['sex']?$data['sex']:'';
            if ($data['sex']=='') {
                $datainfo['SEX'] = '2';
            }else{
                $datainfo['SEX'] = $data['sex'];
            }
           
            $datainfo['PER_IS_SMOKING'] =(isset($data['perIsSmoking']) and $data['perIsSmoking']!=='')?$data['perIsSmoking']:2; //是否吸烟
            $datainfo['PER_BIRTH_DATE'] =cutting_time($data['perBirthDate']); //出生日期
            $datainfo['AGE']=$data['age'];
            $datainfo['PER_ADDRESS'] = $data['perAddress'];  //籍贯
            $datainfo['PER_RESIDENT']=$data['perResident'];    //户籍
            $datainfo['PER_IS_MARRIED']=$data['perIsMarried'];  //婚姻状况
            $datainfo['CHILD_NUM'] =$data['childNum'];
            $datainfo['CHILD_BOY_NUM']=$data['childBoyNum']; //孩子数量(男)
            $datainfo['CHILD_GIRL_NUM']=$data['childGirlNum']; //孩子数量(女)
            $datainfo['PER_POLITICAL']=$data['perPolitical']; //政治面貌    perPolitical
            $datainfo['HOUSEHOLD']=(isset($data['hosehold']) and $data['hosehold']!=='')?$data['hosehold']:3; //户口性质
            $datainfo['PER_NATIONAL'] =$data['perNational'];
            $datainfo['FUND_ACCOUNT'] =$data['fundAccount']; //公积金账号
            $datainfo['SC_EMAIL'] =$data['scEmail']; //花名邮箱
            $datainfo['EMAIL'] = $data['email'];   //私人邮箱
            $datainfo['WE_CHAT'] = $data['weChat']; //微信
            $datainfo['QQ_ACCOUNT'] = $data['qqAccount'];//qq账号---------------------------
            $datainfo['HOU_ADDRESS'] = $data['houAdderss']; //户籍地址
            $datainfo['LIVING_ADDRESS'] = $data['livingAddress'];  //现居住地址
            $datainfo['FIRST_LAN'] = $data['firstLan']; //第一外语
            $datainfo['FIRST_LAN_LEVEL'] = (isset($data['firstLanLevel']) and $data['firstLanLevel']!=='')?$data['firstLanLevel']:3; //外语程度
            $datainfo['SECOND_LAN'] =$data['secondLan'];
            $datainfo['SECOND_LAN_LEVEL'] = (isset($data['secondLanLevel']) and $data['secondLanLevel']!=='')?$data['secondLanLevel']:3;
            $datainfo['HOBBY_SPA'] = $data['hobbySpa'];  //兴趣爱好--------------------------
            $datainfo['PER_CARD_PIC'] = $data['perCardPic']; //身份证正反面
            $datainfo['RESUME'] =$data['resume'];  //简历
            $datainfo['GRA_SCHOOL'] = $data['eduExp'][0]['schoolName'];  //毕业学校
            $datainfo['EDU_BACK'] = $data['eduExp'][0]['eduDegNat'];  //毕业学校---------------
            $datainfo['DEPT_GROUP'] =$data['deptGroup'];
            $datainfo['RANK'] = $data['rank'];
            $datainfo['MAJORS'] = $data['eduExp'][0]['eduMajors'];
            $datainfo['PROVINCE'] = $data['houAdderss']['proh'];
            $datainfo['CITY'] = $data['houAdderss']['cityH'];
            $datainfo['AREA'] = $data['houAdderss']['areaH'];
            $datainfo['DETAIL'] = $data['houAdderss']['detailH'];

            $datainfo['PROVINCE_LIVING'] = $data['livingAddress']['provL'];
            $datainfo['CITY_LIVING'] = $data['livingAddress']['cityL'];
            $datainfo['AREA_LIVING'] = $data['livingAddress']['areaL'];
            $datainfo['DETAIL_LIVING'] = $data['livingAddress']['detailL'];

            $datainfo['IS_FILED'] = $data['is_filed'];

            $edu = $data['eduExp'];
            $workExp = $data['workExp'];
            $home = $data['home'];
            $training = $data['training'];
            $certificate = $data['certificate'];
            $bankCard = $data['bankCard'];

            $datainfo['PER_CARD_PIC'] = $data['perCardPic']; //身份证正反面
            $datainfo['RESUME'] =$data['resume']; //简历

            $datainfo['GRA_CERT'] = $data['graCert'];
            $datainfo['DEG_CERT'] = $data['degCert'];
            $datainfo['LEARN_PROVE'] = $data['learnProve'];

            
            $datainfo['RANK'] = $data['rank'];
            //var_dump($datainfo);die;
            // check DEPT_GROUP
            /*if(!empty($datainfo['DEPT_GROUP'])){
                $deptData = D("TbHrDept")->where('ID='.$datainfo['DEPT_GROUP'])->find();
                $datainfo['DEPT_GROUP'] = $deptData['DEPT_NM'];
            }
            // check DEPT_NAME
            if(!empty($datainfo['DEPT_NAME'])){
                $deptData = D("TbHrDept")->where('ID='.$datainfo['DEPT_NAME'])->find();
                $datainfo['DEPT_NAME']= $deptData['DEPT_NM'];
            }
            $dataAll = $this->create($datainfo,1);
            }*/

            $dataAll = D('TbHrEmpl')->create($datainfo);


            //var_dump($dataAll);die;

            $cardData = D('TbHrCard')->create($datainfo);

            $dataAll['card'] = $cardData;
            $dataAll['empl_child'][] = array(
                'V_STR1'=>$data['concatName'],
                'V_STR2'=>$data['concatWay'],
                'V_STR3'=>$data['concatRel'],
                 'TYPE' => 0,
                );
   
            foreach ($edu as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_STR1'=>$value['schoolName'],
                    'V_DATE1'=>cutting_time($value['eduStartTime']),
                    'V_DATE2'=>cutting_time($value['eduEndTime']),
                    'V_INT1'=>$value['isDegree'],
                    'V_STR2'=>$value['eduMajors'],
                    'V_STR3'=>$value['certiNo'],
                    'V_STR4'=>$value['eduDegNat'],
                    'V_STR5'=>$data['graCert'],
                    'V_STR6'=>$data['degCert'],
                    'V_STR7'=>$data['learnProve'],
                    'V_STR8' => $value['validateRes'],
                    'TYPE' => 2,
                    );
            }

            foreach ($home as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_STR1'=>$value['homeRes'],
                    'V_STR2'=>$value['homeName'],
                    'V_STR3'=>$value['homeAge'],
                    'V_STR4'=>$value['occupa'],
                    'V_STR8'=>$value['workUnits'],
                    'TYPE' => 1,
                    );
            }

            foreach ($training as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_STR1'=>$value['trainingName'],
                    'V_DATE1'=>cutting_time($value['trainingStartTime']),
                    'V_DATE2'=>cutting_time($value['trainingEndTime']),
                    'V_STR2'=>$value['trainingIns'],
                    'V_STR9'=>$value['trainingDes'],
                    'TYPE' => 4,
                    );
            }

            foreach ($certificate as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_STR1'=>$value['certiName'],
                    'V_DATE1'=>cutting_time($value['certifiTime']),
                    'V_STR2'=>$value['certifiunit'],
                    'TYPE' => 5,
                    );
            }

           

            foreach ($workExp as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_DATE1'=>cutting_time($value['wordStartTime']),
                    'V_DATE2'=>cutting_time($value['wordEndTime']),
                    'V_STR1'=>$value['companyName'],
                    'V_STR2'=>$value['posi'],
                    'V_STR3'=>$value['depReason'],
                    'TYPE' => 11,
                    );
            }

             foreach ($bankCard as $key => $value) {
                $dataAll['empl_child'][] = array(
                    'V_STR1'=>$value['bankAct'],
                    'V_STR2'=>$value['bankName'],
                    'V_STR3'=>$value['swiftCood'],
                    'V_STR4'=>$value['bankDeposit'],
                    'V_STR5'=>$value['BankEndeposit'],
                    'TYPE' => 12,
                    );
            }

            // format temp necessary data
            $formats = array();
            $formats['format_DEPT_NAME_id'] = $datainfo['format_DEPT_NAME_id'];
            $formats['format_DEPT_GROUP_id'] = $datainfo['format_DEPT_GROUP_id'];


            //验证数据格式、必填
            $ret_data = array();
            $ret_data['dataAll']=$dataAll;
            $ret_data['formats']=$formats;
            $ret_data['is_error']=0;
            $ret_data['msg']='';
            $id = $data['emplid'];
            $pic = D('TbHrEmpl')->field('PIC')->where('ID='.$id)->find();
            if (empty($pic['PIC'])&&empty($data['picName'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='请上传头像';
            }elseif(empty($datainfo['EMP_NM'])){
                $ret_data['is_error']=1;
                $ret_data['msg']='真名不能为空';
            }/*elseif(empty($datainfo['ERP_PWD'])){
                $ret_data['is_error']=1;
                $ret_data['msg']='ERP密码不能为空';
            }*/elseif (empty($datainfo['PER_PHONE'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='联系方式不能为空';
            } elseif (empty($datainfo['PER_CART_ID'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='身份证号不能为空';
            }elseif (empty($datainfo['PER_BIRTH_DATE'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='出生日期不能为空';
            }elseif (empty($datainfo['AGE'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='年龄不能为空';
            }elseif (empty($datainfo['PER_ADDRESS'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='籍贯不能为空';
            }elseif (empty($datainfo['PER_RESIDENT'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='户籍不能为空';
            }elseif (empty($datainfo['PER_IS_MARRIED'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='婚姻状况不能为空';
            }elseif ($datainfo['CHILD_NUM']=='') {
                $ret_data['is_error']=1;
                $ret_data['msg']='子女数不能为空';
            }elseif($datainfo['CHILD_BOY_NUM']==''){
                $ret_data['is_error']=1;
                $ret_data['msg']='孩子性别(男)不能为空';
            }elseif ($datainfo['CHILD_GIRL_NUM']=='') {
                $ret_data['is_error']=1;
                $ret_data['msg']='孩子性别(女)不能为空';
            }elseif (empty($datainfo['PER_POLITICAL'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='政治面貌不能为空';
            }elseif (empty($datainfo['PER_NATIONAL'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='民族不能为空';
            }elseif (empty($datainfo['SC_EMAIL'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='花名邮箱不能为空';
            }elseif (empty($datainfo['EMAIL'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='私人邮箱不能为空';
            } elseif (empty($datainfo['QQ_ACCOUNT'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='qq账号不能为空';
            }elseif (empty($datainfo['WE_CHAT'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='微信号不能为空';
            }elseif (empty($datainfo['DETAIL'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='户籍地址不能为空';
            }elseif (empty($datainfo['DETAIL_LIVING'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='现居住地址不能为空';
            }elseif (empty($datainfo['PER_CARD_PIC'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='需要上传身份证正反面';
            }elseif (empty($datainfo['RESUME'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='需要上传简历附件';
            }elseif (empty($data['concatName'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='紧急联系人姓名不能为空';
            }elseif (empty($data['concatWay'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='紧急联系人联系方式不能为空';
            }elseif (empty($data['concatRel'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='紧急联系人关系不能为空';
            }elseif (empty($edu[0]['eduStartTime'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历开始时间不能为空';
            }elseif (empty($edu[0]['eduEndTime'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历结束时间不能为空';
            }elseif (empty($edu[0]['schoolName'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历学校名称不能为空';
            }elseif (empty($edu[0]['eduMajors'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历专业不能为空';
            }elseif (empty($edu[0]['eduDegNat'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历学历性质不能为空';
            }elseif (empty($edu[0]['validateRes'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历学历验证结果不能为空';
            }elseif (empty($edu[0]['certiNo'])) {
                $ret_data['is_error']=1;
                $ret_data['msg']='教育经历毕业证书编号不能为空';
            }
        
            return $ret_data;

    }

    //登录admin数据
    public function getAdminData($adminData)
    {
        //停用启用
        if ($adminData['STATUS']) {
            if ($adminData['STATUS']=='在职' or $adminData['STATUS']=='兼职') {
                $admin_data['IS_USE'] = 0;
            }else{
                $admin_data['IS_USE'] = 1;
            }
        }
        $admin_data = [
                'ROLE_ID'   => 15, //OA用户
                'M_ADDTIME' => time(),
            ];
            if ($adminData['ERP_ACT']) $admin_data['M_NAME'] = $adminData['ERP_ACT'];
            if ($adminData['EMP_SC_NM']) $admin_data['EMP_SC_NM'] = $adminData['EMP_SC_NM'];
            if ($adminData['SEX']) $admin_data['M_SEX'] = $adminData['SEX'];
            if ($adminData['PER_PHONE']) $admin_data['M_MOBILE'] = $adminData['PER_PHONE'];
            if ($adminData['SC_EMAIL']) $admin_data['M_EMAIL'] = $adminData['SC_EMAIL']; 

            //区分编辑与新建(密码)
            if(!empty($adminData['ERP_PWD'])){
                $admin_data['M_PASSWORD'] = $adminData['ERP_PWD'];
            }
            return $admin_data;
    }
    //随机生成密码
    public function getRandPwd()
    {
        $str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $rndstr;    //用来存放生成的随机字符串 
        for($i=0;$i<4;$i++) { 
            $rndcode=rand(0,50); 
            $rndstr.=$str[$rndcode]; 
        }
        $password =  $rndstr.mt_rand(0,1000);
        return $password;
    }
    //邮件内容
    public function getEmailContent($SCName,$password,$EmpScNm)
    {
          $nowData = D("TbHrEmpl")->where('ERP_ACT='."'".$_SESSION['m_loginname']."'")->find();  $_SESSION['m_loginname'];
          if($nowData){
            $name =  $nowData['EMP_SC_NM'];
          }else{
            $name = $_SESSION['m_loginname'];
          }
        $str = "<p style='font-size:17px;'>Dear {$SCName} :</p><p>你的ERP登录密码已经被重置,新密码为:<b>{$password}</b></p><p>登录网址&nbsp&nbsp&nbsp http://erp.gshopper.com</p><p>请悉知</p>"; 
        $time = date("Y-m-d H:i:s");
        $str = "<div  style='width: 500px;margin:0 auto;border: 1px solid #ebeced;font-size: 14px;text-indent: 28px;box-shadow: 2px 2px 3px #cfcfcf;'>
        <div style='width: 500px;height: 30px;background: #f8fafe;border: 1px solid #ebeced;line-height: 30px;'><b> Dear:{$SCName} </b> </div>
        <div style='width: 430px;height: 120px;border: 1px solid #ebeced;margin: 25px;  padding: 9px;margin-bottom: 22px;'>
                <p>你的ERP登录密码已经被重置,新密码为:<b>{$password}</b></p>
                <p>登录网址&nbsp&nbsp&nbsp http://erp.gshopper.com</p>
                <p>请知悉</p>
        </div>
        <div style='width: 500px;line-height: 30px;height: 30px;background: #fff0d5;'>{$time}, 由{$name}重置了你的密码</div>
        </div>";
        return $str;
    }

    public function getNewPwd($erpPwd,$erpAct,$erpid)
    {
        $accountSource = D("admin")->where('M_NAME='."'".$erpAct."'")->find();
        $isOA = $accountSource['oa_user_state'];
       // if($isOA==='1'){
            $tmp['ID'] = $erpid;
            $tmp['ERP_PWD'] =md5($erpPwd);
            $tmp['card']['ERP_PWD'] =md5($erpPwd);
           
            $adminData['M_PASSWORD'] = md5($erpPwd);
        /*}*//*else{
            $tmp['ID'] = $erpid;
            $tmp['ERP_PWD'] =md5($erpPwd.C("PASSKEY"));
            $tmp['card']['ERP_PWD'] = md5($erpPwd.C("PASSKEY"));
            $adminData['M_PASSWORD'] = md5($erpPwd.C("PASSKEY"));
        }*/

        $data['tmp'] = $tmp;
        $data['adminData'] = $adminData;
        return $data;
    }

//ger biness card
    public function getBusinessData($scName)
    {
        if (!empty($scName)) {
            $personData = $this
            ->field("ID,ERP_ACT,EMP_SC_NM,PIC,JOB_CD,DEPT_NAME")
            ->where('ERP_ACT='."'".$scName."'")
            ->find();
            
            /*$deptdata = M('hr_empl_dept','tb_')
            ->where('TYPE = 1 AND ID2='.$personData['ID'])
            ->find();*/
            //$dept = D("TbHrDept")->field("DEPT_NM")->where('ID='.$deptdata['ID1'])->find();
            if(!is_null($personData)){
                $personData = $personData;      
            }else{
                $personData = [];
            }
        }else{
            $personData = [];
        }
        return $personData;
    }
    

}

