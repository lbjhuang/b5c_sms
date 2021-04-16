<?php 
/**
* api to hr-meeting 
* by huanzhu
*/
class HrMeetingAapi extends Action
{
	private $meeting;
	private $waiting;

	public function __construct($value='')
	{
		$this->meeting = D('TbHrMeeting');
		$this->waiting = D('TbHrWaitThings');
	}
	
	//会议列表
	public function dataList()
	{
		$data = $this->meeting->getData();
		return $data;
	}

	//会议详情页
	public function getMeetingDet()
	{
		$detail = $this->meeting->getDetail();
		return $detail;
	}

	//修改新增会议数据
	public function operationData()
	{
		$res = $this->meeting->operation();
		return $res;
	}
	//批量删除
	public function batchDel()
	{
		$res = $this->meeting->del();
		return $res;
	}

	//保存会议的待办事项
	public function save_waitThings()
	{
		$res = $this->meeting->saveWait();
		return $res;
	}

	//当前待办事项
	public function getWait_Data()
	{
		$res = $this->meeting->waitThingsData();
		return $res;
	}

	//删除待办事项
	public function delWaitThings()
	{
		$res = $this->meeting->delWait();
		return $res;
	}
	//批量修改状态
	public function batchChangeStatus()
	{
		$res = $this->meeting->changeStatus();
		return $res;
	}
	//导出excel
	public function exportList()
	{
		$res = $this->meeting->exportMeet();
		return $res;
	}
	//获取中文名
	public function getEnName()
	{
		$enName = $_SESSION['m_loginname'];

		$nameData = D("TbHrEmpl")
		->field("EMP_SC_NM")
		->where("ERP_ACT="."'".$enName."'")
		->find();
		$name = $nameData['EMP_SC_NM']?$nameData['EMP_SC_NM']:'';
		return $nameData['EMP_SC_NM'];
	}

	public function getPersonList()
	{
		$res = D("TbHrEmpl")->getMeetPersonData();
		return $res;
	}


	/*待办事项*/


	//待办事项列表
	public function waiting_List()
	{
		$res = $this->waiting->getWaitingList();
		return $res;
	}

	//增加编辑事项(主题)
	public function wait_operationData()
	{
		$res = $this->waiting->operationData();
		return $res;
	}
	//主题数据(内容+主题)
	public function getWaitingDet()
	{
		$res = $this->waiting->getWaitDet();
		return $res;
	}

	//删除待办事项内容
	public function delWaitContent()
	{
		$res = $this->waiting->delContent();
		return $res;
	}
	//批量删除
	public function wait_batchDel()
	{
		$res = $this->waiting->batchDelWait();
	}

	//导出
	public function wait_exportList()
	{
		$res = $this->waiting->waitExport();
	}

	//修改状态
	public function wait_batchChangeStatus()
	{
		$res = $this->waiting->changeStatus();
	}

	//跟进人数据
	public function getWaitPersonList()
	{
		$res = D("TbHrEmpl")->getWaitPersonData();
		return $res;
	}
}


 ?>