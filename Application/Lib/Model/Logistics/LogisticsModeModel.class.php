<?php

/**
 * 物流模式数据模型，物流方式
 * User: afanti
 * Date: 2017/10/23
 * Time: 17:08
 */
class LogisticsModeModel extends model
{

    protected $trueTableName = 'tb_ms_logistics_mode';

    // 设置当前模型的数据表前缀
    protected $tablePrefix = 'tb_ms_';

    protected $_map = array(
        'id' => 'ID',
        'logistics_code' => 'LOGISTICS_CODE',
        'logistics_mode' => 'LOGISTICS_MODE',
        'service_code' => 'SERVICE_CODE',
        'creator' => 'CREATOR',
        'create_time' => 'CREATE_TIME',
        'update_time' => 'UPDATE_TIME',
        'is_enable' => 'IS_ENABLE',
        'is_delete' => 'IS_DELETE',
        'remark' => 'REMARK',
    );

    /**
     * 根据ID读取单个物流模式数据
     *
     * @param int $id 物流模式id
     *
     * @return array 数据或者空
     */
    public function getModeById($id)
    {
        return $this->where("ID={$id}")->find();
    }

    /**
     * 搜索物流模式
     *
     * @param array $condition 搜索条件[logisticsCode,logisticsMode,serviceCode,startTime, start, rows]
     *
     * @return array 数据数组或者false
     */
    public function searchMode($condition = [], $type)
    {
        $where = "IS_DELETE=0";
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'mode') {
            $where .= " AND LOGISTICS_MODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'serviceCode') {
            $where .= " AND SERVICE_CODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCompanyCode'])) {
            $where_logistics_code_str = '';
            if (strstr(',', $condition['logisticsCompanyCode']) !== 0) {
                $logistics_code_arr = explode(',', $condition['logisticsCompanyCode']);
                foreach ($logistics_code_arr as $logistics_code) {
                    $where_logistics_code_str .= "'{$logistics_code}',";
                }
                $where_logistics_code_str = trim($where_logistics_code_str, ',');
            } else {
                $where_logistics_code_str = "'{$condition['logisticsCompanyCode']}'";
            }
            $where .= " AND LOGISTICS_CODE IN ({$where_logistics_code_str})";
        }
        !empty($condition['surface']) && $where .= " AND SURFACE_WAY_GET_CD like '%{$condition['surface']}%'";
        //!empty($condition['logisticsCode']) && $where .= " AND LOGISTICS_CODE='{$condition['logisticsCode']}'";//CD
        !empty($condition['logisticsMode']) && $where .= " AND LOGISTICS_MODE like '%{$condition['logisticsMode']}%'";
        !empty($condition['serviceCode']) && $where .= " AND SERVICE_CODE='{$condition['serviceCode']}'";

        if (!empty($condition['startTime']) or !empty($condition['endTime'])) {
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');

            if (!empty($condition['startTime'])) $where .= " AND UPDATE_TIME>=DATE('{$condition['startTime']}') ";
            if (!empty($condition['endTime'])) $where .= " AND UPDATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }

        if (isset($condition['start']) && !empty($condition['rows'])) {
            $result = $this->where($where)->limit($condition['start'], $condition['rows'])->order('UPDATE_TIME desc')->select();
        } else {
            $result = $this->where($where)->order('UPDATE_TIME desc')->select();
        }
        if ($type == true) {
            return $result;
        }
        if ($type == 'export') {
            if (isset($condition['start']) && !empty($condition['rows'])) {
                $result = $this
                    ->field("ID,LOGISTICS_CODE,LOGISTICS_MODE,SERVICE_CODE,CREATOR,CREATE_TIME,UPDATE_TIME,IS_ENABLE,need_gift,logistics_account_info_id")
                    ->where($where)
                    ->limit($condition['start'], $condition['rows'])
                    ->order('UPDATE_TIME desc')
                    ->select();
            } else {
                $result = $this
                    ->field("ID,LOGISTICS_CODE,LOGISTICS_MODE,SERVICE_CODE,CREATOR,CREATE_TIME,UPDATE_TIME,IS_ENABLE,need_gift,logistics_account_info_id")
                    ->where($where)
                    ->order('UPDATE_TIME desc')
                    ->select();
            }
            foreach ($result as $k => $v) {
                if ($v['IS_ENABLE'] == '1') {
                    $result[$k]['IS_ENABLE'] = '已启用';
                } else {
                    $result[$k]['IS_ENABLE'] = '已停用';
                }
            }
        }
        foreach ($result as $k => $v) {
            if (!empty($v['SURFACE_WAY_GET_CD'])) {
                $SURFACE_WAY_GET_ARR_CD = explode(",", $v['SURFACE_WAY_GET_CD']);
                $resDataAll = [];
                foreach ($SURFACE_WAY_GET_ARR_CD as $v1) {
                    $resData = M("ms_cmn_cd", "tb_")->where("CD='{$v1}'")->field("CD_VAL")->find();
                    $resDataAll[] = $resData['CD_VAL'];
                }
                $resData1 = implode(",", $resDataAll);
                $result[$k]['SURFACE_WAY_GET_NAME'] = $resData1;
            }
        }
        return $result;
    }

