<?php

class TbMsGudsOptModel extends BaseModel
{
    protected $trueTableName = 'tb_ms_guds_opt';

    const PREFIX_ATTR = 'PROCESS_TB_MS_GUDS_OPT_';
    const STANDARD_CONF = '标配';
    const STANDARD_CODE = 8000;
    const STANDARD_CODE_E = 800000;

    protected $_link = [
        'guds' => [
            'mapping_type' => BELONGS_TO,
            'class_name'   => 'Guds',
            'foreign_key'  => 'GUDS_ID',
        ]
    ];

    public $gudOptProperty;

    /**
     * 商品属性获取
     * @param string|array $skuId 商品sku
     * @return string|array 返回商品属性
     */
    public function gudOptProperty($skuId)
    {
        $r = null;
        if (is_string($skuId)) {
            $gudProperty = RedisModel::get_key(static::PREFIX_ATTR . $skuId);
            if ($gudProperty) {
                return [$skuId => json_decode($gudProperty, ture) ['optValNmAndCnsNm']];
            }
        } elseif (is_array($skuId)) {
            foreach ($skuId as $key => $sku) {
                if (isset($r [$sku]))
                    continue;
                $gudProperty = RedisModel::get_key(static::PREFIX_ATTR . $sku);
                if ($gudProperty) {
                    $r [$sku] = json_decode($gudProperty, ture) ['optValNmAndCnsNm'];
                }
            }
            return $r;
        }
    }

    /**
     * 如果redis没有查询到进行数据库查询并进行静态缓存
     * @param string|array $skuId 商品sku
     * @return string|array 返回商品属性
     */
    public function searchGudProperty($skuId)
    {
        $skuId = (array)$skuId;
        $model = new Model();
        if (isset($this->gudOptProperty [$skuId])) {
            return [$skuId => $this->gudOptProperty [$skuId]];
        } else {
            $gudOpt = $this->where(['GUDS_OPT_ID' => ['in', (array)$skuId]])->getField('GUDS_OPT_ID as sku, GUDS_OPT_VAL_MPNG as mpng');
            if ($gudOpt) {
                array_walk_recursive($gudOpt, function(&$opt, $sku) use ($model) {
                    if (strpos($opt, ';') !== false) {
                        list($fOpt, $lOpt) = explode(';', $opt);
                        $ret = $model->table('tb_ms_opt_val')->where(['OPT_ID' => ['eq', explode(':', $fOpt) [0]], 'OPT_VAL_ID' => ['eq', explode(':', $fOpt) [1]]])->find();
                        strtolower($ret ['OPT_VAL_NM']) == 'none'?$opt = L('标配'):$opt .= $ret ['OPT_VAL_NM'];
                        strtolower($ret ['OPT_VAL_CNS_NM']) == 'none'?$opt .= ':' . L('标配'):$opt .= ':' . $ret ['OPT_VAL_CNS_NM'];
                        $ret = $model->table('tb_ms_opt_val')->where(['OPT_ID' => ['eq', explode(':', $lOpt) [0]], 'OPT_VAL_ID' => ['eq', explode(':', $lOpt) [1]]])->find();
                        strtolower($ret ['OPT_VAL_NM']) == 'none'?$opt .= ';' . L('标配'):$opt .= ';' . $ret ['OPT_VAL_NM'];
                        strtolower($ret ['OPT_VAL_CNS_NM']) == 'none'?$opt .= ':' . L('标配'):$opt .= ':' . $ret ['OPT_VAL_CNS_NM'];
                    } else {
                        $ret = $model->table('tb_ms_opt_val')->where(['OPT_ID' => ['eq', explode(':', $opt) [0]], 'OPT_VAL_ID' => ['eq', explode(':', $opt) [1]]])->find();
                        strtolower($ret ['OPT_VAL_NM']) == 'none'?$opt = L('标配'):$opt = $ret ['OPT_VAL_NM'];
                        strtolower($ret ['OPT_VAL_CNS_NM']) == 'none'?$opt .= ':' . L('标配'):$opt .= ':' . $ret ['OPT_VAL_CNS_NM'];
                    }
                });
                $this->gudOptProperty = array_merge($this->gudOptProperty, $gudOpt);
                foreach ($skuId as $k => $v) {
                    $r [$v] = $this->gudOptProperty($v);
                }

                return $r;
            } else {
                return null;
            }
        }
    }
}