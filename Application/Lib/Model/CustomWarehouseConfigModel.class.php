<?php

/**
 * ERP自定义走仓规则配置表模型
 * User: shenmo
 * Date: 2020/08/27
 * Time: 16:08
 */
class CustomWarehouseConfigModel extends BaseModel
{

    protected $trueTableName = 'tb_ms_custom_warehouse_config';

    /**
     * 根据ID读取ERP自定义走仓规则配置
     * @param int $id id
     * @return array 数据或者空
     */
    public function getDetailById($id)
    {
        return $this->where("ID={$id}")->find();
    }

    /**
     * 读取ERP自定义走仓规则配置数据
     * @param array $where 条件
     * @param array $page 分页
     * @return array 数据或者空
     */
    public function getData($where, $page)
    {
        $page_act = $page['this_page'] - 1;
        if ($page_act < 0) {
            $page_act = 0;
        }
        return $this->where($where)
            ->field('tb_ms_custom_warehouse_config.*,tb_ms_logistics_mode.LOGISTICS_MODE logistics_mode,tb_ms_logistics_mode.SERVICE_CODE service_code')
            ->join('tb_ms_logistics_mode ON tb_ms_logistics_mode.ID=tb_ms_custom_warehouse_config.logistics_mode_id')
            ->limit($page_act * $page['page_count'], $page['page_count'])
            ->order('rule_type asc, status desc, sort')
            ->select();
    }

    /**
     * 读取ERP自定义走仓规则配置数据
     * @param array $where 条件
     * @return array 数据或者空
     */
    public function getCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 读取ERP自定义走仓规则配置数据
     * @param array $data
     * @return array 数据或者空
     */
    public function getDetail($data)
    {
        if (empty($data['id'])) return false;
        $where['tb_ms_custom_warehouse_config.id'] = $data['id'];
        return $this
            ->field('tb_ms_custom_warehouse_config.*,tb_ms_logistics_mode.LOGISTICS_MODE logistics_mode,tb_ms_logistics_mode.SERVICE_CODE service_code')
            ->join('tb_ms_logistics_mode ON tb_ms_logistics_mode.ID=tb_ms_custom_warehouse_config.logistics_mode_id')
            ->where($where)->find();
    }

    /**
     * 查询条件
     *
     * @param array $request_data 搜索条件[logisticsCode,logisticsMode, start]
     *
     * @return array 数据数组或者false
     */
    public function searchWhere($request_data = [])
    {
        $where = [];
        !empty($request_data['id']) && $where['id'] = $request_data['id'];
        !empty($request_data['store_id']) && $where['store_id'] = $request_data['store_id'];
        !empty($request_data['sku_id']) && $where['sku'] = ['like', '%' . $request_data['sku_id'] . '%'];
        !empty($request_data['country']) && $where['_string'] .= " FIND_IN_SET('".$request_data['country']."',country_id) ";
        !empty($request_data['goods_name']) && $where['sku'] = ['like', '%' . $request_data['goods_name'] . '%'];
        !empty($request_data['rule_name']) && $where['rule_name'] = ['like', '%' . $request_data['rule_name'] . '%'];
        !empty($request_data['warehouse_code']) && $where['warehouse_code'] = $request_data['warehouse_code'];
        !empty($request_data['logistics_company_code']) && $where['logistics_company_code'] = $request_data['logistics_company_code'];
        !empty($request_data['logistics_mode_id']) && $where['logistics_mode_id'] = $request_data['logistics_mode_id'];
        !empty($request_data['face_order_code']) && $where['face_order_code'] = $request_data['face_order_code'];
        //没有选状态默认非删除
        if ((isset($request_data['status']) && $request_data['status'] !== '')) {
            $where['status'] = $request_data['status'];
        } else {
            $where['status'] = ['neq', 2];
        }
        //未选择筛选条件 除店铺之外我筛选项
        if ((count($where) == 2 && $request_data['status'] !== 0 && $request_data['status'] != 2)) {
            //追加默认规则
            $map['_complex'] = $where;
            $map['rule_type'] = 2;
            $map['_logic'] = 'or';
        } else {
            $map = $where;
        }
        return $map;
    }

