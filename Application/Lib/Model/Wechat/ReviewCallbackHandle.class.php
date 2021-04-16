<?php
/**
 * User: yangsu
 * Date: 2019/3/22
 * Time: 10:03
 */

@import("@.Action.Scm.DemandAction");
@import("@.Action.Scm.ScmBaseAction");

class ReviewCallbackHandle
{
    public static $res = [
        'code' => 200,
        'msg' => 'success',
        'data' => [],
    ];

    /**
     * @param $params
     * @param $Model
     * @param $res
     *
     * @return mixed
     */
    private static function checkRequestReviewData($params, $Model, $res)
    {
        $ret = $Model->table('tb_sell_demand')->where(['id' => $params ['review']['order_id']])->find();
        if (empty($ret) || is_null($params['status'])) {
            $res['code'] = 3000;
            $res['msg'] = '无效参数';
        }
        return $res;
    }

    private function baseHandle($params)
    {

    }

    private function checkRequestData()
    {

    }

    public function salesLeadershipApproval($params)
    {
        return self::scmApproval($params, 'salesLeadershipApproval');
    }

    public function ceoApproval($params)
    {
        return self::scmApproval($params, 'ceoApproval');
    }

    public static function scmApproval($params, $type)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_sell_demand')->where(['id' => $params['review']['order_id']])->find();
            if (empty($ret) || is_null($params['status'])) {
                throw new Exception(L('无效参数'), 3000);
            }
            $temp_data['id'] = $params['review']['order_id'];
            $temp_data['status'] = $params['status'];
            $demand = $Model->table('tb_sell_demand')
                ->field('step,status')
                ->where(['id' => $params['review']['order_id']])
                ->find();
            switch ($type) {
                case 'salesLeadershipApproval':
                    if ('N002120700' !== $demand['step'] || ('N002120700' == $demand['step'] && 'N002130300' !== $demand['status'])) {
                        throw new Exception(L('单号已被处理'), 3000);
                    }
                    $temp_data['remark'] = $params['reason'];
                    $temp_res = (new DemandAction())->seller_leader_approve($temp_data);
                    $key = 'seller_leader_approve';
                    break;
                case 'ceoApproval':
                    if ('N002120800' !== $demand['step'] || ('N002120800' == $demand['step'] && 'N002130300' !== $demand['status'])) {
                        throw new Exception(L('单号已被处理'), 3000);
                    }
                    $temp_res = (new DemandAction())->ceo_approve($temp_data);
                    $key = 'ceo_approve';
                    break;
            }

            if (false === $temp_res) {
                $res['code'] = 5000;
                $res['msg'] = '审批操作失败';
            } else {
                (new ScmBaseAction())->logBegin($key, $temp_data['id']);
                if ($params['status'] == 1) {
                    $res['wechat'] = ['receipt' => L('您已同意需求编号') . '：' . $params ['review']['order_no'] . L('的申请')];
                } elseif ($params['status'] == 0) {
                    $res['wechat'] = ['receipt' => L('您已拒绝需求编号') . '：' . $params ['review']['order_no'] . L('的申请')];
                } else {
                    $res['code'] = 3000;
                    $res['msg'] = '无效参数';
                }
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }

    /**
     * 划转审核回调方法
     *
     * @param $params
     *
     * @return array
     */
    public static function transferApproval($params)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_fin_account_transfer')->where(['id' => $params['review']['order_id']])->find();
            if (empty($ret) || is_null($params['status'])) {
                throw new Exception(L('无效参数'), 3000);
            }
            $temp_data['id'] = $params['review']['order_id'];
            $temp_data['agree'] = $params['status'];
            if ($params['status'] == 1) {
                $temp_data['comment'] = $params['reason'];
                $temp_data['auditReason'] = '';
            } else if ($params['status'] == 0) {
                $temp_data['comment'] = $params['reason'];
                $temp_data['auditReason'] = $params['reason'];
            } else {
                throw new Exception(L('未知审核状态'), 3000);
            }
            if (!$params['reason']) {
                throw new Exception(L('请填写批注'), 3000);
            }
            $temp_res = (new FinanceAction())->audit($temp_data);
            $key = 'transfer_approval';

