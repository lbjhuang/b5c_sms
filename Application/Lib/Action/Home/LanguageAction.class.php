<?php
/**
 * User: yuanshixiao
 * Date: 2018/1/25
 * Time: 15:49
 */

class LanguageAction extends BaseAction
{
    /**
     * 翻译列表页面
     */
    public function index() {
        $this->display();
    }

    /**
     * 翻译列表接口
     */
    public function language_list() {
        $param = $this->getParams();
        $is_show = true;
        $res = $this->get_language_list($param, $is_show);
        $this->ajaxReturn($res,'',1);
    }

    public function get_language_list($param, $is_show = false) {

        $param['list_rows'] ? '' : $param['list_rows'] = 20;
        $where  = [];
        ZUtils::mbtrim($param['element']) ? $where['element'] = ['like','%'.ZUtils::mbtrim($param['element']).'%']:'';
        empty($param['ids']) or $where['id'] = ['in', $param['ids']];
        import('ORG.Util.Page');
        $count_sql  = M('language')->field('id')->where($where)->group('element')->buildSql();
        $count      = M()->table($count_sql.' a')->count();
        if ($param['isExport']) $param['list_rows'] = $count;
        $page       = new Page($count,$param['list_rows']);
        $list       = M('language')
            ->field('id,element,group_concat(concat_ws("||",type,translation_content) SEPARATOR "|||") translate')
            ->where($where)
            ->group('element')
            ->limit($page->firstRow,$page->listRows)
            ->order('CONVERT( element USING gbk ) COLLATE gbk_chinese_ci ASC')
            ->select();
        if ($is_show) { // 前端展示多语言列表，调整返回接口结构，导出功能暂时用原来的不变
            $codeModel = D('Universal/Dictionary');
            $languageRes = $codeModel->getLanguage('CD', 'ETC3');
            $initLanguageData = []; // 初始化多语言数据
            foreach ($languageRes as $key => $value) {
                $initLanguageData[$key] = '';
            }

        }

        foreach ($list as &$v) {
            $translation = explode('|||',trim($v['translate'],'|||'));
            $is_show? $v['name'] = $initLanguageData : '';
            foreach ($translation as $val) {
                $tran = explode('||',trim($val,'||'));
                if ($is_show) {
                    $v['name'][$tran[0]] = $tran[1];
                } else {
                    $v[$tran[0]] = $tran[1];
                }                
            }

            $is_show ? $v['name']['N000920100'] = $v['element'] : $v['N000920100'] = $v['element'];
            unset($v['translate']);
        }
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function export() {
        $param = $this->getParams();
        $param['isExport'] = true;
        $res = $this->get_language_list($param);
        $exportExcel = new ExportExcelModel();
        $key = 'A';
        $exportExcel->attributes = [
            $key++ => ['name' => L('元素名称'), 'field_name' => 'element'],
/*            $key++ => ['name' => L('英文翻译'), 'field_name' => 'N000920200'],
            $key++ => ['name' => L('日文翻译'), 'field_name' => 'N000920300'],
            $key++ => ['name' => L('韩文翻译'), 'field_name' => 'N000920400'],*/
        ];

        $codeModel = D('Universal/Dictionary');
        $languageRes = $codeModel->getLanguage('CD', 'CD_VAL');
        foreach ($languageRes as $k => $v) {
            $exportExcel->attributes[$key++] = ['name' => L($v."翻译"), 'field_name' => $k];
        }

        $exportExcel->data = $res['list'];
        $exportExcel->export();
    }

    /**
     * 翻译导入
     */
    public function import() {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error(L('请上传EXCEL文件'),'',true);
            }
        }
        //读取Excel文件
        $PHPExcel   = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet      = $PHPExcel->getSheet(0);
        //取得最大的行号
        $allRow     = $sheet->getHighestRow();
        $rows_check = [];
        $rows       = [];
        $has_error  = false;
        $language_m = new LanguageModel();
        $language_m->startTrans();
        $user = $_SESSION['m_loginname'];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $row['element']             = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $row['type']                = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            $row['translation_content'] = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());
            // $row['error_msg']           = '';
            unset($row['k']);
            unset($row['error_msg']);
            if(!$row['element'] || !$row['type'] || !$row['translation_content']) {
                $row['error_msg']   .= '|数据不全|';
                $has_error          = true;
            }
            $elements[]            = $row['element'];
            if($rows_check[$row['element']] && $rows_check[$row['element']][$row['type']]) {
                if(count($rows_check[$row['element']][$row['type']]) == 1) {
                    $rows[$rows_check[$row['element']][$row['type']][0]['k']]['error_msg'] .= '|重复|' ;
                }
                $row['error_msg']   .= '|重复|';
                $has_error          = true;
            }
            if (!$row['error_msg']) {
                $exists = $language_m->where(['element'=>$row['element'],'type'=>$row['type']])->select();
                if($exists) {
                    $row['updated_by'] = $user;
                    $res_row = $language_m->where(['element'=>$row['element'],'type'=>$row['type']])->save($row);

                }else {
                    $row['created_by'] = $user;
                    $row['updated_by'] = $user;
                    $res_row = $language_m->add($row);
                }
                if($res_row === false) {
                    ELog::add('导入翻译保存失败：'.M()->getDbError(),ELog::ERR);
                    $row['error_msg'] = '导入翻译保存失败,错误码为-2';
                    $has_error = true;
                    //$language_m->rollback();
                    //$this->ajaxReturn($rows,'导入失败',-1);
                }
            }
            
            $rows[]                                         = $row;
            $row['k']                                       = $currentRow-2;
            $rows_check[$row['element']][$row['type']][]    = $row;
        }
        if($has_error) {
            $language_m->rollback();
            $this->ajaxReturn($rows,'导入失败',-1);
        }else {
            $language_m->commit();
            $language_m->flushCache();
            $this->ajaxReturn(0,'导入成功',1);
        }
    }

    public function log_list() {
        $params = $this->getParams();
        if (empty($params['element'])) {
            $where['_string'] = '1 != 1';
        } else {
            $where['element'] = $params['element'];
        }
        empty($params['updated_at'][0]) or $where['log.updated_at'][] = ['egt', $params['updated_at'][0]];
        empty($params['updated_at'][1]) or $where['log.updated_at'][] = ['elt', $params['updated_at'][1] . ' 23:59:59'];
        empty($params['language']) or $where['log.type'] = $params['language'];
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 10;
        $offset = ($params['page'] - 1) * $params['page_size'];
        $query = M()->table('gs_dp.dp_bbm_language_log log')
            ->field([
                'log.*',
                'cd.cd_val as language'
            ])
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = log.type')
            ->where($where);
        $query1 = clone $query;
        $total = $query->count();
        $query1->limit($offset . ',' . $params['page_size']);
        $list = $query1->order('log.id desc')->select();
        $data = ['list' => $list, 'total' => $total];
        $this->ajaxReturn(['data' => $data, 'code' => 2000, 'msg' => 'success']);
    }

    /**
     * 批量保存接口
     */
    public function language_save() {
        $save_data  = $_POST['data'];
        $language_m = new LanguageModel();
        $res        = $language_m->saveAllTrans($save_data);
        if($res === false) {
            $this->error($language_m->getError());
        }else {
            $language_m->flushCache();
            $this->success('保存成功');
        }
    }

    /**
     * 删除接口
     */
    public function language_del() {
        $element        = $_REQUEST['element'];
        $language_m     = new LanguageModel();
        $res            = $language_m->where(['element'=>['in',$element]])->delete();
        if($res === false) {
            $this->error('删除失败');
        }else {
            $language_m->flushCache();
            $this->success('删除成功');
        }
    }

    /**
     * 检查翻译元素是否存在
     */
    public function check_exist() {
        $element = I('request.element');
        if((new LanguageModel())->where(['element'=>$element])->find()) {
            $this->success('元素已存在');
        }else {
            $this->error('元素不存在');
        }
    }
}