<?php

/**
 * User: yangsu
 * Date: 19/11/19
 * Time: 14:21
 */

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class GuzzleModel extends Model
{
    public static $config = [
        //'api_url'       => 'http://openapi.winit.com.cn/openapi/service',
        'api_url'       => 'http://openapi.sandbox.winit.com.cn/openapi/service',
        'action'        => 'winit.tools.address.isValid',
        'app_key'       => 'rebecca',
        'token'         => '89435277FA3BA272DE795559998E-',
        'platform'      => 'OWNERERP',
        'client_id'     => 'ODJKMDU1YZCTYJQ5YY00ZWZLLTK5N2QTOWY4MZI5OGMWNDG2',
        'client_secret' => 'ODQ4YWY0ZWETMZLLZC00MJFJLTG3NWUTYTMWNGIYMJFINJFJODK4OTMZNZY1NDA3MJE3MA==',
        'format'        => 'json',
        'language'      => 'zh_CN',
        'sign_method'   => 'md5',
        'version'       => '1.0',
    ];
    public static $global_data = [];

    public static function recommend($data = [], $uuid)
    {
        $client = new Client();
        $uri = HOST_URL_API . '/process/public_process.json';
        $requests = function ($total) use ($data, $uri, $uuid) {
            for ($i = 0; $i < $total; $i++) {
                list($temp_datum,) = PatchModel::filterRecommend($data[$i], $uuid, true);
                yield new Request('POST', $uri, [], json_encode($temp_datum, JSON_UNESCAPED_UNICODE));
            }
        };
        $data_total = count($data);
        $pool = new Pool($client, $requests($data_total), [
            'concurrency' => 5,
            'fulfilled'   => function ($response, $index) {
                // this is delivered each successful response
                $body = $response->getBody();
                $contents = $body->getContents();
                $body_array = json_decode($contents, true);
                if (is_string($body_array['data'])) {
                    $body_array['data'] = json_decode($body_array['data'], true)['data'];
                }
                self::$global_data[$index] = $body_array['data'];
            },
            'rejected'    => function ($reason, $index) {
                // this is delivered each failed request
                Logs([$reason, $index], __FUNCTION__, __CLASS__);
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
        foreach (DataModel::toYield(self::$global_data) as $index_values) {
            foreach ($index_values as $value) {
                $res[] = $value;
            }
        }
        Logs($res, __FUNCTION__, __CLASS__);
        return $res;
    }

    public static function addressVaild($data = [], $uuid)
    {
        $client = new Client();
        $config = self::$config;
        $uri = $config['api_url'];
        $requests = function ($total) use ($data, $uri, $config) {
            $date = date('Y-m-d H:i:s');
            for ($i = 0; $i < $total; $i++) {
                $item = $data[$i];
                $param = [
                    'city'    => htmlspecialchars_decode($item['ADDRESS_USER_CITY'], ENT_QUOTES),
                    "country" => $item['ADDRESS_USER_COUNTRY_EDIT'] ?: (htmlspecialchars_decode($item['ADDRESS_USER_COUNTRY'], ENT_QUOTES) ?: $item['ADDRESS_USER_COUNTRY_CODE']),
                    'houseNo' => $item['doorplate'],
                    'street'  => htmlspecialchars_decode($item['ADDRESS_USER_ADDRESS1'], ENT_QUOTES),
                    'zipcode' => htmlspecialchars_decode($item['ADDRESS_USER_POST_CODE'], ENT_QUOTES),
                ];
                $data = [
                    'city'      => $param['city'],
                    'country'   => $param['country'],
                    'houseNo'   => $param['houseNo'],
                    'street'    => $param['street'],
                    'zipcode'   => $param['zipcode'],
                    'timestamp' => $date,
                ];
                list($userSign, $clientSign) = ApiModel::getSign($data);
                unset($data['timestamp']);
                $request = [
                    'action'      => $config['action'],
                    'app_key'     => $config['app_key'],
                    'client_id'   => $config['client_id'],
                    'client_sign' => $clientSign,
                    'data'        => $data,
                    'format'      => $config['format'],
                    'language'    => $config['language'],
                    'platform'    => $config['platform'],
                    'sign'        => $userSign,
                    'sign_method' => $config['sign_method'],
                    'timestamp'   => $date,
                    'version'     => $config['version'],
                ];
                yield new Request('POST', $uri, [], json_encode($request, JSON_UNESCAPED_UNICODE));
            }
        };
        $data_total = count($data);
        $pool = new Pool($client, $requests($data_total), [
            'concurrency' => 5,
            'fulfilled'   => function ($response, $index) {
                // this is delivered each successful response
                $body = $response->getBody();
                $contents = $body->getContents();
                self::$global_data[$index] = $contents;
            },
            'rejected'    => function ($reason, $index) {
                // this is delivered each failed request
                Logs([$reason, $index], __FUNCTION__, __CLASS__);
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
        foreach (DataModel::toYield(self::$global_data) as $index_values) {
            $res[] = $index_values;
        }
        Logs($res, __FUNCTION__, __CLASS__);
        return $res;
    }

    /**
     * @param       $url
     * @param array $post_data
     * @param null  $access_token
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function post($url, array $post_data, $access_token = null)
    {
        $client = new Client();
        $data['form_params'] = $post_data;
        $response = $client->post($url, $data);
        return $response->getBody();
    }

    public function get($url, $options = [])
    {
        $client = new Client();
        $response = $client->get($url, $options);
        return $response->getBody();
    }

    public static function reOrderApply($data = [], $uuid)
    {
        $client = new Client();
        $uri = ERP_URL . 'index.php?m=crontabHandle&a=reOrderApplySubmit';
        $requests = function ($total) use ($data, $uri, $uuid) {
            for ($i = 0; $i < $total; $i++) {
                sleep(1);
                yield new Request('POST', $uri, [], json_encode($data[$i], JSON_UNESCAPED_UNICODE));
            }
        };
        $data_total = count($data);
        $pool = new Pool($client, $requests($data_total), [
            'concurrency' => 5,
            'fulfilled'   => function ($response, $index) {
                // this is delivered each successful response
                $body = $response->getBody();
                $contents = $body->getContents();
                $body_array = json_decode($contents, true);
                if (is_string($body_array['data'])) {
                    $body_array['data'] = json_decode($body_array['data'], true);
                }
                self::$global_data[$index] = $body_array;
            },
            'rejected'    => function ($reason, $index) {
                // this is delivered each failed request
                Logs([$reason, $index], __FUNCTION__, __CLASS__);
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
        Logs(json_encode(self::$global_data), __FUNCTION__, __CLASS__);
        $where_str = ' ( 1 != 1 ';
        foreach (DataModel::toYield(self::$global_data) as $index_values) {
            if (!empty($index_values['data'])){
                $where_str .= sprintf(" OR (order_id = '%s' AND platform_cd = '%s')", $index_values['data']['order_id'], $index_values['data']['platform_code']);
            }
        }
        $where_str .= ' ) ';
        //10068 GP标记发货的优化补充改动
        $res = M('op_order_return', 'tb_')
            ->where(['status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_WAITING])
            ->where($where_str, null, true)->delete();
        Logs($res, __FUNCTION__, __CLASS__);
        return $res;
    }

    //循环请求api
    public static function reOrderApplyNew($data = [])
    {
        $uri = ERP_URL . 'index.php?m=crontabHandle&a=reOrderApplySubmit';
        $total = count($data);
        for ($i = 0; $i < $total; $i++) {
            sleep(1);
            $data_json = json_encode($data[$i]);
            $res = HttpTool::Curl_post_json($uri, $data_json, 5, 5);
            $body_array = json_decode($res, true);
            self::$global_data[$i] = $body_array;
        }
        Logs(json_encode(self::$global_data), __FUNCTION__, __CLASS__);
        $where_str = ' ( 1 != 1 ';
        foreach (DataModel::toYield(self::$global_data) as $index_values) {
            if (!empty($index_values['data'])){
                $where_str .= sprintf(" OR (order_id = '%s' AND platform_cd = '%s')", $index_values['data']['order_id'], $index_values['data']['platform_code']);
            }
        }
        $where_str .= ' ) ';
        //10068 GP标记发货的优化补充改动
        $res = M('op_order_return', 'tb_')
            ->where(['status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_WAITING])
            ->where($where_str, null, true)->delete();
        Logs($res, __FUNCTION__, __CLASS__);
        return $res;
    }

}