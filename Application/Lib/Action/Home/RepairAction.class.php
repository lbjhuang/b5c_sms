<?php

/**
 * User: yangsu
 * Date: 18/9/11
 * Time: 13:33
 */

@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

class RepairAction extends BaseAction
{

    /**
     * @var null
     */
    public $model = null;
    /**
     * @var string
     */
    public $virtual_warehouse = 'N000680800';

    public $old_data;

    public $new_data;

    /**
     * RepairAction constructor.
     */
    public function __construct()
    {
        LogsModel::$project_name = 'Repair';
        parent::__construct();
        $this->model = new \Model();
    }

    public function fixWarehouseCodeInfo()
    {
        // #10195 仓库新增&编辑优化，上线前该仓库CD已经创建，且在使用当中，需要手动维护补充下仓库信息20201014
        $insertSql = "INSERT INTO `tb_wms_warehouse` (
`warehouse`,
`company_id`,
`attribute_id`,
`location`,
`location_switch`,
`valuation`,
`remarks`,
`contacts`,
`in_contacts`,
`out_contacts`,
`address`,
`place`,
`city`,
`phone`,
`is_show`,
`CD`,
`system_docking`,
`auto_dispatch`,
`auto_dispatch_delay`,
`manage`,
`areas`,
`sender`,
`sender_phone_number`,
`sender_system`,
`auto_group`,
`sender_zip_code`,
`job_content`,
`contract_no`,
`contract_start`,
`contract_end`,
`cost_currency`,
`cost_per_day`,
`operator_cds`,
`type_cd`,
`created_at`,
`created_by`,
`updated_at`,
`updated_by`,
`account_id`,
`is_bonded`,
`default_addr`,
`address_en` 
)
VALUES
    (
    '美西联宇仓',
    NULL,
    NULL,
    NULL,
    0,
    NULL,
    NULL,
    'Bill.Mi',
    'Bill.Mi',
    'Bill.Mi',
    '9570 Santa Anita  Ave #B , Rancho Cucamonga, CA 91730    tel:9094818999',
    '(USA)美国-加利福尼亚-洛杉矶',
    '2356,2466,2468',
    '',
    1,
    'N000689480',
    'N002040200',
    0,
    NULL,
    NULL,
    NULL,
    '',
    '',
    NULL,
    0,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'N000590100',
    NULL,
    '',
    'N002590100',
    '2020-10-14 10:35:59',
    'Weslie.Li',
    '2020-10-14 11:19:33',
    'Weslie.Li',
    NULL,
    0,
    1,
    '9570 Santa Anita  Ave #B , Rancho Cucamonga, CA 91730    tel:9094818999' 
    );"; 
        $res = M()->query($insertSql);
        p($res);
    }

    public function fixPurAttment()
    {
        $model = M('pur_order_detail','tb_');

        $save['attachment'] = '[{"original_name":"20200930-Byredo香水 BR_SYS_FILE_20200930165338_8757-sy_已签章.pdf","save_name":"BR_SYS_FILE_20200930181728_2959.pdf"}]';
        
        //$res = $model->where(['procurement_number' => 'RN202001150001-001'])->save($save);

        $res = $model->where(['procurement_number' => 'RN202009300006-001'])->save($save);
        p($res);
    }

    public function fixWarehouseSmallTeamData()
    {
        // 部分历史数据中，SCM创建时没有填写小团队但是字段有值，导致商品入库时按照有小团队的入库形式入库，需要处理这部分历史数据
        $sql = "SELECT
    sg.id,
    pod.procurement_number,
    sg.sell_small_team_json,
    gi.`sell_small_team_json`,
    gi.relevance_id 
FROM
    `tb_pur_goods_information` gi
    LEFT JOIN tb_pur_ship_goods sg ON sg.information_id = gi.information_id
    LEFT JOIN tb_pur_relevance_order pro ON pro.relevance_id = gi.relevance_id
    LEFT JOIN tb_pur_order_detail pod ON pod.order_id = pro.order_id
    LEFT JOIN tb_pur_ship ps ON ps.id = sg.ship_id 
WHERE
    (gi.`sell_small_team_json` = '' 
    OR gi.`sell_small_team_json` IS NULL )
    AND sg.`sell_small_team_json` != '' ";
        try {
            $Model = M();
            $res = $Model->query($sql);
            $Model->startTrans();
            $shipGoodsModel = M('ship_goods', 'tb_pur_');
            foreach ($res as $key => $value) {
                if ($value['id']) {
                    $saveData['sell_small_team_json'] = ''; $re = false;
                    $re = $shipGoodsModel->where(['id' => $value['id']])->save($saveData);
                    if (false === $re) {
                        p($value['id']);
                        $Model->rollback();
                    }
                }
            }
            $Model->commit();
        }  catch (Exception $exception) {
            var_dump($exception);
            $Model->rollback();
        }
        echo "SUCCESS";
    }

    // 获取小团队和店铺之间的关系
    public function getSmallTeamStoreRelation()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
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
        $data = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $store_id = ''; $small_sale_team = '';
            $store_id = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $small_sale_team = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            if ($store_id && $small_sale_team) {
                $data[$store_id] = $small_sale_team;
            }
        }
        return $data;
    }
    #9946 库存归属转为小团队-仓储模块调整
    public function changeSmallSellTeamFromAscriptionStore()
    {
        try {
            $res = $this->getSmallTeamStoreRelation();
            //1.tb_wms_group_bill中ascription_store有对应关系的，small_sale_team_code改为对应的CD值
            // 获取店铺有值的列表
            // 循环判断该店铺是否有对应的CD，保存CD
            $Model = M();
            $Model->startTrans();
            $group_map = $bill_map = $allo_map = [];
            $group_sku_m = M('wms_group_bill', 'tb_');
            $bill_m = M('wms_bill', 'tb_');
            $batch_m = M('wms_batch', 'tb_');
            $allo_m = M('wms_allo_attribution', 'tb_');
            $group_map['ascription_store'] = array('EXP', 'IS NOT NULL');
            $bill_res = $group_sku_m->field('ascription_store,id')->where($group_map)->select();
            foreach ($bill_res as $key => $v) {
                if ($res[$v['ascription_store']] && $v['id']) {
                    $save = $where = [];
                    $where['id'] = $v['id'];
                    $save['small_sale_team_code'] = $res[$v['ascription_store']];
                    $group_res = $group_sku_m->where($where)->save($save);
                    if ($group_res === false) {
                        p("该组合数据更新小团队数据失败，id为{$v['id']},小团队CD值为{$res[$v['ascription_store']]}");
                        $Model->rollback();
                    }
                }
            }
            //2.tb_wms_bill中ascription_store找到对应CD值的，bill.id = tb_wms_batch.bill_id，保存small_sale_team_code值\
            $bill_map['ascription_store'] = array('EXP', 'IS NOT NULL');
            $bill_re = $bill_m->field('batch,ascription_store')->where($bill_map)->select();
            foreach ($bill_re as $key => $value) {
                if ($res[$value['ascription_store']] && $value['batch']) {
                   $save = $where = [];
                   $where['id'] = $value['batch'];
                   $save['small_sale_team_code'] = $res[$value['ascription_store']];
                   $batch_res = $batch_m->where($where)->save($save);
                   if ($batch_res === false) {
                       p("该流水数据更新小团队数据失败，batch_id为{$value['batch']},小团队CD值为{$res[$value['ascription_store']]}");
                       $Model->rollback();
                   } 
                }
            }
            // 获取有store的列表
            // 循环处理
            //3.tb_wms_allo_attribution,change_type_cd为N002990001，且前后值可以找到对应的销售小团队的CD值，包括一方为空的
            $allo_map['change_type_cd'] = 'N002990001';
            $allo_res = $allo_m->field('id,old,new')->where($allo_map)->select();
            // 查change_type_cd为N002990001的列表结果
            // 如果有不为空的，则判断是否有对应的CD，有的话修改数据
            foreach ($allo_res as $key => $vv) {
                $where = $save = [];
                if ($vv['old'] && $vv['new'] && $res[$vv['old']] && $res[$vv['new']]) {
                    $save['old'] = $res[$vv['old']];
                    $save['new'] = $res[$vv['new']];
                } else {
                    if ($vv['old'] && $res[$vv['old']] && !$vv['new']) {
                        $save['old'] = $res[$vv['old']];
                    }
                    if ($vv['new'] && $res[$vv['new']] && !$vv['old']) {
                        $save['new'] = $res[$vv['new']];
                    }
                }
                $where['id'] = $vv['id'];
                if ($save) {
                    $save['change_type_cd'] = 'N002990005';
                    $allo_re = $allo_m->where($where)->save($save);
                    if ($allo_re === false) {
                        p("该变更数据更新小团队数据失败，id为{$vv['id']}，old为{$vv['old']}，new为{$vv['new']}");
                        $Model->rollback();
                    } 
                }
            }
            $Model->commit();
            echo "SUCCESS!";
        }  catch (Exception $exception) {
            var_dump($exception);
            $Model->rollback();
        }
    }
    public function fixReturnGoodsData()
    {
        // 获取需要生成抵扣金的退货出库的数据
        $sql = "SELECT
    t.return_number,
    gi.unit_price,
    gi.unit_expense,
    t.return_id,
    ro.relevance_id,
    pr.return_no
FROM
    tb_pur_return_goods t
    LEFT JOIN tb_pur_return_order ro ON ro.id = t.return_order_id
    LEFT JOIN tb_pur_goods_information gi ON gi.information_id = t.information_id
    LEFT JOIN tb_pur_return pr ON pr.id = t.return_id 
    left join tb_pur_relevance_order pro on pro.relevance_id = ro.relevance_id
WHERE
    t.vir_type_cd = 'N002440100' 
    AND pr.outbound_status = '1'
    AND t.created_at > '2019-08-25 00:00:00'
    AND pro.prepared_time > '2019-08-12 00:00:00'";
        $res = M()->query($sql);  
        foreach ($res as $key => $value) {
            $data[$value['return_no']][$value['relevance_id']]['amount'] = bcadd($data[$value['return_no']][$value['relevance_id']]['amount'], bcmul(bcadd($value['unit_price'], $value['unit_expense'], 8), $value['return_number'], 8), 2);
        }

        try {
            $Model = M();
            $Model->startTrans();
            $logic     = D('Purchase/Payment','Logic');
            $purOper_m = D('Scm/PurOperation');
            foreach ($data as $k => $v) {
                  foreach ($v as $kk => $vv) {
                      if ($vv['amount'] == '0.00' || $vv['amount'] == '0') {
                          continue;
                      }
                      // 生成抵扣金记录
                      $addData = [];
                      $addData['remark_deduction'] = '历史异常抵扣金处理';
                      $addData['amount_deduction'] = $vv['amount'];
                      $addData['relevance_id'] = $kk;
                      $res_dedu        = $logic->regardAsDeduction($addData);
                      if(!$res_dedu) {
                            ELog::add('历史数据生成抵扣金数据失败：'.json_encode($addData).M()->getDbError(),ELog::ERR);
                            $Model->rollback();
                            return false;
                      }
                      // 触发操作记录生成
                      $operationAddInfo = '';
                      $operationAddInfo['main_id'] = $res_dedu;
                      $operationAddInfo['clause_type'] = '5';
                      $operationAddInfo['money_type'] = '2';
                      $operationAddInfo['action_type_cd'] = 'N002870016';
                      $operationAddInfo['bill_no'] = $k;
                      $operationAddInfo['created_by'] = 'system';
                      $re = $purOper_m->add($operationAddInfo);
                      if ($re === false) {
                            ELog::add('历史数据生成抵扣金数据失败：'.json_encode($addData).M()->getDbError(),ELog::ERR);
                            $Model->rollback();
                            return false;
                      }
                      p($res_dedu);
                  }
              }
              $Model->commit();
              echo "SUCCESS";  
        } catch (Exception $exception) {
            var_dump($exception);
            $Model->rollback();
        } 



	}
    // 填充地区英文
    public function fillingEnglishArea()
    {
        try {
            set_time_limit(0);
            // 获取xml文件
            $file_name = APP_PATH . 'Tpl/Home/Warehouse/loclist.xml';
            $str = file_get_contents($file_name);
            $strObj = simplexml_load_string($str);
            //var_dump($strObj);die;
            $json = json_encode($strObj);
            $ary = json_decode($json, true);
            $siteModel = M('crm_site','tb_');
            $res_region = $siteModel->where(['PARENT_ID' => '0'])->getField('CODE,ID');
            $Model = M();
            $Model->startTrans();
            foreach ($ary as $key => $value) {
                foreach ($value as $ck => $cv) {
                    if (!empty($cv['@attributes']['Code'])) {
                        $res = false;
                        // 国家 英文补充
                        $country_id = '';
                        $country_id = $res_region[$cv['@attributes']['Code']];
                        
                        $res = $siteModel->where(['ID' => $country_id])->save(['NAME_EN' => $cv['@attributes']['Name']]);
                        if ($res === false) {
                            p('第一级国家英文更新失败');
                            $Model->rollback();
                        }

                    }
                    $res = false;
                    if (empty($cv['State']['City'])) { // 表明有三级
                        foreach ($cv['State'] as $sck => $scv) {
                            // 二级补充
                            $res = false;
                            $res = $siteModel->where(['LEVEL' => '2', 'PARENT_ID' => $country_id, 'CODE' => $scv['@attributes']['Code']])->save(['NAME_EN' => $scv['@attributes']['Name']]); 
                            $province_id = '';
                            $province_id = $siteModel->where(['LEVEL' => '2', 'PARENT_ID' => $country_id, 'CODE' => $scv['@attributes']['Code']])->getField('ID'); 
                            if ($res === false) {
                                p("第二级省份英文更新失败{$cv['State']['@attributes']['Name']}-{$cv['State']['@attributes']['Code']}");
                                $Model->rollback();
                            }
                            if (empty($province_id)) {
                                p('第二级省份ID获取失败');
                                $Model->rollback();
                            }

                            foreach ($scv['City'] as $scvk => $scvv) {
                                // 三级补充
                                $res = false;
                                if ($scvv['Name'] && $scvv['Code']) {
                                    $res = $siteModel->where(['LEVEL' => '3', 'PARENT_ID' => $province_id, 'CODE' => $scvv['Code']])->save(['NAME_EN' => $scvv['Name']]); 
                                    if ($res === false) {
                                        p("第三级区域英文更新失败{$scvv['Code']}-{$scvv['Name']}");
                                        $Model->rollback();
                                    }
                                } else {
                                    $res = $siteModel->where(['LEVEL' => '3', 'PARENT_ID' => $province_id, 'CODE' => $scvv['@attributes']['Code']])->save(['NAME_EN' => $scvv['@attributes']['Name']]); 
                                    if ($res === false) {
                                        p("第三级区域英文更新失败{$scvv['@attributes']['Code']}-{$scvv['@attributes']['Name']}");
                                        $Model->rollback();
                                    }
                                }
                            }
                        }
                    } else {
                       foreach ($cv['State']['City'] as $cck => $ccv) {
                           // 二级补充
                            $res = false;
                            $res = $siteModel->where(['LEVEL' => '2','PARENT_ID' => $country_id, 'CODE' => $ccv['@attributes']['Code']])->save(['NAME_EN' => $ccv['@attributes']['Name']]);
                            if ($res === false) {
                               p("第二级省份英文更新失败{$ccv['@attributes']['Code']}-{$ccv['@attributes']['Name']}");
                               $Model->rollback();
                            }
                       } 
                    }
                    
                    
                }
            }
            $Model->commit();
            echo "SUCCESS!";
        }  catch (Exception $exception) {
            var_dump($exception);
            $Model->rollback();
        }
    }

    public function fixDeductionRepeatData()
    {
        try {
            $Model = M();

            $sql = "SELECT
            t.main_id, 
            pp.status,
            pp.relevance_id,
            pp.amount_payable,
            pp.create_time,
            pp.payment_no
        FROM
            `tb_pur_operation` t
        LEFT JOIN
            tb_pur_payment pp ON pp.id = t.main_id
        WHERE
            t.`action_type_cd` = 'N002870003' 
            AND t.`clause_type` = '8' 
            AND t.`money_type` = '1'
            AND (t.`bill_no` = '' OR t.`bill_no` IS NULL)";
            /*1.预计付款时间payable_date也要根据tb_pur_payment进行调整create_time 只要是1970-01-01才需要调整
            2.tb_pur_operation中的bill_no需要补充
                amount_payable
                create_time
                relevance_id
                status 为0
                =》找到tb_pur_payment.id，即tb_pur_operation.main_id B记录->获取bill_no
                补充到现在的不是0的main_id中（A记录）
                删除是0的main_id的记录（B记录）
                删除B记录对s应的payment的记录
                确认是否付款总状态需要调整*/
            $data = M()->query($sql);

            $pay_model = M('payment', 'tb_pur_');
            $operation_model = M('operation', 'tb_pur_');
            foreach ($data as $key => $value) {
                if (!$value['payment_no']) {
                    continue;
                }
                $id = ''; $bill_no = '';
                if ($value['status'] === '0') {
                    // 删除应付记录
                    // 确认是否付款总状态需要调整
                    $id = $value['main_id'];
                } elseif ($value['status'] === '1') {

                    $id = $Model->table('tb_pur_payment')->where(['create_time' => $value['create_time'], 'amount_payable' => $value['amount_payable'], 'relevance_id' => $value['relevance_id'], 'status' => 0])->getField('id');

                    $bill_no = $Model->table('tb_pur_operation')->where(['main_id' => $id, 'money_type' => '1'])->getField('bill_no');
                    if (!$bill_no || !$id) {
                        throw new \Exception("无法获取该id{$id}或bill_no{$bill_no},main_id为{$value['payment_no']}");    
                    }
                    $Model->table('tb_pur_operation')->where(['main_id' => $value['main_id']])->save(['bill_no' => $bill_no]);
                } else {
                    throw new \Exception('该状态有异常');
                }


                // 应付记录删除
                $del_res = $Model->table('tb_pur_payment')->where(['id' => $id])->save(['status' => '5', 'deleted_by' => 'system', 'deleted_at' => date('Y-m-d H:i:s', time())]);
                if (false === $del_res) {
                    echo M()->_sql();
                    $Model->rollback();
                }
                // 更新订单总表付款状态 tb_pur_relevance_order payment_status
                $change_res = A('Home/OrderDetail')->changePayStatus($value['relevance_id'], $pay_model);

                // 触发操作记录删除
                $opr_res = $operation_model->where(['main_id' => $id, 'money_type' => '1'])->delete(); 
                if (false === $opr_res) {
                    echo M()->_sql();
                    throw new \Exception('触发操作记录删除失败');
                }
            }

            // 将所有该条款下的"1970-01-01"的值，处理为创建时间
            $sql = "SELECT
            t.main_id, 
            pp.status,
            pp.relevance_id,
            pp.amount_payable,
            pp.create_time,
            pp.payment_no,
            pp.payable_date,
            pp.payment_audit_id,
            t.clause_type
        FROM
            `tb_pur_operation` t
        LEFT JOIN
            tb_pur_payment pp ON pp.id = t.main_id
        WHERE
            t.`action_type_cd` = 'N002870003' 
            AND t.`money_type` = '1'
                        AND pp.payable_date = '1970-01-01'
                        AND pp.`status` != '5'";
            $res_data = $Model->query($sql);
            foreach ($res_data as $key => $value) {
                // 将日期改为创建的日期
                // 将 tb_pur_payment 改为 创建时间
                $where = []; $save = []; $res = false; $where_map = []; $audit_res = [];$audit_save_res = false; $save_time = '';
                $where['id'] = $value['main_id'];
                $save_time = date('Y-m-d',strtotime($value['create_time']));
                $save['payable_date'] = $save_time;

                $res = $Model->table('tb_pur_payment')->where($where)->save($save);
                if ($res === false) {
                    p("{$value['main_id']}更新日期失败");
                    $Model->rollback();
                }
                // 将 payment_audit_id 的 payable_amount_after 如果是1970-01-01 改为 创建时间
                if (!empty($value['payment_audit_id'])) {
                    
                    $where_map['id'] = $value['payment_audit_id']; 
                    $audit_res = $Model->table('tb_pur_payment_audit')->field('payable_date_after,payable_date_before')->where($where_map)->find();
                    if ($audit_res['payable_date_after'] === '1970-01-01') {
                        $save = [];
                        $save['payable_date_after'] = $save_time;
                        $audit_save_res = $Model->table('tb_pur_payment_audit')->where($where_map)->save($save);
                        if ($audit_save_res === false) {
                            p("{$value['main_id']} - {$value['payment_audit_id']}更新日期失败");
                            $Model->rollback();
                        }
                    }
                    if ($audit_res['payable_date_before'] === '1970-01-01') {
                        $save = [];
                        $save['payable_date_before'] = $save_time;
                        $audit_save_res = $Model->table('tb_pur_payment_audit')->where($where_map)->save($save);
                        if ($audit_save_res === false) {
                            p("{$value['main_id']} - {$value['payment_audit_id']}更新日期payable_amount_before失败");
                            $Model->rollback();
                        }
                    }
                }
                // 将 payment_audit_id 的 payable_amount_before 如果是1970-01-01 改为 创建时间
            }
            

            $Model->commit();
            echo "success";
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
            p($res);
            $Model->rollback();
        }

    }

    // 10382 运费模板配置批量处理 
    public function batchDealLogisticsMode()
    {
        //1.通过tb_ms_logistics_mode.LOGISTICS_MODE 查询所有的ID
        //3.根据ID(即tb_lgt_postage_model.LOGISTICS_MODEL_ID)，获取 tb_lgt_postage_model.OUT_AREAS
        //4.判断是否存在“深圳金山仓”的code
        //5.如果没有，则补充该code，并保存
        set_time_limit(0);
        $logistics_model = M('logistics_mode', 'tb_ms_'); // 物流方式
        $log_postage_model = M('postage_model', 'tb_lgt_'); // 运费模板
        $where = [];
        $nameParams = [
            'ChinaPost上海仓平邮',                        
            'EPC-普货',
            'Wish邮智选标准 - 普货 (EPC)',                        
            'Wish邮智选经济 - 普货（EPC）',                      
            '安速派标准-普货（EPC/A ）',                   
            '安速派经济-普货（EPC/A ）',                      
            '燕文C挂号小包',                        
            '燕邮宝平邮-特货',      
            '燕文航空经济小包-普货',
            '华南小包平邮'
        ];
        $condNameArr = array();$whereIdx = 0;
        foreach ($nameParams as $idx => $nParam) {
            $kParam = '%' . $nParam . '%';
            array_push($condNameArr, array('LOGISTICS_MODE' => array('like', '%' . $nParam  . '%')));
        }
        $condNameArr['_logic'] = 'or';
        $where["_complex"][$whereIdx++] = $condNameArr;
        $res = $logistics_model->field('ID')->where($where)->select();
        $v_res = [];
        $v_res = array_column($res, 'ID');
        $where_v_map = [];
        $where_v_map['LOGISTICS_MODEL_ID'] = array('in', $v_res);
        $out_areas_cd = $log_postage_model->field('ID,OUT_AREAS')->where($where_v_map)->select();
        foreach ($out_areas_cd as $ak => $vk) {
            if (!strstr($vk['OUT_AREAS'], 'N000689494')) {
                $save_data = []; $where_map = []; $save_res = false;
                $save_data['OUT_AREAS'] = $vk['OUT_AREAS'] . ',N000689494';
                $where_map['ID'] = $vk['ID'];
                if (!$vk['ID']) {
                    continue;
                }
                if (!$vk['OUT_AREAS']) {
                    $save_data['OUT_AREAS'] = 'N000689494';
                }
                $save_res = $log_postage_model->where($where_map)->save($save_data);
                if (false === $save_res) {
                    p($where_map);
                    p($save_data);
                }
            }
        }
        echo 'success';
    }

    public function updateStoreData()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
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
        $data = $orders = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $store_id = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $IS_VAT = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $store_by = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $STORE_STATUS = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());

            if (!$store_id) {
                continue;
            }
            $map = [];
            $save_data = [];
            $map['ID'] = $store_id;
            // 找到excel数据是空的，根据原来的值，进行修复
            $res = false;
            if ($STORE_STATUS !== '运营中' && $STORE_STATUS !== '未运营') {
                $store_info = '';
                $store_info = $this->model->table('tb_ms_store_back_202020430')->where($map)->find();
                $save_data['STORE_STATUS'] = $store_info['STORE_STATUS'];
                
                $res = $this->model->table('tb_ms_store')->where($map)->save($save_data);
                if (false === $res) {
                    p('更新失败' . $store_id);
                }
                if ($store_info['STORE_STATUS'] == 0) {
                    $update_store_id_arr[] = $store_id;
                }
            }
        }
        p($update_store_id_arr);
        echo "success";
        Logs(['update_store_arr' => $update_store_id_arr], __FUNCTION__.'----updateSQL', 'tr');
    }

    public function fixHistEmpl()
    {
        $emplModel = M('hr_empl','tb_'); $hrJobModel = M('hr_jobs', 'tb_');
        // 筛选job_id为0的数据
        $map['JOB_ID'] = 0;
        $list = $emplModel->field('ID, JOB_CD')->where($map)->select();
        // 补充job_id的数据
        $job_info = $hrJobModel->getField('ID,CD_VAL');
        $job_info = array_flip($job_info);
        foreach ($list as $key => $value) {
            $res = false; $where_map = []; $save_data = [];
            $where_map['ID'] = $value['ID'];
            if (!$job_info[$value['JOB_CD']]) {
                continue;
            }
            $save_data['JOB_ID'] = $job_info[$value['JOB_CD']];
            $res = $emplModel->where($where_map)->save($save_data);
            if (false === $res) {
                p($value['ID']);
            }
        }
        echo 'success';
    }
        

    // 修复个别历史数据没有付款来源的问题
    public function fixPaymentAuditDefaultSource()
    {
        $payAuditModel      = M('pur_payment_audit', 'tb_');
        $save['source_cd']  = 'N003010001';
        $save['updated_by'] = 'system';
        $res                = $payAuditModel->where('source_cd IS NULL')->save($save);
        if (false === $res) {
            echo M()->_sql();
        }
        echo 'success';
    }

    public function getRealWarehouseData($list)
    {
        $real_list = [];
        $new_list  = [];
        foreach ($list as $key => $value) {
            if (!in_array($value['PLAT_CD'] . $value['ORDER_ID'], $real_list)) {
                $real_list[] = $value['PLAT_CD'] . $value['ORDER_ID'];
                //$value['ORD_HIST_HIST_CONT'] = trim($value['ORD_HIST_HIST_CONT'], " \"");
                $new_list[] = $value;
            } else {
                p($value['ORDER_ID']);
                //p($value['ORD_HIST_HIST_CONT']);
            }
        }
        return $new_list;
    }

    public function fixWarehouseData()
    {
        // 获取仓库所有名称
        $warehouse_arr = CodeModel::getCodeKeyValArr(['N00068'], '');
        // 获取需要修复的订单列表
        $list = $this->getWarehouseHistData();
        // 移除掉部分订单重复记录
        $list = $this->getRealWarehouseData($list);

        // 对匹配词做修剪，移除可能存在的双引号trim
        // 循环处理匹配，并修改仓库名称
        $op_order_model = M('order', 'tb_op_');
        $string_log     = [];
        foreach ($warehouse_arr as $key => $value) {
            foreach ($list as $k => $v) {
                if (strpos($v['ORD_HIST_HIST_CONT'], "编辑为 " . $value) !== false || strpos($v['ORD_HIST_HIST_CONT'], "编辑为 \"" . $value) !== false) {
                    if ($v['WAREHOUSE'] !== $key) {
                        // 更新，根据tb_op_order.ORDER_ID，key来更新tb_op_order.WAREHOUSE
                        $save              = [];
                        $res               = '';
                        $last_sql          = '';
                        $save['WAREHOUSE'] = $key;
                        $where['ID']       = $v['ID'];
                        $res               = $op_order_model->where($where)->save($save);
                        if (false === $res) {
                            p("更新订单仓库失败，订单ID为{$v['ID']}，原仓库为{$v['WAREHOUSE']}-{$v['WAREHOUSE_val']},更新保存仓库为{$key}-{$value}");
                        } else {
                            p("更新订单仓库成功，订单ID为{$v['ID']}，原仓库为{$v['WAREHOUSE']}-{$v['WAREHOUSE_val']},更新保存仓库为{$key}-{$value}");
                        }
                        // 补充下日志，以防万一有问题，可以重新更改数据
                        $last_sql     = M()->_sql();
                        $string_log[] = $last_sql;
                    }
                }
            }
        }
        Logs(json_encode($string_log), __FUNCTION__ . '----lastsql', 'tr');
    }

    public function getWarehouseHistData()
    {
        $sql = "SELECT
* 
FROM
(
SELECT
tb_op_order.ID,
tb_op_order.ORDER_ID,
tb_op_order.PLAT_CD,
tb_op_order.WAREHOUSE,
tb_op_order.ORDER_CREATE_TIME,
cd_1.CD_VAL AS WAREHOUSE_val,
tb_op_order.logistic_model_id,
sms_ms_ord_hist_0323.ORD_HIST_HIST_CONT,
sms_ms_ord_hist_0323.ORD_HIST_REG_DTTM 
FROM
( sms_ms_ord_hist_0323, tb_op_order )
LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_op_order.WAREHOUSE 
WHERE
tb_op_order.PLAT_CD IN ( 'N000831400', 'N000837401', 'N000837402', 'N000837403', 'N000837404', 'N000837405', 'N000837406', 'N000837407', 'N000837408', 'N000837409', 'N000837428', 'N000837430', 'N000834300', 'N000834200', 'N000834100' ) 
AND tb_op_order.BWC_ORDER_STATUS = 'N000550600' 
AND tb_op_order.ORDER_ID = sms_ms_ord_hist_0323.ORD_NO 
AND tb_op_order.PLAT_CD = sms_ms_ord_hist_0323.plat_cd 
AND sms_ms_ord_hist_0323.ORD_HIST_HIST_CONT != '物流下单成功' 
AND ( sms_ms_ord_hist_0323.ORD_HIST_HIST_CONT LIKE '%下发仓库%' ) 
) AS temp_1 
#WHERE
#temp_1.ORD_HIST_HIST_CONT NOT LIKE CONCAT( '%编辑为 ', temp_1.WAREHOUSE_val, '%' )
#AND
#temp_1.ORD_HIST_HIST_CONT NOT LIKE CONCAT( '%编辑为 \"', temp_1.WAREHOUSE_val, '%' )
ORDER BY temp_1.ORD_HIST_REG_DTTM DESC";
        return $list = M()->query($sql);
    }

    // 抵扣金记录数据变更
    public function fixSupplierData()
    {
        $Model               = new Model();
        $where['id']         = '52';
        $save['supplier_id'] = '1982';
        $res                 = $Model->table('tb_pur_deduction')->where($where)->save($save);
        p($res);
    }

    // #10144 变更部分触发操作的类型
    public function fixPurOperationData()
    {
        // 获取需要更改的操作记录
        $sql                 =
            "SELECT
        po.id 
        FROM
            `tb_pur_payment` pp
            LEFT JOIN `tb_pur_operation` po ON pp.id = po.main_id
            LEFT JOIN `tb_pur_clause` pc ON pc.purchase_id = pp.relevance_id 
        WHERE
            po.money_type = '1' 
            AND po.action_type_cd = 'N002870003' 
            AND po.clause_type = '8' 
            AND pp.STATUS = '0' 
            AND pc.clause_type = '1' 
            AND pc.percent = '100'";
        $operation_arr       = M()->query($sql);
        $pur_operation_model = M('operation', 'tb_pur_');
        // 循环更新
        foreach ($operation_arr as $key => $value) {
            $where_map['id'] = $value['id'];
            $re              = $pur_operation_model->where($where_map)->save(['clause_type' => '11']);
            if (false === $re) {
                p("更新失败，触发操作记录表ID为{$value['id']}");
            }
        }
        echo "success";
    }

    public function cutUseDeductionData($order)
    {
        $bool = false;
        if (empty($order)) return $bool; // 根据order_id获取相关信息

        //获取供应商抵扣金账户 我方公司-供应商-币种
        // 优先根据供应商id来获取抵扣金账户
        $where = [
            'our_company_cd'        => $order['our_company'],
            'deduction_currency_cd' => $order['amount_currency'],
        ];
        if (!empty($order['supplier_new_id'])) {
            $where['supplier_id'] = $order['supplier_new_id'];
        } else {
            $where['supplier_name_cn'] = $order['supplier_id'];
        }
        $deduction = '';
        $deduction = M('deduction', 'tb_pur_')->where($where)->find();
        if (!$deduction) {
            ELog::add(['info' => '扣减抵扣金失败：供应商抵扣金账户不存在', 'where_map' => json_encode($where), 'request' => json_encode($order)], ELog::ERR);
            return "RY-1";
        }

        if ($deduction['over_deduction_amount'] < $order['amount_payable']) {
            $log_arr['over_deduction_amount'] = $deduction['over_deduction_amount'];
            $log_arr['amount_payable']        = $order['amount_payable'];
            ELog::add(['info' => '扣减抵扣金失败：供应商账户余额小于当前抵扣金额', 'where_map' => json_encode($log_arr), 'request' => json_encode($order)], ELog::ERR);
            return "RY-2";
        }


        $param_final['relevance_id']      = $order['relevance_id'];
        $param_final['amount_deduction']  = $order['amount_payable'];
        $param_final['remark_deduction']  = '系统自动处理历史数据';
        $param_final['deduction_type_cd'] = 'N002660100'; // 多付未退款
        $param_final['voucher_deduction'] = json_encode([["name" => "", "savename" => ""]]);
        $param_final['supplier_new_id']   = $order['supplier_new_id'] ? $order['supplier_new_id'] : $deduction['supplier_id'];

        $deduction_detail_id = (new PurPaymentService())->useDeduction($param_final);
        if (!$deduction_detail_id) {
            ELog::add(['info' => '扣减抵扣金失败：生成抵扣金详情记录失败', 'param_final' => json_encode($param_final), 'request' => json_encode($order)], ELog::ERR);
            return $bool;
        }
        return $deduction_detail_id;
    }

    public function getNeedAdjustPurHistData()
    {
        $sql =
            "SELECT
            po.main_id,
            pp.create_time,
            pod.our_company,
            pod.amount_currency,
            pod.supplier_new_id,
            pod.supplier_id,
            pod.procurement_number,
            pp.payment_no,
            pp.relevance_id,
            pp.amount_payable,
            po.clause_type,
            pod.order_id 
        FROM
            `tb_pur_payment` pp
            LEFT JOIN `tb_pur_operation` po ON pp.id = po.main_id
            LEFT JOIN `tb_pur_relevance_order` pro ON pro.relevance_id = pp.relevance_id
            LEFT JOIN `tb_pur_order_detail` pod ON pod.order_id = pro.order_id 
        WHERE
            po.money_type = '1' 
            AND po.action_type_cd IN ( 'N002870003', 'N002870006', 'N002870014' ) 
            AND pp.STATUS = '0' 
            AND po.clause_type IN ( 3, 6, 9, 10, 11 ) 
            
        ORDER BY
            pp.create_time";
        return M()->query($sql);
    }

    public function handleAdjustPurHistData($res)
    {
        $res_real          = [];
        $purOperationModel = D('Scm/PurOperation');
        foreach ($res as $key => $value) {
            if ($value['clause_type'] == '10') {
                $no_fund_of_end = $purOperationModel->checkHasFundOfEnd($value['relevance_id']); //是否是无尾款
                if (!$no_fund_of_end) {
                    $res_real[] = $value;
                }
            } else {
                $res_real[] = $value;
            }
        }
        return $res_real;
    }

    // #10144 将部分应付改为扣减抵扣金
    public function fixPurHistPaymentData()
    {
        // 获取需要调整的应付记录（部分采购入库确认，预付款付款撤回，撤回入库完结）
        $res = $this->getNeedAdjustPurHistData();
        // 需要排查部分撤回入库完结type为10的应付单,去掉无尾款的订单
        $res                         = $this->handleAdjustPurHistData($res);
        $deduction_no_exist_account  = [];
        $deduction_no_enough_account = [];
        $Model                       = M();

        foreach ($res as $key => $value) {
            $dedu_account = '';
            $dedu_account = $value['our_company'] . '-' . $value['amount_currency'] . '-' . $value['supplier_id'];
            if (in_array($dedu_account, $deduction_no_exist_account)) {
                p("该应付单{$value['payment_no']}无法处理，抵扣金账户为{$dedu_account}，原因是RY-1，采购单号为{$value['procurement_number']}");
                continue;
            }
            if (in_array($dedu_account, $deduction_no_enough_account)) {
                p("该应付单{$value['payment_no']}无法处理，抵扣金账户为{$dedu_account}，原因是RY-2，采购单号为{$value['procurement_number']}");
                continue;
            }
            $res_dedc = '';
            $res_dedc = $this->cutUseDeductionData($value);
            // 根据抵扣金账户（标记状态）判断该应付金额是否可以扣减
            if ($res_dedc === 'RY-1') {
                p("头单-该应付单{$value['payment_no']}无法处理，抵扣金账户为{$dedu_account}，原因是RY-1，采购单号为{$value['procurement_number']}");
                $deduction_no_exist_account[] = $dedu_account;
                continue;
            }
            if ($res_dedc === 'RY-2') {
                p("头单-该应付单{$value['payment_no']}无法处理，抵扣金账户为{$dedu_account}，原因是RY-2，采购单号为{$value['procurement_number']}");
                $deduction_no_enough_account[] = $dedu_account;
                continue;
            }
            $Model->startTrans();
            if (!$res_dedc) {
                // 回滚
                p("该应付单{$value['payment_no']}无法处理,原因是RY-3，采购单号为{$value['procurement_number']}");
                $Model->rollback();
                break;
            }


            // 6) 更新触发操作日志，应付改为抵扣，根据应付id，应付id改为抵扣id updated_by system
            $res_update_oper = $this->updatePurOperationData($res_dedc, $value, $Model);
            if (!$res_update_oper) {
                // 回滚
                p("该应付单{$value['payment_no']}无法处理,原因是RY-4，采购单号为{$value['procurement_number']}");
                $Model->rollback();
                break;
            }
            // 5）删除对应的应付记录，软删除,状态和删除人以及删除时间
            $res_update_pay = $this->updatePurPaymentData($value, $Model);
            if (!$res_update_pay) {
                // 回滚
                p("该应付单{$value['payment_no']}无法处理,原因是RY-5，采购单号为{$value['procurement_number']}");
                $Model->rollback();
                break;
            }
            p("==================================该应付单{$value['payment_no']}处理成功，采购单号为{$value['procurement_number']}==============================");
            $Model->commit();
        }


    }

    public function updatePurOperationData($res_dedc, $value, $Model)
    {
        return $Model->table('tb_pur_operation')->where(['main_id' => $value['main_id']])->save(['money_type' => '2', 'main_id' => $res_dedc, 'updated_by' => 'system']);
    }

    public function updatePurPaymentData($value, $Model)
    {
        return $Model->table('tb_pur_payment')->where(['id' => $value['main_id']])->save(['status' => '5', 'deleted_by' => 'system', 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    public function fixWorkOrderData()
    {
        $Model = new Model();

        // 工单记录
        $where['id']           = '179';
        $save['opt_user_id']   = '9626';
        $save['opt_user_name'] = 'Habit.Wang';
        $res                   = $Model->table('tb_ms_question')->where($where)->save($save);
        p($res);
        $where['id'] = '180';
        $res         = $Model->table('tb_ms_question')->where($where)->save($save);
        p($res);

        $where['id']           = '168';
        $save                  = [];
        $save['opt_user_id']   = '400';
        $save['opt_user_name'] = 'Jeli.Fang';
        $res                   = $Model->table('tb_ms_question')->where($where)->save($save);
        p($res);
        $where['id'] = '140';
        $res         = $Model->table('tb_ms_question')->where($where)->save($save);
        p($res);

        // 日志记录
        $where['id']          = '284';
        $save                 = [];
        $save['deal_user_id'] = '9626';
        $res                  = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);
        $where['id'] = '285';
        $res         = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);


        $where['id']          = '287';
        $save                 = [];
        $save['deal_user_id'] = '400';
        $res                  = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);
        $where['id'] = '288';
        $res         = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);
        $where['id'] = '289';
        $res         = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);
        $where['id'] = '290';
        $res         = $Model->table('tb_ms_question_log')->where($where)->save($save);
        p($res);


    }

    public function fixOurCompanyData()
    {
        /*tb_crm_company_management from #9719
        id 55 删除
        id 37 更改 our_company_cd 为N001244712

        tb_crm_company_shareholder
        87 company_management_id 改为 37


        tb_ms_cmn_cd
        N001244719 USE_YN N*/
        $Model                         = new Model();
        $where['id']                   = '87';
        $save['company_management_id'] = '37';
        $del_res                       = $Model->table('tb_crm_company_shareholder')->where($where)->save($save);
        p($del_res);

        $where                  = [];
        $save                   = [];
        $where['id']            = '37';
        $save['our_company_cd'] = 'N001244712';
        $de_res                 = $Model->table('tb_crm_company_management')->where($where)->save($save);
        p($de_res);

        $where       = [];
        $where['id'] = '55';
        $dl_res      = $Model->table('tb_crm_company_management')->where($where)->delete();
        p($dl_res);

        $where          = [];
        $save           = [];
        $where['CD']    = 'N001244719';
        $save['USE_YN'] = 'N';
        $res            = $Model->table('tb_ms_cmn_cd')->where($where)->save($save);
        p($res);


    }

    /**
     * update b2b send out power
     */
    public function updateB2bPower()
    {
        $Model                = new Model();
        $where_power['power'] = 0;
        $all_order_batch_db   = $this->getAllOrderBatchId($Model, $where_power);
        Logs($all_order_batch_db, 'all_order_batch_db');
        $bill_id_arr       = $this->joinBillIdStr($all_order_batch_db);
        $res_unit_price_db = $this->getAllUnitPrice($Model, $bill_id_arr);
        Logs($res_unit_price_db, 'res_unit_price_db');
        foreach ($res_unit_price_db as $v) {
            $where_upd['SHIP_ID']            = $v['SHIP_ID'];
            $where_upd['SHIPPING_SKU']       = $v['SKU_ID'];
            $where_upd['DELIVERY_WAREHOUSE'] = $v['delivery_warehouse'];
            $old_db                          = $Model->table('tb_b2b_ship_goods')
                ->field('ID,power,DELIVERED_NUM')
                ->where($where_upd)
                ->find();
            if (!$old_db) {
                Logs($old_db);
                continue;
            }
            $save_upd['power'] = $old_db['DELIVERED_NUM'] * $v['rmb_unit_price'];
            $update_status_db  = $Model->table('tb_b2b_ship_goods')
                ->where($where_upd)
                ->save($save_upd);
            Logs([$old_db, $v, $update_status_db], 'updateDataLog');
        }

    }

    /**
     * @param $Model
     * @param $where_power
     *
     * @return array
     */
    private function getAllOrderBatchId($Model, $where_power)
    {
        $all_order_batch_db = $Model->table('tb_b2b_ship_goods,tb_b2b_ship_list')
            ->field('tb_b2b_ship_list.order_batch_id')
            ->where($where_power)
            ->where('tb_b2b_ship_goods.SHIP_ID = tb_b2b_ship_list.ID')
            ->group('tb_b2b_ship_list.order_batch_id')
            ->select();
        $all_order_batch_db = array_column($all_order_batch_db, 'order_batch_id');
        return $all_order_batch_db;
    }

    /**
     * @param $all_order_batch_db
     *
     * @return null|string
     */
    public function joinBillIdStr($all_order_batch_db)
    {
        $bill_id_arr = null;
        foreach ($all_order_batch_db as $order_batch_id) {
            $bill_id_arr .= "'{$order_batch_id}',";
        }
        $bill_id_arr = trim($bill_id_arr, ',');
        return $bill_id_arr;
    }

    /**
     * @param $Model
     * @param $bill_id_arr
     *
     * @return mixed
     */
    private function getAllUnitPrice($Model, $bill_id_arr)
    {
        $field             = 't3.GSKU AS SKU_ID,t3.send_num AS occupy_num,t1.warehouse_id AS delivery_warehouse,t3.unit_price,t3.unit_price AS rmb_unit_price,t3.unit_price_usd,t3.currency_id,t3.currency_time,t1.link_bill_id AS ORD_ID,t2.ID AS SHIP_ID';
        $res_unit_price_db = $Model->table('tb_wms_bill as t1,tb_b2b_ship_list as t2,tb_wms_stream as t3')
            ->field($field)
            ->where("t1.id = t3.bill_id AND t2.order_batch_id = t1.link_bill_id  AND t1.link_bill_id IN ({$bill_id_arr})")
            ->select();
        return $res_unit_price_db;
    }

    /**
     *
     */
    public function updateScmSendOutPower()
    {
        $Model                                 = new Model();
        $where_warehouse['DELIVERY_WAREHOUSE'] = $this->virtual_warehouse;
        $goods_db                              = $Model->table('tb_b2b_ship_goods')
            ->field('ID,SHIP_ID,SHIPPING_SKU,DELIVERED_NUM,power')
            ->where($where_warehouse)
            ->select();
        Logs($goods_db, 'goods_db');
        foreach ($goods_db as $good) {
            if ($good['power'] % $good['DELIVERED_NUM'] != 0) {
                $where_good['ID']   = $good['ID'];
                $save_good['power'] = $good['power'] * $good['DELIVERED_NUM'];
                $save_db            = $Model->table('tb_b2b_ship_goods')
                    ->where($where_good)
                    ->save($save_good);
                Logs([$where_good, $save_db], 'goods_db');
            }
        }
    }

    /**
     *
     */
    public function updateScmSendOutAllPower()
    {
        $Model = new Model();
        if ('all' == I('warehouse')) {
            $where_string = "tb_b2b_ship_goods.SHIP_ID = tb_b2b_ship_list.ID AND tb_b2b_ship_goods.DELIVERY_WAREHOUSE != '{$this->virtual_warehouse}'";
            $list_db      = $Model->table('tb_b2b_ship_list,tb_b2b_ship_goods')
                ->field('tb_b2b_ship_list.ID,tb_b2b_ship_list.power_all')
                ->where($where_string)
                ->group('tb_b2b_ship_list.ID')
                ->select();
            $b2b          = new B2bAction();
            foreach ($list_db as $list) {
                $res = $b2b->updateShipAllPower($list['ID']);
                Logs($res, 'updateShipAllPower');
            }
        } else {
            $where_string = "tb_b2b_ship_goods.SHIP_ID = tb_b2b_ship_list.ID AND tb_b2b_ship_goods.DELIVERY_WAREHOUSE = '{$this->virtual_warehouse}'";
            $list_db      = $Model->table('tb_b2b_ship_list,tb_b2b_ship_goods')
                ->field('tb_b2b_ship_list.ID,tb_b2b_ship_list.power_all')
                ->where($where_string)
                ->group('tb_b2b_ship_list.ID')
                ->select();
            foreach ($list_db as $list) {
                $where_good['SHIP_ID'] = $list['ID'];
                $goods_db              = $Model->table('tb_b2b_ship_goods')
                    ->field('ID,power')
                    ->where($where_good)
                    ->select();
                $powers_arr            = array_column($goods_db, 'power');
                $powers_sum            = array_sum($powers_arr);
                if ($list['power_all'] < $powers_sum) {
                    $where_ship_list['ID']        = $list['ID'];
                    $save_powers_sum['power_all'] = $powers_sum;
                    $save_db                      = $Model->table('tb_b2b_ship_list')
                        ->where($where_ship_list)
                        ->save($save_powers_sum);
                    Logs([$list, $powers_sum, $save_db], 'save_db');
                }
            }
        }
        Logs($list_db, 'list_db');

    }


    /**
     * 收款状态
     */
    public function updateB2bAllReceiptState()
    {
        // 部分收款
        $model             = new Model();
        $part_sql          = "
            SELECT
                sum(receipt_operation_status) AS sum_receipt_operation_status,
                sum(unconfirmed_state) AS sum_unconfirmed_state,
                COUNT(ID) AS count_id,
                ORDER_ID,
                PO_ID
            FROM
                tb_b2b_receipt
            GROUP BY
                ORDER_ID
            HAVING
              sum_receipt_operation_status > 0
            AND sum_receipt_operation_status = sum_unconfirmed_state
              ";
        $all_part_order_db = $model->query($part_sql);
        Logs(json_encode($all_part_order_db), 'all_part_order_db', 'updateB2bAllReceiptState');
        $upd_part_sql = "UPDATE tb_b2b_order,
             (
                SELECT
                    sum(receipt_operation_status) AS sum_receipt_operation_status,
                    sum(unconfirmed_state) AS sum_unconfirmed_state,
                    COUNT(ID) AS count_id,
                    ORDER_ID,
                    PO_ID
                FROM
                    tb_b2b_receipt
                GROUP BY
                    ORDER_ID
                HAVING                    
                 sum_receipt_operation_status > 0
                AND sum_receipt_operation_status = sum_unconfirmed_state
            ) AS t2
            SET tb_b2b_order.receipt_state = 1
            WHERE
                tb_b2b_order.ID = t2.ORDER_ID";
        $upd_part_db  = $model->query($upd_part_sql);
        Logs(json_encode($upd_part_db), 'upd_part_db', 'updateB2bAllReceiptState');
        //    全部收款
        $sql_all      = "
            SELECT
                sum(receipt_operation_status) AS sum_receipt_operation_status,
                sum(unconfirmed_state) AS sum_unconfirmed_state,
                COUNT(ID) AS count_id,
                ORDER_ID,
                PO_ID
            FROM
                tb_b2b_receipt
            GROUP BY
                ORDER_ID
            HAVING
              sum_receipt_operation_status > 0
            AND sum_receipt_operation_status = count_id
            AND sum_receipt_operation_status = sum_unconfirmed_state
              ";
        $all_order_db = $model->query($sql_all);
        Logs(json_encode($all_order_db), 'all_order_db', 'updateB2bAllReceiptState');
        $upd_all_sql = "UPDATE tb_b2b_order,
             (
                SELECT
                    sum(receipt_operation_status) AS sum_receipt_operation_status,
                    sum(unconfirmed_state) AS sum_unconfirmed_state,
                    COUNT(ID) AS count_id,
                    ORDER_ID,
                    PO_ID
                FROM
                    tb_b2b_receipt
                GROUP BY
                    ORDER_ID
                HAVING
                    sum_receipt_operation_status = count_id
                AND sum_receipt_operation_status > 0
                AND sum_receipt_operation_status = sum_unconfirmed_state
            ) AS t2
            SET tb_b2b_order.receipt_state = 2
            WHERE
                tb_b2b_order.ID = t2.ORDER_ID";
        $upd_all_db  = $model->query($upd_all_sql);
        Logs(json_encode($upd_all_db), 'upd_all_db', 'updateB2bAllReceiptState');
    }


    /**
     * 发货状态
     */
    public function updateB2bAllOrderState()
    {
        $model           = new Model();
        $sql_search      = " SELECT
                        t1.ORDER_ID,
                        t1.shipping_status,
                        t2.order_state
                    FROM
                        tb_b2b_doship AS t1,
                        tb_b2b_order AS t2
                    WHERE
                        t1.ORDER_ID = t2.ID
                    AND t1.shipping_status != t2.order_state + 1;
                    ";
        $order_status_db = $model->query($sql_search);
        Logs(json_encode($order_status_db), 'order_status_db', 'updateB2bAllOrderState');
        $sql                 = "UPDATE tb_b2b_order,
                 (
                    SELECT
                        t1.ORDER_ID,
                        t1.shipping_status,
                        t2.order_state
                    FROM
                        tb_b2b_doship AS t1,
                        tb_b2b_order AS t2
                    WHERE
                        t1.ORDER_ID = t2.ID
                    AND t1.shipping_status != t2.order_state + 1
                ) AS t3
                SET tb_b2b_order.order_state = t3.shipping_status - 1
                WHERE
                    tb_b2b_order.ID = t3.ORDER_ID";
        $order_status_upd_db = $model->query($sql);
        Logs(json_encode($order_status_upd_db), 'order_status_upd_db', 'updateB2bAllOrderState');
    }

    /**
     * 理货确认状态
     */
    public function updateB2bAllWarehousingState()
    {
        $model = new Model();
        // 更新全部确认
        $search_all_confirm_sql = "SELECT
            ORDER_ID,
            SUM(`status`) AS sum_status,
            COUNT(`ORDER_ID`) AS count_num
        FROM
            tb_b2b_warehouse_list
        GROUP BY
            ORDER_ID
        HAVING
            sum_status = count_num * 2";
        $warehousing_state_db   = $model->query($search_all_confirm_sql);
        Logs(json_encode($warehousing_state_db), 'warehousing_state_db', 'updateB2bAllWarehousingState');
        $upd_all_confirm_sql     = "UPDATE tb_b2b_order,
             (
                SELECT
                    ORDER_ID,
                    SUM(`status`) AS sum_status,
                    COUNT(`ORDER_ID`) AS count_num
                FROM
                    tb_b2b_warehouse_list
                GROUP BY
                    ORDER_ID
                HAVING
                    sum_status = count_num * 2
            ) AS t2
            SET tb_b2b_order.warehousing_state = 2
            WHERE
                tb_b2b_order.ID = t2.ORDER_ID";
        $warehousing_state_up_db = $model->query($upd_all_confirm_sql);
        Logs(json_encode($warehousing_state_up_db), 'warehousing_state_up_db', 'updateB2bAllWarehousingState');
        //    更新部分确认
        $search_all_confirm_sql    = "
        SELECT
            ORDER_ID,
            SUM(`status`) AS sum_status,
            COUNT(`ORDER_ID`) AS count_num
        FROM
            tb_b2b_warehouse_list
        GROUP BY
            ORDER_ID
        HAVING
            sum_status <> count_num * 2 
            AND sum_status > 1";
        $warehousing_state_part_db = $model->query($search_all_confirm_sql);
        Logs(json_encode($warehousing_state_part_db), 'warehousing_state_part_db', 'updateB2bAllWarehousingState');
        $upd_all_confirm_sql      = "UPDATE tb_b2b_order,
             (
                SELECT
                    ORDER_ID,
                    SUM(`status`) AS sum_status,
                    COUNT(`ORDER_ID`) AS count_num
                FROM
                    tb_b2b_warehouse_list
                GROUP BY
                    ORDER_ID
                HAVING
                    sum_status <> count_num * 2
                    AND sum_status > 1
            ) AS t2
            SET tb_b2b_order.warehousing_state = 1
            WHERE
                tb_b2b_order.ID = t2.ORDER_ID";
        $warehousing_state_all_db = $model->query($upd_all_confirm_sql);
        Logs(json_encode($warehousing_state_all_db), 'warehousing_state_all_db', 'updateB2bAllWarehousingState');
    }

    public function updateB2bDoship()
    {
        $model                    = new Model();
        $where['shipping_status'] = 0;
        $save['shipping_status']  = 1;
        $search_db                = $model->table('tb_b2b_doship')
            ->where($where)
            ->select();
        Logs($search_db, 'search_db', 'updateB2bDoship');
        $res = $model->table('tb_b2b_doship')
            ->where($where)
            ->save($save);
        Logs($res, 'update_num', 'updateB2bDoship');
    }

    public function addTbConDivisionWarehouseData()
    {
        $db_warehouses = TbMsCmnCd::where('CD', 'like', 'N00068%')->get()->toArray();
        foreach ($db_warehouses as $db_warehouse) {
            $temp_data['warehouse_cd']            = $db_warehouse['CD'];
            $temp_data['purchase_warehousing_by'] = 'zhaomu';
            $temp_data['transfer_warehousing_by'] = 'duhan';
            $temp_data['b2b_order_outbound_by']   = 'zhaomu';
            $temp_data['transfer_out_library_by'] = 'duhan';
            $temp_data['prchasing_return_by']     = 'zhaomu';
            $temp_data['updated_at']              = $temp_data['created_by'] = 'system';
            if (false !== strstr($db_warehouse['CD_VAL'], '日本')) {
                $temp_data['purchase_warehousing_by'] = 'yushu';

                $temp_data['b2b_order_outbound_by'] = 'yushu';

                $temp_data['prchasing_return_by'] = 'yushu';
            }
            if (false !== strstr($db_warehouse['CD_VAL'], '韩国')) {
                $temp_data['purchase_warehousing_by'] = 'zhenghe';

                $temp_data['b2b_order_outbound_by'] = 'zhenghe';

                $temp_data['prchasing_return_by'] = 'zhenghe';
            }
            if (false !== strstr($db_warehouse['CD_VAL'], '深圳')) {
                $temp_data['purchase_warehousing_by'] = 'yefan';
                $temp_data['transfer_warehousing_by'] = 'yefan';
                $temp_data['b2b_order_outbound_by']   = 'yefan';
                $temp_data['transfer_out_library_by'] = 'yefan';
                $temp_data['prchasing_return_by']     = 'yefan';
            }
            if (false !== strstr($db_warehouse['CD_VAL'], '上海')) {
                $temp_data['purchase_warehousing_by'] = 'yefan';
                $temp_data['transfer_warehousing_by'] = 'yefan';
                $temp_data['b2b_order_outbound_by']   = 'yefan';
                $temp_data['transfer_out_library_by'] = 'yefan';
                $temp_data['prchasing_return_by']     = 'yefan';
            }
            $temp_datas[] = $temp_data;
        }
        $this->model = new Model();
        $res         = $this->model->table('tb_con_division_warehouse')->addAll($temp_datas);
        var_dump($res);
    }

    public function updateAllocationStatus()
    {
        $data = DataModel::getDataNoBlankToArr();
        $sql  = "UPDATE tb_wms_allo SET state = '{$data['state']}' WHERE allo_no = '{$data['allo_no']}'";
        Logs([$sql, DataModel::userNamePinyin()], __FUNCTION__, __CLASS__);
        var_dump($this->model->execute($sql));
    }

    public function updateEsOrders()
    {
        if (IS_POST) {
            $orders    = trim(I('orders'));
            $order_arr = explode(',', $orders);
            if (empty($order_arr) || !is_array($order_arr)) {
                $this->error('数据为空');
            }
            $order_id = WhereModel::arrayToInString($order_arr);
            $sql      = "SELECT ORDER_ID,PLAT_CD FROM tb_op_order WHERE ORDER_ID IN ({$order_id})";
            $Model    = new Model();
            $op_dbs   = $Model->query($sql);
            foreach ($op_dbs as $value) {
                $temp_order    = [
                    'opOrderId' => $value['ORDER_ID'],
                    'platCd'    => $value['PLAT_CD'],
                ];
                $temp_orders[] = $temp_order;
            }
            if ($temp_orders) {
                $res = ApiModel::publicProcess($temp_orders);
            }
            $this->success($res['res']);
        }
        $this->display('update_es_orders');
    }

    public function supplementaryVirtualWarehouse()
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $pms_database      = PMS_DATABASE;
            $purchase_order_no = 'RN201808090010-002';
            //            $purchase_order_no = 'RN201907240008-002';
            $relevance_id_sql    = "SELECT
                            tb_pur_relevance_order.relevance_id
                        FROM
                            `tb_pur_relevance_order`,
                            `tb_pur_order_detail`
                        WHERE
                            tb_pur_order_detail.order_id = tb_pur_relevance_order.order_id
                        AND tb_pur_order_detail.procurement_number = '{$purchase_order_no}'";
            $relevance_id        = $Model->query($relevance_id_sql)[0]['relevance_id'];
            $get_goods_sql       = "SELECT t.*, a.upc_id,
                         sum(b.warehouse_number) warehouse_number,
                         sum(b.warehouse_number_broken) warehouse_number_broken,
                         sum(
                            CASE c.warehouse_status
                            WHEN 1 THEN
                                0
                            ELSE
                                b.ship_number - b.warehouse_number - b.warehouse_number_broken
                            END
                        ) unwarehoused_number
                        FROM
                            tb_pur_goods_information t
                        LEFT JOIN {$pms_database}.product_sku a ON a.sku_id = t.sku_information
                        LEFT JOIN tb_pur_ship_goods b ON b.information_id = t.information_id
                        LEFT JOIN tb_pur_ship c ON c.id = b.ship_id
                        WHERE
                            (t.relevance_id = '{$relevance_id}')
                        GROUP BY
                            t.information_id";
            $goods               = $Model->query($get_goods_sql);
            $shipping_number_sum = $warehouse_number_sum = array_sum(array_column($goods, ''));
            $tb_pur_ship         = "INSERT INTO `tb_pur_ship` (`relevance_id`, `has_ship_info`, `bill_of_landing`, `warehouse_id`, `shipping_number`, `warehouse_number`, `warehouse_number_broken`, `difference_number`, `extra_cost_currency`, `extra_cost`, `warehouse_extra_cost_currency`, `warehouse_extra_cost`, `shipment_date`, `arrival_date`, `arrival_date_actual`, `sale_no`, `sale_no_correct`, `need_warehousing`, `need_warehousing_correct`, `warehouse`, `warehouse_correct`, `credential`, `remark`, `warehouse_status`, `tally_list`, `warehouse_user`, `warehouse_time`, `create_user`, `create_time`) VALUES ('{$relevance_id}', '1', '', '', '{$shipping_number_sum}', '0', '0', '0', '', '0.00', '', '0.00', '2019-07-30', '2019-07-30', NULL, '', '', '1', '1', 'N000680500', '', '', '', '1', '0', '', NULL, '-shoudong', '2019-07-30 11:21:52')";
            $tb_pur_ship_id      = $Model->execute($tb_pur_ship);
            if (false === $tb_pur_ship_id) {
                throw new Exception('main error' . __LINE__);
            }
            $tb_pur_ship_id = $Model->getLastInsID();
            echo '$tb_pur_ship_id' . $tb_pur_ship_id . PHP_EOL;
            $time                = DateModel::now();
            $tb_pur_warehouse    = "INSERT INTO `tb_pur_warehouse` (`warehouse_code`, `ship_id`, `tally_list`, `warehouse_number`, `warehouse_number_broken`, `log_currency`, `storage_log_cost`, `service_currency`, `log_service_cost`, `warehouse_user`, `warehouse_time`) VALUES ( NULL, '{$tb_pur_ship_id}', '0', '{$warehouse_number_sum}', '0', '', '0.00', '', '0.00', '', '{$time}')";
            $tb_pur_warehouse_id = $Model->execute($tb_pur_warehouse);
            if (false === $tb_pur_warehouse_id) {
                throw new Exception('main error' . __LINE__);
            }
            $tb_pur_warehouse_id = $Model->getLastInsID();
            echo '$tb_pur_warehouse_id' . $tb_pur_warehouse_id . PHP_EOL;
            foreach ($goods as $good) {
                $tb_pur_ship_goods    = "INSERT INTO `tb_pur_ship_goods` (`ship_id`, `information_id`, `search_id`, `sku_id`, `goods_name`, `goods_attribute`, `length`, `width`, `height`, `weight`, `warehouse`, `ship_number`, `number_info_ship`, `number_info_warehouse`, `warehouse_number`, `warehouse_number_broken`, `difference_number`, `difference_reason`, `tax_rate`, `warehouse_cost`, `production_date_ship`, `production_date`, `sku_id_back`, `search_id_back`) VALUES ('{$tb_pur_ship_id}', '{$good['information_id']}', '', '', '', '', '0.00', '0.00', '0.00', '0.00', '', '{$good['goods_number']}', '', '', '0', '0', '0', '', '', '0.00', NULL, NULL, NULL, NULL)";
                $tb_pur_ship_goods_id = $Model->execute($tb_pur_ship_goods);
                if (false === $tb_pur_ship_goods_id) {
                    throw new Exception('error' . __LINE__);

                }
                $tb_pur_ship_goods_id = $Model->getLastInsID();
                echo '$tb_pur_ship_goods_id' . $tb_pur_ship_goods_id . PHP_EOL;

                $tb_pur_warehouse_goods    = "INSERT INTO `tb_pur_warehouse_goods` ( `warehouse_id`, `ship_goods_id`, `length`, `width`, `height`, `weight`, `tax_rate`, `warehouse_number`, `warehouse_number_broken`, `number_info_warehouse`) VALUES ('{$tb_pur_warehouse_id}', '{$tb_pur_ship_goods_id}', '0.00', '0.00', '0.00', '0.00', '', '{$good['goods_number']}', '0', '');";
                $tb_pur_warehouse_goods_id = $Model->execute($tb_pur_warehouse_goods);
                if (false === $tb_pur_warehouse_goods_id) {
                    throw new Exception('error' . __LINE__);
                }
                $tb_pur_warehouse_goods_id = $Model->getLastInsID();
                echo '$tb_pur_warehouse_goods_id' . $tb_pur_warehouse_goods_id . PHP_EOL;

            }
            echo 'success';
            $Model->commit();
        } catch (Exception $exception) {
            var_dump($exception);
            $Model->rollback();
        }
    }

    public function updateMarkPurchaseShipment()
    {
        try {
            $purchase_order_nos = [
                'RN201904020006-002',
                'RN201904080006-002',
                'RN201904010017-001',
                'RN201903220017-002',
                'RN201901140004-001',
            ];
            $Model              = new Model();
            $Model->startTrans();
            foreach ($purchase_order_nos as $purchase_order_no) {
                $sql      = "SELECT
                            tb_pur_relevance_order.relevance_id,
                            tb_pur_order_detail.order_id,
                            tb_pur_ship.warehouse_status,
                            tb_pur_relevance_order.warehouse_status AS order_warehouse_status
                        FROM
                            (
                                tb_pur_order_detail,
                                tb_pur_relevance_order
                            )
                        LEFT JOIN tb_pur_ship ON tb_pur_ship.relevance_id = tb_pur_relevance_order.relevance_id
                        WHERE
                            tb_pur_order_detail.procurement_number = '{$purchase_order_no}'
                        AND tb_pur_relevance_order.order_id = tb_pur_order_detail.order_id;
                        ";
                $temp_res = $Model->query($sql);
                if (empty($temp_res[0]['order_id'])) {
                    continue;
                }
                $all_carry_out = array_sum(array_column($temp_res, 'warehouse_status'));
                if (empty($temp_res) || 0 == $all_carry_out) {
                    $save['warehouse_status'] = 0;
                } elseif (count($temp_res) == $all_carry_out) {
                    $save['warehouse_status'] = 2;
                } else {
                    $save['warehouse_status'] = 1;
                }

                $where['order_id']       = $temp_res[0]['order_id'];
                $res[$purchase_order_no] = $Model->table('tb_pur_relevance_order')
                    ->where($where)
                    ->save($save);
            }
            $Model->commit();
            var_dump($res);
        } catch (Exception $exception) {
            Logs($exception);
            $Model->rollback();
            echo 'error';
        }
    }

    public function updatePurGoodsNum()
    {
        $purchase_order_nos  = [
            'RN201904020006-002',
            'RN201904080006-002',
            'RN201904010017-001',
            'RN201903220017-002',
            'RN201901140004-001',
        ];
        $procurement_numbers = WhereModel::arrayToInString($purchase_order_nos);
        $sql                 = "
        UPDATE tb_pur_goods_information,(SELECT
                        tb_pur_relevance_order.relevance_id
                        FROM
                            (
                                tb_pur_order_detail,
                                tb_pur_relevance_order
                            )
                        WHERE
                            tb_pur_order_detail.procurement_number IN ( {$procurement_numbers})
                        AND tb_pur_relevance_order.order_id = tb_pur_order_detail.order_id) AS t1
        SET 
        tb_pur_goods_information.ship_end_number  = tb_pur_goods_information.ship_end_number + (tb_pur_goods_information.goods_number -  tb_pur_goods_information.shipped_number )
        WHERE tb_pur_goods_information.relevance_id = t1.relevance_id
        ";
        echo $sql;
    }

    public function sendOutGoodsApiError()
    {
        $Db = new Model();
        $Db->startTrans();
        $ship_list_sql = "INSERT INTO `tb_b2b_ship_list` (`BILL_LADING_CODE`,`DELIVERY_TIME`,`Estimated_arrival_DATE`,`SHIPMENTS_NUMBER`,`LOGISTICS_CURRENCY`,`REMARKS`,`warehouse`,`is_use_send_net`,`LOGISTICS_COSTS`,`DOSHIP_ID`,`AUTHOR`,`order_id`,`order_batch_id`,`goods_batch_json`,`SUBMIT_TIME`) VALUES ('131-5413 9724/ PLIJP2A11043','2019-09-20','2019-09-25',6249,'N000590100','','N000689464','0',262.1842354767,3395,'yushu','3198','RN201909040011_3395_0926181926','{}','2019-09-26 18:19:26') ";
        $Db->execute($ship_list_sql);
        $ship_id            = $Db->getLastInsID();
        $warehouse_list_sql = "INSERT INTO `tb_b2b_warehouse_list` (`SHIPMENTS_NUMBER`,`warehouse`,`DOSHIP_ID`,`AUTHOR`,`order_id`,`SHIP_LIST_ID`) VALUES (6249,'N000689464',3395,'yushu','3198',{$ship_id})  ";
        $Db->execute($warehouse_list_sql);
        $warehouse_id = $Db->getLastInsID();

        $ship_goods_sql = "INSERT INTO `tb_b2b_ship_goods` (`SHIP_ID`,`SHIPPING_SKU`,`sku_show`,`SHIPPED_NUM`,`DELIVERED_NUM`,`power`,`DELIVERY_WAREHOUSE`,`REMARKS`,`goods_id`) VALUES ({$ship_id},'8000842601','8000842601',0,'67',0,'N000689464',null,'17455'),({$ship_id},'8000842701','8000842701',0,'4314',0,'N000689464',null,'17452'),({$ship_id},'8000842801','8000842801',0,'1535',0,'N000689464',null,'17442'),({$ship_id},'8002815201','8002815201',0,'333',0,'N000689464',null,'17447')";
        $Db->execute($ship_goods_sql);
        $warehouse_goods_sql = "INSERT INTO `tb_b2b_warehousing_goods` (`ship_id`,`warehousing_id`,`warehouse_sku`,`sku_show`,`TOBE_WAREHOUSING_NUM`,`DELIVERY_WAREHOUSE`,`REMARKS`,`goods_id`) VALUES ({$ship_id},{$warehouse_id},'8000842601','8000842601','67','N000689464',null,'17455'),({$ship_id},{$warehouse_id},'8000842701','8000842701','4314','N000689464',null,'17452'),({$ship_id},{$warehouse_id},'8000842801','8000842801','1535','N000689464',null,'17442'),({$ship_id},{$warehouse_id},'8002815201','8002815201','333','N000689464',null,'17447')";
        $Db->execute($warehouse_goods_sql);

        $Db->execute("INSERT INTO `tb_b2b_log` (`ORDER_ID`,`STATE`,`COUNT`,`associated_document_number`,`USER_ID`,`create_time`) VALUES (3198,1,'进行发货RN201909040011_3395_0926181926',null,'yushu','2019-09-26 18:19:26') ");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=384,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842601 AND ID = 17456 ) ");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=4314,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842701 AND ID = 17452 )");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=60,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842701 AND ID = 17453 ) ");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=120,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842701 AND ID = 17454 )");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=1535,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842801 AND ID = 17442 ) ");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=120,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842801 AND ID = 17443 )");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=200,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8000842801 AND ID = 17444 ) ");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=540,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8002815201 AND ID = 17450 )");
        $Db->execute("UPDATE `tb_b2b_goods` SET `SHIPPED_NUM`=120,`TOBE_DELIVERED_NUM`=0 WHERE ( ORDER_ID = 3198 AND SKU_ID = 8002815201 AND ID = 17451 ) ");
        $Db->execute("UPDATE `tb_b2b_order` SET `order_state`=1 WHERE ( ID = 3198 )");
        $Db->execute("UPDATE `tb_b2b_doship` SET `sent_num`=11612,`todo_sent_num`=298,`update_time`='2019-09-26 18:19:26',`shipping_status`=2 WHERE ( ORDER_ID = 3198 )");
        $Db->commit();
    }

    public function b2bUpdate()
    {
        $Db = new Model();
        $Db->startTrans();
        $Db->execute("UPDATE `tb_b2b_doship` SET `sent_num`='13322', `todo_sent_num`='0', `shipping_status`='3' WHERE (`ID`='3285')");
        $Db->execute("UPDATE `tb_b2b_order` SET `order_state`='2' WHERE (`ID`='3088')");
        $Db->commit();
    }

    //更正采购发货入库仓库数据
    public function updatePurShipWarehouse()
    {
        $model = new Model();
        $sql1  = "SELECT * from tb_pur_ship WHERE id = 8736";
        Logs($model->query($sql1), __FUNCTION__, 'repair');
        $sql2 = "UPDATE tb_pur_ship SET warehouse='N000686900' where id = 8736";
        $res  = $model->execute($sql2);
        v($res);
    }

    public function b2bErrorDoShipStatus()
    {
        $Db = new Model();
        $Db->startTrans();

        $Db->execute("UPDATE tb_b2b_doship SET sent_num = 39170 WHERE PO_ID = 'RN201908270013'");


        $Db->commit();
    }

    public function b2bWithdraw()
    {
        $Db = new Model();
        $Db->startTrans();
        $Db->execute("UPDATE `tb_b2b_order` SET `order_state`=0 WHERE ( `ID` = '3278' )");
        $Db->execute("UPDATE `tb_pur_relevance_order` SET `ship_status`=0,`warehouse_status`=0,`shipped_number`=0 WHERE ( `order_id` = '11212' ) ");
        $Db->execute("UPDATE `tb_pur_goods_information` SET `shipped_number`=0 WHERE ( `relevance_id` = '11212' ) AND ( `sku_information` = '8000391701' ) ");
        $Db->execute("UPDATE `tb_wms_batch` SET `total_inventory`=1935,`occupied`=1935 WHERE ( `purchase_order_no` = 'RN201910100006-001' ) AND ( `SKU_ID` = '8000391701' ) AND ( `vir_type` = 'N002440200' ) ");
        $Db->execute(" UPDATE `tb_wms_batch_order` SET `ORD_ID`='RN201910100006',`batch_id`='248590',`use_type`=1 WHERE ( `ORD_ID` = 'RN201910100006_d_248590' ) AND ( `use_type` = 2 )");
        $Db->execute(" UPDATE `tb_wms_batch` SET `total_inventory`=231,`occupied`=231 WHERE ( `purchase_order_no` = 'RN201910100006-001' ) AND ( `SKU_ID` = '8000391801' ) AND ( `vir_type` = 'N002440200' )");
        $Db->commit();
    }

    public function deleteErrorStore()
    {
        $Db = new Model();
        $Db->startTrans();
        $sql = "DELETE tb_ms_store
                FROM
                    tb_ms_store
                WHERE
                    ID IN (236, 235, 233, 232)";
        $Db->execute($sql);
        $Db->commit();
    }

    public function updateUserScNm()
    {
        $Db = new Model();
        $Db->startTrans();
        $sql = "UPDATE `tb_hr_card`
                    SET `EMP_SC_NM` = REPLACE (
                        `EMP_SC_NM`,
                        '.',
                        ' '
                    )";
        $Db->execute($sql);
        $Db->commit();
    }

    public function updateAlloState()
    {
        $Db = new Model();
        $Db->startTrans();
        $sql = "UPDATE `tb_wms_allo` SET `state`='N001970602' WHERE (`id`='2696')";
        $Db->execute($sql);
        $Db->commit();
    }

    public function updateAdminHuaming()
    {
        $Db = new Model();
        $Db->startTrans();
        $Db->execute("UPDATE bbm_admin SET huaming = 'zhengxiu' WHERE huaming = 'Amy.Li'");
        $Db->execute("UPDATE bbm_admin SET huaming = 'lingqing' WHERE huaming = 'Ezer.Kang'");
        $Db->commit();
    }

    //修复采购单应付状态
    public function repairPaymentStatus()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_pur_relevance_order SET payment_status = 2 WHERE relevance_id IN ('9794',
            '10327',
            '10522',
            '10527',
            '10726',
            '10777',
            '10959',
            '11058',
            '10803',
            '10804',
            '10857',
            '10943',
            '10958',
            '11042',
            '11046',
            '11047',
            '11073',
            '11080',
            '11126',
            '11140',
            '11152',
            '11168',
            '11172',
            '11199',
            '11216',
            '11235',
            '11243',
            '11244',
            '11256',
            '11314',
            '11315',
            '11317',
            '11338',
            '11350',
            '11372',
            '11383',
            '11412'
         )");
        $Db->commit();
        echo $res;
        die;
    }

    public function updateConversionName()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE `tb_scm_conversion` SET need_reviewer = 'Bryan.Shang' WHERE need_reviewer = 'bryan.shang'");
        $Db->commit();
        echo $res;
        die;
    }

    //修改国家表英格兰二字码
    public function updateUserArea()
    {
        $model              = new Model();
        $where['zh_name']   = '英格兰';
        $where['area_type'] = 1;
        $save['two_char']   = 'GB';
        $search_db          = $model->table('tb_ms_user_area')
            ->where($where)
            ->select();
        Logs($search_db, 'search_db', 'updateUserArea');
        $res = $model->table('tb_ms_user_area')
            ->where($where)
            ->save($save);
        Logs($res, 'update_num', 'updateUserArea');
        echo $res;
        die;
    }

    public function updateOrderPackageTemp()
    {
        $order_id = I('order_id');
        $plat_cd  = I('plat_cd');
        $sql      = "UPDATE `tb_op_order` SET `LOGISTICS_SINGLE_STATU_CD`='N002080400' WHERE (`ORDER_ID`='{$order_id}' AND PLAT_CD = '{$plat_cd}')";
        $Db       = new Model();
        $res      = $Db->execute($sql);
        echo $res;

    }

    //更新我方公司详情
    public function updateOurCompany()
    {
        $Db = new Model();
        $Db->startTrans();
        //旧我方公司
        $name = 'IZENE AUSTRALIA PTY. LTD.';
        //新我方公司
        $name_new = 'IZENE AUSTRALIA PTY.LTD 澳大利亚子公司';
        $ret      = $this->updateOurCompanyDetail($name, $name_new);
        //旧我方公司
        $name = 'iZENEeu International Tech B.V.';
        //新我方公司
        $name_new = 'iZENEeu International Tech B.V. 荷兰子公司';
        $ret      = $this->updateOurCompanyDetail($name, $name_new);

        $Db->commit();
        //$Db->rollback();
        echo json_encode($ret);
    }

    //更新我方公司详情
    public function updateOurCompanyDetail($name, $name_new)
    {
        $this->old_data = $this->getCDByOurCompany($name);
        //旧我方公司取消使用
        $res            = M('_ms_cmn_cd', 'tb_')->where(['CD_VAL' => $name])->save(['USE_YN' => 'N']);
        $this->new_data = $this->getCDByOurCompany($name_new);
        $res            = $this->editOurCompanyRelation();

        return $res;
    }

    //更新我方公司关联表信息
    public function getCDByOurCompany($name)
    {
        //旧我方公司详情
        $model                = M('_ms_cmn_cd', 'tb_');
        $conditions['CD_VAL'] = $name;
        $conditions ['CD']    = ['like', '%N00124%'];
        $ret                  = $model->field('CD, CD_VAL, ETC')->where($conditions)->find();
        Logs($ret, __FUNCTION__, '采购退货我方公司数据备份');
        return $ret;
    }

    //更新我方公司关联表信息
    public function editOurCompanyRelation()
    {
        $model = new Model();
        //记录日志旧我方公司数据备份
        //采购模块 -> 采购退货 我方公司 tb_pur_deduction
        $pur_return = $model->table('tb_pur_deduction')->where(['our_company_cd' => $this->old_data['CD']])->select();
        Logs($pur_return, __FUNCTION__, '采购退货我方公司数据备份');
        $res = $model->table('tb_pur_deduction')->where(['our_company_cd' => $this->old_data['CD']])->save(['our_company_cd' => $this->new_data['CD'], 'our_company_name' => $this->new_data['CD_VAL']]);

        $pur_return = $model->table('tb_b2b_info')->where(['our_company' => $this->old_data['CD_VAL']])->select();
        Logs($pur_return, __FUNCTION__, 'b2b详情我方公司数据备份');
        //B2b业务 -> 采购退货 我方公司 tb_b2b_info 更新条件为我方公司名称
        $pur_return = $model->table('tb_b2b_info')->where(['our_company' => $this->old_data['CD_VAL']])->save(['our_company' => $this->new_data['CD_VAL']]);

        //采购模块 -> 采购订单 我方公司 tb_pur_order_detail
        $res = $this->editOurCompany('tb_pur_order_detail', 'our_company');

        //采购模块 -> 采购退货 我方公司 tb_pur_return
        $res = $this->editOurCompany('tb_pur_return', 'our_company_cd');

        //B2b业务 -> 采购订单 我方公司 tb_fin_account_turnover
        $res = $this->editOurCompany('tb_fin_account_turnover', 'opp_company_name');

        //仓储管理 -> 采购订单 我方公司 tb_wms_bill
        $res = $this->editOurCompany('tb_wms_bill', 'CON_COMPANY_CD');

        //物流管理 -> 采购订单 我方公司 tb_ms_logistics_account_info
        $res = $this->editOurCompany('tb_ms_logistics_account_info', 'our_sigin_company_cd');

        //财务管理 -> 采购订单 我方公司 tb_fin_account_turnover
        $res = $this->editOurCompany('tb_fin_account_turnover', 'company_code');

        //财务管理 -> 采购订单 我方公司 tb_fin_account_bank
        $res = $this->editOurCompany('tb_fin_account_bank', 'company_code');

        //财务管理 -> 采购订单 我方公司 tb_pur_payment_audit
        $res = $this->editOurCompany('tb_pur_payment_audit', 'our_company_cd');

        //法务管理 -> 采购订单 我方公司 tb_crm_contract
        $res = $this->editOurCompany('tb_crm_contract', 'CON_COMPANY_CD');

        //法务管理 -> 采购订单 我方公司 tb_crm_qualification
        $res = $this->editOurCompany('tb_crm_qualification', 'our_company_code');

        //配置管理 -> 采购订单 我方公司 tb_con_division_our_company
        $res = $this->editOurCompany('tb_con_division_our_company', 'our_company_cd');

        //配置管理 -> 采购订单 我方公司 tb_ms_store
        $res = $this->editOurCompany('tb_ms_store', 'company_cd');

        //需求管理 -> 采购订单 我方公司 tb_sell_demand
        $res = $this->editOurCompany('tb_sell_demand', 'our_company');

        return $res;
    }

    //更新我方公司关联表信息
    public function editOurCompany($table, $key = 'our_company_cd')
    {
        $model = new Model();
        //记录日志旧我方公司数据备份
        $order_detail = $model->table($table)->where([$key => $this->old_data['CD']])->select();
        Logs($order_detail, __FUNCTION__, '我方公司数据备份');
        $res = $model->table($table)->where([$key => $this->old_data['CD']])->save([$key => $this->new_data['CD']]);
        return $res;
    }

    public function updatePurSpCharterNo()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_pur_order_detail SET sp_charter_no = '2623377' WHERE sp_charter_no = '68642028-000-12-18-5'");
        $Db->commit();
        echo $res;
        die;
    }

    public function updateB2bPayable()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_b2b_receivable SET order_account = '0.00', current_receivable = '0.00' WHERE id IN (3828, 3829)");
        $Db->commit();
        echo $res;
        die;
    }

    public function updateScmGoodsNum()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_sell_demand_goods SET on_way_number = 0, spot_number = 32 WHERE id = 25328");
        $Db->commit();
        echo $res;
        die;
    }

    public function updatePaymentAccountingAudit()
    {
        $Db = new Model();
        $Db->startTrans();
        $date = date('Y-m-d H:i:s', time());
        $res  = $Db->execute("update `tb_pur_payment` set `supply_note` = `accounting_return_reason`");
        $res  = $Db->execute("update `tb_pur_payment` set `accounting_return_reason` = ''");
        $Db->commit();
        echo $res;
        die;
    }

    public function importRemoteArea()
    {
        $model = M('area_configuration', 'tb_op_');
        $model->where(['id' => ['gt', 0]])->delete();
        $list = '[{"num":"171","company":"万邑通"}]';
        $list = json_decode($list, true);
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'country_id'         => '105',
                'description'        => null,
                'prefix_postal_code' => $item['num'],
                'logistics_company'  => 'N000708200',
                'logistics_mode'     => '369',
                'created_by'         => 'Habit Wang',
                'updated_by'         => 'Habit Wang',
            ];
        }
        $model->startTrans();
        $res = $model->addAll($data);
        if (!$res) {
            $model->rollback();
            die('fail');
        }
        $model->commit();
        echo $res;
        die;
    }

    public function addSystemCoverValidates()
    {
        $date  = I('date');
        $limit = I('limit');
        if (empty($limit)) {
            $limit = 500;
        }
        if (empty($date)) {
            return null;
        }
        $Db         = new Model();
        $sql        = "SELECT
                    tb_op_order.ORDER_ID,
                    tb_op_order.PLAT_CD,
                    tb_op_order.ORDER_PAY_TIME,
                    tb_system_cover_validates.validate_json
                FROM
                    tb_op_order,
                    tb_system_cover_validates
                WHERE
                    tb_op_order.PLAT_CD IN (
                        'N000831200',
                        'N000832800',
                        'N000835000',
                        'N000834900',
                        'N000834400',
                        'N000834700',
                        'N000832500'
                    )
                AND tb_op_order.ORDER_PAY_TIME >= '{$date} 00:00:00'
                AND tb_op_order.ORDER_PAY_TIME <= '{$date} 23:59:59'
                AND tb_system_cover_validates.order_id = tb_op_order.ORDER_ID
                AND tb_system_cover_validates.plat_cd = tb_op_order.PLAT_CD
                AND tb_system_cover_validates.sorts_id = 3
                AND (
                    tb_system_cover_validates.validate_json NOT LIKE '%order_time%'
                    OR tb_system_cover_validates.validate_json NOT LIKE '%order_pay_time%'
                )
                LIMIT {$limit}";
        $res        = $Db->query($sql);
        $systemSort = new SystemSortModel();
        foreach ($res as $re) {
            $systemSort->order_id     = $re['ORDER_ID'];
            $systemSort->plat_cd      = $re['PLAT_CD'];
            $systemSort->update_datas = [
                'ORDER_TIME'     => '',
                'ORDER_PAY_TIME' => '',
            ];
            $systemSort->updateSystemSort(3);
            $update_ing[] = $re['ORDER_ID'];
        }
        var_dump($update_ing);
    }

    public function deleteDc()
    {
        $Db  = new Model();
        $res = $Db->execute("DELETE FROM tb_con_division_our_company WHERE id = 182");
        echo $res;
        die;
    }

    //初始化资质Excel数据
    public function qualificationInit()
    {
        $Db  = new Model();
        $sql = "
        INSERT INTO `tb_crm_qualification`(`number`, `our_company_code`, `name`, `attachment`, `issue_date`, `expire_date`, `renew_date`, `is_long_time`, `issue_office`, `renew_address`, `renew_material`, `department`, `query_path`, `precautions`, `content`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
('GSZZ201912130001', 'N001240300', '自理报检企业备案登记证明书', '', '2015年2月3日', '2020年2月2日', '', 0, '上海出入境检验检疫局', NULL, 'customs', NULL, NULL, '名称、地址、法人变更后需要变更证照', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130002', 'N001240300', '互联网药品信息服务资格证书', '', '2013年4月25日', '2018年4月24日', '2017年9月24日', 0, '浦东市场监督管理局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130003', 'N001240300', '对外贸易经营者备案登记表', '', '2017年5月16日', '/', '', 0, '浦东市场监督管理局', '合欢路2号', NULL, NULL, NULL, '名称、地址、法人变更后需要变更证照', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130004', 'N001240300', '中国海关报关单位注册登记证书', '', '2017年6月6日', '长期有效', '', 0, '上海浦东海关', '合欢路3号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130005', 'N001240300', '食品经营许可证', '', '2016年9月29日', '2019年9月28日', '2019年7月', 0, '浦东市场监督管理局', '合欢路2号', NULL, NULL, NULL, '零售批发', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130006', 'N001240300', '酒类商品零售许可证', '', '2016年11月16日', '2019年11日15', '', 0, '酒类专卖管理局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130007', 'N001240500', '食品经营许可证', '', '2018年7月13日', '2023年7月12日', '2023年7月12日', 0, '上海市浦东新区市场监督管理局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130008', 'N001240500', '对外经营者备案登记表', '', '2017年5月3日', '/', '/', 0, '对外贸易经营者备案登记局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130009', 'N001240500', '出入境检验检疫报检企业备案表', '', '2017年4月11日', '/', '/', 0, '中华人民共和国上海出入境检验检疫局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130010', 'N001240500', '海关报关单位注册登记证书', '', '2017年5月4日', '长期', '/', 0, '浦东海关', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130011', 'N001240500', '酒类商品批发许可证', '', '2016年12月21日', '2019年12月20日', '2019年12月20日', 0, '上海市酒类专卖管理局', '肇嘉浜路301号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130012', 'N001240500', '酒类商品零售许可证', '', '2017年4月18日', '2020年4月17日', '2020年4月17日', 0, '上海市浦东新区酒类专卖管理局', '合欢路2号', NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130013', 'N001242600', '对外经营者备案登记表', '', '2018年8月1日', '/', '/', 0, '对外贸易经营者备案登记局', NULL, NULL, NULL, NULL, NULL, '  ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130014', 'N001242600', '海关报关单位注册登记证书', '', '2017年3月31日', '长期', '/', 0, '中华人民共和国深圳海关', NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130015', 'N001242700', '海关报关单位注册登记证书', '', '2017年6月8日', '长期', '/', 0, '中国人民共和国嘉定海关', NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130016', 'N001242700', '对外贸易经营者备案登记表', '', '2017年5月19日', '/', '/', 0, '对外贸易经营者备案登记局', NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130017', 'N001242700', '出入境检验检疫报检企业备案表', '', '2017年6月1日', '/', '/', 0, '中华人民共和国上海出入境检验检疫局', NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130018', 'N001240200', '上海市高新技术成果转化项目', '', '2014年11月10日', '2019年11月10日', '2019年1月1日', 0, '上海市高新技术成果转化项目认定委员会（代理）', NULL, NULL, NULL, NULL, '代理', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130019', 'N001240200', '海关报关单位注册登记证书', '', '2017年6月12日', '长期', '/', 0, '外高桥海关', '合欢路2号', NULL, NULL, NULL, '换新地址', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130020', 'N001240200', '对外经营者备案登记表', '', '2017年6月6日', '长期', '/', 0, '对外贸易经营者备案登记局', '合欢路2号', NULL, NULL, NULL, '换新地址', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130021', 'N001240200', '出入境检验检疫报检企业备案表', '', '2017年6月7日', '长期', '/', 0, '上海出入境检验检疫局', '合欢路2号', NULL, NULL, NULL, '换新地址', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130022', 'N001240200', '高新技术企业', '', '2016年11月24日', '2019年11月24日', '2019年1月1日', 0, '上海科委（代理）', NULL, NULL, NULL, NULL, '代理', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130023', 'N001244717', 'IZENE SPAIN INTERNATIONAL, S.L.公司成立文本', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\西班牙公司- IZENE SPAIN INTERNATIONAL S.L\\IZENE SPAIN INTERNATIONAL, S.L. 注册交接文件', '仅有西语件；暂未找到其他语言文件；待确认', '公司成立时间：2019年1月24日 注册地址：PASEO DE GRACIA 78,5-1., BARCELONA', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130024', 'N001244717', 'IZENE SPAIN INTERNATIONAL, S.L.公司税号（NIF）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\西班牙公司- IZENE SPAIN INTERNATIONAL S.L\\IZENE SPAIN INTERNATIONAL, S.L. 注册交接文件', '-', '公司税号：B67369397', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130025', 'N001244717', 'IZENE SPAIN INTERNATIONAL, S.L.公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\西班牙公司- IZENE SPAIN INTERNATIONAL S.L\\IZENE SPAIN INTERNATIONAL, S.L. 注册交接文件', '-', '约定经营范围，注册资本，股东等信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130026', 'N001244717', 'YEOGIRL YUN--外国人身份证号码NIE', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\西班牙公司- IZENE SPAIN INTERNATIONAL S.L\\IZENE SPAIN INTERNATIONAL, S.L. 注册交接文件', '-', '该文件用来证明Yeogirl Yun的身份信息已在德国马德里警察总署的中央外国人登记处登记。', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130027', 'N001244300', 'IZENE MERKENUNIE-注册文件扫描件', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '-', '公司成立时间：2018年11月26日 注册地址：Gijzelaarsstraat 21 2000 Antwerpen, Belgium  注册资本：18,600 EUR', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130028', 'N001244300', 'IZENE MERKENUNIE注册信息摘录', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '当地语言件+英文翻译；和汇总表成立时间不一致，汇总表需更改（等ERP上线后）', '公司成立时间：2018年11月26日 注册地址：Gijzelaarsstraat 21 2000 Antwerpen, Belgium  注册资本：18,600 EUR', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130029', 'N001244300', 'IZENE-比利时公司成立官方信息', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '-', '公司成立时间：2018年11月26日 注册地址：Gijzelaarsstraat 21 2000 Antwerpen, Belgium  注册资本：18,600 EUR', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130030', 'N001244300', 'ING business card', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '2023年11月到期', 'ING银行的银行卡信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130031', 'N001244300', '比利时公司章程20181205', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130032', 'N001244300', 'Activation vat number Izene', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE Merkenunie BVBA - 比利时公司', '-', 'VAT号：BE0713872290', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130033', 'N001244713', 'certificate of registration注册证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '和汇总表成立时间不一致，汇总表需更改（等ERP上线后）', '公司成立时间：2017年8月18日 ACN：621169195', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130034', 'N001244713', 'ABN', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', 'ABN:54621169195  生效日期：2017年9月1日  ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130035', 'N001244713', 'IZENE AUSTRALIA PTY LTD - Constitution公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130036', 'N001244713', 'ASIC_Corporate key', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', 'ASIC颁发，Corporate Key:75875475', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130037', 'N001244713', 'company profile', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', '公司基本概况表', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130038', 'N001244713', 'consent ot act a director董事证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', '公司申请时签发', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130039', 'N001244713', 'consent to act as a secretary秘书证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', '公司申请时签发', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130040', 'N001244713', 'forms manager - ASIC', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130041', 'N001244713', 'IZENE AUSTRALIA PTY LTD-股东董事名册', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130042', 'N001244713', 'minutes of meeting会议记录', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', '公司成立的基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130043', 'N001244713', 'register of officers', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', '高管登记名册', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130044', 'N001244713', 'Appointment of public officer', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130045', 'N001244713', 'share certificate 2股权证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130046', 'N001244713', 'TFN-Tax file number税号', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE AUSTRALIA PTY LTD-澳洲第一公司', '-', 'TFN:523389190  生效日期：2017年9月1日', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130047', 'N001243200', 'Gshopper Dauphin LLC-公司注册登记证 Certificate of Entry', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/Limited Liability Company Gshopper Dauphin俄罗斯公司', NULL, '公司成立时间：2018.08.10  注册地址：office 69, room 4, premises V, 3rd follr, bld.2, 5/7 Rozhdestvenka str., Moscow, 107031, Russia.  注册资本：15,000 USD 公司营业执照号：1187746820359', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130048', 'N001243200', '税务登记证Tax Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, '-', '税号：1187746820359', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130049', 'N001243200', '公司章程Charter', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, '-', '公司注册资金，经营范围等详细情况', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130050', 'N001243200', '决议Resolution', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130051', 'N001243200', 'POA and application form for director …', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, '核对新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130052', 'N001244700', '营业执照', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', NULL, '公司成立时间：2018年11月12日 注册资本：2000欧元   公司注册地址：25 place de la Madeleine 75008 Paris 公司营业执照号：84380214100013', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130053', 'N001244700', '公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130054', 'N001244700', '商会登记证', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130055', 'N001244700', '增值税登记证书', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', '增值税号：FR54843802141  ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130056', 'N001244700', '广告信件', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130057', 'N001244700', '登报声明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130058', 'N001244700', 'INSEE 证书', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', '生效日期：2018年11月9日', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130059', 'N001244700', '来自法国税务局的调查问卷', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130060', 'N001244700', 'EORI 号码注册文件', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE FRANCE INTERNATIONAL - 法国公司', '证照文件夹未见此文件，仍需后续整理确认', 'EORI号码：FR84380214100013.', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130061', 'N001242800', 'Articles of incorporation iZENEhl B.V.', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130062', 'N001242800', 'iZENEhl B.V. - Business Register Extract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', 'RSIN: 858 194 557 认缴注册资本EUR100 实缴0', '等同于国内的营业执照', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130063', 'N001242800', 'IZENEHL - letter about VAT nr page 2', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130064', 'N001242800', 'IZENEHL - letter about VAT nr', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130065', 'N001242800', 'iZENEhl - Shareholder register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '-', '股东登记册', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130066', 'N001242800', 'VAT No.', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '-', 'VAT Number: NL858194557B01', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130067', 'N001242800', 'contracts', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/iZENEhl B.V. - 荷兰公司', '核对新增。和盈科的服务协议', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130068', 'N001243100', 'IzeneDE International Tech GmbH-Articles of Association', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '公司章程', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130069', 'N001243100', 'General Engagement Terms', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130070', 'N001243100', '2018-01-23 Finanzverwaltung', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130071', 'N001243100', '2019-01-28 Notare Oertel & Thoma（营业执照）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130072', 'N001243100', '2019-03-22 Generalzolldirektion-EORI-IzeneDE', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'EORI：DE987846855307155', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130073', 'N001243100', '190305 AP The official notification of the tax number', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130074', 'N001243100', '190305 AP The official notification of the VAT-ID number for IzeneDE int', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130075', 'N001243100', 'IzeneDE international Tech Gmbh - local tax number', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '当地税号取得时间：2019年2月27日  当地税号：133/5839/2042  ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130076', 'N001243100', 'IzeneDE International Tech GmbH- Amtsgericht Düsseldorf--法院登记信', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '类似于国内的注册证明文件/营业执照', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130077', 'N001243100', 'IzeneDE- VAT', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'VAT:DE322114749', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130078', 'N001243100', 'Notarization  - 公证书(上）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '公司成立时间：2017年11月27日  注册资本：25,000欧元  注册地址：Immermannstr. 13 D-40210 Dusseldorf ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130079', 'N001243100', 'Notarization  - 公证书(下）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130080', 'N001244701', 'IZENE HOLDING PYT.LTD-Share Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', NULL, 'ACN:629420444  注册地址：401/315 New South Head Road Double Bay, NSW 2028  股东为IZENE AUSTRALIA PTY LTD ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130081', 'N001244701', 'ABN-IZENE Holding Pty. Ltd.-更新', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', 'ABN：13629420444  生效日期：2018年10月16日  ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130082', 'N001244701', 'certificate_of_registration_IZENE HOLDING (Yafeng Cai - 12_10_2018 14_26_53) 公司营业执照', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130083', 'N001244701', 'company application form 公司申请表格', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130084', 'N001244701', 'Company structure 公司架构', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130085', 'N001244701', 'Company_Constitution 公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130086', 'N001244701', 'TFN-IZENE Holding Pty. Ltd.（税号）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', 'TFN：570797224', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130087', 'N001244701', '澳洲公司IZENE HOLDING PTY LTD--GST', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE HOLDING PTY.LTD - 澳洲第二公司', '-', 'GST：13629420444   生效日期：2018年10月16日', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130088', 'N001244200', 'HRB法院登记处--登记材料', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '注册地址：Immermannstr.13 D-40210 Dusseldorf   注册资本：25,000 EUR ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130089', 'N001244200', 'Notarization', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '公证书', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130090', 'N001244200', 'service agreement --- EGSZ & Zaario', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130091', 'N001244200', 'Contract--ABD&Zaario', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130092', 'N001244200', '股东名册', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '股东：Yeogirl Yun', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130093', 'N001244200', '德国Zaario公司信件（法务保管）20181113', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130094', 'N001244712', 'BTW税号', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, 'BTW：NL857967071B01', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130095', 'N001244712', 'BSN', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, 'BSN：468465224', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130096', 'N001244712', 'iZENEeu- Memorandum of Association-公司章程(荷兰语）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130097', 'N001244712', 'iZENEeu-kvk-公司成立证明（荷兰语）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130098', 'N001244712', 'izene-公司章程（英文）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130099', 'N001244712', 'izene-kvk（公司成立证明）（英文）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEeu International Tech B.V - 荷兰-飞鸿个人公司', NULL, '注册资本：100欧元  公司成立时间：2017年9月26日  股东：Yeogirl Yun   注册号：000038020742  注册地址：Olympisch Stadion 24, 1076DE Amsterdam RSIN:857967071  CCI number:69684383', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130100', 'N001242900', 'izene us 注册证书', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', NULL, '公司成立时间：2017年2月14日  注册地址：1608 E AYRE ST, WILMINGTON, NEW CASTLE, U.S.   注册资本：1500美元 注册号：6322461', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130101', 'N001242900', 'Coporate Bylaws - 公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130102', 'N001242900', 'IZENE US INC - 2017annual franchise tax report', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130103', 'N001242900', 'register of director', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', '董事：Yeogirl Yun', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130104', 'N001242900', 'share certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', '股东：iZENEhk, Limited   注册资本：1500美元', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130105', 'N001242900', 'Share Certificate股权认购书（空）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130106', 'N001242900', 'Stock Transfer Ledger Sheet - 认股表', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130107', 'N001242900', 'Taxpayer Number', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', 'Taxpayer Identification Number: 32-0539489', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130108', 'N001242900', '2017income tax return', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130109', 'N001242900', 'Apostille - 特拉华州政府公证', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', '该文件内容系对公司注册证书的公证', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130110', 'N001242900', 'certificate of incumbency', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '-', '在职证明', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130111', 'N001242900', 'business certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '核对新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130112', 'N001242900', 'certificate of incorporation with apostille', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '核对新增', '公证文件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130113', 'N001242900', 'izene2018年度报税表', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件/IZENE US INC - 美国公司', '核对新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130114', 'N001244720', '[IZENE EU INC LIMITED] - certificate of incorporation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE?EU?INC?LIMITED - 爱尔兰公司', '-', '公司成立时间：2017年5月2日   注册号：603391 ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130115', 'N001244720', '[IZENE EU INC LIMITED] - Form of Constitution', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE?EU?INC?LIMITED - 爱尔兰公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130116', 'N001244720', 'certificate of share', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE?EU?INC?LIMITED - 爱尔兰公司', '-', '股东：iZENEhk, Limited 注册成本：100欧元', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130117', 'N001244720', 'minutes of meeting 20170502', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\IZENE?EU?INC?LIMITED - 爱尔兰公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130118', 'N001241100', 'A series of Share Certificates', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', NULL, NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130119', 'N001241100', 'Certificate of Good Standing-26 Mar 2013', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', NULL, '良好存续证明', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130120', 'N001241100', 'Certificate of Incorporation_iZENEtech', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '和汇总表成立时间不一致，汇总表需更改（等ERP上线后）', '公司成立时间：2011年9月30日', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130121', 'N001241100', 'Certificate of Incumbency dd 6 Sep 2017 (ICSL)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '在职证明', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130122', 'N001241100', 'iZENEtech - Certificate of Good Standing dd 7 Nov 17', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '良好存续证明', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130123', 'N001241100', 'iZENEtech - Certificate of Good Standing dd 28 May 18', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '良好存续证明', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130124', 'N001241100', 'iZENEtech - Memorandum and Articles of Association -8 Apr 2013', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '2013年4月8日公司章程', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130125', 'N001241100', 'iZENEtech - Memorandum and Articles of Association -16 Feb 2012', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '2012年2月16日公司章程', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130126', 'N001241100', 'iZENEtech - Memorandum and Articles of Association -18 May 2015', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '2015年5月18日公司章程', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130127', 'N001241100', 'iZENEtech - Memorandum and Articles of Association -21 May 2015', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', '2015年5月21日公司章程', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130128', 'N001241100', 'Register?of?Directors?& Officers-?May.12.2017', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130129', 'N001241100', 'Written Resolusion-16 Feb 2012', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130130', 'N001241100', 'Yeogirl Yun 开曼股权证明-原件', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130131', 'N001241100', '股东名册', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130132', 'N001241100', '其他资料', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\iZENEtech,Inc - 开曼公司', '共享盘中仍有很多资料待重新整理（如OAK，wisenut及其他融资/VIE材料）', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130133', 'N001244702', '投资登记证Investment Registration Certificate -VN（越南语）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130134', 'N001244702', '投资登记证Investment Registration Certificate（英文）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '-', '投资登记证号：3242670993；第一次认证时间：2019年2月22日', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130135', 'N001244702', '税务事项通知书（iZENE)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130136', 'N001244702', '商业登记证Buisness Registration Certificate- VN（越南语）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130137', 'N001244702', '商业登记证（英文版）Buisness Registration Certificate- EN（英文）', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', NULL, '公司注册号： 0315577143；  注册时间：2019年3月25日；公司法定代表人： NGUYEN THI LAN HUONG、YEOGIRL YUN ；   ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130138', 'N001244702', '开户证明-HOP DONG KIEM DE NGHI MO TAI KHOAN - CTY IZENE', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '-', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130139', 'N001244703', '卢森堡公司公示文本', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130140', 'N001244703', '卢森堡公司BDO', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130141', 'N001244703', '卢森堡公司商业登记证', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130142', 'N001244703', '卢森堡公司BDO', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130143', 'N001244703', '反洗钱声明TEMPLATE CF CORP_AML De?claration_ENG _(final)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130144', 'N001244703', 'Entities Tax Self-Cerification Form', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\离职交接\\连晋离职交接材料\\工作交接-LJ2伯玉\\越南公司IZENE VIETNAM INTERNATIONAL CO., LTD\\IZENE VIETNAM INTERNATIONAL COMPANY LIMITED - 越南公司\n（及分散在共享盘各处的资料）', '此次资料整理新增', '共享盘中仍有非常多的资料待重新整理及核对', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130145', 'N001244706', 'Certificate of Formation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '2019年6月4日提交注册；公司名称、地址等基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130146', 'N001244706', 'Opening Agreement', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '协议签署件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130147', 'N001244706', 'Membership Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '样张及空白张', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130148', 'N001244706', 'Record of Certificates Issued and Transferred', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130149', 'N001244706', 'Membership Listing/ Certificate Register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130150', 'N001244706', 'Notice of EIN (Employer Identification Number)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 84-1996172', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130151', 'N001244706', 'Statutory Agent Representation Contract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '与代理（Paracorp Incorporated）签署的注册服务协议', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130152', 'N001244704', 'Certificate of Formation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '2019年5月10日提交注册；公司名称、地址等基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130153', 'N001244704', 'Opening Agreement', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '协议签署件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130154', 'N001244704', 'Membership Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '样张及空白张', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130155', 'N001244704', 'Record of Certificates Issued and Transferred', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130156', 'N001244704', 'Membership Listing/ Certificate Register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130157', 'N001244704', 'Notice of EIN (Employer Identification Number)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 84-1832117', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130158', 'N001244704', 'Statutory Agent Representation Contract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '与代理（Paracorp Incorporated）签署的注册服务协议 ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130159', 'N001244705', 'Certificate of Formation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '2019年5月10日提交注册；公司名称、地址等基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130160', 'N001244705', 'Opening Agreement', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '协议签署件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130161', 'N001244705', 'Membership Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '样张及空白张', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130162', 'N001244705', 'Record of Certificates Issued and Transferred', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130163', 'N001244705', 'Membership Listing/ Certificate Register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130164', 'N001244705', 'Notice of EIN (Employer Identification Number)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 84-1845532', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130165', 'N001244705', 'Statutory Agent Representation Contract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '与代理（Paracorp Incorporated）签署的注册服务协议', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130166', 'N001244721', 'Certificate of Formation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '2019年7月23日提交注册；公司名称、地址等基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130167', 'N001244721', 'Opening Agreement', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '协议签署件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130168', 'N001244721', 'Membership Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '样张及空白张', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130169', 'N001244721', 'Record of Certificates Issued and Transferred', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130170', 'N001244721', 'Membership Listing/ Certificate Register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130171', 'N001244721', 'Notice of EIN (Employer Identification Number)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 84-2522408', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130172', 'N001244721', 'Statutory Agent Representation Contract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '与代理（Paracorp Incorporated）签署的注册服务协议 ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130173', 'N001244722', 'Certificate of Formation', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '2019年7月23日提交注册；公司名称、地址等基本信息', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130174', 'N001244722', 'Opening Agreement', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '协议签署件', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130175', 'N001244722', 'Membership Certificate', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '样张及空白张', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130176', 'N001244722', 'Record of Certificates Issued and Transferred', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130177', 'N001244722', 'Membership Listing/ Certificate Register', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130178', 'N001244722', 'Notice of EIN (Employer Identification Number)', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 84-2509726', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130179', 'N001244722', 'Statutory Agent Representation Contract', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '与代理（Paracorp Incorporated）签署的注册服务协议 ', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130180', 'N001244723', 'bylaws 公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130181', 'N001244723', 'certificate of incorporation 注册证书', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '成立于2019年9月3日,注册资本5000美元', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130182', 'N001244723', 'certificate of incumbency 代理证明信', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130183', 'N001244723', 'certificate of shares（iZENEhk）持股证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130184', 'N001244723', 'minutes of first meeting 公司成立会议记录', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130185', 'N001244723', 'stock transfer leger sheet 股票账目', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130186', 'N001244723', 'tax application form 税号申请表', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN: 38-4129204', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130187', 'N001244723', '空白 持股证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130188', 'N001244723', 'YK 公司秘书服务卡', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130192', 'N001244724', 'certificate of shares（iZENEhk）持股证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130193', 'N001244724', 'minutes of first meeting 公司成立会议记录', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130194', 'N001244724', 'stock transfer leger sheet 股票账目', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130195', 'N001244724', 'tax application form 税号申请表', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', 'EIN：32-0611100', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130196', 'N001244724', '空白 持股证明', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '空白无内容', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130189', 'N001244724', 'bylaws 公司章程', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130190', 'N001244724', 'certificate of incorporation 注册证书', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', '成立于2019年9月3日,注册资本5000美元', 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130191', 'N001244724', 'certificate of incumbency 代理证明信', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37'),
('GSZZ201912130197', 'N001244724', 'YK 公司秘书服务卡', '', '', '', NULL, 0, NULL, NULL, NULL, NULL, '\\\\172.16.1.161\\legal\\新-公司证照-资质-章程文件\\DE- 注册美国新公司（7家）', '此次资料整理新增', NULL, 'Habit Wang','2019-12-13 14:06:37', 'Habit Wang', '2019-12-13 14:06:37')";
        $Db->startTrans();
        $date = date('Y-m-d H:i:s', time());
        $res  = $Db->execute("truncate table `tb_crm_qualification`;");
        $res  = $Db->execute($sql);
        $Db->commit();
        echo $res;
        die;
    }

    public function updateB2bWarehouseNum()
    {
        $Db  = new Model();
        $res = $Db->execute("UPDATE tb_b2b_warehouse_list SET WAREHOUSEING_NUM = 37 WHERE ID = 1619");
        echo $res;
        die;
    }

    //删除万邑通物流方式
    public function deleteAreaConfiguration()
    {
        $Db = new Model();
        //偏远地区万邑通配置
        $area_configuration = $Db->table('tb_op_area_configuration')->where(['logistics_company' => 'N000708200'])->select();
        Logs($area_configuration, 'update_area_configuration');
        $res = $Db->execute("DELETE from `tb_op_area_configuration` WHERE `logistics_company` = 'N000708200'");
        die($res);
    }

    //修复出库单号批次
    public function updateWarehouseList()
    {
        $Db        = new Model();
        $ship_list = $Db->table('tb_b2b_ship_list')->where(['ID' => '2659'])->find();
        Logs($ship_list, 'updateWarehouseList');
        $res = $Db->execute("UPDATE `tb_b2b_ship_list` SET `out_bill_id` = 'XSC2019111500016' WHERE `ID` = '2659'");
        var_dump($res);
    }

    public function deleteExtend()
    {
        $Db       = new Model();
        $order_id = I('order_id');
        if (empty($order_id)) {
            $order_id = 'P577098976026254291';
        }
        $sql = "DELETE tb_op_order_extend FROM tb_op_order_extend WHERE  order_id = '{$order_id}'";
        $res = $Db->execute($sql);
        var_dump($res);
    }

    public function addStorePlatVal()
    {
        $Model    = new Model();
        $response = $Model->table('tb_ms_store')
            ->field(['tb_ms_store.ID', 'tb_ms_cmn_cd.CD_VAL'])
            ->join('left join tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_ms_store.PLAT_CD')
            ->select();
        foreach ($response as $value) {
            $temp_where['ID']         = $value['ID'];
            $temp_save['plat_cd_val'] = strtolower(str_replace('-', '.', $value['CD_VAL']));
            $save_response[]          = $Model->table('tb_ms_store')->where($temp_where)->save($temp_save);
        }
        var_dump($save_response);
    }

    public function updateStorePlatVal()
    {
        $Model                            = new Model();
        $where['tb_ms_store.plat_cd_val'] = 'amazon';
        $response                         = $Model->table('tb_ms_store')
            ->field(['tb_ms_store.ID', 'tb_ms_store.MERCHANT_ID', 'tb_ms_cmn_cd.CD_VAL'])
            ->join('left join tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_ms_store.PLAT_CD')
            ->where($where)
            ->select();
        foreach ($response as $value) {
            $temp_where['ID']         = $value['ID'];
            $value['MERCHANT_ID']     = preg_replace('/([\x80-\xff]*)/i', '', $value['MERCHANT_ID']);
            $value['MERCHANT_ID']     = str_replace('(', '', $value['MERCHANT_ID']);
            $value['MERCHANT_ID']     = str_replace(')', '', $value['MERCHANT_ID']);
            $value['MERCHANT_ID']     = str_replace('--', '-', $value['MERCHANT_ID']);
            $value['MERCHANT_ID']     = trim($value['MERCHANT_ID'], '-');
            $temp_save['plat_cd_val'] = strtolower(str_replace('-', '.', $value['MERCHANT_ID']));
            $save_response[]          = $Model->table('tb_ms_store')->where($temp_where)->save($temp_save);
        }
        var_dump($save_response);
    }

    public function invoiceInfoFix()
    {
        $Db = new Model();
        $Db->startTrans();
        /*$Db->execute("UPDATE `tb_pur_goods_information` SET `invoiced_money`=346.08 WHERE ( `information_id` = '32029' ) AND ( `sku_information` = '8100147107' ) ");
        $Db->execute("UPDATE `tb_pur_goods_information` SET `invoiced_money`=259.56 WHERE ( `information_id` = '32030' ) AND ( `sku_information` = '8100147104' ) ");
        $Db->execute("UPDATE `tb_pur_goods_information` SET `invoiced_money`=44.75 WHERE ( `information_id` = '32037' ) AND ( `sku_information` = '8100213903' ) ");
        $Db->execute("UPDATE `tb_pur_goods_information` SET `invoiced_money`=80.70 WHERE ( `information_id` = '32038' ) AND ( `sku_information` = '8100213906' ) ");

        $Db->execute("UPDATE `tb_pur_invoice_goods` SET `invoice_money`=346.08 WHERE ( `information_id` = '32029' )");
        $Db->execute("UPDATE `tb_pur_invoice_goods` SET `invoice_money`=259.56 WHERE ( `information_id` = '32030' )");
        $Db->execute("UPDATE `tb_pur_invoice_goods` SET `invoice_money`=44.75 WHERE ( `information_id` = '32037' )");
        $Db->execute("UPDATE `tb_pur_invoice_goods` SET `invoice_money`=80.70 WHERE ( `information_id` = '32038' )");*/
        $Db->execute("UPDATE `tb_pur_invoice` SET `invoice_money`=1641.00 WHERE ( `id` = '4809' )");
        $Db->commit();
        echo 'success';
    }

    //修复供应商抵扣金账户
    public function updateSupplierDeduction()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_pur_deduction SET supplier_id = '83',over_deduction_amount = over_deduction_amount -2780,unused_deduction_amount = unused_deduction_amount - 2780 WHERE id = 51");
        $Db->commit();
        echo $res;
        die;
    }

    public function addSupplierDeduction()
    {
        $Db = new Model();
        $Db->startTrans();
        $data = [
            'supplier_id'             => '1769',
            'our_company_cd'          => 'N001240500',
            'our_company_name'        => '载鸿贸易（上海）有限公司（Zaihong Trade (Shanghai) Co., Ltd.）',
            'supplier_name_cn'        => '上海亮淘实业有限公司',
            'supplier_name_en'        => '',
            'deduction_currency_cd'   => 'N000590300',
            'over_deduction_amount'   => '2780',
            'used_deduction_amount'   => '0',
            'unused_deduction_amount' => '2780',
            'created_by'              => 'Lily.Ji',
            'updated_by'              => 'Lily.Ji',
        ];
        $res  = M('deduction', 'tb_pur_')->add($data);
        if ($res) {
            $res2 = $Db->execute("UPDATE tb_pur_deduction_detail SET deduction_id = {$res} WHERE id = 589");
        }
        $Db->commit();
        echo $res;
        echo $res2;
        die;
    }

    public function updateSkuPrice()
    {
        $Db           = new Model();
        $pms_database = PMS_DATABASE;
        $res          = $Db->execute("update {$pms_database}.product_price set discount = 0.05, sale_price = real_price * 1.05 where sale_price = '' or sale_price is null");
        echo $res;
        die;
    }

    public function runtimeDirectory()
    {
        $log_file_path = __DIR__ . '../../../Runtime/Logs/';
        if (!is_dir($log_file_path)) {
            mkdir($log_file_path);
        }
        $cache_file_path = __DIR__ . '../../../Runtime/Cache/';
        if (!is_dir($cache_file_path)) {
            mkdir($cache_file_path);
        }
        $temp_file_path = __DIR__ . '../../../Runtime/Temp/';
        if (!is_dir($temp_file_path)) {
            mkdir($temp_file_path);
        }
    }

    public function updateBatchTotalInventory()
    {
        $DB  = new Model();
        $sql = "UPDATE tb_wms_batch 
SET tb_wms_batch.total_inventory = tb_wms_batch.occupied + tb_wms_batch.locking + tb_wms_batch.available_for_sale_num 
WHERE
    tb_wms_batch.total_inventory != tb_wms_batch.occupied + tb_wms_batch.locking + tb_wms_batch.available_for_sale_num 
    AND tb_wms_batch.create_time > '2020-03-01'";
        $res = $DB->execute($sql);
        var_dump($res);
    }

    /**
     * 未计算取消出库单（@todo）
     */
    public function updateBatchForCreateDateAuthentic()
    {
        $DB  = new Model();
        $sql = "UPDATE tb_wms_batch,tb_wms_stream,
(
    SELECT
        temp_occupy.*,
        sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) AS sum_request_occupy_num,
        temp_occupy.warehouse_number - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) AS 'now_show_total_inventory',
        temp_occupy.warehouse_number - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) - temp_occupy.sum_occupy AS 'now_show_available_for_sale_num',
        temp_occupy.sum_occupy AS 'now_show_occupied',
        (
        temp_occupy.warehouse_number - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) )) - temp_occupy.total_inventory AS 'total_inventory_diff',
        ( temp_occupy.warehouse_number - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) - temp_occupy.sum_occupy ) - temp_occupy.available_for_sale_num AS 'avalilable_for_sale_num_diff',
        temp_occupy.sum_occupy - temp_occupy.occupied AS 'occupied_diff' 
    FROM
        (
        SELECT
            tb_wms_batch.id AS batch_id,
            tb_wms_batch.sku_id,
            tb_wms_batch.all_total_inventory,
            tb_wms_batch.total_inventory,
            tb_wms_batch.occupied,
            tb_wms_batch.locking,
            tb_wms_batch.available_for_sale_num,
            tb_wms_batch.purchase_order_no,
            tb_wms_batch.stream_id,
            tb_wms_stream.send_num,
            sum(
            IFNULL( tb_wms_batch_order.occupy_num, 0 )) AS sum_occupy,
            tb_pur_warehouse_goods.warehouse_number,
            GROUP_CONCAT( tb_wms_batch_order.ORD_ID ) 
        FROM
            ( tb_wms_batch, tb_wms_bill, tb_pur_warehouse, tb_pur_warehouse_goods, tb_pur_ship_goods, tb_pur_goods_information )
            LEFT JOIN tb_wms_batch_order ON tb_wms_batch_order.batch_id = tb_wms_batch.id 
            AND tb_wms_batch_order.use_type = 1
            LEFT JOIN tb_wms_stream ON tb_wms_stream.id = tb_wms_batch.stream_id 
        WHERE
            tb_wms_batch.create_time >= '2020-03-10 00:00:00' 
            AND tb_wms_batch.create_time <= '2020-03-18 00:00:00' 
            AND tb_wms_batch.vir_type = 'N002440100' 
            AND tb_wms_bill.id = tb_wms_batch.bill_id 
            AND tb_pur_warehouse.warehouse_code = tb_wms_bill.bill_id 
            AND tb_pur_warehouse.id = tb_pur_warehouse_goods.warehouse_id 
            AND tb_pur_warehouse_goods.ship_goods_id = tb_pur_ship_goods.id 
            AND tb_pur_ship_goods.information_id = tb_pur_goods_information.information_id 
            AND tb_pur_goods_information.sku_information = tb_wms_batch.SKU_ID 
        GROUP BY
            tb_wms_batch.id 
        ) AS temp_occupy
        LEFT JOIN tb_wms_batch_order ON tb_wms_batch_order.batch_id = temp_occupy.batch_id 
        AND tb_wms_batch_order.use_type != 1 
        AND tb_wms_batch_order.use_type != 3
    WHERE
        1 = 1 
    GROUP BY
        temp_occupy.batch_id 
    ) AS temp_now 
    SET tb_wms_batch.total_inventory = temp_now.now_show_total_inventory,
    tb_wms_batch.available_for_sale_num = temp_now.now_show_available_for_sale_num,
    tb_wms_batch.occupied = temp_now.now_show_occupied,
    tb_wms_stream.send_num = temp_now.warehouse_number 
