<?php
/**
 * 订单物流派单、发货业务处理。
 * 订单系统处理订单的操作，操作完成后进行发货时请求该类相关的接口
 * 实现自动派单发货、或手工派单发货。
 * 
 * 分为两大类：
 *  1、自动派单
 *  2、手动派单
 *
 * User: afanti
 * Date: 2017/10/13
 * Time: 17:25
 */
class ShippingAction extends BaseAction{
    
    public function _initialize()
    {
        parent::_initialize();
    }
    
    public function autoShipping()
    {
    
    }
    
    
    public function manuallyShopping()
    {
        
    }
    
    
}