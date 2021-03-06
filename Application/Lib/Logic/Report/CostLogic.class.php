<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Report/ReportBaseLogic.class.php';

class CostLogic extends ReportBaseLogic
{

    public function listData($params,$is_add_cache = false)
    {
        $where['bi.relation_type'] = ['in', ['N002350300', 'N002350400','N002350705']];
        empty($params['zd_date'][0]) or $where['bi.zd_date'][] = ['egt', $params['zd_date'][0]];
        !empty($params['zd_date'][1]) ? $end = $params['zd_date'][1] . ' 23:59:59' : $end = date('Y-m-d 23:59:59');
        $where['bi.zd_date'][] = ['elt', $end];
//        empty($params['pur_create_time'][0]) or $where['pod.create_time'][] = ['egt', $params['pur_create_time'][0]];
//        empty($params['pur_create_time'][1]) or $where['pod.create_time'][] = ['elt', $params['pur_create_time'][1] . ' 23:59:59'];
//        empty($params['our_company']) or $where['pod.our_company'] = ['in', $params['our_company']];
//        empty($params['purchase_team']) or $where['pod.payment_company'] = ['in', $params['purchase_team']];
        empty($params['zd_user']) or $where['bi.zd_user'] = ['in', $params['zd_user']];
//        empty($params['purchase_order_no']) or $where['purchase_order_no'] = $params['purchase_order_no'];
        empty($params['warehouse']) or $where['bi.warehouse_id'] = ['in', $params['warehouse']];
//        empty($params['sku_upc_id']) or $where['_string'] = "ba.SKU_ID = '{$params['sku_upc_id']}' or psku.upc_id='{$params['sku_upc_id']}'";
        if ($params['spu_name']) {
            $sku_ids = SkuModel::titleToSku($params['spu_name']);
//            $where['ba.SKU_ID'] = ['in', $sku_ids];
        }
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        empty($params['_batch_ids']) or $where['st.batch'] = ['in', $params['_batch_ids']];

        if(!empty($params['relation_type'])){
            if (in_array('N002350400',$params['relation_type'])) array_push($params['relation_type'],'N002350705');
            $where['bi.relation_type'] = ['in',$params['relation_type'] ];
        }

        $offset = ($params['page'] - 1) * $params['page_size'];
       
       
        $query = (new SlaveModel())->table('tb_wms_stream')->alias('st')
            ->field([
                'concat(bi.bill_id, "-", st.id) as bill_no',//??????id
                'ifnull(cd.cd_val,"CNY") as pur_currency',//????????????
                'ifnull(st2.pur_invoice_tax_rate,0) as tax_rate',//????????????
                'gi.unit_price',//????????????
                'st2.unit_price as unit_price2',//????????????
                'ba.purchase_order_no',//????????????
                'cd5.CD_VAL as relation_type', //????????????
                'cd5.CD as relation_type_cd', //???????????? cd
                'cd1.cd_val as our_company',//??????????????????
                'cd1.cd as our_company_cd',//??????????????????cd
                'cd2.cd_val as purchase_team',//????????????
                'cd2.cd as purchase_team_cd',//????????????cd
                'pod.create_time as pur_create_time',//?????????????????????
                'cd3.cd_val as warehouse', //??????
                'bi.warehouse_id as warehouse_cd',//??????cd
                'ba.sku_id',//sku
                'psku.upc_id',//?????????
                'psku.upc_more',//??????????????????
                'ba.batch_code',//?????????
                'st.send_num',//??????
                'cd4.cd_val as unit',//??????
                'us.M_NAME as zd_user', //?????????
                'bi.zd_user as zd_user_id',//?????????id
                'bi.zd_date',//????????????
                'ba.id as batch_id',
                'st2.id as in_stream_id',
                'pspu.is_group_sku',//??????????????????
                'pod.supplier_id as supplier',//?????????
                'bi.link_bill_id',
                'bi.relation_type as relation_code_bi',  // ????????????
                'bi2.relation_type as relation_code_bi2',  // ????????????
                'concat(bi.bill_id, "-", st.id) AS link_bill_no',
                'st2.unit_price_origin',
            ])
            ->join('LEFT JOIN tb_wms_bill bi ON bi.id = st.bill_id and bi.type = 0')
            ->join('LEFT JOIN tb_wms_batch ba ON ba.id = st.batch')
            ->join('LEFT JOIN tb_wms_bill bi2 ON bi2.id = ba.bill_id')
            ->join('LEFT JOIN tb_wms_stream st2 ON st2.id = ba.stream_id')
            ->join('LEFT JOIN tb_pur_order_detail pod ON pod.procurement_number = ba.purchase_order_no')
            ->join('LEFT JOIN tb_pur_relevance_order rel ON rel.order_id = pod.order_id')
            ->join('LEFT JOIN tb_pur_goods_information gi ON gi.relevance_id = rel.relevance_id and gi.sku_information=ba.SKU_ID')
            ->join('LEFT JOIN ' . PMS_DATABASE .'.product_sku psku ON psku.sku_id = ba.SKU_ID')
            ->join('LEFT JOIN ' . PMS_DATABASE .'.product pspu ON pspu.spu_id = psku.spu_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = pod.amount_currency')
            ->join('LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = bi2.CON_COMPANY_CD')
            ->join('LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = ba.purchase_team_code')
            ->join('LEFT JOIN tb_ms_cmn_cd cd3 ON cd3.CD = bi.warehouse_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd4 ON cd4.CD = pspu.charge_unit')
            ->join('LEFT JOIN tb_ms_cmn_cd cd5 ON cd5.CD = bi.relation_type')
            ->join('LEFT JOIN bbm_admin us ON us.M_ID = bi.zd_user')
            ->where($where);
            
        $tmp_list = $query->order('bi.zd_date desc')->select();
        
      
        //var_dump(M()->_sql());die;
        //var_dump(M()->_sql());die;
        $list = [];
        foreach ($tmp_list as $v) {
            if ($v['is_group_sku']) {
                $this->insertOriginBatches($v, $list);
            } else {
                $list[] = $v;
            }
        }
       
        unset($tmp_list);
      
       
        $sku_ids = $params['spu_name'] ? SkuModel::titleToSku($params['spu_name']) : [];
      
        $list = array_filter($list, function($v) use ($params, $sku_ids) {
            $flg = empty($params['pur_create_time'][0]) || $v['pur_create_time'] > $params['pur_create_time'][0];
            $flg = $flg && (empty($params['pur_create_time'][1]) || $v['pur_create_time'] < $params['pur_create_time'][1] . ' 23:59:59');
            $flg = $flg && (empty($params['our_company']) || in_array($v['our_company_cd'], $params['our_company']));
            $flg = $flg && (empty($params['purchase_team']) || in_array($v['purchase_team_cd'], $params['purchase_team']));
//            $flg = $flg && (empty($params['purchase_order_no']) || $v['purchase_order_no'] == $params['purchase_order_no'] || $v['link_bill_id'] == $params['purchase_order_no']);
            //???????????????B2C????????????????????????
            $flg = $flg && (empty($params['purchase_order_no']) || $v['purchase_order_no'] == $params['purchase_order_no'] || ($v['link_bill_id'] == $params['purchase_order_no'] && $v['relation_code_bi'] != 'N002350300'));
            $upc_more_arr = explode(',' ,$v['upc_more']);
            $flg = $flg && (empty($params['sku_upc_id']) || $v['sku_id'] == $params['sku_upc_id'] || $v['upc_id'] == $params['sku_upc_id'] || in_array($params['sku_upc_id'],$upc_more_arr));
            $flg = $flg && (empty($params['spu_name']) || in_array($v['sku_id'], $sku_ids));
            $flg = $flg && (empty($params['supplier']) || stripos($v['supplier'], $params['supplier']) !== false);
            return $flg;
        });
        $this->data['total'] = count($list);
        if ($params['page_size'] != -1) {
            $list = array_slice($list, $offset, $params['page_size']);
        }
       
        $list = SkuModel::getInfo($list, 'sku_id', ['spu_name', 'attributes']);
        $scale = $params['isExport'] ? 4 : 2;
        foreach ($list as &$v) {
            if ($v['unit_price']) {
                $v['pur_amount_no_tax'] = $v['unit_price'] * $v['send_num'] / (1 + $v['tax_rate']);//?????????????????????
            } else {
                $v['pur_amount_no_tax'] = $v['unit_price2'] * $v['send_num'] / (1 + $v['tax_rate']);
            }
            $v['tax'] = $v['pur_amount_no_tax'] * $v['tax_rate'];//????????????

            // ??????????????????
//            if ($v['relation_code_bi'] ==  'N002350705' || $v['relation_code_bi2'] ==  'N002350705'){
//
//                $v['bill_no'] = $v['link_bill_no'];
//                $v['pur_amount_no_tax'] = $v['unit_price_origin'] * $v['send_num'] / (1 + $v['tax_rate']);
//                $v['tax'] = $v['pur_amount_no_tax'] * $v['tax_rate'];//????????????
//                $v['purchase_order_no'] = $v['link_bill_id'];
//            }
            if ($v['relation_code_bi'] ==  'N002350705'){
                $v['bill_no'] = $v['link_bill_no'];
                $v['pur_amount_no_tax'] = $v['unit_price_origin'] * $v['send_num'] / (1 + $v['tax_rate']);
                $v['tax'] = $v['pur_amount_no_tax'] * $v['tax_rate'];//????????????
                $v['purchase_order_no'] = $v['link_bill_id'];
            }

            if ($v['relation_code_bi'] ==  'N002350705'){
                $v['relation_type'] = "B2B??????";
            }

            $v['pur_amount_no_tax'] = round($v['pur_amount_no_tax'], $scale);
            $v['tax'] = round($v['tax'], $scale);
            # ?????????????????????
            if($v['upc_more']) {
                $upc_more_arr = explode(',', $v['upc_more']);
                array_unshift($upc_more_arr,$v['upc_id']);
                $v['upc_id'] = implode(",\r\n", $upc_more_arr);
            }
        }
        unset($v);
        if($is_add_cache){
           return $list;

        }
        $this->data['list'] = $list;
        $this->data['page'] = $params['page'];
        $this->data['page_size'] = $params['page_size'];
        $this->code = 2000;
        return true;
    }

   
    /**
     * ??????
     * @param $params
     */
    public function export($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $params['page_size'] = -1;
        $params['isExport'] = true;
        $this->listData($params);
        $data = $this->data['list'];
        $exportExcel = new ExportExcelModel();
        $key = 'A';

        $exportExcel->attributes = [
            $key++ => ['name' => L('??????ID'), 'field_name' => 'bill_no'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'pur_currency'],
            $key++ => ['name' => L('?????????????????????'), 'field_name' => 'pur_amount_no_tax'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'tax_rate'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'tax'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'relation_type'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'purchase_order_no'],
            $key++ => ['name' => L('??????????????????'), 'field_name' => 'our_company'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'purchase_team'],
            $key++ => ['name' => L('?????????????????????'), 'field_name' => 'pur_create_time'],
            $key++ => ['name' => L('??????'), 'field_name' => 'warehouse'],
            $key++ => ['name' => L('sku??????'), 'field_name' => 'sku_id'],
            $key++ => ['name' => L('?????????'), 'field_name' => 'upc_id'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'spu_name'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'attributes'],
            $key++ => ['name' => L('?????????'), 'field_name' => 'batch_code'],
            $key++ => ['name' => L('??????'), 'field_name' => 'send_num'],
            $key++ => ['name' => L('??????'), 'field_name' => 'unit'],
            $key++ => ['name' => L('?????????'), 'field_name' => 'zd_user'],
            $key++ => ['name' => L('????????????'), 'field_name' => 'zd_date'],
            $key++ => ['name' => L('?????????'), 'field_name' => 'supplier']
        ];
        $exportExcel->data = $data;
        $exportExcel->export();
    }

