<?php

/**
 * User: yangsu
 * Date: 16/12/21
 * Time: 17:25
 */

//use Think\Controller;

class StockAction extends BaseAction
{

    private $attribute = [
        '0' => '自用'
    ];
    private $valuation = [
        '0' => '全月加平均'
    ];
    private $bill_state = [
        '0' => '未确认',
        '1' => '已确认'
    ];
    private $location = 0;
    private $back = 0;
    private $outgoing = [];
    private $outgo = null;
    public $actlist = [];

    public function _initialize()
    {
// 定义变量
        header('Access-Control-Allow-Origin: *');
        $HI_PATH = '../Public/';
        $this->assign('HI_PATH', $HI_PATH);
        ini_set('date.timezone', 'Asia/Shanghai');
        /* if (!session('m_loginname')) {
             header("Location:/index.php?m=public&a=login");
         }*/
        $module = $this->_get_menu();
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            if (!$_POST) {
                $_POST = json_decode($json_str, true);
            }
            $_REQUEST = array_merge($_POST, $_GET);
        }
    }


    /**
     * 仓库
     */
    public function warehouse()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        if (IS_POST) {
            I("post.location_switch") == 1 ? $location_switch = 1 : $location_switch = 0;
            $warehouse_data = $this->getParams();
            $warehouse_cd = $this->warehouse_cd($warehouse_data['warehouse']);

            $model = new Model();
            $model->startTrans();
            if (!empty(I("post.id"))) {// edit
                $where['id'] = I("post.id");
                $warehouse_data['CD'] = empty($warehouse_cd) ? $where['id'] : $warehouse_cd;
                $result = $Warehouse->where($where)->save($warehouse_data);
                if ($result) {
                    $return_arr = array('info' => '修改成功,2秒后重载', "status" => "y");
                    $model->commit();
                } else {
                    $return_arr = array('info' => '修改失败', "status" => "n");
                    $model->rollback();
                }
            } else {// add
                if ($warehouse_data ['job_content']) {
                    $job_content = '';
                    foreach ($warehouse_data ['job_content'] as $key => $value) {
                        $job_content .= $value . ',';
                    }
                    $job_content = rtrim($job_content, ',');
                    $warehouse_data ['job_content'] = $job_content;
                }
                $result = $Warehouse->add($warehouse_data);
                $Warehouse->CD = empty($warehouse_cd) ? $result : $warehouse_cd;
                $result_cd = $Warehouse->where('id = ' . $result)->save();
                if ($result && $result_cd) {
                    $return_arr = array('info' => '增加成功,2秒后重载', "status" => "y");

                    //所有店铺设置成不支持该新增的仓库
                    $res = (new ConfigurationService($model))->setStoreNotSupportWarehouse($warehouse_cd);
                    if (!$res) {
                        $return_arr = array('info' => '设置店铺不支持该仓库失败', "status" => "n");
                    }

                    $_POST['attribute'] = I("post.attribute_id");
                    $return_arr['data'] = $_POST;

                    $model->commit();
                } else {
                    $model->rollback();
                    $return_arr = array('info' => '增加失败', "status" => "n");
                }
            }
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        // 国家
        $hot = [
            ['value' => 2356, 'label' => 'USA美国', 'children' => array(new stdClass())],
            ['value' => 3025, 'label' => 'JPN日本', 'children' => array(new stdClass())],
            ['value' => 1517, 'label' => 'KOR韩国', 'children' => array(new stdClass())],
        ];
        $packCountry = $this->packCountry(BaseModel::getCountry());
        foreach ($hot as $v) {
            array_unshift($packCountry, $v);
        }
        $list = $Warehouse->where('is_show = 1')->select();
        $this->assign('getCountry', json_encode($packCountry, JSON_UNESCAPED_UNICODE));
        $this->assign('Countrykey', json_encode(array_column($packCountry, 'value'), JSON_UNESCAPED_UNICODE));
        $this->assign('company_arr', json_encode($this->get_company(), JSON_UNESCAPED_UNICODE));
        $this->assign('attribute_arr', json_encode($this->attribute, JSON_UNESCAPED_UNICODE));
        $this->assign('valuation_arr', json_encode($this->valuation, JSON_UNESCAPED_UNICODE));
        $this->assign('various', '4');
        $this->assign('all_house_sku', json_encode($this->get_all_house_sku(), JSON_UNESCAPED_UNICODE));
        $this->assign('list', $list);
        $this->assign('json_list', json_encode($list, JSON_UNESCAPED_UNICODE));
        $this->assign('warehouseContacts', json_encode(BaseModel::warehouseContacts(), JSON_UNESCAPED_UNICODE));
        $this->assignJson('filterWarehouses', $this->getAllNotConfWarehouse($list));
        $this->assignJson('warehouses', BaseModel::getAllDeliveryWarehouse());
        $this->assignJson('manage', BaseModel::manage());
        $this->assignJson('system_docking', BaseModel::senderSystem());
        $this->assignJson('jobContent', BaseModel::jobContent());
        $this->display();
    }

    /**
     * 获得所有未配置的仓库,根据配置表已开始的仓库，并且筛选掉已经选择过的仓库
     *
     * @param array $warehousesInfo 仓库信息
     *
     * @return array $warehouses 可供选择的从仓库
     */
    public function getAllNotConfWarehouse($warehousesInfo)
    {
        $existingWarehouses = array_column($warehousesInfo, 'CD');
        $warehouses = BaseModel::getAllDeliveryWarehouse();
        foreach ($warehouses as $k => $v) {
            if (in_array($v ['CD'], $existingWarehouses)) unset($warehouses [$k]);
        }

        return $warehouses;
    }


    /**
     *检查仓库
     */
    public function check_warehouse()
    {

        $Warehouse = M('warehouse', 'tb_wms_');
        $warehouse_name = I('post.warehouse_name');
        $where['warehouse'] = $warehouse_name;
        $where['is_show'] = 1;
        $result = $Warehouse->where($where)->select();
        if ($result) {
            $return_arr = array('info' => '仓库名已存在', "status" => "n");
        } else {
            $return_arr = array('info' => '仓库名未存在', "status" => "y", 'data' => $result);
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 删除仓库
     */
    public function warehouse_del()
    {
        $id = I("post.id");
        $Location = M('location', 'tb_wms_');
        $where['warehouse_id'] = $id;
        $location_link = $Location->where($where)->count();
        if ($location_link == 0) {
            $Warehouse = M('warehouse', 'tb_wms_');
            $data['is_show'] = 0;
            $where_id['id'] = $id;
            if ($Warehouse->where($where_id)->save($data)) {
                $return_arr = array('info' => '删除成功', "status" => "y");
            } else {
                $return_arr = array('info' => '删除失败', "status" => "n");
            }
        } else {
            $return_arr = array('info' => '请先删除关联货位', "status" => "n");
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 栏位
     */
    public function position()
    {
        $Location = M('location', 'tb_wms_');
        $location_data = $Location->where('tb_wms_location.is_show = 1 ')->
        join('tb_wms_warehouse on  tb_wms_warehouse.CD = tb_wms_location.warehouse_id')->
        join('tb_wms_location_details on tb_wms_location_details.location_id = tb_wms_location.id')->
        field(array('tb_wms_location.id' => 'l_id', 'location_code', 'location_name', 'tb_wms_warehouse.warehouse' => 'warehouse_name', 'count(tb_wms_location_details.id)' => 'l_sum'))->
        group('tb_wms_location_details.location_id')->select();
        $this->assign('location_data', json_encode($location_data, JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     *检查货位
     */
    public function check_location()
    {
        $location = I('post.location');
        $house = I('post.house');
        $outgo_state = I('post.outgo_state');
        if (empty($house)) {
            $return_arr = array('info' => '选择仓库', "status" => "n");
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        if ($outgo_state == 'storage') {
            $Location_details = M('location_details', 'tb_wms_');
            $where['box_name'] = $location;
            $where['warehouse_id'] = $house;
            $l_count = $Location_details->where($where)->count();
            if ($l_count > 0) {
                $data = $Location_details->where($where)->find();
                $return_arr = array('info' => '货位存在', "status" => "y", "data" => $data);
            } else {
                $return_arr = array('info' => '货位未存在', "status" => "n");
            }

        } else {
            $Location_sku = M('location_sku', 'tb_wms_');
            $where_sku['tb_wms_location_sku.sku'] = I('post.GSKU');
            $where_sku['tb_wms_location_sku.count'] = array('GT', 0);
            $Location_sku_box = $Location_sku
                ->where($where_sku)
                ->join("left join tb_wms_location_details on  tb_wms_location_sku.box_name = tb_wms_location_details.box_name AND tb_wms_location_details.warehouse_id = '" . $house . "'")
                ->field('tb_wms_location_sku.box_name,tb_wms_location_sku.count')
                ->select();
            if (count($Location_sku_box) > 0) {
                $return_arr = array('info' => '货位存在', "status" => "i", "data" => $Location_sku_box);
            } else {
                $return_arr = array('info' => '无对应货位', "status" => "n", "data" => $Location_sku_box);
            }
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 栏位详情
     */
    public function position_add()
    {
        if (IS_POST) {
            $position_data = array(
                'location_code' => I("post.location_key"),
                'location_name' => I("post.location_name"),
                'location_sum' => null,
                'warehouse_id' => I('post.location_id'),
            );
//            检验货位
            $Location = M('location', 'tb_wms_');
            $check['location_code'] = $position_data['location_code'];
            $check['is_show'] = 1;
            $check_code_sum = $Location->where($check)->count();
            if ($check_code_sum != 0) {
                $return_arr = array('info' => '货位已存在', "status" => "y");
                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit;
            }
            $qu = I("post.qu");
            $Location_details = M('location_details', 'tb_wms_');
            $check_s['area'] = $qu;
            $check_s['warehouse_id'] = I('post.location_id');
//            $check_s['location_id'] = $location_id;
            $check_s_sum = $Location_details->where($check_s)->count();
            if ($check_s_sum != 0) {
                $return_arr = array('info' => '区位冲突', "status" => "y");
                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit;
            }
//          优先创建货位
            $location_id = $Location->add($position_data);
            if ($location_id) {
                $qu = I("post.qu");
                $pai = I("post.pai");
                $ceng = I("post.ceng");
                $ge = I("post.ge");
                for ($p = 1; $p <= $pai; $p++) {
                    for ($c = 1; $c <= $ceng; $c++) {
                        for ($g = 1; $g <= $ge; $g++) {
                            $data['box_name'] = $p . '-' . $c . '-' . $g;
                            $data['area'] = $qu;
                            $data['occupy'] = 0;
                            $data['location_id'] = $location_id;
                            $data['warehouse_id'] = I('post.location_id');
                            $datas[] = $data;
                            unset($data);
                        }
                    }
                }

//            join data
                $Location_details = M('location_details', 'tb_wms_');
                $data_start = $Location_details->addAll($datas);
                if ($data_start) {
                    $return_arr = array('info' => '保存成功', "status" => "y");
                } else {
                    $return_arr = array('info' => '保存失败', "status" => "y", 'datas' => $datas, 'start' => $data_start, 'post' => $position_data);
                }
            } else {
                $return_arr = array('info' => '保存失败', "status" => "y");
            }
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }
// 启用货位
        $this->location = 1;

        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 栏位导入
     */
    public function position_import()
    {
        if (IS_POST) {
            $location_id = I('post.location_id');

            $position_data = array(
                'location_name' => I("post.location_name"),
                'warehouse_id' => I('post.house_list_model'),
            );

            if (empty($location_id)) {
// add
                $Location = M('location', 'tb_wms_');
                if (empty($position_data['location_name']) || empty($position_data['warehouse_id'])) {
                    $error[][] = '基本数据为空';
                    goto echo_error;
                } else {
                    $where['location_name'] = $position_data['location_name'];
                    $where['warehouse_id'] = $position_data['warehouse_id'];
                    $where['is_show'] = 1;
                    if ($Location->where($where)->count() > 0) {
                        $error[][] = '货位名称重复';
                        goto echo_error;
                    }

                }
                header("content-type:text/html;charset=utf-8");
                $filePath = $_FILES['expe']['tmp_name'];
                vendor("PHPExcel.PHPExcel");
                $objPHPExcel = new PHPExcel();
                $PHPReader = new PHPExcel_Reader_Excel2007();
                if (!$PHPReader->canRead($filePath)) {
                    $PHPReader = new PHPExcel_Reader_Excel5();
                    if (!$PHPReader->canRead($filePath)) {
                        echo 'no Excel';
                        return;
                    }
                }
                $PHPExcel = $PHPReader->load($filePath);
                $sheet = $PHPExcel->getSheet(0);
                $allRow = $sheet->getHighestRow();
                $model = new Model();
                $model->startTrans();
                $location_id = $model->table('tb_wms_location')->add($position_data);
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    $box_name = (string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue();
                    if ($this->check_postition(trim($box_name), $position_data['warehouse_id']) > 0) {
                        $error[][] = $currentRow . '货位重复.';
                        goto echo_error;
                    }
                    if (!empty($box_name)) {
                        $check[] = $data['box_name'] = trim($box_name);
                        $data['location_id'] = $location_id;
                        $data['warehouse_id'] = $position_data['warehouse_id'];
                        $datas[] = $data;
                    }
                }
                $check_unique = array_unique($check);
                if (count($check) != count($check_unique)) {
                    $diff = array_diff_assoc($check, $check_unique);

                    $model->rollback();
                    foreach ($diff as $i) {
                        $error[][] = $i . '货位重复.';
                    }
                    goto echo_error;
                }

                if (count($datas) > 0) {
                    $details = $model->table('tb_wms_location_details')->addAll($datas);
                    if ($details > 0) {
                        $model->commit();
                        $this->redirect('position_show', array('location_id' => $location_id));
                    } else {
                        $model->rollback();
                        $error[][] = 'excel数据异常，新增失败.';
                    }
                } else {
                    $model->rollback();
                    $error[][] = 'excel数据为空';
                }
                echo_error:

                $this->assign('check_data', $error);
                $go_url = U('Stock/position_import');
                $this->assign('go_url', $go_url);
                $this->display('error');
                exit;
            } else {
//                upd
                $location_id = I('location_id');
                header("content-type:text/html;charset=utf-8");
                $filePath = $_FILES['expe']['tmp_name'];
                vendor("PHPExcel.PHPExcel");
                $objPHPExcel = new PHPExcel();
                $PHPReader = new PHPExcel_Reader_Excel2007();
                if (!$PHPReader->canRead($filePath)) {
                    $PHPReader = new PHPExcel_Reader_Excel5();
                    if (!$PHPReader->canRead($filePath)) {
                        echo 'no Excel';
                        return;
                    }
                }
                $PHPExcel = $PHPReader->load($filePath);
                $sheet = $PHPExcel->getSheet(0);
                $allRow = $sheet->getHighestRow();
                $model = new Model();
                $model->startTrans();
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    $box_name = (string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue();

                    if (!empty($box_name)) {
                        $box_name = trim($box_name);
                        $check[] = $data['box_name'] = $box_name;
                        $data['location_id'] = $location_id;
                        $data['warehouse_id'] = $position_data['warehouse_id'];
                        $datas[] = $data;
                    }
                }
                $check_unique = array_unique($check);
                if (count($check) != count($check_unique)) {
                    $diff = array_diff_assoc($check, $check_unique);

                    $model->rollback();
                    foreach ($diff as $i) {
                        $error[][] = $i . '货位重复.';
                    }
                    goto echo_error1;
                }
                if (count($datas) > 0) {
                    $Location_details = M('location_details', 'tb_wms_');
                    $location_details = $Location_details->where('location_id = ' . $location_id)->getField('box_name,occupy');
                    $location_details_box = array_keys($location_details);
                    $box_merge = array_merge($check, $location_details_box);
                    $box_del = array_diff($box_merge, $check);
                    foreach ($box_del as $key => $val) {
                        if ($location_details[$val] > 0) {
                            $model->rollback();
                            $error[][] = $val . '货位有库存' . $location_details[$val] . '，不能删除';
                            goto echo_error1;
                        }
                    }
//                    add
                    $box_add = array_diff($box_merge, $location_details_box);
                    $where_del['box_name'] = array('in', $box_del);
                    if (count($box_del) > 0 || count($box_add) > 0) {
                        $model->table('tb_wms_location_details')->where($where_del)->delete();
                        foreach ($datas as $k => $v) {
                            if (in_array($v['box_name'], $box_add)) {
                                $box_add_data[] = $v;
                            }
                        }
                        $model->table('tb_wms_location_details')->addAll($box_add_data);
                        $model->commit();
                        $this->redirect('position_show', array('location_id' => $location_id));
                    } else {
                        $model->rollback();
                        $error[][] = 'excel无可处理数据';
                    }
                } else {
                    $model->rollback();
                    $error[][] = 'excel数据为空';
                }
                echo_error1:

                $this->assign('check_data', $error);
                $go_url = U('Stock/position_show', array('location_id' => $location_id));
                $this->assign('go_url', $go_url);
                $this->display('error');
                exit;

            }
        }
        $this->location = 1;
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 栏位展示
     */
    public function position_show()
    {
        $location_id = I('location_id');
        $Location = M('location', 'tb_wms_');
        $location = $Location->where('id = ' . $location_id)->select();
        $Location_details = M('location_details', 'tb_wms_');
        $location_details = $Location_details
            ->join('left join tb_wms_location_sku on tb_wms_location_sku.warehouse_id  = tb_wms_location_details.warehouse_id AND tb_wms_location_sku.box_name  = tb_wms_location_details.box_name ')
            ->where('location_id = ' . $location_id)
            ->field('tb_wms_location_details.*,tb_wms_location_sku.count as occupy')
            ->select();

        $this->assign('location', json_encode($location, JSON_UNESCAPED_UNICODE));
        $this->assign('location_details', json_encode($location_details, JSON_UNESCAPED_UNICODE));
        $this->assign('location_sum', json_encode(count($location_details), JSON_UNESCAPED_UNICODE));

        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 栏位编辑
     */
    public function position_edit()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        $house_list = $Warehouse->getField('id,company_id,warehouse');
        $this->assign('house_list', json_encode($house_list, JSON_UNESCAPED_UNICODE));
        $l_id = I("l_id");

        $Location = M('location', 'tb_wms_');
        $d_where['location_id'] = $where['id'] = $l_id;
        $location_data = $Location->where($where)->select();

        $Location_details = M('location_details', 'tb_wms_');
        $l_d_data = $Location_details->where($d_where)->select();
        $this->assign('location_data', json_encode($location_data, JSON_UNESCAPED_UNICODE));
        $this->assign('l_d_data', json_encode($l_d_data, JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     *  栏位删除
     */
    public function position_del()
    {
        $id = I("post.id");
        $Location = M('stream', 'tb_wms_');
        $where['location_id'] = $id;
        $location_link = $Location->where($where)->count();
        if ($location_link == 0) {
            $Location = M('location', 'tb_wms_');
            $id = I("post.id");
            $Location->is_show = 0;
            if ($Location->where('id = ' . $id)->save()) {
                $return_arr = array('info' => '删除成功', "status" => "y");
            } else {
                $return_arr = array('info' => '删除失败', "status" => "n");
            }
        } else {
            $return_arr = array('info' => '请先删除关联商品', "status" => "n");
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 出入库管理
     */
    public function warehouse_switch_back()
    {
        $Bill = M('bill', 'tb_wms_');
        $where['is_show'] = 1;
        if (IS_POST) {
            empty(I("post.link_bill_id")) ? '' : $where['link_bill_id'] = ZUtils::filterBlank(I("post.link_bill_id"));
            empty(I("post.batch")) ? '' : $where['batch'] = ZUtils::filterBlank(I("post.batch"));
            empty(I("post.bill_id")) ? '' : $where['bill_id'] = ZUtils::filterBlank(I("post.bill_id"));
            empty(I("post.other_code")) ? '' : $where['other_code'] = ZUtils::filterBlank(I("post.other_code"));
            empty(I("post.bill_type")) ? '' : $where['bill_type'] = ZUtils::filterBlank(I("post.bill_type"));
            empty(I("post.house_list_model")) ? '' : $where['warehouse_id'] = ZUtils::filterBlank(I("post.house_list_model"));
            empty(I("post.bill_date_ation")) ? '' : $where['bill_date'] = ZUtils::filterBlank(array('EGT', I("post.bill_date_ation")));
            empty(I("post.bill_date_end")) ? '' : $where['bill_date'] = ZUtils::filterBlank(array('ELT', I("post.bill_date_end")));
            empty(I("post.sale_no")) ? '' : $where['sale_no'] = ZUtils::filterBlank(array('eq', I("post.sale_no")));
            if (!empty(I("post.GSKU"))) {
                $Stream = M('stream', 'tb_wms_');
                $where_sku['GSKU'] = I("post.GSKU");
                $bill_id_arr = $Stream->where($where_sku)->field('bill_id')->select();
                foreach ($bill_id_arr as $key => $val) {
                    $bill_id_arr_un[] = $val['bill_id'];
                }
                $bill_id_arr_un = array_unique($bill_id_arr_un);
                $where['id'] = array('in', $bill_id_arr_un);
            }
        }
        $_param = $this->_param();
        $_param = empty($_param) ? 0 : $_param;
        $this->assign('param', json_encode($_param, JSON_UNESCAPED_UNICODE));
        import('ORG.Util.Page');
        $count = $Bill->where($where)->count();
        $Page = new Page($count, 100);
        $show = $Page->show();
        $bills = $Bill->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('bills', json_encode($bills, JSON_UNESCAPED_UNICODE));
        $this->assign('pages', $show);
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('bill_state', json_encode($this->bill_state, JSON_UNESCAPED_UNICODE));
        $this->assign('warehouse_use', json_encode($this->get_use_extends(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('outgo', json_encode($this->get_outgo(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->assign('currency', json_encode($this->get_currency(), JSON_UNESCAPED_UNICODE));
        $this->assign('out_storage', json_encode($this->get_outgo('outgoing'), JSON_UNESCAPED_UNICODE));
        $this->assign('rule_storage', json_encode(BaseModel::getRuleStorage(), JSON_UNESCAPED_UNICODE));
        $this->assign('go_url', GO_URL);
        $this->assign('this_user', session('m_loginname'));

        $this->display();
    }

    /**
     * Excel 出入库
     */
    public function import_bill()
    {
        if ($_POST ['type'] == 1) {
            // 入库
            $model = new ExcelImportBillModel();
            $r = $model->import();
        } else if ($_POST ['type'] == 3){
            $model = new ExcelReturnImportModel();
            $r = $model->import();
        }
        else {
            // 出库
            $model = new ExcelExportBillModel();
            $r = $model->import();
        }

        if ($r['code'] == 2000) {
            FileModel::copyExcel($model->excel_path, $model->saveName);
        }

        $response = $this->formatOutput($r ['code'], $r ['info'], $r ['data']);

        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批量导入
     */
    public function import_bill_back()
    {
        ini_set('max_execution_time', 1800);
        ini_set('request_terminate_timeout', 18000);
        ini_set('max_execution_time', 1800);
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $sheet = $PHPExcel->getSheet(0);
        $allRow = $sheet->getHighestRow();
        $outgo_state = I('post.outgo_state');
        $type = 1;

        $model = new Model();
        if ('-' == $outgo_state) {
            $storageType = $model->table('tb_ms_cmn_cd')->field(['CD as cd'])->where(['CD' => ['like', 'N000950%']])->select();
        } else {
            $storageType = $model->table('tb_ms_cmn_cd')->field(['CD as cd'])->where(['CD' => ['like', 'N000940%']])->select();
        }
        $storageType = array_column($storageType, 'cd');

        $outgo_s = '入库类型';
        if ('-' == $outgo_state) $outgo_s = '出库类型';
        if ('-' == $outgo_state) $type = 0;
        // 仓库
        $warehouse = BaseModel::getWarehouseId();
        // 销售团队
        $saleTeam = BaseModel::saleTeamCdCur();
        // 采购团队
        $purTeam = BaseModel::spTeamCdExtend();
        // 数据验证
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            if ((string)$PHPExcel->getActiveSheet()->getCell("N" . $currentRow)->getValue())
                $purNos [$currentRow] = (string)$PHPExcel->getActiveSheet()->getCell("N" . $currentRow)->getValue();
            $num = (string)$PHPExcel->getActiveSheet()->getCell("I" . $currentRow)->getValue();
            $currency = $PHPExcel->getActiveSheet()->getCell("K" . $currentRow)->getValue();
            if (!ctype_digit($num)) {
                $error [][] = 'I' . $currentRow . L('数量不为整数，请修正');
            }
            if ($currency != 'CNY' and $outgo_state != '-') {
                $error [][] = 'K' . $currentRow . L('币种必须为CNY');
            }
            if (!in_array($PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue(), $storageType)) {
                $error [][] = 'B' . $currentRow . L('出入库类型错误');
            }
            if ($PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getDataType() != 's') {
                $error [][] = 'B' . $currentRow . L('入库日期时间格式错误，请设置为文本格式');
            }
            // 仓库校验
            if (!isset($warehouse [$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue()])) {
                $error [][] = 'A' . $currentRow . L('未知仓库CODE，请查验或将A列设置为文本格式重新导入');
            }
            // 销售团队校验
            if (!isset($saleTeam [$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue()])) {
                $error [][] = 'D' . $currentRow . L('销售团队异常，请校验');
            }
            // 采购团队校验
            if (!isset($purTeam [$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue()])) {
                $error [][] = 'E' . $currentRow . L('采购团队异常，请校验');
            }
        }
        // 采购单号验证
        if ($purNos) {
            // 反转数组，以保留每个采购单号在excel中的坐标行
            $basePurNos = array_flip($purNos);
            $purModel = new Model();
            $ret = (array)$purModel->table('tb_pur_order_detail')->field('procurement_number')->where(['procurement_number' => ['in', array_unique($purNos)]])->select();
            $ret = array_column($ret, 'procurement_number');
            if (count($ret) != count(array_unique($purNos))) {
                $diff = array_diff($purNos, $ret);
                foreach ($diff as $key => $value) {
                    $error [][] = 'N' . $basePurNos [$value] . '：' . L('采购单号') . ' ' . $value . ' ' . L('不存在');
                }
            }
        }

        if ($error) {
            $this->assign('check_data', $error);
            $this->display();
            exit;
        }
        $Guds_opt = M('guds_opt', 'tb_ms_');
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $bill['warehouse_id'] = (string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue();// 仓库CODE
            $bill['bill_type'] = (string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue();// 收发类别CODE
            $bill['CON_COMPANY_CD'] = (string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue();// 所属公司CODE
            $bill['SALE_TEAM'] = (string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue();// 销售归属CODE
            $bill['bill_state'] = 1;
            $bill ['user_id'] = BaseModel::getName();
            $bill['zd_user'] = session('user_id');
            $bill['zd_date'] = date('Y-m-d H:i:s', time());
            $bill['bill_date'] = date('Y-m-d', time());
            $bill['channel'] = 'N000830100';
            $bill['procurement_number'] = (string)$PHPExcel->getActiveSheet()->getCell("N" . $currentRow)->getValue();
            $bill['SP_TEAM_CD'] = (string)$PHPExcel->getActiveSheet()->getCell("E" . $currentRow)->getValue();// 采购团队CODE
            $bill_all = $bill['company_id'] . $bill['channel'] . $bill['warehouse_id'] . $bill['bill_type'] . $bill['user_id'] . $bill['bill_date'] . $bill ['SALE_TEAM'] . $bill['procurement_number'] . $bill ['SP_TEAM_CD'];
            $bill_hash = md5($bill_all);
            if (!empty($bill_data[$bill_hash]['row'])) {
                $bill['row'] = $currentRow . ',' . $bill_data[$bill_hash]['row'];
            } else {
                $bill['row'] = $currentRow;
            }

            $bill_data[$bill_hash] = $bill;
            // bill 数据组装结束
            $stream['GSKU'] = (string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue();// 商品编码
            $stream['GUDS_OPT_ID'] = $qr_code = (string)$PHPExcel->getActiveSheet()->getCell("G" . $currentRow)->getValue();// 条形码
            $stream['deadline_date_for_use'] = $qr_code = $PHPExcel->getActiveSheet()->getCell("H" . $currentRow)->getValue();
            if ($stream ['deadline_date_for_use']) {
                $stream ['deadline_date_for_use'] = date('Y-m-d', strtotime($stream['deadline_date_for_use']));
            }
            if (empty($stream['GSKU']) && !empty($stream['GUDS_OPT_ID'])) {
                $where_qr['GUDS_OPT_UPC_ID'] = $qr_code;
                $res = $Guds_opt->where($where_qr)->field('GUDS_OPT_ID')->find();
                empty($res) ? $stream['GSKU'] = '' : $stream['GSKU'] = $res['GUDS_OPT_ID'];
            }

            $stream['add_time'] = date('Y-m-d', strtotime($PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue()));
            $stream['currency_time'] = date('Y-m-d', strtotime($PHPExcel->getActiveSheet()->getCell("M" . $currentRow)->getValue()));
            $stream['should_num'] = $stream['send_num'] = (int)$PHPExcel->getActiveSheet()->getCell("I" . $currentRow)->getValue();
            if ('-' == $outgo_state) {
                $stream['unit_price'] = (double)static::get_power($stream['GSKU']);
            } else {
                $stream['unit_price'] = (double)$PHPExcel->getActiveSheet()->getCell("J" . $currentRow)->getValue();
            }
            if (is_object($stream['add_time'])) $stream['add_time'] = $stream['add_time']->__toString();
            $exchangeRate = RedisModel::get_key('xchr_' . date('Ymd', strtotime($stream['add_time'])));
            if (is_null($exchangeRate) and '-' != $outgo_state) {
                $error [][] = L('无法获取到美元汇率，请重试');
                $this->assign('check_data', $error);
                $this->display();
                exit;
            } else {
                $usdXchar = json_decode($exchangeRate, true)['usdXchrAmtCny'];
            }
            $stream ['unit_price_usd'] = bcdiv($stream ['unit_price'], $usdXchar, 4);//含税美元单价
            $stream['currency_id'] = $this->upd_cd((string)$PHPExcel->getActiveSheet()->getCell("K" . $currentRow)->getValue());
            $stream['unit_money'] = (double)($stream['send_num'] * $stream['unit_price']);
            $stream['row'] .= ' ' . $currentRow;
            $stream_data[$bill_hash][] = $stream;
            unset($bill);
            unset($stream);
        }
        //check excel
        if ('-' == $outgo_state) {
            $check_data = $this->check_excel($bill_data, $stream_data, 1);
        } else {
            $check_data = $this->check_excel($bill_data, $stream_data, '');
        }
        $requestData = null;
        if (0 == count($check_data)) {
            $model = new Model();
            $model->startTrans();
            $count = 0;
            foreach ($bill_data as $key => $val) {
                $tmp_header = [];
                $val['bill_id'] = $this->get_bill_id($val['bill_type'], $val['bill_date']);
                unset($val['row']);
                $val ['type'] = $type;
                $b_id = $model->table('tb_wms_bill')->data($val)->add();
                if ($b_id) {
                    // 将 bill_id 写入 stream_data 中
                    foreach ($stream_data[$key] as $k => &$v) {
                        unset($v['row']);
                        unset($v['GUDS_OPT_ID']);
                        unset($v['sum']);
                        $v['bill_id'] = $b_id;
                    }
                }
            }
            // stream_data 入库单个入库生成 stream_id 并更新到 stream_data 中
            $requestData = [];
            foreach ($stream_data as $key => $ttok) {
                $tmp_body = null;
                // 每一个 stream_data 下的多个数据都属于同一个 bill
                foreach ($ttok as $k => $vst) {
                    $vst ['warehouse_id'] = $bill_data [$key]['warehouse_id'];
                    if ($outgo_state != '-') {
                        $stream_id = $model->table('tb_wms_stream')->add($vst);
                        if (!$stream_id) {
                            $this->error('子表数据写入失败' . $model->getError());
                            return;
                        }
                    }
                    // 出库数据组装
                    if ($outgo_state == '-') {
                        $export = [
                            'skuId' => $vst ['GSKU'],
                            'saleTeamCode' => $bill_data [$key]['SALE_TEAM'],
                            'deliveryWarehouse' => $bill_data [$key]['warehouse_id'],
                            'num' => (int)($vst ['send_num']),
                            'purchaseOrderNo' => $bill_data [$key]['procurement_number'],
                            'purchaseTeamCode' => $bill_data [$key]['SP_TEAM_CD'],
                            'operatorId' => $_SESSION['user_id'],
//                            'filePath' => '',
                            'billId' => $vst ['bill_id'],
                        ];
                        $outStorageData [] = $export;
                    } else { // 入库数据组装
                        $tmp_header = [
                            'billId' => $vst ['bill_id'],
                            'lockCode' => create_guid(),
                            'type' => 0,
                            'data' => []
                        ];
                        $tmp_body [] = [
                            'gudsId' => substr($vst ['GSKU'], 0, 8),
                            'skuId' => $vst ['GSKU'],
                            'inStorageTime' => $vst ['add_time'] ? $vst ['add_time'] : null,
                            'num' => (int)($outgo_state . $vst ['send_num']),
                            'deadlineDateForUse' => $vst ['deadline_date_for_use'],
                            'operatorId' => $_SESSION['userId'],
                            'purchaseOrderNo' => (string)$bill_data [$key]['procurement_number'],
                            'purchaseTeamCode' => $bill_data [$key]['SP_TEAM_CD'],
                            'channel' => 'N000830100',
                            'ChannelSkuId' => $vst ['ChannelSkuId'] ? $vst ['ChannelSkuId'] : 0,
                            'streamId' => $stream_id,
                            'saleTeamCode' => $bill_data [$key]['SALE_TEAM'],
                            'deliveryWarehouse' => $bill_data [$key]['warehouse_id']
                        ];
                    }
                    $count++;
                }
                if ($outgo_state == '-') {
                    // 出库请求数据结构
                    $requestData = [
                        'processCode' => 'EXCEL_EXPORT',
                        'processId' => create_guid(),
                        'data' => $outStorageData
                    ];

                } else {
                    // 入库请求数据结构
                    $tmp_header ['data'] = $tmp_body;
                    $requestData['data']['batch'][] = $tmp_header;
                }
            }
            // send request
            if ($requestData and $this->sendRequest($requestData, $outgo_state)) {
                $model->commit();
                if ($outgo_state != '-') {
                    if ($this->saveBatchIdToStream) {
                        $warehouse = new WarehouseIOModel();
                        $warehouse->updateStream($this->saveBatchIdToStream);
                    }
                }
                $msg = "成功" . $count . "条";
            } else {
                $model->rollback();
                $response = $this->getResponseData();
                $msg = $response['msg'];
            }
            $this->success($msg, '', 5);
        }
        $this->assign('check_data', $check_data);
        $this->display();
    }

    private $_responseData;

    public function setReponseData($responseData)
    {
        return $this->_responseData = $responseData;
    }

    public function getResponseData()
    {
        return $this->_responseData;
    }


    public $saveBatchIdToStream;

    /**
     * @param $requestData Array 发起请求的数据
     * @param $type        String 出入库标识符
     *
     * @return bool 返回成功与否
     */
    public function sendRequest($requestData, $type)
    {
        $type == '-' ? $url = OLD_HOST_URL_API . '/batch/export2.json' : $url = OLD_HOST_URL_API . '/batch/update_total.json';
        if ($requestData) {
            $this->setRequestUrl($url);
            $responseData = json_decode(curl_get_json($url, json_encode($requestData)), true);
            $this->_catchMe($requestData, $responseData);
            $this->setReponseData($responseData);
            if ($responseData ['code'] == 2000) {
                $warehouse = new WarehouseIOModel();
                if ($type == '-') {
                    if ($warehouse->outgoing($responseData['data'])) {
                        return true;
                    }
                } else {
                    $this->saveBatchIdToStream = $warehouse->storage($responseData ['data']);
                    return true;
                }
            }
            return false;
        }
    }

    /**
     *删除订单
     */
    public function del_bill()
    {
        $Bill = M('bill', 'tb_wms_');
        $bill_id = $id = I("post.id");
        $check_del_bill = $this->check_del_bill($bill_id, 'delord');
        if ($check_del_bill['state'] == 1) {
            $return_arr = array('info' => '删除失败,sku>' . $check_del_bill['sku'] . '<数量不足', "status" => "n");
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $Bill->is_show = 0;
        $bill = $Bill->where('id=' . $id)->save();
        $bill_val = $Bill->where('id=' . $id)->select();

        $Stream = M('stream', 'tb_wms_');
        $all_list = $Stream->where('bill_id=' . $id)->select();

        $get_outgoing = $this->get_outgoing();
        $data['get_outgoing'] = $get_outgoing;
        $data['bill_val'] = $bill_val;
        if (in_array($bill_val[0]['bill_type'], array_keys($get_outgoing))) {
            $outgo_state = null;
        } else {
            $outgo_state = '-';
        }
        $data['$outgo_state'] = $outgo_state;

        foreach ($all_list as $key => $val) {
            $skuId = $val['GSKU'];
            $gudsId = substr($val['GSKU'], 0, -2);
            $changeNm = $val['send_num'];
            $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm;
            $data['urls'][] = $url;

            $get_start = json_decode(curl_request($url), 1);
            if ($get_start['code'] != 2000) {
                throw new Exception('接口异常' . $get_start);
            }
        }


        if ($bill) {
            $return_arr = array('info' => '删除成功', "status" => "y", 'data' => $data);
        } else {
            $return_arr = array('info' => '删除失败', "status" => "n");
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);

    }

    /**
     * get this order good
     */
    /*    public function loading()
        {
            $Stream = D('Stream');
            $where['bill_id'] = I('post.bill_id');
            $stream_arr = $Stream->relation(true)->where($where)->select();
            if ($stream_arr) {
                $return_arr = array('info' => '成功', "status" => "y", 'data' => $stream_arr);
            } else {
                $return_arr = array('info' => '失败', "status" => "n");
            }
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
        }*/

    /**
     * 获取订单商品
     */
    public function loadingBack()
    {
        $Stream = M('stream', 'tb_wms_');
        $where['bill_id'] = I('post.bill_id');
        $stream_arr = $Stream->where($where)
            ->join('left join tb_wms_location_details on tb_wms_location_details.id = tb_wms_stream.location_id')
            ->field('tb_wms_stream.*,tb_wms_location_details.box_name as location')
            ->select();
        $Bill = M('center_stock', 'tb_wms_');
        $where_bill['id'] = I('post.bill_id');
        $bill_code = $Bill->where($where_bill)->getField('bill_type');
        if (in_array($bill_code, array_keys($this->get_out()))) {
            $bill_type = L('收');
        } else {
            $bill_type = L('出');
        }
        if ($stream_arr) {
            foreach ($stream_arr as $key => &$val) {
                $model = D('Opt');
                $GUDS_ID = $val['GSKU'];
                $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->select();
                if (empty($guds)) {
                    //    $this->ajaxReturn(0, $guds, 0);
                    //    exit();
                }
                $guds['Opt'] = $guds;
                foreach ($guds['Opt'] as $key => $opt) {
                    $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
                    foreach ($opt_val as $v) {
                        $val_str = '';
                        $o = explode(':', $v);
                        $model = M('ms_opt', 'tb_');
                        $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                        if (empty($opt_val_str)) {
                            $val_str = L('标配');
                        } elseif ($opt_val_str['OPT_ID'] == '8000') {
                            $val_str = L('标配');
                        } elseif ($opt_val_str['OPT_ID'] != '8000') {
                            $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                        }
                        $guds['opt_val'][$key]['val'] .= $val_str;
                        $guds['opt_val'][$key]['GUDS_OPT_ID'] = $opt['GUDS_OPT_ID'];
                        $guds['opt_val'][$key]['GUDS_ID'] = $opt['GUDS_ID'];
                        $guds['opt_val'][$key]['SLLR_ID'] = $opt['SLLR_ID'];
                    }
                }
                $val['guds'] = $guds;
                unset($guds);
            }


            $return_arr = array('info' => '成功', "status" => "y", 'data' => $stream_arr, 'bill_type' => $bill_type);
        } else {
            $return_arr = array('info' => '失败', "status" => "n", 'data' => $stream_arr);
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 出入库管理页面优化重构
     */
    public function loading()
    {
        $Stream = M('stream', 'tb_wms_');
        $where['bill_id'] = I('post.bill_id');
        $stream_arr = $Stream->where($where)
            ->join('left join tb_wms_location_details on tb_wms_location_details.id = tb_wms_stream.location_id')
            ->field('tb_wms_stream.*,tb_wms_location_details.box_name as location')
            ->select();
        $Bill = M('center_stock', 'tb_wms_');
        $where_bill['id'] = I('post.bill_id');
        $bill_code = $Bill->where($where_bill)->getField('bill_type');
        if (in_array($bill_code, array_keys($this->get_out()))) {
            $bill_type = L('收');
        } else {
            $bill_type = L('出');
        }
        if ($stream_arr) {
            foreach ($stream_arr as $key => &$val) {
                $model = D('Opt');
                $GUDS_ID = $val['GSKU'];
                $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->select();
                if (empty($guds)) {
                    //    $this->ajaxReturn(0, $guds, 0);
                    //    exit();
                }
                $guds['Opt'] = $guds;
                foreach ($guds['Opt'] as $key => $opt) {
                    $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
                    foreach ($opt_val as $v) {
                        $val_str = '';
                        $o = explode(':', $v);
                        $model = M('ms_opt', 'tb_');
                        $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                        if (empty($opt_val_str)) {
                            $val_str = L('标配');
                        } elseif ($opt_val_str['OPT_ID'] == '8000') {
                            $val_str = L('标配');
                        } elseif ($opt_val_str['OPT_ID'] != '8000') {
                            $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                        }
                        $guds['opt_val'][$key]['val'] .= $val_str;
                        $guds['opt_val'][$key]['GUDS_OPT_ID'] = $opt['GUDS_OPT_ID'];
                        $guds['opt_val'][$key]['GUDS_ID'] = $opt['GUDS_ID'];
                        $guds['opt_val'][$key]['SLLR_ID'] = $opt['SLLR_ID'];
                    }
                }
                $val['guds'] = $guds;
                unset($guds);
            }


            $return_arr = array('info' => '成功', "status" => "y", 'data' => $stream_arr, 'bill_type' => $bill_type);
        } else {
            $return_arr = array('info' => '失败', "status" => "n", 'data' => $stream_arr);
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }
    

    public function warehouse_switch()
    {
        $this->display();
    }

    /**
     * 出入库管理
     */
    public function pageData()
    {
        $query = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $stream = new StreamModel();
        $query['calcStat'] = false;
        
        $response = $stream->orderStorage($query, false, true);
        $data ['pageIndex'] = $stream->pageIndex;
        $data ['pageSize'] = $stream->pageSize;
        $data ['totalPage'] = ceil($stream->count / $stream->pageSize);
        $data ['pageData'] = $response;
        $data ['query'] = $query;
        $data ['totalCount'] = $stream->count;
        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 出入库列表汇总信息
     */
    public function statData()
    {
        $query = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $stream = new StreamModel();
        $query['calcStat'] = true;
        $data = $stream->orderStorage($query);
        $data ['query'] = $query;
        #去除货币页面显示
        $data ['currency'] = '';

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    //出入库导出校验条数
    public function checkPageDataExport(){
        $post_data = DataModel::getData()['data']['query'];
        $model = new StreamModel();
        $response = array(
            'code'=> 200,
            'is_hint'=> false,
        );
        list($total,$query) = $model->checkOrderStorage($post_data);
        if ($total > 300000) {
            $response['code'] = 300;
            $response['msg'] = L('出入库报表导出不能超过30W条。');
        }
        if ( $total > 5000){
            $dataService = new DataService();
            $excel_name = DataModel::userNamePinyin()."-出入库管理-".time().'.csv';
            $dataService->addOne($query,4,$excel_name,$total);
            $response['is_hint'] = true;
        }
        $this->ajaxReturn($response);
    }


    /**
     * 出入库导出
     */
    public function pageDataExport()
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        $query = ZUtils::filterBlank(json_decode($this->getParams() ['post_data'], true)) ['data']['query'];
        $stream = new StreamModel();
        $response = $stream->orderStorage($query, true);

        $exportExcel = new ExportExcelModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('出入库编号'), 'field_name' => 'childBillId'],
            $key++ => ['name' => L('商品类型'), 'field_name' => 'productType'],
            $key++ => ['name' => L('出入库类别'), 'field_name' => 'type'],
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouseId'],
            $key++ => ['name' => L('归属我方公司'), 'field_name' => 'ourCompany'],
            $key++ => ['name' => L('	SKU编码'), 'field_name' => 'GSKU'],
            $key++ => ['name' => L('条形码'), 'field_name' => 'GUDSOPTUPCID'],
            $key++ => ['name' => L('属性'), 'field_name' => 'GUDSOPTVALMPNG'],
            $key++ => ['name' => L('	商品名称'), 'field_name' => 'GUDSNM'],
            $key++ => ['name' => L('批次号'), 'field_name' => 'batchCode'],
            $key++ => ['name' => L('到期日'), 'field_name' => 'deadlineDateForUse'],
            $key++ => ['name' => L('数量'), 'field_name' => 'sendNum'],
            $key++ => ['name' => L('单位'), 'field_name' => 'VALUATIONUNIT'],
            $key++ => ['name' => L('采购币种'), 'field_name' => 'currency'],
            $key++ => ['name' => L('采购单价（采购币种，含增值税）'), 'field_name' => 'unitPriceLast'],
            $key++ => ['name' => L('采购成本（采购币种,含增值税）'), 'field_name' => 'amountPriceLast'],
            $key++ => ['name' => L('采购单价（采购币种,不含增值税）'), 'field_name' => 'unitPriceNoTaxLast'],
            $key++ => ['name' => L('采购成本（采购币种,不含增值税）'), 'field_name' => 'amountPriceNoTaxLast'],
            $key++ => ['name' => L('采购单价（CNY,含增值税）'), 'field_name' => 'unitPrice'],
            $key++ => ['name' => L('采购成本（CNY,含增值税）'), 'field_name' => 'amountPrice'],
            $key++ => ['name' => L('采购单价（CNY,不含增值税）'), 'field_name' => 'unitPriceNoTax'],
            $key++ => ['name' => L('采购成本（CNY,不含增值税）'), 'field_name' => 'amountPriceNoTax'],
            $key++ => ['name' => L('采购单价（USD,含增值税）'), 'field_name' => 'unitPriceUSD'],
            $key++ => ['name' => L('采购成本（USD,含增值税）'), 'field_name' => 'amountPriceUSD'],
            $key++ => ['name' => L('采购单价（USD,不含增值税）'), 'field_name' => 'unitPriceNoTaxUSD'],
            $key++ => ['name' => L('采购成本（USD,不含增值税）'), 'field_name' => 'amountPriceNoTaxUSD'],
            $key++ => ['name' => L('PO内物流成本'), 'field_name' => 'poCost'],
            $key++ => ['name' => L('物流服务成本'), 'field_name' => 'logServiceCost'],
            $key++ => ['name' => L('运输成本'), 'field_name' => 'carryCost'],
            $key++ => ['name' => L('操作人'), 'field_name' => 'zdUser'],
            $key++ => ['name' => L('操作时间'), 'field_name' => 'zdDate'],
            $key++ => ['name' => L('收发类别'), 'field_name' => 'billType'],
            $key++ => ['name' => L('归属销售团队'), 'field_name' => 'SALETEAM'],
            $key++ => ['name' => L('销售小团队'), 'field_name' => 'SMALLSALETEAM'],
            $key++ => ['name' => L('关联单据'), 'field_name' => 'relationType'],
            $key++ => ['name' => L('关联单据号'), 'field_name' => 'linkBillIdOri'],
            $key++ => ['name' => L('关联单据号2'), 'field_name' => 'linkB5cNo'],
            $key++ => ['name' => L('备注'), 'field_name' => 'importRemark'],
        ];
        $exportExcel->data = $response;
        $exportExcel->export();
    }

    /**
     * 出入库详情
     */
    public function inventory_details()
    {
        if (IS_POST) {
            $get_outgo = I("post.outgo");
            switch ($get_outgo) {
                case 'outgoing':
                    $outgo_state = "-";
                    $outgo_state_del = null;
                    break;
                case  'storage':
                    $outgo_state = null;
                    $outgo_state_del = "-";
                    break;
                default:
                    $return_arr = array('info' => '订单出入状态异常', "status" => "n", 'data' => '');
                    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                    exit;
            }


            $bill_id = I("post.bill_id");
            $data['link_bill_id'] = I("post.link_bill_id");
            $data['warehouse_id'] = I("post.house_list");
            $data['company_id'] = I("post.company");
            $data['bill_type'] = I("post.bill_type");
            $data['bill_date'] = I("post.date");
            $data['batch'] = I("post.batch");
            $data['user_id'] = I("post.warehouse_use");
            $data['bill_state'] = I("post.bill_state");

//
            $data['channel'] = I("post.channel");
            $data['invoice'] = I("post.invoice");
            $data['business'] = I("post.business");
            $data['supplier'] = I("post.supplier");
            $data['due_date'] = I("post.due_date");
            $data['incidental'] = I("post.incidental");
            $data['total_cost'] = I("post.total_cost");
            $data['warehouse_rule'] = I("post.warehouse_rule");
            $data['sale_no'] = I("post.sale_no");
            $data['purchase_logistics_cost'] = I("post.purchase_logistics_cost");
//          outgo filter
            $this->outgoing = array_keys($this->get_outgoing());
            if (in_array($data['bill_type'], $this->outgoing)) {
                $all_list = $this->_param();

                $Stream = M('stream', 'tb_wms_');
                foreach ($all_list['order_lists'] as $key => $val) {
                    $where['bill_id'] = array('neq', '');
                    $where['GSKU'] = $val['GSKU'];
                    $stream_arr = $Stream->where($where)->field('*,sum(send_num) AS sum_num')->group('GSKU,warehouse_id')->order('id')->select();
                    if (1 != 1) {
                        $return_arr = array('info' => '库存不足', "status" => "n", 'data' => '');
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
            }


            if (empty($bill_id)) {
                $data['zd_user'] = session('m_loginname');
                $data['zd_date'] = date('Y-m-d H:i:s');
                $data['bill_id'] = $this->get_bill_id(I("post.bill_type"));
                $model = new Model();
                $model->startTrans();

                $b_id = $model->table('tb_wms_bill')->data($data)->add();

                if ($b_id) {
// add order
                    $all_list = $this->_param();

                    foreach ($all_list['order_lists'] as $key => &$val) {
                        $val['line_number'] = $key;
                        $val['bill_id'] = $b_id;
                        $val['warehouse_id'] = $data['warehouse_id'];
                        $arr_unique[] = $val['GSKU'] . '-' . $val['deadline_date_for_use'];
                        $skuId = $val['GSKU'];
                        $channel = $data['channel'];
                        $gudsId = substr($skuId, 0, -2);
                        $changeNm = (int)$val['send_num'];
                        trace($changeNm, '$changeNm');
                        if ($changeNm <= 0) {
                            $this->back = 3;
                            break;
                        }
//                      check location
                        if (!empty($val['location_id']) || !empty($val['location'])) {
                            $wher_sku['sku'] = $val['GSKU'];
                            $wher_sku['box_name'] = $val['location'];
                            $wher_sku['warehouse_id'] = $data['warehouse_id'];
                            $location_sku = $model->table('tb_wms_location_sku')->where($wher_sku)->getField('count');
//                                出库
                            if ($outgo_state == "-") {
                                if ($location_sku > $val['send_num']) {
                                    $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setDec('count', $val['send_num']);
                                } elseif ($location_sku == $val['send_num']) {
                                    $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->delete();
                                } else {
                                    $this->back = 2;
                                    $return_arr = array('info' => $val['location'] . '货位不够', "status" => "n", 'data' => '');
                                    break;
                                }
                            } else {
                                if ($location_sku > 0) {
                                    $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setInc('count', $val['send_num']);
                                } else {
                                    $wher_sku['count'] = $val['send_num'];
                                    $location_sku_state = $model->table('tb_wms_location_sku')->data($wher_sku)->add();
                                }
                            }

                        }
                        if ($changeNm >= 0) {
                            if ($outgo_state == "-") {
                                $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm . '&channel=' . $channel;
                            } else {
                                $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm . '&channel=' . $channel;
                            }
                            trace($url, '$url1');
                            $urls[] = $url;
                            $get_start = json_decode(curl_request($url), 1);
                            if ($get_start['code'] != 2000) {
                                $this->back = 1;
                                break;
                            }
                        }
                        if (empty($val['location_id'])) {
                            $val['location_id'] = $model->table('tb_wms_location_details')->where("box_name = '" . $val['location'] . "' AND warehouse_id = '" . $data['warehouse_id'] . "'")->getField('id');

                        }
//  组装数组
                        unset($val['goods_name']);
                        unset($val['UP_SKU']);
                        unset($val['bar_code']);
                        unset($val['location']);
                        unset($val['location_list']);
                        $order_lists[] = $val;


                    }
                    if ($this->back == 2) {
                        $model->rollback();
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    }
                    if ($this->back == 3) {
                        $model->rollback();
                        $return_arr = array('info' => '数量异常', "status" => "n", 'data' => $all_list);
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    }
                    if ($this->back == 1) {
                        $model->rollback();
                        $return_arr = array('info' => '接口异常：回滚订单', "status" => "n", 'data' => $url);
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    }
                    if (count($arr_unique) != count(array_unique($arr_unique))) {
                        $model->rollback();
                        $return_arr = array('info' => '单订单SKU编码+生产日期重复', "status" => "n", 'data' => $arr_unique);
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    }


                    $data_start = $model->table('tb_wms_stream')->addAll($order_lists);
                    if ($data_start) {
                        $model->commit();
                        $return_arr = array('info' => '创建成功', "status" => "y");
                    } else {
                        $error_arr['all_list'] = $all_list;
                        $error_arr['urls'] = $urls;
                        $error_arr['data_start_sql'] = $model->getLastSql();
                        $error_arr['data_start_err'] = $model->getDbError();
                        $model->rollback();
                        $return_arr = array('info' => '创建失败', "status" => "n", "data" => $data_start, "error_arr" => $error_arr);
                    }
                } else {
                    $error_arr['data_start_sql'] = $model->getLastSql();
                    $error_arr['data_start_err'] = $model->getDbError();
                    $model->rollback();
                    $return_arr = array('info' => '根订单创建失败', "status" => "n", "data" => $data, "error_arr" => $error_arr);
                }

                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                $data['zd_user'] = I("post.zd_user");
                $data['zd_date'] = I("post.zd_date");

                $data['xg_user'] = session('m_loginname');
                $data['xg_date'] = date('Y-m-d H:i:s');

                $model = new Model();
                $model->startTrans();
                $where_save['bill_id'] = $bill_id;
                $b_id = $model->table('tb_wms_bill')->where($where_save)->save($data);
// remove old order
                $where_bid['bill_id'] = I("post.this_id");
                $all_id = $model->table('tb_wms_stream')->where($where_bid)->field('id')->select();
                foreach ($all_id as $key => $val) {
                    $all_id_all[] = $val['id'];
                }
                $del_api = $model->table('tb_wms_stream')->where($where_bid)->field('id,GSKU,send_num,location_id')->select();

                foreach ($del_api as $key => $val) {
//                      check location
                    if (!empty($val['location_id'])) {
                        $wher_sku['sku'] = $val['GSKU'];
                        $wher_sku['box_name'] = $model->table('tb_wms_location_details')->where("id = '" . $val['location_id'] . "'")->getField('box_name');
                        $wher_sku['warehouse_id'] = $data['warehouse_id'];
                        $location_sku = $model->table('tb_wms_location_sku')->where($wher_sku)->getField('count');
//                                出库
                        if ($outgo_state == "-") {
                            if ($location_sku == 0) {
                                $wher_sku['count'] = $val['send_num'];
                                $location_sku_state = $model->table('tb_wms_location_sku')->data($wher_sku)->add();
                            } else {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setInc('count', $val['send_num']);
                            }
                        } else {
                            if ($location_sku == $val['send_num']) {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->delete();
                            } else {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setDec('count', $val['send_num']);
                            }
                        }
                    }

                    $skuId = $val['GSKU'];
                    $gudsId = substr($skuId, 0, -2);
                    $changeNm = $val['send_num'];
                    $channel = $data['channel'];
                    if ($outgo_state == "-") {
                        $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state_del . $changeNm . '&channel=' . $channel;
                    } else {
                        $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state_del . $changeNm . '&channel=' . $channel;
                    }
                    trace($outgo_state_del, '$outgo_state_del');
                    trace($outgo_state, '$outgo_state');
                    trace($url, '$url3');
                    $get_start = json_decode(curl_request($url), 1);

                    if ($get_start['code'] != 2000) {
                        $return_arr = array('info' => '接口异常：订单处理失败' . $get_start['msg'], "status" => "n", 'data' => $url);
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    }
                }
                $del_where['id'] = array('in', $all_id_all);
                $model->table('tb_wms_stream')->where($del_where)->delete();
// add order
                $all_list = $this->_param();
                foreach ($all_list['order_lists'] as $key => &$val) {
                    $val['id'] = null;
                    $val['bill_id'] = I("post.this_id");
                    $val['line_number'] = $key;
                    $val['warehouse_id'] = $data['warehouse_id'];
                    $arr_unique[] = $val['GSKU'] . '-' . $val['deadline_date_for_use'];
                    empty($val['goods_id']) ? $val['goods_id'] = 0 : null;
                    empty($val['should_num']) ? $val['should_num'] = 0 : null;
                    empty($val['location_id']) ? $val['location_id'] = 0 : null;
                    empty($val['duty']) ? $val['duty'] = 0 : null;
                    empty($val['add_time']) ? $val['add_time'] = '0000-00-00 00:00:00' : null;

                    //                      check location
                    if (!empty($val['location_id']) || !empty($val['location'])) {
                        $wher_sku['sku'] = $val['GSKU'];
                        $wher_sku['box_name'] = $val['location'];
                        $wher_sku['warehouse_id'] = $data['warehouse_id'];
                        $location_sku = $model->table('tb_wms_location_sku')->where($wher_sku)->getField('count');
//                                出库
                        if ($outgo_state == "-") {
                            if ($location_sku > $val['send_num']) {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setDec('count', $val['send_num']);
                            } elseif ($location_sku == $val['send_num']) {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->delete();
                            } else {
                                $this->back = 2;
                                $return_arr = array('info' => $val['location'] . '货位不够', "status" => "n", 'data' => '');
                                break;
                            }
                        } else {
                            if ($location_sku != 0) {
                                $location_sku_state = $model->table('tb_wms_location_sku')->where($wher_sku)->setInc('count', $val['send_num']);
                            } else {
                                $wher_sku['count'] = $val['send_num'];
                                $location_sku_state = $model->table('tb_wms_location_sku')->data($wher_sku)->add();
                            }
                        }

                    }


                    $skuId = $val['GSKU'];
                    $gudsId = substr($skuId, 0, -2);
                    $changeNm = $val['send_num'];
                    $channel = $data['channel'];
                    if ($changeNm > 0) {

                        if ($outgo_state == "-") {
                            $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm . '&channel=' . $channel;
                        } else {
                            $url = HOST_URL_API . '/guds_stock/update_total.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&changeNm=' . $outgo_state . $changeNm . '&channel=' . $channel;

                        }
                        trace($url, '$url2');
                        $get_start = json_decode(curl_request($url), 1);

                        if ($get_start['code'] != 2000) {
                            $model->rollback();
                            $return_arr = array('info' => '接口异常：订单处理失败' . $get_start['msg'], "status" => "n", 'data' => $url);
                            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                            exit();
                        }
                    }
                    unset($val['goods_name']);
                    unset($val['UP_SKU']);
                    unset($val['bar_code']);
                    unset($val['location']);
                    unset($val['location_list']);
                    $order_lists[] = $val;
                }

                if (count($arr_unique) != count(array_unique($arr_unique))) {
                    $return_arr = array('info' => '单订单SKU编码+生产日期重复', "status" => "n", 'data' => $arr_unique);
                    $model->rollback();
                    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                if ($this->back == 0) {
                    $data_start = $model->table('tb_wms_stream')->addAll($order_lists);
                    if ($data_start) {
                        $model->commit();
                        $return_arr = array('info' => '修改成功', "status" => "y", 'data' => $data_start);
                    } else {
                        $model->rollback();
                        $return_arr = array('info' => '修改失败', "status" => "n", 'data' => $data_start);
                    }
                } else {
                    $model->rollback();
                }

                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
        $company_arr = $this->get_company();
        $this->assign('currency_ids', json_encode(BaseModel::getCurrency(), JSON_UNESCAPED_UNICODE));
        $this->assign('company_arr', json_encode($company_arr, JSON_UNESCAPED_UNICODE));
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list_be', json_encode($this->get_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $outgo = $this->get_outgo();
        $this->assign('outgo', json_encode($outgo, JSON_UNESCAPED_UNICODE));
        $warehouse_use = $this->get_use();
        $this->assign('warehouse_use', json_encode($warehouse_use, JSON_UNESCAPED_UNICODE));
        $this->assign('currency', json_encode($this->get_currency(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->assign('bill_state', json_encode($this->bill_state, JSON_UNESCAPED_UNICODE));
        $this->assign('user_name', $_SESSION['m_loginname']);
        $this->assign('user_default', json_encode($this->get_default(), JSON_UNESCAPED_UNICODE));

//        add m
        $this->assign('business_list', json_encode($this->get_business_list(), JSON_UNESCAPED_UNICODE));
        $this->assign('supplier_list', json_encode($this->get_supplier_list(), JSON_UNESCAPED_UNICODE));

        $this->display();
    }

    /**
     * 出库展示
     */
    public function out_or_in()
    {
//        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE)); // 仓库
//        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));// 所有的仓库
//        $this->assign('outgo', json_encode($this->get_outgo(), JSON_UNESCAPED_UNICODE));// 出入库类型
//        $this->assign('currency', json_encode($this->get_currency(), JSON_UNESCAPED_UNICODE));// 币种
//        $this->assign('warehouse_rule', json_encode($this->getwarehouse_rule(I('get.outgoing')), JSON_UNESCAPED_UNICODE));// 出入库规则
        $billModel = M('bill', 'tb_wms_');
        $where['id'] = $this->getParams()['bill_id'];
        $bill = $billModel->where($where)->find();
        if (in_array($bill['bill_type'], array_keys($this->get_out()))) {// 入库类型
            $this->assign('outgo_state', 'storage');
            $flag = 'in';
            $data = $this->orderIn($bill);
        } else {// 出库
            $this->assign('outgo_state', 'outgoing');
            $flag = 'out';
            $data = $this->orderOut($bill);
        }
        if ($data) {
            if ($flag == 'out') {
                $skuId = array_unique(array_column($data ['data']['guds'], 'SKU_ID'));
            } else {
                $skuId = array_unique(array_column($data ['data']['guds'], 'GSKU'));
            }

            $stock = A('Home/Stock');
            $imgs = $stock->getGudsImg($skuId);
            if ($imgs) {
                $data ['data']['guds'] = array_map(function ($r) use ($imgs, $flag) {
                    if ($flag == 'out') {
                        $r ['img'] = $imgs [$r ['SKU_ID']];
                    } else {
                        $r ['img'] = $imgs [$r ['GSKU']];
                    }
                    return $r;
                }, $data ['data']['guds']);
            }
        }

        $this->ajaxReturn($data, json, 1);
        //$this->display($flag);
    }

    /**
     * 入库单
     */
    public function orderIn($bill)
    {
        $batchModel = M('_wms_batch', 'tb_');// 获取批次数据
        $where = null;// 查询条件重置
        $where['tb_wms_batch.bill_id'] = $bill ['id'];
        $fields = 'tb_wms_stream.GSKU, tb_ms_guds.GUDS_NM, tb_ms_guds_opt.GUDS_OPT_VAL_MPNG,
                   tb_ms_guds_opt.GUDS_OPT_UPC_ID, tb_wms_batch.batch_code, tb_wms_stream.deadline_date_for_use,
                   tb_wms_stream.send_num, tb_wms_stream.currency_id, tb_ms_guds.VALUATION_UNIT,
                   tb_wms_stream.unit_price, tb_wms_stream.unit_money';
        $batchs = $batchModel
            ->field($fields)
            ->join('left join tb_wms_stream on tb_wms_batch.stream_id = tb_wms_stream.id')
            ->join('left join tb_ms_guds_opt on tb_wms_stream.GSKU = tb_ms_guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds on SUBSTR(tb_ms_guds_opt.GUDS_OPT_ID, 1, 8) = tb_ms_guds.GUDS_ID')
            ->where($where)
            ->select();
        $stockAction = A('Home/Stock');
        // 属性转码
        foreach ($batchs as $k => &$v) {
            $v ['GUDS_OPT_VAL_MPNG'] = $stockAction->gudsOptsMerge($v ['GUDS_OPT_VAL_MPNG']);// 属性
            $v ['VALUATION_UNIT'] = BaseModel::getUnit()[$v ['VALUATION_UNIT']]['CD_VAL'];// 单位
            $v ['currency_id'] = BaseModel::getCurrency()[$v ['currency_id']];
            //$v ['currency_id'] = $this->get_currency()[$v ['currency_id']]['CD_VAL'];// 币种
        }
        // 出入库单转码
        if ($bill) {
            $bill ['warehouse_id'] = $this->get_all_warehouse()[$bill ['warehouse_id']]['warehouse'];// 仓库
            $bill ['bill_type'] = $this->get_outgo()[$bill ['bill_type']]['CD_VAL'];// 订单类型
            $bill ['SALE_TEAM'] = BaseModel::saleTeamCdExtend()[$bill ['SALE_TEAM']]['CD_VAL'];// 销售团队
        }
        $this->assign('bill', json_encode($bill, JSON_UNESCAPED_UNICODE));
        $this->assign('batchs', json_encode($batchs, JSON_UNESCAPED_UNICODE));
        $response = [];
        if ($bill or $batchs) {
            $data ['code'] = 2000;
            $data ['msg'] = 'success';
            $data ['data'] = [
                'bill' => $bill,
                'guds' => $batchs
            ];
        } else {
            $data ['code'] = 1000;
            $data ['msg'] = '无数据';
        }
        $response [] = $data;
        return $data;
    }

    /**
     * 出库单
     */
    public function orderOut($bill)
    {
        $where['tb_wms_batch.bill_id'] = $bill ['id'];
        $fields = [
            'tb_wms_batch.id',
            'tb_wms_batch.SKU_ID',// SKU
            'tb_ms_guds.GUDS_NM',// 商品名
            'tb_ms_guds_opt.GUDS_OPT_VAL_MPNG',// 属性
            'tb_ms_guds_opt.GUDS_OPT_UPC_ID',// 条形码
            //'tb_wms_stream.send_num',// 数量
            'tb_ms_guds.VALUATION_UNIT',// 单位
            'tb_wms_batch.purchase_order_no', // 采购单号
            'tb_wms_batch.purchase_team_code',// 采购团队
            'tb_wms_batch.create_time as in_time',// 入库时间
            'tb_wms_batch.deadline_date_for_use',// 到期日
            'tb_wms_batch.batch_code',// 批次号
            'tb_pur_order_detail.our_company',// 公司名,
            'tb_wms_batch.sale_team_code'
        ];
        $billModel = M('_wms_bill', 'tb_');
        $ret = $billModel->where('id = ' . $bill ['id'])->find();
        if ($ret ['batch_ids']) {
            $batchIds = json_decode($ret ['batch_ids'], true);
            foreach ($batchIds as $k => $v) {
                foreach ($v as $s => $a) {
                    $batch_ids [] = $a ['batchId'];
                    $batchOperationData [$a['batchId']] = $a ['num'];
                }
            }
        }
        // 1: 获得 tb_wms_batch 数据
        $batchModel = D('TbWmsBatch');
        //$batch_ids = $bill['batch_ids'];
        $batch_conditions ['tb_wms_batch.id'] = ['in', $batch_ids];
        $batchs = $batchModel
            ->field($fields)
            ->join('left join tb_pur_order_detail on tb_wms_batch.purchase_order_no = tb_pur_order_detail.procurement_number')
            //->join('left join tb_wms_stream on tb_wms_stream.bill_id = tb_wms_batch.bill_id')
            ->join('LEFT JOIN tb_ms_guds_opt ON tb_wms_batch.SKU_ID = tb_ms_guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds on SUBSTR(tb_ms_guds_opt.GUDS_OPT_ID, 1, 8) = tb_ms_guds.GUDS_ID')
            ->where($batch_conditions)
            ->select();
        //-转换为以 batch_id 为key的数组
        foreach ($batchs as $key => $value) {
            // 取到 batch_id 用来查询 batch_order 表数据
            $data [$value ['id']] = $value;
        }
        $batchs = $data;
        unset($data);
        $stockAction = A('Home/Stock');
        // 2: 通过 bill 的 batch_ids 查询到批次相关信息
        $batch_order_conditions ['tb_wms_batch_order.batch_id'] = ['in', $batch_ids];
        $batch_order_conditions ['tb_wms_batch_order.use_type'] = ['eq', 2];
        if ($bill ['bill_id']) $batch_order_conditions ['tb_wms_batch_order.ORD_ID'] = ['eq', $bill ['link_bill_id']];
        $batchOrderModel = M('_wms_batch_order', 'tb_');
        $batchOrders = $batchOrderModel->where($batch_order_conditions)->select();

        // 如果存在 batch_orders 数据则循环追加到 batchs 下的 order_child 下
        if ($batchOrders) {
            foreach ($batchOrders as $k => &$v) {
                $v ['sale_team_code'] = BaseModel::personLiable()[$v ['sale_team_code']]['CD_VAL'];
                $batchs [$v ['batch_id']]['order_child'][] = $v;
            }
        }
        //-属性转码
        foreach ($batchs as $k => &$v) {
            $v ['GUDS_OPT_VAL_MPNG'] = $stockAction->gudsOptsMerge($v ['GUDS_OPT_VAL_MPNG']);// 属性
            $v ['VALUATION_UNIT'] = BaseModel::getUnit()[$v ['VALUATION_UNIT']]['CD_VAL'];// 单位
            //$v ['currency_id'] = $this->get_currency()[$v ['currency_id']]['CD_VAL'];// 币种
            $v ['our_company'] = BaseModel::ourCompany()[$v ['our_company']];
            $v ['purchase_team_code'] = BaseModel::spTeamCd()[$v ['purchase_team_code']];
            $v ['sale_team_code'] = BaseModel::saleTeamCd()[$v ['sale_team_code']];
            if ($v ['order_child']) {
                foreach ($v ['order_child'] as $s => $t) {
                    $t ['sale_team_code'] = BaseModel::saleTeamCd()[$t['sale_team_code']]['CD_VAL'];
                }
            } else {
                $v ['order_child'] = '';
            }
        }
        // 出入库单转码
        if ($bill) {
            $bill ['warehouse_id'] = $this->get_show_warehouse()[$bill ['warehouse_id']]['warehouse'];// 仓库
            $bill ['bill_type'] = $this->get_outgo()[$bill ['bill_type']]['CD_VAL'];// 订单类型
            BaseModel::personLiable()[$bill ['SALE_TEAM']]['CD_VAL'] ? $bill ['SALE_TEAM'] = BaseModel::personLiable()[$bill ['SALE_TEAM']] : $bill ['SALE_TEAM'] = BaseModel::saleTeamCdExtend()[$bill ['SALE_TEAM']];
            $bill ['channel'] = BaseModel::getChannels()[$bill ['channel']];
        }
        $this->assign('bill', json_encode($bill, JSON_UNESCAPED_UNICODE));
        $this->assign('batchs', json_encode($batchs, JSON_UNESCAPED_UNICODE));
        $response = [];
        foreach ($batchs as $k => &$v) {
            $v ['send_num'] = $batchOperationData [$k];
        }
        if ($bill or $batchs) {
            $data ['code'] = 2000;
            $data ['msg'] = 'success';
            $data ['data'] = [
                'bill' => $bill,
                'guds' => $batchs
            ];
        } else {
            $data ['code'] = 1000;
            $data ['msg'] = '无数据';
        }
        $response [] = $data;
        return $data;
    }

    /**
     * 出入库展示
     */
    public function inventory_edit()
    {
        // 基础数据
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

        $this->display();
    }

    /**
     * 出入库修改
     */
    public function inventory_xiugai()
    {
//        init loading
        $this->assign('company_arr', json_encode($this->get_company(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_list_be', json_encode($this->get_warehouse(), JSON_UNESCAPED_UNICODE));

        $this->assign('warehouse_use', json_encode($this->get_use(), JSON_UNESCAPED_UNICODE));
        $this->assign('currency', json_encode($this->get_currency(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->assign('bill_state', json_encode($this->bill_state, JSON_UNESCAPED_UNICODE));

        $id = I('get.bill_id');
        $Bill = M('bill', 'tb_wms_');
        $where['id'] = $id;
        $bills = $Bill->where($where)->select();
        $this->assign('bills', json_encode($bills, JSON_UNESCAPED_UNICODE));

        if (in_array($bills[0]['bill_type'], array_keys($this->get_out()))) {
            $this->assign('outgo_state', 'storage');
            $this->assign('outgo', json_encode($this->get_outgo('storage'), JSON_UNESCAPED_UNICODE));
        } else {
            $this->assign('outgo_state', 'outgoing');
            $this->assign('outgo', json_encode($this->get_outgo('outgoing'), JSON_UNESCAPED_UNICODE));

        }

        $Stream = M('stream', 'tb_wms_');
        $wheres['bill_id'] = $id;
        $stream = $Stream->where($wheres)
            ->join('left join tb_wms_location_details on tb_wms_location_details.id = tb_wms_stream.location_id')
            ->field('tb_wms_stream.*,tb_wms_location_details.box_name as location')
            ->select();
        $this->assign('stream', json_encode($stream, JSON_UNESCAPED_UNICODE));
        $this->assign('this_user', session('m_loginname'));
//        add m
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('business_list', json_encode($this->get_business_list(), JSON_UNESCAPED_UNICODE));
        $this->assign('supplier_list', json_encode($this->get_supplier_list(), JSON_UNESCAPED_UNICODE));

        $this->display();
    }

    /**
     * 出入库确认
     */
    public function confirm_order()
    {
        $Bill = M('bill', 'tb_wms_');
        $where['id'] = I('post.bill_id');
        $data['bill_state'] = 1;
        $data['qr_user'] = session('m_loginname');
        $data['qr_date'] = date('Y-m-d H:i:s');
        $bills = $Bill->where($where)->save($data);
        if ($bills) {
            $return_arr = array('info' => '确认成功', "status" => "y", 'data' => $data);
        } else {
            $return_arr = array('info' => '确认失败', "status" => "n", 'bills' => $bills);
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 取消确认
     */
    public function confirm_no()
    {
        $Bill = M('bill', 'tb_wms_');
        $where['id'] = I('post.bill_id');
        $data['bill_state'] = 0;
        $data['qr_user'] = '';
        $data['qr_date'] = '';
        $bills = $Bill->where($where)->save($data);
        if ($bills) {
            $return_arr = array('info' => '取消确认成功', "status" => "y", 'data' => $data);
        } else {
            $return_arr = array('info' => '取消确认失败', "status" => "n", 'bills' => $bills);
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * SKU查询
     */
    public function search_sku()
    {
        if (IS_POST) {
            $location = $this->get_goods(I("post.GSKU"));
            if (!empty($location)) {
                if (count($location) == 1) {
                    foreach ($location as $key => $val) {
                        $data_one = $val;
                    }
                    $return_arr = array('info' => '查询成功', "status" => "y", 'data' => $data_one, 'key' => 0);
                } else {
                    $return_arr = array('info' => '查询成功', "status" => "y", 'data' => $location, 'key' => 1);
                }
            } else {
                $return_arr = array('info' => '查询无结果', "status" => "n", 'data' => $location);
            }
        } else {
            $return_arr = array('info' => '错误请求', "status" => "n");
        }
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 库存锁定/解锁
     */
    public function lock()
    {
        $post = $this->_param();
        $skuadd = I('get.skuadd');
        if (!empty($skuadd)) {
            $this->assign('skuadd', $skuadd);
        }
        if (empty($post) || !empty($skuadd)) {
            $post = array(
                'init_key' => 'SKU',
                'init_value' => '',
                'DELIVERY_WAREHOUSE' => '',
            );
        }
        //锁定平台
        $this->assign('plat_ret', json_encode(BaseModel::getStoreInfo(), JSON_UNESCAPED_UNICODE));
        $this->assign('lock_list', $this->get_lock('goods', $post));
        $this->assign('post', json_encode($post, JSON_UNESCAPED_UNICODE));
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));

        $this->display();
    }

    /**
     * 库存锁重构版
     */
    public function lock_extend()
    {
        // 查询条件
        $params = $this->getParams();
        // 获取批次相关库存锁数据
        $ret = $this->get_lock_extend();
        if ($ret) {
            $skuId = array_unique(array_column($ret, 'SKU_ID'));
            $stock = A('Home/Stock');
            $imgs = $stock->getGudsImg($skuId);
            if ($imgs) {
                $ret = array_map(function ($lock) use ($imgs) {
                    $lock ['img'] = $imgs [$lock ['SKU_ID']];
                    return $lock;
                }, $ret);
            }
        }
        $this->assign('lock_list', json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->assign('post', json_encode($params, JSON_UNESCAPED_UNICODE));
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode(BaseModel::getAllDeliveryWarehouseLock(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->assign('plat_ret', json_encode(BaseModel::getStoreInfo(), JSON_UNESCAPED_UNICODE));
        $this->assign('stores', json_encode(BaseModel::getStores(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 新版锁库数据，满足批次需求
     */
    public function get_lock_extend()
    {
        $params = $this->getParams();

        $model = M('_wms_batch_child', 'tb_');
        // 需要查询的字段
        $fields = [
            'tb_wms_batch_child.id',
            'tb_wms_batch_child.SKU_ID',        // SKU 编码
            'tb_wms_batch_child.channel',       // 第三方 SKU 编码
            'tb_ms_guds.GUDS_NM',               // 商品名
            'tb_ms_guds_opt.GUDS_OPT_CODE',// 自编码
            'tb_ms_guds.VALUATION_UNIT',        // 单位
            'tb_ms_guds.DELIVERY_WAREHOUSE',    // 仓库
            'SUM(tb_wms_batch_child.available_for_sale_num) as locked',// 锁定库存
            'tb_wms_batch_child.store_id',        // 锁定店铺
            'tb_wms_batch_child.CHANNEL_SKU_ID'
        ];
        $conditions = [];
        $params ['init_key'] ? $conditions [$params ['init_key']] = ['eq', $params ['init_value']] : '';
        $ret = $model->where($conditions)
            ->field(implode(',', $fields))
            ->join('tb_ms_guds ON SUBSTR(tb_wms_batch_child.SKU_ID, 1, 8) = tb_ms_guds.GUDS_ID')// 商品详情
            ->join('LEFT JOIN tb_ms_guds_opt ON tb_wms_batch_child.SKU_ID = tb_ms_guds_opt.GUDS_OPT_ID')// 单位
            ->group('tb_wms_batch_child.SKU_ID, tb_wms_batch_child.channel, tb_wms_batch_child.CHANNEL_SKU_ID, tb_wms_batch_child.store_id')
            ->select();
        // 循环将商品属性替换掉
        $stockAction = A('Home/Stock');
        $warehouses = $this->get_show_warehouse();
        foreach ($ret as $k => &$v) {
            $v ['GUDS_OPT_VAL_MPNG'] = $stockAction->gudsOptsMerge($v ['GUDS_OPT_VAL_MPNG']);
            $v ['VALUATION_UNIT'] = BaseModel::getUnit()[$v ['VALUATION_UNIT']]['CD_VAL'];
            $v ['DELIVERY_WAREHOUSE'] = $warehouses [$v ['DELIVERY_WAREHOUSE']]['warehouse'];
        }
        return $ret;
    }

    /**
     * 获取店铺
     */
    public function get_store()
    {
        $post_json = file_get_contents('php://input', 'r');
        $post = json_decode($post_json);
        $store_ret = BaseModel::getStoreInfo($post->plat_code);
        if (count($store_ret) > 0) {
            $return_data = [
                code => '20000',
                msg => 'success',
                data => $store_ret
            ];
        } else {
            $return_data = [
                code => '40001',
                msg => '没有相关数据',
                data => $store_ret
            ];
        }
        echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取渠道SKU
     */
    public function get_channel_sku()
    {
        $post_json = file_get_contents('php://input', 'r');
        $post = json_decode($post_json);
        $drguds_opt = BaseModel::getStoreInfo();
        if (count($drguds_opt) > 0) {
            $return_data = [
                code => '20000',
                msg => 'success',
                data => $drguds_opt
            ];
        } else {
            $return_data = [
                code => '40001',
                msg => '没有相关数据',
                data => $drguds_opt
            ];
        }
        echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 锁库日志
     */
    public function lock_log()
    {
        $post = $this->_param();
        if (empty($post)) {
            $post = array(
                'init_key' => 'SKU',
                'init_value' => '',
                'DELIVERY_WAREHOUSE' => '',
            );
        }
        $this->assign('lock_list', $this->get_lock_log($post));

        $this->assign('post', json_encode($post, JSON_UNESCAPED_UNICODE));
        $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('metering', json_encode($this->get_metering(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 获取渠道SKU
     */
    public function get_gshopper_sku()
    {
        $post_json = file_get_contents('php://input', 'r');
        $post = json_decode($post_json);
        /*  $Guds_store = M('guds_store', 'tb_ms_');
          $where['GUDS_ID'] = substr($post->sku, 0, -2);
  //        $where['PLAT_CD'] = $post->channel;
          $where['STORE_STATUS'] = 'N000840300';
          $guds_store = $Guds_store->where($where)->field('ID,GUDS_ID,GUDS_NM')->select();
          if (count($guds_store) > 0) {*/
        $Drguds_opt = M('drguds_opt', 'tb_ms_');
        $where_opt['tb_ms_guds_store.PLAT_CD'] = $post->plat_cd;
        $where_opt['GUDS_OPT_ID'] = $post->sku;
        $where_opt['THRD_SKU_ID'] = array('neq', '');
        $drguds_opt = $Drguds_opt
            ->join('left join tb_ms_guds_store on tb_ms_drguds_opt.GUDS_ID = tb_ms_guds_store.ID')
            ->where($where_opt)->field('GUDS_OPT_ID,SKU_ID,THRD_SKU_ID')->select();
        if (count($drguds_opt) > 0) {
            $return_data = [
                code => '20000',
                msg => 'success',
                data => $drguds_opt
            ];
        } else {
            $return_data = [
                code => '40001',
                msg => '无渠道SKU',
                data => $drguds_opt
            ];
        }
        echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
    }


    /**
     *展示商品
     */
    public function search_goods()
    {
        $GUDS_ID = I('post.GSKU');
        $guds_s = M('guds', 'tb_ms_');
        $where_guds['MAIN_GUDS_ID'] = substr($GUDS_ID, 0, -2);
        $res = $guds_s->where($where_guds)->field('GUDS_CNS_NM,GUDS_CODE,VALUATION_UNIT,DELIVERY_WAREHOUSE')->select();
        if (count($res) > 0) {
            $return = array('status' => 'y', 'msg' => '', 'data' => $res);
        } else {
            $return = array('status' => 'n', 'msg' => 'SKU异常', 'data' => $res);
        }
        echo json_encode($return, JSON_UNESCAPED_UNICODE);
    }


    /**
     * 获取锁定
     */
    public function get_lock($goods = null, $post = null, $sku = null, $channel = null)
    {
        $Stand = M('center_stock', 'tb_wms_');
        $where_stand['total_inventory'] = array('neq', 0);
        $where_stand['channel'] = array('neq', 'N000830100');

        if (!empty($sku) && !empty($channel)) {
            $where_stand['SKU_ID'] = $sku;
            $where_stand['channel'] = $channel;
        }

        if ($post['init_key'] == 'SKU') {
            $where_stand['SKU_ID'] = array('like', "%" . $post['init_value'] . "%");
        }
        $show = null;
        if (($post['init_key'] != 'SKU' && empty($post['init_value'])) && empty($post['DELIVERY_WAREHOUSE'])) {
            import('ORG.Util.Page');
            $count = $Stand->where($where_stand)->count();
            $Page = new Page($count, 30);
            $show = $Page->show();
        } else {
            $stream_arr = $Stand->where($where_stand)
                ->field('SKU_ID,total_inventory,channel,CHANNEL_SKU_ID, locking')
                ->order('SKU_ID,channel desc')
                ->select();
        }
        $this->assign('pages', $show);

        if ('goods' == $goods) {
            $guds_s = M('guds', 'tb_ms_');
            foreach ($stream_arr as $key => $val) {
                $where_guds['MAIN_GUDS_ID'] = substr($val['SKU_ID'], 0, -2);
                if (empty($post['init_value']) && empty($post['DELIVERY_WAREHOUSE'])) {
                    $val['goods'] = $guds_s->where($where_guds)->field('GUDS_CNS_NM,GUDS_CODE,VALUATION_UNIT,DELIVERY_WAREHOUSE')->select();
                    empty($val['goods']) ? '' : $vals[] = $val;

                } else {
                    if (($post['init_key'] != 'SKU' && empty($post['init_value'])) && empty($post['DELIVERY_WAREHOUSE'])) {

                    } else {
                        empty($post['init_value']) ? '' : $where_guds[$post['init_key']] = array('like', "%" . $post['init_value'] . "%");
                        empty($post['DELIVERY_WAREHOUSE']) ? '' : $where_guds['DELIVERY_WAREHOUSE'] = array('like', "%" . $post['DELIVERY_WAREHOUSE'] . "%");
                        $val['goods'] = $guds_s->where($where_guds)->field('GUDS_CNS_NM,GUDS_CODE,VALUATION_UNIT,DELIVERY_WAREHOUSE')->select();
                        empty($val['goods']) ? '' : $vals[] = $val;

                    }
                }

            }
        }
        $return = $vals;
        return json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     *获取锁日志
     */
    public function get_lock_log($post, $goods = 'goods')
    {
        $Lock_log = M('lock_log', 'tb_wms_');
        if (!empty($sku) && !empty($channel)) {
            $where_stand['GSKU'] = $sku;
            $where_stand['channel'] = $channel;
        }

        if ($post['init_key'] == 'SKU') {
            $where_stand['GSKU'] = array('like', "%" . $post['init_value'] . "%");
        }
        $show = null;
        if (($post['init_key'] != 'SKU' && empty($post['init_value'])) && empty($post['DELIVERY_WAREHOUSE'])) {
            import('ORG.Util.Page');
            $count = $Lock_log->where($where_stand)->count();
            $Page = new Page($count, 30);
            $show = $Page->show();
            $stream_arr = $Lock_log->where($where_stand)
                ->field('tb_wms_lock_log.GSKU,tb_wms_lock_log.lock_sum,tb_wms_lock_log.unlock_sum,tb_wms_lock_log.channel,tb_wms_lock_log.operate_time,bbm_admin.M_NAME as user_name')
                ->join('left join bbm_admin on bbm_admin.M_ID =  tb_wms_lock_log.user_id')
                ->order('tb_wms_lock_log.operate_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $stream_arr = $Lock_log->where($where_stand)
                ->field('tb_wms_lock_log.GSKU,tb_wms_lock_log.lock_sum,tb_wms_lock_log.unlock_sum,tb_wms_lock_log.channel,tb_wms_lock_log.operate_time,bbm_admin.M_NAME as user_name')
                ->join('left join bbm_admin on bbm_admin.M_ID =  tb_wms_lock_log.user_id')
                ->order('tb_wms_lock_log.operate_time desc')
                ->select();
        }

        $this->assign('pages', $show);


        if ('goods' == $goods) {
            $guds_s = M('guds', 'tb_ms_');
            trace($post, '$post');
            foreach ($stream_arr as $key => $val) {
                $where_guds['MAIN_GUDS_ID'] = substr($val['GSKU'], 0, -2);
                if (empty($post['init_value']) && empty($post['DELIVERY_WAREHOUSE'])) {
                    $val['goods'] = $guds_s->where($where_guds)->field('GUDS_CNS_NM,GUDS_CODE,VALUATION_UNIT,DELIVERY_WAREHOUSE')->select();
                    empty($val['goods']) ? '' : $vals[] = $val;

                } else {
                    if (($post['init_key'] != 'SKU' && empty($post['init_value'])) && empty($post['DELIVERY_WAREHOUSE'])) {

                    } else {
                        empty($post['init_value']) ? '' : $where_guds[$post['init_key']] = array('like', "%" . $post['init_value'] . "%");
                        empty($post['DELIVERY_WAREHOUSE']) ? '' : $where_guds['DELIVERY_WAREHOUSE'] = array('like', "%" . $post['DELIVERY_WAREHOUSE'] . "%");
                        $val['goods'] = $guds_s->where($where_guds)->field('GUDS_CNS_NM,GUDS_CODE,VALUATION_UNIT,DELIVERY_WAREHOUSE')->select();
                        empty($val['goods']) ? '' : $vals[] = $val;

                    }
                }

            }
        }
        $return = $vals;
        return json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 按批次锁库
     */
    public function batch_lock()
    {
        $params = $this->getParams();
        $model = M('_wms_batch', 'tb_');
        // 取得与店铺相关的销售团队
        $storeModel = M('_ms_store', 'tb_');
        $res = $storeModel->field('SALE_TEAM_CD')->where('id = ' . $params ['store_id'])->find();
        // 通过店铺取得销售团队，通过销售团队 code 与 sku 查询批次表中可售大于 0 的批次进行锁库
        $conditions = [
            'tb_wms_batch.channel' => 'N000830100', // 固定不变，从 B5C 锁到其他平台去
            'CHANNEL_SKU_ID' => $params ['channel_sku_id'],
            'SKU_ID' => $params ['sku'],
            'all_available_for_sale_num' => [
                'gt', 0
            ]
            //'sale_team_code' => null
        ];

        // 存在channel_sku_id则取，不存在则销毁，channel_sku_id 只有 Gshopper 会用到
        if ($params ['channel_sku_id'] == 0 or $params ['channel_sku_id'] == '') {
            unset($conditions ['CHANNEL_SKU_ID']);
        } else {
            unset($conditions ['CHANNEL_SKU_ID']);
        }
        if ($params ['DELIVERY_WAREHOUSE']) $conditions ['tb_wms_bill.warehouse_id'] = $params ['DELIVERY_WAREHOUSE'];
        $saleTeams = [$res ['SALE_TEAM_CD'], 'N001281500'];
        $saleWhere = ' sale_team_code in(';
        foreach ($saleTeams as $key => $value) {
            $saleWhere .= '"' . $value . '",';
        }
        $saleWhere = rtrim($saleWhere, ',');
        $saleWhere .= ')';
        // 增加销售团队条件限制，增加可售数量大于 0 条件限制
        //$conditions ['sale_team_code'] = ['in', $saleTeams];
        $batchs = $model
            ->field('tb_wms_batch.*, tb_wms_bill.warehouse_id')// , DISTINCT tb_ms_guds.SHELF_LIFE
            ->join('left join tb_ms_guds on tb_wms_batch.GUDS_ID = tb_ms_guds.GUDS_ID')
            ->join('left join tb_wms_bill on tb_wms_batch.bill_id = tb_wms_bill.id')
            ->where($conditions)
            ->where($saleWhere . ' OR CASE WHEN SUBSTR(tb_ms_guds.CAT_CD, 1, 3) = "C07" THEN PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(tb_wms_batch.create_time, \'%Y%m\')) > 3 and tb_wms_batch.total_inventory > 0
                WHEN SUBSTR(tb_ms_guds.CAT_CD, 1, 3) != "C07" THEN PERIOD_DIFF(DATE_FORMAT(NOW(), \'%Y%m\'), DATE_FORMAT(tb_wms_batch.create_time, \'%Y%m\')) > 5 and tb_wms_batch.total_inventory > 0 END')
            ->select();
        if ($batchs) {
            foreach ($batchs as $key => &$value) {
                $value ['locks'] = 0;
            }
        }
        if ($batchs) {
            $skuId = array_unique(array_column($batchs, 'SKU_ID'));
            $stock = A('Home/Stock');
            $imgs = $stock->getGudsImg($skuId);
            if ($imgs) {
                $batchs = array_map(function ($r) use ($imgs) {
                    $r ['img'] = $imgs [$r ['SKU_ID']];
                    return $r;
                }, $batchs);
            }
        }
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('store_id', json_encode($params ['store_id'], JSON_UNESCAPED_UNICODE));
        $this->assign('batchs', json_encode($batchs, JSON_UNESCAPED_UNICODE));
        $this->assign('channel', json_encode($params ['channel'], JSON_UNESCAPED_UNICODE));
        $this->assign('channel_sku_id', json_encode($params ['channel_sku_id'], JSON_UNESCAPED_UNICODE));
        $this->assign('sku', json_encode($params ['sku'], JSON_UNESCAPED_UNICODE));
        $this->assign('count', count($batchs));
        $this->assign('sale_teams', json_encode(BaseModel::saleTeamCd(), JSON_UNESCAPED_UNICODE));
        $this->assign('sp_teams', json_encode(BaseModel::spTeamCd(), JSON_UNESCAPED_UNICODE));
        $this->assign('guds_name', json_encode($params ['guds_name'], JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 按批次解锁
     */
    public function batch_unlock()
    {
        $params = $this->getParams();
        $model = M('_wms_batch_child', 'tb_');
        if ($params ['channel_sku_id'] == '' or $params ['channel_sku_id'] == 0) $params ['channel_sku_id'] = 0;
        $conditions = [
            'tb_wms_batch_child.channel' => $params ['channel'],
            'tb_wms_batch_child.CHANNEL_SKU_ID' => $params ['channel_sku_id'],
            'tb_wms_batch_child.SKU_ID' => $params ['sku'],
            'tb_wms_batch_child.store_id' => $params ['store_id']
        ];
        if ($params ['channel_sku_id'] == 0 or $params ['channel_sku_id'] == '') {
            unset($conditions ['CHANNEL_SKU_ID']);
        }
        $fields = [
            'DISTINCT tb_wms_batch_child.id',
            'tb_wms_batch.sale_team_code',
            'tb_wms_batch.purchase_team_code',
            'tb_ms_guds.SHELF_LIFE',
            'tb_wms_batch_child.available_for_sale_num as locked',
            'tb_wms_batch.batch_code',
            'tb_wms_batch.purchase_order_no',
            'tb_wms_batch.deadline_date_for_use',
            'tb_wms_batch_child.store_id',
            'tb_wms_batch_child.channel',
            'tb_wms_batch_child.CHANNEL_SKU_ID',
            'tb_wms_batch_child.batch_id',
            'tb_wms_bill.warehouse_id'
        ];
        $batchs = $model
            ->field(implode(',', $fields))
            ->join('LEFT JOIN tb_wms_batch on tb_wms_batch_child.batch_id = tb_wms_batch.id')
            ->join('LEFT JOIN tb_ms_guds on SUBSTR(tb_wms_batch.SKU_ID, 1, 8) = tb_ms_guds.GUDS_ID')
            ->join('LEFT JOIN tb_wms_bill on tb_wms_batch.bill_id = tb_wms_bill.id')
            ->where($conditions)
            ->select();
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('store_id', json_encode($params ['store_id'], JSON_UNESCAPED_UNICODE));
        $this->assign('batchs', json_encode($batchs, JSON_UNESCAPED_UNICODE));
        $this->assign('channel', json_encode($params ['channel'], JSON_UNESCAPED_UNICODE));
        $this->assign('channel_sku_id', json_encode($params ['channel_sku_id'], JSON_UNESCAPED_UNICODE));
        $this->assign('sku', json_encode($params ['sku'], JSON_UNESCAPED_UNICODE));
        $this->assign('count', count($batchs));
        $this->assign('sale_teams', json_encode(BaseModel::saleTeamCd(), JSON_UNESCAPED_UNICODE));
        $this->assign('sp_teams', json_encode(BaseModel::spTeamCd(), JSON_UNESCAPED_UNICODE));
        $this->assign('guds_name', json_encode($params ['guds_name'], JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 保存批次锁库
     */
    public function savelock()
    {
        $post = file_get_contents('php://input', 'r');
        $post = json_decode($post);
        foreach ($post as $k => $v) {
            if ($v->locks <= 0) {
                unset($post[$k]);
            }
        }
        $baseData = $this->getParams();
        $model = new StockModel();
        if ($_GET['isunlock']) $model->isunlock = '-';
        $model->store_id = $_GET['store_id'];
        $model->channel = $baseData ['channel'];
        $model->channel_sku_id = $baseData ['channel_sku_id'];
        $model->sku = $baseData ['sku'];
        $model->isunlock = $baseData ['isunlock'];
        $ret = $model->parseLock($post);
        // 日志数据
        $log['sku'] = $lock['skuid'] = $baseData ['sku'];
        $lock['gudsid'] = substr($lock['skuid'], 0, -2);
        $log['total_inventory'] = $lock['number'] = $model->isunlock ? 0 : $model->total_lock_num;
        $log['channel'] = $lock['channel'] = $baseData ['channel'];
        $log['unlock_sum'] = $model->isunlock ? $model->total_lock_num : 0;
        $log['return'] = serialize($ret);
        $log_arr[] = $log;
        $this->add_lock_log($log_arr);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    /**
     *保存锁
     */
    public function save_lock()
    {
        $post = file_get_contents('php://input', 'r');
        $post = json_decode($post);
        $params = $post->params;

        $log['sku'] = $lock['skuid'] = $params->sku;
        $lock['gudsid'] = substr($lock['skuid'], 0, -2);
        $log['total_inventory'] = $lock['number'] = $params->total_inventory;
        $log['channel'] = $lock['channel'] = $params->channel;

        $log['channelSkuId'] = $lock['channelSkuId'] = $params->channel_sku == '' ? 0 : $params->channel_sku;
        //首先判断有无渠道也就是平台，没有就直接返回错误
        if (empty($lock['channel'])) {
            $return = array('status' => 'n', 'code' => 4001, 'msg' => '渠道缺失', 'curl_data' => '', 'data' => '', 'url' => '', 'post_msg' => '');
            goto return_back;
        }
        $lock_arr[] = $lock;
        $url = HOST_URL_API . '/guds_stock/muti_lock.json';
        if ($lock['channel'] == 'N000831400') {

            $Standing = M('center_stock', 'tb_wms_');
            $total_inventory = $Standing->where("channel = 'N000831400' AND SKU_ID = " . $lock['skuid'])->getField('total_inventory');

            $Drguds_opt = M('drguds_opt', 'tb_ms_');
            $where_opt['tb_ms_drguds_opt.SKU_ID'] = $lock['channelSkuId'];
            $drguds_opts = $Drguds_opt->where($where_opt)
                ->join('left join tb_ms_guds_store on tb_ms_guds_store.ID = tb_ms_drguds_opt.GUDS_ID')
                ->getField('tb_ms_drguds_opt.GUDS_ID,tb_ms_drguds_opt.GUDS_OPT_ID,tb_ms_drguds_opt.SKU_ID,tb_ms_drguds_opt.THRD_SKU_ID,tb_ms_guds_store.THRD_GUDS_ID');
            $drguds_opt = array_values($drguds_opts)[0];
            $get_data['get_msg'] = $msg = [
                "platCode" => 'N000831400',
                "processId" => create_guid(),
                "data" => [
                    "stocks" => [
                        [
                            "gudsId" => $drguds_opt['GUDS_ID'],
                            "thrdGudsId" => $drguds_opt['THRD_GUDS_ID'],
                            "skuId" => $drguds_opt['SKU_ID'],
                            "thrdSkuId" => $drguds_opt['THRD_SKU_ID'],
                            "stockCount" => $lock['number'],
                            "totalStockCount" => $total_inventory,
                            "status" => 0
                        ]
                    ]
                ]
            ];

            $get_data['get_url'] = $url_asyn = GSHOPPER . '/product/allotProductStock.json';
            $get_data['get_data'] = $get_datas = curl_get_json($url_asyn, json_encode($msg));
            $get_data['get_asyn'] = $get_asyn = json_decode($get_datas, 1);
            $get_data['time'] = date("Y-m-d H:i:s");
            trace($get_data, '$get_data');
            if ($get_asyn['code'] != 2000) {
                $return = array('status' => 'n', 'code' => $get_asyn['code'], 'msg' => $get_asyn['data'], 'curl_data' => $get_asyn['data'], 'data' => $get_asyn, 'url' => $url, 'post_msg' => $msg);
                goto return_back;
            }
        }
        $get_start = json_decode(curl_get_json($url, json_encode($lock_arr)), 1);

        if ($get_start['code'] == 2000) {
            $return = array('status' => 'y', 'msg' => '锁定成功', 'data' => $params, 'code' => $get_start['code'], 'msg' => $get_start['msg'], 'post_data' => $lock_arr);
        } else {
            $return = array('status' => 'n', 'code' => $get_start['code'], 'msg' => $get_start['msg'], 'curl_data' => $get_start['data'], 'data' => $lock_arr, 'url' => $url);
        }


        return_back:
        //        add log
        $log['return'] = serialize($return);
        $log_arr[] = $log;
        $this->add_lock_log($log_arr);

        echo json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 删除锁
     */
    public function del_lock()
    {
        $post = file_get_contents('php://input', 'r');
        $post = json_decode($post);
        $params = $post->params;

//        join array
        $where['SKU_ID'] = $log['sku'] = $lock['skuid'] = $params->sku;
        $where['GUSD_ID'] = $lock['gudsid'] = substr($lock['skuid'], 0, -2);
        $where['channel'] = $log['channel'] = $lock['channel'] = $params->channel;
        $where['CHANNEL_SKU_ID'] = $log['channelSkuId'] = $lock['channelSkuId'] = $params->channel_sku;
        $init_num = $params->init_num;
//       有占用禁止删除 ，can update total_inventory to sale
        $where['sale'] = $log['sale'] = $params->total_inventory;


        //        search
        $Standing = M('center_stock', 'tb_wms_');
        $sale = $Standing->where($where)->getField('sale');
        if ($sale < $init_num) {
            $return = array('status' => 'n', 'code' => '', 'msg' => '库存不够', 'sale' => $sale);
            echo json_encode($return, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $log['unlock_sum'] = $lock['number'] = '-' . $init_num;

        $lock_arr[] = $lock;
        $url = HOST_URL_API . '/guds_stock/muti_lock.json';

        if ($lock['channel'] == 'N000831400') {

            $Standing = M('center_stock', 'tb_wms_');
            $total_inventory = $Standing->where("channel = 'N000831400' AND SKU_ID = " . $lock['skuid'])->getField('total_inventory');

            $Drguds_opt = M('drguds_opt', 'tb_ms_');
            $where_opt['tb_ms_drguds_opt.SKU_ID'] = $lock['channelSkuId'];
            $drguds_opts = $Drguds_opt->where($where_opt)
                ->join('left join tb_ms_guds_store on tb_ms_guds_store.ID = tb_ms_drguds_opt.GUDS_ID')
                ->getField('tb_ms_drguds_opt.GUDS_ID,tb_ms_drguds_opt.GUDS_OPT_ID,tb_ms_drguds_opt.SKU_ID,tb_ms_drguds_opt.THRD_SKU_ID,tb_ms_guds_store.THRD_GUDS_ID');
            $drguds_opt = array_values($drguds_opts)[0];
            $get_data['get_msg'] = $msg = [
                "platCode" => 'N000831400',
                "processId" => create_guid(),
                "data" => [
                    "stocks" => [
                        [
                            "gudsId" => $drguds_opt['GUDS_ID'],
                            "thrdGudsId" => $drguds_opt['THRD_GUDS_ID'],
                            "skuId" => $drguds_opt['SKU_ID'],
                            "thrdSkuId" => $drguds_opt['THRD_SKU_ID'],
//                        "stockCount" => $total_inventory + $lock['number'],
                            "stockCount" => $lock['number'],
                            "totalStockCount" => $total_inventory,
                            "status" => 0  // should upd 1 del stock type
                        ]
                    ]
                ]
            ];

            $get_data['get_url'] = $url_asyn = GSHOPPER . '/product/allotProductStock.json';
            $get_data['get_data'] = $get_datas = curl_get_json($url_asyn, json_encode($msg));
            $get_data['get_asyn'] = $get_asyn = json_decode($get_datas, 1);
            $get_data['time'] = date("Y-m-d H:i:s");
            trace($get_data, '$get_data');
            if ($get_asyn['code'] != 2000) {
                $return = array('status' => 'n', 'code' => $get_asyn['code'], 'msg' => $get_asyn['data'], 'curl_data' => $get_asyn['data'], 'data' => $lock_arr, 'url' => $url, 'post_msg' => $msg);
                goto return_back_to;
            }
        }

        $get_start = json_decode(curl_get_json($url, json_encode($lock_arr)), 1);
        if ($get_start['code'] == 2000) {
            $return = array('status' => 'y', 'msg' => '解锁成功', 'data' => $params, 'code' => $get_start['code'], 'msg' => $get_start['msg'], 'curl_data' => $get_start['data']);
        } else {
            $return = array('status' => 'n', 'code' => $get_start['code'], 'msg' => $get_start['msg'], 'curl_data' => $get_start['data'], 'data' => $lock_arr, 'url' => $url);
        }
        return_back_to:
        $log['return'] = serialize($return);
        $log_arr[] = $log;
        $this->add_lock_log($log_arr, 'del');
        echo json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     * lock log add
     */
    private function add_lock_log($log_arr, $type = '')
    {
        foreach ($log_arr as $key => $val) {
            $add['GSKU'] = $val['sku'];
            $add['lock_sum'] = $val['total_inventory'];
            $add['unlock_sum'] = $val['unlock_sum'];
            $add['channel'] = $val['channel'];
            $add['return'] = $val['return'];
            $add['user_id'] = $_SESSION['userId'];
            $add['operate_time'] = date("Y-m-d H:i:s");
            $adds[] = $add;
        }
        trace($adds, '$adds');
        $Lock_log = M('lock_log', 'tb_wms_');
        $log_start = $Lock_log->addAll($adds);
        return $log_start;
    }

    /**
     * 效验锁数目
     */
    public function check_lock_num()
    {
        $post = file_get_contents('php://input', 'r');
        $post = json_decode($post);
        $params = $post->params;

        $sku = $params->sku;
        $channel = $params->channel;
        $channel_sku = $params->channel_sku;
        $where['SKU_ID'] = $sku;
        if ($channel != 1) {
            $where['channel'] = $channel;
        }
        if ($channel_sku != '') {
            $where['CHANNEL_SKU_ID'] = $channel_sku;
        }
        $Standing = M('center_stock', 'tb_wms_');
        echo json_decode($Standing->where($where)->getField('sale'), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 现存量查询
     */
    public function existing()
    {
        $params = $this->_param();
        $Stand = M('center_stock', 'tb_wms_');
        $where_stand['total_inventory'] = array('neq', 0);

        $get_data = $this->_param();
        $check_data = ['SKU', 'GUDS_CNS_NM', 'DELIVERY_WAREHOUSE'];
        $show = null;

        if ('down' != I('post.down')) {
            if (!empty(I("SKU"))) {
                $where_stand['tb_wms_center_stock.SKU_ID'] = array('like', "%" . I("SKU") . "%");
                // $where ['tb_wms_center_stock.SKU_ID'] = ['like', '%' . I("SKU") . '%'];
                // $where ['tmg.GUDS_CODE'] = ['like', '%' . I("SKU") . '%'];
                //$where ['tmg.GUDS_OPT_UPC_ID'] = ['like', '%' . I("SKU") . '%'];
                // $where['_logic'] = 'or';
                // $where_stand['_complex'] = $where;
            }
            if (!empty(I("GUDS_CNS_NM"))) {
                $where_stand['tmg.GUDS_NM'] = array('like', "%" . I("GUDS_CNS_NM") . "%");
            }
            if (!empty(I("DELIVERY_WAREHOUSE"))) {
                $where_stand['tmg.DELIVERY_WAREHOUSE'] = array('eq', I("DELIVERY_WAREHOUSE"));
            }
            if (1 != I("channel")) {
                $where_stand['tb_wms_center_stock.channel'] = array('eq', 'N000830100');
            }
        }
        if (!empty($params['sku_none'])) {
            $nwhere_stand = $where_stand;
            $nwhere_stand ['total_inventory'] = ['eq', 0];
            unset($where_stand['total_inventory']);
        }
        if (empty(I("DELIVERY_WAREHOUSE")) && empty(I("GUDS_CNS_NM"))) {
            $top_data = $Stand->cache(true, 300)->where($where_stand)
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = tb_wms_center_stock.SKU_ID')
                ->field('tb_wms_power.weight,tb_wms_center_stock.total_inventory')
                ->select();
            $ntop_data = $Stand->cache(true, 300)->where($nwhere_stand)
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = tb_wms_center_stock.SKU_ID')
                ->field('tb_wms_power.weight,tb_wms_center_stock.total_inventory')
                ->select();
        } else {
            $top_data = $Stand->cache(true, 30)->where($where_stand)
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = tb_wms_center_stock.SKU_ID')
                ->join('left join (select * from tb_ms_guds group by tb_ms_guds.DELIVERY_WAREHOUSE,tb_ms_guds.MAIN_GUDS_ID) tmg  on tmg.MAIN_GUDS_ID = tb_wms_center_stock.GUDS_ID')
                //->join('left join tb_ms_guds_opt on tmg.MAIN_GUDS_ID = tb_ms_guds_opt.GUDS_ID')
                ->field('tb_wms_power.weight,tb_wms_center_stock.total_inventory')
                ->select();

            $ntop_data = $Stand->cache(true, 30)->where($nwhere_stand)
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = tb_wms_center_stock.SKU_ID')
                ->join('left join (select * from tb_ms_guds group by tb_ms_guds.DELIVERY_WAREHOUSE,tb_ms_guds.MAIN_GUDS_ID) tmg  on tmg.MAIN_GUDS_ID = tb_wms_center_stock.GUDS_ID')
                ->field('tb_wms_power.weight,tb_wms_center_stock.total_inventory')
                ->select();
        }

        $count = count($top_data);
        $ncount = count($ntop_data);
        $top_nums = 0;
        $top_sums = 0;
        foreach ($top_data as $v) {
            $top_nums += $v['total_inventory'];
            $top_sums += $v['total_inventory'] * $v['weight'];
        }

        if ('down' != I('post.down')) {
            import('ORG.Util.Page');
            $page_num = I('page_num') > 0 ? I('page_num') : 20;
            $Page = new Page($count, $page_num);
            $Page->page_num = $page_num;
            $show = $Page->show();
            $model_s = M();
            $sql_s = $model_s->table('tb_wms_center_stock')->where($where_stand)
                ->field('tb_wms_center_stock.*,tmg.DELIVERY_WAREHOUSE as warehouse_id')
                ->join('left join  (select * from tb_ms_guds group by tb_ms_guds.DELIVERY_WAREHOUSE,tb_ms_guds.MAIN_GUDS_ID) tmg  on tmg.MAIN_GUDS_ID = tb_wms_center_stock.GUDS_ID')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select(false);
            $stream_arr = $model_s->table($sql_s . ' s')
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = s.SKU_ID')
                ->order('s.SKU_ID,s.channel desc')
                ->field('s.*,tb_wms_power.weight')
                ->select();
        } else {
            $stream_arr = $Stand->where($where_stand)
                ->join('left join tb_wms_power on tb_wms_power.SKU_ID = tb_wms_center_stock.SKU_ID')
                ->join('left join (select * from tb_ms_guds group by tb_ms_guds.DELIVERY_WAREHOUSE,tb_ms_guds.MAIN_GUDS_ID) tmg on tmg.MAIN_GUDS_ID = tb_wms_center_stock.GUDS_ID')
                ->order('tb_wms_center_stock.SKU_ID,tb_wms_center_stock.channel desc')
                ->field('tb_wms_center_stock.*,tb_wms_power.weight,tmg.DELIVERY_WAREHOUSE as warehouse_id')
                ->select();
        }
        if ($stream_arr) {
            foreach ($stream_arr as $key => &$val) {
                $sku = $val['SKU_ID'];
                if (empty($val['warehouse_id'])) {
                } else {
                    $model = D('Opt');
                    $GUDS_ID = $val['SKU_ID'];
                    // tb_wms_stand 表中的sku_id等于tb_ms_guds_opt表中的GUDS_OPT_ID
                    $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->select();
                    $guds['Opt'] = $guds;
                    // 查询商品的属性，在tb_ms_opt表中
                    foreach ($guds['Opt'] as $key => $opt) {
                        $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
                        foreach ($opt_val as $v) {
                            $val_str = '';
                            $o = explode(':', $v);
                            $model = M('ms_opt', 'tb_');
                            $opt_val_str = $model->cache(true, 300)->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                            if (empty($opt_val_str)) {
                                $val_str = L('标配');
                            } elseif ($opt_val_str['OPT_ID'] == '8000') {
                                $val_str = L('标配');
                            } elseif ($opt_val_str['OPT_ID'] != '8000') {
                                $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                            }
                            $guds['opt_val'][$key]['val'] .= $val_str;
                        }
                    }
                    $val['guds'] = $guds;
                    unset($guds);
                    $new_stream_arr[] = $val;
                }
            }
        }
        if ('down' == I('post.down')) {
            $this->down_existing($new_stream_arr);
        } else {
            $_param = $this->_param();
            $_param = empty($_param) ? 0 : $_param;
            $this->assign('param', json_encode($_param, JSON_UNESCAPED_UNICODE));
            $this->assign('go_url', GO_URL);
            $this->assign('stream_arr', json_encode($new_stream_arr, JSON_UNESCAPED_UNICODE));
            $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
            $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
            $this->assign('all_channel', json_encode($this->get_all_channel(), JSON_UNESCAPED_UNICODE));
            $this->assign('pages', $show);
            $this->assign('count', $count);
            $this->assign('ncount', $ncount);
            $this->assign('top_nums', $top_nums);
            $this->assign('top_sums', number_format($top_sums, 2));
            $this->display();
        }
    }

    public function existing_extend()
    {
        $this->display("existing_extend_new");
    }

    /**
     * 现存量列表数据获取
     */
    public function standingData()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);
        $model = new StandingExistingModel();
        $response = $model->getData($params);

        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $response;
        $data ['query'] = $params;
        $data ['totalCount'] = $model->count;
        $data ['amountSpu'] = number_format($model->amountSpu, 0);
        $data ['amountSku'] = number_format($model->count, 0);
        $data ['amountMoney'] = number_format($model->amountMoney, 2);
        $data ['amountUsdMoney'] = number_format($model->amountUsdMoney, 2);
        $data ['amountMoneyNoTax'] = number_format($model->amountMoneyNoTax, 2);
        $data ['amountUsdMoneyNoTax'] = number_format($model->amountUsdMoneyNoTax, 2);
        $data ['amountNumber'] = number_format($model->amountNumber, 0);

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 批次数据查询
     */
    public function batchData()
    {
        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new StandingExistingModel();
        $response = $model->getBatchData($params);

        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $response;
        $data ['query'] = $params;
        $data ['totalCount'] = $model->count;

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    public function order_redirect()
    {
        $params = ZUtils::filterBlank($this->getParams());
        list($url, $args) = (new StandingExistingModel())->getOrderUrl($params);
        if (!$url || !$args) die('Error');
        $this->redirect($url, $args);
    }

    /**
     * 服务费用列表
     */
    public function serviceCostList()
    {
        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new StandingExistingModel();
        $params['list_type'] = 'service';
        $response = $this->formatOutput(2000, 'success', $model->getLogServiceCostList($params));
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 物流费用列表
     */
    public function logCostList()
    {
        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new StandingExistingModel();
        $params['list_type'] = 'log';
        $response = $this->formatOutput(2000, 'success', $model->getLogServiceCostList($params));
        $this->ajaxReturn($response, 'json');
    }

    public function warehouseCostList()
    {
        $params = ZUtils::filterBlank($this->getParams() ['data']['query']);
        $model = new StandingExistingModel();
        $response = $this->formatOutput(2000, 'success', $model->getWarehouseCostList($params));
        $this->ajaxReturn($response, 'json');
    }

    public function existingList()
    {
        $request = DataModel::getDataNoBlankToArr();
        $this->validateExistingList();
        $model = new StandingExistingModel();
        $response = $this->formatOutput(2000, 'success', $model->getExistingList($request));
        $this->ajaxReturn($response, 'json');
    }

    private function validateExistingList($request)
    {

    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function exportCsv($data, $title, $filename)
    {
        if (!$filename) {
            $filename = date('Ymd') . '.csv'; //设置文件名
        }
        header('Content-Type: text/csv');
        header("Content-type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename={$filename}");
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        fputcsv($out, $title);
        foreach ($data as $row) {
            $line = [
                (string)$row['skuId'] . "\t",
                (string)$row['cat_level1_name'] . "\t",
                (string)$row['cat_level2_name'] . "\t",
                (string)$row['cat_level3_name'] . "\t",
                (string)$row['cat_level4_name'] . "\t",
                (string)$row['upcId'] . "\t",
                (string)$row['gudsNm'] . "\t",
                (string)$row['attr'] . "\t",
                (string)$row['warehouse'] . "\t",
                (string)$row['batchCode'] . "\t",
                (string)$row['ourCompany'] . "\t",
                (string)$row['saleTeam'] . "\t",
                (string)$row['smallSaleTeam'] . "\t",
                (string)$row['purNum'] . "\t",
                (string)$row['purTeam'] . "\t",
                (string)$row['purStorageDate'] . "\t",
                (string)$row['addTime'] . "\t",
                (string)$row['isDrug'] . "\t",
                (string)$row['existedDays'] . "\t",
                (string)$row['currentExistedDays'] . "\t",
                (string)$row['deadLineDate'] . "\t",
                (int)$row['amountTotalNum'],
                (int)$row['amountSaleNum'],
                (int)$row['amountOccupiedNum'],
                (int)$row['amountLockingNum'],
                (float)$row['unitPrice'],
                (float)$row['unitPriceUsd'],
                (float)$row['unitPriceNoTax'],
                (float)$row['unitPriceUsdNoTax'],
                (float)$row['poLogCost'],
                (float)$row['logServiceCost'],
                (float)$row['carryCost'],
                (float)$row['warehouseCost'],
                (string)$row['pur_currency'] . "\t",
                (float)$row['unit_price_origin'],
                (float)$row['unit_price_no_tax_origin'],
                (float)$row['po_cost_origin'],
                (string)$row['productType'] . "\t",
                (string)$row['is_oem_brand'] . "\t"
            ];
            fputcsv($out, $line);
        }

        fclose($out);
    }


    public function checkExport(){
        $post_data = DataModel::getData();
        $model = new StandingExistingModel();
        $response = array(
            'code'=> 200,
            'is_hint'=> false,
        );
        list($total,$query) = $model->checkBatchData($post_data);
        if ( $total > 5000){
            $dataService = new DataService();
            $excel_name = DataModel::userNamePinyin()."-现存量-".time().'.csv';
            $dataService->addOne($query,2,$excel_name,$total);
            $response['is_hint'] = true;
        }
        $this->ajaxReturn($response);
    }

    public function testTask()
    {
        $trigger_type = 0;
        $dataService = new DataService();
        $list = $dataService->sendRemindTaskEmail($trigger_type);

        $this->ajaxReturn($list);
    }


    /**
     * 导出现存量
     */
    public function export()
    {
        session_write_close();
        $model = new Model();
        $startTime = $this->microtime_float();
        $startMem = memory_get_usage();
        $params = func_get_args();
        if (empty($params)) {
            $params = ZUtils::filterBlank($this->getParams() ['post_data']);
        } else {
            $params = ZUtils::filterBlank($params[0]['post_data']);
        }
        $model = new StandingExistingModel();
        $response = $model->getBatchData(json_decode($params, true), true);
        $skus = array_column($response, 'skuId');
        $ret = SkuModel::getSkusInfo($skus, $appends = ['spu_name', 'attributes', 'product_sku']);
        $response = array_map(function ($r) use ($ret) {

             $r ['upcId'] = $ret ['product_sku'][$r ['skuId']]['upc_id'];
            if($ret ['product_sku'][$r ['skuId']]['upc_more']) {
                $upc_more_arr = explode(",", $ret ['product_sku'][$r ['skuId']]['upc_more']);
                array_unshift($upc_more_arr, $r ['upcId']);
                $r['upcId'] = implode(',', $upc_more_arr);
            }
           
            $r ['attr'] = $ret ['attributes'][$r ['skuId']];
            $r ['gudsNm'] = $ret ['spu_name'][$ret ['product_sku'][$r ['skuId']]['spu_id']];

            return $r;
        }, $response);
        $exportExcel = new ExportExcelModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('SKU编码'), 'field_name' => 'skuId'],
            $key++ => ['name' => L('一级类目'), 'field_name' => 'cat_level1_name'],
            $key++ => ['name' => L('二级类目'), 'field_name' => 'cat_level2_name'],
            $key++ => ['name' => L('三级类目'), 'field_name' => 'cat_level3_name'],
            $key++ => ['name' => L('四级类目'), 'field_name' => 'cat_level4_name'],
            $key++ => ['name' => L('条形码'), 'field_name' => 'upcId'],
            $key++ => ['name' => L('商品名称'), 'field_name' => 'gudsNm'],
            $key++ => ['name' => L('属性'), 'field_name' => 'attr'],
            $key++ => ['name' => L('仓库'), 'field_name' => 'warehouse'],
            $key++ => ['name' => L('批次号'), 'field_name' => 'batchCode'],
            $key++ => ['name' => L('所属公司'), 'field_name' => 'ourCompany'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'saleTeam'],
            $key++ => ['name' => L('销售小团队'), 'field_name' => 'smallSaleTeam'],
            $key++ => ['name' => L('采购单号'), 'field_name' => 'purNum'],
            $key++ => ['name' => L('采购团队'), 'field_name' => 'purTeam'],
            $key++ => ['name' => L('采购入库时间'), 'field_name' => 'purStorageDate'],
            $key++ => ['name' => L('入库时间'), 'field_name' => 'addTime'],
            $key++ => ['name' => L('是否滞销'), 'field_name' => 'isDrug'],
            $key++ => ['name' => L('总在库天数'), 'field_name' => 'existedDays'],
            $key++ => ['name' => L('当前仓库在库天数'), 'field_name' => 'currentExistedDays'],
            $key++ => ['name' => L('到期日'), 'field_name' => 'deadLineDate'],
            $key++ => ['name' => L('在库库存'), 'field_name' => 'amountTotalNum'],
            $key++ => ['name' => L('可售'), 'field_name' => 'amountSaleNum'],
            $key++ => ['name' => L('占用'), 'field_name' => 'amountOccupiedNum'],
            $key++ => ['name' => L('锁定'), 'field_name' => 'amountLockingNum'],
            $key++ => ['name' => L('采购单价（CNY，含增值税）'), 'field_name' => 'unitPrice'],
            $key++ => ['name' => L('采购单价（USD，含增值税）'), 'field_name' => 'unitPriceUsd'],
            $key++ => ['name' => L('采购单价（CNY，不含增值税）'), 'field_name' => 'unitPriceNoTax'],
            $key++ => ['name' => L('采购单价（USD，不含增值税）'), 'field_name' => 'unitPriceUsdNoTax'],
            $key++ => ['name' => L('是否ODM'), 'field_name' => 'is_oem_brand']
        ];
        $title = [
            L('SKU编码'),
            L('一级类目'),
            L('二级类目'),
            L('三级类目'),
            L('四级类目'),
            L('条形码'),
            L('商品名称'),
            L('属性'),
            L('仓库'),
            L('批次号'),
            L('所属公司'),
            L('销售团队'),
            L('销售小团队'),
            L('采购单号'),
            L('采购团队'),
            L('采购入库时间'),
            L('入库时间'),
            L('是否滞销'),
            L('总在库天数'),
            L('当前仓库在库天数'),
            L('到期日'),
            L('在库库存'),
            L('可售'),
            L('占用'),
            L('锁定'),
            L('采购单价（CNY，含增值税）'),
            L('采购单价（USD，含增值税）'),
            L('采购单价（CNY，不含增值税）'),
            L('采购单价（USD，不含增值税）'),
            L('PO内费用单价（CNY）'),
            L('服务费用单价（CNY）'),
            L('运输费用单价（CNY）'),
            L('仓储费用单价（CNY）'),
            L('采购币种'),
            L('采购单价（采购币种，含增值税）'),
            L('采购单价（采购币种，不含增值税）'),
            L('PO内费用单价（采购币种）'),
            L('商品类型'),
            L('是否ODM'),
        ];
        $file_name = 'existing_stock_' . date('Ymd') . '000000.csv';
        $this->exportCsv($response, $title, $file_name);
        return $file_name;
        exit;

        $exportExcel->data = $response;
        $exportExcel->export();
    }

    /**
     * 占用查询
     */
    public function occupySearch()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);

        $model = new StandingExistingModel();
        $response = $model->occupyData($params);

        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = (array)$response;
        $data ['query'] = $params;
        $data ['totalCount'] = $model->count;
        $data ['amountMoeny'] = number_format($model->amountMoney, 4, '.', ',');
        $data ['amountNumber'] = number_format($model->amountNumber, 0, '.', ',');

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    /**
     * 锁定查询
     */
    public function lockingSearch()
    {
        $params = ZUtils::filterBlank($this->getParams()['data']['query']);

        $model = new StandingExistingModel();
        $response = $model->lockingData($params);

        $data ['pageIndex'] = $model->pageIndex;
        $data ['pageSize'] = $model->pageSize;
        $data ['totalPage'] = ceil($model->count / $model->pageSize);
        $data ['pageData'] = $response;
        $data ['query'] = $params;
        $data ['totalCount'] = $model->count;
        $data ['amountMoeny'] = number_format($model->amountMoney, 4, '.', ',');
        $data ['amountNumber'] = number_format($model->amountNumber, 0, '.', ',');

        $response = $this->formatOutput(2000, 'success', $data);
        $this->ajaxReturn($response, 'json');
    }

    public function testYield()
    {
        $startMemory = memory_get_usage();
        $sql = 'select * from tb_wms_stream';
        $pdo = new \PDO('mysql:host=mysql.stage.com;dbname=b5c_stage', 'b5c', 'b5c');
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $rows = $pdo->query($sql);
        //$filename = date('Ymd') . '.csv'; //设置文件名
        //header('Content-Type: text/csv');
        //header("Content-Disposition: attachment;filename={$filename}");
        //$out = fopen('php://output', 'w');
        //fputcsv($out, ['ORDER_ID', 'PLAT_CD', 'PLAT_NAME', 'SHOP_ID', 'ORDER_UPDATE_TIME']);

        $exportExcel = new ExportExcelModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('订单号'), 'field_name' => 'ORDER_ID'],
            $key++ => ['name' => L('平台CODE'), 'field_name' => 'PLAT_CD'],
            $key++ => ['name' => L('平台名称'), 'field_name' => 'PLAT_NAME'],
            $key++ => ['name' => L('店铺ID'), 'field_name' => 'SHOP_ID'],
            $key++ => ['name' => L('订单更新时间'), 'field_name' => 'ORDER_UPDATE_TIME']
        ];


        $tmp = [];
        foreach ($rows as $row) {
            $r ['ORDER_ID'] = $row ['bill_id'];
            $r ['PLAT_CD'] = $row ['GSKU'];
            $r ['PLAT_NAME'] = $row ['send_num'];
            $r ['SHOP_ID'] = $row ['deadline_date_for_use'];
            $r ['ORDER_UPDATE_TIME'] = $row ['add_time'];
            //$line = [$row['ORDER_ID'], $row['PLAT_CD'], $row['PLAT_NAME'], $row['SHOP_ID'], $row ['ORDER_UPDATE_TIME']];
            //var_dump($line);exit;
            //fputcsv($out, $line);
            $tmp [] = $r;
        }
        $exportExcel->data = $tmp;
        $exportExcel->export();

        //fclose($out);
        $memory = round((memory_get_usage() - $startMemory) / 1024 / 1024, 3) . 'M' . PHP_EOL;
        file_put_contents('/tmp/test.txt', $memory, FILE_APPEND);
        echo $memory;
    }

    /**
     * order查询
     */
    public function search_up()
    {
        $Order = M('order', 'tb_op_');
        $where['tb_op_order.B5C_ORDER_NO'] = I("post.order_id");
        $ope_field = 'tb_op_order.B5C_ORDER_NO,tb_wms_operation_history.*';
        $order = $Order->field($ope_field)
            ->where($where)
            ->group('tb_wms_operation_history.order_id')
            ->having('count(tb_wms_operation_history.id)=1')
            ->join('left join tb_wms_operation_history on tb_op_order.B5C_ORDER_NO = tb_wms_operation_history.order_id')
            ->select();
        if ($order) {
            $info = '查询正常';
            $status = 'y';
        } else {
            $Orders = M('ord', 'tb_ms_');
            $wheres['tb_ms_ord.ORD_ID'] = I("post.order_id");
            $ope_field = 'tb_ms_ord.ORD_ID,tb_wms_operation_history.*';
            $order = $Orders->field($ope_field)
                ->where($wheres)
                ->group('tb_wms_operation_history.order_id')
                ->having('count(tb_wms_operation_history.id)=1')
                ->join('left join tb_wms_operation_history on tb_ms_ord.ORD_ID = tb_wms_operation_history.order_id')
                ->select();
            if ($order) {
                $info = '查询正常';
                $status = 'y';
            } else {
                $info = '查询无结果';
                $status = 'n';

            }
        }
        $return_arr = array('info' => $info, "status" => $status, 'data' => $order);
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 客商档案详情页
     */
    public function supplier()
    {
        $Supplier = M('supplier', 'tb_wms_');
        if (IS_POST) {
            $type = I("post.type");
            $post_data = $this->_param();
            if (!empty($post_data['add_data'])) {
                $where_name['suppli_name'] = $post_data['add_data']['suppli_name'];
                $supplier_name = $Supplier->where($where_name)->order('id asc')->select();
            }
            switch ($type) {
                case 'add':
                    if ($supplier_name[0]['id'] > 0 && $supplier_name[0]['suppli_name'] == $post_data['add_data']['suppli_name']) {
                        $return_arr = array('info' => '供应商名称重复', "status" => "n");
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $return_arr = $this->supplier_add($post_data['add_data']);
                    break;
                case 'upd':


                    if ($supplier_name[0]['id'] > 0 && $supplier_name[0]['suppli_name'] == $post_data['add_data']['suppli_name'] && $supplier_name[0]['id'] != $post_data['add_data']['id']) {
                        $return_arr = array('info' => '供应商名称重复', "status" => "n");
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $return_arr = $this->supplier_upd($post_data['add_data']);
                    break;
                case 'del':
                    $id = I("post.id");
                    $return_arr = $this->supplier_del($id);
                    break;
                case 'see':
                    if ('warehouse' == $post_data['search']) {

                        $Res = M('cmn_cd', 'tb_ms_');
                        $where['CD_VAL'] = $post_data['search_val'];
                        $house_key = $Res->where($where)->Field('CD,CD_VAL,ETc')->find();
                        $where[$post_data['search']] = $house_key['CD'];

                    } elseif ('suppli_name' == $post_data['search']) {
                        $where['suppli_name|abbreviation|en_name|en_ab'] = array('LIKE', "%" . $post_data['search_val'] . "%");
                    } else {
                        $where[$post_data['search']] = array('LIKE', "%" . $post_data['search_val'] . "%");
                    }
                    $this->assign('search_val', $post_data['search_val']);
                    $this->assign('search', $post_data['search']);
                    break;
            }
            if ('see' != $type) {
                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        if (empty($post_data['search_val'])) {
            $where = '';
        }
        $supplier = $Supplier->where($where)->order('id asc')->select();
        if (empty($post_data['search'])) {
            $this->assign('search', 'suppli_name');
        }
        $this->assign('house_list', json_encode($this->get_show_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('house_all_list', json_encode($this->get_all_warehouse(), JSON_UNESCAPED_UNICODE));
        $this->assign('producer', json_encode($this->get_producer(), JSON_UNESCAPED_UNICODE));
        $this->assign('supplier', json_encode($supplier, JSON_UNESCAPED_UNICODE));
        $this->display();

    }


    private function supplier_add($get_data)
    {
        $Supplier = M('supplier', 'tb_wms_');
        foreach ($get_data as $key => $val) {
            $data[$key] = $val;
        }
        $data_pust['id'] = $add = $Supplier->data($data)->add();
        if ($add) {
            $return_arr = array('info' => '新增成功', "status" => "y", "data" => $data_pust);
        } else {
            $return_arr = array('info' => '新增失败', "status" => "n", "data" => $data);
        }
        return $return_arr;
    }

    private function supplier_del($id)
    {
        $Supplier = M('supplier', 'tb_wms_');
        $where_del['id'] = $id;
        $del = $Supplier->where($where_del)->delete();
        if ($del) {
            $return_arr = array('info' => '删除成功', "status" => "y");
        } else {
            $return_arr = array('info' => '删除失败', "status" => "n", "data" => $del);
        }
        return $return_arr;
    }

    private function supplier_upd($get_data)
    {
        $Supplier = M('supplier', 'tb_wms_');
//        $old = $Supplier->where('id = ' . $get_data['id'])->find();
        $upd = $Supplier->where('id = ' . $get_data['id'])->data($get_data)->save();
        if ($upd || $upd == 0) {
            $return_arr = array('info' => '修改成功', "status" => "y");
        } else {
            $return_arr = array('info' => '修改失败', "status" => "n", "data" => $get_data);
        }
        return $return_arr;
    }

    public function Curl_post($url, $data)
    {

        $ch = curl_init();
        $header = array(
            "Accept: application/json",
            "Content-Type: application/json"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 180);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $PostData = $data;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        $temp = curl_exec($ch);
        return $temp;
        curl_close($ch);
    }

    /**
     * 发货出库 qoo10
     * true
     */
    public function deliver()
    {
        //
        if (1 != 1) {   //?????
            $data_start = null;
            if ($data_start) {
                $return_arr = array('info' => '创建成功', "status" => "y");
            } else {
                $return_arr = array('info' => '无可用库存', "status" => "n");
            }
            $return_arr = array('info' => '占用解锁异常', "status" => "n");
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ordId = I('post.ordId');
        if (empty($ordId)) {
            redirect(U('Public/error'), 2, '无订单号');
            return false;
            exit;
        }
        //  批次
        $ordId = $this->order_thr_to_erp($ordId);
        $Bill = M('bill', 'tb_wms_');    //库存单据表
        $check['link_bill_id'] = $ordId;
        if ($Bill->where($check)->count() != 0) {
            $return_arr = array('info' => '订单已存在，设置发货状态', 'code' => 200, "status" => "y");
            trace($return_arr, '$return_arr');
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $return_arr = $this->go_batch($ordId);
        trace($return_arr, '$return_arr');
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
        exit;

//        check orderid exist,property ? >.<
        $Bill = M('bill', 'tb_wms_');    //库存单据表
        $check['other_code'] = $ordId;
        $b_count = $Bill->where($check)->count();
        if ($Bill->where($check)->count() != 0) {
            $return_arr = array('info' => '单据已处理', "status" => "n", "code" => 'x01');
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $orderWhere['tb_op_order.ORDER_ID'] = $ordId;
        $order = M('op_order', 'tb_');
        $orderField = 'tb_op_order.ORDER_ID,tb_op_order.B5C_ORDER_NO, tb_op_order.BWC_ORDER_STATUS, tb_op_order.PLAT_CD,
                        tb_op_order.SHOP_ID, tb_op_order.PLAT_NAME, tb_op_order.USER_ID, tb_op_order.USER_NAME, tb_op_order.USER_EMAIL, tb_op_order.PAY_METHOD,
                        tb_op_order.PAY_TRANSACTION_ID, tb_op_order.PAY_CURRENCY, tb_op_order.PAY_SETTLE_PRICE, tb_op_order.PAY_VOUCHER_AMOUNT,
                        tb_op_order.PAY_SHIPING_PRICE, tb_op_order.PAY_PRICE, tb_op_order.PAY_TOTAL_PRICE, tb_op_order.ADDRESS_USER_NAME, tb_op_order.ADDRESS_USER_PHONE,
                        tb_op_order.ADDRESS_USER_COUNTRY, tb_op_order.ADDRESS_USER_ADDRESS1, tb_op_order.ADDRESS_USER_POST_CODE, tb_op_order.SHIPPING_MSG,
                        tb_op_order.SHIPPING_TYPE,tb_op_order.SHIPPING_DELIVERY_COMPANY,tb_op_order.SHIPPING_TRACKING_CODE,tb_op_order.PAY_SHIPING_PRICE';
        $detail = $order->field($orderField)->where($orderWhere)->find();
        trace($detail, '$detail');

        $detail['ORD_STAT_CD_NAME'] = L($detail['BWC_ORDER_STATUS']);

        //订单商品list
        $gud = M('op_order_guds', 'tb_');
        $gudField = 'tb_op_order_guds.SKU_ID, tb_op_order_guds.ORDER_ITEM_ID, tb_op_order_guds.ITEM_NAME, tb_op_order_guds.SKU_MESSAGE,
                    tb_op_order_guds.ITEM_PRICE, tb_op_order_guds.ITEM_COUNT,tb_ms_guds_opt.GUDS_OPT_ID,
                    tb_op_order_guds.B5C_SKU_ID,tb_ms_guds.DELIVERY_WAREHOUSE';
        $gudWhere['tb_op_order_guds.ORDER_ID'] = $ordId;
        $gud_list = $gud->field($gudField)
            ->join('left join tb_ms_guds_opt on tb_op_order_guds.SKU_ID=tb_ms_guds_opt.GUDS_OPT_ID')
            ->join('left join tb_ms_guds on tb_op_order_guds.B5C_ITEM_ID=tb_ms_guds.GUDS_ID')
            ->where($gudWhere)->select();

        foreach ($gud_list as $k => $v) {
            $detail['gudAmount'] += $v['RMB_PRICE'] * $v['ORD_GUDS_QTY'];
        }
        trace($detail, '$detail');
        trace($gud_list, '$gud_list');
        $array['detail'] = $detail;
        $array['gudList'] = $gud_list;
        if (empty($gud_list) || empty($detail)) {
            if (empty($gud_list)) $msg = '商品信息获取异常';
            if (empty($detail)) $msg = '金额异常';
            $return_arr = array('info' => $msg, "status" => "n", 'data' => serialize($detail) . '_' . serialize($gud_list));
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (IS_POST) {
            $bill_id = I("post.bill_id");

            $data['other_code'] = $ordId;
            if (empty($detail['B5C_ORDER_NO'])) {
                $return_arr = array('info' => 'B5C订单号缺失', "status" => "n");
                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit();
            }
            $data['link_bill_id'] = $detail['B5C_ORDER_NO'];
            $data['channel'] = $detail['PLAT_NAME'];

            $data['warehouse_id'] = empty($array['gudList'][0]['DELIVERY_WAREHOUSE']) ? 'N000680100' : $array['gudList'][0]['DELIVERY_WAREHOUSE']; // 国内仓
            $data['company_id'] = 'N000980400'; // 载鸿
            $data['user_id'] = I("post.userId"); //
            $data['bill_type'] = 'N000950100';
            $data['bill_date'] = date('Y-m-d');
            $data['batch'] = null;
            $data['bill_state'] = 1;
//          outgo filter,check out stock

            $data['zd_user'] = boolval(session('m_loginname')) ? session('m_loginname') : 'admin';
            $data['zd_date'] = date('Y-m-d H:i:s');

            $data['bill_id'] = $this->get_bill_id($data['bill_type']);

            $b_id = $Bill->data($data)->add();
            if ($b_id) {
// add order
                $all_list = array();
                $Stream = M('stream', 'tb_wms_');
                $user_id = session('user_id');
                foreach ($array['gudList'] as $key => $val) {
                    if (empty($val['B5C_SKU_ID'])) {
                        $where_del['id'] = $b_id;
                        $Bill->where($where_del)->delete();
                        unset($where_del);
                        $return_arr = array('info' => 'SKU缺失', "status" => "n");
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    } else {
                        $skuId = $unique['GSKU'] = $array_l['GSKU'] = $val['B5C_SKU_ID'];
                        /* if(empty($val['B5C_SKU_ID'])){
                             $return_arr = array('info' => 'SKU缺失', "status" => "n");
                             echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                             exit();
                         }*/
                        $array_l['line_number'] = $key;
                        $array_l['bill_id'] = $b_id;
                        $array_l['should_num'] = $val['ITEM_COUNT'];
                        $array_l['send_num'] = $val['ITEM_COUNT'];
                        $array_l['unit_price'] = $val['ITEM_PRICE'];
                        $array_l['no_unit_price'] = $val['ITEM_PRICE'];
                        $array_l['taxes'] = 0;
                        if (empty($detail['PAY_CURRENCY'])) {
                            $return_arr = array('info' => '币种缺失', "status" => "n");
                            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                            exit();
                        }
                        if (10 == strlen($detail['PAY_CURRENCY'])) {
                            $array_l['currency_id'] = $detail['PAY_CURRENCY'];
                        } else {
                            $Currency = M('cmn_cd', 'tb_ms_');
                            $where['CD_VAL'] = $detail['PAY_CURRENCY'];
                            $res = $Currency->where($where)->select();
                            $array_l['currency_id'] = $res[0]['CD'];
                        }
                        $arr_unique[] = $unique['GSKU'];
                        $all_list[] = $array_l;

                        $gudsId = substr($skuId, 0, -2);
//                        $ordId = $detail['B5C_ORDER_NO'];
                        $number = $val['ITEM_COUNT'];
                        if ($val['ITEM_COUNT'] > 0) {
                            $data_get['num'] = $number;
                            $data_get['skuId'] = trim($skuId);
                            $data_get['gudsId'] = $gudsId;
                            $data_get['orderId'] = $detail['B5C_ORDER_NO'];
                            $data_get['type'] = 0;
                            $data_get['operatorId'] = $user_id;
                            $data_arr[] = $data_get;
                            /*$url = HOST_URL_API . '/guds_stock/export.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&number=' . $number . '&ordId=' . trim($detail['B5C_ORDER_NO']);
                            $get_start = json_decode(curl_request($url), 1);
                            if ($get_start['code'] != 2000) {
                                $where_del['id'] = $b_id;
                                $Bill->where($where_del)->delete();
                                if ($get_start['code'] == 40056031) {
                                    $return_arr = array('info' => '总库存不足:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                                } else {
                                    $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => $get_start . $url);
                                }
                                $this->back = 0;
                                echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                                exit();
                            } else {
                                $back['gudsId'] = $gudsId;
                                $back['skuId'] = $skuId;
                                $back['number'] = $number;
                                $back_arr[] = $back;
                            }*/
                        }
                    }
                }

                $data_curl['data']['export'] = $data_arr;
                $url = HOST_URL_API . '/batch/export.json';
                if (HOST_URL_API == 'HOST_URL_API') $url = 'http://b5caiapi.stage.com/batch/export.json';
                $get_start = json_decode(curl_get_json($url, json_encode($data_curl, JSON_UNESCAPED_UNICODE)), 1);
                trace($get_start, '$get_start');
                trace($url, '$url');
                if ($get_start['code'] != 2000) {
                    $where_del['id'] = $b_id;
                    $Bill->where($where_del)->delete();
                    if ($get_start['code'] == 40056031) {
                        $return_arr = array('info' => '总库存不足:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                    } else {
                        $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => $get_start . $url);
                    }
                    $this->back = 0;
                    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                    exit();
                }

                if (count($arr_unique) != count(array_unique($arr_unique))) {
                    $where_del['id'] = $b_id;
                    $Bill->where($where_del)->delete();
                    unset($where_del);
                    $return_arr = array('info' => '单订单SKU编码重复', "status" => "n");
                    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                $data_start = $Stream->addAll($all_list);
                if ($data_start) {
                    $return_arr = array('info' => '创建成功', "status" => "y");
                } else {
                    $where_del['id'] = $b_id;
                    $Bill->where($where_del)->delete();
                    unset($where_del);
                    $return_arr = array('info' => '创建商品失败', "status" => "n", "data" => $array['gudList']);
                }
            } else {
                $return_arr = array('info' => '根订单创建失败', "status" => "n");
            }
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $return_arr = array('info' => '参数获取失败', "status" => "n");
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }

    /*code:
    * GudsStore_NOT_ENOUGH(40056028,"商品库存不足"),
    * No_Change(40056029,"数据无变化"),
    * Guds_ON_OVERSALE(40056030,"商品超卖中"),
    * Guds_ON_OVEROccupy(40056031,"占用总数不够出库"),
    * Operation_Done(40056032,"该操作已经执行，无需重复"),
    * @return bool*/

    /**
     *发货出库回滚
     */
    public function deliver_back()
    {
        $ordId = I('post.ordId');
        $delthis = I('post.delthis');
        if (empty($ordId)) {
            redirect(U('Public/error'), 2, '无订单号');
            return false;
            exit;
        }
        $Bill = M('bill', 'tb_wms_');
        $check['other_code'] = $ordId;
        $b_id = $Bill->where($check)->field('id,other_code,link_bill_id')->select();

        if ($b_id[0]['id']) {
            $Stream = M('stream', 'tb_wms_');
            $s_where['bill_id'] = $b_id[0]['id'];
            try {
                $GSKU_count = $Stream->where($s_where)->field('id,GSKU,send_num')->select();
                foreach ($GSKU_count as $key) {
                    $gudsId = substr($key['GSKU'], 0, -2);
                    $number = $key['send_num'];
                    $url = HOST_URL_API . '/guds_stock/export.json?gudsId=' . $gudsId . '&skuId=' . $key['GSKU'] . '&number=-' . $number . '&ordId=' . $b_id[0]['other_code'];

                    $get_start = json_decode(curl_request($url), 1);
                    if ($get_start['code'] != 2000) {
                        $where_del['id'] = $b_id;
                        $Bill->where($where_del)->delete();
                        $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                        exit();
                    } else {
                        $back['gudsId'] = $gudsId;
                        $back['skuId'] = $key['GSKU'];
                        $back['number'] = $number;
                        $back_arr[] = $back;
                    }
                }
                if (1 == $delthis) {
                    $s_count = $Stream->where($s_where)->delete();
                    $b_count = $Bill->where($check)->delete();
                    if ($b_count) {
                        $return_arr = array('info' => '删除成功', "status" => "n");
                    }

                } else {
                    $Bill->is_show = 0;
                    $b_count = $Bill->where($check)->save();
                    $return_arr = array('info' => '回滚成功', "status" => "y");
                }

            } catch (Exception $e) {
                $return_arr = array('info' => '回滚异常', "status" => "n");
            }

        } else {
            $return_arr = array('info' => '无效订单号', "status" => "n");
        }


        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    }


    /**
     *保税仓出库 费舍尔
     */
    public function deliver_warehouse()    //发货后要经过保税仓出库
    {
        $b5c_id = I('post.b5c_id');
        $Bill = M('bill', 'tb_wms_');    //库存单据表
        $check['link_bill_id'] = $b5c_id;
        if ($Bill->where($check)->count() != 0) {
            $return_arr = array('info' => '订单已存在，设置发货状态', 'code' => 200, "status" => "y");
            trace($return_arr, '$return_arr_w');
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }
//  批次
        $return_arr = $this->go_batch($b5c_id);
        trace($return_arr, '$return_arr');
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
        exit;

        if (!$this->checkOccupy($b5c_id)) {    //查询是否有操作记录
            $return_arr = array('info' => '数据无占用', "code" => '5001', "status" => "n", 'data' => '');
            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Ord = M('ord', 'tb_ms_');
        $where['ORD_ID'] = $b5c_id;
        $ord = $Ord->where($where)->find();  //查找到这条订单

        $bill['link_bill_id'] = $b5c_id;   //订单号
        $bill['channel'] = $this->get_channel($ord['PLAT_FORM']);   //通过数据字典查询到订单平台

        $bill['user_id'] = I("post.userId"); //用户id
        $bill['bill_type'] = 'N000950100';   //设置订单号前缀
        $bill['bill_id'] = $this->get_bill_id($bill['bill_type']);  //拼接处b5c订单号
        $bill['warehouse_id'] = empty($ord['DELIVERY_WAREHOUSE']) ? 'N000680100' : $ord['DELIVERY_WAREHOUSE']; // 国内仓


        $bill['company_id'] = 'N000980400'; // 载鸿
        $bill['bill_date'] = date('Y-m-d');
        $bill['batch'] = null;
        $bill['bill_state'] = 1;

        $bill['zd_user'] = boolval(session('m_loginname')) ? session('m_loginname') : 'admin';
        $bill['zd_date'] = date('Y-m-d H:i:s');

//        $Bill = M('bill', 'tb_wms_');
//        $b_id = $Bill->data($bill)->add();
        $model = new Model();
        $model->startTrans();
        $b_id = $model->table('tb_wms_bill')->add($bill);

        if ($b_id) {
            try {
                $Ord_guds_opt = M('ord_guds_opt', 'tb_ms_');
                $where['ORD_ID'] = $b5c_id;
                $ord_guds_opt = $Ord_guds_opt->where($where)->select();

                if (empty($ord_guds_opt)) {
                    $model->rollback();
                    $return_arr = array('info' => '订单异常,ord_guds_opt检索不到', "code" => 400, "status" => "n", 'data' => '');
                    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                    exit();
                } else {

                    foreach ($ord_guds_opt as $key => $val) {
                        $stream['GSKU'] = $val['GUDS_OPT_ID'];
                        $stream['no_unit_price'] = $stream['unit_price'] = $val['RMB_PRICE'];
                        $stream['send_num'] = $stream['should_num'] = intval($val['ORD_GUDS_QTY']);
                        $stream['taxes'] = 0;
                        $stream['currency_id'] = 'N000590300';//RMB
                        $stream['bill_id'] = $b_id;
                        $stream['line_number'] = $key;
                        $stream['batch'] = $val['wrapped_skuid']; //
                        $stream_all[] = $stream;
                    }
                    $stream_data = $model->table('tb_wms_stream')->addAll($stream_all);
                    if ($stream_data) {
                        /*   foreach ($stream_all as $key) {
                               $gudsId = substr($key['GSKU'], 0, -2);
                               $number = $key['send_num'];
                               if ($ord['PLAT_FORM'] == 'N000831400') {
                                   $url = HOST_URL_API . '/guds_stock/export.json?gudsId=' . $gudsId . '&skuId=' . $key['GSKU'] . '&number=' . $number . '&ordId=' . $b5c_id . '&channel=' . $ord['PLAT_FORM'] . '&channelSkuId=' . $key['batch'];
                                   //echo $url;
                               } else {
                                   $url = HOST_URL_API . '/guds_stock/export.json?gudsId=' . $gudsId . '&skuId=' . $key['GSKU'] . '&number=' . $number . '&ordId=' . $b5c_id;
                               }
                               trace($url, '$url');
                               $get_start = json_decode(curl_request($url), 1);
                               if ($get_start['code'] != 2000) {
                                   $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                               } else {
                                   $back['gudsId'] = $gudsId;
                                   $back['skuId'] = $key['GSKU'];
                                   $back['number'] = $number;
                                   $back_arr[] = $back;
                               }
                           }*/
                        $user_id = session('user_id');

                        foreach ($stream_all as $v) {
                            $gudsId = substr($v['GSKU'], 0, -2);
                            $data_get['num'] = $v['send_num'];
                            $data_get['skuId'] = $v['GSKU'];
                            $data_get['gudsId'] = $gudsId;
                            $data_get['orderId'] = $b5c_id;
                            $data_get['type'] = 0;
                            $data_get['operatorId'] = $user_id;
                            $data_arr[] = $data_get;
                        }
                        $data_curl['data']['export'] = $data_arr;
                        $url = HOST_URL_API . '/batch/export.json';
                        if (HOST_URL_API == 'HOST_URL_API') $url = 'http://b5caiapi.stage.com/batch/export.json';
                        $get_start = json_decode(curl_get_json($url, json_encode($data_curl, JSON_UNESCAPED_UNICODE)), 1);
                        trace($url, '$url');
                        trace($get_start, '$get_start');
                        if ($get_start['code'] != 2000) {
                            if ($get_start['code'] == 40056031) {
                                $return_arr = array('info' => '总库存不足:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                            } else {
                                $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => $get_start . $url);
                            }
                            $this->back = 0;
                            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                            exit();
                        }
                        $user_id = session('user_id');

                        foreach ($stream_all as $v) {
                            $gudsId = substr($v['GSKU'], 0, -2);
                            $data_get['num'] = $v['send_num'];
                            $data_get['skuId'] = $v['GSKU'];
                            $data_get['gudsId'] = $gudsId;
                            $data_get['orderId'] = $b5c_id;
                            $data_get['type'] = 0;
                            $data_get['operatorId'] = $user_id;
                            $data_arr[] = $data_get;
                        }
                        $data_curl['data']['export'] = $data_arr;
                        $url = HOST_URL_API . '/batch/export.json';
                        if (HOST_URL_API == 'HOST_URL_API') $url = 'http://b5caiapi.stage.com/batch/export.json';
                        $get_start = json_decode(curl_get_json($url, json_encode($data_curl, JSON_UNESCAPED_UNICODE)), 1);
                        trace($url, '$url');
                        trace($get_start, '$get_start');
                        if ($get_start['code'] != 2000) {
                            if ($get_start['code'] == 40056031) {
                                $return_arr = array('info' => '总库存不足:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                            } else {
                                $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => $get_start . $url);
                            }
                            $this->back = 0;
                            echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                            exit();
                        }
                    }
                }

            } catch (Exception $e) {
                $error['error'] = $e;
                echo json_encode($error, JSON_UNESCAPED_UNICODE);
                exit();
            }

            if (empty($return_arr)) {
                $model->commit();
                $return_arr = array('info' => '创建成功', 'code' => '200', "status" => "y");
            } else {
                $model->rollback();
                $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
            }


        }

        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);

//        $array_l['bill_id'] = $b_id;


    }

    /**
     * 下载
     */
    public function download()
    {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Home/Stock/' . $name;
        Http::download($filename, $filename);
    }

    /**
     * 获取公司
     *
     * @return mixed
     */
    private function get_company()
    {
        $Company = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '公司档案';
        return $Company->where($where)->getField('CD,CD_VAL,ETc');
    }

    private function get_show_warehouse()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        $where['is_show'] = 1;
        //$this->location == 1 ? $where['location_switch'] = 1 : ''; //货位
        return $Warehouse->where($where)->getField('CD,company_id,warehouse');
    }

    public function get_all_warehouse()
    {
        $model = new Model();
        $ret = $model->table('tb_ms_cmn_cd')->where(['cd' => ['like', 'N00068%']])->getField('CD, CD, CD_VAL as warehouse');
        return $ret;
    }

    public static function get_int_warehouse_code()
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        return $Warehouse->where('LENGTH(\'CD\') < 10')->getField('CD,company_id,warehouse as CD_VAL');
    }


    private function get_warehouse()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = 'DELIVERY_WAREHOUSE';
        return $Res->where($where)->getField('CD,CD_VAL,ETc');
    }

    private function warehouse_cd($w)
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_VAL'] = $w;
        $val = $Res->where($where)->field('CD')->find();
        return $val['CD'];
    }

//
    private function get_business_list()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = 'DELIVERY_WAREHOUSE';
        return $Res->where($where)->getField('CD,CD_VAL,ETc');
    }

//  供应商
    private function get_supplier_list()
    {
        $Warehouse = M('supplier', 'tb_wms_');
        return $Warehouse->getField('id,suppli_name,en_name');
    }

    /**
     * 收发类别
     *
     * @return mixed
     */
    private function get_outgo($get_outgo = null)
    {
        $outgo = I('get.outgo');
        if ($get_outgo) {
            $outgo = $get_outgo;
        }
        switch ($outgo) {
            case 'storage':
                $where['CD_NM'] = '入库类型';
                break;
            case 'outgoing':
                $where['CD_NM'] = '出库类型';
                break;
            default:
                $where['CD_NM'] = array('in', '出库类型,入库类型');
        }
        $Res = M('cmn_cd', 'tb_ms_');
        return $Res->where($where)->getField('CD,CD_VAL,ETc');
    }

    private function get_outgoing()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '出库类型';
        return $Res->where($where)->getField('CD,CD_VAL');
    }

    private function get_out()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '入库类型';
        return $Res->where($where)->getField('CD,CD_VAL');
    }

    /**
     * 出入库规则
     */
    private function getwarehouse_rule($type = 'in_storage')
    {
        $rules = [
            'out_storage' => [
                2 => '默认规则(先进先出/效期敏感商品将以效期优先)',
                3 => '指定采购批次出库',
                5 => '虚拟出库'
            ],
            'in_storage' => [
                1 => '实际入库',
                0 => '虚拟入库(直接发给客户)',
            ]
        ];

        return $rules [$type];
    }

    //    人员
    private function get_use_extends()
    {
        $Role = M('role', 'bbm_');
        $Admin = M('admin', 'bbm_');
        return $Admin->getField('M_ID as id,M_NAME as nickname,M_ID code_id');
    }

    private function get_use()
    {
        $Role = M('role', 'bbm_');
        $Admin = M('admin', 'bbm_');
        return $Admin->where('ROLE_ID = ' . $Role->where('ROLE_ID = ' . ROLE_ID)->getField('ROLE_ID'))->getField('M_ID as id,M_NAME as nickname,M_ID code_id');
    }

    /**
     *货位获取
     */
    public function get_location($warehouse_id)
    {
        $Location = M('location', 'tb_wms_');
        $where['warehouse_id'] = $warehouse_id;
        return $Location->where($where)->getField('id,location_name,location_code');
    }


    /**
     *商品获取
     */
    public function get_goods($GSKU)
    {
        $Goods = M('goods', 'tb_wms_');
        $where['GSKU'] = $GSKU;
        return $Goods->where($where)->getField('id,goods_name,UP_SKU,bar_code,digit');
    }

//    商品
    /*    public function get_goods($GSKU)
        {
            $Goods = M('guds', 'tb_ms_');
            $GSKU_id = substr($GSKU,0,-2);
            $where['GUDS_ID'] = $GSKU_id ;
            return $Goods->where($where)->select();
        }*/


    /**
     * 币种获取
     */
    public function get_currency()
    {
        $Currency = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '기준환율종류코드';
        return $Currency->where($where)->getField('CD,CD_VAL,ETc');
    }

    /**
     * 计量单位
     */
    private function get_metering()
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = 'VALUATION_UNIT';
        return $Cmn_cd->where($where)->getField('CD,CD_VAL,ETc');
    }


    /**
     * 查询SPU
     */
    public function searchguds()
    {
        $GUDS_ID = I('post.GSKU');
        if (strlen($GUDS_ID) > 0) {
            $qr_code = $GUDS_ID;
            $Guds_opt = M('guds_opt', 'tb_ms_');
            if (!empty($qr_code)) {
                $where_qr['GUDS_OPT_UPC_ID'] = array('like', '%' . $GUDS_ID . '%');
                $res = $Guds_opt->where($where_qr)->field('GUDS_OPT_ID')->find();
                empty($res) ? '' : $GUDS_ID = $res['GUDS_OPT_ID'];
            }
        }
        $model = D('Opt');
        $guds = $model->relation(true)->where('GUDS_OPT_ID = ' . $GUDS_ID)->select();
        if (empty($guds)) {
            $this->ajaxReturn(0, $guds, 0);
            exit();
        }
        $guds['Opt'] = $guds;
        foreach ($guds['Opt'] as $key => $opt) {
            $opt_val = explode(';', $opt['GUDS_OPT_VAL_MPNG']);
            foreach ($opt_val as $v) {
                $val_str = '';
                $o = explode(':', $v);
                $model = M('ms_opt', 'tb_');
                $opt_val_str = $model->join('left join tb_ms_opt_val on tb_ms_opt_val.OPT_ID = tb_ms_opt.OPT_ID')->where('tb_ms_opt.OPT_ID = ' . $o[0] . ' and tb_ms_opt_val.OPT_VAL_ID = ' . $o[1])->field('tb_ms_opt.OPT_CNS_NM,tb_ms_opt_val.OPT_VAL_CNS_NM,tb_ms_opt.OPT_ID')->find();
                if (empty($opt_val_str)) {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] == '8000') {
                    $val_str = L('标配');
                } elseif ($opt_val_str['OPT_ID'] != '8000') {
                    $val_str = $opt_val_str['OPT_CNS_NM'] . ':' . $opt_val_str['OPT_VAL_CNS_NM'] . ' ';
                }
                $guds['opt_val'][$key]['val'] .= $val_str;
                $guds['opt_val'][$key]['price'] = sprintf("%.2f", $opt['GUDS_OPT_BELOW_SALE_PRC'] / $rate);
                $guds['opt_val'][$key]['GUDS_OPT_ID'] = $opt['GUDS_OPT_ID'];
                $guds['opt_val'][$key]['GUDS_ID'] = $opt['GUDS_ID'];
                $guds['opt_val'][$key]['SLLR_ID'] = $opt['SLLR_ID'];
            }
        }


        $this->ajaxReturn(0, $guds, 1);
    }

    /**
     *获取订单
     */
    public function get_bill_id($e, $get_date = null, $sale_team_nm)
    {
        $Bill = M('bill', 'tb_wms_');
        $date = date("Y-m-d");
        empty($get_date) ? '' : $date = $get_date;
        $where['bill_date'] = $date;

        $max_id = $Bill->where($where)->order('id')->limit(1)->count();
//CR+170122+0001
        $type = '';
        switch ($e) {
            case 'N000940100':
                $type = 'CGR';
                break;
            case 'N000940200':
                $type = 'THR';
                break;
            case 'N000941000':
                $type = 'THR';
                break;
            case 'N000940300':
                $type = 'QTR';
                break;
            case 'N000950100':
                $type = 'XSC';
                break;
            case 'N000950200':
                $type = 'BSC';
                break;
            case 'N000950300':
                $type = 'QTC';
                break;
            case 'N000940400':
                $type = 'PYR';
                break;
            case 'N000950400':
                $type = 'PKC';
                break;
            case 'N000950600':
                $type = 'DB';
                break;
            case 'N000950500':
                $type = 'DB';
                break;
        }
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

    private function get_default()
    {
        $user_id = $_SESSION['userId'];
        $Relation = M('relation', 'tb_wms_');
        $where['user_id'] = $user_id;
        return $Relation->where($where)->find();
    }

    /**
     *原产国
     */
    private function get_producer()
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '원산지코드';
        return $Cmn_cd->where($where)->getField('CD,CD_VAL,ETc');
    }

    private function upd_cd($cd_v, $cd_nm)
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_VAL'] = $cd_v;
        if (!empty($cd_nm)) {
            $where['CD_NM'] = $cd_nm;
        }
        $res = $Cmn_cd->where($where)->Field('CD')->find();
        return $res['CD'];
    }

    private function upd_warehouse($w)
    {
        $Warehouse = M('warehouse', 'tb_wms_');
        return $Warehouse->where('is_show = 1 AND warehouse = \'' . $w . '\'')->getField('CD');
    }

    private function get_all_channel()
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = SALE_CHANNEL;
        return $Cmn_cd->where($where)->getField('CD,CD_VAL,ETc');
    }

    private function get_channel($e)
    {
        $Cmn_cd = M('cmn_cd', 'tb_ms_');
        $where['CD'] = $e;
        $res = $Cmn_cd->where($where)->Field('CD_VAL')->find();
        return $res['CD_VAL'];
    }

    /*private function upd_use($cd_v)
    {
        $Res = M('user', 'tb_wms_');
        $where['nickname'] = $cd_v;
        $res = $Res->where($where)->Field('id')->find();
        return $res['id'];
    }*/

    private function upd_use($cd_v)
    {
//        $cd_v = 'yangsu';
        $Role = M('role', 'bbm_');;
        $Admin = M('admin', 'bbm_');
        $user_arr = $Admin->where('ROLE_ID = ' . $Role->where('ROLE_ID = ' . ROLE_ID)->getField('ROLE_ID'))->where('M_NAME = \'' . $cd_v . '\'')->limit(1)->getField('M_NAME as nickname,M_ID as id,M_ID code_id');
        return $user_arr[$cd_v]['code_id'];
    }

    private function check_excel($b, $s, $stage = '')
    {
        $code_zh = [
            'company_id' => '公司',
            'channel' => '渠道',
            'SALE_TEAM' => '销售团队',
            'warehouse_id' => '仓库',
            'bill_type' => '收发类别',
            'user_id' => '库管员',
            'bill_date' => '日期',
            //'GSKU' => '商品编码',
            'GUDS_OPT_ID' => '条形码',
            'should_num' => '数量',
            'unit_price' => '单价',
            'taxes' => '税率',
            'currency_id' => '币种',
            'sum' => '金额',
            'create_time' => '入库时间',
            'deadline_date_for_use' => '到期日',
        ];
        // 明日时间，时间戳
        $timeZone = new TimeZone(date('Y/m/d 00:00:00', time()));
        $timeZone->add('P1D');
        $tomorrow = $timeZone->transformationDate('Y-m-d');
        $tomorrow = strtotime($tomorrow);
        // 入库时间验证
        if ($stage == '') {
            foreach ($s as $k => $v) {
                // 检查时间不能超过当前实际日期
                foreach ($v as $i => $t) {
                    if ($t ['add_time']) {
                        $t_nt = strtotime($t ['add_time']);
                        if ($t_nt >= $tomorrow) $error [] = '第' . $t ['row'] . '行的<入库时间>不能超过当前实际日期 <br/>';
                    } else {
                        $error [] = '第' . $t['row'] . '行的<入库时间>不能为空';
                    }
                }
            }
        }
        if ($stage) {
            $saleTeamCodes = BaseModel::saleTeamCd();
        } else {
            $saleTeamCodes = BaseModel::saleTeamCdExtend();
        }
        foreach ($b as $key => $val) {
            foreach ($val as $k => $v) {
                if ($k == 'channel' or $k == 'deadline_date_for_use') {
                    continue;
                } elseif ($k == 'SALE_TEAM') {
                    if ($saleTeamCodes [$v]) continue;
                    else $error [] = '第' . $val['row'] . '行的<' . $code_zh[$k] . '> 不能为空，或者销售团队已禁止使用 <br/>';
                } else {
                    //empty($v) ? $error[] = '第' . $val['row'] . '行的<' . $code_zh[$k] . '>异常 <br/>' : '';
                }
            }
            empty($error) ? '' : $error_data[] = $error;
            unset($error);
        }

        return $error_data;
    }

    private function check_del_bill($b, $outgo_state)
    {
        $res['state'] = 0;
        $res['sku'] = '';
        if ('-' == $outgo_state) {

            foreach ($b as $key => $val) {
                foreach ($val as $k => $v) {
                    empty($all[$v['GSKU']]) ? $all[$v['GSKU']] = $v['send_num'] : $all[$v['GSKU']] += $v['send_num'];
                }
            }
            $Standing = M('center_stock', 'tb_wms_');
            $where['SKU_ID'] = array('in', array_keys($all));

            $standing_data = $Standing->where($where)->getField('SKU_ID,sale');

            foreach ($all as $key => $val) {
                if ($all[$key] - $standing_data[$key] > 0) {
                    $res['state'] = 1;
                    $res['sku'] .= '&nbsp&nbsp' . $key . '>数量不足,需要出库' . $all[$key] . '，实际余数为' . $standing_data[$key] . '<br>';
                }
            }

        } elseif ('delord' == $outgo_state) {
            $Bill = M('bill', 'tb_wms_');
            $bill_type = $Bill->where('id=' . $b)->getField('bill_type');

            if (!in_array($bill_type, array_keys($this->get_outgoing()))) {
                $Stream = M('stream', 'tb_wms_');
                $all_list = $Stream->where('bill_id=' . $b)->select();
                $sum = 0;
                foreach ($all_list as $key => $val) {
                    $all[$val['GSKU']] = $val['send_num'] + $sum;
                }
                $Standing = M('center_stock', 'tb_wms_');
                $where['SKU_ID'] = array('in', array_keys($all));
                $standing_data = $Standing->where($where)->getField('SKU_ID,total_inventory');
                trace($all, '$all');
                trace($standing_data, '$standing_data');
                $Guds = M('guds', 'tb_ms_');
                foreach ($all as $key => $val) {
                    $where_over['GUDS_ID'] = substr($key, 0, -2);
//                    check OVER_YN
                    $guds_over = $Guds->where($where_over)->getField('OVER_YN');
                    trace($guds_over, '$guds_over');
                    if ($all[$key] - $standing_data[$key] > 0) {
                        if ($guds_over == 'N') {
                            $res['state'] = 1;
                        }
                        $res['sku'] .= '&nbsp' . $key;

                    }
                }
            }
        } else {
            $Bill = M('bill', 'tb_wms_');
            $bill_type = $Bill->where('id=' . $b)->getField('bill_type');

            if (!in_array($bill_type, array_keys($this->get_outgoing()))) {
                $Stream = M('stream', 'tb_wms_');
                $all_list = $Stream->where('bill_id=' . $b)->select();
                $sum = 0;
                foreach ($all_list as $key => $val) {
                    $all[$val['GSKU']] = $val['send_num'] + $sum;
                }
                $Standing = M('center_stock', 'tb_wms_');
                $where['SKU_ID'] = array('in', array_keys($all));
                $standing_data = $Standing->where($where)->getField('SKU_ID,sale');
                foreach ($all as $key => $val) {
                    if ($all[$key] - $standing_data[$key] > 0) {
                        $res['state'] = 1;
                        $res['sku'] .= '&nbsp' . $key;
                    }
                }
            }
        }

        return $res;
    }

    /**
     *效验货位
     */
    public function check_postition($p, $w)
    {
        $Location_details = M('location_details', 'tb_wms_');
        $where['box_name'] = $p;
        $where['warehouse_id'] = $w;
        return $Location_details->where($where)->count();
    }

    /**
     * 更新数据
     */
    public function upd_data()
    {
        $Standing = M('center_stock', 'tb_wms_');
        $Operation_history = M('operation_history', 'tb_wms_');
        $standing = $Standing->field('SKU_ID,occupy,total_inventory')->select();

        foreach ($standing as $key => $val) {

            $where['sku_id'] = $val['SKU_ID'];
            $Operation_history_sql = $Operation_history->where($where)->group('tb_wms_operation_history.order_id')->having('count(tb_wms_operation_history.id)=1')->select(false);
            $model = new Model();
            $count = $model->table($Operation_history_sql . ' a')->where("a.ope_type = 'N001010100'")->count();

            $counts = 0;
            foreach ($count as $k => $v) {
                $counts += $v['change_num'];
            }

            $Standing->occupy = $counts;
            $Standing->sale = $val['total_inventory'] - $counts;
            $where_sku['SKU_ID'] = $val['SKU_ID'];
            $Standing->where($where_sku)->save();
            print_r($counts);
            echo '<br/>';
        }

    }

    /*'N000550100'               => '待确认',
    'N000550200'               => '确认中',
    'N000550300'               => '待付款',
    'N000550301'               => '支付中',
    'N000550302'               => '信息异常',
    'N000550400'               => '待发货',
    'N000550500'               => '待收货',
    'N000550600'               => '已收货',
    'N000550700'               => '已付尾款',
    'N000550800'               => '交易成功',
    'N000550900'               => '交易关闭',*/

    /**
     * 同步异常订单
     */
    public function syn_occupy()
    {
        $Operation_history = M('operation_history', 'tb_wms_');
        $Ord = M('ord', 'tb_ms_');
        $model = new Model();
        $close_subQuery = $model->table('tb_wms_operation_history')->where("tb_wms_operation_history.ope_type = 'N001010200'")->select(false);
        $all_close = $model->table($close_subQuery . ' c,tb_ms_ord')->where("tb_ms_ord.ORD_ID = c.order_id AND tb_ms_ord.ORD_STAT_CD != 'N000550900'")->field('c.order_id,c.sku_id,c.change_num')->select();
        $yfh_close = $model->table($close_subQuery . ' c,tb_ms_ord')->where("tb_ms_ord.ORD_ID = c.order_id AND tb_ms_ord.ORD_STAT_CD != 'N000550900' AND (tb_ms_ord.ORD_STAT_CD = 'N000550500' OR  tb_ms_ord.ORD_STAT_CD = 'N000550800')")->field('c.id,c.order_id,c.sku_id,c.change_num')->select();
        echo '<pre>';
        print_r($yfh_close);
        $wfh_close = $model->table($close_subQuery . ' c,tb_ms_ord')->where("tb_ms_ord.ORD_ID = c.order_id AND tb_ms_ord.ORD_STAT_CD NOT IN ('N000550900','N000550500','N000550800')")->field('c.id,c.order_id,c.sku_id,c.change_num')->select();
        print_r($wfh_close);
        $err = 0;

        echo 'yfh';

        foreach ($yfh_close as $v) {
            $deldata = $model->table('tb_wms_operation_history')->where("order_id = '" . $v['order_id'] . "' AND sku_id = '" . $v['sku_id'] . "' AND ope_type = 'N001010200'")->delete();
            $new_deldata = $model->table('tb_wms_operation_history')->where("order_id = '" . $v['order_id'] . "' AND sku_id = '" . $v['sku_id'] . "' AND ope_type = 'N001010100'")->delete();
            print_r($deldata);
            print_r($new_deldata);
            if ($deldata == 0 || $new_deldata == 0) {
                $err = 1;
                goto errors;
                break;
            } else {
                $s_deldata = $model->table('tb_wms_operation_history')->where("order_id = '" . $v['order_id'] . "'")->select();
                echo '$s_deldata';
                var_dump($s_deldata);
                //            补占用
                $skuId = $v['sku_id'];
                $gudsId = substr($v['sku_id'], 0, -2);
                $changeNm = (int)$v['change_num'];
                $ordId = $v['order_id'];
                $url = HOST_URL_API . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&number=' . abs($changeNm) . '&ordId=' . $ordId;
                print_r($url);
                $results = json_decode(curl_request($url), 1);
                print_r($results);
                if ($results['code'] != 2000) {
                    $err = 1;
                    goto errors;
                    break;
                }
//            走出库

                $result = json_decode($this->deliver_warehouse_this($v['order_id'], 1), 1);
                echo 'chu';
                print_r($result);
                if ($result['code'] != 2000) {
                    $err = 1;
                    goto errors;
                    break;
                }
                $log = A('Log');
                $log->index($v['order_id'], $v['sku_id'], $result['msg']);
            }

        }
        echo 'wfh';
        if ($err != 1) {
            foreach ($wfh_close as $v) {
                //            删除错误关闭记录
                $deldata = $model->table('tb_wms_operation_history')->where("order_id = '" . $v['order_id'] . "' AND sku_id = '" . $v['sku_id'] . "' AND ope_type = 'N001010200'")->delete();
                //            删除占用记录
                $new_deldata = $model->table('tb_wms_operation_history')->where("order_id = '" . $v['order_id'] . "' AND sku_id = '" . $v['sku_id'] . "' AND ope_type = 'N001010100'")->delete();
                print_r($deldata);
                print_r($new_deldata);
                if ($deldata == 0 || $new_deldata == 0) {
                    $err = 1;
                    goto errors;
                    break;
                } else {
//            补占用
                    $skuId = $v['sku_id'];
                    $gudsId = substr($v['sku_id'], 0, -2);
                    $changeNm = (int)$v['change_num'];
                    $ordId = $v['order_id'];
                    $url = HOST_URL_API . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . trim($skuId) . '&number=' . abs($changeNm) . '&ordId=' . $ordId;
                    print_r($url);
                    $result = json_decode(curl_request($url), 1);
                    print_r($result);
                    if ($result['code'] != 2000) {
                        $err = 1;
                        goto errors;
                        break;
                    }
                    $log = A('Log');
                    $log->index($v['GUDS_OPT_ID'], $skuId, $result['msg'] . $url);
                    $urls[] = $url;
                }
            }

        }
        errors:
        if ($err != 1) {
//            $model->commit();
        } else {
            echo 'roll1';
//            $model->rollback();
        }
        echo '</pre>';
    }

    /**
     *创建出入库订单
     */
    public function deliver_warehouse_this($b5c_id, $userId)
    {
        $b5c_id = $b5c_id;
        $Ord = M('ord', 'tb_ms_');
        $where['ORD_ID'] = $b5c_id;
        $ord = $Ord->where($where)->find();

        $bill['link_bill_id'] = $b5c_id;
        $bill['channel'] = $this->get_channel($ord['PLAT_FORM']);

        $bill['user_id'] = $userId; //
        $bill['bill_type'] = 'N000950100';
        $bill['bill_id'] = $this->get_bill_id($bill['bill_type']);
        $bill['warehouse_id'] = empty($ord['DELIVERY_WAREHOUSE']) ? 'N000680100' : $ord['DELIVERY_WAREHOUSE']; // 国内仓


        $bill['company_id'] = 'N000980400'; // 载鸿
        $bill['bill_date'] = date('Y-m-d');
        $bill['batch'] = null;
        $bill['bill_state'] = 1;

        $bill['zd_user'] = boolval(session('m_loginname')) ? session('m_loginname') : 'admin';
        $bill['zd_date'] = date('Y-m-d H:i:s');

//        $Bill = M('bill', 'tb_wms_');
//        $b_id = $Bill->data($bill)->add();
        $model = new Model();
        $model->startTrans();
        $b_id = $model->table('tb_wms_bill')->add($bill);

        if ($b_id) {

            $Ord_guds_opt = M('ord_guds_opt', 'tb_ms_');
            $where['ORD_ID'] = $b5c_id;
            $ord_guds_opt = $Ord_guds_opt->where($where)->select();

            if (empty($ord_guds_opt)) {
                $is_error = 1;
                $return_arr = array('info' => '订单异常', "code" => 400, "status" => "n", 'data' => '');
                goto echo_this;
            } else {

                foreach ($ord_guds_opt as $key => $val) {
                    $stream['GSKU'] = $val['GUDS_OPT_ID'];
                    $stream['no_unit_price'] = $stream['unit_price'] = $val['RMB_PRICE'];
                    $stream['send_num'] = $stream['should_num'] = intval($val['ORD_GUDS_QTY']);
                    $stream['taxes'] = 0;
                    $stream['currency_id'] = 'N000590300';//RMB
                    $stream['bill_id'] = $b_id;
                    $stream['line_number'] = $key;
                }


                $stream_all[] = $stream;

                $stream_data = $model->table('tb_wms_stream')->addAll($stream_all);

                if ($stream_data) {

                    foreach ($stream_all as $key) {
                        $gudsId = substr($key['GSKU'], 0, -2);
                        $number = $key['send_num'];
//                        不走占用直接扣减？
                        $url = HOST_URL_API . '/guds_stock/export.json?gudsId=' . $gudsId . '&skuId=' . $key['GSKU'] . '&number=' . $number . '&ordId=' . $b5c_id;

                        $get_start = json_decode(curl_request($url), 1);
                        if ($get_start['code'] != 2000) {
                            $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
                        } else {
                            $back['gudsId'] = $gudsId;
                            $back['skuId'] = $key['GSKU'];
                            $back['number'] = $number;
                            $back_arr[] = $back;
                        }
                    }

                }
            }

            echo_this:
            if ($is_error == 1) {
                $model->rollback();
                return json_encode($return_arr, JSON_UNESCAPED_UNICODE);
                exit;
            }
            if (empty($return_arr)) {
                $model->commit();
                $return_arr = array('info' => '创建成功', 'code' => '2000', "status" => "y");
            } else {
                $model->rollback();
                $return_arr = array('info' => '接口处理异常:' . $get_start['msg'], "code" => $get_start['code'], "status" => "n", 'data' => '');
            }


        }

        return json_encode($return_arr, JSON_UNESCAPED_UNICODE);

//        $array_l['bill_id'] = $b_id;


    }

    //check Occupy
    private function checkOccupy($o, $ope_type = null)
    {
        $Operation = M('operation_history', 'tb_wms_');    //出库操作记录
        $occupy = $Operation->where("ope_type = 'N001010100'  and order_id = '" . $o . "'")->count();
        if ($ope_type == 'onlyOccupy') {
            $release = 0;
        } else {
            $release = $Operation->where("ope_type != 'N001010100'  and order_id = '" . $o . "'")->count();
        }

        if ($occupy > 0 && $release == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新权值
     */
    private function update_weighting($sku = null, $all = null)
    {
        if ($all == 'goallben') {
            $Stand = M('center_stock', 'tb_wms_');
            $where_stand['total_inventory'] = array('neq', 0);
            $where_stand['channel'] = array('eq', 'N000830100');
            $stream_arr = $Stand->where($where_stand)
                ->field('SKU_ID')
                ->select();
            foreach ($stream_arr as $key => $val) {
                $this->update_weighting($val['SKU_ID']);
            }
            $update_state = true;
        } elseif (!empty($sku)) {
            $Model = new Model();
            $where['GSKU'] = $sku;
            $stream_sql = $Model->table('tb_wms_stream')->where($where)->select(false);
            $stage_type = implode("','", array_keys($this->get_out()));
            $stage_arr = $Model->table($stream_sql . ' s,tb_wms_bill')
                ->where('s.bill_id = tb_wms_bill.id AND tb_wms_bill.is_show = 1  AND tb_wms_bill.bill_type in (\'' . $stage_type . '\')')
                ->field('s.GSKU,s.send_num,s.unit_price,s.no_unit_price')
                ->select();
            $out_type = implode("','", array_keys($this->get_outgoing()));
            $out_arr = $Model->table($stream_sql . ' s,tb_wms_bill')
                ->where('s.bill_id = tb_wms_bill.id AND tb_wms_bill.is_show = 1 AND tb_wms_bill.bill_type in (\'' . $out_type . '\')')
                ->field('s.GSKU,s.send_num,s.unit_price,s.no_unit_price')
                ->select();
            $stage_all_num = $stage_all_sum = 0;
            foreach ($stage_arr as $key => $val) {
                $stage_all_sum += $val['send_num'] * $val['unit_price'];
                $stage_all_num += $val['send_num'];
            }
            $out_all_num = $out_all_sum = 0;
            foreach ($out_arr as $key => $val) {
                $out_all_sum += $val['send_num'] * $val['unit_price'];
                $out_all_num += $val['send_num'];
            }
            $data['weight'] = round($stage_all_sum / $stage_all_num, 4);
            $data['weighting_out'] = round($out_all_sum / $out_all_num, 4);

            $update_sate = $Model->table('tb_wms_power')->where('SKU_ID = ' . $sku)->save($data);
            if (!$update_sate) {
                $data['SKU_ID'] = $sku;
                $update_sate = $Model->table('tb_wms_power')->add($data);
            }
            $update_state = true;
        } else {
            $update_state = false;
        }
        return $update_state;
    }

    /**
     *获取所有仓库对应sku数
     */
    public function get_all_house_sku()
    {
        $model = new Model();
        $sql = '
            SELECT
                SUM(t4.all_total) as all_num, SUM(t4.total_inventory_all) as total_inventory_all, t4.warehouse_id as warehouse, COUNT(t4.SKU_ID) as all_sku
            FROM
                (
                    SELECT
                        SUM(t3.all_total) as all_total,
                        SUM(t3.total_inventory) as total_inventory_all,
                        t3.SKU_ID,
                        t3.warehouse_id
                    FROM
                        (
                            SELECT
                                (
                                    t1.total_inventory * t2.unit_price
                                ) AS all_total,
                                t1.SKU_ID,
                                t1.total_inventory,
                                t2.warehouse_id
                            FROM
                                tb_wms_batch t1,
                                tb_wms_stream t2
                            WHERE
                                t1.stream_id = t2.id
                            AND t1.channel = "N000830100"
                            AND t1.total_inventory > 0
                        ) t3
                    GROUP BY
                        t3.SKU_ID,
			            t3.warehouse_id
                ) t4, tb_wms_center_stock t5
                WHERE t4.SKU_ID = t5.SKU_ID
                GROUP BY t4.warehouse_id';
        $ret = $model->query($sql);
        foreach ($ret as $key => $value) {
            $data [$value ['warehouse']] = $value;
        }

        return $data;
    }

    /**
     *下载
     */
    public function down_existing($e)
    {
        $expTitle = "现存量查询";
        $expCellName = array(
            array('SKU_ID', 'SKU编码'),
            array('channel', '渠道'),
            array('GUDS_CNS_NM', '商品名称'),
            array('GUDS_OPT_UPC_ID', '条形码'),
            array('opt_val', '属性'),
            array('warehouse', '仓库'),
            array('total_inventory', '总库存数'),
            array('sale', '可售'),
            array('occupy', '占用'),
            array('locking', '锁定'),
            array('weight', '成本价'),
            array('weighting_sum', '库存成本'),
        );
        $all_channel = $this->get_all_channel();
        $house_all_list = $this->get_all_warehouse();
//        join exp excel
        foreach ($e as $key => $val) {
            $join_data['SKU_ID'] = $val['SKU_ID'];
            $join_data['channel'] = $all_channel[$val['channel']]['CD_VAL'];
            $join_data['GUDS_CNS_NM'] = $val['guds'][0]['Guds']['GUDS_CNS_NM'];
            $join_data['GUDS_OPT_UPC_ID'] = $val['guds'][0]['Guds']['GUDS_OPT_UPC_ID'];
            $join_data['opt_val'] = $val['guds']['opt_val'][0]['val'];
            $join_data['warehouse'] = $house_all_list[$val['warehouse_id']]['warehouse'];
            $join_data['total_inventory'] = $val['total_inventory'];
            $join_data['sale'] = $val['sale'];
            $join_data['occupy'] = $val['occupy'];
            $join_data['locking'] = $val['locking'];
            $join_data['weight'] = number_format($val['weight'], 4);
            $join_data['weighting_sum'] = number_format($val['weight'] * $val['total_inventory'], 2);
            $expTableData[] = $join_data;
        }
        $this->exportExcel($expTitle, $expCellName, $expTableData);
    }

    /**
     *excel处理
     */
    public function exportExcel($expTitle, $expCellName, $expTableData, $type = 0)
    {
        ini_set('memory_limit', '512M');
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $expTitle . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        // 组装为二维数组
        if (count($expTableData) == count($expTableData, 1)) {
            $expTableData = array($expTableData);
        }
        if ($type == 0) {
            $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
            $objPHPExcel->getActiveSheet()->setTitle($expTitle);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(10);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(10);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(15);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(40);        // Miscellaneous glyphs, UTF-8
            $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);        // Miscellaneous glyphs, UTF-8
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $fileName);
        // 设置标题
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }

        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        $column_index = 0;    // 控制行数据
        foreach ($this->procBigData($expTableData) as $k => $v) {
            $title_index = 0; // 控制标题对应相应的数据格
            foreach ($expCellName as $field_name => $title) {
                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$title_index] . ($column_index + 3), $v[$title[0]], PHPExcel_Cell_DataType::TYPE_STRING);
                $title_index++;
            }
            unset($v);
            $column_index++;
        }
        unset($expTableData);
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     *数据处理
     */
    public function procBigData($expTableData)
    {
        foreach ($expTableData as $key => $value) {
            yield $value;
        }
    }

    /**
     * 获取仓库对应SKU数
     */
    public function get_all_house_sku_old()
    {
        $Stream = M('stream', 'tb_wms_');
        $Bill = M('bill', 'tb_wms_');
        $where_in['tb_wms_bill.bill_type'] = array('in', array_keys($this->get_out()));
        $where_out['tb_wms_bill.bill_type'] = array('in', array_keys($this->get_outgoing()));
        $all_in = $Bill->where($where_in)->field('id')->select();
        $all_out = $Bill->where($where_out)->field('id')->select();
        $where_s_in['bill_id'] = array('in', array_column($all_in, 'id'));
        $data_in = $Stream->where($where_s_in)->field('GSKU,warehouse_id,sum(send_num) as all_sum')->group('GSKU,warehouse_id')->select();
        $where_s_out['bill_id'] = array('in', array_column($all_out, 'id'));
        $data_out = $Stream->where($where_s_out)->field('GSKU,warehouse_id,sum(send_num) as all_sum')->group('GSKU,warehouse_id')->select();
        foreach ($data_in as $key => $val) {
            $new_data_in[$val['warehouse_id'] . '-' . $val['GSKU']]['all_sum'] += empty($val['all_sum']) ? 0 : $val['all_sum'];
        }
        foreach ($data_out as $key => $val) {
            $new_data_out[$val['warehouse_id'] . '-' . $val['GSKU']]['all_sum'] -= empty($val['all_sum']) ? 0 : $val['all_sum'];
        }
        echo '<pre>';
//        print_r($new_data_in);
        print_r(array_merge_recursive($new_data_in, $new_data_out));
    }

    /**
     * 获取城市
     */
    public function getCity()
    {
        $p = I('provinces');
        $end = I('end');
        $province = $this->runCity($p);
        if (count($province) > 0) {
            $info = [
                'msg' => '查询成功',
                'keys' => array_column($province, 'ID')
            ];
            $this->ajaxReturn($this->packCountry($province, $end), $info, 1);
        } else {
            $info = [
                'msg' => '查询失败',
            ];
            $this->ajaxReturn($province, $info, 0);
        }


    }

    private function packCountry($c, $end = null)
    {
        foreach ($c as $v) {
            $c_n['value'] = $v['ID'];
            $c_n['label'] = $v['NAME'];
            if ('end' != $end) {
                $c_n['children'] = array(new stdClass());
            }
            $c_n_arr[] = $c_n;
        }
        return $c_n_arr;
    }


    // 获得市
    private function runCity($parent_id)
    {
        $model = M('_crm_site', 'tb_');
        $ret = $model->field('NAME, ID')->where('PARENT_ID = "' . $parent_id . '"')->select();
        return $ret;
    }

    /**
     * 测试
     */
    public function test()
    {
        print_r($_SESSION);
    }

    //check Occupy
    private function searchOccupy($o, $ope_type = null)
    {
        $Operation = M('operation_history', 'tb_wms_');
        $occupy = $Operation->where("ope_type = 'N001010100'")->select();


    }

    private function check_post($p, $c)
    {
        $state = 0;
        foreach ($c as $k => $v) {
            empty($p[$v]) ? '' : $state = 1;
        }
        return $state;
    }

    /**
     *权值获取
     */
    public static function get_power($sku)
    {
        $Power = M('power', 'tb_wms_');
        $where['SKU_ID'] = $sku;
        $res = $Power->cache(50)->where($where)->getField('weight');
        trace($res, '$res');
        if ($res) {
            return $res;
        } else {
            return 0;
        }
    }

    /**
     * 同步订单状态已更改，未发货
     */
    public function sync_order()
    {
        ini_set('max_execution_time', 1800);
        ini_set('request_terminate_timeout', 18000);
        $models = M('ms_ord', 'tb_');
        $date = empty(I('date')) ? '2017-06-01' : I('date');
        $date_end = empty(I('date_end')) ? '2017-06-19 23:59:59' : I('date_end');

        $where['tb_ms_ord.PAY_DTTM'] = array(array('gt', $date), array('lt', $date_end), 'and');
        $where['tb_ms_ord.PLAT_FORM'] = array(array('EXP', 'IS NULL'), array('exp', ' IN ("N000830100","N000830200","N000831300")'), 'or');
        $where['ORD_STAT_CD'] = array('eq', 'N000550500');

        $datas = $models->where($where)->field('ORD_ID')->select();
        $datas_key = array_column($datas, 'ORD_ID');
        $fun = empty(I('fun')) ? 'deliver_warehouse' : I('fun');
        $url = SMS2_URL . 'index.php?m=stock&a=' . $fun;

        foreach ($this->procBigData($datas_key) as $k => $v) {
            $post_data['b5c_id'] = $v;
            $states[$v] = curl_request($url, $post_data);
        }
        var_dump($states);
    }

    /**
     * 清理异常qoo10
     */
    public function clear_qoo10()
    {
        ini_set('max_execution_time', 1800);
        ini_set('request_terminate_timeout', 18000);
        $arrs = [];
        $Operation_history = M('operation_history', 'tb_wms_');
        $where['order_id'] = array('in', $arrs);
        $where['ope_type'] = array('eq', 'N001010100');
        $operation_history_arr = $Operation_history->where($where)->field('order_id,sku_id,change_num')->select();
        echo '<pre>';
        foreach ($this->procBigData($operation_history_arr) as $k => $v) {

            $skuId = $v['sku_id'];
            $gudsId = substr($skuId, 0, -2);
            $changeNm = (int)$v['change_num'];
            $outgo_state = '-';
            $ordId = $v['order_id'];
            $url = HOST_URL_API . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . $skuId . '&number=' . $outgo_state . $changeNm . '&ordId=' . $ordId;
            $urls[] = $url;
            $result[] = json_decode(curl_request($url), 1);
        }
        print_r($result);
        print_r($urls);

    }

    /**
     * 清理异常第三方
     */
    public function clear_old_thr()
    {
        ini_set('max_execution_time', 1800);
        ini_set('request_terminate_timeout', 18000);

        $Operation_history = M('operation_history', 'tb_wms_');
        $where['ope_type'] = array('eq', 'N001010301');
        $operation_history_arr = $Operation_history->where($where)->field(' order_id,sku_id,ope_type,change_num')->select();

        echo '<pre>';
        foreach ($this->procBigData($operation_history_arr) as $k => $v) {
            $where['order_id'] = $v['order_id'];
            $where['sku_id'] = $v['sku_id'];
            $save['ope_type'] = 'N001010100';
            $save['change_num'] = abs($v['sku_id']);
            $Operation_history->where($where)->save($save);

            $skuId = $v['sku_id'];
            $gudsId = substr($skuId, 0, -2);
            $changeNm = (int)$v['change_num'];
            $ordId = $v['order_id'];
            $url = HOST_URL_API . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . $skuId . '&number=' . $changeNm . '&ordId=' . $ordId;
            $urls[] = $url;
            $result[] = json_decode(curl_request($url), 1);
        }
        print_r($result);
        print_r($urls);
    }

    public function clean_occupy()
    {
        $arr = [['8000378401', '2', 'b5cb499051299380']];
        print_r($this->get_occupy_api($arr));
    }

    private function get_occupy_api($operation_history_arr)
    {
        $result = null;
        foreach ($this->procBigData($operation_history_arr) as $k => $v) {
            $skuId = $v[0];
            $gudsId = substr($skuId, 0, -2);
            $changeNm = (int)$v[1];
            $ordId = $v[2];
            $host_url_api = 'http://i.b5cai.com';
            $url = $host_url_api . '/guds_stock/update_occupy.json?gudsId=' . $gudsId . '&skuId=' . $skuId . '&number=-' . $changeNm . '&ordId=' . $ordId;
            $urls[] = $url;
            $result[] = json_decode(curl_request($url), 1);
        }
        return array($urls, $result);
    }

    /**
     * 传入ERP订单号，自动分类获取订单详情
     *
     * @param String $ord_id ERP订单号
     *
     * @return String $ORD_TYPE_CD
     *                       有两种情况
     *                       part1、自营订单tb_op_order
     *                       part2、非自营订单tb_ms_ord
     */
    public function get_order_type()
    {
        $ord_id = $_GET['ordId'];
        //part1、自营订单
        $where = null;
        $wehre ['B5C_ORDER_NO'] = ['eq', $ord_id];
        $m = M('op_order', 'tb_');
        $result = $m->where($where)->find();
        if ($result) {
            $_GET['ordId'] = $result ['ORDER_ID'];
            $this->orderdetail_self();
        }

        // part2、非自营订单
        $where = null;
        $where['tb_ms_ord.PLAT_FORM'] = [['EXP', 'IS NULL'], ['exp', ' IN ("N000830100","N000830200","N000831300")'], 'or'];
        $where['tb_ms_ord.ORD_ID'] = ['eq', $ord_id];
        $model = M('ms_ord', 'tb_');
        $result = $model->where($where)->find();
        if ($result) {
            //大宗
            if ($result ['ORD_TYPE_CD'] == 'N000620100') {
                $this->orderdetail_bulk();
            }
            //现货
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DELIVERY_WAREHOUSE'] == 'N000680100') {
                $this->orderdetail_xh();
            }
            //直邮
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DELIVERY_WAREHOUSE'] == 'N000680200') {
                $this->orderdetail_dm();
            }
            //保税
            if ($result ['ORD_TYPE_CD'] == 'N000620400' and $result ['DEVIVERY_WAREHOUSE'] == 'N000680300') {
                $this->orderdetail_bw();
            }
        }

        $this->orderdetail_qg();
    }

    private function order_erp_to_thr($ORD_ID)
    {
        $thr_order_id = $ORD_ID;
        $Model = M();
        $thr_order_id_t = $Model->table('tb_ms_ord')->where('ORD_ID = \'' . $ORD_ID . '\'')->getField('THIRD_ORDER_ID');
        if ($thr_order_id_t) {
            $thr_order_id = $thr_order_id_t;
        }
        return $thr_order_id;
    }

    /**
     * 根据SKU查询商品信息
     */
    public function search_gudsinfo_by_sku()
    {
        $params = $this->getParams();
        $GUDS_ID = $params ['GSKU'];
        $guds_s = M('guds_opt', 'tb_ms_');
        //$where_guds['GUDS_ID'] = substr($GUDS_ID, 0, -2);
        $where_guds['tb_ms_guds_opt.GUDS_OPT_ID'] = $GUDS_ID;
        $ret = $guds_s
            ->join('left join tb_ms_guds on SUBSTR(tb_ms_guds_opt.GUDS_OPT_ID, 1, 8) = tb_ms_guds.GUDS_ID')
            ->where($where_guds)
            ->field('tb_ms_guds.GUDS_CNS_NM, tb_ms_guds.GUDS_CODE, tb_ms_guds.VALUATION_UNIT, tb_ms_guds.DELIVERY_WAREHOUSE, tb_ms_guds_opt.GUDS_OPT_CODE')
            ->find();
        //echo $guds_s->getLastSql();exit;
        if ($ret) {
            $imgs = $this->getGudsImg([$GUDS_ID]);
            $stockAction = A('Home/Stock');
            $ret ['GUDS_OPT_VAL_MPNG'] = $stockAction->gudsOptsMerge($ret ['GUDS_OPT_VAL_MPNG']);
            $ret ['VALUATION_UNIT'] = BaseModel::getUnit()[$ret ['VALUATION_UNIT']]['CD_VAL'];
            $ret ['DELIVERY_WAREHOUSE'] = BaseModel::get_all_warehouse()[$ret ['DELIVERY_WAREHOUSE']]['warehouse'];
            $ret ['img'] = $imgs [$GUDS_ID];
            $info = L('success');
            $state = 1;
        } else {
            $info = L('未查询到数据');
            $state = 0;
        }
        $this->ajaxReturn($ret, $info, $state);
    }

    /**
     * 批次
     *
     * @param $ordId
     *
     * @return array
     */
    public function go_batch($ordId)
    {
        G('beginBatch');
        $today_date = date('Ymd');
        $goods = $this->join_batch_goods($ordId);
        trace(G('beginBatch', $ordId . '-joinBatchGoods', 6), $today_date . '-' . $ordId . '-joinBatchGoods-' . microtime(true));

        $MsOrd = M('ms_ord', 'tb_');
        $field = ['PLAT_FORM', 'DELIVERY_WAREHOUSE'];
        $where['ORD_ID'] = $ordId;
        $msord = $MsOrd->field($field)->where($where)->find();
        trace(G('beginBatch', $ordId . '-searchMsOrd', 6), $today_date . '-' . $ordId . '-searchMsOrd-' . microtime(true));
        $opOrd = M('op_order', 'tb_');
        $where['B5C_ORDER_NO'] = $ordId;
        // $warehouse_id = $msord['DELIVERY_WAREHOUSE'];
        $warehouse_id = $opOrd->where($where)->getField('WAREHOUSE');
        if (empty($warehouse_id)) {
            $return_arr = array('info' => '仓库为空', 'code' => '400000', "status" => "n");
            return $return_arr;
        }
        $channel = $msord['PLAT_FORM'];
        $type = 0;
        $third_order_id = $this->order_erp_to_thr($ordId);
        trace(G('beginBatch', $ordId . '-erpToThr', 6), $today_date . '-' . $ordId . '-erpToThr-' . microtime(true));
        $warehouse_status = B2bModel::out_online_warehouse($goods, $channel, $warehouse_id, $type, $ordId, $third_order_id);
        trace(G('beginBatch', $ordId . '-sendOnlineWare', 6), $today_date . '-' . $ordId . '-sendOnlineWare-' . microtime(true));
        if ($warehouse_status['code'] != 2000) {
            $msg = $warehouse_status['code'] . $warehouse_status['msg'] . json_encode($warehouse_status['info']);
            $return_arr = array('info' => $msg, 'code' => $warehouse_status['code'], "status" => "n");
        } else {
            $return_arr = array('info' => '创建发货成功', 'code' => 200, "status" => "y");
        }
        return $return_arr;
    }

    /**
     * 商品
     *
     * @param $ordId
     *
     * @return array
     */
    private function join_batch_goods($ordId)
    {
        $Guds = M('ms_ord_guds_opt', 'tb_');
        $gudField = 'GUDS_OPT_ID,ORD_GUDS_QTY,ORD_GUDS_QTY,RMB_PRICE';
        $gudWhere['ORD_ID'] = $ordId;
        $gud_list = $Guds->field($gudField)->where($gudWhere)->select();
        $date = date('Y-m-d H:i:s');
        foreach ($gud_list as $v) {
            $gud['GSKU'] = $v['GUDS_OPT_ID'];  // GUDS_OPT_ID
//            $info = B2bModel::get_goods_info($gud['GSKU']);
            $gud['taxes'] = 0;
            $gud['should_num'] = $v['ORD_GUDS_QTY']; // ORD_GUDS_QTY
            $gud['send_num'] = $v['ORD_GUDS_QTY'];
            $gud['price'] = $v['RMB_PRICE'];// 单价 RMB_PRICE
            $gud['currency_id'] = 'N000590300';// 币种
            $gud['currency_time'] = $date;
            $guds[] = $gud;
        }
        return $guds;
    }

    private function order_thr_to_erp($thr_order_id)
    {
        $ORD_ID = $thr_order_id;
        $Model = M();
        $ORD_ID_t = $Model->table('tb_ms_ord')->where('THIRD_ORDER_ID = \'' . $thr_order_id . '\'')->getField('ORD_ID');
        if ($ORD_ID_t) {
            $ORD_ID = $ORD_ID_t;
        }
        return $ORD_ID;
    }

    public function rule_forward()
    {
        $params = $this->getParams();
        $url = http_build_query($params);
        exit($url);
    }

    public function testApi()
    {
        $params [] = [
            'stream_id' => 1,
            'log_service_cost' => 5,
            'storage_log_cost' => 6,
            'carry_cost' => 2,
            'all_log_service_cost' => 5,
            'all_storage_log_cost' => 6,
            'all_carry_cost' => 2,
            'currency_id' => 'N000590100', // 美元币种
            'pur_storage_date' => '2018-10-17' // 转换日期
        ];

        $params [] = [
            'stream_id' => 2,
            'log_service_cost' => 9,
            'storage_log_cost' => 9,
            'carry_cost' => 7,
            'all_log_service_cost' => 9,
            'all_storage_log_cost' => 9,
            'all_carry_cost' => 7,
            'currency_id' => 'N000590100', // 美元币种
            'pur_storage_date' => '2018-10-12' // 转换日期
        ];
        $model = new TbWmsStreamCostLogModel();
        $model->addRecording($params);
    }


    /**
     * 导出当前数据
     * @return string
     */
    public function exportPresent()
    {
        session_write_close();
        set_time_limit(0);
        $params = $this->getParams();
        $model = new StandingExistingModel();
        $list = $model->getDataNew($params);
        foreach ($list as $key=>$value){
            $list[$key]['is_oem_brand'] = empty($value['is_oem_brand']) ? "非ODM品牌" : "ODM品牌";
            $list[$key]['amountMoney'] = round($value['amountMoney'],2);
            $list[$key]['amountMoneyNoTax'] = round($value['amountMoneyNoTax'],2);
            $list[$key]['amountUsdMoney'] = round($value['amountUsdMoney'],2);
            $list[$key]['amountUsdMoneyNoTax'] = round($value['amountUsdMoneyNoTax'],2);
        }
        $map  = [
            ['field_name' => 'skuId', 'name' => L('SKU编码')],
            ['field_name' => 'is_oem_brand', 'name' => L('品牌类型')],
            ['field_name' => 'upcId', 'name' => L('条形码')],
            ['field_name' => 'gudsName', 'name' => L('商品名称')],
            ['field_name' => 'optAttr', 'name' => L('属性')],
            ['field_name' => 'amountTotalNum', 'name' => L('在库库存')],
            ['field_name' => 'amountSaleNum', 'name' => L('可售库存')],
            ['field_name' => 'amountOccupiedNum', 'name' => L('占用库存')],
            ['field_name' => 'amountLockingNum', 'name' => L('锁定库存')],
            ['field_name' => 'amountMoney', 'name' => L('库存成本（CNY，含增值税）')],
            ['field_name' => 'amountMoneyNoTax', 'name' => L('库存成本（CNY，不含增值税）')],
            ['field_name' => 'amountUsdMoney', 'name' => L('库存成本（USD，含增值税）')],
            ['field_name' => 'amountUsdMoneyNoTax', 'name' => L('库存成本（USD，不含增值税）')],
        ];
        $model->exportCsv($list,$map);
        unset($list);
        unset($map);
    }
    public function put_warehouse_approval_new()
    {
        $this->display();
    }

    public function out_warehouse_approval_new()
    {
        $this->display();
    }

    public function out_or_in_warehouse_approval_view() {
        $this->display();
    }

}