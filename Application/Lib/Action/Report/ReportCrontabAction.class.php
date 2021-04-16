<?php



/**
 * Class ReceivableAction
 */

//团队库存汇总报表定时任务
class ReportCrontabAction extends Action
{

    public function team_inventory_list_data()
    {
        //一般仓
        // $redis = RedisModel::connect_init();
        // echo $redis->get('TeamInventoryListData');exit;
        $start_time = $this->getMsectime();

        set_time_limit(30);
        ini_set('memory_limit', '512M');
        Logs(['action' => '定时生成团队库存汇总报表 start'], 'crontabTeamInventory list_data start');
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__ . ' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $model = new SlaveModel();
            $queryParams['type_cd'] = 'N002590100';
            $warehouse_list = WarehouseModel::filterWarehouse($queryParams);

            $standing_where_str = ' 1 = 1 ';
            $standing_where_str .= " and t1.vir_type != 'N002440200' ";
            $standing_where_str .= " and t1.vir_type != 'N002410200' ";
            $standing_where_str .= " and tb.type = '1' ";
            $standing_where_str .= " and tb.warehouse_id in ('" . implode("','", array_keys($warehouse_list)) . "') ";

            $allo_onway_where_str = ' 1 = 1 ';
            $allo_onway_where_str .= " and t1.vir_type = 'N002410200' ";
            $allo_onway_where_str .= " and t1.total_inventory > 1 ";

            //获取已经开启的销售团队
            $saleTeamCodes = BaseModel::saleTeamCdExtend();
            $sale_team_code = array_keys($saleTeamCodes);
            $start = '2018-08-01 00:00:00';
            $end = date('Y-m-d H:i:s', time());

            //现存量（CNY）
            $standing_amount_data = $model->table("tb_wms_batch AS t1")
            ->field(['ifnull(sum(t1.total_inventory * ws.unit_price_usd), 0) amount', 't1.sale_team_code sale_team_code'])
            ->join('LEFT JOIN tb_wms_bill tb ON t1.bill_id = tb.id')
            ->join('LEFT JOIN tb_wms_stream ws ON ws.id = t1.stream_id')
            ->where(['_string' => $standing_where_str,  't1.sale_team_code' => ['in', $sale_team_code]])
            ->group('sale_team_code')
            ->select();
            $standing_amount_data = $this->formatData($standing_amount_data);

            //调拨在途（CNY）
            $map = [
                    "wb.vir_type" => "N002410200",
                    'wb.total_inventory' => ["gt", 0],
                    "wb.sale_team_code" => ['in', $sale_team_code],
                ];
            $map_string = "(( wb.total_inventory > 0 AND wb.vir_type <> \"N002410200\") OR ((ba.available_for_sale_num + ba.occupied) > 0 AND wb.vir_type = \"N002410200\"))";

            $allo_onway_amount_data = $model->table(" tb_wms_batch AS wb")
                ->field('sum(if(wb.vir_type = \'N002410200\', (ba.available_for_sale_num + ba.occupied), wb.total_inventory) * ws.unit_price) as amount,wb.sale_team_code sale_team_code')
                ->join('left join tb_wms_bill wbill on wbill.id=wb.bill_id')
                ->join('left join tb_pur_order_detail od on od.procurement_number=wbill.link_bill_id')
                ->join('left join tb_pur_relevance_order rel on rel.order_id=od.order_id')
                ->join('left join tb_pur_goods_information gi on gi.relevance_id=rel.relevance_id and gi.sku_information=wb.sku_id')
                ->join('left join tb_wms_batch_allo ba on ba.batch_id=wb.id')
                ->join('left join tb_wms_allo wa on wa.allo_no = ba.allo_no')
                ->join('left join tb_wms_stream ws on ws.id=wb.stream_id')
                ->where($map)
                ->where($map_string, null, true)
                ->group('sale_team_code')
                ->select();
            $allo_onway_amount_data = $this->formatData($allo_onway_amount_data);

            //采购在途
            $where = [
                'prepared_time' => [['EGT', $start], ['ELT', $end]], 'dem.sell_team' => ['in', $sale_team_code],
                'rel.order_status' => ['neq', 'N001320500']
            ];

            $query =  $model->table("tb_pur_relevance_order AS rel")
            ->field([
                'procurement_number', //采购单号
                'od.real_currency_rate as po_date_rate', //PO日期兑换人民币汇率
                "(SELECT IFNULL(sum(qp.amount_account * qp.exchange_tax_account), 0) FROM tb_pur_payment qp
                    left join tb_pur_payment_audit pa on qp.payment_audit_id = pa.id
                    WHERE pa.billing_at < '$end' and pa.billing_at > '$start'  AND qp.STATUS = 3  AND qp.relevance_id = rel.relevance_id) 
                    AS amount_paid_ori", //付款金额 【实际出账金额】之和
                '(select ifnull(sum(rg.return_number*(gi.unit_price+gi.unit_expense)), 0) from tb_pur_return_goods rg
                    LEFT JOIN tb_pur_return_order ro ON ro.id=rg.return_order_id
                    LEFT JOIN tb_pur_return pr ON pr.id=ro.return_id
                    LEFT JOIN tb_pur_goods_information gi ON gi.information_id=rg.information_id
                    where ro.relevance_id = rel.relevance_id and pr.status_cd in ("N002640200","N002640300")) as return_goods_amount_ori', //退货金额 退货数量中的【退货出库数量】*[单价（含增值税）+PO内费用单价]
                "(SELECT IFNULL(sum((wgood.warehouse_number + wgood.warehouse_number_broken) * (pgi.unit_price+pgi.unit_expense)), 0) FROM tb_pur_warehouse_goods wgood 
                    LEFT JOIN tb_pur_warehouse wh ON wgood.warehouse_id = wh.id  LEFT JOIN tb_pur_ship pship ON pship.id = wh.ship_id
                    LEFT JOIN tb_pur_ship_goods sgood2 ON wgood.ship_goods_id = sgood2.id
                    LEFT JOIN tb_pur_goods_information pgi ON pgi.information_id = sgood2.information_id
                    WHERE pship.relevance_id = rel.relevance_id AND wh.warehouse_time < '$end' and wh.warehouse_time > '$start' )
                    AS amount_in_warehouse_ori", //已入库金额
                'cd2.CD_VAL as sale_team_val', //销售团队
                'cd2.CD as sale_team_cd', //销售团队
                'rel.relevance_id'
            ])
            ->join('LEFT JOIN tb_pur_order_detail od ON od.order_id = rel.order_id')
            ->join('LEFT JOIN tb_sell_quotation quo on quo.quotation_code=procurement_number')
            ->join('left join tb_sell_demand dem on dem.id=quo.demand_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = dem.sell_team')
            ->where($where);
            $list = $query->order('prepared_time desc')->select();
            $pur_order_nos = array_column($list, 'procurement_number');
            $return_amount_data =  $model->table("tb_fin_claim AS cl")
            ->field(['cl.order_no as pur_order_no', 'cl.claim_amount', 'cd.cd_val as currency', 'cd1.cd_val as pur_currency', 'xchr.*', '1 as CNY_XCHR_AMT_CNY'])
            ->join('LEFT JOIN tb_fin_account_turnover at ON at.id = cl.account_turnover_id')
            ->join('LEFT JOIN tb_pur_order_detail pod ON pod.procurement_number = cl.order_no')
            ->join('LEFT JOIN tb_pur_relevance_order pro ON pro.order_id = pod.order_id') //prepared_time
            ->join('LEFT JOIN tb_ms_xchr xchr ON xchr.XCHR_STD_DT = date_format(pro.prepared_time,"%Y%m%d")')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = at.currency_code')
            ->join('LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = pod.amount_currency')
            ->where(['cl.order_type' => 'N001950600', 'cl.order_no' => ['in', $pur_order_nos]])
            ->select();
            $return_amount_list = [];
            foreach ($return_amount_data as $v) {
                $rate = $v[$v['currency'] . '_XCHR_AMT_CNY'] / $v[$v['pur_currency'] . '_XCHR_AMT_CNY'];
                $amount = $v['claim_amount'] * $rate;
                $return_amount_list[$v['pur_order_no']] += $amount;
            }
            foreach ($list as &$v) {
                // 综合付款金额 = 付款金额 - 退款金额 （2019-10-09 需求9551-供应商对账需求改动）
                // 综合付款金额 = 付款金额 + 使用抵扣金金额 - 退款金额 - 算作抵扣金金额（返利除外）
                if (isset($return_amount_list[$v['procurement_number']])) {
                    $v['amount_paid_ori'] -= $return_amount_list[$v['procurement_number']];
                }
                $v['amount_in_warehouse_ori'] -= $v['return_goods_amount_ori'];
                $v['amount_onway_ori'] = $v['amount_paid_ori'] - $v['amount_in_warehouse_ori'];
                $v['amount_onway_cny'] = $v['amount_onway_ori'] * $v['po_date_rate'];
            }
            unset($v);
            //$list = array_filter($list, function ($v) {return $v['amount_onway'] >= 0.005;});
            $onway_amount = [];
            foreach ($list as $v) {
                $onway_amount[$v['sale_team_cd']] += $v['amount_onway_cny'];
            }
            $list = [];
            $rate_curreny = exchangeRateConversion('CNY', 'USD', date('Ymd'));
            foreach ($saleTeamCodes as $key => $item) {
                $data = [];
                $data['sale_team_code'] = $item['CD'];
                $data['sale_team'] = $item['CD_VAL'];
                $data['standing_existing'] = isset($standing_amount_data[$key]) ? $standing_amount_data[$key] : 0;
                $data['purchase_onway'] = $onway_amount[$key] * $rate_curreny;
                $data['allocation_onway'] = isset($allo_onway_amount_data[$key]) ? $rate_curreny * $allo_onway_amount_data[$key] : 0;
                $list[$key] = $data;
            }
            $list = array_values($list);
            $data['data']['list'] = $list;
            $data['data']['total'] = count($list);
            $redis = RedisModel::connect_init();
            $redis->setex('TeamInventoryListData', 120, json_encode($data));
            Logs(['data' => $data], 'crontabTeamInventory list_data end');

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }


       
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        echo json_encode($data);
    }

    //汇总数据格式化
    public function formatData($data)
    {
        $list = [];
        foreach ($data as $v) {
            $list[$v['sale_team_code']] += $v['amount'];
        }
        return $list;
    }

    public function cost_list_data()
    {
        //预热一个月内的数据
        set_time_limit(300);
        $start_time = $this->getMsectime();
        ini_set('memory_limit', '2048M');
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);

            cookie('think_language', 'zh-cn');
            $t1 = time();
            $time = date('Y-m-d H:i:s');
            $arr = ['query_time' => $time];
            $count = 0;
            $start_date = date('Y-m-d', strtotime('-45day'));
            // $start_date = '2019-06-01';
            $last_date = date('Y-m-d');

            for ($ii = 0; $ii < 60; $ii++) {
                $t_each = time();
                if (strtotime($start_date) > strtotime($last_date)) {
                    break;
                }


                $logic = D('Report/Cost', 'Logic');
                $tmp_date = date('Y-m-d', strtotime($start_date) + 1 * 24 * 60 * 60);
                
                $start_date  = $tmp_date;
                $data = [
                    'zd_date' => [$tmp_date, $tmp_date],
                    'page_size' => -1,
                ];


                // echo $start_date."<br>";
                $data = $logic->listData($data, 1);
                $tmp_count = count($data);
                $count += $tmp_count;
                if ($tmp_count > 0) {
                    array_walk($data, function (&$value, $key, $arr) {
                        $value = array_merge(
                            $value,
                            $arr
                        );
                    }, $arr);
                    #每次写入mongodb的条数
                    $each_num = 10000;
                    $tmp_each_i = ceil($tmp_count / $each_num);

                    for ($mongo_write_i = 0; $mongo_write_i < $tmp_each_i; $mongo_write_i++) {
                        $tmp_write_data = array_slice($data, $mongo_write_i * $each_num, $each_num);
                        MongoDbModel::client()->insertAll('tb_cost_list_data', $tmp_write_data);
                    }
                }

                // unset($data);
                Logs(['query_time' => $time, 'date' => [$start_date, $tmp_date], 'count' => $tmp_count, 'end ' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB', 'time each ' => time() - $t_each], 'get cost');
              
                #下次开始时间大于今天了
                if ((strtotime($start_date) + 1 * 24 * 60 * 60) > strtotime($last_date)) {
                    break;
                }
            }
            Logs(['time_all' => time() - $t1], 'get cost');
            #记录本次集合完成
            MongoDbModel::client()->insertOne('tb_cost_list_data_log', ['query_time' => $time, 'use_time' => time() - $t1, 'count' => $count, 'created_time' => date('Y-m-d H:i:s')]);
            #清除历史记录
            MongoDbModel::client()->deleteMany('tb_cost_list_data', ['query_time' => ['$ne' => $time]]);
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (Exception $e) {
            Logs(['msg' => $e->getMessage(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'line' => $e->getLine()], 'get cost error');
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
    }


    public function  get_stock_count(){
        $start_time = $this->getMsectime();
        try{
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            $t1 = time();
//            $model = new Model();
            $model = new SlaveModel();
            $count = $model->table(B5C_DATABASE . '.tb_wms_stream t1')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_bill t2 ON t1.bill_id = t2.id')
            //                ->join('left join ' . PMS_DATABASE . '.product_sku t5 ON t5.sku_id = t1.GSKU')
            ->join('left join ' . B5C_DATABASE . '.tb_wms_batch t3 ON t1.batch = t3.id')
            ->where("t1.tag = 0 AND t2.vir_type != 'N002440200'")
            ->count();
            if ($count > 0) {
                $redis = RedisModel::connect_init();
                $redis->setex('StockListDataCount', 120, $count);
            }
            Logs(['time_all' => time() - $t1,'count'=>$count], 'get stock count');
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        }catch(Exception $e){
            Logs(['msg' => $e->getMessage(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'line' => $e->getLine()], 'get stock count error');
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
       
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
