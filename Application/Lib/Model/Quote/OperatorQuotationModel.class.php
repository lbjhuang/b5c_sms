<?php

import('ORG.Util.Date');// 导入日期类

/**
 * 运营报价基础信息模型
 * @author Redbo He
 * @date 2020-11-03 11:17
 */
class OperatorQuotationModel extends BaseModel
{
	// 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quotation';
    protected $fields = [
        'quote_no','quote_type','quote_intention_type','require_complete_date',
        'complete_date','small_team_cd','status_cd','is_twice_quote','operate_remark','remark','creator_id','created_by',
        'operator_id','updated_by','created_at','updated_at','director_id','director_by'
    ];

    protected $_validate = [
        ['quote_type', 'require', '报价类型不能为空！', 1, 'regex', 3],
        ['quote_type', [self::QUOTE_TYPE_NORMAL, self::QUOTE_TYPE_PRE],'报价类型值的范围不正确',Model::MUST_VALIDATE,'in'], // 当值不为空的时候判断是否在一个范围内
        ['quote_intention_type', 'require', '报价意向不能为空！', 1, 'regex', 3],

        ['allocate_nos', 'check_allocate_nos_require', '调拨单号不能为空不能为空！', 1, 'callback',3],
        ['allocate_nos', 'check_allocate_nos_relation', 'allocate_no_error', 1, 'callback',3],

        ['quote_intention_type', [ self::QUOTE_INTENTION_TYPE_IN_BULK,self::QUOTE_INTENTION_TYPE_LCL,self::QUOTE_INTENTION_TYPE_FCL ],'报价意向值的范围不正确',Model::MUST_VALIDATE,'in'],
        ['require_complete_date', 'require', '报价要求完成时间不能为空！', 1, 'regex', 3],
        ['require_complete_date', 'check_require_complete_date','完成时间格式有误',1,'callback'],

        ['small_team_cd', 'require', '请选择销售小团队！！', 1, 'regex', 3],
        ['small_team_cd', 'check_small_team', '销售小团队参数异常，请检查', 1, 'callback',3],
    ];

    /**
     * 调拨单号的校验规则
     * @var array[]
     */
    public $allocate_nos_validate = [
        ['quote_type', 'require', '报价类型不能为空！', 1, 'regex', 3],
        ['quote_type', [self::QUOTE_TYPE_NORMAL, self::QUOTE_TYPE_PRE],'报价类型值的范围不正确',Model::MUST_VALIDATE,'in'], // 当值不为
        ['allocate_nos', 'check_allocate_nos_require', '调拨单号不能为空不能为空！', 1, 'callback',3],
        ['allocate_nos', 'check_allocate_nos_relation', 'allocate_no_error', 1, 'callback',3],
    ];


    public $cancel_quotation_validate = [
        ['quotation_id', 'require', '报价单ID不能为空！', 1, 'regex', 3],
        ['quotation_id', 'check_quotation_exist', '报价单不存在，请检查', 1, 'callback',3],
        ['quotation_id', 'check_quotation_lcl_status', '当前报价单拼柜中，取消报价失败', 1, 'callback',3],
        ['quotation_id', 'check_quotation_cancel_status', '报价单已被取消，不能在执行如下操作', 1, 'callback',3],
    ];


    #  模型关联关系
    protected $_link = [
        'QuoteWmsAllo' => [
            'mapping_type'    =>HAS_MANY ,
            'class_name'=>'Quote/QuoteWmsAllo',
            'foreign_key'=>'quotation_id',
            'mapping_name'=>'quote_wms_allos',
            'mapping_order'=>'id asc',
        ],

        "QuotationWmsAllos" => [
            'mapping_type'    =>MANY_TO_MANY,
            'class_name'=>'TbWmsAllo',
            'mapping_name'=>'quotation_wms_allos',
            'foreign_key'=>'quotation_id',
            'relation_foreign_key'=>'allo_id',
            'relation_table'=>'tb_quote_wms_allo',
            "mapping_fields" => "id,b.allo_no,b.allo_out_team,b.allo_in_warehouse,b.allo_out_team,b.allo_out_warehouse"
        ],
    ];



