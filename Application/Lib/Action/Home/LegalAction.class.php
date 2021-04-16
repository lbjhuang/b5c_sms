<?php
/**
 * Created by PhpStorm.
 * User: shenmo
 * Date: 2019/7/30
 * Time: 10:20
 */

class LegalAction extends BaseAction
{
    public function _initialize()
    {
        parent::_initialize();
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
    }

    /**
     * 商标列表页
     */
    public function odm()
    {
        $this->display();
    }

    /**
     * 商标新增页
     */
    public function odm_add()
    {
        $this->display('odm_add');
    }

    /**
     * 商标新增页
     */
    public function odm_edit()
    {
        $this->display('odm_edit');
    }


    /**
     * 注册商标列表页
     */
    public function registered_trademark()
    {
        $this->display();
    }

    /**
     * 使用商标列表页
     */
    public function use_trademark()
    {
        $this->display();
    }




}