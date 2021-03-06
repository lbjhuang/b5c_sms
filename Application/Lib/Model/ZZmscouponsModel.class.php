<?php
class ZZmscouponsModel extends CommonModel{

    protected $trueTableName = "tb_ms_coupons";


    // 
    public function _before_insert(&$data,$options){
        parent::_before_insert($data, $options);
        $data['create_time'] = time();
        if(empty($data['code'])){
            $data['code'] = uniqid();
        }
        if(empty($data['create_admin_id'])){
            if(!empty($_SESSION['user_id'])){
                $data['create_admin_id'] = $_SESSION['user_id'];
            }
        }
    }

    // 
    public function _before_update(&$data,$options){
        parent::_before_update($data, $options);
        $data['update_time'] = time();
    }

    public function _after_select(&$resultSet,$options){
        parent::_after_select($resultSet, $options);
    }

    /**
     *  Auto calculate remain num
     *
     */
    public function check_remain_num($id){
        $status = null;
        $data = $this->find($id);
        if($data){
            if($data['remain_num']!=$data['circulation']-$data['already_receive_num']){
                $data['remain_num']=$data['circulation']-$data['already_receive_num'];
                $editArr = array();
                $editArr['remain_num'] = $data['remain_num'];
                $status = $this->where(array("id"=>$id))->data($editArr)->save();
            }
        }
        return $status;
    }

