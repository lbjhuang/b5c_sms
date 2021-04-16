<?php
/**
 * Class QuoteAction
 * 报价控制器
 * @author Redbo He
 * @date 2020/11/3  11:07
 */
;

class QuoteAction extends BaseAction
{
    protected $quotationRepository;

    public function quotation_management()
    {
        $this->display();
    }

    public function quotation_management_detail()
    {
        $this->display();
    }

    public function see_management_detail()
    {
        $this->display();
    }

    public function combine_cabinets_detail()
    {
        $this->display();
    }

    public function see_cabinets_detail()
    {
        $this->display();
    }

    public function _initialize()
    {
        parent::_initialize();
        # 执行父类函数$result = new ();
        $this->quotationRepository = new QuotationRepository();
    }


    public function index()
    {
        $page_size = I('get.size', 10);
        $list = $this->quotationRepository->search($page_size);
        return $this->ajaxSuccess($list, 'success');
    }


    public function quote_lcl_list()
    {
        $page_size = I('get.size', 10);
        $list = $this->quotationRepository->quote_lcl_list($page_size);
        return $this->ajaxSuccess($list, 'success');
    }

    /**
     * 获取报价列表
     * @author Redbo He
     * @date 2020/11/13  13:25
     */
    public function quote_log_list()
    {
        $object_id = I("get.object_id");
        $object_name = I("get.object_name","quotation");
        if(empty($object_id))
        {
            return $this->ajaxError([],'对象记录ID不能为空');
        }
        $list = $this->quotationRepository->quote_log_list($object_name, $object_id);
        return $this->ajaxSuccess($list, 'success');
    }

    /**
     * 调拨单号数据校验
     * @author Redbo He
     * @date 2021/2/7 13:40
     */
    public function allocate_nos_valid()
    {
        # 是否是手动数据校验
        $_POST['is_manual_valid'] = 1;
        $model = D("Quote/OperatorQuotation");
        $validate =  $model->allocate_nos_validate;
        if ($model->validate($validate)->create(I('post.'), 3)) {
            return $this->ajaxSuccess([],'数据校验通过');
        }
        else
        {
            $error = $model->getError();
            if ($error == 'allocate_no_error')
            {
                $error = $model->allocate_no_error;
            }
            return $this->ajaxError([], $error);
        }
    }

    /**
     * 获取报价 options
     * @author Redbo He
     * @date 2020/11/3  13:25
     */
    public function commonOptions()
    {
        $result = $this->quotationRepository->getQuoteOptions();
        return $this->ajaxSuccess($result);
    }

    /**
     * 保存数据结果
     *
     * @author Redbo He
     * @date 2020/11/3  17:34
     */
    public function save_quotation_step1()
    {
        if(IS_POST) {
            $model = D("Quote/OperatorQuotation");
            if ($model->create(I('post.'), 1)) {
                try {
                    $trans = M();
                    $trans->startTrans();   // 开启事务
                    if ($id = $model->add()) {
                        $trans->commit();
                        return $this->ajaxSuccess($this->quotationRepository->formatQuotation($model->where("id = {$id}")->find()), "报价数据创建成功");
                    }
                } catch (\Exception $e) {
                    $trans->rollback();
                    Log::record("【报价信息创建失败】" . $e->__toString(), Log::ERR);
                    return $this->ajaxError([], '服务器异常，数据保存失败');
                }
            }
            $error = $model->getError();
            if ($error == 'allocate_no_error')
            {
                $error = $model->allocate_no_error;
            }
            return $this->ajaxError([], $error);
        }
    }

    /**
     * 获取柜报报价报价下拉框数据
     * @author Redbo He
     * @date 2020/11/9  14:27
     */
    public function get_quotation_lcl_options()
    {
        $data = $this->quotationRepository->get_quotation_lcl_options();
        return $this->ajaxSuccess($data,"success");
    }

    /**
     * 报价信息也数据接口
     * @author Redbo He
     * @date 2020/11/4  9:47
     */
    public function quotation_detail()
    {
        $id =  I('get.id');
        if(empty($id)) {
            return $this->ajaxError([],'参数ID不能为空');
        }
        $result = $this->quotationRepository->quotationDetail($id);
        if($result['status'])
        {
            return $this->ajaxSuccess($result['data'],$result['msg']);
        }
        else
        {
            return $this->ajaxSuccess([],$result['msg']);
        }
    }

