<?php
/**
 * User: yangsu
 * Date: 19/8/26
 * Time: 14:38
 */

import('ORG.Util.Page');// 导入分页类
class AllocationExtendAttributionRepository extends Repository
{


    public function index($where, $limit, $is_excel = false)
    {
        $field = ['tb_wms_allo_attribution.*,tb_wms_allo.allo_no'];
        $query  = $this->model->table(
            'tb_wms_allo_attribution')
            ->join("tb_wms_allo_attribution_sku ON tb_wms_allo_attribution.id = tb_wms_allo_attribution_sku.allo_attribution_id")
            ->join('left join ' . PMS_DATABASE . '.product_sku p_sku on tb_wms_allo_attribution_sku.sku_id = p_sku.sku_id')
            ->join("LEFT JOIN tb_wms_allo on tb_wms_allo_attribution.allo_id = tb_wms_allo.id")
            ->field($field)
            ->where($where)
            ->group('tb_wms_allo_attribution.id');
        $count_query = clone $query;
        $query_sub_query = $count_query->select(false);
        $model = M();
        $count = $model->table("{$query_sub_query} as tmp")->count();
        $pageObj       = new Page($count, $limit[1]);// 实例化分页类 传入总记录数和每页显示的记录数
        $pageObj->firstRow = $limit[0];
        $page['total'] = $count;
        $page['per_page'] = $limit[1];
        if (false === $is_excel) {
            $query->limit($pageObj->firstRow, $pageObj->listRows);
        }
        $res_db = $query
            ->order('tb_wms_allo_attribution.id desc')
            ->select();
        return [$res_db, $page];
    }

    public function show($allo_attribution_id)
    {
        $field = ['tb_wms_allo_attribution.*'];
        $where['tb_wms_allo_attribution.id'] = $allo_attribution_id;
        return $this->model->table('tb_wms_allo_attribution')
            ->field($field)
            ->where($where)
            ->select();
    }

    public function attribution($allo_attribution_id, $change_order_no = null)
    {
        $field = ['tb_wms_allo_attribution.*'];
        if (!empty($allo_attribution_id)) {
            $where['tb_wms_allo_attribution.id'] = $allo_attribution_id;
        }
        if (!empty($change_order_no)) {
            $where['tb_wms_allo_attribution.change_order_no'] = $change_order_no;
        }
        return $this->model->table('tb_wms_allo_attribution')
            ->field($field)
            ->where($where)
            ->find();
    }

    public function attributionSkus($allo_attribution_id)
    {
        $field = ['tb_wms_allo_attribution_sku.*,tb_wms_bill.warehouse_id AS warehouse_cd,
        tb_wms_bill.SP_TEAM_CD AS purchasing_team,
        tb_wms_bill.ascription_store AS store_id,tb_wms_batch.batch_code AS batch_no,
        tb_wms_batch.small_sale_team_code,
        tb_wms_batch.vir_type'
        ];
        $where['tb_wms_allo_attribution_sku.allo_attribution_id'] = $allo_attribution_id;
        $where_string = 'tb_wms_allo_attribution_sku.batch_id = tb_wms_batch.id AND tb_wms_batch.bill_id = tb_wms_bill.id';
        return $this->model->table('tb_wms_allo_attribution_sku,tb_wms_batch,tb_wms_bill')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }


    public function approval($data)
    {
        $where['tb_wms_allo_attribution.id'] = $data['id'];
        $where['tb_wms_allo_attribution.review_type_cd'] = TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT;
        $now = DateModel::now();
        if (TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED == $data['review_type_cd']) {
            $save['tb_wms_allo_attribution.cancel_by'] = DataModel::userNamePinyin();
            $save['tb_wms_allo_attribution.cancel_at'] = $now;
        } else {
            if ('erpadmin' != strtolower(DataModel::userNamePinyin())) {
                $where['tb_wms_allo_attribution.review_by'] = DataModel::userNamePinyin();
            }
            $save['tb_wms_allo_attribution.review_at'] = $now;
        }
        $save['tb_wms_allo_attribution.review_type_cd'] = $data['review_type_cd'];
        $save['tb_wms_allo_attribution.updated_by'] = DataModel::userNamePinyin();
        $save['tb_wms_allo_attribution.updated_at'] = $now;
        $save['tb_wms_allo_attribution.review_by'] = NULL;//审核完成，当前审核人清空
        return $this->model->table('tb_wms_allo_attribution,tb_wms_allo_attribution_sku')
            ->where($where)
            ->save($save);
    }

