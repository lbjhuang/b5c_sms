<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 14:18
 */
class TbMsCmnCdModel extends Model
{
    private static $_instance;
    protected $trueTableName                                = 'tb_ms_cmn_cd';
    public static $purchase_order_status_cd_pre             = 'N00132'; //采购订单状态
    public static $warehouse_cd_pre                         = 'N00068'; //仓库
    public static $purchase_team_cd_pre                     = 'N00129'; //采购团队
    public static $sell_team_cd_pre                         = 'N00128'; //销售团队
    public static $sell_mode_cd_pre                         = 'N00147'; //销售模式
    public static $warehouse_difference_cd_pre              = 'N00146'; //出入库差异
    public static $tax_rate_cd_pre                          = 'N00134'; //采购税率
    public static $currency_cd_pre                          = 'N00059'; //币种
    public static $ship_credential_type_cd_pre              = 'N00148'; //发货凭证类型
    public static $payment_days_cd_pre                      = 'N00142'; //付款天数
    public static $payment_days_type_cd_pre                 = 'N00140'; //付款天数类型
    public static $payment_percent_cd_pre                   = 'N00141'; //付款比例
    public static $payment_node_cd_pre                      = 'N00139'; //付款节点
    public static $payment_dif_reason_cd_pre                = 'N00154'; //应付差额原因
    public static $our_company_cd_pre                       = 'N00124'; //我方公司
    public static $invoice_type_cd_pre                      = 'N00135'; //发票类型
    public static $valuation_unit_cd_pre                    = 'N00069'; //计价单位
    public static $business_direction_cd_pre                = 'N00115'; //业务方向
    public static $business_type_cd_pre                     = 'N00116'; //业务类型
    public static $delivery_type_cd_pre                     = 'N00153'; //交货方式
    public static $language_cd_pre                          = 'N00092'; //语言
    public static $b5c_order_status_cd_pre                  = 'N00055'; //帮我采订单状态
    public static $sell_demand_type_cd_pre                  = 'N00210'; //需求类型
    public static $sell_order_source_cd_pre                 = 'N00211'; //订单来源
    public static $scm_step_cd_pre                          = 'N00212'; //scm总进度
    public static $scm_demand_status_cd_pre                 = 'N00213'; //scm需求状态
    public static $scm_quotation_chosen_cd_pre              = 'N00214'; //scm报价中标情况
    public static $scm_quotation_status_cd_pre              = 'N00215'; //scm报价状态
    public static $payment_cycle_cd_pre                     = 'N00216'; //scm付款周期
    public static $payment_cycle_type_cd_pre                = 'N00221'; //scm付款周期类型
    public static $collection_node_cd_pre                   = 'N00220'; //付款节点
    public static $collection_cycle_cd_pre                  = 'N00217'; //scm收款周期
    public static $collection_days_type_cd_pre              = 'N00219'; //收款天数类型
    public static $auth_and_link_cd_pre                     = 'N00218'; //授权和链路
    public static $purchase_type_cd_pre                     = 'N00189'; //授权和链路
    public static $deal_type_cd_pre                         = 'N00258'; //SCM交易类型
    public static $purchase_return_status_cd_pre            = 'N00264'; //采购退货单状态
    public static $supplier_deduction_type_cd_pre           = 'N00266'; //供应商抵扣类型
    public static $logic_status_cd_pre                      = 'N00127'; //物流状态
    public static $logics_platform_friend_status_cd_pre     = 'N00303'; // 伙伴数据物流状态

    public static $language_english_cd = 'N000920200'; //英语

    public static $currency_usd = 'N000590100'; //美元
    public static $currency_cny = 'N000590300'; //人民币

    public static $platform_ebay = 'N002620600'; //ebay平台

