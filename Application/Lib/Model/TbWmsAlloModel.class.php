<?php


/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/1/11
 * Time: 13:25
 */

class TbWmsAlloModel extends BaseModel
{
    const ALLO_WAIT_AUDIT = 'N001970100'; // 待审核
    const ALLO_REMOVE = 'N001970600'; // 已撤回
    const ALLO_WAIT_OUTGOIN = 'N001970200'; // 待出库
    const ALLO_WAIT_STORAGE = 'N001970300'; // 待入库
    const ALLO_WAIT_JOB = 'N001970602'; // 待作业
    const ALLO_IN_TRANSIT = 'N001970603'; // 运输中
    const ALLO_WAIT_REFUSE = 'N001970500'; // 已拒绝
    const ALLO_WAIT_REPORT = 'N000950200'; // 报损出库
    const ALLO_SUCCESS = 'N001970400'; // 调拨成功
    const ALLO_OUTGOING = 'N000950600'; // 收发类别，调拨出库
    const ALLO_STORAGE = 'N000940500'; // 收发类别，调拨入库
    const ALLO_TYPE_AUDIT = 1;            // 审核调拨
    const ALLO_TYPE_UNAUDIT = 2;            // 非审核调拨
    const ALLO_TODO_SUBMIT = 'N001970601'; // 待提交

