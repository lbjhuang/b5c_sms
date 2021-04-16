<?php

/**
 * 偏远地区仓储类
 * Class RemoteAreaRepository
 */
import('ORG.Net.UploadFile');

class RemoteAreaRepository extends Repository
{
    # 表格列的对应关系
    protected $cols_map = [
        'A' => 'good_name',
        'B' => 'prefix_postal_code',
        'C' => 'logistics_company',
        'D' => 'logistics_mode',
    ];

    protected $errors = [];

    public function import()
    {
        if(isset($_FILES['excel']) && $_FILES['excel']['error'] == 0) {
            // 判断是不是excel 文件
            $allowExts = ['xlsx', 'xls']; // 附件上传类型
            ini_set('date.timezone', 'Asia/Shanghai');
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['excel']['tmp_name'];  //导入的excel路径

            // 文件后缀类型判断
            $ext = substr($_FILES['excel']['name'], strrpos($_FILES['excel']['name'], '.')+1);
            if(!in_array($ext, $allowExts))
            {
                return  ['status'=> false,'msg' => '请选择正确的导入文件！','data' => []];
            }
            vendor("PHPExcel.PHPExcel");
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    return  ['status'=> false,'msg' => '上传不是excel文件','data' => []];
                }
            }
            $PHPExcel = $PHPReader->load($filePath); // 文件名称
            $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表从0读起
            $rows = $sheet->getHighestRow();//行数
            $cols = $sheet->getHighestColumn();//列数
            $cols_map = $this->cols_map;
            # 表单数据提取
            $list = [];
            for ($row = 2; $row <= $rows; $row++){ //行数是以第2行开始
                for ($col = 'A'; $col <= $cols; $col++) {
                    $cel = $col . $row;
                    $column_name = isset($cols_map[$col]) ? $cols_map[$col] : '';
                    $val = $sheet->getCell($col . $row)->getFormattedValue();
                    $list[$row][$column_name] = trim($val);
                }

                # 剔除最后一行数据异常问题
                if($row == $rows && !array_filter(array_values($list[$row]))) {
                    unset($list[$row]);
                }
            }
            # 表单数据校验
            $res = $this->validImportData($list);
            if(!$res['status'])
            {
                return $res;
            }

