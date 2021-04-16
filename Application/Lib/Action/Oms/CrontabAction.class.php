<?php
/**
 * 订单中心相关定时任务
 * Class CrontabOmsAction
 */

class CrontabAction extends Action
{
    /**
     *  GP订单派单后12小时未出库邮件提醒   邮件发送时间为每个自然天的15:00
     */
    public function gpOrderRemind()
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
            $plat_cd_data = CodeModel::getSiteCodeArr("N002620800");
            if ($plat_cd_data) {
                $condition['tb_op_order.PLAT_CD'] =  array('in', array_column($plat_cd_data, 'CD'));
            }
            $condition['tb_op_order.SEND_ORD_STATUS'] = "N001820200";
            $condition['tb_ms_ord.WHOLE_STATUS_CD'] =  array('in', array('N001821101', 'N001820800', 'N001820700', 'N001820600', 'N001820500'));
            $condition['tb_op_order.BWC_ORDER_STATUS'] =  'N000550400';
            $condition['_string'] = "(tb_ms_ord_package.TRACKING_NUMBER IS NULL  OR tb_ms_ord_package.TRACKING_NUMBER = '')";
            //        $condition['tb_op_order.SEND_ORD_TIME'] = array( 'lt',date('Y-m-d H:i:s',time() - 1800));
            $condition['tb_op_order.SEND_ORD_TIME'] = array('lt', date('Y-m-d H:i:s', time() - 3600 * 12));
            $expTableData = M('order', 'tb_op_')
            ->field('tb_op_order.ORDER_NO AS order_no,
                    tb_op_order.ORDER_ID as order_id,
                    tb_op_order.B5C_ORDER_NO AS b5c_order_no,
                    tb_op_order.PLAT_CD AS plat_cd,
                    group_concat(DISTINCT tb_op_order_guds.SKU_ID )as sku_id,
                    tb_op_order.warehouse,
                    tb_op_order.logistic_cd,
                    LOGISTICS_CODE AS logistics_code,
                    tb_op_order.BWC_ORDER_STATUS AS bwc_order_status,
                    tb_op_order.SEND_ORD_STATUS AS send_ord_status,
                    tb_ms_ord.WHOLE_STATUS_CD AS whole_status_cd,
                    tb_ms_ord_package.TRACKING_NUMBER AS tracking_number,
                    tb_op_order.SEND_ORD_TIME AS send_ord_time')
            ->join('LEFT JOIN tb_ms_logistics_mode ON tb_op_order.logistic_model_id = tb_ms_logistics_mode.id')
            ->join('LEFT JOIN tb_op_order_guds ON tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID')
            ->join('LEFT JOIN tb_ms_ord ON tb_ms_ord.ORD_ID = tb_op_order.B5C_ORDER_NO')
            ->join('LEFT JOIN tb_ms_ord_package ON tb_ms_ord_package.ORD_ID = tb_op_order.ORDER_ID')
            ->where($condition)
                ->group('tb_op_order.ORDER_ID')
                ->order('tb_op_order.SEND_ORD_TIME')
                ->select();
            $sku_str = array_column($expTableData, 'sku_id');
            $sku_data = array();
            foreach ($sku_str as $value) {
                if (strpos($value, ',') !== false) {
                    $sku_sub_data = explode(',', $value);
                    $sku_data = array_merge($sku_sub_data, $sku_data);
                } else {
                    array_push($sku_data, $value);
                }
            }
            unset($sku_str);
            if ($expTableData) {
                SkuModel::initModel();
                $spu_name_list = SkuModel::getSkuNames($sku_data, 'product_sku.sku_id,product_detail.spu_name');
                $spu_name_data = array();
                foreach ($spu_name_list as $value) {
                    $spu_name_data[$value['sku_id']] = $value['spu_name'];
                }
                foreach ($expTableData as &$item) {
                    $sku_id_data = explode(',', $item['sku_id']);
                    $sku_name_data = array();
                    foreach ($sku_id_data as $value) {
                        if (isset($spu_name_data[$value]))
                            array_push($sku_name_data, $spu_name_data[$value]);
                    }
                    $item['sku_name'] = implode(',', $sku_name_data);
                }
            }
            unset($spu_name_list);
            unset($spu_name_data);
            unset($sku_id_data);
            unset($sku_name_data);

            $expTableData = CodeModel::autoCodeTwoVal(
                $expTableData,
                ['plat_cd', 'warehouse', 'logistic_cd', 'bwc_order_status', 'send_ord_status', 'whole_status_cd']
            );

            $expCellName = [
                ['order_no', '平台订单号', 20],
                ['b5c_order_no', 'ERP订单号', 20],
                ['plat_cd_val', '站点', 20],
                ['sku_id', 'SKU编码', 20],
                ['sku_name', '商品名称', 20],
                ['warehouse_val', '发货仓库', 20],
                ['logistic_cd_val', '发货物流公司', 20],
                ['bwc_order_status_val', '订单单状态', 20],
                ['whole_status_cd_val', '派单状态', 20],
                ['send_ord_time', '派单时间', 20],
            ];
            $email = new SMSEmail();
            $user = C('gp_send_reminder_email')['recipient']; // 收件人
            $cc   = C('gp_send_reminder_email')['cc']; // 抄送
            $title = '以下GP订单派单后12小时未出库，请尽快出库';
            $message = $this->getMessage($expCellName, $expTableData);
            $expTitle = '截止' . date("Y-m-d H:i:s") . '派单12小时后未出库GP订单';
            $attachment = $this->exportListExcel($expTitle, $expCellName, $expTableData);
            $res = $email->sendEmail($user, $title, $message, $cc, $attachment);

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');  

        // var_dump($res);
        // exit;
    }