    const QUOTE_TYPE_NORMAL = 1;
    const QUOTE_TYPE_PRE   = 2;

    # 是否是二次报价
    const IS_TWICE_QUOTE_YES = 1;
    const IS_TWICE_QUOTE_NO = 0;

    # 报价意向类型
    const QUOTE_INTENTION_TYPE_IN_BULK = 1;
    const QUOTE_INTENTION_TYPE_LCL = 2;
    const QUOTE_INTENTION_TYPE_FCL = 3;

    # 责任人
    protected static $director_id = '9937'; # 乐山
    protected static $pro_director_id = '255'; # Jo.Diao



    # 1：正常报价，2：提前报价
    public static $quote_type_str_map = [
        self::QUOTE_TYPE_NORMAL => '正常报价',
        self::QUOTE_TYPE_PRE    => '提前报价'
    ];

    public static $quote_intention_type_map = [
        self::QUOTE_INTENTION_TYPE_IN_BULK => '优先散货',
        self::QUOTE_INTENTION_TYPE_LCL     => '优先拼柜',
        self::QUOTE_INTENTION_TYPE_FCL     => '整柜',
    ];

    const STATUS_CD_WAIT_ENQUIRY = 'N003580001'; # 待询价
    const STATUS_CD_WAIT_QUOTE   = 'N003580002'; # 待报价
    const STATUS_CD_WAIT_CONFIRM = 'N003580003'; # 待确认
    const STATUS_CD_FINISH       = 'N003580004'; # 已完成
    const STATUS_CD_LCL          = 'N003580005'; # 拼柜中
    const STATUS_CD_LCL_FINISH   = 'N003580006'; # 拼柜已完成
    const STATUS_CANCEL          = 'N003580007'; # 报价单取消

    public $allocate_no_error = ''; # 错误信息

    protected   $quotation = null;

    /**
     * 校验要求完成时间格式
     * @param string $value
     * @return mixed
     * @author Redbo He
     * @date 2020/11/3  19:10
     */
    public function check_require_complete_date($value)
    {
        if(date("Y-m-d", strtotime($value)) == $value) {
            return true;
        }
        return false;
    }


