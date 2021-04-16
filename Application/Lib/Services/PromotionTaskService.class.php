<?php
/*
 *
 */
class PromotionTaskService extends Service
{

    protected $repository;

    protected $model = "";
    protected $promotion_demand_table = "";
    protected $promotion_task_table = "";

    protected  $get_promotion_task_url = 'index.php?m=index&a=index&source=email&actionType=promotionTask&id=';
    protected  $get_promotion_demand_url = 'index.php?m=index&a=index&source=email&actionType=promotionDemand&id=';

    public function __construct($model)
    {
        $this->model = empty($model) ? new Model() : $model;
        $this->promotion_demand_table = M('promotion_demand','tb_ms_');
        $this->promotion_task_table = M('promotion_task','tb_ms_');
        $this->repository = new PromotionTaskRepository($this->model);
    }

    public function adjustTaskInfoList($data)
    {
        $promotion_demand_nos = array_column($data, 'promotion_demand_no');
        if (empty($promotion_demand_nos)) {
            throw  new  Exception('参数异常');
        }
        $condtion = array(
            'promotion_demand_no' => array('in', $promotion_demand_nos)
        );
        $promotionDemandService = new PromotionDemandService();
        $list = $promotionDemandService->cloneDemandList($condtion);
        $tempList = []; $finalList = [];
        foreach ($list as $key => $item){
            $tempList[$item['promotion_demand_no']] = $item;
            if ($item['promotion_type_cd'] != 'N003610003'){
                $tempList[$item['promotion_demand_no']]['dis_product_pirce'] = "";
                $tempList[$item['promotion_demand_no']]['dis_product_pirce_back'] = "";
                $tempList[$item['promotion_demand_no']]['dis_product_pirce_front'] = "";
            }
        }
        foreach ($data as $key => $value) {
            $finalList[$key] = $tempList[$value['promotion_demand_no']];
            $finalList[$key]['promotion_task_no'] = $value['promotion_task_no'];
            $finalList[$key]['forecast_sum_price'] = $value['forecast_sum_price'];
            $finalList[$key]['forecast_sum_currency_cd'] = $value['forecast_sum_currency_cd'];
            $finalList[$key]['forecast_rol'] = $value['forecast_rol'];
        }
        return $finalList;      
    }
    public function getTaskInfo($data)
    {
        $search_data = $data['search'];
        if (isset($search_data['promotion_task_no']) && !empty($search_data['promotion_task_no'])){
            $condtion['tb_ms_promotion_task.promotion_task_no'] = array('in',explode(',',$search_data['promotion_task_no']));
        }
        $field = "
        tb_ms_promotion_task.promotion_task_no,
        tb_ms_promotion_task.forecast_sum_price,
        tb_ms_promotion_task.forecast_sum_currency_cd,
        tb_ms_promotion_task.forecast_rol,
        tb_ms_promotion_task.promotion_demand_no
        ";
        $list = $this->repository->getList($condtion,$field,'');
        $list = $this->adjustTaskInfoList($list);
        return $list;
    }


    public function createPromotionTaskNo($promotion_task_no) {
        if (empty($promotion_task_no)){
            $promotion_task_no = $this->promotion_task_table->lock(true)->where(['promotion_task_no'=>['like','TGRW'.date('Ymd').'%']])->order('id desc')->getField('promotion_task_no');
        }
        if($promotion_task_no) {
            $num = substr($promotion_task_no,-4)+1;
        }else {
            $num = 1;
        }
        $promotion_task_no = 'TGRW'.date('Ymd').substr(10000+$num,1);
        return $promotion_task_no;
    }

    /**
     *  添加数据【单条】
     */
    public function add($insert_data){
        $res = $this->repository->add($insert_data);
        return $res;
    }

    /**
     * 更新
     */
    public function update($condtion,$update_data){
        $res = $this->repository->update($condtion,$update_data);
        return $res;
    }

    /**
     *  添加数据【批量】
     */
    public function addAllMany($insert_data,$channel_data,$activity_data){

        $res = $this->repository->addAll($insert_data);
        if (!$res){
            throw new Exception('认领推广需求失败');
        }

        // 渠道数据处理
        $url = INSIGHT.'/insight-backend/gpChannel/bulkInsert';
        $response = $this->requsetInsightJson($url,$channel_data);
        $channel_key_value = array();
        if ($response){
            $response = json_decode($response,true);
            if (isset($response['code']) &&  $response['code'] == 200000){
                // 更新任务渠道信息
                foreach ($response['datas'] as $value){
                    $update_data = array(
                        'channel_id' => $value['id'],
                        'channel_name' => $value['channel_name'],
                        'channel_tag' =>$value['channel_tag']
                    );
                    $res = $this->repository->update(array('promotion_task_no'=>$value['promotion_task_no']),$update_data);
                    if (!$res){
                        throw new Exception('更新渠道信息异常');
                    }
                    $channel_key_value[$value['promotion_task_no']] = $value['id'];
                }
            }else{
                throw new Exception('批量创建渠道失败:'.$response['msg']);
            }
        }else{
            throw new Exception('批量创建渠道通讯异常');
        }

        // 活动数据处理
        foreach ($activity_data as &$datum ){
            if (isset($channel_key_value[$datum['promotion_task_no']]) && !empty($channel_key_value[$datum['promotion_task_no']])){
                $datum['channel_id'] = $channel_key_value[$datum['promotion_task_no']];
            }
        }
        unset($datum);
        $url = INSIGHT.'/insight-backend/gpActivity/bulkInsert';
        $response = $this->requsetInsightJson($url,$activity_data);
        if ($response){
            $response = json_decode($response,true);
            if (isset($response['code']) &&  $response['code'] == 200000){
                // 更新任务活动信息
                foreach ($response['datas'] as $value){
                    $update_data = array(
                        'activity_id' => $value['id'],
                        'link_tracking'=>$value['act_url'],
                    );
                    $this->repository->update(array('promotion_task_no'=>$value['promotion_task_no']),$update_data);
                }
            }else{
                throw new Exception('批量创建活动失败:'.$response['msg']);
            }
        }else{
            throw new Exception('批量创建活动通讯异常');
        }

        return $res;
    }