    private function insertOriginBatches($group_batch, &$list)
    {
        $ori_batches = M('group_stream', 'tb_wms_')->alias('gst')
            ->field(['gb.sku_json',
                'gst.from_batch as batch_id',
                'gst.sku_id',
                'cd.cd_val as pur_currency',//????????????
                'ifnull(st2.pur_invoice_tax_rate,0) as tax_rate',//????????????
                'gi.unit_price',//????????????
                'ba.purchase_order_no',//????????????
                'cd1.cd_val as our_company',//??????????????????
                'cd1.cd as our_company_cd',//??????????????????cd
                'cd2.cd_val as purchase_team',//????????????
                'cd2.cd as purchase_team_cd',//????????????cd
                'pod.create_time as pur_create_time',//?????????????????????
                'ba.sku_id',//sku
                'psku.upc_id',//?????????
                'ba.batch_code',//?????????
                'cd4.cd_val as unit',//??????
                'st2.id as in_stream_id',
                'pod.supplier_id as supplier',//?????????
            ])
            ->join('left join tb_wms_group_bill gb on gb.id=gst.group_bill_id')
            ->join('LEFT JOIN tb_wms_batch ba ON ba.id = gst.from_batch')
            ->join('LEFT JOIN tb_wms_stream st2 ON st2.id = ba.stream_id')
            ->join('LEFT JOIN tb_pur_order_detail pod ON pod.procurement_number = ba.purchase_order_no')
            ->join('LEFT JOIN tb_pur_relevance_order rel ON rel.order_id = pod.order_id')
            ->join('LEFT JOIN tb_pur_goods_information gi ON gi.relevance_id = rel.relevance_id and gi.sku_information=ba.SKU_ID')
            ->join('LEFT JOIN ' . PMS_DATABASE .'.product_sku psku ON psku.sku_id = ba.SKU_ID')
            ->join('LEFT JOIN ' . PMS_DATABASE .'.product pspu ON pspu.spu_id = psku.spu_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = pod.amount_currency')
            ->join('LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = pod.our_company')
            ->join('LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = pod.payment_company')
            ->join('LEFT JOIN tb_ms_cmn_cd cd4 ON cd4.CD = pspu.charge_unit')
            ->where(['to_batch' => $group_batch['batch_id']])
            ->group('gst.sku_id')
            ->select();
        foreach (json_decode($ori_batches[0]['sku_json'], true) as $v){
            $sku_num_map[$v['childSkuId']] = (int) $v['num'];
        }
        foreach ($ori_batches as &$v) {
            $v['bill_no'] = $group_batch['bill_no'];
            $v['warehouse'] = $group_batch['warehouse'];
            $v['zd_date'] = $group_batch['zd_date'];
            $v['zd_user'] = $group_batch['zd_user'];
            $v['relation_type'] = $group_batch['relation_type'];
            $v['is_group_child_sku'] = 1;
            $v['send_num'] = $group_batch['send_num'] * $sku_num_map[$v['sku_id']];
            $list[] = $v;
        }
        unset($v);
        unset($ori_batches);
        unset($sku_num_map);
    }
}