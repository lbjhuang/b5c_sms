<?php
/**
 * 处理SKU与第三方平台店铺的SKU进行绑定
 * User: afanti
 * Date: 2017/9/4
 * Time: 13:52
 */
class SkuRelationsModel extends Model
{
    protected $trueTableName = 'tb_ms_sku_relation';
    
    /**
     * 查询指定SKU和第三方SKU 编号的绑定关系。
     * @param string $thirdSkuId 第三方的SKU ID
     * @param int $storeId 店铺ID编号
     * @return mixed
     */
    public function getRelationBySku($thirdSkuId, $storeId){
        $res = $this->where("third_sku_id='{$thirdSkuId}' AND store_id={$storeId}")
            ->find();
        
        return $res;
    }
    
    /**
     * 读取列表
     * @param int $start
     * @param int $limit
     */
    public function getList($start = 0, $limit = 20)
    {
        if (empty($condition))
        {
            $this->where("1")
                ->limit($start, $limit)
                ->select();
        }
    }
    
    /**
     * 添加新的SKU绑定关系
     * @param $data
     * @return bool|mixed
     */
    public function addNewRelation($data)
    {
        if (empty($data))
        {
            return false;
        }
        
        $timeNow = date('Y-m-d H:i:s',time());
        $save = [
            'b5c_sku_id' => $data['skuId'],
            'third_sku_id' => $data['thirdSku'],
            'store_id' => $data['store'],
            'plat_cd' => $data['platform'],
            'create_user' => $data['userName'],
            'create_time' => $timeNow,
            'update_time' => $timeNow
        ];

        return $this->add($save);
    }
    
