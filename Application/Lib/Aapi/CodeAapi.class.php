<?php
/**
 * 
 * User: 
 * Date: 
 * Time: 
 */

class CodeAapi extends Action {

    public function index(){
        return 'test';
    }

    /**
     *  
     *
     */
    public function codeall(){
        // Response | 返回参数说明
        $ret = array();
        $ret['code'] = 0;
        $ret['msg'] = '';
        $ret['data'] = null;

        $ret['data'] = D('ZZmscmncd')->indexCode();

        return ZWebHttp::CallbackBegin(1).
                json_encode($ret).
                ZWebHttp::CallbackEnd(1);
    }

    /**
     *  find one area by area_id
     *
     */
    public function findDataOfArea(){
        $ret = array();
        $reqs = array();
        $area_id = isset($_REQUEST['area_id'])?$_REQUEST['area_id']:'';
        $reqs['get_model'] = 'tb_ms_user_area';
        $reqs['get_where_keys'][] = 'id';
        $reqs['get_where_vals'][] = $area_id;
        // check cache - start
        $cache_name = 'db_tb_ms_user_area'.':'.$area_id;
        $cache_data = S($cache_name);
        if (!empty($cache_data)) {
            return $cache_data;
        }
        // check cache - end
        $ret = DataMain::doModelInfo($reqs);
        //set cache data
        S($cache_name, $ret, DataMain::$cachetime);
        return $ret;
    }

}

