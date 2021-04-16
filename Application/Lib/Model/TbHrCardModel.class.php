<?php

/**
 * 名片表模型
 */
class TbHrCardModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_card';
    protected $_link = [
        'empl' => [
            'mapping_type' => HAS_ONE,
            'class_name' => 'TbHrEmplModel',
            'foreign_key' => 'ID',
            'relation_foreign_key' => 'EMPL_ID',
            'mapping_name' => 'empl',
        ]

    ];
    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];

    protected $_validate = [
        /*['EMP_NM','require','请输入真名称'],//默认情况下用正则进行验证
        ['PER_PHONE','require','请输入联系方式'],//默认情况下用正则进行验证
        ['OFF_TEL','require','请输入分机号'],//默认情况下用正则进行验证
        ['JOB_TYPE_CD','require','请输入职位类别'],//默认情况下用正则进行验证
        ['PER_CART_ID','require','身份证号'],//默认情况下用正则进行验证
        ['SEX','require','请输入性别'],//默认情况下用正则进行验证
        ['PER_IS_SMOKING','require','请选择是否吸烟'],//默认情况下用正则进行验证
        ['PER_BIRTH_DATE','require','请填写出生日期'],//默认情况下用正则进行验证
        ['AGE','require','请输入年龄'],//默认情况下用正则进行验证
        ['PER_ADDRESS','require','请输入籍贯'],//默认情况下用正则进行验证
        ['PER_RESIDENT','require','请输入户籍'],//默认情况下用正则进行验证
        ['PER_IS_MARRIED','require','请输入婚姻状况'],//默认情况下用正则进行验证
        ['CHILD_NUM','require','请输入子女数'],//默认情况下用正则进行验证
        ['PER_POLITICAL','require','请选择政治面貌'],//默认情况下用正则进行验证
        ['HOUSEHOLD','require','请输入户口性质'],//默认情况下用正则进行验证
        ['PER_NATIONAL','require','请输入民族'],//默认情况下用正则进行验证
        ['FUND_ACCOUNT','require','请输入公积金账号'],//默认情况下用正则进行验证
        ['SC_EMAIL','require','请输入花名邮箱'],//默认情况下用正则进行验证
        ['EMAIL','require','请输入私人邮箱'],//默认情况下用正则进行验证
        ['WE_CHAT','require','请输入微信'],//默认情况下用正则进行验证
        ['QQ_ACCOUNT','require','请输入QQ账号'],//默认情况下用正则进行验证
        ['DETAIL','require','请输入户籍地址'],//默认情况下用正则进行验证
        ['DETAIL_LIVING','require','请输入现居住地址'],//默认情况下用正则进行验证
        ['FIRST_LAN','require','请输入语言能力'],//默认情况下用正则进行验证
        ['FIRST_LAN_LEVEL','require','请输入语言能力程度'],//默认情况下用正则进行验证
        ['SECOND_LAN','require','请输入第二语言能力'],//默认情况下用正则进行验证
        ['SECOND_LAN_LEVEL','require','请输入第二语言能力程度'],//默认情况下用正则进行验证
        ['HOBBY_SPA','require','请输入爱好及特长'],//默认情况下用正则进行验证

        ['PER_CARD_PIC','require','请上传身份证正反面照片'],//默认情况下用正则进行验证
        ['RESUME','require','请上传简历'],*///默认情况下用正则进行验证


    ];

    // show necessary employees type
    //public $necessary_empl_types = array('在职', '兼职',);
    public $necessary_empl_types = array('在职');

    public function findOneByEmplId($empl_id)
    {
        static $stc_one_empl = array();
        if (isset($stc_one_empl[$empl_id])) {
            return $stc_one_empl[$empl_id];
        }
        $data = $this->where(array('EMPL_ID' => $empl_id))->find();
        $data = is_array($data) ? $data : array();
        $stc_one_empl[$empl_id] = $data;
        return $data;
    }

    public function findOneWithType($empl_id, $typeArr = array())
    {
        $info = $this->findOneByEmplId($empl_id);
        $info = D('TbHrDept')->peopleNeceData($info);
        $typeArr['TYPE'] = isset($typeArr['TYPE']) ? $typeArr['TYPE'] : null;
        $info['employee_type'] = TbHrDeptModel::getTypeForEDRelation($typeArr['TYPE']);
        $info['employee_type_id'] = TbHrDeptModel::toFrontEDRelation($typeArr['TYPE']);
        $info['employee_type_level'] = isset($typeArr['TYPE_LEVEL']) ? $typeArr['TYPE_LEVEL'] : '';
        $info['job_rank'] = D('TbHrEmpl')
            ->alias('t')
            ->join('left join tb_hr_jobs a on a.ID=t.JOB_ID')
            ->where(['t.ID' => $empl_id])
            ->getField('a.RANK');
        // check empl and relation exists
        if (!isset($info['ID'])) {
            $typeArr['ID'] = isset($typeArr['ID']) ? $typeArr['ID'] : null;
            if ($typeArr['ID']) {
                $where_del = array();
                $where_del['ID'] = $typeArr['ID'];
                $status = M('hr_empl_dept', 'tb_')->where($where_del)->delete();
            }
        }
        if (isset($info['ID'])) {
            $status_str = isset($info['STATUS']) ? $info['STATUS'] : null;
            if (!in_array($status_str, $this->necessary_empl_types)) {
                $info = array();
            }
        }
        return $info;
    }

    /**
     *  Use keyword to search employees
     *
     */
    public function search_empl_by_key($searchdata = '')
    {
        $list = array();
        $searchdata = trim($searchdata);
        Mainfunc::SafeFilter($searchdata);
        if (empty($searchdata)) {
            return $list;
        }
        if (preg_match("/^[a-zA-Z\s]+$/", $searchdata)) {
            $count = strlen($searchdata);
            $newStr = '';
            for ($i = 0; $i < $count; $i++) {
                $newStr .= $searchdata[$i] . '%';
            }
        } else {
            $str = preg_split('/(?<!^)(?!$)/u', $searchdata);
            foreach ($str as $key => $value) {
                $newStr .= $value;
            }
        }
        $m_obj = D('TbHrCard');
        $where_data = array();
        $where_data['_logic'] = 'or';
        $where_data['ERP_ACT'] = array("like", "%{$newStr}%");
        //$where_data['EMPL_ID'] = array("like","%{$newStr}%");
        //$where_data['EMP_NM'] = array("like","%{$newStr}%");
        $where_data['EMP_SC_NM'] = array("like", "%{$newStr}%");
        //$where_data['EMAIL'] = array("like","%{$newStr}%");

        $order_data = array(
            'ID' => 'desc',
        );
        $list = $m_obj->field('*')
            ->where($where_data)
            ->order($order_data)
            ->select();
        $list = is_array($list) ? $list : array();
        $m_dept = D('TbHrDept');
        foreach ($list as $k => $v) {
            $list[$k] = $m_dept->peopleNeceData($v);
        }
        return $list;
    }

    public function searchDepartmentPeople($search, $dept_id)
    {
        $map['_logic'] = 'or';
        $map['EMP_NM'] = ['like', "%{$search}%"];
        $map['ERP_ACT'] = ['like', "%{$search}%"];
        $where['ID1'] = $dept_id;
        $where['_complex'] = $map;
        $people = $this
            ->field('t.EMPL_ID,EMP_SC_NM,ERP_ACT,JOB_CD,SEX,STATUS,WORK_NUM')
            ->alias('t')
            ->join('inner join tb_hr_empl_dept a on a.ID2=t.EMPL_ID')
            ->where($where)
            ->select();
        return $people;
    }

    public function searchPeople($search)
    {
        $map['_logic'] = 'or';
        $map['EMP_NM'] = ['like', "%{$search}%"];
        $map['ERP_ACT'] = ['like', "%{$search}%"];
        $people = $this
            ->field('EMPL_ID,EMP_SC_NM,ERP_ACT,JOB_CD,SEX,STATUS,WORK_NUM')
            ->where($map)
            ->select();
        return $people;
    }

    const Sex_man = 0;
    const Sex_woman = 1;
    const Sex_none = 2;

    public static function getSexForUser($key = null)
    {
        $items = [
            self::Sex_man => '男',
            self::Sex_woman => '女',
            self::Sex_none => '不填',
        ];
        return DataMain::getItems($items, $key);
    }

    // 是否吸烟 0否1是2不填
    const Smoke_no = 0;
    const Smoke_yes = 1;
    const Smoke_none = 2;

    public static function getSmokeForUser($key = null)
    {
        $items = [
            self::Smoke_no => 'No',
            self::Smoke_yes => 'Yes',
            self::Smoke_none => '',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  Mapping
     *  e.g.
     *           array(
     *               'lable'=>'',
     *               'value'=>'',
     *               'field'=>'',
     *           ),
     *
     */
    public function matchKeyTitle()
    {
        $arr = array(
            array(
                'lable' => "工号",
                'value' => 'workNum',
                'field' => 'WORK_NUM',
            ),
            array(
                'lable' => "花名",
                'value' => 'EmpScNm',
                'field' => 'EMP_SC_NM',
            ),
            array(
                'lable' => "部门",
                'value' => 'deptName',
                'field' => 'DEPT_NAME',
            ),
            array(
                'lable' => "组别",
                'value' => 'deptGroup',
                'field' => 'DEPT_GROUP',
            ),
            array(
                'lable' => "司龄",
                'value' => 'companyAge',
                'field' => 'COMPANY_AGE',
            ),
            array(
                'lable' => "中文职位",
                'value' => 'jobCd',
                'field' => 'JOB_CD',
            ),
            array(
                'lable' => "英文职位",
                'value' => 'JobEnCd',
                'field' => 'JOB_EN_CD',
            ),
            array(
                'lable' => "工作地点",
                'value' => 'workPlace',
                'field' => 'WORK_PALCE',
            ),
            array(
                'lable' => "直接领导",
                'value' => 'directLeader',
                'field' => 'DIRECT_LEADER',
            ),
            array(
                'lable' => "部门总监",
                'value' => 'departHead',
                'field' => 'DEPART_HEAD',
            ),
            array(
                'lable' => "对接HR",
                'value' => 'dockingHr',
                'field' => 'DOCKING_HR',
            ),
            array(
                'lable' => "职级",
                'value' => 'rank',
                'field' => 'RANK',
            ),
            array(
                'lable' => "离职时间",
                'value' => 'depJobDate',
                'field' => 'DEP_JOB_DATE',
            ),
            array(
                'lable' => "离职编号",
                'value' => 'depJobNum',
                'field' => 'DEP_JOB_NUM',
            ),
            array(
                'lable' => "ERP账号",
                'value' => 'erpAct',
                'field' => 'ERP_ACT',
            ),
            array(
                'lable' => "ERP密码",
                'value' => 'erpPwd',
                'field' => 'ERP_PWD',
            ),
            array(
                'lable' => "状态",
                'value' => 'status',
                'field' => 'STATUS',
            ),
            array(
                'lable' => "真名",
                'value' => 'empNm',
                'field' => 'EMP_NM',
            ),
            array(
                'lable' => "联系方式",
                'value' => 'prePhone',
                'field' => 'PER_PHONE',
            ),
            array(
                'lable' => "分机号",
                'value' => 'offTel',
                'field' => 'OFF_TEL',
            ),
            array(
                'lable' => "职位类别",
                'value' => 'jobTypeCd',
                'field' => 'JOB_TYPE_CD',
            ),
            array(
                'lable' => "身份证号码",
                'value' => 'perCartId',
                'field' => 'PER_CART_ID',
            ),
            array(
                'lable' => "性别",
                'value' => 'sex',
                'field' => 'SEX',
            ),
            array(
                'lable' => "是否吸烟",
                'value' => 'perIsSmoking',
                'field' => 'PER_IS_SMOKING',
            ),
            array(
                'lable' => "出生日期",
                'value' => 'perBirthDate',
                'field' => 'PER_BIRTH_DATE',
            ),
            array(
                'lable' => "年龄",
                'value' => 'age',
                'field' => 'AGE',
            ),
            array(
                'lable' => "籍贯",
                'value' => 'perAddress',
                'field' => 'PER_ADDRESS',
            ),
            array(
                'lable' => "户籍",
                'value' => 'perResident',
                'field' => 'PER_RESIDENT',
            ),
            array(
                'lable' => "婚姻状态",
                'value' => 'perIsMarried',
                'field' => 'PER_IS_MARRIED',
            ),
            array(
                'lable' => "子女数",
                'value' => 'childNum',
                'field' => 'CHILD_NUM',
            ),
            array(
                'lable' => "政治面貌",
                'value' => 'perPolitical',
                'field' => 'PER_POLITICAL',
            ),
            array(
                'lable' => "户口性质",
                'value' => 'hosehold',
                'field' => 'HOUSEHOLD',
            ),
            array(
                'lable' => "民族",
                'value' => 'perNational',
                'field' => 'PER_NATIONAL',
            ),
            array(
                'lable' => "公积金账号",
                'value' => 'fundAccount',
                'field' => 'FUND_ACCOUNT',
            ),
            array(
                'lable' => "花名邮箱",
                'value' => 'scEmail',
                'field' => 'SC_EMAIL',
            ),
            array(
                'lable' => "入职时间",
                'value' => 'perJobDate',
                'field' => 'PER_JOB_DATE',
            ),
            array(
                'lable' => "微信",
                'value' => 'weChat',
                'field' => 'WE_CHAT',
            ),
            array(
                'lable' => "私人邮箱",
                'value' => 'email',
                'field' => 'EMAIL',
            ),
            array(
                'lable' => "QQ",
                'value' => 'qqAccount',
                'field' => 'QQ_ACCOUNT',
            ),
            array(
                'lable' => "户籍地址",
                'value' => 'detail',
                'field' => 'DETAIL',
            ),
            array(
                'lable' => "爱好及特长",
                'value' => 'hobbySpa',
                'field' => 'HOBBY_SPA',
            ),
            array(
                'lable' => "毕业学院",
                'value' => 'graSCHOOL',
                'field' => 'GRA_SCHOOL',
            ),
            array(
                'lable' => "学历",
                'value' => 'eduBACK',
                'field' => 'EDU_BACK',
            ),
            array(
                'lable' => "专业",
                'value' => 'MAJORS',
                'field' => 'MAJORS',
            ),
            array(
                'lable' => "毕业时间",
                'value' => 'endTIME',
                'field' => 'endTIME',
            ),
            array(
                'lable' => "毕业证书编号",
                'value' => 'biyezhengshubianhao',
                'field' => 'biyezhengshubianhao',
            ),
            array(
                'lable' => "前一家公司",
                'value' => 'prevCompany',
                'field' => 'prevCompany',
            ),
            array(
                'lable' => "再前一家公司",
                'value' => 'prevPrevCompany',
                'field' => 'prevPrevCompany',
            ),
            array(
                'lable' => "现住址",
                'value' => 'presentAddress',
                'field' => 'DETAIL_LIVING',
            ),
            array(
                'lable' => "姓名",
                'value' => 'fullname',
                'field' => 'emer_fullname',
            ),
            array(
                'lable' => "关系",
                'value' => 'relationship',
                'field' => 'emer_relationship',
            ),
            array(
                'lable' => "联系方式",
                'value' => 'contactInformation',
                'field' => 'emer_contactInformation',
            ),
            array(
                'lable' => "银行",
                'value' => 'bank',
                'field' => 'b_bank',
            ),
            array(
                'lable' => "卡号",
                'value' => 'cardNumber',
                'field' => 'b_cardNumber',
            ),

        );
        return $arr;
    }

    public function findKeyLable($key)
    {
        static $stc_relations = null;
        if ($stc_relations === null) {
            $keyInfo = $this->matchKeyTitle();
            $stc_relations = array_column($keyInfo, 'lable', 'value');
        }
        $ret = isset($stc_relations[$key]) ? $stc_relations[$key] : null;
        return $ret;
    }

    public function findKeyData($key)
    {
        static $stc_relations = null;
        if ($stc_relations === null) {
            $keyInfo = $this->matchKeyTitle();
            $stc_relations = array_column($keyInfo, 'field', 'value');
        }
        $ret = isset($stc_relations[$key]) ? $stc_relations[$key] : null;
        return $ret;
    }

    /**
     *  check and gain by key from array
     *   格式化时间
     */
    public function gainKeyInfo($data = array(), $key = '')
    {
        $ret = '';
        $ret = isset($data[$key]) ? $data[$key] : '';
        $childrens = isset($data['children_list']) ? $data['children_list'] : null;
        // some extra
        if ($key == 'SEX') {
            $ret = self::getSexForUser($ret);
        }
        if ($key == 'PER_JOB_DATE' or $key == 'PER_BIRTH_DATE' or $key == 'DEP_JOB_DATE') {
            if ($ret == '0000-00-00 00:00:00') {
                $ret = '';
            }
            if ($ret) {
                $ret = date('Y-m-d', strtotime($ret));     //处理y-m-d格式
            }
        }
        // smoke
        if ($key == 'PER_IS_SMOKING') {
            $ret = self::getSmokeForUser($ret);
        }
        // 教育经历
        if ($key == 'MAJORS' or $key == 'endTIME' or $key == 'biyezhengshubianhao') {
            if ($childrens) {
                $tmp = D('TbHrEmplChild')->takeInfoListByType($childrens, 2, false);
                if ($tmp) {
                    $edu = D('TbHrEmplChild')->formatEdu($tmp);
                    switch ($key) {
                        case 'MAJORS':
                            $ret = isset($edu['major']) ? $edu['major'] : '';
                            break;
                        case 'endTIME':
                            $ret = isset($edu['end_date']) ? $edu['end_date'] : '';
                            break;
                        case 'biyezhengshubianhao':
                            $ret = isset($edu['graduation_certificate_number']) ? $edu['graduation_certificate_number'] : '';
                            break;
                        default:
                    }
                }
            }
        }
        // 工作经历
        if ($key == 'prevCompany' or $key == 'prevPrevCompany') {
            if ($childrens) {
                $tmp = D('TbHrEmplChild')->takeInfoListByType($childrens, 11, true);
                $tmp = ZFun::array_sort_rows($tmp, 'ID', 'asc');
                if ($tmp) {
                    switch ($key) {
                        case 'prevCompany':
                            $work = D('TbHrEmplChild')->formatWork(isset($tmp[0]) ? $tmp[0] : null);
                            $ret = isset($work['V_STR1']) ? $work['V_STR1'] : '';
                            break;
                        case 'prevPrevCompany':
                            $work = D('TbHrEmplChild')->formatWork(isset($tmp[1]) ? $tmp[1] : null);
                            $ret = isset($work['V_STR1']) ? $work['V_STR1'] : '';
                            break;
                        default:
                    }
                }
            }
        }
        // 紧急联系人信息
        if ($key == 'emer_fullname' or $key == 'emer_relationship' or $key == 'emer_contactInformation') {
            if ($childrens) {
                $tmp = D('TbHrEmplChild')->takeInfoListByType($childrens, 0, false);
                if ($tmp) {
                    $emer = D('TbHrEmplChild')->formatEmer($tmp);
                    switch ($key) {
                        case 'emer_fullname':
                            $ret = isset($emer['V_STR1']) ? $emer['V_STR1'] : '';
                            break;
                        case 'emer_relationship':
                            $ret = isset($emer['V_STR3']) ? $emer['V_STR3'] : '';
                            break;
                        case 'emer_contactInformation':
                            $ret = isset($emer['V_STR2']) ? $emer['V_STR2'] : '';
                            break;
                        default:
                    }
                }
            }
        }
        // 银行卡信息
        if ($key == 'b_bank' or $key == 'b_cardNumber') {
            if ($childrens) {
                $tmp = D('TbHrEmplChild')->takeInfoListByType($childrens, 12, true);
                $tmp = ZFun::array_sort_rows($tmp, 'ID', 'asc');
                if ($tmp) {
                    $bank = D('TbHrEmplChild')->formatBank(isset($tmp[0]) ? $tmp[0] : null);
                    switch ($key) {
                        case 'b_bank':
                            $ret = isset($bank['V_STR4']) ? $bank['V_STR4'] : '';
                            break;
                        case 'b_cardNumber':
                            $ret = isset($bank['V_STR1']) ? $bank['V_STR1'] : '';
                            break;
                        default:
                    }
                }
            }
        }
        // var_dump($key);
        return $ret;
    }

    /**
     * @param array $allow_email
     * @return array
     */
    public static function getCardWorkPalce(array $allow_email)
    {
        $Model = new Model();
        $cards = $Model->table('bbm_admin,tb_hr_card')
            ->field('bbm_admin.M_EMAIL,tb_hr_card.WORK_PALCE')
            ->where(['bbm_admin.M_EMAIL' => ['IN', $allow_email]])
            ->where("bbm_admin.empl_id = tb_hr_card.EMPL_ID", null, true)
            ->select();
        $cards_key_val = array_column($cards, 'WORK_PALCE', 'M_EMAIL');
        return $cards_key_val;
    }

    /**
     * @param array $allow_email
     * @return array
     */
    public static function getCardWorkPalceFromUser($user_name)
    {
        $Model = new Model();
        return $Model->table('bbm_admin,tb_hr_card')
            ->where(['bbm_admin.M_NAME' => $user_name])
            ->where("bbm_admin.empl_id = tb_hr_card.EMPL_ID", null, true)
            ->getField('tb_hr_card.WORK_PALCE');
    }

}