    /**
     * 商品导入模型信息导出
     * @author Redbo He
     * @date 2020/11/4  10:52
     */
    public function excelDownload()
    {
        $file_name = I('get.file_name','quote_goods_import.xlsx') ;
        $file_path = APP_PATH . 'Tpl/Home/Public/Excel/' . $file_name;
        if(file_exists($file_path)) {
            import('ORG.Net.Http');
            Http::download($file_path, $file_name);
        }
        if(IS_AJAX) {
            return $this->ajaxError([],'文件不存在');
        }
        throw_exception("文件{$file_name}不存在");
    }


    /**
     * 搜索商品信息
     * @author Redbo He
     * @date 2021/2/7 16:26
     */
    public function searchGoods()
    {
        $keyword = I("get.keyword");
        $res = $this->quotationRepository->searchGoods();
        return $this->ajaxSuccess($res, '数据查询成功');
    }

    /**
     * 保存询价数据
     * @author Redbo He
     * @date 2020/11/4  19:15
     */
    public function save_quotation_step2()
    {
        $params_data  = json_decode(file_get_contents('php://input'),1);
        $id = $params_data['id'];
        # 数据校验
        $quotation_model = D("Quote/OperatorQuotation");
        $quotation = $quotation_model->where([['id' => $id]])->find();
        if(!$quotation) {
            return $this->ajaxError([],'报价数据不存在，请检查请求数据');
        }

        # 报价单已取消业务判断
        if(!$quotation['status_cd'] == $quotation_model::STATUS_CANCEL) {
            return $this->ajaxError([],'报价单已取消，无法在询价');
        }

        if($quotation['quote_type'] == $quotation_model::QUOTE_TYPE_PRE && ( !isset($params_data['goods_info']) ||  empty($params_data['goods_info'])))  {
            return $this->ajaxError([],'商品信息不能为空');
        }


        $goods_data = [];
        if($quotation['quote_type'] == $quotation_model::QUOTE_TYPE_PRE) {
            $goods_model = D("Quote/QuoteGoods");
            $goods_error = '';

            foreach ($params_data['goods_info'] as $goods)
            {
                if(!$goods_model->create($goods, 1))
                {
                    $goods_error = $goods_model->getError();
                    break;
                }
            }

            if($goods_error)
            {
                return $this->ajaxError([], $goods_error);
            }
            $date = new Date();
            foreach ($params_data['goods_info'] as $item) {
                $item['quotation_id'] = $id;
                $item['creator_id'] = session('user_id');
                $item['created_by'] = session('m_loginname');
                $item['created_at'] = $date->format();
                $goods_data[] = $item;
            }
        }
        
        ##  校验询价信息
        $inquiries_model = D("Quote/QuoteInquiries");
        if(!$inquiries_model->create($params_data['inquiries']))
        {
            return $this->ajaxError([], $inquiries_model->getError());
        }
        ## 校验 装箱信息
        $packing_information_model =  D("Quote/QuotePackingInformation");
        $pack_error = '';
        if(is_array($params_data['packing_data']))
        {
            foreach ($params_data['packing_data'] as $packing_item)
            {
                if(!$packing_information_model->create($packing_item))
                {
                    $pack_error = $packing_information_model->getError();
                    break;

                }
            }
        }
        if($pack_error)
        {
            return $this->ajaxError([], $pack_error);
        }

        $quotation_params = $params_data['quotation'];
        if($quotation_params && isset($quotation_params['operate_remark']) && (sstrlen($quotation_params['operate_remark']) > 100)) {
            return $this->ajaxError([], "运营备注字长度已超过100，请检查");
        }
        $trans = M();
        $trans->startTrans();   // 开启事务
        try 
        {
            // 插入商品信息 提前报价 保存商品信息
            if($goods_data) {
                $res = $goods_model->addAll($goods_data);
            }
            // 保存 询价信息
            $id = $inquiries_model->data($params_data['inquiries'])->add();
            // 保存 包装信息
            $pack_id = $packing_information_model->addAll($params_data['packing_data']);
            # 报价单订单状态 改为 待报价
            $data['status_cd'] = $quotation_model::STATUS_CD_WAIT_QUOTE;
            # 运营备注信息
            if(isset($quotation_params['operate_remark'])) {
                $data['operate_remark'] = $quotation_params['operate_remark'];
            }
            $res2 = $quotation_model->where([['id' => $params_data['id']]])->data($data)->save();
            # 记录日志信息
            $quote_logs_model = D("Quote/QuoteLogs");
            $service = new QuotationService();
            $service->saveLog($quote_logs_model::OBJECT_NAME_QUOTATION,  $params_data['id'],"发起询价");
            ### 发送企业微信模板消息
            $service = new QuotationService();
            $service->pushQuoteWorkWxMessage('wait_quote', $quotation);

        } 
        catch (\Exception $e) 
        {
            $trans->rollback();
            Log::record("【保存询价信息失败】".$e->__toString(), Log::ERR);
            return $this->ajaxError([],'服务器异常，数据保存失败');
        }
        $trans->commit();
        return $this->ajaxSuccess([],'数据保存成功');
    }

