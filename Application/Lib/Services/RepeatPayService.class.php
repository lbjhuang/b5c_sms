<?php

class RepeatPayService extends Service
{

    public $user_name;
    public $model;
    public $pay_log_table;

    public $pay_status = [
        '1' => '支付成功'
    ];

    public $status = [
        '1' => '待处理',
        '2' => '已处理'
    ];

    public $error_status = [
        '1' => '重复支付'
    ];
    
    public function __construct($model)
    {
        $this->user_name                 = DataModel::userNamePinyin();
        $this->model                     = empty($model) ? new Model() : $model;
        $this->pay_log_table              = M('b2c_pay_log_copy1', 'tb_');
    }

    public function getRepeatList()
    {
        $field = 'platform, orderIds, pay_method, pay_id, pay_time, currency, amount, status, error_status, pay_status, orderCurrency, orderPrice';
        $where['orderIds'] = array('neq', '');
        $data = $this->pay_log_table->where($where)->field($field)->order('pay_time desc')->select();
        return $this->adjustData($data);
    }

    public function adjustData($data = [])
    {
        if (!$data) {
            return false;
        }
        $new_data = [];
        foreach ($data as $key => $value) {
            $value['pay_status_val'] = $this->pay_status[$value['pay_status']];
            $value['status_val'] = $this->status[$value['status']];
            $value['error_status_val'] = $this->error_status[$value['error_status']]; 
            $value['platform_val'] = cdVal($value['platform']); 
            $new_data[$value['orderIds']][] = $value;
        }
        return $new_data;
    }

    public function changeRepeatStatus($param = [])
    {
        $save['status'] = 2;
        $where['orderIds'] = $param['orderIds'];
        $data_res = $this->pay_log_table->where($where)->select();
        if (!$data_res) {
            throw new Exception('更改状态失败，请检查订单号是否合法');
        }
        $res = $this->pay_log_table->where($where)->save($save);
        if ($res === false) {
            throw new Exception('更改状态失败');
        }
    }
}
