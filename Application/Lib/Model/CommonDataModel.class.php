<?php
/**
 * 接口获取基础数据
 * User: b5m
 * Date: 2018/2/5
 * Time: 11:11
 */

class CommonDataModel extends Model
{
    public static $currency;

    //易达回邮单退货服务编号映射
    public static $return_service = [
        ['ReturnServiceNo' => 'W','ReturnServiceName' => '重新上架','ItemNumber' => 'a','ItemName' => '清理','Fee' => '1','Comments' => ''],
        ['ReturnServiceNo' => 'W','ReturnServiceName' => '重新上架','ItemNumber' => 'b','ItemName' => '换包装','Fee' => '1','Comments' => '另加包装盒材料费'],
        ['ReturnServiceNo' => 'W','ReturnServiceName' => '重新上架','ItemNumber' => 'c','ItemName' => '上架','Fee' => '0','Comments' => ''],
        ['ReturnServiceNo' => 'Z','ReturnServiceName' => '增值服务','ItemNumber' => 'a','ItemName' => '换配件','Fee' => '2','Comments' => '5欧封顶'],
        ['ReturnServiceNo' => 'Z','ReturnServiceName' => '增值服务','ItemNumber' => 'b','ItemName' => '拆卸重组装','Fee' => '2','Comments' => '8欧封顶'],
        ['ReturnServiceNo' => 'Z','ReturnServiceName' => '增值服务','ItemNumber' => 'c','ItemName' => '其他','Fee' => '1','Comments' => '8欧封顶'],
        ['ReturnServiceNo' => 'M','ReturnServiceName' => '销毁','ItemNumber' => 'a','ItemName' => '特殊产品','Fee' => '0.1','Comments' => '2欧封顶'],
        ['ReturnServiceNo' => 'M','ReturnServiceName' => '销毁','ItemNumber' => 'b','ItemName' => '一般产品','Fee' => '0','Comments' => '0'],
        ['ReturnServiceNo' => 'C','ReturnServiceName' => '退回中国','ItemNumber' => 'a','ItemName' => '单独快递','Fee' => '','Comments' => '实报实销'],
        ['ReturnServiceNo' => 'C','ReturnServiceName' => '退回中国','ItemNumber' => 'b','ItemName' => '拼箱空运','Fee' => '','Comments' => '实报实销'],
        ['ReturnServiceNo' => 'C','ReturnServiceName' => '退回中国','ItemNumber' => 'c','ItemName' => '拼箱海运','Fee' => '','Comments' => '实报实销'],
        ['ReturnServiceNo' => 'C','ReturnServiceName' => '退回中国','ItemNumber' => 'd','ItemName' => '特殊处理','Fee' => '','Comments' => '实报实销'],
        ['ReturnServiceNo' => 'P','ReturnServiceName' => '图片服务','ItemNumber' => 'a','ItemName' => 'SKU图片','Fee' => '0.5','Comments' => '一组5张'],
        ['ReturnServiceNo' => 'P','ReturnServiceName' => '图片服务','ItemNumber' => 'b','ItemName' => '3C类产品','Fee' => '','Comments' => ''],
        ['ReturnServiceNo' => 'T','ReturnServiceName' => '指定产品的硬件检测','ItemNumber' => 'a','ItemName' => '仅限平衡车、手机、扫地机器人三类产品','Fee' => 'EUR5.80','Comments' => '仅限平衡车、手机、扫地机器人三类产品硬件检测（不包含软件检测和维修）'],
    ];

    public static $reply_status = [
        '0' => '获取成功',
        '1' => '获取中',
        '2' => '获取失败',
    ];

    /**
     * 币种
     */
    public static function currency()
    {
        if (static::$currency) return static::$currency;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where('CD like "N00059%"')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$currency = $ret;
    }

    public static $currency_Y;

    /**
     * 物流公司
     */
    public static function currencyOpen()
    {
        if (static::$currency_Y) return static::$currency_Y;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00059%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$currency_Y = $ret;
    }

    public static $currencyExtend;

    /**
     * 币种
     */
    public static function currencyExtend()
    {
        if (static::$currencyExtend) return static::$currencyExtend;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->where('CD like "N00059%"')->getField('CD as cd, CD_VAL as cdVal');
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$currencyExtend = $ret;
    }

    public static $company;

    /**
     * 公司
     */
    public static function company()
    {
        if (static::$company) return static::$company;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00124%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$company = $ret;
    }

    public static $company_open;

    /**
     * 公司(开启)
     */
    public static function companyOpen()
    {
        if (static::$company_open) return static::$company_open;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00124%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$company_open = $ret;
    }

    public static $conCompanyCd;
    // 我方公司
    public static function conCompanyCd()
    {
        if (static::$conCompanyCd) return static::$conCompanyCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00124%'];
        $ret = $model->where($conditions)->select();
        return static::$conCompanyCd = array_column($ret, 'CD_VAL', 'ETC');
    }

    public static $transfer;

    /**
     * 流水类型（转账类型、收支方向）
     */
    public static function transfer()
    {
        if (static::$transfer) return static::$transfer;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00195%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$transfer = $ret;
    }

    public static $account;

    /**
     * 账户类型
     */
    public static function account()
    {
        if (static::$account) return static::$account;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N001930%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$account = $ret;
    }

    public static $turnOver;

    /**
     * 转账状态
     */
    public static function turnOver()
    {
        if (static::$turnOver) return static::$turnOver;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00194%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->order('SORT_NO asc')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$turnOver = $ret;
    }

    public static $accoutListState;

    /**
     * 账户列表启动停用状态
     */
    public static function accountListState()
    {
        return [
            [
                'cd' => 1,
                'cdVal' => L('启用中')
            ],
            [
                'cd' => 2,
                'cdVal' => L('已停用')
            ]
        ];
    }

    public static $transferType;