    /**
     * 保存报价
     * @author Redbo He
     * @date 2020/11/5  13:28
     */
    public function save_quotation_step3()
    {
        $params_data  = json_decode(file_get_contents('php://input'),1);
        $quotation_scheme_model = D("Quote/QuotationScheme");
        $object_name = $params_data['object_name'];
        $object_id   = $params_data['object_id'];
        if(!in_array($object_name,[$quotation_scheme_model::OBJECT_NAME_QUOTE_LCL, $quotation_scheme_model::OBJECT_NAME_QUOTATION]))
        {
            return $this->ajaxError([],'对象名称错误，请检查');
        }
        if(empty($object_id)) {
            return $this->ajaxError([],'对象ID不能为空，请检查');
        }
        # 数据合法性校验
        $quotation = $quote_lcl =  [];
        if($object_name == $quotation_scheme_model::OBJECT_NAME_QUOTATION)
        {
            $quotation_model = D("Quote/OperatorQuotation");
            $quotation = $quotation_model->where([['id' => $object_id]])->find();
            if(!$quotation) {
                return $this->ajaxError([],'报价数据记录不存在，请检查请求数据');
            }
            # 报价单已取消业务判断
            if(!$quotation['status_cd'] == $quotation_model::STATUS_CANCEL) {
                return $this->ajaxError([],'报价单已取消，不能执行该操作');
            }
        }
        if($object_name == $quotation_scheme_model::OBJECT_NAME_QUOTE_LCL) {
            $quote_lcl_model = D("Quote/QuoteLcl");
            $quote_lcl = $quote_lcl_model->relation('quote_lcl_quotations')->where([['id' => $object_id]])->find();
            if(!$quote_lcl) {
                return $this->ajaxError([],'拼柜报价数据记录不存在，请检查请求数据');
            }
        }
        ### 校验数据合理性
        if(empty($params_data['quotation_scheme'])) {
            return $this->ajaxError([],'报价方案信息不能为空');
        }
        ## 校验方案信息
        $error_msg = $scheme_detail_error_msg = '';
        $quotation_scheme_detail_model = D("Quote/QuotationSchemeDetail");
        $type = Model::MODEL_INSERT;
        $scheme_id = 0;
        foreach ($params_data['quotation_scheme'] as $scheme_item)
        {
            $scheme_id = isset($scheme_item['id']) ? $scheme_item['id']: 0;
            $type = $scheme_id ? Model::MODEL_UPDATE: Model::MODEL_INSERT;
            if(!$quotation_scheme_model->create($scheme_item, $type))
            {
                $error_msg = $quotation_scheme_model->getError();
                break;
                # 校验方案新是否输入有误
            }
            foreach ($scheme_item['scheme_detail'] as $k => $item)
            {
                if(!$quotation_scheme_detail_model->create($item,$type))
                {
                    $scheme_detail_error_msg = $quotation_scheme_detail_model->getError();
                    $index = $k + 1;
                    $scheme_detail_error_msg = "[方案{$index}数据异常]:". $scheme_detail_error_msg;
                }
                if($scheme_detail_error_msg)
                {
                    break;
                }
            }
            if($scheme_detail_error_msg) {
                break;
            }
        }
        if($error_msg) {
            return $this->ajaxError([], $error_msg);
        }
        # 方案数据异常
        if($scheme_detail_error_msg) {
            return $this->ajaxError([], $scheme_detail_error_msg);
        }
        $trans = M();
        $trans->startTrans();   // 开启事务
        $error = '';
        try 
        {
            foreach ($params_data['quotation_scheme'] as $scheme_item)
            {
                $scheme_id = isset($scheme_item['id']) ? $scheme_item['id']: 0;
                $type = $scheme_id ? Model::MODEL_UPDATE: Model::MODEL_INSERT;
                if($quotation_scheme_model->create($scheme_item, $type))
                {
                    if(!$scheme_id)
                    {
                        $res = $quotation_scheme_model->add($scheme_item);
                    }
                    else
                    {
                        $res = $quotation_scheme_model->save($scheme_item);
                    }
                    if(!$res)
                    {
                        $error = $quotation_scheme_model->getDbError();
                        $error2 = $quotation_scheme_model->getError();
                        if($error) {
                            $error = "后台数据更新失败，请稍后再试";
                        }
                        else
                        {
                            $error = $error2;
                        }
                        break;
                    }
                }
                else
                {
                    $error = $quotation_scheme_model->getError();
                    break;
                }
            }

            # 提前报价 正式报价 修改报价数据状态
            $service = new QuotationService();
            $content = "提交报价";
            if($params_data['object_name'] == $quotation_scheme_model::OBJECT_NAME_QUOTATION)
            {
                $quotation_model = D("Quote/OperatorQuotation");
                # object_name  object_id
                $update_data['status_cd'] = $quotation_model::STATUS_CD_WAIT_CONFIRM;
                $res2 = $quotation_model->where([['id' => $params_data['object_id']]])->data($update_data)->save();
                if($res2 === false) {
                    throw_exception("报价方案状态数据修改失败");
                }
                $quotation = $quotation_model->where([['id' => $params_data['object_id']]])->find();
                if($quotation['is_twice_quote']) {
                    $content = "发起二次报价";
                    ### 发送企业微信消息
                    $res = $service->pushQuoteWorkWxMessage('twice_quote_wait_confirm',$quotation);
                }
                else
                {
                    ### 发送企业微信消息
                    $service->pushQuoteWorkWxMessage('wait_confirm',$quotation);
                }
            }
            else if ($params_data['object_name'] == $quotation_scheme_model::OBJECT_NAME_QUOTE_LCL)
            {
                $quote_lcl_model = D("Quote/QuoteLcl");
                # object_name  object_id
                $update_data['status_cd'] = $quote_lcl_model::STATUS_CD_WAIT_CONFIRM;
                $res2 = $quote_lcl_model->where([['id' => $params_data['object_id']]])->data($update_data)->save();
                if($res2 === false) {
                    throw_exception("拼柜方案状态数据修改失败");
                }
                $content = "提交拼柜报价";
                # quote_lcl_wait_confirm
                $service->pushQuoteWorkWxMessage('quote_lcl_wait_confirm',$quote_lcl,'quote_clc');
            }
            $service->saveLog($object_name,  $params_data['object_id'],$content);
        } 
        catch (\Exception $e) 
        {
            $trans->rollback();
            Log::record("【报价信息保存失败】".$e->__toString(), Log::ERR);
            return $this->ajaxError([],'服务器异常，数据保存失败');
        }
        if($error) {
            $trans->rollback();
            return $this->ajaxError([], $error);
        }
        $trans->commit();
        return $this->ajaxSuccess([],'数据保存成功');
    }


