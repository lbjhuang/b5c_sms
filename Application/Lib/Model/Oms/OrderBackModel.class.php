<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/4/18
 * Time: 18:27
 */
class OrderBackModel extends Model
{
    protected $trueTableName = '';

    protected $warehouses;

    protected $autoCheckFields = false;

    const STATIC_STATE_N001821000 = 'N001821000';

    /**
     * 订单只能回退到待拣货、待分拣、待核单、待派单
     * 回退的状态集合，其中 key 值作为流程顺序
     * @return array
     */
    public static function backState()
    {
        return [
            0 => 'N001821000',
            1 => 'N001820500',
            2 => 'N001820600',
            3 => 'N001820700'
        ];
    }

    /**
     * 订单处于待拣货、待分拣、待核单、待发货状态下可状态回退
     * 增加待发货可回退的状态
     */
    public static function backStateExtend()
    {
        return [
            1 => 'N001820500',
            2 => 'N001820600',
            3 => 'N001820700',
            4 => 'N001820800'
        ];
    }

    /**
     * 流程配置映射订单流程状态
     */
    public static function mappingProcessState()
    {
        return [
            'N002060100' => 'N001820500',
            'N002060200' => 'N001820600',
            'N002060300' => 'N001820700'
        ];
    }

    /**
     * 状态中文名映射
     */
    public static function stateNm()
    {
        return [
            'N001820500' => L('待拣货'),
            'N001820600' => L('待分拣'),
            'N001820700' => L('待核单'),
            'N001820800' => L('待发货'),
            'N001821000' => L('待派单')
        ];
    }

    /**
     * 仓库流程配置中文名映射
     */
    public static function warehouseProcessNm()
    {
        return [
            'N001820500' => L('拣货'),
            'N001820600' => L('分拣'),
            'N001820700' => L('核单'),
            'N001821000' => L('待派单')
        ];
    }

    /**
     * 主流程
     * @param $params
     * @return array
     */
    public function main($params)
    {
        if (($key = $this->checkState($params ['state'])) !== false) {
            $this->warehousesConf();
            $orders = $this->orders($params);
            if ($orders) {
                $orders = array_map(function($order) use ($key, $params) {
                    return $this->checkOrderWarehouseProcess($order, $key, $params);
                }, $orders);
                if ($orders) {
                    try {
                        // 记录被删除订单的订单   订单号 => 回执前的状态
                        $backOrder = null;
                        $orders = array_map(function($order) use ($key, &$backOrder) {
                            $logState = $order ['bwcOrderStatus'];
                            $platCd   = $order ['platCd'];
                            if ($order ['code'] == 2000) {
                                if ($this->updateMsOrd($order ['b5cOrderNo'], $order ['orderId'], self::backState() [$key], $logState, $platCd, $order['updatedTime'])) {
                                    $order ['msg'] = L('状态回退成功');
                                    // 订单退回至待派单时，删除订单并备份订单
                                    if ($key == static::STATIC_STATE_N001821000) {
                                        $backOrder [$order ['b5cOrderNo']]['state']  = $order ['wholeStatusCd'];
                                        $backOrder [$order ['b5cOrderNo']]['ordId']  = $order ['orderId'];
                                        $backOrder [$order ['b5cOrderNo']]['platCd'] = $order ['platCd'];
                                        $backOrder [$order ['b5cOrderNo']]['backState'] = $key;
                                    }
                                } else {
                                    $order ['code'] = 3000;
                                    $order ['msg']  = L('状态回退失败');
                                }
                            }
                            return $order;
                        }, $orders);
                    } catch (\Exception $e) {
                        $orders = L($e->getMessage());
                    }
                    // 需删除的订单
                    if (!empty($backOrder)) {
                        $model = new OrdBakModel();
                        $r = $model->backups(array_keys($backOrder));
                        // 接口删除订单失败的单子
                        $failOrder = null;
                        if ($r) {
                            foreach ($r ['data']['data'] as $key => $value) {
                                if ($value ['code'] != 2000) {
                                    $failOrder [] = $value ['orderId'];
                                    $orders [$value ['orderId']]['msg']  = $value ['msg'];
                                    $orders [$value ['orderId']]['code'] = $value ['code'];
                                }
                            }
                        } else {
                            // 未收到接口返回，状态全部回滚
                            foreach ($backOrder as $orderId => $value) {
                                $this->updateMsOrd($orderId, $value ['ordId'], self::backState() [$value ['backState']], $value ['state'], $value ['platCd'], null, '接口返回异常，状态回滚');
                            }
                        }
                        if (!empty($failOrder)) {
                            foreach ($failOrder as $key => $orderId) {
                                $this->updateMsOrd($orderId, $backOrder [$orderId]['ordId'], $backOrder [$orderId]['state'], self::backState() [$backOrder [$orderId]['backState']], $backOrder [$orderId]['platCd'], null,  L('接口返回异常，状态回滚'));
                            }
                        }
                    }
                }
                RedisLock::unlock();
            } else {
                foreach ($params ['ordId'] as $key => $value) {
                    $tmp ['code'] = 3000;
                    $tmp ['msg']  = L('订单未查询到');
                    $tmp ['b5cOrderNo'] = $value;
                    $orders [] = $tmp;
                    $tmp = null;
                }
            }
        }

        $response ['code'] = 2000;
        $response ['info'] = 'success';
        $response ['data']['pageData'] = $orders;

        return $response;
    }

