<?php

/**
 * 订单导出模板
 * Class OrderExportTemplate
 */
class OrderExportTemplateAction extends BasisAction{


    /**
     *  模板字段
     */
    public function get_field(){
        $search = $_GET['search'];
        $lsit = array(
            'package_info' => array_change_key_case(OrderExportTemplateService::$package_info,CASE_LOWER),
            'date_info' => array_change_key_case(OrderExportTemplateService::$date_info,CASE_LOWER),
            'amount_info' => array_change_key_case(OrderExportTemplateService::$amount_info,CASE_LOWER),
            'product_info' => array_merge(array_change_key_case(OrderExportTemplateService::$product_info,CASE_LOWER),
                array_change_key_case(OrderExportTemplateService::$fixed_product_info,CASE_LOWER)),
            'buyer_info' => array_change_key_case(OrderExportTemplateService::$buyer_info,CASE_LOWER),
            'logistics_info' => array_merge(array_change_key_case(OrderExportTemplateService::$logistics_info,CASE_LOWER),
                array_change_key_case(OrderExportTemplateService::$fixed_logistics_info,CASE_LOWER)),
            'business_info' => array_change_key_case(OrderExportTemplateService::$business_info,CASE_LOWER),
        );
        $data = array();
        foreach ($lsit as $key => $value){
            foreach ($value as $k => $v){
                if ($search){
                    if (strpos($v,$search) !== false){
                        $data[$key][] = array(
                            'name_en' => $k,
                            'name_cn' => $v,
                        );
                    }
                }else{
                    $data[$key][] = array(
                        'name_en' => $k,
                        'name_cn' => $v,
                    );
                }
            }

        }
        $this->ajaxReturn(['code' => 2000, 'msg' => 'success', 'data' => $data]);
    }

    public function get_export_template(){
        $service = new  OrderExportTemplateService();
        $list = $service->getList(array());
        $oneself = array();
        $other_people = array();
        if ($list){
            foreach ($list as $value){
                if ($value['create_by'] == userName()){
                    $oneself[] = $value;
                }else{
                    $other_people[] = $value;
                }
            }
        }
        $this->ajaxSuccess(array('oneself'=>$oneself,'other_people'=>$other_people));
    }

    /**
     *  订单导出
     */
    public function export()
    {
        ini_set('max_execution_time', '120');
        session_write_close();
        try{
            $post_data = DataModel::getData();
            $post_data['sort'] = 'ORDER_TIME desc';
            $service = new OrderExportTemplateService();
            $res = $service->export($post_data);
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),'4000');
        }
        $this->ajaxSuccess(['id'=>$res]);

    }

    public function lists(){
        $service = new OrderExportTemplateService();
        $res = $service->getList(array());
        $this->ajaxSuccess($res);
    }

    public function add(){
        $request_data = DataModel::getDataNoBlankToArr();
        try{
            if ( empty($request_data['name'] ) || empty($request_data['field_json'] ) ){
                throw new Exception('请求参数异常');
            }
            $service = new OrderExportTemplateService();
            $info = $service->getFind(array('name'=>$request_data['name']),'id');
            if ($info){
                throw new Exception('模板名称已存在，请修改后再保存！');
            }
            $insert_data = array(
                'name' => $request_data['name'],
                'field_json' => json_encode($request_data['field_json']),
                'create_by' => userName(),
                'create_at' => date("Y-m-d H:i:s"),
                'update_by' => userName(),
                'update_at' => date("Y-m-d H:i:s")
            );
            $res = $service->add($insert_data);
            if (!$res){
                throw new Exception('模板添加异常');
            }
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),'4000');
        }
        $this->ajaxSuccess(['id'=>$res]);
    }

    public function update(){
        $request_data = DataModel::getDataNoBlankToArr();
        try{
            if (empty($request_data['id'] ) ||  empty($request_data['name'] ) || empty($request_data['field_json'] ) ){
                throw new Exception('请求参数异常');
            }
            $condtion = array('id'=>$request_data['id']);
            $service = new OrderExportTemplateService();
            if (!$service->auth($condtion)){
                throw new Exception('不可修改他人创建的模板！');
            }

            $info = $service->getFind(array('name'=>$request_data['name'],'id'=>array('NEQ',$request_data['id'])),'id');
            if ($info){
                throw new Exception('模板名称已存在，请修改后再保存！');
            }
            $update_data = array(
                'name' => $request_data['name'],
                'field_json' => json_encode($request_data['field_json']),
                'update_by' => userName(),
                'update_at' => date("Y-m-d H:i:s")
            );
            $res = $service->update($condtion,$update_data);
            if ($res === false){
                throw new Exception('模板更新异常');
            }
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),'4000');
        }
        $this->ajaxSuccess(['id'=>$request_data['id']]);
    }

    public function del(){
        $request_data = DataModel::getDataNoBlankToArr();
        try{
            if (empty($request_data['id'] )){
                throw new Exception('请求参数异常');
            }
            $service = new OrderExportTemplateService();
            $condtion = array('id'=>$request_data['id']);
            if (!$service->auth($condtion)){
                throw new Exception('不可修改他人创建的模板！');
            }
            $res = $service->del($condtion);
            if ($res === false){
                throw new Exception('模板删除异常');
            }
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),'4000');
        }
        $this->ajaxSuccess(['id'=>$request_data['id']]);
    }
}
