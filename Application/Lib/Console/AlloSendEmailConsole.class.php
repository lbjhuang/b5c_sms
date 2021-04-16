<?php

/**
 * 调拨异步发送邮件
 * User: b5m
 * Date: 2018/1/22
 * Time: 14:54
 */
class AlloSendEmailConsole extends ConsoleAction
{
    public $mail = null;
    public $sales = null;
    public $warehouses = null;
    public static $error = [];

    public function __construct()
    {
        $this->mail = new ExtendSMSEmail();
        $this->sales = BaseModel::saleTeamCd();
        $this->warehouses = BaseModel::getAllDeliveryWarehouse();
    }

    /**
     * 获取需要发送邮件的调拨数据，如果没有则抛出异常
     * @return mixed
     * @throws Exception
     */
    public function getNeedSendData()
    {
        $model = new TbWmsAlloModel();
        $conditions ['state'] = ['eq', 'N001970100'];
        $conditions ['allo_type'] = ['eq', '1'];
        $conditions ['transfer_type'] = ['eq', '0'];
        $conditions ['_string'] = 'send_mail_state=1 or send_wechat_state=1';
        $ret = $model
            ->where($conditions)
            ->limit(0, 20)
            ->select();
        foreach ($ret as $k => $v) {
            $ret[$k]['child'] = M('allo_child', 'Tb_wms_')
                ->field('sum(demand_allo_num) demand_allo_num,sku_id')
                ->where(['allo_id' => $v['id']])
                ->group('sku_id')
                ->select();
        }
        if (!$ret)
            $this->record(L('无需要发送通知的调拨单'), $conditions);
        return $ret;
    }

    /**
     * 获得我方可售库存
     * @param mixed $query
     * @return int
     */
    public function getOurInventoryForSale($query)
    {
        $model = new SlaveModel();
        $conditions = [];
        $conditions ['t1.sale_team_code'] = ['eq', $query ['sale_team_code']];
        $conditions ['t1.SKU_ID'] = ['in', $query ['sku_id']];
        $conditions ['t2.warehouse_id'] = ['eq', $query ['warehouse_code']];
        $fields = [
            't1.SKU_ID',
            't1.sale_team_code',
            't1.total_inventory',
            'IFNULL(SUM(t1.available_for_sale_num), 0)  as available_for_sale_num_total',
            'SUM(t1.total_inventory) as total_inventory_total',
            't2.warehouse_id'
        ];
        $ret = $model
            ->table('tb_wms_batch t1, tb_wms_stream t2')
            ->field($fields)
            ->group('t1.sale_team_code, t2.warehouse_id, t1.SKU_ID')
            ->where('t1.stream_id = t2.id')
            ->where($conditions)
            ->select();

        return $ret;
    }

    /**
     * 获取商品名称、条形码
     * @param array|string $sku
     * @return mixed
     */
    public function getGudsNmUpc($sku)
    {
        $model = new PmsBaseModel();
        $ret = $model->table('product_sku')->alias('sku')
            ->join('left join product p on p.spu_id = sku.spu_id')
            ->join('left join product_detail pd on pd.spu_id = p.spu_id')
            ->join('left join product_image pe on pe.spu_id = p.spu_id and image_type="N000080200"')
            ->where(['sku.sku_id' => ['in', $sku], 'pd.language' => 'N000920100'])
            ->group('sku.sku_id')
            ->getfield('sku.sku_id,pd.spu_name,sku.upc_id,sku.upc_more,image_url', true);
        return $ret;
    }

    /**
     * 邮件发送
     * @param array $data
     * @return
     */
    public function send($data)
    {
        $action = A('Home/AllocationExtend');
        $agree_url = $this->generateAgreeUrl($data ['id']);
        $refuse_url = $this->generateRefuseUrl($data ['id']);
        $content = $action->get_mail_template($data, $agree_url, $refuse_url);

        return $this->mail->sendEmail($data ['mail'], L('调拨申请'), $content);
    }

