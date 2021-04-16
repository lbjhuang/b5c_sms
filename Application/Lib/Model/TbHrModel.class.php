<?php 
/**
* Hr人员管理模型
*/
class TbHrModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_card';
    private $returndata = [
        'empNm' => '',
        'info' => null,
        'status' => null,
    ];

    

    /**
     * 人员管理人员列表数据
     * @param $Keyword 人员搜索关键字条件
     * @return 
     */
    public function showPerson($Keyword){  
       
        $m= M('hr_card','tb_');
        $order_str = $this->getOrder($Keyword);
        $where = $this->getwhere($Keyword);
        $Keyword =json_decode($Keyword['params'],true);
        if ($Keyword['seMonk']) {
            $seMonkdata = M('hr_empl_child','tb_')->field('EMPL_ID')->where('TYPE=7 AND V_STR1='."'".$Keyword['seMonk']."'")->select();
            $emplid = array_column($seMonkdata, 'EMPL_ID');
            $where['EMPL_ID'] = array('in',$emplid);
        }
        $Person = $m
            ->field('tb_hr_card.EMPL_ID,tb_hr_card.ERP_ACT,tb_hr_card.WORK_NUM,tb_hr_card.PER_JOB_DATE,tb_hr_card.COMPANY_AGE,
                tb_hr_card.EMP_SC_NM,tb_hr_card.JOB_CD,tb_hr_card.DEPT_NAME,tb_hr_card.WORK_PALCE,tb_hr_card.PER_CART_ID,
                tb_hr_card.PER_BIRTH_DATE,tb_hr_card.SEX,tb_hr_card.PER_RESIDENT,tb_hr_card.PER_PHONE,tb_hr_card.GRA_SCHOOL,
                tb_hr_card.EDU_BACK,tb_hr_card.DEPT_GROUP,tb_hr_card.DEP_JOB_DATE,tb_hr_card.COMPANY_AGE,tb_hr_card.JOB_EN_CD,tb_hr_card.STATUS')
            ->join('tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_id')
            ->join('tb_hr_dept b on b.ID=a.ID1')
            ->order($order_str)
            ->where($where)
            ->group('EMPL_ID')
            ->select();

        foreach ($Person as $key => $v) {
            if ($Person[$key]['PER_BIRTH_DATE'] == '0000-00-00 00:00:00') {
                $Person[$key]['PER_BIRTH_DATE'] = '';
            }
            if ($Person[$key]['PER_JOB_DATE'] == '0000-00-00 00:00:00') {
                $Person[$key]['PER_JOB_DATE'] = '';
            }
            if ($Person[$key]['DEP_JOB_DATE'] == '0000-00-00 00:00:00') {
                $Person[$key]['DEP_JOB_DATE'] = '';
            }
            $Person[$key]['PER_JOB_DATE'] = $Person[$key]['PER_JOB_DATE']?substr($Person[$key]['PER_JOB_DATE'], 0,10):'';
            $Person[$key]['PER_BIRTH_DATE'] = $Person[$key]['PER_BIRTH_DATE']?substr($Person[$key]['PER_BIRTH_DATE'], 0,10):'';
           // echo date('Y',strtotime($v['PER_JOB_DATE']));die;
            if ($v['PER_JOB_DATE']!='') {
               $year = (date('Y') - date('Y',strtotime($v['PER_JOB_DATE'])))?(date('Y') - date('Y',strtotime($v['PER_JOB_DATE']))):0;
                $month = (date('m')-date('m',strtotime($v['PER_JOB_DATE'])))?(date('m')-date('m',strtotime($v['PER_JOB_DATE']))):0;
                $Person[$key]['COMPANY_AGE'] = $year*12+$month;
            }
            
            $LEADERID = $v['LEADER_MNG_ID'];
            $LEADER = D('TbHrCard')->findOneByEmplId($LEADERID);
            $Person[$key]['LEADER_MNG_PR'] = $LEADER['EMP_SC_NM'];
            if (is_null($Person[$key]['LEADER_MNG_PR'])) {
                $Person[$key]['LEADER_MNG_PR'] = '空';
            }
            if ($Person[$key]['SEX']=='0')
                $Person[$key]['SEX'] = '男';
            if ($Person[$key]['SEX']=='1')
                $Person[$key]['SEX'] = '女';
            
            
        }

        return $Person;
    }
    /**
     *   choose sort type
     *
     */
    public function getOrder($Keyword){
        $Keyword = @json_decode($Keyword['params'],true);
        $ret = 'EMPL_ID desc';
        $orderArr=array();
        $orderArr['orderinfo']  =(isset($Keyword['orderinfo']) and strtolower($Keyword['orderinfo'])=='asc')?'asc':'desc';
        $orderArr['ordernext']   =($orderArr['orderinfo']=='asc')?'desc':'asc';    //??
        $orderArr['ordertype']   =isset($Keyword['ordertype'])?$Keyword['ordertype']:'';
        switch($orderArr['ordertype']){
            case 1:
                $ret = 'WORK_NUM+0 '.$orderArr['orderinfo'];
                break;
            case 2:
                $ret = 'PER_JOB_DATE '.$orderArr['orderinfo'];
                break;
            case 3:
                $ret = 'COMPANY_AGE '.$orderArr['orderinfo'];
                break;
            case 4:
                $ret = 'CONVERT(EMP_SC_NM USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 5:
                $ret = 'CONVERT(JOB_CD USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 6:
                $ret = 'CONVERT(DEPT_NAME USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 7:
                $ret = 'CONVERT(DEPT_GROUP USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 8:
                $ret = 'CONVERT(WORK_PALCE  USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 9:
                $ret = 'PER_CART_ID '.$orderArr['orderinfo'];
                break;
            case 10:
                $ret = 'PER_BIRTH_DATE '.$orderArr['orderinfo'];
                break;
            case 11:
                $ret = 'SEX '.$orderArr['orderinfo'];
                break;
            case 12:
                $ret = 'CONVERT(PER_RESIDENT  USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 13:
                $ret = 'PER_PHONE '.$orderArr['orderinfo'];
                break;
            case 14:
                $ret = 'CONVERT(GRA_SCHOOL USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 15:
                $ret = 'CONVERT(EDU_BACK  USING gbk) COLLATE gbk_chinese_ci '.$orderArr['orderinfo'];
                break;
            case 16:
                break;
            case 17:
                break;
            case 18:
                break;
            default:
                $ret = 'WORK_NUM+0 asc';
        }
        return $ret;
    }
    /**
     *人员筛选条件
     */
    public function getwhere($Keyword){
        
        $key = '';
        $where = array();
        //$where['STATUS'] = '在职';
        $Keyword =json_decode($Keyword['params'],true);
        $key = trim($Keyword['seKey']);  //关键字查询
        if ($key) {
            $where['EMP_SC_NM'] = array("like","%{$key}%");
            $where['DIVISON_NAME'] = array("like","%{$key}%");
            $where['b.DEPT_NM'] = array("like","%{$key}%");
            $where['PHONE_NUM'] = array("like","%{$key}%");
            $where['OFF_TEL'] = array("like","%{$key}%");
            $where['LEADER_MNG_ID'] = array("like","%{$key}%");
            $where['COMPANY_AGE'] = array("like","%{$key}%");
            $where['JOB_CD'] = array("like","%{$key}%");
            $where['DEPT_GROUP'] = array("like","%{$key}%");
            $where['WORK_PALCE'] = array("like","%{$key}%");
            $where['PER_CART_ID'] = array("like","%{$key}%");
            $where['SEX'] = array("like","%{$key}%");
            $where['PER_RESIDENT'] = array("like","%{$key}%");
            $where['GRA_SCHOOL'] = array("like","%{$key}%");
            $where['EDU_BACK'] = array("like","%{$key}%");


            $where['_logic'] = 'or';
        }
        if ($Keyword['seWorkNum']) {
            $seWorkNum = trim($Keyword['seWorkNum']);
            $where['WORK_NUM'] = array('like',"%{$seWorkNum}%");
        }
        if ($Keyword['seDept']) {
            $seDept = trim($Keyword['seDept']);
            $where['b.DEPT_NM'] = array('like',"%{$seDept}%");
        }
        if ($Keyword['seWorkplace']) {
            $where['WORK_PALCE'] = $Keyword['seWorkplace'];
        }
        if ($Keyword['seWorkaddress']) {
            $seWorkaddress = trim($Keyword['seWorkaddress']);
            $where['WORK_PALCE'] = array('like',"%{$seWorkaddress}%");
        }
        if ($Keyword['seStatus']) {
            $where['tb_hr_card.STATUS'] = $Keyword['seStatus'];
        }
        if ($Keyword['seScNm']) {
            $seScNm = trim($Keyword['seScNm']);
            $where['EMP_SC_NM'] = array('like',"%{$seScNm}%");
        }
        if ($Keyword['seLeader']) {
            $where['DIRECT_LEADER'] = $Keyword['seLeader'];
        }
        if ($Keyword['seComPh']) {
            $seComPh = trim($Keyword['seComPh']);
            $where['OFF_TEL'] = array('like',"%{$seComPh}%");
        }
        if ($Keyword['seJobCd']) {
            $where['JOB_CD'] = $Keyword['seJobCd'];
        }
        if ($Keyword['seTrName']) {
            $seTrName = trim($Keyword['seTrName']);
            $where['EMP_NM'] = array('like',"%{$seTrName}%");
        }
        if ($Keyword['seEmail']) {
            $seEmail = trim($Keyword['seEmail']);
            $where['EMAIL'] = array('like',"%{$seEmail}%");
        }
        if ($Keyword['seCellPh']) {
            $seCellPh = trim($Keyword['seCellPh']);
            $where['PER_PHONE'] = array('like',"%{$seCellPh}%");
        }
        if ($Keyword['seJobType']) {
            $where['JOB_TYPE_CD'] = $Keyword['seJobType'];
        }
        if ($Keyword['seName']===0||$Keyword['seName']) {
            $where['SEX'] = $Keyword['seName'];

        }
        return $where;

    }
        

        
    
    /**
     *名片
     */
    public function cardData($data){
        $emplId = $data['emplID'];
        if ($emplId) {
            $cardInfo1 = M('hr_card','tb_')->where('tb_hr_card.EMPL_ID='."'".$emplId."'")->find();
        }else{
            $cardInfo1 = M('hr_card','tb_')->where('tb_hr_card.ERP_ACT='."'".$_SESSION['m_loginname']."'")->find();
        }
       

        foreach ($cardInfo1 as $key => $value) {
            if ($cardInfo1[$key]=='0000-00-00 00:00:00') {
                $cardInfo1[$key] = '';
            }
        }
        
        $cardInfo['Pic'] = 'index.php?m=Api&a=show&filename='.$cardInfo1['PIC'];
        $cardInfo['workNum'] = $cardInfo1['WORK_NUM'];
        $cardInfo['EmpScNm'] = $cardInfo1['EMP_SC_NM'];
        $cardInfo['perJobDate'] =$cardInfo1['PER_JOB_DATE']?substr($cardInfo1['PER_JOB_DATE'],0,10):'';
        $cardInfo['deptName'] = $cardInfo1['DEPT_NAME'];
        $cardInfo['deptGroup'] = $cardInfo1['DEPT_GROUP'];
        if ($cardInfo1['PER_JOB_DATE']!='') {
            $year = (date('Y') - date('Y',strtotime($cardInfo1['PER_JOB_DATE'])))?(date('Y') - date('Y',strtotime($cardInfo1['PER_JOB_DATE']))):0;
            $month = (date('m')-date('m',strtotime($cardInfo1['PER_JOB_DATE'])))?(date('m')-date('m',strtotime($cardInfo1['PER_JOB_DATE']))):0;
            $cardInfo['companyAge'] = $year*12+$month;
        }
        
        //$cardInfo['companyAge'] = $cardInfo1['COMPANY_AGE'];
        #cardInfo1 这里面表字段中根本没有 job_id  所以一直都是null
        // $cardInfo['JOB_ID'] = $cardInfo1['JOB_ID'];
        $cardInfo['JOB_ID'] = M('hr_empl','tb_')->where(['ID'=> $emplId])->getField('JOB_ID');
        

        $cardInfo['jobCd'] = $cardInfo1['JOB_CD'];

        $cardInfo['JobEnCd'] = $cardInfo1['JOB_EN_CD'];
        $cardInfo['workPlace'] = $cardInfo1['WORK_PALCE'];
        $cardInfo['directLeader'] = $cardInfo1['DIRECT_LEADER'];
        $cardInfo['departHead'] = $cardInfo1['DEPART_HEAD'];
        $cardInfo['dockingHr'] = $cardInfo1['DOCKING_HR'];
        $cardInfo['rank'] = $cardInfo1['RANK'];
        $cardInfo['depJobDate'] = $cardInfo1['DEP_JOB_DATE']?substr($cardInfo1['DEP_JOB_DATE'],0,10):'';
        $cardInfo['depJobNum'] = $cardInfo1['DEP_JOB_NUM'];
        $cardInfo['erpAct'] = $cardInfo1['ERP_ACT'];
        $cardInfo['erpPwd'] = $cardInfo1['ERP_PWD'];
        $cardInfo['status'] = $cardInfo1['STATUS'];
        $cardInfo['empNm'] = $cardInfo1['EMP_NM'];
        $cardInfo['prePhone'] = $cardInfo1['PER_PHONE'];
        $cardInfo['offTel'] = $cardInfo1['OFF_TEL'];
        $cardInfo['jobTypeCd'] = $cardInfo1['JOB_TYPE_CD'];
        $cardInfo['perCartId'] = $cardInfo1['PER_CART_ID'];
        $cardInfo['sex'] = $cardInfo1['SEX'];
        $cardInfo['perIsSmoking'] = $cardInfo1['PER_IS_SMOKING'];
        $cardInfo['perBirthDate'] = $cardInfo1['PER_BIRTH_DATE']?substr($cardInfo1['PER_BIRTH_DATE'],0,10):'';
        $cardInfo['age'] = $cardInfo1['AGE'];
        $cardInfo['perAddress'] = $cardInfo1['PER_ADDRESS'];
        $cardInfo['perResident'] = $cardInfo1['PER_RESIDENT'];
        $cardInfo['perIsMarried'] = $cardInfo1['PER_IS_MARRIED'];
        $cardInfo['childNum'] = $cardInfo1['CHILD_NUM'];

        $cardInfo['childBoyNum'] = $cardInfo1['CHILD_BOY_NUM'];
        $cardInfo['childGirlNum'] = $cardInfo1['CHILD_GIRL_NUM'];
        $cardInfo['perPolitical'] = $cardInfo1['PER_POLITICAL'];
        $cardInfo['hosehold'] = $cardInfo1['HOUSEHOLD'];
        $cardInfo['fundAccount'] = $cardInfo1['FUND_ACCOUNT'];
        $cardInfo['scEmail'] = $cardInfo1['SC_EMAIL'];
        $cardInfo['email'] = $cardInfo1['EMAIL'];
        $cardInfo['weChat'] = $cardInfo1['WE_CHAT'];
        $cardInfo['qqAccount'] = $cardInfo1['QQ_ACCOUNT'];
        $cardInfo['livingAddress'] = $cardInfo1['LIVING_ADDRESS'];
        $cardInfo['firstLan'] = $cardInfo1['FIRST_LAN'];
        $cardInfo['firstLanLevel'] = $cardInfo1['FIRST_LAN_LEVEL'];
        $cardInfo['secondLan'] = $cardInfo1['SECOND_LAN'];
        $cardInfo['secondLanLevel'] = $cardInfo1['SECOND_LAN_LEVEL'];
        $cardInfo['hobbySpa'] = $cardInfo1['HOBBY_SPA'];
        $cardInfo['perCardPic'] = $cardInfo1['PER_CARD_PIC'];
        $cardInfo['resume'] = $cardInfo1['RESUME'];
        $cardInfo['perNational'] = $cardInfo1['PER_NATIONAL'];
        $cardInfo['emplid'] = $cardInfo1['EMPL_ID'];

        $cardInfo['graCert'] = $cardInfo1['GRA_CERT'];
        $cardInfo['degCert'] = $cardInfo1['DEG_CERT'];
        $cardInfo['learnProve'] = $cardInfo1['LEARN_PROVE'];

        $cardInfo['is_filed'] = $cardInfo1['IS_FILED'];
        $cardInfo['isFirstCom'] = $cardInfo1['IS_FIRST_COM'];

        $provH = $cardInfo1['PROVINCE'];
        $cityH = $cardInfo1['CITY'];
        $areaH = $cardInfo1['AREA'];
        $detailH = $cardInfo1['DETAIL'];

        $provL = $cardInfo1['PROVINCE_LIVING'];
        $cityL = $cardInfo1['CITY_LIVING'];
        $areaL = $cardInfo1['AREA_LIVING'];
        $detailL = $cardInfo1['DETAIL_LIVING'];
        $cardInfo['houAdderss'] = array(
            'proh' =>$provH,
            'cityH' =>$cityH,
            'areaH' =>$areaH,
            'detailH' =>$detailH
            );
        $cardInfo['livingAddress'] = array(
            'provL' =>$provL,
            'cityL' =>$cityL,
            'areaL' =>$areaL,
            'detailL' =>$detailL
            );
//        var_dump($cardInfo);die;
        $emplid = $cardInfo1['EMPL_ID'];
        //department
        $department = D('Hr/HrEmplDept')->field('ID1,PERCENT')->where(['ID2'=>$emplid])->select();
        $department_list = D('TbHrDept')->dept_list();
        foreach ($department as $k => $v) {
            $department[$k] = array_merge($v,$department_list[$v['ID1']]);
        }

        $dataTest = D('TbHrEmplChild')->where('EMPL_ID='.$emplid)->select();

        foreach ($dataTest as $k => $v) {
            $type = $v['TYPE'];
            switch ($type) {
                case 0:
                    $friInfo['concatName'] = $dataTest[$k]['V_STR1'];
                    $friInfo['concatWay'] = $dataTest[$k]['V_STR2'];
                    $friInfo['concatRel'] = $dataTest[$k]['V_STR3'];
                    break;
                case 2:
                    $tmpedu['eduStartTime'] = $dataTest[$k]['V_DATE1']?substr($dataTest[$k]['V_DATE1'],0,10):'';
                    $tmpedu['eduEndTime'] = $dataTest[$k]['V_DATE2']?substr($dataTest[$k]['V_DATE2'],0,10):'';
                    $tmpedu['schoolName'] = $dataTest[$k]['V_STR1'];
                    $tmpedu['eduMajors'] = $dataTest[$k]['V_STR2'];
                    $tmpedu['certiNo'] = $dataTest[$k]['V_STR3'];
                    $tmpedu['isDegree'] = $dataTest[$k]['V_INT1'];
                    $tmpedu['eduDegNat'] = $dataTest[$k]['V_STR4'];
                    $tmpedu['graCert'] = $dataTest[$k]['V_STR5'];
                    $tmpedu['degCert'] = $dataTest[$k]['V_STR6'];
                    $tmpedu['learnProve'] = $dataTest[$k]['V_STR7'];
                    $tmpedu['validateRes'] = $dataTest[$k]['V_STR8'];
                    $eduInfo[] = $tmpedu;
                        break;
                default:
                    # code...
                    break;
            }

        }

          $home2 = M('hr_empl_child','tb_')->field('*')->where('TYPE = 1 AND EMPL_ID='.$emplid)->select();
        //var_dump($conInfo2);die;
        $home =array();
        foreach ($home2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

            $value3['homeRes'] = $value['V_STR1'];
            $value3['homeName'] = $value['V_STR2'];
            $value3['homeAge'] = $value['V_STR3'];
            $value3['occupa'] = $value['V_STR4'];
            $value3['workUnits'] = $value['V_STR8'];
            //if ($value2['conCompany']) {
                $home[] = $value3;
            //}
        }


        $conInfo2 = M('hr_empl_child','tb_')->field('V_STR1,V_STR2,V_DATE1,V_DATE2,V_DATE3,V_STR3')->where('TYPE = 3 AND EMPL_ID='.$emplid)->select();
        //var_dump($conInfo2);die;
        $conInfo =array();
        foreach ($conInfo2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

            $value3['conCompany'] = $value['V_STR1'];
            $value3['natEmploy'] = $value['V_STR2'];
            $value3['trialEndTime'] = $value['V_DATE1']?substr($value['V_DATE1'],0,10):'';
            $value3['conStartTime'] = $value['V_DATE2']?substr($value['V_DATE2'],0,10):'';
            $value3['conEndTime'] = $value['V_DATE3']?substr($value['V_DATE3'],0,10):'';
            $value3['conStatus'] = $value['V_STR3'];
            //if ($value2['conCompany']) {
                $conInfo[] = $value3;
            //}
        }


        $training2 = M('hr_empl_child','tb_')->field('V_STR1,V_STR2,V_DATE1,V_DATE2,V_STR9')->where('TYPE = 4 AND EMPL_ID='.$emplid)->select();
        $training =array();
        foreach ($training2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }
            $value4['trainingName'] = $value['V_STR1'];
            $value4['trainingStartTime'] = $value['V_DATE1']?substr($value['V_DATE1'],0,10):'';
            $value4['trainingEndTime'] = $value['V_DATE2']?substr($value['V_DATE2'],0,10):'';
            $value4['trainingIns'] = $value['V_STR2'];
            $value4['trainingDes'] = $value['V_STR9'];
            //if ($value2['conCompany']) {
                $training[] = $value4;
            //}
        }


        $certificate2 = M('hr_empl_child','tb_')->field('V_STR1,V_DATE1,V_STR2')->where('TYPE = 5 AND EMPL_ID='.$emplid)->select();
        $certificate =array();
        foreach ($certificate2 as $key => $value) {
            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }
            $value5['certiName'] = $value['V_STR1'];
            $value5['certifiTime'] = $value['V_DATE1']?substr($value['V_DATE1'],0,10):'';
            $value5['certifiunit'] = $value['V_STR2'];
                $certificate[] = $value5;
        }

        $reward2 = M('hr_empl_child','tb_')->field('V_STR1,V_STR10')->where('TYPE = 7 AND EMPL_ID='.$emplid)->select();
        $reward =array();
        foreach ($reward2 as $key => $value) {
            $value7['rewardName'] = $value['V_STR1'];
            $value7['rewardContent'] = $value['V_STR10'];
            //if ($value3['rewardName']) {
                $reward[] = $value7;
            //}
        }
        $promo2 = M('hr_empl_child','tb_')->field('V_STR1,V_DATE1,V_STR10')->where('TYPE = 8 AND EMPL_ID='.$emplid)->select();
        $promo =array();
        foreach ($promo2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

            $value8['promoType'] = $value['V_STR1'];
            $value8['promoTime'] = $value['V_DATE1']?substr($value['V_DATE1'], 0,10):'';
            $value8['promoContent'] = $value['V_STR10'];
            //if ($value4['promoType']) {
                $promo[] = $value8;
            //}
        }


        $inter2 = M('hr_empl_child','tb_')->field('V_STR1,V_DATE1,V_STR2,V_STR3,V_STR4,V_STR5')->where('TYPE = 9 AND EMPL_ID='.$emplid)->select();
        $inter =array();
        foreach ($inter2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

            $value9['interType'] = $value['V_STR1'];
            $value9['interTime'] = $value['V_DATE1']?cutting_times($value['V_DATE1']):'';
            $value9['interObj'] = $value['V_STR2'];
            $value9['interPerson'] = $value['V_STR3'];
            $value9['interContent'] = $value['V_STR4'];
            $value9['afterCase'] = $value['V_STR5'];
            //var_dump($value5);die;
            //if ($value5['interType']) {
                $inter[] = $value9;
            //}
        }



        $paper2 = M('hr_empl_child','tb_')->field('V_DATE1,V_STR10')->where('TYPE = 10 AND EMPL_ID='.$emplid)->select();
        $paper =array();
        foreach ($paper2 as $key => $value) {

            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

            $value10['paperMissTime'] = $value['V_DATE1']?substr($value['V_DATE1'], 0,10):'';
            $value10['paperMissCon'] = $value['V_STR10'];
            //if ($value1['paperMissTime']) {
                $paper[] = $value10;
            //}
        }


        $workExp2 = M('hr_empl_child','tb_')->field('V_DATE1,V_STR1,V_STR2,V_STR3,V_DATE2')->where('TYPE = 11 AND EMPL_ID='.$emplid)->select();
        $workExp =array();
        foreach ($workExp2 as $key => $value) {
            //var_dump($value);
            foreach ($value as $k => $v) {
                if ($v=='0000-00-00 00:00:00') {
                    $value[$k] = '';
                }
            }

//var_dump($value);die;
            $value11['wordStartTime'] = $value['V_DATE1']?substr($value['V_DATE1'], 0,10):'';
            $value11['wordEndTime'] = $value['V_DATE2']?substr($value['V_DATE2'], 0,10):'';
            $value11['companyName'] = $value['V_STR1'];
            $value11['posi'] = $value['V_STR2'];
            $value11['depReason'] = $value['V_STR3'];
            //if ($value1['paperMissTime']) {
                $workExp[] = $value11;
            //}
        }

        $bankCard2 = M('hr_empl_child','tb_')->field('V_STR1,V_STR2,V_STR3,V_STR4,V_STR5')->where('TYPE = 12 AND EMPL_ID='.$emplid)->select();
        $bankCard =array();
        foreach ($bankCard2 as $key => $value) {
            $value12['bankAct'] = $value['V_STR1'];
            $value12['bankName'] = $value['V_STR2'];
            $value12['swiftCood'] = $value['V_STR3'];
            $value12['bankDeposit'] = $value['V_STR4'];
            $value12['BankEndeposit'] = $value['V_STR5'];
                $bankCard[] = $value12;
        }

        $hrRecord2 = M('hr_empl_child','tb_')->field('V_STR1,V_DATE1')->where('TYPE = 13 AND EMPL_ID='.$emplid)->select();
        $hrRecord =array();
        foreach ($hrRecord2 as $key => $value) {
            $value13['reContent'] = $value['V_STR1'];
            $value13['reTime'] = cutting_time($value['V_DATE1']);
                $hrRecord[] = $value13;
        }

        $Mycard = array(
            'cardInfo' =>$cardInfo,
            'department' =>(array)$department,
            'workExp' =>$workExp,
            'home' =>$home,
            'friInfo' =>$friInfo,
            'eduInfo' =>$eduInfo,
            'conInfo' => $conInfo,
            'reward' =>$reward,
            'training' =>$training,
            'promo' =>$promo,
            'inter' =>$inter,
            'workExp' =>$workExp,
            'certificate' =>$certificate,
            'bankCard' =>$bankCard,
            'paper' => $paper,
            'hrRecord'=>$hrRecord,
            );
        //var_dump($Mycard);die;
        return $Mycard;
    }



    /**
     *个人信息编辑
     *@param $personalData  要修改的个人信息数据
     */
    public function editPersonal($personalData)
    {
        $m = D('hr_empl','tb_');

    }
    /**
     * 根据EMPL_id 获取该用户所有部门
     * 
     */
    public function getDeptByUser($id)
    {
        return M('hr_empl_dept', 'tb_')
            ->join('left join  tb_hr_dept on tb_hr_dept.ID = tb_hr_empl_dept.ID1')
            ->field('tb_hr_dept.ID,tb_hr_dept.DEPT_NM,tb_hr_dept.TYPE')
            ->where(['tb_hr_empl_dept.ID2' => $id])
            ->select();
    }
    /**
     * 获取部门
     * 
     */
    public function getLevelDept()
    {
        return M('hr_dept', 'tb_')
            ->field('ID,DEPT_NM,PAR_DEPT_ID,TYPE')
            ->where('DELETED_BY is null')
            ->select();
    }
   

    /**
     * 根据部门获取负责人 
    * 
     */
    public function summaryPeopleInDept($dept_id)
    {
        $list = M('hr_empl_dept', 'tb_')
        ->field('tb_hr_empl_dept.*,tb_hr_card.EMP_NM,tb_hr_card.EMP_SC_NM,tb_hr_card.EMPL_ID,tb_hr_card.ERP_ACT')
        ->join('tb_hr_empl on tb_hr_empl.ID = tb_hr_empl_dept.ID2')
        ->join('tb_hr_card on tb_hr_empl_dept.ID2 = tb_hr_card.EMPL_ID')
        ->where(['tb_hr_empl_dept.TYPE' => 0, 'tb_hr_empl_dept.ID1' => $dept_id, 'tb_hr_card.STATUS'=>'在职'])->order('tb_hr_empl_dept.SORT')->select();
        return $list;
    }
    #根部部门Id获取部门
    public function getDeptById($id){
        $dept = M('hr_dept', 'tb_')
            ->where(['ID' => $id])->find();
        return $dept;
    }
    #根据用户id获取职业
    public function getJobById($id)
    {
        $job = M('hr_empl', 'tb_')
            ->where(['ID' => $id])
            ->field('ID,JOB_ID,JOB_CD,EMP_NM,EMP_SC_NM')
            ->find();
        return $job;
    }

    #获取部门中类型为非支持部门的
    public function getBusinessDept()
    {
        $dept = M('hr_dept', 'tb_')
            ->where("DELETED_BY is null and  TYPE !='N002510200'" )->select();
        return $dept;
    }
    #获取所有的部门总监 在组织架构里是领导级别 & 对应的部门包含【非支持部门】 & 职级≤15
    public function getAllBusinessDirector($dept)
    {
        $data = M('hr_empl_dept', 'tb_')
            ->join('tb_hr_empl on tb_hr_empl_dept.ID2 = tb_hr_empl.ID')
            ->join('tb_hr_jobs on tb_hr_jobs.ID = tb_hr_empl.JOB_ID')
            ->join('tb_hr_card on tb_hr_empl_dept.ID2 = tb_hr_card.EMPL_ID')
            ->field('tb_hr_empl.ERP_ACT,tb_hr_empl.ID,tb_hr_jobs.RANK,tb_hr_empl.ID as EMPL_ID')
            ->where(['tb_hr_empl_dept.ID1'=>['IN',$dept], 'TYPE'=>0, 'tb_hr_jobs.RANK'=>['ELT',15], 'tb_hr_card.STATUS' => '在职'])
            ->group('tb_hr_empl_dept.ID2')
            ->select();
        return $data;
    }

    public function getCeo(){
        $data = M('hr_empl_dept', 'tb_')
            ->join('tb_hr_empl on tb_hr_empl_dept.ID2 = tb_hr_empl.ID')
            ->join('tb_hr_dept on tb_hr_dept.ID = tb_hr_empl_dept.ID1')
            ->field('tb_hr_empl.ERP_ACT,tb_hr_empl.ID,tb_hr_empl.ID as EMPL_ID')
            ->where(['tb_hr_empl_dept.TYPE' => 0, 'tb_hr_dept.PAR_DEPT_ID' => 0])
            ->find();
        return $data;
    }

    public function insertPromotion($data,$model){
        return $model->table('tb_hr_promotion')->add($data);
    }
    public function insertPromotionDetail($data, $model)
    {
        return $model->table('tb_hr_promotion_detail')->add($data);
    }
    public function insertApproverAll($data, $model)
    {
        return $model->table('tb_hr_promotion_approver')->addAll($data);
    }

    public function getPromotionList($where, $page)
    {


        $offset = ($page['this_page'] - 1) * $page['page_size'];
       
        $limit = $page['page_size'];
        $query = M('hr_promotion', 'tb_')->where($where);
        $queryList = clone $query;
        $count = $query->count();
        $pageCount = ceil($count / $limit);
        $list = $queryList
        ->field('tb_hr_promotion.*,if(tb_hr_promotion.status="N003630006","",tb_hr_promotion.current_approver) as current_approver,date_format(tb_hr_promotion.promotion_time, "%Y-%m") as promotion_time,bbm_admin.M_NAME,tb_hr_dept.DEPT_NM,jobs1.CD_VAL as current_job_name,jobs2.CD_VAL as promotion_job_name,tb_ms_cmn_cd.CD_VAL as status_name,cd2.CD_VAL as promotion_raise_type_cd_val')
        ->join('bbm_admin on bbm_admin.empl_id = tb_hr_promotion.empl_id')
        ->join('tb_hr_dept on tb_hr_dept.ID = tb_hr_promotion.dept_id')
        ->join('tb_hr_jobs as jobs1 on jobs1.ID = tb_hr_promotion.current_job_id')
        ->join('tb_hr_jobs as jobs2 on jobs2.ID = tb_hr_promotion.promotion_job_id')
        ->join('tb_ms_cmn_cd  on tb_ms_cmn_cd.CD = tb_hr_promotion.status')
        ->join('tb_ms_cmn_cd cd2  on cd2.CD = tb_hr_promotion.promotion_raise_type_cd')
        ->limit($offset, $limit)
        ->order('tb_hr_promotion.created_at desc')
        ->select();
       
        $re = [
                'data' => $list,
                'page' => [
                    'page_count' => $pageCount,
                    'this_page' => $page['this_page'],
                    'count' => $count
                ]
            ];
        
        return $re;
    }
    public function getPromotionCardDetail($id){
        return M('hr_card', 'tb_')->field('tb_hr_card.EMPL_ID,tb_hr_card.ERP_ACT,tb_hr_card.WORK_NUM,tb_hr_card.PER_JOB_DATE,
                tb_hr_card.EMP_SC_NM,tb_hr_card.JOB_CD,tb_hr_card.WORK_PALCE,tb_hr_card.PIC')
            ->where(['tb_hr_card.EMPL_ID'=>$id])
            ->find();
    }
    public function getPromotionDetail($id)
    {
        return M('hr_promotion', 'tb_')
            ->field('tb_hr_promotion.promotion_time,tb_hr_promotion.status,tb_hr_promotion.type,tb_hr_promotion.is_first_promote,tb_hr_promotion.last_promotion_time,tb_hr_promotion.current_approver,tb_hr_promotion.rating,
            jobs1.CD_VAL as current_job_name,
            jobs2.CD_VAL as promotion_job_name,
            jobs3.CD_VAL as last_promotion_before_job_name,
            tb_hr_promotion.dept_id,
            tb_hr_promotion.empl_id,
            tb_hr_promotion_detail.work_content,tb_hr_promotion_detail.reward_punishment_record,tb_hr_promotion_detail.direct_leadership_opinion,
            tb_hr_promotion_detail.gp_earning1,
            tb_hr_promotion_detail.gp_earning2,
            tb_hr_promotion_detail.odm_earning1,
            tb_hr_promotion_detail.odm_earning2,
            tb_hr_promotion.promotion_raise_type_cd, 
            tb_hr_promotion.last_salary_amount,
            tb_hr_promotion.now_salary_amount,
            tb_hr_promotion.raise_salary_amount,
            tb_hr_promotion.currency_cd,
            tb_hr_promotion_detail.personal_last_month_sale_amount,
            tb_hr_promotion_detail.personal_last_month_sale_earning,
            tb_hr_promotion_detail.gp_earning1_amount,
            tb_hr_promotion_detail.gp_earning2_amount,
            tb_hr_promotion_detail.odm_earning1_amount,
            tb_hr_promotion_detail.odm_earning2_amount,
            cd.CD_VAL as currency_cd_val
            ')
            ->join('tb_hr_promotion_detail on tb_hr_promotion.id = tb_hr_promotion_detail.promotion_id')
            ->join('tb_hr_jobs as jobs1 on jobs1.ID = tb_hr_promotion.current_job_id')
            ->join('tb_hr_jobs as jobs2 on jobs2.ID = tb_hr_promotion.promotion_job_id')
            ->join('tb_hr_jobs as jobs3 on jobs3.ID = tb_hr_promotion.last_promotion_before_job_id')
            ->join('tb_ms_cmn_cd as cd on cd.CD = tb_hr_promotion.currency_cd')
            ->where(['tb_hr_promotion.id' => $id])
            ->find();
    }
    #获取hr部门下的人员id
    public function getHr(){
        return M('hr_empl_dept', 'tb_')
            ->field('bbm_admin.M_ID,bbm_admin.M_NAME')
            ->join('tb_hr_dept on tb_hr_dept.ID = tb_hr_empl_dept.ID1')
            ->join('bbm_admin on bbm_admin.empl_id = tb_hr_empl_dept.ID2')
            ->where(['tb_hr_dept.DEPT_NM' => 'HR'])
            ->select();
    }

    public function updateApprover($model,$id,$data){
       return $model->table('tb_hr_promotion_approver')->where(['id'=>$id])->save($data);
    }
    public function getUserByEmplId($emplId){
        return M('admin', 'bbm_')->where(['empl_id' => $emplId])->getField('M_NAME');
    }
    #候选人列表搜索
    public function getUser($name){
       return M('hr_card', 'tb_')
           ->field('erp.M_ID id,erp.M_NAME name,erp.empl_id')
           ->join('bbm_admin as erp on erp.empl_id = tb_hr_card.EMPL_ID')
           ->join('tb_hr_empl as empl on empl.ID = tb_hr_card.EMPL_ID')
           ->where([
                    'erp.M_NAME'=>['like',"%$name%"],
                    'empl.ID'=>['GT',0],
                    'tb_hr_card.STATUS'=>['NEQ','离职']
               ])
           ->select();
    }
    public function updateTbCardByEmplId($model,$data){
        return $model->table('tb_hr_card')->where(['EMPL_ID' => $data['empl_id']])->save(['JOB_CD' => $data['job_name'], 'JOB_EN_CD' => $data['job_name_en'],'UPDATE_TIME'=>date('Y-m-d H:i:s')]);
    }
    public function updateTbEmplByEmplId($model,$data){
        return $model->table('tb_hr_empl')->where(['ID' => $data['empl_id']])->save(['JOB_CD' => $data['job_name'], 'JOB_ID' => $data['job_id'],'UPDATE_TIME'=>date('Y-m-d H:i:s')]);
    }

}
