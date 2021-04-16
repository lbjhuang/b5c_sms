<?php
/**
 * 仓库相关
 * User: b5m
 * Date: 2017/11/21
 * Time: 10:47
 */
class WarehouseAction extends BaseAction
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
        header('Access-Control-Allow-Origin: *');
        parent::_initialize();
    }

    public function index()
    {

    }

    /**
     * 仓库目录路由
     */
    public function warehouseDirectory()
    {
        $this->display();
    }

    /**
     * 仓库目录数据
     */
    public function warehouseDirectoryData()
    {
        $queryParams = ZUtils::filterBlank($this->getParams()) ['data']['query'];
        $model = new WarehouseModel();
        $r = $model->data($queryParams);
        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize']  = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData']  = $r;
        $data ['query']     = $queryParams;
        $data ['count']     = $model->count;
        $data ['notConfiguredWarehouse'] = $model->notConfiguredWarehouse;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    public function filterWarehouse()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $list = WarehouseModel::filterWarehouse($queryParams);
        $this->ajaxReturn(['list' => $list, 'code' => 2000, 'data' => ''], 'json');
    }

    // 查看日志
    public function getOperLogInfo()
    {
        $param = ZUtils::filterBlank($this->getParams());
        $warehouse_log = new WarehouseLogService();
        $data = $warehouse_log->getInfo($param);
        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }



    /**
     * 更新
     */
    public function update()
    {
        try {
            $form =  DataModel::getDataNoBlankToArr() ['data']['query']['form'];
            if (!$form['warehouse']) {
                $code = 3000;
                $msg = L('仓库名称不能为空');
                goto end;
            }
            $rClineVal = RedisModel::lock('warehouse_name' . $form['warehouse'], 10);
            if (!$rClineVal) {
                throw new Exception('已提交数据，无需重复点击');
            }
            $model = M('wms_warehouse', 'tb_');
            $model_other = new Model();
            $model_other->startTrans();
            $form['cd'] = $form ['warehouseCode']; 
            
            if (!$form['id']) { // #10195 仓库新增&编辑优化，新增时根据仓库名称自动创建CD值
                $initData['cd_type'] = 'N00068';
                $initCodeData['CD_NM'] =  'DELIVERY_WAREHOUSE';
                $initCodeData['CD_VAL'] =  $form['warehouse'];
                $initCodeData['USE_YN'] =  'N';
                $initCodeData['is_add'] =  'Y';
                $initCodeData['SORT_NO'] = '0';
                $initData['data'][] = $initCodeData;
                $codeRes = A('Universal/Dictionary')->saveDictionaryByCdType($initData);
                if ($codeRes['code'] !== 200 || !$codeRes['data'][0]['cd']) {
                    $code = 3000;
                    $msg = $codeRes['msg'];
                    goto end;
                }
                $log_add_data_arr = []; $log_add_data = [];
                $log_add_data['warehouse_cd'] = $codeRes['data'][0]['cd'];
                $log_add_data['field_name'] = '仓库名称';
                $log_add_data['front_value'] = '创建';
                $log_add_data['later_value'] = '创建';
                $log_add_data['update_by'] = userName();
                $log_add_data['update_at'] = date("Y-m-d H:i:s");
                $log_add_data_arr[] = $log_add_data;
                $warehouse_log = new WarehouseLogService(); // 添加创建日志，便于知道创建CODE是谁
                $ret = $warehouse_log->addLog($log_add_data_arr);
                if ($ret === false) {
                    $code = 3000;
                    $msg  = L('添加新增仓库操作日志码表状态失败');
                    $model_other->rollback();
                    goto end;
                }
                $form['cd'] = $codeRes['data'][0]['cd'];
            }
            $form ['jobContent'] = implode(':', $form ['jobContent']);
            $form = BaseModel::hump($form);
            $form['updated_by'] = session('m_loginname');
            if(empty($form['contract_start'])) unset($form['contract_start']);
            if(empty($form['contract_end'])) unset($form['contract_end']);
            $form['CD'] = $form ['cd'];
            $form ['operator_cds'] = implode(',', $form ['operator_cds']);
            $form ['in_contacts'] = implode(',', $form ['in_contacts']);
            $form ['out_contacts'] = implode(',', $form ['out_contacts']);
            // 校验关闭条件
            if (!$form ['status'] && !WarehouseModel::checkCanDisable($form ['cd'])) {
                $code = 3000;
                $msg  = L('不符合停用条件，请检查现存量或调拨单');
                goto end;
            }
            if ($form['cost_per_day'] && !is_numeric($form['cost_per_day'])) {
                $code = 3000;
                $msg  = L('仓库每天成本填写错误');
                goto end;
            }
            if (!$form['warehouse_code'] && $form['id']) {
                $code = 3000;
                $msg = L('仓库编辑时，仓库CD不能为空');
                goto end;
            }

            // 修改码表状态
            $cd_status = $form['status'] ? 'Y' : 'N';
            if ($form['id']) {
                $warehouse_log = new WarehouseLogService();
                $log_data = $warehouse_log->getUpdateMessage("tb_ms_cmn_cd", ['CD'=>$form['CD']], ['USE_YN' => $cd_status, 'CD_VAL' => $form['warehouse']], $form['CD']);
                if (!empty($log_data)){ // 添加日志
                    $ret = $warehouse_log->addLog($log_data);
                    if ($ret === false) {
                        $code = 3000;
                        $msg  = L('添加编辑操作日志码表状态失败');
                        $model_other->rollback();
                        goto end;
                    }
                }
                $initData = []; $initCodeData = [];
                $initData['cd_type'] = 'N00068';
                $initCodeData['CD_NM'] =  'DELIVERY_WAREHOUSE';
                $initCodeData['CD_VAL'] =  $form['warehouse'];
                $initCodeData['USE_YN'] =  $cd_status;
                $initCodeData['is_add'] =  'N';
                $initCodeData['CD'] = $form['warehouse_code'];
                $initData['data'][] = $initCodeData;
                $codeRes = A('Universal/Dictionary')->saveDictionaryByCdType($initData);

                if ($codeRes['code'] !== 200) {
                    $code = 3000;
                    $msg = $codeRes['msg'];
                    $model_other->rollback();
                    goto end;
                }
            }
            M('ms_cmn_cd', 'tb_')->where(['CD' => $form['CD']])->save(['USE_YN' => $cd_status]);
            if ($form ['id']) {
                $create_data = $model->create($form);
                $log_data = $warehouse_log->getUpdateMessage("tb_wms_warehouse", ['ID'=>$form['id']], $create_data, $form['CD']);
                // 添加日志
                if (!empty($log_data)){
                    $ret = $warehouse_log->addLog($log_data);
                    if ($ret === false) {
                        $code = 3000;
                        $msg  = L('添加编辑操作日志失败');
                        $model_other->rollback();
                        goto end;
                    }
                }

                if ($model->save($model->create($form)) !== false && $code !== 3000) {
                    $model_other->commit();
                    $code = 2000;
                    $msg  = L('更新成功');
                } else {
                    $code = 3000;
                    $msg  = L('更新失败') .'：'. L($model->getDbError());
                }
            } else {
                $form['created_by'] = session('m_loginname');
                $form['created_at'] = date('Y-m-d H:i:s');
                $exist = $model->where(['CD' => $form['cd']])->count();
                if ($exist > 0) {
                    $code = 3000;
                    $msg  = L('新增仓库失败：仓库档案已存在');
                } else {
                    if ($model->add($model->create($form)) && $code !== 3000) {

                        //所有店铺设置成不支持该新增的仓库
                        $res_warehouse = (new ConfigurationService($model_other))->setStoreNotSupportWarehouse($form ['cd']);
                        if (!$res_warehouse) {
                            $code = 3000;
                            $msg  = L('设置店铺不支持该仓库失败');
                            $model_other->rollback();
                        } else {
                            $model_other->commit();
                            $code = 2000;
                            $msg  = L('新增仓库成功');
                        }
                    } else {
                        $code = 3000;
                        $msg  = L('新增仓库失败') . '：' . L($model->getDbError());
                    }
                }

            }
            end:
            $data = null;

            $response = $this->formatOutput($code, $msg, $data);
            RedisModel::unlock('warehouse_name' . $form['warehouse']);
            $this->ajaxReturn($response, 'json');
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxReturn($res, 'json');
        }

    }

    public function exportXls()
    {
        $queryParams = ZUtils::filterBlank(json_decode($this->getParams()['post_data'], true)) ['data']['query'];
        $model = new WarehouseModel();
        $model->exportXls($queryParams);
    }

    /**
     * 删除仓库
     */
    public function deleteWarehouse()
    {
        $form = ZUtils::filterBlank($this->getParams());
        if ($form) {
            if ($form ['amountSku'] > 0) {
                $code = 3000;
                $msg  = L('删除失败，当前仓库SKU总数大于0');
            } else {
                $model = new Model();
                $isok = $model->table('tb_wms_warehouse')->where(['id' => ['eq', $form ['id']]])->delete();
                if ($isok) {
                    $code = 2000;
                    $msg  = L('删除成功');
                } else {
                    $code = 3000;
                    $msg  = L('删除失败：') . $model->getDbError();
                }
            }
        } else {
            $code = 3000;
            $msg  = L('数据异常');
        }

        $response = $this->formatOutput($code, $msg, null);
        $this->ajaxReturn($response, 'json');
    }


    /**
     * 现存量锁定查询
     */
    public function searchLock()
    {
        $queryParams = ZUtils::filterBlank($this->getParams()) ['data']['query'];
        $model = new WarehouseModel();
        $r = $model->listData($queryParams);
        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize']  = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData']  = $r;
        $data ['query']     = $queryParams;
        $data ['count']     = $model->count;
        $data ['page']      = $model->page;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 在途列表页
     */
    public function onwayIndex()
    {
        $this->display();
    }

    /**
     * 在途列表数据
     */
    public function onwayList()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $list = WarehouseModel::onwayList($queryParams);
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }


    /**
     * 在途列表数据导出
     */
    public function exportOnwayList()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $flag = 1;
        $list = WarehouseModel::onwayList($queryParams, $flag);
        WarehouseModel::exportOnway($list['list']);
    }

    public function onwayOccupiedList()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $list = WarehouseModel::onwayOccupiedList($queryParams);
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 安全库存列表页
     */
    public function safeStock()
    {
        $this->display('safeStock');
    }

    /**
     * 安全库存数据
     */
    public function safeStockList()
    {
        $params =  DataModel::getDataNoBlankToArr();
        $warehouse = $this->warehouse(true);
        //默认一般仓
        !empty($params['warehouse_cd']) or $params['warehouse_cd'] = array_column($warehouse, 'cd');
        $list = WarehouseModel::safeStockList($params);
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 仓库筛选数据
     */
    public function warehouse($flag = false)
    {
        //一般仓 type_cd N002590100
        //$params = ZUtils::filterBlank($this->getParams());
        $params =  DataModel::getDataNoBlankToArr();
        $WarehouseModel = new WarehouseModel();
        $list = $WarehouseModel->warehouse($params);
        if ($flag) {
            return $list;
        }
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 安全库存数据导出
     */
    public function exportSafeStock()
    {
        $params =  DataModel::getDataNoBlankToArr();
        $flag = 1;
        $list = WarehouseModel::safeStockList($params, $flag);
        WarehouseModel::exportSafeStock($list);
    }

    /**
     * 更新安全库存
     */
    public function updateSafeStock()
    {
        $params =  DataModel::getDataNoBlankToArr();
        if (empty($params['id'])) {
            $code = 3000;
            $msg  = L('安全库存id缺失');
        }
        if ($params['id']) {
            $model = M('wms_safety', 'tb_');
            $save['set_safety_stock'] = $params ['set_safety_stock'];
            $save['update_by'] = session('m_loginname');
            $save['update_at'] = date('Y-m-d H:i:s');
            if ($model->save($model->create($params)) !== false) {
                $code = 2000;
                $msg  = L('更新成功');
            } else {
                $code = 3000;
                $msg  = L('更新失败') . '：' . L($model->getDbError());
            }
        }
        $response = $this->formatOutput($code, $msg, null);
        $this->ajaxReturn($response, 'json');
    }
}