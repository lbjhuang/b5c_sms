<?php
/**
 * postage Model
 *
 * User: huanzhu
 * Date: 2018/1/8
 * Time: 13:12
 *
 */
class LgtPostageModel extends BaseModel
{

    protected $trueTableName = 'tb_lgt_postage_model';

    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER', 'getName', Model::MODEL_BOTH, 'callback']
    ];
    protected $_validate = [
        ['MODEL_NM','require','请输入模版名称'],//默认情况下用正则进行验证
        ['STATE_CODE','require','请选择状态'],//默认情况下用正则进行验证
        ['OUT_AREAS','require','请选择出发地'],//默认情况下用正则进行验证
        ['DAY2','require','请填写时效结束时间'],//默认情况下用正则进行验证
        ['DENOMINATED_TYPE','require','请选择计价方式'],//默认情况下用正则进行验证
        //  ['COEFFICIENT','require','请输入计泡系数'],//默认情况下用正则进行验证
        //['MAX_WEIGHT','require','请输入最大重量'],//默认情况下用正则进行验证
        ['POSTTAGE_DISCOUNT','require','请输入运费折扣'],//默认情况下用正则进行验证
        ['PROCESS_DISCOUNT','require','请输入处理费折扣'],//默认情况下用正则进行验证
//        ['REMARK','require','请输入注意事项'],//默认情况下用正则进行验证
        ['DOCKING_HR','require','请输入对接hr'],//默认情况下用正则进行验证
        ['ERP_ACT','require','请输入ERP账号'],//默认情况下用正则进行验证
        //['RANK','require','请输入职级'],//默认情况下用正则进行验证
        // ['ERP_PWD','require','请输入ERP密码'],//默认情况下用正则进行验证     IS_FILED
        ['STATUS','require','请输入状态'],//默认情况下用正则进行验证     IS_FILED
        //['IS_FILED','require','请选择档案是否归档']//默认情况下用正则进行验证
    ];

    private $importErr = [];//导入错误信息
    private $excel_name = '';
    private $importMap = [
        'logCompany',
        'LOGISTICS_MODE',//LOGISTICS_MODEL_ID
        'MODEL_NM',
        'STATE_CODE',//0启用1停用
        'OUT_AREAS',
        'DAY1',
        'DAY2',
        'DENOMINATED_TYPE',//0-仅计重;1-计泡
        'COEFFICIENT',
        'MAX_WEIGHT',//FIRST_HEAVY_TYPE
        'POSTTAGE_DISCOUNT',
        'POSTTAGE_DISCOUNT_DATE_START',
        'POSTTAGE_DISCOUNT_DATE_END',
        'PROCESS_DISCOUNT',
        'PROCESS_DISCOUNT_DATE_START',
        'PROCESS_DISCOUNT_DATE_END',
        'WEIGHT1',//[闭区间
        'WEIGHT2',//)开区间
        'COST',
        'PROCESS_WEIGHT',
        'PROCESS_COST',
        'BAN_ITEM_CAT',
        'LENGTH1_START',
        'LENGTH1_END',
        'LENGTH2_START',
        'LENGTH2_END',
        'LENGTH3_MAX',
        'VOLUME_MAX',
        'SEND_AREAS',//支持国家
        'SEND_AREAS_PART',//支持区域
    ];

    public function getName()
    {
        return $_SESSION['m_loginname'];
    }

    /**
     * @return $res list data
     */
    public function getListData()
    {
        $logModeId = $_GET['logModeId'];
        $pagenow = $_GET['pagenow'];
        $condition = Mainfunc::chooseParam('param');

        $pageSize = $_GET['pageSize'];
        $startPage = ($pagenow-1)*$pageSize;
        $startPage = $startPage>0?$startPage:0;
        $where = $this->getWhere($logModeId,$condition);
        $data = $this
            ->where($where)
            ->limit($startPage,$pageSize)
            ->order('ID desc')
            ->select();
        $count = $this->getCounts($logModeId,$condition);
        $conditionCount = 0;
        foreach ($data as $k => $v) {
            $OUT_AREAS = explode(",", $v['OUT_AREAS']);
            $SEND_AREAS = explode(",",$v['SEND_AREAS']);
            $OUT_AREAS_DATA = M("ms_cmn_cd","tb_")->field("CD_VAL")->where("CD='{$OUT_AREAS[0]}'")->find();
            $SEND_AREAS_DATA = M("ms_user_area","tb_")->field("zh_name")->where("area_no='{$SEND_AREAS[1]}'")->find();
            $data[$k]['OUT_AREAS_SHOW'] = $OUT_AREAS_DATA['CD_VAL'];
            $data[$k]['SEND_AREAS_SHOW'] = $SEND_AREAS_DATA['zh_name'];

            $valData = M("lgt_postage_val","tb_")
                ->field("ID,POSTAGE_MODEL_ID,WEIGHT1,COST")
                ->where("POSTAGE_MODEL_ID=".$v['ID'])->select();
            if ($v['FIRST_HEAVY_TYPE']==='1') {   //无首重
                $data[$k]['first_heavy'] = '-';
                $data[$k]['first_heavy_cost'] = '-';
                $data[$k]['second_heavy'] = $valData[0]['WEIGHT1'];
                $data[$k]['second_heavy_cost'] = $valData[0]['COST'];
            }else{
                $data[$k]['first_heavy'] = $valData[0]['WEIGHT1'];
                $data[$k]['first_heavy_cost'] = $valData[0]['COST'];
                $data[$k]['second_heavy'] = $valData[1]['WEIGHT1'];
                $data[$k]['second_heavy_cost'] = $valData[1]['WEIGHT1'];
            }
            $data[$k]['SEND_AREAS_SEARCH'] = explode(",", $v['SEND_AREAS']);
        }

        $res['data'] = $data?$data:[];
        $res['count'] = $count?:0;
        return $res;
    }

    private function getCounts($logModeId,$condition)
    {
        $where = $this->getWhere($logModeId,$condition);
        $count = $this
            ->where($where)
            ->count();
        return $count;
    }

    private function getWhere($logModeId,$condition=array())
    {
        if (!is_null($logModeId)) {
            $where['LOGISTICS_MODEL_ID'] = $logModeId;
        }
        if($condition['OUT_AREAS']) $where['OUT_AREAS'] = array("like","%{$condition['OUT_AREAS']}%");
        if($condition['SEND_AREAS']) $where['SEND_AREAS'] = array("like","%".','."{$condition['SEND_AREAS']}".','."%");
        if(!empty($condition['STATE_CODE']) or $condition['STATE_CODE'] ==='0' ) $where['STATE_CODE'] = $condition['STATE_CODE'];
        if(!empty($condition['DENOMINATED_TYPE'] or $condition['DENOMINATED_TYPE'] ==='0')) $where['DENOMINATED_TYPE'] = $condition['DENOMINATED_TYPE'];
        //if($condition['SEND_AREAS']) $where['SEND_AREAS'] = array("like","%{$condition['SEND_AREAS']}%");
        return $where;
    }

    /**
     *获取价格区间及首重
     *@param [string] $postModelId 模板id
     *@return  array $tempData 价格区间数据
     */
    private function getPostValData($postModelId='',$tempData=array())
    {
        $valData = $tempData['postVal'];
        //区间校验
        foreach ($valData as $k => $v) {

            if (!self::isUNum($v['WEIGHT1'])) $msg = '区间1(kg)必须为数字类型';
            if (!self::isUNum($v['WEIGHT2'])) $msg = '区间2(kg)必须为数字类型';
            if (!self::isNum($v['COST'])) $msg = '固定费用（元）必须为数字类型';
            if (!self::isUNum($v['PROCESS_WEIGHT'])) $msg = '每X千克必须为数字类型';
            if (!self::isUNum($v['PROCESS_COST'])) $msg = '每X千克费用（元）必须为数字类型';
            if ((float)$v['WEIGHT1'] >= (float)$v['WEIGHT2']) $msg = '区间1(kg) 必须小于 区间2(kg)';
            if ($k > 0 && (float)$v['WEIGHT1'] < (float)$tempData['postVal'][$k - 1]['WEIGHT2']) $msg = '区间范围重复';
            if ($msg) return ['code' => 500, 'msg' => 'err', 'data' => $msg];
            $valData[$k]['POSTAGE_MODEL_ID'] = $postModelId;
            $valResData[] = $valData[$k];
        }

        return $valResData;
    }

    /**
     * 生成仓库关联数据
     * @param $tempData 基础数据  $postModelId 运费模板id
     * @return $wareParamData  仓库管理模板数据
     */
    private function setModelWare_ParamsData($tempData=array(),$postModelId='')
    {
        $wareData = explode(",", $tempData['OUT_AREAS']);
        $func = function ($v)use($postModelId){
            $wareAllParamData['P_ID1'] =  $postModelId;
            $wareAllParamData['P_ID2'] =  $v;
            $wareAllParamData['TYPE'] =  5;
            $wareAllParamData['CREATE_TIME'] =  date("Y-m-d H:i:s");
            $wareAllParamData['CREATE_USER'] =  $_SESSION['m_loginname'];
            return $wareAllParamData;
        };
        $wareParamData = array_map($func, $wareData);
        return $wareParamData;

    }
    /**
     * [getAreaData description]
     * @param  [arr] $areaData    [区域数据(含国家、地区)]
     * @param  [int] $postModelId [运费模板id]  $logModelId  物流方式id
     * @param  [string] $dealType [数据处理类型(新增/编辑)]  1.默认新增,2编辑
     * @return [arr] $areaData 组长国家数据(批量格式)        [description]
     */
    private function getAreaData($areaData=array(),$postModelId='',$logModelId='',$dealType='1')
    {
        $resAreaData = [];
        foreach ($areaData as $k => $v) {
            if (!empty($v['province'])) {	//区域数据组合
                foreach ($v['province'] as $k1 => $v1) {
                    $province['LGT_POSTAGE_ID'] = $postModelId;
                    $province['AREA_NO'] = $v1['area_no'];
                    $province['AREA_TYPE'] = $v1['area_type'];
                    $province['TYPE'] = '0';
                    $resAreaData[] = $province;
                }

            }

            $country['LGT_POSTAGE_ID'] = $postModelId;
            $country['AREA_NO'] = $v['area_no'];
            $country['AREA_TYPE'] = $v['area_type'];
            if (!empty($v['province'])) {
                $country['TYPE'] = 1;
            }else{
                $country['TYPE'] = 0;
            }
            $resAreaData[] = $country;
        }
        $resArea_checkData = $resAreaData;

        //区域重复校验
        $validatePostageIdArr = $this->field("ID")->where("LOGISTICS_MODEL_ID={$logModelId} and ID <> {$postModelId}")->select();
        //var_dump($resAreaData);die;
        $validatePostageId = array_column($validatePostageIdArr, "ID");
        if ($dealType=='2') {

            $hasOwnArea = M("lgt_postage_area","tb_")
                ->field("LGT_POSTAGE_ID,AREA_NO,AREA_TYPE,TYPE")
                ->where("LGT_POSTAGE_ID={$postModelId}")->select();
            $compact = array_column($hasOwnArea, 'AREA_NO');
            foreach ($resArea_checkData as $k => $v) {
                if ($v['AREA_TYPE']=='2') {
                    if (in_array($v['AREA_NO'], $compact)) {
                        unset($resArea_checkData[$k]);
                    }
                }
            }
        }
        $repetAreaArr = [];  //重复的城市
        foreach ($resArea_checkData as $k => $v) {
            if ($v['AREA_TYPE']!=='1') {   //去除国家
                $where['AREA_NO'] = $v['AREA_NO'];
                $where['LGT_POSTAGE_ID'] = array("in",$validatePostageId);
                //var_dump($where);die;
                if ($validateRes = M("lgt_postage_area","tb_")->where($where)->find()) {
                    $repetArea = M("ms_user_area","tb_")->where("area_no={$validateRes['AREA_NO']}")->find();
                    $repetAreaArr[]=$repetArea['zh_name'];
                }
            }
        }
        if (!empty($repetAreaArr)) {
            $data = array('code' => 501, 'msg' => 'error', 'data' => $repetAreaArr);
            return $data;
        }

        return $resAreaData;
    }
    /**
     * 验证条件判断下的必填项
     * @param  array  $tempData
     * @return $data
     */
    private function validateData($tempData=array(),$postageId='')
    {

        $POSTTAGE_DISCOUNT = floatval($tempData['POSTTAGE_DISCOUNT']);
        $PROCESS_DISCOUNT = floatval($tempData['PROCESS_DISCOUNT']);
        if ($POSTTAGE_DISCOUNT>100 or $POSTTAGE_DISCOUNT<0 or $PROCESS_DISCOUNT>100 or $PROCESS_DISCOUNT<0) {
            $data = array('code' => 500, 'msg' => 'error', 'data' => '运费、处理费折扣范围在0到100之间');
            return $data;
        }
        //必填校验
        if ($tempData['DENOMINATED_TYPE']==1) {
            if (empty($tempData['COEFFICIENT'])) {
                $data = array('code' => 500, 'msg' => 'error', 'data' => '请填写计泡系数');
                return $data;
            }
        }
        //跟进是否有最大重量判断最大重量校验
        if (!$tempData['MAX_WEIGHT_TYPE']) {
            if (empty($tempData['MAX_WEIGHT'])) {
                $data = array('code' => 500, 'msg' => 'error', 'data' => '请填写最大重量');
                return $data;
            }
        }
        if (!$tempBaseData = $this->create($tempData)) {
            $data = array('code' => 500, 'msg' => 'error', 'data' => $this->getError());
            return $data;
        }


        if (empty($postageId)) {   //新增
            $repetName = $this->where("LOGISTICS_MODEL_ID={$tempData['LOGISTICS_MODEL_ID']} AND MODEL_NM='{$tempData['MODEL_NM']}'")->find();
            if ($repetName) {
                $data = array('code' => 500, 'msg' => 'error', 'data' => '同一个物流方式内模板名字不能重复');
                return $data;
            }
            //新增的情况没有值不传null
            foreach ($tempBaseData as $k => $v) {
                if (empty($v)) {
                    unset($tempBaseData[$k]);
                }
            }
        }else{  //修改
            $nowData = $this->where("LOGISTICS_MODEL_ID={$tempData['LOGISTICS_MODEL_ID']} AND ID={$postageId}")->find();
            if ($nowData['MODEL_NM'] !== $tempData['MODEL_NM']) {
                $repetName = $this->where("LOGISTICS_MODEL_ID={$tempData['LOGISTICS_MODEL_ID']} AND MODEL_NM='{$tempData['MODEL_NM']}'")->find();
                if ($repetName) {
                    $data = array('code' => 500, 'msg' => 'error', 'data' => '同一个物流方式内模板名字不能重复');
                    return $data;
                }
            }
            foreach ($tempBaseData as $k => $v) {
                if ($k=='POSTTAGE_DISCOUNT_DATE_START' or $k=='POSTTAGE_DISCOUNT_DATE_END'
                    or $k=='PROCESS_DISCOUNT_DATE_START' or $k=='PROCESS_DISCOUNT_DATE_END'
                    or $k=='LENGTH1_START' or $k=='LENGTH1_END' or $k=='LENGTH2_START' or $k=='LENGTH2_END' or $k=='LENGTH3_MAX' or $k=='VOLUME_MAX') {
                    if (empty($v)) {
                        $tempBaseData[$k]=null;
                    }
                }

            }
        }

        return $tempBaseData;
    }
    /**
     * [getAddTempData 新增运费模板]
     * @param  [arr] $tempData [基础数据]
     * @return [int] $res  新增结果     [description]
     */
    public function getAddTempData($tempData=array())
    {
        $tempData['OUT_AREAS'] = implode(",", $tempData['OUT_AREAS']);    //触发地
        $tempData['BAN_ITEM_CAT'] = implode(",", $tempData['BAN_ITEM_CAT']);   //不支持类型
        $tempBaseData = $this->validateData($tempData);
        if ($tempBaseData['msg']=='error') {
            return $tempBaseData;
        }
        $this->startTrans();
        //新增基础信息
        $tempBaseData['UPDATE_USER'] = $_SESSION['m_loginname'];
        $tempBaseData['CREATE_USER'] = $_SESSION['m_loginname'];
        $tempBaseData['EXCEL'] = '';
        if ($tempBaseData['MAX_WEIGHT_TYPE']) $tempBaseData['MAX_WEIGHT'] = null;
        if (!$res = $this->add($tempBaseData)) {//saving
            $data = array('code' => 500, 'msg' => 'error', 'data' => $this->getDbError());
            $this->rollback();
            return $data;
        }
        #-------------------------------------------------------------------------------------\
        //新增仓库信息(params)  (模板id,)
        $supWareData = $this->setModelWare_ParamsData($tempData,$res);  //获取数据
        if (!$wareRes = M("ms_params","tb_")->addAll($supWareData)) {//saving
            $data = array('code' => 500, 'msg' => 'error', 'data' => M("ms_params","tb_")->getDbError());
            return $data;
        }
        #-------------------------------------------------------------------------------------\
        //新增价价格区间	//校验价格区间结果返回错误
        $postageVal = $this->getPostValData($res,$tempData);
        $tempBaseVal['POSTTAGE_VAL'] = json_encode($postageVal,true);

        $editValres = $this->where('ID='.$res)->save($tempBaseVal);   //添加价格code值(json)saving
        if ($postageVal['msg']) {
            $this->rollback();
            return $postageVal;
        }
        if (!$valRes = M("lgt_postage_val","tb_")->addAll($postageVal)) {//saving
            $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_postage_val","tb_")->getDbError());
            $this->rollback();
            return $data;
        }
        #--------------------------------------------------------------------------------------------\
        //获取区域数据、新增支持目的地区域  传入模板id
        $areaData = $this->getAreaData($tempData['AlreadyChose'],$res,$tempData['LOGISTICS_MODEL_ID']);
        if ($areaData['msg']) {
            $this->rollback();
            return $areaData;
        }

        if (!empty($areaData)) {
            if (!$areaRes = M("lgt_postage_area","tb_")->addAll($areaData)) { //saving
                $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_postage_area","tb_")->getDbError());
                $this->rollback();
                return $data;
            }
        }
        $SEND_AREAS_PART = [];
        foreach ($areaData as $k => $v) {	       //删除非国家code
            if ($v['AREA_TYPE']!=='1')  {
                $SEND_AREAS_PART[] = $v['AREA_NO'];
                unset($areaData[$k]);
            }
        }
        $areaEditData['SEND_AREAS'] = implode(",",array_column($areaData, 'AREA_NO'));
        $areaEditData['SEND_AREAS_PART'] = implode(",",$SEND_AREAS_PART);

        if (empty($areaEditData['SEND_AREAS'])) {  //目的地必填验证
            $data = array('code' => 500, 'msg' => 'error', 'data' => '请选择目的地');
            $this->rollback();
            return $data;
        }
        $areaEditData['SEND_AREAS'] = ','.$areaEditData['SEND_AREAS'].',';
        $editareaRes = $this->where('ID='.$res)->save($areaEditData);   //添加国家信息,逗号分割saving
        //---日志记录
        $logData = $this->getLogData($tempData['LOGISTICS_MODEL_ID']);
        if (!$reslog = M("lgt_log","tb_")->add($logData)) {
            $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_log","tb_")->getDbError());
            $this->rollback();
            return $data;
        }
        $this->commit();
        #----------------------------------------------------------------------------------------------\
        //物流方式增加模板id,补充支持仓库信息
        $ware_postage_res = $this->updateWarehouseAndPostageId($tempData['LOGISTICS_MODEL_ID'], $res);
        return $res.','.$tempData['LOGISTICS_MODEL_ID'];
    }

    private function updateWarehouseAndPostageId($modeId, $postage_id)
    {
        //更新logistics_mode的ware_house
        $warehouses = M('lgt_postage_model', 'tb_')->alias('lpm')
            ->field('lpm.OUT_AREAS as ware,lpm.id')
            ->where(['lpm.LOGISTICS_MODEL_ID' => $modeId, 'lpm.STATE_CODE' => 0])
            ->select();
        $warestr = '';
        $postage_ids = [$postage_id];
        foreach ($warehouses as $v) {
            $postage_ids[] = $v['id'];
            if ($v['ware']) {
                $warestr .= ',' . $v['ware'];
            }
        }
        $warestrarr = array_unique(explode(",", $warestr));
        unset($warestrarr[0]);
        $ware_postage_data['WARE_HOUSE'] = implode(",", $warestrarr);
        $ware_postage_data['POSTAGE_ID'] = implode(",", array_unique($postage_ids));
        $ware_postage_res = D("Logistics/LogisticsMode")->where("ID={$modeId}")->save($ware_postage_data);
        return $ware_postage_res;
    }

    private function updateLMWarehouse(array $logistics_model_ids)
    {
        $lm_list = D("Logistics/LogisticsMode")->field("ID,POSTAGE_ID")->where(['ID' => ['in', $logistics_model_ids]])->select();
        foreach ($lm_list as $v) {
            $warehouses = $this->field("OUT_AREAS")->where("find_in_set(id,\"{$v['POSTAGE_ID']}\")")->find();
        }
    }

    /**
     * 编辑运费模板
     * @param  array  $tempData
     * @return $res
     */
    public function getEditTempData($tempData=array(),$postageId='')
    {
        $tempData['OUT_AREAS'] = implode(",", $tempData['OUT_AREAS']);    //触发地
        $tempData['BAN_ITEM_CAT'] = implode(",", $tempData['BAN_ITEM_CAT']);   //不支持类型
        $tempBaseData = $this->validateData($tempData,$postageId);
        $tempBaseData['UPDATE_USER'] = $_SESSION['m_loginname'];
        $tempBaseData['EXCEL'] = '';
        if ($tempBaseData['MAX_WEIGHT_TYPE']) $tempBaseData['MAX_WEIGHT'] = null;
        if ($tempBaseData['msg']=='error') {
            return $tempBaseData;
        }
        $this->startTrans();
        //修改基础信息
        if (!$res = $this->where("ID={$postageId}")->save($tempBaseData)) {
            $data = array('code' => 500, 'msg' => 'error', 'data' => $this->getDbError());
            $this->rollback();
            return $data;
        }
        #-------------------------------------------------------------------------------------
        //修改仓库信息(params)  (模板id,)
        $supWareData = $this->setModelWare_ParamsData($tempData,$postageId);  //获取数据

        $areaDelRes = M("ms_params","tb_")->where("P_ID1={$postageId} AND TYPE=5")->delete();  //先删后存

        if (!$wareRes = M("ms_params","tb_")->addAll($supWareData) or !$areaDelRes) {

            $data = array('code' => 500, 'msg' => 'error', 'data' => M("ms_params","tb_")->getDbError());
            return $data;
        }
        #-------------------------------------------------------------------------------------
        //修改价价格区间	//校验价格区间结果返回错误
        $postageVal = $this->getPostValData($postageId,$tempData);

        $tempBaseVal['POSTTAGE_VAL'] = json_encode($postageVal,true);

        $editValres = $this->where('ID='.$postageId)->save($tempBaseVal);   //添加价格code值(json)
        if ($postageVal['msg']) {
            $this->rollback();
            return $postageVal;
        }
        $valDelRes = M("lgt_postage_val","tb_")->where("POSTAGE_MODEL_ID={$postageId}")->delete();
        //echo M("lgt_postage_val","tb_")->_sql();die;

        if (!$valRes = M("lgt_postage_val","tb_")->addAll($postageVal) or !$valDelRes) {

            $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_postage_val","tb_")->getDbError());

            $this->rollback();
            return $data;
        }
        #--------------------------------------------------------------------------------------------
        //获取区域数据、新增支持目的地区域  传入模板id
        $areaData = $this->getAreaData($tempData['AlreadyChose'],$postageId,$tempData['LOGISTICS_MODEL_ID'],'2');
        if ($areaData['msg']) {
            $this->rollback();
            return $areaData;
        }
        if (!empty($areaData)) {
            $valDelRes = M("lgt_postage_area","tb_")->where("LGT_POSTAGE_ID={$postageId}")->delete();
            if (!$areaRes = M("lgt_postage_area","tb_")->addAll($areaData)) {
                $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_postage_area","tb_")->getDbError());
                $this->rollback();
                return $data;
            }
        }
        $SEND_AREAS_PART = [];
        foreach ($areaData as $k => $v) {	       //删除非国家code
            if ($v['AREA_TYPE']!=='1')  {
                $SEND_AREAS_PART[] = $v['AREA_NO'];
                unset($areaData[$k]);
            }
        }
        $areaEditData['SEND_AREAS'] = implode(",",array_column($areaData, 'AREA_NO'));
        $areaEditData['SEND_AREAS_PART'] = implode(",",$SEND_AREAS_PART);

        if (empty($areaEditData['SEND_AREAS'])) {  //目的地必填验证
            $data = array('code' => 500, 'msg' => 'error', 'data' => '请选择目的地');
            $this->rollback();
            return $data;
        }
        $areaEditData['SEND_AREAS'] = ','.$areaEditData['SEND_AREAS'].',';
        $editareaRes = $this->where('ID='.$postageId)->save($areaEditData);   //添加国家信息,逗号分割
        $logData = $this->getLogData($tempData['LOGISTICS_MODEL_ID'],'2');
        if (!$res = M("lgt_log","tb_")->add($logData)) {
            $data = array('code' => 500, 'msg' => 'error', 'data' => M("lgt_log","tb_")->getDbError());
            $this->rollback();
            return $data;
        }
        $this->commit();
        $ware_postage_res = $this->updateWarehouseAndPostageId($tempData['LOGISTICS_MODEL_ID'], $postageId);
        return $postageId.','.$tempData['LOGISTICS_MODEL_ID'];


    }

    public function getLogData($logId,$type='1')
    {
        $logData['LOG_MODEL_ID'] = $logId;
        if ($type=='2') {
            $logData['LOG_MSG'] = '修改运费模板';
        }else{
            $logData['LOG_MSG'] = '创建运费模板';
        }

        $logData['TYPE'] = 1;
        $logData['CREATE_TIME'] = date("Y-m-d H:i:s",time());
        $logData['CREATE_USER'] = $_SESSION['m_loginname'];
        return $logData;

    }

    /**
     * [getdetailData description]
     * @param  int $id  运费模板id
     * @return [description]
     */
    public function getModel_DetailData($id)
    {
        $DictionaryModel = D("Universal/Dictionary");   //实例化统一模型
        $detail_baseData = $this->where('ID='.$id)->find();

        //format time
        $detail_baseData['POSTTAGE_DISCOUNT_DATE_START'] = cutting_time($detail_baseData['POSTTAGE_DISCOUNT_DATE_START']);
        $detail_baseData['POSTTAGE_DISCOUNT_DATE_END'] = cutting_time($detail_baseData['POSTTAGE_DISCOUNT_DATE_END']);
        $detail_baseData['PROCESS_DISCOUNT_DATE_START'] = cutting_time($detail_baseData['PROCESS_DISCOUNT_DATE_START']);
        $detail_baseData['PROCESS_DISCOUNT_DATE_END'] = cutting_time($detail_baseData['PROCESS_DISCOUNT_DATE_END']);

        $OUT_AREAS = explode(",", $detail_baseData['OUT_AREAS']);

        $func = function ($v)use($DictionaryModel){				//目的地
            $simpleData = $DictionaryModel->getDictionaryByCd($v);
            return $simpleData[$v]['CD_VAL'];
        };
        $detail_baseData['OUT_AREAS_DATA'] = implode(",", array_map($func, $OUT_AREAS));

        $detail_valData = M("lgt_postage_val","tb_")
            ->field("WEIGHT1,TYPE,COST,PROCESS_COST")
            ->where("POSTAGE_MODEL_ID={$id}")
            ->select();

        if ($detail_baseData['FIRST_HEAVY_TYPE']=='1') { 			//价格区间
            $detail_baseData['interval'] = $detail_valData;
        }else{
            $detail_baseData['first_heavy'] = $detail_valData[0];
            unset($detail_valData[0]);
            $detail_baseData['interval'] = $detail_valData;
        }
        $detail_baseData['BAN_ITEM_CAT'] = explode(",", $detail_baseData['BAN_ITEM_CAT']);   //限制条件
        $SEND_AREAS = M("lgt_postage_area","tb_")->where("LGT_POSTAGE_ID={$id}")->select();
        foreach ($SEND_AREAS as $k => $v) {
            $areaData = M("ms_user_area","tb_")->where("area_no='{$v['AREA_NO']}'")->find();
            $SEND_AREAS_DETAIL[] = $areaData;
        }

        $detail_baseData['SEND_AREAS'] = $SEND_AREAS_DETAIL;
        foreach ($detail_baseData['BAN_ITEM_CAT'] as $k => $v) {
            $simpleData1 = M("ms_cmn_cd",'tb_')->where('CD='."'".$v."'")->find();
            $detail_baseData['BAN_ITEM_CAT'][$k]= $simpleData1['CD_VAL'];
        }
        $detail_baseData['now_time'] = date("Y-m-d",time());
        $detail_baseData['POSTTAGE_VAL'] = json_decode($detail_baseData['POSTTAGE_VAL'], true);
        return $detail_baseData;
    }

    /**
     * [getdetailData description]
     * @param  int $where  where条件
     * @return [description]
     */
    public function getModel_DetailDataList($where)
    {
        $detail_baseData = $this
            ->field('tb_lgt_postage_model.ID,tb_lgt_postage_model.MODEL_NM,tb_lgt_postage_model.STATE_CODE,tb_lgt_postage_model.OUT_AREAS,
            tb_lgt_postage_model.DAY1,tb_lgt_postage_model.DAY2,tb_lgt_postage_model.DENOMINATED_TYPE,tb_lgt_postage_model.COEFFICIENT,tb_lgt_postage_model.MAX_WEIGHT,
            tb_lgt_postage_model.POSTTAGE_DISCOUNT,tb_lgt_postage_model.POSTTAGE_DISCOUNT_DATE_START,tb_lgt_postage_model.POSTTAGE_DISCOUNT_DATE_END,tb_lgt_postage_model.PROCESS_DISCOUNT,
            tb_lgt_postage_model.PROCESS_DISCOUNT_DATE_START,tb_lgt_postage_model.PROCESS_DISCOUNT_DATE_END,tb_lgt_postage_model.BAN_ITEM_CAT,tb_lgt_postage_model.POSTTAGE_VAL,
            tb_lgt_postage_model.LENGTH1_START,tb_lgt_postage_model.LENGTH1_END,tb_lgt_postage_model.LENGTH2_START,tb_lgt_postage_model.LENGTH2_END,
            tb_lgt_postage_model.LENGTH3_MAX,tb_lgt_postage_model.VOLUME_MAX,tb_ms_logistics_mode.LOGISTICS_MODE,tb_ms_cmn_cd.CD_VAL logCompany')
            ->join('left join tb_ms_logistics_mode on tb_ms_logistics_mode.ID = tb_lgt_postage_model.LOGISTICS_MODEL_ID')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_ms_logistics_mode.LOGISTICS_CODE')
            ->where($where)
            ->order('tb_ms_logistics_mode.ID desc')
            ->select();
        $ids = array_column($detail_baseData, 'ID');
        $data = [];
        $SEND_AREAS_DETAIL = M("lgt_postage_area","tb_")->field('tb_lgt_postage_area.LGT_POSTAGE_ID,tb_ms_user_area.zh_name,tb_ms_user_area.area_type')
            ->join('left join tb_ms_user_area on tb_ms_user_area.area_no = tb_lgt_postage_area.AREA_NO')
            ->select();
        $tem = [];
        foreach ($SEND_AREAS_DETAIL as $key => $item) {
            if ($item['area_type'] == '1') {
                if (isset($tem[$item['LGT_POSTAGE_ID']]['country'])) {
                    $tem[$item['LGT_POSTAGE_ID']]['country'] .= ',' . $item['zh_name'];
                } else {
                    $tem[$item['LGT_POSTAGE_ID']]['country'] = $item['zh_name'];
                }
                continue;
            }
            if (isset($tem[$item['LGT_POSTAGE_ID']]['area'])) {
                $tem[$item['LGT_POSTAGE_ID']]['area'] .= ',' . $item['zh_name'];
            } else {
                $tem[$item['LGT_POSTAGE_ID']]['area'] = $item['zh_name'];
            }
        }
        $SEND_AREAS_DETAIL_LIST = $tem;
        $cd_lists = $this->getCdListByCds();
        $cds = array_column($cd_lists, 'CD');
        $cd_values = array_column($cd_lists, 'CD_VAL');
        $detail_baseData = DataModel::toYield($detail_baseData);
        foreach ($detail_baseData as $key => $item) {
            $d = $item;
            $d['POSTTAGE_DISCOUNT_DATE_START'] = cutting_time($item['POSTTAGE_DISCOUNT_DATE_START']);
            $d['POSTTAGE_DISCOUNT_DATE_END'] = cutting_time($item['POSTTAGE_DISCOUNT_DATE_END']);
            $d['PROCESS_DISCOUNT_DATE_START'] = cutting_time($item['PROCESS_DISCOUNT_DATE_START']);
            $d['PROCESS_DISCOUNT_DATE_END'] = cutting_time($item['PROCESS_DISCOUNT_DATE_END']);
            $d['OUT_AREAS_DATA'] = str_replace($cds, $cd_values, $item['OUT_AREAS']);//目的地
            $SEND_AREAS_DETAIL = $SEND_AREAS_DETAIL_LIST[$item['ID']];
            unset($SEND_AREAS_DETAIL_LIST[$item['ID']]);
            $d['BAN_ITEM_CAT'] = str_replace($cds, $cd_values, $item['BAN_ITEM_CAT']);//目的地
            $d['STATE_CODE'] = $item['STATE_CODE'] == '0' ? '启用' : '未启用';
            $d['DENOMINATED_TYPE'] = $item['DENOMINATED_TYPE'] == '0' ? '仅计重' : '计泡';
            $d['COEFFICIENT'] = $item['DENOMINATED_TYPE'] == '1' ? $item['COEFFICIENT'] : '无';
            $d['MAX_WEIGHT'] = $item['MAX_WEIGHT_TYPE'] == '1' ? '无限制' : $item['MAX_WEIGHT'];
            if (empty($item['POSTTAGE_DISCOUNT_DATE_START'])) {
                $d['POSTTAGE_DISCOUNT_DATE_START'] = '任何时间';
                $d['POSTTAGE_DISCOUNT_DATE_END'] = '';
            }
            if (empty($item['POSTTAGE_DISCOUNT_DATE_END'])) {
                $d['POSTTAGE_DISCOUNT_DATE_START'] = '';
                $d['POSTTAGE_DISCOUNT_DATE_END'] = '永久';
            }
            if (empty($item['POSTTAGE_DISCOUNT_DATE_START']) && empty($item['POSTTAGE_DISCOUNT_DATE_END'])) {
                $d['POSTTAGE_DISCOUNT_DATE_START'] = '永久';
                $d['POSTTAGE_DISCOUNT_DATE_END'] = '';
            }
            if (empty($item['PROCESS_DISCOUNT_DATE_START'])) {
                $d['PROCESS_DISCOUNT_DATE_START'] = '任何时间';
                $d['PROCESS_DISCOUNT_DATE_END'] = '';
            }
            if (empty($item['PROCESS_DISCOUNT_DATE_END'])) {
                $d['PROCESS_DISCOUNT_DATE_START'] = '';
                $d['PROCESS_DISCOUNT_DATE_END'] = '永久';
            }
            if (empty($item['PROCESS_DISCOUNT_DATE_START']) && empty($item['PROCESS_DISCOUNT_DATE_END'])) {
                $d['PROCESS_DISCOUNT_DATE_START'] = '永久';
                $d['PROCESS_DISCOUNT_DATE_END'] = '';
            }
            if (empty($item['LENGTH1_START'])) {
                $d['LENGTH1_START'] = 0;
            }
            if (empty($item['LENGTH1_END'])) {
                $d['LENGTH1_END'] = 0;
            }
            if (empty($item['LENGTH2_START'])) {
                $d['LENGTH2_START'] = 0;
            }
            if (empty($item['LENGTH2_END'])) {
                $d['LENGTH2_END'] = 0;
            }
            if ((empty($item['LENGTH1_START']) || $item['LENGTH1_START'] == 0) && (empty($item['LENGTH1_END']) || $item['LENGTH1_END'] == 0)) {
                $d['LENGTH1_START'] = '无限制';
                $d['LENGTH1_END'] = '';
            }
            if ((empty($item['LENGTH2_START']) || $item['LENGTH2_START'] == 0) && (empty($item['LENGTH2_END']) || $item['LENGTH2_END'] == 0)) {
                $d['LENGTH2_START'] = '无限制';
                $d['LENGTH2_END'] = '';
            }
            $d['LENGTH3_MAX'] = empty($item['LENGTH3_MAX']) || $item['LENGTH3_MAX'] == 0 ? '无限制' : $item['LENGTH3_MAX'];
            $d['VOLUME_MAX'] = empty($item['VOLUME_MAX']) || $item['VOLUME_MAX'] == 0 ? '无限制' : $item['VOLUME_MAX'];
            $POSTAGE_VAL = json_decode($item['POSTTAGE_VAL'], true);
            foreach ($POSTAGE_VAL as $index => $val) {
                $list = $d;
                $list['WEIGHT1'] = $val['WEIGHT1'];
                $list['WEIGHT2'] = $val['WEIGHT2'];
                $list['COST'] = $val['COST'];
                $list['PROCESS_WEIGHT'] = $val['PROCESS_WEIGHT'];
                $list['PROCESS_COST'] = $val['PROCESS_COST'];
                $list['POSTAGE_MODEL_ID'] = $val['POSTAGE_MODEL_ID'];
                $list['country'] = $SEND_AREAS_DETAIL['country'];
                $list['area'] = $SEND_AREAS_DETAIL['area'];
                $data[] = $list;
            }
            unset($d);
        }

        return $data;
    }

    //获取CD列表 默认不支持类型、仓库
    public function getCdListByCds($cdArr = ['N00192', 'N00068'])
    {
        if (empty($cdArr)) return [];
        $list = [];
        foreach ($cdArr as $value) {
            $list[] = ' CD like "%' . $value . '%" ';
        }
        $where_str = implode('OR', $list);
        $cdData = M("ms_cmn_cd",'tb_')->where($where_str)->select();
        return $cdData;
    }

    public function saveStatus($modelIDArr)
    {
        $data['STATE_CODE'] = 1;
        foreach ($modelIDArr as  $v) {
            $editRes = $this->where('ID='.$v)->save($data);
        }
        return $editRes;
    }

    public function import($queryParams)
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        ini_set('date.timezone', 'Asia/Shanghai');
        $filePath = $_FILES['file']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        $key = 'A';
        foreach ($this->importMap as $v) {
            $excel_key[$key++] = $v;
        }
        $activeSheet = $PHPExcel->getActiveSheet();
        $getV = function ($k) use ($activeSheet) {
            return ZUtils::mbtrim($activeSheet->getCell($k)->getValue());
        };
        $importData = [];
        for ($row = 2; $row <= $allRow; $row++) {
            if (!$getV('A' . $row) && !$getV('B' . $row) && !$getV('C' . $row)) {
                $allRow = $row - 1;
                break;
            }
            $hashkey = md5($getV('C' . $row));
            $range = [];
            foreach ($excel_key as $k => $v) {
                if (in_array($v, ['WEIGHT1', 'WEIGHT2', 'COST', 'PROCESS_WEIGHT', 'PROCESS_COST'])) {
                    $range[$v] = $getV($k . $row);
                } else {
                    $importData[$hashkey][$v] = $getV($k . $row);
                }
            }
            $importData[$hashkey]['__row'] = $range['__row'] = $row;
            $importData[$hashkey]['__range'][] = $range;
        }
        unset($activeSheet);unset($PHPExcel);
        $this->excel_name = $_SESSION['m_loginname'] . date('_YmdHis_') . mt_rand(0, 10000) . '.xlsx';
        $import_err = $this->validateAndFill($importData);
        if ($import_err['error'] == 0) {
            FileModel::copyExcel2($filePath, $this->excel_name);
            $import_err = $this->saveImportLgt($importData);
        }
        $import_err['total'] = $allRow - 1;
        $import_err['success'] = $import_err['total'] - $import_err['error'];
        $import_err['data'] = array_values($import_err['data']);
        return $import_err;
    }
    private function validateAndFill(&$importData)
    {
        //物流公司列表
        $logCompanies = M()->table('tb_ms_cmn_cd')
            ->where(['CD' => ['like', '%N00070%'], 'USE_YN' => 'Y'])
            ->getField('CD,CD_VAL');
        //物流方式列表
        $logModes = M()->table('tb_ms_logistics_mode')
            ->where(['IS_ENABLE' => 1, 'IS_DELETE' => 0])
            ->getField('ID,LOGISTICS_MODE');
        //物流公司+方式map
        $logCompanyModes = M()->table('tb_ms_logistics_mode')
            ->where(['IS_ENABLE' => 1, 'IS_DELETE' => 0])
            ->getField('ID,LOGISTICS_CODE,LOGISTICS_MODE');
        //仓库列表
        $warehouses = M()->table('tb_ms_cmn_cd')
            ->where(['CD' => ['like', '%N00068%'], 'USE_YN' => 'Y'])
            ->getField('CD,CD_VAL');
        //特性列表
        $features = M()->table('tb_ms_cmn_cd')
            ->where(['CD' => ['like', '%N00192%'], 'USE_YN' => 'Y'])
            ->getField('CD,CD_VAL');
        $country = M()->table('tb_ms_user_area')
            ->where(['area_type' => 1])
            ->getField('area_no,zh_name');
        $states = [0 => '已启用', 1 => '已停用'];
        $denominated_types = [0 => '仅重量', 1 => '计泡'];
        $count = 0;
        $err = [];
        $now = date('Y-m-d H:i:s');
        $user = $_SESSION['m_loginname'];
        $user_id = $_SESSION['userId'];
//        ValidatorModel::validate($rule, $importData);
        $logistics_model_ids = [];
        foreach ($importData as &$v) {
            $flg = true;
            if (!$lc_cd = array_search($v['logCompany'], $logCompanies)) {
                $err[$v['__row']]['err'][] = '物流公司为空，不存在或未启用';
            }
            if (!$lm_id = array_search($v['LOGISTICS_MODE'], $logModes)) {
                $err[$v['__row']]['err'][] = '物流方式为空，不存在或未启用';
            } elseif ($logCompanyModes[$lm_id]['LOGISTICS_CODE'] != $lc_cd) {
                $msg = '当前物流公司不包含该物流方式';
                //物流方式属于多个公司
                foreach (array_keys($logModes, $v['LOGISTICS_MODE']) as $kk) {
                    if ($logCompanyModes[$kk]['LOGISTICS_CODE'] == $lc_cd) {
                        $lm_id = $kk;
                        $msg = '';
                    }
                }
                if ($msg) $err[$v['__row']]['err'][] = $msg;
            }
            $logistics_model_ids[] = $v['LOGISTICS_MODEL_ID'] = $lm_id;
            if (!$v['MODEL_NM']) $err[$v['__row']]['err'][] = '运费模板名称必填';
            if (($status = array_search($v['STATE_CODE'], $states)) === false) $err[$v['__row']]['err'][] = '状态填写错误，选填：已启用，已停用';
            $v['STATE_CODE'] = $status;
            if (!$v['OUT_AREAS']) {
                $err[$v['__row']]['err'][] = '出发仓库必填';
            } else {
                $warehouse = [];
                foreach (explode(',', $v['OUT_AREAS']) as $vv) {
                    if (!$w_cd = array_search($vv, $warehouses)) {
                        $err[$v['__row']]['err'][] = '出发仓库不存在，多个仓库请使用英文逗号（,）分隔：' . $vv;
                        break;
                    }
                    $warehouse[] = $w_cd;
                }
                $v['OUT_AREAS'] = implode(',', array_unique($warehouse));
            }
            if (!self::isInt($v['DAY1'])) $err[$v['__row']]['err'][] = '时效1必填且为整数类型';
            if (!self::isInt($v['DAY2'])) $err[$v['__row']]['err'][] = '时效2必填且为整数类型';
            if ((float)$v['DAY1'] >= (float)$v['DAY2']) $err[$v['__row']]['err'][] = '时效2必须大于时效1';
            if (($d_t = array_search($v['DENOMINATED_TYPE'], $denominated_types)) === false) $err[$v['__row']]['err'][] = '计价方式填写错误，选填：仅重量，计泡';
            $v['DENOMINATED_TYPE'] = $d_t;
            if ($d_t) {
                if (!self::isUNum($v['COEFFICIENT'])) $err[$v['__row']]['err'][] = '计泡系数不能为空且必须为数字类型';
            } else {
                $v['COEFFICIENT'] = null;
            }
            if ($v['MAX_WEIGHT']) {
                if (!self::isUNum($v['MAX_WEIGHT'])) {
                    $err[$v['__row']]['err'][] = '最大重量必须为数字类型';
                }
                $v['MAX_WEIGHT_TYPE'] = 0;
            } else {
                $v['MAX_WEIGHT_TYPE'] = 1;
                $v['MAX_WEIGHT'] = null;
            }
            if ($v['POSTTAGE_DISCOUNT']) {
                if (!self::isUNum($v['POSTTAGE_DISCOUNT'])) {
                    $err[$v['__row']]['err'][] = '运费折扣必须为数字类型，且不带百分号';
                } elseif ($v['POSTTAGE_DISCOUNT'] > 100) {
                    $err[$v['__row']]['err'][] = '运费折扣必须小于100';
                }
            } else {
                $v['POSTTAGE_DISCOUNT'] = 100;
                $v['POSTTAGE_DISCOUNT_DATE_START'] = null;
                $v['POSTTAGE_DISCOUNT_DATE_END'] = null;
            }

            if ($v['POSTTAGE_DISCOUNT_DATE_START']) {
                if (!DateModel::validateDate($v['POSTTAGE_DISCOUNT_DATE_START'])) $err[$v['__row']]['err'][] = '运费折扣有效期1格式错误';
                if ($v['POSTTAGE_DISCOUNT_DATE_END'] <= $v['POSTTAGE_DISCOUNT_DATE_START']) $err[$v['__row']]['err'][] = '运费折扣有效期2必须大于有效期1';
            } else {
                $v['POSTTAGE_DISCOUNT_DATE_START'] = null;
            }
            if ($v['POSTTAGE_DISCOUNT_DATE_END']) {
                if (!DateModel::validateDate($v['POSTTAGE_DISCOUNT_DATE_END'])) $err[$v['__row']]['err'][] = '运费折扣有效期2格式错误';
                if (!$v['POSTTAGE_DISCOUNT_DATE_START']) $err[$v['__row']]['err'][] = '运费折扣有效期1必填';
            } else {
                $v['POSTTAGE_DISCOUNT_DATE_END'] = null;
            }
            if ($v['PROCESS_DISCOUNT']) {
                if (!self::isUNum($v['PROCESS_DISCOUNT'])) {
                    $err[$v['__row']]['err'][] = '处理费折扣必须为数字类型，且不带百分号';
                } elseif ($v['PROCESS_DISCOUNT'] > 100) {
                    $err[$v['__row']]['err'][] = '处理费折扣必须小于100';
                }
            } else {
                $v['PROCESS_DISCOUNT'] = 100;
                $v['PROCESS_DISCOUNT_DATE_START'] = null;
                $v['PROCESS_DISCOUNT_DATE_END'] = null;
            }

            if ($v['PROCESS_DISCOUNT_DATE_START']) {
                if (!DateModel::validateDate($v['PROCESS_DISCOUNT_DATE_START'])) $err[$v['__row']]['err'][] = '处理费折扣有效期1格式错误';
                if ($v['PROCESS_DISCOUNT_DATE_END'] <= $v['PROCESS_DISCOUNT_DATE_START']) $err[$v['__row']]['err'][] = '处理费折扣有效期2必须大于有效期1';
            } else {
                $v['PROCESS_DISCOUNT_DATE_START'] = null;
            }
            if ($v['PROCESS_DISCOUNT_DATE_END']) {
                if (!DateModel::validateDate($v['PROCESS_DISCOUNT_DATE_END'])) $err[$v['__row']]['err'][] = '处理费折扣有效期2格式错误';
                if (!$v['PROCESS_DISCOUNT_DATE_START']) $err[$v['__row']]['err'][] = '处理费折扣有效期1必填';
            } else {
                $v['PROCESS_DISCOUNT_DATE_END'] = null;
            }
            foreach ($v['__range'] as $kk => &$vv) {
                if (!self::isUNum($vv['WEIGHT1'])) $err[$vv['__row']]['err'][] = '区间1(kg)必须为数字类型';
                if (!self::isUNum($vv['WEIGHT2'])) $err[$vv['__row']]['err'][] = '区间2(kg)必须为数字类型';
                if (!self::isNum($vv['COST'])) $err[$vv['__row']]['err'][] = '固定费用（元）必须为数字类型';
                if (!self::isUNum($vv['PROCESS_WEIGHT'])) $err[$vv['__row']]['err'][] = '每X千克必须为数字类型';
                if (!self::isUNum($vv['PROCESS_COST'])) $err[$vv['__row']]['err'][] = '每X千克费用（元）必须为数字类型';
                if ((float)$vv['WEIGHT1'] >= (float)$vv['WEIGHT2']) $err[$vv['__row']]['err'][] = '区间1(kg) 必须小于 区间2(kg)';
                if ($kk > 0 && (float)$vv['WEIGHT1'] < (float)$v['__range'][$kk - 1]['WEIGHT2']) $err[$vv['__row']]['err'][] = '区间范围重复';
                if (!isset($v['__range'][$kk + 1]) && !$v['MAX_WEIGHT_TYPE'] && $vv['WEIGHT2'] != $v['MAX_WEIGHT']) {
                    $err[$vv['__row']]['err'][] = '最后一个 区间2(kg) 的值必须等于最大重量';
                }
                if ($err[$vv['__row']]['err']) {
                    $err[$vv['__row']]['row'] = $vv['__row'];
                    $err[$vv['__row']]['name'] = $v['MODEL_NM'];
                    $flg = false;
                }
                unset($vv['__row']);
            }
            unset($vv);
            if ($v['BAN_ITEM_CAT']) {
                $b_items = [];
                foreach (explode(',', $v['BAN_ITEM_CAT']) as $vv) {
                    if (!$b_cd = array_search($vv, $features)) {
                        $err[$v['__row']]['err'][] = '不支持类型（CM）不存在，多个类型请使用英文逗号（,）分隔：' . $vv;
                        break;
                    }
                    $b_items[] = $b_cd;
                }
                $v['BAN_ITEM_CAT'] = implode(',', array_unique($b_items));
            }
            if ($v['LENGTH1_START']) {
                if (!self::isUNum($v['LENGTH1_START'])) $err[$v['__row']]['err'][] = '最长边开始 必须为数字类型';
                if (!$v['LENGTH1_END']) $err[$v['__row']]['err'][] = '最长边结束 不能为空';
            } else {
                $v['LENGTH1_START'] = 0;
            }
            if ($v['LENGTH1_END']) {
                if (!self::isUNum($v['LENGTH1_END'])) $err[$v['__row']]['err'][] = '最长边结束 必须为数字类型';
                if ((float)$v['LENGTH1_END'] <= (float)$v['LENGTH1_START']) $err[$v['__row']]['err'][] = '最长边结束 必须大于 最长边开始';
            }
            if ($v['LENGTH2_START']) {
                if (!self::isUNum($v['LENGTH2_START'])) $err[$v['__row']]['err'][] = '第二长边开始 必须为数字类型';
                if (!$v['LENGTH2_END']) $err[$v['__row']]['err'][] = '第二长边结束 不能为空';
            } else {
                $v['LENGTH2_START'] = 0;
            }
            if ($v['LENGTH2_END']) {
                if (!self::isUNum($v['LENGTH2_END'])) $err[$v['__row']]['err'][] = '第二长边结束 必须为数字类型';
                if ((float)$v['LENGTH2_END'] <= (float)$v['LENGTH2_START']) $err[$v['__row']]['err'][] = '第二长边结束 必须大于 第二长边开始';
            }
            if ($v['LENGTH3_MAX'] && !self::isUNum($v['LENGTH3_MAX'])) $err[$v['__row']]['err'][] = '长宽高之和(≦) 必须为数字类型';
            if ($v['VOLUME_MAX'] && !self::isUNum($v['VOLUME_MAX'])) $err[$v['__row']]['err'][] = '体积(≦) 必须为数字类型';
            //SEND_COUNTRY
            //SEND_AREAS
            $c_a = [];
            $existed_areas = M()->table('tb_lgt_postage_model pm,tb_lgt_postage_area ar')
                ->where(['_string' => 'pm.ID=ar.LGT_POSTAGE_ID', 'ar.TYPE' => 0, 'pm.LOGISTICS_MODEL_ID' => $v['LOGISTICS_MODEL_ID'], 'pm.MODEL_NM' => ['neq', $v['MODEL_NM']], 'pm.STATE_CODE' => 0])
                ->getField('ar.id,ar.AREA_NO');
            $country_ids = [];
            if (!$v['SEND_AREAS']) {
                $err[$v['__row']]['err'][] = '支持国家必填';
            } else {
                foreach (explode(',', $v['SEND_AREAS']) as $vv) {
                    if (!$c_no = array_search($vv, $country)) {
                        $err[$v['__row']]['err'][] = '支持国家不存在，多个国家请使用英文逗号（,）分隔：'. $vv;
                        break;
                    }
                    $c_a[$c_no] = [
                        'AREA_NM' => $vv,
                        'AREA_NO' => $c_no,
                        'AREA_TYPE' => 1,
                        'TYPE' => 0,
                    ];
                }
                $country_ids = array_keys($c_a);
                $v['SEND_AREAS'] = ',' . implode(',', $country_ids) . ',';
            }
            if (!$v['SEND_AREAS_PART']) {
//                $err[$v['__row']]['err'][] = '支持国家必填';
            } else {
                $areas = explode(',', $v['SEND_AREAS_PART']);
                $areas_info_list = M('ms_user_area', 'tb_')
                    ->where(['parent_no' => ['in', $country_ids], 'zh_name' => ['in', $areas]])
                    ->getField('area_no,zh_name,area_type,parent_no');
                if (count($areas_info_list) !== count($areas)) {
                    $err[$v['__row']]['err'][] = '支持区域不存在或不属于支持国家，多个区域请使用英文逗号（,）';
                } else {
                    $SEND_AREAS_PART = [];
                    foreach ($areas_info_list as $kk => $vv) {
                        $c_a[$kk] = [
                            'AREA_NM' => $vv['zh_name'],
                            'AREA_NO' => $kk,
                            'AREA_TYPE' => 2,
                            'TYPE' => 0,
                        ];
                        $SEND_AREAS_PART[] = $kk;
                        $c_a[$vv['parent_no']]['TYPE'] = 1;
                    }
                    $v['SEND_AREAS_PART'] = implode(',', $SEND_AREAS_PART);
                }
            }
            foreach ($c_a as $kk => $vv) {
                if ($vv['AREA_TYPE'] === 2 && in_array($kk, $existed_areas)) {
                    $area_type = $vv['AREA_TYPE'] == 1 ? '国家' : '区域';
                    $err[$v['__row']]['err'][] = "支持区域重复：" . $vv['AREA_NM'];
                }
                if ($vv['AREA_TYPE'] === 2 && in_array($areas_info_list[$vv['AREA_NO']]['parent_no'], $existed_areas)) {
//                    $err[$v['__row']]['err'][] = "支持区域重复，已有模板包含所属国家的全境区域：" . $vv['AREA_NM'];
                }
            }
            $v['__area'] = $c_a;
            $v['CURRENCY_CD'] = 'N000590300';
            $v['CHANNEL_TYPE'] = $country_ids[0] == '1' && count($country_ids) == 1 ? 1 : 0;
            $v['CREATE_USER'] = $user;
            $v['UPDATE_USER'] = $user;
            $v['EXCEL'] = $this->excel_name;
            if ($err[$v['__row']]['err']) {
                $err[$v['__row']]['row'] = $v['__row'];
                $err[$v['__row']]['name'] = $v['MODEL_NM'];
                $flg = false;
            }
            if (!$flg) $count++;
        }
        unset($v);
        return ['error' => count($err), 'data' => $err, 'logistics_model_ids' => $logistics_model_ids];
    }

    public static function isInt($num)
    {
        return is_numeric($num) && $num == (int)$num && $num >= 0;
    }

    public static function isUNum($num)
    {
        return is_numeric($num) && $num >= 0;
    }

    public static function isNum($num)
    {
        return is_numeric($num);
    }

    private function saveImportLgt(array $importData)
    {
        $err = [];
        M()->startTrans();
        foreach ($importData as $v) {
            $existed = M('lgt_postage_model', 'tb_')->where(['MODEL_NM' => $v['MODEL_NM'], 'LOGISTICS_MODEL_ID' => $v['LOGISTICS_MODEL_ID']])->getField('ID');
            $v['POSTTAGE_VAL'] = json_encode($v['__range']);//区间信息
            if ($existed) {
                $postage_id = $existed;
                $del_params = M("ms_params","tb_")->where("P_ID1={$postage_id} AND TYPE=5")->delete();  //先删后存
                $del_val = M("lgt_postage_val","tb_")->where("POSTAGE_MODEL_ID={$postage_id}")->delete();
                $del_area = M("lgt_postage_area","tb_")->where("LGT_POSTAGE_ID={$postage_id}")->delete();
                if (!$del_params || !$del_val || !$del_area) {
                    $msg = '原数据删除失败：' . M()->getDbError();
                    goto saveEnd;
                }
                //修改
                if (!$this->where("ID={$postage_id}")->save($this->create($v))) {
                    $msg = '模板修改失败：' . $this->getDbError();
                    goto saveEnd;
                }
            } else {
                //新增基础信息
                if (!$postage_id = $this->add($this->create($v))) {//saving
                    $msg = '模板添加失败：' . $this->getDbError();
                    goto saveEnd;
                }
            }

            #-------------------------------------------------------------------------------------\
            //新增仓库信息(params)  (模板id,)
            $supWareData = $this->setModelWare_ParamsData($v,$postage_id);  //获取数据
            if (!M("ms_params","tb_")->addAll($supWareData)) {//saving
                $msg = '仓库配置添加失败：' . M("ms_params","tb_")->getDbError();
                goto saveEnd;
            }
            #-------------------------------------------------------------------------------------\
            //新增价价格区间	//校验价格区间结果返回错误
            foreach ($v['__range'] as &$vv) {
                $vv['POSTAGE_MODEL_ID'] = $postage_id;
                unset($vv['__row']);
            }
            unset($vv);
            if (!M("lgt_postage_val","tb_")->addAll($v['__range'])) {//saving
                $msg = '价格区间保存失败：' . M("lgt_postage_val","tb_")->getDbError();
                goto saveEnd;
            }
            #--------------------------------------------------------------------------------------------\
            //获取区域数据、新增支持目的地区域  传入模板id
            foreach ($v['__area'] as &$vv) {
                $vv['LGT_POSTAGE_ID'] = $postage_id;
            }
            unset($vv);
            if (!M("lgt_postage_area","tb_")->addAll($v['__area'])) { //saving
                $msg = '支持区域保存失败：' . M("lgt_postage_area","tb_")->getDbError();
                goto saveEnd;
            }
            //日志保存
            $logData = $this->getLogData($v['LOGISTICS_MODEL_ID'], $existed ? '2' : '1');
            $logData['EXCEL'] = $this->excel_name;
            $logData['LGT_POSTAGE_ID'] = $postage_id;
            if (!$reslog = M("lgt_log","tb_")->add($logData)) {
                $msg = '日志保存失败：' . M("lgt_log","tb_")->getDbError();
                goto saveEnd;
            }
            #----------------------------------------------------------------------------------------------\
            #物流方式表增加模板id,仓库信息
            $res = $this->updateWarehouseAndPostageId($v['LOGISTICS_MODEL_ID'], $postage_id);
            if ($res === false) {
                $msg = '物流方式表更新失败';
                goto saveEnd;
            }
            continue;
            saveEnd:
            $err[$v['__row']]['err'][] = $msg;
            $err[$v['__row']]['row'] = $v['__row'];
            $err[$v['__row']]['name'] = $v['MODEL_NM'];
            continue;
        }
        if ($err) {
            M()->rollback();
        } else {
            M()->commit();
        }
        return ['error' => count($err), 'data' => $err];
    }

    public function getLog($params)
    {
        $list = [
            'update_time' => '2018-12-27 20:09:48',
            'excel' => 'leshan_20190108195928_161.xlsx',
            'update_user' => 'leshan',
            'detail' => '<修改原产地>:<修改前>-韩国,<修改后>-中国',
        ];
        $list = M()->table('gs_dp.dp_lgt_postage_log')
            ->where(['postage_id' => $params['postage_id']])
            ->order('id desc')
            ->select();
        printr($list);die;
        return ['list' => $list, 'total' => count($list)];
    }
}