    /**
     * 报价需求确认接口
     * @author Redbo He
     * @date 2020/11/5  19:18
     */
    public function quotation_scheme_conform()
    {
        $scheme_id    = I('post.scheme_id');
        if(empty($scheme_id)) {
            return $this->ajaxError([],"方案ID不能为空");
        }
        $trans = M();
        $trans->startTrans();   // 开启事务
        try 
        {
            $result = $this->quotationRepository->quotation_scheme_conform($scheme_id);

        } 
        catch (\Exception $e) 
        {
            $trans->rollback();
            Log::record("【报价信息保存失败】".$e->__toString(), Log::ERR);
            return $this->ajaxError([],'服务器异常，数据保存失败');
        }
        if(!$result['status']) {
            $trans->rollback();
            return $this->ajaxError([],$result['msg']);
        }
        else
        {
            $trans->commit();
            return $this->ajaxSuccess([], $result['msg']);
        }
    }

    /**
     * 回到 上一步
     * @author Redbo He
     * @date 2020/11/6  13:22
     */
    public function back_to_quote()
    {
        $quotation_id = I('post.quotation_id');
        $all_not_approved = I('post.all_not_approved'); #  是否全部
        if(empty($quotation_id))
        {
            return $this->ajaxError([], '报价记录ID不能为空');
        }
        $quotation_model = D("Quote/OperatorQuotation");
        $quotation = $quotation_model->where([['id' => $quotation_id]])->find();
        if(!$quotation) {
            return $this->ajaxError([],'报价数据记录不存在，请检查请求数据');
        }
        if($quotation['status_cd'] != $quotation_model::STATUS_CD_WAIT_CONFIRM) {
            return $this->ajaxError([],'当前状态无法执行该操作');
        }
        $trans = M();
        $trans->startTrans();   // 开启事务
        try 
        {
             $update_data = [
                 'status_cd'     => $quotation_model::STATUS_CD_WAIT_QUOTE
             ];
             if($all_not_approved) {
                 $update_data['is_twice_quote'] = $quotation_model::IS_TWICE_QUOTE_YES;
             }
             $res = $quotation_model->where([['id' =>$quotation_id]])->data($update_data)->save();
             if($res)
             {
                if($all_not_approved)
                {
                    $quotation_scheme_model = D("Quote/QuotationScheme");
                    $quotation_scheme_model->where([
                            "object_name" => $quotation_scheme_model::OBJECT_NAME_QUOTATION,
                            "object_id" => $quotation_id
                        ])->data(["audit_status" => $quotation_scheme_model::AUDIT_STATUS_FAIL])->save();
                    # 待二次报价事项
                    $service = new QuotationService();
                    $service->pushQuoteWorkWxMessage('wait_twice_quote',$quotation);
                }
             }
             else
             {
                 $trans->rollback();
                 return $this->ajaxError([],'服务器异常，数据修改失败');
             }
        } 
        catch (\Exception $e) 
        {
            $trans->rollback();
            Log::record("【数据状态保存失败】".$e->__toString(), Log::ERR);
            return $this->ajaxError([],'服务器异常，数据修改失败');
        }
        $trans->commit();
        return $this->ajaxSuccess([],'Success');
    }

