<?php

/**
 * 入库测试类
 *
 */
class TestAction extends BaseAction
{
    public function ValidateAddressTest() {
        var_dump($this->ValidateAddress());
    }
    public function ValidateAddress() {
        $address = ['Sherry.Huang@gshopper.com,Wendy.Chen@gshopper.com'];
        //$address = ['Sherry.Huang@gshopper.com','Wendy.Chen@gshopper.com'];
        $address = 'Sherry.Huang@gshopper.com';
        if (function_exists('filter_var')) { //Introduced in PHP 5.2
            if(filter_var($address, FILTER_VALIDATE_EMAIL) === FALSE) {
                return false;
            } else {
                return true;
            }
        } else {
            return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
        }
    }

    public function bulidInveWechatApproval($id, $send_email) {
        $send_email = ['tianrui'];
        $send_data = $this->getTransferWechatApprovalData($id);
        $wx_return_res = (new ReviewMsgTpl())->sendWechatInveApproval($send_data, $send_email, self::WX_CALLBACK_FUNCTION);
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    public function slave_db_test()
    {
        $model = new SlaveModel();
        $res = $model->table('tb_pur_payment_audit')->find();
        v($res);
    }

    public function leader_test()
    {
        $name = I('name');
        $m = new TbHrDeptModel();
        $res = $m->getDeptLeaderByEmpName($name);
        v($res);
    }

    public function leader_test2()
    {
        $name = I('name');
        $dept_id = I('dept_id');
        $m = new TbHrDeptModel();
        $res = $m->getDeptLeaderByDeptId($name, $dept_id);
        v($res);
    }
    /**
     * 入库测试案例
     *
     */
    public function in_test()
    {
        $bill = new TbWmsBillModel();

        $data = [
            'bill' => [
                'bill_type'               => 'N000940100',//收发类型，采购入库为N000940100固定不变
                'link_bill_id'            => 'b5cb49053203333',//b5c id
                'warehouse_rule'          => '0',// 数据库无对应字段
                'batch'                   => date('Y-m-d', time()),//批次，这个待定
                'sale_no'                 => 'test20170717',// 数据库无对应字段
                'channel'                 => 'B5C',// 91440300311647055E
                'supplier'                => '',// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                'purchase_logistics_cost' => '58.67',//采购端的物流费用
                'warehouse_id'            => 'N000680300',// 仓库id（码表或数据字典对应的值）
                'total_cost'              => '300.01',//入库总成本
                'bill_state'              => '',   //单据状态，可为空
                'bill_date'               => date('Y-m-d', time()),//单据日期,
                'CON_COMPANY_CD'          => 1,
                'SALE_TEAM'               => 'N001280100'
            ],
            'guds' => [
                [
                    'GSKU'                  => '8000360401', // sku
                    'taxes'                 => '0.7',       // 税率
                    'should_num'            => '800',  // 应发货
                    'send_num'              => '489',    // 实际发货
                    'deadline_date_for_use' => '20170713',// 生产日期
                    'price'                 => '168.54',    // 单价（不含税）
                    'currency_id'           => 'N000590300',// 币种（码表或数据字典对应的值）
                    'currency_time'         => '20170713',// 具体交易时间（用作取币种当天的汇率）
                ],
                [
                    'GSKU'                  => '8000372401',
                    'taxes'                 => '0.4',
                    'should_num'            => '999',
                    'send_num'              => '321',
                    'deadline_date_for_use' => '20170713',
                    'price'                 => '189.5',
                    'currency_id'           => 'N000590300',// 数据库无对应字段
                    'currency_time'         => '20170713',// 数据库无对应字段
                ],
            ]
        ];

        //$data = json_decode($str, true);
        $isok = $bill->outAndInStorage($data);
        echo '<Pre/>';
        var_dump($isok);
        exit;
        exit;

    }

    /**
     * 出库测试
     *
     */
    public function out_test()
    {
        $bill = new TbWmsBillModel();
        /**
         * 批量data操作
         * $data = [
         *    [
         *          ['GSKU'] => 'xxxx',
         *          ['send_num'] => (int)xxx,
         *    ],
         *    [
         *          ['GSKU'] => 'xxxx',
         *          ['send_num'] => (int)xxx,
         *    ]
         * ]
         *
         */
        // 单条操作
        $data = [
            //'bill_type' => 'N000950100',
            'GSKU'     => '8000360401',
            'send_num' => 200,
        ];
        $isok = $bill->outStorage($data);
        echo '<Pre/>';
        var_dump($isok);
        exit;
        exit;
    }

    public function ship()
    {
        D('TbPurRelevanceOrder')->where(['relevance_id' => 290])->save(['number_total' => 1000]);
    }

    public function replase()
    {
        //if (!isset($this->access ['test/replase'])) js_redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
        $url   = 'http://i.b5cai.com/batch/release_occupy.json';
        $sku   = $_GET['sku_id'];
        $model = new Model();
        $ret   = $model->table('tb_wms_batch_order')->where('SKU_ID = "' . $sku . '" and up_flag = 1')->select();

        $requestData ['data']['release'] = [];
        if ($ret) {
            foreach ($ret as $k => $v) {
                $tmp ['gudsId']                    = $v ['GUDS_ID'];
                $tmp ['skuId']                     = $v ['SKU_ID'];
                $tmp ['orderId']                   = $v ['ORD_ID'];
                $requestData ['data']['release'][] = $tmp;
            }
        }

        curl_get_json($url, json_encode($requestData));
    }

    public function testAppPush()
    {
        $a = ['a' => 'b', 'c' => 'd'];
        B('UMessagePush', $a);
    }

    public function index()
    {
        M()->startTrans();
        M('demand', 'tb_sell_')->lock(true)->where(['id' => 1303])->find();
        sleep(180);
        M()->commit();
    }

    public function lock_save()
    {
        try {
            $m   = M('demand', 'tb_sell_');
            $res = $m->where(['id' => 1303])->save(['customer_charter_no' => '222222']);
            var_dump('aa', $res, $m->getDbError());
        } catch (Exception $e) {
            var_dump('bbb', $e->getMessage());
        }
    }

    public function transfer()
    {
        //企业微信审批
        $send_email    = ['fuming@gshopper.com'];
        $wx_return_res = (new FinanceService())->bulidTransferWechatApproval(335, $send_email);
        v($wx_return_res);
    }

    public function saveAllTrans()
    {
        //同步英文翻译配置 SP_NAME SP_RES_NAME_EN
        $data[] = ['element' => '测试11', 'type' => 'N000920200', 'translation_content' => 'test11'];
        $res    = (new LanguageModel())->saveAllTrans($data);
        v($res);
    }

    //一般付款申请通知
    public function general()
    {
        //企业微信审批
        $send_email    = ['Allen.Ouyang@gshopper.com', 'shenmo@gshopper.com'];
        $wx_return_res = (new FinanceService())->bulidGeneralWechatApproval(12963, $send_email);
        echo "调用成功了吗";
        v($wx_return_res);
    }

    //一般付款申请通知
    public function test11()
    {
        $Model = M();
        //运单号面单获取状态 N002080900  物流公司 万邑通
        $order_info = $Model->table('tb_ms_ord AS t1, tb_wms_batch_order AS t2,tb_op_order AS t3,tb_wms_warehouse AS t4,tb_ms_ord AS t5')->field('t1.ORD_ID,t3.ORDER_TIME,t3.ORDER_ID,t3.ORDER_NO,t3.PLAT_CD')->where("
        t1.ORD_ID = t2.ORD_ID AND t1.THIRD_ORDER_ID = t3.ORDER_ID AND t5.THIRD_ORDER_ID = t3.ORDER_ID 
        AND t3.THIRD_DELIVER_STATUS = '1' AND t5.WHOLE_STATUS_CD = 'N001820800' 
        ")->group('t2.ORD_ID')->order('t1.reset_num asc,t1.ESTM_RCP_REQ_DT asc')->find();
        if (empty($order_info)) v($order_info);
        //企业微信审批
        $sendout_time = date('Y-m-d H:i:s');
        $user_ids    = 'shenmo';
        //$user_ids    = 'Allen.Ouyang';
        $order_detail_url = ERP_URL . '/index.php?g=OMS&m=Order&a=orderDetail&isShowEditButtonByOmsListEntry=true&order_no=';
        $order_detail_url = 'http://local.gshopper.com/index.php?g=OMS&m=Order&a=orderDetail&isShowEditButtonByOmsListEntry=true&order_no=';
        $order_detail_url .= $order_info['ORDER_NO'] . '&thrId=' . $order_info['ORDER_ID'] . '&platCode=' . $order_info['PLAT_CD'];
        //$order_detail_url = ERP_URL . '/index.php';
        echo $order_detail_url;
        $plat_forms = CommonDataModel::platform();
        $message_string = ">**企业微信通知事项** 
> 你有一个订单自动出库，已成功标记发货
>订单号  ：<font color=info >{$order_info['ORDER_NO']}</font> 
>出库时间：<font color=warning >{$sendout_time}</font> 
>站点    ：<font color=info >{$plat_forms[$order_info['PLAT_CD']]}</font> 
>下单时间    ：<font color=info >{$order_info['ORDER_TIME']}</font> 
>如需查看详情，请点击：[查看详情]({$order_detail_url})";
        $res = ApiModel::WorkWxSendMarkdownMessage($user_ids, $message_string);
        v($res);
    }

    //售后申请通知
    public function afterSale()
    {
        $after_sale_no = I('after_sale_no') ? I('after_sale_no') : '202011040023'; //默认202011040023 id 711
        $Model = new \Model();
        $after_sale_id = $Model->table('tb_op_order_refund')->where(['after_sale_no' => $after_sale_no])->getField('id');
        //企业微信审批
        $send_email    = ['tujin.li@gshopper.com', 'leshan@gshopper.com', 'Roddy.Yu@gshopper.com', 'shenmo@gshopper.com'];
        $wx_return_res = (new OmsAfterSaleService(null))->bulidAfterSaleApproval($after_sale_id, $send_email);
        echo "调用成功了吗";
        v($wx_return_res);
    }

    //售后申请通知
    public function returnWarehouse()
    {
        $data['order_id'] = 'ff8080817867851f01786919dcb71582'; //订单号
        $data['return_goods_id'] = I('return_goods_id') ? I('return_goods_id') : '45029'; //默认id 45911
        $data['warehouse_num_broken'] = 1;
        $data['warehouse_num'] = 2;
        $wx_return_res = (new OmsAfterSaleService(null))->ReturnWarehouseApproval($data);

//        $send_email    = ['cuncheng.xiao@gshopper.com', 'sellers.shen@gshopper.com'];
//        $wx_return_res = (new OmsAfterSaleService(null))->bulidReturnWarehouseApproval($data['return_goods_id'], $send_email);

        echo "调用成功了吗";
        v($wx_return_res);
    }

    //检查更新订单收入成本冲销状态状态
    public function checkOrderChargeOffStatus()
    {
        $data['order_id'] = 'GD6W5UBK3DUD2_2'; //订单号
        $data['platform_cd'] = 'N000836300'; //平台
        //$data['order_id'] = '202012091952'; //订单号
        //$data['platform_cd'] = 'N000830400'; //平台
        $return_res = (new OmsAfterSaleService(null))->upOrderChargeOffStatus($data);
        echo "检查更新订单收入成本冲销状态状态";
        v($return_res);
    }

    //一般付款申请通知
    public function workWxSendMessage()
    {
        //企业微信审批
        $send_email    = 'shenmo';
        $data = '您已同意付款单号XXXXX的申请-群发。';
        $wx_return_res = (new ApiModel())->WorkWxMessage($send_email, $data);
        echo "调用成功了吗";
        v($wx_return_res);
    }

    //导出调拨数据
    public function ex_allo()
    {
        $data = M('wms_allo', 'tb_')
            ->field('allo_no,create_time,M_NAME')
            ->join('left join bbm_admin on tb_wms_allo.create_user = bbm_admin.M_ID')
            ->select();
        $map  = [
            ['field_name' => 'allo_no', 'name' => '调拨单号'],
            ['field_name' => 'create_time', 'name' => '创建时间'],
            ['field_name' => 'M_NAME', 'name' => '创建人'],
        ];
        $this->exportCsv($data, $map);
    }

    //导出物流方式、运费、出发地组合数据
    public function ex_logistics()
    {
        $postage_model = M('lgt_postage_model', 'tb_');
        $cd_model      = M('ms_cmn_cd', 'tb_');

        $logis = M('ms_logistics_mode', 'tb_')->field('LOGISTICS_MODE, POSTAGE_ID')->select();

        $data = [];
        foreach ($logis as $key => $value) {
            $post_arr = explode(',', $value['POSTAGE_ID']);
            $postage  = $postage_model->field('MODEL_NM, OUT_AREAS')->where(['ID' => ['in', $post_arr]])->select();
            foreach ($postage as $k => $v) {
                $cd_arr = explode(',', $v['OUT_AREAS']);
                $cds    = $cd_model->field('CD_VAL')->where(['CD' => ['in', $cd_arr]])->select();
                foreach ($cds as $cd) {
                    $data[] = [
                        'LOGISTICS_MODE' => $value['LOGISTICS_MODE'],
                        'MODEL_NM'       => $v['MODEL_NM'],
                        'CD_VAL'         => $cd['CD_VAL'],
                    ];
                }
            }
        }
        $map = [
            ['field_name' => 'LOGISTICS_MODE', 'name' => '物流方式'],
            ['field_name' => 'MODEL_NM', 'name' => '模板名称'],
            ['field_name' => 'CD_VAL', 'name' => '出发地（仓库）'],
        ];
        $this->exportCsv($data, $map);
    }

    public function ex_supplier()
    {
        $supplier     = D('TbCrmSpSupplier');
        $ret          = $supplier->field('SP_NAME,SP_RES_NAME,SP_NAME_EN,SP_RES_NAME_EN,CREATE_USER_ID,CREATE_TIME,UPDATE_USER_ID,UPDATE_TIME,COPANY_TYPE_CD,SP_CAT_CD,RISK_RATING,AUDIT_STATE,SP_TEAM_CD,SP_ADDR1,SP_CHARTER_NO')->select();
        $all_user     = BaseModel::getAdmin();
        $all_country  = BaseModel::getCountryInfo();
        $risk         = [
            1 => '低风险',
            2 => '中风险',
            3 => '高风险',
        ];
        $audit_status = [
            1 => '未审核',
            2 => '已审核',
        ];
        $cmn_cat      = BaseModel::getCmnCat();
        $map          = [
            ['field_name' => 'SP_NAME', 'name' => '供应商名称'],
            ['field_name' => 'SP_RES_NAME', 'name' => '供应商简称'],
            ['field_name' => 'SP_NAME_EN', 'name' => '英文名称'],
            ['field_name' => 'SP_RES_NAME_EN', 'name' => '英文简称'],
            ['field_name' => 'CREATE_USER_ID', 'name' => '创建人'],
            ['field_name' => 'CREATE_TIME', 'name' => '创建时间'],
            ['field_name' => 'UPDATE_USER_ID', 'name' => '最新修改人'],
            ['field_name' => 'UPDATE_TIME', 'name' => '最新修改时间'],
            ['field_name' => 'COPANY_TYPE_CD', 'name' => '企业类型'],
            ['field_name' => 'SP_CAT_CD', 'name' => '供货品类'],
            ['field_name' => 'contract_count', 'name' => '合同数'],
            ['field_name' => 'contract_count_is_use', 'name' => '生效合同'],
            ['field_name' => 'RISK_RATING', 'name' => '风险评级'],
            ['field_name' => 'AUDIT_STATE', 'name' => '审核状态'],
            ['field_name' => 'REVIEWER', 'name' => '审核人'],
            ['field_name' => 'REV_TIME', 'name' => '审核时间'],
            ['field_name' => 'SP_ADDR1', 'name' => '国别'],
            ['field_name' => 'SP_TEAM_CD', 'name' => '采购团队']
        ];
        foreach ($ret as $v) {
            $v['CREATE_USER_ID'] = $all_user[$v['CREATE_USER_ID']];
            $v['UPDATE_USER_ID'] = $all_user[$v['UPDATE_USER_ID']];
            $v['COPANY_TYPE_CD'] = cdVal($v['COPANY_TYPE_CD']);
            $v['SP_ADDR1']       = $all_country[$v['SP_ADDR1']];
            $v['RISK_RATING']    = $risk[$v['RISK_RATING']];
            $v['AUDIT_STATE']    = $audit_status[$v['AUDIT_STATE']];
            $purchase_team       = [];
            foreach (explode(',', $v['SP_TEAM_CD']) as $val) {
                $purchase_team[] = cdVal($val);
            }
            $v['SP_TEAM_CD']            = implode(',', $purchase_team);
            $v['contract_count_is_use'] = $v['SP_CHARTER_NO'] ? M('contract', 'tb_crm_')->where(['CRM_CON_TYPE' => 0, 'CON_STAT' => 1, 'SP_CHARTER_NO' => $v['SP_CHARTER_NO']])->count() : 0;
            $v['contract_count']        = $v['SP_CHARTER_NO'] ? M('contract', 'tb_crm_')->where(['CRM_CON_TYPE' => 0, 'SP_CHARTER_NO' => $v['SP_CHARTER_NO']])->count() : 0;
            $audit                      = $v['SP_CHARTER_NO'] ? M('forensic_audit', 'tb_ms_')->field('REVIEWER,REV_TIME')->where(['CRM_CON_TYPE' => 0, 'SP_CHARTER_NO' => $v['SP_CHARTER_NO']])->find() : '';
            $v['REVIEWER']              = $all_user[$audit['REVIEWER']];
            $v['REV_TIME']              = $audit['REV_TIME'];
            $sp_cat                     = [];
            $list                       = explode(',', $v['SP_CAT_CD']);
            foreach ($list as $k => $val) {
                $sp_cat[] = $cmn_cat[$val];
            }
            $v['SP_CAT_CD'] = implode(',', $sp_cat);
            unset($v['SP_CHARTER_NO']);
            $data[] = $v;
        }
        $this->exportCsv($data, $map);
    }

    public function export_contract()
    {
        $con_type   = BaseModel::conType();
        $con_status = [
            1 => '有效合同',
            2 => '合同已到期',
            3 => '合同作废',
        ];
        $map        = [
            ['field_name' => 'CON_NO', 'name' => '合同编号'],
            ['field_name' => 'CON_TYPE', 'name' => '合作类型'],
            ['field_name' => 'CON_NAME', 'name' => '合同简称'],
            ['field_name' => 'SP_NAME', 'name' => '供应商名称'],
            ['field_name' => 'START_TIME', 'name' => '合同起始时间'],
            ['field_name' => 'IS_RENEWAL', 'name' => '是否自动续约'],
            ['field_name' => 'CONTRACTOR', 'name' => '签约人'],
            ['field_name' => 'CON_STAT', 'name' => '合同状态'],
            ['field_name' => 'UPDATE_TIME', 'name' => '合同状态更新时间']
        ];
        $contract   = M('contract', 'tb_crm_')
            ->field('CON_NO,CON_TYPE,CON_NAME,SP_NAME,START_TIME,IS_RENEWAL,CONTRACTOR,CON_STAT,UPDATE_TIME')
            ->where(['CRM_CON_TYPE' => 0])
            ->select();
        foreach ($contract as &$v) {
            $v['CON_TYPE']   = $con_type[$v['CON_TYPE']];
            $v['IS_RENEWAL'] = $v['IS_RENEWAL'] == 1 ? '不自动续约' : '自动续约';
            $v['CON_STAT']   = $con_status[$v['CON_STAT']];
        }
        $this->exportCsv($contract, $map);
    }

    public function client()
    {
        return;
        $sup_model = M('crm_sp_supplier', 'tb_');
        $b2b_model = M('b2b_info', 'tb_');
        $list      = $b2b_model->field('ID, CLIENT_NAME_EN, CLIENT_NAME')->select();
        foreach ($list as $v) {
            $where     = [
                'SP_NAME'        => $v['CLIENT_NAME'],
                '_logic'         => 'or',
                'SP_RES_NAME_EN' => $v['CLIENT_NAME_EN'],
            ];
            $client_id = $sup_model->where($where)->getField('ID');
            if (!$client_id) {
                continue;
            }
            $b2b_model->where(['ID' => $v['ID']])->save(['client_id' => $client_id]);
        }
    }

    //采购退货出库数据修复
    public function pur_out_stock()
    {
        return;
        $order_ids = ['412' => 'CGTH201906280001', '383' => 'CGTH201906280002'];
        foreach ($order_ids as $k => $v) {
            $res = (new WarehousingService())->out_stock_repair($v, $k);
            Logs(json_encode($res), __FUNCTION__, __CLASS__);
            sleep(1);
        }
        v($res);
    }

    //采购退货出库数据修复
    public function repair_pur_out_stock_status()
    {
        return;
        $sql        = "SELECT re.return_no from tb_pur_return re
          LEFT JOIN tb_wms_batch_order bo on re.return_no=bo.ORD_ID
          where re.outbound_status=1 and bo.use_type=1 and re.status_cd='N002640200'";
        $res        = M()->query($sql);
        $return_nos = array_column($res, 'return_no');
        $data       = [
            'status_cd'         => 'N002640100',
            'outbound_status'   => 0,
            'updated_by'        => null,
            'out_of_stock_time' => null,
        ];
        $res_sql    = M('pur_return', 'tb_')->where(['return_no' => ['in', $return_nos]])->save($data);
        echo $res_sql;
        die;
    }

    public function own_logistic()
    {
        $warehouse_logistics = [
            '澳大利亚万邑通仓'  => '万邑通',
            '英国万邑通仓'    => '万邑通',
            '俄罗斯艾姆勒仓'   => '艾姆勒',
            '美国东部万邑通仓'  => '万邑通',
            '美国西部万邑通仓'  => '万邑通',
            '美国新泽西出口易仓' => '出口易',
            '美国南部万邑通仓'  => '万邑通',
            '比利时万邑通仓'   => '万邑通',
            '德国易达仓'     => '易达',
            '俄罗斯旺集仓'    => '旺集物流',
            '法国谷仓QY'    => '易可达',
            '德国天坤仓'     => '天坤仓自有物流',
            '捷克谷仓XZ'    => '易可达',
            '美国谷仓'      => '易可达',
            '韩国韵达仓'     => '中通快递',
            '深圳发网仓'     => '发网自有物流',
            '青浦发网仓'     => '发网自有物流',
            '意大利谷仓'     => '易可达',
            '西班牙谷仓QY'   => '易可达',
            '出口易法国仓'    => '出口易',
            '万邑通德国仓'    => '万邑通',
            '西班牙易达仓'    => '易达',
            '捷网俄罗斯仓'    => '捷网物流',
            '法国谷仓YZ'    => '易可达',
            '捷克谷仓QY'    => '易可达',
        ];
        $warehouses          = array_keys($warehouse_logistics);
        $cd_model            = M('ms_cmn_cd', 'tb_');
        foreach ($warehouses as $v) {
            $cd = $cd_model->where(['CD_VAL' => $v, 'CD' => ['like', 'N00068%']])->find();
            if (empty($cd)) {
                $no[] = $v;
            }
        }
        Logs(json_encode($no), __FUNCTION__ . '不存在仓库', __CLASS__);

        $postage_model = M('lgt_postage_model', 'tb_');
        $cd_model      = M('ms_cmn_cd', 'tb_');
        $own_model     = M('ms_logistics_own_config', 'tb_');

        $logis = M('ms_logistics_mode', 'tb_')
            ->field('tb_ms_logistics_mode.ID,tb_ms_logistics_mode.LOGISTICS_CODE, tb_ms_logistics_mode.POSTAGE_ID,tb_ms_cmn_cd.CD_VAL AS LOGISTICS_CODE_VAL')
            ->join('LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_ms_logistics_mode.LOGISTICS_CODE')
            ->select();

        foreach ($logis as $key => $value) {
            $post_arr       = explode(',', $value['POSTAGE_ID']);
            $postage        = $postage_model->field('OUT_AREAS')->where(['ID' => ['in', $post_arr]])->select();
            $own_warehouses = [];
            foreach ($postage as $k => $v) {
                $cd_arr = explode(',', $v['OUT_AREAS']);
                $cds    = $cd_model->field('CD,CD_VAL')->where(['CD' => ['in', $cd_arr]])->select();
                foreach ($cds as $cd) {
                    if (in_array($cd['CD_VAL'], $warehouses)) {
                        $own_warehouses[]              = $cd['CD'];
                        $own_warehouses_val[$cd['CD']] = $cd['CD_VAL'];
                    }
                }
            }
            if (empty($own_warehouses)) {
                continue;
            }
            $own_warehouses = array_unique($own_warehouses);
            foreach ($own_warehouses as $own) {
                if ($value['LOGISTICS_CODE_VAL'] != $warehouse_logistics[$own_warehouses_val[$own]]) {
                    Logs($value['LOGISTICS_CODE_VAL'], __FUNCTION__, __CLASS__);
                    continue;
                }
                $data = [
                    'warehouse_code'             => $own,
                    'logistics_company_code'     => $value['LOGISTICS_CODE'],
                    'logistics_mode_id'          => $value['ID'],
                    'is_own_logistics_warehouse' => 1,
                    'created_by'                 => 'Erpadmin',
                    'updated_by'                 => 'Erpadmin',
                ];
                $res  = $own_model->add($data);

                if (!$res) {
                    $fail[] = $data;
                    Logs(json_encode($data), __FUNCTION__ . '配置失败', __CLASS__);
                }
            }
        }
        echo '不存在的仓库：';
        echo '<pre>';
        var_dump($no);
        echo '<br>-----------------------------------------';
        echo '配置失败：';
        echo '<pre>';
        var_dump($fail);
        echo $res;
        die;
    }

    public function ship_end()
    {
        $data  = json_decode('[{"relevance_id":6549,"procurement_number":"RN201812140012-001"},
            {"relevance_id":6630,"procurement_number":"RN201812210018-001"},
            {"relevance_id":6630,"procurement_number":"RN201812210018-001"},
            {"relevance_id":6630,"procurement_number":"RN201812210018-001"},
            {"relevance_id":6630,"procurement_number":"RN201812210018-001"},
            {"relevance_id":6630,"procurement_number":"RN201812210018-001"},
            {"relevance_id":6797,"procurement_number":"RN201812270002-002"},
            {"relevance_id":6737,"procurement_number":"RN201812280002-002"},
            {"relevance_id":6737,"procurement_number":"RN201812280002-002"},
            {"relevance_id":6737,"procurement_number":"RN201812280002-002"},
            {"relevance_id":6737,"procurement_number":"RN201812280002-002"},
            {"relevance_id":6756,"procurement_number":"RN201812290006-001"},
            {"relevance_id":6756,"procurement_number":"RN201812290006-001"},
            {"relevance_id":6756,"procurement_number":"RN201812290006-001"},
            {"relevance_id":6756,"procurement_number":"RN201812290006-001"},
            {"relevance_id":6904,"procurement_number":"RN201901040010-002"},
            {"relevance_id":6903,"procurement_number":"RN201901080016-001"},
            {"relevance_id":6905,"procurement_number":"RN201901080020-001"},
            {"relevance_id":6905,"procurement_number":"RN201901080020-001"},
            {"relevance_id":6906,"procurement_number":"RN201901080020-002"},
            {"relevance_id":7035,"procurement_number":"RN201901160004-001"},
            {"relevance_id":7153,"procurement_number":"RN201901170019-001"},
            {"relevance_id":7090,"procurement_number":"RN201901180013-001"},
            {"relevance_id":7090,"procurement_number":"RN201901180013-001"},
            {"relevance_id":7090,"procurement_number":"RN201901180013-001"},
            {"relevance_id":7121,"procurement_number":"RN201901180018-001"},
            {"relevance_id":7145,"procurement_number":"RN201901180019-001"},
            {"relevance_id":7145,"procurement_number":"RN201901180019-001"},
            {"relevance_id":7145,"procurement_number":"RN201901180019-001"},
            {"relevance_id":7145,"procurement_number":"RN201901180019-001"},
            {"relevance_id":7145,"procurement_number":"RN201901180019-001"},
            {"relevance_id":7281,"procurement_number":"RN201901240011-001"},
            {"relevance_id":7281,"procurement_number":"RN201901240011-001"},
            {"relevance_id":7281,"procurement_number":"RN201901240011-001"},
            {"relevance_id":7283,"procurement_number":"RN201901280014-001"},
            {"relevance_id":7282,"procurement_number":"RN201901280018-001"},
            {"relevance_id":7282,"procurement_number":"RN201901280018-001"},
            {"relevance_id":7408,"procurement_number":"RN201902140005-002"},
            {"relevance_id":7408,"procurement_number":"RN201902140005-002"},
            {"relevance_id":7370,"procurement_number":"RN201902140011-001"},
            {"relevance_id":7400,"procurement_number":"RN201902140018-001"},
            {"relevance_id":7410,"procurement_number":"RN201902150030-002"},
            {"relevance_id":7410,"procurement_number":"RN201902150030-002"},
            {"relevance_id":7463,"procurement_number":"RN201902200011-002"},
            {"relevance_id":7459,"procurement_number":"RN201902200013-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7460,"procurement_number":"RN201902200016-001"},
            {"relevance_id":7538,"procurement_number":"RN201902210013-001"},
            {"relevance_id":7552,"procurement_number":"RN201902220003-001"},
            {"relevance_id":7552,"procurement_number":"RN201902220003-001"},
            {"relevance_id":7552,"procurement_number":"RN201902220003-001"},
            {"relevance_id":7568,"procurement_number":"RN201902220008-001"},
            {"relevance_id":7568,"procurement_number":"RN201902220008-001"},
            {"relevance_id":7568,"procurement_number":"RN201902220008-001"},
            {"relevance_id":7568,"procurement_number":"RN201902220008-001"},
            {"relevance_id":7540,"procurement_number":"RN201902220017-001"},
            {"relevance_id":7572,"procurement_number":"RN201902220023-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7592,"procurement_number":"RN201902280018-001"},
            {"relevance_id":7715,"procurement_number":"RN201903050019-001"},
            {"relevance_id":7716,"procurement_number":"RN201903050019-002"},
            {"relevance_id":7709,"procurement_number":"RN201903050020-002"},
            {"relevance_id":7718,"procurement_number":"RN201903060005-001"},
            {"relevance_id":7718,"procurement_number":"RN201903060005-001"},
            {"relevance_id":7718,"procurement_number":"RN201903060005-001"},
            {"relevance_id":7827,"procurement_number":"RN201903130006-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7870,"procurement_number":"RN201903150004-001"},
            {"relevance_id":7922,"procurement_number":"RN201903150018-001"},
            {"relevance_id":7922,"procurement_number":"RN201903150018-001"},
            {"relevance_id":7922,"procurement_number":"RN201903150018-001"},
            {"relevance_id":7922,"procurement_number":"RN201903150018-001"},
            {"relevance_id":7922,"procurement_number":"RN201903150018-001"},
            {"relevance_id":7945,"procurement_number":"RN201903150029-001"},
            {"relevance_id":7945,"procurement_number":"RN201903150029-001"},
            {"relevance_id":7945,"procurement_number":"RN201903150029-001"},
            {"relevance_id":7945,"procurement_number":"RN201903150029-001"},
            {"relevance_id":7945,"procurement_number":"RN201903150029-001"},
            {"relevance_id":7946,"procurement_number":"RN201903150029-002"},
            {"relevance_id":7946,"procurement_number":"RN201903150029-002"},
            {"relevance_id":7936,"procurement_number":"RN201903150032-001"},
            {"relevance_id":7936,"procurement_number":"RN201903150032-001"},
            {"relevance_id":7936,"procurement_number":"RN201903150032-001"},
            {"relevance_id":7918,"procurement_number":"RN201903180016-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7994,"procurement_number":"RN201903190006-001"},
            {"relevance_id":7968,"procurement_number":"RN201903200012-001"},
            {"relevance_id":8007,"procurement_number":"RN201903210011-001"},
            {"relevance_id":8106,"procurement_number":"RN201903290008-001"},
            {"relevance_id":8150,"procurement_number":"RN201903290017-001"},
            {"relevance_id":8126,"procurement_number":"RN201904010021-001"},
            {"relevance_id":8126,"procurement_number":"RN201904010021-001"},
            {"relevance_id":8126,"procurement_number":"RN201904010021-001"},
            {"relevance_id":8126,"procurement_number":"RN201904010021-001"},
            {"relevance_id":8126,"procurement_number":"RN201904010021-001"},
            {"relevance_id":8171,"procurement_number":"RN201904010040-001"},
            {"relevance_id":8171,"procurement_number":"RN201904010040-001"},
            {"relevance_id":8171,"procurement_number":"RN201904010040-001"},
            {"relevance_id":8171,"procurement_number":"RN201904010040-001"},
            {"relevance_id":8171,"procurement_number":"RN201904010040-001"},
            {"relevance_id":8125,"procurement_number":"RN201904020001-001"},
            {"relevance_id":8153,"procurement_number":"RN201904020011-001"},
            {"relevance_id":8153,"procurement_number":"RN201904020011-001"},
            {"relevance_id":8153,"procurement_number":"RN201904020011-001"},
            {"relevance_id":8153,"procurement_number":"RN201904020011-001"},
            {"relevance_id":8170,"procurement_number":"RN201904020012-001"},
            {"relevance_id":8437,"procurement_number":"RN201904220012-001"},
            {"relevance_id":8437,"procurement_number":"RN201904220012-001"},
            {"relevance_id":8506,"procurement_number":"RN201904260002-001"},
            {"relevance_id":8639,"procurement_number":"RN201905090002-001"},
            {"relevance_id":8639,"procurement_number":"RN201905090002-001"},
            {"relevance_id":8639,"procurement_number":"RN201905090002-001"},
            {"relevance_id":8861,"procurement_number":"RN201905230011-002"}]', true);
        $logic = D('Purchase/Ship', 'Logic');
        foreach ($data as $v) {
            $res = $logic->shipEndRe($v['relevance_id']);
            if (!$res) {
                $logs[] = $v;
                Logs(json_encode($v), __FUNCTION__ . '标记完结失败', __CLASS__);
            }
        }
        v($logs);
    }

    //调拨在途库存导出
    public function alloOnWayExport()
    {
        $sql  = 'SELECT
         b.allo_no AS 调拨单号,
         (SELECT e.CD_VAL FROM tb_ms_cmn_cd e WHERE e.cd = b.allo_out_team) AS 调出团队,
         (SELECT e.CD_VAL FROM tb_ms_cmn_cd e WHERE e.cd = b.allo_out_warehouse) AS 调出仓库,
         (SELECT e.CD_VAL FROM tb_ms_cmn_cd e WHERE e.cd = b.allo_in_warehouse) AS 调入仓库,
         c.SKU_ID,
         c.batch_code AS 批次号,
         a.occupy_num AS 数量,
         d.unit_price AS 采购单价
        FROM
         tb_wms_batch_order a
        INNER JOIN tb_wms_allo b ON a.ORD_ID = b.allo_no
        LEFT JOIN tb_wms_batch c ON a.batch_id = c.id
        LEFT JOIN tb_wms_stream d ON c.stream_id = d.id
        WHERE
         b.state = \'N001970300\'
        AND a.use_type = 10';
        $data = M()->query($sql);
        $map  = [
            ['field_name' => '调拨单号', 'name' => '调拨单号'],
            ['field_name' => '调出团队', 'name' => '调出团队'],
            ['field_name' => '调出仓库', 'name' => '调出仓库'],
            ['field_name' => '调入仓库', 'name' => '调入仓库'],
            ['field_name' => 'SKU_ID', 'name' => 'SKU_ID'],
            ['field_name' => '批次号', 'name' => '批次号'],
            ['field_name' => '数量', 'name' => '数量'],
            ['field_name' => '采购单价', 'name' => '采购单价'],
        ];
        $this->exportCsv($data, $map);
    }

    public function importQualification()
    {
        $data  = '[{"我方公司CODE":"N001240300","证照名称":"自理报检企业备案登记证明书","发证日":"2015年2月3日","到期日":"2020年2月2日","续证时间":"2020年2月2日","发证机关":"上海出入境检验检疫局","续证地点":"","续证材料":"","对应负责部门":"customs","注意事项":"名称、地址、法人变更后需要变更证照"},{"我方公司CODE":"N001240300","证照名称":"互联网药品信息服务资格证书","发证日":"2013年4月25日","到期日":"2018年4月24日","续证时间":"2017年9月24日","发证机关":"浦东市场监督管理局","续证地点":"合欢路2号","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240300","证照名称":"对外贸易经营者备案登记表","发证日":"2017年5月16日","到期日":"长期","续证时间":"","发证机关":"浦东市场监督管理局","续证地点":"合欢路2号","续证材料":"","对应负责部门":"","注意事项":"名称、地址、法人变更后需要变更证照"},{"我方公司CODE":"N001240300","证照名称":"中国海关报关单位注册登记证书","发证日":"2017年6月6日","到期日":"长期","续证时间":"","发证机关":"上海浦东海关","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240300","证照名称":"食品经营许可证","发证日":"2016年9月29日","到期日":"2019年9月28日","续证时间":"2019年7月","发证机关":"浦东市场监督管理局","续证地点":"合欢路2号","续证材料":"","对应负责部门":"","注意事项":"零售批发"},{"我方公司CODE":"N001240300","证照名称":"酒类商品零售许可证","发证日":"2016年11月16日","到期日":"2019年11日15","续证时间":"","发证机关":"酒类专卖管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"食品经营许可证","发证日":"2018年7月13日","到期日":"2023年7月12日","续证时间":"2023年7月12日","发证机关":"上海市浦东新区市场监督管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"对外经营者备案登记表","发证日":"2017年5月3日","到期日":"长期","续证时间":"","发证机关":"对外贸易经营者备案登记局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240700","证照名称":"公众聚集场所投入使用 营业前消防安全检查合格证","发证日":"2017年5月3日","到期日":"长期","续证时间":"","发证机关":"上海市长宁区公安消防支队","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"出入境检验检疫报检企业备案表","发证日":"2017年4月11日","到期日":"长期","续证时间":"","发证机关":"中华人民共和国上海出入境检验检疫局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"海关报关单位注册登记证书","发证日":"2017年5月4日","到期日":"长期","续证时间":"","发证机关":"浦东海关","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243000","证照名称":"食品经营许可证","发证日":"2018年5月19日","到期日":"2023年5月18日","续证时间":"","发证机关":"上海市浦东新区市场监督管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"酒类商品批发许可证","发证日":"2016年12月21日","到期日":"2019年12月20日","续证时间":"2019年12月20日","发证机关":"上海市酒类专卖管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240500","证照名称":"酒类商品零售许可证","发证日":"2017年4月18日","到期日":"2020年4月17日","续证时间":"2020年4月17日","发证机关":"上海市浦东新区酒类专卖管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240700","证照名称":"酒类商品零售许可证","发证日":"2017年9月30日","到期日":"2020年9月29日","续证时间":"2020年9月29日","发证机关":"上海市长宁区酒类专卖管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240700","证照名称":"食品经营许可证","发证日":"2017年4月12日","到期日":"2021年7月25日","续证时间":"2021年7月25日","发证机关":"上海市长宁区市场监督管理局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242600","证照名称":"对外经营者备案登记表","发证日":"2018年8月1日","到期日":"长期","续证时间":"","发证机关":"对外贸易经营者备案登记局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242600","证照名称":"海关报关单位注册登记证书","发证日":"2017年3月31日","到期日":"长期","续证时间":"","发证机关":"中华人民共和国深圳海关","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242700","证照名称":"海关报关单位注册登记证书","发证日":"2017年6月8日","到期日":"长期","续证时间":"","发证机关":"中国人民共和国嘉定海关","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242700","证照名称":"对外贸易经营者备案登记表","发证日":"2017年5月19日","到期日":"长期","续证时间":"","发证机关":"对外贸易经营者备案登记局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242700","证照名称":"出入境检验检疫报检企业备案表","发证日":"2017年6月1日","到期日":"长期","续证时间":"","发证机关":"中华人民共和国上海出入境检验检疫局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240200","证照名称":"上海市高新技术成果转化项目","发证日":"2014年11月10日","到期日":"2019年11月10日","续证时间":"2019年1月1日","发证机关":"上海市高新技术成果转化项目认定委员会（代理）","续证地点":"","续证材料":"","对应负责部门":"","注意事项":"代理"},{"我方公司CODE":"N001240200","证照名称":"海关报关单位注册登记证书","发证日":"2017年6月12日","到期日":"长期","续证时间":"","发证机关":"外高桥海关","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240200","证照名称":"对外经营者备案登记表","发证日":"2017年6月6日","到期日":"长期","续证时间":"","发证机关":"对外贸易经营者备案登记局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240200","证照名称":"出入境检验检疫报检企业备案表","发证日":"2017年6月7日","到期日":"长期","续证时间":"","发证机关":"上海出入境检验检疫局","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001240200","证照名称":"高新技术企业","发证日":"2016年11月24日","到期日":"2019年11月24日","续证时间":"2019年1月1日","发证机关":"上海科委（代理）","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244717","证照名称":"IZENE SPAIN INTERNATIONAL, S.L.公司成立文本","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244717","证照名称":"IZENE SPAIN INTERNATIONAL, S.L.公司税号（NIF）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244717","证照名称":"IZENE SPAIN INTERNATIONAL, S.L.公司章程","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244717","证照名称":"YEOGIRL YUN--外国人身份证号码NIE","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"IZENE MERKENUNIE-注册文件扫描件","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"IZENE MERKENUNIE注册信息摘录","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"IZENE-比利时公司成立官方信息","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"ING business card","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"比利时公司章程20181205","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244300","证照名称":"Activation vat number Izene","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"certificate of registration注册证明","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"ABN","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"IZENE AUSTRALIA PTY LTD - Constitution公司章程","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"ASIC_Corporate key","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"company profile","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"consent ot act a director董事证明","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"consent to act as a secretary秘书证明","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"forms manager - ASIC","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"IZENE AUSTRALIA PTY LTD-股东董事名册","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"minutes of meeting会议记录","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"register of officers","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"Appointment of public officer","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"share certificate 2股权证明","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242400","证照名称":"TFN-Tax file number税号","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243200","证照名称":"Gshopper Dauphin LLC-公司注册登记证 Certificate of Entry","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243200","证照名称":"税务登记证Tax Certificate","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243200","证照名称":"公司章程Charter","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243200","证照名称":"决议Resolution","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"营业执照","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"公司章程","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"商会登记证","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"增值税登记证书","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"广告信件","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"登报声明","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"INSEE 证书","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"来自法国税务局的调查问卷","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244700","证照名称":"EORI 号码注册文件","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"Articles of incorporation iZENEhl B.V.","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"iZENEhl B.V. - Business Register Extract","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"IZENEHL - letter about VAT nr page 2","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"IZENEHL - letter about VAT nr","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"iZENEhl - Shareholder register","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242800","证照名称":"VAT No.","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"IzeneDE International Tech GmbH-Articles of Association","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"General Engagement Terms","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"2018-01-23 Finanzverwaltung","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"2019-01-28 Notare Oertel & Thoma（营业执照）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"2019-03-22 Generalzolldirektion-EORI-IzeneDE","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"190305 AP The official notification of the tax number","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"190305 AP The official notification of the VAT-ID number for IzeneDE int","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"IzeneDE international Tech Gmbh - local tax number","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"IzeneDE International Tech GmbH- Amtsgericht Düsseldorf--法院登记信","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"IzeneDE- VAT","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"Notarization - 公证书(上）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001243100","证照名称":"Notarization - 公证书(下）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"IZENE HOLDING PYT.LTD-Share Certificate","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"ABN-IZENE Holding Pty. Ltd.-更新","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"certificate_of_registration_IZENE HOLDING (Yafeng Cai - 12_10_2018 14_26_53) 公司营业执照","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"company application form 公司申请表格","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"Company structure 公司架构","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"Company_Constitution 公司章程","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"TFN-IZENE Holding Pty. Ltd.（税号）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244701","证照名称":"澳洲公司IZENE HOLDING PTY LTD--GST","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"HRB法院登记处--登记材料","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"Notarization","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"service agreement --- EGSZ & Zaario","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"Contract--ABD&Zaario","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"股东名册","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244200","证照名称":"德国Zaario公司信件（法务保管）20181113","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"BTW税号","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"BSN","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"iZENEeu- Memorandum of Association-公司章程(荷兰语）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"iZENEeu-kvk-公司成立证明（荷兰语）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"izene-公司章程（英文）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244712","证照名称":"izene-kvk（公司成立证明）（英文）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"izene us 注册证书","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"Coporate Bylaws - 公司章程","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"IZENE US INC - 2017annual franchise tax report","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"register of director","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"share certificate","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"Share Certificate股权认购书（空）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"Stock Transfer Ledger Sheet - 认股表","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"Taxpayer Number","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"2017income tax return","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"Apostille - 特拉华州政府公证","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242900","证照名称":"certificate of incumbency","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242500","证照名称":"[IZENE EU INC LIMITED] - certificate of incorporation","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242500","证照名称":"[IZENE EU INC LIMITED] - Form of Constitution","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242500","证照名称":"certificate of share","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001242500","证照名称":"minutes of meeting 20170502","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"A series of Share Certificates","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Certificate of Good Standing-26 Mar 2013","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Certificate of Incorporation_iZENEtech","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Certificate of Incumbency dd 6 Sep 2017 (ICSL)","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Certificate of Good Standing dd 7 Nov 17","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Certificate of Good Standing dd 28 May 18","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Memorandum and Articles of Association -8 Apr 2013","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Memorandum and Articles of Association -16 Feb 2012","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Memorandum and Articles of Association -18 May 2015","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"iZENEtech - Memorandum and Articles of Association -21 May 2015","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Register of Directors & Officers- May.12.2017","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Written Resolusion-16 Feb 2012","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"Yeogirl Yun 开曼股权证明-原件","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001241100","证照名称":"股东名册","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"投资登记证Investment Registration Certificate -VN（越南语）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"投资登记证Investment Registration Certificate（英文）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"税务事项通知书（iZENE)","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"商业登记证Buisness Registration Certificate- VN（越南语）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"商业登记证（英文版）Buisness Registration Certificate- EN（英文）","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""},{"我方公司CODE":"N001244702","证照名称":"开户证明-HOP DONG KIEM DE NGHI MO TAI KHOAN - CTY IZENE","发证日":"","到期日":"长期","续证时间":"","发证机关":"","续证地点":"","续证材料":"","对应负责部门":"","注意事项":""}]';
        $data  = json_decode($data, true);
        $model = M('qualification', 'tb_crm_');
        $model->startTrans();
        foreach ($data as $value) {
            if ($value['到期日'] == '长期') {
                $is_long_time = 1;
                $expire_date  = null;
            } else {
                $is_long_time = 0;
                $expire_date  = $value['到期日'];
            }
            $add_data[] = [
                'number'           => 'GSZZ' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('qualification', 4),
                'our_company_code' => $value['我方公司CODE'],
                'name'             => $value['证照名称'],
                'issue_date'       => $value['发证日'],
                'expire_date'      => $expire_date,
                'renew_date'       => $value['续证时间'],
                'is_long_time'     => $is_long_time,
                'issue_office'     => $value['发证机关'],
                'renew_address'    => $value['续证地点'],
                'renew_material'   => $value['续证材料'],
                'department'       => $value['对应负责部门'],
                'precautions'      => $value['注意事项'],
                'created_by'       => 'admin',
                'updated_by'       => 'admin'
            ];
        }
        if (!$model->addAll($add_data)) {
            echo 'fail';
            $model->rollback();
            die;
        }
        echo 'success';
        $model->commit();
    }

    //虚拟仓采购订单取消，扣减在途库存历史数据处理
    public function onWayHandle()
    {
        $sql  = "SELECT
            rel.relevance_id,
            od.order_id,
            od.procurement_number,
            gi.sku_information,
            gi.goods_number,
            ba.M_ID
        FROM
            tb_pur_relevance_order rel
        LEFT JOIN tb_pur_order_detail od ON rel.order_id = od.order_id
        LEFT JOIN tb_pur_goods_information gi ON rel.relevance_id = gi.relevance_id
        LEFT JOIN bbm_admin ba ON rel.last_update_user = ba.M_NAME
        WHERE
            od.warehouse = 'N000680800' AND rel.order_status='N001320500';";
        $list = M()->query($sql);
        foreach ($list as $item) {
            $param[] = [
                "operatorId"      => $item['M_ID'],
                "skuId"           => $item['sku_information'],
                "purchaseOrderNo" => $item['procurement_number'],
                "num"             => $item['goods_number'],
            ];
        }
        $res = ApiModel::onWayEnd($param, true);
        v($res);
    }

    //银行账号导入
    public function importAccount()
    {
        try {
            $model      = new Model();
            $excel_path = './account.xlsx';
            vendor('PHPExcel.PHPExcel');
            $input_file_type = PHPExcel_IOFactory::identify($excel_path);
            $obj_reader      = PHPExcel_IOFactory::createReader($input_file_type);
            $obj_excel       = $obj_reader->load($excel_path);
            $sheet           = $obj_excel->getSheet(0);
            $max_row         = $sheet->getHighestRow();
            $max_cloumn      = $sheet->getHighestColumn();

            $base = $index = $point = 'A';
            for ($i = 1; $i <= 26; $i++) {
                $name [$index] = trim((string)$sheet->getCell($index . 1)->getValue());
                $index++;
                if ($i % 26 == 0) {
                    $index = $base . $point;
                    $base++;
                }
            }

            $model->startTrans();
            $cd_model = M('cmn_cd', 'tb_ms_');
            for ($j = 2; $j <= $max_row; $j++) {
                $company         = $obj_excel->getActiveSheet()->getCell("A" . $j)->getValue();
                $account_type    = $obj_excel->getActiveSheet()->getCell("B" . $j)->getValue();
                $payment_channel = $obj_excel->getActiveSheet()->getCell("C" . $j)->getValue();
                $open_bank       = $obj_excel->getActiveSheet()->getCell("D" . $j)->getValue();
                $account_bank    = $obj_excel->getActiveSheet()->getCell("E" . $j)->getValue();
                $currency        = $obj_excel->getActiveSheet()->getCell("F" . $j)->getValue();
                $swift_code      = $obj_excel->getActiveSheet()->getCell("G" . $j)->getValue();
                $bsb_no          = $obj_excel->getActiveSheet()->getCell("H" . $j)->getValue();
                $reason          = $obj_excel->getActiveSheet()->getCell("I" . $j)->getValue();
                $status_name     = $obj_excel->getActiveSheet()->getCell("J" . $j)->getValue();

                $company_code = $cd_model->where(['CD_VAL' => trim($company), 'CD' => ['like', "N00124%"]])->getField('CD');
                if (!$company_code) {
                    $model->rollback();
                    die('fail');
                }
                $currency_code = $cd_model->where(['CD_VAL' => trim($currency), 'CD' => ['like', "N00059%"]])->getField('CD');
                if (!$currency_code) {
                    $model->rollback();
                    die('fail');
                }
                $payment_channel_code = $cd_model->where(['CD_VAL' => trim($payment_channel), 'CD' => ['like', "N00100%"]])->getField('CD');
                if (!$payment_channel_code) {
                    $model->rollback();
                    die('fail');
                }
                $data[] = [
                    'company_code'       => $company_code,
                    'account_type'       => 'N001930300',
                    'payment_channel_cd' => $payment_channel_code,
                    'open_bank'          => $open_bank,
                    'account_bank'       => $account_bank,
                    'currency_code'      => $currency_code,
                    'state'              => 1,
                    'update_user'        => 9186,
                    'update_time'        => dateTime(),
                    'create_user'        => 9186,
                    'create_time'        => dateTime(),
                ];
            }
            if (empty($data)) {
                die('data is empty');
            }
            $res = $model->table('tb_fin_account_bank')->addAll($data);
            if (!$res) {
                $model->rollback();
                echo '<pre>';var_dump($data);
                die('add fail');
            }
            $model->commit();
            die('success');
        } catch (Exception $e) {
            $model->rollback();
            v($e->getMessage());
        }
    }

    public function getCellData($currentRow)
    {
        $base = $index = $point = 'A';
        for ($i = 1; $i <= $this->excel->max_column_int; $i++) {
            $name [$index] = trim((string)$this->excel->sheet->getCell($index . $currentRow)->getValue());
            $index++;
            if ($i % 26 == 0) {
                $index = $base . $point;
                $base++; 
            }
        }
        return $name;
    }

//    public function export()
//    {
//        $export = new ExportExcelModel();
//        $export->attributes = [
//            'A' => ['name' => L('店铺编号'), 'field_name' => 'ID'],
//            'B' => ['name' => L('店铺名称'), 'field_name' => 'STORE_NAME'],
//            'C' => ['name' => L('店铺别名'), 'field_name' => 'MERCHANT_ID'],
//            'D' => ['name' => L('平台名称'), 'field_name' => 'PLAT_NAME'],
//            'E' => ['name' => L('国家字段'), 'field_name' => 'zh_name'],
//            'F' => ['name' => L('销售团队'), 'field_name' => 'SALE_TEAM'],
//            'G' => ['name' => L('店铺状态'), 'field_name' => 'STORE_ZN_STATUS'],
//            'H' => ['name' => L('授权状态'), 'field_name' => 'STATUS'],
//            'I' => ['name' => L('店铺主页地址'), 'field_name' => 'STORE_INDEX_URL'],
//            'J' => ['name' => L('店铺后台地址'), 'field_name' => 'STORE_BACKSTAGE_URL'],
//            'K' => ['name' => L('商品主链接'), 'field_name' => 'PRODUCT_DETAIL_URL_MARK'],
//            'L' => ['name' => L('注册公司'), 'field_name' => 'company'],
//        ];
//        $model = new TbMsStoreModel();
//        //$export->title = L('会议记录' );
//        $export->fileName = L('店铺管理列表');
//
//        $data = [];
//        unset($data['count']);
//        //var_dump($data);die;
//        $export->data = $data;
//
//        if ($export->getError()) {
//            $this->error($export->getError());
//        }
//        $export->export();
//    }
//
//    public function exportExcel()
//    {
//        ini_set('memory_limit', '512M');
//        $xlsTitle = iconv('utf-8', 'gb2312', 'test');//文件名称
//        $fileName = date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
//        vendor("PHPExcel.PHPExcel");
//        $objPHPExcel = new PHPExcel();
//        //$objPHPExcel->getActiveSheet()->mergeCells('A1:B1');//合并单元格
//        $objPHPExcel->getActiveSheet()->setShowGridlines(false);//去除网格线
//        header('pragma:public');
//        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
//        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
//        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//        $objWriter->save('php://output');
//        exit;
//    }


    public function ftp_test()
    {
        try {
            $file = I('name');
            if (empty($file)) {
                $file = 'test.xml';
            }
            v(FtpModel::client()->ftpPut($file));
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function sftp_test()
    {
        try {
            $file = I('name');
            if (empty($file)) {
                $file = 'test.xml';
            }
            v(SftpModel::client()->put($file));
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function xml_test()
    {
        try {
            $xml = '<?xml version="1.0" encoding="UTF-8"?><Document><CstmrCdtTrfInitn><PmtInf><PmtInfId></PmtInfId><ReqdExctnDt>2018-08-12</ReqdExctnDt><DbtrAcct><Id><Othr><Id>44707988988</Id></Othr></Id></DbtrAcct><UltmtDbtr><Nm></Nm></UltmtDbtr><CdtTrfTxInf><PmtId><EndToEndId>1808120101</EndToEndId></PmtId><Cdtr><Nm>TEST ONE TIME BENEFICIARY</Nm><PstlAdr><StrtNm>ONE TIME BENE ADDRESS 1</StrtNm><TwnNm>ONE TIME TOWN</TwnNm><PstCd>00000</PstCd><CtrySubDvsn>ZHEJIANG</CtrySubDvsn><Ctry>HK</Ctry></PstlAdr><Id><OrgId><Othr><Id>ONETIMECORPID</Id></Othr></OrgId><PrvtId><Othr><Id>ONETIMEOTHRID</Id><SchmeNm><Cd>027</Cd></SchmeNm></Othr></PrvtId></Id></Cdtr><CdtrAgt><FinInstnId><Nm>JP MORGAN CHASE HONG KONG</Nm><BIC>CHASHKHH</BIC><Othr><Id>123456</Id><SchmeNm><Cd>027</Cd></SchmeNm></Othr><PstlAdr><StrtNm>ONE TIME BENE BANK ADDRESS 1</StrtNm><TwnNm>TOWN</TwnNm><PstCd>12334</PstCd><CtrySubDvsn>HONG KONG</CtrySubDvsn><Ctry>HK</Ctry></PstlAdr></FinInstnId><BrnchId><Nm>HONG KONG PAY</Nm></BrnchId></CdtrAgt><IntrmyAgt1><FinInstnId><Nm>BANK OF CHINA(HONGKONG) LTD.</Nm><BIC>BKCHHKHH</BIC><Othr><Id>BOACHATS</Id><SchmeNm><Cd>027</Cd></SchmeNm></Othr><PstlAdr><Ctry>HK</Ctry></PstlAdr></FinInstnId></IntrmyAgt1><IntrmyAgt1Acct><Id><Othr><Id>BOA123456</Id></Othr></Id></IntrmyAgt1Acct><CdtrAcct><Id><Othr><Id>00113323951</Id><SchmeNm><Cd>2</Cd></SchmeNm></Othr></Id><Ccy>HKD</Ccy><Tp><Cd>02</Cd></Tp></CdtrAcct><Amt><InstdAmt>17.30</InstdAmt><EqvtAmt><Amt></Amt><CcyOfTrf>HKD</CcyOfTrf></EqvtAmt></Amt><XchgRateInf><XchgRate>1.012356</XchgRate><RateTp></RateTp><CtrctId></CtrctId></XchgRateInf><Purp><Cd>001</Cd></Purp><ChrgBr>002</ChrgBr><RltdRmtInf><RmtLctnMtd>07</RmtLctnMtd><RmtLctnElctrncAdr>a@b.com</RmtLctnElctrncAdr></RltdRmtInf><RgltryRptg><Dtls><Cd></Cd><Tp></Tp><Ctry></Ctry><Inf>DETAILED INFORMATION 1</Inf></Dtls><DbtCdtRptgInd></DbtCdtRptgInd><Authrty><Nm></Nm><Ctry></Ctry></Authrty></RgltryRptg><CustomAttributes><TrxnCode>DWIR</TrxnCode><TrfRmtncIdntfr1>BATCH18081200001</TrfRmtncIdntfr1><TrfRmtncIdntfr2>SERIAL0001</TrfRmtncIdntfr2><Cdtr><PstlAdr><StrtNm2>ONE TIME BENE ADDRESS 2</StrtNm2></PstlAdr><NonResFlg>0</NonResFlg><Contact><Nm>ZHANG SAN</Nm><Dpt>FINANCIAL</Dpt><Phn>PHONE NO.</Phn><Fax>FAX NO.</Fax><Email>E@MAIL.COM</Email></Contact></Cdtr><CdtrAgt><FinInstnId><PstlAdr><StrtNm2>ONE TIME BENE BANK ADDRESS 2</StrtNm2></PstlAdr></FinInstnId></CdtrAgt><XchgRateInf><CcyBuyFlg></CcyBuyFlg><CcyBuyDt></CcyBuyDt></XchgRateInf><CdtTrfBdgtCd>-GOOD</CdtTrfBdgtCd><CdtrRmndr><RmndrFlg>1</RmndrFlg></CdtrRmndr><CdtrAgtRmndr><RmndrFlg>1</RmndrFlg><RmndrMode>01</RmndrMode><RmndrAdr>00862188889999</RmndrAdr></CdtrAgtRmndr><ExclsvPmt>1</ExclsvPmt><ExctnExpdts>1</ExctnExpdts><ExctnRtgs>0</ExctnRtgs><FreeText1>FREE TEXT 1</FreeText1><FreeText2>FREE TEXT 2</FreeText2><FreeText3>FREE TEXT 3</FreeText3><Invoice>INVOICE LINE NO DETAIL</Invoice><RgltryRptg><Dtls><Inf2>DETAILED INFORMATION 2</Inf2></Dtls></RgltryRptg><ErpAprvr>SALLY</ErpAprvr><PmtRsn2></PmtRsn2><PmtRsn3></PmtRsn3><PmtRsn4></PmtRsn4></CustomAttributes><RmtInf><Ustrd>Reason, Reason, Reason, Reason, Reason, Reason, Reason, Reason, Reason, Reason, Reason, Reason, </Ustrd><Strd><RfrdDocInf><Tp><CdOrPrtry><Cd></Cd></CdOrPrtry></Tp><Nb></Nb><RltdDt></RltdDt></RfrdDocInf><RfrdDocAmt><DuePyblAmt></DuePyblAmt><RmtdAmt></RmtdAmt></RfrdDocAmt><CdtrRefInf><Ref></Ref></CdtrRefInf></Strd></RmtInf></CdtTrfTxInf></PmtInf></CstmrCdtTrfInitn></Document>';
            $xml_object = simplexml_load_string($xml);
            $xml_json = json_encode($xml_object );
            $xml_array =json_decode($xml_json,true);
            $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Document></Document>');

            arrayToXml($xml_array,$xml_data);
            $result = $xml_data->asXML('c:/log/b.xml');
            v($result);
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function imap_test()
    {
        try {
            $res = (new KyribaService())->mailReceive();
        }  catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        v($res);
    }

    public function imap_test2()
    {
        try {
            $date = I('date');
            $res = (new KyribaService())->mailReceiveTest($date);
        }  catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        v($res);
    }

    public function imap_excel_test()
    {
        try {
            $excel_patch = 'c:/img/test.xls';
            $server = new KyribaService();
            $currency = (new TbMsCmnCdModel())->currency();
            $server->currency_map = array_column($currency, 'CD', 'CD_VAL');
            $res = $server->readExcel($excel_patch);
        }  catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        v($res);
    }

    public function sftp_download_test()
    {
        try {
            v(SftpModel::client()->downloadFile());
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function pgp_encrypt_test()
    {
        try {
            $name = I('name');
            if (!empty($name)) {
                $content = file_get_contents(ATTACHMENT_DIR_DOC. $name);
            } else {
                $content = file_get_contents(ATTACHMENT_DIR_DOC. 'BR_SYS_FILE_20171204100921_9768.asc');
            }
            if (empty($content)) {
                $content = 'pgp encrypt test';
            }
            $gpg = new Gpg();
            if ('sms2.b5cai.com'== $_SERVER ['HTTP_HOST'] || 'erp.gshopper.com'== $_SERVER ['HTTP_HOST']) {
                $gpg->public_key = file_get_contents(APP_SITE. '/resources/key/erp-online-pgp-public-key.txt');
            } else {
                $gpg->public_key = file_get_contents(APP_SITE. '/resources/key/erp-stage-pgp-public-key.txt');
            }
            $encrypt_text = $gpg->encrypt($content);
            if ($encrypt_text === false) {
                v('encrypt failed');
            }
            v($encrypt_text);
//            file_put_contents('/opt/b5c-disk/doc/BR_SYS_FILE_20171204100921_9768.asc', $encrypt_text);
//            v(SftpModel::client()->putOther('BR_SYS_FILE_20171204100921_9768.asc'));

        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function pgp_decrypt_test()
    {
        try {
            $name = I('name');
            if (!empty($name)) {
                $content = file_get_contents(ATTACHMENT_DIR_DOC. $name);
            } else {
                $content = file_get_contents(ATTACHMENT_DIR_DOC. 'BR_SYS_FILE_20171204100921_9768.asc');
            }
            if (empty($content)) {
                $content = 'pgp decrypt test';
            }
            $decrypt_content = (new Gpg())->decrypt($content);
//            file_put_contents(ATTACHMENT_DIR_DOC. time(). '.csv', $decrypt_content);
            v($decrypt_content);
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function pgp_decrypt_content()
    {
        try {
            $content = I('content');
            $decrypt_content = (new Gpg())->decrypt($content);
            v($decrypt_content);
        } catch(Exception $exception) {
            v($exception->getMessage());
        }
    }

    public function kyriba_sftp_receive()
    {
        $res = (new KyribaService())->sftpReceive();
        v($res);
    }

    public function amqp_test()
    {
        $data = [['name'=>123],['name'=>666]];
        $res = (new KyribaService())->publisherReceiveContent($data);
        v($res);
    }

    public function kyriba_receive_consume()
    {
        $res = (new KyribaService())->consumeReceiveContent();
        v($res);
    }

    public function kyriba_mail_test()
    {
        //一般付款
        $field = "pa.*, gp.payment_type";
        $general_info = (new Model())->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->where(['pa.id' => 13049])
            ->find();
        $send_res = (new GeneralPaymentService())->sendPaidEmail($general_info, A('OrderDetail')->paid_email_content(APP_PATH. 'Tpl/Home/Finance/paid_general_email.html'));
        v($send_res);
    }

    public function getSession()
    {
        print_r(json_encode($_SESSION));
    }


    public function monolog_test ()
    {
//        ELog::add([
//            'username' => 'admin',
//            'email' => 'admin@example.com',
//            'name' => 'Admin User',
//        ]);
//        $insertOneResult = MongoDbModel::client()->insertOne('tb_ms_logistics_thr_api_log', [
//            'username' => 'admin',
//            'email' => 'admin@example.com',
//            'name' => 'Admin User',
//        ]);
//        $logArr['mapWhere'] = 1;
//        $logArr['supplierInfo'] = 2;
//        $logArr['lastsql'] = 3;
//        Logs($logArr, __FUNCTION__.'----temp', 'tr');


        /*$id = 'test';
        $msg = 'testtest';
        $insertOneResult = MongoDbModel::client()->insertOne('tb_wms_inve_logs', [
                    'inve_id' => $id,
                    'msg' => $msg,
                    'time' => date('Y-m-d H:i:s', time()),
                    'user' => DataModel::userNamePinyin()
         ]);
        p($insertOneResult);
        $res = MongoDbModel::client()->find('tb_wms_inve_logs',['inve_id'=> $id]);
        p($res);
        v( MongoDbModel::client()->find('tb_wms_inve_logs',['inve_id'=> $id]));*/
    }

    //语言刷新
    public function flushCache()
    {
        $type = $_REQUEST['type'];
        //刷新语言
        $language_key = 'LANGUAGE_en-us';
        if (1 == $type) {
            $language = new LanguageModel();
            $translation = $language->where(['type' => 'N000920200'])->getField('element,translation_content', true);
            $res = RedisModel::set_key($language_key, json_encode($translation, JSON_UNESCAPED_UNICODE));
            echo '重置英文翻译配置:' . $language_key;
        }
        $translation = RedisModel::get_key($language_key);
        echo ($translation);
    }

    public function pur()
    {

        //企业微信审批
        $send_email    = ['xuejun.zou@gshopper.com'];
        $wx_return_res = (new PurPaymentService())->purPaymentWechatApproval( $_GET['payment_audit_id'], $send_email);
        v($wx_return_res);
    }

    //一般付款申请通知
    public function pur_test()
    {
        $fileModel        = new FileDownloadModel();
        $file_attach = $info['status'] == TbPurPaymentAuditModel::$status_no_billing ? $info['payment_voucher'] : $info['billing_voucher'];
        $file_attach = json_decode($file_attach, true);
        foreach ($file_attach as $v) {
            if($v) {
                $fileModel->fname    = $v['save_name'];
                $file = $fileModel->getFilePath();
                if (is_file($file)) {
                    $attachment[] = $file;
                }
            }
        }
        //企业微信审批
        $send_email    = ['xuejun.zou@gshopper.com'];
        $wx_return_res = (new FinanceService())->bulidGeneralWechatApproval(13882, $send_email);
        echo "调用成功了吗";
        v($wx_return_res);
    }
    public function getSessionRedisByUid()
    {
        $uid = $_REQUEST['uid'];
        $session = RedisModel::client()->hgetall('uid_session_id_' . $uid);
        $refresh = RedisModel::client()->hgetall('refresh_role_session_id_' . $uid);
        echo json_encode(['session'=>$session,'refersh'=>$refresh]);
        exit;
    }

    public function makeDownTest()
    {
        $allo_id = 3518;
        $AllocationExtendNewService = new AllocationExtendNewService();
        $last_data_allo = M('wms_allo', 'tb_')->where(['id' => $allo_id])->find();
        $AllocationExtendNewService->sendWxMsg($last_data_allo, '正常入库');
    }
}