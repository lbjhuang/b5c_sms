<?php

/**
 * User: tianrui
 * Date: 19/03/07
 * Time: 15:31
 */
@import("@.Model.BaseModel");

class CodeService extends Service
{
    /**
     * @var FinanceRepository
     */
    public $CodeRepository;

    public $code_table;

    /**
     * FinanceService constructor.
     */
    public function __construct()
    {
        $this->CodeRepository = new CodeRepository();
        $this->code_table  = M('ms_cmn_cd', 'tb_');
    }

    public function getListByField($request_data)
    {
        $where = $res = [];
        $where['CD'] = array('like', '%N00323%');
        if ($request_data['field']) {
            $where[$request_data['field']] = array('eq', $request_data['CD']);
        }
        if ($request_data['need_open']) {
            $where['USE_YN'] = array('eq', $request_data['need_open']);
        }
        $res = $this->code_table->field('CD, CD_VAL')->where($where)->select();
        $res = array_column($res, 'CD_VAL', 'CD');
        if ($res && $request_data['need_default'] === 'Y') {
            array_unshift($res,"无");
        }
        return $res;
    }

    public function getCodeSearchList($where, $limit, $need_count, $is_export)
    {
        $query      = $this->code_table->field('CD, CD_NM, CD_VAL, ETC, ETC2, ETC3,ETC4,ETC5,USE_YN, SORT_NO')->where($where);
        $query_copy = clone $query;
        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (!$is_export) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('created_at desc')->select();
        $count['all_num'] = 0;
        $count['open_num'] = 0; 
        if ($need_count) {
            $count = $this->getCodeCountsDataInfo($db_res);
        }
        
        return [$db_res, $pages, $count];
    }

    public function codeSearchList($request_data, $is_export = false)
    {
        $search_map  = [
            'code_value' => 'CD_VAL',
        ];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", []);
           
        $where = $this->getMoreWhereData($request_data['search'], $where);
      
        list($res_db, $pages, $count) = $this->getCodeSearchList($where, $limit, $request_data['search']['need_count'], $is_export);
        return [
            'data'  => $res_db,
            'pages' => $pages,
            'count' => $count
        ];
    }

    public function getMoreWhereData($search, $where = [])
    {
        $comment_map = [
            '1' => 'ETC',
            '2' => 'ETC2',
            '3' => 'ETC3',
            '4' => 'ETC4',
            '5' => 'ETC5',
        ];
        if ($search['prefix']) {
            $where['CD'] = array('like', '%' . $search['prefix'] .'%');
        }
        if ($search['code_id']) {
            $where['CD'] = array('like', '%' . $search['code_id'] .'%');
        }
        if ($search['prefix'] && $search['code_id']) {
            $where['CD'] =array('like',array('%' . $search['prefix'] .'%', '%' . $search['code_id'] .'%'), 'AND');
        }
        if ($search['comment_content']) {
            if (!$search['comment_type']) { // 没有表示etc1,etc2,etc3只要符合都行
                $map['ETC']  = array('like', '%' . $search['comment_content'] .'%');
                $map['ETC2']  = array('like', '%' . $search['comment_content'] .'%');
                $map['ETC3']  = array('like', '%' . $search['comment_content'] .'%');
                $map['ETC4']  = array('like', '%' . $search['comment_content'] .'%');
                $map['_logic'] = 'or';
                $where['_complex'] = $map;
            } else {
                $where[$comment_map[$search['comment_type']]] = array('like', '%' . $search['comment_content'] .'%');
            }
        }
        if ($search['ids']) {
            $where['CD'] = array('in', $search['ids']);
        }
        return $where;
    }

    public function getCodeTypeKeyVal($status)
    {
        if (strval($status) !== '1') { // 获取全部
            $status = '0, 1';
        }
        $return_res = $this->CodeRepository->getCodeTypeKeyVal($status);
        return $return_res;
    }

    // 获取某个code type旗下的code总数量以及开启状态的数量
    public function getCodeCountsInfo($dict, $prefix)
    {
        $count['all_num'] = count($dict[$prefix]);

        $open_num = 0;
        foreach ($dict[$prefix] as $key => $value) {
            if ($value['USE_YN'] === 'Y') {
                $open_num++;
            }
        }
        $count['open_num'] = $open_num;

        return $count;
    }