    /**
     * 资金划转类型
     */
    public static function transferType()
    {
        if (static::$transferType) return static::$transferType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00199%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$transferType = $ret;
    }

    public static $auditPerson;

    /**
     * 审批人获取
     */
    public static function auditPerson()
    {
        if (static::$transferType) return static::$transferType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N002000%'];
        $ret = $model->field('CD as cd, ETC, SORT_NO')->order('SORT_NO asc')->where($conditions)->select();
        return static::$transferType = $ret;
    }

    /**
     * 当前审批人中文
     */
    public static function currentAuditor()
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N002000%'];
        $ret = $model->field('CD as cd, ETC3, SORT_NO')->order('SORT_NO asc')->where($conditions)->select();
        return $ret;
    }

    public static $failStatus;

    /**
     * 状态
     */
    public static function failStatus()
    {
        if (static::$failStatus) return static::$failStatus;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00209%'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        return static::$failStatus = $ret;
    }

    /**
     * 销售团队
     *
     */
    public static function saleTeams()
    {
        return BaseModel::saleTeamCdCur();
    }

    /**
     * 采购团队
     *
     */
    public static function purTeams()
    {
        return BaseModel::spTeamCdExtend();
    }

    /**
     * 店铺
     */
    public static function stores()
    {
        return BaseModel::getStoreName();
    }

    /**
     * 国家-地区
     */
    public static function country()
    {
        $model = M('_ms_user_area', 'tb_');
        $ret = $model->field('ID as id, CONCAT(two_char, zh_name) AS NAME, parent_no')->where('parent_no = 0')->select();
        return $ret;
        //return BaseModel::getCountry();
    }

    /**
     * 仓库
     */
    public static function warehouses()
    {
        return BaseModel::getAllDeliveryWarehouse();
    }

    /**
     * 退货仓库
     */
    public static function return_warehouses()
    {
        return BaseModel::getReturnDeliveryWarehouse();
    }

    /**
     * 易达回邮单退货服务编号映射
     */
    public static function return_service()
    {
        $data = json_decode(RedisModel::get_key('return_service' . date('Y-m-d')), true);
        if (!$data) {
            $res = (new ApiModel())->getReturnService();
            if (200 == $res['code']) {
                //解析xml
                $data = json_decode(json_encode(simplexml_load_string($res['data'], 'SimpleXMLElement', LIBXML_NOCDATA), true), true)['GetReturnServiceResponse']['ReturnService'];
                RedisModel::set_key('return_service' . date('Y-m-d'), json_encode($data));
            }
            if (500 == $res['code']) {
                $data = [];
            }
        }
        $return_service = [];
        foreach ($data as $item) {
            if (!isset($item['ItemList']['Item']['ItemNumber'])) {
                foreach ($item['ItemList']['Item'] as $value) {
                    $list = [];
                    $list['ReturnServiceName'] = $item['ReturnServiceName'];
                    $list['ReturnServiceNo'] = $item['ReturnServiceNo'];
                    $list['ItemNumber'] = $value['ItemNumber'];
                    $list['ItemName'] = $value['ItemName'];
                    $list['Fee'] = is_array($value['Fee']) && empty($value['Fee']) ? '' : $value['Fee'];
                    $list['Comments'] = is_array($value['Comments']) && empty($value['Comments']) ? '' : $value['Comments'];
                    $return_service[] = $list;
                }
            } else {
                $value = $item['ItemList']['Item'];
                $list = [];
                $list['Comments'] = $item['Comments'];
                $list['ReturnServiceName'] = $item['ReturnServiceName'];
                $list['ReturnServiceNo'] = $item['ReturnServiceNo'];
                $list['ItemNumber'] = $value['ItemNumber'];
                $list['ItemName'] = $value['ItemName'];
                $list['Fee'] = is_array($value['Fee']) && empty($value['Fee']) ? '' : $value['Fee'];
                $list['Comments'] = is_array($value['Comments']) && empty($value['Comments']) ? '' : $value['Comments'];
                $return_service[] = $list;
            }
        }
        return $return_service;
    }

    public static $allPlat;

    /**
     * 获取所有的平台
     *
     */
    public static function platform()
    {
        if (static::$allPlat) {
            return static::$allPlat;
        }
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD, CD_VAL')->order('SORT_NO asc')->where('CD like "N00083%" and USE_YN = "Y"')->select();   //获取平台数据WORK_NUM
        $ret = array_column($ret, 'CD_VAL', 'CD');
        return static::$allPlat = $ret;
    }

    /**
     * 包裹类型
     */
    public static function gudsType()
    {
        return [
            0 => L('单品单数'),
            1 => L('单品多数'),
            2 => L('多品混包')
        ];
    }

    /**
     * 拣货单排序方式
     *
     * @param null $type
     *
     * @return array
     */
    public static function pickingSortType($type = null)
    {
        switch ($type) {
            case 'sorting':
                $res_arr = [
                    'orderTime' => L('按下单时间'),
                    'orderPayTime' => L('按付款时间'),
                    'sendOrdTime' => L('按派单时间'),
                    'remarkMsgPinyin' => L('按备注排序')
                ];
                break;
            case 'outstorage_sort':
                $res_arr = [
                    'orderTime' => L('按下单时间'),
                    'orderPayTime' => L('按付款时间'),
                    'shippingTime' => L('按平台发货时间'),
                    'sendOrdTime' => L('按派单时间'),
                    //'msOrd.sTime4.keyword' => L('按出库时间')
                    'msOrd.sendoutTime.keyword' => L('按出库时间')
                ];
                break;
            default:
                $res_arr = [
                    'orderTime' => L('按下单时间'),
                    'orderPayTime' => L('按付款时间'),
                    'shippingTime' => L('按平台发货时间'),
                    'sendOrdTime' => L('按派单时间')
                ];
        }
        return $res_arr;
    }

    /**
     * 仓库管理页，SKU编码、条形码选项
     *
     * @var
     * @return array
     */
    public static function upcSku()
    {
        return [
            't1.GSKU' => L('SKU编码'),
            't5.upc_id' => L('条形码')
        ];
    }

    public static $logisticsCompany;

    /**
     * 物流公司
     */
    public static function logisticsCompany()
    {
        if (static::$logisticsCompany) return static::$logisticsCompany;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00070%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$logisticsCompany = $ret;
    }

    public static $logisticsType;

    /**
     * 物流方式
     */
    public static function logisticsType($where = [])
    {
        if (static::$logisticsType) return static::$logisticsType;
        $model = M('_ms_logistics_mode', 'tb_');
//        $conditions ['CD'] = ['like', '%N001730%'];
//        $conditions ['USE_YN'] = ['eq', 'Y'];
        $where['IS_DELETE'] = 0;
        $where['IS_ENABLE'] = 1;
        $ret = $model->field('ID as id, LOGISTICS_MODE as logisticsMode')->where($where)->select();
        return static::$logisticsType = $ret;
    }

    /**
     * 拣货列表时间范围索引
     * 输入框索引
     */
    public static function pickingTimeRangeIndex($type = null)
    {
        if ($type == 'outstorage_sort') {
            $range['keyWordRange'] = [
                [
                    "orderTime" => L("下单时间"),
                    "orderPayTime" => L("付款时间"),
                    "shippingTime" => L("平台发货时间"),
//                    "msOrd.sTime4" => L("平台发货时间"),
                    "sendOrdTime" => L("派单时间"),
                    "msOrd.sendoutTime.keyword" => L("出库时间"),
//                    "userEmail" => L('收件人邮箱'),
                ]
            ];
        } else {
            $range['keyWordRange'] = [
                [
                    "orderTime" => L("下单时间"),
                    "orderPayTime" => L("付款时间"),
                    "shippingTime" => L("平台发货时间"),
//                    "msOrd.sTime4" => L("平台发货时间"),
                    "sendOrdTime" => L("派单时间"),
//                    "userEmail" => L('收件人邮箱'),
                ]
            ];
        }
        $data = [
            'keyWordInput' => [
                [
                    "b5cOrderNo" => L("订单号"),
                    "ordPackage.trackingNumber" => L('运单号'),
                    "ordGudsOpt.gudsOptId" => L('SKU ID'),
                    "orderId" => L('第三方订单 ID'),
                    "orderNo" => L('第三方订单号'),
                    "userEmail" => L('收件人邮箱'),
                ]
            ],
            'keyWordInputApart' => [
                [
                    "b5cOrderNo" => L("订单号"),
                    "ordPackage.trackingNumber" => L('运单号'),
                    "ordGudsOpt.gudsOptId" => L('SKU ID'),
                    "orderId" => L('第三方订单 ID'),
                    "orderNo" => L('第三方订单号'),
                    "msOrd.pickingNo" => L('拣货号'),
                    "userEmail" => L('收件人邮箱'),
                ]
            ],

        ];
        return array_merge($data, $range);
    }

    public static $surfaceWayGet;

    /**
     * 面单获取方式
     */
    public static function surfaceWayGet()
    {
        if (static::$surfaceWayGet) return static::$surfaceWayGet;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00201%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$surfaceWayGet = $ret;
    }

    public static $systemDocking;

    /**
     * 发货系统
     */
    public static function systemDocking()
    {
        if (static::$systemDocking) return static::$systemDocking;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N002040%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$systemDocking = $ret;
    }


    public static $logicStatus;

    /**
     * 物流状态
     */
    public static function logicStatus()
    {
        if (static::$logicStatus) return static::$logicStatus;
        $fields = [
            'CD as cd',
            'CD_VAL as cdVal',
            'ETC as etc',
            'SORT_NO as sortNo'
        ];
        $model = M('_ms_cmn_cd', 'tb_');
        $subQuery [] = [
            'CD' => ['like', 'N00088%'],
        ];
        $subQuery ['_logic'] = 'or';
        $subQuery ['CD'] = ['like', 'N00127%'];

        $conditions ['USE_YN'] = ['eq', 'Y'];
        $conditions [] = $subQuery;
        $ret = $model->field($fields)->order('SORT_NO asc')->where($conditions)->select();
        if ($ret) {
            $r = [];
            foreach ($ret as $key => $value) {
                if ($value ['etc'] == 1) {
                    $r ['success'][] = $value;
                } else
                    $r ['fail'][] = $value;
            }
        }
        foreach ($r ['success'] as $key => &$value) {
            $value ['cdVal'] = L($value ['cdVal']);
        }
        foreach ($r ['fail'] as $key => &$value) {
            $value ['cdVal'] = L($value ['cdVal']);
        }
        static::$logicStatus = $r;
        return static::$logicStatus;
    }

    public static function weight()
    {
        return [
            1 => L('未称重'),
            2 => L('已称重')
        ];
    }

    public static $relationType;

    /**
     * 关联单据
     */
    public static function relationType()
    {
        if (static::$relationType) return static::$relationType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '%N00235%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$relationType = $ret;
    }

    public static $inStorage;

    /**
     * 入库类型
     */
    public static function inStorage()
    {
        if (static::$inStorage) return static::$inStorage;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00094%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$inStorage = $ret;
    }

    public static $freightType;

    /**
     * 运费类型
     */
    public static function freightType()
    {
        if (static::$freightType) return static::$freightType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N000179%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$freightType = $ret;
    }

    public static $outStorage;

    /**
     * 出库类型
     */
    public static function outStorage()
    {
        if (static::$outStorage) return static::$outStorage;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N000950%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$outStorage = $ret;
    }

    /**
     * 出入库类型（出库、入库）
     */
    public static function outgoingType()
    {
        return [
            0 => L('出库'),
            1 => L('入库')
        ];
    }

    public static $users;

    public static function users()
    {
        if (static::$users) return static::$users;
        $model = new Model();
        $ret = $model->table('bbm_admin')->field('M_ID as mId, M_NAME as mName, EMP_SC_NM as empScNm')->order('M_ID asc')->select();
        return static::$users = $ret;
    }

    public static function area($parentId = 0)
    {
        $model = new Model();
        $conditions = null;
        if (is_null($parentId)) {
            $conditions ['PARENT_IDS'] = ['eq', 0];
        } else {
            $conditions ['PARENT_IDS'] = ['eq', $parentId];
        }
        $ret = $model->table('tb_crm_site')
            ->field('CASE PARENT_ID WHEN 0 THEN CONCAT("(", RES_NAME, ")", NAME) ELSE NAME END AS resName, ID as id, CASE PARENT_ID WHEN 0 THEN CONCAT("(", RES_NAME, ")", NAME_EN) ELSE NAME_EN END AS resNameEn')
            ->where($conditions)
            ->order('SORT ASC')
            ->select();

        return $ret;
    }

    public static $jobContent;

    public static function jobContent()
    {
        if (static::$jobContent) return static::$jobContent;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N002060%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$jobContent = $ret;
    }

    public static $forwardingCompanyCd;

    public static function forwardingCompanyCd()
    {
        if (static::$forwardingCompanyCd) return static::$forwardingCompanyCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00239%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$forwardingCompanyCd = $ret;
    }

    public static $buttItemCd;

    public static function buttItemCd()
    {
        if (static::$buttItemCd) return static::$buttItemCd;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00240%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$buttItemCd = $ret;
    }

    public static $virType;

    public static function virType()
    {
        if (static::$virType) return static::$virType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00244%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$virType = $ret;
    }

    public static $inventoryType;

    public static function inventoryType()
    {
        if (static::$virType) return static::$virType;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00248%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->order('SORT_NO asc')->where($conditions)->select();
        return static::$virType = $ret;
    }

    public static $bwcOrderStatus;

    public static function bwcOrderStatus()
    {
        return self::basis('bwcOrderStatus', 'N00055');
    }

    public static $purchasingTeam;

    public static function purchasingTeam()
    {
        return self::basis('purchasingTeam', 'N00129');
    }

    public static $warehouseOperator;

    public static function warehouseOperator()
    {
        return self::basis('warehouseOperator', 'N00270');
    }

    public static $warehouseType;

    public static function warehouseType()
    {
        return self::basis('warehouseType', 'N00259');
    }

    /**
     * @param $cache_key
     * @param $str
     *
     * @return mixed
     */
    private static function basis($cache_key, $str)
    {
        if (static::$$cache_key) return static::$$cache_key;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', '' . $str . '%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal,SORT_NO as sortNo, ETC as comment, ETC2 as comment2, ETC3 as comment3')
            ->order('SORT_NO asc,CD asc')
            ->where($conditions)
            ->select();
        return static::$$cache_key = $ret;
    }


    /**
     * @param $str
     *
     * @return mixed
     */
    private static function getSubBasis($str, $column = 'ETC3')
    {
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['_string'] = self::getLikeOrQuery($str, $column);
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal,SORT_NO as sortNo, ETC2 as comment2, ETC3 as comment3')
            ->order('SORT_NO asc,CD asc')
            ->where($conditions)
            ->select();
        return $ret;
    }

    /**
     * @param $arr
     * @param $field
     *
     * @return mixed
     */
    public static function getLikeOrQuery($arr, $field)
    {
        foreach ($arr as $item) {
            $list[] =  $field . ' like "%' . $item . '%" ';
        }
        $str = '(' . implode(' or ' , $list) . ')';
        return $str;
    }

    /**
     * 流水预分方向
     * 收款类型 客户打款/平台提现/退税收款/其它
     *
     * @return type object
     */
    public static $receipt;

    public static function collectionType()
    {
        if (static::$receipt) return static::$receipt;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00252%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$receipt = $ret;
    }

    /**
     * 佣金配置-维度
     * @return type object
     */
    public static $dimension;

    public static function dimension()
    {
        if (static::$dimension) return static::$dimension;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00260%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$dimension = $ret;
    }

    /**
     * 佣金配置-计算
     * @return type object
     */
    public static $calculate;

    public static function judge()
    {
        if (static::$calculate) return static::$calculate;
        $model = M('_ms_cmn_cd', 'tb_');
        //$conditions ['CD'] = ['like', 'N00261%'];
        $conditions ['CD'] = ['in', ['N002610100' ,'N002610200']];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$calculate = $ret;
    }

    /**
     * 佣金配置-计算
     * @return type object
     */
    public static $calculate2;

    public static function judge2()
    {
        if (static::$calculate2) return static::$calculate2;
        $model = M('_ms_cmn_cd', 'tb_');
        $conditions ['CD'] = ['like', 'N00261%'];
        $conditions ['USE_YN'] = ['eq', 'Y'];
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where($conditions)->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$calculate = $ret;
    }

    public static $siteCd;

    public function siteCd()
    {
        return self::basis('siteCd', 'N00262');
    }

    public static $plannedTransportationChannelCds;

    public function plannedTransportationChannelCds()
    {
        return self::basis('plannedTransportationChannelCds', 'N00282');
    }
    public static $insurance_claims_cd_map;

    public function getInsuranceClaimsCdMap()
    {
        return self::basis('insurance_claims_cd_map', 'N00283');
    }
    public static $insurance_coverage_cd_map;

    public function getInsuranceCoverageCdMap()
    {
        return self::basis('insurance_coverage_cd_map', 'N00284');
    }

    public static $goods_type_cd;

    public function goodsTypeCd()
    {
        return static::$goods_type_cd = CodeModel::getGoodsTypeCode();
    }


    /**
     * 收款节点类型
     */
    public static $nodeType;

    public static function nodeType()
    {
        if (static::$nodeType) return static::$nodeType;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where('CD like "N00139%"')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$nodeType = $ret;
    }

    /**
     * 收款节点天数
     */
    public static $nodeDate;

    public static function nodeDate()
    {
        if (static::$nodeDate) return static::$nodeDate;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD as cd, CD_VAL as cdVal')->where('CD like "N00142%"')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$nodeDate = $ret;
    }

    /**
     * 商标
     */
    public static function trademark()
    {
        $model = M('_trademark_base', 'tb_');
        $ret = $model->field('id, trademark_name AS NAME, img_url')->where('is_delete_state = 0')->select();
        return $ret;
    }

    /**
     * 商标类型
     */
    public static $trademarkType;
    public static function trademarkType()
    {
        if (static::$trademarkType) return static::$trademarkType;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD as cd, CD_VAL as cdVal, ETC as cdVal_en')->where('CD like "N00297%"')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$nodeType = $ret;
    }

    /**
     * 商标注册当前状态
     */
    public static $currentType;
    public static function currentType()
    {
        if (static::$currentType) return static::$currentType;
        $model = M('_ms_cmn_cd', 'tb_');
        $ret = $model->field('CD as cd, CD_VAL as cdVal, ETC as cdVal_en')->where('CD like "N00298%"')->select();
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value ['cdVal'] = L($value ['cdVal']);
                unset($value);
            }
        }
        return static::$nodeType = $ret;
    }

    /**
     * 国家
     */
    public static $areaCode;
