<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/2/1
 * Time: 18:55
 */

class FinanceAction extends BaseAction
{
    private $accountLog;
    private $mail;
    protected $whiteList = [
        'thrTurnOver'
    ];

    public function _initialize()
    {
        $whiteFunctionArr = ['importformatpaymentdata', 'transform', 'downloadtemplate', 'settlement_detail', 'update_settlement', 'delete_settlement', 'export_settlement', 'importbatchcheck'];
        if (!in_array(strtolower(ACTION_NAME), $whiteFunctionArr)) {

            $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
            if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
                $json_str = file_get_contents('php://input');
                if (strtolower(__ACTION__) != '/index.php/finance/receiptentry') {
                    $json_str = stripslashes($json_str);//制表符\t会被转化成t
                }
                $_POST = json_decode($json_str, true);
                $_REQUEST = array_merge($_POST, $_GET);
            }
            $this->accountLog = new TbWmsAccountBankLogModel();
            $this->mail = new ExtendSMSEmail();
            import('ORG.Util.Page');// 导入分页类
            header('Access-Control-Allow-Origin: *');
            header('Content-Type:text/html;charset=utf-8');
            if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
                && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
            ){
                parent::_initialize();
            }
            B('SYSOperationLog');
        }
       
    }

    // 获取原始表模板文件
    public function downloadTemplate()
    {
        $FinanceTransferService = new FinanceTransferService();
        $fileInfo = $FinanceTransferService->getRuleInfo(); // 根据所传参数定位原始表模板文件
        if (!$fileInfo) {
            $response = $this->formatOutput('3011', '参数有误或无法找到对应下载模板', I('request.'));
            return $this->ajaxReturn($response, 'json');
        }
        $name = $fileInfo['file_name'];
        //$name  = iconv('utf-8', 'gb2312', $name); // 防止中文名称乱码读取数据失败
        $filename = TMPL_PATH.'Home/Finance/Temp/'.$name;
        import('ORG.Net.Http');
        Http::download($filename, $filename);
    }

    //完结订单原始数据转换start======================================================================================
    // 原始表excel统一数据转换入口
    public function transform()
    {

        $post = I('post.');
        if (!$post['store_id'] || !$post['site_cd'] || !$post['plat_cd']) {
            throw new Exception("缺少必传参数，请先检查");
        }
        try {
            vendor("PHPExcel.PHPExcel"); //只需加载一次即可
            $FinanceTransferService = new FinanceTransferService();
            // 前端字段转换
            // 获取相关信息
            $ruleInfo = $FinanceTransferService->getRuleInfo();

            // 校验 返回true表示校验成功            
            $res = $FinanceTransferService->finTransValid($ruleInfo['file_name']);

            // 分发指派到特定方法（规则）进行处理
            $result = '';
            if ($res) {
                $result = $FinanceTransferService->distributeFunc($ruleInfo['func_name']);
            }
            if ($result) {
                // 处理成功，保存上传文件，否则返回失败原因
                $resu = $FinanceTransferService->saveFile($result);
                if ($resu) {
                    $response = $this->formatOutput('2000', 'success', $result);
                } else {
                    $response = $this->formatOutput('3000', '数据保存成功，但文件路径插入数据表失败', $result);
                }
            } else {
                $response = $this->formatOutput('3001', '保存数据失败', $res);
            }

            // 返回标准表数据
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $response = $this->formatOutput('3003', $info);
        }
        return $this->ajaxReturn($response, 'json');
    }
    // 初始化映射数据导入到数据表
    public function addDataByExcel()
    {
        vendor("PHPExcel.PHPExcel"); //只需加载一次即可
        $FinanceTransferService = new FinanceTransferService();
        $FinanceTransferService->initData();
    }
    //完结订单原始数据转换end======================================================================


    public function mailtest()
    {
        $this->display('auditMailTemplate');
    }

    /**
     * 入口页
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 账户详情
     */
    public function accountDetail()
    {
        $this->display('accountDetail');
    }

    /**
     * 转账
     */
    public function transferAccount()
    {
        $this->display('transferAccount');
    }

    /**
     * 日记账
     */
    public function turnoverList()
    {
        $this->display('turnoverList');
    }

    /**
     * 转账申请
     */
    public function transferApp()
    {
        $this->display('transferApp');
    }

    /**
     * 供应商对账
     */
    public function supplier_reconciliation()
    {
        $this->display('supplier_reconciliation');
    }

    //获取查询条件
    public function getWhere($params, $is_export = false)
    {
        $where = [];
        if (!empty($params['start_date'])) {
            $start_date = $params['start_date'] . ' 00:00:00';
        } else {
            //起始对账日期 2018-08-01
            $start_date = '2018-08-01 00:00:00';
        }
        //默认截止日期为当天
        if (!empty($params['end_date'])) {
            $end_date = $params['end_date'] . ' 23:59:59';
        } else {
            $end_date = date('Y-m-d H:i:s', time());
        }
        $where['o.prepared_time'][] = ['EGT', $start_date];
        $where['o.prepared_time'][] = ['ELT', $end_date];
        $where['o.order_status'][] = ['neq', "N001320500"]; //过滤已取消的采购订单
        //供应商
        if (!empty($params['supplier_name'])) {
            if (strpos($params['supplier_name'], '|') !== false) {
                $supplier_names = explode('|', $params['supplier_name']);
                $condition = [];
                foreach ($supplier_names as $item) {
                    if ($is_export) {
                        $condition[] = ' a.supplier_id = "' . $item . '"';
                    } else {
                        $condition[] = ' a.supplier_id like "%' . $item . '%"';
                    }
                }
                $where['_string'] = implode(' OR ', $condition);
            } else {
                if ($is_export) {
                    $where['a.supplier_id'] = array('eq', $params['supplier_name']);
                } else {
                    $where['a.supplier_id'] = array('like', '%' . $params['supplier_name'] . '%');
                }
            }
        }
        //我方公司
        if (!empty($params['our_company'])) {
            $where['a.our_company'] = ['in', $params['our_company']];
        }
        return $where;
    }

    /**
     * 供应商对账详情明细
     * @param $params
     * *@param $is_export
     * @return array|json
     */
    public function getReconciliationDetail($params = [], $is_export = false)
    {
        if (empty($params['start_date'])) $params['start_date'] = '2018-08-01 00:00:00';
        if (empty($params['end_date'])) $params['end_date'] = date('Y-m-d H:i:s', time());
        //$params['our_company'] = 'N001240500';
        //$params['supplier_name'] = '合肥一米家居饰品有限公司';
        $where = $this->getWhere($params, $is_export);
        $type = true;
        $financeServicve = new FinanceService();
        $relevanceData = $financeServicve->getRelevanceData($where, $type);

        //获取在途报表数据
        $onway_data = $financeServicve->getOnwayData($params);
        $tem = [];
        foreach ($onway_data as $item) {
            $tem[$item['relevance_id']] = $item;
        }
        $onway_data = $tem;
        $data = $financeServicve->formatRecociliationDataNew($relevanceData, $onway_data);
        return $data;
    }

    /**
     * 供应商对账
     * @param $is_excel
     * @return array|json
     */
    public function reconciliationDataList($is_excel = false)
    {
        $params = ZUtils::filterBlank($this->getParams());
        if ($params['page'] && $params['pageSize']) {
            $limit = [($params['page'] - 1) * $params['pageSize'], $params['pageSize']];
        } else {
            $limit = [0, 10];
        }
        if (empty($params['start_date'])) $params['start_date'] = '2018-08-01 00:00:00';
        //默认截止日期为当天
        if (!empty($params['end_date'])) {
            $end_date = $params['end_date'] . ' 23:59:59';
        } else {
            $end_date = $params['end_date'] = date('Y-m-d H:i:s', time());
        }
        $where = $this->getWhere($params);
        $type = true;
        $financeServicve = new FinanceService();
        $relevanceData = $financeServicve->getRelevanceData($where, $type);

        //获取在途报表数据
        $onway_data = $financeServicve->getOnwayData($params);
        $tem = [];
        foreach ($onway_data as $item) {
            $tem[$item['relevance_id']] = $item;
        }
        $onway_data = $tem;
        $data = $financeServicve->formatRecociliationDataNew($relevanceData, $onway_data);
        $list = [];
        //人民币转美元比率
        $rate_curreny = exchangeRateConversion('CNY', 'USD', date('Ymd'));
        foreach ($data as $key => $item) {
            $k = $item['our_company'] . '_' . $item['supplier_id'];
            //原始币种转人民币比率 * 人民币转美元比率
            if ($item['currency_old'] == 'USD') {
                $cate = 1;
            } else {
                $cate = $item['po_date_rate'] * $rate_curreny;
            }
            if (!isset($list[$k])) {
                $list[$k]['our_company_name'] = $item['our_company_name'];
                $list[$k]['our_company'] = $item['our_company'];
                $list[$k]['supplier_id'] = $item['supplier_id'];
            }
            $list[$k]['payment_amount'] += $item['payment_amount_num'] * $cate;
            $list[$k]['warehouse_amount'] += $item['warehouse_amount_num'] * $cate;
            $list[$k]['surplus_amount'] += $item['surplus_amount_num'] * $cate;
            $list[$k]['contract_amount'] += $item['amount_old'] * $cate;
            if ($item['payment_company'] && !in_array($item['payment_company'], $list[$k]['payment_company'])) {
                $list[$k]['payment_company'][] = $item['payment_company'];
            }
        }
        $tem = [];
        foreach ($list as $key => $item) {
            $item['payment_amount'] = number_format(round($item['payment_amount'], 2), 2); //付款
            $item['warehouse_amount'] = number_format(round($item['warehouse_amount'], 2), 2); //付款
            $item['surplus_amount'] = number_format(round($item['surplus_amount'], 2), 2); //付款
            $item['contract_amount'] = number_format(round($item['contract_amount'], 2), 2); //付款
            $item['payment_company'] = implode(',', $item['payment_company']);
            $item['rate_curreny'] = $rate_curreny;
            $tem[] = $item;
        }
        $list = array_values($tem);
        if ($is_excel) {
            return $list;
        }
        $return['total'] = count($tem);
        $return['end_date'] = $end_date;
        $return['page'] = $limit[0];
        $return['pageSize'] = $limit[1];
        $return['list'] = array_slice($list, $limit[0], $limit[1]);
        $this->ajaxReturn($return, 'json');
    }


    /**
     * 导出供应商对账
     * @param $is_excel
     * @return array|json
     */
    public function reconciliationDataAllExport()
    {
        $list = $this->reconciliationDataList(true);
        $map        = [
            ['field_name' => 'supplier_id', 'name' => '供应商名称'],
            ['field_name' => 'our_company_name', 'name' => '我方公司名称'],
            ['field_name' => 'contract_amount', 'name' => '合同金额（USD）'],
            ['field_name' => 'payment_amount', 'name' => '付款金额（USD）'],
            ['field_name' => 'warehouse_amount', 'name' => '入库金额（USD）'],
            ['field_name' => 'surplus_amount', 'name' => '余额（USD）'],
            ['field_name' => 'payment_company', 'name' => '采购团队'],
        ];
        $this->exportCsv($list, $map);
    }

    //导出供应商对账数据
    public function reconciliationDataExport()
    {
        ini_set('memory_limit', '256M');
        $params = json_decode(ZUtils::filterBlank($this->getParams())['post_data'], true);
        if (empty($params['our_company']) || empty($params['supplier_name'])) {
            die('我方公司或供应商缺失');
        }
        $data = $this->getReconciliationDetail($params, true);
        if (empty($data)) {
            die('无对账详情明细数据');
        }
        $expCellName = array(
            array('procurement_number', 'ERP采购单号'), //采购订单号
            array('online_purchase_order_number', '订单号码'), //PO订单号
            array('currency_old', '币种'),
            array('amount', '订单金额'),
            array('payment_amount', '付款金额'),
            array('warehouse_amount', '发货金额'),
            array('surplus_amount', '余额'),
            array('used_deduction_amount', '使用抵扣金'),
            array('as_deduction_amount', '产生抵扣金'),
            array('surplus_deduction_amount', '余额（计算抵扣金）'),
            array('reconciliation_remark', '对账备注'),
        );
        $cellNum2 = count($expCellName);
        $expTitle = 'excel';
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $file_name = $data[0]['our_company_name'];

        $start_date = $params['start_date'] ? date('Y年m月d日', strtotime($params['start_date'])) : '2018年8月1日';
        $date = $params['end_date'] ? date('Y年m月d日', strtotime($params['end_date'])) : date('Y年m月d日');
        $fileName = urlencode(str_replace(' ', '', $params['supplier_name'] . '-' . $file_name . '-截至' . $date));
        $expTitle = '供应商往来对账单';
        $part1 = [
            ['销售方', $params['supplier_name']],
            ['购买方', $file_name],
            ['账单起始日', $start_date],
            ['账单截止日', $date],
        ];
        $part2 = '请确认，起始'. $start_date. '截至' . $date . '，双方的交易情况是否与下列明细一致。如一致，请在本对账单下端“数据确认无误”处签章证明；如有数据不符或订单不全，请在“数据不符及需加以说明”处列明不符的金额和遗漏的订单。';
        $part3 = '账单明细如下：';
        $part4 = '金额分币种汇总：';
        //按币种汇总
        $part5 = [];
        foreach ($data as &$item) {
            $k = $item['currency_old'];
            if (!isset($part5[$k])) {
                $part5[$k]['currency'] = $k;
                //汇总未格式化的余额
                $part5[$k]['surplus_amount'] = $item['surplus_amount_num'];
                $part5[$k]['surplus_deduction_amount'] = $item['surplus_deduction_amount'];
                $item['surplus_deduction_amount'] = number_format($item['surplus_deduction_amount'], 2);
            } else {
                $part5[$k]['surplus_amount'] += $item['surplus_amount_num'];
                $part5[$k]['surplus_deduction_amount'] += $item['surplus_deduction_amount'];
                $item['surplus_deduction_amount'] = number_format($item['surplus_deduction_amount'], 2);
            }
        }
        $part5 = array_values($part5);
        $part6 = '注：对账单金额=付款金额-发货金额（正数余数为购买方预付金额；负数余额为购买方应付金额）';
        $part7 = '本对账单仅为复核账目之用，上述内容不构成对双方实际债权债务关系的变更。恳请贵司在收到本对账单的10天之内回复。';
        $part8 = '1、数据确认无误';
        $part9 = '2、数据不符合及需加以说明';
        $part10 = ['币种','余额','余额（计算抵扣金）'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $dataNum = count($part1);
        $dataNum5 = count($data);
        $dataNum6 = count($part5);
        $cellNum = count($cellName);
        //文件头部名称字体加粗居中
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:' . $cellName[$cellNum2 - 1] . '1')->setCellValue('A1', $expTitle);//合并单元格 表头
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25); // 设置宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(25);
        $index = 3;
        for ($i = $index; $i < $dataNum + $index; $i ++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $i, $part1[$i - $index][0]);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $i, $part1[$i - $index][1]);
        }
        $index2 = $i + 1;
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A' . $index2 . ':' . $cellName[$cellNum2 - 1] . ($index2 + 1))->setCellValue('A' . $index2, $part2);//合并单元格
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index2 . ':' . $cellName[$cellNum2 - 1] . ($index2 + 1))->getAlignment()->setWrapText(true); //换行

        $index3 = $i + 4;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $index3, $part3); //第三部分
        $index4 = $i + 5;
        $objPHPExcel->getActiveSheet()->getRowDimension($index4)->setRowHeight(30); //设置行高
        //所有垂直水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index4 . ':' . $cellName[$cellNum2 - 1] . $index4)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直对齐
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index4 . ':' . $cellName[$cellNum2 - 1] . $index4)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平对齐
        for ($i = $index4; $i < $dataNum5 + $index4 + 1 ; $i ++) {
            if ($i == $index4) {
                for ($j = 0; $j <= $cellNum2; $j ++) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j] . $i, $expCellName[$j][1]);
                }
            } else {
                for ($j = 0; $j <= $cellNum2; $j ++) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j] . $i , " " . $data[$i - $index4 - 1][$expCellName[$j][0]]);
                }
            }
        }
        //内外边框
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//边框是细的
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index4 . ':' . $cellName[$j - 2] . ($i - 1))->applyFromArray($styleArray);
        $index5 = $i + 1;
        $index10 = $index5 + 1;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$cellNum2 - 3] . $index5, $part4);//第三部分
        foreach ($part10 as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$cellNum2 - 3 + $k] . $index10, $v);
        }
        $index6 = $index10 + 1;
        for ($i = $index6; $i < $dataNum6 + $index6; $i ++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$cellNum2 - 3] . $i, $part5[$i - $index6]['currency']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$cellNum2 - 2] . $i, number_format($part5[$i - $index6]['surplus_amount'], 2));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$cellNum2 - 1] . $i, number_format($part5[$i - $index6]['surplus_deduction_amount'], 2));
        }
        $objPHPExcel->getActiveSheet()->getStyle('I' . $index10 . ':' . $cellName[$j - 2] . ($i - 1))->applyFromArray($styleArray);

        $objPHPExcel->getActiveSheet()->getStyle($cellName[$cellNum2 - 2] . $index6 . ':' . $cellName[$cellNum2 - 1] . ($i - 1))->applyFromArray($styleArray);

        $index7 = $i + 1;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $index7, $part6);//第6部分

        $index8 = $i + 3;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $index8, $part7);//第7部分

        $index9 = $i + 5;
        if ($cellNum2 % 2 == 0) {
            $middle = round($cellNum2/2) - 1;
        } else {
            $middle = round($cellNum2/2) - 2;
        }

        //外边框
        $styleArray2 = array(
            'borders' => array(
                'outline' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//边框是细的
                ),
            ),
        );
        $style1 = 'A' . $index9 . ':' . $cellName[$middle] . ($index9 + 8);
        $style2 = $cellName[$middle + 1] . $index9 . ':' . $cellName[$cellNum2 - 1] . ($index9 + 8);

        $objPHPExcel->setActiveSheetIndex(0)->mergeCells($style1)->setCellValue('A' . $index9, $part8);//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells($style2)->setCellValue($cellName[$middle + 1] . $index9, $part9);//合并单元格
        //所有顶部对齐水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index9 . ':' . $cellName[$middle + 1] . $index9)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP); //垂直对齐
        $objPHPExcel->getActiveSheet()->getStyle('A' . $index9 . ':' . $cellName[$middle + 1] . $index9)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平对齐
        $objPHPExcel->getActiveSheet()->getStyle($style1)->applyFromArray($styleArray2);
        $objPHPExcel->getActiveSheet()->getStyle($style2)->applyFromArray($styleArray2);

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    //银行账号导入
    public function importAccount()
    {
        try {
            if ($_FILES) {
                set_time_limit(0);
                ini_set('memory_limit', '512M');
                header("content-type:text/html;charset=utf-8");
                $excel_path = $_FILES['file']['tmp_name'];
                vendor('PHPExcel.PHPExcel');
                $model      = new Model();
                //$input_file_type = PHPExcel_IOFactory::identify($excel_path);
                //$PHPReader      = PHPExcel_IOFactory::createReader($input_file_type);

                //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
                $PHPReader = new PHPExcel_Reader_Excel2007();
                if (!$PHPReader->canRead($excel_path)) {
                    $PHPReader = new PHPExcel_Reader_Excel5();
                    if (!$PHPReader->canRead($excel_path)) {
                        $this->ajaxError('', '请上传EXCEL文件');
                    }
                }
                $obj_excel       = $PHPReader->load($excel_path);
                $sheet           = $obj_excel->getSheet(0);
                $max_row         = $sheet->getHighestRow();
                $max_cloumn      = $sheet->getHighestColumn();

                $model->startTrans();
                $err = [];
                for ($j = 2; $j <= $max_row; $j++) {
                    $procurement_number         = $obj_excel->getActiveSheet()->getCell("A" . $j)->getValue();
                    $reconciliation_remark    = $obj_excel->getActiveSheet()->getCell("B" . $j)->getValue();
                    $relevance_id = $model->table('tb_pur_relevance_order')
                        ->alias('o')
                        ->join("left join tb_pur_order_detail a on a.order_id = o.order_id")
                        ->where(['a.procurement_number' => trim($procurement_number)])->getField('o.relevance_id');
                    if (!$relevance_id) {
                        $err[] = '第' . $j . '行采购单号错误：' . $procurement_number;
                        continue;
                    }
                    $data[] = [
                        'relevance_id'          => $relevance_id,
                        'reconciliation_remark' => $reconciliation_remark,
                    ];
                }
                //有错误的采购单号不导入
                if (!empty($err)) {
                    $err_str = implode('<br/>', $err);
                    $this->ajaxError('', '导入失败:<br/>' . $err_str);
                }
                if (empty($data)) {
                    $this->ajaxError('', 'Excel数据为空');
                }
                //设置批量更新的主键
                $res = BaseModel::saveMult($data, 'tb_pur_relevance_order', 'relevance_id');
                if ($res === false) {
                    $this->ajaxError('', '导入失败');
                }
                $model->commit();
                $this->ajaxSuccess('', '导入成功');
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError($res);
        }
    }

    public function formatData($data, $type = false)
    {
        $tem = [];
        foreach ($data as $item) {
            //详情
            if ($type == true) {
                $k = $item['relevance_id'];
                if (!isset($tem[$k])) {
                    $tem[$k] = $item;
                    $tem[$k]['amount'] = $item['amount_old'];
                    //用amount_tmp存放转换为美元后的金额
                    $tem[$k]['amount_tmp'] = $item['amount'];
                } else {
                    $tem[$k]['amount'] += $item['amount_old'];
                    //amount_tmp累计转换为美元后的金额
                    $tem[$k]['amount_tmp'] += $item['amount'];
                }
            } else {
                //对账列表
                $k = $item['our_company'] . '_' . $item['supplier_id'];
                if (!isset($tem[$k])) {
                    $tem[$k] = $item;
                } else {
                    $tem[$k]['amount'] += $item['amount'];
                }
            }
        }
        return $tem;
    }

    /**
     * 银行账户列表
     */
    public function bankAccountList($join_search = null)
    {
        if ($join_search) {
            $params = $join_search;
        } else {
            $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        }
        $_GET ['p'] = $_POST ['p'] = $params ['pageIndex'] == Null ? 1 : $params ['pageIndex'];
        $fields = [
            "id",
            "company_code as companyCode",
            "supplier_id as supplierId",
            "account_class_cd",
            "open_bank as openBank",
            "account_bank as accountBank",
            "swift_code as swiftCode",
            "currency_code as currencyCode",
            "update_user as updateUser",
            "update_time as updateTime",
            "account_type as accountType",
            "state",
            "bsb_no as bsbNo",
            "reason",
            "payment_channel_cd",
            "bank_settlement_code",
            "bank_address",
            "bank_address_detail",
            "bank_postal_code",
            "bank_account_type",
            "bank_short_name",
            "city"
        ];
        $model = new TbWmsAccountBankModel();
        $where = $params ['companyCode'] ? ['_complex' => [
            'company_code' => ['in', $params ['companyCode']],
            'supplier_id' => ['in', $params ['companyCode']],
            '_logic'=>'or'
            ]] : null;
        $model->accountClassCd = $params ['accountClassCd'] ? ['in', $params ['accountClassCd']] : '';
        $model->openBank = $params ['openBank'] ? ['eq', $params ['openBank']] : '';
        $model->accountBank = $params ['accountBank'] ? ['eq', $params ['accountBank']] : '';
        $model->currencyCode = $params ['currencyCode'] ? ['eq', $params ['currencyCode']] : '';
        $model->state = $params ['state'] ? ['eq', $params ['state']] : '';
        $model->payment_channel_cd = !empty($params ['payment_channel_cd']) ? ['in', $params ['payment_channel_cd']] : '';
        $model->account_type = !empty($params ['account_type']) ? ['in', $params ['account_type']] : '';
        $temp_params = [];
        foreach ($model->params as $key => $value) {
            if (!empty($value)) {
                $temp_params[$key] = $value;
            }
        }
        $model->params = $temp_params;
        if ($where) $model->where($where);
        if ($join_search) {
            return $model->field('id')
                ->where($model->params)
                ->order('state asc, update_time desc')
                ->select();
        }
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $count = $model->where($model->params)->count();
        if ($where) $model->where($where);
        if ($params['pageIndex']) {
            $page = new Page($count, $size);
            $ret = $model->field($fields)
                ->where($model->params)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->order('state asc, update_time desc')
                ->select();
        }else{
            $ret = $model->field($fields)
                ->where($model->params)
                ->order('state asc, update_time desc')
                ->select();
        }
        $ret = CodeModel::autoCodeTwoVal($ret, ['payment_channel_cd', 'bank_account_type', 'account_class_cd']);
        $allUserName = TbWmsAccountTransferModel::getAllUserName();
        foreach ($ret as $key => &$value) {
            $value ['updateUser'] = $allUserName [$value ['updateUser']];
            $value ['accountClassCd'] = $value ['account_class_cd'];
            $value ['accountClassCdVal'] = $value ['account_class_cd_val'];
            $value ['companyCode'] = $value ['accountClassCd'] == 'N003510002' ? $value ['supplierId'] : $value ['companyCode'];
            unset($value);
        }
        if ($params ['rec']) {
            $baseModel = new Model();
            $companyForCountry = $baseModel->table('tb_ms_cmn_cd')->where('CD = "%s"', $params ['companyCode'])->find();
            if ($companyForCountry and $companyForCountry ['ETC3'] == 'CN') {
                $data ['usePayCurrency'] = true;
            } else {
                $data ['usePayCurrency'] = false;
            }
        }
        $data ['pageNo'] = $_GET ['p'];
        $data ['pageSize'] = $size;
        $data ['totalPage'] = ceil($count / $data ['pageSize']);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $count;
        //$data ['company']         = BaseModel::ourCompany();
        //$data ['currency']        = BaseModel::getCurrency();
        //$data ['accountType']     = TbWmsAccountBankModel::accountType();
        //$data ['state']           = $model::state();

        $response = $this->formatOutput('2000', 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * @name excel导入银行账户
     */
    public function import_bank_account($path = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'] ? $_FILES['file']['savepathtmp_name'] : $path['savepath']. $path['savename'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                throw new Exception(L('请上传EXCEL文件'));
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $data = $orders = [];
        //从第三行开始读数据
        $error_report_msg_list = []; $has_error_flag = false;
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $id = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $bank_short_name  = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());
            $bank_settlement_code  = trim((string)$PHPExcel->getActiveSheet()->getCell("L" . $currentRow)->getValue());
            $bank_address  = trim((string)$PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue());
            $bank_address_detail  = trim((string)$PHPExcel->getActiveSheet()->getCell("N" . $currentRow)->getValue());
            $bank_postal_code  = trim((string)$PHPExcel->getActiveSheet()->getCell("O" . $currentRow)->getValue());
            $account_type  = trim((string)$PHPExcel->getActiveSheet()->getCell("P" . $currentRow)->getValue());
            $bank_account['id'] = $id;
            $bank_account['bank_short_name'] = $bank_short_name; //银行简称
            $bank_account['bank_settlement_code'] = $bank_settlement_code; //本地结算代码
            $bank_account['bank_address'] = $bank_address; //银行地址
            $bank_account['bank_address_detail'] = $bank_address_detail; //银行详细地址
            $bank_account['bank_postal_code'] = $bank_postal_code; //银行邮编
            $bank_account['account_type'] = $account_type; //账户种类
            if (!empty($thr_order_no) && empty($bill_amount)) {
                $has_error_flag = true;
                $error_report_msg['reason'] = '账单金额不可为空';
                // throw new Exception(L('第' . $currentRow . '行数据不全'));
            }
            $data[] = $bank_account;
            $error_report_msg_list[] = $error_report_msg;
            unset($error_report_msg);
        }
        //设置批量更新的主键
        $res = BaseModel::saveMult($data, 'tb_fin_account_bank', 'relevance_id');
        return $res;
    }

    public function companyBanks()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']);
        $model = new TbWmsAccountBankModel();
        $where = ['_complex' => [
            'company_code' => ['in', $params ['company_code']],
            'supplier_id' => ['in', $params ['company_code']],
            '_logic'=>'or'
        ]];
        $banks = $model->where($where)->group('open_bank')->getField('open_bank', true);
        $this->ajaxSuccess(['banks' => $banks]);
    }

    public function companyBankAccounts()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']);
        $model = new TbWmsAccountBankModel();
        $accounts = $model
            ->alias('t')
            ->field('id,account_bank,swift_code,currency_code,a.CD_VAL currency, bank_short_name')
            ->join('tb_ms_cmn_cd a on a.CD=t.currency_code')
            ->where(['_complex' => [
                'company_code' => ['in', $params ['company_code']],
                'supplier_id' => ['in', $params ['company_code']],
                '_logic'=>'or'
            ], 'open_bank' => $params['open_bank']])
            ->select();
        $this->ajaxSuccess($accounts);
    }

    /**
     * 创建银行账户|更新银行账户
     */
    public function createBankAccount()
    {
        $code = 2000;
        $info = 'success';
        $data = [];

        $params = ZUtils::filterBlank($this->getParams()['data']);
        $model = new TbWmsAccountBankModel();
        $model->accountClassCd = $params ['accountClassCd'];
        $model->companyCode = $params ['companyCode'];
        $model->accountType = $params ['accountType'];
        $model->openBank = $params ['openBank'];
        $model->accountBank = $params ['accountBank'];
        $model->currencyCode = $params ['currencyCode'];
        $model->swiftCode = $params ['swiftCode'];
        $model->bsbNo = $params ['bsbNo'];
        $model->state = $params ['state'];
        $model->reason = $params ['reason'];
        $model->payment_channel_cd = $params ['payment_channel_cd'];
        $model->bank_settlement_code = $params ['bank_settlement_code'];
        $model->bank_address = $params ['bank_address'];
        $model->bank_address_detail = $params ['bank_address_detail'];
        $model->bank_postal_code = $params ['bank_postal_code'];
        $model->bank_account_type = $params ['bank_account_type'];
        $model->city = $params ['city'];
        $model->bank_short_name = $params ['bank_short_name'];
        if ($params ['accountClassCd'] == 'N003510002') {
            $model->companyCode = '';
            $model->supplier_id = $params ['companyCode'];
        }
        if (isset($params ['id'])) {
            $model->id = $params ['id'];
            if ($model->save($model->create($model->params, 2))) {
                $info = L('更新银行账户成功');
                $data ['pageData'] = BaseModel::convertUnder($model->params);
            } else {
                $code = 3000;
                $info = L('更新银行账户失败');
                preg_match('/^1062/', $model->db->getError(), $matchs);
                if ($matchs [0] == '1062') {
                    $data ['error'] = L('银行账号已存在');
                } else {
                    $data ['error'] = $model->getError();
                }
            }
        } else {
            $ret = $model->create($model->params);
            if ($id = $model->add($ret)) {
                $model->id = $id;
                $ret ['id'] = $id;
                $info = L('新增银行账户成功');
                $data ['pageData'] = BaseModel::convertUnder($ret);
            } else {
                $code = 3000;
                $info = L('新增银行账户失败');
                preg_match('/^1062/', $model->db->getError(), $matchs);
                if ($matchs [0] == '1062') {
                    $data ['error'] = L('银行账号已存在');
                } else {
                    $data ['error'] = $model->getError();
                }
            }
        }
        $this->accountLog->createLog($model->id, $info);
        $response = $this->formatOutput($code, $info, $data);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 转账换汇接口
     */
    public function transfer()
    {
        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $_GET ['p'] = $_POST ['p'] = $params ['pageIndex'] == Null ? 1 : $params ['pageIndex'];
        $model = new TbWmsAccountTransferModel();
        //是否展示待我审批
        if ($r = $model->getAuditorStep()) {
            $data ['meApprove'] = true;
        } else {
            $data ['meApprove'] = false;
        }
        $bankModel = new TbWmsAccountBankModel();
        $model->accountTransferType = $params ['accountTransferType'] ? ['eq', $params ['accountTransferType']] : '';
        $model->state = $params ['state'] ? ['in', $params ['state']] : '';
        $model->transferNo = $params ['transferNo'] ? ['like', '%' . $params ['transferNo'] . '%'] : '';
        $model->payCompanyCode = $params ['payCompanyCode'] ? ['in', $params ['payCompanyCode']] : '';
        $model->payOpenBank = $params ['payOpenBank'] ? ['eq', $params ['payOpenBank']] : '';
        $model->payAccountBank = $params ['payAccountBank'] ? ['eq', $params ['payAccountBank']] : '';
        $model->recCompanyCode = $params ['recCompanyCode'] ? ['in', $params ['recCompanyCode']] : '';
        $model->recOpenBank = $params ['recOpenBank'] ? ['eq', $params ['recOpenBank']] : '';
        $model->recAccountBank = $params ['recAccountBank'] ? ['eq', $params ['recAccountBank']] : '';
        $model->currencyCode = $params ['currencyCode'] ? ['eq', $params ['currencyCode']] : '';
        $model->createUserNm = $params ['createUser'] ? ['like', '%' . $params ['createUser'] . '%'] : '';
        if ($data ['meApprove'] and isMobile()) {
            $model->currentAuditStep = ['in', array_column($r, 'canAuditStep')];
        }
        if ($params ['minAmountMoney'] and $params ['maxAmountMoney']) {
            $model->amountMoney = [['egt', $params ['minAmountMoney']], ['elt', $params ['maxAmountMoney']], 'and'];
        } elseif ($params ['minAmountMoney'] and !$params ['maxAmountMoney']) {
            $model->amountMoney = ['egt', $params ['minAmountMoney']];
        } elseif ($params ['maxAmountMoney'] and !$params ['minAmountMoney']) {
            $model->amountMoney = ['elt', $params ['maxAmountMoney']];
        }
        //$model->minAmountMoney = $params ['minAmountMoney'];
        //$model->maxAmountMoney = $params ['maxAmountMoney'];
        if ($params ['createStartTime'] and !$params ['createEndTime']) {
            $model->createTime = $params ['createStartTime'] ? ['egt', $params ['createStartTime'] . ' 00:00:00'] : '';
            unset($params ['createStartTime'], $params ['createEndTime']);
        } elseif ($params ['createEndTime'] and !$params ['createStartTime']) {
            $model->createTime = $params ['createEndTime'] ? ['elt', $params ['createEndTime'] . ' 23:59:59'] : '';
            unset($params ['createStartTime'], $params ['createEndTime']);
        } elseif ($params ['createStartTime'] and $params ['createEndTime']) {
            $model->createTime = [['egt', $params ['createStartTime']], ['elt', $params ['createEndTime'] . ' 23:59:59'], 'and'];
            unset($params ['createStartTime'], $params ['createEndTime']);
        }
        $model->createStartTime = $params ['createStartTime'];
        $model->createEndTime = $params ['createEndTime'];
        $model->cd = $params ['currentAuditor'];

        $where = $model->params;
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $subQuery = $model
            ->table('tb_ms_cmn_cd')
            ->where('cd like "N002000%"')
            ->buildSql();

        if (!empty($params ['paymentAuditNo'])) {
            $where['pa.payment_audit_no'] = $params ['paymentAuditNo'];
            $allData = $model
                ->table('tb_fin_account_transfer t1')
                ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                ->join('inner join tb_pur_payment_audit pa on pa.id = t1.payment_audit_id')
                ->where($where)->select();
        } else {
            $allData = $model
                ->table('tb_fin_account_transfer t1')
                ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                ->where($where)->select();
        }

        $count = count($allData);
        $currencies = CommonDataModel::currency();
        $currencies = array_column($currencies, 'cdVal', 'cd');
        $totalAmountMoney = 0;
        foreach ($allData as $key => $value) {
            $totalAmountMoney += (floatval($this->getCurrencyRate($currencies [$value ['currency_code']], $value ['create_time'])) * floatval($value ['amount_money']));
        }
        $page = new Page($count, $size);
        $fields = [
            't1.id',
            'account_transfer_type',
            'payment_company_class_cd',
            'collection_company_class_cd',
            'transfer_no',
            'pay_company_name',
            //'pay_open_bank',
            'rec_company_name',
            //'rec_open_bank',
            'currency_code',
            'amount_money',
            'create_user',
            'state',
            'create_time',
            'audit_step',
            'current_step',
            'ETC3',
            'create_user_nm'
        ];

        if ($params ['meApprove']) {
            $node = $model->getAuditorStep();
            if ($node) {
                foreach ($node as $key => $value) {
                    $canAuditStep [] = $value ['canAuditStep'];
                }
                if (!empty($params ['paymentAuditNo'])) {
                    $ret = $model->field($fields)
                        ->table('tb_fin_account_transfer t1')
                        ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                        ->join('inner join tb_pur_payment_audit pa on pa.id = t1.payment_audit_id')
                        ->where($model->params)
                        ->where(['t1.current_step' => ['in', $canAuditStep]])
                        ->where(['pa.payment_audit_no' => $params ['paymentAuditNo']])
                        ->limit($page->firstRow . ',' . $page->listRows)->order('t1.id desc')->select();
                } else {
                    $ret = $model->field($fields)
                        ->table('tb_fin_account_transfer t1')
                        ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                        ->where($model->params)
                        ->where(['t1.current_step' => ['in', $canAuditStep]])
                        ->limit($page->firstRow . ',' . $page->listRows)->order('t1.id desc')->select();
                }
            }
        } else {
            if (!empty($params ['paymentAuditNo'])) {
                $ret = $model->field($fields)
                    ->table('tb_fin_account_transfer t1')
                    ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                    ->join('inner join tb_pur_payment_audit pa on pa.id = t1.payment_audit_id')
                    ->where($model->params)
                    ->where(['pa.payment_audit_no' => $params ['paymentAuditNo']])
                    ->limit($page->firstRow . ',' . $page->listRows)->order('t1.id desc')->select();
            } else {
                $ret = $model->field($fields)
                    ->table('tb_fin_account_transfer t1')
                    ->join($subQuery . ' t2 ON t1.current_step = t2.SORT_NO')
                    ->where($model->params)->limit($page->firstRow . ',' . $page->listRows)->order('t1.id desc')->select();
            }
        }
        $ret = BaseModel::convertUnder($ret);
        $data ['pageNo'] = $_GET ['p'];
        $data ['pageSize'] = $size;
        $data ['totalPage'] = ceil($count / $data ['pageSize']);
        $allUserName = TbWmsAccountTransferModel::getAllUserName();
        $accountTransferType = array_column(CommonDataModel::accountTransferType(), 'cdVal', 'cd');
        foreach ($ret as $key => &$value) {
            if (empty($value['createUserNm'])) {
                $value['createUserNm'] = DataModel::getUserScNameById($value['createUser']);
            }
            if (empty($value['createUserNm'])) {
                $value['createUserNm'] = DataModel::getUserNameById($value['createUser']);
            }
            $value ['createUser'] = $allUserName [$value ['createUser']];
            $value ['auditAuth'] = $model->checkAuditAuth($value ['currentStep'], $value ['auditStep']);
            $value ['accountTransferTypeVal'] = $accountTransferType[$value['accountTransferType']];
            unset($value);
        }
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $count;
        $data ['totalAmountMoney'] = number_format($totalAmountMoney, 2, '.', '');
        $data ['totalAmountMoneyCurrency'] = self::$suffix;

        $response = $this->formatOutput('2000', 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 发起转账申请|更新转账申请|审核
     */
    public function transferApply()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $model = new TbWmsAccountTransferModel();
        $bankModel = new TbWmsAccountBankModel();
        $payAccountInfo = BaseModel::convertUnder($bankModel->getAccountBankInfo($params ['payCompanyCode'], $params ['payOpenBank'], $params ['payAccountBank'], $params ['paymentCompanyClassCd']));
        $recAccountInfo = BaseModel::convertUnder($bankModel->getAccountBankInfo($params ['recCompanyCode'], $params ['recOpenBank'], $params ['recAccountBank'], $params ['collectionCompanyClassCd']));
        try {
            $model->startTrans();
            if ($recAccountInfo and $payAccountInfo) {
                //银行简称为CITI 付款公司、收款公司、付款银行名称、付款公司银行账号、收款公司银行账号必填
                if (strtoupper($payAccountInfo['bankShortName']) == 'CITI') {
                    if (empty($payAccountInfo ['bankSettlementCode'])) {
                        throw new Exception(L('请先补充付款账号-本地结算代码'));
                    }
                    if (empty($payAccountInfo ['bankAddress'])) {
                        throw new Exception(L('请先补充付款账号-银行地址'));
                    }
                    if (empty($payAccountInfo ['bankAddressDetail'])) {
                        throw new Exception(L('请先补充付款账号-银行详细地址'));
                    }
                    if (empty($payAccountInfo ['bankPostalCode'])) {
                        throw new Exception(L('请先补充付款账号-银行邮编'));
                    }
                    if (empty($payAccountInfo ['bankAccountType'])) {
                        throw new Exception(L('请先补充付款账号-账户种类'));
                    }
                }
                $model->payAccountBankId = $payAccountInfo ['id'];
                $model->paymentCompanyClassCd = $params ['paymentCompanyClassCd'];
                $model->collectionCompanyClassCd = $params ['collectionCompanyClassCd'];
                $model->accountTransferType = $params ['paymentCompanyClassCd'] == 'N003510001' && $params ['collectionCompanyClassCd'] == 'N003510001' ? 1 : 2;
                $model->payCompanyName = $params ['payCompanyName'];
                $model->payCompanyCode = $params ['payCompanyCode'];
                $model->payAccountBank = $params ['payAccountBank'];
                $model->payOpenBank = $params ['payOpenBank'];
                $model->recAccountBankId = $recAccountInfo ['id'];
                $model->recCompanyName = $params ['recCompanyName'];
                $model->recCompanyCode = $params ['recCompanyCode'];
                $model->recAccountBank = $params ['recAccountBank'];
                $model->recOpenBank = $params ['recOpenBank'];
                $model->currencyCode = $params ['currencyCode'];
                $model->amountMoney = $params ['amountMoney'];
                $model->reason = $params ['reason'];
                $model->use = $params ['use'];
                $swapType = $model->getTransferType($params ['payCompanyCode'], $payAccountInfo['currencyCode'], $params ['recCompanyCode'], $recAccountInfo ['currencyCode']);
                $model->transferType = $swapType ['cd'];
                $model->auditStep = $swapType ['etc'];
                if (!isset($params ['id'])) {
                    $model->transferNo = 'ZZ' . date('Ymd', time()) . TbWmsNmIncrementModel::generateNo('ZZ');
                }
                if (isset($params ['id'])) {
                    $ret = $model->where('id = %d', [$params [id]])->find();
                    $transfer_no = $ret['transfer_no'];
                    //清楚批注
                    FinAccountMsgModel::cleanAuditMsg($ret ['transfer_no']);
                    $model->id = $id = $params ['id'];
                    if ($_FILES) {
                        if ($imgInfo = $this->uploadFile($_FILES ['attachment'])) {
                            foreach ($imgInfo as $key => $value) {
                                $tmp [$key]['baseName'] = $value ['name'];
                                $tmp [$key]['saveName'] = $value ['savename'];
                            }
                        }
                        if ($params ['attachment'] and $tmp) {
                            $saveTmp = [];
                            foreach ($params ['attachment'] as $key => $value) {
                                $value = json_decode($value, true);
                                foreach ($value as $k => $v) {
                                    $saveTmp [] = $v;
                                }
                            }
                            $model->attachment = json_encode(array_merge($saveTmp, $tmp));
                        } elseif ($params ['attachment'] and !$tmp) {
                            $saveTmp = [];
                            foreach ($params ['attachment'] as $key => $value) {
                                $value = json_decode($value, true);
                                foreach ($value as $k => $v) {
                                    $saveTmp [] = $v;
                                }
                            }
                            $model->attachment = json_encode($saveTmp);
                        } else {
                            $model->attachment = json_encode($tmp);
                        }
                    } else {
                        if ($params ['attachment']) {
                            $saveTmp = [];
                            foreach ($params ['attachment'] as $key => $value) {
                                $value = json_decode($value, true);
                                foreach ($value as $k => $v) {
                                    $saveTmp [] = $v;
                                }

                            }
                            $model->attachment = json_encode($saveTmp);
                        }
                    }
                    if ($ret ['state'] == $model->failState) {
                        $model->currentAuditStep = 0;
                        $model->currentStep = 1;
                        $model->state = $model->initState;
                    }
                    if (empty($params ['reason'])) $model->params['reason'] = '';
                    if (empty($params ['use'])) $model->params['use'] = '';
                    // 审核失败的更新
                    if ($model->save($model->create($model->params))) {
                        $code = 2000;
                        $info = L('更新申请成功');
                        $model->commit();
                        $data ['pageData'] = BaseModel::convertUnder($model->params);
                    } else {
                        $code = 3000;
                        $info = L('更新申请失败');
                        $model->rollback();
                        $data ['error'] = $model->db->getError();
                    }
                    //重新编辑提交，删除付款单
                    if (!empty($ret['payment_audit_id'])) {
                        (new TransferPaymentService())->cancelPaymentBill($ret['payment_audit_id']);
                    }

                } else {
                    if ($_FILES) {
                        if ($imgInfo = $this->uploadFile($_FILES ['attachment'])) {
                            foreach ($imgInfo as $key => $value) {
                                $tmp [$key]['baseName'] = $value ['name'];
                                $tmp [$key]['saveName'] = $value ['savename'];
                            }
                            $model->attachment = json_encode($tmp);
                        }
                    }
                    $model->createUserNm = $_SESSION ['emp_sc_nm'];
                    $ret = $model->create($model->params);
                    $id = $model->add($ret);
                    if ($ret and $id) {
                        $code = 2000;
                        $info = L('发起申请成功');
                        $ret ['id'] = $id;
                        // 发送邮件
                        $createUser = $model->getAuditName($ret ['create_user']);
                        $ret ['attachment'] = json_decode($ret ['attachment'], true);
                        foreach ($ret ['attachment'] as $key => &$value) {
                            $value ['resource'] = $this->packStreamFile($value ['saveName']);
                        }
                        $ret ['create_user'] = $createUser;
                        $ret ['currency_code'] = $model->getCurrencyVal($ret ['currency_code']);
                        $ret ['url'] = C('redirect_audit_addr') . '/index.php?m=finance&a=transferApp&id=' . $id;
                        $ret ['pay_currency_code'] = CommonDataModel::currencyExtend() [$payAccountInfo ['currencyCode']];
                        $ret ['rec_currency_code'] = CommonDataModel::currencyExtend() [$recAccountInfo ['currencyCode']];
                        $ret ['comments'] = BaseModel::convertUnder(FinAccountMsgModel::getAuditMsg($ret ['transfer_no']));
                        $context = $this->getTemplate('Finance:auditMailTemplate', $ret);
                        $auditUserMail = explode('@', CommonDataModel::auditPerson() [0]['ETC'])[0] . '@gshopper.com';
                        if ($this->sendMail($auditUserMail, L('资金划转审批提醒'), $context)) {
                            $msg = L('发送邮件成功');
                        } else {
                            $msg = L('发送邮件失败');
                        }
                        $mainMsg = L('向:') . '(' . explode('@', CommonDataModel::auditPerson() [0]['ETC'])[0] . ')' . $msg;
                        $this->accountLog->createLog($model->transferNo, $mainMsg, json_encode($ret));
                        // 邮件发送结束
                        $data ['pageData'] = BaseModel::convertUnder($ret);
                        $model->commit();

                        $pushParams = [
                            'description' => L('待审批提醒'),
                            'title' => L('资金划转待审批提醒'),
                            'ticker' => L('资金划转待审批提醒'),
                            'text' => L('编号为') . $ret['transfer_no'] . L('的资金划转申请需要您审批'),
                            'transferNo' => $ret ['transfer_no'],
                            'idVal' => $id,
                            'alias' => $this->getUserInfoByName(explode('@', CommonDataModel::auditPerson() [0]['ETC'])[0])['M_ID']
                        ];
                        B('UMessagePush', $pushParams);

                        //

                    } else {
                        $code = 3000;
                        $info = L('发起申请失败');
                        $model->rollback();
                        $data ['error'] = $model->getError();
                    }
                }
            } else {
                $code = 3000;
                if (!$payAccountInfo) {
                    $info[] = L('未查询到付款公司');
                }
                if (!$recAccountInfo) {
                    $info[] = L('未查询到收款公司');
                }
                $model->rollback();
                $data = [];
            }
        } catch (Exception $e) {
            $model->rollback();
            $code = 3000;
            $info = $e->getMessage();
            $data = [];
        }
        $transfer_no = $model->transferNo ? : $transfer_no;
        $this->accountLog->createLog($transfer_no, $info, null, 2);

        if ($code == 2000) {
            //企业微信审批,添加和编辑都要
            $send_email = CommonDataModel::auditPerson()[0]['ETC'];
            if ($send_email == 'Yeogirl.Yun@gshopper.com') {
                @SentinelModel::addAbnormal('划转审批企业微信发给了老板，已拦截', $transfer_no,[$send_email,$transfer_no],'fin_notice');
            } else {
                (new FinanceService())->bulidTransferWechatApproval($id, $send_email);
            }
        }
        $response = $this->formatOutput($code, $info, $data);
        $this->ajaxReturn($response, 'json');
    }

    public function getUserInfoByName($name)
    {
        $model = new Model();
        $ret = $model->table('bbm_admin')
            ->where('M_NAME = "%s"', $name)
            ->find();
        return $ret;
    }

    /**
     * 编辑备注
     */
    public function updateComment()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']);
        $model = new TbWmsAccountTransferModel();
        $auditor = array_column($model->getAuditorStep(), 'canAuditStep');
        if ($params ['comments']) {
            foreach ($params ['comments'] as $key => $value) {
                if (in_array($value ['currentAuditStep'], $auditor)) {
                    if (FinAccountMsgModel::updateAuditMsg($value ['transferNo'], $value ['currentAuditStep'], $value ['comment'], $_SESSION ['userId'])) {
                        $msg = L('更新批注成功');
                        $flag = true;
                    } else {
                        $msg = L('更新批注失败,数据写入失败');
                        $flag = false;
                        break;
                    }
                } else {
                    $msg = L('无权限更新批注');
                    break;
                }
            }
        }
        if ($flag == true) {
            $data ['msg'] = $msg;
            $response = $this->formatOutput('2000', 'success', $data);
        } else {
            $data ['msg'] = $msg;
            $response = $this->formatOutput('3000', 'fail', $data);
        }

        return $this->ajaxReturn($response, 'json');
    }

    /**
     * 审核(用于财务人员审核步骤)
     * @param $data 企业微信审核参数
     * @throws Exception
     */
    public function audit($wechat_data = [])
    {
        if (empty($wechat_data)) {
            $params = ZUtils::filterBlank($this->getParams()['data']);
        } else {
            $params = $wechat_data;
        }
        $model = new TbWmsAccountTransferModel();
        $model->id = $params ['id'];
        $ret = $approve_ret = $model->where($model->params)->find();
        $flag = true;
        $state = '';
        if ($model->checkAuditAuth($ret['current_step'], $ret ['audit_step']) and $ret ['state'] == $model->initState) {
            try {
                $model->startTrans();
                $curStep = $ret ['current_step'];//当前审核节点
                $ret ['current_step'] = $ret ['current_step'] + 1;
                $ret ['current_audit_step'] = $ret ['current_audit_step'] + 1;
                if ($params ['agree'] == false) {
                    $ret ['state'] = $model->failState;
                    $ret ['audit_reason'] = $params ['auditReason'];
                    $flag = false;
                } else {
                    if ($ret ['current_audit_step'] == $ret ['audit_step']) {
//                        $ret ['state'] = $model::TRANSFER_WAIT_PAY;
                        $ret ['state'] = $model::TRANSFER_WAIT_ACCOUNTING;//待会计审核
                    }
                }
                if ($model->save($model->create($ret, 2))) {
                    if ($params ['agree'] == false) {
                        $msg = $params ['auditReason'];
                    } else {
                        //最后一个审核人审核创建付款单
                        if ($ret['audit_step'] == $ret['current_audit_step']) {
                            $payment_audit_id = (new TransferPaymentService())->createPaymentAuditBill($ret);
                            $save_res = $model->where(['id'=>$ret['id']])->save(['payment_audit_id'=>$payment_audit_id]);
                            if (false === $save_res) {
                                throw new Exception(L('关联付款单失败'));
                            }
                            if ($payment_audit_id){
                                // 创建付款单  - 关联交易，关联交易（间接）发起企业微信审核
                                (new PurPaymentService())->purPaymentWechatApproval($payment_audit_id);
                            }

                        }
                        $msg = $params ['comment'];
                    }
                    // 批注写入
                    FinAccountMsgModel::writeAuditMsg($ret ['transfer_no'], $msg, $curStep);
                    $bankModel = new TbWmsAccountBankModel();
                    $payAccountInfo = BaseModel::convertUnder($bankModel->getAccountBankInfo($ret ['pay_company_code'], $ret ['pay_open_bank'], $ret ['pay_account_bank'], $ret ['payment_company_class_cd']));
                    $recAccountInfo = BaseModel::convertUnder($bankModel->getAccountBankInfo($ret ['pay_company_code'], $ret ['pay_open_bank'], $ret ['pay_account_bank'], $ret ['payment_company_class_cd']));
                    $code = 2000;
                    $info = L('审核完成');
                    $data = [];
                    // 发送邮件给申请人
                    $state = $flag ? L('同意') : L('拒绝');
                    $templateData ['auditPerson'] = $_SESSION ['m_loginname'];
                    $templateData ['auditReason'] = $ret ['audit_reason'];
                    $templateData ['state'] = $state;
                    $templateData ['flag'] = $flag;
                    $context = $this->getTemplate('Finance:applyMailTemplate', $templateData);
                    $createUser = $model->getAuditName($ret ['create_user']);
                    $applyUserMail = $createUser . '@gshopper.com';
                    if (!$this->sendMail($applyUserMail, L('资金划转进度提醒'), $context))
                        throw new Exception(L('发送至') . $createUser . L('失败'));
                    $context = null;
                    // 成功则发送邮件给下一位审核人
                    if ($flag) {
                        $ret ['attachment'] = json_decode($ret ['attachment'], true);
                        foreach ($ret ['attachment'] as $key => &$value) {
                            $value ['resource'] = $this->packStreamFile($value ['saveName']);
                        }
                        $ret ['create_user']       = $createUser;
                        $ret ['currency_code']     = $model->getCurrencyVal($ret ['currency_code']);
                        $ret ['pay_currency_code'] = CommonDataModel::currencyExtend() [$payAccountInfo ['currencyCode']];
                        $ret ['rec_currency_code'] = CommonDataModel::currencyExtend() [$recAccountInfo ['currencyCode']];
                        $ret ['url']               = C('redirect_audit_addr') . '/index.php?m=finance&a=transferApp&id=' . $ret ['id'];
                        $ret ['comments']          = BaseModel::convertUnder(FinAccountMsgModel::getAuditMsg($ret ['transfer_no']));
                        $context                   = $this->getTemplate('Finance:auditMailTemplate', $ret);
                        if ($ret['current_audit_step'] < $ret['audit_step']) {
                            //最后一步审核不向下一个审核人发送审核邮件
                            $auditUserMail = CommonDataModel::auditPerson() [$ret ['current_audit_step']]['ETC'];
                            if (!$this->sendMail($auditUserMail, L('资金划转审批提醒'), $context)) {
                                $msg = L('发送审核邮件失败');
                            } else {
                                $msg = L('发送审核邮件成功');
                            }
                            $mainMsg = L('向:') . '(' . explode('@', CommonDataModel::auditPerson() [$ret ['current_audit_step']]['ETC'])[0] . ')' . $msg;
                            $this->accountLog->createLog($ret ['transfer_no'], $mainMsg, json_encode($ret));
                        }

                        $pushParams = [
                            'description' => L('审批通过提醒'),
                            'title' => L('资金划转审批反馈'),
                            'ticker' => L('资金划转审批反馈'),
                            'text' => $_SESSION ['EMP_SC_NM'] ? $_SESSION ['EMP_SC_NM'] : $_SESSION ['emp_sc_nm'] . L('通过了您编号为') . $ret ['transfer_no'] . L('的资金划转流程'),
                            'transferNo' => $ret ['transfer_no'],
                            'idVal' => $ret ['id'],
                            'alias' => $this->getUserInfoByName(explode('@', $createUser)[0])['M_ID']
                        ];
                        B('UMessagePush', $pushParams);
                        if ($ret ['state'] != $model::TRANSFER_WAIT_PAY) {
                            $pushParams = null;
                            $pushParams = [
                                'description' => L('待审批提醒'),
                                'title' => L('资金划转待审批提醒'),
                                'ticker' => L('资金划转待审批提醒'),
                                'text' => L('编号为') . $ret['transfer_no'] . L('的资金划转申请需要您审批'),
                                'transferNo' => $ret ['transfer_no'],
                                'idVal' => $ret ['id'],
                                'alias' => $this->getUserInfoByName(explode('@', $auditUserMail)[0])['M_ID']
                            ];
                            B('UMessagePush', $pushParams);
                        }

                        //企业微信审批
                        if ($approve_ret['current_audit_step']+1 < $approve_ret['audit_step']) {
                            $send_email = CommonDataModel::auditPerson()[$approve_ret['current_audit_step']+1]['ETC'];
                            if ($send_email == 'Yeogirl.Yun@gshopper.com') {
                                @SentinelModel::addAbnormal('划转审批企业微信发给了老板，已拦截', $approve_ret['transfer_no'],[$send_email,$approve_ret],'fin_notice');
                            } else {
                                (new FinanceService())->bulidTransferWechatApproval($approve_ret['id'], $send_email);
                            }
                        }

                    } else {
                        $pushParams = [
                            'description' => L('审批失败提醒'),
                            'title' => L('资金划转审批反馈'),
                            'ticker' => L('资金划转审批反馈'),
                            'text' => $_SESSION ['EMP_SC_NM'] ? $_SESSION ['EMP_SC_NM'] : $_SESSION ['emp_sc_nm'] . L('拒绝了您编号为') . $ret ['transfer_no'] . L('的资金划转流程'),
                            'transferNo' => $ret ['transfer_no'],
                            'idVal' => $ret ['id'],
                            'alias' => $this->getUserInfoByName(explode('@', $createUser)[0])['M_ID']
                        ];
                        B('UMessagePush', $pushParams);
                    }
                    $model->commit();
                } else {
                    $model->rollback();
                    $code = 3000;
                    $info = $model->db->getError();
                    $data = [];
                }
            } catch (Exception $e) {
                $model->rollback();
                $code = 3000;
                $info = $e->getMessage();
                $data = [];
            }

        } else {
            $code = 3000;
            $info = L('当前申请不能审核');
            $data = [];
        }
        $this->accountLog->createLog($ret ['transfer_no'], $info, null, 2);
        if (empty($wechat_data)) {
            $response = $this->formatOutput($code, $info, $data);
            Logs([$code, $info, $data,$response],__FUNCTION__,__CLASS__);
            ob_end_clean();
            $this->ajaxReturn($response, 'json');
        } else {
            return [
                'code' => $code,
                'msg' => $info,
            ];
        }
    }

    /**
     * 模板获取
     *
     * @param string $templateName 模板名
     * @param array $data 模板数据
     *
     * @return string 模板
     */
    public function getTemplate($templateName, $data)
    {
        $data ['amount_money'] = format_for_currency($data ['amount_money']);
        $this->assign('data', $data);
        return $this->fetch($templateName);
    }

    /**
     * 邮件发送
     *
     */
    private function sendMail($mail, $msg, $content)
    {
        return $this->mail->sendEmail($mail, $msg, $content);
    }

    /**
     * 付款确认已经放到付款单处理流程
     * 转账操作
     */
    public function payConfirm()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $model = new TbWmsAccountTransferModel();
        $model->id = $params ['id'];
        $model->payTransferTime = $params ['payTransferTime'];
        $model->payActualMoney = $params ['payActualMoney'];
        $model->payReason = $params ['payReason'];
        $ret = $model->where('id = %d', [$model->id])->find();
        try {
            if ($ret ['state'] == $model::TRANSFER_WAIT_PAY) {
                $model->currentStep = $ret ['current_step'] + 2;
                $model->state = $model::TRANSFER_WAIT_REC;
                if ($_FILES) {
                    $saveTmp = [];
                    if ($imgInfo = $this->uploadFile($_FILES ['payVoucherFile'])) {
                        foreach ($imgInfo as $key => $value) {
                            $saveTmp [$key]['baseName'] = $value ['name'];
                            $saveTmp [$key]['saveName'] = $value ['savename'];

//                            $saveTmp [$key]['name'] = $value ['name'];
//                            $saveTmp [$key]['savename'] = $value ['savename'];
                        }
                        $model->payVoucherFile = json_encode($saveTmp, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($model->save($model->create($model->params, 2))) {
                    $transfer_info = M('fin_account_transfer', 'tb_')->find($params ['id']);
                    $account_transfer_no = 'LS' . date(Ymd) . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
                    $field = ['swift_code', 'currency_code'];
                    $pay_bank_info = M('fin_account_bank', 'tb_')->field($field)->find($ret['pay_account_bank_id']);
                    $collection_bank_info = M('fin_account_bank', 'tb_')->field($field)->find($ret['rec_account_bank_id']);

                    $code = 2000;
                    $info = L('转账成功');
                    $data = [];
                    $r ['companyCode'] = $ret ['pay_company_code'];
                    $r ['openBank'] = $ret ['pay_open_bank'];
                    $r ['accountBank'] = $ret ['pay_account_bank'];
                    $r ['transferNo'] = $ret ['transfer_no'];
                    $r ['transferTime'] = $model->payTransferTime;
                    $r ['amountMoney'] = $model->payActualMoney;
//                    $r ['currencyCode'] = $ret ['currency_code'];
                    $r ['currencyCode'] = $pay_bank_info ['currency_code'];
                    $r ['companyName'] = $ret ['pay_company_name'];

                    $r ['accountTransferNo'] = $account_transfer_no;
                    $r ['oppCompanyName'] = $ret ['rec_company_name'];
                    $r ['oppOpenBank'] = $ret ['rec_open_bank'];
                    $r ['oppAccountBank'] = $ret ['rec_account_bank'];

                    $r ['transferVoucher'] = $model->payVoucherFile;
                    $r ['swiftCode'] = $pay_bank_info ['swift_code'];
                    $r ['oppSwiftCode'] = $collection_bank_info ['swift_code'];
                    $r ['remark'] = $params ['payReason'];

                    $r ['originalCurrency'] = $pay_bank_info ['currency_code'];
                    $r ['originalAmount'] = $transfer_info['pay_actual_money'];

                    $r ['otherCurrency'] = $pay_bank_info ['currency_code'];
                    $r ['otherCost'] = 0;
                    $r ['remitterCurrency'] = $pay_bank_info ['currency_code'];
                    $r ['remitterCost'] = 0;
                    $r ['tradeType'] = 1;

                    if (!$turnoverId = $this->writeToTurnOver($r, TbWmsAccountTurnoverModel::TRANSFER_PAY, TbWmsAccountTurnoverModel::TRANSFER_OUT)) {
                        $model->rollback();
                        throw new Exception(L('写入日记账出错，请重试'));
                    }

                    //划转转出写入日记账关联
                    $admin_info = M('admin', 'bbm_')->field(['EMP_SC_NM'])->find($transfer_info['audit_user']);
                    $claimModel = new TbFinClaimModel();
                    $relation = [
                        'account_turnover_id' => $turnoverId,
                        'order_type' => 'N001950300',//划转转出
//                        'order_id' => $params['orderId'],//划转没有订单id
                        'order_no' => $transfer_info['transfer_no'],
//                        'child_order_no' => $turnover_info['child_order_no'],//没有
//                        'sale_teams' => $params['saleTeams'],//划转没有销售团队
                        'claim_amount' => $transfer_info ['pay_actual_money'],
                        'created_at' => $transfer_info['audit_time'],
                        'created_by' => $admin_info['EMP_SC_NM'],
                    ];
                    $res = $claimModel->add($relation);
                    if (!$res) {
                        $model->rollback();
                        $code = 3001;
                        $info = L('划转转出写入日记账关联表失败');
                        $data ['pageData'] = $relation;
                    } else {
                        $model->commit();
                    }
                } else {
                    $model->rollback();
                    $code = 3000;
                    $info = $model->db->getError();
                    $data = [];
                }
            } else {
                $model->rollback();
                $code = 3000;
                $info = L('转账失败(状态异常)');
                $data = [];
            }
        } catch (Exception $e) {
            $model->rollback();
            $code = 3000;
            $info = $e->getMessage();
            $data = [];
        }

        $this->accountLog->createLog($ret ['transfer_no'], $info, null, 2);
        $response = $this->formatOutput($code, $info, $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 收款操作
     * TRANSFER_WAIT_REC
     * TRANSFER_SUCCESS
     */
    public function recConfirm()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $model = new TbWmsAccountTransferModel();
        $model->id = $params ['id'];
        $model->recTransferTime = $params ['recTransferTime'];
        $model->recActualMoney = $params ['recActualMoney'];
        $model->recReason = $params ['recReason'];
        $ret = $model->where('id = %d', [$model->id])->find();
        try {
            if ($ret ['state'] == $model::TRANSFER_WAIT_REC) {
                $model->currentStep = $ret ['current_step'] + 1;
                $model->state = $model::TRANSFER_SUCCESS;
                $model->startTrans();
                if ($_FILES) {
                    $saveTmp = [];
                    if ($imgInfo = $this->uploadFile($_FILES ['recVoucherFile'])) {
                        foreach ($imgInfo as $key => $value) {
                            $saveTmp [$key]['baseName'] = $value ['name'];
                            $saveTmp [$key]['saveName'] = $value ['savename'];
                        }
                        $model->recVoucherFile = json_encode($saveTmp, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($model->save($model->create($model->params, 2))) {

                    $transfer_info = M('fin_account_transfer', 'tb_')->find($params ['id']);
                    $account_transfer_no = 'LS' . date(Ymd) . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
                    $field = ['swift_code', 'currency_code'];
                    $pay_bank_info = M('fin_account_bank', 'tb_')->field($field)->find($ret['pay_account_bank_id']);
                    $collection_bank_info = M('fin_account_bank', 'tb_')->field($field)->find($ret['rec_account_bank_id']);

                    $code = 2000;
                    $info = L('收款成功');
                    $r ['companyCode'] = $ret ['rec_company_code'];
                    $r ['openBank'] = $ret ['rec_open_bank'];
                    $r ['accountBank'] = $ret ['rec_account_bank'];
                    $r ['transferNo'] = $ret ['transfer_no'];
                    $r ['transferTime'] = $model->recTransferTime;
                    $r ['amountMoney'] = $model->recActualMoney;
//                    $r ['currencyCode'] = $ret ['currency_code'];
                    $r ['currencyCode'] = $collection_bank_info ['currency_code'];
                    $r ['companyName'] = $ret ['rec_company_name'];

                    $r ['accountTransferNo'] = $account_transfer_no;
                    $r ['oppCompanyName'] = $ret ['pay_company_name'];
                    $r ['oppOpenBank'] = $ret ['pay_open_bank'];
                    $r ['oppAccountBank'] = $ret ['pay_account_bank'];

                    $r ['transferVoucher'] = $model->recVoucherFile;
                    $r ['swiftCode'] = $collection_bank_info ['swift_code'];
                    $r ['oppSwiftCode'] = $pay_bank_info ['swift_code'];
                    $r ['remark'] = $params ['recReason'];
//                    $r ['originalCurrency'] = $pay_bank_info ['currency_code'];
//                    $r ['originalAmount'] = $transfer_info['pay_actual_money'];

                    $r ['originalCurrency'] = $collection_bank_info ['currency_code'];//改成收款银行币种和金额
                    $r ['originalAmount'] = $model->recActualMoney;

                    $r ['otherCurrency'] = $collection_bank_info ['currency_code'];
                    $r ['otherCost'] = 0;
                    $r ['remitterCurrency'] = $collection_bank_info ['currency_code'];
                    $r ['remitterCost'] = 0;
                    $r ['tradeType'] = 1;

                    if (!$turnoverId = $this->writeToTurnOver($r, TbWmsAccountTurnoverModel::TRANSFER_REC, TbWmsAccountTurnoverModel::TRANSFER_IN)) {
                        $model->rollback();
                        throw new Exception(L('写入日记账出错，请重试'));
                    }
                    $data = [];

                    //划转转入写入日记账关联
                    $admin_info = M('admin', 'bbm_')->field(['EMP_SC_NM'])->find($transfer_info['audit_user']);
                    $claimModel = new TbFinClaimModel();
                    $relation = [
                        'account_turnover_id' => $turnoverId,
                        'order_type' => 'N001950400',//划转转入
//                        'order_id' => $params['orderId'],//划转没有订单id
                        'order_no' => $transfer_info['transfer_no'],
//                        'child_order_no' => $turnover_info['child_order_no'],//没有
//                        'sale_teams' => $params['saleTeams'],//划转没有销售团队
                        'claim_amount' => $params ['recActualMoney'],
                        'created_at' => $transfer_info['audit_time'],
                        'created_by' => $admin_info['EMP_SC_NM'],
                    ];
                    $res = $claimModel->add($relation);
                    if (!$res) {
                        $model->rollback();
                        $code = 3001;
                        $info = L('划转转入写入日记账关联表失败');
                        $data ['pageData'] = $relation;
                    } else {
                        $model->commit();
                    }
                } else {
                    $model->rollback();
                    $code = 3000;
                    $info = $model->db->getError();
                    $data = [];
                }
            } else {
                $model->rollback();
                $code = 3000;
                $info = L('收款失败');
                $data = [];
            }
        } catch (Exception $e) {
            $model->rollback();
            $code = 3000;
            $info = $e->getMessage();
            $data = [];
        }

        $this->accountLog->createLog($ret ['transfer_no'], $info, null, 2);
        $response = $this->formatOutput($code, $info, $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 文件下载接口
     */
    public function downloadFile()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $model = new TbWmsAccountTransferModel();
        $model->id = $params ['id'];
        $params ['fieldName'] = BaseModel::humpToLine($params ['fieldName']);
        $ret = $model->where("id = %d", [$model->id])->find();
        if ($ret [$params ['fieldName']]) {
            $ret [$params ['fieldName']] = json_decode($ret [$params ['fieldName']], true);
            $downloadFileName = '';
            foreach ($ret [$params ['fieldName']] as $key => $value) {
                if ($value ['saveName'] == $params ['saveName']) {
                    $downloadFileName = $value ['saveName'];
                }
            }
            $fd = new FileDownloadModel();
            $fd->fname = $downloadFileName;
            try {
                if (!$fd->downloadFile()) {
                    $this->error("文件不存在");
                }
            } catch (exception $e) {
                $this->error('文件不存在');
            }
        }
        $this->error('文件不存在');
    }

    /**
     * 外部转账流水写入（20190822废弃，新的方法TbWmsAccountTurnoverModel->thrTurnOver）
     */
    public function thrTurnOver()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']);
        if (!empty($params)) {
            $model = new TbWmsAccountTurnoverModel();
            $model->startTrans();
            $handlingFee = $params['handlingFee'];
            $accountTransferNo = 'LS' . date(Ymd) . TbWmsNmIncrementModel::generateNo('LS');//生成流水号
            if (is_numeric($handlingFee) && $handlingFee != '0.00') {
                $originalMoney = bcadd($params['amountMoney'], $handlingFee, 4);
            } else {
                $handlingFee = 0;
                $originalMoney = $params['amountMoney'];
            }
            if (is_array($params ['transferVoucher'])) {
                $transferVoucher = json_encode($params ['transferVoucher'], JSON_UNESCAPED_UNICODE);
            } else {
                $voucher['name'] = $params ['transferVoucher'];
                $voucher['savename'] = $params ['transferVoucher'];
                $vouchers[] = $voucher;
                $transferVoucher = json_encode($vouchers, JSON_UNESCAPED_UNICODE);
            }
            $model->transferType = $params ['transferType'];
            $model->companyCode = $params ['companyCode'];
            $model->openBank = $params ['openBank'];
            $model->accountBank = $params ['accountBank'];
            $model->transferNo = $params ['transferNo'];
            $model->transferTime = $params ['transferTime'];
            $model->amountMoney = $params['amountMoney'];
            $model->currencyCode = $params ['currencyCode'];
            $model->payOrRec = $params ['payOrRec'];
            $model->companyName = $params ['companyName'];
            $model->childTransferNo = $params ['childTransferNo'];

            $model->accountTransferNo = $accountTransferNo;
            $model->oppCompanyName = $params ['oppCompanyName'];
            $model->oppOpenBank = $params ['oppOpenBank'];
            $model->oppAccountBank = $params ['oppAccountBank'];
            $model->transferVoucher = $transferVoucher;
            $model->swiftCode = $params ['swiftCode'];
            $model->oppSwiftCode = $params ['oppSwiftCode'];
//            $model->remark = $params ['remark'];

            $model->originalCurrency = $params ['currencyCode'];
            $model->originalAmount = $originalMoney;
            $model->otherCurrency = $params ['currencyCode'];
            $model->otherCost = $handlingFee;

            $model->remitterCurrency = $params ['currencyCode'];
            $model->remitterCost = 0.00;

            $saveModel = $model->create($model->params);

            $create_info['create_user'] = empty($params ['createUser']) ? $_SESSION['userId'] : $params ['createUser'];
            $create_info['create_time'] = $params ['createTime'];
            $create_info['remark'] = $params ['remark'];
            $data = array_merge($model->params, $create_info);
            if ($turnoverId = $model->add($data)) {
                $code = 2000;
                $info = L('写入日记账成功');
                $data ['pageData'] = BaseModel::convertUnder($saveModel);
                $params['turnoverId'] = $turnoverId;
                $claimModel = new TbFinClaimModel();
                $res = $claimModel->addPurToTurnoverRelation($params);//采购应付写入日记账关联
                if (!$res) {
                    $model->rollback();
                    $error = $claimModel->error_info;
                    $code = $error['code'];
                    $info = $error['msg'];
                    $data ['pageData'] = BaseModel::convertUnder($params);
                } else {
                    $model->commit();
                }
            } else {
                $model->rollback();
                $code = 3000;
                $info = L('写入日记账失败');
                $data ['pageData'] = BaseModel::convertUnder($model->params);
                $data ['error'] = $model->db->getError();
            }
        } else {
            $data ['pageData'] = null;
            $data ['error'] = L('参数缺失');
            $code = 3000;
            $info = L('写入日记账失败');
        }
        $model->params = null;
        $response = $this->formatOutput($code, $info, $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 收款完成，写入日记账
     *
     * @param array $data 待写入数据
     * @param string $transferType 收支方向
     * @param int $payOrRec 支出还是收入
     *
     * @return boolean
     */
    private function writeToTurnOver($data, $transferType, $payOrRec)
    {
        $model = new TbWmsAccountTurnoverModel();
        $model->transferType = $transferType;
        $model->companyCode = $data ['companyCode'];
        $model->openBank = $data ['openBank'];
        $model->accountBank = $data ['accountBank'];
        $model->transferNo = $data ['transferNo'];
        $model->transferTime = $data ['transferTime'];
        $model->amountMoney = $data ['amountMoney'];
        $model->currencyCode = $data ['currencyCode'];
        $model->payOrRec = $payOrRec;
        $model->companyName = $data ['companyName'];

        $model->account_transfer_no = $data ['accountTransferNo'];
        $model->opp_company_name = $data ['oppCompanyName'];
        $model->opp_open_bank = $data ['oppOpenBank'];
        $model->opp_account_bank = $data ['oppAccountBank'];

        $model->transfer_voucher = $data ['transferVoucher'];
        $model->swift_code = $data ['swiftCode'];
        $model->opp_swift_code = $data ['oppSwiftCode'];
        $model->remark = $data ['remark'];
        $model->original_currency = $data ['originalCurrency'];
        $model->original_amount = $data ['originalAmount'];
        $model->other_currency = $data ['otherCurrency'];
        $model->other_cost = $data ['otherCost'];
        $model->remitter_currency = $data ['remitterCurrency'];
        $model->remitter_cost = $data ['remitterCost'];
        $model->trade_type = $data ['tradeType'];

        if ($id = $model->add($model->create($model->params))) {
            return $id;
        }
        return false;
    }

    /**
     * 转账详情
     */
    public function transferDetail()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new TbWmsAccountTransferModel();
        $accountBankModel = new TbWmsAccountBankModel();
        $model->id = $params ['id'];
        $fields = [
            'at.id',
            'at.transfer_no',
            'at.state',
            'at.attachment',
            'at.pay_company_name',
            'at.pay_open_bank',
            'at.pay_account_bank',
            'at.pay_account_bank_id',
            'at.rec_company_name',
            'at.rec_open_bank',
            'at.rec_account_bank',
            'at.rec_account_bank_id',
            'at.rec_transfer_time',
            'at.rec_actual_money',
            'at.rec_voucher_file',
            'at.current_step',
            'at.current_audit_step',
            'at.audit_step',
            'at.audit_user',
            'at.audit_reason',
            'at.reason',
            'at.pay_company_code',
            'at.rec_company_code',
            'at.use',
            'at.rec_reason',
            'at.pay_transfer_time',
            'at.audit_time',
            'at.amount_money',//申请金额
            'at.pay_actual_money',//历史数据显示该字段
            'at.currency_code',//申请付款币种
            'at.pay_reason',
            'at.pay_voucher_file',
            'at.account_transfer_type',
            'at.payment_company_class_cd',
            'at.collection_company_class_cd',
            'pa.billing_date',
            '(pa.billing_amount + pa.billing_fee) as billing_amount',//实际付款金额
            'pa.billing_currency_cd',
            'pa.payment_audit_no',
            'pa.billing_voucher',
            'pa.confirmation_remark',
            'pa.status as payable_status',
            'pa.id as payment_audit_id',
            'pa.source_cd',
        ];
        $ret = (new Model())->table('tb_fin_account_transfer at')
            ->field($fields)
            ->join('left join tb_pur_payment_audit pa on pa.id = at.payment_audit_id')
            ->where(['at.id' => $params ['id']])
            ->find();
        $ret = (new PurService())->orderStatusToVal($ret,true);
        if (!empty($ret)) {
            $payAccountBankInfo = $accountBankModel->where('id = %d', [$ret ['pay_account_bank_id']])->find();
            $recAccountBankInfo = $accountBankModel->where('id = %d', [$ret ['rec_account_bank_id']])->find();
            $ret ['pay_swift_code'] = $payAccountBankInfo ['swift_code'];
            $ret ['pay_bsb_no'] = $payAccountBankInfo ['bsb_no'];
            $ret ['pay_currency_code'] = $payAccountBankInfo ['currency_code'];
            $ret ['rec_swift_code'] = $recAccountBankInfo ['swift_code'];
            $ret ['rec_bsb_no'] = $recAccountBankInfo ['bsb_no'];
            $ret ['rec_currency_code'] = $recAccountBankInfo ['currency_code'];

            $ret ['pay_actual_currency_code'] = $ret ['pay_currency_code'];

            $ret = CodeModel::autoCodeOneVal($ret, ['collection_company_class_cd', 'payment_company_class_cd']);
            $accountTransferType = array_column(CommonDataModel::accountTransferType(), 'cdVal', 'cd');
            $ret ['account_transfer_type_val'] = $accountTransferType[$ret['account_transfer_type']];
            if (!empty($ret['payment_audit_id'])) {
                $ret ['pay_actual_money']  = $ret ['billing_amount'];//实际付款金额
                $ret ['pay_transfer_time'] = $ret ['billing_date'];
                $ret ['pay_reason']        = $ret ['confirmation_remark'];
                $ret ['pay_actual_currency_code'] = $ret ['billing_currency_cd'];//实际付款币种
                if (!empty($ret['billing_voucher'])) {
                    //付款单附件格式转换成原接口需要的格式
                    $ret ['payVoucherFile'] = json_decode(str_replace(['original_name', 'save_name'], ['baseName', 'saveName'], $ret['billing_voucher']), true);
                }
            }

            $currentStep = $ret ['current_step'];
            $auditStep = $ret ['audit_step'];
            unset($ret ['pay_account_bank_id']);
            unset($ret ['rec_account_bank_id']);
            unset($ret ['current_step']);
            unset($ret ['audit_step']);
            if ($model->checkAuditAuth($currentStep, $auditStep) and $ret ['state'] == $model->initState) {
                $data ['authIdentify'] = true;
            } else {
                $data ['authIdentify'] = false;
            }
            $ret = BaseModel::convertUnder($ret);
            // 日志信息获取
            $accountBankLog = new TbWmsAccountBankLogModel();
            $accountBankLog->orderNo = $ret ['transferNo'];
            $accountBankLog->tag = ['in', [2]];
            $accountBankLog->msg = ['neq', '更新申请成功'];
            $log = $accountBankLog->where($accountBankLog->params)->group('create_user,msg')->order('create_time desc')->limit(0,5)->select();
            $create_user = array_column($log, 'create_user');
            $auditTime = $time_map = array_column($log, 'create_time');
            sort($auditTime);
            if ($ret['payableStatus'] && $ret['payableStatus'] != TbPurPaymentAuditModel::$status_accounting_audit || $currentStep > 4) {
                krsort($create_user);
                $create_user = array_values($create_user);
            } else {
                //$ret['currentAuditStep']+1
                $auditTime = array_slice($auditTime, count($auditTime ) - ($ret['currentAuditStep'] + 1));
                sort($auditTime);
                krsort($create_user);
                $create_user = array_slice($create_user, count($create_user ) - ($ret['currentAuditStep'] + 1));
                $create_user = array_values($create_user);
            }
        } else {
            $data ['authIdentify'] = false;
        }
        // 获取全部审核流程,并将审核时间追加到流程上
        $data ['auditStep'] = $model->auditStep($auditStep);
        $data ['isLastAuditor'] = $auditStep == $currentStep ? true : false;
        $allUserName = $model->allUserName;
        foreach ($data ['auditStep'] as $key => &$value) {
//            $value ['auditTime'] = $log[$key]['create_time'];
            if (!$value ['etc'] and $log [$key]) {
                $value ['etc'] = $allUserName[$create_user [$key]];
                if ($value['cd'] == TbWmsAccountTransferModel::TRANSFER_WAIT_ACCOUNTING) {
                    $value ['etc'] = TbPurPaymentAuditModel::$accounting_audit_user;
                }
                $value['auditTime'] = $auditTime[$key];
                if ($key != 0) {
                    //$value ['cdVal'] = $allUserName[$log [$key]['create_user']] . L('审批');
                }
            }
            unset($value);
        }
        // 更新流程的状态，当前节点如果是失败，则当前节点以前的状态全部成功，其他状态等待审核
        if ($ret ['state'] == $model->failState) {
            for ($i = 0; $i < ($currentStep - 1); $i++) {
                $data ['auditStep'][$i]['state'] = 2;
            }
            $data ['auditStep'][$currentStep - 1]['state'] = 3;
        } else {
            for ($i = 0; $i < $currentStep; $i++) {
                $data ['auditStep'][$i]['state'] = 2;
            }
        }
        // 文件流获取
        if ($ret ['attachment']) {
            $ret ['attachment'] = json_decode($ret ['attachment'], true);
            foreach ($ret ['attachment'] as $key => &$value) {
                $value ['resource'] = $this->packStreamFile($value ['saveName']);
                unset($value);
            }
        }
        if ($ret ['recVoucherFile'])
            $ret ['recVoucherFile'] = json_decode($ret ['recVoucherFile'], true);
        foreach ($ret as $key => $value) {
            if ($key == 'auditUser') {
                $ret ['auditUser'] = $allUserName [$value];
            }
        }
        $data ['comment'] = BaseModel::convertUnder(FinAccountMsgModel::getAuditMsg($ret ['transferNo']));
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['currentStep'] = $currentStep;
        $data ['failState'] = $ret ['state'] == $model->failState ? true : false;
        if ($ret ['state'] == $model::TRANSFER_WAIT_PAY || $ret ['state'] == $model::TRANSFER_WAIT_ACCOUNTING) {
            $data ['waitForPay'] = true;
        } else {
            $data ['waitForPay'] = false;
        }
        //处理历史待付款单子，历史单子走原来的付款逻辑
        $data ['oldForPay'] = false;
        if ($ret ['state'] == $model::TRANSFER_WAIT_PAY && empty($ret['paymentAuditId'])) {
            $data ['oldForPay'] = true;
        }
        $data ['waitForRec'] = $ret ['state'] == $model::TRANSFER_WAIT_REC ? true : false;
        $data ['recSuccess'] = $ret ['state'] == $model::TRANSFER_SUCCESS ? true : false;
        //是否有可修改批注的权限
        $data ['auditorPermissions'] = $model->getAuditorStep();
        //转账操作权限
        $data ['payPermissions'] = isset($this->access ['Finance/payPer']) ? true : false;
        //收款操作权限
        $data ['recPermissions'] = isset($this->access ['Finance/recPer']) ? true : false;
        //修改权限
        $data ['modifyPermissions'] = isset($this->access ['Finance/modifyPer']) ? true : false;
        $response = $this->formatOutput('2000', 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 多文件上传
     */
    public function mulUploadFile()
    {
        $fd = new FileUploadModel();
        if ($fd->uploadFileArr()) {
            return $fd->uploadInfo;
        }
        return false;
    }

    /**
     * 上传凭证
     *
     * @param resource $file
     *
     * @return bool
     */
    public function uploadFile($file)
    {
        $fd = new FileUploadModel();
        if ($fd->saveFile($file)) {
            return $fd->info;
        }
        return false;
    }

    /**
     * 日记账
     */
    public function turnover()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);

        $model = new TbWmsAccountTurnoverModel();
        $ret = $model->getList($params);

        $data ['pageNo'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $model->count;

        $response = $this->formatOutput(2000, 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 操作日志
     *
     */
    public function operationLog()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $_GET ['p'] = $_POST ['p'] = $params ['pageIndex'] == Null ? 1 : $params ['pageIndex'];

        $fields = [
            "create_user",
            "create_time",
            "msg",
        ];

        $model = new TbWmsAccountBankLogModel();
        $model->orderNo = $params ['orderNo'] ? ['eq', $params ['orderNo']] : '';

        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $count = $model->where($model->params)->count();

        $page = new Page($count, $size);
        $ret = $model->field($fields)->where($model->params)->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();
//        $allUserName = TbWmsAccountTransferModel::getAllUserName();
        foreach ($ret as $key => &$value) {
            $user_name = DataModel::getUserScNameById($value ['create_user']);
            if (empty($user_name)) {
                $user_name = DataModel::getUserNameById($value ['create_user']);
            }
            $value ['create_user'] = $user_name;
            unset($value);
        }
        $data ['pageNo'] = $_GET ['p'];
        $data ['pageSize'] = $size;
        $data ['totalPage'] = ceil($count / $data ['pageSize']);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $count;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 删除转账单
     *
     */
    public function delTransfer()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']);

        $model = new TbWmsAccountTransferModel();
        if ($this->getNumeric($params ['id'])) {
            $model->id = $params ['id'];
            $ret = $model->where($model->params)->find();
            if ($ret ['state'] == $model->failState) {
                if ($model->where(['id' => $model->id])->save(['state' => TbWmsAccountTransferModel::TRANSFER_DELETE])) {
                    $code = 2000;
                    $info = L('删除成功');
                } else {
                    $code = 3000;
                    $info = L('删除失败') . $model->db->getError();
                }
            } else {
                $code = 3000;
                $info = L('当前申请不能删除');
            }
        } else {
            $code = 3000;
            $info = L('请求参数异常');
        }
        $data = [];

        $response = $this->formatOutput($code, $info, $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 删除数据做数据校验
     */
    function getNumeric($val)
    {
        if (is_numeric($val)) {
            return true;
        }
        return false;
    }

    /**
     * @var array
     * 币种汇率缓存
     */
    public static $rate = [];
    public static $suffix = 'CNY';
    public static $infix = '_XCHR_AMT_';

    /**
     * 汇率获取
     *
     * @param string $currency 币种
     * @param date $date 交易日期
     *
     * @return float|null 返回汇率
     */
    public function getCurrencyRate($currency, $date)
    {
        $date = date('Ymd', strtotime($date));
        $this->assign('suffix', self::$suffix);
        if (self::$rate [$currency]) {
            return self::$rate [$currency];
        }
        $model = new Model();
        $prefix = strtoupper($currency);
        $field = $prefix . self::$infix . self::$suffix;
        $conditions ['XCHR_STD_DT'] = ['eq', $date];

        $ret = $model->table('tb_ms_xchr')
            ->field($field)
            ->where($conditions)
            ->find();

        self::$rate [$currency] = $ret [$field];

        return $ret [$field];
    }

    /**
     * 公共数据接口
     *
     */
    public function commonData()
    {
        $commonType ['currency'] = 'CommonDataModel::currency';
        $commonType ['company'] = 'CommonDataModel::company';
        $commonType ['company_open'] = 'CommonDataModel::companyOpen';//我方公司(开启)
        $commonType ['transfer'] = 'CommonDataModel::transfer';
        $commonType ['account'] = 'CommonDataModel::account';
        $commonType ['turnOver'] = 'CommonDataModel::turnOver';
        $commonType ['currentAuditor'] = 'CommonDataModel::currentAuditor';
        $commonType ['accountListState'] = 'CommonDataModel::accountListState';
        $commonType ['receiptType'] = 'CommonDataModel::collectionType';//收款类型
        $commonType ['saleTeams'] = 'CommonDataModel::saleTeams';
        $commonType ['payment_channel'] = 'CommonDataModel::paymentChannel';
        $commonType ['account_type'] = 'CommonDataModel::account';
        $commonType ['account_class_cd'] = 'CommonDataModel::accountClassCd';//账户归属
        $commonType ['supplier'] = 'CommonDataModel::supplier';//账户归属
        $commonType ['account_transfer_type'] = 'CommonDataModel::accountTransferType';//资金划转类型

        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        foreach ($params as $key => $bool) {
            if ($bool)
                $response [$key] = call_user_func_array($commonType [$key], []);
        }

        $response = $this->formatOutput(2000, 'success', $response);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 流文件包装
     *
     * @param string $fname 要发送的文件(全路径)
     *
     * @return mixed
     */
    public function packStreamFile($fname)
    {
        $basePath = ATTACHMENT_DIR_IMG;
        $fullPath = $basePath . $fname;
        $response = '';
        if (file_exists($fullPath))
            $response = base64_encode(file_get_contents($fullPath));

        return $response;
    }

    /**
     * Excel 导出
     */
    public function exportExcel()
    {
        $params = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true));
        $export_type = $params['export_type'];
        $exportExcelModel = new ExportExcelModel();
        unset($params['export_type']);
        if ($export_type == 'relation') {
            //日记账关联导出
            $model = new TbFinClaimModel();
            $ret = $model->getList($params['data']['query'], true);
            foreach ($ret as $k => &$v) {
                $v['claimAmount'] = number_format($v['claimAmount'], 2);
            }
            $exportExcelModel->attributes = [
                'A' => ['name' => L('流水ID'), 'field_name' => 'accountTransferNo'],
                'B' => ['name' => L('流水类型'), 'field_name' => 'transactionType'],
                'C' => ['name' => L('收支方向'), 'field_name' => 'transferName'],
                'D' => ['name' => L('主订单号'), 'field_name' => 'orderNo'],
                'E' => ['name' => L('子订单号'), 'field_name' => 'childOrderNo'],
                'F' => ['name' => L('销售团队'), 'field_name' => 'saleTeamsName'],
                'G' => ['name' => L('币种'), 'field_name' => 'currencyName'],
                'H' => ['name' => L('金额'), 'field_name' => 'claimAmount'],
                'I' => ['name' => L('我方账户名称'), 'field_name' => 'companyName'],
                'J' => ['name' => L('我方银行'), 'field_name' => 'openBank'],
                'K' => ['name' => L('我方银行账号'), 'field_name' => 'accountBank'],
                'L' => ['name' => L('对方账户名称'), 'field_name' => 'oppCompanyName'],
                'M' => ['name' => L('对方银行'), 'field_name' => 'oppOpenBank'],
                'N' => ['name' => L('对方银行账号'), 'field_name' => 'oppAccountBank'],
                'O' => ['name' => L('发生日期'), 'field_name' => 'transferTime'],
                'R' => ['name' => L('银行参考号'), 'field_name' => 'bank_reference_no'],
                'S' => ['name' => L('(银行返回的)付款原因'), 'field_name' => 'bank_payment_reason'],
                'T' => ['name' => L('备注'), 'field_name' => 'remark'],
                'P' => ['name' => L('关联人'), 'field_name' => 'createdBy'],
                'Q' => ['name' => L('创建时间'), 'field_name' => 'createAt'],
            ];
        } else {
            //日记账导出
            $model = new TbWmsAccountTurnoverModel();
            $ret = $model->getList($params['data']['query'], true);
            foreach ($ret as $k => &$v) {
                $v['amountMoney'] = number_format($v['amountMoney'], 2);
            }
            $exportExcelModel->attributes = [
                'A' => ['name' => L('流水ID'), 'field_name' => 'accountTransferNo'],
                'B' => ['name' => L('流水类型'), 'field_name' => 'transactionType'],
                'C' => ['name' => L('是否关联交易'), 'field_name' => 'tradeType'],
                'D' => ['name' => L('预分方向'), 'field_name' => 'collectionType'],
                'E' => ['name' => L('币种'), 'field_name' => 'currencyCode'],
                'F' => ['name' => L('金额'), 'field_name' => 'amountMoney'],
                'G' => ['name' => L('我方账户名称'), 'field_name' => 'companyName'],
                'H' => ['name' => L('我方银行'), 'field_name' => 'openBank'],
                'I' => ['name' => L('我方银行账号'), 'field_name' => 'accountBank'],
                'J' => ['name' => L('对方账户名称'), 'field_name' => 'oppCompanyName'],
                'K' => ['name' => L('对方银行'), 'field_name' => 'oppOpenBank'],
                'L' => ['name' => L('对方银行账号'), 'field_name' => 'oppAccountBank'],
                'M' => ['name' => L('发生日期'), 'field_name' => 'transferTime'],
                'N' => ['name' => L('创建时间'), 'field_name' => 'createTime'],
                'O' => ['name' => L('银行参考号'), 'field_name' => 'bank_reference_no'],
                'P' => ['name' => L('(银行返回的)付款原因'), 'field_name' => 'bank_payment_reason'],
                'Q' => ['name' => L('备注'), 'field_name' => 'remark'],
            ];
        }
        $exportExcelModel->data = $ret;
        $exportExcelModel->export();
    }

    /**
     * 格式化输出
     *
     * @param int $code 状态码
     * @param string $info 提示信息
     * @param array $data 返回数据
     *
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg' => $info,
            'data' => $data
        ];

        return $response;
    }

    public function outputExcel()
    {
        $export_params = $_POST['export_params'];
        $data = json_decode($export_params, true);
        $ids = $this->getIds($data);
        $finance_service = new FinanceService();
        $finance_service->getFinanceExcelAttr();
        $finance_service->outputExcel(
            $finance_service->exp_title,
            $finance_service->exp_cell_name,
            $finance_service->getAccounts($ids));
    }

    /**
     * @return mixed
     */
    private function getIds($data)
    {
        return $this->bankAccountList($data['data']['query']);
    }

    public function receipt_entry()
    {
        $this->assign('msg', []);
        $this->display();
    }



    /**
     * 收款流水录入
     */
    public function receiptEntry()
    {
        if (IS_POST) {
            $data = ZUtils::filterBlank($this->getParams());
            $turnoverModel = new TbWmsAccountTurnoverModel();
            $result = $turnoverModel->receiptEntry($data);//写入收款流水
            if (!$result) {
                $this->ajaxReturn($turnoverModel->error_info, 'json');
            } else {
                $this->ajaxReturn($turnoverModel->success_info, 'json');
            }
        } else {
            $this->assign('msg', null);
            $this->display();
        }
    }

    //收款流水录入银行信息联动
    public function getReceiptBank()
    {
//        if (!IS_AJAX) {
//            return;
//        }
        $data = ZUtils::filterBlank($this->getParams());
        if ($data['company_code']) {
            $condition['company_code'] = ['eq', $data['company_code']];
        }
        $condition['state'] = ['eq', 1];

        $field = ['id, company_code, open_bank, account_bank'];
        $list = $account_info = M('_fin_account_bank', 'tb_')->field($field)->where($condition)->select();
        $list = assoc_unique($list, 'open_bank');//去重收款银行
        foreach ($list as $key => &$value) {
            foreach ($account_info as $v) {
                if ($value['open_bank'] == $v['open_bank']) {
                    $value['account'][] = $v['account_bank'];
                }
            }
        }
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 收款录入凭证图片
     * @return type
     */
//    public function uploadImage() {
//        if (!IS_AJAX) {
//            return;
//        }
//        $fileModel = new FileUploadModel();
//        $save_name = $fileModel->fileUploadExtend();
//        $response = $this->formatOutput(2000, 'success', $save_name);
//        $this->ajaxReturn($response, 'json');
//    }

    public function turnoverRelation()
    {
        $this->display();
    }

    /**
     * 获取日记账关联数据
     */
    public function getTurnoverRelation()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);

        $model = new TbFinClaimModel();
        $ret = $model->getList($params);

        $data ['pageNo'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $model->count;

        $response = $this->formatOutput(2000, 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 收款录入填写付款账户联想B2B客户
     */
    public function getB2bCustomer()
    {
//        if (!IS_AJAX) {
//            return;
//        }
        $name = ZUtils::filterBlank($this->getParams())['name'];
        $where = [
            'DATA_MARKING' => ['eq', '1'],
            'SP_NAME' => ['like', "%$name%"]
        ];
        $data = M('_crm_sp_supplier', 'tb_')->field(['ID, SP_NAME'])->where($where)->select();
        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    public function turnoverDetail()
    {
        $this->display();
    }

    //获取日记账详情
    public function getTurnoverDetail()
    {
        $data = ZUtils::filterBlank($this->getParams());
        $turnoverModel = new TbWmsAccountTurnoverModel();
        $result = $turnoverModel->getTurnoverDetail($data);
        if (!$result) {
            $this->ajaxReturn($turnoverModel->error_info, 'json');
        } else {
            $response = $this->formatOutput(2000, 'success', $result);
            $this->ajaxReturn($response, 'json');
        }
    }

    public function rate_page()
    {
        $this->display();
    }

    public function rate_list()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $this->ajaxReturn(FinanceModel::getRateList($params));
    }

    public function rate_edit()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $this->ajaxReturn(FinanceModel::rateEdit($params));
    }


    //批量导入付款单号核销数据
    public function importBatchCheck()
    {
        if (IS_POST) {
            $request = ZUtils::filterBlank($this->getParams());
            $packageModel = new BatchPackageImportCollectionModel();
            $result = $packageModel->import($request['type']);
            if (!$result) {
                $this->ajaxReturn($packageModel->error_info, 'json');
            } else {
                $this->ajaxReturn($packageModel->success_info, 'json');
            }
        }
    }

    //批量导入收款流水录入
    public function importRecipt()
    {
//        if (!IS_AJAX) {
//            return;
//        }
        if (IS_POST) {
            $packageModel = new PackageImportCollectionModel();
            $result = $packageModel->import();
            if (!$result) {
                $this->ajaxReturn($packageModel->error_info, 'json');
            } else {
                $this->ajaxReturn($packageModel->success_info, 'json');
            }
        }
    }

    /**
     * 下载批量导入模板
     */
    public function downloadPackage()
    {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Home/Finance/' . $name;
        Http::download($filename, $filename);
    }

    public function deleteReceipt()
    {
        try {
            $request = ZUtils::filterBlank($this->getParams());
            if ($request) {
                $this->checkDeleteReceipt($request);
            }
            $res = DataModel::$success_return;
            $res['code'] = 2000;
            $turnoverModel = new TbWmsAccountTurnoverModel();
            $turnoverModel->startTrans();
            $turnoverModel->deleteReceipt($request);
            $turnoverModel->commit();
        } catch (Exception $exception) {
            $turnoverModel->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function checkDeleteReceipt($request)
    {
        $rules = [
            'id' => 'required|numeric',
            'reason' => 'required|string|min:1|max:800',
        ];
        $attributes = [
            'id' => '流水id',
            'reason' => '删除原因',
        ];
        $this->validate($rules, $request, $attributes);
    }

    public function receipt_email_content()
    {
        return $this->fetch('email');
    }

    public function settlement_list() {
        $params = $this->jsonParams();
        $_GET['p'] = $params['p'];
        $list = (new SettlementLogic())->getList($params);
        $this->ajaxSuccess($list);
    }

    public function settlement_detail() {
        $params     = $this->params();
        $_GET['p']  = $params['p'];
        $detail     = (new SettlementLogic())->getDetail($params);
        $this->ajaxSuccess($detail);
    }

    public function update_settlement() {
        $params = $this->params();
        $settlement_l = new SettlementLogic();
        $res = $settlement_l->update($params);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$settlement_l->getError());
        }
    }

    public function delete_settlement() {
        $params = $this->params();
        $settlement_l = new SettlementLogic();
        $res = $settlement_l->delete($params['id']);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$settlement_l->getError());
        }
    }

    public function export_settlement() {
        $params         = $this->params();
        $settlement_l   = new SettlementLogic();
        $file_path      = $settlement_l->getFilePath($params['id']);
        import('ORG.Net.Http');
        Http::download($file_path, $file_path);
    }

    public function pay_order_list()
    {
        //除了配置管理-分工配置-按公司分工-付款负责人里配置的用户，其他用户进入付款单列表，默认筛选【创建人】=当前登录用户
        $GeneralPaymentService = new GeneralPaymentService(null);
        $is_all = $GeneralPaymentService->getAuthByUserId(); //是否展示所有付款单
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_id', $_SESSION['user_id']);
        $this->assign('is_all', $is_all);
        $this->display();
    }

    public function pay_order_detail()
    {
        $this->display();
    }


    public function platform_bill_list()
    {
        $this->display();
    }

    public function order_cancellation_list()
    {
        $this->display();
    }
    /**
     *  关联交易列表
     */
    public function rel_trans_list()
    {
        $this->display();
    }


    public function platform_bill_data()
    {
        $params = $this->jsonParams();
        $_GET['p'] = $params['pageNo'];
        $model = new TbPlatformBillModel();
        $ret = $model->getList($params['data']['query']);
        $data ['pageNo'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $model->count;

        $response = $this->formatOutput(2000, 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    //TPP核销列表
    public function platform_bill_cancellation_data()
    {
        $params = $this->jsonParams();
        $_GET['p'] = $params['pageNo'];
        $model = new TbPlatformBillModel();
        $ret = $model->getBillCancellationList($params['data']['query']);
        $data ['pageNo'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $ret;
        $data ['parmeterMap'] = $params;
        $data ['totalCount'] = $model->count;
        $data ['in_come_amounts'] = number_format($model->in_come_amounts, 2);
        $data ['bill_amount_counts'] = number_format($model->bill_amount_counts, 2);
        $data ['wait_cancellation_amounts'] = number_format($model->wait_cancellation_amounts, 2);

        $response = $this->formatOutput(2000, 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    //tpp核销列表数据汇总
    public function platform_bill_count()
    {
        $params = $this->jsonParams();
        $model = new TbPlatformBillModel();
        $model->getPlatformBillCount($params['data']['query']);
        $data ['parmeterMap'] = $params;
        $data ['in_come_amounts'] = number_format($model->in_come_amounts, 2);
        $data ['bill_amount_counts'] = number_format($model->bill_amount_counts, 2);
        $data ['wait_cancellation_amounts'] = number_format($model->wait_cancellation_amounts, 2);

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    //订单核销详情
    public function platform_bill_cancellation_detail()
    {
        $params = $this->jsonParams();
        if (empty($params['data']['query']['thr_order_no'])) {
            $this->ajaxError([], '平台订单号缺失');
        }
        $model = new TbPlatformBillModel();
        $ret = $model->getBillCancellationDetail($params['data']['query']);
        $data = $ret;
        $data ['in_come_amounts'] = number_format($model->in_come_amounts, 2, '.', '');
        $data ['bill_amount_counts'] = number_format($model->bill_amount_counts, 2, '.', '');
        $data ['wait_cancellation_amounts'] = number_format($model->wait_cancellation_amounts, 2, '.', '');

        $response = $this->formatOutput(2000, 'success', $data);

        $this->ajaxReturn($response, 'json');
    }

    //导出
    public function platform_bill_cancellation_export()
    {
        //导出与列表页参数格式不同
        $params = $this->params();
        $params = json_decode($params['post_data'], true);
        $model = new TbPlatformBillModel();
        $list = $model->getBillCancellationList($params['data']['query'], true);
        $map = [
            ['field_name' => 'order_no', 'name' => '平台订单号'],
            ['field_name' => 'platform_name', 'name' => '平台'],
            ['field_name' => 'site_name', 'name' => '站点'],
            ['field_name' => 'store_name', 'name' => '店铺'],
            ['field_name' => 'sale_team_name', 'name' => '销售团队'],
            ['field_name' => 'company_name', 'name' => '我方注册公司'],
            ['field_name' => 'currency', 'name' => '交易币种'],
            ['field_name' => 'in_come_amount', 'name' => 'ERP收入（交易币种）'],
            ['field_name' => 'zd_date', 'name' => 'ERP收入更新时间'],
            ['field_name' => 'bill_amount_count', 'name' => '已核销收入（交易币种）'],
            ['field_name' => 'created_at', 'name' => '核销金额更新时间'],
            ['field_name' => 'wait_cancellation_amount', 'name' => '待核销收入（交易币种）'],
        ];

        $this->exportCsv($list, $map);
    }

    public function platform_bill_error_export()
    {
        try {
            $params = I('get.');
            $this->import_bill($params, '', 'Y');
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $this->ajaxError($params, $info);
        }
    }

    public function platform_bill_add()
    {
        try {
            $params = $this->jsonParams();
            $model = new TbPlatformBillModel();
            $model->startTrans();
            $res = $model->platform_bill_add($params);
            if ($res) {
                //有上传文件url
                if (!empty($params['arrange_bill'][0])) {
                    $path = $params['arrange_bill'][0];
                    if (empty($path['savename']) || empty($path['savepath'])) {
                        $this->ajaxError($res, '标准核销表格URL缺失');
                    }
                    $res = $this->import_bill($path, $res, $params['need_export_err_report']);
                    if (!$res) {
                        $model->rollback();
                        $this->ajaxError($res, '标准核销表格保存失败', 4000);
                    }
                }
            } else {
                $model->rollback();
                $this->ajaxError($res, '保存失败');
            }
            $model->commit();
            $this->ajaxSuccess($res, '保存成功');
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $this->ajaxError($res, $info);
        }

    }

    //审核账单
    public function platform_bill_save()
    {
        $params = $this->jsonParams();
        if (empty($params['data']['platform_bill_id'])) {
            $this->ajaxError([], '平台账单ID缺失');
        }
        $model = new TbPlatformBillModel();
        $res = $model->platform_bill_save($params['data']);
        if (!$res) {
            $this->ajaxError($res, 'success');
        }
        $this->ajaxSuccess($res, 'success');
    }

    /**
     * @name excel导入账单信息
     */
    public function import_bill($path = null, $platform_bill_id = null, $is_export = 'N')
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'] ? $_FILES['file']['savepathtmp_name'] : $path['savepath']. $path['savename'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                throw new Exception(L('请上传EXCEL文件'));
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $data = $orders = [];
        $model = new TbPlatformBillModel();
        $params = $this->jsonParams();
        if ($is_export === 'Y') {
            $params['platform_code'] = $path['platform_code'];
            $params['store_id'] = $path['store_id'];
        }
        $platform_bill_no = $model->createPlatformBillNO();
        //从第三行开始读数据
        $error_report_msg_list = []; $has_error_flag = false;
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $thr_order_no = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $bill_amount  = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $error_report_msg['thr_order_no'] = $thr_order_no;
            $error_report_msg['bill_amount'] = $bill_amount;
            $error_report_msg['reason'] = '数据合格';
            $error_report_msg['current_row'] = $currentRow;
            if (!empty($thr_order_no) && empty($bill_amount)) {
                $has_error_flag = true;
                $error_report_msg['reason'] = '账单金额不可为空';
                // throw new Exception(L('第' . $currentRow . '行数据不全'));
            }
            if (empty($thr_order_no) && !empty($bill_amount)) {
                $has_error_flag = true;
                $error_report_msg['reason'] = '平台订单号不可为空';
            }
            if (empty($thr_order_no) && empty($bill_amount)) {
                continue;
            }
            $data[] = [
                'platform_bill_id' => $platform_bill_id,
                'platform_bill_no' => $platform_bill_no,
                'thr_order_no'     => $thr_order_no,
                'bill_amount'      => $bill_amount,
                'created_by'       => $_SESSION['m_loginname'],
                'updated_by'       => $_SESSION['m_loginname'],
                'bill_status'      => 'N003180001',
            ];
            $orders[] = [
                'plat_cd'      => $params['platform_code'],
                'store_id'      => $params['store_id'],
                'thr_order_id' => $thr_order_no,
                'current_row'  => $currentRow,
            ];
            $error_report_msg_list[] = $error_report_msg;
            unset($error_report_msg);
        }
        $Model = M();
        $order_where['ORDER_ID'] = ['in', array_column($orders, 'thr_order_id')];
        $order_where['PLAT_CD'] = $params['platform_code'];
        $op_orders = $Model->table('tb_op_order')->field('ID,ORDER_ID,STORE_ID')->where($order_where)->select();
        $order_map = [];
        foreach ($op_orders as $v) {
            $order_map[$v['ORDER_ID'].$v['STORE_ID']] = $v['ID'];
        }
        foreach ($orders as $key => $v) {
            $order_inc_id = $order_map[$v['thr_order_id'].$v['store_id']];
            if (empty($order_inc_id)) {
                $has_error_flag = true;
                $error_report_msg_list = $this->getLineErrMsg($v['current_row'], $error_report_msg_list, '订单号和平台店铺不符合');
                //throw new Exception(L('第' . $v['current_row'] . '行数据不全或订单号和平台店铺不符合:' . $v['thr_order_id']));
            } else {
                $data[$key]['order_inc_id'] = $order_inc_id;
            } 
        }
        
        if ($is_export === 'Y') {
            if ($error_report_msg_list) {
                $exportExcelModel = new ExportExcelModel();
                $exportExcelModel->title = '平台订单号,账单金额皆为必填，账单金额正数代表收款，负数代表退款';
                $exportExcelModel->attributes = [
                    'A' => ['name' => L('平台订单号'), 'field_name' => 'thr_order_no'],
                    'B' => ['name' => L('账单金额'), 'field_name' => 'bill_amount'],
                    'C' => ['name' => L('报错原因'), 'field_name' => 'reason'],
                ];
                $exportExcelModel->data = $error_report_msg_list;
                $exportExcelModel->fileName = date('YmdHis', time()) . '.xls';
                $exportExcelModel->export();
            } else {
                throw new Exception(L('缺失数据，无法导出'));
            }   
        } else {
            $res = false;
            if (!$has_error_flag) {
                $model = M('platform_bill_cancellation', 'tb_');
                $res = $model->addAll($data);
            }
            return $res;
        }
        
    }

    // 获取更新excel的报错信息(订单号+平台)
    public function getLineErrMsg($line, $list, $error_msg)
    {
        foreach ($list as $key => &$value) {
            if ($line == $value['current_row'] && $value['reason'] == '数据合格') { // 其余情况保留原样
                $value['reason'] = $error_msg;
            }
        }
        return $list;
    }

    //根据站点获取平台
    public function sub_basis()
    {
        $params = $this->jsonParams();
        $data = CommonDataModel::subBasis($params['data']['site_code']);
        $this->ajaxSuccess($data, 'success');
    }

    //根据平台获取店铺
    public function get_store()
    {
        $params = $this->jsonParams();
        $plat = $params['data']['plat_code'];
        if (!empty($plat) && !is_array($plat)) {
            $plat = (array)$plat;
        }
        $conditions ['_string'] = CommonDataModel::getLikeOrQuery($plat, 'PLAT_CD');
        $data = M('ms_store', 'tb_')->where($conditions)->field('ID,STORE_NAME,MERCHANT_ID')->select();
        $this->ajaxSuccess($data, 'success');
    }

    //根据平台获取账单时间区间
    public function get_platform_bill()
    {
        $params = $this->jsonParams();
        $code = $params['data']['code'];
        $query_column = $params['data']['query_column'];
        if (!empty($code) && !is_array($code)) {
            $code = (array)$code;
        }
        $conditions ['_string'] = CommonDataModel::getLikeOrQuery($code, $query_column);
        $conditions ['bill_status'] = ['neq', TbPlatformBillModel::BILL_STATUS_NO];
        $data = M('platform_bill', 'tb_')->where($conditions)->field('s_bill_time,e_bill_time')->select();
        $this->ajaxSuccess($data, 'success');
    }

    /**
     *  关联交易列表
     */
    public function rel_trans_data()
    {
        import('ORG.Util.Page');
        $params = $this->getParams();
        $object = new RelatedTransactionService();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($params['pages']) && !empty($params['pages']['per_page']) && !empty($params['pages']['current_page'])){
            $pages = array(
                'per_page' =>$params['pages']['per_page'],
                'current_page' => $params['pages']['current_page']
            );
        }

        $where = $object->mergeWhere($params['search']);
        list($list, $count) = $object->getList($where,$pages);
        if (empty($count)) {
            $list = [];
        }
        $data = ['data' => $list, 'page' => ['total_rows' => $count]];
        $this->ajaxSuccess($data);
    }


    /**
     * 导出
     */
    public function rel_trans_export()
    {
        $params = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true));
        $object = new RelatedTransactionService();
        $where = $object->mergeWhere($params['search']);
        $esData = $object->getExportList($where);
        if (empty($esData)){
            echo "无数据";
            die; 
        }
        $exportExcel = new ExportExcelModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('关联交易订单号'), 'field_name' => 'rel_trans_no'],
            $key++ => ['name' => L('触发操作'), 'field_name' => 'trigger_type_val'],
            $key++ => ['name' => L('销售公司'), 'field_name' => 'sell_company_cd_val'],
            $key++ => ['name' => L('采购公司'), 'field_name' => 'pur_company_cd_val'],
            $key++ => ['name' => L('SKU'), 'field_name' => 'sku_id'],
            $key++ => ['name' => L('条形码'), 'field_name' => 'upc_id'],
            $key++ => ['name' => L('商品名称'), 'field_name' => 'GUDS_NM'],
            $key++ => ['name' => L('商品属性'), 'field_name' => 'GUDS_OPT_VAL_MPNG'],
            $key++ => ['name' => L('数量'), 'field_name' => 'sku_quantity'],
            $key++ => ['name' => L('交易币种'), 'field_name' => 'rel_currency_cd_val'],
            $key++ => ['name' => L('交易价格'), 'field_name' => 'rel_price'],
            $key++ => ['name' => L('交易时间'), 'field_name' => 'rel_time'],
            $key => ['name' => L('操作人'), 'field_name' => 'operation_user'],
        ];
        ob_end_clean();
        $exportExcel->data = $esData;
        $exportExcel->export();
    }

    //一般付款流

    //新增一般付款
    public function create_general_payment()
    {
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_id', $_SESSION['user_id']);
        $this->display('create_general_payment');
    }

    //编辑一般付款
    public function edit_general_payment()
    {
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_id', $_SESSION['user_id']);
        $this->display('edit_general_payment');
    }

    //一般付款详情
    public function general_payment_detail()
    {
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_id', $_SESSION['user_id']);
        $this->display('general_payment_detail');
    }
    //付款单审核
    public function payment_examine()
    {
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_id', $_SESSION['user_id']);
        $this->display('payment_examine');
    }

    //新增一般付款
    public function createGeneralPayment()
    {
        try {
            M()->startTrans();
            $params = $this->jsonParams();
            $GeneralPaymentService = new GeneralPaymentService(null);
            if ($params['payment_audit']['submit_type'] != 1) { //保存草稿 不用校验
                $this->verificationTrademarkInfo($params);
            }
           
            $res = $GeneralPaymentService->generalPaymentAdd($params);
            M()->commit();
            $this->ajaxSuccess($res, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    //参数校验
    public function verificationTrademarkInfo($params)
    {
        $rules = [
            //'trademark_base.trademark_type' => 'required|string|size:10',
            'payment_audit.our_company_cd' => 'required|string',
            'payment_audit.payment_currency_cd' => 'required|string',
            'payment_audit.payable_date' => 'required|string',
            'payment_audit.payment_channel_cd' => 'required|string',
            'payment_audit.payment_way_cd' => 'required|string',

            'general_payment.payment_nature' => 'required|string',
            'general_payment.contract_information' => 'required|string',
            'general_payment.settlement_type' => 'required|string',
            'general_payment.procurement_nature' => 'required|string',
            'general_payment.invoice_information' => 'required|string',
            'general_payment.bill_information' => 'required|string',
            'general_payment.payment_type' => 'required|string',
            //'general_payment.actual_fee_Department' => 'required|string',
            //'general_payment.actual_fee_department_id' => 'required|string',
            'general_payment.dept_id' => 'required|string',
        ];
        $custom_attributes = [
            'payment_audit.our_company_cd' => '我方公司',
            'payment_audit.payment_currency_cd' => '付款币种',
            'payment_audit.payable_date' => '预计付款日期',
            'payment_audit.payment_channel_cd' => '支付渠道',
            'payment_audit.payment_way_cd' => '支付方式',

            'general_payment.payment_nature' => '付款性质',
            'general_payment.contract_information' => '合同信息',
            'general_payment.settlement_type' => '结算类型',
            'general_payment.procurement_nature' => '采购性质',
            'general_payment.invoice_information' => '发票信息',
            'general_payment.bill_information' => '账单信息',
            'general_payment.payment_type' => '付款类型',
            //'general_payment.actual_fee_Department' => '实际费用归属部门',
            //'general_payment.actual_fee_department_id' => '实际费用归属部门id',
            'general_payment.dept_id' => '审批部门id',
        ];
        $general_payment = $params['general_payment'];

        if (empty($general_payment['dept_id'])) {
            throw new Exception(L('审批部门id必填'));
        }
        if ($general_payment['payment_nature'] == '1') { //【付款性质】=对公，则此项必填
            //【供应商】必填
            $rules['general_payment.supplier'] = 'required|string';
            $custom_attributes['general_payment.supplier'] = '供应商';
        }
        if ($general_payment['contract_information'] == '1' && $general_payment['payment_nature'] == '1') { //有合同 & 【付款性质】=对公，则此项必填
            //【合同编号】必填
            $rules['general_payment.contract_no'] = 'required|string';
            $custom_attributes['general_payment.contract_no'] = '合同编号';
        }
        if ($general_payment['invoice_information'] != 'N003290003') { //发票信息不是不适用
            //【发票类型】必填
            $rules['general_payment.invoice_type'] = 'required|string';
            $custom_attributes['general_payment.invoice_type'] = '发票类型';
        }
        $baseModel = new Model();
        $payment_type_cd = $baseModel->table('tb_ms_cmn_cd')->where('CD = "%s"', $general_payment['payment_type'])->find();
        $payment_audit = $params['payment_audit'];
        if ($payment_audit['payment_channel_cd'] == 'N001000301') { //支付渠道为银行

            //当付款类型对应数据字典【费用类型】的Comment2=员工个人账户 & 支付渠道=银行 时，申请付款表单页和后续付款单详情页【收款方账户/订单信息】隐藏，相关字段无需填写。
            if ($payment_type_cd['ETC2'] != '员工个人账户') {
                //【支付渠道】=银行 & 【支付方式】=转账
                if ($payment_audit['payment_way_cd'] == 'N003020001') { //支付方式为转账
                    //【该渠道收款账户名】【该渠道收款账号】都必填 新增验证逻辑 #10691 应付确认/付款申请提交增加校验
                    //①【确认后-SWIFT CODE】和【确认后-收款银行本地结算代码】至少填一个，当然可以都填。
                    //②【确认后-SWIFT CODE】如果填了，则必填8位或11位字符串。
                    if (empty($payment_audit['supplier_swift_code']) && empty($payment_audit['bank_settlement_code'])) {
                        throw new Exception(L('【SWIFT CODE】和【收款银行本地结算代码】至少填一个'));
                    }
                    if (!empty($payment_audit['supplier_swift_code']) && !preg_match('/^.{8}$|^.{11}$/', $payment_audit['supplier_swift_code'])) {
                        throw new Exception(L('【SWIFT CODE】必填8位或11位字符串。'));
                    };
                }
                //【收款账户名】【收款账户开户行】【收款银行账号】【收款银行 SWIFT CODE】都必填
                $rules['payment_audit.supplier_collection_account'] = 'required|string';
                $rules['payment_audit.supplier_opening_bank'] = 'required|string';
                $rules['payment_audit.supplier_card_number'] = 'required|string';
                //$rules['payment_audit.supplier_swift_code'] = 'required|string';
                //$rules['payment_audit.bank_settlement_code'] = 'required|string';
                $rules['payment_audit.bank_address'] = 'required|string';
                $rules['payment_audit.city'] = 'required|string';
//              $rules['payment_audit.bank_address_detail'] = 'required|string';
//              $rules['payment_audit.bank_postal_code'] = 'required|string';
                $rules['payment_audit.account_currency'] = 'required|string';
//              $rules['payment_audit.account_type'] = 'required|string';
                $custom_attributes['payment_audit.supplier_collection_account'] = '收款账户名';
                $custom_attributes['payment_audit.supplier_opening_bank'] = '收款账户开户行';
                $custom_attributes['payment_audit.supplier_card_number'] = '收款银行账号';
                //$custom_attributes['payment_audit.supplier_swift_code'] = '收款银行 SWIFT CODE';
                //$custom_attributes['payment_audit.bank_settlement_code'] = '收款银行本地结算代码';
                $custom_attributes['payment_audit.bank_address'] = '收款银行地址';
                $custom_attributes['payment_audit.city'] = '收款银行地址ID';
//              $custom_attributes['payment_audit.bank_address_detail'] = '收款银行详细地址';
//              $custom_attributes['payment_audit.bank_postal_code'] = '收款银行邮编';
                $custom_attributes['payment_audit.account_currency'] = '收款账号币种';
//              $custom_attributes['payment_audit.account_type'] = '收款账户种类';
            }
        } else if ($payment_audit['payment_channel_cd'] == 'N001000300') { //支付渠道为线下支付

        } else {
            if ($payment_audit['payment_way_cd'] == 'N003020001') { //支付方式为转账
                //【该渠道收款账户名】【该渠道收款账号】都必填
                $rules['payment_audit.collection_user_name'] = 'required|string';
                $rules['payment_audit.collection_account'] = 'required|string';
                $custom_attributes['payment_audit.collection_user_name'] = '该渠道收款账户名';
                $custom_attributes['payment_audit.collection_account'] = '该渠道收款账号';
            }
            if ($payment_audit['payment_way_cd'] == 'N003020002') { //支付方式为按订单支付
                //【平台名称】【店铺名称】【平台订单号】都必填
                $rules['payment_audit.platform_cd'] = 'required|string';
                $rules['payment_audit.store_name'] = 'required|string';
                $rules['payment_audit.platform_order_no'] = 'required|string';
                $custom_attributes['payment_audit.platform_cd'] = '平台名称';
                $custom_attributes['payment_audit.store_name'] = '店铺名称';
                $custom_attributes['payment_audit.platform_order_no'] = '平台订单号';
            }
            if ($payment_audit['payment_way_cd'] == 'N003020005') { //支付方式为按交易号退款
                //【交易号】必填
                $rules['payment_audit.trade_no'] = 'required|string';
                $custom_attributes['payment_audit.trade_no'] = '交易号';
            }
        }
        $general_payment_detail = $params['general_payment_detail'];
        //当付款类型对应数据字典【费用类型】的Comment2=员工个人账户时，所有明细的【实际费用申请人】必须相同。 需求10952 一般付款表单结构调整
        if ($payment_type_cd['ETC2'] == '员工个人账户') {
            //去重后两个以上
            if (count(array_unique(array_column($general_payment_detail, 'actual_fee_applicant'))) >= 2) {
                throw new Exception(L($payment_type_cd['CD_VAL'] . '付款请确保所有明细的实际费用申请人相同'));
            }
        }
        $amount = array_column($general_payment_detail, 'amount');
        $vat_rate = array_column($general_payment_detail, 'vat_rate');
        $sum = array_sum($amount) + array_sum($vat_rate);
        if ($sum < 0 || !$sum) {
            throw new Exception(L('一般付款详情-：支付金额和增值税总和必须大于0'));
        }
      
        if ($general_payment['invoice_information'] == 'N003290001' && empty($general_payment['invoice_attachment'])) {
            throw new Exception(L('发票附件必填'));
        }
       
        //【账单信息】=已取得账单，账单附件必填
        if ($general_payment['bill_information'] == 'N003300001' && empty($general_payment['bill_attachment'])) {
            throw new Exception(L('账单附件必填'));
        }
       
        foreach ($general_payment_detail as $key => $val) {
            if (empty($val['project_summary'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：项目摘要必填'));
            }
            if (empty($val['subdivision_type'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：细分类型必填'));
            }
            /*if (!isset($val['amount']) || $val['amount'] === '') {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：支付金额（不含增值税）必填'));
            }
            if (!isset($val['vat_rate']) || $val['vat_rate'] === '') {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：增值税必填'));
            }*/
            //【发票信息】=已取得发票，发票附件必填
            // if ($general_payment['invoice_information'] == 'N003290001' && empty($val['invoice_attachment'])) {
            //     throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：发票附件必填'));
            // }
            // //【账单信息】=已取得账单，账单附件必填
            // if ($general_payment['bill_information'] == 'N003300001' && empty($val['bill_attachment'])) {
            //     throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：账单附件必填'));
            // }
            if (empty($val['relation_bill_type'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：关联单据类型必填'));
            }
            if (empty($val['actual_fee_applicant'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：实际费用申请人必填'));
            }
            if (empty($val['actual_fee_Department'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：实际费用归属部门必填'));
            }
            if (empty($val['actual_fee_department_id'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：实际费用归属部门id必填'));
            }
            //关联单据类型非无的情况，关联单据号必填
            if ($val['relation_bill_type'] != 'N003310005' && empty($val['relation_bill_no'])) {
                throw new Exception(L('一般付款详情-第' . ($key + 1) . '行：关联单据号必填'));
            }
        }
        $this->validate($rules, $params, $custom_attributes);
    }

    /**
     * 付款单状态更新
     * @param $request_data
     * @throws Exception
     */
    public function paymentStatusUpdate()
    {
        try {
            M()->startTrans();
            $params = $this->jsonParams();
            $GeneralPaymentService = new GeneralPaymentService(null);
            if ($params['status'] == 4) { //提交付款单 验证参数
                $ret = $GeneralPaymentService->getGeneralPayment($params['payment_audit_id']);
                $this->verificationTrademarkInfo($ret);
            }
            $res = $GeneralPaymentService->updatePaymentBillStatus($params, 1);
            M()->commit();
            $this->ajaxSuccess($res, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    //编辑一般付款
    public function editGeneralPayment()
    {
        try {
            M()->startTrans();
            $params = $this->jsonParams();
            if ($params['payment_audit']['submit_type'] != 1) { //保存草稿 不用校验
                $this->verificationTrademarkInfo($params);
            }
            $GeneralPaymentService = new GeneralPaymentService(null);
            $res = $GeneralPaymentService->generalPaymentEdit($params);
            M()->commit();
            $this->ajaxSuccess($res, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    //一般付款详情
    public function generalPaymentDetail()
    {
        $params = $this->jsonParams();
        $GeneralPaymentService = new GeneralPaymentService(null);
        $res = $GeneralPaymentService->getGeneralPaymentDetail($params['payment_audit_id']);
        $this->ajaxSuccess($res, 'success');
    }

    //税号提交
    public function saveTaxNumber()
    {
        try {
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal    = RedisModel::lock('tax_number_config' . $request_data['our_company_cd'].$request_data['country_id'], 10);
            if ($request_data) {
                $this->validateTaxNumberData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new FinanceService())->saveTaxNumber($request_data);
            $model->commit();
            RedisModel::unlock('tax_number_config' . $request_data['our_company_cd'].$request_data['country_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateTaxNumberData($data)
    {
        $rules['our_company_cd'] = 'required|string|size:10';
        $rules['country_id']     = 'required|numeric';
        $rules['vat_number']     = 'required';
        $rules['tax_rate']       = 'required|numeric|min:0';
        $attributes = [
            'our_company_cd' => '我方公司',
            'country_id'     => '税号所属国',
            'vat_number'     => 'VAT号',
            'tax_rate'       => '税率'
        ];
        $this->validate($rules, $data, $attributes);
    }

    //税号列表
    public function taxNumberList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = (new FinanceService())->getTaxNumberList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function  exportTaxNumberList() {
        $request_data = ZUtils::filterBlank($_POST);
        $list = (new FinanceService())->getTaxNumberList($request_data, true)['data'];
        $map  = [
            ['field_name' => 'our_company_cd_val', 'name' => L('我方公司')],
            ['field_name' => 'country_name', 'name' => L('税号所属国')],
            ['field_name' => 'vat_number', 'name' => L('VAT号')],
            ['field_name' => 'tax_rate', 'name' => L('税率')]
        ];
        $this->exportCsv($list, $map);
    }


    /**
     * 待认领记录列表
     */
    public function waitingReceiveList()
    {
        $this->display('WaitingReceiveList');
    }

    /**
     * 待认领记录列表数据
     */
    public function waitingReceiveListData()
    {
        $params =$this->getParams();
        $finance_model = new TbFinAccountTurnoverModel();
        $result = $finance_model->getWaitingReceiveList($params);
        $this->ajaxReturn($result, 'json');
    }

    /**
     * 待认领列表点击分配方向后
     */
    public function referenceTurnoverDirection() {
        $params = $this->getParams();
        $turnover_id = $params['turnover_id'];
        if($params['collection_type'] == 'N002520100') {
            //如果是客户打款或采购退款则需要添加收款认领列表记录，否则不添加只修改日记账单的预分方向
            $insert['account_turnover_id'] = $turnover_id;
            $insert['claim_status'] = 'N002550100';//认领状态-未完结
            $insert['created_by'] = $_SESSION['m_loginname'];
            $insert['created_at'] = date("Y-m-d H:i:s");
            $insert['updated_by'] = $_SESSION['m_loginname'];
            $insert['updated_at'] = date("Y-m-d H:i:s");
            $insert['tag_by'] = $_SESSION['m_loginname'];
            $insert['tag_at'] =  date("Y-m-d H:i:s");
            M()->startTrans();
            $ret1 = M('account_turnover_status','tb_fin_')->data($insert)->add();
            $update['collection_type'] = trim($params['collection_type']);
            $update['our_remark'] = trim($params['our_remark']);
            $ret2 = M('account_turnover','tb_fin_')->where(['id'=>$turnover_id])->save($update);
            if(!($ret1 > 0 && $ret2 > 0)) {
                M()->rollback();
                $this->ajaxError([], '分配失败',3000);
            }else{
                M()->commit();
                $this->ajaxSuccess([], '分配成功',2000);
            }
        }else{
            //添加备注
            $update['our_remark'] = trim($params['our_remark']);
            $update['collection_type'] = trim($params['collection_type']);
            $ret = M('account_turnover','tb_fin_')->where(['id'=>$turnover_id])->save($update);
            if($ret > 0){
                $this->ajaxSuccess([], '分配成功',2000);
            }else{
                $this->ajaxError([], '分配失败',3000);
            }
        }

    }


    //添加备注
    public function addWaitingReceiveRemark(){
        $params = $this->params();
        $turnover_model = new TbFinAccountTurnoverModel();
        $res = $turnover_model->addRemark($params);
        if($res > 0){
            $this->ajaxSuccess([], '添加成功',2000);
        }else{
            $this->ajaxError([], '添加失败',3000);
        }
    }


    //导入数据校验及返回
    public function importFormatPaymentData(){
        $params = $this->params();
        $payment_type_select = $params['payment_type_select'];
        //$payment_type_select = 'N002930007';
        $fill_data = [];
        $format_error = [];
        if(!empty($payment_type_select)){
            //先查找付款类型的细分类型
            $pay_type_arr = M('ms_cmn_cd','tb_')->field('CD,CD_VAL')->where(['ETC' => $payment_type_select])->select();
            $bill_type_arr = M('ms_cmn_cd','tb_')->field('CD,CD_VAL')->where(['CD' =>['like', '%N00331000%']])->select();
            $pay_type_data = [];
            $bill_type_data = [];
            foreach($pay_type_arr as $pv){
                $pay_type_data[$pv['CD_VAL']] = $pv['CD'];
            }
            foreach($bill_type_arr as $bv){
                $bill_type_data[$bv['CD_VAL']] = $bv['CD'];
            }

            $excel_file = $_FILES['file']["tmp_name"];
            $excel_operate = new ExcelOperation();
            $excel_data = $excel_operate->getImportData($excel_file, 0,'',2,false,true);
            if(empty($excel_data)){
                $format_error[] = "请填写数据再导入";
            }else{
                //header('Content-type: text/html; charset=UTF8'); // UTF8不行改成GBK试试，与你保存的格式匹配
                //printr($excel_data);die;
                //$excel_row_name = $excel_operate->getImportHeader($excel_file, 0,1);
                //需要查数据库匹配的
                $users = [];
                $bill_no_exist = [];
                foreach ($excel_data as $lk=>$lv) {
                    $users[] = trim($lv[4]['cell_value']);
                    $bill_no = explode(',', trim($lv[6]['cell_value']));
                    $bill_type = $bill_type_data[trim($lv[5]['cell_value'])];
                    if(!empty($bill_type) && !empty($bill_no) && trim($lv[5]['cell_value']) != '无'){
                        $order_no_arr = $this->finOrderNo($bill_type, $bill_no);
                        $bill_no_exist[trim($lv[6]['cell_value'])] = empty($order_no_arr) ? [] : $order_no_arr;
                    }

                }


                $real_users = M('admin','bbm_')->field('M_NAME')->where(['M_NAME'=>['in', $users]])->select();
                $real_names = array_column($real_users, 'M_NAME');
                //进行一些校验
                foreach ($excel_data as $ek=>$ev) {
                    if(trim($ev[0]['cell_value']) === '' || empty(trim($ev[0]['cell_value']))){
                        $format_error[] = $ev[0]['cell_name'].'列项目摘要不能为空';
                    }

                    if(empty($pay_type_data[trim($ev[1]['cell_value'])])){
                        $format_error[] = $ev[1]['cell_name'].'列细分类型匹配失败';
                    }

                    if(trim($ev[2]['cell_value']) === ''){
                        $format_error[] = $ev[2]['cell_name'].'列支付金额不能为空';
                    } else if(!preg_match('/^[+]?\d*(\.\d+)?$/', $ev[2]['cell_value'])){
                        $format_error[] = $ev[2]['cell_name'].'列支付金额格式有误';
                    }

                    if(trim($ev[3]['cell_value']) === ''){
                        $format_error[] = $ev[3]['cell_name'].'列增值税不能为空';
                    } else if(!preg_match('/^[+]?\d*(\.\d+)?$/', $ev[3]['cell_value'])){
                        $format_error[] = $ev[3]['cell_name'].'列增值税格式有误';
                    }

                    if(empty(trim($ev[4]['cell_value']))){
                        $format_error[] = $ev[4]['cell_name'].'列费用申请人不能为空';
                    }else if(!in_array(trim($ev[4]['cell_value']), $real_names)){
                        $format_error[] = $ev[4]['cell_name'].'列费用申请人匹配失败';
                    }

                    if(preg_match('/^\d.*/',trim($ev[4]['cell_value']))){
                        $format_error[] = $ev[4]['cell_name'].'列费用申请人不能为数字或数字开头';
                    }

                    if(empty(trim($ev[5]['cell_value']))){
                        $format_error[] = $ev[5]['cell_name'].'列关联单据类型不能为空';
                    }else if(empty($bill_type_data[trim($ev[5]['cell_value'])])){
                        $format_error[] = $ev[5]['cell_name'].'列单据类型类型匹配失败';
                    }

                    if(trim($ev[5]['cell_value']) != '无'){
                        if(empty(trim($ev[6]['cell_value']))){
                            $format_error[] = $ev[6]['cell_name'].'列关联单据号不能为空';
                        }
                        if(!empty(trim($ev[6]['cell_value']))){
                            //单据号匹配，从数据库查询到的和提交的
                            $this_bill_arr = explode(',', trim($ev[6]['cell_value']));
                            $diff = array_diff($this_bill_arr, $bill_no_exist[trim($ev[6]['cell_value'])]);
                            if(!empty($diff)){
                                $diff_orders = "\n".implode("\n", $diff);
                                $format_error[] = $ev[6]['cell_name'].'列关联如下单据号匹配失败 '.$diff_orders;
                            }
                        }
                    }

                    $this_fill_data['content'] = $ev[0]['cell_value'];
                    $this_fill_data['pay_type'] = $pay_type_data[trim($ev[1]['cell_value'])];
                    $this_fill_data['pay_amount'] = $ev[2]['cell_value'];
                    $this_fill_data['tax'] = $ev[3]['cell_value'];
                    $this_fill_data['real_apply_person'] = $ev[4]['cell_value'];
                    $this_fill_data['bill_type'] = $bill_type_data[trim($ev[5]['cell_value'])];;
                    $this_fill_data['related_bill_no'] = trim($ev[5]['cell_value']) != '无' ? explode(',', str_replace('，',',', $ev[6]['cell_value'])) : '';
                    $fill_data[] = $this_fill_data;
                }
            }
        }else{
            $this->ajaxError('', '请选中付款类型',3000);
        }
        //是否要要导入，有任何一个错误则全部文件数据都不能导入
        $can_all_import = empty($format_error) ? true : false;
        $this->ajaxSuccess(['fill_data'=>$fill_data, 'format_error'=>$format_error, 'can_all_import'=>$can_all_import], '处理成功',2000);
        
    }


    //根据单据类型查询是否存在这个单号
    public function finOrderNo($relation_bill_type, $order_no){
        if ($relation_bill_type == 'N003310001') { //采购单
            $model = M('pur_order_detail', 'tb_');
            $field = 'procurement_number as relation_bill_no';
            $column = 'procurement_number';
        }
        if ($relation_bill_type == 'N003310002') { //调拨单
            $model = M('wms_allo', 'tb_');
            $field = 'allo_no as relation_bill_no';
            $column = 'allo_no';
        }
        if ($relation_bill_type == 'N003310003') { //B2B订单
            $model = M('b2b_order', 'tb_');
            $field = 'PO_ID as relation_bill_no';
            $column = 'PO_ID';
        }
        if ($relation_bill_type == 'N003310004') { //B2C订单
            ini_set('max_execution_time', 1800);
            ini_set('memory_limit', '512M');
            $model = M('op_order', 'tb_');
            $field = 'ORDER_NO  as relation_bill_no';
            $column = 'ORDER_NO';
            $where['PARENT_ORDER_ID'] = array('exp', 'is null'); // 只查母单不考虑子单，否则会重复
        }

        if ($relation_bill_type == 'N003310006') {  //推广任务ID
            $model = M('promotion_task', 'tb_ms_');
            $field = 'promotion_task_no as relation_bill_no,forecast_rol';
            $column = 'promotion_task_no';
        }
        $where[$column] = ['in', $order_no];
        $ret = $model->field($field)->where($where)->select();
        return array_column($ret, 'relation_bill_no');
    }
}

