<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/3/8
 * Time: 15:03
 */

@import("@.Model.Wechat.ReviewCallbackHandle");

class ReviewCallback
{
    /**
     * 回调方法填充
     * @return array
     */
    public function getCbMap()
    {

        /**
         * @see AllocationExtendModel::wechat_exam 调拨审批 test
         * @see TbWmsAlloModel::weChatApprove 调拨审批注册
         * @see ReviewCallBackHandle::salesLeadershipApproval 销售领导 scm审批
         * @see ReviewCallBackHandle::ceoApproval ceo scm 审批
         * @see ReviewCallBackHandle::newTransferApproval 新调拨审批
         * @see ProductTransfer::wechatApprove 正次品转换 审批
         */
        return [
            'db_exam' => 'AllocationExtendModel::wechat_exam',
            'weChatApprove' => 'TbWmsAlloModel::weChatApprove',
            'sales_leadership_approval' => 'ReviewCallbackHandle::salesLeadershipApproval',
            'ceo_approval' => 'ReviewCallbackHandle::ceoApproval',
            'transfer_approval' => 'ReviewCallbackHandle::transferApproval',
            'general_approval' => 'ReviewCallbackHandle::generalApproval',
            'payment_message_notice' => 'ReviewCallbackHandle::paymentMessageNotice',
            'after_sales_audit' => 'ReviewCallbackHandle::afterSalesAudit',
            'product_transfer' => 'ProductTransfer::wechatApprove',
            'new_transfer_approval' => 'ReviewCallBackHandle::newTransferApproval',
            'attribution_transfer_approval' => 'ReviewCallBackHandle::attributionTransferApproval',
        ];
    }

    /**
     * 回调业务审批处理逻辑
     * @param $cb string 回调方法名
     * @param $params array
     * @return array|mixed
     */
    public function callback($cb, $params)
    {
        $cb_map = $this->getCbMap();
        if (isset($cb_map[$cb])) {
            return call_user_func($cb_map[$cb], $params);
        } else {
            return ['code' => 3000, 'msg' => '回调方法异常', 'data' => ''];
        }
    }

}