<?php

/**
 * User: yangsu
 * Date: 19/08/26
 * Time: 11:31
 */


/**
 * Class AllocationExtendAttributionService
 */

class AllocationExtendAttributionService extends Service
{
    /**
     * @var AllocationExtendAttributionRepository
     */
    protected $repository;


    /**
     * AllocationExtendAttributionService constructor.
     *
     * @param null $external_model
     */

    public function __construct($external_model = null)
    {
        $this->repository = new AllocationExtendAttributionRepository($external_model);
    }

    public function index($data, $where = [])
    {
        $search_map = [
            'review_type_cd'  => 'tb_wms_allo_attribution.review_type_cd',
            'change_order_no' => 'tb_wms_allo_attribution.change_order_no',
            //'sku_id' => 'tb_wms_allo_attribution_sku.sku_id',
            'change_type_cd'  => 'tb_wms_allo_attribution.change_type_cd',
            'created_by'      => 'tb_wms_allo_attribution.created_by',
        ];
        if ($data['search']['created_at']) {
            $where['tb_wms_allo_attribution.created_at'] = ['LIKE', $data['search']['created_at'] . '%'];
        }
        //应审核人
        if ($data['search']['reviewer_by']) {
            $where['tb_wms_allo_attribution.reviewer_by'] = ['LIKE',  '%'. $data['search']['reviewer_by'] . '%'];
        }
        $search_accurate_arr = [
            'change_order_no',
            'created_by',
        ];
        list($where, $limit) = WhereModel::joinSearchTemp($data, $search_map, $where, $search_accurate_arr);
        if (isset($data['search']) && !empty($data['search']['sku_id'])) {
            $complex['p_sku.sku_id'] = $data['search']['sku_id'];
            $complex['p_sku.upc_id'] = $data['search']['sku_id'];
            $complex['_string']      = "FIND_IN_SET('{$data['search']['sku_id']}',p_sku.upc_more)";
            $complex['_logic']       = 'or';
            $where['_complex']       = $complex;
        }

        if ($data['search']['trigger_type'] == 1) {
            //非调拨触发
            $where['_string'] = 'tb_wms_allo_attribution.allo_id IS NULL';
        } else if ($data['search']['trigger_type'] == 2) {
            //调拨触发
            $where['_string'] = 'tb_wms_allo_attribution.allo_id IS NOT NULL';
        }

        # 排查所有已删除的数据
        $where['tb_wms_allo_attribution.deleted_at'] = ['exp', "IS NULL"];
        list($lists, $page) = $this->repository->index($where, $limit);

        $page['current_page'] = $data['page']['current_page'];
        $page['per_page']     = $data['page']['per_page'];
        $lists                = CodeModel::autoCodeTwoVal($lists, ['review_type_cd', 'change_type_cd', 'attribution_team_cd']);
        $lists                = (array)$this->mapMixing($lists);
        return [$lists, $page];
    }

    public function mapMixing($lists)
    {
        $map_data  = CodeModel::getCodeKeyValArr(['N00129', 'N00128', 'N00323'], null);
        $map_store = $this->repository->getStoreName();
        $map_data  = $map_data + $map_store;
        foreach ($lists as &$list) {
            $list['old_val'] = $map_data[$list['old']];
            $list['new_val'] = $map_data[$list['new']];
        }
        return $lists;
    }

    public function show($allo_attribution_id, $change_order_no = null)
    {
        $attribution = $this->repository->attribution($allo_attribution_id);
        $is_show     = 1;
        $allo_info   = [];
        if ($attribution['allo_id']) {
            #由调拨绑定  无法在此页面审核  隐藏操作按钮
            $is_show = 0;

            $tb_wms_allo_model = D("TbWmsAllo");
            $allo_info         = $tb_wms_allo_model->field([
                'id', 'allo_no', 'state',
            ])->find($attribution['allo_id']);
        }
        if (empty($attribution)) {
            throw new Exception(L('获取归属调拨信息错误，请检查单号'));
        }
        if (empty($allo_attribution_id)) {
            $allo_attribution_id = $attribution['id'];
        }

        # 调拨单号
        $attribution['allo_no'] = '';
        if ($allo_info) {
            $attribution['allo_no'] = $allo_info['allo_no'];
        }
        $attribution = CodeModel::autoCodeOneVal($attribution, ['review_type_cd', 'change_type_cd', 'attribution_team_cd']);

        $attribution         = $this->mapMixing([$attribution])[0];
        $res                 = [
            'info' => $attribution,
            'skus' => $this->repository->attributionSkus($allo_attribution_id),
            'logs'  => $this->assemblyLog($attribution),
        ];
        $res['skus']         = SkuModel::getInfo($res['skus'], 'sku_id', ['spu_name', 'attributes', 'image_url', 'product_sku']);
        $res['skus']         = CodeModel::autoCodeTwoVal($res['skus'], ['warehouse_cd', 'purchasing_team', 'small_sale_team_code']);
        $map_store           = $this->repository->getStoreName();
        $goods_type_key_vals = array_column(CodeModel::getGoodsTypeCode(), 'cdVal', 'cd');
        foreach ($res['skus'] as &$value) {
            $value['upc_id'] = $value['product_sku']['upc_id'];
            if ($value['product_sku']['upc_more']) {
                $upc_more_arr = explode(',', $value['product_sku']['upc_more']);
                array_unshift($upc_more_arr, $value['product_sku']['upc_id']);
                $value['upc_id'] = implode(",\r\n", $upc_more_arr);
            }
            $value['store_name']   = $map_store[$value['store_id']];
            $value['vir_type_val'] = $goods_type_key_vals[$value['vir_type']];
            unset($value['product'], $value['product_sku']);
        }
        $res['button'] = $is_show;
        return $res;
    }

