<?php
import('ORG.Util.Date');// 导入日期类


class QuotationService extends Service
{

    public static $msg_templates = [
        "msg_markdown_tpl" => ">**main_title**
>sub_title
content
        ",
        'common_content'   => ">
>报价发起人：<font color=info >created_by</font>
>报价单号：<font color=warning >quote_no</font>
>报价发起时间：<font color=comment >created_at</font>
>请尽快处理。
>
>如需查看详情，请点击：[查看详情](detail_url)
 ",
        "quote_clc_content" => ">
>拼柜操作人：<font color=info >director_by</font>
>拼柜单号：<font color=warning >lcl_no</font>
>报价发起时间：<font color=comment >created_at</font>
>请尽快处理。
>
>如需查看详情，请点击：[查看详情](detail_url)
 ",
        'wait_quote'       => [
            'main_title' => "待报价事项",
            'sub_title'  => "运营已提交最新的询价信息，请尽快报价",
            'send_to'    => 'director_id'
        ],
        'wait_confirm' => [
            'main_title' => "报价方案待确认事项",
            'sub_title'  => "物流已提交最新的报价信息，请尽快确认",
            'send_to'    => 'creator_id'
        ],
        'wait_shipping' => [
            'main_title' => "待发货事项",
            'sub_title'  => "运营已确认报价方案，请尽快通知物流商揽货",
            'send_to'    => 'director_id'
        ],
        'wait_twice_quote' => [
            'main_title' => "待二次报价事项",
            'sub_title'  => "运营不同意初次报价方案，请尽快二次报价",
            'send_to'    => 'director_id'
        ],
        'twice_quote_wait_confirm' => [
            'main_title' => "二次报价方案待确认事项",
            'sub_title'  => "物流已提交最新的报价信息，请尽快确认",
            'send_to'    => 'creator_id'
        ],
        'quote_has_lcl' => [ # 报价单被拼柜
            'main_title' => "拼柜事项",
            'sub_title'  => "物流将您的报价单进行拼柜，请知悉",
            'send_to'    => 'creator_id',
            'content' => ">
>报价发起人：<font color=info >created_by</font>
>拼柜单号：<font color=warning >lcl_no</font>
>报价发起时间：<font color=comment >created_at</font>
>请尽快处理。
>
>如需查看详情，请点击：[查看详情](detail_url)"
        ],

        'quote_lcl_wait_confirm' => [
            'main_title' => "拼柜方案待沟通确认事项",
            'sub_title'  => "物流已提交最新的拼柜报价信息，请尽快沟通确认",
            'send_to'    => 'quote_lcl_quotations.creator_id',
        ],
        'quote_lcl_wait_shipping' => [
            'main_title' => "拼柜待发货事项",
            'sub_title'  => "拼柜单涉及运营已经一致确认拼柜方案，请尽快发货",
            'send_to'    => 'director_id',
        ]
    ];

    # 报价责任人角色
    protected static $quote_stage_role_id = 129; # 测试环境暴击责任人角色ID
    protected static $quote_role_id = 86;         #正式环境暴击责任人角色ID



