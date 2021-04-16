<?php
/**
 * 营销管理-推广需求
 * Class PromotionDemandAction
 */

class PromotionDemandAction extends BaseAction
{
    private $promotionDemandService = "";
    public static $status_close_cd = "N003590001";  // 推广需求状态	已关闭
    public static $status_rebuttal_cd = "N003590002"; // 推广需求状态	已驳回
    public static $status_already_claim_cd = "N003590003";  // 推广需求状态 已认领
    public static $status_await_claim_cd = "N003590004";  // 推广需求状态	 待认领
    // 站点-国家-默认语言-默认币种
    public static $site_currency = [
        [ // Gshopper-KR
            'site' => 'N000831400',
            'origin' => '278',  // 国家ID
            'language' => 'N000920400',  // 默认语音
            'currency' => 'N000590200',  // 默认币种
        ],
        [ // Gshopper-CN
            'site' => 'N000834100',
            'origin' => '1',
            'language' => 'N000920100',
            'currency' => 'N000590300',
        ],
        [ // Gshopper-US
            'site' => 'N000834200',
            'origin' => '334',
            'language' => 'N000920200',
            'currency' => 'N000590100',
        ],
        [ // Gshopper-JP
            'site' => 'N000834300',
            'origin' => '147',
            'language' => 'N000920300',
            'currency' => 'N000590400',
        ],
        [ // Gshopper-AU
            'site' => 'N000837401',
            'origin' => '30',
            'language' => 'N000920200',
            'currency' => 'N000590800',
        ],
        [ // Gshopper-FR
            'site' => 'N000837402',
            'origin' => '105',
            'language' => 'N000920500',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-DE
            'site' => 'N000837403',
            'origin' => '102',
            'language' => 'N000920600',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-HK
            'site' => 'N000837404',
            'origin' => '60186',
            'language' => 'N000920700',
            'currency' => 'N000590600',
        ],
        [ // Gshopper-IT
            'site' => 'N000837405',
            'origin' => '131',
            'language' => 'N000920200',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-RU
            'site' => 'N000837406',
            'origin' => '229',
            'language' => 'N000920200',
            'currency' => 'N000591900',
        ],
        [ // Gshopper-SG
            'site' => 'N000837407',
            'origin' => '252',
            'language' => 'N000920200',
            'currency' => 'N000590700',
        ],
        [ // Gshopper-UK
            'site' => 'N000837408',
            'origin' => '338',
            'language' => 'N000920200',
            'currency' => 'N000590900',
        ],
        [ // Gshopper-ES
            'site' => 'N000837409',
            'origin' => '277',
            'language' => 'N000920800',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-NZ
            'site' => 'N000837428',
            'origin' => '222',
            'language' => 'N000920200',
            'currency' => 'N000591902',
        ],
        [ // Gshopper-PH
            'site' => 'N000837430',
            'origin' => '237',
            'language' => 'N000920200',
            'currency' => 'N000591500',
        ],
        [ // Gshopper-NL
            'site' => 'N000837437',
            'origin' => '219',
            'language' => 'N000920200',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-PL
            'site' => 'N000837436',
            'origin' => '236',
            'language' => 'N000920200',
            'currency' => 'N000591903',
        ],
        [ // Gshopper-PT
            'site' => 'N000837441',
            'origin' => '227',
            'language' => 'N000920200',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-BE
            'site' => 'N000837442',
            'origin' => '47',
            'language' => 'N000920200',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-CZ
            'site' => 'N000837444',
            'origin' => '80',
            'language' => 'N001960200',
            'currency' => 'N000590500',
        ],
        [ // Gshopper-NTT
            'site' => 'N000837443',
            'origin' => '47',
            'language' => 'N001960200',
            'currency' => 'N000590500',
        ],
    ];
    protected $site_currency_key_value = [];

    public function __construct()
    {
    }

    public function index(){


    }

    /**
     *  获取站点对应的基础数据
     */
    public function getSiteCurrency(){
        $list = CodeModel::autoCodeTwoVal(self::$site_currency,['currency']);
        $this->ajaxSuccess($list);
    }
    /**
     *  列表数据
     */
    public function getList(){
        $request_data = DataModel::getDataNoBlankToArr();
        $data = (new PromotionDemandService())->getList($request_data);
        $this->ajaxSuccess($data);
    }

    /**
     * 发布需求
     */
    public function create(){
        try{
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];
            $model->startTrans();
            if (!empty($post_data)){
                foreach ( $post_data as &$datum){
                    $datum['promotion_pirce'] = str_replace(',','',$datum['promotion_pirce']);
                    $this->createValidate($datum);
                }
                unset($datum);
                (new PromotionDemandService($model))->addAll($post_data);
            }else{
                throw  new  Exception('请求参数不能为空');
            }
            $model->commit();
        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess();
    }

