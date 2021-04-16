<?php
/**
 * 店铺模型
 * User: afanti
 * Date: 2017/9/4
 * Time: 16:14
 */
class StoreModel extends Model{
    protected $trueTableName = "tb_ms_store";
    
    protected $schemaTrans = "
        ID AS id,
        STORE_NAME as name,
        STORE_PWD as password,
        MERCHANT_ID as merchantId,
        APPKES as authorize,
        PLAT_CD as platform,
        BEAN_CD as brand,
        SITE_CD as site,
        USER_ID as userId,
        STATUS_CD as status,
        AUTH_TIME as authTime,
        CREATE_USER_ID as creator,
        CREATE_TIME as createTime,
        UPDATE_USER_ID as updater,
        UPDATE_TIME as updateTime,
        LAST_TIME_POINT lastCrawTime,
        PLAT_NAME as platName,
        PROXY as proxy,
        DELETE_FLAG as isDelete,
        USER_NAME as userName,
        USER_PWD as userPassword,
        SALE_TEAM_CD as saleTeam
    ";
    
    /**
     * 根据平台CODE 读取店铺列表
     * @param string $platform
     * @param null $fields
     * @return bool|mixed
     */
    public function getStoreByPlatform($platform, $fields = null){
        if (empty($platform)){
            return false;
        }
        
        $fields = !empty($fields) ? $fields : $this->schemaTrans;
        $data = $this->where("PLAT_CD = '{$platform}'")
            ->getField($fields);
        //echo $this->getLastSql();
        return $data;
    }
    
    /**
     * 根据id读取店铺信息
     * @param $id
     * @return bool|mixed
     */
    public function getStoreById($id)
    {
        if (empty($id)){
            return false;
        }

        $res = $this->search(['ID'=>$id]);
        return !empty($res) && is_array($res) ? array_pop($res) : $res;
    }
    
    /**
     * 按照指定条件搜索店铺
     * @param array $condition
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function search($condition, $start = 0, $limit = 20)
    {
        $where = "1";
        $cacheKey = 'STORE_LIST_' . md5(var_export($condition,true));
        $dataArr = cache($cacheKey);
        if (!empty($dataArr)){
            return $dataArr;
        }
    
        !empty($condition['ID']) && $where .= " AND ID ={$condition['ID']}";
        !empty($condition['STORE_NAME']) && $where .= " AND STORE_NAME LIKE '%{$condition['STORE_NAME']}%'";
        
        $storeList = $this->where($where)->field($this->schemaTrans)->limit($start, $limit)->select();
        $dataArr = [];
        if (!empty($storeList)){
            foreach ($storeList as $key => $store){
                $dataArr[$store['id']] = $store;
            }
            cache($cacheKey, $dataArr, 3600);
        }
        
        return $dataArr;
    }

    // 获取店铺的id和名称对应关系
    public function getStoreKeyValue()
    {
        if (cache('STORE_ID_NAME_KEY_VALUE')) {
            return cache('STORE_ID_NAME_KEY_VALUE');
        }
        $dataArr = $this->getField('STORE_NAME,ID');
        cache('STORE_ID_NAME_KEY_VALUE', $dataArr, 3600);
        return $dataArr;
    }
}