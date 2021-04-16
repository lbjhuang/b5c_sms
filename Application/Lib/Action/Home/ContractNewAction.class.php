<?php

use GuzzleHttp\Client;

class ContractNewAction extends BaseAction
{

    public function _initialize()
    {
        $this->service = new ContractService();
    }
// ==新版合同流程 #11006-法务合同审批流程20201124START======================================================
    public function saveFileStatus() 
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkSaveFileStatus($request_data['data']);
            $response_data = $this->service->saveFileStatus($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function flow()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkContractBaseInfo($request_data['data']);
            $response_data = $this->service->flow($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function checkSaveFileStatus($data)
    {
        if (!$data['contract_id']) {
            throw new Exception("合同id参数缺失");
        }
    }
    public function saveSealStatus() 
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkSaveSealStatus($request_data['data']);
            $response_data = $this->service->saveSealStatus($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function checkSaveSealStatus($data)
    {
        if (!in_array($data['seal_status'], ['1', '2'])) {
            throw new Exception("盖章状态传值异常{$data['seal_status']}");
        }
        if (!$data['contract_id']) {
            throw new Exception("合同id参数缺失");
        }
    }

    public function getLegalPeople()
    {
        try {
            $response_data = $this->service->getLegalPeople();
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function saveLegalFile()
    {
        try {
            $request_data['data'] = $_POST;
            $response_data = $this->service->saveLegalFile($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }   
    }

    public function getAuditNameInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $response_data = $this->service->getAuditNameInfo($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function checkUploadAuditContract()
    {

    }
    public function uploadAuditContract()
    {
        try {
            $request_data['data'] = $_POST;
            $this->checkUploadAuditContract($request_data['data']);
            $response_data = $this->service->uploadAuditContract($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function getAuditInfo()
    {
       try {
           $request_data = DataModel::getDataNoBlankToArr();
           $this->checkGetAuditInfo($request_data['data']);
           $response_data = $this->service->getAuditInfo($request_data['data']);
           $this->ajaxSuccess($response_data);
       } catch (Exception $exception) {
           $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
       } 
    }

    public function getLog()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkContractBaseInfo($request_data['data']);
            $response_data = $this->service->getLog($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    public function checkContractBaseInfo($data)
    {
        if (!$data['contract_id']) {
            throw new Exception("合同id参数缺失");
        }
    }

    public function checkGetAuditInfo($data)
    {

    }


    public function audit()
    {
       try {
           $request_data = DataModel::getDataNoBlankToArr();
           $this->checkAudit($request_data['data']);
           list($response_data, $msg) = $this->service->audit($request_data['data']);
           $this->ajaxSuccess($response_data, $msg);
       } catch (Exception $exception) {
           $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
       } 
    }

    public function getAuditCommonData()
    {
        try {
            $response_data = $this->service->getAuditCommonData();
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }
    // 获取该合同的领导名称和法务审核
    public function getAuditPeople()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkGetAuditPeople($request_data['data']);
            $response_data = $this->service->getAuditPeople($request_data['data']);
            $this->ajaxSuccess($response_data, '类型是'.$this->service->leaderType);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }
    // 数据展示（合同&&供应商&&风险等级）
    public function getContractInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkGetContractInfo($request_data['data']);
            $response_data = $this->service->getContractInfo($request_data['data']);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }
    public function checkAudit($data)
    {
        if (strval($data['type']) !== '4') { // 非退回操作，意见栏必填
            if (!$data['remark']) throw new Exception("请先填写意见栏");
        }
    }
    public function checkGetAuditPeople($data)
    {

    }
    public function checkGetContractInfo($data)
    {

    }
    public function checkCreateContractInfo($data)
    {
        if ($data['type'] == '2') { // 创建提交审核时需要校验
            if (strtotime($data['START_TIME']) >= strtotime($data['END_TIME'])) {
                throw new Exception("合同开始时间不得大于结束时间");
            }
            if (!$data['supplier_id']) {
                throw new Exception("供应商ID缺失，请先填写");
            }
            $res = A('Supplier')->search_supplier_by_id($data['supplier_id']);
            if ($res === false) {
                throw new Exception("暂无法获取到该供应商ID{$data['supplier_id']}的相关信息，请先核实");
            }
            if (strval($res['AUDIT_STATE']) === '1') {
                throw new Exception("当前id对应的公司（供应商/B2B客户）未审核，请先审核");
            }
            if (!$res['SP_NAME']) {
                throw new Exception("该供应商名称缺失，请先补充");
            }
            // 没有传供应商名称的值时，用这个
            if (!$data['SP_NAME']) {
                $data['SP_NAME'] = $res['SP_NAME'];
            }
            $data['CRM_CON_TYPE'] = $res['DATA_MARKING']; // 合同归属（b2b客户，采购供应商）
        }
        return $data;
    }
    // 新版-创建合同
    public function createContractInfo()
    {
        $trans_result = true;
        $trans = M();
        $trans->startTrans();
        try {
            $request_data = $_POST;
            if (!$request_data) {
                $request_data = DataModel::getDataNoBlankToArr();
            }
            $request_data = $this->checkCreateContractInfo($request_data);
            $response_data = $this->service->createContractInfo($request_data);
        } catch (Exception $ex) {
            $trans_result = false;
            Log::record("== 合同创建失败 ==" . $ex->__toString(), Log::ERR); 
            Log::record($ex->getMessage(), Log::SQL);
        }
        if ($trans_result === false) {
            $trans->rollback();
            $this->ajaxError($response_data, $ex->getMessage(), $ex->getCode());
        } else {
            $trans->commit();
            $this->ajaxSuccess($response_data);
        }
    }


    // 获取审批流程节点
    public function getAuditStatus()
    {
        try {
            $response_data = CodeModel::getLegalAuditStatus();
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
         
    }

// ==新版合同流程 #11006 法务合同审批流程 END===========================================================================

}