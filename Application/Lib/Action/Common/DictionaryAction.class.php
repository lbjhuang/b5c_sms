<?php
/**
 * 公共字典对外接口
 * Class DictionaryAction
 */

class DictionaryAction extends CommonBaseAction {


    public function getCodeList()
    {

        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $codeService = new CodeService();
            $data = $codeService->codeSearchList($request_data);
            return $this->ajaxSuccess($data,'success');
        } catch (Exception $exception) {
            return $this->ajaxError("服务器异常，请稍后再试");
        }
    }

}