<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 10:56
 */

@import("@.Action.Scm.ScmBaseAction");

class DemandAction extends ScmBaseAction
{

    public function demand_list() {
        import('ORG.Util.Page');
        $param  = $this->params();
        $count  = D('Demand')->demandCount($param);
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = (new DemandModel())->demandList($param,$page->firstRow.','.$page->listRows);
        $this->ajaxSuccess(['list'=>$list,'page'=>['total_rows'=>$count]]);
    }

    public function demand_save() {
        $logic  = D('Scm/Demand','Logic');
        Logs($_POST, __FUNCTION__, __CLASS__);
        if (!$_POST['step'] || $_POST['step'] == 'N002120400') {
            $error_msg = $this->validate_demand_save_data($_POST);
            if (!empty($error_msg)) $this->ajaxError([], $error_msg);
        }
        $res = $logic->saveDemand($_POST);
        if($res) {
            $this->ajaxSuccess(['demand_id' => $logic->getRet()['data']['demand_id']]);
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    private function validate_demand_save_data(&$data)
    {
        if ($data['demand_type'] != 'N002100100') {
            //不为热销品囤货
            if ($data['rebate_rate'] && (!is_numeric($data['rebate_rate']) || $data['rebate_rate'] < 0)) {
                return '返利比例必须是数字，并且非负数';
            }
            $data['rebate_rate'] = $data['rebate_rate'] * 0.01;
            $rebate_amount = round($data['rebate_rate'] * $data['sell_amount'],2);
            if ($rebate_amount != $data['rebate_amount']) {
//                return '前后端返利金额计算不一致';
            }
        } else {
            unset($data['rebate_rate']);
            unset($data['rebate_amount']);
        }
    }

    public function release_spot() {
        $id         = I('request.id');
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->releaseSpot($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function demand_detail() {
        $id     = I('request.id');
        $demand = D('Scm/Demand','Logic')->demandDetail($id);
        $this->ajaxSuccess($demand);
    }

    public function demand_submit() {
        $id     = I('request.id');
        $profit = I('request.profit');
        $demand_l = D('Scm/Demand','Logic');
        $res = $demand_l->demandSubmit($id,$profit);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function demand_delete() {
        $id = I('request.id');
        $demand_l = D('Scm/Demand','Logic');
        $res = $demand_l->demandDelete($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function demand_approve() {
        $id         = I('request.id');
        $status     = I('request.status');
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->approve($id,$status);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    /**
     * @param bool $is_wechat
     */
    public function seller_leader_approve($data=[]) {
        $id         = I('request.id');
        $status     = I('request.status');
        $remark     = I('request.remark');
        if (!empty($data)) {
            $id         = $data['id'];
            $status     = $data['status'];
            $remark     = $data['remark'];
        }
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->approve($id,$status,'',$remark);
        if (!empty($data)) {
            return $res;
        }
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function ceo_approve($data = []) {
        $id         = I('request.id');
        $status     = I('request.status');
        if (!empty($data)) {
            $id         = $data['id'];
            $status     = $data['status'];
        }
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->approve($id,$status);
        $return_res = $this->cache_ret = $res ? ['code' => 2000, 'data' => ['demand_id' => $id], 'msg' => '处理完成'] : ['code' => 3000, 'data' => ['demand_id' => $id], 'msg' => '系统异常'];
        if (!empty($data)) {
            return $return_res;
        }
        if($res) {
            IS_POST ? $this->ajaxSuccess() : die('<h1>' . L('处理完成') . '</h1>');
        }else {
            IS_POST ? $this->ajaxError([],$demand_l->getError()) : die('<h1>' . L($demand_l->getError()) . '</h1>');
        }
    }

    public function choose_quotation() {
        $choose     = $_POST;
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->chooseQuotation($choose);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    /**
     * 需求确认后提交
     */
    /*
    public function demand_quotation_submit() {
        $id                     = $_POST['id'];
        $profit                 = $_POST['profit'];
        $profit['demand_id']    = $id;
        $demand_l = D('Scm/Demand','Logic');
        $res = $demand_l->demandQuotationSubmit($id,$profit);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }
    */

    /**
     * 放弃需求
     */
    public function demand_discard() {
        $id = I('request.id');
        $demand_l = D('Scm/Demand','Logic');
        $res = $demand_l->demandDiscard($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function import_goods() {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error(L('请上传EXCEL文件'),'',true);
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $expe = [];
        $msg = '';
        $cd_m = new TbMsCmnCdModel();
        $currency_list  = array_flip($cd_m->getCdKeyY(TbMsCmnCdModel::$currency_cd_pre));
        $auth_link_list = array_flip($cd_m->getCdKeyY(TbMsCmnCdModel::$auth_and_link_cd_pre));
        $search_c       = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $search         = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $currency       = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $price          = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $price_no_tax   = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            $number         = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue());
            $auth_and_link  = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());
            if($currentRow == 3) $currency_c = $currency;
            $goods      = D('Goods','Logic')->getGoods($search);
            if(!$search || !$goods) {
                $error = true;
                $msg .= "第{$currentRow}行商品异常<br />";
            }
            if($goods['supplier_cd'] != 'N002680001') {
                $error = true;
                $msg .= "第{$currentRow}行商品供应商不为GP<br />";
            }
            if(in_array($search,$search_c)) {
                $error = true;
                $msg .= "第{$currentRow}行商品重复<br />";
            }else {
                $search_c []= $search;
            }
            if(!$currency_list[$currency] || $currency != $currency_c) {
                $error = true;
                $msg .= "第{$currentRow}行币种有误<br />";
            }
            $price_input = $price ? $price : $price_no_tax;
            if(!is_numeric($price_input) || $price_input <= 0) {
                $error = true;
                $msg .= "第{$currentRow}行商品价格有误（比如数据中含0、或者为负数）<br />";
            }
            if(!is_numeric($number) || strstr($number,'.') || $number<=0) {
                $error = true;
                $msg .= "第{$currentRow}行商品数量有误（比如数量中含小数、或者小于1）<br />";
            }
            if(!$auth_link_list[$auth_and_link]) {
                $error = true;
                $msg .= "第{$currentRow}行授权和链路有误<br />";
            }
            if ($goods && $price_input && $number) {
                $goods['search_id']     = $search;
                $goods['currency']      = $currency_list[$currency];
                $goods['price']         = $price;
                $goods['price_no_tax']  = $price_no_tax;
                $goods['number']        = $number;
                $goods['auth_and_link'] = $auth_link_list[$auth_and_link];
                $expe[]             = $goods;
            }
        }
        if($error) {
            $this->ajaxError([],$msg);
        }else {
            $this->ajaxSuccess($expe);
        }
    }

    /*
     * 销售侧提交po
     */
    public function demand_upload_po()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $data['step'] = 'upload_po';
        $data['model'] = 'demand';
        $demand_l->handlePO($data);
        $this->ajaxReturn($demand_l->getRet());
    }
    /*
     * 销售侧法务审批
     */
    public function demand_justice_approve()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $demand_l->justiceApprove($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /*
     * 销售侧重上传po
     */
    public function demand_reupload_po()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $data['step'] = 'reupload_po';
        $data['model'] = 'demand';
        $demand_l->handlePO($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /**
     * 法务盖章
     */
    public function demand_justice_stamp()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $demand_l->justiceStamp($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /**
     * 法务盖章失败，直接提交按钮
     */
    public function demand_return_stamp()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand', 'Logic');
        $demand_l->returnStamp($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /**
     * 法务审批建议
     */
    public function demand_forensic_audit_proposal() {
        $data = $this->params();
        $demand_l = D('Scm/Demand', 'Logic');
        if($demand_l->forensicAuditProposal($data)) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError($demand_l->getError());
        }
    }

    /**
     * 法务审批建议
     */
    public function demand_audit_email() {
        $data = $this->params();
        $demand_l = D('Scm/Demand', 'Logic');
        if($demand_l->forensicAuditEmail($data['id'], $data['to'], $data['cc'])) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError($demand_l->getError());
        }
    }

    /**
     * PO添加水印
     */
    public function demand_watermark_to_po() {
        $data = $this->params();
        $demand_l = D('Scm/Demand', 'Logic');
        if($res = $demand_l->watermarkToPo($data['id'])) {
            $this->ajaxSuccess($res);
        }else {
            $this->ajaxError($demand_l->getError());
        }
    }

     /*
     * 销售侧归档po
     */
    public function demand_po_archive()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $data['step'] = 'po_archive';
        $data['model'] = 'demand';
        $demand_l->handlePO($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /*
    * 销售侧重新归档po
    */
    public function demand_po_rearchive()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $data['step'] = 'po_rearchive';
        $data['model'] = 'demand';
        $demand_l->handlePO($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /**
     * 创建订单
     */
    public function demand_create_order()
    {
        $data = $this->params();
        $demand_l = D('Scm/Demand','Logic');
        $demand_l->createOrder($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    /**
     * 处理申请
     */
    public function process_application() {
        $param = I('request.');
        $demand_l = D('Scm/Demand','Logic');
        $res = $demand_l->processApplication($param['id'],$param['process_method'],$param['approve_to_or_reason']);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }

    }

    /**
     * 退回草稿重新修改
     */
    public function return_to_draft() {
        $id         = I('request.id');
        $reason     = trim(I('request.reason'));
        $demand_l   = D('Scm/Demand','Logic');

        $rClineVal = RedisModel::lock('demand_id' . $id, 10);
        if (!$rClineVal) {
            $this->ajaxError([],'获取流水锁失败');
        }
        $res        = $demand_l->returnToDraft($id,$reason);
        if($res) {
            RedisModel::unlock('demand_id' . $id);
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function return_to_seller_choose() {
        $id         = I('request.id');
        $reason     = I('request.reason');
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->returnToSellerChoose($id,$reason);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function create_order() {
        $id         = I('request.id');
        $demand_l   = D('Scm/Demand','Logic');
        $res        = $demand_l->createOrders($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$demand_l->getError());
        }
    }

    public function resend_ceo_email()
    {
        $id = I('id');
        try {
            $demand = D('Scm/Demand')->field('id,demand_code,step,status,ceo_email_send')->where(['id' => $id])->find();
            if (empty($demand)) throw new \Exception('需求不存在');
            if ($demand['step'] != DemandModel::$step['ceo_approve'] || $demand['status'] != DemandModel::$status['untreated']) throw new \Exception('状态异常');
            $remain = 1800 + $demand['ceo_email_send'] - time();
            if ($remain > 0) {
                $m = floor($remain / 60);
                $s = $remain - 60 * $m;
                throw new \Exception("发送邮件过于频繁，请{$m}分{$s}秒后再操作");
            }
            ScmEmailModel::I($demand);
            $this->ajaxSuccess([], '审批提醒邮件发送成功');
        } catch (\Exception $e) {
            $this->ajaxError([], $e->getMessage() ?: '邮件发送失败，请联系飞松处理');
        }
    }

    public function batch_stock() {
        $param  = Mainfunc::getInputJson();
        $res    = ApiModel::batchStock($param);
        $this->ajaxReturn($res);
    }

    public function batch_search() {
        $param  = Mainfunc::getInputJson();
        $res    = ApiModel::batchSearch($param);
        $this->ajaxReturn($res);
    }

    public function on_way_search() {
        $param  = Mainfunc::getInputJson();
        $res    = ApiModel::batchSearch($param);
        $this->ajaxReturn($res);
    }

    public function save_deal_detail() {
        $demand_code    = $_REQUEST['demand_code'];
        $res            = D('Scm/Demand','Logic')->flushDealDetail($demand_code, 1);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],D('Scm/Demand','Logic')->getError());
        }
    }

    public function save_all_deal_detail() {
        set_time_limit(0);
        $res = D('Scm/Demand','Logic')->saveAllDealDetail();
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],D('Scm/Demand','Logic')->getError());
        }
    }

    public function deal_list() {
        import('ORG.Util.Page');
        $param  = $this->params();
        $count  = D('DealDetail')->dealCount($param);
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = D('DealDetail')->dealList($param,$page->firstRow.','.$page->listRows);
        $this->ajaxSuccess(['list'=>$list,'page'=>['total_rows'=>$count]]);
    }

    public function deal_export() {
        $param      = $this->params();
        $fileName   = 'SCM历史成交明细'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['需求编号', '交易类型', '业务类型', 'SKU编码', '条形码', '商品名称', '商品属性', '成交日期','单次需求总数', '供应商', '采购交货方式','入库仓库','采购币种','采购单价（不含增值税）','采购增值税单价','客户','销售交货方式','客户收货地址','销售币种','销售单价（不含增值税）','销售增值税单价'];

//        // 输出Excel列名信息
//        foreach ($head as $i => $v) {
//            // CSV的Excel支持GBK编码，一定要转换，否则乱码
//            $head[$i] =  $v;
//        }
        fputcsv($fp, $head);

        $list = D('DealDetail')->dealList($param);
        foreach ($list as $k => $v) {
            $row = [];
            $row[] =  $v['demand_code'];
            $row[] =  $v['deal_type'];
            $row[] =  $v['business_mode'];
            $row[] =  $v['sku_id']."\t";
            $row[] =  $v['bar_code']."\t";
            $row[] =  $v['goods_name'];
            $row[] =  $v['attributes'];
            $row[] =  $v['deal_time'];
            $row[] =  $v['require_number'];
            $row[] =  $v['supplier'];
            $row[] =  $v['delivery_type'];
            $row[] =  $v['warehouse'];
            $row[] =  $v['currency'];
            $row[] =  round($v['purchase_price_not_contain_tax'],2);
            $row[] =  round($v['purchase_price_tax'],2);
            $row[] =  $v['customer'];
            $row[] =  $v['receive_mode'];
            $row[] =  $v['receive_country'] . '-' . $v['receive_province'];
            $row[] =  $v['sell_currency'];
            $row[] =  round($v['sell_price_not_contain_tax'],2);
            $row[] =  round($v['sell_price_tax'],2);
            fputcsv($fp, $row);
        }
    }

    public function checkPredestinateNum()
    {
        $request_data = DataModel::getDataNoBlankToArr();
        $demand_code  = $request_data['demand_code'];
        $sku_ids      = $request_data['sku_ids'];

        $demand_l   = D('Scm/Demand','Logic');
        if ($demand_l->checkPredestinateNum($demand_code,$sku_ids)) {
            $res = DataModel::$success_return;
        } else {
            $res = DataModel::$error_return;
        }
        $this->ajaxReturn($res);
    }

}
