<?php
class QuestionAddRemark implements QuestionStrategy
{
	function dealWithButton($data = [])
	{
		$question_model = new QuestionModel();
		$questionService = new QuestionService();
		$question_log_model = new QuestionLogModel();

		$question_log_model->startTrans();

		// 记录操作日志

		$data['deal_user_id'] = $data['info']['opt_user_id']; // 当前处理人还是原来的
		$data['deal_status_cd'] = $data['info']['status'];
		$ql_res = $question_log_model->saveLogData($data);
		if ($qm_res !== false && $ql_res !== false) {
			$question_log_model->commit();
		} else {
            throw new Exception($question_log_model->getError());
			$question_log_model->rollback();
		}


		// 发送微信消息 无法回滚，所以放到最后来执行

		$wx_user = $question_log_model->getDealUser($data['info']['opt_user_id']);
		$wx_res = $questionService->dealWxSend($wx_user, $data);

		if ($wx_res) {
			//提交时状态=待明确，则自动触发【转实施】操作
			if ($data['info']['status'] === $questionService::$STATUS_UNDEFINED) {
				$question_trancry = new QuestionTranCry();
				$wx_res = $question_trancry->dealWithButton($data);
			}
		}


		return $wx_res;
	}

}