    /**
     * 发送微信消息
     * @param $data
     * @return bool
     */
    public function sendWeChat($data)
    {
        $allow_email = explode(',', $data['mail']);
        $allow_man = [];
        foreach ($allow_email as $v) {
            $allow_man[] = explode('@', $v)[0];
        }
        $review = [
            'review_type' => 'WMS',
            'order_id' => $data['id'],
            'order_no' => $data['allo_no'],
            'allowed_man_json' => $allow_man,
            'detail_json' => [
                'data' => [
                    'order_no' => $data['allo_no'],
                    'team' => $data['allo_out_team'] . '->' . $data['allo_in_team'],
                    'warehouse' => $data['allo_out_warehouse'] . '->' . $data['allo_in_warehouse'],
                    'apply' => $data['create_user'],
                    'apply_time' => $data['create_time'],
                ],
                'keys' => [
                    'order_no' => '调拨单号',
                    'team' => '团队',
                    'warehouse' => '仓库',
                    'apply' => '发起人',
                    'apply_time' => '发起时间',
                ],
                'config' => [
                    'view_type' => 'allo',
                    'agree_btn' => 1,
                    'refuse_btn' => 1,
                    'refuse_text' => 0,
                    'agree_text' => 0,
                    'detail_btn' => 0,
                ]
            ],
            'callback_function' => 'weChatApprove',
        ];
        foreach ($data['child'] as $v) {
            $review['detail_json']['data_goods'][] = [
                'spu_name' => $v['GUDS_NM'],
                'image_url' => $v['GUDS_IMG'],
                'demand_allo_num' => $v['demand_allo_num'],
            ];
        }

        $init_language = LanguageModel::getCurrent();
        $cards_key_val = TbHrCardModel::getCardWorkPalce($allow_email);
        foreach ($allow_email as $value) {
            $temp_language = ReviewMsg::getPathToLang($cards_key_val[$value]);
            LanguageModel::setCurrent($temp_language);
            $msg = [
                'tousers' => [$value],
                'textcard' => [
                    'title' => L('调拨申请审批'),
                    'description' => "<div class='normal'>" . L('调拨单号') . "：{$data['allo_no']}</div>
<div class='normal'>" . L('团队') . "：{$data['allo_out_team']}->{$data['allo_in_team']}</div>
<div class='normal'>" . L('仓库') . "：{$data['allo_out_warehouse']}->{$data['allo_in_warehouse']}</div>
<div class='normal'>" . L('发起人') . "：{$data['create_user']}</div>
<div class='normal'>" . L('发起时间') . "：{$data['create_time']}</div>",
                    'btntxt' => L('查看详情')
                ]
            ];
            $sends[] = (new ReviewMsg())->create($review)->send($msg);
        }
        LanguageModel::setCurrent($init_language);
        return $sends;
    }

    public $data;

