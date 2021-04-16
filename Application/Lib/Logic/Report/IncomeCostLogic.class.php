<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Report/ReportBaseLogic.class.php';

class IncomeCostLogic extends ReportBaseLogic
{

    public function listData($params,$uuid)
    {
        //查询收入成本分页列表数据
        $data = ApiModel::getIncomeCostList($params,$uuid);
        $data['rows'] = SkuModel::getInfo($data['rows'], 'b5cSkuId', ['spu_name', 'attributes']);
        $data['rows'] = $this->formatIncomeCostData($data['rows']);
        $this->data = $data;
        $this->data['query'] = $params;
        $this->code = 2000;
        return true;
    }

    public function sumData($params)
    {
        //查询收入成本聚合数据
        $data = ApiModel::getIncomeCostSum($params);
        $this->data = $data;
        $this->data['query'] = $params;
        $this->code = 2000;
        return true;
    }

    public function export($params)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');
        $uuid = uuid();
        $map = [
            ['name' => L('收入成本ID'), 'field_name' => 'id'],
            ['name' => L('是否组合商品'), 'field_name' => 'groupSku'],
            ['name' => L('来源渠道'), 'field_name' => 'sourceChannelCodeNm'],
            ['name' => L('业务类型'), 'field_name' => 'bizTypeNm'],
            ['name' => L('关联出入库ID'), 'field_name' => 'storeRelationId'],
            ['name' => L('销售币种'), 'field_name' => 'saleCurrencyCodeNm'],
            ['name' => L('不含税收入（订单币种）'), 'field_name' => 'exTaxMoney'],
            ['name' => L('成本币种'), 'field_name' => 'costCurrencyCodeNm'],
            ['name' => L('不含税成本（订单币种）'), 'field_name' => 'exTaxCost'],
            ['name' => L('不含税收入（USD）'), 'field_name' => 'exTaxMoneyUSD'],
            ['name' => L('不含税成本（USD）'), 'field_name' => 'exTaxCostUSD'],
            ['name' => L('毛利（USD）'), 'field_name' => 'grossProfitUSD'],
            ['name' => L('毛利率%'), 'field_name' => 'grossProfitRate'],
            ['name' => L('销项增值税率'), 'field_name' => 'saleVatRate'],
            ['name' => L('销项增值税额（订单币种）'), 'field_name' => 'saleVat'],
            ['name' => L('进项增值税率'), 'field_name' => 'inputVatRate'],
            ['name' => L('进项增值税额（订单币种）'), 'field_name' => 'inputVat'],
            ['name' => L('销项增值税额（USD）'), 'field_name' => 'saleVatUSD'],
            ['name' => L('进项增值税额（USD）'), 'field_name' => 'inputVatUSD'],
            ['name' => L('销售订单号'), 'field_name' => 'saleOrderNo'],
            ['name' => L('我方公司'), 'field_name' => 'ourCompanyName'],
            ['name' => L('销售团队'), 'field_name' => 'saleTeamName'],
            ['name' => L('仓库'), 'field_name' => 'warehouseName'],
            ['name' => L('SKU编码'), 'field_name' => 'b5cSkuId'],
            ['name' => L('条形码'), 'field_name' => 'barCode'],
            ['name' => L('商品名称'), 'field_name' => 'gudsName'],
            ['name' => L('商品属性'), 'field_name' => 'attributes'],
            ['name' => L('批次号'), 'field_name' => 'batchCode'],
            ['name' => L('数量'), 'field_name' => 'useNum'],
            ['name' => L('单位'), 'field_name' => 'useNumUnitNm'],
            ['name' => L('操作人'), 'field_name' => 'operator'],
            ['name' => L('生成时间'), 'field_name' => 'createTimeMills'],
            ['name' => L('平台'), 'field_name' => 'platNm'],
            ['name' => L('店铺编号'), 'field_name' => 'storeId'],
            ['name' => L('店铺'), 'field_name' => 'storeName'],
            ['name' => L('客户ID'), 'field_name' => 'customId'],
            ['name' => L('客户名称'), 'field_name' => 'customName'],
            ['name' => L('收货国家'), 'field_name' => 'receiverCountryName'],
            ['name' => L('采购单号'), 'field_name' => 'purchaseOrderNo'],
            ['name' => L('采购团队'), 'field_name' => 'purchaseTeamName'],
            ['name' => L('供应商ID'), 'field_name' => 'supplierId'],
            ['name' => L('供应商'), 'field_name' => 'supplierName'],
            ['name' => L('是否GP'), 'field_name' => 'isGshopper'],
            ['name' => L('是否ODM'), 'field_name' => 'isOdm'],
            ['name' => L('数据类型'), 'field_name' => 'handleAuto_val'],
        ];
        $filename = '收入成本报表_' . date('Ymd') . '.csv'; //设置文件名
        header("Content-Type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename={$filename}");
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);//BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, array_column($map, 'name'));
//        $fields = array_column($map, 'field_name');
//        $data = DataModel::toYield($data);


        $params['pageSize'] = 2000;
        $params['pageNo'] = 0;
        //$params['isExport'] = true;
        $this->listData($params,$uuid);
        while ($data = $this->data['rows']) {
            foreach ($data as $key => $row) {
                $row['groupSku'] = $row['groupSku'] ? '是' : '否';
                $row['isGshopper'] = $row['isGshopper'] ? '是' : '否';
                $row['isOdm'] = $row['isOdm'] ? '是' : '否';
                $row['saleOrderNo'] = $row['parentSaleOrderNo'] ? $row['parentSaleOrderNo'] : $row['saleOrderNo'];
                $line = [
                    (string)$row['id'] . "\t",//转换字符串类型否则数字类型变科学计数
                    (string)$row['groupSku'] . "\t",
                    (string)$row['sourceChannelCodeNm'] . "\t",
                    (string)$row['bizTypeNm'] . "\t",
                    (string)$row['storeRelationId'] . "\t",
                    (string)$row['saleCurrencyCodeNm'] . "\t",
                    (string)$row['exTaxMoney'] . "\t",
                    (string)$row['costCurrencyCodeNm'] . "\t",
                    (string)$row['exTaxCost'] . "\t",
                    (string)$row['exTaxMoneyUSD'] . "\t",
                    (string)$row['exTaxCostUSD'] . "\t",
                    (string)$row['grossProfitUSD'] . "\t",
                    (string)($row['grossProfitRate'] * 100 . '%') . "\t",
                    (string)($row['saleVatRate'] * 100 . '%') . "\t",
                    (string)$row['saleVat'] . "\t",
                    (string)($row['inputVatRate'] * 100 . '%') . "\t",
                    (string)$row['inputVat'] . "\t",
                    (string)$row['saleVatUSD'] . "\t",
                    (string)$row['inputVatUSD'] . "\t",
                    (string)$row['saleOrderNo'] . "\t",
                    (string)$row['ourCompanyName'] . "\t",
                    (string)$row['saleTeamName'] . "\t",
                    (string)$row['warehouseName'] . "\t",
                    (string)$row['b5cSkuId'] . "\t",
                    (string)$row['barCode'] . "\t",
                    (string)$row['gudsName'] . "\t",
                    (string)$row['attributes'] . "\t",
                    (string)$row['batchCode'] . "\t",
                    (string)$row['useNum'] . "\t",
                    (string)$row['useNumUnitNm'] . "\t",
                    (string)$row['operator'] . "\t",
                    (string)date('Y-m-d H:i:s', $row['createTimeMills']/1000) . "\t",
                    (string)$row['platNm'] . "\t",
                    (string)$row['storeId'] . "\t",
                    (string)$row['storeName'] . "\t",
                    (string)$row['customId'] . "\t",
                    (string)$row['customName'] . "\t",
                    (string)$row['receiverCountryName'] . "\t",
                    (string)$row['purchaseOrderNo'] . "\t",
                    (string)$row['purchaseTeamName'] . "\t",
                    (string)$row['supplierId'] . "\t",
                    (string)$row['supplierName'] . "\t",
                    (string)$row['isGshopper'] . "\t",
                    (string)$row['isOdm'] . "\t",
                    (string)$row['handleAuto_val'] . "\t",
                ];
                /*$line = array_map(function($field) use($row) {
                    return (string) $row[$field] . "\t";
                }, $fields);*/
                fputcsv($out, $line);
                unset($data[$key]);
            }
            $params['pageNo']++;
            $this->listData($params,$uuid);
        }
        fclose($out);
        exit;
    }

    private function buildQuery($params)
    {
        empty($params['currentPage']) and $params['currentPage'] = 1;
        empty($params['pageSize']) and $params['pageSize'] = 20;
        $es_search = new EsSearchModel('income', 'income');
        //生成日期
        empty($params['createTime']) or $es_search->where(['createTime' => ['range', ['gte' => strtotime($params['createTime'][0]) * 1000, 'lte' => strtotime($params['createTime'][1]) * 1000 + 86400000]]]);
        //毛利率
        empty($params['grossProfitRate']) or $es_search->where(['grossProfitRate' => ['range', ['gte' => strtotime($params['grossProfitRate'][0]) * 1000, 'lte' => strtotime($params['grossProfitRate'][1]) * 1000 + 86400000]]]);
        if ($params['spu_name']) {
            $sku_ids = SkuModel::titleToSku($params['spu_name']);
            $es_search->where(['skuId' => ['and', $sku_ids]]);
        }
        $q = $es_search
            ->where(['id' => ['and', $params['id']]]) //收入成本id
            ->where(['saleOrderNo' => ['and', $params['saleOrderNo']]]) //销售单号
            ->where(['purchaseOrderNo' => ['and', $params['purchaseOrderNo']]]) //采购单号
            ->where(['ourCompanyCode' => ['and', $params['ourCompanyCode']]]) //我方公司编码
            ->where(['ourCompanyName' => ['like', $params['ourCompanyName']]]) //我方公司编码名称
            ->where(['saleTeamCode' => ['and', $params['saleTeamCode']]]) //销售团队编码
            ->where(['saleTeamName' => ['like', $params['saleTeamName']]]) //销售团队名称
            ->where(['purchaseTeamCode' => ['and', $params['purchaseTeamCode']]]) //采购团队编码
            ->where(['purchaseTeamName' => ['like', $params['purchaseTeamName']]]) //采购团队名称
            //->where(['b5cSkuId' => ['like', $params['b5cSkuId']]]) //skuId
            //->where(['barCode' => ['like', $params['barCode']]]) //条形码
            ->where(['gudsName' => ['like', $params['gudsName']]])//商品名称
            ->where(['warehouseCode' => ['and', $params['warehouseCode']]])//仓库编码
            ->where(['warehouseName' => ['like', $params['warehouseName']]])//仓库名称
            ->where(['operator' => ['like', $params['operator']]])//操作人
            ->where(['bizType' => ['and', $params['bizType']]]) //业务类型
            ->where(['customName' => ['like', $params['customName']]])//客户名称
            ->where(['platCd' => ['and', $params['platCd']]])//平台编码
            ->where(['platName' => ['like', $params['platName']]])//平台名称
            ->where(['storeId' => ['and', $params['storeId']]])//店铺Id
            ->where(['storeName' => ['like', $params['storeName']]])//店铺名称
            ->where(['supplierId' => ['and', $params['supplierId']]])//供应商编码
            ->where(['supplieName' => ['like', $params['supplieName']]])//供应商名称
            ->where(['groupSku' => ['and', $params['groupSku']]]) //是否组合sku
            ->where(['sourceChannelCode' => ['and', $params['sourceChannelCode']]]) //来源渠道
            ->where(['storeRelationId' => ['and', $params['storeRelationId']]]) //出入库关联id
            ->sort(['createTime' => 'desc'])
            ->page($params['currentPage'] - 1, $params['pageSize'])
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
        empty($params['b5cSkuId']) or $q['body']['query']['bool']['must'][] = [
            'bool' => [
                'should' => [
                    [
                        'term' => [
                            'barCode' => $params['b5cSkuId']
                        ]
                    ],
                    [
                        'term' => [
                            'b5cSkuId' => $params['b5cSkuId']]
                    ]
                ]
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

    /**
     * 格式化条形码显示
     * @param array $list
     * @return array
     * @author Redbo He
     * @date 2020/10/15 15:45
     */
    public function formatIncomeCostData(array $list)
    {
        foreach ($list  as &$item) {
            if($item['upcMore']) {
                $upc_more_arr = explode(',', $item['upcMore']);
                array_unshift($upc_more_arr,$item['barCode']);
                $item['barCode'] = implode(",\r\n", $upc_more_arr);
            }
            if ($item['handleAuto'] == 1) {
                $item['handleAuto_val'] = '正常生成';
            } else if ($item['handleAuto'] == 2) {
                $item['handleAuto_val'] = '数据修复';
            } else {
                $item['handleAuto_val'] = '未知';
            }
        }
        return $list;
    }
}