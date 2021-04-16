<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/7/9
 * Time: 16:50
 */

class AllocationExtendModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_allo';

    # 运行绑定报价单的调拨单状态
    public static $allow_bind__quotation_states = [
        'N001970602','N001970603','N001970400',
    ];



    /**
     * @param $params
     * @return mixed
     */
    public function showData($params)
    {
        $billId = $params ['id'];
        $bill = $this->bill($billId);
        $guds = $this->stream($billId);

        $response ['data']['bill'] = $bill;
        $response ['data']['guds'] = $guds;

        return $response;
    }

    /**
     * @param $billId
     * @return array
     */
    public function bill($billId)
    {
        $bill = new TbWmsBillModel();
        $fields = [
            'tb_wms_bill.bill_id',
            'tb_wms_bill.link_bill_id',
            'tb_wms_bill.SALE_TEAM',
            'tb_wms_bill.bill_type',
            'tb_wms_bill.warehouse_id'
        ];
        $ret = $bill
            ->field($fields)
            ->where(['id' => ['eq', $billId]])
            ->find();

        // 出入库单转码
        if ($ret) {
            $ret ['warehouse_id'] = $this->get_show_warehouse()[$ret ['warehouse_id']]['warehouse'];// 仓库
            $ret ['bill_type']    = $this->get_outgo()[$ret ['bill_type']]['CD_VAL'];// 订单类型
            $ret ['SALE_TEAM'] = BaseModel::saleTeamList(true)[$ret ['SALE_TEAM']];
            $ret ['channel'] = BaseModel::getChannels()[$ret ['channel']];
        }

        return $ret;
    }

    /**
     * 获得仓库名称
     * @return mixed
     */
    public function get_show_warehouse()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        $where['is_show'] = 1;
        return $Warehouse->where($where)->getField('CD,company_id,warehouse');
    }

    /**
     * 收发类别
     * @return mixed
     */
    public function get_outgo($get_outgo = null)
    {
        $outgo = I('get.outgo');
        if ($get_outgo) {
            $outgo = $get_outgo;
        }
        switch ($outgo) {
            case 'storage':
                $where['CD_NM'] = '入库类型';
                break;
            case 'outgoing':
                $where['CD_NM'] = '出库类型';
                break;
            default:
                $where['CD_NM'] = array('in', '出库类型,入库类型');
        }
        $Res = M('cmn_cd', 'tb_ms_');
        return $Res->where($where)->getField('CD,CD_VAL,ETc');
    }

    /**
     * @param $billId
     * @return array
     */
    public function stream($billId)
    {
        $stream = new StreamModel();
        $conditions ['bill_id'] = ['eq', $billId];
        $fields = [
            't1.GSKU',
            //'t4.GUDS_NM',
            //'t3.GUDS_OPT_VAL_MPNG',
            //'t3.GUDS_OPT_UPC_ID',
            't2.batch_code',
            't1.deadline_date_for_use',
            't1.send_num',
            't1.currency_id',
            //'t4.VALUATION_UNIT',
            't2.create_time as add_time',
            't1.unit_price',
            't1.unit_money',
            't6.CON_COMPANY_CD as our_company',
            't5.procurement_number',
            't6.SP_TEAM_CD as purchase_team_code',
            't6.SALE_TEAM as sale_team_code'
        ];
        $stream->subWhere('t1.bill_id', ['eq', $billId]);
        $ret = $this->table('tb_wms_stream t1')
            ->field($fields)
            ->join('LEFT JOIN tb_wms_batch t2 ON t2.id = t1.batch')
            //->join('LEFT JOIN tb_ms_guds_opt t3 on t1.GSKU = t3.GUDS_OPT_ID')
            //->join('LEFT JOIN tb_ms_guds t4 on SUBSTR(t3.GUDS_OPT_ID, 1, 8) = t4.GUDS_ID')
            ->join('LEFT JOIN tb_pur_order_detail t5 on t2.purchase_order_no = t5.procurement_number')
            ->join('LEFT JOIN tb_wms_bill t6 ON t1.bill_id = t6.id')
            ->where($stream::$where)
            ->select();

        $sku = array_column($ret, 'GSKU');
        $tmp = [];
        foreach ($sku as $key => $value) {
            $tmp [] = ['sku_id' => $value];
        }
        $opt = SkuModel::getInfo($tmp, 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);

        $tmp = [];
        foreach ($opt as $key => $value) {
            $tmp [$value ['sku_id']] = $value;
        }

        foreach ($ret as $key => &$value) {
            $value ['GUDS_NM'] = $tmp [$value ['GSKU']]['spu_name'];
            $value ['GUDS_OPT_VAL_MPNG'] = $tmp [$value ['GSKU']]['attributes'];
            $value ['GUDS_OPT_UPC_ID'] = $tmp [$value ['GSKU']]['product_sku']['upc_id'];
        }
        if ($ret) {
            $ret = array_map(function ($batch) {
                $batch ['VALUATION_UNIT'] = BaseModel::getUnit()[$batch ['VALUATION_UNIT']]['CD_VAL'];// 单位
                $batch ['our_company'] = BaseModel::ourCompany()[$batch ['our_company']];
                $batch ['purchase_team_code'] = BaseModel::spTeamCd()[$batch ['purchase_team_code']];
                $batch ['sale_team_code'] = BaseModel::saleTeamCd()[$batch ['sale_team_code']];
                return $batch;
            }, $ret);
        }

        return $ret;
    }

    /**
     * 商品属性组装
     * @param string 商品属性编码
     * @return string 商品属性编码对应的中文名
     */
    public function gudsOptsMerge($val)
    {
        $str = explode(';', $val);
        $opt = BaseModel::getGudsOpt();
        $shtml = '';
        $length = count($str);
        for ($i = 0; $i < $length; $i++) {
            if ($opt[$str[$i]]['OPT_CNS_NM'] and $opt[$str[$i]]['OPT_VAL_CNS_NM']) $shtml .= $opt[$str[$i]]['OPT_CNS_NM'] . ':' . $opt[$str[$i]]['OPT_VAL_CNS_NM'] . ' ';
            else $shtml .= $opt[$str[$i]]['OPT_VAL_CNS_NM'];
        }
        return $shtml;
    }

    /**
     * 微信审批回调
     * @see ReviewCallback::getCbMap
     * @param $params
     * @return array
     */
    public static function wechat_exam($params)
    {
        $receipt = $params['status'] ? '您已同意调拨单号：DBXXXXXXXXXX的调拨申请' : '您已拒绝调拨单号：DBXXXXXXXXXX的调拨申请';
        return ['code' => 2000, 'msg' => 'success', 'data' => '', 'wechat' => ['receipt' => $receipt]];
    }
}