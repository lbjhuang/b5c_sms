<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/4/19
 * Time: 14:40
 */

class OrderOneKeyThroughModel extends Model
{
    protected $trueTableName = '';

    protected $warehouses;
    public $patch_type = false;

    public function __construct()
    {

    }

    /**
     * 一键通过可前进的状态
     * 可前进的状态集合，其中 key 值作为流程顺序
     * @return array
     */
    public static function throughState()
    {
        return [
            'N001820200' => 'N001820500',
            'N001820500' => 'N001820600',
            'N001820600' => 'N001820700',
            'N001820700' => 'N001820800'
        ];
    }

    /*
     * 可进行一键通过的状态
     * 订单处于待拣货、待分拣、待核单状态下可状态
     *
     */
    public static function throughStateExtend()
    {
        return [
            'N001820200' => 'N001820200',
            'N001820500' => 'N001820500',
            'N001820600' => 'N001820600',
            'N001820700' => 'N001820700'
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
            'N001820200' => L('派单'),
            'N001820500' => L('待拣货'),
            'N001820600' => L('待分拣'),
            'N001820700' => L('待核单'),
            'N001820800' => L('待发货')
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
            'N001820700' => L('核单')
        ];
    }

    /**
     * 主流程
     * @param $params
     * @return array
     */
    public function main($params)
    {
        if (!empty($params ['ordId'])) {
            $this->warehousesConf();
            if($this->patch_type){
                $orders = $this->ordersPatch($params ['ordId']);
            }else{
                $orders = $this->orders($params);
            }

            if ($orders) {
                $orders = array_map(function($order) use ($params) {
                    return $this->checkOrderWarehouseProcess($order, $params);
                }, $orders);
                if ($orders) {
                    try {
                        $orders = array_map(function($order) {
                            $state = $this->forwardState($order ['warehouse'], $order ['wholeStatusCd']);
                            $logState = $order ['bwcOrderStatus'];
                            if ($order ['code'] == 2000) {
                                if ($this->updateMsOrd($order, $state, $logState)) {
                                    $order ['msg'] = L('一键通过成功');
                                } else {
                                    $order ['code'] = 3000;
                                    $order ['msg']  = L('一键通过失败');
                                }
                            }
                            return $order;
                        }, $orders);
                    } catch (\Exception $e) {
                        $orders = L($e->getMessage());
                    }
                }
                RedisLock::unlock();
            }
        } else {
            foreach ($params ['ordId'] as $key => $value) {
                $tmp ['code'] = 3000;
                $tmp ['msg']  = L('订单未查询到');
                $tmp ['b5cOrderNo'] = $value;
                $orders [] = $tmp;
                $tmp = null;
            }
        }
        $response ['code'] = 2000;
        $response ['info'] = 'success';
        $response ['data']['pageData'] = $orders;

        return $response;
    }

    /**
     * 一键通过的下一次状态
     * @param string $warehouse    仓库
     * @param string $currentState 当前状态
     * @return bool
     */
    public function forwardState($warehouse, $currentState)
    {
        //获得仓库配置的流程
        $process = $this->warehouses [$warehouse]['jobContent'];
        $nextState = self::throughState() [$currentState];
        if (!$nextState) {
            $state = array_pop(self::throughState());
            return $state;
        }
        if (in_array($nextState, $process)) {
            return $nextState;
        } else {
            return $this->forwardState($warehouse, $nextState);
        }
    }

    /**
     * 检查每个订单的仓库流程
     * @param array $order 订单
     * @param array $params 请求参数
     * @return array
     */
    public function checkOrderWarehouseProcess($order, $params)
    {
        if (!RedisLock::lock($order['orderId'] . '_' . $order['platCd'], 20)) {
            $order ['code'] = 3000;
            $order['msg'] = L('订单锁获取失败');
        } elseif (isset($params['from']) && $order['wholeStatusCd'] != $params['from']) {
            $order['code'] = 3000;
            $order['msg'] = L('派单状态已修改，请刷新后重试');
        } elseif (in_array($order ['wholeStatusCd'], self::throughStateExtend())) {
            $order ['code'] = 2000;
        } else {
            $order ['code'] = 3000;
            $order ['msg']  = L('该订单当前状态不可一键通过');
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

    public function ordersPatch($orders)
    {
        $Model = M();
        $where['t1.ORD_ID'] = array('in',$orders);
        $res = $Model->table('tb_ms_ord as t1,tb_op_order as t2')
            ->field('t2.warehouse,t1.whole_status_cd,t1.ORD_ID,t2.ORDER_ID,t2.BWC_ORDER_STATUS,t2.PLAT_CD')
            ->where($where)
            ->where('t2.ORDER_ID = t1.THIRD_ORDER_ID')
            ->select();
        foreach ($res as $key => $value) {
            $pageData [] = [
                'warehouse'     => $value['warehouse'], //仓库
                'wholeStatusCd' => $value['whole_status_cd'], //当前订单整体状态
                'b5cOrderNo'    => $value['ORD_ID'], //b5c订单号
                'orderId'       => $value['ORDER_ID'],
                'bwcOrderStatus'=> $value['BWC_ORDER_STATUS'],
                'platCd'        => $value['PLAT_CD']
            ];
        }

        return $pageData;
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
            $pageData [] = [
                'warehouse'     => $esData ['warehouse'], //仓库
                'wholeStatusCd' => $esData ['msOrd'][0]['wholeStatusCd'], //当前订单整体状态
                'b5cOrderNo'    => $esData ['b5cOrderNo'], //b5c订单号
                'orderId'       => $esData ['orderId'],
                'bwcOrderStatus'=> $esData ['bwcOrderStatus'],
                'platCd'        => $esData ['platCd'],
                'updatedTime'   => $esData['msOrd'][0]['updatedTime'],
            ];
        }

        return $pageData;
    }

    /**
     * 更新派单状态
     * @param array $order   待更新的订单
     * @param string $state   待更新的状态
     * @param string $logState写入日志的状态
     * @return bool
     */
    public function updateMsOrd($order, $state, $logState)
    {
        $model = new TbMsOrdModel();
        if ($order) {
            $model->startTrans();
            $conditions ['tb_ms_ord.ORD_ID'] = ['in', $order ['b5cOrderNo']];
            // 派单状态不能为已出库
            $conditions ['tb_ms_ord.WHOLE_STATUS_CD'] = ['neq', 'N001820900'];
            $order['updatedTime'] and $conditions ['tb_ms_ord.updated_time'] = $order['updatedTime'];
            $ret ['WHOLE_STATUS_CD'] = $state;
            $ret ['S_TIME1']         = null;
            if ($model->table('tb_ms_ord')->where($conditions)->save($ret)) {
                $log ['ORD_NO']             = $order ['orderId'];
                $log ['ORD_HIST_SEQ']       = time();
                $log ['ORD_STAT_CD']        = $logState;
                $log ['ORD_HIST_WRTR_EML']  = $_SESSION['m_loginname'];
                $log ['ORD_HIST_REG_DTTM']  = date('Y-m-d H:i:s', time());
                $log ['ORD_HIST_HIST_CONT'] = L('派单状态由：') . '[' . self::stateNm() [$order ['wholeStatusCd']] . ']' . $this->isOneKeyThrough() . '[' .self::stateNm() [$state] . ']';
                $log ['plat_cd']            = $order ['platCd'];
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
     * 是否是一键通过
     * @var bool
     */
    public $requestType = true;
    private function isOneKeyThrough()
    {
        if ($this->requestType)
            return L('一键通过至');
        else
            return L('至');
    }
}