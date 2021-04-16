<?php

/**
 * User: shenmo
 * Date: 19/6/17
 * Time: 11:00
 */
class AccountingSubjectAction extends BaseAction
{
    protected $success_code = 200;

    /**
     * @name 会计科目列表分页
     * @param $is_excel
     */
    public function index()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data['pages']) {
                $limit = [($request_data['pages']['current_page'] - 1) * $request_data['pages']['per_page'], $request_data['pages']['per_page']];
            } else {
                $limit = [0, 10];
            }
            $where = TbAccountingSubjectModel::getWhere($request_data['search']);
            list($res_db, $pages) = TbAccountingSubjectModel::getList($where, $limit);

            $data = $this->getAccountingSubjectType($res_db);
            $res_return['data'] = $data;
            $res_return['pages'] = $pages;
            $res = DataModel::$success_return;
            $res['code'] = $this->success_code;
            $res['data'] = $res_return;
            $this->ajaxReturn($res);

        } catch (Exception $exception) {
            $this->ajaxError($res, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @name 查询父会计科目列表 （所有）
     * @param $data
     */
    public function getList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $where = TbAccountingSubjectModel::getWhere($request_data['search']);
            //父级次
            $where['level']--;
            $res_db = TbAccountingSubjectModel::getList($where, [], true);

            $data = $this->getAccountingSubjectType($res_db[0]);
            $res_return['data'] = $data;
            $res = DataModel::$success_return;
            $res['code'] = $this->success_code;
            $res['data'] = $res_return;
            $this->ajaxReturn($res);
        } catch (Exception $exception) {
            $this->ajaxError($res, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @name 会计科目详情\
     * @param $flag
     * @return data
     */
    public function getDetail($flag = 0)
    {
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['search'])) {
            $result = array('code' => '40003', 'msg' => L('请输入查询参数'), 'data' => null);
            $this->jsonOut($result);
        }
        $where = TbAccountingSubjectModel::getWhere($request_data['search']);
        $res_return = TbAccountingSubjectModel::getDetail($where);
        if (!empty($res_return)) {
            $res_return['subject_code_diff'] = $this->createDefCode($res_return['subject_code']);
        }
        if ($flag) {
            return $res_return;
        }
        $res = DataModel::$success_return;
        $res['code'] = $this->success_code;
        $res['data'] = $res_return;
        $this->ajaxReturn($res);
    }

    public function createDefCode($subjectCode)
    {
        //以字符串数字结尾或者字符串数字 code最大值 99
        if ((int)$subjectCode || (int)strrev($subjectCode)) {
            $prefix = substr($subjectCode, 0, strlen($subjectCode) - 2);
            $suffix = substr($subjectCode, -2);
            (int)$suffix < 99 ? $suffix++ : '99';
            $subjectCodeDef = $prefix . str_pad($suffix, 2, '0', STR_PAD_LEFT);
        } else {
            $subjectCodeDef = $subjectCode . '01';
        }
        return $subjectCodeDef;
    }

    /**
     * @name 会计科目类型 、级次配置列表
     * @param
     * @return json
     */
    public function getSubjectConf()
    {
        $directoryModel = new DictionaryModel();
        $typeData = $directoryModel->getAccountSubjectType();
        $levelData = $directoryModel->getAccountSubjectLevel();
        $res = DataModel::$success_return;
        $res['code'] = $this->success_code;
        $res['data']['account_subject_type'] = $typeData;
        $res['data']['account_subject_level'] = $levelData;
        $this->ajaxReturn($res);
    }

    /**
     * @name 会计科目新建
     * @param $data
     */
    public function create()
    {
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['subject_code'])) {
            $result = array('code' => '40001', 'msg' => L('请输入科目编码'), 'data' => null);
            $this->jsonOut($result);
        }
        if (empty($request_data['subject_name'])) {
            $result = array('code' => '40000', 'msg' => L('请输入科目名称'), 'data' => null);
            $this->jsonOut($result);
        }
        //级次大于1验证
        if ($request_data['level'] > 1 && empty($request_data['p_subject_code'])) {
            $result = array('code' => '40002', 'msg' => L('请添加上级科目'), 'data' => null);
            $this->jsonOut($result);
        }
        if ($ret = $this->checkAccountingSubject($request_data)) {
            $this->ajaxReturn($ret);
        }
        //记录创建用户
        $request_data['created_by'] = DataModel::userNamePinyin();
        $request_data['updated_by'] = DataModel::userNamePinyin();
        $res_return = TbAccountingSubjectModel::updateAccountingSubject($request_data);
        $res = DataModel::$success_return;
        $res['code'] = $this->success_code;
        $res['data'] = $res_return;
        Logs([$request_data,$res], __FUNCTION__, __CLASS__);
        $this->ajaxReturn($res);
    }

    public function update()
    {
        $request_data = DataModel::getDataNoBlankToArr();
        if (empty($request_data['subject_name'])) {
            $result = array('code' => '40003', 'msg' => L('请输入科目名称'), 'data' => null);
            $this->ajaxReturn($result);
        }
        if ($ret = $this->checkAccountingSubject($request_data)) {
            $this->ajaxReturn($ret);
        }
        //记录操作用户
        $request_data['updated_by'] = DataModel::userNamePinyin();
        $res_return = TbAccountingSubjectModel::updateAccountingSubject($request_data);
        $res = DataModel::$success_return;
        $res['code'] = $this->success_code;
        $res['data'] = $res_return;
        $this->ajaxReturn($res);
    }

    public function remove()
    {
        $request_data = DataModel::getDataNoBlankToArr();
        if ($ret = $this->checkAccountingSubject($request_data, 1)) {
            $this->ajaxReturn($ret);
        }
        $data['id'] = $request_data['id'];
        $data['is_delete_state'] = TbAccountingSubjectModel::IS_DELETE_STATE_YES;
        //记录操作用户
        $data['updated_by'] = DataModel::userNamePinyin();
        $res_return = TbAccountingSubjectModel::updateAccountingSubject($data);
        $res = DataModel::$success_return;
        $res['code'] = $this->success_code;
        $res['data'] = $res_return;
        $this->ajaxReturn($res);
    }

    public function checkAccountingSubject($data, $flag = 0)
    {
        $level = '';
        $subjectTypeCd = '';
        if (!empty($data['id'])) {
            $where = ['id' => $data['id']];
            $res_return = TbAccountingSubjectModel::getDetail($where);
            if (empty($res_return)) {
                return ['data' => null, 'msg' => L('无效的科目ID'), 'code' => 3003];
            }
            $level = $res_return['level'];
            $subjectTypeCd = $res_return['subject_type_cd'];
            //删除验证
            if ($flag) {
                if ($res_return['use_count'] > 0) {
                    return ['data' => null, 'msg' => L('该科目已被相关模块使用，无法删除。'), 'code' => 3004];
                }
                //查询子类科目
                $where = ['p_subject_code' => $res_return['subject_code']];
                $res_return = TbAccountingSubjectModel::getDetail($where);
                if (!empty($res_return)) {
                    return ['data' => null, 'msg' => L('该科目有从属次级科目，无法删除。'), 'code' => 3005];
                }
            }
        }
        if (!empty($data['subject_name'])) {
            //相同类别、级次下不允许同名
            $level = $level ? $level : TbAccountingSubjectModel::$levelMap[$data['level']];
            $subjectTypeCd = $subjectTypeCd ? $subjectTypeCd : $data['subject_type_cd'];
            $where = ['subject_name' => $data['subject_name'], 'level' => $level, 'subject_type_cd' => $subjectTypeCd];
            $res_return = TbAccountingSubjectModel::getDetail($where);
            if (!empty($res_return)) {
                return ['data' => null, 'msg' => L('已存在相同的科目名称'), 'code' => 3000];
            }
        }
        if (!empty($data['subject_code'])) {
            $where = ['subject_code' => $data['subject_code']];
            $res_return = TbAccountingSubjectModel::getDetail($where);
            if (!empty($res_return)) {
                return ['data' => null, 'msg' => L('已存在相同的科目编码'), 'code' => 3001];
            }
        }
        /*if (!empty($data['level']) && !empty($data['subject_code'])) {
            $level = TbAccountingSubjectModel::$levelMap[$data['level']];
            if (strlen($data['subject_code']) != TbAccountingSubjectModel::$levelLengthMap[$level]); {
                return ['data' => null,'msg' => L('科目编码长度请参照默认值'),'code' => 3002];
            }
        }*/

        return false;
    }

    public function getAccountingSubjectType($data = [])
    {
        $directoryModel = new DictionaryModel();
        $k = DictionaryModel::ACCOUNTING_SUBJECT_TYPE;
        $typeData = $directoryModel->getDictByType([$k], true);
        $list = [];
        foreach ($data as $item) {
            $item['subject_type'] = $typeData[$k][$item['subject_type_cd']];
            $list[] = $item;
        }
        return $list;
    }

    public function initAccountingSubject()
    {
        if ($_FILES) {
            $lang_set = 'en-us';
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['file']['tmp_name'];
            vendor("PHPExcel.PHPExcel");
            $objPHPExcel = new PHPExcel();
            //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    echo 'no Excel';
                    return;
                }
            }
            //读取Excel文件
            $PHPExcel = $PHPReader->load($filePath);
            //读取excel文件中的第一个工作表
            $sheet = $PHPExcel->getSheet(0);
            //取得最大的列号
            $allColumn = $sheet->getHighestColumn();
            //取得最大的行号
            $allRow = $sheet->getHighestRow();
            $temp = [];
            $err = [];
            //ERP中已有数据
            $model = M('_accounting_subject', 'tb_');
            //已有codeMap
            $code_ret = $model->getField('subject_code, subject_name');
            //已有nameMap
            $name_ret = array_flip($code_ret);
            $levelMap = array_flip(TbAccountingSubjectModel::$subjectTypeMap);
            //Excel导入的供应商客户数据
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $data = [];
                $subject_type = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());//供应商名称
                $level = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());//类型（供应商&客户）需要做拆分处理
                $subject_code = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());//是否已存在（中文类型，是或者否）
                $subject_name = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());//详细办公地址
                $data['subject_name'] = $subject_name;
                $data['subject_code'] = $subject_code;
                $data['p_subject_code'] = '';
                $data['subject_type_name'] = $subject_type;
                $data['subject_type_cd'] = '';
                $data['level'] = $level;
                $data['p_level'] = $level - 1;
                if (isset($code_ret[$subject_code]) || isset($name_ret[$subject_name])) {
                    $err[] = $data;
                    continue;
                }
                if (isset($levelMap[$subject_type])) {
                    $data ['subject_type_cd'] = $levelMap[$subject_type];
                }
                if ($level > 1) {
                    //截取 其中 1~n-2位字符
                    $data['p_subject_code'] = substr($subject_code, 0, strlen($subject_code) - 2);
                }
                //记录创建用户
                $data['created_by'] = DataModel::userNamePinyin();
                $data['updated_by'] = DataModel::userNamePinyin();
                $data ['created_at'] = date('Y-m-d H:i:s');
                $temp[] = $data;
            }
            $ret_subject = $model->addAll($temp);
            $this->assign('show', true);
            $this->assign('ret_subject', count($temp));
            $this->assign('temp', $temp);
            $this->assign('err', $err);
        }

        $this->display('Finance/import_accounting_subject');
    }
}
