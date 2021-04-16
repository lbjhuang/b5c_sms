<?php
class KyribaHistFixAction extends BaseAction {
	public function kyribaHistDataDeal()
	{
		try {
		    $kyService = new KyribaService();
		    $response_data = $kyService->kyribaHistDataDeal();
		    if ($response_data['code'] == 200) {
		    	$this->ajaxSuccess($response_data);
		    } else {
		    	$this->ajaxError($response_data['data'], L('导入失败，请先核实数据'));
		    }
		} catch (Exception $exception) {
		    $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
		}
	}
}