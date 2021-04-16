<?php

/**
 * User: yangsu
 * Date: 18/2/24
 * Time: 11:28
 */
class EsModel extends ESClientModel
{
    private $conf = [
        'index' => 'es_order',
        'type' => 'es_order',
    ];

    /**
     * @param null $query
     * @param null $page
     * @param null $sort
     * @param null $match_phrase
     *
     * @return response
     */
    public function search($query = null, $page = null, $sort = null, $match_phrase = null, $id = null)
    {
        $params = $this->joinParams($query, $page, $sort, $match_phrase, $id);
        $params['search_type'] = 'dfs_query_then_fetch';
        Logs(json_encode($params), '$params', 'es_search');
        //var_dump(json_encode($params));die;
        $response = ESClientModel::search($params);
        return $response;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getIdData($id, $sort = null)
    {
        $params = $this->joinParams(null, null, $sort, null, $id);
        Logs(json_encode($params), '$params', 'es_search');
        $response = ESClientModel::$_client->get($params);
        return $response;
    }

    /**
     * @param      $query
     * @param      $page
     * @param      $sort
     * @param null $match_type
     * @param null $id
     *
     * @return array
     */
    private function joinParams($query, $page, $sort = null, $match_type = null, $id = null)
    {
        $res = [
            'index' => $this->conf['index'],
            'type' => $this->conf['type'],
        ];
        if ($this->conf['search_type']) {
            $res['search_type'] = $this->conf['search_type'];
        }
        if ($query) {
            switch ($match_type) {
                case 'match':
                    $res['body']['query']['match'] = $query;
                    break;
                case 'match_phrase':
                    $res['body']['query']['match_phrase'] = $query;
                    break;
                case 'bool':
                    $res['body']['query']['bool'] = $query;
                    break;
                default:
                    $res['body']['query'] = $query;
            }
        }
        if ($page) {
            $res['size'] = $page['size'];
            $res['from'] = $page['from'];
        }
        if ($sort) {
            $res['body']['sort'] = $sort;
        }
        if ($id) {
            $res['id'] = $id;
        }
        return $res;
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    private function joinBody($query)
    {
        return $query;
    }

    /**
     * @param        $id
     * @param        $body
     * @param string $index
     * @param string $type
     *
     * @return mixed
     */
    public function updates($id, $body, $index = 'es_order', $type = 'es_order')
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => [
                'doc' => $body
            ]
        ];
        $response = ESClientModel::$_client->update($params);
        return $response;
    }

    public function getDataAggregation($query = null,$aggregate_field, $aggregate_method = 'sum')
    {
        $params = $this->joinParams($query, null, null, null, null);
        $aggregate_field_index = $aggregate_method . '_'. $aggregate_field;
        $params['body']["aggs"] = [
            $aggregate_field_index => [
                $aggregate_method => [
                    'field' => $aggregate_field
                ]
            ]
        ];
        $params['search_type'] = 'dfs_query_then_fetch';
        $params['size'] = 0;
        return  ESClientModel::$_client->search($params);
    }
}