    /**
     * 商品excel数据上传与数据解析
     *
     * @author Redbo He
     * @date 2020/11/4  15:08
     */
    public function excelGoodsImport()
    {
         $result = $this->quotationRepository->excelGoodsImport();
         if(!$result['status']) {
             return $this->ajaxError($result['data'],$result['msg']);
         }else
         {
             return $this->ajaxSuccess($result['data'], $result['msg']);
         }
    }


    /**
     * 保存拼柜报价
     * @author Redbo He
     * @date 2020/11/9  15:16
     */
    public function save_quote_lcl_step1()
    {
        if(IS_POST)
        {
            $params_data  = json_decode(file_get_contents('php://input'),1);
            $quote_lcl_model = D("Quote/QuoteLcl");
            if(is_array($quote_lcl_model->create($params_data,1)))
            {
                $trans = M();
                $trans->startTrans();   // 开启事务
                try
                {
                    $res = $quote_lcl_model->add($params_data);
                    if(!$res)
                    {
                        return $this->ajaxError([],'服务器异常，数据保存失败');
                    }
                }
                catch (\Exception $e)
                {
                    $trans->rollback();
                    Log::record("【发起拼柜信息保存失败】".$e->__toString(), Log::ERR);
                    return $this->ajaxError([],'服务器异常，数据保存失败');
                }
                $trans->commit();
                return $this->ajaxSuccess($quote_lcl_model->where("id = {$res}")->find(), "拼柜数据创建成功");
            }
            else
            {
                return $this->ajaxError([], $quote_lcl_model->getError());
            }
        }
    }