WHERE
    temp_now.now_show_total_inventory >= 0 
    AND temp_now.now_show_available_for_sale_num >= 0 
    AND ( ( temp_now.total_inventory_diff != 0 OR temp_now.avalilable_for_sale_num_diff != 0 OR temp_now.occupied_diff != 0 ) OR tb_wms_stream.send_num != temp_now.warehouse_number ) 
    AND tb_wms_batch.id = temp_now.batch_id 
    AND tb_wms_stream.id = temp_now.stream_id";
        $res = $DB->execute($sql);
        var_dump($res);
    }

    /**
     * 未计算取消出库单（@todo）
     */
    public function updateBatchForCreateDateDefective()
    {
        $DB  = new Model();
        $sql = "UPDATE tb_wms_batch,
(
    SELECT
        temp_occupy.*,
        sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) AS sum_request_occupy_num,
        temp_occupy.warehouse_number_broken - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) AS 'now_show_total_inventory',
        temp_occupy.warehouse_number_broken - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) - temp_occupy.sum_occupy AS 'now_show_available_for_sale_num',
        temp_occupy.sum_occupy AS 'now_show_occupied',
        (
        temp_occupy.warehouse_number_broken - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) )) - temp_occupy.total_inventory AS 'total_inventory_diff',
        ( temp_occupy.warehouse_number_broken - sum( IFNULL( tb_wms_batch_order.request_occupy_num, IFNULL( tb_wms_batch_order.occupy_num, 0 )) ) - temp_occupy.sum_occupy ) - temp_occupy.available_for_sale_num AS 'avalilable_for_sale_num_diff',
        temp_occupy.sum_occupy - temp_occupy.occupied AS 'occupied_diff' 
    FROM
        (
        SELECT
            tb_wms_batch.id AS batch_id,
            tb_wms_batch.sku_id,
            tb_wms_batch.all_total_inventory,
            tb_wms_batch.total_inventory,
            tb_wms_batch.occupied,
            tb_wms_batch.locking,
            tb_wms_batch.available_for_sale_num,
            tb_wms_batch.purchase_order_no,
            sum(
            IFNULL( tb_wms_batch_order.occupy_num, 0 )) AS sum_occupy,
            tb_pur_warehouse_goods.warehouse_number_broken,
            GROUP_CONCAT( tb_wms_batch_order.ORD_ID ) 
        FROM
            ( tb_wms_batch, tb_wms_bill, tb_pur_warehouse, tb_pur_warehouse_goods, tb_pur_ship_goods, tb_pur_goods_information )
            LEFT JOIN tb_wms_batch_order ON tb_wms_batch_order.batch_id = tb_wms_batch.id 
            AND tb_wms_batch_order.use_type = 1 
        WHERE
            tb_wms_batch.create_time >= '2020-03-10 00:00:00' 
            AND tb_wms_batch.create_time <= '2020-03-18 00:00:00' 
            AND tb_wms_batch.vir_type = 'N002440400' 
            AND tb_wms_bill.id = tb_wms_batch.bill_id 
            AND tb_pur_warehouse.warehouse_code = tb_wms_bill.bill_id 
            AND tb_pur_warehouse.id = tb_pur_warehouse_goods.warehouse_id 
            AND tb_pur_warehouse_goods.ship_goods_id = tb_pur_ship_goods.id 
            AND tb_pur_ship_goods.information_id = tb_pur_goods_information.information_id 
            AND tb_pur_goods_information.sku_information = tb_wms_batch.SKU_ID 
        GROUP BY
            tb_wms_batch.id 
        ) AS temp_occupy
        LEFT JOIN tb_wms_batch_order ON tb_wms_batch_order.batch_id = temp_occupy.batch_id 
        AND tb_wms_batch_order.use_type != 1 
        AND tb_wms_batch_order.use_type != 3
    WHERE
        1 = 1 
    GROUP BY
        temp_occupy.batch_id 
    ) AS temp_now 
    SET tb_wms_batch.total_inventory = temp_now.now_show_total_inventory,
    tb_wms_batch.available_for_sale_num = temp_now.now_show_available_for_sale_num,
    tb_wms_batch.occupied = temp_now.now_show_occupied 
