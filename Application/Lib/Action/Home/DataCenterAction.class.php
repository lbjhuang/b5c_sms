<?php

class DataCenterAction extends BaseAction
{
    protected $service;
    public function erp_opertion()
    {
        $this->display();
    }

    public function erp_opertion_more()
    {
        $this->display();
    }
    
    public function abacusshow()
    {
        $this->display();
    }

    public function abacus_keyword_research()
    {
        $this->display();
    }

    public function abacus_market_research()
    {
        $this->display();
    }

    public function divideResult()
    {
        $this->display();
    }

    public function gp_channel_transfer()
    {
        $this->display();
    }

    public function pv_monitor()
    {
        $this->display();
    }

    public function odm_goods()
    {
        $this->display();
    }

    public function gp_user_analysis()
    {
        $this->display();
    }

    public function marketshow()
    {
        $this->display();
    }

    public function getMarketData()
    {
        $access_token = $this->getAccessTokenForMt();
        $url = "http://mtapi.gshopper.com/api/products/hotness?page=1&access_token=" . $access_token;
        $res = DataModel::$success_return;
        $res['data'] = json_decode(curl_get_json_get($url), true);
        $this->ajaxReturn($res);
    }

    public function products()
    {
        header('Content-Type:text/json;charset=utf-8');
        set_time_limit(10);
        $this->service = new DataCenterService();
        $request_data = $_POST;
        $res = $this->service->products($request_data);
        echo json_encode($res);
    }

    public function getMonthlyHotness()
    {
        set_time_limit(10);
        $this->service = new DataCenterService();
        $request_data = $_GET;
        $res['data'] = $this->service->getMonthlyHotness($request_data);
        $res['msg'] = 'success';
        $res['code'] = 200;
        echo json_encode($res);
    }

    public function platforms()
    {
        header('Content-Type:text/json;charset=utf-8');
        $data = [
            "data" => [
                [
                    "id" => 1,
                    "platform" => "Amazon",
                    "createdAt" => "2019-04-29T15:29:55.000+0000",
                    "country_codes" => [
                        "AU",
                        "JP",
                        "IT",
                        "US",
                        "DE",
                        "IN",
                        "BR",
                        "CA",
                        "GB",
                        "ES",
                        "FR",
                    ]
                ],
                [
                    "id" => 2,
                    "platform" => "eBay",
                    "createdAt" => "2019-06-05T09:56:50.000+0000",
                    "country_codes" => [
                        "DE",
                        "FR",
                        "US",
                    ]
                ],
                [
                    "id" => 13,
                    "platform" => "Cdiscount",
                    "createdAt" => "2019-11-11T14:28:17.000+0000",
                    "country_codes" => [
                        "FR",
                    ]

                ],
            ],
        ];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function categories()
    {
        header('Content-Type:text/json;charset=utf-8');
        $data = [
            "list" => [
                [
                    "category" => "Collectibles",
                    "category_id" => 3,
                ],
                [
                    "category" => "Sports & Outdoors",
                    "category_id" => 1089,
                ],
            ],
            "totalPage" => 0,
            "page" => 0,
        ];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getMarketAccessToken()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://mtapi.gshopper.com/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=password&password=izene123&username=test%40gshopper.com",
            CURLOPT_HTTPHEADER => [
                "authorization: Basic Y2xpZW50OnBAc3N3b3Jk",
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
                "postman-token: 0df6e617-4408-10da-81c2-cb77bbb1cfe4",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    /**
     * @return mixed
     */
    private function getAccessTokenForMt()
    {
        $access_token_info = $this->getMarketAccessToken();
        $at_info = json_decode($access_token_info, true);
        $access_token = $at_info['access_token'];
        return $access_token;
    }
}