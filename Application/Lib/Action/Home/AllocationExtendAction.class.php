<?php
/**
 * 调拨
 * User: b5m
 * Date: 2017/8/29
 * Time: 16:05
 */

/**
 * Class AllocationExtendAction
 */
class AllocationExtendAction extends BaseAction
{
    const ALLO_STATE_ING = 1; // 流程进行中
    const ALLO_STATE_OFF = 2; // 流程结束
    public $whiteList = [
        'agree',
        'disagree',
        'allocationTimeoutSendMail'
    ];
    private $allo_type = 0;
    private $unset_keys = [
        'N001970601', 'N001970602', 'N001970603'
    ];

    private $allo_logistics_state = [
        '1' => '订舱安排中',
        '2' => '为已出库，待离港',
        '3' => '航行中',
        '4' => '清关中',
        '5' => '清关完成，待送仓',
        '6' => '已送仓，待上架',
        '7' => '上架中',
        '8' => '上架完成'
    ];

    /**
     * @param null $allo_type
     *
     * @return bool|void
     */
    public function _initialize()
    {
        if ($_REQUEST['allo_type']) {
            $this->allo_type = $_REQUEST['allo_type'];
        }
        import('ORG.Util.Page');
        if (strtolower($_SERVER ['CONTENT_TYPE']) == 'application/json') {
            $json_str = file_get_contents('php://input');
            $_POST = json_decode($json_str, true);
        }
    }

    /**
     * 调拨超时邮件发送服务
     */
    public function allocationTimeoutSendMail()
    {
        $model = new TbWmsAlloModel();

        $conditions ['create_time'] = ['gt', '2018-08-01 00:00:00'];
        $conditions ['state'] = ['eq', 'N001970300'];
        $conditions ['_string'] = 'datediff(now(), tb_wms_allo.estimate_arrive_date) > 0';

        $ret = $model
            ->field([
                'tb_wms_allo.allo_no as allocationNo', // 调拨单号
                'tb_wms_allo.allo_out_warehouse as allocationOutWarehouse', // 调出仓库
                'tb_wms_allo.allo_in_warehouse as allocationInWarehouse',// 调入仓库
                'tb_wms_allo.allo_out_team as allocationOutTeam',// 调出团队
                'tb_wms_allo.allo_in_team as allocationInTeam',// 调入团队
                't2.M_NAME as initiator',// 调拨发起人
                't3.M_NAME as confirmOutDeliveryPerson',// 出库确认人
                'tb_wms_allo.estimate_arrive_date as estimateArriveDate', // 预计到日期
                'datediff(now(), tb_wms_allo.estimate_arrive_date) as timeoutNum'// 超期天数
            ])
            ->join('LEFT JOIN bbm_admin t2 ON tb_wms_allo.create_user = t2.M_ID')
            ->join('LEFT JOIN bbm_admin t3 ON tb_wms_allo.update_user = t3.M_ID')
            ->where($conditions)
            ->select();

        return $ret;
    }

    /**
     * 库存调拨
     * 每个sku有多少个销售团队就展示多少条数据
     */
    public function index()
    {
        $model = new TbWmsAlloModel();
        $params = ZUtils::filterBlank($this->getParams());
        $_GET ['p'] = $_POST['p'] = $params['p'];
        $page_i = $_POST['page_i'] ? $_POST['page_i'] : 20;
        $conditions = $model->search($params);
        $subQuery = $model->field('t2.id,
			t2.allo_no,
			t2.allo_type,
			t2.allo_in_team,
			t2.allo_in_warehouse,
			t2.allo_out_team,
			t2.allo_out_warehouse,
			t2.create_time,
			t2.update_time,
			t2.transfer_type,
			t2.state, (select GROUP_CONCAT(sku_id) from tb_wms_allo_child t1 where t1.allo_id = t2.id) as sku')
            ->table('tb_wms_allo t2')
            ->buildSql();
        $count = $model->table($subQuery . ' t3')
            ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=t3.allo_in_warehouse')
            ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=t3.allo_out_warehouse')
            ->where($conditions)
            ->count();
        $page = new Page($count, $page_i);
        $subQuery = $model->field('t2.*, (select GROUP_CONCAT(sku_id) from tb_wms_allo_child t1 where t1.allo_id = t2.id) as sku')
            ->table('tb_wms_allo t2')
            ->buildSql();
        $ret = $model->table($subQuery . ' t3')
            ->field([
                't3.*',
                'ba.M_NAME as create_user_nm',
                'ba2.M_NAME as update_user_nm'
            ])
            ->where($conditions)
            ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=t3.allo_in_warehouse')
            ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=t3.allo_out_warehouse')
            ->join('left join bbm_admin ba on ba.m_id=t3.create_user')
            ->join('left join bbm_admin ba2 on ba2.m_id=t3.update_user')
            ->limit($page->firstRow, $page->listRows)
            ->order('id desc')
            ->select();

        $show = $page->ajax_show('flip');
        if (IS_POST)
            $this->AjaxReturn(['ret' => array_values($ret), 'page' => $show, 'count' => $count], 'success', 1);

        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouseLock());
        $this->assignJson('count', $count);
        $this->assignJson('teams', BaseModel::saleTeamCd());
        $this->assignJson('ret', $ret);
        $this->assignJson('pages', $show);
        $this->assignJson('params', $params);
        $this->assignJson('alloType', BaseModel::alloType());
        $states = BaseModel::auditAlloState();
        if (0 === $this->allo_type) {
            foreach ($this->unset_keys as $unset_key) {
                unset($states[$unset_key]);
            }
        }
        $this->assignJson('states', $states);
        $template_file = 'AllocationExtend/index';
        $this->display($template_file);
    }

