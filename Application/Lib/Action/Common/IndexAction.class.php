<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/8
 * Time: 17:30
 */
@import("@.Model.Scm.DemandModel");

class IndexAction extends BaseAction
{
    public function _initialize()
    {
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
        ){
            parent::_initialize();
        }
    }

    public function get_cd($cd_type_arr = []) {
        $types = $cd_type_arr ? $cd_type_arr : I('request.cd_type');
        // 鉴于部分code(我方公司)需要实时获取，添加是否需要缓存来处理
        $cd_m   = new TbMsCmnCdModel();
        $data   = [];
        foreach ($types as $k => $v) {
            $no_cache = false;
            if ($k == 'supplier') {
                $no_cache = true;
                $data[$k] = CommonDataModel::supplier();
                continue;
            }
            if ($k == 'company_open') {
                $no_cache = true;
                $data[$k] = CommonDataModel::companyOpen();
                continue;
            }
            $key = $k.'_cd_pre';
            if(isset($cd_m::$$key)) {
                if ($cd_m::$$key === 'N00124') {
                    $no_cache = true;
                }
                $data[$k] = $v === 'true' ? $cd_m->getCd($cd_m::$$key, $no_cache) : $cd_m->getCdY($cd_m::$$key, $no_cache);
            }
        }
        if ($cd_type_arr) { // 内部调用
            return $data;
        }
        $this->ajaxReturn(['data'=>$data,'msg'=>'','code'=>2000]);
    }

    /**
     * 获取地址（弃用）
     */
    public function get_address() {
        $pid        = I('request.pid');
        $address    = (new TbCrmSiteModel())->getChildrenAddress($pid);
        $this->ajaxReturn(['data'=>$address,'msg'=>'','code'=>2000]);
    }

    /**
     * 获取地址
     */
    public function get_area() {
        $parent_no  = I('request.parent_no');
        $is_id = I('is_id');
        if (strval($is_id) === 'Y' && $parent_no) { // 表明parent_no传的是id,而不是area_no，需要根据id获取area_no
            $address_info = (new AreaModel())->getAreaById($parent_no);
            $parent_no = $address_info['area_no'];
        }
        $address    = (new AreaModel())->getChildrenArea($parent_no);
        $this->ajaxReturn(['data'=>$address,'msg'=>'','code'=>2000]);
    }

    public function search_customer_or_supplier() {
        $supplier_id    = htmlspecialchars_decode(I('request.supplier_id'));
        $type           = I('request.type');
        $demand_type    = I('request.demand_type');//需求类型
        $source_cd      = I('request.source_cd');//来源类型
        $all            = I('request.all');
        $sp_supplier    = M('sp_supplier','tb_crm_');
        if(empty($supplier_id) && empty($all))
            $this->ajaxReturn(['data'=>[],'msg'=>'请填写搜索条件','code'=>3000]);
        if ($supplier_id) {
            $conditions['SP_NAME']          = array('like','%'. $supplier_id.'%');
            $conditions['SP_NAME_EN']       = array('like','%'. $supplier_id.'%');
            $conditions['SP_RES_NAME_EN']   = array('like','%'. $supplier_id.'%');
            $conditions['_logic']           = 'or';
            $where['_complex']              = $conditions;
        }
        //需求类型=销售订单时 SCM创建需求
        if ($demand_type == DemandModel::$demand_type_sell) {
            $where['AUDIT_STATE'] = TbCrmSpSupplierModel::IS_AUDIT_YES; //已审核
        }
        //来源类型=一般申请付款
        if ($source_cd == TbPurPaymentAuditModel::$source_general_payable) {
            $where['AUDIT_STATE'] = ['in', [TbCrmSpSupplierModel::IS_AUDIT_YES, TbCrmSpSupplierModel::NOT_AUDIT]]; //已审核
        }
        $where['DATA_MARKING']          = $type;
        //$where['SP_CHARTER_NO']         = array('neq',''); from 9779 采购退货发起根据供应商id校验 因为部分没有营业执照号，需要去掉该筛选条件
        if (true == $all) {
            $field = ['ID','SP_NAME'];
        }else{
            $field = ['*'];
        }
        $supper_info = $sp_supplier->field($field)->where($where)->select();
        $this->ajaxReturn(['data'=>$supper_info,'msg'=>'success','code'=>2000]);
    }

    public function exchange_rate() {
        $redis      = RedisModel::connect_init();
        $rates      = json_decode($redis->get('xchr_'.date('Ymd')),true);
        if (empty($rates)) {
            $rates      = json_decode($redis->get('xchr_'.date('Ymd',strtotime('-1 day'))),true);
        }
        if($rates) {
            $this->ajaxSuccess(['exchange_rate'=>$rates]);
        }else {
            $this->ajaxError();
        }

    }


    /**
     * 统一日志获取接口 gs-dp
     */
    public function getLogs() {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            $res['code'] = 200;

            $search_map = $search_type = [];
            if ($request_data['search']) {
                foreach ($request_data['search'] as $key => $value) {
                    $search_map[$key] = $key;
                    if (!is_array($value)) {
                        $search_type[] = $key;
                    }
                }
            }
            list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, [], $search_type);
            $logs = LogsModel::getOperationLogs($where, $limit, $request_data['table_map']);

            //数据二次组装，自定义
            switch ($request_data['table_map']) {
                case 'area_config':
                    break;
                default:
                    break;
            }
            $res['data'] = $logs;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //根据物流公司获取物流方式
    public function getLogisticsWay() {
        $request_data = DataModel::getDataNoBlankToArr();
        $res = DataModel::$success_return;
        $res['code'] = 200;
        $where['LOGISTICS_CODE'] = $request_data['logistics_company_code'];
        $res['data'] = CommonDataModel::logisticsType($where);
        $this->ajaxReturn($res);
    }

    //支付渠道获取支付方式
    public function getPaymentWay() {
        $request_data = DataModel::getDataNoBlankToArr();
        $res = DataModel::$success_return;
        $res['code'] = 200;
        $etc = TbMsCmnCdModel::getPaymentChannelEtc($request_data['payment_channel_cd']);
        $way_cds = explode(',', $etc);
        $res['data'] = TbMsCmnCdModel::getPaymentWayByChannelCd($way_cds);
        $this->ajaxReturn($res);
    }

    //根据code获取子级code
    public function sub_basis()
    {
        $params = $this->jsonParams();
        $column = $params['column'] ? $params['column'] : 'ETC';
        $data = CommonDataModel::subBasis($params['code'], $column);
        $this->ajaxSuccess($data, 'success');
    }

    //获取合同信息
    public function contract_info()
    {
        //CON_COMPANY_CD 922
        //SP_NAME 测试公司
        $params = $this->jsonParams();
        $data = $params;
        //兼容data数据格式
        if (!empty($params['data'])) {
            $data = $params['data'];
        }
        $data['audit_status_cd'] = TbCrmContractModel::FINISH;
        $data = CommonDataModel::contractInfo($data);
        $this->ajaxSuccess($data, 'success');
    }

    //获取资金调配合同
    public function fund_allocation_contract()
    {
        //CON_COMPANY_CD 我方公司cd或供应商id  CON_NAME CON_COMPANY_CD
        //PAY_COMPANY_CD 付款公司cd或供应商id  CON_NAME CON_COMPANY_CD
        $params = $this->jsonParams();
        $data = $params;
        //兼容data数据格式
        if (!empty($params['data'])) {
            $data = $params['data'];
        }
        $data = CommonDataModel::fundAllocationContract($data);
        $this->ajaxSuccess($data, 'success');
    }

    //获取供应商信息（已审核、无需审核）
    public function supplier_info()
    {
        //SP_NAME 测试公司
        $params = $this->jsonParams();
        $data = $params;
        //兼容data数据格式
        if (!empty($params['data'])) {
            $data = $params['data'];
        }
        $data = CommonDataModel::supplierInfo($data);
        $this->ajaxSuccess($data, 'success');
    }

    //获取关联单据号列表
    public function order_info()
    {
        $params = $this->jsonParams();
        $data = CommonDataModel::orderInfo($params);
        $this->ajaxSuccess($data, 'success');
    }

    // 获取【我方公司】数据源，排除已经废弃的公司
    public function get_our_company(){
        $condtion = array(
            'CD'=>array('like','N00124%'),
            'USE_YN'=> "Y",
            'ETC5'=>array(array('NEQ','1'),array("exp" ,"IS NULL"),'or'),
        );
        $field = "CD,CD_VAL,ETC,ETC2";
        $data = CodeModel::getCodeAll($condtion,$field);
        $this->ajaxReturn(['data'=>$data,'msg'=>'','code'=>2000]);
    }
}