<?php
/**
 * User: yuanshixiao
 * Date: 2018/11/15
 * Time: 13:34
 */


class HrEmplDeptModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_empl_dept';

    const Type_ed_incharge = 0;
    const Type_ed_default = 1;
    const Type_ed_other = 2;
//    protected $_validate = array(
//        array('CD_VAL','require','必填:职位使用名称（中文）',1),
//        array('ETC','require','必填:职位名称（英文）',1),
//    );

    public function addRelation($id,$depts) {
        if(!$this->addRelationValidate($depts)) return false;
        $data_add = [];
        foreach ($depts as $v) {
            //查询是否原来是这个部门负责人
            $is_charge = $this->where(['ID2'=>$id,'ID1'=>$v['ID1'],'TYPE'=>self::Type_ed_incharge])->getField('ID');
            $data_add[] = [
                'ID1'       => $v['ID1'],
                'ID2'       => $id,
                'PERCENT'   => $v['PERCENT'],
                'TYPE'      => $is_charge ? self::Type_ed_incharge : self::Type_ed_default,
            ];
        }
        $res_del = $this->where(['ID2'=>$id,'type'=>1])->delete();
        if($res_del === false) {
            $this->error = '删除旧部门失败';
            return false;
        }
        $res_add = $this->addAll($data_add);
        if(!$res_add) {
            $this->error = '保存部门失败';
            return false;
        }
        return true;
    }

    public function addRelationValidate($depts) {
        $percent_all    = 0;
        $dept_ids       = [];
        $res            = true;
        foreach ($depts as $v) {
            if(!$v['ID1'] || !D('TbHrDept')->where(['ID'=>$v['ID1']])->getField('ID')) {
                $this->error = '部门不存在';
                $res = false;
                break;
            }
            if(in_array($v['ID1'],$dept_ids)) {
                $this->error = '部门重复';
                $res = false;
                break;
            }
            $dept_ids[]     = $v['ID1'];
            $percent_all    += $v['PERCENT'];
        }
        /*if($percent_all != 100) { // Q 672 移除部门占比判断逻辑
            $this->error    = '部门占比之和必须为100';
            $res            = false;
        }*/
        return $res;
    }

    public function setInCharge($dept_id,$empl_id) {
        $this->startTrans();
        $empl_ids   = explode(',',$empl_id);
        Logs([$empl_id, $empl_ids], __FUNCTION__, 'hr-sort');
        if(!$this->setInChargeValidate($dept_id,$empl_ids)) return false;
        $res_set_old = $this
            ->where(['ID1'=>$dept_id,'TYPE'=>0])
            ->save(['TYPE'=>1]);
        if($empl_id) {
            $res_set_new = $this
                ->where(['ID1'=>$dept_id,'ID2'=>['in',$empl_ids]])
                ->save(['TYPE'=>0]);
            $empl_count = count($empl_ids);
            Logs([$empl_ids, $empl_count, $res_set_new], __FUNCTION__, 'hr-sort');
            for ($i = 0; $i < $empl_count; $i++) {
                $sort_res = $this->where(['ID1'=>$dept_id, 'ID2'=>$empl_ids[$i]]) ->save(['SORT'=>$i]);
                if (false === $sort_res) {
                    @SentinelModel::addAbnormal('组织架构设置部门负责人', '失败', [$sort_res, $empl_ids,$i, $dept_id], 'general');
                }
            }
        }else {
            $res_set_new = true;
        }
        if($res_set_old === false || $res_set_new === false) {
            $this->error = '保存失败';
            $this->rollback();
            return false;
        }
        $this->commit();
        return true;
    }

    public function setInChargeValidate($dept_id,$empl_ids) {
        $res = true;
        foreach ($empl_ids as $v) {
            if($v && !$this->where(['ID1'=>$dept_id,'ID2'=>$v])->getField('ID')) {
                $this->error = '只能选择当前部门下的人员';
                $res = false;
            }
        }
        return $res;
    }

    public function getError() {
        return $this->error;
    }

    public function _before_insert(&$data,$options){
        parent::_before_insert($data,$options);
        $data['CREATE_USER_ID'] = $_SESSION['user_id'];
        $data['CREATE_TIME']    = date('Y-m-d H:i:s');
        $data['UPDATE_USER_ID'] = $_SESSION['user_id'];
        $data['UPDATE_TIME']    = date('Y-m-d H:i:s');
    }

    public function _before_update(&$data,$options){
        parent::_before_update($data,$options);
        $data['UPDATE_USER_ID'] = $_SESSION['user_id'];
        $data['UPDATE_TIME']    = date('Y-m-d H:i:s');
    }

}