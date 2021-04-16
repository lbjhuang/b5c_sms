<?php
import("@.Model.StringModel");
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/10/13
 * Time: 14:14
 */
class LogisticsRelationModel extends Model{
    protected $trueTableName ='tb_ms_logistics_relation';

    /**
     * 搜索读取物流关系列表数据
     * @param $condition
     * @return mixed
     */
    public function getRelations($condition)
    {
        $where = "is_delete=0";//未删除的
        !empty($condition['id']) && $where .= " AND id={$condition['id']}";
        !empty($condition['ownCode']) && $where .= " AND b5c_logistics_cd = '{$condition['ownCode']}'";
        !empty($condition['thirdCode']) && $where .= " AND third_logistics_cd='{$condition['thirdCode']}'";
        !empty($condition['platCode']) && $where .= " AND plat_CD='{$condition['platCode']}'";
        !empty($condition['logisticName']) && $where .= " AND logistics_name LIKE '%{$condition['logisticName']}%'";
        !empty($condition['partnerId']) && $where .= " AND partner_id='{$condition['partnerId']}'";
        !empty($condition['partnerKey']) && $where .= " AND partner_key='{$condition['partnerKey']}'";

        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND create_time>=DATE('{$condition['startTime']}')";
            $where .= " AND create_time<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }

        if (isset($condition['start']) && !empty($condition['rows'])){
            $result = $this->where($where)->limit($condition['start'], $condition['rows'])->select();
        } else {
            $result = $this->where($where)->select();
        }

//        echo  $this->getLastSql();
        return $result;
    }

    /**
     * 读取物流规则总数量
     *
     * @param $condition
     * @return mixed
     */
    public function getRelationCount($condition){
        $where = "is_delete=0";//未删除的
        !empty($condition['id']) && $where .= " AND id={$condition['id']}";
        !empty($condition['ownCode']) && $where .= " AND b5c_logistics_cd = '{$condition['ownCode']}'";
        !empty($condition['thirdCode']) && $where .= " AND third_logistics_cd='{$condition['thirdCode']}'";
        !empty($condition['platCode']) && $where .= " AND plat_CD='{$condition['platCode']}'";
        !empty($condition['logisticName']) && $where .= " AND logistics_name LIKE '%{$condition['logisticName']}%'";
        !empty($condition['partnerId']) && $where .= " AND partner_id='{$condition['partnerId']}'";
        !empty($condition['partnerKey']) && $where .= " AND partner_key='{$condition['partnerKey']}'";

        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND create_time>=DATE('{$condition['startTime']}')";
            $where .= " AND create_time<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }

        return $this->where($where)->getField('COUNT(id)');
    }

    /**
     * 添加一条物流关系
     * @param $data
     * @return mixed
     */
    public function addRelation($data)
    {
        $relation = [];
        $timeNow = date('Y-m-d H:i:s', time());
        $relation['b5c_logistics_cd'] = !empty($data['ownCode']) ? $data['ownCode'] : '';
        $relation['third_logistics_cd'] = !empty($data['thirdCode']) ? $data['thirdCode'] : NULL;
        $relation['plat_cd'] = !empty($data['platCode']) ? $data['platCode'] : NULL;//请在Action层验证
        $relation['logistics_name'] = !empty($data['logisticName']) ? $data['logisticName'] : '';//请在Action层验证
        $relation['partner_id'] = !empty($data['partnerId']) ? $data['partnerId'] : NULL;//请在Action层验证
        $relation['partner_key'] = !empty($data['partnerKey']) ? $data['partnerKey'] : '';
        $relation['create_user'] = $_SESSION['m_loginname'];
        $relation['create_time'] = $timeNow;
        $relation['update_time'] = $timeNow;
        $relation['is_delete'] = 0;
        $relation['third_logistics_cd'] = \Application\Lib\Model\StringModel::replaceNonBreakingSpace($relation['third_logistics_cd']);
        $res = $this->add($relation);
        return $res;
    }

    /**
     * 更新一条物流关系
     * @param $data
     * @param $condition
     * @return bool
     */
    public function updateRelation($data, $condition)
    {
        if (empty($data) || empty($condition['id'])){
            return false;
        }

        !empty($data['ownCode']) && $relation['b5c_logistics_cd'] = $data['ownCode'];
        !empty($data['thirdCode']) && $relation['third_logistics_cd'] =  $data['thirdCode'];
        !empty($data['platCode']) && $relation['plat_cd'] =  $data['platCode'];
        !empty($data['logisticName']) && $relation['logistics_name'] =  $data['logisticName'] ;
        !empty($data['partnerId']) && $relation['partner_id'] =  $data['partnerId'] ;
        !empty($data['partnerKey']) && $relation['partner_key'] = $data['partnerKey'];

        //实际没有提供要修改的值，不做更新返回false
        if (empty($relation)) {
            return false;
        }

        $where = "1";
        if (!empty($condition['id'])) {
            $where .= " AND ID={$condition['id']}";
        } else if (!empty($condition['ownCode']) && !empty($condition['platCode'])) {
            $where = "b5c_logistics_cd='{$condition['ownCode']}' AND plat_cd='{$condition['platCode']}'";
        }
        $relation['third_logistics_cd'] = \Application\Lib\Model\StringModel::replaceNonBreakingSpace($relation['third_logistics_cd']);
        $res = $this->where($where)->save($relation);
        //echo $this->getLastSql();
        return $res;
    }

    /**
     * 删除一条物流关系
     * 支持两种途径：
     *  1、主键ID直接删除。
     *  2、根据字典表CODE码和销售渠道平台CODE码来删除。
     * @param array $condition
     * @return bool
     */
    public function deleteRelation($condition)
    {
        if (empty($condition['id'])){
            return  false;
        }

        $where = " ID IN({$condition['id']})";

        if (!empty($condition['ownCode']))
        {
            $where .= " AND `b5c_logistics_cd` = '{$condition['ownCode']}'";
        }

        if (!empty($condition['platCode']))
        {
            $where .= " AND `plat_cd` = '{$condition['platCode']}'";
        }

        if (!empty($condition['thirdCode']))
        {
            $where .= " AND `third_logistics_cd` = '{$condition['thirdCode']}'";
        }

        return $this->where($where)->setField('is_delete', 1);
    }

}