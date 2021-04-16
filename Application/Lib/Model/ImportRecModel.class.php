<?php 
/**
* 
*/
class ImportRecModel extends BaseImportExcelModel
{
	protected $trueTableName = 'tb_hr_resume';

	protected $_auto = [
        ['CREATE_TIME', 'getTime', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback'],
 
    ];
	
	public function fieldMapping()    //重写字段
    {
        return [
            'ID' => ['field_name' => '编号', 'required' => true],
            'NAME' => ['field_name' => '姓名', 'required' => true],
            'JOBS' => ['field_name' => '应聘岗位', 'required' => false],
            'TEL' => ['field_name' => '电话', 'required' => false],
            'MAIL' => ['field_name' => '邮箱', 'required' => false],
            'SOURCE' => ['field_name' => '简历来源', 'required' => false],
            'URL' => ['field_name' => '链接', 'required' => false],
            'STATUS' => ['field_name' => '状态', 'required' => false],
            'PIC_URL' => ['field_name' => '附件地址', 'required' => false],
            'NAME1' => ['field_name' => '预约人', 'required' => false],
            'NAME2' => ['field_name' => '面试官', 'required' => false],
            'WEEKDAYS' => ['field_name' => '星期', 'required' => false],
            'JOB_DATE1' => ['field_name' => '预约日期', 'required' => false],
            'JOB_DATE2' => ['field_name' => '预约面试时间', 'required' => false],
            'DEPT_ID' => ['field_name' => '部门', 'required' => false],
            'JOB_MSG' => ['field_name' => '面试评语', 'required' => false],
        ];
    }
//过滤月日
    public function format($data='')
    {
        $regex = "/\月/";
        $data =  preg_replace($regex,"-",$data);
        $regex = "/\日/";
        return preg_replace($regex, '', $data);
    }
    
    public function import()
    {
    	parent::import();
        
    	foreach ($this->data as $key => $value) {
    	 	 $data = array_column($value, 'value','db_field');
             
              //$data['TEL']
             $deptData = D('TbHrDept')->field('ID,DEPT_NM')->select();
             foreach ($deptData as  $v) {
                 if (str_replace(' ', '', strtolower($v['DEPT_NM']))==str_replace(' ','',trim(strtolower($data['DEPT_ID'])))) {
                     $data['DEPT_ID'] = $v['ID'];
                 }
             }
             if ($data['JOB_DATE1']!='') {
                $pos = strpos($data['JOB_DATE1'],"日");
                if (!$pos) {
                    $data = [
                        'code'=>500,
                        'msg'=>'error',
                        'data'=>'预约日期格式填写不正确',
                    ];
                    exit(json_encode($data));
                }
                $data['JOB_TIME1'] = substr($data['JOB_DATE1'],$pos+3)?substr($data['JOB_DATE1'],$pos+3):'';
                $data['JOB_DATE1'] = substr($data['JOB_DATE1'],0, $pos+3); 
                $data['JOB_DATE1'] = date('Y').'-'.$this->format($data['JOB_DATE1']);
                 //$data['JOB_DATE1'] = '2017'.'-'.$this->format($data['JOB_DATE1']);
                $data['JOB_DATE1'] = date('Y-m-d',strtotime($data['JOB_DATE1']));
             }else{
                $data['JOB_DATE1'] = date('Y-m-d');
                $data['JOB_TIME1'] =date('H:i');
             }
             if ($data['JOB_DATE2']!='') {
                $pos = strpos($data['JOB_DATE2'],"日");
                if (!$pos) {
                    $data = [
                        'code'=>500,
                        'msg'=>'error',
                        'data'=>'预约面试时间格式填写不正确',
                    ];
                    exit(json_encode($data));
                }
                $data['JOB_TIME2'] = substr($data['JOB_DATE2'],$pos+3);
                $data['JOB_DATE2'] = substr($data['JOB_DATE2'],0, $pos+3);    
                $data['JOB_DATE2'] = date('Y').'-'.$this->format($data['JOB_DATE2']);
                //$data['JOB_DATE2'] = '2017'.'-'.$this->format($data['JOB_DATE2']);
                $data['JOB_DATE2'] = date('Y-m-d',strtotime($data['JOB_DATE2']));
             }else{
                $data['JOB_DATE2'] = '';
                $data['JOB_TIME2'] ='';
             }
             //验证重复号码
             if (!$telRes = $this->where("TEL='{$data['TEL']}'")->find()) {
                  $packArr[] = array_merge($data, $this->create($data, 1));    //模型填充,create组装数据
             }else{
                  $repeatTel[] = $data['TEL'];      
              }
    	   }
           //去除输入的重复号码
           if (!is_null($packArr)) {
                $AllTel = array_column($packArr, 'TEL');
                $hasRepeatTel =  array_unique(array_diff_assoc($AllTel, array_unique($AllTel)));
                //$hasRepeatTel =  implode(",",array_unique(array_diff_assoc($AllTel, array_unique($AllTel))));
           }
          
           foreach ($packArr as $k => &$v) {
               if (in_array($v['TEL'], $hasRepeatTel)) {
                   unset($packArr[$k]);
               }
           }
           $uniqueRepeatTel = implode(",", $hasRepeatTel);   //写入重复的号码




           $repeatTel = array_unique($repeatTel);
           $this->startTrans(); 
           if (!is_null($repeatTel)) {
                $telStr = implode(",", $repeatTel);
           }
    	if ($this->addAll($packArr)) {  
            $inFirstID = $this->getLastInsID();
            $dataLength = count($packArr);
            foreach ($packArr as $k => $v) {
                $data = D('TbHrResumeOperationLog')->create($v,1);
                $data['RESUME_ID'] = $inFirstID+$k;
                $logData[] = $data; 
            }
            if (!D('TbHrResumeOperationLog')->addAll($logData)) {
                $data = [
                    'code'=>500,
                    'msg'=>'error',
                    'data'=>'操作日志未记录'
                ];
                $this->rollback();
                exit(json_encode($data));
            }



            if (!empty($uniqueRepeatTel)) {
                $data = [
                    'code'=>501,
                    'msg'=>'tel repeat ',
                    'data'=>'您的导入数据中有重复电话号,请修改:'.$uniqueRepeatTel,
                ];
            }elseif ($telStr) {
                 $data = [
                    'code'=>501,
                    'msg'=>'tel repeat ',
                    'data'=>'导入用户的电话'.$telStr.'已存在,请前往修改状态',
                ];
            }else{
                $data = [
                    'code'=>200,
                    'msg'=>'success',
                    'data'=>'导入成功'
                ];
            }
            $this->commit();
		    exit(json_encode($data));
		}else{
            if ($telStr) {
                $data = [
                    'code'=>501,
                    'msg'=>'tel repeat ',
                    'data'=>'导入用户的电话'.$telStr.'已存在,请前往修改状态',
                ];
            }elseif(!empty($uniqueRepeatTel)) {
                $data = [
                    'code'=>501,
                    'msg'=>'tel repeat ',
                    'data'=>'您的导入数据中有重复电话号,请修改:'.$uniqueRepeatTel,
                ];
            }else{
                $res = $this->getError();
                $data = [
                    'code'=>500,
                    'msg'=>'error',
                    'data'=>$res
                ];
            }
    		exit(json_encode($data));
	   }
    }
}
?>