    private function assemblyLog($attr_data)
    {
        //发起日志
        $created_logs = [
            [
                'id' => null,
                'allo_attribution_id' => $attr_data['id'],
                'review_user' => $attr_data['created_by'],
                'review_user_id' => null,
                'review_type_cd' => TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT,//待审核
                'review_time' => $attr_data['created_at'],
            ]
        ];
        //审核日志
        $review_logs = (array) $this->repository->getReviewLogs($attr_data['id']);
        return array_merge($created_logs, $review_logs);
    }

    /**
     * 审核
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function approval($data)
    {
        $allo_attribution_id = $data['id'];
        $allo_attribution = $this->repository->findAttrById($allo_attribution_id);
        if (1 !== $this->repository->approval($data)) {
            $this->verificationReviewStatus($allo_attribution_id, $data);
            throw new Exception(L('归属调拨审批失败'));
        }

        //审核日志记录
        $this->addReviewLog($allo_attribution, $data['review_type_cd']);

        $WechatMsg        = new WechatMsg();
        switch ($data['review_type_cd']) {
            case TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FINISHED:
                $this->ascriptionRecordGenerateApi($allo_attribution);
                $WechatMsg->sendText($allo_attribution['review_by'] . '@gshopper.com', L("您已审核通过库存归属变更单号：") . $allo_attribution['change_order_no']);
                $WechatMsg->sendText($allo_attribution['created_by'] . '@gshopper.com', L("库存归属变更单号：") . $allo_attribution['change_order_no'] . L("已经审核通过"));
                break;
            case TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FAILED:

                $this->ascriptionOccupyFreedApi($allo_attribution['change_order_no']);
                $WechatMsg->sendText($allo_attribution['review_by'] . '@gshopper.com', L("您已拒绝通过库存归属变更单号：") . $allo_attribution['change_order_no']);
                $WechatMsg->sendText($allo_attribution['created_by'] . '@gshopper.com', L("库存归属变更单号：") . $allo_attribution['change_order_no'] . L("已经被拒绝"));
                break;
            case TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED:
                $this->ascriptionOccupyFreedApi($allo_attribution['change_order_no']);
                $WechatMsg->sendText($allo_attribution['review_by'] . '@gshopper.com', L("库存归属变更单号：") . $allo_attribution['change_order_no'] . L("已取消"));
                break;
        }
    }

    private function ascriptionRecordGenerateApi($allo_attribution)
    {
        switch ($allo_attribution['change_type_cd']) {
            case 'N002990001':
                $type = 3;
                break;
            case 'N002990002':
                $type = 1;
                break;
            case 'N002990003':
                $type = 2;
                break;
            case 'N002990005':
                $type = 4;
                break;
        }
        $data         = [
            "orderId"    => $allo_attribution['change_order_no'],
            "type"       => $type,
            "style"      => $allo_attribution['new'],
            "operatorId" => DataModel::userId()
        ];
        $response_api = ApiModel::homeTransfer($data);
        if (2000 != $response_api['code']) {
            throw new Exception(L('订单审批失败 API ') . $response_api['msg']);
        }
    }

    private function ascriptionOccupyFreedApi($order_no)
    {
        $response_api = ApiModel::releaseOccupancyOrder($order_no);
        if (2000 != $response_api['code']) {
            throw new Exception(L('订单占用取消失败 API') . $response_api['msg']);
        }
    }

    private function verificationReviewStatus($allo_attribution_id, $data)
    {
        $allo_attribution_data = $this->repository->attribution($allo_attribution_id);
        if (empty($allo_attribution_data['review_by'])) {
            throw new Exception(L('审批人为空'));
        }
        if (TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT != $allo_attribution_data['review_type_cd']) {
            throw new Exception(L('状态错误'));
        }
        if (TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED != $data['review_type_cd']
            && ($allo_attribution_data['review_by'] != DataModel::userNamePinyin() || 'erpadmin' != strtolower(DataModel::userNamePinyin()))) {
            throw new Exception(DataModel::userNamePinyin() . L('无审批权限'));
        }

    }

    public function create($change_order_no, $attribution, $attribution_skus)
    {
        $login_user  = DataModel::userNamePinyin();
        $reviewer_by = $this->obtainReviewBy($attribution);
        $review_by   = NULL;//第一个审核人
        if ($reviewer_by) {
            $review_by = array_shift(explode('>', $reviewer_by));
        }
        $attribution       = [
            'change_order_no'     => $change_order_no,
            'change_type_cd'      => $attribution['change_type_cd'],
            'attribution_team_cd' => $attribution['attribution_team_cd'],
            'old'                 => $attribution['old'],
            'new'                 => $attribution['new'],
            'reviewer_by'         => $reviewer_by,
            'review_by'           => $review_by,
            'created_by'          => $login_user,
            'updated_by'          => $login_user,
        ];
        $attribution['id'] = $this->repository->createAlloAttribution($attribution);
        if (empty($attribution['id'])) {
            throw new Exception(L('创建归属调拨失败'));
        }
        $this->repository->createAlloAttributionSku(
            $this->assemblyAttributionData($attribution['id'], $attribution_skus)
        );
        return [$attribution, $review_by];
    }

    #调拨中变更所属小团队申请  针对jerry团队
    public function createByAllo($change_order_no, $attribution, $attribution_skus, $allo_id = 0)
    {
        $login_user                         = DataModel::userNamePinyin();
        $attribution['change_type_cd']      = 'N002990005';
        $attribution['attribution_team_cd'] = $attribution['into_team'];
        $reviewer_by                        = $this->obtainReviewBy($attribution, true);
        $review_by                          = $reviewer_by;//只有一个审核人
        $attribution = [
            'change_order_no'     => $change_order_no,
            'change_type_cd'      => 'N002990005',
            'attribution_team_cd' => $attribution['into_team'],
            'old'                 => '',
            'new'                 => $attribution['small_team_cd'],
            'reviewer_by'         => $reviewer_by,
            'review_by'           => $review_by,
            'created_by'          => $login_user,
            'updated_by'          => $login_user,
        ];
        if ($allo_id) {
            $update_re = $this->repository->updateAttr(['allo_id' => $allo_id], ['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => DataModel::userNamePinyin()]);
            if (empty($update_re)) {
                throw new Exception(L('更新归属调拨失败'));
            }
        }

        $attribution['id'] = $this->repository->createAlloAttribution($attribution);
        if (empty($attribution['id'])) {
            throw new Exception(L('创建归属调拨失败'));
        }
        // $this->repository->createAlloAttributionSku(
        //     $this->assemblyAttributionData($attribution['id'], $attribution_skus)
        // );
        return [$attribution, $review_by];
    }

    public function createAlloAttributionSku($allo_attribution_id, $arr)
    {

        foreach ($arr as $value) {
            $temp_attribution_skus[] = [
                'allo_attribution_id' => $allo_attribution_id,
                'sku_id'              => $value['sku_id'],
                'batch_id'            => $value['batch_id'],
                'transfer_number'     => $value['num'],
                'created_by'          => DataModel::userNamePinyin(),
                'updated_by'          => DataModel::userNamePinyin(),
            ];
        }
        $this->repository->createAlloAttributionSku($temp_attribution_skus);
    }

    public function updateAttr($attr_id, $allo_id)
    {
        $this->repository->updateAttrById($attr_id, $allo_id);
    }

    /**
     * 获取归属调拨审核人集合 11375 库存归属变更优化，调整如下
     * @param $attribution
     * @param bool $is_allo_launch 是否调拨发起
     * @return null
     */
    private function obtainReviewBy($attribution, $is_allo_launch = false)
    {
        $reviewer_by = null;
        if ($is_allo_launch) {
            //若当前库存归属变更单由调拨单发起，则库存归属变更单审核人统一为jerry huang
            return 'Jerry.Huang';
        }
        switch ($attribution['change_type_cd']) {
            //该类型线上未开启，保持原来的逻辑
            case CodeModel::$change_attribution_store_cd:
                $sales_team_by = DataModel::emailToUser(CodeModel::getTeamBy($attribution['attribution_team_cd']));
                if ($attribution['old']) {
                    $reviewer_by = $this->repository->getStoreBy($attribution['old']);
                } else {
                    $reviewer_by = $sales_team_by;
                }
                break;
            case CodeModel::$change_sales_team_cd:
                //审批人改为变更前团队领导和变更后团队领导共同审核（先变更前团队领导审核，后变更后团队领导审核）
                if ($attribution['old']) {
                    $reviewer_arr = explode(',', CodeModel::getTeamBy($attribution['old']));
                    $old_reviewer = DataModel::emailToUser($reviewer_arr);
                    if ($old_reviewer) {
                        $old_reviewer = implode('>', $old_reviewer);
                        $reviewer_by  = $old_reviewer;
                    }
                }
                if ($attribution['new']) {
                    $reviewer_arr = explode(',', CodeModel::getTeamBy($attribution['new']));
                    $new_reviewer = DataModel::emailToUser($reviewer_arr);
                    if ($new_reviewer) {
                        $new_reviewer = implode('>', $new_reviewer);
                    }
                    if ($old_reviewer) {
                        $reviewer_by = trim($old_reviewer . '>' . $new_reviewer, '>');
                    } else {
                        $reviewer_by = $new_reviewer;
                    }
                }
                break;
            case CodeModel::$change_purchasing_team_cd:
                //审批人改为变更前团队领导和变更后团队领导共同审核（先变更前团队领导审核，后变更后团队领导审核）
                if ($attribution['old']) {
                    $reviewer_arr = explode(',', CodeModel::getTeamBy($attribution['old']));
                    $old_reviewer = DataModel::emailToUser($reviewer_arr);
                    if ($old_reviewer) {
                        $old_reviewer = implode('>', $old_reviewer);
                        $reviewer_by  = $old_reviewer;
                    }
                }
                if ($attribution['new']) {
                    $reviewer_arr = explode(',', CodeModel::getTeamBy($attribution['new']));
                    $new_reviewer = DataModel::emailToUser($reviewer_arr);
                    if ($new_reviewer) {
                        $new_reviewer = implode('>', $new_reviewer);
                    }
                    if ($old_reviewer) {
                        $reviewer_by = trim($old_reviewer . '>' . $new_reviewer, '>');
                    } else {
                        $reviewer_by = $new_reviewer;
                    }
                }
                break;
            case CodeModel::$change_small_sales_team_cd:
                //审批人改为变更前团队领导和变更后团队领导共同审核（先变更前团队领导审核，后变更后团队领导审核）
                if ($attribution['old']) {
                    $old_reviewer = DataModel::emailToUser(CodeModel::getTeamBy($attribution['old'], 'ETC2'));
                    if ($old_reviewer) {
                        $old_reviewer = str_replace(',', '>', $old_reviewer);
                        $reviewer_by  = $old_reviewer;
                    }
                }
                if ($attribution['new']) {
                    $new_reviewer = DataModel::emailToUser(CodeModel::getTeamBy($attribution['new'], 'ETC2'));
                    if ($new_reviewer) {
                        $new_reviewer = str_replace(',', '>', $new_reviewer);
                    }
                    if ($old_reviewer) {
                        $reviewer_by = trim($old_reviewer . '>' . $new_reviewer, '>');
                    } else {
                        $reviewer_by = $new_reviewer;
                    }
                }
                break;
        }
        $reviewer_by = implode('>', array_unique(explode('>', $reviewer_by)));//去重
        return $reviewer_by ?: NULL;
    }

