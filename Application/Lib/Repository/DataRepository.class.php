<?php

/**
 * Class DataRepository
 *
 *
 */
class DataRepository extends Repository
{

    /**
     * @var null
     */
    public static $PMS = null;
    /**
     * @var null
     */
    public static $db_name = null;

    protected $table = 'b5c_excel_task';

    //派单提醒任务表
    protected $remind_table = 'b5c_remind_task';

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
            self::$PMS->db(1, 'DATA_DB');
            self::$db_name = C('DATA_DB.DB_NAME');
        }
    }

    public function addOne($data){
        $db_arr = self::$PMS->table($this->table)->add($data);
        return $db_arr;
    }

    public function getList($where,$pages)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $count =$db_arr = self::$PMS->table($this->table)
            ->field('excel_name,type,status,created_by,created_at,updated_by,updated_at')
            ->where($where)
            ->count();
        $list =$db_arr = self::$PMS->table($this->table)
            ->field('excel_name,type,status,created_by,created_at,updated_by,updated_at')
            ->where($where)
            ->order('id desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        return [$list, $count];

    }

    public function getRemindTaskList($where)
    {
        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $list =$db_arr = self::$PMS->table($this->remind_table)
            //->field('excel_name,type,status,created_by,created_at,updated_by,updated_at')
            ->where($where)
            ->order('id desc')
            ->select();
        return $list;
    }

    public function addRemindTask($data)
    {
        $db_arr = self::$PMS->table($this->remind_table)->add($data);
        return $db_arr;
    }

    public function addRemindTaskAll($data)
    {
        $db_arr = self::$PMS->table($this->remind_table)->addAll($data);
        return $db_arr;
    }

    public function editRemindTask($where, $data)
    {
        $db_arr = self::$PMS->table($this->remind_table)->where($where)->save($data);
        return $db_arr;
    }
}
