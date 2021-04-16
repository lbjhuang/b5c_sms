<?php 
/**
* User: recruit
* Date: 2017/9/13
* author: huanzhu
*/
class HrRecruitAapi extends Action
{
	private $recruitModel;

	private function changeJson($code,$msg,$res)
	{
		$data = [
			'code'=>$code,
			'msg'=>$msg,
			'data'=>$res
		];
		exit(json_encode($data));
	}
	public function __construct()
	{
		$this->recruitModel =D('TbHrResume');
	}




	public function recexport($data)
	{
		$recinfo = $this->recruitModel->getDbFields();
		$recinfo = $this->recruitModel->matchKeyTitle();
		$recinfo = array_column($recinfo, 'field');
		unset($recinfo[15]);
		$arrId = $_REQUEST['param'];
		$arrId = explode(',', $arrId);
		foreach ($arrId as $v) {
			$dataRecruits = $this->recruitModel->field('ID,NAME,JOBS,TEL,MAIL,SOURCE,URL,STATUS,PIC_URL,NAME1,NAME2,
				WEEKDAYS,JOB_TIME1,JOB_TIME2,DEPT_ID,CREATE_TIME,JOB_MSG,JOB_DATE1,JOB_DATE2')->where('ID='.$v)->find();
			if(!empty($dataRecruits['JOB_DATE2'])){
				$dataRecruits['JOB_DATE2'] = str_replace('-', '月', substr($dataRecruits['JOB_DATE2'],5)) .'日'.$dataRecruits['JOB_TIME2'];	
			}
			if(!empty($dataRecruits['JOB_DATE1'])){
				$dataRecruits['JOB_DATE1'] = str_replace('-', '月', substr($dataRecruits['JOB_DATE1'],5)) .'日'.$dataRecruits['JOB_TIME1'];	
			}
			$dataRecruit[] = $dataRecruits; 
		}
		vendor("PHPExcel.PHPExcel");
		$objectPHPExcel = new PHPExcel();
        $objectPHPExcel->setActiveSheetIndex ( 0 );
        $objectPHPExcel->getActiveSheet()->setTitle ( '招聘管理数据' );
        $objSheet = $objectPHPExcel->getActiveSheet();//获取当前活动sheet

        $filename = 'export_recruit.xls';
        if(empty($dataRecruit)){
            header('Content-Type:application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
            $objWriter = PHPExcel_IOFactory::createWriter ( $objectPHPExcel, 'Excel5' );
            $objWriter->save ( 'php://output' );
            return null;
        }
		$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
		->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $line = 2;
        foreach($dataRecruit as $k_data=>$v_data){


        	$objSheet->getRowDimension($line)->setRowHeight(20);
        	$deptData = D('TbHrDept')->field('DEPT_NM')->where('ID='.$v_data['DEPT_ID'])->find();
        	$v_data['DEPT_ID'] = $deptData['DEPT_NM'];
            $i = 0;
            $maxlen = 0;
            foreach($recinfo as $key=>$val){
            	if(strlen($v_data[$val])>$key) $key =  strlen($v_data[$val]);
                $use_cell = PHPExcel_Cell::stringFromColumnIndex($i).$line;
                $col_cell = PHPExcel_Cell::stringFromColumnIndex($i);                  
               	if($key<10) $key=10;
                $objSheet->getColumnDimension($col_cell)->setWidth($key+5);               
                $objSheet->setCellValueExplicit($use_cell, $v_data[$val], PHPExcel_Cell_DataType::TYPE_STRING);
                ++$i;
            }
            ++$line;
           
        }


        $line = 1;
        $i = 0;
        $objSheet->getRowDimension($line)->setRowHeight(23);
        
        foreach($recinfo as $key=>$val){
            $col_cell = PHPExcel_Cell::stringFromColumnIndex($i);    //超过26行返回AA、AB...
            $objSheet->getStyle($col_cell)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);   //设置每个字段文本格式
			$objSheet->getStyle($col_cell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
           // $objSheet->getColumnDimension($col_cell)->setWidth(20);
            $use_cell = PHPExcel_Cell::stringFromColumnIndex($i).$line;
            $objSheet->getStyle($use_cell)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
            $objSheet->getStyle($use_cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objSheet->getStyle($use_cell)->getFill()->getStartColor()->setARGB('#000000');
            

            $use_val = $this->recruitModel->findKeyLable($val);
            $objSheet->setCellValue($use_cell,$use_val);   //写数据
            ++$i;
        }

         header('Content-Type:application/vnd.ms-excel');
	        header('Cache-Control: max-age=0');//禁止缓存
	        header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
	        $objWriter = PHPExcel_IOFactory::createWriter ( $objectPHPExcel, 'Excel5' );
	        $objWriter->save ( 'php://output' );
	}

	//detail
	public function detailData($data)
	{
		$id = $data['params'];
		if ($detaildata = $this->recruitModel->where("ID=".$id)->find()) {
			if ($detaildata['NAME2']) {
				$nameStr = $detaildata['NAME2'];
				$regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\，|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
           		 if ($nameStr = preg_replace($regex,',',$nameStr)) {
           		 	$nameArr = explode(',', $nameStr);
            	}else{
                	$nameArr[] = $nameStr;
            	}
				$detaildata['NAME2'] = $nameArr;
			}else{
				$detaildata['NAME2'] = array();
			}
			if ($detaildata['JOBS']) {
				if ($jobData = M('ms_cmn_cd','tb_')->field('CD,CD_VAL')->where('CD_VAL='."'".$detaildata['JOBS']."'")->find()) {
				   $jobid = $jobData['CD'];
				}else{
					$jobData = D('TbHrJobs')->field('ID,CD_VAL')->where('CD_VAL='."'".$detaildata['JOBS']."'")->find();
					$jobid = $jobData['ID'];
				}
				
			}
			$detaildata['JOB_ID'] = $jobid;
			if ($detaildata['IS_NOT_ARRANGE']==='1') {
				$detaildata['IS_NOT_ARRANGE'] = true;
			}else{
				$detaildata['IS_NOT_ARRANGE'] = false;
			}
			$code = 200;
			$msg = 'success';
			$res = $detaildata;
		}
		return $this->changeJson($code,$msg,$res);
	}

	//del
	public function delData($data)
	{
		$id = $data['params'];
		if (is_array($id)) {
			$where = 'ID in('.implode(',',$id).')';  
		}
		if ($res = $this->recruitModel->where($where)->delete()) {
			$code = 200;
			$msg = 'success';
			$res = '删除成功';	
		}else{
			$code = 500;
			$msg = 'error';
			$res = '删除失败';	
		}
		return $this->changeJson($code,$msg,$res);	
	}
	//change
	public function change($data)
	{
		$id = $data['id'];
		$status['STATUS'] = $data['status'];
		foreach ($id as $key => $v) {
			//记录修改日志
			$changeData = D('TbHrResume')->where('ID='."'".$v."'")->find();
			$changeData = D('TbHrResumeOperationLog')->create($changeData,1);
			$changeData['RESUME_ID'] = $changeData['ID'];
			$changeData['STATUS'] = $data['status'];
			unset($changeData['ID']);
			$logData[] = $changeData;
			$this->recruitModel->where("ID=".$v)->save($status);
		}
		
		if (!D('TbHrResumeOperationLog')->addAll($logData)) {
			return $this->changeJson(500,'error','日志记录失败');exit();
		}
		
		//var_dump($logData);die;
		return $this->changeJson(200,'success','修改成功');
	}

	//入职状态简历数据
	public function ResData($data)
	{
		$id = $data['resid'];
		if ($res = $this->recruitModel->where('id='.$id)->find()) {
			$newres = $this->recruitModel->changeParam($res);
			$code = 200;
			$msg = 'success';
			$res = $newres;
		}else{
			$code = 500;
			$msg = 'error';
			$res = '无数据';
		}
		return $this->changeJson($code,$msg,$res);
		
	}


	public function pullEmail($data)
	{
		$res = $this->recruitModel->pullemail($data);
		return json_encode($res);
	}

	public function getLeaderNM($deptname)
	{
		$deptArr = explode(',', $deptname);
		$where['DEPT_NM'] = array('in',$deptArr);
		$deptData = D('TbHrDept')->where($where)->select();
		$deptids = array_column($deptData,'ID');
		
		$deptAllData = D('TbHrDept')->select();
		//处理pid键值
		foreach ($deptAllData as $k1 => &$v1) {
			$v1['PID'] = $v1['PAR_DEPT_ID'];
			unset($v1['PAR_DEPT_ID']);
		}
		foreach ($deptids as  $vid) {
			$arr = genCate($deptAllData,$vid);
			foreach ($arr as $k => $v) {
				$addDept[] = $v; 
			}
		}
		$deptid1 = array_column($addDept, 'ID');
		$deptid = array_unique(array_merge($deptids, $deptid1));
		$deptid = $deptid?$deptid:$deptids;
		$typearr = [1,'0'];
		$where['TYPE'] =array('NEQ',2);
		$where['ID1'] = array('in',$deptid);  
		$relation = M('hr_empl_dept','tb_')->where($where)->select();
		//var_dump($relation);die;
		foreach ($relation as $k => $v) {
			$leaderid[] = $v['ID2'];
		}
		$where['EMPL_ID'] = array('in',$leaderid);
		$where['STATUS'] = array('NEQ','离职');
		$leaderdata = D('TbHrCard')->field('EMP_SC_NM')->where($where)->select();
		$leaderdata = array_column($leaderdata, 'EMP_SC_NM');
		if (count($leaderdata)>0) {
			$code = 200;
			$msg = 'success';
			$res = $leaderdata;
		}else{
			$code = 500;
			$msg = 'error';
			$res = '当前选中部门无人员';
		}
		return $this->changeJson($code,$msg,$res);
	}

	//获取职位状态、英文名称,职位id(修改使用)
	public function enJob($znJob)
	{
		$jobData = M('ms_cmn_cd','tb_')->where("CD_VAL="."'".$znJob."'")->find();
		if (!$jobData) {
			$jobData = D('TbHrJobs')->field('ID,CD_VAL,ETC,USE_YN')->where("CD_VAL="."'".$znJob."'")->find();
		}
		if ($jobData['USE_YN']=='Y') {
			$jobData['USE_YN'] = '已生效';
		}else{
			$jobData['USE_YN'] = '未生效';
		}
		$id = $jobData['CD']?$jobData['CD']:$jobData['ID'];
		$jobinfo = array('id'=>$id,'etc'=>$jobData['ETC'],'USE_YN'=>$jobData['USE_YN']);
		return $this->changeJson(200,'success',$jobinfo);
	}
	//log
	public function getlogData($resId)
	{
		//$data =  D('TbHrResumeOperationLog')->->where('RESUME_ID='.$resId)->order('CREATE_TIME desc')->limit(1)->select();
		//var_dump($data);die;
		if ($data = D('TbHrResumeOperationLog')->where('RESUME_ID='.$resId)->order('CREATE_TIME desc')->select()) {
			//var_dump($data);die;
			$data = D('TbHrDept')->getDeptName($data);
			$code = 200;
			$msg = 'success';
			$arr_out =array();
			foreach ($data as $k => $v) {
				 $key_out = date('Y-m-d',strtotime($v['CREATE_TIME'])) ; //提取内部一维数组的key(name age)作为外部数组的键  
                    if(array_key_exists($key_out,$arr_out)){  
                        continue;  
                      }
                    else{  
                        $arr_out[$key_out] = $data[$k]; //以key_out作为外部数组的键  
                        $arr_wish[$k] = $data[$k];  //实现二维数组唯一性  
                    }  
			}
		
			//$arr = array_column($arr_wish, 'CREATE_TIME');
			$res = $arr_wish;
		return $this->changeJson($code,$msg,$res);
		
	}
}
	//job
	public function setJob($jobData)
	{
		$arr = array('N00163','N00164','N00165');
		$codedata = M('ms_cmn_cd','tb_')->field('CD_VAL')->where("left(CD,6) in ('N00165','N00164','N00163')")->select();
		$codedata = array_column($codedata, 'CD_VAL');
		$jobdata =  D('TbHrJobs')->field('CD_VAL')->select();
		$jobdata = array_column($jobdata, 'CD_VAL');
		$JOBS =  array_merge($jobdata,$codedata);
		if ($jobData['USE_YN']=='已生效') {
			$jobData['USE_YN'] = 'Y';
		}else{
			$jobData['USE_YN'] = 'N';
		}
			if ($data = D('TbHrJobs')->create($jobData)) {
				/*if (in_array($data['CD_VAL'], $JOBS)) {
					$code = 500;
					$msg ='error';
					$res = '职务名称已存在';
					return $this->changeJson($code,$msg,$res);exit();
				}*/
				$jobName = $jobData['CD_VAL'];
				$res1 = D('TbHrJobs')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='Y'")->find();
				if (M('ms_cmn_cd','tb_')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='Y'")->find()||$res1) {
					return $this->changeJson(500,'error','编辑失败,修改职位名称已存在');exit();
				}
				if (D('TbHrJobs')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='N'")->find()) {
					return $this->changeJson(400,'error','编辑失败,该岗位已存在(状态未生效),是否设置为已生效?');exit();
				}
				if (M('ms_cmn_cd','tb_')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='N'")->find()||$res1) {
					return $this->changeJson(400,'error','编辑失败,该岗位已存在(状态未生效),是否设置为已生效?');exit();
				}

				if (!D('TbHrJobs')->add($data)) {
					$code = 500;
					$msg ='error';
					$res = '添加失败';
					return $this->changeJson($code,$msg,$res);exit();
				}
			}else{
				$res = D('TbHrJobs')->getError();
				$code = 500;
				$msg = 'error';
				return $this->changeJson($code,$msg,$res);exit();
			}
			return $this->changeJson(200,'success','添加成功');exit();
	}

	public function editJob($jobData)
	{
		//var_dump($jobData);die;
		$jobName = $jobData['CD_VAL'];
		$res1 = D('TbHrJobs')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='Y'")->find();
		$res2 = M('ms_cmn_cd','tb_')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='Y'")->find();
		$id = $res1['ID']?$res1['ID']:$res2['CD'];
		if ($id!=$jobData['ID']) {

			if ($res2||$res1) {
				return $this->changeJson(500,'error','编辑失败,修改职位名称已存在');exit();
			}
			if (D('TbHrJobs')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='N'")->find()) {
				return $this->changeJson(400,'error','编辑失败,该岗位已存在(状态未生效),是否设置为已生效?');exit();
			}
			if (M('ms_cmn_cd','tb_')->where('CD_VAL='."'".$jobName."'"."AND USE_YN ='N'")->find()||$res1) {
				return $this->changeJson(400,'error','编辑失败,该岗位已存在(状态未生效),是否设置为已生效?');exit();
			}
		}
		

		$jobid = $jobData['ID'];
		if (M('ms_cmn_cd','tb_')->where('CD='."'".$jobid."'")->find()) {
			if ($jobData['USE_YN']=='已生效') {
				$jobData['USE_YN']='Y';
			}elseif ($jobData['USE_YN']=='未生效') {
				$jobData['USE_YN']='N';
			}
			M('ms_cmn_cd','tb_')->where('CD='."'".$jobid."'")->save($jobData);
		}else{
			if ($jobData['USE_YN']=='已生效') {
				$jobData['USE_YN']='Y';
			}elseif ($jobData['USE_YN']=='未生效') {
				$jobData['USE_YN']='N';
			}
			$jobData1 = D('TbHrJobs')->create($jobData,1);
			D('TbHrJobs')->where('ID='."'".$jobid."'")->save($jobData1);
		}
		//修改该人员应聘职位
		$resumeid = $jobData['resumeId'];
		$editdata['JOBS'] = $jobName;
		//var_dump($jobName);die;
		
		D('TbHrResume')->where('ID='."'".$resumeid."'")->save($editdata);
		return $this->changeJson(200,'success','修改成功');exit();

	}

	public function waitData($page)
	{

		$start = ($page['pagenow']-1)*$page['pageSize'];
		$pageSize = $page['pageSize'];
		//$loginName = $_SESSION['m_loginname'];
		$ZnName = A("HrMeeting","Aapi",true);
		$loginName = $ZnName->getEnName();
		$count = $this->recruitModel->where("JOB_TIME2 ='' AND IS_NOT_ARRANGE !=1 AND NAME1 ="."'".$loginName."'")->count();
		if ($data = $this->recruitModel
			->where("JOB_TIME2 ='' AND IS_NOT_ARRANGE !=1 AND NAME1 ="."'".$loginName."'")
			->order('JOB_DATE1 asc,JOB_TIME1 asc')
			->limit($start,$pageSize)->select()) {
			foreach ($data as $k => $v) {
				$data[$k]['IDS'] = $start+$k+1;
			}
			$data = D('TbHrDept')->getDeptName($data);

			$code = 200;
			$msg = $count;
			$res = $data;
		}
		//echo $this->recruitModel->_sql();die;
		return $this->changeJson($code,$msg,$res);
	}

	public function CommunData($page)
	{
		$start = ($page['pagenow']-1)*$page['pageSize'];
		$pageSize = $page['pageSize'];
		$showDay = date('Y-m-d', strtotime('+8 days'));
		$showDay1 = date('Y-m-d', strtotime('-7 days'));
		$nowDay = date('Y-m-d',strtotime('+1 days'));
		$facetime = array(array('egt',$showDay1),array('lt',$showDay), 'and');
		$updatetime = array(array('egt',$showDay1),array('lt',$nowDay), 'and');

		//$loginName = $_SESSION['m_loginname'];
		$ZnName = A("HrMeeting","Aapi",true);
		$loginName = $ZnName->getEnName();
		$where1=array('IS_NOT_ARRANGE'=>1,'UPDATE_TIME'=>$updatetime,'NAME1='."'".$loginName."'",'_logic'=>'and');
		$where2=array('JOB_DATE2'=>$facetime,'_complex'=>$where1,'_logic'=>'or');
		$where = array('_logic'=>'AND','_complex'=>$where2,'NAME1='."'".$loginName."'");

		//未勾选不安排的
		/*$where = array('IS_NOT_ARRANGE'=>0,'JOB_DATE2'=>$facetime,'_logic'=>'and');
		$ArrangeData = $this->recruitModel->where($where)->order('JOB_DATE2 desc')->limit($start,$pageSize)->select();

		$where = array('IS_NOT_ARRANGE'=>1,'JOB_DATE2'=>$updatetime,'_logic'=>'and');
		$ArrangeData = $this->recruitModel->where($where)->order('UPDATE_TIME desc')->limit($start,$pageSize)->select();*/
		

		$count = $this->recruitModel->where($where)->count();
		if ($data = $this->recruitModel->where($where)->order("JOB_DATE2 asc,JOB_DATE2 asc")->limit($start,$pageSize)->select()) {
			//echo $this->recruitModel->_sql();die;
			$pageSize = $page['pageSize'];
			foreach ($data as $k => $v) {
				$data[$k]['IDS'] = $start+$k+1;
			}
			$data = D('TbHrDept')->getDeptName($data);
			$code = 200;
			$msg = $count;
			$res = $data;
		}
		//echo D('TbHrResume')->_sql();die;
		return $this->changeJson($code,$msg,$res);
	}
	public function setUse($data)
	{
		$res = D('TbHrJobs')->where('CD_VAL='."'".$data['CD_VAL']."'")->setField('USE_YN','Y');
		if ($res ||M('ms_cmn_cd','tb_')->where('CD_VAL='."'".$data['CD_VAL']."'")->setField('USE_YN','Y')) {
			return $this->changeJson(200,'success','设置成功');
		}
	}

}


 ?>