<?php
class QuestionSave implements QuestionStrategy
{
	function dealWithButton($data = [])
	{
		$question_log_model = new QuestionLogModel();

		$question_log_model->startTrans();

		// 记录操作日志

		$data['deal_user_id'] = $data['info']['opt_user_id']; 
		$data['deal_status_cd'] = $data['info']['status'];
		$ql_res = $question_log_model->saveLogData($data);
		if ($ql_res !== false) {
			$question_log_model->commit();
		} else {
            throw new Exception($question_log_model->getError());
			$question_log_model->rollback();
		}


		return $ql_res;
	}

}