    /**
     *  check data : if ok, format data . if error , return error.
     * @param array $info 
     * @return array (is_error,err_msg,data)
     */
    public function check_data_pass($info,$edit_info=null){
        $ret = array();
        $is_error = 0;
        $err_msg = '';

        $coupon = array();
        $coupon['name'] = isset($info['name'])?$info['name']:'';
        $coupon['plat_cd'] = isset($info['plat_cd'])?$info['plat_cd']:'';
        $coupon['valid_type'] = isset($info['valid_type'])?$info['valid_type']:'';
        $coupon['valid_start'] = isset($info['valid_start'])?$info['valid_start']:'';
        $coupon['valid_end'] = isset($info['valid_end'])?$info['valid_end']:'';
        $coupon['valid_receive_after_days'] = isset($info['valid_receive_after_days'])?$info['valid_receive_after_days']:'';
        $coupon['circulation'] = isset($info['circulation'])?$info['circulation']:'';
        $coupon['is_limit_circulation'] = isset($info['is_limit_circulation'])?$info['is_limit_circulation']:'';
        $coupon['circulation_daily'] = isset($info['circulation_daily'])?$info['circulation_daily']:'';
        $coupon['receive_type'] = isset($info['receive_type'])?$info['receive_type']:'';
        $coupon['receive_num_each'] = isset($info['receive_num_each'])?$info['receive_num_each']:'';
        $coupon['receive_interval_days'] = isset($info['receive_interval_days'])?$info['receive_interval_days']:'';
        $coupon['receive_num_day'] = isset($info['receive_num_day'])?$info['receive_num_day']:'';
        $coupon['usage_can_superposition'] = isset($info['usage_can_superposition'])?$info['usage_can_superposition']:'';
        $coupon['using_conditions_type'] = isset($info['using_conditions_type'])?$info['using_conditions_type']:'';
        $coupon['using_conditions_amount'] = isset($info['using_conditions_amount'])?$info['using_conditions_amount']:'';
        $coupon['favorable_results_type'] = isset($info['favorable_results_type'])?$info['favorable_results_type']:'';
        $coupon['favorable_money'] = isset($info['favorable_money'])?$info['favorable_money']:'';
        $coupon['favorable_discount'] = isset($info['favorable_discount'])?$info['favorable_discount']:'';
        $coupon['favorable_gift'] = isset($info['favorable_gift'])?$info['favorable_gift']:'';
        $coupon['favorable_coupon'] = isset($info['favorable_coupon'])?$info['favorable_coupon']:'';
        $coupon['use_scope_type'] = isset($info['use_scope_type'])?$info['use_scope_type']:'';
        $coupon['use_scope_product'] = isset($info['use_scope_product'])?$info['use_scope_product']:'';
        $coupon['delivering_way_type'] = isset($info['delivering_way_type'])?$info['delivering_way_type']:'';
        $coupon['delivering_way_sys'] = isset($info['delivering_way_sys'])?$info['delivering_way_sys']:'';
        $coupon['status'] = isset($info['status'])?$info['status']:'';

        //format
        $coupon['favorable_discount'] = floatval($coupon['favorable_discount']);
        if($coupon['valid_start'])
            $coupon['valid_start'] = strtotime($coupon['valid_start']);
        if($coupon['valid_end'])
            $coupon['valid_end'] = strtotime($coupon['valid_end'].' 23:59:59');
        $coupon['valid_start'] = intval($coupon['valid_start']);
        $coupon['valid_end'] = intval($coupon['valid_end']);
        $coupon['name'] = trim($coupon['name']);
        //check
        if(!$is_error){
            if(empty($coupon['name'])){
                $is_error = 1;
                $err_msg = L('Empty name');
            }
        }
        if(!$is_error){
            $len = strlen_utf8($coupon['name']);
            if(0<$len and $len<=100){
            }else{
                $is_error = 1;
                $err_msg = L('???????????????').' '.L('100????????????');
            }
        }
        //???????????????
        if(!$is_error){
            if($coupon['is_limit_circulation']){
                if($coupon['circulation_daily']>$coupon['circulation']){
                    $is_error = 1;
                    $err_msg = L('???????????????').' '.L('????????????????????????');
                }
            }
        }
        //????????????
        if(!$is_error){
            if($coupon['receive_type']==1){
                $max = $coupon['is_limit_circulation']==1?$coupon['circulation_daily']:$coupon['circulation'];
                if($coupon['receive_num_each']>$max){
                    $is_error = 1;
                    $err_msg = L('????????????').' '.L('?????????????????????????????????');
                }
            }
            elseif($coupon['receive_type']==2){
                $max = $coupon['is_limit_circulation']==1?$coupon['circulation_daily']:$coupon['circulation'];
                if(0<$coupon['receive_interval_days'] and $coupon['receive_interval_days']<=500){
                }else{
                    $is_error = 1;
                    $err_msg = L('????????????').' '.L('???: ????????????1???????????????500????????????');
                }
                if($coupon['receive_num_day']>$max){
                    $is_error = 1;
                    $err_msg = L('????????????').' '.L('?????????????????????????????????');
                }
            }
        }
        //????????????
        if(!$is_error){
            if($coupon['favorable_results_type']==2){
                if(1<=$coupon['favorable_discount'] and $coupon['favorable_discount']<=99){
                }else{
                    $is_error = 1;
                    $err_msg = L('????????????').' - '.L('????????????1???????????????99????????????');
                }
                // if(!preg_match('/^[\d]{1,2}$/is',$coupon['favorable_discount'])){
                //     $is_error = 1;
                //     $err_msg = L('????????????').' - '.L('????????????1???????????????99????????????');
                // }
                if(1<=$coupon['favorable_discount'] and $coupon['favorable_discount']<=10){
                }else{
                    $is_error = 1;
                    $err_msg = L('????????????').' - '.L('????????????1???????????????10?????????');
                }
            }
            elseif($coupon['favorable_results_type']==5){
                //check skuid - favorable_gift
                $check_exist = M('ms_guds_opt','tb_')->where(array('GUDS_OPT_ID'=>$coupon['favorable_gift'],))->find();
                if(empty($check_exist)){
                    $is_error = 1;
                    $err_msg = L('??????sku id?????????');
                }
            }
            elseif($coupon['favorable_results_type']==6){
                //check favorable_coupon
                $this_code = isset($edit_info['code'])?$edit_info['code']:'';
                $check_data = D("ZZmscoupons")->where(
                    array('code'=>array(
                            array('eq',($coupon['favorable_coupon'])),
                            array('neq',($this_code)),
                            'and',
                        )
                    )
                )->find();
                if(empty($check_data)){
                    $is_error = 1;
                    $err_msg = L('???????????????ID?????????');
                }
            }
        }
        //????????????
        if(!$is_error){
            if($coupon['using_conditions_type']==2){
                if($coupon['using_conditions_amount']<=0){
                    $is_error = 1;
                    $err_msg = L('????????????').' - '.L('????????????1????????????');
                }
            }
        }
        //???-???
        if(!$is_error){
            if($coupon['using_conditions_type']==2 and $coupon['favorable_results_type']==1){
                if($coupon['using_conditions_amount']<$coupon['favorable_money']){
                    $is_error = 1;
                    $err_msg = L('?????????????????????,??????????????????');
                }
            }
        }


        $ret['is_error'] = $is_error;
        $ret['err_msg'] = $err_msg;
        $ret['data'] = $coupon;
        return $ret;
    }

