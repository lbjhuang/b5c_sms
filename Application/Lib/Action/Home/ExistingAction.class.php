<?php
/**
 * 现存量
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/21
 * Time: 10:32
 */

class ExistingAction extends BaseAction
{
    public function _initialize()
    {
        import('ORG.Util.Page');
    }

    public function index()
    {
        $model = new ExistingModel();

        $response = $model->searchModel($this->getParams());

        $this->AjaxReturn($response, 'success', 1);
    }
}