<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/25
 * Time: 17:57
 */

class WarehouseAction extends BaseAction
{
    public function warehouse_end_revoke() {
        $params     = $this->params();
        $return_l   = D('Purchase/Warehouse','Logic');
        $res        = $return_l->warehouseEndRevoke($params['ship_id']);
        if($res) {
            $this->ajaxSuccess($res);
        }
        $this->ajaxReturn(['code'=>3000,'msg'=>$return_l->getError(),'data'=>$return_l->getData()]);
    }

}