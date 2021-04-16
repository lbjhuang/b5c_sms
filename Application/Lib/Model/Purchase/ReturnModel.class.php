<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/27
 * Time: 17:08
 */

class ReturnModel extends Model
{
    protected $trueTableName = 'tb_pur_return';
    static $return_no_pre = 'CGTH';
    protected $_validate = [
        ['warehouse_cd','require','仓库必填',1,'regex',1],
        ['supplier_id','require','供应商必填','regex',1],
        ['purchase_team_cd','require','采购团队必填','regex',1],
        ['our_company_cd','require','我方公司必填必填','regex',1],
        ['receiver','require','收货人必填','regex',1],
        ['receiver_contact_number','require','收件人联系电话必填','regex',1],
        ['receive_address_country','require','收货地址-国家必填','regex',1],
        ['receive_address_province','require','收货地址-省/州必填','regex',1],
        ['receive_address_detail','require','详细地址必填','regex',1],
    ];

    static $return_status = [
        'to_out_storage'    => 'N002640100',
        'to_tally'          => 'N002640200',
        'done'              => 'N002640300',
    ];

    protected $_auto = array (
        array('status_cd','N002640100'),
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    );

    public function createReturnNo() {
        $date       = date('Ymd');
        $latest_no  = $this->lock(true)->where(['return_no'=>['like', self::$return_no_pre . $date . '%']])->order('id desc')->getField('return_no');
        if($latest_no) {
            return self::$return_no_pre . (substr($latest_no, 4)+1);
        }else {
            return self::$return_no_pre . $date . '0001';
        }
    }

    public function addReturn($data) {
        $data['return_no'] = $this->createReturnNo();
        if(!$this->create($data)) return false;
        $res = $this->add();
        if($res === false) $this->error = '保存失败';
        return $res;
    }

    public function returnCount($param) {
        $where = $this->listWhere($param);
        $result = $this
            ->alias('t')
            ->field('t.id')
            ->join('tb_ms_cmn_cd a on a.CD=t.status_cd')
            ->join('tb_ms_cmn_cd b on b.CD=t.warehouse_cd')
            ->join('tb_ms_cmn_cd c on c.CD=t.purchase_team_cd')
            ->join('tb_ms_cmn_cd d on d.CD=t.our_company_cd')
            ->join('tb_crm_sp_supplier e on e.ID=t.supplier_id')
            ->join('tb_ms_user_area f on f.area_no=t.receive_address_country')
            ->join('tb_ms_user_area g on g.area_no=t.receive_address_province')
            ->join('tb_ms_user_area h on h.area_no=t.receive_address_area')

            ->join('left join tb_pur_return_order i on t.id=i.return_id')
            ->join('left join tb_pur_relevance_order j on i.relevance_id=j.relevance_id')
            ->join('left join tb_pur_order_detail k on j.order_id=k.order_id')
            ->join('left join tb_pur_return_goods l on t.id=l.return_id')
            ->join('left join tb_pur_goods_information m on l.information_id=m.information_id')

            ->where($where)
            ->group('id')
            ->select();
        return count($result);
    }

    public function returnList($param, $limit) {
        $where = $this->listWhere($param);
        return $this
            ->alias('t')
            ->field('t.id,return_no,a.CD_VAL status,b.CD_VAL warehouse,c.CD_VAL purchase_team,t.created_by,e.SP_NAME supplier,d.CD_VAL our_company,receiver_contact_number,f.zh_name receive_address_country,g.zh_name receive_address_province,h.zh_name receive_address_area,estimate_arrive_date')
            ->join('tb_ms_cmn_cd a on a.CD=t.status_cd')
            ->join('tb_ms_cmn_cd b on b.CD=t.warehouse_cd')
            ->join('tb_ms_cmn_cd c on c.CD=t.purchase_team_cd')
            ->join('tb_ms_cmn_cd d on d.CD=t.our_company_cd')
            ->join('tb_crm_sp_supplier e on e.ID=t.supplier_id')
            ->join('tb_ms_user_area f on f.area_no=t.receive_address_country')
            ->join('tb_ms_user_area g on g.area_no=t.receive_address_province')
            ->join('tb_ms_user_area h on h.area_no=t.receive_address_area')

            ->join('left join tb_pur_return_order i on t.id=i.return_id')
            ->join('left join tb_pur_relevance_order j on i.relevance_id=j.relevance_id')
            ->join('left join tb_pur_order_detail k on j.order_id=k.order_id')
            ->join('left join tb_pur_return_goods l on t.id=l.return_id')
            ->join('left join tb_pur_goods_information m on l.information_id=m.information_id')

            ->where($where)
            ->order('id desc')
            ->group('id')
            ->limit($limit)
            ->select();
    }

