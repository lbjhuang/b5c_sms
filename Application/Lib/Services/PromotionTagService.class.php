<?php

/**
 * Class PromotionTagService
 */
class PromotionTagService extends Service
{

    protected $repository;

    protected $model = "";
    protected $promotion_demand_table = "";
    protected $promotion_task_table = "";


    public function __construct($model)
    {
        $this->model = empty($model) ? new Model() : $model;
        $this->repository = new PromotionTagRepository($this->model);
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



    public function getFind($condtion, $field = "*"){
        $info_data = $this->repository->getFind($condtion,$field);
        return $info_data;
    }

    public function getListData($request_data){
        $condtion = $this->searchWhere($request_data);
        $list = $this->repository->getList($condtion,'id,type_cd,tag_name');
        $list = CodeModel::autoCodeTwoVal($list,['type_cd']);
        return $list;
    }

    public function getData($request_data){
        $request_data['status'] = 1;
        $condtion = $this->searchWhere($request_data);
        $list = $this->repository->getList($condtion,'id,type_cd,tag_name');
        $list = CodeModel::autoCodeTwoVal($list,['type_cd']);
        return $list;
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
        $list = $this->repository->getList($condtion,'id,type_cd,tag_name,status',$limit);
        $list = CodeModel::autoCodeTwoVal($list,['type_cd']);
        foreach ($list as $key => $value){
            $list[$key]['id_number'] = sprintf("%05d", $value['id']);
        }
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
            //   类型
            if (isset($search_data['type_cd']) && !empty($search_data['type_cd'])){
                $condtion['type_cd'] =$search_data['type_cd'];
            }
            //   标签名称
            if (isset($search_data['tag_name']) && !empty($search_data['tag_name'])){
                $condtion['tag_name'] = array('like','%'.$search_data['tag_name'].'%');
            }

            //   标签状态
            if (isset($search_data['status']) && $search_data['status'] != "" ){
                $condtion['status'] = $search_data['status'];
            }
        }
        return $condtion;
    }
}