    /**
     * 创建新数据。
     * @param array $data 数据
     * @param string $p_sort 父排序
     * @return bool|mixed
     */
    public function create($data, $p_sort = '')
    {
        $timeNow = date('Y-m-d H:i:s', time());
        $newData['p_id'] = isset($data['p_id']) ? $data['p_id'] : 0;
        $newData['store_id'] = $data['store_id'];
        $newData['sort'] = isset($data['sort']) ? $data['sort'] : $this->getSort($p_sort);
        $newData['p_sort'] = isset($data['p_sort']) ? $data['p_sort'] : $newData['sort'];
        $newData['rule_name'] = $data['rule_name'];
        $newData['status'] = 1; //默认启动
        $newData['rule_type'] = isset($data['rule_type']) ? $data['rule_type'] : 1;
        $newData['warehouse_code'] = !empty($data['warehouse_code']) ? $data['warehouse_code'] : '';
        $newData['logistics_company_code'] = !empty($data['logistics_company_code']) ? $data['logistics_company_code'] : '';
        $newData['logistics_mode_id'] = !empty($data['logistics_mode_id']) ? $data['logistics_mode_id'] : '';
        $newData['face_order_code'] = !empty($data['face_order_code']) ? $data['face_order_code'] : '';
        if ($p_sort) {
            //复制
            $newData['sku'] = !empty($data['sku']) ? $data['sku'] : '';
            $newData['sku_id'] = !empty($data['sku_id']) ? $data['sku_id'] : '';
            $newData['country'] = !empty($data['country']) ? $data['country'] : '';
            $newData['country_id'] = !empty($data['country_id']) ? $data['country_id'] : '';
        } else {
            //新增
            $newData['sku'] = !empty($data['sku']) ? json_encode($data['sku'], JSON_UNESCAPED_UNICODE) : '';
            $newData['sku_id'] = !empty($data['sku']) ? implode(',', array_column($data['sku'], 'sku_id')) : '';
            $newData['country'] = !empty($data['country']) ? json_encode($data['country'], JSON_UNESCAPED_UNICODE) : '';
            $newData['country_id'] = !empty($data['country']) ? implode(',', array_column($data['country'], 'id')) : '';
        }
        $newData['prefix'] = !empty($data['prefix']) ? $data['prefix'] : '';
        $newData['suffix'] = !empty($data['suffix']) ? $data['suffix'] : '';
        $newData['shipping_method'] = !empty($data['shipping_method']) ? $data['shipping_method'] : '';
        $newData['remark'] = !empty($data['remark']) ? $data['remark'] : '';
        $newData['created_by'] = $_SESSION['m_loginname'];
        $newData['created_at'] = $timeNow;
        $newData['updated_at'] = $timeNow;
        $newData['assign_warehouse'] = !empty($data['assign_warehouse']) ? json_encode($data['assign_warehouse'], JSON_UNESCAPED_UNICODE) : '';
        $newData['assign_shipping_method'] = !empty($data['assign_shipping_method']) ? json_encode($data['assign_shipping_method'], JSON_UNESCAPED_UNICODE) : '';
        $newData['assign_country'] = !empty($data['assign_country']) ? json_encode($data['assign_country'], JSON_UNESCAPED_UNICODE) : '';
        M()->startTrans();
        // 增加操作日志
        $res = $this->addStoreConfigLog($data['store_id'], $data['rule_name']);
        if (!$res) {
            //$outputs['msg'] = '添加日志失败';
            M()->rollback();
            return false;
        }
        $res = $this->add($newData);
        if (!$res) {
            //$outputs['msg'] = '添加日志失败';
            M()->rollback();
            return false;
        }
        M()->commit();
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 创建新数据。
     * @param string $sort 父排序
     * @return bool|mixed
     */
    public function getSort($sort = '')
    {
        //$sort = "C00001";
        if (!empty($sort)) {
            $where['sort'] = $sort;
            $p_config = $this->field('id, sort')->where($where)->find();
            if (empty($p_config)) return false;
            $config = $this->where(['p_id' => $p_config['id']])->order('sort desc')->getField('sort');
            $max_child_no = 0;
            if (!empty($config)) {
                $max_child_no = substr($config, str_len($p_config['sort']));
            }
            $new_child_sort = ($p_config['sort'] . str_pad(++ $max_child_no, 5, '0', STR_PAD_LEFT ));
            return $new_child_sort;
        }
        $max_sort = $this->order('sort desc')->getField('sort');
        $pre_sort = 'A';
        $max_no = 0;
        if (!empty($max_sort)) {
            $pre_sort = $max_sort[0];
            $max_no = substr($max_sort, 1, 5);
        }
        //每个档位99999个
        if ($max_no < 99999) {
            $no = ++ $max_no;
        } else {
            $pre_sort ++;
            $no = 1;
        }
        $new_sort = ($pre_sort . str_pad($no, 5, '0', STR_PAD_LEFT ));
        return $new_sort;
    }

    /**
     * 手动排序。
     * @param string $data
     * @return bool|mixed
     */
    public function manualSort($data, $model)
    {
        if (empty($data['id']) || empty($data['type'])) throw new Exception(L('请求数据为空'), 300);
        if ($data['type'] == 1) {
            $compare = 'lt';
            $sort_type = 'desc';
        } else {
            $compare = 'gt';
            $sort_type = 'asc';
        }
        $sort = $this->where(['id' => $data['id']])->find();
        $sort_tem = $this->where(['sort' => [$compare, $sort['sort']], 'store_id' => $sort['store_id'], 'status' => 1])->order('sort ' . $sort_type)->find();
        if (empty($sort_tem) || empty($sort)) return false;
        //交换排序号
        $res = $this->where(['id' => $data['id']])->save(['sort' => $sort_tem['sort']]);
        if (!$res) return false;
        $res = $this->where(['id' => $sort_tem['id']])->save(['sort' => $sort['sort']]);
        if (!$res) return false;
        return true;
    }

    /**
     * 更新数据
     * @param array $data      要更新的新数据
     * @return bool
     */
    public function update($data)
    {
        if (empty($data['id'])) throw new Exception(L('请求数据为空'), 300);
        $where['id'] = $data['id'];
        $saveData['rule_name'] = $data['rule_name'];
        $saveData['rule_type'] = isset($data['rule_type']) ? $data['rule_type'] : 1;
        $saveData['warehouse_code'] = $data['warehouse_code'];
        $saveData['logistics_company_code'] = $data['logistics_company_code'];
        $saveData['logistics_mode_id'] = $data['logistics_mode_id'];
        $saveData['face_order_code'] = $data['face_order_code'];
        $saveData['sku'] = !empty($data['sku']) ? json_encode($data['sku'], JSON_UNESCAPED_UNICODE) : '';
        $saveData['sku_id'] = !empty($data['sku']) ? implode(',', array_column($data['sku'], 'sku_id')) : '';
        $saveData['prefix'] = $data['prefix'];
        $saveData['suffix'] = $data['suffix'];
        $saveData['shipping_method'] = $data['shipping_method'];
        $saveData['country'] = !empty($data['country']) ? json_encode($data['country'], JSON_UNESCAPED_UNICODE) : '';
        $saveData['country_id'] = !empty($data['country']) ? implode(',', array_column($data['country'], 'id')) : '';
        $saveData['remark'] = $data['remark'];
        $saveData['updated_by'] = $_SESSION['m_loginname'];
        $saveData['updated_at'] = date('Y-m-d H:i:s', time());
        $saveData['assign_warehouse'] = !empty($data['assign_warehouse']) ? json_encode($data['assign_warehouse'], JSON_UNESCAPED_UNICODE) : '';
        $saveData['assign_shipping_method'] = !empty($data['assign_shipping_method']) ? json_encode($data['assign_shipping_method'], JSON_UNESCAPED_UNICODE) : '';
        $saveData['assign_country'] = !empty($data['assign_country']) ? json_encode($data['assign_country'], JSON_UNESCAPED_UNICODE) : '';
        M()->startTrans();
        // 增加操作日志
        $ret = $this->addStoreLog($data['id'], $saveData);
        if (!$ret) {
            //$outputs['msg'] = '添加日志失败';
            M()->rollback();
            return false;
        }
        //编辑自定义走仓规则
        $res = $this->where($where)->save($saveData);
        if (!$ret) {
            //$outputs['msg'] = '添加日志失败';
            M()->rollback();
            return false;
        }
        M()->commit();
        #echo $this->getLastSql();
        return $res;
    }

    public function addStoreLog($id, $saveData)
    {
        // 增加操作日志
        $store_log = new StoreLogService();
        $log_data = $store_log->getUpdateMessage("tb_ms_custom_warehouse_config", ['id' => $id], $saveData, 6, $id);
        if (empty($log_data)){
            return true;
        }
        // 添加日志
        return $store_log->addLog($log_data);
    }

    public function addStoreConfigLog($store_id, $rule_name)
    {
        // 增加走仓配置操作日志
        $data = array(
            'store_id' => $store_id,
            'module' => 6,
            'field_name' => '规则名称',
            'front_value' => !empty($front_value) ? $front_value : "",
            'later_value' => !empty($rule_name) ? $rule_name : "",
            'update_by' => userName(),
            'update_at' => date("Y-m-d H:i:s"),
        );
        // 添加日志
        return M("store_log",'tb_ms_')->add($data);;
    }

    /**
     * 更新数据
     * @param array $data 要更新的新数据
     * @return bool
     */
    public function updateStatus($data)
    {
        if (empty($data['id']) || !isset($data['status']) || $data['status'] == '') throw new Exception(L('请求数据为空'), 300);
        if ($data['status'] == 1) {
            //启用规则重新生成排序
            $saveData['sort'] = $this->getSort();
        }
        $where['id'] = $data['id'];
        $saveData['status'] = $data['status'];
        M()->startTrans();
        // 增加操作日志
        $ret = $this->addStoreLog($data['id'], $saveData);
        if (!$ret) {
            //$outputs['msg'] = '添加日志失败';
            M()->rollback();
            return false;
        }
        //编辑自定义走仓规则
        $res = $this->where($where)->save($saveData);
        M()->commit();
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 复制数据
     * @param array $data 要复制的新数据
     * @return bool
     */
    public function copy($data)
    {
        if (empty($data['id'])) throw new Exception(L('请求数据为空'), 300);
        $data = $this->where(['id' => $data['id']])->find();
        if (empty($data)) throw new Exception(L('该规则不存在'), 300);
        $sort = $data['sort'];
        unset($data['sort']);
        $data['rule_name'] = $data['rule_name'] . '（copy）';
        $data['p_id'] = $data['id'];
        $res = $this->create($data, $sort);
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 复制数据
     * @param array $params 要复制的新数据
     * @return bool
     */
    public function configCopyToOtherStore($params)
    {
        if (empty($params['id']) || empty($params['copy_id']) || empty($params['new_id'])) throw new Exception(L('请求数据为空'), 300);
        $data = $this->where(['id' => $params['id']])->find();
        if (empty($data)) throw new Exception(L('该规则不存在'), 300);
        $rule_name = $data['rule_name'] . '_' . $params['copy_id'];
        $saveData = [];
        $timeNow = date('Y-m-d H:i:s', time());
        $sort = $this->getSort();
        $pre_sort = $sort[0];
        $max_no = substr($sort, 1, 5);
        $new_ids = explode(',', $params['new_id']);
        if (empty($new_ids)) return false;
        foreach ($new_ids as $value) {
                $save['p_id'] = isset($data['p_id']) ? $data['p_id'] : 0;
                $save['store_id'] = $value;
                $save['p_sort'] = isset($data['p_sort']) ? $data['p_sort'] : '';
                $save['sort'] = $pre_sort . str_pad($max_no ++, 5, '0', STR_PAD_LEFT );
                $save['rule_name'] = $rule_name;
                $save['status'] = isset($data['status']) ? $data['status'] : 1;
                $save['rule_type'] = isset($data['rule_type']) ? $data['rule_type'] : 1;
                $save['warehouse_code'] = !empty($data['warehouse_code']) ? $data['warehouse_code'] : '';
                $save['logistics_company_code'] = !empty($data['logistics_company_code']) ? $data['logistics_company_code'] : '';
                $save['logistics_mode_id'] = !empty($data['logistics_mode_id']) ? $data['logistics_mode_id'] : '';
                $save['face_order_code'] = !empty($data['face_order_code']) ? $data['face_order_code'] : '';
                $save['sku'] = !empty($data['sku']) ? $data['sku'] : '';
                $save['sku_id'] = !empty($data['sku_id']) ? $data['sku_id'] : '';
                $save['prefix'] = !empty($data['prefix']) ? $data['prefix'] : '';
                $save['suffix'] = !empty($data['suffix']) ? $data['suffix'] : '';
                $save['shipping_method'] = !empty($data['shipping_method']) ? $data['shipping_method'] : '';
                $save['country'] = !empty($data['country']) ? $data['country'] : '';
                $save['country_id'] = !empty($data['country_id']) ? $data['country_id'] : '';
                $save['remark'] = !empty($data['remark']) ? $data['remark'] : '';
                $save['created_by'] = $_SESSION['m_loginname'];
                $save['created_at'] = $timeNow;
                $save['updated_at'] = $timeNow;
                $save['assign_warehouse'] = !empty($data['assign_warehouse']) ? $data['assign_warehouse'] : '';
                $save['assign_shipping_method'] = !empty($data['assign_shipping_method']) ? $data['assign_shipping_method'] : '';
                $save['assign_country'] = !empty($data['assign_country']) ? $data['assign_country'] : ''; //和Murphy Peng对接存国家二字码
                $saveData[] = $save;
        }
        $res = $this->addAll($saveData);
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 整店复制数据
     * @param array $data 要复制的新数据
     * @return bool|mixed
     */
    public function copyByStore($data)
    {
        //复制的店铺id 生成规则的目标店铺id
        if (empty($data['copy_id']) || empty($data['new_id'])) throw new Exception(L('请求数据为空'), 300);
        //需要复制的规则列表
        $list = $this->where(['store_id' => $data['copy_id']])->select();
        if (empty($data)) throw new Exception(L('该规则不存在'), 300);
        $saveData = [];
        $timeNow = date('Y-m-d H:i:s', time());
        $sort = $this->getSort();
        $pre_sort = $sort[0];
        $max_no = substr($sort, 1, 5);
        $new_ids = explode(',', $data['new_id']);
        foreach ($new_ids as $value) {
            foreach ($list as $key => $item) {
                $save['p_id'] = isset($item['p_id']) ? $item['p_id'] : 0;
                $save['store_id'] = $value;
                $save['p_sort'] = isset($item['p_sort']) ? $item['p_sort'] : '';
                $save['sort'] = $pre_sort . str_pad($max_no ++, 5, '0', STR_PAD_LEFT );
                $save['rule_name'] = $item['rule_name'] . '_' . $data['copy_id'];
                $save['rule_type'] = isset($item['rule_type']) ? $item['rule_type'] : 1;
                $save['warehouse_code'] = !empty($item['warehouse_code']) ? $item['warehouse_code'] : '';
                $save['logistics_company_code'] = !empty($item['logistics_company_code']) ? $item['logistics_company_code'] : '';
                $save['logistics_mode_id'] = !empty($item['logistics_mode_id']) ? $item['logistics_mode_id'] : '';
                $save['face_order_code'] = !empty($item['face_order_code']) ? $item['face_order_code'] : '';
                $save['sku'] = !empty($item['sku']) ? $item['sku'] : '';
                $save['sku_id'] = !empty($data['sku_id']) ? $data['sku_id'] : '';
                $save['prefix'] = !empty($item['prefix']) ? $item['prefix'] : '';
                $save['suffix'] = !empty($item['suffix']) ? $item['suffix'] : '';
                $save['shipping_method'] = !empty($item['shipping_method']) ? $item['shipping_method'] : '';
                $save['country'] = !empty($item['country']) ? $item['country'] : '';
                $save['country_id'] = !empty($item['country_id']) ? $item['country_id'] : '';
                $save['remark'] = !empty($item['remark']) ? $item['remark'] : '';
                $save['created_by'] = $_SESSION['m_loginname'];
                $save['created_at'] = $timeNow;
                $save['updated_at'] = $timeNow;
                $save['assign_warehouse'] = !empty($item['assign_warehouse']) ? $item['assign_warehouse'] : '';
                $save['assign_shipping_method'] = !empty($item['assign_shipping_method']) ? $item['assign_shipping_method'] : '';
                $save['assign_country'] = !empty($item['assign_country']) ? $item['assign_country'] : ''; //和Murphy Peng对接存国家二字码
                $saveData[] = $save;
            }
        }
        $res = $this->addAll($saveData);
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 根据店铺获取物流公司
     * @param array $data
     * @return bool
     */
    public function logisticsCompanyByStore($data)
    {
        if (empty($data['store_id'])) throw new Exception(L('请求数据为空'), 300);
        $Model = M();
        $list = $Model->table('tb_ms_logistics_mode')
            ->field('tb_ms_logistics_mode.LOGISTICS_CODE,group_concat(tb_ms_logistics_mode.WARE_HOUSE) WARE_HOUSE,tb_ms_cmn_cd.CD_VAL')
            ->join('tb_ms_logistics_mode_info ON tb_ms_logistics_mode.ID=tb_ms_logistics_mode_info.logistics_mode_id')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_logistics_mode.LOGISTICS_CODE')
            ->where(['tb_ms_logistics_mode_info.store_id' => $data['store_id']])
            ->group('tb_ms_logistics_mode.LOGISTICS_CODE')
            ->select();
        foreach ($list as $key => $item) {
            $list[$key]['WARE_HOUSE'] = implode(',', array_unique(explode(',', $item['WARE_HOUSE'])));
        }
        return $list;
        #echo $this->getLastSql();
    }

    public function logisticsModeByCompanyAndStore($data)
    {
        if (empty($data['store_id']) || empty($data['company_code'])/* || empty($data['warehouse_code'])*/) throw new Exception(L('请求数据为空'), 300);
        $Model = M();
        $list = $Model->table('tb_ms_logistics_mode')
            ->field('tb_ms_logistics_mode.ID,tb_ms_logistics_mode.LOGISTICS_CODE,tb_ms_logistics_mode.LOGISTICS_MODE,tb_ms_logistics_mode.POSTAGE_ID,tb_ms_logistics_mode.SURFACE_WAY_GET_CD')
            ->join('tb_ms_logistics_mode_info ON tb_ms_logistics_mode.ID=tb_ms_logistics_mode_info.logistics_mode_id')
            ->where(['tb_ms_logistics_mode_info.store_id' => $data['store_id'], 'tb_ms_logistics_mode.LOGISTICS_CODE' => $data['company_code']])
            ->select();
        //根据物流模板过滤
        /*$where_support_ware['P_ID2'] = $data['warehouse_code'];
        $wareLogistics = M("ms_params", "tb_")
            ->where($where_support_ware)
            ->where("TYPE =5", null, true)
            ->select();
        $postage_ids = array_column($wareLogistics, 'P_ID1');
        foreach ($list as $key => $item) {
            if (empty(array_intersect(explode(",", $item['POSTAGE_ID']), $postage_ids))) {
                unset($list[$key]);
            }
        }*/
        return $list;
        #echo $this->getLastSql();
    }

    /**
     * 删除
     *
     * @param array $id 删除条件[id]
     *
     * @return bool
     */
    public function delete($id)
    {

    }

    /**
     * 删除
     * @param $store_id
     * @return bool
     */
    public function getShippingType($store_id)
    {
        //根据店铺获取APPKEYS
        $Model = new Model();
        $store = $Model->table('tb_ms_store')->field('APPKES,PLAT_CD')->where(['ID' => $store_id])->find();
        if (empty($store)) return [];
        $res = ApiModel::getShippingType($store['APPKES']);
        $aeop_logistics_service_result = $res['aliexpress_logistics_redefining_listlogisticsservice_response']['result_list']['aeop_logistics_service_result'];
        if (empty($aeop_logistics_service_result)) return [];
        $display_names = array_column($aeop_logistics_service_result, 'display_name');
        $service_names = array_column($aeop_logistics_service_result, 'service_name');
        $display_names = array_unique(array_merge($display_names, $service_names));
        // 平台API标识 同时匹配 service_name display_name
        if (! empty($display_names)) {
            $where['third_logistics_cd'] = ['in', $service_names];
        }
        $Model = new Model();
        $where['operate_type'] = 1; //拉单物流匹配
        $where['plat_cd'] = $store['PLAT_CD']; //当前店铺所属平台
        $data = $Model->table('tb_ms_logistics_relation')
            ->field('tb_ms_logistics_relation.third_logistics_cd,tb_ms_logistics_relation.operate_type,tb_ms_logistics_mode.ID logistics_mode_id,tb_ms_logistics_mode.LOGISTICS_MODE')
            ->join('tb_ms_logistics_mode ON tb_ms_logistics_mode.ID=tb_ms_logistics_relation.logistics_mode_id')
            ->where($where)
            ->select();
        $logistics_relation = [];
        foreach ($data as $item) {
            $logistics_relation[$item['third_logistics_cd']] = $item;
        }
        foreach ($aeop_logistics_service_result as $key => $value) {
            if(isset($logistics_relation[$value['service_name']])) {
                $aeop_logistics_service_result[$key]['logistics_mode_id'] = $logistics_relation[$value['service_name']]['logistics_mode_id'];
                $aeop_logistics_service_result[$key]['logistics_mode'] = $logistics_relation[$value['service_name']]['LOGISTICS_MODE'];
            } else if(isset($logistics_relation[$value['display_name']])) {
                $aeop_logistics_service_result[$key]['logistics_mode_id'] = $logistics_relation[$value['display_name']]['logistics_mode_id'];
                $aeop_logistics_service_result[$key]['logistics_mode'] = $logistics_relation[$value['display_name']]['LOGISTICS_MODE'];
            } else {
                $aeop_logistics_service_result[$key]['logistics_mode_id'] = '';
                $aeop_logistics_service_result[$key]['logistics_mode'] = '';
            }
        }
        return $aeop_logistics_service_result;
    }

}