    public $params;
    protected $trueTableName = 'tb_wms_allo';
    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user', 'getName', Model::MODEL_INSERT, 'callback'],
        ['update_time', 'getTime', Model::MODEL_UPDATE, 'callback'],
        ['update_user', 'getName', Model::MODEL_UPDATE, 'callback'],
    ];
    protected $_link = [
        'child' => [
            'mapping_type' => HAS_MANY,
            'class_name' => 'TbWmsAlloChild',
            'foreign_key' => 'allo_id',
            'mapping_name' => 'child',
            'mapping_key' => 'id',
        ]
    ];
    private $_allocationData = [];
    private $_alloType = null;
    private $_processData = [];
    private $_processChildData = [];
    private $_requestData = [];
    protected $_requestUri = 'batch/update_occupy.json';
    private $_prefixAllo = 'ALLO_DB';
    private $send_net_warehouse_cds = [
        'N000688700',
        'N000688800',
    ];

    public function __construct($name = '')
    {
        parent::__construct($name);
    }

    private $errorOrderResponse = [
        'DB201904280011',
        'DB201904150011'
    ];

    # 调拨优化涉及团队 cd
    public static $allot_optimize_teams = [
        'N001282800'
    ];

    /**
     * 调拨单列表页查询筛选
     *
     * @param $params
     *
     * @return array 返回满足框架要求的筛选条件组合
     */
    public static function search($params, $is_new_allo = false)
    {
       
        if ($params['transfer_type']) {
            $conditions ['t3.transfer_type'] = ['eq', $params['transfer_type']];
        } else {
            $conditions ['t3.transfer_type'] = ['eq', 0];
        }
        if ($params ['no_type'] == 1 and $params ['allo_no']) {
            $conditions ['allo_no'] = ['eq', $params ['allo_no']];
        } elseif ($params ['no_type'] == 2 and $params ['allo_no']) {
            $conditions ['t3.sku'] = ['like', '%' . $params ['allo_no'] . '%'];
        }
        if ($params ['allo_type']) {
            $conditions ['t3.allo_type'] = ['eq', $params ['allo_type']];
        }
        if ($params ['allo_in_team']) {
            $conditions ['t3.allo_in_team'] = ['eq', $params ['allo_in_team']];
        }
        if ($params ['allo_in_warehouse']) {
            $conditions ['t3.allo_in_warehouse'] = ['eq', $params ['allo_in_warehouse']];
        }
        if ($params ['allo_out_team']) {
            $conditions ['t3.allo_out_team'] = ['eq', $params ['allo_out_team']];
        }
        if ($params ['allo_out_warehouse']) {
            $conditions ['t3.allo_out_warehouse'] = ['eq', $params ['allo_out_warehouse']];
        }
        if ($params ['lunch_start_time']) {
            $conditions['t3.create_time'] = array(array('egt', $params ['lunch_start_time'] . ' 00:00:00'), array('elt', $params ['lunch_end_time'] . ' 59:59:59'), 'and');
        }
        if ($params ['update_start_time']) {
            $conditions['t3.update_time'] = array(array('egt', $params ['update_start_time'] . ' 00:00:00'), array('elt', $params ['update_end_time'] . ' 59:59:59'), 'and');
        }
        if ($params ['state'] && is_array($params ['state'] )) {
            $conditions ['t3.state'] = ['in', $params ['state']];
        }
        if ($params ['transfer_warehousing_by']) {
            $conditions ['dw_in.transfer_warehousing_by'] = ['like', '%' . $params['transfer_warehousing_by'] . '%'];
        }
        if ($params ['transfer_out_library_by']) {
            $conditions ['dw_out.transfer_out_library_by'] = ['like', '%' . $params['transfer_out_library_by'] . '%'];
        }
        if ($is_new_allo) {
            if (null !== $params['allo_out_status'] && '' !== $params['allo_out_status']) {
                $conditions['tb_wms_allo_new_status.allo_out_status'] = ['eq', $params['allo_out_status']];
            }
            if (null !== $params['allo_in_status'] && '' !== $params['allo_in_status']) {
                $conditions['tb_wms_allo_new_status.allo_in_status'] = ['eq', $params['allo_in_status']];
            }
            #upc 可能对应多个sku编码 用or连接
            if($params['sku_id']){
                $upcToSkuArr = SkuModel::upcTosku($params['sku_id'], 'loose');
                $tmpSkuArr = [];
                $tmpSkuArr[] = 'or';
                foreach($upcToSkuArr  as $v){
                    array_unshift($tmpSkuArr,['LIKE','%'.$v.'%']);
                }
                #搜索条件可能为sku  兼容进去
                array_unshift($tmpSkuArr, ['LIKE', '%' . $params['sku_id'] . '%']);
                $conditions['t3.sku'] = $tmpSkuArr;


            }
           
            // $params['sku_id'] and $conditions['t3.sku'] = ['LIKE', '%' . SkuModel::upcTosku($params['sku_id'], 'strict_mixing') . '%'];
            
            $params['create_user'] and $conditions['ba.M_NAME'] = ['LIKE', "%{$params['create_user']}%"];
            $params['auditor_by'] and $conditions['ETC'] = ['LIKE', "%{$params['auditor_by']}%"];
            $params['task_launch_by'] and $conditions['dw_in.task_launch_by'] = ['LIKE', "%{$params['task_launch_by']}%"];
        }
        # 调拨管理优化修改
        if(isset($params['node_type']) && is_array($params['node_type']) && $params['node_type']) {
            $conditions["tws_out_stock.logistics_state"] = ["in", $params['node_type']];
        }
        if(isset($params['small_team']) &&  is_array($params['small_team']) && $params['small_team']) {
            $conditions["tws_process.small_team_cd"] = ["in", $params['small_team']];
        }
        if(isset($params['cabinet_number']) && $params['cabinet_number']) {
            $conditions["tws_out_stock.cabinet_number"] = ["eq", $params['cabinet_number']];
        }
        if(isset($params['transport_company_id']) && is_array($params['transport_company_id']) && $params['transport_company_id']) {
            $conditions["tws_out_stock.transport_company_id"] = ["in", $params['transport_company_id']];
        }

        return $conditions;

    }

    /**
     * 流程中查询筛选
     *
     * @param $request
     *
     * @return array 返回满足框架要求的筛选条件组合
     */
    //优化后的
    public static function searchProcess($request, $isAlloAll = false)
    {
        $conditions = [];
        if ('N002440100' == $request ['vir_type_cd']) {
            $conditions ['vir_type_cd'] = 'N002440100';;
        }
        if ($request ['sku'] and !is_array($request ['sku'])) {
            if ($isAlloAll == false) {
                $conditions ['_string'] = '(a.SKU_ID like "%' . $request ['sku'] . '%" OR t9.upc_id LIKE "%' . $request ['sku'] . '%" OR find_in_set("'.$request ['sku'].'",t9.upc_more))';
            } else {
                $conditions ['a.SKU_ID'] = ['like', '%' . $request ['sku'] . '%'];
            }
        }
        if ($request ['selected_state']) {
            if ($request ['selected_state'] == 2) {
                $conditions ['a3.num'] = ['gt', 0];
            } else {
                if ($conditions ['_string']) {
                    $conditions ['_string'] = $conditions ['_string'] . ' AND ( a3.num = 0 or a3.num IS NULL )';
                } else {
                    $conditions ['_string'] = '( a3.num = 0 or a3.num IS NULL )';
                }
            }
        }
        $conditions ['a.total_inventory_total'] = ['gt', 0];
        return $conditions;
    }

        /**
     * 新建库存归属第二步数据展示条件组装（EXCEL导入专用）
     * 条件加到子查询，减少查询数据集合
     * @param $request
     * @param $processInfo
     * @param $and_vir_type_where
     * @param $is_all 是否全部调拨
     * @return mixed
     */
    public static function searchStockSubQueryProcess($request, $processInfo, $and_vir_type_where, $is_all = false)
    {
        if ($is_all) {
            $where ['_string'] = 't1.bill_id = t3.id and t1.vir_type <> "N002440200"';
        } else {
            $where ['_string'] = "t1.stream_id = t2.id {$and_vir_type_where} AND t1.bill_id = t3.id AND t2.bill_id = t3.id AND t1.vir_type not in ('N002440200', 'N002410200')";
        }
        if ($request ['SP_TEAM_CD']) {
            $where [' t1.purchase_team_code'] = ['eq', $request ['SP_TEAM_CD']];
        }
        if ($request ['purchasing_team_cd']) {
            $where [' t1.purchase_team_code'] = ['eq', $request ['purchasing_team_cd']];
        }
        if ((2 == $processInfo['transfer_type'] && 'N002990003' == $processInfo['change_type_cd'])) {
            if (empty($processInfo['old'])) {
                $where [' t1.purchase_team_code'] = ['EXP', "IS NULL OR  t1.purchase_team_code  =''"];
            } else {
                $where [' t1.purchase_team_code'] = ['eq', $processInfo['old']];
            }
        }

        if ($request ['shop_id']) {
            $where ['t3.ascription_store'] = ['eq', $request ['shop_id']];
        }
        if (2 == $processInfo['transfer_type'] && 'N002990001' == $processInfo['change_type_cd']) {
            if (empty($processInfo['old'])) {
                $where ['t3.ascription_store'] = ['EXP', "IS NULL OR t3.ascription_store  =''"];
            } else {
                $where ['t3.ascription_store'] = ['eq', $processInfo['old']];
            }
        }

        //新增条形码筛选逻辑
        if ((!empty($request ['GUDS_OPT_UPC_ID']) and is_array($request ['GUDS_OPT_UPC_ID']))) {
            //供应商 = GP
            $map['supplier'] = 'N002680001';
            $complex= [];
            $complex['upc_id'] = ['in', $request ['GUDS_OPT_UPC_ID']];
            $insets = [];
            if(is_array($request['GUDS_OPT_UPC_ID'])) {
                foreach ($request['GUDS_OPT_UPC_ID'] as $upc_id) {
                    $insets[] = "FIND_IN_SET('{$upc_id}',tab1.upc_more)";
                }
                $find_in_set_str = implode(' OR ', $insets);
                $complex['_logic'] = 'or';
                $complex['_string'] = "({$find_in_set_str})";
                $map['_complex'] = $complex;
            }

            $skuProduct = PMSSearchModel::skuProduct($map);
            $skuIds = array_column($skuProduct, 'sku_id');
            //根据条形码筛选出sku列表并且和导入的sku列表合并去重
            $request ['sku'] = array_unique(array_values(array_merge($request ['sku'], $skuIds)));
        }
        if ($request ['sku'] and is_array($request ['sku'])) {
            $where ['t1.SKU_ID'] = ['in', $request ['sku']];
        }
        if ($request ['guds_nm']) {
            $sku = SkuModel::titleToSku($request ['guds_nm']);
            if ($request ['sku']) {
                $where ['t1.SKU_ID'] = ['in', array_merge((array)$request ['sku'], (array)$sku)];
            } else {
                $where ['t1.SKU_ID'] = ['in', $sku];
            }
        }
        if ($request ['out_team']) {
            $where ['t3.warehouse_id'] = ['eq', $request ['out_team']];
        }

        if (empty($request ['warehouse_code']) and empty($request ['sale_team_code'])) {
            $map ['t1.sale_team_code'] = ['neq', $request ['into_team']];
            $map ['_logic'] = 'or';
            $map ['t3.warehouse_id'] = ['neq', $request ['into_warehouse']];
            $where ['_complex'] = $map;
        } else {
            if (($request ['warehouse_code'] == $request ['into_warehouse']) and ($request ['sale_team_code'] == $request ['into_team'])) {
                $where ['t1.sale_team_code'] = ['eq', ''];
                $where ['t3.warehouse_id'] = ['eq', ''];
            } else {
                if ($request ['sale_team_code'] or $request ['into_team']) {
                    $where ['t1.sale_team_code'] = ['eq', $request ['sale_team_code']];
                    if ($request ['sale_team_code'] and ($request ['sale_team_code'] != $request ['into_team'])) {
                        $where ['t1.sale_team_code'] = ['eq', $request ['sale_team_code']];
                    }
                    if (empty($request ['sale_team_code']) and $request ['into_team']) {
                        $where ['t1.sale_team_code'] = ['neq', $request ['into_team']];
                    }
                }

                if ($request ['warehouse_code'] or $request ['into_warehouse']) {
                    $where ['t3.warehouse_id'] = ['eq', $request ['warehouse_code']];
                    if ($request ['warehouse_code'] and ($request ['warehouse_code'] != $request ['into_warehouse'])) {
                        $where ['t3.warehouse_id'] = ['eq', $request ['warehouse_code']];
                    }
                    if (empty($request ['warehouse_code']) and $request ['into_warehouse']) {
                        $where ['t3.warehouse_id'] = ['neq', $request ['into_warehouse']];
                    }
                }

                //如果只选择了调出仓库没有选择调出团队，并且调出团队不等于调入团队，则查询全部团队
                $w_code = $request ['warehouse_code'];
                if ($where['t1.sale_team_code'][0] == 'neq' && ($w_code && ($w_code != $request['into_warehouse']))) {
                    unset($where ['t1.sale_team_code']);
                }

                //如果只选择了调出团队没有选择调出仓库，并且调出仓库不等于调入仓库，则查询全部仓库
                $s_code = $request ['sale_team_code'];
                if ($where['t3.warehouse_id'][0] == 'neq' && ($s_code && ($s_code != $request['into_team']))) {
                    unset($where ['t3.warehouse_id']);
                }
            }
        }
        return $where;
    }

    /**
     * 新建调拨第二步数据展示条件组装
     * 条件加到子查询，减少查询数据集合
     * @param $request
     * @param $processInfo
     * @param $and_vir_type_where
     * @param $is_all 是否全部调拨
     * @return mixed
     */
    public static function searchSubQueryProcess($request, $processInfo, $and_vir_type_where, $is_all = false)
    {
        if ($is_all) {
            $where ['_string'] = 't1.bill_id = t3.id and t1.vir_type <> "N002440200"';
        } else {
            $where ['_string'] = "t1.stream_id = t2.id {$and_vir_type_where} AND t1.bill_id = t3.id AND t2.bill_id = t3.id AND t1.vir_type not in ('N002440200', 'N002410200')";
        }
        if ($request ['SP_TEAM_CD']) {
            $where [' t1.purchase_team_code'] = ['eq', $request ['SP_TEAM_CD']];
        }
        if ($request ['purchasing_team_cd']) {
            $where [' t1.purchase_team_code'] = ['eq', $request ['purchasing_team_cd']];
        }
        if ((2 == $processInfo['transfer_type'] && 'N002990003' == $processInfo['change_type_cd'])) {
            if (empty($processInfo['old'])) {
                $where [' t1.purchase_team_code'] = ['EXP', "IS NULL OR  t1.purchase_team_code  =''"];
            } else {
                $where [' t1.purchase_team_code'] = ['eq', $processInfo['old']];
            }
        }

        if ($request ['shop_id']) {
            $where ['t3.ascription_store'] = ['eq', $request ['shop_id']];
        }
        if (2 == $processInfo['transfer_type'] && 'N002990001' == $processInfo['change_type_cd']) {
            if (empty($processInfo['old'])) {
                $where ['t3.ascription_store'] = ['EXP', "IS NULL OR t3.ascription_store  =''"];
            } else {
                $where ['t3.ascription_store'] = ['eq', $processInfo['old']];
            }
        }

        //新增条形码筛选逻辑
        if ((!empty($request ['GUDS_OPT_UPC_ID']) and is_array($request ['GUDS_OPT_UPC_ID']))) {
            //供应商 = GP
            $map['supplier'] = 'N002680001';
            $complex= [];
            $complex['upc_id'] = ['in', $request ['GUDS_OPT_UPC_ID']];
            $insets = [];
            if(is_array($request['GUDS_OPT_UPC_ID'])) {
                foreach ($request['GUDS_OPT_UPC_ID'] as $upc_id) {
                    $insets[] = "FIND_IN_SET('{$upc_id}',tab1.upc_more)";
                }
                $find_in_set_str = implode(' OR ', $insets);
                $complex['_logic'] = 'or';
                $complex['_string'] = "({$find_in_set_str})";
                $map['_complex'] = $complex;
            }

            $skuProduct = PMSSearchModel::skuProduct($map);
            $skuIds = array_column($skuProduct, 'sku_id');
            //根据条形码筛选出sku列表并且和导入的sku列表合并去重
            $request ['sku'] = array_unique(array_values(array_merge($request ['sku'], $skuIds)));
        }
        if ($request ['sku'] and is_array($request ['sku'])) {
            $where ['t1.SKU_ID'] = ['in', $request ['sku']];
        }
        if ($request ['guds_nm']) {
            $sku = SkuModel::titleToSku($request ['guds_nm']);
            if ($request ['sku']) {
                $where ['t1.SKU_ID'] = ['in', array_merge((array)$request ['sku'], (array)$sku)];
            } else {
                $where ['t1.SKU_ID'] = ['in', $sku];
            }
        }
        if ($request ['out_team']) {
            $where ['t3.warehouse_id'] = ['eq', $request ['out_team']];
        }

        if (empty($request ['warehouse_code']) and empty($request ['sale_team_code'])) {
            $map ['t1.sale_team_code'] = ['neq', $request ['into_team']];
            $map ['_logic'] = 'or';
            $map ['t3.warehouse_id'] = ['neq', $request ['into_warehouse']];
            $where ['_complex'] = $map;
        } else {
            if (($request ['warehouse_code'] == $request ['into_warehouse']) and ($request ['sale_team_code'] == $request ['into_team'])) {
                $where ['t1.sale_team_code'] = ['eq', ''];
                $where ['t3.warehouse_id'] = ['eq', ''];
            } else {
                if ($request ['sale_team_code'] or $request ['into_team']) {
                    $where ['t1.sale_team_code'] = ['eq', $request ['sale_team_code']];
                    if ($request ['sale_team_code'] and ($request ['sale_team_code'] != $request ['into_team'])) {
                        $where ['t1.sale_team_code'] = ['eq', $request ['sale_team_code']];
                    }
                    if (empty($request ['sale_team_code']) and $request ['into_team']) {
                        $where ['t1.sale_team_code'] = ['neq', $request ['into_team']];
                    }
                }

                if ($request ['warehouse_code'] or $request ['into_warehouse']) {
                    $where ['t3.warehouse_id'] = ['eq', $request ['warehouse_code']];
                    if ($request ['warehouse_code'] and ($request ['warehouse_code'] != $request ['into_warehouse'])) {
                        $where ['t3.warehouse_id'] = ['eq', $request ['warehouse_code']];
                    }
                    if (empty($request ['warehouse_code']) and $request ['into_warehouse']) {
                        $where ['t3.warehouse_id'] = ['neq', $request ['into_warehouse']];
                    }
                }

                //如果只选择了调出仓库没有选择调出团队，并且调出团队不等于调入团队，则查询全部团队
                $w_code = $request ['warehouse_code'];
                if ($where['t1.sale_team_code'][0] == 'neq' && ($w_code && ($w_code != $request['into_warehouse']))) {
                    unset($where ['t1.sale_team_code']);
                }

                //如果只选择了调出团队没有选择调出仓库，并且调出仓库不等于调入仓库，则查询全部仓库
                $s_code = $request ['sale_team_code'];
                if ($where['t3.warehouse_id'][0] == 'neq' && ($s_code && ($s_code != $request['into_team']))) {
                    unset($where ['t3.warehouse_id']);
                }
            }
        }
        //归属店铺 array 小团队上线后移除
        /*if (!empty($request['ascription_store'])) {
            //不包含无归属店铺
            if (!in_array(0, $request ['ascription_store'])) {
                $where ['t3.ascription_store'] = ['in', $request ['ascription_store']];
            } else {
                if (count($request['ascription_store']) == 1) {
                    if ($where ['_string']) {
                        $where ['_string'] = $where ['_string'] . ' AND ( t3.ascription_store = 0 or t3.ascription_store = "" or t3.ascription_store IS NULL )';
                    } else {
                        $where ['_string'] = ' ( t3.ascription_store = 0 or t3.ascription_store = "" or t3.ascription_store IS NULL )';
                    }
                } else {
                    if ($where ['_string']) {
                        $where ['_string'] .= ' AND ((t3.ascription_store = 0 or t3.ascription_store = "" or t3.ascription_store IS NULL) or (t3.ascription_store in (' . implode(',', $request['ascription_store']) . ')))';
                    } else {
                        $where ['_string'] = ' ((t3.ascription_store = 0 or t3.ascription_store = "" or t3.ascription_store IS NULL) or (t3.ascription_store in (' . implode(',', $request['ascription_store']) . ')))';
                    }
                }

            }
        }*/
        if (!empty($request['sell_small_team_cd'])) {
            //不包含无小团队
            if (is_array($request['sell_small_team_cd'])) {
                if (!in_array('0', $request['sell_small_team_cd'])) {
                    $where['t1.small_sale_team_code'] = ['in', $request['sell_small_team_cd']];
                } else {
                    if (count($request['sell_small_team_cd']) == 1) {
                        if ($where['_string']) {
                            $where['_string'] = $where['_string'] . ' AND ( t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL )';
                        } else {
                            $where['_string'] = ' ( t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL )';
                        }
                    } else {
                        if ($where['_string']) {
                            $where['_string'] .= ' AND ((t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL) or (t1.small_sale_team_code in (' . implode(',', $request['sell_small_team_cd']) . ')))';
                        } else {
                            $where['_string'] = ' (( t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL) or (t1.small_sale_team_code in (' . implode(',', $request['sell_small_team_cd']) . ')))';
                        }
                    }
                }
            }
        }
        if (is_string($request['sell_small_team_cd'])) {
            if (!empty($request['sell_small_team_cd'])) {
                $where['t1.small_sale_team_code'] = $request['sell_small_team_cd'];
            } else {
                if ($where['_string']) {
                    $where['_string'] = $where['_string'] . ' AND ( t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL )';
                } else {
                    $where['_string'] = ' ( t1.small_sale_team_code = "" or t1.small_sale_team_code IS NULL )';
                }
            }
        }
        return $where;
    }

    /**
     * 验证是审核调拨还是非审核调拨
     *
     * @return boolean 返回 ture or false
     */
    public function _validate()
    {
        if ($this->params ['allo_type'] == SELF::ALLO_TYPE_AUDIT or $this->params ['allo_type'] == SELF::ALLO_TYPE_UNAUDIT) {
            $this->_alloType = $this->params ['allo_type'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * 新增调拨
     *
     * @param array $params 流程编号
     *
     * @return array 返回处理结果
     */
    public
    function createAllo($params)
    {
        if ($processInfo = TbWmsAlloProcessModel::getProcessInfo($params)) {
            $uuid = $processInfo ['uuid'];
            $this->getProcessData($uuid);
            if (!$this->getProcessChildData($uuid)) {
                return $this->returnInfo('', L('异常：[当前流程无调拨数据]'), 0);
            }
            if (0 == $processInfo['transfer_type'] && !$this->validateAlloWarehouse()) {
                return $this->returnInfo('', L('跨仓库调拨，请在新调拨模块操作！'), 0);
            }
            if (2 == $processInfo['transfer_type'] && !$this->validateAlloAttribution($processInfo)) {
                return $this->returnInfo('', L('归属调拨数据不正确'), 0);
            }
            $this->params = $params;
            //参数验证
            if (!$this->_validate())
                return $this->returnInfo('', L('调拨类型异常：[无效的调拨类型]'), 0);
            $this->startTrans();
            try {
                if (2 == $processInfo['transfer_type']) {
                    //库存归属
                    $change_order_no = 'GSDD' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('qualification', 4);
                    $allo_id = $this->generateAlloAttribution($change_order_no);
                } else {
                    //新调拨
                    $into_team  = $this->_processData['into_team'];
                    if($into_team == 'N001282800'){
                        #jerry团队才走这个
                        #筛选出无小团队的  走库存归属占用
                      
                        $attr = [];
                        foreach($this->_processChildData as $value){
                            if(empty($value['out_small_team']) && substr($value['sku_id'],0,1) != 9){
                                array_push($attr,$value);
                            }
                        }
                      
                        if(!empty($attr)){
                            $change_order_no = 'GSDD' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('qualification', 4);
                             #需要更新allo_id
                            $attr_id = $this->generateAlloAttributionByAllo($change_order_no,'', $attr);
                           
                            
                        }
                        
                        #处理调拨数据
                        $allo_id = $this->generateAlloOrder(1, $attr_id);

                    }else{
                        $allo_id = $this->generateAlloOrder();
                    }
                    #$this->_processChildData

                   
                    // $allo_id = $this->generateAlloOrder();
                }
                //关闭流程
                TbWmsAlloProcessModel::closeProcess($processInfo ['uuid']);
                $data['allo_id'] = $allo_id;
                $this->returnInfo($data, '', '');
                if (2 != $processInfo['transfer_type']) {
                    $temp_auto = $this->autoParseAllo($allo_id);
                    if (!$temp_auto) {
                        throw new Exception('调拨自动操作错误');
                    }
                } else {
                    $temp_auto = ['data' => ['allo_id' => $allo_id], 'info' => L('成功：[流程关闭，生成调拨单]'), 'status' => 1];
                }
                $this->commit();
                return $temp_auto;
                $info = L('成功：[流程关闭，生成调拨单]');
                $status = 1;
            } catch (Exception $e) {
                $this->rollback();
                $data = '';
                $info = $e->getMessage();
                $status = 0;
            }
        } else {
            $data = '';
            $info = L('请求异常') . '：[' . L('流程已失效，请重新创建') . ']';
            $status = 0;
        }
        $this->setResponseData(array_merge($this->getResponseData(), array('local' => array('data' => $data, 'info' => $info, 'status' => $status))));
        return $this->returnInfo($data, $info, $status);
    }

    /**
     * 编辑调拨
     *
     * @param array $params 流程编号
     *
     * @return array 返回处理结果
     */
    public
    function editAllo($params)
    {
        if ($processInfo = TbWmsAlloProcessModel::getProcessInfo($params)) {
            $uuid = $processInfo ['uuid'];
            $this->getProcessData($uuid);
            if (!$this->getProcessChildData($uuid)) {
                return $this->returnInfo('', L('异常：[当前流程无调拨数据]'), 0);
            }
            if (0 == $processInfo['transfer_type'] && !$this->validateAlloWarehouse()) {
                return $this->returnInfo('', L('跨仓库调拨，请在新调拨模块操作！'), 0);
            }
            if (2 == $processInfo['transfer_type'] && !$this->validateAlloAttribution($processInfo)) {
                return $this->returnInfo('', L('归属调拨数据不正确'), 0);
            }
            $this->params = $params;
            //参数验证
            if (!$this->_validate())
                return $this->returnInfo('', L('调拨类型异常：[无效的调拨类型]'), 0);
            $allo_id = $params['allo_id'];
            $this->startTrans();
            try {
                if (2 == $processInfo['transfer_type']) {
                    //$change_order_no = 'GSDD' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('qualification', 4);
                    //$allo_id = $this->generateAlloAttribution($change_order_no);
                } else {
                    //编辑调拨
                    $into_team  = $this->_processData['into_team'];
                    if ($into_team == 'N001282800') {
                        #jerry团队才走这个
                        #筛选出无小团队的  走库存归属占用

                        $attr = [];
                        foreach ($this->_processChildData as $value) {
                            if (empty($value['out_small_team']) && substr($value['sku_id'], 0, 1) != 9) {
                                array_push($attr, $value);
                            }
                        }

                        if (!empty($attr)) {
                            #删除之前的归属占用
                            $change_order_no = 'GSDD' . date(Ymd) . TbWmsNmIncrementModel::generateCustomNo('qualification', 4);
                            #需要更新allo_id
                            $attr_id = $this->generateAlloAttributionByAllo($change_order_no, '', $attr, $allo_id);
                            if($attr_id){
                                $AllocationExtendAttributionService = new AllocationExtendAttributionService();
                                $AllocationExtendAttributionService->updateAttr($attr_id, $allo_id);
                            }   
                            
                        }

                        #处理调拨数据
                        // $allo_id = $this->generateAlloOrder(1, $attr_id);
                        $res = $this->editAlloOrder($allo_id,1);
                    }else{
                        $res = $this->editAlloOrder($allo_id);
                    }
                   
                }
                //关闭流程
                TbWmsAlloProcessModel::closeProcess($processInfo ['uuid']);
                $data['allo_id'] = $allo_id;
                $this->returnInfo($data, '', '');
                if (2 != $processInfo['transfer_type']) {
                    $temp_auto = $this->autoParseAllo($allo_id);
                    if (!$temp_auto) {
                        throw new Exception('调拨自动操作错误');
                    }
                } else {
                    $temp_auto = ['data' => ['allo_id' => $allo_id], 'info' => L('成功：[流程关闭，编辑调拨单]'), 'status' => 1];
                }
                $this->commit();
                return $temp_auto;
                $info = L('成功：[流程关闭，编辑调拨单]');
                $status = 1;
            } catch (Exception $e) {
                $this->rollback();
                $data = '';
                $info = $e->getMessage();
                $status = 0;
            }
        } else {
            $data = '';
            $info = L('请求异常') . '：[' . L('流程已失效，请重新编辑') . ']';
            $status = 0;
        }
        $this->setResponseData(array_merge($this->getResponseData(), array('local' => array('data' => $data, 'info' => $info, 'status' => $status))));
        return $this->returnInfo($data, $info, $status);
    }

    private
    function validateAlloWarehouse()
    {
        $child_out_warehouses = array_unique(array_column($this->_processChildData, 'out_warehouse'));
        if (1 < count($child_out_warehouses) || !in_array($this->_processData['into_warehouse'], $child_out_warehouses)) {
            return false;
        }
        return true;
    }

    private
    function validateAlloAttribution($processInfo)
    {
        if (empty($processInfo['change_type_cd']) || empty($processInfo['attribution_team_cd'])) {
            return false;
        }
        return true;
    }

    private
    function generateAlloAttribution($change_order_no, $batches = [])
    {
        if (empty($this->_processChildData) or is_null($this->_processData)) {
            throw new Exception(L('无调拨数据'));
        }
        foreach ($this->_processChildData as $processChildDatum) {
            $batches[] = [
                'batchId' => $processChildDatum['batch_id'],
                'num' => $processChildDatum['num'],
            ];
        }
        $batch = [
            'orderId' => $change_order_no,
            'virType' => CodeModel::$stock_cd,
            'type' => 0,
            'batches' => $batches,
        ];
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        list($attribution, $reviewer_by) = $AllocationExtendAttributionService->create($change_order_no, $this->_processData, $this->_processChildData);
        $res_api = ApiModel::occupyBatch($batch);
        if (2000 !== $res_api['code'] || 2000 !== $res_api['data']['occupy'][0]['code']) {
            @SentinelModel::addAbnormal('归属调拨占用错误', L('归属调拨占用错误: API ') . $res_api['msg'], $res_api);
            throw new Exception(L('归属调拨占用错误: API ') . $res_api['msg']);
        }
        (new ReviewMsgTpl())->sendWeChatAttributionTransfer($attribution, $reviewer_by);
        return $attribution['id'];
    }
    #调拨中归属占用
    private
    function generateAlloAttributionByAllo($change_order_no, $batches = [],$attr, $allo_id = 0)
    {
        // if (empty($this->_processChildData) or is_null($this->_processData)) {
        //     throw new Exception(L('无调拨数据'));
        // }
        #根据正次品开始请求
        $typeReply = [];
        $newRequest = [];
        foreach ($attr as $processChildDatum) {
            if(in_array($processChildDatum['positive_defective_type_cd'],$typeReply)){
                $newRequest[$processChildDatum['positive_defective_type_cd']][] = $processChildDatum;
            }else{
                $newRequest[$processChildDatum['positive_defective_type_cd']] = [];
                $newRequest[$processChildDatum['positive_defective_type_cd']][] = $processChildDatum;
                array_push($typeReply, $processChildDatum['positive_defective_type_cd']);
            }
          
        }
        $allRequest = [];
        foreach($newRequest as $key=>$typeData){
            $batches = [];
            foreach($typeData as $value){
            
                $batches[] = [
                    'skuId' => $value['sku_id'],
                    'num' => $value['num'],
                    "warehouseCd" => $this->_processData['allo_out_warehouse'],
                    "saleTeamCd" => $this->_processData['into_team'],
                ];
                
            }
            $allRequest[] = [
                'orderId' => $change_order_no,
                'virType' => $key,
                'type' => 0,
                'batches' => $batches,
            ];

        }
      
        // foreach ($attr as $processChildDatum) {
        //     $batches[] = [
        //         'skuId' => $processChildDatum['sku_id'],
        //         'num' => $processChildDatum['num'],
        //         "warehouseCd"=>$this->_processData['allo_out_warehouse'],
        //         "saleTeamCd" => $this->_processData['into_team'],
        //     ];
        // }
        // $batch = [
        //     'orderId' => $change_order_no,
        //     'virType' => CodeModel::$stock_cd,
        //     'type' => 0,
        //     'batches' => $batches,
        // ];
        #创建记录
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        list($attribution, $reviewer_by) = $AllocationExtendAttributionService->createByAllo($change_order_no, $this->_processData, $this->_processChildData, $allo_id);
       
        
        $res_api = ApiModel::occupyBatch($allRequest,2);
        if (2000 !== $res_api['code'] || 2000 !== $res_api['data']['occupy'][0]['code']) {
            @SentinelModel::addAbnormal('归属调拨占用错误', L('归属调拨占用错误: API ') . $res_api['msg'], $res_api);
            throw new Exception(L('归属调拨占用错误: API ') . $res_api['msg']);
        }
        $attrSku = [];
        foreach($res_api['data']['occupy'] as $occupy){
            foreach($occupy['data']['batches'] as $batches){
                $attrSku[] = [
                    'sku_id'=> $batches['skuId'],
                    'num'=> $batches['num'],
                    'batch_id' => $batches['batchId'],
                ];
            }
        }
        $AllocationExtendAttributionService->createAlloAttributionSku($attribution['id'],$attrSku);
        #需要对方返回sku id 和批次 和数量  才能写入库存占用表
        // `allo_attribution_id`,`sku_id`,`batch_id`,`transfer_number`,`created_by`,`updated_by`
        return $attribution['id'];
    }
    /**
     * 调拨自动化操作
     * 1:仓内调拨全部自动化完成，不需要审核
     * 2:仓间调拨非审核模式自动完成，审核模式需要带确认
     */
    public
    function autoParseAllo($allo_id = null)
    {
        $model = new TbWmsAlloChildModel();
        try {
            if ($this->_alloType == SELF::ALLO_TYPE_AUDIT) {
                //审核调拨：全部都得审核，审核完后，如果是库内则直接完成。库间继续后面的流程
            } else {
                //非审核调拨：库间的调拨全部为待出库、库内的调拨全部直接完成。
                foreach ($this->allos as $k => $v) {
                    // 库间直接完成
                    if ($v ['allo_in_warehouse'] == $v ['allo_out_warehouse']) {
                        $ids [] = $v ['id'];
                    }
                }
                //$ids = $this->alloIds;
            }
            if ($ids) {
                $return = $this->autoOutGoing($ids);
                $return = $this->autoStorage($ids);
            } else {
                $return = ['data' => ['allo_id' => $allo_id], 'info' => L('成功：[流程关闭，生成调拨单]'), 'status' => 1];
            }
        } catch (Exception $e) {
            return $this->returnInfo('', $e->getMessage(), 0);
        }
        return $return;
    }

    /**
     * 审核完成，库内自动出入库
     */
    public
    function autoEmailOutgoingAndStorage($id)
    {
        try {
            $conditions ['id'] = ['in', $id];
            $ret = $this->where($conditions)->select();
            if ($ret) {
                foreach ($ret as $k => $v) {
                    if ($v ['allo_in_warehouse'] == $v ['allo_out_warehouse']) {
                        $ids [] = $v ['id'];
                    }
                }
            }
            if ($ids) {
                $this->autoOutGoing($ids);
                $return = $this->autoStorage($ids);
            } else {
                $return = ['data' => '', 'info' => L('操作完成'), 'status' => 1];
            }
        } catch (Exception $e) {
            return $this->returnInfo('', $e->getMessage(), 0);
        }
        return $return;
    }

    /**
     * 自动出库
     *
     * @param array $ids 调拨单 id
     *
     * @return array
     */
    public
    function autoOutGoing($ids)
    {
        $conditions ['id'] = ['in', $ids];
        $ret = $this->where($conditions)->select();
        $model = new TbWmsAlloChildModel();
        foreach ($ret as $k => $v) {
            //非审核，所有流程自动化完成
            $v ['outgoing_date'] = date('Y-m-d H:i:s', time());
            $data ['ret'] = $v;
            $child = $model->where(['allo_id' => ['eq', $v ['id']]])->select();
            foreach ($child as $key => &$value) {
                $value ['actual_outgoing_num'] = $value ['demand_allo_num'];
                unset($value);
            }
            $data ['child'] = $child;
            $return = $this->outGoing($data);
        }
        return $return;

    }

    /**
     * 自动入库
     *
     * @param array $ids 调拨单id
     *
     * @return array
     */
    public
    function autoStorage($ids)
    {
        $conditions ['id'] = ['in', $ids];
        $ret = $this->where($conditions)->select();
        $model = new TbWmsAlloChildModel();
        foreach ($ret as $k => $v) {
            //非审核，所有流程自动化完成
            $v ['storage_date'] = date('Y-m-d H:i:s', time());
            $v ['estimate_arrive_date'] = date('Y-m-d H:i:s', time());
            $data ['ret'] = $v;
            $child = $model->where(['allo_id' => ['eq', $v ['id']]])->select();
            foreach ($child as $key => &$value) {
                $value ['actual_storage_num'] = $value ['demand_allo_num'];
                unset($value);
            }
            $data ['child'] = $child;
            $return = $this->storage($data);;
        }
        return $return;
    }

    public
        $alloIds = [];
    public
        $allos = [];

    /**
     * 调拨单生成逻辑
     * 1：按仓库分
     * 2：按销售团队分
     * 3：生成按条件1、2规则的拨单
     *
     * @return boolean 返回 true 或者
     * @throws Exception 数据写入一出场
     */
    public
    function generateAlloOrder($is_attr = 0,$attr_id = 0)
    {
        if (empty($this->_processChildData) or is_null($this->_processData)) {
            throw new Exception(L('无调拨数据'));
        }
        foreach ($this->_processChildData as $k => &$v) {
            $v ['into_warehouse'] = $this->_processData ['into_warehouse'];
            $v ['into_team'] = $this->_processData ['into_team'];
            unset($v ['id']);
            unset($v ['uuid']);
            $allocationData [$v ['out_warehouse']][$v ['out_team']][] = $v;
            unset($v);
        }
        $processData = [];
        foreach ($allocationData as $outWarehouseCode => $value) {
            foreach ($value as $outTeam => $v) {
                $tmp = [];
                $tmp ['allo']['state'] = $this->_alloType == self::ALLO_TYPE_AUDIT ? self::ALLO_WAIT_AUDIT : self::ALLO_WAIT_OUTGOIN;
                if (1 == $this->_processData['transfer_type']) {
                    $tmp ['allo']['state'] = self::ALLO_TODO_SUBMIT;
                }
                $tmp ['allo']['allo_no'] = 'DB' . date('Ymd', time()) . TbWmsNmIncrementModel::generateNo($this->_prefixAllo);
                $tmp ['allo']['allo_type'] = $this->_alloType;
                $tmp ['allo']['allo_in_team'] = $this->_processData ['into_team'];
                $tmp ['allo']['allo_in_warehouse'] = $this->_processData ['into_warehouse'];
                $tmp ['allo']['allo_out_team'] = $outTeam;
                $tmp ['allo']['allo_out_warehouse'] = $outWarehouseCode;
                $tmp ['allo']['process_id'] = $this->_processData ['id'];
                $tmp ['allo']['transfer_type'] = $this->_processData['transfer_type'];
                $tmp ['allo']['transfer_use_type'] = $this->_processData['transfer_use_type'];

                #查询出入仓是否都为国内仓
                $allo_in_warehouse_place = M('wms_warehouse', 'tb_')->where(['CD' => $tmp['allo']['allo_in_warehouse']])->getField('place');
                $allo_out_warehouse_place = M('wms_warehouse', 'tb_')->where(['CD' => $tmp['allo']['allo_out_warehouse']])->getField('place');
                if (strpos($allo_in_warehouse_place, '(CN)中国') !== false && strpos($allo_out_warehouse_place, '(CN)中国') !== false) {
                    $tmp['allo']['is_cn_warehouse'] = 1;
                }
                if (strpos($allo_in_warehouse_place, '中国') !== false && strpos($allo_out_warehouse_place, '中国') !== false) {
                    $tmp['allo']['is_cn_warehouse'] = 1;
                }
                if (strpos($allo_in_warehouse_place, '(CN)') !== false && strpos($allo_out_warehouse_place, '(CN)') !== false) {
                    $tmp['allo']['is_cn_warehouse'] = 1;
                }
                if (!empty($this->_processData['use_fawang_logistics'])) {
                    $tmp ['allo']['use_fawang_logistics'] = $this->_processData['use_fawang_logistics'];
                }
                foreach ($v as $j => &$s) {
                    unset($s ['out_team']);
                    unset($s ['out_warehouse']);
                    unset($s ['into_warehouse']);
                    unset($s ['into_team']);
                    $s ['demand_allo_num'] = (int)$s ['num'];
                    if ($s ['num'] === 0) {
                        throw new Exception(L('调拨数量不能为0'));
                    }
                    unset($s ['num']);
                    unset($s);
                }
                $tmp ['alloChild'][] = $v;
                $processData [] = $tmp;
                unset($v);
            }
            unset($tmp);
        }
        foreach ($processData as $key => $value) {
            if (1 == $this->_processData['transfer_type']) {
                $value['allo']['use_fawang_logistics'] = $this->_processData['use_fawang_logistics'];
            } else {
                if (in_array($value['allo']['allo_out_warehouse'], $this->send_net_warehouse_cds)) {
                    if ($value['allo']['allo_out_warehouse'] == $value['allo']['allo_in_warehouse']) {
                        $value['allo']['use_fawang_logistics'] = 2;
                    } else {
                        $value['allo']['use_fawang_logistics'] = 1;
                    }
                }
            }
            if (!$alloId = $this->add($this->create($value['allo']))) {
                throw new Exception(L('数据异常: [写入调拨单数据异常') . $this->getDbError() . ']');
            }
            if (1 == $this->_processData['transfer_type']) {
                $allo_new_status = [
                    'allo_id' => $alloId,
                    'allo_out_status' => 0,
                    'allo_in_status' => 1,
                    'created_by' => DataModel::userNamePinyin(),
                    'updated_by' => DataModel::userNamePinyin(),
                ];
                $this->table('tb_wms_allo_new_status')->add($allo_new_status);
                $global_allo_id = $alloId;
            }
            $this->allos   [] = array_merge($value ['allo'], ['id' => $alloId]);
            $this->alloIds [] = $alloId;
            foreach ($value['alloChild'] as $k => $v) {

                foreach ($v as $s => &$j) {

                    $j ['allo_id'] = $alloId;
                    unset($j ['allo_id']);
                    $j ['gudsId'] = substr($j ['sku_id'], 0, 8);
                    $j ['skuId'] = $j ['sku_id'];
                    $j ['storeId'] = $j ['out_store'];
                    if ('N002440400' == $j['positive_defective_type_cd']) {
                        $j ['num'] = 0;
                        $j ['brokenNum'] = (int)$j ['demand_allo_num'];
                    } else {
                        $j ['num'] = (int)$j ['demand_allo_num'];
                    }
                    $j ['smallSaleTeamCode'] = $j ['out_small_team'];
                    unset($j['sku_id']);
                    unset($j['out_store']);
                    unset($j['out_small_team']);

                    $j ['orderId'] = $value ['allo']['allo_no'];//$this->getLastInsID();
                    $j ['checkId'] = $alloId;//$this->getLastInsID();
                    $j ['deliveryWarehouse'] = $value ['allo']['allo_out_warehouse'];
                    $j ['operatorId'] = $this->getName();
                    $j ['saleTeamCode'] = $value ['allo']['allo_out_team'];
                    unset($j['demand_allo_num']);
                    $requestData [] = $j;

                    unset($j['checkId']);
                    unset($j);
                }

            }
        }
        $batchIds = $childData = [];
        $attr_request_data = [];
       
        if($is_attr){
            foreach($requestData as $key=>$value){
                if(empty($value['smallSaleTeamCode']) && substr($value['skuId'], 0, 1) != 9 ){
                    unset($requestData[$key]);
                    $value['num'] = $value['num'] + $value['brokenNum'];
                    array_push($attr_request_data,$value);
                }
            }
        }
       
        if($attr_request_data){
          
            foreach($attr_request_data as $value){
                $tmp = [];
                $tmp['allo_id'] = $global_allo_id;
                $tmp['sku_id'] = $value['skuId'];
                $new_guds_info = $tmp;
                $new_guds_info['allo_id'] = $global_allo_id;
                $new_guds_info['created_by'] = $new_guds_info['updated_by'] = DataModel::userNamePinyin();
                $new_guds_infos[$tmp['sku_id']] = $new_guds_info;
                $tmp['demand_allo_num'] = $value['num'];
                $tmp['batch_id'] = '';
                $tmp['deadline_date_for_use'] = NULL;
                $tmp['is_write_sku_num'] = 0;

                
                // $batchIds[] = $v['id'];
                // $batchIdAndSkus[$v['id']] = $value['data']['skuId'];
                $childData[] = $tmp;
            }
           

        }
        
        if($requestData){
           
            $this->setRequestData($requestData);
            // 请求接口验证
            $this->requestApi();
            $response = $this->getResponseData();


            foreach ($response['data']['occupy'] as $key => $value) {
                foreach ($value['data']['occupyBatches'] as $k => $v) {
                    $tmp = [];
                    $tmp['allo_id'] = $value['data']['checkId'];
                    $tmp['sku_id'] = $value['data']['skuId'];
                    $new_guds_info = $tmp;
                    $new_guds_info['allo_id'] = $global_allo_id;
                    $new_guds_info['created_by'] = $new_guds_info['updated_by'] = DataModel::userNamePinyin();
                    $new_guds_infos[$tmp['sku_id']] = $new_guds_info;
                    $tmp['demand_allo_num'] = $v['num'];
                    $tmp['batch_id'] = $v['id'];
                    $tmp['deadline_date_for_use'] = NULL;
                    $tmp['is_write_sku_num'] = 1;
                    $batchIds[] = $v['id'];
                    $batchIdAndSkus[$v['id']] = $value['data']['skuId'];
                    $childData[] = $tmp;
                }
            }
        }
       
       
        if ($deadLines = $this->getDeadLineData($batchIds, $batchIdAndSkus)) {
            foreach ($childData as $k => $v) {
                $childData[$k]['deadline_date_for_use'] = $deadLines [$v ['batch_id']];
            }
        }
        if ($childData) {
            $mer = null;
            // 到期日合并
            foreach ($childData as $key => &$value) {
                $map = '';
                $map = $value ['deadline_date_for_use'] . $value ['sku_id'] . $value ['allo_id'];
                if (isset($mer [$map])) {
                    $value ['demand_allo_num'] += $childData [$mer [$map]]['demand_allo_num'];
                    $value ['batch_id'] = $value ['batch_id'] . ',' . $childData [$mer [$map]]['batch_id'];
                    unset($childData [$mer [$map]]);
                    $mer [$map] = $key;
                } else {
                    $mer [$map] = $key;
                }
                unset($value);
            }
            $model = new TbWmsAlloChildModel();
            if (!$model->addAll($childData)) {
                throw new Exception(L('数据异常: [写入调拨子表数据异常') . $model->getDbError() . ']');
            }
            if (1 == $this->_processData['transfer_type']) {
                if (!$model->table('tb_wms_allo_new_guds_infos')->addAll(array_values($new_guds_infos))) {
                    throw new Exception(L('数据异常: [写入新调拨商品数据异常') . $model->getDbError() . ']');
                }
            }
        }
        $AllocationExtendAttributionService = new AllocationExtendAttributionService();
        $AllocationExtendAttributionService->updateAttr($attr_id, $global_allo_id);
        if (1 == $this->_processData['transfer_type']) {
            return $global_allo_id;
        }
        return true;
    }

    /**
     * 调拨单编辑逻辑
     * 1：按仓库分
     * 2：按销售团队分
     * 3：生成按条件1、2规则的拨单
     * @param int $allo_id
     * @return boolean 返回 true 或者
     * @throws Exception 数据写入一出场
     */
    public
    function editAlloOrder($allo_id,$is_attr = 0)
    {
        if (empty($this->_processChildData) or is_null($this->_processData)) {
            throw new Exception(L('无调拨流程数据'));
        }
        $where['id'] = $allo_id;
        $allo = $this->where($where)->find();
        if (empty($allo)) {
            throw new Exception(L('无调拨数据'));
        }
        $save['process_id'] = $this->_processData ['id'];
        $res = $this->where($where)->save($save);
        if (false === $res) {
            throw new Exception(L('数据异常: [编辑调拨单数据异常') . $this->getDbError() . ']');
        }
        foreach ($this->_processChildData as $key => $value) {
            $j ['gudsId'] = substr($value ['sku_id'], 0, 8);
            $j ['skuId'] = $value ['sku_id'];
            $j ['storeId'] = $value ['out_store'];
            $j ['num'] = (int)$value ['num'];
            if ('N002440400' == $value['positive_defective_type_cd']) {
                $j ['brokenNum'] = (int)$value ['num'];
                $j ['num'] = 0;
            }
            $j ['smallSaleTeamCode'] = $value ['out_small_team'];
            $j ['orderId'] = $allo ['allo_no'];//$this->getLastInsID();
            $j ['checkId'] = $allo_id;//$this->getLastInsID();
            $j ['deliveryWarehouse'] = $allo ['allo_out_warehouse'];
            $j ['operatorId'] = $this->getName();
            $j ['saleTeamCode'] = $allo ['allo_out_team'];
            $requestData [] = $j;
        }
        $attr_request_data = [];
        $batchIds = $childData = [];
        if ($is_attr) {
            foreach ($requestData as $key => $value) {
                if (empty($value['smallSaleTeamCode']) && substr($value['skuId'],0,1) != 9) {
                    unset($requestData[$key]);
                    array_push($attr_request_data, $value);
                }
            }
        }
        
        
        
        //新调拨商品数据
        $model = new Model();
        $del_guds_where['allo_id'] = $allo_id;
        $allo_guds_infos = $model->table('tb_wms_allo_new_guds_infos')->where($del_guds_where)->select();
        $allo_guds_sku = [];
        foreach ($allo_guds_infos as $item) {
            $allo_guds_sku[$item['sku_id']] = $item;
        }
        if (count($requestData) > 0) {
            $this->setRequestData($requestData);
            // 请求接口验证
            $this->requestApi();
            $response = $this->getResponseData();
            foreach ($response['data']['occupy'] as $key => $value) {
                foreach ($value['data']['occupyBatches'] as $k => $v) {
                    $tmp = [];
                    $tmp['allo_id'] = $value['data']['checkId'];
                    $tmp['sku_id'] = $value['data']['skuId'];
                    $new_guds_info = $tmp;
                    $new_guds_info['created_by'] = $new_guds_info['updated_by'] = DataModel::userNamePinyin();
                    //原来就有的商品价格信息保留
                    if (isset($allo_guds_sku[$tmp['sku_id']])) {
                        $new_guds_info['tax_free_sales_unit_price'] = $allo_guds_sku[$tmp['sku_id']]['tax_free_sales_unit_price'];
                        $new_guds_info['tax_free_sales_unit_price_currency_cd'] = $allo_guds_sku[$tmp['sku_id']]['tax_free_sales_unit_price_currency_cd'];
                    } else {
                        $new_guds_info['tax_free_sales_unit_price'] = null;
                        $new_guds_info['tax_free_sales_unit_price_currency_cd'] = null;
                    }
                    $new_guds_infos[$tmp['sku_id']] = $new_guds_info;
                    $tmp['demand_allo_num'] = $v['num'];
                    $tmp['batch_id'] = $v['id'];
                    $tmp['deadline_date_for_use'] = NULL;
                    $tmp['is_write_sku_num'] = 1;
                    $batchIds[] = $v['id'];
                    $batchIdAndSkus[$v['id']] = $value['data']['skuId'];
                    $childData[] = $tmp;
                }
            }
        }
        if(count($attr_request_data) > 0){
            foreach ($attr_request_data as $k => $v) {
                $tmp = [];
                $tmp['allo_id'] = $allo_id;
                $tmp['sku_id'] = $v['skuId'];
                $new_guds_info = $tmp;
                $new_guds_info['created_by'] = $new_guds_info['updated_by'] = DataModel::userNamePinyin();
                //原来就有的商品价格信息保留
                if (isset($allo_guds_sku[$tmp['sku_id']])) {
                    $new_guds_info['tax_free_sales_unit_price'] = $allo_guds_sku[$tmp['sku_id']]['tax_free_sales_unit_price'];
                    $new_guds_info['tax_free_sales_unit_price_currency_cd'] = $allo_guds_sku[$tmp['sku_id']]['tax_free_sales_unit_price_currency_cd'];
                } else {
                    $new_guds_info['tax_free_sales_unit_price'] = null;
                    $new_guds_info['tax_free_sales_unit_price_currency_cd'] = null;
                }
                $new_guds_infos[$tmp['sku_id']] = $new_guds_info;
                $tmp['demand_allo_num'] = $v['num'];
                $tmp['batch_id'] = '';
                $tmp['deadline_date_for_use'] = NULL;
                $tmp['is_write_sku_num'] = 0;
                // $batchIds[] = $v['id'];
                // $batchIdAndSkus[$v['id']] = $value['data']['skuId'];
                $childData[] = $tmp;
            }
        }
        if ($deadLines = $this->getDeadLineData($batchIds, $batchIdAndSkus)) {
            foreach ($childData as $k => $v) {
                $childData[$k]['deadline_date_for_use'] = $deadLines [$v ['batch_id']];
            }
        }
        if ($childData) {
            $mer = null;
            // 到期日合并
            foreach ($childData as $key => &$value) {
                $map = $value ['deadline_date_for_use'] . $value ['sku_id'] . $value ['allo_id'];
                if (isset($mer [$map])) {
                    $value ['demand_allo_num'] += $childData [$mer [$map]]['demand_allo_num'];
                    $value ['batch_id'] = $value ['batch_id'] . ',' . $childData [$mer [$map]]['batch_id'];
                    unset($childData [$mer [$map]]);
                    $mer [$map] = $key;
                } else {
                    $mer [$map] = $key;
                }
                unset($value);
            }
            //调拨子单
            $model = new TbWmsAlloChildModel();
            //删除旧调拨子单
            $del_where['allo_id'] = $allo_id;;
            if (!$model->where($del_where)->delete()) {
                throw new Exception(L('数据异常: [删除旧调拨子单数据异常') . $model->getDbError() . ']');
            }
            //新增调拨子单
            if (!$model->addAll($childData)) {
                throw new Exception(L('数据异常: [写入新调拨子单数据异常') . $model->getDbError() . ']');
            }
            if (1 == $this->_processData['transfer_type']) {
                if (!$model->table('tb_wms_allo_new_guds_infos')->where($del_guds_where)->delete()) {
                    throw new Exception(L('数据异常: [删除旧新调拨商品数据异常') . $model->getDbError() . ']');
                }
                if (!$model->table('tb_wms_allo_new_guds_infos')->addAll(array_values($new_guds_infos))) {
                    throw new Exception(L('数据异常: [写入新调拨商品数据异常') . $model->getDbError() . ']');
                }
            }
        }
        if (1 == $this->_processData['transfer_type']) {
            return $allo_id;
        }
        return true;
    }

    /**
     * 到期日获取，接口返回的 batch_id 查询所有的 stream 数据，将到期日冗余到 tb_wms_allo_child 表中,页面展示不做关联查询
     *
     * @param array $batchIds 批次 id
     * @param array $batchIdAndSku 批次与 sku 关联的数组
     *
     * @return array|bool 返回以 batch_id 为 key，到期日为 value 的数据，或者返回 false
     * @throws Exception 未查询到到期日则抛出异常
     */
    public
    function getDeadLineData($batchIds, $batchIdAndSku)
    {
        $batchModel = new TbWmsBatchModel();
        $streamIds = $batchModel->where(['id' => ['in', $batchIds]])->getField('id, stream_id, batch_code, deadline_date_for_use');
        //$streamModel = new Model();
        //$conditions ['id'] = ['in', array_column($streamIds, 'stream_id')];
        //$deadLines = $streamModel->table('tb_wms_stream')->where($conditions)->getField('id, deadline_date_for_use');
        if ($streamIds) {
            $tmp = [];
            foreach ($batchIds as $key => $batch_id) {
                if (!$streamIds [$batch_id]['deadline_date_for_use']) {
                    $error [$batchIdAndSku [$batch_id]] = $streamIds [$batch_id]['batch_code'];
                } else {
                    $tmp [$batch_id] = $streamIds [$batch_id]['deadline_date_for_use'];
                }
            }
        } else {
            foreach ($batchIds as $key => $batch_id) {
                $error [$batchIdAndSku [$batch_id]] = $streamIds [$batch_id]['batch_code'];
            }
        }
        if ($error) {
            foreach ($error as $sku => $batch_code) {
                $error .= '[SKU: ' . $sku . '-' . $batch_code . L('批次') . ']';
            }
            //throw  new Exception($error . L('批次未查询到到期日，无法出库'));
        }

        return $tmp;
    }

    /**
     * 获得流程数据
     *
     * @param string $uuid
     *
     * @return array
     */
    public
    function getProcessData($uuid)
    {
        $processModel = new TbWmsAlloProcessModel();
        return $this->_processData = $processModel->where("uuid = '%s'", [$uuid])->find();
    }

    /**
     * 获得流程子数据
     *
     * @param string $uuid
     *
     * @return array
     */
    public
    function getProcessChildData($uuid)
    {
        $processChildModel = new TbWmsAlloProcessChildModel();
        return $this->_processChildData = $processChildModel->where("uuid = '%s' AND num > 0", [$uuid])->select();
    }

    /**
     * 接口验证
     * 占用库存
     */
    public
    function requestApi()
    {
        foreach ($this->getRequestData() as $key => $r) {
            $requestHeader ['data']['occupy'][] = $r;
        }
        $requestHeader ['data']['type'] = 1;
        $this->setRequestData($requestHeader);
        $response = $this->sendRequest($this->_requestUri);
        if ($response ['code'] != 2000)
            throw new Exception(L('远程请求异常') . '：[' . L($response['msg']) . ']');
    }

    /**
     * 接口请求
     *
     * @param string $uri
     *
     * @return array
     */
    public
    function sendRequest($uri, $time = 60)
    {
        $url = HOST_URL_API . '/' . $uri;
        $this->setRequestUrl($url);
        $orderId = $this->getRequestData()['data']['export'][0]['orderId'];
        Logs($orderId, __FUNCTION__, __CLASS__);
        if (in_array($orderId, $this->errorOrderResponse)) {
            $response = $this->errorOrderHadle();
        } else {
            $response = curl_get_json_time($url, json_encode($this->getRequestData()), $time);
        }
        $response = json_decode($response, true);
        ZUtils::saveLog(['url' => $url, 'req' => $this->getRequestData(), 'res' => $response], 'allocation_extend');
        $this->setResponseData($response);
        return $response;
    }

    private
    function errorOrderHadle()
    {
        return ['code' => 2000];
    }

    /**
     * 调拨单状态检查
     *
     * @param int $id 调拨单 id
     * @param string $state 调拨单状态码
     *
     * @return array|false  查询到返回数据，未查询到返回 false
     */
    public
    function checkAlloState($id, $state)
    {
        $conditions ['id'] = ['eq', $id];
        $conditions ['state'] = ['eq', $state];

        $ret = $this->where($conditions)->select();
        if ($ret)
            return $ret;
        else
            return false;
    }

    /**
     * 撤回
     */
    public
    function receive($params)
    {
        $model = new ReceiveModel();
        return $model->receive($params);
    }

    /**
     * 出库
     */
    public
    function outGoing($params)
    {
        $model = new OutGoingModel();
        return $model->outGoing($params);
    }

    /**
     * 入库
     */
    public
    function storage($params)
    {
        $model = new StorageModel();
        return $model->storage($params);
    }

    /**
     * 出入库单生成
     * 生成bill_id
     *
     * @param string $warehouse_code 仓库 code 码
     * @param string $sale_team_code 销售团队 code 码
     * @param string $type 出入库类型 N000940500 调拨入库 N000950600 调拨出库
     * @param string $linkBillId 关联单据号
     *
     * @return int|false 返回插入数据的 id,或者 false
     * @throws Exception
     */
    public
    function orderIOGenerate($warehouse_code, $sale_team_code, $type, $linkBillId = null)
    {
        $stock = A('Home/Stock');
        $bill ['bill_id'] = $type == 'N000940500' ? 'DBR' . date('Ymd', time()) . TbWmsNmIncrementModel::generateNo('OUTGOINGSTORAGE') : 'DBC' . date('Ymd', time()) . TbWmsNmIncrementModel::generateNo('OUTGOINGSTORAGE');
        $bill ['bill_type'] = $type;
        $bill ['link_bill_id'] = $linkBillId;
        $bill ['warehouse_rule'] = 2;
        $bill ['warehouse_id'] = $warehouse_code;
        $bill ['zd_user'] = $this->getName();
        $bill ['user_id'] = $this->getName();
        $bill ['zd_date'] = date('Y-m-d H:i:s', time());
        $bill ['bill_date'] = date('Y-m-d H:i:s', time());
        $bill ['SALE_TEAM'] = $sale_team_code;
        $bill ['batch_ids'] = null;
        $bill ['vir_type'] = 'N002440100';
        $bill ['relation_type'] = 'N002350100';
        $bill ['type'] = $type == 'N000940500' ? 1 : 0;
        $model = new TbWmsBillModel();
        if ($id = $model->add($bill)) {
            return $id;
        }

        if ($type == self::ALLO_OUTGOING)
            $msg = L('生成出库单失败');
        else
            $msg = L('生成入库单失败');

        throw new Exception($msg);
    }

    public
        $batchIdAndStreamIds = [];

    /**
     * 出入库单子数据生成
     *
     * @param array $batchIdsAndNum 以 batch_id 为 key, 出库数量为 value 的数组
     * @param int $billId 出入库单 id
     * @param array $allo 调拨数据
     *
     * @return boolean 写入子数据成功或者失败
     * @throws Exception 未查询到子数据、或者写入失败抛出异常
     */
    public
    function orderIOChildGenerate($batchIdsAndNum, $billId, $allo)
    {
        $batchModel = new TbWmsBatchModel();
        $batchIds = array_keys($batchIdsAndNum);
        $conditions ['id'] = ['in', $batchIds];
        // 获取历史streamId
        $batchAndStreamIds = $batchModel
            ->where($conditions)
            ->getField('stream_id, id');
        $streamModel = M('_wms_stream', 'tb_');
        // 获取 stream 相关信息
        $streams = $streamModel
            ->where(['id' => ['in', array_keys($batchAndStreamIds)]])
            ->select();
        if (!$streams) {
            throw new Exception(L('批次') . '[' . implode(',', $batchIds) . ']' . L('未查询到入库相关数据'));
        }
        foreach ($streams as $k => $value) {
            $r = null;
            $r ['bill_id'] = $billId;
            $r ['line_number'] = $value ['line_number'];
            $r ['goods_id'] = $value ['goods_id'];
            $r ['GSKU'] = $value ['GSKU'];
            $r ['should_num'] = $batchIdsAndNum [$batchAndStreamIds [$value ['id']]];
            $r ['send_num'] = $batchIdsAndNum [$batchAndStreamIds [$value ['id']]];
            $r ['warehouse_id'] = $value ['warehouse_id'];
            $r ['location_id'] = $value ['location_id'];
            $r ['batch'] = $batchAndStreamIds [$value ['id']];
            $r ['deadline_date_for_use'] = $value ['deadline_date_for_use'];
            $r ['unit_price_usd'] = $value ['unit_price_usd'];
            $r ['unit_price'] = $value ['unit_price'];
            $r ['no_unit_price'] = $value ['no_unit_price'];
            $r ['taxes'] = $value ['taxes'];
            $r ['unit_money'] = $value ['unitPrice'] * $batchIdsAndNum [$batchAndStreamIds [$value ['id']]];
            $r ['no_unit_money'] = $value ['noUnitPrice'] * $batchIdsAndNum [$batchAndStreamIds [$value ['id']]];
            $r ['duty'] = $value ['duty'];
            $r ['currency_id'] = $value ['currency_id'];
            $r ['give_status'] = $value ['give_status'];
            $r ['add_time'] = $value ['add_time'];
            $r ['digit'] = $value ['digit'];
            $r ['currency_time'] = $value ['currency_time'];
            $r ['up_flag'] = $value ['up_flag'];
            $r ['GSKU_back'] = $value ['GSKU_back'];
            $r ['outgoing_type'] = $value ['outgoing_type'];
            $r ['reported_loss_reason'] = $value ['reported_loss_reason'];
            $r ['pur_invoice_tax_rate'] = $value ['pur_invoice_tax_rate'];
            $r ['proportion_of_tax'] = $value ['proportion_of_tax'];
            $r ['storage_log_cost'] = $value ['storage_log_cost'];
            $r ['log_service_cost'] = $value ['log_service_cost'];
            $r ['pur_storage_date'] = $value ['pur_storage_date'];
            $r ['log_currency'] = $value ['log_currency'];
            $r ['create_time'] = date('Y-m-d H:i:s', time());
            $r ['tag'] = 0;
            $stream [] = $r;
        }

        if (!$streamModel->addAll($stream)) {
            trace($this->getLastSql(), 'OutgoingStreamLastExecSql');
            throw new Exception(L('生成出库字段数据失败'));
        }
    }

    /**
     * 返回类似于控制器异步请求所返回的数据类型
     *
     * @return array
     */
    public
    function returnInfo($data, $info, $status)
    {
        $this->_catchMe();
        return ['data' => $data, 'info' => $info, 'status' => $status];
    }

    public
    function weChatApprove($params)
    {
        $res['code'] = 2000;
        $res['msg'] = 'success';
        $res['data'] = [];
        $model = new TbWmsAlloModel();
        $ret = $model->where(['allo_no' => $params ['review']['order_no']])->find();
        if ($ret && $params['status'] == 1) {
            if ($ret ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $ret ['state'] = TbWmsAlloModel::ALLO_WAIT_OUTGOIN;
                $data = $model->create($ret, 2);
                if ($model->save($data)) {
                    $model->autoEmailOutgoingAndStorage((array)$ret ['id']);
                    $res['wechat'] = ['receipt' => L('您已同意调拨单号') . '：' . $params ['review']['order_no'] . L('的调拨申请')];
                } else {
                    $res['code'] = 3000;
                    $res['msg'] = 'fail';
                }
            } else {
                $res['code'] = 3000;
                $res['msg'] = '状态已改变，当前链接已失效';
            }
        } elseif ($ret && $params['status'] == 0) {
            if ($ret ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $receive = new TbWmsAlloModel();
                $query ['id'] = $ret ['id'];
                $query ['state'] = TbWmsAlloModel::ALLO_WAIT_REFUSE;
                $query ['info'] = L('已成功拒绝调拨请求');
                $ret = $receive->receive($query);
                $res['msg'] = $ret['info'];
                $res['wechat'] = ['receipt' => L('您已拒绝调拨单号') . '：' . $params ['review']['order_no'] . L('的调拨申请')];
            } else {
                $res['code'] = 3000;
                $res['msg'] = '状态已改变，当前链接已失效';
            }
        } else {
            $res['code'] = 3000;
            $res['msg'] = '无效参数';
        }
        return $res;
    }
}

/**
 * Class OutGoing
 * 出库逻辑
 */
class OutGoingModel extends TbWmsAlloModel
{
    protected $_requestUri = 'batch/allocate_export.json';

    public function outGoing($params)
    {
        try {
            $this->startTrans();
            $alloModel = new TbWmsAlloModel();
            $allo = $alloModel->create($params ['ret'], 2);
            $childModel = new TbWmsAlloChildModel();

            $isUseSendNet = $params ['ret']['use_fawang_logistics'];
            if (false === M('allo', 'tb_wms_')->where(['allo_no' => $allo ['allo_no']])->save(['use_fawang_logistics' => $isUseSendNet])) {
                throw new Exception(L('是否对接发网仓失败'));
            }
            // $isUseSendNet = M('allo', 'tb_wms_')->where(['allo_no' => $allo ['allo_no']])
            //     ->getField('use_fawang_logistics');

            foreach ($params ['child'] as $key => $value) {
                $child [] = $childModel->create($value, 2);
                $data = [];
                $data ['skuId'] = $value ['sku_id'];
                $data ['gudsId'] = substr($value ['sku_id'], 0, 8);
                $data ['orderId'] = $allo ['allo_no'];
                $data ['isUseSendNet'] = $isUseSendNet;
                $data ['operatorId'] = $this->getName();
                $data ['num'] = (int)$value ['actual_outgoing_num'];
                $data ['releaseNum'] = (int)($value ['demand_allo_num'] - $value ['actual_outgoing_num']);
                $data ['deliveryWarehouse'] = $allo ['allo_out_warehouse'];
                $data ['saleTeamCode'] = $allo ['allo_out_team'];
                $data ['deadlineDateForUse'] = $value ['deadline_date_for_use'] ? $value ['deadline_date_for_use'] : Null;
                if (!isset($data['deadlineDateForUse']) or strtotime($data['deadlineDateForUse']) == false or $data['deadlineDateForUse'] == '0000-00-00 00:00:00' or $data['deadlineDateForUse'] == '0000-00-00') {
                    $data ['deadlineDateForUse'] = null;
                }
                $batchIdsAndNum [$value ['batch_id']]['should_num'] = $value ['demand_allo_num'];
                $batchIdsAndNum [$value ['batch_id']]['send_num'] = $value ['actual_outgoing_num'];
                $requestHeader ['data']['export'] [] = $data;
            }
            $this->setRequestData($requestHeader);
            $count = count($child);
            $this->sendRequest($this->_requestUri, $count > 60 ? $count : 60);

            if ($this->getResponseData() ['code'] != 2000) {
                $this->rollback();
                throw new Exception(L($this->getResponseData() ['msg']));
            }

            $allo ['state'] = TbWmsAlloModel::ALLO_WAIT_STORAGE;
            $allo ['outgoing_date'] = date('Y-m-d H:i:s');
            $allo ['outgoing_bill_id'] = null;
            $allo = $alloModel->create($allo, 2);

            if (!$alloModel->save($allo)) {
                $this->rollback();
                throw new Exception(L('更新调拨单信息失败') . $alloModel->getError());
            }

            if (true) {
                foreach ($child as $k => $v) {
                    if (!$childModel->save($v)) {
                        $this->rollback();
                        throw new Exception(L('更新调拨单子数据失败') . $childModel->getError());
                    }
                }
            }

            $this->commit();

            return $this->returnInfo('', L('出库完成'), 1);
        } catch (Exception $e) {
            $this->rollback();

            return $this->returnInfo('', L('远程请求异常：') . L($e->getMessage()), 0);
        }
    }
}

/**
 * Class Storage
 * 入库逻辑
 */
class StorageModel extends TbWmsAlloModel
{
    protected $_requestUri = 'batch/allocate.json';

    const STORAGE_OUT = 0; // 仓内调拨
    const STORAGE_IN = 1; // 跨仓调拨

    public function storage($params)
    {
        try {
            $Model = new Model();
            $Model->startTrans();
            $log['params'] = $params;
            $alloModel = new TbWmsAlloModel();
            $allo = $alloModel->create($params ['ret'], 2);
            $childModel = new TbWmsAlloChildModel();
            $child = $params ['child'];
            $storageNum = array_column($params ['child'], 'actual_storage_num');
            $outgoingNum = array_column($params ['child'], 'actual_outgoing_num');
            $amountStorageLogCost = (float)$params ['ret']['amount_storage_log_cost'];
            $amountOutgoingLogCost = (float)$params ['ret']['amount_outgoing_log_cost'];
            $logServiceCost = (float)$params ['ret']['log_service_cost'];
            $tmp = [];
            $isUseSendNet = M('allo', 'tb_wms_')->where(['allo_no' => $allo ['allo_no']])
                ->getField('use_fawang_logistics');
            foreach ($child as $key => $value) {
                $data = [];
                $data ['gudsId'] = substr($value ['sku_id'], 0, 8);
                $data ['skuId'] = $value ['sku_id'];
                $data ['num'] = (int)$value ['actual_storage_num'];
                $data ['brokenExportNum'] = (int)((int)$value ['actual_outgoing_num'] - (int)$value ['actual_storage_num']);
                if (!isset($value ['deadline_date_for_use']) or strtotime($value ['deadline_date_for_use']) == false or $value ['deadline_date_for_use'] == '0000-00-00 00:00:00' or $value ['deadline_date_for_use'] == '0000-00-00')
                    $data ['deadlineDateForUse'] = null;
                else
                    $data ['deadlineDateForUse'] = $value ['deadline_date_for_use'];
                $data ['operatorId'] = $this->getName();
                $data ['teamIn'] = $allo ['allo_in_team'];
                $data ['teamOut'] = $allo ['allo_out_team'];
                $inWarehouse = $allo ['allo_in_warehouse'];
                $data ['warehouseIn'] = $allo ['allo_in_warehouse'];
                $data ['warehouseOut'] = $allo ['allo_out_warehouse'];
                $data ['orderId'] = $allo ['allo_no'];
                $data ['isUseSendNet'] = $isUseSendNet;// 是否使用发网发货
                $data ['amountStorageLogCost'] = $amountStorageLogCost;//入库物流费用总价
                $data ['amountOutgoingLogCost'] = $amountOutgoingLogCost;//出库物流费用总价
                $data ['logServiceCost'] = $logServiceCost;//入库物流服务单价
                $data ['storageLogCurrency'] = $params ['ret']['storage_log_currency'];//入库物流币种
                $data ['outgoingLogCurrency'] = $params ['ret']['outgoing_log_currency'];//出库物流币种
                $data ['logServiceCoseCurrency'] = $params ['ret']['log_service_cose_currency'];//服务费用币种
                //$data ['batch_id']           = $value ['batch_id'];
                $batchIdsAndNum [$value ['batch_id']]['should_num'] = $value ['actual_outgoing_num'];
                $batchIdsAndNum [$value ['batch_id']]['send_num'] = $value ['actual_storage_num'];
                $tmp [] = $data;
            }
            $r ['lockCode'] = create_guid();
            $r ['type'] = SELF::STORAGE_IN;
            $r ['data'] = $tmp;
            $requestHeader['data']['allocate'][] = $r;
            unset($allo ['voucher_file']);
            $log['api_request'] = $requestHeader;
            $log['api_url'] = $this->_requestUri;
            $this->setRequestData($requestHeader);
            $count = count($child);
            $this->sendRequest($this->_requestUri, $count > 60 ? $count : 60);
            $res = $this->getResponseData();
            $log['api_response'] = $res;
            if ($res['code'] != 2000 && $params['id'] != '1372') {//fixme 修复代码
                throw new Exception(($this->getResponseData() ['msg']));
            }
            //更新调拨单
            $allo ['state'] = TbWmsAlloModel::ALLO_SUCCESS;
            $allo ['storage_date'] = date('Y-m-d H:i:s', time());
            $alloData = $alloModel->create($allo ['state'], 2);
            unset($allo ['outgoing_bill_id']);
            unset($allo ['storage_bill_id']);
            unset($allo ['report_loss_bill_id']);
            if (!$alloModel->save($allo)) {
                throw new Exception(L('更新调拨单信息失败') . $alloModel->getError());
            }
            //更新调拨子单，更新实际入库数量
            foreach ($child as $k => $v) {
                unset($v ['GUDS_NM']);
                unset($v ['GUDS_OPT_UPC_ID']);
                if ($childModel->save($childModel->create($v, 2)) === false) {
                    throw new Exception(L('更新调拨单子数据失败') . $childModel->getError());
                }
            }
            $Model->commit();
            $msg = L('调拨成功');
            $state = 1;
        } catch (\Exception $e) {
            $Model->setEnforcement(true);
            $Model->rollback();
            $msg = L('请求 API 接口失败') . L($e->getMessage() . $e->getLine());
            $state = 0;
        }

        return $this->returnInfo('', $msg, $state);
    }
}

/**
 * Class Receive
 * 撤销操作
 */
class ReceiveModel extends TbWmsAlloModel
{
    protected $_requestUri = 'batch/withdraw.json';

    public function receive($params)
    {
        try {
            $this->startTrans();
            $model = new TbWmsAlloModel();
            $allo = $model->where('id = %d', [$params ['id']])->find();
            if ($allo and $allo ['state'] == TbWmsAlloModel::ALLO_WAIT_AUDIT) {
                $child = new TbWmsAlloChildModel();
                $retChild = $child->where('allo_id = %d', [$params ['id']])->select();
                if ($retChild) {
                    foreach ($retChild as $key => $value) {
                        $data = [];
                        $data ['gudsId'] = substr($value ['sku_id'], 0, 8);
                        $data ['skuId'] = $value ['sku_id'];
                        $data ['operatorId'] = $this->getName();
                        $data ['orderId'] = $allo ['allo_no'];
                        $requestHeader ['data']['withdraw'][] = $data;
                    }
                }
                $this->setRequestData($requestHeader);
                $this->sendRequest($this->_requestUri);
                if ($this->getResponseData() ['code'] != 2000) {
                    $this->rollback();
                    throw new Exception($this->getResponseData() ['msg']);
                }
                $allo ['state'] = $params ['state'];
                if ($model->save($model->create($allo))) {
                    $this->commit();
                    return $this->returnInfo('', $params ['info'], 1);
                } else {
                    $this->rollback();
                    return $this->returnInfo('', L('状态更新失败'), 0);
                }
            } else {
                $this->rollback();
                return $this->returnInfo('', L('调拨单状态异常无法执行操作'), 0);
            }
        } catch (Exception $e) {
            $this->rollback();
            return $this->returnInfo('', L('远程请求异常：') . L($e->getMessage()), 0);
        }
    }
}