    /**
     * 批量添加绑定数据关系。
     * @param $data
     * @return array|bool
     */
    public function batchAdd($data)
    {
        if (empty($data)){
            return false;
        }
        
        $timeNow = date('Y-m-d H:i:s', time());
        $user = $_SESSION['m_loginname'];
        $values = "";
        foreach ($data as $key => $value) {
            if ($key == 0) continue;
            if (empty($value[1]) || empty($value[2])|| empty($value[3])|| empty($value[5])){
                continue;
            }
            $values .= "('{$value[1]}','{$value[2]}',{$value[3]},'{$value[5]}','{$user}','{$timeNow}','{$timeNow}'),";
        }
        $sql = "INSERT INTO `tb_ms_sku_relation`(
                    `b5c_sku_id` ,
                    `third_sku_id` ,
                    `store_id` ,
                    `plat_cd` ,
                    `create_user` ,
                    `create_time` ,
                    `update_time`
                )
                VALUES {$values}";
        $sql = trim($sql, ',') . ';';
        //保存如果失败，记录失败信息。
        $res = $this->query($sql);
        return $res;
    }
    
    
    /**
     * 删除绑定关系。
     * @param number $skuId ERP SKU id
     * @param string $thirdSkuId 第三方的SKU ID 格式各种，所以必要字符串
     * @param int $storeId 店铺id
     * @return mixed
     */
    public function deleteRelation($skuId, $thirdSkuId, $storeId){
        $res = $this->where("b5c_sku_id={$skuId} AND third_sku_id='{$thirdSkuId}' AND store_id={$storeId}")
            ->delete();
        
        return $res;
    }

    /**
     * 删除一条SKU绑定关系
     * @param $id
     * @return bool|mixed
     */
    public function deleteById($id){
        $id = intval($id);
        if (empty($id)) return  false;
        return $this->where("id={$id}")->delete();
    }
    
    /**
     * 按条件搜索搜索数据列表。
     * @param $condition
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function search($condition, $start = 0, $limit = 20)
    {
        if (empty($condition))
        {
            return $this->where("1")
                ->limit($start, $limit)
                ->select();
        }
    
        $where = " 1 ";
        if (!empty($condition['gudsName'])){
            $where .= " AND (G.GUDS_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_CNS_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_VICE_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_VICE_CNS_NM LIKE '%{$condition['gudsName']}%' )";
        }
    
        !empty($condition['skuId']) && $where .= " AND R.b5c_sku_id = '{$condition['skuId']}'";
        !empty($condition['storeName']) && $where .= " AND S.STORE_NAME LIKE '%{$condition['storeName']}%'";
        !empty($condition['platform']) && $where .= " AND C.CD LIKE 'N00083%' AND C.CD_VAL LIKE '%{$condition['platform']}%'";
        !empty($condition['thirdSku']) && $where .= " AND R.third_sku_id = '{$condition['thirdSku']}'";
        //添加语言筛选，默认中文，如果没有对应的SPU记录，商品名将是 空值
        $lang = !empty($condition['lang']) ? $condition['lang'] : 'N000920100';
        $langFilter = " AND G.LANGUAGE='{$lang}'";
        
        $sql = "SELECT
                R.`id`,
                R.`b5c_sku_id` AS skuId,
                R.`third_sku_id` AS thirdSkuId,
                G.GUDS_NM AS gudsName,
                C.`CD_VAL` AS platformName,
                R.`store_id` as storeId,
                S.`STORE_NAME` AS storeName,
                R.`plat_cd` AS platformCode,
                R.`create_user` AS creator,
                R.`create_time` AS createTime,
                R.`update_time` AS updateTime
                FROM `tb_ms_sku_relation` AS R
                LEFT JOIN tb_ms_store AS S ON R.store_id = S.ID
                LEFT JOIN tb_ms_cmn_cd AS C ON R.plat_cd = C.CD
                LEFT JOIN tb_ms_guds_opt AS O ON O.GUDS_OPT_ID = R.b5c_sku_id
                LEFT JOIN tb_ms_guds AS G ON G.MAIN_GUDS_ID = O.GUDS_ID {$langFilter}
                WHERE {$where}
                ORDER BY R.id DESC
                LIMIT {$start}, {$limit}";
        $data = $this->query($sql);
        //echo $this->getLastSql();
        return $data;
    }
    
    /**
     * 查询符合条件的数据的数量
     * @param array $condition 查询条件 skuId,thirdSku,storeName,platform,gudsName
     * @return mixed
     */
    public function searchCount($condition)
    {
        if (empty($condition))
        {
            return $this->where("1")->getField('COUNT(*)');
        }
    
        $where = " 1 ";
        if (!empty($condition['gudsName'])){
            $where .= " AND (G.GUDS_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_CNS_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_VICE_NM LIKE '%{$condition['gudsName']}%'
                  OR G.GUDS_VICE_CNS_NM LIKE '%{$condition['gudsName']}%' )";
        }
    
        !empty($condition['skuId']) && $where .= " AND R.b5c_sku_id = '{$condition['skuId']}'";
        !empty($condition['storeName']) && $where .= " AND S.STORE_NAME LIKE '%{$condition['storeName']}%'";
        !empty($condition['platform']) && $where .= " AND C.CD LIKE 'N00083%' AND C.CD_VAL LIKE '%{$condition['platform']}%'";
        !empty($condition['thirdSku']) && $where .= " AND R.third_sku_id = '{$condition['thirdSku']}'";
        //添加语言筛选，默认中文，如果没有对应的SPU记录，商品名将是 空值
        $lang = !empty($condition['lang']) ? $condition['lang'] : 'N000920100';
        $langFilter = " AND G.LANGUAGE='{$lang}'";
    
        $sql = "SELECT COUNT(*) AS total
                FROM `tb_ms_sku_relation` AS R
                LEFT JOIN tb_ms_store AS S ON R.store_id = S.ID
                LEFT JOIN tb_ms_cmn_cd AS C ON R.plat_cd = C.CD
                LEFT JOIN tb_ms_guds_opt AS O ON O.GUDS_OPT_ID = R.b5c_sku_id
                LEFT JOIN tb_ms_guds AS G ON G.MAIN_GUDS_ID = O.GUDS_ID {$langFilter}
                WHERE {$where} ";
        
        $res = $this->query($sql);
        //echo $this->getLastSql();
        $res = array_pop($res);
        return $res['total'];
    }

    /**
     * 通过skuIds获取绑定的数据
     * @param array $params  array [32,321321,321312]
     * @return array | bool
     */
    public function getDataBySkuIds($params,$field="b5c_sku_id,third_sku_id,store_id,plat_cd")
    {
        if(!is_array($params) || empty($params)) {
            return false;
        }
        
        $where['b5c_sku_id'] = array('in',implode(',',$params));
        return $this->field($field)->where($where)->select();
    }
    
    /**
     * 验证SKU是否存在
     * @param $skuId
     * @return mixed
     */
    public function checkSkuId($skuId)
    {
        $sql = "SELECT COUNT(`GUDS_OPT_ID`) AS num FROM tb_ms_guds_opt WHERE GUDS_OPT_ID = '{$skuId}';";
        $skuCount = $this->execute($sql);
        return $skuCount['num'];
    }
    
    /**
     * 读取店铺数据
     * @return false|int|mixed
     */
    public function getStoreList()
    {
        $store = cache('SKU_BIND_STORE');
        if (!empty($store)){
            return $store;
        }
        
        $sql = "SELECT * FROM tb_ms_store;";
        $store = $this->execute($sql);
        $res = $this->cache('SKU_BIND_STORE', $store, 600);
        return $res;
    }

    /**
     * 更新绑定关系
     * @param $data
     * @param $condition
     * @return bool|false|int
     */
    public function updateRelation($data, $condition)
    {
        if (empty($data) || empty($condition)){
            return false;
        }

        if (empty($condition['id'])){
            return false;
        }

        $time = date('Y-m-d H:i:s', time());
        $sql = "UPDATE tb_ms_sku_relation 
                SET third_sku_id = '{$data['third_sku_id']}',
                store_id={$data['store_id']},
                plat_cd='{$data['plat_cd']}',
                update_time = '{$time}'
                WHERE id={$condition['id']};";

        return $this->execute($sql);
    }


    /**
     * 绑定SKU关系后，更新订单数据
     * @return false|int
     */
    public function updateOrders()
    {
        $sql = "UPDATE tb_op_order_guds t1,
                tb_op_order t2,
                tb_ms_sku_relation t3 
                SET
                 t1.B5C_SKU_ID = t3.b5c_sku_id 
                WHERE
                  t1.ORDER_ID = t2.ORDER_ID 
                  AND t2.STORE_ID = t3.store_id 
                  AND t2.PLAT_CD = t3.plat_cd 
                  AND t1.SKU_ID = t3.third_sku_id 
                  AND (t1.B5C_SKU_ID IS NULL OR t1.B5C_SKU_ID = '' );";
        return $this->execute($sql);
    }
    
}