    // 获取code数据列表总数量以及开启状态的数量
    public function getCodeCountsDataInfo($dict)
    {
        $count['all_num'] = count($dict);

        $open_num = 0;
        foreach ($dict as $key => $value) {
            if ($value['USE_YN'] === 'Y') {
                $open_num++;
            }
        }
        $count['open_num'] = $open_num;
        return $count;
    }

    // 获取某个code type 或全部 的详细信息
    public function getCdTypeList($cd_type)
    {
        $where = [];
        if ($cd_type) {
            $where = ['cd_type', $cd_type];
        }
        $res = $this->CodeRepository->getCodeTypeList($where);

        return $res;
    }

    // code type 状态更改，开启关闭
    public function changeCdTypeStatus($request_data)
    {
        // codetype想要关闭状态时，要确认该code类型下所有CODE的USE_YN状态都为“N”，如果不是，不允许关闭，开启不用判断
        if (strval($request_data['status']) === '0') {
            $CodeModel = new CodeModel();
            $checkRes = $CodeModel->getCodeArr([$request_data['cd_type']]); // 检测是否有尚在使用的code

            if ($checkRes) {
                throw new Exception(L('该code type下还有尚在使用状态的code，无法执行关闭操作'));
            }
        }

        $save['status'] = $request_data['status'];
        $save['cd_type'] = $where['cd_type'] = $request_data['cd_type'];
        $this->CodeRepository->updateCodeTypeStatus($where, $save);
    }

    public function createCdType($request_data)
    {
        // 判断该名称是否已经存在
        $checkRes = $this->CodeRepository->checkCodeTypeNameExist(['cd_type_name', $request_data['cd_type_name']]);
        if ($checkRes) {
            throw new Exception(L('该code type名称已经存在，无需重复新增'));
        }

        // 组装数据
        $cd_type_key = $this->CodeRepository->getLastCodeTypeValue();
        $cd_type_key = str_pad((int)$cd_type_key+1, 5, "0", STR_PAD_LEFT);
        $cd_type = 'N'.$cd_type_key;
        $addData = [
            'cd_type' => $cd_type,
            'cd_type_key' => $cd_type_key,
            'cd_type_name' => $request_data['cd_type_name'],
            'status' => 1, // 默认开启

        ];
        
        $res = $this->CodeRepository->createCodeType($addData);
        if (!$res) {
            throw new Exception(L('新增数据失败'));
        }
        return $res;
    }

    public function modifyRepeatCodeData($res)
    {
        if (!$res) {
            return false;
        }
        $cd_arr = '';
        foreach ($res as $key => &$value) {
            // $cd_arr = str_replace($value['cd_arr'], "','", ',');
            $value['cd_arr'] = trim($value['cd_arr'], "'");
            $re = explode("','", $value['cd_arr']);

            foreach ($re as $k => $v) {
                $where['CD'] = trim($v);
                $save['CD_NM'] = $value['CD_NM'] . '_repeat';
                $resu = $this->CodeRepository->updateCode($where, $save);
                if (!$resu) {
                    p($where['CD']);die;
                }
            }
            // 把原有的code type的值添加repeat
            $cdTypeWhere['cd_type'] = $value['CD_6'];
            $cdTypeSave['cd_type_name'] = $value['CD_NM'] . '_repeat';
            $cdRes = $this->CodeRepository->updateCodeTypeStatus($cdTypeWhere, $cdTypeSave);
            unset($cdTypeWhere);
            unset($cdTypeSave);
            if (!$cdRes) {
                p("cdtype更新失败{$value['CD_6']}");die;
            }
        }
        return true;
    }
    public function getCodeTypeByGroup()
    {
        // 处理不同的type下相同的cd_type（添加"_repeat"）;
        $res = $this->CodeRepository->modifyCodeData();
        $this->modifyRepeatCodeData($res);

        $res = $this->CodeRepository->getCodeTypeByGroup();
        // 获取code type 的数据
        $codeTypeList = $this->CodeRepository->getCodeTypeKeyVal();
        $codeTypeList = array_flip($codeTypeList);
        // p($codeTypeList);die;

        // p($res);die;
        $addDataRes = [];
        $cdTypeRes = [];
        foreach ($res as $key => $value) {
            $cd_type = substr($value['CD'], 0, 6);
            // 处理同一个type下多个不同的cd_type_name
            if (in_array($cd_type, $cdTypeRes)) {
                continue;
            }
            if (in_array($cd_type, $codeTypeList)) {
                continue;
            }

            $cdTypeRes[] = $cd_type;

            $addDataRes[$key]['cd_type'] = $cd_type;
            $addDataRes[$key]['cd_type_name'] = $value['CD_NM'];
            $addDataRes[$key]['cd_type_key'] = substr($value['CD'], 1, 5);
            $addDataRes[$key]['status'] = 1;
            $addDataRes[$key]['created_by'] = 'system';

        }
        // p($addDataRes);die;
        $res = $this->CodeRepository->createBatchCode($addDataRes);
        return $res;
    }

