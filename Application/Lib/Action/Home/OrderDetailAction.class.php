<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/4/19
 * Time: 13:31
 */


class OrderDetailAction extends BaseAction{

    //发票类型与oa对应关系
    public $invoice_relation = [
        "N001350100" => "26",
        "N001350200" => "27",
        "N001350300" => "29",
        "N001350400" => "521",
        "N001350500" => "722",
    ];

    //货币类型与oa对应关系
    public $currency_relation = [
        "N000590100" => "2",
        "N000590200" => "1",
        "N000590300" => "0",
        "N000590400" => "4",
        "N000590500" => "6",
        "N000590600" => "3",
        "N000590700" => "5",
    ];

    public function _initialize()
    {
       if (! in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && ! in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS')))
        {
            parent::_initialize();

        }
    }



    //#10380 历史异常应付、抵扣金处理
    public function fixHistPayDeduction()
    {
        try {
            $model = new Model();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new PurPaymentService($model))->fixPurHistPayDeduction();
            $model->commit();
            p($res);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $model->rollback();
            $this->error($res['msg']);
        }
    }

    // 组装数据
    public function assemblingClause($list)
    {
        $dataList = [];
        foreach ($list as $key => $value) {
            $dataList[$value['procurement_number']]['pur_order'] = $value['procurement_number'];
            if ($value['clause_type'] == '1') { // 预付款
                $dataList[$value['procurement_number']]['pre_pay'] .= $value['msg'] . PHP_EOL;
            } elseif ($value['clause_type'] == '2') { // 尾款
                $dataList[$value['procurement_number']]['end_pay'] = $value['msg'];
            } 
        }
        return $dataList;
    }
    // 采购单条款导出
    public function clause_export() {
        $fileName = 'clause_export'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $model  = M('clause','tb_pur_');
        $sql = "SELECT
CASE
        pc.action_type_cd 
    WHEN 'N002860002' THEN
    CONCAT(mcc.CD_VAL , '(', pc.pre_paid_date, ')付PO金额',  ROUND(pc.percent), '%') 
    ELSE 
    CONCAT( mcc.CD_VAL, '(', pc.days, ')天付PO金额', ROUND(pc.percent), '%') 
    END AS msg,
    pod.procurement_number,
    pc.clause_type
FROM
    tb_pur_clause pc
    LEFT JOIN tb_pur_relevance_order pro ON pro.relevance_id = pc.purchase_id
    LEFT JOIN tb_pur_order_detail pod ON pod.order_id = pro.order_id
    LEFT JOIN tb_ms_cmn_cd mcc ON mcc.CD = pc.action_type_cd 
WHERE
    pod.procurement_number IS NOT NULL
AND
    pc.purchase_id IS NOT NULL";
        $list = M()->query($sql);
        $list = $this->assemblingClause($list);
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['采购单号', '预付款', '尾款'];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] =  $v;
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($list as $k => $v) {
            $row = [];
            $row[] =  $v['pur_order']."\t";
            $row[] =  $v['pre_pay']."\t";
            $row[] =  $v['end_pay']."\t";
            fputcsv($fp, $row);
        }
    }

    public function deduction_where($param = [])
    {
        $where = [];
        if ($param['deduction_currency_cd']) {
            $where['t.deduction_currency_cd'] = $param['deduction_currency_cd']; 
        }
        if ($param['our_company_cd']) {
            $where['t.our_company_cd'] = $param['our_company_cd']; 
        }
        if ($param['supplier_id']) {
            $where['t.supplier_id'] = $param['supplier_id']; 
        }
        if ($param['type'] == '2' || $param['type'] == '4') {
            $where['pdd.is_revoke'] = '0';
        }
        if ($param['order_no']) {
            $where['t.order_no'] = $param['order_no'];
        }
        return $where;
    }
    // 抵扣金供应商列表数据导出（余额导出，明细导出）
    public function deduction_sup_export() {

        $fileName = 'supplier_deduction'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $param = $this->params();
        $param = $param['export_params'];
        $param = json_decode($param, true);
        $where = $this->deduction_where($param);
        $head = ['供应商ID', '我方公司', '供应商名称', '供应商名称（EN）', '币种', '金额'];
        $field = ['t.supplier_id', 't.our_company_name', 't.supplier_name_cn','t.supplier_name_en','t.deduction_currency_cd'];

        if ($param['type'] === '1') { // 余额导出
            array_push($field, 't.over_deduction_amount');
            $list   = M('deduction','tb_pur_')
                ->field($field)
                ->alias('t')
                ->where($where)
                ->select();
        } elseif ($param['type'] === '2') { // 明细导出
            array_push($head, '采购单号', '备注', '触发操作', '适用条款', '关联单据号', '确认人', '确认时间');
            array_push($field, 'pdd.deduction_amount','pdd.id', 'pdd.order_no', 'pdd.remark', 'pdd.created_at', 'pdd.created_by', 'pdd.turnover_type');
            $list   = M('deduction','tb_pur_')
                ->field($field)
                ->alias('t')
                ->join('left join tb_pur_deduction_detail pdd on pdd.deduction_id = t.id')
                ->where($where)
                ->select();
            $list = D('Scm/PurOperation')->getAssemOperationInfo($list, '2');
        } elseif ($param['type'] === '3') { // 赔偿返利余额导出
            $head = ['采购单号','供应商ID', '我方公司', '供应商名称', '供应商名称（EN）',  '币种', '余额'];
            $field = ['t.order_no','t.supplier_id', 'css.SP_NAME as supplier_name_cn','css.SP_NAME_EN as supplier_name_en','mcc.CD_VAL as our_company_name','t.deduction_currency_cd', 't.over_deduction_amount'];
            $list   = M('deduction_compensation','tb_pur_')
                ->field($field)
                ->alias('t')
                ->join('left join tb_crm_sp_supplier css on css.ID = t.supplier_id')
                ->join('left join tb_ms_cmn_cd mcc on mcc.CD = t.our_company_cd')
                ->where($where)
                ->select();
        } elseif ($param['type'] === '4') {
            $head = ['采购单号','供应商ID', '我方公司', '供应商名称', '供应商名称（EN）', '币种', '余额'];
            array_push($head, '备注', '抵扣凭证', '确认人', '确认时间');
            $field = ['t.supplier_id', 'mcc.CD_VAL as our_company_name', 'css.SP_NAME as supplier_name_cn','css.SP_NAME_EN as supplier_name_en','t.deduction_currency_cd', 't.order_no' , 'pdd.deduction_amount', 't.order_no', 'pdd.remark', 'pdd.deduction_voucher', 'pdd.created_by', 'pdd.created_at', 'pdd.turnover_type'];
            $list   = M('deduction_compensation','tb_pur_')
                ->field($field)
                ->alias('t')
                ->join('left join tb_crm_sp_supplier css on css.ID = t.supplier_id')
                ->join('left join tb_ms_cmn_cd mcc on mcc.CD = t.our_company_cd')
                ->join('left join tb_pur_deduction_compensation_detail pdd on pdd.deduction_id = t.id')
                ->where($where)
                ->select();
            $list = (new PurService())->getVoucher($list);
        }

        if ($list) {
            $list = CodeModel::autoCodeTwoVal($list, ['deduction_currency_cd']);            
            $list = (new PurService())->changeSupplierName($list);
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = $v;
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);
        foreach ($list as $k => $v) {
            $row = [];
            if ($param['type'] === '3' || $param['type'] === '4') {
                $row[] = $v['order_no']."\t";
            }
            $row[] = $v['supplier_id']."\t";
            $row[] = $v['our_company_name']."\t";
            $row[] = $v['supplier_name_cn']."\t";
            $row[] = $v['supplier_name_en']."\t";
            $row[] = $v['deduction_currency_cd_val']."\t";
            if ($v['deduction_amount'] !== null) { // 明细
                if ($v['turnover_type'] == '1') {
                    $row[] = '-' . number_format($v['deduction_amount'], 2, '.', ',');
                } elseif ($v['turnover_type'] == '2') {
                    $row[] = number_format($v['deduction_amount'], 2, '.', ',');
                }
            } else { // 余额
                $row[] =number_format($v['over_deduction_amount'], 2, '.', ',');
            }
            if ($param['type'] === '2') {
                $row[] = $v['order_no']."\t";
                $row[] = $v['remark']."\t";
                $row[] = $v['action_type_cd_val']."\t";
                $row[] = $v['clause']."\t";
                $row[] = $v['bill_no']."\t";
                $row[] = $v['created_by']."\t";
                $row[] = $v['created_at']."\t";
            }
            if ($param['type'] === '4') {   
                $row[] = $v['remark']."\t";
                $row[] = $v['deduction_voucher_name_str']."\t";
                $row[] = $v['created_by']."\t";
                $row[] = $v['created_at']."\t";
            }
            fputcsv($fp, $row);
        }
    }

    // 抵扣金/应付金导出
    public function pur_operation_pay_export() {
        $fileName = 'payment'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $where = [];
        $model  = M('operation','tb_pur_');
        $where['t.money_type'] = '1'; // 应付
        $where['t.clause_type'] = array('in', ['7', '10']);
        $where['t.action_type_cd'] = array('in', ['N002870006', 'N002870018']);
        $list = $model
            ->alias('t')
            ->field('pp.payment_no, pp.relevance_id, pod.procurement_number, t.action_type_cd, t.clause_type, pod.supplier_id, pp.our_company, pp.status, pod.amount_currency, pp.amount_payable, pp.amount_payable_split, pp.amount_deduction, pp.amount_account, t.created_by, t.created_at')
            ->join('left join tb_pur_payment pp on pp.id = t.main_id')
            ->join('left join tb_pur_relevance_order pro on pro.relevance_id = pp.relevance_id')
            ->join('left join tb_pur_order_detail pod on pod.order_id = pro.order_id')
            ->where($where)
            ->select();
        $newList = [];
        $status_list = ['待确认', '待付款', '待出账', '已完成', '待审核', '已删除'];
        $clause_type_list = [7 => '无尾款', 10 => '尾款-每次发货后X天付款 & 无尾款'];
        $purOperationModel = D('Scm/PurOperation');
        // 过滤数据 保留（对应采购单尾款字段=无）
        foreach ($list as $key => $value) {
            $flag = false;
            if (!$value['relevance_id']) {
                continue;
            }
            $flag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($flag) {
                $newList[] = $value;
            }
        }


        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['Payment-Key', '采购单号','触发操作','适用条款','供应商','我方公司','应付单状态','预计付款币种','确认前-本期应付金额（拆分前）','确认前-本期应付金额（拆分后）','使用抵扣金金额','本单分摊扣款金额','创建人','创建时间'];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] =  $v;
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($newList as $k => $v) {
            $row = [];
            $row[] =  $v['payment_no'];
            $row[] =  $v['procurement_number']."\t";
            $row[] =  cdVal($v['action_type_cd']);
            $row[] =  $clause_type_list[$v['clause_type']];
            $row[] =  $v['supplier_id'];
            $row[] =  cdVal($v['our_company']);
            $row[] =  $status_list[$v['status']];
            $row[] =  cdVal($v['amount_currency']);
            $row[] =  $v['amount_payable'];
            $row[] =  $v['amount_payable_split'];
            $row[] =  $v['amount_deduction'];
            $row[] =  $v['amount_account'];
            $row[] =  $v['created_by'];
            $row[] =  $v['created_at'];
            fputcsv($fp, $row);
        }
    }

    // 数据变更
    public function change_operation_data()
    {
        $where = [];
        $model  = M('operation','tb_pur_');
        $where['clause_type'] = '8';
        $where['action_type_cd'] = array('in', ['N002870006', 'N002870005', 'N002870004', 'N002870016']);
        $save = ['clause_type' => '10'];
        $res = $model->where($where)->save($save);
        p($res);die;
    }
    // 抵扣金
    public function pur_operation_deduction_export() {
        $fileName = 'deduction'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $where = [];
        $model  = M('operation','tb_pur_');
        $where['t.money_type'] = '2'; // 抵扣
        $where['t.clause_type'] = array('in', ['7', '10']);
        $where['t.action_type_cd'] = array('in', ['N002870017', 'N002870005', 'N002870004', 'N002870016']);
        $list = $model
            ->alias('t')
            ->field('pod.supplier_id, pod.our_company, pod.amount_currency, pod.procurement_number, pp.deduction_amount, t.action_type_cd, t.clause_type, t.created_by, t.created_at, pro.relevance_id')
            ->join('left join tb_pur_deduction_detail pp on pp.id = t.main_id')
            ->join('left join tb_pur_order_detail pod on pod.procurement_number = pp.order_no')
            ->join('left join tb_pur_relevance_order pro on pro.order_id = pod.order_id')
            ->where($where)
            ->select();
        $newList = [];
        $clause_type_list = [7 => '无尾款', 10 => '尾款-每次发货后X天付款 & 无尾款'];
        $purOperationModel = D('Scm/PurOperation');
        // 过滤数据 保留（对应采购单尾款字段=无）
        foreach ($list as $key => $value) {
            $flag = false;
            if (!$value['relevance_id']) {
                continue;
            }
            $flag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($flag) {
                $newList[] = $value;
            }
        }

// 解决有空白数据导出的问题

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['供应商' , '我方公司', '币种', '采购单号', '金额', '触发操作', '适用条款', '确认人', '确认时间'];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] =  $v;
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($newList as $k => $v) {
            $row = [];
            $row[] =  $v['supplier_id'];
            $row[] =  cdVal($v['our_company']);
            $row[] =  cdVal($v['amount_currency']);
            $row[] =  $v['procurement_number']."\t";
            $row[] =  $v['deduction_amount'];
            $row[] =  $clause_type_list[$v['clause_type']];
            $row[] =  cdVal($v['action_type_cd']);
            $row[] =  $v['created_by'];
            $row[] =  $v['created_at'];
            fputcsv($fp, $row);
        }
    }

    public function fetch($templateFile = '', $content = '', $prefix = '')
    {
        return parent::fetch($templateFile, $content, $prefix); // TODO: Change the autogenerated stub
    }

    /**
     * 添加采购订单
     * @param bool $is_review
     */
    public function order_add($is_review = false){
        $order_detail = M('order_detail','tb_pur_'); //实例化采购信息表
        if($this->isPost()){
            $add_data = $this->params();
            if($add_data['contract_number']) {
                $add_data['has_contract'] = 1;
            }else {
                $add_data['has_contract'] = 0;
            }
            if($add_data['procurement_number']) {
                if($order_detail->where(['procurement_number'=>$add_data['procurement_number']])->find()) {
                    $this->error(L('PO单号或采购单号') . $add_data['procurement_number'] .L( '已经创建过订单，请勿重复创建'), U('orderDetail/order_list', 2));
                }
            }else {
                $this->error(L('请填写PO单号或采购单号'));
            }
            if ($_FILES['attachment']['name'] || $_FILES['approve_credential']['name']) {
                // 图片上传
                $fd = new FileUploadModel();
                $ret = $fd->uploadFileArr();
                if($ret){
                    foreach($ret as $v) {
                        if($v['key'] == 'attachment') {
                            $add_data['attachment'][] = $v['savename'];
                        }else {
                            $add_data['approve_credential'] = $v['savename'];
                        }
                    }
                    $add_data['attachment'] = implode(',',$add_data['attachment']);
                }else {
                    $this->error(L("修改失败：上传文件失败").$fd->error,U('OrderDetail/order_list'),2);
                }
            }
            if($add_data['payment_info']) {
                $add_data['payment_info'] = json_encode($add_data['payment_info']);
            }else {
                $purchase_info['payment_info'] = '';
            }
            $add_data['create_time']            = date("Y-m-d H:i:s",time());
            $add_data['money_total_rmb']        = I('post.money_total_rmb');;
            $add_data['supplier_invoice_title'] = I('post.supplier_id');;
            $model = new Model();
            $model->startTrans();
            $order_id                   = M('order_detail','tb_pur_')->add($add_data); //采购信息
            $sell_id                    = M('sell_information','tb_pur_')->add($add_data); //销售信息
            $drawback_id                = M('drawback_information','tb_pur_')->add($add_data); //退税信息
            $predict_id                 = M('predict_profit','tb_pur_')->add($add_data);
            //下面将上面的每张表汇总到关联的订单表中
            $prepared_by                = I('post.prepared_by');
            $prepared_time              = date("Y-m-d H:i:s",time());
            $add_data['prepared_time']  = $prepared_time; //加入制单时间
            $creation_time              = date("Y-m-d H:i:s",time()); //该时间是向订单关联总表中插入使用的，是为了方便搜索使用
            $creation_times             = date("Y-m-d H:i:s",time()); //该时间是向采购应付表中插入使用的，是为了方便搜索使用
            $sou_time                   = date("Y-m-d",time()); //搜索的时间，时间与制单时间保持一致，格式为年月日
            $add_data['sou_time']       = $sou_time;
            $add_data['creation_times'] = $creation_times;
            $receipt_time               = substr("$prepared_time",0,10); //订单编号的时间
            $receipt_time               = explode('-',$receipt_time); //将时间格式进行转换
            $receipt_time               = implode('',$receipt_time); //将时间格式进行转换
            $new_order                  = M('payable','tb_pur_')->order("prepared_time desc")->find(); //查出最新的一条关联订单
            $receipt_number             = $new_order['receipt_number']; //查出的最新的订单编号
            $serial_number              = substr("$receipt_number",-4)+1; //流水号
            $serial_number              = sprintf("%04d", $serial_number); //不够四位数用0自动补全
            $receipt_number             = 'YF'.$receipt_time.$serial_number;
            $number_total               = I('post.number_total');//接收商品数量的合计
            $money_total                = I('post.money_total');//接收商品金额的合计
            $money_total_rmb            = I('post.money_total_rmb');//接收合计的外币金额换算成人民币的金额
            $show_total_rate            = I('post.show_total_rate'); //用于保存 1USD = 6.8933RMB格式的汇率到数据库，然后在修改的时候显示
            $real_total_rate            = I('post.real_total_rate'); //用于保存当天的真实汇率
            $curType                    = $add_data['curType'];  //接收商品的币种
            $add_data['receipt_number'] = $receipt_number;
            //$payable_id                 = M('payable','tb_pur_')->add($add_data);
            $collect_order              = compact("order_id","sell_id","curType","sou_time","drawback_id","predict_id","prepared_by","prepared_time","create_time","receipt_number","number_total","money_total","payable_id","creation_time","money_total_rmb","show_total_rate","real_total_rate"); //所有订单ID打包汇总插入关联的订单表中
            $collect_order['last_update_time']  = date('Y-m-d H:i:s');
            $collect_order['last_update_user']  = $_SESSION['m_loginname'];
            $collect_order['prepared_by']       = $_SESSION['m_loginname'];
            $relevance_id                       = D('TbPurRelevanceOrder')->add($collect_order); //将相关信息向订单关联总表中插入

            //将商品信息接收入库
            $search_information = I("post.search_information");
            $sku_information    = I("post.sku_information");
            $goods_name         = I("post.goods_name");
            $goods_attribute    = I("post.goods_attribute");
            $unit_price         = I("post.unit_price");
            $goods_number       = I("post.goods_number");
            $goods_money        = I("post.goods_money");
            $hotness            = I("post.hotness");
            $num                = count($search_information);
            for($i=0;$i<$num;$i++){
                $goods['search_information']    = $search_information[$i];
                $goods['sku_information']       = $sku_information[$i];
                $goods['goods_name']            = $goods_name[$i];
                $goods['invoice_name']          = $goods_name[$i];
                $goods['goods_attribute']       = $goods_attribute[$i];
                $goods['unit_price']            = $unit_price[$i];
                $goods['goods_number']          = $goods_number[$i];
                $goods['goods_money']           = $goods_money[$i];
                $goods['hotness']               = $hotness[$i];
                $goods['create_time']           = date("Y-m-d H:i:s",time());  //形成一一对应关系所以进行循环
                $goods['relevance_id']          = $relevance_id;
                $goods_all[]                    = $goods;
            }
            $goods_add_res = M('goods_information','tb_pur_')->addAll($goods_all);

            if($order_id&&$sell_id&&$drawback_id&&$predict_id&&$relevance_id&&$goods_add_res){
                (new TbPurActionLogModel())->addLog($relevance_id);
                $model->commit();
                if($is_review) {
                    return $relevance_id;
                }else {
                    $this->ajaxReturn($relevance_id,L('保存成功'),1);
                }
            }else{
                $model->rollback();
                if($is_review) {
                    return false;
                }else {
                    $this->ajaxReturn(0,L('保存失败'),0);
                }
            }
        }else{
            $cmn_cd             = new TbMsCmnCdModel(); // 实例化数据字典表
            $admin              = M('admin', 'bbm_'); // 实例化用户表
            $user_id            = $_SESSION['user_id'];
            $admin_info         = $admin->where("M_ID='$user_id'")->select();
            $this->assign('invoice_relation',$this->invoice_relation);
            $this->assign('currency_relation',$this->currency_relation);
            $this->assign('admin_info',$admin_info);
            $this->payment_type         = TbPurOrderDetailModel::$payment_type;
            $this->payment_period       = TbPurOrderDetailModel::$payment_period;
            $this->payment_day_type     = TbPurOrderDetailModel::$payment_day_type;
            $this->cmn_cd_info          = $cmn_cd->getCdY($cmn_cd::$purchase_team_cd_pre);
            $this->business_direction   = $cmn_cd->getCdY($cmn_cd::$business_direction_cd_pre);
            $this->business_type        = $cmn_cd->getCdY($cmn_cd::$business_type_cd_pre);
            $this->delivery_type        = $cmn_cd->getCdY($cmn_cd::$delivery_type_cd_pre);
            $this->tax_rate             = $cmn_cd->getCdY($cmn_cd::$tax_rate_cd_pre);
            $this->sell_team            = $cmn_cd->getCdY($cmn_cd::$sell_team_cd_pre);
            $this->sell_mode            = $cmn_cd->getCdY($cmn_cd::$sell_mode_cd_pre);
            $this->invoice_type         = $cmn_cd->getCdY($cmn_cd::$invoice_type_cd_pre);
            $this->our_company          = $cmn_cd->getCdY($cmn_cd::$our_company_cd_pre);
            $this->currency             = $cmn_cd->getCdY($cmn_cd::$currency_cd_pre);
            $this->payment_node         = $cmn_cd->getCdY($cmn_cd::$payment_node_cd_pre);
            $this->payment_days         = $cmn_cd->getCdY($cmn_cd::$payment_days_cd_pre);
            $this->payment_percent      = $cmn_cd->getCdY($cmn_cd::$payment_percent_cd_pre);
            $this->currency_rates       = (new TbMsXchrModel())->currency_rates();
            $this->users                = B2bModel::get_user();
            $this->country              = (new TbCrmSiteModel())->getChildrenAddress(0);
            //$this->assign('supper_info',$supper_info);
            $this->display('order_update');
        }
    }


    /**
     * 调取汇率的接口
     */
    public function exchange_rate(){
        $src_currency = I('get.currency'); //获取原始币种
        $rate = exchangeRate($src_currency);
        if (empty($rate)) {
            $reduce_date =  date('Ymd',strtotime('-1 day'));
            $rate = exchangeRate($src_currency,$reduce_date);
        }
        if($rate) {
            $this->success(['rate'=>$rate]);
        }else {
            $this->error(['rate'=>$rate]);
        }

    }


    /**
     * sku信息查询
     */
    public function order_add_ajax(){
        $sku = I('post.sku');
        $now_number = I('post.now_number'); //序号
        $url = U('Stock/Searchguds','','',false,true);
        $res = json_decode(curl_request($url,['GSKU'=>$sku]),true);
        if($res['status'] == 0) {
            echo 1;
            exit;
        }
        /*
        $guds_opt = M('guds_opt','tb_ms_'); //实例化商品属性表
        $guds = M('guds','tb_ms_'); //实例化商品表
        $guds_img = M('guds_img','tb_ms_'); //实例化商品图片表
        $sku_info = $guds_opt->where("GUDS_OPT_ID='$sku'")->find();
        if($sku_info==''){
            echo 1;   //如果sku查询为空，就返回1，并且程序停止执行
            exit; }
        $goods_id = $sku_info['GUDS_ID'];
        $attr_code = explode(';',$sku_info['GUDS_OPT_VAL_MPNG']);//商品选择价格图示

        foreach($attr_code as $key=>$v){
            //$attr01[] = explode(':',$v);
            $val_str = '';
            $o = explode(':', $v);
            $model = M('ms_opt', 'tb_');
            $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
            if (empty($opt_val_str)) {
                $val_str = L('标配');
                $attr[$key] = $val_str;
            } elseif ($opt_val_str['OPT_ID'] == '8000') {
                $val_str = L('标配');
                $attr[$key] = $val_str;
            } elseif ($opt_val_str['OPT_ID'] != '8000') {
                $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                $attr[$key] = $val_str;

            }

        }
        $attr = implode(' ',$attr);
        $goods_info = $guds->where("GUDS_ID='$goods_id'")->find();
        $img_info = $guds_img->where("GUDS_ID='$goods_id'")->find();
        $guds_img = $img_info['GUDS_IMG_CDN_ADDR'];
        $goods_name =  $goods_info['GUDS_CNS_NM'];
        */
        $guds['goods_name'] = $res['info'][0]['Guds']['GUDS_NM'];
        $guds['sku']        = $res['info'][0]['GUDS_OPT_ID'];
        $guds['search']     = $sku;
        $guds['guds_img']   = $res['info'][0]['Img'];
        $guds['val_str']    = $res['info']['opt_val'][0]['val'];
        $guds['now_number'] = $now_number;
        $standing           = M('center_stock','tb_wms_')
            ->field('sum(sale) as sale_num,sum(on_way) on_way_num')
            ->where(['SKU_ID'=>$sku])
            ->group('SKU_ID')
            ->find();
        $guds['sale_num']   = $standing['sale_num'];
        $guds['on_way_num'] = $standing['on_way_num'];
        $this->guds         = [$guds];
        $cmn_cd             = M('cmn_cd','tb_ms_');
        $this->currency     = $cmn_cd->where("CD_NM='기준환율종류코드'")->select(); //币种查询
        $this->display();


    }


    /*  //提交的时候对sku验证
      public function commitSku(){
          $sku = I('post.sku');
          $sku = explode(',',$sku);
          $sku_len = count($sku);
          $guds_opt = M('guds_opt','tb_ms_'); //实例化商品属性表


          for($i=0;$i<$sku_len;$i++){
              $sku_info = $guds_opt->where("GUDS_OPT_ID='$sku[$i]'")->find();
              if($sku_info==''){
                  echo 1;   //如果sku查询为空，就返回1，并且程序停止执行
                  exit;
              }

          }


      }*/



    /**
     * 获取查询条件
     * @param $params
     * @return mixed
     */
    public function getConditions($params)
    {
        if(!empty($params['sku_or_upc'])) {
            $map_s['pa.upc_id'] = $params['sku_or_upc'];
            $map_s['pa.sku_id'] = $params['sku_or_upc'];
            $map_s['_string'] = "FIND_IN_SET('{$params['sku_or_upc']}',pa.upc_more)";
            $map_s['_logic']    = 'or';
            $conditions[0] = $map_s;
        }
        if(isset($params['order_status'])) {
            $conditions['order_status'] = $params['order_status'] != '' ?  $params['order_status'] : ['in',TbPurRelevanceOrderModel::$order_status];
        }else {
            $conditions['order_status'] = TbPurRelevanceOrderModel::$order_status['not_cancelled'];
        }
        if(isset($params['ship_status']) && $params['ship_status'] !== ''){
            if($params['ship_status'] && false !== strstr($params['ship_status'],',')){
                $conditions['ship_status'] = ['in',WhereModel::stringToInArray($params['ship_status'])];
            }else{
                 $conditions['ship_status'] = $params['ship_status'];
            }
        }
        (isset($params['ship_time']) && $params['ship_time'] !== '') ? $conditions['tb_sell_quotation.ship_time'] = $params['ship_time'] : '';
        (isset($params['warehouse_status']) && $params['warehouse_status'] !== '') ? $conditions['warehouse_status'] = $params['warehouse_status'] : '';
        (isset($params['invoice_status']) && $params['invoice_status'] !== '') ? $conditions['invoice_status'] = $params['invoice_status'] : '';
        if($params['number']) {
            //$params['number_type'] == 1 ? $conditions['a.procurement_number'] = ['like','%'.$params['number'].'%'] : $conditions['a.online_purchase_order_number'] = ['like','%'.$params['number'].'%'];
            $condition['a.procurement_number']           = $params['number'];
            $condition['a.online_purchase_order_number'] = $params['number'];
            $condition['_logic']    = 'or';
            $conditions['_complex'] = $condition;
        }
        (isset($params['payment_status']) && $params['payment_status'] !== '') ? $conditions['payment_status'] = $params['payment_status'] : '';
        (isset($params['has_refund']) && $params['has_refund'] !== '') ? $conditions['has_refund'] = $params['has_refund'] : '';
        (isset($params['has_return_goods']) && $params['has_return_goods'] !== '') ? $conditions['has_return_goods'] = $params['has_return_goods'] : '';
        !empty($params['procurement_number']) ? $conditions['a.procurement_number'] = array('like','%'.$params['procurement_number'].'%') : '';
        !empty($params['sell_number']) ? $conditions['b.sell_number'] = array('like','%'.$params['sell_number'].'%') : '';
        !empty($params['supplier_id']) ? $conditions['supplier_id'] = ['like','%'.htmlspecialchars_decode($params['supplier_id']).'%'] : '';
        if($params['supp_id']) {
            $map['supp_id']         = ['like','%'.$params['supp_id'].'%'];
            $map['cus_res_name_en'] = ['like','%'.$params['supp_id'].'%'];
            $map['_logic']          = 'or';
            $conditions[1] = $map;
        }
        !empty($params['business_direction']) ? $conditions['a.business_direction'] = $params['business_direction'] : '';
        !empty($params['business_type']) ? $conditions['a.business_type'] = $params['business_type'] : '';
        !empty($params['prepared_by']) ? $conditions['t.prepared_by'] = array('like', '%' . $params['prepared_by'] . '%') : '';
        !empty($params['payment_company']) ? $conditions['payment_company'] = $params['payment_company'] : '';
        !empty($params['goods_name']) ? $conditions['pb.spu_name'] =array('like','%'. $params['goods_name'].'%') : '';
        switch ($params['time_type']) {
            case 0 :
                break;
            case 1:
                break;
            case 2 :
                break;
            case 3 :
                break;
        }
        !empty($params['start_time']) ? $conditions['sou_time'] = array('EGT',$params['start_time']) : '';
        !empty($params['end_time']) ? $conditions['t.sou_time'] = array('ELT',$params['end_time']) : '';

        if(!empty($params['b2b_no'])) {
            if($params['b2b_no_type'] == 1) {
                $b2b_no = "'". $params['b2b_no']. "'";
            }else {
                $b2b_no = M('b2b_info','tb_')->where(['THR_PO_ID'=>$params['b2b_no']])->getField('PO_id',true);
                $b2b_no = "'". join("','", $b2b_no). "'";
            }
            $conditions['_string'] = "f.ORD_ID in ({$b2b_no}) and ((f.use_type=1 and g.shipping_status<3) or (f.use_type=2))";
        }
        return $conditions;
    }

    /**
     * 订单列表的信息展示
     */
    public function order_list(){
        import('ORG.Util.Page');
        $cmn_cd             = M('cmn_cd','tb_ms_'); // 实例化数据字典表
        $relevance_order    = M('relevance_order','tb_pur_'); //实例化订单关联总表
        $params             = I('get.');
        $where=$this->getConditions($params);
        $purchase_sql = $relevance_order
            ->alias('t')
            ->field('t.relevance_id')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->join("left join tb_pur_sell_information b on b.sell_id = t.sell_id ")
            ->join("left join tb_pur_goods_information c on c.relevance_id = t.relevance_id")
            ->join("left join tb_sell_quotation on tb_sell_quotation.quotation_code = a.procurement_number")
            ->join('left join '.PMS_DATABASE.'.product_sku pa on c.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->group('t.relevance_id')
            ->where($where);
        if(!empty($params['b2b_no'])) {
            $purchase_sql->join('tb_wms_batch e on e.purchase_order_no=a.procurement_number')
                ->join('tb_wms_batch_order f on f.batch_id=e.id')
                ->join('tb_b2b_doship g on g.PO_ID=f.ORD_ID');
        }
        $purchase_sql = $purchase_sql->buildSql();

        $model = new SlaveModel();

        $counts = $model->table($purchase_sql.' a')->count();
        $Page = new Page($counts, 20); //每页显示3条数据
        $show = $Page->show(); //分页显示

        $purchase_info = $model->table('tb_pur_relevance_order t')
            ->field('
                t.relevance_id,t.prepared_by,t.prepared_time,order_status,ship_status,warehouse_status,payment_status,invoice_status,
                a.procurement_number,a.business_type,a.supplier_id,a.amount_currency,a.amount,a.online_purchase_order_number,
                b.supp_id,
                d.total_profit_margin,
                substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name
            ')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->join("left join tb_pur_sell_information b on b.sell_id = t.sell_id ")
            ->join("left join tb_pur_goods_information c on c.relevance_id = t.relevance_id")
            ->join("left join tb_pur_predict_profit d on d.predict_id = t.predict_id ")
            ->join("left join tb_sell_quotation on tb_sell_quotation.quotation_code = a.procurement_number")
            ->join('left join '.PMS_DATABASE.'.product_sku pa on c.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->group('t.relevance_id')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order("prepared_time desc");
        if(!empty($params['b2b_no'])) {
            $purchase_info->join('tb_wms_batch e on e.purchase_order_no=a.procurement_number')
                ->join('tb_wms_batch_order f on f.batch_id=e.id')
                ->join('tb_b2b_doship g on g.PO_ID=f.ORD_ID');
        }
        $purchase_info = $purchase_info->select();

        $business_types = $cmn_cd->where("CD_NM='业务类型'")->select();    //查出业务类型信息
        $purchase_team = $cmn_cd->where("CD_NM='采购团队'")->select();    //查出业务类型信息
        $order_status_cd = (new TbMsCmnCdModel())->getPurchaseOrderStatuskey();
        $this->assign('params',$params);
        $this->assign('show', $show);
        $this->assign('count', $counts);
        $this->assign('firstRow',$Page->firstRow);
        $this->assign('purchase_info',$purchase_info);
        $this->assign('business_types',$business_types);
        $this->assign('purchase_team',$purchase_team);
        $this->assign('order_status_cd',$order_status_cd);
        $this->assign('ship_status_arr',TbPurRelevanceOrderModel::$ship_status);
        $this->assign('warehouse_status_arr',TbPurRelevanceOrderModel::$warehouse_status);
        $this->assign('payment_status_arr',TbPurRelevanceOrderModel::$payment_status);
        $this->assign('invoice_status_arr',TbPurRelevanceOrderModel::$invoice_status);
        $this->display();

    }

    public function order_export_new() {
        $relevance_l    = D('Purchase/ExportOrder','Logic');
        $params         = I('request.');
        $where          = $this->getConditions($params);
        $condition      = $this->getConditions($where);
        $relevance_l->exportOrder($condition);
    }

    /**
     * 订单导出
     */
    public function order_export(){
        set_time_limit(0);

        $fileName = '采购订单'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        import('ORG.Util.Page');
        $relevance_order    = M('relevance_order','tb_pur_'); //实例化订单关联总表
        $params             = I('request.');
        $where              = $this->getConditions($params);
        $purchase_sql       = $relevance_order
            ->alias('t')
            ->field('t.relevance_id')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->join("left join tb_pur_sell_information b on b.sell_id = t.sell_id ")
            ->join("left join tb_pur_goods_information c on c.relevance_id = t.relevance_id")
            ->join('tb_wms_batch e on e.purchase_order_no=a.procurement_number')
            ->join('tb_wms_batch_order f on f.batch_id=e.id')
            ->join('tb_b2b_doship g on g.PO_ID=f.ORD_ID')
            ->join("left join tb_sell_quotation on tb_sell_quotation.quotation_code = a.procurement_number")
            ->join('left join '.PMS_DATABASE.'.product_sku pa on c.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->group('t.relevance_id')
            ->where($where)
            ->buildSql();

        $count = M()->table($purchase_sql.' a')->count();
        if($count > 10000) {
            $this->error('当前搜索结果超出1万条，系统1次最多导出1万条，请将搜索结果控制在1万条再导出！');
        }

        $purchase_info = $relevance_order
            ->alias('t')
            ->field('
                t.relevance_id,t.prepared_by,t.prepared_time,order_status,ship_status,warehouse_status,payment_status,invoice_status,
                a.procurement_number,a.business_type,a.supplier_id,a.amount_currency,a.amount,a.purchase_type,a.payment_company,
                b.supp_id,b.sell_team,b.sell_number,sell_money,curr,seller,
                d.total_profit_margin,d.cash_efficiency,
                substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name
            ')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id ")
            ->join("left join tb_pur_sell_information b on b.sell_id = t.sell_id ")
            ->join("left join tb_pur_goods_information c on c.relevance_id = t.relevance_id")
            ->join("left join tb_pur_predict_profit d on d.predict_id = t.predict_id ")
            ->join('tb_wms_batch e on e.purchase_order_no=a.procurement_number')
            ->join('tb_wms_batch_order f on f.batch_id=e.id')
            ->join('tb_b2b_doship g on g.PO_ID=f.ORD_ID')
            ->join("left join tb_sell_quotation on tb_sell_quotation.quotation_code = a.procurement_number")
            ->join('left join '.PMS_DATABASE.'.product_sku pa on c.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->group('t.relevance_id')
            ->where($where)
            ->select();

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = [
            'procurement_number'=>'PO/采购单号',
            'supplier_id'=>'供应商',
            'c.amount_currency'=>'币种',
            'amount'=>'采购金额（含税）',
//            'total_profit_margin'=>'利润率（退税后）',
            'prepared_by'=>'采购员',
            'prepared_time'=>'创建时间',
            'v.payment_status'=>'付款状态',
            'v.ship_status'=>'发货状态',
            'v.invoice_status'=>'开票状态',
            'v.warehouse_status'=>'入库状态',
            'c.order_status'=>'订单状态',
            'c.purchase_type'=>'采购类型',
            'c.payment_company'=>'采购团队',
//            'sell_number'=>'销售PO',
//            'c.curr'=>'销售币种',
//            'sell_money'=>'销售金额',
//            'supp_id'=>'客户名称',
            'seller'=>'销售同事',
            'c.sell_team'=>'销售团队',
//            'cash_efficiency'=>'CE评级（CE值）'
        ];

        $ship_status = [
            0 => '待发货',
            1 => '部分发货',
            2 => '发货完成',
        ];

        $warehouse_status = [
            0 => '待入库',
            1 => '部分入库',
            2 => '入库完成',
        ];

        $invoice_status = [
            0 => '待开票',
            1 => '部分开票',
            2 => '开票完成',
        ];

        $payment_status = [
            0 => '待付款',
            1 => '部分付款',
            2 => '付款完成',
        ];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = $v;
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($purchase_info as $k => $v) {
            foreach ($head as $key => $value) {
                unset($column);
                $key_e = explode('.',$key);
                if(count($key_e) == 2) {
                    if($key_e[0] == 'c') {
                        $column = cdVal($v[$key_e[1]]);
                    }else {
                        $arr = $$key_e[1];
                        $column = $arr[$v[$key_e[1]]];
                    }
                }else {
                    if($key == 'cash_efficiency') {
                        if($v[$key] >= 3000) {
                            $ce_level  = 'S';
                        }elseif($v[$key] >= 2000) {
                            $ce_level  = 'A';
                        }elseif($v[$key] >= 1000) {
                            $ce_level  = 'B';
                        }elseif($v[$key] >= 800) {
                            $ce_level  = 'C';
                        }elseif($v[$key] >= 500) {
                            $ce_level  = 'D';
                        }elseif($v[$key] <= 0) {
                            $ce_level  = 'S';
                        }else {
                            $ce_level  = 'F';
                        }
                        $column = $ce_level.'('.($v[$key]==0?'提前付款':$v[$key]).')';
                    }else {
                        $column = $v[$key];
                    }
                }
                $row[$key] = $column . ($key == 'procurement_number' ? "\t" : '');
            }
            fputcsv($fp, $row);
        }
    }


    /**
     * 编辑采购订单
     * @param bool $is_review
     */
    public function order_update($is_review = false){
        $order_detail = M('order_detail','tb_pur_'); //实例化采购信息表
        $sell_information = M('sell_information','tb_pur_'); //实例化销售信息表
        $drawback_information = M('drawback_information','tb_pur_'); //实例化退税信息表
        $goods_information = M('goods_information','tb_pur_'); //实例化商品信息表
        $predict_profit = M('predict_profit','tb_pur_');//实例化预计利润表
        $relevance_order = D('TbPurRelevanceOrder'); //实例化订单关联总表
        if($this->isPost()){
            $purchase_info = $this->getParams();
            //$add_data = $_REQUEST;
            if($purchase_info['contract_number']) {
                $purchase_info['has_contract'] = 1;
            }else {
                $purchase_info['has_contract'] = 0;
            }
            if($purchase_info['payment_days'] === '') $purchase_info['payment_days'] = null;
            $relevance_id = I('post.relevance_id');
            $purchase_info['money_total_rmb'] = I('post.money_total_rmb');
            $purchase_info['create_time'] = date("Y-m-d H:i:s",time());
            $relevance_info = $relevance_order->where("relevance_id='$relevance_id'")->find();
            $order_id = $relevance_info['order_id'];
            if($purchase_info['procurement_number']) {
                $res = $order_detail->where(['procurement_number'=>$purchase_info['procurement_number']])->find();
                if($res && $res['order_id'] != $order_id) {
                    $this->error(L('PO单号或采购单号').$purchase_info['procurement_number'].L('已经创建过订单，请勿重复创建'),U('orderDetail/order_list',2));
                }
            }else {
                $this->error(L('请填写PO单号或采购单号'));
            }
            if ($_FILES['attachment']['name'] || $_FILES['approve_credential']['name']) {
                // 图片上传
                $fd = new FileUploadModel();
                $ret = $fd->uploadFileArr();
                if($ret){
                    foreach($ret as $v) {
                        if($v['key'] == 'attachment') {
                            $purchase_info['attachment'][] = $v['savename'];
                        }else {
                            $purchase_info['approve_credential'] = $v['savename'];
                        }
                    }
                    $purchase_info['attachment'] = implode(',',$purchase_info['attachment']);
                }else {
                    $this->error(L("修改失败：上传文件失败").$fd->error,U('OrderDetail/order_list'),2);
                }
            }else {
                $purchase_info['attachment'] = implode(',',$purchase_info['attachment']);
            }
            if($purchase_info['payment_info']) {
                $purchase_info['payment_info'] = json_encode($purchase_info['payment_info']);
            }else {
                $purchase_info['payment_info'] = '';
            }
            $purchase_info['supplier_invoice_title'] = $purchase_info['supplier_id'];
            $sell_id = $relevance_info['sell_id'];
            $drawback_id = $relevance_info['drawback_id'];
            $predict_id = $relevance_info['predict_id'];
            $relevance_id = $relevance_info['relevance_id'];
            $payable_id = $relevance_info['payable_id'];
            $prepared_by = I('post.prepared_by');
            $creation_time = date("Y-m-d H:i:s",time());
            $creation_times = date("Y-m-d H:i:s",time()); //该时间是向采购应付表中插入使用的，是为了方便搜索使用
            $purchase_info['creation_times'] =  $creation_times;
            $prepared_time =  I('post.prepared_time'); //订单时间
            $number_total = I('post.number_total');//接收商品数量的合计
            $money_total = I('post.money_total');//接收商品金额的合计
            $money_total_rmb = I('post.money_total_rmb');//接收合计的外币金额换算成人民币的金额
            $show_total_rate = I('post.show_total_rate'); //用于保存 1USD = 6.8933RMB格式的汇率到数据库，然后在修改的时候显示
            $real_total_rate = I('post.real_total_rate'); //用于保存当天的真实汇率
            $curType = I("post.curType");  //接收商品的币种
            $relevance_info = compact("prepared_by","prepared_time","number_total","money_total","money_total_rmb","show_total_rate","real_total_rate","curType","creation_time"); //制单人和制单时间需要修改的信息
            $relevance_info['last_update_time'] = date('Y-m-d H:i:s');
            $relevance_info['last_update_user'] = $_SESSION['m_loginname'];
            $order_info = $order_detail->where("order_id='$order_id'")->save($purchase_info); //采购订单信息
            $sell_info = $sell_information->where("sell_id='$sell_id'")->save($purchase_info); //销售信息
            $drawback_info = $drawback_information->where("drawback_id='$drawback_id'")->save($purchase_info);  //退税信息
            $predict_info = $predict_profit->where("predict_id='$predict_id'")->save($purchase_info); //预计利润信息
            $relevance_info = $relevance_order->where("relevance_id='$relevance_id'")->save($relevance_info); //制单人和制单时间的修改
            //商品信息的修改
            $info_id            = I("post.information_id");
            $sku_information    = I("post.sku_information");
            $search_information = I("post.search_information");
            $invoice_name       = $goods_name = I("post.goods_name");
            $goods_attribute    = I("post.goods_attribute");
            $unit_price         = I("post.unit_price");
            $goods_number       = I("post.goods_number");
            $goods_money        = I("post.goods_money");
            $hotness            = I("post.hotness");
            $relevance_info     = $relevance_order->order("prepared_time desc")->find();///查出最新一条数据

            $relevance_id_log   = $relevance_id;
            unset($relevance_id);
            unset($payable_id);
            for($i=0;$i<count($search_information);$i++){
                $relevance_id[] = I('post.relevance_id');//形成一一对应关系所以进行循环
            }

            $data =  compact("info_id","search_information","sku_information","goods_name","invoice_name","goods_attribute","unit_price","goods_number","goods_money","hotness","relevance_id"
            );

            foreach($data as $key=>$v){

                foreach($v as  $key1=>$v1 ){
                    $goods[$key1][$key]=$v1;
                }
            }


            foreach($goods as $key=>$v){
                $info_id = $v['info_id'];
                if($info_id==''){
                    unset($info_id);
                    $v['create_time'] = date("Y-m-d H:i:s",time());
                    $goods_info[] = $goods_information->add($v);
                }else{
                    $goods_count = count($v);
                    if($goods_count==1){
                        $goods_info[] = $goods_information->where("information_id='$info_id'")->delete();
                    }else{
                        $goods_info[] = $goods_information->where("information_id=$info_id")->save($v);
                    }

                }

            }



            if($order_info>=0&&$sell_info>=0&&$drawback_info>=0&&$predict_info>=0&&$relevance_info>=0&&$goods_info>=0){
                (new TbPurActionLogModel())->addLog($relevance_id_log,'order_update');
                if($is_review) {
                    return true;
                }else {
                    $this->success(L("修改成功"),U('OrderDetail/order_list'),1);
                }
            }else{
                if($is_review){
                    return false;
                }else {
                    $this->error(L("修改失败"),U('OrderDetail/order_list'),1);
                }
            }

        }else{
            $relevance_id       = I('get.id'); //接收订单的ID
            $relevance_info     = $relevance_order->where("relevance_id='$relevance_id'")->find();
            $order_id           = $relevance_info['order_id'];
            $sell_id            = $relevance_info['sell_id'];
            $drawback_id        = $relevance_info['drawback_id'];
            $predict_id         = $relevance_info['predict_id'];
            $relevance_id       = $relevance_info['relevance_id'];
            $order_info         = $order_detail->where("order_id='$order_id'")->find(); //采购订单信息
            $order_info['payment_info'] = json_decode($order_info['payment_info'],true);
            $sell_info          = $sell_information->where("sell_id='$sell_id'")->find(); //销售信息
            $drawback_info      = $drawback_information->where("drawback_id='$drawback_id'")->find();  //退税信息
            $predict_info       = $predict_profit->where("predict_id='$predict_id'")->find(); //预计利润信息
            $profit_margin      = ($predict_info['profit_margin']*100)."%";
            $total_profit_margin = ($predict_info['total_profit_margin']*100)."%";
            $retained_profits   = ($predict_info['retained_profits']*100)."%";
            $goods_info         = $goods_information
                ->field('t.*,sum(a.sale) as sale_num,sum(a.on_way) on_way_num')
                ->alias('t')
                ->join('left join tb_wms_center_stock a on a.SKU_ID=t.sku_information')
                ->group('t.information_id')
                ->where(['relevance_id'=>$relevance_id,'sku_information'=>['exp','is not null']])
                ->select();
            $contracts          = M('contract','tb_crm_')->field('SP_BANK_CD,BANK_ACCOUNT,SWIFT_CODE,CON_NO,CON_NAME,SP_CHARTER_NO')->where(['SP_CHARTER_NO'=>$order_info['sp_charter_no']])->order('create_time desc')->select();
            $cmn_cd             = new TbMsCmnCdModel();

            $this->approve      = M('approve','tb_pur_')->where(['relevance_id'=>$relevance_id,'approve_status'=>['neq','N001320200']])->order('approve_time desc')->find(); //币种查询
            if($order_info['sp_charter_no']) {
                $this->risk_rating  = M('sp_supplier','tb_crm_')->where(['SP_CHARTER_NO'=>$order_info['sp_charter_no'],'DATA_MARKING'=>0])->getField('RISK_RATING');
                $this->has_cooperate = (new TbPurOrderDetailModel())->supplierHasCooperate($order_info['sp_charter_no']);
            }
            $this->assign('currency_relation',$this->currency_relation);
            $this->assign('order_info',$order_info);
            $this->assign('sell_info',$sell_info);
            $this->assign('drawback_info',$drawback_info);
            $this->assign('predict_info',$predict_info);
            $this->assign('goods_info',$goods_info);
            $this->assign('relevance_info',$relevance_info);
            $this->assign('relevance_id',$relevance_id);
            $this->assign('profit_margin',$profit_margin);
            $this->assign('total_profit_margin',$total_profit_margin);
            $this->assign('retained_profits',$retained_profits);
            $this->assign('contracts',$contracts);
            $this->payment_type         = TbPurOrderDetailModel::$payment_type;
            $this->payment_period       = TbPurOrderDetailModel::$payment_period;
            $this->payment_day_type     = TbPurOrderDetailModel::$payment_day_type;
            $this->cmn_cd_info          = $cmn_cd->getCdY($cmn_cd::$purchase_team_cd_pre);
            $this->business_direction   = $cmn_cd->getCdY($cmn_cd::$business_direction_cd_pre);
            $this->business_type        = $cmn_cd->getCdY($cmn_cd::$business_type_cd_pre);
            $this->delivery_type        = $cmn_cd->getCdY($cmn_cd::$delivery_type_cd_pre);
            $this->tax_rate             = $cmn_cd->getCdY($cmn_cd::$tax_rate_cd_pre);
            $this->sell_team            = $cmn_cd->getCdY($cmn_cd::$sell_team_cd_pre);
            $this->sell_mode            = $cmn_cd->getCdY($cmn_cd::$sell_mode_cd_pre);
            $this->invoice_type         = $cmn_cd->getCdY($cmn_cd::$invoice_type_cd_pre);
            $this->our_company          = $cmn_cd->getCdY($cmn_cd::$our_company_cd_pre);
            $this->currency             = $cmn_cd->getCdY($cmn_cd::$currency_cd_pre);
            $this->payment_node         = $cmn_cd->getCdY($cmn_cd::$payment_node_cd_pre);
            $this->payment_days         = $cmn_cd->getCdY($cmn_cd::$payment_days_cd_pre);
            $this->payment_percent      = $cmn_cd->getCdY($cmn_cd::$payment_percent_cd_pre);
            $this->currency_rates       = (new TbMsXchrModel())->currency_rates();
            $this->users                = B2bModel::get_user();
            $this->country              = (new TbCrmSiteModel())->getChildrenAddress(0);
            if(I('request.is_edit')) {
                $this->assign('is_edit',1);
            }else {
                $this->assign('is_edit',0);
            }
            $this->display();

        }
    }

    /**
     * 订单详情
     */
    public function order_detail() {
        $order_detail                       = M('order_detail','tb_pur_'); //实例化采购信息表
        $sell_information                   = M('sell_information','tb_pur_'); //实例化销售信息表
        $drawback_information               = M('drawback_information','tb_pur_'); //实例化退税信息表
        $goods_information                  = M('goods_information','tb_pur_'); //实例化商品信息表
        $predict_profit                     = M('predict_profit','tb_pur_');//实例化预计利润表
        $relevance_order                    = M('relevance_order','tb_pur_'); //实例化订单关联总表
        $relevance_id                       = I('get.id'); //接收订单的ID
        $relevance_info                     = $relevance_order->where("relevance_id='$relevance_id'")->find();
        $order_id                           = $relevance_info['order_id'];
        $sell_id                            = $relevance_info['sell_id'];
        $drawback_id                        = $relevance_info['drawback_id'];
        $predict_id                         = $relevance_info['predict_id'];
        $relevance_id                       = $relevance_info['relevance_id'];
        $relevance_info['cancel_voucher']   = json_decode($relevance_info['cancel_voucher'],true);
        $order_info                         = $order_detail
            ->field('t.*,a.NAME,a.RES_NAME')
            ->alias('t')
            ->join('left join tb_crm_site a on a.ID=t.source_country')
            ->where(["order_id"=>$order_id])
            ->find(); //采购订单信息
        $order_info['payment_info'] = json_decode($order_info['payment_info'],true);
        if(substr($order_info['procurement_number'],0,2) == 'RN') {
            $order_info['attachment'] = json_decode($order_info['attachment'],true);
        }else {
            $attachment = [];
            foreach (explode(',',$order_info['attachment']) as $v) {
                $attachment[] = ['original_name'=>$v,'save_name'=>$v];
            }
            $order_info['attachment'] = $attachment;
        }
        $sell_info          = $sell_information->where("sell_id='$sell_id'")->find(); //销售信息
        $drawback_info      = $drawback_information->where("drawback_id='$drawback_id'")->find();  //退税信息
        $predict_info       = $predict_profit->where("predict_id='$predict_id'")->find(); //预计利润信息
        $profit_margin      = ($predict_info['profit_margin']*100)."%";
        $total_profit_margin = ($predict_info['total_profit_margin']*100)."%";
        $retained_profits   = ($predict_info['retained_profits']*100)."%";
        $goods_info         = $goods_information
            ->alias('t')
            ->field('t.*,a.upc_id')
            ->where(["relevance_id"=>$relevance_id])
            ->join('left join '.PMS_DATABASE.'.product_sku a on a.sku_id=t.sku_information')
            ->group('t.information_id')
            ->select(); //商品信息
        $goods_info = SkuModel::getInfo($goods_info,'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute']);
        if($order_info['sp_charter_no']) {
            $this->risk_rating  = M('sp_supplier','tb_crm_')->where(['SP_CHARTER_NO'=>$order_info['sp_charter_no'],'DATA_MARKING'=>0])->getField('RISK_RATING');
            $this->has_cooperate = (new TbPurOrderDetailModel())->supplierHasCooperate($order_info['sp_charter_no']);
        }
        //是否有操作应付
        $payment = M('payment','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        $has_payment = false;
        foreach ($payment as $v) {
            if($v['status'] > 0) {
                $has_payment = true;
                break;
            }
        }
        //是否有操作发票
        $payment = M('invoice','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        if($payment) {
            $has_invoice = true;
        }else {
            $has_invoice = false;
        }
        $this->assign('order_info',$order_info);
        $this->assign('sell_info',$sell_info);
        $this->assign('drawback_info',$drawback_info);
        $this->assign('predict_info',$predict_info);
        $this->assign('goods_info',$goods_info);
        $this->assign('relevance_info',$relevance_info);
        $this->assign('relevance_id',$relevance_id);
        $this->assign('profit_margin',$profit_margin);
        $this->assign('total_profit_margin',$total_profit_margin);
        $this->assign('retained_profits',$retained_profits);
        $cmn_cd                 = new TbMsCmnCdModel();
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->cmn_cd_info      = $cmn_cd->getCdY($cmn_cd::$purchase_team_cd_pre);
        $this->our_company      = $cmn_cd->getCdY($cmn_cd::$our_company_cd_pre);
        $this->delivery_type    = $cmn_cd->getCdY($cmn_cd::$delivery_type_cd_pre);
        $this->sell_team        = $cmn_cd->getCdY($cmn_cd::$sell_team_cd_pre);
        $this->sell_mode        = $cmn_cd->getCdY($cmn_cd::$sell_mode_cd_pre);
        $this->business_type    = $cmn_cd->getCdY($cmn_cd::$business_type_cd_pre);
        $this->has_payment      = $has_payment;
        $this->has_invoice      = $has_invoice;
        $this->imgs             = $imgs;
        $this->approve          = M('approve','tb_pur_')->where(['relevance_id'=>$relevance_id,'approve_status'=>['neq','N001320200'],'status'=>0])->order('approve_time desc')->find(); //币种查询
        $this->display();
    }

    /**
     * 编辑部分非关联信息
     */
    public function order_update_part() {
        $purchase_l = D('Purchase','Logic');
        if($purchase_l->updatePart($_POST)) {
            $this->success('保存成功');
        }else {
            $this->error('保存失败:'.$purchase_l->getError());
        }
    }

    /**
     * 重置为草稿
     */
    public function reset_to_draft() {
        $id         = I('request.relevance_id');
        $purchase_l = D('Purchase','Logic');
        if($purchase_l->resetToDraft($id)) {
            $this->success('重置成功');
        }else {
            $this->error('重置失败'.$purchase_l->getError());
        }
    }

    /**
     * 发货
     */
    public function ship() {
        if(IS_POST) {
            //清空异常信息
            $message = ValidatorModel::clearMessage();
            $add_data = $post = $_POST;
            unset($add_data['shipped_goods']);
            $shipped_goods = $post['shipped_goods'];
            $model = new Model();
            $model->startTrans();
            $relevance = $model->lock(true)->table('tb_pur_relevance_order')->where(['relevance_id'=>$post['relevance_id']])->find();
//            $warehouse = $model->table('tb_pur_order_detail')->where(['order_id'=>$relevance['order_id']])->getField('warehouse');
//            $add_data['warehouse'] = $warehouse;
//            if($warehouse == 'N000680800') {
//                $add_data['need_warehousing'] = 0;
//            }else {
//                $add_data['need_warehousing'] = 1;
//            }
            if(!$relevance || $relevance['order_status'] != 'N001320300' || $relevance['ship_status'] == 2) {
                $model->rollback();
                $this->ajaxReturn(0,L('需要发货的订单不存在或不符合发货条件'),0);
            }
            $shipped_number = $relevance['shipped_number']+$post['shipping_number'];
            $relevance_update['shipped_number'] = $shipped_number;
            if($post['remainder_total'] > 0) { // 还有部分商品未发货 
                $relevance_update['ship_status'] = 1;
            }else {
                $relevance_update['ship_status'] = 2; // 发货完成
            }
            //更新订单发货数
            $res_rel = D('TbPurRelevanceOrder')
                ->where(['relevance_id'=>$post['relevance_id']])
                ->save($relevance_update);
            if($res_rel === false) {
                $model->rollback();
                $this->ajaxReturn(0,L('更新订单发货数失败'),0);
            }
            $ship_m = new TbPurShipModel();

            //发货信息数据校验
            if($add_data['has_ship_info']) {
                if(!$add_data['bill_of_landing']) {
                    // $model->rollback();
                    // $this->ajaxReturn(0,L('提单号(或其他有效单据号)必须'),0);
                }
            }else {
                //没有提单号/物流单号时自动生成单号
                $pre_bill = $model->table('tb_pur_ship')->lock(true)->where(['bill_of_landing'=>['like','BL'.date('Ymd').'%']])->order('bill_of_landing desc')->getField('bill_of_landing');
                if($pre_bill) {
                    $num = substr($pre_bill,-3)+1;
                }else {
                    $num = 1;
                }
                $add_data['bill_of_landing'] = 'BL'.date('Ymd').substr(1000+$num,1);
            }
            if($add_data['has_ship_info'] && !$add_data['shipment_date']) {
                $model->rollback();
                $this->ajaxReturn(0,L('发货日期必须'),0);
            }
            if($add_data['shipment_date'] > date('Y-m-d')) {
                $model->rollback();
                $this->ajaxReturn(0,L('发货日期不能晚于今天'),0);
            }
            if($add_data['shipment_date'] > $add_data['arrival_date']) {
                $model->rollback();
                $this->ajaxReturn(0,L('到货日期不得早于发货日期'),0);
            }
            if(!$add_data['shipment_date']) $add_data['shipment_date'] = date('Y-m-d');
            if($add_data['extra_cost'] && !$add_data['extra_cost_currency']) {
                $model->rollback();
                $this->ajaxReturn(0,L('请选择币种'),0);
            }
            if(!$add_data['warehouse']) {
                $model->rollback();
                $this->ajaxReturn(0,L('请选择我方仓库'),0);
            }
            //上传文件处理
            //上传文件必须选择类型
            $files_name = $_FILES['credential']['name'];
            $files_type = $post['credential_type'];
            $n          = 0;
            $file       = [];
            foreach ($files_name as $k => $v) {
                if($v) $n++;
                if($v && !$post['credential_type'][$k]) {
                    $this->error(L('请选择上传的文件类型'));
                }
            }
            //有上传文件时才处理
            if($n > 0) {
                $fd     = new FileUploadModel();
                $res    = $fd->uploadFileArr();
                if(!$res){
                    $model->rollback();
                    $this->error(L("保存失败：上传文件失败").$fd->error,U('OrderDetail/order_list'),2);
                }
                foreach ($files_name as $k => $v) {
                    if($v) {
                        $file_info['type'] = $files_type[$k];
                        $file_info['name'] = array_shift($res)['savename'];
                        $file[] = $file_info;
                    }
                }
            }
            $add_data['credential'] = json_encode($file);
            //保存发货信息
            if($model->table('tb_pur_ship')->validate($ship_m->_validate)->auto($ship_m->_auto)->create($add_data)) {
                $ship_id = $model->add();
                if(!$ship_id) {
                    $model->rollback();
                    $this->ajaxReturn(0,L('保存发货信息失败'),0);
                }
            }else {
                $model->rollback();
                $this->ajaxReturn(0,$model->getError(),0);
            }

            //保存入库编号
            $res = $model->table('tb_pur_ship')->where(['create_time'=>['lt',date('Y-m-d')]])->order('id desc')->find();
            $d_value = str_pad($ship_id-$res['id'],3,'0',STR_PAD_LEFT );
            $warehouse_id = date('Ymd').$d_value;
            $res = $model->table('tb_pur_ship')->where(['id'=>$ship_id])->save(['warehouse_id'=>$warehouse_id]);
            if(!$res) {
                $model->rollback();
                $this->ajaxReturn(0,L('保存失败：生成入库编号失败'),0);
            }

            $getShippedGoodsInfo = []; // 用于创建应付记录时，获取对应的金额
            //单个商品更新发货数,计算商品总金额
            foreach ($shipped_goods as $v) {
                $arr = json_decode($v,true);
                $goods = $model
                    ->table('tb_pur_goods_information')
                    ->where(['information_id'=>$arr['information_id']])
                    ->find();
                if(!$goods) {
                    $model->rollback();
                    $this->ajaxReturn(0,L('采购订单中不存在该商品'),0);
                }

                $goods_add['ship_id']               = $ship_id;
                $goods_add['information_id']        = $arr['information_id'];
                $goods_add['ship_number']           = $arr['ship_number'];
                $goods_add['number_info_ship']      = json_encode($arr['number_info']);
                $goods_add                          = array_merge($goods_add);
                $res_ship_goods = $model->table('tb_pur_ship_goods')->add($goods_add);
                if(!$res_ship_goods) {
                    $model->rollback();
                    $this->ajaxReturn(0,L('创建发货商品失败'),0);
                }
                $res_goods = $model->table('tb_pur_goods_information')->where(['information_id'=>$arr['information_id']])->setInc('shipped_number',$arr['ship_number']);
                if($res_goods === false) {
                    $model->rollback();
                    $this->ajaxReturn(0,L('更新商品发货数失败'),0);
                }

                $getShippedGoodsInfo[] = ['information_id' => $arr['information_id'], 'ship_number' => $arr['ship_number']];

            }

            // 根据报价单id（仅限创建采购单时用）或采购单id获取条款信息，有信息记录，走新流程，没有，则走旧流程
            $clauseInfo = (new Model())->table('tb_pur_clause')->where(['purchase_id' => $post['relevance_id']])->getField('id'); 
            if (!$clauseInfo) {
                $payment_m = new TbPurPaymentModel();
                $res_payment = $payment_m->createPayableByShip($ship_id);
                if(!$res_payment) {
                    $model->rollback();
                    $this->ajaxReturn(0,'生成应付失败：'.$payment_m->getError(),0);
                }
            }

            $add_data['ship_code'] = $warehouse_id;
            if(D('Warehouse/WarehouseChild')->isInRule($add_data['warehouse'])) {
                $ship_l         = D('Purchase/Ship','Logic');
                $res_notice     = $ship_l->noticeThirdWarehouse($add_data,$shipped_goods);
                if(!$res_notice) {
                    $model->rollback();
                    $this->ajaxReturn(0,'通知第三方仓库失败：'.$ship_l->getError(),0);
                }
            }
            if(!$add_data['need_warehousing']) {
                $ship_m = D('Purchase','Logic');
                $res_warehouse = $ship_m->warehouseVirtual($ship_id);
                if(!$res_warehouse) {
                    $model->rollback();
                    $this->ajaxReturn(0,L('发货失败:'.$ship_m->error),0);
                }
            }
            D('Purchase/Ship','Logic')->shipNoticeEmail($add_data,$relevance['order_id']);
            (new TbPurActionLogModel())->addLog($post['relevance_id']);

            if ($clauseInfo) {
                // 生成应付记录
                $addDataInfo = [];
                $addDataInfo['detail'] = $getShippedGoodsInfo; // 本次发货数量 和采购价 用来获取付款规则公式的总金额
                $addDataInfo['ship_id'] = $ship_id;
                $addDataInfo['class'] = __CLASS__;
                $addDataInfo['function'] = __FUNCTION__;
                $pur_res =D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', 'N002870002', $post['relevance_id'], $warehouse_id);
                if (!$pur_res) {
                    $model->rollback();
                    $this->ajaxReturn(0,'生成应付失败',0);
                }
            }
            $model->commit();
            $this->ajaxReturn(0,L('发货成功'),1);
        }else {
            $order_detail = M('order_detail', 'tb_pur_'); //实例化采购信息表
            $goods_information = M('goods_information', 'tb_pur_'); //实例化商品信息表
            $relevance_order = M('relevance_order', 'tb_pur_'); //实例化订单关联总表
            $relevance_id = I('get.id'); //接收订单的ID
            $relevance_info = $relevance_order->where("relevance_id='$relevance_id'")->find();
            $order_id = $relevance_info['order_id'];
            $relevance_id = $relevance_info['relevance_id'];
            $order_info = $order_detail->where("order_id='$order_id'")->find(); //采购订单信息
            $goods_info = $goods_information
                ->field('tb_pur_goods_information.*,b.is_shelf_life,upc_id,a.upc_more,shipped_number,sum(c.return_number) return_number')
                ->where(["relevance_id" => $relevance_id])
                ->join('left join '.PMS_DATABASE.'.product_sku a on a.sku_id = tb_pur_goods_information.sku_information')
                ->join('left join '.PMS_DATABASE.'.product b on b.spu_id = a.spu_id')
                ->join('tb_pur_return_goods c on c.information_id=tb_pur_goods_information.information_id')
                ->group('tb_pur_goods_information.information_id')
                ->select(); //商品信息

            $relevance_info['shipped_number'] = array_sum(array_column($goods_info, 'shipped_number'))-array_sum(array_column($goods_info, 'return_number'));
            $goods_info             = SkuModel::getInfo($goods_info,'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute']);

            foreach ($goods_info as $k => &$v) {
                if($v['upc_more']) {
                    $upc_more_arr = explode(',', $v['upc_more']);
                    array_unshift($upc_more_arr, $v['upc_id']);
                    $v['upc_id'] = implode(",<br/>", $upc_more_arr); # 返回br标签 前端显示换行 
                }
            }
            $cmn_cd                 = TbMsCmnCdModel::getInstance();
            $this->cmn_cd_info      = $cmn_cd->getCdY($cmn_cd::$purchase_team_cd_pre);
            $this->currency         = $cmn_cd->getCdY($cmn_cd::$currency_cd_pre);
            $this->credential_types = $cmn_cd->getCdY($cmn_cd::$ship_credential_type_cd_pre); //币种查询
            $this->warehouse        = $cmn_cd->getCdY($cmn_cd::$warehouse_cd_pre);
            $occupy_orders          = M('batch','tb_wms_')
                ->field('b.demand_code,b.third_po_no')
                ->alias('t')
                ->join('tb_wms_batch_order a on a.batch_id=t.id')
                ->join('tb_sell_demand b on b.demand_code=a.ORD_ID')
                ->where(['t.purchase_order_no'=>$order_info['procurement_number'],'t.vir_type'=>'N002440200','a.occupy_num'=>['gt',0],'a.use_type'=>1])
                ->group('a.ORD_ID')
                ->select();
            $this->assign('order_info', $order_info);
            $this->assign('goods_info', $goods_info);
            $this->assign('relevance_info', $relevance_info);
            $this->assign('relevance_id', $relevance_id);
            $this->assign('occupy_orders', $occupy_orders);
            if (I('request.is_edit')) {
                $this->assign('is_edit', 1);
            } else {
                $this->assign('is_edit', 0);
            }
            $this->display();
        }
    }



    /**
     * 发货详情
     */
    public function ship_detail() {
        $order_detail       = M('order_detail','tb_pur_'); //实例化采购信息表
        $goods_information  = M('goods_information','tb_pur_'); //实例化商品信息表
        $relevance_order    = M('relevance_order','tb_pur_'); //实例化订单关联总表
        $relevance_id       = I('get.id'); //接收订单的ID
        $relevance_info     = $relevance_order->where("relevance_id='$relevance_id'")->find();
        $order_id           = $relevance_info['order_id'];
        $relevance_id       = $relevance_info['relevance_id'];
        $order_info         = $order_detail->where("order_id='$order_id'")->find(); //采购订单信息
        $goods_info         = $goods_information
            ->alias('t')
            ->field('t.*,a.upc_id')
            ->join('left join '.PMS_DATABASE.'.product_sku a on t.sku_information=a.sku_id')
            ->where(["relevance_id"=>$relevance_id])
            ->group('t.information_id')
            ->select(); //商品信息
        $goods_info = SkuModel::getInfo($goods_info,'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute']);
        foreach ($goods_info as $v) {
            $goods[$v['information_id']] = $v;
        }
        $ships       = M('ship','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        $count_ships = count($ships);
        foreach ($ships as $k => $v) {
            $ships[$k]['goods_number']  = 0;
            $ships[$k]['credential']    = json_decode($v['credential'],true);
            $ship_goods_arr             = M('ship_goods','tb_pur_')->where(['ship_id'=>$v['id']])->select();
            foreach ($ship_goods_arr as $value) {
                $ship_goods[$v['id']][$value['information_id']] = $value;
                $ships[$k]['goods_number']  += $goods[$value['information_id']]['goods_number'];
            }
        }
        $cmn_cd             = M('cmn_cd','tb_ms_');
        $currency           = $cmn_cd->where("CD_NM='기준환율종류코드'")->getField('CD,CD_VAL',true); //币种查询
        $warehouses         = $cmn_cd->where("CD_NM='DELIVERY_WAREHOUSE'")->getField('CD,CD_VAL',true); //币种查询
        $this->assign('currency_relation',$this->currency_relation);
        $this->assign('order_info',$order_info);
        $this->assign('goods',$goods);
        $this->assign('ships',$ships);
        $this->assign('count_ships',$count_ships);
        $this->assign('ship_goods',$ship_goods);
        $this->assign('relevance_info',$relevance_info);
        $this->assign('relevance_id',$relevance_id);
        $this->assign('currency',$currency);
        $this->assign('warehouses',$warehouses);
        $this->display();
    }



    /**
     * 删除订单
     */
    public function order_del(){
        $order_detail = M('order_detail','tb_pur_'); //实例化采购信息表
        $sell_information = M('sell_information','tb_pur_'); //实例化销售信息表
        $drawback_information = M('drawback_information','tb_pur_'); //实例化退税信息表
        $goods_information = M('goods_information','tb_pur_'); //实例化商品信息表
        $predict_profit = M('predict_profit','tb_pur_');//实例化预计利润表
        $relevance_order = M('relevance_order','tb_pur_'); //实例化订单关联总表
        $payable = M('payable', 'tb_pur_'); //实例化采购应付表
        $relevance_id = I('get.relevance_id'); //接收需要删除的订单ID
        $relevance_id = explode(',',$relevance_id);
        for($i=0;$i<count($relevance_id);$i++){
            $relevance_info[] = $relevance_order->where("relevance_id='$relevance_id[$i]'")->find();
        }

        foreach($relevance_info as $key=>$v){
            $order_id = $v['order_id']; //订单ID
            $sell_id = $v['sell_id'];  //销售ID
            $drawback_id = $v['drawback_id']; //退税信息ID
            $predict_id = $v['predict_id']; //预计利润表信息ID
            $relevance_id = $v['relevance_id']; //关联总表的信息ID
            $payable_id = $v['payable_id']; //采购应付表的信息ID
            $order_del = $order_detail->where("order_id='$order_id'")->delete(); //删除采购订单信息
            $sell_del = $sell_information->where("sell_id='$sell_id'")->delete(); //删除销售订单信息
            $drawback_del = $drawback_information->where("drawback_id='$drawback_id'")->delete(); //删除退税订单信息
            $predict_del = $predict_profit->where("predict_id='$predict_id'")->delete(); //删除退税订单信息
            $goods_del = $goods_information->where(["relevance_id"=>$relevance_id])->delete(); //删除退税订单信息
            $goods_info = $goods_information->where("relevance_id='$relevance_id'")->select(); //查出需要删除的商品信息
            foreach($goods_info as $key1=>$v1){
                $information_id = $v1['information_id'];

            }

            $relevance_info =  $relevance_order->where("relevance_id='$relevance_id'")->delete(); //查出需要删除的商品信息

        }
        if($order_del&&$sell_del&&$drawback_del&&$predict_del&&$goods_del&&$relevance_info){
            echo 1;
        }else{
            echo 0;
        }



    }


    /**
     * 获取供应商查询条件
     * @param $params
     * @return mixed
     */
    public function getSupper($params,$type = 0){

        if(!empty($params['supplier_id'])) {
            $conditions['SP_NAME']          = array('like','%'. $params ['supplier_id'].'%');
            $conditions['SP_NAME_EN']       = array('like','%'. $params ['supplier_id'].'%');
            $conditions['SP_RES_NAME_EN']   = array('like','%'. $params ['supplier_id'].'%');
            $conditions['_logic']           = 'or';
        }
        $where['_complex'] = $conditions;
        $where['DATA_MARKING'] = $type;
        return  $where;
    }



    /**
     * 供应商查询
     */
    public function supplier_sou(){
        //供应商查询
        $params['supplier_id'] = $supplier_id = htmlspecialchars_decode(I('get.supplies_id'));
        $sp_supplier = M('sp_supplier','tb_crm_');
        $where=$this->getSupper($params);
        $supper_info = $sp_supplier->where($where)->select();
        $purchase_order_m = new TbPurOrderDetailModel();
        foreach ($supper_info as $k=>$v) {
            $has_cooperate = $purchase_order_m->supplierHasCooperate($v['SP_CHARTER_NO']);
            if($has_cooperate) {
                $supper_info[$k]['has_cooperate'] = 1;
            }else {
                $supper_info[$k]['has_cooperate'] = 0;
            }
        }
        $this->assign('supper_info',$supper_info);
        $this->display("supplier_sou_ajax");
    }

    /**
     * 客户查询
     */
    public function search_customer() {
        $params['supplier_id'] = $supplier_id = I('get.supplies_id');
        $sp_supplier = M('sp_supplier','tb_crm_');
        $where=$this->getSupper($params,1);
        $supper_info = $sp_supplier->where($where)->select();
        $this->assign('supper_info',$supper_info);
        $this->display();
    }

    /**
     * 发送审批邮件
     */
    public function sendForReview() {
        $relevance_id       = I('request.relevance_id');
        if(IS_POST) {
            if($relevance_id) {
                $res = $this->order_update(true);
            }else {
                $relevance_id = $res = $this->order_add(true);
            }
            if(!$res) {
                $this->ajaxReturn(0,L('保存信息失败,请重试'),0);
            }
        }
        $relevance          = M('relevance_order','tb_pur_')->where(['relevance_id'=>$relevance_id])->find();
        $predict_id         = $relevance['predict_id'];
        $order_id           = $relevance['order_id'];
        $this->relevance    = $relevance;
        $order        = M('order_detail','tb_pur_')->where(['order_id'=>$order_id])->find();
        $info         = M('predict_profit','tb_pur_')->where(['predict_id'=>$predict_id])->find();
        $drawback     = M('drawback_information','tb_pur_')->where(['drawback_id'=>$relevance['drawback_id']])->find();
        $sell         = M('sell_information','tb_pur_')
            ->field('t.*,a.cooperative_rating')
            ->alias('t')
            ->join('left join tb_crm_sp_supplier a on a.SP_CHARTER_NO=t.cus_charter_no')
            ->where(['sell_id'=>$relevance['sell_id']])
            ->find();
        $goods        = M('goods_information','tb_pur_')
            ->field('t.*,sum(a.sale) as sale_num,sum(a.on_way) on_way_num,b.GUDS_IMG_CDN_ADDR img_src')
            ->alias('t')
            ->join('left join tb_wms_center_stock a on a.SKU_ID=t.sku_information')
            ->join('left join (select * from tb_ms_guds_img group by GUDS_ID) b on b.GUDS_ID=left(t.sku_information,8)')
            ->group('t.information_id')
            ->where(['relevance_id'=>$relevance_id,'sku_information'=>['exp','is not null']])
            ->select();
        if(!$order['procurement_number']) {
            $this->ajaxReturn(0,L('请填写采购单号'),0);
        }
        if(!$order['payment_company']) {
            $this->ajaxReturn(0,L('请选采购团队'),0);
        }
        //普通采购才校验
        if($order['purchase_type'] == 'N001890100') {
            if(!$order['sp_charter_no']) {
                $this->ajaxReturn(0,L('请选择供应商'),0);
            }
            if(!$order['supplier_id_en']) {
                $this->ajaxReturn(0,L('缺失供应商（EN）信息,请补全后再提交'),0);
            }
            if(!$order['contract_number']) {
                $this->ajaxReturn(0,L('请选择采购合同'),0);
            }
            if(!$order['supplier_collection_account']) {
                $this->ajaxReturn(0,L('请填写收款账户名'),0);
            }
            if(!$order['supplier_opening_bank']) {
                $this->ajaxReturn(0,L('请填写供应商开户行'),0);
            }
            if(!$order['supplier_card_number']) {
                $this->ajaxReturn(0,L('请填写供应商银行账号'),0);
            }
            //附件
            $attachment = explode(',',$order['attachment']);
            $attachment_number = 0;
            foreach ($attachment as $v) {
                if($v) {
                    $attachment_number++;
                }
            }
            if(!$attachment_number) $this->ajaxReturn(0,L('至少上传一个附件'),0);
        }else {
            if(!$order['supplier_id']) {
                $this->ajaxReturn(0,L('请选择供应商'),0);
            }
        }

        if(!$order['amount']) {
            $this->ajaxReturn(0,L('请填写采购金额'),0);
        }
        if(!$order['amount_currency']) {
            $this->ajaxReturn(0,L('请选择币种'),0);
        }
        if(!$order['business_type']) {
            $this->ajaxReturn(0,L('请选择业务类型'),0);
        }
        if(!$order['delivery_type']) {
            $this->ajaxReturn(0,L('请选择交货方式'),0);
        }
        if($order['procurement_date'] == '0000-00-00') {
            $this->ajaxReturn(0,L('请填写预计采购日期'),0);
        }
        if($order['arrival_date'] == '0000-00-00') {
            $this->ajaxReturn(0,L('请填写预计到货日期'),0);
        }
        if(!$order['payment_info']) $this->ajaxReturn(0,L('请完善付款信息'),0);
        $payment_info = $order['payment_info'] = json_decode($order['payment_info'],true);
        $payment_percent_total = 0;
        foreach ($payment_info as $k => $v) {
            $payment_percent_total += $v['payment_percent'];
            if($order['payment_type'] == 0) {
                if(!$v['payment_date'] || !$v['payment_percent']) $this->error(L('请完善付款信息'));
                if($payment_info[$k-1] && $v['payment_date']<$payment_info[$k-1]['payment_date']) $this->error(L('付款时间中，账期时间排序有误'));
            }else {
                if(!$v['payment_node'] || !$v['payment_days'] || !$v['payment_percent']) $this->error(L('请完善付款信息'));
                if($payment_info[$k-1] && $v['payment_date_estimate']<$payment_info[$k-1]['payment_date_estimate']) $this->error(L('付款时间中，账期时间排序有误'));
            }
        }
        if($payment_percent_total != 100) $this->error(L('付款比例必须为100%'));
        if(!$order['invoice_type']) {
            $this->ajaxReturn(0,L('请选择发票类型'),0);
        }
        if(!$order['tax_rate']) {
            $this->ajaxReturn(0,L('请选择税点'),0);
        }
        if(!$order['source_country']) {
            $this->ajaxReturn(0,L('请选择货源国家'),0);
        }
        if(!$order['our_company']) {
            $this->ajaxReturn(0,L('请选择我方公司'),0);
        }

        if($sell['has_sell_number'] && !$sell['sell_number']) {
            $this->ajaxReturn(0,L('请填写销售PO'),0);
        }
        $b2b_arr = ['B2B Online Team1（商鞅）','B2B Offline','B2B Online Team2（卫青）','B2B Online West'];
        if(in_array($order['business_type'],$b2b_arr)) {
            if(!$sell['has_sell_contract'] && !$sell['approve_credential']) {
                $this->ajaxReturn(0,L('B2B业务如果没有销售合同必须上传CEO特批凭证'),0);
            }
        }
        if($sell['has_sell_contract'] && !$sell['sell_contract']) {
            $this->ajaxReturn(0,L('请填写销售合同编号'),0);
        }
        if($sell['has_sell_contract'] && !$sell['cus_charter_no']) {
            $this->ajaxReturn(0,L('请选择对应销售客户'),0);
        }
        if(!$sell['sell_team']) {
            $this->ajaxReturn(0,L('请选择销售团队'),0);
        }
        if(!$sell['seller']) {
            $this->ajaxReturn(0,L('请选择销售团队同事'),0);
        }
        if(!$sell['sell_mode']) {
            $this->ajaxReturn(0,L('请选择销售方式'),0);
        }
        if($sell['payment_days'] === null) {
            $this->ajaxReturn(0,L('收款账期数据为空，请补充以后提交'),0);
        }
        if(!$sell['curr']) {
            $this->ajaxReturn(0,L('请选择销售币种'),0);
        }
        if(!$sell['sell_money']) {
            $this->ajaxReturn(0,L('请天填写销售金额'),0);
        }
        $receivable = 0;
        if($sell['pre_receivable_percent']) {
            if($sell['pre_receivable_date'] == null || $sell['pre_receivable_date'] == '0000-00-00') $this->error(L('有收款比例的收款日期必填'));
            $receivable += $sell['pre_receivable_percent'];
            $receivable_date[] = $sell['pre_receivable_date'];
        }
        if($sell['mid_receivable_percent']) {
            if($sell['mid_receivable_date'] == null || $sell['mid_receivable_date'] == '0000-00-00') $this->error(L('有收款比例的收款日期必填'));
            $receivable += $sell['mid_receivable_percent'];
            $receivable_date[] = $sell['mid_receivable_date'];
        }
        if($sell['end_receivable_percent']) {
            if($sell['end_receivable_date'] == null || $sell['end_receivable_date'] == '0000-00-00') $this->error(L('有收款比例的收款日期必填'));
            $receivable += $sell['end_receivable_percent'];
            $receivable_date[] = $sell['end_receivable_date'];
        }
        if($receivable != 100) {
            $this->error(L('收款比例必须为100%'));
        }
        foreach ($receivable_date as $k => $v) {
            if($k>0 && $v<$receivable_date[$k-1]) {
                $this->error(L('付款时间中，账期时间排序有误'));
            }
        }
        if(number_format($order['amount'],2) != number_format($relevance['money_total']+$order['expense'],2)) {
            $this->ajaxReturn(0,L('采购金额必须等于商品金额与采购端物流费用之和'),0);
        }
        foreach ($goods as $v) {
            if(!$v['search_information']) {
                $this->ajaxReturn(0,L('sku/条形码不存在'),0);
            }
            if(!$v['unit_price']) {
                $this->ajaxReturn(0,$v['search_information'].L('商品单价必填'),0);
            }
            if(!$v['goods_number']){
                $this->ajaxReturn(0,$v['search_information'].L('商品数量必填'),0);
            }
            if(!$v['hotness'])
                $this->ajaxReturn(0,$v['search_information'].L('没有商品热度，请获取商品热度后，再提交采购审批'),0);

        }

        if($info['purchase_total_money'] <= 0) $this->error(L('预计利润部分的采购金额必须＞0'));
//        if($info['total_money'] <= 0) $this->error(L('预计利润部分的销售收入必须＞0'));
//        if($info['net_profit'] <= 0) $this->error(L('预计利润部分的净利润必须＞0'));
//        if($info['total_profit'] <= 0) $this->error(L('预计利润部分的总利润必须＞0'));

        //cash efficiency评级
        if($info['cash_efficiency'] >= 3000) {
            $info['cash_efficiency_level']  = 'S';
        }elseif($info['cash_efficiency'] >= 2000) {
            $info['cash_efficiency_level']  = 'A';
        }elseif($info['cash_efficiency'] >= 1000) {
            $info['cash_efficiency_level']  = 'B';
        }elseif($info['cash_efficiency'] >= 800) {
            $info['cash_efficiency_level']  = 'C';
        }elseif($info['cash_efficiency'] >= 500) {
            $info['cash_efficiency_level']  = 'D';
        }elseif($info['cash_efficiency'] <= 0) {
            $info['cash_efficiency_level']  = 'S';
        }else {
            $info['cash_efficiency_level']  = 'F';
        }

//        if($info['cash_efficiency_level'] == 'F') {
//            $this->error(L('CE值过低，请修正过后提交'));
//        }

        if(in_array($info['cash_efficiency_level'],['S','A','B']) && $order['purchase_type'] == 'N001890100') {
            $auto = true;
        }else {
            $auto = false;
        }

        if($order['purchase_type'] == 'N001890100') {
            $cc         = C('screening_cc_email_address');
            $address    = C('screening_email_address');
        }else {
            $cc         = C('screening_cc_email_address_1688Taobao');
            $address    = C('screening_email_address_1688Taobao');
        }
        $save               = [];
        $approve_user       = '';
        $secret                     = md5(uniqid().mt_rand(10000,99999)); //生成唯一秘钥
        $save['relevance_id']   = $relevance_id;
        $save['secret_key']     = $secret; //生成唯一秘钥
        $save['approve_status'] = 'N001320200'; //审核中
        if($auto) {
            $save['approve_user']   = 'System'; //截取邮箱账号作为审核人
        }else {
            foreach ($address as $v) {
                $approve_user .= explode('@',$v)[0].',';//截取邮箱账号作为审核人
            }
            $save['approve_user']   = rtrim($approve_user,',');
        }
        $save['create_time']    = date('Y-m-d H:i:s');
        $save['create_user']    = $_SESSION['m_loginname'];
        M()->startTrans();
        $res = M('approve','tb_pur_')->add($save);
        if($res === false) {
            M()->rollback();
            ELog::add('添加审批数据失败:'.M()->getDbError(),ELog::ERR);
            $this->ajaxReturn(0,L('发送审核邮件失败'),0);
        }
        $res = D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save(['order_status'=>'N001320200']);
        if($res === false) {
            M()->rollback();
            ELog::add('修改订单状态失败:'.M()->getDbError(),ELog::ERR);
            $this->ajaxReturn(0,L('发送审核邮件失败'),0);
        }

        $email_log['from']          = C('email_address');
        $email_log['email_type']    = 'N001310100';
        $email_log['create_user']   = $_SESSION['m_loginname'];
        $email_log['send_time']     = date('Y-m-d H:i:s');
        $email                      = new SMSEmail();
        $purchase_team_email        = M('cmn_cd','tb_ms_')->where(['CD'=>$order['payment_company']])->getField('ETC');
        $sell_team_email            = M('cmn_cd','tb_ms_')->where(['CD'=>$sell['sell_team']])->getField('ETC');

        if($purchase_team_email) {
            $purchase_team_email_arr = explode(',',$purchase_team_email);
            $cc = array_merge($cc,$purchase_team_email_arr);
        }
        if($sell_team_email) {
            $sell_team_email_arr = explode(',',$sell_team_email);
            $cc = array_merge($cc,$sell_team_email_arr);
        }
        $cc[]               = $relevance['prepared_by'].'@gshopper.com';
        $this->order        = $order;
        $this->info         = $info;
        $this->drawback     = $drawback;
        $this->sell         = $sell;
        $this->goods        = $goods;
        $this->auto         = $auto;
        if($auto) {
            $title = "B级以上采购订单审批通过提醒：（ID：{$order['procurement_number']}） - {$relevance['prepared_by']} - ".str_replace('-','/',substr($relevance['prepared_time'],0,10));
        }else {
            $title = "审批提醒：采购订单需要审批 - （ID：{$order['procurement_number']}） - {$relevance['prepared_by']} - ".str_replace('-','/',substr($relevance['prepared_time'],0,10));
        }
        $this->title            =  $title;
        $email_log['to']        = $address;
        $this->secret           = $secret;
        $message                = $this->fetch();
        $email_log['content']   = $message;
        $res                    = M('email','tb_')->add($email_log);
        if($res === false) {
            M()->rollback();
            ELog::add('添加邮件记录失败:'.M()->getDbError(),ELog::ERR);
            $this->ajaxReturn(0,L('发送审核邮件失败'),0);
        }
        $res                    = $email->sendEmail($address,$title,$message,$cc);
        if($res) {
            M()->commit();
            if($auto) $this->review($secret,1,'System');
            (new TbPurActionLogModel())->addLog($relevance_id);
            $this->ajaxReturn(['jump_url'=>U('order_detail',['id'=>$relevance_id])],L('申请邮件已发送，等待').rtrim($approve_user,',').L('审核中'),1);
        }else {
            M()->rollback();
            $this->ajaxReturn(0,L('提交审批失败：'.$email->getError()),0);
        }
    }

    /**
     * @param $secret
     * @param $status
     * @param $user
     */
    public function review($secret,$status,$user='') {
        if(!in_array($status,[0,1])) {
            $this->error(L('参数错误'),U('index/index'));
        }
        $approve_m  = M('approve','tb_pur_');
        $approve    = $approve_m->where(['secret_key'=>$secret])->find();
        $goods = M('goods_information','tb_pur_')->where(['relevance_id'=>$approve['relevance_id']])->select();
        $order = M('order_detail','tb_pur_')->alias('t')->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')->where(['relevance_id'=>$approve['relevance_id']])->find();

        if(!$approve) {
            $this->error(L('审批请求不存在'),U('index/index'));
        }
        //审批记录为审批通过或审批失败
        if($approve['approve_status'] == 'N001320300' || $approve['approve_status'] == 'N001320400') {
            $this->error(L('此次请求已经审批过'),U('index/index'));
        }
        if($approve['status'] == 1) {
            $this->error(L('此次审批申请已经失效'),U('index/index'));
        }

        $order_status = $order['order_status'];
        //采购订单状态为审批通过或审批失败
        if($order_status == 'N001320300'){
            $this->error(L('该采购订单已经审核通过'),U('index/index'));
        }
        $model = new Model();
        $model->startTrans();
        $save['approve_time'] = date('Y-m-d H:i:s');
        if($user) {
            $save['actual_approve_user'] = $user;
        }else {
            $save['actual_approve_user'] = $_SESSION['m_loginname'];
        }
        if($status == 1) {
            $msg = '审批通过';
            $save['approve_status']     = 'N001320300';
            $approve_res                = $model->table('tb_pur_approve')->where(['secret_key'=>$secret])->save($save);
            $relevance_res              = D('TbPurRelevanceOrder')->where(['relevance_id'=>$approve['relevance_id']])->save(['order_status'=>'N001320300']);
            $email_content  = "Dear {$order['prepared_by']}:<br />Your purchase order ID: {$order['procurement_number']} has been approved.<br />Please arrange the next steps. Thank you.";
            $email_title    = "Purchase orders from {$order['procurement_number']} is approved";
        }else {
            $msg                        = '审批不通过';
            $save['approve_status']     = 'N001320400';
            $approve_res                = $model->table('tb_pur_approve')->where(['secret_key'=>$secret])->save($save);
            $relevance_res              = D('TbPurRelevanceOrder')->where(['relevance_id'=>$approve['relevance_id']])->save(['order_status'=>'N001320400']);
            $email_content  = "Dear {$order['prepared_by']}:<br />Your purchase order ID: {$order['procurement_number']} has been failed.<br />Please arrange the next steps. Thank you.";
            $email_title    = "Purchase orders from {$order['procurement_number']} is failed";
        }
        if($approve_res && $relevance_res) {
            (new TbPurActionLogModel())->addLog($approve['relevance_id']);
            $model->commit();

            if($status == 1) {
                //生成应付
                $payment_m = new TbPurPaymentModel();
                if($order['payment_type'] == 0) {
                    if($order['payment_period']) {
                        $payment_info = json_decode($order['payment_info'],true);
                        foreach($payment_info as $k => $v) {
                            $payable['relevance_id']    = $order['relevance_id'];
                            $payable['payable_date']    = $v['payment_date'];
                            $payable['amount']          = ($order['amount']-$order['expense']);
                            $payable['amount_payable']  = $payable['amount']*$v['payment_percent']/100;
                            $payable['payment_period']  = "第{$k}期-{$v['payment_percent']}%";
                            $payable['payment_no']      = $payment_m->createPaymentNO();
                            $res                        = $payment_m->add($payable);
                            if(!$res) {
                                ELog::add('生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
                            }
                        }
                    }
                }else {
                    $payment_info = json_decode($order['payment_info'],true);
                    foreach ($payment_info as $v) {
                        if($v['payment_node'] == 'N001390100') {
                            $payable['relevance_id']   = $order['relevance_id'];
                            $payable['payable_date']   = date('Y-m-d',strtotime($order['procurement_date'])+$v['payment_days']*24*3600);
                            $payable['amount']         = ($order['amount']-$order['expense']);
                            $payable['amount_payable'] = $payable['amount']*$v['payment_percent']/100;
                            $payable['payment_period'] = "第1期-"
                                .cdVal($v['payment_node'])
                                .$v['payment_days']
                                .TbPurOrderDetailModel::$payment_day_type[$v['payment_day_type']]
                                .$v['payment_percent'].'%';
                            $payable['payment_no']     = $payment_m->createPaymentNO();
                            $res = $payment_m->add($payable);
                            if(!$res) {
                                ELog::add('生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
                            }
                            break;
                        }
                    }
                }
                //增加商品在途
                foreach ($goods as $v) {
                    $on_way['SKU_ID']       = $v['sku_information'];
                    $on_way['TYPE']         = 0;
                    $on_way['on_way']       = $v['goods_number'];
                    $on_way['on_way_money'] = round($v['goods_number']*$v['unit_price']*$order['amount_currency_rate'],2);
                    $on_way_all[]           = $on_way;
                }
                $url        = U('bill/on_way_and_on_way_money','','',false,true);
                $res        = curl_request($url,$on_way_all);
                $res_arr    = json_decode($res,true);
                if($res_arr['code'] != 10000111) {
                    ELog::add('增加在途数据失败：'.json_encode($on_way_all).$res,ELog::ERR);
                }
            }
            $email          = new SMSEmail();
            $email_address  = $order['prepared_by'].'@gshopper.com';
            $cc_address     = M('cmn_cd','tb_ms_')->where(['CD'=>$order['payment_company']])->getField('ETC2');
            $email->sendEmail($email_address,$email_title,$email_content,$cc_address);
            $this->success(L($msg.',操作成功'),U('index/index'));
        }else {
            $model->rollback();
            $this->error(L($msg.',操作失败'),U('index/index'));
        }
    }

    /**
     * 下载文件
     */
    public function download() {
        $download = new FileDownloadModel();
        if ('excel' == I('path')) {
            $download->path = ATTACHMENT_DIR_EXCEL;
        } else  if ('doc' == I('path')) {
            $download->path = ATTACHMENT_DIR_DOC;
        }
        $file = I('get.file');
        $download->fname = I('get.file');
        $download->origin_file_name = I('get.origin_file_name');
        $download->downloadFile();
    }

    /**
     * 获取OA上的PO数据
     */
    public function get_po_data()
    {

        $CON_NO = $this->getParams()['CON_NO'];
        if(M('order_detail','tb_pur_')->where(['procurement_number'=>$CON_NO])->find()) {
            $this->ajaxReturn(L('该PO单号已创建过采购订单，请勿重复创建!'),'',0);
        }
        $oci = new MeBYModel();
        $sql = "SELECT * FROM ECOLOGY.FORMTABLE_MAIN_91 a left join ECOLOGY.HRMRESOURCE b on a.SQR = b.ID  WHERE DJBH='" . $CON_NO . "'";
        $checkSql = "SELECT wr.STATUS FROM ECOLOGY.FORMTABLE_MAIN_91 fm LEFT JOIN ECOLOGY.WORKFLOW_REQUESTBASE wr on fm.REQUESTID = wr.REQUESTID WHERE DJBH = '" . $CON_NO . "'";
        $checkRet = $oci->testQuery($checkSql);
        //if ($checkRet[0]['STATUS'] != '结束') $this->ajaxReturn('编号为：' . $CON_NO . ' 的合同不存在或尚未完成审核，请修改', '', 0);
        $ret = $oci->testQuery($sql);
        if ($ret) {
            $data = $ret[0];
            if($data['CGBUSINESSLICENSE']) {
                $data['supplier'] = M('sp_supplier','tb_crm_')->field('SP_NAME,SP_TEAM_CD,RISK_RATING,SP_CHARTER_NO,SP_NAME_EN')->where(['SP_CHARTER_NO'=>$data['CGBUSINESSLICENSE'],'DATA_MARKING'=>0])->find();
                $has_cooperate = (new TbPurOrderDetailModel())->supplierHasCooperate($data['CGBUSINESSLICENSE']);
                if($has_cooperate) {
                    $data['supplier']['has_cooperate'] = 1;
                }else {
                    $data['supplier']['has_cooperate'] = 0;
                }
            }
            $this->ajaxReturn($data, '', 1);
        } else {
            $this->ajaxReturn(L('未查询到编号为：') . $CON_NO . L('的合同，请修改'), '', 0);
        }
    }

    /**
     * 查询合同
     */
    public function getContract() {
        $sp_charter_no = I('request.sp_charter_no');
        $contract = M('contract','tb_crm_')
            ->alias('t')
            ->field('SP_BANK_CD,BANK_ACCOUNT,SWIFT_CODE,CON_NO,CON_NAME,SP_CHARTER_NO,collection_account_name,a.CD CON_COMPANY_CD')
            ->join('left join tb_ms_cmn_cd a on a.ETC=t.CON_COMPANY_CD and a.CD like "'.TbMsCmnCdModel::$our_company_cd_pre.'%" and t.CON_COMPANY_CD<>"" and a.USE_YN = "Y"')
            ->where(['SP_CHARTER_NO'=>$sp_charter_no])
            ->where(['audit_status_cd' => TbCrmContractModel::FINISH])
            ->where("IS_RENEWAL = 0 or (IS_RENEWAL = 1 and END_TIME >= '%s' )",[date('Y-m-d H:i:s')])#是否自动续约，1-否；0-是； 
            ->order('create_time desc')
            ->select();
        if($contract !== false) {
            $this->ajaxReturn($contract,'',1);
        }else {
            $this->ajaxReturn(L('获取失败'),'',0);
        }
    }

    /**
     * 导入商品
     */
    public function importGoods() {
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
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $expe = [];
        $goods_info_url = U('Stock/Searchguds','','',false,true);
        $msg = '';
        $skus = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $temp   = [];
            $search    = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $price  = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $number     = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $res = json_decode(curl_request($goods_info_url,['GSKU'=>$search]),true);
            if(!$search || $res['status'] == 0) {
                $error = true;
                $msg .= "第{$currentRow}行商品不存在<br />";
            }else {
                $sku = $res['info'][0]['GUDS_OPT_ID'];
                $skus[$currentRow] = $res['info'][0]['GUDS_OPT_ID'];
            }
            if(!is_numeric($price) || $price<=0) {
                $error = true;
                $msg .= "第{$currentRow}行商品价格有误（比如书籍中含0、或者为负数）<br />";
            }
            if(!is_numeric($number) || strstr($number,'.') || $number<=0) {
                $error = true;
                $msg .= "第{$currentRow}行商品数量有误（比如数量中含小数、或者小于1）<br />";
            }
            if ($sku && $price && $number) {
                $temp['search']     = $search;
                $temp['sku']        = $sku;
                $temp['now_number'] = $currentRow-1;
                $temp['price']      = round($price,2);
                $temp['number']     = $number;
                $temp['goods_money']= round($price*$number,2);
                $temp['goods_name'] = $res['info'][0]['Guds']['GUDS_NM'];
                $temp['guds_img']   = $res['info'][0]['Img'];
                $temp['val_str']    = $res['info']['opt_val'][0]['val'];
                $standing           = M('center_stock','tb_wms_')
                    ->field('sum(sale) as sale_num,sum(on_way) on_way_num')
                    ->where(['SKU_ID'=>$sku])
                    ->group('SKU_ID')
                    ->find();
                $temp['sale_num']   = $standing['sale_num'];
                $temp['on_way_num'] = $standing['on_way_num'];
                $expe[]             = $temp;
            }
        }
        if($error) {
            $this->error($msg,'',true);
        }else {
            $currency_s = I('request.currency');
            $this->guds = $expe;
            $cmn_cd           = M('cmn_cd','tb_ms_');
            $this->currency   = $cmn_cd->where("CD_NM='기준환율종류코드'")->select(); //币种查询
            $this->currency_s   = $currency_s;
            $this->is_import = 1;
            $html = $this->fetch('order_add_ajax');
            $this->success($html,'',true);
        }
    }

    /**
     * 入库
     */
    public function warehouse() {
        if(IS_POST) {
            $warehouse_save = $_POST;
            if ($_FILES['tally_list']['name']) {
                // 图片上传
                $fd = new FileUploadModel();
                $ret = $fd->uploadFile();
                if(!$ret){
                    $this->error(L("保存失败：上传文件失败").$fd->error,U('OrderDetail/order_list'),2);
                }else {
                    $warehouse_save['tally_list'] = $fd->save_name;
                }
            }
            foreach ($warehouse_save['goods'] as $k => $v) {
                $number_info = []; $check_small_team = [];
                foreach ($v['number_info']['expiration_date'] as $key => $value) {
                    $info['number']             = $v['number_info']['number'][$key];
                    $info['broken_number']      = $v['number_info']['broken_number'][$key];
                    $info['expiration_date']    = $value;
                    $info['small_team_code']    = $v['number_info']['small_team_code'][$key] ? $v['number_info']['small_team_code'][$key] : '';
                    $number_info[]              = $info;
                    if (isset($v['number_info']['small_team_code'][$key])) { // 兼容历史没有小团队的采购单
                        if (!in_array($info['small_team_code'], $check_small_team)) {
                            $check_small_team[] = $info['small_team_code'];
                        } else {
                            $this->error('入库失败，同一个SKU下小团队不可重复');
                        }
                    } 
                }
                $warehouse_save['goods'][$k]['number_info_warehouse'] = json_encode($number_info);
            }
            $model              = D('Purchase/Warehouse','Logic');
            $res                = $model->warehouse($warehouse_save);
            if($res) {
                $this->email_info   = $email_info = $model->email_info;
                $email_content      = $this->fetch('warehouse_email');
                $email_m            = new SMSEmail();
                $email_m->sendEmail($email_info['receiver'],$email_info['title'],$email_content,$email_info['cc']);
                $this->success(L('入库成功'));
            }else {
                $this->error(L('入库失败：'.$model->getError()));
            }
        }

        $id = I('request.id');
        $detail = (new PurService())->getShipInfo($id);
        $detail = (new PurService())->getSmallTeamNormalGoodsNums($detail);
        // 先判断下该采购单是否是历史数据（即该采购单是小团队功能上线前创建的，可根据是否采购商品小团队的值是否为空决定）
        $sell_team_list = NULL;
        if ($detail['goods'][0]['information']['sell_small_team_json']) {
            $sell_team = $detail['relevance']['sell_information']['sell_team']; // 小团队列表
            $sell_team_list = CodeModel::getSellSmallTeamCodeArr($sell_team);
            $sell_team_list = array_column($sell_team_list, 'CD_VAL', 'CD');
        }
        $this->sell_team_list = $sell_team_list;
        $detail['credential']       = json_decode($detail['credential'],true);
        $this->detail               = $detail;
        $cd_m                       = new TbMsCmnCdModel();
        $this->warehouse            = $cd_m->warehouse();
        $this->currency             = $cd_m->getCdY($cd_m::$currency_cd_pre);
        $this->tax_rate             = $cd_m->taxRate();
        $this->warehouse_difference = $cd_m->warehouseDifference();
        $this->display();
    }


    /**
     * excel入库
     */
    public function warehouse_by_excel() {
        set_time_limit(0);
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
        $goods = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $arrival_date_actual    = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $warehouse              = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $sku_id                 = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            $number                 = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue());
            $procurement_number     = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());
            $goods[$procurement_number][] = [
                'procurement_number'    => $procurement_number,
                'number'                => $number,
                'sku_id'                => $sku_id,
                'warehouse'             => $warehouse,
                'arrival_date_actual'   => substr($arrival_date_actual,0,10)
            ];
        }
        $purchase_logic = D('Purchase','Logic');
        switch ($purchase_logic->excelWarehouse($goods)) {
            case 0 :
                $response_data = [
                    'status'        => 0,
                    'info'          => '导入失败',
                    'has_failure'   => true,
                    'failure_data'  => $purchase_logic->res_orders,
                ];
                $this->ajaxReturn($response_data);
                break;
            case 1 :
                $response_data = [
                    'status'        => 1,
                    'info'          => '导入成功',
                    'has_failure'   => false,
                    'failure_data'  => [],
                ];
                $this->ajaxReturn($response_data);
                break;
            case 2 :
                $response_data = [
                    'status'        => 1,
                    'info'          => '部分成功',
                    'has_failure'   => true,
                    'failure_data'  => $purchase_logic->res_orders,
                ];
                $this->ajaxReturn($response_data);
                break;
        }
    }

    /**
     * 入库列表
     */
    public function warehouse_list() {
        import('ORG.Util.Page');
        $model  = M('ship','tb_pur_');
        $params = I('request.');
        if($params['sku_or_upc']) {
            $map_s['pa.upc_id'] = $params['sku_or_upc'];
            $map_s['pa.sku_id'] = $params['sku_or_upc'];
            $map_s['_string'] = "FIND_IN_SET('{$params['sku_or_upc']}',pa.upc_more)";
            $map_s['_logic']    = 'or';
            $where[0] = $map_s;
        }
        if(isset($params['warehouse_status'])  && '' !== $params['warehouse_status']) {
            if(false !== strstr($params['warehouse_status'],',')){
                $where['t.warehouse_status'] = ['IN',WhereModel::stringToInArray($params['warehouse_status'])];
            }else {
                $where['t.warehouse_status'] = $params['warehouse_status'];
            }
        }
        $params['warehouse']                            ? $where['t.warehouse']         = $params['warehouse']:'';
        $params['supplier_id']                          ? $where['b.supplier_id']       = ['like','%'.html_entity_decode($params['supplier_id']).'%']:'';
        if($params['number']) {
            /*$column         = $params['number_type'] ? 'procurement_number' : 'online_purchase_order_number';
            $where[$column] = ['like','%'.$params['number'].'%'];*/
            $conditions['procurement_number']           = $params['number'];
            $conditions['online_purchase_order_number'] = $params['number'];
            $conditions['_logic'] = 'or';
            $where['_complex']    = $conditions;
        };
        $params['bill_of_landing']                      ? $where['bill_of_landing']     = $params['bill_of_landing']:'';
        $params['prepared_by']                          ? $where['prepared_by']         = ['like','%'.$params['prepared_by'].'%']:'';
        $params['payment_company']                      ? $where['payment_company']     = $params['payment_company']:'';
        $params['purchase_warehousing_by']                      ? $where['tb_con_division_warehouse.purchase_warehousing_by']     = $params['purchase_warehousing_by']:'';
        $params['tb_sell_quotation.arrive_time']                      ? $where['arrive_time']     = $params['arrive_time']:'';
        $params['start_time']                           ? $where['shipment_date']       = ['egt',$params['start_time']]:'';
        $params['end_time']                             ? $where['shipment_date']       = ['elt',$params['end_time']]:'';
        $params['start_time'] && $params['end_time']    ? $where['shipment_date']       = ['between',[$params['start_time'],$params['end_time']]]:'';
        $where['language'] = ['in',PmsBaseModel::getLangCondition()];
        $count_sql  = $model->alias('t')
            ->field('t.id')
            ->join('left join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('left join tb_pur_ship_goods d on d.ship_id=t.id')
            ->join('left join tb_pur_goods_information e on e.information_id=d.information_id')
            ->join('left join tb_sell_quotation on tb_sell_quotation.quotation_code=b.procurement_number')
            ->join('left join tb_con_division_warehouse on tb_con_division_warehouse.warehouse_cd=t.warehouse')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on e.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id')
            ->group('t.id')
            ->where($where)
            ->buildSql();
        $count = M()->table($count_sql.' ship_count')->count();
        $page = new Page($count,20);
        $list = $model->alias('t')
            ->field('t.*,a.prepared_by,b.procurement_number,tb_sell_quotation.arrive_time,b.online_purchase_order_number,b.supplier_id,c.CD_VAL warehouse,                substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name')
            ->join('left join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('left join tb_ms_cmn_cd c on c.CD=t.warehouse')
            ->join('left join tb_pur_ship_goods d on d.ship_id=t.id')
            ->join('left join tb_pur_goods_information e on e.information_id=d.information_id')
            ->join('left join tb_sell_quotation on tb_sell_quotation.quotation_code=b.procurement_number')
            ->join('left join tb_con_division_warehouse on tb_con_division_warehouse.warehouse_cd=t.warehouse')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on e.sku_information=pa.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id')
            ->where($where)
            ->group('t.id')
            ->limit($page->firstRow.','.$page->listRows)
            ->order('t.arrival_date desc')
            ->select();
        $cd_m                       = new TbMsCmnCdModel();
        $this->warehouses           = $cd_m->warehouseKey();
        $this->purchase_teams       = $cd_m->getCd($cd_m::$purchase_team_cd_pre);
        $this->show                 = $page->show();
        $this->param                = $params;
        $this->count                = $count;
        $this->list                 = $list;
        $this->display();
    }

    /**
     * 入库导出
     */
    public function warehouse_export() {
        $fileName = '采购入库'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $model  = M('ship','tb_pur_');
        $params = I('request.');
        if(is_numeric($params['warehouse_status'])) {
            $where['t.warehouse_status'] = $params['warehouse_status'];
        }
        $params['warehouse']                            ? $where['t.warehouse']         = $params['warehouse']:'';
        $params['supplier_id']                          ? $where['b.supplier_id']       = ['like','%'.$params['supplier_id'].'%']:'';
        $params['procurement_number']                   ? $where['procurement_number']  = $params['procurement_number']:'';
        $params['bill_of_landing']                      ? $where['bill_of_landing']  = $params['bill_of_landing']:'';
        $params['prepared_by']                          ? $where['prepared_by']         = ['like','%'.$params['prepared_by'].'%']:'';
        $params['payment_company']                      ? $where['payment_company']     = $params['payment_company']:'';
        $params['start_time']                           ? $where['shipment_date']       = ['egt',$params['start_time']]:'';
        $params['end_time']                             ? $where['shipment_date']       = ['elt',$params['end_time']]:'';
        $params['start_time'] && $params['end_time']    ? $where['shipment_date']       = ['between',[$params['start_time'],$params['end_time']]]:'';
        $count  = $model->alias('t')
            ->join('left join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->where($where)
            ->count();
        if($count > 10000) {
            $this->error('当前搜索结果超出1万条，系统1次最多导出1万条，请将搜索结果控制在1万条再导出！');
        }
        $list = $model->alias('t')
            ->field('t.*,a.prepared_by,b.procurement_number,b.supplier_id,c.CD_VAL warehouse,e.goods_name')
            ->join('left join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('left join tb_ms_cmn_cd c on c.CD=t.warehouse')
            ->join('left join tb_pur_ship_goods d on d.ship_id=t.id')
            ->join('left join tb_pur_goods_information e on e.information_id=d.information_id')
            ->where($where)
            ->group('t.id')
            ->select();
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['发货编号', 'PO/采购单号', '提单号', '发货数量    ', '已入库数量', '供应商', '采购人', '发货时间', '到货/到港时间','入我方库', '入库仓库', '入库状态'];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] =  $v;
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($list as $k => $v) {
            $row = [];
            $row[] =  $v['warehouse_id'];
            $row[] =  $v['procurement_number']."\t";
            $row[] =  $v['bill_of_landing'];
            $row[] =  $v['shipping_number'];
            $row[] =  $v['warehouse_number'];
            $row[] =  $v['supplier_id'];
            $row[] =  $v['prepared_by'];
            $row[] =  $v['shipment_date'];
            $row[] =  $v['arrival_date'];
            $row[] =  $v['need_warehousing']?'是':'否';
            $row[] =  $v['warehouse'];
            $row[] =  $v['warehouse_status']?'已入库':'待入库';
            fputcsv($fp, $row);
        }
    }

    /**
     * 入库详情
     */
    public function warehouse_detail() {
        $model                  = D('TbPurShip');
        $id                     = I('request.id');
        $detail                 = $model->relation(true)->where(['id'=>$id])->find();

        // 是否有小团队，根据销售团队的CD值的ETC5是否为“有小团队”
        $detail['has_small_team'] = 'N';
        $has_small_team = M('ms_cmn_cd', 'tb_')->where(['CD' => $detail['relevance']['sell_information']['sell_team']])->getField('ETC5');
        if ($has_small_team === '有小团队') {
            $detail['has_small_team'] = 'Y';
        }
        $detail['credential']   = json_decode($detail['credential'],true);
        $detail['warehouse_list'] = M('warehouse','tb_pur_')->where(['ship_id'=>$detail['id']])->select();
        $service_cost           = 0;
        $small_team_arr = CodeModel::getCodeKeyValArr(['N00323']); // 获取小团队key-val
        foreach ($detail['goods'] as $k => $v) {
            $price_rmb          = $v['information']['unit_price']*$detail['relevance']['real_total_rate'];
            $per_service_cost   = ($v['warehouse_cost']-$price_rmb)*$v['warehouse_number'];
            $service_cost       += $per_service_cost;

            $detail['goods'][$k]['information'] = SkuModel::getInfo([$detail['goods'][$k]['information']],'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute'])[0];
            $location_info = M('location_sku','tb_wms_')
                ->alias('t')
                ->join('left join tb_wms_warehouse a on a.id = t.warehouse_id')
                ->where(['t.sku'=>$v['information']['sku_information'],'a.CD'=>$detail['warehouse']['CD']])
                ->find();
            $detail['goods'][$k]['location'] = $location_info['location_code'];
            $detail['goods'][$k]['defective_location_code'] = $location_info['defective_location_code'];
            $sku_info =  D('Pms/PmsProductSku')->where(['sku_id'=>$v['information']['sku_information']])->field(['upc_id','upc_more'])->find(); 
            $detail['goods'][$k]['information']['upc_id'] = $sku_info['upc_id'];
            $detail['goods'][$k]['sell_small_team_arr'] = json_decode($detail['goods'][$k]['sell_small_team_json'], true);
            if ($detail['goods'][$k]['sell_small_team_arr']) {
                foreach ($detail['goods'][$k]['sell_small_team_arr'] as $kk => $value) {
                    $detail['goods'][$k]['sell_small_team_arr'][$kk]['small_team_code_val'] = $small_team_arr[$detail['goods'][$k]['sell_small_team_arr'][$kk]['small_team_code']];
                }
            }

            # 条形码显示 
        
            if($sku_info['upc_more']) {
                $upc_more_arr = explode(',', $sku_info['upc_more']);
                array_unshift($upc_more_arr, $sku_info['upc_id']);
                $detail['goods'][$k]['information']['upc_id'] = implode(",<br/>", $upc_more_arr); # 返回br标签 前端显示换行 
            }
        }
        $detail['service_cost'] = number_format($service_cost,2);
        $this->detail           = $detail;
        $this->display();
    }

    public function warehouse_info() {
        $relevance_id   = I('request.id');
        $relevance      = (new TbPurRelevanceOrderModel())->relation(true)->where(['relevance_id'=>$relevance_id])->find();
        $goods          = (new TbPurGoodsInformationModel())
            ->field('t.search_information,t.sku_information,t.goods_name,t.goods_attribute,t.goods_number,sum(a.ship_number) ship_number,sum(a.warehouse_number) warehouse_number,sum(a.warehouse_number_broken) warehouse_number_broken,pa.upc_id')
            ->alias('t')
            ->join('left join tb_pur_ship_goods a on a.information_id=t.information_id')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on t.sku_information=pa.sku_id')
            ->where(['t.relevance_id'=>$relevance_id])
            ->group('t.information_id')
            ->select();
        $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'goods_image','attributes'=>'goods_attribute']);
        $this->relevance    = $relevance;
        $this->goods        = $goods;
        $this->display();
    }

    /**
     * 查询商品
     */
    public function search_goods() {
        $search_id  = I('request.search_id');
        $now_number = I('post.now_number'); //序号
        $url        = U('Stock/Searchguds','','',false,true);
        $res        = json_decode(curl_request($url,['GSKU'=>$search_id]),true);
        if($res['status'] == 0) {
            $this->error(L('SKU编码/条形码错误'));
        }
        $guds['goods_name'] = $res['info'][0]['Guds']['GUDS_NM'];
        $guds['sku_id']     = $res['info'][0]['GUDS_OPT_ID'];
        $guds['search_id']  = $search_id;
        $guds['guds_img']   = $res['info'][0]['Img'];
        $guds['val_str']    = $res['info']['opt_val'][0]['val'];
        $guds['now_number'] = $now_number;
        $warehouse          = M('guds','tb_ms_')
            ->alias('t')
            ->field('a.CD warehouse,a.CD_VAL warehouse_name,t.IS_SHELF_LIFE as is_shelf_life')
            ->join('left join tb_ms_cmn_cd a on a.CD = t.DELIVERY_WAREHOUSE')
            ->where(['MAIN_GUDS_ID'=>substr($guds['sku_id'],0,8)])
            ->find();
        $guds['warehouse']      = $warehouse['warehouse'];
        $guds['warehouse_name'] = $warehouse['warehouse_name'];
        $guds['is_shelf_life']  = $warehouse['is_shelf_life'];
        $this->success($guds);
    }

    public function payable_list() {
        import('ORG.Util.Page');
        $params = I('');
        $where = $this->payable_where($params);
        if ($where !== 'No result') {
            list($list, $count, $show) = (new PurPaymentService())->getPayableList($where);
        } else {
            $count = 0;
            $list = [];
        }
        // p($list);die;
        $cmn_m = new TbMsCmnCdModel();
        $this->list             = $list;
        $this->page             = $show;
        $this->count            = $count;
        $this->param            = $params;
        $this->our_company      = $cmn_m->getCd($cmn_m::$our_company_cd_pre);
        $this->purchase_team    = $cmn_m->getCd($cmn_m::$purchase_team_cd_pre);
        $this->purchase_type    = $cmn_m->getCd($cmn_m::$purchase_type_cd_pre);
        $this->pur_operation    = $cmn_m->getCd($cmn_m::$pur_operation_cd_pre);
        $this->display();
    }

    public function payable_where($params) {
        $where = [];
        if($params['purchase_type']) $where['b.purchase_type'] = $params['purchase_type'];
        if($params['payment_no']) $where['t.payment_no'] = ['like','%'.$params['payment_no'].'%'];
        /*if($params['number']) {
            $column         = $params['number_type'] ? 'b.procurement_number' : 'b.online_purchase_order_number';
            $where[$column] = ['like','%'.$params['number'].'%'];
        }*/
        if($params['number']) {
            $condition['b.procurement_number']           = $params['number'];
            $condition['b.online_purchase_order_number'] = $params['number'];
            $condition['_logic'] = 'or';
            $where['_complex']   = $condition;
        }
        if($params['supplier_id']) $where['b.supplier_id'] = ['like','%'.html_entity_decode($params['supplier_id']).'%'];
        if($params['our_company']) $where['b.our_company'] = $params['our_company'];
        if($params['payment_manager_by']) $where['tb_con_division_our_company.payment_manager_by'] = $params['payment_manager_by'];
        if($params['payment_company']) $where['b.payment_company'] = $params['payment_company'];
        if($params['prepared_by']) $where['a.prepared_by'] = ['like','%'.$params['prepared_by'].'%'];
        if($params['time_type'] == 0) {
            $time_key = 't.payable_date';
        }elseif($params['time_type'] == 1) {
            $time_key = 'pa.billing_date';
        }else {
            $time_key = 't.update_time';
        }
        if($params['start_time'] && $params['end_time']) {
            $where[$time_key] = ['between',[$params['start_time'].($time_key=='t.update_time'?' 00:00:00':''),$params['end_time'].($time_key=='t.update_time'?' 23:59:59':'')]];
        }elseif($params['start_time']) {
            $where[$time_key] = ['egt',$params['start_time'].($time_key=='t.update_time'?' 00:00:00':'')];
        }elseif($params['end_time']) {
            $where[$time_key] = ['elt',$params['end_time'].($time_key=='t.update_time'?' 23:59:59':'')];
        }
        if($params['status'] !== '' && isset($params['status']) ) $where['t.status'] = $params['status'];
        $where['order_status'] = 'N001320300';

        if (!empty($params['payment_audit_no'])) $where['pa.payment_audit_no'] = $params['payment_audit_no'];
        if (!empty($params['payment_channel_cd'])) $where['pa.payment_channel_cd'] = ['in',explode(',',$params['payment_channel_cd'])];
        if (!empty($params['payment_way_cd'])) $where['pa.payment_way_cd'] = ['in',explode(',',$params['payment_way_cd'])];

        //新增触发操作
        if ($params['pur_operation']) {
            $res = D('Scm/PurOperation')->field('main_id')->where(['action_type_cd' => $params['pur_operation'], 'money_type' => '1'])->select();
            if ($res) {
                $res_arr = array_column($res, 'main_id');
                if (count($res_arr) !== 0) {
                    $where['t.id'] = ['in', $res_arr];
                }
            } else {
                $where = 'No result';
            }

        }
        return $where;
    }

    public function payable_export() {
        $params = I('get.');
        $where = $this->payable_where($params);
        list($list, $count, $show) = (new PurPaymentService())->getPayableList($where, true);
        $map  = [
            ['field_name' => 'payment_no', 'name' => 'Payment Key'],
            ['field_name' => 'payable_status_val', 'name' => '应付状态'],
            ['field_name' => 'payment_audit_no', 'name' => '关联付款单号'],
            ['field_name' => 'procurement_number', 'name' => '采购单号'],
            ['field_name' => 'online_purchase_order_number', 'name' => '采购PO单号'],
            ['field_name' => 'purchase_type_val', 'name' => '采购类型'],
            ['field_name' => 'total_amount', 'name' => '采购总金额'],
            ['field_name' => 'action_type_cd', 'name' => '触发操作'],
            ['field_name' => 'clause_type', 'name' => '适用条款'],
            ['field_name' => 'bill_no', 'name' => '关联单据号'],
            ['field_name' => 'our_company_val', 'name' => '我方公司'],
            ['field_name' => 'supplier_id', 'name' => '供应商名称'],
            ['field_name' => 'supplier_id_en', 'name' => '供应商名称（英文）'],
            ['field_name' => 'online_purchase_website_val', 'name' => '采购网站'],
            ['field_name' => 'online_purchase_account', 'name' => '下单账号'],
            ['field_name' => 'sell_team_val', 'name' => '销售团队'],
            ['field_name' => 'seller', 'name' => '销售同事'],
            ['field_name' => 'payment_company_val', 'name' => '采购团队'],
            ['field_name' => 'prepared_by', 'name' => '采购人'],
            ['field_name' => 'confirm_remark', 'name' => '备注'],
            ['field_name' => 'payment_channel_cd_val', 'name' => '支付渠道'],
            ['field_name' => 'payment_way_cd_val', 'name' => '支付方式'],
            ['field_name' => 'payable_date_before', 'name' => '确认前-预计付款日期'],
            ['field_name' => 'payable_date_after', 'name' => '确认后-预计付款日期'],
            ['field_name' => 'formula', 'name' => '确认前-本期应付金额计算公式'],
            ['field_name' => 'amount_payable', 'name' => '确认前-本期应付金额'],
            ['field_name' => 'amount_confirm', 'name' => '确认后本期应付金额'],
            ['field_name' => 'platform_cd_val', 'name' => '平台名称'],
            ['field_name' => 'store_name', 'name' => '店铺名称'],
            ['field_name' => 'platform_order_no', 'name' => '平台订单号'],
            ['field_name' => 'supplier_collection_account', 'name' => '收款账户名'],
            ['field_name' => 'supplier_opening_bank', 'name' => '收款账户开户行'],
            ['field_name' => 'supplier_card_number', 'name' => '收款银行账号'],
            ['field_name' => 'supplier_swift_code', 'name' => '收款银行SWIFT CODE'],
            ['field_name' => 'collection_account', 'name' => '该支付渠道收款账号'],
            ['field_name' => 'collection_user_name', 'name' => '该支付渠道收款用户名'],
            ['field_name' => 'payment_currency_cd_val', 'name' => '提交付款币种'],
            ['field_name' => 'amount_paid', 'name' => '本单分摊提交付款金额'],
            ['field_name' => 'billing_currency_cd_val', 'name' => '扣款币种'],
            ['field_name' => 'amount_account', 'name' => '本单分摊扣款金额'],
            ['field_name' => 'expense', 'name' => '本单分摊扣款手续费'],
            ['field_name' => 'billing_total_amount', 'name' => '本单分摊扣款总金额'],
            ['field_name' => 'billing_date', 'name' => '出账日期'],
            ['field_name' => 'use_deduction', 'name' => '是否使用抵扣金支付'],
            ['field_name' => 'amount_deduction', 'name' => '本次使用抵扣金金额'],
            ['field_name' => 'lave_amount', 'name' => '抵扣后-剩余应付'],
            ['field_name' => 'remark_deduction', 'name' => '抵扣金使用备注'],
            ['field_name' => 'voucher_deduction', 'name' => '沟通凭证'],
            ['field_name' => 'amount_difference', 'name' => '应付差额'],
            ['field_name' => 'amount_difference', 'name' => '继续支付剩余部分'],
            ['field_name' => 'difference_reason', 'name' => '差异原因'],
            ['field_name' => 'amount_difference', 'name' => '下一次付款金额'],
            ['field_name' => 'next_pay_time', 'name' => '下一次付款日期'],
            ['field_name' => 'confirm_remark', 'name' => '提交付款备注'],
            ['field_name' => 'confirmation_remark', 'name' => '付款/出账确认备注'],
            ['field_name' => 'update_time', 'name' => '单据更新时间'],
        ];
        $this->exportCsv($list, $map);
    }

    public function payable_detail() {
        $id = I('request.id');
        $pur_info = D('Scm/PurOperation')->pur_info;
        list($info, $supplier_info) = (new PurRepository())->getPayableDetail($id, $pur_info);
        if(!$info) $this->error('采购应付不存在');

        //关联应付单信息
        $rel_payable_info = (new PurRepository())->getRelPayableInfo($info['payment_audit_id'], $id);
        $rel_payable_info = CodeModel::autoCodeTwoVal($rel_payable_info, ['amount_currency']);
        foreach ($rel_payable_info as &$value) {
            $value['action_type_cd'] = $pur_info[$value['action_type_cd']]['name'];
        }

        $order = M('relevance_order','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->join('left join tb_crm_sp_supplier c on c.ID=a.supplier_new_id and c.DATA_MARKING=0')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')
            ->where(['t.relevance_id'=>$info['relevance_id']])
            ->find();
        $risk_rating  = M('sp_supplier','tb_crm_')->where(['ID'=>$order['supplier_new_id'],'DATA_MARKING'=>0])->getField('RISK_RATING');
        $old_sup = M('order_detail','tb_pur_')->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')
            ->where(['supplier_new_id'=>$order['supplier_new_id'],'order_status'=>'N001320300','relevance_id'=>['lt',$order['relevance_id']]])
            ->find();
        $this->has_cooperate    = $old_sup ? 1 : 0;
        $this->risk_rating      = $risk_rating;
        $this->info             = DataModel::formatAmount($info);
        $this->order            = DataModel::formatAmount($order);
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->can_return       = D('Purchase', 'Logic')->canReturn($info['relevance_id']);
        $this->supplier_info    = $supplier_info;
        $this->rel_payable_info = DataModel::formatAmount($rel_payable_info);
        $this->display();
    }

    public function payable_info() {
        $relevance_id   = I('request.id');
        $payment = M('payment','tb_pur_')
            ->alias('t')
            ->field('t.*,a.open_bank')
            ->join('left join tb_fin_account_bank a on a.account_bank=t.our_company_bank_account')
            ->where(['relevance_id'=>$relevance_id])
            ->select();
        $relevance = M('relevance_order','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->join('left join tb_crm_sp_supplier c on c.ID=a.supplier_new_id and c.DATA_MARKING=0')
            ->where(['t.relevance_id'=>$relevance_id])
            ->find();
        $risk_rating  = M('sp_supplier','tb_crm_')->where(['ID'=>$relevance['supplier_new_id'],'DATA_MARKING'=>0])->getField('RISK_RATING');
        $old_sup = M('order_detail','tb_pur_')->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')
            ->where(['supplier_new_id'=>$relevance['supplier_new_id'],'order_status'=>'N001320300','relevance_id'=>['lt',$relevance['relevance_id']]])
            ->find();
        $this->has_cooperate    = $old_sup ? 1 : 0;
        $this->risk_rating      = $risk_rating;
        $this->payment          = $payment;
        $this->count            = count($payment);
        $this->relevance        = $relevance;
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->display();
    }

    // 10287 对于预付款拆分问题抵扣金触发相关的优化
    public function fixPurPayHist()
    {
        try {
            $model = new Model();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new PurPaymentService($model))->fixPurPayHistDetail();
            $model->commit();
            p($res);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
            $this->error($res['msg']);
        }
    }

    //采购应付金额确认
    public function payable_confirm() {
        if(IS_POST) {
            try {
                $model = new Model();
                $request_data = ZUtils::filterBlank($_POST);

                if ($request_data) {
                    $this->confirmValidate($request_data);
                } else {
                    throw new Exception('请求为空');
                }
                $rClineVal = RedisModel::lock('payment_id' . $request_data['confirm']['id'], 10);
                if (!$rClineVal) {
                    throw new Exception('获取流水锁失败');
                }
                $res = DataModel::$success_return;
                $res['code'] = 200;
                $model->startTrans();
                $payment_audit_id = (new PurPaymentService($model))->payableConfirm($request_data);
                $model->commit();
                RedisModel::unlock('payment_id' . $request_data['confirm']['id']);

                // 采购应付增加 企业微信审核
                (new PurPaymentService())->purPaymentWechatApproval($payment_audit_id);


            } catch (Exception $exception) {
                $model->rollback();
                $res = $this->catchException($exception);
                $this->error($res['msg']);
            }
            $this->success('success');
        }

        $id = I('request.id');
        list($info, $supplier_info) = (new PurRepository())->getPayableDetail($id);
        if(!$info) $this->error(L('采购应付不存在'));

        $order = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('t.*,a.*,b.sell_team,b.seller,c.ID supplier_id, a.supplier_id as sup_name')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->join("left join tb_crm_sp_supplier c on c.ID=a.supplier_new_id and c.DATA_MARKING=0")
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')
            ->where(['t.relevance_id'=>$info['relevance_id']])
            ->find();
        if ($order['supplier_id']) {
            $order['amount_deduction'] = M('deduction', 'tb_pur_')
                ->where(['supplier_id' => $order['supplier_id'], 'our_company_cd' => $order['our_company'], 'deduction_currency_cd' => $order['amount_currency']])
                ->getField('over_deduction_amount');
            list($amount_deduction_compensation, $is_require) = $this->getOrderCompensation($order);
            $order['amount_deduction_compensation'] = $amount_deduction_compensation;
            $order['amount_deduction_compensation_require'] = $is_require;
        } else {
            $order['amount_deduction'] = 0;
        }

        $this->info               = $info;
        $this->order              = $order;
        $this->payment_dif_reason = (new TbMsCmnCdModel())->getCdY(TbMsCmnCdModel::$payment_dif_reason_cd_pre);
        $this->purchase_type      = TbPurOrderDetailModel::$purchase_type;
        $this->supplier_info      = $supplier_info;
        $this->now_date           = date('Y-m-d');
        $this->display();
    }

    public function getOrderCompensation($order)
    {
        /*【赔偿返利金分母金额确定】
            根据采购单采购单号确认【供应商】&【我方公司】&【币种】
            有账户
                返回所有账户的余额总额
            没有账户
                展示为0

        【赔偿返利金是否带星号必填确定逻辑】
            根据采购单采购单号确认【供应商】&【我方公司】&【币种】
            有账户
                必填，没有默认值
            没有账户
                该供应商对应的任意采购单中有录入过返利、赔偿类型的抵扣金（查找范围包含原来历史数据）
                    有
                        必填，没有默认值
                    无
                        禁止填写，默认值为0*/
        $list = M('deduction_compensation', 'tb_pur_')
        ->field('over_deduction_amount')
        ->where(['supplier_id' => $order['supplier_id'], 'our_company_cd' => $order['our_company'], 'deduction_currency_cd' => $order['amount_currency']])
        ->select();
        $amountArr = array_column($list, 'over_deduction_amount');
        $money_deduction    = 0;
        foreach ($amountArr as $v) {
            $money_deduction = bcadd($money_deduction,$v,8);
        }
        $is_require = 'N';
        if (count($list) !== 0) {
            $is_require = 'Y';
        } else {
            $mapWhere['t.supplier_id'] = $order['supplier_id'];
            $mapWhere['pdd.is_revoke'] = '0';
            $mapWhere['pdd.deduction_type_cd'] = array('in', ['N002660200', 'N002660201']);
            $res = M('deduction', 'tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_deduction_detail pdd on pdd.deduction_id = t.id')
            ->where($mapWhere)
            ->select();
            if ($res) {
                $is_require = 'Y';
            }
        }
        return [$money_deduction, $is_require];
    }

    public function confirmValidate($request_data) {
        $data          = $request_data['confirm'];
        $payment_audit = array_merge($request_data['payment_audit'],$request_data['order']);  
        if(!$data['amount_confirm']) {
            throw new Exception(L('请填写确认金额'));
        }
        if($data['amount_difference'] > 0 ) {
            if($data['pay_remainder'] == 1) {
                if(!$data['next_pay_time']) {
                    throw new Exception(L('请填写下次付款时间'));
                }
            }else {
                if(!$data['difference_reason']) {
                    throw new Exception(L('请选择差异原因'));
                }
            }
        }
        if((empty($data['voucher_deduction']) || $data['voucher_deduction'] == '[]') && $data['amount_deduction_compensation'] > 0) {
            throw new Exception(L('未上传赔偿及返利金使用凭证'));
        }
        //【支付渠道】=银行 & 【支付方式】=转账
        if ($payment_audit['payment_channel_cd'] == 'N001000301' && $payment_audit['payment_way_cd'] == 'N003020001') { //支付方式为转账
            //新增验证逻辑 #10691 应付确认/付款申请提交增加校验
            //①【确认后-SWIFT CODE】和【确认后-收款银行本地结算代码】至少填一个，当然可以都填。
            //②【确认后-SWIFT CODE】如果填了，则必填8位或11位字符串。
            if (empty($payment_audit['supplier_swift_code']) && empty($payment_audit['bank_settlement_code'])) {
                throw new Exception(L('【确认后-SWIFT CODE】和【确认后-收款银行本地结算代码】至少填一个'));
            }
            if (!empty($payment_audit['supplier_swift_code']) && !preg_match('/^.{8}$|^.{11}$/', $payment_audit['supplier_swift_code'])) {
                throw new Exception(L('【确认后-SWIFT CODE】必填8位或11位字符串。'));
            };
        }
        list($rules, $custom_attributes) = TbPurPaymentAuditModel::getValidateData($payment_audit['payment_channel_cd'],$payment_audit['payment_way_cd']);
        $rules['payment_channel_cd']   = 'required|size:10';
        $rules['payment_way_cd']       = 'required|size:10';
        $rules['our_company_cd']       = 'required';
        $rules['payable_date_before']  = 'required';
        $rules['payable_date_after']   = 'required';
        $rules['payable_currency_cd'] = 'required';
        $custom_attributes['payment_channel_cd'] = '支付渠道';
        $custom_attributes['payment_way_cd']     = '支付方式';
        $custom_attributes['our_company_cd']     = '我方公司code';
        $custom_attributes['payable_date_before']= '确认前-预计付款日期';
        $custom_attributes['payable_date_after'] = '确认后-预计付款日期';
        $custom_attributes['payable_currency_cd'] = '采购单币种';
        $this->validate($rules, $payment_audit, $custom_attributes);
    }

    //应付单撤回到待确认
    public function cancel_confirm() {
        try {
            $model            = new Model();
            $request_data     = ZUtils::filterBlank($_GET);
            $payment_audit_id = $request_data['payment_audit_id'];
            if (!$payment_audit_id) {
                throw new Exception(L('付款单id为空'));
            }
            $rClineVal    = RedisModel::lock('cancel_payment' . $payment_audit_id, 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new PurPaymentService($model))->cancelConfirm($payment_audit_id);
            $model->commit();
            RedisModel::unlock('cancel_payment' . $payment_audit_id);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
            $this->error(L($res['msg']));
        }
        $this->success('撤回成功');
    }

    public function payable_write_off() {
        if(IS_POST) {
            $data   = I('post.');
            $model  = D('TbPurPayment');
            $res    = $model->paymentWriteOff($data);
            if(!$res) {
                $this->ajaxError([], $model->getError());
            }
            $this->ajaxSuccess();
        }
        $id = I('request.id');
        $info = M('payment','tb_pur_')
            ->alias('t')
            ->field('t.*,a.open_bank')
            ->join('left join tb_fin_account_bank a on a.account_bank=t.our_company_bank_account')
            ->where(['t.id'=>$id])
            ->find();
        if(!$info) $this->error(L('采购应付不存在'));
        // 触发操作等信息
        $res = D('Scm/PurOperation')->field('action_type_cd, clause_type, bill_no')->where(['main_id' => $id])->find();
        $pur_info = D('Scm/PurOperation')->pur_info;
        $clause_type = D('Scm/PurOperation')->clause_type;
        //触发操作
        $info['action_type_cd'] = $pur_info[$res['action_type_cd']]['name'];
        //适用条款
        $info['clause_type'] = $clause_type[$res['clause_type']];
        //关联单据号
        $info['bill_no'] = $res['bill_no'] ? $pur_info[$res['action_type_cd']]['bill_no'] . " : {$res['bill_no']}" : '';
        //计算公式
        $info['formula'] = $pur_info[$res['action_type_cd']]['formula'];
        
        $info['voucher'] = explode(',',$info['voucher']);
        $info['voucher_deduction'] = json_decode($info['voucher_deduction'],true);
        $order = M('relevance_order','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->join('left join tb_crm_sp_supplier c on c.ID=a.supplier_new_id and c.DATA_MARKING=0')
            ->where(['t.relevance_id'=>$info['relevance_id']])
            ->find();
        $risk_rating  = M('sp_supplier','tb_crm_')->where(['ID'=>$order['supplier_new_id'],'DATA_MARKING'=>0])->getField('RISK_RATING');
        $old_sup = M('order_detail','tb_pur_')->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')
            ->where(['supplier_new_id'=>$order['supplier_new_id'],'order_status'=>'N001320300','relevance_id'=>['lt',$order['relevance_id']]])
            ->find();
        $this->has_cooperate    = $old_sup ? 1 : 0;
        $this->risk_rating      = $risk_rating;
        $this->info             = $info;
        $this->order            = $order;
        $this->our_company      = (new TbMsCmnCdModel())->getCdY(TbMsCmnCdModel::$our_company_cd_pre);
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->currency         = (new TbMsCmnCdModel())->getCdY(TbMsCmnCdModel::$currency_cd_pre);
        $this->display();
    }

    //应付单/付款撤回到待付款
    public function payable_return_to_payment_confirm() {
        try {
            $model        = new Model();
            $model->startTrans();
            $id = I('request.id');
            $reason = trim(I('request.reason'));
            $source_cd = trim(I('request.source_cd'));
            if (!$id) {
                throw new Exception(L('撤回参数缺失'));
            }
            if (!$reason) {
                throw new Exception(L('撤回原因必填'));
            }
            if (!$source_cd) {
                throw new Exception(L('应付来源必填'));
            }
            $rClineVal    = RedisModel::lock('payment_confirm_cancel' . $id, 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            //  采购应付 调拨应对 B2C退款
            if ($source_cd == TbPurPaymentAuditModel::$source_allo_payable) {
                // 调拨应付
                (new ExpenseBillPaymentService($model))->returnToPaymentConfirm($id, $reason);
            } else if ($source_cd == TbPurPaymentAuditModel::$source_b2c_payable){
                // B2C退款
                (new B2CPaymentService($model))->returnToPaymentConfirm($id, $reason);
            } else if ($source_cd == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                (new TransferPaymentService($model))->returnToPaymentConfirm($id, $reason);
            } else if ($source_cd == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                (new TransferPaymentService($model))->returnToPaymentConfirm($id, $reason);
            } else if ($source_cd == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款
                (new GeneralPaymentService($model))->returnToPaymentConfirm($id, $reason);
            }else{
                // 采购应付
                (new PurPaymentService($model))->returnToPaymentConfirm($id, $reason);
            }
            $model->commit();
            RedisModel::unlock('payment_confirm_cancel' . $id);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
            $this->ajaxError('',$res['msg']);
        }
        $this->ajaxSuccess();
    }

    public function paid_email_content($name = 'paid_email') {
        return $this->fetch($name);
    }

    public function purchase_return() {
        $relevance_id   = I('post.relevance_id');
        $reason         = I('post.reason');
        if(!$relevance_id || !$reason) {
            $this->error = L('参数错误');
        }
        $purchase_logic = D('Purchase','Logic');
        $res = $purchase_logic->returnToDraft($relevance_id);
        if($res) {
            (new TbPurActionLogModel())->addLog($relevance_id);
            $email          = new SMSEmail();
            $email_title    = "采购订单被退回提醒";
            $this->prepared_by          = $purchase_logic->relevance['prepared_by'];
            $this->procurement_number   = $purchase_logic->order['procurement_number'];
            $this->reason               = $reason;
            $this->user                 = $_SESSION['m_loginname'];
            $email_content              = $this->fetch();
            $res            = $email->sendEmail($purchase_logic->relevance['prepared_by'].'@gshopper.com',$email_title,$email_content);
            if(!$res) {
                $this->success('操作成功，但是邮件发送失败：'.$email->getError());
            }else {
                $this->success('操作成功');
            }
        }else {
            $this->error('操作失败：'.$purchase_logic->getError());
        }
    }

    public function invoice_list() {
        import('ORG.Util.Page');
        $param = $this->params();
        $where = $this->invoice_where($param);
        $count_sql = M('relevance_order','tb_pur_')
            ->field('t.relevance_id')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_invoice b on b.relevance_id=t.relevance_id')
            ->join('left join tb_ms_cmn_cd AS our_cd on our_cd.CD = a.our_company')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')
            ->group('t.relevance_id')
            ->where($where)
            ->buildSql();
        $count_sql = 'select count(*) count from' . $count_sql . ' a limit 1';
        $count  = M()->query($count_sql)[0]['count'];
        $page   = new Page($count,20);
        $list   = M('relevance_order','tb_pur_')
            ->field('t.*,a.*')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_invoice b on b.relevance_id=t.relevance_id')
            ->join('left join tb_ms_cmn_cd AS our_cd on our_cd.CD = a.our_company')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')
            ->where($where)
            ->group('t.relevance_id')
            ->limit($page->firstRow,$page->listRows)
            ->order('t.relevance_id desc,b.id desc')
            ->select();
        $this->param            = $param;
        $this->list             = $list;
        $this->count            = $count;
        $this->page             = $page->show();
        $cd_m                   = new TbMsCmnCdModel();
        $this->our_company      = $cd_m->getCd($cd_m::$our_company_cd_pre);
        $this->purchase_team    = $cd_m->getCd($cd_m::$purchase_team_cd_pre);
        $this->invoice_type     = $cd_m->getCd($cd_m::$invoice_type_cd_pre);
        $this->display();
    }

    private function invoice_where($param) {
        if(isset($param['purchase_type']) && $param['purchase_type'] !== '') $where['purchase_type'] = $param['purchase_type'];
        if(isset($param['payment_status']) && $param['payment_status'] !== '') $where['payment_status'] = $param['payment_status'];

        if(isset($param['invoice_status']) && $param['invoice_status'] !== '') {
            if ($param['invoice_status'] && false !== strstr($param['invoice_status'],',')) {
                $where['invoice_status'] = ['in',WhereModel::stringToInArray($param['invoice_status'])];
            }else{
                $where['invoice_status'] = $param['invoice_status'];
            }
        }
        if(isset($param['has_invoice_unconfirmed']) && $param['has_invoice_unconfirmed'] !== '') {
            if ($param['has_invoice_unconfirmed'] && false !== strstr($param['has_invoice_unconfirmed'],',')) {
                $where['has_invoice_unconfirmed'] = ['in',WhereModel::stringToInArray($param['has_invoice_unconfirmed'])];
            }else{
                $where['has_invoice_unconfirmed'] = $param['has_invoice_unconfirmed'];
            }
        }
        /*$number_key = $param['number_type'] ? 'procurement_number' : 'online_purchase_order_number';
        if($param['procurement_number']) $where[$number_key] = $param['procurement_number'];*/
        if($param['procurement_number']) {
            $conditions['procurement_number']           = $param['procurement_number'];
            $conditions['online_purchase_order_number'] = $param['procurement_number'];
            $conditions['_logic'] = 'or';
            $where['_complex']    = $conditions;
        }

        if($param['supplier_id']) $where['supplier_id'] = ['like','%'.$param['supplier_id'].'%'];
        if($param['supplier_id_en']) $where['supplier_id_en'] = ['like','%'.$param['supplier_id_en'].'%'];
        if($param['our_company']) $where['our_company'] = $param['our_company'];
        if($param['invoice_affirm_by']) $where['tb_con_division_our_company.invoice_person_charge_by'] = $param['invoice_affirm_by'];
        if($param['payment_company']) $where['payment_company'] = $param['payment_company'];
        if($param['prepared_by']) $where['prepared_by'] = ['like','%'.$param['prepared_by'].'%'];
        if($param['invoice_type']) $where['a.invoice_type'] = $param['invoice_type'];
        if($param['invoice_no']) $where['invoice_no'] = ['like','%'.$param['invoice_no'].'%'];
        if($param['start_time'] && $param['end_time']) {
            $where['sou_time'] = ['between',[$param['start_time'],$param['end_time']]];
        }elseif($param['start_time']) {
            $where['sou_time'] = ['egt',$param['start_time']];
        }elseif($param['end_time']) {
            $where['sou_time'] = ['elt',$param['end_time']];
        }
        $where['order_status'] = 'N001320300';
        return $where;
    }

    public function invoice_export() {
        $fileName = '采购发票'.time().'.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $param = $this->params();
        $where = $this->invoice_where($param);
        $count_sql = M('relevance_order','tb_pur_')
            ->field('t.relevance_id')
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_invoice b on b.relevance_id=t.relevance_id')
            ->join('left join tb_ms_cmn_cd AS our_cd on our_cd.CD = a.our_company')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')
            ->group('t.relevance_id')
            ->where($where)
            ->buildSql();
        $count  = M()->table($count_sql.' a')->count();

        if($count > 50000) {
            $this->error('当前搜索结果超出5万条，系统1次最多导出5万条，请将搜索结果控制在5万条再导出！');
        }

        $list   = M('relevance_order','tb_pur_')
            ->field('a.invoice_title,a.invoice_no,a.action_no,a.invoice_money,a.tax_rate,a.invoice_type,a.status,a.create_user,a.create_time,t.prepared_by,b.procurement_number,b.online_purchase_order_number,b.procurement_date,b.supplier_id,b.supplier_id_en,b.our_company,b.amount_currency,b.amount,b.payment_company,b.order_remark,t.payment_status,t.relevance_id,c.seller')
            ->alias('t')
            ->join('left join tb_pur_invoice a on a.relevance_id=t.relevance_id')
            ->join('left join tb_pur_order_detail b on t.order_id=b.order_id')
            ->join('left join tb_pur_sell_information c on c.sell_id=t.sell_id')
            ->join('left join tb_ms_cmn_cd AS our_cd on our_cd.CD = b.our_company')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=b.our_company')
            ->where($where)
            ->order('t.relevance_id')
            ->select();
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['采购单号', '采购PO单号', '操作编号', '付款状态', '采购币种', '采购金额', '发票状态', '发票币种', '发票金额', '供应商名称', '供应商名称（EN）', '我方公司', '采购团队', '采购人', '创建时间', '销售同事', '发票类型','订单备注', '税点', '发票号码', '发票抬头', '发票提交人', '发票提交时间'];

        // 输出Excel列名信息
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = $v;
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);
        $invoiced_money = 0;
        foreach ($list as $k => $v) {
            $row = [];
            $row[] = $v['procurement_number']."\t";
            $row[] = $v['online_purchase_order_number']."\t";
            $row[] = $v['action_no']."\t";
            $row[] = $v['payment_status']==0?'未付款':($v['payment_status']==1?'部分付款':'完成付款');
            $row[] = cdVal($v['amount_currency']);
            $row[] = number_format($v['amount'],2);
            $row[] = $v['status']!==null?($v['status']?'已确认':'待确认'):'未开票';
            $row[] = $v['invoice_money']!==null?(cdVal($v['amount_currency'])):'';
            $row[] = $v['invoice_money']!==null?(number_format($v['invoice_money'],2)):'';
            $row[] = $v['supplier_id'];
            $row[] = $v['supplier_id_en'];
            $row[] = cdVal($v['our_company']);
            $row[] = cdVal($v['payment_company']);
            $row[] = $v['prepared_by'];
            $row[] = $v['procurement_date'];
            $row[] = $v['seller'];
            $row[] = cdVal($v['invoice_type']);
            $row[] = $v['order_remark'];
            $row[] = cdVal($v['tax_rate']);
            $invoice_no_arr    = json_decode($v['invoice_no'],true);
            $invoice_no        = '';
            foreach ($invoice_no_arr as $val) {
                $invoice_no .= $val['no'].",";
            }
            $row[] = trim($invoice_no,',')."\t";
            $row[] = $v['invoice_title'];
            $row[] = $v['create_user'];
            $row[] = $v['create_time'];
            fputcsv($fp, $row);
            $invoiced_money += $v['invoice_money'];
            if($v['relevance_id'] != $list[$k+1]['relevance_id'] && $v['action_no']) {
                if($invoiced_money < $v['amount']) {
                    $row = [];
                    $row[] = $v['procurement_number']."\t";
                    $row[] = $v['online_purchase_order_number']."\t";
                    $row[] = '';
                    $row[] = $v['payment_status']==0?'未付款':($v['payment_status']==1?'部分付款':'完成付款');
                    $row[] = cdVal($v['amount_currency']);
                    $row[] = number_format($v['amount'],2);
                    $row[] = '未开票';
                    $row[] = '';
                    $row[] = '';
                    $row[] = $v['supplier_id'];
                    $row[] = $v['supplier_id_en'];
                    $row[] = cdVal($v['our_company']);
                    $row[] = cdVal($v['payment_company']);
                    $row[] = $v['prepared_by'];
                    $row[] = $v['procurement_date'];
                    $row[] = $v['seller'];
                    $row[] = $v['order_remark'];
                    fputcsv($fp, $row);
                }
                $invoiced_money = 0;
            }
        }
    }

    public function invoice_info() {
        $relevance_id   = I('request.relevance_id');
        $order          = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('a.*,t.invoice_status,t.relevance_id,t.has_invoice_unconfirmed,t.order_status,tb_con_division_our_company.invoice_person_charge_by,t.payment_status')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_ms_cmn_cd AS our_cd on our_cd.CD = a.our_company')
            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=a.our_company')

            ->where(['relevance_id'=>$relevance_id])
            ->find();
        $goods = M('goods_information','tb_pur_')->where(['relevance_id'=>$relevance_id])->select();
        $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes'],['spu_name'=>'goods_name','attributes'=>'goods_attribute']);
        $goods_r = [];
        foreach ($goods as $v) {
            $goods_r[$v['information_id']] = $v;
        }
        $order['goods'] = $goods_r;

        $invoice = M('invoice','tb_pur_')
            ->where(['relevance_id'=>$relevance_id])
            ->select();
        foreach ($invoice as $k => $v) {
            $invoice[$k]['invoice_no']  = json_decode($v['invoice_no'],true);
            $invoice[$k]['goods']       = M('invoice_goods','tb_pur_')->where(['invoice_id'=>$v['id']])->select();
        }

        $order = CodeModel::autoCodeOneVal($order, ['amount_currency']);
        $order['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$order['payment_status']];
        //计算在途发票金额
        $this->on_way_invoice_amount = (new PurService())->getOnWayInvoiceAmount($relevance_id)[$relevance_id];
        $order['is_marking_end_billing'] = $this->checkIsMarkingEndBilling($relevance_id);
        $this->order            = $order;
        $this->invoice          = $invoice;
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->display();
    }

    public function checkIsMarkingEndBilling($relevance_id)
    {
        $Model = new Model();
        $where['relevance_id'] = $relevance_id;
        $mark_invoiciong_count = $Model->table('tb_pur_action_log')
            ->where($where)
            ->where("info = '发票标记完结撤回' OR info = '标记开票完结'", null, true)
            ->count();
        if (0 !== $mark_invoiciong_count % 2) {
            return 1;
        }
        return 0;
    }

    public function invoice_add() {
        if(IS_POST) {
            $data = $_POST;
            $save_order['invoice_type']             = $data['invoice_type'];
            $save_order['tax_rate']                 = $data['tax_rate'];
            $save_order['supplier_invoice_title']   = $data['invoice_title'];
            M()->startTrans();
            $order = M('relevance_order','tb_pur_')
                ->alias('t')
                ->lock(true)
                ->field('t.order_id,t.has_invoice_unconfirmed,a.procurement_number,order_status')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->find();
            if($order['has_invoice_unconfirmed'] != 0) {
                M()->rollback();
                $this->error(L('只有无待处理发票才可新增'));
            }
            if($order['order_status'] == 'N001320500') {
                M()->rollback();
                $this->error(L('采购订单已取消'));
            }
            $res_order = M('order_detail','tb_pur_')
                ->where(['order_id'=>$order['order_id']])
                ->save($save_order);
            if($res_order === false) {
                M()->rollback();
                $this->error(L('发票添加失败:发票信息更新失败'));
            }
            $res_relevance = M('relevance_order','tb_pur_')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->save(['has_invoice_unconfirmed'=>1]);
            if($res_relevance === false) {
                M()->rollback();
                $this->error(L('发票添加失败:更新发票状态失败'));
            }
            $pre_action_no = M('invoice','tb_pur_')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->order('id desc')
                ->getField('action_no');
            if($pre_action_no) {
                $no = str_pad(explode('-',$pre_action_no)[1]+1,3,'0',STR_PAD_LEFT);
            }else {
                $no = '001';
            }
            $i_no       = $data['invoice_no'];
            $scan       = $data['scan'];
            $invoice_no = [];
            foreach ($i_no as $k => $v) {
                $invoice_no[] = [
                    'no'    => $v,
                    'scan'  => $scan[$k]
                ];
            }
            $invoice['invoice_title']   = $data['invoice_title'];
            $invoice['invoice_no']      = json_encode($invoice_no);
            $invoice['action_no']       = $order['procurement_number'].'-'.$no;
            $invoice['invoice_money']   = $data['invoice_money'];
            $invoice['relevance_id']    = $data['relevance_id'];
            $invoice['invoice_type']    = $data['invoice_type'];
            $invoice['tax_rate']        = $data['tax_rate'];
            $invoice['remark']          = $data['remark'];
            $invoice['create_user']     = session('m_loginname');
            $invoice['create_time']     = date('Y-m-d H:i:s');
            $invoice_id                 = M('invoice','tb_pur_')->add($invoice);
            if(!$invoice_id) {
                M()->rollback();
                $this->error(L('发票添加失败'));
            }
            $invoice_goods  = [];
            $invoice_name   = $data['invoice_name'];
            $invoice_money  = $data['invoice_money_g'];
            $valuation_unit = $data['valuation_unit'];
            foreach ($invoice_name as $k => $v) {
                $goods['information_id']            = $k;
                $goods['invoice_name']              = $v;
                $goods['invoice_money']             = $invoice_money[$k];
                $goods['invoice_id']                = $invoice_id;
                $invoice_goods[]                    = $goods;
                $save_information['valuation_unit'] = $valuation_unit[$k];
                $save_information['invoice_name']   = $invoice_name[$k];
                $res_information            = M('goods_information','tb_pur_')
                    ->where(['information_id'=>$k])
                    ->save($save_information);
                if($res_information === false) {
                    M()->rollback();
                    $this->error(L('添加发票失败:更新商品信息失败'));
                }
            }
            $res_goods = M('invoice_goods','tb_pur_')->addAll($invoice_goods);
            if($res_goods === false) {
                M()->rollback();
                $this->error(L('发票添加失败:添加开票商品信息失败'));
            }
            (new TbPurActionLogModel())->addLog($data['relevance_id']);
            M()->commit();
            $this->success(L('保存成功'));
        }else {
            $relevance_id   = I('request.relevance_id');
            $un_confirm_invoice_num = M('invoice','tb_pur_')
                ->where(['relevance_id'=>$relevance_id,'status'=>0])
                ->count();
            if($un_confirm_invoice_num > 0) {
                $this->error(L('有发票未确认，请将所有发票确认后再添加'));
            }
            $order          = M('relevance_order','tb_pur_')
                ->alias('t')
                ->field('a.*,t.invoice_status,t.relevance_id,t.payment_status')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['relevance_id'=>$relevance_id])
                ->find();
            $goods = M('goods_information','tb_pur_')
                ->where(['relevance_id'=>$relevance_id])
                ->select();
            $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes'],['spu_name'=>'goods_name','attributes'=>'goods_attribute']);
            $goods_r = [];
            foreach ($goods as $v) {
                $goods_r[$v['information_id']] = $v;
            }
            $order['goods'] = $goods_r;

            $order = CodeModel::autoCodeOneVal($order, ['amount_currency']);
            $order['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$order['payment_status']];
            //计算在途发票金额
            $this->on_way_invoice_amount = (new PurService())->getOnWayInvoiceAmount($relevance_id)[$relevance_id];

            $cmn_m                  = new TbMsCmnCdModel();
            $this->valuation_unit   = $cmn_m->getCdY(TbMsCmnCdModel::$valuation_unit_cd_pre);
            $this->invoice_type     = $cmn_m->getCdY(TbMsCmnCdModel::$invoice_type_cd_pre);
            $this->tax_rate         = $cmn_m->getCdY(TbMsCmnCdModel::$tax_rate_cd_pre);
            $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
            $this->order            = $order;
            $this->display();
        }
    }



    public function invoice_edit() {
        if(IS_POST) {
            $data = $_POST;
            $save_order['invoice_type']             = $data['invoice_type'];
            $save_order['tax_rate']                 = $data['tax_rate'];
            $save_order['supplier_invoice_title']   = $data['invoice_title'];
            M()->startTrans();
            $invoice_status = M('invoice','tb_pur_')
                ->lock(true)
                ->where(['id'=>$data['id']])
                ->getField('status');
            if($invoice_status != 2) {
                M()->rollback();
                $this->error(L('只有退回的发票可以编辑'));
            }
            $res_relevance = D('TbPurRelevanceOrder')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->save(['has_invoice_unconfirmed'=>1]);
            if($res_relevance === false) {
                M()->rollback();
                $this->error('更新采购单发票代办状态失败');
            }
            $order = D('TbPurRelevanceOrder')
                ->alias('t')
                ->field('t.order_id,a.procurement_number')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->find();
            $res_order = M('order_detail','tb_pur_')
                ->where(['order_id'=>$order['order_id']])
                ->save($save_order);
            if($res_order === false) {
                M()->rollback();
                $this->error(L('发票添加失败:发票信息更新失败'));
            }
            $i_no       = $data['invoice_no'];
            $scan       = $data['scan'];
            $invoice_no = [];
            foreach ($i_no as $k => $v) {
                $invoice_no[] = [
                    'no'    => $v,
                    'scan'  => $scan[$k]
                ];
            }
            $invoice['invoice_title']   = $data['invoice_title'];
            $invoice['remark']          = $data['remark'];
            $invoice['invoice_no']      = json_encode($invoice_no);
            $invoice['invoice_money']   = $data['invoice_money'];
            $invoice['relevance_id']    = $data['relevance_id'];
            $invoice['invoice_type']    = $data['invoice_type'];
            $invoice['tax_rate']        = $data['tax_rate'];
            $invoice['create_user']     = session('m_loginname');
            $invoice['create_time']     = date('Y-m-d H:i:s');
            $invoice['status']          = TbPurInvoiceModel::$status_unconfirmed;
            $invoice_res                = M('invoice','tb_pur_')
                ->where(['id'=>$data['id']])
                ->save($invoice);
            if(!$invoice_res) {
                M()->rollback();
                $this->error(L('发票编辑失败'));
            }
            $invoice_goods  = [];
            $invoice_name   = $data['invoice_name'];
            $invoice_money  = $data['invoice_money_g'];
            $valuation_unit = $data['valuation_unit'];
            $goods_id       = $data['goods_id'];
            foreach ($invoice_name as $k => $v) {
                $goods['invoice_name']              = $v;
                $goods['invoice_id']                = $data['id'];
                $goods['information_id']            = $k;
                $goods['invoice_money']             = $invoice_money[$k];
                $goods['valuation_unit']            = $valuation_unit[$k];
                $invoice_goods[]                    = $goods;
                $save_information['valuation_unit'] = $valuation_unit[$k];
                $save_information['invoice_name']   = $invoice_name[$k];
                $res_information            = M('goods_information','tb_pur_')
                    ->where(['information_id'=>$k])
                    ->save($save_information);
                if($res_information === false) {
                    M()->rollback();
                    $this->error(L('编辑发票失败:更新商品信息失败'));
                }
                if($goods_id[$k]) {
                    $res_goods = M('invoice_goods','tb_pur_')
                        ->where(['id'=>$goods_id[$k]])
                        ->save($goods);
                }else {
                    $res_goods = M('invoice_goods','tb_pur_')->add($goods);
                }
                if($res_goods === false) {
                    ELog::add(['info'=>'采购发票商品保存失败:'.M()->getDbError(),'request'=>$_POST,'goods'=>$goods],Elog::ERR);
                    M()->rollback();
                    $this->error(L('编辑发票失败:发票商品编辑失败'));
                }
            }
            (new TbPurActionLogModel())->addLog($data['relevance_id']);
            M()->commit();
            $this->success(L('保存成功'));
        }else {
            $id = I('request.id');
            $invoice = M('invoice','tb_pur_')
                ->where(['id'=>$id])
                ->find();
            $order = M('relevance_order','tb_pur_')
                ->alias('t')
                ->field('a.*,t.invoice_status,t.relevance_id,t.payment_status')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['relevance_id'=>$invoice['relevance_id']])
                ->find();
            $goods = M('goods_information','tb_pur_')
                ->alias('t')
                ->field('t.*,a.invoice_money,a.id')
                ->join('left join tb_pur_invoice_goods a on a.information_id=t.information_id and a.invoice_id='.$id)
                ->where(['relevance_id'=>$invoice['relevance_id']])
                ->select();
            $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes'],['spu_name'=>'goods_name','attributes'=>'goods_attribute']);
            $invoice['invoice_no'] = json_decode($invoice['invoice_no'],true);
            $goods_r = [];
            foreach ($goods as $v) {
                $goods_r[$v['information_id']] = $v;
            }
            $order['goods'] = $goods_r;

            $order = CodeModel::autoCodeOneVal($order, ['amount_currency']);
            $order['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$order['payment_status']];
            //计算在途发票金额
            $this->on_way_invoice_amount = (new PurService())->getOnWayInvoiceAmount($invoice['relevance_id'])[$invoice['relevance_id']];

            $cmn_m                  = new TbMsCmnCdModel();
            $this->valuation_unit   = $cmn_m->getCdY(TbMsCmnCdModel::$valuation_unit_cd_pre);
            $this->invoice_type     = $cmn_m->getCdY(TbMsCmnCdModel::$invoice_type_cd_pre);
            $this->tax_rate         = $cmn_m->getCdY(TbMsCmnCdModel::$tax_rate_cd_pre);
            $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
            $this->order            = $order;
            $this->invoice          = $invoice;
            $this->display('invoice_add');
        }
    }

    public function invoice_confirm() {
        if(IS_POST) {
            $data       = $_POST;
            $invoice = M('invoice','tb_pur_')
                ->field('relevance_id,status')
                ->where(['id'=>$data['id']])
                ->find();
            if(!$invoice) {
                $this->error(L('发票不存在'));
            }else {
                if($invoice['status']) $this->error(L('保存失败：此发票已经确认过'));
            }
            $goods  = M('goods_information','tb_pur_')
                ->alias('t')
                ->field('t.*,a.invoice_money')
                ->join('left join tb_pur_invoice_goods a on a.information_id=t.information_id and a.invoice_id='.$data['id'])
                ->where(['relevance_id'=>$invoice['relevance_id']])
                ->select();
            M()->startTrans();
            $order_status = D('TbPurRelevanceOrder')->lock(true)->where(['relevance_id'=>$invoice['relevance_id']])->getField('order_status');
            if($order_status == 'N001320500') {
                M()->rollback();
                $this->error(L('采购订单已取消'));
            }
            $save_invoice = [
                'status'        => 1,
                'confirm_user'  => session('m_loginname'),
                'confirm_time'  => date('Y-m-d H:i:s')
            ];
            $res_invoice = M('invoice','tb_pur_')
                ->where(['id'=>$data['id']])
                ->save($save_invoice);
            $uninvoiced_money_total = 0;
            foreach ($goods as $v) {
                if($v['invoice_money']) {
                    $uninvoiced_money       = $v['goods_money']-$v['invoiced_money']-$v['invoice_money'];
                    $res_information = M('goods_information','tb_pur_')
                        ->where(['information_id'=>$v['information_id']])
                        ->setInc('invoiced_money',$v['invoice_money']);
                    if($res_information === false) {
                        M()->rollback();
                        $this->error(L('更新商品已开票金额失败'));
                    }
                }else {
                    $uninvoiced_money = $v['goods_money']-$v['invoiced_money'];
                }
                if($uninvoiced_money < 0) {
                    $uninvoiced_money = 0;
                }
                $uninvoiced_money_total += $uninvoiced_money;
            }
            if(number_format($uninvoiced_money_total,2) == 0) {
                $invoice_status = 2;
            }else {
                $invoice_status = 1;
            }
            $res_order = M('relevance_order','tb_pur_')
                ->where(['relevance_id'=>$data['relevance_id']])
                ->save(['invoice_status'=>$invoice_status,'has_invoice_unconfirmed'=>0]);
            if($res_invoice === false || $res_order === false) {
                M()->rollback();
                $this->error(L('保存失败'));
            }
            (new TbPurActionLogModel())->addLog($data['relevance_id']);
            M()->commit();
            $this->success(L('保存成功'));
        }else {
            $id         = I('request.id');
            $invoice    = M('invoice','tb_pur_')
                ->where(['id'=>$id])
                ->find();
            $goods      = M('invoice_goods','tb_pur_')
                ->alias('t')
                ->field('t.invoice_name invoiced_name,t.invoice_money,a.*')
                ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                ->where(['invoice_id'=>$id])
                ->select();
            $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes'],['spu_name'=>'goods_name','attributes'=>'goods_attribute']);
            $order      = M('relevance_order','tb_pur_')
                ->alias('t')
                ->field('a.*,t.invoice_status,t.relevance_id,t.payment_status')
                ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['relevance_id'=>$invoice['relevance_id']])
                ->find();

            $order = CodeModel::autoCodeOneVal($order, ['amount_currency']);
            $order['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$order['payment_status']];
            //计算在途发票金额
            $this->on_way_invoice_amount = (new PurService())->getOnWayInvoiceAmount($invoice['relevance_id'])[$invoice['relevance_id']];

            $invoice['invoice_no']  = json_decode($invoice['invoice_no'],true);
            $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
            $this->invoice          = $invoice;
            $this->order            = $order;
            $this->goods            = $goods;
            $this->display();
        }
    }

    public function invoice_detail() {
        $id         = I('request.id');
        $invoice    = M('invoice','tb_pur_')
            ->where(['id'=>$id])
            ->find();
        $invoice['invoice_no'] = json_decode($invoice['invoice_no'],true);
        $goods      = M('invoice_goods','tb_pur_')
            ->alias('t')
            ->field('t.invoice_name invoiced_name,t.invoice_money,a.*')
            ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
            ->where(['invoice_id'=>$id])
            ->select();
        $goods = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes'],['spu_name'=>'goods_name','attributes'=>'goods_attribute']);
        $order      = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('a.*,t.invoice_status,t.relevance_id,t.payment_status')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->where(['relevance_id'=>$invoice['relevance_id']])
            ->find();

        $order = CodeModel::autoCodeOneVal($order, ['amount_currency']);
        $order['payment_status'] = TbPurRelevanceOrderModel::$payment_status[$order['payment_status']];
        //计算在途发票金额
        $this->on_way_invoice_amount = (new PurService())->getOnWayInvoiceAmount($invoice['relevance_id'])[$invoice['relevance_id']];

        $this->invoice          = $invoice;
        $this->order            = $order;
        $this->goods            = $goods;
        $this->purchase_type    = TbPurOrderDetailModel::$purchase_type;
        $this->display();
    }

    public function file_upload() {
        $fd = new FileUploadModel();
        $res = $fd->uploadFileArr();
        if ($res) {
            $info = $res[0];
            $this->ajaxReturn(1,$info,1);
        } else {
            $this->error($fd->error, '', true);
        }
    }

    public function purchase_log() {
        $id = I('request.id');
        $this->id   = $id;
        $this->list = M('action_log','tb_pur_')->where(['relevance_id'=>$id])->order('id desc')->select();
        $this->display();
    }

    public function ce_tool() {
        $this->display();
    }

    public function ce_tool_base_data() {
        $model = new TbMsCmnCdModel();
        $info['purchase_team']  = $model->getCdY($model::$purchase_team_cd_pre);
        $info['sell_team']      = $model->getCdY($model::$sell_team_cd_pre);
        $info['currency']       = $model->getCdY($model::$currency_cd_pre);
        $info['currency_rate']  = (new TbMsXchrModel())->currency_rates();
        $info['pay_percent']    = $model->getCdY($model::$payment_percent_cd_pre);
        $info['sales_staff']    = B2bModel::get_user();
        $this->ajaxReturn($info);
    }

    public function ce_tool_email() {
        $this->info     = $info = I('post.');
        $email          = new SMSEmail();
        $email_address  = explode(',',$info['receive_address']);
        if($info['cc_address']) $cc_address = explode(',',$info['cc_address']);
        $cc_address     []= $_SESSION['m_loginname'].'@gshopper.com';
        $email_title    = 'Purchase Application - CE Report';
        $email_content  = $this->fetch();
        $res = $email->sendEmail($email_address,$email_title,$email_content,$cc_address);
        if($res) {
            $this->success('发送成功');
        }else {
            $this->error('发送失败：'.$email->getError());
        }
    }

    public function special_offer() {
        $this->display();
    }

    public function special_offer_list() {
        //搜索条件
        $param = $this->params();
        $where = [];
        if($param['supplier'])
            $where['supplier'] = ['like','%'.html_entity_decode($param['supplier']).'%'];
        if($param['purchase_staff'])
            $where['purchase_staff'] = ['like','%'.$param['purchase_staff'].'%'];
        if($param['purchase_team'])
            $where['purchase_team'] = $param['purchase_team'];
        if($param['start_time'] && $param['end_time']) {
            $where['create_time'] = ['between',[$param['start_time'].' 00:00:00',$param['end_time'].' 23:59:59']];
        }elseif($param['start_time']) {
            $where['create_time'] = ['egt',$param['start_time'].' 00:00:00'];
        }elseif($param['end_time']) {
            $where['create_time'] = ['elt',$param['end_time'].' 23:59:59'];
        }
        $param['list_rows'] ? '' : $param['list_rows'] = 20;

        $model = new TbPurSpecialOfferModel();
        $count = $model->where($where)->count();
        import('ORG.Util.Page');
        $page = new Page($count,$param['list_rows']);
        $list = $model->field('id,supplier,a.CD_VAL purchase_team,purchase_staff,create_time')
            ->alias('t')
            ->join('left join tb_ms_cmn_cd a on a.CD=t.purchase_team')
            ->where($where)
            ->order('id desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        $this->ajaxReturn(0,['list'=>$list,'page'=>['total_rows'=>$count]],1);
    }

    public function special_offer_init() {
        $cmn = new TbMsCmnCdModel();
        $base_data['has_invoice']               = TbPurSpecialOfferModel::$has_invoice;
        $base_data['invoice_type']              = $cmn->getCdY($cmn::$invoice_type_cd_pre);
        $base_data['currency']                  = $cmn->getCdY($cmn::$currency_cd_pre);
        $base_data['tax_rate']                  = $cmn->getCdY($cmn::$tax_rate_cd_pre);
        $base_data['purchase_team']             = $cmn->getCdY($cmn::$purchase_team_cd_pre);
        $base_data['purchase_staff']            = B2bModel::get_user();
        $base_data['authorization_and_link']    = TbPurSpecialOfferGoodsModel::$authorization_and_link;
        $this->ajaxReturn(0,$base_data,1);
    }

    public function special_offer_search_goods() {
        $gsku = I('request.gsku');
        $res = D('Goods','Logic')->getGoods($gsku);
        if($res) {
            $guds['goods_name']         = $res['goods_name'];
            $guds['sku']                = $res['sku_id'];
            $guds['gsku']               = $gsku;
            $guds['goods_attribute']    = $res['goods_attribute'];
            $guds['gudsImgCdnAddr']     = $res['guds_img_cdn_addr'];
            $this->ajaxReturn($guds,'',1);
        }else {
            $this->ajaxReturn('','商品信息获取失败',0);
        }
    }

    public function special_offer_import_goods() {
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
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $goods['gsku']          = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $goods['currency']      = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $goods['price']         = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $goods['number']        = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            $goods['auth_and_link'] = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue());
            $goods['remark']        = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());
            $goods_arr              []= $goods;
        }
        $special_offer_logic = D('SpecialOffer','Logic');
        $res = $special_offer_logic->importGoods($goods_arr);
        if($res) {
            $this->ajaxReturn($special_offer_logic->imported_goods,'导入成功',1);
        }else {
            $this->ajaxReturn($special_offer_logic->imported_goods,'导入失败',0);
        }
    }

    public function get_address() {
        $pid        = I('request.pid');
        $address    = (new TbCrmSiteModel())->getChildrenAddress($pid);
        $this->ajaxReturn(0,$address,1);
    }

    public function special_offer_save() {
        $data                   = $_POST;
        $special_offer_m        = new TbPurSpecialOfferModel();
        $special_offer_goods_m  = new TbPurSpecialOfferGoodsModel();
        M()->startTrans();
        if(!$data['id']) {
            $data['create_user'] = $_SESSION['m_loginname'];
            if($special_offer_m->create($data)) {
                $id = $special_offer_m->add();
                if(!$id) {
                    M()->rollback();
                    $this->error('保存失败:基础信息保存失败');
                }
                $data['id'] = $id;
                $goods      = $data['goods'];
                $fields = $special_offer_goods_m->getDbFields();
                foreach ($goods as  &$v) {
                    foreach ($v as $key => $value) {
                        if(!in_array($key,$fields)) {
                            unset($v[$key]);
                        }
                    }
                    $v['special_offer_id'] = $id;
                }
                $res_goods = $special_offer_goods_m->addAll($goods);
                if(!$res_goods) {
                    M()->rollback();
                    $this->error('保存失败:商品保存失败');
                }
            }else {
                M()->rollback();
                $this->error($special_offer_m->getError());
            }
        }else {
            if($special_offer_m->create()) {
                $res = $special_offer_m->save();
                if ($res === false) {
                    M()->rollback();
                    $this->error('保存失败:基础信息保存失败');
                }
                $res_del_goods = $special_offer_goods_m->where(['special_offer_id' => $data['id']])->delete();
                if ($res_del_goods === false) {
                    M()->rollback();
                    $this->error('保存失败:商品删除失败');
                }
                $goods = $data['goods'];
                $fields = $special_offer_goods_m->getDbFields();
                foreach ($goods as &$v) {
                    foreach ($v as $key => $value) {
                        if(!in_array($key,$fields)) {
                            unset($v[$key]);
                        }
                    }
                    $v['special_offer_id'] = $data['id'];
                    ksort($v);
                }
                $res_goods = $special_offer_goods_m->addAll($goods);
                if (!$res_goods) {
                    M()->rollback();
                    $this->error('保存失败:商品保存失败');
                }
            }else {
                M()->rollback();
                $this->error($special_offer_m->getError());
            }
        }
        M()->commit();
        /*
        $this->info             = $data;
        $this->currency         = (new TbMsCmnCdModel())->getCd(TbMsCmnCdModel::$currency_cd_pre);
        $this->auth_and_link    = TbPurSpecialOfferGoodsModel::$authorization_and_link;
        $email_content          = $this->fetch('special_offer_notice_email');
        $email                  = new SMSEmail();
        $email_title            = 'Special Offer Information';
        $email_address          = C('special_offer_notice_email');
        $res_email = $email->sendEmail($email_address,$email_title,$email_content);
        */
        $this->success('保存成功'/*.$res_email?'':'，但是邮件发送失败'*/);
    }

    public function special_offer_detail() {
        $id     = I('request.id');
        $detail = (new TbPurSpecialOfferModel())->detail($id,true);
        $detail['goods'] = (new TbPurSpecialOfferGoodsModel())->getGoods($id);
        $this->ajaxReturn(0,$detail,1);
    }


    public function getCds() {
        $type   = I('request.cd_type');
        $types  = explode(',',$type);
        $cd_m   = new TbMsCmnCdModel();
        $data   = [];
        foreach ($types as $v) {
            $key = $v.'_cd_pre';
            if(isset($cd_m::$$key)) {
                $data[$v] = $cd_m->getCd($cd_m::$$key);
            }
        }
        $this->ajaxReturn(0,$data,1);
    }

    public function export_onway() {
        set_time_limit(0);
        $fileName = '在途金额' . time() . '.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $relevance_order = M('relevance_order', 'tb_pur_'); //实例化订单关联总表
        $params = I('request.');
        !empty($params['start_time']) ? $where['sou_time'] = array('EGT',$params['start_time']) : '';
        !empty($params['end_time']) ? $where['t.sou_time'] = array('ELT',$params['end_time']) : '';
        $purchase_info = $relevance_order
            ->alias('t')
            ->field(' t.relevance_id,t.sou_time,a.procurement_number,a.amount_currency,b.CD_VAL our_company')
            ->join("left join tb_pur_order_detail a on a.order_id = t.order_id")
            ->join("left join tb_ms_cmn_cd b on b.CD = a.our_company")
            ->where($where)
            ->select();

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF) . chr(0xBB) . chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['采购单号','实际付款金额','入库金额','我方公司'];

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);

        foreach ($purchase_info as $v) {
            $exchage_rate = exchangeRate(cdVal($v['amount_currency']),$purchase_info['sou_time']);
            $line = [];
            $line[] = $v['procurement_number'];
            $line[] = round(M('payment','tb_pur_')->where(['relevance_id'=>$v['relevance_id']])->sum('amount_paid')*$exchage_rate,2);
            $line[] = M('warehouse_goods','tb_pur_')
                ->alias('t')
                ->join('left join tb_pur_ship_goods a on a.id=t.ship_goods_id')
                ->join('left join tb_pur_goods_information b on b.information_id=a.information_id')
                ->where(['b.relevance_id'=>$v['relevance_id']])
                ->sum('t.warehouse_number*b.unit_price')*$exchage_rate;
            $line[] = $v['our_company'];
            fputcsv($fp, $line);
        }
    }

    public function ship_end() {
        $relevance_id   = I('request.relevance_id');
        $logic          = D('Purchase/Ship','Logic');
        $res            = $logic->shipEnd($relevance_id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function reverse_ship_end() {
        $relevance_id   = I('request.relevance_id');
        $logic          = D('Purchase/Ship','Logic');
        $res            = $logic->reverseShipEnd($relevance_id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function warehouse_end() {
        $ship_id    = I('request.ship_id');
        $logic      = D('Purchase/Warehouse','Logic');
        $res        = $logic->warehouseEnd($ship_id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function invoice_end() {
        $relevance_id   = I('request.relevance_id');
        $logic          = D('Purchase/Invoice','Logic');
        $res            = $logic->invoiceEnd($relevance_id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function invoiceEndWithdraw() {
        $relevance_id   = I('request.relevance_id');
        $logic          = D('Purchase/Invoice','Logic');
        $res            = $logic->invoiceEndWithdraw($relevance_id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function order_cancel() {
        $relevance_id   = I('request.relevance_id');
        $cancel_voucher = I('request.cancel_voucher','','');
        $cancel_reason  = trim(I('request.cancel_reason'));
        $logic          = D('Purchase/Relevance','Logic');
        $res            = $logic->orderCancel($relevance_id,$cancel_reason,$cancel_voucher);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    public function is_reserved() {
        $relevance_id   = I('request.relevance_id');
        $logic          = D('Purchase/Relevance','Logic');
        $is_reserved    = $logic->isReserved($relevance_id);
        $this->ajaxSuccess(['is_reserved'=>$is_reserved]);
    }

    public function ship_revoke() {
        $id             = I('request.id');
        $logic          = D('Purchase/Ship','Logic');
        $res            = $logic->shipRevoke($id);
        if($res) {
            $this->ajaxSuccess();
        }else {
            $this->ajaxError([],$logic->getError());
        }
    }

    /******************start:触发操作生成采购抵扣金或应付金生成相关*******************/
    public function get_operation_amount()
    {
        try {
            $res = DataModel::$success_return;
            $res['data'] = (new PurService())->getOperationAmount($_POST);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            $res['data'] = $request_data;
        }
        $this->ajaxReturn($res);
    }
    /******************end:触发操作生成采购抵扣金或应付金生成相关*******************/


    // 批量处理采购单中没有营业执照的采购单，需要在供应商表中新增记录
    public function batchDealHistoryPurOrder()
    {
        try {
            // 数据处理二期

            //1.历史此类供应商记录创建时间=最早出现这个供应商的线上采购采购单的创建时间；min create_time

            //2.历史此类供应商记录创建人=最早出现这个供应商的线上采购采购单的采购同事；(bbm_admin.huaming ->bbm_admin.M_ID)

            //3.历史此类供应商的审核状态全部设置为【无需审核】。
            $crmSpSupplierModel = M('crm_sp_supplier','tb_');
            $purOrderDetailModel = M('pur_order_detail', 'tb_');
            $purRelevanceOrderModel = M('pur_relevance_order', 'tb_');
            $adminModel = M('admin', 'bbm_');

            $res = $crmSpSupplierModel->where(['SP_CHARTER_NO_TYPE' => '1'])->select();
            foreach ($res as $key => $value) {
                // 创建时间
                $order_id = ''; $create_time = ''; $create_name = '';
                $order_id = $purOrderDetailModel->where(['supplier_id' => $value['SP_NAME']])->min('order_id');
                $create_name = $purRelevanceOrderModel->where(['order_id' => $order_id])->getField('prepared_by');
                $search_name = 'huaming';
                if (strpos($create_name, '.') !== false) { // 部分采购单是以英文名如Weslis.Li存进，而不是花名
                    $search_name = 'M_NAME';
                }
                $saveData['CREATE_USER_ID'] = $adminModel->where([$search_name => $create_name])->getField('M_ID');
                $saveData['CREATE_TIME'] = $purRelevanceOrderModel->where(['order_id' => $order_id])->getField('prepared_time');
                $saveData['AUDIT_STATE'] = '3';
                $re = $crmSpSupplierModel->where(['ID' => $value['ID']])->save($saveData);
                if (false === $re) {
                    echo "供应商{$value['supplier_id']}更新失败";
                    echo "<br>";
                    throw new \Exception(L('生成供应商数据更新失败'));
                }
                unset($saveData);
            }
            $crmSpSupplierModel->commit();
            echo  "新增成功";
            echo "<br>";die;
            /*// 先获取采购单中，营业执照为空的，且供应商不为空，供应商名称(去重)
            // 根据该名称，去供应商表查询，没有则添加，有的话则继续
            $res = M()->query('SELECT supplier_id FROM `tb_pur_order_detail`  WHERE `sp_charter_no` IS NULL AND `supplier_id` IS NOT NULL group by supplier_id');
            // p($res);die;
            $crmSpSupplierModel = M('crm_sp_supplier','tb_');
            $crmSpSupplierModel->startTrans();
            foreach ($res as $key => $value) {
                $re = '';
                $re = $crmSpSupplierModel->where(['SP_NAME' => $value['supplier_id']])->getField('ID');
                if ($re) {
                    continue;
                }
                $addSupplierData = [];
                $addSupplierData['SP_NAME'] = $value['supplier_id'];
                $addSupplierData['COPANY_TYPE_CD'] = 'N001190800';
                $addSupplierData['SP_CHARTER_NO_TYPE'] = '1';
                $addSupplierData['SP_STATUS'] = '1';
                $addSupplierData['DEL_FLAG'] = '1';
                $supplier_id = $crmSpSupplierModel->add($addSupplierData);
                if (!$supplier_id) {
                    echo "供应商{$value['supplier_id']}新增失败";
                    echo "<br>";
                    throw new \Exception(L('生成供应商数据失败'));
                }
                $crmSpSupplierModel->commit();
                echo  "新增成功";
                echo "<br>";
            }*/
        } catch (Exception $exception) {
            $crmSpSupplierModel->rollback();
            $res = $this->catchException($exception);
        }
    }

    /******************start:采购抵扣金相关*******************/
    //算作/使用抵扣金
    public function addDeductionAmount() {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model = new Model();
            $model->startTrans();
            (new PurService())->addDeductionAmount($request_data, $model);
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function deductionList() {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = (new PurService())->getDeductionList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function deductionDetail() {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = (new PurService())->getDeductionDetail($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function searchSuppliers()
    {
        $name = DataModel::getDataNoBlankToArr()['search']['supplier_name'];
        $conditions['SP_NAME']          = array('like','%'. $name.'%');
        $conditions['SP_NAME_EN']       = array('like','%'. $name.'%');
        $conditions['SP_RES_NAME_EN']   = array('like','%'. $name.'%');
        $conditions['_logic']           = 'or';
        $where['_complex']              = $conditions;
        $where['DATA_MARKING']          = 0;
        $where['SP_CHARTER_NO']         = array('neq','');

        $data = M('_crm_sp_supplier', 'tb_')->field(['ID as supplier_id, SP_NAME as supplier_name'])->where($where)->select();
        $res = DataModel::$success_return;
        $res['code'] = 200;
        $res['data'] = $data;
        $this->ajaxReturn($res);
    }

    /**
     * 算作/使用抵扣金撤回
     */
    public function cancelDeductionAmount() {
        try {
            $deduction_detail_id = DataModel::getDataNoBlankToArr()['deduction_detail_id'];
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model = new Model();
            $model->startTrans();
            // #10241 抵扣金支持扣减为负数(算作抵扣金的取消操作，也需要扣减抵扣金)
            (new PurService())->cancelDeductionAmount($deduction_detail_id, $model, true);
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 导入抵扣金
     */
    public function importDeductionAmount() {
        try {
            set_time_limit(0);
            header("content-type:text/html;charset=utf-8");

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model = new Model();

            $filePath = $_FILES['file']['tmp_name'];
            vendor("PHPExcel.PHPExcel");
            $objPHPExcel = new PHPExcel();
            //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    throw new \Exception(L('请上传EXCEL文件'));
                }
            }
            //读取Excel文件
            $PHPExcel = $PHPReader->load($filePath);
            //读取excel文件中的第一个工作表
            $sheet = $PHPExcel->getSheet(0);
            //取得最大的行号
            $allRow = $sheet->getHighestRow();

            $purService = new PurService();
            $model->startTrans();
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $order_no = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
                $deduction_money = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
                $deduction_type = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
                $order = $model->table('tb_pur_order_detail')
                    ->field('procurement_number,supplier_id,
                        supplier_id_en,sp_charter_no,amount_currency,
                        our_company,tb_ms_cmn_cd.CD_VAL as our_company_name, supplier_new_id'
                    )
                    ->join('left join tb_ms_cmn_cd on tb_pur_order_detail.our_company = tb_ms_cmn_cd.CD')
                    ->where(['procurement_number' => $order_no])
                    ->find();
                if (empty($order)) {
                    throw new \Exception(L('未找到采购单，'.$order_no));
                }
                $data = [
                    'sp_charter_no' => $order['sp_charter_no'],
                    'supplier_name_cn' => $order['supplier_id'],
                    'supplier_name_en' => $order['supplier_id_en'],
                    'our_company_cd' => $order['our_company'],
                    'our_company_name' => $order['our_company_name'],
                    'deduction_currency_cd' => $order['amount_currency'],
                    'deduction_type_cd' => $deduction_type,
                    'deduction_amount' => $deduction_money,
                    'order_no' => $order_no,
                    'supplier_new_id' => $order['supplier_new_id'],
                    'turnover_type' => 2
                ];
                $purService->addDeductionAmount($data, $model);
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
    /******************end:采购抵扣金相关*******************/

    /**
     * 获取打印入库单数据
     */
    public function getInWarehouseInfo() {
        try {
            $ship_id = DataModel::getDataNoBlankToArr()['ship_id'];
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $detail = (new PurService())->getShipInfo($ship_id);
            $data = [];
            array_map(function($value) use (&$data) {
                $over_in_warehouse_num = $value['ship_number']-$value['warehouse_number']-$value['warehouse_number_broken'];
                if ($over_in_warehouse_num != 0) {
                    $data[] = [
                        'sku_id'                  => $value['information']['sku_information'],
                        'upc_id'                  => $value['information']['upc_id'],
                        'goods_name'              => $value['information']['goods_name'],
                        'goods_attribute'         => $value['information']['goods_attribute'],
                        'goods_image'             => $value['information']['goods_image'],
                        'location_code'           => $value['location'],
                        'defective_location_code' => $value['defective_location_code'],
                        'over_in_warehouse_num'   => $over_in_warehouse_num
                    ];
                }
            }, $detail['goods']);
            $res['data'] = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function paymentAuditList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new PurPaymentService())->searchPaymentAuditList($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    //付款单详情
    public function paymentAuditDetail()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $payment_audit_id = $request_data['payment_audit_id'];
            if (!$payment_audit_id) {
                throw new Exception(L('参数不全'));
            }
            $res          = DataModel::$success_return;
            $res['code']  = 200;

            //  采购应付 调拨应对 B2C退款
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_allo_payable) {
                // 调拨应付
                $data = (new ExpenseBillPaymentService())->getPaymentAuditDetail($payment_audit_id);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable){
                // B2C退款
                $data =  (new B2CPaymentService())->getPaymentAuditDetail($payment_audit_id);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                $data =  (new TransferPaymentService())->getPaymentAuditDetail($payment_audit_id);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                $data =  (new TransferPaymentService())->getPaymentAuditDetail($payment_audit_id);
            } else {
                // 采购应付
                $data =  (new PurPaymentService())->getPaymentAuditDetail($payment_audit_id);
            }
            $result['data'] = $data;
            $res['data']  = $result;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    // 处理特殊同名供应商id
    public function dealSpecialScmOrderSupplier()
    {
        $specialSupplierInfo = [
            '764' => '南京雅唐科技有限公司',
            '1005' => '上海杰士客食品有限公司',
            '414' => '沃歌（上海）品牌管理有限公司',
            '502' => '上海肯雀儿实业有限公司',
            '692' => '玉环海纳包装有限公司',
            '1392' => '山东泛亚国际货运有限公司',
            '1427' => '上海莫泰浦东南路酒店有限公司',
            '1566' => '上海楠轩信息科技有限公司',
            '869' => '利創商事株式会社'
        ];
            // '1458' => '佳悦企业有限公司',
        $orderModel = M('pur_order_detail', 'tb_');
        foreach ($specialSupplierInfo as $key => $value) {
            $whereMap = []; $saveData = [];
            $whereMap['supplier_id'] = $value;
            $saveData['supplier_new_id'] = $key;
            $res = $orderModel->where($whereMap)->save($saveData);
            if (false === $res) {
                p("{$key} - {$value},更新保存失败");
            }
        }

        echo "success";
        return false;

    }

    public function dealScmOrderSupplier()
    {
        $crmSpModel = M('crm_sp_supplier', 'tb_');
        $orderModel = M('pur_order_detail', 'tb_');
        //$map['COPANY_TYPE_CD'] = 'N001190800'; // 采购供应商类型
        $map['DATA_MARKING'] = '0'; // 供应商
        try {
            $model        = new Model();
            $model->startTrans();
            $res = $crmSpModel->field('ID,SP_NAME')->where($map)->select();
            $reData = [];
            foreach ($res as $key => $value) {
                if (!$value['SP_NAME'] || !$value['ID']) {
                    continue;
                }
                if (in_array($value['SP_NAME'], $reData)) {
                    p("供应商名称有重复，获取保存失败，{$value['ID']}-{$value['SP_NAME']}");
                    // 将原来已经更新的供应商id清空，待产品确认后再修复
                    $whereMap = [];
                    $saveData = [];
                    $whereMap['supplier_id'] = $value['SP_NAME'];
                    $saveData['supplier_new_id'] = '';
                    $res = $orderModel->where($whereMap)->save($saveData);
                    continue;
                }
                $whereMap = [];
                $saveData = [];
                $whereMap['supplier_id'] = $value['SP_NAME'];
                $saveData['supplier_new_id'] = $value['ID'];
                $res = $orderModel->where($whereMap)->save($saveData);
                if (false === $res) {
                    $lastsql = M()->_sql();
                    Logs(['$sql_print' => $lastsql], __FUNCTION__, __CLASS__);
                    throw new Exception("获取保存失败，{$value['ID']}-{$value['SP_NAME']}");
                }
                $reData[] = $value['SP_NAME'];
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
            p($res);
        }

        
    }

    //付款/出账/待出账出账
    public function paymentSubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $model        = new Model();
            $model->startTrans();
            if ($request_data) {
                $this->validatePaymentSubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $rClineVal = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            $id = M('op_order_refund', 'tb_')->where(['payment_audit_id' => $request_data['payment_audit_id']])->getField('id');
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $this->checkPaymentAuditStatus($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            //  采购应付 调拨应对 B2C退款
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_allo_payable) {
                // 调拨应付
                (new ExpenseBillPaymentService($model))->paymentSubmit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable){
                // B2C退款
                (new B2CPaymentService($model))->paymentSubmit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                (new TransferPaymentService($model))->paymentSubmit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                (new TransferPaymentService($model))->paymentSubmit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款
                (new GeneralPaymentService($model))->paymentSubmit($request_data);
            }else{
                // 采购应付
                (new PurPaymentService($model))->paymentSubmit($request_data);
            }

            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable) {
                #发送企业微信消息
                $afterSaleService = new OmsAfterSaleService();
                $afterSaleService->after_sale_refund_pass_wx_msg($id, 4);
            }
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function checkPaymentAuditStatus($request_data)
    {
        $status = ($request_data['type'] == 2) ? TbPurPaymentAuditModel::$status_no_billing : TbPurPaymentAuditModel::$status_finished;
        $db_status = M('payment_audit', 'tb_pur_')->where(['id' => $request_data['payment_audit_id']])->getField('status');
        if ($status == $db_status) {
            throw new Exception('付款单状态已更新');
        }
    }

    private function validatePaymentSubmitData($data) {
        $rules = [
            'payment_our_bank_account' => 'required',
            'pay_com_cd'               => 'required',
            'payment_audit_id'         => 'required|numeric',
            'type'                     => 'required',
            'source_cd'                => 'required|size:10',
            'pay_type'                 => 'required|size:1',
//            'trade_type_cd'            => 'required|size:10',
        ];
        $custom_attributes = [
            'payment_our_bank_account' => '付款账号',
            'pay_com_cd'               => '付款公司',
            'payment_audit_id'         => '付款id',
            'type'                     => '是否出账',
            'source_cd'                => '来源',
            'pay_type'                 => '是否通过Kyriba支付',
//            'trade_type_cd'            => '交易类型'
        ];
        // 所有付款种类开放手续费承担方式验证逻辑
        //$payment_channel = M('payment_audit', 'tb_pur_')->where(['id' => $data['payment_audit_id']])->find();
        /*if ($payment_channel['payment_channel_cd'] == 'N001000301') { //支付渠道为银行
            $rules["commission_type"] = 'required|string';
            $custom_attributes['commission_type'] = '手续费承担方式';
        }*/
        if ($data['type'] == 2) { //【是否已出账】=未出账的付款单
            $rules['no_billing.payment_currency_cd'] = 'required|string|size:10';
            $rules['no_billing.payment_amount']      = 'required|numeric';
            $rules["no_billing"]                     = 'required|array';
            $rules['payment_account_id']             = 'required|numeric';
            $custom_attributes['no_billing.payment_currency_cd'] = '付款币种';
            $custom_attributes['no_billing.payment_amount']      = '付款金额';
            $custom_attributes['payment_account_id']             = '付款账号id';
            /*if ($payment_channel['payment_channel_cd'] != 'N001000301' && $payment_channel['status'] != '1') { //支付渠道为银行 & 【支付渠道】=银行 & 【是否已出账】=未出账的付款单
                $rules['no_billing.payment_voucher']     = 'required';
                $custom_attributes['no_billing.payment_voucher']     = '付款凭证';

            }*/

        }
        if ($data['pay_type'] == 1) { //推送kyriba  交易类型 手续费承担方式 必填
            $rules["trade_type_cd"] = 'required|size:10';
            $custom_attributes["trade_type_cd"] = '交易类型';
            $rules["commission_type"] = 'required|string';
            $custom_attributes['commission_type'] = '手续费承担方式';
        }
        if ($data['type'] == 2 || $data['type'] == 3) {
            if ($data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable) {
                // 一般付款
                //$payment_channel_cd = M('payment_audit', 'tb_pur_')->where(['id' => $data['payment_audit_id']])->getField('payment_channel_cd');
                /*if ($payment_channel_cd == 'N001000301') { //支付渠道为银行
                    $rules["commission_type"] = 'required|string';
                    $custom_attributes['commission_type'] = '手续费承担方式';
                }*/
            }
        }
        //供应商为数字 我方公司为10位字符
        if (!is_numeric($data['pay_com_cd']) && strlen($data['pay_com_cd']) != 10) {
            $rules["pay_com_cd"] = 'required|size:10';
        }
        if ($data['type'] == 3) {
//            $rules['already_billing.payment_currency_cd'] = 'required|string|size:10';
            $rules['already_billing.billing_amount']      = 'required|numeric';
            $rules['already_billing.billing_fee']         = 'required|numeric';
            $rules['already_billing.billing_voucher']     = 'required';
            $rules['already_billing.billing_date']        = 'required';
            $rules["already_billing"]                     = 'required|array';

//            $custom_attributes['already_billing.payment_currency_cd'] = '付款币种';
            $custom_attributes['already_billing.billing_amount']      = '扣款金额';
            $custom_attributes['already_billing.billing_fee']         = '扣款手续费';
            $custom_attributes['already_billing.billing_voucher']     = '付款水单';
            $custom_attributes['already_billing.billing_date']        = '出账日期';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    //获取符合条件的可合并付款的应付单
    public function getPaymentBill()
    {
        try {
            $request_data = DataModel::getDataToArr();
            if ($request_data) {
                $this->validatePaymentBillData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new PurPaymentService())->searchMergedPaymentBill($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validatePaymentBillData($data) {
        $data = $data['search'];
        list($rules, $custom_attributes) = TbPurPaymentAuditModel::getValidateData($data['payment_channel_cd'],$data['payment_way_cd']);
        $rules['payment_id']                     = 'required|numeric';
        $rules['payment_channel_cd']             = 'required|size:10';
        $rules['payment_way_cd']                 = 'required|size:10';
        $rules['supplier_name']                  = 'required';
        $custom_attributes['payment_id']         = '应付单id';
        $custom_attributes['payment_channel_cd'] = '支付渠道';
        $custom_attributes['payment_way_cd']     = '支付方式';
        $custom_attributes['supplier_name']      = '供应商';
        $this->validate($rules, $data, $custom_attributes);
    }

    public function payableBillLog()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new PurPaymentService())->getPayableBillLog($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function paymentBillLog()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new PurPaymentService())->getPaymentBillLog($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function getPrePaymentInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (!$request_data['payment_audit_id']) throw new Exception('缺少付款单id参数');
            if (!$request_data['money_type']) throw new Exception('缺少类型参数');
            if (!$request_data['action_type_cd']) throw new Exception('缺少类型参数');
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new PurPaymentService())->getPrePaymentInfo($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    // 处理因#10380重复生成抵扣金的记录（原先#10287 对于预付款拆分问题抵扣金触发相关的优化已经生成一次抵扣金记录了）
    public function getRepeatDeductionData()
    {
        // 原先历史采购单中，拆单的应付单如果已经完成，会走完生成抵扣金流程，跟本次生成抵扣金重复，故需要将本次部分拆单的应付单的抵扣金记录删除以及触发操作删除
        $sql = "SELECT
    po.main_id as id
FROM
    `tb_pur_operation` po
left join
    tb_pur_payment pp on pp.payment_no = po.bill_no
WHERE
    po.`money_type` = '2' 
    AND po.`created_at` > '2020-07-22 10:50:13' 
    AND po.`created_by` = 'system' 
    AND po.`action_type_cd` = 'N002870015'
    AND pp.pid != '0'
    AND pp.`status` = '3'
";
        $data = M()->query($sql);
        return $data;
    }
    public function fixRepeatHistDeduction()
    {
        try {
            $Model = M();
            $operation_model = M('operation', 'tb_pur_');

            // 抵扣金处理
            $dedRes = $this->getRepeatDeductionData();
            foreach ($dedRes as $k => $v) {
                (new PurService())->cancelDeductionAmount($v['id'], $Model, true);
                // 触发操作记录删除
                $opr_res = $operation_model->where(['main_id' => $v['id'], 'money_type' => '2'])->delete(); 
                if (false === $opr_res) {
                    echo M()->_sql();
                    throw new \Exception('抵扣金触发操作记录删除失败');
                }
            }
            $Model->commit();
            echo "success";
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            p($res);
            $Model->rollback();
        }
    }
    // #10299 历史异常抵扣金清理
    public function fixHistDeduction()
    {
        try {
            $res = $this->getHistPayData();
            $Model = M();
            $pay_model = M('payment', 'tb_pur_');
            $operation_model = M('operation', 'tb_pur_');
            foreach ($res as $key => $value) {
                // 应付记录删除
                $del_res = $Model->table('tb_pur_payment')->where(['id' => $value['id']])->save(['status' => '5', 'deleted_by' => 'system', 'deleted_at' => date('Y-m-d H:i:s', time())]);
                if (false === $del_res) {
                    echo M()->_sql();
                    $Model->rollback();
                }
                // 更新订单总表付款状态 tb_pur_relevance_order payment_status
                $change_res = $this->changePayStatus($value['relevance_id'], $pay_model);
                if (false === $change_res) {
                    echo M()->_sql();
                    $Model->rollback();
                }
            }
            // 抵扣金处理
            $dedRes = $this->getHistDeduData();
            foreach ($dedRes as $k => $v) {
                (new PurService())->cancelDeductionAmount($v['id'], $Model, true);
                // 触发操作记录删除
                $opr_res = $operation_model->where(['main_id' => $v['id'], 'money_type' => '2'])->delete(); 
                if (false === $opr_res) {
                    echo M()->_sql();
                    throw new \Exception('抵扣金触发操作记录删除失败');
                }
            }
            $Model->commit();
            echo "success";
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            p($res);
            $Model->rollback();
        }
    }

    public function getHistDeduData()
    {
        $where = [];
        $model  = M('operation','tb_pur_');
        $where['t.money_type'] = '2'; // 抵扣
        $where['t.clause_type'] = array('in', ['7', '10']);
        $where['t.action_type_cd'] = array('in', ['N002870017', 'N002870005', 'N002870004', 'N002870016']);
        $where['pp.is_revoke'] = array('neq', '1');
        $list = $model
            ->alias('t')
            ->field('pp.id, pro.relevance_id')
            ->join('left join tb_pur_deduction_detail pp on pp.id = t.main_id')
            ->join('left join tb_pur_order_detail pod on pod.procurement_number = pp.order_no')
            ->join('left join tb_pur_relevance_order pro on pro.order_id = pod.order_id')
            ->where($where)
            ->group('pp.id')
            ->select();
        $newList = [];
        $clause_type_list = [7 => '无尾款', 10 => '尾款-每次发货后X天付款 & 无尾款'];
        $purOperationModel = D('Scm/PurOperation');
        // 过滤数据 保留（对应采购单尾款字段=无）
        foreach ($list as $key => $value) {
            $flag = false;
            if (!$value['relevance_id']) {
                continue;
            }
            $flag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($flag) {
                $newList[] = $value;
            }
        }
        return $newList;
    }

    public function getHistPayData()
    {
        $where = [];
        $model  = M('operation','tb_pur_');
        $where['t.money_type'] = '1'; // 应付
        $where['t.clause_type'] = array('in', ['7', '10']);
        $where['t.action_type_cd'] = array('in', ['N002870006', 'N002870018']);
        $where['pp.status'] = array('neq', '5');
        $list = $model
            ->alias('t')
            ->field('pp.id, pp.payment_no, pp.relevance_id')
            ->join('left join tb_pur_payment pp on pp.id = t.main_id')
            ->join('left join tb_pur_relevance_order pro on pro.relevance_id = pp.relevance_id')
            ->join('left join tb_pur_order_detail pod on pod.order_id = pro.order_id')
            ->where($where)
            ->select();
        $newList = [];
        $purOperationModel = D('Scm/PurOperation');
        // 过滤数据 保留（对应采购单尾款字段=无）
        foreach ($list as $key => $value) {
            $flag = false;
            if (!$value['relevance_id']) {
                continue;
            }
            $flag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($flag) {
                $newList[] = $value;
            }
        }
        return $newList;
    }

    // 可以用来修复删除应付或抵扣金记录，请勿删除
    public function fixHisData()
    {
        /*应付生成
            删除记录 应付记录payment
                用sql
            总应付状态更改
            触发操作记录删除 根据main_id，&& money_type = 1删除操作记录

        抵扣处理
            删除记录
            总金额扣减掉抵扣金额
                over_deduction_amount //剩余抵扣总金额金额 减掉 金额
                unused_deduction_amount //算作抵扣金总金额 减掉 金额
            触发操作记录删除 根据main_id，&& money_type = 2删除操作记录

        确认下软删除是否有效
        没有效果就只能删除记录了

        旧单通过触发条件误生成应付记录（N002870008）
        YF20190814007 RN201902200013-001 
        YF20190816005 RN201907090003-002
        YF20190816006 RN201907010002-001

        抵扣记录多了的只有 这一个 RN201906260010-001*/
        // 应付记录
        // 抵扣记录 83 8
        try {
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $operation_model = M('operation', 'tb_pur_');
            /*// 应付记录处理
            $pay_model = M('payment', 'tb_pur_');
            $deduction_model = M('deduction', 'tb_pur_');
            $deduction_detail_model = M('deduction_detail', 'tb_pur_');
            $deal_pay_arr = ['YF20190814007', 'YF20190816005', 'YF20190816006'];
            foreach ($deal_pay_arr as $key => $value) {
                // 删除应付记录以及触发记录
                $pay_info = [];
                $pay_info = $pay_model->where(['payment_no' => $value])->find();
                if (!$pay_info) {
                    continue;
                }
                $pa_res = $pay_model->where(['payment_no' => $value])->delete();
                if (!$pa_res) {
                    echo M()->_sql();
                    throw new \Exception("应付记录{$value}删除失败");
                }

                // 更新订单总表付款状态 tb_pur_relevance_order payment_status
                $this->changePayStatus($pay_info['relevance_id'], $pay_model);

                $opr_res = $operation_model->where(['main_id' => $pay_info['id'], 'money_type' => '1'])->delete(); 
                if (!$opr_res) {
                    echo M()->_sql();
                    throw new \Exception('应付触发操作记录删除失败');
                }
            }*/


            $model = new Model();
            $deduction_detail_id = '233';
            (new PurService())->cancelDeductionAmount($deduction_detail_id, $model);
            // 触发操作记录删除
            $opr_res = $operation_model->where(['main_id' => $deduction_detail_id, 'money_type' => '2'])->delete(); 
            if (!$opr_res) {
                echo M()->_sql();
                throw new \Exception('抵扣金触发操作记录删除失败');
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    // 根据应付表的status状态判断是否需要调整采购单的状态
    public function changePayStatus($relevance_id, $model)
    {
        if (!$model) {
            throw new \Exception('实例化应付记录表失败');
        }
        if (!$relevance_id) {
            throw new \Exception('缺失参数');
        }
        $where_map = [];
        $where_map['relevance_id'] = $relevance_id;
        $where_map['status'] = array('neq', '5'); // 删除状态不属于合理付款数据之内
        $res = $model->field('status')->where($where_map)->select();
        $count_num = 0;
        $pay_done_num = 0;
        foreach ($res as $key => $value) {
            $count_num++;
            if (strval($value['status']) === '3' ) {
                $pay_done_num++;
            }
        }
        if (!$pay_done_num) { // 待付款
            $payment_status = '0';
        } else if (strval($pay_done_num) === strval($count_num)) { // 付款完成
            $payment_status = '2';
            p($relevance_id);
        } else {
            $payment_status = '1';
        }
        $save['payment_status'] = $payment_status;
        $relevance_order_model = M('relevance_order', 'tb_pur_');
        if (false === $relevance_order_model->where(['relevance_id' => $relevance_id])->save($save)) {
            throw new \Exception('更新采购单付款状态失败');
        }
        return true;
    }

    /**
     * 获取 新条款 非创建后 无尾款的采购单id
     * @param $action_type_cd
     * @return array
     */
    public function getCheckRelevanceIds($action_type_cd = '')
    {
        //获取走新条款的采购单 过滤创建后的新条款 （订单创建时，才用报价id获取相应条款信息，因为还没创建relevance_id）
        $clause_info = (new Model())->table('tb_pur_clause')->field('purchase_id')->where(['_string'=> 'action_type_cd <> "N002870001"'])->select(); // 获取条款信息
        $relevance_ids = array_unique(array_column($clause_info, 'purchase_id'));
        //过滤已经生成对应触发操作抵扣记录的采购单号
        $relevance_ids = $this->checkRelevanceIdsByDeduction($relevance_ids, $action_type_cd);
        $relevance_id_tem = [];
        foreach ($relevance_ids as $key => $value) {
            if (!isset($value)) {
                continue;
            }
            //筛选出无尾款的采购单id
            if (D('Scm/PurOperation')->checkHasFundOfEnd($value)) {//是否是无尾款
                $relevance_id_tem[] = $value;
            }
        }

        //未取消的采购单
        $relevance_model = M('relevance_order','tb_pur_');
        $relevance = $relevance_model->field('relevance_id')->where(['relevance_id' => ['in', $relevance_id_tem], '_string'=> 'order_status <> "N001320500"'])->select();
        $relevance_ids = array_column($relevance, 'relevance_id');
        return $relevance_ids;
    }

    /**
     * 根据抵扣金明细过滤采购单号
     * @param $relevance_ids
     * @param $action_type_cd
     * @return array
     */
    public function checkRelevanceIdsByDeduction($relevance_ids, $action_type_cd = '')
    {
        //获取抵扣金明细
        $relevance_ids = empty($_REQUEST['relevance_id']) ? $relevance_ids : [$_REQUEST['relevance_id']];
        $deduction_detail = $this->getDeductionByRelevanceIds($relevance_ids, $action_type_cd);
        if (!empty($deduction_detail)) {
            $relevance_ids_tem = array_unique(array_column($deduction_detail, 'relevance_id'));
            $relevance_ids = array_diff($relevance_ids, $relevance_ids_tem);
        }
        return $relevance_ids;
    }

    /**
     * 获取抵扣金明细
     * @param $relevance_ids
     * @param $action_type_cd
     * @return array
     */
    public function getDeductionByRelevanceIds($relevance_ids, $action_type_cd = '')
    {
        $clause_type = '7';
        if ($action_type_cd == 'N002870004') $clause_type = '8';
        $deduction_detail_model = M('deduction_detail','tb_pur_');
        $where = ['o.relevance_id' => ['in', $relevance_ids], 'op.clause_type' => $clause_type];
        if (!empty($action_type_cd)) {
            $where['op.action_type_cd'] = $action_type_cd;
        }
        $deduction_detail = $deduction_detail_model
            ->alias('t')
            ->field('t.id, o.relevance_id, t.deduction_id, t.deduction_amount, t.turnover_type')
            ->join("left join tb_pur_order_detail d on d.procurement_number = t.order_no ")
            ->join("left join tb_pur_relevance_order o on o.order_id = d.order_id ")
            ->join("left join tb_pur_operation op on op.main_id = t.id ")
            ->where($where) //4:尾款-每次入库后X天付款;
            ->select(); // 获取条款信息
        return $deduction_detail;
    }


    // 采购入库确认（残次品）
    public function warehouseBrokenHistory()
    {
        //$relevance_ids = '10231';
        $action_type_cd = 'N002870004';
        $relevance_ids = $this->getCheckRelevanceIds($action_type_cd);
        $model = new Model();
        $model->startTrans();
        //获取采购单对应的物流发货-入库数据
        $ship_infos = M('ship','tb_pur_')
            ->alias('t')
            ->field('w.id warehouse_id, w.warehouse_code, t.relevance_id')
            ->join("left join tb_pur_warehouse w on w.ship_id = t.id ")
            ->where(['t.relevance_id'=> ['in',$relevance_ids]])->select();
        $success = [];
        $err = [];
        foreach ($ship_infos as $key => $value) {
            //商品发货表
            $shipped_goods = $model->table('tb_pur_warehouse_goods')->field('ship_goods_id as goods_id, warehouse_number, warehouse_number_broken')->where(['warehouse_id' => $value['warehouse_id'], '_string' => 'warehouse_number_broken > 0'])->select();
            if (empty($shipped_goods)) {
                continue;
            }
            $addDataInfo['detail'] = $shipped_goods; // 本次发货数量 和采购价 用来获取付款规则公式的总金额
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            $resDeduction = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870004', $value['relevance_id'], $value['warehouse_code']);
            if (!$resDeduction) {
                $err[] = $value['relevance_id'];
            } else {
                $success[] = $value['relevance_id'];
            }
        }
        if (!empty($err)) {
            $model->rollback();
            throw new \Exception(L('生成应付记录失败' . json_encode($err)));
        }
        $model->commit();
        echo '采购入库确认（残次品）历史数据修复成功-采购单号：' . json_encode($success);
        return true;
    }

    // 采购退货出库确认（正品）
    public function returnDeliveryConfirmation()
    {
        //获取抵扣记录
        //采购单状态=未取消 & 走新条款 & 尾款-每次入库后X天付款 的采购单
        $deduction_detail_model = M('deduction_detail','tb_pur_');
        $deduction_detail = $deduction_detail_model
            ->alias('t')
            ->field('t.id, t.deduction_id, t.deduction_amount, t.turnover_type')
            ->join("left join tb_pur_order_detail d on d.procurement_number = t.order_no ")
            ->join("left join tb_pur_relevance_order o on o.order_id = d.order_id ")
            ->join("left join tb_pur_operation op on op.main_id = t.id ")
            ->where(['op.clause_type' => 4, 'op.action_type_cd' => 'N002870016']) //4:尾款-每次入库后X天付款 && 采购退货出库（正品）;
            ->where(['_string'=> 'o.order_status <> "N001320500"']) //未处理
            ->select(); // 获取条款信息
        $ids = array_column($deduction_detail, 'id');
        $model = new Model();
        $model->startTrans();
        $where = [];
        if (empty($deduction_detail)) {
            throw new \Exception(L('抵扣记录不存在'));
        }
        foreach ($deduction_detail as $value) {
            $where['id'] = $value['deduction_id'];
            $deduction = $model->table('tb_pur_deduction')->where($where)->find();
            if (!empty($deduction)) {
                if ($value['turnover_type'] == PurService::TURNOVER_AS_DEDUCTION_AMOUNT) {
                    $save_data = [
                        'over_deduction_amount' => $deduction['over_deduction_amount'] - $value['deduction_amount'],
                        'unused_deduction_amount' => $deduction['unused_deduction_amount'] - $value['deduction_amount'],
                    ];
                } else if ($value['turnover_type'] == PurService::TURNOVER_USE_DEDUCTION_AMOUNT) {
                    if ($deduction['over_deduction_amount'] < $value['deduction_amount']) {
                        $model->rollback();
                        throw new \Exception(L('供应商账户余额小于当前抵扣金额'));
                    }
                    $save_data = [
                        'over_deduction_amount' => $deduction['over_deduction_amount'] + $value['deduction_amount'],
                        'used_deduction_amount' => $deduction['used_deduction_amount'] - $value['deduction_amount'],
                    ];
                } else {
                    $model->rollback();
                    throw new \Exception(L('未知进出账类型'));
                }
                $res = $model->table('tb_pur_deduction')->where($where)->save($save_data);
                if (!$res) {
                    $model->rollback();
                    throw new \Exception(L('更新供应商金额信息失败'));
                }
            }
        }
        $del_where['id'] = ['in', $ids];
        $res = $deduction_detail_model->where($del_where)->delete();
        if (!$res) {
            $model->rollback();
            throw new \Exception(L('删除抵扣金额信息失败'));
        }
        $model->commit();
        return true;
    }

    // 标记入库完结
    public function warehouseEndHistory()
    {
        //获取 新条款 非创建后 无尾款的采购单id
        $action_type_cd = 'N002870005';
        $relevance_ids = $this->getCheckRelevanceIds($action_type_cd);
        $ship_infos = M('ship','tb_pur_')
            ->alias('t')
            ->field('t.id, t.warehouse_id, w.warehouse_code, t.relevance_id')
            ->join("left join tb_pur_warehouse w on w.ship_id = t.id ")
            ->join("left join tb_pur_ship_goods g on g.ship_id = t.id ")
            ->where(['t.relevance_id' => ['in', $relevance_ids], 't.warehouse_status' => '1', '_string' => 't.shipping_number - t.warehouse_number - t.warehouse_number_broken > 0'])->select();
        $model = new Model();
        $model->startTrans();
        if (empty($ship_infos)) {
            throw new \Exception(L('没有需要处理的标记入库完结数据'));
        }
        //入库完成
        $success = [];
        $err = [];
        foreach ($ship_infos as $key => $value) {
            $ship_info = M('ship','tb_pur_')->field('id, relevance_id, warehouse_id')->where(['relevance_id'=>$value['relevance_id'], 'warehouse_status' => 1])->find();
            $warehouse_id = $value['warehouse_id'];
            $addDataInfo['money_id'] = $ship_info['id'];
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            $op_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870005', $value['relevance_id'], $warehouse_id);
            if (!$op_res) {
                $err[] = $value['relevance_id'];
            } else {
                $success[] = $value['relevance_id'];
            }
        }
        if (!empty($err)) {
            $model->rollback();
            throw new \Exception(L('生成抵扣记录失败' . json_encode($err)));
        }
        $model->commit();
        echo '标记入库完结历史数据修复成功-采购单号：' . json_encode($success);
        return true;
    }

    // 标记发货完结
    public function shipEndHistory()
    {
        //获取 新条款 非创建后 无尾款的采购单id
        $action_type_cd = 'N002870017';
        $relevance_ids = $this->getCheckRelevanceIds($action_type_cd);
        $goods_info = M('goods_information', 'tb_pur_')
            ->field('relevance_id, ship_end_number')
            ->where(['relevance_id' => ['in', $relevance_ids], '_string' => 'ship_end_number > 0'])
            ->group('relevance_id')
            ->select();
        if (empty($goods_info)) {
            throw new \Exception(L('没有需要处理的标记发货完结数据'));
        }
        $model = new Model();
        $model->startTrans();
        $success = [];
        $err = [];
        foreach ($goods_info as $key => $value) {
            // 生成抵扣记录
            $addDataInfo['clause_type'] = '7';
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            $pur_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870017', $value['relevance_id']);
            if (!$pur_res) {
                $err[] = $value['relevance_id'];
            } else {
                $success[] = $value['relevance_id'];
            }
        }
        if (!empty($err)) {
            $model->rollback();
            throw new \Exception(L('生成抵扣记录失败' . json_encode($err)));
        }
        $model->commit();
        echo '标记发货完结历史数据修复成功-采购单号：' . json_encode($success);
        return true;
    }

    //更新历史数据通过order_id
    public function updateHistoryOrder()
    {
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '512M');
        $model = new Model();
        $model->startTrans();
        if (!empty($_REQUEST['relevance_id'])) {
            $order = D('TbPurRelevanceOrder')->where(['relevance_id'=>$_REQUEST['relevance_id']])->find();
            if(empty($order)) {
                throw new \Exception(L('查询采购单失败' . json_encode($order)));
            }
            $order_ids = [$order['order_id']];
        } else {
            $relevance_model = M('relevance_order','tb_pur_');
            $relevance = $relevance_model->field('order_id')->select();
            $order_ids = array_column($relevance, 'order_id');
        }
        //$relevance_ids = ['10603']
        $err = [];
        while (!empty($order_ids)) {
            $order_id = array_shift($order_ids);
            $res = D('Scm/PurOperation')->updateOrderPaymentStatus($order_id);
            if ($res === false) {
                $err[] = $order_id;
            }
        }
        echo 'success:';
        if ($err)
        echo '<br/>---------------------<br/>error:' . json_encode($err);
        $model->commit();
    }

    //会计审核
    public function accountingAudit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $model        = new Model();
            $model->startTrans();
            if ($request_data) {
                $this->validateAccountingAuditData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $rClineVal    = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            $id = M('op_order_refund', 'tb_')->where(['payment_audit_id' => $request_data['payment_audit_id']])->getField('id');
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            //  采购应付 调拨应对 B2C退款
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_allo_payable) {
                // 调拨应付
                (new ExpenseBillPaymentService($model))->accountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable){
                // B2C退款
                (new B2CPaymentService($model))->accountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                (new TransferPaymentService($model))->accountingAudit($request_data);
                $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
                $this->WorkWxSendMessage($request_data, $payment_info);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                (new TransferPaymentService($model))->accountingAudit($request_data);
                $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
                $this->WorkWxSendMessage($request_data, $payment_info);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款
                $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
                if ($request_data['is_return'] == 1) { // 保存退回记录状态和操作人
                    (new GeneralPaymentService($model))->recordReturnInfo($request_data);
                }
                (new GeneralPaymentService($model))->accountingAudit($request_data);
                $this->WorkWxSendMessage($request_data, $payment_info);
            }else{
                // 采购应付
                (new PurPaymentService($model))->accountingAudit($request_data);
                $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
                $this->WorkWxSendMessage($request_data, $payment_info);
            }

            
            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
            if($request_data['is_return'] == 1 && $request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable){
                #发送企业微信消息
                $afterSaleService = new OmsAfterSaleService();
                $afterSaleService->after_sale_refund_pass_wx_msg($id,5);
            }

        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $request_data
     * @param $payment_info
     *
     * @return string
     */
    protected function WorkWxSendMessage($request_data, $payment_info)
    {
        //企业微信信息发送
        $wx_return_res = (new ApiAction())->WorkWxSendMessage($request_data, $payment_info);
    }

    private function validateAccountingAuditData($data) {
        $rules = [
            'status'           => 'required|numeric',
            'payment_audit_id' => 'required|numeric',
        ];
        $custom_attributes = [
            'status'           => '审核状态',
            'payment_audit_id' => '付款id',
        ];
        if ($data['is_return']) {
            $rules['accounting_return_reason']             = 'required';
            $custom_attributes['accounting_return_reason'] = '退回原因';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    //撤回到待会计审核/待提交
    public function returnToAccountingAudit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $model        = new Model();
            $model->startTrans();
            if (!$request_data['payment_audit_id']) throw new Exception('付款单id必填');
            $rClineVal    = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            if (!$rClineVal) throw new Exception('获取流水锁失败');
            $res         = DataModel::$success_return;
            $res['code'] = 200;

            //  采购应付 调拨应对 B2C退款
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_allo_payable) {
                // 调拨应付
                (new ExpenseBillPaymentService($model))->returnToAccountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_b2c_payable){
                // B2C退款
                (new B2CPaymentService($model))->returnToAccountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                (new TransferPaymentService($model))->returnToAccountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                (new TransferPaymentService($model))->returnToAccountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款
                //(new GeneralPaymentService($model))->recordReturnInfo($request_data); 撤回按原来的逻辑即可
                (new GeneralPaymentService($model))->returnToAccountingAudit($request_data);
            }else{
                // 采购应付
                (new PurPaymentService($model))->returnToAccountingAudit($request_data);
            }

            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function paymentAuditExport() {
        $request_data = json_decode($_POST['export_params'], true);
        $service = new PurPaymentService();
        $list = $service->searchPaymentAuditList($request_data, true)['data'];
        $list = $service->addExtraData($list);
        $map  = [
            ['field_name' => 'payment_audit_no', 'name' => '付款单号'],
            ['field_name' => 'source_cd_val', 'name' => '来源'],
            ['field_name' => 'payable_status_val', 'name' => '付款单状态'],
            ['field_name' => 'payment_nature_val', 'name' => '付款性质'],
            ['field_name' => 'our_company_cd_val', 'name' => '我方公司'],
            ['field_name' => 'supplier_name', 'name' => '供应商'],
            ['field_name' => 'contract_information_val', 'name' => '合同信息'],
            ['field_name' => 'contract_no', 'name'         => '合同编号'],
            ['field_name' => 'settlement_type_val', 'name' => '结算类型'],
            ['field_name' => 'procurement_nature_val', 'name' => '采购性质'],
            ['field_name' => 'invoice_information_val', 'name' => '发票信息'],
            ['field_name' => 'invoice_type_val', 'name' => '发票类型'],
            ['field_name' => 'bill_information_val', 'name' => '账单信息'],
            ['field_name' => 'payment_type_val', 'name' => '付款类型'],
            ['field_name' => 'payment_remark', 'name' => '付款需求备注'],

            ['field_name' => 'payable_date_after', 'name' => '预计付款日期'],
            ['field_name' => 'accounting_audit_user', 'name' => '审核负责人'],
            ['field_name' => 'payment_manager_by', 'name' => '付款负责人'],
            ['field_name' => 'created_by', 'name' => '创建人'],
            ['field_name' => 'created_at', 'name' => '创建时间'],
            ['field_name' => 'dept_name', 'name' => '审批部门'],
            ['field_name' => 'payment_channel_cd_val', 'name' => '支付渠道'],
            ['field_name' => 'payment_way_cd_val', 'name' => '支付方式'],
            ['field_name' => 'payable_currency_cd_val', 'name' => '币种'],
            ['field_name' => 'payable_amount_before', 'name' => '确认前-本期应付金额'],
            ['field_name' => 'payable_amount_after', 'name' => '确认后-本期应付金额'],
            ['field_name' => 'bank_reference_no', 'name' => '银行参考号'],
            ['field_name' => 'bank_payment_reason', 'name' => '(银行返回的)付款原因'],
            ['field_name' => 'supplier_collection_account', 'name' => '收款账户名'],
            ['field_name' => 'supplier_opening_bank', 'name' => '收款账户开户行'],
            ['field_name' => 'supplier_card_number', 'name' => '收款银行账号'],
            ['field_name' => 'supplier_swift_code', 'name' => '收款银行SWIFT CODE'],
            ['field_name' => 'platform_cd_val', 'name' => '平台名称'],
            ['field_name' => 'store_name', 'name' => '店铺名称'],
            ['field_name' => 'platform_order_no', 'name' => '平台订单号'],
            ['field_name' => 'trade_no', 'name' => '交易号'],
            ['field_name' => 'pay_com_cd_val', 'name' => '付款公司'],
            ['field_name' => 'collection_account', 'name' => '该支付渠道收款账号'],
            ['field_name' => 'collection_user_name', 'name' => '该支付渠道收款用户名'],
            ['field_name' => 'payment_account', 'name' => '该支付渠道付款账号'],
            ['field_name' => 'payment_our_bank', 'name' => '付款银行名称'],
            ['field_name' => 'payment_our_bank_account', 'name' => '付款银行账号'],
            ['field_name' => 'payment_currency_cd_val', 'name' => '提交付款币种'],
            ['field_name' => 'payment_amount', 'name' => '提交付款金额'],
            ['field_name' => 'billing_currency_cd_val', 'name' => '扣款币种'],
            ['field_name' => 'billing_amount', 'name' => '扣款金额'],
            ['field_name' => 'billing_fee', 'name' => '扣款手续费'],
            ['field_name' => 'billing_total_amount', 'name' => '扣款总金额'],
            ['field_name' => 'billing_date', 'name' => '出账日期'],
            ['field_name' => 'confirmation_remark', 'name' => '付款/出账确认备注'],
        ];
        $this->exportCsv($list, $map);
    }

    //kyriba/银行审核失败处理流程
    public function kyribaPaymentResubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $model        = new Model();
            $model->startTrans();
            if (!$request_data) {
                throw new Exception('请求为空');
            }
            if (empty($request_data['payment_audit_id'])) {
                throw new Exception('付款单id不能为空');
            }
            if (empty($request_data['source_cd'])) {
                throw new Exception('付款来源不能为空');
            }
            $rClineVal = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇重新提交
                $service = new TransferPaymentService($model);
                $service->payment_audit_id = $request_data['payment_audit_id'];
                $service->transferStepHandel();
                $service->returnToPaymentConfirm($request_data['payment_audit_id'], '', $request_data['is_payment_return']);
                $service->returnToAccountingAudit($request_data);
                $request_data['is_return'] = 1;
                $request_data['status'] = 0;
                $service->accountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇重新提交
                $service = new TransferPaymentService($model);
                $service->payment_audit_id = $request_data['payment_audit_id'];
                $service->transferStepHandel();
                $service->returnToPaymentConfirm($request_data['payment_audit_id'], '');
                $service->returnToAccountingAudit($request_data);
                $request_data['is_return'] = 1;
                $request_data['status'] = 0;
                $service->accountingAudit($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款重新提交
                $service = new GeneralPaymentService($model);
                $service->returnToPaymentConfirm($request_data['payment_audit_id'], '', $request_data['is_payment_return']);
                $service->returnToAccountingAudit($request_data);
//                $request_data['is_return'] = 0;
//                $request_data['status'] = 0;
//                $service->accountingAudit($request_data);
                $payment_audit_no_new = $service->renewCreatePaymentBill($request_data['payment_audit_id']);//重新生成付款单
                $request_data['status'] = 5;
                $service->updatePaymentBillStatus($request_data, 1);
                $res['data'] = ['payment_audit_no_new' => $payment_audit_no_new];
            } else {
                // 采购应付重新提交
                $service = new PurPaymentService($model);
                $service->returnToPaymentConfirm($request_data['payment_audit_id'], '', $request_data['is_payment_return']);
                $service->returnToAccountingAudit($request_data);
                $request_data['is_return'] = 0;
                $request_data['status'] = 0;
                $service->accountingAudit($request_data);
            }
            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
}
