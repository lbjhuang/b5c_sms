<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 17:08
 */

class ReturnGoodsModel extends Model
{
    protected $trueTableName = 'tb_pur_return_goods';

    protected $_validate = [
        ['information_id','require','采购单商品id必须',1,'regex',1],
        ['vir_type_cd','require','类型必须',1,'regex',1],
    ];

    protected $_auto = array (
        array('created_by','userName',1,'function'),
        array('created_at','dateTime',1,'function'),
        array('updated_by','userName',3,'function'),
        array('updated_at','dateTime',3,'function'),
    );


    public function addReturnGoods($return_id, $return_order_id, $goods) {
        $this->_auto[] = ['return_id',$return_id];
        $this->_auto[] = ['return_order_id',$return_order_id];
        $res_create = $this->create($goods);
        $res_add    = $this->add();
        if(!$res_create || !$res_add) {
            $this->rollback();
            $res_create ? $this->error = '商品保存失败' : '';
            return false;
        }
        return $res_add;
    }

    public function getReturnGoods($return_order_id) {
        return $this
            ->where(['return_id'=>$return_order_id])
            ->select();
    }
}