    public function createAlloAttribution($save)
    {
        $add = $this->model->table('tb_wms_allo_attribution')
            ->add($save);
        return $add;
    }
    public function updateAttr($where,$data){
        $re = $this->model->table('tb_wms_allo_attribution')
            ->where($where)
            ->save($data);
        return $re;
    }
    public function createAlloAttributionSku($save)
    {
        return $this->model->table('tb_wms_allo_attribution_sku')
            ->addAll($save);
    }

    public function getStoreBy($store_id)
    {
        $where['id'] = $store_id;
        return $this->model->table('tb_ms_store')
            ->where($where)
            ->getField('store_by');
    }

    public function getStoreName()
    {
        $res_db = $this->model->table('tb_ms_store')
            ->field('ID,STORE_NAME')
            ->select();
        return array_column($res_db, 'STORE_NAME', 'ID');
    }

    public function updateAttrById($attr_id,$allo_id){
        $this->model->table('tb_wms_allo_attribution')->where(['id'=> $attr_id])->save(['allo_id'=>$allo_id]);
    }

    public function findAttrByWhere($where){
        return $this->model->table('tb_wms_allo_attribution')
            ->where($where)
            ->find();
    }

    public function findAttrById($attr_id){
        return $this->model->table('tb_wms_allo_attribution') ->where(['id'=> $attr_id])->find();
    }

    public function getAttrByIds($attr_ids){
        $attr_ids = (array) $attr_ids;
        $attr_data = $this->model->table('tb_wms_allo_attribution')
            ->field(['id', 'change_order_no', 'reviewer_by', 'review_by', 'allo_id', 'review_type_cd'])
            ->where(['id'=> ['in', $attr_ids]])
            ->select();
        return array_column($attr_data, null, 'id');
    }

    /**
     * @param $attr_id 归属单id
     * @param $next_review_by 下个审核人
     * @param $review_type_cd 审核状态
     * @return mixed
     */
    public function nextApproval($attr_id, $next_review_by, $review_type_cd)
    {
        $where = ['id' => $attr_id];
        $save_data = [
            'review_by'      => $next_review_by,
            'review_type_cd' => $review_type_cd,
        ];
        return $this->updateAttr($where, $save_data);
    }

    public function addReviewLog($log_data)
    {
        return $this->model->table('tb_wms_allo_attribution_review_log')->add($log_data);
    }

    public function getReviewLogs($attr_id)
    {
        return $this->model->table('tb_wms_allo_attribution_review_log')->where(['allo_attribution_id' => $attr_id])->select();
    }

    //调拨触发的库存归属审核
    public function approvalByAllocation($data)
    {
        $where['tb_wms_allo_attribution.id'] = $data['id'];
        $where['tb_wms_allo_attribution.review_type_cd'] = TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT;
        $now = DateModel::now();
        if (TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED == $data['review_type_cd']) {
            $save['tb_wms_allo_attribution.cancel_by'] = DataModel::userNamePinyin();
            $save['tb_wms_allo_attribution.cancel_at'] = $now;
        } else {
            $save['tb_wms_allo_attribution.review_at'] = $now;
        }
        $save['tb_wms_allo_attribution.review_type_cd'] = $data['review_type_cd'];
        $save['tb_wms_allo_attribution.updated_by'] = DataModel::userNamePinyin();
        $save['tb_wms_allo_attribution.updated_at'] = $now;
        $save['tb_wms_allo_attribution.review_by'] = NULL;//审核完成，当前审核人清空
        return $this->model->table('tb_wms_allo_attribution,tb_wms_allo_attribution_sku')
            ->where($where)
            ->save($save);
    }
}