    /**
     * 校验调拨单号是否为空
     * @param $value
     * @author Redbo He
     * @date 2020/11/6  14:26
     */
    public function check_allocate_nos_require($value)
    {
        $quote_type = I('post.quote_type');
        if($quote_type == self::QUOTE_TYPE_NORMAL) {
            if(empty($value))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * 校验调拨单是否符合要求
     * @param $value
     * @author Redbo He
     * @date 2020/11/6  14:38
     */
    public function check_allocate_nos_relation($value)
    {
        # 系统判定被关联调拨单号是否调出仓库完全一致，（ 调出仓库一致） # allo_out_warehouse
        # 以及是否为同一运营发起，  （ 同一个运营） # create_user
        # 以及期望运输渠道是否一致， （期望运输渠道） #
        # 以及被关联调拨单是否为运输中， （运输中 ） # # N001970603

        # 校验调拨单是已经被关联报价单重 （排查已取消的调拨单） 提示 {调拨单号} 已再报价中
        $quote_type = I('post.quote_type');
        $is_manual_valid = I('post.is_manual_valid',0);
        if($quote_type == self::QUOTE_TYPE_NORMAL)
        {
            $allocate_nos_arr  =  array_unique(array_filter(explode(',',str_replace(["，"],",", $value))));
            # tb_wms_allo
            $tb_wms_allo_model  = D("TbWmsAllo");
            $map = [];
            # $map['create_user'] = session('user_id');
            # $map['state'] = $tb_wms_allo_model::ALLO_IN_TRANSIT; # 运输中
            $map['allo_no']  = ['in', $allocate_nos_arr];
            $result = $tb_wms_allo_model->where($map)
                ->order("id DESC")
                ->field(["id",'allo_no','state','create_user','allo_out_warehouse','allo_in_warehouse','planned_transportation_channel_cd'])
                ->select();
            if(empty($result))
            {
                $this->allocate_no_error = "调拨单数据不存在，重新填写关联调拨单号";
                return false;
            }
            if(count($result) != count($allocate_nos_arr))
            {
                $this->allocate_no_error = "调拨单数据填写数据异常，重新填写关联调拨单号";
                return false;
            }

            # 发起报价不能包含别人的调拨单；当前取消这个逻辑，支持运营在当前界面关联他人的调拨单，不做限制
//            $create_users = array_unique(array_column($result,'create_user'));
//            if(count($create_users) > 1 || ($create_users[0] != session('user_id'))) {
//                $this->allocate_no_error = "调拨单数据数据包含他人调拨单，无法生成报价单，请检查";
//                return false;
//            }

            $states = array_unique(array_column($result,'state'));
            #  状态校验
            if(count($states) > 1 || (count($states) == 1 && $states[0] != $tb_wms_allo_model::ALLO_IN_TRANSIT ))
            {
                $this->allocate_no_error = "调拨单状态不支持报价，请检查并重新填写关联调拨单号";
                return false;
            }
            # 一个调拨单号不支持与多个报价单号进行关联，且一个调拨单号只能被报价单关联一次
            # 增加 排查取消状态状态的调拨单
            $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
            $tb_quote_wms_allo = $quotation_wms_allo_model->alias("a")
                ->field("a.*,b.status_cd")
                ->join("inner join tb_quotation as b on a.quotation_id = b.id")
                ->where([
                    'a.allo_no' => ['in', $allocate_nos_arr],
                    'b.status_cd' => ['neq', self::STATUS_CANCEL]
                ])->select();
            if($tb_quote_wms_allo)
            {
                if($is_manual_valid) {
                    $allo_nos = array_column($tb_quote_wms_allo,'allo_no');
                    $this->allocate_no_error = implode(',', $allo_nos).  "已经在报价中";
                } else
                {
                    $this->allocate_no_error = "一个调拨单号不支持与多个报价单号进行关联，且一个调拨单号只能被报价单关联一次";
                }
                return false;
            }
            # 判断 调出仓库是否一致
            $allo_out_warehouses = array_column($result,'allo_out_warehouse');
            if(count(array_unique($allo_out_warehouses)) != 1)
            {
                $this->allocate_no_error = "调拨单调出仓库不一致，重新填写关联调拨单号";
                return false;
            }
            $planned_transportation_channel_cds = array_column($result,'planned_transportation_channel_cd');
            if(count(array_unique($planned_transportation_channel_cds)) != 1) {
                $this->allocate_no_error = "调拨单调期望运输渠道不一致，重新填写关联调拨单号";
                return false;
            }
        }
        return true;
    }

    public function check_quotation_exist($value)
    {
        $quotation = $this->getQuotation($value);
        if($quotation) return true;
        return false;
    }

    /**
     * 检查报价单状态
     * @param $value
     * @author Redbo He
     * @date 2021/2/8 10:41
     */
    public function check_quotation_cancel_status($value)
    {
        $quotation = $this->getQuotation($value);
        if($quotation && $quotation['status_cd'] == self::STATUS_CANCEL) {
            return false;
        }
        return true;
    }

    #
    public function check_quotation_lcl_status($value)
    {
        $quotation = $this->getQuotation($value);
        if($quotation && $quotation['status_cd'] == self::STATUS_CD_LCL) {
            return false;
        }
        return true;
    }

    protected  function getQuotation($quotation_id)
    {
        if(!is_null($this->quotation)) return $this->quotation;
        $quotation = $this->where("id = {$quotation_id}")->find();
        if($quotation)
        {
            $this->quotation = $quotation;
        }
        return $this->quotation;
    }

    /**
     * 校验销售小团队参数是否异常
     * @param $value
     * @author Redbo He
     * @date 2021/2/7 11:32
     */
    public function check_small_team($value)
    {
        $quote_small_teams = CommonDataModel::QuoteSmallTeams();
        $quote_small_teams_cds = array_column($quote_small_teams,'cd');
        if(in_array($value, $quote_small_teams_cds)) {
            return true;
        }
        return false;
    }

    public function getDirector()
    {
        $director_id = self::$director_id;
        if(isProductEnv())
        {
            $director_id = self::$pro_director_id;
        }
        $Admin = D("Admin");
        return  $Admin->field("M_ID as id, M_NAME as name, M_MOBILE as mobile, M_EMAIL email")->where("M_ID = {$director_id}")->find();
    }

    /**
     * 添加前置函数
     * @param array $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/3  19:13
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        $service = new QuotationService();
        # 报价单状态
        $data['status_cd'] = self::STATUS_CD_WAIT_ENQUIRY; # 待询价
        $data['quote_no'] = $service->generateQuoteNo(); # 订单编号创建
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
    }

    /**
     * 插入后置函数
     * @param $data
     * @param $options
     * @author Redbo He
     * @date 2020/11/6  15:58
     */
    protected function _after_insert($data, $options)
    {
        $quote_type = I('post.quote_type');
        if($quote_type == self::QUOTE_TYPE_NORMAL)
        {
            $allocate_nos = I('post.allocate_nos');
            $allocate_nos_arr  =  array_unique(array_filter(explode(',',str_replace(["，"],",", $allocate_nos))));
            $tb_wms_allo_model  = D("TbWmsAllo");
            $map = [];
           # $map['create_user'] = session('user_id');
            $map['state'] = $tb_wms_allo_model::ALLO_IN_TRANSIT; # 运输中
            $map['allo_no']  = ['in', $allocate_nos_arr];
            $result = $tb_wms_allo_model->where($map)
                ->order("id DESC")
                ->field(["id",'allo_no','state','create_user'])
                ->select();
            if(empty($result)) {
                throw_exception("调拨单数据不存在");
            }
            $insert_data = [];
            #  "quotation_id","allo_id","allo_no","creator_id","created_by","created_at",
            $date = new Date();
            foreach ($result as $item)
            {
                $insert_data[] = [
                    'quotation_id' => $data['id'],
                    'allo_id' => $item['id'],
                    'allo_no' => $item['allo_no'],
                    'creator_id' =>  session('user_id'),
                    'created_by' =>  session('m_loginname'),
                    'created_at' =>  $date->format(),
                ];
            }
            if($insert_data) {
                $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
                $quotation_wms_allo_model->addAll($insert_data);
            }

            ## 正常报价 更新调拨单信息
            $allo_ids = array_column($result,'id');
            $tb_wms_allo_model = D("TbWmsAllo");
            $res4 = $tb_wms_allo_model->where(['id' => ['in', $allo_ids]])->data([
                'quote_no'    => $data['quote_no'],
                'update_user' => session('user_id'),
                'update_time' => $date->format()
            ])->save();
        }

        # 记录日志信息
        $quote_logs_model = D("Quote/QuoteLogs");
        $service = new QuotationService();
        $service->saveLog($quote_logs_model::OBJECT_NAME_QUOTATION,  $data['id'],"发起报价");
    }


    protected function _before_update(&$data, $option)
    {
        $date = new Date();
        # 修改完成时间
        if(isset($data['status_cd']) && $data['status_cd'] == self::STATUS_CD_FINISH ) {
            $data['complete_date'] = $date->format();
        }
        $data['operator_id'] = session('user_id');
        $data['updated_at'] = $date->format();
        $data['updated_by'] = session('m_loginname');
    }

    /**
     * 数据更新 发送消息 TODO
     * @param $data
     * @param $options
     * @author Redbo He
     * @date 2020/11/5  19:52
     */
    protected function _after_update($data, $options)
    {
        
    }

    // 查询成功后的回调方法
    protected function _after_find(&$result,$options) {

        $service = new QuotationService();
        $directors  = $service->getQuoteDirectors();
        $result['director_id'] = implode(',', array_column($directors,'id'));
        $result['director_by'] = implode(', ', array_column($directors,'name'));
        // 获取关联数据 并附加到结果中
        if(!empty($options['link']))
            $this->getRelation($result,$options['link']);
    }


}