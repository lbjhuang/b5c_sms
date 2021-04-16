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
        $this->display("Attribute");
    }

     //品牌列表
    public function brandList()
    {
        $this->display("Brand");
    }

    //类目列表
    public function cateList()
    {
        $this->display("Category");
    }

    //商品列表
    public function productList()
    {
        $this->display("Good");
    }

    public function thirdSku() {
        $this->display("Sku");
    }

    public function assemble() {
        $this->display("Assemble");
    }

    public function price() {
        $this->display("PriceBook");
    }

    //gp商品图片管理
    public function GpImg()
    {
        $this->display("GpImg");
    }
    //预售商品
    public function Advance()
    {
        $this->display("Advance");
    }
}
