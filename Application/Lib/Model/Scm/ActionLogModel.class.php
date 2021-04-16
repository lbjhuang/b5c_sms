<?php
/**
 * User: due
 * Date: 2018/4/17
 * Time: 13:15
 */

class ActionLogModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_action_log';

    /**获取查询条件
     * @param $param
     * @return array
     */
    public function getWhere($param)
    {
        $where = ['demand_id' => $param['demand_id'], 'show' => 1];
        !empty($param['begin_time']) ? $where[] = ['time' => ['EGT', $param['begin_time']]] : null;
        !empty($param['end_time']) ? $where[] = ['time' => ['ELT', $param['end_time'] . ' 23:59:59']] : null;
        !empty($param['user']) ? $where['user'] = $param['user'] : null;
        !empty($param['info']) ? $where['info'] = $param['info'] : null;
        !empty($param['step']) ? $where['step'] = $param['step'] : null;
        return $where;
    }

    /**获取记录条数
     * @param $param
     * @return mixed
     */
    public function logCount($param)
    {
        $where = $this->getWhere($param);
        return $this->where($where)->count();
    }

    public function logList($param, $limit = '')
    {
        $where = $this->getWhere($param);
        $list = $this->alias('t')
            ->field("t.id,t.time,t.user,t.detail_type,t.info,if(detail_type='','0','1') as has_detail")
//            ->join('left join tb_ms_cmn_cd c on c.CD=t.old_step')
//            ->join('left join tb_ms_cmn_cd d on d.CD=t.step')
            ->where($where)
            ->limit($limit)
            ->order('id desc')
            ->select();
        return $list;
    }
}