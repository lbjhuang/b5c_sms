<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Report/ReportBaseLogic.class.php';

class IncomeLogic extends ReportBaseLogic
{
    public function listData($params)
    {
        /*empty($params['zd_date'][0]) or $where['zd_date'][] = ['egt', $params['zd_date'][0]];
        !empty($params['zd_date'][1]) ? $end = $params['zd_date'][1] . ' 23:59:59' : $end = date('Y-m-d 23:59:59');
        $where['zd_date'][] = ['elt', $end];
        empty($params['sale_no']) or $where['bi.link_bill_id'] = $params['sale_no'];
        empty($params['warehouse']) or $where['bi.warehouse_id'] = ['in', $params['warehouse']];
        empty($params['sku_upc_id']) or $where['_string'] = "ba.SKU_ID = '{$params['sku_upc_id']}' or psku.upc_id='{$params['sku_upc_id']}'";
        if ($params['spu_name']) {
            $sku_ids = SkuModel::titleToSku($params['spu_name']);
            $where['ba.SKU_ID'] = ['in', $sku_ids];
        }
        empty($params['zd_user']) or $where['bi.zd_user'] = ['in', $params['zd_user']];
        empty($params['relation_type']) or $where[] = ['bi.relation_type' => ['in', $params['relation_type']]];
        empty($params['plat_cd']) or $where['oo.PLAT_CD'] = ['in', $params['plat_cd']];
        empty($params['store_id']) or $where['oo.STORE_ID'] = ['in', $params['store_id']];
        $where[] = ['bi.relation_type' => ['in', ['N002350300', 'N002350400']]];//C,B

        empty($params['po_date'][0]) or $where[] = ['bf.po_time' => ['egt', $params['po_date'][0]], 'oo.ORDER_PAY_TIME' => ['egt', $params['po_date'][0]], '_logic' => 'or'];
        empty($params['po_date'][1]) or $where[] = ['bf.po_time' => ['elt', $params['po_date'][1] . ' 23:59:59'], 'oo.ORDER_PAY_TIME' => ['elt', $params['po_date'][1] . ' 23:59:59'], '_logic' => 'or'];
        empty($params['our_company']) or $where[] = ['bf.our_company' => ['in', $params['our_company']], 'cd5.CD_VAL' => ['in', $params['our_company']], '_logic' => 'or'];
        empty($params['sale_team']) or $where_out[] = ['b_sale_team_cd' => ['in', $params['sale_team']], 'c_sale_team_cd' => ['in', $params['sale_team']], '_logic' => 'or'];
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        $offset = ($params['page'] - 1) * $params['page_size'];
        $subSql = M('stream', 'tb_wms_')->alias('st')
            ->field([
                'bi.id',
                'concat(bi.bill_id, "-", st.id) as bill_no',//出库id
                'if(bi.relation_type="N002350400","b","c") as type',
                'cd4.CD_VAL as b_currency',//b销售币种
//                'bg.price_goods as b_price',//b销售单价
                'cd.CD_VAL as b_tax_rate',//b增值税率 %
                'bf.our_company as b_our_company',//b我方销售公司 中
                'bf.SALES_TEAM as b_sale_team_cd',//b销售团队
                'bf.po_time as b_po_date',//b销售PO日期

                'oo.PAY_CURRENCY as c_currency',//c销售币种
                'og.ITEM_PRICE as c_price',//c销售单价
                'oo.PAY_ITEM_PRICE as c_item_price',//c商品总价
                'oo.PAY_TOTAL_PRICE as c_total_price',//c支付金额
                'cd5.CD_VAL as c_our_company',//c我方销售公司
                'if(locate(",",stor.SALE_TEAM_CD)=0,stor.SALE_TEAM_CD,ba.sale_team_code) as c_sale_team_cd',//c销售团队cd 多个取批次上的
                'oo.ORDER_PAY_TIME as c_po_date',//c销售PO日期
                'cd6.CD_VAL as c_plat',//c平台
                'stor.STORE_NAME as c_store',//c店铺

                'if(bi.relation_type="N002350400",cd4.CD_VAL,oo.PAY_CURRENCY) as currency',//销售币种
                'if(bi.relation_type="N002350400",(select price_goods from tb_b2b_goods where ORDER_ID = bo.ID and SKU_ID = ba.SKU_ID limit 1),og.ITEM_PRICE) as price',//销售单价
                'if(bi.relation_type="N002350400",cd.CD_VAL,"0%") as tax_rate',//增值税率
                'if(bi.relation_type="N002350400",bf.our_company,cd5.CD_VAL) as our_company',//我方销售公司
                'if(bi.relation_type="N002350400",bf.po_time,oo.ORDER_PAY_TIME) as po_date',//销售PO日期
                'if(bi.relation_type="N002350400","",cd6.CD_VAL) as plat',//c平台
                'if(bi.relation_type="N002350400","",stor.STORE_NAME) as store',//c店铺
                'if(bi.relation_type="N002350400",bo.PO_ID,oo.ORDER_NO) as sale_no',//销售订单号

                'cd9.CD_VAL as relation_type',//关联单据类型
                'cd10.CD_VAL as warehouse',//仓库
                'ba.sku_id',//sku
                'psku.upc_id',//条形码
                'ba.batch_code',//批次号
                'st.send_num',//数量
                'cd2.cd_val as unit',//单位
                'us.M_NAME as zd_user',//操作人
                'bi.zd_date',//出库时间
                'ba.id as batch_id',
                'st2.id as in_stream_id',
                'pspu.is_group_sku'//是否组合商品
            ])
            ->join('LEFT JOIN tb_wms_bill bi ON bi.id = st.bill_id and bi.type = 0')
            ->join('LEFT JOIN tb_wms_batch ba ON ba.id = st.batch')
            ->join('LEFT JOIN tb_wms_stream st2 ON st2.id = ba.stream_id')
            ->join('LEFT JOIN tb_b2b_order bo ON bi.link_bill_id like concat(bo.PO_ID, "%")')
            ->join('LEFT JOIN tb_b2b_info bf ON bf.ORDER_ID = bo.ID')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = bf.TAX_POINT')
            ->join('LEFT JOIN tb_ms_cmn_cd cd4 ON cd4.CD = bf.po_currency')

            ->join('LEFT JOIN tb_op_order oo ON oo.ORDER_ID = bi.link_bill_id')
            ->join('LEFT JOIN tb_op_order_guds og ON og.ORDER_ID = oo.ORDER_ID and og.B5C_SKU_ID = ba.SKU_ID')
            ->join('LEFT JOIN tb_ms_store stor ON stor.ID = oo.STORE_ID')
            ->join('LEFT JOIN tb_ms_cmn_cd cd5 ON cd5.CD = stor.company_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd cd6 ON cd6.CD = oo.PLAT_CD')

            ->join('LEFT JOIN ' . PMS_DATABASE .'.product_sku psku ON psku.sku_id = ba.SKU_ID')
            ->join('LEFT JOIN ' . PMS_DATABASE .'.product pspu ON pspu.spu_id = psku.spu_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = pspu.charge_unit')
            ->join('LEFT JOIN bbm_admin us ON us.M_ID = bi.zd_user')
            ->join('LEFT JOIN tb_ms_cmn_cd cd3 ON cd3.CD = bi.warehouse_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd9 ON cd9.CD = bi.relation_type')
            ->join('LEFT JOIN tb_ms_cmn_cd cd10 ON cd10.CD = bi.warehouse_id')
            ->where($where)
            ->buildSql();
        $query = M()->table($subSql.' tmp')->field([
                '*',
                'if(type="b",cd7.CD_VAL,cd8.CD_VAL) as sale_team',
            ])
            ->join('LEFT JOIN tb_ms_cmn_cd cd7 ON cd7.CD = b_sale_team_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd cd8 ON cd8.CD = c_sale_team_cd')
            ->where($where_out);
        $query1 = clone $query;
        $params['page_size'] == -1 or $query->limit($offset . ',' . $params['page_size']);
        $list = $query->order('id desc')->select();
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes']);
        $scale = $params['isExport'] ? 4 : 2;
        foreach ($list as &$v) {
            if ($v['type'] == 'b') {
                $v['sale_amount_no_tax'] = $v['price'] * $v['send_num'] / (1 + $v['tax_rate'] / 100);//不含税采购金额
            } else {
                $v['sale_amount_no_tax'] = $v['price'] / $v['c_item_price'] * $v['c_total_price'] * $v['send_num'];//不含税采购金额
            }
            $v['tax'] = $v['sale_amount_no_tax'] * $v['tax_rate'] / 100;//增值税额
            $v['sale_amount_no_tax'] = round($v['sale_amount_no_tax'], $scale);
            $v['tax'] = round($v['tax'], $scale);
        }*/
        $q = $this->buildQuery($params);
       //dd($q);
//        var_dump($q);die;
        $es_data = (new ESClientModel())->search($q)['hits'];
        $this->data['total'] = $es_data['total'];
        $list = &$es_data['hits'];
        foreach ($list as &$v) {
            $upc_id =  $v['_source']['upcId'];
            if(isset($v['_source']['upcMore']) && $v['_source']['upcMore']) {
                $upc_more_arr = explode(',', $v['_source']['upcMore']);
                array_unshift($upc_more_arr,$upc_id);
                $upc_id = implode(",\r\n", $upc_more_arr);
            }
            $v = [
                'bill_no' => $v['_source']['billNo'],//出库id
                'currency' => $v['_source']['currency'],//销售币种
                'sale_amount_no_tax' => $v['_source']['noTaxSaleMoney'],//不含税销售金额
                'tax_rate' => $v['_source']['taxRate'],//增值税率
                'tax' => $v['_source']['valueAddedTax'],//增值税
                'relation_type' => $v['_source']['relationType'],//关联类型
                'sale_no' => $v['_source']['saleNo'],//销售订单号
                'our_company' => $v['_source']['ourCompany'],//我方公司
                'sale_team' => $v['_source']['saleTeam'],//销售团队
                'po_date' => date('Y-m-d H:i:s', $v['_source']['poDate'] / 1000),//销售po时间
                'warehouse' => $v['_source']['warehouse'],
                'sku_id' => $v['_source']['skuId'],
                'upc_id' => $upc_id,//条形码
                'upc_more' => $v['_source']['upcMore'],
                'spu_name' => '',
                'attributes' => '',//商品属性
                'batch_code' => $v['_source']['batchCode'],//批次号
                'send_num' => $v['_source']['sendNum'],//数量
                'unit' => $v['_source']['unit'],//
                'zd_user' => $v['_source']['zdUser'],//操作时间
                'zd_date' => date('Y-m-d H:i:s', $v['_source']['zdDate'] / 1000),//操作日期
                'plat' => $v['_source']['plat'],//平台
                'store_id' => $v['_source']['storeId'],//店铺
                'store' => $v['_source']['store'],//店铺
                'batch_id' => $v['_source']['batchId'],
                'in_stream_id' => $v['_source']['inStreamId'],
                'bill_id' => $v['_source']['billId'],
                'customer' => $v['_source']['customer'],//客户名称
                'country' => $v['_source']['country'],//收货国家
                'amount_sum' => number_format( $v['_source']['noTaxSaleMoney']+$v['_source']['valueAddedTax'], 2)
            ];
        }
        unset($es_data);
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes']);
        if (!$params['isExport']) {
            foreach ($list as &$v) {
                $v['sale_amount_no_tax'] = number_format($v['sale_amount_no_tax'], 2, '.', '');
                $v['tax'] = number_format($v['tax'], 2, '.', '');
            }
        }
        $this->data['list'] = $list;
        $this->data['page'] = $params['page'];
        $this->data['page_size'] = $params['page_size'];
        $this->data['query'] = $q;
        $this->code = 2000;
        return true;
    }

