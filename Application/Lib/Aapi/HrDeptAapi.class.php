<?php 
/**
 *  Api for zuzhijiagou
 *  author:      huaxin
 *
 */
class HrDeptAapi extends Action
{
    public $name_dept = 'TbHrDept';

    public function __construct()
    {
        $m_dept = D($this->name_dept);
        $one = $m_dept->field ('*')->where()->find();
        if(empty($one)){
            // default top level
            $status = $m_dept->setDefaultOne();
        }
    }


    public function dept_test(){
        $user='huaxin@gshopper.com';
        $title='test mail';
        $message='test mail '.date('Y-m-d H:i:s');
        $cc=null;
        $user='huaxin@gshopper.com';
        $email = new SMSEmail();
        $res = $email->sendEmail($user, $title, $message, $cc);
        var_dump($res);
    }


    /**
     *  Move data of in charge ( none 0 -> default 2 )
     *  e.g. :  index.php?m=api&a=hr_dept_move_default_in_charge
     *  usage:  
     *
     */
    public function dept_move_default_in_charge(){
        $m_empl_dept = M('hr_empl_dept', 'tb_');
        $list = $m_empl_dept->field ('*')
                ->where(array('TYPE'=>TbHrDeptModel::Type_ed_incharge,'TYPE_LEVEL'=>TbHrDeptModel::Type_level_0,))
                ->order(array('ID'=>'desc',))
                ->select();
        $count_all = count($list);
        $count_del = 0;
        $count_edit = 0;

        foreach($list as $key=>$val){
            $empl_id = $val['ID2'];
            $dept_id = $val['ID1'];
            // check exist
            $one = $m_empl_dept->where(
                array(
                    'ID2'=>$empl_id,
                    'ID1'=>$dept_id,
                    'TYPE'=>TbHrDeptModel::Type_ed_incharge,
                    'TYPE_LEVEL'=>TbHrDeptModel::Type_level_2,
                )
            )->find();

            // del or edit
            if($one){
                // del - del old data
                $where_data = array(
                    'ID'=>$val['ID'],
                );
                $status = DataMain::dbDelByNameData($m_empl_dept,$where_data);
                ++$count_del;
            }else{
                // edit - update 0 to 2
                $edit_data = array(
                    'TYPE_LEVEL'=>TbHrDeptModel::Type_level_2,
                );
                $status = $m_empl_dept->where(array('ID'=>$val['ID']))->data($edit_data)->save();
                ++$count_edit;
            }
            // var_dump($val,$one,$status); die();
            // die();
        }
        $ret = array();
        $ret['count_all'] = $count_all;
        $ret['count_del'] = $count_del;
        $ret['count_edit'] = $count_edit;
        return $ret;
    }

    /**
     *  Renew people's dept
     *  Api
     *  e.g.:  /index.php?m=api&a=hr_dept_renew_empl_dept&uid=***
     *
     */
    public function dept_renew_empl_dept(){
        $uid = isset($_REQUEST['uid'])?$_REQUEST['uid']:null;
        $uid = intval($uid);
        $one_info = D('TbHrCard')->findOneByEmplId($uid);
        $tmpData = D('TbHrEmpl')->fmtDeptByEmpl($one_info);
        $tmp_dept_id = null;
        $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_GROUP_id'])?null:$tmpData['format_DEPT_GROUP_id']);
        $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_NAME_id'])?null:$tmpData['format_DEPT_NAME_id']);
        // keep
        $tmp = D('TbHrDept')->addEmployeeToDepartment($uid,$tmp_dept_id);
        $data['uid'] = $uid;
        $data['dept_id'] = $tmp_dept_id;
        $data['status'] = $tmp;
        $outputs = array();
        $outputs['code'] = 200;
        $outputs['data'] = $data;
        echo json_encode($outputs);
        die();
    }

