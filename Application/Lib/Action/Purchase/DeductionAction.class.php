<?php
class DeductionAction extends BaseAction
{
	// 获取符合范围的采购单号
	public function getPurOrderNum()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $res = DataModel::$success_return;
		    $res['code'] = 200;
		    if ($request_data) {
		        $this->checkPurOrderSelectRequest($request_data);
		    }
		    $PurService = new PurService();
		    $res['data'] = $PurService->getPurOrderFieldByMap($request_data);
		} catch (Exception $exception) {
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}

	private function checkPurOrderSelectRequest($data)
	{
	    $rules = [
	        'supplier_id' => 'required',
	        'amount_currency' => 'required|string|size:10',
	        'our_company' => 'required|string|size:10',
	    ];
	    $attributes = [
	        'supplier_id' => '供应商ID',
	        'amount_currency' => '币种',
	        'our_company' => '我方公司',
	    ];
	    $this->validate($rules, $data, $attributes);
	}

	// 余额转账
	public function transfer()
	{
		try {
		    $request_data = DataModel::getDataNoBlankToArr();
		    $model = new Model();
		    $PurService = new PurService();
            $model->startTrans();
		    $res = DataModel::$success_return;
		    $res['code'] = 200;
		    $res['data'] = $PurService->deductionTransfer($request_data, $model);
		    $model->commit();
		} catch (Exception $exception) {
			$model->rollback();
		    $res = $this->catchException($exception);
		}
		$this->ajaxReturn($res);
	}


	public function deductionList() {
	    try {
	        $request_data = DataModel::getDataNoBlankToArr();
	        $res = DataModel::$success_return;
	        $res['code'] = 200;
	        $res['data'] = (new PurService())->getDeductionCompensationList($request_data, $request_data['others']['return_all']);
	    } catch (Exception $exception) {
	        $res = $this->catchException($exception);
	    }
	    $this->ajaxReturn($res);
	}

	public function deductionDetail() {
	    try {
	        $request_data = DataModel::getDataNoBlankToArr();
	        $res = DataModel::$success_return;
	        $res['code'] = 200;
	        $res['data'] = (new PurService())->getDeductionCompensationDetail($request_data);
	    } catch (Exception $exception) {
	        $res = $this->catchException($exception);
	    }
	    $this->ajaxReturn($res);
	}

	public function addDeductionAmount() {
	    try {
	        $request_data = DataModel::getDataNoBlankToArr();
	        $res = DataModel::$success_return;
	        $res['code'] = 200;
	        $model = new Model();
	        $model->startTrans();
	        (new PurService())->addDeductionAmountCompensation($request_data, $model);
	        $model->commit();
	    } catch (Exception $exception) {
	        $model->rollback();
	        $res = $this->catchException($exception);
	    }
	    $this->ajaxReturn($res);
	}

	// 赔偿返利金取消，若来源为余额转账，还需还原余额账户和删除相关触发操作
	public function cancelDeductionAmount() {
	    try {
	        $deduction_detail_id = DataModel::getDataNoBlankToArr()['deduction_detail_id'];
	        $res = DataModel::$success_return;
	        $res['code'] = 200;
	        $model = new Model();
	        $model->startTrans();
	        (new PurService())->cancelDeductionAmountCompensation($deduction_detail_id, $model, true);
	        $model->commit();
	    } catch (Exception $exception) {
	        $model->rollback();
	        $res = $this->catchException($exception);
	    }
	    $this->ajaxReturn($res);
	}
}