    public function quote_lcl_rollback()
    {
        $lcl_id = I("post.id");
        if(empty($lcl_id)) {
            return $this->ajaxError([],"拼柜单ID不能为空");
        }
        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl = $quote_lcl_model->where("id = {$lcl_id}")->find();
        if(empty($quote_lcl)) {
            return $this->ajaxError([],"拼柜单不存在，请检查数据");
        }
        $result = $this ->quotationRepository->quoteLclRollback($lcl_id);
        if(!$result['status']) {
            return $this->ajaxError([],$result['msg']);
        }else
        {
            return $this->ajaxSuccess([], $result['msg']);
        }
    }

    /**
     * 拼柜报价详情接口
     * @author Redbo He
     * @date 2020/11/10  14:44
     */
    public function quote_lcl_detail()
    {
        $id = I('get.id');
        $result = $this->quotationRepository->quoteLclDetail($id);
        if(!$result['status']) {
            return $this->ajaxError([],$result['msg']);
        }else
        {
            return $this->ajaxSuccess($result['data'], $result['msg']);
        }
    }


    public function quote_lc_show()
    {
        return $this->quotation_detail();
    }

    /**
     * 拼柜报价-确认报价方案
     * @author Redbo He
     * @date 2020/11/12  13:44
     */
    public function quote_lcl_scheme_confirm()
    {
        if(IS_POST)
        {
            $params_data  = json_decode(file_get_contents('php://input'),1);
            $quote_lcl_scheme_confirm_data_model = D("Quote/QuoteLclSchemeConfirmData");
            $quote_lcl_id = $params_data['quote_lcl_id'];
            if(empty($quote_lcl_id)) {
                return $this->ajaxError([],"报价单ID不能为空");
            }
            $quote_lcl_model = D("Quote/QuoteLcl");
            $quote_lcl = $quote_lcl_model->where("id = {$quote_lcl_id}")->find();
            if(empty($quote_lcl)) {
                return $this->ajaxError([],"拼柜单不存在，请检查数据");
            }
            $scheme_confirm_data = $params_data['scheme_confirm_data'];
            if(empty($scheme_confirm_data)) {
                return $this->ajaxError([],"方案价格确认数据不能为空");
            }
            $check_error = '';
            foreach ($scheme_confirm_data as $item) {
                if(!$quote_lcl_scheme_confirm_data_model->create($item,1)) {
                    $check_error  = $quote_lcl_scheme_confirm_data_model->getError();
                    break;
                }
            }
            if($check_error)
            {
                return $this->ajaxError([], $check_error);
            }
            # 基础校验 校验是否 完全一致
            $quotation_scheme_ids = array_column($scheme_confirm_data, 'quotation_scheme_id');
            if(count(array_unique($quotation_scheme_ids)) != 1) {
                return $this->ajaxError([], "报价方案不一致，无法通过报价确认");
            }

            $quotation_scheme_id = array_unique($quotation_scheme_ids)[0];
            $trans = M();
            $trans->startTrans();   // 开启事务            
            try 
            {
                $res = $quote_lcl_scheme_confirm_data_model->addAll($scheme_confirm_data);
                if($res)
                {
                    ## 修改报价单方案状态已审核通过 其他的为 不通过
                    $quotation_scheme_model = D("Quote/QuotationScheme");
                    $update_data = ['audit_status' => $quotation_scheme_model::AUDIT_STATUS_SUCCESS];
                    $res3 = $quotation_scheme_model->where([['id' => $quotation_scheme_id]])->data($update_data)->save();
                    $res4 = $quotation_scheme_model->where([[
                            'object_name' => $quotation_scheme_model::OBJECT_NAME_QUOTE_LCL,
                            'object_id' => $quote_lcl_id,
                        ],
                        [ 'id' => ['neq', $quotation_scheme_id]],
                        [ 'audit_status' => ['neq', $quotation_scheme_model::AUDIT_STATUS_FAIL]]
                    ])->data([
                        'audit_status' => $quotation_scheme_model::AUDIT_STATUS_FAIL
                    ])->save();
                    # 修改 拼柜单状态信息
                    $quote_lcl_res = $quote_lcl_model->where("id = {$quote_lcl_id}")->data(['status_cd' => $quote_lcl_model::STATUS_CD_FINISH])->save();
                    # 报价单 修改状态
                    $quote_lcl_quote_relation_model = D("Quote/QuotationLclQuotationRelations");
                    $data = $quote_lcl_quote_relation_model->alias("a")
                        ->where([['quote_lcl_id' => $quote_lcl_id]])
                        ->select();
                    $quotation_ids = array_column($data,'quotation_id');
                    $quotation_model = D("Quote/OperatorQuotation");
                    # object_name  object_id
                    $quotation_update_data['status_cd'] = $quotation_model::STATUS_CD_LCL_FINISH;
                    $res2 = $quotation_model->where([['id' => ['in', $quotation_ids]]])->data($quotation_update_data)->save();
                    # 发送消息与 记录日志
                    $service = new QuotationService();
                    # 发送企业微信模板
                    $service->pushQuoteWorkWxMessage('quote_lcl_wait_shipping',$quote_lcl,'quote_clc');
                    # 发送邮件
                    $service->sendEmail($quote_lcl_id, $quotation_scheme_model::OBJECT_NAME_QUOTE_LCL);
                    # 记录日志信息
                    $quote_logs_model = D("Quote/QuoteLogs");
                    $service->saveLog($quote_logs_model::OBJECT_NAME_QUOTE_LCL,  $quote_lcl_id,"确认拼柜报价");
                }
            } 
            catch (\Exception $e) 
            {
                $trans->rollback();
                Log::record("【拼柜确认报价保存失败】".$e->__toString(), Log::ERR);
                return $this->ajaxError([],'服务器异常，数据保存失败');
            }
            $trans->commit();
            return $this->ajaxSuccess([], 'success');
        }
    }