//    public static function areaCode()
//    {
//        if (static::$areaCode) return static::$areaCode;
//        $area_names = ['中国','中国香港','越南','德国','俄罗斯','法国','比利时','新加坡','美国','韩国','日本','荷兰','英国','印度','卢森堡','澳大利亚','欧盟','比荷卢联盟','西班牙','意大利'];
//        $model = M('_ms_user_area', 'tb_');
//        $ret = $model->field('id, zh_name NAME')
//            ->where(['zh_name' => ['in', $area_names]])
//            ->group('NAME')
//            ->select();
//        return $ret;
//    }
    public static function areaCode()
    {
        if (static::$areaCode) return static::$areaCode;
        $model = M('_ms_user_area', 'tb_');
        $ret = $model->field('id, zh_name NAME')
            ->where(['parent_no' => 0])
            ->select();
        return $ret;
    }

    /**
     * 国家
     */
//    public static $country_info;
//    public static function getCountry()
//    {
//        if (static::$country_info) return static::$country_info;
//        $model = M('_ms_user_area', 'tb_');
//        $ret = $model->field('id, zh_name NAME')
//            ->where(['parent_no' => 0])
//            ->select();
//        return $ret;
//    }

    /**
     * 在库天数级别
     */
    public static function existedDaysLevel()
    {
        return StandingExistingModel::$existed_days_level;
    }

    public static $review_type_cd_map;
    public static function reviewType(){
        return self::basis('review_type_cd_map', 'N00300');
    }

    public static $change_type_cd_map;
    public static function changeType(){
        return self::basis('change_type_cd_map', 'N00299');
    }

    /**
     * 账号平台/支付渠道
     */
    public static $payment_channel;
    public static function paymentChannel()
    {
        return self::basis('payment_channel', 'N00100');
    }

    /**
     * 支付方式
     */
    public static $payment_method;
    public static function paymentMethod()
    {
        return self::basis('payment_method', 'N00302');
    }

    /**
     * 会计审核退回原因
     */
    public static $accounting_return_reason;
    public static function accountingReturnReason()
    {
        return self::basis('accounting_return_reason', 'N00308');
    }

    public static $billStatus;

    /**
     * 获取所有的账单状态
     *
     */
    public static $bill_status;
    public static function billStatus()
    {
        return self::basis('bill_status', 'N00318');
    }

    /**
     * 获取所有的销售渠道（站点）
     *
     */
    public static $sale_channel;
    public static function saleChannel()
    {
        return self::basis('sale_channel', 'N00083');
    }

    /**
     * 获取所有的收入成本来源渠道（站点）
     *
     */
    public static $income_cost_sale_channel;
    public static function incomeCostSourceChannelCode()
    {
        return self::basis('income_cost_sale_channel', 'N00281');
    }

    public static function subBasis($platformCode, $column = 'ETC3')
    {
        return self::getSubBasis($platformCode, $column);
    }

    /**
     * 获取所有的交易类型
     *
     */
    public static $transaction_type;
    public static function transactionType()
    {
        return self::basis('transaction_type', 'N00333');
    }

    /**
     * 获取所有的收款账户种类
     *
     */
    public static $collection_account_type;
    public static function collectionAccountType()
    {
        return self::basis('collection_account_type', 'N00334');
    }

    /**
     * 获取合同信息
     *
     */
    public static $contract_info;
    public static function contractInfo($params)
    {
        if (static::$contract_info) return static::$contract_info;
        if (empty($params['SP_NAME'])) return [];
        $contract = D('TbCrmContract');
        //申请付款请求
        if (isset($params['CON_COMPANY_CD'])) {
            $conditions ['CD'] = $params['CON_COMPANY_CD'];
            $ret = M('_ms_cmn_cd', 'tb_')->field('CD , ETC')->where($conditions)->find();if (!empty($ret)) {
                //一般付款申请：传的是code $params['CON_COMPANY_CD'] = ETC
                $params['CON_COMPANY_CD'] = (int)$ret['ETC'] + 1;
                $condition = $contract->searchModel($params);
                #新增合同过期时间验证
                $tmpWhere = "IS_RENEWAL = 0 or (IS_RENEWAL = 1 and END_TIME >= '%s' )";#是否自动续约，1-否；0-是；
                $ret = $contract->distinct(true)->field('tb_crm_contract.*,tb_crm_sp_supplier.BANK_SETTLEMENT_CODE,tb_crm_sp_supplier.CITY,tb_crm_sp_supplier.BANK_ADDRESS,tb_crm_sp_supplier.ACCOUNT_CURRENCY')->where($condition)->where($tmpWhere,date('Y-m-d 00:00:00'))->order('CREATE_TIME desc')
                    ->join('left join tb_crm_sp_supplier on tb_crm_contract.SP_CHARTER_NO = tb_crm_sp_supplier.SP_CHARTER_NO')
                    ->select();
                return $ret;
            }
        }
        //$condition = $contract->searchModel($params);
        $condition = [];
        //empty($params['SP_NAME']) or $condition['tb_crm_sp_supplier.SP_NAME'] = $params['SP_NAME'];
        empty($params['SP_NAME']) or $condition['_string'] = '(tb_crm_contract.SP_NAME = "' . $params['SP_NAME'] . '" or tb_crm_sp_supplier.SP_NAME = "' . $params['SP_NAME'] . '")';
        //isset($params['CON_COMPANY_CD']) && $params['CON_COMPANY_CD'] !== '' and $condition['tb_crm_contract.CON_COMPANY_CD'] = $params['CON_COMPANY_CD'];
        $ret = $contract->where($condition)->order('tb_crm_contract.CREATE_TIME desc')
            ->join('left join tb_crm_sp_supplier on tb_crm_contract.SP_CHARTER_NO = tb_crm_sp_supplier.SP_CHARTER_NO')
            ->select();
        return static::$contract_info = $ret;
    }

    /**
     * 获取资金调配合同 (申请付款请求)
     *
     */
    public static $fund_allocation_contract;
    public static function fundAllocationContract($params)
    {
        //CON_COMPANY_CD 我方公司cd或供应商id  CON_NAME CON_COMPANY_CD
        //PAY_COMPANY_CD 付款公司cd或供应商id  CON_NAME CON_COMPANY_CD
        $k = $params['PAY_COMPANY_CD'] . '-' . $params['CON_COMPANY_CD'];
        if (static::$fund_allocation_contract[$k]) return static::$fund_allocation_contract[$k];
        if (empty($params['PAY_COMPANY_CD']) || empty($params['CON_COMPANY_CD'])) return [];
        $contract = D('TbCrmContract');
        $conditions ['CD'] = $params['CON_COMPANY_CD'];
        $ret = M('_ms_cmn_cd', 'tb_')->field('CD, CD_VAL, ETC')->where($conditions)->find();
        $conditions ['CD'] = $params['PAY_COMPANY_CD'];
        $ret2 = M('_ms_cmn_cd', 'tb_')->field('CD, CD_VAL, ETC')->where($conditions)->find();
        //一般付款申请：传的是code $params['CON_COMPANY_CD'] = ETC
        $ETC = $ret ?(int)$ret['ETC'] : '';
        $ETC2 = $ret2 ? (int)$ret2['ETC'] : '';
        if ($ret && $ret2) {
            //传值两个都是我方公司
            $sp_name = $ret['CD_VAL'];
            $sp_name2 = $ret2['CD_VAL'];
            $where_string=" ((tb_crm_sp_supplier.SP_NAME = '{$sp_name2}' and CON_COMPANY_CD = '{$ETC}') or (tb_crm_sp_supplier.SP_NAME = '{$sp_name}' and CON_COMPANY_CD = '{$ETC2}') or 
              (tb_crm_contract.SP_NAME = '{$sp_name2}' and CON_COMPANY_CD = '{$ETC}') or (tb_crm_contract.SP_NAME = '{$sp_name}' and CON_COMPANY_CD = '{$ETC2}')) ";
        } else {
            //传值一个供应商一个我方公司
            $where_string=" (tb_crm_sp_supplier.ID = '{$params['PAY_COMPANY_CD']}' and CON_COMPANY_CD = '{$ETC}') or (tb_crm_sp_supplier.ID = '{$params['CON_COMPANY_CD']}' and CON_COMPANY_CD = '{$ETC2}')";
        }
        $condition = array('_complex'=> $where_string);
        #新增合同过期时间验证
        $tmpWhere = " (IS_RENEWAL = 0 or (IS_RENEWAL = 1 and END_TIME >= '%s' )) and END_TIME <> '' and END_TIME is not null and tb_crm_contract.SP_CHARTER_NO is not null and tb_crm_contract.SP_CHARTER_NO != ''";#是否自动续约，1-否；0-是；
        //$tmpWhere = "IS_RENEWAL = 0 or (IS_RENEWAL = 1 and END_TIME >= '%s' )";#是否自动续约，1-否；0-是；
        $ret = $contract->field('tb_crm_contract.*,tb_crm_sp_supplier.BANK_SETTLEMENT_CODE,tb_crm_sp_supplier.CITY,tb_crm_sp_supplier.BANK_ADDRESS,tb_crm_sp_supplier.ACCOUNT_CURRENCY')
            ->where($condition)->where($tmpWhere,date('Y-m-d 00:00:00'))->order('CREATE_TIME desc')
            ->join('left join tb_crm_sp_supplier on tb_crm_contract.SP_CHARTER_NO = tb_crm_sp_supplier.SP_CHARTER_NO and tb_crm_sp_supplier.DATA_MARKING = 0')
            ->select();
        return static::$fund_allocation_contract[$k] = $ret;
    }

    /**
     * 获取供应商信息（已审核、无需审核）
     *
     */
    public static $supplier_info;
    public static function supplierInfo($params)
    {
        if (static::$supplier_info) return static::$supplier_info;
        if (empty($params['SP_NAME'])) return [];
        $model = M('crm_sp_supplier', 'tb_');
        $condition['SP_NAME'] = $params['SP_NAME'];
        $condition['AUDIT_STATE'] = ['in', [TbCrmSpSupplierModel::IS_AUDIT_YES, TbCrmSpSupplierModel::NOT_AUDIT]]; //已审核
        $ret = $model->where($condition)->find();
        return static::$contract_info = $ret;
    }

    /**
     * 获取关联单据号
     *
     */
    public static $order_info;
    public static function orderInfo($params)
    {
        $relation_bill_type = $params['relation_bill_type'];
        $order_no = $params['order_no'];
        if (empty($order_no) || $relation_bill_type == 'N003310005') return [];
        if (static::$order_info[$relation_bill_type]) return static::$order_info[$relation_bill_type];
        $desc = 'ID desc';
        $column = 'ORDER_NO';
        if ($relation_bill_type == 'N003310001') { //采购单
            $model = M('pur_order_detail', 'tb_');
            //$field = 'procurement_number as relation_bill_no,online_purchase_order_number';
            $field = 'procurement_number as relation_bill_no';
            $column = 'procurement_number';
            $desc = 'create_time desc';
        }
        if ($relation_bill_type == 'N003310002') { //调拨单
            $model = M('wms_allo', 'tb_');
            $field = 'allo_no as relation_bill_no';
            $desc = 'id desc';
            $column = 'allo_no';
        }
        if ($relation_bill_type == 'N003310003') { //B2B订单
            $model = M('b2b_order', 'tb_');
            $field = 'PO_ID as relation_bill_no';
            $column = 'PO_ID';
        }
        if ($relation_bill_type == 'N003310004') { //B2C订单
            ini_set('max_execution_time', 1800);
            ini_set('memory_limit', '512M');
            $model = M('op_order', 'tb_');
            //$field = 'ORDER_NO  as relation_bill_no,B5C_ORDER_NO,order_id,CHILD_ORDER_ID';
            $field = 'ORDER_NO  as relation_bill_no';
            $column = 'ORDER_NO';
            $where['PARENT_ORDER_ID'] = array('exp', 'is null'); // 只查母单不考虑子单，否则会重复
        }

        if ($relation_bill_type == 'N003310006') {  //推广任务ID
            $model = M('promotion_task', 'tb_ms_');
            $field = 'promotion_task_no as relation_bill_no,forecast_rol';
            $column = 'promotion_task_no';
        }


        //$where[$column] = ['like', '%' . $order_no . '%'];
        $where[$column] = $order_no; //改为精准匹配
        $ret = $model->field($field)->where($where)->order($desc)->select();
        return static::$order_info[$relation_bill_type] = $ret;
    }

    /**
     * 获取所有的结算类型
     *
     */
    public static $settlement_type;
    public static function settlementType()
    {
        return self::basis('settlement_type', 'N00327');
    }

    /**
     * 获取所有的采购性质
     *
     */
    public static $procurement_nature;
    public static function procurementNature()
    {
        return self::basis('procurement_nature', 'N00328');
    }

    /**
     * 获取所有的发票信息
     *
     */
    public static $invoice_information;
    public static function invoiceInformation()
    {
        return self::basis('invoice_information', 'N00329');
    }

    /**
     * 获取所有的发票类型
     *
     */
    public static $invoice_type;
    public static function invoiceType()
    {
        return self::basis('invoice_type', 'N00135');
    }

    /**
     * 获取所有的账单信息
     *
     */
    public static $bill_information;
    public static function billInformation()
    {
        return self::basis('bill_information', 'N00330');
    }

    /**
     * 获取所有的付款类型
     *
     */
    public static $payment_type;
    public static function paymentType()
    {
        return self::basis('payment_type', 'N00293');
    }

    /**
     * 获取所有的关联单据类型
     *
     */
    public static $relation_bill_type;
    public static function relationBillType()
    {
        return self::basis('relation_bill_type', 'N00331');
    }

    /**
     * 获取所有的手续费承担方式
     *
     */
    public static $commission_type;
    public static function commissionType()
    {
        return self::basis('commission_type', 'N00332');
    }

    /**
     * 获取所有的地址校验配置
     *
     */
    public static $address_valid_conf;
    public static function addressValidConf()
    {
        if (static::$address_valid_conf) return static::$address_valid_conf;
        return self::basis('address_valid_conf', 'N00343');
    }
    /**
     * 获取送仓方式配置
     *
     */
    public static $send_warehouse_way;
    public static function sendWarehouseWay()
    {
        if (static::$send_warehouse_way) return static::$send_warehouse_way;
        return self::basis('send_warehouse_way', 'N00346');
    }
    /**
     * 柜型
     *
     */
    public static $cabinet_type;
    public static function cabinetType()
    {
        if (static::$cabinet_type) return static::$cabinet_type;
        return self::basis('cabinet_type', 'N00349');
    }
    /**
     * 清关方式
     *
     */
    public static $customs_clear;
    public static function customsClear()
    {
        if (static::$customs_clear) return static::$customs_clear;
        return self::basis('customs_clear', 'N00347');
    }



    /**
     * 获取所有的GP_售后通知配置
     *
     */
    public static $after_sale_notice_conf;
    public static function afterSaleNoticeConf()
    {
        if (static::$after_sale_notice_conf) return static::$after_sale_notice_conf;
        return self::basis('after_sale_notice_conf', 'N00325');
    }

    /**
     * 获取所有的账户归属
     *
     */
    public static $account_class_cd;
    public static function accountClassCd()
    {
        if (static::$account_class_cd) return static::$account_class_cd;
        return self::basis('account_class_cd', 'N00351');
    }

    /**
     * 获取所有的账户归属
     *
     */
    public static function accountTransferType()
    {
        return [
            ['cd' => 1, 'cdVal' => '直接'],
            ['cd' => 2, 'cdVal' => '间接'],
        ];
    }

    /**
     * 获取所有审核的供应商
     *
     */
    public static $supplier;
    public static function supplier()
    {
        if (static::$supplier) return static::$supplier;
        $where['AUDIT_STATE'] = ['in', [TbCrmSpSupplierModel::IS_AUDIT_YES, TbCrmSpSupplierModel::NOT_AUDIT]]; //已审核
        //$where['COPANY_TYPE_CD'] = 'N001190901';//企业类型-供应商 支付供应商
        $where['_string'] .= " FIND_IN_SET('N001190901',COPANY_TYPE_CD) ";
        $where['DATA_MARKING'] = 0;
        $sp_supplier = M('sp_supplier','tb_crm_');
        $field = ['ID','SP_NAME'];
        return $sp_supplier->field($field)->where($where)->select();
    }

    // 企业类型
    public static $company_type_cd;
    public static function companyTypeCd()
    {
        if (static::$company_type_cd) return static::$company_type_cd;
        return self::basis('company_type_cd', 'N00119');

    }

    // ---------------------------  ★报价管理 options start★ --------------------------------------//
    protected static $declareType;
    public static function declareType()
    {
        return self::quoteOptions('declareType', 'N00355');
    }
    protected static $isElectric;
    public static function isElectric(){
        return self::quoteOptions('isElectric', 'N00356');
    }

    protected static $quoteStatus;
    public static function  quoteStatus()
    {
        return self::quoteOptions('quoteStatus', 'N00358');
    }


    protected static $quoteLclStatus;
    public static function  quoteLclStatus()
    {
        $quote_lcl_model = D("Quote/QuoteLcl");
        if (static::$quoteLclStatus) return static::$quoteLclStatus;
        $quoteStatus =  self::quoteOptions('quoteStatus', 'N00358');
        $quoteLclAllowStatus = $quote_lcl_model::$allow_status;
        $quoteLclStatus = array_only($quoteStatus, $quoteLclAllowStatus);
        self::$quoteLclStatus = $quoteLclStatus;
        return $quoteLclStatus;
    }

    protected static $stuffingType;
    public static function stuffingType()
    {
        return self::quoteOptions('stuffingType', 'N00357');
    }



    public static function quoteType()
    {
        $model = D("Quote/OperatorQuotation");
        return $model::$quote_type_str_map;
    }

    public static function quoteIntentionType() {
        $model = D("Quote/OperatorQuotation");
        return $model::$quote_intention_type_map;
    }


    protected static $logisticsSupplier = [];

    /**
     * 物流供应商
     * @param $params
     * @return array|bool|mixed|null
     * @author Redbo He
     * @date 2020/11/4  17:38
     */
    public static function logisticsSupplier()
    {
        if (static::$logisticsSupplier) return static::$logisticsSupplier;
        $model = M('crm_sp_supplier', 'tb_');
        $complex['COPANY_TYPE_CD'] = 'N001190900';
        $complex['_string'] = "FIND_IN_SET('N001190900',COPANY_TYPE_CD)";
        $complex['_logic'] = 'or';
        $condition['_complex'] = $complex;
        $condition['AUDIT_STATE'] = ['in', [TbCrmSpSupplierModel::IS_AUDIT_YES, TbCrmSpSupplierModel::NOT_AUDIT]]; //已审核
        $ret = $model->where($condition)->getField("ID,SP_NAME,SP_RES_NAME_EN");
        return static::$logisticsSupplier = $ret;
    }

    protected static $quotation = [];
    /**
     * 获取报价相关 code
     */
    public static function quotation()
    {
        if (static::$quotation) {
            return static::$quotation;
        }
        $model       = M('_ms_cmn_cd', 'tb_');
        $where['CD'] = array('between', array('N003550001', 'N003580006'));
        $ret         = $model->field('CD, CD_VAL')->order('SORT_NO asc')
            ->where($where)
            ->select();
        $ret         = array_column($ret, 'CD_VAL', 'CD');
        return static::$quotation = $ret;
    }

    protected static function quoteOptions($key_name, $code_prefix)
    {
        $quotation = self::quotation();
        if (static::$$key_name) return static::$$key_name;
        $ret = [];
        if($quotation) {
            foreach ($quotation as $key => $val) {
                if(strpos($key,$code_prefix) !== false) {
                    $ret[$key] = $val;
                }
            }
        }
        return static::$$key_name = $ret;
    }

    protected static $quoteSmallTeams;
    public static function  QuoteSmallTeams()
    {
        if (static::$quoteSmallTeams) return static::$quoteSmallTeams;
        return self::basis('quoteSmallTeams', 'N003230');
    }


    // ---------------------------  ★报价管理 options end ★ --------------------------------------//
    /**
     * 晋升状态
     *
     */
    public static $promotion_staus;
    public static function promotionStausCd()
    {
        if (static::$promotion_staus) return static::$promotion_staus;
        return self::basis('promotion_staus', 'N00363');
    }

    /**
     * 回邮单获取状态
     *
     */
    public static function replyStatus()
    {
        return TbMsCmnCdModel::replyStatusCd();
    }

    /**
     * 优惠码类型
     *
     */
    public static $coupon_source;
    public static function couponSource()
    {
        if (static::$coupon_source) return static::$coupon_source;
        return self::basis('coupon_source', 'N00378');
    }

    /**
     * OTTO_回邮单_仓库配置
     *
     */
    public static $reply_order_warehouse;
    public static function replyOrderWarehouse()
    {
        if (static::$reply_order_warehouse) return static::$reply_order_warehouse;
        return static::$reply_order_warehouse = TbMsCmnCdModel::replyOrderWarehouse();
    }

    /**
     * OTTO_回邮单_快递公司
     *
     */
    public static $reply_order_express;
    public static function replyOrderExpress()
    {
        if (static::$reply_order_express) return static::$reply_order_express;
        return static::$reply_order_express = TbMsCmnCdModel::replyOrderExpress();
    }
}