    /**
     * 发送消息
     * @param $msg_type
     * @param $quote_data
     * @param $quote_type
     * @author Redbo He
     * @date 2020/11/17 19:26
     */
    public function pushQuoteWorkWxMessage($msg_type, $quote_data, $quote_type = '', $send_to_user_id = 0)
    {
        if (!is_array($quote_data) && empty($quote_data)) {
            return false;
        }
        $type_template_params = isset(self::$msg_templates[$msg_type]) ? self::$msg_templates[$msg_type] : '';
        if (empty($type_template_params)) {
            return false;
        }
        $default_content_key = 'common_content';
        $content_key         = '';
        if ($quote_type) {
            $content_key = $quote_type . '_content';
        }
        $common_content = isset(self::$msg_templates[$content_key]) ? self::$msg_templates[$content_key] : self::$msg_templates[$default_content_key];

        if (isset($type_template_params['content']) && !empty($type_template_params['content']))
        {
            $common_content = $type_template_params['content'];
        }
        $msg_markdown_tpl = self::$msg_templates['msg_markdown_tpl'];
        $mes_params       = [
            'quote_no'   => isset($quote_data['quote_no']) ? $quote_data['quote_no'] : '',
            'lcl_no'     => isset($quote_data['lcl_no']) ? $quote_data['lcl_no'] : '',
            'director_by'=> isset($quote_data['director_by']) ? $quote_data['director_by'] : '',
            'created_at' => $quote_data['created_at'],
            'created_by' => $quote_data['created_by'],
        ];
        $tab_data = [
            'url' =>  urlencode('/index.php?' . http_build_query(['m' => 'quote', 'a' => 'quotation_management_detail', 'id' => $quote_data['id']])),
            'name' => '编辑报价'
        ];
        if($quote_type == 'quote_clc') {
            $tab_data = [
                'url' => urldecode('/index.php?' . http_build_query(['m' => 'quote', 'a' => 'combine_cabinets_detail', 'id' => $quote_data['id']])),
                'name' => '编辑拼柜'
            ];
        }
        $detail_url = ERP_HOST . '/index.php?' . http_build_query(['tab_data' => $tab_data]);
        $mes_params['detail_url'] = $detail_url;

        $send_to_field    = isset($type_template_params['send_to']) ? $type_template_params['send_to'] : '';
        if(strrpos($send_to_field,".") !== false) {
            $send_to_fields = explode('.', $send_to_field);
            $send_to_user_ids = [];
            if(count($send_to_fields) == 2) {
                $send_to_data     = $quote_data;
                $send_to_user_id = array_unique(array_column(array_get($quote_data,$send_to_fields[0]),$send_to_fields[1]));
                $send_to_user_id = implode(',', $send_to_user_id);
            }
        }
        else
        {
            $send_to_user_id  = $send_to_user_id ? $send_to_user_id : $quote_data[$send_to_field];
        }

        $admin_user_wxids  = $this->getAdminWorkWxUid($send_to_user_id);
        $res = false ;
        if($admin_user_wxids)
        {
            unset($type_template_params['send_to']);
            foreach ($admin_user_wxids as $admin_user)
            {
                $mes_params['director_by'] = $admin_user['name'];
                $common_content   = $this->replace_template_var($common_content, $mes_params);
                $type_template_params['content'] = $common_content;
                $content  = $this->replace_template_var($msg_markdown_tpl, $type_template_params);
                $res = ApiModel::WorkWxSendMarkdownMessage($admin_user['wid'], $content);
            }
        }
        return $res;
        ## 发送模板消息
    }

    public function replace_template_var($template, $data)
    {
        if ($data) {
            foreach ($data as $k => $v) {
                $template = str_replace($k, $v, $template);
            }
        }
        return $template;
    }

    protected function getAdminWorkWxUid($user_id)
    {
        $Admin = D("Admin");
        $user_ids = explode(',', $user_id);
        $admin_user_wids  =  $Admin->field("a.M_ID as id, a.M_NAME as name, a.M_MOBILE as mobile, a.M_EMAIL emailm,a.empl_id, b.wid")
                                ->alias("a")
                                ->join("inner join tb_hr_empl_wx as b on a.empl_id = b.uid")
                                ->where(["M_ID" => ['in', $user_ids]])
                                ->select();
        return $admin_user_wids;

    }




    /**
     * 创建订单
     * @author Redbo He
     * @date 2020/11/3  20:00
     */
    public function generateQuoteNo($table_name = 'quotation', $table_prefix = 'tb_', $prefix = 'BJ', $no_field_name = 'quote_no')
    {
        $model  = M($table_name, $table_prefix); #
        $date = new Date();
        $start_date = $date->format("%Y-%m-%d 00:00:00");
        $end_date = $date->format("%Y-%m-%d 23:59:59");
        $where['created_at'] = ['between', [$start_date, $end_date]];
        $last_record = $model->where($where)->order('id DESC')->find();
        $start_num = 0;
        if($last_record) {
            $last_sn = $last_record[$no_field_name];
            $start_num = substr($last_sn,-4);
        }

        $order_num = str_pad($start_num +1,4,0,STR_PAD_LEFT);
        return $prefix . date("Ymd"). $order_num;
    }

    /**
     * 保存操作日志信息
     * @param $object_name
     * @param $object_id
     * @param $content
     * @return bool|mixed|string
     * @author Redbo He
     * @date 2020/11/13  10:48
     */
    public function saveLog( $object_name , $object_id, $content)
    {
        $quote_logs_model = D("Quote/QuoteLogs");
        if(!in_array($object_name, $quote_logs_model::$allow_object_names))
        {
            return  false;
        }
        $data = [
            'object_name' =>  $object_name,
            'object_id' =>  $object_id,
            'operation_detail' => $content
        ];
        return $quote_logs_model->add($data);
    }