    public static $demand_change_reason_cd_pre          = 'N00222'; //SCM需求修改原因
    public static $abandon_demand_reason_cd_pre         = 'N00223'; //SCM需求弃单原因
    public static $quotation_change_reason_cd_pre       = 'N00224'; //SCM报价修改原因
    public static $abandon_quotation_reason_cd_pre      = 'N00225'; //SCM报价弃单原因
    public static $demand_not_approve_reason_cd_pre     = 'N00226'; //SCM需求审批失败原因
    public static $leader_not_approve_reason_cd_pre     = 'N00227'; //SCM销售领导审批失败原因
    public static $ceo_not_approve_reason_cd_pre        = 'N00228'; //SCM CEO审批失败原因
    public static $justice_not_approve_reason_cd_pre    = 'N00229'; //SCM法务审批失败原因
    public static $stamp_not_approve_reason_cd_pre      = 'N00230'; //SCM法务盖章退回原因
    public static $archive_not_approve_reason_cd_pre    = 'N00231'; //SCM归档PO退回原因
    public static $agree_apply_action_cd_pre            = 'N00232'; //SCM同意采购申请方向
    public static $not_agree_apply_reason_cd_pre        = 'N00233'; //SCM不同意采购申请原因
    public static $process_application_method_cd_pre    = 'N00234'; //SCM不同意采购申请原因
    public static $pur_website_cd_pre                   = 'N00237'; //线上采购网站
    public static $ship_type_cd_pre                     = 'N00245'; //发货操作
    public static $onway_type_cd_pre                    = 'N00241'; //在途类型
    public static $relation_type_cd_pre                 = 'N00235'; //出入库关联单据类型
    public static $plat_cd_pre                          = 'N00083'; //站点（原平台）
    public static $new_plat_cd_pre                      = 'N00262'; //平台
    public static $pur_website_and_new_plat_cd_pre      = 'N00262,N00237,N00083'; //平台&&线上采购网站&&gshopper站点
    public static $product_conversion_status_cd_pre     = 'N00275'; //商品转换单状态
    public static $product_conversion_type_cd_pre       = 'N00272'; //商品转换单类型

    public static $department_type_cd_pre = 'N00251'; //部门类型
    public static $claim_status_cd_pre = 'N00255'; //认领状态
    public static $deduction_type_cd_pre = 'N00253'; //	扣费类型

    public static $after_sale_status_cd_pre = 'N00280'; // 售后状态
    public static $receivable_status_cd_pre = 'N00254'; // 应收状态

    public static $b2b_order_return_goods_status_cd_pre = 'N00277'; //B2B订单退货状态
    public static $b2b_return_goods_status_cd_pre       = 'N00276'; //B2B退货单状态

    public static $company_business_status_cd_pre = 'N00295'; // 我方公司管理-工商登记状态
    public static $company_shareholder_type_cd_pre = 'N00296'; // 我方公司管理-股东类型
    public static $payment_channel_cd_pre = 'N00100'; // 支付渠道
    public static $payment_way_cd_pre = 'N00302'; // 支付方式
    public static $payment_source_cd_pre = 'N00301'; // 支付来源
    public static $payment_subdivision_type_pre = 'N00294'; // 费用细分类型

 	public static $pur_operation_cd_pre               = 'N00287'; //采购结算触发操作
    public static $logics_platform_type_cd_pre = 'N00285'; // 物流管理-物流轨迹平台

    public static $logics_company_cd_pre = 'N00070'; // 物流管理-物流公司
    public static $reissue_type_cd_pre = 'N00313';//售后类型-补发
    public static $return_type_cd_pre  = 'N00314';//售后类型-退货
    public static $refund_type_cd_pre  = 'N00315';//售后类型-退款
    public static $refund_audit_status_cd_pre = 'N00317';//退款审核状态
    public static $after_sale_reason_cd_pre = 'N00316';//售后类型原因备注
    public static $after_sale_reason_gp_cd_pre = 'N00324';//gp售后类型原因备注

    public static $message_search_cd_pre = 'N00312';  // 报文查询

    public static $sell_small_team_cd_pre = 'N00323';  // 销售小团队

    public static $promotion_demand_status_cd_pre = 'N00359';  // 推广需求状态
    public static $promotion_task_status_cd_pre = 'N00360';  // 推广任务状态
    public static $promotion_demand_type_cd_pre  = 'N00361';  // 推广需求内容类型

    public static $store_type_cd_pre  = 'N00370';  // 店铺类型

    public static $reply_status  = 'N00377';  // 回邮单获取状态
    public static $reply_order_warehouse  = 'N00379';  // OTTO_回邮单_仓库配置
    public static $reply_order_express  = 'N00382';  // OTTO_回邮单_快递公司

