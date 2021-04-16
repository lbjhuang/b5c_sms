<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/8
 * Time: 13:25
 */

class QuotationAction extends ScmBaseAction
{
    const PUR_PRE_PAID_CREATE_ORDER_CODE = 'N002860001';//触发时间类型CD, 订单创建后 N002860001，
    public function quotation_list()
    {
        import('ORG.Util.Page');
        $param = $this->params();
        $count = D('Quotation')->quotationCount($param);
        $page = new Page($count, $param['rows'] ? $param['rows'] : 20);
        $list = D('Quotation')->quotationList($param, $page->firstRow . ',' . $page->listRows);
        $this->ajaxReturn(['data' => ['list' => $list, 'page' => ['total_rows' => $count]], 'msg' => 'success', 'code' => 2000]);
    }

    public function quotation_save()
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        $quotation = $_POST;
        try {
            $this->validateQuotation($quotation);
            $logic = D('Scm/Quotation', 'Logic');
            $logic->saveQuotation($quotation);
            $this->ajaxReturn($logic->getRet());
        } catch (Exception $e) {
            $this->ajaxReturn(['data' => null, 'msg' =>$e->getMessage(), 'code' => '401']);
        }

    }

    // 校验数据 （预付款和尾款信息校验）
    public function validateQuotation($data)
    {
        // 预付款和尾款都没有
        //     y
        //         end,至少选择其一
        //     n
        //         校验是否字段都必填
        //         天数非负整数，最大999
        //         付后需要填写数字，0<a<=100，小数点最多4位
        //         日期格式要正确
        //         日期不可为空
        if (count($data['pre_pay']) == 0 && count($data['end_pay']) == 0) { // 预付款和尾款信息至少选其一
            throw new Exception('预付款和尾款信息至少选其一，不可两者皆为无');
        }
        if (!empty($data['pre_pay'])) {
            foreach ($data['pre_pay'] as $key => $value) {
                if ($value['action_type_cd'] == self::PUR_PRE_PAID_CREATE_ORDER_CODE) {
                    $rules["pre_pay.{$key}.days"] = 'required|numeric|min:0|max:999|integer';
                }
                $rules["pre_pay.{$key}.action_type_cd"] = 'required|string';
                $rules["pre_pay.{$key}.percent"] = 'required';
                $rules["pre_pay.{$key}.pre_paid_date"] = 'required|date';
            }
        }
        if (!empty($data['end_pay'])) {
            $rules['end_pay.action_type_cd'] = 'required';
            $rules['end_pay.days'] = 'required|numeric|min:0|max:999|integer';
            $rules['end_pay.pre_paid_date'] = 'required|date';
        }
        $attributes = [
            'pre_pay' => '预付款',
            'end_pay' => '尾款',
        ];

        ValidatorModel::validate($rules, $data, $attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $key => $value) {
                throw new Exception($value[0], 40001);
            }
        }
    }


    public function quotation_update()
    {
        $quotation = $_POST;
        $logic = D('Scm/Quotation', 'Logic');
        $logic->updateQuotation($quotation);
        $this->ajaxReturn($logic->getRet());
    }

    public function quotation_submit()
    {
        $id = I('request.id');
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->quotationSubmit(['id' => $id]);
        $this->ajaxReturn($quotation_l->getRet());
    }

    public function quotation_detail()
    {
//        var_dump(TbPurOrderDetailModel::getShipOutdateList());die;
//        var_dump((new DisplayAction())->getCeoEmailContent(1078));die;
//        ScmEmailModel::I(D('Demand')->find(1200));die;
//        (new QuotationModel())->updateCE(D('Demand')->find(1051));
        $id = I('request.id');
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->quotationDetail($id);
        $this->ajaxReturn($quotation_l->getRet());
    }

    public function quotation_confirm()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->quotationConfirm($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /*
     * 采购侧上传po
     */
    public function quotation_upload_po()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $data['step'] = 'upload_po';
        $data['model'] = 'quotation';
        $quotation_l->handlePO($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /*
     * 采购侧法务审批
     */
    public function quotation_justice_approve()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->justiceApprove($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /*
     * 采购侧重上传po
     */
    public function quotation_reupload_po()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $data['step'] = 'reupload_po';
        $data['model'] = 'quotation';
        $quotation_l->handlePO($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    public function quotation_justice_stamp()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->justiceStamp($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 法务盖章失败，直接提交按钮
     */
    public function quotation_return_stamp()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->returnStamp($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 法务审批建议
     */
    public function quotation_forensic_audit_proposal()
    {
        $data = $this->params();
        $demand_l = D('Scm/Quotation', 'Logic');
        if ($demand_l->forensicAuditProposal($data)) {
            $this->ajaxSuccess();
        } else {
            $this->ajaxError($demand_l->getError());
        }
    }

    /**
     * 法务通知邮件
     */
    public function quotation_audit_email()
    {
        $data = $this->params();
        $demand_l = D('Scm/Quotation', 'Logic');
        if ($demand_l->forensicAuditEmail($data['id'], $data['to'], $data['cc'])) {
            $this->ajaxSuccess();
        } else {
            $this->ajaxError($demand_l->getError());
        }
    }

    /**
     * PO添加水印
     */
    public function quotation_watermark_to_po()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        if ($res = $quotation_l->watermarkToPo($data['id'])) {
            $this->ajaxSuccess($res);
        } else {
            $this->ajaxError([], $quotation_l->getError());
        }
    }

    /*
    * 采购侧归档po
    */
    public function quotation_po_archive()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $data['step'] = 'po_archive';
        $data['model'] = 'quotation';
        $quotation_l->handlePO($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /*
    * 采购侧重新归档po
    */
    public function quotation_po_rearchive()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $data['step'] = 'po_rearchive';
        $data['model'] = 'quotation';
        $quotation_l->handlePO($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 创建订单
     */
    public function quotation_create_order()
    {
        $data = $this->params();
//        D('Quotation')->createOrder($data);
        $this->ajaxReturn(['data' => [], 'msg' => L('error'), 'code' => 3000]);
    }

    /**
     * 放弃报价
     */
    public function quotation_discard()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->discard($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 删除报价
     */
    public function quotation_delete()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->quotationDelete($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 申请修改
     */
    public function quotation_apply_edit()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->applyEdit($data);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     * 申请弃单
     */
    public function quotation_apply_discard()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->applyDiscard($data);
        $this->ajaxReturn($quotation_l->getRet());
    }


    public function quotation_import_goods()
    {
        $filePath = $_FILES['file']['tmp_name'];
        $demand_id = I('request.demand_id');
        header("content-type:text/html;charset=utf-8");
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error(L('请上传EXCEL文件'), '', true);
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
        $currency_list = array_flip($cd_m->getCdKeyY(TbMsCmnCdModel::$currency_cd_pre));
        $drawback_percent_list = array_flip($cd_m->getCdKeyY(TbMsCmnCdModel::$tax_rate_cd_pre));
        $goods_list = D('DemandGoods')->where(['demand_id' => $demand_id])->field("search_id,sku_id")->select();
        if (!$goods_list) {
            $this->ajaxError([], '需求商品不存在');
        }
        # 处理多条形码业务
//        $sku_ids = array_column($goods_list,'sku_id');
//        $goods_skus =  D("Pms/PmsProductSku")->where([
//            'sku_id' => ['in', $sku_ids]
//        ])->field(['sku_id','upc_id','upc_more'])->select();
//        $upc_more_arr = [];
//        foreach ($goods_skus as $sku) {
//            $tmp = explode(',', $sku['upc_more']);
//            $upc_more_arr = array_merge($tmp, $upc_more_arr);
//        }
        $goods_list = array_column($goods_list,'search_id'); #
//        if($upc_more_arr) {
//            $goods_list = array_unique(array_merge($goods_list, $upc_more_arr));
//        }
        $search_c = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $search = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $currency = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $price = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $price_no_tax = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            $number = trim((string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue());
            $drawback_percent = round(trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue()) * 100) . '%';
            if ($currentRow == 3) $currency_c = $currency;
            if (!$search || !in_array($search, $goods_list)) {
                $error = true;
                $msg .= "第{$currentRow}行商品不存在<br />";
            }
            if (in_array($search, $search_c)) {
                $error = true;
                $msg .= "第{$currentRow}行商品重复<br />";
            } else {
                $search_c [] = $search;
            }
            if (!$currency_list[$currency] || $currency != $currency_c) {
                $error = true;
                $msg .= "第{$currentRow}行币种有误<br />";
            }
            $price_input = $price ? $price : $price_no_tax;
            if (!is_numeric($price_input) || $price_input <= 0) {
                $error = true;
                $msg .= "第{$currentRow}行商品价格有误（比如数据中含0、或者为负数）<br />";
            }
            if (!is_numeric($number) || strstr($number, '.') || $number <= 0) {
                $error = true;
                $msg .= "第{$currentRow}行商品数量有误（比如数量中含小数、或者小于1）<br />";
            }
            if (!$drawback_percent_list[$drawback_percent]) {
                $error = true;
                $msg .= "第{$currentRow}行退税比例有误<br />";
            }
            $goods = [];
            if ($price_input && $number) {
                $goods['search_id'] = $search;
                $goods['currency'] = $currency_list[$currency];
                $goods['price'] = $price;
                $goods['price_no_tax'] = $price_no_tax;
                $goods['number'] = $number;
                $goods['drawback_percent'] = $drawback_percent_list[$drawback_percent];
                $expe[] = $goods;
            }
        }
        if ($error) {
            $this->ajaxError([], $msg);
        } else {
            $this->ajaxSuccess($expe);
        }
    }

    public function request_advance()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->requestAdvance($data['id']);
        $this->ajaxReturn($quotation_l->getRet());
    }

    public function allow_advance()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->allowAdvance($data['id']);
        $this->ajaxReturn($quotation_l->getRet());
    }

    public function cancel_advance()
    {
        $data = $this->params();
        $quotation_l = D('Scm/Quotation', 'Logic');
        $quotation_l->cancelAdvance($data['id']);
        $this->ajaxReturn($quotation_l->getRet());
    }

    /**
     *  通过销售团队获取销售小团队
     */
    public function get_sell_small_teame()
    {
        $post_data = $this->params();
        if (!isset($post_data['code']) || empty($post_data['code'])) {
            $this->ajaxError(array(),"参数异常",4000);
        }
        $list = CodeModel::getSellSmallTeamCodeArr($post_data['code']);
        $this->ajaxSuccess($list);
    }
}