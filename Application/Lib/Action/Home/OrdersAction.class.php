<?php

/**
 * Created by PhpStorm.
 * User: muzhitao
 * Date: 2016/8/2
 * Time: 13:14
 */
import("@.ToolClass.B2b.BaseCommon");

class OrdersAction extends BaseAction
{
    public $Model;
    public $order_arr_rep = [];
    public $cd_valkey_arr = [];
    public $logistics_mode_arr = [];
    private $qiniuHost = 'http://7xli3x.media1.z0.glb.clouddn.com/';
    
    protected $order_source_status_map = [];

    public $actlist = [];
    public $info_arr = ['ADDRESS_USER_NAME', 'ADDRESS_USER_PHONE', 'ADDRESS_USER_COUNTRY', 'ADDRESS_USER_COUNTRY_CODE', 'RECEIVER_TEL', 'ADDRESS_USER_POST_CODE', 'BUYER_MOBILE', 'SHIPPING_TYPE', 'BUYER_TEL', 'SHIPPING_MSG', 'ADDRESS_USER_PROVINCES', 'ADDRESS_USER_CITY', 'ADDRESS_USER_REGION', 'ADDRESS_USER_ADDRESS1', 'ADDRESS_USER_ADDRESS3'];
    public $b2c_plats = ['N000834100', 'N000831400'];
    public $source = ['普通用户', '扫码用户'];
    const  order_detail_url = '/index.php?g=OMS&m=Order&a=orderDetail&isShowEditButtonByOmsListEntry=true&api=b08a8be1abd25efd858141757dbfc5c5&order_no=';

    //  店铺类型对应的订单类型
    public $store_order_type = [
        'N003700001'=>'N003720001',
        'N003700002'=>'N003720002',
        'N003700003'=>'N003720003',
        'N003700004'=>'N003720004',
    ];

    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_SESSION['in']) {
            parent::_initialize();
        }
    }

    public function orders()
    {
        ini_set("max_execution_time", "1800");
        $is_createorderdm = isset($this->access['Orders/createorderdm']) ? 1 : 0;

        $is_createorderbw = isset($this->access['Orders/createorderbw']) ? 1 : 0;

        $is_createorderxh = isset($this->access['Orders/createorderxh']) ? 1 : 0;
        $is_createorderqg = isset($this->access['Orders/createorderqg']) ? 1 : 0;
        $this->assign('is_createorderdm', $is_createorderdm);
        $this->assign('is_createorderbw', $is_createorderbw);
        $this->assign('is_createorderxh', $is_createorderxh);
        $this->assign('is_createorderqg', $is_createorderqg);
        $this->getdata(0);
    }

    // 大宗订单
    public function orders_bulk()
    {
        $this->getdata(1);
    }

    // 直邮type为2
    public function orders_dm()
    {
        $this->getdata(2);
    }

    // 保税type为3
    public function orders_bw()
    {
        $this->getdata(3);
    }

    // 现货type为4
    public function orders_xh()
    {
        $this->getdata(4);
    }

    //获取大宗、直邮、现货、保税订单列表  根据不同type值获取不同页面的各个功能的url,订单列表
    public function getdata($type)
    {
        ini_set("max_execution_time", "1800");  //修改phpini最大执行时间
//        <{$Think.lang.totalprice}>
        $order_status = (new TbMsCmnCdModel())->getCdKey(TbMsCmnCdModel::$b5c_order_status_cd_pre);
        $this->assign('order_status', $order_status);
        $detail_url = '';
        $closeord = 'Orders/closeord';
        $type_add_mult = null;
        $order_type = null;
        if ($type == 0) {    //全部订单
            $bulkedit = 'Orders/bulkedit_bulk';
            $closeord = 'Orders/closeord_bulk';
            $bulksendout = 'Orders/bulksendout_bulk';
            $saveord = 'Orders/saveord_bulk';
            $saveprice = 'Orders/saveprice_bulk';
            $detail_url = 'Orders/orderdetail_bulk';
        }
        if ($type == 1) {      //大宗订单
            $bulkedit = 'Orders/bulkedit_bulk';
            $closeord = 'Orders/closeord_bulk';
            $bulksendout = 'Orders/bulksendout_bulk';
            $saveord = 'Orders/saveord_bulk';
            $saveprice = 'Orders/saveprice_bulk';
            $detail_url = 'Orders/orderdetail_bulk';
        } elseif ($type == 2) {     //直邮订单
            $bulkedit = 'Orders/bulkedit_dm';
            $closeord = 'Orders/closeord_dm';
            $bulksendout = 'Orders/bulksendout_dm';
            $saveord = 'Orders/saveord_dm';
            $saveprice = 'Orders/saveprice_dm';
            $detail_url = 'Orders/orderdetail_dm';
            $createorder = 'Orders/createorderdm';
            $setpaynumber = 'Orders/setpaynumber_dm';
        } elseif ($type == 3) {    //保税订单
            $bulkedit = 'Orders/bulkedit_bw';
            $closeord = 'Orders/closeord_bw';
            $bulksendout = 'Orders/bulksendout_bw';
            $saveord = 'Orders/saveord_bw';
            $saveprice = 'Orders/saveprice_bw';
            $detail_url = 'Orders/orderdetail_bw';
            $createorder = 'Orders/createorderbw';
            $type_add_mult = 'bw';// 在保税中增加批量po的判断依据
            $order_type = 3;
        } elseif ($type == 4) {      //现货订单
            $bulkedit = 'Orders/bulkedit_xh';
            $closeord = 'Orders/closeord_xh';
            $bulksendout = 'Orders/bulksendout_xh';
            $saveord = 'Orders/saveord_xh';
            $saveprice = 'Orders/saveprice_xh';
            $detail_url = 'Orders/orderdetail_xh';
            $createorder = 'Orders/createorderxh';
            $setpaynumber = 'Orders/setpaynumber_xh';
            $type_add_mult = 'xh';// 在现货中增加批量po的判断依据
            $order_type = 4;
        }
        $is_closeord = isset($this->access[$closeord]) ? 1 : 0;
        $is_bulkedit = isset($this->access[$bulkedit]) ? 1 : 0;
        if ($type == 3) {
            $is_bulkedit = 0;      //保税订单取消批量编辑
        }
        //判断$this->access 中是否有这些url
        //dump($this->access);
        $is_bulksendout = isset($this->access[$bulksendout]) ? 1 : 0;
        $is_saveord = isset($this->access[$saveord]) ? 1 : 0;
        $is_saveprice = isset($this->access[$saveprice]) ? 1 : 0;
        $is_detail = isset($this->access[$detail_url]) ? 1 : 0;
        $is_createorder = isset($this->access[$createorder]) ? 1 : 0;
        $is_setpaynumber = isset($this->access[$setpaynumber]) ? 1 : 0;
        $this->assign('is_bulkedit', $is_bulkedit);
        $this->assign('is_closeord', $is_closeord);
        $this->assign('is_bulksendout', $is_bulksendout);
        $this->assign('is_saveord', $is_saveord);
        $this->assign('is_saveprice', $is_saveprice);
        $this->assign('detail_url', $detail_url);
        $this->assign('is_detail', $is_detail);
        $this->assign('is_createorder', $is_createorder);
        $this->assign('is_setpaynumber', $is_setpaynumber);
        $this->assign('order_type', $order_type);
        $this->assign('access', $this->access);

        $where = [];
        //订单类型条件
        if ($type != '') {
            $type_cd = I('get.ORD_TYPE_CD');
            if ($type_cd == '') {
                if ($type == 1) {
                    $where['tb_ms_ord.ORD_TYPE_CD'] = 'N000620100';
                } elseif ($type == 2) {
                    $where['tb_ms_ord.ORD_TYPE_CD'] = 'N000620400';
                    $where['tb_ms_ord.DELIVERY_WAREHOUSE'] = 'N000680200';
                } elseif ($type == 3) {
                    $where['tb_ms_ord.ORD_TYPE_CD'] = 'N000620400';
//                    $where['tb_ms_ord.DELIVERY_WAREHOUSE'] = 'N000680300';
                } elseif ($type == 4) {
                    $where['tb_ms_ord.ORD_TYPE_CD'] = 'N000620400';
                    $where['tb_ms_ord.DELIVERY_WAREHOUSE'] = 'N000680100';
                }
            } else {
                if ($ORD_TYPE_CD = I('get.ORD_TYPE_CD')) {
                    $where['tb_ms_ord.ORD_TYPE_CD'] = $ORD_TYPE_CD;
                }
                if ($DELIVERY_WAREHOUSE = I('get.DELIVERY_WAREHOUSE')) {
                    $where['tb_ms_ord.DELIVERY_WAREHOUSE'] = $DELIVERY_WAREHOUSE;
                }
            }
            $this->assign('type', $type);
        }

        $where['tb_ms_ord.PLAT_FORM'] = array(array('EXP', 'IS NULL'), array('exp', ' IN ("N000830100","N000830200","N000831300")'), 'or');
        $where = $where + $this->getwhere();

        $this->assign('where', encode(json_encode($where)));
        $order = $this->getorder();
        $this->assign('order', htmlspecialchars(json_encode($order)));
        $model = M('ms_ord', 'tb_');
        if (!isset($where['tb_ms_guds.GUDS_NM']) && !isset($where['tb_ms_cmn_cd.CD_VAL'])) {
            import('ORG.Util.Page');// 导入分页类
            $count = $model->where($where)->count();
            $page = new Page($count, 10);
            $show = $page->show();
        }
        $model
            ->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_ms_ord.ORD_ID')
            ->join('left join tb_ms_cust on tb_ms_cust.CUST_ID = tb_ms_ord.CUST_ID')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_cust.SH_SALER')
            ->join('left join tb_ms_ord_guds_opt on tb_ms_ord.ORD_ID = tb_ms_ord_guds_opt.ORD_ID');
        //判断有条件的情况下拼接条件筛选语句
        if (isset($where['tb_ms_guds.GUDS_NM']) ||
            isset($where['tb_ms_cmn_cd.CD_VAL']) ||
            isset($where['tb_ms_ord_guds_opt.GUDS_OPT_ID']) ||
            isset($where['tb_ms_ord_package.EXPE_COMPANY']) ||
            isset($where['tb_ms_ord_guds_opt.THIRD_SUPPLIER']) ||
            isset($where['tb_ms_ord_guds_opt.THIRD_SERIAL_NUMBER'])
        ) {
            $model_c = clone($model);
            import('ORG.Util.Page');// 导入分页类
            $all_data = $model_c->where($where)->group('tb_ms_ord.ORD_ID')->select();
            $count = count($all_data);
            $page = new Page($count, 10);
            $show = $page->show();
        }
        $model->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID=tb_ms_ord_guds_opt.GUDS_ID');
        $result = $model
            ->field('tb_ms_ord.REFUND_STAT_CD, tb_ms_ord.REMARK_MSG, tb_ms_ord.REMARK_STAT_CD,tb_ms_ord.ORD_ID,tb_ms_ord.PAY_ID,tb_ms_ord.ORD_TYPE_CD,tb_ms_ord.CUST_ID,tb_ms_ord.ORD_CUST_CP_NO,tb_ms_ord.DISCOUNT_MN,tb_ms_ord.THIRD_ORDER_ID,tb_ms_ord.DLV_AMT,tb_ms_ord.TARIFF,tb_ms_ord.ORD_CUST_NM,tb_ms_ord.SYS_REG_DTTM,tb_ms_ord.PAY_DTTM,tb_ms_ord.ORD_STAT_CD,tb_ms_ord.DELIVERY_WAREHOUSE,tb_ms_ord_package.SUBSCRIBE_TIME,tb_ms_ord_package.TRACKING_NUMBER,tb_ms_ord_package.EXPE_COMPANY,tb_ms_cmn_cd.CD_VAL AS SH_SALER,sum(tb_ms_ord_guds_opt.RMB_PRICE*tb_ms_ord_guds_opt.ORD_GUDS_QTY)/if(isnull(tb_ms_ord_package.TRACKING_NUMBER),"1",count(distinct(tb_ms_ord_package.TRACKING_NUMBER))) as total_price,count("tb_ms_ord_guds_opt.ORD_ID") as GUDS_NUM,tb_ms_ord_guds_opt.RMB_PRICE,tb_ms_ord_guds_opt.ORD_GUDS_QTY,tb_ms_ord_guds_opt.ORD_GUDS_SALE_PRC,tb_ms_ord_guds_opt.RMB_PRICE,tb_ms_ord_guds_opt.GUDS_ID,tb_ms_ord_guds_opt.GUDS_OPT_ID,tb_ms_guds.GUDS_NM')
            ->where($where)
            ->group('tb_ms_ord.ORD_ID')
            ->order($order)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
        $this->assign('result', $result);
        $this->assign('pages', $show);
        $this->assign('total', $count);
        $this->assign('type_add_mult', $type_add_mult);
        $this->display('orders');
    }

    /**
     * 退款状态
     */
    public static function refund_state()
    {
        return [
            'N001080500' => '未申请退款',
            'N001080100' => '退款待处理',
            'N001080200' => '退款申请通过',
            'N001080300' => '退款成功',
            'N001080400' => '退款拒绝'
        ];
    }

    /**
     * 退款拒绝理由
     */
    public static function refund_reason_for_rejection()
    {
        return [
            '005' => [
                '01' => '拒绝理由1',
                '02' => '拒绝理由2',
                '03' => '拒绝理由3',
            ]
        ];
    }

    //求购订单列表，单独的表
    public function orders_qg()
    {
        //订单状态
        $order_status = (new TbMsCmnCdModel())->getCdKey(TbMsCmnCdModel::$b5c_order_status_cd_pre);
        $this->assign('order_status', $order_status);
        $this->assign('type', 'qg');

        $is_closeord = isset($this->access['Orders/closeord_qg']) ? 1 : 0;
        $is_bulkedit = isset($this->access['Orders/bulkedit_qg']) ? 1 : 0;
        $is_bulksendout = isset($this->access['Orders/bulksendout_qg']) ? 1 : 0;
        $is_saveord = isset($this->access['Orders/saveord_qg']) ? 1 : 0;
        $is_saveprice = isset($this->access['Orders/saveprice_qg']) ? 1 : 0;
        $is_createorder = isset($this->access['Orders/createorderqg']) ? 1 : 0;
        $is_setpaynumber = isset($this->access['Orders/setpaynumber_qg']) ? 1 : 0;
        $this->assign('is_bulkedit', $is_bulkedit);
        $this->assign('is_closeord', $is_closeord);
        $this->assign('is_bulksendout', $is_bulksendout);
        $this->assign('is_saveord', $is_saveord);
        $this->assign('is_saveprice', $is_saveprice);
        $this->assign('is_createorder', $is_createorder);
        $this->assign('is_setpaynumber', $is_setpaynumber);
//        $this->assign('detail_url',$detail_url);

        $where = [];
        $where = $where + $this->getwhere(0, 1);
        $order = $this->getorder(0, 1);
        $model = M('ms_ord_spl', 'tb_');
//        if(!isset($where['sms_ms_estm_guds.ORD_GUDS_CNS_NM']) && !isset($where['tb_ms_cmn_cd.CD_VAL'])){
//            import('ORG.Util.Page');// 导入分页类
//            $count = $model->where($where)->count();
//            $page = new Page($count, 10);
//            $show = $page->show();
//        }
        if (!isset($where['tb_ms_ord_guds_opt.ORD_GUDS_CNS_NM']) && !isset($where['tb_ms_cmn_cd.CD_VAL'])) {
            import('ORG.Util.Page');// 导入分页类
            $count = $model->where($where)->count();
            $page = new Page($count, 10);
            $show = $page->show();
        }

//        $model->join('left join sms_ms_estm_guds on sms_ms_estm_guds.ORD_NO = tb_ms_ord_spl.ORD_ID')->join('left join sms_ms_estm on sms_ms_estm.ORD_NO = tb_ms_ord_spl.ORD_ID')->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_ms_ord_spl.ORD_ID')->join('left join bbm_pay_order on tb_ms_ord_spl.ORD_ID=bbm_pay_order.order_id')->join('left join bbm_pay_callback  on bbm_pay_callback.pay_id = bbm_pay_order.pay_id')->join('left join tb_ms_cust on tb_ms_cust.CUST_ID = tb_ms_ord_spl.CUST_ID')->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_cust.SH_SALER');
        $model->join('left join tb_ms_ord_guds_opt on tb_ms_ord_guds_opt.ORD_ID = tb_ms_ord_spl.ORD_ID')
//            ->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_ord_guds_opt.GUDS_ID')
            ->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_ms_ord_spl.ORD_ID')
//            ->join('left join bbm_pay_order on tb_ms_ord_spl.ORD_ID=bbm_pay_order.order_id')
//            ->join('left join bbm_pay_callback  on bbm_pay_callback.pay_id = bbm_pay_order.pay_id')
            ->join('left join tb_ms_cust on tb_ms_cust.CUST_ID = tb_ms_ord_spl.CUST_ID')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_cust.SH_SALER');
//        if(isset($where['sms_ms_estm_guds.ORD_GUDS_CNS_NM']) || isset($where['tb_ms_cmn_cd.CD_VAL'])){
//            $model_c = clone($model);
//            import('ORG.Util.Page');// 导入分页类
//            $all_data = $model_c->where($where)->group('tb_ms_ord_spl.ORD_ID')->select();
//            $count = count($all_data);
//            $page = new Page($count, 10);
//            $show = $page->show();
//        }
        if (isset($where['tb_ms_ord_guds_opt.ORD_GUDS_CNS_NM']) ||
            isset($where['tb_ms_cmn_cd.CD_VAL']) ||
            isset($where['tb_ms_ord_guds_opt.GUDS_ID']) ||
            isset($where['tb_ms_ord_package.EXPE_COMPANY'])
        ) {
            $model_c = clone($model);
            import('ORG.Util.Page');// 导入分页类
            $all_data = $model_c->where($where)->group('tb_ms_ord_spl.ORD_ID')->select();
            $count = count($all_data);
            $page = new Page($count, 10);
            $show = $page->show();
        }
//        $result = $model->group('tb_ms_ord_spl.ORD_ID')->order($order)->limit($page->firstRow.','.$page->listRows)->field('count("sms_ms_estm_guds.ORD_NO") as GUDS_NUM,tb_ms_ord_spl.ORD_ID,bbm_pay_order.pay_id as PAY_ID,tb_ms_ord_spl.CUST_ID,tb_ms_ord_spl.ORD_CUST_NM,sms_ms_estm_guds.GUDS_ID,sms_ms_estm_guds.ORD_GUDS_CNS_NM as GUDS_NM,sms_ms_estm_guds.ORD_GUDS_QTY,(sms_ms_estm_guds.ORD_GUDS_SALE_PRC/sms_ms_estm.STD_XCHR_AMT) as RMB_PRICE,tb_ms_ord_package.TRACKING_NUMBER,tb_ms_ord_package.EXPE_COMPANY,tb_ms_ord_spl.PAY_DTTM,tb_ms_ord_spl.SYS_REG_DTTM,tb_ms_ord_spl.OPTION_MODE_CD as ORD_STAT_CD,tb_ms_cmn_cd.CD_VAL AS SH_SALER,sms_ms_estm.DLV_AMT,sms_ms_estm.DISCOUNT_MN,(sms_ms_estm.PO_SUM_AMT/sms_ms_estm.STD_XCHR_AMT) as total_price')->where($where)->select();
        $result = $model->group('tb_ms_ord_spl.ORD_ID')->order($order)->limit($page->firstRow . ',' . $page->listRows)->field('
         tb_ms_ord_spl.REMARK_STAT_CD,
         count("tb_ms_ord_spl.ORD_ID") as GUDS_NUM,
         tb_ms_ord_spl.ORD_ID,tb_ms_ord_spl.CUST_ID,
         tb_ms_ord_spl.ORD_CUST_NM,
         tb_ms_ord_guds_opt.GUDS_ID,
         tb_ms_ord_guds_opt.GUDS_OPT_ID,
         tb_ms_ord_guds_opt.ORD_GUDS_CNS_NM as GUDS_NM,
         tb_ms_ord_guds_opt.ORD_GUDS_QTY,tb_ms_ord_guds_opt.RMB_PRICE,
         tb_ms_ord_package.TRACKING_NUMBER,
         tb_ms_ord_package.EXPE_COMPANY,
         tb_ms_ord_spl.PAY_DTTM,tb_ms_ord_spl.SYS_REG_DTTM,
         tb_ms_ord_spl.OPTION_MODE_CD as ORD_STAT_CD,
         tb_ms_cmn_cd.CD_VAL AS SH_SALER,
         tb_ms_ord_spl.DLV_AMT,
         tb_ms_ord_spl.DISCOUNT_MN,
         tb_ms_ord_spl.TARIFF,
         tb_ms_ord_spl.PO_SUM_AMT as total_price
         ')->where($where)->select();
        //支付订单数据获取支付号
        foreach ($result as $key => $val) {
            $pay = M('pay_order')->where('order_status = "SUCCESS" and order_id = "' . $val['ORD_ID'] . '"')->field('pay_id')->find();
            $result[$key]['PAY_ID'] = $pay['pay_id'];
        }
        //dump($result);
        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
        $this->assign('result', $result);
        $this->assign('type', 'qg');
        $this->assign('result', $result);
        $this->assign('pages', $show);
        $this->assign('total', $count);
        $this->assign('access', $this->access);

        $this->display('orders');
    }

    //获取自营订单，单独的表
    public function orders_self()
    {
        LogsModel::$project_name = 'b2b2c_order';
        LogsModel::$time_grain = true;
        LogsModel::$act_microtime = microtime(true);
        LogsModel::$hash_key = crc32(LogsModel::$act_microtime);
        Logs('act_time:', 'act');
        ini_set('max_input_nesting_level', 500);
        $refund_state = static::refund_state();   //获取退款状态数据
        $this->assign('refund_state', $refund_state);
        $bulksendout = 'Orders/bulksendout_self';  //【批量发货url
        $is_bulksendout = isset($this->access[$bulksendout]) ? 1 : 1;
        $bulkedit = 'Orders/bulkedit_self';       //批量编辑url
        $is_bulkedit = isset($this->access[$bulkedit]) ? 1 : 1;
        $this->assign('is_bulkedit', $is_bulkedit);

        $order_status = (new TbMsCmnCdModel())->getCdKey(TbMsCmnCdModel::$b5c_order_status_cd_pre);
        $this->assign('order_status', $order_status);
        $this->assign('logistic_status', C('logistic_status'));    //获取配置文件中的物流状态
        $store = M('ms_store', 'tb_')->field('STORE_NAME,MERCHANT_ID,USER_ID')->select();   //店铺名,店铺id，userid
        $this->assign('store', $store);
        $where = $this->getwhere(1);
        Logs('time:', 'get_where');
//        $where[]['tb_op_order.PLAT_CD'] = ['neq', 'N000831400'];   //限制不显示gshopperApp平台的订单

        $order = $this->getorder(1);   //获取排序
        $this->assign('order', htmlspecialchars(json_encode($order)));
        $plat = M('ms_store', 'tb_')->field('PLAT_CD,SITE_CD')->group('SITE_CD')->select();   //店铺数据
        $plat = BaseModel::getPlat();  //平台数据
        $model = M('ms_store', 'tb_');
        if ($platIdChoose = I('PLAT_CD')) {
            $wherestore['tb_ms_store.PLAT_CD'] = is_array($platIdChoose) ? array('in', $platIdChoose) : $platIdChoose;
            $whereexpe['tb_ms_logistics_relation.plat_cd'] = is_array($platIdChoose) ? array('in', $platIdChoose) : $platIdChoose;
        }
        if ($platIdChoose) {
            $is_bulksendout = 1;
        } else {
            $is_bulksendout = 1;
        }
        $this->assign('is_bulksendout', $is_bulksendout);
        $ret = $model->field('MERCHANT_ID')->where($wherestore)->select();   //获取平台数据WORK_NUM
        $store = array_column($ret, 'MERCHANT_ID', 'MERCHANT_ID');
        $EXPE_COMPANY = M('ms_logistics_relation', 'tb_')->field(
            'tb_ms_logistics_relation.plat_cd as PLAT_CD,
            tb_ms_logistics_relation.b5c_logistics_cd as CD,
                tb_ms_logistics_relation.third_logistics_cd as CD_VAL
                ')->where($whereexpe)->select();
        $this->assign('plat', $plat);
        $this->assign('store', $store);
        $model = M('op_order', 'tb_');

        //判断包裹类型，不同订单，在qoo10中可能属于同个订单，按照收件人排序。
        //新增逻辑，如果用户有退货请求，增加数据筛选，与tb_ms_ord进行数据筛选
        $boxwhere = [];
        $having_co = '';
        $having_se = '';
        if ($boxtype = I('boxtype')) {    //包裹类型条件筛选,这个条件必须在已经付款待发货状态下
            $this->assign('boxtype', $boxtype);
            switch ($boxtype) {
                case 1:
                    $having_co = 'count(1) = 1 and ITEM_COUNT=1'; //测试环境出现订单表ORDER_ID重复的情况，count(1) = count(distinct(PLAT_CD))或1
                    $having_se = 'count(1) = if(count(tb_wms_center_stock.channel),count(tb_wms_center_stock.channel),1) and ITEM_COUNT=1';  //单品单数
                    break;
                case 2:
                    $having_co = 'count(1) = 1 and ITEM_COUNT > 1';
                    $having_se = 'count(1) = if(count(tb_wms_center_stock.channel),count(tb_wms_center_stock.channel),1) and ITEM_COUNT > 1';   //单品多数
                    break;
                case 3:
                    $having_co = 'count(1) > 1';
                    $having_se = 'count(1) > if(count(tb_wms_center_stock.channel),count(tb_wms_center_stock.channel),1)';          //单品混包
                    break;
                default :
                    break;
            }
            $where['BWC_ORDER_STATUS'] = 'N000550400';   //已付款待发货状态条件
        }
        Logs('time:', 'join_box_type');
        import('ORG.Util.Page');// 导入分页类
        if (I('REFUND_STAT_CD')) {
            $where['tb_op_order.REFUND_STAT_CD'] = is_array(I('REFUND_STAT_CD')) ? array('in', I('REFUND_STAT_CD')) : I('REFUND_STAT_CD');
        }
        if (true == I('ERR_ORDER')) {
            /*$where['tb_op_order_guds.B5C_SKU_ID'] = array('exp','is null');
            $where_son1['tb_op_order.ADDRESS_USER_REGION'] = array('exp','is  null');
            $where_son1['tb_op_order.ADDRESS_USER_CITY'] = array('exp','is  null');
            $where_son1['tb_op_order.ADDRESS_USER_ADDRESS1'] = array('exp','is  null');
            $where_son1['tb_op_order.ADDRESS_USER_PHONE'] = array('exp','is  null');
            $where_son1['_logic'] = 'or';
            $where['_complex'] = $where_son1;*/

            $where_son1['tb_op_order.ADDRESS_USER_ADDRESS1'] = array('exp', 'is  null');
            $where_son1['tb_op_order.BWC_ORDER_STATUS'] = array('NOT IN', array('N000551000', 'N000550900'));
            $where_son1['_logic'] = 'AND';
            $where['_complex'] = $where_son1;
        }
        if (true == I('SKUNO')) {
            $where['tb_op_order_guds.B5C_SKU_ID'] = array('exp', 'IS NULL');
            $where['_string'] = '(tb_op_order.SEND_ORD_STATUS = \'N001820100\' ) OR ( tb_op_order.SEND_ORD_STATUS != \'N001820100\' AND tb_op_order.B5C_ORDER_NO IS NULL)';
        }
        if (true == I('LACK_STOCK')) {
            $where['tb_op_order_guds.B5C_SKU_ID'] = array('exp', 'IS NOT NULL');
            $where['_string'] = '(tb_op_order.SEND_ORD_STATUS != \'N001820100\' AND  tb_op_order.B5C_ORDER_NO IS NULL)';
        }
        $this->assign('where', encode(json_encode($where)));
        Logs('time:', 'join_where');
        $sql = $model
            ->field('tb_op_order.ID,tb_op_order_guds.ITEM_COUNT')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID')
            ->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_op_order.B5C_ORDER_NO')
            ->join('left join tb_ms_lgt_track on tb_ms_lgt_track.ORD_ID = tb_op_order.B5C_ORDER_NO and tb_ms_lgt_track.LGT_TYPE=tb_ms_ord_package.LOGISTIC_STATUS')
            ->join('left join tb_ms_guds on tb_op_order_guds.B5C_SKU_ID is not null and tb_ms_guds.MAIN_GUDS_ID = left(tb_op_order_guds.B5C_SKU_ID,8) and LANGUAGE = "N000920100"')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->where($where)
            ->group('tb_op_order.ORDER_ID')
            ->having($having_co)
            ->buildSql();   //生成sql语句用于查询次数,最终的数据要和查语句的数据保持一致

        $count = M()->table($sql . ' a')->count();
        Logs('time:', 'search_count');

        //echo $count;
        $page = new Page($count, 50);
        $show = $page->show();
        // 显示全部退款状态数据
        $m = M('op_order', 'tb_');
        $result = $m
            ->field('tb_op_order.REFUND_STAT_CD,
                tb_op_order.B5C_ORDER_DES_COUNT,
                tb_op_order.FILE_NAME,
                tb_op_order.REMARK_STAT_CD,
                tb_op_order.ID,
                tb_op_order.ADDRESS_USER_ADDRESS3,
                tb_op_order.ORDER_ID,
                tb_op_order.ORDER_NO,
                tb_op_order.ORDER_ID as ORD_ID,
                tb_op_order.PLAT_CD,tb_ms_store.PLAT_NAME,
                tb_ms_store.MERCHANT_ID as SHOP_ID,tb_op_order.USER_ID,tb_op_order.PAY_CURRENCY,
                tb_ms_store.SALE_TEAM_CD,
                tb_op_order.PAY_TOTAL_PRICE,tb_op_order.PAY_SHIPING_PRICE,tb_op_order.PAY_VOUCHER_AMOUNT,
                tb_op_order.ADDRESS_USER_NAME,tb_op_order.ORDER_TIME,
                tb_op_order.ORDER_PAY_TIME,tb_op_order.SHIPPING_DELIVERY_COMPANY,
                tb_op_order.SHIPPING_TRACKING_CODE,tb_op_order.ORDER_STATUS,tb_op_order.PAY_PRICE,
                tb_op_order.BWC_ORDER_STATUS,
                tb_op_order.B5C_ORDER_NO,tb_op_order.B5C_ACCOUNT_ID,
                tb_op_order.SHORT_SUPPLY,
                tb_op_order.SEND_ORD_STATUS,
                tb_op_order.WAREHOUSE,
                tb_op_order.SEND_ORD_MSG,
                tb_op_order.SEND_ORD_TIME,
                tb_op_order.SHIPPING_TIME as SUBSCRIBE_TIME,count("tb_op_order_guds.ORDER_ID")/if(count(tb_wms_center_stock.channel),count(tb_wms_center_stock.channel),1) as GUDS_NUM,
                tb_op_order_guds.ORDER_ITEM_ID,
                tb_op_order_guds.ITEM_NAME,
                tb_op_order_guds.ITEM_PRICE as RMB_PRICE,tb_op_order_guds.ITEM_COUNT,
                tb_op_order_guds.B5C_ITEM_ID,
                tb_op_order_guds.B5C_SKU_ID,
                tb_ms_guds.GUDS_NM,tb_wms_center_stock.total_inventory,
                tb_ms_lgt_track.LGT_TYPE,
                tb_ms_lgt_track.LGT_CONTENT,
                tb_ms_guds.DELIVERY_WAREHOUSE
                ')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID')
            ->join('left join tb_ms_guds on tb_op_order_guds.B5C_SKU_ID is not null and tb_ms_guds.MAIN_GUDS_ID = left(tb_op_order_guds.B5C_SKU_ID,8) and LANGUAGE = "N000920100"')
            ->join('left join tb_wms_center_stock  on tb_op_order_guds.B5C_SKU_ID is not null and tb_wms_center_stock.SKU_ID = tb_op_order_guds.B5C_SKU_ID')
            ->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_op_order.B5C_ORDER_NO')
            ->join('left join tb_ms_lgt_track on tb_ms_lgt_track.ORD_ID = tb_op_order.B5C_ORDER_NO and tb_ms_lgt_track.LGT_TYPE=tb_ms_ord_package.LOGISTIC_STATUS')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->where($where)
            ->having($having_se)
            ->order($order)
            ->group('tb_op_order.ORDER_ID')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        Logs('time:', 'search_data');
        $all_sku = array_unique(array_column($result, 'B5C_SKU_ID'));
        foreach ($all_sku as $k => $v) {
            if (strlen_utf8($v) > 11) {
                $arr = explode('/', trim($v, '/'));
                unset($all_sku[$k]);
                foreach ($arr as $v) {
                    if (!empty($v)) array_push($all_sku, $v);
                }
            }
        }
        if (count(array_unique($all_sku)) > 0) {
            $Model = M();
            $batch_where['SKU_ID'] = array('in', array_unique($all_sku));
            $batch_arr = $Model->table('tb_wms_batch')->field('SKU_ID,sale_team_code,available_for_sale_num as sum_sale')->where($batch_where)->select();
            $func = function ($v) {
                $arr[$v['SKU_ID'] . $v['sale_team_code']] = $v['sum_sale'];
                return $arr;
            };
            $batch_hs = array_map($func, $batch_arr);
            $batch_one = array_reduce($batch_hs, 'array_merge', []);
        }
        foreach ($result as $k => $v) {
            $result[$k]['total_inventory'] = null;
            if (strlen_utf8($v['B5C_SKU_ID']) > 11) {
                $arr = explode('/', trim($v['B5C_SKU_ID'], '/'));
                foreach ($arr as $m) {
                    if (!empty($batch_one[$m . $v['SALE_TEAM_CD']])) {
                        $result[$k]['total_inventory'] .= $batch_one[$m . $v['SALE_TEAM_CD']] . '/';
                    }
                }
            } else {
                if (!empty($batch_one[$v['B5C_SKU_ID'] . $v['SALE_TEAM_CD']])) {
                    $result[$k]['total_inventory'] = $batch_one[$v['B5C_SKU_ID'] . $v['SALE_TEAM_CD']];
                }
            }
            if (empty($result[$k]['PLAT_NAME']) && empty($result[$k]['SHOP_ID'])) {
                $olddata = M('op_order', 'tb_')->field('tb_op_order.PLAT_NAME,
                tb_op_order.SHOP_ID')->where('tb_op_order.ORDER_ID = "' . $result[$k]['ORDER_ID'] . '"')->find();
                $result[$k]['PLAT_NAME'] = $olddata['PLAT_NAME'];
                $result[$k]['SHOP_ID'] = $olddata['SHOP_ID'];
            }
        }
        $this->assign('boxwhere', $boxwhere);
        Logs('time:', 'join_batch_stock');
        $Order_model = M();
        if (I('patch_manual') && $result) {
            $result_warehoues = OrdersModel::get_all_batch_plat_warehouse($result, $Order_model);
            $result_key = array_column($result, 'ORDER_ID');
            $result_key_flip = array_flip($result_key);
            foreach ($result_warehoues as $key => $val) {
                $result[$result_key_flip[$key]]['warehous'] = $result_warehoues[$key];
            }
        }
        Logs('time:', 'dispatch_do');
        $warehouses = B2bModel::get_code_cd('N00068');
        $erp_warehouses = StockAction::get_int_warehouse_code();
        is_array($erp_warehouses) ? $warehouses += $erp_warehouses : '';
        $this->assign('warehouses', $warehouses);
        $send_status_arr = B2bModel::get_code_cd('N00182');
        $send_status = [
            'N001820100' => $send_status_arr['N001820100']['CD_VAL'],
            'N001820200' => $send_status_arr['N001820200']['CD_VAL'],
            'N001820300' => $send_status_arr['N001820300']['CD_VAL']
        ];
        $this->assign('send_status_arr', $send_status);
        $this->assign('result', $result);
        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();   //在数据字典表中查出物流公司
        $WARE_ADDRESS = M('ms_cmn_cd', 'tb_')->where('CD_NM = "DELIVERY_WAREHOUSE"')->select();

        $PLAT_FORM = [
            'N000831400',
            'N000834100',
            'N000834200',
            'N000834300'
        ];
        $this->assign('PLAT_FORM', $PLAT_FORM);
        $this->assign('SEND_ORD_STATUS_ARR', $send_status_arr);
        $COUNTRY = BaseModel::getAreaCode(true);
        $this->assign('WARE_ADDRESS', $WARE_ADDRESS);
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
        $this->assign('access', $this->access);
        $this->assign('type', 'self');
        $this->assign('pages', $show);
        $this->assign('total', $count);
        $this->assign('COUNTRY', $COUNTRY);
        $this->assign('is_closeord', 1);
        Logs('time:', 'end_data');
        $this->display('orders');
        Logs('time:', 'end');
        LogsModel::$project_name = 'sys';
    }


    public function getReason($ordId = null)
    {
        $ordId = I('ordId');
        $res = M("_ms_b5create_log", 'tb_')->field("create_time,err_msg")->where("order_id='$ordId'")->select();
        //echo M("_ms_b5create_log",'tb_')->_sql();die;
        echo json_encode($res);
        die;
        $this->assign('res', $res);
        $this->ajaxReturn($res, '$info', '$status', '$type');
    }

    /**
     * 新的自营订单 gsopperApp平台
     */
    public function new_orders_self()
    {
        ini_set("max_execution_time", "1800");
//        <{$Think.lang.totalprice}>
        $order_status = (new TbMsCmnCdModel())->getCdKey(TbMsCmnCdModel::$b5c_order_status_cd_pre);

        $this->assign('order_status', $order_status);
        $this->assign('source', $this->source);

        $detail_url = '';
        $closeord = 'Orders/closeord';
        $type_add_mult = null;
        $order_type = null;
        $bulkedit = 'Orders/bulkedit_dm';
        $closeord = 'Orders/closeord_dm';
        $bulksendout = 'Orders/bulksendout_dm';
        $saveord = 'Orders/saveord_dm';
        $saveprice = 'Orders/saveprice_dm';
        $detail_url = 'Orders/orderdetail_dm';
        $createorder = 'Orders/createorderdm';
        $setpaynumber = 'Orders/setpaynumber_dm';

        $this->assign('detail_url', $detail_url);
        $this->assign('order_type', $order_type);
        $this->assign('access', $this->access);

        $is_closeord = isset($this->access[$closeord]) ? 1 : 0;
        $this->assign('is_closeord', $is_closeord);

        $is_bulkedit = isset($this->access[$bulkedit]) ? 1 : 0;
        if ($type == 3) $is_bulkedit = 0;
        $this->assign('is_bulkedit', $is_bulkedit);
        // 权限控制
        $is_bulksendout = isset($this->access[$bulksendout]) ? 1 : 0;
        $this->assign('is_bulksendout', $is_bulksendout);

        $is_saveord = isset($this->access[$saveord]) ? 1 : 0;
        $this->assign('is_saveord', $is_saveord);

        $is_saveprice = isset($this->access[$saveprice]) ? 1 : 0;
        $this->assign('is_saveprice', $is_saveprice);

        $is_detail = isset($this->access[$detail_url]) ? 1 : 0;
        $this->assign('is_detail', $is_detail);

        $is_createorder = isset($this->access[$createorder]) ? 1 : 0;
        $this->assign('is_createorder', $is_createorder);

        $is_setpaynumber = isset($this->access[$setpaynumber]) ? 1 : 0;
        $this->assign('is_setpaynumber', $is_setpaynumber);

        $where = [];
        // 自营订单，一件代发状态吗N000620400，韩国仓状态码N000680200
        $where['tb_ms_ord.ORD_TYPE_CD'] = 'N000620400';
        // 订单状态为代发货的
        $where = $where + $this->getWhereNew();
        $this->assign('where', encode(json_encode($where)));
        // 获取排序方式
        $order = $this->getorder();
        $this->assign('order', htmlspecialchars(json_encode($order)));
        $model = M('ms_ord', 'tb_');
        //没有条件筛选下的条数
        if (!isset($where['tb_ms_guds.GUDS_NM']) && !isset($where['tb_ms_cmn_cd.CD_VAL'])) {
            import('ORG.Util.Page');// 导入分页类
            $count = $model->where($where)
                ->join('left join tb_ms_thrd_cust on tb_ms_thrd_cust.CUST_EML = tb_ms_ord.THRD_USER_ID')
                ->count();
            $page = new Page($count, 10);
            $show = $page->show();
        }
        $model->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_ms_ord.ORD_ID')
            ->join('left join tb_ms_cust on tb_ms_cust.CUST_ID = tb_ms_ord.CUST_ID')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_cust.SH_SALER')
            ->join('left join tb_ms_ord_guds_opt on tb_ms_ord.ORD_ID = tb_ms_ord_guds_opt.ORD_ID')
            ->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_ord_guds_opt.GUDS_ID')
            ->join('left join tb_op_order on tb_op_order.B5C_ORDER_NO = tb_ms_ord.ORD_ID')
            ->join('left join tb_ms_thrd_cust on tb_ms_thrd_cust.CUST_EML = tb_ms_ord.THRD_USER_ID');
        //->join('left join ');
        // 根据排序参数获取数据集
        //有条件筛选下的分页条数
        if (
            isset($where['tb_ms_guds.GUDS_NM']) ||
            isset($where['tb_ms_cmn_cd.CD_VAL']) ||
            isset($where['tb_ms_ord_guds_opt.GUDS_OPT_ID']) ||
            isset($where['tb_ms_ord_package.EXPE_COMPANY']) ||
            isset($where['tb_ms_ord_guds_opt.THIRD_SUPPLIER']) ||
            isset($where['tb_ms_ord_guds_opt.THIRD_SERIAL_NUMBER']) ||
            isset($where['tb_ms_guds.DELIVERY_WAREHOUSE'])
        ) {
            $model_c = clone($model);
            import('ORG.Util.Page');// 导入分页类
            $all_data = $model_c->where($where)->group('tb_ms_ord.ORD_ID')->select();
            $count = count($all_data);
            $page = new Page($count, 10);
            $show = $page->show();
        }

        // 对数据集进行分页排序
        $result = $model
            ->field('tb_ms_ord.ESTM_RCP_REQ_DT,tb_ms_ord.REMARK_STAT_CD,
             tb_ms_ord.ORD_ID,tb_ms_ord.PAY_ID,
             tb_ms_ord.ORD_TYPE_CD,
             tb_ms_ord.CUST_ID,
             tb_ms_ord.PLAT_FORM,
             tb_ms_ord.ORD_CUST_CP_NO,
             tb_ms_ord.DISCOUNT_MN,
             tb_ms_ord.THIRD_ORDER_ID,
             tb_ms_ord.DLV_AMT,
             tb_ms_ord.TARIFF,
             tb_ms_ord.ORD_CUST_NM,
             tb_ms_ord.SYS_REG_DTTM,
             tb_ms_ord.PAY_DTTM,
             tb_ms_ord.ORD_STAT_CD,
             tb_ms_ord.DELIVERY_WAREHOUSE,
             tb_ms_ord_package.SUBSCRIBE_TIME,
             tb_ms_ord_package.TRACKING_NUMBER,
             tb_ms_ord_package.EXPE_COMPANY,
             tb_ms_cmn_cd.CD_VAL AS SH_SALER,
              tb_ms_ord.REFUND_STAT_CD,
              tb_ms_ord.currency_cd,
              sum(tb_ms_ord_guds_opt.RMB_PRICE*tb_ms_ord_guds_opt.ORD_GUDS_QTY)/if(isnull(tb_ms_ord_package.TRACKING_NUMBER),"1",count(distinct(tb_ms_ord_package.TRACKING_NUMBER))) as total_price,
              count("tb_ms_ord_guds_opt.ORD_ID")/count(distinct(tb_ms_ord_package.TRACKING_NUMBER)) as GUDS_NUM,
              tb_ms_ord_guds_opt.RMB_PRICE,
              tb_ms_ord_guds_opt.ORD_GUDS_QTY,
              tb_ms_ord_guds_opt.ORD_GUDS_SALE_PRC,
              tb_ms_ord_guds_opt.RMB_PRICE,
              tb_ms_ord_guds_opt.GUDS_ID,
              tb_ms_ord_guds_opt.GUDS_OPT_ID,
              tb_ms_guds.GUDS_NM,
              tb_ms_ord_package.SUBSCRIBE_TIME,
              tb_op_order.PAY_SHIPING_PRICE,
              tb_op_order.PAY_VOUCHER_AMOUNT,
              tb_op_order.PAY_CURRENCY,
              tb_op_order.ORDER_TIME,
              tb_op_order.ORDER_PAY_TIME')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->group('tb_ms_ord.ORD_ID')
            ->order($order)
            ->select();
        //dump($result);
        $this->assign('boxtype', I('boxtype'));
        // 增加退款状态选择项
        $refund_state = static::refund_state();
        $this->assign('refund_state', $refund_state);
        // 增加平台渠道选项
        $plat = M('ms_cmn_cd', 'tb_')->field('CD,CD_VAL')->where(['CD' => ['in', $this->b2c_plats]])->select();
        $this->assign('plat', $plat);
        // 增加店铺账号选项
        $store = M('ms_store', 'tb_')->field('STORE_NAME,MERCHANT_ID,USER_ID')->select();
        $this->assign('store', $store);
        // 增加包裹类型选项
        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();
        // 物流公司
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
//        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();

        $WARE_ADDRESS = M('ms_cmn_cd', 'tb_')->where('CD_NM = "DELIVERY_WAREHOUSE"')->select();
        $this->assign('WARE_ADDRESS', $WARE_ADDRESS);
        // 数据集
        $this->assign('result', $result);
        // 分页插件
        $this->assign('pages', $show);
        // 记录条数
        $this->assign('total', $count);
        $this->assign('logistics_company_arr', B2bModel::get_code_cd('N00070'));
        Logs(B2bModel::get_code_cd('N00070'), 'B2bModel::get_code_cd(\'N00070\')');
        Logs($logistics_company_arr, 'B2bModel::get_code_cd(\'N00070\')');
        $this->assign('logistic_status', C('logistic_status'));    //获取配置文件中的物流状态
        // 是否是本也得判断依据
        $this->assign('type', 'new_self');
        $this->assign('logistic_status', C('logistic_status'));   //物流轨迹条件数据
        $this->display('orders');
    }

    /**
     * 同意退款，改变状态
     * 退款申请通过 N001080200
     */
    public function agree_refund()
    {
        if (!IS_AJAX) {
            $info ['message'] = L('异常访问');
            $info ['status'] = 503;
            $this->ajaxReturn(0, $info, 0);
        }
        $ORD_ID = I('ORD_ID');
        $MESSAGE = I('MESSAGE');

        // tb_ms_ord 退款状态修改
        $model = M('ms_ord', 'tb_');
        $data ['REFUND_STAT_CD'] = 'N001080200';

        // 增加Q消息队列
        $rbmq = new RabbitMqModel();
        $result = $model->field('THRD_USER_ID, PLAT_FORM, OA_NUM, THIRD_ORDER_ID')->where('ORD_ID = "' . $ORD_ID . '"')->find();
        // PLAT_FORM N000831400
        if ($result) {
            $msg = [
                "platCode" => $result ['PLAT_FORM'],//第三方平台code码
                "processId" => create_guid(),//uuid
                "data" => [
                    "orders" => [
                        [
                            "thirdOrdId" => $result ['THIRD_ORDER_ID'],      //第三方订单号id
                            "ordId" => $ORD_ID,                         //tb_ms_ordORD_ID"
                            "refnundStatCd" => $data ['REFUND_STAT_CD'],   //退款状态码"
                            "msg" => "SUCCESS",            //消息推送状态，只要发出了请求就成功就会使msg=SUCCESS
                            "oaNo" => '',                         //oa单号，只会在退款成功时才会非空
                            "code" => "2000",                //消息推送状态，只要请求发送成功就会是code=2000
                            "refnundDate" => date('Y-m-d\TH:i:s\Z', time()),
                        ]
                    ]
                ]
            ];

            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                // 限制，只能是Gshopper订单才发送到Q队列
                if ($result ['PLAT_FORM'] == 'N000831400') {
                    $rbmq->setData($msg);
                    $rbmq->submit();
                }
                $info ['message'] = L('操作成功');
                $info ['status'] = '200';
                $log = A('Log');
                $log->index($ORD_ID, 'N001080200', '退款审核通过');
                $this->ajaxReturn(0, $info, 1);
            } else {
                if (!$rbmq->submit()) {
                    $m = L('数据如Q失败，请重试');
                } else {
                    $m = L('操作失败，没有数据改变');
                }

                $info ['message'] = $m;
                $info ['status'] = '300';
                $this->ajaxReturn(0, $info, 0);
            }
        }

        $info ['message'] = '未查询到订单' . $ORD_ID;
        $info ['status'] = '300';
        $this->ajaxReturn(0, $info, 0);
    }

    /**
     * 拒绝退款
     * 退款拒绝 N001080400
     */
    public function refuse_refund()
    {
        if (!IS_AJAX) {
            $info ['message'] = L('异常访问');
            $info ['status'] = 503;
            $this->ajaxReturn(0, $info, 0);
        }
        $ORD_ID = I('ORD_ID');
        $MESSAGE = I('MESSAGE');

        // tb_ms_ord 退款状态修改
        $model = M('ms_ord', 'tb_');
        $data ['REFUND_STAT_CD'] = 'N001080400';
        // 增加Q消息队列
        $rbmq = new RabbitMqModel();

        $result = $model->field('THRD_USER_ID, PLAT_FORM, OA_NUM, THIRD_ORDER_ID')->where('ORD_ID = "' . $ORD_ID . '"')->find();
        if ($result) {
            $msg = [
                "platCode" => $result ['PLAT_FORM'],//第三方平台code码
                "processId" => create_guid(),//uuid
                "data" => [
                    "orders" => [
                        [
                            "thirdOrdId" => $result ['THIRD_ORDER_ID'],      //第三方订单号id
                            "ordId" => $ORD_ID,                         //tb_ms_ordORD_ID"
                            "refnundStatCd" => $data ['REFUND_STAT_CD'],   //退款状态码"
                            "msg" => $MESSAGE,            //消息推送状态，只要发出了请求就成功就会使msg=SUCCESS
                            "oaNo" => '',                         //oa单号，只会在退款成功时才会非空
                            "code" => "2000",                //消息推送状态，只要请求发送成功就会是code=2000
                            "refnundDate" => date('Y-m-d\TH:i:s\Z', time())
                        ]
                    ]
                ]
            ];

            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                // 限制，只能是Gshopper订单才发送到Q队列
                if ($result ['PLAT_FORM'] == 'N000831400') {
                    $rbmq->setData($msg);
                    $rbmq->submit();
                }
                $info ['message'] = L('操作成功');
                $info ['status'] = '200';
                $log = A('Log');
                $log->index($ORD_ID, 'N001080400', $MESSAGE);
                $this->ajaxReturn(0, $info, 1);
            } else {
                if (!$rbmq->submit()) $m = L('数据如Q失败，请重试');
                else $m = L('操作失败，没有数据改变');
                $info ['message'] = $m;
                $info ['status'] = '300';
                $this->ajaxReturn(0, $info, 0);
            }
        }

        $info ['message'] = '未查询到订单' . $ORD_ID;
        $info ['status'] = '300';
        $this->ajaxReturn(0, $info, 0);
    }

    /**
     * 同意退款
     * 退款成功 N001080300
     */
    public function refund_success()
    {
        if (!IS_AJAX) {
            $info ['message'] = L('异常访问');
            $info ['status'] = 503;
            $this->ajaxReturn(0, $info, 0);
        }
        $ORD_ID = I('ORD_ID');
        $MESSAGE = I('OA_CD');

        // tb_ms_ord 退款状态修改
        $model = M('ms_ord', 'tb_');
        $data ['REFUND_STAT_CD'] = 'N001080300';
        $data ['OA_NUM'] = $MESSAGE;

        // 增加Q消息队列
        $rbmq = new RabbitMqModel();

        $result = $model->field('THRD_USER_ID, PLAT_FORM, OA_NUM, THIRD_ORDER_ID')->where('ORD_ID = "' . $ORD_ID . '"')->find();
        if ($result) {
            $msg = [
                "platCode" => $result ['PLAT_FORM'],//第三方平台code码
                "processId" => create_guid(),//uuid
                "data" => [
                    "orders" => [
                        [
                            "thirdOrdId" => $result ['THIRD_ORDER_ID'],      //第三方订单号id
                            "ordId" => $ORD_ID,                         //tb_ms_ordORD_ID"
                            "refnundStatCd" => $data ['REFUND_STAT_CD'],   //退款状态码"
                            "msg" => 'SUCCESS',            //消息推送状态，只要发出了请求就成功就会使msg=SUCCESS
                            "oaNo" => $result['OA_NUM'],                         //oa单号，只会在退款成功时才会非空
                            "code" => "2000",                //消息推送状态，只要请求发送成功就会是code=2000
                            "refnundDate" => date('Y-m-d\TH:i:s\Z', time())
                        ]
                    ]
                ]
            ];

            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                // 限制，只能是Gshopper订单才发送到Q队列
                if ($result ['PLAT_FORM'] == 'N000831400') {
                    $rbmq->setData($msg);
                    $rbmq->submit();
                }
                $info ['message'] = L('操作成功');
                $info ['status'] = '200';
                $log = A('Log');
                $log->index($ORD_ID, 'N001080300', '退款成功');
                $this->ajaxReturn(0, $info, 1);
            } else {
                if (!$rbmq->submit()) $m = L('数据如Q失败，请重试');
                else $m = L('操作失败，没有数据改变');
                $info ['message'] = L('操作失败，没有数据改变');
                $info ['status'] = '300';
                $this->ajaxReturn(0, $info, 0);
            }
        }

        $info ['message'] = '未查询到订单' . $ORD_ID;
        $info ['status'] = '300';
        $this->ajaxReturn(0, $info, 0);
    }

    /**
     * 获取退款查询条件
     */
    public function getRefundStatWhere()
    {
        $where = null;
        if (I('REFUND_STAT_CD')) {
            $where['tb_op_order.REFUND_STAT_CD'] = I('REFUND_STAT_CD');
        }
        return $where;
    }

    public function getstore()
    {
        $plat = I('post.plat');
        $store = M('ms_store', 'tb_')->where('SITE_CD = "' . $plat . '"')->field('STORE_NAME,MERCHANT_ID')->select();
        $this->ajaxReturn(0, $store, 1);
    }

    public function syncorders()
    {
        $msg = '';
        $plat = I('post.plat');
        $store = I('post.store');
        $startTime = strtotime(I('post.startTime'));
        $endTime = strtotime(I('post.endTime'));
//        if (abs($startTime - $endTime) > 3600 * 3) {
//            $msg = '时间间隔不得超过3个小时';
//        }
        $url = SYNC_URL . '?merchantId=' . $store . '&platCd=' . $plat . '&startTime=' . ($startTime * 1000) . '&endTime=' . $endTime * 1000;
        $res = curl_request($url);
        $res = str_replace('\\"', '"', $res);
        $res = substr($res, 1, strlen($res) - 2);
        $res = json_decode($res, true);
        if ($res['resultCode'] == 'SUCCESS') {
            foreach ($res['data'] as $order) {
                if ($order['resultCode'] == 'SUCCESS') {
                    $msg .= $order['orderId'] . ':' . $order['msg'] . PHP_EOL;
                }
            }
        }
        $msg = $msg == '' ? L('没有订单同步成功') : $msg;
        $this->ajaxReturn(0, $msg, 1);
    }

    //获取查询条件
    public function getwhere($type, $qg)
    {
        $expe_company = C('expe_company');
        $expe = $expe_company[I('EXPE_COMPANY')];
        $expe = implode(',', $expe);
        $ware = I('WARE_ADDRESS');
        if ($type == 1) {
            if (I('COUNTRY')) {
                $country_arr = BaseModel::join_country(I('COUNTRY'));
                $where_country['tb_op_order.ADDRESS_USER_COUNTRY'] = array('in', $country_arr);
                $where_country['tb_op_order.ADDRESS_USER_COUNTRY_CODE'] = array('in', $country_arr);
                $where_country['_logic'] = 'or';
                $where['_complex'] = $where_country;
//                $where['tb_op_order.ADDRESS_USER_COUNTRY'] = I('COUNTRY');

            }
            if ($expe) {
                $where['tb_op_order.SHIPPING_DELIVERY_COMPANY'] = array('in', $expe);
            }
            if ($ware) {
                $where['tb_ms_guds.DELIVERY_WAREHOUSE'] = array('in', $ware);
            }
            $STORE_NAME = I('STORE_NAME');
//            $BWC_USER_ID = I('BWC_USER_ID');
            if ($STORE_NAME) {
                $where['tb_op_order.PLAT_NAME'] = $STORE_NAME;
//                $where['tb_op_order.BWC_USER_ID'] = $BWC_USER_ID;
            }
            $where_STAT = 'BWC_ORDER_STATUS';

            $SHORT_SUPPLY = I('SHORT_SUPPLY');
            if ($logistic_status = I('LOGISTIC_STATUS')) {
                $where['tb_ms_ord_package.LOGISTIC_STATUS'] = array('in', $logistic_status);
            }

            if ($SHORT_SUPPLY) {
                $where['tb_op_order.SHORT_SUPPLY'] = $SHORT_SUPPLY;
            }
            $ALLOW_SENDOUT = I('ALLOW_SENDOUT');
            if ($ALLOW_SENDOUT) {
                $where['tb_op_order.SHIPPING_DELIVERY_COMPANY'] = array(array('EXP', 'IS not NULL'), array('neq', ''), 'and');
                $where['tb_op_order.SHIPPING_TRACKING_CODE'] = array(array('EXP', 'IS not NULL'), array('neq', ''), 'and');
            }

            $time = $this->search_time_get();


            if (I("keyword_type") == 'THIRD_ORDER_ID') {
//                $keywords_type = 'tb_op_order.ORDER_ID';
                $keywords_type = 'tb_op_order.ORDER_NO';
            } elseif (I("keyword_type") == 'GUDS_NM') {
                $keywords_type = 'tb_op_order_guds.ITEM_NAME';
            } elseif (I("keyword_type") == 'ORD_CUST_NM') {
                $keywords_type = 'tb_op_order.ADDRESS_USER_NAME';
            } elseif (I("keyword_type") == 'ADDRESS_USER_PHONE') {
                $keywords_type = 'tb_op_order.ADDRESS_USER_PHONE';
            } elseif (I("keyword_type") == 'B5C_ORDER_NO') {
                $keywords_type = 'tb_op_order.B5C_ORDER_NO';
            } elseif (I("keyword_type") == 'B5C_SKU_ID') {
                $keywords_type = 'tb_op_order_guds.B5C_SKU_ID';
            } elseif (I("keyword_type") == 'B5C_ACCOUNT_ID') {
                $keywords_type = 'tb_op_order.B5C_ACCOUNT_ID';
            }

            // 退货状态
            $where_REFUND = 'tb_op_order.REFUND_STAT_CD';
            if ($REFUND_STAT_CD = I('REFUND_STAT_CD')) {
                $where[$where_REFUND] = is_array($REFUND_STAT_CD) ? array('in', $REFUND_STAT_CD) : $REFUND_STAT_CD;
            }

            if ($PLAT_CD = I('PLAT_CD')) {
                $where['tb_ms_store.PLAT_CD'] = is_array($PLAT_CD) ? array('in', $PLAT_CD) : $PLAT_CD;
            }
            if ($PLAT_CD = I('STORE')) {
                $where['tb_ms_store.MERCHANT_ID'] = $PLAT_CD;
            }
            $num = 'tb_op_order_guds.ITEM_COUNT';
            $minnum = (int)I('minnum');
            $maxnum = (int)I('maxnum');
            if ($maxnum && $minnum) {
                if ($maxnum < $minnum) {
                    $tmp = $maxnum;
                    $maxnum = $minnum;
                    $minnum = $tmp;
                }
                $where[$num] = array(array('egt', $minnum), array('elt', $maxnum));
            } elseif ($minnum) {
                $where[$num] = array('egt', $minnum);
            } elseif ($minnum) {
                $where[$num] = array('elt', $maxnum);
            }
        } else {
            if ($expe) {
                $where['tb_ms_ord_package.EXPE_COMPANY'] = array('in', $expe);
            }
            if ($qg) {
                $where_STAT = 'OPTION_MODE_CD';
                $table_prefix = 'tb_ms_ord_spl.';
//                $GUDS_NM = 'sms_ms_estm_guds.ORD_GUDS_CNS_NM';
                $GUDS_NM = 'tb_ms_ord_guds_opt.ORD_GUDS_CNS_NM';
                if (I("keyword_type") == 'PAY_ID') {
                    $keywords_type = 'bbm_pay_order.pay_id';
                }
            } else {
                $where_STAT = 'ORD_STAT_CD';
                $table_prefix = 'tb_ms_ord.';
                $GUDS_NM = 'tb_ms_guds.GUDS_NM';
                if (I("keyword_type") == 'PAY_ID') {
                    $keywords_type = $table_prefix . 'PAY_ID';
                }
            }
            // 退货状态
            $where_REFUND = 'tb_ms_ord.REFUND_STAT_CD';
            if ($REFUND_STAT_CD = I('REFUND_STAT_CD')) {
                $where[$where_REFUND] = is_array($REFUND_STAT_CD) ? array('in', $REFUND_STAT_CD) : $REFUND_STAT_CD;
            }
            if ($PLAT_CD = I('PLAT_CD')) {
                $where['tb_ms_ord.PLAT_FORM'] = is_array($PLAT_CD) ? array('in', $PLAT_CD) : $PLAT_CD;
            }
            $time = $this->search_time_new_get($table_prefix);
            if (I("keyword_type") == 'ORD_ID') {
                $keywords_type = $table_prefix . 'ORD_ID';
            } elseif (I("keyword_type") == 'CUST_ID') {
                $keywords_type = $table_prefix . 'CUST_ID';
            } elseif (I("keyword_type") == 'GUDS_NM') {
                $keywords_type = $GUDS_NM;
            } elseif (I("keyword_type") == 'GUDS_ID') {
                $keywords_type = 'tb_ms_ord_guds_opt.GUDS_OPT_ID';
            } elseif (I("keyword_type") == 'EXPE_COMPANY') {
                $keywords_type = 'tb_ms_ord_package.EXPE_COMPANY';
            } elseif (I("keyword_type") == 'THIRD_SUPPLIER') {
                $keywords_type = 'tb_ms_ord_guds_opt.THIRD_SUPPLIER';
            } elseif (I("keyword_type") == 'THIRD_SERIAL_NUMBER') {
                $keywords_type = 'tb_ms_ord_guds_opt.THIRD_SERIAL_NUMBER';
            } elseif (I("keyword_type") == 'ORD_CUST_NM') {
                $keywords_type = $table_prefix . 'ORD_CUST_NM';
            } elseif (I("keyword_type") == 'ORD_CUST_CP_NO') {
                $keywords_type = $table_prefix . 'ORD_CUST_CP_NO';
            } elseif (I("keyword_type") == 'SH_SALER') {
                $keywords_type = 'tb_ms_cmn_cd.CD_VAL';
            } elseif (I("keyword_type") == 'TRACKING_NUMBER') {
                $keywords_type = 'tb_ms_ord_package.TRACKING_NUMBER';
            } elseif (I("keyword_type") == 'THIRD_ORDER_ID') {
                $keywords_type = 'tb_ms_ord.THIRD_ORDER_ID';
            }

        }
        if (I('starttime') && I('endtime')) {
            $where[$time] = array(array('gt', I('starttime')), array('lt', I('endtime') . ' 23:59:59'));
        } elseif (I('starttime')) {
            $where[$time] = array('gt', I('starttime'));
        } elseif (I('endtime')) {
            $where[$time] = array('lt', I('endtime') . ' 23:59:59');
        }

        if (I("keywords") != '') {
            $keywords = I("keywords");
            $where[$keywords_type] = array('like', '%' . trim($keywords) . '%');
        }
        if ($SEND_STATUS = I('SEND_STATUS')) {
            $where_send_STAT = 'SEND_ORD_STATUS';
            $where[$where_send_STAT] = is_array($SEND_STATUS) ? array('in', $SEND_STATUS) : $SEND_STATUS;
            if (is_array($SEND_STATUS)) {
                /*$where['tb_op_order.ADDRESS_USER_REGION'] = array('exp', 'is not null');
                $where['tb_op_order.ADDRESS_USER_CITY'] = array('exp', 'is not null');
                $where['tb_op_order.ADDRESS_USER_ADDRESS1'] = array('exp', 'is not null');
                $where['tb_op_order.ADDRESS_USER_PHONE'] = array('exp', 'is not null');*/
                $where['_complex'] = $this->whereTempJoin();
                if ($where['tb_op_order_guds.B5C_SKU_ID']) {
                    $like = $where['tb_op_order_guds.B5C_SKU_ID'][1];
                    $where['tb_op_order_guds.B5C_SKU_ID'] = array('exp', 'is not null AND tb_op_order_guds.B5C_SKU_ID like \'' . $like . '\'');
                } else {
                    $where['tb_op_order_guds.B5C_SKU_ID'] = array('exp', 'is not null');
                }

            }
        }

        if ($ORD_STAT_CD = I('ORD_STAT_CD')) {
            $where[$where_STAT] = is_array($ORD_STAT_CD) ? array('in', $ORD_STAT_CD) : $ORD_STAT_CD;
        }
        // 平台选择
        //$where_FLAT = 'tb_ms_ord.PLAT_FORM';
//        if ($FLAT_FORM = I('PLAT_CD')) {
//            $where[$where_FLAT] = $FLAT_FORM;
//        }
        // 店铺账号选择
        $where_B5C_Account_id = 'tb_ms_cust.CUST_ID';
        if ($BWC_USER_ID = I('BWC_USER_ID')) {
            $where[$where_B5C_Account_id] = $BWC_USER_ID;
        }
        //echo $where;
        return !empty($where) ? $where : [];
    }

    public function getWhereNew()
    {
        $expe_company = C('expe_company');
        $expe = $expe_company[I('EXPE_COMPANY')];
        $expe = implode(',', $expe);
        $ware = I('WARE_ADDRESS');
        if (1) {
            if ($expe) {
                $where['tb_ms_ord_package.EXPE_COMPANY'] = array('in', $expe);
            }
            if ($ware) {
                $where['tb_ms_guds.DELIVERY_WAREHOUSE'] = array('in', $ware);
            }
            $source = I('source');
            if (strlen_utf8($source)) {
                if ($source == 1) {
                    $where['tb_ms_thrd_cust.bi'] = array('exp', 'is not null');
                } else if ($source == 0) {
                    $where['tb_ms_thrd_cust.bi'] = array('exp', 'is null');
                }

            }
        }

        $where_STAT = 'ORD_STAT_CD';
        $table_prefix = 'tb_ms_ord.';
        $GUDS_NM = 'tb_ms_guds.GUDS_NM';
        if (I("keyword_type") == 'PAY_ID') {
            $keywords_type = $table_prefix . 'PAY_ID';
        }
        // 退货状态
        $where_REFUND = 'tb_ms_ord.REFUND_STAT_CD';
        if ($REFUND_STAT_CD = I('REFUND_STAT_CD')) {
            $where[$where_REFUND] = $REFUND_STAT_CD;
        }
        if ($PLAT_CD = I('PLAT_CD')) {
            $where['tb_ms_ord.PLAT_FORM'] = $PLAT_CD;
        } else {
            $where['tb_ms_ord.PLAT_FORM'] = ['in', $this->b2c_plats];
        }
        $time = $this->search_time_new_get($table_prefix);
        if (I("keyword_type") == 'ORD_ID') {
            $keywords_type = $table_prefix . 'ORD_ID';
        } elseif (I("keyword_type") == 'CUST_ID') {
            $keywords_type = $table_prefix . 'CUST_ID';
        } elseif (I("keyword_type") == 'GUDS_NM') {
            $keywords_type = $GUDS_NM;
        } elseif (I("keyword_type") == 'GUDS_ID') {
            $keywords_type = 'tb_ms_ord_guds_opt.GUDS_OPT_ID';
        } elseif (I("keyword_type") == 'EXPE_COMPANY') {
            $keywords_type = 'tb_ms_ord_package.EXPE_COMPANY';
        } elseif (I("keyword_type") == 'THIRD_SUPPLIER') {
            $keywords_type = 'tb_ms_ord_guds_opt.THIRD_SUPPLIER';
        } elseif (I("keyword_type") == 'THIRD_SERIAL_NUMBER') {
            $keywords_type = 'tb_ms_ord_guds_opt.THIRD_SERIAL_NUMBER';
        } elseif (I("keyword_type") == 'ORD_CUST_NM') {
            $keywords_type = $table_prefix . 'ORD_CUST_NM';
        } elseif (I("keyword_type") == 'ORD_CUST_CP_NO') {
            $keywords_type = $table_prefix . 'ORD_CUST_CP_NO';
        } elseif (I("keyword_type") == 'SH_SALER') {
            $keywords_type = 'tb_ms_cmn_cd.CD_VAL';
        } elseif (I("keyword_type") == 'TRACKING_NUMBER') {
            $keywords_type = 'tb_ms_ord_package.TRACKING_NUMBER';
        } elseif (I("keyword_type") == 'THIRD_ORDER_ID') {
            $keywords_type = 'tb_ms_ord.THIRD_ORDER_ID';
        }

        if (I('starttime') && I('endtime')) {
            $where[$time] = array(array('gt', I('starttime')), array('lt', I('endtime') . ' 23:59:59'));
        } elseif (I('starttime')) {
            $where[$time] = array('gt', I('starttime'));
        } elseif (I('endtime')) {
            $where[$time] = array('lt', I('endtime') . ' 23:59:59');
        }

        if (I("keywords") != '') {
            $keywords = I("keywords");
            $where[$keywords_type] = array('like', '%' . trim($keywords) . '%');
        }

        if ($ORD_STAT_CD = I('ORD_STAT_CD')) {
            $where[$where_STAT] = $ORD_STAT_CD;
        }
        // 平台选择
        //$where_FLAT = 'tb_ms_ord.PLAT_FORM';
        //        if ($FLAT_FORM = I('PLAT_CD')) {
        //            $where[$where_FLAT] = $FLAT_FORM;
        //        }
        // 店铺账号选择
        $where_B5C_Account_id = 'tb_ms_cust.CUST_ID';
        if ($BWC_USER_ID = I('BWC_USER_ID')) {
            $where[$where_B5C_Account_id] = $BWC_USER_ID;
        }
        return !empty($where) ? $where : [];
    }

    //获取排序
    public function getorder($type, $qg)
    {
        if ($type == 1) {
            $PAY_DTTM = 'tb_op_order.ORDER_PAY_TIME';
            $SYS_REG_DTTM = 'tb_op_order.ORDER_TIME';
            $total_price = 'tb_op_order.PAY_PRICE';
            $REMARK_STAT_CD = 'tb_op_order.REMARK_STAT_CD';
        } else {
            if ($qg == 1) {
                $PAY_DTTM = 'tb_ms_ord_spl.PAY_DTTM';
                $SYS_REG_DTTM = 'tb_ms_ord_spl.SYS_REG_DTTM';
                $GUDS_ID = 'tb_ms_ord_guds_opt.GUDS_OPT_ID';
                $total_price = 'total_price';
                $REMARK_STAT_CD = 'tb_ms_ord_spl.REMARK_STAT_CD';
            } else {
                $PAY_DTTM = 'tb_ms_ord.PAY_DTTM';
//                $SYS_REG_DTTM = 'tb_ms_ord.SYS_REG_DTTM';
                $SYS_REG_DTTM = 'tb_ms_ord.ESTM_RCP_REQ_DT';
                $GUDS_ID = 'tb_ms_ord_guds_opt.GUDS_ID';
                $total_price = 'total_price';
                $REMARK_STAT_CD = 'tb_ms_ord.REMARK_STAT_CD';
            }
        }

        if ($sort = I('sort_type')) {
            if ($sort == 'PAY_DTTM') {
                $table_pre = $PAY_DTTM;
            } elseif ($sort == 'SYS_REG_DTTM') {
                $table_pre = $SYS_REG_DTTM;
            } elseif ($sort == 'total_price') {
                $table_pre = $total_price;
            } elseif ($sort == 'GUDS_ID') {
                $table_pre = $GUDS_ID;
            } elseif ($sort == 'REMARK_STAT_CD') {
                $table_pre = $REMARK_STAT_CD;
            }
            $order = I('sort_value') == 1 ? $table_pre . ' desc' : $table_pre . ' asc';
        } else {
            $order = $SYS_REG_DTTM . ' desc';
        }
        return $order;
    }


    public function download()
    {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Home/Orders/' . $name;
        Http::download($filename, $filename);
    }

    //关闭订单、批量发货
    public function bulkset()
    {
        $ords = I('post.ords');
        $type = I('post.type');
        $ord_type = I('post.ord_type');
        if ($type == 1) {
            //批量关闭
//            $this->closeord($ords);
            if ($ord_type == 0) {
                $this->closeord_bulk($ords);
            }
            if ($ord_type == 1) {
                $this->closeord_bulk($ords);
            } elseif ($ord_type == 2) {
                $this->closeord_dm($ords);
            } elseif ($ord_type == 3) {
                $this->closeord_bw($ords);
            } elseif ($ord_type == 4) {
                $this->closeord_xh($ords);
            } elseif ($ord_type == 'qg') {
                $this->closeord_qg($ords);
            } elseif ($ord_type == 'new_self') {
                $this->closeord_gshopper($ords);
            }
        } elseif ($type == 2) {
            //批量发货
            if ($ord_type == 1) {
                $this->bulksendout_bulk($ords);
            } elseif ($ord_type == 2) {
                $this->bulksendout_dm($ords);
            } elseif ($ord_type == 3) {
                $this->bulksendout_bw($ords);
            } elseif ($ord_type == 4) {
                $this->bulksendout_xh($ords);
            } elseif ($ord_type == 'qg') {
                $this->bulksendout_qg($ords);
            } elseif ($ord_type == 'self') {
                if (strpos($ords, ',')) {
                    $ords = explode(',', $ords);
                    foreach ($ords as $key => $val) {
                        $this->bulksendout_self($val);
                    }
                } else {
                    $this->bulksendout_self($ords);
                }

            }
        }
    }

    public function closeord_bulk($ords)
    {
        $this->closeord($ords);
    }

    public function closeord_dm($ords)
    {
        $this->closeord($ords);
    }

    public function closeord_bw($ords)
    {
        $this->closeord($ords);
    }

    public function closeord_xh($ords)
    {
        $this->closeord($ords);
    }

    public function closeord_qg($ords)
    {
        $this->closeord($ords, 1);
    }

    public function closeord_gshopper($ords)
    {
        $this->closeord($ords);
    }

    /**
     * @param $ords
     */
    public function close_order_patch($ords)
    {
        $ordlist = explode(',', $ords);
        if ($this->check_b5cOrderID($ordlist)) {
            $CLOSE_TYPE = 'N000550900';
            $res = OrdersModel::orderStatus_upd($ordlist, $CLOSE_TYPE);
            $this->ajaxReturn(0, $res['msg'], 1);
        } else {
            $close_order_arr = OrdersModel::join_close_order($ordlist);
            if (count($close_order_arr)) {

            } else {
                $this->ajaxReturn(0, L('操作失败，订单为空'), 0);
            }
        }
    }

//关闭订单
    public function closeord($ords, $qg)
    {
        $readyIn = true;
        //区分求购，不同表
        //关闭订单改变订单状态
        if ($qg == 1) {
            $readyIn = false;
            $data['OPTION_MODE_CD'] = 'N000550900';
            $model = M('ms_ord_spl', 'tb_');  //订单从表
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $data['ORD_STAT_CD'] = 'N000550900';
            $model = M('ms_ord', 'tb_');
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }

        $message = '关闭成功';
        LogsModel::$project_name = 'closeOrd';
        if (preg_match_all('/\\\\/', $ords) !== false) {
            $ords = str_replace('\\', '\\\\', $ords);
            Logs($ords, 'err_ords');
        }
        $ORDLIST = explode(',', $ords);
        $guds_opt = M('ms_ord_guds_opt', 'tb_');
        $PLAT_FORM = [
            'N000831400',
            'N000834100',
            'N000834200',
            'N000834300'
        ];
        $ret = $model->where('ORD_ID in (' . $ords . ') and PLAT_FORM in (\'' . implode('\',\'', $PLAT_FORM) . '\')')->select();
        //不能关闭的订单状态
        $can_not_close_order_states = [
            'N000550900',
            'N000551000',
            'N000550300'
        ];
        Logs($ret, 'retAct');
        if ($ret) {
            foreach ($ret as $key => $value) {
                if (in_array($value['ORD_STAT_CD'], $can_not_close_order_states)) {
                    unset($ret[$key]);
                }
            }
            if ($ret) {
                foreach ($ret as $k => $v) {
                    $nords [] = $v['ORD_ID'];
                }
                if ($nords) {
                    $where ['tb_ms_ord.ORD_ID'] = ['in', $nords];
                    $data ['REFUND_STAT_CD'] = 'N001080300';
                    $close_thr_arr = OrdersModel::close_order_thr($ords);
                    Logs($close_thr_arr, 'close_thr_arr');
                    if ($model->data($data)->where($where)->save() == true) {
                        if ($readyIn) {
                            $fields = 'tb_ms_ord.THIRD_ORDER_ID,
                                        tb_ms_ord.ORD_ID, 
                                        tb_ms_ord.PLAT_FORM,
                                        tb_ms_ord_package.EXPE_COMPANY,
                                        tb_ms_ord_package.TRACKING_NUMBER,
                                        tb_ms_ord_package.EXPE_CODE, 
                                        tb_ms_ord_package.SYS_REG_DTTM,
                                        tb_ms_ord.REFUND_STAT_CD';
                            $ret = $model->field($fields)
                                ->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')
                                ->where($where)
                                ->select();
                            if ($ret) {
                                $orders = null;
                                foreach ($ret as $key => $value) {
                                    if (in_array($value ['PLAT_FORM'], $PLAT_FORM)) {
                                        $order = [
                                            "thirdOrdId" => $value ['THIRD_ORDER_ID'],
                                            "ordId" => $value ['ORD_ID'],
                                            "ordStatCd" => "N000550900",
                                            "msg" => "关闭订单",
                                            "expeCompany" => $value ['EXPE_COMPANY'],
                                            "trackingNumber" => $value ['TRACKING_NUMBER'],
                                            "expeCode" => $value ['EXPE_CODE'],
                                            "expeDate" => $value ['SYS_REG_DTTM'],
                                            "refnundStatCd" => $value ['REFUND_STAT_CD'],
                                            "oaNO" => $value ['OA_NUM'],
                                        ];
                                        $orders [] = $order;
                                        $orders_plat [$value ['PLAT_FORM']][] = $order;
                                    }
                                }
                                //关闭订单后发送q队列
                                if ($orders) {                           //RabbitMq队列
                                    $rbmq = new RabbitMqModel();
                                    $rbmq->exchangeName = 'gshopperExchange';
                                    $rbmq->routeKey = 'Q-B5C2GS-02-RK-01';
                                    $rbmq->queueName = 'Q-B5C2GS-02';
                                    foreach ($orders_plat as $k => $v) {
                                        $msg = [
                                            "platCode" => $k,
                                            "processId" => create_guid($k),
                                            "data" => [
                                                "orders" => $v
                                            ]
                                        ];
                                        $rbmq->setData($msg);
                                        $rbmq_status = $rbmq->submit(false);
                                        trace($rbmq_status, '$rbmq_status');
                                        trace($msg, '$msg');
                                    }
                                    $rbmq->disConnect();
                                }
                            }
                            // 取出订单表的ord_id和PLAT_FORM
                            $list_plat_form = array_column($ret, 'ORD_ID', 'PLAT_FORM');   //从$ret中取出下标是ord_id,plat_form的值组成数组
                            //var_dump($list_plat_form);


                            //日志
                            foreach ($nords as $key => $val) {
                                $ORD_ID = trim($val, "'");

                                $content = '订单关闭';
                                //echo $content;die;
                                $log = A('Log');   //实例化日志类
                                $log->index($ORD_ID, $ORD_STAT_CD, $content);  //关闭订单打印日志
                                $where = null;
                                //            查询占用
                                if ($this->checkOccupy($ORD_ID, 'onlyOccupy')) {    //查询订单操作历史
                                    $where['ORD_ID'] = $ORD_ID;
                                    $guds_all = $guds_opt->where($where)->select();
                                    Logs($guds_opt->getLastSql(), '$last_sql');
                                    Logs($guds_all, '$guds_all');
                                    foreach ($guds_all as $k => $v) {
                                        $cancel_data['gudsId'] = $v['GUDS_ID'];
                                        $cancel_data['skuId'] = $v['GUDS_OPT_ID'];
                                        $cancel_data['orderId'] = $v['ORD_ID'];
                                        $cancel_datas[] = $cancel_data;

                                        /*if ($this->checkOccupySku($ORD_ID, $v['GUDS_OPT_ID'])) {
                                            $skuId = $v['GUDS_OPT_ID'];
                                            $gudsId = $v['GUDS_ID'];
                                            $changeNm = (int)$v['ORD_GUDS_QTY'];
                                            $outgo_state = '-';
                                            $ordId = $v['ORD_ID'];
                                            //$url = HOST_URL_API . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . $skuId . '&number=' . $outgo_state . $changeNm . '&ordId=' . $ordId;
                                            $url = HOST_URL_API . '/guds_stock/update_occupy.json';
                                            $url .= '?gudsId=' . $gudsId;
                                            $url .= '&skuId=' . $skuId;
                                            $url .= '&number=' . $outgo_state . $changeNm;
                                            $url .= '&ordId=' . $ordId;
                                            $url .= '&channel=' . $list_plat_form[$v['ORD_ID']];
                                            if ($list_plat_form[$v['ORD_ID']] == 'N000831400') $url .= '&channelSkuId=' . $v['wrapped_skuid'];
                                            trace($url, '$url');
                                            $result = json_decode(curl_request($url), 1);
                                            $log = A('Log');
                                            $log->index($ORD_ID, $skuId, $result['msg'] . $url);    //获取订单数据打印日志
                                        }*/
                                    }
                                }
                            }
                            Logs($cancel_datas, 'cancel_datas');
                            if (count($cancel_datas) > 0) {
                                $cancel_res = OrdersModel::cancel_order($cancel_datas);
                                Logs($cancel_res, 'cancel_res');
                            }
                            $this->ajaxReturn(0, $message, 1);
                        } else {
                            $this->ajaxReturn(0, L('操作失败，订单已关闭'), 0);
                        }
                    } else {
                        $this->ajaxReturn(0, L('处理失败，订单已关闭'), 0);
                    }
                }
            } else {
                $this->ajaxReturn(0, L('操作失败，订单状态错误或订单已关闭'), 0);
            }
        } else {
            $close_thr_arr = OrdersModel::close_order_thr($ords);
            Logs($close_thr_arr, 'close_thr_arr');
            $close_order_status = $model->data($data)->where('ORD_ID in (' . $ords . ') OR THIRD_ORDER_ID in (' . $ords . ')')->save();
            $close_order_status = true;
            Logs($close_order_status, 'close_order_status');
            if ($close_order_status == true) {
                if ($readyIn) {
                    $fields = 'tb_ms_ord.THIRD_ORDER_ID, tb_ms_ord.ORD_ID, tb_ms_ord.PLAT_FORM,
                               tb_ms_ord_package.EXPE_COMPANY, tb_ms_ord_package.TRACKING_NUMBER,
                               tb_ms_ord_package.EXPE_CODE, tb_ms_ord_package.SYS_REG_DTTM';
                    $ret = $model->field($fields)
                        ->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')
                        ->where('tb_ms_ord.ORD_ID in (' . $ords . ')')
                        ->select();
                    if ($ret) {
                        $orders = null;
                        foreach ($ret as $key => $value) {
                            if ($value ['PLAT_FORM'] == 'N000831400') {
                                $order = [
                                    "thirdOrdId" => $value ['THIRD_ORDER_ID'],
                                    "ordId" => $value ['ORD_ID'],
                                    "ordStatCd" => "N000550900",
                                    "msg" => "关闭订单",
                                    "expeCompany" => $value ['EXPE_COMPANY'],
                                    "trackingNumber" => $value ['TRACKING_NUMBER'],
                                    "expeCode" => $value ['EXPE_CODE'],
                                    "expeDate" => $value ['SYS_REG_DTTM'],
                                    "refnundStatCd" => $value ['REFUND_STAT_CD']
                                ];
                                $orders [] = $order;
                            }
                        }
                        if ($orders) {
                            $rbmq = new RabbitMqModel();
                            $rbmq->exchangeName = 'gshopperExchange';
                            $rbmq->routeKey = 'statusOrder';

                            $msg = [
                                "platCode" => 'N000831400',
                                "processId" => create_guid(),
                                "data" => [
                                    "orders" => $orders
                                ]
                            ];
                            $rbmq->setData($msg);
                            if (!$rbmq->submit()) {
                                throw new \Exception('消息推送至Q队列失败');
                            }
                        }
                    }


                    // 取出订单表的ord_id和PLAT_FORM
                    $list_plat_form = array_column($ret, 'ORD_ID', 'PLAT_FORM');
                    //日志
                    Logs($ORDLIST, '$ORDLIST');
                    foreach ($ORDLIST as $key => $val) {
                        $ORD_ID = trim($val, "'");
                        $content = '订单关闭';
                        $log = A('Log');
                        $log->index($ORD_ID, $ORD_STAT_CD, $content);
                        // 查询占用
                        $ORD_ID = $this->order_thr_to_erp($ORD_ID);
                        if ($this->checkOccupy($ORD_ID, 'onlyOccupy')) {
                            $where['ORD_ID'] = $ORD_ID;
                            $guds_all = $guds_opt->where($where)->select();
                            foreach ($guds_all as $k => $v) {
                                $cancel_data['gudsId'] = $v['GUDS_ID'];
                                $cancel_data['skuId'] = $v['GUDS_OPT_ID'];
                                $cancel_data['orderId'] = $v['ORD_ID'];
                                $cancel_datas[] = $cancel_data;
                            }

                        }
                    }
                    Logs($cancel_datas, 'cancel_datas');
                    if (count($cancel_datas) > 0) {
                        $cancel_res = OrdersModel::cancel_order($cancel_datas);
                        Logs($cancel_res, 'cancel_res');
                    }
                    $this->ajaxReturn(0, $message, 1);
                } else {
                    Logs($model->getLastSql(), 'getSql');
                    $this->ajaxReturn(0, L('操作失败，订单已关闭'), 0);
                }
            } else {
                $this->ajaxReturn(0, L('操作失败，订单关闭保存失败'), 0);
            }
        }
    }

    /**
     * close closed orders
     */
    public function closed_orders()
    {
        set_time_limit(1800);
        $close_arr = ['N000550900'];
        $ORDLIST = array_column(OrdersModel::get_statusOrd($close_arr), 'ORD_ID');
        $guds_opt = M('ms_ord_guds_opt', 'tb_');
        $where['ORD_ID'] = array('in', $ORDLIST);
        $guds_all = $guds_opt->field('GUDS_ID,GUDS_OPT_ID,ORD_ID')->where($where)->select();
        foreach ($guds_all as $v) {
            $cancel_data['gudsId'] = $v['GUDS_ID'];
            $cancel_data['skuId'] = $v['GUDS_OPT_ID'];
            $cancel_data['orderId'] = $v['ORD_ID'];
            $cancel_datas[] = $cancel_data;
        }
        if (count($cancel_datas) > 0) {
            ob_start();
            foreach ($cancel_datas as $v) {
                unset($test_arr);
                $test_arr[] = $v;
                $cancel_res = OrdersModel::cancel_order($test_arr);
                Logs($v, '$v');
                Logs($cancel_res, 'cancel_res');
                echo $v['orderId'] . '-' . json_encode($cancel_res, JSON_UNESCAPED_UNICODE) . "\r\n <br>";
                flush();
                ob_flush();
                usleep(500000);
            }
            ob_clean();
        }
    }

    /**
     * received order close
     */
    public function received_orders()
    {
        set_time_limit(1800);
        $handle_arr = ['N000550500', 'N000550800'];
        $ord_id_arr_all = array_column(OrdersModel::get_statusOrd($handle_arr), 'ORD_ID');
        Logs($ord_id_arr_all, '$ord_id_arr_all');
        $bill = null;
        ob_start();
        ob_end_flush();
        foreach ($ord_id_arr_all as $v) {
            unset($ord_id_arr);
            $ord_id_arr[] = $v;
            $out_msg_arr = OrdersModel::go_all_batch($ord_id_arr, $bill);
            Logs($out_msg_arr, 'timing_send_out_msg');
            echo $v . '-' . $out_msg_arr['code'] . '-' . $out_msg_arr['info'] . "\n<br>";
            flush();
            ob_flush();
            usleep(500000);
        }
        ob_clean();
    }

    public function bulksendout_bulk($ords)
    {
        $this->bulksendout($ords);
    }

    public function bulksendout_dm($ords)
    {
        $this->bulksendout($ords);
    }

    public function bulksendout_bw($ords)
    {
        $this->bulksendout($ords);
    }

    public function bulksendout_xh($ords)
    {
        $this->bulksendout($ords);
    }

    public function bulksendout_qg($ords)
    {
        $this->bulksendout($ords, 1);
    }

    public function bulksendout($ords, $qg)
    {
        if ($qg == 1) {
            $data['OPTION_MODE_CD'] = 'N000550500';
            $model = M('ms_ord_spl', 'tb_');
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $data['ORD_STAT_CD'] = 'N000550500';
            $model = M('ms_ord', 'tb_');
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }

        $message = L('批量设置发货成功');
        $ORDLIST = explode(',', $ords);
        if ($model->data($data)->where('ORD_ID in (' . $ords . ')')->save() == true) {
            //日志
            foreach ($ORDLIST as $val) {
                $ORD_ID = trim($val, "'");
                $content = '设置发货成功';
                $log = A('Log');
                $log->index($ORD_ID, $ORD_STAT_CD, $content);
            }
            $this->ajaxReturn(0, $message, 1);
        } else {

        }
    }

    /*read action*/

    public function bulksendout_self($ords)
    {
        $ORDLIST = explode('-', $ords);
        $ordId = str_replace("'", "", $ORDLIST[0]);
        $platId = str_replace("'", "", $ORDLIST[1]);
        $urlstock = SMS2_URL . 'index.php?m=stock&a=deliver';
        $message = "处理成功\r\n";
        $ord_m = new TbOpOrdModel();
        $res = $ord_m->sendOut($ordId, $platId);
        if ($res == true) {
            $this->ajaxReturn(0, $message, 1);
        } else {
            $this->ajaxReturn(0, $ord_m->getError(), 0);
        }
    }

//批量发货交付状态页面
    public function my_order()
    {

        $ords = I('request.ords');
        $type = I('post.type');
        $ord_type = I('post.ord_type');
        $ORDLIST = explode(',', $ords);
        $urlstock = SMS2_URL . 'index.php?m=stock&a=deliver';
        $message_orders = array();
        $message = [];
        /* $message = "处理成功\r\n";*/
        trace($ord_type, '$ord_type');
        if ($ord_type == 'self') {
            $ali_shop = ['N000832000', 'N000831900'];
            $Model = M();
            $plat_field = ['THIRD_ORDER_ID', 'PLAT_FORM'];
            $plat_id_arr = $Model->table('tb_ms_ord')->field($plat_field)->where(' THIRD_ORDER_ID IN (' . $ords . ')')->select();
            $plat_val_arr = array_column($plat_id_arr, 'PLAT_FORM', 'THIRD_ORDER_ID');
            foreach ($ORDLIST as $val) {
                $ord_id = trim($val, "'");
//                close  third ship
                $third_result['data']['orders'][0]['stat'] = true;
//                if (1 != 1 || in_array($plat_val_arr[$val], $ali_shop)) {
                if (1 != 1) {
                    $third_data['data']['orders']['0']['orderId'] = $ord_id;
                    $third_result = $this->Curl_post(THIRD_SHIP_API, json_encode($third_data));
                    $third_result = json_decode($third_result, true);
                }
                trace(isset($third_result['data']['orders'][0]['stat']) && $third_result['data']['orders'][0]['stat'] == true, 'status');
                if (isset($third_result['data']['orders'][0]['stat']) && $third_result['data']['orders'][0]['stat'] == true) {
                    $ord_m = new TbOpOrdModel();
                    $res = $ord_m->sendOut($ord_id);
                    trace($ord_id, '$ord_id');
                    trace($res, '$res');
                    if ($res == false) {
                        $message[] = $ord_id;
                        $message[] = $ord_m->getError();
                        $message_count = count($message);
                        for ($i = 0; $i < $message_count / 2; $i++) {
                            for ($a = 0; $a < 2; $a++) {
                                $message_orders[$i][$a] = $message[$i * 2 + $a];
                            }
                        }
                    }
                } else {
                    if (isset($third_result['data']['orders'][0]['order_msg'])) {
                        $info['message'] = '第三方接口:' . $third_result['data']['orders'][0]['order_msg'];
                    } elseif (isset($third_result['msg'])) {
                        $info['message'] = '第三方接口:' . $third_result['msg'];
                    } else {
                        $info['message'] = '第三方发货接口调取失败';
                    }
                    trace($info, '$info');
                    $info['message'] = L('第三方平台发货状态更新失败');
                    $message[] = $ord_id;
                    $message[] = $info['message'];
                    $message_count = count($message);
                    for ($i = 0; $i < $message_count / 2; $i++) {
                        for ($a = 0; $a < 2; $a++) {
                            $message_orders[$i][$a] = $message[$i * 2 + $a];
                        }
                    }
                }
            }
        } else {
            $push_arr = [];
            foreach ($ORDLIST as $val) {
                $ord_id = trim($val, "'");
                $ord_m = new TbOpOrdModel();
//                $res = $ord_m->sendOut($ord_id,null,$ord_type);
                $res = 1;
                trace($res, '$res');
                if ($res == false) {
                    /* $message .= $ord_id.'发货失败:'.$ord_m->getError()."\r\n";*/
                    $message[] = $ord_id;
                    $message[] = $ord_m->getError();
                    $message_count = count($message);
                    for ($i = 0; $i < $message_count / 2; $i++) {
                        for ($a = 0; $a < 2; $a++) {
                            $message_orders[$i][$a] = $message[$i * 2 + $a];
                        }
                    }
                } else {
                    $push_arr[] = $ord_id;
                }
            }
            if ($push_arr) {
                $push_status = $this->push_out_status($push_arr);
                trace($push_status, '$push_status');
            }
        }

        $orderlist_num = count($ORDLIST); //总订单数
        $orderlist_false = count($message_orders); //失败订单数量
        $orderlist_success = $orderlist_num - $orderlist_false; //成功订单的数量
        /* echo 1;*/
        $this->assign('message_orders', $message_orders);
        $this->assign('orderlist_num', $orderlist_num);
        $this->assign('orderlist_false', $orderlist_false);
        $this->assign('orderlist_success', $orderlist_success);
        $this->display('deliver_status');
        /*$this->ajaxReturn(0, $message, 1);*/
    }

    /**
     * 关闭订单
     */
    public function close_gshopper_order()
    {

        $ords = I('request.ords');
        $type = I('post.type');
        $ord_type = I('post.ord_type');
        $ORDLIST = explode(',', $ords);
        $urlstock = SMS2_URL . 'index.php?m=stock&a=deliver';
        $message_orders = array();
        $message = [];
        /* $message = "处理成功\r\n";*/
        foreach ($ORDLIST as $val) {
            $ord_id = trim($val, "'");
            $ord_m = new TbOpOrdModel();
            $res = $ord_m->sendOut($ord_id);
            if ($res == false) {
                /* $message .= $ord_id.'发货失败:'.$ord_m->getError()."\r\n";*/
                $message[] = $ord_id;
                $message[] = $ord_m->getError();
                $message_count = count($message);
                for ($i = 0; $i < $message_count / 2; $i++) {
                    for ($a = 0; $a < 2; $a++) {
                        $message_orders[$i][$a] = $message[$i * 2 + $a];
                    }
                }
            }

        }

        $orderlist_num = count($ORDLIST); //总订单数
        $orderlist_false = count($message_orders); //失败订单数量
        $orderlist_success = $orderlist_num - $orderlist_false; //成功订单的数量

        /* echo 1;*/
        $this->assign('message_orders', $message_orders);
        $this->assign('orderlist_num', $orderlist_num);
        $this->assign('orderlist_false', $orderlist_false);
        $this->assign('orderlist_success', $orderlist_success);
        $this->display('close_status');
        /*$this->ajaxReturn(0, $message, 1);*/
    }


    public function bulkedit_bulk()
    {
        $this->bulkedit();
    }

    public function bulkedit_dm()
    {
        $this->bulkedit();
    }

    public function bulkedit_bw()
    {
        $this->bulkedit();
    }

    public function bulkedit_xh()
    {
        $this->bulkedit();
    }

    public function bulkedit_qg()
    {
        $this->bulkedit(1);
    }

    //批量编辑
    public function bulkedit($qg = '')
    {
        //I方法获取不了数组,后续处理
        $params = $this->_param();
        $data = ($params['data']);
        foreach ($data as $val) {
            $data1 = array();
            if ($val['type'] == 1) {
                //编辑优惠运费
                $data1['DISCOUNT_MN'] = $val['discount'];
                $data1['DLV_AMT'] = $val['shipping'];
                if ($qg == 1) {
                    $old = M('ms_estm', 'sms_')->where('ORD_NO = "' . $val['ORD_ID'] . '"')->field('DISCOUNT_MN,DLV_AMT')->find();
                    M('ms_estm', 'sms_')->data($data1)->where('ORD_NO = "' . $val['ORD_ID'] . '"')->save();
                } else {
                    $old = M('ms_ord', 'tb_')->where('ORD_ID = "' . $val['ORD_ID'] . '"')->field('DISCOUNT_MN,DLV_AMT,ORD_STAT_CD')->find();
                    M('ms_ord', 'tb_')->data($data1)->where('ORD_ID = "' . $val['ORD_ID'] . '"')->save();
                }

                $olddiscount = $old['DISCOUNT_MN'] != '' ? $old['DISCOUNT_MN'] : 0;
                $olddlv = $old['DLV_AMT'] != '' ? $old['DLV_AMT'] : 0;
                $content = '';
                if ($old['DISCOUNT_MN'] != $val['discount']) {
                    $content = '订单：' . $val['ORD_ID'] . '修改优惠运费成功，优惠：' . $olddiscount . '->' . $val['discount'];
                }
                if ($old['DLV_AMT'] != $val['shipping']) {
                    if ($old['DISCOUNT_MN'] == $val['discount']) {
                        $content = '订单：' . $val['ORD_ID'] . '修改优惠运费成功，运费：' . $olddlv . '->' . $val['shipping'];
                    } else {
                        $content .= '运费：' . $olddlv . '->' . $val['shipping'];
                    }
                }
                if ($content != '') {
                    $log = A('Log');
                    $log->index($val['ORD_ID'], '', $content);
                }
            } else {
                //编辑物流
                $data1['TRACKING_NUMBER'] = $val['TRACKING_NUMBER'];
                $data1['EXPE_CODE'] = $val['EXPE_COMPANY'];
                $data1['EXPE_COMPANY'] = L($val['EXPE_COMPANY']);
                $ORD_ID = $val['ORD_ID'];
                $package = M('ms_ord_package', 'tb_')->where('ORD_ID = "' . $ORD_ID . '"')->select();
                if (empty($package)) {
                    $data1['ORD_ID'] = $ORD_ID;
                    $data1['SYS_REG_DTTM'] = date('Y-m-d H:i:s', time());
                    $data1['SYS_CHG_DTTM'] = date('Y-m-d H:i:s', time());
                    $data1['updated_time'] = date('Y-m-d H:i:s', time());
                    $guds = M('ms_ord_guds_opt', 'tb_')->join('left join tb_ms_guds on tb_ms_ord_guds_opt.GUDS_ID = tb_ms_guds.GUDS_ID')->field('tb_ms_ord_guds_opt.GUDS_OPT_ID as b5cSkuId,tb_ms_ord_guds_opt.SLLR_ID as brndId,tb_ms_ord_guds_opt.GUDS_ID as goodsId,tb_ms_ord_guds_opt.ORD_GUDS_QTY as goodsNum,tb_ms_guds.GUDS_NM as gudsCnsNm')->where('tb_ms_ord_guds_opt.ORD_ID = "' . $ORD_ID . '"')->select();
                    $data1['GOODS_LIST'] = json_encode($guds);
                    M('ms_ord_package', 'tb_')->data($data1)->add();
                } else {
                    M('ms_ord_package', 'tb_')->data($data1)->where('ORD_ID = "' . $ORD_ID . '"')->save();
                }
                $content = '物流信息修改成功：快递公司：' . $data1['EXPE_COMPANY'] . ',运单号：' . $data1['TRACKING_NUMBER'];
                $log = A('Log');
                $log->index($ORD_ID, '', $content);
            }
        }
        $this->ajaxReturn(0, L('批量编辑成功'), 1);
    }

    public function bulkedit_self()
    {
        $params = $this->_param();
        $data = ($params['data']);
        foreach ($data as $val) {
            $ORD_ID = $val['ORD_ID'];
            $data1 = [];
            $data1['SHIPPING_TRACKING_CODE'] = $val['TRACKING_NUMBER'];
            $data1['SHIPPING_DELIVERY_COMPANY'] = L($val['EXPE_COMPANY']);
            if (M('op_order', 'tb_')->data($data1)->where('ORDER_ID = "' . $ORD_ID . '"')->save()) {
                $content = '物流信息修改成功：快递公司：' . $data1['SHIPPING_DELIVERY_COMPANY'] . ',运单号：' . $data1['SHIPPING_TRACKING_CODE'];
                $log = A('Log');
                $log->index($ORD_ID, 'N000550400', $content);
            }
            $order = M('op_order', 'tb_')->where('ORDER_ID = "' . $ORD_ID . '"')->find();
            if ($order) {
                if ($order['B5C_ORDER_NO']) {
                    $datap['EXPE_COMPANY'] = L($val['EXPE_COMPANY']);
                    $datap['TRACKING_NUMBER'] = $val['TRACKING_NUMBER'];
                    $datap['ORD_ID'] = $order['B5C_ORDER_NO'];
                    $datap['SYS_REG_DTTM'] = date('Y-m-d H:i:s', time());
                    $datap['SYS_CHG_DTTM'] = date('Y-m-d H:i:s', time());
                    $datap['updated_time'] = date('Y-m-d H:i:s', time());
                    if (M('ms_ord_package', 'tb_')->where('ORD_ID = "' . $order['B5C_ORDER_NO'] . '"')->find()) {
                        unset($datap['ORD_ID']);
                        M('ms_ord_package', 'tb_')->data($datap)->where('ORD_ID = "' . $order['B5C_ORDER_NO'] . '"')->save();
                    } else {
                        M('ms_ord_package', 'tb_')->data($datap)->add();
                    }
                }
            }
        }


        $this->ajaxReturn(0, L('批量编辑成功'), 1);
    }

    public function saveord_bulk()
    {
        $this->saveord();
    }

    public function saveord_dm()
    {
        $this->saveord();
    }

    public function saveord_bw()
    {
        $this->saveord();
    }

    public function saveord_xh()
    {
        $this->saveord();
    }

    public function saveord_qg()
    {
        $this->saveord();
    }


    //单个保存发货信息
    public function saveord()
    {
        $data['EXPE_CODE'] = I('post.EXPE_COMPANY');
        $data['EXPE_COMPANY'] = L(I('post.EXPE_COMPANY'));
        $data['TRACKING_NUMBER'] = I('post.TRACKING_NUMBER');
        $ORD_ID = I('post.ORD_ID');
        $package = M('ms_ord_package', 'tb_')->where('ORD_ID = "' . $ORD_ID . '"')->find();
        $info = array();
        $info['message'] = L('物流信息保存成功');
        $info['EXPE_COMPANY'] = $data['EXPE_COMPANY'];
        $info['EXPE_COMPANY_VAL'] = L($data['EXPE_COMPANY']);
        $info['TRACKING_NUMBER'] = $data['TRACKING_NUMBER'];
        if (empty($package)) {
            $data['ORD_ID'] = $ORD_ID;
            $data['SYS_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $data['SYS_CHG_DTTM'] = date('Y-m-d H:i:s', time());
            $data['updated_time'] = date('Y-m-d H:i:s', time());
            $fields = 'tb_ms_ord_guds_opt.GUDS_OPT_ID as b5cSkuId,tb_ms_ord_guds_opt.SLLR_ID as brndId,tb_ms_ord_guds_opt.GUDS_ID as goodsId,
                       tb_ms_ord_guds_opt.ORD_GUDS_QTY as goodsNum,tb_ms_guds.GUDS_NM as gudsCnsNm';
            $guds = M('ms_ord_guds_opt', 'tb_')
                ->join('left join tb_ms_guds on tb_ms_ord_guds_opt.GUDS_ID = tb_ms_guds.GUDS_ID')
                ->field($fields)
                ->where('tb_ms_ord_guds_opt.ORD_ID = "' . $ORD_ID . '"')
                ->select();
            $data['GOODS_LIST'] = json_encode($guds);
            if (M('ms_ord_package', 'tb_')->data($data)->add()) {

            } else {

            }
        } else {
            if (M('ms_ord_package', 'tb_')->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
            } else {

            }
        }

        $m = M('ms_ord_guds_opt', 'tb_');
        $cost_data = $m
            ->field('tb_wms_power.weight*tb_ms_ord_guds_opt.ORD_GUDS_QTY as cost')
            ->join("left join tb_wms_power on tb_wms_power.SKU_ID = GUDS_OPT_ID")
            ->where('tb_ms_ord_guds_opt.ORD_ID = "' . $ORD_ID . '"')
            ->select();
        //批量修改sku商品成本数据
        foreach ($cost_data as $key => $value) {
            $sumcost += $value['cost'];   //总成本
            $value['cost'] = $value;
            $res = $m->where('ORD_ID = "' . $ORD_ID . '"')->save($value);   //把每个商品的成本数据添加到sku表里
        }
        $data['cost'] = $sumcost;  //获取总成本
        $res = M("ms_ord", "tb_")->where('ORD_ID = "' . $ORD_ID . '"')->save($data);

        $content = '物流信息修改成功：快递公司：' . L($data['EXPE_COMPANY']) . ',运单号：' . $data['TRACKING_NUMBER'];
        $log = A('Log');
        $log->index($ORD_ID, '', $content);

//        if ($type) {
//            //编辑并发货
//            if (M('ms_ord', 'tb_')->data(array('ORD_STAT_CD' => 'N000550500'))->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
//                $info['status'] = '待收货';
//            } else {
//
//            }
//        }
        $this->ajaxReturn(0, $info, 1);

    }

    //自营保存物流信息
    public function saveord_self()
    {
        $ORD_ID = I('post.ORD_ID');
        if (empty($ORD_ID) || !isset($ORD_ID)) {
            $info['message'] = '无订单编号';
            $this->ajaxReturn(0, $info, 1);
            exit;
        }
        $data['SHIPPING_TRACKING_CODE'] = I('post.TRACKING_NUMBER');
        $expe = I('post.EXPE_COMPANY');
        $data['SHIPPING_DELIVERY_COMPANY_CD'] = $expe;
        $expe_company = M('ms_logistics_relation', 'tb_')->field('third_logistics_cd')->where('b5c_logistics_cd = "' . $expe . '"')->find();
        //$data['SHIPPING_DELIVERY_COMPANY'] = L(I('post.EXPE_COMPANY'));
        $data['SHIPPING_DELIVERY_COMPANY'] = $expe_company['third_logistics_cd'];
        $info['message'] = L('物流信息保存成功');
        $order = M('op_order', 'tb_')->where('ORDER_ID = "' . $ORD_ID . '"')->find();
        if ($order) {
            if ($order['BWC_ORDER_STATUS'] == 'N000550500') {
                $data ['BWC_ORDER_STATUS'] = 'N000550400';
                if ($order['B5C_ORDER_NO']) {
                    M('ms_ord', 'tb_')->where(['ORD_ID' => $order['B5C_ORDER_NO']])->save(['ORD_STAT_CD' => 'N000550400']);
                }
            }
        }
        if ($order['B5C_ORDER_NO']) {
            $datap['EXPE_CODE'] = I('post.EXPE_COMPANY');
            $datap['EXPE_COMPANY'] = $expe_company['third_logistics_cd'];
            $datap['TRACKING_NUMBER'] = I('post.TRACKING_NUMBER');
            $datap['ORD_ID'] = $order['B5C_ORDER_NO'];
            $datap['SYS_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $datap['SYS_CHG_DTTM'] = date('Y-m-d H:i:s', time());
            $datap['updated_time'] = date('Y-m-d H:i:s', time());
            if (M('ms_ord_package', 'tb_')->where('ORD_ID = "' . $order['B5C_ORDER_NO'] . '"')->find()) {
                unset($datap['ORD_ID']);
                M('ms_ord_package', 'tb_')->data($datap)->where('ORD_ID = "' . $order['B5C_ORDER_NO'] . '"')->save();
            } else {
                M('ms_ord_package', 'tb_')->data($datap)->add();
            }

            $m = M('op_order', 'tb_');
            if ($m->data($data)->where('ORDER_ID = "' . $ORD_ID . '"')->save()) {
                $content = '物流信息修改成功：快递公司：' . $data['SHIPPING_DELIVERY_COMPANY'] . ',运单号：' . $data['SHIPPING_TRACKING_CODE'];
                $log = A('Log');
                $state = $data ['BWC_ORDER_STATUS'] ? $data ['BWC_ORDER_STATUS'] : 'N000550400';
                $log->index($ORD_ID, $state, $content);
                $info['TRACKING_NUMBER'] = $data['SHIPPING_TRACKING_CODE'];
                $info['EXPE_COMPANY_VAL'] = L($data['SHIPPING_DELIVERY_COMPANY']);
            }
        }

        $this->ajaxReturn(0, $info, 1);
    }

    public function bulksetexpe_self()
    {
        $orders = I('post.ords');
        $data['SHIPPING_DELIVERY_COMPANY'] = L(I('post.expe'));
        if (M('op_order', 'tb_')->data($data)->where('ORDER_ID in (' . $orders . ') ')->save()) {
            $ORDLIST = explode(',', $orders);
            foreach ($ORDLIST as $val) {
                $ORD_ID = trim($val, "'");
                $content = '批量修改物流公司成功,物流公司:' . $data['SHIPPING_DELIVERY_COMPANY'];
                $log = A('Log');
                $log->index($ORD_ID, 'N000550400', $content);
            }
        }
        $this->ajaxReturn(0, L('修改成功'), 1);
    }

    public function setpaynumber_dm()
    {
        $this->bulksetpaynumber();
    }

    public function setpaynumber_xh()
    {
        $this->setpaynumber();
    }

    public function setpaynumber_qg()
    {
        $this->bulksetpaynumber();
    }

    public function bulksetpaynumber()
    {
        $orders = I('post.ords');
        $ord_type = I('post.ord_type');
        $data['PAY_SER_NM'] = I('post.pay_ser_nm');
        if ($ord_type == 'qg') {
            $model = M('ms_ord_spl', 'tb_');
            $data['OPTION_MODE_CD'] = 'N000550400';
        } else {
            $model = M('ms_ord', 'tb_');
            $data['ORD_STAT_CD'] = 'N000550400';
        }
        $data['PAY_WAY'] = 'N001000300';
        $data['PAY_DTTM'] = date('Y-m-d H:i:s', time());
        $orders_arr = explode(',', $orders);
        foreach ($orders_arr as $order) {
            if ($model->data($data)->where('ORD_ID = ' . $order)->save()) {
                $log = A('Log');
                $log->index(trim($order, "'"), 'N000550400', '线下支付成功，流水号:' . $data['PAY_SER_NM']);
            }
        }

        $this->ajaxReturn(0, L('修改成功'), 1);
    }

    public function saveprice_bulk()
    {
        $this->saveprice();
    }

    public function saveprice_dm()
    {
        $this->saveprice();
    }

    public function saveprice_bw()
    {
        $this->saveprice();
    }

    public function saveprice_xh()
    {
        $this->saveprice();
    }

    public function saveprice_qg()
    {
        //求购关联表不同，单独做修改
        $data['DISCOUNT_MN'] = (float)I('post.DISCOUNT_MN');
        $data['DLV_AMT'] = (float)I('post.DLV_AMT');
        $data['TARIFF'] = (float)I('post.TARIFF', 0);
        $ORD_ID = I('post.ORD_ID');
        $type = I('post.type');
        $info = array();
//        $model = M('ms_estm','sms_');
        $model = M('ms_ord_spl', 'tb_');
//        $oldprice = $model->where('ORD_NO = "'.$ORD_ID.'" AND DISCOUNT_MN = '.$data['DISCOUNT_MN'].' AND DLV_AMT = '.$data['DLV_AMT'])->find();
        $oldprice = $model->where('ORD_ID = "' . $ORD_ID . '" and DISCOUNT_MN = ' . $data['DISCOUNT_MN'] . ' AND DLV_AMT = ' . $data['DLV_AMT'] . ' AND TARIFF=' . $data['TARIFF'])->find();
        if ($oldprice) {
            $info['message'] = L('修改成功');
            $info['DISCOUNT_MN'] = $data['DISCOUNT_MN'];
            $info['DLV_AMT'] = $data['DLV_AMT'];
            $info['TARIFF'] = $data['TARIFF'];
        } else {
//            $old = $model->where('ORD_NO = "'.$ORD_ID.'"')->field('DISCOUNT_MN,DLV_AMT')->find();
            $old = $model->where('ORD_ID = "' . $ORD_ID . '"')->field('DISCOUNT_MN,DLV_AMT,TARIFF')->find();
//            if($model->data($data)->where('ORD_NO = "'.$ORD_ID.'"')->save()){
            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                $info['message'] = L('修改成功');
                $info['DISCOUNT_MN'] = $data['DISCOUNT_MN'];
                $info['DLV_AMT'] = $data['DLV_AMT'];
                $info['TARIFF'] = $data['TARIFF'];
                $olddiscount = $old['DISCOUNT_MN'] != '' ? $old['DISCOUNT_MN'] : 0;
                $olddlv = $old['DLV_AMT'] != '' ? $old['DLV_AMT'] : 0;
                $oldtax = $old['TARIFF'] != '' ? $old['TARIFF'] : 0;
                $arr = [];
                if ($olddiscount != $data['DISCOUNT_MN']) {
                    $arr[] = '优惠修改成功,优惠:' . $olddiscount . '->' . $data['DISCOUNT_MN'];
                }
                if ($olddlv != $data['DLV_AMT']) {
                    $arr[] = '运费修改成功,运费:' . $olddlv . '->' . $data['DLV_AMT'];
                }
                if ($oldtax != $data['TARIFF']) {
                    $arr[] = '税费修改成功,税费:' . $oldtax . '->' . $data['TARIFF'];
                }
                $content = implode(" ", $arr);
                $log = A('Log');
                $ORD_STAT_CD = M('ms_ord_spl', 'tb_')->where('ORD_ID = "' . $ORD_ID . '"')->field('OPTION_MODE_CD')->find();
                $log->index($ORD_ID, $ORD_STAT_CD['OPTION_MODE_CD'], $content);

            } else {

            }
        }
        $this->ajaxReturn(0, $info, 1);
    }

    //保存优惠运费
    public function saveprice($ord_type = '')
    {
        $data['DISCOUNT_MN'] = I('post.DISCOUNT_MN');
        $data['DLV_AMT'] = I('post.DLV_AMT');
        $ORD_ID = I('post.ORD_ID');
        $type = I('post.type');
        $info = array();
        $oldprice = M('ms_ord', 'tb_')->where('ORD_ID = "' . $ORD_ID . '" AND DISCOUNT_MN = ' . $data['DISCOUNT_MN'] . ' AND DLV_AMT = ' . $data['DLV_AMT'])->find();
        if ($oldprice) {
            $info['message'] = L('修改优惠、运费成功');
            $info['DISCOUNT_MN'] = $data['DISCOUNT_MN'];
            $info['DLV_AMT'] = $data['DLV_AMT'];
        } else {
            $old = M('ms_ord', 'tb_')->where('ORD_ID = "' . $ORD_ID . '"')->field('DISCOUNT_MN,DLV_AMT,ORD_STAT_CD')->find();
            if (M('ms_ord', 'tb_')->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                $info['message'] = L('修改优惠、运费成功');
                $info['DISCOUNT_MN'] = $data['DISCOUNT_MN'];
                $info['DLV_AMT'] = $data['DLV_AMT'];
                $olddiscount = $old['DISCOUNT_MN'] != '' ? $old['DISCOUNT_MN'] : 0;
                $olddlv = $old['DLV_AMT'] != '' ? $old['DLV_AMT'] : 0;
                if ($old['DISCOUNT_MN'] != $data['DISCOUNT_MN']) {
                    $content = '订单：' . $ORD_ID . '修改优惠运费成功，优惠：' . $olddiscount . '->' . $data['DISCOUNT_MN'];
                }
                if ($old['DLV_AMT'] != $data['DLV_AMT']) {
                    if ($old['DISCOUNT_MN'] == $data['DISCOUNT_MN']) {
                        $content = '订单：' . $ORD_ID . '修改优惠运费成功，运费：' . $olddlv . '->' . $data['DLV_AMT'];
                    } else {
                        $content .= '运费：' . $olddlv . '->' . $data['DLV_AMT'];
                    }
                }
                $log = A('Log');
                $log->index($ORD_ID, $old['ORD_STAT_CD'], $content);

            } else {

            }
        }

        if ($type) {
            if (M('ms_ord', 'tb_')->data(array('ORD_STAT_CD' => 'N000550300'))->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                $info['status'] = '待付款';
            } else {

            }
        }
        $this->ajaxReturn(0, $info, 1);
    }

    //发货、po确认按钮操作   发货方法
    public function confirm()
    {
        $ORD_ID = I('post.ORD_ID');
        $type = I('post.type');
        $ord_type = I('post.ord_type');
        $plat_id = I('post.plat_id');
        $info = array();
        if ($type == 1) {
            if ($ord_type == 1) {
                $this->sendout_bulk($ORD_ID, 1);
            } elseif ($ord_type == 2) {
                $this->sendout_dm($ORD_ID, 1);
            } elseif ($ord_type == 3) {
                $this->sendout_bw($ORD_ID, 1);
            } elseif ($ord_type == 4) {
                $this->sendout_xh($ORD_ID, 1);
            } elseif ($ord_type == 'qg') {
                $this->sendout_qg($ORD_ID, 1);
            } elseif ($ord_type == 'self') {
//            check order oversold >.< ohh
                if ($this->is_check_oversold($ORD_ID)) {
                    $info['message'] = '订单内商品超卖，发货不成功';
                    $this->ajaxReturn(0, $info, 0);
                }
                $this->sendout_self($ORD_ID, 1, $plat_id);
            } elseif ($ord_type == 5) {   //新自营订单
                $this->sendout_new_self($ORD_ID, 1);
            }
        } elseif ($type == 2) {
            if ($ord_type == 1) {
                $this->poconfirm_bulk($ORD_ID, 1);
            } elseif ($ord_type == 2) {
                $this->poconfirm_dm($ORD_ID, 1);
            } elseif ($ord_type == 3) {
                $this->poconfirm_bw($ORD_ID, 1);
            } elseif ($ord_type == 4) {
                $this->poconfirm_xh($ORD_ID, 1);
            } elseif ($ord_type == 'qg') {
                $this->poconfirm_qg($ORD_ID, 1);
            }
        }
    }

    /**
     * @param $thr_order_id
     *
     * @return mixed
     */
    private function order_thr_to_erp($thr_order_id)
    {
        $ORD_ID = $thr_order_id;
        $Model = M();
        $ORD_ID_t = $Model->table('tb_ms_ord')->where('THIRD_ORDER_ID = \'' . $thr_order_id . '\'')->getField('ORD_ID');
        if ($ORD_ID_t) {
            $ORD_ID = $ORD_ID_t;
        }
        return $ORD_ID;
    }

    /**
     * 效验订单超卖处理
     *
     * @param  $order_id
     *
     * @return bool
     */
    private function is_check_oversold($order_id)
    {
        $Batch_order = M('batch_order', 'tb_wms_');
//        $batch_order = $Batch_order->field('id, ORD_ID, use_type,occupy_num')->where('ORD_ID = \'' . $this->order_thr_to_erp($order_id) . '\' AND ( (use_type = 0 AND oversole_num > 0) OR use_type IN (2, 3) )')->select();
        $batch_thr_order = $Batch_order->field('id, ORD_ID, use_type,oversole_num   ')->where('ORD_ID = \'' . $this->order_thr_to_erp($order_id) . '\' AND ( (use_type = 0 AND oversole_num > 0) )')->select();
        $batch_order = $Batch_order->field('id, ORD_ID, use_type,oversole_num   ')->where('ORD_ID = \'' . $order_id . '\' AND ( (use_type = 0 AND oversole_num > 0) )')->select();
        if (count($batch_thr_order) || count($batch_order)) return true;
        return false;
    }

    //大宗发货
    public function sendout_bulk($ORD_ID, $type = '')
    {
        $this->sendout($ORD_ID, $type);
    }

    //直邮发货
    public function sendout_dm($ORD_ID, $type = '')
    {
        $this->sendout($ORD_ID, $type);
    }

    //保税发货
    public function sendout_bw($ORD_ID, $type = '')
    {
        $this->sendout($ORD_ID, $type);
    }

    public function sendout_xh($ORD_ID, $type = '')
    {
        $this->sendout($ORD_ID, $type);
    }

    public function sendout_qg($ORD_ID, $type = '')
    {
        $this->sendout($ORD_ID, $type, 1);
    }

    public function sendout_new_self($ORD_ID, $type = '')
    {
        G('begin');
        $today_date = date('Ymd');
        $readyIn = true;
        if ($qg == 1) {
            $readyIn = false;
            $data['OPTION_MODE_CD'] = 'N000550500';
            $model = M('ms_ord_spl', 'tb_');
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $data['ORD_STAT_CD'] = 'N000550500';
            $model = M('ms_ord', 'tb_');
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }
        $ret = $model->field(
            'tb_ms_ord.ORD_ID, 
                            tb_ms_ord.THIRD_ORDER_ID,
                            tb_ms_ord_package.EXPE_COMPANY,
                            tb_ms_ord.TRACKING_NUMBER,
                            tb_ms_ord_package.EXPE_CODE,
                            tb_ms_ord.PLAT_FORM,
                            tb_ms_ord.SYS_REG_DTTM'
        )->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"')->find();

        $guys_data = M('ms_ord_guds_opt', 'tb_')->join('left join tb_ms_ord on tb_ms_ord_guds_opt.ORD_ID = tb_ms_ord.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"');
        trace(G('begin', 'end1', 6), $today_date . '-' . $ORD_ID . '-dataLoading');
        if ($type == 1) {

            $data['ORD_STAT_CD'] = 'N000550500';
            $info['message'] = L('设置发货成功');
            $info['status'] = '待收货';
            // 口库存
            $urlstock = SMS2_URL . 'index.php?m=stock&a=deliver_warehouse';  //保税仓出库
            if (SMS2_URL == 'SMS2_URL') $urlstock = 'http://erp.gshopper.stage.com/index.php?m=stock&a=deliver_warehouse';
            $data_stock = [];
            $data_stock['userId'] = $_SESSION['userId'];
            $data_stock['b5c_id'] = $ORD_ID;
            $res = curl_request($urlstock, $data_stock);   //仓库
            $res = json_decode($res, 1);            //返回结果
            trace($res, '$res_w');
            trace(G('begin', 'end2', 6), $today_date . '-' . $ORD_ID . '-outSendERP');
            if ($res ['code'] == 200) {   //接口返回数据正常
                if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {   //
                    // 修改发货时间
                    $pg = M('_ms_ord_package', 'tb_');
                    $d['SUBSCRIBE_TIME'] = date('Y-m-d H:i:s');
                    $pg->data($d)->where('ORD_ID = "' . $ORD_ID . '"')->save();
                    // 入q队列
                    $ret = $model->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"')->find();
                    trace($readyIn, '$readyIn');
                    trace($ret, '$ret');
                    trace(G('begin', 'end3', 6), $today_date . '-' . $ORD_ID . '-actionMQ');
                    if ($ret && $readyIn) {
                        $rbmq = new RabbitMqModel();
                        $rbmq->exchangeName = 'gshopperExchange';
                        $rbmq->routeKey = 'Q-B5C2GS-02-RK-01';
                        $rbmq->queueName = 'Q-B5C2GS-02';
                        $sys_reg_dttm = strtotime($ret['SYS_REG_DTTM']);
                        //date_default_timezone_set('UTC');
                        $sys_reg_dttm = date('Y-m-d\TH:i:s\Z', $sys_reg_dttm);

                        $msg = [
                            "platCode" => $ret ['PLAT_FORM'],
                            "processId" => create_guid(),
                            "data" => [
                                "orders" => [[
                                    "thirdOrdId" => $ret ['THIRD_ORDER_ID'],
                                    "ordId" => $ret ['ORD_ID'],
                                    "ordStatCd" => "N000550500",
                                    "msg" => "发货",
                                    "expeCompany" => $ret ['EXPE_COMPANY'],
                                    "trackingNumber" => $ret ['TRACKING_NUMBER'],
                                    "expeCode" => $ret ['EXPE_CODE'],
                                    "expeDate" => $sys_reg_dttm,
                                ]]
                            ]
                        ];
                        $rbmq->setData($msg);  //把状态存入rabbitmq队列
                        $rbmq_status = $rbmq->submit();
                        trace($rbmq_status, '$rbmq_status');
                        trace($msg, '$msg');
                    }
                    trace(G('begin', 'end4', 6), $today_date . '-' . $ORD_ID . '-endMQ');
                    $log = A('Log');  //打印日志
                    $log->index($ORD_ID, $ORD_STAT_CD, $info['message']);
                    trace(G('begin', 'end5', 6), $today_date . '-' . $ORD_ID . '-addLog');
                    $this->ajaxReturn(0, $info, 1);
                } else {
                    $info['message'] = L('设置发货失败' . $model->error());
                    $info['status'] = '待发货';
                }
            } else {
                if (!$res) {
                    $info ['message'] = '接口返回异常';
                } else {
                    $info ['message'] = $res['info'];
                }
                $info['status'] = '待发货';
                trace(G('begin', 'end6', 6), $today_date . '-' . $ORD_ID . '-error');
            }
            $this->ajaxReturn(0, $info, 0);
        } else {

        }
        trace(G('begin', 'end7', 6), $today_date . '-' . $ORD_ID . '-end');
    }

    public function sendout($ORD_ID, $type = '', $qg)
    {
        $readyIn = true;
        if ($qg == 1) {
            $readyIn = false;
            $data['OPTION_MODE_CD'] = 'N000550500';
            $model = M('ms_ord_spl', 'tb_');
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $data['ORD_STAT_CD'] = 'N000550500';
            $model = M('ms_ord', 'tb_');
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }
        $ret = $model->field('tb_ms_ord.ORD_ID, tb_ms_ord.THIRD_ORDER_ID, tb_ms_ord_package.EXPE_COMPANY, tb_ms_ord.TRACKING_NUMBER, tb_ms_ord_package.EXPE_CODE, tb_ms_ord.PLAT_FORM, tb_ms_ord.SYS_REG_DTTM')->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"')->find();

        if ($type == 1) {
            $data['ORD_STAT_CD'] = 'N000550500';
            $info['message'] = L('设置发货成功');
            $info['status'] = '待收货';

            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {

                $ret = $model->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"')->find();
                if ($ret && $readyIn) {
                    $rbmq = new RabbitMqModel();
                    $rbmq->exchangeName = 'gshopperExchange';
                    $rbmq->routeKey = 'statusOrder';
                    $rbmq->queueName = 'Q-B5C2GS-02';
                    $sys_reg_dttm = strtotime($ret['SYS_REG_DTTM']);
                    date_default_timezone_set('UTC');
                    $sys_reg_dttm = date('Y-m-d\TH:i:s\Z', $sys_reg_dttm);

                    $msg = [
                        "platCode" => $ret ['PLAT_FORM'],
                        "processId" => create_guid(),
                        "data" => [
                            "orders" => [[
                                "thirdOrdId" => $ret ['THIRD_ORDER_ID'],
                                "ordId" => $ret ['ORD_ID'],
                                "ordStatCd" => "N000550500",
                                "msg" => "发货",
                                "expeCompany" => $ret ['EXPE_COMPANY'],
                                "trackingNumber" => $ret ['TRACKING_NUMBER'],
                                "expeCode" => $ret ['EXPE_CODE'],
                                "expeDate" => $sys_reg_dttm,
                            ]]
                        ]
                    ];
                    $rbmq->setData($msg);
                    $rbmq->submit();
                }

                $log = A('Log');
                $log->index($ORD_ID, $ORD_STAT_CD, $info['message']);
            } else {
                $info['message'] = L('发货失败' . $model->error());
                $info['status'] = '待发货';
            }
            $this->ajaxReturn(0, $info, 1);
        } else {

        }

    }

    //自营发货
    public function sendout_self($ORD_ID, $type = '', $plat_id)
    {
        $plat_from = array('N000830600', 'N000831200', 'N000832800', 'N000830700',
            'N000830800', 'N000830900', 'N000831000', 'N000831100', 'N000832900',
            'N000832300', 'N000830300', 'N000830400', 'N000830500');
        $ali_shop = ['N000832000', 'N000831900'];
        if (in_array($plat_id, $plat_from)) {
            $third_result['data']['orders'][0]['stat'] = true;
            if (1 != 1 || in_array($plat_id, $ali_shop)) {
                $third_data['data']['orders']['0']['orderId'] = $ORD_ID;
                $third_result = $this->Curl_post(THIRD_SHIP_API, json_encode($third_data));
                $third_result = json_decode($third_result, true);
                trace($third_result, '$third_result');
            }
            if (isset($third_result['data']['orders'][0]['stat']) && $third_result['data']['orders'][0]['stat'] == true) {
                $ord_m = new TbOpOrdModel();
                $res = $ord_m->sendOut($ORD_ID, $plat_id);   //扣除库存和占用
                if ($res == true) {
                    $info['message'] = L('发货成功');
                    $info['status'] = '待收货';
                    $this->ajaxReturn(0, $info, 1);
                    //发货成功以后保存成本信息
                } else {
                    trace($ord_m->getError(), '$ord_m->getError()');
                    $this->ajaxReturn(0, $ord_m->getError(), 2);
                }
            } else {
                if (isset($third_result['data']['orders'][0]['order_msg'])) {
                    $info['message'] = '第三方接口:' . $third_result['data']['orders'][0]['order_msg'];
                } elseif (isset($third_result['data']['orders'][0]['orderMsg'])) {
                    $info['message'] = '第三方接口:' . $third_result['data']['orders'][0]['orderMsg'];
                } elseif (isset($third_result['msg'])) {
                    $info['message'] = '第三方接口:' . $third_result['msg'];
                } else {
                    $info['message'] = L('第三方发货接口调取失败');
                }
                trace($info, '$info');
                $info['message'] = L('第三方平台发货状态更新失败');
                $this->ajaxReturn(0, $info, 0);
            }

        } else {
            $ord_m = new TbOpOrdModel();
            $res = $ord_m->sendOut($ORD_ID, $plat_id);   //扣除库存和占用
            if ($res == true) {
                $info['message'] = L('发货成功');
                $info['status'] = '待收货';
                $this->ajaxReturn(0, $info, 1);
                //发货成功以后保存成本信息
            } else {
                trace($ord_m->getError(), '$ord_m->getError()');
                $this->ajaxReturn(0, $ord_m->getError(), 2);
            }
        }
    }

    public function receive($ORD_ID, $type)
    {
        if ($type == 'self') {
            $model = M('op_order', 'tb_');
            $data['BWC_ORDER_STATUS'] = 'N000550800';
            $ORD_STAT_CD = $data['ORDER_STATUS'];
        } elseif ($type == 'qg') {
            $model = M('ms_ord_spl', 'tb_');
            $data['OPTION_MODE_CD'] = 'N000550800';
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $model = M('ms_ord', 'tb_');
            $data['ORD_STAT_CD'] = 'N000550800';
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }

        $data['ORD_STAT_CD'] = 'N000550600';
        $info['message'] = L('收货成功');
        $info['status'] = 2;
        if ($type == 'self') {
            $res = $model->data($data)->where('ORDER_ID = "' . $ORD_ID . '"')->save();
        } else {
            $res = $model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save();
        }
        trace($res, '$res');
        if ($res) {
            $log = A('Log');
            $log->index($ORD_ID, $ORD_STAT_CD, $info['message']);
//          处理推送GAPP
            $order_channel = $this->get_order_channel($ORD_ID);
            $GAPP_channels = $this->get_gapp_channels('Gshopper');
            trace($order_channel, '$order_channel');
            trace($GAPP_channels, '$GAPP_channels');
            if (in_array($order_channel, $GAPP_channels)) {
                $info = $this->sync_gapp_state($ORD_ID);
                trace($info, '$info');
            }
        } else {
            $info['message'] = L('收货失败' . $model->error());
            $info['status'] = 1;
        }
        $this->ajaxReturn($info['status']);
    }

    public function bulkreceive()
    {
        $ords = I('request.ords');
        $type = I('post.ord_type');
        $ORDLIST = explode(',', $ords);

        if ($type == 'self') {
            $model = M('op_order', 'tb_');
            $data['BWC_ORDER_STATUS'] = 'N000550800';
            $ORD_STAT_CD = $data['ORDER_STATUS'];
        } elseif ($type == 'qg') {
            $model = M('ms_ord_spl', 'tb_');
            $data['OPTION_MODE_CD'] = 'N000550800';
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $model = M('ms_ord', 'tb_');
            $data['ORD_STAT_CD'] = 'N000550800';
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }
        $message_orders = array();
        $message = [];
        if ($type == 'self') {
            foreach ($ORDLIST as $val) {
                $ord_id = trim($val, "'");
                $res = $model->data($data)->where('ORDER_ID = "' . $ord_id . '"')->save();
            }
        } else {
            foreach ($ORDLIST as $val) {
                $ord_id = trim($val, "'");
                $res = $model->data($data)->where('ORD_ID = "' . $ord_id . '"')->save();
            }
        }

        $orderlist_num = count($ORDLIST); //总订单数
        $orderlist_false = count($message_orders); //失败订单数量
        $orderlist_success = $orderlist_num - $orderlist_false; //成功订单的数量

        $this->assign('message_orders', $message_orders);
        $this->assign('orderlist_num', $orderlist_num);
        $this->assign('orderlist_false', $orderlist_false);
        $this->assign('orderlist_success', $orderlist_success);
        $this->display('receive_status');

    }

    public function poconfirm_bulk($ORD_ID, $type = '')
    {
        $this->poconfirm($ORD_ID, $type);
    }

    public function poconfirm_dm($ORD_ID, $type = '')
    {
        $this->poconfirm($ORD_ID, $type);
    }

    public function poconfirm_bw($ORD_ID, $type = '')
    {
        $this->poconfirm($ORD_ID, $type);
    }

    public function poconfirm_xh($ORD_ID, $type = '')
    {
        $this->poconfirm($ORD_ID, $type);
    }

    public function poconfirm_qg($ORD_ID, $type = '')
    {
        $this->poconfirm($ORD_ID, $type, 1);
    }

    public function poconfirm($ORD_ID, $type = '', $qg)
    {
        if ($qg == 1) {
            $data['OPTION_MODE_CD'] = 'N000550300';
            $model = M('ms_ord_spl', 'tb_');
            $ORD_STAT_CD = $data['OPTION_MODE_CD'];
        } else {
            $data['ORD_STAT_CD'] = 'N000550300';
            $model = M('ms_ord', 'tb_');
            $ORD_STAT_CD = $data['ORD_STAT_CD'];
        }
        if ($type == 1) {
            $data['ORD_STAT_CD'] = 'N000550300';
            $info['message'] = L('PO确认成功');
            $info['status'] = '待付款';
            if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                $log = A('Log');
                $log->index($ORD_ID, $ORD_STAT_CD, $info['message']);
                $this->ajaxReturn(0, $info, 1);
            }
        } else {

        }

    }

    public function createorder()
    {
        $this->display();
    }

    public function createorderdm()
    {
        $this->assign('type', 2);
        $this->display('createorder');
    }

    public function createorderbw()
    {
        $this->assign('type', 3);
        $this->display('createorder');
    }

    public function createorderxh()
    {
        $this->assign('type', 4);
        $this->display('createorder');
    }


    public function createorderqg()
    {
        $this->display('createorderqg');
    }

    public function uploadpic()
    {
//        echo json_encode(array('success'=>true,'data'=>'http://7xli3x.media1.z0.glb.clouddn.com/b5c-web-1583bbbb0c5d4d'));die;
//        die;
        if (empty ($_FILES ['pic'])) {
            echo json_encode(array(
                'success' => false,
                'message' => '请选择要上传的图片'
            ));
            die ();

        }
        if ($_FILES ['pic'] ['size'] > 5 * 1024 * 1024) {
            echo json_encode(array(
                'success' => false,
                'message' => '只能上传5M以下的图片'
            ));
            die ();
        }
        $length = strlen($_FILES ['pic'] ['name']);
        $len = strrpos($_FILES ['pic'] ['name'], '.');
        $extenth = substr($_FILES ['pic'] ['name'], ($len + 1) - $length);
        if (!in_array(strtolower($extenth), array('jpg', 'png', 'jpeg'))) {
            echo json_encode(array(
                'success' => false,
                'message' => '图片格式错误，只支持jpg,png,jpeg'
            ));
            die ();
        }
        $ak = '7mGm2AwdRtgCnJ3LMgFBWjjwRQH9CuGR0f6m0WIE';
        $sk = 'hM5KeiDW0RAL-jWlm3jB70DwkuZawFipUlR8B2gf';
        $domain = 'portal.qiniu.com';
        try {
            import('ORG.Net.Qiniu');
            $qiniu = new Qiniu($ak, $sk, $domain, 'korea-space');
            $key = 'b5c-web-' . uniqid(true);
            $result = $qiniu->uploadFile($_FILES['pic']['tmp_name'], $key);
            $img = $this->qiniuHost . $result['key'];

        } catch (HttpException $e) {
            echo json_encode(array('success' => false, 'message' => '服务器错误,请稍后尝试'));
            die;
        }
        echo json_encode(array('success' => true, 'data' => $img));
        die;
    }


    //全部订单详情页
    function orderdetail($ord_type = '')
    {
        if ($ord_type == 1) {
            $sendout = 'Orders/sendout_bulk';
            $poconfirm = 'Orders/poconfirm_bulk';
            $saveord = 'Orders/saveord_bulk';
            $saveprice = 'Orders/saveprice_bulk';
        } elseif ($ord_type == 2) {
            $sendout = 'Orders/sendout_dm';
            $poconfirm = 'Orders/poconfirm_dm';
            $saveord = 'Orders/saveord_dm';
            $saveprice = 'Orders/saveprice_dm';
        } elseif ($ord_type == 3) {
            $sendout = 'Orders/sendout_bw';
            $poconfirm = 'Orders/poconfirm_bw';
            $saveord = 'Orders/saveord_bw';
            $saveprice = 'Orders/saveprice_bw';
        } elseif ($ord_type == 4) {
            $sendout = 'Orders/sendout_xh';
            $poconfirm = 'Orders/poconfirm_xh';
            $saveord = 'Orders/saveord_xh';
            $saveprice = 'Orders/saveprice_xh';
        }
        $agree_refund = 'Orders/agree_refund';        // 同意退款
        $refuse_refund = 'Orders/refuse_refund';       // 拒绝退款
        $refund_success = 'Orders/refund_success';    // 退款成功

        $is_agree_refund = isset($this->access[$agree_refund]) ? 1 : 0;
        $is_refuse_refund = isset($this->access[$refuse_refund]) ? 1 : 0;
        $is_refund_success = isset($this->access[$refund_success]) ? 1 : 0;

        $this->assign('is_agree_refund', $is_agree_refund);
        $this->assign('is_refuse_refund', $is_refuse_refund);
        $this->assign('is_refund_success', $is_refund_success);

        $is_sendout = isset($this->access[$sendout]) ? 1 : 0;
        $is_poconfirm = isset($this->access[$poconfirm]) ? 1 : 0;
        $is_saveord = isset($this->access[$saveord]) ? 1 : 0;
        $is_saveprice = isset($this->access[$saveprice]) ? 1 : 0;
        $this->assign('is_sendout', $is_sendout);
        $this->assign('is_poconfirm', $is_poconfirm);
        $this->assign('is_saveord', $is_saveord);
        $this->assign('is_saveprice', $is_saveprice);

        if (empty($_GET['ordId'])) {
            redirect(U('Public/error'), 2, '无订单号');
            return false;
        }
        //print_r($_GET);exit();
        $orderWhere['tb_ms_ord.ORD_ID'] = I('get.ordId');
        //订单基本信息
        $order = M('ms_ord', 'tb_');
        $orderField = 'tb_ms_ord.REMARK_MSG,
                        tb_ms_ord.POST_CD,
                        tb_ms_ord.ORD_ID,
                        tb_ms_ord.THIRD_ORDER_ID, 
                        tb_ms_ord.ORD_STAT_CD,
                        tb_ms_ord.ORD_TYPE_CD,
                        bbm_pay_order.cashier_version,
                        bbm_pay_order.order_currency,
                        tb_ms_ord.PAY_AMOUNT,
                        tb_ms_ord.DISCOUNT_MN, 
                        tb_ms_ord.DLV_AMT,
                        tb_ms_ord.TARIFF,
                        tb_ms_ord.PAY_AMOUNT, 
                        bbm_pay_order.payer_name,
                        bbm_pay_callback.channel,
                        tb_ms_ord.PAY_ID,
                        tb_ms_ord.PAY_SER_NM, 
                        tb_ms_ord.PAY_WAY,
                        tb_ms_ord.CUST_ID, 
                        tb_ms_ord.ORD_CUST_NM, 
                        tb_ms_ord.ORD_CUST_NM,
                        tb_ms_ord.ORD_CUST_CP_NO,
                        tb_ms_ord.ADPR_ADDR,
                        tb_ms_ord.SENDER_INFO, 
                        tb_ms_ord.REQ_CONT, 
                        tb_ms_ord.REC_ID_CARD,
                        tb_ms_ord.DLV_MODE_CD,
                        tb_ms_ord.BUYER_NM,
                        tb_ms_ord.DELIVERY_WAREHOUSE,
                        tb_ms_cust.CUST_NICK_NM,
                        tb_ms_ord.REFUND_STAT_CD,
                        tb_ms_ord.OA_NUM,
                        tb_ms_ord.currency_cd,
                        tb_op_order.ADDRESS_USER_COUNTRY,
                        tb_op_order.PAY_VOUCHER_AMOUNT,
                        tb_op_order.PAY_SHIPING_PRICE,
                        tb_op_order.PAY_CURRENCY,
                        tb_ms_thrd_cust.CUST_CP_NO as third_CUST_ID,
                        tb_ms_thrd_cust.CUST_NICK_NM as third_CUST_NM';

        $detail = $order->field($orderField)->join('left join bbm_pay_order on tb_ms_ord.ORD_ID=bbm_pay_order.order_id')->
        join('left join bbm_pay_callback  on bbm_pay_callback.pay_id = bbm_pay_order.pay_id')
            ->join('left join tb_op_order on tb_op_order.B5C_ORDER_NO = tb_ms_ord.ORD_ID')
            ->join('left join tb_ms_cust on tb_ms_ord.CUST_ID=tb_ms_cust.CUST_ID')
            ->join('left join tb_ms_thrd_cust on tb_ms_ord.thrd_user_id=tb_ms_thrd_cust.cust_eml')->where($orderWhere)->find();

//         $country_crm = $detail['ADDRESS_USER_COUNTRY'];
//        $countData = M('crm_site','tb_')->where("PARENT_ID=0  AND RES_NAME= '$country_crm'")->find();
//
//        $detail['ADDRESS_USER_COUNTRY'] = $countData['NAME'];

        $type = 0;
        if ($detail['ORD_STAT_CD'] == 'N000550400﻿') {
            $type = 1;
        } else {
            $type = 2;
        }
        $this->assign('type', $type);
        $detail['ORD_STAT_CD_NAME'] = L($detail['ORD_STAT_CD']);
        $detail['ORD_TYPE_CD_NAME'] = C($detail['ORD_TYPE_CD']);
        $detail['currency'] = C($detail['currency_cd']);
        //dump($detail);
        //订单商品list
        $gud = M('ms_ord_guds_opt', 'tb_');
        $gudField = 'tb_ms_ord_guds_opt.SLLR_ID,tb_ms_ord_guds_opt.GUDS_ID,tb_ms_ord_guds_opt.GUDS_OPT_ID, tb_ms_guds.DELIVERY_WAREHOUSE,tb_ms_ord_guds_opt.THIRD_SUPPLIER,tb_ms_ord_guds_opt.THIRD_SERIAL_NUMBER,tb_ms_ord_guds_opt.THIRD_PAY_AMOUNTS, tb_ms_guds.GUDS_NM, tb_ms_ord_guds_opt.RMB_PRICE, tb_ms_ord_package.TRACKING_NUMBER,
                    tb_ms_ord_guds_opt.ORD_GUDS_QTY, tb_ms_ord_package.EXPE_COMPANY, tb_ms_guds_img.GUDS_IMG_CDN_ADDR, tb_ms_guds_opt.GUDS_OPT_VAL_MPNG';
        $gudWhere['tb_ms_ord_guds_opt.ORD_ID'] = I('get.ordId');
        $gudWhere['tb_ms_guds_img.GUDS_IMG_CD'] = 'N000080200';
        $gud_list = $gud->field($gudField)->join('left join tb_ms_guds_opt on tb_ms_ord_guds_opt.GUDS_OPT_ID=tb_ms_guds_opt.GUDS_OPT_ID')->
        join('left join tb_ms_guds on tb_ms_ord_guds_opt.GUDS_ID=tb_ms_guds.GUDS_ID')->
        join('left join tb_ms_guds_img on tb_ms_ord_guds_opt.GUDS_ID=tb_ms_guds_img.GUDS_ID')->
        join('left join tb_ms_ord_package on tb_ms_ord_guds_opt.ORD_ID=tb_ms_ord_package.ORD_ID')->where($gudWhere)->
        group('tb_ms_ord_guds_opt.GUDS_OPT_ID')->select();
        trace($gud_list, '$gud_list');
        foreach ($gud_list as $k => $v) {
            $gud_list[$k]['SKU'] = $this->getGudval($v['GUDS_OPT_VAL_MPNG']);
            $detail['gudAmount'] += $v['RMB_PRICE'] * $v['ORD_GUDS_QTY'];
            $gud_list[$k]['ORD_GUDS_QTY'] = intval($v['ORD_GUDS_QTY']);
        }

        $logWhere['ORD_NO'] = I('get.ordId');
        $ModelLog = M('ms_ord_hist', 'sms_');
        $logField = 'ORD_HIST_REG_DTTM, ORD_STAT_CD, ORD_HIST_WRTR_EML, ORD_HIST_HIST_CONT';
        $logList = $ModelLog->field($logField)->where($logWhere)->order('ORD_HIST_SEQ desc')->select();

        $ret = $logList;
        $ret = array_column($ret, 'ORD_HIST_HIST_CONT', 'ORD_STAT_CD');
        $this->assign('refund_reason', $ret);
        $array['detail'] = $detail;
        $array['gudList'] = $gud_list;
        $array['logList'] = $logList;
        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
        return $array;
    }

    function getGudval($str = '')
    {
        if (empty($str)) {
            return false;
        }
        if (strstr($str, ';') != false) {
            $temp = explode(';', $str);
            foreach ($temp as $key => $value) {
                $array[] = explode(':', $value);
            }
        } else {
            $array = explode(':', $str);
        }
        $val = M('ms_opt_val', 'tb_');
        $attr = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $valList = $val->join('left join tb_ms_opt on tb_ms_opt.OPT_ID=tb_ms_opt_val.OPT_ID')->
                where('tb_ms_opt.OPT_ID=' . $value[0] . ' and tb_ms_opt_val.OPT_VAL_ID=' . $value[1])->find();
                $valList['OPT_CNS_NM'] = $value[0] == 8000 ? '标配' : $valList['OPT_CNS_NM'];
                $valList['OPT_VAL_CNS_NM'] = $value[1] == 800000 ? '标配' : $valList['OPT_VAL_CNS_NM'];
                $attr .= $valList['OPT_CNS_NM'] . ':' . $valList['OPT_VAL_CNS_NM'] . '<br>';
            } else {
                $valList = $val->join('left join tb_ms_opt on tb_ms_opt.OPT_ID=tb_ms_opt_val.OPT_ID')->
                where('tb_ms_opt.OPT_ID=' . $array[0] . ' and tb_ms_opt_val.OPT_VAL_ID=' . $array[1])->find();
                $valList['OPT_CNS_NM'] = $array[0] == 8000 ? '标配' : $valList['OPT_CNS_NM'];
                $valList['OPT_VAL_CNS_NM'] = $array[1] == 800000 ? '标配' : $valList['OPT_VAL_CNS_NM'];
                $attr = $valList['OPT_CNS_NM'] . ':' . $valList['OPT_VAL_CNS_NM'] . '<br>';
                break;
            }
        }
        return $attr;
    }

    //大宗采购
    public function orderdetail_bulk()
    {

        //echo "<pre>";echo MODULE_NAME;print_r($_SESSION);exit();
        $array = $this->orderdetail(1);
        $Gapp = I('get.Gapp');
        if ($Gapp == 1) {
            $array['detail']['CUST_ID'] = preg_replace('/[a-zA-Z]/', '*', $array['detail']['third_CUST_ID']);
            $array['detail']['CUST_NICK_NM'] = $array['detail']['third_CUST_NM'];
        }
        $this->assign('detail', $array['detail']);
        $this->assign('gudList', $array['gudList']);
        $this->assign('logList', $array['logList']);

        $refund_state = static::refund_state();
        $this->assign('refund_state', $refund_state);
        $this->assign('type', '2');
        $this->assign('ord_type', '1');
        $this->display('orderdetail');
    }

    //现货订单
    public function orderdetail_xh()
    {
        $array = $this->orderdetail(4);
        $is_new = I('get.is_new_self');
        if ($is_new) $this->assign('is_new_self', 1);
        $Gapp = I('get.Gapp');
        if ($Gapp == 1) {
            $array['detail']['CUST_ID'] = preg_replace('/[a-zA-Z]/', '*', $array['detail']['third_CUST_ID']);
            $array['detail']['CUST_NICK_NM'] = $array['detail']['third_CUST_NM'];
        }
        $this->assign('detail', $array['detail']);
        $this->assign('gudList', $array['gudList']);
        $this->assign('logList', $array['logList']);
        $refund_state = static::refund_state();
        $this->assign('refund_state', $refund_state);
        $this->assign('type', '1');
        $this->assign('ord_type', '4');
        $this->display('orderdetail');
    }

    //直邮代发
    public function orderdetail_dm()
    {
        $array = $this->orderdetail(2);
        $Gapp = I('get.Gapp');
        if ($Gapp == 1) {
            $array['detail']['CUST_ID'] = preg_replace('/[a-zA-Z]/', '*', $array['detail']['third_CUST_ID']);
            $array['detail']['CUST_NICK_NM'] = $array['detail']['third_CUST_NM'];
        }
        $this->assign('detail', $array['detail']);
        //dump($array['gudList']);
        $this->assign('gudList', $array['gudList']);
        $this->assign('logList', $array['logList']);

        $refund_state = static::refund_state();
        $this->assign('refund_state', $refund_state);
        $this->assign('type', '1');
        $this->assign('ord_type', '2');
        $this->assign('is_new_self', true);
        $this->display('orderdetail');
    }

    //保税代发
    public function orderdetail_bw()
    {
        $array = $this->orderdetail(3);
        $Gapp = I('get.Gapp');
        if ($Gapp == 1) {
            $array['detail']['CUST_ID'] = preg_replace('/[a-zA-Z]/', '*', $array['detail']['third_CUST_ID']);
            $array['detail']['CUST_NICK_NM'] = $array['detail']['third_CUST_NM'];
        }
        $this->assign('detail', $array['detail']);
        $this->assign('gudList', $array['gudList']);
        $this->assign('logList', $array['logList']);

        $refund_state = static::refund_state();
        $this->assign('refund_state', $refund_state);
        $this->assign('type', '1');
        $this->assign('ord_type', '3');
        $this->display('orderdetail');
    }

    //求购订单
    public function orderdetail_qg()
    {
        $sendout = 'Orders/sendout_qg';
        $poconfirm = 'Orders/poconfirm_qg';
        $saveord = 'Orders/saveord_qg';
        $saveprice = 'Orders/saveprice_qg';
        $uploadpo = 'Orders/uploadpo';
        $insertguds = 'Orders/insertguds';
        $is_sendout = isset($this->access[$sendout]) ? 1 : 0;
        $is_poconfirm = isset($this->access[$poconfirm]) ? 1 : 0;
        $is_saveord = isset($this->access[$saveord]) ? 1 : 0;
        $is_saveprice = isset($this->access[$saveprice]) ? 1 : 0;
        $is_uploadpo = isset($this->access[$uploadpo]) ? 1 : 0;
        $is_insertguds = isset($this->access[$insertguds]) ? 1 : 0;
        $this->assign('is_sendout', $is_sendout);
        $this->assign('is_poconfirm', $is_poconfirm);
        $this->assign('is_saveord', $is_saveord);
        $this->assign('is_saveprice', $is_saveprice);
        $this->assign('is_uploadpo', $is_uploadpo);
        $this->assign('is_insertguds', $is_insertguds);


        if (empty($_GET['ordId'])) {
            redirect(U('Public/error'), 2, '无订单号');
            return false;
        }
        $orderWhere['tb_ms_ord_spl.ORD_ID'] = I('get.ordId');
        $order = M('ms_ord_spl', 'tb_');
        $orderField = 'tb_ms_ord_spl.REMARK_MSG, tb_ms_ord_spl.ORD_ID,tb_ms_ord_spl.OPTION_MODE_CD,tb_ms_ord_spl.CUST_ID,tb_ms_cust.CUST_NICK_NM,tb_ms_ord_spl.ORD_CUST_EML, tb_ms_ord_spl.REQ_CONT,tb_ms_ord_spl.ORD_CUST_NM,tb_ms_ord_spl.ADPR_ADDR,tb_ms_ord_spl.REQ_CONT,tb_ms_ord_spl.ORD_CUST_CP_NO,tb_ms_ord_package.EXPE_COMPANY,tb_ms_ord_package.TRACKING_NUMBER,sms_ms_estm.DLV_AMT,sms_ms_estm.DISCOUNT_MN,tb_ms_ord_spl.PAY_AMOUNT,bbm_pay_order.PAY_ID,bbm_pay_callback.channel,sms_ms_ord.ORD_SUM_AMT,sms_ms_estm.STD_XCHR_AMT';
//        $detail = $order->field($orderField)
//            ->join('left join sms_ms_ord on sms_ms_ord.B5C_ORD_NO = tb_ms_ord_spl.ORD_ID')
//            ->join('left join sms_ms_estm on tb_ms_ord_spl.ORD_ID=sms_ms_estm.ORD_NO')
//            ->join('left join tb_ms_ord_guds_img on tb_ms_ord_guds_img.ORD_ID = tb_ms_ord_spl.ORD_ID')
//            ->join('left join tb_ms_cust on tb_ms_ord_spl.CUST_ID=tb_ms_cust.CUST_ID')
//            ->join('left join tb_ms_ord_package on tb_ms_ord_spl.ORD_ID=tb_ms_ord_package.ORD_ID')
//            ->join('left join bbm_pay_order on tb_ms_ord_spl.ORD_ID=bbm_pay_order.order_id')
//            ->join('left join bbm_pay_callback  on bbm_pay_callback.pay_id = bbm_pay_order.pay_id')
//            ->where($orderWhere)->find();
        $detail = $order->field('tb_ms_ord_spl.REMARK_MSG, tb_ms_ord_spl.ORD_ID,tb_ms_ord_spl.OPTION_MODE_CD,tb_ms_ord_spl.CUST_ID,tb_ms_cust.CUST_NICK_NM,tb_ms_ord_spl.ORD_CUST_EML, tb_ms_ord_spl.REQ_CONT,tb_ms_ord_spl.ORD_CUST_NM,tb_ms_ord_spl.ADPR_ADDR,tb_ms_ord_spl.REQ_CONT,tb_ms_ord_spl.ORD_CUST_CP_NO,tb_ms_ord_package.EXPE_COMPANY,tb_ms_ord_spl.DLV_AMT,tb_ms_ord_spl.DISCOUNT_MN,tb_ms_ord_spl.TARIFF,tb_ms_ord_spl.PO_SUM_AMT as ORD_SUM_AMT,tb_ms_ord_package.TRACKING_NUMBER,tb_ms_ord_spl.PAY_AMOUNT,bbm_pay_order.PAY_ID,tb_ms_ord_spl.PAY_WAY,tb_ms_ord_spl.PAY_SER_NM,bbm_pay_callback.channel')
            ->join('left join tb_ms_ord_guds_img on tb_ms_ord_guds_img.ORD_ID = tb_ms_ord_spl.ORD_ID')
            ->join('left join tb_ms_cust on tb_ms_ord_spl.CUST_ID=tb_ms_cust.CUST_ID')
            ->join('left join tb_ms_ord_package on tb_ms_ord_spl.ORD_ID=tb_ms_ord_package.ORD_ID')
            ->join('left join bbm_pay_order on tb_ms_ord_spl.ORD_ID=bbm_pay_order.order_id')
            ->join('left join bbm_pay_callback  on bbm_pay_callback.pay_id = bbm_pay_order.pay_id')
            ->where($orderWhere)->find();
        $img = M('ms_ord_guds_img', 'tb_')->where('ORD_ID = "' . $_GET['ordId'] . '"')->select();
//        $gud_list = M('ms_estm_guds','sms_')
//            ->field('tb_ms_guds.STD_XCHR_KIND_CD,tb_ms_guds_img.GUDS_IMG_CDN_ADDR,sms_ms_estm_guds.ORD_GUDS_SALE_PRC,sms_ms_estm_guds.ORD_GUDS_CNS_NM,sms_ms_estm_guds.ORD_GUDS_QTY,tb_ms_guds_opt.*')
//            ->join('left join tb_ms_guds_opt on tb_ms_guds_opt.GUDS_OPT_ID = sms_ms_estm_guds.SKU_ID')
//            ->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_guds_opt.GUDS_ID')
//            ->join('left join tb_ms_guds_img on tb_ms_guds_img.GUDS_ID = tb_ms_guds_opt.GUDS_ID')
//            ->group('sms_ms_estm_guds.SKU_ID')
//            ->where('ORD_NO = "'.$_GET['ordId'].'" and tb_ms_guds_img.GUDS_IMG_CD = "N000080200"')
//            ->select();
        $gud_list = M('ms_ord_guds_opt', 'tb_')
            ->field('tb_ms_guds_opt.GUDS_OPT_VAL_MPNG,tb_ms_guds_opt.GUDS_OPT_ID,tb_ms_ord_guds_opt.RMB_PRICE,tb_ms_ord_guds_opt.ORD_GUDS_CNS_NM as ORD_GUDS_CNS_NM,tb_ms_ord_guds_opt.ORD_GUDS_QTY')
            ->join('left join tb_ms_guds_opt on tb_ms_guds_opt.GUDS_OPT_ID = tb_ms_ord_guds_opt.GUDS_OPT_ID')
            ->group('tb_ms_ord_guds_opt.GUDS_ID')
            ->where('ORD_ID = "' . $_GET['ordId'] . '"')
            ->select();
//        $rate = $detail['STD_XCHR_AMT'];
        foreach ($gud_list as $k => $gud) {
            $sku = substr($gud['GUDS_OPT_ID'], 0, 8);
            $delivery = M('ms_guds', 'tb_')->where('GUDS_ID = "' . $sku . '"')->field('DELIVERY_WAREHOUSE')->find();
            $gud_img = M('ms_guds_img', 'tb_')->where('GUDS_ID = "' . $sku . '" and GUDS_IMG_CD = "N000080200"')->find();
            $gud_list[$k]['GUDS_IMG_CDN_ADDR'] = $gud_img['GUDS_IMG_CDN_ADDR'];
//            $gud_list[$k]['ORD_GUDS_SALE_PRC'] = sprintf("%.2f",$gud['ORD_GUDS_SALE_PRC']/$rate);
            $opt_val = explode(';', $gud['GUDS_OPT_VAL_MPNG']);
            foreach ($opt_val as $v) {
                $val_str = '';
                $o = explode(':', $v);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
            }
            $gud_list[$k]['sku_val'] = $val_str;
            $gud_list[$k]['DELIVERY_WAREHOUSE'] = $delivery['DELIVERY_WAREHOUSE'];
        }
        //订单log记录
        $logWhere['ORD_NO'] = I('get.ordId');
        $ModelLog = M('ms_ord_hist', 'sms_');
        $logField = 'ORD_HIST_REG_DTTM, ORD_STAT_CD, ORD_HIST_WRTR_EML, ORD_HIST_HIST_CONT';
        $logList = $ModelLog->field($logField)->where($logWhere)->order('ORD_HIST_SEQ desc')->select();
        foreach ($logList as $k => $v) {
            if (strstr($v['ORD_HIST_HIST_CONT'], 'BR_SYS_FILE_')) {
                $name = explode(')', explode('(', $v['ORD_HIST_HIST_CONT'])[1])[0];
                $url = U('orders/downloadfile', array('name' => $name));
                $logList[$k]['ORD_HIST_HIST_CONT'] = '<a href="' . $url . '" style="text-decoration:underline;color:#06c;">' . $v['ORD_HIST_HIST_CONT'] . '</a>';
            }
        }
        $this->assign('logList', $logList);
        $this->assign('img', $img);
        $detail['ORD_STAT_CD_NAME'] = L($detail['OPTION_MODE_CD']);
        $this->assign('detail', $detail);
        $this->assign('gudList', $gud_list);
        $EXPE_COMPANY = M('ms_cmn_cd', 'tb_')->where('CD_NM = "LOGISTICS_COMPANY"')->select();
        $this->assign('EXPE_COMPANY', $EXPE_COMPANY);
        $this->display('orderdetail_qg');
    }

    public function downloadfile()
    {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = '/opt/b5c-disk/doc/' . $name;
        Http::download($filename, $filename);
    }

    //自营订单
    public function orderdetail_self()
    {
        $sku_arr = I('sku');
        if (!empty($sku_arr)) {
            $where['ORDER_ID'] = $_GET['ordId'];
            $Model = M();
            foreach ($sku_arr as $k => $v) {
                $where['SKU_ID'] = $k;
                $save['B5C_SKU_ID'] = $v;
                $save['UPDATE_USER_LAST'] = session('m_loginname');
                $res = $Model->table('tb_op_order_guds')->where($where)->save($save);
            }
        }
        $update_info = I('update_info');
        if ('7fe4771c008a22eb763df47d19e2c6aa' == $update_info) {
            $Model = M();
            $where['ORDER_ID'] = $_GET['ordId'];
            $save = $this->join_info_upd($_POST);
            $save['UPDATE_USER_LAST'] = session('m_loginname');
            $res = $Model->table('tb_op_order')->where($where)->save($save);
        }
        if (empty($_GET['ordId'])) {
            redirect(U('Public/error'), 2, '无订单号');
            return false;
        }
        //print_r($_GET);exit();
        $orderWhere['tb_op_order.ORDER_ID'] = I('get.ordId');
        $order = M('op_order', 'tb_');
        $orderField = 'tb_op_order.REMARK_MSG, tb_op_order.ORDER_ID,tb_op_order.ORDER_NO, tb_op_order.BWC_ORDER_STATUS,tb_op_order.B5C_ORDER_NO, tb_op_order.PLAT_CD,
                        tb_op_order.SHOP_ID, tb_op_order.PLAT_NAME, tb_op_order.USER_ID, tb_op_order.USER_NAME, tb_op_order.USER_EMAIL, tb_op_order.PAY_METHOD,
                        tb_op_order.PAY_TRANSACTION_ID, tb_op_order.PAY_CURRENCY, tb_op_order.PAY_SETTLE_PRICE, tb_op_order.PAY_VOUCHER_AMOUNT,
                        tb_op_order.PAY_SHIPING_PRICE, tb_op_order.PAY_PRICE, tb_op_order.PAY_TOTAL_PRICE, tb_op_order.ADDRESS_USER_NAME, tb_op_order.ADDRESS_USER_PHONE,
                        tb_op_order.ADDRESS_USER_PROVINCES, tb_op_order.ADDRESS_USER_CITY, tb_op_order.ADDRESS_USER_REGION,  
                        tb_op_order.ADDRESS_USER_COUNTRY,tb_op_order.ADDRESS_USER_COUNTRY_CODE, tb_op_order.ADDRESS_USER_ADDRESS1, tb_op_order.ADDRESS_USER_ADDRESS2, tb_op_order.ADDRESS_USER_ADDRESS3, tb_op_order.ADDRESS_USER_POST_CODE, tb_op_order.SHIPPING_MSG,
                        tb_op_order.SHIPPING_TYPE,tb_op_order.SHIPPING_DELIVERY_COMPANY,tb_op_order.SHIPPING_TRACKING_CODE,tb_op_order.PAY_SHIPING_PRICE,tb_op_order.B5C_ACCOUNT_ID,
                        tb_op_order.RECEIVER_TEL,tb_op_order.BUYER_TEL,tb_op_order.BUYER_MOBILE,
                        tb_op_order.ADDRESS_USER_ADDRESS_MSG,tb_ms_store.STORE_INDEX_URL,tb_op_order.STORE_ID
                        ';

        $detail = $order->field($orderField)
            ->join('left join tb_ms_store on tb_ms_store.ID  = tb_op_order.STORE_ID')
            ->where($orderWhere)->find();
//        $country_crm = $detail['ADDRESS_USER_COUNTRY'];
//        $countData = M('crm_site','tb_')->where("PARENT_ID=0  AND RES_NAME= '$country_crm'")->find();
//        $detail['ADDRESS_USER_COUNTRY'] = $countData['NAME'];
        if ($detail['B5C_ORDER_NO']) {
            $lgt = M('ms_lgt_track', 'tb_')
                ->alias('t')
                ->field('LGT_TYPE,LGT_CONTENT')
                ->join('left join tb_ms_ord_package as a on a.ORD_ID=t.ORD_ID and a.LOGISTIC_STATUS=t.LGT_TYPE')
                ->where(['t.ORD_ID' => $detail['B5C_ORDER_NO']])
                ->find();
            if ($lgt) {
                $detail['LGT_TYPE'] = $lgt['LGT_TYPE'];
                $lgt_content = json_decode($lgt['LGT_CONTENT'], ture);
                $detail['LGT_CONTENT'] = $lgt_content['steps'];
            }
        }
        if ($detail['B5C_ACCOUNT_ID']) {
            $cust = M('ms_cust', 'tb_')->where('CUST_ID = "' . $detail['B5C_ACCOUNT_ID'] . '"')->field('CUST_NICK_NM')->find();
            $detail['USER_NAME'] = $cust['CUST_NICK_NM'];
        }
        $detail['ORD_STAT_CD_NAME'] = L($detail['BWC_ORDER_STATUS']);
        //dump($detail);  //订单
        //订单商品list
        $gud = M('op_order_guds', 'tb_');
        $gudField = 'tb_op_order_guds.B5C_ITEM_ID,tb_op_order_guds.B5C_SKU_ID,tb_op_order_guds.SKU_ID, tb_op_order_guds.ORDER_ITEM_ID, tb_op_order_guds.ITEM_NAME, tb_op_order_guds.SKU_MESSAGE,
                    tb_op_order_guds.ITEM_PRICE, tb_op_order_guds.ITEM_COUNT,tb_ms_guds_opt.SLLR_ID,tb_ms_guds_opt.GUDS_ID,tb_op_order_guds.PRODUCT_DETAIL_URL';
        $gudWhere['tb_op_order_guds.ORDER_ID'] = I('get.ordId');
        $gud_list = $gud->field($gudField)
            ->join('left join tb_ms_guds_opt on tb_op_order_guds.SKU_ID=tb_ms_guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds on tb_op_order_guds.B5C_ITEM_ID=tb_ms_guds.GUDS_ID')
//                    ->join('left join tb_ms_guds_img on tb_op_order_guds.B5C_ITEM_ID=tb_ms_guds_img.GUDS_ID')
            //->join('left join tb_ms_ord_package on tb_op_order_guds.ORDER_ITEM_ID=tb_ms_ord_package.ORD_ID')
            ->where($gudWhere)->select();
        $M = M();
        foreach ($gud_list as $k => $v) {
            if ($v['B5C_SKU_ID']) {
                $spu = substr($v['B5C_SKU_ID'], 0, 8);
                $guds_name = M('ms_guds', 'tb_')->field('GUDS_NM')->where('MAIN_GUDS_ID= "' . $spu . '" and LANGUAGE = "N000920100"')->find();
                $gud_list[$k]['ITEM_NAME'] = $guds_name['GUDS_NM'];
            }
            if ($v['B5C_SKU_ID']) {
                $opt_val = M('ms_guds_opt', 'tb_')->field('GUDS_OPT_VAL_MPNG')->where('GUDS_OPT_ID= "' . $v['B5C_SKU_ID'] . '"')->find();
                $val_str = '';
                $o = explode(':', $opt_val['GUDS_OPT_VAL_MPNG']);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
                $gud_list[$k]['SKU_MESSAGE'] = $val_str;
            }
            $skuId = substr($v['B5C_SKU_ID'], 0, 8);
            if ($v['B5C_SKU_ID']) {
                $WARE_INFO[] = M('ms_guds', 'tb_')->field("DELIVERY_WAREHOUSE")->where('GUDS_ID=' . $skuId)->find();  //每个商品的仓库信息
            } elseif ($gud_list[$k]['PRODUCT_DETAIL_URL'] && stristr($gud_list[$k]['PRODUCT_DETAIL_URL'], ':') === false) {
                $product_detail_url_mark = $M->table('tb_ms_store')->where('ID =' . $detail['STORE_ID'])->getField('PRODUCT_DETAIL_URL_MARK');
                $url = sprintf($product_detail_url_mark, $gud_list[$k]['PRODUCT_DETAIL_URL']);
                $gud_list[$k]['PRODUCT_DETAIL_URL'] = $url;
            }
            $detail['gudAmount'] += $v['RMB_PRICE'] * $v['ORD_GUDS_QTY'];
        }
        foreach ($WARE_INFO as $k => $v) {
            Logs($gud_list, '$gud_list');
            foreach ($gud_list as $key => $value) {
                $gud_list[$key]['ware_address'] = $v['DELIVERY_WAREHOUSE'];   //拿到仓库信息
                $sku_8 = substr($value['B5C_SKU_ID'], 0, 8);
                if (!$gud_list[$key]['PRODUCT_DETAIL_URL']) {
                    $product_detail_url_mark = $M->table('tb_ms_store')->where('ID =' . $detail['STORE_ID'])->getField('PRODUCT_DETAIL_URL_MARK');
                    if ($product_detail_url_mark) {
                        $where_relation['store_id'] = $detail['STORE_ID'];
                        $where_relation['third_sku_id'] = $value['SKU_ID'];
                        $where_relation['b5c_sku_id'] = $value['B5C_SKU_ID'];
                        $product_detail_url_id = $M->table('tb_ms_sku_relation')->where($where_relation)->getField('PRODUCT_DETAIL_URL_ID');
                        if (!$product_detail_url_id) {
                            unset($where_relation['third_sku_id']);
                            $product_detail_url_id_t = $M->table('tb_ms_sku_relation')->field('PRODUCT_DETAIL_URL_ID')->where($where_relation)->limit(1)->find();
                            $product_detail_url_id = $product_detail_url_id_t['PRODUCT_DETAIL_URL_ID'];
                        }
                        if ($product_detail_url_id) {
                            $url = sprintf($product_detail_url_mark, $product_detail_url_id);
                            $gud_list[$key]['PRODUCT_DETAIL_URL'] = $url;
                        } else {
                            /*$spu = !empty($value['GUDS_ID']) ? $value['GUDS_ID'] : $sku_8;
                            $gud_list[$key]['PRODUCT_DETAIL_URL'] = '/index.php?g=guds&m=guds&a=index#/goodslist/goodsedit/' . $spu;*/
                            $gud_list[$key]['PRODUCT_DETAIL_URL'] = null;
                        }
                    }
                } elseif (($gud_list[$key]['PRODUCT_DETAIL_URL'] && stristr($gud_list[$key]['PRODUCT_DETAIL_URL'], ':') === false)) {
                    $product_detail_url_mark = $M->table('tb_ms_store')->where('ID =' . $detail['STORE_ID'])->getField('PRODUCT_DETAIL_URL_MARK');
                    $url = sprintf($product_detail_url_mark, $gud_list[$key]['PRODUCT_DETAIL_URL']);
                    $gud_list[$key]['PRODUCT_DETAIL_URL'] = $url;
                }
                //$gud_list[$key]['ware_address'] = $EXPE_COMPANY[$gud_list[$key]['ware_address']];
                $img_path = $M->table('tb_ms_guds_img')->where('GUDS_ID = ' . $sku_8)->group('GUDS_ID')->getField('GUDS_IMG_CDN_ADDR');
                $gud_list[$key]['IMGS'] = $img_path;
            }
        }

        //echo '<pre>';print_r($detail);echo '</pre>';
        $array['detail'] = $detail;
        $array['gudList'] = $gud_list;
        $logWhere['ORD_NO'] = I('get.ordId');
        $ModelLog = M('ms_ord_hist', 'sms_');
        $logField = 'ORD_HIST_REG_DTTM, ORD_STAT_CD, ORD_HIST_WRTR_EML, ORD_HIST_HIST_CONT';
        $logList = $ModelLog->field($logField)->where($logWhere)->order('ORD_HIST_SEQ desc')->select();
        $WARE_CD = M('ms_cmn_cd', 'tb_')->where('CD_NM = "DELIVERY_WAREHOUSE"')->select();
        $this->assign('WARE_CD', $WARE_CD);
        $this->assign('logList', $logList);
        $this->assign('detail', $array['detail']);
        $array['gudList'] = B2bModel::goodsImgAdd($array['gudList'], 'B5C_SKU_ID');
        // Logs($array['gudList'],'$array[\'gudList\']','orders');
        $this->assign('gudList', $array['gudList']);
        $this->assign('type', '3');
        $this->assign('logistic_status', C('logistic_status'));


        $this->display('orderdetail_self');
    }

    //上传po单
    public function uploadpo()
    {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->maxSize = -1;// 设置附件上传大小
        $upload->allowExts = array();// 设置附件上传类型
        $upload->savePath = '/opt/b5c-disk/doc/';// 设置附件上传目录
        if (!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            //存储上传文件的数据
            $type = I('post.type');
            $ord_id = I('post.ORD_NO');
            $ordseq = M('ms_ord_file', 'sms_')->field('ORD_FILE_SEQ')->where('ORD_NO = "' . I('post.ORD_NO') . '"')->order('ORD_FILE_SEQ desc')->find();
            $data['ORD_NO'] = $ord_id;
            $data['ORD_FILE_SEQ'] = isset($ordseq) ? $ordseq['ORD_FILE_SEQ'] + 1 : 0;
            if ($type == 1) {
                $data['ORD_FILE_KIND_CD'] = 'N000540200';
                $content = '上传询盘单(' . $info[0]['savename'] . ')';
            } elseif ($type == 2) {
                $data['ORD_FILE_KIND_CD'] = 'N000540300';
                $content = '上传报价单(' . $info[0]['savename'] . ')';
            } else {
                $data['ORD_FILE_KIND_CD'] = 'N000540100';
                $content = '上传PO单(' . $info[0]['savename'] . ')';
            }
            $data['ORD_FILE_ORGT_FILE_NM'] = $info[0]['name'];
            $data['ORD_FILE_SYS_FILE_NM'] = $info[0]['savename'];
            $data['ORD_FILE_REGR_EML'] = I('post.ORD_CUST_EML');
            $data['ORD_FILE_REG_DTTM'] = date('Y-m-d H:i:s');
            $data['updated_time'] = date('Y-m-d H:i:s');
            if (M('ms_ord_file', 'sms_')->data($data)->add()) {
                if ($type == 3) {
                    //PO上传，插入商品数据
                    $guds = $this->importExcel($info[0]['savename']);
                    $this->insertguds($guds, $ord_id);
                }
                $log = A('Log');
                $log->index($ord_id, I('post.ORD_STAT_CD'), $content);
                $this->success(L('上传成功'));
            }
        }
    }


    public function uploadexpe()     //物流模板导入
    {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $expe = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $temp = [];
            $ORDER_ID = (string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue();
            $SHIPPING_DELIVERY_COMPANY = (string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue();
            $SHIPPING_DELIVERY_COMPANY_CD = (string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue();
            $SHIPPING_TRACKING_CODE = (string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue();
            if ($ORDER_ID && $SHIPPING_DELIVERY_COMPANY && $SHIPPING_DELIVERY_COMPANY_CD && $SHIPPING_TRACKING_CODE) {
                $temp['ORDER_ID'] = $ORDER_ID;
                $temp['SHIPPING_DELIVERY_COMPANY'] = $SHIPPING_DELIVERY_COMPANY;
                $temp['SHIPPING_DELIVERY_COMPANY_CD'] = $SHIPPING_DELIVERY_COMPANY_CD;
                $temp['SHIPPING_TRACKING_CODE'] = $SHIPPING_TRACKING_CODE;
                $expe[] = $temp;
            }
            $temp = [];
        }
        $order_m = M('op_order', 'tb_');
        /*$failed_orders = '';*/
        $failed = [];
        $failed_orders = array();
        $failed_num = 0;
        $success_orders = '';
        $success_num = 0;
        $log = A('Log');
        // 筛选
        foreach ($expe as $val) {
            $order = $order_m->where('ORDER_ID = "' . $val['ORDER_ID'] . '"')->find();
            if ($order) {
                // 物流公司和code必须对应
                if (L($val['SHIPPING_DELIVERY_COMPANY_CD']) != $val['SHIPPING_DELIVERY_COMPANY']) {
                    /*$failed_orders .= $val['ORDER_ID'] . '|';*/
                    $failed[] = $val['ORDER_ID'];
                    $failed[] = "物流公司和code不对应";
                    $failed_count = count($failed);
                    for ($i = 0; $i < $failed_count / 2; $i++) {
                        for ($a = 0; $a < 2; $a++) {
                            $failed_orders[$i][$a] = $failed[$i * 2 + $a];
                        }
                    }

                    $failed_num++;
                    continue;
                }
                // 必须是待发货，才能成功，condition.1,新增待收货也可修改物流
                if ($order['BWC_ORDER_STATUS'] != 'N000550400' and $order['BWC_ORDER_STATUS'] != 'N000550500') {
                    /* $failed_orders .= $val['ORDER_ID'] . '|';*/
                    $failed[] = $val['ORDER_ID'];
                    $failed[] = "必须是待发货，才能成功";
                    $failed_count = count($failed);
                    for ($i = 0; $i < $failed_count / 2; $i++) {
                        for ($a = 0; $a < 2; $a++) {
                            $failed_orders[$i][$a] = $failed[$i * 2 + $a];
                        }
                    }
                    $failed_num++;
                    continue;
                }
                // 过滤掉物流公司跟物流号未发生变化的单号
                if ($order['SHIPPING_DELIVERY_COMPANY_CD'] == $val['SHIPPING_DELIVERY_COMPANY_CD'] && $order['SHIPPING_TRACKING_CODE'] == $val['SHIPPING_TRACKING_CODE']) {
                    $success_orders .= $val['ORDER_ID'] . '|';
                    $success_num++;
                    continue;
                }
                // 当订单状态为待收货时，且发生的物流修改操作，此时将订单状态重置回待发货
                if ($order['BWC_ORDER_STATUS'] == 'N000550500') {
                    $val ['BWC_ORDER_STATUS'] = 'N000550400';
                    $order ['BWC_ORDER_STATUS'] = 'N000550400';
                }
                // 更新物流，这里可不用事物，如果因为网络原因失败，重复导入即可
                if ($order_m->where('ORDER_ID = "' . $val['ORDER_ID'] . '"')->data($val)->save()) {
                    // 日志记录
                    $content = '物流信息修改成功：快递公司：' . L($val['SHIPPING_DELIVERY_COMPANY']) . ',运单号：' . $val['SHIPPING_TRACKING_CODE'];
                    $log->index($val['ORDER_ID'], $order ['BWC_ORDER_STATUS'], $content);
                    $success_orders .= $val['ORDER_ID'] . '|';
                    $success_num++;
                } else {
                    /* $failed_orders .= $val['ORDER_ID'] . '|';*/
                    $failed[] = $val['ORDER_ID'];
                    $failed[] = "网络原因失败";
                    $failed_count = count($failed);
                    for ($i = 0; $i < $failed_count / 2; $i++) {
                        for ($a = 0; $a < 2; $a++) {
                            $failed_orders[$i][$a] = $failed[$i * 2 + $a];
                        }
                    }
                    $failed_num++;
                }
            } else {
                /* $failed_orders .= $val['ORDER_ID'] . '|';*/

                $failed[] = $val['ORDER_ID'];
                $failed[] = "订单不存在";
                $failed_count = count($failed);
                for ($i = 0; $i < $failed_count / 2; $i++) {
                    for ($a = 0; $a < 2; $a++) {
                        $failed_orders[$i][$a] = $failed[$i * 2 + $a];
                    }
                }

                $failed_num++;
            }
        }

        /* print_r($failed_orders);exit;*/
        $total_order_num = $failed_num + $success_num;


        $this->assign('failed_orders', $failed_orders);
        $this->assign('failed_num', $failed_num);
        $this->assign('success_orders', $success_orders);
        $this->assign('success_num', $success_num);
        $this->assign('total_order_num', $total_order_num);
        $this->display('importexpemsg');
    }

    public function Curl_post($url, $data)
    {

        $ch = curl_init();
        $header = array(
            "Accept: application/json",
            "Content-Type: application/json"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $PostData = $data;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
        return $temp;
        curl_close($ch);
    }

    /**
     *第三方订单导入
     */
    public function otherimport($is_json = false)
    {
        session_write_close();
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPReader->setReadDataOnly(true);
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        $redis = null;
        $nameUnique = date("YmdHis", time());
        $excel_key = $this->excelKeyJoin();
        $this->Model = M();
        $cd_arr = ['N00068', 'N00070', 'N00083', 'N00055', 'N00201'];
        $this->cd_valkey_arr = CodeModel::getCodeValKeyArr($cd_arr);
        $this->logistics_mode_arr = $this->getLogisticsMode();
        $activeSheet = $PHPExcel->getActiveSheet();
        $getVal = function ($key) use ($activeSheet) {
            return preg_replace('/(^[\s\t\r\n　]+)|([\s\t\r\n　]+$)/u', '', $activeSheet->getCell($key)->getValue());
        };
        $check_data = [];
        $line_map = [];
        $tmpTitleKey = array_values($excel_key);
        $order_type_data = array();  // 订单类型信息
        for ($row = 2; $row <= $allRow; $row++) {    //从第二行数据开始读取
            unset($order_data);
            //校验最大行
            $hasData = false;
            foreach ($tmpTitleKey as $tmpTitleKeyValue) {
                if ((string)$getVal($tmpTitleKeyValue . $row) != '') {
                    $hasData = true;
                }
            }
            if (!$hasData) {
                break;
            }


            $line_map[$row]['order_id'] = (string)$getVal($excel_key['order_id'] . $row);
            $order_data['exist'] = $this->check_orderID((string)$getVal($excel_key['order_id'] . $row));
            $send_data['thr_order_number'] = $guds_data['ORDER_ID'] = $order_data['ORDER_ID'] = (string)$getVal($excel_key['order_id'] . $row);   //E
            $order_data['ORDER_NO'] = (string)$getVal($excel_key['order_no'] . $row); // order NO
            $order_data['PLAT_NAME'] = $getVal($excel_key['plat_cd'] . $row);   //平台code
            if ($this->cd_valkey_arr[$order_data['PLAT_NAME']]) {
                $paltCd = $send_data['plat_cd'] = $order_data['PLAT_CD'] = $this->cd_valkey_arr[$order_data['PLAT_NAME']];
            } else {
                $paltCd = $send_data['plat_cd'] = $order_data['PLAT_CD'] = '';
            }

            $storeId = $this->check_shopID((string)$getVal($excel_key['shop_id'] . $row), $paltCd);
            $order_type_data[$order_data['ORDER_ID'].'_'. $order_data['PLAT_CD']] = $this->store_order_type[$storeId[0]['store_type_cd']];
            $storeId = $storeId[0]['ID'];
            if ($storeId) {
                $order_data['SHOP_ID'] = (string)$getVal($excel_key['shop_id'] . $row);
                $order_data['STORE_ID'] = (int)$storeId;
            } else {
                $order_data['SHOP_ID'] = null;
                $order_data['STORE_ID'] = null;
            }
            //校验最大行
            // if (!$order_data['PLAT_CD'] && !$order_data['SHOP_ID'] && !$order_data['ORDER_ID'] && !$order_data['ORDER_NO']) {
            //     break;
            // }

            $order_data['PAY_CURRENCY'] = $this->upd_cd((string)$getVal($excel_key['pay_currency'] . $row)) ? (string)$getVal($excel_key['pay_currency'] . $row) : null;  //币种

            $order_data['PAY_VOUCHER_AMOUNT'] = $this->getNum($getVal($excel_key['pay_voucher_amount'] . $row));   //优惠金额
            $order_data['PAY_SHIPING_PRICE'] = $this->getNum($getVal($excel_key['pay_shiping_price'] . $row));   //运费
            $order_data['PAY_PRICE'] = $order_data['PAY_TOTAL_PRICE'] = $this->getNum($getVal($excel_key['pay_price'] . $row));   //支付总价
            $order_data['PAY_SETTLE_PRICE'] = $this->getNum($getVal($excel_key['pay_settle_price'] . $row));   //结算价

            $guds_data['third_party_sales_price'] = $this->getNum($getVal($excel_key['third_party_sales_price'] . $row));   // 第三方商品销售价


            $order_data['PAY_TRANSACTION_ID'] = (string)$getVal($excel_key['pay_transaction_id'] . $row);   //第三方支付号
            $order_data['out_trade_no'] = (string)$getVal($excel_key['out_trade_no'] . $row);   //交易号2


            $order_data['ORDER_TIME'] = date("Y-m-d H:i:s", strtotime($this->check_orderTime($getVal($excel_key['order_time'] . $row), $row) ? $getVal($excel_key['order_time'] . $row) : ''));//下单时间
            if (empty($order_data['ORDER_TIME'])) {
                $order_data['ORDER_TIME'] = null;
            }
            $order_data['ORDER_PAY_TIME'] = date("Y-m-d H:i:s", strtotime($this->check_orderPayTime($getVal($excel_key['order_pay_time'] . $row), $row) ? $getVal($excel_key['order_pay_time'] . $row) : ''));   //付款时间
            if (empty($order_data['ORDER_PAY_TIME'])) {
                $order_data['ORDER_PAY_TIME'] = null;
            }
            $shipping_time_excel = $getVal($excel_key['shipping_time'] . $row);
            if (!empty($shipping_time_excel)) {
                $order_data['SHIPPING_TIME'] = date("Y-m-d H:i:s", strtotime($shipping_time_excel));   //发货时间
            } else {
                $order_data['SHIPPING_TIME'] = null;
            }

            $api_currency = BaseCommon::api_currency(strtoupper($order_data['PAY_CURRENCY']), $order_data['ORDER_PAY_TIME']);
            if ($api_currency) {
                $order_data['PAY_TOTAL_PRICE_DOLLAR'] = $order_data['PAY_TOTAL_PRICE'] * $api_currency;
            }
            if (!$api_currency || ($order_data['PAY_TOTAL_PRICE_DOLLAR'] == $order_data['PAY_TOTAL_PRICE'] && $order_data['PAY_CURRENCY'] != 'USD')) {
                $order_data['PAY_TOTAL_PRICE_DOLLAR'] = null;
            }


            $order_data['logistic_cd'] = $order_data['b5c_logistics_cd'] = (string)$getVal($excel_key['logistic_cd'] . $row);   //物流编码
            $this->warehouseExcelJoin($activeSheet, $row, $order_data, $excel_key, $check_data);
           
            list($order_data, $send_data) = $this->getExcelData($getVal, $row, $order_data, $send_data, $excel_key);

            $order_data['CREATE_USER'] = session('m_loginname');
            $order_data['SOURCE'] = 'Excel 导入';

            $order_data['FILE_NAME'] = $order_data['CREATE_USER'] . '_' . $nameUnique . '.xlsx';
            //order_data 是订单数据
            $order_data['UPDATE_TIME'] = $order_data['ORDER_CREATE_TIME'] = date("Y:m:d H:i:s");
            //guds_data 是商品数据
            $guds_data['PLAT_CD'] = $paltCd ?: '';
            $guds_data['B5C_SKU_ID'] = $this->check_skuID((string)$getVal($excel_key['b5c_sku_id'] . $row)) ? (string)$getVal($excel_key['b5c_sku_id'] . $row) : 'sku##' . (string)$getVal($excel_key['b5c_sku_id'] . $row);  //SKUID

            $guds_data['SKU_ID'] = $guds_data['ORDER_ITEM_ID'] = (string)$getVal($excel_key['sku_id'] . $row);  //第三方商品ID
            //这里写H
            $PLAT_FORM = [
                'N000831400',
                'N000834100',
                'N000834200',
                'N000834300'
            ];
            $guds_data['GAPP_SKU_ID'] = $guds_data['E2G_SKU_ID'] = null;
            if (in_array($paltCd, $PLAT_FORM)) {

                $guds_data['GAPP_SKU_ID'] = $guds_data['E2G_SKU_ID'] = $this->check_e2gSkuID((string)$getVal($excel_key['gapp_sku_id'] . $row), $redis) ? (string)$getVal($excel_key['gapp_sku_id'] . $row) : null;
            }                                                                       //E2G关联SKU ID


            $guds_data['ITEM_NAME'] = (string)$getVal($excel_key['item_name'] . $row);    //商品标题
            $guds_data['SKU_MESSAGE'] = (string)$getVal($excel_key['sku_message'] . $row);     //商品属性
            $guds_data['ITEM_PRICE'] = $this->getNum($getVal($excel_key['item_price'] . $row));   //商品单价
            $guds_data['ITEM_COUNT'] = $this->getNum($getVal($excel_key['item_count'] . $row), true, true);   //商品数量

            $order_data['PAY_ITEM_PRICE'] = $guds_data['ITEM_PRICE'] && $guds_data['ITEM_COUNT'] ? $guds_data['ITEM_PRICE'] * $guds_data['ITEM_COUNT'] : '0';   // 商品总价 //自己算
            $guds_data['row'] = $order_data['row'] = $row;
            $order_data = $this->trimData($order_data);//order_id 空格错误
            $send_data = $this->trimData($send_data);
            $guds_data = $this->trimData($guds_data);
            if (isset($order_all_data) && isset($order_all_data[$guds_data['ORDER_ID']])) {
                $order_data['PAY_ITEM_PRICE'] += $order_all_data[$guds_data['ORDER_ID']]['PAY_ITEM_PRICE'];
            }
            // 对于GP平台导入订单，身份证加密
            $order_data['BUYER_USER_IDENTITY_CARD'] = $order_data['ADDRESS_USER_IDENTITY_CARD'];
            if ($order_data['ADDRESS_USER_IDENTITY_CARD'] && OrderModel::isB2c($order_data['PLAT_CD']) && $order_data['PLAT_CD'] != 'N000837453'){
                $order_data['ADDRESS_USER_IDENTITY_CARD'] = ZUtils::encodeDecodeId("enc", $order_data['ADDRESS_USER_IDENTITY_CARD']); ;
                $order_data['BUYER_USER_IDENTITY_CARD'] = $order_data['ADDRESS_USER_IDENTITY_CARD'];
            }

            $order_all_data[$guds_data['ORDER_ID']] = $order_data;
            $guds_all_data[] = $guds_data;
        }

        //国家信息缓存
        $country_name_arr = [];
        $unique_orders = $this->check_null($order_all_data, $guds_all_data, $country_name_arr, $check_data);   //检查excel表数据的问题
        $result = ['success' => 0, 'total' => count($order_all_data), 'fail' => count($check_data), 'exist' => 0];
        if (empty($check_data)) {
//            $check_data = $this->filterGpOrder($order_all_data);
        }
        if (empty($check_data)) {    //如果数据没有问题,则把数据插入数据表
            $model = new Model();
            $model->startTrans();
            if (empty($order_all_data) && empty($guds_all_data)) {
                Logs($unique_orders, '$unique_orders');
                $model->rollback();
                $result['fail'] = $result['total'];
                $this->assign('result', json_encode($result));
                $this->display('Oms@Patch:import_result');
            }
            $order_source_status_map = $this->getSourceStatusMap();
            $order_source_status_value_cd_map = array_flip($order_source_status_map);
            $gp_site_code = CodeModel::getGpPlatCds();
            $hash = [];
            foreach ($order_all_data as $key => $val) {
                unset($val['row']);
                unset($val[$val['ORDER_ID']]);
                $val['ADDRESS_USER_COUNTRY_ID'] = $country_name_arr[$val['ADDRESS_USER_COUNTRY']]['id'];
                $val['ADDRESS_USER_COUNTRY_CODE'] = $country_name_arr[$val['ADDRESS_USER_COUNTRY']]['twoChar'];
                $val['ADDRESS_USER_COUNTRY_EDIT'] = $val['ADDRESS_USER_COUNTRY'];
                $val['ADDRESS_USER_COUNTRY'] = $country_name_arr[$val['ADDRESS_USER_COUNTRY']]['enName'];
                $order_origin = strtolower($val['order_origin']);
                $val['order_origin'] = isset($order_source_status_value_cd_map[$order_origin]) ? $order_source_status_value_cd_map[$order_origin] : NULL; #订单来源字段添加
                if (in_array($val['ORDER_ID'], $unique_orders)) {
                    unset($order_all_data[$key]);
                } else {
                    if ($val['tracking_number']) {
                        $add_package['ORD_ID'] = $val['ORDER_ID'];
                        $add_package['PLAT_CD'] = $val['PLAT_CD'];
                        $add_package['TRACKING_NUMBER'] = $val['tracking_number'];
                        $add_package_arr[] = $add_package;
                    }
                    unset($val['tracking_number']);
                    $order_all_data_index[] = M('op_order', 'tb_')->create($val);
                }
                if (!in_array($val['PLAT_CD'], $gp_site_code) && $val['ADDRESS_USER_NAME']) {
                    $hash[] = strtolower(hash('sha256', $val['PLAT_CD'] . $val['ADDRESS_USER_NAME']));
                }
            }
            $refund_map       = [];
            $after_sale_count = '';
            if (!empty($hash)) {
                $refund_info  = $model->table('tb_op_order_refund')
                    ->field('user_identifier_hash,count(id) as refund_count')
                    ->where(['user_identifier_hash'=>['in',$hash]])
                    ->group('user_identifier_hash')
                    ->select();
                foreach ($refund_info as $v) {
                    $refund_map[$v['user_identifier_hash']] = $v['refund_count'];//退款hash和退款次数映射
                }
                $after_sale_count = $model->table('tb_ms_cmn_cd')->where(['CD' => 'N003530001'])->getField('CD_VAL');//频次售后次数
            }
            foreach ($order_all_data_index as $key => $item) {
                $order_info[] = [
                    'opOrderId' => $item['ORDER_ID'],
                    'platCd'    => $item['PLAT_CD'],
                ];
                if (!$after_sale_count) {
                    continue;
                }
                $order_hash = strtolower(hash('sha256', $item['PLAT_CD'] . $item['ADDRESS_USER_NAME']));
                if ($refund_map[$order_hash] >= $after_sale_count) {
                    $order_all_data_index[$key]['REMARK_MSG'] = '高风险：99%';
                }
            }
            $order = $model->table('tb_op_order')->addAll($order_all_data_index);   //数据批量写入订单表

            if ($add_package_arr) {
                $package = $model->table('tb_ms_ord_package')->addAll($add_package_arr);
            }
            foreach ($guds_all_data as $key => &$val) {
                if (in_array($val['ORDER_ID'], $unique_orders)) {
                    unset($guds_all_data[$key]);
                }
                $val['CREATE_AT'] = $val['UPDATE_AT'] = DateModel::now();
                $val['CREATE_USER'] = $val['UPDATE_USER_LAST'] = DataModel::userNamePinyin();
                unset($val['row']);
                $guds_all_data[$key] = M('op_order_guds', 'tb_')->create($val);
            }
            $guds_all_data = array_values($guds_all_data);
            $guds_all_data = $this->splitGroupSku($guds_all_data, $model);
            $guds_all_data = $this->customsInfoInsert($guds_all_data, $order_data['STORE_ID'], null,
                'B5C_SKU_ID');
            $guds = $model->table('tb_op_order_guds')->addAll($guds_all_data);    //把excel表中的商品写入对应的订单商品关系表中
            if ($order && $guds) {
                $extend_data = [];
                foreach ($order_info as $item) {
                    if($model->table('tb_op_order_extend')->where(['order_id'=>$item['opOrderId'], 'plat_cd'=>$item['platCd']])->find()) {
                        continue;
                    }
                    $extend_data[] = [
                        'order_id' => $item['opOrderId'],
                        'plat_cd' => $item['platCd'],
                        'btc_order_type_cd' => $order_type_data[$item['opOrderId'].'_'.$item['platCd']],
                        'created_by' => DataModel::userNamePinyin(),
                        'updated_by' => DataModel::userNamePinyin(),
                    ];
                }
                if (!empty($extend_data)) {
                    $res_extend = $model->table('tb_op_order_extend')->addAll($extend_data);
                    if (!$res_extend) {
                        $model->rollback();
                        $result['fail'] = $result['total'];
                        $this->assign('result', json_encode($result));
                        $this->display('Oms@Patch:import_result');
                    }
                }
                if (!file_exists('/opt/b5c-disk/excel')) {
                    mkdir("/opt/b5c-disk/excel");
                }
                $destination = "/opt/b5c-disk/excel/" . session('m_loginname') . '_' . $nameUnique . '.xlsx';    //同一时间上传的exccel表名相同
                if (move_uploaded_file($filePath, $destination)) {
                    $send = $model->table('tb_op_send')->addAll($send_data);
                    SmsMsOrdHistModel::writeMulHist($this->joinExcelLog($order_all_data_index));
                    $model->commit();
                    if (count($order_info) > 0) {
                        //批量主动刷新订单
                        ApiModel::updateOrderFromEs($order_info, __FUNCTION__);
                    }
                    $result['success'] = count($order_all_data_index);
                    $result['fail'] = count($unique_orders);
                } else {
                    $model->rollback();
                    $result['fail'] = $result['total'];
                }
                $this->assign('result', json_encode($result));
                $this->display('Oms@Patch:import_result');
            } else {
                $model->rollback();
                $result['fail'] = $result['total'];
                /*if (!$order) {
                    $result['fail'] = count($unique_orders);
                }
                if (!$guds) {
                    $result['fail'] = count($unique_orders);
                }*/
                $this->assign('result', json_encode($result));
                $this->display('Oms@Patch:import_result');
            }
        } else {
            $ord_id = '';
            $err_count = 0;
            foreach ($check_data as $k => $v) {
                if ($ord_id != $line_map[$k]['order_id']) {
                    $err_count++;
                }
                $ord_id = $line_map[$k]['order_id'];
                $result['check_data'][] = ['line' => $k, 'order_id' => $ord_id, 'msg' => implode("<br/>", $v)];
            }
            $result['fail'] = $err_count;
            $this->assign('result', json_encode($result));
            $this->display('Oms@Patch:import_result');
        }
    }

    private function filterGpOrder($orders)
    {
        $gp_cd_arr = CodeModel::getGpPlatCds();
        foreach ($orders as $key => $order) {
            if (in_array($order['PLAT_CD'], $gp_cd_arr)) {
                $check_data[$order['row']][] = "订单平台属于 GSHOPPER";
            }
        }
        return $check_data;
    }

    /**
     * @param $data
     * @param $model
     *
     * @return array
     */
    private function splitGroupSku($data, $model)
    {
        $sku_arr = array_column($data, 'B5C_SKU_ID');
        $group_sku_map_arr = SkuModel::getGroupSkuMap($sku_arr);
        if (empty($group_sku_map_arr)) {
            return $data;
        }
        foreach ($group_sku_map_arr as $value) {
            $group_sku_two[$value['cb_sku_id']][] = $value;
        }

        $cb_sku_ids = array_unique(array_column($group_sku_map_arr, 'cb_sku_id'));
        $split_cb_sku_ids = SkuModel::getSplitSkuIds($cb_sku_ids);
        foreach ($group_sku_two as $key => $value) {
            if (!in_array($key, $split_cb_sku_ids)) {
                unset($group_sku_two[$key]);
            }
        }
        $original_data = $split_data = [];
        foreach ($data as $dk => $datum) {
            $check_data = [];
            if (9 == substr($datum['B5C_SKU_ID'], 0, 1)) {
                if (in_array($datum['B5C_SKU_ID'], $split_cb_sku_ids)) {
                    $temp_original = [
                        'order_id' => $datum['ORDER_ID'],
                        'plat_cd' => $datum['PLAT_CD'],
                        'sku_id' => $datum['SKU_ID'],
                        'guds_type' => 0,
                        'item_id' => $datum['item_id'],
                        'b5c_sku_id' => $datum['B5C_SKU_ID'],
                        'paid_price' => $datum['PAID_PRICE'],
                        'item_price' => $datum['ITEM_PRICE'],
                        'item_count' => $datum['ITEM_COUNT'],
                        'create_user' => DataModel::userNamePinyin(),
                        'update_user' => DataModel::userNamePinyin(),
                    ];
                    if ($temp_original) {
                        $cb_origin_id = $model->table('tb_op_order_guds_cb_origin')
                            ->add($temp_original);
                    }
                }
                if (!empty($group_sku_two[$datum['B5C_SKU_ID']])) {
                    foreach ($group_sku_two[$datum['B5C_SKU_ID']] as $key => $value) {
                        $temp_datum = $datum;
                        $temp_datum['B5C_SKU_ID'] = $value['sku_id'];
                        $temp_datum['ITEM_COUNT'] = bcmul($temp_datum['ITEM_COUNT'], $value['number']);
                        $temp_datum['group_sku_id'] = $cb_origin_id ? $cb_origin_id : '';
                        $temp_datum['item_id'] = "_" . sprintf('%04s', $key);
                        $temp_datum['ITEM_PRICE'] = null;
                        $temp_datum['PAID_PRICE'] = null;
                        //                    unset($temp_datum['ITEM_PRICE'], $temp_datum['PAID_PRICE']);
                        $split_data[] = $check_data[] = $temp_datum;
                    }
                } else {
                    $data[$dk]['PAID_PRICE'] = $data[$dk]['PAID_PRICE'] ? $data[$dk]['PAID_PRICE'] : null;
                    $data[$dk]['item_id'] = '';
                    $data[$dk]['group_sku_id'] = null;
                }
                $original_data[] = $temp_original;
                if (!empty($check_data)) {
                    unset($data[$dk]);
                }
            } else {
                $data[$dk]['PAID_PRICE'] = $data[$dk]['PAID_PRICE'] ? $data[$dk]['PAID_PRICE'] : null;
                $data[$dk]['item_id'] = null;
                $data[$dk]['group_sku_id'] = null;
            }
        }
        if ($split_data) {
            $data = array_merge($split_data, array_values($data));
        }
        return $data;
    }

    private function getLogisticsMode()
    {
        $where['IS_DELETE'] = 0;
        $where['IS_ENABLE'] = 1;
        $temp_res = $this->Model->table('tb_ms_logistics_mode')
            ->field('ID,LOGISTICS_MODE')
            ->where($where)
            ->select();
        $res = array_column($temp_res, 'ID', 'LOGISTICS_MODE');
        return $res;
    }

    public function warehouseExcelJoin($activeSheet, $currentRow, &$order_data, $excel_key, &$check_data)
    {
        $warehouse_arr = ['WAREHOUSE' => '[仓库]填写不正确，请前往仓库管理->仓库目录查询后填写', 'logistic_cd' => '[物流公司]名称填写不正确，请前往物流管理->物流目录里查询后填写'];
        $check_arr = ['WAREHOUSE' => 'N00068', 'logistic_cd' => 'N00070'];
        foreach ($warehouse_arr as $key => $value) {
            $order_data[$key] = (string)$activeSheet->getCell($excel_key[$key] . $currentRow)->getValue();
            if ($order_data[$key]) {
                $warehouse_info_val = $this->cd_valkey_arr[trim($order_data[$key])];
                if ($warehouse_info_val && strpos($warehouse_info_val, $check_arr[$key]) !== false) {
                    $order_data[$key] = $warehouse_info_val;
                } else {
                    $check_data[$currentRow][] = $value;
                }
            } else {
                $order_data[$key] = null;
            }
        }
        $order_data['logistic_model_id'] = (string)$activeSheet->getCell($excel_key['logistic_model_id'] . $currentRow)->getValue();
        if ($order_data['logistic_model_id']) {
            if ($this->logistics_mode_arr[trim($order_data['logistic_model_id'])]) {
                $order_data['logistic_model_id'] = $this->logistics_mode_arr[trim($order_data['logistic_model_id'])];
            } else {
                $check_data[$currentRow][] = '[物流方式]名称填写不正确，请前往物流管理->物流方式配置里查询后填写';
            }
        } else {
            $order_data['logistic_model_id'] = NULL;
        }

        $order_data['SURFACE_WAY_GET_CD'] = (string)$activeSheet->getCell($excel_key['SURFACE_WAY_GET_CD'] . $currentRow)->getValue();
        if ($order_data['SURFACE_WAY_GET_CD']) {
            $SURFACE_WAY_GET_CD = $this->cd_valkey_arr[trim($order_data['SURFACE_WAY_GET_CD'])];
            if ($SURFACE_WAY_GET_CD && strpos($SURFACE_WAY_GET_CD, 'N00201') !== false) {
                $order_data['SURFACE_WAY_GET_CD'] = $SURFACE_WAY_GET_CD;
            } else {
                $check_data[$currentRow][] = '[面单获取方式]不正确';
            }
        } else {
            $order_data['SURFACE_WAY_GET_CD'] = '';
        }
        $order_data['tracking_number'] = (string)$activeSheet->getCell($excel_key['tracking_number'] . $currentRow)->getValue();
    }

    public function excelKeyJoin()
    {
        $excel_keys = [
            'plat_cd',
            'shop_id',
            'order_id',
            'order_no',
            'b5c_sku_id',
            'sku_id',
            'pay_currency',
            'item_price',
            'item_count',
            //'pay_item_price',//自己算
            'pay_voucher_amount',
            'pay_shiping_price',
            'pay_price',
            'pay_transaction_id',
            'out_trade_no',
            'order_time',
            'order_pay_time',
            'address_user_name',
            'address_user_phone',
            'receiver_tel',
            //
            'user_email',

            'address_user_identity_card', //

            'address_user_country',
            'address_user_provinces',
            'address_user_city',
            'address_user_address1',

            'bwc_order_status',
            'pay_settle_price',//非必填开始
            'address_user_post_code',
            'shipping_time',
            'WAREHOUSE',
            'logistic_cd',
            'logistic_model_id',
            'SURFACE_WAY_GET_CD',
            'tracking_number',
            'packing_no',
            'shipping_msg',
            'REMARK_MSG',
            'order_origin',


// 之前历史数据
            'gapp_sku_id',
            'item_name',
            'sku_message',
            'third_party_sales_price',
            'shipping_delivery_company',
            'shipping_tracking_code',
            'address_user_region',
            'sender_name',
            'sender_phone',
            'sender_zip_code',
            'sender_company',
            'sender_province',
            'sender_city',
            'sender_area',
            'sender_address',
        ];
        $excel_key_arr = [];
        $key = 'A';
        foreach ($excel_keys as $v) {
            $excel_key_arr[$v] = $key++;
        }
        return $excel_key_arr;
    }


    public function getCustoms($data, $store_id = null, $b5c_order_id = null, $sku_id_key = 'SKU_ID', $cost_price_key = 'CUSTOMS_PRICE')
    {
        $sku_arr = array_column($data, $sku_id_key);
        $request_data = [];
        foreach ($sku_arr as $value) {
            $temp_data['skuId'] = $value;
            $request_data[] = $temp_data;
        }
        if (empty($request_data)) {
            return $data;
        }
        $response_data = ApiModel::getCustomsApi(json_encode($request_data));
        if ($response_data['code'] == 2000) {
            $average_price_array = array_column($response_data['data'], 'averagePurchasePrice', 'skuId');
            foreach ($data as $key => $value) {
                if (empty($data[$key][$cost_price_key]) && $data[$key]['costUsdPrice']) {
                    $data[$key][$cost_price_key] = $data[$key]['costUsdPrice'];
                }
                if (!$b5c_order_id && (empty($data[$key][$cost_price_key]) || $data[$key][$cost_price_key] == 0) && $average_price_array[$value[$sku_id_key]]) {
                    $data[$key][$cost_price_key] = $average_price_array[$value[$sku_id_key]];
                }
            }
        } else {
            Logs(json_encode($response_data), __FUNCTION__ . '批量SKU采购价查询接口失败', __CLASS__);
        }
        return $data;
    }

    /**
     * @param      $data
     * @param null $store_id
     * @param null $b5c_order_id
     * @param $sku_key
     *
     * @return mixed
     */
    public function customsInfoInsert($data, $store_id = null, $b5c_order_id = null, $sku_key = 'SKU_ID')
    {
        // $data[] = $data_single;
        $sku_arr = array_column($data, $sku_key);
        if ($sku_arr) {
            $Model = new PmsBaseModel();
            $where['sku_id'] = array('IN', $sku_arr);
            $res_column = array_column(
                $Model->table('product_sku')
                    ->field('sku_id,hs_price')
                    ->where($where)->select(),
                'hs_price',
                'sku_id');
            foreach ($data as $key => $value) {
                $data[$key]['CUSTOMS_PRICE'] = $res_column[$value[$sku_key]];
                if (empty($res_column[$value[$sku_key]]) || $res_column[$value[$sku_key]] == 0) {
                    $column_null_arr[] = $value[$sku_key];
                }
            }
            $data = $this->getCustoms($data, $store_id = null, $b5c_order_id, $sku_key);
        }
        return $data;
    }

    /**
     * Excel派单
     */
    public function excelPatch()
    {
        Logs('excelAction', 'excelAction', 'patch');
        set_time_limit(3600);
        $is_excel_type = true;
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $sheet = $PHPExcel->getSheet(0);   //获取第一个sheet
        $allRow = $sheet->getHighestRow();   //取得excel总行数
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $order_data['order_id'] = trim($PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $order_data['warehouse'] = $PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue();
            $order_data['send_goods_time'] = $PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue();
            $order_data_arr[] = $order_data;
        }
        Logs('patchAction', 'patchAction', 'patch');
        $patchOrder_res = $this->patch_order($order_data_arr, $is_excel_type);
        Logs('showHtml', 'showHtml', 'patch');
        if ($patchOrder_res['html']) {
            $this->display('dispatch_status');
        } else {
            echo $patchOrder_res['msg'];
        }
    }

    //下载excel
    public function otherexport($filename)
    {
        $filename = I('get.filename');
        import('ORG.Net.Http');
        $filename1 = '/opt/b5c-disk/excel/' . $filename;
        $showName = session('m_loginname') . '_' . date("YmdHis", time()) . '_' . uniqid() . '.xlsx';
        Http::download($filename1, $showName);
    }


    //现货订单修改商品数据
    public function savegudsopt()
    {
        $ORD_ID = I('post.ORD_ID');
        $name = I('post.name');
        $val = I('post.val');
        $data[$name] = $val;
        $GUDS_OPT_ID = I('post.GUDS_OPT_ID');
        $info['msg'] = L('修改成功');
        if (M('ms_ord_guds_opt', 'tb_')->where('ORD_ID = "' . $ORD_ID . '" and GUDS_OPT_ID = "' . $GUDS_OPT_ID . '"')->data($data)->save()) {

        } else {

        }
        $this->ajaxReturn(0, $info, 1);

    }

    /**
     * 插入求购商品
     */
    public function insertguds($guds = [], $ord_id = '')
    {
        if (IS_AJAX) {
            $params = $this->_param();
            $guds = $params;
            $ord_id = $guds['ORD_ID'];
        }
        $old_guds = M('ms_ord_guds_opt', 'tb_')->where('ORD_ID = "' . $ord_id . '"')->find();
        if ($old_guds) {
            //重新上传po单，删除旧数据
            M('ms_ord_guds_opt', 'tb_')->where('ORD_ID = "' . $ord_id . '"')->delete();
        }
        $url = HOST_URL . '/index/getxchr.json';
        $result = json_decode(curl_request($url), 1);
        $ord = M('ms_ord_spl', 'tb_')->where('ORD_ID = "' . $ord_id . '"')->find();
        $guds_model = M('ms_ord_guds_opt', 'tb_');
        $total_price = 0;
        foreach ($guds['gudList'] as $gud) {
            $guds_opt = M('ms_guds_opt', 'tb_')->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_guds_opt.GUDS_ID')->where('GUDS_OPT_ID = ' . $gud['sku'])->find();
            if (empty($guds_opt)) {
                $this->error('sku:' . $gud['sku'] . '不存在');
            }
            if ($guds_opt['STD_XCHR_KIND_CD'] == 'N000590100') {
                $rate = 1 / ($result ['data'] ['usdXchrAmt'] / $result ['data'] ['rmbXchrAmt']);
            } elseif ($guds_opt['STD_XCHR_KIND_CD'] == 'N000590400') {
                $rate = $result ['data'] ['rmbXchrAmtJpy'];
            } elseif ($guds_opt['STD_XCHR_KIND_CD'] == 'N000590300') {
                $rate = 1;
            } else {
                $rate = $result ['data'] ['rmbXchrAmt'];
            }

            $data = [];
            $data['CUST_ID'] = $ord['CUST_ID'];
            $data['ORD_ID'] = $ord['ORD_ID'];
            $data['SLLR_ID'] = $guds_opt['SLLR_ID'];
            $data['GUDS_ID'] = $guds_opt['GUDS_ID'];
            $data['GUDS_OPT_ID'] = $gud['sku'];
            $data['ORD_GUDS_QTY'] = $gud['num'];
            $data['ORD_GUDS_SALE_PRC'] = $gud['price'];
            $data['RMB_PRICE'] = $gud['price'];
            $data['ORD_GUDS_CNS_NM'] = $guds_opt['GUDS_NM'];
            $data['ORD_GUDS_KOR_NM'] = $guds_opt['GUDS_NM'];
            $data['ORD_GUDS_ORG_RMBP'] = $guds_opt['GUDS_OPT_ORG_PRC'] / $rate;
            $data['SYS_REG_DTTM'] = date('Y-m-d H:i:s');
            $data['SYS_CHG_DTTM'] = date('Y-m-d H:i:s');
            $data['updated_time'] = date('Y-m-d H:i:s');
            $total_price += $gud['num'] * $gud['price'];
            $guds_model->data($data)->add();
        }
        $ord_data = [];
        $ord_data['DISCOUNT_MN'] = $guds['discount'];
        $ord_data['DLV_AMT'] = $guds['dlvamount'];
        $ord_data['PO_SUM_AMT'] = sprintf('%.2f', $total_price);
        $ord_data['TARIFF'] = $guds['tax'];
        $ord_data['STD_XCHR_AMT'] = 1;
        $ord_data['OPTION_MODE_CD'] = 'N000550200';
        M('ms_ord_spl', 'tb_')->where('ORD_ID = "' . $ord_id . '"')->data($ord_data)->save();
        if (IS_AJAX) {
            $info['msg'] = L('PO上传成功');
            $log = A('Log');
            $log->index($ord_id, 'N000550200', '编辑PO成功');
            $this->ajaxReturn(0, $info, 1);
        }
    }

    /**
     * 导入Excel文件
     */
    public function importExcel($fileName)
    {
        header("content-type:text/html;charset=utf-8");
        //redirect传来的文件名
        //文件路径
        $filePath = '/opt/b5c-disk/doc/' . $fileName;
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }

        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
//         (string)$PHPExcel->getActiveSheet()->getCell("C5")->getValue();
        //从第二行开始插入,第一行是列名
        $guds = [];
        for ($currentRow = 9; $currentRow <= $allRow; $currentRow++) {
            //获取B列的值
            $sku = (string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue();
            if ($sku) {
                $temp['sku'] = $sku;
                $temp['price'] = (string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue();
                $temp['num'] = (string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue();
                $guds['gudList'][] = $temp;
            }
        }
        $guds['discount'] = (string)$PHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $guds['dlvamount'] = (string)$PHPExcel->getActiveSheet()->getCell("C6")->getValue();
        $guds['tax'] = (string)$PHPExcel->getActiveSheet()->getCell("E6")->getValue();
        return $guds;
    }


    function exportorder()
    {
        session_write_close();
        //导出Excel
        $xlsName = "订单";


        $xlsCell = array(
//            array('id','平台'),
            'CUST_ID' => 'B5C用户ID',
            'CD_VAL' => '上海负责人',
            'ORD_TYPE_CD' => '订单类型',
            'SYS_REG_DTTM' => '下单时间',
            'PAY_DTTM' => '付款时间',
            'SUBSCRIBE_TIME' => '发货时间',
            'ORD_ID' => 'b5c订单号',
            'PAY_ID' => 'b5c支付号',
            'DELIVERY_WAREHOUSE' => 'SKU所在地',
            'THIRD_ORDER_ID' => '第三方订单号',
            'GUDS_NM' => '商品标题',
            'GUDS_OPT_ID' => '商品编码(货号）',
            'ORD_GUDS_QTY' => '数量',
            'RMB_PRICE' => '商品单价',
            'total_price' => '商品小计',
            'DLV_AMT' => '运费',
            'TARIFF' => '税费',
            'DISCOUNT_MN' => '优惠金额',
            'ord_price' => '总价',
            'ORD_CUST_NM' => '客户姓名',
            'BUYER_NM' => '买家姓名',
            'ORD_CUST_CP_NO' => '电话',
            'REC_ID_CARD' => '身份证号码',
            'ADPR_ADDR' => '地址',
            'TRACKING_NUMBER' => '物流单号',
            'SHIPPING_MSG' => '用户备注',
            'REMARK_MSG' => '运营备注',
            'ORD_STAT_CD' => '状态',
            'POST_CD' => '邮编',
        );
        $model = M('ms_ord', 'tb_');
        $model->join('left join tb_ms_ord_package on tb_ms_ord_package.ORD_ID = tb_ms_ord.ORD_ID')->join('left join tb_ms_cust on tb_ms_cust.CUST_ID = tb_ms_ord.CUST_ID')->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_cust.SH_SALER')->join('left join tb_ms_ord_guds_opt on tb_ms_ord.ORD_ID = tb_ms_ord_guds_opt.ORD_ID')->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_ord_guds_opt.GUDS_ID');
        $where = json_decode(htmlspecialchars_decode(decode(I('get.where'))), 1);
        $order = json_decode(htmlspecialchars_decode(I('get.order')), 1);
        $orders = str_replace('\'', '', I('get.lord'));
        if ($orders) {
            $where['tb_ms_ord.ORD_ID'] = array('in', $orders);
        }
        $out_str = I('get.out');
        if (!empty($out_str)) {
            $out_arr = explode(',', $out_str);
            $where['tb_ms_ord.ORD_ID'] = array('in', $out_arr);
        }

        $xlsData = $model->field('
            tb_ms_ord.CUST_ID,
            tb_ms_cmn_cd.CD_VAL,
            tb_ms_ord.SYS_REG_DTTM,
            tb_ms_ord.PAY_DTTM,
            tb_ms_ord_package.SUBSCRIBE_TIME,
            tb_ms_ord.ORD_ID,
            tb_ms_ord.PAY_ID,            
            tb_ms_ord.THIRD_ORDER_ID,
            tb_ms_guds.GUDS_NM,
            tb_ms_ord_guds_opt.GUDS_OPT_ID,
            tb_ms_ord_guds_opt.ORD_GUDS_QTY,
            tb_ms_ord_guds_opt.RMB_PRICE,
            tb_ms_ord_guds_opt.RMB_PRICE*tb_ms_ord_guds_opt.ORD_GUDS_QTY as total_price,
            tb_ms_ord.DLV_AMT,
            tb_ms_ord.TARIFF,
            tb_ms_ord.DISCOUNT_MN,
            tb_ms_ord.ORD_CUST_NM,
            tb_ms_ord.BUYER_NM,
            tb_ms_ord.ORD_CUST_CP_NO,
            tb_ms_ord.REC_ID_CARD,
            tb_ms_ord.ADPR_ADDR,
            tb_ms_ord_package.TRACKING_NUMBER,
            tb_ms_ord.ORD_STAT_CD,
            tb_ms_ord.POST_CD,
            tb_ms_ord.DELIVERY_WAREHOUSE,
            tb_ms_ord.REMARK_MSG,
            tb_ms_ord.SHIPPING_MSG,
            tb_ms_ord.ORD_TYPE_CD
        ')->where($where)->order($order)->select();
        $warehouse_code_arr = B2bModel::get_code_cd('N00068');
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['ORD_CUST_CP_NO'] = ' ' . $v['ORD_CUST_CP_NO'];
            $xlsData[$k]['POST_CD'] = ' ' . $v['POST_CD'];
            $xlsData[$k]['DELIVERY_WAREHOUSE'] = $warehouse_code_arr[$v['DELIVERY_WAREHOUSE']]['CD_VAL'];
            $xlsData[$k]['REC_ID_CARD'] = (string)$v['REC_ID_CARD'] . "\t";
            $xlsData[$k]['TRACKING_NUMBER'] = (string)$v['TRACKING_NUMBER'] . "\t";
            $xlsData[$k]['THIRD_ORDER_ID'] = (string)$v['THIRD_ORDER_ID'] . "\t";
            if ($v['ORD_TYPE_CD'] == 'N000620400') {
                if ($v['DELIVERY_WAREHOUSE'] == 'N000680100') {
                    $xlsData[$k]['ORD_TYPE_CD'] = '现货订单';
                } else if ($v['DELIVERY_WAREHOUSE'] == 'N000680200') {
                    $xlsData[$k]['ORD_TYPE_CD'] = '直邮订单';
                } else if ($v['DELIVERY_WAREHOUSE'] == 'N000680300') {
                    $xlsData[$k]['ORD_TYPE_CD'] = '保税订单';
                }
            } else if ($v['ORD_TYPE_CD'] == 'N000620100') {
                $xlsData[$k]['ORD_TYPE_CD'] = '大宗订单';
            }
            $xlsData[$k]['ORD_STAT_CD'] = L($v['ORD_STAT_CD']);
            if (!$v['ord_prize']) {
                $xlsData[$k]['ord_price'] = $v['total_price'] + $v['DLV_AMT'] + $v['TARIFF'] - $v['DISCOUNT_MN'];
            }
        }
        $this->exportExcel_new_self($xlsName, $xlsCell, $xlsData, 0);
    }

    public function exportExcel_new_self($expTitle, $expCellName, $expTableData)
    {
        $cell = array(
            0 => 'CUST_ID',
            1 => 'CD_VAL',
            2 => 'ORD_TYPE_CD',
            3 => 'SYS_REG_DTTM',
            4 => 'PAY_DTTM',
            5 => 'SUBSCRIBE_TIME',
            6 => 'ORD_ID',
            7 => 'PAY_ID',
            8 => 'DELIVERY_WAREHOUSE',// sku 所在地
            9 => 'THIRD_ORDER_ID',
            10 => 'GUDS_NM',
            11 => 'GUDS_OPT_ID',
            12 => 'ORD_GUDS_QTY',
            13 => 'RMB_PRICE',
            14 => 'total_price',
            15 => 'DLV_AMT',
            16 => 'TARIFF',
            17 => 'DISCOUNT_MN',
            18 => 'ord_price',
            19 => 'ORD_CUST_NM',
            20 => 'BUYER_NM',
            21 => 'ORD_CUST_CP_NO',
            22 => 'REC_ID_CARD',
            23 => 'ADPR_ADDR',
            24 => 'TRACKING_NUMBER',
            25 => 'REMARK_MSG',
            26 => 'ORD_STAT_CD',
            27 => 'POST_CD',
        );
        $xlsTitle = $expTitle;
        $fileName = $expTitle . date('_YmdHis');
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$cell[$i]]);
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), $expTableData[$i][$cell[$j]]);
            }
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

//订单导入方法
    function exportorder_self()
    {
        //导出Excel
        $xlsName = "订单";
        $xlsCell = array(
            array('ORDER_ID', '第三方订单ID'),
            array('ORDER_NO', '第三方订单编号'),
            array('merchant_ID', '店铺'),
            array('SHIPPING_DELIVERY_COMPANY', '物流公司'),
            array('SHIPPING_TRACKING_CODE', '物流单号'),
            array('ITEM_NAME', '商品标题'),
            array('B5C_SKU_ID', 'B5C sku ID'),
            array('SKU_ID', '第三方SKU编号'),
            array('ORDER_TIME', '下单时间'),
            array('ORDER_PAY_TIME', '付款时间'),
            array('SHIPPING_TIME', '发货时间'),
            array('SKU_MESSAGE', '商品SKU属性'),
            array('ADDRESS_USER_NAME', '收货人姓名'),
            array('ADDRESS_USER_PHONE', '收货人电话'),
            array('RECEIVER_TEL', '收货人固话'),
            array('ADDRESS_USER_ADDRESS_MSG', '收货人地址'),
            array('ADDRESS_USER_ADDRESS3', '收货人地址2'),
            array('ADDRESS_USER_POST_CODE', '邮编'),
            array('BUYER_MOBILE', '买家手机'),
            array('BUYER_TEL', '买家固话'),
            array('ITEM_COUNT', '数量'),
            array('ITEM_PRICE', '单价'),
            array('REMARK_MSG', '备注'),
        );
        $model = M('op_order', 'tb_');
        $where = json_decode(htmlspecialchars_decode(decode(I('get.where'))), 1);
        $order = json_decode(htmlspecialchars_decode(I('get.order')), 1);
        $out_str = I('get.out');
        if (!empty($out_str)) {
            $out_arr = explode(',', $out_str);
            $where['tb_op_order.ORDER_NO'] = array('in', $out_arr);
        }
        $boxtype = '';
        if (I('get.boxtype')) {
            $boxtype = I('get.boxtype');
            $wherenum = '';
            if ($boxtype == 1) {
                $wherenum = ' and tb_op_order_guds.ITEM_COUNT = 1';
            } elseif ($boxtype == 2) {
                $wherenum = ' and tb_op_order_guds.ITEM_COUNT >1';
            }
            $where['BWC_ORDER_STATUS'] = 'N000550400';
            $c = $model->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID')
                ->where($where)->where('tb_op_order_guds.ID is not null and tb_op_order.ADDRESS_USER_ADDRESS3 is not null' . $wherenum)
                ->field('count("tb_op_order.ID") as c,tb_op_order.ADDRESS_USER_ADDRESS3,tb_op_order_guds.ITEM_COUNT')
                ->group('tb_op_order.ADDRESS_USER_ADDRESS3')->select();
            $arr = [];
            foreach ($c as $v) {
                if ($v['c'] == 1) {
                    $arr[] = "'" . $v['ADDRESS_USER_ADDRESS3'] . "'";
                }
            }
            $a = implode(',', $arr);
            if ($boxtype == 1 || $boxtype == 2) {
                $boxwhere = 'tb_op_order.ADDRESS_USER_ADDRESS3 IN (' . $a . ') and tb_op_order.ADDRESS_USER_ADDRESS3 is not null' . $wherenum;
            } else {
                $boxwhere = 'tb_op_order.ADDRESS_USER_ADDRESS3 NOT IN (' . $a . ') and tb_op_order.ADDRESS_USER_ADDRESS3 is not null';
            }
        }
        $model->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID')->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID');
        $out_str = I('get.out');
        if (!empty($out_str)) {
            $out_arr = explode(',', $out_str);
            $where['tb_op_order.ORDER_NO'] = array('in', $out_arr);
        }
        if ($boxtype) {
            //tb_op_order.BWC_ORDER_STATUS
            $xlsData = $model->field('
            tb_op_order.ORDER_ID,
            tb_op_order.ORDER_TIME,       
            tb_op_order.ORDER_PAY_TIME,       
            tb_op_order.SHIPPING_TIME,     
            tb_op_order_guds.SKU_ID,
            tb_ms_store.merchant_ID,
            tb_op_order.SHIPPING_DELIVERY_COMPANY,
            tb_op_order.SHIPPING_TRACKING_CODE,
            tb_op_order_guds.ITEM_NAME,
            tb_op_order_guds.B5C_SKU_ID,
            tb_op_order_guds.SKU_MESSAGE,
            tb_op_order.ADDRESS_USER_NAME,
            tb_op_order.ADDRESS_USER_PHONE,
            tb_op_order.RECEIVER_TEL,
            tb_op_order.ADDRESS_USER_ADDRESS_MSG,
            tb_op_order.ADDRESS_USER_ADDRESS3,
            tb_op_order.ADDRESS_USER_POST_CODE,
            tb_op_order.BUYER_MOBILE,
            tb_op_order.BUYER_TEL,
            tb_op_order_guds.ITEM_COUNT,
            tb_op_order_guds.ITEM_PRICE,
            tb_op_order.REMARK_MSG,
            tb_op_order.ORDER_NO
        ')->where($where)->where($boxwhere)->order($order)->select();
        } else {
            $xlsData = $model->field('
            tb_op_order.ORDER_ID,       
            tb_op_order.ORDER_TIME,       
            tb_op_order.ORDER_PAY_TIME,       
            tb_op_order.SHIPPING_TIME,
            tb_op_order_guds.SKU_ID,
            tb_ms_store.merchant_ID,
            tb_op_order.SHIPPING_DELIVERY_COMPANY,
            tb_op_order.SHIPPING_TRACKING_CODE,
            tb_op_order_guds.ITEM_NAME,
            tb_op_order_guds.B5C_SKU_ID,
            tb_op_order_guds.SKU_MESSAGE,
            tb_op_order.ADDRESS_USER_NAME,
            tb_op_order.ADDRESS_USER_PHONE,
            tb_op_order.RECEIVER_TEL,
            tb_op_order.ADDRESS_USER_ADDRESS_MSG,
            tb_op_order.ADDRESS_USER_ADDRESS3,
            tb_op_order.ADDRESS_USER_POST_CODE,
            tb_op_order.BUYER_MOBILE,
            tb_op_order.BUYER_TEL,
            tb_op_order_guds.ITEM_COUNT,
            tb_op_order_guds.ITEM_PRICE,
            tb_op_order.REMARK_MSG,
            tb_op_order.ORDER_NO
        ')->where($where)->order($order)->select();
        }
        foreach ($xlsData as $key => $val) {
            $xlsData[$key]['ADDRESS_USER_POST_CODE'] = ' ' . $val['ADDRESS_USER_POST_CODE'];
            if ($val['B5C_SKU_ID']) {
                $spu = substr($val['B5C_SKU_ID'], 0, 8);
                $guds_name = M('ms_guds', 'tb_')->join('left join tb_ms_guds_opt on tb_ms_guds_opt.GUDS_ID = tb_ms_guds.MAIN_GUDS_ID')->field('tb_ms_guds.GUDS_NM,tb_ms_guds_opt.GUDS_OPT_VAL_MPNG')->where('tb_ms_guds.MAIN_GUDS_ID= "' . $spu . '" and tb_ms_guds.LANGUAGE = "N000920100"')->find();
                $xlsData[$key]['ITEM_NAME'] = $guds_name['GUDS_NM'];
                $opt_val = explode(';', $guds_name['GUDS_OPT_VAL_MPNG']);
                foreach ($opt_val as $v) {
                    $val_str = '';
                    $o = explode(':', $v);
                    $model = M('ms_opt', 'tb_');
                    $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                    if (empty($opt_val_str)) {
                        $val_str = L('标配');
                    } elseif ($opt_val_str['OPT_ID'] == '8000') {
                        $val_str = L('标配');
                    } elseif ($opt_val_str['OPT_ID'] != '8000') {
                        $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                    }
                    $xlsData[$key]['SKU_MESSAGE'] = $val_str;
                }
            }
        }
        $this->exportExcel_self($xlsName, $xlsCell, $xlsData);
    }
    public function change_out_stock_data($expCellName)
    {
        $newData = []; $newDataList = [];
        foreach ($expCellName as $key => $value) {
            $newData = [];
            $newData[] = $value['field_name'];
            $newData[] = $value['name'];
            $newDataList[] = $newData;
        }
        return $newDataList;
    }

    // 已出库导出
    public function exportExcel_stock_out($xlsTitle, $expCellName, $expTableData, $width = [])
    {
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $expCellName = $this->change_out_stock_data($expCellName);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->freezePane('A2');
        $objPHPExcel->getDefaultStyle()->getFont()->setName('微软雅黑');
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ');
        for ($i = 0; $i < $cellNum; $i++) { // 行宽自动调整
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
            if (!empty($width)) {
                if ($width['type'] == 'auto_size') {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
                }
                if ($width['size']) {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setWidth($width['size'][$cellName[$i]]);
                }
            }
        }
        //$to_string_vals = ['orderTime','orderPayTime','shippingTime', 'trackingNumber', 'addressUserPhone','receiverTel', 'orderNo', 'b5cSkuId', 'skuId'];
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                if (isset($expTableData[$i][$expCellName[$j][0]])) { // 对长数字字符串以文本形式来显示科学计数法
                    $val_data = $expTableData[$i][$expCellName[$j][0]];
                    $val_data = (string)$val_data;
                    $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);
                    /*if (in_array($expCellName[$j][0], $to_string_vals)) {

                    }*/
                }
            }
        }
        $fileName = $xlsTitle . date('_YmdHis');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        trace(time(), 'order_excel_end');
        exit;
    }

    public function exportExcel_self($expTitle, $expCellName, $expTableData, $width = [])
    {
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD','CE','CF','CG','CH','CI');
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
            if (!empty($width)) {
                if ($width['type'] == 'auto_size') {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
                }
                if ($width['size']) {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setWidth($width['size'][$cellName[$i]]);
                }
            }
        }
        $to_string_vals = ['ORDER_ID','ORDER_NO','ADDRESS_USER_PHONE', 'ADDRESS_USER_POST_CODE', 'RECEIVER_TEL'];
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                //  0 也显示
                if (isset($expTableData[$i][$expCellName[$j][0]])) { // 对长数字字符串以文本形式来显示科学计数法
                    if (0 == $j || 1 == $j || 17 == $j) {
                        $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $expTableData[$i][$expCellName[$j][0]], PHPExcel_Cell_DataType::TYPE_STRING);
                    } else {
                        $val_data = (string)$expTableData[$i][$expCellName[$j][0]];
                        if (substr($val_data, 0, 1) == '=') {
                            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);
                        } else {
                            if (in_array($expCellName[$j][0], $to_string_vals)) {
                                $val_data = (string)$val_data;
                            }
                            if ($expCellName[$j][0] == 'ADDRESS_USER_POST_CODE') {
                                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);
                            } else {
                                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                        }
                    }
                }
            }
        }
        $xlsTitle = $expTitle;
        $fileName = $expTitle . date('_YmdHis');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        trace(time(), 'order_excel_end');
        exit;
    }

    public function exportExcel($expTitle, $expCellName, $expTableData, $type = 0)
    {
        ini_set('memory_limit', '256M');
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $_SESSION['account'] . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        // 组装为二维数组
        if (count($expTableData) == count($expTableData, 1)) {
            $expTableData = array($expTableData);
        }
        if ($type == 0) {
            $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(10);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(10);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(40);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);        // Miscellaneous glyphs, UTF-8
        } else {
            $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(30);        // Miscellaneous glyphs, UTF-8
        }
        // 设置标题
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }

        //for($i = 0; $i < $dataNum; $i++) {
//            for($j = 0; $j < $cellNum; $j++) {
//                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
//            }
//        }
        $column_index = 0;    // 控制行数据
        foreach ($this->procBigData($expTableData) as $k => $v) {
            $title_index = 0; // 控制标题对应相应的数据格
            foreach ($expCellName as $field_name => $title) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$title_index] . ($column_index + 3), $v[$title[0]]);
                $title_index++;
            }
            unset($v);
            $column_index++;
        }
        unset($expTableData);
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function exportCsv($file_name, $head, $data)
    {
        // 不限制脚本执行时间以确保导出完成
        set_time_limit(0);
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = iconv('utf-8', 'gb2312', $v);
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($data as $v) {
            foreach ($head as $i => $value) {
                $row[$i] = iconv('utf-8', 'gb2312', $v[$i]);
            }
            fputcsv($fp, $row);
        }
    }

    /**
     * 协程，实现Iterator接口，返回迭代器
     * 处理大数据数组，分批返回，降低内存消耗
     */
    public function procBigData($expTableData)
    {
        foreach ($expTableData as $key => $value) {
            echo $value;
        }
    }

    public function docreateorder()
    {
        $params = $this->_param();
        $url = HOST_URL . 'third_order/save.json';
        //校验account
        $account = M('ms_cust', 'tb_')->where('CUST_ID = ' . $params['account'])->find();
        if (empty($account)) {
            $this->ajaxReturn(0, L('用户不存在'), 0);
        }
        if ($params['transCheCd'] == 'N000680300') {
            $params['transCheCd'] = 'FISHER';
        } else {
            $params['transCheCd'] = 'STO';
        }
        $headers = array(
            "content-type: application/json"
        );

        $params_logisticsfee = [];
        $params_logisticsfee['transCheCd'] = $params['transCheCd'];
        $params_logisticsfee['adprAddr'] = $params['adprAddr'];
        $total_price = 0;
        foreach ($params['goods'] as $key => $val) {
            $params_logisticsfee['tbMsOrdGudsOptDtos'][$key]['ordGudsQty'] = $val['gudsQty'];
            $params_logisticsfee['tbMsOrdGudsOptDtos'][$key]['gudsId'] = $val['gudsId'];
            $params_logisticsfee['tbMsOrdGudsOptDtos'][$key]['gudsOptId'] = $val['skuId'];
            $params_logisticsfee['tbMsOrdGudsOptDtos'][$key]['sllrId'] = $val['sllr_id'];
            unset($params['goods'][$key]['gudsId']);
            unset($params['goods'][$key]['sllr_id']);
            $total_price += floatval($val['gudsQty']) * floatval($val['rmbPrice']);
        }
        $data_logisticsfee = json_encode($params_logisticsfee);
        $url_logisticsfee = HOST_URL . 'logistics/calculateLogisticsFee.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_logisticsfee);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_logisticsfee);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result_logisticsfe = curl_exec($ch);
        curl_close($ch);
        $result_logisticsfe = json_decode($result_logisticsfe, 1);
        if ($result_logisticsfe['code'] == '2000') {
            $dlvAmt = $result_logisticsfe['data'];
        } else {
            $dlvAmt = 0;
        }

        $tariff_params = [];
        $tariff_params['tbMsOrdGudsOptDtos'] = $params_logisticsfee['tbMsOrdGudsOptDtos'];
        $tariff_params['logisticsFee'] = $dlvAmt;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, HOST_URL . 'logistics/calculateValueAddTax.json');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tariff_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($res, 1);
        $tariff = 0;
        if ($res['code'] == 2000) {
            $tariff = $res['data'];
        }

        $arr = [];
        $arr['platform'] = 'N000830100';
        $arr['isNeedOccupy'] = true;
        $arr['orders'][0] = $params;
        $arr['orders'][0]['dlvAmt'] = $dlvAmt;
        $arr['orders'][0]['tariff'] = $tariff;
        $params['ordStatCd'] == 4 ? $arr['orders'][0]['ordStatCd'] = 'N000550200' : '';
        // type3 创建保税订单 type2 创建直邮订单 type4 现货订单
        switch ($params['type']) {
            case 2:
                // 创建直邮订单
                $arr['orders'][0]['ordStatCd'] = 'N000550300';
                break;
            case 3:
                // 创建保税订单
                $arr['orders'][0]['ordStatCd'] = 'N000550300';
                break;
            case 4:
                // 创建现货订单
                $arr['orders'][0]['ordStatCd'] = 'N000550200';
                break;
        }
        // 非协议定价用户直接是待付款N000550300，协议定价是确认中N000550100
        // 协议定价用户
        $data = json_encode($arr);
        $headers = array(
            "content-type: application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, 1);
        if ($result['code'] == '2000') {
            if (!empty($result['data']['success'])) {
                $info['msg'] = L('新建订单成功，订单号：') . $result['data']['success'][0]['b5cOrderId'];
                $log = A('Log');
                $log->index($result['data']['success'][0]['b5cOrderId'], $arr['orders'][0]['ordStatCd'], '新建订单成功');
                $this->ajaxReturn(0, $info, 1);
            } else {
                $msg = $result['data']['failed'][0]['msg'];
                $this->ajaxReturn(0, $msg, 0);
            }
        }
    }

    public function docreateorderqg()
    {
        $url = HOST_URL . 'ordspl/import.json';
        $params = $this->_param();
        $data = json_encode($params);
        $headers = array(
            "content-type: application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, 1);
        if ($result['code'] == '2000') {
            $info['msg'] = L('新建订单成功，订单号：') . $result['data'];
            $log = A('Log');
            $log->index($result['data'], 'N000550100', '新建订单成功');
            $this->ajaxReturn(0, $info, 1);
        } else {
            $this->ajaxReturn(0, $result['msg'], 0);
        }
    }

    public function calcufee()
    {
        $url = HOST_URL . 'logistics/calculateLogisticsFee.json';
        $params = $this->_param();
        $gudsid = $params['tbMsOrdGudsOptDtos'][0]['gudsId'];
        $transCheCd = $params['transCheCd'];
        if ($params['transCheCd'] == 'N000680300') {
            $params['transCheCd'] = 'FISHER';
        } else {
            $params['transCheCd'] = 'STO';
        }
        $data = json_encode($params);
        $headers = array(
            "content-type: application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, 1);
        if ($result['code'] == '2000') {
            $info['fee'] = $result['data'];
            $arr = [];
            $arr['tbMsOrdGudsOptDtos'] = $params['tbMsOrdGudsOptDtos'];
            $arr['logisticsFee'] = $info['fee'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, HOST_URL . 'logistics/calculateValueAddTax.json');
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $res = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($res, 1);
            $rate = 0;
            if ($res['code'] == 2000) {
                $rate = $res['data'];
            }
            $info['rate'] = $rate;
            $this->ajaxReturn(0, $info, 1);
        } else {
            $this->ajaxReturn(0, $result['msg'], 0);
        }

    }

    public function searchuser()
    {
        $CUST_ID = I('post.CUST_ID');
        $result = M('ms_cust', 'tb_')->where('CUST_ID = "' . $CUST_ID . '"')->find();
        if (empty($result)) {
            $this->ajaxReturn(0, L('用户不存在'), 0);
        } else {
            $info = [];
            $addressurl = HOST_URL . '/address/getall.json?custId=' . $CUST_ID;
            $address = json_decode(curl_request($addressurl), 1);
            if ($address['code'] == '2000') {
                foreach ($address['data'] as $key => $val) {
                    $info['address'][$key]['fulladdress'] = $val['addrPrvn'] . $val['addrCty'] . $val['addrDstr'] . $val['addrDtl'] . ' ' . $val['rcvrNm'] . ' ' . $val['rcvrTel'];
                    $info['address'][$key]['addrPrvn'] = $val['addrPrvn'];
                    $info['address'][$key]['addrCty'] = $val['addrCty'];
                    $info['address'][$key]['addrDstr'] = $val['addrDstr'];
                    $info['address'][$key]['addrDtl'] = $val['addrDtl'];
                    $info['address'][$key]['rcvrNm'] = $val['rcvrNm'];
                    $info['address'][$key]['rcvrTel'] = $val['rcvrTel'];
                }
            }
            $info['custNickNm'] = $result['CUST_NICK_NM'];
            // 非协议定价用户
            $info['changeprice'] = 0;
            // 协议定价用户
            if ($result['PRICE_TYPE'] == 'N000900200') {
                $info['changeprice'] = 1;
            }
            $this->ajaxReturn(0, $info, 1);
        }
    }

    public function getaddr()
    {
        $url = ADDRESS_URL;
        $result = json_decode(curl_request($url), 1);
        $parent = array_column($result ['data'], 'parent_id', 'id');
        $name = array_column($result ['data'], 'name', 'id');
        $type = array_column($result ['data'], 'type', 'id');
        $second = array_keys($type, 2);
        $third = array_keys($type, 3);
        $fourth = array_keys($type, 4);
        foreach ($fourth as $v) {
            $area [$parent [$v]] [] = $name [$v];
        }
        foreach ($third as $v) {
            if (!isset ($area [$v])) {
                $area [$v] = array('无');
            }
            $city [$parent [$v]] [] = array(
                'name' => $name [$v],
                'areaList' => $area [$v]
            );
        }

        foreach ($second as $v) {
            if (!isset ($city [$v])) {
                $city [$v] = array('无');
            }
            if ($parent [$v] == 1) {
                $show [] = array(
                    'name' => $name [$v],
                    'cityList' => $city [$v]
                );
            }
        }
        echo json_encode($show, JSON_UNESCAPED_UNICODE);
        die ();
    }

    //求购搜索商品，不区分仓库
    public function searchguds_qg()
    {
        $url = HOST_URL . '/index/getxchr.json';
        $result = json_decode(curl_request($url), 1);
        $GUDS_ID = I('post.GUDS_ID');
        $flag = I('post.flag');
        if ($flag == 1) {
            $model = D('Guds');
            $main_guds_id = M('ms_guds', 'tb_')->where('GUDS_ID = ' . $GUDS_ID)->field('MAIN_GUDS_ID as GUDS_ID')->find();
            $guds = $model->relation(true)->where('GUDS_ID = ' . $main_guds_id['GUDS_ID'])->find();
            if (empty($guds)) {
                $this->ajaxReturn(0, L('商品不存在'), 0);
                exit();
            }
        } elseif ($flag == 2) {
            $model = D('Opt');
            $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->find();
            if (empty($guds)) {
                $this->ajaxReturn(0, L('商品不存在'), 0);
                exit();
            }
            $guds_img = M('ms_guds_img', 'tb_')->where('GUDS_ID = ' . $guds['GUDS_ID'] . ' and GUDS_IMG_CD = "N000080200"')->find();
            $guds['Img']['GUDS_IMG_CDN_ADDR'] = $guds_img['GUDS_IMG_CDN_ADDR'];
            $guds['Opt'][] = $guds;
            $guds['GUDS_NM'] = $guds['Guds']['GUDS_NM'];
            $guds['STD_XCHR_KIND_CD'] = $guds['Guds']['STD_XCHR_KIND_CD'];
            $guds['DELIVERY_WAREHOUSE'] = $guds['Guds']['DELIVERY_WAREHOUSE'];
            $guds['MIN_BUY_NUM'] = $guds['Guds']['MIN_BUY_NUM'];
            $guds['MAX_BUY_NUM'] = $guds['Guds']['MAX_BUY_NUM'];
        }

        if ($guds['STD_XCHR_KIND_CD'] == 'N000590100') {
            $rate = 1 / ($result ['data'] ['usdXchrAmt'] / $result ['data'] ['rmbXchrAmt']);
        } elseif ($guds['STD_XCHR_KIND_CD'] == 'N000590400') {
            $rate = $result ['data'] ['rmbXchrAmtJpy'];
        } elseif ($guds['STD_XCHR_KIND_CD'] == 'N000590300') {
            $rate = 1;
        } else {
            $rate = $result ['data'] ['rmbXchrAmt'];
        }
        foreach ($guds['Opt'] as $key => $opt) {
            $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
            foreach ($opt_val as $v) {
                $val_str = '';
                $o = explode(':', $v);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
                $guds['opt_val'][$key]['val'] .= $val_str;
                if (isset($opt['GUDS_OPT_BELOW_SALE_PRC'])) {
                    $price = $opt['GUDS_OPT_BELOW_SALE_PRC'];
                } elseif (isset($opt['GUDS_OPT_MID_SALE_PRC'])) {
                    $price = $opt['GUDS_OPT_MID_SALE_PRC'];
                } elseif (isset($opt['GUDS_OPT_HIGH_SALE_PRC'])) {
                    $price = $opt['GUDS_OPT_HIGH_SALE_PRC'];
                }
                $guds['opt_val'][$key]['price'] = sprintf("%.2f", $price / $rate);
                $guds['opt_val'][$key]['GUDS_OPT_ID'] = $opt['GUDS_OPT_ID'];
                $guds['opt_val'][$key]['GUDS_ID'] = $opt['GUDS_ID'];
                $guds['opt_val'][$key]['SLLR_ID'] = $opt['SLLR_ID'];
            }
        }
        $guds_list = [];
        $guds_list['GUDS_NM'] = $guds['GUDS_NM'];
        $guds_list['DELIVERY_WAREHOUSE'] = $guds['DELIVERY_WAREHOUSE'];
        $guds_list['opt_val'] = $guds['opt_val'];
        $guds_list['img'] = $guds['Img']['GUDS_IMG_CDN_ADDR'];
        $guds_list['MAX_BUY_NUM'] = $guds['MAX_BUY_NUM'];
        $guds_list['MIN_BUY_NUM'] = $guds['MIN_BUY_NUM'];
        $this->ajaxReturn(0, $guds_list, 1);
    }

    public function searchguds()
    {
        $url = HOST_URL . '/index/getxchr.json';
        $result = json_decode(curl_request($url), 1);
        if (isset($result['data'])) {
            $rate = $result ['data'] ['rmbXchrAmt'];
        }
        $GUDS_ID = I('post.GUDS_ID');
        $type = I('post.type');
        $delivery = I('post.delivery');
        $WAREHOUSE = I('post.WAREHOUSE');
        $gudscount = I('post.gudscount');
        $mark = I('post.mark');
        $flag = I('post.flag');
        if ($flag == 1) {
            $model = D('Guds');
            $main_guds_id = M('ms_guds', 'tb_')->where('GUDS_ID = ' . $GUDS_ID)->field('MAIN_GUDS_ID as GUDS_ID')->find();
            $guds = $model->relation(true)->where('GUDS_ID = ' . $main_guds_id['GUDS_ID'])->find();
            if (empty($guds)) {
                $this->ajaxReturn(0, L('商品不存在'), 0);
                exit();
            }
        } elseif ($flag == 2) {
            $model = D('Opt');
            $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->find();
            if (empty($guds)) {
                $this->ajaxReturn(0, L('商品不存在'), 0);
                exit();
            }
            $guds_img = M('ms_guds_img', 'tb_')->where('GUDS_ID = ' . $guds['GUDS_ID'])->find();
            $guds['Img']['GUDS_IMG_CDN_ADDR'] = $guds_img['GUDS_IMG_CDN_ADDR'];
            $guds['Opt'][] = $guds;
            $guds['GUDS_NM'] = $guds['Guds']['GUDS_NM'];
            $guds['STD_XCHR_KIND_CD'] = $guds['Guds']['STD_XCHR_KIND_CD'];
            $guds['DELIVERY_WAREHOUSE'] = $guds['Guds']['DELIVERY_WAREHOUSE'];
            $guds['MIN_BUY_NUM'] = $guds['Guds']['MIN_BUY_NUM'];
            $guds['MAX_BUY_NUM'] = $guds['Guds']['MAX_BUY_NUM'];
        }
        if (($type == 2 && $guds['DELIVERY_WAREHOUSE'] != 'N000680200') || ($type == 3 && $guds['DELIVERY_WAREHOUSE'] != 'N000680300') || ($type == 4 && $guds['DELIVERY_WAREHOUSE'] != 'N000680100')) {
            $this->ajaxReturn(0, L('商品仓库不正确'), 0);
            exit();
        }

        if ($guds['STD_XCHR_KIND_CD'] == 'N000590100') {
            $rate = 1 / ($result ['data'] ['usdXchrAmt'] / $result ['data'] ['rmbXchrAmt']);
        } elseif ($guds['STD_XCHR_KIND_CD'] == 'N000590400') {
            $rate = $result ['data'] ['rmbXchrAmtJpy'];
        } elseif ($guds['STD_XCHR_KIND_CD'] == 'N000590300') {
            $rate = 1;
        } else {
            $rate = $result ['data'] ['rmbXchrAmt'];
        }
        //添加第二个商品 或者 已添加两个商品  并且 收货地不同 判断发货仓库
        if ((($gudscount == 1 && $mark != 1) || ($gudscount > 1)) && $guds['DELIVERY_WAREHOUSE'] != $delivery && $delivery != '') {
            $this->ajaxReturn(0, L('商品发货仓库不同，不能添加'), 0);
            exit();
        }

        foreach ($guds['Opt'] as $key => $opt) {
            $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
            foreach ($opt_val as $v) {
                $val_str = '';
                $o = explode(':', $v);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
                $guds['opt_val'][$key]['val'] .= $val_str;
                $guds['opt_val'][$key]['price'] = sprintf("%.2f", $opt['GUDS_OPT_BELOW_SALE_PRC'] / $rate);
                $guds['opt_val'][$key]['GUDS_OPT_ORG_PRC'] = sprintf("%.2f", $opt['GUDS_OPT_ORG_PRC'] / $rate);
                $guds['opt_val'][$key]['GUDS_OPT_ID'] = $opt['GUDS_OPT_ID'];
                $guds['opt_val'][$key]['GUDS_ID'] = $opt['GUDS_ID'];
                $guds['opt_val'][$key]['SLLR_ID'] = $opt['SLLR_ID'];
            }
        }
        $guds_list = [];
        $guds_list['GUDS_NM'] = $guds['GUDS_NM'];
        $guds_list['DELIVERY_WAREHOUSE'] = $guds['DELIVERY_WAREHOUSE'];
        $guds_list['opt_val'] = $guds['opt_val'];
        $guds_list['img'] = $guds['Img']['GUDS_IMG_CDN_ADDR'];
        $guds_list['MAX_BUY_NUM'] = $guds['MAX_BUY_NUM'];
        $guds_list['MIN_BUY_NUM'] = $guds['MIN_BUY_NUM'];
        $this->ajaxReturn(0, $guds_list, 1);
    }

    /**
     * 订单打印功能
     */
    public function dayin()
    {
        $params = $this->_param();
        $op = M('op_order', 'tb_');
        // 获取op_order所有订单
        $orders = $op->where('ORDER_ID in (' . $params['ords'] . ')')->field('ORDER_ID,ADDRESS_USER_NAME,PACK_NO,PACKING_NO,RELATED_ORDER,SELLER_DELIVERY_NO,ADDRESS_USER_ADDRESS3')->select();
        $new_orders = [];
        foreach ($orders as $key => $val) {
            // 循环获取订单下所有的商品
            $guds = M('op_order_guds', 'tb_')->where('ORDER_ID = "' . $val['ORDER_ID'] . '"')->find();
            $orders[$key]['ITEM_COUNT'] = $guds['ITEM_COUNT'];
            $orders[$key]['ITEM_PRICE'] = $guds['ITEM_PRICE'];
            $orders[$key]['B5C_ITEM_ID'] = $guds['B5C_ITEM_ID'];
            // 商品帮我采对应的skuid，如果不存在则使用第三方的sku信息
            if (!$guds['B5C_SKU_ID']) {
                $orders[$key]['OPTION_CODE'] = $guds['OPTION_CODE'];
            } else {
                // 如果存在则通过B5C_SKU_ID去ms_guds_opt表查询商品的关联信息
                $opt_val = M('ms_guds_opt', 'tb_')->field('GUDS_OPT_VAL_MPNG')->where('GUDS_OPT_ID= "' . $guds['B5C_SKU_ID'] . '"')->find();
                $val_str = '';
                $o = explode(':', $opt_val['GUDS_OPT_VAL_MPNG']);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
                $orders[$key]['OPTION_CODE'] = $val_str;
            }
            // 如果没有B5C对应的商品ID，则使用第三方返回的SPU信息
            if (!$guds['B5C_ITEM_ID']) {
                $orders[$key]['SLLER_ITEM_CODE'] = $guds['ITEM_NAME'];
            } else {
                // 如果有B5C对应的商品ID，则在ms_guds表中查询商品中文名
                //$guds_name = M('ms_guds','tb_')->field('GUDS_NM')->where('GUDS_ID= "'.$guds['B5C_ITEM_ID'].'"')->find();
                // $orders[$key]['SLLER_ITEM_CODE'] = $guds_name['GUDS_NM'];GUDS_CNS_NM
                // 修改为中文名称
                //$orders[$key]['SLLER_ITEM_CODE'] = $guds_name['GUDS_NM'];
            }
            if ($guds['B5C_SKU_ID']) {
                $spu = substr($guds['B5C_SKU_ID'], 0, 8);
                $guds_name = M('ms_guds', 'tb_')->field('GUDS_NM')->where('MAIN_GUDS_ID= "' . $spu . '" and LANGUAGE = "N000920100"')->find();
                $orders[$key]['SLLER_ITEM_CODE'] = $guds_name['GUDS_NM'];
            }

            if (strstr($val['ADDRESS_USER_ADDRESS3'], 'SGP') != '') {
                if (!isset($new_orders[$val['ADDRESS_USER_ADDRESS3']])) {
                    $new_orders[$val['ADDRESS_USER_ADDRESS3']]['guds'][] = $orders[$key];
                } else {
                    $new_orders[$val['ADDRESS_USER_ADDRESS3']]['guds'][] = $orders[$key];
                }
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['total_num'] += $guds['ITEM_COUNT'];
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['ADDRESS_USER_ADDRESS3'] = $val['ADDRESS_USER_ADDRESS3'];
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['PACK_NO'] = $val['PACK_NO'];
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['PACKING_NO'] = $val['PACKING_NO'];
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['SELLER_DELIVERY_NO'] = $val['SELLER_DELIVERY_NO'];
                $new_orders[$val['ADDRESS_USER_ADDRESS3']]['RELATED_ORDER'] = $val['RELATED_ORDER'];
            } else {
                if (!isset($new_orders[$key])) {
                    $new_orders[$key]['guds'][] = $orders[$key];
                } else {
                    $new_orders[$key]['guds'][] = $orders[$key];
                }
                $new_orders[$key]['total_num'] += $guds['ITEM_COUNT'];
                $new_orders[$key]['ADDRESS_USER_ADDRESS3'] = $val['ADDRESS_USER_ADDRESS3'];
                $new_orders[$key]['PACK_NO'] = $val['PACK_NO'];
                $new_orders[$key]['PACKING_NO'] = $val['PACKING_NO'];
                $new_orders[$key]['SELLER_DELIVERY_NO'] = $val['SELLER_DELIVERY_NO'];
                $new_orders[$key]['RELATED_ORDER'] = $val['RELATED_ORDER'];
            }

        }
        $order = [];
        foreach ($new_orders as $k => $v) {
            $order[] = $v;
        }
        $this->assign('orders', $order);
        $this->display('print');
    }

    public function getbarcode($barcode = '')
    {
        vendor("Barcode.Barcode");
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);
        $code = new BCGcode128();
        $code->setScale(2);
        $code->setColor($colorFront, $colorBack);
        $code->parse($barcode);
        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
        header('Content-Type: image/png');
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }

    /**
     * patch order [b2c, b2b2c]
     *
     * @param null $excel_orders
     * @param null $is_excel_type
     * @param bool $enabled_plat_cd
     *
     * @return mixed
     */
    public function patch_order($excel_orders = null, $is_excel_type = null, $enabled_plat_cd = false)
    {
        G('begin');
        set_time_limit(3600);
        LogsModel::$project_name = 'patch';
        $timestamp = microtime(true);
        $res['msg'] = '订单为空';
        $res['state'] = 4001;
        $order_json = I('order_arr');
        if ($order_json || $excel_orders) {
            if ($excel_orders) {
                $orderlist_num = count($excel_orders);
                list($order_arr_rep, $err_arrs) = $this->check_path_excel($excel_orders);
            } else {
                $order_arr_rep = json_decode(str_replace('&quot;', '"', urldecode($order_json)), true);
                $orderlist_num = count($order_arr_rep);

            }
            $where_orders['ORDER_ID'] = array('IN', array_column($order_arr_rep, 'order_id'));

            $Model = M();
            $logistics_where = $this->initWhere();
            $old_patch = 0;
            $order_self_logistic = [];
            $patch_m = new PatchModel();
            foreach ($order_arr_rep as $k => $v) {
                if (empty($v['order_id'])) {
                    unset($order_arr_rep[$k]);
                } elseif (!isset($v['warehouse']) || empty($v['warehouse'])) {
                    $err_msg['orderId'] = $v['order_id'];
                    $err_msg['msg'] = '未选择仓库';
                    unset($order_arr_rep[$k]);
                    $err_arrs[] = $err_msg;
                    Logs($err_msg, $timestamp . '-err_msg');
                }

                if (empty($v['plat_cd'])) {
                    $old_patch += 1;
                }
                $logistics_where .= " OR (tb_op_order.ORDER_ID = '" . $v['order_id'] . "' AND tb_op_order.PLAT_CD = '" . $v['plat_cd'] . "')  ";
            }

            if (0 == $old_patch) {
                list($order_arr_rep, $logistics_res_arr, $order_self_logistic) = $this->logisticsSingleCheck($order_arr_rep, $logistics_where, $Model);
            }

            //自有物流获取面单、改订单状态为待开单，不派单
            $order_log_m = new OrderLogModel();
            $Model = M();
            foreach ($order_self_logistic as $v) {
                $where_pack['ORD_ID'] = $where['ORDER_ID'] = $v['order_id'];
                $where_pack['plat_cd'] = $where['PLAT_CD'] = $v['plat_cd'];
                $save['LOGISTICS_SINGLE_STATU_CD'] = 'N002080200';
                $save['send_ord_status'] = 'N001821101';
                $save['LOGISTICS_SINGLE_ERROR_MSG'] = null;
                $save['LOGISTICS_SINGLE_UP_TIME'] = date();
                $del_res[] = $Model->table('tb_ms_ord_package')->where($where_pack)->delete();
                $res_pending[] = $Model->table('tb_op_order')->where($where)->save($save);
                $order_log_m->addLog($v['order_id'], $v['plat_cd'], '派单成功，到待开单', $save);
            }

            if (count($logistics_res_arr)) {
                $err_arrs = array_merge($logistics_res_arr, (array)$err_arrs);
            }
            list($build_data, $err_order_data, $err_no_order_arrs, $err_arrs) = OrdersModel::build_order_data_join($order_arr_rep, $enabled_plat_cd, $err_arrs);
            if (is_array($err_no_order_arrs) && count($err_no_order_arrs)) {
                $err_arrs = array_merge($err_no_order_arrs, (array)$err_arrs);
            }
            if ($build_data || $res_pending) {
                unset($res);
                /*list($build_data, $sku_err_arrs) = $this->goodsRepeat_remove($build_data);
                if ($sku_err_arrs) {
                    $err_arrs = is_array($err_arrs) ? array_merge($err_arrs, $sku_err_arrs) : $sku_err_arrs;
                }*/
                $patch_count = count($build_data);
                Logs(G('begin', 'end8', 6), $timestamp . '-arr-getAPIAct-patch-count:'.$patch_count.'-time');
                // $res = OrdersModel::fetch_data_push($build_data);
                if ($build_data) $res = OrdersModel::erpOrderDispatch($build_data);
                Logs(G('begin', 'end2', 6), $timestamp . '-arr-getAPIEnd-patch-count:'.$patch_count.'-time');
                // $Model = M();
                    if ($res['code'] == 2000) {
                    Logs(json_encode($build_data), $timestamp . '-build_data', 'patch_data');
                    Logs($res, $timestamp . '-res_ord', 'patch_data');
                    $order_keyVal_arr = array_column($order_arr_rep, 'warehouse', 'order_id');
                    if (!$is_excel_type) {
                        $Stock = new StockAction();
                        $order_arr = array_column($res['data']['success'], 'orderId');
                        $where_op['ORDER_ID'] = array('in', $order_arr);
                        $plat_form_arr = $Model->table('tb_op_order')->field('ORDER_ID,PLAT_CD')->where($where_op)->select();
                        $plat_form_arr_key = array_column($plat_form_arr, 'PLAT_CD', 'ORDER_ID');
                        $PLAT_FORM = [
                            'N000831400',
                            'N000834100',
                            'N000834200',
                            'N000834300'
                        ];
                    }
                    $order_arr_rep_key = array_column($order_arr_rep, 'send_goods_time', 'order_id');
                    $guds_msg = [];
                    foreach ($res['data']['success'] as $v) {
                        unset($save);
                        $where['ORDER_ID'] = $v['orderId'];
                        $where['PLAT_CD'] = $v['platform'];
                        // $save['WAREHOUSE'] = ($v['deliveryWarehouse']) ? $v['deliveryWarehouse'] : $order_keyVal_arr[$v['orderId']];
                        $save['SEND_ORD_TIME'] = date("Y-m-d H:i:s");
                        $save['B5C_ORDER_NO'] = $v['b5cOrderId'];
                        if (!empty($order_arr_rep_key[$v['orderId']])) $save['SHIPPING_TIME'] = $order_arr_rep_key[$v['orderId']];
                        $res_msg[] = $Model->table('tb_op_order')->where($where)->save($save);
                        // $guds_msg[] = $this->updateGudsSkuCost($v, $Model, $guds_msg);
                        $where_ms['ORD_ID'] = $v['b5cOrderId'];
                        if (!in_array($plat_form_arr_key[$v['orderId']], $PLAT_FORM) && !$is_excel_type) {
                            Logs('go_batch', $timestamp . '-go_batch');
                        }
                        $order_status_upd[] = $v['b5cOrderId'];
                        $order_upd[] = ['thr_order_id'=> $v['orderId'], 'plat_cd'=> $v['platform']];
                    }
                    if (count($res['data']['failed']) > 0) {
                        $b5cOrderId_arr = array_column($res['data']['failed'], 'b5cOrderId');
                        $where_b5c['ORD_ID'] = array('in', $b5cOrderId_arr);
//                        $OrdWarehouse_arr = $Model->table('tb_wms_batch_order')->field('ORD_ID,delivery_warehouse')->where($where_b5c)->group('ORD_ID')->select();
//                        Logs($OrdWarehouse_arr, $timestamp . '-OrdWarehouse_arr', 'patch_data');
                        foreach ($res['data']['failed'] as $k => $v) {
                            unset($save);
                            if ($v['msg'] != '订单已存在' && $v['msg'] != '保存订单异常') {
                                $where['ORDER_ID'] = $v['orderId'];
                                $where['PLAT_CD'] = $v['platform'];
                                $save['SEND_ORD_STATUS'] = 'N001820300';
                                $save['SEND_ORD_MSG'] = $v['msg'];
                                $save['SEND_ORD_TIME'] = date("Y-m-d H:i:s");
                                $SEND_ORD_TYPE_CD = $v['sendOrdTypeCd'];
                                if (!$SEND_ORD_TYPE_CD) {
                                    switch ($v['msg']) {
                                        case '库存数量不足':
                                            $SEND_ORD_TYPE_CD = 'N002030700';
                                            break;
                                        default:
                                            $SEND_ORD_TYPE_CD = 'N002030200';
                                    }
                                }
                                $save['SEND_ORD_TYPE_CD'] = $SEND_ORD_TYPE_CD;
                                $res_msg[] = $Model->table('tb_op_order')->where($where)->save($save);
                            } else {
                                if (!empty($v['b5cOrderId'])) {
                                    $where['ORDER_ID'] = $v['orderId'];
                                    $where['PLAT_CD'] = $v['platform'];
                                    $save['B5C_ORDER_NO'] = $v['b5cOrderId'];
                                    $save['SEND_ORD_STATUS'] = 'N001820200';
                                    $save['SEND_ORD_MSG'] = '重复派单';
                                    $res['data']['failed'][$k]['msg'] .= ',重复派单';
                                    $save['SEND_ORD_TIME'] = date("Y-m-d H:i:s");
                                    // $save['WAREHOUSE'] = ($v['deliveryWarehouse']) ? $v['deliveryWarehouse'] : $order_keyVal_arr[$v['orderId']];
                                    $save['SHIPPING_TIME'] = $order_arr_rep_key[$v['orderId']];
                                    $res_msg[] = $Model->table('tb_op_order')->where($where)->save($save);
                                    // $guds_msg[] = $this->updateGudsSkuCost($v, $Model, $guds_msg);
                                    $order_status_upd[] = $v['b5cOrderId'];
                                }
                            }
                            if ($v['msg'] == '系统内部错误' || $v['code'] == 40002002) {
                                // $order_arr_rep[$k]['msg'] = '网络异常';
                            }
                        }
                    }
                    if ($order_status_upd) {
                        $order_status_upd_arr['ordId'] = $order_status_upd;
                        $this->updateHairnetOrderInfo($order_status_upd);
//                        $this->updPatchStatus($order_status_upd_arr);
                    }
                    SmsMsOrdHistModel::writeMulHist($this->joinPatchLog($res['data']));
                    //派单成功后日志记录状态
                    SmsMsOrdHistModel::writeMulHist($this->joinPatchStatusLog($res['data']));
                } else {
                    Logs(json_encode($build_data), $timestamp . '-build_data', 'patch_data');
                }
                //错误提示去重整合
                $all_errors = array_merge((array)$res['data']['failed'],(array) $err_arrs);
                $failed_unique = [];
                foreach ($all_errors as $v) {
                    if (!empty($failed_unique[$v['orderId']])) {
                        $failed_unique[$v['orderId']]['msg'] .= "；".$v['msg'];
                    } else {
                        $failed_unique[$v['orderId']] = $v;
                    }
                }
                unset($all_errors);
                $failed_unique = array_values($failed_unique);

                $res_return['body']['message_orders'] = $message_orders = $failed_unique;
                $res_return['body']['orderlist_false'] = $orderlist_false = count($failed_unique);
                $res_return['body']['orderlist_success'] = $orderlist_success = count($res['data']['success']) + count($res_pending);
                $res_return['body']['orderlist_num'] = $orderlist_num = $orderlist_false + $orderlist_success;
                $res_return['body']['message_orders'] = $message_orders = $message_orders;
                $res_return['body']['success_orders'] = $order_upd;
                $this->assign('message_orders', $message_orders);
                $this->assign('orderlist_num', $orderlist_num);
                $this->assign('orderlist_false', $orderlist_false);
                $this->assign('orderlist_success', $orderlist_success);
                $html = $this->fetch('dispatch_status');
                $res['html'] = $html;
            } else {
                if ($err_arrs) {
                    //错误提示去重整合
                    $err_arrs_unique = [];
                    foreach ($err_arrs as $v) {
                        if (!empty($err_arrs_unique[$v['orderId']])) {
                            $err_arrs_unique[$v['orderId']]['msg'] .= "；".$v['msg'];
                        } else {
                            $err_arrs_unique[$v['orderId']] = $v;
                        }
                    }
                    unset($err_arrs);
                    $err_arrs_unique = array_values($err_arrs_unique);
                    $res['body']['orderlist_false'] = count($err_arrs_unique);    // 错误数
                    $res['body']['orderlist_success'] = $orderlist_success = 0;
                    $res['body']['orderlist_num'] = $orderlist_num;   // 总数
                    $res['body']['message_orders'] = $err_arrs_unique;
                    if (1 == $res['body']['orderlist_num']) $res['msg'] = $res['body']['message_orders'][0]['msg'];
                } else {
                    Logs($err_order_data, 'err_order_data');
                    $res['info'] = $res['msg'] = '无对应下发仓库或信息,或库存无信息';
                    $res['status'] = $res['state'] = 4002;
                }
            }
            //释放订单锁
            RedisLock::unlock();
        }
        if ($enabled_plat_cd && $res_return) {
            return $res_return;
        }
        if ($is_excel_type) {
            return $res;
        }
        echo json_encode($res);
    }

    private function updateHairnetOrderInfo(array $b5c_order_ids)
    {
        $this->Model = new Model();
        $where['tb_op_order.B5C_ORDER_NO'] = ['IN', $b5c_order_ids];
        $db_res = $this->Model->table('(tb_op_order,tb_ms_order_warehouse_callback)')
            ->field('tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_ms_order_warehouse_callback.weight,tb_ms_order_warehouse_callback.order_status')
            ->where($where)
            ->where('tb_op_order.ORDER_ID = tb_ms_order_warehouse_callback.order_id 
            AND tb_op_order.PLAT_CD = tb_ms_order_warehouse_callback.plat_cd', null, true)
            ->select();
        $OrderLogModel = new OrderLogModel();
        $LogService = new LogService();
        foreach ($db_res as $key => $value) {
            list($save_res, $msg) = $this->updateOrderInfo($value['ORDER_ID'],
                $value['PLAT_CD'],
                $value['weight'],
                $LogService);
            if (false === $save_res) {
                $msg = L('更新失败');
            }
            $OrderLogModel->addLog($value['ORDER_ID'], $value['PLAT_CD'], '更新发网信息' . $msg);
            Logs([$value, $save_res], __CLASS__, __FUNCTION__);
        }
    }

    /**
     * @param $order_id
     * @param $plat_cd
     * @param $weighing
     * @param LogService $LogService
     *
     * @return array
     */
    private function updateOrderInfo($order_id, $plat_cd, $weighing, LogService $LogService)
    {
        $where_op_order['THIRD_ORDER_ID'] = $order_id;
        $where_op_order['PLAT_FORM'] = $plat_cd;
        $save_op_order['WHOLE_STATUS_CD'] = 'N001820800';
        $save_op_order['weighing'] = $weighing;
        $table = 'tb_ms_ord';
        $msg = $LogService->getUpdateMessage($table, $where_op_order, $save_op_order);
        return [$this->Model->table($table)
            ->where($where_op_order)
            ->save($save_op_order), $msg];
    }

    private function initWhere()
    {
        return '1 != 1';
    }

    /**
     * @param      $wheres
     * @param null $Model
     *
     * @return mixed
     */
    public function logisticsSingleCheck($order_arr_rep, $wheres, $Model)
    {
        $res_arr = $Model->table('tb_op_order')
            ->field('tb_op_order.ID,
            tb_op_order.ORDER_ID,
            tb_op_order.PLAT_CD,
            tb_op_order.SURFACE_WAY_GET_CD,
            tb_op_order.B5C_ORDER_NO,
            tb_op_order.BWC_ORDER_STATUS,
            tb_op_order.SEND_ORD_STATUS,
            tb_op_order.LOGISTICS_SINGLE_STATU_CD,
            concat(tb_op_order.ORDER_ID,tb_op_order.PLAT_CD) AS ORDER_KEY,
            tb_op_order.warehouse,
            tb_op_order.logistic_cd,
            tb_op_order.logistic_model_id,
            tb_ms_ord_package.TRACKING_NUMBER')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->where($wheres)
            ->select();
        $res_arr_logistics = array_column($res_arr, 'LOGISTICS_SINGLE_STATU_CD', 'ORDER_KEY');
        $TRACKING_NUMBER = array_column($res_arr, 'TRACKING_NUMBER', 'ORDER_KEY');
        $res_arr_surface = array_column($res_arr, 'SURFACE_WAY_GET_CD', 'ORDER_KEY');
        $res_arr_b5c_order = array_column($res_arr, 'B5C_ORDER_NO', 'ORDER_KEY');
        $res_arr = array_column_key($res_arr, 'ORDER_KEY');
        $res_up_arr = DataModel::upDimension($res_arr, 'ORDER_KEY');
        $err_msg_arr = [];
        $order_self_logistic = [];
        $is_patch_status = ['N000550400', 'N000550500', 'N000550600', 'N000550800', 'N000551004'];#派单新增处理中状态
        $is_patch_snd_status = ['N001820100', 'N001821000', 'N001820300'];
        $patch_m = D('Oms/Patch');
        foreach ($order_arr_rep as $k => $v) {
            $order_key = $v['order_id'] . $v['plat_cd'];
            if ($res_arr[$order_key]['SURFACE_WAY_GET_CD'] != 'N002010200' && $patch_m->isSelfLogistic(['warehouse' => $res_arr[$order_key]['warehouse'], 'logistic_cd' => $res_arr[$order_key]['logistic_cd'], 'logistic_model_id' => $res_arr[$order_key]['logistic_model_id']])) {
                unset($order_arr_rep[$k]);
                $order_self_logistic[] = $v;
                continue;
            }
            //11079 派单流程调整-派单逻辑调整
            /*if ($res_arr_surface[$v['order_id'] . $v['plat_cd']] == 'N002010100' && $res_arr_logistics[$v['order_id'] . $v['plat_cd']] != 'N002080400') {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = L('面单获取方式为一键获取时，运单号不能为空');
                $err_msg_arr[] = $err_msg;
                unset($order_arr_rep[$k]);
            }*/
            //派单逻辑完善派单 批量派单 批量派单+获取单号都要加面单为一键获取 运单号不能有值的逻辑 -- 暂时关闭限制
            /*if ($res_arr_surface[$v['order_id'] . $v['plat_cd']] == 'N002010100' && $TRACKING_NUMBER[$v['order_id'] . $v['plat_cd']]) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = L('面单获取方式为一键获取 运单号不能有值');面单获取方式为一键获取 运单号不能有值
                $err_msg_arr[] = $err_msg;
                unset($order_arr_rep[$k]);
            }*/
            if ($res_arr_b5c_order[$v['order_id'] . $v['plat_cd']]) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = L('已生成 ERP 订单，派单失败');
                $err_msg_arr[] = $err_msg;
                unset($order_arr_rep[$k]);
            }
            $err_ord_str = $err_str = '';
            foreach ($res_up_arr[$v['order_id'] . $v['plat_cd']] as $up_key => $up_value) {
                switch ($up_key) {
                    case 'warehouse':
                        if (empty($up_value)) {
                            $err_str .= '下发仓库' . ' ';
                        }
                        break;
                    case 'logistic_cd':
                        if (empty($up_value)) {
                            $err_str .= '物流公司' . ' ';
                        }
                        break;
                    case 'logistic_model_id':
                        if (empty($up_value)) {
                            $err_str .= '物流方式' . ' ';
                        }
                        break;
                    case 'SURFACE_WAY_GET_CD':
                        if (empty($up_value)) {
                            $err_str .= '面单获取方式' . ' ';
                        }
                        break;
                    case 'BWC_ORDER_STATUS':
                        if (!in_array($up_value, $is_patch_status)) {
                            $err_ord_str = '订单不在待发货/待收货/已收货/交易成功状态，派单失败';
                        }
                        break;
                    case 'SEND_ORD_STATUS':
                        if (!in_array($up_value, $is_patch_snd_status)) {
                            $err_ord_str = '订单不在待派单状态，派单失败';
                        }
                        break;
                }
            }
            if (!empty($err_str)) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = $err_str . L('不能为空，派单失败');
                $err_msg_arr[] = $err_msg;
                unset($order_arr_rep[$k]);
            }
            if (!empty($err_ord_str)) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = $err_ord_str;
                $err_msg_arr[] = $err_msg;
                unset($order_arr_rep[$k]);
            }
        }
        return array($order_arr_rep, $err_msg_arr, $order_self_logistic);
    }

    /**
     * @param $data_arr
     *
     * @return array
     */
    public function check_path_excel($data_arr)
    {
        $code_arr = B2bModel::get_code_cd('N00068');
        $code_arr_cd = array_column($code_arr, 'CD');
        $where_warehouse = '1 != 1';
        foreach ($data_arr as $value) {
            $where_warehouse .= sprintf(" OR ( tb_op_order.ORDER_ID = '%s' AND tb_op_order.PLAT_CD = '%s' ) ", $value['order_id'], $value['plat_cd']);
        }
        $Model = M();
        $db_warehouse = $Model->table('tb_op_order')
            ->field('tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_op_order.WAREHOUSE,CONCAT(tb_op_order.ORDER_ID,tb_op_order.PLAT_CD) AS ORDER_KEY,tb_op_order_extend.btc_order_type_cd')
            ->join('LEFT JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id 
	AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd')
            ->where($where_warehouse)
            ->select();
        $db_warehouse_key_val = array_column($db_warehouse, 'WAREHOUSE', 'ORDER_KEY');
        $db_btc_order_type_key_val = array_column($db_warehouse, 'btc_order_type_cd', 'ORDER_KEY');
        $locktime = count($data_arr) > 5 ? count($data_arr) : 5;
        foreach ($data_arr as $v) {
            //订单锁获取
            if (!RedisLock::lock($v['order_id'] . '_' . $v['plat_cd'], $locktime)) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = '派单失败：订单锁获取失败';
                $err_arrs[] = $err_msg;
                continue;
            }
            if ($db_btc_order_type_key_val[$v['order_id'] . $v['plat_cd']] == 'N003720003') {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = '代销售订单禁止派单操作';
                $err_arrs[] = $err_msg;
                continue;
            }
            if ($db_warehouse_key_val[$v['order_id'] . $v['plat_cd']] != $v['warehouse']) {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = '派单失败：网络延迟，请重试';
                $err_arrs[] = $err_msg;
            } elseif (in_array($v['warehouse'], $code_arr_cd)) {
                $data_new_arr[] = $v;
            } else {
                $err_msg['orderId'] = $v['order_id'];
                $err_msg['msg'] = '仓库异常';
                $err_arrs[] = $err_msg;
            }
        }
        return [$data_new_arr, $err_arrs];
    }

    /**
     * update order type in [] from b2b2c, b2c
     */
    public static function send_out_goods()
    {
        $Model = M();
        $res_arr = $Model->table('tb_ms_ord AS t1, tb_wms_batch_order AS t2,tb_op_order AS t3,tb_wms_warehouse AS t4')->field('t1.ORD_ID')->where("t1.ORD_ID = t2.ORD_ID AND t1.THIRD_ORDER_ID = t3.ORDER_ID AND t3.BWC_ORDER_STATUS IN ('N000550500','N000550600','N000550800') AND t3.CHILD_ORDER_ID IS NULL AND (t2.use_type = 1 OR (t2.use_type = 0 AND t2.oversole_num = 0)) AND t3.PLAT_CD NOT IN ('N000831400','N000834100','N000834200','N000834300') AND t2.delivery_warehouse IS NOT NULL AND t2.delivery_warehouse != '' AND t1.reset_num < 3 AND t4.CD = t2.delivery_warehouse AND t4.system_docking = 'N002040200' ")->group('t2.ORD_ID')->order('t1.reset_num asc,t1.ESTM_RCP_REQ_DT asc')->limit(80)->select();
        Logs($res_arr, 'res_arr');
        if ($res_arr) {
            $ord_id_arr_all = array_column($res_arr, 'ORD_ID');

            //过滤已经发起退款的订单自动发货
            if (!empty($ord_id_arr_all)) {
                $ord_id_arr_all = (new OmsAfterSaleService())->filterOrderRefund($ord_id_arr_all);
            }
            if (empty($ord_id_arr_all)) {
                return $out_msg_arr_all = 'filter null order';
            }

            $order_data['data']['query']['ordId'] = $ord_id_arr_all;
            $requre_arr = ApiModel::goOutBatch(json_encode($order_data, JSON_UNESCAPED_UNICODE));
            $out_msg_data = $requre_arr['data'];
            foreach ($out_msg_data as $v) {
                $out_msg_arr = $v;
                $where_op['B5C_ORDER_NO'] = $where_ord_arr['ORD_ID'] = $v['ordId'];
                $save_op_res['SHIPPING_TIME'] = $save_res['sendout_time'] = date('Y-m-d H:i:s');
                $save_res['sendout_status'] = $out_msg_arr['code'];
                $res_save[] = $Model->table('tb_ms_ord')->where($where_ord_arr)->save($save_res);
                if (200 == $out_msg_arr['code'] || 2000 == $out_msg_arr['code']) {
                    $res_op_save[] = $Model->table('tb_op_order')->where($where_op)->save($save_op_res);
                }
                $out_msg_arr_all[] = $out_msg_arr;
            }
            foreach ($res_arr as $value) {
                $where_ord_arr['ORD_ID'] = $value['ORD_ID'];
                $Model->table('tb_ms_ord')->where($where_ord_arr)->setInc('reset_num');
            }
            Logs($requre_arr, 'requre_arr');
        } else {
            $out_msg_arr_all = 'null order';
        }
        return $out_msg_arr_all;
    }

    /**
     * update order type in [] from b2b2c, b2c
     */
    public static function send_out_goods_winit()
    {
        $Model = M();
        //运单号面单获取状态 N002080900  物流公司 万邑通
        $res_arr = $Model->table('tb_ms_ord AS t1, tb_wms_batch_order AS t2,tb_op_order AS t3,tb_wms_warehouse AS t4,tb_ms_ord AS t5')->field('t1.ORD_ID,t3.ORDER_TIME,t3.ORDER_ID,t3.ORDER_NO,t3.PLAT_CD')->where("
        t1.ORD_ID = t2.ORD_ID AND t1.THIRD_ORDER_ID = t3.ORDER_ID AND t5.THIRD_ORDER_ID = t3.ORDER_ID 
        AND t3.LOGISTICS_SINGLE_STATU_CD = 'N002080900' AND t3.logistic_cd = 'N000708200' AND t3.THIRD_DELIVER_STATUS = '1' AND t5.WHOLE_STATUS_CD = 'N001820800' 
        AND t3.CHILD_ORDER_ID IS NULL AND (t2.use_type = 1 OR (t2.use_type = 0 AND t2.oversole_num = 0)) 
        AND t2.delivery_warehouse IS NOT NULL AND t2.delivery_warehouse != '' AND t1.reset_num < 3 AND t4.CD = t2.delivery_warehouse AND t4.system_docking = 'N002040200' 
        ")->group('t2.ORD_ID')->order('t1.reset_num asc,t1.ESTM_RCP_REQ_DT asc')->limit(80)->select();
        Logs($res_arr, 'res_arr');
        $plat_forms = CommonDataModel::platform();
        if ($res_arr) {
            $ord_id_arr_all = array_column($res_arr, 'ORD_ID');
            $order_id_arr_all = array_column($res_arr, 'ORDER_ID');
            $plat_cd_arr_all = array_column($res_arr, 'PLAT_CD');

            //过滤已经发起退款的订单自动发货
            if (!empty($ord_id_arr_all)) {
                $ord_id_arr_all = (new OmsAfterSaleService())->filterOrderRefund($ord_id_arr_all);
            }
            if (empty($ord_id_arr_all)) {
                return $out_msg_arr_all = 'filter null order';
            }
            foreach ($res_arr as $item) {
                $order_infos[$item['ORD_ID']] = $item;
            }
            $order_data['data']['query']['ordId'] = $ord_id_arr_all;
            $requre_arr = ApiModel::goOutBatch(json_encode($order_data, JSON_UNESCAPED_UNICODE));
            $sendout_time = date('Y-m-d H:i:s');
            $where['ORD_NO'] = ['in', $order_id_arr_all];
            $where['plat_cd'] = ['in', $plat_cd_arr_all];
            $where['ORD_HIST_HIST_CONT'] = '面单开始获取'; //推送信息给订单获取单号操作人
            $log_res = M("ms_ord_hist", "sms_")->field('ORD_NO, plat_cd, ORD_HIST_WRTR_EML')->where($where)->group('ORD_NO, plat_cd')->order('updated_time desc')->select();
            foreach ($log_res as $item) {
                $log_infos[$item['ORD_NO'] . $item['plat_cd']] = $item['ORD_HIST_WRTR_EML'];
            }
            $out_msg_data = $requre_arr['data'];
            foreach ($out_msg_data as $v) {
                $out_msg_arr = $v;
                $where_op['B5C_ORDER_NO'] = $where_ord_arr['ORD_ID'] = $v['ordId'];
                $save_op_res['SHIPPING_TIME'] = $save_res['sendout_time'] = $sendout_time;
                $save_res['sendout_status'] = $out_msg_arr['code'];
                $res_save[] = $Model->table('tb_ms_ord')->where($where_ord_arr)->save($save_res);
                $order_info = $order_infos[$v['ordId']];
                $user_ids = isset($log_infos[$order_info['ORDER_ID'] . $order_info['PLAT_CD']]) ? $log_infos[$order_info['ORDER_ID'] . $order_info['PLAT_CD']] : 'shenmo';
                if (200 == $out_msg_arr['code'] || 2000 == $out_msg_arr['code']) {
                    //订单自动出库成功向物流下单成功操作人推送企业微信卡片消息
                    $order_detail_url = ERP_URL . self::order_detail_url . $order_info['ORDER_NO'] . '&thrId=' . $order_info['ORDER_ID'] . '&platCode=' . $order_info['PLAT_CD'];
                    $message_string = ">**企业微信通知事项** 
> 你有一个订单自动出库，已成功标记发货
>TO  ：<font color=info >{$user_ids}</font> 
>订单号  ：<font color=info >{$order_info['ORDER_NO']}</font> 
>出库时间：<font color=warning >{$sendout_time}</font> 
>站点    ：<font color=info >{$plat_forms[$order_info['PLAT_CD']]}</font> 
>下单时间    ：<font color=info >{$order_info['ORDER_TIME']}</font> 
>如需查看详情，请点击：[查看详情]({$order_detail_url})";
                    $res_op_save[] = $Model->table('tb_op_order')->where($where_op)->save($save_op_res);
                } else {
                    //订单自动出库失败向物流下单成功操作人推送企业微信卡片消息
                    $message_string = ">**企业微信通知事项** 
> 你有一个订单自动出库失败，当前出库单状态异常，请尽快确认
>TO  ：<font color=info >{$user_ids}</font> 
>订单号  ：<font color=info >{$order_info['ORDER_NO']}</font> 
>出库时间：<font color=warning >{$sendout_time}</font> 
>站点    ：<font color=info >{$plat_forms[$order_info['PLAT_CD']]}</font> 
>下单时间    ：<font color=info >{$order_info['ORDER_TIME']}</font> 
>如需查看详情，请点击：[查看详情]({$order_detail_url})";
                    //只推送成功自动出库的消息
                    $wx_return_res = ApiModel::WorkWxSendMarkdownMessage($user_ids, $message_string);
                }
                $out_msg_arr_all[] = $out_msg_arr;
            }
            foreach ($res_arr as $value) {
                $where_ord_arr['ORD_ID'] = $value['ORD_ID'];
                $Model->table('tb_ms_ord')->where($where_ord_arr)->setInc('reset_num');
            }
            Logs($requre_arr, 'requre_arr');
        } else {
            $out_msg_arr_all = 'null order';
        }
        return $out_msg_arr_all;
    }

    /**
     * 退回到待派单
     */
    public static function return_to_patch()
    {
        $Model = M();
        //运单号面单获取状态 N002080800 已作废 物流公司 万邑通 待获取运单号订单
        $res_arr = $Model->table('tb_op_order AS t3,tb_ms_ord AS t5')->field('t3.ID,t3.B5C_ORDER_NO')
            ->where("t5.THIRD_ORDER_ID = t3.ORDER_ID AND t3.SEND_ORD_STATUS = 'N001820200'AND t5.WHOLE_STATUS_CD = 'N001821102'  AND t3.LOGISTICS_SINGLE_STATU_CD = 'N002080800' AND t3.logistic_cd = 'N000708200' AND t3.THIRD_DELIVER_STATUS = '7'")
            ->group('t3.ORDER_ID,t3.PLAT_CD')->order('t3.ORDER_TIME desc')->limit(50)->select();
        Logs($res_arr, 'res_arr');
        if ($res_arr) {
            $map = $data = [];
            foreach ($res_arr as $item) {
                $data[] = ['order_number' =>$item['B5C_ORDER_NO']];
                $map[$item['B5C_ORDER_NO']] = $item['ID'];
            }
            $order_data['data']['orders'] = $data;
            $requre_arr = ApiModel::return_to_patch(json_encode($order_data, JSON_UNESCAPED_UNICODE));
            $return_msg_data = $requre_arr['data']['data']['data'];
            foreach ($return_msg_data as $item) {
                if (200 == $item['code'] || 2000 == $item['code']) {
                    $id[] = $map[$item['orderId']];
                }
            }
            //订单自动退回到待派单 清空作废信息
            if (isset($id) && !empty($id)) {
                $where_op['ID'] = ['in', $id];
                $save_op_res['LOGISTICS_SINGLE_STATU_CD'] = 'N002080100';
                $res_op_save = $Model->table('tb_op_order')->where($where_op)->save($save_op_res);
            }
            Logs($requre_arr, 'requre_arr');
        } else {
            $requre_arr = 'null order';
        }
        //$wx_return_res = ApiModel::WorkWxSendWarnMessage('shenmo', '自动退回待派单' . json_encode($res_arr));
        return $requre_arr;
    }

    public static function hairnetDelivery()
    {
        $Model = M();
        /*$B2bRepository = new B2bRepository();
        $send_net_warehouse_cds = $B2bRepository->getSendNetWarehouseCds();
        $warehouce_cds = array_column($send_net_warehouse_cds, 'cd');
        $where['tb_op_order.WAREHOUSE'] = ['IN', $warehouce_cds];*/
        $where['tb_op_order.WAREHOUSE'] = 'N000688800';
        $where['tb_ms_ord.weighing'] = ['EXP', 'IS NOT NULL'];
        $where['tb_ms_ord.WHOLE_STATUS_CD'] = 'N001820800';
        $where_string = 'tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD';
        $res_arr = $Model->table('tb_op_order,tb_ms_ord')
            ->field('tb_ms_ord.ORD_ID')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_ms_ord.ORD_ID')
            ->order('tb_ms_ord.reset_num asc,tb_ms_ord.ESTM_RCP_REQ_DT asc')
            ->limit(50)
            ->select();
        Logs($res_arr, 'res_arr');
        if ($res_arr) {
            $ord_id_arr_all = array_column($res_arr, 'ORD_ID');
            $order_data['data']['query']['ordId'] = $ord_id_arr_all;
            $requre_arr = ApiModel::goOutBatch(json_encode($order_data, JSON_UNESCAPED_UNICODE));
            $out_msg_data = $requre_arr['data'];
            foreach ($out_msg_data as $v) {
                $out_msg_arr = $v;
                $where_op['B5C_ORDER_NO'] = $where_ord_arr['ORD_ID'] = $v['ordId'];
                $save_op_res['SHIPPING_TIME'] = $save_res['sendout_time'] = date('Y-m-d H:i:s');
                $save_res['sendout_status'] = $out_msg_arr['code'];
                $res_save[] = $Model->table('tb_ms_ord')->where($where_ord_arr)->save($save_res);
                if (200 == $out_msg_arr['code'] || 2000 == $out_msg_arr['code']) {
                    $res_op_save[] = $Model->table('tb_op_order')->where($where_op)->save($save_op_res);
                }
                $out_msg_arr_all[] = $out_msg_arr;
            }
            foreach ($res_arr as $value) {
                $where_ord_arr['ORD_ID'] = $value['ORD_ID'];
                $Model->table('tb_ms_ord')->where($where_ord_arr)->setInc('reset_num');
            }
            Logs($requre_arr, 'requre_arr');
        } else {
            $out_msg_arr_all = 'null order';
        }
        return $out_msg_arr_all;
    }

    /**
     * clean reset num
     */
    public function clean_reset_num()
    {
        $Model = M();
        $save['reset_num'] = 0;
        if (I('ord')) {
            $where['ORD_ID'] = I('ord');
        } else {
            $where['reset_num'] = array('GT', 0);
        }
        $res = $Model->table('tb_ms_ord')->where($where)->save($save);
        var_dump($Model->getLastSql());
    }

    private function upd_cd($c, $cd_nm)
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_VAL'] = $c;
        if (!empty($cd_nm)) {
            $where['CD_NM'] = $cd_nm;
        }
        return $Cmn_cd->where($where)->Field('CD')->count();

    }

    private function getNum($num, $isInt = false, $unsign = false)
    {
        $flg = is_numeric($num);
        $intFlg = !$isInt || $num == (int)$num;
        $unsignFlg = !$unsign || $num >= 0;
        return $flg && $intFlg && $unsignFlg ? $num : '';
    }

    private function check_orderPayTime($data, $currentRow)
    {
        if (!empty($data)) {
            if (strpos($data, '-')) {
                $time = explode('-', $data)[0];
                $res = is_numeric($time);
            } else if (strpos($data, '/')) {
                $time = explode('/', $data)[0];
                $res = is_numeric($time);
            } else {
                $res = false;
            }
        }
        return $res;
    }

    private function check_orderTime($data, $currentRow)
    {
        if (!empty($data)) {
            if (strpos($data, '-')) {
                $time = explode('-', $data)[0];
                $res = is_numeric($time);
            } else if (strpos($data, '/')) {
                $time = explode('/', $data)[0];
                $res = is_numeric($time);
            } else {
                $res = 1;
            }
            if ($res == false) {
                $check_data = '第' . $currentRow . '行的下单时间：' . $data . '有问题';
                echo '<a href="/index.php?m=orders&amp;a=orders_self"><button type="button" name="out" class="button-sky">返回</button></a>';
                echo $check_data;
                exit;
            }
        }
        return $res;
    }

    private function check_shopID($o, $platCD)
    {
        $Order = M('store', 'tb_ms_');
        $where['STORE_NAME'] = trim($o);
        $where['PLAT_CD'] = trim($platCD);
        return $Order->field('ID,store_type_cd')->where($where)->select();
    }


    private function check_orderID($o)
    {
        $Order = M('order', 'tb_op_');
        $where['ORDER_ID'] = $o;

        return $Order->where($where)->count();
    }

    private function check_skuID($s)
    {
        return (new PmsBaseModel())->table('product_sku')->where(['sku_id' => $s])->count();
    }

    private function check_e2gSkuID($s, $redis)
    {
        if ($redis && $redis->get('erp_tb_ms_drguds_opt_' . $s)) {
            return true;
        } else {
            $Order = M('opt', 'tb_ms_drguds_');
            $where['SKU_ID'] = $s;
            if ($Order->field("THRD_SKU_ID")->where($where)->select()) {
                $T = $Order->field("THRD_SKU_ID")->where($where)->find();
                return true;
            }
        }
        return false;
    }

    private function check_sku($k)
    {
        $Guds_opt = M('guds_opt', 'tb_ms_');
        $where['GUDS_OPT_ID'] = $k;
        return $Guds_opt->where($where)->count();
    }

    /*
    *$e  订单数据
    *$g  商品数据
    */

    private function check_null($e, $g, &$c = [], &$check_data)
    {

        $code_zh = [
            'PLAT_NAME' => '平台',
            'PLAT_CD' => '平台',
            'SHOP_ID' => '店铺',
            'ORDER_ID' => '第三方订单ID',
            'ORDER_NO' => '第三方订单号',
            'PAY_CURRENCY' => '币种',
            'PAY_VOUCHER_AMOUNT' => '订单总优惠金额',
            'PAY_SHIPING_PRICE' => '订单运费',
            'PAY_TOTAL_PRICE' => '订单支付总价',
            'ORDER_TIME' => '下单时间',
            'ORDER_PAY_TIME' => '付款时间',
            'ADDRESS_USER_NAME' => '收货人姓名',
            'ADDRESS_USER_PHONE' => '收货人手机',
            'RECEIVER_TEL' => '收货人电话',
            'ADDRESS_USER_ADDRESS1' => '具体地址',
            'ADDRESS_USER_COUNTRY' => '国家',
            'ADDRESS_USER_PROVINCES' => '省',
            'ADDRESS_USER_CITY' => '市',
            // 'ADDRESS_USER_ADDRESS2' => '收货地址（区县）',
            'ADDRESS_USER_ADDRESS3' => '收货地址（街道）',
            'ADDRESS_USER_ADDRESS4' => '收货地址（具体地址）',
            'SHIPPING_TYPE' => '运送方式',
            'B5C_SKU_ID' => 'SKUID',
            'SKU_ID' => '第三方商品ID',
            'ITEM_PRICE' => '订单商品销售单价',
            'ITEM_COUNT' => '商品数量',
            'BWC_ORDER_STATUS' => '状态编码',
            'order_origin' => '订单客户来源',

            //不验证
            'PAY_SETTLE_PRICE' => '订单结算价',
            'PAY_TRANSACTION_ID' => '第三方支付号',
            'out_trade_no' => '交易号2',
            'SHIPPING_DELIVERY_COMPANY' => '物流公司',
            'SHIPPING_TRACKING_CODE' => '物流单号',
            'tracking_number' => '物流单号',//导入值

            'ADDRESS_USER_POST_CODE' => '邮编',
            'SHIPPING_MSG' => '备注'
        ];

        $flip_code = [
            'PLAT_NAME',
            'PLAT_CD',
            'SHOP_ID',
            'ORDER_ID',
            'ORDER_NO',
            'PAY_CURRENCY',
            'PAY_VOUCHER_AMOUNT',
            'PAY_SHIPING_PRICE',
            'PAY_TOTAL_PRICE',
            'ORDER_TIME',
            'ORDER_PAY_TIME',
            'ADDRESS_USER_NAME',
            'ADDRESS_USER_PHONE',
            'RECEIVER_TEL',
            'ADDRESS_USER_ADDRESS1',
            'ADDRESS_USER_COUNTRY',
            'ADDRESS_USER_PROVINCES',
            'ADDRESS_USER_CITY',
            'ADDRESS_USER_ADDRESS3',
            'ADDRESS_USER_ADDRESS4',
            'SHIPPING_TYPE',
            'B5C_SKU_ID',
            'SKU_ID',
            'ITEM_PRICE',
            'ITEM_COUNT',
            'BWC_ORDER_STATUS'
        ];

        $order_source_map = $this->getSourceStatusMap();
        $order_source_val_map = array_values($order_source_map);

        $gshopper_plat_cds = CodeModel::getSiteCodeArr(["N002620800"]); # shopper平台数据集合
        $gshopper_plat_cds = array_column($gshopper_plat_cds,'CD');
        foreach ($e as $key => $val) {

            foreach ($val as $k => $v) {
                if (in_array($k, $flip_code)) {
                    if ((int)$val['PAY_SHIPING_PRICE'] === 0 && $k == 'PAY_SHIPING_PRICE') {

                    } else if (strlen($val['BWC_ORDER_STATUS']) != 10 && $k == 'BWC_ORDER_STATUS') {
                        $check_data[$val['row']][] = "[订单状态]异常";
                    } else if (empty($v) && $k == 'SHOP_ID') {
                        $check_data[$val['row']][] = "[店铺]名称异常，请填写后重新导入";
                    } else if (empty($v) && $k == 'PAY_CURRENCY') {
                        $check_data[$val['row']][] = "[币种]填写异常，请填写英文大写币种";
                    } else if (empty($v) && ($k == 'ORDER_ID'|| $k == 'ORDER_NO')) {
                        $check_data[$val['row']][] = '[' . $code_zh[$k] . ']字段为0或者为空，请填写后重新导入';
                    } else {
                        $v === '' ? $check_data[$val['row']][] = '[' . $code_zh[$k] . ']字段为空，请填写后重新导入' : null;
                    }
                }
                if (in_array($k, ['ADDRESS_USER_PHONE', 'RECEIVER_TEL', 'tracking_number', 'ADDRESS_USER_POST_CODE'])) {
                    if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $v) > 0) {
                        $check_data[$val['row']][] = "[{$code_zh[$k]}]不能包含汉字，请按真实数据填写";
                    }
                }
            }

            //同时校验店铺+平台
            $strore_info = M('store','tb_ms_')->field('ID')->where(['STORE_NAME'=>$val['SHOP_ID'], 'PLAT_CD'=>$val['PLAT_CD'], 'store_type_cd'=>'N003700003'])->find();
            if ($strore_info){
                $check_data[$val['row']][] = '我方代运营店铺禁止导入订单';
                $unique_orders[] = $val['ORDER_ID'];
            }
            if ($val['exist']) {
                $check_data[$val['row']][] = '[第三方订单id]为空或重复，请去除重复项重新导入';
                $unique_orders[] = $val['ORDER_ID'];
            } else if (!preg_match('|^[0-9a-zA-Z\-\_]+$|', trim($val['ORDER_ID']))) {
                $check_data[$val['row']][] = '[第三方订单id]不能包含汉字，请按真实数据填写';
            }

            if (!preg_match('|^[0-9a-zA-Z\-\_]+$|', trim($val['ORDER_NO']))) {
                $check_data[$val['row']][] = '[第三方订单号]不能包含汉字，请按真实数据填写';
            }

            if (!$val['ORDER_TIME']) {
                $check_data[$val['row']][] = '[下单时间]不能为空';
            } else if (substr($val['ORDER_TIME'], 0, 2) != "20") {
                $check_data[$val['row']][] = '[下单时间]格式错误';
            } else if (strpos($val['ORDER_TIME'], '-')) {
                if ($val['ORDER_TIME'] > date('Y-m-d H:i:s')) {
                    $check_data[$val['row']][] = '[下单时间]时间应小于当前时间，请修改后重试';
                }
            } else {
                $check_data[$val['row']][] = '[下单时间]不符合规范，请使用2017/06/01或2017-06-01格式';
            }
            if (!$val['ORDER_PAY_TIME']) {
                $check_data[$val['row']][] = '[付款时间]不能为空';
            } elseif (!DateModel::validateDate($val['ORDER_PAY_TIME'])) {
                $check_data[$val['row']][] = '[付款时间]格式错误';
            }
            if (strpos($val['ORDER_PAY_TIME'], '-')) {
                if ($val['ORDER_PAY_TIME'] > date('Y-m-d H:i:s')) {
                    $check_data[$val['row']][] = '[付款时间]时间应小于当前时间，请修改后重试';
                }
            } else {
                $check_data[$val['row']][] = '[付款时间]不符合规范，请使用2017/06/01或2017-06-01格式';
            }
            if ($val['SHIPPING_TIME']) {
                if (!DateModel::validateDate($val['SHIPPING_TIME'])) {
                    $check_data[$val['row']][] = '[发货时间]格式错误';
                } elseif ($val['SHIPPING_TIME'] > date('Y-m-d H:i:s')) {
                    $check_data[$val['row']][] = '[发货时间]时间应小于当前时间，请修改后重试';
                }
            }
            if($val['order_origin']) {
                $order_origin = strtolower($val['order_origin']);
                if(!in_array($order_origin ,$order_source_val_map)) {
                    $check_data[$val['row']][] = '[订单客户来源]数据输入有误，请检查后再从新输入';
                }
            }

            if(in_array($val['PLAT_CD'], $gshopper_plat_cds) && empty($val['order_origin'])) {
                $check_data[$val['row']][] = '当前是Gshopper平台订单 [订单客户来源]字段必填';
            }

            //11349 Qoo10结算金额修改1.1版本
            if($val['PLAT_NAME'] == 'Qoo10-SG' && empty($val['PAY_SETTLE_PRICE'])) {
                $check_data[$val['row']][] = 'Qoo10SG平台对应的结算金额必填，将用于insight统计数据使用';
            }
            $country = null;
            if (isset($c[$val['ADDRESS_USER_COUNTRY']]) || $country = TbMsUserAreaModel::checkCountry(strtolower($val['ADDRESS_USER_COUNTRY']))) {
                $country and $c[$val['ADDRESS_USER_COUNTRY']] = json_decode($country, true);
            } else {
                $check_data[$val['row']][] = "[国家名称]不规范，请检查后重新导入";
            }

            //支付总价校验 (浮点比较)  PAY_VOUCHER_AMOUNT PAY_SHIPING_PRICE PAY_ITEM_PRICE
            $num_pri = $val['PAY_TOTAL_PRICE'] - ($val['PAY_ITEM_PRICE'] - $val['PAY_VOUCHER_AMOUNT'] + $val['PAY_SHIPING_PRICE']);
            if ($num_pri > 0.000001 || $num_pri < -0.000001) {
                $check_data[$val['row']][] = "∑订单商品销售单价*商品数量-订单总优惠金额+订单运费≠支付总价，请检查后重新导入";
            }
        }
        $sku_ids = [];//第三方sku集合
        $b5c_sku_ids = [];//第三方sku集合
        foreach ($g as $key => $val) {
            $uni_key = $val['ORDER_ID'] . $val['PLAT_CD'];
            unset($sku_arr);
            foreach ($val as $k => $v) {
                if ($k == 'PLAT_CD' && $v === '') {
                    $check_data[$val['row']][] = "[平台]名称填写不正确，请前往配置管理->店铺管理里查询后填写";
                    continue;
                }
                if (in_array($k, $flip_code)) {
                    $v === '' ? $check_data[$val['row']][] = '[' . $code_zh[$k] . ']为空，请填写后重新导入' : null;
                }
            }
            $b5c_sku_ids[$uni_key][] = $val['B5C_SKU_ID'];
            if (count($b5c_sku_ids[$uni_key]) !== count(array_unique($b5c_sku_ids[$uni_key]))) {
                $check_data[$val['row']][] = 'SKU ID 在订单中重复';
            }

            $sku_ids[$uni_key][] = $val['SKU_ID'];
            if (count($sku_ids[$uni_key]) !== count(array_unique($sku_ids[$uni_key]))) {
                $check_data[$val['row']][] = '第三方SKU ID 在订单中重复';
            }

            if (strpos($val['B5C_SKU_ID'], '##')) {
                $check_data[$val['row']][] = '[SKU ID]: 不存在，请检查SKU ID';
            }
            if ($val['PLAT_CD'] == "N000831400" && $val['E2G_SKU_ID'] == "") {
                // $check_data[$val['row']][] = "第" . $val['row'] . "行的< E2G关联SKU ID>不存在";
            }
        }
        return $unique_orders;
    }

    //check Occupy sku  检查占用
    private function checkOccupySku($o, $s)
    {

        $Operation = M('operation_history', 'tb_wms_');
        $occupy = $Operation->where("ope_type = 'N001010100'  and order_id = '" . $o . "' and sku_id = '" . $s . "'")->count();
        $release = $Operation->where("ope_type != 'N001010100'  and order_id = '" . $o . "' and sku_id = '" . $s . "'")->count();
        trace($occupy, '$occupy');
        trace($release, '$release');
        if ($occupy == 1 && $release == 0) {
            return true;
        } else {
            return false;
        }
    }

    //check Occupy
    private function checkOccupy($o, $ope_type = null)
    {

        $Batch_order = M('batch_order', 'tb_wms_');
        $count = $Batch_order->where("use_type = 1 AND ORD_ID = '" . $o . "'")->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 批量导入po
     * 保税模块
     */
    public function multiple_import_bill_bw()
    {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];

        $excelOperationModel = new BatchPoBwModel($filePath);
        $ret = $excelOperationModel->main();
        if ($ret['state'] == 1) {
            $this->assign('success_data', $ret['success_data']);
            $this->assign('check_data', $ret['check_data']);
            $this->assign('n_search_data', $ret['n_search_data']);
            $this->display();
        } else {
            $this->assign('check_data', $ret['check_data']);
            $this->assign('n_search_data', $ret['n_search_data']);
            $this->display();
        }
    }

    /**
     * 批量导入po
     * 现货模块
     */
    public function multiple_import_bill_xh()
    {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['multiple_po_file_upload_xh']['tmp_name'];

        $excelOperationModel = new BatchPoXhModel($filePath);
        $ret = $excelOperationModel->main();
        if ($ret['state'] == 1) {
            $this->assign('success_data', $ret['success_data']);
            $this->assign('check_data', $ret['check_data']);
            $this->assign('n_search_data', $ret['n_search_data']);
            $this->display();
        } else {
            $this->assign('check_data', $ret['check_data']);
            $this->assign('n_search_data', $ret['n_search_data']);
            $this->display();
        }
    }

    /**
     * 修改备注
     */
    public function edit_update_marks()
    {
        if (!IS_AJAX) $this->ajaxReturn(0, '异常访问', 0);
        $ORD_ID = I('post.ORD_ID');
        $REMARK_MSG = I('post.REMARK_MSG');
        $REMARK_STAT_CD = I('post.REMARK_STAT_CD');
        $IS_SELF = I('post.ORD_TYPE');

        if (!$REMARK_STAT_CD) $data ['REMARK_MSG'] = '';
        else $data ['REMARK_MSG'] = $REMARK_MSG;
        $data ['REMARK_STAT_CD'] = $REMARK_STAT_CD;
        switch ($IS_SELF) {
            case 1:
                $model = M('_op_order', 'tb_');
                $ret = $model->field('ORDER_STATUS, REMARK_MSG')->where('ORDER_ID = "' . $ORD_ID . '"')->find();
                $lmsg = $ret ['REMARK_MSG'];
                $ORD_STAT = $ret ['ORDER_STATUS'];
                if ($model->data($data)->where('ORDER_ID = "' . $ORD_ID . '"')->save()) {
                    if (!$REMARK_STAT_CD) $message = '已取消备注';
                    else $message = '备注成功';
                    $status = 1;
                } else {
                    $message = '备注失败';
                    $status = 0;
                }
                break;
            case 2:
                $model = M('_ms_ord_spl', 'tb_');
                $ret = $model->field('OPTION_MODE_CD, REMARK_MSG')->where('ORD_ID = "' . $ORD_ID . '"')->find();
                $lmsg = $ret ['REMARK_MSG'];
                $ORD_STAT = $ret ['OPTION_MODE_CD'];
                if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                    if (!$REMARK_STAT_CD) $message = '已取消备注';
                    else $message = '备注成功';
                    $status = 1;
                } else {
                    $message = '备注失败';
                    $status = 0;
                }
                break;
            case 0:
                $model = M('_ms_ord', 'tb_');
                $ret = $model->field('ORD_STAT_CD, REMARK_MSG')->where('ORD_ID = "' . $ORD_ID . '"')->find();
                $lmsg = $ret ['REMARK_MSG'];
                $ORD_STAT = $ret ['ORD_STAT_CD'];
                if ($model->data($data)->where('ORD_ID = "' . $ORD_ID . '"')->save()) {
                    if (!$REMARK_STAT_CD) $message = '已取消备注';
                    else $message = '备注成功';
                    $status = 1;
                } else {
                    $message = '备注失败';
                    $status = 0;
                }
                break;
        }
        $logMsg = $REMARK_STAT_CD ? '增加备注(' . $REMARK_MSG . ')' : '取消备注，已删除备注信息';
        $log = A('Log');
        $log->index($ORD_ID, $ORD_STAT, $logMsg);

        $this->ajaxReturn(0, $message, $status);
    }


    /**
     * @var 重置操作数量
     */
    public function resetNum($ord_id)
    {
        //$ord_id = $_POST['ord_id'];
        $m = M("");
        $res = $m->execute("update tb_op_order set `B5C_ORDER_DES_COUNT` = 0 where ORDER_ID='$ord_id'");
        if ($res || $res === 0) {
            $info = array("res" => 1, "msg" => "重置成功");
            echo json_encode($info);
            die;
        } else {
            $info = array("res" => 1, "msg" => "重置失败");
            echo json_encode($info);
            die;
        }


    }

    /**
     * 传入ERP订单号，自动分类获取订单详情
     *
     * @param String $ord_id ERP订单号
     *
     * @return String $ORD_TYPE_CD
     *                       有两种情况
     *                       part1、自营订单tb_op_order
     *                       part2、非自营订单tb_ms_ord
     */
    public function get_order_type()
    {
        $ord_id = $_GET['ordId'];
        //part1、自营订单
        $where = null;
        $where ['B5C_ORDER_NO'] = ['eq', $ord_id];
        $m = M('op_order', 'tb_');
        $result = $m->where($where)->find();
        if ($result) {
            $_GET['ordId'] = $result ['ORDER_ID'];
            $this->orderdetail_self();
        }
        // part2、非自营订单
        $where = null;
        $where['tb_ms_ord.PLAT_FORM'] = [['EXP', 'IS NULL'], ['exp', ' IN ("N000830100","N000830200","N000831300")'], 'or'];
        $where['tb_ms_ord.ORD_ID'] = ['eq', $ord_id];
        $model = M('ms_ord', 'tb_');
        $result = $model->where($where)->find();
        if ($result) {
            //大宗
            if ($result ['ORD_TYPE_CD'] == 'N000620100') {
                $this->orderdetail_bulk();
            }
            //现货
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DELIVERY_WAREHOUSE'] == 'N000680100') {
                $this->orderdetail_xh();
            }
            //直邮
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DELIVERY_WAREHOUSE'] == 'N000680200') {
                $this->orderdetail_dm();
            }
            //保税
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DEVIVERY_WAREHOUSE'] == 'N000680300') {
                $this->orderdetail_bw();
            }
        }
        $this->orderdetail_qg();
    }

    /**
     * @param $ORD_ID
     *
     * @return mixed
     */
    private function sync_gapp_state($ORD_ID)
    {
// 增加Q消息队列
        $Model = M();
        $rbmq = new RabbitMqModel();
//        $result = $Model->table('tb_ms_ord')->field('THRD_USER_ID,EXPE_COMPANY, PLAT_FORM, OA_NUM, THIRD_ORDER_ID')->where('ORD_ID = "' . $ORD_ID . '"')->find();
        $result = $Model->table('tb_ms_ord')->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')->where('tb_ms_ord.ORD_ID = "' . $ORD_ID . '"')->find();
        $rbmq->exchangeName = 'gshopperExchange';
        $rbmq->routeKey = 'Q-B5C2GS-02-RK-01';
        $rbmq->queueName = 'Q-B5C2GS-02';
        $sys_reg_dttm = strtotime($result['SYS_REG_DTTM']);
        $sys_reg_dttm = date('Y-m-d\TH:i:s\Z', $sys_reg_dttm);

        $msg = [
            "platCode" => $result['PLAT_FORM'],//第三方平台code码
            "processId" => create_guid(),//uuid
            "data" => [
                "orders" => [
                    [
                        "thirdOrdId" => $result['THIRD_ORDER_ID'],      //第三方订单号id
                        "ordId" => $ORD_ID,                         //tb_ms_ordORD_ID"
                        "ordStatCd" => 'N000550600',   //"
                        "msg" => "Receipt",
                        "expeCompany" => $result['EXPE_COMPANY'],
                        "trackingNumber" => $result['TRACKING_NUMBER'],
                        "expeCode" => $result['EXPE_CODE'],
                        "expeDate" => $sys_reg_dttm,
                    ]
                ]
            ]
        ];
        $rbmq->setData($msg);
        $res = $rbmq->submit();
        trace($msg, '$msg');
        trace($res, '$res');
        $info ['message'] = L('操作成功');
        $info ['status'] = '2';
        if (!$res) {
            $info ['message'] = L('数据入Q失败，请重试');
            $info ['status'] = '300';
        }
        return $info;
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    private function get_order_channel($order_id)
    {
        $Model = M();
        $order_channel = $Model->table('tb_ms_ord')->where('ORD_ID = \'' . $order_id . '\'')->getField('PLAT_FORM');
        return $order_channel;
    }

    /**
     * @param $CD_VAL_LIKE
     *
     * @return mixed
     */
    private function get_gapp_channels($CD_VAL_LIKE)
    {
        $Models = M();
        $where['CD_NM'] = array('EQ', 'SALE CHANNEL');
        $where['CD_VAL'] = array('LIKE', $CD_VAL_LIKE . '%');
        $channel_arr = $Models->table('tb_ms_cmn_cd')->field('CD')->where($where)->select();
        $channel_arr = array_column($channel_arr, 'CD');
        return $channel_arr;
    }

    public function test_gapp()
    {
        Logs('sssa', 'asdfasdbb');
        exit;
        $CURL = new StockAction();
        foreach ($arr as $v) {
            $data['order_arr'] = '[{"order_id":"' . $v . '","warehouse":"N000680100"}]';
//            $res[] = $CURL->Curl_post('http://erp.gshopper.com/index.php?m=orders&a=patch_order',$data);
            $res[] = $CURL->Curl_post('http://sms2.b5c.com/index.php?m=orders&a=patch_order', $data);
            var_dump($res);
            exit;
        }
//        var_dump($res);
    }

    /**
     * 组装商品URL
     *
     * @param $SKU_ID
     */
    public function join_go_url($SKU_ID)
    {
        $M = M();
        $M->table('tb_ms_guds_opt')->where('GUDS_OPT_ID = ' . $SKU_ID)->select();
        $mainID = '';
        $SPU = '';
        $SLLR_ID = '';
        $url = '/index.php?g=guds&m=guds&a=index#/goodslist/goodsedit/%s/%s/%s';
        printf($url, $mainID, $SPU, $SLLR_ID);
    }

    /**
     * @param $push_arr
     *
     * @return bool
     */
    private function push_out_status($push_arr)
    {
        $PLAT_FORM = [
            'N000831400',
            'N000834100',
            'N000834200',
            'N000834300'
        ];
        $model = M('ord', 'tb_ms_');
        $where['tb_ms_ord.ORD_ID'] = array('in', $push_arr);
        $fields = 'tb_ms_ord.THIRD_ORDER_ID,
                                        tb_ms_ord.ORD_ID, 
                                        tb_ms_ord.PLAT_FORM,
                                        tb_ms_ord_package.EXPE_COMPANY,
                                        tb_ms_ord_package.TRACKING_NUMBER,
                                        tb_ms_ord_package.EXPE_CODE, 
                                        tb_ms_ord_package.SYS_REG_DTTM,
                                        tb_ms_ord.REFUND_STAT_CD';
        $ret = $model->field($fields)
            ->join('left join tb_ms_ord_package on tb_ms_ord.ORD_ID = tb_ms_ord_package.ORD_ID')
            ->where($where)
            ->select();
        if ($ret) {
            $orders = null;
            foreach ($ret as $key => $value) {
                if (in_array($value ['PLAT_FORM'], $PLAT_FORM)) {
                    $sys_reg_dttm = date('Y-m-d\TH:i:s\Z', strtotime($value['SYS_REG_DTTM']));
                    $order = [
                        "thirdOrdId" => $value['THIRD_ORDER_ID'],
                        "ordId" => $value['ORD_ID'],
                        "ordStatCd" => "N000550500",
                        "msg" => "发货",
                        "expeCompany" => $value['EXPE_COMPANY'],
                        "trackingNumber" => $value['TRACKING_NUMBER'],
                        "expeCode" => $value['EXPE_CODE'],
                        "expeDate" => $sys_reg_dttm,
                    ];
                    $orders [] = $order;
                    $orders_plat [$value ['PLAT_FORM']][] = $order;
                }
            }
            if ($orders) {
                $rbmq = new RabbitMqModel();
                $rbmq->exchangeName = 'gshopperExchange';
                $rbmq->routeKey = 'Q-B5C2GS-02-RK-01';
                $rbmq->queueName = 'Q-B5C2GS-02';
                foreach ($orders_plat as $k => $v) {
                    $msg = [
                        "platCode" => $k,
                        "processId" => create_guid($k),
                        "data" => [
                            "orders" => $v
                        ]
                    ];
                    $rbmq->setData($msg);
                    $rbmq_status = $rbmq->submit(false);
                    trace($rbmq_status, '$rbmq_status');
                    trace($msg, '$msg');
                }
                $rbmq->disConnect();
                return $rbmq_status;
            }
        }
    }

    /**
     * @return string
     */
    private function search_time_get()
    {
        $time_get = I("time_type");
        switch ($time_get) {
            case 1:
                $time = 'tb_op_order.ORDER_TIME';
                break;
            case 2:
                $time = 'tb_op_order.SHIPPING_TIME';
                break;
            default:
                $time = 'tb_op_order.ORDER_PAY_TIME';
        }
        return $time;
    }

    /**
     * @return string
     */
    private function search_time_new_get($_prefix)
    {
        $time_get = I("time_type");
        switch ($time_get) {
            case 1:
                $time = $_prefix . 'SYS_REG_DTTM';
                break;
            case 2:
                $time = 'tb_ms_ord_package.SUBSCRIBE_TIME';
                break;
            default:
                $time = $_prefix . 'PAY_DTTM';
        }
        return $time;
    }


    private function join_info_upd($arr)
    {
        unset($arr['update_info']);

        return $arr;
    }

    /**
     * 过滤重复SKU订单
     *
     * @param $repeat_data
     *
     * @return array
     */
    private function goodsRepeat_remove($repeat_data)
    {
        $err_arrs = array();
        foreach ($repeat_data->orders as $key => $val) {
            $goods_arr = $this->obj2arr($val->goods);
            $sku_arr = array_column($goods_arr, 'skuId');
            if (count($sku_arr) != count(array_unique($sku_arr))) {
                unset($repeat_data->orders[$key]);
                $err_msg['orderId'] = $val->thirdOrdId;
                $err_msg['msg'] = L('SKU重复');
                $err_arrs[] = $err_msg;
            }
        }
        $repeat_data->orders = array_values($repeat_data->orders);
        return array($repeat_data, $err_arrs);
    }

    private function obj2arr($data)
    {
        return json_decode(json_encode($data), true);
    }

    public function test()
    {
        exit;
        $data = [
            'data' => [
                [
                    'bill' => [
                        'channel' => 'N000830600',
                        'warehouse_id' => 'N000680100',
                        'link_bill_id' => 'gspx51176701059810',
                        'third_order_id' => '4961422296493994642',
                        'bill_type' => 'N000950100'
                    ],
                    'guds' => [
                        [
                            'GSKU' => '8000372801',
                            'taxes' => 0,
                            'should_num' => 2,
                            'send_num' => 2,
                            'price' => 27.48,
                            'currency_id' => 'N000590300',
                            'currency_time' => '2017-12-06 05:00:09'
                        ]
                    ]
                ]
            ],
            'type' => 0,
            'bill_type' => 'N000950100' //	收发类别
        ];
        $url = U('bill/mul_out_and_in_storage', '', true, false, true);
        if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') $url = 'http://erp.gshopper.com/index.php?m=bill&a=mul_out_and_in_storage';
        $res = json_decode(curl_request($url, $data, 'PHPSESSID=' . $_COOKIE['PHPSESSID'] . '; think_language=zh-CN'), true);

        Logs($url, '$url');
        Logs($data, '$data');
        Logs($res, '$res');
        exit;
    }

    /**
     * @param $order_all_data_index
     *
     * @return array
     */
    private function joinExcelLog($order_all_data_index)
    {
        foreach ($order_all_data_index as $value) {
            $excel_log ['ORD_NO'] = $value['ORDER_ID'];
            $excel_log ['plat_cd'] = $value['PLAT_CD'];
            $excel_log ['ORD_HIST_SEQ'] = time();
            // 'N001820100'
            $excel_log ['ORD_STAT_CD'] = $value['BWC_ORDER_STATUS'];
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $excel_log ['ORD_HIST_HIST_CONT'] = 'Excel 导入';
            $excel_log_arr[] = $excel_log;
        }
        return $excel_log_arr;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function joinPatchLog($data)
    {
        $bwc_res_keyval = $this->bwcStatusArrGet($data);
        foreach ($data['success'] as $value) {
            $excel_log ['ORD_NO'] = $value['orderId'];
            $excel_log ['plat_cd'] = $value['platform'];
            $excel_log ['ORD_HIST_SEQ'] = time();
            $excel_log ['ORD_STAT_CD'] = $bwc_res_keyval[$value['orderId'] . $value['platform']];
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $excel_log ['ORD_HIST_HIST_CONT'] = '派单成功';
            $excel_log_arr[] = $excel_log;
        }
        foreach ($data['failed'] as $value) {
            $excel_log ['ORD_NO'] = $value['orderId'];
            $excel_log ['plat_cd'] = $value['platform'];
            $excel_log ['ORD_HIST_SEQ'] = time();
            $excel_log ['ORD_STAT_CD'] = $bwc_res_keyval[$value['orderId'] . $value['platform']]; // 'N001821000'
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $excel_log ['ORD_HIST_HIST_CONT'] = '派单失败:'.$value['msg'];
            $excel_log_arr[] = $excel_log;
        }
        return $excel_log_arr;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function joinPatchStatusLog($data)
    {
        $where = '1 != 1';
        foreach ($data['success'] as $value) {
            $where .= sprintf(" OR ( ORDER_ID = '%s' AND PLAT_CD = '%s' ) ", $value['orderId'], $value['platform']);
        }
        $Model = M();
        $db_res = $Model->table('tb_op_order t1')
            ->field('t1.ORDER_ID,t1.PLAT_CD,IFNULL(t3.WHOLE_STATUS_CD,t1.SEND_ORD_STATUS) AS send_status_cd,WAREHOUSE,CONCAT(ORDER_ID,PLAT_CD) AS ORDER_KEY')
            ->join('LEFT JOIN tb_ms_ord AS t3 on  t1.ORDER_ID = t3.THIRD_ORDER_ID  AND t1.PLAT_CD = t3.PLAT_FORM')
            ->where($where)
            ->select();
        $send_status_cd = array_column($db_res, 'send_status_cd', 'ORDER_KEY');
        $cd_arr = array_merge(
            B2bModel::get_code_cd('N00182')
        );
        $bwc_res_keyval = $this->bwcStatusArrGet($data);
        foreach ($data['success'] as $value) {
            $excel_log ['ORD_NO'] = $value['orderId'];
            $excel_log ['plat_cd'] = $value['platform'];
            $excel_log ['ORD_HIST_SEQ'] = time();
            $excel_log ['ORD_STAT_CD'] = $bwc_res_keyval[$value['orderId'] . $value['platform']];
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $send_status_cd_val = $send_status_cd[$value['orderId'] . $value['platform']] == 'N002080200' ? '待获取运单号' : $cd_arr[$send_status_cd[$value['orderId'] . $value['platform']]]['CD_VAL'];
            $excel_log ['ORD_HIST_HIST_CONT'] = '派单状态由待派单变为' . $send_status_cd_val;
            $excel_log_arr[] = $excel_log;
        }
        return $excel_log_arr;
    }

    /**
     * @param $PHPExcel
     * @param $currentRow
     * @param $order_data
     * @param $send_data
     *
     * @return array
     */
    private function getExcelData($getVal, $row, $order_data, $send_data, $excel_key)
    {
        $order_data['SHIPPING_DELIVERY_COMPANY'] = (string)$getVal($excel_key['shipping_delivery_company'] . $row);   //物流公司
        $order_data['SHIPPING_TRACKING_CODE'] = (string)$getVal($excel_key['shipping_tracking_code'] . $row);  //物流单号
        $order_data['ADDRESS_USER_NAME'] = (string)$getVal($excel_key['address_user_name'] . $row);   //收货人姓名
        $order_data['ADDRESS_USER_PHONE'] = (string)$getVal($excel_key['address_user_phone'] . $row);  //收货人电话
        $order_data['RECEIVER_TEL'] = (string)$getVal($excel_key['receiver_tel'] . $row);  //收货人电话
        $order_data['USER_EMAIL'] = (string)$getVal($excel_key['user_email'] . $row);  //收货人电话
        $order_data['ADDRESS_USER_COUNTRY'] = (string)$getVal($excel_key['address_user_country'] . $row);  //收货地址国家
        $order_data['ADDRESS_USER_PROVINCES'] = (string)$getVal($excel_key['address_user_provinces'] . $row);  //收货地址省
        $order_data['ADDRESS_USER_CITY'] = (string)$getVal($excel_key['address_user_city'] . $row);  //收货地址市
        $order_data['ADDRESS_USER_REGION'] = (string)$getVal($excel_key['address_user_region'] . $row); //收货地址县区
        $order_data['ADDRESS_USER_ADDRESS1'] = (string)$getVal($excel_key['address_user_address1'] . $row);  //收货地址具体地址
        $order_data['ADDRESS_USER_POST_CODE'] = (string)$getVal($excel_key['address_user_post_code'] . $row);   //邮编
        $order_data['SHIPPING_MSG'] = (string)$getVal($excel_key['shipping_msg'] . $row);  // 运营备注
        $order_data['REMARK_MSG'] = (string)$getVal($excel_key['REMARK_MSG'] . $row);  // 用户备注
        $order_data['order_origin'] = (string)$getVal($excel_key['order_origin'] . $row);  // 订单来源
        $order_data['PACKING_NO'] = (string)$getVal($excel_key['packing_no'] . $row);  // 包裹号

        $order_data['BWC_ORDER_STATUS'] = (string)$getVal($excel_key['bwc_order_status'] . $row); // order status
        $order_data['ADDRESS_USER_IDENTITY_CARD'] = (string)$getVal($excel_key['address_user_identity_card'] . $row); // 收件人身份证
        if ($this->cd_valkey_arr[$order_data['BWC_ORDER_STATUS']]) {
            $order_data['BWC_ORDER_STATUS'] = trim($this->cd_valkey_arr[$order_data['BWC_ORDER_STATUS']]);
        } else {
            $order_data['BWC_ORDER_STATUS'] = '';
        }

        $send_data['sender_name'] = (string)$getVal($excel_key['sender_name'] . $row); // 寄件人信息
        $send_data['sender_phone'] = (string)$getVal($excel_key['sender_phone'] . $row);
        $send_data['sender_zip_code'] = (string)$getVal($excel_key['sender_zip_code'] . $row);
        $send_data['sender_company'] = (string)$getVal($excel_key['sender_company'] . $row);
        $send_data['sender_province'] = (string)$getVal($excel_key['sender_province'] . $row);
        $send_data['sender_city'] = (string)$getVal($excel_key['sender_city'] . $row);
        $send_data['sender_area'] = (string)$getVal($excel_key['sender_area'] . $row);
        $send_data['sender_address'] = (string)$getVal($excel_key['sender_address'] . $row);
        return array($order_data, $send_data);
    }

    /*
     * @param $where_temp
     */
    private function whereTempJoin($where_temp)
    {
        for ($i = 1; $i <= 5; $i++) {
            $where_temp['tb_op_order.ADDRESS_USER_ADDRESS' . $i] = array('exp', 'is not null');
        }
        $where_temp['_logic'] = 'or';
        return $where_temp;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function bwcStatusArrGet($data)
    {
        $status_arr = array_column($data['success'], 'orderId');
        if (!is_array($status_arr)) {
            $status_arr = [];
        }
        $status_err_arr = array_column($data['failed'], 'orderId');
        if (count($status_err_arr) && is_array($status_err_arr)) {
            $status_arr = array_merge($status_arr, $status_err_arr);
        }
        $bwc_res_keyval = $this->searchStatus($status_arr);
        return $bwc_res_keyval;
    }

    /**
     * @param $order_status_upd_arr
     */
    private function updPatchStatus($order_status_upd_arr)
    {
        if (!class_exists('OrderOneKeyThroughModel')) {
            include APP_PATH . 'Lib/Model/Oms/OrderOneKeyThroughModel.class.php';
        }
        $OrderOneKeyThroughModel = new OrderOneKeyThroughModel();
        $OrderOneKeyThroughModel->patch_type = true;
        $OrderOneKeyThroughModel->requestType = false;
        $resOrderOneKeyThrough = $OrderOneKeyThroughModel->main($order_status_upd_arr);
        Logs($order_status_upd_arr, 'order_status_upd_arr');
        Logs($resOrderOneKeyThrough, 'resOrderOneKeyThrough');
    }

    /**
     * @param $order_data
     *
     * @return array
     */
    private function trimData($data)
    {
        $is_null_arr = ['logistic_model_id'];
        foreach ($data as $key => $val) {
            if (!in_array($key, $is_null_arr) && !empty($val)) {
                $data[$key] = preg_replace('/(^[\s\t\r\n　]+)|([\s\t\r\n　]+$)/u', '', $val);
            }
        }
        unset($key);
        unset($val);
        return $data;
    }

    /**
     * @param $status_arr
     * @param $where
     * @param $bwc_res_keyval
     *
     * @return mixed
     */
    public function searchStatus($status_arr)
    {
        $Model = M();
        $where['ORDER_ID'] = array('IN', $status_arr);
        $bwc_status_arr = $Model->table('tb_op_order')->field('ORDER_ID,PLAT_CD,BWC_ORDER_STATUS')->where($where)->select();
        foreach ($bwc_status_arr as $value) {
            $bwc_res_keyval[$value['ORDER_ID'] . $value['PLAT_CD']] = $value['BWC_ORDER_STATUS'];
        }
        return $bwc_res_keyval;
    }

    /**
     * @param $data
     * @param $column_null_arr
     * @param $sku_arr
     * @param $Model
     * @param $sku_total_arr
     *
     * @return mixed
     */
    private function getSkuPatchPriceSql($data, $column_null_arr, $sku_arr, $Model)
    {
        if (!empty($column_null_arr)) {
            $sku_string = implode("','", $sku_arr);
            $sql = "SELECT DISTINCT tb_wms_stream.id, a.sku_id , IFNULL(tb_ms_cmn_cd.CD_VAL, IFNULL(tb_wms_stream.currency_id, 'CNY')) AS currency , a.total_inventory * tb_wms_stream.unit_price AS all_inventory, a.total_inventory AS NUMS , DATE_FORMAT(IFNULL(tb_wms_stream.currency_time, tb_wms_bill.zd_date), '%Y-%m-%d') AS create_time FROM tb_wms_batch a LEFT JOIN tb_wms_center_stock b ON a.SKU_ID = b.SKU_ID LEFT JOIN tb_ms_guds c ON b.GUDS_ID = c.GUDS_ID LEFT JOIN tb_wms_stream ON a.stream_id = tb_wms_stream.id LEFT JOIN tb_wms_bill ON a.bill_id = tb_wms_bill.id LEFT JOIN tb_ms_cmn_cd ON tb_wms_stream.currency_id = tb_ms_cmn_cd.CD  WHERE a.sku_id IN ('" . $sku_string . "')  AND (a.available_for_sale_num != 0 OR a.occupied != 0 OR a.locking != 0) AND tb_wms_bill.warehouse_id IS NOT NULL AND a.total_inventory > 0 ";
            $res = $Model->query($sql);
            foreach ($res as $key => $value) {
                $cambio = BaseCommon::api_currency($value['currency'], $value['create_time']);
                if (empty($sku_total_arr[$value['sku_id']]['all_inventory'])) {
                    $sku_total_arr[$value['sku_id']]['all_inventory'] = 0;
                    $sku_total_arr[$value['sku_id']]['all_num'] = 0;
                }
                $sku_total_arr[$value['sku_id']]['all_inventory'] += ($value['all_inventory'] * $cambio);
                $sku_total_arr[$value['sku_id']]['all_num'] += $value['NUMS'];
            }
        }
        if ($sku_total_arr) {
            foreach ($data as $key => $value) {
                if (in_array($value['SKU_ID'], $column_null_arr) && $sku_total_arr[$value['SKU_ID']]['all_num'] > 0) {
                    $data[$key]['CUSTOMS_PRICE'] = number_format($sku_total_arr[$value['SKU_ID']]['all_inventory'] / $sku_total_arr[$value['SKU_ID']]['all_num'], 4);
                }
            }
        }
        return $data;
    }

    /**
     * @param $v
     * @param $Model
     *
     * @return array
     */
    private function updateGudsSkuCost($v, $Model, $guds_msg)
    {
        if ($v['gudsData']) {
            foreach ($v['gudsData'] as $guds_val) {
                $where_guds['B5C_SKU_ID'] = $guds_val['b5cSkuId'];
                $where_guds['guds_type'] = $guds_val['gudsType'];
                $save_guds['cost_usd_price'] = $guds_val['costUsdPrice'];
                $guds_msg[] = $Model->table('tb_op_order_guds')->where($where_guds)->save($save_guds);
            }
        }
        return $guds_msg;
    }
    
    
    protected function getSourceStatusMap()
    {
        if(!$this->order_source_status_map)
        {
            $order_source_status_map = CodeModel::getCodeLang(PatchModel::getListCdArr('order_source_status'));
            $order_source_status_map = array_column($order_source_status_map,'CD_VAL','CD');
            $order_source_status_map = array_map(function($v) {
                return strtolower($v);
            }, $order_source_status_map);
            $this->order_source_status_map = $order_source_status_map;
        }
        return $this->order_source_status_map;
    }

}

function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}



