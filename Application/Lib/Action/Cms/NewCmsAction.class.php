<?php
/**
 * Created by PhpStorm.
 * User: Gshopper
 * Date: 2018/5/2
 * Time: 13:30
 */

class NewCmsAction extends BaseAction
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //菜单列表
    public  function  menuList()
    {
        $this->display("Menu");
    }

     //广告列表
    public function AdList()
    {
        $this->display("Ads");
    }

    //活动列表
    public function activityList()
    {
        $this->display("Active");
    }

    //文章列表

    public function articleList()
    {

        $this->display("Article");
    }

    //楼层列表

        public function floorList()
        {

            $this->display("Floor");
        }

         //优惠券列表

        public function couponList()
        {

            $this->display("Coupon");
        }
    //评论列表

    public function commentList()
    {

        $this->display("Comment");
    }

    //留言咨询列表
    public function messageList()
    {

        $this->display("Message");
    }

    //栏目配置列表
    public function columnList()
    {

        $this->display("Column");
    }

     public function tagList()
     {
         $this->display("Tag");
     }

     public function gpReviewList()
     {
         $this->display("GPReview");
     }
     
     public function promoList()
     {
         $this->display("Promo");
     }

     public function hotWordsList()
     {
         $this->display("Hotwords");
     }

}

