<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 10:56
 */

class CostAction extends ReportBaseAction
{

    public function cost_list() {
        $this->display();
    }

    public function list_data($request_data = null) {

      
        $request_data ? $data = $request_data : $data = $this->params();
        
        #一个半月
        $start_date = date('Y-m-d', strtotime('-45day'));
        // $start_date = '2019-06-01';
        
        $query_time = MongoDbModel::client()->findOne('tb_cost_list_data_log', [], ['query_time' => -1]);
         if(!empty($query_time['query_time'])){
            $query_time = $query_time['query_time'];
        }
       
        if(!empty($data['zd_date'][0]) && !empty($data['zd_date'][1]) && $data['zd_date'][0] >= $start_date && $query_time){

            #一个半月内 使用缓存
           
    
            $where = $this->validateRequestByMongodb($data);
            $where['query_time'] = $query_time;
            $page = !empty($data['page'])? $data['page']:1;
            $page_size = !empty($data['page_size']) ? $data['page_size'] : 20;
            $skip = ($page - 1) * $page_size;
            $option = ['limit' => $page_size, 'skip' => $skip];
            $count = MongoDbModel::client()->count('tb_cost_list_data', $where);
            $list = MongoDbModel::client()->find('tb_cost_list_data', $where, $option);
            $list = SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes']);
            $re['list'] = $list;
            $re['page'] = $page;
            $re['page_size'] = $page_size;
            $re['total'] = $count;
            
            $this->ajaxSuccess($re);

        }else{
            $logic = D('Report/Cost', 'Logic');
            $logic->listData($data);
            if ($request_data) {
                return $logic->getData();
            }
            $this->ajaxReturn($logic->getRet());
        }
       
        
    }
    #过滤组装where数据
    private function validateRequestByMongodb($data){
        $where = [];
        if(!empty($data['zd_date'])) $where['zd_date'] = ['$gte'=> $data['zd_date'][0].' 00:00:00', '$lte'=> $data['zd_date'][1].'23:59:59'];
        if(!empty($data['purchase_order_no'])) $where['purchase_order_no'] = ['$regex'=> $data['purchase_order_no']];
        if (!empty($data['our_company'])) $where['our_company_cd'] = ['$in' => $data['our_company']];
        if (!empty($data['purchase_team'])) $where['purchase_team_cd'] = ['$in' => $data['purchase_team']];
        if (!empty($data['pur_create_time'])) $where['pur_create_time'] = ['$gte' => $data['pur_create_time'][0] . ' 00:00:00', '$lte' => $data['pur_create_time'][1] . '23:59:59'];
        if (!empty($data['warehouse'])) $where['warehouse_cd'] = ['$in' => $data['warehouse']];
        if(!empty($data['sku_upc_id'])) $where['$or'] = [['sku_id'=>$data['sku_upc_id']], ['upc_id'=>$data['sku_upc_id']]];
        if (!empty($data['relation_type'])) $where['relation_type_cd'] = ['$in' => $data['relation_type']];
        if (!empty($data['spu_name'])) $where['spu_name'] = ['$regex' => $data['spu_name']];
        if (!empty($data['supplier'])) $where['supplier'] = ['$regex' => $data['supplier']];
        if (!empty($data['zd_user'])) $where['zd_user_id'] = ['$in' => $data['zd_user']];
        return $where;
    }
    public function export() {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $params = json_decode($this->params()['export_params'], true);
        $params['page_size'] = -1;
        $params['isExport'] = true;
        $logic = D('Report/Cost', 'Logic');
//        $logic->export($data);
        $logic->listData($params);
        $data = $logic->data['list'];
        $map = [
            ['field_name' => 'bill_no', 'name' => L('出库ID')],
            ['field_name' => 'pur_currency', 'name' => '成本币种'],
            ['field_name' => 'pur_amount_no_tax', 'name' => '不含税采购金额'],
            ['field_name' => 'tax_rate', 'name' => '增值税率'],
            ['field_name' => 'tax', 'name' => '增值税额'],
            ['field_name' => 'relation_type', 'name' => '业务类型'],
            ['field_name' => 'purchase_order_no', 'name' => '采购单号'],
            ['field_name' => 'our_company', 'name' => '我方采购公司'],
            ['field_name' => 'purchase_team', 'name' => '采购团队'],
            ['field_name' => 'pur_create_time', 'name' => '采购单创建日期'],
            ['field_name' => 'warehouse', 'name' => '仓库'],
            ['field_name' => 'sku_id', 'name' => 'sku编码'],
            ['field_name' => 'upc_id', 'name' => '条形码'],
            ['field_name' => 'spu_name', 'name' => '商品名称'],
            ['field_name' => 'attributes', 'name' => '商品属性'],
            ['field_name' => 'batch_code', 'name' => '批次号'],
            ['field_name' => 'send_num', 'name' => '数量'],
            ['field_name' => 'unit', 'name' => '单位'],
            ['field_name' => 'zd_user', 'name' => '操作人'],
            ['field_name' => 'zd_date', 'name' => '出库时间'],
            ['field_name' => 'supplier', 'name' => '供应商'],
        ];
        $this->exportCsv($data, $map);
    }
}