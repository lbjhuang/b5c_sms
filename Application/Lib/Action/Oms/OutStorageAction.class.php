<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/4/28
 * Time: 15:07
 */

class OutStorageAction extends BaseAction
{
    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        parent::_initialize();
        B('SYSOperationLog');
    }

    /**
     * 列表页路由
     */
    public function listpage()
    {
        $this->display();
    }

    /**
     * 列表页数据获取接口
     */
    public function listPageData()
    {
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model  = new OutStorageModel();
        $esData= $model->data($query);
        #根据订单id查找是否发起过赔付单
        $b5cOrderNoIds = array_column($esData,'b5cOrderNo');
        $compenData = $model->getCompenListByB5cOrderNo($b5cOrderNoIds);
        $compenData = array_column($compenData,'b5c_order_no');
        foreach ($esData as &$value) {
            if ($value['logistics_abnormal_status'] == 1) {
                $value['logistics_abnormal_status_name'] = ['扫描超时'];
            } else if ($value['logistics_abnormal_status'] == 2) {
                $value['logistics_abnormal_status_name'] = ['投妥超时'];
            } else if ($value['logistics_abnormal_status'] == 3) {
                $value['logistics_abnormal_status_name'] = ['扫描超时', '投妥超时'];
            } else {
                $value['logistics_abnormal_status_name'] = [];
            }

            $value['saleAfterFeeCny'] = $value['saleAfterFeeCny'] ? number_format($value['saleAfterFeeCny'], 2) : ''; 
            $value['saleAfterFeeCurrencyCny'] =  $value['saleAfterFeeCurrencyCny'] ? $value['saleAfterFeeCurrencyCny'] : '';
            if($value['saleAfterFeeCurrencyCny'] && empty($value['saleAfterFeeCny'])){
                $value['saleAfterFeeCny'] = '0.00';
            }
            $value['is_create_compen'] = 0;
            if (!in_array($value['b5cOrderNo'], $compenData)) {
                $value['is_create_compen'] = 1;
            }
        }
        $query ['pageIndex']<0?$query ['pageIndex'] = 1:$query['pageIndex'] = $query ['pageIndex'];
        $query ['pageSize']?$size = $query ['pageSize']:$size = 20;
        $data ['pageIndex']       = $query ['pageIndex'];
        $data ['pageSize']        = $size;
        $data ['totalPage']       = ceil($model->total / $data ['pageSize']);
        $data ['pageData']        = $esData;
        $data ['parmeterMap']     = $query;
        $data ['totalCount']      = $model->total;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 物流轨迹
     */
    public function feeding()
    {
        if (!empty($skuInfo)) {
            $skuInfo = array_map(function ($e) {

            }, $skuInfo);
        }
        $query = ZUtils::filterBlank($this->getParams()['data']['query']);
        $trackingNo = $query ['trackingNumber'];
        $orderId    = $query ['orderId'];
        $platCd     = $query ['platCd'];

        if ($orderId and $platCd) {
            $model = new Model();
            // 获取语言CODE
            $language = $model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N000920%']])->getField('ETC2, CD');
            $conditions ['order_id']   = ['eq', $orderId];
            if ($trackingNo) $conditions ['tracking_number'] = ['eq', $trackingNo];
            $conditions ['PLAT_CD']  = ['eq', $platCd];
            $conditions ['b5c_logistics_status'] = ['neq', 'N001270200'];
            // 优先查询手动填写的物流轨迹，没有时再去查自动获取的数据
            $conditions ['source_type'] = ['eq', '1'];
            $conditions ['language'] = ['eq', 'N000920200'];
            if ($platCd === 'N000834100') { // gp-cn店铺用中文，其余都用英文 #9789
                $conditions ['language'] = ['eq', 'N000920100'];
            }
            $lgtAllContent = $model
                ->field('date, status_description as remark')
                ->table('tb_lgt_tracking')
                ->where($conditions)
                ->order('date')
                ->select();
            if (!$lgtAllContent) {
                $conditions ['source_type'] = '0';
                $conditions ['language'] = ['eq', $language [strtolower(LANG_SET)]];
                $lgtAllContent = $model
                    ->field('date, status_description as remark')
                    ->table('tb_lgt_tracking')
                    ->where($conditions)
                    ->order('date')
                    ->select();
            }
            $code = 2000;
            $msg  = L('成功');
        } else {
            $code = 3000;
            $msg  = L('查询条件缺失');
        }

        $data ['pageData'] = $lgtAllContent;
        $data ['parmeterMap'] = $query;

        $response = $this->formatOutput($code, $msg, $data);
        $this->ajaxReturn($response, 'json');
    }
    private $cacheXchr;
    private function getXchr($currency, $date) {
        if (!$this->cacheXchr) {
            $tmp = M('ms_xchr', 'tb_')->field("*,1 AS CNY_XCHR_AMT_CNY")->select();
            foreach ($tmp as $v) {
                $this->cacheXchr[$v['XCHR_STD_DT']] = $v;
            }
            $tmp = null;
        }
        $ymd = str_replace('-', '', substr($date, 0, 10));
        $currency = strtoupper($currency);
        return $this->cacheXchr[$ymd][$currency . '_XCHR_AMT_CNY'];
    }

    public function checkExport(){
        $post_data = DataModel::getData();
        $model  = new OutStorageModel();
        $response = array(
            'code'=> 200,
            'is_hint'=> false,
        );
        list($total,$query) = $model->checkData($post_data);
        if ( $total > 4000){
            $dataService = new DataService();
            $excel_name = DataModel::userNamePinyin()."-订单已出库列表-".time().'.csv';
            $dataService->addOne($query,1,$excel_name,$total);
            $response['is_hint'] = true;
        }
        $this->ajaxReturn($response);
    }



    /**
     * 导出
     */
    public function export()
    {
        $time = time();
        session_write_close();
        $query = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true));
        $model  = new OutStorageModel();
        $esData = $model->data($query, false, true);
       
        foreach ($esData as &$v) {
            $freight_rate = $this->getXchr($v['freight_currency'], $v['orderPayTime']);
            if ($v['estimatedFreight']) $v['estimatedFreight'] = round($v['estimatedFreight'] * $freight_rate, 2);
            if ($v['trueFreight']) $v['trueFreight'] = round($v['trueFreight'] * $freight_rate, 2);
            if ($v['importFreight']) $v['importFreight'] = round($v['importFreight'] * $freight_rate, 2);
            if ($v['carry_tariff']) $v['carry_tariff'] = round($v['carry_tariff'] * $this->getXchr($v['carry_tariff_currency'], $v['orderPayTime']), 2);
            if ($v['subsidy']) $v['subsidy'] = round($v['subsidy'] * $this->getXchr($v['subsidy_currency'], $v['orderPayTime']), 2);
            if ($v['preAmountFreight']) $v['preAmountFreight'] = round($v['preAmountFreight'] * $this->getXchr($v['preFreightCurrency'], $v['orderPayTime']), 2);
            if ($v['vatFee']) $v['vatFee'] = round($v['vatFee'] * $this->getXchr($v['vatFeeCurrency'], $v['orderPayTime']), 2);
            if ($v['paypalFee']) $v['paypalFee'] = round($v['paypalFee'] * $this->getXchr($v['paypalFeeCurrency'], $v['orderPayTime']), 2);
            if ($v['leagueFee']) $v['leagueFee'] = round($v['leagueFee'] * $this->getXchr($v['leagueFeeCurrency'], $v['orderPayTime']), 2);
            if ($v['purReturnFee']) $v['purReturnFee'] = round($v['purReturnFee'] * $this->getXchr($v['purReturnFeeCurrency'], $v['orderPayTime']), 2);
            if ($v['insuranceFee']) $v['insuranceFee'] = round($v['insuranceFee'] * $this->getXchr($v['insuranceCurrency'], $v['orderPayTime']), 2);
        }
        unset($v);

        //根据物流单号查询是否有物流轨迹 #9992
        $tracking_num = array_unique(array_filter(array_column($esData, 'trackingNumber')));
        // $tracking_map = M('lgt_tracking', 'tb_')
        //     ->where(['tracking_number' => ['in',$tracking_num]])
        //     ->group('tracking_number')
        //     ->order('create_time desc')
        //     ->getField('tracking_number,b5c_logistics_status');
        //group by 先于order by 执行 所以这里无法筛选出每个单号最新的状态
        $tracking_map_table = M('lgt_tracking', 'tb_')
            ->where(['tracking_number' => ['in',$tracking_num]])
            ->order('date desc')
            ->field('tracking_number,b5c_logistics_status')
            ->limit('999999999')  #mysql 5.7后子句必须limit 才生效
            ->select(false);
        $tracking_map = M()->table($tracking_map_table.' as a')->group('a.tracking_number')->getField('tracking_number,b5c_logistics_status');
        foreach ($esData as &$value) {
            $value['trackingNumber'] = $value['trackingNumber'] . "\t";
            if (empty($tracking_map[$value['trackingNumber']])) {
                $value['isHasTrack'] = '否';
                $value['trackingStatus'] = '暂未发现';
            } else {
                if ($tracking_map[$value['trackingNumber']] !== 'N001270200' && $tracking_map[$value['trackingNumber']] !== 'N003030008') {
                    $value['isHasTrack'] = '是';
                    $value['trackingStatus'] = $tracking_map[$value['trackingNumber']] ? : '';
                } else {
                    $value['isHasTrack'] = '否';
                    $value['trackingStatus'] = '未发现';
                }
            }
            if ($value['logistics_abnormal_status'] == 1) {
                $value['logistics_abnormal_status_name'] = ['扫描超时'];
            } else if ($value['logistics_abnormal_status'] == 2) {
                $value['logistics_abnormal_status_name'] = ['投妥超时'];
            } else if ($value['logistics_abnormal_status'] == 3) {
                $value['logistics_abnormal_status_name'] = ['扫描超时', '投妥超时'];
            } else {
                $value['logistics_abnormal_status_name'] = [];
            }
            if (!empty($value['logistics_abnormal_status_name'])) {
                $value['logistics_abnormal_status_name_str'] = implode(',', $value['logistics_abnormal_status_name']);
            }
        }
        unset($value);
        unset($tracking_num);
        unset($tracking_map);
        $esData = CodeModel::autoCodeTwoVal($esData, ['trackingStatus']);
       
        foreach ($esData as &$item) {
            $item['trackingStatus'] = $item['trackingStatus_val'] ? : $item['trackingStatus'];
            unset($item['trackingStatus_val']);
        }
        unset($item);

        $exportExcel = new ExportModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('站点名称'), 'field_name' => 'platName'],
            $key++ => ['name' => L('店铺名称'), 'field_name' => 'storeName'],
            $key++ => ['name' => L('店铺所属公司'), 'field_name' => 'company_name'],
            $key++ => ['name' => L('订单创建类型'), 'field_name' => 'source'],
            $key++ => ['name' => L('订单创建人'), 'field_name' => 'createUser'],
            $key++ => ['name' => L('第三方订单ID'), 'field_name' => 'orderId'],
            $key++ => ['name' => L('第三方订单号'), 'field_name' => 'orderNo'],
            $key++ => ['name' => L('ERP订单号'), 'field_name' => 'b5cOrderNo'],
            $key++ => ['name' => L('订单状态'), 'field_name' => 'bwcOrderStatusNm'],
            $key++ => ['name' => L('SKU ID'), 'field_name' => 'b5cSkuId'],
            $key++ => ['name' => L('第三方SKU ID'), 'field_name' => 'skuId'],
            $key++ => ['name' => L('SKU名称'), 'field_name' => 'skuNm'],
            $key++ => ['name' => L('SKU 属性'), 'field_name' => 'gudsOptValMpngNm'],
            $key++ => ['name' => L('商品重量'), 'field_name' => 'sku_weight'],
            $key++ => ['name' => L('规格'), 'field_name' => 'sku_size'],
            $key++ => ['name' => L('币种'), 'field_name' => 'payCurrency'],
            $key++ => ['name' => L('商品成本价(USD)'), 'field_name' => 'costPrice'],
            $key++ => ['name' => L('商品采购公司'), 'field_name' => 'skuPurchasingCompany'],
            $key++ => ['name' => L('订单商品销售单价'), 'field_name' => 'itemPrice'],
            $key++ => ['name' => L('商品数量'), 'field_name' => 'itemCount'],
            $key++ => ['name' => L('订单商品总价'), 'field_name' => 'payItemPrice'],
            $key++ => ['name' => L('订单总优惠金额'), 'field_name' => 'payVoucherAmount'],
            $key++ => ['name' => L('订单运费'), 'field_name' => 'payShipingPrice'],
            $key++ => ['name' => L('订单包装费'), 'field_name' => 'payWrapperAmount'],
            $key++ => ['name' => L('订单分期总手续费'), 'field_name' => 'payInstalmentServiceAmount'],
            $key++ => ['name' => L('订单商品总税费'), 'field_name' => 'tariff'],
            $key++ => ['name' => L('订单优惠总税费'), 'field_name' => 'promotionDiscountTax'],
            $key++ => ['name' => L('订单运费折扣税费'), 'field_name' => 'shippingDiscountTax'],
            $key++ => ['name' => L('订单包装费税费'), 'field_name' => 'giftWrapTax'],
            $key++ => ['name' => L('订单支付总价'), 'field_name' => 'payTotalPrice'],
            $key++ => ['name' => L('结算费'), 'field_name' => 'paySettlePrice'],
            $key++ => ['name' => L('结算费（USD）'), 'field_name' => 'paySettlePriceDollar'],
            $key++ => ['name' => L('估重运费（CNY）'), 'field_name' => 'estimatedFreight'],
            $key++ => ['name' => L('实重运费（CNY）'), 'field_name' => 'trueFreight'],
            $key++ => ['name' => L('尾程运费（CNY）'), 'field_name' => 'importFreight'],
            $key++ => ['name' => L('头程物流费用（CNY）'), 'field_name' => 'preAmountFreight'],
            $key++ => ['name' => L('保险费（CNY）'), 'field_name' => 'insuranceFee'],
            $key++ => ['name' => L('VAT费用（CNY）'), 'field_name' => 'vatFee'],
            $key++ => ['name' => L('支付手续费（CNY）'), 'field_name' => 'paypalFee'],
            $key++ => ['name' => L('流量活动费用（CNY）'), 'field_name' => 'leagueFee'],
            $key++ => ['name' => L('采购返佣金（CNY）'), 'field_name' => 'purReturnFee'],
            $key++ => ['name' => L('关税（CNY）'), 'field_name' => 'carry_tariff'],
            $key++ => ['name' => L('平台活动补贴（CNY）'), 'field_name' => 'subsidy'],
            $key++ => ['name' => L('下单时间'), 'field_name' => 'orderTime'],
            $key++ => ['name' => L('付款时间'), 'field_name' => 'orderPayTime'],
            $key++ => ['name' => L('发货时间'), 'field_name' => 'shippingTime'],
            $key++ => ['name' => L('收货人姓名'), 'field_name' => 'addressUserName'],
            $key++ => ['name' => L('收货人手机'), 'field_name' => 'addressUserPhone'],
            $key++ => ['name' => L('收货人电话'), 'field_name' => 'receiverTel'],
            $key++ => ['name' => L('收货人邮箱'), 'field_name' => 'userEmail'],
            $key++ => ['name' => L('买家ID'), 'field_name' => 'buyerUserId'],
            $key++ => ['name' => L('国家'), 'field_name' => 'addressUserCountryIdNm'],
            $key++ => ['name' => L('省'), 'field_name' => 'addressUserProvinces'],
            $key++ => ['name' => L('市'), 'field_name' => 'addressUserCity'],
            $key++ => ['name' => L('区（县）'), 'field_name' => 'addressUserRegion'],
            $key++ => ['name' => L('具体地址'), 'field_name' => 'addressUserAddress1'],
            $key++ => ['name' => L('邮编'), 'field_name' => 'addressUserPostCode'],
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouseNm'],
            $key++ => ['name' => L('物流公司'), 'field_name' => 'logisticCdNm'],
            $key++ => ['name' => L('物流方式'), 'field_name' => 'logisticModel'],
            $key++ => ['name' => L('物流单号'), 'field_name' => 'trackingNumber'],
            $key++ => ['name' => L('发货状况'), 'field_name' => 'logistics_abnormal_status_name_str'],
            $key++ => ['name' => L('是否有物流轨迹'), 'field_name' => 'isHasTrack'],
            $key++ => ['name' => L('当前物流状态'), 'field_name' => 'trackingStatus'],
            $key++ => ['name' => L('包裹号'), 'field_name' => 'PACKING_NO'],
            $key++ => ['name' => L('用户备注'), 'field_name' => 'shippingMsg'],
            $key++ => ['name' => L('运营备注'), 'field_name' => 'remarkMsg'],
            $key   => ['name' => L('售后费用（CNY）'), 'field_name' => 'saleAfterFeeCny'],
        ];