            if (2000 !== $temp_res['code']) {
                $res['code'] = $temp_res['code'];
                $res['msg'] = $temp_res['msg'];
            } else {
                $send_emails = [];
                $temp_language = [];
                $init_language = LanguageModel::getCurrent();
                foreach ($params['review']['allowed_man_json'] as $v) {
                    $send_emails[] = $v . '@gshopper.com';
                }
                $cards_key_val = TbHrCardModel::getCardWorkPalce($send_emails);
                foreach ($send_emails as $value) {
                    $temp_language = ReviewMsg::getPathToLang($cards_key_val[$value]);
                    if ($init_language != $temp_language) {
                        LanguageModel::setCurrent($temp_language);
                    }
                }
                if ($params['status'] == 1) {
                    $res['wechat'] = ['receipt' => L('您已同意资金划转编号') . '：' . $params ['review']['order_no'] . L('的申请')];
                } elseif ($params['status'] == 0) {
                    $res['wechat'] = ['receipt' => L('您已拒绝资金划转编号') . '：' . $params ['review']['order_no'] . L('的申请')];
                }
//                Logs(json_encode($res));
                if ($init_language != $temp_language) {
                    LanguageModel::setCurrent($init_language);
                }
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }

    public static function newTransferApproval($params)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_wms_allo')->where(['id' => $params['review']['id']])->find();
            if (empty($ret) || is_null($params['status'])) {
                throw new Exception(L('无效参数'), 3000);
            }
            $temp_data['id'] = $params['review']['id'];
            $temp_data['type'] = $params['status'];

            $AllocationExtendNewService = new AllocationExtendNewService();
            $res['data'] = $AllocationExtendNewService->updateReviewAllo($temp_data['id'], $temp_data);
            if ($params['status'] == 1) {
                $res['wechat'] = ['receipt' => L('您已同意调拨单号') . '：' . $params ['review']['order_no'] . L('的调拨申请')];
            } elseif ($params['status'] == 0) {
                $res['wechat'] = ['receipt' => L('您已拒绝调拨单号') . '：' . $params ['review']['order_no'] . L('的调拨申请')];
            } else {
                $res['code'] = 3000;
                $res['msg'] = '无效参数';
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }

    /**
     * 一般付款审核回调方法
     *
     * @param $params
     *
     * @return array
     */
    public static function paymentMessageNotice($params)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_pur_payment_audit')->where(['id' => $params['review']['id']])->find();
            if (empty($ret) || is_null($params['status'])) {
                throw new Exception(L('无效参数'), 3000);
            }

            if ($params['status'] == 1) {
                $res['wechat'] = ['receipt' => L('您已同意付款单号') . '：' . $params ['review']['order_no'] . L('的申请')];
            } elseif ($params['status'] == 0) {
                $res['wechat'] = ['receipt' => L('您已拒绝付款单号') . '：' . $params ['review']['order_no'] . L('的申请')];
            } else {
                $res['code'] = 3000;
                $res['msg'] = '无效参数';
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }

    /**
     * 售后申请审核回调方法
     *
     * @param $params
     *
     * @return array
     */
    public static function afterSalesAudit($params)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_op_order_refund')->where(['id' => $params['review']['id']])->find();
            if (empty($ret) || is_null($params['status'])) {
                throw new Exception(L('无效参数'), 3000);
            }

            if ($params['status'] == 1) {
                $res['wechat'] = ['receipt' => L('您已同意售后单号') . '：' . $params ['review']['after_sale_no'] . L('的申请')];
            } elseif ($params['status'] == 0) {
                $res['wechat'] = ['receipt' => L('您已拒绝售后单号') . '：' . $params ['review']['after_sale_no'] . L('的申请')];
            } else {
                $res['code'] = 3000;
                $res['msg'] = '无效参数';
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }

    public static function attributionTransferApproval($params)
    {
        try {
            $res = self::$res;
            $res['code'] = 2000;
            $Model = new \Model();
            $ret = $Model->table('tb_wms_allo_attribution')->where(['id' => $params['review']['id']])->find();
            if (empty($ret) || is_null($params['status'] || !in_array($params['status'], [0, 1]))) {
                throw new Exception(L('无效参数'), 3000);
            }
            $temp_data['id'] = $params['review']['id'];
            switch ($params['status']) {
                case 0:
                    $temp_data['review_type_cd'] = 'N003000003';
                    break;
                case 1:
                    $temp_data['review_type_cd'] = 'N003000002';
                    break;
            }
            $AllocationExtendAttributionService = new AllocationExtendAttributionService();
            $res['data'] = $AllocationExtendAttributionService->approval($temp_data);
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
            ];
        }
        return $res;
    }
}