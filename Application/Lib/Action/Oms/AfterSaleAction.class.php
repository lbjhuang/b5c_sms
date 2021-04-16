<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/28
 * Time: 14:20
 */
class AfterSaleAction extends BasisAction
{

    public $omsAfterSaleService;
    public $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model               = new \Model();
        $this->omsAfterSaleService = new OmsAfterSaleService($this->model);
    }

    /**
     * 导入批量申请售后模板下载
     */
    public function downloadBatchAfterSaleTemplate()
    {
        $name = 'batch_aftersale.xls';
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Oms/AfterSale/' . $name;
        Http::download($filename, $filename);
    }

    public function editExcelData()
    {
        $data = json_decode(RedisModel::get_key('erp_return_error_excel'), true);
        foreach ($data as $key => $val) {
            $data[$key]['error_info'] = isset($this->omsAfterSaleService->notice_msg[$key + 1]) ? implode(',', $this->omsAfterSaleService->notice_msg[$key + 1]) : '';
        }
        RedisModel::set_key('erp_return_error_excel', json_encode($data));
    }

    public function readyExcel()
    {
        $data = json_decode(RedisModel::get_key('erp_return_error_excel'), true);
        RedisModel::set_key('erp_return_error_excel' , '');
        vendor("PHPExcel.PHPExcel");
        $name = 'batch_aftersale.xls';
        $inputFileName = APP_PATH . 'Tpl/Oms/AfterSale/' . $name;
        date_default_timezone_set('PRC');
        // 读取excel文件
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);
        } catch(\Exception $e) {
            die('加载文件发生错误："'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        }
        $baseRow=1;      //指定插入到第17行后
        foreach($data as $index=>$dataRow){
            $row= $index + $baseRow;    //$row是循环操作行的行号
            //$objPHPExcel->getActiveSheet()->insertNewRowBefore($row,1);  //在操作行的号前加一空行，这空行的行号就变成了当前的行号
            //对应的列都附上数据和编号
            $objPHPExcel->getActiveSheet()->setCellValue( 'A'.$row, $dataRow['platform_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'B'.$row, $dataRow['store_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'C'.$row, $dataRow['order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'D'.$row, $dataRow['sku_id']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'E'.$row, $dataRow['yet_return_num']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'F'.$row, $dataRow['warehouse_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'G'.$row, $dataRow['logistics_no']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'H'.$row, $dataRow['logistics_way_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'I'.$row, $dataRow['logistics_fee']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'J'.$row, $dataRow['logistics_fee_currency_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'K'.$row, $dataRow['service_fee']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'L'.$row, $dataRow['service_fee_currency_name']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'M'.$row, $dataRow['return_reason']);
            $objPHPExcel->getActiveSheet()->setCellValue( 'N'.$row, $dataRow['error_info']);
        }
        $filename = '退货错误报告' . date('Ymd');
        ob_end_clean();//清除缓存区，解决乱码问题
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $filename . '.xls');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    // 获取批量上传的申请售后数据
    public function getapplyBatchExcelData()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //强制读取第一个工作表
        $PHPExcel->setActiveSheetIndex(0);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();

        $data = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $data[$currentRow]['platform_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $data[$currentRow]['store_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $data[$currentRow]['order_id'] = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $data[$currentRow]['sku_id'] = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            $data[$currentRow]['yet_return_num'] = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue());
            $data[$currentRow]['warehouse_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());
            $data[$currentRow]['logistics_no'] = trim((string)$PHPExcel->getActiveSheet()->getCell("G" . $currentRow)->getValue());
            $data[$currentRow]['logistics_way_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("H" . $currentRow)->getValue());
            $data[$currentRow]['logistics_fee'] = trim((string)$PHPExcel->getActiveSheet()->getCell("I" . $currentRow)->getValue());
            $data[$currentRow]['logistics_fee_currency_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("J" . $currentRow)->getValue());
            $data[$currentRow]['service_fee'] = trim((string)$PHPExcel->getActiveSheet()->getCell("K" . $currentRow)->getValue());
            $data[$currentRow]['service_fee_currency_name'] = trim((string)$PHPExcel->getActiveSheet()->getCell("L" . $currentRow)->getValue());
            $data[$currentRow]['return_reason'] = trim((string)$PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue());
            $data_arr = array_filter($data[$currentRow]); // 过滤该行全部字段都没有值的情况
            if ($data_arr) {
                $data[$currentRow]['line'] = $currentRow; 
            } else {
                unset($data[$currentRow]);
            }
        }
        $PHPExcel->disconnectWorksheets();
        unset($PHPExcel);
        return $data;
    }

    // 校验批量提交的售后数据
    public function validateBatchApplySubmitData($data = [])
    {
        $error_msg = [];$error_msg['not_allow'] = false;
        if (!$data) {
            $error_msg['not_allow'] = true;
            $error_msg['data'][] = '该excel暂无数据，请确认后重新上传';
            return $error_msg;
        }

        if (count($data) > 1500) {
            $error_msg['not_allow'] = true;
            $error_msg['data'][] = '该excel数据条数过多（超出1500条限制），请确认后重新上传';
            return $error_msg;
        }
        $require_field = [
            'platform_name'                 => ['cn_name' => '站点', 'check_name'=> 'plat'],
            //'store_name'                    => ['cn_name' => '店铺', 'check_name'=> 'store_name_arr'],
            'order_id'                      => ['cn_name' => '订单id', 'check_name'=> false],
            'sku_id'                        => ['cn_name' => 'SKU', 'check_name'=> false],
            'yet_return_num'                => ['cn_name' => '退货件数', 'check_name'=> false],
            'warehouse_name'                => ['cn_name' => '退货仓库', 'check_name'=> 'warehouse'],
            'logistics_no'                  => ['cn_name' => '物流单号', 'check_name'=> false],
            'logistics_way_name'            => ['cn_name' => '物流方式', 'check_name'=> false],
            'logistics_fee'                 => ['cn_name' => '预计物流费用', 'check_name'=> false],
            'logistics_fee_currency_name'   => ['cn_name' => '预计物流费用币种', 'check_name'=> 'currency'],
            'service_fee'                   => ['cn_name' => '服务费用', 'check_name'=> false],
            'service_fee_currency_name'     => ['cn_name' => '服务费币种', 'check_name'=> 'currency'],
            'return_reason'                 => ['cn_name' => '售后原因', 'check_name'=> false]
        ];
        $require_field_value = array_keys($require_field);
        $cd_type['currency'] = 'false'; // 准备币种数据（需要已开启）
        $cd_type['warehouse'] = 'false'; // 准备仓库数据（需要已开启）
        $cd_type['plat'] = 'true'; // 准备站点数据
        //$cd_type['logics_company'] = 'true'; // 准备物流方式名称,用户直接填名称不需要了
        $cdModel = A('Common/Index');
        $cd_res_arr = $cdModel->get_cd($cd_type);
        $cd_res = [];
        foreach ($cd_res_arr as $key => $value) {
            foreach ($value as $k => $v) {
                $cd_res[$key][$v['CD_VAL']] = $v['CD'];
            }
        } 
        
        // 准备店铺名称
        $cd_res['store_name_arr'] = D('Guds/Store')->getStoreKeyValue();
        $ordModel = M('ord', 'tb_ms_');
        $orderModel = M('order', 'tb_op_');
        $ordGudModel = M('order_guds', 'tb_op_');
        $check_order = [];
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k, $require_field_value)) {
                    if (empty($v) && $v !== '0') {
                        $error_msg['not_allow'] = true;
                        $error_msg['data'][] = "第{$value['line']}行的 {$require_field[$k]['cn_name']} 不可为空";
                    } else {
                        if ($require_field[$k]['check_name'] !== false) {
                            if (!$cd_res[$require_field[$k]['check_name']][$v]) {
                                $error_msg['not_allow'] = true;
                                $error_msg['data'][] = "第{$value['line']}行的 {$require_field[$k]['cn_name']} 数据在系统中不存在该名称，请核对后再提交";
                            }
                        }
                    }
                    //订单派单状态是否为已出库 tb_ms_ord.WHOLE_STATUS_CD = N001820900 根据tb_ms_ord.THIRD_ORDER_ID（即op_order.order_id）
                    if ($k === 'order_id' && !empty($v)) {
                        $WHOLE_STATUS_CD = $ordModel->where(['THIRD_ORDER_ID' => $v])->getField('WHOLE_STATUS_CD');
                        if ($WHOLE_STATUS_CD !== 'N001820900') {
                            $error_msg['not_allow'] = true;
                            $error_msg['data'][] = "第{$value['line']}行的 订单派单状态不是“已出库”状态，无法申请售后";
                        }
                    }
                }
            }
            $plat_cd = ''; $store_id = ''; $store_id_res = ''; $sku_res = '';
            $plat_cd = $cd_res['plat'][$value['platform_name']];
            $store_id = $cd_res['store_name_arr'][$value['store_name']];
            if ($value['order_id'] && $value['sku_id'] && $plat_cd) {
                //SKU属于该订单商品,请输入正确的商品编码(在该order_id 和plat_cd和sku 下获取)
                $sku_res = $ordGudModel->where(['PLAT_CD' => $plat_cd, 'ORDER_ID' => $value['order_id'], 'B5C_SKU_ID' => $value['sku_id']])->getField('PLAT_CD');
                if (!$sku_res) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的SKU不属于{$value['platform_name']}平台下的该订单号，无法申请售后";
                }
                $store_id_res = $orderModel->field('STORE_ID, ORDER_NO')->where(['ORDER_ID' => $value['order_id'], ['PLAT_CD' => $plat_cd]])->find();
                //店铺不为空
                if (!empty($value['store_name']) && $store_id_res['STORE_ID'] != $store_id) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的填写的店铺名称与{$value['platform_name']}平台下的该订单所属店铺名称不一致，请核实";
                }

                if (!$store_id_res['ORDER_NO']) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的{$value['platform_name']}平台下的该订单号不存在，请核实";
                }
                //根据订单号、店铺、平台去重
                $where = ['tb_op_order.ORDER_ID' => $value['order_id'], 'tb_ms_ord.WHOLE_STATUS_CD' => 'N001820900'];
                if (!empty($value['store_name'])) {
                    $where['tb_op_order.STORE_ID'] = $store_id;
                    $string = $value['platform_name'] . '平台/' . $value['store_name'] . '店铺';
                } else {
                    $where['tb_op_order.PLAT_CD'] = $plat_cd;
                    $string = $value['platform_name'] . '平台';
                }
                $order_num = $this->model->table('tb_op_order')
                    ->join('left join tb_ms_ord on tb_op_order.ORDER_ID = tb_ms_ord.THIRD_ORDER_ID')
                    ->where($where)
                    ->count();
                if ($order_num > 1) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的{$string}下的订单号，存在重复";
                }
                $order_no = $data[$key]['order_no'] = $store_id_res['ORDER_NO'];
                $order_id = $value['order_id'];
                $order_info = $this->model->table('tb_op_order')->field('ORDER_ID,BWC_ORDER_STATUS,PLAT_NAME,PLAT_CD')->where(['ORDER_ID' => $order_id, 'PLAT_CD' => $plat_cd])->find();
                if (!in_array($order_info['BWC_ORDER_STATUS'], ['N000550500', 'N000550600', 'N000550800', 'N000550400', 'N000550900', 'N000551000'])) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的订单状态不属于：‘待收货’、‘已收货’、‘交易成功’、‘交易关闭’、‘交易取消’，不可申请售后";
                }
                $order_goods_num      = $this->omsAfterSaleService->getOrderGoodsNum($order_id, $plat_cd, $value['sku_id'] , OmsAfterSaleService::TYPE_RETURN);//获取订单商品数量
                if (!$order_goods_num) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的订单商品数量小于1或该订单中未找到此商品";
                }
                $where = [
                    'oor.order_id'    => $order_id,
                    'oor.platform_cd' => $plat_cd,
                    'org.sku_id'      => $value['sku_id'],
                    'org.status_code' => ['neq', OmsAfterSaleService::STATUS_CANCEL]
                ];
                $return_info = $this->model->table('tb_op_order_return oor')
                    ->field('SUM(org.yet_return_num-org.refuse_warehouse_num) AS all_return_num')
                    ->join('left join tb_op_order_return_goods org on oor.id = org.return_id ')
                    ->where($where)
                    ->select();
                $all_return_num  = $return_info[0]['all_return_num'];
                if (bcadd($all_return_num, $value['yet_return_num']) > $order_goods_num) {
                    $error_msg['not_allow'] = true;
                    $error_msg['data'][] = "第{$value['line']}行的订单商品编码为{$value['sku_id']}的商品，本次退货件数已超过该订单允许可退货件数，请核实确认";
                }
            }
            $data[$key]['error_info'] = implode(',', $error_msg['data']);
            $error_msg['data'] = [];
        }
        // 导入数据校验不通过 生成Excel
        //if ($error_msg['not_allow'] === true) {
            $title = [[
                'platform_name'                 => '站点',
                'store_name'                    => '店铺',
                'order_id'                      => '订单id',
                'sku_id'                        => 'SKU',
                'yet_return_num'                => '退货件数',
                'warehouse_name'                => '退货仓库',
                'logistics_no'                  => '物流单号',
                'logistics_way_name'            => '物流方式',
                'logistics_fee'                 => '计物流费用',
                'logistics_fee_currency_name'   => 'cn流费用币种',
                'service_fee'                   => '服务费用',
                'service_fee_currency_name'     => '服务费币种',
                'return_reason'                 => '售后原因',
                'error_info'                    => '错误信息',
            ]];
            $error_data = array_merge($title, $data);
            RedisModel::set_key('erp_return_error_excel', json_encode($error_data));
        //}

        // 重新组装数据
        if ($error_msg['not_allow'] === false) {
            $ass_data = [];
            foreach ($data as $key => $value) {
                $order_info = []; $return_info = []; $goods_info = []; $base_info = [];
                $order_info['order_id'] = $value['order_id'];
                $order_info['order_no'] = $value['order_no'];
                $order_info['platform_cd'] = $cd_res['plat'][$value['platform_name']];
                $order_info['line'] = $value['line'];
                $base_info['logistics_no'] = $value['logistics_no'];
                $base_info['logistics_way_code'] = $value['logistics_way_name'];
                $base_info['logistics_fee_currency_code'] = $cd_res['currency'][$value['logistics_fee_currency_name']];
                $base_info['logistics_fee'] = $value['logistics_fee'];
                $base_info['service_fee_currency_code'] = $cd_res['currency'][$value['service_fee_currency_name']];
                $base_info['service_fee'] = $value['service_fee'];
                $base_info['return_reason'] = $value['return_reason'];
                $goods_info['sku_id'] = $value['sku_id'];
                $goods_info['yet_return_num'] = $value['yet_return_num'];
                $goods_info['warehouse_code'] = $cd_res['warehouse'][$value['warehouse_name']];
                $goods_info['upc_id'] = SkuModel::getUpcId($goods_info['sku_id']);
                $ass_data[$key]['order_info'] = $order_info;
                $ass_data[$key]['return_info']['base_info'] = $base_info;
                $ass_data[$key]['return_info']['goods_info'][] = $goods_info;
            }
            $error_msg['data'] = $ass_data;
        }
        return $error_msg;
    }

    // 批量锁和批量解锁，以防止多次频繁上传点击
    public function batchLockOrderNo($request_data, $is_lock = false)
    {
        $error_msg = [];
        if (!$is_lock) {
            foreach ($request_data as $key => $value) {
                $rClineVal    = RedisModel::lock('order_no' . $value['order_info']['order_no'], 10);
                if (!$rClineVal) {
                    $error_msg[] = "第{$value['order_info']['line']}行获取订单流水锁失败";
                    // throw new Exception('获取流水锁失败');
                }
            }
            
        } else {
            foreach ($request_data as $key => $value) {
                RedisModel::unlock('order_no' . $value['order_info']['order_no']);
            }    
        }
        return $error_msg ? $error_msg : false;
    }


    // 批量售后申请提交
    public function applySubmitBatch()
    {
        try {
            // 获取上传的excel数据
            $request_data = $this->getapplyBatchExcelData();
            $data_msg = $this->validateBatchApplySubmitData($request_data);
            if ($data_msg['not_allow'] === true) { //基础校验不通过，返回校验提示结果
                $res = DataModel::$error_return;
                $res['info'] = '批量退货导入错误';
                $res['data'] = $data_msg['data'];
            } else {
                $request_data = $data_msg['data'];
//todo 批量锁
                //$error_arr = $this->batchLockOrderNo($request_data, false);
                $res         = DataModel::$success_return;
                $this->model->startTrans();
                foreach ($request_data as $key => $value) {
                    $this->omsAfterSaleService->is_batch = true;
                    $this->omsAfterSaleService->batch_line = $value['order_info']['line'];
                    $this->omsAfterSaleService->applySubmit($value);
                }
                if ($this->omsAfterSaleService->notice_msg) {
                    $this->editExcelData();
                    $res = DataModel::$error_return;
                    $res['info'] = '批量退货导入错误';
                    //$res['data'] = $this->omsAfterSaleService->notice_msg;
                    Logs(['msg' => $this->omsAfterSaleService->notice_msg], __FUNCTION__.'----batchMsg', 'tr');
                    $this->model->rollback();
                } else {
                    $this->model->commit();
                }
                unset($this->omsAfterSaleService->is_batch);
                unset($this->omsAfterSaleService->batch_line);
// todo 批量解锁 弃用（因为excel中允许同一个订单出现多次记录中）
                //$this->batchLockOrderNo($request_data, true);
            }
        } catch (Exception $exception) {
            $last_sql = M()->_sql();
            $this->model->rollback();
            $res = $this->catchException($exception);
            Logs(['res' => $res, 'last_sql' => $last_sql], __FUNCTION__.'----batchSysMsg', 'tr');
        }
        $this->ajaxReturn($res);
    }

    //售后申请提交
    public function applySubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal    = RedisModel::lock('order_no' . $request_data['order_info']['order_no'], 10);
            if ($request_data) {
                $this->validateApplySubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->applySubmit($request_data);
            $this->model->commit();
            RedisModel::unlock('order_no' . $request_data['order_info']['order_no']);
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function validateApplySubmitData($data)
    {
        $goods_return_attributes      = $goods_reissue_attributes = $rules = [];
        $rules['order_info.order_no']    = 'required';
        $rules['order_info.order_id']    = 'required';
        $rules['order_info.platform_cd'] = 'required';
        if ($data['return_info']) {
            $rules["return_info.base_info.logistics_no"]                = 'required';
            $rules["return_info.base_info.logistics_way_code"]          = 'required|string';
            $rules["return_info.base_info.logistics_fee_currency_code"] = 'required|string|size:10';
            $rules["return_info.base_info.logistics_fee"]               = 'required|numeric|min:0';
            $rules["return_info.base_info.service_fee_currency_code"]   = 'required|string|size:10';
            $rules["return_info.base_info.service_fee"]                 = 'required|numeric|min:0';
            $rules["return_info.base_info.return_reason"]               = 'required';
            $rules["return_info.goods_info"]                            = 'required|array';

            foreach ($data['return_info']['goods_info'] as $key => $value) {
                // $rules["return_info.goods_info.{$key}.upc_id"]                            = 'required';
                $rules["return_info.goods_info.{$key}.sku_id"]         = 'required';
                $rules["return_info.goods_info.{$key}.yet_return_num"] = 'required|integer|min:1';
                $rules["return_info.goods_info.{$key}.warehouse_code"] = 'required|string|size:10';
                //                $rules["return_info.goods_info.{$key}.order_goods_num"] = 'required|integer|min:1';

                // $goods_return_attributes["return_info.goods_info.{$key}.upc_id"]          = '商品条形码';
                $goods_return_attributes["return_info.goods_info.{$key}.sku_id"]         = '商品sku';
                $goods_return_attributes["return_info.goods_info.{$key}.yet_return_num"] = '退货件数';
                $goods_return_attributes["return_info.goods_info.{$key}.warehouse_code"] = '退货仓库';
            }
        }
        if ($data['reissue_info']) {
//            $rules["reissue_info.base_info.child_order_no"] = 'sometimes|required';
            $rules["reissue_info.base_info.receiver_name"]  = 'required';
            $rules["reissue_info.base_info.receiver_phone"] = 'required';
            $rules["reissue_info.base_info.country_id"]     = 'required';
            $rules["reissue_info.base_info.province_id"]    = 'required';
//            $rules["reissue_info.base_info.city_id"]        = 'required';
            $rules["reissue_info.base_info.address"]        = 'required';
            $rules["reissue_info.base_info.postal_code"]    = 'required';
            $rules["reissue_info.base_info.reissue_reason"] = 'required';
            // $rules["reissue_info.base_info.email"]          = 'required';
            $rules["reissue_info.goods_info"] = 'required|array';

            foreach ($data['reissue_info']['goods_info'] as $key => $value) {
                // $rules["reissue_info.goods_info.{$key}.upc_id"]                             = 'required';
                $rules["reissue_info.goods_info.{$key}.sku_id"]          = 'required';
                $rules["reissue_info.goods_info.{$key}.yet_reissue_num"] = 'required|integer|min:1';
                //                $rules["reissue_info.goods_info.{$key}.order_goods_num"]  = 'required|integer|min:1';

                // $goods_reissue_attributes["reissue_info.goods_info.{$key}.upc_id"]          = '商品条形码';
                $goods_reissue_attributes["reissue_info.goods_info.{$key}.sku_id"]          = '商品sku';
                $goods_reissue_attributes["reissue_info.goods_info.{$key}.yet_reissue_num"] = '补发件数';
            }
        }
        $custom_attributes = [
            'order_info.order_no'     => '订单号',
            'order_info.order_id'     => '订单id',
            'order_info.platform_cd'  => '平台cd',
            'return_info.goods_info'  => '退货商品信息',
            'reissue_info.goods_info' => '补发商品信息',

            'return_info.base_info.logistics_no'                => '物流单号',
            'return_info.base_info.logistics_way_code'          => '物流方式',
            'return_info.base_info.logistics_fee_currency_code' => '物流费用币种',
            'return_info.base_info.logistics_fee'               => '物流费用',
            'return_info.base_info.service_fee_currency_code'   => '服务费币种',
            'return_info.base_info.service_fee'                 => '服务费',
            'return_info.base_info.return_reason'               => '售后原因',

            'reissue_info.base_info.receiver_name'  => '收件人姓名',
            'reissue_info.base_info.receiver_phone' => '收件人手机号',
            'reissue_info.base_info.country_id'     => '国家',
            'reissue_info.base_info.province_id'    => '省份',
            'reissue_info.base_info.city_id'        => '城市',
            'reissue_info.base_info.address'        => '详细地址',
            'reissue_info.base_info.postal_code'    => '邮编',
            'reissue_info.base_info.reissue_reason' => '售后原因',
            'reissue_info.base_info.email'          => '邮箱',
        ];
        $custom_attributes = array_merge($goods_return_attributes, $custom_attributes);
        $custom_attributes = array_merge($goods_reissue_attributes, $custom_attributes);
        $this->validate($rules, $data, $custom_attributes);
    }

    public function getProducts()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data['search']) {
                $this->validateProductsData($request_data['search']);
            }
            $product_info = $this->omsAfterSaleService->searchProducts($request_data);
            if ($product_info['data']) {
                $order_id        = $request_data['search']['order_id'];
                $platform_cd     = $request_data['search']['platform_cd'];
                $sku_id          = $request_data['search']['sku_id'];
                $type            = $request_data['search']['type'];
                $order_goods_num = $this->omsAfterSaleService->getOrderGoodsNum($order_id, $platform_cd, $sku_id, $type);
                list($yet_return_num, $yet_reissue_num) = $this->omsAfterSaleService->countYetApplyNum($order_id, $platform_cd, $sku_id);
                $product_info['data'][0]['default_warehouse_code'] = $this->omsAfterSaleService->getOrderWarehouse($order_id, $platform_cd);
                $product_info['data'][0]['over_return_num']        = $order_goods_num - $yet_return_num;
                $product_info['data'][0]['over_reissue_num']       = $order_goods_num - $yet_reissue_num;
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $product_info;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateProductsData($data)
    {
        $rules = [
            'sku_id'      => 'required',
            'order_no'    => 'required',
            'type'        => 'required',
            'order_id'    => 'required',
            'platform_cd' => 'required',
        ];
        $custom_attributes = [
            'sku_id'      => '商品编号',
            'order_no'    => '订单号',
            'type'        => '售后类型',
            'order_id'    => '订单id',
            'platform_cd' => '平台cd',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    //申请售后页面展示的售后信息
    public function applyDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateApplyDetailData($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsAfterSaleService->getApplyDetail($request_data['order_id'], $request_data['platform_cd']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateApplyDetailData($data)
    {
        $rules             = [
            'order_no' => 'required',
            'order_id' => 'required',
            'platform_cd' => 'required',
        ];
        $custom_attributes = [
            'order_no' => '订单号',
            'order_id' => '订单id',
            'platform_cd' => '平台cd',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }


    //退货详情
    public function returnDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateDetailData($request_data);
            $res                              = DataModel::$success_return;
            $res['code']                      = 200;
            $data                             = $this->omsAfterSaleService->getReturnDetail($request_data['after_sale_no']);
            $res['data']['data']              = $data;
            $res['data']['total_status_code'] = $data[0]['total_status_code'];
            $total_status_code  = $data[0]['total_status_code'];
            $res['data']['only_return_money'] = 0;
            if($total_status_code == OmsAfterSaleService::STATUS_ONLY_REFUND_MONEY)
            {
                $res['data']['only_return_money'] = 1;
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //补发详情
    public function reissueDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateDetailData($request_data);
            $res                              = DataModel::$success_return;
            $res['code']                      = 200;
            $data                             = $this->omsAfterSaleService->getReissueDetail($request_data['after_sale_no']);
            $res['data']['data']              = $data;
            $res['data']['total_status_code'] = $data[0]['total_status_code'];
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateDetailData($data)
    {
        $rules             = [
            'after_sale_no' => 'required',
        ];
        $custom_attributes = [
            'after_sale_no' => '售后单号',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    //订单详情-售后信息
    public function detail()
    {
        try {
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = null;
            $request_data = DataModel::getDataNoBlankToArr();
            if (!empty($request_data['order_id'])) {
                $result = [
                    'return_info'  => $this->omsAfterSaleService->getReturnDetail('', $request_data['order_id'], $request_data['platform_cd']),
                    'reissue_info' => $this->omsAfterSaleService->getReissueDetail('', $request_data['order_id'], $request_data['platform_cd']),
                ];
                $res['data'] = $result;
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //售后列表
    public function lists()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->omsAfterSaleService->getList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //退货单取消
    public function cancelReturn()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateCancelData($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->cancelReturn($request_data);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //补发单取消
    public function cancelReissue()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateCancelData($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->cancelReissue($request_data);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateCancelData($data)
    {
        $rules             = [
            'after_sale_no'    => 'sometimes|required',
            'order_no'         => 'required',
            'return_goods_id'  => 'sometimes|required',
            'reissue_goods_id' => 'sometimes|required',
            'reissue_no'       => 'sometimes|required',
            // 'child_order_no' => 'sometimes|required',
        ];
        $custom_attributes = [
            'after_sale_no'    => '售后单号',
            'order_no'         => '订单号',
            'return_goods_id'  => '退货单id',
            'reissue_goods_id' => '补货单id',
            'reissue_no'       => '补发单号',
            'child_order_no'   => '子订单号',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    //待退货入库
    public function waitReturnList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->omsAfterSaleService->getWaitReturnList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //已经退货入库
    public function alreadyReturnList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->omsAfterSaleService->getReturnWarehouseList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //退货入库页面
    public function returnWarehouseDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (!isset($request_data['return_goods_id'])) {
                throw new \Exception(L('参数错误'));
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsAfterSaleService->getReturnWarehouseDetail($request_data['return_goods_id']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //退货入库提交
    public function returnWarehouseSubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateReturnWarehouseData($request_data);
            $rClineVal    = RedisModel::lock('order_id' . $request_data['order_id'], 10);
            if (!$rClineVal) {
                //获取锁失败
                throw new Exception('order_id:' . $request_data['order_id'] . '退货入库id:' . $request_data['return_goods_id'] . '获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->returnWarehouseSubmit($request_data);
            $is_end = $this->omsAfterSaleService->tagEnd($request_data['return_id']);
            $this->omsAfterSaleService->updateWarehouseStatus($request_data['return_id'], $request_data['return_goods_id'], $is_end);
            $this->model->commit();
            //退货入库成功后 推送消息
            $wx_return_res = (new OmsAfterSaleService(null))->ReturnWarehouseApproval($request_data);

            RedisModel::unlock('order_id' . $request_data['order_id']);
        } catch (Exception $exception) {
            RedisModel::unlock('order_id' . $request_data['order_id']);
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //阻塞获取锁
    public function locked($order_id)
    {
        // theoretically locking_timeout should be checked against time_limit (max_execution_time)
        $start = microtime(true);
        $hadLock = null;
        $locking_timeout = 10;
        while (true) {
            $rClineVal = RedisModel::lock('order_id' . $order_id, 10);
            if ($rClineVal) {
                return true;
            }
            if (microtime(true) - $start > $locking_timeout) {
                // abort waiting for lock release
                return false;
            }
            sleep(1);
        }
        return false;
    }

    //拒绝退货入库提交
    public function refuseReturnWarehouseSubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateRefuseReturnWarehouseData($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->refuseReturnWarehouseSubmit($request_data);
            $is_end = $this->omsAfterSaleService->tagEnd($request_data['return_id']);
            $this->omsAfterSaleService->updateRefusedStatus($request_data['return_id'], $request_data['return_goods_id'], $is_end);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function validateReturnWarehouseData($data)
    {
        if ($data['warehouse_num'] <= 0 && $data['warehouse_num_broken'] <= 0) {
            throw new \Exception(L('正品数和残次品数必须有一个大于0'));
        }
        $rules             = [
            'return_id'            => 'required|integer',
            'return_goods_id'      => 'required|integer',
            'sku_id'               => 'required',
            'warehouse_num'        => 'required|integer|min:0',
            'warehouse_num_broken' => 'sometimes|integer|min:0',
        ];
        $custom_attributes = [
            'return_id'            => '退货单id',
            'return_goods_id'      => '退货单商品信息id',
            'sku_id'               => '商品sku',
            'warehouse_num'        => '正品数量',
            'warehouse_num_broken' => '残次品数量',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    private function validateRefuseReturnWarehouseData($data)
    {
        $rules             = [
            'return_id'            => 'required|integer',
            'return_goods_id'      => 'required|integer',
            'sku_id'               => 'required',
            'warehouse_num_refuse' => 'required|integer|min:1',
            'refuse_reason'        => 'required',
        ];
        $custom_attributes = [
            'return_id'            => '退货单id',
            'return_goods_id'      => '退货单商品信息id',
            'sku_id'               => '商品sku',
            'warehouse_num_refuse' => '拒绝退货数量',
            'refuse_reason'        => '拒绝入库原因',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    public function orderProductSkus()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (!isset($request_data['order_id'])) {
                throw new \Exception(L('订单号不能为空'));
            }

            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsAfterSaleService->searchOrderSkuIds($request_data['order_id'], $request_data['platform_cd']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    protected function catchException($exception, $Model = null)
    {
        $res = DataModel::$error_return;
        if ($this->error_message) {
            $msg_arr    = array_values($this->error_message);
            $res['msg'] = $res['info'] = $msg_arr[0][0];
        } else {
            $res['msg'] = $res['info'] = $exception->getMessage();
        }
        if ($exception->getCode()) $res['code'] = $exception->getCode();
        if ($Model) {
            $Model->rollback();
        }
        return $res;
    }

    public function after_sale_apply()
    {
        $this->display();
    }

    public function after_sale_list()
    {
        $this->display();
    }

    public function return_detail()
    {
        $this->display();
    }

    public function reissue_detail()
    {
        $this->display();
    }

    public function refund_detail()
    {
        $this->display();
    }

    public function wait_warehouse_list()
    {
        $this->display();
    }

    /*****************************退款*****************************/

    /**
     * 退款申请/审核页数据
     */
    public function refundApplyDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateRefundApplyDetailData($request_data);
           
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsAfterSaleService->getRefundApplyDetail($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateRefundApplyDetailData($data)
    {
        $rules             = [
            'order_no'    => 'required',
            'order_id'    => 'required',
            'platform_cd' => 'required|size:10',
        ];
        $custom_attributes = [
            'order_no'    => '订单号',
            'order_id'    => '订单id',
            'platform_cd' => '平台code',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }
    private function validateRefundOrderStatus($data)
    {
        
        $res = $this->omsAfterSaleService->getRefundOrderStatus($data['order_id'],$data['platform_cd']);
      
        if(!$res||$res['BWC_ORDER_STATUS'] == 'N000551004'){
            throw new Exception('此订单状态为处理中无法发起售后');
        }
       
    }
    /**
     * 退款详情页
     */
    public function refundDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = $this->omsAfterSaleService->getRefundDetail($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function refundApplySubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            if ($request_data) {
                $this->validateRefundApplySubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            // 10735-订单状态_处理中支持售后退款
//            #排除处理中的订单
//            $this->validateRefundOrderStatus($request_data);
            $rClineVal    = RedisModel::lock('order_no' . $request_data['order_no'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $after_sale_no                = $this->omsAfterSaleService->refundApplySubmit($request_data);
            $res['data']['after_sale_no'] = $after_sale_no;
            $this->model->commit();
            RedisModel::unlock('order_no' . $request_data['order_no']);
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 回邮单退货提交
     *
     */
    public function reOrderApplySubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateReOrderApplySubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $rClineVal    = RedisModel::lock('order_no' . json_encode($request_data['order_info']), 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $return      = $this->omsAfterSaleService->reOrderApplySubmit($request_data);
            $res['data']['after_sale_no'] = $return['after_sale_no'];
            $res['data']['order_id']      = $return['order_id'];
            $res['data']['order_no']      = $return['order_no'];
            $res['data']['platform_code'] = $return['platform_code'];
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        RedisModel::unlock('order_no' . json_encode($request_data['order_info']));
        $this->ajaxReturn($res);
    }

    private function validateReOrderApplySubmitData($data)
    {
        $goods_return_attributes         = $rules = [];
        $rules['warehouse_info']         = 'required';
        $rules['order_info.order_no']    = 'required';
        $rules['order_info.order_id']    = 'required';
        $rules['order_info.platform_cd'] = 'required';
        if ($data['return_info']) {
            $rules["return_info.base_info.logistics_fee_currency_code"] = 'required|string|size:10';
            $rules["return_info.base_info.logistics_fee"]               = 'required|numeric|min:0';
            $rules["return_info.base_info.service_fee_currency_code"]   = 'required|string|size:10';
            $rules["return_info.base_info.service_fee"]                 = 'required|numeric|min:0';
            $rules["return_info.base_info.return_reason"]               = 'required';
            $rules["return_info.base_info.return_time"]                 = 'required';
            $rules["return_info.goods_info"]                            = 'required|array';
            $rules["customer_info.receiver_name"]         = 'required';
            $rules["customer_info.postal_code"]           = 'required';
            $rules["customer_info.two_char"]              = 'required';
            $rules["customer_info.country_name"]          = 'required';
            $rules["customer_info.city_name"]             = 'required';
            $rules["customer_info.address_1"]             = 'required';

            foreach ($data['return_info']['goods_info'] as $key => $value) {
                $rules["return_info.goods_info.{$key}.sku_id"]         = 'required';
                $rules["return_info.goods_info.{$key}.yet_return_num"] = 'required|integer|min:1';
                $rules["return_info.goods_info.{$key}.warehouse_code"] = 'required|string|size:10';
                $rules["return_info.goods_info.{$key}.handle_type"]    = 'required';
                $goods_return_attributes["return_info.goods_info.{$key}.sku_id"]         = '商品sku';
                $goods_return_attributes["return_info.goods_info.{$key}.yet_return_num"] = '退货件数';
                $goods_return_attributes["return_info.goods_info.{$key}.warehouse_code"] = '退货仓库';
                $goods_return_attributes["return_info.goods_info.{$key}.handle_type"]    = '处理方式';
            }
        }
        $custom_attributes = [
            'warehouse_info'             => '仓库信息（收货方）',
            'order_info.order_no'        => '订单号',
            'order_info.order_id'        => '订单id',
            'order_info.platform_cd'     => '平台cd',
            'return_info.goods_info'     => '退货商品信息',
            'return_info.customer_info'  => '客户信息',

            'return_info.base_info.logistics_fee_currency_code' => '物流费用币种',
            'return_info.base_info.logistics_fee'               => '物流费用',
            'return_info.base_info.service_fee_currency_code'   => '服务费币种',
            'return_info.base_info.service_fee'                 => '服务费',
            'return_info.base_info.return_reason'               => '售后原因',
            'return_info.base_info.return_time'                 => '退货时间',
            'customer_info.receiver_name'                       => '收货人姓名',
            'customer_info.postal_code'                         => '邮编',
            'customer_info.two_char'                            => '国家（二字码）',
            'customer_info.country_name'                        => '国家名',
            'customer_info.city_name'                           => '城市名',
            'customer_info.address_1'                           => '地址一',
        ];
        $custom_attributes = array_merge($goods_return_attributes, $custom_attributes);
        $this->validate($rules, $data, $custom_attributes);
    }

    private function validateRefundApplySubmitData($data)
    {
        $rules = [];
        $rules['order_id']        = 'required';
        $rules['order_no']        = 'required';
        $rules['platform_cd']     = 'required|size:10';
        $rules['attachment']      = 'required';
//        $rules['apply_opinion']   = 'required';
        $rules['audit_status_cd'] = 'required|size:10';

        $custom_attributes = [
            'order_id'        => '订单id',
            'order_no'        => '订单号',
            'platform_cd'     => '平台code',
            'attachment'      => '附件',
//            'apply_opinion'   => '签字意见',
            'audit_status_cd' => '审核状态',
        ];
        foreach ($data['refund_info'] as $key => $value) {
           if (isset($value['id'])) {
                continue;
            }
            $rules["refund_info.{$key}.type"]               = 'required';
            $rules["refund_info.{$key}.order_pay_date"]     = 'required';
            $rules["refund_info.{$key}.current_date"]       = 'required';
            $rules["refund_info.{$key}.refund_channel_cd"]  = 'required|size:10';
            $rules["refund_info.{$key}.refund_user_name"]   = 'required';
            $rules["refund_info.{$key}.refund_amount"]      = 'required|numeric|min:0';
            $rules["refund_info.{$key}.amount_currency_cd"] = 'required';
            $rules["refund_info.{$key}.sales_team_cd"]      = 'required|size:10';
            $rules["refund_info.{$key}.refund_reason_cd"]   = 'required';
            $rules["refund_info.{$key}.created_by"]         = 'required';
            
            $custom_attributes["refund_info.{$key}.type"]               = '售后类型';
            $custom_attributes["refund_info.{$key}.order_pay_date"]     = '订单支付日期';
            $custom_attributes["refund_info.{$key}.current_date"]       = '当前日期';
            $custom_attributes["refund_info.{$key}.refund_channel_cd"]  = '赔付渠道';
            $custom_attributes["refund_info.{$key}.refund_user_name"]   = '赔付对象';
            $custom_attributes["refund_info.{$key}.refund_amount"]      = '支付金额';
            $custom_attributes["refund_info.{$key}.amount_currency_cd"] = '支付金额币种';
            $custom_attributes["refund_info.{$key}.sales_team_cd"]      = '实际业务所属部门';
            $custom_attributes["refund_info.{$key}.refund_reason_cd"]   = '赔付原因';
            $custom_attributes["refund_info.{$key}.created_by"]         = '实际费用申请人';
           
           if (!$data['trade_no']) { // 没有订单交易号时，账号必填 #10296 售后单列表逻辑的优化修改 
               $rules["refund_info.{$key}.refund_account"]     = 'required';
               $custom_attributes["refund_info.{$key}.refund_account"]     = '账号';
           }
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    public function auditRefund()
    {

        
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateAuditRefundData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            
           
            $rClineVal    = RedisModel::lock('after_sale_id' . $request_data['after_sale_id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $this->omsAfterSaleService->auditRefund($request_data);
            $this->model->commit();
            RedisModel::unlock('after_sale_id' . $request_data['after_sale_id']);
            #发送企业微信消息
            $afterSaleService = new OmsAfterSaleService();
            $type = $request_data['audit_status_cd'] == 'N003170004' ? 1 : 2;
            $afterSaleService->after_sale_refund_pass_wx_msg($request_data['after_sale_id'],$type);
          
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 批量审核售后
     */
    public function batchAuditRefund()
    {
        $post_data = DataModel::getDataNoBlankToArr();
        $afterSaleService = new OmsAfterSaleService();
        $res         = DataModel::$success_return;
        try {

            $this->model->startTrans();
            if (empty($post_data)){
                throw new Exception('请求为空');
            }

            foreach ($post_data as $request_data){
                if ($request_data) {
                    $this->validateAuditRefundData($request_data);
                } else {
                    throw new Exception('请求参数为空');
                }
                $rClineVal    = RedisModel::lock('after_sale_id' . $request_data['after_sale_id'], 10);
                if (!$rClineVal) {
                    throw new Exception('获取流水锁失败');
                }
                $temp_data = [
                    'after_sale_id' => $request_data['after_sale_id'],
                    'audit_status_cd' => $request_data['audit_status_cd'],
                    'order_no' => $request_data['order_no']
                ];

                $res['code'] = 200;
                $temp_data = CodeModel::autoCodeOneVal($temp_data,['audit_status_cd']);
                $res['data'][] = $temp_data;
                $this->omsAfterSaleService->auditRefund($request_data);
                RedisModel::unlock('after_sale_id' . $request_data['after_sale_id']);
                #发送企业微信消息
                $type = $request_data['audit_status_cd'] == 'N003170004' ? 1 : 2;
                $afterSaleService->after_sale_refund_pass_wx_msg($request_data['after_sale_id'],$type);
            }
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }


    private function validateAuditRefundData($data)
    {
        $rules = [
            'after_sale_id'   => 'required',
            'audit_status_cd' => 'required',
//            'audit_opinion'   => 'required',
        ];
        $custom_attributes = [
            'after_sale_id'   => '售后单id',
            'audit_status_cd' => '审核状态',
//            'audit_opinion'   => '签字意见',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    public function afterSaleStatus()
    {
        try {
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = TbMsCmnCdModel::getAfterSaleStatusMap();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function mixAfterSaleStatus()
    {
        try {
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = TbMsCmnCdModel::getMixAfterSaleStatus();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //审核撤回
    public function revokeReview()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $id = M('op_order_refund', 'tb_')->where(['payment_audit_id' => $request_data['payment_audit_id']])->getField('id');
           
            $model        = new Model();
            $model->startTrans();
            if ($request_data) {
                ELog::add(['info'=>'售后申请审核撤回记录','request' => json_encode($request_data)],ELog::INFO);
                $this->validateRevokeReviewData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $rClineVal    = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            (new B2CPaymentService($model))->accountingAudit($request_data);
            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
            #发送企业微信消息
            $afterSaleService = new OmsAfterSaleService();
            $afterSaleService->after_sale_refund_pass_wx_msg($id,3);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateRevokeReviewData($data) {
        $rules = [
            'status'           => 'required|numeric',
            'payment_audit_id' => 'required|numeric',
        ];
        $custom_attributes = [
            'status'           => '审核状态',
            'payment_audit_id' => '付款id',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    public function listExport() {
        $request_data = ZUtils::filterBlank($_POST);
        $list = $this->omsAfterSaleService->getList($request_data, true)['data'];
        foreach ($list as &$item) {
            $name = '';
            foreach ($item['product_info'] as $v) {
                $name .= 'SKU:'.$v['sku_id'] . 'x'. $v['num'].' '.$v['pay_currency'].':'.$v['pay_total_price'].' '.$v['spu_name'].' '.$v['product_attr'].PHP_EOL;
            }
            $item['p_info'] = $name;
            $item['p_sku_id'] = $item['product_info'][0]['sku_id'];
            $item['after_sale_no'] = $item['after_sale_no']."\t";
            $item['order_no'] = $item['order_no']."\t";

            # 退款金額仅针对退款
            if(in_array($item['type_name'],['退货','补发'])) {
                $item['pay_currency'] = $item['pay_total_price'] = '';
            }

        }
        $map  = [
            ['field_name' => 'store_name', 'name' => '店铺'],
            ['field_name' => 'after_sale_no', 'name' => '售后单号'],
            ['field_name' => 'type_name', 'name' => '售后类型'],
            ['field_name' => 'p_info', 'name' => '商品'],
            ['field_name' => 'p_sku_id', 'name' => '商品编码'],
            ['field_name' => 'order_no', 'name' => '订单号'],
            ['field_name' => 'created_at', 'name' => '发起时间'],
            ['field_name' => 'status_code_val', 'name' => '售后状态'],
            ['field_name' => 'created_by', 'name' => '申请人'],
            ['field_name' => 'audit_status_cd_val', 'name' => '审核状态'],
            ['field_name' => 'reason', 'name' => '售后原因'],
            ['field_name' => 'remark', 'name' => '运营备注'],
            ['field_name' => 'amount_currency_cd_val', 'name' => '申请退款币种'],
            ['field_name' => 'refund_amount', 'name' => '申请退款金额'],
        ];
        $this->exportCsv($list, $map);
    }
}