    public function newIndex($is_export = false)
    {
        $model = new TbWmsAlloModel();
        $params = ZUtils::filterBlank($this->getParams());
        $_GET ['p'] = $_POST ['p'] = $params['p'];
        $page_i = $_POST['page_i'] ? $_POST['page_i'] : 20;
        $conditions = $model->search($params, true);
        $conditions['t3.deleted_at'] = ['EXP', 'IS NULL'];
        // $conditions['t3.allo_no'] = 'DB202002280010';


        $subQuery = $model->field('t2.*,
         (select GROUP_CONCAT(sku_id) from tb_wms_allo_child t1 where t1.allo_id = t2.id group by t1.allo_id) as sku')
            ->table('tb_wms_allo t2')
            ->buildSql();
        $count_sub_query = $model->table($subQuery . ' t3')
            ->field("COUNT(*) AS tp_count")
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = t3.allo_in_team')
            ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=t3.allo_in_warehouse')
            ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=t3.allo_out_warehouse')
            ->join('left join tb_wms_allo_new_status  on tb_wms_allo_new_status.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks_node AS tws_node ON tws_node.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_process AS tws_process ON tws_process.id = t3.process_id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks AS tws_out_stock ON tws_out_stock.allo_id = t3.id')
            ->join('left join bbm_admin ba on ba.m_id=t3.create_user')
            ->join('left join bbm_admin ba2 on ba2.m_id=t3.update_user')
            ->where($conditions)
            ->group("t3.id")
            ->select(false);
        $count_result = M()->field("COUNT(*) AS tp_count")->table("{$count_sub_query} as tmp")->find();


        $count = $count_result ? $count_result['tp_count'] : 0;
        $page = new Page($count, $page_i);
        $subQuery = $model->field('t2.*,
         (select GROUP_CONCAT(sku_id) from tb_wms_allo_child t1 where t1.allo_id = t2.id group by t1.allo_id) as sku')
            ->table('tb_wms_allo t2')
            ->buildSql();
        if ($is_export) {
            #导出不能大于2000
            $page->firstRow = 0;
            $page->listRows = 2000;


        }
        $query = $model->table($subQuery . ' t3')
            ->field([
                't3.*',
                'ba.M_NAME as create_user_nm',
                'ba2.M_NAME as update_user_nm',
                'tws_process.small_team_cd'
            ])
            ->where($conditions)
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = t3.allo_in_team')
            ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=t3.allo_in_warehouse')
            ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=t3.allo_out_warehouse')
            ->join('left join tb_wms_allo_new_status  on tb_wms_allo_new_status.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks_node AS tws_node ON tws_node.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_process AS tws_process ON tws_process.id = t3.process_id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks AS tws_out_stock ON tws_out_stock.allo_id = t3.id')
            ->join('left join bbm_admin ba on ba.m_id=t3.create_user')
            ->join('left join bbm_admin ba2 on ba2.m_id=t3.update_user')
            ->limit($page->firstRow, $page->listRows)
            ->group("t3.id")
            ->order('id desc');

        if ($is_export) {
            #导出不能大于2000
            $page->firstRow = 0;
            $page->listRows = 2000;
            $exportQuery = $query->buildSql();
            $exportData = $model->table($exportQuery . ' allo')
                ->field([
                    'allo.allo_no',
                    'allo.small_team_cd',
                    'allo.id as allo_real_id',
                    'allo.allo_in_team',
                    'allo.allo_in_warehouse',
                    'allo.allo_out_warehouse',
                    'allo.create_user_nm',
                    'demand_allo_num',
                    'tb_crm_sp_supplier.SP_NAME as tb_crm_sp_supplier_value',
                    'info.*',
                    'info.id as info_id',
                    '(guds.this_out_authentic_products+guds.this_out_defective_products ) as out_num',
                    'guds.out_stocks_id',
                    'guds.sku_id',
                    'IFNULL(sum((in_guds.this_in_authentic_products+in_guds.this_in_defective_products )),0) as in_num',


                ])
                ->join('LEFT JOIN tb_wms_allo_new_out_stocks AS info ON allo.id = info.allo_id')
                ->join('LEFT JOIN tb_wms_allo_new_out_stock_guds as guds on guds.out_stocks_id = info.id')
                ->join('tb_wms_allo_new_in_stock_guds as in_guds on in_guds.out_stock_id = guds.out_stocks_id and in_guds.sku_id = guds.sku_id')
                ->join('tb_wms_allo_child as child on child.allo_id = allo.id and child.sku_id = guds.sku_id')
                ->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.ID = info.transport_company_id')
                ->group("allo.id,guds.id,guds.sku_id")
                ->order('allo.id desc')
                ->select();

            #单独查询时间
            $allo_id = array_unique(array_column($exportData, 'allo_real_id'));
            $nodeTime = M('wms_allo_new_out_stocks_node', 'tb_')
                ->field([

                    'GROUP_CONCAT(concat(type,"#",node_plan)) as nodePlanTime',//报价时间
                    'GROUP_CONCAT(concat(type,"#",scheduled_date)) as scheduledDate',//预计时间
                    'GROUP_CONCAT(concat(type,"#",node_operate)) as nodeTime',//实际完成时间
                    'data_type',
                    'concat(allo_id,"-",out_stock_id) as allo_stock_key'])
                ->where(['allo_id' => ['in', $allo_id]])->group('allo_id,out_stock_id')
                ->select();
            $nodeTime = array_column($nodeTime, null, 'allo_stock_key');


            $exportData = SkuModel::getInfo(
                $exportData,
                'sku_id',
                ['spu_name', 'attributes', 'image_url', 'product_sku']
            );

            #处理cd数据
            $changeCd = [
                'allo_in_team',
                'allo_in_warehouse',
                'allo_out_warehouse',
                'planned_transportation_channel_cd',
                'customs_clear',
                'send_warehouse_way',
                'cabinet_type',
                'small_team_cd'

            ];
            $exportData = CodeModel::autoCodeTwoVal($exportData, $changeCd);

            $write = [
                'list_data1' => [],
                'list_data2' => []
            ];
            foreach ($exportData as $value) {
                if ($nodeTime[$value['allo_real_id'] . '-' . $value['info_id']]) {

                    $value['nodeTime'] = explode(',', $nodeTime[$value['allo_real_id'] . '-' . $value['info_id']]['nodeTime']);
                    $value['nodePlanTime'] = explode(',', $nodeTime[$value['allo_real_id'] . '-' . $value['info_id']]['nodePlanTime']);
                    $value['scheduledDate'] = explode(',', $nodeTime[$value['allo_real_id'] . '-' . $value['info_id']]['scheduledDate']);

                    $tmpNodeSystem = [];
                    foreach ($value['nodeTime'] as $nodeTimeValue) {
                        $tmp = explode('#', $nodeTimeValue);
                        $value['nodeTime' . $tmp[0]] = $tmp[1];
                        $tmpNodeSystem[$tmp[0] - 1]['node_operate'] = $tmp[1];
                    }
                    foreach ($value['nodePlanTime'] as $nodeTimeValue) {
                        $tmp = explode('#', $nodeTimeValue);
                        $value['nodePlanTime' . $tmp[0]] = $tmp[1];
                        $tmpNodeSystem[$tmp[0] - 1]['node_plan'] = $tmp[1];
                    }
                    #计算出系统预估时间
                    if ($nodeTime[$value['allo_real_id'] . '-' . $value['info_id']]['data_type'] == 1) {
                        #新数据
                        foreach ($value['scheduledDate'] as $nodeTimeValue) {

                            $tmp = explode('#', $nodeTimeValue);
                            $value['nodeSystemPlan' . $tmp[0]] = $tmp[1];
                        }

                        if (!empty($value['nodeSystemPlan3'])) {
                            $value['nodeSystemPlan1'] = $tmpNodeSystem[0]['node_plan'];
                            $value['nodeSystemPlan2'] = $tmpNodeSystem[1]['node_plan'];
                        }

                    } else {

                        foreach ($tmpNodeSystem as $key => $v1) {
                            if ($key == 0) {
                                $value['nodeSystemPlan1'] = $v1['node_plan'];
                            }
                            if ($key > 0 && $tmpNodeSystem[$key - 1]['node_operate']) {
                                $tmpNodeSystem[$key]['node_system_plan'] = $value['nodeSystemPlan' . ($key + 1)] = date('Y-m-d', (strtotime($tmpNodeSystem[$key - 1]['node_operate']) + (strtotime($tmpNodeSystem[$key]['node_plan']) - strtotime($tmpNodeSystem[$key - 1]['node_plan']))));

                            }

                            if ($key > 0 && empty($tmpNodeSystem[$key - 1]['node_operate']) && !empty($tmpNodeSystem[$key - 1]['node_system_plan'])) {
                                $tmpNodeSystem[$key]['node_system_plan'] = $value['nodeSystemPlan' . ($key + 1)] = date('Y-m-d', (strtotime($tmpNodeSystem[$key - 1]['node_system_plan']) + (strtotime($tmpNodeSystem[$key]['node_plan']) - strtotime($tmpNodeSystem[$key - 1]['node_plan']))));

                            }
                        }
                    }


                }
                if ($value['out_plate_number_val']) {
                    $value['out_plate_number_val'] = $value['out_plate_number_type'] == 2 ? '散装' . $value['out_plate_number_val'] . '箱' : '打板';
                }
                if ($value['logistics_state']) {
                    $value['logistics_state'] = $this->allo_logistics_state[$value['logistics_state']];
                }
                unset($value['nodeTime']);
                unset($value['nodePlanTime']);
                if (empty($value['planned_transportation_channel_cd']) || in_array($value['planned_transportation_channel_cd'], ['N002820002', 'N002820005'])) {
                    array_push($write['list_data1'], $value);
                } else {
                    array_push($write['list_data2'], $value);
                }
            }

            $exportExcelObj = new AlloExport();
            $exportExcelObj->setData($write)
                ->download();

            exit;
        }


        $ret = $query
            ->select();


        $show = $page->ajax_show('flip');
        if (IS_POST)
            $this->AjaxReturn(['ret' => array_values($ret), 'page' => $show, 'count' => $count], 'success', 1);

        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouseLock());
        $this->assignJson('count', $count);
        $this->assignJson('teams', BaseModel::saleTeamCd());
        $this->assignJson('ret', $ret);
        $this->assignJson('pages', $show);
        $this->assignJson('params', $params);
        $this->assignJson('alloType', BaseModel::alloType());
        $this->assignJson('allo_out_status_codes', CodeModel::getAlloOutStatusCode());
        $this->assignJson('allo_in_status_codes', CodeModel::getAlloInStatusCode());
        $this->assignJson('track_node_types', AllocationExtendNewService::$out_stocks_node_type_map);

        $states = BaseModel::auditAlloState();
        if (0 === $this->allo_type) {
            foreach ($this->unset_keys as $unset_key) {
                unset($states[$unset_key]);
            }
        }
        $this->assignJson('states', $states);
        $template_file = 'AllocationExtendNew/index';
        $this->display($template_file);
    }

    public function checkoutExport()
    {

        $model = new TbWmsAlloModel();
        $params = ZUtils::filterBlank($this->getParams());
        $_GET['p'] = $_POST['p'] = $params['p'];
        $conditions = $model->search($params, true);
        $conditions['t3.deleted_at'] = ['EXP', 'IS NULL'];
        // $conditions['t3.allo_no'] = 'DB202002280010';


        $subQuery = $model->field('t2.*,
         (select GROUP_CONCAT(sku_id) from tb_wms_allo_child t1 where t1.allo_id = t2.id group by t1.allo_id) as sku')
            ->table('tb_wms_allo t2')
            ->buildSql();
        $count_sub_query = $model->table($subQuery . ' t3')
            ->field("COUNT(*) AS tp_count")
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = t3.allo_in_team')
            ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=t3.allo_in_warehouse')
            ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=t3.allo_out_warehouse')
            ->join('left join tb_wms_allo_new_status  on tb_wms_allo_new_status.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks_node AS tws_node ON tws_node.allo_id = t3.id')
            ->join('LEFT JOIN tb_wms_allo_process AS tws_process ON tws_process.id = t3.process_id')
            ->join('LEFT JOIN tb_wms_allo_new_out_stocks AS tws_out_stock ON tws_out_stock.allo_id = t3.id')
            ->join('left join bbm_admin ba on ba.m_id=t3.create_user')
            ->join('left join bbm_admin ba2 on ba2.m_id=t3.update_user')
            ->where($conditions)
            ->group("t3.id")
            ->select(false);
        $count_result = M()->field("COUNT(*) AS tp_count")->table("{$count_sub_query} as tmp")->find();
        if ($count_result['tp_count'] > 2000) {
            $this->ajaxError([], L('当前筛选调拨单超过2000条，不支持导出'));
        }
        $this->ajaxSuccess();
    }

    /**
     * 获取销售团队领导的email
     *
     * @param string $saleCode
     *
     * @return string
     */
    public function getSaleTeamEmail($saleCode)
    {
        $model = new Model();
        $ret = $model->table('tb_ms_cmn_cd')->field('CD as cd, ETC as etc')->where(['CD' => ['eq', $saleCode]])->find();

        return $ret;
    }

    /**
     * 查看详情
     */
    public function show()
    {
        $params = $this->getParams();
        $model = new TbWmsAlloModel();
        $process = $model->where('id = %d', [$params ['id']])->find();
        if ($process) {
            $child = new TbWmsAlloChildModel();
            $fields = [
                'tb_wms_allo_child.sku_id',
                'SUM(tb_wms_allo_child.demand_allo_num) as demand_allo_num',
                'SUM(tb_wms_allo_child.actual_outgoing_num) as actual_outgoing_num',
                'tb_wms_allo_child.actual_demand_diff_reason',
                'SUM(tb_wms_allo_child.actual_storage_num) as actual_storage_num',
                'tb_wms_allo_child.outgoing_storage_diff_reason',
                'tb_wms_allo_child.deadline_date_for_use',
                'dw_in.transfer_warehousing_by',
                'dw_out.transfer_out_library_by',
            ];
            $c = $child->field($fields)
                ->join('left join tb_wms_allo  on tb_wms_allo.id = tb_wms_allo_child.allo_id')
                ->join('left join tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd=tb_wms_allo.allo_in_warehouse')
                ->join('left join tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd=tb_wms_allo.allo_out_warehouse')
                ->where('allo_id =' . $process ['id'])
                ->group('sku_id')
                ->select();
            $sku = array_column($c, 'sku_id');
            $tmp = [];
            foreach ($sku as $key => $value) {
                $tmp [] = ['sku_id' => $value];
            }
            $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);
            $tmp = [];
            foreach ($opt as $key => $value) {
                $tmp [$value['sku_id']] = $value;
            }
            foreach ($c as $key => &$value) {
                $value['GUDS_NM'] = $tmp [$value['sku_id']]['spu_name'];
                $value['img'] = $tmp [$value['sku_id']]['image_url'];
                $value['GUDS_OPT_UPC_ID'] = $tmp [$value['sku_id']]['product_sku']['upc_id'];
            }

            $this->assignJson('child', $c);
            unset($process ['child']);
            $this->assignJson('alloRet', $process);
            $process ['state'] == TbWmsAlloModel::ALLO_SUCCESS ?
                $this->assignJson('showBunder', true) : $this->assignJson('showBunder', false);
            $process ['state'] == TbWmsAlloModel::ALLO_WAIT_OUTGOIN ?
                $this->assignJson('waitOutgoing', true) : $this->assignJson('waitOutgoing', false);
            $process ['state'] == TbWmsAlloModel::ALLO_WAIT_STORAGE ?
                $this->assignJson('waitStorage', true) : $this->assignJson('waitStorage', false);
            if ($process ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $this->assignJson('canRemove', true);
                // 多邮箱情况
                if (strpos($_SESSION ['m_email'], ',') !== false) {
                    $loginUserEmail = explode(',', $_SESSION ['m_email']);
                    if (in_array($this->getSaleTeamEmail($process ['allo_out_team']) ['etc'], $loginUserEmail)) {
                        $this->assignJson('showAgree', true);
                        $this->assignJson('showRefuse', true);
                    } else {
                        $this->assignJson('showAgree', false);
                        $this->assignJson('showRefuse', false);
                    }
                } else {
                    if ($this->getSaleTeamEmail($process ['allo_out_team']) ['etc'] == $_SESSION ['m_email']) {
                        $this->assignJson('showAgree', true);
                        $this->assignJson('showRefuse', true);
                    } else {
                        $this->assignJson('showAgree', false);
                        $this->assignJson('showRefuse', false);
                    }
                }
            } else {
                $this->assignJson('canRemove', false);
                $this->assignJson('showAgree', false);
                $this->assignJson('showRefuse', false);
            }
        }

        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouseLock());
        $this->assignJson('saleTeams', BaseModel::saleTeamCd());
        $this->assignJson('alloType', BaseModel::alloType());
        $this->assignJson('alloState', BaseModel::auditAlloState());
        $template_file = null;
        if (1 == $this->allo_type) {
            $template_file = 'AllocationExtendNew/' . __FUNCTION__;
        }
        $this->display($template_file);
    }

