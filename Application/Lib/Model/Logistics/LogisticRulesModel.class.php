<?php
/**
 * 物流规则数据处理模型.
 * 
 * User: afanti
 * Date: 2017/10/12
 * Time: 15:31
 * 
 * CREATE TABLE `tb_ms_logistics_rules` (
 * `ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
 * `RULE_NAME` varchar(100) NOT NULL COMMENT '规则名称',
 * `DESTN_COUNTRY` varchar(50) NOT NULL COMMENT '物流目的地国家,只可以有一个',
 * `DESTN_CITY` varchar(50) DEFAULT NULL COMMENT '物流目的地城市,只可以有一个',
 * `SALE_CHANNEL` varchar(1000) NOT NULL COMMENT '销售渠道|平台,CODE码，多个用逗号分隔',
 * `SHIPPING_WHSE` varchar(50) NOT NULL COMMENT '出货仓库 CODE码只允许一个',
 * `LOGISTICS_CODE` varchar(50) NOT NULL COMMENT '那一家快递,CODE码，来自字典表，只允许一个',
 * `LOGISTICS_MODE` varchar(100) NOT NULL COMMENT '物流方式，例如：大韩通运或者德邦E邮宝，与快递公司有关',
 * `CREATOR` VARCHAR(50) NOT NULL COMMENT '创建人',
 * `REMARK` varchar (200) DEFAULT NULL COMMENT '备注内容,可为空',
 * `CREATE_TIME` datetime NOT NULL COMMENT '添加时间',
 * `UPDATE_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
 * `IS_DELETE` tinyint(2) DEFAULT '0' COMMENT '是否删除,默认0=未删除,1=删除',
 * PRIMARY KEY (`ID`),
 * KEY `destination` (`DESTN_COUNTRY`,`DESTN_CITY`),
 * KEY `guds` (`SALE_CHANNEL`,`SHIPPING_WHSE`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8
 */

class LogisticRulesModel extends Model {
    
    protected $trueTableName = 'tb_ms_logistics_rules';
    
    protected $_map = [
        'id' => 'ID',
        'ruleName' => 'RULE_NAME',
        'destnCountry' => 'DESTN_COUNTRY',
        'destnCity' => 'DESTN_CITY',
        'saleChannel' => 'SALE_CHANNEL',
        'warehouse' => 'SHIPPING_WHSE',
        'logisticsCode' => 'LOGISTICS_CODE',
        'logisticsMode' => 'LOGISTICS_MODE',
        'creator' => 'CREATOR',
        'remark' => 'REMARK',
        'createTime' => 'CREATE_TIME',
        'updateTime' => 'UPDATE_TIME',
        'isDelete' => 'IS_DELETE',
        'isEnable' => 'IS_ENABLE'
    ];
    
    /**
     * 添加新的物流规则
     * @param array $data 数据
     * @return mixed
     */
    public function addRule($data){
        $rule = [];
        $timeNow = date('Y-m-d H:i:s', time());
        $rule['RULE_NAME'] = !empty($data['ruleName']) ? $data['ruleName'] : 'CommonRule';
        $rule['DESTN_COUNTRY'] = !empty($data['destnCountry']) ? $data['destnCountry'] : NULL; //请在Action层验证
        $rule['DESTN_CITY'] = !empty($data['destnCity']) ? $data['destnCity'] : NULL;//请在Action层验证
        $rule['SALE_CHANNEL'] = !empty($data['saleChannel']) ? $data['saleChannel'] : '';//请在Action层验证
        $rule['SHIPPING_WHSE'] = !empty($data['warehouse']) ? $data['warehouse'] : NULL;//请在Action层验证
        $rule['LOGISTICS_CODE'] = !empty($data['logisticsCode']) ? $data['logisticsCode'] : '';
        $rule['LOGISTICS_MODE'] = !empty($data['logisticsMode']) ? $data['logisticsMode'] : '';
        $rule['CREATOR'] = $_SESSION['m_loginname'];
        $rule['REMARK'] = !empty($data['remark']) ? $data['remark'] :  NULL;
        $rule['CREATE_TIME'] = $timeNow;
        $rule['UPDATE_TIME'] = $timeNow;
        $rule['IS_DELETE'] = 0;
        $rule['IS_ENABLE'] = 1;
        
        return $this->add($rule);
    }
    
    /**
     * 更新物流规则
     * @param array $data 要更新新数据
     * @param array $condition 更新条件
     * @return bool 影响行数 | false 失败
     */
    public function updateRule($data, $condition){
        if (empty($data) || empty($condition['id'])){
            return false;
        }
    
        !empty($data['ruleName']) && $rule['RULE_NAME'] = $data['ruleName'];
        !empty($data['destnCountry']) && $rule['DESTN_COUNTRY'] =  $data['destnCountry'];
        !empty($data['destnCity']) && $rule['DESTN_CITY'] =  $data['destnCity'];
        !empty($data['saleChannel']) && $rule['SALE_CHANNEL'] =  $data['saleChannel'] ;
        !empty($data['warehouse']) && $rule['SHIPPING_WHSE'] =  $data['warehouse'] ;
        !empty($data['logisticsCode']) && $rule['LOGISTICS_CODE'] = $data['logisticsCode'];
        !empty($data['logisticsMode']) && $rule['LOGISTICS_MODE'] = $data['logisticsMode'];
        isset($data['isEnable']) && $rule['IS_ENABLE'] = $data['isEnable'];
        !empty($data['creator']) && $rule['CREATOR'] = $data['creator'];
        !empty($data['remark']) && $rule['REMARK'] = $data['remark'];
        //!empty($data['isDelete']) && $rule['IS_DELETE'] = $data['isDelete']; //删除必须走 删除接口
        
        #$where = "IS_DELETE = 0";
        $where = "1";
        if (!empty($condition['id'])) {
            $where .= " AND ID={$condition['id']}";
        }
        
        if (!empty($condition['ruleName']))
        {
            $where .= " AND `RULE_NAME` = '{$condition['ruleName']}'";
        }
        
        $res = $this->where($where)->save($rule);
        //echo $this->getLastSql();
        return $res;
    }
    
