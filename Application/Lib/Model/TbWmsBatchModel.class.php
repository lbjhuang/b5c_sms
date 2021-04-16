<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/8/17
 * Time: 10:41
 */

class TbWmsBatchModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_batch';

    protected $_link = [
        'TbWmsBatchOrder' => [
            'mapping_type' => HAS_MANY,
            'class_name' => 'TbWmsBatchOrder',
            'foreign_key' => 'batch_id',
            'relation_foreign_key' => 'id',
            'mapping_name' => 'order_batch_child',
            'mapping_key' => 'id',
            //'condition' => 'tb_crm_contract.CRM_CON_TYPE = 0 and tb_crm_contract.SP_CHARTER_NO is not null and SP_CHARTER_NO != ""',
        ]
    ];

    /**
     * 获取批次id与skuid
     * @param  int $batch_id 批次id
     * @return string $sku_id
     */
    public function getBatchIdSkuId($batch_id)
    {
        return $ret = $this->where('id = ' . $batch_id)->find();
    }

    /**
     * 占用订单查询
     */
    public function take_up($batch_id)
    {
        if ($ret = $this->getBatchIdSkuId($batch_id)) {
            $Operation_history = M('operation_history', 'tb_wms_');
            $where['sku_id'] = $ret ['SKU_ID'];
            if (!empty(I("post.p"))) {
                $_GET['p'] = I("post.p");
            }
            import('ORG.Util.Page');// 导入分页类
            $Operation_history_sql = $Operation_history->where($where)->group('tb_wms_operation_history.order_id')->having('count(tb_wms_operation_history.id)=1')->select(false);
            $model = new Model();
            $count = $model->table($Operation_history_sql . ' a')->where("a.ope_type = 'N001010100'")->count();
    //        $count = count($Operation_history->where($where)->group('tb_wms_operation_history.order_id')->having('count(tb_wms_operation_history.id)=1')->select());

            $Page = new Page($count, 50);
            $show['ajax'] = $Page->ajax_show();
            $show['sum'] = $Page->get_totalPages();
            $show['sku'] = $ret ['SKU_ID'];

            /*$operation_history = $Operation_history->field($ope_field)
                ->where($where)->group('tb_wms_operation_history.order_id')->having('count(tb_wms_operation_history.id)=1')
                ->order('tb_wms_operation_history.id desc')
                ->join('left join tb_op_order on tb_op_order.B5C_ORDER_NO = tb_wms_operation_history.order_id')
                ->limit($Page->firstRow . ',' . $Page->listRows)->select();*/
            $ope_field = 'tb_op_order.ORDER_ID,a.*';
            $operation_history = $model->field($ope_field)->table($Operation_history_sql . ' a')->where("a.ope_type = 'N001010100'")
                ->join('left join tb_op_order on tb_op_order.B5C_ORDER_NO = a.order_id')
                ->order('a.id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)->select();
            if ($operation_history) {
                $info = '查询正常';
                $status = 'y';
            } else {
                $info = '查询无结果';
                $status = 'n';
            }
        } else {
            $operation_history = null;
            $info = '查询无结果';
            $status = 'n';
        }
        return $return_arr = array('info' => $info, "status" => $status, 'data' => $operation_history, 'show' => $show);
    }

    public function getBatch($sku_id,$sell_team) {
        $three_month                        = date('Y-m-d H:i:s',strtotime('-3 month'));
        $five_month                         = date('Y-m-d H:i:s',strtotime('-5 month'));
        $map['t.sku_id']                    = $sku_id;
        $map['t.total_inventory']           = ['gt',0];
        $where['t.sale_team_code']          = ['in',[$sell_team,'N001281500']];
        $where['_string']                   = "if(CAT_CD like 'C07%',t.create_time < '$three_month',t.create_time < '$five_month')";
        $where['_logic']                    = 'or';
        $map['_complex']                    = $where;
        $res = $this
            ->alias('t')
            ->field('t.batch_code,t.id,purchase_order_no,b.CD_VAL purchase_team,f.CD_VAL sell_team,batch_code,available_for_sale_num,left(t.create_time,10) storage_time,left(t.deadline_date_for_use,10) deadline_date,total_cost,c.CD_VAL warehouse,unit_price,unit_price_usd,g.procurement_date,left(CAT_CD,3) cat_cd_left,t.create_time original_storage_time')
            ->join('left join tb_wms_bill a on a.id=t.bill_id')
            ->join('left join tb_ms_cmn_cd b on b.CD=t.purchase_team_code')
            ->join('left join tb_ms_cmn_cd c on c.CD=a.warehouse_id')
            ->join('left join tb_ms_guds d on d.GUDS_ID = t.SKU_ID')
            ->join('left join tb_wms_stream e on e.id = t.stream_id')
            ->join('left join tb_ms_cmn_cd f on f.CD=t.sale_team_code')
            ->join('left join tb_pur_order_detail g on g.procurement_number=t.purchase_order_no')
            ->where($map)
            ->group('t.id')
            ->order('available_for_sale_num desc')
            ->select();
        foreach ($res as &$v) {
            if($v['cat_cd_left'] == 'C07' && $v['original_storage_time'] < $three_month) {
                $v['unsalable'] = '是';
            }else if($v['cat_cd_left'] != 'C07' && $v['original_storage_time'] < $three_month) {
                $v['unsalable'] = '是';
            }else {
                $v['unsalable'] = '否';
            }
        }
        return $res;
    }
}