    /**
     * 拒绝调拨
     */
    public function disagree()
    {
        $params = $this->getParams();
        if ($params ['hash']) {
            $model = new TbWmsAlloModel();
            $ret = $model->where("id = %d", [$params ['hash']])->find();
            if ($ret and $ret ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $receive = new TbWmsAlloModel();
                $query ['id'] = $params ['hash'];
                $query ['state'] = TbWmsAlloModel::ALLO_WAIT_REFUSE;
                $query ['info'] = L('已成功拒绝调拨请求');
                $ret = $receive->receive($query);
                $this->success($ret ['info']);
//                $ret ['state'] = TbWmsAlloModel::ALLO_WAIT_REFUSE;
//                $data = $model->create($ret, 2);
//                if ($model->save($data)) {
//                    $this->success(L('success'));
//                } else {
//                    $this->error(L('fail'));
//                }
            } else {
                $this->error(L('状态已改变，当前链接已失效'));
            }
        } else {
            $this->error(L('无效参数'));
        }
    }

    /**
     * 撤回
     */
    public function remove()
    {
        $params = $this->getParams();
        $receive = new TbWmsAlloModel();
        $params ['state'] = TbWmsAlloModel::ALLO_REMOVE;
        $params ['info'] = L('撤回成功');
        $ret = $receive->receive($params);
        $this->AjaxReturn($ret ['data'], $ret ['info'], $ret ['status']);
    }

    /**
     * 创建新的流程(新建调拨单)
     */
    public function create_new_process()
    {
        $model = new TbWmsAlloProcessModel();
        $params = ZUtils::filterBlank($this->getParams());
        if (IS_POST) {
            try {
                if (isset($params['transfer_use_type'])) {
                    $this->validateCreateNewProcess($params);
                    $params['transfer_type'] = 1;
                    $params['into_team'] = $params['allo_in_team'];
                }
                if (2 == $params['transfer_type'] && $params['attribution_team_cd'] == $params['old'] && $params['old'] == $params['new']) {
                    throw new Exception(L('归属销售团队和旧的销售团队和新的销售团队不能完全相同'));
                }
                if ($params['change_type_cd'] === 'N002990005') {
                    if ($params['old'] === $params['new']) {
                        throw new Exception(L('销售小团队新旧值不可以相同'));
                    }
                    if (empty($params['new'])) {
                        throw new Exception(L('销售小团队新值不可以为空~'));
                    }
                }
                #测试写死
                // $params['small_team_cd'] = 'N003230003';
                if ($params['allo_in_team'] == 'N001282800' && empty($params['small_team_cd'])) {
                    throw new Exception(('jerry团队必传小团队'));

                }

                // 【当前归属小团队】和【新的归属小团队】不能一样
                $autoData = $model->create($params);
                $ret = $model->data($autoData)->add();
                if ($ret) {
                    $data = TbWmsAlloProcessModel::buildAccessToken($autoData ['uuid']);
                    $info = L('新建流程成功');
                    $status = 1;
                } else {
                    $data = '';
                    $info = $model->getDbError();
                    $status = 0;
                }
            } catch (Exception $exception) {
                $data = $this->error_message;
                $info = 'error';
                $status = 500;
            }
            $this->AjaxReturn($data, $info, $status);
        } else {
            $this->assignJson('transTeam', BaseModel::saleTeamCdExtend());
            $this->assignJson('transWarehouse', BaseModel::getWarehouseId());
            $template_file = null;
            if (1 == $this->allo_type) {
                $template_file = 'AllocationExtendNew/' . __FUNCTION__;
            }
            if (2 == $this->allo_type) {
                $this->redirect('allocation_extend_attribution/new_one', 302);
            }
            $this->display($template_file);
        }
    }

    /**
     * 创建新的流程(编辑调拨单)
     */
    public function create_edit_process()
    {
        $model = new TbWmsAlloProcessModel();
        $params = ZUtils::filterBlank($this->getParams());
        if (IS_POST) {
            try {
                $allo_id = $params ['allo_id'];
                $Model = new Model();
                $allo = $Model->table('tb_wms_allo')->where(['id' => $allo_id])->find();
                $processId = $allo['process_id'];
                $add['transfer_use_type'] = $allo['transfer_use_type'];
                $add['transfer_type'] = 1;
                $add['into_team'] = $allo['allo_in_team'];
                $add['into_warehouse'] = $allo['allo_in_warehouse'];
                $add['allo_out_warehouse'] = $allo['allo_out_warehouse'];
                $add['use_fawang_logistics'] = 2;
                $add['small_team_cd'] = M('wms_allo_process', 'tb_')->where(['id' => $processId])->getField('small_team_cd');
                $autoData = $model->create($add);
                $ret = $model->data($autoData)->add();
                if ($ret) {
                    $data = TbWmsAlloProcessModel::buildAccessToken($autoData ['uuid']);
                    $info = L('新建流程成功');
                    $status = 1;
                    //获取旧的占用
                    $AllocationExtendNewService = new AllocationExtendNewService();
                    $allo_batch = $AllocationExtendNewService->get_allo_batch($allo_id);
                    if (!empty($allo_batch)) {
                        $process_child_add = [];
                        foreach ($allo_batch as $item) {
                            $process_child['uuid'] = $autoData ['uuid'];
                            $process_child['sku_id'] = $item['SKU_ID'];
                            $process_child['num'] = $item['sum_occupy_num'];
                            $process_child['out_warehouse'] = $allo['allo_out_warehouse'];
                            $process_child['out_team'] = $allo['allo_out_team'];
                            $process_child['positive_defective_type_cd'] = $item['vir_type'];
                            $process_child_add[] = $process_child;
                        }
                        //重新生成新的流程占用
                        $process_child_model = new TbWmsAlloProcessChildModel();
                        $res = $process_child_model->addAll($process_child_add);
                        if (!$res) {
                            $data = '';
                            $info = $process_child_model->getDbError();
                            $status = 0;
                        }
                    }

                    #获取归属占用
                    $attr = $AllocationExtendNewService->transferProductsAtto($allo_id);

                    if (count($attr) > 0) {
                        $process_child_add = [];
                        foreach ($attr as $item) {
                            $process_child['uuid'] = $autoData['uuid'];
                            $process_child['sku_id'] = $item['SKU_ID'];
                            $process_child['num'] = $item['sum_occupy_num'];
                            $process_child['out_warehouse'] = $allo['allo_out_warehouse'];
                            $process_child['out_team'] = $allo['allo_out_team'];
                            $process_child['positive_defective_type_cd'] = $item['vir_type'];
                            $process_child_add[] = $process_child;
                        }
                        //重新生成新的流程占用
                        $process_child_model = new TbWmsAlloProcessChildModel();
                        $res = $process_child_model->addAll($process_child_add);
                        if (!$res) {
                            $data = '';
                            $info = $process_child_model->getDbError();
                            $status = 0;
                        }
                    }

                } else {
                    $data = '';
                    $info = $model->getDbError();
                    $status = 0;
                }
            } catch (Exception $exception) {
                $data = $this->error_message;
                $info = 'error';
                $status = 500;
            }
            $this->AjaxReturn($data, $info, $status);
        } else {
            $this->assignJson('transTeam', BaseModel::saleTeamCdExtend());
            $this->assignJson('transWarehouse', BaseModel::getWarehouseId());
            $template_file = null;
            if (1 == $this->allo_type) {
                $template_file = 'AllocationExtendNew/' . __FUNCTION__;
            }
            if (2 == $this->allo_type) {
                $this->redirect('allocation_extend_attribution/new_one', 302);
            }
            $this->display($template_file);
        }
    }