    public static $improt_bill_audit_status_cd_pre  = 'N00367';  // 出入库审批流程节点
    public static $improt_bill_type_cd_pre  = 'N00368';  //    出入库类型

    public static $promotion_tag_type_cd_pre  = 'N00374';  //    推广标签类型





    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }


    /**
     * @param string $cd_pre 码表CD前六位
     * @return mixed
     * 根据数据字典CD前六位获取该类型数据字典
     */
    public function getCd($cd_pre = '', $no_cache = false) {
        $cd_pre_arr = explode(',', $cd_pre);
        if (empty($cd_pre_arr)) {
            $this->error = '参数错误';
            return false;
        }
        $cd_str = '';
        foreach ($cd_pre_arr as $item) {
            if(empty($item) || strlen($item) != 6) {
                $this->error = '参数错误';
                return false;
            }
            $cd_str .= "CD LIKE '{$item}%' OR ";
        }
        $where['_string'] = trim($cd_str, 'OR ');
        if ($no_cache) {
            return $this->field('CD,CD_VAL,ETC,ETC2')->order('SORT_NO ASC')->where($where)->select();
        }
        return $this->cache(true,300)->field('CD,CD_VAL,ETC,ETC2')->order('SORT_NO ASC')->where($where)->select();
    }

    /**
     * @param string $cd_pre 码表CD前六位
     * @return mixed
     * 根据数据字典CD前六位获取该类型数据字典
     */
    public function getCdY($cd_pre = '', $no_cache = false) {
        $cd_pre_arr = explode(',', $cd_pre);
        if (empty($cd_pre_arr)) {
            $this->error = '参数错误';
            return false;
        }
        $cd_str = '';
        foreach ($cd_pre_arr as $item) {
            if(empty($item) || strlen($item) != 6) {
                $this->error = '参数错误';
                return false;
            }
            $cd_str .= "CD LIKE '{$item}%' OR ";
        }
        $where['_string'] = '('.trim($cd_str, 'OR '). ") AND USE_YN = 'Y'";
        if ($no_cache) {
            return $this->field('CD,CD_VAL,ETC,ETC2')->order('SORT_NO ASC')->where($where)->select();
        }
        return $this->cache(true,300)->field('CD,CD_VAL,ETC,ETC2')->order('SORT_NO ASC')->where($where)->select();
    }

    /**
     * @param string $cd_pre
     * @return bool
     * 根据数据字典CD前六位获取该类型数据字典，并以CD字段作为键名,只能两个字段，不然返回数据格式会变
     */
    public function getCdKey($cd_pre = '') {
        if($cd_pre && strlen($cd_pre) == 6) {
            return $this->cache(true,300)->order('SORT_NO ASC')->order('SORT_NO ASC')->where(['CD'=>['like',$cd_pre.'%']])->getField('CD,CD_VAL',true);
        }else {
            $this->error = '参数错误';
            return false;
        }
    }

    /**
     * @param string $cd_pre
     * @return bool
     * 根据数据字典启用CD前六位获取该类型数据字典，并以CD字段作为键名,只能两个字段，不然返回数据格式会变
     */
    public function getCdKeyY($cd_pre = '') {
        if($cd_pre && strlen($cd_pre) == 6) {
            return $this->cache(true,300)->order('SORT_NO ASC')->order('SORT_NO ASC')->where(['CD'=>['like',$cd_pre.'%']])->getField('CD,CD_VAL',true);
        }else {
            $this->error = '参数错误';
            return false;
        }
    }

    /**
     * @param string $cd_pre
     * @return bool
     * 根据数据字典CD前六位获取该类型数据字典，并以CD字段作为键名
     */
    public function getCdKeyArr($cd_pre = '') {
        if($cd_pre && strlen($cd_pre) == 6) {
            return $this->cache(true,300)->order('SORT_NO ASC')->order('SORT_NO ASC')->where(['CD'=>['like',$cd_pre.'%']])->getField('CD,CD_VAL,ETC,ETC2',true);
        }else {
            $this->error = '参数错误';
            return false;
        }
    }

    /**
     * @param string $cd_pre
     * @return bool
     * 根据数据字典CD前六位获取该类型数据字典，并以CD字段作为键名
     */
    public function getCdKeyArrY($cd_pre = '') {
        if($cd_pre && strlen($cd_pre) == 6) {
            return $this->cache(true,300)->order('SORT_NO ASC')->order('SORT_NO ASC')->where(['CD'=>['like',$cd_pre.'%'],['USE_YN'=>'Y']])->getField('CD,CD_VAL,ETC,ETC2',true);
        }else {
            $this->error = '参数错误';
            return false;
        }
    }

    /**
     * @param string $cd_pre
     * @return bool
     * 根据数据字典CD前六位获取该类型code列表
     */
    public function getCdListY($cd_pre) {
        if($cd_pre && strlen($cd_pre) == 6) {
            return $this->cache(true,300)->order('SORT_NO ASC')->order('SORT_NO ASC')->where(['CD'=>['like',$cd_pre.'%'],['USE_YN'=>'Y']])->getField('CD',true);
        }else {
            $this->error = '参数错误';
            return false;
        }
    }

    /**
     * @return mixed
     * 获取采购订单状态
     */
    public function getPurchaseOrderStatus() {
        return $this->getCd(self::$purchase_order_status_cd_pre);
    }

    /**
     * @return mixed
     * 获取采购订单状态,CD作为键名
     */
    public function getPurchaseOrderStatusKey() {
        return $this->getCdKey(self::$purchase_order_status_cd_pre);
    }

    /**
     * @return mixed
     * 获取采购订单状态
     */
    public function warehouse() {
        return $this->getCd(self::$warehouse_cd_pre);
    }

    /**
     * @return mixed
     * 获取采购订单状态,CD作为键名
     */
    public function warehouseKey() {
        return $this->getCdKey(self::$warehouse_cd_pre);
    }

    /**
     * @return mixed
     * 获取采购团队
     */
    public function purchaseTeams() {
        return $this->getCd(self::$purchase_team_cd_pre);
    }

    /**
     * @return mixed
     * 获取采购团队,CD作为键名
     */
    public function purchaseTeamsKey() {
        return $this->getCdKey(self::$purchase_team_cd_pre);
    }

    public function warehouseDifference() {
        return $this->getCd(self::$warehouse_difference_cd_pre);
    }

    public function taxRate() {
        return $this->getCd(self::$tax_rate_cd_pre);
    }

    public function currency() {
        return $this->getCd(self::$currency_cd_pre);
    }

    public function site() {
        return $this->getCdKey(self::$new_plat_cd_pre);
    }

    /**获取多个key的map
     * @param array $keys_pre
     * @return mixed
     */
    public function getKeysMap($keys_pre = [])
    {
        $where['_logic'] = 'or';
        foreach ($keys_pre as $v) {
            $where[]['CD'] = ['like', $v . '%'];
        }
        return $this->where($where)->getField('CD,CD_VAL', true);
    }

    /**获取cdval
     * @param $cd
     * @return mixed
     */
    public static function getVal($cd)
    {
        return self::getInstance()->cache(true,300)->where(['CD' => $cd])->getField('CD_VAL');
    }

    /**获取scm法务审批人
     * @param $cd
     * @return mixed
     */
    public static function getLegalMan($cd)
    {
        $field = MODULE_NAME == 'Demand' ? 'ETC3' : 'ETC3';
        $etc3 = self::getInstance()->cache(true,300)->where(['CD' => $cd])->getField('ETC3');
        return strtolower($etc3);
    }

    public function currentLangCode() {
        return $this->where(['CD'=>['like',self::$language_cd_pre.'%'],'ETC2'=>LANG_SET])->getField('CD');
    }

    public static function getCompanyCdByVal($cd_val)
    {
        return self::getInstance()
            ->where(['CD'=>['like',self::$our_company_cd_pre.'%'], 'CD_VAL'=>$cd_val])
            ->getField('CD', true);
    }

    public static function getPaymentChannelEtc($cd)
    {
        return self::getInstance()
            ->where(['CD'=>['like',self::$payment_channel_cd_pre.'%'], 'CD'=>$cd, 'USE_YN'=>'Y'])
            ->getField('ETC');
    }

    public static function getPaymentWayByChannelCd($way_cds)
    {
        return self::getInstance()
            ->field('CD,CD_VAL,ETC,ETC2')
            ->where(['CD'=>['like',self::$payment_way_cd_pre.'%'], 'CD'=>['in', $way_cds], 'USE_YN'=>'Y'])
            ->select();
    }

    public static function getCurrencyCdByVal($cd_val)
    {
        return self::getInstance()
            ->where(['CD'=>['like',self::$currency_cd_pre.'%'], 'CD_VAL'=>$cd_val])
            ->getField('CD');
    }

    public static function getAfterSaleStatusMap()
    {
        $where_str = "(CD LIKE '". self::$return_type_cd_pre. "%' OR CD LIKE '".
            self::$reissue_type_cd_pre. "%' OR CD LIKE '".
            self::$refund_type_cd_pre."%') AND USE_YN = 'Y'";
        $where['_string'] = $where_str;
        $status_map =self::getInstance()->where($where)->getField('CD_VAL,ETC');

        foreach ($status_map as $key => $value) {
            $cd_arr = explode(',', $value);
            $status_arr[$key] = self::getInstance()
                ->field('CD,CD_VAL')
                ->where(['CD'=>['in',$cd_arr], 'USE_YN'=>'Y'])
                ->select();
        }
        return $status_arr;
    }

    public static function getMixAfterSaleStatus()
    {
        $where_str = "(CD LIKE '". self::$return_type_cd_pre. "%' OR CD LIKE '".
            self::$reissue_type_cd_pre. "%' OR CD LIKE '".
            self::$refund_type_cd_pre."%') AND USE_YN = 'Y'";
        $where['_string'] = $where_str;
        $status_map =self::getInstance()->where($where)->getField('CD_VAL,ETC');
        $status_arr['type'] = array_keys($status_map);
        foreach ($status_map as $key => $value) {
            $cd_arr = explode(',', $value);
            $status_arr['status'][] = self::getInstance()
                ->field('CD,CD_VAL')
                ->where(['CD'=>['in',$cd_arr], 'USE_YN'=>'Y'])
                ->select();
        }
        return $status_arr;
    }

    /**
     * 获取退款售后原因
     * @return mixed
     */
    public static function getRefundReason()
    {
        $where_str = "CD LIKE '". self::$after_sale_reason_cd_pre. "%' OR CD LIKE '".self::$after_sale_reason_gp_cd_pre. "%' ";
        $where['_string'] = $where_str;
        return self::getInstance()
            ->where($where)
            ->getfield('CD,CD_VAL', true);
    }

    /**
     * 费用细分类型-考勤相关
     * @return mixed
     */
    public static function getAttendanceSubdivisionType()
    {
        return self::getInstance()
            ->where(['CD'=>['like',self::$payment_subdivision_type_pre.'%'], 'USE_YN'=>'Y', 'ETC2'=>'考勤相关'])
            ->getField('CD',true);
    }


    /**
     * 根据平台CD获取平台店铺/站点CD
     * @param $etc3
     * @param string $use_yn
     * @return mixed
     */
    public static function getSiteCdsByETC3($etc3, $use_yn = 'Y')
    {
        return self::getInstance()
            ->where(['ETC3'=>$etc3, 'USE_YN'=>$use_yn])
            ->getField('CD',true);
    }

    public static function replyStatusCd()
    {
        return self::getInstance()
            ->field('CD,CD_VAL,ETC,ETC2')
            ->where(['CD'=>['like',self::$reply_status.'%'], 'USE_YN'=>'Y'])
            ->select();
    }

    public static function replyOrderWarehouse()
    {
        return self::getInstance()
            ->field('CD,CD_VAL,ETC')
            ->where(['CD'=>['like',self::$reply_order_warehouse.'%'], 'USE_YN'=>'Y'])
            ->select();
    }

    public static function replyOrderExpress()
    {
        return self::getInstance()
            ->field('CD,CD_VAL')
            ->where(['CD'=>['like',self::$reply_order_express.'%'], 'USE_YN'=>'Y'])
            ->select();
    }
}