    /**
     * 查询规则
     * @param array $condition 查询条件数组
     * @return mixed
     */
    public function getRules($condition){
        $where = "R.IS_DELETE = 0";
        !empty($condition['id']) && $where .= " AND R.ID={$condition['id']}";
        !empty($condition['ruleName']) && $where .= " AND R.`RULE_NAME` LIKE '%{$condition['ruleName']}%'";
        !empty($condition['destnCountry']) && $where .= " AND R.DESTN_COUNTRY = '{$condition['destnCountry']}'";
        !empty($condition['warehouse']) && $where .= " AND R.SHIPPING_WHSE='{$condition['warehouse']}'";
        !empty($condition['logisticsCode']) && $where .= " AND R.LOGISTICS_CODE='{$condition['logisticsCode']}'";
        !empty($condition['logisticsMode']) && $where .= " AND R.LOGISTICS_MODE='{$condition['logisticsMode']}'";
        !empty($condition['isEnable']) && $where .= " AND R.IS_ENABLE={$condition['isEnable']}";
    
        //从逗号分隔的字符串字段中，查询包含指定的单个值的数据记录条件。
        if(!empty($condition['saleChannel']))
        {
            $channelList= explode(',', $condition['saleChannel']);
            $channelCondition = "";
            foreach ($channelList as $channel){
                $channelCondition .= " FIND_IN_SET('{$channel}', R.SALE_CHANNEL) OR ";
            }
            $where .= " AND (" . rtrim($channelCondition, 'OR ') . " ) ";//移除最后的空格和OR
        }
        
        //按添加时间范围查询
        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND R.CREATE_TIME>=DATE('{$condition['startTime']}') ";
            $where .= " AND R.CREATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }
    
        $sql = "SELECT R.*,M.LOGISTICS_MODE AS logisticsModeName FROM `tb_ms_logistics_rules` AS R
                LEFT JOIN tb_ms_logistics_mode AS M ON R.LOGISTICS_MODE=M.id WHERE {$where} ";
        
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
     * 查询符合条件的数据数量。
     * @param $condition
     * @return mixed
     */
    public function getRulesCount($condition){
        $where = "R.IS_DELETE != 1";
        !empty($condition['id']) && $where .= " AND R.ID={$condition['id']}";
        !empty($condition['ruleName']) && $where .= " AND R.`RULE_NAME` LIKE '%{$condition['ruleName']}%'";
        !empty($condition['destnCountry']) && $where .= " AND R.DESTN_COUNTRY = '{$condition['destnCountry']}'";
        !empty($condition['warehouse']) && $where .= " AND R.SHIPPING_WHSE='{$condition['warehouse']}'";
        !empty($condition['logisticsCode']) && $where .= " AND R.LOGISTICS_CODE='{$condition['logisticsCode']}'";
        !empty($condition['logisticsMode']) && $where .= " AND R.LOGISTICS_MODE='{$condition['logisticsMode']}'";
        !empty($condition['isEnable']) && $where .= " AND R.IS_ENABLE={$condition['isEnable']}";
        
        //从逗号分隔的字符串字段中，查询包含指定的单个值的数据记录条件。
        if(!empty($condition['saleChannel']))
        {
            $channelList= explode(',', $condition['saleChannel']);
            $channelCondition = "";
            foreach ($channelList as $channel){
                $channelCondition .= " FIND_IN_SET('{$channel}', R.SALE_CHANNEL) OR ";
            }
            $where .= " AND (" . rtrim($channelCondition, 'OR ') . " ) ";//移除最后的空格和OR
        }
    
        //按添加时间范围查询
        if (!empty($condition['startTime'])){
            $endTime = !empty($condition['endTime']) ? $condition['endTime'] : date('Y-m-d H:i:s');
            $where .= " AND R.CREATE_TIME>=DATE('{$condition['startTime']}') ";
            $where .= " AND R.CREATE_TIME<=DATE('{$endTime}') + INTERVAL 1 DAY ";
        }
        
        $sql = "SELECT COUNT(R.id) AS total FROM `tb_ms_logistics_rules` AS R
                LEFT JOIN tb_ms_logistics_mode AS M ON R.LOGISTICS_MODE=M.id WHERE {$where} ";
        
        $count = $this->query($sql);
        
        //echo $this->getLastSql();
        $total = end($count);
        return $total['total'];
    }
    
    /**
     * 删除规则
     * @param array $condition 删除条件
     * @return mixed
     */
    public function deleteRule($condition){
        if (empty($condition)){
            return false;
        }
        
        $where = "1";
        if (!empty($condition['id'])) {
            $where .= " AND ID IN ({$condition['id']})";
        }
    
        if (!empty($condition['ruleName']))
        {
            $where .= " AND `RULE_NAME` = '{$condition['ruleName']}'";
        }
        
        return $this->where($where)->setField('IS_DELETE', 1);//执行逻辑删除
    }
    
}