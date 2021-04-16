<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/25
 * Time: 17:55
 */

class SettlementLogic extends BaseLogic
{

    /**
     * @param $data
     * @return bool
     */
    public function create($data) {
        if(!$data['settlement']['id']) {
            $id = $res = $this->addSettlement($data);
        }else {
            $id = $data['settlement']['id'];
            $res = $this->updateSettlement($data);
        }
        if(!$res) return false;
        return $id;
    }

    public function addSettlement($data) {
        M()->startTrans();
        $settlement_m = new SettlementModel();
        if(!($settlement_m->create($data['settlement']) && $res_settlement = $settlement_m->add())) {
            $this->error = '导入信息保存失败';
            M()->rollback();
            Logs(['$data' => $data, 'error_info' => $this->error], __FUNCTION__, __CLASS__);
            return false;
        }
        if($this->saveSettlementDetail($res_settlement,$data)) {
            return $res_settlement;
        }
        return false;
    }

    public function updateSettlement($data) {
        M()->startTrans();
        $settlement_m               = D('Settlement');
        $order_id   = D('SettlementOrder')->where(['settlement_id'=>$data['settlement']['id']])->getField('id');
        $res_order  = D('SettlementOrder')->where(['settlement_id'=>$data['settlement']['id']])->delete();
        $res_goods  = D('SettlementOrderGoods')->where(['order_id'=>$order_id])->delete();
        $res_sell   = D('SettlementOrderSell')->where(['order_id'=>$order_id])->delete();
        $res_plat   = D('SettlementOrderPlat')->where(['order_id'=>$order_id])->delete();
        $res_return = D('SettlementOrderReturn')->where(['order_id'=>$order_id])->delete();
        $res_ship   = D('SettlementOrderShip')->where(['order_id'=>$order_id])->delete();
        $res_other  = D('SettlementOrderOther')->where(['order_id'=>$order_id])->delete();
        if(!($res_order && $res_goods && $res_sell && $res_plat && $res_return && $res_ship && $res_other)) {
            $this->error = '删除旧详情数据失败';
            Logs(['$data' => $data, 'error_info' => $this->error], __FUNCTION__, __CLASS__);
            return false;
        }
        if(!($settlement_m->create($data['settlement']) && $res_settlement = $settlement_m->save())) {
            $this->error = '导入信息保存失败';
            Logs(['$data' => $data, 'error_info' => $this->error], __FUNCTION__, __CLASS__);
            M()->rollback();
            return false;
        }
        return $this->saveSettlementDetail($res_settlement,$data);
    }

    public function saveSettlementDetail($id,$data) {
        $goods = [];
        $order_m = new SettlementOrderModel();
        foreach ($data['order'] as $v) {
            $v['info']['settlement_id'] = $id;
            if(!($order_m->create($v['info']) && $res_order = $order_m->add())) {
                echo $order_m->getError();
                echo $order_m->getDbError();
                $this->error = '订单信息保存失败';
                Logs(['$data' => $data, '$res_order' => $res_order, 'error_info' => $this->error], __FUNCTION__, __CLASS__);
                M()->rollback();
                return false;
            }
            foreach ($v['goods'] as $val) {
                $val['settlement_order_id'] = $res_order;
                $val['created_by'] = $_SESSION['m_loginname'];
                $val['updated_by'] = $_SESSION['m_loginname'];
                $goods[]           = $val;
            }
            $v['sell']['settlement_order_id'] = $res_order;
            $v['sell']['created_by'] = $_SESSION['m_loginname'];
            $v['sell']['updated_by'] = $_SESSION['m_loginname'];
            $sell[] = $v['sell'];
            $v['plat']['settlement_order_id'] = $res_order;
            $v['plat']['created_by'] = $_SESSION['m_loginname'];
            $v['plat']['updated_by'] = $_SESSION['m_loginname'];
            $plat[] = $v['plat'];
            $v['return']['settlement_order_id'] = $res_order;
            $v['return']['created_by'] = $_SESSION['m_loginname'];
            $v['return']['updated_by'] = $_SESSION['m_loginname'];
            $return[] = $v['return'];
            $v['ship']['settlement_order_id'] = $res_order;
            $v['ship']['created_by'] = $_SESSION['m_loginname'];
            $v['ship']['updated_by'] = $_SESSION['m_loginname'];
            $ship[] = $v['ship'];
            $v['other']['settlement_order_id'] = $res_order;
            $v['other']['created_by'] = $_SESSION['m_loginname'];
            $v['other']['updated_by'] = $_SESSION['m_loginname'];
            $other[] = $v['other'];
        }
        $res_goods = (new SettlementOrderGoodsModel())->addAll($goods);
        $res_sell = (new SettlementOrderSellModel())->addAll($sell);
        $res_plat = (new SettlementOrderPlatModel())->addAll($plat);
        $res_return = (new SettlementOrderReturnModel())->addAll($return);
        $res_ship = (new SettlementOrderShipModel())->addAll($ship);
        $res_other = (new SettlementOrderOtherModel())->addAll($other);
        if(!($res_order && $res_goods && $res_sell && $res_plat && $res_return && $res_ship && $res_other)) {
            M()->rollback();
            $this->error = '保存失败：'.M()->getDbError();
            Logs(['$data' => $data, '$res_order' => $res_order,'$res_goods' => $res_goods, '$res_sell' => $res_sell, '$res_plat' => $res_plat, '$res_return' => $res_return, '$res_ship' => $res_ship, '$res_other' => $res_other, 'error_info' => $this->error], __FUNCTION__, __CLASS__);
            return false;
        }
        M()->commit();
        return true;
    }