    private function assemblyAttributionData($allo_attribution_id, $attribution_skus, $temp_attribution_skus = [])
    {
        foreach ($attribution_skus as $attribution_sku) {
            $temp_attribution_skus[] = [
                'allo_attribution_id' => $allo_attribution_id,
                'sku_id'              => $attribution_sku['sku_id'],
                'batch_id'            => $attribution_sku['batch_id'],
                'transfer_number'     => $attribution_sku['num'],
                'created_by'          => DataModel::userNamePinyin(),
                'updated_by'          => DataModel::userNamePinyin(),
            ];
        }
        return $temp_attribution_skus;
    }

    public function getAttrIdByAlloId($allo_id)
    {
        return M('wms_allo_attribution', 'tb_')->where(['allo_id' => $allo_id])->getField('id');
    }

    public function getAttrByAlloId($allo_id)
    {
        return M('wms_allo_attribution', 'tb_')->where(['allo_id' => $allo_id])->find();
    }

    public function getAttrChangeOrderByAlloId($allo_id)
    {
        return M('wms_allo_attribution', 'tb_')->where(['allo_id' => $allo_id])->getField('change_order_no');
    }

    //取消归属变更单
    public function cancelAllocateAttribution($attribution_id)
    {
        //调拨创建或者已完成的的归属单不支持取消
        $review_type_cd = TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FINISHED;
        $attr_data    = $this->repository->findAttrById($attribution_id);
        if (!empty($attr_data['allo_id']) || $attr_data['review_type_cd'] == $review_type_cd) {
            throw new Exception(L('调拨创建或者已完成的的归属单不支持取消'));
        }
        $save_res = $this->repository->updateAttr(['id' => $attribution_id], ['review_type_cd' => TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED]);
        if (false === $save_res) {
            throw new Exception(L('取消失败'));
        }
        $this->addReviewLog($attr_data, TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_CANCELED);
        //取消需要释放占用库存
        $this->ascriptionOccupyFreedApi($attr_data['change_order_no']);
        return true;
    }

