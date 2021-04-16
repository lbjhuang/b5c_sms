<?php
/**
 * User: yuanshixiao
 * Date: 2018/6/13
 * Time: 14:58
 */

class InsightWorkReportModel extends BaseModel
{
    protected $connection = 'insight_db_config';
    protected $trueTableName = 'dw_fact.fact_tb_work_report';
    public static $report_type = [
        'weekly_report' => 'N002360100',
    ];

    public function reportList($param,$limit = '') {
        $where = $this->getWhere($param);
        $list = $this
            ->field('id,report_code,year,year_extra,week,week_extra,content_text content,create_time,edit_time')
            ->where($where)
            ->order('id desc')
            ->limit($limit)
            ->select();
        return $list;
    }

    public function reportCount($param) {
        $where = $this->getWhere($param);
        return $this
            ->where($where)
            ->count();
    }

    public function getWhere($param) {
        $where = [];
        if($param['min_create_time'] && $param['max_create_time']) {
            $where['create_time'] = ['between',[$param['min_create_time'],$param['max_create_time']]];
        }elseif($param['min_create_time']) {
            $where['create_time'] = ['egt',$param['min_create_time']];
        }elseif($param['max_create_time']) {
            $where['create_time'] = ['elt',$param['max_create_time']];
        }
        $param['year'] ? $where['year'] = $param['year'] :'';
        if($param['week']) {
            $map['week']        = $param['week'];
            $map['week_extra']  = $param['week'];
            $map['_logic']      = 'or';
            $where['_complex']  = $map;
        }
        $param['content'] ? $where['content_text'] = ['like','%'.$param['content'].'%'] :'';
        $where['creator'] = $_SESSION['m_loginname'];
        $where['report_type'] = self::$report_type['weekly_report'];
        return $where;
    }
}