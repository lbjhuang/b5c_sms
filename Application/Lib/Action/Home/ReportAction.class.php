<?php
/**
 * 报表
 * User: yangsu
 * Date: 2019/8/12
 * Time: 16:05
 */

class ReportAction extends BaseAction
{
    /**
     * @var ReportService
     */
    protected $service;
    /**
     * @var ReportFormatter
     */
    protected $formatter;

    /**
     * @return bool|void
     */
    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        }
        $this->service = new ReportService();
        $this->formatter = new ReportFormatter();
    }

    public function b2bReceivable()
    {
        try {
            $data = DataModel::getDataNoBlankToArr();
            $this->validateB2bReceivable();
            $res = DataModel::$success_return;
            list($res['data']['list'], $res['data']['page'], $res['data']['sum_current_receivable_cny'], $res['data']['sum_current_receivable_usd']) = $this->service->b2bReceivable($data);
            $res['data']['list'] = CodeModel::autoCodeTwoVal($res['data']['list'], ['SALES_TEAM', 'order_currency_cd']);
            $res['data']['sum_current_receivable_cny'] = number_format($res['data']['sum_current_receivable_cny'], 2);
            $res['data']['sum_current_receivable_usd'] = number_format($res['data']['sum_current_receivable_usd'], 2);
            $res['data']['list'] = $this->formatter->b2bReceivable($res['data']['list']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateB2bReceivable()
    {

    }

    public function b2bReceivableExport()
    {
        try {
            $data = DataModel::getDataNoBlankToArr();
            $this->validateB2bReceivable();
            $res = DataModel::$success_return;
            $this->service->b2bReceivableExport($data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }

    public function earlyMonthB2bReceivableExport()
    {
        $start_time = $this->getMsectime();
        try {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $data = array(
                'search' =>
                array(
                    'from_today' =>
                    array(),
                    'client_name' =>
                    array(),
                    'sales_team' =>
                    array(),
                    'order_number' =>
                    array(
                        'type' => 'b2b',
                        'value' => '',
                    ),
                    'our_company' =>
                    array(),
                    'po_user' =>
                    array(),
                ),
                'pages' =>
                array(
                    'per_page' => '10',
                    'current_page' => '1',
                )
            );
           
            $this->validateB2bReceivable();
            
            $file_name = 'b2b_report_' . date('Ymd') . '000000.xls';
          
            $this->service->b2bReceivableExport($data, $file_name);
            
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        


    }

    public function updateFromToday()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $this->service->updateFromToday();

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        
    }

    public function getB2bReportExcel()
    {
        $Model = new Model();
        $res = DataModel::$success_return;
        $res['data'] = $Model->table('tb_excel_b2b_report')
            ->select();
        $this->ajaxReturn($res);
    }

    public function getReportExcel()
    {
        $type = I('type');
        $res = DataModel::$success_return;
        $res['data'] = TbExcelReport::getReportExcel($type);
        $this->ajaxReturn($res);
    }

    //历史现存量
    public function earlyMonthExistingStockExport()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            ini_set('max_execution_time', '500');
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $query_params = ZUtils::filterBlank($this->getParams());
            $storage_date = '';
            if ($query_params['storage_end_date']) {
                $storage_date = ['2010-01-01', $query_params['storage_end_date']];//可指定时间搜索
            }
            $warehouse_list = WarehouseModel::filterWarehouse(['type_cd' => 'N002590100']);//一般仓
            $warehouse_cds = array_keys($warehouse_list);
            $post_data = [
                'mixedCode' => '',
                'gudsName' => '',
                'warehouse' => $warehouse_cds,
                'purNum' => '',
                'saleTeam' => [],
                'purTeam' => [],
                'company' => [],
                'purDate' => [],
                'storageDate' => [],
                'dueDate' => [],
                'isDrug' => '',
                'showAll' => false,
                'productType' => '',
                'ascription_store' => [],
                'small_sale_team' => [],
                'existed_days' => ['', ''],
                'existed_days_level' => [],
                'is_oem_brand' => [],
            ];
            if ($storage_date) {
                $post_data['storageDate'] = $storage_date;
            }
            $param['post_data'] = json_encode($post_data);
            $file_name = 'existing_stock_' . date('YmdHi') . '.xls';
            $this->service->existingStockExport($param, $file_name);
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        
    }

    /**
     * 获取毫秒
     * @return float
     */
    public function getMsectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}