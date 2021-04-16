<?php
/**
 * User: yangsu
 * Date: 19/2/27
 * Time: 15:08
 */

@import("@.Model.Orm.TbPurReturn");
@import("@.Model.Orm.TbPurReturnGoods");
@import("@.Model.Orm.TbPurReturnOrder");
@import("@.Model.Orm.TbPurGoodsInformation");
@import("@.Model.Orm.TbMsUserArea");


class WarehousingRepository extends Repository
{
    /**
     * @param $id
     * @return \Illuminate\Support\Collection|null|class
     */
    public function getTbPurReturnId($id)
    {
        return TbPurReturn::find($id, ['id']);
    }

    /**
     * @param $where
     * @param $limit
     * @return array
     */
    public function getReturnOutList($where, $limit)
    {
        $where_string = "tb_pur_return.supplier_id = tb_crm_sp_supplier.ID AND tb_crm_sp_supplier.DATA_MARKING = 0";
        $model = $this->model->table('(tb_pur_return,tb_crm_sp_supplier)')
            ->field('
                tb_pur_return.id,
                tb_pur_return.return_no,
                tb_pur_return.status_cd,
                tb_pur_return.outbound_status,
                tb_pur_return.warehouse_cd,
                tb_pur_return.purchase_team_cd,
                tb_pur_return.create_user,
                tb_pur_return.created_by,
                tb_pur_return.created_at,
                tb_crm_sp_supplier.SP_NAME AS supplier,
                tb_pur_return.our_company_cd,
                tb_pur_return.receive_address_country,
                tb_pur_return.receive_address_province,
                tb_pur_return.receive_address_area,
                tb_pur_return.receive_address_detail
            ')
            ->join('left join tb_con_division_warehouse on tb_con_division_warehouse.warehouse_cd = tb_pur_return.warehouse_cd')
            ->join('tb_pur_return_goods prg on prg.return_id = tb_pur_return.id')
            ->join('tb_pur_goods_information pgi on pgi.information_id = prg.information_id')
            ->join(PMS_DATABASE .'.product_sku ps on ps.sku_id = pgi.sku_information')
            ->where($where)
            ->where($where_string)
            ->order('tb_pur_return.id DESC');
        $model_limit = clone $model;
        $page['total'] = $model
            ->count("distinct tb_pur_return.return_no");
        $page['current_page'] = $limit[0];
        $page['per_page'] = $limit[1];
        $data = $model_limit
            ->group('tb_pur_return.return_no')
            ->limit($limit[0], $limit[1])
            ->select();
        // echo M()->_sql();die;
        return [$data, $page];
    }

    public function getTbMsUserArea($area_nos)
    {
        return $this->objToArray(TbMsUserArea::whereIn('area_no', $area_nos)
            ->get(['area_no', 'zh_name']));
    }


    public function getReturnDeliverDetails($id)
    {
        $data = TbPurReturn::where('id', $id)
            ->with(['TbCrmSpSupplier', 'TbPurReturnGood', 'TbPurReturnOrder'])
            ->with('TbPurReturnGood.TbPurGoodsInformation')
            ->first();
        return $this->objToArray($data);
    }

    /**
     * @param $id
     * @param $save
     * @param $Model
     * @return mixed
     */
    public function updateReturnDeliveryConfirmation($id, $save, $Model)
    {
        $save['outbound_status'] = 1;
        $save['status_cd'] = 'N002640200';
        $save['updated_by'] = $save['out_of_stock_user'] = DataModel::userNamePinyin();
        $save['out_of_stock_time'] = DateModel::now();
        $where['id'] = $id;
        $where['outbound_status'] = 0;
        if ($Model) {
            $this->model = $Model;
        }
        return $this->model->table('tb_pur_return')
            ->where($where)
            ->save($save);
    }

    public function getPrchasingReturnBy($warehouse_cd)
    {
        $where['warehouse_cd'] = $warehouse_cd;
       return $this->model->table('tb_con_division_warehouse')
            ->where($where)
            ->getField('prchasing_return_by');

    }
}