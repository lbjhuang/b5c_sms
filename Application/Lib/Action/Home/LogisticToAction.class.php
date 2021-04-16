<?php
/**
 * User: yangsu
 * Date: 18/11/7
 * Time: 15:39
 */


class LogisticToAction extends BaseAction
{
    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        }
    }

    public function sendAggregationMail()
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
            $aggrgation_arr = $this->getMailMessageData();
            $this->assign('data_arr', $aggrgation_arr);
            Logs(json_encode($aggrgation_arr, JSON_UNESCAPED_UNICODE), 'aggregation mail data', 'Mail');
            $message = $this->fetch('LogisticTo/aggregation_mail');
            $cc = C('LOGISTIC_AGGREGATION_CC');
            $user = C('LOGISTIC_AGGREGATION_USER');
            $res = (new SMSEmail())->sendEmail($user, '物流跟进提醒', $message, $cc, $this->getMailAttachment($aggrgation_arr));
            Logs($res, 'send aggregation mail type', 'Mail');
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');

    }

    /**
     * @param array $aggrgation_arr
     *
     * @return array
     */
    private function getMailMessageData($aggrgation_arr = [])
    {
        $aggrgation_arr['procurement'] = TbPurOrderDetailModel::getOutdateList();
        $aggrgation_arr['procurement'] = CodeModel::autoCodeTwoVal($aggrgation_arr['procurement'],
            [
                'payment_company',
            ],'all');
        $aggrgation_arr['procurement_ship'] = TbPurOrderDetailModel::getShipOutdateList();
        $aggrgation_arr['transfers'] = (new AllocationExtendAction())->allocationTimeoutSendMail();
        $aggrgation_arr['transfers'] = CodeModel::autoCodeTwoVal($aggrgation_arr['transfers'],
            [
                'allocationOutWarehouse',
                'allocationInWarehouse',
                'allocationOutTeam',
                'allocationInTeam',
            ],'all');
        $b2bAction = new B2bAction();
        $aggrgation_arr['b2b_delivery'] = $b2bAction->getDeliveryMailCount();
        $aggrgation_arr['b2b_tally'] = $b2bAction->getTallyMailCount();
        return $aggrgation_arr;
    }

    private function getMailAttachment(array $aggrgation_arr)
    {
        $exportExcel = new ExportExcelModel();
        $exportExcel->sheetObject->setTitle('采购发货超时');
        $this->writeProcurementShip($exportExcel, $aggrgation_arr['procurement_ship']);
        $exportExcel->addSheet('采购入库超时');
        $this->writeProcurement($exportExcel, $aggrgation_arr['procurement']);
        $exportExcel->addSheet('调拨入库超时');
        $this->writeRransfers($exportExcel, $aggrgation_arr['transfers']);
        $exportExcel->addSheet('B2B发货超时');
        $this->writeB2bDelivery($exportExcel, $aggrgation_arr['b2b_delivery']);
        $exportExcel->addSheet('B2B理货超时');
        $this->writeB2bTally($exportExcel, $aggrgation_arr['b2b_tally']);
        $exportExcel->phpExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($exportExcel->phpExcel, 'Excel5');
        $fpath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '物流跟进邮件附件.xls';
        $objWriter->save($fpath);
        return $fpath;
    }

    private function writeProcurementShip(ExportExcelModel &$exportExcel, $procurement_ship)
    {
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('采购单号'), 'field_name' => 'procurement_number'],
            $key++ => ['name' => L('采购PO单号'), 'field_name' => 'po'],
            $key++ => ['name' => L('供应商'), 'field_name' => 'supplier'],
            $key++ => ['name' => L('采购团队'), 'field_name' => 'purchase_team'],
            $key++ => ['name' => L('采购同事'), 'field_name' => 'purchaser'],
            $key++ => ['name' => L('预计发货日期'), 'field_name' => 'ship_time'],
            $key++ => ['name' => L('超期天数'), 'field_name' => 'outdate']
        ];
        $exportExcel->data = $procurement_ship;
        $exportExcel->setColumnTitle();
        $exportExcel->parseData();
    }

    private function writeProcurement(ExportExcelModel &$exportExcel, $procurement)
    {
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('采购单号'), 'field_name' => 'procurement_number'],
            $key++ => ['name' => L('采购PO单号'), 'field_name' => 'online_purchase_order_number'],
            $key++ => ['name' => L('发货编号'), 'field_name' => 'warehouse_id'],
            $key++ => ['name' => L('提单号'), 'field_name' => 'bill_of_landing'],
            $key++ => ['name' => L('供应商'), 'field_name' => 'supplier_id'],
            $key++ => ['name' => L('采购团队'), 'field_name' => 'payment_company_val'],
            $key++ => ['name' => L('采购同事'), 'field_name' => 'prepared_by'],
            $key++ => ['name' => L('到货仓库'), 'field_name' => 'warehouse'],
            $key++ => ['name' => L('预计到货/到港日期'), 'field_name' => 'arrival_date'],
            $key++ => ['name' => L('超期天数'), 'field_name' => 'outdate']
        ];
        $exportExcel->data = $procurement;
        $exportExcel->setColumnTitle();
        $exportExcel->parseData();
    }

    private function writeRransfers(ExportExcelModel &$exportExcel, $transfers)
    {
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('调拨单号'), 'field_name' => 'allocationNo'],
            $key++ => ['name' => L('调出仓库'), 'field_name' => 'allocationOutWarehouse_val'],
            $key++ => ['name' => L('调入仓库'), 'field_name' => 'allocationInWarehouse_val'],
            $key++ => ['name' => L('调出团队'), 'field_name' => 'allocationOutTeam_val'],
            $key++ => ['name' => L('调入团队'), 'field_name' => 'allocationInTeam_val'],
            $key++ => ['name' => L('调拨发起人'), 'field_name' => 'initiator'],
            $key++ => ['name' => L('出库确认人'), 'field_name' => 'confirmOutDeliveryPerson'],
            $key++ => ['name' => L('预计到达日期'), 'field_name' => 'estimateArriveDate'],
            $key++ => ['name' => L('超期天数'), 'field_name' => 'timeoutNum']
        ];
        $exportExcel->data = $transfers;
        $exportExcel->setColumnTitle();
        $exportExcel->parseData();
    }

    private function writeB2bDelivery(ExportExcelModel &$exportExcel, $b2b_delivery)
    {
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('B2B订单号'), 'field_name' => 'PO_ID'],
            $key++ => ['name' => L('销售PO单号'), 'field_name' => 'THR_PO_ID'],
            $key++ => ['name' => L('客户'), 'field_name' => 'CLIENT_NAME'],
            $key++ => ['name' => L('目标城市'), 'field_name' => 'target_city'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'SALES_TEAM_val'],
            $key++ => ['name' => L('销售同事'), 'field_name' => 'PO_USER'],
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouses'],
            $key++ => ['name' => L('预计发货日期'), 'field_name' => 'delivery_time'],
            $key++ => ['name' => L('超期天数'), 'field_name' => 'beyond_number'],
        ];
        $exportExcel->data = $b2b_delivery;
        $exportExcel->setColumnTitle();
        $exportExcel->parseData();
    }

    private function writeB2bTally(ExportExcelModel &$exportExcel, $b2b_tally)
    {
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('发货单号'), 'field_name' => 'BILL_LADING_CODE'],
            $key++ => ['name' => L('B2B订单号'), 'field_name' => 'PO_ID'],
            $key++ => ['name' => L('销售PO单号'), 'field_name' => 'THR_PO_ID'],
            $key++ => ['name' => L('发货仓库'), 'field_name' => 'warehouse_val'],
            $key++ => ['name' => L('发货人'), 'field_name' => 'AUTHOR'],
            $key++ => ['name' => L('客户'), 'field_name' => 'CLIENT_NAME'],
            $key++ => ['name' => L('目标港口'), 'field_name' => 'target_port'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'SALES_TEAM_val'],
            $key++ => ['name' => L('销售同事'), 'field_name' => 'PO_USER'],
            $key++ => ['name' => L('预计到港日期'), 'field_name' => 'Estimated_arrival_DATE'],
            $key++ => ['name' => L('超期天数'), 'field_name' => 'beyond_number'],
        ];
        $exportExcel->data = $b2b_tally;
        $exportExcel->setColumnTitle();
        $exportExcel->parseData();
    }

}