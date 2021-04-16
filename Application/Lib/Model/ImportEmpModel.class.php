<?php

/**
 * 模型
 *
 */
class ImportEmpModel extends BaseImportExcelModel
{
    protected $trueTableName = 'tb_hr_empl';
    protected $_link = [
        'card' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbHrCard',
            'foreign_key' => 'EMPL_ID',
            'relation_foreign_key' => 'ID',
            'mapping_name' => 'child'
        ],
        'child' => [
            'mapping_type' => HAS_MANY,
            'class_name' => 'TbHrEmplChild',
            'foreign_key' => 'EMPL_ID',
            'relation_foreign_key' => 'ID',
            'mapping_name' => 'empl_child'
        ],
    ];

    public function fieldMapping()
    {
        return [
            'WORK_NUM' => ['field_name' => '工号', 'required' => true],
            'PER_JOB_DATE' => ['field_name' => '入职时间', 'required' => true],
            'COMPANY_AGE' => ['field_name' => '司龄', 'required' => false],
            'EMP_NM' => ['field_name' => '真名', 'required' => true],
            'EMP_SC_NM' => ['field_name' => '花名', 'required' => true],
            'JOB_CD' => ['field_name' => '中文职位', 'required' => true],
            'DEPT_NAME' => ['field_name' => '部门', 'required' => true],
            'DEPT_GROUP' => ['field_name' => '组别', 'required' => true],
            'WORK_PALCE' => ['field_name' => '工作地点', 'required' => true],
            'PER_CART_ID' => ['field_name' => '身份证号', 'required' => true],
            'PER_BIRTH_DATE' => ['field_name' => '出生日期', 'required' => false],

            'RANK' => ['field_name' => '职级', 'required' => false],   //职级
            'DIRECT_LEADER' => ['field_name' => '直接领导', 'required' => false], //直接领导
            'DEPART_HEAD' => ['field_name' => '部门总监', 'required' => false],   //部门总监
            'DOCKING_HR' => ['field_name' => '对接HR', 'required' => false],    //对接hr
            'JOB_TYPE_CD' => ['field_name' => '职位类别', 'required' => false], //职务类别


            //合同信息
            'REMARK' => ['field_name' => '合同公司', 'required' => false],  //合同公司
            'PER_NATIONAL' => ['field_name' => '用工性质', 'required' => false],  //用工性质
            'PER_P_DATE1' => ['field_name' => '试用期结束时间', 'required' => false],  //试用期结束时间
            'PER_P_DATE2' => ['field_name' => '合同开始时间', 'required' => false],  //合同开始时间
            'JOB_CON_END_TIME' => ['field_name' => '合同结束时间', 'required' => false],  //合同结束时间
            'PER_NAME' => ['field_name' => '合同状态', 'required' => false],  //合同状态

            //工作内奖惩
            'PER_SCHOOL' => ['field_name' => '奖惩名称', 'required' => false],  //奖惩名称
            'PER_ADDRESS1' => ['field_name' => '奖惩内容', 'required' => false],  //奖惩内容

            //晋升记录
            'PER_ADDRESS2' => ['field_name' => '晋升类型', 'required' => false],  //晋升类型
            'PER_REMARK' => ['field_name' => '晋升时间', 'required' => false],  //晋升时间
            'LIVING_ADDRESS' => ['field_name' => '晋升内容', 'required' => false],  //晋升内容

            //日报缺失
            'END_TIME' => ['field_name' => '日报缺失时间', 'required' => false],  //日报缺失时间
            'LEARN_PROVE' => ['field_name' => '日报缺失内容', 'required' => false],  //日报缺失内容


            'AGE' => ['field_name' => '年龄', 'required' => false],
            'SEX' => ['field_name' => '性别', 'required' => false],
            'PER_RESIDENT' => ['field_name' => '户籍', 'required' => true],
            'PER_IS_MARRIED' => ['field_name' => '婚姻状况', 'required' => true],
            'PER_PHONE' => ['field_name' => '手机号码', 'required' => true],
            'PER_IS_SMOKING' => ['field_name' => '抽烟', 'required' => true],
            'GRA_SCHOOL' => ['field_name' => '毕业学院', 'required' => true],
            'EDU_BACK' => ['field_name' => '学历', 'required' => true],
            'MAJORS' => ['field_name' => '专业', 'required' => true],
            'FUND_ACCOUNT' => ['field_name' => '公积金账号', 'required' => true],
            'JOB_EN_CD' => ['field_name' => '英文职位', 'required' => false],
            'PER_POLITICAL' => ['field_name' => '政治面貌', 'required' => true],
            'EMAIL' => ['field_name' => '个人邮箱', 'required' => true],
            'SC_EMAIL' => ['field_name' => '花名邮箱', 'required' => true],
            'DEP_JOB_NUM' => ['field_name' => '离职编号', 'required' => true],
            'DEP_JOB_DATE' => ['field_name' => '离职时间', 'required' => true],
            'STATUS' => ['field_name' => '状态', 'required' => true],
            'V_STR1' => ['field_name' => '关系', 'required' => true],
            'V_STR3' => ['field_name' => '联系方式', 'required' => true],

            'V_DATE2' => ['field_name' => '毕业时间', 'required' => true],
            'V_STR4' => ['field_name' => '毕业证书编号', 'required' => true],
            'V_STR2' => ['field_name' => '姓名', 'required' => true],
            'V_STR5' => ['field_name' => '学历', 'required' => true],
            'V_STR6' => ['field_name' => '专业', 'required' => true],
            'V_STR7' => ['field_name' => '毕业学院', 'required' => true],

            'V_STR8' => ['field_name' => '前一家公司', 'required' => true],
            'V_STR9' => ['field_name' => '再前一家公司', 'required' => true],
            'V_STR10' => ['field_name' => '银行', 'required' => true],
            'V_STR11' => ['field_name' => '卡号', 'required' => true],

            'DETAIL' => ['field_name' => '户籍地址', 'required' => true],
            'DETAIL_LIVING' => ['field_name' => '现住址', 'required' => true],
            'ERP_ACT' => ['field_name' => 'erp账号(花名拼音)', 'required' => true],
        ];
    }

    //验证
    public function validate($p)
    {
        $scName = $p['EMP_SC_NM'];
        if ($scName == '') {

            $data = [
                'code' => 500,
                'msg' => 'error',
                'data' => '花名不能为空',
            ];

            return $data;
            $this->rollback();
        }

        $erpAccount = $p['ERP_ACT'];
        if ($erpAccount == '') {
            $data = [
                'code' => 500,
                'msg' => 'error',
                'data' => 'Erp账号不能为空',
            ];

            return $data;
            $this->rollback();
        }
        $status = $p['STATUS'];
        if ($status == '') {
            $data = [
                'code' => 500,
                'msg' => 'error',
                'data' => '状态不能为空',
            ];
            $this->rollback();
            return $data;
        }
        $scmap['STATUS'] = array('neq', '离职');
        $scmap['EMP_SC_NM'] = array('eq', $scName);
        $resName = M('hr_card', 'tb_')->where($scmap)->limit(1)->select();

        if (!is_null($resName)) {
            $data = [
                'code' => 500,
                'msg' => 'error',
                'data' => '花名重复,' . $scName,
            ];

            return $data;
            $this->rollback();
        }

        $resAccount = M('hr_card', 'tb_')->where("STATUS!='离职' AND ERP_ACT='{$erpAccount}'")->limit(1)->select();
        if (!is_null($resAccount)) {
            $data = [
                'code' => 500,
                'msg' => 'error',
                'data' => 'erp账号重复,' . $erpAccount,
            ];

            return $data;
            $this->rollback();
        }
    }


    /**
     * 数据再组装
     *
     */
    public function packData()
    {
        if (empty($this->data)) {
            $data['data'] = '请填写数据再导入';
            $data['code'] = 400;
            return $data;
        }
        //header('Content-type: text/html; charset=UTF8');
        //echo "<pre>";
        //print_r($this->data);die;
        //校验数据
        $format_error = [];
        $duplicate_work_num = [];
        foreach ($this->data as $ek => $ev) {
            //导入数据中，工号或花名、erp账号、花名邮箱其中一项已存在系统则返回错误
            $all_work_num[] = trim($ev['A']['value']);
            $duplicate_work_num[trim($ev['A']['value'])][] = 'A' . $ek;
            $all_name[] = trim($ev['E']['value']);
            $all_erp_account[] = trim($ev['F']['value']);
            $all_jobs[] = trim($ev['G']['value']);
            $all_dept[] = trim($ev['I']['value']);
            $all_email[] = trim($ev['AP']['value']);
        }

        //相同工号验证
        foreach ($duplicate_work_num as $rk => $rv) {
            if (count($rv) > 1) {
                $format_error[implode(',', $rv)] = '填写了相同的工号：' . $rk;
            }
        }
        $employee_model = M('hr_card', 'tb_');
        $job_model = M('hr_jobs', ' tb_');
        $dept_model = M('hr_dept', ' tb_');
        //人员状态
        $work_status_cd = M('ms_cmn_cd', 'tb_')->field('CD_VAL')->where(['CD' => ['like', '%N00156%']])->select();
        $work_location_cd = M('ms_cmn_cd', 'tb_')->field('CD_VAL')->where(['CD' => ['like', '%N00155%']])->select();
        $work_status_cd = array_column($work_status_cd, 'CD_VAL');
        $work_location_cd = array_column($work_location_cd, 'CD_VAL');

        if (!empty($all_work_num)) {
            $find_work_num = $employee_model->field('WORK_NUM')->where(['WORK_NUM' => ['in', $all_work_num], 'STATUS' => '在职'])->select();
            $find_work_num = array_column($find_work_num, 'WORK_NUM');
        }

        if (!empty($all_name)) {
            $find_name = $employee_model->field('EMP_SC_NM')->where(['STATUS' => ['neq', '离职'], 'EMP_SC_NM' => ['in', $all_name]])->select();
            $find_name = array_column($find_name, 'EMP_SC_NM');
        }

        if (!empty($all_erp_account)) {
            $find_erp_account = $employee_model->field('ERP_ACT')->where(['STATUS' => ['neq', '离职'], 'ERP_ACT' => ['in', $all_erp_account]])->select();
            $find_erp_account = array_column($find_erp_account, 'ERP_ACT');
        }

        if (!empty($all_email)) {
            $find_sc_email = $employee_model->field('SC_EMAIL')->where(['SC_EMAIL' => ['in', $all_email]])->select();
            $find_sc_email = array_column($find_sc_email, 'SC_EMAIL');
        }

        if (!empty($all_jobs)) {
            $find_jobs = $job_model->field('CD_VAL')->where(['CD_VAL' => ['in', $all_jobs]])->select();
            $find_jobs = array_column($find_jobs, 'CD_VAL');
        }

        if (!empty($all_dept)) {
            $find_dept = $dept_model->field('DEPT_NM')->where(['DEPT_NM' => ['in', $all_dept]])->select();
            $find_dept = array_column($find_dept, 'DEPT_NM');
        }

        vendor("PHPExcel.PHPExcel"); //只需加载一次即可
        foreach ($this->data as $dk => $dv) {
            //工号、花名，erp账号，入职时间、工作地点、花名邮箱、中文职位、部门的数据格式正确与否校验
            if (trim($dv['A']['value']) === '') {
                $format_error['A' . $dk] = '工号不能为空';
            } else if (!preg_match('/^[0-9]*$/', trim($dv['A']['value']))) {
                $format_error['A' . $dk] = '工号列请输入数字';
            } else if (in_array(trim($dv['A']['value']), $find_work_num)) {
                $format_error['A' . $dk] = '工号已存在';
            }

            if (empty(trim($dv['B']['value']))) {
                $format_error['B' . $dk] = '入职时间不能为空';
            } else {
                if (is_numeric(trim($dv['B']['value'])) === false) {
                    $format_error['B' . $dk] = '入职时间格式不正确';
                } else {
                    $join_date = gmdate("Y-m-d H:i:s", PHPExcel_Shared_Date::ExcelToPHP(trim($dv['B']['value'])));
                    if (!preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s+(20|21|22|23|[0-1]\d):[0-5]\d:[0-5]\d$/', $join_date)) {
                        $format_error['B' . $dk] = '入职时间格式不正确';
                    }
                }

            }

            if (trim($dv['E']['value']) === '') {
                $format_error['E' . $dk] = '花名不能为空';
            } else if (!preg_match("/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\s]+$/u", trim($dv['E']['value']))) {
                $format_error['E' . $dk] = '花名列请不要输入特殊字符';
            } else if (in_array(trim($dv['E']['value']), $find_name)) {
                $format_error['E' . $dk] = '花名已存在';
            }

            if (trim($dv['F']['value']) === '') {
                $format_error['F' . $dk] = 'erp账号不能为空';
            } else if (in_array(trim($dv['F']['value']), $find_erp_account)) {
                $format_error['F' . $dk] = 'erp账号已存在';
            }

            if (trim($dv['K']['value']) === '') {
                $format_error['K' . $dk] = '工作地点不能为空';
            } else if (!in_array(trim($dv['K']['value']), $work_location_cd)) {
                $format_error['K' . $dk] = '工作地点输入不正确';
            }


            if (trim($dv['AP']['value']) === '') {
                $format_error['AP' . $dk] = '花名邮箱不能为空';
            } else if (!preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,6}$/', trim($dv['AP']['value']))) {
                $format_error['AP' . $dk] = '花名邮箱格式不正确';
            } else if (in_array(trim($dv['AP']['value']), $find_sc_email)) {
                $format_error['AP' . $dk] = '花名邮箱已存在';
            }

            if (trim($dv['AQ']['value']) === '') {
                $format_error['AQ' . $dk] = '状态不能为空';
            } else if (!in_array(trim($dv['AQ']['value']), $work_status_cd)) {
                $format_error['AQ' . $dk] = '状态输入不正确';
            }

            if (trim($dv['G']['value']) === '') {
                $format_error['G' . $dk] = '岗位名称不能为空';
            } else if (!in_array(trim($dv['G']['value']), $find_jobs)) {
                $format_error['G' . $dk] = '填写的岗位名称不存在';
            }

            if (trim($dv['I']['value']) === '') {
                $format_error['I' . $dk] = '部门不能为空';
            } else if (!in_array(trim($dv['I']['value']), $find_dept)) {
                $format_error['I' . $dk] = '填写的部门不存在';
            }
        }
        if (!empty($format_error)) {
            return ['code' => 300, 'data' => $format_error];
        } else {
            $data = [];
            //自动员信息数据计算
            foreach ($this->data as $index => $info) {
                $temp = [];
                foreach ($info as $key => $value) {
                    $temp [$value ['db_field']] = $value ['value'];
                }
                $autoData = $this->create();
                //if (!empty($temp['PER_JOB_DATE'])) {   //计算司龄(按月计算)
                //    $mon = substr($temp['PER_JOB_DATE'], 5, 2);
                //    $comAge = (12 - $mon) + (date("Y") - 1 - substr($temp['PER_JOB_DATE'], 0, 4)) * 12 + date('m');
                //    if ($comAge < 0) $comAge = 0;
                //} else {
                //    $temp['PER_JOB_DATE'] = null;
                //    $comAge = 0;
                //}
                $temp['COMPANY_AGE'] = 0;
                $cardID = $temp['PER_CART_ID'];
                $temp['PER_BIRTH_DATE'] = substr($cardID, 6, 4) . '-' . substr($cardID, 10, 2) . '-' . substr($cardID, 12, 2);
                if ($cardID) {
                    $temp['AGE'] = date("Y") - substr($cardID, 6, 4);
                }
                if (strtolower($temp['PER_IS_SMOKING']) == 'yes') {
                    $temp['PER_IS_SMOKING'] = 1;
                }

                if (!empty(substr($cardID, 16, 1))) {
                    if (substr($cardID, 16, 1) % 2 != 0) {
                        $temp['SEX'] = 0;
                    } else {
                        $temp['SEX'] = 1;
                    }
                } else {
                    $temp['SEX'] = 2;
                }

                $empl = M('ms_cmn_cd', 'tb_')->where('CD_VAL=' . "'" . $temp['JOB_CD'] . "'")->find();
                $temp['JOB_EN_CD'] = $empl['ETC'];
                $data [] = $temp;
            }

            //header('Content-type: text/html; charset=UTF8');
            //echo "<pre>";
            //print_r($data);die;
            //循环导入
            $this->startTrans();
            foreach ($data as $key => $value) {
                $tmpData = D('TbHrEmpl')->fmtDeptOfEmpl($value);
                //if($tmpData===false)   $data = ['code' => 500,'msg' => 'error','data' => '部门组别不匹配']; return $data;
                $tmp_dept_id = null;
                $tmp_dept_id = $tmp_dept_id ? $tmp_dept_id : (empty($tmpData['format_DEPT_GROUP_id']) ? null : $tmpData['format_DEPT_GROUP_id']);
                $tmp_dept_id = $tmp_dept_id ? $tmp_dept_id : (empty($tmpData['format_DEPT_NAME_id']) ? null : $tmpData['format_DEPT_NAME_id']);
                $tmp_dept_id = $tmp_dept_id ? $tmp_dept_id : $tmpData['format_DEPT_GROUP_id'];

                $value['DEPT_GROUP'] = empty($tmpData['DEPT_GROUP']) ? '' : $tmpData['DEPT_GROUP'];
                $value['DEPT_NAME'] = empty($tmpData['DEPT_NAME']) ? '' : $tmpData['DEPT_NAME'];
                $data[$key] = $value;
                // check dept end
                $p = $c = null;
                $p = $this->create($data[$key], 1);

                if (!is_null($p['PER_JOB_DATE'])) $p['PER_JOB_DATE'] = excelTime($p['PER_JOB_DATE']);
                if (!strtotime($p['PER_JOB_DATE'])) {
                    $p['PER_JOB_DATE'] = null;
                    $p['COMPANY_AGE'] = 0;
                }
                if ($data[$key]['PER_P_DATE1'] != '') $data[$key]['PER_P_DATE1'] = excelTime($data[$key]['PER_P_DATE1']);
                //if ($data[$key]['PER_P_DATE1']!='') $data[$key]['PER_P_DATE1'] = excelTime($data[$key]['PER_P_DATE1']);
                if ($data[$key]['PER_P_DATE2'] != '') $data[$key]['PER_P_DATE2'] = excelTime($data[$key]['PER_P_DATE2']);
                if ($data[$key]['JOB_CON_END_TIME'] != '') $data[$key]['JOB_CON_END_TIME'] = excelTime($data[$key]['JOB_CON_END_TIME']);
                if ($data[$key]['PER_REMARK'] != '') $data[$key]['PER_REMARK'] = excelTime($data[$key]['PER_REMARK']);
                if ($data[$key]['END_TIME'] != '') $data[$key]['END_TIME'] = excelTime($data[$key]['END_TIME']);
                $child = D('TbHrEmplChild')->create($data[$key], 1);
                //组装跟进数据
                //合同
                $child['conCompany'] = $data[$key]['REMARK'];
                $child['natEmploy'] = $data[$key]['PER_NATIONAL'];
                $child['trialEndTime'] = $data[$key]['PER_P_DATE1'];
                $child['conStartTime'] = $data[$key]['PER_P_DATE2'];
                $child['conEndTime'] = $data[$key]['JOB_CON_END_TIME'];
                $child['constatus'] = $data[$key]['PER_NAME'];
                //奖惩
                $child['rewardName'] = $data[$key]['PER_SCHOOL'];
                $child['rewardContent'] = $data[$key]['PER_ADDRESS1'];
                //升职
                $child['promoType'] = $data[$key]['PER_ADDRESS2'];
                $child['promoTime'] = $data[$key]['PER_REMARK'];
                $child['promoContent'] = $data[$key]['LIVING_ADDRESS'];
                //日报
                $child['paperMissTime'] = $data[$key]['END_TIME'];
                $child['paperMissCon'] = $data[$key]['LEARN_PROVE'];

                $worknum = $p['WORK_NUM'];
                $resworknum = M('hr_empl', 'tb_')->where("STATUS!='离职' AND WORK_NUM='{$worknum}'")->find();
                if ($resworknum) {   //如工号重复则覆盖
                    foreach ($p as $k => $v) {
                        if (($v == '' or $v == '--' or $v == 2) && $v !== 0) unset($p[$k]);
                    }
                    $tmp = $p;
                    $tmp['ID'] = $resworknum['ID'];
                    $tmp['card'] = D('TbHrCard')->create($p, 1);
                    $tmp['card']['GRA_SCHOOL'] = $value['V_STR7'];
                    $tmp['card']['EDU_BACK'] = $value['V_STR5'];
                    foreach ($tmp['card'] as $k => $v) {
                        if (($v != '' && $v != '--') or $v === 0) {
                            $tmp['card'][$k] = $v;
                        } else {
                            unset($tmp['card'][$k]);
                        }
                    }

                    $id = $resworknum['ID'];
                    $old_data = M('hr_empl_child', 'tb_')->where('TYPE  in (0,1,2,4,5,11,12,3,7,8,10) AND EMPL_ID = ' . $id)->select();
                    $old_ids = array_column($old_data, 'ID');
                    $old_max = max($old_ids);
                    if ($old_ids) {
                        $resDel = M('hr_empl_child', 'tb_')->where('EMPL_ID=' . $id . ' AND ID<=' . $old_max)->delete();
                    }
                    foreach ($child as $k => $v) {
                        if ($v == '') {
                            unset($child[$k]);
                        }
                    }
                    $count = 0;
                    $cc1 = $cc = $cc3 = $cc4 = 1;
                    foreach ($old_data as $k => $v) {
                        if ($v['TYPE'] == '0') {
                            if ($cc == 1) {
                                $tmp['empl_child'][$count]['V_STR1'] = $child['V_STR2'] ? $child['V_STR2'] : $v['V_STR1'];
                                $tmp['empl_child'][$count]['V_STR2'] = $child['V_STR3'] ? $child['V_STR3'] : $v['V_STR2'];
                                $tmp['empl_child'][$count]['V_STR3'] = $child['V_STR1'] ? $child['V_STR1'] : $v['V_STR3'];
                                $tmp['empl_child'][$count]['TYPE'] = '0';
                            }
                            $cc++;
                            $count++;
                        }
                        if ($v['TYPE'] == '2') {
                            if ($cc1 == 1) {
                                $tmp['empl_child'][$count]['V_STR1'] = $child['V_STR7'] ? $child['V_STR7'] : $v['V_STR1'];
                                $tmp['empl_child'][$count]['V_STR2'] = $child['V_STR6'] ? $child['V_STR6'] : $v['V_STR2'];
                                $tmp['empl_child'][$count]['V_STR3'] = $child['V_STR4'] ? $child['V_STR4'] : $v['V_STR3'];
                                $tmp['empl_child'][$count]['V_STR4'] = $child['V_STR5'] ? $child['V_STR5'] : $v['V_STR4'];
                                $tmp['empl_child'][$count]['V_STR8'] = $v['V_STR8'];
                                $tmp['empl_child'][$count]['V_INT1'] = $v['V_INT1'];

                                $tmp['empl_child'][$count]['V_DATE1'] = $v['V_DATE1'];
                                $tmp['empl_child'][$count]['V_DATE2'] = $v['V_DATE2'];
                                $tmp['empl_child'][$count]['TYPE'] = '2';
                            }
                            $count++;
                            $cc1++;
                        }
                        //合同
                        if ($v['TYPE'] == '3') {
                            $tmp['empl_child'][$count]['V_STR1'] = $child['conCompany'] ? $child['conCompany'] : $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_STR2'] = $child['natEmploy'] ? $child['natEmploy'] : $v['V_STR2'];

                            $tmp['empl_child'][$count]['V_DATE1'] = $child['trialEndTime'] ? $child['V_DATE1'] : $v['V_DATE1'];
                            $tmp['empl_child'][$count]['V_DATE2'] = $child['conStartTime'] ? $child['conStartTime'] : $v['V_DATE2'];
                            $tmp['empl_child'][$count]['V_DATE3'] = $child['conEndTime'] ? $child['conEndTime'] : $v['V_DATE3'];

                            $tmp['empl_child'][$count]['V_STR3'] = $child['constatus'] ? $child['constatus'] : $v['V_STR3'];
                            $tmp['empl_child'][$count]['TYPE'] = '3';
                            $count++;
                        }
                        if ($v['TYPE'] == '1') {
                            //$count=0;
                            $tmp['empl_child'][$count]['V_STR1'] = $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_STR2'] = $v['V_STR2'];
                            $tmp['empl_child'][$count]['V_STR3'] = $v['V_STR3'];
                            $tmp['empl_child'][$count]['V_STR4'] = $v['V_STR4'];
                            $tmp['empl_child'][$count]['V_STR8'] = $v['V_STR8'];

                            $tmp['empl_child'][$count]['TYPE'] = '1';
                            $count++;
                        }
                        if ($v['TYPE'] == '4') {
                            //$count=0;
                            $tmp['empl_child'][$count]['V_STR1'] = $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_STR2'] = $v['V_STR2'];
                            $tmp['empl_child'][$count]['V_STR9'] = $v['V_STR9'];
                            $tmp['empl_child'][$count]['V_DATE1'] = $v['V_DATE1'];
                            $tmp['empl_child'][$count]['V_DATE2'] = $v['V_DATE2'];

                            $tmp['empl_child'][$count]['TYPE'] = '4';
                            $count++;
                        }
                        if ($v['TYPE'] == '5') {
                            //$count=0;
                            $tmp['empl_child'][$count]['V_STR1'] = $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_STR2'] = $v['V_STR2'];
                            $tmp['empl_child'][$count]['V_DATE1'] = $v['V_DATE1'];

                            $tmp['empl_child'][$count]['TYPE'] = '5';
                            $count++;
                        }

                        if ($v['TYPE'] == '7') {
                            //$count=0;
                            $tmp['empl_child'][$count]['V_STR1'] = $child['rewardName'] ? $child['rewardName'] : $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_STR2'] = $child['rewardContent'] ? $child['rewardContent'] : $v['V_STR10'];
                            $tmp['empl_child'][$count]['TYPE'] = '7';
                            $count++;
                        }

                        if ($v['TYPE'] == '8') {
                            //$count=0;
                            $tmp['empl_child'][$count]['V_STR1'] = $child['promoType'] ? $child['promoType'] : $v['V_STR1'];
                            $tmp['empl_child'][$count]['V_DATE1'] = $child['promoTime'] ? $child['promoTime'] : $v['V_DATE1'];
                            $tmp['empl_child'][$count]['V_STR10'] = $child['promoContent'] ? $child['promoContent'] : $v['V_STR10'];
                            $tmp['empl_child'][$count]['TYPE'] = '8';
                            $count++;
                        }

                        if ($v['TYPE'] == '10') {
                            $tmp['empl_child'][$count]['V_DATE1'] = $child['paperMissTime'] ? $child['paperMissTime'] : $v['V_DATE1'];
                            $tmp['empl_child'][$count]['V_STR10'] = $child['paperMissCon'] ? $child['paperMissCon'] : $v['V_STR10'];
                            $tmp['empl_child'][$count]['TYPE'] = '10';
                            $count++;
                        }

                        if ($v['TYPE'] == '11') {
                            if ($cc3 == 1) {
                                $tmp['empl_child'][$count]['V_STR1'] = $child['V_STR8'] ? $child['V_STR8'] : $v['V_STR1'];
                            } else if ($cc3 == 2) {
                                $tmp['empl_child'][$count]['V_STR1'] = $child['V_STR9'] ? $child['V_STR9'] : $v['V_STR1'];
                            }

                            $tmp['empl_child'][$count]['V_STR2'] = $v['V_STR2'];
                            $tmp['empl_child'][$count]['V_STR3'] = $v['V_STR3'];
                            $tmp['empl_child'][$count]['V_DATE1'] = $v['V_DATE1'];
                            $tmp['empl_child'][$count]['V_DATE2'] = $v['V_DATE2'];

                            $tmp['empl_child'][$count]['TYPE'] = '11';
                            $cc3++;
                            $count++;
                        }
                        if ($v['TYPE'] == '12') {
                            if ($cc4 == 1) {
                                $tmp['empl_child'][$count]['V_STR1'] = $child['V_STR11'] ? $child['V_STR11'] : $v['V_STR1'];
                                $tmp['empl_child'][$count]['V_STR2'] = $v['V_STR2'];
                                $tmp['empl_child'][$count]['V_STR3'] = $v['V_STR3'];
                                $tmp['empl_child'][$count]['V_STR4'] = $child['V_STR10'] ? $child['V_STR10'] : $v['V_STR4'];
                                $tmp['empl_child'][$count]['V_STR5'] = $v['V_STR5'];

                                $tmp['empl_child'][$count]['TYPE'] = '12';
                            }
                            $cc4++;
                            $count++;
                        }
                    }
                    $editAdminData = D("TbHrEmpl")->getAdminData($tmp);
                    $status = D('TbHrDept')->matchEmployeeToDepartment($resworknum['ID'], $tmp_dept_id);
                    //覆盖admin账户信息
                    unset($editAdminData['ROLE_ID']);
                    unset($editAdminData['M_ADDTIME']);
                    $editAdminData['M_UPDATED'] = time();
                    $adminRes = D("admin")->where('empl_id=' . $tmp['ID'])->save($editAdminData);
                    $res = D('TbHrEmpl')->relation(true)->where('ID=' . $id)->save($tmp);

                } else {
                    //if(!is_null($this->validate($p))){
                    //    return $this->validate($p);
                    //}
                    $p['GRA_SCHOOL'] = $value['V_STR7'];
                    $p['EDU_BACK'] = $value['V_STR5'];
                    if (!$p = $this->create($p, 1)) {
                        $data = [
                            'code' => 500,
                            'msg' => 'error',
                            'data' => $this->getError(),
                        ];
                        return $data;
                    }

                    $p['child'] = D("TbHrCard")->create($p, 1);
                    $p['child']['ERP_PWD'] = md5("gshopper@123");
                    $p['ERP_PWD'] = md5("gshopper@123");

                    if (!$ret = $this->relation(true)->add($p)) {

                        $data = [
                            'code' => 500,
                            'msg' => 'error',
                            'data' => 'add error',
                        ];
                        return $data;
                    }

                    $adminData = D("TbHREmpl")->getAdminData($p);
                    $adminData['empl_id'] = $ret;

                    //$adminData['M_PASSWORD'] = md5("izene@123".C("PASSKEY"));
                    if (!$res = D("admin")->add($adminData)) {

                        $data = [
                            'code' => 500,
                            'msg' => 'error',
                            'data' => "accout add error",
                        ];
                        return $data;
                    }

                    if (!D('AdminRole')->add(['M_ID' => $res, 'ROLE_ID' => 15])) {
                        $data = [
                            'code' => 500,
                            'msg' => 'error',
                            'data' => "accout and permissions association failed",
                        ];
                        return $data;
                    }

                    $parens['EMPL_ID'] = $ret;
                    $child['EMPL_ID'] = $ret;
                    // relate employee and department
                    $status = D('TbHrDept')->matchEmployeeToDepartment($parens['EMPL_ID'], $tmp_dept_id);
                    $v1 = array();
                    $v1['V_STR1'] = $child['V_STR2'];
                    $v1['V_STR2'] = $child['V_STR3'];
                    $v1['V_STR3'] = $child['V_STR1'];
                    $v1['EMPL_ID'] = $child['EMPL_ID'];
                    $v1['TYPE'] = 0;
                    $dataAll[] = $v1;
                    D("TbHrEmplChild")->add($v1);
                    $v2 = array();
                    $v2['V_STR3'] = $child['V_STR4'];
                    $v2['V_DATE2'] = $child['V_DATE2'];
                    $v2['V_STR1'] = $child['V_STR7'];
                    $v2['V_STR2'] = $child['V_STR6'];
                    $v2['V_STR4'] = $child['V_STR5'];
                    $v2['EMPL_ID'] = $child['EMPL_ID'];
                    $v2['TYPE'] = 2;
                    $dataAll[] = $v2;
                    D("TbHrEmplChild")->add($v2);
                    $v3 = array();
                    $v3['V_STR1'] = $child['V_STR8'];
                    $v3['EMPL_ID'] = $child['EMPL_ID'];
                    $v3['TYPE'] = 11;
                    $dataAll[] = $v3;
                    D("TbHrEmplChild")->add($v3);
                    $v4 = array();
                    $v4['V_STR1'] = $child['V_STR9'];
                    $v4['EMPL_ID'] = $child['EMPL_ID'];
                    $v4['TYPE'] = 11;
                    $dataAll[] = $v4;
                    D("TbHrEmplChild")->add($v4);
                    $v5 = array();
                    $v5['V_STR4'] = $child['V_STR10'];
                    $v5['V_STR1'] = $child['V_STR11'];
                    $v5['EMPL_ID'] = $child['EMPL_ID'];
                    $v5['TYPE'] = 12;
                    $dataAll[] = $v5;
                    D("TbHrEmplChild")->add($v5);

                    $v6 = [];
                    $v6['V_STR1'] = $child['conCompany'];
                    $v6['V_STR2'] = $child['natEmploy'];
                    $v6['V_DATE1'] = $child['trialEndTime'];
                    $v6['V_DATE2'] = $child['conStartTime'];
                    $v6['V_DATE3'] = $child['conEndTime'];
                    $v6['V_STR3'] = $child['constatus']; //状态
                    $v6['EMPL_ID'] = $child['EMPL_ID'];
                    $v6['TYPE'] = 3;
                    D("TbHrEmplChild")->add($v6);

                    $v7 = [];
                    $v7['V_STR1'] = $child['rewardName'];
                    $v7['V_STR10'] = $child['rewardContent'];
                    $v7['EMPL_ID'] = $child['EMPL_ID'];
                    $v7['TYPE'] = 7;
                    D("TbHrEmplChild")->add($v7);

                    $v8 = [];
                    $v8['V_STR1'] = $child['promoType'];
                    $v8['V_DATE1'] = $child['promoTime'];
                    $v8['V_STR10'] = $child['promoContent'];
                    $v8['EMPL_ID'] = $child['EMPL_ID'];
                    $v8['TYPE'] = 8;
                    D("TbHrEmplChild")->add($v8);

                    $v9 = [];
                    $v9['V_DATE1'] = $child['paperMissTime'];
                    $v9['V_STR10'] = $child['paperMissCon'];
                    $v9['EMPL_ID'] = $child['EMPL_ID'];
                    $v9['TYPE'] = 10;
                    D("TbHrEmplChild")->add($v9);

                    //补写事务
                }


            }
            return $res;
        }
    }


    public function import()
    {
        parent::import();
        $res = $this->packData();
        if (in_array($res['code'], [300,400,500])) {
            $data = [
                'code' => $res['code'],
                'msg' => 'error',
                'data' => $res['data'],
            ];
            return $data;
        } else {
            $this->commit();
            $data = [
                'code' => 200,
                'msg' => 'success',
                'data' => '导入成功',
            ];
            return $data;
        }
    }
}