<?php
/**
 *  
 *
 */
class ZZmsmessagepushresModel extends CommonModel{

    protected $trueTableName = "tb_ms_message_push_res";


    // 
    public function _before_insert(&$data,$options){
        parent::_before_insert($data, $options);
        $data['add_time'] = time();
    }

    // 
    public function _before_update(&$data,$options){
        $data['add_time'] = time();
        parent::_before_update($data, $options);

    }

    public function _after_select(&$resultSet,$options){
        parent::_after_select($resultSet, $options);
    }

    /**
     *  push res : msg and user
     *
     */
    public function addPushResUser($msg_id,$cust_id,$failed=1){
        $new = array();
        $new['msg_id'] = $msg_id;
        $new['CUST_ID'] = $cust_id;
        $new['failed'] = $failed;
        //check exist , if not then add
        $arr=$this->field ("*")
                ->where (array('msg_id'=>$msg_id,'CUST_ID'=>$cust_id,))
                ->find();
        $findId = isset($arr['id'])?$arr['id']:null;
        if($findId>0){
            //update
            $status = $this->where(array('id'=>$findId))->data($new)->save();
            return $status;
        }
        $status = $this->data($new)->add();
        return $status;
    }

}
