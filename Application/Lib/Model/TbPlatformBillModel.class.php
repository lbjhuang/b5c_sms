<?php
/**
 * 日记账关联模型
 * User: fuming
 * Date: 2018/12/12
 */

@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

class TbPlatformBillModel extends BaseModel
{
    protected $trueTableName = 'tb_platform_bill';
    public $params;
    const BILL_STATUS_BUSINESS_EXAMINE = 'N003180001'; //待业务审核
    const BILL_STATUS_FINANCE_EXAMINE = 'N003180002'; //待财务审核
    const BILL_STATUS_YES = 'N003180003'; //已生效
    const BILL_STATUS_NO = 'N003180004'; //已作废

    protected $_auto = [
        ['created_at', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['updated_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
    ];
    
    public $error_info;
    public $success_info = ['code' => 2000, 'msg' => '操作成功'];
    const B2B_REC = 'N001950200';//B2B收款
    const PUR_LOAN = 'N001950100';//采购贷款

    private $return_exchange = [];

    public function __set($name, $value)
    {
        $this->$name = $value;
        $name = $this->humpToLine($name);
        if ($value !== '' and !is_null($value) and $value !== false)
            $this->params[$name] = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public $totalAmountMoney;
    public $in_come_amounts; //总应核销金额
    public $bill_amount_counts; //已核销金额
    public $wait_cancellation_amounts; //剩余应核销金额
    public $count;
    public $pageIndex;
    public $pageSize;
    public $firstRow;

    /**
     * 数据获取
     */
    public function getList($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;

        is_null($params['pageSize'])  or $pageSize = $params['pageSize'];
        is_null($params['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params['pageIndex'];

        //fields
        $field = [
            "b1.id",
            "b1.platform_bill_no",
            "b1.platform_code",
            "b1.site_code",
            "b1.store_id",
            "b1.s_bill_time",
            "b1.e_bill_time",
            "b1.arrange_bill",
            "b1.original_bill",
            "b1.created_by as import_man",
            "b1.created_at as import_time",
            "b1.business_audit_man",
            "b1.finance_audit_man",
            "b1.business_audit_time",
            "b1.finance_audit_time",
            "b1.bill_status",
            "b1.created_by as import_man",
            "cd1.CD_VAL as platform_name",
            "cd2.CD_VAL as site_name",
            "s1.STORE_NAME as store_name",
        ];
        //query
        $this->subWhere('b1.platform_bill_no', ['eq', $params['platform_bill_no']])
            ->subWhere('b1.platform_code', ['in', $params['platform_code']])
            ->subWhere('b1.site_code', ['in', $params['site_code']])
            ->subWhere('b1.store_id', ['in', $params['store_id']])
            ->subWhere('b1.bill_status', ['in',  $params['bill_status']])
            ->subWhere('b1.business_audit_man', ['in',  $params['business_audit_man']])
            ->subWhere('b1.finance_audit_man', ['in',  $params['finance_audit_man']])
            ->subWhere('b1.business_audit_charge_man', ['in',  $params['business_audit_charge_man']])
            ->subWhere('b1.finance_audit_charge_man', ['in',  $params['finance_audit_charge_man']])
            ->subWhere('b1.created_by', ['in',  $params['import_man']])
            ->subWhere('b1.created_at', ['xrange', [$params['import_time'][0], $params['import_time'][1]]])
            ->subWhere('b1.business_audit_time', ['xrange', [$params['business_audit_time'][0], $params['business_audit_time'][1]]])
            ->subWhere('b1.finance_audit_time', ['xrange', [$params['finance_audit_time'][0], $params['finance_audit_time'][1]]]);
        $this->getBillTimeWhere($params);
        //static::$where['c1.transfer_type'] = ['in', parent::$PAYMENT_TYPE];//收款
        //static::$where['c1.transfer_type'] = ['not in', parent::$PAYMENT_TYPE];//付款

        $join1 = 'left join tb_ms_cmn_cd cd1 on cd1.CD = b1.platform_code';
        $join2 = 'left join tb_ms_cmn_cd cd2 on cd2.CD = b1.site_code';
        $join3 = 'left join tb_ms_store s1 on s1.id = b1.store_id';
        $count = $this->table('tb_platform_bill b1')->where(static::$where) ->count();
        $Page  = new Page($count, $pageSize);
        //exec
        $subQuery = $this->field($field)
            ->table('tb_platform_bill b1')
            ->join($join1)
            ->join($join2)
            ->join($join3)
            ->where(static::$where);
        if ($isExcel == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->order('b1.id desc')->select();

        $this->count            = $count;
        $this->pageIndex        = $pageIndex;
        $this->pageSize         = $pageSize;
        return $ret;
    }

    public function getBillTimeWhere($params)
    {
        $where = null;
        if (!empty($params['bill_time'][0]) && !empty($params['bill_time'][1])) {
            $complex['b1.s_bill_time'] = [['gt', $params['bill_time'][0] . ' 00:00:00'], ['lt', $params['bill_time'][1] . ' 23:59:59'], 'and'];
            $complex['b1.e_bill_time'] = [['gt', $params['bill_time'][0] . ' 00:00:00'], ['lt', $params['bill_time'][1] . ' 23:59:59'], 'and'];
            $complex['_logic'] = 'or';
            static::$where['_complex'] = $complex;
        }
        if (!empty($params['bill_time'][0]) && empty($params['bill_time'][1])) {
            static::$where['b1.s_bill_time'] = ['gt', $params['bill_time'][0] . ' 00:00:00'];
        }
        if (empty($params['bill_time'][0]) && !empty($params['bill_time'][1])) {
            static::$where['b1.e_bill_time'] = ['lt', $params['bill_time'][1] . ' 23:59:59'];
        }
        if (!empty($params['sale_team_cd'])) {
            if (!is_array($params['sale_team_cd'])) $params['sale_team_cd'] = (array) $params['sale_team_cd'];
            $arr = '';
            foreach ($params['sale_team_cd'] as $item) {
                $arr[] = ' s1.SALE_TEAM_CD like "%' . $item . '%" ';
            }
            static::$where['_string'] = '(' . implode(' or ' , $arr) . ')';
        }
        return true;
    }

    //核销列表组装查询条件
    private function getPlatformBillWhere($params)
    {
        $this->subWhere('tb_op_order.STORE_ID', ['in', $params['store_id']])
            ->subWhere('tb_op_order.BWC_ORDER_STATUS', ['in', $params['bill_status']])
            ->subWhere('tb_ms_store.company_cd', ['in', $params['company_code']])
            ->subWhere('bc.created_at', ['xrange', $params['bill_time']]);
        $gp_sites = CodeModel::getGpPlatCds();
        array_push($gp_sites, 'N002620800');

        if (!empty($params['site_code'])) {
            foreach ($params['site_code'] as &$v) {
                if (in_array($params['site_code'], $gp_sites)) {
                    unset($v);
                    continue;
                }
            }
            if (!empty($params['site_code'])) {
                if (!empty($params['platform_code'])) {
                    static::$where['tb_op_order.PLAT_CD'] = ['in', $params['platform_code']];
                } else {
                    $site_cds = array_column(CodeModel::getSiteCodeArr($params['site_code']), 'CD');
                    static::$where['tb_op_order.PLAT_CD'] = ['in', $site_cds];
                }
            }
        }else{
            if (!empty($params['platform_code'])){
                static::$where['tb_op_order.PLAT_CD'] = ['in', $params['platform_code']];
            }
        }

        if (empty($params['zd_date'])) {
            static::$where['tb_op_order.PARENT_ORDER_ID'] = ['exp', 'is null'];
        } else {
            static::$where['bill.relation_type'] = 'N002350300';
            static::$where['bill.zd_date'] = ['between', [$params['zd_date'][0]. ' 00:00:00', $params['zd_date'][1]. ' 23:59:59']];
        }
        $gp_str = "('". implode("', '", $gp_sites). "')";
        $where_str = "(tb_op_order.PLAT_CD not in $gp_str)";
        if (!empty($params['thr_order_no'])) {
            $thr_order_no = $params['thr_order_no'];
            $where_str .= " and tb_op_order.ORDER_NO = '$thr_order_no' ";

//            $condition['_string'] = "ORDER_NO = '$thr_order_no' or ORDER_ID = '$thr_order_no' or B5C_ORDER_NO = '$thr_order_no' or CHILD_ORDER_ID like '$thr_order_no%'";
//            $ids = M('op_order', 'tb_')->where($condition)->getField('ID', true);
//            if (!empty($ids)) {
//                $ids_str = "('". implode("','", $ids). "')";
//                $where_str .= " and tb_op_order.ID in $ids_str ";
//            } else {
//                $where_str .= " and 1 != 1 ";
//            }
//            $where_str .= " and (tb_op_order.ORDER_NO = '$thr_order_no' or
//            tb_op_order.ORDER_ID = '$thr_order_no'or
//            tb_op_order.B5C_ORDER_NO = '$thr_order_no' or
//            tb_op_order.CHILD_ORDER_ID like '%$thr_order_no%') ";
        }
        if (!empty($params['sale_team_cd'])) {
            $temp_str = " and ( ";
            foreach ($params['sale_team_cd'] as $v) {
                $temp_str .= "tb_ms_store.SALE_TEAM_CD like '%$v%' or ";
            }
            $where_str .= trim($temp_str, 'or '). ') ';
        }
        if ($where_str) {
            static::$where['_string'] = $where_str;
        }
    }

    //获取排序规则
    private function getPlatformBillHaving($params)
    {
        $having_str = '';
        if ($params['wait_cancellation_amount'] && count($params['wait_cancellation_amount']) != 3) {
            $having_str = " (";
            foreach ($params['wait_cancellation_amount'] as $v) {
                if ($v == 0) {
                    $having_str .= "ifnull(fs.in_come_amount,0) - ifnull(sum(bc.bill_amount), 0) = 0 or ";
                } else if ($v == 1) {
                    $having_str .= "ifnull(fs.in_come_amount,0) - ifnull(sum(bc.bill_amount), 0) > 0 or ";
                } else if ($v == -1) {
                    $having_str .= "ifnull(fs.in_come_amount,0) - ifnull(sum(bc.bill_amount), 0) < 0 or ";
                }
            }
            $having_str = trim($having_str, 'or ') . ') ';
        }
        return $having_str;
    }

    /**
     * 账单核销数据获取
     */
    public function getBillCancellationList($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;
        is_null($params['pageSize'])  or $pageSize = $params['pageSize'];
        is_null($params['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params['pageIndex'];

        $this->getPlatformBillWhere($params);//组装查询条件
        $having_str = $this->getPlatformBillHaving($params);//having过滤

        $model = new SlaveModel();

        //根据ERP更新时间是否为空，组装不同的sql（出于性能考虑）
        if (empty($params['zd_date'])) {
            $count_query = $model->table('tb_op_order')
                ->field('count(*),fs.*')
                ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ID = bc.order_inc_id and bc.bill_status = 'N003180003'")
                ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                ->where(static::$where)
                ->group('tb_op_order.ID')
                ->having($having_str);
        } else {
            $count_query = $model->table('tb_wms_bill bill')
                ->field('count(*),fs.*')
                ->join("left join tb_op_order on bill.link_b5c_no = tb_op_order.B5C_ORDER_NO")
                ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ORDER_NO = bc.thr_order_no and bc.bill_status = 'N003180003'")
                ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                ->where(static::$where)
                ->group('ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID)')
                ->having($having_str);
        }
        if ($having_str) {
            $count_query->having($having_str);
        }
        $result = $count_query->select();
        $count = count($result);
        $list = [];
        if($count > 0) {
            if (empty($params['zd_date'])) {
                $field = [
                    "0 as search_type",
                    "tb_op_order.PARENT_ORDER_ID",
                    "tb_op_order.ID",
                    "tb_op_order.ORDER_ID",
                    "tb_op_order.ORDER_ID as order_id",
                    "tb_op_order.ORDER_ID as thr_order_no",
                    "tb_op_order.ORDER_NO as order_no",
                    "bc.platform_bill_no",
                    "tb_op_order.PLAT_CD as plat_cd",
                    "tb_ms_store.STORE_NAME as store_name",
                    "tb_ms_store.company_cd",
                    "tb_op_order.BWC_ORDER_STATUS as bwc_order_status",
                    "tb_ms_store.SALE_TEAM_CD",
                    "tb_op_order.PAY_CURRENCY as currency",
//                    "tb_op_order.PAY_TOTAL_PRICE as in_come_amount",
                    "tb_op_order.B5C_ORDER_NO as b5c_order_no",
                    "sum(bc.bill_amount) as bill_amount_count",
                    "max(bc.created_at) as created_at",
                    "cd.CD_VAL as site_name",
                    "tb_op_order.PLAT_NAME as platform_name",
                    "tb_op_order.CHILD_ORDER_ID as child_order_id",
//                    "if(isnull(tb_op_order.CHILD_ORDER_ID),bill.zd_date, (SELECT max(zd_date) from tb_wms_bill where relation_type = 'N002350300' and FIND_IN_SET(link_bill_id,CHILD_ORDER_ID))) zd_date",
//                    '(ifnull(sum(fs.ex_tax_money), 0) + ifnull(sum(fs.sale_vat), 0)) as in_come_amount',
                    "fs.*",
                    "if(isnull(max(fs.create_time)),bill.zd_date, max(fs.create_time)) as zd_date"//收入成本报表create_time字段可能为空
                ];
                $query = $model->table('tb_op_order')
                    ->field($field)
                    ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ID = bc.order_inc_id and bc.bill_status = 'N003180003'")
                    ->join("left join tb_wms_bill bill on  bill.relation_type = 'N002350300' and bill.link_b5c_no = tb_op_order.B5C_ORDER_NO")
                    ->join("left join tb_fin_tax_rate_part ftr on ftr.area_id = tb_op_order.ADDRESS_USER_COUNTRY_ID AND ftr.`start` <= bill.zd_date and (ftr.`end` >= bill.zd_date OR ftr.`end` IS NULL)")
                    ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                    ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                    ->join("left join tb_ms_cmn_cd cd ON cd.CD = tb_op_order.PLAT_CD")
                    ->where(static::$where)
                    ->group('tb_op_order.ID')
                    ->order('tb_op_order.ID desc');
//                    ->order('tb_op_order.ORDER_CREATE_TIME desc');
            } else {
                $field = [
                    "1 as search_type",
                    "tb_op_order.PARENT_ORDER_ID",
                    "tb_op_order.ID",
                    "tb_op_order.ORDER_ID",
                    "ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID) as order_id",
                    "ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID) as thr_order_no",
                    "tb_op_order.ORDER_NO as order_no",
                    "bc.platform_bill_no",
                    "tb_op_order.PLAT_CD as plat_cd",
                    "tb_ms_store.STORE_NAME as store_name",
                    "tb_ms_store.company_cd",
                    "tb_op_order.BWC_ORDER_STATUS as bwc_order_status",
                    "tb_ms_store.SALE_TEAM_CD",
                    "tb_op_order.PAY_CURRENCY as currency",
//                    "tb_op_order.PAY_TOTAL_PRICE as in_come_amount",
                    "tb_op_order.B5C_ORDER_NO as b5c_order_no",
                    "sum(bc.bill_amount) as bill_amount_count",
                    "max(bc.created_at) as created_at",
                    "cd.CD_VAL as site_name",
                    "tb_op_order.PLAT_NAME as platform_name",
                    "tb_op_order.CHILD_ORDER_ID as child_order_id",
//                    "max(bill.zd_date) zd_date",
//                    '(ifnull(sum(fs.ex_tax_money), 0) + ifnull(sum(fs.sale_vat), 0)) as in_come_amount',
                    "fs.*",
                    "if(isnull(max(fs.create_time)),bill.zd_date, max(fs.create_time)) as zd_date"//收入成本报表create_time字段可能为空
                ];
                $query = $model->table('tb_wms_bill bill')
                    ->field($field)
                    ->join("left join tb_op_order on bill.link_b5c_no = tb_op_order.B5C_ORDER_NO")
                    ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ORDER_NO = bc.thr_order_no and bc.bill_status = 'N003180003'")
                    ->join("left join tb_fin_tax_rate_part ftr on ftr.area_id = tb_op_order.ADDRESS_USER_COUNTRY_ID AND ftr.`start` <= bill.zd_date and (ftr.`end` >= bill.zd_date OR ftr.`end` IS NULL)")
                    ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                    ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                    ->join("left join tb_ms_cmn_cd cd ON cd.CD = tb_op_order.PLAT_CD")
                    ->where(static::$where)
                    ->group('ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID)')
                    ->order('tb_op_order.ID desc');
//                    ->order('tb_op_order.ORDER_CREATE_TIME desc');
            }
            if (false === $isExcel) {
                $query->limit($pageSize * ($pageIndex - 1), $pageSize);
            }
            if ($having_str) {
                $query->having($having_str);
            }

            $list            = $query->select();
            $list            = CodeModel::autoCodeTwoVal($list, ['company_cd', 'bwc_order_status']);
            $sale_team       = BaseModel::saleTeamCd();
            $sale_team_cds   = array_keys($sale_team);
            $sale_team_names = array_values($sale_team);
            $plat_cds        = array_column($list, 'plat_cd');
            //过滤gp平台
            $site_cds = array_values(
                array_filter($plat_cds, function ($cd) {
                    if (false === strpos($cd, 'N00083')) {
                        return false;
                    }
                    return true;
                })
            );
            if (!empty($site_cds)) {
                $etc_map  = M('cmn_cd', 'tb_ms_')->where(['CD' => ['in', $site_cds]])->getField('CD,ETC3');
                $plat_map = M('cmn_cd', 'tb_ms_')->where(['CD' => ['like', 'N00262%']])->getField('CD,CD_VAL');
            }
            unset($sale_team);
            foreach ($list as &$item) {
                $item['sale_team_name']           = str_replace($sale_team_cds, $sale_team_names, $item['SALE_TEAM_CD']);
                $item['company_name']             = $item['company_cd_val'];
                $item['in_come_amount']           = $item['in_come_amount'] ?: '0.00';
                $item['bill_amount_count']        = $item['bill_amount_count'] ?: '0.00';
                $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'] ?: '0.00';;//待核销收入
                $item['platform_name']            = $plat_map[$etc_map[$item['plat_cd']]] ?: platform_name;
                //判断获取最新ERP收入更新时间
                $parent_order_id = $item['PARENT_ORDER_ID'];
                if (!empty($parent_order_id)) {
                    $child_order_id = M('op_order', 'tb_')->where(['ORDER_ID' => $parent_order_id])->getField('child_order_id');
                }
                if (!empty($child_order_id) || !empty($item['child_order_id'])) {
                    //有子单，取最新的时间
                    $child_order_id = $child_order_id ? : $item['child_order_id'];
                    $child_order_id_arr = explode(',', $child_order_id);
                    $child_order_id_str = "('". implode("','", $child_order_id_arr). "')";
                    $res = $model->query("SELECT max(zd_date) as zd_date from tb_wms_bill where relation_type = 'N002350300' and link_bill_id in $child_order_id_str");
                    $item['zd_date'] = $res[0]['zd_date'];
                }
                if (!empty($child_order_id) && $item['search_type'] == '1') {
                    //有子单，并且是tb_wms_bill作为基表，需要重新计算待核销金额（因为数据重复分组了，有多少个子单出库就重复多少次）
                    $child_order_id_arr = explode(',', $child_order_id);
                    $child_order_id_str = "('". implode("','", $child_order_id_arr). "')";
                    $res = $model->query("SELECT count(*) as count from tb_wms_bill where relation_type = 'N002350300' and link_bill_id in $child_order_id_str");
                    if ($res[0]['count'] > 1 && $item['bill_amount_count'] != 0) {
                        $item['bill_amount_count'] = bcdiv($item['bill_amount_count'], $res[0]['count'], 2);
                        $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'];
                    }
                } else if (!empty($child_order_id) && $item['search_type'] == '0') {
                    //有子单，并且是tb_op_order作为基表，需要重新计算erp收入
                    $child_order_id_arr = explode(',', $child_order_id);
                    $child_order_id_str = "('". implode("','", $child_order_id_arr). "')";
                    $res = $model->query("SELECT sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)) as in_come_amount from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') and sale_order_no in $child_order_id_str");
                    $item['in_come_amount'] = $res[0]['in_come_amount'] ? : 0;
                    $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'];
                }
                $item['bill_amount_count'] = bcadd($item['bill_amount_count'], 0 ,2);
                $item['wait_cancellation_amount'] = bcadd($item['wait_cancellation_amount'], 0 ,2);
                $item['in_come_amount'] = bcadd($item['in_come_amount'], 0 ,2);
            }
        }
        $this->count     = $count;
        $this->pageIndex = $pageIndex;
        $this->pageSize  = $pageSize;
        return $list;
    }

    //tpp核销列表数据汇总
    public function getPlatformBillCount($params)
    {
        
        $this->getPlatformBillWhere($params);//组装查询条件
        $having_str = $this->getPlatformBillHaving($params);//having过滤

        $model = new SlaveModel();
        $xchr_pay_currency = StringModel::getXchrCurrency('tb_op_order.PAY_CURRENCY');

        $today_time = date('Ymd');
        if (empty($params['zd_date'])) {
            $count_query = $model->table('tb_op_order')
                ->field([
                    "fs.*",
                    "0 as search_type",
                    "tb_op_order.PARENT_ORDER_ID",
                    "tb_op_order.CHILD_ORDER_ID",
                    "tb_op_order.PAY_CURRENCY",
                    'tb_op_order.ID',
                    'tb_op_order.PAY_TOTAL_PRICE',
//                    "if(tb_op_order.PAY_CURRENCY = 'USD',ifnull(sum(fs.ex_tax_money),0) + ifnull(sum(fs.sale_vat),0),(ifnull(sum(fs.ex_tax_money),0) + ifnull(sum(fs.sale_vat),0)) * {$xchr_pay_currency}) as in_come_amount",
                    "if(tb_op_order.PAY_CURRENCY = 'USD',fs.in_come_amount,fs.in_come_amount * {$xchr_pay_currency}) as in_come_amount",
                    "if(tb_op_order.PAY_CURRENCY = 'USD',ifnull(sum(bc.bill_amount),0),ifnull(sum(bc.bill_amount  * {$xchr_pay_currency}),0)) as bill_amount_count"
                ])
                ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ID = bc.order_inc_id and bc.bill_status = 'N003180003'")
                ->join("left join tb_wms_bill bill on  bill.relation_type = 'N002350300' and bill.link_b5c_no = tb_op_order.B5C_ORDER_NO")
                ->join("left join tb_fin_tax_rate_part ftr on ftr.area_id = tb_op_order.ADDRESS_USER_COUNTRY_ID AND ftr.`start` <= bill.zd_date and (ftr.`end` >= bill.zd_date OR ftr.`end` IS NULL)")
                ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                ->join("left join tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = '{$today_time}'")
                ->where(static::$where)
                ->group('tb_op_order.ID');
        } else {
            $count_query = $model->table('tb_wms_bill bill')
                ->field([
                    "fs.*",
                    "1 as search_type",
                    "tb_op_order.PARENT_ORDER_ID",
                    "tb_op_order.CHILD_ORDER_ID",
                    "tb_op_order.PAY_CURRENCY",
                    'tb_op_order.ID',
                    'tb_op_order.PAY_TOTAL_PRICE',
//                    "if(tb_op_order.PAY_CURRENCY = 'USD',ifnull(sum(fs.ex_tax_money),0) + ifnull(sum(fs.sale_vat),0),(ifnull(sum(fs.ex_tax_money),0) + ifnull(sum(fs.sale_vat),0)) * {$xchr_pay_currency}) as in_come_amount",
                    "if(tb_op_order.PAY_CURRENCY = 'USD',fs.in_come_amount,fs.in_come_amount * {$xchr_pay_currency}) as in_come_amount",
                    "if(tb_op_order.PAY_CURRENCY = 'USD',ifnull(sum(bc.bill_amount),0),ifnull(sum(bc.bill_amount  * {$xchr_pay_currency}),0)) as bill_amount_count"
                ])
                ->join("left join tb_op_order on bill.link_b5c_no = tb_op_order.B5C_ORDER_NO")
                ->join("left join tb_platform_bill_cancellation bc on tb_op_order.ORDER_NO = bc.thr_order_no and bc.bill_status = 'N003180003'")
                ->join("left join tb_fin_tax_rate_part ftr on ftr.area_id = tb_op_order.ADDRESS_USER_COUNTRY_ID AND ftr.`start` <= bill.zd_date and (ftr.`end` >= bill.zd_date OR ftr.`end` IS NULL)")
                ->join("left join (select ifnull(sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)), 0) AS in_come_amount,create_time,sale_order_no from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') group by sale_order_no) fs on tb_op_order.ORDER_ID = fs.sale_order_no")
                ->join("left join tb_ms_store ON tb_ms_store.ID = tb_op_order.STORE_ID")
                ->join("left join tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = '{$today_time}'")
                ->where(static::$where)
                ->group('ifnull(tb_op_order.PARENT_ORDER_ID, tb_op_order.ORDER_ID)');
        }
        if ($having_str) {
            $count_query->having($having_str);
        }
        $count_list = $count_query->select();

        foreach ($count_list as &$item) {
            $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'];//待核销收入
            $parent_order_id = $item['PARENT_ORDER_ID'];
            $child_order_id = $item['CHILD_ORDER_ID'];
            if (empty($child_order_id) && !empty($parent_order_id)) {
                $child_order_id = M('op_order', 'tb_')->where(['ORDER_ID' => $parent_order_id])->getField('CHILD_ORDER_ID');
            }
            if (!empty($child_order_id) && $item['search_type'] == '1') {
                //有子单，并且是tb_wms_bill作为基表，需要重新计算待核销金额（因为数据重复分组了，有多少个子单出库就重复多少次）
                $child_order_id_arr = explode(',', $child_order_id);
                $child_order_id_str = "('". implode("','", $child_order_id_arr). "')";
                $res = $model->query("SELECT count(*) as count from tb_wms_bill where relation_type = 'N002350300' and link_bill_id in $child_order_id_str");
                if ($res[0]['count'] > 1 && $item['bill_amount_count'] != 0) {
                    $item['bill_amount_count'] = bcdiv($item['bill_amount_count'], $res[0]['count'], 2);
                    $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'];
                }
            } else if (!empty($child_order_id) && $item['search_type'] == '0') {
                //有子单，并且是tb_op_order作为基表，需要重新计算erp收入
                $child_order_id_arr = explode(',', $child_order_id);
                $child_order_id_str = "('". implode("','", $child_order_id_arr). "')";
                $res = $model->query("SELECT sum(ifnull(ex_tax_money,0)+ifnull(sale_vat,0)) as in_come_amount from tb_fin_revenue_stream where source_channel_code in ('N002810002','N002810004','N002810005') and sale_order_no in $child_order_id_str");
                $item['in_come_amount'] = $res[0]['in_come_amount'] ? : 0;
                $item['wait_cancellation_amount'] = $item['in_come_amount'] - $item['bill_amount_count'];
            }
        }
        $this->getAmountData($count_list);
    }

    /**
     * 账单核销数据获取
     */
    public function getBillCancellationListNew($params, $isExcel = false)
    {
        $bill_cancellation = $this->getBillCancellation();
        $thr_order_no = array_filter(array_column($bill_cancellation, 'thr_order_no'));
        $params['thr_order_no'] = $thr_order_no;
        $ret = $this->bill_cancellation_data($params, false);
        $ret = $this->addCancellationData($ret, $params);
        $list = [];
        //数据量较大的时候
        //$num = 5000;
        $num = 20;
        if ($this->count < $num) {
            $list = $this->spliceCancellationInCome($ret, $params);
        } else {
            $total = ceil($this->count / $num);
            for ($i = 0; $i < $total; $i ++) {
                $tmp = array_slice($ret, $i * $num, $num);
                $data = $this->spliceCancellationInCome($tmp, $params);
                $list = array_merge($list, $data);
            }
        }
        $list = !$isExcel ? array_slice($list, $this->firstRow, $this->pageSize) : $list;

        return $list;
    }

    /**
     * 拼接账单核销-收入数据
     */
    public function spliceCancellationInCome($data, $params)
    {
        $sale_team = BaseModel::saleTeamCd();
        $sale_team_cds = array_keys($sale_team);
        $sale_team_names = array_values($sale_team);
        $sale_nos = array_filter(array_column($data, 'thr_order_no'));
        //获取收入报表数据
        $list = [];
        $request['sale_no'] = $sale_nos;
        $request['isExport'] = true;
        empty($params['zd_date']) or $request['zd_date'] = $params['zd_date'];
        $in_come = $this->getInComeList($request);
        foreach ($data as $key => $item) {
            //ERP收入更新日期筛选过滤
            if (!empty($params['zd_date']) && !isset($in_come[$item['thr_order_no']])) {
                continue;
            }
            $item['currency'] = $in_come[$item['thr_order_no']]['currency'];
            $item['zd_date'] = $in_come[$item['thr_order_no']]['zd_date'];
            $item['in_come_amount'] = $in_come[$item['thr_order_no']]['sale_amount_no_tax'] + $data[$item['thr_order_no']]['tax'];
            $item['wait_cancellation_amount'] = $item['bill_amount_count'] - $item['in_come_amount'];
            $item['number_type'] = $item['wait_cancellation_amount'] > 0 ? 1 : -1;
            $item['sale_team_name'] = str_replace($sale_team_cds, $sale_team_names, $item['sale_team_cd']);
            $list[] = $item;
        }
        $list = $this->filter_wait_cancellation_amount($list, $params);
        $this->count = count($list);
        return $list;
    }

    /**
     * 过滤待核销收入
     */
    public function filter_wait_cancellation_amount($list, $params)
    {
        //  【待核销收入】，选项为正、负、0，可多选
        $inventory_type_one = "";
        if (count($params['wait_cancellation_amount']) == 1){
            foreach ($params['wait_cancellation_amount'] as $value){
                $inventory_type_one = $value;
            }
        }
        $inventory_type_two = "";
        if (count($params['wait_cancellation_amount']) == 2){
            $inventory_data = ['1','-1','0'];
            $inventory_diff = array_diff($inventory_data,$params['wait_cancellation_amount']);
            foreach ($inventory_diff as $value){
                $inventory_type_two = $value;
            }
        }
        foreach ($list as $key => $item) {

            //根据待核销收入过滤
            switch ($inventory_type_one){
                case '1' :
                    if ($item['wait_cancellation_amount'] <= 0) unset($list[$key]);
                    break;
                case '-1' :
                    if ($item['wait_cancellation_amount'] >= 0) unset($list[$key]);
                    break;
                case '0' :
                    if ($item['wait_cancellation_amount'] != 0) unset($list[$key]);
                    break;
            }
            switch ($inventory_type_two){
                case '1' :
                    if ($item['wait_cancellation_amount'] > 0) unset($list[$key]);
                    break;
                case '-1' :
                    if ($item['wait_cancellation_amount'] < 0) unset($list[$key]);
                    break;
                case '0' :
                    if ($item['wait_cancellation_amount'] == 0) unset($list[$key]);
                    break;
            }
            if (isset($list[$key])) {
                $rate_curreny =  exchangeRateConversion($item['currency'], 'USD', date('Y-m-d'));
                $this->in_come_amounts += $item['in_come_amount'] * $rate_curreny;
                $this->bill_amount_counts += $item['bill_amount_count'] * $rate_curreny;
                $this->wait_cancellation_amounts += $item['wait_cancellation_amount'] * $rate_curreny;
            }
        }
        return $list;
    }

    /**
     * 账单核销数据获取
     */
    public function getBillCancellationDetail($params, $isExcel = false)
    {
        $params['is_detail'] = true;
        $ret = $this->getBillCancellationList($params);
        $child_order_id = $ret[0]['child_order_id'];
        $op_order_id = $ret[0]['ORDER_ID'];
        if (empty($child_order_id)) {
            $sale_nos = array_column($ret, 'b5c_order_no');
        } else {
            $child_order_id = explode(',', $ret[0]['child_order_id']);
            $sale_nos = M('order', 'tb_op_')->where(['ORDER_ID'=>['IN',$child_order_id], 'PLAT_CD'=>$ret[0]['plat_cd']])->getField('B5C_ORDER_NO', true);
        }
        $platform_bill_nos = array_column($ret, 'platform_bill_no');
        //获取收入报表数据
        $in_come_list = [];
        if (!empty(array_filter($sale_nos))) {
            //$in_come_list = $this->getInComeList(['sale_no' => $sale_nos, 'isExport' => $isExcel], true);
            $in_come_list = $this->getInComeCostList($op_order_id);
        }
        //$where ['c1.platform_bill_no'] = ['in', $platform_bill_nos];
        $where ['c1.thr_order_no'] = ['in', array_column($ret, 'order_id')];
        $where ['b1.bill_status'] = self::BILL_STATUS_YES;
        $bill_cancellation_list = $this->getBillCancellationData($where);
        if (!empty($bill_cancellation_list)) {
            $bill_amount_counts = array_sum(array_column($bill_cancellation_list, 'bill_amount'));
        }
        $list['bill_cancellation'] = $ret[0];
        $list['in_come_data'] = $in_come_list;
        $list['bill_cancellation_list'] = $bill_cancellation_list;
        $this->bill_amount_counts = $bill_amount_counts ? $bill_amount_counts : 0;
        return $list;
    }

    /**
     * 账单核销数据获取
     */
    public function getBillCancellationData($where)
    {
        $ret = M('platform_bill_cancellation', 'tb_')
            ->alias('c1')
            ->field([
                "c1.id",
                "c1.platform_bill_no",
                "b1.platform_code",
                "b1.site_code",
                "b1.store_id",
                "b1.s_bill_time",
                "b1.e_bill_time",
                "b1.business_audit_time",
                "b1.finance_audit_time",
                "b1.business_audit_charge_man",
                "b1.finance_audit_charge_man",
                "b1.bill_status",
                "c1.thr_order_no",
                "c1.bill_amount",
                "c1.created_by",
                "c1.created_at",
                "b1.business_audit_man",
                "b1.finance_audit_man",
            ])
            ->join('left join tb_platform_bill b1 on b1.id = c1.platform_bill_id')
            ->where($where)->order('b1.s_bill_time asc, b1.e_bill_time asc')->select();
        return $ret;
    }

    /**
     * 账单核销数据获取
     */
    public function bill_cancellation_data_old($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;
        is_null($params['pageSize'])  or $pageSize = $params['pageSize'];
        is_null($params['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params['pageIndex'];

        //fields
        $field = [
            "b1.id",
            "b1.platform_bill_no",
            "b1.platform_code",
            "b1.site_code",
            "b1.store_id",
            "b1.business_audit_time",
            "b1.finance_audit_time",
            "b1.bill_status",
            "c1.thr_order_no",
            "sum(c1.bill_amount) as bill_amount_count",
            "max(c1.created_at) as created_at",
            "cd1.CD_VAL as platform_name",
            "cd2.CD_VAL as site_name",
            "s1.STORE_NAME as store_name",
            "s1.SALE_TEAM_CD sale_team_cd",
            "cd3.CD_VAL as company_name",
        ];
        //query
        $this->subWhere('b1.platform_code', ['in', $params['platform_code']])
            ->subWhere('b1.site_code', ['in', $params['site_code']])
            ->subWhere('b1.store_id', ['in', $params['store_id']])
            ->subWhere('s1.company_cd', ['in', $params['company_code']])
            //->subWhere('s1.SALE_TEAM_CD', ['like', $params['sale_team_cd']])
            ->subWhere('c1.thr_order_no', ['eq', $params['thr_order_no']])
            ->subWhere('b1.bill_status', ['in',  $params['bill_status']])
            ->subWhere('b1.business_audit_man', ['eq',  $params['business_audit_man']])
            ->subWhere('b1.finance_audit_man', ['eq',  $params['finance_audit_man']])
            ->subWhere('b1.business_audit_charge_man', ['eq',  $params['business_audit_charge_man']])
            ->subWhere('b1.finance_audit_charge_man', ['eq',  $params['finance_audit_charge_man']])
            ->subWhere('b1.created_by', ['eq',  $params['import_man']])
            ->subWhere('b1.created_at', ['xrange', [$params['s_import_at'], $params['e_import_at']]])
            ->subWhere('b1.business_audit_time', ['xrange', [$params['s_business_audit_time'], $params['e_business_audit_time']]])
            ->subWhere('b1.finance_audit_time', ['xrange', [$params['s_finance_audit_time'], $params['e_finance_audit_time']]]);
        $this->getBillTimeWhere($params);

        $join1 = 'left join tb_platform_bill_cancellation c1 on c1.platform_bill_id = b1.id and c1.thr_order_no <> ""';
        $join2 = 'left join tb_ms_cmn_cd cd1 on cd1.CD = b1.platform_code';
        $join3 = 'left join tb_ms_cmn_cd cd2 on cd2.CD = b1.site_code';
        $join4 = 'left join tb_ms_store s1 on s1.id = b1.store_id';
        $join5 = 'left join tb_ms_cmn_cd cd3 on cd3.CD = s1.company_cd';
        $subSql = $this->field(['b1.id'])->table('tb_platform_bill b1')->join($join1)->join($join4)
            ->where(static::$where)->group('c1.thr_order_no')->buildSql();
        $count = M()->table($subSql . ' tmp')->count();
        $Page  = new Page($count, $pageSize);
        //exec
        $subQuery = $this->field($field)
            ->table('tb_platform_bill b1')
            ->join($join1)
            ->join($join2)
            ->join($join3)
            ->join($join4)
            ->join($join5)
            ->where(static::$where)
            ->group('c1.thr_order_no');
        if ($isExcel == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->order('c1.id desc')->select();
        $this->count            = $count;
        $this->pageIndex        = $pageIndex;
        $this->pageSize         = $pageSize;
        $this->firstRow         = $Page->firstRow;
        return $ret;
    }

    /**
     * 账单核销数据获取
     */
    public function bill_cancellation_data($params, $isExcel = false)
    {
        $pageSize = 20;
        $pageIndex = $_GET ['p'] = $_POST ['p'] = 1;
        is_null($params['pageSize'])  or $pageSize = $params['pageSize'];
        is_null($params['pageIndex']) or $_GET ['p'] = $_POST ['p'] = $pageIndex = $params['pageIndex'];
        //fields
        $field = [
            "o1.ID",
            "o1.ORDER_ID",
            "o1.ORDER_NO",
            "o1.B5C_ORDER_NO",
            "o1.PLAT_CD",
            "o1.STORE_ID",
            "o1.ORDER_NO",
            "o1.BWC_ORDER_STATUS",
            "cd1.ETC3",
            "cd2.CD_VAL as site_name",
            "cd1.CD_VAL as platform_name",
            "s1.STORE_NAME as store_name",
            "s1.SALE_TEAM_CD sale_team_cd",
            "cd3.CD_VAL as company_name",
        ];
        //query
        $this->subWhere('o1.PLAT_CD', ['in', $params['platform_code']])
            ->subWhere('o1.site_code', ['in', $params['site_code']])
            ->subWhere('o1.STORE_ID', ['in', $params['store_id']])
            ->subWhere('o1.BWC_ORDER_STATUS', ['eq', $params['order_status']])
            ->subWhere('s1.company_cd', ['in', $params['company_code']]);

        if (!empty($params['thr_order_no'])) {
            if (!is_array($params['thr_order_no'])) $params['thr_order_no'] = (array) $params['thr_order_no'];
            $str = implode('" , "' , $params['thr_order_no']);
            static::$where['_string'] = ' o1.ORDER_ID in ("' . $str . '") ' . ' or ' . ' o1.ORDER_NO in ("' . $str . '") ' . ' or ' . ' o1.B5C_ORDER_NO in ("' . $str . '") ';
        }

        $subSql = $this->field(['o1.ID'])->table('tb_op_order o1')
            ->where(static::$where)->buildSql();
        $count = M()->table($subSql . ' tmp')->count();
        $Page  = new Page($count, $pageSize);
        $join1 = 'left join tb_ms_cmn_cd cd1 on cd1.CD = o1.PLAT_CD';
        $join2 = 'left join tb_ms_store s1 on s1.id = o1.STORE_ID';
        $join3 = 'left join tb_ms_cmn_cd cd2 on cd2.CD = cd1.ETC3';
        $join4 = 'left join tb_ms_cmn_cd cd3 on cd3.CD = s1.company_cd';
        $subQuery = $this->field($field)
            ->table('tb_op_order o1')
            ->join($join1)
            ->join($join2)
            ->join($join3)
            ->join($join4)
            ->where(static::$where);
        if ($isExcel == false)
            $subQuery->limit($Page->firstRow, $Page->listRows);
        $ret = $subQuery->order('o1.ID desc')->select();
        $this->count     = $count;
        $this->pageIndex = $pageIndex;
        $this->pageSize  = $pageSize;
        $this->firstRow  = $Page->firstRow;
        return $ret;
    }

    /**
     * 账单核销获取收入报表数据
     */
    public function addCancellationData($data, $params)
    {
        //获取账单核销数据
        $bill_cancellation = $this->getBillCancellation($params);
        $list = [];
        foreach ($data as $key => $item) {
            //ERP收入更新日期筛选过滤
            if (isset($bill_cancellation[$item['ORDER_NO']])) {
                $item['bill_amount_count'] = $bill_cancellation[$item['ORDER_NO']]['bill_amount_count'];
                $item['created_at'] = $bill_cancellation[$item['ORDER_NO']]['created_at'];
                $item['thr_order_no'] = $item['ORDER_NO'];
            }
            if (isset($bill_cancellation[$item['B5C_ORDER_NO']])) {
                $item['bill_amount_count'] = $bill_cancellation[$item['B5C_ORDER_NO']]['bill_amount_count'];
                $item['created_at'] = $bill_cancellation[$item['B5C_ORDER_NO']]['created_at'];
                $item['thr_order_no'] = $item['B5C_ORDER_NO'];
            }
            if (isset($bill_cancellation[$item['ORDER_ID']])) {
                $item['bill_amount_count'] = $bill_cancellation[$item['ORDER_ID']]['bill_amount_count'];
                $item['created_at'] = $bill_cancellation[$item['ORDER_ID']]['created_at'];
                $item['thr_order_no'] = $item['ORDER_ID'];
            }

            $list[] = $item;
        }
        return $list;
    }

    /**
     * 账单核销数据获取
     */
    public function getBillCancellation($params = [])
    {
        $field = [
            "b1.id",
            "b1.bill_status",
            "c1.thr_order_no",
            "sum(c1.bill_amount) as bill_amount_count",
            "max(c1.created_at) as created_at"
        ];
        //query
        $where = [];
        empty($params['thr_order_no']) or $where['c1.thr_order_no'] = ['in', $params['thr_order_no']];
        empty($params['bill_status']) or $where['b1.bill_status'] = ['in', $params['bill_status']];

        $this->getBillTimeWhere($params);
        $subQuery = $this->field($field)
            ->table('tb_platform_bill b1')
            ->join('left join tb_platform_bill_cancellation c1 on c1.platform_bill_id = b1.id and c1.thr_order_no <> ""')
            ->where($where)
            ->group('c1.thr_order_no');
        $ret = $subQuery->order('c1.id desc')->select();
        $list = [];
        foreach ($ret as $key => $item) {
            $list[$item['thr_order_no']] = $item;
        }
        return $list;
    }

    /**
     * 账单核销获取收入报表数据
     */
    public function getInComeList($params, $flag = false)
    {
        $params['page_size'] = 15000;
        if ($params['isExport'] == false) {
            $params['page_size'] = 500;
        }
        $params['page'] = 1;
        $logic = D('Report/Income', 'Logic');
        $logic->listData($params);
        $data = $logic->getRet();
        $list = [];
        if (empty($data['data']['list'])) return [];
        if ($flag) {
            return $data['data']['list'];
        }
        foreach ($data['data']['list'] as $key => $item) {
            if (!isset($list[$item['sale_no']])) {
                $list[$item['sale_no']] = $item;
            } else {
                $list[$item['sale_no']]['sale_amount_no_tax'] += $item['sale_amount_no_tax'];
                $list[$item['sale_no']]['tax'] += $item['tax'];
                if ($list[$item['sale_no']]['zd_date'] < $item['zd_date']) {
                    //$list[$item['sale_no']]['zd_date'] = $item['zd_date'];
                }
            }
        }
       return $list;
   }

    /**
     * 账单核销获取收入报表数据
     */
    public function getInComeCostList($op_order_id)
    {
        if (empty($op_order_id)) return [];
        $where = [
            'sale_order_no' => $op_order_id,
            'source_channel_code' => ['in', ['N002810004', 'N002810005', 'N002810002']],
        ];
        //获取成本收入数据
        $list = M('fin_revenue_stream', 'tb_')
            ->field('tb_fin_revenue_stream.*, cd.CD_VAL warehouse_name, tb_op_order_guds.B5C_SKU_ID sku_id')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_fin_revenue_stream.sale_order_no AND tb_op_order_guds.PLAT_CD = tb_fin_revenue_stream.plat_cd')
            ->join("left join tb_ms_cmn_cd cd ON cd.CD = tb_fin_revenue_stream.warehouse_code")
            ->where($where)->select();
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes', 'image_url', 'upc_id', 'product_sku']);
        foreach ($list as $key => $item) {
            $list[$key]['bill_no'] = $item['store_relation_id'];
            $list[$key]['sale_amount_no_tax'] = $item['ex_tax_money'];
            $list[$key]['tax_rate'] = ($item['sale_vat_rate'] * 100) . '%';
            $list[$key]['tax'] = $item['sale_vat'];
            $list[$key]['zd_date'] = $item['create_time'];
            $list[$key]['amount_sum'] = $item['ex_tax_money'] + $item['sale_vat'];
            $list[$key]['batch_code'] = $item['batch_code'];
            $list[$key]['warehouse'] = $item['warehouse_name'];
            $list[$key]['send_num'] = $item['use_num'];
            $list[$key]['zd_user'] = $item['operator'];
            $list[$key]['upc_id'] = $item['product_sku']['upc_id'];
        }
       return $list;
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
                    case 'in':
                        static::$where [$key] = $str;
                        break;
                    default:
                        static::$where [$key] = $val;
                        break;
                }
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

    public function platform_bill_add($params)
    {
        $this->platform_bill_no = $this->createPlatformBillNO();
        $this->platform_code = $params['platform_code'];
        $this->site_code = $params['site_code'];
        $this->store_id = $params['store_id'];
        $this->s_bill_time = $params['bill_time'][0];
        $this->e_bill_time = $params['bill_time'][1];
        $this->arrange_bill = $params['arrange_bill'][0]['savename'];
        $this->original_bill = $params['original_bill'][0]['savename'];
        $this->business_audit_man = $params['business_audit_man'];
        $this->finance_audit_man = $params['finance_audit_man'];
        $this->business_audit_time = $params['business_audit_time'];
        $this->finance_audit_time = $params['finance_audit_time'];
        $businessChargeMan = $this->getChargeManByStoreId($params['store_id']);
        $this->business_audit_charge_man = $businessChargeMan['M_NAME'] ? $businessChargeMan['M_NAME'] : '';
        $this->finance_audit_charge_man = $params['finance_audit_charge_man'] ? $params['finance_audit_charge_man'] : 'Astor.Zhang';
        $this->bill_status = self::BILL_STATUS_BUSINESS_EXAMINE;
        $params['import_man'] = $_SESSION['m_loginname'];
        $params['import_time'] = date('Y-m-d H:i:s',time());
        $this->created_by = $_SESSION['m_loginname'];
        $this->updated_by = $_SESSION['m_loginname'];
        if ($id = $this->add($this->create($this->params))) {
            return $id;
        }
        return false;
    }

    //账单编辑
    public function platform_bill_save($params)
    {
        if ($params['bill_status'] == self::BILL_STATUS_FINANCE_EXAMINE) {
            $params['business_audit_man'] = $_SESSION['m_loginname'];
            $params['business_audit_time'] = date('Y-m-d H:i:s',time());
        }
        if ($params['bill_status'] == self::BILL_STATUS_YES) {
            $params['finance_audit_man'] = $_SESSION['m_loginname'];
            $params['finance_audit_time'] = date('Y-m-d H:i:s',time());
        }
        if ($params['bill_status'] == self::BILL_STATUS_NO) {
            $params['deleted_by'] = $_SESSION['m_loginname'];
            $params['deleted_at'] = date('Y-m-d H:i:s',time());
        }
        $this->platform_code = $params['platform_code'];
        $this->site_code = $params['site_code'];
        $this->store_id = $params['store_id'];
        $this->s_bill_time = $params['bill_time'][0];
        $this->e_bill_time = $params['bill_time'][1];
        $this->arrange_bill = $params['arrange_bill'];
        $this->original_bill = $params['original_bill'];
        $this->business_audit_man = $params['business_audit_man'];
        $this->finance_audit_man = $params['finance_audit_man'];
        $this->business_audit_time = $params['business_audit_time'];
        $this->finance_audit_time = $params['finance_audit_time'];
        //$this->business_audit_charge_man = $params['business_audit_charge_man'];
        //$this->finance_audit_charge_man = $params['finance_audit_charge_man'];
        $this->bill_status = $params['bill_status'];
        $where['id'] = $params['platform_bill_id'];
        $res = $this->where($where)->save($this->create($this->params));
        if ($res) {
            return $this->synBillStatusToCancellation($params['platform_bill_id'], $params['bill_status']);
        }
        return false;
    }

    private function synBillStatusToCancellation($platform_bill_id, $bill_status)
    {
        return M('platform_bill_cancellation', 'tb_')
            ->where(['platform_bill_id' => $platform_bill_id])
            ->save(['bill_status' => $bill_status]);
    }

    public function createPlatformBillNO()
    {
        $pre_platform_bill_no = M('platform_bill', 'tb_')->lock(true)->where(['platform_bill_no' => ['like', 'ZD' . date('Ymd') . '%']])->order('id desc')->getField('platform_bill_no');
        if ($pre_platform_bill_no) {
            $num = substr($pre_platform_bill_no, -4) + 1;
        } else {
            $num = 1;
        }
        $platform_bill_no = 'ZD' . date('Ymd') . substr(10000 + $num, 1);
        return $platform_bill_no;
    }

    /**
     * 获取店铺业务审核负责人
     *
     * @param int $store_id
     *
     * @return string
     */
    public function getChargeManByStoreId($store_id)
    {
        if (empty($store_id)) return false;
        //GP店铺会关联多个销售团队 取第一个销售团队 本次需求（9881）不考虑GP
        $model = new Model();
        $store = $model->table('tb_ms_store')
            ->field('SALE_TEAM_CD')->where(['ID' => $store_id])->find();
        //存在','代表该店铺关联多个销售团队
        $saleCode = $store['SALE_TEAM_CD'];
        $p = strpos($store['SALE_TEAM_CD'], ',');
        if ($p !== false) {
            $saleCode = substr($store['SALE_TEAM_CD'], 0, $p);
        }
        $ret = $model->table('tb_ms_cmn_cd cd')
            ->field('ba.M_NAME')
            ->join('left join bbm_admin ba on ba.M_EMAIL = cd.ETC')
            ->where(['CD' => ['eq', $saleCode]])
            ->find();
        return $ret;
    }

    /**
     * @param $item
     * @param $date
     * @param array $return_exchange
     *
     * @return mixed
     */
    public function getExchangeRate($item, $date)
    {
        if (empty($this->return_exchange[$item['currency'].$date])) {
            $this->return_exchange[$item['currency'].$date] = exchangeRateConversion($item['currency'], 'USD', $date);
        }
        return $this->return_exchange[$item['currency'].$date];
    }

    /**
     * @param $count_list
     * @param $date
     *
     * @return mixed
     */
    public function getAmountData($count_list)
    {
        $rate = exchangeRateConversion('CNY', 'USD', date('Y-m-d'));
        foreach ($count_list as $v) {
            if ($v['PAY_CURRENCY'] == 'USD') {
                $this->in_come_amounts           += $v['in_come_amount'];
                $this->bill_amount_counts        += $v['bill_amount_count'];
                $this->wait_cancellation_amounts += $v['wait_cancellation_amount'];
            } else {
                $this->in_come_amounts           += $rate * $v['in_come_amount'];
                $this->bill_amount_counts        += $rate * $v['bill_amount_count'];
                $this->wait_cancellation_amounts += $rate * $v['wait_cancellation_amount'];
            }
        }
        $this->in_come_amounts           = round($this->in_come_amounts, 2);
        $this->bill_amount_counts        = round($this->bill_amount_counts, 2);
        $this->wait_cancellation_amounts = round($this->wait_cancellation_amounts, 2);
    }
}