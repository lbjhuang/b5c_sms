<?php
/**
 * User: TR
 * Date: 19/04/26
 * Time: 10:05
 */

class FinanceTransferService extends Service
{
    /**
     * SentinelService constructor.
     */

    public $originFile = './Application/Tpl/Home/Finance/Temp/'; // 原始表模板存放的路径
    public $allColumn; //取得最大的列号
    public $allRow; //取得最大的行号
    public $orderRow; // 订单号所在列值
    public $phpexcelInfo; //实例化的excel对象信息

    public function __construct()
    {
//        $this->SentinelRepository = new SentinelRepository();
    }

    // 初始化数据
    public function initData()
    {
        $phpexcelInfo = $this->getExcelInfo($_FILES['file']['tmp_name']);
        $PHPExcel = $phpexcelInfo['PHPExcel'];
        $allColumn = $phpexcelInfo['allColumn']; //取得最大的列号
        $allRow = $phpexcelInfo['allRow'];         //取得最大的行号
        $this->phpexcelInfo = $phpexcelInfo;
        $data = []; // 获取原始数据
        $settleRulesModel = M('op_settlement_rules', 'tb_');
        $settleMapModel = M('op_settlement_map', 'tb_');
        $settleMapRulesModel = M('op_settlement_map_rules', 'tb_');


        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            $store_id = ''; $func_name = ''; $file_name = ''; $mapWhere = [];
            $store_id = trim((string)$this->getSingleField('A', $currentRow));
            $func_name = trim((string)$this->getSingleField('B', $currentRow));
            $file_name = trim((string)$this->getSingleField('C', $currentRow));
            $site_cd = trim((string)$this->getSingleField('D', $currentRow));
            $plat_cd = trim((string)$this->getSingleField('E', $currentRow));
            $mapWhere['func_name'] = $func_name;
            $rules_id = $settleRulesModel->where($mapWhere)->getField('rules_id');
            if (!$rules_id) {

                // rules func_name file_name
                // 是否存在方法名，没有则新增
                $addData = [];
                $addData = [
                    'func_name' => $func_name,
                    'file_name' => $file_name,
                    'created_by' => 'system',
                ];
                $rules_id = $settleRulesModel->add($addData);
            }
        //map store_id site_cd plat_cd
            // 店铺id是否存在，没有则新增
            $mapResWhere = [];
            $mapResWhere['store_id'] = $store_id;
            $map_id = $settleMapModel->where($mapResWhere)->getField('map_id');
            if (!$map_id) {
                $addMapData = [];
                $addMapData = [
                    'store_id' => $store_id,
                    'site_cd' => $site_cd,
                    'plat_cd' => $plat_cd,
                    'created_by' => 'system',
                ];
                // 新增成功，则根据方法名，找到rules_id
                $map_id = $settleMapModel->add($addMapData);
            }

            // map_id rules_id 映射表新增
            $addRuleMapData = [];
            $addMapRulesData = [];
            $addMapRulesData = [
                'map_id' => $map_id,
                'rules_id' => $rules_id,
                'created_by' => 'system',
            ];
            $res = $settleMapRulesModel->add($addMapRulesData);
            
        }
    }

    // 保存文件
    public function saveFile($settlement_id)
    {
        if (!empty($_FILES)) {
            $uploadModel = new FileUploadModel();
            $uploadModel->filePath = '/opt/b5c-disk/excel/';
            $uploadModel->fileExts = ['csv', 'xls', 'xlsx'];
            $fileName = $uploadModel->fileUploadExtend();
            if (!$fileName) {
                throw new Exception("保存数据成功，但上传文件失败");
            }
            // 记录文件地址
            $saveFilePath = $uploadModel->filePath . $fileName;
            $res = (new SettlementModel())->where(['id'=>$settlement_id])->save(['file_path' => $saveFilePath]);
            return $res;
        }
        return false;
    }

    // 数据具体转换规则方法分发
    public function distributeFunc($funcName = '')
    {
        if (!$funcName) {
            throw new Exception("尚未定义该模板对应的数据转换方法名称");
        }

        if (!method_exists($this, $funcName)) {
            throw new Exception("该模板对应的数据转换方法名称不存在");
        }

        $addAllData = $this->$funcName(); // 对应是是各种规则模板的方法名称，方法名称可从数据表里获取（tb_op_settlement_rules.func_name） 

        $data = (new SettlementLogic())->create($addAllData); //保存数据，返回
        return $data;
    }
    /*
    *获取具体某行某列的值
    *$row 列
    *$line 行
    */
    private function getSingleField($row = '', $line = '')
    {
        $phpexcelInfo = $this->phpexcelInfo;
        $PHPExcel = $phpexcelInfo['PHPExcel'];
        return $PHPExcel->getActiveSheet()->getCell($row.$line)->getValue();        
    }

    // 原始表 Lazada
    private function rulesLazada()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'N';
        $mapping = $FinanceMapService->rulesLazadaMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }

        $post = I('post.');

        //币种
        switch (strval($post['store_id'])) {
            case '174':
            case '9':
                $currency = 'IDR';        
                break;
            case '171':
            case '8':
            case '88':
                $currency = 'MYR';        
                break;
            case '11':
            case '172':
                $currency = 'PHP';        
                break;
            case '12':
            case '170':
                $currency = 'SGD';        
                break;
            case '10':
            case '173':
                $currency = 'THP';
                break;
            case '33':
            case '189':
                $currency = 'VND';
            default:
                # code...
                break;
        }

        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $double = 0;
            $order_no_val = $value;
            if(false !== stripos($value, "E")){
                $a = explode("E",strtolower($value));
                $order_no_val = bcmul($a[0], bcpow(10, $a[1], $double), $double);
            }
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $order_no_val,
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $end_date[$value] ? $end_date[$value] : '', 
                    'deposit_date' => $deposit_date[$value] ? $deposit_date[$value] : '',
                    'amount' => $amount[$value],
                    'currency_cd' => $flipCurrency[$currency],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'sale_amount' => $sale_amount[$value],
                    'our_coupon_amount' => $our_coupon_amount[$value],
                    'our_bind_sale_amount' => $our_bind_sale_amount[$value],
                    'shared_amount' => $shared_amount_1[$value] - $shared_amount_2[$value] - $shared_amount_3[$value],
                    'commission' => $commission_1[$value] + $commission_2[$value],

                ],
                'ship' => [
                    'buyer_freight' => $buyer_freight[$value],
                    'our_collection_buyer_freight_tax' => $our_collection_buyer_freight_tax[$value],
                    'our_payment_buyer_freight_tax' => $our_payment_buyer_freight_tax[$value],
                    'plat_service_cost' => $plat_service_cost[$value],
                    'distribution_cost' => $distribution_cost[$value],

                ],
                'return' => [
                    'refund_date' => $refund_date[$value] ? $refund_date[$value] : '',
                    'our_coupon_amount_return' => $our_coupon_amount_return[$value],
                    'our_bind_sale_amount_return' => $our_bind_sale_amount_return[$value],
                    'buyer_amount_return' => $buyer_amount_return[$value],
                    'commission_return' => $commission_return[$value],

                ],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            'total_cost' => array_sum($commission_1) + array_sum($commission_2),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;
    }

    // 原始表模板 shopee-泰国站
    private function rulesShopeeTh()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesShopeeThMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }

        $post = I('post.');

        //币种
        $currency = 'THP';        


        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                // $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'amount' => $amount_1[$value] - $amount_2[$value] - $amount_3[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    // 'our_discount_amount' => $our_discount_amount[$value],
                    'our_coupon_amount' => $our_coupon_amount[$value],
                    'our_bind_sale_amount' => $our_bind_sale_amount[$value],
                    'shared_amount' => $shared_amount_1[$value] - $shared_amount_2[$value] - $shared_amount_3[$value] - $shared_amount_4[$value],
                    'commission' => $commission_1[$value] + $commission_2[$value],
                    'plat_coupon_amount' => $plat_coupon_amount[$value],
                    'plat_integral_amount' => $plat_integral_amount[$value],
                    'plat_bind_sale_amount' => $plat_bind_sale_amount[$value],
                    'credit_card_dealer_amount' => $credit_card_dealer_amount[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value] ? $shipped_date[$value] : '',
                    'confirmed_date' => $confirmed_date[$value] ? $confirmed_date[$value] : '',
                    'buyer_freight' => $buyer_freight[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount_1) - array_sum($amount_2) - array_sum($amount_3), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            'total_cost' => array_sum($commission_1) +  array_sum($commission_2),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;         
    }

 
    // 原始表模板 shopee-印尼站
    private function rulesShopeeId()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesShopeeIdMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }

        $post = I('post.');

        //币种
        $currency = 'IDR';        

        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                // $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'amount' => $amount_1[$value] + $amount_2[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'our_discount_amount' => $our_discount_amount[$value],
                    'our_coupon_amount' => $our_coupon_amount[$value],
                    'our_bind_sale_amount' => $our_bind_sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'plat_coupon_amount' => $plat_coupon_amount[$value],
                    'plat_integral_amount' => $plat_integral_amount[$value],
                    'plat_bind_sale_amount' => $plat_bind_sale_amount[$value],
                    'credit_card_dealer_amount' => $credit_card_dealer_amount[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value] ? $shipped_date[$value] : '',
                    'confirmed_date' => $confirmed_date[$value] ? $confirmed_date[$value] : '',
                    'buyer_freight' => $buyer_freight[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount_1) + array_sum($amount_2), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;         
    }
    // 原始表模板 shopee-马来西亚站和新加坡站
    private function rulesShopeeMy()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesShopeeMyMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }

        $post = I('post.');

        //币种
        switch (strval($post['store_id'])) {
            case '73':
                $currency = 'MYR';        
                break;
            case '99':
                $currency = 'MYR';        
                break;
            case '74':
                $currency = 'SGD';        
                break;
            case '103':
                $currency = 'SGD';        
                break;
            default:
                # code...
                break;
        }

        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 
        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                // $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'amount' => $amount[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '' ,
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '' ,
                    'currency_cd' => $flipCurrency[$currency],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'our_discount_amount' => $our_discount_amount[$value],
                    'our_coupon_amount' => $our_coupon_amount[$value],
                    'our_bind_sale_amount' => $our_bind_sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'plat_coupon_amount' => $plat_coupon_amount[$value],
                    'plat_integral_amount' => $plat_integral_amount[$value],
                    'plat_bind_sale_amount' => $plat_bind_sale_amount[$value],
                    'credit_card_dealer_amount' => $credit_card_dealer_amount[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value] ? $shipped_date[$value] : '',
                    'confirmed_date' => $confirmed_date[$value] ? $confirmed_date[$value] : '',
                    'buyer_freight' => $buyer_freight[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;         
    }

    // 原始表模板 Ebay-UK
    private function rulesEbayUS()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesEbayUSMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }
        //币种
        $currency = 'USD';        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'deposit_date' => $deposit_date[$value] ? $deposit_date[$value] : '',
                    'amount' => $amount[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                    'payment_method' => $payment_method[$value],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'buyer_amount' => $buyer_amount[$value], 
                    'buyer_amount_tax' => $buyer_amount_tax[$value],
                    'palat_collection_buyer_amount_tax' => $palat_collection_buyer_amount_tax[$value],
                    'our_collection_of_cost' => $our_collection_of_cost[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'our_collection_buyer_cost_charge' => $our_collection_buyer_cost_charge[$value],
                    'plat_service_cost' => $plat_service_cost[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $post = I('post.');
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;       
    }
    // 原始表模板 Ebay-UK
    private function rulesEbayUk()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesEbayUkMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }
        //币种
        $currency = 'GBP';        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'deposit_date' => $deposit_date[$value] ? $deposit_date[$value] : '',
                    'amount' => $amount[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                    'payment_method' => $payment_method[$value],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'buyer_amount' => $buyer_amount[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'our_collection_buyer_cost_charge' => $our_collection_buyer_cost_charge[$value],
                    'plat_service_cost' => $plat_service_cost[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $post = I('post.');
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;       
    }
    // 原始表模板 Ebay-AU3
    private function rulesEbayAu3()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesEbayAu3Map();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }
        //币种
        $currency = 'AUD';        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'deposit_date' => $deposit_date[$value] ? $deposit_date[$value] : '',
                    'amount' => $amount[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                    'payment_method' => $payment_method[$value],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'buyer_amount' => $buyer_amount[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value],
                    'buyer_amount_tax' => $buyer_amount_tax_1[$value] + $buyer_amount_tax_2[$value],
                    'palat_collection_buyer_amount_tax' => $buyer_amount_tax_1[$value] + $buyer_amount_tax_2[$value],// 两者一样


                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'plat_service_cost' => $plat_service_cost[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $post = I('post.');
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;       
    }
    // 原始表模板 Ebay-AU
    private function rulesEbayAu()
    {
        $FinanceMapService = new FinanceMapService();
        $this->orderRow = 'A';
        $mapping = $FinanceMapService->rulesEbayAuMap();
        foreach ($mapping as $ka => $value) {
            $$ka = $this->getFieldInfo($value['row'], $value['adjustMap'], $value['mathMap'], $value['validMap']);
        }
        //币种
        $currency = 'AUD';        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种 

        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['goods_name'] = $goods_name[$key][$v['other_sku_id']];
                // $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($order_no as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date[$value] ? $start_date[$value] : '',
                    'end_date' => $start_date[$value] ? $start_date[$value] : '', // 没有结算结束月
                    'deposit_date' => $deposit_date[$value] ? $deposit_date[$value] : '',
                    'amount' => $amount[$value],
                    'paid_on_date' => $paid_on_date[$value] ? $paid_on_date[$value] : '',
                    'order_created_date' => $order_created_date[$value] ? $order_created_date[$value] : '',
                    'currency_cd' => $flipCurrency[$currency],
                    'payment_method' => $payment_method[$value],
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'shared_amount' => $shared_amount[$value],
                    'buyer_amount' => $buyer_amount[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value],

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'our_collection_buyer_cost_charge' => $our_collection_buyer_cost_charge[$value],
                    'plat_service_cost' => $plat_service_cost[$value],

                ],
                'return' => [],
                'plat' => [], //这期开发暂不需要
                'other' => [],                
            ];
        }

        // 获取end_date最大值和start_date最小值
        // 总表
        $post = I('post.');
        $addAllData = [];
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => min($start_date),// 北京时间东八区
            'end_date' => max($start_date),// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有
            // 'total_cost' => array_sum($commission),
        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;       
    }


    // 原始表模板 Amazon 日本站
    private function rulesAmazonJp()
    {
        $post = I('post.');

        // 开始各种规则
        $addAllData = [];
        // 获取结算月
        $start_date = $this->getSingleField('B','2');
        $end_date = $this->getSingleField('C','2');
        $start_date = date('Y-m-d H:i:s', strtotime($start_date));// 北京时间东八区
        $end_date = date('Y-m-d H:i:s', strtotime($end_date));// 北京时间东八区

        //入账月
        $deposit_date = $this->getSingleField('D','2');
        $deposit_date = date('Y-m-d H:i:s', strtotime($deposit_date));// 北京时间东八区

        //币种
        $currency = $this->getSingleField('F','2');        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种
        

        //订单相关
        // 先获取订单id总数组
        $this->orderRow = 'H';
        $orderFieldArr = $this->getFieldInfo('', ['unique', 'filter']);
        // 净入账款(记得还需要相加获得总金额)
        $amount = $this->getFieldInfo('Y', [], ['+']);
        $amount_2 = $this->getFieldInfo('AA', [], ['+']);
        $amount_3 = $this->getFieldInfo('AG', [], ['+']);
        $amount_4 = $this->getFieldInfo('AI', [], ['+']);
        $amount_5 = $this->getFieldInfo('AJ', [], ['+']);


        // sku
        $validMap = [
            'G' => 'Order',
            'X' => 'Principal',
        ];

        // 平台商品号
        $plat_goods_id = $this->getFieldInfo('S', [], [], $validMap);
        // 我方商品号
        $our_goods_id = $this->getFieldInfo('T', [], [], $validMap);
        $sku = $this->getFieldInfo('V', ['sku'], [], $validMap);
        foreach ($sku as $key => $value) {
            foreach ($value as $k => $v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }
        // 购货量
        $goods_number = $this->getFieldInfo('W', [], ['firstline']);
        // 原含税货价
        $sale_amount = $this->getFieldInfo('Y', [], ['+'], $validMap);
        // 原含税货价中：我方以折扣让利的部分
        $our_discount_amount = $this->getFieldInfo('AG', [], ['+','||'], ['G' => 'Order', 'AF' => 'Principal']);
        //U8

        // 我方积分让利金额
        $our_integral_amount = $this->getFieldInfo('AA', [], ['+','||'], ['G' => 'Order','Z' => 'PointsGranted']); 

        // 我方优惠券让利金额
        //$our_coupon_amount = $this->getFieldInfo('O', [], ['+','||'], ['G' => 'CouponRedemptionFee','M' => 'CouponRedemptionFee','N' => 'Save 10% on Gohyo Black Light 9 LED UV Bar Glow in the Dark']); 
        // 买家、平台、信用卡商共同承担的金额(原含税货价 - 原含税货价中：我方以折扣让利的部分 - 我方优惠券让利金额)
// shared_amount = $sale_amount - $our_discount_amount - $our_integral_amount

        // 买家承担的交易税
        //$buyer_amount_tax = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order','M' => 'ItemPrice','N' => 'Tax']);
        // 我方主承担的买家交易税
        $our_cost_of_buyer_amount_tax = $this->getFieldInfo('AG', [], ['+', '||'], ['G' => 'Order','AF' => 'TaxDiscount']);
        // 买家承担的买家交易税商家代收(amazon 中两者相等)
        $palat_collection_buyer_amount_tax = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'Tax']);

        // 买家承担的买家交易税商家代收后代付
        //$palat_payment_buyer_amount_tax =  $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ItemWitheldTax','N' => 'MarketplaceFacilitatorTax-Principal']);
        // 商家代收代付后的退税后的净税额
        $plat_collection_net_tax = $palat_collection_buyer_amount_tax;
        // 佣金及服务费
        $commission = $this->getFieldInfo('AA', [], ['+','||'], ['G' => 'Order','Z' => 'Commission']);
        // 买家承担精美包装款/保险费我方代收
        $our_collection_of_cost = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'GiftWrap']);
        // 买家承担精美包装款/保险费我方代付
        $our_payment_of_cost = $this->getFieldInfo('AA', [], ['+','||'], ['G' => 'Order','Z' => 'GiftwrapChargeback']);

    // 配送信息
        // 发货日
        $shipped_date = $this->getFieldInfo('R', ['UTC'], ['maxtime'], ['G' => 'Order']);
        // 我方垫付平台承担运费
        $our_payment_plat_freight_sp = $this->getFieldInfo('AG', [], ['+', '||'], ['G' => 'Order','AF' => 'Shipping']);

        $our_payment_plat_freight_sp2 = $this->getFieldInfo('AA', [], ['+', '||'], ['G' => 'Order','Z' => 'ShippingChargeback']);

        // 我方收回平台承担运费
        $our_collection_plat_freight = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'Shipping']);

        // 买家承担运费的税中我方代收
        $our_collection_buyer_freight_tax = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'ShippingTax']);

        //`our_payment_buyer_freight_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担运费的税中我方代收后代付',
        // $our_payment_buyer_freight_tax = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order', 'M' => 'ItemWitheldTax', 'N' => 'MarketplaceFacilitatorTax-Shipping']);

        // `our_collection_buyer_cost_charge` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费我方代收',
        $our_collection_buyer_cost_charge = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'COD']);

        // `our_collection_buyer_service_cost_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费税费我方代收',
        $our_collection_buyer_service_cost_tax = $this->getFieldInfo('Y', [], ['+'], ['G' => 'Order','X' => 'COD Tax']);
        // `our_payment_buyer_service_cost_and_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费及税费我方代收后代付',
        $our_payment_buyer_service_cost_and_tax = $this->getFieldInfo('AA', [], ['+','||'], ['G' => 'Order','Z' => 'CODFee']);


        // `plat_service_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台配送服务费',
        $plat_service_cost = $this->getFieldInfo('AA', [], ['+','||'], ['G' => 'Order','Z' => 'FBAPerUnitFulfillmentFee']);

        // `plat_warheouse_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台入仓费',
        // $plat_warheouse_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Order', 'M' => 'ItemFees', 'N' => 'ShippingHB']);
        // `plat_stock_transfer_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台存货转移费',
        // $plat_stock_transfer_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'other-transaction', 'M' => 'other-transaction', 'N' => 'RemovalComplete']);
        // `plat_inventory_destruction_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台存货销毁费',
        // $plat_inventory_destruction_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'other-transaction', 'M' => 'other-transaction', 'N' => 'DisposalComplete']);
    // 退货信息
        // `return_date` date DEFAULT NULL COMMENT '退货日',
        //$return_date = $this->getFieldInfo('Q', ['UTC'], ['firstline'], ['G' => 'Refund']);
        // `refund` decimal(20,0) unsigned DEFAULT '0' COMMENT '退货退款',
        $refund = $this->getFieldInfo('Y', [], ['+', '||'], ['G' => 'Refund','X' => 'Principal']);
        // `our_discount_amount_return` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '我方折扣让利金额收回',
        $our_discount_amount_return = $this->getFieldInfo('AG', [], ['+'], ['G' => 'Refund', 'AF' => 'Principal']);

        //我方积分让利金额收回
        $our_integral_amount_return = $this->getFieldInfo('AA', [], ['+'], ['G' => 'Refund', 'Z' => 'PointsReturned']);
        //我方主承担的买家交易税
        $our_cost_of_buyer_amount_tax_return = $this->getFieldInfo('AG', [], ['+'], ['G' => 'Refund', 'AF' => 'TaxDiscount']);

        // `commission_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '佣金退货收回',
        $commission_return = $this->getFieldInfo('AA', [], ['+'], ['G' => 'Refund', 'Z' => 'Commission']);

        // `retrun_service_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '退货手续费',
        $retrun_service_cost = $this->getFieldInfo('AA', [], ['+', '||'], ['G' => 'Refund', 'Z' => 'RefundCommission']);

        // `return_service_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '退货服务价',
        // $return_service_amount = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemPrice', 'N' => 'RestockingFee']);
        // `our_payment_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '我方垫付平台承担运费',
        $our_payment_plat_freight = $this->getFieldInfo('Y', [], ['+', '||'], ['G' => 'Refund', 'X' => 'Shipping']);

        // `our_collection_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '我方收回平台承担运费',
        $our_collection_plat_freight_1 = $this->getFieldInfo('AG', [], ['+'], ['G' => 'Refund', 'AF' => 'Shipping']);
        $our_collection_plat_freight_2 = $this->getFieldInfo('AA', [], ['+'], ['G' => 'Refund', 'Z' => 'ShippingChargeback']);

        //`our_collection_buyer_service_cost_and_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费及税费我方代收',
        $our_collection_buyer_service_cost_and_tax = $this->getFieldInfo('AA', [], ['+'], ['G' => 'Refund', 'Z' => 'CODFee']);
        // `our_payment_buyer_cost_charge` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费我方代收后代付',
        $our_payment_buyer_cost_charge = $this->getFieldInfo('Y', [], ['+', '||'], ['G' => 'Refund', 'X' => 'COD']);
        // `our_payment_buyer_service_cost_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担服务费税费我方代收后代付',
        $our_payment_buyer_service_cost_tax = $this->getFieldInfo('Y', [], ['+', '||'], ['G' => 'Refund', 'X' => 'COD Tax']);

        // `our_collection_of_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '买家承担精美包装退货方代收',
        // $our_collection_of_cost = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemFees', 'N' => 'GiftwrapChargeback']);
    // 其他信息
         // `plat_indemnity` decimal(20,2) unsigned DEFAULT NULL COMMENT '平台赔款',
        $plat_indemnity_1 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'REVERSAL_REIMBURSEMENT']);
        $plat_indemnity_2 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'CS_ERROR_ITEMS']);
        $plat_indemnity_3 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'MULTICHANNEL_ORDER_LOST']);

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($orderFieldArr as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date ? $start_date : '',
                    'end_date' => $end_date ? $end_date : '',
                    'deposit_date' => $deposit_date ? $deposit_date : '',
                    'amount' => $amount[$value] + $amount_2[$value]+ $amount_3[$value]+ $amount_4[$value]+ $amount_5[$value],
                    'currency_cd' => $flipCurrency[$currency],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'our_discount_amount' => $our_discount_amount[$value],
                    // 'our_coupon_amount' => $our_coupon_amount[$value],
                    'our_integral_amount' => $our_integral_amount[$value],
                    'shared_amount' => $sale_amount[$value] - $our_discount_amount[$value] - $our_integral_amount[$value],
                    //'buyer_amount_tax' => $buyer_amount_tax[$value], 
                    'our_cost_of_buyer_amount_tax' => $our_cost_of_buyer_amount_tax[$value],
                    'palat_collection_buyer_amount_tax' => $palat_collection_buyer_amount_tax[$value], 
                    //'palat_payment_buyer_amount_tax' => $palat_payment_buyer_amount_tax[$value],  
                    'plat_collection_net_tax' => $plat_collection_net_tax[$value], 
                    'commission' => $commission[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value], 
                    'our_payment_of_cost' => $our_payment_of_cost[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(), 

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'our_payment_plat_freight' => $our_payment_plat_freight_sp[$value] + $our_payment_plat_freight_sp2[$value],
                    'our_collection_plat_freight' => $our_collection_plat_freight[$value],
                    'our_collection_buyer_freight_tax' => $our_collection_buyer_freight_tax[$value],
                    // 'our_payment_buyer_freight_tax' => $our_payment_buyer_freight_tax[$value],
                    'our_collection_buyer_cost_charge' => $our_collection_buyer_cost_charge[$value],
                    'our_collection_buyer_service_cost_tax' => $our_collection_buyer_service_cost_tax[$value],
                    'our_payment_buyer_service_cost_and_tax' => $our_payment_buyer_service_cost_and_tax[$value],
                    'plat_service_cost' => $plat_service_cost[$value],
                    //'plat_warheouse_cost' => $plat_warheouse_cost[$value],
                    //'plat_stock_transfer_cost' => $plat_stock_transfer_cost[$value],
                    //'plat_inventory_destruction_cost' => $plat_inventory_destruction_cost[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),

                ],
                'return' => [
                    //'return_date' => $return_date[$value], 
                    'refund' => $refund[$value],
                    'our_integral_amount_return' => $our_integral_amount_return[$value], 
                    'our_discount_amount_return' => $our_discount_amount_return[$value], 
                    'our_cost_of_buyer_amount_tax_return' => $our_cost_of_buyer_amount_tax_return[$value],
                    'commission_return' => $commission_return[$value], 
                    'retrun_service_cost' => $retrun_service_cost[$value], 
                    // 'return_service_amount' => $return_service_amount[$value], 
                    'our_payment_plat_freight' => $our_payment_plat_freight[$value], 
                    // 'our_collection_of_cost' => $our_collection_of_cost[$value], 
                    'our_collection_plat_freight' => $our_collection_plat_freight_1[$value] + $our_collection_plat_freight_2[$value],
                    'our_collection_buyer_service_cost_and_tax' => $our_collection_buyer_service_cost_and_tax[$value],
                    'our_payment_buyer_cost_charge' => $our_payment_buyer_cost_charge[$value],
                    'our_payment_buyer_service_cost_tax' => $our_payment_buyer_service_cost_tax[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),
                ],
                'plat' => [], //这期开发暂不需要
                'other' => [
                    // 'plat_indemnity' => $plat_indemnity_1[$value] + $plat_indemnity_2[$value] + $plat_indemnity_3[$value],
                    // 'created_by' => DataModel::userNamePinyin(),
                    // 'created_at' => DateModel::now(),
                ],                
            ];
        }

        // 总表
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => $start_date,// 北京时间东八区
            'end_date' => $end_date,// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount) + array_sum($amount_2) + array_sum($amount_3) + array_sum($amount_4) + array_sum($amount_5), // 净入账款总金额
            'total_cost' => array_sum($commission),
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有

        ];
        $addAllData['order'] = $orderAllInfo;

        return $addAllData;
    }
    // 原始表模板 Amazon 除日本站的全部店铺.xlsx
    private function rulesAmazon()
    {
        $post = I('post.');

        // 开始各种规则
        $addAllData = [];
        // 获取结算月
        $start_date = $this->getSingleField('B','2');
        $end_date = $this->getSingleField('C','2');
        $start_date = date('Y-m-d H:i:s', strtotime($start_date) + 8 * 60 * 60);// 北京时间东八区
        $end_date = date('Y-m-d H:i:s', strtotime($end_date) + 8 * 60 * 60);// 北京时间东八区

        //入账月
        $deposit_date = $this->getSingleField('D','2');
        $deposit_date = date('Y-m-d H:i:s', strtotime($deposit_date) + 8 * 60 * 60);// 北京时间东八区

        //币种
        $currency = $this->getSingleField('F','2');        
        $tmpCurr = array_flip(BaseModel::getCurrencyFlip()); // 币种=>CODE        
        $flipCurrency = array_flip($tmpCurr); // CODE=>币种
        

        //订单相关
        // 先获取订单id总数组
        $this->orderRow = 'H';
        $orderFieldArr = $this->getFieldInfo('', ['unique', 'filter']);
        // 净入账款(记得还需要相加获得总金额)
        $amount = $this->getFieldInfo('O', [], ['+']);

        // sku
        $validMap = [
            'G' => 'Order',
            'M' => 'ItemPrice',
            'N' => 'Principal',
        ];

        // 平台商品号
        $plat_goods_id = $this->getFieldInfo('S');
        // 我方商品号
        $our_goods_id = $this->getFieldInfo('T');
        $sku = $this->getFieldInfo('V', ['sku'], [], $validMap);
        foreach ($sku as $key => &$value) {
            foreach ($value as $k => &$v) {
                $sku[$key][$k]['plat_goods_id'] = $plat_goods_id[$key];
                $sku[$key][$k]['our_goods_id'] = $our_goods_id[$key];
                $sku[$key][$k]['created_by'] = DataModel::userNamePinyin();
                $sku[$key][$k]['created_at'] = DateModel::now();
            }
        }
        // 购货量
        $goods_number = $this->getFieldInfo('W', [], ['+'], $validMap);
        // 原含税货价
        $sale_amount = $this->getFieldInfo('O', [], ['+'], $validMap);
        // 原含税货价中：我方以折扣让利的部分
        $our_discount_amount = $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order', 'M' => 'Promotion', 'N' => 'Principal']);
        // 我方优惠券让利金额
        $our_coupon_amount = $this->getFieldInfo('O', [], ['+','||'], ['G' => 'CouponRedemptionFee','M' => 'CouponRedemptionFee','N' => 'Save 10% on Gohyo Black Light 9 LED UV Bar Glow in the Dark']); 
        // 买家、平台、信用卡商共同承担的金额(原含税货价 - 原含税货价中：我方以折扣让利的部分 - 我方优惠券让利金额)
// shared_amount = $sale_amount - $our_discount_amount - $our_coupon_amount
        // 买家承担的交易税
        $buyer_amount_tax = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order','M' => 'ItemPrice','N' => 'Tax']);
        // 买家承担的买家交易税商家代收(amazon 中两者相等)
        $palat_collection_buyer_amount_tax = $buyer_amount_tax;
        // 买家承担的买家交易税商家代收后代付
        $palat_payment_buyer_amount_tax =  $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ItemWitheldTax','N' => 'MarketplaceFacilitatorTax-Principal']);
        // 商家代收代付后的退税后的净税额
        $plat_collection_net_tax = $palat_payment_buyer_amount_tax;
        // 佣金及服务费
        $commission = $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ItemFees','N' => 'Commission']);
        // 买家承担精美包装款/保险费我方代收
        $our_collection_of_cost = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order','M' => 'ItemPrice','N' => 'GiftWrap']);
        // 买家承担精美包装款/保险费我方代付
        $our_payment_of_cost = $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ItemFees','N' => 'GiftwrapChargeback']);

    // 配送信息
        // 发货日
        $shipped_date = $this->getFieldInfo('Q', ['UTC'], ['maxtime'], $validMap);
        // 我方垫付平台承担运费
        $our_payment_plat_freight_sp =  $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'Promotion','N' => 'Shipping']);
        $our_payment_plat_freight_sp2 =  $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ItemFees','N' => 'ShippingChargeback']);  
        $our_payment_plat_freight_sp3 =  $this->getFieldInfo('O', [], ['+','||'], ['G' => 'Order','M' => 'ShipmentFeesr','N' => 'FBA transportation fee']);   
