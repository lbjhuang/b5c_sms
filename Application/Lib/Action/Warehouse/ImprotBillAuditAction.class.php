<?php
/**
 *  EXCEl出入库审批流程
 * Class ImprotBillAuditAction
 */

class ImprotBillAuditAction extends BaseAction
{

    public static $status_await_sub_cd = "N003670001";     //  出入库审批流程节点	  待提交申请
    public static $status_await_audit_cd = "N003670002";   //  出入库审批流程节点	  待财务审批
    public static $status_audit_acc_cd = "N003670003";      //  出入库审批流程节点	审批完成
    public static $status_audit_rebuttal_cd = "N003670004";  // 出入库审批流程节点	审批驳回
    public static $status_audit_cancel_cd = "N003670005";    //  出入库审批流程节点	审批取消
    public static $status_await_lead_cd = "N003670006";     //  出入库审批流程节点	待领导审批

    public static $out_warehouse_type = "N003680001";  //  出入库类型	 EXCEL出库
    public static $in_warehouse_type = "N003680002";  //  出入库类型	EXCEL入库

    
    public static $one_level_audit_by = "Astor.Zhang";    // 第一级审核人
    public static $two_level_audit_by = "Derek.Jiang";  // 第二级审核人
    public static $three_level_audit_by = "Helen.Yuan";  // 第三级审核人
    
    
    public static $url_detail  = 'index.php?m=stock&a=put_warehouse_approval_view';


    public static $warehouse = [
        "N000680100","N000680200","N000680300","N000680400","N000680500","N000680600","N000680700","N000680800","N000680900","N000681000","N000681100","N000681200","N000681300","N000681400","N000681500","N000681600","N000681700","N000681800","N000681900","N000682100","N000682200","N000682300","N000682400","N000682600","N000682800","N000682900","N000683000","N000683100","N000683200","N000683300","N000683400","N000683500","N000683600","N000683700","N000683800","N000683900","N000684000","N000684100","N000684200","N000684300","N000684400","N000684500","N000684600","N000684700","N000684800","N000684900","N000685000","N000685100","N000685200","N000685300","N000685400","N000685500","N000685600","N000685700","N000685800","N000685900","N000686000","N000686100","N000686200","N000686300","N000686400","N000686500","N000686600","N000686700","N000686800","N000686900","N000687000","N000687100","N000687200","N000687300","N000687400","N000687500","N000687600","N000687700","N000687800","N000687900","N000688000","N000688100","N000688200","N000688300","N000688400","N000688500","N000688600","N000688700","N000688800","N000688900","N000689000","N000689100","N000689200","N000689300","N000689400","N000689401","N000689402","N000689403","N000689404","N000689405","N000689406","N000689407","N000689408","N000689409","N000689410","N000689411","N000689412","N000689413","N000689414","N000689415","N000689416","N000689417","N000689418","N000689419","N000689420","N000689421","N000689422","N000689423","N000689424","N000689425","N000689426","N000689427","N000689428","N000689429","N000689430","N000689431","N000689432","N000689433","N000689434","N000689435","N000689436","N000689437","N000689438","N000689439","N000689440","N000689441","N000689442","N000689443","N000689444","N000689445","N000689446","N000689447","N000689448","N000689449","N000689450","N000689451","N000689452","N000689453","N000689454","N000689455","N000689456","N000689457","N000689458","N000689459","N000689460","N000689461","N000689462","N000689463","N000689464","N000689465","N000689466","N000689467","N000689468","N000689469","N000689470","N000689471","N000689472","N000689473","N000689474","N000689475","N000689476","N000689477","N000689478","N000689479","N000689480","N000689481","N000689482","N000689483","N000689484","N000689485","N000689486","N000689487","N000689488","N000689489","N000689490","N000689491","N000689492","N000689493","N000689494","N000689495","N000689496","N000689497","N000689498","N000689499","N000689500","N000689501","N000689502","N000689503","N000689504","N000689505","N000689506","N000689507","N000689508","N000689509","N000689510","N000689511","N000689512","N000689513","N000689514","N000689515","N000689516","N000689517","N000689518","N000689519","N000689520","N000689521","N000689522","N000689523","N000689524","N000689525","N000689526","N000689527","N000689528","N000689529","N000689530","N000689531","N000689532","N000689533","N000689534","N000689535"
    ];


