<?php

/**
 * User: yangsu
 * Date: 17/12/6
 * Time: 10:37
 */
class LogsModel extends Model
{
    public static $file_path = '/opt/logs/logstash/erp/';
    public static $file_pre = 'log_';
    public static $project_name = 'sys';
    public static $type = 'info';
    public static $time_grain = false;
    public static $memory = false;
    public static $consume = false;
    public static $act_microtime = '';
    public static $hash_key = '';
    public static $uuid = '';
    public static $data_to_json = false;

    //统一日志记录表映射
    public static $table_map = [
        'area_config' => 'dp_tb_op_area_configuration_log',
        'accounting_subject' => 'dp_tb_accounting_subject_log', //会计科目log
    ];

    /**
     * @param      $msg
     * @param null $tips
     * @param null $project_name
     * @param null $type
     */
    public static function raise($msg, $tips = null, $project_name = null, $type = null)
    {
        if (empty($project_name)) $project_name = self::$project_name;
        ($type) ? self::$type = $type : '';
        $file_path = self::join_file_path($project_name);
        file_put_contents($file_path, self::join_msg($msg, $tips), FILE_APPEND);
    }

    /**
     * @param $args
     */
    public static function raise_args($args)
    {
        $data_list = [
            ['msg' => null],
            ['tips' => null],
            ['project_name' => null],
            ['type' => null],
        ];
        $arr_list = ['msg', 'tips', 'project_name', 'type'];
        foreach ($args as $k => $v) {
            $data_list[$arr_list[$k]] = $v;
        }
        if (!isLoadedMonoLogDriver()) {
            if (is_array($data_list['msg'])) {
                $data_list['msg'] = json_encode($data_list['msg'], JSON_UNESCAPED_UNICODE);
            }
            self::raise($data_list['msg'], $data_list['tips'], $data_list['project_name'], $data_list['type']);
        } else {
            //使用monolog-mongodb存储日志
            $context = [json_encode((array) $data_list['msg'])];
            \Snowair\Think\Logger::info($data_list['tips'], $context);
        }
    }

    /**
     * @param      $msg
     * @param      $tips
     * @param bool $n
     *
     * @return string
     */
    private static function join_msg($msg, $tips, $n = true)
    {
        $n = ($n) ? "\n" : null;
        $time = (self::$time_grain) ? '-' . microtime(true) : null;
        $tips = ($tips) ? ' - ' . $tips : null;
        if (self::$uuid) {
            $tips = self::$uuid;
        }
        $memory = (self::$memory) ? '-' . memory_get_usage() : null;
        if (self::$data_to_json) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        } elseif (is_object($msg)) {
            $msg = print_r(get_object_vars($msg), true);
        }
        (self::$act_microtime) ? ($msg .= ':' . (microtime(true) - self::$act_microtime)) : '';
        (self::$hash_key) ? ($tips .= '-' . self::$hash_key) : '';
        $ip = '-' . $_SERVER['SERVER_ADDR'];
        return strtoupper(self::$type) . ': ' . date(YmdHis) . $ip . $time . $memory . $tips . ': ' . $msg . $n;
    }

    /**
     * @param null $project_name
     *
     * @return string
     */
    private static function join_file_path($project_name = null)
    {
        if (!is_dir(self::$file_path)) mkdir(self::$file_path);
        return self::$file_path . self::$file_pre . $project_name . '-' . date('Ymd-H') . '.log';
    }

    public static function initConfig($project_name = null, $act_microtime = true)
    {
        if (empty($project_name)) {
            $project_name = self::getProjectName();
        }
        self::$project_name = $project_name;
        self::$time_grain = true;
        self::$act_microtime = microtime($act_microtime);
        self::$hash_key = crc32(LogsModel::$act_microtime);
        self::$data_to_json = true;
    }

    /**
     * @return mixed
     */
    private static function getProjectName()
    {
        return debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function'];
    }

    /**
     * 获取统一操作日志gp-dp
     * @param array $where
     * @param $limit
     * @param $table_map 表映射
     *
     * @return array
     * @throws Exception
     */
    public static function getOperationLogs($where = [], $limit, $table_map)
    {
        $table_name = self::$table_map[$table_map];
        if (!isset($table_name)) {
            throw  new \Exception(L('未指定表名'));
        }

        $query = M()->table('gs_dp.' . $table_name)->field('process_id,process_type,table_name', true)->where($where);
        $query_copy = clone $query;

        $pages['total'] = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];

        $query_copy->limit($limit[0], $limit[1]);
        $db_res = $query_copy->order('id desc')->select();

        return [
            'data' => $db_res,
            'pages' => $pages
        ];
    }
}