// $our_payment_plat_freight = $our_payment_plat_freight_sp + $our_payment_plat_freight_sp2 + our_payment_plat_freight_sp3;
        $our_collection_plat_freight = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order','M' => 'ItemPrice','N' => 'Shipping']);
        // 买家承担运费的税中我方代收
        $our_collection_buyer_freight_tax = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order', 'M' => 'ItemPrice', 'N' => 'ShippingTax']);
        //`our_payment_buyer_freight_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '买家承担运费的税中我方代收后代付',
        $our_payment_buyer_freight_tax = $this->getFieldInfo('O', [], ['+'], ['G' => 'Order', 'M' => 'ItemWitheldTax', 'N' => 'MarketplaceFacilitatorTax-Shipping']);
        // `plat_service_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台配送服务费',
        $plat_service_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Order', 'M' => 'ItemFees', 'N' => 'FBAPerUnitFulfillmentFee']);
        // `plat_warheouse_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台入仓费',
        $plat_warheouse_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Order', 'M' => 'ItemFees', 'N' => 'ShippingHB']);
        // `plat_stock_transfer_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台存货转移费',
        $plat_stock_transfer_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'other-transaction', 'M' => 'other-transaction', 'N' => 'RemovalComplete']);
        // `plat_inventory_destruction_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '平台存货销毁费',
        $plat_inventory_destruction_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'other-transaction', 'M' => 'other-transaction', 'N' => 'DisposalComplete']);
    // 退货信息
        // `return_date` date DEFAULT NULL COMMENT '退货日',
        $return_date = $this->getFieldInfo('Q', ['UTC'], ['firstline'], ['G' => 'Refund']);
        // `refund` decimal(20,0) unsigned DEFAULT '0' COMMENT '退货退款',
        $refund = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Refund', 'M' => 'ItemPrice', 'N' => 'Principal']);
        // `our_discount_amount_return` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '我方折扣让利金额收回',
        $our_discount_amount_return = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'Promotion', 'N' => 'Principal']);
        // `commission_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '佣金退货收回',
        $commission_return = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemFees', 'N' => 'Commission']);
        // `retrun_service_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '退货手续费',
        $retrun_service_cost = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Refund', 'M' => 'ItemFees', 'N' => 'RefundCommission']);
        // `return_service_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '退货服务价',
        $return_service_amount = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemPrice', 'N' => 'RestockingFee']);
        // `our_payment_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '我方垫付平台承担运费',
        $our_payment_plat_freight = $this->getFieldInfo('O', [], ['+', '||'], ['G' => 'Refund', 'M' => 'ItemPrice', 'N' => 'Shipping']);
        // `our_collection_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '我方收回平台承担运费',
        $our_collection_plat_freight_1 = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'Promotion', 'N' => 'Shipping']);
        $our_collection_plat_freight_2 = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemFees', 'N' => 'ShippingChargeback']);
        // `our_collection_of_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '买家承担精美包装退货方代收',
        $our_collection_of_cost = $this->getFieldInfo('O', [], ['+'], ['G' => 'Refund', 'M' => 'ItemFees', 'N' => 'GiftwrapChargeback']);
    // 其他信息
         // `plat_indemnity` decimal(20,2) unsigned DEFAULT NULL COMMENT '平台赔款',
        $plat_indemnity_1 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'REVERSAL_REIMBURSEMENT']);
        $plat_indemnity_2 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'CS_ERROR_ITEMS']);
        $plat_indemnity_3 = $this->getFieldInfo('O', [], ['+'], ['G' => 'other-transaction', 'M' => 'FBA Inventory Reimbursement', 'N' => 'MULTICHANNEL_ORDER_LOST']);

        // 获取各个订单号关联的字段总数组
        // 循环获取，组装成标准表结构输出
        $orderAllInfo = [];
        foreach ($orderFieldArr as $key => $value) {
            $orderAllInfo[] = [
                'info' => [
                    'order_no' => $value,
                    'start_date' => $start_date ? $start_date : '',
                    'end_date' => $end_date ? $end_date : '',
                    'deposit_date' => $deposit_date ? $deposit_date : '',
                    'amount' => $amount[$value],
                    'currency_cd' => $flipCurrency[$currency],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),
                ],
                'goods' => $sku[$value],
                'sell' => [
                    'goods_number' => $goods_number[$value],
                    'sale_amount' => $sale_amount[$value],
                    'our_discount_amount' => $our_discount_amount[$value],
                    'our_coupon_amount' => $our_coupon_amount[$value],
                    'shared_amount' => $sale_amount[$value] - $our_discount_amount[$value] - $our_coupon_amount[$value],
                    'buyer_amount_tax' => $buyer_amount_tax[$value], 
                    'palat_collection_buyer_amount_tax' => $palat_collection_buyer_amount_tax[$value], 
                    'palat_payment_buyer_amount_tax' => $palat_payment_buyer_amount_tax[$value],  
                    'plat_collection_net_tax' => $plat_collection_net_tax[$value], 
                    'commission' => $commission[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value], 
                    'our_payment_of_cost' => $our_payment_of_cost[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(), 

                ],
                'ship' => [
                    'shipped_date' => $shipped_date[$value],
                    'our_payment_plat_freight' => $our_payment_plat_freight_sp[$value] + $our_payment_plat_freight_sp2[$value] +$our_payment_plat_freight_sp3[$value],
                    'our_collection_plat_freight' => $our_collection_plat_freight[$value],
                    'our_collection_buyer_freight_tax' => $our_collection_buyer_freight_tax[$value],
                    'our_payment_buyer_freight_tax' => $our_payment_buyer_freight_tax[$value],
                    'plat_service_cost' => $plat_service_cost[$value],
                    'plat_warheouse_cost' => $plat_warheouse_cost[$value],
                    'plat_stock_transfer_cost' => $plat_stock_transfer_cost[$value],
                    'plat_inventory_destruction_cost' => $plat_inventory_destruction_cost[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),

                ],
                'return' => [
                    'return_date' => $return_date[$value], 
                    'refund' => $refund[$value], 
                    'our_discount_amount_return' => $our_discount_amount_return[$value], 
                    'commission_return' => $commission_return[$value], 
                    'retrun_service_cost' => $retrun_service_cost[$value], 
                    'return_service_amount' => $return_service_amount[$value], 
                    'our_payment_plat_freight' => $our_payment_plat_freight[$value], 
                    'our_collection_of_cost' => $our_collection_of_cost[$value], 
                    'our_collection_plat_freight' => $our_collection_plat_freight_1[$value] + $our_collection_plat_freight_2[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),
                ],
                'plat' => [], //这期开发暂不需要
                'other' => [
                    'plat_indemnity' => $plat_indemnity_1[$value] + $plat_indemnity_2[$value] + $plat_indemnity_3[$value],
                    'created_by' => DataModel::userNamePinyin(),
                    'created_at' => DateModel::now(),
                ],                
            ];
        }
        // 总表
        $addAllData['settlement'] = [
            'store_id' => $post['store_id'],
            'site_cd' => $post['site_cd'],
            'plat_cd' => $post['plat_cd'],
            'start_date' => $start_date,// 北京时间东八区
            'end_date' => $end_date,// 北京时间东八区
            'currency_cd' => $flipCurrency[$currency],
            'total_amount' => array_sum($amount), // 净入账款总金额
            'total_cost' => array_sum($commission),
            'excel_has_date' => 1, // excel是否含有结算月 0.没有 1.有

        ];
        $addAllData['order'] = $orderAllInfo;
        return $addAllData;
    }


    // 获取单列数据
    /*
    *  $phpexcelInfo 由$this->getExcelInfo($filePath);获取
    *  $orderRow 原始表订单号的列值，用于作为key关联信息
    *  $row 具体的英文表头列值，比如“A”，表示获取A列所有数值,如果是某列和某列运算得出，如O*Q，表示Q列*O列
    *  $adjustMap 该列数据格式调整，需要处理的方式数组，比如校验，修改数据格式，去重，去空,去掉特定值,科学计数法(E)，转换时间格式（utc格林尼治改为东八区）需要将值除以100(算术符号@数值，如/@100),调整金钱格式$, 获取时间T-L(获取左边的时间),T-R（获取右边的时间，如‘31 Dec 2018 - 06 Jan 2019’，取06 Jan 2019）, 根据现在时间获取某一天时间，比如获取下一个_周五_所在年月week等['unique','filter', 'UTC','E','/@100', '$', 'week']
    *  $mathMap 该列数据同一个订单号下的数据之间的计算,以及是否需要取绝对值(加减乘除)，还有取时间最大值，时间最小值，只取首行（前提是同一订单号下）等，如['+', '-','*', '/','maxtime', 'mintime', '||', 'firstline']
    *  $validMap 该列数据需要根据其他某行或多行的值来确定，J列是否符合时间格式 例如['J' => 'timeFormat','G' => 'Order', 'M' => 'ItemPrice']
    */
    private function getFieldInfo($row = '', $adjustMap = [], $mathMap = [], $validMap = [])
    {
        $phpexcelInfo = $this->phpexcelInfo;
        $orderRow = $this->orderRow;
        if (!$phpexcelInfo) {
            throw new Exception("请先实例化excel对象");           
        }
        if ($orderRow == '') {
            throw new Exception("请先输入具体订单号列值");           
        }

        $PHPExcel = $phpexcelInfo['PHPExcel'];
        $allColumn = $phpexcelInfo['allColumn']; //取得最大的列号
        $allRow = $phpexcelInfo['allRow'];         //取得最大的行号
        $data = []; // 获取原始数据

        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $orderRowKey = '';
//优化（订单列缓存起来）
            $orderRowKey = trim((string)$this->getSingleField($orderRow, $currentRow)); // 获取当前该行订单号的值(可以做缓存)
            $noConform = false; // 是否需要其他字段等于某值才符合条件,默认符合
            $noTimeFormat = false;  
            if (count($validMap) != 0) {
                foreach ($validMap as $validKey => $validVal) {
                    $valSingleVal = $this->getSingleField($validKey, $currentRow);
                    if ($validVal == 'timeFormat') { 
                        $isFormat = $this->checkDateTime($valSingleVal);
                        if (!$isFormat) { // 不符合
                            $noTimeFormat = true;
                            break;
                        }
                    } else {
                        if ($valSingleVal != $validVal) {
                            $noConform = true; // 表示该行数据中，不符合取值条件，放弃本次循环
                            break;
                        }    
                    }
                }
                if ($noConform || $noTimeFormat) {
                    continue;
                }
            }

            if (!empty($row)) { // 取得不是订单号那一列
                if (!empty($orderRowKey)) {
                    $singleField = '';
                    if (false !== stripos($row, "*")) { // 假如不是特定某一列，而是某列和某列运算结果，如J*Q
                        $rowArr = explode('*', $row);
                        $singleField_1 = $this->getSingleField($rowArr[0], $currentRow);
                        $singleField_2 = $this->getSingleField($rowArr[1], $currentRow);
                        if (in_array('$-', $adjustMap)) { // 需要更改格式，便于后面统计计算 如Rp 10.000 转换为10
                            $singleField_1 = $this->changeSingleMoneyFormat($singleField_1, '$-');
                            $singleField_2 = $this->changeSingleMoneyFormat($singleField_2, '$-');
                        }
                        if (in_array('$', $adjustMap)) { // 需要更改格式，便于后面统计计算 如Rp 10.000 转换为10
                            $singleField_1 = $this->changeSingleMoneyFormat($singleField_1, '$');
                            $singleField_2 = $this->changeSingleMoneyFormat($singleField_2, '$');
                        }

                        $singleField = $singleField_1 * $singleField_2;
                    }
                    else {
                        $singleField = $this->getSingleField($row, $currentRow);
                    }
                    if (in_array('$-', $adjustMap)) { // 需要更改格式，便于后面统计计算 如Rp 10.000 转换为10
                        $singleField = $this->changeSingleMoneyFormat($singleField,'$-');
                    }
                    /*if (in_array('$', $adjustMap)) { // 需要更改格式，便于后面统计计算 如Rp 10.000 转换为10
                        $singleField = $this->changeSingleMoneyFormat($singleField,'$');
                    }*/
                    if (in_array('+', $mathMap)) { // 表明同一订单号下的数据需要相加
                        if (in_array('||', $mathMap)) { // 表明该数据需要取绝对值，一般与数据相加一起运用
                            $singleField = abs($singleField);                            
                        }
                        if (!empty($data[$orderRowKey])) { // 表明该订单号（key值）对应的已经有值，需要相加，没有则直接取值
                            $data[$orderRowKey] += floatval($singleField);
                        } else {
                            $data[$orderRowKey] = floatval($singleField);
                        }
                    } else {
                        if (in_array('sku', $adjustMap)) { // 表示获取数据是sku这列
                            $skuVal = '';
                            $skuVal = trim((string)$singleField);
                            if ($skuVal) { // sku有值才保存，因为涉及到后面校验
                                if (!in_array($skuVal, $data[$orderRowKey])) { // 表示该订单号里还没有该sku值，用于排除重复
                                    $data[$orderRowKey][] = $skuVal;
                                }
                            }                           
                        } else if (in_array('goods', $adjustMap)) { // 表示该订单号下有可能有多个sku多个商品名称
                            $skuRow = array_search('goods', $adjustMap);
                            $skuSingleField = $this->getSingleField($skuRow, $currentRow); // 获取对应的sku
                            $goodsVal = '';
                            $goodsVal = trim((string)$singleField);
                            if ($goodsVal && $skuSingleField) { // 商品和sku有值才保存
                                if (!in_array($goodsVal, $data[$orderRowKey][$skuSingleField])) { // 表示该订单号里该sku还没有该商品名称，用于排除重复
                                    $data[$orderRowKey][$skuSingleField] = $goodsVal;
                                }
                            }

                        }
                        else {
                            if (in_array('maxtime', $mathMap) || in_array('mintime', $mathMap) ) { // 获取最大值 或最小值
                                if (!empty($data[$orderRowKey])) { // 该订单号对应的日期不为空，需要获取最大/最小的日期
                                    if (in_array('maxtime', $mathMap)) {
                                        if (strtotime($data[$orderRowKey]) < strtotime($singleField)) { // 当前行的时间值大，则用当前行的，否则用原来的
                                            $data[$orderRowKey] = $singleField;
                                        }
                                    }
                                    if (in_array('mintime', $mathMap)) {
                                        if (strtotime($data[$orderRowKey]) > strtotime($singleField)) { // 当前行的时间值大，则用当前行的，否则用原来的
                                            $data[$orderRowKey] = $singleField;
                                        }
                                    }
                                    
                                } else {
                                    $data[$orderRowKey] = trim((string)$singleField);
                                }
                            } else {
                                if (in_array('firstline', $mathMap)) { // 同一订单号支取第一个即首行的值即可
                                    if (empty($data[$orderRowKey])) { 
                                        $data[$orderRowKey] = trim((string)$singleField);
                                    }   
                                } else {
                                    $data[$orderRowKey] = trim((string)$singleField);
                                }
                            }
                        }
                    }
                }
            } else { // 直接取订单号那列数据
                $data[] = $orderRowKey;
            }

        }
        //用于校验，修改数据格式，去重，去空值等
        if (count($adjustMap) !== 0) {
            $data = $this->adjustData($adjustMap, $data);
        }
        return $data;
    }

    public function adjustData($adjustMap, $data)
    {
        if (!$data || !$adjustMap) {
            return false;
        }
        foreach ($adjustMap as $key => $value) {
            if(false !== stripos($value, "T-")){
                $data = $this->explodeTime($data, $value);
            }
            if(false !== stripos($value, "@")){
                $data = $this->arithmetic($data, $value);
            }
            if(false !== stripos($value, "$-")){
                $data = $this->changeMoneyFormat($data, $value);
            }
            switch ($value) {
                case 'unique': // 去重
                    $data = array_unique($data);
                    break;
                case 'filter': // 过滤空值
                    $data = array_filter($data);
                    break;
                case 'sku': 
                    $data = $this->validSku($data);
                    break;
                case 'UTC':
                    $data = $this->changeUtcTime($data);
                    break;
                case '$':
                    $data = $this->changeMoneyFormat($data);
                    break;
                case 'E':
                    $data = $this->sctonum($data);
                    break;
                case 'week':
                    $data = $this->getSpecialDay($data);
                    break;
                default:
                    # code...
                    break;
            }
        }
        return $data;
        // array_values(array_diff($names, ['-']));
    }


    // 根据指定时间，获取某个特定时间，目前只有获取下一个周五
    public function getSpecialDay($data)
    {
        foreach ($data as $key => $value) {
            $week = date('w',strtotime($value));
            $timeInt = (12 - intval($week)) * 60 * 60 * 24;
            $value = date('Y-m-d H:i:s', strtotime($value) + $timeInt);
        }
        return $data;
    }
    // 时间数据获取格式如31 Dec 2018 - 06 Jan 2019
    public function explodeTime($data, $rules)
    {
        if (!$data || !$rules) {
            return false;
        }

        foreach ($data as $key => &$value) {
            $re = explode(' - ', $value);
            switch ($rules) {
                case 'T-L': // 获取左边的时间
                    $value = $re[0];
                    break;
                case 'T-R': // 获取右边的时间
                    $value = $re[1];
                    break;
                default:
                    # code...
                    break;
            }
        }
        return $data;
    }

    // 数据调整
    public function arithmetic($data, $rules)
    {
        if (!$data || !$rules) {
            return false;
        }

        $res = explode("@",$rules);
        foreach ($data as $key => &$value) {
            if ($value == 0) {
                continue;
            }
            switch (strval($res[0])) {
                case '/':
                    $value = $value/$res[1];
                    break;
                
                default:
                    # code...
                    break;
            }
            
        }
        return $data;
    }

    // 判断是否符合时间格式
    public function checkDateTime($data)
    {
        if (!$data) {
            return false;
        }
        if ($data != '-') { // 不等于-，即可（from JQ）
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $num         科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认0位
     * @return string
     */
    // 科学计数法换为数字
    public function sctonum($data)
    {
        if (count($data) == 0) {
            return false;
        }

        $double = 0;
        foreach ($data as $key => &$value) {
            if(false !== stripos($value, "e")){
                $a = explode("e",strtolower($value));
                $value = bcmul($a[0], bcpow(10, $a[1], $double), $double);
            }
        }
        return $data;
    }

    // 金钱格式调整，便于计算
    public function changeSingleMoneyFormat($data, $moneyFormat)
    {
        if (!$data) {
            return false;
        }
        if (strstr($data, " ")) { // 有空白，如AU $5.99
            $re = explode(" ",$data);
            switch ($moneyFormat) {
                case '$':// 无格式 类似 AU $100.00
                    $data = substr($re[1], 1);
                    break;
                case '$-': //有格式 类似Rp 100.00
                    $data = $re[1];
                    break;
                default:
                    # code...
                    break;
            }
        } else {
            switch ($moneyFormat) {
                case '$':// 无格式 类似 $100.00
                    $data = substr($data, 1);
                    break;
                default:
                    # code...
                    break;
            }
        }
        if (strstr($data, ',')) {
            $data = str_replace( ',', '', $data);
        }
        return $data;
    }


    // 将金钱格式处理为符合要求的格式
    public function changeMoneyFormat($data, $moneyFormat = '')
    {
        if (count($data) == 0) {
            return false;
        }
        foreach ($data as $key => $value) {
            if (strstr($value, " ")) { // 有空白，如AU $5.99
                $re = explode(" ",$value);
                if ($moneyFormat) { // 有格式 类似Rp 100.00
                    $data[$key] = $re[1];
                } else { // 无格式 类似 AU $100.00
                    $data[$key] = substr($re[1], 1);
                }
            } else {
                if ($moneyFormat) {
                    $data[$key] = $value;
                } else {
                    $data[$key] = substr($value, 1); //$5.99 去掉左边第一个字符
                }
            }
            if (strstr($data[$key], ',')) {
                $data[$key] = str_replace( ',', '', $data[$key]);
            }
        }
        return $data;
    }

    // 将utc格式时间统一为datetime格式
    public function changeUtcTime($data)
    {
        if (count($data) == 0) {
            return false;
        }
        foreach ($data as $key => $value) {
            if (!$value) {
                continue;
            }
            if (!strstr($value,"UTC")) { // 没有utc
                if (is_numeric($value)) {
                    $time = ($value - 25569) * 24*60*60; //获得秒数
                    $data[$key] = date('Y-m-d H:i:s', $time);   //出来正确格式 2011-10-31
                } else {
                    $data[$key] = date('Y-m-d H:i:s', strtotime($data[$key]));
                }
            } else {
                $data[$key] = date('Y-m-d H:i:s', strtotime($data[$key]) + 8 * 60 * 60);// 北京时间东八区
            }
        }
        return $data;
    }

    // 校验sku
    public function validSku($data)
    {
        $post = I('post.');
        if (!$data) {
            return false;
        }
        $Model = new PmsBaseModel();
        $adJustData = [];

        // 不要循环取查数据库了，耗时太长，根据store_id和site_cd获取third_sku_id数组，判断是否在数组里面即可
        $whereMap = [
            // 'third_sku_id' => $v,
            'store_id' => $post['store_id'],
            'plat_cd' => $post['site_cd'], // 这里的平台指的是该导入需求的站点CD
        ];
        $thirdSkuIdArr = $Model->table('product_sku_relation')->field('sku_id, third_sku_id')->where($whereMap)->select();
        $thirdSkuArr = [];
        foreach ($thirdSkuIdArr as $key => $value) {
            $thirdSkuArr[$value['sku_id']] = $value['third_sku_id'];
        }
        // 反转获取 third_sku_id =>  sku_id
        $thirdThrSkuArr = array_flip($thirdSkuArr);
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                // 校验sku格式
                // 不符合格式，直接按第三方sku来查sku
                //
                $sku_id = ''; 
                if (count($thirdThrSkuArr) !== 0 && count($thirdSkuArr)) {
                    if (strlen($v) == '10') {
                        if (in_array($v, $thirdThrSkuArr)) {
                            $sku_id = $v;
                        }
                        //$sku_id = $Model->table('product_sku')->where('sku_id = '.$v)->getField('sku_id');               
                    }
                    if (strlen($v) !== '10' || !$sku_id) {
                        if (in_array($v, $thirdSkuArr)) {
                            $sku_id = $thirdThrSkuArr[$v];
                        }
                    }
                }
                if (!$sku_id) {
                    throw new Exception("导入失败，导入的SKU不符合ERP中SKU的格式要求，请检查数据格式（sku为{$v}）");
                }
                $adJustData[$key][$k]['sku_id'] = $sku_id; 
                $adJustData[$key][$k]['other_sku_id'] = $v;
            }
        }
        return $adJustData;
    }



    // 获取文件相关信息（最大行数，最大列值）
    public function getExcelInfo($filePath)
    {
        header("content-type:text/html;charset=utf-8");
        // $filePath  = iconv('utf-8', 'gb2312', $filePath); // 防止中文名称乱码读取数据失败
        //$objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                // 判断使用哪种格式
                $PHPReader = new PHPExcel_Reader_CSV(); 
                if (!$PHPReader->canRead($filePath)) {
                    throw new Exception("请上传正确的EXCEL文件");
                }
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();

        $excelInfo['allColumn'] = $allColumn;
        $excelInfo['allRow'] = $allRow;
        $excelInfo['PHPExcel'] = $PHPExcel;
        return $excelInfo;
    }

    // 获取文件的表头hash值
    public function get_excel_hash($filePath = ''){
        $excelInfo = $this->getExcelInfo($filePath);
        $allRow = $excelInfo['allRow'];
        $allColumn = $excelInfo['allColumn'];
        $PHPExcel = $excelInfo['PHPExcel'];

        if ($allRow > 25000) {
            throw new Exception("EXCEL文件行数大小超过25000");
        }

        $data = '';
        for ($currentRow='A'; $currentRow < $allColumn; $currentRow++) { 
            $data .= $PHPExcel->getActiveSheet()->getCell($currentRow . '1')->getValue();
        }

        return hash("sha256", $data);
    }

    // 校验
    public function finTransValid($file_name)
    {
        // 获取原始表的表头进行hash
        if (S($file_name)) {
            $origon_field_hash = S($file_name);    
        } else {
            $origon_field_hash = $this->get_excel_hash($this->originFile . $file_name);
            S($file_name, $origon_field_hash);
        }
        $upload_field_hash = $this->get_excel_hash($_FILES['file']['tmp_name']);
        if ($origon_field_hash != $upload_field_hash) {
            // throw new Exception("上传文档与原始表模板 【{$file_name}】 表头格式不符，请先检查");
            throw new Exception("!导入失败，导入的表格样式不合要求，请通过【查看导入模板】下载模板检查");
        }

        // 初始化excel对象
        $this->phpexcelInfo = $this->getExcelInfo($_FILES['file']['tmp_name']);

        return true;
        // 根据key获取缓存的hash,没有则从文件中获取
    }

    // 获取规则的具体信息（方法名称，原始表模板名称）
    public function getRuleInfo()
    {
        // 没有缓存才查数据表
        $post = I('request.');
        $store_id = $post['store_id'];
        $site_cd = $post['site_cd'];
        $plat_cd = $post['plat_cd'];
        
        $ruleKey = $store_id . '-' . $site_cd . '-' . $plat_cd;
        if (S($ruleKey)) {
            return S($ruleKey);
        } else {
            $conditions['m.store_id'] = ['eq', $store_id];
            $conditions['m.site_cd'] = ['eq', $site_cd];
            $conditions['m.plat_cd'] = ['eq', $plat_cd];
            $FinanceTransferRepository = new FinanceTransferRepository();        
            $res  = $FinanceTransferRepository->getRuleInfo($conditions);
            if (!$res) {
                return false;
            }
            S($ruleKey, $res);
            return $res;
        }

    }

}