    /**
     *  ????????? [1????????????2????????????]
     *
     */
    public function gain_validity_period($coupon){
        $ret = '';
        if($coupon['valid_type']==1){
            $ret .= $coupon['valid_start']?date('Y-m-d',$coupon['valid_start']):'';
            $ret .= '~';
            $ret .= $coupon['valid_end']?date('Y-m-d',$coupon['valid_end']):'';
        }elseif($coupon['valid_type']==2){
            $ret = str_replace('<<n>>',$coupon['valid_receive_after_days'],"?????????<<n>>???");
        }
        return $ret;
    }

    /**
     *  ???????????? (1?????????2?????????)
     *
     */
    public function gain_using_condition($coupon){
        $ret = '';
        if($coupon['using_conditions_type']==1){
            $ret = '?????????';
        }elseif($coupon['using_conditions_type']==2){
            $ret = '???'.$coupon['using_conditions_amount'];
        }
        return $ret;
    }

    /**
     *  ???????????? [1???2??????3?????????4?????????5?????????6??????]
     *
     */
    public function gain_favorable_result($coupon){
        $ret = '';
        if($coupon['favorable_results_type']==1){
            $ret = '???'.$coupon['favorable_money'];
        }elseif($coupon['favorable_results_type']==2){
            $ret = str_replace('<<n>>',$coupon['favorable_discount'],"???<<n>>???");
        }elseif($coupon['favorable_results_type']==3){
            $ret = '?????????';
        }elseif($coupon['favorable_results_type']==4){
            $ret = '?????????';
        }elseif($coupon['favorable_results_type']==5){
            $ret = '?????????';
        }elseif($coupon['favorable_results_type']==6){
            $ret = '??????';
        }
        return $ret;
    }

    /**
     *  ???????????? [1???????????????2???????????????3???????????????4???????????????]
     *
     */
    public function gain_use_scope($coupon){
        return self::getItemsForScope($coupon['use_scope_type']);
    }

    const Scope_web = 1;
    const Scope_category = 2;
    const Scope_brand = 3;
    const Scope_goods = 4;
    public static function getItemsForScope($key = null)
    {
        $items = [
            self::Scope_web => '???????????????', 
            self::Scope_category => '???????????????',
            self::Scope_brand => '???????????????',
            self::Scope_goods => '???????????????',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  ????????????
     *
     */
    public function gain_deliver_way($coupon){
        return self::getItemsForDeliver($coupon['delivering_way_type']);
    }

    const Deliver_sys = 1;
    const Deliver_manual = 2;
    const Deliver_user = 3;
    public static function getItemsForDeliver($key = null)
    {
        $items = [
            self::Deliver_sys => '????????????', 
            self::Deliver_manual => '????????????',
            self::Deliver_user => '????????????',
        ];
        return DataMain::getItems($items, $key);
    }

    /**
     *  ????????? ?????????/?????????*100%
     *
     */
    public function gain_use_rate($coupon){
        $ret = '';
        $coupon['already_use_num'] = isset($coupon['already_use_num'])?$coupon['already_use_num']:'';
        $coupon['already_receive_num'] = isset($coupon['already_receive_num'])?$coupon['already_receive_num']:'';
        $coupon['already_use_num'] = intval($coupon['already_use_num']);
        $coupon['already_receive_num'] = intval($coupon['already_receive_num']);
        if($coupon['already_receive_num']!=0){
            $ret = $coupon['already_use_num']/$coupon['already_receive_num']*100;
        }
        $ret = round($ret,2).'%';
        return $ret;
    }



}
