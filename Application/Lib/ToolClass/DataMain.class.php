<?php

/**
 *  class: obtain some data from db
 *
 */


class DataMain{
    public static $cachetime = 60;

    public static function getItems($items, $key = null,$throw=true)
    {
        if ($key !== null)
        {
            if (key_exists($key, $items))
            {
                return $items[$key];
            }
            if($throw)
            {
                return null;
            }
            return 'unknown key:' . $key;
        }
        return $items;
    }

    const Status_Enable = 1;
    const Status_Desable = 0;
    public static function getStatusItems($key = null)
    {
        $items = [
            self::Status_Enable => L('可用'), 
            self::Status_Desable => L('禁用')
        ];
        return self::getItems($items, $key);
    }

    const Status_open = 1;
    const Status_close = 0;
    public static function getStatusItemsForOpen($key = null)
    {
        $items = [
            self::Status_open => L('启用'), 
            self::Status_close => L('关闭')
        ];
        return self::getItems($items, $key);
    }

    const Status_Y = 'Y';
    const Status_N = 'N';
    public static function getStatusYN($key = null)
    {
        $items = [
            self::Status_N => L('否'),
            self::Status_Y => L('是'),
        ];
        return self::getItems($items, $key);
    }


    /**
     *
     *
     */
    public static function get_table_fields($table){
        $Models = new Model();
        $arr = $Models->query("DESCRIBE `".$table."`");
        $fieldarr=array();
        foreach($arr as $zhi){
            $fieldarr[]=$zhi['Field'];
        }
        return $fieldarr;
    }

    /**
     *
     *
     */
    public static function fieldData($fields,$table){
        $new=array();
        $table_fields=self::get_table_fields($table);
        foreach($fields as $key=>$value){
            if(in_array($key, $table_fields)){
                $new[$key]=($value);
            }
        }
        return $new;
    }

    /**
     *  Get tb datas
     *
     */
    public function doModelInfo($get_params){
        if(!$get_params){
            $get_params = $_REQUEST;
        }
        $ret = array();
        $get_model = isset($get_params['get_model'])?$get_params['get_model']:'';
        $get_where_keys = isset($get_params['get_where_keys'])?$get_params['get_where_keys']:'';
        $get_where_vals = isset($get_params['get_where_vals'])?$get_params['get_where_vals']:'';
        $get_order = isset($get_params['get_order'])?$get_params['get_order']:'';
        $get_page = isset($get_params['get_page'])?$get_params['get_page']:'';
        $get_limit = isset($get_params['get_limit'])?$get_params['get_limit']:'';
        $get_is_all = isset($get_params['get_is_all'])?$get_params['get_is_all']:'';
        if(!is_array($get_where_keys)) $get_where_keys=array();
        if(!is_array($get_where_vals)) $get_where_vals=array();
        if($get_page<0)  $get_page=1;
        if($get_limit>10000)  $get_limit=10000;
        $get_limit = intval($get_limit);

        if(!$get_model){
            return $ret;
        }

        $where_arr = array();
        $orderby = '';
        foreach($get_where_keys as $key=>$val){
            $cond_k = $val;
            $cond_v = isset($get_where_vals[$key])?$get_where_vals[$key]:'';
            $where_arr=array_merge($where_arr,array($cond_k=>$cond_v));
        }

        $Model = new Model();
        $list = $Model
            ->field('*')
            ->table($get_model)
            ->where($where_arr)
            ->order($orderby);
        if($get_is_all){
            $list = $list->limit("0,$get_limit");
            $ret['data'] = $list->select();
        }else{
            $list = $list->limit("0,1");
            $ret['data'] = $list->find();
        }
        return $ret;
    }


    /**
     *  gain admin info
     *
     */
    static public function gainAdminById($id){
        $info = D("Admin")->find($id);
        return $info;
    }

    /**
     *  gain admin name
     *
     */
    static public function gainAdminName($id){
        $data = self::gainAdminById($id);
        $name = isset($data['M_NAME'])?$data['M_NAME']:'';
        return $name;
    }

    /**
     *  Common func that sys delete data. 
     *
     */
    public static function dbDelByNameData($tb_name='',$where_data=array()){
        $status = $tb_name->where($where_data)->delete();
        return $status;
    }







}