    public function deleteCd($request_data)
    {
        // to do        
    }

    public function checkSaveDictionary($request_data)
    {
        if (!$request_data['data']) {
            throw new Exception(L('提交无数据，请检查后再提交'));        
        }
        // 校验传递过来的值是否有重复
        $cdValRes = array_column($request_data['data'], 'CD_VAL');
        if (count($cdValRes) != count(array_unique($cdValRes))) {
            throw new Exception(L('提交的Code Value有重复值，请检查后再提交'));
        }

        // 检查code type是否开启
        $res = $this->CodeRepository->checkCodeTypeStatus($request_data['cd_type']);
        if (!$res) {
            throw new Exception(L('提交的Code Type未开启，请开启后再提交'));
        }
        
        // 校验该type下数据库里的值是否有重复（除提交的CD本身）
        // 先获取该type下所有名称
        $codeValueRes = $this->CodeRepository->getCodeKeyVal($request_data['cd_type']);
        $codeValueKeyRes = array_flip($codeValueRes);


        // 循环判断
        foreach ($request_data['data'] as $key => $value) {

            if (in_array($value['CD_VAL'], $codeValueRes)) {
 
                if ($value['CD'] && $value['CD'] === $codeValueKeyRes[$value['CD_VAL']]) { // 排除该CD本身值
                    continue;
                }
                throw new Exception(L('提交的Code Value有重复值或数据库中已存在（'.$value['CD_VAL'].'），请检查后再提交'));
            }
        }
    }

    public function saveDictionaryByCdType($request_data)
    {
        $model = new Model();
        $model->startTrans();
        $this->checkSaveDictionary($request_data);
        $cd_type = $request_data['cd_type'];       
        // 判断该类型下是否有值，没有默认从0001开始
        $directoryModel = new DictionaryModel();
        $dict = $directoryModel->getDictByType([$cd_type]);
        $lastCDNum = 0;
        $increateNum = 0;
        if (count($dict[$cd_type]))
        {
            // 根据code type 获取最新的code值
            $condtion['CD'] = array("like","%".$cd_type."%");
            $lastCD = $this->CodeRepository->getLastCodeValue($condtion);
            $lastCDNum = substr($lastCD, 6, 4);
        }

        // 循环处理数据，有cd则更新，没有则按照（cd + 1）规则新增
        $request_data['data'] = array_reverse($request_data['data']); //数组顺序颠倒,保证前端、后台数据顺序
        foreach ($request_data['data'] as $key => &$value) {
            $save = [];
            $where = [];

            if ("Y" === $value['is_add']) { // 新增
                unset($value['CD']);
                unset($value['is_add']);
                $increateNum++;
                $save = $value;
                $cd_type_num = str_pad((int)$lastCDNum + $increateNum, 4, "0", STR_PAD_LEFT);
                if ((int)$cd_type_num > 9999) {
                    $model->rollback();
                    throw new Exception(L('亲，该code type下提交的'.$value['CD_VAL'].'（含）的值无法继续新增，因为超过上限（9999）啦'));
                }
                $save['CD'] = $cd_type . $cd_type_num;
                $res_add = $this->CodeRepository->createCode($save);
                if (!$res_add) {
                    $model->rollback();
                    throw new Exception(L('添加失败'));
                }
                $cd = $cd_type . $cd_type_num;
                if (substr($cd, 0, 6) == TbMsCmnCdModel::$warehouse_cd_pre) {
                    //所有店铺设置成不支持该新增的仓库
                    $res_warehouse = (new ConfigurationService($model))->setStoreNotSupportWarehouse($cd);
                    if (!$res_warehouse) {
                        $model->rollback();
                        throw new Exception(L('设置店铺不支持该仓库失败'));
                    }
                }
                $res[] = ['cd' => $save['CD'], 'val' => $save['CD_VAL']];
        
            } else if ('N' === $value['is_add']) { // 更新
                $where['CD'] = $value['CD'];
                unset($value['is_add']);
                $save = $value;
                // p($save);die;
                $res = $this->CodeRepository->updateCode($where, $save);
                if (false === $res) {
                    $model->rollback();
                    throw new Exception('更新CODE失败:'.$model->getDbError());
                }       
            }
        }
        $model->commit();
        return $res;
    }

