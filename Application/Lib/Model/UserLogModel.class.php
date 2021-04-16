<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/8/14
 * Time: 10:25
 */
class UserLogModel extends BaseModel
{
    private $_esClient;

    public $total;

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        $this->_esClient = new ESClientModel();
    }

    public function getListData($params)
    {
        $esModel = new EsSearchExtendModel();
        $params ['p'] - 1 < 0 ? $pageIndex = 0 : $pageIndex = $params ['p'] - 1;
        $params ['pageSize'] ? $size = $params ['pageSize'] : $size = 20;
        $esModel
            ->sort(['cTimeStamp' => 'desc'])
            ->where(['ip'       => ['and', $params ['ip']]])
            ->where(['user'     => ['and', $params ['user']]])
            ->where(['noteType' => ['and', $params ['noteType']]])
            ->where(['model'    => ['and', $params ['model']]])
            ->where(['action'   => ['and', $params ['action']]])
            ->where(['source'   => ['and', $params ['source']]])
            ->where(['nodeId'   => ['and', $params ['nodeId']]])
            ->where(['cTimeStamp' => ['range', ['gte' => strtotime($params ['startTime']) ? (string)strtotime($params ['startTime'] . ' 00:00:00'): '', 'lte' => strtotime($params ['endTime']) ? (string)strtotime($params ['endTime'] . ' 23:59:59'): '']]]);
        //->where([$params ['search_condition'] => ['like', $params ['search_value']]]);
        $q = $esModel->page($pageIndex, $size)->getQuery();
        $response = $this->_esClient->search($q);
        $this->total = $response ['hits']['total'];
        $response = $response ['hits']['hits'];

        return $response;
    }
}