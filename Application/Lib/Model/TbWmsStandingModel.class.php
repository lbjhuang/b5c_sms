<?php

class TbWmsStandingModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_center_stock';

    protected $_link = [
        'guds_opt' => [
            'mapping_type' => HAS_ONE,
            'class_name'   => 'TbMsGudsOpt',
            'foreign_key'  => 'GUDS_ID',
        ],
        'guds' => [
            'mapping_type' => BELONGS_TO,
            'class_name'   => 'Guds',
            'foreign_key'  => 'GUDS_ID',
        ],
    ];

    private $_inData; // 写入的数据
    private $_outData; // 写出的数据
    private $_data;
    private $_errorInfo;
    private $_msgCode;
    private $_requestData;
    private $_responseData;

    const DATA_IN = 0;
    const DATA_OUT = 1;

    /**
     * 参数空格处理
     *
     */
    public function parseParamsSpace($params)
    {
        foreach ($params as $k => &$v) {
            if (is_array($v)) {
                $this->parseParamsSpace($v);
            } else {
                $v = trim($v);
            }
        }
        return $params;
    }

    public function search($params)
    {
        $params = $this->parseParamsSpace($params);
        $conditions = [];
        if ($params ['SKU']) {
            $where ['tb_wms_center_stock.SKU_ID']     = ['like', "%" . $params ['SKU'] . "%"];
            $where ['stab8.upc_id']     = ['like', "%" . $params ['SKU'] . "%"];
            //$where ['tb_ms_guds_opt.GUDS_OPT_CODE']   = ['like', "%" . $params ['SKU'] . "%"];
            //$where ['tb_ms_guds_opt.GUDS_OPT_UPC_ID'] = ['like', "%" . $params ['SKU'] . "%"];
            //$where ['tb_ms_guds.GUDS_CODE']           = ['like', "%" . $params ['SKU'] . "%"];
            $where['_logic'] = 'or';
            $conditions [] = $where;
        }
        empty($params ['GUDS_CNS_NM']) or $conditions ['stab1.spu_name'] = ['like', "%" . $params ['GUDS_CNS_NM'] . "%"];
       // empty($params ['house_list_model']) or $conditions []['tb_ms_guds.DELIVERY_WAREHOUSE'] = ['eq', $params ['house_list_model']];
        // 库存可以为0，在途不为0
        if ($params ['def_sku_none'] == 0) {
            $conditions ['all_inventory'] = ['neq', 0];
        } else {
            unset($conditions ['all_inventory']);
        }
        //!empty($params ['DELIVERY_WAREHOUSE']) or $conditions ['tb_ms_guds.DELIVERY_WAREHOUSE'] = ['neq', 'null'];
        $conditions['tb_wms_center_stock.channel'] = ['eq', 'N000830100'];
//        $cond_t [] = [
//            'tb_wms_center_stock.total_inventory' => ['neq', 0],
//        ];
//        $cond_t ['_logic'] = 'or';
//        $cond_t [] = [
//            'tb_wms_center_stock.total_inventory' => ['eq', 0],
//            'tb_wms_center_stock.on_way'          => ['neq', 0],
//        ];
//        if ($params ['def_sku_none'] == 1) {
//            $cond_t = null;
//        } else {
//            $conditions [] = $cond_t;
//        }

        return $conditions;
    }

    /**
     * 生成子查询条件
     * @param $calcu boolean 是否是计算
     */
    public function build_child_search_conditions($params, $calcu = false)
    {
        $params = $this->parseParamsSpace($params);
        $where = ' and ';
        if ($calcu) $where = ' ';
        if ($params ['SKU'] and $calcu) {
            $where .= ' t1.SKU_ID = "' . $params ['SKU'] . '" and ';
        }
        if ($params ['GUDS_CNS_NM'] and $calcu) {
            $where .= ' t2.GUDS_NM like "%' . $params ['GUDS_CNS_NM'] . '%" and ';
        }
        if ($params ['house_list_model']) {
            $houses = ' t3.warehouse_id in (';
            foreach ($params ['house_list_model'] as $k => $v) {
                $houses .= '"' . $v . '",';
            }
            $houses = rtrim($houses, ',');
            $houses .= ') and ';
            $where .= $houses;
        }
        // 采购单号
        if ($params ['pru_order_no'] and $calcu == false) {
            $where .= ' t1.purchase_order_no = "' . $params ['pru_order_no'] . '" and ';
        } elseif ($params ['pru_order_no'] and $calcu == true) {
            $where .= ' t3.procurement_number = "' . $params ['pru_order_no'] . '" and ';
        }
        // 销售团队
        if ($params ['select_sale_teams']) {
            $sales = ' t1.sale_team_code in (';
            foreach ($params ['select_sale_teams'] as $k => $v) {
                $sales .= '"'. $v . '",';
            }
            $sales = rtrim($sales, ',');
            $sales .= ') and ';
            $where .= $sales;
        }
        // 采购团队
        if ($params ['select_sp_teams']) {
            $sp = ' t1.purchase_team_code in (';
            foreach ($params ['select_sp_teams'] as $k => $v) {
                $sp .= '"' . $v . '",';
            }
            $sp = rtrim($sp, ',');
            $sp .= ') and ';
            $where .= $sp;
        }
        // 公司
        if ($params ['select_company_teams'] and $calcu == false) {
            $co = ' t1.our_company in (';
            foreach ($params ['select_company_teams'] as $k => $v) {
                $co .= '"'. $v . '",';
            }
            $co = rtrim($co, ',');
            $co .= ') and ';
            $where .= $co;
        } elseif ($params ['select_company_teams'] and $calcu == true) {
            $co = ' t3.our_company in (';
            foreach ($params ['select_company_teams'] as $k => $v) {
                $co .= '"'. $v . '",';
            }
            $co = rtrim($co, ',');
            $co .= ') and ';
            $where .= $co;
        }
        // 采购时间
        if ($params ['sp_time']) {
            if ($params ['sp_time'][0]) {
                $where .= ' t1.procurement_date >= "' . $params ['sp_time'][0] . '" and ';
            }
            if ($params ['sp_time'][1]) {
                $where .= ' t1.procurement_date <= "' . $params ['sp_time'][1] . '" and ';
            }
        }
        // 入库时间
        if ($params ['in_time']) {
            if ($params ['in_time'][0]) {
                $where .= ' t1.create_time >= "' . date('Y-m-d', strtotime($params ['in_time'][0])) . '  00:00:00" and ';
            }
            if ($params ['in_time'][1]) {
                $where .= ' t1.create_time < "' . date('Y-m-d', strtotime($params ['in_time'][1])) . '  23:59:59" and ';
            }
        }
        // 到期日
        if ($params ['expire_time']) {
            if ($params ['expire_time'][0]) {
                $where .= ' t1.deadline_date_for_use >= "' . date('Y-m-d', strtotime($params ['expire_time'][0])) . ' 00:00:00" and';
            }
            if ($params ['expire_time'][1]) {
                $where .= ' t1.deadline_date_for_use < "' . date('Y-m-d', strtotime($params ['expire_time'][1])) . ' 23:59:59" and';
            }
        }
        // 是否滞销
        if ($params ['unsalable']) {
            $where .= ' 
                CASE t1.cat_level1 WHEN t1.cat_level1 = 6 AND PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(t1.create_time, \'%Y%m\')) > 3 THEN 1 WHEN t1.cat_level1 <> 6 AND PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(t1.create_time, \'%Y%m\')) > 5 THEN 1 ELSE 2 END AND';
            if ($params ['unsalable'] == 1) {
                $where .= ' t1.cat_level1 = 1 ';
            } else {
                $where .= ' t1.cat_level1 = 2 ';
            }
        }
        //$where .= ' t2.id IS NOT NULL and';
        $where = rtrim($where, 'and ');
        return $where;
    }

    /**
     * 生成子查询条件
     *
     */
    public function build_child_search_conditions_batch($params)
    {
        $params = $this->parseParamsSpace($params);
        $where = '';
        // 采购单号
        if ($params ['pru_order_no']) {
            $where .= ' td.procurement_number = "' . $params ['pru_order_no'] . '" and ';
        }
        // 销售团队
        if ($params ['select_sale_teams']) {
            $sales = ' a.sale_team_code in (';
            foreach ($params ['select_sale_teams'] as $k => $v) {
                $sales .= '"'. $v . '",';
            }
            $sales = rtrim($sales, ',');
            $sales .= ') and ';
            $where .= $sales;
        }
        // 采购团队
        if ($params ['select_sp_teams']) {
            $sp = ' a.purchase_team_code in (';
            foreach ($params ['select_sp_teams'] as $k => $v) {
                $sp .= '"' . $v . '",';
            }
            $sp = rtrim($sp, ',');
            $sp .= ') and ';
            $where .= $sp;
        }
        // 公司
        if ($params ['select_company_teams']) {
            $co = ' td.our_company in (';
            foreach ($params ['select_company_teams'] as $k => $v) {
                $co .= '"'. $v . '",';
            }
            $co = rtrim($co, ',');
            $co .= ') and ';
            $where .= $co;
        }
        // 采购时间
        if ($params ['sp_time']) {
            if ($params ['sp_time'][0]) {
                $where .= ' td.procurement_date >= "' . $params ['sp_time'][0] . '" and ';
            }
            if ($params ['sp_time'][1]) {
                $where .= ' td.procurement_date <= "' . $params ['sp_time'][1] . '" and ';
            }
        }
        // 入库时间
        if ($params ['in_time']) {
            if ($params ['in_time'][0]) {
                $where .= ' a.create_time >= "' . date('Y-m-d', strtotime($params ['in_time'][0])) . ' 00:00:00" and ';
            }
            if ($params ['in_time'][1]) {
                $where .= ' a.create_time < "' . date('Y-m-d', strtotime($params ['in_time'][1])) . '  23:59:59" and ';
            }
        }
        // 到期日
        if ($params ['expire_time']) {
            if ($params ['expire_time'][0]) {
                $where .= ' a.deadline_date_for_use >= "' . date('Y-m-d', strtotime($params ['expire_time'][0])) . ' 00:00:00" and';
            }
            if ($params ['expire_time'][1]) {
                $where .= ' a.deadline_date_for_use < "' . date('Y-m-d', strtotime($params ['expire_time'][1])) . ' 23:59:59" and';
            }
        }
        // 是否滞销
        if ($params ['unsalable']) {
            $where .= ' 
                CASE t1.cat_level1 WHEN t1.cat_level1 = 6 AND PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(a.create_time, \'%Y%m\')) > 3 THEN 1 WHEN t1.cat_level1 <> 6 AND PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(a.create_time, \'%Y%m\')) > 5 THEN 1 ELSE 2 END AND';
            if ($params ['unsalable'] == 1) {
                $where .= ' t1.cat_level1 = 1 AND';
            } else {
                $where .= ' t1.cat_level1 = 2 AND';
            }
        }
        // 仓库
        if ($params ['house_list_model']) {
            $houses = ' tb_wms_bill.warehouse_id in (';
            foreach ($params ['house_list_model'] as $k => $v) {
                $houses .= '"' . $v . '",';
            }
            $houses = rtrim($houses, ',');
            $houses .= ') and ';
            $where .= $houses;
        }
        $where .= ' tb_wms_bill.warehouse_id IS NOT NULL and a.total_inventory > 0 and';
        $where = rtrim($where, 'and ');
        return $where;
    }

    /**
     * @return 将查询条件组装为字符串
     * @return String
     *
     */
    public function assembleSearchConditions($params)
    {
        $where = '';
        $tmp = '';
        $params = $this->parseParamsSpace($params);
        if ($params ['GUDS_CNS_NM']) {
            $tmp .= 'c.GUDS_NM like "%' . $params ['GUDS_CNS_NM'] . '%" and ';
        }
        if ($params ['SKU']) {
            $tmp .= '(a.SKU_ID like "%' . $params ['SKU'] .  '%" and ';
        }
        if ($params ['select_sale_teams']) {
            $sales = '(';
            foreach ($params ['select_sale_teams'] as $key => $value) {
                $sales .= '"' . $value . '",';
            }
            $sales = rtrim($sales, ', ');
            $sales .= ') ';
            $tmp .= 'a.sale_team_code in ' . $sales . ' and ';
        }
        if ($params ['house_list_model']) {
            $houses = ' tb_wms_bill.warehouse_id in (';
            foreach ($params ['house_list_model'] as $k => $v) {
                $houses .= '"' . $v . '",';
            }
            $houses = rtrim($houses, ',');
            $houses .= ') and ';
            $tmp .= $houses;
        }

//        if ($params ['house_list_model']) {
//            $tmp .= 'tb_wms_bill.warehouse_id = "' . $params ['house_list_model'] . '" and ';
//        }
        if ($params ['pru_order_no']) {
            $tmp .= 'td.procurement_number = "' . $params ['pru_order_no'] . '" and ';
        }
        if ($params ['select_sp_teams']) {
            $sps = '(';
            foreach ($params ['select_sp_teams'] as $key => $value) {
                $sps .= '"' . $value . '",';
            }
            $sps = rtrim($sps, ', ');
            $sps .= ') ';
            $tmp .= 'a.purchase_team_code in ' . $sps . ' and ';
        }
        if ($params ['select_company_teams']) {
            $cos = '(';
            foreach ($params ['select_company_teams'] as $key => $value) {
                $cos .= '"' . $value . '",';
            }
            $cos = rtrim($cos, ', ');
            $cos .= ') ';
            $tmp .= 'td.our_company in ' . $cos . ' and ';
        }
        // 入库时间
        if ($params ['in_time']) {
            if ($params ['in_time'][0]) {
                $tmp .= ' a.create_time >= "' . date('Y-m-d', strtotime($params ['in_time'][0])) . '  00:00:00" and ';
            }
            if ($params ['in_time'][1]) {
                $tmp .= ' a.create_time < "' . date('Y-m-d', strtotime($params ['in_time'][1])) . '  23:59:59" and ';
            }
        }
        // 到期日
        if ($params ['expire_time']) {
            if ($params ['expire_time'][0]) {
                $tmp .= ' a.deadline_date_for_use >= "' . date('Y-m-d', strtotime($params ['expire_time'][0])) . ' 00:00:00" and ';
            }
            if ($params ['expire_time'][1]) {
                $tmp .= ' a.deadline_date_for_use < "' . date('Y-m-d', strtotime($params ['expire_time'][1])) . ' 23:59:59" and ';
            }
        }
        $tmp .= ' a.total_inventory > 0 and ';
        if ($tmp) {
            $where = ' where ';
            $where .= $tmp;
            $where = rtrim($where, 'and ');
        }

        return $where;
    }

    public function build_search_conditions($conditions)
    {
        $where = '';
        foreach ($conditions as $key => $value) {
            if (is_int($value[1])) {
                if (!$value [1]) $value [1] = NULL;
                $where .= $key . $value [0] . $value [1] . ' and ';
            } else {
                $where .= $key . $value [0] . '"' . $value [1] . '"' . ' and ';
            }
        }
        if ($where) {
            $where = rtrim($where, ' and');
            $where = ' where ' . $where;
        }
        return $where;
    }


    public $attributes = [
        ['SKU_ID', 'require' => 'both', 'message' => 'SKU_ID'],
        ['GUDS_ID', 'require' => 'none', 'message' => 'SPU_ID'],
        ['channel', 'require' => 'none', 'message' => '渠道'],
        ['CHANNEL_SKU_ID', 'require' => 'none', 'message' => '渠道SKU_ID'],
        ['TYPE', 'require' => 'both', 'message' => '新增，减少(0新增，1减少)'],
    ];

    /**
     * 错误码
     *
     */
    public function msgCode()
    {
        return [
            '10000001' => '无数据',
            '10000010' => '参数缺失',
            '10000101' => '新增在途、在途金失败',
            '10000110' => '减少在途、在途金失败',
            '10000111' => '操作成功',
            '10001011' => '操作失败',
        ];
    }

    /**
     * 在途与在途金写入
     *
     */
    public function onWayAndOnWayMoney($data)
    {
        if ($data) {
            //$this->_data = $data;
            $this->setRequestData($data);
            $this->main();
        } else {
            $this->_msgCode = 10000001;
        }
        $this->setResponseData($ret = $this->parseInfo());
        $this->_catchMe();
        return $ret;
    }

    public function main()
    {
        $this->startTrans();
        try {
            if ($this->_validata()) {
                $this->classification(); //数据分类
                if ($this->writeData()) {
                    $this->commit();
                    $this->_msgCode = 10000111;
                } else {
                    $this->rollback();
                }
            } else {
                $this->_msgCode = 10000010;
            }
        } catch (\Exception $e) {
            $this->rollback();
        }
    }

    public function writeData()
    {
        $inOk = true;
        $outOk = true;
        if (empty($this->_inData) and empty($this->_outData)) {
            $isok = false;
            $this->_msgCode = 10000001;
        } else {
            if ($this->_inData) {
                foreach ($this->_inData as $k => $v) {
                    $where ['SKU_ID'] = ['eq', $v ['SKU_ID']];
                    $where ['channel'] = ['eq', $v ['channel']];
                    $where ['CHANNEL_SKU_ID'] = ['eq', $v ['CHANNEL_SKU_ID']];
                    if (!$this->where($where)->setInc('on_way', $v ['on_way']) or !$this->where($where)->setInc('on_way_money', $v ['on_way_money'])) {
                        $inOk = false;
                        $info = [];
                        $info ['SKU_ID'] = $v['SKU_ID'];
                        $info ['MSG'] = $this->msgCode()[10000101] . '，未查询到数据';
                        $this->_errorInfo [] = $info;
                    }
                }
            }
            if ($this->_outData) {
                foreach ($this->_outData as $k => $v) {
                    $where ['SKU_ID'] = ['eq', $v ['SKU_ID']];
                    $where ['channel'] = ['eq', $v ['channel']];
                    $where ['CHANNEL_SKU_ID'] = ['eq', $v ['CHANNEL_SKU_ID']];
                    if (!$this->where($where)->setDec('on_way', $v ['on_way']) or !$this->where($where)->setDec('on_way_money', $v ['on_way_money'])) {
                        $outOk = false;
                        $info = [];
                        $info ['SKU_ID'] = $v['SKU_ID'];
                        $info ['MSG'] = $this->msgCode()[10000110] . '，未查询到数据';
                        $this->_errorInfo [] = $info;
                    }
                }
            }
        }
        if ($inOk and $outOk) {
            $this->_msgCode = 10000111;
            return true;
        }
        $this->_msgCode = 10001011;
        return false;
    }

    /**
     * 数据分类、填充
     *
     */
    public function classification()
    {
        foreach ($this->getRequestData() as $key => $info) {
            if ($info['TYPE'] == self::DATA_IN) {
                // 数据优先填充
                if (!isset($info ['channel'])) $info ['channel'] = 'N000830100';
                if (!isset($info ['CHANNEL_SKU_ID'])) $info ['CHANNEL_SKU_ID'] = '0';
                unset($info ['TYPE']);
                $this->_inData [] = $info;
            } else {
                if (!isset($info ['channel'])) $info ['channel'] = 'N000830100';
                if (!isset($info ['CHANNEL_SKU_ID'])) $info ['CHANNEL_SKU_ID'] = '0';
                unset($info ['TYPE']);
                $this->_outData [] = $info;
            }
        }
    }

    /**
     * 数据验证
     * 对订单与商品必填的数据进行校验，若有缺失则返回相对应的错误信息
     * @return boolean
     */
    private function _validata()
    {
        foreach ($this->getRequestData() as $key => $info) {
            foreach ($this->attributes as $k => $value) {
                if (!isset($info [$value [0]]) and $value ['require'] == 'both') $this->_errorInfo [][$info['SKU_ID']][$value [0]] = $value ['message'];
            }
        }
        if ($this->_errorInfo) return false;
        return true;
    }

    /**
     * 消息回送
     * @return code code值，预先定义。msg 信息类型文本提示。info 具体提示
     */
    public function parseInfo()
    {
        if (is_array($this->_msgCode)) {
            foreach ($this->_msgCode as $key => $v) {
                $msg .= $this->msgCode()[$v] . ':';
            }
        } else {
            $msg = $this->msgCode()[$this->_msgCode];
        }
        $ret = ['code' => $this->_msgCode, 'msg' => $msg, 'info' => $this->_errorInfo];
        return $ret;
    }
}