    /**
     *  Renew people's dept by max
     *  
     *  e.g.:  /index.php?m=api&a=hr_dept_renew_empl_dept_old&maxid=***
     *
     */
    public function dept_renew_empl_dept_old(){
        $outputs = array();
        $maxid = isset($_REQUEST['maxid'])?$_REQUEST['maxid']:null;
        $maxid = intval($maxid);
        $max = 10000;
        $i = 0;
        if($maxid>0){
            while($maxid>0){
                $uid = intval($maxid);
                $one_info = D('TbHrCard')->findOneByEmplId($uid);
                $tmpData = D('TbHrEmpl')->fmtDeptByEmpl($one_info);
                $tmp_dept_id = null;
                $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_GROUP_id'])?null:$tmpData['format_DEPT_GROUP_id']);
                $tmp_dept_id = $tmp_dept_id?$tmp_dept_id:(empty($tmpData['format_DEPT_NAME_id'])?null:$tmpData['format_DEPT_NAME_id']);
                // keep
                $tmp = D('TbHrDept')->addEmployeeToDepartment($uid,$tmp_dept_id);
                $data['uid'] = $uid;
                $data['dept_id'] = $tmp_dept_id;
                $data['status'] = $tmp;

                $outputs['data'][] = $data;

                $maxid--;
                $outputs['i'] = $i;
                // check max
                ++$i;
                if($i>$max){
                    break(1);
                }
            }
        }
        return ($outputs);
    }

    /**
     *  Department list
     *
     */
    public function dept_list_all(){
        $level = I('level');
        $m_obj = D($this->name_dept);
        $order_data = array('DEPT_LEVEL'=>'asc','PAR_DEPT_ID'=>'asc','ID'=>'asc',);
        $list = $m_obj->field ('*')
                ->where ()
                ->order($order_data)
                ->select();
        return $list;
    }

    /**
     *  Department list
     *
     */
    public function dept_list_by_level(){
        $inputdata = file_get_contents('php://input');
        $inputdata = @json_decode($inputdata,true);
        $list = D($this->name_dept)->dept_list_by_level($inputdata);
        return $list;
    }

    /**
     *  Department list
     *
     */
    public function dept_list_by_user(){
        $inputdata = file_get_contents('php://input');
        $inputdata = @json_decode($inputdata,true);
        $list = D($this->name_dept)->dept_list_by_user($inputdata);
        return $list;
    }

    /**
     *  Department level list
     *
     */
    public function dept_list_level(){
        D($this->name_dept)->summaryPeopleInDept();
        $m_obj = D($this->name_dept);
        $order_data = array('DEPT_LEVEL'=>'asc','PAR_DEPT_ID'=>'asc','ID'=>'asc',);
        $list = $m_obj->field ('*')
                ->where ()
                ->order($order_data)
                ->select();
        // match and sort list
        $level = 0;
        $list = $m_obj->organizeDeptTree($list, $level);
        return $list;
    }

    public function dept_list_level_all(){
        D($this->name_dept)->summaryPeopleInDept();
        $m_obj = D($this->name_dept);
        $order_data = array('DEPT_LEVEL'=>'asc','PAR_DEPT_ID'=>'asc','ID'=>'asc',);
        $list = $m_obj->field ('*')
                ->where (['DELETED_BY IS NULL'])
                ->order($order_data)
                ->select();
        // match and sort list
        $level = 0;
        $list = $m_obj->organizeDeptTreeLevel($list, $level);
        $list = $m_obj->organizeDeptTreePeopleNum($list);
        return $list;
    }

    /**
     *  Gain on department
     *
     */
    public function dept_get_one(){
        $id = I('id');
        $data = D($this->name_dept)->gainOneDept($id);
        return $data;
    }

    /**
     *  Search employees
     *
     */
//    public function dept_search_people(){
//        $searchdata = isset($_REQUEST['searchdata'])?$_REQUEST['searchdata']:null;
//        $list = D('TbHrCard')->search_empl_by_key($searchdata);
//        return $list;
//    }

    public function dept_search_people() {
        $search_data    = $_REQUEST['searchdata'];
        $dept_id        = $_REQUEST['dept_id'];
        if($dept_id) {
            $people         = D('TbHrCard')->searchDepartmentPeople($search_data,$dept_id);
        }else {
            $people         = D('TbHrCard')->searchPeople($search_data);
        }
        return $people;
    }

    /**
     *  Search department
     *
     */
    public function dept_search_department(){
        $searchdata = isset($_REQUEST['searchdata'])?$_REQUEST['searchdata']:null;
        $m_dept = D($this->name_dept);
        $list = $m_dept->search_dept_by_key($searchdata);
        return $list;
    }

    /**
     *  Department list
     *
     */
    public function dept_list(){
        $m_dept = D($this->name_dept);
        $list = $m_dept->dept_list();
        return $list;
    }