    public function listWhere($param) {
        $where = [];
        if($param['return_no']) $where['t.return_no'] = $param['return_no'];
        if($param['status_cd']) $where['t.status_cd'] = $param['status_cd'];
        if($param['warehouse_cd']) $where['t.warehouse_cd'] = ['in',$param['warehouse_cd']];
        if($param['purchase_team_cd']) $where['t.purchase_team_cd'] = ['in',$param['purchase_team_cd']];
        if($param['created_by']) $where['t.created_by'] = $param['created_by'];
        if($param['supplier'])  $where['e.SP_NAME'] = ['like','%'.$param['supplier'].'%'];
        if($param['our_company_cd']) $where['t.our_company_cd'] = ['in',$param['our_company_cd']];

        /*if ($param['order_type'] && $param['order_type'] == 'po_number') {
            if($param['order_type_val']) $where['k.online_purchase_order_number'] = $param['order_type_val'];
        } else if ($param['order_type'] && $param['order_type'] == 'pur_number') {
            if($param['order_type_val']) $where['k.procurement_number'] = $param['order_type_val'];
        }*/
        if($param['order_type_val']) {
            $conditions['k.procurement_number']           = $param['order_type_val'];
            $conditions['k.online_purchase_order_number'] = $param['order_type_val'];
            $conditions['_logic'] = 'or';
            $where['_complex']    = $conditions;
        }
        if($param['sku_upc_id']) {
            $sku_id = (new PmsBaseModel())->table('product_sku')->where([
                '_logic' => 'OR',
                'upc_id' => $param['sku_upc_id'],
                '_string' => "FIND_IN_SET('{$param['sku_upc_id']}',upc_more)"
            ])->getField('sku_id');
            if ($sku_id) {
                $where['m.sku_information'] = $sku_id;
            } else {
                $where['m.sku_information'] = $param['sku_upc_id'];
            }
        }
        return $where;
    }

    public function returnDetail($id) {
        return $this
            ->field('t.*,a.CD_VAL status,b.CD_VAL warehouse,c.CD_VAL purchase_team,d.CD_VAL our_company,e.CD_VAL estimate_logistics_cost_currency,f.CD_VAL estimate_other_cost_currency,g.zh_name receive_address_country,h.zh_name receive_address_province,i.zh_name receive_address_area,j.SP_NAME supplier')
            ->alias('t')
            ->join('tb_ms_cmn_cd a on a.CD=t.status_cd')
            ->join('tb_ms_cmn_cd b on b.CD=t.warehouse_cd')
            ->join('tb_ms_cmn_cd c on c.CD=t.purchase_team_cd')
            ->join('tb_ms_cmn_cd d on d.CD=t.our_company_cd')
            ->join('tb_ms_cmn_cd e on e.CD=t.estimate_logistics_cost_currency_cd')
            ->join('tb_ms_cmn_cd f on f.CD=t.estimate_other_cost_currency_cd')
            ->join('tb_ms_user_area g on g.area_no=t.receive_address_country')
            ->join('tb_ms_user_area h on h.area_no=t.receive_address_province')
            ->join('tb_ms_user_area i on i.area_no=t.receive_address_area')
            ->join('tb_crm_sp_supplier j on j.ID=t.supplier_id')
            ->where(['t.id'=>$id])
            ->find();
    }
}