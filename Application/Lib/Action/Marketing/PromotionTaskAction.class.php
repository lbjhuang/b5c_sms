<?php
/**
 * 营销管理-推广任务
 * Class PromotionDemandAction
 */

class PromotionTaskAction extends BaseAction
{

    public static $status_returned_cd = "N003600001";  // 推广任务状态	已退回
    public static $status_accom_cd = "N003600002"; // 推广任务状态	已推广
    public static $status_being_cd = "N003600003";  // 	推广任务状态	推广中
    public static $status_feed_cd = "N003600004";  // 推广任务状态	待反馈优惠码

    public static $coupon_source_erp = "N003780001";  // 优惠码来源	系统优惠码
    public static $coupon_source_shopnc = "N003780002";  // 优惠码来源	新后台优惠码

    // 获取对应的推广任务信息
    public function getTaskInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $response_data = (new PromotionTaskService())->getTaskInfo($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     *  获取基础数据源-渠道平台
     */
    public function getChannelData(){

        $data = array();
        // 渠道平台
        $url = INSIGHT.'/insight-backend/gpPlatform/queryPlatform';
        $list = (new PromotionTaskService())->requsetInsightJson($url,array());
        $list = json_decode($list,true);

        if (!empty($list) && !empty($list['datas'] )){
            $data['channel_platform'] = $list['datas'];
        }else{
            $this->ajaxError([],'获取基础数据源-渠道媒介失败',4000);
        }

        // 渠道媒介
        $url = INSIGHT.'/insight-backend/gpChannelMedium/queryChannelMediumInfo';
        $list = (new PromotionTaskService())->requsetInsightJson($url,array());
        $list = json_decode($list,true);
        if (!empty($list) && !empty($list['datas'] )){
            $data['channel_medium'] = $list['datas'];
        }else{
            $this->ajaxError([],'获取基础数据源-渠道平台失败',4000);
        }

        $this->ajaxSuccess($data);
    }


    /**
     *  创建认领任务
     */
    public function create(){
        try{
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];

            $model->startTrans();
            $promotionDemandService = new PromotionDemandService();
            $promotionTaskService = new PromotionTaskService();
            if (!empty($post_data)){
                $promotion_task_no = "";
                $insert_data  = array();
                $channel_data = array();
                $activity_data = array();
                $send_data_wx = array();
                if (count($post_data) != count(array_unique(array_column($post_data,'activity_tag')))){
                    $repet_arr = array_diff_assoc(array_column($post_data,'activity_tag'),array_unique(array_column($post_data,'activity_tag')));
                    throw new Exception('活动标签 ：'.implode(",",$repet_arr).' 重复');
                }
                foreach ( $post_data as $key =>  $itme){
                    $task_info = $promotionTaskService->getFind(array('activity_tag'=>$itme['activity_tag']));
                    if ($task_info){
                        throw new Exception('活动标签已经存在:'.$itme['promotion_demand_no']);
                    }
                    $demand_info = $promotionDemandService->getFind(array('promotion_demand_no'=>$itme['promotion_demand_no']));
                    $this->createValidate($itme,$demand_info);
                    $temp_data['promotion_demand_no'] = isset($itme['promotion_demand_no']) ? $itme['promotion_demand_no'] : "";
                    $temp_data['channel_platform_id'] = isset($itme['channel_platform_id']) ? $itme['channel_platform_id'] : "";
                    $temp_data['channel_platform_name'] = isset($itme['channel_platform_name']) ? $itme['channel_platform_name'] : "";
                    $temp_data['channel_platform_tag'] = isset($itme['channel_platform_tag']) ? $itme['channel_platform_tag'] : "";
                    $temp_data['channel_medium_id'] = isset($itme['channel_medium_id']) ? $itme['channel_medium_id'] : "";
                    $temp_data['channel_medium_name'] = isset($itme['channel_medium_name']) ? $itme['channel_medium_name'] : "";
                    $temp_data['activity_tag'] = isset($itme['activity_tag']) ? $itme['activity_tag'] : "";
                    $temp_data['link_tracking'] = isset($itme['link_tracking']) ? $itme['link_tracking'] : "";
                    $temp_data['promontion_price'] = isset($itme['promontion_price']) ? $itme['promontion_price'] : "";
                    $temp_data['promontion_price'] = isset($itme['promontion_price']) ? $itme['promontion_price'] : "";
                    $temp_data['promontion_currency_cd'] = isset($itme['promontion_currency_cd']) ? $itme['promontion_currency_cd'] : "";
                    $temp_data['forecast_sum_price'] = isset($itme['forecast_sum_price']) ? $itme['forecast_sum_price'] : "";
                    $temp_data['forecast_sum_currency_cd'] = isset($itme['forecast_sum_currency_cd']) ? $itme['forecast_sum_currency_cd'] : "";
                    $temp_data['forecast_rol'] = isset($itme['forecast_rol']) ? $itme['forecast_rol'] : "";




                    $temp_data['status_cd'] = 'N003600003';
                    if ($demand_info['promotion_type_cd'] == 'N003610003'){
                        $temp_data['status_cd'] = 'N003600004';
                        //$temp_data['coupon_source_cd'] = self::$coupon_source_erp; //11237 shopNC优惠码同步-待反馈优惠码任务默认系统优惠码类型
                    }
                    $temp_data['promotion_task_no'] = $promotionTaskService->createPromotionTaskNo($promotion_task_no);
                    $promotion_task_no =  $temp_data['promotion_task_no'];
                    $temp_data['activity_name'] = $itme['activity_tag'];
                    $temp_data['create_by'] = userName();
                    $temp_data['create_at'] = date("Y-m-d H:i:s");
                    $temp_data['update_by'] = userName();
                    $temp_data['update_at'] = date("Y-m-d H:i:s");
                    $channel_data[] = array(
                        "promotion_task_no" => $temp_data['promotion_task_no'],
                        "channel_name" => $itme['channel_platform_name'].'&'.$itme['channel_medium_name'],
                        "medium_name" => $itme['channel_medium_name'],
                        "platform_name" => $itme['channel_platform_name'],
                        "platform_tag" => $itme['channel_platform_tag'],
                        "medium_tag" =>  $itme['channel_medium_tag'],
                        "channel_tag" => "hmsr={$itme['channel_platform_tag']}&hmmd={$itme['channel_medium_tag']}",
                        "create_by" => userName(),
                    );
                    $activity_data[] = array(
                        "promotion_task_no" => $temp_data['promotion_task_no'],
                        'act_name' => $itme['activity_tag'],
                        'country_id' => $demand_info['area_id'],
                        'act_describe' => "",
                        'act_tag' => $itme['activity_tag'],
                        'act_url' => "{$demand_info['link']}?hmsr={$itme['channel_platform_tag']}&hmmd={$itme['channel_medium_tag']}&hmci={$itme['activity_tag']}",
                        'start_date' => date("Ymd"),
                        'end_date' => date("Ymd"),
                        'ddc_usd' => 0,
                        'total_cost_usd' => 0,
                        "create_by" => userName(),
                    );
                    $insert_data[] = $temp_data;

                    $send_data_wx[$key]['promotion_demand_no'] = $temp_data['promotion_demand_no'];
                    $send_data_wx[$key]['promotion_task_no'] = $temp_data['promotion_task_no'];
                    $send_data_wx[$key]['promotion_type_cd'] = $demand_info['promotion_type_cd'];
                    $send_data_wx[$key]['platform_cd'] = $demand_info['platform_cd'];
                    $send_data_wx[$key]['channel_platform_name'] = $temp_data['channel_platform_name'];
                    $send_data_wx[$key]['channel_medium_name'] = $temp_data['channel_medium_name'];
                    $send_data_wx[$key]['send_by'] = $demand_info['create_by'];
                    $send_data_wx[$key]['promontion_price'] = $temp_data['promontion_price'];
                    $send_data_wx[$key]['forecast_sum_currency_cd'] = $temp_data['forecast_sum_currency_cd'];
                    $send_data_wx[$key]['promontion_currency_cd'] = $temp_data['promontion_currency_cd'];

                }
                // 更新推广状态
                $promotion_demand_no_data = array_unique(array_column($insert_data,'promotion_demand_no'));
                if ($promotion_demand_no_data){
                    $condtion = array(
                        'promotion_demand_no' => array('in',$promotion_demand_no_data)
                    );
                    $update_data = array('status_cd'=>PromotionDemandAction::$status_already_claim_cd);
                    $res = $promotionDemandService->update($condtion,$update_data);
                    if ($res === false){
                        throw  new  Exception('更新推广需求状态失败');
                    }
                }

                $res = (new PromotionTaskService($model))->addAllMany($insert_data,$channel_data,$activity_data);

            }else{
                throw  new  Exception('请求参数不能为空');
            }
            $model->commit();

            // 认领完成 企业微信通知
            (new PromotionTaskService())->claimWxMessage($send_data_wx);


        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess($res);
    }

    /**
     * 参数验证
     * @param $request_data
     * @throws Exception
     */
    public function createValidate($datum,$demand_info) {
        $rules = array(
            'promotion_demand_no' => 'required',
            'channel_platform_id' => 'required',
            'channel_platform_name' => 'required',
            'channel_platform_tag' => 'required',
            'channel_medium_id' => 'required',
            'channel_medium_name' => 'required',
            'channel_medium_tag' => 'required',
            'activity_tag' => 'required',
            'link_tracking' => 'required',
            'forecast_sum_price' => 'required',
            'forecast_sum_currency_cd' => 'required',
            'forecast_rol' => 'required',

        );
        $custom_attributes = array(
            'promotion_demand_no' => '推广需求编号',
            'channel_platform_id' => '渠道平台ID',
            'channel_platform_name' => '渠道平台名称',
            'channel_platform_tag' => '渠道平台标签',
            'channel_medium_id' => '渠道媒介ID',
            'channel_medium_name' => '渠道媒介名称',
            'channel_medium_tag' => '渠道媒介标签',
            'activity_tag' => '活动标签',
            'link_tracking' => '带标签推广链接',

            'forecast_sum_price' => '预估总费用',
            'forecast_sum_currency_cd' => '预估总费用币种',
            'forecast_rol' => '预估ROI',
        );

        if (empty($demand_info)){
            throw new Exception('推广需求不存在:'.$datum['promotion_demand_no']);
        }

        // 活动标签只能是字母或者数字
        if (!preg_match('/^[a-zA-Z0-9]+$/',$datum['activity_tag'])){
            throw new Exception('录入的活动标签有误');
        }

        if (!preg_match('/^[0-9]+(\.?[0-9]+)?$/',$datum['forecast_sum_price']) || $datum['forecast_sum_price'] < 0){
            throw new Exception('预估总费用输入有误');
        }

        if (!preg_match('/^[0-9]+(\.?[0-9]+)?$/',$datum['forecast_rol']) || $datum['forecast_rol'] < 0){
            throw new Exception('预估ROI输入有误');
        }


        if ($demand_info['status_cd'] == PromotionDemandAction::$status_rebuttal_cd ||
            $demand_info['status_cd'] == PromotionDemandAction::$status_close_cd){
            throw new Exception('需求状态不符合认领条件:'.$datum['promotion_demand_no']);
        }
        $this->validate($rules, $datum, $custom_attributes);
    }


    /**
     *  列表数据
     */
    public function getList(){
        $request_data = DataModel::getDataNoBlankToArr();
        $list = (new PromotionTaskService())->getList($request_data);
        $this->ajaxSuccess($list);
    }

    public function export(){
        session_write_close();
        set_time_limit(0);
        $request_data = $_POST;
        $list = (new PromotionTaskService())->getList($request_data,true);
        $datas = $list['datas'];
        $map  = [
            ['field_name' => 'promotion_task_no', 'name' => L('推广任务ID')],
            ['field_name' => 'promotion_demand_no', 'name' => L('推广需求ID')],
            ['field_name' => 'status_cd_val', 'name' => L('状态')],
            ['field_name' => 'coupon_source_cd_val', 'name' => L('优惠码来源')],
            ['field_name' => 'coupon', 'name' => L('coupon')],
            ['field_name' => 'platform_cd_val', 'name' => L('平台')],
            ['field_name' => 'promotion_link', 'name' => L('推广链接')],
            ['field_name' => 'promotion_line_time', 'name' => L('推广上线时间')],
            ['field_name' => 'feedback', 'name' => L('进度反馈')],
            ['field_name' => 'sku_id', 'name' => L('SKU（ERP的SKU编码）')],
            ['field_name' => 'product_name', 'name' => L('商品名称')],
            ['field_name' => 'product_attribute', 'name' => L('商品属性')],
            ['field_name' => 'link_tracking', 'name' => L('商品链接/活动页链接/站点链接（带Tracking Code）')],
            ['field_name' => 'area_name', 'name' => L('推广国家')],
            ['field_name' => 'currency_cd_val', 'name' => L('推广价格（币种）')],
            ['field_name' => 'promotion_pirce', 'name' => L('推广价格')],
            ['field_name' => 'channel_platform_name', 'name' => L('推广平台')],
            ['field_name' => 'channel_medium_name', 'name' => L('推广媒介')],
            ['field_name' => 'forecast_sum_currency_cd_val', 'name' => L('预估总花费（币种）')],
            ['field_name' => 'forecast_sum_price', 'name' => L('预估总花费')],
            ['field_name' => 'forecast_sum_price_usd', 'name' => L('预估总花费（USD）')],
            ['field_name' => 'now_total_price_uds', 'name' => L('当前累计花费（USD')],
            ['field_name' => 'forecast_rol', 'name' => L('预估ROI')],
            ['field_name' => 'demand_create_at', 'name' => L('需求日期')],
            ['field_name' => 'create_at', 'name' => L('认领日期')],
            ['field_name' => 'site_cd_val', 'name' => L('站点')],
            ['field_name' => 'demand_create_by', 'name' => L('需求人')],
            ['field_name' => 'create_by', 'name' => L('认领人')],
            ['field_name' => 'return_reason', 'name' => L('退回原因')],
            ['field_name' => 'dis_product_pirce', 'name' => L('优惠前商品价格（页面币种）')],
            ['field_name' => 'profit', 'name' => L('需求方利润率范围')],
            ['field_name' => 'type_cd_val', 'name' => L('标签类型')],
            ['field_name' => 'tag_name', 'name' => L('标签名称')],
        ];
        (new PromotionTaskService())->exportCsv($datas, $map);
    }




    /**
     *  编辑 确认优惠码【1】 - 编辑推广进展【2】 - 确认完成【3】  - 确认退回【4】
     */
    public function edit(){
        try{
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];
            $model->startTrans();
            $promotionTaskService = new PromotionTaskService($model);
            $send_data_wx = array();
            if (!empty($post_data)){
                foreach ( $post_data as $key => $datum){
                    $condtion = array(
                        'promotion_task_no' => $datum['promotion_task_no']
                    );
                    $info_data = $promotionTaskService->getFind($condtion,'promotion_demand_no,status_cd,create_by,channel_platform_name,channel_medium_name');
                    if (empty($info_data)){
                        throw new Exception("推广任务不存在");
                    }
                    $send_data_wx[$key]['promotion_task_no'] = $datum['promotion_task_no'];
                    $send_data_wx[$key]['promotion_demand_no'] = $datum['promotion_demand_no'];
                    $send_data_wx[$key]['type'] = $datum['type'];
                    $send_data_wx[$key]['promotion_link'] = $datum['promotion_link'];
                    $send_data_wx[$key]['feedback'] = $datum['feedback'];
                    $send_data_wx[$key]['send_by'] = $info_data['create_by'];
                    $send_data_wx[$key]['return_reason'] = $datum['return_reason'];


                    switch ($datum['type']){
                        case  1 :


                            if ($info_data['status_cd'] != self::$status_feed_cd){
                                throw new Exception('推广任务状态不符合确认优惠码条件');
                            }

                            // 平台
                            if (empty($datum['platform_cd'])){//self::$coupon_source_erp
                                // throw new Exception("请选择推广任务ID为{$info_data['promotion_demand_no']}的平台");
                                throw new Exception("请选择平台"); // 产品Calvin要求更改 11373 https://shimo.im/docs/3DVYXPcpQRWxk9P6/  问题3
                            }

                            if ($info_data['channel_platform_name'] == 'EmarsysEDM' && $info_data['channel_medium_name'] == 'EDM')
                            {

                            } else { // #11373 不同推广类型区分流程
                               // 待反馈优惠码
                               if (empty($datum['coupon'])){
                                   throw new Exception("请输入优惠码");
                               }
                               // 优惠码来源
                               if (empty($datum['coupon_source_cd'])){//self::$coupon_source_erp
                                   throw new Exception("优惠码来源必填");
                               }     
                               //系统优惠码
                               if ($datum['coupon_source_cd'] == self::$coupon_source_erp) {
                                   // 验证优惠码CMS 是否存在
                                   $coupon_info = (new PromotionTaskService())->getCouponFind(array('cms_promo_code.promo_code ' => $datum['coupon']));
                                   if (empty($coupon_info)){
                                       throw new Exception("优惠码{$datum['coupon']} 不存在，请先去创建该优惠码");
                                   }
                               }
                            }

                            
                            $save_data = array(
                                'coupon' => $datum['coupon'],
                                'status_cd' => self::$status_being_cd,
                                //更新优惠码来源
                                'coupon_source_cd' => $datum['coupon_source_cd'],
                                'platform_cd' => $datum['platform_cd'],
                                'update_at' => date("Y-m-d H:i:s"),
                                'update_by' => userName()
                            );
                            $res = $promotionTaskService->update($condtion,$save_data);
                            if (!$res){
                                throw new Exception('更新失败-确认优惠码');
                            }
                            $info = (new PromotionTaskRepository())->getJoinFind($condtion,'tb_ms_promotion_demand.create_by');
                            $send_data_wx[$key]['demand_create_by'] = $info['create_by'];
                            break;
                        case 2 :
                            // 编辑推广进展
                            if (empty($datum['promotion_link'])){
                                throw new Exception("请输入推广链接");
                            }
                            if (empty($datum['feedback'])){
                                throw new Exception("请输入推广进展");
                            }
                            if ($info_data['status_cd'] != self::$status_being_cd){
                                throw new Exception('推广任务状态不符合编辑推广进展条件');
                            }

                            $save_data = array(
                                'promotion_link' => $datum['promotion_link'],
                                'promotion_line_time' => $datum['promotion_line_time'],
                                'feedback' => $datum['feedback'],
                                'update_at' => date("Y-m-d H:i:s"),
                                'update_by' => userName()
                            );
                            $res = $promotionTaskService->update($condtion,$save_data);
                            if (!$res){
                                throw new Exception('更新失败-推广进展');
                            }

                            $info = (new PromotionTaskRepository())->getJoinFind($condtion,'tb_ms_promotion_demand.create_by');
                            $send_data_wx[$key]['send_by'] = $info['create_by'];

                            break;
                        case 3 :
                            // 确认完成
                            if ($info_data['status_cd'] != self::$status_being_cd){
                                throw new Exception('推广任务状态不符合确认完成条件');
                            }
                            $save_data = array(
                                'status_cd' => self::$status_accom_cd,
                                'update_at' => date("Y-m-d H:i:s"),
                                'update_by' => userName()
                            );
                            $res = $promotionTaskService->update($condtion,$save_data);
                            if (!$res){
                                throw new Exception('更新失败-推广进展');
                            }
                            break;
                        case 4 :
                            // 确认退回
                            if ($info_data['status_cd'] == self::$status_being_cd || $info_data['status_cd'] == self::$status_feed_cd){
                                $save_data = array(
                                    'status_cd' => self::$status_returned_cd,
                                    'return_reason' => $datum['return_reason'],
                                    'update_at' => date("Y-m-d H:i:s"),
                                    'update_by' => userName()
                                );

                                $res = $promotionTaskService->update($condtion,$save_data);
                                if (!$res){
                                    throw new Exception('更新失败-推广进展');
                                }
                                $info = (new PromotionTaskRepository())->getJoinFind($condtion,'tb_ms_promotion_demand.create_by');
                                $send_data_wx[$key]['send_by'] = $info['create_by'];

                            }else{
                                throw new Exception('推广任务状态不符合退回条件');
                            }
                            break;
                        default:
                            throw new Exception('更新状态异常');
                    }
                }
            }else{
                throw  new  Exception('请勾选相应的推广任务');
            }
            $model->commit();

            // 发送企业微信通知
            (new PromotionTaskService())->editWxMessage($send_data_wx);

        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess();

    }

    /**
     *  查询优惠码信息
     */
    public function searchCoupon(){
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['coupon'])){
            $this->ajaxError([],'请输入优惠码',4000);
        }
        $condtion = array(
            'cms_promo_code.promo_code ' => $request_data['coupon'],
        );
        $field = '*';
        $data_info  = (new PromotionTaskService())->getCouponFind($condtion,$field);
        $data_info = CodeModel::autoCodeOneVal($data_info,['order_amount_currency_cd','reduction_amount_currency_cd']);
        $coupon_info = [];
        if ($data_info){
            $coupon_info['activity_name'] = $data_info['activity_name'];
            if (empty($data_info['site_cds'])){
                $coupon_info['site_cds'] = 'All';
            }else{
                $site_cds = explode(',',$data_info['site_cds']);
                $site_data = M('cmn_cd','tb_ms_')->field('CD_VAL')->where(array('CD'=>array('in',$site_cds)))->select();
                $coupon_info['site_cds'] = implode(',',array_column($site_data,'CD_VAL'));
            }
            if (empty($data_info['sku_ids'])){
                $coupon_info['sku_ids'] = 'All';
            }else{
                $coupon_info['sku_ids'] = $data_info['sku_ids'];
            }
            $coupon_info['reduction_type_val'] = "";
            if ($data_info['reduction_type'] == 0){
                $coupon_info['reduction_type_val'] = "满 {$data_info['order_amount_currency_cd_val']} {$data_info['order_amount']} 减 {$data_info['reduction_amount_currency_cd_val']} {$data_info['reduction_amount']}";
            }elseif ($data_info['reduction_type'] == 1){
                $discount = bcsub(1 , $data_info['discount'],2) * 100;
                $coupon_info['reduction_type_val'] = "满 {$data_info['order_amount_currency_cd_val']} {$data_info['order_amount']} 减 {$discount}%";
            }
            if ($data_info['is_show'] == 1) {
                $coupon_info['is_show'] = '商品详情页';
            } else {
                $coupon_info['is_show'] = '不展示';
            }
            $coupon_info['time_limit_start'] = $data_info['time_limit_start'];
            $coupon_info['time_limit_end'] = $data_info['time_limit_end'];

            if (empty($data_info['usage_count_limit'])){
                $coupon_info['usage_count_limit'] = '不限制使用次数';
            }else{
                $coupon_info['usage_count_limit'] = $data_info['usage_count_limit'];
            }
            $coupon_info['used_count'] =  $data_info['used_count'];
        }
        $this->ajaxSuccess($coupon_info);
    }


    //获取优惠码
    public function getCoupon()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr()['search'];
            $response  = (new PromotionTaskService())->getCoupon($request_data);
            $this->ajaxSuccess($response);
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),4000);
        }
    }


    //编辑单个的推广任务的附件，存字段，文件上传
    public function updateAttachment() {
        $fd = new FileUploadModel();
        $res = $fd->uploadFileArr();
        if ($res) {
            $params = $this->params();
            $id = $params['id'];
            $res2 = (new PromotionTaskService())->updateAttach($id, $res);
            if($res2){
                $this->ajaxSuccess($res2,'更新附件成功');
            }else{
                $this->ajaxError('','更新附件失败','');
            }
        } else {
            $this->error($fd->error, '', true);
        }
    }




    public function test(){
        (new PromotionTaskService())->test();
    }


}