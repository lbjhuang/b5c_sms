<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 配置管理模块服务层
 * Description of Configuration
 *
 * @author Administrator
 */
class ConfigurationService extends Service {
    
    private $user_name;
    
    private $model;
    public function __construct($model) {
        if ($model) {
            $this->model = $model;
        } else {
            $this->model = new Model();
        }
        $this->user_name = DataModel::userNamePinyin();
    }

    /**
     * 所有店铺设置成不支持新增的仓库
     * @param type $warehouse_code
     * @return boolean
     */
    public function setStoreNotSupportWarehouse($warehouse_code) {
        if (empty($warehouse_code)) {
            return FALSE;
        }
        $update_sql = "UPDATE tb_ms_store SET WAREHOUSES = (CASE ID ";
        $stores = M('ms_store', 'tb_')->field('ID, WAREHOUSES')->select();
        $date = dateTime();
        foreach ($stores as $v) {
            $warehouse = $v['WAREHOUSES'];
            if ($warehouse) {
                $update_sql .= " WHEN {$v['ID']} THEN ". "'$warehouse". ','."$warehouse_code'";
            } else {
                $update_sql .= " WHEN {$v['ID']} THEN ". "'$warehouse_code'";
            }
            
            $data[] = [
                'P_ID1' => $v['ID'],
                'P_ID2' => $warehouse_code,
                'TYPE' => 3,
                'CREATE_TIME' => $date,
                'CREATE_USER' => $this->user_name,
            ];
        }
        $update_sql .= ' END)';
        $update_res = $this->model->query($update_sql);
//        if (!$update_res) {
//            $this->model->rollback();
//            return FALSE;
//        }
        
        if (!M('ms_params', 'tb_')->addAll($data)) {
            $this->model->rollback();
            return FALSE;
        }
        return TRUE;
    }

    /**********************自有物流仓配置：start**********************/
    public function owmLogisticsSave($request_data) {

        $own_model = M('ms_logistics_own_config', 'tb_');
        $where = [
            'warehouse_code' => $request_data['warehouse_code'],
            'logistics_company_code' => $request_data['logistics_company_code'],
            'logistics_mode_id' => $request_data['logistics_mode_id'],
        ];
        if($request_data['id']) {
            $where['id'] = ['neq', $request_data['id']];
        }
        $own_info = $own_model->where($where)->find();
        if (!empty($own_info)) {
            throw new \Exception(L('该自有仓配置已经存在'));
        }
        if(!$own_model->create($request_data)) {
            throw new \Exception(L('创建数据失败'));
        }
        $own_model->updated_by = $this->user_name;
        if(!$request_data['id']) {
            $own_model->created_by = $this->user_name;
            if (!$own_model->add()) {
                throw new \Exception(L('自有物流仓配置失败'));
            }
        } else {
            if (false === $own_model->where(['id' => $request_data['id']])->save()) {
                throw new \Exception(L('自有物流仓配置失败'));
            }
        }
    }

    private function isUniqueOwnLogistics($data) {

    }

    public function searchOwnLogisticsList($request_data, $is_excel = false) {
        $search = $request_data['search'];
        $where = [];
        $map['USE_YN'] = 'Y';
        if (!empty($search['warehouse'])) {
            $map['CD']               = ['like', "N00068%"];
            $map['CD_VAL']           = ['like', "%{$search['warehouse']}%"];
            $warehouse_code          = M('ms_cmn_cd', 'tb_')->where($map)->getField('CD', true);
            $where['loc.warehouse_code'] = ['IN', $warehouse_code];
        }

        if (!empty($search['logistics_company'])) {
            $map['CD']                       = ['like', "N00070%"];
            $map['CD_VAL']                   = ['like', "%{$search['logistics_company']}%"];
            $logistics_company_code          = M('ms_cmn_cd', 'tb_')->where($map)->getField('CD', true);
            $where['loc.logistics_company_code'] = ['IN', $logistics_company_code];
        }
        if (!empty($search['logistics_mode'])) {
            $condition['IS_DELETE']      = 0;
            $condition['IS_ENABLE']      = 1;
            $condition['LOGISTICS_MODE'] = ['like', "%{$search['logistics_mode']}%"];
            $logistics_mode_id           = M('_ms_logistics_mode', 'tb_')->where($condition)->getField('ID', true);
            $where['loc.logistics_mode_id']  = ['IN', $logistics_mode_id];
        }
        $field = "loc.*, lm.LOGISTICS_MODE as logistics_mode";
        $query = $this->model->table('tb_ms_logistics_own_config loc')
            ->field($field)
            ->join('left join tb_ms_logistics_mode lm on loc.logistics_mode_id = lm.ID')
            ->where($where);
        $query_copy = clone $query;
        if ($request_data['pages']) {
            $page_info = $request_data['pages'];
            $limit = [($page_info['current_page'] - 1) * $page_info['per_page'], $page_info['per_page']];
        } else {
            $limit = [0, 10];
        }
        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        if (isset($page_info['current_page'])) {
            $i = ($page_info['current_page'] - 1) * $page_info['per_page'];
        } else {
            $i = 0;
        }
        $db_res = $query_copy->order('updated_at desc')->select();
        foreach ($db_res as &$value) {
           if ($value['is_own_logistics_warehouse']) {
                $value['is_own_logistics_warehouse_val'] = '是';
            } else {
                $value['is_own_logistics_warehouse_val'] = '否';
            }
            $value['number'] = ++$i;
        }
        $db_res = CodeModel::autoCodeTwoVal($db_res, [
            'warehouse_code',
            'logistics_company_code',
        ]);
        return [
            'data' => $db_res,
            'pages' => $pages
        ];
    }
    /**********************自有物流仓配置：end**********************/
}