    public function quote_lcl_back_to_quote()
    {
        $quote_lcl_id = I('post.quote_lcl_id');
        if(empty($quote_lcl_id))
        {
            return $this->ajaxError([], '拼柜ID不能为空');
        }

        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl = $quote_lcl_model->where("id = {$quote_lcl_id}")->find();
        if(!$quote_lcl) {
            return $this->ajaxError([],'拼柜数据记录不存在，请检查请求数据');
        }
        if($quote_lcl['status_cd'] != $quote_lcl_model::STATUS_CD_WAIT_CONFIRM) {
            return $this->ajaxError([],'当前状态无法执行该操作');
        }
        $trans = M();
        $trans->startTrans();   // 开启事务
        try
        {
            $update_data = [
                'status_cd'     => $quote_lcl_model::STATUS_CD_WAIT_QUOTE
            ];
            $res = $quote_lcl_model->where([['id' =>$quote_lcl_id]])->data($update_data)->save();
            if($res == false )
            {
                $trans->rollback();
                return $this->ajaxError([],'服务器异常，数据修改失败');
            }

        }
        catch (\Exception $e)
        {
            $trans->rollback();
            Log::record("【数据状态保存失败】".$e->__toString(), Log::ERR);
            return $this->ajaxError([],'服务器异常，数据修改失败');
        }
        $trans->commit();
        return $this->ajaxSuccess([],'Success');
    }

    /**
     * 取消报价单操作
     * @author Redbo He
     * @date 2021/2/7 17:48
     */
    public function cancel_quotation()
    {
        $post_params = I("post.");
        $model = D("Quote/OperatorQuotation");
        $validate =  $model->cancel_quotation_validate;
        if (is_array($model->validate($validate)->create(I('post.'), 3))) {
            $is_confirm = I('post.is_confirm',0);
            if(!$is_confirm) {
                return $this->ajaxSuccess([
                    'can_cancel' => 1,
                ],"数据校通过");
            }
            $quotation_id= $post_params['quotation_id'];
            $result  = $this->quotationRepository->cancelQuotation($quotation_id);
            if(!$result['status']) {
                return $this->ajaxError([],$result['msg']);
            }
            else
            {
                return $this->ajaxSuccess([], $result['msg']);
            }
        }
        else
        {
            $error = $model->getError();
            return $this->ajaxError([], $error);
        }

    }

