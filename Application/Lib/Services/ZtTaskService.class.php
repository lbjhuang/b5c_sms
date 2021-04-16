<?php
/**
 * User: zouxuejun
 * Date: 20/3/2
 * Time: 16:52
 */

/**
 * 禅道数据基础服务类
 * Class ZtTaskServices
 */
class ZtTaskService extends Service
{
    private $doing_status = ['wait', 'doing', 'pause'];
    public $done_status = ['done', 'closed'];
    private $abnormal_status = ['cancel'];
    public $status_mapping = [
        'wait'   => '未开始',
        'doing'  => '进行中',
        'pause'  => '暂停',
        'done'   => '完成',
        'closed' => '关闭',
        'cancel' => '取消',
    ];
    private $user_group_arr = [
        'yangsu' => [
            'yangsu',
            'fuming',
            'ziling',
            'baisui',
            'baer',
            'jifan',
            'yezi',
        ],
        'fuming' => [
            'fuming',
            'tianrui',
            'shenmo',
            'Xuejun.Zou',
        ],
        'ziling' => [
            'ziling',
            'chilian',
            'yuanzong',
            'mochen',
            'feiyang',
            'likexiu',
        ],
        'jifan'  =>
            [
                'jifan',
                'shihe',
                'manyi',
            ],
        'yezi'   => [
            'yezi',
            'Rui.Xu',
            'ahua',
            'muzimo',
        ],
        'baer'   => [
            'baer',
            'ningxiang',
            'fly',
        ],
        'baisui' =>
            [
                'baisui',
                'leshan',
                'zijian',
                'zifeng',
                'changjingzhi',
                'hanshan',
                'mengyi',
            ],
    ];
    public $count_mapping = [
        'all_sum'      => '包含组员总计',
        'sum'          => '总计',
        'sum_consumed' => '总耗时',
        'sum_estimate' => '总预计工时',
    ];



    public function show($user = 'yangsu', $type = null)
    {
        if (! $user) {
            return [];
        }
        if ('Ben Huang' == $user) {
            $user = 'yangsu';
        }
        $db_arr = $this->getTask($user, $type);
        $counts = $this->getCounts($user, $db_arr);
        $data = $this->joinShowData($db_arr, $counts);
        return $data;
    }
    /**
     * @param      $user
     * @param null $type
     *
     * @return array
     * @throws \Exception
     */
    public function getTask($user, $type = null)
    {
        $take = 9999;
        $status = $this->doing_status;
        $user_arr = $this->getUserArr($user, $this->user_group_arr);
        $ZtTaskRepository = new ZtTaskRepository();
        $db_arr = $ZtTaskRepository->getTaskDb($take, $user_arr, $status, 'assignedTo');
        $db_done_arr = [];
        if (!empty($type)) {
            switch ($type) {
                case 'thisAll':
                    $act_date = date("Y-m-d H:i:s");
                    break;
                case 'weekAll':
                    $now = time();    //当时的时间戳
                    $number = date("w",$now);  //当时是周几
                    $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
                    $diff_day = $number - 1; //求到周一差几天
                    $act_date =  date("Y-m-d H:i:s",$now - ($diff_day * 60 * 60 * 24));
                    break;
                case 'all':
                    $act_date = '';
                    break;
            }
            $db_done_arr = $ZtTaskRepository->getDoneTaskDb($take, $user_arr, $this->done_status, 'finishedBy', $act_date);
        }
        $db_done_arr = empty($db_done_arr) ? [] : $db_done_arr;
        $db_arr = array_merge($db_arr, $db_done_arr);
        if (empty($db_arr)) {
            return $db_arr;
        }
        foreach ($db_arr as &$value) {
            $value['color'] = $this->stringToColorCode($value['assignedTo']);
            $value['status_color'] = null;
            if ('wait' != $value['status']) {
                $value['status_color'] = $this->stringToColorCode($value['status']);
            }
        }
        return $db_arr;
    }

