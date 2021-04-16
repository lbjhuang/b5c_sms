<?php
/**
 * 字典模型类，查询各类字典码数据
 * 请不要每一次添加一个方法读取不同的字典数据，那样这个类将会暴涨。
 * 请只需要添加对应的方法名到 下面的注释:
 *  mehtod array 你要定义的方法名 注释，即可直接调用,参数是：CODE码前缀
 *
 * User: afanti
 * Date: 2017/7/25
 * Time: 14:21
 *
 * 
 * @method array getPurchaseOrderStatus($prefix)获取采购订单状态
 * @method array getWarehouse($prfix) 仓库列表
 */
class DictionaryModel extends Model
{
    /**
     * @var string 数据字典表
     */
    protected $trueTableName = "tb_ms_cmn_cd";
    protected $_map = array(
        'code' => 'CD',
        'cdName' => 'CD_NM',
        'platformName' => 'CD_VAL',
        'langCode' => 'ETC',
        'sortNo' => 'SORT_NO'
    );
    
    const ORIGIN_PREFIX = 'N00041';//产地
    const CURRENCY_PREFIX = 'N00059';//币种
    const WAREHOUSE_PREFIX = 'N00068';//仓库
    const PURCHASE_ORDER_STATUS_PREFIX = 'N00132';//采购订单状态
    const GUDS_VALUATION_UNIT_PREFIX = 'N00069';//商品单位
    const GUDS_SALE_CHANNEL_PREFIX = 'N00083';//销售渠道
    const GUDS_BRAND_COUNTRY_PREFIX = 'N00041';//品牌国家
    const GUDS_AUTH_TYPE_PREFIX = 'N00074';//授权方式
    const GUDS_BANK_ACOUNT_PREFIX = 'N00003';//银行账号
    const GUDS_COMPANY_TYPE_PREFIX = 'N00005';//公司类型
    const GUDS_BRAND_STATUS_PREFIX = 'N00004';//品牌状态
    const GUDS_SALE_STATUS_PREFIX = 'N00010';//销售状态
    const GUDS_PRODUCT_TYPE_PREFIX = 'N00118';//商品类型
    const GUDS_PRODUCT_FLAG_PREFIX = 'N00037';//商品Flag
    const GUDS_PRODUCT_DESCRIPTION_PREFIX = 'N00076';//产品简介
    const PLATFORM_PREFIX = 'N00083';//平台CODE 前缀
    const QUESTION_TYPE_PREFIX = 'N00175';//问题类型
    const QUESTION_DEALWITH_STATUS_PREFIX = 'N00176';//反馈问题状态
    const QUESTION_VALIDITY_PREFIX = 'N00177';//反馈问题有效性
    const REFUND_RATE = 'N00085';//返税比率
    const CROSS_BOARD_RATE = 'N00102';//跨境综合税率
    const LOGISTICS_COMPANY = 'N00070';//物流公司
    const EXPRESS_CAT = 'N00071';//物流类目
    const EXPRESS_TYPE = 'N00082';//物流类别
    const PURCHASE_TEAM = 'N00128';//采购团队
    const SALE_TEAM = 'N00129'; //销售团队
    const SALE_MODE = 'N00147';//销售模式
    const WAREHOUSE_DIFF = 'N00146'; //出入库差异
    const PURCHASE_TAX = 'N00134'; //采购税率
    const SHIP_CREDENTIAL = 'N00148'; //发货凭证类型，产地证、备案证，授权证等
    const PAYMENT_DAYS = 'N00142'; //付款天数
    const PAYMENT_PERCENT = 'N00141'; //付款比例
    const PAYMENT_NODE = 'N00139'; //付款节点
    const PAYMENT_DIFF_REASON = 'N00154'; //应付差额原因
    const OUR_COMPANY = 'N00124'; //我方公司
    const WAYBILL_SOURCE = 'N00178';//物流面单来源
    const WAYBILL_TEMPLATE = 'N00179';//物流面单模板
    const COUPON_ONCE_SEND_OBJECT = 'N00185';//优惠券一次发放对象
    const COUPON_CONTINUED_SEND_OBJECT = 'N00186';//优惠券持续发放对象
    const COUPON_TYPE = 'N00187';//优惠券类型
    const SKU_FEATURE = 'N00192';//sku扩展信息，报关和自动派单用。
    const LANGUAGES = 'N00092';//语言配置前缀
    const ACCOUNTING_SUBJECT_TYPE = 'N00289';//会计科目类型
    const ACCOUNTING_SUBJECT_LEVEL = 'N00290';//会计科目级次

