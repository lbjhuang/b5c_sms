<?php

/**
 * User: yangsu
 * Date: 17/12/20
 * Time: 12:11
 */
class HttpTool
{

    public static function Curl_post_json($url, $data, $connecttimeout = 30, $timeout = 30)
    {
        $ch = curl_init();
        $header = array(
            "Accept: application/json",
            "Content-Type: application/json"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $PostData = $data;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
        curl_close($ch);
        return $temp;
    }
    public static function Curl_post_json_header($url, $data, $header = [],$connecttimeout = 30, $timeout = 30)
    {
        $ch = curl_init();
        // $tmpHeader = array(
        //     "Accept: application/json",
        //     "Content-Type: application/json"
        // );

        // $header = array_merge($tmpHeader,$header);
      
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $PostData = $data;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
       
        curl_close($ch);
        return $temp;
    }
    public static function curlReq($url, $data, $connecttimeout = 30, $timeout = 30)
    {
        $ch = curl_init();
        $PostData = $data;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
        curl_close($ch);
        return $temp;
    }
    public static function Curl_post($url, $data, $connecttimeout = 30, $timeout = 30)
    {
        //初使化init方法
        $ch = curl_init();
        //指定URL
        curl_setopt($ch, CURLOPT_URL, $url);
        //设定请求后返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //声明使用POST方式来进行发送
        curl_setopt($ch, CURLOPT_POST, 1);
        //发送什么数据呢
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        //忽略证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);

        $header = array(
            "Accept: application/json",
            "Content-Type: application/json"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        //设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        //发送请求
        $output = curl_exec($ch);

        //关闭curl
        curl_close($ch);

        //返回数据
        return $output;
    }
    public static function curlGet($url, $connecttimeout = 30, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public static function curlXml($url, $data, $connecttimeout = 30, $timeout = 30)
    {
        $ch = curl_init();
        $PostData = $data;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml; charset=utf-8"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
        curl_close($ch);
        return $temp;
    }

    public static function curlForm($url, $post_data)
    {
        //初始化curl
        $ch = curl_init();
        $cookie = array();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36",
            CURLOPT_COOKIESESSION => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_COOKIE => $cookie
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}