    /**
     *  编辑 驳回-关闭
     */
    public function edit(){
        try{
            $model = new Model();
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];
            $model->startTrans();
            $promotionDemandService = new PromotionDemandService($model);

            $send_data_wx= array();

            if (!empty($post_data)){
                foreach ( $post_data as $key => $datum){

                    $send_data_wx[$key]['status_cd'] =  $datum['status_cd'];
                    $send_data_wx[$key]['promotion_demand_no'] =  $datum['promotion_demand_no'];
                    $send_data_wx[$key]['rebuttal_reasons'] =  $datum['rebuttal_reasons'];

                    $condtion = array(
                        'promotion_demand_no' => $datum['promotion_demand_no']
                    );
                    $info_data = $promotionDemandService->getFind($condtion,'status_cd,create_by');
                    if (empty($info_data)){
                        throw new Exception("推广需求不存在");
                    }
                    if ($datum['status_cd'] == self::$status_rebuttal_cd){

                        if (empty($datum['rebuttal_reasons'])){
                            throw new Exception("驳回原因不能为空");
                        }
                        if ($info_data['status_cd'] == self::$status_close_cd || $info_data['status_cd'] == self::$status_rebuttal_cd){
                            throw  new  Exception("需求状态不符合驳回条件");
                        }
                        $update_data = array(
                            'status_cd' => self::$status_rebuttal_cd,
                            'rebuttal_reasons' => $datum['rebuttal_reasons'],
                            'update_at' => date("Y-m-d H:i:s"),
                            'update_by' => userName()
                        );
                        $promotionDemandService->update($condtion,$update_data);


                    }else if ($datum['status_cd'] == self::$status_close_cd){
                        $update_data = array(
                            'status_cd' => self::$status_close_cd,
                            'update_at' => date("Y-m-d H:i:s"),
                            'update_by' => userName()
                        );
                        $promotionDemandService->update($condtion,$update_data);
                    }else{
                        throw new Exception('参数状态异常');
                    }

                    $send_data_wx[$key]['send_by'] =  $info_data['create_by'];
                }
            }else{
                throw  new  Exception('请求参数不能为空');
            }
            $model->commit();

            // 发送企业微信通知
            (new PromotionTaskService())->rebuttalWxMessage($send_data_wx);

        }catch (Exception $exception){
            $model->rollback();
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess();

    }

