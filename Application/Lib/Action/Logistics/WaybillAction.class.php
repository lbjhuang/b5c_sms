<?php
/**
 * 运货单管理业务处理
 *
 *  即俗称的物流面单管理，这里主要是接口为主。
 *  实现物流面单的获取，列表、对接、CURD操作。
 *  打印的功能放到对应的页面上处理，数据的过去通过该类的接口获得。
 *
 * User: afanti
 * Date: 2017/10/12
 * Time: 18:35
 */

class WaybillAction extends BaseAction{
    const GSHOPPER_SOURCE = 'N001780300';
    const GSHOPPER_TEMPLATE = 'N001790200';
    
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 面单关联关系管理页面
     */
    public function Waybill()
    {
        $this->display();
    }
    
    /**
     * 面单格式配置页面
     */
    public function faceFormat()
    {
        //那个发货公司，订单号，物流单号
        $shipperId = I('get.ShipperId', 1);//默认G shopper中国公司，载鸿贸易
        $orderId = I('get.orderId','gspt508219035515');
        $logisticsNumber = I('logisticsNumber', '12345678901');
        
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        $barCode = $host . U('Universal/BarCode/getCode128',['codeNumber' => $logisticsNumber]);
        $shipper = new ShippingCompanyModel();
        $shipperInfo = $shipper->getShipperById($shipperId);
        
        $orderApi = HOST_URL . 'shipping/getOrderDetail.json?ids=' . $orderId;
        $data = ['orderId' => $orderId, 'orderApi' => $orderApi, 'barCode' => $barCode,'shipper' => $shipperInfo];
        $this->assign('baseData', json_encode($data,JSON_UNESCAPED_SLASHES));
        $this->display('faceFormat');
    }
    
    /**
     * 读取单个面单信息
     */
    public function getWaybill()
    {
        $id = $_REQUEST['id'];
        $orderId = I('get.orderId','gspt501575444005');//默认的为测试数据
        if (empty($id) || empty($orderId)){
            $result = ['code' => 40001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
        
        if (false !== strpos($id, ',')){
            $idList = explode(',', $id);
            if (count($idList) > 10){
                $result = ['code' => 40001, 'msg' => 'ID count can not more than 10!', 'data' => null];
                $this->jsonOut($result);
            }
        }
        
        $waybillModel = new WaybillModel();
        $waybill = $waybillModel->getWaybillById($id);
        $waybill = $waybillModel->parseFieldsMap($waybill);
    
        //读取物流公司，面单来源，面单模板类型数据
        $dictionary = new DictionaryModel();
        $dict = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::WAYBILL_SOURCE,
            DictionaryModel::WAYBILL_TEMPLATE
        ]);
        
        //添加面单相关名称解释
        foreach ($waybill as $key => $item){
            $item = $waybillModel->parseFieldsMap($item);
            $item['logisticsCompany'] = $dict[DictionaryModel::LOGISTICS_COMPANY][$item['logisticsCode']]['CD_VAL'];
            $item['templateName'] = $dict[DictionaryModel::WAYBILL_TEMPLATE][$item['templateCode']]['CD_VAL'];
            $item['sourceName'] = $dict[DictionaryModel::WAYBILL_SOURCE][$item['sourceCode']]['CD_VAL'];
            $waybill[$key] = $item;
        
        }
        
        $result = ['code' => 200, 'msg' => 'success', 'data' => $waybill];
        $this->jsonOut($result);
    }
    
    /**
     * 批量读取多个面单信息
     */
    public function searchWaybill()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        
        $page = !empty(trim($params['page']))? trim($params['page']) : 1;
        $rows = !empty(trim($params['rows']))? trim($params['rows']) : 20;
        $start = ($page - 1) * $rows;
        
        $params['start'] = $start;
        $params['rows'] = $rows;
    
        $waybillModel = new WaybillModel();
        $list = $waybillModel->searchWaybill($params);
        $total = $waybillModel->getTotalWaybill($params);
    