    /**
     *  Set up the person in charge
     *
     */
//    public function dept_set_person_in_charge(){
//        $empl_id = isset($_REQUEST['empl_id'])?$_REQUEST['empl_id']:null;
//        $dept_id = isset($_REQUEST['dept_id'])?$_REQUEST['dept_id']:null;
//
//        $outputs = array();
//
//        if(empty($dept_id)){
//            $outputs['code'] = 500;
//            $outputs['msg'] = 'empty info';
//            return $outputs;
//        }
//        //check exists
//        $d_dept = D($this->name_dept)->gainSimpleOneDept($dept_id);
//        if(empty($d_dept)){
//            $outputs['code'] = 500;
//            $outputs['msg'] = 'not exists';
//            return $outputs;
//        }
//        $empl_ids = explode(',',$empl_id);
//        $empl_ids = ZFun::arrNoEmptyMix($empl_ids);
//        if($empl_ids){
//            foreach($empl_ids as $key=>$val_id){
//                //check exists
//                $d_empl = D('TbHrCard')->findOneByEmplId($val_id);
//                if(empty($d_empl)){
//                    $outputs['code'] = 500;
//                    $outputs['msg'] = 'employee not exists '.$val_id;
//                    return $outputs;
//                }
//            }
//        }
//
//        $status = D($this->name_dept)->relateMoreInCharge($dept_id,$empl_ids);
//        // mark the type level
//        $type_level = isset($_REQUEST['type_level'])?$_REQUEST['type_level']:null;
//        $type_levels = explode(',',$type_level);
//        $status = D($this->name_dept)->renewTypeLevel($dept_id,$empl_ids,$type_levels);
//
//        $outputs = D($this->name_dept)->gainDeptPeopleInCharge($dept_id);
//        return $outputs;
//    }

    public function dept_set_person_in_charge()
    {
        $empl_id    = isset($_REQUEST['empl_id']) ? $_REQUEST['empl_id'] : null;
        $dept_id    = isset($_REQUEST['dept_id']) ? $_REQUEST['dept_id'] : null;
        $type_level = isset($_REQUEST['type_level']) ? $_REQUEST['type_level'] : null;
        if(empty($dept_id)) {
            $outputs['code'] = 500;
            $outputs['msg'] = 'empty info';
            return $outputs;
        }
        $model = D('Hr/HrEmplDept');
        $res = $model->setInCharge($dept_id,$empl_id);
        if($res) {
            return [];
        }else {
            $outputs['code']    = 500;
            $outputs['msg']     = $model->getError();
            return $outputs;
        }
    }

    /**
     *  Add a person to the department
     *
     */
    public function dept_add_person_for_department(){
        $outputs = array();
        $empl_id = isset($_REQUEST['empl_id'])?$_REQUEST['empl_id']:null;
        $dept_id = isset($_REQUEST['dept_id'])?$_REQUEST['dept_id']:null;
        if(empty($empl_id) or empty($dept_id)){
            $outputs['code'] = 500;
            $outputs['msg'] = 'empty info';
            return $outputs;
        }
        //check exists
        $d_empl = D('TbHrCard')->findOneByEmplId($empl_id);
        $d_dept = D($this->name_dept)->gainSimpleOneDept($dept_id);
        if(empty($d_empl) or empty($d_dept)){
            $outputs['code'] = 500;
            $outputs['msg'] = 'not exists';
            return $outputs;
        }

        $status = D($this->name_dept)->addEmployeeToDepartment($empl_id,$dept_id);
        return $outputs;
    }

    /**
     *  Delete department
     *
     */
    public function dept_delete(){
        $dept_id = isset($_REQUEST['dept_id'])?$_REQUEST['dept_id']:null;
        // check exists
        $d_dept = D($this->name_dept)->gainOneDept($dept_id);
        if(empty($d_dept)){
            $outputs['code'] = 500;
            $outputs['msg'] = '部门不存在';
            return $outputs;
        }
        // check relations
        if($d_dept['staff_count']>0){
            $outputs['code'] = 500;
            $outputs['msg'] = '该部门当前还有成员,请优先更换成员部门！';
            return $outputs;
        }
        //过滤掉逻辑删除的部门
        foreach ($d_dept['child_branch'] as $key => $item) {
            if (!empty($item['DELETED_BY'])) {
                unset($d_dept['child_branch'][$key]);
            }
        }
        foreach ($d_dept['child_department'] as $key => $item) {
            if (!empty($item['DELETED_BY'])) {
                unset($d_dept['child_department'][$key]);
            }
        }
        if(empty($d_dept['child_branch']) and empty($d_dept['child_department'])){
        }else{
            $outputs['code'] = 500;
            $outputs['msg'] = '该部门当前还有下级部门，不能删除！';
            return $outputs;
        }
        // del
//        $status = D($this->name_dept)->where(array('ID'=>$dept_id))->delete();
        $status = D($this->name_dept)
            ->where(array('ID'=>$dept_id))
            ->save([
                'DELETED_BY' => DataModel::userNamePinyin(),
                'DELETED_AT' => dateTime()
            ]);
        if(!$status){
            $outputs['code'] = 500;
            $outputs['msg'] = '删除失败';
            return $outputs;
        }
        return array();
    }

