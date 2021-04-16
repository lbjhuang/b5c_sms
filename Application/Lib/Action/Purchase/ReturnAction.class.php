<?php
/**
 * User: yuanshixiao
 * Date: 2019/2/25
 * Time: 17:57
 */

class ReturnAction extends BaseAction
{
    public function initiate_return() {
        $params     = $this->jsonParams();
        $return_l   = D('Purchase/Return','Logic');
        $res        = $return_l->initiateReturn($params);
        if($res) {
            $this->ajaxSuccess(['id'=>$res]);
        }
        $this->ajaxReturn(['code'=>3000,'msg'=>$return_l->getError(),'data'=>$return_l->getData()]);
    }

    public function search_goods() {
        $params             = $this->jsonParams();
        $return_l           = D('Purchase/Return','Logic');
        $key                = $params['type'] == 0 ? 'upc_id' : 'sku_id';
        $goods_search[$key] = $params['search_val'];
        $params['goods']    = [$goods_search];
        $res        = $return_l->searchGoods($params);
        if($res) {
            $this->ajaxSuccess($res);
        }
        $this->ajaxError([],$return_l->getError());
    }

    public function import_goods() {
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error(L('请上传EXCEL文件'),'',true);
            }
        }
        $PHPExcel   = $PHPReader->load($filePath);
        $sheet      = $PHPExcel->getSheet(0);
        $allRow     = $sheet->getHighestRow();
        $sku_arr    = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $temp           = [];
            $temp['sku_id'] = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $temp['upc_id'] = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $sku_arr[]      = $temp;
        }
        $params             = $this->params();
        $params['goods']    = $sku_arr;
        $return_l   = D('Purchase/Return','Logic');
        $res        = $return_l->searchGoods($params);
        if($res) {
            $this->ajaxSuccess($res);
        }
        $this->ajaxError([],$return_l->getError());
    }

    public function return_list() {
        $params     = $this->jsonParams();
        $_GET['p']  = $params['p'];
        $return_l   = D('Purchase/Return','Logic');
        $list       = $return_l->returnList($params);
        $this->ajaxSuccess($list);
    }

    public function return_detail() {
        $params     = $this->jsonParams();
        $return_l   = D('Purchase/Return','Logic');
        $detail     = $return_l->returnDetail($params['id']);
        $this->ajaxSuccess($detail);
    }

    public function return_tally() {
        $params     = $this->jsonParams();
        $return_l   = D('Purchase/Return','Logic');
        $res        = $return_l->tally($params);
        if($res) {
            $this->ajaxSuccess();
        }
        $this->ajaxError([],$return_l->getError());
    }

    public function delete_return() {
        $params     = $this->jsonParams();
        $return_l   = D('Purchase/Return','Logic');
        $res        = $return_l->deleteReturn($params['id']);
        if($res) {
            $this->ajaxSuccess($res);
        }
        $this->ajaxError([],$return_l->getError());
    }

}