    public function exportListExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle;
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        // 居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal('center');
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical('center');
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
            $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setWidth($expCellName[$i][2]);
        }
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2),  ' '.$expTableData[$i][$expCellName[$j][0]]);
                $objPHPExcel->getActiveSheet(0)->getStyle($cellName[$j].($i+2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            }
        }
        ob_start();
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $expTitle = '/opt/b5c-disk/excel/截止'.date("YmdHis").'派单12小时后未出库GP订单.xls';
        file_put_contents($expTitle, $content);
        return $expTitle;
    }

    public function getMessage($expCellName,$expTableData){
        $tableHeaderStyle_1='';
        $tableHeaderStyle_2='';
        $tableHeaderStyle_3='';

        $style ='<style type="text/css"> 
                 table{ 
                 background-color: #cccccc !important;
                 margin-top: 13px;
                 }
                 td{ 
                 	background-color:#ffffff;
	                height:25px;
	                line-height:150%;
                 }
                .table__hearder-td{
                background:#e9faff !important;
                color:#255e95;
                font-family: 微软雅黑;
                font-weight: bold;
                font-size: 16px;
                }
                 .table__hearder-td:nth-of-type(1){ width: 120px}
                 .table__hearder-td:nth-of-type(2){ width: 120px}
                 .table__hearder-td:nth-of-type(3){ width: 120px}
                 .table__hearder-td:nth-of-type(4){ max-width: 10%; min-width: 180px;}
                 .table__hearder-td:nth-of-type(5){ max-width: 10%; min-width: 180px;}
                 .table__hearder-td:nth-of-type(6){ width: 140px}
                 .table__hearder-td:nth-of-type(7){ width: 140px}
                 .table__hearder-td:nth-of-type(8){ width: 80px}
                 .table__hearder-td:nth-of-type(9){ width: 80px}
                 .table__hearder-td:nth-of-type(10){ width: 120px}

                .table__body-td{
                background:#f3f3f3 !important;
                white-space: normal;
                word-break: break-all;
                }
                </style>';

        $tableHeaderTdStyle="table__hearder-td";
        $tableBodyTdStyle="table__body-td";


        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        print_r($cellNum."  ");
        print_r($dataNum);


        $message = '<p>以下GP订单派单后12小时未出库，请尽快出库。</p><table width="100%" border="0"  cellspacing="1" cellpadding="6">';
        $message.= '<tr>';
        for($i=0;$i<$cellNum;$i++){
            $message.= '<td class='.$tableHeaderTdStyle.'>'.$expCellName[$i][1].'</td>';
        }
        $message.= '</tr>';
        for($i=0;$i<$dataNum;$i++){
            $message.= '<tr>';
            for($j=0;$j<$cellNum;$j++){
                $message.= '<td class='.$tableBodyTdStyle.'>'.$expTableData[$i][$expCellName[$j][0]].'</td>';
            }
            $message.= '</tr>';
        }
        $message .= '</table>';
        return $message.$style;
    }

    /**
     * 校验地址
     * @return bool
     */
    public function checkOrderAddressValid()
    {
        $start_time = $this->getMsectime();
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $limit = I('num') ? I('num') : 60;
            $model = new Model();
            //获取所有的地址校验配置-对应的物流模式
            $logistics_modes = CodeModel::getEtcKeyValue('N00343'); //GP
            $logistics_mode_ids = M('ms_logistics_mode', 'tb_')->field('id')->where(['LOGISTICS_MODE' => ['in', array_keys($logistics_modes)]])->select(); //第三方平台
            $where['o.logistic_cd'] = 'N000708200'; //万邑通
            $where['o.logistic_model_id'] = ['in', array_column($logistics_mode_ids, 'id')]; //万邑通
            $where['e.address_valid_status'] = 0;

            //只执行待派单的订单
            $list = $model->table('tb_op_order o')
                ->field('o.ORDER_ID,o.PLAT_CD,o.ADDRESS_USER_CITY,o.ADDRESS_USER_COUNTRY,o.ADDRESS_USER_COUNTRY_EDIT,o.ADDRESS_USER_COUNTRY_CODE,
            o.ADDRESS_USER_ADDRESS1,o.ADDRESS_USER_POST_CODE,e.doorplate,e.id')
                ->join('left join tb_ms_store s on o.STORE_ID = s.ID')
                ->join('left join tb_op_order_extend e on o.ORDER_ID = e.order_id and o.PLAT_CD = e.plat_cd')
                ->where($where)
                ->where(' (s.SEND_ORD_TYPE = 0 OR (s.SEND_ORD_TYPE = 1 AND o.send_ord_status != \'N001820100\' )) AND (  (o.B5C_ORDER_NO IS NULL)  ) AND ( o.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( o.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (o.CHILD_ORDER_ID IS NULL)  AND ( o.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true)
                ->limit($limit)->select();
            //var_dump($list);exit;
            if (empty($list)) {
                RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
                Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
                die('没有需要校验地址的订单。');
            }
            echo "开始校验地址...";
            //验证结果入库
            $err_order = $save_all = [];
            $order_log_m = new OrderLogModel();
            $order_extend_model = M('order_extend', 'tb_op_');
            foreach ($list as $k => $item) {
                if (!$item['id']) {
                    $err_order[] = $item['ORDER_ID'];
                    continue;
                }
                $param = [
                    'city'    => htmlspecialchars_decode($item['ADDRESS_USER_CITY'], ENT_QUOTES),
                    "country" => $item['ADDRESS_USER_COUNTRY_EDIT'] ?: (htmlspecialchars_decode($item['ADDRESS_USER_COUNTRY'], ENT_QUOTES) ?: $item['ADDRESS_USER_COUNTRY_CODE']),
                    'houseNo' => $item['doorplate'],
                    'street'  => htmlspecialchars_decode($item['ADDRESS_USER_ADDRESS1'], ENT_QUOTES),
                    'zipcode' => htmlspecialchars_decode($item['ADDRESS_USER_POST_CODE'], ENT_QUOTES),
                ];
                $res = ApiModel::addressValid($param);
                //验证结果入库
                //地址校验
                if ($res) {
                    $save['address_valid_status'] = 1;
                    if (strpos($res, 'true') !== false) {
                        $save['address_valid_status'] = 2;
                    } else {
                        $err_order[] = $item['ORDER_ID'];
                    }
                } else {
                    $res = '{"code" : "-1","msg" : "收货地址校验API无响应！请稍后再试。","data" : ""}';
                    $save['address_valid_status'] = 1;
                    $err_order[] = $item['ORDER_ID'];
                }
                $save['address_valid_res'] = $res;
                $res1 = $order_extend_model->where(['id' =>  $item['id']])->save($save);
                $msg = '待派单列表页收货地址校验请求。请求：' . json_encode($param) . '响应：' . $res;
                $order_log_m->addLog($item['ORDER_ID'], $item['PLAT_CD'], $msg, $param, 'ERP SYSTEM');
            }
            $num = count($list);
            $error_num = count($err_order);
            echo "共有{$num}单校验地址，失败{$error_num}单:{" . implode(',', $err_order) . "}";
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        exit;
    }

    //发送触发操作邮件
    public function testTask()
    {
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            echo '发送触发操作邮件脚本执行......<br/>';
            //获取单号
            echo '获取单号操作邮件发送start<br/>';
            $trigger_type = 1;
            $dataService = new DataService();
            $list = $dataService->sendRemindTaskEmail($trigger_type);
            //var_dump($list);
            echo '获取单号操作邮件发送end<br/>';

            //标记发货
            //echo '标记发货操作邮件发送start<br/>';
            $trigger_type = 0;
            $dataService = new DataService();
            $list = $dataService->sendRemindTaskEmail($trigger_type);
            //var_dump($list);
            echo '标记发货操作邮件发送end<br/>';
            echo '发送触发操作邮件脚本执行结束<br/>';
            

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
            return true;
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
       
    }

    //检查订单收入成本冲销状态状态（未冲销，已冲销）
    public function checkOrderChargeOffStatus()
    {
        $return_res = (new OmsAfterSaleService(null))->checkOrderChargeOffStatus();
        echo "检查更新订单收入成本冲销状态状态";
        v($return_res);
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