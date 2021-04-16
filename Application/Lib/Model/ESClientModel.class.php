<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/1/3
 * Time: 17:00
 */

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\MaxRetriesException;
use Elasticsearch\Common\Exceptions\ClientErrorResponseException;

class ESClientModel
{
    public static $_client;

    /**
     * ESClientModel constructor.
     */
    public function __construct()
    {
        try {
            if (!is_object(self::$_client))
                self::$_client = $this->_setConfigure();
            return self::$_client;
        } catch (ClientErrorResponseException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 配置
     *
     * @return ClientBuilder
     */
    private function _setConfigure()
    {
        $configure = C('elasticsearch_conn_conf');
        $defaultHandler = ClientBuilder::defaultHandler();
        $handler = [
            'handler' => $defaultHandler
        ];
        $configure = array_merge($configure, $handler);

        $clientBuilder = ClientBuilder::fromConfig($configure);

        return $clientBuilder;
    }

    /**
     * 创建索引
     *
     * @return mixed
     */
    public function index()
    {
        $params = [
            'index' => 'test_index',
            'type' => 'test_type',
            'id' => '999001002',
            'body' => ['testField' => 'bac']
        ];

        $response = self::$_client->index($params);
        return $response;
    }

    public function putMapping()
    {
        $params = [
            'index' => 'newproduct',
            'type' => 'newproduct',
            'body' => [

                    "newproduct" => [
                        "properties" => [
                            "currencyType1" => [
                                "type" => "text",

                            ],

                        ]
                    ]

            ]
        ];
        $response = self::$_client->indices()->putMapping($params);
        return $response;
    }

    public function post($params)
    {
        $response = self::$_client->index($params);
        return $response;
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function update($params)
    {
        $response = self::$_client->update($params);
        return $response;

    }

    /**
     * 搜索文档
     *
     * @return response
     */
    public function search($params)
    {
        try {
            $response = self::$_client->search($params);
            return $response;
        } catch (MaxRetriesException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof MaxRetriesException) {
                echo "Max retries!";
            }
        }
    }

    /**
     * 获得文档
     *
     * @return mixed
     */
    public function get()
    {
        $params = [
            'index' => 'test_index',
            'type' => 'test_type',
            'id' => '999001001',
        ];
        $response = self::$_client->get($params);
        return $response;
    }
}