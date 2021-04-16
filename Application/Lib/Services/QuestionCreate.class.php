<?php
class QuestionCreate implements QuestionStrategy
{
	function dealWithButton($data = [])
	{
		$questionService = new QuestionService();		
		$question_log_model = new QuestionLogModel();

		$question_log_model->startTrans();

		// 记录操作日志

		$data['deal_user_id'] = $data['info']['opt_user_id']; // 操作完后应处理人id(bbm_admin.M_ID)，多个时以英文逗号隔开 
		$data['deal_status_cd'] = $data['info']['status'];
		$ql_res = $question_log_model->saveLogData($data);
		if ($ql_res !== false) {
			$question_log_model->commit();
		} else {
            throw new Exception($question_log_model->getError());
			$question_log_model->rollback();
		}
		$wx_user = $questionService->getDefaultUser($questionService::$DEFAULT_CRY)['name'];
		$wx_res = $questionService->dealWxSend($wx_user, $data);

		return $wx_res;
	}

}