    private function validateCreateNewProcess($data)
    {
        $rules = [
            'transfer_use_type' => 'required|numeric',
            'allo_in_team' => 'required|string|size:10',
            'allo_out_warehouse' => 'required|string|size:10',
            'use_fawang_logistics' => 'required|numeric',
        ];
        $custom_attributes = [
            'transfer_use_type' => '调拨用途',
            'allo_in_team' => '销售团队',
            'allo_out_warehouse' => '调出仓库',
            'use_fawang_logistics' => '是否对接发网仓',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }


    /**
     * 流程创建后，页面内的数据
     * @param bool $flag 判断是否限制组合商品
     */
    public function show_allo_data($flag)
    {
        $params = ZUtils::filterBlank($this->getParams());
        $_GET ['p'] = $_POST ['p'] = $params['p'];
        $page_i = $_POST['page_i'] ? $_POST['page_i'] : 20;
        $r = $this->searchModel($params, false, $page_i, $flag);
        $processId = $r['processInfo']['uuid'];

        $skus = array_column($r ['ret'], 'SKU_ID', 'sku_id');
        $tmp = [];
        foreach ($skus as $key => $value) {
            $tmp [] = ['sku_id' => $value];
        }
        $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);
        $tmp = [];
        foreach ($opt as $key => $value) {
            $tmp [$value['sku_id']] = $value;
        }
        $ret = $r ['ret'];
        $count = $r ['count'];
        $goods_type_key_vals = array_column(CodeModel::getGoodsTypeCode(), 'cdVal', 'cd');

        $map_store = (new AllocationExtendAttributionRepository())->getStoreName();
        foreach ($ret as $key => &$value) {
            $value['GUDS_NM'] = $tmp [$value['SKU_ID']]['spu_name'];
            $value['img'] = $tmp [$value['SKU_ID']]['image_url'];
            $value['GUDS_OPT_UPC_ID'] = $tmp [$value['SKU_ID']]['product_sku']['upc_id'];
            $value['vir_type_cd_val'] = $goods_type_key_vals[$value['vir_type_cd']];
            $value['shop_id_val'] = $map_store[$value['shop_id']];

            if ($tmp [$value['SKU_ID']]['product_sku']['upc_more']) {
                if ($tmp [$value['SKU_ID']]['product_sku']['upc_more']) {
                    $upc_more_arr = explode(',', $tmp [$value['SKU_ID']]['product_sku']['upc_more']);
                    array_unshift($upc_more_arr, $tmp [$value['SKU_ID']]['product_sku']['upc_id']);
                    $value['GUDS_OPT_UPC_ID'] = implode("\r\n,", $upc_more_arr);
                }
            }
        }
        $ret = CodeModel::autoCodeTwoVal($ret, ['purchasing_team_cd', 'small_sale_team_code']);
        $show = $r ['page']->ajax_show('flip');
        if (IS_POST and !$params ['isNewProcess']) {
            $this->AjaxReturn(['ret' => array_values($ret), 'page' => $show, 'count' => $count], 'success', 1);
        }

        // 过滤销售团队不能与调入团队相同
        $saleTeam = BaseModel::saleTeamCd();
        // 过滤仓库不能与调入仓库相同
        $warehouses = BaseModel::warehouseList(true, true);
        $processInfo = $r ['processInfo'];
        $processInfo['transfer_use_type_val'] = CodeModel::getTransferUseTypeCode()[$processInfo['transfer_use_type']];
        $processInfo['allo_out_warehouse_val'] = BaseModel::warehouseList()[$processInfo['allo_out_warehouse']]['CD_VAL'];
        $processInfo['out_team'] = $processInfo['into_team'];
        $processInfo['out_team_val'] = BaseModel::saleTeamCd()[$processInfo['into_team']];
        $processInfo['use_fawang_logistics_val'] = CodeModel::getSendNetCode()[$processInfo['use_fawang_logistics']];
        $processInfo = CodeModel::autoCodeOneVal($processInfo, ['attribution_team_cd', 'change_type_cd']);
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        $processInfo = $AllocationExtendAttributionService->mapMixing([$processInfo])[0];
        $isJerry = 0;
        $smallTeam = [];

        if ($processInfo['into_team'] == 'N001282800') {
            $isJerry = 1;
            $smallTeam = [
                '0' => '无',
                $processInfo['small_team_cd'] => BaseModel::samllTeamCd()[$processInfo['small_team_cd']]
            ];
        }

        $this->assign('count', (int)$count);
        $this->assignJson('process_info', $processInfo);
        $this->assignJson('token', $params ['token']);
        $this->assignJson('into_team', BaseModel::saleTeamCd() [$processInfo ['into_team']]);
        $this->assignJson('is_jerry', $isJerry);
        $this->assignJson('small_team', $smallTeam);
        $this->assignJson('into_team_code', $processInfo ['into_team']);
        $this->assignJson('into_warehouse_code', $processInfo ['into_warehouse']);
        $this->assignJson('into_warehouse', BaseModel::warehouseList() [$processInfo ['into_warehouse']]['CD_VAL']);
        $this->assignJson('response', array_values($ret));
        $this->assignJson('selectedState', BaseModel::selectedState());
        $this->assignJson('warehouses', $warehouses);
        $this->assignJson('saleTeams', $saleTeam);
        $this->assignJson('canSubmit', in_array(1, array_column($ret, 'allo_child_state')));
        $this->assignJson('page', $show);
        $this->display();
    }

