<?php

/**
 * User: yangsu
 * Date: 16/12/20
 * Time: 10:29
 *
 */
class DataAction extends BaseAction
{
    public $cdMapping = [
        'country_status' => 'BaseModel::getAreaCode',
    ];


    public function index()
    {
        echo 'dir';

    }

    /**
     * 公司
     */
    public function company()
    {
        echo 'company';
    }

    /**
     * 部门
     */
    public function department()
    {
        echo 'department';
    }

    public function user()
    {
        echo 'user';
    }

    public function goods_class()
    {
        echo 'goods_class';
    }

    public function goods()
    {
        echo 'goods';
    }

    public function currency()
    {
        echo 'currency';
    }

    /**
     * @param array $require
     */
    public function cdList($require = array())
    {
        $data_get = DataModel::getData(true, 'query');
        $data = array();
        if ($data_get) {
            foreach ($data_get as $value) {
                $cd_key_arr = array_keys($this->cdMapping);
                if (in_array($value, $cd_key_arr)) {
                    $data[$value] = call_user_func($this->cdMapping[$value]);
                } else {

                }
            }
        }
        $return_data['status'] = 400;
        $return_data['msg'] = 'error';
        $return_data['data'] = $data;
        if (!empty($return_data['data'])) {
            $return_data['status'] = 200000;
            $return_data['msg'] = 'success';
        }
        $this->ajaxReturn($return_data);
    }


}