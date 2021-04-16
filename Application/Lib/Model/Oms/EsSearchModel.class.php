<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/6
 * Time: 15:19
 * example
 * $q = $esModel
    ->sort(['test' => 'desc'])
    ->setDefault(['and', ['msOrd', 'msOrd.ordId']])
    ->setDefaultNotNull(['not', [field, field]])
    ->page()
    ->where(['ordId' => ['and', ['gspp510299663841', 'gspt510644281596']]])
    ->where(['a' => ['and', ['ddd', 'eee']]])
    ->where(['c' => ['or', ['aa', 'bb']]])
    ->where(['d' => ['range', ['gte' => 'xxx', 'lte' => '']]])
    ->getQuery();
 */
class EsSearchModel
{
    /**
     * @var string 索引
     */
    public $index = 'es_order';

    /**
     * @var string 类型
     */
    public $type = 'es_order';

    /**
     * @var 匹配模式
     */
    public $pattern = [
        'and'  => 'must',
        'or'   => 'should',
        'not'  => 'must_not',
        'range'=> 'filter',
        'like' => 'must'
    ];

    /**
     * @var 过滤器模式
     */
    public $filter = 'bool';

    /**
     * @var null 消息体
     */
    public $body = [];

    /**
     * @var int 每页显示条数
     */
    public $size = 20;

    /**
     * @var int 从第几条开始，用于分页
     */
    public $fromSize = 0;

    /**
     * @var string 索引后缀
     */
    private $subFix = '.keyword';

    /**
     * @var 匹配条件
     */
    public $query = [];

    public function __construct($index = 'es_order', $type = 'es_order')
    {
        $this->index = $index;
        $this->type = $type;
        if ($type == 'income') {
            $this->subFix = '';
        }
    }

    /**
     * [field => [pattern => []]] 数组
     * [field => [pattern => 'test']] 字符串
     * [field => [pattern => ['gt' => , 'lt' => ]]] 范围
     * @param array|string $param 搜索参数，支持链式调用
     * @param string $is_keyword 是否是关键词查询
     * @throws EsSearchException
     * @return $this
     */
    public function where($param, $is_keyword = true)
    {
        if (!empty($param)) {
            if (is_string($param))
                throw new EsSearchException('the params must be array');
            $this->filterParse($param, $is_keyword);

            return $this;
        }
    }

    /**
     * 参数过滤，分析
     * @param $param
     * @param $is_keyword 是否关键词查询
     * @throws EsSearchException
     * @return $this
     */
    private function filterParse($param, $is_keyword)
    {
        $field = array_keys($param) [0];
        list($pattern, $value) = $param [$field];
        if (!is_null($pattern) and ($value != "" and count($value) > 0)) {
            if (array_key_exists($pattern, $this->pattern) === false) {
                throw new EsSearchException('pattern not match in (and, or, not, range, like)');
            }
            if ($pattern == 'like') {
                $this->query ['query'][$this->filter]['must'][] = ['wildcard' => [$field.$this->subFix => '*'.$value.'*']];
            } else {
                if (is_array($value) and $pattern == 'range') {
                    $rangeValue = array_filter($value, function($v) {
                        return ($v);
                    });
                    if ($rangeValue) {
                        $this->query ['query'][$this->filter][$this->pattern [$pattern]][$pattern][$field] = $rangeValue;
                    }
                } elseif (is_array($value) and $pattern != 'range' and $is_keyword) {
                    $this->query ['query'][$this->filter][$this->pattern [$pattern]][] = ['terms' => [$field.$this->subFix => $value]];
                } elseif (is_array($value) and $pattern != 'range' and !$is_keyword) {
                    $this->query ['query'][$this->filter][$this->pattern [$pattern]][] = ['terms' => [$field => $value]];
                } elseif (!is_array($value)) {
                    $this->query ['query'][$this->filter][$this->pattern [$pattern]][] = ['match' => [$field => $value]];
                }
            }
        }
    }

    /**
     * 分页参数
     * @param int $fromSize
     * @param int $size
     * @return $this
     */
    public function page($fromSize = 0, $size = 0)
    {
        empty($fromSize) or $this->fromSize = $fromSize;
        empty($size)     or $this->size     = $size;
        $this->body ['from'] = ($this->fromSize - 1 < 0)?0:($this->fromSize * $this->size);
        $this->body ['size'] = $this->size;
        return $this;
    }

    /**
     * 排序
     * @param $params
     * @return $this
     * @throws EsSearchException
     */
    public function sort($params)
    {
        if (!is_array($params)) {
            throw new EsSearchException('sort must be array ["sortField" => "desc|asc"]');
        }
        if ($params) {
            foreach ($params as $key => $value) {
                if (!empty($key)) {
                    $this->body ['sort'][] = [$key => $value];
                }
            }
        }
        return $this;
    }

    /**
     * 设置默认值
     * ['and', [field, field]]
     */
    public function setDefault($params)
    {
        if (empty($params)) {
            return $this;
        }
        list($pattern, $value) = $params;
        if (empty($value)) {
            throw new EsSearchException('default value can not be null');
        }
        if (array_key_exists($pattern, $this->pattern)) {
            foreach ($value as $k => $v) {
                if (!empty($v)) {
                    $this->query ['query'][$this->filter][$this->pattern [$pattern]][] = ['exists' => ['field' => $v]];
                }
            }
        } else {
            throw new EsSearchException('pattern not match in (and, or, not, range)');
        }
        return $this;
    }

    /**
     * 设置不能为空
     * ['not', [field, field]]
     */
    public function setDefaultNotNull($params)
    {
        if (empty($params)) {
            return $this;
        }
        list($pattern, $value) = $params;
        if (empty($value)) {
            throw new EsSearchException('default value can not be null');
        }
        if (array_key_exists($pattern, $this->pattern)) {
            foreach ($value as $k => $v) {
                if (!empty($v)) {
                    $this->query ['query'][$this->filter][$this->pattern [$pattern]][] = ['term' => [$v . $this->subFix => ""]];
                }
            }
        } else {
            throw new EsSearchException('pattern not match in (and, or, not, range)');
        }
        return $this;
    }

    /**
     * 设置忽略值
     */
    public function setMissing($params)
    {
        if (empty($params)) {
            return $this;
        }
        list($pattern, $value) = $params;
        if (empty($value)) {
            throw new EsSearchException('default value can not be null');
        }
        if (array_key_exists($pattern, $this->pattern)) {
            foreach ($value as $k => $v) {
                if (!empty($v)) {
                    $this->query ['query'][$this->filter]['must_not'][] = ['exists' => ['field' => $v]];
                }
            }
        } else {
            throw new EsSearchException('pattern not match in (and, or, not, range)');
        }
        return $this;
    }

    /**
     * 获取查询条件
     * @return array|匹配条件
     */
    public function getQuery()
    {
        $request ['index'] = $this->index;
        $request ['type']  = $this->type;
        $this->query = array_merge($this->body, $this->query);
        $request ['body']  = $this->query;

        return $request;
    }

    /**
     * 最少匹配一项should中条件子句
     * @return $this
     */
    public function minimum(){
        $this->query ['query'][$this->filter]['minimum_should_match'] = 1;
        return $this;
    }
}

class EsSearchException extends Exception
{
}