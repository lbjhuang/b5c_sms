<?php
/**
 * 
 * User: 
 * Date: 
 * Time: 
 */

class MsgAapi extends Action {

    public function index(){
        return 'test';
    }

    /**
     *  对接 /mesage/appPushRes.json
     *  [http://wiki.b5msoft.com/index.php/Sms2-Page:/m/api/a/pushmsg]
     */
    public function apppushresult(){
        $debug_log = file_get_contents('php://input');
        Log::write(
            '[debug api - apppushresult]'.str_replace("\\/", "/", $debug_log)
        );
        $example = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'pushs' => array(
                    array('msgId'=>'1','custId'=>'123',),
                    array('msgId'=>'1','custId'=>'124',),
                ),
                'fail_pushs' => array(
                    array('msgId'=>'2','custId'=>'1023',),
                    array('msgId'=>'2','custId'=>'1024',),
                )
            ),
        );
        // Response | 返回参数说明
        // 名称  是否必须    类型  描述
        // code    not null    String  status code
        // msg     not null    string  message tips
        // data    can be null     string  data which response form b5c 
        $ret = array();
        $ret['code'] = 0;
        $ret['msg'] = '';
        $ret['data'] = null;

        $code = Mainfunc::chooseParam('code');
        $msg = Mainfunc::chooseParam('msg');
        $data = Mainfunc::chooseParam('data');
        $pushs = isset($data['pushs'])?$data['pushs']:null;
        $fail_pushs = isset($data['fail_pushs'])?$data['fail_pushs']:null;

        $is_empty = 0;
        if($code!=2000){
            $is_empty = 1;
        }
        if(!is_array($pushs)){
            $is_empty = 1;
        }
        if(!is_array($fail_pushs)){
            $is_empty = 1;
        }
        if($is_empty){
            $ret['code'] = 50000001;
            $ret['msg'] = 'Wrong request data.'.' '.'Example : '.json_encode($example);
            $debug_log = json_encode($ret);
            Log::write(
                '[debug api - apppushresult outputs]'.str_replace("\\/", "/", $debug_log)
            );
            return ZWebHttp::CallbackBegin(1).
                json_encode($ret).
                ZWebHttp::CallbackEnd(1);
        }

        foreach($fail_pushs as $key=>$val){
            $status = D('ZZmsmessagepushres')->addPushResUser($val['msgId'],$val['custId'],1);
        }
        foreach($pushs as $key=>$val){
            $status = D('ZZmsmessagepushres')->addPushResUser($val['msgId'],$val['custId'],0);
        }

        $ret['code'] = 2000;
        $ret['msg'] = 'success';
        $ret['data'] = $data;
        $debug_log = json_encode($ret);
        Log::write(
            '[debug api - apppushresult outputs]'.str_replace("\\/", "/", $debug_log)
        );
        return ZWebHttp::CallbackBegin(1).
                json_encode($ret).
                ZWebHttp::CallbackEnd(1);
    }

}