    /**
     * 一次多去多个指定前缀的CODE码，有2H缓存
     * @param array $options Type 数组，即：CODE前缀数组。
     * @param bool $filter 是否过滤为K->V
     * @return array|false   以 前缀 为key的数组, 或者 option空的时候返回false。
     */
    public function getDictByType($options, $filter = false)
    {
        if (empty($options)) {
            return false;
        }

        /*$keyFilter = $filter ? 'filter' : 'non';
        if (sizeof($options) > 1){
            $cacheKey = 'Dictionary_'.$keyFilter.md5(implode('',$options));
        } else {//只取一个时复用，getDictionary 的缓存。
            $cacheKey = $cacheKey = 'Dictionary_'.$keyFilter.end($options);
        }

        $data = S($cacheKey);*/
        if (empty($data)) {
            $where = "1 AND ";
            foreach ($options as $prifix) {
                $where .= " CD LIKE '%{$prifix}%' OR";
            }
    
            $where = substr($where, 0, -2);
            if ($filter == true){
                $data = $this->where($where)->order('SORT_NO ASC')->getField('CD,CD_VAL', true);
            } else {
                $data = $this->where($where)->order('SORT_NO ASC')->getField('CD,CD_NM,CD_VAL,ETC,ETC2,FLAG,ETC3,USE_YN,SORT_NO', true);
            }

            // $cacheRes = S($cacheKey, $data, 2 * 3600);
        }
        
        $lastGroup = array();
        foreach ((array)$data as $cd => $item) {
            $prefix = substr($cd, 0, 6);
            $lastGroup[$prefix][$cd] = $item;
        }

        //echo $this->getLastSql();
        return $lastGroup;
    }
    
    /**
     * 根据字典码前缀读取字典数据。
     *
     * @param $prefix
     * @return bool
     */
    public function getDictionary($prefix)
    {
        if (!empty($prefix) && strlen($prefix) == 6) {
            $cacheKey = 'Dictionary_Data_'.$prefix;
            $data = S($cacheKey);
            if (!empty($data)){
                return  $data;
            }
    
            $data = $this->order('SORT_NO ASC')
                ->where(['CD' => ['like', $prefix . '%']])
                ->getField('CD,CD_NM,CD_VAL,ETC,ETC2,FLAG,ETC3', true);
            $cacheRes = S($cacheKey, $data, 2 * 3600);
            return $data;
        } else {
            $this->error = '字典码前缀参数错误';
            return false;
        }
    }

    /**
     * 根据字典码前缀读取字典数据。(列表格式)
     *
     * @param $prefix
     * @return bool
     */
    public function getDictionaryList($prefix)
    {
        if (!empty($prefix) && strlen($prefix) == 6) {
            $cacheKey = 'Dictionary_Data_'.$prefix;
            /*$data = S($cacheKey);
            if (!empty($data)){
                return  $data;
            }*/

            $data = $this->order('SORT_NO ASC')
                ->where(['CD' => ['like', $prefix . '%']])
                ->field('CD,CD_NM,CD_VAL,ETC,ETC2,FLAG,ETC3')->select();
            $cacheRes = S($cacheKey, $data, 2 * 3600);
            return $data;
        } else {
            $this->error = '字典码前缀参数错误';
            return false;
        }
    }
    
