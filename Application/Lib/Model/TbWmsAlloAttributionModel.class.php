<?php


/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2021/04/08
 * Time: 14:25
 */

class TbWmsAlloAttributionModel extends BaseModel
{
    const ALLO_ATTR_AUDIT_WAIT     = 'N003000001'; // 待审核
    const ALLO_ATTR_AUDIT_FINISHED = 'N003000002'; // 已完成
    const ALLO_ATTR_AUDIT_FAILED   = 'N003000003'; //审核失败
    const ALLO_ATTR_AUDIT_CANCELED = 'N003000004'; //已取消

    protected $trueTableName = 'tb_wms_allo_attribution';
}