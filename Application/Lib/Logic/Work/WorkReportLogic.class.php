<?php
/**
 * User: yuanshixiao
 * Date: 2018/6/14
 * Time: 10:44
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class WorkReportLogic extends BaseLogic
{
    public function saveWeeklyReport($data) {
        $model      = D('WorkReport');
        $model_i    = D('InsightWorkReport');
        $year       = date('Y');
		$temp_m = date('m');
		$temp_d = date('d');
		if(1 == $temp_m && $temp_d < 4){
//			$year  -= 1 ;
		}
        $week       = $data['week'];
        $save['edit_time']  = date('Y-m-d H:i:s');
        if(date('W',strtotime($year.'-01-01')) != 1) {
            $current_week = date('W')+1;
        }else {
            $current_week = date('W');
        }
        //只能写当周或之前的周，周一不能写本周
        if($week > $current_week) {
            //$this->error = '只能写当周或之前的周报';
            //return false;
			
        }elseif(date('w') == 1 && $week == $current_week) {
            $this->error = '周一不能写本周周报';
            return false;
        }
        //当前周为第一周，且当年第一天不是第一周，则为跨年周，既是去年最后一周，又是今年第一周
        $save['year']       = $year;
        $save['year_extra'] = null;
        $save['week']       = $week;
        $save['week_extra'] = null;
//        if($week == 1 && date('W',strtotime($year.'-01-01')) != 1) {
//            $save['year']       = $year-1;
//            $save['year_extra'] = $year;
//            $save['week_extra'] = $week;
//            //去年第一天不是周一，统计周加一
//            if(date('W',strtotime(($year-1).'-01-01')) != 1) {
//                $save['week'] = date('W',strtotime($year.'-01-01'))+1;
//            }else {
//                $save['week'] = date('W',strtotime($year.'-01-01'));
//            }
//        }elseif($week != 1 && $week == date('W',strtotime(($year+1).'-01-01'))) {
//            $save['year']       = $year;
//            $save['year_extra'] = $year+1;
//            $save['week']       = $week;
//            $save['week_extra'] = 1;
//        }else {
//            $save['year']       = $year;
//            $save['year_extra'] = null;
//            $save['week']       = $week;
//            $save['week_extra'] = null;
//        }
        $save['content_html']   = htmlspecialchars_decode($data['content_html']);
        $save['content_text']   = strip_tags(htmlspecialchars_decode($data['content_html']));
        $save['creator']        = $_SESSION['m_loginname'];
        $save['report_type']    = WorkReportModel::$report_type['weekly_report'];
        $model->startTrans();
        if($data['id']) {
            $exist_where        = ['creator'=>$_SESSION['m_loginname'],'year'=>$year,'week'=>$week,'id'=>['neq',$data['id']]];
            $exist              = $model->where($exist_where)->getField('id');
            $exist_extra_where  = ['creator'=>$_SESSION['m_loginname'],'year_extra'=>$year,'week_extra'=>$week,'id'=>['neq',$data['id']]];
            $exist_extra        = $model->where($exist_extra_where)->getField('id');
            if($exist || $exist_extra) {
                $model->rollback();
                $this->error = "系统检测到您{$year}年{$week}周已经提交过周报，提交失败";
                return false;
            }
            unset($save['year']);
            $res = $model->where(['id'=>$data['id']])->save($save);
            if($res === false) {
                $model->rollback();
                $this->error = '保存失败';
                return false;
            }
            $res_i  = $model_i->where(['id'=>$data['id']])->save($save);
            if($res_i === false) {
                $model->rollback();
                $this->error = '保存失败';
                return false;
            }
        }else {
            $work_num            = M('hr_card','tb_')->where(['ERP_ACT'=>$_SESSION['m_loginname']])->getField('WORK_NUM');
            $save['report_code'] = $work_num.date('Ymd');
            $exist_where        = ['creator'=>$_SESSION['m_loginname'],'year'=>$year,'week'=>$week];
            $exist              = $model->where($exist_where)->getField('id');
            $exist_extra_where  = ['creator'=>$_SESSION['m_loginname'],'year_extra'=>$year,'week_extra'=>$week];
            $exist_extra        = $model->where($exist_extra_where)->getField('id');
            if($exist) {
                $model->rollback();
                $this->error = "系统检测到您{$year}年{$week}周已经提交过周报，提交失败";
                return false;
            }
            $res   = $model->add($save);
            if($res === false) {
                $model->rollback();
                $this->error = '保存失败';
                return false;
            }
            $save['id'] = $res;
            $res_i      = $model_i->add($save);
            if($res_i === false) {
                $model->rollback();
                $this->error = 'insight保存失败';
                return false;
            }
        }
        $model->commit();
        return true;
    }
	
	public function deleteWork(){
		$id = I('id');
		$model = new Model();
		$where['id'] =  $id;
		if($id && $where){
		return $model->table('tb_work_report')
		->where($where)	
		->delete();
		}
		
	}

}