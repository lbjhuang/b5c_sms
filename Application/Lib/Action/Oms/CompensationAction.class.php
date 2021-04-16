<?php

class CompensationAction extends CompensationBaseAction
{
    protected $service;
    protected $compensationRepository;
    protected $model;

    public function __construct()
    {
        parent::__construct();
        #全局仅有此model 方便事务和查询
        #注入service repository
        $this->model = M();
        $this->service = new CompensationService($this->model);
        $this->compensationRepository = new CompensationRepository();
    }

    // 路由部分
    public function compensation_list() {
        $this->display();
    }

    public function create() {
        $this->display();
    }

    public function detail() {
        $this->display();
    }

    public function edit() {
        $this->display();
    }

    #创建赔付单
    public function compenCreate()
    {

        try {
            $params = ZUtils::filterBlank($this->getParams());
            $rClineVal = RedisModel::lock(__FUNCTION__.$params['b5c_order_no'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $this->model->startTrans();
            $this->service->compenCreate($params);
            $this->model->commit();
            RedisModel::unlock(__FUNCTION__.$params['b5c_order_no']);
            $this->ajaxSuccess([]);
        } catch (Exception $e) {
            #捕获异常自动回滚
            RedisModel::unlock(__FUNCTION__.$params['b5c_order_no']);
            $this->getException($e, $this->model);
        }
    }

    #根据订单号查询是否可以发起赔付单 是的话返回订单信息
    public function checkCompen()
    {

        try {
            #订单存在于已出库列表且未发起赔付才能发起
            $b5cOrderId = $_REQUEST['b5c_order_no'] ? $_REQUEST['b5c_order_no'] : 0;
            if (empty($b5cOrderId)) {
                throw new Exception(L('订单id非法'));
            }
            $order = $this->service->getOrderDetail($b5cOrderId);

            if (!$order) {
                throw new Exception(L('查询失败，当前订单不满足赔付条件'));
            }
            $compen = $this->service->getCompen(['b5c_order_no' => $b5cOrderId, 'deleted_by' => ['exp', 'IS NULL']]);
            if ($compen) {
                throw new Exception(L('查询失败，当前此订单已经在赔付处理'));
            }
            $this->ajaxSuccess($order);

        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    #赔付单列表
    public function CompenList()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $list = $this->service->CompenList($params);
            $this->ajaxSuccess($list);
        } catch (Exception $e) {
            #捕获异常自动回滚
            $this->getException($e, $this->model);
        }

    }

    #修改赔付单
    public function compenEdit()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $rClineVal = RedisModel::lock(__FUNCTION__.$params['id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $this->model->startTrans();
            $this->service->compenEdit($params);
            $this->model->commit();
            RedisModel::unlock(__FUNCTION__.$params['id']);
            $this->ajaxSuccess([]);
        } catch (Exception $e) {
            #捕获异常自动回滚
            RedisModel::unlock(__FUNCTION__.$params['id']);
            $this->getException($e, $this->model);
        }
    }

    #赔付单详情
    public function compenDetail()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $data = $this->service->compenDetail($params);
            $this->ajaxSuccess($data);
        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    #日志列表
    public function compenLog()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $data = $this->service->compenLog($params);
            $this->ajaxSuccess($data);
        } catch (Exception $e) {
            $this->getException($e);
        }

    }

    #删除赔付单
    public function compenDelete()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $rClineVal = RedisModel::lock(__FUNCTION__, 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $this->model->startTrans();
            $this->service->compenDelete($params);
            $this->model->commit();
            RedisModel::unlock(__FUNCTION__);
            $this->ajaxSuccess();
        } catch (Exception $e) {
            RedisModel::unlock(__FUNCTION__);
            $this->getException($e,$this->model);
        }
    }

    #赔付单导出  根据ids
    public function compenExport()
    {
        try {
            $params = ZUtils::filterBlank($this->getParams());
            $this->service->compenExport($params);
//            $this->ajaxSuccess($data);
        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    public function compenCommonList()
    {
        $warehoustList = $this->service->warehoustList();
        $CompenStatusList = $this->service->CompenStatusList();
        $CompenReasonList = $this->service->CompenReasonList();
        $warehoustList = array_column($warehoustList, null, 'CD');
        $CompenStatusList = array_column($CompenStatusList, null, 'CD');
        $CompenReasonList = array_column($CompenReasonList, null, 'CD');
        $this->ajaxSuccess([
            'warehouse' => $warehoustList,
            'compen_status' => $CompenStatusList,
            "Compen_reason" => $CompenReasonList
        ]);

    }


    /**
     * 批量插入赔付单
     * @author Redbo He
     * @date 2021/3/22 16:15
     */
    public function batchImportCompensate()
    {
        try {
            $rClineVal = RedisModel::lock('batchImportCompensate', 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $this->model->startTrans();
            $res         = DataModel::$success_return;
            $res['code'] = 2000;
            $res['data'] = $this->service->excelImportCompensate();
            $errors = $this->service->errors;
            if (!empty($errors)) {
                $this->service->errors = [];
                $this->model->rollback();
                $res['code'] = 3000;
                $res['info'] = 'error';
                $res['msg'] = 'error';
            }
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
        }
        RedisModel::unlock('batchImportCompensate');
        $this->ajaxReturn($res);
    }

    /**
     * 一键生成发票
     */
    public function generateInvoice()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal    = RedisModel::lock('generateInvoice', 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 2000;
            $res['data'] = $this->service->oneKeyGenerateInvoice($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        RedisModel::unlock('generateInvoice');
        $this->ajaxReturn($res);
    }

    /**
     * 提醒上传文件
     */
    public function remindUploadFiles()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = $this->service->remindUploadFiles($request_data);
            $this->ajaxSuccess();
        } catch (Exception $exception) {
            $this->getException($exception);
        }
    }

    /**
     * 下载模板
     */
    public function downloadTemplate()
    {
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Oms/Compensation/template.xlsx';
        Http::download($filename, $filename);
    }

}