    public function getList($params) {
        if($params['plat_cd']) {
            return $this->getSettlementList($params);
        }else {
            return $this->getSettlementOrderList($params);
        }
    }

    /**
     * @param $params
     * @return array
     * 没有店铺时，展示导入总信息列表
     */
    public function getSettlementList($params) {
        import('ORG.Util.Page');// 导入分页类
        $model  = new SettlementModel();
        $where  = $this->getSettlementWhere($params);
        $count  = $model->alias('t')->where($where)->count();
        $page   = new Page($count,$params['rows'] ? : 20);
        $sort   = $this->getSettlementSort($params);
        $list = $model
            ->field('t.*,a.STORE_NAME store_name')
            ->alias('t')
            ->join('left join tb_ms_store a on a.ID=t.store_id')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order($sort)
            ->select();
        foreach ($list as &$v) {
            $v['site']          = cdVal($v['site_cd']);
            $v['plat']          = cdVal($v['plat_cd']);
            $v['currency']      = cdVal($v['currency_cd']);
            if($v['start_date']) $v['start_date'] = substr($v['start_date'], 0, 7);
            if($v['end_date']) $v['end_date'] = substr($v['end_date'], 0, 7);
        }
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function getSettlementSort($params) {
        if($params['sort_key']) {
            $sort_type = $params['sort_type'] ? ' DESC' : ' ASC';
            $sort = $params['sort_key'] . $sort_type;
        }else {
            $sort = 'created_at DESC';
        }
        return $sort;
    }

    public function getSettlementWhere($params) {
        $where['t.plat_cd']   = $params['plat_cd'];
        $where['t.site_cd']   = $params['site_cd'];
        $where['t.store_id']  = $params['store_id'];
        if($params['start_date']) $where['start_date'] = ['egt',$params['start_date'] . '-01 00:00:00'];
        if($params['end_date']) $where['end_date'] = ['elt',$params['end_date'] . '-31 23:59:59'];
        if(is_numeric($params['total_amount_min']) && is_numeric($params['total_amount_max'])) {
            $where['total_amount'] = ['between',[$params['total_amount_min'],$params['total_amount_max']]];
        }elseif(is_numeric($params['total_amount_min'])){
            $where['total_amount'] = ['egt',$params['total_amount_min']];
        }elseif (is_numeric($params['total_amount_max'])) {
            $where['total_amount'] = ['elt',$params['total_amount_max']];
        }
        if(is_numeric($params['total_cost_min']) && is_numeric($params['total_cost_max'])) {
            $where['total_cost'] = ['between',[$params['total_cost_min'],$params['total_cost_max']]];
        }elseif(is_numeric($params['total_cost_min'])){
            $where['total_cost'] = ['egt',$params['total_cost_min']];
        }elseif (is_numeric($params['total_cost_max'])) {
            $where['total_cost'] = ['elt',$params['total_cost_max']];
        }
        if($params['introduction']) $where['introduction'] = ['like',$params['introduction']];
        if($params['updated_by']) $where['updated_by'] = $params['updated_by'];
        return $where;
    }

    /**
     * @param $params
     * @return array
     * 有店铺时，展示订单信息列表
     */
    public function getSettlementOrderList($params) {
        import('ORG.Util.Page');// 导入分页类
        $model = new SettlementOrderModel();
        $where = $this->getSettlementOrderWhere($params);
        $count = $model->where($where)->count();
        $page = new Page($count,$params['rows'] ? : 20);
        $order = $model->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        $settlement_ids         = array_column($order,'settlement_id');
        $settlement_order_ids   = array_column($order,'id');
        $settlement = (new SettlementModel())
            ->field('t.*,a.STORE_NAME store_name')
            ->alias('t')
            ->join('left join tb_ms_store a on a.ID=t.store_id')
            ->where(['t.id'=>['in',$settlement_ids]])
            ->select();
        $goods = (new SettlementOrderGoodsModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $plat = (new SettlementOrderPlatModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $return = (new SettlementOrderReturnModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $sell = (new SettlementOrderSellModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $ship = (new SettlementOrderShipModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $other = (new SettlementOrderOtherModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $list = [];
        $goods_new = [];
        foreach ($goods as $v) {
            $goods_new[$v['settlement_order_id']][] = $v;
        }
        foreach ($order as $v) {
            $data = [
                'settlement' => array_column_key($settlement, 'id')[$v['settlement_id']],
                'order' => $v,
                'goods' => $goods_new[$v['id']],
                'plat' => array_column_key($plat, 'settlement_order_id')[$v['id']],
                'return' => array_column_key($return, 'settlement_order_id')[$v['id']],
                'sell' => array_column_key($sell, 'settlement_order_id')[$v['id']],
                'ship' => array_column_key($ship, 'settlement_order_id')[$v['id']],
                'other' => array_column_key($other, 'settlement_order_id')[$v['id']],
            ];
            $data['settlement']['site']     = cdVal($data['settlement']['site_cd']);
            $data['settlement']['plat']     = cdVal($data['settlement']['plat_cd']);
            $data['settlement']['currency'] = cdVal($data['settlement']['currency_cd']);
            $data['order']['currency']      = cdVal($data['settlement']['currency_cd']);
            $list[] = $data;
        }
        $list = $this->settlementListFormat($list);
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function settlementListFormat($list) {
        foreach ($list as &$v) {
            if($v['settlement']['start_date']) $v['settlement']['start_date'] = substr($v['settlement']['start_date'], 0, 7);
            if($v['settlement']['end_date']) $v['settlement']['end_date'] = substr($v['settlement']['end_date'], 0, 7);
            if($v['order']['start_date']) $v['order']['start_date'] = substr($v['order']['start_date'], 0, 7);
            if($v['order']['end_date']) $v['order']['end_date'] = substr($v['order']['end_date'], 0, 7);
            if($v['order']['deposit_date']) $v['order']['deposit_date'] = substr($v['order']['deposit_date'], 0, 7);
            if($v['order']['paid_on_date']) $v['order']['paid_on_date'] = substr($v['order']['paid_on_date'], 0, 10);
            if($v['order']['order_created_date']) $v['order']['order_created_date'] = substr($v['order']['order_created_date'], 0, 10);
            if($v['ship']['shipped_date']) $v['ship']['shipped_date'] = substr($v['ship']['shipped_date'], 0, 10);
            if($v['ship']['confirmed_date']) $v['ship']['confirmed_date'] = substr($v['ship']['confirmed_date'], 0, 10);
            if($v['return']['return_date']) $v['return']['return_date'] = substr($v['return']['return_date'], 0, 10);
            if($v['return']['return_date']) $v['return']['return_date'] = substr($v['return']['return_date'], 0, 10);
        }
        return $list;
    }

    public function getSettlementOrderWhere($params) {
        $where = [];
        if($params['start_date']) $where['start_date'] = ['egt',$params['start_date'] . '-01 00:00:00'];
        if($params['end_date']) $where['end_date'] = ['elt',$params['end_date'] . '-31 23:59:59'];
        return $where;
    }

    /**
     * @param $params
     * @return array
     * 获取B2C结算详情
     */
    public function getDetail($params) {
        import('ORG.Util.Page');// 导入分页类
        $model                  = new SettlementOrderModel();
        $where                  = ['settlement_id'=>$params['id']];
        $count                  = $model->where($where)->count();
        $page                   = new Page($count,$params['rows'] ? : 20);
        $order                  = $model->where($where)->limit($page->firstRow, $page->listRows)->select();
        $settlement_order_ids   = array_column($order,'id');
        $settlement = (new SettlementModel())
            ->field('t.*,a.STORE_NAME store_name')
            ->alias('t')
            ->join('left join tb_ms_store a on a.ID=t.store_id')
            ->where(['t.id'=>$params['id']])
            ->find();
        $settlement['site']     = cdVal($settlement['site_cd']);
        $settlement['plat']     = cdVal($settlement['plat_cd']);
        $settlement['currency'] = cdVal($settlement['currency_cd']);
        $goods      = (new SettlementOrderGoodsModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $plat       = (new SettlementOrderPlatModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $return     = (new SettlementOrderReturnModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $sell       = (new SettlementOrderSellModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $ship       = (new SettlementOrderShipModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $other      = (new SettlementOrderOtherModel())->where(['settlement_order_id'=>['in',$settlement_order_ids]])->select();
        $list       = [];
        $goods_new  = [];
        foreach ($goods as $v) {
            $goods_new[$v['settlement_order_id']][] = $v;
        }
        foreach ($order as $v) {
            $data = [
                'settlement'    => $settlement, //为方便前端开发从外层迁移到内层，但造成数据重复
                'order'         => $v,
                'goods'         => $goods_new[$v['id']],
                'plat'          => array_column_key($plat, 'settlement_order_id')[$v['id']],
                'return'        => array_column_key($return, 'settlement_order_id')[$v['id']],
                'sell'          => array_column_key($sell, 'settlement_order_id')[$v['id']],
                'ship'          => array_column_key($ship, 'settlement_order_id')[$v['id']],
                'other'         => array_column_key($other, 'settlement_order_id')[$v['id']],
            ];
            $data['order']['currency']  = cdVal($data['settlement']['currency_cd']);
            $list[]                     = $data;
        }
        $list = $this->settlementListFormat($list);
        return ['detail'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function update($params) {
        $settlement_m = new SettlementModel();
        $settlement_m->startTrans();
        $settlement_info = $settlement_m->field('excel_has_date')->where(['id'=>$params['id']])->find();
        if(!$settlement_info) {
            $settlement_m->rollback();
            $this->error = '收支明细不存在';
            return false;
        }
        $save['id']             = $params['id'];
        $save['introduction']   = $params['introduction'];
        if($params['start_date']) $save['start_date'] = $params['start_date'].'-01';
        if($params['end_date']) $save['end_date'] = $params['end_date'].'-01';
        if(!$settlement_m->create($save) || $settlement_m->save() === false) {
            $settlement_m->rollback();
            $this->error = '保存失败';
            return false;
        }
        if(!$settlement_info['excel_has_date']) {
            $save_order['start_date']   = $save['start_date'];
            $save_order['end_date']     = $save['end_date'];
            $res_order = (new SettlementOrderModel())->where(['settlement_id'=>$params['id']])->save($save_order);
            if($res_order === false) {
                $settlement_m->rollback();
                $this->error = '订单数据保存失败';
                return false;
            }
        }
        $settlement_m->commit();
        return true;
    }

    public function delete($id) {
        M()->startTrans();
        $settlement_order_m   = new SettlementOrderModel();
        $settlement_id = (new SettlementModel())->where(['id'=>['in',$id]])->getField('id');
        if(!$settlement_id) {
            M()->rollback();
            $this->error = '结算详情不存在';
            return false;
        }
        $res_settlement = (new SettlementModel())->where(['id'=>['in',$id]])->delete();
        $order_id       = $settlement_order_m->where(['settlement_id'=>['in',$id]])->getField('id',true);
        $res_order      = $settlement_order_m->where(['settlement_id'=>['in',$id]])->delete();
        $res_goods      = (new SettlementOrderGoodsModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        $res_plat       = (new SettlementOrderPlatModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        $res_return     = (new SettlementOrderReturnModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        $res_sell       = (new SettlementOrderSellModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        $res_ship       = (new SettlementOrderShipModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        $res_other      = (new SettlementOrderOtherModel())->where(['settlement_order_id'=>['in',$order_id]])->delete();
        if(!($res_settlement && $res_order && $res_goods && $res_sell && $res_plat && $res_return && $res_ship && $res_other)) {
            M()->rollback();
            $this->error = '删除失败：'.M()->getDbError();
            return false;
        }
        M()->commit();
        return true;
    }

    public function getFilePath($settlement_id) {
        return (new SettlementModel())->where(['id'=>$settlement_id])->getField('file_path');
    }
}