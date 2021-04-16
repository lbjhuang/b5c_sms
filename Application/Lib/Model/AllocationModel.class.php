<?php
/**
 * 调拨模型
 *
 */
class AllocationModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_allocation';

    public $saleTeamCdCurrent = '';

    public $mails = [];

    private $_error = [];

    protected $_auto = [
        ['create_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['launch_time', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['create_user_id', 'getName', Model::MODEL_INSERT, 'callback'],
        ['state', 1, Model::MODEL_INSERT]
    ];

    public function create_guid()
    {
        return create_guid();
    }

    public function searchModel($params)
    {
        if ($params ['view_range_show'] == 1) {
            $conditions ['all_oversole_number'] = ['gt', 0];
        }
        if ($params ['view_range_show'] == 0) {
            unset($conditions ['view_range_show']);
        }
        if ($params ['sales_team_show']) {
            $conditions ['sale_team_code'] = [
                'eq', $params ['sales_team_show']
            ];
        }
        if ($params ['list_warehouse_show']) {
            $conditions ['tb_ms_guds.DELIVERY_WAREHOUSE'] = [
                'eq', $params ['list_warehouse_show']
            ];
        }
        if ($params ['sku_id']) {
            $conditions ['sku_id'] = [
                'eq', $params ['sku_id']
            ];
        }

        return $conditions;
    }

    /**
     * @return 将查询条件组装为字符串
     * @return String
     *
     */
    public function assembleSearchConditions($params)
    {
        if ($params ['view_range_show'] == 1) {
            $conditions ['tt1.all_oversole_number'] = ['>', 0];
        }
        if ($params ['view_range_show'] == 0) {
            unset($conditions ['view_range_show']);
        }
        if ($params ['sales_team_show']) {
            $conditions ['tt1.sale_team_code'] = [
                '=', $params ['sales_team_show']
            ];
        }
        if ($params ['list_warehouse_show']) {
            $conditions ['tt1.warehouse'] = [
                '=', $params ['list_warehouse_show']
            ];
        }
        if ($params ['sku_id']) {
            $conditions ['tt1.SKU_ID'] = [
                '=', $params ['sku_id']
            ];
        }
        
        return $this->build_search_conditions($conditions);
    }

    public function build_search_conditions($conditions)
    {
        $where = '';
        foreach ($conditions as $key => $value) {
            if (is_int($value[1])) {
                $where .= $key . $value [0] . $value [1] . ' and ';
            } else {
                $where .= $key . $value [0] . '"' . $value [1] . '"' . ' and ';
            }
        }
        if ($where) {
            $where = rtrim($where, ' and');
            $where = ' where ' . $where;
        }

        return $where;
    }

    /**
     * 生成调拨记录单
     *
     */
    public function createHistory($params)
    {
        $this->saleTeamCdCurrent = $params ['sale_team_code'];
        try {
            $this->startTrans();
            $autoData = $this->create($params, 1);
            $data = null;
            $data = [];
            $saveData = [];
            $flag = true;
            $msg  = '';
            // 剥离页面数据，进行数据对象模型创建
            $allo_info ['sale_team_code'] = $params ['sale_team_code'];
            $allo_info ['warehouse'] = $params ['warehouse'];
            foreach ($params['batchs'] as $k => $v) {
                if (!isset($v ['launch_num']) or $v ['launch_num'] <= 0) continue;
                $v ['userId'] = $_SESSION['userId'];
                $allo_info ['batchs'] = [];
                $allo_info ['batchs'][] = $v;
                $tmp ['guid_map']  = create_guid();
                $allo_info ['guid'] = $tmp ['guid_map'];
                $tmp ['allo_info'] = json_encode($allo_info);
                $tmp ['allo_all_num'] = $v ['launch_num'];
                $tmp ['warehouse_cd'] = $params ['warehouse'];
                $tmp ['allo_guds'] = $v ['GUDS_NM'];
                $tmp ['receive_team_cd'] = $v ['sale_team_code'];
                $tmp ['launch_team_cd'] = $params ['sale_team_code'];
                $tmp ['all_available_for_sale_nums'] = $v ['all_available_for_sale_nums'];
                $tmp ['sku_id'] = $v ['SKU_ID'];
                $tmp ['allo_no'] = $this->generateAlloNo(null, $v ['launch_team_cd']);
                $data [] = $tmp;
            }
            // 检查邮箱
            $mail = $this->getMailBySaleTeam();
            foreach ($data as $k => $v) {
                if (!$mail [$v['receive_team_cd']]['ETC2']) {
                    $this->_error ['error']['mail'][] = '[' . $mail [$v['receive_team_cd']]['CD_VAL'] . ']' . L('该销售团队未设置调拨申请接收邮箱');
                    $flag = false;
                } else {
                    $this->mails [$v ['guid_map']] = $mail [$v ['receive_team_cd']]['ETC2'];
                }
                if ($mail [$v ['receive_team_cd']]['USE_YN'] == 'N') {
                    $this->_error ['error']['sale_team'][] = '[' . $mail [$v['receive_team_cd']]['CD_VAL'] . ']' . L(' 已禁止使用');
                    $flag = false;
                }
            }
            // 保存数据对象模型，提取以id为key值的数组，数据包含每行数据的信息
            if ($flag) {
                foreach ($data as $k => $v) {
                    $saveData = array_merge($v, $autoData);
                    if ($id = $this->add($saveData)) {
                        $allos [$id] = $v;
                    } else {
                        $flag = false;
                        $this->_error ['error']['db'][] = [
                            'db' => $this->db->getError(),
                            'msg' => L('调拨失败，写入失败')
                        ];
                    }
                }
            }
            // 全部保存成功则进行事物的提交，转交至邮件发送
            if ($flag) {
                if ($this->sendMail($allos)) {
                    $this->commit();
                    $return ['state'] = 1;
                    $return ['data']  = null;
                    $return ['info']  = L('发起调拨成功');
                } else {
                    $this->rollback();
                    $return ['state'] = 0;
                    $return ['data']  = L('邮件发送失败');
                    $return ['info']  = L('发起调拨失败') . $this->getError();
                }
            } else {
                $this->rollback();
                $return ['state'] = 0;
                $return ['data']  = $this->db->getError();
                $return ['info']  = L('发起调拨失败') . $this->getError();
            }
        } catch (\Exception $e) {
            $this->rollback();
            $return ['state'] = 0;
            $return ['data']  = $e->getMessage();
            $return ['info']  = L('发起调拨失败') . $e->getMessage();
        }
        // 日志记录，数据返回
        $this->setRequestData($saveData);
        $response = array_merge($return, $this->_error);
        $this->setResponseData($response);
        $this->_catchMe();

        return $return;
    }

    /**
     * 获取错误报告
     * @params $type
     * @return String Error message
     */
    public function getError($type = '')
    {
        $error_type = [
            'mail',
            'sendMailError',
            'db',
            'sale_team'
        ];
        $error = '';
        if (in_array($type, $error_type)) {
            $error = implode(',', $this->_error ['error'][$type]);
        } else {
            foreach ($error_type as $key => $value) {
                $error .= implode(',', $this->_error ['error'][$value]);
            }
        }

        return $error;
    }

    /**
     * 获得所有销售团队对应的邮箱
     * @return sale team code, mail, USE_YN
     */
    public function getMailBySaleTeam()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00128%"')->getField('CD, CD_VAL, ETC as ETC2, USE_YN');

        return $ret;
    }

    /**
     * 邮件发送
     * @params $allos 邮件确认的数据
     * @return boolean send mail is success
     */
    public function sendMail($allos)
    {
        $mail = new ExtendSMSEmail();
        $title = L('调拨申请');
        // 数据拆分，不同的销售团队接收不同的数据
        $alloAction = A('Home/Allocation');
        foreach ($allos as $k => $v) {
            $confirm_url = $this->generateAgreeUrl($v ['guid_map']);
            $refuse_url  = $this->generateRefuseUrl($v ['guid_map']);
            $content = $alloAction->get_allo_template($v, $confirm_url, $refuse_url, BaseModel::saleTeamCd()[$this->saleTeamCdCurrent]);
            // 发送邮件
            // $address = 'benyin@gshopper.com'; //测试邮件
            if (!$mail->sendEmail($this->mails [$v['guid_map']], $title, $content)) {
                $this->_error ['error']['sendMailError'][] = $mail->ErrorInfo;
                return false;
            }
        }

        return true;
    }

    /**
     * 生成同意连接
     * @params $guid 全局唯一标示
     * @return String url
     */
    public function generateAgreeUrl($guid)
    {
        $web_site = C('redirect_audit_addr');
        $params = [
            'm' => 'allocation',
            'a' => 'mail_confirm_refuse',
            'hash' => $guid,
            'timestamp' => time(),
            'confirm' => 'agree'
        ];
        $web_site .= '/index.php?' . http_build_query($params);

        return $web_site;
    }

    /**
     * 生成拒绝连接
     * @params $guid 全局唯一标示
     * @return String url
     */
    public function generateRefuseUrl($guid)
    {
        $web_site = C('redirect_audit_addr');
        $params = [
            'm' => 'allocation',
            'a' => 'mail_confirm_refuse',
            'hash' => $guid,
            'timestamp' => time(),
            'confirm' => 'refuse'
        ];
        $web_site .= '/index.php?' . http_build_query($params);

        return $web_site;
    }

    /**
     * 生成调拨单号
     * @params date $get_date
     * @params String $sale_team_code sale team code
     * @return String allocation order number
     */
    public function generateAlloNo($get_date = null, $sale_team_code)
    {
        $sale_team_nm = $this->parseSaleTeam($sale_team_code);

        $date = date("Y-m-d");
        $start_date = date("Y-m-d 00:00:00");
        $end_date = date("Y-m-d 23:59:59");
        empty($get_date) ? '' : $date = $get_date;
        $where['create_time'] = [
            ['gt', $start_date],
            ['lt', $end_date]
        ];

        $max_id = $this->where($where)->order('id')->limit(1)->count();
        $type = 'DB';
        $date = date("Ymd");
        empty($get_date) ? '' : $date = date("Ymd", strtotime($get_date));
        $date = substr($date, 2);
        $wrate_id = $max_id + 1;
        $w_len = strlen($wrate_id);
        $b_id = '';
        if ($w_len < 4) {
            for ($i = 0; $i < 4 - $w_len; $i++) {
                $b_id .= '0';
            }
        }

        return $type . $sale_team_nm . $date . $b_id . $wrate_id;
    }

    /**
     * 销售团队简写处理
     * @params $saleTeamCode 销售团队编码
     * @return Array
     */
    public function parseSaleTeam($saleTeamCode)
    {
        $model = M('cmn_cd', 'tb_ms_');
        $saleTeams = array_column($model->where('CD like "N001290%"')->select(), 'CD_VAL', 'CD');

        return explode('-', $saleTeams [$saleTeamCode])[0];
    }
}
