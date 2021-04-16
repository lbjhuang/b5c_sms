<?php

import('ORG.Util.Page');// 导入分页类
import('ORG.Util.Date');// 导入日期类
class QuotationRepository extends Repository
{
    public function getQuoteOptions()
    {
        $model = D("Quote/OperatorQuotation");
        $data  = [
            'quote_types'           => $model::$quote_type_str_map,
            'quote_intention_types' => $model::$quote_intention_type_map,
        ];
        return $data;
    }


    public function quotationDetail($id)
    {
        $model     = D("Quote/OperatorQuotation");
        $quotation = $this->getQuotation($id);
        if (!$quotation) {
            return ['status' => false, 'msg' => '数据不存在，请检查参数是否有误'];
        }
        $goods = $inquiries = $packing_info = $quotation_schemes = [];
        ### 商品信息查询 如果是提前报价数据
        if ($quotation['quote_type'] == $model::QUOTE_TYPE_PRE) {
            $goods        = $this->getPreQuoteGoods($id);
            $packing_info = $this->getPreQuotePackingInfo($id);
        } else if ($quotation['quote_type'] == $model::QUOTE_TYPE_NORMAL) {
            $goods        = $this->getNormalQuoteGoods($id); # 商品信息
            $packing_info = $this->getNormalQuotePackingInfo($id);
        }
        $inquiries         = $this->getQuoteInquiries($id); # 询价信息
        $quotation_schemes = $this->getQuoteSchemes($id);
        $schemes_options = [];
        # 报价单处于待确认状态 需要 提供 options 数据给前端
        if ($quotation['status_cd'] == $model::STATUS_CD_WAIT_CONFIRM) {
            $schemes_options        = [];
            $quotation_scheme_model = D("Quote/QuotationScheme");
            $scheme_type_str_map = $quotation_scheme_model::$scheme_type_str_map;
            $quotation_scheme_data = [];
            foreach ($quotation_schemes as $item) {
                $quotation_scheme_data[$item['scheme_type']][] = $item;
            }
            foreach ($quotation_scheme_data as $k => $quotation_data_schemes) {
                # 所有方案可再次选择
                foreach ($quotation_data_schemes as $kk =>  $quotation_scheme) {
                    $schemes_options[$quotation_scheme['id']] = [
                        'scheme_id' => $quotation_scheme['id'],
                        "val"       =>  $scheme_type_str_map[$quotation_scheme['scheme_type']] ."-方案" .  ($kk + 1)
                    ];
                }
            }
        }
        $data = [
            'quotation'         => $quotation,
            'goods'             => $goods,
            'inquiry'           => $inquiries,
            'packing_info'      => $packing_info,
            'quotation_schemes' => $quotation_schemes,
            'schemes_options'   => $schemes_options
        ];
        return ['status' => true, 'msg' => 'success', 'data' => $data];
    }

    public function getQuotation($quotation_id)
    {
        $model     = D("Quote/OperatorQuotation");
        $quotation = $model->where([['id' => $quotation_id]])->find();
        if(!$quotation) {
            return false;
        }
        if($quotation) $quotation = $this->formatQuotation($quotation);

        $quotation['allo_nos'] = $quotation['allo_out_team_val'] = '';
        if ($quotation['quote_type'] == $model::QUOTE_TYPE_NORMAL)
        {
            // 获取匹配单号
            $quote_wms_allos  = $model->relationGet("quote_wms_allos");
            $quote_wms_allos = array_column($quote_wms_allos,'allo_no');
            $quotation_wms_allos = $model->relationGet('quotation_wms_allos');
            if($quotation_wms_allos) {
                foreach ($quotation_wms_allos as &$quotation_wms_allo) {
                    $quotation_wms_allo = CodeModel::autoCodeOneVal($quotation_wms_allo,
                        ['allo_out_team',]
                    );
                }
                $allo_out_team_vals = implode(',',array_unique(array_column($quotation_wms_allos,'allo_out_team_val')));
                $quotation['allo_out_team_val'] = $allo_out_team_vals;
            }
            $quotation['allo_nos'] = $quote_wms_allos ? implode(",", $quote_wms_allos) : '';
        }
        return $quotation;
    }
    /**
     * 获取提前报价 报价单商品信息
     * @param $quotation_id
     * @return mixed
     * @author Redbo He
     * @date 2020/11/5  16:50
     */
    public function getPreQuoteGoods($quotation_id)
    {
        $quote_goods_model = D("Quote/QuoteGoods");
        return  $quote_goods_model->where([
            "quotation_id" => $quotation_id
        ])->order("id ASC")->select();
    }

    public function getNormalQuoteGoods($quotation_id, $get_allo = false)
    {
        $model     = D("Quote/OperatorQuotation");
        $quotation = $this->getQuotation($quotation_id);
        if(in_array($quotation['status_cd'],[$model::STATUS_CD_FINISH, $model::STATUS_CD_LCL_FINISH]) && $quotation['execute_status'] == 1 && !$get_allo) {
            $quote_goods_model = D("Quote/QuoteGoods");
            $data  = $quote_goods_model->where([
                "quotation_id" => $quotation_id
            ])
                ->order("id ASC")->select();
        }
        else
        {
            $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
            $data = $quotation_wms_allo_model->field([
                "tb_quote_wms_allo.id",
                "tb_quote_wms_allo.quotation_id",
                "tb_quote_wms_allo.allo_id",
                "tb_quote_wms_allo.allo_no",
                "tb_wms_allo_new_guds_infos.sku_id",
                "tb_wms_allo_child.demand_allo_num as good_number",
                "tb_quote_wms_allo.created_by",
                "0.00 as logistics_price",
                " '' as logistics_price_currency_cd",
                "tb_quote_wms_allo.created_at",
            ])->join(" inner join tb_wms_allo_new_guds_infos on tb_quote_wms_allo.allo_id = tb_wms_allo_new_guds_infos.allo_id ")
                ->join(" inner join tb_wms_allo_child on tb_wms_allo_new_guds_infos.allo_id = tb_wms_allo_child.allo_id AND tb_wms_allo_child.sku_id = tb_wms_allo_new_guds_infos.sku_id ")
                ->where(["tb_quote_wms_allo.quotation_id" => $quotation_id])
                ->select();
        }
        $data = SkuModel::getInfo($data, 'sku_id',
            ['spu_name', 'attributes',]
        );
        foreach ($data as &$item) {
            if(empty($item['good_name'])) {
                $item['good_name'] = $item['spu_name'];
                unset($item['spu_name']);
            }
        }
        return $data;

    }


    public function getQuoteInquiries($quotation_id)
    {
        $inquiries_model          = D("Quote/QuoteInquiries");
        $data                     = $inquiries_model->alias("a")
            ->field("a.*,b.quote_intention_type")
            ->join(" inner join tb_quotation as b  on a.quotation_id = b.id ")
            ->where([
                "a.quotation_id" => $quotation_id
            ])->find();
        if ($data)
        {
            $model                    = D("Quote/OperatorQuotation");
            $quote_intention_type_map = $model::$quote_intention_type_map;
            $data['quote_intention_type_name'] = isset($quote_intention_type_map[$data['quote_intention_type']]) ? $quote_intention_type_map[$data['quote_intention_type']] : '';
            $data['transport_supplier_id'] = $data['transport_supplier_id'] ? $data['transport_supplier_id'] : '';
        }

        return $data;
    }

    public function getNormalQuotePackingInfo($quotation_id)
    {
        $quote_wms_allo_model  = D("Quote/QuoteWmsAllo");
        $data = $quote_wms_allo_model->field([
                    "tb_quote_packing_information.id",
                    "tb_quote_wms_allo.quotation_id", "tb_quote_wms_allo.allo_id","tb_quote_wms_allo.allo_no",
                    "tb_wms_allo_new_works.total_box_num", "tb_wms_allo_new_works.total_volume", "tb_wms_allo_new_works.total_weight",
                    "IFNULL(tb_quote_packing_information.allo_in_warehouse,tb_wms_allo.allo_in_warehouse ) as  allo_in_warehouse",
                    "tb_quote_packing_information.allo_in_warehouse_address",
                    "IFNULL(tb_quote_packing_information.allo_out_warehouse,tb_wms_allo.allo_out_warehouse ) as  allo_out_warehouse",
                    "tb_quote_packing_information.allo_out_warehouse_address",
                    "tb_quote_packing_information.declare_type_cd", "tb_quote_packing_information.is_electric_cd"
                    ])
                    ->join(" inner join tb_wms_allo on tb_quote_wms_allo.allo_id = tb_wms_allo.id ")
                    ->join(" LEFT  join tb_wms_allo_new_works on tb_quote_wms_allo.allo_id = tb_wms_allo_new_works.allo_id ")
                    ->join(" LEFT join tb_quote_packing_information on tb_quote_wms_allo.quotation_id = tb_quote_packing_information.quotation_id AND tb_quote_packing_information.allo_no = tb_quote_wms_allo.allo_no ")
                    ->where([
                        "tb_quote_wms_allo.quotation_id" => $quotation_id
                    ])->select();
        $warehouses  = CommonDataModel::warehouses();
        foreach ($data as $k => &$item) {
            $item = CodeModel::autoCodeOneVal($item,
                ['declare_type_cd', 'is_electric_cd','allo_in_warehouse','allo_out_warehouse']
            );

            if(isset($warehouses[$item['allo_in_warehouse']]) && is_null($item['allo_in_warehouse_address'])) {
                $item['allo_in_warehouse_address'] = $warehouses[$item['allo_in_warehouse']]['place_address'];
            }

            if(isset($warehouses[$item['allo_out_warehouse']]) && is_null($item['allo_out_warehouse_address'])) {
                $item['allo_out_warehouse_address'] = $warehouses[$item['allo_out_warehouse']]['place_address'];
            }


        }
        return $data;
    }


