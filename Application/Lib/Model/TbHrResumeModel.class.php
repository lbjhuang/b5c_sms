<?php 
/**
* 简历表模型
*/
class TbHrResumeModel extends BaseModel
{
	protected $trueTableName = 'tb_hr_resume';
	protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];

   protected $_validate = [
        ['NAME','require','请填写姓名'],//默认情况下用正则进行验证
        ['JOBS','require','请填写预约岗位'],//默认情况下用正则进行验证
        //['TEL','require','请填写电话'],//默认情况下用正则进行验证
        //['MAIL','require','请填写邮箱'],//默认情况下用正则进行验证
        //['SOURCE','require','请填写简历来源'],//默认情况下用正则进行验证
        //['URL','require','请填写链接'],//默认情况下用正则进行验证
        //['STATUS','require','请填写状态'],//默认情况下用正则进行验证
       // ['PIC_URL','require','请填写附件地址'],//默认情况下用正则进行验证
        ['NAME1','require','请填写预约人'],//默认情况下用正则进行验证
        //['WEEKDAYS','require','请填写星期几'],//默认情况下用正则进行验证
        //['NAME2','require','请填写面试官'],//默认情况下用正则进行验证
        //['DEPT_ID','require','请填写部门'],//默认情况下用正则进行验证
        //['JOB_TIME2','require','请填写面试时间'],//默认情况下用正则进行验证
        //['JOB_MSG','require','请填写面试评语'],//默认情况下用正则进行验证
        //['JOB_DATE1','require','请填写预约日期'],//默认情况下用正则进行验证
        //['JOB_TIME1','require','请填写预约时间'],//默认情况下用正则进行验证
    ];


   public function showResume($keywords)
   {
       $m = D('TbHrResume');
       $pageSize = $keywords['pageSize']?$keywords['pageSize']:5;
       $sePage = $keywords['size']; 
       $page = ($sePage-1)*$pageSize;
        $start = $page>0?$page:0;
        $where =$this->getwhere($keywords);

        $order = $this->getorder($keywords);
        $sql = $m
        ->join('left join tb_hr_dept on tb_hr_resume.DEPT_ID = tb_hr_dept.ID')
        ->field('tb_hr_resume.ID,tb_hr_resume.NAME,tb_hr_resume.WEEKDAYS,
            tb_hr_resume.DEPT_ID,tb_hr_resume.JOBS,tb_hr_resume.NAME2,tb_hr_resume.TEL,tb_hr_resume.SOURCE,
            tb_hr_resume.STATUS,tb_hr_resume.CREATE_TIME,tb_hr_resume.JOB_TIME1,tb_hr_resume.JOB_TIME2,
            tb_hr_resume.NAME1,tb_hr_resume.JOB_DATE1,tb_hr_resume.JOB_DATE2,tb_hr_resume.IS_NOT_ARRANGE,tb_hr_dept.DEPT_NM
            ')
        ->order($order)
        ->where($where)
       // ->where()
        ->limit($start,$pageSize)
        
        ->buildSql();
        //var_dump($where); die;
        $deptData = D('TbHrDept')->select();
        $recruitData = M()->table($sql . 'a')->select();
        foreach ($recruitData as $k => $v) {
            $recruitData[$k]['CREATE_TIME'] = date('Y-m-d H:i',strtotime($v['CREATE_TIME']));
            foreach ($deptData as $k1 => $v1) {
                if ($v['DEPT_ID']==$v1['ID']) {
                    $recruitData[$k]['DEPT_NAME'] = $v1['DEPT_NM'];
                }
            }
        }

        $count = $m->join('left join tb_hr_dept on tb_hr_resume.DEPT_ID = tb_hr_dept.ID')->where($where)->count();

        
        $recruitData['count'] = $count;
        return $recruitData;
   }

   public function getwhere($keywords)
    {
    	$status = $keywords['checkStatus'];

        if ($keywords['checkStatus']) {
            $where['tb_hr_resume.STATUS'] = array("in",$status);
        }
        if(!empty($keywords["startTime"])&&!empty($keywords["endTime"])&&!empty($keywords['date'])){
        	$where[$keywords['date']] = array(array('egt',cutting_time($keywords["startTime"])),array('elt',cutting_time($keywords["endTime"]).' 23:59:59'),'AND');
        }else if(!empty($keywords['date'])){
            if(!empty($keywords['startTime']) or !empty($keywords['endTime']))
        	$where[$keywords['date']] = array(array('egt',cutting_time($keywords["startTime"])),array('elt',cutting_time($keywords["endTime"]).' 23:59:59'),'OR');
        }
        
        $value = trim($keywords['keyword']);
        $key = $keywords['week'];
        if ($keywords['keyword']) {
        	switch ($key) {
        		case '周几':
        			$key = 'WEEKDAYS';
        			break;
        		case '姓名':
        			$key = 'NAME';
        			break;
        		case '部门':
        			$key = 'DEPT_NM';
        			break;
        		case '应聘岗位':
        			$key = 'JOBS';
        			break;
        		case '面试官':
        			$key = 'NAME2';
        			break;
        		case '电话':
        			$key = 'TEL';
        			break;
        		case '邮箱':
        			$key = 'MAIL';
        			break;
        		case '简历来源':
        			$key = 'SOURCE';
        			break;
        		case '面试评语':
        			$key = 'JOB_MSG';
        			break;
        		case '预约人':
        			$key = 'NAME1';
        			break;
        		case '链接':
        			$key = 'URL';
        			break;
        		default:
        			break;
        	}
        	$where[$key] = array("like","%{$value}%");
            
        }
        return $where;
    }
    public function getorder($keywords)
    {
    	$ret = ' ID desc';
    	$order = $keywords['checkSorting'];
    	if ($keywords['checkSorting']) {
    		switch ($order) {
    			case '按预约时间':
    				$ret = 'JOB_DATE1 desc,JOB_TIME1 DESC';
    				break;
    			case '按创建时间':
    				$ret = ' CREATE_TIME desc';
    				break;
    			case '按面试时间':
    				$ret = ' JOB_DATE2 desc,JOB_TIME2 desc';
    				break;
    			case '按岗位':
    				$ret = 'CONVERT(JOBS USING gbk) COLLATE gbk_chinese_ci  asc';
    				break;
    			case '...':
    				break;
    		}
    	}
        return $ret;
    }


        public function matchKeyTitle(){
        $arr = array(
            array(
                'lable'=>"编号",
                'value'=>'id',
                'field'=>'ID',
            ),
            array(
                'lable'=>"预约面试时间",
                'value'=>'job_date2',
                'field'=>'JOB_DATE2',
            ),
             array(
                'lable'=>"星期",
                'value'=>'weekdays',
                'field'=>'WEEKDAYS',
            ),
             array(
                'lable'=>"姓名",
                'value'=>'name',
                'field'=>'NAME',
            ),
             array(
                'lable'=>"部门",
                'value'=>'deptid',
                'field'=>'DEPT_ID',
            ),
             array(
                'lable'=>"应聘岗位",
                'value'=>'jobs',
                'field'=>'JOBS',
            ),
             array(
                'lable'=>"面试官",
                'value'=>'name2',
                'field'=>'NAME2',
            ),
            array(
                'lable'=>"电话",
                'value'=>'tel',
                'field'=>'TEL',
            ),
            array(
                'lable'=>"邮箱",
                'value'=>'mail',
                'field'=>'MAIL',
            ),
            array(
                'lable'=>"简历来源",
                'value'=>'source',
                'field'=>'SOURCE',
            ),
            array(
                'lable'=>"状态",
                'value'=>'status',
                'field'=>'STATUS',
            ),
            /*array(
                'lable'=>"附件地址",
                'value'=>'picurl',
                'field'=>'PIC_URL',
            ),*/
            array(
                'lable'=>"预约日期",
                'value'=>'job_date1',
                'field'=>'JOB_DATE1',
            ),
            array(
                'lable'=>"面试评语",
                'value'=>'jobMsg',
                'field'=>'JOB_MSG',
            ),
            array(
                'lable'=>"预约人",
                'value'=>'name1',
                'field'=>'NAME1',
            ),
            array(
                'lable'=>"链接",
                'value'=>'url',
                'field'=>'URL',
            ),
        );
        return $arr;
    }

    public function findKeyLable($key){
        static $stc_relations=null;
        if($stc_relations===null){
            $keyInfo = $this->matchKeyTitle();
            $stc_relations = array_column($keyInfo,'lable','field');
        }
        $ret = isset($stc_relations[$key])?$stc_relations[$key]:null;
        return $ret;
    }


    public function gainKeyInfo($data=array(),$key=''){
        $ret = '';
        $ret = isset($data[$key])?$data[$key]:'';
        // some extra
        if($key=='SEX'){
            $ret = self::getSexForUser($ret);
        }
        if($key=='PER_JOB_DATE' or $key=='PER_BIRTH_DATE' or $key=='DEP_JOB_DATE'){
            if($ret=='0000-00-00 00:00:00'){
                $ret = '';
            }
            if($ret){
                $ret = date('Y-m-d',strtotime($ret));     //处理y-m-d格式
            }
        }
        return $ret;
    }


    public function changeParam($res=array())
    {
        $newres['empNm'] = $res['NAME'];
        $newres['jobCd'] = $res['JOBS'];
        $newres['prePhone'] = $res['TEL'];
        $newres['email'] = $res['MAIL'];
       // $newres['status'] = $res['STATUS'];
        $newres['resume'] = $res['PIC_URL'];
        $depts = D('TbHrDept')->field('DEPT_NM')->where("ID={$res['DEPT_ID']}")->find();
        $newres['deptName'] =$depts['DEPT_NM'];
        return $newres;
    }
    //手动推送应聘人员信息
    public function pullemail($data)
    {
        $ids = $data['ids'];
        $Master = $data['leader'];
        if (count($Master)==0) {
            $res = [
                'res'=>'推送失败,请选择推送领导',
                'code'=>500,
                'msg'=>'error',
            ];
            return $res;die;
        }
        foreach ($Master as   $v) {
           $MasterEmails = D('TbHrEmpl')->field('SC_EMAIL')->where('EMP_SC_NM='."'".$v."'")->find();
           if ($MasterEmails['SC_EMAIL'] == '') {
               $res = [
                'res'=>'推送失败,'.$v.'的邮箱不存在',
                'code'=>500,
                'msg'=>'error',
            ];
            return $res;
           }
           $MasterEmail[$v] = $MasterEmails['SC_EMAIL'];
           
        }


        $email = new SMSEmail();
        $email->FromName = $_SESSION['m_loginname'];      
        $title = '面试安排 - '.$Master.' -'.date('Y-m-d');
        $where['ID'] = array('in',$ids);
        $data = $this->where($where)->order('JOB_TIME2 asc')->select();
        $chechStatus = array_column($data, 'STATUS','ID');
        foreach ($chechStatus as $k => $v) {
            if ($v!='初试'&&$v!='复试') {
                $unConform[] = $k; 
            }
        }
        $dataStr = implode($unConform, ',');
        if ($dataStr) {
            $res = [
                'res'=>'简历编号'.$dataStr.'状态不符合,仅允许初试和复试状态简历推送',
                'code'=>500,
                'msg'=>'error',
            ];
            return $res;die;
        }
        //$message = 'Dear'.;
        //var_dump($data);die;
        

        //$cc = ['huanzhu@gshopper.com'];   推送
        $where = array('in',$ids);
        foreach ($MasterEmail as $k => $v) {
            $title = '面试安排 - '.$k.' -'.date('Y-m-d');

            $count = 1;
            $message = 'Dear '.$k.':'.'<br>';
            $message .= "<div style='height:10px;'></div>";
            $message .='以下表格是之后的面试安排,届时前台会通知您进行面试,请合理安排工作时间';
            $message .= "<div style='height:10px;'></div>";
            foreach ($data as $k=> $v1) {
                
                $v1['NAME2'] = str_replace(',', '、', $v1['NAME2']);

                if ($count==1) {
                    $message .= '<table  style="border:1px solid #cadee7;" width="850" cellpadding=0 cellspacing=0 rules="all">';
                    $message.='<tr align="center" height="30" bgcolor="#1e7eb4" style="color: white;" ><td width="50" style="text-align: center;">序号</td><td>面试时间</td><td>周几</td><td>姓名</td><td>部门</td><td>应聘岗位</td><td>面试状态</td><td>面试官</td></tr>';
                }
                
                $message .= '<tr align="center" height="30"  >';
                $message .= '<td>'.$count.'</td>';
                $message .= '<td>'.$v1['JOB_DATE2'].' '.$v1['JOB_TIME2'].'</td>';
                $message .= '<td>'.$v1['WEEKDAYS'].'</td>';
                $message .= '<td>'.$v1['NAME'].'</td>';
                $deptid = $v1['DEPT_ID'];
                $deptids =  D('TbHrDept')->where('ID='."'".$deptid."'")->find();
                $v1['DEPT_ID'] = $deptids['DEPT_NM'];
               if ($deptinfo = D('TbHrDept')->where('ID='.$deptid)->find()) {
                    $data[$k]['DEPT_ID'] = $deptinfo['DEPT_NM'];
                }
                $message .= '<td>'.$v1['DEPT_ID'].'</td>';
                $message .= '<td>'.$v1['JOBS'].'</td>';
                $message .='<td>'.$v1['STATUS'].'</td>';
                $message .= '<td>'.$v1['NAME2'].'</td>';
                $count++;
                $message .='</tr>';
                if ($v1['PIC_URL']) {
                    $attachments[] = ATTACHMENT_DIR.$v1['PIC_URL'];    
                }
            }
        $message .= '</table>';
        $message .= "<div style='height:10px;'></div>";
            $message .='请悉知';
            $message .= "<div style='height:10px;'></div>";
            //var_dump($message);die;
            if (!$ret = $email->sendEmail($v,$title,$message,$cc,$attachments)) {
                $res = [
                    'res'=>'推送失败',
                    'code'=>500,
                    'msg'=>'success',
                ]; 
                return $res;die;
            }

        }
             $res = [
                'res'=>'推送成功',
                'code'=>200,
                'msg'=>'success',
            ];  
        
 
        return $res;   
    }

    //定时推送邮件到各部门负责人
    public function sendMail()
    {
        $data = D('TbHrResume')->select();
        $date = array_column($data, 'JOB_DATE2');
        $pushDate = getSameWeekLast($date);    //获取符合条件的时间
        foreach ($data as $v) {
            if (in_array($v['JOB_DATE2'], $pushDate)) {
                $needinfo[] =$v['NAME2']; 
                $needData[] = $v;                        
            }
        }
        foreach ($needinfo as  $v) {
            $name2 = explode(',', $v);
            foreach ($name2 as  $v) {
                $name2Arr[] = $v;    
            }
            
        }
        $name2Arr = array_unique($name2Arr);

        foreach ($name2Arr as $v) {  //批量发送邮件  给这些面试官发
            $email = new SMSEmail();
            $email->FromName = '本周面试安排通知';

                 $title = '面试安排 -'.$v .'- '.date('Y.m.d');  
                    $message ='Dear '.$v.':';
                    $message .= "<div style='height:10px;'></div>";
                    $message .='以下表格是本周的面试安排,届时前台会通知您进行面试,请合理安排工作时间';
                    $message .= "<div style='height:10px;'></div>";

                    $message .= "<table style='border:1px solid #cadee7;' border='1' width='850' cellpadding=0 cellspacing=0 rules='all'>
                    <tr align='center' height='30' bgcolor='#1e7eb4' style='color: white;'>
                    <td width='50' style='text-align: center;'>序号</td>
                    <td>面试时间</td>
                    <td>星期几</td>
                    <td>姓名</td>
                    <td>部门</td>
                    <td>应聘岗位</td>
                    <td>面试状态</td>
                    <td>面试官</td>
                    </tr>";
                    unset($attachment);
                     $count = 1;                           
             foreach ($needData as $v1) {
                $arr = explode(',', $v1['NAME2']);
                if (in_array($v, $arr)) {
                    $deptid = $v1['DEPT_ID'];
                    if ($deptinfo = D('TbHrDept')->where('ID='.$deptid)->find()) {
                        $resumeData[$k]['DEPT_ID'] = $deptinfo['DEPT_NM'];
                    }
                    $v1['NAME2'] = str_replace(',', '、', $v1['NAME2']);

                    if (substr($v1['JOB_TIME2'], 8,2)==date('d')+1) {
                        $message .= "<tr bgcolor='yellow' align='center'>
                        <td align='center' height='30'>{$count}</td>
                        <td>{$v1['JOB_DATE2']} {$v1['JOB_TIME2']}</td>
                        <td>{$v1['WEEKDAYS']}</td>
                        <td>{$v1['NAME']}</td>
                        <td>{$resumeData[$k]['DEPT_ID']}</td>
                        <td>{$v1['JOBS']}</td>
                        <td>{$v1['STATUS']}</td>
                        <td>{$v1['NAME2']}</td>
                        </tr>";
                    }else{
                        $message .= "<tr align='center'>
                        <td align='center' height='50'>{$count}</td>
                        <td>{$v1['JOB_DATE2']} {$v1['JOB_TIME2']}</td>
                        <td>{$v1['WEEKDAYS']}</td>
                        <td>{$v1['NAME']}</td>
                        <td>{$resumeData[$k]['DEPT_ID']}</td>
                        <td>{$v1['JOBS']}</td>
                        <td>{$v1['STATUS']}</td>
                        <td>{$v1['NAME2']}</td>
                        </tr>";
                    }
                    $count++;
                    if ($v2['PIC_URL']) {
                        $attachment[] = ATTACHMENT_DIR.$v['PIC_URL']; 
                    }
                   
                }

               
            }
            $message .= "</table><br>"; 
            $message .= "<div style='height:10px;'></div>";
            $message .='请悉知';
            $message .= "<div style='height:10px;'></div>";
            $emaildata = D('TbHrCard')->where("EMP_SC_NM="."'".$v."'")->find();
            $addresser = $emaildata['SC_EMAIL'];
            $cc = [];
            if ($addresser) {
                $res = $email->sendEmail($addresser,$title,$message,$cc,$attachment);    
            }    
        }
        return $res;
    }


}


 ?>