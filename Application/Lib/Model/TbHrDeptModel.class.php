<?php
class TbHrDeptModel extends BaseModel{
    protected $trueTableName = 'tb_hr_dept';
    public $max_level = 3;

    protected $_auto = [
        ['CREATE_TIME','getTime',Model::MODEL_INSERT,'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];

    protected $_validate = array(
        array('DEPT_NM','require','必填:部门名称',1),
        array('DEPT_SHORT_NM','require','必填:部门简称',1),
        array('TYPE','require','必填:部门类型',1),
        array('LEGAL_PERSON','require','必填:法务负责人',1),
//        array('DEPT_NM','','重复:部门名称',0,'unique'), // 在新增的时候验证 name 字段是否唯一
//        array('DEPT_EN_NM','require','必填:部门英文名称',1),
//        array('DEPT_CN_NM','require','必填:部门中文名称',1),
    );

    // 
    public function _before_insert(&$data,$options){
        parent::_before_insert($data, $options);
        $data['CREATE_USER_ID'] = isset($data['CREATE_USER_ID'])?$data['CREATE_USER_ID']:null;
        $data['UPDATE_USER_ID'] = isset($data['UPDATE_USER_ID'])?$data['UPDATE_USER_ID']:null;
        $data['CREATE_USER_ID'] = intval($data['CREATE_USER_ID']);
        $data['UPDATE_USER_ID'] = intval($data['UPDATE_USER_ID']);
    }

    // 
    public function _before_update(&$data,$options){
        parent::_before_update($data, $options);
        $data['UPDATE_USER_ID'] = isset($data['UPDATE_USER_ID'])?$data['UPDATE_USER_ID']:null;
        $data['UPDATE_USER_ID'] = intval($data['UPDATE_USER_ID']);
    }

    public static $stc_empl_dept_list = array();

    /**
     *  Department type
     *
     */
    const Type_company = 0;
    const Type_department = 1;
    const Type_team = 2;
    public static function getTypeForDept($key = null)
    {
        $items = [
            self::Type_company => '公司', 
            self::Type_department => '部门',
            self::Type_team => '团队',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  Department status
     *
     */
    public static function getStatusForDept($key=null){
        $items = D("ZZmscmncd")->getValueFromName('HR-DEPT-STATUS');
        return DataMain::getItems($items, $key);
    }

    /**
     *  Department type
     *
     */
    const Type_ed_incharge = 0;
    const Type_ed_default = 1;
    const Type_ed_other = 2;
    public static function getTypeForEDRelation($key = null)
    {
        $items = [
            self::Type_ed_incharge => '负责人', 
            self::Type_ed_default => '默认',
            self::Type_ed_other => '其他',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  make relation for front
     *  @param type  int
     *  @param way   int  - 0 output front , 1 input front
     */
    public static function toFrontEDRelation($type,$way=0){
        $map = array(
            self::Type_ed_incharge => self::Type_ed_default,
            self::Type_ed_default => self::Type_ed_incharge,
        );
        $ret = null;
        if($way==0){
            $ret = isset($map[$type])?$map[$type]:$type;
        }
        elseif($way==1){
        }
        return $ret;
    }

    /**
     *  Type level of the people who is department
     *
     */
    const Type_level_0 = 0;
    const Type_level_1 = 1;
    const Type_level_2 = 2;
    public static function getTypeLevelForEDRelation($key = null)
    {
        $items = [
            self::Type_level_0 => '默认', 
            self::Type_level_1 => '一级负责人',
            self::Type_level_2 => '二级负责人',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  make a default one
     *
     */
    public function setDefaultOne(){
        $default_data = array();
        $default_data['DEPT_NM'] = 'Gshopper';
        $default_data['DEPT_EN_NM'] = 'Gshopper';
        $default_data['DEPT_CN_NM'] = 'Gshopper';
        $default_data['TYPE'] = 'N002510100';
        $default_data['STATUS'] = 'N001490300';
        $default_data['DEPT_LEVEL'] = '0';
        $default_data['PAR_DEPT_ID'] = '0';
        if($ret = $this->create($default_data)) {
            if($isok = $this->add($this->data(''))) {
            }
        }
        $isok = isset($isok)?$isok:null;
        return $isok;
    }

    /**
     *  Use keyword to search departments
     *
     */
    public function search_dept_by_key($searchdata=''){
        $list = array();
        $searchdata = trim($searchdata);
        Mainfunc::SafeFilter($searchdata);
        if(empty($searchdata)){
            // return $list;
        }
        $m_obj = $this;
        $where_data = array();
        $where_data['_logic'] = 'or';
        $where_data['DEPT_NM'] = array("like","%{$searchdata}%");
        $where_data['DEPT_EN_NM'] = array("like","%{$searchdata}%");
        $where_data['DEPT_CN_NM'] = array("like","%{$searchdata}%");
        $order_data = array(
            'ID'=>'desc',
        );
        $list = $m_obj->field ('*')
                ->where ($where_data)
                ->order($order_data)
                ->select();
        $list = is_array($list)?$list:array();
        return $list;
    }

    /**
     *  Use keyword to search departments
     *
     */
    public function dept_list(){
        $m_obj = $this;
        $order_data = array(
            'SORT'=>'asc',
        );
        $list = $m_obj->field('ID,DEPT_NM,DEPT_EN_NM,DEPT_CN_NM,DEPT_SHORT_NM,TYPE,SORT,LEGAL_PERSON,PAR_DEPT_ID')->order($order_data)->where("DELETED_BY IS NULL")->select();
        $list = array_column($list,null,"ID");
        foreach ($list as $k => $v) {
            //部门类型名称
            $list[$k]['TYPE_NM']    = $this->getDeptTypeName($v['TYPE']);
            //部门上级
            $parent_department      = $v['PAR_DEPT_ID'];
            $parent_department_top  = $parent_department;
            //部门领导
            $list[$k]['leader_direct']  = $this->getDeptLeader($k);
            if($list[$parent_department]['PAR_DEPT_ID']) {
                //递归取最上级部门（第二级，第一级为公司）
                $list[$k]['PAR_DEPT_NM']    = $list[$parent_department]['DEPT_NM'];
                while (true) {
                    if(!$list[$list[$parent_department_top]['PAR_DEPT_ID']]['PAR_DEPT_ID']) break;
                    $parent_department_top  = $list[$parent_department_top]['PAR_DEPT_ID'];
                }
                if($parent_department != $parent_department_top) {
                    $list[$k]['PAR_DEPT_NM'] .= '/'.$list[$parent_department_top]['DEPT_NM'];
                }
                $list[$k]['leader_dept']    = $this->getDeptLeader($parent_department_top);
            }else {
                $list[$k]['PAR_DEPT_NM']  = '';
                $list[$k]['leader_dept']  = $list[$k]['leader_direct'];
            }

        }
        return $list;
    }


    /**
     *  从高到低部门等级返回
     *
     */
    public function dept_info(){
        $m_obj = $this;
        $order_data = array(
            'SORT'=>'asc',
        );
        $list = $m_obj->field('ID,DEPT_NM,DEPT_EN_NM,DEPT_CN_NM,DEPT_SHORT_NM,TYPE,SORT,LEGAL_PERSON,PAR_DEPT_ID')->order($order_data)->where("DELETED_BY IS NULL")->select();


        $list = array_column($list,null,"ID");


        foreach ($list as $k => $v) {
            //部门类型名称
            $list[$k]['TYPE_NM']    = $this->getDeptTypeName($v['TYPE']);
            //部门上级
            $parent_department      = $v['PAR_DEPT_ID'];

            $parent_department_top  = $parent_department;

            //部门领导
            $list[$k]['leader_direct']  = $this->getDeptLeader($k);
            if($list[$parent_department]['PAR_DEPT_ID']) {
                //递归取最上级部门（第二级，第一级为公司）
                $list[$k]['PAR_DEPT_NM']    = $list[$parent_department]['DEPT_NM'];
                while (true) {
                    if(!$list[$list[$parent_department_top]['PAR_DEPT_ID']]['PAR_DEPT_ID']) break;
                    $parent_department_top  = $list[$parent_department_top]['PAR_DEPT_ID'];
                }
                if($parent_department != $parent_department_top) {
                    $list[$k]['DEPT_NM'] =  $list[$k]['PAR_DEPT_NM'].'/'. $list[$k]['DEPT_NM'];
                    $list[$k]['PAR_DEPT_NM']    = $list[$parent_department_top]['DEPT_NM'];
                }
                $list[$k]['leader_dept']    = $this->getDeptLeader($parent_department_top);
            }else {
                $list[$k]['PAR_DEPT_NM']  = '';
                $list[$k]['leader_dept']  = $list[$k]['leader_direct'];
            }

        }
        return $list;
    }


    public function getDeptTypeName($dept_type) {
        return cdVal($dept_type);
    }

    public function getDeptLeader($dept_id) {
        $where = [
            't.ID1'   => $dept_id,
            't.TYPE'  => self::Type_ed_incharge,
        ];
        $leader = D('Hr/HrEmplDept')
            ->alias('t')
            ->join('tb_hr_empl a on a.ID=t.ID2')
            ->where($where)
            ->order('TYPE_LEVEL ASC')
            ->getField('EMP_SC_NM',true);
        $leader = implode(',',$leader);
        return $leader;
    }

    public function getDeptLeaderNew($dept_id) {
        $where = [
            't.ID1'   => $dept_id,
            't.TYPE'  => self::Type_ed_incharge,
            'card.STATUS'=> '在职',
            'd.PAR_DEPT_ID'=> ['gt', 0],//过滤最顶级部门的负责人
//            'd.STATUS'=> ['neq','N001490100']//部门状态生效
        ];
        $leader = D('Hr/HrEmplDept')
            ->alias('t')
            ->field('card.ERP_ACT')
            ->join('tb_hr_card card on card.EMPL_ID=t.ID2')
            ->join('tb_hr_empl empl ON card.EMPL_ID = empl.ID')
            ->join('tb_hr_jobs jobs ON empl.JOB_ID = jobs.ID')
            ->join('tb_hr_dept d on t.ID1=d.ID')
            ->where($where)
            ->order('d.DEPT_LEVEL DESC, d.PAR_DEPT_ID DESC, d.ID DESC, t.TYPE_LEVEL DESC,jobs.RANK DESC')
            ->select();
        $leader = array_unique(array_column($leader, 'ERP_ACT'));
        $leader = implode(',',$leader);
        return $leader;
    }

    public function getDeptLeaderByEmpName($emp_sc_nm)
    {
        $result = [];
        $dept_ids = M('hr_card','tb_')
            ->field('b.ID')
            ->join('tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_id')
            ->join('tb_hr_dept b on b.ID=a.ID1')
//            ->order('WORK_NUM+0 desc')
            ->order('b.DEPT_LEVEL DESC, b.PAR_DEPT_ID DESC, b.ID DESC')
            ->where(['tb_hr_card.STATUS' => '在职', 'ERP_ACT' => $emp_sc_nm])
            ->select();
        $dept_ids = array_column($dept_ids, 'ID');
        foreach ($dept_ids as $dept_id) {
            $result[] = $this->recursiveSearchDeptLeader($dept_id);
        }
        $result = array_unique(array_filter($result));
        $leader_str = '';
        array_map(function($item) use (&$leader_str) {
            //$leader_str .= $item. ',';
            $leader_str .= $item. ',';
        }, $result);
        //return trim($leader_str, ',');
        return trim($leader_str, ',');
    }

    //根据登录用户and审核部门查询审批领导列
    public function getDeptLeaderByDeptId($emp_sc_nm, $dept_id)
    {
        $result = [];
        $dept_ids = M('hr_card','tb_')
            ->field('b.ID')
            ->join('tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_id')
            ->join('tb_hr_dept b on b.ID=a.ID1')
//            ->order('WORK_NUM+0 desc')
            ->order('b.DEPT_LEVEL DESC, b.PAR_DEPT_ID DESC, b.ID DESC')
            ->where(['tb_hr_card.STATUS' => '在职', 'ERP_ACT' => $emp_sc_nm, 'b.ID' => $dept_id])
            ->select();
        $dept_ids = array_column($dept_ids, 'ID');
        foreach ($dept_ids as $dept_id) {
            $result[] = $this->recursiveSearchDeptLeader($dept_id);
        }
        $result = array_unique(array_filter($result));
        $leader_str = '';
        array_map(function($item) use (&$leader_str) {
            //$leader_str .= $item. ',';
            $leader_str .= $item. ',';
        }, $result);
        //return trim($leader_str, ',');
        return trim($leader_str, ',');
    }

    //根据用户名获取归属部门
    public function getDeptListByEmpName($emp_sc_nm)
    {
        $dept_list = M('hr_card','tb_')
            ->field('b.ID,b.DEPT_NM,b.DEPT_EN_NM,b.DEPT_CN_NM')
            ->join('tb_hr_empl_dept a on a.ID2=tb_hr_card.EMPL_id')
            ->join('tb_hr_dept b on b.ID=a.ID1')
//            ->order('WORK_NUM+0 desc')
            ->order('b.DEPT_LEVEL DESC, b.PAR_DEPT_ID DESC, b.ID DESC')
            ->where(['tb_hr_card.STATUS' => '在职', 'ERP_ACT' => $emp_sc_nm])
            ->select();
        return $dept_list;
    }

    /**
     * 递归查找部门领导关系树
     * @param $dept_id
     * @param $dept_leader
     * @return string
     */
    public function recursiveSearchDeptLeader($dept_id, $dept_leader)
    {
        $parent_dept_id = $this->where(['ID' => $dept_id])->getField('PAR_DEPT_ID');
        $dept_leader[] = $this->getDeptLeaderNew($dept_id);
        if (empty($parent_dept_id)) {
            $leader_str = '';
            $dept_leader = array_filter($dept_leader);
            array_map(function($item) use (&$leader_str) {
                $leader_str .= $item. ',';
            }, $dept_leader);
            return trim($leader_str, ',');
        }
        return $this->recursiveSearchDeptLeader($parent_dept_id, $dept_leader);
    }

    /**
     *  Get one dept with id or name
     *
     */
    public function gainDeptByMagicStr($whichstr){
        $ret_data = null;
        $is_id = is_numeric($whichstr);
        if($is_id){
            $ret_data = $this->gainSimpleOneDept($whichstr);
        }

        if($ret_data===null){
            $ret_data = $this->gainDeptByName($whichstr);
        }
        $ret_data = is_array($ret_data)?$ret_data:array();

        return $ret_data;
    }

    /**
     *  Get dept with name and parent name.
     *
     */
    public function gainDeptCheckCheifName($name='',$p_name=''){
        $retInfo = array();
        $retInfo['dept_name'] = null;
        $retInfo['dept_group'] = null;
        $list = $this->where(array('DEPT_NM'=>$name))->select();
        $list = is_array($list)?$list:array();
        foreach($list as $key=>$val){
            $p_id = $val['PAR_DEPT_ID'];
            $p_data = $this->gainSimpleOneDept($p_id);
            $one_name = isset($p_data['DEPT_NM'])?$p_data['DEPT_NM']:null;
            if($one_name==$p_name){
                // match ok
                $retInfo['dept_name'] = $p_data;
                $retInfo['dept_group'] = $val;
                break(1);
            }
        }
        return $retInfo;
    }


     /**
     *  Just get one dept (simple data)
     *
     */
    public function gainSimpleOneDept($id){
        //递归获取所有父级id
        $data = $this->where(array('ID'=>$id))->find();
        static $ids=[]; 
        static $parentDeptNameAll = [];  
        $ids[] = $data['ID'];
        if($data['PAR_DEPT_ID']==='0'){   //递归出口
            $where['ID'] = array('in',$ids);
            $parentDeptAll = $this->where($where)->select();
            $parentDeptNameAll = array_column($parentDeptAll, 'DEPT_NM');            
        }else{
            $this->gainSimpleOneDept($data['PAR_DEPT_ID']);
        }
        return $parentDeptNameAll;
    }

    /**
     *  Get dept with name and parent name.
     *
     */
    public function gainDeptByCheifName($name='',$p_name=''){
        $retInfo = array();
        $retInfo['dept_name'] = null;
        $retInfo['dept_group'] = null;
        //$list = $this->where(array('DEPT_NM'=>$name))->select();
        $list = $this->where("lower(replace(DEPT_NM,' ',''))="."'".strtolower(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$name))."'")->select();
        $list = is_array($list)?$list:array();
        foreach($list as $key=>$val){
            $p_id = $val['PAR_DEPT_ID'];   //父级id
            $p_data = $this->gainSimpleOneDept($p_id);  //子组别的父级部门
            foreach ($p_data as $k => $v) {
                $change_p_data[] =strtolower(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$v));
            }
            if(in_array(strtolower(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$p_name)), $change_p_data)){
                // match ok
                $p_data = $this->where("lower(replace(DEPT_NM,' ',''))="."'".strtolower(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$p_name))."'")->find();
                $retInfo['dept_name'] = $p_data;
                $retInfo['dept_group'] = $val;
                break(1);
            }else{
                $retInfo = false;
            }
        }
        return $retInfo;
    }

    /**
     *  Get one dept by name
     *
     */
    public function gainDeptByName($name){
        //$data = $this->where(array('DEPT_NM'=>$name))->find();
        $data = $this->where("lower(replace(DEPT_NM,' ',''))="."'".strtolower(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$name))."'")->find();
        $data = is_array($data)?$data:array();
        return $data;
    }

    /**
     *  Get one dept id by name
     *
     */
    public function gainDeptIdByName($name){
        $data = $this->gainDeptByName($name);
        $id = isset($data['ID'])?$data['ID']:null;
        return $id;
    }



   


    /**
     *  Just get one dept (tb data)
     *
     */
    public function gainEasyOneDept($id){
        $data = $this->where(array('ID'=>$id))->find();
        $data = is_array($data)?$data:array();
        return $data;
    }


    /**
     *  Get one dept
     *
     */
    public function gainOneDept($id){
        $data = $this->where(array('ID'=>$id))->find();
        if($data){
            $dept_id = $data['ID'];
            //在职人数
            $data['staff_count'] = $this->countDeptStaffNum($dept_id);
            //分部信息
            //下级分部
            $child_branch = $this->gainChildDeptByType($dept_id,null);
            //下级部门
            $child_department = $this->gainChildDeptByType($dept_id,'2');
            $data['child_branch'] = $child_branch;
            $data['child_department'] = $child_department;
            //下级的下级分部
            $child_next_ids = array_column($child_branch,'ID','ID');
            $child_branch_next = $this->gainChildDeptByType($child_next_ids,null);
            $data['child_branch_next'] = $child_branch_next;
            //所属部门
            $data['belong_to'] = $this->gainSimpleOneDept($data['PAR_DEPT_ID']);
            //部门负责人
            $data['people_in_charge'] = $this->gainDeptPeopleInCharge($dept_id);
            //部门员工
            $data['people_employees'] = $this->gainDeptPeopleList($dept_id);
        }

        return $data;
    }

    /**
     *  Get children by parent id and type
     *  @param $p_id 
     *  @param $type 类型；0-公司;1-部门;2-团队
     *
     */
    public function gainChildDeptByType($p_id,$type=null){
        $wheredata = array();
        $wheredata['PAR_DEPT_ID'] = $p_id;
        if(is_array($p_id) and (!empty($p_id))){
            $wheredata['PAR_DEPT_ID'] = array('IN',$p_id);
        }
        if($type){
            $wheredata['TYPE'] = $type;
        }
        $data = $this->where($wheredata)->select();
        if($data){
            foreach($data as $key=>$val){
                $tmp = $this->countDeptStaffNum($val['ID']);
                $data[$key]['staff_count'] = $tmp;
                //部门员工
                $data[$key]['people_employees'] = $this->gainDeptPeopleList($val['ID']);
            }
        }
        $data = is_array($data)?$data:array();
        return $data;
    }

    /**
     *  Number of department employees
     *
     */
    public function countDeptStaffNum($dept_id){
        $m = M('hr_empl_dept', 'tb_');
        $wheredata = array('ID1'=>$dept_id);
        $wheredata['STATUS'] = array('IN',D('TbHrCard')->necessary_empl_types);
        $count = $m->join(' tb_hr_card ON tb_hr_card.EMPL_ID = tb_hr_empl_dept.ID2')->where($wheredata)->count();
        return $count;
    }

    /**
     *  people who in charge the dept
     *
     */
    public function gainDeptPeopleInCharge($dept_id){
        $m = M('hr_empl_dept', 'tb_');
        $info = $m->where(
            array(
                'ID1'=>$dept_id,
                'TYPE'=>self::Type_ed_incharge,
            )
        )->select();
        $ret = null;
        if($info){
            foreach($info as $key=>$val){
                $staff_id = $val['ID2'];
                $info2 = D('TbHrCard')->findOneWithType($staff_id,$val);
                if(!isset($info2['ID'])) continue(1);
                $ret[] = $info2;
            }
        }
        $ret = is_array($ret)?$ret:array();
        return $ret;
    }


    /**
     * 获取部门下的员工
     * @param $dept_id
     * @param null $type
     * @return array
     */
    public function gainDeptPeopleList($dept_id){
        $all_dept_list = self::$stc_empl_dept_list;
        // default list
        $list = null;
        if(isset($all_dept_list[$dept_id])){
            $list = $all_dept_list[$dept_id];
        }
        // select one by one
         

        if(empty($all_dept_list)){
            $employee_query = M('hr_empl_dept', 'tb_')
                ->field('tb_hr_empl_dept.*')
                ->join('tb_hr_empl on tb_hr_empl.ID = tb_hr_empl_dept.ID2')
                ->join('tb_hr_jobs on tb_hr_jobs.ID = tb_hr_empl.JOB_ID');
            $leader_query = clone $employee_query;

            //部门负责人按选择顺序排序，非部门负责人按职级排序
            $employee_list = $employee_query->where(['tb_hr_empl_dept.TYPE'=>1, 'tb_hr_empl_dept.ID1'=>$dept_id])->order('IF (isnull(tb_hr_jobs.RANK),1,0),tb_hr_jobs.RANK')->select();
            $leader_list   = $leader_query->where(['tb_hr_empl_dept.TYPE'=>0, 'tb_hr_empl_dept.ID1'=>$dept_id])->order('tb_hr_empl_dept.SORT')->select();
            $employee_list = empty($employee_list) ? [] : $employee_list;
            $leader_list   = empty($leader_list) ? [] : $leader_list;
            $list          = array_merge($leader_list, $employee_list);
        }

        $ret = array();
        if($list){
            foreach($list as $key=>$val){
                $staff_id           = $val['ID2'];//员工id
                $info               = D('TbHrCard')->findOneWithType($staff_id,$val);//找到员工信息
                $info['PERCENT']    = $val['PERCENT'];
                if(!isset($info['ID'])) continue(1);
                $ret[] = $info;
            }
        }
        $ret = is_array($ret)?$ret:array();
        return $ret;
    }

    /**
     *  gain people in dept
     *
     */
    public function gainDeptPeople($dept_id,$empl_id){
        $m = M('hr_empl_dept', 'tb_');
        $list = $m->where(
            array(
                'ID2'=>$empl_id,
                'ID1'=>$dept_id,
            )
        )->select();
        return $list;
    }

    public function addInCharge($empl_id,$dept_id,$type=0){
        $status = null;
        if(empty($empl_id) or empty($dept_id)){
            return $status;
        }
        $user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:null;
        $user_id = intval($user_id);
        $m = M('hr_empl_dept', 'tb_');
        $empldeptdata = array(
            'ID2'=>$empl_id,
            'ID1'=>$dept_id,
            'TYPE'=>$type,
            'CREATE_TIME'=>date('Y-m-d H:i:s'),
            'UPDATE_TIME'=>date('Y-m-d H:i:s'),
            'CREATE_USER_ID'=>$user_id,
            'UPDATE_USER_ID'=>$user_id,
            'PERCENT'=>100,    //默认部门占比100%
        );

        $status = $m->data($empldeptdata)->add();

        return $status;
    }

    public function editInCharge($empl_id,$dept_id,$type=0){
        $user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:null;
        $user_id = intval($user_id);
        $m = M('hr_empl_dept', 'tb_');
        $empldeptdata = array(
            'ID2'=>$empl_id,
            'ID1'=>$dept_id,
            'TYPE'=>$type,
            'UPDATE_TIME'=>date('Y-m-d H:i:s'),
            'UPDATE_USER_ID'=>$user_id,
        );
        $status = $m->where(array('ID2'=>$empl_id,'ID1'=>$dept_id))->data($empldeptdata)->save();
        return $status;
    }

    /**
     *  Set people in charge of dept
     *
     */
    public function relateInCharge($empl_id,$dept_id,$type=0){
        $ret_status = null;
        if(empty($empl_id) or empty($dept_id)){
            return $ret_status;
        }
        $list = $this->gainDeptPeople($dept_id,$empl_id);

        // update
        $m = M('hr_empl_dept', 'tb_');
        $update_to_normal = $m
            ->where(array('TYPE'=>$type,'ID1'=>$dept_id))
            ->data(array('TYPE'=>self::Type_ed_default))
            ->save();
        // people in dept     -- 1) update no in charge 2) update in charge
        if($list){
            $ret_status = $this->editInCharge($empl_id,$dept_id,$type);
        }
        // people not in dept -- 1) update no in charge 2) insert in charge
        if(!$list){
            $ret_status = $this->addInCharge($empl_id,$dept_id,$type);
        }
        return $ret_status;
    }

    /**
     *  delete employee from department
     *
     */
    public function delDeptEmpl($dept_id,$empl_id){
        $m_empl_dept = M('hr_empl_dept', 'tb_');
        $where_del = array('ID1'=>$dept_id,'ID2'=>$empl_id,'TYPE'=>array('IN',array(0,1)));
        $status = $m_empl_dept->where($where_del)->delete();
        return $status;
    }

    /**
     *  Set some people in charge of dept
     *
     */
    public function relateMoreInCharge($dept_id,$empl_ids,$type=0){
        $ret = null;
        $this->startTrans();
        if(is_string($empl_ids)){
            $empl_ids = explode(',',$empl_ids);
        }
        $empl_ids = is_array($empl_ids)?$empl_ids:array();
        $exists_incharge = $this->gainDeptPeopleInCharge($dept_id);
        $exists_ids = array();
        foreach($exists_incharge as $key=>$val){
            $exists_ids[] = $val['EMPL_ID'];
        }
        // check one by one way
        if($exists_ids){
            // var_dump($exists_ids);
            foreach($exists_ids as $k=>$v){
                if(!in_array($v,$empl_ids)){
                    // del and check if keep normal employee
                    $this->delDeptEmpl($dept_id,$v);
                    $one_info = D('TbHrCard')->findOneByEmplId($v);
                    $tmpData = D('TbHrEmpl')->fmtDeptByEmpl($one_info);
                    $tmp_dept_id = null;
                    $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_GROUP_id'])?null:$tmpData['format_DEPT_GROUP_id']);
                    $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_NAME_id'])?null:$tmpData['format_DEPT_NAME_id']);
                    // keep
                    $tmp = $this->addEmployeeToDepartment($v,$tmp_dept_id);
                }
            }
        }
        // delete old not need
        $m_empl_dept = M('hr_empl_dept', 'tb_');
        // $where_del = array();
        // $where_del['TYPE'] = $type;
        // $where_del['ID1'] = $dept_id;
        // if($empl_ids) $where_del['ID2'] = array('NOT IN',$empl_ids);
        // $status = $m_empl_dept->where($where_del)->delete();
        // add not exists
        foreach($empl_ids as $v_id){
            // delete empl
            $where_del = array();
            $where_del['TYPE'] = array('IN',array(0,1));
            $where_del['ID1'] = $dept_id;
            $where_del['ID2'] = $v_id;
            $status = $m_empl_dept->where($where_del)->delete();
            // if(in_array($v_id,$exists_ids)){
            // }else{
                $status = $this->addInCharge($v_id,$dept_id,$type);
            // }
        }
        $this->commit();
        // ok
        $ret = $empl_ids;
        return $ret;
    }

    /**
     *  Add people to dept
     *
     */
    public function addEmployeeToDepartment($empl_id,$dept_id,$type=1){
        $ret_status = null;
        if(empty($empl_id) or empty($dept_id)){
            return $ret_status;
        }
        $list = $this->gainDeptPeople($dept_id,$empl_id);
        if($list){
            // if one is in charge , do nothing
            $is_incharge = 0;
            foreach($list as $k=>$v){
                if($v['TYPE']==self::Type_ed_incharge){
                    $is_incharge = 1;
                    $ret_status = 'in charge';
                }
            }
            if(!$is_incharge){
                $ret_status = $this->editInCharge($empl_id,$dept_id,$type);
            }
        }
        if(!$list){
            $ret_status = $this->addInCharge($empl_id,$dept_id,$type);
        }
        return $ret_status;
    }

    /**
     *  Match employee with department
     *
     */
    public function matchEmployeeToDepartment($empl_id,$dept_id,$type=1){
        $ret_status = null;
        // delete whether employee in some departments
        $m_empl_dept = M('hr_empl_dept', 'tb_');
        $status = $m_empl_dept->where(
            array(
                'TYPE'=>$type,
                'ID2'=>$empl_id,
            )
        )->delete();
        // add relation
        $ret_status = $this->addEmployeeToDepartment($empl_id,$dept_id,$type);
        return $ret_status;
    }

    /**
     *  Update the people (responsible level)
     *
     */
    public function renewTypeLevel($dept_id,$empl_ids,$type_levels){
        if(is_string($empl_ids)){
            $empl_ids = explode(',',$empl_ids);
        }
        $empl_ids = is_array($empl_ids)?$empl_ids:array();
        $type_levels = is_array($type_levels)?$type_levels:array();
        $n_empl = count($empl_ids);
        $n_level = count($type_levels);
        if($n_empl!=$n_level){
            //clean
            $type_levels = array();
        }
        // do everyone
        $m_empl_dept = M('hr_empl_dept', 'tb_');
        foreach($empl_ids as $key=>$empl_id){
            $level = isset($type_levels[$key])?$type_levels[$key]:null;
            $level = $level?$level:2;

            // delete old data which it it zero.
            $where_data = array(
                'TYPE'=>self::Type_ed_incharge,
                'ID2'=>$empl_id,
                'TYPE_LEVEL'=>self::Type_level_0,
                'ID1'=>$dept_id,
            );
            $status = DataMain::dbDelByNameData($m_empl_dept,$where_data);

            // check exists
            $one = $m_empl_dept->where(
                array(
                    'ID2'=>$empl_id,
                    'ID1'=>$dept_id,
                    'TYPE'=>self::Type_ed_incharge,
                    'TYPE_LEVEL'=>$level,
                )
            )->find();

            // add or edit(do nothing)
            if($one===null){
                // add
                $status = $this->addPeopleDeptAndLevel($empl_id,$dept_id,self::Type_ed_incharge,$level);
            }
        }
        // do everyone - end

    }

    /**
     *  Add people to dept (type and level)
     *
     */
    public function addPeopleDeptAndLevel($empl_id,$dept_id,$type=0,$type_level=2){
        $status = null;
        if(empty($empl_id) or empty($dept_id)){
            return $status;
        }
        $user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:null;
        $user_id = intval($user_id);
        $m = M('hr_empl_dept', 'tb_');
        $empldeptdata = array(
            'ID2'=>$empl_id,
            'ID1'=>$dept_id,
            'TYPE'=>$type,
            'CREATE_TIME'=>date('Y-m-d H:i:s'),
            'UPDATE_TIME'=>date('Y-m-d H:i:s'),
            'CREATE_USER_ID'=>$user_id,
            'UPDATE_USER_ID'=>$user_id,
            'TYPE_LEVEL'=>$type_level,
        );
        $status = $m->data($empldeptdata)->add();
        return $status;
    }

    /**
     * @return array the staff short rules.
     */
    public function people_rules()
    {
        return [
            'ID',
            'ERP_ACT',
            'EMPL_ID',
            'EMP_NM',
            'EMP_SC_NM',
            'SEX',
            'EMAIL',
            'WORK_NUM',
            'STATUS',
            'DIRECT_LEADER',
            'JOB_CD',
            'PER_JOB_DATE',
        ];
    }

    /**
     * 组装员工其它信息
     *  Short people fields
     *
     */
    public function peopleNeceData($people_data){
        $people_data = is_array($people_data)?$people_data:array();
        $mTbHrCard = D('TbHrCard');
        $need_keys = $this->people_rules();
        if($people_data){
            $tmp = array();
            foreach($need_keys as $v_rule){
                $tmp[$v_rule] = isset($people_data[$v_rule])?$people_data[$v_rule]:'';
                $tmp[$v_rule] = $mTbHrCard->gainKeyInfo($people_data,$v_rule);
            }
            $people_data = $tmp;
        }
        return $people_data;
    }

    /**
     * @param $list 部门信息
     * @param int $level 部门等级
     * @param int $parent_dept_id 父部门id
     * @return array
     */
    public function organizeDeptTree($list, $level=0, $parent_dept_id=0){
        $list = is_array($list)?$list:array();
        $do_list = array();
        foreach($list as $key=>$val){
            $one_dept   = $val;
            $dept_id    = $one_dept['ID'];
            $level_dept = $one_dept['DEPT_LEVEL'];
            $p_dept_id  = $one_dept['PAR_DEPT_ID'];

            if($level==0){
                if($level==$level_dept){
                    $one_dept['people_employees'] = $this->gainDeptPeopleList($dept_id);
                    $one_dept['next_nodes'] = $this->organizeDeptTree($list, ($level+1), $dept_id);
                    $do_list[] = $one_dept;
                }
            }else{
                if($parent_dept_id==$p_dept_id){
                    $one_dept['people_employees'] = $this->gainDeptPeopleList($dept_id);
                    $one_dept['next_nodes'] = $this->organizeDeptTree($list, ($level+1), $dept_id);
                    $do_list[] = $one_dept;
                }
            }
        }

        return $do_list;
    }

    /**
     *
     *
     */
    public function organizeDeptTreeLevel($list, $level=0, $parent_dept_id=0){
      
        $list = is_array($list)?$list:array();
        $do_list = array();
       
        foreach($list as $key=>$val){
            $one_dept   = $val;
            $dept_id    = $one_dept['ID'];
            $level_dept = $one_dept['DEPT_LEVEL'];
            $p_dept_id  = $one_dept['PAR_DEPT_ID'];
            $one_dept['node_type'] = 'dept';
            $one_dept['node_name'] = '';

            if($level==0){
                
                if($level==$level_dept){
                    $one_dept['people_employees'] = $this->gainDeptPeopleList($dept_id);
                    $one_dept['people_num']       = array_sum(array_column($one_dept['people_employees'],'PERCENT'))/100;
//                    $one_dept['people_top_list'] = $this->obtainHeadPeople($one_dept['people_employees']);
                    $one_dept['next_nodes'] = $this->organizeDeptTreeLevel($list, ($level+1), $dept_id);
                    $do_list[] = $one_dept;
                }
            }else{
                if($parent_dept_id==$p_dept_id){
                    $one_dept['people_employees'] = $this->gainDeptPeopleList($dept_id);
                    $one_dept['people_num']       = array_sum(array_column($one_dept['people_employees'],'PERCENT'))/100;
//                    $one_dept['people_top_list'] = $this->obtainHeadPeople($one_dept['people_employees']);
                    $one_dept['next_nodes'] = $this->organizeDeptTreeLevel($list, ($level+1), $dept_id);
                    $do_list[] = $one_dept;
                }
            }
        }

        return $do_list;
    }

    /**
     *
     *
     */
    public function organizeDeptTreePeopleNum($list){
        foreach($list as $key=>&$val){
            if($val['next_nodes']) {
                $val['next_nodes'] = $this->organizeDeptTreePeopleNum($val['next_nodes']);
                $val['people_num'] += array_sum(array_column($val['next_nodes'],'people_num'));
            }
        }
        return $list;
    }

    /**
     *   Get the level list of employees
     *
     */
    public function obtainLevelPeople($employees){
        $ret = array();
        foreach($employees as $k=>$v){
            $type_id = $v['employee_type_id'];
            $type_level = $v['employee_type_level'];
            if($type_id==1){
                $ret[$type_level][] = $v;
            }
        }
        return $ret;
    }

    /**
     *   Get the head of employees
     *
     */
    public function obtainHeadPeople($employees){
        $list = $this->obtainLevelPeople($employees);
        $list = isset($list[1])?$list[1]:null;
        $list = is_array($list)?$list:array();
        return $list;
    }

    /**
     *  Check and make data of dept
     *  @param  $data  array
     *  @param  $old_data  mix
     *  @return array
     *
     */
    public function formatDeptFields($arr, $old_data=array()){
        $ret_data = array();
        $ret_data['is_error'] = 0;
        $ret_data['msg'] = '';
        $ret_data['data'] = null;

        // mapping fields
        $mapArr = array(
            'DEPT_NM'       =>  '',
//            'DEPT_EN_NM'    =>  '',
//            'DEPT_CN_NM'    =>  '',
            'DEPT_SHORT_NM' =>  '',
            'TYPE'          =>  '',
            'STATUS'        =>  '',
            'DEPT_LEVEL'    =>  '',
            'REG_TIME'      =>  '',
            'PAR_DEPT_ID'   =>  '',
        );

        foreach($mapArr as $k=>$v){
            $v_map = $v?$v:$k;
            if(!isset($arr[$k])){
                $arr[$k] = isset($arr[$v_map])?$arr[$v_map]:'';
            }
        }

        $arr['STATUS'] = $arr['STATUS']?$arr['STATUS']:null;
        if($arr['STATUS']===null) unset($arr['STATUS']);
        $arr['DEPT_LEVEL'] = intval($arr['DEPT_LEVEL']);
        $arr['PAR_DEPT_ID'] = intval($arr['PAR_DEPT_ID']);
        if($arr['REG_TIME']){
            $tmp = strtotime($arr['REG_TIME']);
            $arr['REG_TIME'] = date('Y-m-d H:i:s',$tmp);
        }
        if(!$arr['REG_TIME']){
            if(empty($old_data)){
                $arr['REG_TIME'] = date('Y-m-d H:i:s');
            }
        }
        // check level
        $arr['DEPT_LEVEL'] = ($arr['PAR_DEPT_ID']>0)?1:0;
        if($arr['PAR_DEPT_ID']>0){
            $parent_data = $this->gainEasyOneDept($arr['PAR_DEPT_ID']);
            if(isset($parent_data['DEPT_LEVEL'])){
                $arr['DEPT_LEVEL'] = $parent_data['DEPT_LEVEL']+1;
            }
            if(empty($parent_data)){
                $ret_data['is_error'] = 1;
                $ret_data['msg'] = 'error parent data';
            }
        }
        // check max level
        if($ret_data['is_error']==0){
            if($arr['DEPT_LEVEL']>$this->max_level){
                $ret_data['is_error'] = 1;
                $ret_data['msg'] = 'error - this level can not add more';
            }
        }

        $ret_data['data'] = $arr;
        return $ret_data;
    }

    public function summaryPeopleInDept(){
        $ret = self::$stc_empl_dept_list;
        if($ret){
            return null;
        }
        $ret = array();
        $employee_query = M('hr_empl_dept', 'tb_')
            ->field('tb_hr_empl_dept.*')
            ->join('tb_hr_empl on tb_hr_empl.ID = tb_hr_empl_dept.ID2')
            ->join('tb_hr_jobs on tb_hr_jobs.ID = tb_hr_empl.JOB_ID');
        $leader_query = clone $employee_query;

        //部门负责人按选择顺序排序，非部门负责人按职级排序
        $employee_list = $employee_query->where(['tb_hr_empl_dept.TYPE'=>1])->order('IF (isnull(tb_hr_jobs.RANK),1,0),tb_hr_jobs.RANK')->select();
        $leader_list   = $leader_query->where(['tb_hr_empl_dept.TYPE'=>0])->order('tb_hr_empl_dept.SORT')->select();
        $employee_list = empty($employee_list) ? [] : $employee_list;
        $leader_list   = empty($leader_list) ? [] : $leader_list;
        $list = array_merge($leader_list, $employee_list);
        $list = is_array($list)?$list:array();
        foreach($list as $k=>$v){
            $ret[$v['ID1']][] = $v;
        }
        self::$stc_empl_dept_list = $ret;
        return null;
    }


    public function getDeptName($data)
    {
        foreach ($data as $k => $v) {
                $deptdata = $this->where("ID=".$v['DEPT_ID'])->find();
                $data[$k]['DEPT'] = $deptdata['DEPT_NM'];
            }
            return $data;
    }

    public function deptExists($id) {
        return $this->where(['ID'=>$id])->getField('id') ? true : false;
    }

    /**
     *  Department list
     *
     */
    public function dept_list_by_level($inputdata){
        //$level = I('level');
        $m_obj = D('TbHrDept');
        $order_data = array('DEPT_LEVEL'=>'asc','PAR_DEPT_ID'=>'asc','ID'=>'asc',);
        $where['_string'] = 'DELETED_BY IS NULL';
        //二级部门
        if ($inputdata['level'] == 1) {
            $where['DEPT_LEVEL'] = 0; //获取顶级部门
            $first_list = $m_obj->field ('*')->where ($where)
                ->order($order_data)->select();
            $where = ['PAR_DEPT_ID' => $first_list[0]['ID']];
        } else {
            //三级部门
            empty($inputdata['level']) or $where['DEPT_LEVEL'] = $inputdata['level'];
            empty($inputdata['p_id']) or $where['PAR_DEPT_ID'] = $inputdata['p_id'];
        }
        $list = $m_obj->field ('*')->where ($where)
            ->order($order_data)->select();
        return $list;
    }

    //根据用户获取部门列表
    public function dept_list_by_user($inputdata)
    {
        //默认当前用户
        $name = $inputdata['name'] ? $inputdata['name'] : $_SESSION['m_loginname'];
        $m = new TbHrDeptModel();
        $list = $m->getDeptListByEmpName($name);
        return $list;
    }




}