    public function assemblyThisTaskPredictParam($data)
    {
        $temp_arr = [];
        if (is_object($data)) {
            $data = $data->toArray();
        }
        if (! is_array($data)) {
            return $temp_arr;
        }
        $reference_arr = [
            'estimate',
            'consumed',
            'left',
        ];
        foreach ($data as $key => $datum) {
            if (in_array($key, $reference_arr)) {
                $tmp_type = gettype($datum);
                switch ($tmp_type) {
                    case 'boolean':
                    case 'integer':
                    case 'double':
                        $temp_arr[] = $datum;
                        break;
                    case 'string':
                        $temp_arr[] = strlen($tmp_type);
                        break;
                    case 'array':
                    case 'object':
                    case 'resource':
                        $temp_arr[] = count($tmp_type);
                        break;
                    case 'NULL':
                    default:
                        $temp_arr[] = 0;

                }
            }
        }
        return $temp_arr;
    }

    /**
     * @param $str
     *
     * @return bool|string
     */
    public function stringToColorCode($str)
    {
        $code = dechex(crc32($str));
        $code = substr($code, 0, 6);
        return $code;
    }

    /**
     * @param       $user
     * @param array $data
     * @param array $counts
     *
     * @return array
     */
    public function getCounts($user, array $data, array $counts = [])
    {
        $user_arr = $this->getUserArr($user, $this->user_group_arr);
        foreach ($data as $datum) {
            if (empty($group_arr[$datum['user_by']]['sum'])) {
                $group_arr[$datum['user_by']]['sum_estimate'] = $group_arr[$datum['user_by']]['sum_consumed'] = $group_arr[$datum['user_by']]['sum'] = 0;
            }
            $group_arr[$datum['user_by']]['sum'] += $datum['user_by'] ? 1 : 0;
            $group_arr[$datum['user_by']]['sum_estimate'] += (float)$datum['estimate'] ?: 0;
            $group_arr[$datum['user_by']]['sum_consumed'] += (float)$datum['consumed'] ?: 0;
        }
        foreach ($user_arr as $value) {
            $counts[$value] = isset($group_arr[$value]) && !empty($group_arr[$value]) ? $group_arr[$value] : [];
        }
        return $counts;
    }

    /**
     * @param $user
     * @param $user_group_arr
     *
     * @return array|mixed
     */
    private function getUserArr($user, $user_group_arr)
    {
        if (in_array($user, array_keys($user_group_arr))) {
            if ('yangsu' == $user) {
                $user_arr = [];
                foreach ($user_group_arr as $value) {
                    $user_arr = array_merge($user_arr, $value);
                    $user_arr = array_unique($user_arr);
                }
            } else {
                $user_arr = $user_group_arr[$user];
            }
        } else {
            $user_arr = [$user];
        }
        return $user_arr;
    }

    public function joinShowData($data, $counts)
    {
        $group_data = [];
        $superior_by = $this->getSuperiorBy();
        foreach ($data as $datum) {
            $group_data[$datum['user_by']][] = $datum;
            if ($superior_by[$datum['user_by']] && $superior_by[$datum['user_by']] !== $datum['user_by']) {
                $group_data[$superior_by[$datum['user_by']]][] = $datum;
            }
        }
        unset($group_datum, $value);
        foreach ($counts as $key => $count) {
            $temp_all_sum = ! empty($group_data[$key]) ? count($group_data[$key]) : 0;
            if ($temp_all_sum != $count) {
                $count['all_sum'] = $temp_all_sum;
            }
            $res[$key]['count'] = $count;
            $res[$key]['data'] = ! empty($group_data[$key]) ? $group_data[$key] : [];
        }
        return $res;
    }

    private function getSuperiorBy()
    {
        $user_arr = $this->user_group_arr;
        foreach ($user_arr as $user_superior => $users) {
            foreach ($users as $subordinate_user) {
                $user_map[$subordinate_user] = $user_superior;
            }
        }
        return $user_map;
    }

    public function isDate($dateString)
    {
        return strtotime(date('Y-m-d H:i:s', strtotime($dateString))) === strtotime($dateString);
    }
}


