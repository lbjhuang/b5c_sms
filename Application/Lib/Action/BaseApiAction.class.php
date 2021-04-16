<?php

/**
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/2/11
 * Time: 17:16
 */
class BaseApiAction extends Action
{
    public $model;
    public $error_message;

    public function _initialize()
    {
        $this->model = new \Model();
    }

    /**
     * @param $exception
     * @param null $Model
     * @return array
     */
    public function catchException($exception, $Model = null)
    {
        $res = DataModel::$error_return;
        if ($this->error_message) {
            $msg_arr    = array_values($this->error_message);
            $res['msg'] = $res['info'] = $msg_arr[0][0];
        } else {
            $res['msg'] = $res['info'] = $exception->getMessage();
        }
        if ($exception->getCode()) $res['code'] = $exception->getCode();
        if ($Model) {
            $Model->rollback();
        }
        return $res;
    }

    /**
     * @param $rules
     * @param $data
     * @param $custom_attributes
     *
     * @throws Exception
     */
    public function validate($rules, $data, $custom_attributes)
    {
        ValidatorModel::validate($rules, $data, $custom_attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $value) {
                throw new Exception(L($value[0]), 40001);
            }
        }
    }
}