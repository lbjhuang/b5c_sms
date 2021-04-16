<?php

/**
 * User: yangsu
 * Date: 19/12/16
 * Time: 11:31
 */

/**
 * Class B2bService
 */
class PullOrderQueueService extends Service
{
    /**
     * @param $data
     * @param int $sheet
     *
     * @return array
     */
    public function pushShopOneDayQueue($data, $sheet = 12)
    {
        $time_add = 86400 / $sheet;
        $now_time = time();
        $e_time = strtotime($data['endDate']);
        //获取总分片数
        $sheet = ceil((strtotime($data['endDate']) - strtotime($data['startDate']))/7200);
        //放开截止时间限制 时差原因
        //if ($now_time < $e_time) $e_time = $now_time;
        for ($i = 0; $i < $sheet; $i++) {
            if (empty($act_time)) {
                $act_time = strtotime($data['startDate']);
            }
            if ($sheet - 1 == $i) {
                $time_add -= 1;
            }
            $end_time = (int)($act_time + $time_add);
            /*if ($now_time < $end_time) {
                $end_time = $now_time;
            }*/

            if ($end_time > $e_time) {
                $end_time = $e_time;
            }
            $params = [
                'stores' => $data['stores'],
                'startDate' => date('YmdHis', $act_time),
                'endDate' => date('YmdHis', $end_time),
            ];
            $params['api_response'] = ApiModel::pullSingle($params);
            $pull_single[] = $params;
            $act_time = $end_time;
            //拉单分片的起始时间超过截止时间则停止拉单
            if ($end_time >= $e_time) {
                break;
            }
            unset($params);
        }
        return $pull_single;
    }

    /**
     *
     * 定时任务执行，不要轻易改动
     *
     * @param $data
     * @param int $sheet
     *
     * @return array
     */
    public function pushTimingShopOneDayQueue($data, $sheet = 12)
    {
        $time_add = 86400 / $sheet;
        $now_time = time();
        for ($i = 0; $i < $sheet; $i++) {
            if (empty($act_time)) {
                $act_time = strtotime($data['startDate']);
            }
            if ($sheet - 1 == $i) {
                $time_add -= 1;
            }
            $end_time = (int)($act_time + $time_add);
            if ($now_time < $end_time) {
                $end_time = $now_time;
            }
            $params = [
                'stores' => $data['stores'],
                'startDate' => date('YmdHis', $act_time),
                'endDate' => date('YmdHis', $end_time),
            ];
            $params['api_response'] = ApiModel::pullSingle($params);
            $pull_single[] = $params;
            $act_time = $end_time;
            unset($params);
        }
        return $pull_single;
    }
}