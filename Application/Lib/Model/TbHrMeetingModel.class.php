<?php 
/**
* user:huanzhu
* date:2017/11/3
* info:meeeting model
*/
class TbHrMeetingModel extends BaseModel
{
    protected $trueTableName = 'tb_hr_meeting';
    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];
    protected $_validate = [
        ['MEETING_THEME','require','请填写主题'],//默认情况下用正则进行验证
        ['RECORD_MAN','require','请选择会议记录人'],//默认情况下用正则进行验证
      //  ['MEETING_PLACE','require','请选择会议地点'],//默认情况下用正则进行验证
        ['PARTCIPANT','require','请选择参与人'],//默认情况下用正则进行验证
        ['MEETING_DATE','require','请填写会议日期'],//默认情况下用正则进行验证
        ['MEETING_TIME','require','请填写会议时间'],//默认情况下用正则进行验证
        ['STATUS','require','请填写会议状态'],//默认情况下用正则进行验证
    ];
    protected function formatTimes($arr=array(),$key='',$page)
    { 
        $sort = $page+1;
        foreach ($arr as $k => $v) {
            if($key=='MEETING_DATE') $arr[$k][$key] = cutting_time($v[$key]); 
            if($key=='MEETING_TIME') $arr[$k][$key] = cutting_hour($v[$key]);
            $arr[$k]['sort'] = $sort;
            $sort++;
            
            if ($v['STATUS']=='完成') {
                //会议的状态完成下所属事项状态联动修改
                $childStatus = D('TbHrWaitThings')->where('PID='.$v['WAIT_ID'])->find();
                $status = $childStatus['STATUS'];
                if ($status!= "完成") {

                    $linkChange = D('TbHrWaitThings')->where('PID='.$v['WAIT_ID'])->setField('STATUS','完成');
                    //echo D('TbHrWaitThings')->_sql();
                }
            }
        }
  
        return $arr;
    }

    //列表数据
    public function getData()
    {
        $data = Mainfunc::chooseParam('param');
        $data['keyValue'] = trim($data['keyValue']);
        //safe validate
        Mainfunc::SafeFilter($data);
        $where = $this->getwhere($data);
        $pageSize = $data['pageSize']?$data['pageSize']:20;
        $sePage = $data['pagenow'];
        $meetCount = $this->where($where)->count();
        //echo $meetCount;die;

        $meetingData = $this
        ->where($where)
        ->order('STATUS desc, MEETING_DATE desc,MEETING_TIME desc')
        ->limit(($sePage-1)*$pageSize,$pageSize)
        ->select();




        $meetingData = $this->formatTimes($meetingData,'MEETING_DATE',$page);
        $meetingData = $this->formattimes($meetingData,'MEETING_TIME');
        
        if(is_null($meetingData)){
          $meetingData['count'] = 0;  
      }else{
        $meetingData['count'] = $meetCount;
      }
      $meetingData = is_array($meetingData)?$meetingData:array(); 
        return $meetingData;
    }

    //条件筛选
    public function getwhere($data)
    {
        if (!empty($data['meetStatus'])) 
        $where['STATUS'] =  array('in',$data['meetStatus']);
        if (empty($data['timeType'])) 
        $data['timeType'] = 'MEETING_DATE';
        if (!empty($data['startMeetTime']) and !empty($data['endMeetTime'])) {
            $where[$data['timeType']] = array(array('ELT',cutting_time($data['endMeetTime'])),array('EGT',cutting_time($data['startMeetTime'])),'and');
        }
        if (!empty($data['startMeetTime']) and empty($data['endMeetTime'])) {
            $where[$data['timeType']] = array(array('EGT',cutting_time($data['startMeetTime'])));
        }
        if (empty($data['startMeetTime']) and !empty($data['endMeetTime'])) 
            $where[$data['timeType']] = array(array('ELT',cutting_time($data['endMeetTime'])));
        if (empty($data['keyWord'])) 
            $data['keyWord'] = "RECORD_MAN";
        $where[$data['keyWord']] = array("like","%{$data['keyValue']}%");
        $where = is_array($where)?$where:array();
        return $where;
        
    }

    //会议详情数据
    public function getDetail()
    {
        $id = Mainfunc::chooseParam('id');
        if (!$detail = $this->where('ID='.$id)->find()) {
            $outputs['code'] = 500;
            $outputs['msg'] = 'error';
            return $outputs;
         }
         if(isset($detail['PARTCIPANT'])) $detail['PARTCIPANT'] = explode(',', $detail['PARTCIPANT']);
        return $detail;
    }

    //增加修改数据  同时修改/增加事项表
    public function operation()
    {
        $meetData = Mainfunc::chooseParam('param');
        if(isset($meetData['PARTCIPANT'])&&is_array($meetData['PARTCIPANT'])) $meetData['PARTCIPANT'] =implode(',',$meetData['PARTCIPANT']);
        $meetData['MEETING_DATE'] = $meetData['MEETING_DATE']?$meetData['MEETING_DATE']:'';
        $meetData['MEETING_TIME'] = $meetData['MEETING_TIME']?$meetData['MEETING_TIME']:'';
        
         if (!$meetData = D('TbHrMeeting')->create($meetData,1)) {
            $outputs['code'] = 500;
            $outputs['msg'] = $this->getError();
            return $outputs;
         } 
         //处理数据作为待办事项
            $waitNewData = D('TbHrWaitThings')->create($meetData,1);
            $waitNewData['THINGS_THEME'] = $meetData['MEETING_THEME'];
            $waitNewData['END_DATE'] = cutting_time($meetData['MEETING_DATE']);
            $waitNewData['END_TIME'] = cutting_hour($meetData['MEETING_TIME']);
            $waitNewData['FOLLOW_MAN'] = $meetData['PARTCIPANT'];
            $waitNewData['PID'] = 1;
        if ($meetData['ID']) {  //修改
            if (!$res=$this->where('ID='.$meetData['ID'])->save($meetData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = 'save error,会议记录未作改动';
                return $outputs;
            }
            if ($meetData['WAIT_ID']) {
                unset($waitNewData['ID']);
                //有事项id情况下联动修改
                if($meetData['WAIT_ID']){
                    $waitRes = D('TbHrWaitThings')->where('ID='.$meetData['WAIT_ID'])->save($waitNewData);   
                }
               
            }
        }else{   //增加
            if (!$res=$this->add($meetData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = 'add error';
                return $outputs;
            }
            //echo $res;die;
            if (!$waitRes = D('TbHrWaitThings')->add($waitNewData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = '事项新建error';
                return $outputs;
            }
            //生成会议的事项id
            //echo $waitRes;
            if (!$waitidres = $this->where('ID='.$res)->setField('WAIT_ID',$waitRes)) {
                $outputs['code'] = 500;
                $outputs['msg'] = '未生成事项关联id';
                return $outputs;
            }
            

        }
        return $res;
    }

    //删除数据
    public function del()
    {
        $id = Mainfunc::chooseParam('params');
        if (is_array($id)) {
            $where = 'ID in('.implode(',',$id).')';  
        }
        if (!$res = $this->where($where)->delete()) {
            $outputs['code'] = 500;
            $outputs['msg'] = 'del error';
            return $outputs; 
        }
        return $res;
    }
    //保存、编辑事项数据
    public function saveWait()
    {
        $waitParams = Mainfunc::chooseParam('params');
        //echo "<pre>";
        //var_dump($waitParams);die;
        //var_dump($waitParams['waitData']['THINGS_THEME']);die;
        //$waitParams['waitData']['THINGS_THEME'] = str_replace("1","123",$waitParams['waitData']['THINGS_THEME']);
        //var_dump($waitParams);die;
        $waitId = $waitParams['waitData']['WAIT_ID']?$waitParams['waitData']['WAIT_ID']:null;
        $waitData = $waitParams['waitData'];
        //处理生成该事项的pid(即为该会议生成的事项id)
        $meetid = $waitData['MEETING_ID'];
        $meetdata = $this->where('ID='.$meetid)->find();
        $waitData['PID'] = $meetdata['WAIT_ID'];

        $waitData['FOLLOW_MAN'] = implode(',',$waitData['ARR_FOLLOW_MAN']);

        if (!$waitData = D('TbHrWaitThings')->create($waitData,1)) {
            $outputs['msg'] = D('TbHrWaitThings')->getError();
            $outputs['code'] = 500;
            return $outputs;
        }
        $waitData['END_DATE'] = cutting_time($waitData['END_DATE']);
        if (is_null($waitId)) {
            //新增
            unset($waitData['ID']);
            if (!$res = D('TbHrWaitThings')->add($waitData)) {
                $outputs['msg'] = 'add error';
                $outputs['code'] = 500;
                return $outputs;
            }
        }else{
            //修改
            if (!$res = D('TbHrWaitThings')->where("ID=".$waitId)->save($waitData)) {
                echo D('TbHrWaitThings')->_sql();die;
                $outputs['msg'] = 'edit error';
                $outputs['code'] = 500;
                return $outputs;
            }

        }
        
        return $res;
     }
     //当前待办事项列表
     public function waitThingsData()
     {
         $meetid = Mainfunc::chooseParam('id');
         if (!$detail = D('TbHrWaitThings')->where('MEETING_ID='.$meetid)->select()) {
            $outputs['code'] = 500;
            $outputs['msg'] = 'error';
            return $outputs;
    }

         //俩种形态
         foreach ($detail as $k => $v) {
             $detail[$k]['ARR_FOLLOW_MAN'] = explode(',', $detail[$k]['FOLLOW_MAN']);
             $detail[$k]['WAIT_ID'] = $detail[$k]['ID'];
             unset($detail[$k]['ID']);

             //echo $detail[$k]['THINGS_THEME'];
         }

         return $detail;
     }
     //删除
     public function delWait()
     {
         $data = Mainfunc::chooseParam('params');
         if (isset($data['WAIT_ID'])) {
             if(!$res = D('TbHrWaitThings')->where('ID='.$data['WAIT_ID'])->delete()){
                $outputs['code'] = 500;
                $outputs['msg'] = 'del error';
                return $outputs;
             } 
         }

         return $res;
     }
     //修改状态
     public function changeStatus()
     {
         $data = Mainfunc::chooseParam('param');
         $ids = $data['ids'];
         $status =$data['status']?$data['status']:null;
         if (empty($status)) {
             $outputs['code'] = 500;
             $outputs['msg'] = '未选择状态';
         }
         if($status=='has') $status='完成';
         if($status=='none') $status='待跟进';
         $where['ID'] = array('in',$ids);
         $res = $this->where($where)->setField('STATUS',$status);
         if (!$res) {
             $outputs['code'] = 500;
             $outputs['msg'] = '批量修改失败';
         }
         $where['ID'] = array('in',$data['ids']);
         $meetData = $this->where($where)->select();
         $datastatus['STATUS'] = $status;
         foreach ($meetData as $k => $v) {
            //有事项id情况下联动修改
            if($v['WAIT_ID']){
                $waitRes = D('TbHrWaitThings')->where('ID='.$v['WAIT_ID'])->save($datastatus);   
            }
         }
        
         return $res;
     }
     //导出
     public function exportMeet()
     {
        $export = new ExportsMeet();
        //$export->title = L('会议记录' );
        $export->fileName = L('会议记录查询');
        $export->data = $this->formatData();
        if ($export->getError()) {
            $this->error($export->getError());
        }
        $export->export();
     }

     public function formatData()
     {
       // 数据
        $ids = Mainfunc::chooseParam('param');
        $arrId = explode(',', $ids);
        foreach ($arrId as $v) {
            $dataMeetData = $this
            ->field('ID,tb_hr_meeting.MEETING_THEME,
              tb_hr_meeting.RECORD_MAN,
              tb_hr_meeting.MEETING_PLACE,
              tb_hr_meeting.PARTCIPANT,
              tb_hr_meeting.MEETING_DATE,
              tb_hr_meeting.MEETING_TIME,
              tb_hr_meeting.STATUS,
              tb_hr_meeting.MEETING_CONTENT')
            ->where('tb_hr_meeting.ID='.$v)
            ->find(); 
            $childWaitData = D("TbHrWaitThings")->where('MEETING_ID='.$dataMeetData['ID'])->select();
            $count = 1;
            foreach ($childWaitData as $key => $value) {
                $childWaitData[$key]['WAIT_ID'] = $count;
                $count++;
                $childWaitData[$key]['wRECORD_MAN'] = $value['RECORD_MAN'];
                $childWaitData[$key]['wSTATUS'] = $value['STATUS'];
              unset($childWaitData[$key]['STATUS']);
              unset($childWaitData[$key]['RECORD_MAN']);
            }
            $dataMeetData['childWait'] = $childWaitData;
            $dataMeetData['MEETING_CONTENT'] = strip_tags($dataMeetData['MEETING_CONTENT']); 
            $dataMeet[] = $dataMeetData; 
        }
        return $dataMeet;
     }
}

/**
* 继承export类重写参数属性方法
*/
class  ExportsMeet extends ExportExcelModel
{
    public $columns;

    public $attributes = [
        'A' => ['name' =>'会议主题', 'field_name' => 'MEETING_THEME'],
        'B' => ['name' => '会议记录人', 'field_name' => 'RECORD_MAN'],
        'C' => ['name' => '会议地点', 'field_name' => 'MEETING_PLACE'],
        'D' => ['name' => '会议参与人', 'field_name' => 'PARTCIPANT'],
        'E' => ['name' => '会议日期', 'field_name' => 'MEETING_DATE'],
        'F' => ['name' => '会议时间', 'field_name' => 'MEETING_TIME'],
        'G' => ['name' => '会议状态', 'field_name' => 'STATUS'],
        //'H' => ['name' => '会议纪要', 'field_name' => 'MEETING_CONTENT'],
    ];
    public function __construct()
    {
        parent::__construct();
    }

    public function setColumnStyle()
    {
        return [
            'font' => [
                'color' => [
                    'argb' => 'FFFFFFFF'
                ]
            ],
        ];
    }

    public function setColumnTitle()
    {
        $this->phpExcel->getActiveSheet()->setTitle('会议跟进');
        if ($this->title) {
            $this->startLine = 3;
        }
        $index = $this->startLine - 1;
         $this->phpExcel->getActiveSheet()->getRowDimension($index)->setRowHeight(20);
        foreach ($this->getMaxTitle() as $key => $value) {
            $this->sheetObject->setCellValue($key . $index, $value ['name']);
            $this->phpExcel->getActiveSheet()->getStyle($key.$index)->applyFromArray($this->setColumnStyle());
            $this->phpExcel
            ->getActiveSheet()
            ->getStyle($key.$index)->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('#000000');
        }
    }



    public function getMaxTitle()
    {
      $max = 0;
      foreach ($this->data as $key => $value) {
        $columns = $this->columnName();
              //定义子数据
              foreach ($value['childWait'] as $k => $v) {
                $childColumnName = [
                   ['name'=>'事项编号','field_name'=>"WAIT_ID".$k],
                  [ 'name'=>'待办事项主题','field_name'=>'THINGS_THEME'.$k],
                  [ 'name'=>'记录人','field_name'=>'wRECORD_MAN'.$k],
                  [ 'name'=>'截止时间','field_name'=>'END_DATE'.$k],
                  [ 'name'=>'跟进人','field_name'=>'FOLLOW_MAN'.$k],
                  [ 'name'=>'状态','field_name'=>'wSTATUS'.$k],
                ];
                $columns = array_merge($columns,$childColumnName);
            }
            $keys = [];
              for ($i='A'; $i <='Z' ; $i++) { 
                if(count($keys)<count($columns))
                  $keys[] = $i;
              }
            $columns = array_combine($keys, array_values($columns));
            if(count($columns)>$max) $max = count($columns);
            if(count($columns)==$max) $maxcolumns = $columns;
       }
       return $maxcolumns;
    } 


    public function parseData()
    {
      
        $width = [];
        if (!$this->data) {
            throw new PHPExcel_Writer_Exception(101);
            return false;
        }
        
        foreach ($this->data as $key => $value) {
          $columns = $this->columnName();
              foreach ($value['childWait'] as $k => $v) {
                $childColumnName = [
                  [ 'name'=>'事项编号','field_name'=>"WAIT_ID".$k],
                  [ 'name'=>'待办事项主题','field_name'=>'THINGS_THEME'.$k],
                  [ 'name'=>'记录人','field_name'=>'wRECORD_MAN'.$k],
                  [ 'name'=>'截止时间','field_name'=>'END_DATE'.$k],
                  [ 'name'=>'跟进人','field_name'=>'FOLLOW_MAN'.$k],
                  [ 'name'=>'状态','field_name'=>'wSTATUS'.$k],
                ];
                $columns = array_merge($columns,$childColumnName);
            }
              $keys = [];
              for ($i='A'; $i <='Z' ; $i++) { 
                if(count($keys)<count($columns))
                  $keys[] = $i;
              }
            $columns = array_combine($keys, array_values($columns));
           
            $index = 0;
            $value1 = $value['childWait'];
            unset($value['childWait']);
            
            foreach ($value1 as $k2 => $v2) {
              foreach ($v2 as $k3 => $v3) {
                $childValue[$k3.$k2] = $v3; 
              }
            }

            foreach ($columns as $k => $v) {
                  if(!is_null($value[$v['field_name']])) {
                    $this->sheetObject->setCellValueExplicit($k . $this->startLine, $value[$v['field_name']], PHPExcel_Cell_DataType::TYPE_STRING);  
                  }else{
                    $this->sheetObject->setCellValueExplicit($k . $this->startLine, $childValue[$v['field_name']], PHPExcel_Cell_DataType::TYPE_STRING); 
                  }
                  $tmp = $value[$v['field_name']]?$value[$v['field_name']]:$childValue[$v['field_name']];
                  if (strlen($tmp) + 10 > $width [$k]) {
                      $width [$k] = strlen($tmp) + 10;
                        $this->sheetObject->getColumnDimension($k)->setWidth(strlen($tmp) + 10);  
                  }
                  $index ++;
            }
            $this->startLine ++;
        }
    }
}