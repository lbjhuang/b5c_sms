<?php
class QuestionTranOthTst implements QuestionStrategy
{
	function dealWithButton($data = [])
	{
		$question_model = new QuestionModel();
		$questionService = new QuestionService();
		$question_log_model = new QuestionLogModel();

		$question_model->startTrans();


		// 更新工单数据

		$data['status'] = $questionService::$STATUS_TESTING;
		$data['opt_user_id'] = $question_log_model->getAppointUser($data, false, true);
		$data['opt_user_name'] = $question_log_model->getAppointUser($data, true, true);
		$qm_res = $question_model->updatQuestionData($data);



		// 记录操作日志

		$data['deal_user_id'] = $data['opt_user_id']; // 操作完后应处理人id(bbm_admin.M_ID)，多个时以英文逗号隔开 
		$data['deal_status_cd'] = $data['status'];
		$ql_res = $question_log_model->saveLogData($data);
		if ($qm_res !== false && $ql_res !== false) {
			$question_model->commit();
		} else {
            throw new Exception($question_model->getError());
			$question_model->rollback();
		}


		// 发送微信消息 无法回滚，所以放到最后来执行

		$wx_user = $question_log_model->getDealUser($data['opt_user_id'], true);
		$wx_res = $questionService->dealWxSend($wx_user, $data);

		return $wx_res;
	}

}