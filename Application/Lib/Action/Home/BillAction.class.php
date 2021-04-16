<?php

/**
 * 入库测试类
 *
 */
class BillAction extends BaseAction
{
    public $isOpenSelfTrans = false;

    public function _initialize()
    {

    }

    /**
     * 出入库接口
     *
     */
    public function mul_out_and_in_storage()
    {
        $bill = new TbWmsBillExtendModel();
        if (IS_POST) {
            $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
            if ($_SERVER ['CONTENT_TYPE'] == 'application/json') {
                $json_str = file_get_contents('php://input');
                $data = json_decode($json_str, true);
            } else {
                $data = $_POST;
            }
            $ret = $bill->outAndInStorage($data);
            echo json_encode($ret, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $a = [
                "code" => 10000000,
                "msg" => "入库成功",
                "info" => NULL
            ];
            $data = [
                'data' => [
                    [
                        'bill' => [
                            'field' => 'data',
                        ],
                        'guds' => [
                            [
                                'field' => 'data',
                            ],
                            [
                                'field' => 'data',
                            ],
                        ],
                    ],
                ],
                'bill_type' => '',
                'type' => 'B2B订单专用，其他类型不可设置这个参数否则全部会走B2B出库（B2B可设置为0,1）'
            ];
            $this->assign('title', 'out_and_in_storyage(出入库)接口参数：');
            $this->assign('exampleRequestFormat', $data);
            $this->assign('msgCode', $bill->msgCode());
            $this->assign('example', json_encode($a));
            $this->assign('bill', $bill->bill_attributes);
            $this->assign('guds', $bill->guds_attributes);
            $this->display();
        }
    }

    /**
     * 出入库测试接口
     */
    public function virtualTest()
    {
        //测试数据
        $data = [
            'bill' => [
                'bill_type' => 'N000940100',
                'procurement_number' => 'RN201805240001-002',
                'warehouse_rule' => '0',
                'batch' => 'BL20180524001',
                'sale_no' => '',
                'channel' => 'N000830100',
                'supplier' => '日本花王有限公司(JKCo. Ltd.)',
                'warehouse_id' => 'N000680800',
                'total_cost' => '527.35',
                'SALE_TEAM' => 'N001280200',
                'SP_TEAM_CD' => 'N001290300',
                'CON_COMPANY_CD' => 'N001240200'
            ],
            'guds' => [
                [
                    'GSKU' => '8000848101',
                    'taxes' => '0.0',
                    'price' => '78.50',
                    'currency_id' => 'N000590100',
                    'currency_time' => '2018-05-24 11:18:24',
                    'send_num' => 1,
                    'deadline_date_for_use' => null
                ]
            ]
        ];
        $bill = new TbWmsBillModel();
        $ret = $bill->outAndInStorage($data);
        var_dump($ret);exit;
        exit;
    }

    /**
     * 出入库接口
     *
     */
    public function out_and_in_storage()
    {
        $bill = new TbWmsBillModel();
        if (IS_POST) {
            $t = time();
            $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
            if ($_SERVER ['CONTENT_TYPE'] == 'application/json') {
                $json_str = file_get_contents('php://input');
                $data = json_decode($json_str, true);
            } else {
                Logs($_POST,'$_POST');
                $data = $_POST;
            }
            $ret = $bill->outAndInStorage($data);
            echo json_encode($ret, JSON_UNESCAPED_UNICODE);
            $ret['time(s)'] = time() - $t;
            exit;
        } else {
            $a = [
                "code" => 10000000,
                "msg" => "入库成功",
                "info" => NULL
            ];
            $data = [
                'bill' => [
                    'field' => 'data',
                ],
                'guds' => [
                    [
                        'field' => 'data',
                    ],
                    [
                        'field' => 'data',
                    ],
                ],
                'type' => 'B2B订单专用，其他类型不可设置这个参数否则全部会走B2B出库（B2B可设置为0,1）'
            ];
            $this->assign('title', 'out_and_in_storyage(出入库)接口参数：');
            $this->assign('exampleRequestFormat', $data);
            $this->assign('msgCode', $bill->msgCode());
            $this->assign('example', json_encode($a));
            $this->assign('bill', $bill->bill_attributes);
            $this->assign('guds', $bill->guds_attributes);
            $this->display();
        }
    }

    /**
     * 在途，在途金接口
     *
     */
    public function on_way_and_on_way_money()
    {
        $data = [
            [
                'SKU_ID' => '8000000401',
                'TYPE' => 0,
                'on_way' => 123,
                'on_way_money' => 3300
            ],
            [
                'SKU_ID' => '8000000401',
                'TYPE' => 1,
                'on_way' => 20,
                'on_way_money' => 300
            ],
        ];
        $standing = new TbWmsStandingModel();
        if (IS_POST) {
            $ret = $standing->onWayAndOnWayMoney($_POST);
            echo json_encode($ret, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $a = [
                "code" => 10000000,
                "msg" => "写入成功",
                "info" => null,
            ];
            $data = [
                [
                    'SKU_ID' => 'SKU_ID_01',
                    'TYPE' => 0,
                    'on_way' => 123,
                    'on_way_money' => 3300.12,
                ],
                [
                    'SKU_ID' => 'SKU_ID_02',
                    'TYPE' => 1,
                    'on_way' => 234,
                    'on_way_money' => 124123.24,
                ],
            ];
            $this->assign('title', 'on_way_and_on_way_money(在途，在途金接口)接口参数：');
            $this->assign('bill', $standing->attributes);
            $this->assign('msgCode', $standing->msgCode());
            $this->assign('exampleRequestFormat', $data);
            $this->assign('example', json_encode($a));
            $this->display('out_and_in_storage');
        }
    }

    public function test()
    {
        $bill = new TbWmsBillModel();
        $data = [
            'bill' => [
                'bill_type' => 'N000940100',//收发类型，采购入库为N000940100固定不变
                'link_bill_id' => 'b5cb49053203333',//b5c id
                'warehouse_rule' => '1',// 数据库无对应字段
                'batch' => date('Y-m-d', time()),//批次，这个待定
                'sale_no' => 'test20170717',// 数据库无对应字段
                'channel' => 'B5C',// 91440300311647055E
                'supplier' => '',// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                'purchase_logistics_cost' => '58.67',//采购端的物流费用
                'warehouse_id' => 'N000680100',// 仓库id（码表或数据字典对应的值）
                'total_cost' => '300.01',//入库总成本
                'bill_state' => null,   //单据状态，可为空
                'bill_date' => date('Y-m-d', time()),//单据日期
                'CON_COMPANY_CD' => 'N001241400',
                'SALE_TEAM' => 'N001280100'
            ],
            'guds' => [
                [
                    'GSKU' => '8000360401', // sku
                    'taxes' => '0.7',       // 税率
                    'should_num' => '800',  // 应发货
                    'send_num' => '489',    // 实际发货
                    'deadline_date_for_use' => '20170713',// 生产日期
                    'price' => '168.54',    // 单价（不含税）
                    'currency_id' => 'N000590300',// 币种（码表或数据字典对应的值）
                    'currency_time' => '20170713',// 具体交易时间（用作取币种当天的汇率）
                ],
                [
                    'GSKU' => '8000372401',
                    'taxes' => '0.4',
                    'should_num' => '999',
                    'send_num' => '321',
                    'deadline_date_for_use' => '20170713',
                    'price' => '189.5',
                    'currency_id' => 'N000590300',// 数据库无对应字段
                    'currency_time' => '20170713',// 数据库无对应字段
                ],
            ],
        ];
        $ret = $bill->outAndInStorage($data);
        echo '<pre/>';var_dump($ret);exit;
        if (IS_POST) {

        } else {
            $a = [
                "code" => 10000000,
                "msg" => "入库成功",
                "info" => NULL
            ];
            $this->assign('msgCode', $bill->msgCode());
            $this->assign('example', json_encode($a));
            $this->assign('bill', $bill->bill_attributes);
            $this->assign('guds', $bill->guds_attributes);
            $this->display();
        }

        return json_encode();
    }

    /**
     * Response 测试类
     *
     */
    public function testResponse()
    {
        $response = new Response();
        $response->format = 'jsonp';
        $response->data = [
            'callback' => 'hello',
            'data' => [
                'a' => 'b',
                'c' => 'd'
            ]
        ];
        $response->send();
    }


    public function log($d = '')
    {
        header("content-type:text/html;charset=utf-8");
        $d = $d ?: date('Ymd');
        $path = '/opt/logs/logstash/erp/erp_wms_' . $d . '.log';
        $content = file_get_contents($path);
        $content = str_replace( "\n", '</br></br>', $content);
//        $encode = mb_detect_encoding($content, array('ASCII','GB2312','GBK','UTF-8'));
//        if ($encode != 'UTF-8') {
//            $content = iconv('GB2312', 'utf-8', $content);
//        }
        die($content);
    }


    public function logDue($d = '')
    {
        header("content-type:text/html;charset=utf-8");
        $d = $d ?: date('Ymd');
        $path = '/opt/logs/logstash/erp/erp_due_' . $d . '.log';
        $content = file_get_contents($path);
        $content = str_replace( "\n", '</br></br>', $content);
//        $encode = mb_detect_encoding($content, array('ASCII','GB2312','GBK','UTF-8'));
//        if ($encode != 'UTF-8') {
//            $content = iconv('GB2312', 'utf-8', $content);
//        }
        die($content);
    }
}