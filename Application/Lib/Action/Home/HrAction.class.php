<?php 
/**
 * Created by sublime.
 * User: b5m
 * Date: 17/8/7
 * Time: 17:55
 * By: huanzhu , huaxin
 */

class HrAction extends BaseAction
{
     private $HrModel;  //数据模型

     public function responseData($code, $msg, $data)
    {
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];

        exit(json_encode($data));
    }

     public function __construct()
    {
        parent::__construct();
        //初始化实例化模型
        $this->HrModel = new TbHrModel();
    }
    /**
     * 人员展示、搜索
     * @param 条件参数
     */
    public function showList(){
        $this->display('showList');
    }
        /**
     * 部门列表
     */
    public function deptTable(){
        $this->display('deptTable');
    }
    
    /**
     *我的名片信息
     *
     */
    public function card(){
        $menu = $_SERVER['QUERY_STRING'];
        $menu .= $_REQUEST['id'];
        $this->assign('menu',$menu);
        $this->display('addPerson');
    }

    /**
     * 职位列表
     * @param 条件参数
     */
    public function job_list(){
        $this->display();
    }


    /**
     *新增人员，同时数据存入名片 未完待续
     * @PersonData 员工信息
     * @childData 员工子信息
     */
    public function addPerson(){
        $menu = $_SERVER['QUERY_STRING'];
        $menu .= $_REQUEST['id'];
        $resId =$_REQUEST['resid'];
        $resumeData = [];
        if (!empty($resId)) {
            $resumeData = D('TbHrResume')->where('ID='.$resId)->find();
        }
        $this->assign('resumeData',$resumeData);
        $this->assign('menu',$menu);
        $this->display('addPerson');
    }

    /**
     * edit人员
     * @PersonData 员工信息
     * @childData 员工子信息
     */
    public function editPerson(){
        $menu = $_SERVER['QUERY_STRING'];
        $menu .= $REQUEST['id'];
        $this->assign('menu',$menu);
        $this->display('addPerson');
    }

    /**
     * 晋升列表页面
     * @param string $value [description]
     */
    public function promotionList()
    {
		$this->display('promotionList');
    }
    
    /**
     * 晋升详情页面
     * @param string $value [description]
     */
    public function promotionDetail()
    {
		$this->display('promotionDetail');
	}

    /**
     * 组织架构首页
     * @param string $value [description]
     */
    public function deptList()
    {
		$this->display('deptList');
	}
	

    /**
     * 简历详情
     * @return [type] [description]
     */
    public function recruit()
    {
       if (!$id = $_GET['resid']) {
           $id = $_GET['resids'];
       }
        ;
        $job = $_GET['job'];
        $this->assign('id',$id);
        $this->assign('job',$job);
        $this->display('recruit');
    }

    /**
     * 新建简历
     * @return [type] [description]
     */
    public function addresume()
    {
        $this->display('addresume');
    }

    /**
     * 简历详情
     */
    public function resDetails()
    {
        $this->display('resDetails');
    }

    /*
     *跟进页面
     */
    public function recuitfollow()
    {
        $this->display('recuitfollow');
    }
    /**
     * 邮件签名
     */
    public function email()
    {
		$this->display('email');
	}
    //会议管理
    public function meeting()
    {
        $this->display('meeting');
    }
    //待办事项
    public function waitthings()
    {
        $this->display('waitthings');  
    }

    public function git_report()
    {
        /*$data = GitReportModel::getData();
        $this->data = $data;*/
        $this->display('git_report');
    }

    //挑选简历
    public function choose_resume()
    {
        $m = D("TbHrResume");
        $ids = explode(",", $_GET['ids']);
        $zn_name = $_GET['zn_name'];
        if (is_array($ids)) {
            $data['NAME1'] = $zn_name;
            $data['JOB_DATE1'] = date("Y-m-d",time());
            $data['JOB_TIME1'] = date("H:i:s",time());
            $data['JOB_DATE2'] = '';
            $data['JOB_TIME2'] = '';
            $data['IS_NOT_ARRANGE'] = '2';
            $data['STATUS'] = '';
            $m->startTrans();
            foreach ($ids as $v) {
                $Data = D("TbHrResume")->where("ID={$v}")->find();
                $logData['RESUME_ID'] = $v;
                $logData['JOB_DATE1'] = date("Y-m-d",time());
                $logData['JOB_TIME1'] =  date("H:i:s",time());
                $logData['JOB_DATE2'] = '';
                $logData['JOB_TIME2'] = '';
                $logData['NAME2'] = $Data['NAME2'];
                $logData['DEPT_ID'] = $Data['DEPT_ID'];
                $logData['JOBS'] = $Data['JOBS'];
                $logData['STATUS'] = '简历被挑选,当前状态清空';
                $logData['JOB_MSG'] = $Data['JOB_MSG'];
                $logData['CREATE_TIME'] = date("Y-m-d H:i:s",time());
                $logData['UPDATE_TIME'] = date("Y-m-d H:i:s",time());
                $logData['CREATE_USER_ID'] = $_SESSION['m_loginname'];
                $logData['CREATE_USER_ID'] = $_SESSION['m_loginname'];

                $logRes = M("hr_resume_operation_log","tb_")->add($logData);
                $res = D("TbHrResume")->where("ID={$v}")->save($data);
                if (!$res or !$logRes) {
                    $this->jsonOut(array("code"=>500,"msg"=>"edit error","data"=>''));
                    $m->rollback();
                 }        
            }    
        }
        $m->commit();
        $this->jsonOut(array("code"=>200,"msg"=>L("操作成功"),"data"=>$res));
    }

    public function git_report_data()
    {
        $query = $this->getParams();
        $data = GitReportModel::getData($query);
        $this->jsonOut(array("code"=>2000,"msg"=>L("操作成功"),"data"=>$data));
    }

    public function get_user_info()
    {
        $nm = I('name');
        $data = GitReportModel::getUserInfo($nm);
        if ($data) {
            $this->jsonOut(array("code"=>2000,"msg"=>L("操作成功"),"data"=>$data));
        } else {
            $this->jsonOut(array("code"=>3000,"msg"=>L("查询无结果"),"data"=>[]));
        }
    }

    public function update_report()
    {
        $data = $this->getParams();
        $data = GitReportModel::updateReport($data);
        if ($data) {
            $this->jsonOut(array("code"=>2000,"msg"=>L("操作成功"),"data"=>$data));
        } else {
            $this->jsonOut(array("code"=>3000,"msg"=>L("操作失败"),"data"=>[]));
        }
    }

    public function deptShow ()
    {
        $model = new Model();
        $list = $model->table('tb_hr_dept hd')
            ->field('hd.*, admin.M_NAME as CREATE_USER')
            ->join('left join bbm_admin admin on admin.M_ID = hd.CREATE_USER_ID')
            ->order('hd.STATUS DESC, hd.DELETED_AT ASC')->select();
        $list = CodeModel::autoCodeTwoVal($list, ['TYPE', 'STATUS']);
        foreach ($list as &$item) {
            if (in_array($item['STATUS'], ['N001490100', 'N001490200'])) {
                $item['STATUS_val'] = '使用中';
            }
            if ($item['DELETED_BY']) {
                $item['STATUS_val'] = '已删除';
            }
        }
        $this->jsonOut(array("code"=>2000,"msg"=>'success',"data"=>$list));
    }

    public function get_dept_options()
    {
        $model = M('hr_dept','tb_');
        $list = $model->field("ID,DEPT_NM,TYPE,STATUS,PAR_DEPT_ID")
                    ->where(['PAR_DEPT_ID' =>  ['neq', 0]])
                    ->order('STATUS DESC, ID ASC')->select();
        $data = [];
        if($list) 
        {
            foreach ($list as $item) {
                $data[] = [
                    'value' => $item['ID'],
                    'label' => $item['DEPT_NM'],
                    'parent_id' => $item['PAR_DEPT_ID'],
                ];
            }
            $data =  buildTree($data,'value','parent_id','children',75);
        }
        return $this->ajaxSuccess($data);
    }
}