    public function getDictionaryByCd($code)
    {
        if (empty($code) || 10 > strlen($code)) {
            return [];
        }
        $cacheKey = 'Dictionary_Data_'.$code;
        $data = S($cacheKey);
        if (!empty($data)){
            return  $data;
        }
    
        $data = $this->order('SORT_NO ASC')
            ->where("CD= '{$code}' ")
            ->getField('CD,CD_NM,CD_VAL,ETC,ETC2,FLAG,ETC3', true);
    
        $cacheRes = S($cacheKey, $data, 2 * 3600);
        return $data;
    }
    
    /**
     * 适配调用不存在的方法时全部转向到读取指定前缀的字典数据。
     * 父类Model的主要默认方法进行支持，getby,getfieldby不支持直接覆盖掉。
     * 
     * @param string $method
     * @param array $args
     * @return bool
     */
    public function __call($method, $args)
    {
        $methodList = array_merge($this->methods, array('count','sum','min','max','avg'));
        if(in_array(strtolower($method),$methodList,true)) {
            return parent::__call($method, $args);
        }
        
        //所有读取字典码的方法统一转到 提取方法，里面添加缓存逻辑。
        return $this->getDictionary($args[0]);
    }
    
    /**
     * 平台数据。
     * @return bool
     */
    public function getPlatform($prefix = null)
    {
        $prefix = empty($prefix) ? self::PLATFORM_PREFIX : $prefix;
        $list = $this->order('SORT_NO ASC')
            ->where(['CD' => ['like', $prefix . '%']])
            ->getField('CD AS code,CD_NM as cdName,CD_VAL as platformName,ETC as langCode', true);
        
        return $list;
    }

    /**
     * 获取币种字典数据
     * @return bool
     */
    public function getCurrency()
    {
        return $this->getDictionary(self::CURRENCY_PREFIX);
    }

    /**
     * 获取产地信息
     * @return bool
     */
    public function getOrigin()
    {
        return $this->getDictionary(self::ORIGIN_PREFIX);
    }

    /**
     * 获取商品单位
     * @param string $key 数组key
     * @param string $val 数组val
     * @return mixed
     */
    public function getGudsUnitVals($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_VALUATION_UNIT_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取销售渠道
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getSaleChannel($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_SALE_CHANNEL_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取品牌国家
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getBrandCountry($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_BRAND_COUNTRY_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取授权类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getAuthType($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_AUTH_TYPE_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取银行账号类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getBankAcount($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_BANK_ACOUNT_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取公司类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getCompanyType($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_COMPANY_TYPE_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取品牌状态类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getBrandStatus($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_BRAND_STATUS_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取销售状态类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getSaleStatus($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_SALE_STATUS_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取商品类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getProductType($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_PRODUCT_TYPE_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 获取商品FLAG
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getProductFlag($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::GUDS_PRODUCT_FLAG_PREFIX);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }

    /**
     * 获取语言标识
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getLanguage($key = 'CD', $val = '')
    {
        $result = $this->getDictionary(self::LANGUAGES);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }

    /**
     * 获取会计科目类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getAccountSubjectType($key = 'CD', $val = '')
    {
        $result = $this->getDictionaryList(self::ACCOUNTING_SUBJECT_TYPE);
        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }

    /**
     * 获取会计科目类型
     * @param string $key
     * @param string $val
     * @return array
     */
    public function getAccountSubjectLevel($key = 'CD', $val = '')
    {
        $result = $this->getDictionaryList(self::ACCOUNTING_SUBJECT_LEVEL);

        return $this->dealWithDateToKeyVal($result, $key = 'CD', $val);
    }
    
    /**
     * 处理字典数据为key-value格式
     * @param $data
     * @return array
     */
    public function dealWithDateToKeyVal($data, $key = 'CD', $valKey = '')
    {
        if (empty($valKey)) {
            return $data;
        }
        $arr = array();
        if (empty($data)) {
            return array();
        }
        foreach ((array)$data as $val) {
            $arr[$val[$key]] = $val[$valKey];
        }
        
        return $arr;
    }
    
}