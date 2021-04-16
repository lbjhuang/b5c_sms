<?php



class ImprotBillAuditRepository extends Repository
{
    public $model;

    public function __construct()
    {
        parent::__construct();
    }
    public function getFind($condtion,$field="*"){
        $info_data = $this->model->table('tb_wms_improt_bill_audit')
            ->field($field)
            ->where($condtion)
            ->find();
        return $info_data;
    }

    public function getDetailList($condtion,$field="*")
    {
        $list = $this->model->table('tb_wms_improt_bill_audit_detail')
            ->field($field)
            ->where($condtion)
            ->select();
        return $list;
    }

    public function getList($condtion,$field="*", $limit = false, $order_by='tb_wms_improt_bill_audit.id desc')
    {
        $list = $this->model->table('tb_wms_improt_bill_audit')
            ->field($field)
            ->where($condtion)
            ->order($order_by)
            ->limit($limit)
            ->select();
        return $list;
    }

    public function add($insert_data){
        $res = $this->model->table('tb_wms_improt_bill_audit')->add($insert_data);
        return $res;
    }

    public function addLog($insert_data){
        $res = $this->model->table('tb_wms_improt_bill_audit_log')->add($insert_data);
        return $res;
    }

    public function addAll($insert_data){
        $res = $this->model->table('tb_wms_improt_bill_audit')->addAll($insert_data);
        return $res;
    }

    public function update($condtion,$update_data){;
        $res = $this->model->table('tb_wms_improt_bill_audit')->where($condtion)->save($update_data);
        return $res;
    }

    public function delDetail($contion){
        $res = $this->model->table('tb_wms_improt_bill_audit_detail')->where($contion)->delete();
        return $res;
    }

    public function addAllDetail($insert_data){
        $res = $this->model->table('tb_wms_improt_bill_audit_detail')->addAll($insert_data);
        return $res;
    }

    public function getLogList($condtion,$field="*")
    {
        $list = $this->model->table('tb_wms_improt_bill_audit_log')
            ->field($field)
            ->where($condtion)
            ->select();
        return $list;
    }

    public function getLevelDept()
    {
        return M('hr_dept', 'tb_')
            ->field('ID,DEPT_NM,PAR_DEPT_ID,TYPE')
            ->where('DELETED_BY is null')
            ->select();
    }
    public function summaryPeopleInDept($dept_id)
    {
        $list = M('hr_empl_dept', 'tb_')
            ->field('tb_hr_empl_dept.*,tb_hr_card.EMP_NM,tb_hr_card.EMP_SC_NM,tb_hr_card.EMPL_ID,tb_hr_card.ERP_ACT,bbm_admin.M_NAME')
            ->join('tb_hr_empl on tb_hr_empl.ID = tb_hr_empl_dept.ID2')
            ->join('tb_hr_card on tb_hr_empl_dept.ID2 = tb_hr_card.EMPL_ID')
            ->join('bbm_admin on bbm_admin.empl_id = tb_hr_card.EMPL_ID')
            ->where(['tb_hr_empl_dept.TYPE' => 0, 'tb_hr_empl_dept.ID1' => $dept_id])->order('tb_hr_empl_dept.SORT')->select();
        return $list;
    }

}