            $data = $res['data'];
            $insert_data = [];
            $opAreaConfigurationModel = D("Oms/OpAreaConfiguration");
            $fields = $opAreaConfigurationModel->getDbFields();
            foreach ($data as $item)
            {
                $tmp = array_only($item, $fields);
                $insert_data[] = $tmp;
            }
            $res = $opAreaConfigurationModel->addAll($insert_data);
            if(!$res) {
                return ['status'=> false,'msg' => '批量更新失败，请稍后再试','data' => []];
            }
            return ['status'=> true,'msg' => '数据导入成功','data' => []];
            # 数据插入
        }
        return ['status'=> false,'msg' => '文件上传失败','data' => []];
    }

    /**
     * 校验导出数据
     * @param array $data
     * @author Redbo He
     * @date 2021/2/2 17:59
     */
    protected function validImportData(array $data)
    {
        if($data) {
            $counties             = (new AreaModel())->getChildrenArea(0);
            $contries_names       = array_column($counties, 'zh_name');
            $logisticsCompanies   = CommonDataModel::logisticsCompany();
            $logisticsType        = CommonDataModel::logisticsType();
            $logisticsModes       = array_column($logisticsType, 'logisticsMode');
            $logisticsCompanyVals = array_column($logisticsCompanies, 'cdVal');
            # 消费券
            $validates = [

                # 国家校验
                ['good_name', 'require', '国家不能为空！', 1, 'regex', 3],
                ['good_name', $contries_names, '国家填写有误', Model::VALUE_VALIDATE, 'in'],

                ## 邮编前n位
                ['prefix_postal_code', 'require', '邮编前n位不能为空！', 1, 'regex', 3],
                ['prefix_postal_code', '1,20', '字数超过20，请确认！', 2, 'length', 3],

                # 物流校验
                ['logistics_company', 'require', '物流公司不能为空！', 1, 'regex', 3],
                ['logistics_company', $logisticsCompanyVals, '物流公司填写有误', Model::VALUE_VALIDATE , 'in'],

                # 物流方式校验
                ['logistics_mode', 'require', '物流方式不能为空！', 1, 'regex', 3],
                ['logistics_mode', '1,100', '字数超过100，请确认！', 2, 'length', 3],
                ['logistics_mode', $logisticsModes, '物流方式填写有误', Model::VALUE_VALIDATE, 'in'],
            ];

            # OpAreaConfiguration
            $opAreaConfigurationModel = D("Oms/OpAreaConfiguration");
            # //3 是我们自己设定的 在Tp中1代表添加 2代表修改 其他则是我们自己定义
            $error_list     = [];
            $field_cols_map = array_flip($this->cols_map);
            foreach ($data as $index => $item) {
                # 由于设置了批量更新可获取到定位信息
                if (!$opAreaConfigurationModel->validate($validates)->create($item, 3)) {
                    $errors = $opAreaConfigurationModel->getError();
                    foreach ($errors as $key => $msg) {
                        $col     = $field_cols_map[$key];
                        $err_msg = $col . $index . ":" . $msg;
                        $error_list[$index]['line']     = $index;
                        $error_list[$index]['errors'][] = $err_msg;
                    }
                }
            }
            # 数组值替换、组装新数据
            $counties_name_id_map       = array_column($counties, 'id', 'zh_name');
            $logisticsCompaniesValCdMap = array_column($logisticsCompanies, 'cd', 'cdVal');
            $logisticsModeIdMap         = array_column($logisticsType, 'id', 'logisticsMode');
            # 转换数据数据
            $where       = [];
            foreach ($data as $k => &$item) {
                $data[$k]['country_id']        = $counties_name_id_map[$item['good_name']];
                $data[$k]['logistics_company'] = $logisticsCompaniesValCdMap[$item['logistics_company']];
                $data[$k]['logistics_mode_ori']= $item['logistics_mode'];
                $data[$k]['logistics_mode']    = $logisticsModeIdMap[$item['logistics_mode']];

            }

            $logistics_companies = array_unique(array_column($data,'logistics_company'));
            $ms_logistics_mode_model = M("ms_logistics_mode","tb_");
            $logistics_company_modes = $ms_logistics_mode_model->where([
                                        "LOGISTICS_CODE" => ['in', $logistics_companies]
                                    ])
                                    ->field("ID,LOGISTICS_CODE,LOGISTICS_MODE")
                                     ->select();
            $logistics_company_modes_map = [];
            foreach ($logistics_company_modes as $logistics_company_mode) {
                $logistics_company_modes_map[$logistics_company_mode['LOGISTICS_CODE']][] = $logistics_company_mode['LOGISTICS_MODE'];
            }
            $check_data = $opAreaConfigurationModel->select();
            $exist_data  =  [];

            if ($check_data) {
                foreach ($check_data as $check_item) {
                    $check_index              = $check_item['country_id'] . "_" . $check_item['prefix_postal_code'] . "_" . $check_item['logistics_company'] . '_' . $check_item['logistics_mode'];
                    $exist_data[$check_index] = $item;

                }
            }
            # 校验数据是否已存在 $logistics_company_modes_map
            $exist_indexes = $exist_indexes2 =  [];
//            dd($logistics_company_modes_map);
            foreach ($data as $index => $item2) {
                $data_index  = $item2['country_id'] . "_" . $item2['prefix_postal_code'] . "_" . $item2['logistics_company'] . '_' . $item2['logistics_mode'];
                if($exist_indexes && in_array($data_index, $exist_indexes)) {
                    $error_list[$index]['line']     = $index;
                    $error_list[$index]['errors'][] = "当前记录已重复，请检查";
                }
                if (isset($exist_data[$data_index])) {
                    $error_list[$index]['line']     = $index;
                    $error_list[$index]['errors'][] = "当前记录已存在";
                }
                $company_modes = isset($logistics_company_modes_map[$item2['logistics_company']]) ?  $logistics_company_modes_map[$item2['logistics_company']] : [];
                if(empty($company_modes) || ($company_modes && !in_array( $item2['logistics_mode_ori'], $company_modes))) {
                    $col = $field_cols_map['logistics_mode'];
                    $error_list[$index]['line']     = $index;
                    $error_list[$index]['errors'][] = $col . $index . ":" .  "物流方式与物流公司不匹配";
                }
                $exist_indexes[] = $data_index;
            }
            if ($error_list) {
                return ['status' => false, 'msg' => "数据校验不通过", "data" => $error_list];
            } else
            {
                return ['status' => true, 'msg' => "数据校验通过", "data" => $data];
            }
        }
        return ['status' => false , 'msg' => "数据不存在","data" => []];

    }

}