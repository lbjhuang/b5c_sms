<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 17:08
 */

class ReturnOrderModel extends Model
{
    protected $trueTableName = 'tb_pur_return_order';
    protected $_validate = [
        ['relevance_id','require','关联订单id缺失','regex',1],
    ];
    protected $_auto = array (
        array('created_by','userName',1,'function'),
        array('created_at','dateTime',1,'function'),
        array('updated_by','userName',3,'function'),
        array('updated_at','dateTime',3,'function'),
    );

    public function addReturnOrder($return_id, $order) {
        $this->_auto[]  = ['return_id',$return_id];
        $res_create     = $this->create($order);
        $res_add        = $this->add();
        if(!$res_create || !$res_add) {
            $res_create ? $this->error = '关联订单保存失败' : '';
            return false;
        }
        return $res_add;
    }

    public function getReturnOrder($return_id) {
        return $this
            ->alias('t')
            ->field('t.id,t.compensation,b.procurement_number,b.online_purchase_order_number,c.CD_VAL compensation_currency,b.amount_currency compensation_currency_cd')
            ->join('tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->join('tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('tb_ms_cmn_cd c on c.CD=b.amount_currency')
            ->where(['t.return_id'=>$return_id])
            ->select();

    }

    public function getReturnOrderGoods($return_id) {
        $order_goods = $this
            ->alias('t')
            ->field('a.id,c.sku_id,c.upc_id,c.upc_more,case a.vir_type_cd when "N002440100" then "正品" when "N002440400" then "残次品" end vir_type_val,d.relevance_id,e.procurement_number,a.return_number,a.tally_number')
            ->join('tb_pur_return_goods a on a.return_order_id=t.id')
            ->join('tb_pur_goods_information b on b.information_id=a.information_id')
            ->join(PMS_DATABASE . '.product_sku c on c.sku_id=b.sku_information')
            ->join('tb_pur_relevance_order d on d.relevance_id=b.relevance_id')
            ->join('tb_pur_order_detail e on e.order_id=d.order_id')
            ->where(['t.return_id'=>$return_id])
            ->select();
        foreach ($order_goods as $k => &$v) {
   
            if($v['upc_more']) {
                $upc_more_arr = explode(',', $v['upc_more']);
                array_unshift($upc_more_arr, $v['upc_id']);
                $v['upc_id'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行 
            }
            
        }
        return SkuModel::getInfo($order_goods,'sku_id',['spu_name','image_url','attributes']);
    }
}