    public function addAllSeveral($insert_data,$channel_data,$activity_data){

        $res = $this->repository->addAll($insert_data);
        if (!$res){
            throw new Exception('认领推广需求失败');
        }
        $promotion_task_nos = array_column($insert_data,'promotion_task_no');
        // 渠道数据处理
        $url = INSIGHT.'/insight-backend/gpChannel/bulkInsert';
        $response = $this->requsetInsightJson($url,$channel_data);
        if ($response){
            $response = json_decode($response,true);
            if (isset($response['code']) &&  $response['code'] == 200000){
                // 更新任务渠道信息
                foreach ($response['datas'] as $value){
                    $update_data = array(
                        'channel_id' => $value['id'],
                        'channel_name' => $value['channel_name'],
                        'channel_tag' =>$value['channel_tag']
                    );
                    $res = $this->repository->update(array('promotion_task_no'=>array('in',$promotion_task_nos)),$update_data);
                    if (!$res){
                        throw new Exception('更新渠道信息异常');
                    }
                }
            }else{
                throw new Exception('批量创建渠道失败:'.$response['msg']);
            }
        }else{
            throw new Exception('批量创建渠道通讯异常');
        }
        $activity_data[0]['channel_id'] = $response['datas'][0]['id'];

        $url = INSIGHT.'/insight-backend/gpActivity/bulkInsert';
        $response = $this->requsetInsightJson($url,$activity_data);
        if ($response){
            $response = json_decode($response,true);
            if (isset($response['code']) &&  $response['code'] == 200000){
                // 更新任务活动信息
                foreach ($response['datas'] as $value){
                    $update_data = array(
                        'activity_id' => $value['id'],
                        'link_tracking'=>$value['act_url'],
                    );
                    $res = $this->repository->update(array('promotion_task_no'=>array('in',$promotion_task_nos)),$update_data);
                }
            }else{
                throw new Exception('批量创建活动失败:'.$response['msg']);
            }
        }else{
            throw new Exception('批量创建活动通讯异常');
        }
        return $res;
    }
    /**
     *   json 格式请求
     */
    public function requsetInsightJson($url,$requset){

        Logs("请求-url-".date("Y-m-d H:i:s")."----".$url,__FUNCTION__,__CLASS__);
        $requset = json_encode($requset);
        Logs("请求-requset_data-".date("Y-m-d H:i:s")."----".$requset,__FUNCTION__,__CLASS__);
        $header  = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($requset) ,
            'erp-req: true' ,
            'erp-cookie: PHPSESSID='.session_id().';' );
        Logs("请求-header_data-".date("Y-m-d H:i:s")."----".json_encode($header),__FUNCTION__,__CLASS__);
        $response = $this->insightCurl($url,$requset,$header);
        Logs("响应-".date("Y-m-d H:i:s")."----".$response,__FUNCTION__,__CLASS__);
        return $response;
    }

    /**
     *   表单 格式请求
     */
    public function requsetInsightForm($url,$requset){

        Logs("请求-url-".date("Y-m-d H:i:s")."----".$url,__FUNCTION__,__CLASS__);
        $requset = http_build_query($requset);
        $requset = urldecode($requset);
        Logs("请求-requset_data-".date("Y-m-d H:i:s")."----".$requset,__FUNCTION__,__CLASS__);
        $header  = array(
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'Content-Length: ' . strlen($requset) ,
            'erp-req: true' ,
            'erp-cookie: PHPSESSID='.session_id().';' );
        Logs("请求-header_data-".date("Y-m-d H:i:s")."----".json_encode($header),__FUNCTION__,__CLASS__);
        $response = $this->insightCurl($url,$requset,$header);
        Logs("响应-".date("Y-m-d H:i:s")."----".$response,__FUNCTION__,__CLASS__);
        return $response;
    }


    public function insightCurl($url, $requset,$header = array(), $cookie = '', $timeout = 60){
        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);          //单位 秒，也可以使用
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requset);//$data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($header){
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        $data = curl_exec($ch);
        return $data;
    }




    public function getFind($condtion, $field = "*"){
        $info_data = $this->repository->getFind($condtion,$field);
        $info_data = DataModel::formatAmount($info_data);
        return $info_data;
    }
    public function getList($request_data,$is_excel = false){
        if ($is_excel){
            $request_data = json_decode($request_data['post_data'],true);
            $search_data = $request_data['search'];
            $sort_data = $request_data['sort_data'];
            $count =  0;
            $limit = false;
            $condtion = $this->searchWhere($search_data);
        }else{
            $search_data = $request_data['search'];
            $pages_data = $request_data['pages'];
            $sort_data = $request_data['sort_data'];
            if (empty($pages_data)){
                $pages_data = array(
                    'per_page' => 10,
                    'current_page' => 1
                );
            }
            $condtion = $this->searchWhere($search_data);
            $count = $this->repository->getList($condtion,'count(*) as total_rows');
            $limit = ($pages_data['current_page'] - 1) * $pages_data['per_page'].' , '.$pages_data['per_page'];
        }
        $field = "
            tb_ms_promotion_task.promotion_demand_no,
            tb_ms_promotion_task.promotion_task_no,
            tb_ms_promotion_task.status_cd,
            tb_ms_promotion_task.coupon,
            tb_ms_promotion_task.promotion_link,
            tb_ms_promotion_task.channel_name,
            tb_ms_promotion_task.channel_tag,
            tb_ms_promotion_task.channel_platform_name,
            tb_ms_promotion_task.channel_platform_tag,
            tb_ms_promotion_task.channel_medium_name,
            tb_ms_promotion_task.channel_medium_tag,
            tb_ms_promotion_task.activity_name,
            tb_ms_promotion_task.activity_tag,
            tb_ms_promotion_task.link_tracking,
            tb_ms_promotion_task.forecast_sum_price,
            tb_ms_promotion_task.forecast_sum_currency_cd,
            tb_ms_promotion_task.forecast_rol,
            tb_ms_promotion_task.feedback,
            tb_ms_promotion_task.return_reason,
            tb_ms_promotion_task.create_at,
            tb_ms_promotion_task.create_by,
            tb_ms_promotion_task.update_by,
            tb_ms_promotion_task.update_at,
            tb_ms_promotion_task.promotion_line_time,
            tb_ms_promotion_task.coupon_source_cd,
            tb_ms_promotion_task.attachment,
            tb_ms_promotion_task.platform_cd,
            tb_ms_promotion_demand.create_by as demand_create_by,
            tb_ms_promotion_demand.create_at as demand_create_at,
            tb_ms_promotion_demand.site_cd,
            tb_ms_promotion_demand.sku_id,
            tb_ms_promotion_demand.area_id,
            tb_ms_promotion_demand.area_name,
            tb_ms_promotion_demand.product_name,
            tb_ms_promotion_demand.product_attribute,
            tb_ms_promotion_demand.product_attribute,
            tb_ms_promotion_demand.product_image,
            tb_ms_promotion_demand.rebuttal_reasons,
            tb_ms_promotion_demand.dis_product_pirce,
            tb_ms_promotion_demand.currency_cd,
            tb_ms_promotion_demand.profit_front,
            tb_ms_promotion_demand.profit_back,
            tb_ms_promotion_demand.promotion_pirce,
            tb_ms_promotion_tag.type_cd,
            tb_ms_promotion_tag.tag_name
             ";
        // 排序处理
        if (isset($sort_data['field']) && !empty($sort_data['field'])
            && isset($sort_data['sort_value']) && !empty($sort_data['sort_value'])){
            $sort_field =  trim($sort_data['field']);
            switch ($sort_field){
                case 'forecast_rol':
                    // 预估ROL
                    $order_by = 'tb_ms_promotion_task.forecast_rol '.$sort_data['sort_value'];
                    $list = $this->repository->getList($condtion,$field,$limit,$order_by);
                    break;
                case 'create_at':
                    // 认领日期
                    $order_by = 'tb_ms_promotion_task.create_at '.$sort_data['sort_value'];
                    $list = $this->repository->getList($condtion,$field,$limit,$order_by);
                    break;
                case 'forecast_sum_price_xchr':
                    // 预估总花费（USD）
                    $order_by = ' forecast_sum_price_xchr '.$sort_data['sort_value'];
                    $field .=",(
                    SELECT
                    CASE
                            tb_ms_cmn_cd.CD_VAL 
                            WHEN 'USD' THEN
                            tb_ms_xchr.USD_XCHR_AMT_CNY 
                            WHEN 'EUR' THEN
                            tb_ms_xchr.EUR_XCHR_AMT_CNY 
                            WHEN 'HKD' THEN
                            tb_ms_xchr.HKD_XCHR_AMT_CNY 
                            WHEN 'SGD' THEN
                            tb_ms_xchr.SGD_XCHR_AMT_CNY 
                            WHEN 'AUD' THEN
                            tb_ms_xchr.AUD_XCHR_AMT_CNY 
                            WHEN 'GBP' THEN
                            tb_ms_xchr.GBP_XCHR_AMT_CNY 
                            WHEN 'CAD' THEN
                            tb_ms_xchr.CAD_XCHR_AMT_CNY 
                            WHEN 'MYR' THEN
                            tb_ms_xchr.MYR_XCHR_AMT_CNY 
                            WHEN 'DEM' THEN
                            tb_ms_xchr.DEM_XCHR_AMT_CNY 
                            WHEN 'MXN' THEN
                            tb_ms_xchr.MXN_XCHR_AMT_CNY 
                            WHEN 'THB' THEN
                            tb_ms_xchr.THB_XCHR_AMT_CNY 
                            WHEN 'PHP' THEN
                            tb_ms_xchr.PHP_XCHR_AMT_CNY 
                            WHEN 'IDR' THEN
                            tb_ms_xchr.IDR_XCHR_AMT_CNY 
                            WHEN 'TWD' THEN
                            tb_ms_xchr.TWD_XCHR_AMT_CNY 
                            WHEN 'VND' THEN
                            tb_ms_xchr.VND_XCHR_AMT_CNY 
                            WHEN 'KRW' THEN
                            tb_ms_xchr.KRW_XCHR_AMT_CNY 
                            WHEN 'JPY' THEN
                            tb_ms_xchr.JPY_XCHR_AMT_CNY 
                            WHEN 'CNY' THEN
                            tb_ms_xchr.CNY_XCHR_AMT_CNY 
                            WHEN 'NGN' THEN
                            tb_ms_xchr.NGN_XCHR_AMT_CNY 
                        END 
                        FROM
                            tb_ms_xchr 
                        WHERE
                            XCHR_STD_DT =  FROM_UNIXTIME(unix_timestamp(tb_ms_promotion_task.create_at),'%Y%m%d') 
                            LIMIT 1 
                        ) * tb_ms_promotion_task.forecast_sum_price AS forecast_sum_price_xchr ";
                    $list = $this->repository->getForecastSumPriceList($condtion,$field,$limit,$order_by);
                    break;
                case 'now_total_price_xchr':
                    $order_by = ' now_total_price_xchr '.$sort_data['sort_value'];
                    $field .= ",SUM((subtotal / ( SELECT SUM( subtotal ) FROM tb_general_payment_detail WHERE tb_general_payment_detail.payment_audit_id =    		  payment_detail_one.payment_audit_id ) * tb_pur_payment_audit.billing_amount ) * (
                            SELECT
                            CASE
                                    tb_ms_cmn_cd.CD_VAL 
                                    WHEN 'USD' THEN
                                    tb_ms_xchr.USD_XCHR_AMT_CNY 
                                    WHEN 'EUR' THEN
                                    tb_ms_xchr.EUR_XCHR_AMT_CNY 
                                    WHEN 'HKD' THEN
                                    tb_ms_xchr.HKD_XCHR_AMT_CNY 
                                    WHEN 'SGD' THEN
                                    tb_ms_xchr.SGD_XCHR_AMT_CNY 
                                    WHEN 'AUD' THEN
                                    tb_ms_xchr.AUD_XCHR_AMT_CNY 
                                    WHEN 'GBP' THEN
                                    tb_ms_xchr.GBP_XCHR_AMT_CNY 
                                    WHEN 'CAD' THEN
                                    tb_ms_xchr.CAD_XCHR_AMT_CNY 
                                    WHEN 'MYR' THEN
                                    tb_ms_xchr.MYR_XCHR_AMT_CNY 
                                    WHEN 'DEM' THEN
                                    tb_ms_xchr.DEM_XCHR_AMT_CNY 
                                    WHEN 'MXN' THEN
                                    tb_ms_xchr.MXN_XCHR_AMT_CNY 
                                    WHEN 'THB' THEN
                                    tb_ms_xchr.THB_XCHR_AMT_CNY 
                                    WHEN 'PHP' THEN
                                    tb_ms_xchr.PHP_XCHR_AMT_CNY 
                                    WHEN 'IDR' THEN
                                    tb_ms_xchr.IDR_XCHR_AMT_CNY 
                                    WHEN 'TWD' THEN
                                    tb_ms_xchr.TWD_XCHR_AMT_CNY 
                                    WHEN 'VND' THEN
                                    tb_ms_xchr.VND_XCHR_AMT_CNY 
                                    WHEN 'KRW' THEN
                                    tb_ms_xchr.KRW_XCHR_AMT_CNY 
                                    WHEN 'JPY' THEN
                                    tb_ms_xchr.JPY_XCHR_AMT_CNY 
                                    WHEN 'CNY' THEN
                                    tb_ms_xchr.CNY_XCHR_AMT_CNY 
                                    WHEN 'NGN' THEN
                                    tb_ms_xchr.NGN_XCHR_AMT_CNY 
                                END 
                                FROM
                                    tb_ms_xchr 
                                WHERE
                                    XCHR_STD_DT = FROM_UNIXTIME( unix_timestamp( tb_pur_payment_audit.billing_date ), '%Y%m%d' ) 
                                    LIMIT 1 
                                )) AS now_total_price_xchr";
                    $list = $this->repository->getNowTotalPriceList($condtion,$field,$limit,$order_by);
                    break;
                default:
            }
        }else{
            $list = $this->repository->getList($condtion,$field,$limit);
        }
        $list = CodeModel::autoCodeTwoVal($list,['status_cd','promotion_type_cd','platform_cd','site_cd','currency_cd','forecast_sum_currency_cd','promontion_currency_cd','currency_cd','type_cd', 'coupon_source_cd']);
        foreach ($list as $key => $value){
            // 预估总花费（USD）
            $list[$key]['forecast_sum_price_usd'] = round( exchangeRateConversion($value['forecast_sum_currency_cd_val'],"USD",date('Ymd',strtotime($value['create_at']))) * $value['forecast_sum_price'],2);
            // 当前累计花费（USD）↕
            $list[$key]['now_total_price_uds'] = $this->disNowTotalPriceUsd($value['promotion_task_no']) ;
            $list[$key]['profit'] = "{$list[$key]['profit_front']}% 至 {$list[$key]['profit_back']}%";
            if ( strtotime($list[$key]['promotion_line_time']) == 0 ){
                $list[$key]['promotion_line_time'] = "";
            }
            //添加一个完整的路径，改名为任务id加后缀
            if(!empty($list[$key]['attachment'])){
                $attachment = json_decode($list[$key]['attachment'],true);
                $list[$key]['file_save_name'] =  $attachment['save_name'];
                $list[$key]['file_origin_name'] =  $attachment['origin_name'];
                $list[$key]['file_show_name'] =  $list[$key]['promotion_task_no'].substr($attachment['origin_name'], strripos($attachment['origin_name'],'.'));
            }else{
                $list[$key]['file_save_name'] = "";
                $list[$key]['file_origin_name']  = "";
                $list[$key]['file_show_name'] =  "";
            }
            $list[$key]['file_real_path'] =  $attachment['save_path_name'];
            $list[$key]['attachment'] =  $attachment['save_path_name'];
            
            if (empty($value['promotion_pirce']) || $value['promotion_pirce'] == 0){
                $list[$key]['promotion_pirce'] = "";
            }
        }

        $list = DataModel::formatAmount($list);
        return array(
            'datas' => $list,
            'page'=>$count
        );
    }

    /**
     *  统计当前累计花费（USD）
     */
    public function disNowTotalPriceUsd($promotion_task_no){
        $condtion = array('relation_bill_no' => $promotion_task_no);
        $field = "payment_detail_one.relation_bill_no,
                tb_pur_payment_audit.billing_currency_cd,
                tb_pur_payment_audit.billing_date,
                (subtotal / ( SELECT SUM( subtotal ) FROM tb_general_payment_detail WHERE tb_general_payment_detail.payment_audit_id =  payment_detail_one.payment_audit_id ) * tb_pur_payment_audit.billing_amount) as sum_subtotal";
        $payment_list  = $this->repository->getPaymentAudit($condtion,$field);
        $now_total_price_usd = 0;
        if ($payment_list){
            $payment_list = CodeModel::autoCodeTwoVal($payment_list,['billing_currency_cd']);
            foreach ($payment_list as $item){
                if ($item['billing_currency_cd'] && $item['billing_date'] && $item['sum_subtotal'] && $item['billing_currency_cd_val'] ){
                    $billing_time = strtotime($item['billing_date']);
                    if ($billing_time > time()){
                        $billing_time = time();
                    }
                    $price_usd = exchangeRateConversion($item['billing_currency_cd_val'],'USD',date('Ymd',$billing_time)) * $item['sum_subtotal'];
                    $now_total_price_usd = bcadd($price_usd,$now_total_price_usd,2);
                }
            }
        }
        return $now_total_price_usd;
    }

    /**
     *  组装列表查询
     * @return array
     */
    private function searchWhere($search_data)
    {
        $condtion = array("1 = 1");
        if (is_array($search_data) && !empty($search_data)){

            //  ID
            if (isset($search_data['ids']) && !empty($search_data['ids'])){
                $condtion['tb_ms_promotion_task.id'] = array('in',explode(',',$search_data['ids']));
            }


            //  推广任务状态
            if (isset($search_data['status_cd']) && !empty($search_data['status_cd'])){
                $condtion['tb_ms_promotion_task.status_cd'] = array('in',explode(',',$search_data['status_cd']));
            }

            // 推广任务编号
            if (isset($search_data['promotion_task_no']) && !empty($search_data['promotion_task_no'])){
                $condtion['tb_ms_promotion_task.promotion_task_no'] = $search_data['promotion_task_no'];
            }

            // 认领人
            if (isset($search_data['create_by']) && !empty($search_data['create_by'])){
                $condtion['tb_ms_promotion_task.create_by'] = array('in',explode(',',$search_data['create_by']));
            }

            // 渠道平台ID
            if (isset($search_data['channel_platform_id']) && !empty($search_data['channel_platform_id'])){
                $condtion['tb_ms_promotion_task.channel_platform_id'] = array('in',explode(',',$search_data['channel_platform_id']));
            }

            // 渠道媒介ID
            if (isset($search_data['channel_medium_id']) && !empty($search_data['channel_medium_id'])){
                $condtion['tb_ms_promotion_task.channel_medium_id'] = array('in',explode(',',$search_data['channel_medium_id']));
            }

            // 认领人
            if (isset($search_data['create_by']) && !empty($search_data['create_by'])){
                $condtion['tb_ms_promotion_task.create_by'] = array('in',explode(',',$search_data['create_by']));
            }

            //  coupon
            if (isset($search_data['coupon']) && !empty($search_data['coupon'])){
                $condtion['coupon'] = array('like','%'.$search_data['coupon'].'%');
            }

            // 推广任务认领日期范围
            if (isset($search_data['create_at_start']) && !empty($search_data['create_at_start'])
            && isset($search_data['create_at_end']) && !empty($search_data['create_at_end'])){
                $condtion['tb_ms_promotion_task.create_at'] = array('between',array($search_data['create_at_start'], date("Y-m-d",strtotime('+1 day',strtotime($search_data['create_at_end'])))));
            }else{
                if (isset($search_data['create_at_start']) && !empty($search_data['create_at_start'])){
                    $condtion['tb_ms_promotion_task.create_at'] = array('EGT',$search_data['create_at_start']);
                }
                if (isset($search_data['create_at_end']) && !empty($search_data['create_at_end'])){
                    $condtion['tb_ms_promotion_task.create_at'] = array('elt',date("Y-m-d",strtotime('+1 day',strtotime($search_data['create_at_end']))));
                }
            }

            // 推广链接
            if (isset($search_data['promotion_link']) && !empty($search_data['promotion_link'])){
                $condtion['tb_ms_promotion_task.link_tracking'] = array('like','%'.$search_data['promotion_link'].'%');
            }


            // 推广需求编号
            if (isset($search_data['promotion_demand_no']) && !empty($search_data['promotion_demand_no'])){
                $condtion['tb_ms_promotion_task.promotion_demand_no'] = $search_data['promotion_demand_no'];
            }
            //  平台
            if (isset($search_data['platform_cd']) && !empty($search_data['platform_cd'])){
                $condtion['platform_cd'] = array('in',explode(',',$search_data['platform_cd']));
            }
            //  站点
            if (isset($search_data['site_cd']) && !empty($search_data['site_cd'])){
                $condtion['site_cd'] = array('in',explode(',',$search_data['site_cd']));
            }
            //  SKU_ID
            if (isset($search_data['sku_id']) && !empty($search_data['sku_id'])){
                $condtion['sku_id'] = array('like','%'.$search_data['sku_id'].'%');
            }

            // 推广需求 日期范围
            if (isset($search_data['demand_create_at_start']) && !empty($search_data['demand_create_at_start'])
                && isset($search_data['demand_create_at_end']) && !empty($search_data['demand_create_at_end'])){
                $condtion['tb_ms_promotion_demand.create_at'] = array('between',array($search_data['demand_create_at_start'], date("Y-m-d",strtotime('+1 day',strtotime($search_data['demand_create_at_end'])))));
            }else{
                if (isset($search_data['demand_create_at_start']) && !empty($search_data['demand_create_at_start'])){
                    $condtion['tb_ms_promotion_demand.create_at'] = array('EGT',$search_data['demand_create_at_start']);
                }
                if (isset($search_data['demand_create_at_end']) && !empty($search_data['demand_create_at_end'])){
                    $condtion['tb_ms_promotion_demand.create_at'] = array('elt',date("Y-m-d",strtotime('+1 day',strtotime($search_data['demand_create_at_end']))));
                }
            }
            // 需求人
            if (isset($search_data['demand_create_by']) && !empty($search_data['demand_create_by'])){
                $condtion['tb_ms_promotion_demand.create_by'] = array('in',explode(',',$search_data['demand_create_by']));
            }

            // 推广需求国家
            if (isset($search_data['area_id']) && !empty($search_data['area_id'])){
                $condtion['tb_ms_promotion_demand.area_id'] = array('in',explode(',',$search_data['area_id']));
            }

            // 推广上线日期
            if (isset($search_data['promotion_line_time_start']) && !empty($search_data['promotion_line_time_start'])
                && isset($search_data['promotion_line_time_end']) && !empty($search_data['promotion_line_time_end'])){
                $condtion['tb_ms_promotion_task.promotion_line_time'] = array('between',array($search_data['promotion_line_time_start'], date("Y-m-d",strtotime('+1 day',strtotime($search_data['promotion_line_time_end'])))));
            }else{
                if (isset($search_data['promotion_line_time_start']) && !empty($search_data['promotion_line_time_start'])){
                    $condtion['tb_ms_promotion_task.promotion_line_time'] = array('EGT',$search_data['promotion_line_time_start']);
                }
                if (isset($search_data['promotion_line_time_end']) && !empty($search_data['promotion_line_time_end'])){
                    $condtion['tb_ms_promotion_task.promotion_line_time'] = array('elt',date("Y-m-d",strtotime('+1 day',strtotime($search_data['promotion_line_time_end']))));
                }
            }

            //  推广标签类型
            if (isset($search_data['type_cd']) && !empty($search_data['type_cd'])){
                $condtion['tb_ms_promotion_tag.type_cd'] = array('in',explode(',',$search_data['type_cd']));
            }
            //  推广标签名称
            if (isset($search_data['tag_name']) && !empty($search_data['tag_name'])) {
                $condtion['tb_ms_promotion_tag.tag_name'] = $search_data['tag_name'];
            }
            // 优惠码来源
            if (isset($search_data['coupon_source_cd']) && !empty($search_data['coupon_source_cd'])){
                if (!is_array($search_data['coupon_source_cd'])) $search_data['coupon_source_cd'] = explode(',',$search_data['coupon_source_cd']);
                $condtion['tb_ms_promotion_task.coupon_source_cd'] = array('in',$search_data['coupon_source_cd']);
            }

        }
        return $condtion;
    }

    /**
     *  查询优惠码
     */
    public function getCouponFind($condtion,$field){
        $model = new CmsBaseModel();
        $coupon_info = $model->table('cms_promo_activity')
            ->field($field)
            ->join('LEFT JOIN cms_promo_code ON cms_promo_activity.id = cms_promo_code.activity_id ')
            ->where($condtion)
            ->find();
        return $coupon_info;
    }

    /**
     *  发送企业微信通知【认领推广需求】
     */
    public function claimWxMessage($post_data){
        $user_name = userName();
        $post_data = CodeModel::autoCodeTwoVal($post_data,['promontion_currency_cd']);
        foreach ($post_data as $itme){
            $promotion_task_url = ERP_URL.$this->get_promotion_task_url.$itme['promotion_task_no'];
            if ($itme['promotion_type_cd'] == 'N003610003'){
                // 推广内容类型为 SKU
                $promontion_price = number_format($itme['promontion_price'],2);
                if ($itme['platform_cd'] == 'N002620800'){
                    // 平台为Gshopper
                    $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广价格: {$itme['promontion_currency_cd_val']} {$promontion_price}，推广任务 ID： {$itme['promotion_task_no']}，请尽快确认优惠码。<a href='{$promotion_task_url}'>【去查看】</a>";
                }else{
                    $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广价格: {$itme['promontion_currency_cd_val']} {$promontion_price}，推广任务 ID： {$itme['promotion_task_no']}，请尽快确保平台价格一致。<a href='{$promotion_task_url}'>【去查看】</a>";
                }
            }else{
                $message_string = " {$user_name} 认领了您的推广需求：{$itme['promotion_demand_no']}。推广平台：{$itme['channel_platform_name']}，推广媒介：{$itme['channel_medium_name']}，推广任务 ID： {$itme['promotion_task_no']}。<a href='{$promotion_task_url}'>【去查看】</a>";
            }
            $this->workWxMessage($itme['send_by'],$message_string);
        }
    }
    /**
     *  发送企业微信通知【反馈优惠码 编辑推广进展】
     */
    public function editWxMessage($post_data){

        foreach ($post_data as $itme){
            if ($itme['type'] == 1){
                $user_name = $itme['demand_create_by'];
                $promotion_demand_no = $itme['promotion_demand_no'];
                $promotion_demand_url = ERP_URL.$this->get_promotion_demand_url.$promotion_demand_no;
                $message_string = " {$user_name} 的推广任务已经反馈优惠码，任务 ID：{$itme['promotion_task_no']}，请继续跟进。<a href='{$promotion_demand_url}'>【去查看】</a>";
                $this->workWxMessage($itme['send_by'],$message_string);
            }elseif ($itme['type'] == 2){
                $promotion_task_url = ERP_URL.$this->get_promotion_task_url.$itme['promotion_task_no'];
                $message_string = "推广任务 ID：{$itme['promotion_task_no']} 的推广进展已经更新。推广链接：{$itme['promotion_link']}，推广进展：{$itme['feedback']}。<a href='{$promotion_task_url}'>【去查看】</a>";
                $this->workWxMessage($itme['send_by'],$message_string);
            }elseif ($itme['type'] == 4){
                $promotion_task_url = ERP_URL.$this->get_promotion_task_url.$itme['promotion_task_no'];
                $message_string = "推广任务 ID：{$itme['promotion_task_no']} 已退回，退回原因：{$itme['return_reason']}。<a href='{$promotion_task_url}'>【去查看】</a>";
                $this->workWxMessage($itme['send_by'],$message_string);
            }
        }
    }

    /**
     *  发送企业微信通知【驳回推广需求】
     */
    public function rebuttalWxMessage($post_data){
        $user_name = userName();

        foreach ($post_data as $itme){
            if ($itme['status_cd'] == PromotionDemandAction::$status_rebuttal_cd){
                $promotion_demand_url = ERP_URL.$this->get_promotion_demand_url.$itme['promotion_demand_no'];
                $message_string = " {$user_name} 驳回了您的推广需求：{$itme['promotion_demand_no']}。驳回原因：{$itme['rebuttal_reasons']}<a href='{$promotion_demand_url}'>【去查看】</a>";
                $this->workWxMessage($itme['send_by'],$message_string);
            }
        }
    }


    /**
     *  发送企业微信通知
     */
    public function  workWxMessage($user_ids,$message_string){
        $WechatMsg = new WechatMsg();
        $res = $WechatMsg->sendTextNew($user_ids . "@gshopper.com",$message_string);
        Logs([$user_ids, $res], __FUNCTION__, __CLASS__);
        return json_decode($res, true);
    }


    /**
     *  GP推广库存预警
     */
    public function gpStockWarning(){
        $condtion = array(
            'tb_ms_promotion_task.status_cd'=> PromotionTaskAction::$status_being_cd,
            'tb_ms_promotion_demand.promotion_type_cd'=> 'N003610003',
            'tb_ms_promotion_demand.platform_cd'=> 'N002620800',
        );
        $field = "
         tb_ms_promotion_task.promotion_demand_no,
         tb_ms_promotion_task.promotion_task_no,
         tb_ms_promotion_task.create_by as task_create_by,
         tb_ms_promotion_demand.sku_id,
         tb_ms_promotion_demand.area_name,
         tb_ms_promotion_demand.site_cd,
         tb_ms_promotion_demand.create_by as demand_create_by
        ";
        $list = $count = $this->repository->getList($condtion,$field);
        $list = CodeModel::autoCodeTwoVal($list,['site_cd']);
        if ($list){
            // 国家转换  insight 与 ERP 国家表未关联
            $country_list = M('user_area','tb_ms_')->field('id,zh_name')->where('area_type = 1')->select();
            $country_data = array();
            if ($country_list){
                foreach ($country_list as $item){
                    $country_data[$item['zh_name']] = $item['id'];
                }
            }
            $model = new PmsBaseModel();
            foreach ($list as $value){
                $promotion_task_url = ERP_URL.$this->get_promotion_task_url.$value['promotion_task_no'];
                $sku_id = $value['sku_id'];
                $country_id = isset($country_data[$value['area_name']]) ? $country_data[$value['area_name']] : "";
                if ($sku_id && $country_id){
                    $condtion = array(
                        'product_sku.sku_id' => $sku_id,
                        'product_detail.language' => 'N000920100'
                    );
                    $field = "
                    product_sku.sku_id,
                    product_detail.spu_name,
                    product_sku.sale_states,
                    product.review_states,
                    product_sku.sku_states,
                    product_price.country_id,
                    product_price.real_price,
                    product_price.warehouse,
                    product_sku_stock.sku_stock
                    ";
                    $sku_data = $model->table('product_sku')
                        ->field($field)
                        ->join('INNER JOIN product ON product_sku.spu_id = product.spu_id')
                        ->join('INNER JOIN product_detail ON product_detail.spu_id = product.spu_id')
                        ->join('LEFT JOIN product_price ON product_price.sku_id = product_sku.sku_id 
	                    AND product_sku.spu_id = product_price.spu_id')
                        ->join('LEFT JOIN product_sku_stock ON product_sku_stock.sku_id = product_price.sku_id 
	                    AND product_sku_stock.warehouse = product_price.warehouse')
                        ->where($condtion)
                        ->select();
                    $country_stock = array(); // 国家对应所有库存
                    $sale_states = '';    // 商品销售状态
                    $review_states = '';  // 商品审核状态
                    $sku_states = '';   // SKU启用状态
                    $spu_name = '';    // 商品名称
                    foreach ($sku_data as $datum){
                        $country_stock[$datum['country_id']] += $datum['sku_stock'];
                        $sale_states = $datum['sale_states'];
                        $review_states = $datum['review_states'];
                        $sku_states = $datum['sku_states'];
                        $spu_name = $datum['spu_name'];
                    }
                    $message_string = "";
                    if ($sale_states == 'N000100100'){
                        if (isset($country_stock[$country_id])){
                            if ($country_stock[$country_id] < 10){
                                // SKU的发布状态=在售时& 销售信息里存在该SKU对应该推广任务ID对应的推广国家，但是该SKU对应该推广国家的GP库存总数小于10
                                $message_string = "检测到SKU {$sku_id}{$spu_name}在{$value['site_cd_val']}站库存低于10，对应推广任务ID为{$value['promotion_task_no']}。请及时补充，避免推广资源的浪费。<a href='{$promotion_task_url}'>【去查看】</a>";
                            }
                        }else{
                            // SKU的发布状态=在售时 ,销售信息里没有该SKU对应该推广任务ID对应的推广国家
                            $message_string = "检测到SKU {$sku_id}{$spu_name}在{$value['site_cd_val']}站未配置销售价格，对应推广任务ID为{$value['promotion_task_no']}。请及时配置价格，避免推广资源的浪费。<a href='{$promotion_task_url}'>【去查看】</a>";
                        }
                    }
                    if ( $sale_states != 'N000100100' || $review_states != 'N000420400' || $sku_states != 1){
                        // 当查询到SKU的发布状态≠在售 or 审核状态≠通过 & 商品状态≠启用时
                        $message_string = "检测到SKU {$sku_id}{$spu_name}在Gshopper未上架，对应推广任务ID为{$value['promotion_task_no']}。请及时上架，避免推广资源的浪费。<a href='{$promotion_task_url}'>【去查看】</a>";
                    }
                    if ($message_string){
                        $WechatMsg = new WechatMsg();
                        $user_email = [$value['task_create_by'] . "@gshopper.com",$value['demand_create_by'] . "@gshopper.com"];
                        $res = $WechatMsg->sendTextNew($user_email,$message_string);
                        Logs([$user_email, $res,$message_string], __FUNCTION__, __CLASS__);
                    }
                }else{
                    var_dump('GP-SKU推广任务数据异常');
                    Logs("GP-SKU推广任务数据异常：".json_encode($value), __FUNCTION__, __CLASS__);
                }
            }
        }else{
            var_dump('暂无GP-SKU推广任务');
            Logs("暂无GP-SKU推广任务", __FUNCTION__, __CLASS__);
        }
        // exit;
    }


    public function exportCsv(&$data, $map, $excel_name, $bool = false)
    {
        $filename = '' . $excel_name . '' . date('YmdHis') . '.csv'; //设置文件名
        header('Content-Type: text/csv');
        header("Content-type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename=".$filename);
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        $title_name = array_column($map, 'name');
        if (empty($title_name)) {
            $title_name = array_column($map, 0);//兼容格式
        }
        fputcsv($out, $title_name);
        $fields = array_column($map, 'field_name');
        if (empty($fields)) {
            $fields = array_column($map, 1);
        }
        foreach ($data as $row) {
            $line = array_map(function ($field) use ($row, $bool) {
                //导出CSV文件去除双引号
                if (is_numeric($row[$field]) || $bool == true) {
                    return (string)$row[$field];
                }
                return (string)$row[$field] . "\t";
            }, $fields);
            fputcsv($out, $line);
        }
        fclose($out);
    }

    public function test(){
        $this->gpStockWarning();;die;
    }



    public function getCoupon($param)
    {
        $response = $this->queryCoupon($param);
        if ($response['code'] != 200) {
            throw new Exception('获取获取新后台优惠码失败，请提醒技术人员查看。');
        }
        $list = [];
        if ($response['total'] > $param['rows']) {
            $count = ceil($response['total'] / $param['rows']);
            for ($i = 0; $i < $count; $i ++) {
                $response = $this->queryCoupon($param);
                $tem = $this->getCouponData($response);
                $list = array_merge($tem,$list);
            }
        } else {
            $list = $this->getCouponData($response);
        }
        return $list;
    }

    //请求shopNC优惠码接口
    public function queryCoupon($param)
    {
        $data = [
            'user' => $param['user'] ? $param['user'] : 'gshopper',
            'title' => $param['title'] ? $param['title'] : '',
            'store_name' => $param['store_name'] ? $param['store_name'] : '',
            'type' => $param['type'] ? $param['type'] : '',
            'curpage' => $param['curpage'] ? $param['curpage'] : '',
            'rows' => $param['rows'] ? $param['rows'] : '',
        ];
        $sign = $this->sign($data);
        $data['sign'] = $sign;
        $url_param = http_build_query($data);
        $api_url = $this->getUrl('act=voucher&op=voucher_list', $url_param);
        return (array)json_decode(curl_get_json_get($api_url), true);
    }

    //组装shopNC优惠码接口返回数据
    public function getCouponData($data = [])
    {
        $list = [];
        foreach ($data['records'] as $item) {
            $list = array_merge($item['voucher_codes'],$list);
        }
        return $list;
    }

    public function sign($data = [])
    {
        ksort($data);
        return md5(http_build_query($data));
    }

    public function getUrl($str, $url_param)
    {
        return SHOPNC_URL . '/mobile/index.php?' . $str . '&' . $url_param;
    }


    //更新附件的上传
    public function updateAttach($id, $attach){
        $condition['id'] = $id;
        $attachment['origin_name'] = $attach[0]['name'];
        $attachment['save_name'] = $attach[0]['savename'];
        $attachment['save_path_name'] = $attach[0]['savepath'] . $attach[0]['savename'];
        $update['attachment'] = json_encode($attachment, JSON_UNESCAPED_UNICODE);
        $res = $this->repository->update($condition, $update);
        if($res > 0) {
            $info = $this->repository->getFind($condition,'attachment,promotion_task_no');
            $attach_file_name = json_decode($info['attachment'],true);
            $attach_file_name =  $info['promotion_task_no'].substr($attach_file_name['save_name'], strrpos($attach_file_name['save_name'],'.'));
            $result = ['file_show_name'=>$attach_file_name, 'file_save_name'=>$attach[0]['savename'], 'file_real_path'=>$attach[0]['savepath'] . $attach[0]['savename'], 'file_origin_name'=>$attach[0]['name']];
            return $result;
        }else{
            return false;
        }

    }
}