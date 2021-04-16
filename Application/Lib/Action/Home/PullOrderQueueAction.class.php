<?php
/**
 * User: yangsu
 * Date: 2019/12/13
 * Time: 16:05
 */

class PullOrderQueueAction extends BaseAction
{
    public function _initialize()
    {
        header('Content-Type: application/json;charset=utf-8');
//        $this->service = new PullOrderQueueService();
    }

    public function makeUpYesterday()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__ . ' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $yesterday = date('Ymd', strtotime('Yesterday'));
            $res['all'] = $this->makeUpAllOpen($yesterday);
            #业务end
            Logs($res, __FUNCTION__, __CLASS__);
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
            $end_time =  $this->getMsectime();
            Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
            $this->jsonOut($res);
            
            
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
       
    }

    /**
     * 每天晚早上九点-Ebay-店铺-[东一区至东八区] 补拉前一天订单
     * @return string
     */
    public function makeUpYesterdayNineAmEbay()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__ . ' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $yesterday = date('Ymd', strtotime('Yesterday'));
            $res['all'] = $this->makeUpNineAmEbay($yesterday);
            #业务end
            Logs($res, __FUNCTION__, __CLASS__);
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
            $end_time =  $this->getMsectime();
            Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
            $this->jsonOut($res);


        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }

    }

    public function makeUpNineAmEbay($start_date = null, $default_sheet = 8){
        if (empty($start_date) || !('1575129600' < strtotime($start_date))) {
            return [];
        }
        // [东一区至东八区]
        $default_timezone_cd_data = array(
            'N003370002',
            'N003370003',
            'N003370004',
            'N003370005',
            'N003370006',
            'N003370007',
            'N003370008',
            'N003370009',
            'N003370010',
        );

        $start_date = date('Ymd', strtotime($start_date)) . '000000';
        $Model = new Model(null, null, 'ONLINE_DB');;
        $where['DELETE_FLAG'] = '0';
        $where['STORE_STATUS'] = '0';
        $where['ORDER_SWITCH'] = 1;
        $where['default_timezone_cd'] = array('in',$default_timezone_cd_data);
        $where['BEAN_CD'] = 'Ebay';
        $stores = $Model->table('tb_ms_store')
            ->field('ID,STORE_NAME')
            ->where($where)
            ->select();
        $PullOrderQueueService = new PullOrderQueueService();
        // 针对危险日期调整店铺片
        $big_store_ids = [90,225,95];
        $no_store_ids = []; // ebay-de-1
        foreach ($stores as $store) {
            if (in_array($store['ID'], $no_store_ids)) {
                continue;
            }
            $sheet = $default_sheet;
            $temp_data = [
                'startDate' => $start_date,
                'stores' => $store['ID'],
            ];
            if (in_array($store['ID'], $big_store_ids)) {
                $sheet = 12;
            }
            $res_data = $PullOrderQueueService->pushTimingShopOneDayQueue($temp_data, $sheet);
            $pullSingle[$store['STORE_NAME']] = $res_data;
        }
        if (I('start_date')) {
            $this->jsonOut($pullSingle);
        }
        return $pullSingle;
    }


    public function makeUpYesterdayByEbay()
    {
        $yesterday = date('Ymd', strtotime('Yesterday'));
        $res['ebay'] = $this->makeUpEbay($yesterday);
        Logs($res, __FUNCTION__, __CLASS__);
        $this->jsonOut($res);
    }

    public function makeUpToday()
    {
        if (0 == RedisModel::get_key('erp_switch_make_up_ebay_every_four_hour')) {
            return [];
        }
        $day = date('Ymd', strtotime('Today'));
        $res['ebay'] = $this->makeUpEbay($day);
        Logs($res, __FUNCTION__, __CLASS__);
        $this->jsonOut($res);
    }

    public function makeUpDesignatedDate()
    {
        $yesterday = date('Ymd', strtotime('Yesterday'));
        $sheet = 13;
        $res['ebay_yesterday'] = $this->makeUpEbay($yesterday, $sheet);
        $res['qoo10_yesterday'] = $this->makeUpQoo10($yesterday, $sheet);
        $today = date('Ymd', strtotime('Today'));
        $res['ebay_today'] = $this->makeUpEbay($today, $sheet);
        $res['ebay_qoo10'] = $this->makeUpQoo10($today, $sheet);
        Logs($res, __FUNCTION__, __CLASS__);
        $this->jsonOut($res);
    }

    public function makeUpAllOpen($start_date = null, $default_sheet = 8)
    {
        if (empty($start_date) || !('1575129600' < strtotime($start_date))) {
            return [];
        }
        $start_date = date('Ymd', strtotime($start_date)) . '000000';
        $Model = new Model(null, null, 'ONLINE_DB');
        $where['DELETE_FLAG'] = '0';
        $where['STORE_STATUS'] = '0';
        $where['ORDER_SWITCH'] = 1;
        $stores = $Model->table('tb_ms_store')
            ->field('ID,STORE_NAME')
            ->where($where)
            ->select();
        $PullOrderQueueService = new PullOrderQueueService();
        // 针对危险日期调整店铺片
        $big_store_ids = [90,225,95];
        $no_store_ids = []; // ebay-de-1
        foreach ($stores as $store) {
            if (in_array($store['ID'], $no_store_ids)) {
                continue;
            }
            $sheet = $default_sheet;
            $temp_data = [
                'startDate' => $start_date,
                'stores' => $store['ID'],
            ];
            if (in_array($store['ID'], $big_store_ids)) {
                $sheet = 12;
            }
             $res_data = $PullOrderQueueService->pushTimingShopOneDayQueue($temp_data, $sheet);
//            // 对于 Ebay-De 店铺增加前天的时间切片
//            if ($store['ID'] == 95 || $store['ID'] == 248){
//                $temp_data = [
//                    'startDate' => date('Ymd', strtotime('-2 day')), // 前天,
//                    'stores' => $store['ID'],
//                ];
//                $res_data_new = $PullOrderQueueService->pushTimingShopOneDayQueue($temp_data, $sheet);
//                $res_data = array_merge($res_data_new,$res_data);
//            }
            $pullSingle[$store['STORE_NAME']] = $res_data;
        }
        if (I('start_date')) {
            $this->jsonOut($pullSingle);
        }
        return $pullSingle;
    }

    public function makeUpEbay($start_date = null, $sheet = 12)
    {
        if (empty($start_date) || !('1575129600' < strtotime($start_date))) {
            return [];
        }
        $start_date = date('Ymd', strtotime($start_date)) . '000000';
        $Model = new Model(null, null, 'ONLINE_DB');
        $where['BEAN_CD'] = 'Ebay';
        $where['DELETE_FLAG'] = '0';
        $where['STORE_STATUS'] = '0';
        $where['ORDER_SWITCH'] = 1;
        $stores = $Model->table('tb_ms_store')
            ->field('ID,STORE_NAME')
            ->where($where)
            ->select();
        $PullOrderQueueService = new PullOrderQueueService();
        foreach ($stores as $store) {
            $temp_data = [
                'startDate' => $start_date,
                'stores' => $store['ID'],
            ];
            $pullSingle[$store['STORE_NAME']] = $PullOrderQueueService->pushShopOneDayQueue($temp_data, $sheet);
        }
        if (I('start_date')) {
            $this->jsonOut($pullSingle);
        }
        return $pullSingle;
    }

    public function makeUpQoo10($start_date = null, $sheet = 12)
    {
        if (empty($start_date) || !('1575129600' < strtotime($start_date))) {
            return [];
        }
        $start_date = date('Ymd', strtotime($start_date)) . '000000';
        $Model = new Model(null, null, 'ONLINE_DB');
        $where['BEAN_CD'] = 'Qoo10';
        $where['DELETE_FLAG'] = '0';
        $where['STORE_STATUS'] = '0';
        $where['ORDER_SWITCH'] = 1;
        $stores = $Model->table('tb_ms_store')
            ->field('ID,STORE_NAME')
            ->where($where)
            ->select();
        $PullOrderQueueService = new PullOrderQueueService();
        foreach ($stores as $store) {
            $sheet = 13;
            $temp_data = [
                'startDate' => $start_date,
                'stores' => $store['ID'],
            ];
            $pullSingle[$store['STORE_NAME']] = $PullOrderQueueService->pushShopOneDayQueue($temp_data, $sheet);
        }
        if (I('start_date')) {
            $this->jsonOut($pullSingle);
        }
        return $pullSingle;
    }

    public function ebay()
    {
//        $this->service->getStoreIds();
        $Model = new Model();
        $where['BEAN_CD'] = 'Ebay';
        $where['DELETE_FLAG'] = '0';
        $where['STORE_STATUS'] = '0';
        $stores = $Model->table('tb_ms_store')
            ->field('id')
            ->where($where)
            ->select();
        $now_time = date('YmdHis');
        $start_date = date('YmdHis', strtotime('-35 minute'));
        $end_date = $now_time;
        foreach ($stores as $store) {
            $url = GENERAL_B5C . "/op/crawler?stores={$store['id']}&startDate={$start_date}&endDate={$end_date}";
            ApiModel::crawler($url);
        }
    }

}