<?php
/**
 * 调拨
 * User: b5m
 * Date: 2017/8/29
 * Time: 16:05
 */

class AllocationAction extends BaseAction
{
    public function _initialize()
    {

    }

    /**
     * 库存调拨
     * 每个sku有多少个销售团队就展示多少条数据
     *
     */
    public function index()
    {
        $params = $this->getParams();
        $allocation = new AllocationModel();
        $conditions = $allocation->searchModel($params);
        $_GET['p'] = $params ['p'];
        //获取已经开启的销售团队
        $saleTeamCodes = BaseModel::saleTeamCdExtend();
        if ($saleTeamCodes) {
            // 销售团队拼接
            $saleTeamsCodesConditions = ' in (';
            foreach ($saleTeamCodes as $k => $v) {
                $saleTeamsCodesConditions .= '"' . $v ['CD'] . '", ';
            }
            $saleTeamsCodesConditions = rtrim($saleTeamsCodesConditions, ' ,');
            $saleTeamsCodesConditions .= ')';
            $baseModel = new Model();
            $searchConditions = $allocation->assembleSearchConditions($params);
            $sql_count = "SELECT COUNT(*) as count from 
            (SELECT
                SUM(all_oversole_number) AS all_oversole_number,
                tb_wms_batch.sale_team_code,
                `SKU_ID`,
                SUM(available_for_sale_num) AS all_available_for_sale_nums,
                `deadline_date_for_use`,
                tb_ms_guds.GUDS_NM,
                tb_wms_bill.warehouse_id AS warehouse
            FROM
                `tb_wms_batch`
            LEFT JOIN tb_ms_guds ON tb_wms_batch.GUDS_ID = tb_ms_guds.GUDS_ID
            LEFT JOIN tb_wms_bill ON tb_wms_batch.bill_id = tb_wms_bill.id
            WHERE tb_wms_batch.sale_team_code {$saleTeamsCodesConditions}
            GROUP BY
                sale_team_code,
                SKU_ID,
                tb_wms_bill.warehouse_id
            ) tt1 left JOIN (select SUM(oversole_num) as oversize, SKU_ID, sale_team_code from tb_wms_batch_order where use_type = 0 group by sale_team_code,
                SKU_ID) tt2
            ON tt1.SKU_ID = tt2.SKU_ID and tt1.sale_team_code = tt2.sale_team_code " . $searchConditions;
            $count = $baseModel->where($conditions)->query($sql_count);
            $count = $count [0]['count'];
            import('ORG.Util.Page');
            $page = new Page($count, 20);
            $show = $page->ajax_show('submit');
            $sql = "SELECT tt1.*, IFNULL(tt2.oversize, 0) as oversize from 
            (SELECT
                SUM(all_oversole_number) AS all_oversole_number,
                tb_wms_batch.sale_team_code,
                `SKU_ID`,
                SUM(available_for_sale_num) AS all_available_for_sale_nums,
                `deadline_date_for_use`,
                tb_ms_guds.GUDS_NM,
                tb_wms_bill.warehouse_id AS warehouse
            FROM
                `tb_wms_batch`
            LEFT JOIN tb_ms_guds ON tb_wms_batch.GUDS_ID = tb_ms_guds.GUDS_ID
            LEFT JOIN tb_wms_bill ON tb_wms_batch.bill_id = tb_wms_bill.id
            WHERE tb_wms_batch.sale_team_code {$saleTeamsCodesConditions}
            GROUP BY
                sale_team_code,
                SKU_ID,
                tb_wms_bill.warehouse_id
            ) tt1 left JOIN (select SUM(oversole_num) as oversize, SKU_ID, sale_team_code from tb_wms_batch_order where use_type = 0 group by sale_team_code,
                SKU_ID) tt2
            ON tt1.SKU_ID = tt2.SKU_ID and tt1.sale_team_code = tt2.sale_team_code " . $searchConditions . "
            LIMIT ".$page->firstRow.",".$page->listRows;
            $ret = $baseModel->where($conditions)->query($sql);
        }
        $view_range = [
            0 => L('全部'),
            1 => L('仅超卖')
        ];
        if (IS_AJAX) {
            $this->ajaxReturn(['data_ret' => $ret, 'info' => 'success', 'status' => 1, 'show' => $show, 'count' => count($count)]);
        }
        $this->assign('param', json_encode($params, JSON_UNESCAPED_UNICODE));
        $this->assign('view_range', json_encode($view_range, JSON_UNESCAPED_UNICODE));
        $this->assign('list_warehouse', json_encode(BaseModel::getWarehouseId(), JSON_UNESCAPED_UNICODE));
        $this->assign('sales_team', json_encode(BaseModel::saleTeamCdExtend(), JSON_UNESCAPED_UNICODE));
        $this->assign('data_ret', json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->assign('show', json_encode($show, JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 调拨记录
     *
     */
    public function allo_history()
    {
        import('ORG.Util.Page');
        $model = new TbWmsAllocationModel();
        $params = $this->getParams();
        $conditions = $model->search($params);
        $count = $model->where($conditions)->count();
        $page = new Page($count, 20);
        $ret = $model
            ->join('left join tb_ms_guds on SUBSTR(tb_wms_allocation.allo_guds, 1, 8) = tb_ms_guds.GUDS_ID')
            ->where($conditions)
            ->limit($page->firstRow, $page->listRows)
            ->field('tb_wms_allocation.*, tb_ms_guds.GUDS_NM')
            ->order('id desc')
            ->select();
        $show = $page->show();

        $this->assign('warehouses', json_encode(BaseModel::get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('teams', json_encode(BaseModel::saleTeamCdExtend(), JSON_UNESCAPED_UNICODE));
        $this->assign('ret', json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->assign('pages', $show);
        $this->assign('states', json_encode($model::state()));
        $this->assign('params', json_encode($params, JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 调拨数据获取
     *
     */
    public function launch_allo()
    {
        $params = $this->getParams();

        $model = M('_wms_batch', 'tb_');
        //$conditions ['tb_wms_batch.state'] = ['eq', 0];
        $conditions ['tb_wms_bill.warehouse_id'] = ['eq', $params ['warehouse']];
        $conditions ['tb_wms_batch.sale_team_code'] = ['neq', $params ['sale_team']];
        $conditions ['tb_wms_batch.SKU_ID'] = ['eq', $params ['sku_id']];
        $fields = [
            'SUM(all_oversole_number) AS all_oversole_number',
            'sale_team_code',
            'SKU_ID',
            'SUM(available_for_sale_num) AS all_available_for_sale_nums',
            'deadline_date_for_use',
            'tb_ms_guds.GUDS_NM'
        ];
        $ret = $model->field($fields)
            ->join('LEFT JOIN tb_ms_guds ON tb_wms_batch.GUDS_ID = tb_ms_guds.GUDS_ID')
            ->join('LEFT JOIN tb_wms_bill ON tb_wms_batch.bill_id = tb_wms_bill.id')
            ->where($conditions)
            ->group('sale_team_code, SKU_ID')
            ->having('all_available_for_sale_nums > 0')
            ->select();
        if ($ret) {
            $msg = L('成功');
            $state = 1;
        } else {
            $msg = L('无可调拨数据');
            $state = 0;
        }

        $this->ajaxReturn($ret, $msg, $state);
    }

    /**
     * 确认调拨
     * 1、调拨先生成出入库单，再进行出入库操作
     * 2、走入库接口
     * 3、将出入库子表id传递给出入库接口
     *
     */
    public function confirm_launch()
    {
        $params = $this->getParams();
        $allocation = new AllocationModel();
        $ret = $allocation->createHistory($params);
        if (session('m_loginname')) {
            if ($ret ['state'] == 1) {
                $data = '邮件发送完成';
                $info = '已发送调拨申请';
                $state = 1;
            } else {
                $data = $ret ['data'];
                $info = $ret ['info'];
                $state = 0;
            }
        } else {
            $data = '请重新登录';
            $info = '发送失败' . $data;
            $state = 0;
        }

        $this->ajaxReturn($data, $info ,$state);
    }

    /**
     * 获取调拨模板
     *
     */
    public function get_allo_template($v, $confirm_url, $refuse_url, $launcher)
    {
        $this->assign('params', $v);
        $this->assign('confirm', $confirm_url);
        $this->assign('refuse', $refuse_url);
        $this->assign('launcher', $launcher);
        // 获取生成html文件
        $content = $this->fetch('template');
        return $content;
    }

    /**
     * 邮件调拨同意与否
     *
     */
    public function mail_confirm_refuse()
    {
        $params = $this->getParams();
        $hash = $params ['hash'];
        header("Content-type: text/html; charset=utf-8");
        $model = new AllocationModel();
        $ret = $model->where('guid_map = "' . $hash . '"')->find();
        if ($ret and $ret ['state'] == 1 and $params ['confirm'] == 'agree') {
            $bill = new TbWmsBillModel();
            // 调拨
            $res = $bill->operationAllo(json_decode($ret ['allo_info'], true));
            $ret = $model->where('guid_map = "' . $hash . '"')->find();
            if ($res ['code'] == 11110111) {
                $ret ['state'] = 2;
            } else {
                $ret ['state'] = 4;
            }
            $ret ['end_time'] = date('Y-m-d H:i:s');
            $msg = $res ['msg'];
            $model->data($ret)->save();
        } elseif ($ret and $ret ['state'] == 1 and $params ['confirm'] == 'refuse') {
            $ret ['state'] = 3;
            $ret ['end_time'] = date('Y-m-d H:i:s');
            $model->save($ret);
            $msg = L("已拒绝请求");
        } else {
            $msg = L("该链接已失效或流程已结束");
        }
        $this->assign('msg', $msg);
        $this->display('confirm');
    }

    /**
     * 撤回调拨
     *
     */
    public function receive()
    {
        $model = new TbWmsAllocationModel();
        $ret = $model->receive($this->getParams());

        $this->ajaxReturn(null, $ret ['msg'], $ret ['state']);
    }

    /**
     * 入库单
     *
     */
    public function out_or_allo()
    {
        $params = $this->getParams();
        $type   = $params ['type'];
        $id     = $params ['id'];

        $model  = new AllocationModel();
        $ret = $model->where('id = ' . $id)->find();

        // 根据类型判断是出还是入1出2入
        if ($type == 1) $storage_id = $ret ['out_storage_id'];
        else $storage_id = $ret ['in_storage_id'];
        // 取得出入库单主表数据
        $billModel = new TbWmsBillModel();
        $bill = $billModel->where('id = ' . $storage_id)->find();
        // 取得出入库单从表数据
        $streamModel = new StreamModel();
        $fields = 'tb_wms_stream.GSKU, tb_ms_guds.GUDS_NM, tb_ms_guds_opt.GUDS_OPT_VAL_MPNG,
                   tb_ms_guds_opt.GUDS_OPT_UPC_ID, tb_wms_stream.deadline_date_for_use, tb_wms_stream.send_num,
                   tb_wms_stream.send_num, tb_ms_guds.VALUATION_UNIT';
        $streams = $streamModel
            ->field($fields)
            ->join('left join tb_ms_guds ON SUBSTR(tb_wms_stream.GSKU, 1, 8) = tb_ms_guds.GUDS_ID')
            ->join('left join tb_ms_guds_opt ON tb_wms_stream.GSKU = tb_ms_guds_opt.GUDS_OPT_ID')
            ->where('bill_id = ' . $bill ['id'])
            ->select();
        // 循环将商品属性替换掉
        $stockAction = A('Home/Stock');
        foreach ($streams as $k => &$v) {
            $v ['GUDS_OPT_VAL_MPNG'] = $stockAction->gudsOptsMerge($v ['GUDS_OPT_VAL_MPNG']);
            $v ['VALUATION_UNIT'] = BaseModel::getUnit()[$v ['VALUATION_UNIT']]['CD_VAL'];
        }
        // 调入团队
        $launch_storage_team = $ret ['launch_team_cd'];
        // 调出团队
        $receive_storage_team = $ret ['receive_team_cd'];

        $this->assign('warerule', BaseModel::getRuleStorage());
        $this->assign('sales', BaseModel::saleTeamCdExtend());
        $this->assign('launch_storage_team', $launch_storage_team);
        $this->assign('receive_storage_team', $receive_storage_team);
        $this->assign('type', $type);
        $this->assign('bill', $bill);
        $this->assign('streams', $streams);
        $this->assign('warehouses', BaseModel::get_all_warehouse());

        $this->display('storage');
    }

    /**
     * 出入库单
     *
     */
    public function out_storage()
    {
        $this->assign('title', L('调拨出库单'));
        $this->assign('id', $this->getParams()['id']);
        $this->display('storage');
    }

    // 处理旧的调拨单，上线后，可删除
    public function dealHistoryAllocation()
    {
        $allo_no = 'DB201905200008'; // 仅此调拨单有占用的库存问题
        $param[] = [
            "orderId" => $allo_no,
        ];
        $res_api = ApiModel::freeUpBatch($param);
        Logs($res_api, __FUNCTION__, __CLASS__);
        if (2000 != $res_api['code']) {
            p($res_api['msg']);die;
            //throw new Exception(L('API 释放占用失败:') . $res_api['msg']);
        }
        echo "SUCCESS";
    }
}