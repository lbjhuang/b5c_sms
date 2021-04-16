<?php

@import("@.Model.BaseModel");

class VideoService extends Service
{
	public $user_id;
	public $msg;
	public function __construct()
	{
	    $this->user_id  = DataModel::userId();
	    $this->user_name  = DataModel::userNamePinyin();
	    $this->public_time = '2020-05-08 10:00:00';
	}

	public function getManagerList()
	{
		$sql = "SELECT
				a.M_ID as admin_id,
				a.huaming,
				hew.wid,
				hew.uid 
			FROM
				tb_hr_empl_wx AS hew
				LEFT JOIN tb_hr_empl he ON hew.uid = he.ID
				LEFT JOIN tb_hr_jobs hj ON hj.ID = he.JOB_ID
				LEFT JOIN bbm_admin a ON a.empl_id = he.ID 
			WHERE
				hj.RANK <= 15
				AND hj.RANK > 0 
				AND a.IS_USE = 0 
				AND a.M_STATUS != 2";
		return M()->query($sql);
	}

	public function checkHasScore($admin_id, $video_id)
	{
		// admin_id,video_id不为空校验
		if (!$admin_id || !$video_id) {
			$this->msg .= "【参数缺失】 ERP后台账号id为{$admin_id}，视频id为{$video_id} ";
			return true;
		}
		$videoScoreModel = M('video_score', 'tb_sys_');
		$res = $videoScoreModel->where(['video_id' => $video_id, 'user_id' => $admin_id])->find();
		return $res;
	}

	public function getVideoList()
	{
		$videoModel = M('video', 'tb_sys_');
		$whereMap['created_at'] = array('egt', $this->public_time);
		$whereMap['is_deleted'] = 0;
		return $videoModel->field('id,name')->where($whereMap)->select();
	}
	public function wx_send_score()
	{
		//1.获取高管用户列表
		$user_list = $this->getManagerList();
		//2.轮询视频列表id，视频名称（排除历史视频）
		$video_list = $this->getVideoList();
		//3.根据id和user_id去评分表查看是否有结果，没有标明没有评分，需发微信
		$msg = '';
		if ($user_list && $video_list) {
			foreach ($user_list as $key => $value) {
				$video_msg = '';
				foreach ($video_list as $k => $v) {
					$res = false;
					$res = $this->checkHasScore($value['admin_id'], $v['id']);
					if (!$res) { // 表明没有找到对应打分记录，需要通知
						$video_msg .= $v['name'] . "%0a";
					}
				}
				// 发送微信
				if ($video_msg && $value['wid']) {
					$msg = "您好！%0a"; // %0a 用于url传输换行，\r\n 会报错
					$msg .= "ERP中已经上传了以下新功能演示视频：%0a";
					$msg .= $video_msg;
					$msg .= '请前往ERP-系统指南-演示视频查看，并给予反馈，希望可以帮助到您的工作，谢谢您！';
					$msg = str_replace(array("\r\n", "\r", "\n"), "", $msg); 
					$msg = str_replace("/", "\\", $msg); // 去掉转移符
					$wx_res = ApiModel::WorkWxSendMessage($value['wid'], $msg);
					if (200000 != $wx_res['code']) {
						$this->msg .= "消息发送失败，消息用户为{$value['wid']}";
					}
				}
			}
		}
		if ($this->msg) {
			return $this->msg;
		}
		return false;
	}
	public function videoScoreSubmit($param, $model)
	{
		// 获取当前视频表信息
		$video_info = VideoModel::getBaseInfo($param);
		if (!$video_info) {
			throw new \Exception("获取视频基础信息失败");
		}
		// 新增评价表记录
		$res = $model->table('tb_sys_video_score')->where(['video_id' => $param['video_id'], 'user_id' => $this->user_id])->find();
		if ($res) {
			throw new \Exception("该用户【{$res['created_by']}】已对视频【{$video_info['name']}】做出评价，无需重复评价~");
		}
		$addData['video_id'] = $param['video_id'];
		$addData['user_id'] = $this->user_id;
		$addData['score'] = $param['score'];
		$addData['remark'] = $param['remark'];
		$addData['created_by'] = $this->user_name;
		$addData['created_at'] = date('Y-m-d H:i:s', time());
		$res_add = $model->table('tb_sys_video_score')->add($addData);
		if (false === $res_add) {
			$addData['lastsql'] = M()->_sql();
			Logs($addData, __FUNCTION__, '-----addVideoData', 'TR');
			throw new \Exception("新增失败");
		}
		
		// 更新视频表的平均分 根据视频id去获取所有的分数+本次提交分，除以人数+1
		$whereMap['id'] = $param['video_id'];
		$updateData['score_sum'] = $video_info['score_sum'] + $param['score'];
		$updateData['score_number'] = $video_info['score_number'] + 1;
		$updateData['score_avg'] = $updateData['score_sum'] / $updateData['score_number'];
		$update_res = $model->table('tb_sys_video')->where($whereMap)->save($updateData);
		if (false === $update_res) {
			$updateData['lastsql'] = M()->_sql();
			Logs($updateData, __FUNCTION__, '-----updateVideoData', 'TR');
			throw new \Exception("更新视频表数据失败");
		}
		return true;
	}
}