        //读取物流公司，面单来源，面单模板类型数据
        $dictionary = new DictionaryModel();
        $dict = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::WAYBILL_SOURCE,
            DictionaryModel::WAYBILL_TEMPLATE
        ]);
        
        foreach ($list as $key => $item){
            $item = $waybillModel->parseFieldsMap($item);
            $item['logisticsCompany'] = $dict[DictionaryModel::LOGISTICS_COMPANY][$item['logisticsCode']]['CD_VAL'];
            $item['templateName'] = $dict[DictionaryModel::WAYBILL_TEMPLATE][$item['templateCode']]['CD_VAL'];
            $item['sourceName'] = $dict[DictionaryModel::WAYBILL_SOURCE][$item['sourceCode']]['CD_VAL'];
            $list[$key] = $item;
            
        }
        
        $result = ['total' => $total, 'page' => $page, 'rows' => $rows, 'list' => $list];
        $this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $result]);
    }
    
    /**
     * 创建Waybill关系数据
     */
    public function createWaybill()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        if (empty($params)){
            $result = ['code' => 40003, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
        
        $waybillModel = new WaybillModel();
        $data['logisticsCode'] = !empty(trim($params['logisticsCode'])) ? trim($params['logisticsCode']) : '';
        $data['logisticsModeId'] = !empty(trim($params['logisticsModeId'])) ? trim($params['logisticsModeId']) : '';
        $data['sourceCode'] = !empty(trim($params['sourceCode'])) ? trim($params['sourceCode']) : '';
        $data['templateCode'] = !empty(trim($params['templateCode'])) ? trim($params['templateCode']) : '';
        
        if (self::GSHOPPER_SOURCE == $data['sourceCode'] && self::GSHOPPER_TEMPLATE != $data['templateCode']){
            $result = ['code' => 40003, 'msg' => L('WAYBILL_MISMATCHING'), 'data' => null];
            $this->jsonOut($result);
        }
        
        if (self::GSHOPPER_SOURCE != $data['sourceCode'] && self::GSHOPPER_TEMPLATE == $data['templateCode']){
            $result = ['code' => 40004, 'msg' => L('WAYBILL_MISMATCHING'), 'data' => null];
            $this->jsonOut($result);
        }
        
        
        //注意顺序，logisticsCode，logisticsModeId 是唯一索引，用来验证是否重复。
        $checkExist = $waybillModel->searchWaybill([
            'logisticsCode' => $data['logisticsCode'],
            'logisticsModeId' => $data['logisticsModeId']
        ]);
        if (!empty($checkExist)){
            $result = ['code' => 40004, 'msg' => L('HAS_EXIST'), 'data' => $checkExist[0]['ID']];
            $this->jsonOut($result);
        }
    
        //创建数据
        $res = $waybillModel->createWaybill($data);
        if (false === $res){
            $result = ['code' => 40005, 'msg' => L('INVALID_PARAMS'), 'data' => $res];
            $this->jsonOut($result);
        }
    
        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => ['id' => $res]));
    }
    
    /**
     * 更新waybill关系数据
     */
    public function updateWaybill()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        if (empty(trim($params['id']))){
            $result = ['code' => 40003, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
        
        $data = [];
        !empty(trim($params['logisticsCode'])) && $data['logisticsCode'] = trim($params['logisticsCode']);
        !empty(trim($params['logisticsModeId'])) && $data['logisticsModeId'] = trim($params['logisticsModeId']);
        !empty(trim($params['templateCode'])) && $data['templateCode'] = trim($params['templateCode']);
        !empty(trim($params['sourceCode'])) && $data['sourceCode'] = trim($params['sourceCode']);
        isset($params['isEnable']) && $data['isEnable'] = trim($params['isEnable']);
        
        $waybillModel = new WaybillModel();
        if (self::GSHOPPER_SOURCE == $data['sourceCode'] && self::GSHOPPER_TEMPLATE != $data['templateCode']){
            $result = ['code' => 40003, 'msg' => L('WAYBILL_MISMATCHING'), 'data' => null];
            $this->jsonOut($result);
        }
        
        //更新数据
        $res = $waybillModel->updateWaybill($data, ['id' => $params['id']]);
        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
    }
    
    /**
     * 删除
     */
    public function deleteWaybill(){
        //$id = trim(I('id'));
        if (empty($id)){
            $this->jsonOut(array('code' => 40012, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
    
        $waybillModel = new WaybillModel();
        $res = $waybillModel->deleteWaybill(['id' => $id]);
        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
        } else {
            $this->jsonOut(array('code' => 50001, 'msg' => L('NOT_EXIST'), 'data' => $res));
        }
    }
    
    /**
     * 打印面单，查看面单的页面上使用
     */
    public function shippingCompany()
    {
        $id = I('get.id');
        
        if (empty($id)){
            $this->jsonOut(array('code' => 40013, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
        $shippingCompany = new ShippingCompanyModel();
        $company = $shippingCompany->getShipperById($id);
        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $company));
    }
    
    /**
     * 读取列表，用于订单发货时指定
     */
    public function shippingCompanyList()
    {
        $shippingCompany = new ShippingCompanyModel();
        $list = $shippingCompany->getShipperList();
        $this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 导入物流模式数据
     */
    public function exportWaybillBind() {
        header('Content-Type: text/html;charset=utf-8');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=waybillBind.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
    
        $params['startTime'] = I('get.startTime');
        $params['endTime'] = I('get.endTime');
        $params['sourceCode'] = I('sourceCode');
    
        $waybillModel = new WaybillModel();
        $list = $waybillModel->searchWaybill($params);
    
        //读取物流公司，面单来源，面单模板类型数据
        $dictionary = new DictionaryModel();
        $dict = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::WAYBILL_SOURCE,
            DictionaryModel::WAYBILL_TEMPLATE
        ]);
    
        foreach ($list as $key => $item){
            $item = $waybillModel->parseFieldsMap($item);
            $item['logisticsCompany'] = $dict[DictionaryModel::LOGISTICS_COMPANY][$item['logisticsCode']]['CD_VAL'];
            $item['templateName'] = $dict[DictionaryModel::WAYBILL_TEMPLATE][$item['templateCode']]['CD_VAL'];
            $item['sourceName'] = $dict[DictionaryModel::WAYBILL_SOURCE][$item['sourceCode']]['CD_VAL'];
            $list[$key] = $item;
        
        }
        
        $title = [
            '编号',
            '物流公司CODE',
            '物流模式编码',
            '物流面单模板编码',
            '物流面单来源编码',
            '物流面单是否启用',
            '创建人',
            '创建日期',
            '更新日期',
            '物流模式名称',
            '物流公司名称',
            '物流面单模板名称',
            '物流面单来源名称'
        ];
        
        $this->outputCvs($title, $list);
    }
    
    private function outputCvs($title, $data)
    {
        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        //输出Excel列名信息
        foreach ($title as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }
        
        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);
        $num = 0;
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 1000;
        
        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            
            $row = $data[$i];
            foreach ($row as $key => $value) {
                //$value = str_replace([',', '，',"\r\n", "\r", "\n"], '-', $value);
                if(false !== strpos($value, ',')){
                    $value = "\"{$value}\"";
                }
                
                if(is_numeric($value)){
                    $value = "\t" . $value;
                }
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            
            fputcsv($fp, $row);
        }
    }
    
}