    /**
     * 判断是否是最后一个审核人
     * @param $attribution_id
     * @return bool
     */
    public function isLastReviewer($attribution_id)
    {
        $attr_data     = $this->repository->findAttrById($attribution_id);
        $reviewers     = explode('>', $attr_data['reviewer_by']);
        $last_reviewer = array_pop($reviewers);
        if (strtolower($last_reviewer) == strtolower($attr_data['review_by'])) {
            return true;
        }
        return false;
    }

    /**
     * 下一个人审核（不是最后一个审核人）
     * @param $request_data
     * @throws Exception
     */
    public function nextApproval($request_data)
    {
        $attr_data       = $this->repository->findAttrById($request_data['id']);
        $reviewers       = explode('>', $attr_data['reviewer_by']);
        $next_review_pos = array_search(DataModel::userNamePinyin(), $reviewers) + 1;//下个审核人
        $next_review_by  = $reviewers[$next_review_pos] ?: null;
        if (!$next_review_by) {
            throw new Exception(L('未找到审核人'));
        }
        $review_type_cd = null;
        if ($request_data['review_type_cd'] == TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FINISHED) {
            //审核通过，但不是最后一个审核，审核状态还是待审核
            $review_type_cd =  TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_WAIT;
        } else if ($request_data['review_type_cd'] == TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FAILED) {
            //审核不通过
            $review_type_cd =  TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FAILED;
        }
        if (!$review_type_cd) {
            throw new Exception(L('审核状态异常'));
        }
        if (false === $this->repository->nextApproval($request_data['id'], $next_review_by, $review_type_cd)) {
            throw new Exception(L('归属调拨审批失败'));
        }
        $this->addReviewLog($attr_data, $request_data['review_type_cd']);

        if ($review_type_cd == TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FAILED) {
            //驳回需要释放占用库存
            $this->ascriptionOccupyFreedApi($attr_data['change_order_no']);
        }
        if ($review_type_cd != TbWmsAlloAttributionModel::ALLO_ATTR_AUDIT_FAILED) {
            //通过才需要发送消息给下个审核人
            (new ReviewMsgTpl())->sendWeChatAttributionTransfer($attr_data, $next_review_by);
        }
    }

    /**
     * 添加审核日志
     * @param $attr_data
     * @param $review_type_cd 审核状态
     * @throws Exception
     */
    private function addReviewLog($attr_data, $review_type_cd)
    {
        $log_data = [
            'allo_attribution_id' => $attr_data['id'],
            'review_user'         => $attr_data['review_by'],
            'review_user_id'      => DataModel::getUserIdByName($attr_data['review_by']),
            'review_type_cd'      => $review_type_cd,
        ];
        $add_res = $this->repository->addReviewLog($log_data);
        if (!$add_res) {
            throw new Exception(L('写入审核日志失败'));
        }
    }

    //调拨触发的库存归属审核
    public function approvalByAllocation($data)
    {
        $allo_attribution_id = $data['id'];
        $allo_attribution = $this->repository->findAttrById($allo_attribution_id);
        if (!$this->repository->approvalByAllocation($data)) {
            $this->verificationReviewStatus($allo_attribution_id, $data);
            throw new Exception(L('归属调拨审批失败'));
        }
        //审核日志记录
        $this->addReviewLog($allo_attribution, $data['review_type_cd']);
    }
}