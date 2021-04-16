<?php

//@import("@.Model.Orm.TbConDivisionOurCompany");
//@import("@.Model.Orm.TbConDivisionWarehouse");
//@import("@.Model.Orm.TbConDivisionClient");

//use Application\Lib\Model\Orm\TbConDivisionOurCompany;

class CompensationRepository extends Repository
{

    public function warehoustList()
    {
        return $this->model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N00068%'], 'USE_YN' => 'Y'])->field(['CD', 'CD_VAL', 'SORT_NO', 'ETC'])->order('SORT_NO asc')->select();

    }

    public function CompenStatusList()
    {
        return $this->model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N00388%'], 'USE_YN' => 'Y'])->field(['CD', 'CD_VAL', 'SORT_NO', 'ETC'])->order('SORT_NO asc')->select();

    }

    public function CompenReasonList()
    {
        return $this->model->table('tb_ms_cmn_cd')->where(['CD' => ['like', 'N00384%'], 'USE_YN' => 'Y'])->field(['CD', 'CD_VAL', 'SORT_NO', 'ETC'])->order('SORT_NO asc')->select();

    }


    public function getOrderByB5cOrderId($b5cOrderId, $fields = ["*"])
    {

        return $this->model->table('tb_op_order')->field($fields)->where(['B5C_ORDER_NO' => $b5cOrderId])->find();

    }

    public function getOrderByB5cOrderIds($b5cOrderIds, $fields = ["*"])
    {
        $b5cOrderIds = (array) $b5cOrderIds;
        return $this->model->table('tb_op_order')->field($fields)->where(['B5C_ORDER_NO' => ['in', $b5cOrderIds]])->select();

    }

    public function getCompen($where, $field = '*')
    {
        return $this->model->table('tb_order_wms_compensation')->where($where)->field($field)->find();
    }

    public function compenGuds($where, $field = '*')
    {
        return $this->model->table('tb_order_wms_compensation_guds')->where($where)->field($field)->select();
    }


    public function CompenList($where, $field, $page)
    {


        $query = $this->model
            ->table('tb_order_wms_compensation as compen')
            ->join('left join tb_order_wms_compensation_guds as compen_guds on compen.id = compen_guds.compensate_id')
            ->where($where)
            ->field($field)
            ->limit($page['first_row'] . ',' . $page['page_size'])
            ->order('compen.id desc')
            ->group('compen.id')
            ->select(false);
        #$field = ['compen.*', 'group_concat(compen_guds.b5c_sku_id) as b5c_sku_id'];
        return $this->model
            ->table($query . ' compen')
            ->field(['compen.*', 'group_concat(compen_guds.b5c_sku_id) as b5c_sku_id'])
            ->join('tb_order_wms_compensation_guds as compen_guds on compen.id = compen_guds.compensate_id')
            ->group('compen.id')
            ->select();
    }

    public function getExportListById($ids)
    {

        return $this->model
            ->table('tb_order_wms_compensation as compen')
            ->field([
                'compen.*', 'compen_guds.*', 'op_order.ADDRESS_USER_NAME as address_user_name',
                'op_order.ADDRESS_USER_ADDRESS1', 'op_order.ADDRESS_USER_ADDRESS2', 'op_order.ADDRESS_USER_COUNTRY',
                'op_order.ADDRESS_USER_CITY', 'op_order.ADDRESS_USER_PROVINCES'
            ])
            ->join('left join tb_order_wms_compensation_guds as compen_guds on compen.id = compen_guds.compensate_id')
            ->join('left join tb_op_order as op_order on op_order.ORDER_ID = compen.order_id and op_order.PLAT_CD = compen.plat_cd')
            ->where(['compen.id' => ['in', $ids]])
            ->select();
    }

    public function CompenListCount($where)
    {

        $query = $this->model
            ->table('tb_order_wms_compensation as compen')
            ->join('left join tb_order_wms_compensation_guds as compen_guds on compen.id = compen_guds.compensate_id')
            ->where($where)
            ->field(['compen.id'])
            ->group('compen.id')
            ->select(false);
        return $this->model->table($query . ' a')->count();
    }

    public function createCompenNo()
    {
        #查找当天的最大赔付单号
        $compenNo = "PF" . date('Ymd');
        $compenLastNo = $this->model
            ->table('tb_order_wms_compensation')
            ->where(['created_at' => ['EGT', date("Y-m-d")]])
            ->order('id desc')
            ->getField('compensate_no');

        return $compenNo = !empty($compenLastNo) ? $compenNo . str_pad((int)substr($compenLastNo, -4) + 1, 4, "0", STR_PAD_LEFT) : $compenNo . '0001';

    }

    public function compenUpdate($data, $where)
    {
        return $this->model
            ->table('tb_order_wms_compensation')
            ->where($where)
            ->save($data);
    }

    public function createCompen($data)
    {
        return $this->model->table('tb_order_wms_compensation')->add($data);
    }

    public function createCompenGuds($data)
    {
        return $this->model->table('tb_order_wms_compensation_guds')->addAll($data);
    }

    public function addLog($data)
    {
        return $this->model->table('tb_order_wms_compensation_log')->add($data);
    }

    public function compenLog($where)
    {
        return $this->model->table('tb_order_wms_compensation_log')->where($where)->select();
    }

    public function getWidByUid($user_ids)
    {
        $user_ids = (array) $user_ids;
        if (empty($user_ids)) {
            return;
        }
        $where = ['bbm_admin.M_ID' => ['in', $user_ids]];
        $user_info = $this->model->table('bbm_admin')
            ->field('b.wid, bbm_admin.M_NAME')
            ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
            ->where($where)
            ->group('b.uid')
            ->select(); // 防止多个相同账号的情况（比如个别用户先辞职后重新入职，tb_hr_empl_wx.uid有出现一对多的情况）
        $user_info = array_unique(array_column($user_info, 'wid'));
        return $user_info;

    }

    public function getWidByRole()
    {
        return $this->model->table('bbm_role AS role')
            ->field(['wx.wid', 'admin.M_NAME'])
            ->join('bbm_admin_role AS admin_role ON role.ROLE_ID = admin_role.ROLE_ID')
            ->join('bbm_admin AS admin ON admin_role.M_ID = admin.M_ID')
            ->join('tb_hr_empl_wx AS wx ON wx.uid = admin.empl_id')
            ->where(['role.ROLE_NAME' => '物流赔付人员'])
            ->order('wx.wid')
            ->select();

    }

    public function getUidByRole($roleName)
    {
        if (empty($roleName)) {
            return;
        }
        return $this->model->table('bbm_role AS role')
            ->field(['admin_role.M_ID'])
            ->join('bbm_admin_role AS admin_role ON role.ROLE_ID = admin_role.ROLE_ID')
            ->where(['role.ROLE_NAME' => ['in', $roleName]])
            ->order('admin_role.M_ID')
            ->select();

    }
}