    /**
     * 检查回退的状态是否可用
     * @param string $state 退回的状态
     * @return bool
     */
    public function checkState($state)
    {
        if ($key = array_keys(self::backState(), $state, true))
            return $key [0];

        return false;
    }

    /**
     * 检查每个订单仓库是否满足流程回退
     * @param array $order 订单
     * @param int   $key   回退的流程序号
     * @param array   $key   请求参数
     * @return array
     */
    public function checkOrderWarehouseProcess($order, $key, $params)
    {
        if (!RedisLock::lock($order['orderId'] . '_' . $order['platCd'], 20)) {
            $order ['code'] = 3000;
            $order['msg'] = L('订单锁获取失败');
        } elseif (isset($params['from']) && $order['wholeStatusCd'] != $params['from']) {
            $order['code'] = 3000;
            $order['msg'] = L('派单状态已修改，请刷新后重试');
        } elseif ($key == static::STATIC_STATE_N001821000) {
            $order ['code'] = 2000;
            return $order;
        } elseif ((array_keys(self::backStateExtend(), $order ['wholeStatusCd'])[0] > $key) and in_array(self::backState() [$key], $this->warehouses [$order ['warehouse']]['jobContent'])) {
            $order ['code'] = 2000;
        } elseif (!in_array(self::backState() [$key], $this->warehouses [$order ['warehouse']]['jobContent'])) {
            $order ['code'] = 3000;
            $order ['msg']  = L('仓库未配置') . L(self::warehouseProcessNm() [self::backState() [$key]]) . L('流程');
        } else {
            $order ['code'] = 3000;
            $order ['msg']  = L('该订单不可回退至：') . L(self::stateNm() [self::backState() [$key]]);
        }

        return $order;
    }

    /**
     * 获取仓库配置信息
     */
    public function warehousesConf()
    {
        $model = new WarehouseModel();
        $fields = [
            'CD as cd',
            'job_content as jobContent'
        ];
        $warehouses = $model
            ->field($fields)
            ->select();
        foreach ($warehouses as $key => $value) {
            $value ['jobContent'] = explode(':', $value ['jobContent']);
            $value ['jobContent'] = array_map(function($process) {
                if ($state = self::mappingProcessState() [$process])
                    return $state;
                else
                    return false;
            }, $value ['jobContent']);
            $value ['jobContent'] = array_filter($value ['jobContent'], function ($process) {
                if ($process)
                    return true;
                else
                    return false;
            });
            $this->warehouses [$value ['cd']] = $value;
        }
    }

    /**
     * 获取订单
     * @param array $params 请求参数
     * @return array
     */
    public function orders($params)
    {
        $esModel = new EsSearchModel();
        $q = $esModel
            ->page(0, count($params ['ordId']))
            ->setMissing(['and', ['childOrderId']])
            ->where(['msOrd.ordId' => ['and', $params ['ordId']]])
            ->getQuery();
        $esClient = new ESClientModel();
        $esData = $esClient->search($q)['hits']['hits'];

        return $this->orderDataModel($esData);
    }

    /**
     * 根据模型返回需要的数据
     * @param $r
     * @return array
     */
    public function orderDataModel($r)
    {
        foreach ($r as $key => $value) {
            $esData = $value ['_source'];
            $pageData [$esData ['b5cOrderNo']] = [
                'warehouse'         => $esData ['warehouse'], //仓库
                'wholeStatusCd'     => $esData ['msOrd'][0]['wholeStatusCd'], //当前订单整体状态
                'b5cOrderNo'        => $esData ['b5cOrderNo'], //b5c订单号
                'orderId'           => $esData ['orderId'],
                'bwcOrderStatus'    => $esData ['bwcOrderStatus'],
                'platCd'            => $esData ['platCd'],
                'updatedTime'       => $esData['msOrd'][0]['updatedTime'],
                'surfaceWayGetCd'   => $esData['surfaceWayGetCd'],
            ];
        }

        return $pageData;
    }