    /**
     *  复制需求
     */
    public function cloneDemand(){
        try{
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];
            $promotionDemandService = new PromotionDemandService();
            if (!empty($post_data)){

                $promotion_demand_nos =  array_column($post_data,'promotion_demand_no');
                if (empty($promotion_demand_nos)){
                    throw  new  Exception('参数异常');
                }
                $condtion = array(
                    'promotion_demand_no' => array('in',$promotion_demand_nos)
                );
                $list = $promotionDemandService->cloneDemandList($condtion);
                if (!$list){
                    throw new Exception('请选择复制的需求');
                }
                if (count(array_unique(array_column($list,'promotion_type_cd'))) != 1){
                    throw new Exception('多个需求同时发起需确保推广内容类型相同');
                }
                if (count(array_unique(array_column($list,'area_id'))) != 1){
                    throw new Exception('多个需求同时发起需确保推广国家相同');
                }
            }else{
                throw  new  Exception('请求参数不能为空');
            }
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),4000);
        }

        $this->ajaxSuccess($list);
    }

    /**
     * 参数验证
     * @param $request_data
     * @throws Exception
     */
    public function createValidate($datum) {
        $rules = array(
            'promotion_type_cd' => 'required|size:10',
            'area_id' => 'required|integer',
            'area_name' => 'required',
            'link' => 'required',
        );
        $custom_attributes = array(
            'promotion_type_cd'=>'推广内容类型',
            'area_id'=>'国家地区',
            'area_name'=>'国家区域名称',
            'link'=>'链接',
        );

        // 推广类型为SKU
        if ($datum['promotion_type_cd'] == 'N003610003'){
            $rules['sku_id'] = 'required';
            $rules['product_name'] = 'required';
            $rules['product_attribute'] = 'required';
            $rules['promotion_pirce'] = 'required';
            $rules['currency_cd'] = 'required';

            $custom_attributes['sku_id'] = 'SKU_ID';
            $custom_attributes['product_name'] = '商品名称';
            $custom_attributes['product_attribute'] = '商品属性';
            $custom_attributes['promotion_pirce'] = '推广价格';
            $custom_attributes['currency_cd'] = '币种';

            if (!preg_match('/^[0-9]+(\.?[0-9]+)?$/',$datum['promotion_pirce']) || $datum['promotion_pirce'] < 0){
                throw new Exception('请输入推广价格');
            }
        }

        $this->validate($rules, $datum, $custom_attributes);



    }

    /**
     *   获取详情
     */
    public function getDetail(){
        try{
            $request_data = DataModel::getDataNoBlankToArr();
            $promotion_demand_no = $request_data['promotion_demand_no'];
            $promotionDemandService = new PromotionDemandService();
            if (!empty($promotion_demand_no)){
                $list = $promotionDemandService->getFind(array('promotion_demand_no'=>$promotion_demand_no));
                $list = DataModel::formatAmount($list);
                $list = CodeModel::autoCodeOneVal($list,['status_cd','promotion_type_cd','platform_cd','site_cd','currency_cd']);
                if ($list['promotion_type_cd'] != 'N003610003'){
                    $list['dis_product_pirce'] = "";
                    $list['dis_product_pirce_back'] = "";
                    $list['dis_product_pirce_front'] = "";
                }
            }else{
                throw  new  Exception('请求参数不能为空');
            }
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),4000);
        }

        $this->ajaxSuccess($list);
    }

    /**
     * 获取多个详情
     */
    public function getMultiDetail(){
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $post_data = $request_data['post_data'];

            $promotionDemandService = new PromotionDemandService();
            if (!empty($post_data)) {
                $promotion_demand_nos = array_column($post_data, 'promotion_demand_no');
                if (empty($promotion_demand_nos)) {
                    throw  new  Exception('参数异常');
                }
                $condtion = array(
                    'promotion_demand_no' => array('in', $promotion_demand_nos)
                );
                $list = $promotionDemandService->cloneDemandList($condtion);
                foreach ($list as $key => $item){
                    if ($item['promotion_type_cd'] != 'N003610003'){
                        $list[$key]['dis_product_pirce'] = "";
                        $list[$key]['dis_product_pirce_back'] = "";
                        $list[$key]['dis_product_pirce_front'] = "";
                    }
                }
            }
        }catch (Exception $exception) {
            $this->ajaxError([], $exception->getMessage(), 4000);
        }
        $this->ajaxSuccess($list);
    }

    /**
     *  查询SKU信息
     */
    public function searchSku(){
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['sku_id'])){
            $this->ajaxError([],'请输入SKU',4000);
        }
        $condtion = array(
            'product_sku.sku_id' => $request_data['sku_id'],
            'product_detail.language' => 'N000920100',
            'option_name_detail.language' => 'N000920100',
            'option_value_detail.language' => 'N000920100',
        );
        $field = 'product_sku.sku_id,
            product_detail.spu_name,
            thumbnail,
            GROUP_CONCAT( option_name_detail.name_detail ) AS name_detail,
            GROUP_CONCAT( option_value_detail.value_detail ) AS value_detail';
        $sku_info = (new PromotionDemandService())->getSkuFind($condtion,$field);
        $this->ajaxSuccess($sku_info);
    }

    /**
     * 推广需求导入
     */
    public function import(){
        header("content-type:text/html;charset=utf-8");
        vendor("PHPExcel.PHPExcel");
        session_write_close();
        set_time_limit(0);
        try{
            $error_data = [];  // 异常详情
            $promotion_demand_service = new PromotionDemandService();
            /*****基础数据获取*****/
            //  推广国家
            $url = INSIGHT.'/insight-backend/gpActivity/queryCountryName';
            $area_list = (new PromotionTaskService())->requsetInsightJson($url,array());
            $area_list  = json_decode($area_list,true);
            $area_data = [];
            if ($area_list && $area_list['success'] == true && !empty($area_list['datas'])){
                foreach ($area_list['datas'] as $item){
                    $area_data[$item['countyName']] = $item['id'];
                }
            }else{
                throw new Exception('国家查询失败');
            }
            unset($area_list);

            // 推广内容类型 - 平台  - 站点
            $cdModel = A('Common/Index');
            $cd_type['promotion_demand_type'] = 'false'; // 推广内容类型（需要已开启）
            $cd_type['currency'] = 'false'; // 币种（需要已开启）
            $cd_type['promotion_tag_type'] = 'true'; //   推广标签类型
            $cd_res_arr = $cdModel->get_cd($cd_type);
            $cd_data = array();
            if ($cd_res_arr){
                foreach ($cd_res_arr['promotion_demand_type'] as $item){
                    $cd_data['promotion_demand_type_data'][$item['CD_VAL']] = $item['CD'];
                }
                foreach ($cd_res_arr['currency'] as $item){
                    $cd_data['currency_data'][$item['CD_VAL']] = $item['CD'];
                }
                foreach ($cd_res_arr['promotion_tag_type'] as $item){
                    $cd_data['promotion_tag_type_data'][$item['CD_VAL']] = $item['CD'];
                }
            }
            unset($cd_res_arr);

            $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    throw  new  Exception('no Excel');
                }
            }
            $PHPReader->setReadDataOnly(true);  // 只读取数据。
            $PHPExcel = $PHPReader->load($filePath);
            $all_row = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
            $active_sheet = $PHPExcel->getActiveSheet();
            $post_data = [];
            foreach ( self::$site_currency as $item){
                $this->site_currency_key_value[$item['site']] = $item['currency'];
            }
            for ($start = 3; $start <= $all_row; $start++) {
                list($temp_data,$error_detail) = $this->importValidate($promotion_demand_service,$active_sheet,$start,$area_data,$cd_data);
                $post_data[] = $temp_data;
                $error_data = array_merge($error_data,$error_detail);
            }
            $error_data = array_unique($error_data);
            if (!empty($error_data)){
                throw  new Exception('导入失败');
            }
            (new PromotionDemandService())->addAll($post_data);
        }catch (Exception $exception){
            $this->ajaxError($error_data,$exception->getMessage(),4000);
        }
        $this->ajaxSuccess();

    }

    /**
     * 导入参数验证
     */
    public function importValidate($promotion_demand_service,$active_sheet,$start,$area_data,$cd_data){

        $error_str = '数据不符合导入要求；';
        $temp_data = array();
        $error_detail = array();
        // 推广内容类型
        $promotion_type_cd = trim($active_sheet->getCell('A' . $start)->getValue());
        $temp_data['promotion_type_cd'] = isset($cd_data['promotion_demand_type_data'][$promotion_type_cd]) ? $cd_data['promotion_demand_type_data'][$promotion_type_cd] : "";
        if (empty($temp_data['promotion_type_cd'])){
            array_push($error_detail,'A' . $start.$error_str);
        }
        // 推广国家
        $area_name = trim($active_sheet->getCell('B' . $start)->getValue());
        $temp_data['area_id'] =  isset($area_data[$area_name]) ? $area_data[$area_name] : "";
        $temp_data['area_name'] =  $area_name;
        if (empty($temp_data['area_id'])){
            array_push($error_detail,'B' . $start.$error_str);
        }

        if($temp_data['promotion_type_cd'] == 'N003610003' ){
            // SKU
            $sku_id = trim($active_sheet->getCell('C' . $start)->getValue());
            $temp_data['sku_id'] = $sku_id;
            $condtion = array(
                'product_sku.sku_id' => $sku_id,
                'product_detail.language' => 'N000920100',
                'option_name_detail.language' => 'N000920100',
                'option_value_detail.language' => 'N000920100',
            );
            $field = 'product_sku.sku_id,
            product_detail.spu_name,
            thumbnail,
            GROUP_CONCAT( option_name_detail.name_detail ) AS name_detail,
            GROUP_CONCAT( option_value_detail.value_detail ) AS value_detail';
            $sku_info = $promotion_demand_service->getSkuFind($condtion,$field);
            if (empty($temp_data['sku_id']) || empty($sku_info)){
                array_push($error_detail,'C' . $start.$error_str);
            }else{
                $temp_data['product_name'] = $sku_info['spu_name'];
                $temp_data['product_attribute'] = $sku_info['name_detail'].':'.$sku_info['value_detail'];
                $temp_data['product_image'] = $sku_info['thumbnail'];
            }

            // 商品价格（币种）
            $currency_cd = trim($active_sheet->getCell('E' . $start)->getValue());
            $temp_data['currency_cd'] = isset($cd_data['currency_data'][$currency_cd]) ? $cd_data['currency_data'][$currency_cd] : "";
            if (empty($temp_data['currency_cd'])){
                array_push($error_detail,'E' . $start.$error_str);
            }
            // 推广价格
            $promotion_pirce = trim($active_sheet->getCell('F' . $start)->getValue());
            $temp_data['promotion_pirce'] =  $promotion_pirce;
            if (!preg_match('/^[0-9]+(\.?[0-9]+)?$/',$temp_data['promotion_pirce']) || $temp_data['promotion_pirce'] < 0){
                array_push($error_detail,'F' . $start.$error_str);
            }

        }
        // 商品/活动页/站点链接
        $link = trim($active_sheet->getCell('D' . $start)->getValue());
        $temp_data['link'] = $link;
        if (empty($temp_data['link'])){
            array_push($error_detail,'D' . $start.$error_str);
        }

        // 备注
        $temp_data['remark'] = trim($active_sheet->getCell('G' . $start)->getValue());
        return array($temp_data,$error_detail);
    }

    /**
     * @param null $data
     */
    public function down()
    {
        $file_name = trim($_POST['file_name']);
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Marketing/Public/Excel/' . $file_name;
        Http::download($filename, $filename);

    }
}