WHERE
    temp_now.now_show_total_inventory >= 0 
    AND temp_now.now_show_available_for_sale_num >= 0 
    AND ( temp_now.total_inventory_diff != 0 OR temp_now.avalilable_for_sale_num_diff != 0 OR temp_now.occupied_diff != 0 ) 
    AND tb_wms_batch.id = temp_now.batch_id";
        $res = $DB->execute($sql);
        var_dump($res);
    }

    public function adjustBatchOccupancy()
    {
        $DB = new Model();
        $id = I('id');
        if (empty($id)) {
            return false;
        }
        $sql = "UPDATE (
    SELECT
        tb_wms_batch_order.ID,
        tb_wms_batch_order.ORD_ID,
        tb_wms_batch_order.use_type,
        tb_wms_batch_order.batch_id,
        tb_wms_batch_order.SKU_ID,
        tb_wms_batch_order.sale_team_code,
        tb_wms_batch_order.occupy_num,
        tb_wms_batch.id AS new_batch_id,
        tb_wms_batch.available_for_sale_num
    FROM
        tb_wms_batch_order
    LEFT JOIN tb_wms_batch ON tb_wms_batch.SKU_ID = tb_wms_batch_order.SKU_ID
    AND tb_wms_batch.sale_team_code = tb_wms_batch_order.sale_team_code
    AND tb_wms_batch.id != tb_wms_batch_order.batch_id
    AND tb_wms_batch.available_for_sale_num >= tb_wms_batch_order.occupy_num
    LEFT JOIN tb_wms_bill ON tb_wms_bill.id = tb_wms_batch.bill_id
    WHERE
        tb_wms_batch_order.ID = {$id}
    AND tb_wms_batch_order.use_type IN (1,8)
    AND tb_wms_bill.warehouse_id = tb_wms_batch_order.delivery_warehouse
    GROUP BY
        tb_wms_batch_order.ID
) AS temp_batch_order,
 tb_wms_batch,
 tb_wms_batch_order
