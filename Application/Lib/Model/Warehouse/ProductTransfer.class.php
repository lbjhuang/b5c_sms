<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/4/11
 * Time: 13:31
 */

require_once APP_PATH.'Lib/Logic/Purchase/WarehouseLogic.class.php';
class ProductTransfer
{
    public static function getApproveMans($sales_team)
    {
        $email = M('cd', 'tb_ms_cmn_')
            ->where(['CD' => $sales_team])
            ->getField('ETC');
        return array_map(function ($v) {
            return explode('@', $v)[0];
        }, explode(',', $email));
    }

    public static function getApproveManEmail($sales_team)
    {
        $email = M('cd', 'tb_ms_cmn_')
            ->where(['CD' => $sales_team])
            ->getField('ETC');
        return explode(',', $email);
    }

    /**
     * @param $trans
     * @param $products
     * @throws Exception
     */
    public static function approveMsg($trans, $products) {
        $send_mans = self::getApproveManEmail($trans['sales_team_cd']);
        $init_language = LanguageModel::getCurrent();
        $cards_key_val = TbHrCardModel::getCardWorkPalce($send_mans);
        $temp_language = ReviewMsg::getPathToLang($cards_key_val[$send_mans[0]]);
        LanguageModel::setCurrent($temp_language);
        $trans['type'] = cdVal($trans['type_cd']);
        $trans['sales_team'] = cdVal($trans['sales_team_cd']);
        $trans['warehouse'] = cdVal($trans['warehouse_cd']);
        $new_products = [];
        foreach ($products as $v) {
            if ($new_products[$v['sku_id']]) {
                $new_products[$v['sku_id']]['number'] += $v['number'];
            } else {
                $new_products[$v['sku_id']] = [
                    'sku_id' => $v['sku_id'],
                    'number' => $v['number']
                ];
            }
        }
        foreach ($trans as &$v) {
            if ($v && !is_array($v)) {
                $v = L($v);
            }
        }
        unset($v);
        $new_products = SkuModel::getInfo(array_values($new_products), 'sku_id', ['spu_name', 'attributes', 'image_url']);
        $review = [
            'review_type' => 'SCM',
            'order_id' => $trans['id'],
            'order_no' => $trans['conversion_no'],
            'allowed_man_json' => DataModel::emailToUser($send_mans),
            'detail_json' => [
                'detail' => $trans,
                'products' => $new_products,
                'config' => [
                    'view_type' => 'product_transfer',
                    'can_approve' => 1,
                ]
            ],
            'callback_function' => 'product_transfer',
        ];
        $msg = [
            'tousers' => $send_mans,
            'textcard' => [
                'title' => L('正次品转换审批'),
                'description' => '<div class="normal">' . L('转换单号'). '：' . $trans['conversion_no'] .'</div><br/>'
                    .'<div class="normal">' . L('转换类型'). '：' . L($trans['type']) .'</div><br/>'
                    .'<div class="normal">' . L('归属销售团队'). '：' . L($trans['sales_team']) .'</div><br/>'
                    .'<div class="normal">' . L('归属仓库'). '：' . L($trans['warehouse']) .'</div><br/>'
                    .'<div class="normal">' . L('发起人'). '：' . $trans['created_by'] .'</div><br/>'
                    .'<div class="normal">' . L('发起时间'). '：' . $trans['created_at'] .'</div>'
                ,
                'btntxt' => L('查看详情')
            ]
        ];
        LanguageModel::setCurrent($init_language);
        $msgSender = new ReviewMsg();
        if (!$msgSender->create($review)->send($msg)) {
            throw new \Exception($msgSender->getError());
        }
    }

    public static function wechatApprove($trans)
    {
        $trans['id'] = $trans ['review']['order_id'];
        $res = self::approve($trans);
        if ($res && $res['code'] == 2000) {
            if ($trans['status']) {
                $msg1 = L('您已同意转换单号');
            } else {
                $msg1 = L('您已拒绝转换单号');
            }
            $res['wechat'] = ['receipt' => "{$msg1}：{$trans ['review']['order_no']}" . L('的申请')];
        }
        return $res;
    }

