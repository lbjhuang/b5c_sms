<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/9/11
 * Time: 10:48
 */
class TbWmsAllocationModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_allocation';

    const WAITING_AUDIT_STATE = 1;// 待审核，初始化写入
    const COMPLETE_STATE = 2;     // 已完成
    const REFUSE_STATE = 3;       // 已拒绝
    const ABOLISH_STATE = 4;      // 已废除

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['launch_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user_id', 'getName', Model::MODEL_INSERT, 'callback'],
        ['state', SELF::WAITING_AUDIT_STATE, Model::MODEL_INSERT],
    ];

    /**
     * 处理状态
     *
     */
    public static function state()
    {
        return [
            1 => L('待审核'),
            2 => L('已完成'),
            3 => L('已拒绝'),
            4 => L('已废除'),
        ];
    }

    /**
     * @param $params 查询条件
     * @return array
     *  ["m"]=>
    string(0) ""
    ["allo_no"]=>
    string(4) "1231"
    ["allo_guds"]=>
    string(0) ""
    ["launch_team"]=>
    string(0) ""
    ["receive_team"]=>
    string(0) ""
    ["Warehouse"]=>
    string(0) ""
    ["state"]=>
    string(0) ""
    ["launch_time"]=>
    string(0) ""
    ["launch_end_time"]=>
    string(0) ""
     */
    public function search($params)
    {
        $conditions = [];

        if ($params) {
            if ($params ['allo_no']) {
                $conditions ['allo_no'] = $params['allo_no'];
            }
            if ($params ['allo_guds']) {
                $conditions ['allo_guds'] = ['like', '%' . $params ['allo_guds'] . '%'];
            }
            if ($params ['launch_team']) {
                $conditiosns ['launch_team_cd'] = $params ['launch_team_cd'];
            }
            if ($params ['receive_team']) {
                $conditions ['receive_team_cd'] = $params ['receive_team'];
            }
            if ($params ['warehouse_show']) {
                $conditions ['warehouse'] = $params ['warehouse_show'];
            }
            if ($params ['state']) {
                $conditions ['state'] = $params ['state'];
            }
            if ($params ['launch_time']) {
                $conditions ['create_time'] = [
                    'gt', $params ['launch_time'] . ' 00:00:00'
                ];
            }
            if ($params ['launch_end_time']) {
                $conditions ['launch_end_time'] = [
                    'lt', $params ['launch_end_time'] . ' 59:59:59'
                ];
            }
            if ($params ['id']) {
                $conditions ['id'] = $params ['id'];
            }
        }
        return $conditions;
    }

    /**
     * @param $params 查询条件
     * @return array
     */
    public function receive($params)
    {
        $conditions = $this->search($params);
        $ret = $this->where($conditions)->find();

        if ($ret and $ret ['state'] == SELF::WAITING_AUDIT_STATE) {
            $data ['state'] = SELF::ABOLISH_STATE;
            $data ['withdraw_time'] = date('Y-m-d H:i:s');
            $data ['end_time'] = date('Y-m-d H:i:s');
            if ($this->where($conditions)->save($data)) {
                $ret = ['msg' => '撤回成功', 'state' => 1];
            } else {
                $ret = ['msg' => $this->getError(), 'state' => 0];
            }
        } else {
            $ret = ['msg' => '该调拨不存在或者状态不正确', 'state' => 0];
        }

        return $ret;
    }

    /**
     * 出入库单数据
     *
     */
    public function storage()
    {
        $this->assign('company_arr', json_encode($this->get_company(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('outgo', json_encode($this->get_outgo(), JSON_UNESCAPED_UNICODE));
        $this->assign('warehouse_use', json_encode($this->get_use(), JSON_UNESCAPED_UNICODE));
        $this->assign('currency', json_encode($this->get_currency(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->assign('bill_state', json_encode($this->bill_state, JSON_UNESCAPED_UNICODE));
        $this->assign('warehouse_rule', json_encode($this->getwarehouse_rule(I('get.outgoing')), JSON_UNESCAPED_UNICODE));
        $this->assign('outgoing', json_encode(I('get.outgoing'), JSON_UNESCAPED_UNICODE));
        $id = I('get.bill_id');
        $Bill = M('bill', 'tb_wms_');
        $where['id'] = $id;
        $bills = $Bill->where($where)->select();
        $this->assign('bills', json_encode($bills, JSON_UNESCAPED_UNICODE));
        if (in_array($bills[0]['bill_type'], array_keys($this->get_out()))) {
            $this->assign('outgo_state', 'storage');
        } else {
            $this->assign('outgo_state', 'outgoing');
        }
        $Stream = M('stream', 'tb_wms_');
        $wheres['bill_id'] = $id;
        $stream = $Stream->where($wheres)
            ->join('LEFT JOIN tb_wms_location_details on tb_wms_location_details.id = tb_wms_stream.location_id')
            ->field('tb_wms_stream.*,tb_wms_location_details.box_name as location')
            ->select();

        $this->assign('stream', json_encode($stream, JSON_UNESCAPED_UNICODE));
        $this->assign('this_user', session('m_loginname'));
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('business_list', json_encode($this->get_business_list(), JSON_UNESCAPED_UNICODE));
        $this->assign('supplier_list', json_encode($this->get_supplier_list(), JSON_UNESCAPED_UNICODE));
    }
}