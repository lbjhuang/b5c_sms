<?php
/**
 * User: yuanshixiao
 * Date: 2018/11/15
 * Time: 13:34
 */


class HrJobsModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_jobs';

    protected $_validate = array(
        array('CD_VAL','require','必填:职位使用名称（中文）',1),
        array('ETC','require','必填:职位名称（英文）',1),
    );

    public function jobsCount($params) {
        return $this->count();
    }

    public function jobsList($params,$limit) {
        return $this
            ->field('t.*,count(a.ID) empl_num')
            ->alias('t')
            ->join('left join tb_hr_empl a on a.JOB_ID=t.ID and a.STATUS in ("在职","兼职")')
            ->group('t.ID')
            ->limit($limit)
            ->select();
    }

    public function jobsAdd($params) {
        if(!$this->create($params)) return false;
        $res = $this->add();
        if($res === false) {
            $this->error = '保存失败';
            return false;
        }
        return true;
    }

    public function jobsEdit($params) {
        if(!$params['ID'] || !$this->jobsExists($params['ID'])) {
            $this->error = '职位不存在';
            return false;
        }
        if(!$this->create($params)) return false;
        $res = $this->save();
        if($res === false) {
            $this->error = '保存失败';
            return false;
        }
        return true;
    }

    public function jobsDel($params) {
        if(!$params['ID'] || !$this->jobsExists($params['ID'])) {
            $this->error = '职位不存在';
            return false;
        }
        $has_empl = D('TbHrEmpl')->where(['JOB_ID'=>$params['ID']])->getField('id');
        if($has_empl) {
            $this->error = '有人员的职位不能删除';
            return false;
        }
        $res = $this->where(['ID'=>$params['ID']])->delete();
        if($res === false) {
            $this->error = '保存失败';
            return false;
        }
        return true;
    }

    public function jobsExists($id) {
        return $this->where(['ID'=>$id])->getField('ID');
    }

    public function _before_insert(&$data,$options){
        parent::_before_insert($data,$options);
        $data['CREATE_USER_ID'] = $_SESSION['user_id'];
        $data['CREATE_TIME']    = date('Y-m-d H:i:s');
        $data['UPDATE_USER_ID'] = $_SESSION['user_id'];
        $data['UPDATE_TIME']    = date('Y-m-d H:i:s');
        $data['USE_YN']         = 'N';
    }

    public function _before_update(&$data,$options){
        parent::_before_update($data,$options);
        $data['UPDATE_USER_ID'] = $_SESSION['user_id'];
        $data['UPDATE_TIME']    = date('Y-m-d H:i:s');
    }
}