    public function export($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [
            ['name' => L('出库id'), 'field_name' => 'bill_no'],
            ['name' => L('销售币种'), 'field_name' => 'currency'],
            ['name' => L('不含税销售金额'), 'field_name' => 'sale_amount_no_tax'],
            ['name' => L('增值税率'), 'field_name' => 'tax_rate'],
            ['name' => L('增值税额'), 'field_name' => 'tax'],
            ['name' => L('关联单据类型'), 'field_name' => 'relation_type'],
            ['name' => L('销售订单号'), 'field_name' => 'sale_no'],
            ['name' => L('我方销售公司'), 'field_name' => 'our_company'],
            ['name' => L('销售团队'), 'field_name' => 'sale_team'],
            ['name' => L('销售po日期'), 'field_name' => 'po_date'],
            ['name' => L('仓库'), 'field_name' => 'warehouse'],
            ['name' => L('SKU编码'), 'field_name' => 'sku_id'],
            ['name' => L('条形码'), 'field_name' => 'upc_id'],
            ['name' => L('商品名称'), 'field_name' => 'spu_name'],
            ['name' => L('商品属性'), 'field_name' => 'attributes'],
            ['name' => L('批次号'), 'field_name' => 'batch_code'],
            ['name' => L('数量'), 'field_name' => 'send_num'],
            ['name' => L('单位'), 'field_name' => 'unit'],
            ['name' => L('操作人'), 'field_name' => 'zd_user'],
            ['name' => L('出库时间'), 'field_name' => 'zd_date'],
            ['name' => L('平台'), 'field_name' => 'plat'],
            ['name' => L('店铺ID'), 'field_name' => 'store_id'],
            ['name' => L('店铺'), 'field_name' => 'store'],
            ['name' => L('客户名称'), 'field_name' => 'customer'],
            ['name' => L('收货国家'), 'field_name' => 'country'],
        ];
        $filename = '收入报表_' . date('Ymd') . '.csv'; //设置文件名
        header("Content-Type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename={$filename}");
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);//BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, array_column($map, 'name'));
//        $fields = array_column($map, 'field_name');
//        $data = DataModel::toYield($data);