    public function getPreQuotePackingInfo($quotation_id)
    {
        $packing_information_model =  D("Quote/QuotePackingInformation");
        $data =  $packing_information_model->where([
            "quotation_id" => $quotation_id
        ])->select();

        foreach ($data as &$item) {
            $item = CodeModel::autoCodeOneVal($item,
                ['declare_type_cd', 'is_electric_cd','allo_in_warehouse','allo_out_warehouse']
            );
        }
        return $data ? $data : [];
    }

    public function getQuoteSchemes($quotation_id, $object_name = 'quotation', $audit_status = null)
    {
        $quotation_scheme_model = D("Quote/QuotationScheme");
        $where = [
            "object_name" => $object_name,
            "object_id"   => $quotation_id
        ];

        if($audit_status) {
            $where['audit_status'] = $audit_status;
        }

        $quote_schemes = $quotation_scheme_model->where($where)->select();
        if($quote_schemes)
        {
            $scheme_ids = array_column($quote_schemes,'id');
            $quotation_scheme_detail_model = D("Quote/QuotationSchemeDetail");
            $quote_schemes_details = $quotation_scheme_detail_model->where([
                'quotation_scheme_id' => ['in', $scheme_ids]
            ])->order("id ASC")->select();
            #
            $quote_schemes_data = [];
            foreach ($quote_schemes_details as $quote_schemes_detail)
            {
                if( $quote_schemes_detail['quotation_ids']) {
                    $quote_schemes_detail['quotation_ids'] = explode(",", $quote_schemes_detail['quotation_ids']);
                }
                $quote_schemes_data[$quote_schemes_detail['quotation_scheme_id']][] = $quote_schemes_detail;
            }

            $show_scheme_name_types = $quotation_scheme_model::$show_scheme_name_types;
            $scheme_type_str_map = $quotation_scheme_model::$scheme_type_str_map;
            foreach ($quote_schemes as $key =>  &$quote_scheme)
            {
                $scheme_name = '报价信息';
                if(in_array($quote_scheme['scheme_type'], $show_scheme_name_types)) {
                    $scheme_name .= '-方案'. ($key +1);
                }

                if($object_name == $quotation_scheme_model::OBJECT_NAME_QUOTATION ) {
                    $scheme_name .= '-'.$scheme_type_str_map[$quote_scheme['scheme_type']];
                }
                $quote_scheme['scheme_name'] = $scheme_name;
                if(isset($quote_schemes_data[$quote_scheme['id']])) {
                    $quote_scheme['scheme_detail'] = $quote_schemes_data[$quote_scheme['id']];
                }
            }
        }
        return $quote_schemes;
    }

    /**
     * 报价方案
     * @param $scheme_id
     * @author Redbo He
     * @date 2020/11/5  19:28
     */
    public  function quotation_scheme_conform($scheme_id)
    {
        $quotation_scheme_model = D("Quote/QuotationScheme");
        $quotation_scheme = $quotation_scheme_model->where([
           [ 'id' => $scheme_id]
        ])->find();
        if(empty($quotation_scheme)) {
            return ['status' => false, 'msg' => '报价方案记录不存在，请检查参数'];
        }
        $update_data = [
            'audit_status' => $quotation_scheme_model::AUDIT_STATUS_SUCCESS
        ];
        try
        {
            $res = $quotation_scheme_model->where([['id' => $scheme_id]])->data($update_data)->save();
            if($res)
            {
                if($quotation_scheme['object_name'] == $quotation_scheme_model::OBJECT_NAME_QUOTATION)
                {
                    $model = D("Quote/OperatorQuotation");
                    $quotation = $model->where([['id' => $quotation_scheme['object_id']]])->find();
                    $res2 = $model->where([['id' => $quotation_scheme['object_id']]])->data([
                        'status_cd' => $model::STATUS_CD_FINISH
                    ])->save();
                    # 记录日志信息
                    $quotation = $model->where([['id' => $quotation_scheme['object_id']]])->find();
                    $content = "确认报价";
                    $service = new QuotationService();
                    if($quotation["is_twice_quote"])
                    {
                        $content = "二次报价确认";
                    }
                    $service->pushQuoteWorkWxMessage('wait_shipping',$quotation); # 待发货事项
                    $service->saveLog($quotation_scheme['object_name'],  $quotation_scheme['object_id'],$content);

                    # 发送邮件
                    $res = $service->sendEmail( $quotation_scheme['object_id'],$quotation_scheme_model::OBJECT_NAME_QUOTATION );
                }
                $res3 = $quotation_scheme_model->where([
                    [
                        'object_name' => $quotation_scheme['object_name'],
                        'object_id' => $quotation_scheme['object_id'],
                    ],
                    [ 'id' => ['neq', $scheme_id]]
                ])->data([
                    'audit_status' => $quotation_scheme_model::AUDIT_STATUS_FAIL
                ])->save();


            }
            else
            {
                return ['status' => false, 'msg' => "数据更新失败，请稍后再试"];
            }
        } 
        catch (\Exception $e) 
        {
            Log::record("【方案信息更新失败】".$e->__toString(), Log::ERR);
            return ['status' => false, 'msg' => "数据更新失败，请稍后再试"];
        }
        ### 更新
        return ['status' => true, 'msg' => "数据更新成功"];

    }

