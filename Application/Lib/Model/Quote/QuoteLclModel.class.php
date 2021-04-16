<?php

import('ORG.Util.Date');// 导入日期类
class QuoteLclModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_lcl';
    protected $fields = [
        "lcl_no",'status_cd','director_id','director_by','creator_id','created_by', 'operator_id','updated_by','created_at','updated_at',
        'deleted_by','deleted_at',
    ];

    protected $_validate = [
        ['lcl_quote_nos', 'lcl_quote_require_check', '关联报价单号不能为空！', 1, 'callback', 3],
        ['lcl_quote_nos', 'lcl_quote_length_check','关联的报价单号必须大于1',1,'callback'],
        ['lcl_quote_nos', 'lcl_quote_check','报价单号已被拼柜，请从新选择',1,'callback'],
        ['lcl_quote_nos', 'lcl_quote_lcl_check','报价单数据中的数据状态不能被拼柜，请检查',1,'callback'],
    ];

    protected $_link = [
        "QuoteLclRelations" => [
            'mapping_type'    => HAS_MANY ,
            'class_name' =>'Quote/QuotationLclQuotationRelations',
            'foreign_key'=>'quote_lcl_id',
            'mapping_name'=>'quote_lcl_relations',
            'mapping_order'=>'id asc',
        ],
        "QuoteLclQuotations" => [
            'mapping_type'    => MANY_TO_MANY,
            'class_name'=>'Quote/OperatorQuotation',
            'mapping_name'=>'quote_lcl_quotations',
            'foreign_key'=>'quote_lcl_id',
            'relation_foreign_key'=>'quotation_id',
            'relation_table'=>'tb_quote_lcl_quotation_relations',
           #  "mapping_fields" => "",
        ],
    ];

    const STATUS_CD_WAIT_QUOTE   = 'N003580002'; # 待报价
    const STATUS_CD_WAIT_CONFIRM = 'N003580003'; # 待确认
    const STATUS_CD_FINISH       = 'N003580004'; # 已完成

    public static  $allow_status = [
        self::STATUS_CD_WAIT_QUOTE,
        self::STATUS_CD_WAIT_CONFIRM,
        self::STATUS_CD_FINISH,
    ];

    # 责任人
    protected static $director_id = '9937'; # 乐山
    protected static $pro_director_id = '255'; # Jo.Diao



    public function lcl_quote_require_check($value)
    {
        if(is_array($value) && count($value) > 0) {
            return true;
        }
        return false;
    }

    public function lcl_quote_length_check($value)
    {
        $min_limit_length = 2; #
        if(is_array($value) && count($value) >= $min_limit_length) {
            return true;
        }
        return false;
    }

    public function lcl_quote_check($value)
    {
        $quotation_model = D("Quote/OperatorQuotation");
        $res = $quotation_model->field(['tb_quotation.id','tb_quotation.quote_no',"tb_quote_lcl_quotation_relations.id as relation_id"])
                        ->join(" inner join tb_quote_lcl_quotation_relations on tb_quotation.id = tb_quote_lcl_quotation_relations.quotation_id ")
                        ->where([
                            "tb_quotation.quote_no" => ["in", $value],
                            "tb_quote_lcl_quotation_relations.deleted_at" => ["exp", "IS NULL"],
                        ])
                        ->select();
        if($res)
        {
            return false;
        }
        return true;
    }

    public function lcl_quote_lcl_check($value)
    {
        $quotation_model = D("Quote/OperatorQuotation");
        $res = $quotation_model->field(['tb_quotation.id','tb_quotation.quote_no'])
            ->where(["quote_no" => ["in", $value], "status_cd" => ['neq',$quotation_model::STATUS_CD_WAIT_QUOTE]])->select();
        if($res)
        {
            return false;
        }
        return true;
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
     * @param $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/9  15:48
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        # 报价单状态
        $service = new QuotationService();
        $data['lcl_no'] = $service->generateQuoteNo("quote_lcl","tb_","PG","lcl_no"); # 订单编号创建
        $data['status_cd'] = self::STATUS_CD_WAIT_QUOTE; # 待报价
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
        $data['updated_at'] = $date->format();
    }


    protected function _after_insert($data, $options)
    {
        $lcl_quote_nos = $data['lcl_quote_nos'];
        $id            = $data['id'];
        $quotation_model = D("Quote/OperatorQuotation");
        $quotations = $quotation_model->field(['id','quote_no'])-> where([
            'quote_no' => ['in', $lcl_quote_nos]
        ])->select();
        $insert_data = [];
        foreach ($quotations as $quotation) {
            $insert_data[]  = [
                'quote_lcl_id' => $id,
                'quotation_id' => $quotation['id'],
            ];
        }
        $quotation_lcl_quotation_relations_model = D("Quote/QuotationLclQuotationRelations");
        $quotation_lcl_quotation_relations_model->addAll($insert_data);
        # 修改 拼单 状态为拼单中
        $quotation_ids = array_column($quotations,'id');
        if($quotation_ids)
        {
            $update_data = [
                'status_cd' => $quotation_model::STATUS_CD_LCL
            ];
            $res = $quotation_model->where([
                ['id' => ['in', $quotation_ids]]
            ])->data($update_data)->save();
        }

        # 记录日志信息
        $service = new QuotationService();

        # 发送企业微信消息 拼柜事项
        foreach ($quotations as $quotation) {
            $quotation['lcl_no'] = $data['lcl_no'];
            $service->pushQuoteWorkWxMessage('quote_has_lcl',$quotation);
        }
        $service->saveLog("quote_lcl", $id,"发起拼柜");
    }

    protected function _before_update(&$data, $option)
    {
        $date = new Date();
        # 修改完成时间
        $data['operator_id'] = session('user_id');
        $data['updated_at'] = $date->format();
        $data['updated_by'] = session('m_loginname');
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


    protected function _after_update($data, $options)
    {

    }


}