    /**
     *  Add department
     *
     */
    public function dept_add_one(){
        $inputdata = file_get_contents('php://input');
        $inputdata = @json_decode($inputdata,true);
        $model = D($this->name_dept);
        $new_data = $model->formatDeptFields($inputdata);
        if($new_data['is_error']){
            $outputs['code'] = 500;
            $outputs['msg'] = $new_data['msg'];
            return $outputs;
        }
        $new_data = $new_data['data'];
        // check
        // test data // {"DEPT_NM":"a","DEPT_EN_NM":"b","DEPT_CN_NM":"c","TYPE":"1","PAR_DEPT_ID":"33"}
        // $test_data = array('DEPT_NM'=>'a','DEPT_EN_NM'=>'b','DEPT_CN_NM'=>'c','TYPE'=>'1','PAR_DEPT_ID'=>'33',);
        // echo json_encode($test_data); die();
        $outputs = array();
        if($ret = $model->create($new_data)) {
            if($isok = $model->add($model->data(''))) {
                $outputs = array(
                    'lastInsertId' =>$isok,
                );
            }else{
                $outputs['code'] = 500;
                $outputs['msg'] = 'Error'.$model->getError();
                return $outputs;
            }
        }else{
            $outputs['code'] = 500;
            $outputs['msg'] = 'Error'.$model->getError();
            return $outputs;
        }
        return $outputs;
    }

    /**
     *  Edit department
     *  need edit ID
     *
     */
    public function dept_edit_one(){
        $inputdata = file_get_contents('php://input');
        $inputdata = @json_decode($inputdata,true);
        $model = D($this->name_dept);
        $dept_id = isset($inputdata['ID'])?$inputdata['ID']:null;
        // check exists
        $d_dept = D($this->name_dept)->gainEasyOneDept($dept_id);
        if(empty($d_dept)){
            $outputs['code'] = 500;
            $outputs['msg'] = 'not exists department';
            return $outputs;
        }

        $edit_data = $model->formatDeptFields($inputdata,$d_dept);
        $edit_data = $edit_data['data'];
        if(empty($edit_data['ID'])){
            $outputs['code'] = 500;
            $outputs['msg'] = 'Empty ID';
            return $outputs;
        }

        $outputs = array();
        if($ret = $model->create($edit_data)) {
            if($isok = $model->save($model->data(''))) {
                $outputs = array(
                    'updateRows' =>$isok,
                );
            }else{
                $outputs['code'] = 500;
                $outputs['msg'] = 'Error'.$model->getError();
                return $outputs;
            }
        }else{
            $outputs['code'] = 500;
            $outputs['msg'] = 'Error'.$model->getError();
            return $outputs;
        }
        return $outputs;
    }

    /**
     *  Select data about department
     *  
     *
     */
    public function dept_choice(){
        $outputs = array();
        $dept_type = (new TbMsCmnCdModel())->getCdKeyY(TbMsCmnCdModel::$department_type_cd_pre);
        $outputs['dept_type'] = $this->arrKeyVal2NewArr($dept_type);
        $dept_status = TbHrDeptModel::getStatusForDept();
        $outputs['dept_status'] = $this->arrKeyVal2NewArr($dept_status);
        $dept_incharge = TbHrDeptModel::getTypeForEDRelation();
        $outputs['dept_incharge'] = $this->arrKeyVal2NewArr($dept_incharge);

        return $outputs;
    }

    /**
     *  Set new array with key and val
     *
     */
    private function arrKeyVal2NewArr($arr){
        $ret = array();
        foreach($arr as $key=>$val){
            $ret[] = array(
                'key'=>$key,
                'val'=>$val,
            );
        }
        return $ret;
    }

}