    /**
     * 获取柜报报价报价下拉框数据
     * @author Redbo He
     * @date 2020/11/9  14:29
     */
    public function get_quotation_lcl_options()
    {
        //  报价单号处于待报价状态；支持多选，无上限，但是有下限，关联的报价单号必须大于1
        #  报价单号之前没被拼柜过、报价单号处于待报价状态
        $quotation_model = D("Quote/OperatorQuotation");
        $map = [
            'status_cd' => $quotation_model::STATUS_CD_WAIT_QUOTE,
            "_string" => "not exists ( SELECT 1 FROM tb_quote_lcl_quotation_relations inner join tb_quote_lcl on tb_quote_lcl_quotation_relations.quote_lcl_id = tb_quote_lcl.id WHERE tb_quotation.id = tb_quote_lcl_quotation_relations.quotation_id
		and tb_quote_lcl.deleted_at is null )"
        ];
        $data = $quotation_model->field("id,quote_no,status_cd")
                            ->where($map)
                            ->select();
        return $data ? $data : [];
    }


    /**
     * excel 数据导入
     * @author Redbo He
     * @date 2020/11/4  13:44
     */
    public function excelGoodsImport()
    {
        if(isset($_FILES['excel']) && $_FILES['excel']['error'] == 0)
        {
            session_write_close();
            ini_set('date.timezone', 'Asia/Shanghai');
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['excel']['tmp_name'];  //导入的excel路径
            vendor("PHPExcel.PHPExcel");
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    echo 'no Excel';
                    return;
                }
            }
            $PHPExcel = $PHPReader->load($filePath); // 文件名称
            $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表从0读起
            $rows = $sheet->getHighestRow();//行数
            $cols = $sheet->getHighestColumn();//列数
            $cols_map = [
                'A' => 'good_name',
                'B' => 'good_number',
            ];
            ### 数据校验
            $list = $errors = array();
            $index = 1;
            for ($row = 2; $row <= $rows; $row++){ //行数是以第2行开始
                for ($col = 'A'; $col <= $cols; $col++) {
                    $cel = $col . $row;
                    $column_name = isset($cols_map[$col]) ? $cols_map[$col] : '';
                    $val = $sheet->getCell($col . $row)->getValue();
                    $list[$row][$column_name] = $val;
                }
                $index ++;
            }

            # 数据校验
            if(empty($list)) {
                return ['status' => false, 'msg' => 'excel 导入数据为空，请检查excel','data' => []];
            }

            ###  数据校验
            $goods_names = array_column($list,'good_name');
            $goods_count_values = array_count_values($goods_names);
            foreach ($list as $row => $item)
            {
                $col = 'A';
                foreach ($item as $key => $val)
                {
                    # 商品名称检查是否有重复
                    $tmp = [
                        'row' => $row,
                        'col' => $col,
                        'cell' => $col. $row,
                    ];
                    if($key == 'good_name') {
                        if(empty($val)) {
                            $tmp['val'] = $val;
                            $tmp['msg'] = "[商品名称]值不能为空";
                            $errors[] = $tmp;
                        }
                        if($goods_count_values[$val] >  1) {
                            $tmp['val'] = $val;
                            $tmp['msg'] = "[商品名称]值重复，请检查";

                            $errors[] = $tmp;
                        }

                    }
                    # 商品数量是否输入有误
                    if($key == 'good_number')  {
                        if(!is_numeric($val) || empty($val)) {
                            $tmp['val'] = $val;
                            $tmp['msg'] = '[数量]值输入有误，请检查';
                            $errors[] = $tmp;
                        }
                    }
                    $col ++ ;
                }
            }
            if($errors) {
                return ['status' => false, 'msg' => '数据解析有问题，请重新导入','data' => $errors];
            }
            $list = array_values($list);
            return ['status' => true , 'msg' => 'success','data' => $list];
        }
        return ['status'=> false,'msg' => '文件上传失败','data' => []];
    }


    /**
     * 获取拼柜数据详情
     * @param $lcl_id
     * @author Redbo He
     * @date 2020/11/10  14:50
     */
    public function quoteLclDetail($lcl_id)
    {
        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl_data = $this->getQuoteLcl($lcl_id);
        if(!$quote_lcl_data)
        {
            return ['status' => false, 'msg' => '数据不存在，请检查参数是否有误'];
        }

        # 查詢拼柜商品
        $quote_lcl_goods = $this->getQuoteLclGoods($lcl_id, $quote_lcl_data);
        # # inquiries  $pack 获取询价 装箱信息
        $quote_lcl_inquiry_pack = $this->getQuoteLclInquiryPackInfo($lcl_id);
        # 装箱数据-总
        $quote_lcl_total_pack_info = $this->getQuoteLclTotalPackInfo($lcl_id);
        # 报价方案信息
        $quote_lcl_schemes = $this->getQuoteSchemes($lcl_id,'quote_lcl');
        $schemes_options = [];

        # 报价单处于待确认状态 需要 提供 options 数据给前端
        if (in_array($quote_lcl_data['status_cd'], [$quote_lcl_model::STATUS_CD_WAIT_CONFIRM, $quote_lcl_model::STATUS_CD_FINISH])) {
            $schemes_options        = [];
            $quotation_scheme_model = D("Quote/QuotationScheme");
            $scheme_type_str_map = $quotation_scheme_model::$scheme_type_str_map;
            foreach ($quote_lcl_schemes as $k => $quote_lcl_scheme) {
                # 所有的方案 产品说了 所有方案再次可选
                $schemes_options[$quote_lcl_scheme['id']] = [
                    'scheme_id' => $quote_lcl_scheme['id'],
                   #  "val"       => $scheme_type_str_map[$quote_lcl_scheme['scheme_type']] ."-方案" .  ($k + 1)
                    "val"       => '报价信息' ."--方案" .  ($k + 1)
                ];
            }

        }
        $quote_lcl_data['transportation_channel_cd_val'] = '';

        # 获取拼柜 报价单基本信息 用来显示 确认报价信息
        $quote_lcl_scheme_check_data = $this->getQuoteLclSchemeConfirmData($lcl_id);
        $data = [
            'quote_lcl'            => $quote_lcl_data,
            'goods'                => $quote_lcl_goods,
            'inquiries_packs'      => $quote_lcl_inquiry_pack,
            'pack_total_info'      => $quote_lcl_total_pack_info,
            'quote_lcl_schemes'    => $quote_lcl_schemes,
            'scheme_confirm_data'  => $quote_lcl_scheme_check_data,
            'schemes_options'      => $schemes_options,
        ];
        return ['status' => true , 'msg' => 'Success','data'=> $data];
    }

    public function getQuoteLcl($lcl_id)
    {
        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl_data = $quote_lcl_model->where("id = {$lcl_id}")->find();
        if(!$quote_lcl_data)
        {
            return false;
        }
        $quote_lcl_data = CodeModel::autoCodeOneVal($quote_lcl_data,
            ['status_cd',]
        );

        $quote_lcl_quotations = $quote_lcl_model->relationGet("quote_lcl_quotations");
        $model = D("Quote/OperatorQuotation");
        $quote_type_normal = $model::QUOTE_TYPE_NORMAL;
        $allo_quotation_ids = array_map(function($v) use ($quote_type_normal) {
                if($v['quote_type'] == $quote_type_normal) {
                    return $v['id'];
                }
                return 0;
        }, $quote_lcl_quotations);
        $quote_lcl_data['relation_allo_nos'] = '';
        $quote_lcl_quotations = CodeModel::autoCodeTwoVal($quote_lcl_quotations, [
            "small_team_cd"]);
        $allo_quotation_ids = array_filter($allo_quotation_ids);
        # 查询调拨单信息
        $quote_lcl_data['relation_quoation_allo_no_map'] = [];
        if($allo_quotation_ids) {
            $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
            $quotation_wms_allo_data = $quotation_wms_allo_model->field("id,quotation_id,allo_id,allo_no")
                    ->where([["quotation_id" => ["in", $allo_quotation_ids]]])
                    ->select();
            $quote_lcl_data['relation_allo_nos'] = implode(",", array_unique(array_column($quotation_wms_allo_data,'allo_no')));
            $quote_lcl_data['relation_quotation_allo_no_map']  = array_column($quotation_wms_allo_data,'allo_no','quotation_id');
        }
        $quote_lcl_data['relation_quote_nos'] = implode(",", array_unique(array_column($quote_lcl_quotations,'quote_no')));
        $quote_lcl_data['relation_small_team_cds'] = implode(",", array_unique(array_column($quote_lcl_quotations,'small_team_cd')));
        $quote_lcl_data['relation_created_byes'] = implode(",", array_unique(array_column($quote_lcl_quotations,'created_by')));
        $quote_lcl_data['relation_created_ids'] = implode(",", array_unique(array_column($quote_lcl_quotations,'creator_id')));
        $quote_lcl_data['relation_small_team_vals'] = implode(",", array_unique(array_column($quote_lcl_quotations,'small_team_cd_val')));
        return $quote_lcl_data;
    }

    /**
     * 获取拼柜 报价 询价 装箱信息
     * @param $lcl_id
     * @author Redbo He
     * @date 2020/11/10  17:32
     */
    public function getQuoteLclInquiryPackInfo($lcl_id)
    {
        $quote_lcl_inquiries = $this->getQuoteLclInquiries($lcl_id);
        $quote_lcl_pre_pack = $this->getPreQuoteLclPackingInfo($lcl_id);
        $quote_lcl_normal_pack = $this->getNormalQuoteLclPackingInfo($lcl_id);

        $quote_lcl_normal_packs = [];
        foreach ($quote_lcl_normal_pack as $item) {
            $quote_lcl_normal_packs[$item['quotation_id']][] = $item;
        }
        $quote_lcl_pre_packs = [];
        foreach ($quote_lcl_pre_pack as $item2) {
            $quote_lcl_pre_packs[$item2['quotation_id']][]  = $item2;
        }

        $model     = D("Quote/OperatorQuotation");
        $quote_type_str_map  = $model::$quote_type_str_map;
        # 组装返回数据格式
        $result = [];
        foreach ($quote_lcl_inquiries as $quote_lcl_inquiry)
        {
            $quote_normal_pack = [];
            if($quote_lcl_inquiry['quote_type'] == $model::QUOTE_TYPE_NORMAL) {
                $quote_normal_pack = isset($quote_lcl_normal_packs[$quote_lcl_inquiry['quotation_id']]) ? $quote_lcl_normal_packs[$quote_lcl_inquiry['quotation_id']] : [];
            }
            else if ($quote_lcl_inquiry['quote_type'] == $model::QUOTE_TYPE_PRE) {
                $quote_normal_pack = isset($quote_lcl_pre_packs[$quote_lcl_inquiry['quotation_id']]) ? $quote_lcl_pre_packs[$quote_lcl_inquiry['quotation_id']] : [];
            }
            $quote_lcl_inquiry['quote_type_name'] = $quote_type_str_map[$quote_lcl_inquiry['quote_type']];
            $quote_lcl_inquiry['quote_lcl_pack_info'] = $quote_normal_pack;

            ## 处理报价类型
            $result[] = $quote_lcl_inquiry;
        }
        return $result;
//        dd($quote_lcl_inquiries);
//        $result = [
//            $model::QUOTE_TYPE_NORMAL => [],
//            $model::QUOTE_TYPE_PRE => [],
//        ];
//        dd($result);
    }

    /**
     * 获取拼柜单询价信息
     * @param $lcl_id
     * @return array|bool|mixed|string|null
     * @author Redbo He
     * @date 2020/11/10  19:27
     */
    public function getQuoteLclInquiries($lcl_id)
    {
        $inquiries_model = D("Quote/QuoteInquiries");
        $inquiries =  $inquiries_model
            ->field("tb_quote_inquiries.*,tb_quotation.quote_no,tb_quote_lcl_quotation_relations.quote_lcl_id,tb_quotation.quote_type,tb_quotation.quote_intention_type")
            ->join("INNER JOIN tb_quote_lcl_quotation_relations ON tb_quote_inquiries.quotation_id = tb_quote_lcl_quotation_relations.quotation_id ")
            ->join("INNER JOIN tb_quotation ON tb_quote_inquiries.quotation_id = tb_quotation.id ")
            ->where([
                "tb_quote_lcl_quotation_relations.quote_lcl_id" => $lcl_id
            ])->select();
        $model     = D("Quote/OperatorQuotation");
        $logisticsSupplier = CommonDataModel::logisticsSupplier();
        $logisticsSupplier = array_column($logisticsSupplier,'SP_RES_NAME_EN','ID');
        $quote_intention_type_map = $model::$quote_intention_type_map;
        foreach ($inquiries as $k => $inquiry) {
            $inquiry =  CodeModel::autoCodeOneVal($inquiry,
                ['planned_transportation_channel_cd']
            );
            $inquiry['transport_supplier_id_val'] = isset($logisticsSupplier[$inquiry['transport_supplier_id']]) ? $logisticsSupplier[$inquiry['transport_supplier_id']] : '';
            $inquiry['quote_intention_type_name'] = isset($quote_intention_type_map[$inquiry['quote_intention_type']]) ? $quote_intention_type_map[$inquiry['quote_intention_type']] : '';
            $inquiries[$k] = $inquiry;
        }
        return $inquiries;
    }

    public function getPreQuoteLclPackingInfo($lcl_id)
    {
        $packing_information_model =  D("Quote/QuotePackingInformation");

        $model = D("Quote/OperatorQuotation");
        $quote_type_pre = $model::QUOTE_TYPE_PRE;
        $where = [
            "tb_quote_lcl_quotation_relations.quote_lcl_id" => $lcl_id,
            "tb_quotation.quote_type" => $quote_type_pre
        ];
        $data =  $packing_information_model
                ->field("tb_quote_packing_information.*")
                ->join("INNER JOIN tb_quote_lcl_quotation_relations ON tb_quote_packing_information.quotation_id = tb_quote_lcl_quotation_relations.quotation_id ")
                ->join("INNER JOIN tb_quotation ON tb_quote_packing_information.quotation_id = tb_quotation.id ")
                ->where($where)->select();
        foreach ($data as &$item) {
            $item = CodeModel::autoCodeOneVal($item,
                ['declare_type_cd', 'is_electric_cd','allo_in_warehouse','allo_out_warehouse']
            );
        }
        return $data ? $data : [];
    }

    public function getNormalQuoteLclPackingInfo($lcl_id)
    {
        $quote_wms_allo_model  = D("Quote/QuoteWmsAllo");
        $data = $quote_wms_allo_model->field([
            "tb_quote_packing_information.id",
            "tb_quote_wms_allo.quotation_id", "tb_quote_wms_allo.allo_id","tb_quote_wms_allo.allo_no",

            "tb_wms_allo_new_works.total_box_num", "tb_wms_allo_new_works.total_volume", "tb_wms_allo_new_works.total_weight",
            "tb_quote_packing_information.allo_in_warehouse", "tb_quote_packing_information.allo_in_warehouse_address",
            "tb_quote_packing_information.allo_out_warehouse", "tb_quote_packing_information.allo_out_warehouse_address",
            "tb_quote_packing_information.declare_type_cd", "tb_quote_packing_information.is_electric_cd"
        ])
            ->join(" inner join tb_wms_allo_new_works on tb_quote_wms_allo.allo_id = tb_wms_allo_new_works.allo_id ")
            ->join(" inner join tb_quote_lcl_quotation_relations on tb_quote_wms_allo.quotation_id = tb_quote_lcl_quotation_relations.quotation_id ")
            ->join(" inner join tb_quote_packing_information on tb_quote_wms_allo.quotation_id = tb_quote_packing_information.quotation_id AND tb_quote_packing_information.allo_no = tb_quote_wms_allo.allo_no")
            ->where([
                "tb_quote_lcl_quotation_relations.quote_lcl_id" => $lcl_id
            ])->select();
        foreach ($data as $k => &$item) {
            $item = CodeModel::autoCodeOneVal($item,
                ['declare_type_cd', 'is_electric_cd','allo_in_warehouse','allo_out_warehouse']
            );
        }
        return $data;
    }

    /**
     * 查询商品信息
     * @param $lcl_id
     * @return mixed
     * @author Redbo He
     * @date 2020/11/10  16:40
     */
    public function getQuoteLclGoods($lcl_id, $quote_lcl_data = [], $get_allo = false)
    {
        if(empty($quote_lcl_data)) {
            $quote_lcl_data = $this->getQuoteLcl($lcl_id);
        }
        $model = D("Quote/QuoteLcl");
        if($quote_lcl_data['status_cd'] == $model::STATUS_CD_FINISH && $quote_lcl_data['execute_status'] == 1 && !$get_allo) {
            $query_sql  = " SELECT tb_quote_goods.id,  tb_quote_goods.quotation_id,  sku_id,tb_quote_goods.good_name,tb_quote_goods.good_number,
                            execute_status, remark,logistics_price,logistics_price_currency_cd
                        FROM tb_quote_goods 
                        INNER JOIN tb_quote_lcl_quotation_relations ON tb_quote_goods.quotation_id = tb_quote_lcl_quotation_relations.quotation_id
                        WHERE tb_quote_lcl_quotation_relations.quote_lcl_id = '{$lcl_id}' ";
        }
        else
        {
            $quotation_model = D("Quote/OperatorQuotation");
            $quote_type_pre = $quotation_model::QUOTE_TYPE_PRE;
            $wms_allo_goods_query_sql = "SELECT
                tb_wms_allo_new_guds_infos.id,
                tb_quote_wms_allo.quotation_id,
                tb_wms_allo_new_guds_infos.sku_id,
                '' as good_name,
                tb_quote_wms_allo.allo_no,
                tb_wms_allo_child.demand_allo_num AS good_number,
                0 as execute_status,
                '' as remark,
                '0.000000' as logistics_price,
                '' as logistics_price_currency_cd
            FROM tb_quote_wms_allo
            INNER JOIN tb_quote_lcl_quotation_relations ON tb_quote_wms_allo.quotation_id = tb_quote_lcl_quotation_relations.quotation_id
            INNER JOIN tb_wms_allo_new_guds_infos ON tb_quote_wms_allo.allo_id = tb_wms_allo_new_guds_infos.allo_id
            INNER JOIN tb_wms_allo_child ON tb_wms_allo_new_guds_infos.allo_id = tb_wms_allo_child.allo_id AND tb_wms_allo_child.sku_id = tb_wms_allo_new_guds_infos.sku_id 
            WHERE tb_quote_lcl_quotation_relations.quote_lcl_id = '{$lcl_id}' ";
            $quote_good_query_sql = " SELECT tb_quote_goods.id, tb_quote_goods.quotation_id, sku_id,tb_quote_goods.good_name, '' as allo_no,tb_quote_goods.good_number, 
                            tb_quote_goods.execute_status, tb_quote_goods.remark,logistics_price,logistics_price_currency_cd
                        FROM tb_quote_goods 
                        INNER JOIN tb_quote_lcl_quotation_relations ON tb_quote_goods.quotation_id = tb_quote_lcl_quotation_relations.quotation_id
                        INNER JOIN tb_quotation ON tb_quote_goods.quotation_id = tb_quotation.id AND tb_quotation.quote_type = {$quote_type_pre}
                        WHERE tb_quote_lcl_quotation_relations.quote_lcl_id = '{$lcl_id}' ";
            $query_sql  = "select * from ( ({$wms_allo_goods_query_sql}) UNION ALL ({$quote_good_query_sql}) ) as query_tmp order by query_tmp.id desc";
        }
        $model = M();
        $goods  = $model->query($query_sql);
        $goods = SkuModel::getInfo($goods, 'sku_id',
            ['spu_name',]
        );
        foreach ($goods as &$good) {
            if($good['sku_id']) {
                $good['good_name'] = $good['spu_name'];
            }
            else
            {
                $good['sku_id'] = '';
            }
            unset($good['spu_name']);
        }
        return $goods;
    }

    /**
     * 获取装箱数据-总数据
     * @param string $lcl_id 拼柜记录ID
     * @return mixed
     * @author Redbo He
     * @date 2020/11/11  20:35
     */
    public function getQuoteLclTotalPackInfo($lcl_id)
    {
        $quote_lcl_quote_relation_model = D("Quote/QuotationLclQuotationRelations");
        $data = $quote_lcl_quote_relation_model->alias("a")
                ->field("  
                      a.id as relation_id, a.quotation_id, b.quote_no,
                      group_concat(c.allo_no) as allo_nos,
                      SUM(c.total_box_num) as all_total_box_num,
                      SUM(c.total_volume) as all_total_volume,
                      SUM(c.total_weight) as all_total_weight
                ")
                ->join("inner join tb_quotation as b on a.quotation_id = b.id")
                ->join("left join tb_quote_packing_information as c on a.quotation_id = c.quotation_id")
                ->where([
                    ['a.quote_lcl_id' => $lcl_id]
                ])
                ->group("a.quotation_id")
                ->select()
        ;
        return $data;
    }

    protected function getQuoteLclSchemeConfirmData($lcl_id)
    {
        # QuoteLclSchemeConfirmDataModel
        $quote_lcl_scheme_confirm_data_model = D("Quote/QuoteLclSchemeConfirmData");
        $quote_lcl_scheme_confirm_data = $quote_lcl_scheme_confirm_data_model
            ->field(['quote_lcl_id','quotation_id','checker_id','checked_by',
                'quotation_scheme_id', 'remark', 'creator_id','created_by','operator_id','updated_by',
                'created_at','updated_at'])
            ->where([
                ["quote_lcl_id" => $lcl_id]
                ])
            ->select();
        if(!$quote_lcl_scheme_confirm_data)
        {
            $fields = ["b.id","b.quote_no",'b.quote_type','b.status_cd','b.creator_id','b.created_by'];
            $quote_lcl_quotations = $this->getQuoteLclQuotations($lcl_id,$fields);
            foreach ($quote_lcl_quotations as $quote_lcl_quotation)
            {
                $quote_lcl_scheme_confirm_data[$quote_lcl_quotation['creator_id']] = [
                    'quote_lcl_id' => $lcl_id,
                    'quotation_id' => $quote_lcl_quotation['id'],
                    'checker_id'  => $quote_lcl_quotation['creator_id'],
                    'checked_by'  => $quote_lcl_quotation['created_by'],
                    'quotation_scheme_id'  => '',
                    'remark'  => '',
                ];
            }
            $quote_lcl_scheme_confirm_data = array_values($quote_lcl_scheme_confirm_data);
        }
       return $quote_lcl_scheme_confirm_data;
    }
    /**
     * 获取拼柜单 报价报价单数据
     * @param $lcl_id
     * @author Redbo He
     * @date 2020/11/12  10:22
     */
    protected function getQuoteLclQuotations($lcl_id,$fields = ["b.*"])
    {
        $quote_lcl_quote_relation_model = D("Quote/QuotationLclQuotationRelations");
        $data = $quote_lcl_quote_relation_model->alias("a")
            ->field($fields)
            ->join("inner join tb_quotation as b on a.quotation_id = b.id")
            ->where([
                ['a.quote_lcl_id' => $lcl_id]
            ])
            ->select();
        foreach ($data  as  $k => $item) {
            $item = CodeModel::autoCodeOneVal($item,
                ['status_cd']
            );
            $data[$k] = $item;
        }
        return $data;
    }

    /**
     * 格式报价信息
     * @param array $data
     * @return mixed
     * @author Redbo He
     * @date 2020/11/4  10:04
     */
    public function formatQuotation(array $data)
    {
        $model = D("Quote/OperatorQuotation");
        $quoteStatus = CommonDataModel::quoteStatus();
        $quote_type_str_map       = $model::$quote_type_str_map;
        $quote_intention_type_map = $model::$quote_intention_type_map;
        if ($data)
        {
            $data['quote_type_name'] = $quote_type_str_map[$data['quote_type']];
            $data['quote_intention_type_name'] = $quote_intention_type_map[$data['quote_intention_type']];
            $data['status_name'] = $quoteStatus[$data['status_cd']];
            $data = CodeModel::autoCodeOneVal($data, ['small_team_cd']);
        }
        $data['quote_lcl_id'] = 0;
        if(in_array($data['status_cd'], [$model::STATUS_CD_LCL, $model::STATUS_CD_LCL_FINISH]))
        {
            $quote_lcl_quote_relation_model = D("Quote/QuotationLclQuotationRelations");
            $quote_lcl_quote_relation = $quote_lcl_quote_relation_model->where([
                ['quotation_id' => $data['id']]
            ])->find();
            if($quote_lcl_quote_relation)
            {
                $data['quote_lcl_id'] = $quote_lcl_quote_relation['quote_lcl_id'];
            }
        }
        return $data;
    }


    public function search($page_size = 10)
    {
        $model = D("Quote/OperatorQuotation");

        $where = [];
        # 已取消的不显示在列表
        $where['a.status_cd'] = ['neq',$model::STATUS_CANCEL];
        # 报价发起人
        if($created_by = I('get.created_by')) $where['a.created_by'] = ['like', "%$created_by%"];
        # 报价单号
        if($quote_no = I('get.quote_no')) $where['a.quote_no'] = ['eq', $quote_no];
        # 报价类型
        if($quote_type = I('get.quote_type'))  $where['a.quote_type'] = ['eq', $quote_type];
        # 报价状态 status_cd
        if($status_cd = I('get.status_cd'))  $where['a.status_cd'] = ['eq', $status_cd];
        # 报价意向 quote_intention_type
        if($quote_intention_type = I('get.quote_intention_type'))  $where['a.quote_intention_type'] = ['eq', $quote_intention_type];
        # 调拨单号 allo_no 
        if($allo_no = I('get.allo_no'))  $where['c.allo_no'] = ['eq', $allo_no];

        # 销售小团队
        if($small_team_cd = I('get.small_team_cd'))  {
            if(is_array($small_team_cd)) {
                $where['a.small_team_cd'] = ['in', $small_team_cd];
            } else {
                $where['a.small_team_cd'] = ['eq', $small_team_cd];
            }
        }
        # 计划运输渠道
        if($planned_transportation_channel_cd = I('get.planned_transportation_channel_cd'))   {
            if(is_array($planned_transportation_channel_cd)) {
                $where['f.planned_transportation_channel_cd'] = ['in', $planned_transportation_channel_cd];
            } else {
                $where['f.planned_transportation_channel_cd'] = ['eq', $planned_transportation_channel_cd];
            }
        }
        
        # 报价操作人 
        if($director_by = I('get.director_by'))  $where['a.director_by'] = ['like', "%$director_by%"];

        # 报价发起时间 
        $created_at_from = I('get.created_at_from');
        $created_at_to   = I('get.created_at_to');
        if($created_at_from && $created_at_to)
            $where['a.created_at'] = ['between', ["$created_at_from 00:00:00", "$created_at_to 23:59:59"]];
        elseif($created_at_from)
            $where['a.created_at'] = ['egt', "$created_at_from 00:00:00"];
        elseif($created_at_to)
            $where['a.created_at'] = ['elt', "$created_at_to 23:59:59"];

        # 报价更新时间 
        $updated_at_from = I('get.updated_at_from');
        $updated_at_to   = I('get.updated_at_to');
        if($updated_at_from && $updated_at_to)
            $where['a.updated_at'] = ['between', ["$updated_at_from 00:00:00", "$updated_at_to 23:59:59"]];
        elseif($updated_at_from)
            $where['a.updated_at'] = ['egt', "$updated_at_from 00:00:00"];
        elseif($updated_at_to)
            $where['a.updated_at'] = ['elt', "$updated_at_to 23:59:59"];

        # 调出仓库
        if($allo_out_warehouse = I('get.allo_out_warehouse')) 
        {
            $complex['b.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['e.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['d.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['_logic'] = 'or';
            $where['_complex'][] = $complex;
        }

        # 调入仓库 
        if($allo_in_warehouse = I('get.allo_in_warehouse')) 
        { 
        
            $complex1['b.allo_in_warehouse'] = ['eq', $allo_in_warehouse];
            $complex1['e.allo_in_warehouse'] = ['eq', $allo_in_warehouse];
            $complex1['d.allo_in_warehouse'] = ['eq', $allo_in_warehouse];
            $complex1['_logic'] = 'or';
            $where['_complex'][] = $complex1;
        }
//        dd($where);
        $quote_type_pre    = $model::QUOTE_TYPE_PRE;
        $quote_type_normal = $model::QUOTE_TYPE_NORMAL;
        $query  = $model->alias("a")
                        ->where($where)
                        ->join(" left join tb_quote_wms_allo as c on a.id = c.quotation_id ")
                        ->join(" left join tb_wms_allo e on c.allo_id = e.id ")
                        ->join(" left join tb_quote_packing_information as b  on a.id = b.quotation_id and a.quote_type ={$quote_type_normal} and c.allo_no = b.allo_no ")
                        ->join(" left join tb_quote_packing_information as d  on a.id = d.quotation_id and a.quote_type='{$quote_type_pre}' ")
                        ->join(" left join tb_quote_inquiries as f on a.id = f.quotation_id ")
                        ->group("a.id");
        $query_1 = clone $query;
        $subQuery = $query_1->field("count(*) as tp_count")->select(false);
        $db = M();
        $count_result  = $db->table($subQuery." tmp")->field("COUNT(*) as count ")->find();
        $count = ($count_result && isset($count_result['count'])) ? $count_result['count'] : 0;
        $page       = new Page($count,$page_size);// 实例化分页类 传入总记录数和每页显示的记录数

        $fileds= "a.id,a.quote_no,a.quote_type,group_concat(IFNULL(c.allo_no,'')) as quote_allos,
                        a.quote_intention_type,a.require_complete_date,a.complete_date,a.status_cd,
                        a.is_twice_quote,a.remark,a.small_team_cd,
                        a.creator_id,a.created_by,a.operator_id,a.updated_by,a.created_at,a.updated_at,
                        f.planned_transportation_channel_cd,
                        (
                         GROUP_CONCAT(
                            case
                                when a.quote_type = {$quote_type_normal} then IFNULL(b.allo_in_warehouse,e.allo_in_warehouse )
                                when a.quote_type = {$quote_type_pre} then d.allo_in_warehouse
                            end
                            )
		                ) as allo_in_warehouse,
                        (
                            case
                                when a.quote_type = {$quote_type_normal} then IFNULL(b.allo_out_warehouse,e.allo_out_warehouse)
                                when a.quote_type = {$quote_type_pre} then d.allo_out_warehouse
                            end
                        ) as allo_out_warehouse,
                        SUM(
                            case
                                when a.quote_type = {$quote_type_normal} then IFNULL(b.total_volume,0)
                                when a.quote_type = {$quote_type_pre} then IFNULL(d.total_volume,0)
                            end
		                 ) as all_total_volume,
                        SUM(
                            case
                                when a.quote_type = {$quote_type_normal} then IFNULL(b.total_box_num,0)
                                when a.quote_type = {$quote_type_pre} then IFNULL(d.total_box_num,0)
                            end
                        ) as all_total_box_num,
                        SUM(
                            case
                                when a.quote_type =  {$quote_type_normal} then IFNULL(b.total_weight,0)
                                when a.quote_type = {$quote_type_pre} then IFNULL(d.total_weight,0)
                            end
                        ) as all_total_weight    
                        ";

        $data['page'] = [
            'total'      => $count,
            'total_page' => $page->get_totalPages(),
            'per_page_size'   => $page_size,
            'now_page'   => I('get.p',1),
        ];

        $list  = $query->field($fileds)
                        ->order("a.id DESC")
                        ->limit($page->firstRow.','.$page->listRows)
                        ->select();
        if($list)
        {
            $quote_type_str_map       = $model::$quote_type_str_map;
            $quote_intention_type_map = $model::$quote_intention_type_map;

             $status_cds = array_unique(array_column($list,'status_cd'));
             $planned_transportation_channel_cd = array_unique(array_column($list,'planned_transportation_channel_cd'));
             $small_team_cds = array_unique(array_column($list,'small_team_cd'));

             $allo_out_warehouse_cds  = array_filter( array_unique(array_column($list,'allo_out_warehouse')));
             $allo_in_warehouse_cds   = array_filter(array_unique(array_column($list,'allo_in_warehouse')));
             $allo_in_warehouse_cds_tmp = [];
             foreach ($allo_in_warehouse_cds as $allo_in_warehouse_cd) {
                $allo_in_warehouse_cd_arr = explode(',', $allo_in_warehouse_cd);
                 $allo_in_warehouse_cds_tmp = array_unique(array_merge($allo_in_warehouse_cds_tmp,$allo_in_warehouse_cd_arr));
             }
             $warehouse_cds = array_unique(array_merge($allo_out_warehouse_cds, $allo_in_warehouse_cds_tmp));
             $code_cds = array_merge($status_cds, $warehouse_cds);
             $code_cds = array_merge($code_cds, $planned_transportation_channel_cd);
             $code_cds = array_merge($code_cds, $small_team_cds);
             $Model = M();
             $res = $Model->table('tb_ms_cmn_cd')
                ->field('CD,CD_VAL,ETC')
                ->where(['CD' => ['in', $code_cds]])
                ->order('SORT_NO asc,CD asc')
                ->select();
            $code_map = array_column($res,'CD_VAL','CD');
            foreach ($list as $k => &$item)
            {

                $item['quote_type_val'] = $quote_type_str_map[$item['quote_type']];
                $item['quote_intention_type_val'] = $quote_intention_type_map[$item['quote_intention_type']];

                # allo_in_warehouse
                $item['status_cd_val'] =$code_map[$item['status_cd']] ? $code_map[$item['status_cd']]: '';
                $item['allo_out_warehouse_val'] =$code_map[$item['allo_out_warehouse']] ? $code_map[$item['allo_out_warehouse']]: '';
                $item['small_team_cd_val'] =$code_map[$item['small_team_cd']] ? $code_map[$item['small_team_cd']]: '';
                $item['planned_transportation_channel_cd_val'] =$code_map[$item['planned_transportation_channel_cd']] ? $code_map[$item['planned_transportation_channel_cd']]: '';
                $item['allo_in_warehouse_val'] = '';
                if($item['allo_in_warehouse']) {
                    $allo_in_warehouse_cds = explode(',', $item['allo_in_warehouse']);
                    $tmp_codes  = [];
                    foreach ($allo_in_warehouse_cds as $allo_in_warehouse_cd) {
                        $tmp_codes[] = $code_map[$allo_in_warehouse_cd];
                    }
                    $item['allo_in_warehouse_val']  = implode(',', $tmp_codes);
                }

            }
        }
        $data['data'] = $list;
        return $data;
    }


    public function quote_lcl_list($page_size = 10)
    {

        $conditions = [];

        # 拼柜发起人
        if($created_by = I('get.created_by')) $conditions['c.created_by'] = ['like', "%$created_by%"];
        # 拼柜单号
        if($lcl_no = I('get.lcl_no')) $conditions['a.lcl_no'] = ['eq', $lcl_no];
        # 报价单号
        if($quote_no = I('get.quote_no')) $conditions['c.quote_no'] = ['eq', $quote_no];
        # 报价状态 status_cd
        if($status_cd = I('get.status_cd'))  $conditions['a.status_cd'] = ['eq', $status_cd];
        $status_cds = I('get.status_cds');
        if($status_cds  && is_array($status_cds)) {
            $conditions['a.status_cd'] = ['in', $status_cds];
        }
        # 销售小团队
        if($small_team_cd = I('get.small_team_cd'))  {
            if(is_array($small_team_cd)) {
                $conditions['c.small_team_cd'] = ['in', $small_team_cd];
            } else {
                $conditions['c.small_team_cd'] = ['eq', $small_team_cd];
            }
        }
        # 拼柜发起时间
        $created_at_from = I('get.created_at_from');
        $created_at_to   = I('get.created_at_to');
        if($created_at_from && $created_at_to)
            $conditions['a.created_at'] = ['between', ["$created_at_from 00:00:00", "$created_at_to 23:59:59"]];
        elseif($created_at_from)
            $conditions['a.created_at'] = ['egt', "$created_at_from 00:00:00"];
        elseif($created_at_to)
            $conditions['a.created_at'] = ['elt', "$created_at_to 23:59:59"];

        # 报价更新时间
        $updated_at_from = I('get.updated_at_from');
        $updated_at_to   = I('get.updated_at_to');
        if($updated_at_from && $updated_at_to)
            $conditions['a.updated_at'] = ['between', ["$updated_at_from 00:00:00", "$updated_at_to 23:59:59"]];
        elseif($updated_at_from)
            $conditions['a.updated_at'] = ['egt', "$updated_at_from 00:00:00"];
        elseif($updated_at_to)
            $conditions['a.updated_at'] = ['elt', "$updated_at_to 23:59:59"];

        # 调出仓库
        $allo_out_warehouse = I('get.allo_out_warehouse');
        if(is_array($allo_out_warehouse) && $allo_out_warehouse) {
            $complex['f.allo_out_warehouse'] = ['in', $allo_out_warehouse];
            $complex['e.allo_out_warehouse'] = ['in', $allo_out_warehouse];
            $complex['g.allo_out_warehouse'] = ['in', $allo_out_warehouse];
            $complex['_logic'] = 'or';
            $conditions['_complex'][] = $complex;
        } else if($allo_out_warehouse)
        {
            $complex['f.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['e.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['g.allo_out_warehouse'] = ['eq', $allo_out_warehouse];
            $complex['_logic'] = 'or';
            $conditions['_complex'][] = $complex;
        }
        $conditions["a.deleted_at"] = ["exp","IS NULL"];

        # 调入仓库


        # 增加数据业务删除
        $quote_lcl_model = D("Quote/QuoteLcl");

        $model = D("Quote/OperatorQuotation");
        $quote_type_pre    = $model::QUOTE_TYPE_PRE;
        $quote_type_normal = $model::QUOTE_TYPE_NORMAL;

        $fields = [
            "a.*",
//            "group_concat(IFNULL(c.quote_no,'')) as quote_nos",
//            "group_concat(IFNULL(c.small_team_cd,'')) as small_team_cds",
//            "group_concat(IFNULL(c.created_by,'')) as created_byes",
            "(
                    case
                        when c.quote_type = {$quote_type_normal} then IFNULL(f.allo_out_warehouse, e.allo_out_warehouse)
                        when c.quote_type = {$quote_type_pre} then g.allo_out_warehouse
                    end
                 ) as allo_out_warehouse"

        ];
        $query =  $quote_lcl_model->alias("a")
            ->field($fields)
            ->join("inner join tb_quote_lcl_quotation_relations as b on a.id = b.quote_lcl_id")
            ->join("inner join tb_quotation as c on c.id = b.quotation_id")
            ->join(" left join tb_quote_wms_allo as d on c.id = d.quotation_id ")

            ->join(" left join tb_wms_allo e on d.allo_id = e.id ")
            ->join(" left join tb_quote_packing_information as f  on c.id = f.quotation_id and c.quote_type ={$quote_type_normal} and d.allo_no = f.allo_no ")
            ->join(" left join tb_quote_packing_information as g  on c.id = g.quotation_id and c.quote_type='{$quote_type_pre}' ")

            ->where($conditions)
            ->order("id DESC")
            ->group("a.id")
            ;
        $sub_query = clone $query;
        $group_sql = $sub_query->select(false);
        $count = M()->table("{$group_sql} as tmp")->count();
        $page       = new Page($count,$page_size);// 实例化分页类 传入总记录数和每页显示的记录数

        $list = M()->table("{$group_sql} as sub_query")
            ->field([
                "sub_query.*",
               "group_concat(IFNULL(i.quote_no,'')) as quote_nos",
                "group_concat(IFNULL(i.small_team_cd,'')) as small_team_cds",
                "group_concat(IFNULL(i.created_by,'')) as created_byes",
            ])
            ->join("inner join tb_quote_lcl_quotation_relations as h on sub_query.id = h.quote_lcl_id")
            ->join("inner join tb_quotation as i on i.id = h.quotation_id")
            ->limit($page->firstRow.','.$page->listRows)
            ->order("sub_query.id DESC")
            ->group("sub_query.id")
            ->select();
        ;
        # $list = $query->limit($page->firstRow.','.$page->listRows)->select();
        if($list)
        {
            # 处理销售小团队显示值问
            $list = CodeModel::autoCodes2Val($list, ['small_team_cds','status_cd','allo_out_warehouse']);
            foreach ($list  as &$item) {
                $item['created_byes'] = implode(',', array_unique(explode(',', $item['created_byes'])));
            }
        }
        else
        {
            $list = [];
        }
        $data['page'] = [
            'total'      => $count,
            'total_page' => $page->get_totalPages(),
            'per_page_size' => $page_size,
            'now_page'   => I('get.p',1),
        ];
        $data['data'] = $list;
        return $data;
    }

    public function quote_log_list($object_name, $object_id)
    {
        $page_size = I('get.size', 10);
        $quote_logs_model = D("Quote/QuoteLogs");
        $where = [
            'object_name' => ['eq', $object_name],
            'object_id'   => ['eq', $object_id]
        ];
        $count = $quote_logs_model->alias('a')->where($where)->count();
        $page  = new Page($count,$page_size);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $quote_logs_model->alias('a')->where($where)->order("id desc")->select();
        $data['page'] = [
            'total'      => $count,
            'total_page' => $page->get_totalPages(),
            'per_page_size' => $page_size,
            'now_page'   => I('get.p',1),
        ];
        $data['data'] = $list;
        return $data;
    }

    /**
     * 报价单回滚
     * @param $lcl_id
     * @author Redbo He
     * @date 2020/11/11  17:15
     */
    public function quoteLclRollback($lcl_id)
    {
        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl = $quote_lcl_model->where("id = {$lcl_id}")->find();
        if(!$quote_lcl)
        {
            return ['status' => false ,'msg' => "拼柜记录不存在，请检查"];
        }
        #  报价单数据删除 报价关联数据删除 关联联的报价单重新成为待报价状态
        # tb_quote_lcl_quotation_relations
        $trans = M();
        $trans->startTrans();   // 开启事务
        try
        {
            # 报价关联数据删除
            $quote_lcl_quote_relation_model = D("Quote/QuotationLclQuotationRelations");
            $quote_lcl_quote_relation_relations = $quote_lcl_quote_relation_model->where([['quote_lcl_id' => $lcl_id]])->select();
            $res = $quote_lcl_quote_relation_model->where([['quote_lcl_id' => $lcl_id]])->delete();
            if($res)
            {
                $quotation_ids = array_column($quote_lcl_quote_relation_relations,'quotation_id');
                $quotation_model = D("Quote/OperatorQuotation");
                $update_data = [
                    'status_cd' => $quotation_model::STATUS_CD_WAIT_QUOTE
                ];
                $res2 = $quotation_model->where([['id' => ['in', $quotation_ids]]])->data($update_data)->save();
            }
            # 报价单数据删除
            $res3 = $quote_lcl_model->where("id = {$lcl_id}")->delete();

        } 
        catch (\Exception $e) 
        {
            $trans->rollback();
            Log::record("【报价单回滚失败】" . $e->__toString(), Log::ERR);
            return ['status' => false, 'msg' => '服务器异常，报价单回滚失败'];
        }
        $trans->commit();
        return ['status' => true, 'msg' => 'success'];

    }


    /**
     * 获取待办事项
     * @param array $where
     * @param int $is_twice_quote
     * @author Redbo He
     * @date 2020/11/13  17:00
     */
    public function get_quote_to_list(array $where, $is_twice_quote = 0)
    {
        $model = D("Quote/OperatorQuotation");
        $quotation_fields = "'quotation' as object_name, id as object_id,quote_no, director_id,creator_id,director_by,created_by,created_at,status_cd ";
        $quote_lcl_fields = "'quote_lcl' as object_name, id as object_id,lcl_no as quote_no, director_id,director_by,creator_id,created_by,created_at,status_cd ";
        $quotation_sub_sql = $model->field($quotation_fields)->alias('a')->where($where)->select(false);
        $quote_lcl_model = D("Quote/QuoteLcl");
        $quote_lcl_sub_sql = $quote_lcl_model->field($quote_lcl_fields)->alias('a')->where($where)->select(false);
        if(!$is_twice_quote) {
            $union_all_sub_sql = " {$quotation_sub_sql} UNION ALL {$quote_lcl_sub_sql}";
        }
        else
        {
            $union_all_sub_sql = $quotation_sub_sql;
        }
        $union_all_sql = "select * from ($union_all_sub_sql) as query_tmp order by created_at desc";
        $query  = M();
        $list = $query->query($union_all_sql);
        if($list)
        {
            foreach ($list as $k => $item) {
                $item['sort_no'] = ($k + 1);
                $item['detail_url_key'] = $item['object_id'];
                $item['detail_url'] = '';
                $item['title'] = '';
                if($item["object_name"] == 'quote_lcl')
                {
                    $erp_url = '//' . $_SERVER['HTTP_HOST'].'/';
                    $item['detail_url'] = $erp_url . 'index.php?m=quote&a=combine_cabinets_detail&id=';
                    $item['title'] = '编辑拼柜';
                }
                $list[$k] = $item;
            }
        }
        return $list;
    }

    /**
     * 商品信息检索
     * @author Redbo He
     * @date 2021/2/7 16:29
     */
    public function searchGoods()
    {
        $keyword = I("get.keyword");
        $language =I("get.language",'N000920100');

        $result = [];
        if($keyword)
        {
            $model = M();
            $where = [
                "product_sku.sku_states"  => ["neq", -1],
                "product_detail.language" => ["eq", $language],
                "product_sku.sku_id"      => ["like", "{$keyword}%"],
            ];
            $result = $model->table(PMS_DATABASE . ".product_sku ")
                ->join("inner join ". PMS_DATABASE.".product_detail ON product_sku.spu_id = product_detail.spu_id")
                ->field("product_sku.spu_id,product_detail.spu_name as good_name, product_sku.sku_id")
                ->where($where)
                ->select();
        }
        return $result;

    }

    /**
     *
     * @param $quotation_id
     * @author Redbo He
     * @date 2021/2/8 11:08
     */
    public function cancelQuotation($quotation_id)
    {
        $model = D("Quote/OperatorQuotation");
        $quotation = $model->where("id = {$quotation_id}")->find();
        if(empty($quotation)) {
            return ['status' => false ,'msg' => "找不到报价单，请检查"];
        }
        if ($quotation['status_cd'] == $model::STATUS_CANCEL) {
            return ['status' => false ,'msg' => "调拨单已取消，无法进行"];
        }
        $update_data = [
            'status_cd' => $model::STATUS_CANCEL,
            'remark' => "调拨单取消"
        ];
        try
        {
            $model->startTrans();
            $res = $model->where("id = {$quotation_id}")->data($update_data)->save();
            # 正常报价 取消需要更新调拨单信息
            $check_res = $res;
            if($res && $quotation['quote_type'] == $model::QUOTE_TYPE_NORMAL)
            {
                $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
                $where = [
                    'quotation_id' => $quotation_id
                ];
                $quote_wms_allos = $quotation_wms_allo_model->where($where)->select();
                $allo_ids = array_unique(array_column($quote_wms_allos,'allo_id'));
                $date = new Date();
                $tb_wms_allo_model = D("TbWmsAllo");
                $check_res = $tb_wms_allo_model->where(['id' => ['in', $allo_ids]])->data([
                    'quote_no'    => "",
                    'update_user' => session('user_id'),
                    'update_time' => $date->format()
                ])->save();
            }
            if($check_res === false)
            {
                $model->rollBack();
            }
            else
            {
                 $model->commit();
            }
        }
        catch (Exception $e)
        {
            Log::record("【调拨单取消更新失败】".$e->__toString(), Log::ERR);
            $model->rollBack();
        }

        if($check_res) {
            return ['status' => true ,'msg' => "报价单已取消"];
        } else {
            return ['status' => false ,'msg' => "报价单取消失败，请稍后再试"];
        }
    }

    /**
     * 查询
     * @param $quotation_ids
     * @author Redbo He
     * @date 2021/2/8 16:31
     */
    public function getExportData(array  $quotation_ids)
    {
        $result = [];
        $quotation_model = D("Quote/OperatorQuotation");
        # 查询导出装箱信息
        # tb_quote_packing_information
        #  $packing_information_model =  D("Quote/QuotePackingInformation");
        $in_string = implode(',', $quotation_ids);
        $where_in_string = " a.id in ($in_string) ";
        $quote_pack_data  = $quotation_model->alias("a")
                        ->field("a.id,a.quote_no,a.quote_type,a.created_by quotation_created_by, DATE_FORMAT(a.created_at,'%Y-%m-%d') as quotation_create_at, a.small_team_cd, b.quotation_id,
                         b.allo_no,b.total_box_num,b.total_volume,b.total_weight,b.allo_in_warehouse,b.allo_in_warehouse_address,b.allo_out_warehouse,
                        b.allo_out_warehouse_address,b.declare_type_cd,b.declare_type_remark,b.is_electric_cd,b.creator_id,b.created_by, d.planned_transportation_channel_cd")
                        ->join(" inner join tb_quote_packing_information as b on a.id = b.quotation_id ")
                        ->join(" left join tb_quote_inquiries as d on a.id = d.quotation_id ")
                        ->whereString($where_in_string)
                        ->where([
                           'a.status_cd' => ['eq', $quotation_model::STATUS_CD_WAIT_QUOTE],
                            ])
                        ->order(" a.id ASC ")
                        ->select();
        $quote_pack_data = CodeModel::autoCodeTwoVal($quote_pack_data,["small_team_cd","allo_in_warehouse",'allo_out_warehouse','declare_type_cd','is_electric_cd','planned_transportation_channel_cd']);
        $goods_list = [];
        if($quote_pack_data)
        {
            $type_normal = $quotation_model::QUOTE_TYPE_NORMAL;
            $type_pre = $quotation_model::QUOTE_TYPE_PRE;
            $normal_allo_nos = array_map(function($v) use ($type_normal) {
                if($v['quote_type'] == $type_normal) { return $v['allo_no']; }
                return '';
            }, $quote_pack_data);
            $normal_allo_nos = array_filter($normal_allo_nos);

            # 调拨商品数据查询
            $quotation_wms_allo_good_data = [];
            if($normal_allo_nos) {
                $quotation_wms_allo_model = D("Quote/QuoteWmsAllo");
                $quotation_wms_allo_good_data = $quotation_wms_allo_model->field([
                    "tb_quote_wms_allo.quotation_id",
                    "tb_quotation.quote_no",
                    "tb_quote_wms_allo.allo_no",
                    "tb_wms_allo_new_guds_infos.sku_id",
                    "'' as good_name",
                    "tb_wms_allo_child.demand_allo_num as good_number",
                ])  ->join(" inner join tb_wms_allo_new_guds_infos on tb_quote_wms_allo.allo_id = tb_wms_allo_new_guds_infos.allo_id ")
                    ->join("inner join tb_quotation  on tb_quote_wms_allo.quotation_id = tb_quotation.id")
                    ->join(" inner join tb_wms_allo_child on tb_wms_allo_new_guds_infos.allo_id = tb_wms_allo_child.allo_id AND tb_wms_allo_child.sku_id = tb_wms_allo_new_guds_infos.sku_id ")
                    ->where(["tb_quote_wms_allo.allo_no" => ['in', $normal_allo_nos]])
                    ->select();
                $quotation_wms_allo_good_data = SkuModel::getInfo($quotation_wms_allo_good_data, 'sku_id',
                    ['spu_name']
                );
                foreach ($quotation_wms_allo_good_data as &$item) {
                    $item['good_name'] = $item['spu_name'];
                    unset($item['spu_name']);
                }
            }

            # 处理提前报价单商品信息
            $quotation_ids = array_unique(array_column($quote_pack_data,'quotation_id'));
            $quote_goods_model = D("Quote/QuoteGoods");
            $pre_goods_list =  $quote_goods_model->alias("a")
                ->field("a.quotation_id,b.quote_no,'' as allo_no , a.sku_id, a.good_name,a.good_number")
                ->join("inner join tb_quotation as b on a.quotation_id = b.id and b.quote_type = {$type_pre}")
                ->where(["a.quotation_id" => ['in', $quotation_ids]])->order("a.id ASC")->select();
        }
        if($quotation_wms_allo_good_data) {
            $goods_list = array_merge($goods_list, $quotation_wms_allo_good_data);
        }
        if($pre_goods_list) {
            $goods_list = array_merge($goods_list, $pre_goods_list);
        }
        $goods_list_data = [];
        if($goods_list) {
            foreach ($goods_list as $good) {
                $index1 = $good['quotation_id'];
                $index2 = (isset($good['allo_no']) && !empty($good['allo_no'] )) ? $good['allo_no'] : 'quote_goods';
                $goods_list_data[$index1][$index2][] = $good;
            }
        }


        # 构建新数组数据 便于数据导出
        $quote_quotation_fields = [
            'id' , 'quote_no' ,'quotation_created_by','quotation_create_at',
            'quote_type','total_box_num','total_volume','total_weight','declare_type_cd_val',
            'is_electric_cd','is_electric_cd_val','planned_transportation_channel_cd',
            'planned_transportation_channel_cd_val',
        ];
        $quotation_data = [];
        $temp_quotation = [];
        foreach ($quote_pack_data as $item) {
            $temp = [];
            foreach ($quote_quotation_fields as $quote_quotation_field)
            {
                $temp[$quote_quotation_field] = isset($item[$quote_quotation_field]) ? $item[$quote_quotation_field] :  "";
            }
            $quotation_id = $item['quotation_id'];
            $temp['merge_num'] = 0;
            $quotation_data[$quotation_id] = $temp;
        }
        foreach ($quote_pack_data as $item) {
            $quotation_id = $item['quotation_id'];
            $quote_no_index  = (isset($item['allo_no']) && $item['allo_no']) ? $item['allo_no'] : 'quote_goods';

            $quotation_goods = isset($goods_list_data[$quotation_id]) ? $goods_list_data[$quotation_id] : [];
            $quote_sub_goods = isset($quotation_goods[$quote_no_index]) ? $quotation_goods[$quote_no_index]: [];
            $item['quote_allo_row_num'] = count($quote_sub_goods);
            $item['goods'] = $quote_sub_goods;
            $quotation_data[$quotation_id]['merge_num'] += count($quote_sub_goods);
            # 优化聚合数据统计部分
            $quotation_data[$quotation_id]['quot_allo_data'][] = $item;
        }

        # 组装数据聚合统计部分
        foreach ($quotation_data as $quotation_id => $item3) {
            $quotation_data[$quotation_id]['total_box_num_sum'] =array_sum( array_column($item3['quot_allo_data'],'total_box_num'));
            $quotation_data[$quotation_id]['total_volume_sum'] =array_sum( array_column($item3['quot_allo_data'],'total_volume'));
            $quotation_data[$quotation_id]['total_weight_sum'] =array_sum( array_column($item3['quot_allo_data'],'total_weight'));
        }
        return [array_values($quotation_data)];
    }

    /**
     * 取消拼柜单
     * @param $quote_lcl_id
     * @author Redbo He
     * @date 2021/4/6 14:14
     */
    public  function cancel_quote_lcl($quote_lcl_id)
    {
        $quote_lcl_model = D("Quote/QuoteLcl");

        $quote_lcl = $quote_lcl_model->where("id = {$quote_lcl_id}")->find();
        if(!$quote_lcl) {
            return ['status' => false ,'msg' => "找不到拼柜单，请检查"];
        }
        $quote_lcl_quotations = $quote_lcl_model->relationGet("quote_lcl_quotations");

        $quotation_ids = array_column($quote_lcl_quotations,'id');
        $model = D("Quote/OperatorQuotation");

        # 点击【取消拼柜按钮】，当前拼柜单被逻辑删除，同时拼柜单关联的报价单，统一状态变更为【待报价】，同 时企业微信推送提醒角色-物流报价人
        try
        {
            $quote_lcl_model->startTrans();
            $date = new Date();
            $quotation_data = [
                'status_cd'  => $model::STATUS_CD_WAIT_QUOTE,
                'remark'     =>  "拼柜单取消，报价单状态改为待报价",
            ];

            # 报价单分组
            $quote_lcl_data = [
                'deleted_by'  => session('user_id'),
                'deleted_at'  => $date->format()
            ];

            if($quotation_ids)
            {
                $res1 = $model->where([[ 'id' => ['in', $quotation_ids]]])->data($quotation_data)->save();
                if(!$res1)
                {
                    throw new \Exception("报价单信息修改失败，请检查请求数据是否正常");
                }
            }
            # 更新更新拼柜单操作
            $res2 = $quote_lcl_model->where("id = {$quote_lcl_id}")->data($quote_lcl_data)->save();
            if(!$res2)
            {
                throw new \Exception("拼柜单信息修改失败，请检查请求数据是否正常");
            }
        }
        catch (\Exception $e)
        {
            Log::record("【报价单取消更新失败】".$e->__toString(), Log::ERR);
            $quote_lcl_model->rollBack();
            return ['status' => false ,'msg' => "报价单失败，请稍后再试"];
        }
        $quote_lcl_model->commit();

        # 发成功消息
        $quotations = $model
                        ->field('*')
                        ->where([['id' => ['in', $quotation_ids]]])
                        ->select();
        if($quote_lcl_quotations)
        {
            $service = new QuotationService();
            $directors  = $service->getQuoteDirectors();
            foreach ($quotations as $quotation)
            {
                $quotation['director_id'] = implode(',', array_column($directors,'id'));
                $service->pushQuoteWorkWxMessage('wait_quote', $quotation);
            }
        }

        return ['status' => true ,'msg' => "报价已取消"];



    }

}