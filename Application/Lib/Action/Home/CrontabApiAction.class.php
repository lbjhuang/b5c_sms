<?php
/**
 * User: yangsu
 * Date: 18/11/6
 * Time: 18:01
 */


class CrontabApiAction extends Action
{


    /**
     *读取正在跑定时任务的锁
     */
    public function index()
    {
        $lockHashKey = RedisModel::hkeys('crontabLockHash');
        $data = [];
        foreach ($lockHashKey as $value){
            $tmp = RedisModel::get_key($value);
            if($tmp){
                $data[] = $value;
            }
        }
        echo json_encode(['code' => 200, 'msg' => 'success', 'data' => $data]);
    }


}