        $params['page_size'] = 15000;
        $params['page'] = 1;
        $params['isExport'] = true;
        $this->listData($params);
        while ($data = $this->data['list']) {
            foreach ($data as $row) {
                $line = [
                    $row['bill_no'] . "\t",//转换字符串类型否则数字类型变科学计数
                    $row['currency'] . "\t",
                    $row['sale_amount_no_tax'],
                    $row['tax_rate'] . "\t",
                    $row['tax'],
                    $row['relation_type'] . "\t",
                    $row['sale_no'] . "\t",
                    $row['our_company'] . "\t",
                    $row['sale_team'] . "\t",
                    $row['po_date'] . "\t",
                    $row['warehouse'] . "\t",
                    $row['sku_id'] . "\t",
                    $row['upc_id'] . "\t",
                    $row['spu_name'] . "\t",
                    $row['attributes'] . "\t",
                    $row['batch_code'] . "\t",
                    $row['send_num'] . "\t",
                    $row['unit'] . "\t",
                    $row['zd_user'] . "\t",
                    $row['zd_date'] . "\t",
                    $row['plat'] . "\t",
                    $row['store_id'] . "\t",
                    $row['store'] . "\t",
                    $row['customer'] . "\t",
                    $row['country'] . "\t"
                ];
                /*$line = array_map(function($field) use($row) {
                    return (string) $row[$field] . "\t";
                }, $fields);*/
                fputcsv($out, $line);
            }
            $params['page']++;
            $this->listData($params);
        }
        fclose($out);
        exit;
    }

    private function buildQuery($params)
    {
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        $es_search = new EsSearchModel('income', 'income');
        empty($params['zd_date']) or $es_search->where(['zdDate' => ['range', ['gte' => strtotime($params['zd_date'][0]) * 1000, 'lte' => strtotime($params['zd_date'][1]) * 1000 + 86400000]]]);
        if ($params['spu_name']) {
            $sku_ids = SkuModel::titleToSku($params['spu_name']);
            $es_search->where(['skuId' => ['and', $sku_ids]]);
        }
        $q = $es_search
            ->where(['ourCompany' => ['and', $params['our_company']]])
            ->where(['relationTypeCd' => ['and', $params['relation_type']]])
            ->where(['warehouseCd' => ['and', $params['warehouse']]])
            ->where(['zdUser' => ['and', $params['zd_user']]])
            ->where(['saleTeamCd' => ['and', $params['sale_team']]])
            ->where(['platCd' => ['and', $params['plat_cd']]])
            ->where(['storeId' => ['and', $params['store_id']]])
            ->where(['customer' => ['like', $params['customer']]])
            ->where(['batchCode' => ['like', $params['batch_code']]])
            ->where(['country' => ['like', $params['country']]])//收货国家筛选
            ->sort(['zdDate' => 'desc'])
            ->page($params['page'] - 1, $params['page_size'])
            ->getQuery();
//        empty($params['sku_upc_id']) or $q['body']['query']['bool']['must'][] = ['query_string' => ['query' => $params['sku_upc_id'], 'fields' => ['upcId', 'skuId']]];
        $q['body']['query']['bool']['must'][] = [
            [
                'range' => [
                    'sendNum' => [
                        'gte' => 1
                    ]
                ]
            ]
        ];
        empty($params['po_date']) or $q['body']['query']['bool']['must'][] = [
            [
                'range' => [
                    'poDate' => [
                        'gte' => strtotime($params['po_date'][0]) * 1000,
                        'lte' => strtotime($params['po_date'][1]) * 1000 + 86400000,
                    ]
                ]
            ]
        ];
        empty($params['sku_upc_id']) or $q['body']['query']['bool']['must'][] = [
            'bool' => [
                'should' => [
                    [
                        'term' => [
                            'upcId' => $params['sku_upc_id']
                        ]
                    ],
                    [
                        'term' => [
                            'skuId' => $params['sku_upc_id']]
                    ],
                    [
                        'wildcard' => [
                            'upcMore' => "*{$params['sku_upc_id']}*"
                        ]
                    ]
                ]
            ]
        ];
        empty($params['sale_no']) or $q['body']['query']['bool']['must'][] = [
            'bool' => [
                'should' => [
                    [
                        'terms' => [
                            'saleNo' => (array) $params['sale_no']
                        ]
                    ],
                    [
                        'terms' => [
                            'tbWmsBill.linkBillId' => (array) $params['sale_no']]
                    ]
                ],
                'minimum_should_match' => 1

            ]
        ];
        return $q;
    }

    public function getLogStat($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $days = ($params['days'] ?: 3) * 86400;
        $size = $params['size'] ?: 15000;
        if ($params['start'] && $params['end']) {
            $gte = strtotime($params['start']);
            $lte = $gte + $days + 86400;
        } elseif ($params['end']) {
            $lte = strtotime($params['end']) + 86400;
            $gte = $lte - $days + 1;
        } elseif ($params['start']) {
            $gte = strtotime($params['start']);
            $lte = $gte + $days;
        } else {
            $lte = strtotime('today');
            $gte = $lte - $days;
        }
        /*$lte = strtotime($params['end'] ?: 'today');
        if ($params['start']) {
            $gte = strtotime($params['start']);
            if (!$params['end'])
                $lte = $gte + $days + 86400;
        } else {
            $gte = $lte - $days;
        }*/
        $q = (new EsSearchModel('gs_log', 'gs_log'))
            ->setDefaultNotNull(['not', ['gs_log.user']])
            ->page(0, $size)//change size
            ->getQuery();
        $q['body']['query']['bool']['must'][] = [
            'range' => [
                'nodeId' => [
                    'gt' => 0
                ]
            ]
        ];
        $q['body']['query']['bool']['must'][] = [
            'range' => [
                'cTimeStamp' => [
                    'gte' => $gte,
                    'lte' => $lte,
                ]
            ]
        ];
        $total = $from = 0;
        $q['body']['from'] = &$from;
        $es = new ESClientModel();
        do {
            $es_data = $es->search($q)['hits'];
            if (!$total) $total = $es_data['total'];
            foreach ($es_data['hits'] as &$log) {
                $nodeId = $log['_source']['nodeId'];
                if ($nodeId) {
                    if (!isset($scores[$nodeId]))
                        $scores[$nodeId] = 0;
                    ++$scores[$nodeId];
                }
            }
            unset($es_data);
            $from += $size;
        } while ($from < $total);
        $res = [];
        isset($params['type']) or $params['type'] = 0;
        $where['TYPE'] = $params['type'];
        $nodes = M('node', 'bbm_')->where($where)
            ->getField('id,CONCAT_WS("/",title,name) as menu,CONCAT_WS("/",ctl,act) as uri,if(TYPE=0,"页面","接口") as type', true);
        foreach ($scores as $k => $v) {
            if (isset($nodes[$k])) {
                $nodes[$k]['times'] = $v;
                $res[$v][] = $nodes[$k];
            }
        }
        krsort($res);
        $data['total'] = $total;
        $data['res'] = array_values($res);
        return $data;
    }
}