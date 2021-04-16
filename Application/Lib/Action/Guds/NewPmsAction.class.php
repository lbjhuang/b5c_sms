<?php
/**
 * Created by PhpStorm.
 * User: Gshopper
 * Date: 2018/5/2
 * Time: 13:30
 */

class NewPmsAction extends BaseAction
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //属性列表
    public  function  optionList()
    {
        $this->display();
    }

     //品牌列表
    public function brandList()
    {
        $this->display();
    }

    //类目列表
    public function cateList()
    {
        $this->display();
    }

    //商品列表
    public function productList()
    {
        $this->display();
    }



}