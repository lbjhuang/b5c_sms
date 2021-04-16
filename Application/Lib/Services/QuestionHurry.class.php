<?php
class QuestionHurry implements QuestionStrategy
{
	function dealWithButton($data = [])
	{
		$questionService = new QuestionService();		
		$question_log_model = new QuestionLogModel();

		$question_log_model->startTrans();

		// 记录操作日志

		$data['deal_user_id'] = $data['info']['opt_user_id']; // 操作完后应处理人id(bbm_admin.M_ID)，多个时以英文逗号隔开 
		$data['deal_status_cd'] = $data['info']['status'];
		$data['content'] = ''; // 不保存备注和补充说明
		$ql_res = $question_log_model->saveLogData($data);
		if ($ql_res !== false) {
			$question_log_model->commit();
		} else {
            throw new Exception($question_log_model->getError());
			$question_log_model->rollback();
		}

		// 发送微信消息 无法回滚，所以放到最后来执行
		$wx_user = $question_log_model->getDealUser($data['info']['opt_user_id'], true);
		$wx_res = $questionService->dealWxSend($wx_user, $data);

		return $wx_res;
	}

}