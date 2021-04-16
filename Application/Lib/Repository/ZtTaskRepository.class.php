<?php

/**
 * Class ZtTaskRepository
 *
 * @package App\Repositories
 */
class ZtTaskRepository extends Repository
{

    /**
     * @var null
     */
    public static $PMS = null;
    /**
     * @var null
     */
    public static $db_name = null;

    protected $table = 'zt_task';
    /**
     * @param $take
     * @param $user_arr
     *
     * @return mixed
     */
    public function __construct($external_model = null)
    {
        if (empty(self::$PMS)) {
            $Model = new Model();
            self::$PMS = clone $Model;
            self::$PMS->db(1, 'ZT_DB');
            self::$db_name = C('ZT_DB.DB_NAME');
        }
    }

    public function getTaskDb($take, $user_arr, $status, $by_type = 'assignedTo')
    {
        $where['status'] = ['in',$status];
        $where[$by_type] = ['in',$user_arr];
        $db_arr = self::$PMS->table($this->table)
            ->field('*,assignedTo AS user_by')
            ->where($where)
            ->order('assignedTo desc,story desc')
            ->select();
        return $db_arr;
    }

    public function getDoneTaskDb($take, $user_arr, $status, $by_type = 'finishedBy', $act_date)
    {

        $where['status'] = ['in',$status];
        $where[$by_type] = ['in',$user_arr];
        if (!empty($act_date)){
            $where['finishedDate'] = array('EGT',$act_date);
        }
        $db_arr = self::$PMS->table($this->table)
            ->field('*,assignedTo AS user_by')
            ->where($where)
            ->group('story')
            ->order('assignedTo desc,id desc')
            ->select();
        return $db_arr;
    }

}