    public static $code_email = array(
        'N001280200' => 'Bryan.Shang',
        'N001280300' => 'Jayden.Lee',
        'N001282000' => 'Recally.Xu',
        'N001280800' => 'Recally.Xu',
        'N001282800' => 'Jerry.Huang,Wendy.Chen',
        'N001283301' => 'Bryan.Shang',
        'N001283303' => 'Recally.Xu',
        'N001283304' => 'Recally.Xu',
        'N001292700' => 'Jerry.Huang,Wendy.Chen,Mingyuan.Kang',
    );


    public function getTeam(){
        $data = array();
        $cds = array_keys(self::$code_email);
        if ($cds){
            $data = (new ImprotBillAuditService())->teamVal(array('CD'=>array('in',$cds)),'CD as cd , CD_VAL as  cd_val');
        }
        $this->ajaxSuccess($data);
    }

    /**
     *  创建 审批流程
     */
    public function create(){
        try{
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            if (!$request_data['type']){
                throw new Exception("出入库类型必填");
            }
//            // 流程发起权限系统角色为：仓储物流组，admin
            if (in_array(8,$_SESSION['role_id']) || in_array(1,$_SESSION['role_id'])){

                if (!isset($request_data['type'])){
                    throw new Exception("请选择出入库类型");
                }
                if (!isset($request_data['team_cd']) || !isset(self::$code_email[$request_data['team_cd']])){
                    throw new Exception("请选择团队");
                }
                $res = (new ImprotBillAuditService())->add($request_data);
            }else{
                throw new Exception("无权限创建审批流程");
            }
        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess($res);
    }

    /**
     *  列表数据
     */
    public function getList(){
        $request_data = DataModel::getDataNoBlankToArr();
        $list = (new ImprotBillAuditService())->getList($request_data);
        $this->ajaxSuccess($list);
    }


    /**
     *  EXCEL 导入数据
     */
    public function excelData(){
        try{
            $response = array();
            $code = 4000;

            $model = new Model();
            $model->startTrans();
            $audit_no = $_POST['audit_no'];
            if (empty($audit_no)){
                throw new Exception('审批编号不能为空');
            }
            $info_data = (new ImprotBillAuditService())->getFind(array('audit_no'=>$audit_no));
            if (empty($info_data)){
                throw new Exception('审批流程异常，请重新开始');
            }
            // EXCEL导入【状态节点必须为待提交】
            if ($info_data['status_cd'] != self::$status_await_sub_cd){
                throw  new Exception('审批流程状态已经更新，请重新刷新');
            }

            $inser_datas = array();
            $sum_price_usd = 0;   // 货值总额（美元）
            $sku_spec_arr = array();    //  SKU种类
            if ($info_data['type_cd'] == self::$in_warehouse_type){
                // EXCEL入库
                $inService = new ImprotBillAuditInService();
                $res_data = $inService->import();
                if ($res_data['code'] == 2000 ){
                    // 数据处理
                    $service = new ImprotBillAuditService($model);
                    //  删除 EXCEL审核流程表详情
                    $res = $service->delDetail(array('audit_no' => $audit_no));
                    if ($res === false){
                        throw new Exception("EXCEL审核流程详情删除异常");
                    }
                    foreach ($res_data['data']['datas'] as $k => $itme){
                        $tmep_data = array();
                        $inser_data = array();
                        foreach ($itme as $key => $value){
                            $key_new = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $key));
                            $value_new = $value['value'];
                            $tmep_data[$key_new] = $value_new;
                        }
                        $inser_data['audit_no'] = $audit_no ;
                        $inser_data['sku_id'] = isset($tmep_data['sku_id']) ? $tmep_data['sku_id'] : "" ;
                        $inser_data['upc_id'] = isset($tmep_data['sku_id']) ? $tmep_data['upc_id'] : "" ;
                        $inser_data['storage_mark_cd'] = isset($tmep_data['storage_mark']) ? $tmep_data['storage_mark'] : "" ;
                        $inser_data['number'] = isset($tmep_data['number']) ? $tmep_data['number'] : 0 ;
                        $inser_data['dead_line'] = isset($tmep_data['dead_line']) ? $tmep_data['dead_line'] : "" ;
                        $inser_data['storage_warehouse_cd'] = isset($tmep_data['storage_warehouse']) ? $tmep_data['storage_warehouse'] : "" ;
                        $inser_data['sale_team_cd'] = isset($tmep_data['sale_team']) ? $tmep_data['sale_team'] : "" ;
                        $inser_data['small_sale_team_cd'] = isset($tmep_data['small_sale_team']) ? $tmep_data['small_sale_team'] : "" ;
                        $inser_data['storage_log_cost'] = isset($tmep_data['storage_log_cost']) ? $tmep_data['storage_log_cost'] : "" ;
                        $inser_data['log_service_cost'] = isset($tmep_data['log_service_cost']) ? $tmep_data['log_service_cost'] : "" ;
                        $inser_data['storage_date'] = isset($tmep_data['storage_date']) ? $tmep_data['storage_date'] : "" ;
                        $inser_data['pur_order_no'] = isset($tmep_data['pur_order_no']) ? $tmep_data['pur_order_no'] : "" ;
                        $inser_data['price'] = isset($tmep_data['price']) ? $tmep_data['price'] : "" ;
                        $inser_data['currency_cd'] = isset($tmep_data['currency']) ? $tmep_data['currency'] : "" ;
                        $price_usd = round(exchangeRateToUsd(CodeModel::getValue($inser_data['currency_cd'])['CD_VAL']) * $inser_data['price'],2);
                        $inser_data['price_usd'] = $price_usd;
                        $sum_price_usd = $sum_price_usd + $price_usd *  $inser_data['number'];
                        $inser_data['pur_team_cd'] = isset($tmep_data['pur_team']) ? $tmep_data['pur_team'] : "" ;
                        $inser_data['pur_team_company_cd'] = isset($tmep_data['pur_team_company']) ? $tmep_data['pur_team_company'] : "" ;
                        $inser_data['pur_storage_date'] = isset($tmep_data['pur_storage_date']) ? $tmep_data['pur_storage_date'] : "" ;
                        $inser_data['pur_invoice_tax_rate'] = isset($tmep_data['pur_invoice_tax_rate']) ? $tmep_data['pur_invoice_tax_rate'] : "" ;
                        $inser_data['proportion_of_tax'] = isset($tmep_data['proportion_of_tax']) ? $tmep_data['proportion_of_tax'] : "" ;
                        $inser_data['intro_team_cd'] = isset($tmep_data['intro_team']) ? $tmep_data['intro_team'] : "" ;
                        $inser_data['intro_type_cd'] = isset($tmep_data['intro_type']) ? $tmep_data['intro_type'] : "" ;
                        $inser_data['remark'] = isset($tmep_data['remark']) ? $tmep_data['remark'] : "" ;
                        $inser_data['batch_code'] = isset($tmep_data['batch_code']) ? $tmep_data['batch_code'] : "" ;
                        array_push($sku_spec_arr,$inser_data['sku_id']);
                        $inser_data['product_type_cd'] = isset($tmep_data['product_type_cd']) ? $tmep_data['product_type_cd'] : "" ;
                        $inser_datas[] = $inser_data;
                    }

                }else{
                    $code = $res_data['code'];
                    $response = $res_data['data'];
                    throw  new Exception('EXCEL入库导入异常');
                }

                $reqDataArr = $res_data['data']['reqData'];
                foreach ($reqDataArr as &$item){
                    $item['bill']['relationType'] = 'N002350707';
                    $item['bill']['linkBillId'] = $audit_no;
                }
                $reqData = json_encode($reqDataArr);   // 出入库接口数据JSON
                
            }else if ($info_data['type_cd'] == self::$out_warehouse_type){
                //  EXCEL出库
                $outService = new ImprotBillAuditOutService();
                $res_data = $outService->import();
                if ($res_data['code'] == 2000 ){
                    // 数据处理
                    $service = new ImprotBillAuditService($model);
                    //  删除 EXCEL审核流程表详情
                    $res = $service->delDetail(array('audit_no' => $audit_no));
                    if ($res === false){
                        throw new Exception("EXCEL审核流程详情删除异常");
                    }
                    $cdModel = A('Common/Index');
                    $cd_type['currency'] = 'false';
                    $cd_res_arr = $cdModel->get_cd($cd_type);
                    $cd_res = [];
                    foreach ($cd_res_arr as $key => $value) {
                        foreach ($value as $k => $v) {
                            $cd_res[$key][$v['CD_VAL']] = $v['CD'];
                        }
                    }
                    foreach ($res_data['data']['datas'] as $itme){
                        $tmep_data = array();
                        $inser_data = array();
                        foreach ($itme as $key => $value){
                            $key_new = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $key));
                            $value_new = $value['value'];
                            $tmep_data[$key_new] = $value_new;
                        }
                        $inser_data['audit_no'] = $audit_no ;
                        $inser_data['upc_id'] = isset($tmep_data['sku_id']) ? $tmep_data['upc_id'] : "" ;
                        $inser_data['sku_id'] = isset($tmep_data['sku_id']) ? $tmep_data['sku_id'] : "" ;
                        $inser_data['number'] = isset($tmep_data['number']) ? $tmep_data['number'] : 0 ;
                        $inser_data['storage_mark_cd'] = isset($tmep_data['storage_mark']) ? $tmep_data['storage_mark'] : "" ;
                        $inser_data['storage_warehouse_cd'] = isset($tmep_data['storage_warehouse']) ? $tmep_data['storage_warehouse'] : "" ;
                        $inser_data['remark'] = isset($tmep_data['remark']) ? $tmep_data['remark'] : "" ;
                        $inser_data['responsible'] = isset($tmep_data['responsible']) ? $tmep_data['responsible'] : "" ;

                        $inser_data['batch_code'] = isset($tmep_data['batch_code']) ? $tmep_data['batch_code'] : "" ;
                        if (empty($inser_data['batch_code'] )){
                            throw new Exception('批次编号异常');
                        }
                        $params = array(
                            'mixedCode' => $inser_data['sku_id'],
                            'warehouse' => self::$warehouse,
                            'batchCode' => $inser_data['batch_code']
                        );
                        $model = new StandingExistingModel();
                        $res = $model->getBatchData($params);
                        if (!$res){
                            throw new Exception('批次编号异常');
                        }
                        $res = $res[0];
                        $inser_data['product_type_cd'] = (isset($res['productType']) && $res['productType']== '正品') ? 'N003730002' : 'N003730003';
                        $inser_data['pur_order_no'] = isset($res['purNum']) ? $res['purNum'] : "" ;
                        $inser_data['price'] = isset($res['unit_price_origin']) ? $res['unit_price_origin'] : "" ;
                        $inser_data['currency_cd'] = isset($res['pur_currency']) && $cd_res['currency'][$res['pur_currency']] ? $cd_res['currency'][$res['pur_currency']] : "" ;
                        $inser_data['price_usd'] = $res['unitPriceUsd'];
                        $sum_price_usd = $sum_price_usd +  $inser_data['price_usd'] * $inser_data['number'];
                        $inser_data['dead_line'] = isset($res['deadLineDate']) ? $res['deadLineDate'] : "" ;
                        $inser_data['sale_team_cd'] = isset($res['saleTeamCd']) ? $res['saleTeamCd'] : "" ;
                        $inser_data['small_sale_team_cd'] = isset($res['smallSaleTeamCd']) ? $res['smallSaleTeamCd'] : "" ;
                        $inser_data['pur_team_cd'] = isset($res['purTeamCd']) ? $res['purTeamCd'] : "" ;
                        $inser_data['pur_team_company_cd'] = isset($res['ourCompanyCd']) ? $res['ourCompanyCd'] : "" ;
                        $inser_data['intro_team_cd'] = isset($res['introTeamCd']) ? $res['introTeamCd'] : "" ;
                        $inser_data['intro_type_cd'] = "" ;
                        $inser_data['storage_log_cost'] = isset($res['storage_log_cost']) ? $res['storage_log_cost'] : "" ;
                        $inser_data['log_service_cost'] = isset($res['log_service_cost']) ? $res['log_service_cost'] : "" ;
                        $inser_data['pur_invoice_tax_rate'] = isset($res['pur_invoice_tax_rate']) ? $res['pur_invoice_tax_rate'] : "" ;
                        $inser_data['proportion_of_tax'] = isset($res['proportion_of_tax']) ? $res['proportion_of_tax'] : "" ;
                        $inser_data['storage_log_cost'] = isset($res['storage_log_cost']) ? $res['storage_log_cost'] : "" ;
                        $inser_data['log_service_cost'] = isset($res['log_service_cost']) ? $res['log_service_cost'] : "" ;
                        $inser_data['pur_storage_date'] = isset($res['purStorageDate']) ? $res['purStorageDate'] : "" ;
                        $inser_data['storage_date'] = isset($res['addTime']) ? $res['addTime'] : "" ;
                        array_push($sku_spec_arr,$inser_data['sku_id']);
                        $inser_datas[] = $inser_data;
                    }
                }else{
                    $code = $res_data['code'];
                    $response = $res_data['data'];
                    throw  new Exception('EXCEL入库导入异常');
                }
                $reqDataArr = $res_data['data']['reqData'];
                foreach ($reqDataArr as &$item){
                    $item['relationType'] = 'N002350707';
                    $item['orderId'] = $audit_no;
                }
                unset($item);
                $reqData = json_encode($reqDataArr);   // 出入库接口数据JSON

            }else{
                throw  new Exception('EXCEL出入库类型异常');
            }

            Logs([$res_data,$audit_no],__FUNCTION__,__CLASS__);

            if (empty($sum_price_usd)){
                throw new Exception("货值总额计算异常（美元）");
            }

            $res = $service->addAllDetail($inser_datas);
            if (!$res){
                throw new Exception("EXCEL审核流程详情添加异常");
            }
            // 更新主表信息
            $update_data = array(
                'sum_price_usd' => $sum_price_usd,
                'sku_spec' => count(array_unique($sku_spec_arr)),
                'req_data' => $reqData,
            );
            $res = $service->update(array('audit_no'=>$audit_no),$update_data);
            if ($res === false){
                throw new Exception("EXCEL审核流程更新异常");
            }
            $model->commit();
        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError($response,$exception->getMessage(),$code);
        }
        $this->ajaxSuccess($response);
    }


    public function getDetailList(){
        $audit_no = $_GET['audit_no'];
        if (empty($audit_no)){
            $this->ajaxError([],'审批编号不能为空',4000);
        }
        $service = new ImprotBillAuditService();
        $info_data = $service->getFind(array('audit_no'=>$audit_no),'team_cd,audit_no,status_cd,type_cd,sum_price_usd,sku_spec,create_by,create_at,new_audit_by');
        $info_data = CodeModel::autoCodeOneVal($info_data,['status_cd','type_cd','team_cd']);
        if (empty($info_data)){
            $this->ajaxError([],'无审批流程',4000);
        }
        $detail_data = $service->getDetailList(array('audit_no'=>$audit_no));
        $detail_data = CodeModel::autoCodeTwoVal($detail_data,['product_type_cd','storage_mark_cd','storage_warehouse_cd','sale_team_cd','small_sale_team_cd','currency_cd','pur_team_cd','pur_team_company_cd','intro_team_cd','intro_type_cd',]);
        $data = array(
            'info_data' => $info_data,
            'detail_data' => $detail_data,
        );
        $this->ajaxSuccess($data);
    }



    public function audit(){
        try{

            $response = array();
            $code = 4000;
            $model = new Model();
            $model->startTrans();
            $request_data = DataModel::getDataNoBlankToArr();
            $service_model = new ImprotBillAuditService($model);

            if (empty($request_data['audit_no'])){
                throw new Exception('审批编号不能为空');
            }

            if (empty($request_data['status_cd'])){
                throw new Exception('流程节点不能为空');
            }

            $info_data = $service_model->getFind(array('audit_no'=>$request_data['audit_no']));
            if (empty($info_data)){
                throw new Exception('审批流程异常，请重新开始');
            }
            $send_data_wx = array();   // 企业微信通知
            $send_data_wx_end = array();   //  流程结束通知
            switch ($request_data['status_cd']){
                case self::$status_await_lead_cd :
                    // 待提交->待领导审批
//                    var_dump($info_data['status_cd']);die;
                    if ($info_data['status_cd'] != self::$status_await_sub_cd){
                        throw new Exception('审批流程状态已经更新，请刷新页面');
                    }

                    // 非创建人无法提交
                    if ($info_data['create_by'] != userName()){
                        throw new Exception('无权限操作');
                    }

                    // 查询直属领导
                    $new_audit_by =  $service_model->getCreateBy($request_data['audit_no']);

                    $sum_price_usd = $info_data['sum_price_usd'];
                    //  根据货值总额来判断审批人
                    if ($sum_price_usd >= 5000){
                        // 直属领导  财务总监-Derek jiang及高级财务总监-Helen yuan审核
                        $two_level_audit_by = $service_model->getLevelFinanceAuditBy("gt5000","two");
                        $three_level_audit_by = $service_model->getLevelFinanceAuditBy("gt5000","three");
                        if(!$two_level_audit_by) {
                            throw new Exception('财务总监角色未配置用户，请先配置用户');
                        }
                        if(!$three_level_audit_by) {
                            throw new Exception('高级财务总监角色未配置用户，请先配置用户');
                        }
                        $audit_by_quence = [ $new_audit_by, $two_level_audit_by, $three_level_audit_by,];
                    }else{
                        // 高级财务经理-joseph chen审核
                        $one_level_audit_by = $service_model->getLevelFinanceAuditBy("lt5000","one");
                        if(!$one_level_audit_by) {
                            throw new Exception('财务经理角色未配置用户，请先配置用户');
                        }
                        $audit_by_quence = array($new_audit_by,$one_level_audit_by);
                    }
                    $update_data = array(
                        'status_cd' => self::$status_await_lead_cd,
                        'new_audit_by' => $new_audit_by,
                        'audit_by_quence' => implode('->',$audit_by_quence),
                    );
                    $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                    if ($res == false){
                        throw new Exception("EXCEL审核流程更新异常-财务审核");
                    }

                    $message = "提交申请";
                    $send_data_wx = array(
                        'send_by' => $new_audit_by,
                        'title' => '待审批事项',
                        'subhead' => $info_data['create_by'].'发起了EXCEL出入库申请，请尽快审批',
                        'audit_no' => $request_data['audit_no'],
                        'create_at' => date('Y-d-m H:i:s'),
                        'create_by' => $info_data['create_by'],
                        'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                    );
                    break;

                case self::$status_await_audit_cd :

                    // 待领导审批->财务审核
                    if ($info_data['status_cd'] != self::$status_await_lead_cd){
                        throw new Exception('审批流程状态已经更新，请刷新页面');
                    }
                    // 非当前审核人 无法提交
                    if ($info_data['new_audit_by'] != userName()){
                        throw new Exception('无权限操作');
                    }

                    $new_audit_by = $info_data['new_audit_by'];
                    $audit_by_quence = explode('->',$info_data['audit_by_quence']);
                    $next = "";  // 下一位审批人
                    $index = array_search($new_audit_by, $audit_by_quence);
                    if($index !== false && $index < count($audit_by_quence)-1) $next = $audit_by_quence[$index+1];
                    if (!$next){
                        throw new Exception('审批流程状态已经完成');
                    }
                    unset($audit_by_quence[$index]);
                    $update_data = array(
                        'status_cd' => self::$status_await_audit_cd,
                        'new_audit_by' => $next,
                        'audit_by_quence' => implode('->',$audit_by_quence),
                    );
                    $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                    if ($res == false){
                        throw new Exception("EXCEL审核流程更新异常-财务审核");
                    }
                    $message = "通过审批";
                    $send_data_wx = array(
                        'send_by' => $next,
                        'title' => '待审批事项',
                        'subhead' => $info_data['create_by'].'发起了EXCEL出入库申请，请尽快审批',
                        'audit_no' => $request_data['audit_no'],
                        'create_at' => date('Y-d-m H:i:s'),
                        'create_by' => $info_data['create_by'],
                        'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                    );
                    break;

                case self::$status_audit_acc_cd :
                    // 待财务审核->审批完成
                    if ($info_data['status_cd'] != self::$status_await_audit_cd){
                        throw new Exception('审批流程状态已经更新，请刷新页面');
                    }

                    if ($info_data['new_audit_by'] != userName()){
                        throw new Exception('无权限操作');
                    }

                    $new_audit_by = $info_data['new_audit_by'];
                    $audit_by_quence = explode('->',$info_data['audit_by_quence']);
                    $next = "";  // 下一位审批人
                    $index = array_search($new_audit_by, $audit_by_quence);
                    if($index !== false && $index < count($audit_by_quence)-1) $next = $audit_by_quence[$index+1];

                    if (empty($next)){
                        $update_data = array(
                            'status_cd' => self::$status_audit_acc_cd,
                        );
                        $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                        if ($res == false){
                            throw new Exception("EXCEL审核流程更新异常-审批完成");
                        }
                        $req_data = json_decode($info_data['req_data'],true);
                        if (empty($req_data)){
                            throw new Exception("EXCEL审核流程出入库接口数据异常");
                        }
                        // 调用java接口出入库处理
                        if ($info_data['type_cd'] == self::$in_warehouse_type){
                            $res_api = (new WmsModel())->xlsInStorage($req_data);
                        }elseif ($info_data['type_cd'] == self::$out_warehouse_type){
                            $res_api = (new WmsModel())->xlsOutStorage($req_data);
                        }else{
                            throw new Exception("EXCEL审核流程出入库类型异常");
                        }
                        if (!$res_api || $res_api['code'] != 2000) {
                            throw new Exception("出入库接口异常:".$res_api['msg']);
                        }

                        $send_data_wx = array(
                            'send_by' => $info_data['create_by'],
                            'title' => '通知事项',
                            'subhead' => '财务已经审批通过了你的EXCEL出入库申请，请知悉',
                            'audit_no' => $request_data['audit_no'],
                            'create_at' => date('Y-d-m H:i:s'),
                            'create_by' => $info_data['create_by'],
                            'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                        );

                        $send_data_wx_end = array(
                            'send_by' => "",
                            'title' => '通知事项',
                            'subhead' => '财务已经审批通过了'.$info_data['create_by'].'的EXCEL出入库申请，请知悉',
                            'audit_no' => $request_data['audit_no'],
                            'create_at' => date('Y-d-m H:i:s'),
                            'create_by' => $info_data['create_by'],
                            'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                        );

                    }else{
                        unset($audit_by_quence[$index]);
                        $update_data = array(
                            'new_audit_by' => $next,
                            'audit_by_quence' => implode('->',$audit_by_quence),
                        );
                        $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                        if ($res == false){
                            throw new Exception("EXCEL审核流程更新异常-审批完成");
                        }

                        $send_data_wx = array(
                            'send_by' => $next,
                            'title' => '待审批事项',
                            'subhead' => $info_data['create_by'].'发起了EXCEL出入库申请，请尽快审批',
                            'audit_no' => $request_data['audit_no'],
                            'create_at' => date('Y-d-m H:i:s'),
                            'create_by' => $info_data['create_by'],
                            'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                        );


                    }
                    $message = "审批通过";
                    break;
                case self::$status_audit_rebuttal_cd :

                    if ($info_data['new_audit_by'] != userName()){
                        throw new Exception('无权限操作');
                    }

                    // 审批驳回
                    $update_data = array(
                        'status_cd' => self::$status_audit_rebuttal_cd,
                    );
                    $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                    if (!$res){
                        throw new Exception("EXCEL审核流程更新异常-审批驳回");
                    }
                    $message = "审批驳回";

                    $send_data_wx = array(
                        'send_by' => $info_data['create_by'],
                        'title' => '待审批事项',
                        'subhead' => '财务已经审批驳回了你的EXCEL出入库申请，请知悉',
                        'audit_no' => $request_data['audit_no'],
                        'create_at' => date('Y-d-m H:i:s'),
                        'create_by' => $info_data['create_by'],
                        'url_detail' => ERP_URL.self::$url_detail."&type={$info_data['type_cd']}&auditNo={$request_data['audit_no']}",
                    );

                    break;
                case self::$status_audit_cancel_cd :
                    #

                    if ($info_data['create_by'] != userName()){
                        throw new Exception('仅发起人可取消此审批单');
                    }

                    $update_data = array(
                        'status_cd' => self::$status_audit_cancel_cd,
                    );
                    $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                    if (!$res){
                        throw new Exception("EXCEL审核流程更新异常-审批取消");
                    }
                    $message = "取消审批";
                    break;
                case self::$status_await_sub_cd :
                    // 退回上一步-待提交
                    if ($info_data['new_audit_by'] != userName()){
                        throw new Exception('无权限操作');
                    }

                    $update_data = array(
                        'status_cd' => self::$status_await_sub_cd,
                    );

                    $res = $service_model->update(array('audit_no'=>$request_data['audit_no']),$update_data);
                    if (!$res){
                        throw new Exception("EXCEL审核流程更新异常-待提交");
                    }
                    $message = "退回上一步";
                    break;
                default :
                    throw  new Exception('审核状态异常');
            }
            $insert_data_log = array(
                'audit_no' => $request_data['audit_no'],
                'message' => $message,
                'create_by' => userName(),
                'create_at' => date("Y-m-d H:i:s",time()),
            );
            $res = $service_model->addLog($insert_data_log);
            if (!$res){
                throw  new Exception('日志添加失败');
            }
            $model->commit();

            // 发送企业微信通知
            $service_model->WorkWxSendMarkdownMessage($send_data_wx,$request_data['audit_no']);
            // 最后的企业微信通知
            if ($send_data_wx_end){
                $send_bys = self::$code_email[$info_data['team_cd']];
                if ($send_bys){
                    $send_bys = explode(',',$send_bys);
                    foreach ($send_bys as $send_by){
                        $send_data_wx_end['send_by'] = $send_by;
                        $service_model->WorkWxSendMarkdownMessage($send_data_wx_end,$request_data['audit_no']);
                    }
                }
            }


        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError($response,$exception->getMessage(),$code);
        }
        $this->ajaxSuccess($response);

    }

    public function getLog(){
        $audit_no = $_GET['audit_no'];
        if (empty($audit_no)){
            $this->ajaxError([],'审批编号不能为空',4000);
        }
        $service = new ImprotBillAuditService();
        $list = $service->getLogList(array('audit_no'=>$audit_no));
        $this->ajaxSuccess($list);
    }
}