    /**
     * 入口函数
     */
    public function run()
    {
        $this->data = $this->getNeedSendData();
        if (!empty($this->data)) {
            foreach ($this->data as $key => &$value) {
                static::record($value ['allo_no'], '开始处理调拨单-' . $value ['allo_no']);
                //获取我方可售库存
                $query ['sale_team_code'] = $value ['allo_out_team'];
                $query ['sku_id'] = array_column($value ['child'], 'sku_id');
                $query ['warehouse_code'] = $value ['allo_out_warehouse'];
                $storage = $this->getOurInventoryForSale($query);
                if (is_null($storage)) {
                    static::record($value ['allo_no'], '未查询到可售库存');
                    $value ['code'] = 20001;
                } else {
                    $skuForSaleNum = array_column($storage, 'available_for_sale_num_total', 'SKU_ID');
                    foreach ($value ['child'] as $k => &$v) {
                        $v ['sale_num'] = $skuForSaleNum [$v ['sku_id']];
                        unset($v);
                    }
                    unset($skuForSaleNum);
                    $value ['code'] = 10001;
                }
                //获取sku相关信息
                $skuInfo = $this->getGudsNmUpc(array_column($value ['child'], 'sku_id'));
                foreach ($value ['child'] as $k => &$v) {
                    $v ['GUDS_UPC'] = $skuInfo [$v ['sku_id']]['upc_id'];
                    # 发送邮件 多条形码处理
                    if($skuInfo [$v['sku_id']]['upc_more']) {
                        $upc_more_arr = explode(',', $skuInfo [$v['sku_id']]['upc_more']);
                        array_unshift($upc_more_arr, $skuInfo [$v ['sku_id']]['upc_id']);
                        $v['GUDS_UPC'] = implode("\r\n,", $upc_more_arr);
                    }

                    $v ['GUDS_NM'] = $skuInfo [$v ['sku_id']]['spu_name'];
                    $v ['GUDS_IMG'] = $skuInfo [$v ['sku_id']]['image_url'];
                    unset($v);
                }
                //过滤销售团队
                if (is_null($this->sales [$value ['allo_out_team']])) {
                    static::record($value ['allo_no'], '该调拨中的销售团队未配置相应的code码，无法查询到邮件信息');
                    $value ['code'] = 20002; //错误码20002
                } else {
                    $value ['code'] = 10002;
                }
                //获取调出团队的email
                $email = $this->getEmail((array)$value ['allo_out_team']);
                if (is_null($email)) {
                    static::record($value ['allo_no'], '未查询到' . $this->sales [$value ['allo_out_team']] . '销售团队邮件信息');
                    $value ['code'] = 20003; //错误码20003
                } else {
                    $value ['email'] = $email;
                    $value ['code'] = 10003;
                }
                //数据替换、邮箱、调入团队、调入仓库、调出仓库，转换为中文
                $value ['mail'] = $email [$value ['allo_out_team']];
                $value ['allo_in_team'] = $this->sales [$value ['allo_in_team']];
                $value ['allo_in_warehouse'] = $this->warehouses [$value ['allo_in_warehouse']]['CD_VAL'];
                $value ['allo_out_team'] = $this->sales [$value ['allo_out_team']];
                $value ['allo_out_warehouse'] = $this->warehouses [$value ['allo_out_warehouse']]['CD_VAL'];
                $value ['create_user'] = M('admin', 'bbm_')->where(['M_ID' => $value['create_user']])->getField('M_NAME');
                //发送邮件
                if ($value ['code'] == 10003) {
                    if ($value['transfer_type'] == 0 && $value['send_mail_state'] == 1) {
                        if ($this->send($value)) {
                            static::record($value ['allo_no'], '邮件发送成功');
                            static::record($value ['allo_no'], '开始更新调拨单邮件发送状态');
                            $ret = $this->updateSendMialState((array)$value ['id']);
                            if ($ret > 0) {
                                $value ['code'] = 20000;
                                static::record($value ['allo_no'], '调拨单邮件发送状态更新成功', 2);
                            } else {
                                static::record($value ['allo_no'], '调拨单邮件发送状态更新失败', 2);
                            }
                        } else {
                            static::record($value ['allo_no'], $email . '邮件系统异常：' . $this->mail->ErrorInfo, 2);
                            $value ['code'] = 20004;
                        }
                    }
                    if ($value['transfer_type'] == 0 && $value['send_wechat_state'] == 1) {
                        if ($this->sendWeChat($value)) {
                            static::record($value ['allo_no'], '微信消息发送成功');
                            static::record($value ['allo_no'], '开始更新调拨单微信发送状态');
                            $ret = $this->updateSendWeChatState((array)$value ['id']);
                            if ($ret > 0) {
                                $value ['code'] = 20000;
                                static::record($value ['allo_no'], '调拨单微信发送状态更新成功', 2);
                            } else {
                                static::record($value ['allo_no'], '调拨单微信发送状态更新失败', 2);
                            }
                        } else {
                            static::record($value ['allo_no'], $email . '微信消息发送异常', 2);
                            $value ['code'] = 20005;
                        }
                    }
                } else {
                    static::record($value ['allo_no'], '');
                }

            }
        } else {
            static::record(null, date('Y-m-d H:i:s', time()) . '开始发送调拨确认邮件');
            static::record(null, date('Y-m-d H:i:s', time()) . '无需要确认的调拨数据，流程结束', 2);
        }

        static::saveLog();

        return;
    }