    /**
     * 流程创建后，页面内的数据(编辑界面)
     * @param bool $flag 判断是否限制组合商品
     */
    public function show_allo_data_edit()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $_GET ['p'] = $_POST ['p'] = $params['p'];
        $page_i = $_POST['page_i'] ? $_POST['page_i'] : 20;
        // 获取流程信息
        $processInfo = TbWmsAlloProcessModel::getProcessInfo($params);
        // 过滤销售团队不能与调入团队相同
        $saleTeam = BaseModel::saleTeamCd();
        // 过滤仓库不能与调入仓库相同
        $warehouses = BaseModel::warehouseList(true, true);
        $processInfo['transfer_use_type_val'] = CodeModel::getTransferUseTypeCode()[$processInfo['transfer_use_type']];
        $processInfo['allo_out_warehouse_val'] = BaseModel::warehouseList()[$processInfo['allo_out_warehouse']]['CD_VAL'];
        $processInfo['out_team'] = $processInfo['into_team'];
        $processInfo['out_team_val'] = BaseModel::saleTeamCd()[$processInfo['into_team']];
        $processInfo['use_fawang_logistics_val'] = CodeModel::getSendNetCode()[$processInfo['use_fawang_logistics']];
        $processInfo = CodeModel::autoCodeOneVal($processInfo, ['attribution_team_cd', 'change_type_cd']);
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        $processInfo = $AllocationExtendAttributionService->mapMixing([$processInfo])[0];
        $isJerry = 0;
        $smallTeam = [];
        if ($processInfo['into_team'] == 'N001282800') {
            $isJerry = 1;
            $smallTeam = [
                '0' => '无',
                $processInfo['small_team_cd'] => BaseModel::samllTeamCd()[$processInfo['small_team_cd']]
            ];
        }
        $this->assignJson('process_info', $processInfo);
        $this->assignJson('token', $params ['token']);
        $this->assignJson('into_team', BaseModel::saleTeamCd() [$processInfo ['into_team']]);
        $this->assignJson('into_team_code', $processInfo ['into_team']);
        $this->assignJson('into_warehouse_code', $processInfo ['into_warehouse']);
        $this->assignJson('into_warehouse', BaseModel::warehouseList() [$processInfo ['into_warehouse']]['CD_VAL']);
        $this->assignJson('selectedState', BaseModel::selectedState());
        $this->assignJson('is_jerry', $isJerry);
        $this->assignJson('small_team', $smallTeam);
        $this->assignJson('warehouses', $warehouses);
        $this->assignJson('saleTeams', $saleTeam);
        $this->display();
    }

    public function searchStockModel($params, $isSearchAll = false, $page_i = 10, $is_restrict_com_sku = false)
    {
        if (!TbWmsAlloProcessModel::checkToken($params)) {
            if ($params ['isNewProcess'])
                $this->redirect('AllocationExtend/create_new_process', [], $delay = 3, L('请求异常：[token 验证失败，请重新创建流程]'));
            else
                $this->AjaxReturn('', L('请求异常：[新建流程失败 token 验证失败]'), 0);
        }
        // 获取流程信息
        $processInfo = TbWmsAlloProcessModel::getProcessInfo($params);
        if (!$processInfo)
            if ($params ['isNewProcess'])
                $this->redirect('AllocationExtend/create_new_process', [], $delay = 3, L('请求异常：[获取流程信息失败，请重新发起流程。(系统' . $delay . '秒后自动跳转)]'));
            else
                $this->AjaxReturn('', L('请求异常：[获取流程信息失败，请重新发起流程。]'), 0);

        $model = new SlaveModel();
        if (2 == $this->allo_type) {
            $fields = [
                't1.sale_team_code',
                't1.total_inventory',
                't1.id AS batch_id',
                't1.batch_code',
                't1.available_for_sale_num as available_for_sale_num_total',
                't1.total_inventory as total_inventory_total',
                't1.SKU_ID',
                't1.vir_type',
                't1.vir_type AS vir_type_cd',
                't3.warehouse_id',
                't3.warehouse_id AS warehouse_cd',
                't3.ascription_store AS shop_id',
                't3.ascription_store',
                't3.SP_TEAM_CD',
                't3.SP_TEAM_CD AS purchasing_team_cd',
                't1.small_sale_team_code'
            ];
        } else {
            $fields = [
                't1.sale_team_code',
                't1.total_inventory',
                't1.id AS batch_id',
                't1.batch_code',
                'SUM(t1.available_for_sale_num) as available_for_sale_num_total',
                'SUM(t1.total_inventory) as total_inventory_total',
                't1.SKU_ID',
                't1.vir_type',
                't1.vir_type AS vir_type_cd',
                't3.warehouse_id',
                't3.ascription_store AS shop_id',
                't3.ascription_store',
                't3.SP_TEAM_CD',
                't3.SP_TEAM_CD AS purchasing_team_cd',
                't1.small_sale_team_code'
            ];
        }
        if (1 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['into_team'];
            $params['warehouse_code'] = $processInfo['allo_out_warehouse'];
            if (isset($processInfo['transfer_use_type']) && 0 == $processInfo['transfer_use_type']) {
                $params['vir_type_cd'] = 'N002440100';
            }
        }
        if (2 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['attribution_team_cd'];
            switch ($processInfo['change_type_cd']) {
                case CodeModel::$change_attribution_store_cd:
                    $params['shop_id'] = $processInfo['old'];
                    break;
                case CodeModel::$change_sales_team_cd:
                    break;
                case CodeModel::$change_purchasing_team_cd:
                    $params['SP_TEAM_CD'] = $processInfo['old'];
                    break;
            }
        }
        if (is_null($params['vir_type_cd']) || '' === $params['vir_type_cd']) {
            if (1 === $this->allo_type) {
                unset($params['vir_type_cd']);
            } else {
                $params['vir_type_cd'] = 'N002440100';
            }
        }
        $conditions = TbWmsAlloModel::searchProcess($params, false);

        if (1 === $this->allo_type || 2 == $processInfo['transfer_type']) {
            $conditions['a.available_for_sale_num_total'] = ['gt', 0];
        }
        if (1 !== $this->allo_type || !empty($params['vir_type_cd'])) {
            $and_vir_type_where = "AND t1.vir_type = '{$params['vir_type_cd']}'";
        }

        //针对库存归属、调拨排除掉组合商品
        if ($is_restrict_com_sku) {
            if ($conditions ['_string']) {
                $conditions ['_string'] = $conditions ['_string'] . ' AND (a.SKU_ID NOT LIKE "9%") ';
            } else {
                $conditions ['_string'] = ' (a.SKU_ID NOT LIKE "9%") ';
            }
        }

        $subQueryConditions = TbWmsAlloModel::searchStockSubQueryProcess($params, $processInfo, $and_vir_type_where);//条件加到子查询，减少查询数据集合
        $subQuery = $model->field($fields)
            ->table('tb_wms_batch t1, tb_wms_stream t2, tb_wms_bill t3');
        if (2 == $processInfo['transfer_type']) {
            $subQuery = $subQuery->group("t1.sale_team_code, t3.warehouse_id, t1.SKU_ID, t1.vir_type,IFNULL(t1.small_sale_team_code, ''),t1.bill_id,t1.id");
        } else {
            $subQuery = $subQuery->group("t1.sale_team_code, t3.warehouse_id, t1.SKU_ID, t1.vir_type,IFNULL(t1.small_sale_team_code, '')");
        }
        $subQuery = $subQuery
            ->where($subQueryConditions)
            ->buildSql();
        $subQuery2 = $model
            ->table('tb_wms_allo_process_child')
            ->where('uuid = "' . $processInfo ['uuid'] . '"', null, true)
            ->group("sku_id,out_warehouse,out_team,positive_defective_type_cd,IFNULL(out_small_team, '')")
            ->field('id,sku_id,out_warehouse,out_team,out_store,positive_defective_type_cd,batch_id,uuid,sum(num) as num,out_small_team')
            ->buildSql();
        /*$subQuery2 = $model
            ->table('tb_wms_allo_process_child')
            ->where('uuid = "' . $processInfo ['uuid'] . '"', null, true)
            ->buildSql();*/
        $count = $model->table($subQuery . ' a')
            ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and (a.ascription_store = a3.out_store or (a3.out_store = "" and a.ascription_store is null)) and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
            (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
            AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
        )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.sku_id')
            ->where($conditions)
            ->count();
        $page = new Page($count, $page_i);
        $query = $model->table($subQuery . ' a')
            ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID  and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and (a.ascription_store = a3.out_store or (a3.out_store = "" and a.ascription_store is null)) and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
            (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
            AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
        )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
            ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.SKU_ID')
            ->join('left join tb_ms_store ts ON ts.ID = a.ascription_store')
            ->join('inner join tb_ms_cmn_cd cd on cd.CD=a.warehouse_id and cd.USE_YN="Y"')
            ->field('a.*, t9.upc_id as upc,t9.upc_more, ts.STORE_NAME, IFNULL(a3.num, 0) as need_num, a3.id as allo_child_id, CASE WHEN a3.num IS NULL THEN 0 ELSE 1 END as allo_child_state')
            ->order('a.available_for_sale_num_total desc')
            ->where($conditions);
        if ($isSearchAll == false)
            $query->limit($page->firstRow, $page->listRows);

        $ret = $query->select();

        return ['ret' => $ret, 'count' => $count, 'page' => $page, 'processInfo' => $processInfo];
    }

    public function searchModel($params, $isSearchAll = false, $page_i = 10, $is_restrict_com_sku = false)
    {
        if (!TbWmsAlloProcessModel::checkToken($params)) {
            if ($params ['isNewProcess'])
                $this->redirect('AllocationExtend/create_new_process', [], $delay = 3, L('请求异常：[token 验证失败，请重新创建流程]'));
            else
                $this->AjaxReturn('', L('请求异常：[新建流程失败 token 验证失败]'), 0);
        }
        // 获取流程信息
        $processInfo = TbWmsAlloProcessModel::getProcessInfo($params);
        if (!$processInfo)
            if ($params ['isNewProcess'])
                $this->redirect('AllocationExtend/create_new_process', [], $delay = 3, L('请求异常：[获取流程信息失败，请重新发起流程。(系统' . $delay . '秒后自动跳转)]'));
            else
                $this->AjaxReturn('', L('请求异常：[获取流程信息失败，请重新发起流程。]'), 0);

        $model = new Model(); #2021-03-11 主从同步较慢 改为主库查询
        if (2 == $this->allo_type) {
            //库存归属变更类型
            $fields = [
                't1.sale_team_code',
                't1.total_inventory',
                't1.id AS batch_id',
                't1.batch_code',
                't1.available_for_sale_num as available_for_sale_num_total',
                't1.total_inventory as total_inventory_total',
                't1.SKU_ID',
                't1.vir_type',
                't1.vir_type AS vir_type_cd',
                't3.warehouse_id',
                't3.warehouse_id AS warehouse_cd',
                't3.ascription_store AS shop_id',
                't3.ascription_store',
                't3.SP_TEAM_CD',
                't3.SP_TEAM_CD AS purchasing_team_cd',
                't1.small_sale_team_code'
            ];
        } else {
            $fields = [
                't1.sale_team_code',
                't1.total_inventory',
                't1.id AS batch_id',
                't1.batch_code',
                'SUM(t1.available_for_sale_num) as available_for_sale_num_total',
                'SUM(t1.total_inventory) as total_inventory_total',
                't1.SKU_ID',
                't1.vir_type',
                't1.vir_type AS vir_type_cd',
                't3.warehouse_id',
                't3.ascription_store AS shop_id',
                't3.ascription_store',
                't3.SP_TEAM_CD',
                't3.SP_TEAM_CD AS purchasing_team_cd',
                't1.small_sale_team_code'
            ];
        }
        if (1 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['into_team'];
            $params['warehouse_code'] = $processInfo['allo_out_warehouse'];
            if (isset($processInfo['transfer_use_type']) && 0 == $processInfo['transfer_use_type']) {
                $params['vir_type_cd'] = 'N002440100';
            }
        }
        if (2 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['attribution_team_cd'];
            switch ($processInfo['change_type_cd']) {
                case CodeModel::$change_attribution_store_cd:
                    $params['shop_id'] = $processInfo['old'];
                    break;
                case CodeModel::$change_sales_team_cd:
                    break;
                case CodeModel::$change_purchasing_team_cd:
                    $params['SP_TEAM_CD'] = $processInfo['old'];
                    break;
            }
        }
        if (is_null($params['vir_type_cd']) || '' === $params['vir_type_cd']) {
            if (1 === $this->allo_type) {
                unset($params['vir_type_cd']);
            }
//            else {
//               $params['vir_type_cd'] = 'N002440100';
//            }
        }
        $conditions = TbWmsAlloModel::searchProcess($params, false);
        if (1 === $this->allo_type || 2 == $processInfo['transfer_type']) {
            $conditions['a.available_for_sale_num_total'] = ['gt', 0];
        }
        if (1 !== $this->allo_type && !empty($params['vir_type_cd'])) {
            $and_vir_type_where = "AND t1.vir_type = '{$params['vir_type_cd']}'";
        }

        //针对库存归属、调拨排除掉组合商品
        if ($is_restrict_com_sku) {
            if ($conditions ['_string']) {
                $conditions ['_string'] = $conditions ['_string'] . ' AND (a.SKU_ID NOT LIKE "9%") ';
            } else {
                $conditions ['_string'] = ' (a.SKU_ID NOT LIKE "9%") ';
            }
        }

        $subQueryConditions = TbWmsAlloModel::searchSubQueryProcess($params, $processInfo, $and_vir_type_where);//条件加到子查询，减少查询数据集合
        $subQuery = $model->field($fields)
            ->table('tb_wms_batch t1, tb_wms_stream t2, tb_wms_bill t3');
        if (2 == $processInfo['transfer_type']) {
            $subQuery = $subQuery->group("t1.sale_team_code, t3.warehouse_id, t1.SKU_ID, t1.vir_type,IFNULL(t1.small_sale_team_code, ''),t1.bill_id,t1.id");
        } else {
            $subQuery = $subQuery->group("t1.sale_team_code, t3.warehouse_id, t1.SKU_ID, t1.vir_type,IFNULL(t1.small_sale_team_code, '')");
        }
        $subQuery = $subQuery
            ->where($subQueryConditions)
            ->buildSql();

        //bug修复，为了不影响除库存归属变更类型外，做如下判断，如其它类型的判断条件也和库存归属变更类型一样，则可取消合并成一个
        if (2 == $this->allo_type) {
            //库存归属变更类型
            $subQuery2 = $model
                ->table('tb_wms_allo_process_child')
                ->where('uuid = "' . $processInfo ['uuid'] . '"', null, true)
                ->group("sku_id,out_warehouse,out_team,positive_defective_type_cd,IFNULL(out_small_team, ''), batch_id")//增加了batch_id字段聚合，库存归属精确到批次
                ->field('id,sku_id,out_warehouse,out_team,out_store,positive_defective_type_cd,batch_id,uuid,sum(num) as num,out_small_team')
                ->buildSql();

            //不同的归属变更类型有不同的聚合判断
            switch ($processInfo['change_type_cd']) {
                //多了销售小团队聚合条件small_sale_team_code
                case CodeModel::$change_small_sales_team_cd:
                    $count = $model->table($subQuery . ' a')
                        ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
                        (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
                        AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
                        )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                        ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.sku_id')
                        ->where($conditions)
                        ->count();

                    $query = $model->table($subQuery . ' a')
                        ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID  and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
                            (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
                            AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
                        )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                        ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.SKU_ID')
                        ->join('left join tb_ms_store ts ON ts.ID = a.ascription_store')
                        ->join('inner join tb_ms_cmn_cd cd on cd.CD=a.warehouse_id and cd.USE_YN="Y"')
                        ->field('a.*, t9.upc_id as upc,t9.upc_more, ts.STORE_NAME, IFNULL(a3.num, 0) as need_num, a3.id as allo_child_id, CASE WHEN a3.num IS NULL THEN 0 ELSE 1 END as allo_child_state')
                        ->order('a.available_for_sale_num_total desc')
                        ->where($conditions);
                    break;
                default:
                    $count = $model->table($subQuery . ' a')
                        ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                        ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.sku_id')
                        ->where($conditions)
                        ->count();

                    $query = $model->table($subQuery . ' a')
                        ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID  and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                        ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.SKU_ID')
                        ->join('left join tb_ms_store ts ON ts.ID = a.ascription_store')
                        ->join('inner join tb_ms_cmn_cd cd on cd.CD=a.warehouse_id and cd.USE_YN="Y"')
                        ->field('a.*, t9.upc_id as upc,t9.upc_more, ts.STORE_NAME, IFNULL(a3.num, 0) as need_num, a3.id as allo_child_id, CASE WHEN a3.num IS NULL THEN 0 ELSE 1 END as allo_child_state')
                        ->order('a.available_for_sale_num_total desc')
                        ->where($conditions);
                    break;
            }
        } else {
            //其它类型
            $subQuery2 = $model
                ->table('tb_wms_allo_process_child')
                ->where('uuid = "' . $processInfo ['uuid'] . '"', null, true)
                ->group("sku_id,out_warehouse,out_team,positive_defective_type_cd,IFNULL(out_small_team, '')")
                ->field('id,sku_id,out_warehouse,out_team,out_store,positive_defective_type_cd,batch_id,uuid,sum(num) as num,out_small_team')
                ->buildSql();

            $count = $model->table($subQuery . ' a')
                ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and (a.ascription_store = a3.out_store or (a3.out_store = "" and a.ascription_store is null)) and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
                (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
                AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
                )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.sku_id')
                ->where($conditions)
                ->count();

            $query = $model->table($subQuery . ' a')
                ->join('left join (' . $subQuery2 . ') a3 on a.SKU_ID = a3.SKU_ID  and a.warehouse_id = a3.out_warehouse and a.sale_team_code = a3.out_team and (a.ascription_store = a3.out_store or (a3.out_store = "" and a.ascription_store is null)) and a.vir_type_cd = a3.positive_defective_type_cd AND (a.batch_id = a3.batch_id OR a3.batch_id  IS NULL) and (a.small_sale_team_code = a3.out_small_team OR (
                    (a3.out_small_team = "" OR a3.out_small_team IS NULL ) 
                    AND ( a.small_sale_team_code = "" OR a.small_sale_team_code IS NULL ) 
                )) and a3.uuid = "' . $processInfo ['uuid'] . '"')
                ->join('left join ' . PMSSearchModel::skuUpcSql() . ' t9 ON t9.sku_id = a.SKU_ID')
                ->join('left join tb_ms_store ts ON ts.ID = a.ascription_store')
                ->join('inner join tb_ms_cmn_cd cd on cd.CD=a.warehouse_id and cd.USE_YN="Y"')
                ->field('a.*, t9.upc_id as upc,t9.upc_more, ts.STORE_NAME, IFNULL(a3.num, 0) as need_num, a3.id as allo_child_id, CASE WHEN a3.num IS NULL THEN 0 ELSE 1 END as allo_child_state')
                ->order('a.available_for_sale_num_total desc')
                ->where($conditions);
        }
        $page = new Page($count, $page_i);

        if ($isSearchAll == false)
            $query->limit($page->firstRow, $page->listRows);

        $ret = $query->select();
        return ['ret' => $ret, 'count' => $count, 'page' => $page, 'processInfo' => $processInfo];
    }

    /**
     * 修改或给该流程新增子数据
     */
    public function update_or_add_allo()
    {
        $params = ZUtils::filterBlank($this->getParams());
        if (!TbWmsAlloProcessModel::checkToken($params))
            $this->AjaxReturn('', L('请求异常：[token 验证失败，请重新创建流程]'), 0);
        $processInfo = TbWmsAlloProcessModel::getProcessInfo($params);
        if ($processInfo) {
            $model = new TbWmsAlloProcessChildModel();
            $params ['uuid'] = $processInfo ['uuid'];
            $conditions ['uuid'] = ['eq', $processInfo ['uuid']];
            $conditions ['sku_id'] = ['eq', $params ['sku_id']];
            $conditions ['out_team'] = ['eq', $params ['out_team']];
            $conditions ['out_warehouse'] = ['eq', $params ['out_warehouse']];
            if ($params ['out_small_team']) {
                $conditions ['out_small_team'] = ['eq', $params['out_small_team']];
            }
            if ($params ['out_store']) {
                $conditions ['out_store'] = ['eq', $params ['out_store']];
            }
            if ($params ['positive_defective_type_cd']) {
                $conditions ['positive_defective_type_cd'] = ['eq', $params ['positive_defective_type_cd']];
            }
            if ($params['batch_id']) {
                $conditions ['batch_id'] = ['eq', $params['batch_id']];
            }
            if ($ret = $model->where($conditions)->find()) {
                $ret ['num'] = $params ['num'];
                //调拨改版，区分归属店铺，多条记录清空其他记录数量，只在第一条插入数量
                $model->where($conditions)->save(['num' => 0]);
                if ($params ['num'] == 0) {
                    $model->where('id = ' . $ret ['id'])->delete();
                } else {
                    //
                    $model->save($ret);
                }
            } else {
                $saveData = $model->create($params);
                $model->add($saveData);
            }
            if ($model->where("uuid = '%s'", $processInfo ['uuid'])->select()) {
                $data = 1;
            } else {
                $data = 0;
            }
            $info = L('成功');
            $status = 1;
        } else {
            $data = '';
            $info = L('请求异常：[流程已失效，请重新创建]');
            $status = 0;
        }
        $this->AjaxReturn($data, $info, $status);
    }

    /**
     * 全部调拨
     */
    public function update_or_add_all_allo()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $hash_lock = md5($params['token']);
        if (!RedisModel::compatibleLock('lock_' . $hash_lock, 14400)) {
//            $this->AjaxReturn('', L('已进行过全部调拨，禁止再次使用'), 0);
        }
        if (!TbWmsAlloProcessModel::checkToken($params))
            $this->AjaxReturn('', L('请求异常：[token 验证失败，请重新创建流程]'), 0);
        $processInfo = TbWmsAlloProcessModel::getProcessInfo($params);
        if ($processInfo) {
            if (0 == $processInfo['transfer_use_type']) {
                $params['vir_type_cd'] = 'N002440100';
            }
            $ret = $this->getAllAllocationData($params, $processInfo);
            if (empty($ret)) {
                $this->AjaxReturn('', L('获取库存失败'), 0);
            }
            // 发起全部调拨的时候，需要清理掉已经勾选的数据，获得所有现存量数据，全部写入 process_child 表
            $model = new TbWmsAlloProcessChildModel();
            if (2 == $processInfo['transfer_type']) {
                $model->where("uuid = '%s'", $processInfo ['uuid'])->delete();
            }
            $params ['uuid'] = $processInfo ['uuid'];
            $conditions ['uuid'] = ['eq', $processInfo ['uuid']];
            foreach ($ret as $key => &$value) {
                $value['out_team'] = $value['sale_team_code'];
                $value['num'] = $value['available_for_sale_num_total'];
                $value['sku_id'] = $value['SKU_ID'];
                $value['out_warehouse'] = $value['warehouse_id'];
                $value['uuid'] = $processInfo ['uuid'];
                $value['positive_defective_type_cd'] = $value['vir_type'];
                $value['batch_id'] = $value['batch_id'];
                $value['out_small_team'] = $params['sell_small_team_cd'][0];//补充小团队需求漏掉的字段，销售团队只有一个取第一个即可
                unset($value['vir_type']);
                unset($value['vir_type_cd']);
                unset($value['sale_team_code']);
                unset($value['total_inventory']);
                unset($value['available_for_sale_num_total']);
                unset($value['total_inventory_total']);
                unset($value['SKU_ID']);
                unset($value['warehouse_id']);
                unset($value['ascription_store'], $value['shop_id'], $value['purchasing_team_cd']);
            }
            if ($model->where($conditions)->select()) {
                //$model->delete(['uuid' => $processInfo ['uuid']]);
                if ($model->addAll($ret)) {
                    $info = L('成功');
                    $status = 1;
                } else {
                    $info = L('失败');
                    $status = 0;
                }
            } else {
                if ($model->addAll($ret)) {
                    $info = L('成功');
                    $status = 1;
                } else {
                    $info = L('失败');
                    $status = 0;
                }
            }
            if ($model->where("uuid = '%s'", $processInfo ['uuid'])->select()) {
                $data = 1;
            } else {
                $data = 0;
            }
            $info = L('成功');
            $status = 1;
        } else {
            $data = '';
            $info = L('请求异常：[流程已失效，请重新创建]');
            $status = 0;
        }
        $this->AjaxReturn($data, $info, $status);
    }

    /**
     * 获取全部调拨的数据
     */
    public function getAllAllocationData($params, $processInfo)
    {
        $model = new SlaveModel();
        $fields = [
            't1.sale_team_code', // 调出团队
            't1.total_inventory',
            'SUM(t1.available_for_sale_num) as available_for_sale_num_total', // 调出团队可售库存
            'SUM(t1.total_inventory) as total_inventory_total',
            't1.SKU_ID', // sku
            't1.vir_type',
            't1.vir_type AS vir_type_cd', // sku
            't3.warehouse_id', // 调出仓库
            't1.id AS batch_id', // 调出仓库
            't3.ascription_store', // 归属店铺
            't3.ascription_store AS shop_id', // 调出仓库
            't3.SP_TEAM_CD AS purchasing_team_cd', // 调出仓库
        ];
        if (1 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['into_team'];
            $params['warehouse_code'] = $processInfo['allo_out_warehouse'];
        }
        if (2 == $processInfo['transfer_type']) {
            $params['sale_team_code'] = $processInfo['attribution_team_cd'];
            switch ($processInfo['change_type_cd']) {
                case CodeModel::$change_attribution_store_cd:
                    $params['shop_id'] = $processInfo['old'];
                    break;
                case CodeModel::$change_sales_team_cd:
                    break;
                case CodeModel::$change_purchasing_team_cd:
                    $params['SP_TEAM_CD'] = $processInfo['old'];
                    break;
            }
        }
        $conditions = TbWmsAlloModel::searchProcess($params, true);
        $subQueryConditions = TbWmsAlloModel::searchSubQueryProcess($params, $processInfo, '', true);//条件加到子查询，减少查询数据集合
        $subQuery = $model->field($fields)
            ->table('tb_wms_batch t1, tb_wms_bill t3');
        if (2 == $processInfo['transfer_type']) {
            //库存归属
            $subQuery = $subQuery->group('sale_team_code, warehouse_id, SKU_ID,t1.vir_type,t3.ascription_store,t1.small_sale_team_code, t3.bill_id, t1.id');//20210223增加t1.id（批次id）聚合，因为库存归属变更数据展示精确到批次
        } else {
            $subQuery = $subQuery->group('sale_team_code, warehouse_id, SKU_ID,t1.vir_type,t3.ascription_store, t1.small_sale_team_code');
        }
        // 筛除在途
        $subQuery = $subQuery->where($subQueryConditions)
            ->buildSql();
        $count = $model->table($subQuery . ' a')
            ->where($conditions)
            ->where(['a.available_for_sale_num_total' => ['gt', 0]])
            ->count();
        $page = new Page($count, 10);
        $ret = $model->table($subQuery . ' a')
            ->field('a.*')
            ->where($conditions)
            ->where(['a.available_for_sale_num_total' => ['gt', 0]])
            ->select();
        return $ret;
    }

    /**
     * 返回上一步
     * 关闭当前流程
     */
    public function lastStep()
    {
        $params = ZUtils::filterBlank($this->getParams());
        $process = TbWmsAlloProcessModel::getProcessInfo($params);
        if ($process) {
            $model = new Model();
            $process ['state'] = self::ALLO_STATE_OFF;
            if ($model->table('tb_wms_allo_process')->save($process)) {
                $this->AjaxReturn('', L('当前流程已关闭'), 1);
            } else {
                $this->AjaxReturn('', L('请求异常：[关闭流程失败]') . $model->getError(), 0);
            }
        } else {
            $this->AjaxReturn('', L('新建流程：[当亲流程已失效，开始新建流程]'), 0);
        }
    }

    /**
     * 打印拣货单
     */
    public function print_picking_list()
    {
        $params = $this->getParams();
        // 新建拣货单号
        $model = new TbWmsAlloModel();
        $ret = $model->where('id = %d', [$params ['id']])->find();
        $pickModel = new Model();
        if ($ret ['is_print_picking_list'] == 0) {
            $ret ['is_print_picking_list'] = 1;
            if ($model->save($ret)) {
                $pData ['allo_id'] = $ret ['id'];
                $pData ['picking_no'] = 'JH-' . date('Ymd') . '-' . TbWmsNmIncrementModel::generateNo('JH-');
                $pData ['create_user'] = BaseModel::getName();
                $pData ['create_date'] = date('Y-m-d H:i:s', time());
                $pickModel->table('tb_wms_allo_picking')->add($pData);
            }
            $pickNo = $pData ['picking_no'];
        } else {
            $pret = $pickModel->table('tb_wms_allo_picking')->where('allo_id = %d', [$params ['id']])->find();
            $pickNo = $pret ['picking_no'];
        }
        $model = new TbWmsAlloChildModel();
        $conditions ['tb_wms_allo_child.allo_id'] = ['eq', $params ['id']];
        $ret = $model
            ->field('twls.location_code, twls.location_code_back, tb_wms_allo_child.sku_id, tb_wms_allo_child.demand_allo_num, tb_wms_allo_child.deadline_date_for_use')
            ->join('left join tb_wms_allo twa on tb_wms_allo_child.allo_id = twa.id')
            ->join('LEFT JOIN tb_wms_warehouse tww ON tww.CD = twa.allo_out_warehouse')
            ->join('LEFT JOIN tb_wms_location_sku twls ON twls.sku = tb_wms_allo_child.sku_id AND twls.warehouse_id = tww.id')
            ->where($conditions)
            ->select();
        $sku = array_column($ret, 'sku_id');
        $tmp = [];
        foreach ($sku as $key => $value) {
            $tmp [] = ['sku_id' => $value];
        }
        $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'upc_id', 'product_sku']);
        $tmp = [];
        foreach ($opt as $key => $value) {
            $tmp [$value['sku_id']] = $value;
        }
        foreach ($ret as $key => &$value) {
            $value['GUDS_NM'] = $tmp [$value['sku_id']]['spu_name'];
            $value['img'] = $tmp [$value['sku_id']]['image_url'];
            $value['GUDS_OPT_UPC_ID'] = $tmp [$value['sku_id']]['product_sku']['upc_id'];
        }

        $this->assignJson('pick_no', $pickNo);
        $this->assignJson('sysTime', date('Y-m-d H:i:s', time()));
        $this->assignJson('ret', $ret);
        $this->display();
    }

    public function launch_allo_no()
    {
        $this->launch_allo();
    }

    /**
     * 发起调拨
     */
    public function launch_allo()
    {
        if (!TbWmsAlloProcessModel::checkToken($this->getParams())) {
            $this->AjaxReturn('', L('请求异常：[token 验证失败，请重新创建流程]'), 0);
        }
        $model = new TbWmsAlloModel();
        $ret = $model->createAllo($this->getParams());
        $this->AjaxReturn($ret ['data'], $ret ['info'], $ret ['status']);
    }

    /**
     * 编辑调拨
     */
    public function editAlloDetail()
    {
        try {
            $Model = new Model();
            $data = DataModel::getDataNoBlankToArr();
            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->editAlloDetail($data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);
    }


    /**
     * 撤回调拨
     */
    public function receive()
    {
        $model = new TbWmsAllocationModel();
        $ret = $model->receive($this->getParams());

        $this->ajaxReturn(null, $ret ['msg'], $ret ['state']);
    }

    /**
     * 获取调拨模板
     */
    public function get_mail_template($allo, $confirm_url, $refuse_url)
    {
        $this->assign('ret', $allo);
        $this->assign('confirm', $confirm_url);
        $this->assign('refuse', $refuse_url);
        // 获取生成html文件
        $content = $this->fetch('AllocationExtend:template');
        return $content;
    }

    /**
     * 同意调拨
     */
    public function agree()
    {
        $params = $this->getParams();
        if ($params ['hash']) {
            $model = new TbWmsAlloModel();
            $ret = $model->where("id = %d", [$params ['hash']])->find();
            if ($ret and $ret ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $ret ['state'] = TbWmsAlloModel::ALLO_WAIT_OUTGOIN;
                $data = $model->create($ret, 2);
                if ($model->save($data)) {
                    $model->autoEmailOutgoingAndStorage((array)$params ['hash']);
                    echo 'success';
                    exit;
                    //$this->success(L('success'));
                } else {
                    echo 'fail';
                    exit;
                    //$this->error(L('fail'));
                }
            } else {
                $this->error(L('状态已改变，当前链接已失效'));
            }
        } else {
            $this->error(L('无效参数'));
        }
    }

    /**
     * 仓库地址获取
     */
    public function getWarehouseAddress()
    {
        $model = new Model();
        $ret = $model->table('tb_wms_warehouse')->getField('CD, place, address');
        return $ret;
    }


    /**
     * 入库确认
     */
    public function confirm_storage()
    {
        set_time_limit(0);
        $params = $this->getParams();
        $model = new TbWmsAlloModel();
        if (IS_POST) {
            //上传文件
            if ($info = $this->upload_file() ['state'] == 1) {
                $ret = $model->storage($params);
                $this->AjaxReturn($ret ['data'], $ret ['info'], $ret ['status']);
            } else {
                $this->AjaxReturn('', L('文件上传失败') . $info ['info'], 0);
            }
        }
        $process = $model->where('id = %d', [$params ['id']])->find();
        if ($process) {
            $child = new TbWmsAlloChildModel();
            $fields = [
                'tb_wms_allo_child.allo_id',
                'tb_wms_allo_child.id',
                'tb_wms_allo_child.sku_id',
                'SUM(tb_wms_allo_child.demand_allo_num) as demand_allo_num',
                'SUM(tb_wms_allo_child.actual_outgoing_num) as actual_outgoing_num',
                'tb_wms_allo_child.actual_demand_diff_reason',
                'SUM(tb_wms_allo_child.actual_storage_num) as actual_storage_num',
                'tb_wms_allo_child.outgoing_storage_diff_reason',
                'tb_wms_allo_child.deadline_date_for_use',
            ];
            $c = $child->field($fields)
                ->where('allo_id =' . $process ['id'])
                ->group('sku_id, deadline_date_for_use')
                ->select();

            $sku = array_column($c, 'sku_id');
            $tmp = [];
            foreach ($sku as $key => $value) {
                $tmp [] = ['sku_id' => $value];
            }
            $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);
            $tmp = [];
            foreach ($opt as $key => $value) {
                $tmp [$value['sku_id']] = $value;
            }
            foreach ($c as $key => &$value) {
                $value['GUDS_NM'] = $tmp [$value['sku_id']]['spu_name'];
                $value['img'] = $tmp [$value['sku_id']]['image_url'];
                $value['GUDS_OPT_UPC_ID'] = $tmp [$value['sku_id']]['product_sku']['upc_id'];
            }
            $this->assignJson('child', $c);
            $this->assignJson('alloRet', $process);
        }

        $this->assignJson('currency', BaseModel::getCurrency());
        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouseLock());
        $this->assignJson('saleTeams', BaseModel::saleTeamCd());
        $this->assignJson('alloType', BaseModel::alloType());
        $this->assignJson('alloState', BaseModel::auditAlloState());
        $this->assignJson('warehouseAddress', $this->getWarehouseAddress());

        $this->display();
    }

    /**
     * 出库确认
     */
    public function confirm_outgoing()
    {
        set_time_limit(0);
        $params = $this->getParams();
        $model = new TbWmsAlloModel();
        if (IS_POST) {
            $outgoing = new TbWmsAlloModel();
            $ret = $outgoing->outGoing($params);
            $this->AjaxReturn($ret ['data'], $ret ['info'], $ret ['status']);
        }
        $process = $model->where('id = %d', [$params ['id']])->find();
        if ($process) {
            $allocationChildModel = new TbWmsAlloChildModel();
            $fields = [
                'tb_wms_allo_child.allo_id',
                'tb_wms_allo_child.id',
                'tb_wms_allo_child.sku_id',
                'SUM(tb_wms_allo_child.demand_allo_num) as demand_allo_num',
                'SUM(tb_wms_allo_child.actual_outgoing_num) as actual_outgoing_num',
                'tb_wms_allo_child.actual_demand_diff_reason',
                'SUM(tb_wms_allo_child.actual_storage_num) as actual_storage_num',
                'tb_wms_allo_child.outgoing_storage_diff_reason',
                'tb_wms_allo_child.deadline_date_for_use',
            ];
            $condition ['allo_id'] = ['eq', $process ['id']];
            $ret = $allocationChildModel->field($fields)
                ->where($condition)
                ->group('sku_id, deadline_date_for_use')
                ->select();
            $sku = array_column($ret, 'sku_id');
            $tmp = [];
            foreach ($sku as $key => $value) {
                $tmp [] = ['sku_id' => $value];
            }
            $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);
            $tmp = [];
            foreach ($opt as $key => $value) {
                $tmp [$value['sku_id']] = $value;
            }
            foreach ($ret as $key => &$value) {
                $value['GUDS_OPT_UPC_ID'] = $tmp [$value['sku_id']]['product_sku']['upc_id'];
                $value['GUDS_NM'] = $tmp [$value['sku_id']]['spu_name'];
                $value['img'] = $tmp [$value['sku_id']]['image_url'];
            }
            $this->assignJson('child', $ret);
            $this->assignJson('ret', $process);
        }

        $this->assignJson('currency', BaseModel::getCurrency());
        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouseLock());
        $this->assignJson('saleTeams', BaseModel::saleTeamCd());
        $this->assignJson('alloType', BaseModel::alloType());
        $this->assignJson('alloState', BaseModel::auditAlloState());
        $this->assignJson('warehouseAddress', $this->getWarehouseAddress());

        $this->display();
    }

    /**
     * 上传凭证
     */
    public function upload_file()
    {
        $fd = new FileUploadModel();
        if ($fd->saveFile($_FILES ['evidence'])) {
            $model = new TbWmsAlloModel();
            $data ['voucher_file'] = $fd->info [0]['savename'];
            if ($model->where('id = %d', [$this->getParams() ['id']])->save($data)) {
                return ['state' => 1];
            } else {
                return ['state' => 0, 'info' => $fd->error];
            }
        }
        return ['state' => 0, 'info' => L('未选择文件')];
    }

    /**
     * 下载凭证
     */
    public function download_file()
    {
        $model = M('_wms_allo', 'tb_');
        $ret = $model->where("id = %d", [$this->getParams() ['id']])->find();
        if ($ret ['voucher_file']) {
            $fd = new FileDownloadModel();
            $fd->fname = $ret ['voucher_file'];
            try {
                if (!$fd->downloadFile()) {
                    $this->error("文件不存在");
                }
            } catch (exception $e) {
                $this->error('文件不存在');
            }
        }
        $this->error('文件不存在');
    }

    /**
     * 出入库订单获取
     */
    public function orderVoucher()
    {
        $params = ZUtils::filterBlank($_POST);
        $model = new Model();
        $response = $model->table('tb_wms_bill')
            ->field('bill_id as billId, id, bill_date')
            ->where(['id' => ['in', explode(',', $params ['billId'])]])
            ->select();

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 出库展示
     */
    public function out_or_allo()
    {
        $params = ZUtils::filterBlank($this->getParams());
        if ($params ['type'] == 1)
            $this->assign('outgo_state', 'outgoing');
        else
            $this->assign('outgo_state', 'storage');

        $model = new AllocationExtendModel();
        $response = $model->showData($params);

        $this->ajaxReturn($response, 'json');
    }

    public function importExcel()
    {
        if (IS_POST) {
            $params = $this->getParams();
            $model = new AlloImportExcelModel();
            $import = $model->import();
            if ($import ['code'] == 200) {
                // SKU 是否可调用、数量是否够调用验证
                $alloSku = array_column($import ['data'], 'sku');
                $params ['sku'] = $alloSku;
                // 保存调拨
                $r = $this->searchModel($params, true);
                $error = null;
                // 全部SKU都查询到
                if ($r ['count'] == count($import ['data'])) {
                    foreach ($r ['ret'] as $key => $value) {
                        if ($value['available_for_sale_num_total'] < $import ['data'][$value['SKU_ID']]['num']) {
                            $error [$model->cacheSku [$value['SKU_ID']]] = $value['SKU_ID'] . ':当前需调拨的数量为：' . $import ['data'][$value['SKU_ID']]['num'] . '  大于可调拨数量:' . $value['available_for_sale_num_total'];
                        }
                    }
                } else {
                    $existSku = array_column($r ['ret'], 'SKU_ID');
                    $diff = array_diff($alloSku, (array)$existSku);
                    foreach ($diff as $key => $value) {
                        $error [$model->cacheSku [$value]] = $value . ':未查询到数据';
                    }
                }
                if ($error) {
                    $response = $this->formatOutput(300, L('导入失败'), $error);
                } else {
                    // 勾选调拨
                    $processChild = new TbWmsAlloProcessChildModel();
                    $saveData = null;
                    list($key, $uuid) = explode('_', $params ['token']);
                    foreach ($r ['ret'] as $key => $value) {
                        $tmp = null;
                        $tmp ['uuid'] = $uuid;
                        $tmp ['sku_id'] = $value['SKU_ID'];
                        $tmp ['out_team'] = $params ['sale_team_code'];
                        $tmp ['out_warehouse'] = $params ['warehouse_code'];
                        $tmp ['num'] = $import ['data'][$value['SKU_ID']]['num'];
                        $saveData [] = $tmp;
                    }
                    if ($processChild->addAll($saveData)) {
                        $response = $this->formatOutput(200, L('已自动勾选需调拨的SKU'), null);
                    } else {
                        $response = $this->formatOutput(300, $processChild->getDbError(), null);
                    }
                }
            } else {
                $response = $this->formatOutput($import ['code'], L('导入失败'), $import ['data']);
            }

            $this->ajaxReturn($response, 'json');
        } else {
            $response = $this->formatOutput(300, L('请求异常'), null);

            $this->ajaxReturn($response, 'json');
        }
    }
}