    /**
     * 导出报价单
     * @author Redbo He
     * @date 2021/2/8 16:19
     */
    public function export_quotation()
    {
        $quotation_ids = I('post.quotation_ids');
        if (empty($quotation_ids)) {
            return $this->ajaxError([],'请先勾选需要导出的报价单');
        }
        # 飞待报价状态判断
        $quotation_model = D("Quote/OperatorQuotation");
        #  'a.status_cd' => ['eq', $quotation_model::STATUS_CD_WAIT_QUOTE],
        # #  数据导出状态判断
        $in_string = implode(',', $quotation_ids);
        $where_in_string = " id in ($in_string) ";
        $quotations = $quotation_model->field("id, quote_no, status_cd")
            ->whereString($where_in_string)
            ->where([
               'status_cd' => ['eq', $quotation_model::STATUS_CD_WAIT_QUOTE],
        ])->select();
        if(empty($quotations)) {
            return $this->ajaxError([],'勾选报价单状态有非待报价报价单，不可导出！');
        }
        if(count($quotation_ids) != count($quotations)) {
            return $this->ajaxError([],'勾选报价单状态有非待报价报价单，不可导出！');
        }

        $export_data = $this->quotationRepository->getExportData($quotation_ids);
        $quotation_export = new QuotationExport();
        $quotation_export->setData($export_data)
                         ->download();
    }

    /**
     * 报价单取消操作
     * @author Redbo He
     * @date 2021/4/6 14:11
     */
    public function cancel_quote_lcl()
    {
        $quote_lcl_id = I('post.quote_lcl_id');
        if(empty($quote_lcl_id))
        {
            return $this->ajaxError([], '拼柜ID不能为空');
        }

        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl = $quote_lcl_model->where("id = {$quote_lcl_id}")->find();
        if(!$quote_lcl) {
            return $this->ajaxError([],'拼柜数据记录不存在，请检查请求数据');
        }

        $res = $this->quotationRepository->cancel_quote_lcl($quote_lcl_id);
        if(!$res['status']) {
            return $this->ajaxError([],$res['msg']);
        }
        else
        {
            return $this->ajaxSuccess([], $res['msg']);
        }


    }

    /**
     * 发送邮件测试
     * @author Redbo He
     * @date 2021/3/10 11:25
     */
    public function sendEmail()
    {
        $service = new QuotationService();
        # ，quote_lcl：拼柜，quotation：
        $object_name = I('get.object_name','quotation');
        $object_id   = I('get.object_id');
        $res = $service->sendEmail($object_id, $object_name);
        return $res;

    }
    public function testMsg()
    {
        //$res = ApiModel::WorkWxSendMessage($wid, "用户 {$user_name} 刚刚查看了您的店铺 {$store_data["STORE_NAME"]} （编号：{$store_data["ID"]} ）的密码，请知悉");
//        $send_email    = 'shenmo';
        $send_email    = 'Redbo.He';
        $data = ">**待报价事项** 
> 运营已提交最新的询价信息，请尽快报价
>报价发起人  ：<font color=info >Robie Pei</font> 
>报价发起时间：<font color=warning >2020-11-17 16:57:24</font> 
报价单号    ：<font color=info >BJ202011040001</font> 
>请尽快处理。 
>如需查看详情，请点击：[报价详情](http://erp.gshopper.stage.com/index.php?m=quote&a=quotation_management_detail&id=41)";
        $res = ApiModel::WorkWxSendMarkdownMessage($send_email, $data);
        dd($res);

        $wx_return_res = (new ApiModel())->WorkWxMessage($send_email, $data);
    }

    public function option_test()
    {

        dd( CommonDataModel::logisticsSupplier());
    }
}