    public static $log;

    public static function record($key, $msg, $mode = null)
    {
        if (!isset(static::$log [$key]) and $mode == null) {
            $mode = 1;
        } elseif ($mode == null) {
            $mode = 3;
        }
        if ($mode == 1)
            $msg = '┏----------' . $msg . '----------┓';
        elseif ($mode == 2)
            $msg = '┗----------' . $msg . '----------┛';
        else
            $msg = '┠' . $msg;

        static::$log [$key][] = $msg;
    }

    public static function saveLog()
    {
        $now = date('Ymd', time());
        $destination = '/opt/logs/logstash/' . $now . '_sendMail.log';
        file_put_contents($destination, date('Y-m-d H:i:s', time()) . ' ' . get_client_ip() . ' - ' . $_SERVER ['SERVER_ADDR'] . ' ' . $_SERVER['REQUEST_URI'] . "\n" . print_r(self::$log, true), FILE_APPEND);
        static::$log = array();
    }

    /**
     * 更新邮件发送状态
     *
     */
    public function updateSendMialState($ids)
    {
        $model = new Model();
        $conditions ['id'] = ['in', $ids];
        $data ['send_mail_state'] = 2;
        $num = $model->table('tb_wms_allo')
            ->where($conditions)
            ->save($data);

        return $num;
    }

    /**
     * 更新邮件发送状态
     *
     */
    public function updateSendWeChatState($ids)
    {
        $model = new Model();
        $conditions ['id'] = ['in', $ids];
        $data ['send_wechat_state'] = 2;
        $num = $model->table('tb_wms_allo')
            ->where($conditions)
            ->save($data);

        return $num;
    }

    /**
     * 错误记录
     * @param $msg
     * @param $data
     */
    public function recorde($msg, $data)
    {
        $tmp = [];
        $tmp ['msg'] = $msg;
        $tmp ['data'] = $data;
        self::$error [] = $tmp;
    }

    /**
     * @param $allo
     * @param $mail
     * @param string $token
     */
    public function sendMail($allo, $mail, $token)
    {
        $agree_url = $this->generateAgreeUrl($token);
        $refuse_url = $this->generateRefuseUrl($token);
        $action = A('Home/AllocationExtend');
        $content = $action->get_mail_template($allo, $agree_url, $refuse_url);
        if (!$this->mail->sendEmail($mail, L('调拨申请'), $content)) {
            $this->record(L('调拨单邮件发送失败'), ['id' => $allo ['child'], 'phpmailerException' => $this->mail->_error]);
        } else {
            return;
        }
    }

    /**
     * 销售团队邮箱
     * @param $saleTeamCodes
     * @return mixed
     *
     */
    public function getEmail($saleTeamCodes)
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $conditions ['CD'] = ['IN', array_unique($saleTeamCodes)];
        $ret = $model->where($conditions)->getField('CD, ETC');
        return $ret;
    }

    /**
     * 生成同意链接
     * @params $guid 全局唯一标示
     * @return String url
     */
    public function generateAgreeUrl($guid)
    {
        $web_site = C('redirect_audit_addr');
        $params = [
            'm' => 'AllocationExtend',
            'a' => 'agree',
            'hash' => $guid,
            'timestamp' => time(),
            'confirm' => 'agree'
        ];
        $web_site .= '/index.php?' . http_build_query($params);

        return $web_site;
    }

    /**
     * 生成拒绝链接
     * @params $guid 全局唯一标示
     * @return String url
     */
    public function generateRefuseUrl($guid)
    {
        $web_site = C('redirect_audit_addr');
        $web_site = 'erp.gshopper.com';
        $params = [
            'm' => 'AllocationExtend',
            'a' => 'disagree',
            'hash' => $guid,
            'timestamp' => time(),
            'confirm' => 'disagree'
        ];
        $web_site .= '/index.php?' . http_build_query($params);

        return $web_site;
    }
}