    /**
     * 获取报价责任人人
     * @author Redbo He
     * @date 2020/11/23 11:01
     */
    public function getQuoteDirectors()
    {
        $role_id = self::$quote_stage_role_id;
        if(isProductEnv())
        {
            $role_id = self::$quote_role_id;
        }
        $admin_m        = D("Admin");
        $admin_role_m   = D("AdminRole");
        $role_id_admin_data =   $admin_role_m->alias("a")
                            ->field("b.M_ID as id , b.M_NAME as name, b.M_EMAIL as email")
                            ->join("inner join bbm_admin b on a.M_ID = b.M_ID")
                            ->where([
                                "a.ROLE_ID" => ['eq', $role_id],
                                "b.is_use" => ['eq', 0],
                            ])
                            ->select();
        return $role_id_admin_data;
    }

    /**
     * 发送报价单确认邮箱
     * @param $object_id
     * @param $object_name
     * @author Redbo He
     * @date 2021/3/10 13:26
     */
    public function sendEmail($object_id, $object_name)
    {
        $quotationScheme = D("Quote/QuotationScheme");
        $quotationRepository = new QuotationRepository();
        $quotation_data = $goods_data = $quotation_schemes =
        $quote_lcl_data = $quotation_allo_no_map =
        $small_team_cds = $create_ids = [];

        $sync_fields = [
            'total_box_num','total_volume','total_weight','allo_in_warehouse_val',
            'allo_out_warehouse_val','declare_type_cd_val','is_electric_cd_val'
        ];
        if($object_name == $quotationScheme::OBJECT_NAME_QUOTATION) {
            $quotation_model = D("Quote/OperatorQuotation");
            $quotation_data = $quotation_model
                                    ->field("id, '' as lcl_no ,quote_no,quote_type, small_team_cd,status_cd,created_by,creator_id")
                                    ->whereString(" id = {$object_id}")
                                    ->find();
            if(empty($quotation_data)) return false;
            if($quotation_data['status_cd'] != $quotation_model::STATUS_CD_FINISH ) {
                return false;
            }
            $quotation_data = CodeModel::autoCodeOneVal($quotation_data,
                ['small_team_cd',]
            );
            $small_team_cds[] = $quotation_data['small_team_cd'];
            $create_ids[] = $quotation_data['creator_id'];
            if ($quotation_data['quote_type'] == $quotation_model::QUOTE_TYPE_PRE) {
                $goods        = $quotationRepository->getPreQuoteGoods($object_id);
                $packing_info = $quotationRepository->getPreQuotePackingInfo($object_id);
            } else if ($quotation_data['quote_type'] == $quotation_model::QUOTE_TYPE_NORMAL) {
                $goods        = $quotationRepository->getNormalQuoteGoods($object_id, true); # 商品信息
                $packing_info = $quotationRepository->getNormalQuotePackingInfo($object_id);
            }
            $packing_data  = array_column($packing_info, NULL,'quotation_id');
            $quotation_schemes = $quotationRepository->getQuoteSchemes($object_id,$object_name, $quotationScheme::AUDIT_STATUS_SUCCESS);
            $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
//            $quotation_wms_allo_data = $quotation_wms_allo_model->field("id,quotation_id,allo_id,allo_no")
//                ->where(["quotation_id" => $object_id])
//                ->select();
            # $quotation_allo_no_map = array_column($quotation_wms_allo_data,'allo_no','quotation_id');
            # 获取报价单
        } else if ($object_name == $quotationScheme::OBJECT_NAME_QUOTE_LCL) {
            # 报价单信息查询
            $quote_lcl_data = $quotationRepository->getQuoteLcl($object_id);
            if(empty($quote_lcl_data)) return false;
            $quote_lcl_model = D("Quote/QuoteLcl");
            if($quote_lcl_data['status_cd'] != $quote_lcl_model::STATUS_CD_FINISH ) {
                return false;
            }
            # relation_small_team_cds
            $small_team_cds = $quote_lcl_data['relation_small_team_cds'];
            $create_ids     = $quote_lcl_data['relation_created_ids'];
            $goods = $quotationRepository->getQuoteLclGoods($object_id, $quote_lcl_data, true);
            $quote_lcl_pre_pack = $quotationRepository->getPreQuoteLclPackingInfo($object_id);
            $quote_lcl_normal_pack = $quotationRepository->getNormalQuoteLclPackingInfo($object_id);
            $packing_data = [];
            if($quote_lcl_pre_pack) { $packing_data = array_merge($packing_data, $quote_lcl_pre_pack);}
            if($quote_lcl_normal_pack) { $packing_data = array_merge($packing_data, $quote_lcl_normal_pack);}
            $packing_data  = array_column($packing_data, NULL,'quotation_id');
            # $quotation_allo_no_map = $quote_lcl_data['relation_quotation_allo_no_map'];
            $quotation_schemes = $quotationRepository->getQuoteSchemes($object_id,$object_name,$quotationScheme::AUDIT_STATUS_SUCCESS);
            # 获取拼柜单报价信息
        }

        foreach ($goods as $good) {
            $quotation_id = $good['quotation_id'];
            $pack = isset($packing_data[$quotation_id]) ? $packing_data[$quotation_id] : [];
            foreach ($sync_fields as $field)
            {
                $good[$field] = '';
                if($pack && isset($pack[$field])) {
                    $good[$field] = $pack[$field];
                }
            }
            $goods_data[] = $good;
        }
        #
        if($quotation_schemes)
        {
            $logisticsSupplier = CommonDataModel::logisticsSupplier() ;
            $logisticsSupplier = array_column($logisticsSupplier,'SP_RES_NAME_EN','ID');
            foreach ($quotation_schemes as &$quotation_scheme) {
                if ($quotation_scheme['scheme_detail']) {
                    $quotation_scheme['scheme_detail']  = CodeModel::autoCodeTwoVal($quotation_scheme['scheme_detail'], [
                        "transportation_channel_cd", 'logistics_currency_cd', 'insurance_currency_cd', 'predict_currency_cd', 'stuffing_type_cd']);
                    foreach ($quotation_scheme['scheme_detail'] as &$scheme_detail) {
                        $scheme_detail['transport_supplier_name'] = isset($logisticsSupplier[$scheme_detail['transport_supplier_id']]) ? $logisticsSupplier[$scheme_detail['transport_supplier_id']] : '';
                        $quotation_scheme['quote_no'] = '';
                        if ($quotation_scheme['object_id'] == $object_id && $quotation_scheme['object_name'] == $quotationScheme::OBJECT_NAME_QUOTATION) {
                            $scheme_detail['quote_no'] = $quotation_data['quote_no'];
                        }
                        else {
                            $scheme_detail['quote_no'] = $scheme_detail['quotation_ids'] ? implode(",", $scheme_detail['quotation_ids']) : "";
                        }
                    }
                }
            }
        }
        #  获取报价单商品信息
        # 查询邮件信息发送人
        $address = $this->getSendToUserEmail($create_ids);
        $view = Think::instance('View');
        $view->assign('object_name',$object_name);
        $view->assign('quote_lcl',$quote_lcl_data);
        $view->assign('quotation',$quotation_data);
        $view->assign('goods',$goods_data);
        $view->assign('quotation_schemes',$quotation_schemes);
        # return $view->display("Quote/quote_email");
        $content = $view->fetch("Quote/quote_email");
        # 增加销售小团队报价领导邮件查询
        $smsEmail = new SMSEmail();
        $cc = $this->getEmailCc($small_team_cds);
        $title = "报价方案确认通知";
        return $smsEmail->sendEmail($address, $title, $content, $cc);
    }



    protected function getSendToUserEmail($create_ids = [])
    {
        $create_emails =  M("admin","bbm_")->where(
            [
                "M_ID" => ["in", $create_ids]
            ]
        )->getField("M_EMAIL",true);
        $create_emails = array_filter($create_emails);

        return $create_emails;
    }

    protected  function getSmallTeamEmails($small_team_cds)
    {
        $result = [];
        if(empty($small_team_cds)) return [];
        $model = M('_ms_cmn_cd', 'tb_');
        $small_team_cd_data = $model->field('CD as cd, CD_VAL as cdVal,SORT_NO as sortNo, ETC as comment, ETC2 as comment2, ETC3 as comment3')
            ->where([
                'CD' => ['in', $small_team_cds]
            ])
            ->select();
        $small_team_manager_names = array_filter(array_column($small_team_cd_data,'comment2'));
        if($small_team_manager_names)
        {
            $result = M("admin","bbm_")->where(
                [
                    "M_NAME" => ["in", $small_team_manager_names]
                ]
            )->getField("M_EMAIL",true)
            ;

        }
        return array_filter($result);
    }

    protected  function getEmailCc($small_team_cds)
    {
        return $this->getSmallTeamEmails($small_team_cds);
    }
}