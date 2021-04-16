<?php
/**
 * Created by sublime.
 * User: b5m
 * Date: 17/10/16
 * Time: 14:38
 * By: huanzhu
 */
import("@.Model.StringModel");

class StoreAction extends BaseAction
{

    public $type_cd_val = [
        0 => '无',
        1 => '发货仓',
        2 => '揽收仓',
        3 => '退货仓',
    ];
    public $warehouse_belong_cd_val = [
        1 => '物流公司',
        2 => '平台店铺',
        3 => '物流公司/平台店铺',
    ];
    #杨素，扶名，寄凡，乐山，卓平，飞松   拉单微信推送
    private static $store_push_order_wx_wid = [
        'YangSu','fuming','jifan','leshan','zhuoping','FeiSong'
    ];
    public function index()
    {
        $this->display('index');
    }

    //基础配置
    public function detail()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $this->display('detail');
    }

    // 店铺键值对(id-name)
    public function get_store()
    {
        $AllocationExtendAttributionRepository = new AllocationExtendAttributionRepository();
        $store_list = $AllocationExtendAttributionRepository->getStoreName();
        $res = DataModel::$success_return;
        $res['data'] = $store_list;
        $this->ajaxReturn($res);
    }

    //仓库配置
    public function detail_ware()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $this->display('detail_ware');
    }

    //物流配置
    public function detail_logistics()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $this->display('detail_logistics');
    }

    //ERP自定义走仓规则配置
    public function detail_warehouse_recommended()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $pms_host = PMS_HOST == 'http://pms.gshopper.com' ? 'https://pms.gshopper.com' : PMS_HOST;
        $this->assign('PMS_HOST', $pms_host);
        $this->display('detail_warehouse_recommended');
    }

    public function detail_other()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);

        $this->display("detail_other");
    }

    //日志
    public function detail_log()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $this->display('detail_log');
    }

    // 财务配置
    public function detail_finance()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        $this->display('detail_finance');
    }

    public function export_store()
    {
        $export = new ExportExcelModel();
        $key = "A";
        $export->attributes = [
            $key++ => ['name' => L('店铺编号'), 'field_name' => 'ID'],
            $key++ => ['name' => L('店铺名称'), 'field_name' => 'STORE_NAME'],
            $key++ => ['name' => L('店铺别名'), 'field_name' => 'MERCHANT_ID'],
            $key++ => ['name' => L('负责人联系方式'), 'field_name' => 'USER_ID'],
            $key++ => ['name' => L('店铺负责人'), 'field_name' => 'store_by_name'],
            $key++ => ['name' => L('运营类型'), 'field_name' => 'OPERATION_ZN_TYPE'],
            $key++ => ['name' => L('店铺状态'), 'field_name' => 'STORE_STATUS'],
            $key++ => ['name' => L('店铺后台地址'), 'field_name' => 'STORE_BACKSTAGE_URL'],
            $key++ => ['name' => L('国家字段'), 'field_name' => 'zh_name'],
            $key++ => ['name' => L('店铺主页地址'), 'field_name' => 'STORE_INDEX_URL'],
            $key++ => ['name' => L('平台名称'), 'field_name' => 'PLAT_NAME'],
            $key++ => ['name' => L('商品主链接'), 'field_name' => 'PRODUCT_DETAIL_URL_MARK'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'SALE_TEAM'],
            $key++ => ['name' => L('发货系统'), 'field_name' => 'DELIVERY_STATUS'],
            $key++ => ['name' => L('是否交VAT'), 'field_name' => 'IS_VAT'],
            $key++ => ['name' => L('授权状态'), 'field_name' => 'STATUS'],
            $key++ => ['name' => L('注册公司'), 'field_name' => 'company'],
            $key++ => ['name' => L('平台说明'), 'field_name' => 'plat_explain'],
            $key++ => ['name' => L('开店时间'), 'field_name' => 'up_shop_time'],
            $key++ => ['name' => L('店铺账号'), 'field_name' => 'up_shop_num'],
            $key++ => ['name' => L('申请邮箱'), 'field_name' => 'proposer_email'],
            $key++ => ['name' => L('申请手机号'), 'field_name' => 'proposer_phone'],
            $key++ => ['name' => L('申请人'), 'field_name' => 'proposer_by_name'],
            $key++ => ['name' => L('是否需押金或收取费用'), 'field_name' => 'is_fee'],
            $key++ => ['name' => L('备注'), 'field_name' => 'remark'],
            $key++ => ['name' => L('信用卡绑定情况'), 'field_name' => 'credit_card_explain'],
            $key++ => ['name' => L('最近确认日期'), 'field_name' => 'recently_affirm_time'],
            $key++ => ['name' => L('收款账户id'), 'field_name' => 'fin_account_bank_id'],
            $key++ => ['name' => L('收入记录公司'), 'field_name' => 'income_company_cd_val'],
            $key++ => ['name' => L('运营后台时间默认时区'), 'field_name' => 'default_timezone_cd_val'],
            $key++   => ['name' => L('交接人'), 'field_name' => 'handover_by_name'],
            $key++   => ['name' => L('店铺类型'), 'field_name' => 'store_type_cd_val'],
            $key++   => ['name' => L('实际运营店铺ID'), 'field_name' => 'reality_opt_store_id'],
            $key   => ['name' => L('合作方公司名称'), 'field_name' => 'supplier_id_val'],
        ];

        $model = new TbMsStoreModel();
        //$export->title = L('会议记录' );
        $export->fileName = L('店铺管理列表');

        $data = $model->export_data();
        //var_dump($data);die;
        $export->data = $data;

        if ($export->getError()) {
            $this->error($export->getError());
        }
        $ob_data = ob_get_clean();
        Logs($ob_data, 'ob_data', 'ob_data');
        $export->export();
    }

    public function modeInfoAdd()
    {
        try {
            $require_data = DataModel::getData(true);
            $this->modeInfoDataCheck($require_data, 'add');
            $Model = new Model();
            $res_data = $Model->table('tb_ms_logistics_mode_info')->addAll($require_data['data']);
            if (! $res_data) {
                throw  new Exception(L('未完成全部新增'), 500);
            }
            $res = DataModel::$success_return;
            $res['info'] = "新增成功";
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);
    }

    public function modeInfoEdit()
    {
        try {
            $require_data = DataModel::getData(true);
            $this->modeInfoDataCheck($require_data, 'edit');
            $Model = new Model();
            $Model->startTrans();
            $res_num = 0;
            $store_log = new StoreLogService();
            foreach ($require_data['data'] as $val) {
                $where['id'] = $val['mode_info_id'];
                $save['order_amount_range'] = $val['order_amount_range'];
                $save['recipient_country'] = $val['recipient_country'];
                $save['cast_time'] = $val['cast_time'];
                $save['cast_switch'] = $val['cast_switch'];

                // 增加操作日志
                $info = $Model->table('tb_ms_logistics_mode_info')->where($where)->find();
                $log_data = $store_log->getUpdateMessage("tb_ms_logistics_mode_info",$where,$save,3,$info['store_id']);

                $res_num += $Model->table('tb_ms_logistics_mode_info')->where($where)->save($save);

                if (!empty($log_data)){
                    $ret = $store_log->addLog($log_data);
                    if ($ret === false) {
                        throw new Exception('添加日志失败');
                    }
                }
            }
            if ($res_num != count($require_data['data'])) {
                throw  new Exception(L('未完成全部更新'), 500);
            }
            $Model->commit();
            $res = DataModel::$success_return;
            $res['info'] = "修改成功";
        } catch (Exception $exception) {
            if ($Model) $Model->rollback();
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);

    }

    public function modeInfoDel()
    {
        $require_data = DataModel::getData(true);
        $Model = new Model();
        $where_del['id'] = ['IN', array_column($require_data['data'], 'mode_info_id')];
        $res_data = $Model->table('tb_ms_logistics_mode_info')->where($where_del)->delete();
        if ($res_data) {
            $res = DataModel::$success_return;
            $res['info'] = "删除 $res_data 条";
        } else {
            $res = DataModel::$error_return;
        }
        $this->ajaxReturn($res);
    }

    private function modeInfoDataCheck($data, $type)
    {
        if (empty($data)) {
            throw new Exception(L('请求数据为空'), 300);
        }
        foreach ($data['data'] as $key => $val) {
            switch ($type) {
                case 'add':
                    $rules = [
                        'data.' . $key . '.logistics_mode_id' => 'required',
                        'data.' . $key . '.store_id'          => 'required',
//                        'data.' . $key . '.cast_time'         => 'required|integer|min:1',
//                        'data.' . $key . '.cast_switch'       => 'required',
                    ];
                    break;
                case 'edit':
                    $rules = [
                        'data.' . $key . '.mode_info_id' => 'required',
//                        'data.' . $key . '.cast_time'         => 'required|integer|min:1',
//                        'data.' . $key . '.cast_switch'       => 'required',
                    ];
                    break;
            }

        }

        if (! ValidatorModel::validate($rules, $data)) {
            throw new Exception(ValidatorModel::getMessage(), 300);
        }
    }

    public function logisticsCompany()
    {
        /*$id = $_GET['id'];
        $TbMsStore = new TbMsStoreModel();
        $supportWare = $TbMsStore->getSupportWare($id);  //获取支持的仓库
        $allWare_logistics_mode = D("Logistics/LogisticsMode")->field("ID,POSTAGE_ID")->select();
        foreach ($supportWare as $v) {
            $wareLogistics = M("ms_params", "tb_")->where("TYPE =5 AND P_ID2=" . "'" . $v . "'")->select();
            $support_postage_id = array_column($wareLogistics, 'P_ID1');
            foreach ($allWare_logistics_mode as $k1 => $v1) {
                if (!is_null($v1['POSTAGE_ID'])) {
                    if (count(array_intersect(explode(",", $v1['POSTAGE_ID']), $support_postage_id)) > 0) {
                        $simple_Logistics[] = $v1;
                        Logs($support_postage_id, '$support_postage_id');
                        Logs($v1, '$v1');
                    }
                }
            }
        }*/
        $Model = M();
        // $where['tb_ms_logistics_mode.ID'] = array('IN', array_unique(array_column($simple_Logistics, 'ID')));
        $res = $Model->table('tb_ms_logistics_mode,tb_ms_cmn_cd')
            ->field('tb_ms_logistics_mode.ID,tb_ms_logistics_mode.LOGISTICS_MODE,tb_ms_logistics_mode.LOGISTICS_CODE AS cd,tb_ms_cmn_cd.CD_VAL AS cdVal')
            // ->where($where)
            ->where('tb_ms_logistics_mode.LOGISTICS_CODE = tb_ms_cmn_cd.CD AND tb_ms_logistics_mode.IS_ENABLE = 1 AND tb_ms_logistics_mode.IS_DELETE = 0')
            ->select();
        foreach ($res as $val) {
            $temp_res[$val['cd']]['cd'] = $val['cd'];
            $temp_res[$val['cd']]['cdVal'] = $val['cdVal'];
            if (! is_array($temp_res[$val['cd']]['LOGISTICS_MODE'])) {
                $temp_res[$val['cd']]['LOGISTICS_MODE'] = [];
            }
            $temp_val['ID'] = $val['ID'];
            $temp_val['CD_VAL'] = $val['LOGISTICS_MODE'];
            $test_res[$val['cd']][] = $temp_val;
            $temp_res[$val['cd']]['LOGISTICS_MODE'][] = $temp_val;
        }
        $response = [
            'code' => 2000,
            'msg'  => 'success',
            'data' => $temp_res,
        ];
        $this->ajaxReturn($response);
    }

    public function logisticsConfig()
    {
        $this->display('logistics_config');
    }

    public function warehouseConfig()
    {
        $this->display('warehouse_config');
    }

    public function logisticsLists()
    {
        $require_data = DataModel::getData(true);
        $Model = M();
        $where = [];
        $parame = $require_data['data'];

        // 物流公司
        if (! empty($parame['logistics_company'])) {
            $where_temp = [
                'tb_ms_cmn_cd.CD_VAL' => ['like', '%' . $parame['logistics_company'] . '%'],
            ];
            $res_temp = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where_temp)->select();
            $where['b5c_logistics_cd'] = ['IN', array_column($res_temp, 'CD')];
        }
        // 物流方式
        if (! empty($parame['logistics_mode'])) {
            $where_temp = [
                'tb_ms_logistics_mode.LOGISTICS_MODE' => ['like', '%' . $parame['logistics_mode'] . '%'],
            ];
            $res_temp = $Model->table('tb_ms_logistics_mode')->field('ID')->where($where_temp)->select();
            $where['logistics_mode_id'] = ['IN', array_column($res_temp, 'ID')];
        }
        // 电商平台
        if (! empty($parame['plat_cd'])) {
            $where_temp = [
                'tb_ms_cmn_cd.CD_VAL' => ['like', '%' . $parame['plat_cd'] . '%'],
            ];
            $res_temp = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where_temp)->select();
            $where['plat_cd'] = ['IN', array_column($res_temp, 'CD')];
        }
        // 平台API标识
        if (! empty($parame['third_logistics_cd'])) {
            $where['third_logistics_cd'] = ['like', '%' . $parame['third_logistics_cd'] . '%'];
        }

        $ress = DataModel::$success_return;
        $res['page']['count'] = $Model->table('tb_ms_logistics_relation')
            ->where($where)
            ->count();
        $res['page']['this_page'] = $require_data['page']['this_page'];
        $res['page']['page_count'] = $require_data['page']['page_count'];
        $page_act = $require_data['page']['this_page'] - 1;
        if ($page_act < 0) {
            $page_act = 0;
        }
        $res['data'] = $Model->table('tb_ms_logistics_relation')
            ->where($where)
            ->order('ID')
            ->limit($page_act * $require_data['page']['page_count'], $require_data['page']['page_count'])
            ->select();
        $res['data'] = $this->cdToVal($res['data']);
        $ress['data'] = $res;
        $this->ajaxReturn($ress);
    }

    private function cdToVal($data)
    {
        $Model = M();
        if (array_column($data, 'logistics_mode_id')) {
            $where['ID'] = ['IN', array_unique(array_column($data, 'logistics_mode_id'))];
            $all_logistics_mode_arr = $Model->table('tb_ms_logistics_mode')->field('ID,LOGISTICS_MODE')->where($where)->select();
            $all_logistics_mode_keyval = array_column($all_logistics_mode_arr, 'LOGISTICS_MODE', 'ID');
        }
        if (array_column($data, 'STORE_ID')) {
            $where['ID'] = ['IN', array_unique(array_column($data, 'STORE_ID'))];
            $all_store_arr = $Model->table('tb_ms_store')->field('ID,STORE_NAME')->where($where)->select();
            $all_store_keyval = array_column($all_store_arr, 'STORE_NAME', 'ID');
        }
        $cd_arr = $Model->table('tb_ms_cmn_cd')->field('CD,CD_VAL')->select();
        $cd_keyval = array_column($cd_arr, 'CD_VAL', 'CD');
        foreach ($data as $key => $val) {
            if (array_column($data, 'logistics_mode_id')) $data[$key]['b5c_logistics_cd_nm'] = $cd_keyval[$data[$key]['b5c_logistics_cd']];
            if ($data[$key]['plat_cd']) $data[$key]['plat_cd_nm'] = $cd_keyval[$data[$key]['plat_cd']];
            if ($data[$key]['WAREHOUSE_CD']) $data[$key]['WAREHOUSE_CD_nm'] = $cd_keyval[$data[$key]['WAREHOUSE_CD']];
            if ($data[$key]['LOGISTIC_CD']) $data[$key]['LOGISTIC_CD_nm'] = $cd_keyval[$data[$key]['LOGISTIC_CD']];
            if ($data[$key]['STORE_ID']) $data[$key]['STORE_ID_nm'] = $all_store_keyval[$data[$key]['STORE_ID']];
            if ($data[$key]['TYPE']) $data[$key]['TYPE_nm'] = $this->type_cd_val[$data[$key]['TYPE']];
            if ($data[$key]['warehouse_belong']) $data[$key]['warehouse_belong_nm'] = $this->warehouse_belong_cd_val[$data[$key]['warehouse_belong']];
            if ($data[$key]['logistics_mode_id']) $data[$key]['logistics_mode_id_nm'] = $all_logistics_mode_keyval[$data[$key]['logistics_mode_id']];

        }
        return $data;
    }

    public function warehouseLists()
    {
        $require_data = DataModel::getData(true);
        $Model = M();
        $where = [];
        $require_data['data']['search_value'] = trim($require_data['data']['search_value']);
        if ($require_data['data']['search_condition'] && $require_data['data']['search_value']) {
            switch ($require_data['data']['search_condition']) {
                case 'WAREHOUSE_CD':
                    $where_temp['tb_ms_cmn_cd.CD_VAL'] = ['like', '%' . $require_data['data']['search_value'] . '%'];
                    $res_temp = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where_temp)->select();
                    $where['WAREHOUSE_CD'] = ['IN', array_column($res_temp, 'CD')];
                    break;
                case 'plat_cd':
                    $where_temp['tb_ms_cmn_cd.CD_VAL'] = ['like', '%' . $require_data['data']['search_value'] . '%'];
                    $res_temp = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where_temp)->select();
                    $where['plat_cd'] = ['IN', array_column($res_temp, 'CD')];
                    break;
                case 'STORE_ID':
                    $where_temp['tb_ms_store.STORE_NAME'] = ['like', '%' . $require_data['data']['search_value'] . '%'];
                    $res_temp = $Model->table('tb_ms_store')->field('ID')->where($where_temp)->select();
                    $where['STORE_ID'] = ['IN', array_column($res_temp, 'ID')];
                    break;
                case 'third_cd':
                    $where['third_cd'] = ['like', '%' . $require_data['data']['search_value'] . '%'];
                    break;
                case 'logistic_cd':
                    $where_temp['tb_ms_cmn_cd.CD_VAL'] = ['like', '%' . $require_data['data']['search_value'] . '%'];
                    $res_temp = $Model->table('tb_ms_cmn_cd')->field('CD')->where($where_temp)->select();
                    $where['logistic_cd'] = ['IN', array_column($res_temp, 'CD')];
                    break;
            }
        }
        $ress = DataModel::$success_return;
        $res['page']['count'] = $Model->table('tb_lgt_third_warehouse_code')
            ->where($where)
            ->count();
        $res['page']['this_page'] = $require_data['page']['this_page'];
        $res['page']['page_count'] = $require_data['page']['page_count'];
        $page_act = $require_data['page']['this_page'] - 1;
        if ($page_act < 0) {
            $page_act = 0;
        }
        $res['data'] = $Model->table('tb_lgt_third_warehouse_code')
            ->where($where)
            ->order('ID')
            ->limit($page_act * $require_data['page']['page_count'], $require_data['page']['page_count'])
            ->select();
        $res['data'] = $this->cdToVal($res['data']);
        $ress['data'] = $res;
        $this->ajaxReturn($ress);
    }

    public function logisticsConfigAdd()
    {
        $require_data = DataModel::getData(true);
        try {
            $Model = M();
            $this->logisticsConfigCheck($require_data);
            $Model->startTrans();
            $add_arr = $this->logisticsConfigFilter($require_data['data']);
            $res_data = $Model->table('tb_ms_logistics_relation')->addAll($add_arr);
            if (! $res_data) {
                $Model->rollback();
                $return_str = '保存失败,';
                $where['ID'] = ['IN', $require_data['data']['logistics_mode']];
                $all_logistics_mode_arr = $Model->table('tb_ms_logistics_mode')->field('ID,LOGISTICS_MODE')->where($where)->select();
                $all_logistics_mode_keyval = array_column($all_logistics_mode_arr, 'LOGISTICS_MODE', 'ID');
                $logistics_str = '';
                foreach ($add_arr as $val) {
                    if ($Model->table('tb_ms_logistics_relation')->where($val)->count()) {
                        $logistics_str .= $all_logistics_mode_keyval[$val['logistics_mode_id']] . ',';
                    }
                }
                $temp_str = sprintf(" %s 物流方式与 %s 标识匹配已存在", $logistics_str, $add_arr[0]['third_logistics_cd']);
                $return_str .= $temp_str;
                throw  new Exception($return_str, 500);
            }
            $Model->commit();
            $res = DataModel::$success_return;
            $res['info'] = "新增成功";
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);
    }

    private function logisticsConfigCheck($data)
    {
        if (empty($data) || empty($data['data'])) {
            throw new Exception(L('请求数据为空'), 300);
        }
        $rules = [
            'data.logistics_company'  => 'required|min:10|max:10',
            'data.logistics_mode'     => 'required|array',
            'data.plat_cd'            => 'required|min:10|max:10',
            'data.third_logistics_cd' => 'required',
        ];
        if (! ValidatorModel::validate($rules, $data)) {
            throw new Exception(ValidatorModel::getMessage(), 300);
        }
    }

    private function logisticsConfigFilter($data)
    {
        foreach ($data['logistics_mode'] as $v) {
            $data_db['logistics_trajectory_url'] = $data['logistics_trajectory_url']; //物流轨迹url
            $data_db['b5c_logistics_cd'] = $data['logistics_company'];
            $data_db['logistics_mode_id'] = $v;
            $data_db['plat_cd'] = $data['plat_cd'];
            $data_db['third_logistics_cd'] = \Application\Lib\Model\StringModel::replaceNonBreakingSpace($data['third_logistics_cd']);
            $data_db['operate_type'] = $data['operate_type'];
            $data_db['create_user'] = userName();
            $data_db['create_time'] = date('Y-m-d H:i:s', time());
            $data_db_arr[] = $data_db;
        }
        return $data_db_arr;
    }

    public function thrModeConfigDel()
    {
        $require_data = DataModel::getData(true);
        $Model = new Model();
        $where_del['id'] = ['IN', array_column($require_data['data'], 'mode_config_id')];
        $res_data = $Model->table('tb_ms_logistics_relation')->where($where_del)->delete();
        if ($res_data) {
            $res = DataModel::$success_return;
            $res['info'] = "删除 $res_data 条";
        } else {
            $res = DataModel::$error_return;
        }
        $this->ajaxReturn($res);
    }

    public function warehouseConfigAdd()
    {
        $require_data = DataModel::getData(true);
        try {
            $Model = M();
            $this->warehouseConfigCheck($require_data);
            $add_arr = $this->warehouseConfigFilter($require_data['data']);
            $res_data = $Model->table('tb_lgt_third_warehouse_code')
                ->addAll($add_arr);
            if (! $res_data) {
                $return_str = '保存失败,';
                $cd_arr = $Model->table('tb_ms_cmn_cd')
                    ->field('CD,CD_VAL')
                    ->select();
                $cd_keyval = array_column($cd_arr, 'CD_VAL', CD);
                switch ($require_data['data']['warehouse_belong']) {
                    case 1:
                        $temp_str = sprintf(" %s 仓库编码，%s 物流公司与 %s 自有仓库的匹配关系已存在",
                                            $require_data['data']['third_cd'],
                                            $cd_keyval[$require_data['data']['logistic_cd']],
                                            $cd_keyval[$require_data['data']['warehouse_cd']]);
                        break;
                    case 2:
                        $where['ID'] = ['IN', $require_data['data']['store_id']];
                        $all_store_arr = $Model->table('tb_ms_store')
                            ->field('ID,STORE_NAME')
                            ->where($where)
                            ->select();
                        $all_store_keyval = array_column($all_store_arr, 'STORE_NAME', 'ID');
                        $store_str = '';
                        foreach ($add_arr as $val) {
                            if ($Model->table('tb_lgt_third_warehouse_code')->where($val)->count()) {
                                $store_str .= $all_store_keyval[$val['STORE_ID']] . ',';
                            }
                        }
                        $temp_str = sprintf(" %s 仓库编码，%s 店铺与 %s 自有仓库的匹配关系已存在",
                                            $require_data['data']['third_cd'],
                                            $store_str,
                                            $cd_keyval[$require_data['data']['warehouse_cd']]);
                        break;
                }
                $return_str .= $temp_str;
                throw  new Exception($return_str, 500);
            }
            $res = DataModel::$success_return;
            $res['info'] = "新增成功";
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
            $res['code'] = $exception->getCode();
        }
        $this->ajaxReturn($res);
    }

    private function warehouseConfigCheck($data)
    {
        if (empty($data) || empty($data['data'])) {
            throw new Exception(L('请求数据为空'), 300);
        }
        $rules = [
            'data.warehouse_cd'     => 'required|min:10|max:10',
            'data.type'             => 'required|numeric',
            'data.third_cd'         => 'required',
            'data.warehouse_belong' => 'required',
        ];
        switch ($data['data']['warehouse_belong']) {
            case 1:
                $rules['data.logistic_cd'] = 'required|min:10|max:10';
                break;
            case 2:
                $rules['data.plat_cd'] = 'required|min:10|max:10';
                $rules['data.store_id'] = 'required|array';
                break;
            case 3:
                $rules['data.logistic_cd'] = 'required|min:10|max:10';
                $rules['data.plat_cd'] = 'required|min:10|max:10';
                $rules['data.store_id'] = 'required|array';
                break;
        }
        if (! ValidatorModel::validate($rules, $data)) {
            throw new Exception(ValidatorModel::getMessage(), 300);
        }
    }

    private function warehouseConfigFilter($data)
    {
        switch ($data['warehouse_belong']) {
            case 1:
                $data_db['WAREHOUSE_CD'] = $data['warehouse_cd'];
                $data_db['TYPE'] = $data['type'];
                $data_db['THIRD_CD'] = \Application\Lib\Model\StringModel::replaceNonBreakingSpace($data['third_cd']);
                $data_db['warehouse_belong'] = $data['warehouse_belong'];
                $data_db['plat_cd'] = $data['plat_cd'];
                $data_db['LOGISTIC_CD'] = $data['logistic_cd'];
                $data_db['STORE_ID'] = -1;
                $data_db['CREATE_TIME'] = date('Y-m-d H:i:s');
                $data_db['CREATE_USER'] = session('m_loginname');
                if ($data['operate_type']) $data_db['operate_type'] = $data['operate_type'];
                $data_db_arr[] = $data_db;
                break;
            case 2:
            case 3:
                foreach ($data['store_id'] as $v) {
                    $data_db['WAREHOUSE_CD'] = $data['warehouse_cd'];
                    $data_db['TYPE'] = $data['type'];
                    $data_db['THIRD_CD'] = \Application\Lib\Model\StringModel::replaceNonBreakingSpace($data['third_cd']);
                    $data_db['warehouse_belong'] = $data['warehouse_belong'];
                    $data_db['plat_cd'] = $data['plat_cd'];
                    $data_db['LOGISTIC_CD'] = $data['logistic_cd'];
                    $data_db['STORE_ID'] = $v;
                    $data_db['CREATE_TIME'] = date('Y-m-d H:i:s');
                    $data_db['CREATE_USER'] = session('m_loginname');
                    if ($data['operate_type']) $data_db['operate_type'] = $data['operate_type'];
                    $data_db_arr[] = $data_db;
                }
                break;
        }
        return $data_db_arr;
    }

    public function warehouseConfigDel()
    {
        $require_data = DataModel::getData(true);
        $Model = new Model();
        $where_del['id'] = ['IN', array_column($require_data['data'], 'warehouse_info_id')];
        $res_data = $Model->table('tb_lgt_third_warehouse_code')->where($where_del)->delete();
        if ($res_data) {
            $res = DataModel::$success_return;
            $res['info'] = "删除 $res_data 条";
        } else {
            $res = DataModel::$error_return;
            $res['info'] = "待删除数据异常";
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param null $data
     */
    public function pullSingle($data = null)
    {
       
        try {
            if (empty($data)) {
                $data = DataModel::getDataToArr();
            }
           
            $this->checkPullSingle($data);
            $sheet = 12;
            $PullOrderQueueService = new PullOrderQueueService();
            $pullSingle = $PullOrderQueueService->pushShopOneDayQueue($data, $sheet);
            #进行日志记录
            $logData =  [
                "store_id" => $data['stores'],
                "module" => 4,
                "field_name" => "拉单",
                "front_value" =>  '',
                "later_value" => '时间区间：'.$data['startDate'].' - '. $data['endDate'],
                "update_by" => userName(),
                "update_at" => date('Y-m-d H:i:s')
            ];
            $store_log = new StoreLogService();
            $store_log->addLog([$logData]);
            $store_name = M('ms_store', 'tb_')->where(['ID' => $data['stores']])->getField('STORE_NAME');
            $content = '操作人 ' . userName() . ' 执行店铺 ' . $store_name . ' 拉单，时间范围 ' . $data['startDate'] . ' - ' . $data['endDate'];
            $this->sendWxByWid($content);
            $this->ajaxSuccess($pullSingle, $pullSingle);
        } catch (Exception $exception) {
            $return_data = json_decode($this->error_data, JSON_UNESCAPED_UNICODE);
            $this->ajaxError($return_data, $exception->getMessage(), $exception->getCode());
        }
    }
    private function sendWxByWid($content){
        
        $user_info = self::$store_push_order_wx_wid;
        foreach ($user_info as $key => $value) {
            Logs(json_encode(['wid' => $value, 'content' => $content]), __FUNCTION__ . '----send data', 'storePushOrderSendWx');
            $res = ApiModel::WorkWxSendMessage($value, $content);
            #记录日志
            Logs(json_encode($res), __FUNCTION__ . '----send res', 'storePushOrderSendWx');
        }

    }
    private function checkPullSingle($data)
    {
        if (empty($data)) {
            throw new Exception(L('请求数据为空'));
        }
        $rules = [
            'stores'    => 'required|numeric',
            'startDate' => 'required|date',
            'endDate'   => 'required|date',
        ];
        $custom_attributes = [
            'stores'    => '店铺 ID',
            'startDate' => '开始时间',
            'endDate'   => '结束时间',
        ];
        if (! ValidatorModel::validate($rules, $data, $custom_attributes)) {
            $this->error_data = ValidatorModel::getMessage();
            throw new Exception(L('请求参数错误'), 300);
        }
//        if (strtotime($data['startDate']) < strtotime('-3 month')) {
//            throw new Exception(L('开始时间不能超过 3 个月'));
//        }
        $end_date = new DateTime($data['endDate']);
        /*if ($end_date->diff((new DateTime($data['startDate'])))->days > 1) {
            throw new Exception(L('时间区间不能大于 1 天'));
        }*/

    }

    /**
     * 获取第三方配置详情
     */
    public function logisticsConfigInfo()
    {
        $require_data = DataModel::getData(true);
        $parame = $require_data['data'];
        if (! isset($parame['logistics_id']) || empty($parame['logistics_id'])) {
            $res = DataModel::$error_return;
            $res['info'] = "参数有误";
            $this->ajaxReturn($res);
        }
        $res_data = M("logistics_relation", 'tb_ms_')
            ->field('b5c_logistics_cd,third_logistics_cd,plat_cd,logistics_trajectory_url,operate_type,logistics_mode_id,LOGISTICS_MODE as logistics_mode_name')
            ->join('tb_ms_logistics_mode ON tb_ms_logistics_mode.ID=tb_ms_logistics_relation.logistics_mode_id')
            ->where(['tb_ms_logistics_relation.id' => $parame['logistics_id']])->find();
        if (empty($res_data)) {
            $res = DataModel::$error_return;
            $res['info'] = "数据不存在";
            $this->ajaxReturn($res);
        }
        //        $res_data = array_change_key_case($res_data,CASE_LOWER);
        $res_data = CodeModel::autoCodeOneVal($res_data, ['b5c_logistics_cd', 'plat_cd']);
        $this->ajaxReturn($res_data);
    }

    /**
     * 获取ERP自定义走仓规则配置
     */
    public function CustomWarehouseConfig()
    {
        try {
            $request_data = DataModel::getData(true);
            $page['this_page'] = $request_data['this_page'];
            $page['page_count'] = $request_data['page_count'];
            $model = new CustomWarehouseConfigModel();
            $where = $model->searchWhere($request_data);
            $res_data = $model->getData($where, $page);
            $total = $model->getCount($where);
            $res_data = CodeModel::autoCodeTwoVal($res_data, ['warehouse_code', 'logistics_company_code', 'face_order_code']);
            $return['data'] = $res_data;
            $return['totalElements'] = $total + 0;//转化为数字
            $return['query'] = $request_data;
            $this->ajaxSuccess($return, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * 获取ERP自定义走仓规则配置详情
     */
    public function CustomWarehouseConfigDetail()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res_data = $model->getDetail($request_data);
            $res_data = CodeModel::autoCodeOneVal($res_data, ['warehouse_code', 'logistics_company_code', 'face_order_code']);
            $this->ajaxSuccess($res_data, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * 新增ERP自定义走仓规则配置
     */
    public function customWarehouseCreate()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->create($request_data);
            if ($res) {
                $this->ajaxSuccess($res, '规则新增成功');
            } else {
                $this->ajaxError($res,'规则新增失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * 验证店铺平台 是否为GP 是否为速卖通 是否为ebay
     */
    public function checkStorePlatCd()
    {
        try {
            $request_data = DataModel::getData(true);
            $Model = new Model();
            $store = $Model->table('tb_ms_store')->where(['ID' => $request_data['id']])->find();
            //获取GP平台
            $gp_plat_cd = array_column(CodeModel::getSiteCodeArr('N002620800'), 'CD');
            $cdiscount_plat_cd = array_column(CodeModel::getSiteCodeArr('N002621700'), 'CD');
            $aliexpress_plat_cd = array_column(CodeModel::getSiteCodeArr('N002621600'), 'CD');
            #新增ebay平台判断   10976 ERP自定义走仓推荐_新增条件2_ebay 
            $ebay_plat_cd = array_column(CodeModel::getSiteCodeArr('N002620600'), 'CD');

            
          
            //速卖通 N000832300
            $res['is_gp'] = in_array($store['PLAT_CD'], $gp_plat_cd) ? 1 : -1;
            $res['is_cdiscount'] = in_array($store['PLAT_CD'], $cdiscount_plat_cd) ? 1 : -1;
            $res['is_aliexpress'] = in_array($store['PLAT_CD'], $aliexpress_plat_cd) ? 1 : -1;
            $res['is_ebay'] = in_array($store['PLAT_CD'], $ebay_plat_cd) ? 1 : -1;
            $res['plat_cd'] = $store['PLAT_CD'];
            $this->ajaxSuccess($res);
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * 获取运输方式（速卖通）
     */
    public function getShippingType()
    {
        try {$request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->getShippingType($request_data['id']);
            $this->ajaxSuccess($res);
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    public function getSort()
    {
        $model = new CustomWarehouseConfigModel();
        $res = $model->getSort();
        $this->ajaxSuccess($res, 'success');
    }

    //手动排序
    public function manualSort()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $model->startTrans();
            $res = $model->manualSort($request_data, $model);
            if ($res) {
                $model->commit();
                $this->ajaxSuccess($res, '规则手动排序成功');
            } else {
                $model->rollback();
                $this->ajaxError($res,'规则手动排序失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception, $model);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * 新增ERP自定义走仓规则配置
     */
    public function customWarehouseEdit()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->update($request_data);
            if (false !== $res) {
                $this->ajaxSuccess($res, '编辑成功');
            } else {
                $this->ajaxError($res,'编辑失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则配置启用、停用、删除
     */
    public function customConfigStatusEdit()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->updateStatus($request_data);
            if (false !== $res) {
                $this->ajaxSuccess($res, '规则状态更新成功');
            } else {
                $this->ajaxError($res,'规则状态更新失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则配置复制
     */
    public function customConfigCopy()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->copy($request_data);
            if ($res) {
                $this->ajaxSuccess($res, '规则复制成功');
            } else {
                $this->ajaxError($res,'规则复制失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则配置整店复制
     */
    public function customConfigCopyByStore()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->copyByStore($request_data);
            if ($res) {
                $this->ajaxSuccess($res, '规则整店复制成功');
            } else {
                $this->ajaxError($res,'规则整店复制失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则配置复制到其他店铺
     */
    public function configCopyToOtherStore()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->configCopyToOtherStore($request_data);
            if ($res) {
                $this->ajaxSuccess($res, '规则整店复制成功');
            } else {
                $this->ajaxError($res,'规则整店复制失败');
            }
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则-根据店铺获取物流公司
     */
    public function logisticsCompanyByStore()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->logisticsCompanyByStore($request_data);
            $ware_houses = null;
            if ($res) {
                $Model = M();
                //获取当前物流方式配置的出库
                $ware_house = array_unique(explode(',', implode(',', array_column($res, 'WARE_HOUSE'))));
                //从store表获取不支持的仓库
                $unSupportWare = $Model->table('tb_ms_store')->field("WAREHOUSES")->where('ID=' . $request_data['store_id'])->find();
                if (!empty($unSupportWare['WAREHOUSES'])) {
                    $unSupportWareArr = explode(",", $unSupportWare['WAREHOUSES']);
                    $supportWare = array_diff($ware_house, $unSupportWareArr);
                }
                $ware_house = !is_null($supportWare) ? $supportWare : $ware_house;
                $ware_houses = $Model->table('tb_ms_cmn_cd')
                    ->field('CD,CD_VAL')
                    ->where(['CD' => ['in', $ware_house]])
                    ->select();
            }
            $return['company'] = $res;
            $return['ware_house'] = $ware_houses;
            $this->ajaxSuccess($return, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则-根据店铺物流公司获取物流公司
     */
    public function logisticsModeByCompanyAndStore()
    {
        try {
            $request_data = DataModel::getData(true);
            $model = new CustomWarehouseConfigModel();
            $res = $model->logisticsModeByCompanyAndStore($request_data);
            $this->ajaxSuccess($res, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    /**
     * ERP自定义走仓规则-国家接口
     * @return mixed
     */
    public function country()
    {
        try {
            $request_data = DataModel::getData(true);
            $res = BaseModel::getAreaByWhere($request_data);
            $this->ajaxSuccess($res, 'success');
        } catch (exception $exception) {
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
    }

    public function logisticsConfigSave()
    {
        $require_data = DataModel::getData(true);
        $parame = $require_data['data'];
        if (! isset($parame['logistics_id']) || empty($parame['logistics_id'])) {
            $res = DataModel::$error_return;
            $res['info'] = "参数有误";
            $this->ajaxReturn($res);
        }
        $res_data = M("logistics_relation", 'tb_ms_')->where(['id' => $parame['logistics_id']])->find();
        if (empty($res_data)) {
            $res = DataModel::$error_return;
            $res['info'] = "数据不存在";
            $this->ajaxReturn($res);
        }
        $save_data = [
            'plat_cd'                  => $parame['plat_cd'],
            'third_logistics_cd'       => $parame['third_logistics_cd'],
            'logistics_trajectory_url' => $parame['logistics_trajectory_url'],
            'operate_type'             => $parame['operate_type'],
            'update_user'              => userName(),
            'update_time'              => date('Y-m-d H:i:s', time()),
        ];
        $res_data = M("logistics_relation", 'tb_ms_')->where(['id' => $parame['logistics_id']])->save($save_data);
        if ($res_data === false) {
            $res = DataModel::$error_return;
            $res['info'] = "编辑失败";
            $this->ajaxReturn($res);
        }
        $res = DataModel::$success_return;
        $this->ajaxReturn($res);
    }

    public function dockingList()
    {
        $Model = new Model();
        $gp_cds = WhereModel::arrayToInString(CodeModel::getGpPlatCds());
        $stores_action = $Model->table('tb_ms_store')
            ->field('tb_ms_store.PLAT_CD AS plat_cd')
            ->where("( `DELETE_FLAG` = '0'  AND `STORE_STATUS` = '0' AND  `ORDER_SWITCH` = 1 ) OR (PLAT_CD IN ($gp_cds))", null, true)
            ->group('PLAT_CD')
            ->select();
        $stores_confs = $Model->table('tb_ms_store')
            ->field('tb_ms_store.PLAT_CD AS plat_cd,APPKES')
            ->where("( APPKES != '{}' AND APPKES IS NOT NULL AND APPKES != '' ) OR (PLAT_CD IN ($gp_cds))", null, true)
            ->group('PLAT_CD')
            ->select();
        $stores_on = $stores_off = [];
        $plat_cd_codes = CodeModel::getCodeArr(['N00083']);
        $stores_action = array_column($stores_action, 'plat_cd');
        $stores_conf_key = array_column($stores_confs, 'plat_cd');
        $stores_conf_key_map = array_column($stores_confs, 'APPKES', 'plat_cd');
        foreach ($plat_cd_codes as $cd_code) {
            if (in_array($cd_code['CD'], $stores_action)) {
                $stores_on[] = $cd_code['CD_VAL'];
            } elseif (isset($stores_conf_key_map[$cd_code['CD']]) && DataModel::isJson($stores_conf_key_map[$cd_code['CD']]) && 1 < json_decode($stores_conf_key_map[$cd_code['CD']], true)['accessToken']) {
                $stores_conf[] = $cd_code['CD_VAL'];
            } else {
                $stores_off[] = $cd_code['CD_VAL'];
            }
        }
        $this->assign('stores_on', $stores_on);
        $this->assign('stores_off', $stores_off);
        $this->assign('stores_conf', $stores_conf);
        $this->display('docking_list');
    }

    //店铺配置-物流配置-编辑详情
    public function modeInfoDetail()
    {
        $model = new Model();
        $require_data = DataModel::getData(true);
        $res_data =$model->table('tb_ms_logistics_mode_info info')
            ->field([
                "info.*",
                "mode.LOGISTICS_CODE as logistics_company",
                "mode.LOGISTICS_MODE as logistics_mode"
            ])
            ->join('tb_ms_logistics_mode mode on mode.ID = info.logistics_mode_id')
            ->where(['info.id' => $require_data['logistics_mode_info_id']])
            ->find();
        $res_data = CodeModel::autoCodeOneVal($res_data, ['logistics_company']);
        $res = DataModel::$success_return;
        $res['data'] = $res_data;
        $this->ajaxReturn($res);
    }
}
