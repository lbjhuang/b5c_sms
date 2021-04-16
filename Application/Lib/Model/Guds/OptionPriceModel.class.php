<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/9/21
 * Time: 16:03
 */
class OptionPriceModel extends Model
{
    protected $trueTableName = "tb_ms_guds_opt_price";
    protected $redisCluster;
    
    protected $_map = array(
        'id' => 'ID',
        'mainGudsId' => 'MAIN_GUDS_ID',
        'optionId' => 'GUDS_OPT_ID',
        'warehouse' => 'WAREHOUSE_CODE',
        'purchasePrice' => 'PURCHASE_PRICE',
        'marketPrice' => 'MARKET_PRICE',
        'grossProfitMargin' => 'GROSS_PROFIT_MARGIN',
        'realPrice' => 'REAL_PRICE',
        'createTime' => 'CREATE_TIME',
        'updateTime' => 'update_time'
    );
    
    // 回调方法 初始化模型
    protected function _initialize() {
        $options = ['cluster' =>'redis'];
        $servers = C('REDIS_SERVER');
        $this->redisCluster = new Predis\Client($servers,$options);
    }
    
    /**
     * 根据MainGudsId检查是否存在价格信息
     * 因为SKU价格表是新扩展的，所有旧商品丢没有，所以更新时要验证，没有的要插入。
     *
     * @param number $mainGudsId 商品主ID
     * @return mixed
     */
    public function getPriceByMainGudsId($mainGudsId)
    {
        $data = $this->where("MAIN_GUDS_ID = '{$mainGudsId}'")->select();
        return empty($data) ? array() : $data;
    }
    
    /**
     * 读取单个SKU的价格列表
     * @param number $mainGudsId 商品主ID
     * @param number $optionId SKU ID
     * @return array|mixed
     */
    public function getPriceByOptionId($mainGudsId, $optionId)
    {
        $data = $this->where("MAIN_GUDS_ID = '{$mainGudsId}' AND GUDS_OPT_ID='{$optionId}'")->select();
        return empty($data) ? array() : $data;
    }
    
    /**
     * 保存商品SKU价格列表
     * //SKU价格信息，同一个SKU在不同的仓库有不同的价格。
     * @param array $data 价格数据
     * @return array|false|int
     */
    public function saveSkuPrice($data){
        
        if (empty($data)) return array();
        
        $values = "";
        $timeNow = date('Y-m-d H:i:s', time());
        foreach ($data['priceGroup'] as $Key => $price){
            empty($price['grossProfitMargin']) && $price['grossProfitMargin'] = '0.00';
            $values .= "({$data['mainGudsId']},{$price['optionId']},'{$price['warehouse']}',";
            $values .= "'{$price['purchasePrice']}','{$price['marketPrice']}','{$price['grossProfitMargin']}',";
            $values .= "'{$price['realPrice']}','{$timeNow}','{$timeNow}'),";
        }
        
        $values = rtrim($values, ',');
        $sql = "INSERT INTO tb_ms_guds_opt_price
                (
                `MAIN_GUDS_ID`,
                `GUDS_OPT_ID`,
                `WAREHOUSE_CODE`,
                `PURCHASE_PRICE`,
                `MARKET_PRICE`,
                `GROSS_PROFIT_MARGIN`,
                `REAL_PRICE`,
                `CREATE_TIME`,
                `update_time`
                )
                VALUES {$values}; ";
        //echo $sql;
        $res = $this->execute($sql);
        $this->cachePrice($data['mainGudsId']);
        return $res;
    }
    
    /**
     * 更新商品SKU价格列表
     * SKU价格信息，同一个SKU在不同的仓库有不同的价格。
     * @param array $price 价格数据
     * @param number $mainGudsId MainGudsId
     * @return bool
     */
    public function updatePrice($price, $mainGudsId)
    {
        if (empty($price)){
            return false;
        }
        
        //更新价格列表
        !empty($price['warehouse']) && $data['WAREHOUSE_CODE'] = $price['warehouse'];
        !empty($price['purchasePrice']) && $data['PURCHASE_PRICE'] = $price['purchasePrice'];
        !empty($price['marketPrice']) && $data['MARKET_PRICE'] = $price['marketPrice'];
        !empty($price['realPrice']) && $data['REAL_PRICE'] = $price['realPrice'];
        $price['grossProfitMargin'] = number_format($price['grossProfitMargin'],2);
        isset($price['grossProfitMargin']) && $data['GROSS_PROFIT_MARGIN'] = $price['grossProfitMargin'];
        $data['update_time'] = date('Y-m-d H:i:s', time());
        
        //没有价格ID主键的话，采用 唯一索引条件 进行更新
        if (empty($price['id'])) {
            $where = "`MAIN_GUDS_ID`='{$mainGudsId}' AND `GUDS_OPT_ID`='{$price['optionId']}'";
            $where .= " AND `WAREHOUSE_CODE`='{{$price['warehouse']}}'";
        } else {
            $where = "ID={$price['id']}";
        }
    
        $res = $this->where($where)->save($data);
        $res && $this->cachePrice($mainGudsId);
        //echo $this->getLastSql();
        return $res == 0 ? true : $res;//更新行数为0时，任务是正确的，要么没有这个数据要么是用同样的数据更新。
    }
    
    /**
     * 缓存价格
     * @param $mainGudsId
     * @return bool 
     */
    public function cachePrice($mainGudsId)
    {
        //更新缓存价格,
        $cacheUpdate = [];
        $priceList = $this->where("MAIN_GUDS_ID={$mainGudsId}")->select();
        foreach ($priceList as $item) {
            $cachePrice['id'] = $item['ID'];
            $cachePrice['mainGudsId'] = $item['MAIN_GUDS_ID'];
            $cachePrice['gudsOptId'] = $item['GUDS_OPT_ID'];
            $cachePrice['warehouseCode'] = $item['WAREHOUSE_CODE'];
            $cachePrice['purchasePrice'] = $item['PURCHASE_PRICE'];
            $cachePrice['marketPrice'] = $item['MARKET_PRICE'];
            $cachePrice['grossProfitMargin'] = $item['GROSS_PROFIT_MARGIN'];
            $cachePrice['realPrice'] = $item['REAL_PRICE'];
            //单个价格缓存，Java太不靠谱啊，缓存使用方式不对，命中率会很低。
            $itemKey = 'search_product_GUDS_OPT_PRICE-'.$item['GUDS_OPT_ID'] . ':' .$item['WAREHOUSE_CODE'];
            $this->redisCluster->set( $itemKey, json_encode($cachePrice));
            $cacheUpdate[] = $cachePrice;
        }
        $cacheData = json_encode($cacheUpdate);
        $res = $this->redisCluster->set('search_product_GUDS_OPT_PRICE-'.$mainGudsId, $cacheData);
        return $res;
    }
    
    
    /**
     * 删除价格信息
     * @param array $condition 删除条件,必须有[id]
     * @return bool|mixed
     */
    public function deletePrice($condition)
    {
        if (empty($condition['id']) || empty($condition['mainGudsId'])){
            return false;
        }
        
        $res = $this->where(" ID IN ({$condition['id']})")->delete();
        $res && $this->cachePrice($condition['mainGudsId']);
        return $res;
    }
    
}