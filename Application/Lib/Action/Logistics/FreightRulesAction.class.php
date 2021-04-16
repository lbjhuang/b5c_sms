<?php
/**
 * Created by PhpStorm.
 * Date: 2018/01/04
 * Time: 09:40
 */
class FreightRulesAction extends BaseAction{
    protected $pk = 'ID';
    public function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        parent::_initialize();
    }
    /**
     * 运费规则列表页
     */
    public function rule_list()
    {
        $logModeId = $_GET['logModeId'];
        $this->assign('logModeId',$logModeId);
        $this->display();
    }
    /**
     * 运费规则创建页
     */
    public function rule_add()
    {
        $logModeId = $_GET['logModeId'];
        $postageId = $_GET['postageId'];
        $this->assign('postageId',$postageId);
        $this->assign('logModeId',$logModeId);
        $this->display();
    }


    /**
     * 运费规则详情页
     */
    public function rule_detail()
    {
        $modelID = $_GET['modelID'];
        $logModeId = $_GET['logModeId'];
        $this->assign('modelID',$modelID);
        $this->assign('logModeId',$logModeId);
        $this->display();
    }

    /**
     * 運費模板操作日志
     */
    public function rule_log()
    {
        $logModeId = $_GET['logModeId'];
        $this->assign('logModeId',$logModeId);
        $this->display();
    }

    /**  
     * 运费规则list
     */
    
    public function getRuleList()
    {
        $ruleModel = new LgtPostageModel();
        $freData =  $ruleModel->getListData();
        $this->jsonOut(array('code' => 200, 'msg' => $freData['count'], 'data' => $freData['data']));
        $this->jsonOut($freData);
    }



    /** 
     *物流方式与物流公司
     */
    public function getLogModeData()
    {
        $id = $_GET['id'];
        $logMode = new LogisticsModeModel();
        $data = $logMode->getModeById($id);
        $codeData = M("ms_cmn_cd","tb_")->field("CD_VAL,CD")
                ->where('CD='."'".$data['LOGISTICS_CODE']."'")->find();
        $data['logCompany'] = $codeData['CD_VAL'];
        $this->jsonOut($data);
    }



    /** 
     *新增运费模板
     */
    public function addPostageTemp()
    {
        $postageTempData = Mainfunc::chooseParam('param');
        if ($postageTempData['FIRST_HEAVY_TYPE']) {
            $postageTempData['FIRST_HEAVY_TYPE'] = 1;
        }else{
            $postageTempData['FIRST_HEAVY_TYPE'] = 0;
        }
        
        $logMode = new LgtPostageModel();
        $tempDataRes = $logMode->getAddTempData($postageTempData);
        if (is_array($tempDataRes)) {
            $this->jsonOut($tempDataRes);
        }
        $this->jsonOut(array('code' => 200, 'msg' => 'add success', 'data' => $tempDataRes));

    }
    /**
     * 编辑运费模板
     */

     public function editPostageTemp()
    {
        $postageId = $_GET['postageId'];
        $postageTempData = Mainfunc::chooseParam('param');
        if ($postageTempData['FIRST_HEAVY_TYPE']) {
            $postageTempData['FIRST_HEAVY_TYPE'] = 1;
        }else{
            $postageTempData['FIRST_HEAVY_TYPE'] = 0;
        }
        $logMode = new LgtPostageModel();
        $tempDataRes = $logMode->getEditTempData($postageTempData,$postageId);
        if (is_array($tempDataRes)) {
            $this->jsonOut($tempDataRes);
        }
        $this->jsonOut(array('code' => 200, 'msg' => 'edit success', 'data' => $tempDataRes));
        
    }


    /** 
     *获取国家下属区域信息
     */
    public function getAreaData()
    {
        $area_no = $_GET['area_no'];
        $areaModel = D("Area");
        $areaData = $areaModel->where('parent_no='.$area_no)->select();
        if (is_null($areaData)) $areaData = [];
        $this->jsonOut($areaData);   
    }
    
    /** 
     *模板详情
     */
    public function getdetailData()
    {
        $modelID = $_GET['modelID'];
        $logMode = new LgtPostageModel();
        $modelData = $logMode->getModel_DetailData($modelID);
        $this->jsonOut($modelData);
    }

    //搜索目的地
    public function search_Destination()
    {
        $searchStr = $_GET['searchStr'];
        if (!empty($searchStr)) { 
            $searchArr = explode(",", $searchStr);

            foreach ($searchArr as $k => $v) {
                //$where['zh_name'] = array('like',"%$v%");
                $where['zh_name'] = $v;
                $initData = M("ms_user_area","tb_")->where($where)->find();
                if ($initData['parent_no']==='0') {
                    $areaData['country'][] = M("ms_user_area","tb_")
                        ->join("tb_ms_cmn_cd on tb_ms_cmn_cd.CD=tb_ms_user_area.continent")
                        ->field("tb_ms_cmn_cd.CD,tb_ms_cmn_cd.CD_VAl,tb_ms_user_area.area_no,
                            tb_ms_user_area.area_type,tb_ms_user_area.rank,tb_ms_user_area.zh_name")
                        ->where($where)->find();
                }else if($initData['parent_no']!=='0' and !is_null($initData)){
                    $areaData['area'][] = $initData;
                }
                if (is_null($initData)) {
                    $areaData['error'][] = $v; 
                }
            }
            $this->jsonOut($areaData);
        }else{
            $this->jsonOut(array('code' => 500, 'msg' => 'error', 'data' => '搜索目的地为空'));
        }
        
    }
    //批量停用接口 ?
    public function disableModel()
    {
        $model= M("lgt_postage_model","tb_");
        $modelID = $_GET['modelId'];
        $logModeID = $_GET['logModeId'];
        $logModeModel = D("Logistics/LogisticsMode");
        $modelID_Arr= $logModeModel->field("POSTAGE_ID")->where("ID={$logModeID}")->find();
        $modelIDArr_HAS = explode(",", $modelID_Arr['POSTAGE_ID']);
        $modelIDArr = explode(",", $modelID);
        $data['POSTAGE_ID'] = implode(",", array_diff($modelIDArr_HAS, $modelIDArr));

        if (!empty($data['POSTAGE_ID'])){
            $res = $logModeModel->where("ID='{$logModeID}'")->save($data);
            if (!$res) {
                $this->jsonOut(array('code' => 500, 'msg' => 'error', 'data' => '操作失败'));
            } 
        }
        
        $logMode = new LgtPostageModel();
        $data['STATE_CODE'] = 1;
        foreach ($modelIDArr as  $v) {
            $editRes = $model->where('ID='.$v)->save($data);
        }
        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => '操作成功'));
    }

    //日志记录
   public function logList()
   {
       $logModeId = $_GET['logModeId'];
       $sePage = $_GET['sePage'];
       $pageSize = $_GET['pageSize'];
       $startPage = ($sePage-1)*$pageSize;
       $data = M("lgt_log","tb_")
       ->where("LOG_MODEL_ID={$logModeId} AND TYPE=1")
       ->limit($startPage,$pageSize)
       ->order('ID desc')
       ->select();
       $logMode = new LogisticsModeModel();
       $LOGISTICS_MODE = $logMode->field("LOGISTICS_MODE")->where("ID={$logModeId}")->find();

       $total = M("lgt_log","tb_")
       ->where("LOG_MODEL_ID={$logModeId} AND TYPE=1")
       ->count();
      // $total = count($data);
       $res = array("code"=>200,"data"=>$data,"total"=>intval($total),'name'=>$LOGISTICS_MODE['LOGISTICS_MODE']);
       $this->jsonOut($res);
   }

    /**
     * 模板下载
     */
    public function tpl_down()
    {
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Logistics/Public/import.xlsx';
        Http::download($filename, $filename);
    }

    public function import()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $res = (new LgtPostageModel)->import($queryParams);
        $this->data = json_encode($res);
        $this->display('import_result');
    }

    public function import_result()
    {
        $this->display();
    }

    public function get_log()
    {
        $queryParams = ZUtils::filterBlank($this->getParams());
        $res = (new LgtPostageModel)->getLog($queryParams);
        $this->jsonOut(['code' => 2000, 'data' => $res, 'msg' => 'success']);
    }

    public function log_excel()
    {
        import('ORG.Net.Http');
        $queryParams = ZUtils::filterBlank($this->getParams());
        $filename = FileModel::$BASE_PATH . '/' . $queryParams['file'];
        Http::download($filename, $filename);
    }
   }