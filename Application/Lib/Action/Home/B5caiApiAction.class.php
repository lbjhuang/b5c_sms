<?php
/**
 * User: yangsu
 * Date: 2019/11/08
 * Time: 13:07
 */

class B5caiApiAction extends BaseAction
{
    private $free_url = [
        'boxinglist/create.json',
        'boxing/create.json',
        'boxing/page.json',
        'boxinglist/page.json',
        'boxinglist/update.json'
    ];

    /**
     *
     */
    public function api()
    {
        try {
            $goto_url = IS_POST ? htmlspecialchars($_GET['goto_url']) : I('goto_url');
            if (empty($goto_url)) {
                throw new Exception('请求接口为空', 400);
            }
            if (!in_array($goto_url, $this->free_url)) {
                throw new Exception('接口验证未通过，不在可请求范围', 500);
            }
            $request_data = DataModel::getDataNoBlankToArr();
            $url = HOST_URL . $goto_url;
            if (!empty($request_data)) {
                $res = ApiModel::postRequestJson($url, json_encode($request_data));
            } else {
                $res = ApiModel::getRequest($url);
            }
        } catch (\Exception $exception) {
            $res = $this->return_error;
            $res['code'] = $exception->getCode() ? $exception->getCode() : 400;
            $res['msg'] = $exception->getMessage();
            $res = json_encode($res, JSON_UNESCAPED_UNICODE);
        }
        header("Content-Type:text/json; charset=utf-8");
        exit($res);
    }
}