    /**
     * 搜索物流模式
     *
     * @param array $condition 搜索条件[logisticsCode,logisticsMode,serviceCode,startTime, start, rows]
     *
     * @return array 数据数组或者false
     */
    public function searchWhere($condition = [])
    {
        $where = "tb_ms_logistics_mode.IS_DELETE=0";
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'mode') {
            $where .= " AND tb_ms_logistics_mode.LOGISTICS_MODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'serviceCode') {
            $where .= " AND tb_ms_logistics_mode.SERVICE_CODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCompanyCode'])) {
            $where_logistics_code_str = '';
            if (strstr(',', $condition['logisticsCompanyCode']) !== 0) {
                $logistics_code_arr = explode(',', $condition['logisticsCompanyCode']);
                foreach ($logistics_code_arr as $logistics_code) {
                    $where_logistics_code_str .= "'{$logistics_code}',";
                }
                $where_logistics_code_str = trim($where_logistics_code_str, ',');
            } else {
                $where_logistics_code_str = "'{$condition['logisticsCompanyCode']}'";
            }
            $where .= " AND tb_ms_logistics_mode.LOGISTICS_CODE IN ({$where_logistics_code_str})";
        }
        !empty($condition['surface']) && $where .= " AND tb_ms_logistics_mode.SURFACE_WAY_GET_CD like '%{$condition['surface']}%'";
        //!empty($condition['logisticsCode']) && $where .= " AND tb_ms_logistics_mode.LOGISTICS_CODE='{$condition['logisticsCode']}'";//CD
        !empty($condition['logisticsMode']) && $where .= " AND tb_ms_logistics_mode.LOGISTICS_MODE like '%{$condition['logisticsMode']}%'";
        !empty($condition['serviceCode']) && $where .= " AND tb_ms_logistics_mode.SERVICE_CODE='{$condition['serviceCode']}'";

        if (!empty($condition['startTime']) or !empty($condition['endTime'])) {
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');

            if (!empty($condition['startTime'])) $where .= " AND tb_ms_logistics_mode.UPDATE_TIME>=DATE('{$condition['startTime']}') ";
            if (!empty($condition['endTime'])) $where .= " AND tb_ms_logistics_mode.UPDATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }

        return $where;
    }

    /**
     * 查询符合条件的数据总数
     *
     * @param array $condition 查询条件[logisticsCode,logisticsMode,serviceCode,startTime]
     *
     * @return int 数量
     */
    public function getTotalModel($condition)
    {
        $where = "IS_DELETE=0";
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'mode') {
            $where .= " AND LOGISTICS_MODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCode']) && $condition['moreSearch'] == 'serviceCode') {
            $where .= " AND SERVICE_CODE like '%{$condition['logisticsCode']}%'";
        }
        if (!empty($condition['logisticsCompanyCode'])) {
            $where_logistics_code_str = '';
            if (strstr(',', $condition['logisticsCompanyCode']) !== 0) {
                $logistics_code_arr = explode(',', $condition['logisticsCompanyCode']);
                foreach ($logistics_code_arr as $logistics_code) {
                    $where_logistics_code_str .= "'{$logistics_code}',";
                }
                $where_logistics_code_str = trim($where_logistics_code_str, ',');
            } else {
                $where_logistics_code_str = "'{$condition['logisticsCompanyCode']}'";
            }
            $where .= " AND LOGISTICS_CODE IN ({$where_logistics_code_str})";
        }