    /**
     * 审批接口
     * @param $params
     * @return array
     */
    public static function approve($params)
    {
        $code = 3000;
        $msg = 'error';
        M()->startTrans();
        $order = M('conversion', 'tb_scm_')
            ->where(['id' => $params['id']])
            ->lock(true)
            ->find();
        if ($order) {
            if (strval($order['affect_supplier_settlement']) === '1' && strval($params['status']) === '1') { // 本次转换同步更新采购单（并且审核通过）才需要生成应付/抵扣记录
                // 获取具体的数量和stream_id
                $order_detail = M('conversion_details', 'tb_scm_')
                    ->field('stream_id, number')
                    ->where(['conversion_id' => $params['id']])
                    ->select();
                $addDataInfo = [];
                $addDataInfo['detail'] = $order_detail;
                $addDataInfo['clause_type'] = '5';
                $addDataInfo['class'] = __CLASS__;
                $addDataInfo['function'] = __FUNCTION__;
                if ($order['type_cd'] === 'N002720001') { // 正转次
                    $type = '2';
                    $operation_cd = 'N002870011';
                }
                if ($order['type_cd'] === 'N002720002') { // 次转正
                    $type = '1';
                    $operation_cd = 'N002870012';
                }
                $op_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, $type, $operation_cd, '', $order['conversion_no']);
                if (!$op_res) {
                    M()->rollback();
                    $msg = L('应付或抵扣记录生成失败');
                    return ['code' => $code, 'msg' => $msg, 'data' => ''];
                }
            }
            $approveMans = self::getApproveMans($order['sales_team_cd']);
            if ($order['status_cd'] != 'N002750001') {
                $msg = L('订单状态错误');
//            } else if (!in_array($_SESSION['m_loginname'], $approveMans)) {
//                $msg = L('没有审批权限');
            } else {
                $status = $params['status'] ? 'N002750002' : 'N002750003';
                $res = M('conversion', 'tb_scm_')
                    ->where(['id' => $params['id']])
                    ->save([
                        'status_cd' => $status,
                        'approval_reason' => $params['reason'],
                        'approval_by' => $_SESSION['m_loginname'],
                        'approval_at' => date('Y-m-d H:i:s'),
                        'updated_by' => $_SESSION['m_loginname'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                if ($res) {
                    $type = cdVal($order['type_cd']);
                    if ($params['status']) {
                        // 完结转换方法
                        //修改消息状态
                        M('reviews', 'tb_sys_')
                            ->where(['order_id' => $params['id'], 'order_no' => $order['conversion_no']])
                            ->save([
                                'review_status' => 1,
                                'review_by' => $_SESSION['m_loginname'],
                                'review_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['m_loginname'],
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        //采购单处理方法
                        if (!$order['affect_supplier_settlement'] || self::purConvertHandle($order['id'], $order['type_cd'])) {
                            $wechatMsg = L('转换单号') . '：' . $order['conversion_no']
                                . L("的{$type}申请已经审批通过");
                        } else {
                            M()->rollback();
                            $msg = L('采购单处理失败');
                            return ['code' => $code, 'msg' => $msg, 'data' => ''];
                        }

                        $data = ['operatorId' => $_SESSION['userId'], 'orderId' => $order['conversion_no'], 'type' => $order['type_cd']];
                        $res = (new WmsModel())->productTransferOver($data);
                        if ($res && $res['code'] == 2000) {
                            $code = 2000;
                            $msg = 'success';
                            M()->commit();
                        } else {
                            M()->rollback();
                            $msg = L('出入库操作失败') . '：' . $res['msg'];
                            return ['code' => $code, 'msg' => $msg, 'data' => ''];
                        }
                    } else {
                        // 调用完结转回退方法
                        $data = [['platCd' => 'N000830100', 'orderId' => $order['conversion_no']]];
                        $res = (new WmsModel())->productTransferAbandon($data);
                        if ($res && $res['code'] == 2000) {
                            //修改消息状态
                            M('reviews', 'tb_sys_')
                                ->where(['order_id' => $params['id'], 'order_no' => $order['conversion_no']])
                                ->save([
                                    'review_status' => 2,
                                    'review_by' => $_SESSION['m_loginname'],
                                    'review_at' => date('Y-m-d H:i:s'),
                                    'updated_by' => $_SESSION['m_loginname'],
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                            $code = 2000;
                            $msg = 'success';
                            M()->commit();
                            $wechatMsg = L('转换单号') . '：' . $order['conversion_no']
                                . L("的{$type}申请被拒绝") . "\n"
                                . L('原因') . "：\n"
                                . $params['reason'];
                        } else {
                            M()->rollback();
                            $msg = L('库存释放失败') . '：' . $res['msg'];
                        }
                    }
                    if (isset($wechatMsg)) {
                        // 发送结果提醒消息
                        $wechat = new WechatMsg();
                        $wechat->create([
                            'tousers' => [$order['created_by'] . '@gshopper.com'],
                            'msgtype' => 'text',
                            'appName' => 'ERP',
                            'text' => [
                                'content' => $wechatMsg
                            ],
                        ]);
                        $wechat->send();
                    }
                } else {
                    M()->rollback();
                    $msg = L('修改转换单状态失败') . '：' . M()->getError();
                }
            }
        } else {
            M()->rollback();
            $msg = L('参数异常');
        }
        return ['code' => $code, 'msg' => $msg, 'data' => ''];
    }

    public static function revoke($params)
    {
        M()->startTrans();
        $code = 3000;
        $msg = 'error';
        $order = M('conversion', 'tb_scm_')
            ->where(['id' => $params ['id']])
            ->lock(true)
            ->find();
        if (!$order || $order['status_cd'] != 'N002750001') {
            $msg = L('订单状态错误');
        } else {
            $res = M('conversion', 'tb_scm_')
                ->where(['id' => $params['id']])
                ->save([
                    'status_cd' => 'N002750004',
                    'updated_by' => $_SESSION['m_loginname'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            if ($res) {
                // 调用撤回转换单释放方法
                $data = [['platCd' => 'N000830100', 'orderId' => $order['conversion_no']]];
                $res = (new WmsModel())->productTransferAbandon($data);
                if ($res && $res['code'] == 2000) {
                    //修改消息状态
                    M('reviews', 'tb_sys_')
                        ->where(['order_id' => $params['id'], 'order_no' => $order['conversion_no']])
                        ->save([
                            'review_status' => 3,
                            'updated_by' => $_SESSION['m_loginname'],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    $code = 2000;
                    $msg = 'success';
                    M()->commit();
                    // 发送结果提醒消息
                    $wechat = new WechatMsg();
                    $wechat->create([
                        'tousers' => self::getApproveManEmail($order['sales_team_cd']),
                        'msgtype' => 'text',
                        'appName' => 'ERP',
                        'text' => [
                            'content' => L('转换单号') . '：' . $order['conversion_no']
                                . L("的申请已撤回")
                        ],
                    ]);
                    $wechat->send();
                } else {
                    M()->rollback();
                    $msg = L('转换单库存释放失败') . '：' . $res['msg'];
                }
            } else {
                $msg = '修改转换单状态失败';
            }
        }
        return ['code' => $code, 'msg' => L($msg), 'data' => ''];
    }

    /**
     * 商品转换完结后采购单处理
     * @param $id
     * @param $type
     */

    public static function purConvertHandle($id, $type)
    {
        $data = M('conversion_details', 'tb_scm_')->alias('pd')
            ->field(['bi.bill_id','pd.sku_id', 'pd.number', "'$type' as type"])
            ->join('left join tb_wms_batch ba on ba.id=pd.batch_id')
            ->join('left join tb_wms_bill bi on bi.id=ba.bill_id')
            ->where(['pd.conversion_id' => $id])
            ->select();
        $ret = (new WarehouseLogic())->goodsConversion($data);
        ZUtils::saveLog(['req' => $data, 'res' => $ret], '商品转换完结后采购单处理');
        return $ret;
    }
}