//        $this->exportCsv($esData, $exportExcel->attributes, '导出_');
//        $exportExcel->data = $esData;
//        $exportExcel->export();
        Logs('out time:'.time()-$time, __FUNCTION__, 'other');

        $xls_cell_keys = array_column($exportExcel->attributes, 'field_name');
        $xls_cell_values = array_column($exportExcel->attributes, 'name');
        if (3000 >= count($esData) && count($esData) != 0) {
            $this->stock_export_min($exportExcel->attributes, $xls_cell_keys, $esData); // 小于5000走phpexcel逻辑，便于调整excel界面样式，自动宽度，(后续字体大小调整，冻结首行)等 #10108
            Logs('out time end:'.time()-$time, __FUNCTION__, 'other');
            die();
        }
//        $string = '';
//        $to_special_arr = [];
//        $to_str_arr = [];
//        $string = ExcelModel::getExportCsvString([$xls_cell_values]);

//        $xlsDataNews = $this->justicExportData($esData, $xls_cell_keys);

//        unset($xlsData);
//        $string .= ExcelModel::getExportCsvString($esData, $to_str_arr, $to_special_arr);
//        unset($xlsDataNews);
//        ExcelModel::exportCsv('已出库列表导出_'.date('YmdHis'), $string);

        $this->exportCsv($esData, $exportExcel->attributes, '已出库列表导出_'.date('YmdHis').'.csv');
        Logs('out time end:'.time()-$time, __FUNCTION__, 'other');
    }

    public function stock_export_min($attributes, $xls_cell_keys, $esData)
    {
//        $xlsData = $this->justicExportData($esData, $xls_cell_keys);
        $Orders = A('Home/Orders');
        $width = ['type' => 'auto_size'];
        $xlsName = '已出库列表导出';
        $Orders->exportExcel_stock_out($xlsName, $attributes, $esData, $width);
    }

    // 根据物流单号查询是否有物流轨迹 #9992
    public function justicExportData($esData, $xls_cell_keys)
    {
        $xlsDataNews = [];
        $lgtTrackModel = M('lgt_tracking', 'tb_');
        if (count($esData) <= 3000) {
            foreach (DataModel::toYield($esData) as $key => $value) {
                foreach (DataModel::toYield($xls_cell_keys) as $temp_key => $temp_value) {
                    if ($temp_value === 'trackingNumber') { 
                        $checkRes = [];
                        $checkRes = $this->checkHasTrack($value[$temp_value], $lgtTrackModel);
                    }
                    if ($temp_value === 'isHasTrack' || $temp_value === 'trackingStatus') {
                        $xlsDataNews[$key][$temp_value] = $checkRes[$temp_value];
                    } else {
                        $xlsDataNews[$key][$temp_value] = $value[$temp_value];
                    }
                }
            }
        } else {
            foreach (DataModel::toYield($esData) as $key => $value) {
                foreach (DataModel::toYield($xls_cell_keys) as $temp_key => $temp_value) {
                    if ($temp_value === 'trackingNumber') { 
                        $checkRes = [];
                        $checkRes = $this->checkHasTrack($value[$temp_value], $lgtTrackModel);
                    }
                    if ($temp_value === 'isHasTrack' || $temp_value === 'trackingStatus') {
                        $xlsDataNew[] = $checkRes[$temp_value];
                    } else if ($temp_value === 'orderTime' || $temp_value === 'orderPayTime' || $temp_value === 'shippingTime' || $temp_value === 'trackingNumber' || $temp_value === 'addressUserPhone' || $temp_value === 'receiverTel' || $temp_value === 'orderNo' || $temp_value === 'b5cSkuId' || $temp_value === 'skuId') {
                        $xlsDataNew[] = $value[$temp_value] . "\n";
                    } else {
                        $xlsDataNew[] = $value[$temp_value];
                    }
                }

                $xlsDataNews[] = $xlsDataNew;
                unset($xlsDataNew);
            }
        }
        
        return $xlsDataNews;
    }

    // 根据物流单号查询是否有物流轨迹
    public function checkHasTrack($tracking_number = '', $lgtTrackModel)
    {
        $checkRes = []; $res = [];
        if (!$tracking_number) {
            $checkRes['isHasTrack'] = '否';
            $checkRes['trackingStatus'] = '暂未发现';
            return $checkRes;
        }
        $conditions['tracking_number'] = $tracking_number;
        $res = $lgtTrackModel->field('b5c_logistics_status')
            ->where($conditions)
            ->order('create_time desc')
            ->find();
        //echo M()->_sql();
        
        $checkRes['isHasTrack'] = '否';
        $checkRes['trackingStatus'] = '未发现';
        if ($res && $res['b5c_logistics_status'] !== 'N001270200' && $res['b5c_logistics_status'] !== 'N003030008') {
            $checkRes['isHasTrack'] = '是';
            $checkRes['trackingStatus'] = cdVal($res['b5c_logistics_status']);
        }
        return $checkRes;
    }

    /**
     * 导入结算运费
     */
    public function import()
    {
        $model = new ExcelAmountFreightModel();
        $r = $model->import();
        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);
        if ($response['code'] == 2000) {
            FileModel::copyExcel($model->excel_path, $model->saveName);//备份上传的excel
        }
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 导入结算运费模板下载
     */
    public function downloadTemplate()
    {
        $name = 'amount_freight.xlsx';
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Oms/OutStorage/' . $name;
        Http::download($filename, $filename);
    }

    /**
     * 格式化输出
     * @param int    $code     状态码
     * @param string $info     提示信息
     * @param array  $data     返回数据
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg'  => $info,
            'data' => $data
        ];

        return $response;
    }
}