    /**
     * 更新派单状态
     * @param string $ordId   待更新的订单
     * @param string $orderId 第三方订单号
     * @param string $state   待更新的状态
     * @return bool
     */
    public function updateMsOrd($ordId, $orderId, $state, $logState, $platCd, $updatedTime, $msg = '派单状态回退至')
    {
        $model = new TbMsOrdModel();
        if ($ordId) {
            $model->startTrans();
            $conditions ['tb_ms_ord.ORD_ID'] = ['in', $ordId];
            $updatedTime and $conditions['updated_time'] = $updatedTime;
            $ret ['WHOLE_STATUS_CD']            = $state;
            $ret ['S_TIME1']                    = null;
            if ($model->table('tb_ms_ord')->where($conditions)->save($ret)) {
                $log ['ORD_NO']             = $orderId;
                $log ['ORD_HIST_SEQ']       = time();
                $log ['ORD_STAT_CD']        = $logState;
                $log ['ORD_HIST_WRTR_EML']  = $_SESSION['m_loginname'];
                $log ['ORD_HIST_REG_DTTM']  = date('Y-m-d H:i:s', time());
                $log ['ORD_HIST_HIST_CONT'] = L($msg) . self::stateNm() [$state];
                $log ['plat_cd']            = $platCd;
                $saveLog [] = $log;
                if (SmsMsOrdHistModel::writeMulHist($saveLog)) {
                    $model->commit();
                    return true;
                } else {
                    $model->rollback();
                    return false;
                }
            } else {
                $model->rollback();
                return false;
            }
        }

        return false;
    }

    /**
     * 打印拣货单，打印分拣单，核单，出库/批量发货/称重发货前订单状态校验&处理
     */
    public function preCheckAndDone($params)
    {
        $orders = [];
        if ($params ['ordId']) {
            $orders = $this->orders($params);
        } elseif ($params['qrCode']) {
            $q = (new EsSearchModel())
                ->where(['ordPackage.trackingNumber' => ['or', $params['qrCode']]])
                ->where(['b5cOrderNo' => ['or', $params['qrCode']]])
                ->where(['orderId' => ['or', $params['qrCode']]])
                ->setMissing(['and', ['childOrderId']])
                //->where(['msOrd.wholeStatusCd' => ['and', 'N001820700']])
                ->getQuery();

            $orders = $this->orderDataModel((new ESClientModel())->search($q)['hits']['hits']);
        }
        $orders_cancel_close = array_filter($orders, function ($v){
            return in_array($v['bwcOrderStatus'], ['N000550900', 'N000551000']);//关闭，取消
        });
        $orders_topay = array_filter($orders, function ($v){
            return in_array($v['bwcOrderStatus'], ['N000550300']);//待付款
        });
        if ($orders_cancel_close || $orders_topay) {
            if ($params['preDone'] == 1) {
                $this->backPatchStatus($orders_topay);//库存占用释放&派单状态更新为待派单
                $this->backCancleStatus($orders_cancel_close);//库存占用释放&派单状态更新为订单取消
                $count = count($orders);
                $ret = ['total' => $count, 'fail' => count($orders_cancel_close) + count($orders_topay)];
                $ret['success'] = $ret['total'] - $ret['fail'];
                $ret['info'] = [];
                foreach ($orders_cancel_close as $v) {
                    $ret['info'][] = ['ordId' => $v['b5cOrderNo'], 'err' => $v['bwcOrderStatus'] == 'N000550900' ? L('订单已关闭') : L('订单已取消')];
                }
                foreach ($orders_topay as $v) {
                    $ret['info'][] = ['ordId' => $v['b5cOrderNo'], 'err' => L('订单待付款')];
                }
                return ['code' => 2000, 'msg' => L('处理完成'), 'data' => $ret];
            } else {
                if ($orders_cancel_close && $orders_topay) {
                    return ['code' => 3000, 'msg' => L('订单已取消/关闭/待付款，是否结束当前流程释放占用库存？'), 'data' => []];
                } elseif ($orders_cancel_close) {
                    return ['code' => 3000, 'msg' => L('订单已取消/关闭，是否结束当前流程释放占用库存？'), 'data' => []];
                } else {
                    return ['code' => 3000, 'msg' => L('订单未付款，是否结束当前流程释放占用库存？'), 'data' => []];
                }
            }
        }
        return ['code' => 2000, 'msg' => L('检查通过'), 'data' => []];
    }

    public function backPatchStatus(array $orders_topay)
    {
        if ($orders_topay) {
            try {
                $params['ordId'] = array_column($orders_topay, 'b5cOrderNo');
                $params['state'] = 'N001821000';//待派单
                $orderBackModel = new OrderBackModel();
                return $orderBackModel->main($params);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function backCancleStatus(array $orders_cancel_close)
    {
        $ret = $this->backPatchStatus($orders_cancel_close);
        if ($ret) {
            try {
                $conditions ['tb_ms_ord.ORD_ID'] = ['in', array_column($orders_cancel_close, 'b5cOrderNo')];
                $ret ['WHOLE_STATUS_CD'] = 'N001821100';
                return (new TbMsOrdModel())->table('tb_ms_ord')->where($conditions)->save($ret);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
}