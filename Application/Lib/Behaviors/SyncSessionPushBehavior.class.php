<?php

/**
 * User: yangsu
 * Date: 18/1/8
 * Time: 10:10
 */
class SyncSessionPushBehavior extends Behavior
{
    // public $out_time = 28800;
    public $out_time = 604800;


    public function run(&$params)
    {
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
            &&  ('local' != $_ENV["NOW_STATUS"] && !is_file(LOCAL_SESSION))) {
            $this->sessionSet();
        }
    }

    public function sessionSet()
    {
        if (count($_SESSION)) {
            $session_json = json_encode($_SESSION, JSON_UNESCAPED_UNICODE);
            $r_client = RedisModel::$client;
            RedisModel::set_key(session_id(), $session_json, $r_client, $this->out_time);
        }
    }

}