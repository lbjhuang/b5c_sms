<?php 
/**
 * 物流规则数据处理模型.
 * 
 * User: huanzhu
 * Date: 2017/12/13
 * Time: 13:20
 */
class ElecsheetLogModel extends Model
{
	protected $trueTableName = 'tb_ms_ord_elecsheet_log';
	protected $_auto = [
        ['create_time', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['update_time', 'getTime', Model::MODEL_BOTH, 'callback'],
    ];

    protected $_map = [
        
    ];
    protected $_validate = [
        
    ];

    private function getWhere($condition)
    {
        if (!empty($condition['startTime']) && empty($condition['endTime']))  $where['tb_ms_ord_elecsheet_log.update_time'] = array("EGT",$condition['startTime']);
        if (!empty($condition['endTime']) && empty($condition['startTime']))  $where['tb_ms_ord_elecsheet_log.update_time'] = array("ELT",date("Y-m-d H:i:s",strtotime("+1day",strtotime($condition['endTime']))));
        if (!empty($condition['endTime']) && !empty($condition['startTime'])) 
            $where['tb_ms_ord_elecsheet_log.update_time'] = array(array('EGT',$condition['startTime']),array('ELT',date("Y-m-d H:i:s",strtotime("+1day",strtotime($condition['endTime'])))),'and');
        if (!empty($condition['apiType']))  $where['tb_ms_ord_elecsheet_log.api_type'] = $condition['apiType'];
        if (!empty($condition['providerSystem']))  $where['tb_ms_ord_elecsheet_log.provider'] = $condition['providerSystem'];

        return $where;

    }
    private function getCount($condition)
    {
        $where = $this->getWhere($condition);
        $count = $this
        ->join("tb_ms_cmn_cd on tb_ms_ord_elecsheet_log.b5c_logistics_cd = tb_ms_cmn_cd.CD")
        ->join("tb_ms_logistics_mode on tb_ms_ord_elecsheet_log.servie_code = tb_ms_logistics_mode.SERVICE_CODE")
        ->field("tb_ms_cmn_cd.CD_VAL,tb_ms_ord_elecsheet_log.*,tb_ms_logistics_mode.LOGISTICS_MODE")
        ->where($where)
        ->limit($pageno,$condition['pageRows'])
        ->count();
        return $count;
    }

    //get api list
    public function getTrackData($condition)
    {
        $pageno = ($condition['pageCurrent']-1)*$condition['pageRows'];

        $where = $this->getWhere($condition);
    	$trackData = $this
    	->join("tb_ms_cmn_cd on tb_ms_ord_elecsheet_log.b5c_logistics_cd = tb_ms_cmn_cd.CD")
        ->join("tb_ms_logistics_mode on tb_ms_ord_elecsheet_log.servie_code = tb_ms_logistics_mode.SERVICE_CODE")
    	->field("tb_ms_cmn_cd.CD_VAL,tb_ms_ord_elecsheet_log.*,tb_ms_logistics_mode.LOGISTICS_MODE")
        ->where($where)
        ->limit($pageno,$condition['pageRows'])
    	->select();

        $count = $this->getCount($condition);  //总数
    	$interData = $this->getInterfacesData();   //获取接口数据
        //assembly data
        $trackResData = $this->combineData($interData,$trackData,$condition);
        $res['trackResData'] = $trackResData;
        $res['count'] = $count;
    	return $res;
    }
    private function getCondition($condition,$trackData)
    {
        if (!empty($condition['apiType']) or !empty($condition['providerSystem'])) {
                foreach ($trackData as $k => $v) {
                   if (!empty($condition['apiType'])) {
                         if($v['api_type']==$condition['apiType']) {
                            $realTrackAll[] = $v;
                        }
                     }
                     if (!empty($condition['providerSystem'])) {
                         if ($v['provider']==$condition['providerSystem']) {
                             $realTrackAll[] = $v;
                         }
                     }
                      
                } 
            
            }else{
                $realTrackAll = $trackData;
            }
            return $realTrackAll;
    }

    /**
     * @return $resData
     * @source get apitype、clientSystem、providerSystem data
     */
    public function combineData($interData=array(),$trackData=array(),$condition)
    {
        
        $apitype = $interData['apiType']?$interData['apiType']:null;
        $providerSystem = $interData['providerSystem']?$interData['providerSystem']:null;
        $clientSystem = $interData['clientSystem']?$interData['clientSystem']:null;
        $apiCallStatus = $interData['apiCallStatus']?$interData['apiCallStatus']:null;
        $realTrackAll = $trackData;
        //$realTrackAll = $this->getCondition($condition,$trackData);
        $sort = 1+$condition['pageRows']*($condition['pageCurrent']-1);;
        foreach ($realTrackAll as $k => $v) {

            $realTrackAll[$k]['sort'] = $sort;
            $sort++;
            if (!is_null($apitype)) {
                foreach ($apitype as $k1 => $v1) {
                    if ($realTrackAll[$k]['api_type']==$k1) {
                        $realTrackAll[$k]['api_type'] = $v1;
                    }
                }
            }
            if (!is_null($providerSystem)) {
                foreach ($providerSystem as $k1 => $v1) {
                    if ($realTrackAll[$k]['provider']==$k1) {
                        $realTrackAll[$k]['provider'] = $v1;
                    }
                }
            }
            if (!is_null($clientSystem)) {
                foreach ($clientSystem as $k1 => $v1) {
                    if ($realTrackAll[$k]['client']==$k1) {
                        $realTrackAll[$k]['client'] = $v1;
                    }
                }
            }
            if (!is_null($apiCallStatus)) {
                foreach ($apiCallStatus as $k1 => $v1) {
                    if ($realTrackAll[$k]['call_health']==$k1) {
                        $realTrackAll[$k]['call_status'] = $v1;
                    }
                }
            }
        }
        return $realTrackAll;
    }
    /**
     * @return $resData
     * @source InterfacesData
     */
    public function getInterfacesData($trackData)
    {
         if ($data = curl_request("http://172.16.1.217:8083/general/lgt/log/translate")){
             $resData = json_decode($data,1);
         }

        return $resData;
    }

    //track data
    private function getlgtData($orderid='')
    {
        if (!empty($orderid)) {
            $trackdetData = M("ms_lgt_track","tb_")
            ->field('*')->where('ord_id='."'".$orderid."'")
            ->select();
            $lastTime = array_column($trackdetData, 'UPDATE_TIME');
            $maxTime = max($lastTime);
            foreach ($trackdetData as $k => $v) {
                if ($v['UPDATE_TIME']==$maxTime) {
                    $trackAll = json_decode($v['LGT_CONTENT'],1);
                }
                //$arrList = json_decode($v['LGT_CONTENT'],1);
                /*foreach ($arrList as  $v1) {
                    $trackAll[] = $v1;
                }*/
                if($v['UPDATE_TIME']==$maxTime){
                    $expeStatus = $v['LGT_TYPE'];  //状态
                    $data['TRACK_NO'] = $v['TRACK_NO'];
                }
            }
            $statusData = M("ms_cmn_cd","tb_")->field("CD,CD_VAL")
            ->where('CD='."'".$expeStatus."'")->find();
            $data['expeStatus'] = $statusData['CD_VAL'];
            $data['expeCode'] = $statusData['CD'];
            $data['trackAll'] = $trackAll;
        }else{
            $data = null;
        }
        return $data;
        
    }

    //log detail
    public function getTrackDetail()
    {
        $Id = $_REQUEST['Id'];
            $logData = $this
            ->join("tb_ms_cmn_cd on tb_ms_ord_elecsheet_log.b5c_logistics_cd = tb_ms_cmn_cd.CD")
            ->join("tb_ms_logistics_mode on tb_ms_ord_elecsheet_log.servie_code = tb_ms_logistics_mode.SERVICE_CODE")
            ->join("tb_ms_ord on tb_ms_ord_elecsheet_log.ord_id=tb_ms_ord.ORD_ID")
            ->field("tb_ms_cmn_cd.CD_VAL,tb_ms_ord.COUNTRY_CODE,
                tb_ms_ord_elecsheet_log.*,
                tb_ms_logistics_mode.LOGISTICS_MODE")
            ->where('tb_ms_ord_elecsheet_log.id='."'".$Id."'")
            ->find();
            if (!is_null($logData['COUNTRY_CODE'])) {
                $znCountry = M("ms_user_area","tb_")->field("zh_name")->where('two_char='."'".$logData['COUNTRY_CODE']."'")->find();
            }
            $detailData['destination'] = $znCountry['zh_name']?$znCountry['zh_name']:''; 

            $orderid = $logData['ord_id'];  //ord id
            //获取物流轨迹数据
            $data = $this->getlgtData($orderid);

            $detailData['EXPE_COMPANY'] = $logData['CD_VAL'];   //company
            $detailData['LOGISTICS_MODE'] = $logData['LOGISTICS_MODE'];  //方式
            if(!is_null($data)){
                $detailData['TRACK_NO'] = $data['TRACK_NO'];   //运单号
                $detailData['expeStatus'] = $data['expeStatus'];  //状态
                $detailData['expeCode'] = $data['expeCode'];
                $detailData['trackAll'] = $data['trackAll'];   
            }
        return $detailData;
    }
}

 ?>