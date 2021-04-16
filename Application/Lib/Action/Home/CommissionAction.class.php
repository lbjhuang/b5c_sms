<?php

class CommissionAction extends BaseAction
{
    public function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
        ){
            parent::_initialize();
        }
    }

    public function getStoreList() {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);

        $model = new CommissionModel();
        $ret = $model->getList($params);
        $response = $this->formatOutput(2000, 'success', $ret);
        $this->ajaxReturn($response, 'json');
    }

    public function getStoreRules() {
        $request_data = ZUtils::filterBlank($this->getParams());
        $params = $request_data['data']['query'];
        $store_id = $request_data['data']['query']['store_id'];
        $model = new CommissionModel();
        $ret = $model->getStoreRules($store_id, $params);
        if (!$ret) {
            $this->ajaxReturn($model->error_info, 'json');
        } else {
            $response = $this->formatOutput(2000, 'success', $ret);
            $this->ajaxReturn($response, 'json');
        }
    }

    public function addCommissionRule() {
        if (IS_POST) {
            $data = ZUtils::filterBlank($this->getParams());
            $model = new CommissionModel();
            $result = $model->addCommissionRule($data);
            if (!$result) {
                $this->ajaxReturn($model->error_info, 'json');
            } else {
                $this->ajaxReturn($model->success_info, 'json');
            }
        }
    }

    public function getCommissionRule() {
        $params = ZUtils::filterBlank($this->getParams())['data']['query'];

        $model = new CommissionModel();
        $ret = $model->getCommissionRule($params['id']);
        $response = $this->formatOutput(2000, 'success', $ret);
        $this->ajaxReturn($response, 'json');
    }

    public function updateCommissionRule() {
        $params = ZUtils::filterBlank($this->getParams());

        $model = new CommissionModel();
        $result = $model->updateCommissionRule($params);
        if (!$result) {
            $this->ajaxReturn($model->error_info, 'json');
        } else {
            $this->ajaxReturn($model->success_info, 'json');
        }
    }

    public function getPmsCategories() {
        $category_level = [
            'N002600100' => 1,
            'N002600200' => 2,
            'N002600300' => 3,
            'N002600400' => 4,
        ];
        $data = ZUtils::filterBlank($this->getParams())['data']['query'];
        $condition['product_category.cat_level'] = $category_level[$data['dimension_cd']];
        $condition['product_category.cat_states'] = 1;
        $condition['product_category_detail.language'] = 'N000920100';
        $field = [
            'product_category_detail.id',
            'product_category_detail.cat_name',
        ];

        $list =(new PmsBaseModel())
            ->table('product_category')
            ->field($field)
            ->join(['left join product_category_detail on product_category.cat_id = product_category_detail.cat_id'])
            ->where($condition)
            ->select();
        $response = $this->formatOutput(2000, 'success', $list);
        $this->ajaxReturn($response, 'json');
    }

    public function changeRuleStatus() {
        if (IS_POST) {
            $data = ZUtils::filterBlank($this->getParams());
            $model = new CommissionModel();
            $result = $model->changeRuleStatus($data['id'], $data['is_enable'], $data['store_id']);
            if (!$result) {
                $this->ajaxReturn($model->error_info, 'json');
            } else {
                $this->ajaxReturn($model->success_info, 'json');
            }
        }
    }

    public function deleteRule() {
        if (IS_POST) {
            $data = ZUtils::filterBlank($this->getParams());
            $model = new CommissionModel();
            $result = $model->deleteRule($data['id'], $data['store_id']);
            if (!$result) {
                $this->ajaxReturn($model->error_info, 'json');
            } else {
                $this->ajaxReturn($model->success_info, 'json');
            }
        }
    }

    public function getStores()
    {
        $params = ZUtils::filterBlank($this->getParams())['data']['query'];
        $model = M('ms_store', 'tb_');
        $where = [];
        if (!empty($params['plat_cds'])) {
            $where['PLAT_CD'] = ['in',$params['plat_cds']];
        }
        $ret = $model->field('ID as cd, STORE_NAME as cdVal')->where($where)->select();
        $response = $this->formatOutput(2000, 'success', $ret);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 公共数据接口
     *
     */
    public function commonData()
    {
        $commonType ['platform'] = 'self::getPlatforms';
//        $commonType ['stores'] = 'self::getStores';
        $commonType ['currency'] = 'CommonDataModel::currency';
        $commonType ['dimension'] = 'CommonDataModel::dimension';
        $commonType ['judge'] = 'CommonDataModel::judge';
        $commonType ['judge2'] = 'CommonDataModel::judge2';

        $params = ZUtils::filterBlank($this->getParams())['data']['query'];
        foreach ($params as $key => $bool) {
            if ($bool)
                $response [$key] = call_user_func_array($commonType [$key], []);
        }

        $response = $this->formatOutput(2000, 'success', $response);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 获取店铺
     * @return type object
     */
//    public static $stores;
//
//    public static function getStores()
//    {
//        if (static::$stores) return static::$stores;
//
//        $model = M('ms_store', 'tb_');
//        $ret = $model->field('ID as cd, STORE_NAME as cdVal')->where('')->select();
//        if ($ret) {
//            foreach ($ret as $key => &$value) {
//                $value ['cdVal'] = L($value ['cdVal']);
//                unset($value);
//            }
//        }
//        return static::$stores = $ret;
//    }

    /**
     * 获取平台
     * @return type object
     */
    public static $platform;

    public static function getPlatforms()
    {
        if (static::$platform) return static::$platform;

        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00083%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$platform = $ret;
    }

    /**
     * 日志接口
     */
    public function getLog() {
        try{
            $params = $this->getParams();
            $where = [];
            if (!empty($params['store_id'])) {
                $where['store_id'] = $params['store_id'];
            }else{
                $this->ajaxReturn(['data' => [], 'code' => 3000, 'msg' =>"没有传入店铺ID"]);
            }

            empty($params['update_time'][0]) or $where['log.updated_time'][] = ['egt', $params['updated_time'][0]];
            empty($params['update_time'][1]) or $where['log.updated_time'][] = ['elt', $params['updated_time'][1] . ' 23:59:59'];
            empty($params['page']) and $params['page'] = 1;
            empty($params['page_size']) and $params['page_size'] = 10;
            $offset = ($params['page'] - 1) * $params['page_size'];
            $query = M()->table('gs_dp.dp_tb_fin_commission_log log')
                ->field([
                    'log.*',
                ])
                ->where($where);
            $query1 = clone $query;
            $total = $query->count();
            $query1->limit($offset . ',' . $params['page_size']);
            $list = $query1->order('log.update_time desc')->select(); //echo M()->table('gs_dp.dp_tb_fin_commision_log log')->getlastsql();die;
            $where_name['ID'] = $params['store_id'];
            $store_name = M()->table('tb_ms_store')->field(['STORE_NAME'])->where($where_name)->find();

            $model = new CommissionModel();
            foreach ($list as $key=>$v){
                $list[$key]['store_name'] = $store_name['STORE_NAME'];
                $list[$key]['content'] = $model->formatLogContent($list[$key]['content']);
            }
            $data = ['list' => $list, 'total' => $total];


            $this->ajaxReturn(['data' => $data, 'code' => 2000, 'msg' => 'success']);
        }catch (\Exception $e){
            $this->ajaxReturn(['data' => [], 'code' => 3000, 'msg' => $e->getMessage()]);
        }




    }

}