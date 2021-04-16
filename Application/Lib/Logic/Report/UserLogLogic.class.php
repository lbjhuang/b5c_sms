<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Report/ReportBaseLogic.class.php';

class UserLogLogic extends ReportBaseLogic
{
    public function moduleList()
    {
        $nodes = M('node', 'bbm_')->where(['LEVEL' => 1])
            ->field('id,name')
            ->select();
        return $nodes;
    }

    public function actionList($params)
    {
        $nodes = M('node', 'bbm_')->where(['LEVEL' => 2, 'TYPE' => 0, 'PID' => $params['node_id']])
            ->field('id,name')
            ->select();
        return $nodes;
    }
    public function listData($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        empty($params['date']) and $params['date'] = [date('Y-m-d', time() - 86400 * 6), date('Y-m-d')];
        $q = (new EsSearchModel('gs_log', 'gs_log'))
            ->setDefaultNotNull(['not', ['gs_log.user']])
            ->getQuery();
        $must = &$q['body']['query']['bool']['must'];
        $must[] = [
            'range' => [
                'nodeId' => [
                    'gt' => 0
                ]
            ]
        ];
        $must[] = [
            'range' => [
                'cTimeStamp' => [
                    'gt' => strtotime($params['date'][0]),
                    'lt' => strtotime($params['date'][1]) + 86400,
                ]
            ]
        ];
        if (!empty($params['module'])) {
            $page_ids = $this->actionList(['node_id' => $params['module']]);
            $must[] = [
                'terms' => [
                    'nodeId' => array_column($page_ids, 'id')
                ]
            ];
        }
        empty($params['action']) or $must[] = [
            'term' => [
                'nodeId' => $params['action']
            ]
        ];
        empty($params['user']) or $must[] = [
            'term' => [
                'user.keyword' => $params['user']
            ]
        ];
        $q['body']['size'] = 0;
        $q['body']['aggs'] = [
            'times' => [
                "terms" => [
                    'field' => 'nodeId',
                    'size' => 100000
                ],
                'aggs' => [
                    'users_stat' => [
                        'terms' => [
                            'field' => 'user',
                            'size' => 100000
                        ]
                    ]
                ]
            ]
        ];
        $es_data = (new ESClientModel())->search($q)['aggregations']['times']['buckets'];
        isset($params['type']) or $params['type'] = 0;
        $where['TYPE'] = $params['type'];
        $where['LEVEL'] = 2;
        $nodes = M('node', 'bbm_')->where($where)
            ->getField('id,title as module,name as action,type', true);
        foreach ($es_data as $v) {
            if (isset($nodes[$v['key']])) {
                $tmp = $nodes[$v['key']];
                $tmp['times'] = $v['doc_count'];
                $tmp['users'] = array_column($v['users_stat']['buckets'], 'key');
                $tmp['users_stat'] = $v['users_stat']['buckets'];
                $list[] = $tmp;
            }
        }
        $data['total'] = count($list);
        $data['list'] = $list;
        return $data;
    }
}