        if (!empty($condition['startTime']) or !empty($condition['endTime'])) {
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            if (!empty($condition['startTime'])) $where .= " AND UPDATE_TIME>=DATE('{$condition['startTime']}') ";
            if (!empty($condition['endTime'])) $where .= " AND UPDATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }

        return $this->where($where)->getField('COUNT(id)');
    }

    /**
     * 创建新数据。
     *
     * @param array $modeData 数据[relationId,logisticsCode,logisticsMode,serviceCode]
     *
     * @return bool|mixed
     */
    public function createMode($modeData)
    {

        if (empty($modeData['logisticsCode']) || empty($modeData['logisticsMode'])) {
            return false;
        }
        $timeNow = date('Y-m-d H:i:s', time());

        $newData['LOGISTICS_CODE'] = $modeData['logisticsCode'];
        $newData['LOGISTICS_MODE'] = $modeData['logisticsMode'];
        $newData['SERVICE_CODE'] = $modeData['serviceCode'];
        $newData['CREATOR'] = $_SESSION['m_loginname'];
        $newData['IS_ENABLE'] = $modeData['is_enable'];
        $newData['CREATE_TIME'] = $timeNow;
        $newData['UPDATE_TIME'] = $timeNow;
        $newData['REMARK'] = !empty($modeData['remark']) ? $modeData['remark'] : '';
        $newData['SURFACE_WAY_GET_CD'] = $modeData['surfaceWay_chose'];
        $newData['need_gift'] = $modeData['need_gift'];
        $newData['logistics_account_info_id'] = $modeData['logistics_account_info_id'];
        $newData['real_logistics_company_id'] = $modeData['real_logistics_company_id'];

        $res = $this->add($newData);
        #echo $this->getLastSql();
        return $res;
    }

    /**
     * 更新数据
     *
     * @param array $data      要更新的新数据
     * @param array $condition 更新条件
     *
     * @return bool
     */
    public function updateMode($data, $condition)
    {
        if (empty($data) || empty($condition)) {
            return false;
        }

        if (empty($condition['id'])) {
            $this->error = L("INVALID_PARAMS");
            return false;
        }

        !empty($condition['logisticsCode']) && $newData['LOGISTICS_CODE'] = $data['logisticsCode'];
        !empty($condition['logisticsMode']) && $newData['LOGISTICS_MODE'] = $data['logisticsMode'];

        $newData['SERVICE_CODE'] = $data['serviceCode'];
        !empty($condition['remark']) && $newData['REMARK'] = $data['remark'];
        $newData['SURFACE_WAY_GET_CD'] = $data['surfaceWay_chose'];

        $newData['IS_ENABLE'] = $data['is_enable'];

        $newData['UPDATE_TIME'] = date('Y-m-d H:i:s', time());
        $newData['CREATOR'] = $_SESSION['m_loginname'];
        $newData['need_gift'] = $data['need_gift'];
        $newData['logistics_account_info_id'] = $data['logistics_account_info_id'];
        $newData['real_logistics_company_id'] = $data['real_logistics_company_id'];

        $res = $this->where('ID=' . $condition['id'])->save($newData);

        //echo $this->getLastSql();
        return $res;
    }

    /**
     * 删除
     *
     * @param array $condition 删除条件[id]
     *
     * @return bool
     */
    public function deleteMode($condition)
    {
        if (empty($condition['id'])) {
            $this->error = L("INVALID_PARAMS");
            return false;
        }

        $res = $this->where("ID IN({$condition['id']})")->setField('IS_DELETE', 1);
        return $res;
    }

}