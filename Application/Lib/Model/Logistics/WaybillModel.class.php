<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/11/6
 * Time: 11:22
 */
class WaybillModel extends Model{
    
    protected $trueTableName = 'tb_ms_logistics_waybill';
    protected $_map = array(
        'id' => 'ID',
        'logisticsCode' => 'LOGISTICS_CODE',
        'logisticsModeId' => 'LOGISTICS_MODE_ID',
        'templateCode' => 'WAYBILL_TEMPLATE',
        'sourceCode' => 'WAYBILL_SOURCE',
        'is_enable' => 'IS_ENABLE',
        'creator' => 'CREATOR',
        'create_time' => 'CREATE_TIME',
        'update_time' => 'UPDATE_TIME',
    );
    
    /**
     * 根据物流面单ID读取面单模板类型以及信息
     * @param $id
     * @return mixed
     */
    public function getWaybillById($id){
        return $this->where("ID IN ({$id})")->select();
    }
    
    /**
     * 搜索查询 面单管理列表
     * @param array $condition 查询条件[logisticsCode,logisticsModeId,templateCode,sourceCode]
     * @return mixed
     */
    public function searchWaybill($condition)
    {
        $where = "1";
        !empty($condition['logisticsCode']) && $where .= " AND W.LOGISTICS_CODE='{$condition['logisticsCode']}'";//CD
        !empty($condition['logisticsModeId']) && $where .= " AND W.LOGISTICS_MODE_ID={$condition['logisticsModeId']}";
        !empty($condition['templateCode']) && $where .= " AND W.WAYBILL_TEMPLATE='{$condition['templateCode']}'";
        !empty($condition['sourceCode']) && $where .= " AND W.WAYBILL_SOURCE='{$condition['sourceCode']}'";
    
        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND W.CREATE_TIME>=DATE('{$condition['startTime']}') ";
            $where .= " AND W.CREATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }
    
        $sql = "SELECT W.*,M.LOGISTICS_MODE AS logisticsModeName FROM `tb_ms_logistics_waybill` AS W
                LEFT JOIN tb_ms_logistics_mode AS M ON W.LOGISTICS_MODE_ID=M.id WHERE {$where} ";
    
        if (isset($condition['start']) && !empty($condition['rows'])){
            $sql .= "LIMIT {$condition['start']}, {$condition['rows']}";
            $result = $this->query($sql);
        } else {
            $result = $this->query($sql);
        }
        //echo $this->getLastSql();
        return $result;
    }
    
    /**
     * 查询符合条件的数据总数
     * @param array $condition 查询条件[logisticsCode,logisticsMode,serviceCode,startTime]
     * @return int 数量
     */
    public function getTotalWaybill($condition){
        $where = "1";
        !empty($condition['logisticsCode']) && $where .= " AND W.LOGISTICS_CODE='{$condition['logisticsCode']}'";//CD
        !empty($condition['logisticsModeId']) && $where .= " AND W.LOGISTICS_MODE_ID={$condition['logisticsModeId']}";
        !empty($condition['templateCode']) && $where .= " AND W.WAYBILL_TEMPLATE='{$condition['templateCode']}'";
        !empty($condition['sourceCode']) && $where .= " AND W.WAYBILL_SOURCE='{$condition['sourceCode']}'";
    
        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND W.CREATE_TIME>=DATE('{$condition['startTime']}') ";
            $where .= " AND W.CREATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }
        
        $sql = "SELECT COUNT(W.id) AS total FROM `tb_ms_logistics_waybill` AS W
                LEFT JOIN tb_ms_logistics_mode AS M ON W.LOGISTICS_MODE_ID=M.id WHERE {$where} ";
    
        $count = $this->query($sql);
        //echo $this->getLastSql();
        $total = end($count);
        return $total['total'];
    }
    
    /**
     * 创建新数据。
     * @param array $data 数据[relationId,logisticsCode,logisticsMode,serviceCode]
     * @return bool|mixed
     */
    public function createWaybill($data){
        
        if (empty($data['logisticsCode']) || empty($data['logisticsModeId']) || empty($data['templateCode'])) {
            return false;
        }
        
        $timeNow = date('Y-m-d H:i:s', time());
        
        $newData['LOGISTICS_CODE'] = $data['logisticsCode'];
        $newData['LOGISTICS_MODE_ID'] = $data['logisticsModeId'];
        $newData['WAYBILL_TEMPLATE'] = $data['templateCode'];
        $newData['WAYBILL_SOURCE'] = empty($data['sourceCode']) ? 'N001780100' : $data['sourceCode'];//默认快递一百
        $newData['IS_ENABLE'] = 1;
        $newData['CREATOR'] = $_SESSION['m_loginname'];
        $newData['CREATE_TIME'] = $timeNow;
        $newData['UPDATE_TIME'] = $timeNow;
        
        $res =  $this->add($newData);
        #echo $this->getLastSql();
        return $res;
    }
    
    /**
     * 更新数据
     * @param array $data 要更新的新数据
     * @param array $condition 更新条件
     * @return bool
     */
    public function updateWaybill($data, $condition){
        if (empty($data) || empty($condition)){
            return false;
        }
        
        if (empty($condition['id'])){
            $this->error = L("INVALID_PARAMS");
            return false;
        }
        
        !empty($data['logisticsCode']) && $newData['LOGISTICS_CODE'] = $data['logisticsCode'];
        !empty($data['logisticsModeId']) && $newData['LOGISTICS_MODE_ID'] = $data['logisticsModeId'];
        !empty($data['templateCode']) && $newData['WAYBILL_TEMPLATE'] = $data['templateCode'];
        !empty($data['sourceCode']) && $newData['WAYBILL_SOURCE'] = $data['sourceCode'];
        isset($data['isEnable']) && $newData['IS_ENABLE'] = trim($data['isEnable']);
        $newData['UPDATE_TIME'] = date('Y-m-d H:i:s', time());
        
        $res = $this->where('ID='.$condition['id'])->save($newData);
        //echo $this->getLastSql();
        return $res;
    }
    
    /**
     * 删除
     * @param array $condition 删除条件[id]
     * @return bool
     */
    public function deleteWaybill($condition){
        if (empty($condition['id'])){
            $this->error = L("INVALID_PARAMS");
            return false;
        }
        
        $res = $this->where("ID IN({$condition['id']})")->delete();
        return $res;
    }
}