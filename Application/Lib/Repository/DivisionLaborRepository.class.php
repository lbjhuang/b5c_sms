<?php
/**
 * User: yangsu
 * Date: 19/5/9
 * Time: 14:49
 */

@import("@.Model.Orm.TbConDivisionOurCompany");
@import("@.Model.Orm.TbConDivisionWarehouse");
@import("@.Model.Orm.TbConDivisionClient");

use Application\Lib\Model\Orm\TbConDivisionOurCompany;

/**
 * Class DivisionLaborRepository
 */
class DivisionLaborRepository extends Repository
{

    public function ourCompanysIndex($wheres, $limit)
    {
        $temp_model = $this->model->table('tb_con_division_our_company')
            ->field('
                tb_con_division_our_company.id,
                tb_ms_cmn_cd.CD AS our_company_cd,
                tb_ms_cmn_cd.CD_VAL AS our_company_cd_val,
                tb_con_division_our_company.payment_manager_by,
                tb_con_division_our_company.invoice_person_charge_by,
                tb_con_division_our_company.created_at,
                tb_con_division_our_company.created_by,
                tb_con_division_our_company.updated_at,
                tb_con_division_our_company.updated_by,
                tb_con_division_our_company.b2b_manager_by
            ')
            ->join('RIGHT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_con_division_our_company.our_company_cd')
            ->where($wheres)
            ->where(' tb_ms_cmn_cd.CD LIKE \'N00124%\' AND tb_ms_cmn_cd.USE_YN = \'Y\' ', null, true);
        list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
        return [$res_db, $pages];
    }

    public function ourCompanysUpdate($where_id, $data)
    {
        return TbConDivisionOurCompany::updateOrCreate($where_id, $data);
    }

    public function clientsIndex($wheres, $limit)
    {
        $temp_model = $this->model->table('tb_con_division_client')
            ->field('
                tb_con_division_client.id,
                tb_crm_sp_supplier.ID AS supplier_id,
                tb_crm_sp_supplier.SP_NAME AS client_name,
                tb_crm_sp_supplier.SP_NAME_EN AS client_name_en,
                tb_con_division_client.sales_assistant_by,                
                tb_con_division_client.created_at,
                tb_con_division_client.created_by,
                tb_con_division_client.updated_at,
                tb_con_division_client.updated_by
            ')
            ->join('RIGHT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.ID  = tb_con_division_client.supplier_id')
            ->where($wheres)
            ->where(' tb_crm_sp_supplier.DATA_MARKING = 1 AND tb_crm_sp_supplier.SP_STATUS = 1 AND tb_crm_sp_supplier.DEL_FLAG = 1 ', null, true);
        list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
        return [$res_db, $pages];
    }

    public function clientsUpdate($where_id, $data)
    {
        return TbConDivisionClient::updateOrCreate($where_id, $data);
    }

    public function warehousesIndex($wheres, $where_sting, $limit)
    {
        $temp_model = $this->model->table('tb_con_division_warehouse')
            ->field('
            tb_con_division_warehouse.id,
            tb_wms_warehouse.CD,
            tb_ms_cmn_cd.CD_VAL as warehouse,
            tb_wms_warehouse.place,
            tb_con_division_warehouse.warehouse_cd,
            tb_con_division_warehouse.purchase_warehousing_by,
            tb_con_division_warehouse.transfer_warehousing_by,
            tb_con_division_warehouse.b2b_order_outbound_by,
            tb_con_division_warehouse.transfer_out_library_by,
            tb_con_division_warehouse.prchasing_return_by,
            tb_con_division_warehouse.inventory_by,
            tb_con_division_warehouse.inventory_finance_by,
            tb_con_division_warehouse.inventory_operate_by,
            tb_con_division_warehouse.task_launch_by
            ')
            ->join('RIGHT JOIN tb_wms_warehouse ON tb_wms_warehouse.CD = tb_con_division_warehouse.warehouse_cd')
            ->join('left JOIN tb_ms_cmn_cd ON tb_wms_warehouse.CD = tb_ms_cmn_cd.CD')
            ->where($wheres)
            ->where(' tb_wms_warehouse.is_show = 1  ', null, true);
        if ($where_sting) {
            $temp_model->where($where_sting, null, true);
        }
        list($pages, $res_db) = $this->joinDataPage($limit, $temp_model);
        return [$res_db, $pages];
    }

    public function warehousesUpdate($where_id, $data)
    {
        return TbConDivisionWarehouse::updateOrCreate($where_id, $data);
    }

    /**
     * @param $limit
     * @param $temp_model
     * @param $pages
     * @return array
     */
    private function joinDataPage($limit, $temp_model)
    {
        $search_model = clone $temp_model;
        $pages['total'] = $temp_model->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];
        $res_db = $search_model->limit($limit[0], $limit[1])->select();
        return array($pages, $res_db);
    }

}
