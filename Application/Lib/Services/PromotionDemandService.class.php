<?php
/*
 *
 */
class PromotionDemandService extends Service
{

    protected $repository;

    protected $model = "";
    protected $promotion_demand_table = "";
    protected $promotion_task_table = "";


    public function __construct($model)
    {
        $this->model = empty($model) ? new Model() : $model;
        $this->promotion_demand_table = M('promotion_demand','tb_ms_');
        $this->promotion_task_table = M('promotion_task','tb_ms_');
        $this->repository = new PromotionDemandRepository($this->model);
    }

    public function createPromotionDemandNo($promotion_demand_no) {
        if (empty($promotion_demand_no)){
            $promotion_demand_no = $this->promotion_demand_table->lock(true)->where(['promotion_demand_no'=>['like','TG'.date('Ymd').'%']])->order('id desc')->getField('promotion_demand_no');
        }
        if($promotion_demand_no) {
            $num = substr($promotion_demand_no,-4)+1;
        }else {
            $num = 1;
        }
        $promotion_demand_no = 'TG'.date('Ymd').substr(10000+$num,1);
        return $promotion_demand_no;
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
    public function addAll($post_data){
        $promotion_demand_no = "";
        $insert_data  = array();
        foreach ($post_data as $itme){
            $temp_data['promotion_type_cd'] = isset($itme['promotion_type_cd']) ? $itme['promotion_type_cd'] : "";
            $temp_data['area_id'] = isset($itme['area_id']) ? $itme['area_id'] : "";
            $temp_data['area_name'] = isset($itme['area_name']) ? $itme['area_name'] : "";
            $temp_data['platform_cd'] = isset($itme['platform_cd']) ? $itme['platform_cd'] : "";
            $temp_data['site_cd'] = isset($itme['site_cd']) ? $itme['site_cd'] : "";
            $temp_data['sku_id'] = isset($itme['sku_id']) ? $itme['sku_id'] : "";
            $temp_data['product_name'] = isset($itme['product_name']) ? $itme['product_name'] : "";
            $temp_data['product_attribute'] = isset($itme['product_attribute']) ? $itme['product_attribute'] : "";
            $temp_data['product_image'] = isset($itme['product_image']) ? $itme['product_image'] : "";
            $temp_data['link'] = isset($itme['link']) ? $itme['link'] : "";
            $temp_data['currency_cd'] = isset($itme['currency_cd']) ? $itme['currency_cd'] : "";
            $temp_data['dis_product_pirce'] = isset($itme['dis_product_pirce']) ? $itme['dis_product_pirce'] : "";
            $temp_data['dis_product_pirce_front'] = isset($itme['dis_product_pirce_front']) ? $itme['dis_product_pirce_front'] : "";
            $temp_data['dis_product_pirce_back'] = isset($itme['dis_product_pirce_back']) ? $itme['dis_product_pirce_back'] : "";
            $temp_data['profit_front'] = isset($itme['profit_front']) ? $itme['profit_front'] : "";
            $temp_data['profit_back'] = isset($itme['profit_back']) ? $itme['profit_back'] : "";
            $temp_data['remark'] = isset($itme['remark']) ? $itme['remark'] : "";
            $temp_data['tag_id'] = isset($itme['tag_id']) ? $itme['tag_id'] : "";
            $temp_data['promotion_pirce'] = isset($itme['promotion_pirce']) ? $itme['promotion_pirce'] : null;


            $temp_data['status_cd'] = 'N003590004'; // 创建待认领状态
            $temp_data['create_by'] = userName();
            $temp_data['create_at'] = date("Y-m-d H:i:s");
            $temp_data['update_by'] = userName();
            $temp_data['update_at'] = date("Y-m-d H:i:s");
            $temp_data['promotion_demand_no'] = $this->createPromotionDemandNo($promotion_demand_no);
            $promotion_demand_no = $temp_data['promotion_demand_no'];
            $insert_data[] = $temp_data;
        }
        $res = $this->repository->addAll($insert_data);
        if (!$res){
            throw new Exception('发布推广需求失败');
        }
        return $res;
    }

    public function getFind($condtion, $field = "*"){
        $info_data = $this->repository->getFind($condtion,$field);
        return $info_data;
    }
    public function getList($request_data){
        $search_data = $request_data['search'];
        $pages_data = $request_data['pages'];
        if (empty($pages_data)){
            $pages_data = array(
                'per_page' => 10,
                'current_page' => 1
            );
        }
        $condtion = $this->searchWhere($search_data);
        $count = $this->repository->getList($condtion,'count(*) as total_rows');
        $limit = ($pages_data['current_page'] - 1) * $pages_data['per_page'].' , '.$pages_data['per_page'];
        $field = 'tb_ms_promotion_demand.*,
            tb_ms_promotion_tag.type_cd,
            tb_ms_promotion_tag.tag_name ';
        $list = $this->repository->getList($condtion,$field,$limit);
        $list = CodeModel::autoCodeTwoVal($list,['status_cd','promotion_type_cd','platform_cd','site_cd','currency_cd','type_cd']);
        foreach ($list as $key => $item){
            if ($item['promotion_type_cd'] != 'N003610003'){
                $list[$key]['dis_product_pirce'] = "";
                $list[$key]['dis_product_pirce_back'] = "";
                $list[$key]['dis_product_pirce_front'] = "";
            }
            if (empty($item['promotion_pirce']) || $item['promotion_pirce'] == 0){
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
     *  组装列表查询
     * @return array
     */
    private function searchWhere($search_data)
    {
        $condtion = array("1 = 1");
        if (is_array($search_data) && !empty($search_data)){
            //   需求状态
            if (isset($search_data['status_cd']) && !empty($search_data['status_cd'])){
                $condtion['tb_ms_promotion_demand.status_cd'] = array('in',explode(',',$search_data['status_cd']));
            }
            // 推广需求编号
            if (isset($search_data['promotion_demand_no']) && !empty($search_data['promotion_demand_no'])){
                $condtion['promotion_demand_no'] = $search_data['promotion_demand_no'];
            }
            // 需求人
            if (isset($search_data['create_by']) && !empty($search_data['create_by'])){
                $condtion['tb_ms_promotion_demand.create_by'] = array('in',explode(',',$search_data['create_by']));
            }

            // 需求提出日期范围
            if (isset($search_data['create_at_start']) && !empty($search_data['create_at_start'])
            && isset($search_data['create_at_end']) && !empty($search_data['create_at_end'])){
                $condtion['tb_ms_promotion_demand.create_at'] = array('between',array($search_data['create_at_start'],date("Y-m-d",strtotime('+1 day',strtotime($search_data['create_at_end'])))));
            }else{
                if (isset($search_data['create_at_start']) && !empty($search_data['create_at_start'])){
                    $condtion['tb_ms_promotion_demand.create_at'] = array('EGT',$search_data['create_at_start']);
                }
                if (isset($search_data['create_at_end']) && !empty($search_data['create_at_end'])){
                    $condtion['tb_ms_promotion_demand.create_at'] = array('elt',date("Y-m-d",strtotime('+1 day',strtotime($search_data['create_at_end']))));
                }
            }

            //   推广内容类型
            if (isset($search_data['promotion_type_cd']) && !empty($search_data['promotion_type_cd'])){
                $condtion['promotion_type_cd'] = array('in',explode(',',$search_data['promotion_type_cd']));
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

            //  商品名称
            if (isset($search_data['product_name']) && !empty($search_data['product_name'])){
                $condtion['product_name'] = array('like','%'.$search_data['product_name'].'%');
            }

            //  国家ID
            if (isset($search_data['area_id']) && !empty($search_data['area_id'])){
                $condtion['tb_ms_promotion_demand.area_id'] = $search_data['area_id'];
            }

            //  推广标签类型
            if (isset($search_data['type_cd']) && !empty($search_data['type_cd'])){
                $condtion['tb_ms_promotion_tag.type_cd'] = array('in',explode(',',$search_data['type_cd']));
            }
            //  推广标签名称
            if (isset($search_data['tag_name']) && !empty($search_data['tag_name'])){
                $condtion['tb_ms_promotion_tag.tag_name'] = $search_data['tag_name'];
            }

        }
        return $condtion;
    }

    // 复制详情
    public  function cloneDemandList($condtion){
        $list = $this->repository->getList($condtion,"status_cd,promotion_type_cd,area_name,dis_product_pirce,
        area_id,platform_cd,site_cd,sku_id,product_name,product_attribute,link,currency_cd,
        dis_product_pirce_front,dis_product_pirce_back,profit_front,profit_back,remark,promotion_demand_no,product_image,
        tb_ms_promotion_tag.type_cd,tb_ms_promotion_tag.tag_name,promotion_pirce");
        $list = CodeModel::autoCodeTwoVal($list,['promotion_type_cd','platform_cd','site_cd','currency_cd','type_cd']);
        $list = DataModel::formatAmount($list);
        foreach ($list as $key => $item){
            if (empty($item['promotion_pirce']) || $item['promotion_pirce'] == 0){
                $list[$key]['promotion_pirce'] = "";
            }
        }
        return $list;
    }

    public function getSkuFind($condtion,$field){
        $model = new PmsBaseModel();
        $sku_info = $model->table('product_sku')
            ->field($field)
            ->join('INNER JOIN product ON product_sku.spu_id = product.spu_id')
            ->join('INNER JOIN product_detail ON product_detail.spu_id = product.spu_id ')
            ->join('INNER JOIN product_attribute ON product_attribute.spu_id = product.spu_id AND product_sku.sku_id = product_attribute.sku_id')
            ->join('LEFT JOIN option_name_detail ON option_name_detail.name_id = product_attribute.name_id')
            ->join('LEFT JOIN option_value_detail ON option_value_detail.value_id = product_attribute.value_id ')
            ->where($condtion)
            ->group('product_sku.sku_id')
            ->find();
        return $sku_info;
    }
}