SET tb_wms_batch.occupied = tb_wms_batch.occupied + temp_batch_order.occupy_num,
 tb_wms_batch.available_for_sale_num = tb_wms_batch.available_for_sale_num - temp_batch_order.occupy_num,
 tb_wms_batch_order.batch_id = temp_batch_order.new_batch_id
WHERE
    tb_wms_batch.id = temp_batch_order.new_batch_id
AND tb_wms_batch_order.ID = temp_batch_order.ID        ";
        $DB->execute($sql);
    }

    public function adjustBatchOut()
    {
        $DB = new Model();
        $id = I('id');
        if (empty($id)) {
            return false;
        }
        $sql = "UPDATE (
    SELECT
        tb_wms_batch_order.ID,
        tb_wms_batch_order.ORD_ID,
        tb_wms_batch_order.use_type,
        tb_wms_batch_order.batch_id,
        tb_wms_batch_order.SKU_ID,
        tb_wms_batch_order.sale_team_code,
        tb_wms_batch_order.request_occupy_num,
        tb_wms_batch.id AS new_batch_id,
        tb_wms_batch.available_for_sale_num,
        out_stream.id AS stream_id 
    FROM
        tb_wms_batch_order
        LEFT JOIN tb_wms_batch ON tb_wms_batch.SKU_ID = tb_wms_batch_order.SKU_ID 
        AND tb_wms_batch.sale_team_code = tb_wms_batch_order.sale_team_code 
        AND tb_wms_batch.id != tb_wms_batch_order.batch_id 
        AND tb_wms_batch.available_for_sale_num >= tb_wms_batch_order.request_occupy_num
        LEFT JOIN tb_wms_bill ON tb_wms_bill.id = tb_wms_batch.bill_id --
        LEFT JOIN tb_wms_bill AS out_bill ON ( out_bill.link_bill_id = tb_wms_batch_order.ORD_ID OR out_bill.link_b5c_no = tb_wms_batch_order.ORD_ID )
        LEFT JOIN tb_wms_stream AS out_stream ON out_stream.bill_id = out_bill.id 
        AND out_stream.GSKU = tb_wms_batch_order.SKU_ID 
        AND out_stream.send_num = tb_wms_batch_order.request_occupy_num 
    WHERE
        tb_wms_batch_order.ID = {$id} 
        AND tb_wms_batch_order.use_type != 1
        AND tb_wms_batch_order.use_type != 3 
        AND tb_wms_bill.warehouse_id = tb_wms_batch_order.delivery_warehouse 
        AND out_stream.id IS NOT NULL
    GROUP BY
        tb_wms_batch_order.ID 
    ) AS temp_batch_order,
    tb_wms_batch,
    tb_wms_batch_order,
    tb_wms_stream 
    SET tb_wms_batch.total_inventory = tb_wms_batch.total_inventory - temp_batch_order.request_occupy_num,
    tb_wms_batch.available_for_sale_num = tb_wms_batch.available_for_sale_num - temp_batch_order.request_occupy_num,
    tb_wms_batch_order.batch_id = temp_batch_order.new_batch_id,
    tb_wms_stream.batch = temp_batch_order.new_batch_id 