    public function synOnlineCodeType()
    {
        $model = M('cmn_cd_type', 'tb_ms_');
        $stage_types = $model->getField('cd_type', true);

        $fail = [];

        $list = ApiModel::getOnlineCodeTypes();

        foreach ($list as $row) {
            if (in_array($row['cd_type'], $stage_types)) {
                continue;
            }
            unset($row['id']);
            $res = $model->add($row);
            if (!$res) {
                $fail[] = $res;
                Logs('syn online code type failed:'.json_encode($row), __FUNCTION__, 'fm');
            }
        }
        if (!empty($fail)) {
            throw new Exception('同步失败');
        }
        return true;
    }

    public function exportXls($data)
    {
        include_once __DIR__ . DIRECTORY_SEPARATOR . 'Oms' . DIRECTORY_SEPARATOR . 'ExportModel.class.php';
        $exportExcel = new ExportModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('Code Type Name'), 'field_name' => 'CD_NM'],
            $key++ => ['name' => L('Code Id'), 'field_name' => 'CD'],
            $key++ => ['name' => L('Code Value'), 'field_name' => 'CD_VAL'],
            $key++ => ['name' => L('Avaliable'), 'field_name' => 'USE_YN'],
            $key++ => ['name' => L('Sort No'), 'field_name' => 'SORT_NO'],
            $key++ => ['name' => L('Comment1'), 'field_name' => 'ETC'],
            $key++ => ['name' => L('Comment2'), 'field_name' => 'ETC2'],
            $key++ => ['name' => L('Comment3'), 'field_name' => 'ETC3'],
            $key++ => ['name' => L('Comment4'), 'field_name' => 'ETC4'],
            $key++ => ['name' => L('Comment5'), 'field_name' => 'ETC5'],
        ];
        $index = 1;
        foreach ($data as &$v) {
            $v['index'] = $index++;
            $v['CD_NM'] = empty($v['CD_NM']) ? '' : $v['CD_NM'];
            $v['CD'] = empty($v['CD']) ? '' : $v['CD'];
            $v['CD_VAL'] = empty($v['CD_VAL']) ? '' : $v['CD_VAL'];
            $v['USE_YN'] = $v['USE_YN'] == 'Y' ? 'On' :'Off';
            $v['SORT_NO'] = empty($v['SORT_NO']) ? 0 : $v['SORT_NO'];
            $v['ETC'] = empty($v['ETC']) ? '' : $v['ETC'];
            $v['ETC2'] = empty($v['ETC2']) ? '' : $v['ETC2'];
            $v['ETC3'] = empty($v['ETC3']) ? '' : $v['ETC3'];
            $v['ETC4'] = empty($v['ETC4']) ? '' : $v['ETC4'];
            $v['ETC5'] = empty($v['ETC5']) ? '' : $v['ETC5'];
        }
        unset($v);
        $exportExcel->data = $data;
        $exportExcel->exportStyle = true; //需要样式
        $exportExcel->export();
    }
}