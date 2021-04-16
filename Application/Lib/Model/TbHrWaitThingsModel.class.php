<?php 
/**
* user:huanzhu
* date:2017/11/9
* info:waitThing model
*/

class TbHrWaitThingsModel extends BaseModel
{
	protected $trueTableName = 'tb_hr_wait_things';
    protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];
    protected $_validate = [
        ['THINGS_THEME','require','请填写跟进事项主题内容'],//默认情况下用正则进行验证
        ['END_DATE','require','请填写截止日期'],
        //['END_TIME','require','请选择截止时间'],//默认情况下用正则进行验证
        ['FOLLOW_MAN','require','请选择跟进人'],//默认情况下用正则进行验证
        ['STATUS','require','请选择待办事项状态'],//默认情况下用正则进行验
    ];

    //处理数据格式
    public function formatData($data = array())
    {
        foreach ($data as $k => $v) {
          if ($v['STATUS']=='完成') {
              //联动修改
              $childStatus = $this->where('PID='.$v['ID'])->find();
                $status = $childStatus['STATUS'];
                if ($status!= "完成") {
                    $linkChange = $this->where('PID='.$v['ID'])->setField('STATUS','完成');
                }
          }
        }
        return $data;
    }


    //列表数据
    public function getWaitingList()
    {
        $data = Mainfunc::chooseParam('param');
        $startPage = ($data['pagenow']-1)*$data['pageSize'];
        $where = $this->getwhere($data);
        $showData= $this
        ->where('PID=1')
        ->where($where)
        ->order("STATUS desc,END_DATE desc")
        ->limit($startPage,$data['pageSize'])
        ->select();
        //处理数据
        $showData = $this->formatData($showData);
        //echo $this->_sql();die;
        $count = $this
        ->where('PID=1')
        ->where($where)
        ->count();
        $childData = $this
        ->where("PID!=1")
        ->where($where)
        ->order("CREATE_TIME desc")
        ->select();
        //主题数据
        $res['show'] = $showData;
        //内容数据
        $res['child'] = $childData;
        $res['count'] = $count;
        return $res;
    }


    public function getwhere($data)
    {
        if (!empty($data['meetStatus']))  $where['STATUS'] =  array('in',$data['meetStatus']);
        if (empty($data['timeType']))  $data['timeType'] = 'END_DATE';
        if (!empty($data['startMeetTime']) and !empty($data['endMeetTime'])) {
            $where[$data['timeType']] = array(array('ELT',cutting_time($data['endMeetTime'])),array('EGT',cutting_time($data['startMeetTime'])),'and');
        }
        if (!empty($data['startMeetTime']) and empty($data['endMeetTime'])) {
            $where[$data['timeType']] = array(array('EGT',cutting_time($data['startMeetTime'])));
        }
        if (empty($data['startMeetTime']) and !empty($data['endMeetTime']))  $where[$data['timeType']] = array(array('ELT',cutting_time($data['endMeetTime'])));

        if (empty($data['keyWord'])) $data['keyWord'] = "RECORD_MAN";
        $where[$data['keyWord']] = array("like","%{$data['keyValue']}%");
        $where = is_array($where)?$where:array();
        return $where;
        
    }


    //增加、修改数据
    public function operationData()
    {
        $newFormData = Mainfunc::chooseParam('params');
        if (isset($newFormData['waitData'])) {
            $newFormData = $newFormData['waitData'];
        }
        if (is_array($newFormData['FOLLOW_MAN'])) {
            $newFormData['FOLLOW_MAN'] = implode(',', $newFormData['FOLLOW_MAN']);    
        }
        
        $newFormData['END_DATE'] = $newFormData['END_DATE']?$newFormData['END_DATE']:'';


        if (!$newFormData =$this->create($newFormData,1)) {
            $outputs['code'] = 500;
            $outputs['msg'] = $this->getError(); 
            return $outputs;
        }
        
        $newFormData['END_DATE'] = cutting_time($newFormData['END_DATE'])?cutting_time($newFormData['END_DATE']):'';
        $newFormData['END_TIME'] = cutting_hour($newFormData['END_TIME'])?cutting_hour($newFormData['END_TIME']):'';
        
        $meetForm = D('TbHrMeeting')->create($newFormData);
        $meetForm['MEETING_THEME'] = $newFormData['THINGS_THEME'];
        $meetForm['PARTCIPANT'] = $newFormData['FOLLOW_MAN'];
        $meetForm['MEETING_DATE'] = $newFormData['END_DATE'];
        $meetForm['MEETING_TIME'] = $newFormData['END_TIME'];

        if (empty($newFormData['ID'])) {
            if(empty($newFormData['PID'])){
                $newFormData['PID'] = 1;    
            }
            //add
            if (!$res = $this->add($newFormData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = 'add error'; 
                return $outputs;
            }
        }else{
            //edit
            if (!$res =$this->where('ID='.$newFormData['ID'])->save($newFormData)) {
                $outputs['code'] = 500;
                $outputs['msg'] = 'error edit';
                return $outputs;
            }
            //联动
            $meeRes = D('TbHrMeeting')->where('WAIT_ID='.$newFormData['ID'])->save($meetForm);

        }
        return $res;
    }

    //获取事项主题主题数据
    public function getWaitDet()
    {
        $id = Mainfunc::chooseParam('id');

        if (!$title = $this->where('ID='.$id)->find()) {
            $outputs['code'] = 500;
            $outputs['msg'] = 'error';
            return $outputs;
         }
         if(isset($title['FOLLOW_MAN'])) $title['FOLLOW_MAN'] = explode(',', $title['FOLLOW_MAN']);

         $contents = $this->where("PID=".$id)->order("CREATE_TIME desc")->select();
         foreach ($contents as $k => $v) {
             $contents[$k]['END_DATE'] = cutting_time(',', $v['END_DATE'])?cutting_time(',', $v['END_DATE']):'';
         }
         $detail['title'] = $title;
         $detail['contents'] = $contents?$contents:array();
        return $detail;
    }


    public function delContent()
    {
        $data = Mainfunc::chooseParam('params');
         if (isset($data['ID'])) {
             if(!$res = $this->where('ID='.$data['ID'])->delete()){
                $outputs['code'] = 500;
                $outputs['msg'] = 'del error';
                return $outputs;
             } 
         }
         return $res;
    }

    //批量删除
    public function batchDelWait()
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
    //导出(自封装)
    /*public function waitExport()
    {
        $ids = Mainfunc::chooseParam('param');
        $arr = [
            ['label'=>'待办事项主题/内容','field'=>'THINGS_THEME'],
            ['label'=>'事项记录人','field'=>'RECORD_MAN'],
            ['label'=>'截止日期','field'=>'END_DATE'],
            ['label'=>'截止时间','field'=>'END_TIME'],
            ['label'=>'跟进人','field'=>'FOLLOW_MAN'],
            ['label'=>'事项状态','field'=>'STATUS'],
         ];
        $excelOperation = new ExcelOperation();
        $excelOperation->title = '待办事项';
        $excelOperation->filename = 'waiting.xls';
        $excelOperation->table_name = 'TbHrWaitThings';
        $excelOperation->matchKeyTitle = $arr;
        $excelOperation->export($ids);
    }*/

    //导出(增加子数据)
    public function waitExport()
    {
        $export = new ExportsWait();
        $export->fileName = L('待办事项记录');
        $export->data = $this->getExportData();
        if ($export->getError()) {
            $this->error($export->getError());
        }
        $export->export();
    }

    public function getExportData()
     {
       // 数据
        $ids = Mainfunc::chooseParam('param');
        $arrId = explode(',', $ids);
        foreach ($arrId as $v) {
            $dataWaitData = $this
            ->field('ID,THINGS_THEME,RECORD_MAN,END_DATE,END_TIME,FOLLOW_MAN,STATUS')
            ->where('PID =1 AND tb_hr_wait_things.ID='.$v)
            ->find(); 
            //echo "<pre>";
           // echo $this->_sql();die;
            //var_dump($dataWaitData);die;
            $childWaitData = $this->where('PID='.$dataWaitData['ID'])->select();


            $count = 1;
            foreach ($childWaitData as $key => $value) {
                    $childWaitData[$key]['WAIT_ID'] = $count;
                    $count++;
                //$childWaitData[$key]['WAIT_ID'] = $count;
                $childWaitData[$key]['wRECORD_MAN'] = $value['RECORD_MAN']; 
                $childWaitData[$key]['wSTATUS'] = $value['STATUS']; 
              unset($childWaitData[$key]['STATUS']);
              unset($childWaitData[$key]['RECORD_MAN']);
            }
            $dataWaitData['childWait'] = $childWaitData;
            $dataWaitData['MEETING_CONTENT'] = strip_tags($dataMeetData['MEETING_CONTENT']); 
            $dataWait[] = $dataWaitData;

        }


        return $dataWait;
     }

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
         //联动
         $where1['WAIT_ID'] = array('in',$ids);
         $dataStatus['STATUS'] = $status;
         $meetRes = D("TbHrMeeting")->where($where1)->setField('STATUS',$status);
         return $res;
    }

}


/**
* 继承export类重写参数属性方法
*/
class  ExportsWait extends ExportExcelModel
{
    public $columns;

    public $attributes = [
        'A' => ['name' =>'事项主题', 'field_name' => 'THINGS_THEME'],
        'B' => ['name' => '会议记录人', 'field_name' => 'RECORD_MAN'],
        'C' => ['name' => '结束时间', 'field_name' => 'END_DATE'],
        //'D' => ['name' => '会议参与人', 'field_name' => 'END_TIME'],
        'E' => ['name' => '跟进人', 'field_name' => 'FOLLOW_MAN'],
        //'F' => ['name' => '会议时间', 'field_name' => 'MEETING_TIME'],
        'G' => ['name' => '事项状态', 'field_name' => 'STATUS'],
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
        $this->phpExcel->getActiveSheet()->setTitle('待办事项');
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