WHERE
    tb_wms_batch.id = temp_batch_order.new_batch_id 
    AND tb_wms_batch_order.ID = temp_batch_order.ID 
    AND tb_wms_stream.id = temp_batch_order.stream_id ";
        $DB->execute($sql);
    }

    public function updateBatchError()
    {
        try {
            $id                     = I('id');
            $total_inventory        = I('total_inventory');
            $occupied               = I('occupied');
            $available_for_sale_num = I('available_for_sale_num');
            if (empty($id) || $total_inventory < 0 || $occupied < 0 || $available_for_sale_num < 0) {
                echo 'request error';
                return null;
            }
            if (($occupied + $available_for_sale_num) > $total_inventory) {
                echo 'request error1';
                return null;
            }
            $DB = new Model();
            $DB->startTrans();
            $sql = "UPDATE tb_wms_batch SET total_inventory = {$total_inventory},  occupied = {$occupied},available_for_sale_num = {$available_for_sale_num} WHERE  id = {$id}";
            Logs($sql);
            echo $sql . PHP_EOL;
            echo $DB->execute($sql);
            $DB->commit();
        } catch (Exception $exception) {
            $DB->rollback();
        }

    }

    public function updateBillIsShow()
    {
        try {
            $id      = I('id');
            $is_show = I('is_show');
            if (empty($id) || empty($is_show)) {
                echo 'request error';
                return null;
            }
            $DB = new Model();
            $DB->startTrans();
            $sql = "UPDATE tb_wms_bill SET is_show = {$is_show} WHERE  id = {$id}";
            Logs($sql);
            echo $sql . PHP_EOL;
            echo $DB->execute($sql);
            $DB->commit();
        } catch (Exception $exception) {
            $DB->rollback();
        }
    }

    public function updateStreamError()
    {

        try {
            $id       = I('id');
            $batch    = I('batch');
            $send_num = I('send_num');
            if (empty($id) || empty($batch) || $send_num < 0) {
                echo 'request error';
                return null;
            }
            $DB = new Model();
            $DB->startTrans();
            $sql = "UPDATE tb_wms_stream SET batch = {$batch},send_num = {$send_num} WHERE  id = {$id}";
            Logs($sql);
            echo $sql . PHP_EOL;
            echo $DB->execute($sql);
            $DB->commit();
        } catch (Exception $exception) {
            $DB->rollback();
        }
    }

    public function updateBatchOrderBatchId()
    {
        // @todo id 1999525 batch_id 285714
        try {
            $id       = I('id');
            $batch_id = I('batch_id');
            if (empty($id) || empty($batch_id)) {
                echo 'request error';
                return null;
            }
            $DB = new Model();
            $DB->startTrans();
            $sql = "UPDATE tb_wms_batch_order SET batch_id = {$batch_id} WHERE  id = {$id}";
            Logs($sql);
            echo $sql . PHP_EOL;
            echo $DB->execute($sql);
            $DB->commit();
        } catch (Exception $exception) {
            $DB->rollback();
        }
    }

    //冻结gp用户
    public function freezeCust()
    {
        $model = new Model();
        $sql   = "UPDATE tb_ms_thrd_cust 
        SET `status` = 2 
        WHERE
            CUST_EML IN (
                SELECT DISTINCT(USER_EMAIL)
                FROM
                    tb_op_order 
                WHERE
                    ORDER_ID IN (
                    'GSL120034223',
                    'GSL140034239',
                    'GSL280034207',
                    'GSL280034218',
                    'GSL270034264',
                    'GSL280034214',
                    'GSL290034321',
                    'GSL290034322',
                    'GSL270034266',
                    'GSL280034204',
                    'GSJ260034201',
                    'GSI190034210',
                    'GSD300034207',
                    'GSE280034201',
                    'GSE240034215',
                    'GSE240034211',
                    'GSE190034208',
                    'GSE290034258',
                    'GSF150034206',
                    'GSF160034216',
                    'GSF160034208',
                    'GSF150034202',
                    'GSE230034301',
                    'GSE310034234',
                    'GSD250034339',
                    'GSE110034402',
                    'GSE200034333',
                    'GSE070034219',
                    'GSE070034214',
                    'GSE070034215',
                    'GSE190034207',
                    'GSE190034204',
                    'GSE070034209',
                    'GSE070034208',
                    'GSE070034205',
                    'GSD250034265',
                    'GSD250034266',
                    'GSE070034225'
                    ) 
            )";
        return $model->query($sql);
    }

    public function updateGpRefundChannel()
    {
        $Db = new Model();
        $Db->startTrans();
        $res = $Db->execute("UPDATE tb_op_order_refund_detail SET refund_channel_cd = 'N001000313' WHERE id = 139");
        $Db->commit();
        echo $res;
        die;
    }

    //采购币种-原始金额数据修复
    public function updateStreamPrice()
    {
        $start     = I('start');
        $end       = I('end');
        $bill_type = I('bill_type');
        if (empty($bill_type)) {
            die('not bill_type');
        }
        $stream = M('wms_stream', 'tb_');
        $stream->startTrans();
        $model = new Model();
        $sql   = "SELECT
            ws.id,
            wb.bill_type,
            ws.unit_price,
            ws.currency_id,
            ws.unit_price_origin,
            ws.pur_storage_date,
            ba.create_time,
            ws.bill_id,
            ws.batch,
            ws.GSKU,
            ba.batch_code 
        FROM
            tb_wms_bill wb
            LEFT JOIN tb_wms_stream ws ON wb.id = ws.bill_id
            LEFT JOIN tb_wms_batch ba ON wb.id = ba.bill_id 
        WHERE
            wb.bill_type = '{$bill_type}' 
            AND ( ws.unit_price_origin IS NULL OR ( ws.unit_price_origin <= 0 AND ws.unit_price_origin != ws.unit_price ) )
        GROUP BY
            ws.id LIMIT $start, $end";
        $list  = $model->query($sql);
        Logs($list, __FUNCTION__, 'fm');
        $xhr = M('ms_xchr', 'tb_');
        foreach ($list as $k => $item) {
            if (empty($item['id'])) {
                continue;
            }
            if (empty($item['currency_id'])) {
                $stream->rollback();
                echo '币种不存在';
                v($item);
            }
            if ($item['currency_id'] == 'N000590300') {
                //人民币不用转汇
                $res = $stream->where(['id' => $item['id']])->save(['unit_price_origin' => $item['unit_price']]);
                if (!$res) {
                    $stream->rollback();
                    v($item);
                }
            } else {
                if (!empty($item['pur_storage_date'])) {
                    $date = date('Ymd', strtotime($item['pur_storage_date']));
                } else if (!empty($item['create_time'])) {
                    $date = date('Ymd', strtotime($item['create_time']));
                } else {
                    $date = date('Ymd');
                }
                $cur   = strtoupper(cdVal($item['currency_id']));
                $field = StringModel::getXchrCurrencyField($cur);
                $rate  = $xhr->where(['XCHR_STD_DT' => $date])->getField($field);
                if (empty($rate)) {
                    $stream->rollback();
                    echo '汇率转换失败';
                    v($item);
                }
                $price = round(bcmul($item['unit_price'], bcdiv(1, $rate, 10), 5), 4);
                $res   = $stream->where(['id' => $item['id']])->save(['unit_price_origin' => $price]);
                if (!$res) {
                    $stream->rollback();
                    echo '更新失败';
                    v($item);
                }
            }
            unset($list[$k]);
        }
        $stream->commit();
        die('success');
    }

    public function updateQoo10JapanSku()
    {
        $model = new Model();
        $sql   = "UPDATE tb_op_order_guds,
(
    SELECT
        tb_op_order_guds.ID,
        tb_op_order.ORDER_CREATE_TIME,
        tb_op_order_guds.SKU_ID,
        tb_op_order_guds.B5C_SKU_ID,
        tb_op_order_guds.ORDER_ITEM_ID 
    FROM
        tb_op_order,
        tb_op_order_guds 
    WHERE
        ( tb_op_order_guds.B5C_SKU_ID IS NULL OR tb_op_order_guds.B5C_SKU_ID = '' ) 
        AND tb_op_order.PLAT_CD = 'N000830500' 
        AND tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID 
        AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD 
        AND tb_op_order.STORE_ID = '225' 
        AND tb_op_order.ORDER_CREATE_TIME > '2019-12-01 00:00:00' 
        AND tb_op_order_guds.ORDER_ITEM_ID = '728791231' 
    ) AS temp_1 
    SET tb_op_order_guds.B5C_SKU_ID = '8002987603' 
WHERE
    tb_op_order_guds.ID = temp_1.ID 
    AND tb_op_order_guds.ORDER_ITEM_ID = '728791231'";
        $res   = $model->execute($sql);
        var_dump($res);
    }

    public function updateQoo10JapanSku2()
    {
        $model = new Model();
        $sql   = "UPDATE tb_op_order_guds,
(
    SELECT
        tb_op_order_guds.ID,
        tb_op_order.ORDER_CREATE_TIME,
        tb_op_order_guds.SKU_ID,
        tb_op_order_guds.B5C_SKU_ID,
        tb_op_order_guds.ORDER_ITEM_ID 
    FROM
        tb_op_order,
        tb_op_order_guds 
    WHERE
        tb_op_order.PLAT_CD in('N000830500') 
        AND tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID 
        AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD 
        AND tb_op_order.STORE_ID = '225' 
        AND tb_op_order.ORDER_CREATE_TIME > '2019-12-01 00:00:00' 
        AND tb_op_order.ORDER_CREATE_TIME <= '2020-04-27 23:59:59' 
        AND tb_op_order_guds.ORDER_ITEM_ID = '655759196'
        and tb_op_order.SEND_ORD_STATUS in('N001821000', 'N001820100')
    ) AS temp_1 
    SET tb_op_order_guds.B5C_SKU_ID = '8104340701' 
WHERE
    tb_op_order_guds.ID = temp_1.ID 
    AND tb_op_order_guds.ORDER_ITEM_ID = '655759196'";
        $res   = $model->execute($sql);
        var_dump($res);
    }

    public function updateB2bReturn()
    {
        $no = 'RN201904100009';
        $Db = new Model();
        $Db->startTrans();
        $order_info = $Db->query("select * from tb_b2b_order where PO_ID = '{$no}'");
        $order_info = $order_info[0];
        if (empty($order_info)) {
            die('failed');
        }
        Logs($order_info, 'order:' . __FUNCTION__, 'other');

        $res1 = $Db->execute("UPDATE `tb_b2b_goods` SET `is_inwarehouse_num`=is_inwarehouse_num-995, `return_num`=return_num-995 WHERE ( `ORDER_ID` = {$order_info['ID']} )");
        $res2 = $Db->execute("UPDATE `tb_b2b_warehouse_list` SET `WAREHOUSEING_NUM`=WAREHOUSEING_NUM-995 WHERE ( `ORDER_ID` = {$order_info['ID']} )");

        $return_info = $Db->query("SELECT * FROM tb_b2b_return WHERE ( `order_id` = {$order_info['ID']} )");
        $return_id   = $return_info[0]['id'];

        $res3 = $Db->execute("DELETE FROM tb_b2b_return WHERE ( `order_id` = {$order_info['ID']} )");
        $res5 = $Db->execute("DELETE FROM tb_b2b_return_goods WHERE ( `return_id` = {$return_id} )");

        $res6 = $Db->execute("UPDATE `tb_b2b_order` SET `return_status_cd`='N002770001' WHERE ( `ID` = {$order_info['ID']} ) ");

        $res7 = $Db->execute("UPDATE tb_b2b_receivable SET current_receivable = current_receivable+1621850,order_account = order_account+1621850 WHERE ( `order_id` = {$order_info['ID']})");
        $res8 = $Db->execute("UPDATE tb_report_b2b_receivable SET amount = amount+1621850 WHERE ( `order_id` = {$order_info['ID']} and type = 'return')");
        if (!$res1) {
            $Db->rollback();
            die('failed 1');
        }
        if (!$res2) {
            $Db->rollback();
            die('failed 2');
        }
        if (!$res3) {
            $Db->rollback();
            die('failed 3');
        }
        if (!$res5) {
            $Db->rollback();
            die('failed 5');
        }
        if (!$res6) {
            $Db->rollback();
            die('failed 6');
        }
        if (!$res7) {
            $Db->rollback();
            die('failed 7');
        }
        if (!$res8) {
            $Db->rollback();
            die('failed 8');
        }
        $Db->commit();

        die('success');
    }

    public function updateStoreVat()
    {
        $data = '[{"ID":"272","IS_VAT":"1"},{"ID":"271","IS_VAT":"1"},{"ID":"268","IS_VAT":"1"},{"ID":"267","IS_VAT":"1"},{"ID":"266","IS_VAT":"0"},{"ID":"254","IS_VAT":"1"},{"ID":"252","IS_VAT":"0"},{"ID":"251","IS_VAT":"0"},{"ID":"250","IS_VAT":"0"},{"ID":"244","IS_VAT":"1"},{"ID":"242","IS_VAT":"1"},{"ID":"227","IS_VAT":"0"},{"ID":"221","IS_VAT":"1"},{"ID":"216","IS_VAT":"1"},{"ID":"215","IS_VAT":"1"},{"ID":"213","IS_VAT":"1"},{"ID":"203","IS_VAT":"1"},{"ID":"200","IS_VAT":"1"},{"ID":"191","IS_VAT":"1"},{"ID":"189","IS_VAT":"1"},{"ID":"180","IS_VAT":"1"},{"ID":"173","IS_VAT":"1"},{"ID":"171","IS_VAT":"1"},{"ID":"170","IS_VAT":"1"},{"ID":"156","IS_VAT":"1"},{"ID":"154","IS_VAT":"1"},{"ID":"146","IS_VAT":"1"},{"ID":"145","IS_VAT":"1"},{"ID":"144","IS_VAT":"1"},{"ID":"137","IS_VAT":"1"},{"ID":"136","IS_VAT":"1"},{"ID":"128","IS_VAT":"1"},{"ID":"119","IS_VAT":"1"},{"ID":"118","IS_VAT":"1"},{"ID":"114","IS_VAT":"1"},{"ID":"112","IS_VAT":"1"},{"ID":"95","IS_VAT":"1"},{"ID":"88","IS_VAT":"1"},{"ID":"69","IS_VAT":"1"},{"ID":"43","IS_VAT":"1"},{"ID":"41","IS_VAT":"1"},{"ID":"35","IS_VAT":"1"},{"ID":"33","IS_VAT":"1"},{"ID":"28","IS_VAT":"1"},{"ID":"26","IS_VAT":"1"},{"ID":"17","IS_VAT":"1"},{"ID":"16","IS_VAT":"1"},{"ID":"14","IS_VAT":"1"},{"ID":"12","IS_VAT":"1"},{"ID":"10","IS_VAT":"1"},{"ID":"9","IS_VAT":"1"},{"ID":"8","IS_VAT":"1"},{"ID":"2","IS_VAT":"1"}]';
        $data = json_decode($data, true);
        $model = M('ms_store', 'tb_');
        foreach ($data as $v) {
            $res = $model->where(['ID'=>$v['ID']])->save(['IS_VAT'=>$v['IS_VAT']]);
            if (false === $res) {
                v($v);
            }
        }
        echo 'success';die;
    }
    public function updateTransferTradeType ()
    {
        $model = new Model();
        $model->startTrans();
        $sql = "UPDATE tb_fin_account_turnover set trade_type = 1 where transfer_type in ('N001950300','N001950400')";
        $res = $model->execute($sql);
        if (false === $res) {
            $model->rollback();
            die('failed');
        }
        $model->commit();
        die('success');
    }

    public function updateTransferStep ()
    {
        $model = new Model();
        $model->startTrans();
        $sql = "UPDATE tb_fin_account_transfer 
            SET current_step = current_step + 1 
            WHERE
                state IN ( 'N001940400', 'N001940300', 'N001940300' ) 
                AND payment_audit_id IS NULL;";
        $res = $model->execute($sql);
        if (false === $res) {
            $model->rollback();
            die('failed');
        }
        $model->commit();
        die('success');
    }

    public function updateOrderStatus ()
    {
        $model = new Model();
        $sql = "update tb_op_order set BWC_ORDER_STATUS = 'N000550400' where ID = 2050812";
        return $model->execute($sql);
    }

    public function bankDataImport()
    {
        $data = '[{"id":"1","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"2","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"4","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"18","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"19","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"62","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"63","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"72","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"92","bank_short_name":"CITI","bank_settlement_code":"006391","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"21/F,Citi Tower, One Bay East 83 Hoi Bun Road,Kwun Tong Kowloon","bank_postal_code":"999077","bank_account_type":"N003340001","city":"60186","":""},{"id":"96","bank_short_name":"CITI","bank_settlement_code":"531584000009","bank_address1":"中国","bank_address2":"广东省","bank_address3":"深圳市","bank_address_detail":"福田中心福华一路免税商务大厦34楼","bank_postal_code":"518048","bank_account_type":"N003340001","city":"1,3342,3381","":""},{"id":"97","bank_short_name":"CITI","bank_settlement_code":"531584000009","bank_address1":"中国","bank_address2":"广东省","bank_address3":"深圳市","bank_address_detail":"福田中心福华一路免税商务大厦34楼","bank_postal_code":"518048","bank_account_type":"N003340001","city":"1,3342,3381","":""},{"id":"98","bank_short_name":"CITI","bank_settlement_code":"531584000009","bank_address1":"中国","bank_address2":"广东省","bank_address3":"深圳市","bank_address_detail":"福田中心福华一路免税商务大厦34楼","bank_postal_code":"518048","bank_account_type":"N003340001","city":"1,3342,3381","":""},{"id":"5","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号2806A单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"6","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号2806A单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"8","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号2806A单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"61","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号2806A单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"67","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号2806A单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"58","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号花旗集团大厦裙楼1－2楼01室，主楼28楼06单元和07单元，31楼，32楼，33楼02单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"73","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号花旗集团大厦裙楼1－2楼01室，主楼28楼06单元和07单元，31楼，32楼，33楼02单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"126","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号花旗集团大厦裙楼1－2楼01室，主楼28楼06单元和07单元，31楼，32楼，33楼02单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"128","bank_short_name":"CITI","bank_settlement_code":"531290000011","bank_address1":"中国","bank_address2":"上海","bank_address3":"上海市","bank_address_detail":"自由贸易试验区花园石桥路33号花旗集团大厦裙楼1－2楼01室，主楼28楼06单元和07单元，31楼，32楼，33楼02单元","bank_postal_code":"200120","bank_account_type":"N003340001","city":"1,4244,4288","":""},{"id":"14","bank_short_name":"CITI","bank_settlement_code":"027","bank_address1":"韩国","bank_address2":"首尔","bank_address3":"","bank_address_detail":"24, Cheonggyecheon-ro, Jung-gu, Seoul, Republic of Korea","bank_postal_code":"03184","bank_account_type":"N003340001","city":"278,60437","":""},{"id":"15","bank_short_name":"CITI","bank_settlement_code":"027","bank_address1":"韩国","bank_address2":"首尔","bank_address3":"","bank_address_detail":"24, Cheonggyecheon-ro, Jung-gu, Seoul, Republic of Korea","bank_postal_code":"03184","bank_account_type":"N003340001","city":"278,60437","":""},{"id":"104","bank_short_name":"EWB","bank_settlement_code":"258688","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"Suite 1108, 11/F, Two International Finance Centre 8 Finance Street, Central, Hong Kong","bank_postal_code":"999077","bank_account_type":"","city":"60186","":""},{"id":"105","bank_short_name":"EWB","bank_settlement_code":"258688","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"Suite 1108, 11/F, Two International Finance Centre 8 Finance Street, Central, Hong Kong","bank_postal_code":"999077","bank_account_type":"","city":"60186","":""},{"id":"106","bank_short_name":"EWB","bank_settlement_code":"258688","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"Suite 1108, 11/F, Two International Finance Centre 8 Finance Street, Central, Hong Kong","bank_postal_code":"999077","bank_account_type":"","city":"60186","":""},{"id":"107","bank_short_name":"EWB","bank_settlement_code":"258688","bank_address1":"中国香港","bank_address2":"","bank_address3":"","bank_address_detail":"Suite 1108, 11/F, Two International Finance Centre 8 Finance Street, Central, Hong Kong","bank_postal_code":"999077","bank_account_type":"","city":"60186","":""}]';
        $data = json_decode($data, true);
        $model = M('fin_account_bank', 'tb_');
        $model->startTrans();
        foreach ($data as $v) {
            $address = $v['bank_address1'];
            if ($v['bank_address2']) {
                $address. '-'. $v['bank_address2'];
            }
            if ($v['bank_address3']) {
                $address. '-'. $v['bank_address3'];
            }
            $row = $model->find($v['id']);
            if (!empty($row['bank_short_name'])) {
                continue;
            }
            $save = [
                'bank_settlement_code' => $v['bank_settlement_code'],
                'bank_address'         => $address,
                'bank_address_detail'  => $v['bank_address_detail'],
                'bank_postal_code'     => $v['bank_postal_code'],
                'bank_account_type'    => $v['bank_account_type'],
                'city'                 => $v['city'],
                'bank_short_name'      => $v['bank_short_name'],
            ];
            $res = $model->where(['id' => $v['id']])->save($save);
            if (false === $res) {
                $model->rollback();
                v($save);
            }
        }
        $model->commit();
        echo 'success';die;
    }

    public function accountingUpdate()
    {
        $model = new Model();
        $sql = "UPDATE tb_pur_payment_audit set accounting_audit_user = 'Sherry.Huang,Wendy.Chen,Astor.Zhang' where id in(24602,24603,24604,24605);";
        $res = $model->execute($sql);
        v($res);
    }

    public function updateBatchPurStorageDate()
    {
        $Db = new Model();
        $where['tb_wms_bill.type'] = 1;
        $where['tb_wms_batch.total_inventory'] = ['gt', 0];
        $where['tb_wms_stream.pur_storage_date'] = ['exp', 'is null'];
        $response                    = $Db->table('tb_wms_stream')
            ->field(['tb_wms_stream.id', 'tb_wms_stream.pur_storage_date','tb_wms_batch.create_time'])
            ->join('left join tb_wms_batch on tb_wms_stream.id = tb_wms_batch.stream_id')
            ->join('left join tb_wms_bill on tb_wms_stream.bill_id = tb_wms_bill.id')
            ->where($where)
            ->order('id desc')
            ->limit(0 , 5000)
            ->select();
        Logs($response, 'tb_wms_stream_db');
        $ids = array_column($response, 'id');
        $sql = "UPDATE tb_wms_stream SET pur_storage_date = CASE id";
        foreach ($response as $item) {
            $sql .= "
                    WHEN {$item['id']} THEN
                    '{$item['create_time']}'";
        }
        $sql = trim($sql, ",");
        $sql .=  " END where id in (" . implode(',', $ids) . ")";
        $res  = $Db->execute($sql);
        echo $res;
        die;
    }

    public function updateShippingTimeIsNull(){
        $Db = new Model();
        $number = I('number');
        if (empty($number)) {
            $number = 5000;
        }
        $sql = "UPDATE b5m_b5c.tb_op_order,
                 (
                    SELECT
                        tb_op_order.ORDER_ID,
                        tb_op_order.PLAT_CD,
                        tb_op_order.SHIPPING_TIME AS OLD_SHIPPING_TIME,
                        tb_op_order_yz.SHIPPING_TIME                        
                    FROM
                        b5m_b5c.tb_op_order,                        
                        sa_api.tb_op_order_yz
                    WHERE
                        tb_op_order.ORDER_ID = tb_op_order_yz.ORDER_ID
                    AND tb_op_order.PLAT_CD = tb_op_order_yz.PLAT_CD
                    AND tb_op_order.SHIPPING_TIME = '2020-06-04 15:58:01'
                    AND tb_op_order_yz.SHIPPING_TIME IS NULL
                    ORDER BY	tb_op_order.ID DESC
                    LIMIT {$number}
                ) AS temp_1
                SET tb_op_order.SHIPPING_TIME = temp_1.SHIPPING_TIME
                WHERE
                    tb_op_order.ORDER_ID = temp_1.ORDER_ID
                AND tb_op_order.PLAT_CD = temp_1.PLAT_CD";
        $res = $Db->execute($sql);
        var_dump($res);

    }

    public function updateShippingTimeIsDiff(){
        $Db = new Model();
        $number = I('number');
        if (empty($number)) {
            $number = 5000;
        }
        $sql = "UPDATE b5m_b5c.tb_op_order,
                 (
                    SELECT
                        tb_op_order.ORDER_ID,
                        tb_op_order.PLAT_CD,
                        tb_op_order.SHIPPING_TIME AS OLD_SHIPPING_TIME,
                        tb_op_order_yz.SHIPPING_TIME
                    FROM
                        b5m_b5c.tb_op_order,
                        sa_api.tb_op_order_yz
                    WHERE
                        tb_op_order.ORDER_ID = tb_op_order_yz.ORDER_ID
                    AND tb_op_order.PLAT_CD = tb_op_order_yz.PLAT_CD
                    AND tb_op_order.SHIPPING_TIME = '2020-06-04 15:58:01'
                    AND tb_op_order.SHIPPING_TIME != tb_op_order_yz.SHIPPING_TIME                    
                    ORDER BY	tb_op_order.ID DESC
                    LIMIT {$number}
                ) AS temp_1
                SET tb_op_order.SHIPPING_TIME = temp_1.SHIPPING_TIME
                WHERE
                    tb_op_order.ORDER_ID = temp_1.ORDER_ID
                AND tb_op_order.PLAT_CD = temp_1.PLAT_CD";
        $res = $Db->execute($sql);
        var_dump($res);
    }

    public function updateDateSetNull(){
        $Db = new Model();
        $sql = "UPDATE tb_op_order
                SET tb_op_order.SHIPPING_TIME = NULL
                WHERE
                    tb_op_order.SHIPPING_TIME = '2020-06-04 15:58:01'
                AND tb_op_order.ORDER_CREATE_TIME > '2020-06-03 00:00:00'";
        $res = $Db->execute($sql);
        var_dump($res);
    }

    //10445 批量上传运单号
    public function updateTracking()
    {
        $data = '[
{"B5C_ORDER_NO":"gspt592185396772","NUM":"358068082691"},
{"B5C_ORDER_NO":"gspt592185394823","NUM":"358068082713"},
{"B5C_ORDER_NO":"gspt592185395182","NUM":"358068082724"},
{"B5C_ORDER_NO":"gspt592185398004","NUM":"358068082735"},
{"B5C_ORDER_NO":"gspt592185395475","NUM":"358068082746"},
{"B5C_ORDER_NO":"gspt592185358106","NUM":"358068082761"},
{"B5C_ORDER_NO":"gspt592185359394","NUM":"358068082772"},
{"B5C_ORDER_NO":"gspt592185395864","NUM":"358068082783"},
{"B5C_ORDER_NO":"gspt592185355147","NUM":"358068082794"},
{"B5C_ORDER_NO":"gspt592185395667","NUM":"358068082805"},
{"B5C_ORDER_NO":"gspt592185395537","NUM":"358068082816"},
{"B5C_ORDER_NO":"gspt592185357700","NUM":"358068082820"},
{"B5C_ORDER_NO":"gspt592185357631","NUM":"358068082831"},
{"B5C_ORDER_NO":"gspt592187273240","NUM":"358068082842"},
{"B5C_ORDER_NO":"gspt592187180644","NUM":"358068082853"},
{"B5C_ORDER_NO":"gspt592187180028","NUM":"358068082864"},
{"B5C_ORDER_NO":"gspt592193619683","NUM":"358068082875"},
{"B5C_ORDER_NO":"gspt592185357006","NUM":"358068082901"},
{"B5C_ORDER_NO":"gspt592185355783","NUM":"358068082912"},
{"B5C_ORDER_NO":"gspt592185395113","NUM":"358068082923"},
{"B5C_ORDER_NO":"gspt592185397724","NUM":"358068082934"},
{"B5C_ORDER_NO":"gspt592185357780","NUM":"358068082945"},
{"B5C_ORDER_NO":"gspt592185397164","NUM":"358068082956"},
{"B5C_ORDER_NO":"gspt592185397292","NUM":"358068082960"},
{"B5C_ORDER_NO":"gspt592185396417","NUM":"358068082971"},
{"B5C_ORDER_NO":"gspt592185395044","NUM":"358068082982"},
{"B5C_ORDER_NO":"gspt592185393458","NUM":"358068092594"},
{"B5C_ORDER_NO":"gspt592185358660","NUM":"358068092605"},
{"B5C_ORDER_NO":"gspt592185361026","NUM":"358068092616"},
{"B5C_ORDER_NO":"gspt592185396830","NUM":"358068092620"},
{"B5C_ORDER_NO":"gspt592185358714","NUM":"358068092631"},
{"B5C_ORDER_NO":"gspt592185354012","NUM":"358068092642"},
{"B5C_ORDER_NO":"gspt592185359892","NUM":"358068092653"},
{"B5C_ORDER_NO":"gspt592185359451","NUM":"358068092664"},
{"B5C_ORDER_NO":"gspt592185355596","NUM":"358068092675"},
{"B5C_ORDER_NO":"gspt592185353904","NUM":"358068092686"},
{"B5C_ORDER_NO":"gspt592185395734","NUM":"358068092690"},
{"B5C_ORDER_NO":"gspt592185359968","NUM":"358068092701"},
{"B5C_ORDER_NO":"gspt592185358313","NUM":"358068092712"},
{"B5C_ORDER_NO":"gspt592185359329","NUM":"358068092723"},
{"B5C_ORDER_NO":"gspt592185354454","NUM":"358068092734"},
{"B5C_ORDER_NO":"gspt592185359127","NUM":"358068092745"},
{"B5C_ORDER_NO":"gspt592185354229","NUM":"358068092756"},
{"B5C_ORDER_NO":"gspt592185395253","NUM":"358068092760"},
{"B5C_ORDER_NO":"gspt592185397783","NUM":"358068092771"},
{"B5C_ORDER_NO":"gspt592193606638","NUM":"358068092782"},
{"B5C_ORDER_NO":"gspt592185360522","NUM":"358068092793"},
{"B5C_ORDER_NO":"gspt592187180926","NUM":"358068092804"},
{"B5C_ORDER_NO":"gspt592185360601","NUM":"358068092826"},
{"B5C_ORDER_NO":"gspt592185396602","NUM":"358068092830"},
{"B5C_ORDER_NO":"gspt592185359067","NUM":"358068092841"},
{"B5C_ORDER_NO":"gspt592185359190","NUM":"358068092852"},
{"B5C_ORDER_NO":"gspt592185354518","NUM":"358068092885"},
{"B5C_ORDER_NO":"gspt592185354393","NUM":"358068092896"},
{"B5C_ORDER_NO":"gspt592185358938","NUM":"358068092900"},
{"B5C_ORDER_NO":"gspt592185357317","NUM":"358068092911"},
{"B5C_ORDER_NO":"gspt592185397357","NUM":"358068092922"},
{"B5C_ORDER_NO":"gspt592185396669","NUM":"358068092933"},
{"B5C_ORDER_NO":"gspt592185359604","NUM":"358068092944"},
{"B5C_ORDER_NO":"gspt592185355211","NUM":"358068092955"},
{"B5C_ORDER_NO":"gspt592185397236","NUM":"358068092966"},
{"B5C_ORDER_NO":"gspt592185358611","NUM":"358068092970"},
{"B5C_ORDER_NO":"gspt592185355903","NUM":"358068092981"},
{"B5C_ORDER_NO":"gspt592185360373","NUM":"358068092992"},
{"B5C_ORDER_NO":"gspt592185357211","NUM":"358068093003"},
{"B5C_ORDER_NO":"gspt592185360062","NUM":"358068093014"},
{"B5C_ORDER_NO":"gspt592185356122","NUM":"358068093025"},
{"B5C_ORDER_NO":"gspt592185397853","NUM":"358068093036"},
{"B5C_ORDER_NO":"gspt592185393395","NUM":"358068093040"},
{"B5C_ORDER_NO":"gspt592185356794","NUM":"358068093051"},
{"B5C_ORDER_NO":"gspt592185361639","NUM":"358068093062"},
{"B5C_ORDER_NO":"gspt592185355278","NUM":"358068093073"},
{"B5C_ORDER_NO":"gspt592185395313","NUM":"358068093084"},
{"B5C_ORDER_NO":"gspt592185397902","NUM":"358068093095"},
{"B5C_ORDER_NO":"gspt592185395600","NUM":"358068093106"},
{"B5C_ORDER_NO":"gspt592185397664","NUM":"358068093110"},
{"B5C_ORDER_NO":"gspt592186417293","NUM":"358068093121"},
{"B5C_ORDER_NO":"gspt592185358813","NUM":"358068093132"},
{"B5C_ORDER_NO":"gspt592185356301","NUM":"358068093143"},
{"B5C_ORDER_NO":"gspt592185397434","NUM":"358068093154"},
{"B5C_ORDER_NO":"gspt592185354345","NUM":"358068093165"},
{"B5C_ORDER_NO":"gspt592185396930","NUM":"358068093176"},
{"B5C_ORDER_NO":"gspt592185355052","NUM":"358068093180"},
{"B5C_ORDER_NO":"gspt592185354065","NUM":"358068093191"},
{"B5C_ORDER_NO":"gspt592185396341","NUM":"358068093202"},
{"B5C_ORDER_NO":"gspt592185357847","NUM":"358068093213"},
{"B5C_ORDER_NO":"gspt592185356393","NUM":"358068093224"},
{"B5C_ORDER_NO":"gspt592185359680","NUM":"358068102836"},
{"B5C_ORDER_NO":"gspt592185357436","NUM":"358068102851"},
{"B5C_ORDER_NO":"gspt592185361502","NUM":"358068102862"},
{"B5C_ORDER_NO":"gspt592185354275","NUM":"358068102873"},
{"B5C_ORDER_NO":"gspt592185354593","NUM":"358068102884"},
{"B5C_ORDER_NO":"gspt592185356612","NUM":"358068102895"},
{"B5C_ORDER_NO":"gspt592185357569","NUM":"358068102906"},
{"B5C_ORDER_NO":"gspt592185396480","NUM":"358068102910"},
{"B5C_ORDER_NO":"gspt592185357378","NUM":"358068102921"},
{"B5C_ORDER_NO":"gspt592185395805","NUM":"358068102932"},
{"B5C_ORDER_NO":"gspt592185396728","NUM":"358068102954"},
{"B5C_ORDER_NO":"gspt592185359530","NUM":"358068102965"},
{"B5C_ORDER_NO":"gspt592185393335","NUM":"358068102976"},
{"B5C_ORDER_NO":"gspt592185396994","NUM":"358068102980"},
{"B5C_ORDER_NO":"gspt592185354980","NUM":"358068102991"},
{"B5C_ORDER_NO":"gspt592185354175","NUM":"358068103002"},
{"B5C_ORDER_NO":"gspt592186417540","NUM":"358068103013"},
{"B5C_ORDER_NO":"gspt592185359006","NUM":"358068103024"},
{"B5C_ORDER_NO":"gspt592185354112","NUM":"358068103035"},
{"B5C_ORDER_NO":"gspt592189974469","NUM":"358068072740"},
{"B5C_ORDER_NO":"gspt592188864100","NUM":"358068082352"},
{"B5C_ORDER_NO":"gspt592188863963","NUM":"358068082363"},
{"B5C_ORDER_NO":"gspt592188863907","NUM":"358068082374"},
{"B5C_ORDER_NO":"gspt592188863765","NUM":"358068082385"},
{"B5C_ORDER_NO":"gspt592188405562","NUM":"358068082396"},
{"B5C_ORDER_NO":"gspt592188405482","NUM":"358068082400"},
{"B5C_ORDER_NO":"gspt592188405271","NUM":"358068082411"},
{"B5C_ORDER_NO":"gspt592188363553","NUM":"358068082422"},
{"B5C_ORDER_NO":"gspt592188363707","NUM":"358068082433"},
{"B5C_ORDER_NO":"gspt592187775981","NUM":"358068082444"},
{"B5C_ORDER_NO":"gspt592187775904","NUM":"358068082455"},
{"B5C_ORDER_NO":"gspt592187775807","NUM":"358068082466"},
{"B5C_ORDER_NO":"gspt592187775723","NUM":"358068082470"},
{"B5C_ORDER_NO":"gspt592187775612","NUM":"358068082481"},
{"B5C_ORDER_NO":"gspt592187775275","NUM":"358068082492"},
{"B5C_ORDER_NO":"gspt592187775169","NUM":"358068082503"},
{"B5C_ORDER_NO":"gspt592187124754","NUM":"358068082514"},
{"B5C_ORDER_NO":"gspt592187124579","NUM":"358068082525"},
{"B5C_ORDER_NO":"gspt592187124192","NUM":"358068082536"},
{"B5C_ORDER_NO":"gspt592186195625","NUM":"358068082540"},
{"B5C_ORDER_NO":"gspt592186195260","NUM":"358068082551"},
{"B5C_ORDER_NO":"gspt592186195199","NUM":"358068082562"},
{"B5C_ORDER_NO":"gspt592186195108","NUM":"358068082573"},
{"B5C_ORDER_NO":"gspt592186194946","NUM":"358068082584"},
{"B5C_ORDER_NO":"gspt592190199364","NUM":"358068082595"},
{"B5C_ORDER_NO":"gspt592190200426","NUM":"358068082606"},
{"B5C_ORDER_NO":"gspt592190199957","NUM":"358068082610"},
{"B5C_ORDER_NO":"gspt592190198832","NUM":"358068082621"},
{"B5C_ORDER_NO":"gspt592190198398","NUM":"358068082632"},
{"B5C_ORDER_NO":"gspt592190198973","NUM":"358068082643"},
{"B5C_ORDER_NO":"gspt592190198892","NUM":"358068082654"},
{"B5C_ORDER_NO":"gspt591928933814","NUM":"358068082665"},
{"B5C_ORDER_NO":"gspt591928933249","NUM":"358068082676"},
{"B5C_ORDER_NO":"gspt591928933330","NUM":"358068082680"},
{"B5C_ORDER_NO":"gspt591775334079","NUM":"380635701205"},
{"B5C_ORDER_NO":"gspt591775334153","NUM":"380635701216"},
{"B5C_ORDER_NO":"gspt591775333975","NUM":"380635701220"},
{"B5C_ORDER_NO":"gspt591775333874","NUM":"380635701231"},
{"B5C_ORDER_NO":"gspt591775333773","NUM":"380635701242"},
{"B5C_ORDER_NO":"gspt591775333620","NUM":"380635701253"},
{"B5C_ORDER_NO":"gspt591775334393","NUM":"6315-7157-0766"},
{"B5C_ORDER_NO":"gspt591775334335","NUM":"380635701275"},
{"B5C_ORDER_NO":"gspt591775333053","NUM":"6315-7157-0770"},
{"B5C_ORDER_NO":"gspt591775332674","NUM":"6315-7157-0781"},
{"B5C_ORDER_NO":"gspt591775332137","NUM":"6315-7157-0803"},
{"B5C_ORDER_NO":"gspt591775331861","NUM":"6315-7157-0814"},
{"B5C_ORDER_NO":"gspt591775333253","NUM":"6315-7157-0766"},
{"B5C_ORDER_NO":"gspt591775332827","NUM":"6315-7157-0770"},
{"B5C_ORDER_NO":"gspt591775332513","NUM":"6315-7157-0781"},
{"B5C_ORDER_NO":"gspt591775331955","NUM":"6315-7157-0803"},
{"B5C_ORDER_NO":"gspt591775331683","NUM":"6315-7157-0814"},
{"B5C_ORDER_NO":"gspt591775333151","NUM":"359574058233"},
{"B5C_ORDER_NO":"gspt591775332740","NUM":"359574058244"},
{"B5C_ORDER_NO":"gspt591775332236","NUM":"359574058222"},
{"B5C_ORDER_NO":"gspt592185356710","NUM":"380635750183"},
{"B5C_ORDER_NO":"gspt592186399355","NUM":"380635749995"},
{"B5C_ORDER_NO":"gspt592185358407","NUM":"380635750054"},
{"B5C_ORDER_NO":"gspt592185358247","NUM":"380635749914"},
{"B5C_ORDER_NO":"gspt592185356514","NUM":"359574090551"},
{"B5C_ORDER_NO":"gspt592185358023","NUM":"380635750172"},
{"B5C_ORDER_NO":"gspt592185355426","NUM":"359574090540"},
{"B5C_ORDER_NO":"gspt592186399291","NUM":"380635750194"},
{"B5C_ORDER_NO":"gspt592186399192","NUM":"380635750076"},
{"B5C_ORDER_NO":"gspt592186399135","NUM":"380635750253"},
{"B5C_ORDER_NO":"gspt592186399061","NUM":"380635750253"},
{"B5C_ORDER_NO":"gspt592185357499","NUM":"359574090271"},
{"B5C_ORDER_NO":"gspt592185361408","NUM":"359574090536"},
{"B5C_ORDER_NO":"gspt592185361249","NUM":"359574090562"},
{"B5C_ORDER_NO":"gspt592185361152","NUM":"380635750010"},
{"B5C_ORDER_NO":"gspt592187181083","NUM":"380635750091"},
{"B5C_ORDER_NO":"gspt592185360855","NUM":"380635750091"},
{"B5C_ORDER_NO":"gspt592187180813","NUM":"380635749951"},
{"B5C_ORDER_NO":"gspt592185360227","NUM":"359574110405"},
{"B5C_ORDER_NO":"gspt592187180509","NUM":"380635749866"},
{"B5C_ORDER_NO":"gspt592187180342","NUM":"380635750124"},
{"B5C_ORDER_NO":"gspt592185360158","NUM":"380635750135"},
{"B5C_ORDER_NO":"gspt592187180164","NUM":"380635749973"},
{"B5C_ORDER_NO":"gspt592187180261","NUM":"380635750135"},
{"B5C_ORDER_NO":"gspt592187179938","NUM":"380635750135"},
{"B5C_ORDER_NO":"gspt592185355968","NUM":"380635750220"},
{"B5C_ORDER_NO":"gspt592187181376","NUM":"380635749870"},
{"B5C_ORDER_NO":"gspt592187179564","NUM":"380635750032"}
]';
        $data = json_decode($data, true);
        $model = M('ms_ord_package', 'tb_');
        $order_model = M('op_order', 'tb_');
        $model->startTrans();
        foreach ($data as $v) {
            $order = $order_model->field(['ORDER_ID', 'PLAT_CD'])->where(['B5C_ORDER_NO'=>$v['B5C_ORDER_NO']])->find();
            if (empty($order)) {
                $no[] = $order;
                continue;
            }
            $row = $model->where(['ORD_ID'=>$order['ORDER_ID'], 'plat_cd'=>$order['PLAT_CD']])->find();
            if (empty($row)) {
                $add_res = $model->add([
                    'ORD_ID'          => $order['ORDER_ID'],
                    'plat_cd'         => $order['PLAT_CD'],
                    'TRACKING_NUMBER' => $v['NUM'],
                    'updated_time'    => dateTime(),
                ]);
                if (!$add_res) {
                    $model->rollback();
                    echo 'add failed';
                    v($v);
                }
            } else {
                $save_res = $model->where(['ORD_ID'=>$order['ORDER_ID'], 'plat_cd'=>$order['PLAT_CD']])->save(['TRACKING_NUMBER'=>$v['NUM']]);
                if (false === $save_res) {
                    $model->rollback();
                    echo 'update failed';
                    v($v);
                }
            }
        }

        $model->commit();
        echo 'success';
        v($no);
    }

    //8017 B2C自动出库，内部关联交易单重新生成
    public function outgoing()
    {
        $query       = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model       = new OmsOutGoingModel();
        $model->mode = 1;
        if ('b08a8be1abd25efd858141757dbfc5c5' == $_GET['api']) {
            $model->autoSend = true;
        } elseif (!empty($query['ordId'])) {
            list($query['ordId'], $err_msg) = A('Oms/OutGoing')->limitUserLogistics($query['ordId']);
        }
        if (!empty($query['ordId'])) {
            $r = $model->outgoingRepair($query);
        } else {
            $r['code'] = 2000;
            $r['info'] = '与用户指定仓库物流不符';
            $r['data'] = [];
        }
        $r['data'] = array_merge(array_values($r['data']), array_values($err_msg));
        $response  = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);

        $order_ids = [];
        foreach ($r ['data'] as $v) {
            if ($v['code'] != '2000') {
                continue;
            }
            $order_ids[] = $v['ordId'];
        }

        $this->ajaxReturn($response, 'json');
    }

    public function repairBatchOrder()
    {
        $batch_order_id = I('batch_order_id');
        $batch_map = [
            '294249' =>263352,
            '307918' =>260901,
            '307919' =>260901,
            '307920' =>260901,
            '307921' =>260901,
            '307922' =>260901,
            '307923' =>260901,
            '307924' =>260901,
            '307925' =>260901,
            '309442' =>236362,
            '309443' =>260901,
            '309444' =>236362,
            '309445' =>262508,
            '309446' =>262508,
            '309447' =>262508,
            '309448' =>262508,
            '317647' =>236362,
            '321354' =>310462
        ];
        $batch_id = M('wms_batch_order','tb_')->where(['id'=>$batch_order_id])->getField('batch_id');
        $res =  M('wms_batch_order','tb_')->where(['id'=>$batch_order_id])->save([
            'batch_id' => $batch_map[$batch_id]
        ]);
        v($res);
    }

    public function repairUseType()
    {
        $param = ZUtils::filterBlank($this->getParams()['data']);
        $use_type = ZUtils::filterBlank($this->getParams()['use_type']);
        $res = M('wms_batch_order','tb_')->where(['id'=>['in', $param]])->save([
            'use_type' => $use_type
        ]);
        v($res);
    }

    public function repairData()
    {
        M('fin_rel_trans','tb_')->where(['id'=>['in',[115092,115054]]])->delete();
        M('wms_batch_order','tb_')->where(['id'=>2105947])->save(['batch_id'=>307918]);
        $model = new Model();
        $sql1 = "SELECT * from tb_wms_batch_order where ORD_ID in (
            'gspt586169898360',
            'gspt587360408480',
            'gspt587360410148',
            'gspt587360404745',
            'gspt587360408802',
            'gspt587360409754',
            'gspt587360401219',
            'gspt587360405402',
            'gspt587360407698',
            'gspt587373476604',
            'gspt587360433392',
            'gspt587373473865',
            'gspt587374081793',
            'gspt587396066032',
            'gspt587374065308',
            'gspt587374076393',
            'gspt587978677843'
            )";
        $batch_order = $model->query($sql1);
        $batch_order_map = array_column($batch_order, 'ORD_ID', 'batch_id');
        $sql2 = "SELECT batch_id,data_json from tb_wms_batch_history where batch_id in (
                294249,
                307923,
                307920,
                307924,
                307925,
                307918,
                307921,
                307922,
                307919,
                309443,
                309444,
                309442,
                309447,
                309448,
                309445,
                309446,
                317647
                );";
        $result = $model->query($sql2);
        foreach ($result as $item) {
            $model->startTrans();
            $deleted_batch = json_decode($item['data_json'], true);
            $old_batch_id = $deleted_batch['batchId'];
//            $old_batch =  M('wms_batch','tb_')->find($old_batch_id);
            $batch_order =  M('wms_batch_order','tb_')->where(['ORD_ID'=>$batch_order_map[$deleted_batch['id']]])->find();
            $old_bill = M('wms_bill','tb_')->where(['link_b5c_no'=>$batch_order_map[$deleted_batch['id']]])->find();
            $old_stream = M('wms_stream','tb_')->where(['bill_id'=>$old_bill['id']])->find();
            //内部关联交易入库批次修复
            $batch_data = [
                'id' => $deleted_batch['id'],
                'all_available_for_sale_num' => $deleted_batch['allAvailableForSaleNum'],
                'all_oversole_number' => $deleted_batch['allOversoleNumber'],
                'all_total_inventory' => $deleted_batch['allTotalInventory'],
                'available_for_sale_num' => $deleted_batch['availableForSaleNum'],
                'batch_code' => $deleted_batch['batchCode'],
                'batch_id' => $deleted_batch['batchId'],
                'bill_id' => $deleted_batch['billId'],
                'broken_num' => $deleted_batch['brokenNum'],
                'channel' => $deleted_batch['channel'],
                'CHANNEL_SKU_ID' => $deleted_batch['channelSkuId'],
                'create_time' => date('Y-m-d H:i:s',$deleted_batch['createTime']/1000),
                'create_user_id' => $deleted_batch['createUserId'],
                'GUDS_ID' => $deleted_batch['gudsId'],
                'is_it_sensitive' => $deleted_batch['isItSensitive'],
                'locking' => $deleted_batch['locking'],
                'occupied' => $deleted_batch['occupied'],
                'original_storage_time' => date('Y-m-d H:i:s',$deleted_batch['originalStorageTime']/1000),
                'purchase_order_no' => $deleted_batch['purchaseOrderNo'],
                'purchase_team_code' => $deleted_batch['purchaseTeamCode'],
                'sale_team_code' => $deleted_batch['saleTeamCode'],
                'SKU_ID' => $deleted_batch['skuId'],
                'state' => $deleted_batch['state'],
                'stream_id' => $deleted_batch['streamId'],
                'total_inventory' => $deleted_batch['totalInventory'],
                'update_time' => date('Y-m-d H:i:s',$deleted_batch['updateTime']/1000),
                'update_user_id' => $deleted_batch['updateUserId'],
                'vir_type' => $deleted_batch['virType'],
                'warehouse_cost' => $deleted_batch['warehouseCost'],
                'warehouse_cost_currency' => $deleted_batch['warehouseCostCurrency'],
                'warehouse_cost_update_time' => date('Y-m-d H:i:s',$deleted_batch['warehouseCostUpdateTime']/1000),
                'warehouse_total_cost' => $deleted_batch['warehouseTotalCost'],
            ];
            $res1 = M('wms_batch','tb_')->add($batch_data);
            if (!$res1) {
                Logs('res1'.$model->getLastSql(), __FUNCTION__,'fm2');
                $model->rollback();
                continue;
            }
            $rel_no = M('fin_rel_trans','tb_')->where(['ord_id'=>$batch_order_map[$batch_data['id']]])->getField('rel_trans_no');
            $old_bill2 = $old_bill;
            //内部关联交易出库单据数据修复
            $old_bill2['id'] = $batch_data['bill_id'] - 1;
            $old_bill2['bill_id'] = 'GLJYC'. substr($rel_no,4);
            $old_bill2['link_bill_id'] = $rel_no;
            $old_bill2['link_b5c_no'] = '';
            $old_bill2['bill_type'] = 'N000950907';
            $old_bill2['relation_type'] = 'N002350705';
            $res2 = M('wms_bill','tb_')->add($old_bill2);
            if (!$res2) {
                Logs('res2'.$model->getLastSql(), __FUNCTION__,'fm2');
                $model->rollback();
                continue;
            }
            //内部关联交易出库stream数据修复
            $old_stream2 = $old_stream;
            $old_stream2['id'] = $batch_data['stream_id'] - 1;
            $old_stream2['bill_id'] = $batch_data['bill_id'] - 1;
            $old_stream2['batch'] = $old_batch_id;
            $old_stream2['ord_guds_id'] = '';
            $old_stream2['tb_wms_batch_order_id'] = $batch_order['id'];
            $old_stream2['unit_price'] = bcdiv($old_stream2['unit_price'],1.01,4);
            $old_stream2['unit_price_origin'] = bcdiv($old_stream2['unit_price_origin'],1.01,4);
            $old_stream2['unit_price_usd'] = bcdiv($old_stream2['unit_price_usd'],1.01,4);
            $res3 = M('wms_stream','tb_')->add($old_stream2);
            if (!$res3) {
                Logs('res3'.$model->getLastSql(), __FUNCTION__,'fm2');
                $model->rollback();
                continue;
            }

            //内部关联交易入库单据数据修复
            $old_bill['id'] = $batch_data['bill_id'];
            $old_bill['bill_id'] = 'GLJYR'. substr($rel_no,4);
            $old_bill['link_bill_id'] = $rel_no;
            $old_bill['link_b5c_no'] = '';
            $old_bill['bill_type'] = 'N000941004';
            $old_bill['relation_type'] = 'N002350705';
            $res2 = M('wms_bill','tb_')->add($old_bill);
            if (!$res2) {
                Logs('res4'.$model->getLastSql(), __FUNCTION__,'fm2');
                continue;
            }

            //内部关联交易入库stream数据修复
            $old_stream['id'] = $batch_data['stream_id'];
            $old_stream['bill_id'] = $old_bill['id'];
            $old_stream['batch'] = $batch_data['id'];
            $old_stream['ord_guds_id'] = '';
            $old_stream['tb_wms_batch_order_id'] = $batch_order['id'];
            $res3 = M('wms_stream','tb_')->add($old_stream);
            if (!$res3) {
                Logs('res5'.$model->getLastSql(), __FUNCTION__,'fm2');
                $model->rollback();
                continue;
            }
            $model->commit();
        }
        v('success');
    }

    public function repairRel()
    {
        $model = new Model();
        $sql1  = "update tb_fin_rel_trans set `status` = 0 where ord_id in (
            'gspt586169898360',
            'gspt587360408480',
            'gspt587360410148',
            'gspt587360404745',
            'gspt587360408802',
            'gspt587360409754',
            'gspt587360401219',
            'gspt587360405402',
            'gspt587360407698',
            'gspt587373476604',
            'gspt587360433392',
            'gspt587373473865',
            'gspt587374081793',
            'gspt587396066032',
            'gspt587374065308',
            'gspt587374076393',
            'gspt587978677843'
            );";
        $res1  = $model->execute($sql1);

        $sql2 = "update tb_wms_bill set type = 1 where id in (
            1958999,
            2024980,
            2024982,
            2024984,
            2024986,
            2024988,
            2024990,
            2024992,
            2024994,
            2034278,
            2034280,
            2034282,
            2034284,
            2034286,
            2034288,
            2034290,
            2072362
            );";
        $res2 = $model->execute($sql2);
        v([$res1, $res2]);
    }
    //一般付款审核负责人，归属部门间隔符调整
    public function paymentAuditUpdate()
    {
        $model = new Model();
        //$where['id'] = '12698';
        //更新审核负责人间隔符
        $where['source_cd'] = 'N003010004';
        $where['accounting_audit_user'] = ['like', '%,%']; //多个审核负责人
        $response = $model->table('tb_pur_payment_audit')->field(['id', 'accounting_audit_user'])
            ->where($where)->order('id desc')->limit(0 , 1000)->select();
        Logs($response, 'tb_wms_stream_db');
        if (!empty($response)) {
            $ids = array_column($response, 'id');
            $sql = "UPDATE tb_pur_payment_audit SET accounting_audit_user = CASE id";
            foreach ($response as $item) {
                $accounting_audit_user = str_replace(',', '->', $item['accounting_audit_user']);
                $sql .= " WHEN {$item['id']} THEN
                    '{$accounting_audit_user}'";
            }
            $sql = trim($sql, ",");
            $sql .=  " END where id in (" . implode(',', $ids) . ")";
            $res  = $model->execute($sql);
            var_dump($res);
        }
        //更新归属部门间隔符
        $this->paymentAuditUpdateData();
        echo 'success';die;
    }

    //一般付款审核负责人，归属部门间隔符调整
    public function paymentAuditUpdateData()
    {
        $model = new Model();
        $GeneralPaymentService = new GeneralPaymentService(null);
        //更新归属部门间隔符
        $where = [];
        $where['actual_fee_Department'] = ['like', '%,%']; //多个归属部门
        $response = $model->table('tb_general_payment')->field(['id', 'actual_fee_Department', 'actual_fee_department_id'])
            ->where($where)->order('id desc')->limit(0 , 1000)->select();
        $data = [];
        if (!empty($response)) {
            foreach ($response as $key => $item) {
                $data[$key]['id'] = $item['id'];
                $actual_fee_Department = str_replace(',', '>', $item['actual_fee_Department']);
                $actual_fee_department_id = $item['actual_fee_department_id'];
                //归属部门不包含Gshopper的拼上Gshopper
                if (strpos($item['actual_fee_Department'], "Gshopper>") === false) {
                    //为空不处理
                    empty($actual_fee_Department) or $actual_fee_Department = 'Gshopper>' . $actual_fee_Department;
                }
                //归属部门id不包含75(Gshopper)的拼上75
                if (strpos($item['actual_fee_department_id'], "75,") === false) {
                    //为空不处理
                    empty($actual_fee_department_id) or $actual_fee_department_id = '75,' . $actual_fee_department_id;
                }
                $data[$key]['actual_fee_Department'] = $actual_fee_Department;
                $data[$key]['actual_fee_department_id'] = $actual_fee_department_id;
            }
            $res = $GeneralPaymentService->saveAll($data, 'tb_general_payment', $model);
            var_dump($res);
        }
        //同步tb_general_payment_detail tb_general_payment数据
        $sql = "
update tb_general_payment_detail,tb_general_payment set tb_general_payment_detail.actual_fee_Department = tb_general_payment.actual_fee_Department,
tb_general_payment_detail.actual_fee_department_id = tb_general_payment.actual_fee_department_id
where tb_general_payment_detail.payment_audit_id = tb_general_payment.payment_audit_id and tb_general_payment_detail.actual_fee_Department = ''
";
        $res = $model->execute($sql);
        var_dump($res);
        echo 'success';die;
    }

    public function updateRefundLog ()
    {
        $model = new Model();
        $sql = "UPDATE tb_op_order_refund_log set status_name = '待确认付款账户' where status_name = '待付款';";
        $res = $model->execute($sql);
        v($res);
    }

    public static function updateIsDirectBilling()
    {
        $model = M('pur_payment_audit','tb_');
        $log_model = M('pur_payment_audit_log','tb_');
        $list = $model->where(['status'=>['in',[2,3]]])->select();
        foreach ($list as $v) {
            $withdraw_time = $log_model->where(['payment_audit_id'=>$v['id'],'operation_info'=>'撤回到待付款'])->order('id desc')->getField('created_at');
            if (empty($withdraw_time)) {
                $log_count = $log_model->where(['payment_audit_id'=>$v['id'], 'status_name'=>'待出账'])->count('id');
            } else {
                $log_count = $log_model->where(['payment_audit_id'=>$v['id'], 'status_name'=>'待出账', 'created_at'=>['gt',$withdraw_time]])->count('id');
            }
            $row = $log_model->where(['payment_audit_id'=>$v['id']])->find();
            if (empty($row)) {
                $res[] = $model->where(['id' => $v['id']])->save(['is_direct_billing' => 0]);
            } else {
                if ($log_count > 0) {
                    $res[] = $model->where(['id' => $v['id']])->save(['is_direct_billing' => 0]);
                } else {
                    $res[] = $model->where(['id' => $v['id']])->save(['is_direct_billing' => 1]);
                }
            }
        }
        v($res);
    }

    public function updateOrderAddress ()
    {
        $model = new Model();
        $sql = "UPDATE tb_op_order set ADDRESS_USER_COUNTRY_CODE = 'FR' where ID = '2610517';";
        $res = $model->execute($sql);
        v($res);
    }

    public function updateKyribaBillingDate ()
    {
        $no_record = $no_date = $no_success = $no_success2 = [];
        $data = '[{"payment_audit_no":"FK202008030003","billing_date":"2020/8/3"},{"payment_audit_no":"FK202008060007","billing_date":"2020/8/6"},{"payment_audit_no":"FK202008070015","billing_date":"2020/8/7"},{"payment_audit_no":"FK202008070008","billing_date":"2020/8/7"},{"payment_audit_no":"FK202008070005","billing_date":"2020/8/11"},{"payment_audit_no":"FK202008240003","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008100002","billing_date":"2020/8/10"},{"payment_audit_no":"FK202008110002","billing_date":"2020/8/11"},{"payment_audit_no":"FK202008110005","billing_date":"2020/8/11"},{"payment_audit_no":"FK202008120008","billing_date":"2020/8/12"},{"payment_audit_no":"FK202008120006","billing_date":"2020/8/12"},{"payment_audit_no":"FK202008120005","billing_date":"2020/8/12"},{"payment_audit_no":"FK202008120010","billing_date":"2020/8/12"},{"payment_audit_no":"FK202008120011","billing_date":"2020/8/12"},{"payment_audit_no":"FK202008180020","billing_date":"2020/8/18"},{"payment_audit_no":"FK202008180042","billing_date":"2020/8/18"},{"payment_audit_no":"FK202008180041","billing_date":"2020/8/18"},{"payment_audit_no":"FK202008190033","billing_date":"2020/8/19"},{"payment_audit_no":"FK202008190077","billing_date":"2020/8/19"},{"payment_audit_no":"FK202008190081","billing_date":"2020/8/20"},{"payment_audit_no":"FK202008190054","billing_date":"2020/8/19"},{"payment_audit_no":"FK202008190058","billing_date":"2020/8/19"},{"payment_audit_no":"FK202008190052","billing_date":"2020/8/19"},{"payment_audit_no":"FK202008310020","billing_date":"2020/9/1"},{"payment_audit_no":"FK202008180045","billing_date":"2020/8/18"},{"payment_audit_no":"FK202008200006","billing_date":"2020/8/20"},{"payment_audit_no":"FK202008200018","billing_date":"2020/8/20"},{"payment_audit_no":"FK202008180039","billing_date":"2020/8/18"},{"payment_audit_no":"FK202008200008","billing_date":"2020/8/26"},{"payment_audit_no":"FK202008250051","billing_date":"2020/8/25"},{"payment_audit_no":"FK202008240011","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008210004","billing_date":"2020/8/21"},{"payment_audit_no":"FK202008210011","billing_date":"2020/8/21"},{"payment_audit_no":"FK202008200024","billing_date":"2020/8/21"},{"payment_audit_no":"FK202008280003","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008240029","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008240030","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008240012","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008240021","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008240010","billing_date":"2020/8/24"},{"payment_audit_no":"FK202008240032","billing_date":"2020/8/25"},{"payment_audit_no":"FK202008250060","billing_date":"2020/8/25"},{"payment_audit_no":"FK202008240038","billing_date":"2020/8/25"},{"payment_audit_no":"FK202008260009","billing_date":"2020/8/26"},{"payment_audit_no":"FK202008260007","billing_date":"2020/8/26"},{"payment_audit_no":"FK202008260013","billing_date":"2020/8/26"},{"payment_audit_no":"FK202008260010","billing_date":"2020/8/26"},{"payment_audit_no":"FK202008270024","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008270013","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008270019","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008260016","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008270005","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008270004","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008260030","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008270012","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008270010","billing_date":"2020/8/27"},{"payment_audit_no":"FK202008280011","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008270022","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008270026","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008280002","billing_date":"2020/8/28"},{"payment_audit_no":"FK202008260017","billing_date":"2020/8/27"},{"payment_audit_no":"FK202009010008","billing_date":"2020/9/1"},{"payment_audit_no":"FK202008310034","billing_date":"2020/9/1"},{"payment_audit_no":"FK202009020005","billing_date":"2020/9/2"},{"payment_audit_no":"FK202009010045","billing_date":"2020/9/2"},{"payment_audit_no":"FK202009020019","billing_date":"2020/9/2"},{"payment_audit_no":"FK202009020022","billing_date":"2020/9/2"},{"payment_audit_no":"FK202009020020","billing_date":"2020/9/2"},{"payment_audit_no":"FK202009010049","billing_date":"2020/9/2"}]';
        $data = json_decode($data, true);
        $model = M('pur_payment_audit', 'tb_');
        $turnover = M('fin_account_turnover', 'tb_');
        foreach ($data as $v) {
            $payment_info = $model->where(['payment_audit_no'=>trim($v['payment_audit_no']), 'status'=>3])->find();
            if (empty($payment_info)) {
                $no_record[] = $v['payment_audit_no'];
                continue;
            }
            $time = strtotime($v['billing_date']);
            if (!$time) {
                $no_record[] = $v['payment_audit_no'];
                continue;
            }
            $billing_date = date('Y-m-d',$time );
            $res = $model->where(['id'=>$payment_info['id']])->save(['billing_date'=>$billing_date]);
            if ($res === false) {
                $no_success[] = $v['payment_audit_no'];
            }
            $res2 = $turnover->where(['transfer_no'=>trim($v['payment_audit_no'])])->save(['transfer_time'=>$billing_date]);
            if ($res2 === false) {
                $no_success2[] = $v['payment_audit_no'];
            }
        }
        echo 'no record';
        var_dump($no_record);
        echo 'no date';
        var_dump($no_date);
        echo 'no success2';
        var_dump($no_success2);
        echo 'no success';
        v($no_success);
    }

    public function updatePaymentAudit ()
    {
        $model = new Model();
        $sql = "UPDATE tb_pur_payment_audit set `status` = 9 where payment_audit_no in ('FK202010200036','FK202010210046')";
        $res = $model->execute($sql);
        v($res);
    }

    public function updatePurWarehouseEnd ()
    {
        $model = new Model();
        $sql = "UPDATE tb_pur_ship set warehouse_status = 1 where id = 11435;";
        $res1 = $model->execute($sql);
        $res2 = $model->execute("UPDATE tb_pur_relevance_order set warehouse_status = 2 where relevance_id = 13842;");
        v([$res1,$res2]);
    }

    public function updatePaymentLogStatusName()
    {
        $model = new Model();
        $model->startTrans();
        $log_model = M('pur_payment_audit_log', 'tb_');
        $payment_model = M('pur_payment_audit', 'tb_');
        $logs = $log_model->where(['operation_info'=>'同步kyriba回传邮件'])->group('payment_audit_id')->select();
        foreach ($logs as $v) {
            $status = $payment_model->where(['id'=>$v['payment_audit_id']])->getField('status');
//            $res1 = $log_model->where(['payment_audit_id'=>$v['payment_audit_id'],'operation_info'=>'确认出账'])->delete();
//            if (!$res1) {
//                $model->rollback();
//                die('delete failed'. $v['payment_audit_id']);
//            }
            $res2 = $log_model->where(['payment_audit_id'=>$v['payment_audit_id']])->save(['status_name'=>TbPurPaymentAuditModel::$status_map[$status]]);
            if (false === $res2) {
                $model->rollback();
                die('save failed'. $v['payment_audit_id']);
            }
        }
        echo 'success';
        $model->commit();
    }
    public function updatePaymentLogInfo()
    {
        $log_model = M('pur_payment_audit_log', 'tb_');
        $payment_model = M('pur_payment_audit', 'tb_');
        $payment_info = $payment_model->where(['status'=>3])->select();
        foreach ($payment_info as $v) {
            if (!$v['bank_reference_no']) {
                continue;
            }
            $data[] = [
                'payment_audit_id' => $v['id'],
                'operation_info' => '同步kyriba银行对账单',
                'status_name' => '已完成',
                'created_at' => $v['updated_at'],
            ];
        }
        $res1 = $log_model->addAll($data);
        $res2 = $log_model->where(['operation_info'=>'更新付款单状态'])->save(['operation_info'=>'撤回']);
        v([$res1,$res2]);
    }
    public function updatePaymentLogRemark()
    {
        $log_model = M('pur_payment_audit_log', 'tb_');
        $payment_model = M('pur_payment_audit', 'tb_');
        $payment_info = $payment_model->where(['pay_type'=>1])->select();
        foreach ($payment_info as $v) {
            $download_url = (new Service())->getKyribaPushFileDownloadPath($v['payment_audit_no']);
            $info = $log_model->where(['payment_audit_id'=>$v['id'], 'operation_info'=>'推kyriba'])->find();
            if (empty($download_url)) {
                continue;
            }
            $res[] = $log_model
                ->where(['payment_audit_id'=>$v['id'], 'operation_info'=>'推kyriba'])
                ->save(['remark'=>"<a href='{$download_url}' style='color:blue';>推kyriba加密前的文件的附件链接，点击可以下载</a>"]);
        }
        v($res);
    }

    public function updateInvoiceCounter()
    {
        $model             = D("B5cInvoiceTask");
        $store_counter_map = C('invoice_store_counter_map');
        foreach ($store_counter_map as $store_id => $value) {
            if (!isset($value['prefix_number'])) {
                continue;
            }
            $id = $model->where(['store_id' => $store_id])->order('id desc')->getField('id');
            if (empty($id)) {
                continue;
            }
            $res[] = $model->query("update gshopper_data.b5c_invoice_task set invoice_counter = {$value['start_counter']} where id = {$id} and store_id = {$store_id}");
//            $res[] = $model->where(['id'=>$id, 'store_id'=>$store_id])->save(['invoice_counter'=>$value['start_counter']]);
        }
        v($res);
    }

    public function saveBankAccountId ()
    {
        $res = $fail = [];
        $model = M('pur_payment_audit', 'tb_');
        $fin_model = M('fin_account_bank', 'tb_');
        $data = $model->where(['payment_account_id' => 0])->select();
        foreach ($data as $v) {
            if ($v['payment_account_id']) {
                continue;
            }
            if (empty($v['payment_our_bank_account'])) {
                continue;
            }
            $id = $fin_model->where(['account_bank'=>$v['payment_our_bank_account']])->getField('id');
            if ($id) {
                $res[] = $model->where(['id'=>$v['id']])->save(['payment_account_id'=>$id]);
            } else {
                $fail[] = $v['payment_audit_no'];
            }
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function saveBankAccountIdOther ()
    {
        $res = $fail = [];
        $model = M('pur_payment_audit', 'tb_');
        $fin_model = M('fin_account_bank', 'tb_');
        $account_map = [
            '741063036838-HKD' => 115,
            'GB18BARC20265364350288-JPY' => 540,
            'GB78BARC20265349244077-EUR' => 530,
            'b5mtrade2' => 9,
            'b5mtrade2@gshopper.c' => 9,
            'b5mtrade@gshopper.co' => 25,
        ];
        $currency_map = [
            'N000590100' => 'USD',
            'N000590300' => 'CNY',
            'N000590400' => 'JPY',
            'N000590500' => 'EUR',
            'N000590600' => 'HKD',
        ];
        $data = $model->where(['payment_account_id' => 0])->select();
        foreach ($data as $v) {
            if ($v['payment_account_id']) {
                continue;
            }
            if (empty($v['payment_our_bank_account'])) {
                continue;
            }
            if (trim($v['payment_our_bank_account']) == '741063036838') {
                $account = trim($v['payment_our_bank_account']). '-'. $currency_map[$v['billing_currency_cd']];
                $account_id = $fin_model->where(['account_bank'=>$account])->getField('id');
                if ($account_id) {
                    $res[] = $model->where(['id'=>$v['id']])->save(['payment_account_id'=>$account_id]);
                } else {
                    $fail[] = $v['payment_audit_no'];
                }
            } else {
                $account_id = $account_map[trim($v['payment_our_bank_account'])];
                if (empty($account_id)) {
                    $fail[] = $v['payment_audit_no'];
                    continue;
                }
                $res[] = $model->where(['id'=>$v['id']])->save(['payment_account_id'=>$account_id]);
            }
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function updateFinClaim ()
    {
        $model = new Model();
        $sql = "SELECT
            pa.id,
            pa.payment_audit_no,
            tu.id AS turnover_id,
            cl.id AS claim_id 
        FROM
            tb_pur_payment_audit pa
            LEFT JOIN tb_fin_account_turnover tu ON tu.transfer_no = pa.payment_audit_no
            LEFT JOIN tb_fin_claim cl ON cl.account_turnover_id = tu.id 
        WHERE
            pa.`status` = 3 
            AND pa.source_cd = 'N003010004' 
            AND ( pa.billing_amount > 0 OR pa.billing_fee > 0 ) 
            AND cl.id IS NULL 
        GROUP BY
            tu.id 
        ORDER BY
            claim_id ASC";
        $list = $model->query($sql);
        $repository = new PurRepository();
        foreach ($list as $item) {
            if ($item['claim_id']) {
                continue;
            }
            if (!$item['turnover_id']) {
                $fail[] = $item['payment_audit_no'];
                continue;
            }
            $pur_info = $repository->getPaymentAuditInfoByPaymentAuditIds([$item['id']], []);
            $res[] = (new TbFinClaimModel())->addGeneralToTurnoverRelation($item['turnover_id'], $pur_info);
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function updateReturnPlatformCd ()
    {
        $res = $fail = [];
        $model = new Model();
        $sql = "SELECT
            ret.id,
            ret.platform_cd,
            ret.after_sale_no,
            rel.platform_country_code 
        FROM
            tb_op_order_return ret
            LEFT JOIN tb_op_order_after_sale_relevance rel ON rel.after_sale_id = ret.id 
            AND rel.type = 1";
        $data = $model->query($sql);
        foreach ($data as $v) {
            if ($v['platform_cd']) {
                continue;
            }
            if (!$v['platform_country_code']) {
                $fail[] = $v;
                continue;
            }
            $res[] = $model->table('tb_op_order_return')->where(['id'=>$v['id']])->save(['platform_cd'=>$v['platform_country_code']]);
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function updateReissuePlatformCd ()
    {
        $res = $fail = [];
        $model = new Model();
        $sql = "SELECT
            ret.id,
            ret.platform_cd,
            ret.after_sale_no,
            rel.platform_country_code 
        FROM
            tb_op_order_reissue ret
            LEFT JOIN tb_op_order_after_sale_relevance rel ON rel.after_sale_id = ret.id 
            AND rel.type = 2";
        $data = $model->query($sql);
        foreach ($data as $v) {
            if ($v['platform_cd']) {
                continue;
            }
            if (!$v['platform_country_code']) {
                $fail[] = $v;
                continue;
            }
            $res[] = $model->table('tb_op_order_reissue')->where(['id'=>$v['id']])->save(['platform_cd'=>$v['platform_country_code']]);
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function updateReissueNumPlatformCd ()
    {
        $res = $fail = [];
        $model = new Model();
        $sql = "SELECT
            ret.id,
            ret.platform_cd,
            rel.platform_country_code 
        FROM
            tb_op_order_reissue_num ret
            LEFT JOIN tb_op_order_after_sale_relevance rel ON rel.after_sale_id = ret.reissue_id 
            AND rel.type = 2;";
        $data = $model->query($sql);
        foreach ($data as $v) {
            if ($v['platform_cd']) {
                continue;
            }
            if (!$v['platform_country_code']) {
                $fail[] = $v;
                continue;
            }
            $res[] = $model->table('tb_op_order_reissue_num')->where(['id'=>$v['id']])->save(['platform_cd'=>$v['platform_country_code']]);
        }
        echo 'result:</br>';
        var_dump($res);
        echo 'failed:</br>';
        v($fail);
    }

    public function updateFinTransferApprovalData()
    {
        $model = new Model();
        $sql = "SELECT
            id, detail_json 
        FROM
            tb_sys_reviews 
        WHERE
            order_no LIKE 'ZZ2020%' 
            AND review_status = 0 
            AND callback_function = 'transfer_approval' 
            AND detail_json LIKE '%\"company_code_val\":null%'";
        $list = $model->query($sql);
        $supplier_model = M('crm_sp_supplier', 'tb_');
        $bank_model = M('fin_account_bank', 'tb_');
        $sys_model = M('sys_reviews', 'tb_');
        $error = [];
        foreach ($list as $v) {
            $detail = json_decode($v['detail_json'], true);
            if (empty($detail['rec_account']['company_code_val'])) {
                $bank_info = $bank_model->find($detail['rec_account']['id']);
                if ($bank_info['account_class_cd'] == 'N003510002' && $bank_info['supplier_id']) {
                    //供应商账户类型
                    $detail['rec_account']['company_code_val'] = $supplier_model->where(['ID' => $bank_info['supplier_id']])->getField('SP_NAME');
                } else {
                    $error[] = $v;
                }
            }
            if (empty($detail['pay_account']['company_code_val'])) {
                $bank_info = $bank_model->find($detail['pay_account']['id']);
                if ($bank_info['account_class_cd'] == 'N003510002' && $bank_info['supplier_id']) {
                    //供应商账户类型
                    $detail['pay_account']['company_code_val'] = $supplier_model->where(['ID' => $bank_info['supplier_id']])->getField('SP_NAME');
                } else {
                    $error[] = $v;
                }
            }
            $detail_json = json_encode($detail);
            $res[] = $sys_model->where(['id'=>$v['id']])->save(['detail_json' => $detail_json]);
        }
        echo 'error:';
        var_dump($error);
        v($res);
    }


    public function updateB2bData()
    {
        header("Content-type: text/html; charset=utf-8");
        try {
            $pur_ship_id = I('pur_ship_id');
            $b2b_ship_id = I('b2b_ship_id');
            $model = new Model();
            $model->startTrans();
            $warehouse_save['ship_id']                 = $pur_ship_id;
            $warehouse_save['warehouse_number_broken'] = 0;
            $goods                                     = M('ship_goods', 'tb_pur_')->where(['ship_id' => $pur_ship_id])->select();
            foreach ($goods as $v) {
                $goods_w['warehouse_number']        = $v['ship_number'];
                $goods_w['warehouse_number_broken'] = 0;
                $goods_w['number_info_warehouse']   = $v['number_info_ship'];
                $warehouse_save['goods'][$v['id']]  = $goods_w;
                $warehouse_save['warehouse_number'] += $v['ship_number'];
            }
            $ship_info = $model->lock(true)->table('tb_pur_ship')->where(['id' => $warehouse_save['ship_id']])->find();

            $warehouse_save['warehouse_user'] = $ship_info['create_user'];
            $warehouse_save['warehouse_time'] = $ship_info['create_time'];

            $order_info = $model
                ->table('tb_pur_order_detail t')
                ->join('left join tb_pur_relevance_order a on a.order_id = t.order_id')
                ->join('left join tb_pur_sell_information b on b.sell_id = a.sell_id')
                ->where(['relevance_id' => $ship_info['relevance_id']])
                ->find();

            $shipments_number = 0;
            foreach ($warehouse_save['goods'] as $k => $v) {
                if ($v['warehouse_number'] || $v['warehouse_number_broken']) {
                    $shipped_goods[] = ['goods_id' => $k, 'warehouse_number' => $v['warehouse_number'], 'warehouse_number_broken' => $v['warehouse_number_broken']];
                    $goods_info      = $model
                        ->table('tb_pur_ship_goods t')
                        ->field('a.sku_information,a.unit_price,t.ship_number,t.warehouse_number,t.warehouse_number_broken,drawback_percent,a.information_id,a.unit_expense, t.sell_small_team_json')
                        ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                        ->where(['t.id' => $k])
                        ->find();
                    if ($v['warehouse_number'] < 0 || $v['warehouse_number_broken'] < 0) {
                        throw new Exception('入库商品数量不能为负');
                    }
                    $b2b_ship_goods[] = [
                        'sku_id'        => $goods_info['sku_information'],
                        'delivered_num' => $goods_info['ship_number'],
                        'sku_price'     => $goods_info['unit_price'],
                        'sku_currency'  => $order_info['amount_currency']
                    ];
                }
                $shipments_number += $goods_info['ship_number'];
            }

            $introduce_team = M('sp_supplier', 'tb_crm_')
                ->where(['SP_CHARTER_NO' => $order_info['sp_charter_no'], 'DATA_MARKING' => 0])
                ->getField('SP_JS_TEAM_CD');
            $b2b_ship_data  = [
                'order_info' => [
                    "po_id"           => $ship_info['sale_no'],
                    "supplier_id"     => 'supplier_id',
                    "purchasing_team" => $order_info['payment_company'],
                    "introduce_team"  => $introduce_team
                ],
                'goods'      => $b2b_ship_goods,
                'logictics'  => [
                    "bill_lading_code"       => $ship_info['bill_of_landing'],
                    "delivery_time"          => $ship_info['shipment_date'],
                    "estimated_arrival_date" => $ship_info['arrival_date'],
                    "logistics_currency"     => $ship_info['extra_cost_currency'],
                    "logistics_costs"        => $ship_info['extra_cost'],
                    "shipments_number"       => $shipments_number,
                    "remarks"                => $ship_info['remark']
                ]
            ];

            $b2b_action    = new B2bAction();
            $order_id      = $model->table('tb_b2b_order')->where(['PO_ID' => $ship_info['sale_no']])->getField('id');
            $b2b_ship_info = $model->table('tb_b2b_ship_list')->where(['ID'=>$b2b_ship_id])->find();
            $b2b_ship_info['ship_id'] = $b2b_ship_info['ID'];
            $b2b_ship_info['warehousing_id'] = $model->table('tb_b2b_warehouse_list')->where(['ORDER_ID'=>$order_id, 'SHIP_LIST_ID'=>$b2b_ship_id])->getField('ID');

            $b2b_action->Model = $model;
            list($upd_sum, $goods_sku_arr) = $this->b2bGoodsDeduction($b2b_ship_data, $order_id, '', $model);
            list($goods_data, $goods_dataw) = $b2b_action->goodsDataJoinScm($b2b_ship_data, $b2b_ship_info, $goods_sku_arr, $order_id);
            $b2b_action->allGoodsAdd($goods_data, $goods_dataw);
            $b2b_action->sync_ship_power_all($goods_data, $order_id, true);
            $b2b_action->updateOrderReceivableAccount($order_id);

            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            v($exception->getMessage());
        }
    }

    public function b2bGoodsDeduction($data, $order_id, $upd_sum = 0, $model)
    {
        $where['ORDER_ID'] = $order_id;
//        $where['TOBE_DELIVERED_NUM'] = array('gt', 0);
        $goods_num_arr = $model->table('tb_b2b_goods')
            ->field('SKU_ID,ID,SHIPPED_NUM,TOBE_DELIVERED_NUM')
            ->where($where)
            ->order('TOBE_DELIVERED_NUM ASC')
            ->select();

        if (empty($goods_num_arr)) {
            throw new Exception(L('介绍，采购团队对应订单信息不存在'), 40403);
        }
        foreach ($goods_num_arr as $goods_value) {
            $goods_sku_arr[$goods_value['SKU_ID']][] = $goods_value;
        }
        foreach ($data['goods'] as $v) {
//            if (array_sum(array_column($goods_sku_arr[$v['sku_id']], 'TOBE_DELIVERED_NUM')) < $v['delivered_num']) {
//                throw new Exception($v['sku_id'] . L('待发数量不足'), 40301);
//            }
            $goods_num = $goods_sku_arr[$v['sku_id']][0];
            if ($v['delivered_num'] <= $goods_num['TOBE_DELIVERED_NUM']) {
                $num_sum_sku['SHIPPED_NUM'] = $goods_num['SHIPPED_NUM'] + $v['delivered_num'];
                $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_num['TOBE_DELIVERED_NUM'] - $v['delivered_num'];
                unset($num_sum_sku);
                $upd_sum += $v['delivered_num'];
                $goods_sku_arr[$v['sku_id']][0]['TOBE_DELIVERED_NUM'] -= $v['delivered_num'];
                $goods_sku_arr[$v['sku_id']][0]['SHIPPED_NUM'] += $v['delivered_num'];
            } else {
                // 注意有两份改动
                foreach ($goods_sku_arr[$v['sku_id']] as $goods_key => $goods_value) {
                    if ($goods_value['TOBE_DELIVERED_NUM'] == 0) continue;
                    if ($v['delivered_num'] && $v['delivered_num'] > $goods_value['TOBE_DELIVERED_NUM']) {
                        $num_sum_sku['SHIPPED_NUM'] = $goods_value['SHIPPED_NUM'] + $goods_value['TOBE_DELIVERED_NUM'];
                        $num_sum_sku['TOBE_DELIVERED_NUM'] = 0;
                        $v['delivered_num'] -= $goods_value['TOBE_DELIVERED_NUM'];
                        $goods_sku_arr[$v['sku_id']][$goods_key]['TOBE_DELIVERED_NUM'] = 0;
                        $upd_sum += $goods_value['TOBE_DELIVERED_NUM'];
                    } elseif ($v['delivered_num'] && $v['delivered_num'] <= $goods_value['TOBE_DELIVERED_NUM']) {

                        $num_sum_sku['SHIPPED_NUM'] = $goods_value['SHIPPED_NUM'] + $v['delivered_num'];
                        $num_sum_sku['TOBE_DELIVERED_NUM'] = $goods_value['TOBE_DELIVERED_NUM'] - $v['delivered_num'];
                        $goods_sku_arr[$v['sku_id']][$goods_key]['SHIPPED_NUM'] += $v['delivered_num'];
                        $goods_sku_arr[$v['sku_id']][$goods_key]['TOBE_DELIVERED_NUM'] -= $v['delivered_num'];
                        $v['delivered_num'] = 0;
                        $upd_sum += $v['delivered_num'];
                    }
                }
            }

        }
        return [$upd_sum, $goods_sku_arr];

    }


    //批量生成法务审核通过状态的供应商
    public function batchMakeSupplier(){
        $company=[
            'N001244725','N001244724','N001244723','N001244722','N001244721','N001244707','N001244709','N001244710','N001244711','N001244714',
            'N001244715','N001244716','N001244717','N001244704','N001244705','N001244706','N001244703','N001244702','N001240200','N001240300','N001240700',
            'N001240800','N001241100','N001241200','N001241300','N001241400','N001241500','N001241700','N001241800','N001242100','N001242200','N001242700','N001242900',
            'N001243000','N001243400','N001243600','N001243700','N001243800','N001243900','N001244000','N001244100','N001244200','N001244400','N001244500','N001244600'];

        $company_list = M('crm_company_management','tb_')->where(['our_company_cd'=>['in', $company]])->select();
        $codes = array_column($company_list, 'our_company_cd');
        //查出已经插入的
        $supplier_model = M('crm_sp_supplier','tb_');
        $exists_supplier = $supplier_model->field('SP_CHARTER_NO')->where(['SP_CHARTER_NO'=>['in', $codes]])->select();
        $exists_supplier_no = array_column($exists_supplier,'SP_CHARTER_NO');

        $audit_model = M('ms_forensic_audit','tb_');

        foreach ($company_list as $key => $value){
            if(!in_array($value['our_company_cd'], $exists_supplier_no)){
                $supplier_model->startTrans();
                //供应商表
                $supplier_insert['SP_CHARTER_NO'] = $value['our_company_cd'];  //营业执照/个人证件号
                $supplier_insert['SP_NAME'] = cdVal($value['our_company_cd']); //供应商中文名
                $supplier_insert['SP_NAME_EN'] = $value['our_company_en'];    //供应商英文名
                $supplier_insert['SP_ADDR1'] = $value['reg_country'];   //注册地址国别
                $supplier_insert['SP_ADDR3'] = $value['reg_province'];   //注册地址省
                $supplier_insert['SP_ADDR4'] = $value['reg_city'];   //注册地址市
                $supplier_insert['SP_ADDR5'] = $value['reg_country'];   //办公地址国家
                $supplier_insert['SP_ADDR7'] = $value['reg_province'];  //办公地址省
                $supplier_insert['SP_ADDR8'] = $value['reg_city'];    //办公地址市
                $supplier_insert['COMPANY_ADDR_INFO'] = $value['reg_address']; //详细地址
                $supplier_insert['COPANY_TYPE_CD'] = 'N001190600'; //企业类型 ->其他 的码值
                $supplier_insert['SP_YEAR_SCALE_CD'] = 'N001200600'; //供应商年业务规模 ->100M以上 的码值
                $supplier_insert['SP_TEAM_CD'] = 'N001292400'; //采购团队 ->all 的码值
                $supplier_insert['SP_JS_TEAM_CD'] = 'N001302200'; //采购团队 的码值
                $supplier_insert['SP_CAT_CD'] = 'N001510100'; //供货品类 ->其他 的码值
                $supplier_insert['COMPANY_MARKET_INFO'] = '-'; //描述与市场定位简述
                $supplier_insert['AUDIT_STATE'] = '2'; //审核状态 已审核

                $res1 = $supplier_model->add($supplier_insert);
                if(!$res1){
                    $a['res1'][] = $value;
                    $supplier_model->rollback();
                    continue;
                }

                //供应商法务审核表
                $audit_insert['EST_TIME'] = $value['register_time']; //注册时间
                $audit_insert['SP_CHARTER_NO'] = $value['our_company_cd']; //营业执照号
                $audit_insert['LG_REP'] = $value['legal_name'];  //法人
                $audit_insert['SHARE_NAME'] = $value['supervisor_name']; //股东名称
                $audit_insert['SUB_CAPITAL'] = '99999999'; //认缴资本
                $audit_insert['CURRENCY'] = 'N000590100'; //认缴资本的币种 USD
                $audit_insert['IS_HAVE_NAGETIVE_INFO'] = '0'; //是否有负面信息
                $audit_insert['RISK_RATING'] = '1'; //风险评级1 低风险

                $res2 = $audit_model->add($audit_insert);
                if(!$res2){
                    $a['res2'][] = $value;
                    $supplier_model->rollback();
                    continue;
                }
                if($res1 > 0 && $res2 > 0){
                    $supplier_model->commit();
                    echo ($key+1).": CODE: {$value['our_company_cd']} is success <br>";
                }
            }else{
                //供应商表
                $supplier_update['SP_CHARTER_NO'] = $value['our_company_cd'];  //营业执照/个人证件号
                $supplier_update['SP_NAME'] = cdVal($value['our_company_cd']); //供应商中文名
                $supplier_update['SP_NAME_EN'] = $value['our_company_en'];    //供应商英文名
                $supplier_update['SP_ADDR1'] = $value['reg_country'];   //注册地址国别
                $supplier_update['SP_ADDR3'] = $value['reg_province'];   //注册地址省
                $supplier_update['SP_ADDR4'] = $value['reg_city'];   //注册地址市
                $supplier_update['SP_ADDR5'] = $value['reg_country'];   //办公地址国家
                $supplier_update['SP_ADDR7'] = $value['reg_province'];  //办公地址省
                $supplier_update['SP_ADDR8'] = $value['reg_city'];    //办公地址市
                $supplier_update['COMPANY_ADDR_INFO'] = $value['reg_address']; //详细地址
                $supplier_update['COPANY_TYPE_CD'] = 'N001190600'; //企业类型 ->其他 的码值
                $supplier_update['SP_YEAR_SCALE_CD'] = 'N001200600'; //供应商年业务规模 ->100M以上 的码值
                $supplier_update['SP_TEAM_CD'] = 'N001292400'; //采购团队 ->all 的码值
                $supplier_update['SP_JS_TEAM_CD'] = 'N001302200'; //采购团队 的码值
                $supplier_update['SP_CAT_CD'] = 'N001510100'; //供货品类 ->其他 的码值
                $supplier_update['COMPANY_MARKET_INFO'] = '-'; //描述与市场定位简述
                $supplier_update['AUDIT_STATE'] = '2'; //审核状态 已审核

                $res1 = $supplier_model->where(['SP_CHARTER_NO'=>$value['our_company_cd']])->save($supplier_update);

                //供应商法务审核表
                $audit_update['EST_TIME'] = $value['register_time']; //注册时间
                $audit_update['SP_CHARTER_NO'] = $value['our_company_cd']; //营业执照号
                $audit_update['LG_REP'] = $value['legal_name'];  //法人
                $audit_update['SHARE_NAME'] = $value['supervisor_name']; //股东名称
                $audit_update['SUB_CAPITAL'] = '99999999'; //认缴资本
                $audit_update['CURRENCY'] = 'N000590100'; //认缴资本的币种 USD
                $audit_update['IS_HAVE_NAGETIVE_INFO'] = '0'; //是否有负面信息
                $audit_update['RISK_RATING'] = '1'; //风险评级1 低风险
                $res2 = $audit_model->where(['SP_CHARTER_NO'=>$value['our_company_cd']])->save($audit_update);
            }
        }
        printr($a);
    }

    //更新退款订单号
    public function updateRefundData ()
    {
        $model = new Model();
        $refund_model = M('op_order_refund', 'tb_');
        $payment_model = M('pur_payment_audit', 'tb_');
        $sql = "SELECT
                oo.ORDER_ID AS op_order_id,
                oo.ORDER_NO AS op_order_no,
                oor.order_id,
                oor.order_no,
                oor.after_sale_no,
                oor.payment_audit_id,
                oor.store_id
            FROM
                tb_op_order_refund oor
                LEFT JOIN tb_op_order oo ON oo.order_id = oor.order_id 
                AND oo.PLAT_CD = oor.platform_cd 
            WHERE
                oor.order_no != oo.ORDER_NO 
                AND oor.status_code IN ( 'N002800012', 'N002800013', 'N002800009' ) limit 100";
        $list = $model->query($sql);
        foreach ($list as $v) {
            $res1[] = $refund_model->where(['after_sale_no' => $v['after_sale_no']])->save(['order_no' => $v['op_order_no']]);
            if (empty($v['store_id']) || empty($v['payment_audit_id'])) {
                continue;
            }
            $res2[] = $payment_model->where(['id' => $v['payment_audit_id']])->save(['platform_order_no' => $v['op_order_no']]);
        }
        v([$res1,$res2]);
    }

    //释放调拨残次品出库占用
    public function releaseAlloGoodsOccupy()
    {
        header("Content-type: text/html; charset=utf-8");
        $api_request_data = '[{"skuId":"8111772601","gudsId":"81117726","orderId":"DB202102240002","isUseSendNet":2,"operatorId":"9779","num":0,"releaseNum":0,"brokenNum":40,"releaseBrokenNum":0,"deliveryWarehouse":"N000689453","saleTeamCode":"N001283301"}]';
        $api_request_data = json_decode($api_request_data, true);
        $res = (new WmsModel())->transferOutLibraryNew($api_request_data);
        Logs(['request' => $api_request_data, 'response' => $res], __FUNCTION__, __CLASS__);
        v($res);
    }


    //删除无效售后单
    public function deleteInvalidOrderReturn ()
    {
        $order_id = I('order_id');
        if (empty($order_id)) {
            $order_id = 'f7455e40-3303-424c-8b0a-2acd2bb88b85';
        }
        $operation_model = M('op_order_return', 'tb_');
        $data = $operation_model->where(['order_id' => $order_id, 'status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_INVALID])->select();
        if (empty($data)) die('order_id error');
        $res = $operation_model->where(['order_id' => $order_id, 'status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_INVALID])->delete();
        Logs([json_encode($data), $res], 'repair', 'deleteInvalidOrderReturn');
        var_dump($res);
    }




    //ODM商标，一条数据国际分类字段会多个则需要根据分类拆成多个拆成多条记录
    public function odmDataReSet(){
        $sql = "select a.*,a.img_url as detail_img_url,b.*,b.img_url as base_img_url from tb_trademark_detail a left join tb_trademark_base b on a.trademark_base_id = b.id where a.international_type like '%/%'  order by a.trademark_base_id desc";
        $all_odm_info = M()->query($sql);
        $base_model = M('trademark_base', 'tb_');
        $detail_model = M('trademark_detail', 'tb_');

        foreach($all_odm_info as $ak=>$av){
            $international_type = explode('/', $av['international_type']);
            foreach($international_type as $ik=>$iv) {
                $insert_detail = [];
                $insert_base = [];
                $trademark_no = date('Ymd') . TbWmsNmIncrementModel::generateNo('TMNO');//生成流水号
                $detail_model->startTrans();
                $insert_base['trademark_name'] = $av['trademark_name'];
                $insert_base['trademark_no'] = $trademark_no;
                $insert_base['trademark_code'] = $av['trademark_code'];
                $insert_base['img_url'] = $av['base_img_url'];
                $insert_base['trademark_type'] = $av['trademark_type'];
                $insert_base['is_delete_state'] = $av['is_delete_state'];
                $insert_base['created_by'] = $av['created_by'];
                $insert_base['created_at'] = $av['created_at'];
                $insert_base['updated_at'] = $av['updated_at'];
                $insert_base['updated_by'] = $av['updated_by'];
                $insert_base['protect_period'] = $av['protect_period'];
                $insert_base['register_apply_no'] = $av['register_apply_no'];
                $res1 = $base_model->add($insert_base);
                $detail_ids = [];
                if ($res1 > 0) {
                    $base_id = $base_model->getLastInsID();
                    $insert_detail['trademark_base_id'] = $base_id;
                    $insert_detail['img_url'] = $av['detail_img_url'];
                    $insert_detail['country_code'] = $av['country_code'];
                    $insert_detail['company_code'] = $av['company_code'];
                    $insert_detail['company_name'] = $av['company_name'];
                    $insert_detail['register_code'] = $av['register_code'];
                    $insert_detail['apply_code'] = $av['apply_code'];
                    $insert_detail['international_type'] = $iv;
                    $insert_detail['goods'] = $av['goods'];
                    $insert_detail['goods_en'] = $av['goods_en'];
                    $insert_detail['is_delete_state'] = $av['is_delete_state'];
                    $insert_detail['applied_date'] = $av['applied_date'];
                    $insert_detail['applicant_name'] = $av['applicant_name'];
                    $insert_detail['applicant_name_en'] = $av['applicant_name_en'];
                    $insert_detail['applicant_address'] = $av['applicant_address'];
                    $insert_detail['applicant_address_en'] = $av['applicant_address_en'];
                    $insert_detail['initial_review_date'] = $av['initial_review_date'];
                    $insert_detail['register_date'] = $av['register_date'];
                    $insert_detail['trademark_type'] = $av['trademark_type'];
                    $insert_detail['trademark_type_en'] = $av['trademark_type_en'];
                    $insert_detail['exclusive_period'] = $av['exclusive_period'];
                    $insert_detail['inter_register_date'] = $av['inter_register_date'];
                    $insert_detail['late_specified_date'] = $av['late_specified_date'];
                    $insert_detail['priority_date'] = $av['priority_date'];
                    $insert_detail['agent'] = $av['agent'];
                    $insert_detail['agent_en'] = $av['agent_en'];
                    $insert_detail['current_state'] = $av['current_state'];
                    $insert_detail['current_state_en'] = $av['current_state_en'];
                    $insert_detail['remark'] = $av['remark'];
                    $insert_detail['created_by'] = $av['created_by'];
                    $insert_detail['created_at'] = $av['created_at'];
                    $insert_detail['updated_by'] = $av['updated_by'];
                    $insert_detail['updated_at'] = $av['updated_at'];
                    $insert_detail['apply_period'] = $av['apply_period'];
                    $insert_detail['apply_price'] = $av['apply_price'];
                    $insert_detail['effective_start'] = $av['effective_start'];
                    $insert_detail['effective_end'] = $av['effective_end'];
                    $insert_detail['attachment'] = $av['attachment'];
                    $insert_detail['apply_currency'] = $av['apply_currency'];
                    $res2 = $detail_model->add($insert_detail);
                    $detail_ids[] = $detail_model->getLastInsID();
                    if($res2 > 0){
                        $detail_model->commit();
                        $res3 = $base_model->where('id = '.$av['trademark_base_id'])->delete();
                        $res4 = $detail_model->where('trademark_base_id = '.$av['trademark_base_id'])->delete();
                        echo "base_id ".$av['trademark_base_id']."has separate into ".implode($detail_ids,',')."<br>";
                    }else{
                        $detail_model->rollback();
                    }
                } else {
                    $detail_model->rollback();
                }
            }
        }
    }



    //ODM商标，一条数据国际分类字段会多个则需要根据分类拆成多个拆成多条记录之后，商品/服务字段也要拆成多条记录，且根据id找出他的实际对应的商品/服务数据
    public function odmDataReSetGoods(){
        $detail_model = M('trademark_detail', 'tb_');
        $all_odm_info = $detail_model->order('id desc')->select();
        //$all_odm_info = $detail_model->order('id desc')->where('id = 3296')->select();
        foreach($all_odm_info as $ak=>$av){
            $international_type = $av['international_type'];
            $goods = $av['goods'];
            preg_match("/\n*($international_type.*)\n*/", $goods,$match);
            if(!empty($match)){
                $update['goods'] = trim($match[0]);
                $res = $detail_model->where('id = '.$av['id'])->save($update);
                if($res > 0){
                    echo "id ".$av['id']." and international_type $international_type has set goods = ".$match[0];
                }
            }else{
                continue;
            }
        }
    }


    //ODM商标编号修复
    public function odmUpdateTrademarkNo(){
        $base_model = M('trademark_base', 'tb_');
        $all_odm = $base_model->where("trademark_no is null or trademark_no = ''")->order('id desc')->select();
        foreach($all_odm as $ak=>$av){
            $save_base_data['trademark_no'] =  date("Ymd",time()).TbWmsNmIncrementModel::generateNo('TN');//生成注册编号;
            $res = $base_model->where('id = '.$av['id'])->save($save_base_data);
            if($res > 0){
                echo "id :".$av['id']." make trademark_no success: {$save_base_data['trademark_no']} <br>";
            }
        }
    }

    
    //ODM商标，国家商标保护期限数据修复，美国5年，其余10年
    public function odmDataReSetProtectedPeriod(){
        $detail_model = M('trademark_detail', 'tb_');
        $base_model = M('trademark_base', 'tb_');
        $all_odm_info = $detail_model->order('id desc')->select();
        foreach($all_odm_info as $ak=>$av){
            $update['protect_period'] = $av['country_code'] == 334 ? 5 : 10;
            $res = $base_model->where('id = '.$av['trademark_base_id'])->save($update);
            if($res > 0){
                echo "trademark_base_id： ".$av['trademark_base_id']." has been set protect_period：".$update['protect_period']."年<br>";
            }

        }
    }

    public function updateAttrReviewer()
    {
        $model      = new Model();
        $attr_model = M('wms_allo_attribution', 'tb_');
        $log_model  = M('wms_allo_attribution_review_log', 'tb_');
        $log_data   = [];
        $list       = $attr_model->select();
        $review_by  = array_unique(array_column($list, 'review_by'));
        $cancel_by  = array_unique(array_column($list, 'cancel_by'));
        $users      = array_merge($review_by, $cancel_by);
        $users      = array_unique($users);
        $review_map = [];
        foreach ($users as $v) {
            $review_map[$v] = DataModel::getUserIdByName($v);
        }
        $model->startTrans();
        foreach ($list as $item) {
            if ($log_model->where(['allo_attribution_id' => $item['id']])->count()) {
                continue;
            }
            if ($item['review_type_cd'] == 'N003000001') {
                //待审核单子设置当前审核人
                $review_arr = explode('>', $item['reviewer_by']);
                if (count($review_arr) == 1) {
                    $save_res = $attr_model->where(['id' => $item['id']])->save(['review_by' => $item['reviewer_by']]);
                }
                if (false === $save_res) {
                    $model->rollback();
                    v('update review_by failed');
                }
            }
            if ($item['review_by'] && $item['review_type_cd'] != 'N003000001') {
                $log_data[] = [
                    'allo_attribution_id' => $item['id'],
                    'review_user'         => $item['review_by'],
                    'review_user_id'      => $review_map[$item['review_by']] ?: null,
                    'review_type_cd'      => $item['review_type_cd'],
                    'review_time'         => $item['review_at'] ?: $item['updated_at']
                ];
            } else if ($item['cancel_by']) {
                //已取消
                $log_data[] = [
                    'allo_attribution_id' => $item['id'],
                    'review_user'         => $item['cancel_by'],
                    'review_user_id'      => $review_map[$item['cancel_by']] ?: null,
                    'review_type_cd'      => $item['review_type_cd'],
                    'review_time'         => $item['cancel_at'] ?: $item['updated_at']
                ];
            }
        }
        if ($log_data) {
            if (!$log_model->addAll($log_data)) {
                $model->rollback();
                v('add failed');
            }
        }
        if (false === $attr_model->where(['review_type_cd' => ['neq', 'N003000001']])->save(['review_by' => NULL])) {
            $model->rollback();
            v('update failed');
        }
        $model->commit();
        v('success');
    }
    
    public function updateB2bLogisticsEstimatCost()
    {
        $model = new Model();
        $list = $model->table('tb_b2b_info')->select();
        $model->startTrans();
        $empty_demand = $empty_demand_profit = [];
        foreach ($list as $item) {
            $logistics_estimat_cost = 0;
            if (empty($item['rebate_rate'])) {
                $logistics_estimat_cost = $item['logistics_estimat'];
            } else {
                $demand = M('sell_demand', 'tb_')->where(['demand_code'=>$item['PO_ID']])->find();
                if (empty($demand)) {
                    $empty_demand[] = $item;
                    continue;
                }
                $demand_profit = M('sell_demand_profit', 'tb_')->where(['demand_id'=>$demand['id']])->find();
                if (empty($demand_profit)) {
                    $empty_demand_profit[] = $item;
                    continue;
                }
                $pur_cost = $spot_cost = 0.00;
                //采购部分扣除返利
                if ($demand_profit['total_cost'] > 0) {
                    if ($demand_profit['revenue'] > 0) {
                        $pur_cost = bcsub($demand_profit['total_cost'], $demand_profit['revenue'] * $item['rebate_rate'], 2);
                    } else {
                        $pur_cost = $demand_profit['total_cost'];
                    }
                }

                //现货部分扣除返利
                if ($demand_profit['sale_cost_spot'] > 0) {
                    if ($demand_profit['revenue_spot'] > 0) {
                        $spot_cost = bcsub($demand_profit['sale_cost_spot'], $demand_profit['revenue_spot'] * $item['rebate_rate'], 2);
                    } else {
                        $spot_cost = $demand_profit['sale_cost_spot'];
                    }
                }
                $logistics_estimat_cost = $pur_cost + $spot_cost;
            }
            if ($logistics_estimat_cost > 0) {
                $res = $model->table('tb_b2b_info')->where(['ID' => $item['ID']])->save(['logistics_estimat_cost' => $logistics_estimat_cost]);
                if (false === $res) {
                    $model->rollback();
                    v('failed');
                }
            }
        }
        if (!empty($empty_demand)) {
            $model->rollback();
            echo 'empty demand';
            v($empty_demand);
        }
        if (!empty($empty_demand_profit)) {
            $model->rollback();
            echo 'empty demand profit';
            v($empty_demand_profit);
        }
        $model->commit();
        v('success');
    }

}
