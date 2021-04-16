<?php

class CommissionModel extends BaseModel
{
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];

    public $count;
    public $pageIndex;
    public $pageSize;

    const JUDGE_EQ = 'N002610100';//等于
    const JUDGE_NEQ = 'N002610200';//不等于
    const JUDGE_GREATER = 'N002610201';//大于
    const JUDGE_LESS = 'N002610202';//小于
    const JUDGE_GREATER_EQ = 'N002610203';//大或等于
    const JUDGE_LESS_EQ = 'N002610204';//小或等于
    const DIMENSION_SKU = 'N002600500';//佣金配置-维度-SKU编号
    const DIMENSION_SALE_PRICE = 'N002600501';//佣金配置-维度-商品销售单价

    const RULE_OPEN = 1;//佣金规则开启中
    const RULE_CLOSE = 0;//佣金规则关闭中

    public function getList($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['page']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['page'];

        $field = [
            "s1.STORE_NAME as store_name",
            "s1.STORE_STATUS as store_status",
            "s1.ID as store_id",
            "tb_ms_cmn_cd.CD_VAL as plat_name",
            "c1.is_enable",
        ];

        $this->subWhere('s1.STORE_STATUS', ['eq', $params ['store_status']]);
        if (!empty($params ['plat_cds'])) {
            static::$where['s1.PLAT_CD'] = ['in', $params ['plat_cds']];
        }
        if (!empty($params ['store_ids'])) {
            static::$where['s1.ID'] = ['in', $params ['store_ids']];
        }

        $query = $this->field($field)
            ->table('tb_ms_store s1')
            ->join('left join (select store_id,is_enable from tb_fin_commission_configuration where is_enable=1 group by store_id) as c1 on s1.ID=c1.store_id')
            ->join('left join tb_ms_cmn_cd on s1.PLAT_CD = tb_ms_cmn_cd.CD')
            ->where(static::$where);
        if ($params ['is_enable'] === "0") {
            $query = $query->where(['c1.is_enable' => ['exp','is null']]);
        } else if ($params ['is_enable'] === "1") {
            $query = $query->where(['c1.is_enable' => ['exp','is not null']]);
        }
        $query_clone = clone $query;
        $count = $query_clone->count();
        $res = $query->limit(($pageIndex-1)*$pageSize, $pageIndex*$pageSize)->select();
        foreach ($res as $key => &$value) {
            if ($value['is_enable']) {
                $value['is_enable_name'] = '启用中';
            } else {
                $value['is_enable_name'] = '未启用';
            }
            if ($value['store_status'] == 0) {
                $value['store_status_name'] = '运营中';
            } else if ($value['store_status'] == 1) {
                $value['store_status_name'] = '未运营';
            } else {
                $value['store_status_name'] = '未知状态';
            }
        }
        $ret = [
            'pageNo' => $pageIndex,
            'pageSize' => $pageSize,
            'totalPage' => ceil($count / $pageSize),
            'totalCount' => $count,
            'parmeterMap' => $params,
            'pageData' => $res
        ];
        return $ret;
    }

    public function getStoreRules($store_id, $params, $isExcel = false)
    {
        if (!$store_id) {
            $this->setError(L('店铺id不存在'), '3000');
            return false;
        }
        $store_info = M()->field([
            "s1.STORE_NAME as store_name",
            "s1.STORE_STATUS as store_status",
            "tb_ms_cmn_cd.CD_VAL as plat_name",
        ])
            ->table('tb_ms_store s1')
            ->join(['left join tb_ms_cmn_cd on s1.PLAT_CD = tb_ms_cmn_cd.CD'])
            ->where(['s1.ID' => $store_id])
            ->find();
        if (empty($store_info)) {
            $this->setError(L('店铺不存在'), '3001');
            return false;
        }
        if ($store_info['store_status'] == 0) {
            $store_info['store_status_name'] = '运营中';
        } else if ($store_info['store_status'] == 1) {
            $store_info['store_status_name'] = '未运营';
        } else {
            $store_info['store_status_name'] = '未知状态';
        }

        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params ['pageSize']) or $pageSize = $params ['pageSize'];
        is_null($params ['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params ['pageIndex'];

        $field = [
            "c1.*",
            "r1.dimension_cd",
            "r1.judge_cd",
            "r1.value",
            "r1.rule_detail", //佣金规则详情
//            "cd1.CD_VAL as dimension_name",
            "pms_d1.cat_name as value_name",
        ];
        $this->subWhere('c1.store_id', ['eq', $store_id])
            ->subWhere('c1.is_enable', ['eq', $params ['is_enable']])
            ->subWhere('c1.updated_at', ['xrange', [$params ['update_start_time'], $params ['update_end_time']]]);
        $count = 0;
        $res = [];
        $query = $this->field($field)
            ->table(B5C_DATABASE. '.tb_fin_commission_configuration c1')
            ->join('left join '. B5C_DATABASE. '.tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id')
            ->join('left join '. PMS_DATABASE. '.product_category_detail pms_d1 on r1.value = pms_d1.id')
//            ->join(['left join tb_ms_cmn_cd cd1 on r1.dimension_cd = cd1.CD'])
            ->where(static::$where);
        $query_clone = clone $query;
        $count = $query_clone->count();
        if ($count > 0) {
            $dimension = self::dimension();
            $currency = self::getCurrency();
            import('ORG.Util.Page');
            $page = new Page($count, $pageSize);
            $judge2 = CommonDataModel::judge2();
            $currency = CommonDataModel::currency();
            $judge2 = array_column($judge2, 'cdVal', 'cd');
            $currency = array_column($currency, 'cdVal', 'cd');
            $res = $query->limit($page->firstRow, $page->listRows)->order('c1.is_enable desc,c1.updated_at desc')->select();
            foreach ($res as $k => &$v) {
                if ($v['rate'] === NULL) {
                    $v['rate_percentage'] = NULL;
                } else {
                    $v['rate_percentage'] = bcmul($v['rate'],100,2). '%';
                }
                $v['dimension_name'] = $dimension[$v['dimension_cd']];
                $v['lowest_currency_cd_name'] = $currency[$v['lowest_currency_cd']];
                $v['highest_currency_cd_name'] = $currency[$v['highest_currency_cd']];
                if ($v['judge_cd'] == self::JUDGE_EQ) {
                    $v['judge_cd_name'] = '为';
                } else if ($v['judge_cd'] == self::JUDGE_NEQ) {
                    $v['judge_cd_name'] = '不为';
                } else {
                    $v['judge_cd_name'] = '';
                }
                if ($v['dimension_cd'] == self::DIMENSION_SKU) {
                    //sku
                    $v['value_name'] = $v['value'];
                }
                if ($v['dimension_cd'] == self::DIMENSION_SALE_PRICE) {
                    //price
                    $v['judge_cd_name'] = $this->ruleDetailMap($v['rule_detail'], $judge2, $currency);
                }
            }
        }

        $ret = [
            'pageNo' => $pageIndex,
            'pageSize' => $pageSize,
            'totalPage' => ceil($count / $pageSize),
            'totalCount' => $count,
            'parmeterMap' => $params,
            'pageData' => $res,
            'store_info' => $store_info,
        ];
        return $ret;
    }

    //佣金配置-维度-商品销售单价-规则文案生成
    public function ruleDetailMap($data, $judge2, $currency){
        $rule_detail = json_decode($data, true);
        $judge_cd_names = [];
        foreach ($rule_detail as $key => $value) {
            if ($value['judge_cd'] == self::JUDGE_EQ) {
                $judge_cd_name = '为';
            } else if ($value['judge_cd'] == self::JUDGE_NEQ) {
                $judge_cd_name = '不为';
            } else {
                $judge_cd_name = $judge2[$value['judge_cd']];
            }
            $judge_cd_names[] = $judge_cd_name . ' ' . $value['value'] . ' ' . $currency[$value['currency']];
        }
        $judge_cd_name = implode(' 且 ', $judge_cd_names);
        return $judge_cd_name;
    }

    public function getCommissionRule($id) {
        if (empty($id)) {
            $this->setError(L('规则id不能为空'), '3000');
            return false;
        }
        $field = [
            'c1.*',
            'r1.commission_configuration_id',
            'r1.dimension_cd',
            'r1.judge_cd',
            'r1.value',
            'r1.rule_detail',
        ];
        $res = $this->field($field)
            ->table('tb_fin_commission_configuration c1')
            ->join(['left join tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id'])
            ->where(['c1.id' => $id])
            ->find();
        $res['rule_detail'] = json_decode($res['rule_detail'], true);
        return $res;
    }

    public function addCommissionRule($data) {
        if (!$this->validateRuleData($data)) {
            return false;
        }
        $date = $this->getTime();
        $admin_name = $_SESSION['m_loginname'];
        $conf_data = [
            'store_id' => $data['store_id'],
            'rule_no' => date(Ymd). TbWmsNmIncrementModel::generateCustomNo(),
            'lowest_currency_cd' => $data['lowest_currency_cd'],
            'highest_currency_cd' => $data['highest_currency_cd'],
            'created_by' => $admin_name,
            'created_at' => $date,
            'updated_by'=> $admin_name //便于日志时展示操作人
        ];
        // 增加生效开始时间，生效结束时间
        if($data['start_time'] === ""){
            $conf_data['start_time'] = NULL;
        }else{
            $conf_data['start_time'] = $data['start_time'];
        }
        if($data['end_time'] === ""){
            $conf_data['end_time'] = NULL;
        }else{
            $conf_data['end_time'] = $data['end_time'];
        }

        if ($data['rate'] === "") {
            $conf_data['rate'] = NULL;
        } else {
            $conf_data['rate'] = $data['rate'];
        }
        if ($data['lowest_commission'] === "") {
            $conf_data['lowest_commission'] = NULL;
        } else {
            $conf_data['lowest_commission'] = $data['lowest_commission'];
        }
        if ($data['highest_commission'] === "") {
            $conf_data['highest_commission'] = NULL;
        } else {
            $conf_data['highest_commission'] = $data['highest_commission'];
        }

        $this->startTrans();
        if (!$conf_id = M('fin_commission_configuration', 'tb_')->add($conf_data)) {
            $this->rollback();
            $this->setError(L('添加佣金规则配置失败'), 3000);
            Logs(json_encode($conf_data), __FUNCTION__.' fail', __CLASS__);
            return false;
        }

        $rule_data = [
            'commission_configuration_id' => $conf_id,
            'dimension_cd' => empty($data['dimension_cd']) ? '' : $data['dimension_cd'],
            'judge_cd' => empty($data['judge_cd']) ? '' : $data['judge_cd'],
            'value' => empty($data['value']) ? '' : $data['value'],
            'created_by' => $admin_name,
            'created_at' => $date,
        ];
        if ($data['dimension_type'] == '2' && $data['dimension_cd'] == 'N002600501') {
            $rule_data['rule_detail'] = json_encode($data['rule_detail']);
        }

        if (!M('fin_commission_rule', 'tb_')->add($rule_data)) {
            $this->rollback();
            $this->setError(L('添加佣金条件配置失败'), 3000);
            Logs(json_encode($rule_data), __FUNCTION__.' fail', __CLASS__);
            return false;
        }
        $this->commit();
        return true;
    }

    public function updateCommissionRule($data) {
        if (!$this->validateRuleData($data)) {
            return false;
        }
        $model = M('fin_commission_configuration', 'tb_');
        $rule = $model->find($data['id']);
        if ($rule['is_enable'] == self::RULE_OPEN) {
            $this->setError(L('启用中的规则不能编辑'), 3000);
            return false;
//            if (!$this->checkEnableRule($data['id'], $data['store_id'], $data)) {
//                return false;
//            }
        }
        $date = $this->getTime();
        $admin_name = $_SESSION['m_loginname'];
        $conf_data = [
            'lowest_currency_cd' => $data['lowest_currency_cd'],
            'highest_currency_cd' => $data['highest_currency_cd'],
            'updated_by' => $admin_name,
            'update_at' => $date,
        ];
        // 增加生效开始时间，生效结束时间
        if($data['start_time'] === ""){
            $conf_data['start_time'] = NULL;
        }else{
            $conf_data['start_time'] = $data['start_time'];
        }
        if($data['end_time'] === ""){
            $conf_data['end_time'] = NULL;
        }else{
            $conf_data['end_time'] = $data['end_time'];
        }

        if ($data['rate'] === "") {
            $conf_data['rate'] = NULL;
        } else {
            $conf_data['rate'] = $data['rate'];
        }
        if ($data['lowest_commission'] === "") {
            $conf_data['lowest_commission'] = NULL;
        } else {
            $conf_data['lowest_commission'] = $data['lowest_commission'];
        }
        if ($data['highest_commission'] === "") {
            $conf_data['highest_commission'] = NULL;
        } else {
            $conf_data['highest_commission'] = $data['highest_commission'];
        }

        $this->startTrans();
        if ($model->where(['id' => $data['id']])->save($conf_data) === false) {
            $this->rollback();
            $this->setError(L('编辑佣金规则配置失败'), 3000);
            Logs(json_encode($conf_data), __FUNCTION__.' fail', __CLASS__);
            return false;
        }

        $rule_data = [
            'dimension_cd' => empty($data['dimension_cd']) ? '' : $data['dimension_cd'],
            'judge_cd' => empty($data['judge_cd']) ? '' : $data['judge_cd'],
            'value' => empty($data['value']) ? '' : $data['value'],
            'updated_by' => $admin_name,
            'update_at' => $date,
        ];
        if ($data['dimension_type'] == '2' && $data['dimension_cd'] == 'N002600501') {
            $rule_data['rule_detail'] = json_encode($data['rule_detail']);
        }

        $condition = ['commission_configuration_id' => $data['id']];
        if (M('fin_commission_rule', 'tb_')->where($condition)->save($rule_data) === false) {
            $this->rollback();
            $this->setError(L('编辑佣金条件配置失败'), 3000);
            Logs(json_encode($rule_data), __FUNCTION__.' fail', __CLASS__);
            return false;
        }
        $this->commit();
        return true;
    }

    public function changeRuleStatus($rule_id, $status, $store_id) {
        if (empty($rule_id)) {
            $this->setError(L('规则id未找到'), '3000');
            return false;
        }
        if (empty($store_id)) {
            $this->setError(L('规则id未找到'), '3000');
            return false;
        }
        if (!in_array($status, [self::RULE_CLOSE,self::RULE_OPEN])) {
            $this->setError(L('未知状态'), '3000');
            return false;
        } //var_dump($this->checkEnableRule($rule_id, $store_id));die;
        if ($status == self::RULE_OPEN && !$this->checkEnableRule($rule_id, $store_id)) return false;
        $updated_by = $_SESSION['m_loginname'];
        $data = [
            'is_enable' => $status,
            'updated_by'=>$updated_by
        ];
        if(!(new Model('commission_configuration', 'tb_fin_'))->where(['id' => $rule_id])->save($data)) {
            // echo (new Model('commission_configuration', 'tb_fin_'))->getLastSql();die;
            $this->setError(L('改变状态失败'), '3000');
            return false;
        }
        return true;
    }

    private function checkEnableRule($rule_id, $store_id, $update_data) {
        $rule = $this->getCommissionRule($rule_id);
        if (empty($rule)) {
            $this->setError(L('未找到佣金规则'), '3000');
            return false;
        }
        if ($rule['dimension_cd'] == 'N002600501') {
            return $this->checkPriceEnableRule($rule_id, $store_id);
        }

        $field = [
            'c1.*',
            'r1.commission_configuration_id',
            'r1.dimension_cd',
            'r1.judge_cd',
            'r1.value',
            'r1.rule_detail',
        ];
        // 开启规则时候增加生效时间的校验
        $status = $this->checkTime($rule_id,$store_id);
        //var_dump($status);die;
        if($status['status'] ==1){
            //$where['c1.rule_id'][] = ['neq',$rule_id];
//            if($status['id']!=""){
//                $where['c1.rule_id'] = $status['id'];
//
//            }
            if (!empty($update_data)) {
                //改成了开启状态不允许编辑，此处不实现
            } else {


                if ($rule['judge_cd'] == self::JUDGE_EQ) {

                    $where = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => $rule['judge_cd'],
                        'r1.value' => $rule['value']
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $where['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_complex'] = $where;
                    $condition = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => ['neq', $rule['judge_cd']],
                        'r1.value' => ['neq', $rule['value']]
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $condition['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_logic'] = 'or';
                    $map[] = $condition;
                } else if ($rule['judge_cd'] == self::JUDGE_NEQ) {
                    $where = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => $rule['judge_cd'],
                      //  'r1.value' => ['eq', $rule['value']],
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $where['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_complex'] = $where;
                    $condition = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => ['neq', $rule['judge_cd']],
                        'r1.value' => ['neq', $rule['value']],
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $condition['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_logic'] = 'or';
                    $map[] = $condition; //var_dump($map);die;
                } else {
                    $where = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => $rule['judge_cd'],
                        'r1.value' => $rule['value']
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $where['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_complex'] = $where;
                    $condition = [
                        'c1.store_id' => $store_id,
                        'c1.is_enable' => self::RULE_OPEN,
                        'r1.dimension_cd' => $rule['dimension_cd'],
                        'r1.judge_cd' => ['neq', $rule['judge_cd']],
                        'r1.value' => $rule['value'],
                    ];
                    if(!empty($status['id'])){
                        $temp_id = implode(',',$status['id']);
                        $condition['c1.id'] = array('in',  $temp_id);

                    }
                    $map['_logic'] = 'or';
                    $map[] = $condition;
                }
            }  //var_dump($map);die;
            if ($update_data) {
                //改成了开启状态不允许编辑，此处不实现
//            $temp_res = $this->field($field)
//            ->table('tb_fin_commission_configuration c1')
//            ->join(['inner join tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id'])
//            ->where($map)
//            ->select();
//            foreach ($temp_res as $v) {
//                if ($v['id'] == $rule_id) {
//                    continue;
//                }
//                $this->setError(L('与' . $v['rule_no'] . '规则冲突，无法启用'), '3000');
//                return false;
//            }
            } else {  //var_dump($map);die;
                //var_dump($map);die;
                $db_res = $this->field($field)
                    ->table('tb_fin_commission_configuration c1')
                    ->join(['inner join tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id'])
                    ->where($map)
                    ->find();
            } //var_dump($status['id']);die;
            //echo M('tb_fin_commission_configuration')->getLastSql();die;
            if (!empty($db_res)) {
                $this->setError(L('与'.$db_res['rule_no'].'规则冲突，无法启用'), '3000');
                return false;
            }
        }

        return true;
    }

    public function checkPriceEnableRule($rule_id, $store_id) {
        $rule = $this->getCommissionRule($rule_id);
        //其他已开启的维度为商品销售单价的规则
        $field = [
            'c1.*',
            'r1.commission_configuration_id',
            'r1.dimension_cd',
            'r1.judge_cd',
            'r1.value',
            'r1.rule_detail',
        ];
        $where = [
            'c1.store_id' => $store_id,
            'c1.is_enable' => self::RULE_OPEN,
            'r1.dimension_cd' => $rule['dimension_cd'],
            '_string' => 'c1.id <> ' . $rule_id
        ];
        // 开启规则时候增加生效时间的校验
        $status = $this->checkTime($rule_id,$store_id);
        //时间重叠或均未配置生效时间
        if ($status['status'] == 1) {
            //对比销售单价条件区间
            if(!empty($status['id'])){
                $temp_id = implode(',',$status['id']);
                $where['c1.id'] = array('in',  $temp_id);
            }
            $data = $this->field($field)
                ->table('tb_fin_commission_configuration c1')
                ->join(['inner join tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id'])
                ->where($where)
                ->select();
            if (empty($data)) return true;
            //验证币种
            $rule_detail_tem = json_decode($data[0]['rule_detail'], true); //取 第一个比较币种
            $currency = array_unique(array_column($rule['rule_detail'], 'currency'));
            $currency_tem = array_unique(array_column($rule_detail_tem, 'currency'));
            if ($currency !== $currency_tem) {
                $this->setError(L('与'.$data[0]['rule_no'].'中币种不一致，请调整币种'), '3000');
                return false;
            }
            $res_data = $this->checkSalePrice($rule['rule_detail'], 1);
            foreach ($data as $k => $v) {
                $rule_detail = json_decode($v['rule_detail'], true);
                //拼装二维数组 => ['大于':'','小于':'',..]
                $rule_detail = $this->checkSalePrice($rule_detail, 1);
                //对所有规则进行校验
                $res = $this->checkSalePriceEnableRule($rule_detail, $res_data);
                if (!$res) {
                    $this->setError(L('与已开启条件'.$v['rule_no'].'中销售单价条件区间重叠，无法开启新规则'), '3000');
                    return false;
                }
            }
        }
        return true;
    }

    public function checkSalePriceEnableRule($rule_detail, $check_rule) {

        //只有一个规则时
        //对比的规则都只有小于|小于等于
        if ((!empty($check_rule['higher']) || !empty($check_rule['higher_equal'])) && (!empty($rule_detail['higher']) || !empty($rule_detail['higher_equal']))) {
            if (empty($check_rule['lower']) && empty($check_rule['lower_equal']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) {
                return false;
            }
        }
        //对比的规则都只有大于|大于等于
        if ((!empty($check_rule['lower']) || !empty($check_rule['lower_equal'])) && (!empty($rule_detail['lower']) || !empty($rule_detail['lower_equal']))) {
            if (empty($check_rule['higher']) && empty($check_rule['higher_equal']) && empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) {
                return false;
            }
        }
        //对比的两个规则计算式都有多个规则时 如 大于&小于
        $res1 = $this->checkEnableRule1($check_rule, $rule_detail);
        if (!$res1) {
            return false;
        }
        //对比的规则计算式不一致 但都只有一项条件
        $res1 = $this->checkEnableRule2($check_rule, $rule_detail);
        if (!$res1) {
            return false;
        }
        //被验证的规则只有一项条件，用于对比的规则有多个条件
        $res1 = $this->checkEnableRule3($check_rule, $rule_detail);
        if (!$res1) {
            return false;
        }
        //被验证的规则只有多项条件，用于对比的规则有一个条件
        $res1 = $this->checkEnableRule4($check_rule, $rule_detail);
        if (!$res1) {
            return false;
        }
        //等于
        if (!empty($check_rule['is']) || !empty($rule_detail['is'])) {
            //只有等于 为
            $res1 = $this->checkEnableRule5($check_rule, $rule_detail);
            if (!$res1) {
                return false;
            }
        }
        //等于
        if (!empty($check_rule['not']) || !empty($rule_detail['not'])) {
            //只有不等于 不为
            $res1 = $this->checkEnableRule6($check_rule, $rule_detail);
            if (!$res1) {
                return false;
            }
        }
        return true;
    }

    //对比的规则计算式不一致 但都只有一项条件时
    public function checkEnableRule1($check_rule, $rule_detail) {
        //多个规则时
        //大于&小于
        if (!empty($check_rule['lower']) && !empty($check_rule['higher'])) {
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower'] && $check_rule['higher'] <= $rule_detail['lower']) || ($check_rule['lower'] >= $rule_detail['higher'] && $check_rule['higher'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower'] && $check_rule['higher'] <= $rule_detail['lower']) || ($check_rule['lower'] >= $rule_detail['higher_equal'] && $check_rule['higher'] >= $rule_detail['higher_equal']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower_equal'] && $check_rule['higher'] <= $rule_detail['lower_equal']) || ($check_rule['lower'] >= $rule_detail['higher'] && $check_rule['higher'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower_equal'] && $check_rule['higher'] <= $rule_detail['lower_equal']) || ($check_rule['lower'] >= $rule_detail['higher_equal'] && $check_rule['higher'] >= $rule_detail['higher_equal']))) {
                    return false;
                }
            }
        }
        //大于&小于等于
        if (!empty($check_rule['lower']) && !empty($check_rule['higher_equal'])) {
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower'] && $check_rule['higher_equal'] <= $rule_detail['lower']) || ($check_rule['lower'] >= $rule_detail['higher'] && $check_rule['higher_equal'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower'] && $check_rule['higher_equal'] <= $rule_detail['lower']) || ($check_rule['lower'] >= $rule_detail['higher_equal'] && $check_rule['higher_equal'] > $rule_detail['higher_equal']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower_equal'] && $check_rule['higher_equal'] < $rule_detail['lower_equal']) || ($check_rule['lower'] >= $rule_detail['higher'] && $check_rule['higher_equal'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower'] <= $rule_detail['lower_equal'] && $check_rule['higher_equal'] < $rule_detail['lower_equal']) || ($check_rule['lower'] >= $rule_detail['higher_equal'] && $check_rule['higher_equal'] > $rule_detail['higher_equal']))) {
                    return false;
                }
            }
        }
        //小于&大于等于
        if (!empty($check_rule['lower_equal']) && !empty($check_rule['higher'])) {
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower_equal'] <= $rule_detail['lower'] && $check_rule['higher'] <= $rule_detail['lower']) || ($check_rule['lower_equal'] >= $rule_detail['higher'] && $check_rule['higher'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower_equal'] <= $rule_detail['lower'] && $check_rule['higher'] <= $rule_detail['lower']) || ($check_rule['lower_equal'] > $rule_detail['higher_equal'] && $check_rule['higher'] >= $rule_detail['higher_equal']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower_equal'] < $rule_detail['lower_equal'] && $check_rule['higher'] <= $rule_detail['lower_equal']) || ($check_rule['lower_equal'] >= $rule_detail['higher'] && $check_rule['higher'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower_equal'] < $rule_detail['lower_equal'] && $check_rule['higher'] <= $rule_detail['lower_equal']) || ($check_rule['lower_equal'] > $rule_detail['higher_equal'] && $check_rule['higher'] >= $rule_detail['higher_equal']))) {
                    return false;
                }
            }
        }
        //小于等于&大于等于
        if (!empty($check_rule['lower_equal']) && !empty($check_rule['higher_equal'])) {
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower_equal'] <= $rule_detail['lower'] && $check_rule['higher_equal'] <= $rule_detail['lower']) || ($check_rule['lower_equal'] >= $rule_detail['higher'] && $check_rule['higher_equal'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower_equal'] <= $rule_detail['lower'] && $check_rule['higher_equal'] <= $rule_detail['lower']) || ($check_rule['lower_equal'] > $rule_detail['higher_equal'] && $check_rule['higher_equal'] > $rule_detail['higher_equal']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher'])) {
                if (!(($check_rule['lower_equal'] < $rule_detail['lower_equal'] && $check_rule['higher_equal'] < $rule_detail['lower_equal']) || ($check_rule['lower_equal'] >= $rule_detail['higher'] && $check_rule['higher_equal'] >= $rule_detail['higher']))) {
                    return false;
                }
            }
            if (!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher_equal'])) {
                if (!(($check_rule['lower_equal'] < $rule_detail['lower_equal'] && $check_rule['higher_equal'] < $rule_detail['lower_equal']) || ($check_rule['lower_equal'] > $rule_detail['higher_equal'] && $check_rule['higher_equal'] > $rule_detail['higher_equal']))) {
                    return false;
                }
            }
        }
        return true;
    }

    //对比的规则计算式不一致 但都只有一项条件时
    public function checkEnableRule2($check_rule, $rule_detail) {
        if ((!empty($check_rule['lower'])) && (empty($check_rule['higher']) &&
                empty($check_rule['higher_equal']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal']))) {
            if ((!empty($rule_detail['higher'])) && $check_rule['lower'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['higher_equal'])) && $check_rule['lower'] < $rule_detail['higher_equal']) {
                return false;
            }
        }
        if ((!empty($check_rule['lower_equal'])) && (empty($check_rule['higher']) &&
                empty($check_rule['higher_equal']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal']))) {
            if ((!empty($rule_detail['higher'])) && $check_rule['lower_equal'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['higher_equal'])) && $check_rule['lower_equal'] <= $rule_detail['higher_equal']) {
                return false;
            }
        }
        if ((!empty($check_rule['higher'])) && (empty($check_rule['lower']) &&
                empty($check_rule['lower_equal']))) {
            if (empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) {
                if ((!empty($rule_detail['lower'])) && $check_rule['higher'] > $rule_detail['lower']) {
                    return false;
                }
                if ((!empty($rule_detail['lower_equal'])) && $check_rule['higher'] > $rule_detail['lower_equal']) {
                    return false;
                }
            }
        }
        if ((!empty($check_rule['higher_equal'])) && (empty($check_rule['lower']) &&
                empty($check_rule['lower_equal']) && empty($rule_detail['higher']) && empty($rule_detail['higher_equal']))) {
            if ((!empty($rule_detail['lower'])) && $check_rule['higher_equal'] > $rule_detail['lower']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal'])) && $check_rule['higher_equal'] >= $rule_detail['lower_equal']) {
                return false;
            }
        }
        return true;
    }

    //被验证的规则只有一项条件，用于对比的规则有多个条件
    public function checkEnableRule3($check_rule, $rule_detail) {
        //只有大于
        if (!empty($check_rule['lower']) && (empty($check_rule['higher']) && empty($check_rule['higher_equal']))) {
            if ((!empty($rule_detail['higher']) && (!empty($rule_detail['lower']) || !empty($rule_detail['lower_equal']))) && $check_rule['lower'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['higher_equal']) && (!empty($rule_detail['lower']) || !empty($rule_detail['lower_equal']))) && $check_rule['lower'] < $rule_detail['higher_equal']) {
                return false;
            }
        }
        //只有大于等于
        if (!empty($check_rule['lower_equal']) && (empty($check_rule['higher']) && empty($check_rule['higher_equal']))) {
            if ((!empty($rule_detail['higher']) && (!empty($rule_detail['lower']) || !empty($rule_detail['lower_equal']))) && $check_rule['lower_equal'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['higher_equal']) && (!empty($rule_detail['lower']) || !empty($rule_detail['lower_equal']))) && $check_rule['lower_equal'] <= $rule_detail['higher_equal']) {
                return false;
            }
        }
        //只有小于
        if (!empty($check_rule['higher']) && (empty($check_rule['lower']) && empty($check_rule['lower_equal']))) {
            if ((!empty($rule_detail['lower']) && (!empty($rule_detail['higher']) || !empty($rule_detail['higher_equal']))) && $check_rule['higher'] > $rule_detail['lower']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal']) && (!empty($rule_detail['higher']) || !empty($rule_detail['higher_equal']))) && $check_rule['higher'] > $rule_detail['lower_equal']) {
                return false;
            }
        }
        //只有小于等于
        if (!empty($check_rule['higher_equal']) && (empty($check_rule['lower']) && empty($check_rule['lower_equal']))) {
            if ((!empty($rule_detail['lower']) && (!empty($rule_detail['higher']) || !empty($rule_detail['higher_equal']))) && $check_rule['higher_equal'] > $rule_detail['lower']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal']) && (!empty($rule_detail['higher']) || !empty($rule_detail['higher_equal']))) && $check_rule['higher_equal'] >= $rule_detail['lower_equal']) {
                return false;
            }
        }
        return true;
    }

    //被验证的规则有多项条件，用于对比的规则有一个条件
    public function checkEnableRule4($check_rule, $rule_detail) {
        //被验证的规则 拥有大于
        if (!empty($check_rule['lower']) && (!empty($check_rule['higher']) || !empty($check_rule['higher_equal']))) {
            if (empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) {
                if (!empty($rule_detail['higher']) && $check_rule['lower'] < $rule_detail['higher']) {
                    return false;
                }
                if (!empty($rule_detail['higher_equal']) && $check_rule['higher'] <= $rule_detail['higher_equal']) {
                    return false;
                }
            }
        }
        //被验证的规则 拥有大于等于
        if (!empty($check_rule['lower_equal']) && (!empty($check_rule['higher']) || !empty($check_rule['higher_equal']))) {
            if (empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) {
                if (!empty($rule_detail['higher']) && $check_rule['lower_equal'] < $rule_detail['higher']) {
                    return false;
                }
                if (!empty($rule_detail['higher_equal']) && $check_rule['lower_equal'] <= $rule_detail['higher_equal']) {
                    return false;
                }
            }
        }
        //被验证的规则 拥有小于
        if (!empty($check_rule['higher']) && (!empty($check_rule['lower']) || !empty($check_rule['lower_equal']))) {
            if (empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) {
                if (!empty($rule_detail['lower']) && $check_rule['higher'] > $rule_detail['higher']) {
                    return false;
                }
                if (!empty($rule_detail['lower_equal']) && $check_rule['higher'] > $rule_detail['lower_equal']) {
                    return false;
                }
            }
        }
        //被验证的规则 拥有小于等于
        if (!empty($check_rule['higher_equal']) && (!empty($check_rule['lower']) || !empty($check_rule['lower_equal']))) {
            if (empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) {
                if (!empty($rule_detail['lower']) && $check_rule['higher_equal'] > $rule_detail['lower']) {
                    return false;
                }
                if (!empty($rule_detail['lower_equal']) && $check_rule['higher_equal'] >= $rule_detail['lower_equal']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function checkEnableRule5($check_rule, $rule_detail) {
        //只有等于
        if (!empty($rule_detail['is']) && ($check_rule['is'] == $rule_detail['is'])) {
            return false;
        }
        //被验证规则存在等于
        if (!empty($check_rule['is'])) {
            //只有等于和
            if ((!empty($rule_detail['lower']) && empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) && $check_rule['is'] > $rule_detail['lower']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal']) && empty($rule_detail['higher']) && empty($rule_detail['higher_equal'])) && $check_rule['is'] >= $rule_detail['lower_equal']) {
                return false;
            }
            if ((!empty($rule_detail['higher']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) && $check_rule['is'] < $rule_detail['higher']) {
                return false;
            }
            //用于对比的有小于等于
            if ((!empty($rule_detail['higher_equal']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) && $check_rule['is'] <= $rule_detail['higher_equal']) {
                return false;
            }


            if ((!empty($rule_detail['lower']) && !empty($rule_detail['higher'])) && $check_rule['is'] > $rule_detail['lower'] && $check_rule['is'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['lower']) && !empty($rule_detail['higher_equal'])) && $check_rule['is'] > $rule_detail['lower'] && $check_rule['is'] <= $rule_detail['higher_equal']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher'])) && $check_rule['is'] >= $rule_detail['lower_equal'] && $check_rule['is'] < $rule_detail['higher']) {
                return false;
            }
            if ((!empty($rule_detail['lower_equal']) && !empty($rule_detail['higher_equal'])) && $check_rule['is'] >= $rule_detail['lower_equal'] && $check_rule['is'] <= $rule_detail['higher_equal']) {
                return false;
            }
        }
        //用来对比的规则只有等于
        if (!empty($rule_detail['is']) && empty($rule_detail['higher']) && empty($rule_detail['higher_equal']) && empty($rule_detail['lower']) && empty($rule_detail['lower_equal'])) {
            if (!empty($check_rule['higher_equal'] && (empty($check_rule['lower']) && empty($check_rule['lower_equal']))) && ($check_rule['higher_equal'] >= $rule_detail['is'])) {
                return false;
            }
            if (!empty($check_rule['higher']) && (empty($check_rule['lower']) && empty($check_rule['lower_equal'])) && ($check_rule['higher'] > $rule_detail['is'])) {
                return false;
            }
            if (!empty($check_rule['lower']) && (empty($check_rule['higher']) && empty($check_rule['higher_equal'])) && ($check_rule['lower'] < $rule_detail['is'])) {
                return false;
            }
            if (!empty($check_rule['lower_equal']) && (empty($check_rule['higher']) && empty($check_rule['higher_equal'])) && ($check_rule['lower_equal'] <= $rule_detail['is'])) {
                return false;
            }
        }
        return true;
    }

    //被验证的规则或用来对比的规则有不等于
    public function checkEnableRule6($check_rule, $rule_detail) {
        if (!empty($rule_detail['not']) && empty($rule_detail['lower']) && empty($rule_detail['higher']) && empty($rule_detail['lower_equal']) && empty($rule_detail['higher_equal'])) {
            //用来对比的规则有不等于
            if ((!empty($check_rule['not']) || !empty($check_rule['lower']) || !empty($check_rule['higher']) || !empty($check_rule['lower_equal']) || !empty($check_rule['higher_equal']))) {
                return false;
            }
            //用来对比的规则有不等于
            if (!empty($check_rule['is']) && ($rule_detail['not'] != $check_rule['is'])) {
                return false;
            }
        }
        if (!empty($check_rule['not']) && empty($check_rule['lower']) && empty($check_rule['higher']) && empty($check_rule['lower_equal']) && empty($check_rule['higher_equal'])) {
            //被验证的规则有不等于
            if ((!empty($rule_detail['not']) || !empty($rule_detail['lower']) || !empty($rule_detail['higher']) || !empty($rule_detail['lower_equal']) || !empty($rule_detail['higher_equal']))) {
                return false;
            }
            //被验证的规则有不等于
            if (!empty($rule_detail['is']) && ($check_rule['not'] != $rule_detail['is'])) {
                return false;
            }
        }
        return true;
    }

    public function deleteRule($id, $store_id) {
        if (empty($id)) {
            $this->setError(L('规则id不能为空'), '3000');
            return false;
        }
        if (empty($store_id)) {
            $this->setError(L('店铺id未找到'), '3000');
            return false;
        }
        $model = M('fin_commission_configuration', 'tb_');
        $admin_name = $_SESSION['m_loginname'];
        $date = $this->getTime();
        $where = [
            'id' => $id,
            'store_id' => $store_id,
        ];
        $rule = $model->where($where)->find();
        if ($rule['is_enable'] == self::RULE_OPEN) {
            $this->setError(L('规则启用中，无法删除'), '3000');
            return false;
        }
        $model->startTrans();
        // 变更更新人，记录日志需要

        $model->where($where)->save(['updated_by'=>$admin_name,'updated_at'=>$date]);
        if(!$model->where($where)->delete()) {
            $model->rollback();
            $this->setError(L('删除失败'), '3000');
            return false;
        } else {
            $res = M('fin_commission_rule', 'tb_')
                ->where(['commission_configuration_id' => $id])
                ->delete();
            if (!$res) {
                $model->rollback();
                $this->setError(L('删除子项失败'), '3000');
                return false;
            }

        }
        $model->commit();
        return true;
    }

    public function validateRuleData($data) {
        $rules = [
            'rate' => 'numeric',
            'lowest_commission' => 'numeric',
            'highest_commission' => 'numeric',
            'store_id' => 'required|numeric',
        ];
        $attributes = [
            'rate' => '佣金费率',
            'lowest_commission' => '最低佣金',
            'highest_commission' => '最高佣金',
            'store_id' => '店铺id',
        ];
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
//            $this->setError(ValidatorModel::getMessage(), 3002);
            $this->setError(L('金额请输入数字'), 3002);
            return false;
        }
        if((!isset($data['rate']) || $data['rate'] === "") && (!isset($data['lowest_commission']) || $data['lowest_commission'] === "")) {
            $this->setError(L('佣金费率和最低佣金至少有一个需要配置'), 3003);
            return false;
        }
        if ($data['rate'] !== "" && ($data['rate'] < 0 || $data['rate'] > 1)) {
            $this->setError(L('佣金费率请填写0-1之间的小数'), 3003);
            return false;
        }
        if ($data['lowest_commission'] !== "" && $data['lowest_commission'] < 0 ) {
            $this->setError(L('最低佣金不能小于0'), 3003);
            return false;
        }
        if (!empty($data['lowest_commission']) && empty($data['lowest_currency_cd'])) {
            $this->setError(L('请选择最低佣金币种'), 3003);
            return false;
        }
        if ($data['highest_commission'] !== "" && $data['highest_commission'] < 0 ) {
            $this->setError(L('最高佣金不能小于0'), 3003);
            return false;
        }
        if (!empty($data['highest_commission']) && empty($data['highest_currency_cd'])) {
            $this->setError(L('请选择最高佣金币种'), 3003);
            return false;
        }
        if (!empty($data['lowest_currency_cd']) && $data['lowest_commission'] === "") {
            $this->setError(L('选择了佣金币种，请填写最低佣金'), 3003);
            return false;
        }
        if (!empty($data['highest_currency_cd']) && $data['highest_commission'] === "") {
            $this->setError(L('选择了佣金币种，请填写最高佣金'), 3003);
            return false;
        }
        //非销售单价时验证
        if ($data['dimension_type'] == '1' && $data['dimension_cd'] != 'N002600501') {
            if (!empty($data['dimension_cd']) && (!$data['judge_cd'] || !$data['value'])) {
                $this->setError(L('计算和值不能为空'), 3003);
                return false;
            }
        }
        if (!empty($data['judge_cd']) && (!$data['dimension_cd'] || !$data['value'])) {
            $this->setError(L('维度和值不能为空'), 3003);
            return false;
        }
        if (!empty($data['value']) && (!$data['dimension_cd'] || !$data['judge_cd'])) {
            $this->setError(L('维度和计算不能为空'), 3003);
            return false;
        }
        if (!empty($data['highest_commission']) && !empty($data['lowest_commission'])) {
            if ($data['highest_commission'] < $data['lowest_commission']) {
                $this->setError(L('最高佣金金额不能小于最低佣金金额'), 3003);
                return false;
            }
        }
        if (!empty($data['dimension_cd']) && $data['dimension_cd'] == self::DIMENSION_SKU){
            if (!$this->checkSku($data['value'])) {
                $this->setError(L('sku编号不存在'), 3003);
                return false;
            }
        }
        if ($data['dimension_type'] == '2' && $data['dimension_cd'] == 'N002600501') {
            if (!$this->checkSalePrice($data['rule_detail'])) {
                return false;
            }
        }
        return true;
    }

    private function checkSku($sku_id) {
        return (new PmsBaseModel())->table('product_sku')->where(['sku_id' => $sku_id])->count();
    }

    private function checkSalePrice($data, $type = 0) {
        //只有一个条件时不验证
        if (count($data) == 1  && $type != 1) {
            return true;
        }
        //取出币种列表
        $currency = array_unique(array_column($data, 'currency'));
        if (count($currency) > 1) {
            $this->setError(L('币种必须一致'), 3003);
            return false;
        }
        //大于
        $greater_than = [];
        //大于大或等于
        $greater_than_equal = [];
        //小于
        $less_than = [];
        //小或等于
        $less_than_equal = [];
        //等于
        $is = [];
        //不等于
        $not = [];
        foreach ($data as $key => $value) {
            //大于
            if ($value['judge_cd'] == 'N002610201') {
                $greater_than[] = $value['value'];
            }
            //大或等于
            if ($value['judge_cd'] == 'N002610203') {
                $greater_than_equal[] = $value['value'];
            }
            //小于
            if ($value['judge_cd'] == 'N002610202') {
                $less_than[] = $value['value'];
            }
            //小或等于
            if ($value['judge_cd'] == 'N002610204') {
                $less_than_equal[] = $value['value'];
            }
            //等于
            if ($value['judge_cd'] == 'N002610100') {
                $is[] = $value['value'];
            }
            //不等于
            if ($value['judge_cd'] == 'N002610200') {
                $not[] = $value['value'];
            }
        }
        //等于只能是一个值
        if (count($is) > 1) {
            $this->setError(L('条件存在冲突，请检查配置'), 3003);
            return false;
        }
        if (count(array_unique($not)) > 1) {
            $this->setError(L('条件存在冲突，请检查配置'), 3003);
            return false;
        }
        //下限取最大值
        $lower = max($greater_than);
        //上限取最小值
        $higher = min($less_than);
        //下限取最大值
        $lower_equal = max($greater_than_equal);
        //上限取最小值
        $higher_equal = min($less_than_equal);
        if ($type == 1) {
            $data = [
                'lower'        => $lower,
                'lower_equal'  => $lower_equal,
                'higher'       => $higher,
                'higher_equal' => $higher_equal,
                'is'           => $is[0],
                'not'          => $not[0],
            ];
            //针对各个计算式组合对计算式数据进行过滤
            $data = $this->checkJudgeData($data);
            return $data;
        }
        if ($lower && $higher) {
            if ($lower >= $higher) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
            if (!empty($is) && !(min($is) > $lower && max($is) < $higher)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($lower && !$higher && !$higher_equal) {
            if (!empty($is) && !(min($is) > $lower)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($lower_equal && !$higher && !$higher_equal) {
            if (!empty($is) && !(min($is) >= $lower_equal)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($higher && !$lower && !$lower_equal) {
            if (!empty($is) && !(max($is) < $higher)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($higher_equal && !$lower && !$lower_equal) {
            if (!empty($is) && !(max($is) <= $higher_equal)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($lower && $higher_equal) {
            if ($lower > $higher_equal) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
            if (!empty($is) && !(min($is) > $lower && max($is) <= $higher_equal)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($lower_equal && $higher) {
            if ($lower_equal > $higher) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
            if (!empty($is) && !(min($is) >= $lower_equal && max($is) < $higher)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if ($lower_equal && $higher_equal) {
            if ($lower_equal > $higher_equal) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
            if (!empty($is) && !(min($is) >= $lower_equal && max($is) <= $higher_equal)) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        //大于、小或等于不能存在相同值
        if (!empty($greater_than) && !empty($less_than_equal)) {
            if (!empty(array_intersect($greater_than, $less_than_equal))) {
                $this->setError(L('条件存在冲突，请检查配置：等于、不等于不能存在相同值'), 3003);
                return false;
            }
            if ($lower >= $higher_equal) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        //大或等于、小于不能存在相同值
        if (!empty($greater_than_equal) && !empty($less_than)) {
            if (!empty(array_intersect($greater_than_equal, $less_than))) {
                $this->setError(L('条件存在冲突，请检查配置：大或等于、小于不能存在相同值'), 3003);
                return false;
            }
            //下限取最大值 上限取最小值
            if ($lower_equal >= $higher) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        //小于、大于不能存在相同值
        if (!empty($less_than) && !empty($greater_than)) {
            if (!empty(array_intersect($less_than, $greater_than))) {
                $this->setError(L('条件存在冲突，请检查配置：小于、大于不能存在相同值'), 3003);
                return false;
            }
            //下限取最大值 上限取最小值
            if ($lower >= $higher) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        //小于、大于不能存在相同值
        if (!empty($less_than_equal) && !empty($greater_than_equal)) {
            //下限取最大值 上限取最小值
            if ($lower_equal > $higher_equal) {
                $this->setError(L('条件存在冲突，请检查配置'), 3003);
                return false;
            }
        }
        if (!empty($is) && !empty($not)) {
            //等于、不等于不能存在相同值
            if (!empty(array_intersect($is, $not))) {
                $this->setError(L('条件存在冲突，请检查配置：等于、不等于不能存在相同值'), 3003);
                return false;
            }
        }
        return true;
    }

    //针对各个计算式组合对计算式数据进行过滤
    public function checkJudgeData($data)
    {
        if ($data['lower'] && $data['lower_equal']) {
            if ($data['lower'] >= $data['lower_equal']) {
                $data['lower_equal'] = null;
            } else {
                $data['lower'] = null;
            }
        }
        if ($data['higher'] && $data['higher_equal']) {
            if ($data['higher'] <= $data['higher_equal']) {
                $data['higher_equal'] = null;
            } else {
                $data['higher'] = null;
            }
        }
        if ($data['not'] && ($data['lower'] || $data['lower_equal'] || $data['higher'] || $data['higher_equal'])) {
            $data['not'] = null;
        }
        return $data;
    }

    public static $where;
    /**
     * 构建查询条件
     * @param mixed $str
     * @return array
     */
    public function subWhere($key, $str)
    {
        if (is_array($str)) {
            list($pattern, $val) = $str;
            if ($val) {
                switch ($pattern) {
                    case 'like':
                        static::$where [$key] = ['like', '%' . $val . '%'];
                        break;
                    case 'range':
                        list($f, $l) = $val;
                        if ($f and $l)
                            static::$where [$key] = [['gt', $f . ' 00:00:00'], ['lt', $l . ' 23:59:59'], 'and'];
                        if ($f and !$l)
                            static::$where [$key] = ['gt', $f . ' 00:00:00'];
                        if ($l and !$f)
                            static::$where [$key] = ['lt', $l . ' 23:59:59'];
                        break;
                    case 'xrange':
                        list($f, $l) = $val;
                        if ($f and $l)
                            static::$where [$key] = [['egt', $f . ' 00:00:00'], ['elt', $l . ' 23:59:59'], 'and'];
                        if ($f and !$l)
                            static::$where [$key] = ['egt', $f . ' 00:00:00'];
                        if ($l and !$f)
                            static::$where [$key] = ['elt', $l . ' 23:59:59'];
                        break;
                    default:
                        static::$where [$key] = $val;
                        break;
                }
            } else if ($val === "0") {
                static::$where [$key] = $val;
            }
        } else {
            if ($str) {
                if (isset(static::$where [$key]))
                    static::$where [$key] .= ' and ' . $str;
                else
                    static::$where [$key] = $str;
            }
        }

        return $this;
    }

    public function setError($msg, $code) {
        $this->error_info = [
            'msg' => $msg,
            'code' => $code,
            'data' => [],
        ];
    }

    public function checkTime($rule_id,$store_id)
    {
        $field = [
            'c1.*',
            'r1.commission_configuration_id',
            'r1.dimension_cd',
            'r1.judge_cd',
            'r1.value',
        ];
        $m =M('tb_fin_commission_configuration');
        $timeData = $this->field($field)
            ->table('tb_fin_commission_configuration c1')
            ->join(['left join tb_fin_commission_rule r1 on c1.id = r1.commission_configuration_id'])
            ->where(['c1.id' => $rule_id])
            ->find();

        $start_time = $timeData['start_time'];
        $end_time = $timeData['end_time'];
        // 时间校验
        // 没有开始没有结束
        $id =[]; $status="";
        if(empty($start_time) && empty($end_time)){
            $temp_data = $m->query("select t1.id from tb_fin_commission_configuration t1 join tb_fin_commission_rule t2 on t1.id = t2.commission_configuration_id
             where  t1.is_enable =1 and store_id = $store_id and t1.id != $rule_id and t1.start_time is null and t1.end_time is null
            ");
            if(!empty($temp_data)){
                $id = array_column($temp_data,'id');
                $status = 1;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }else{ 
                $status = 2;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }



            // 有开始，没有结束
        }elseif ((!empty($start_time)) && empty(($end_time))){
            $temp_data = $m->query("select t1.id from tb_fin_commission_configuration t1 join tb_fin_commission_rule t2 on t1.id = t2.commission_configuration_id
             where ((t1.end_time is null and t1.start_time is not null) or (t1.end_time>='$start_time')) and t1.is_enable =1 and store_id = $store_id and t1.id != $rule_id
            ");

            if(!empty($temp_data)){
                $id = array_column($temp_data,'id');
                $status = 1;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }else{
                $status = 2;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }

            // 有结束，没有开始
        }elseif ((!empty($end_time)) && (empty($start_time))){
            $temp_data = $m->query("select t1.id from tb_fin_commission_configuration t1 join tb_fin_commission_rule t2 on t1.id = t2.commission_configuration_id
             where ((t1.start_time is null and t1.end_time is not null) or (t1.start_time<='$end_time')) and t1.is_enable =1 and store_id = $store_id and t1.id != $rule_id
            ");

            if(!empty($temp_data)){
                $id = array_column($temp_data,'id');
                $status = 1;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }else{
                $status = 2;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }



            // 有开始，有结束
        }else{
            $temp_data = $m->query("select t1.id from tb_fin_commission_configuration t1 join tb_fin_commission_rule t2 on t1.id = t2.commission_configuration_id
             where ((t1.start_time >='$start_time' AND t1.start_time <= '$end_time') OR (t1.start_time <='$start_time'  AND t1.end_time >='$end_time') OR(t1.end_time >='$start_time' AND t1.end_time <= '$end_time')
             or (t1.start_time is null and t1.end_time>='$start_time') or (t1.end_time is null and t1.start_time<='$end_time')) and t1.is_enable =1 and store_id = $store_id  and t1.id != $rule_id
            ");
            //echo M('tb_fin_commission_configuration')->getLastSql();die;
            //var_dump($temp_data);die;
            if(!empty($temp_data)){
                $id = array_column($temp_data,'id');
                $status = 1;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }else{
                $status = 2;
                $state = ['id'=>$id,'status'=>$status];
                return $state;
            }

        }
        $status = 2;
        $state = [$id,$status];
        return $state;


    }

    //格式化日志详细信息
    public function formatLogContent($content) {
        //维度
        $dimension = CommonDataModel::dimension();
        $dimension_cd = array_column($dimension, 'cd');
        $dimension_cdVal = array_column($dimension, 'cdVal');
        //币种
        $currency = CommonDataModel::currency();
        $currency_cd = array_column($currency, 'cd');
        $currency_cdVal = array_column($currency, 'cdVal');
        //计算
        $judge2 = CommonDataModel::judge2();
        $judge2_cd = array_column($judge2, 'cd');
        $judge2_cdVal = array_column($judge2, 'cdVal');
        $arr1 = ['dimension_cd', 'judge_cd', 'currency', 'value'];
        $arr2 = ['维度', '计算', '币种', '值'];
        $arr_cd = array_merge($arr1,$dimension_cd, $currency_cd, $judge2_cd);
        $arr2_cdVal = array_merge($arr2, $dimension_cdVal, $currency_cdVal, $judge2_cdVal);
        $content = str_replace($arr_cd, $arr2_cdVal, $content);
        return $content;
    }
}