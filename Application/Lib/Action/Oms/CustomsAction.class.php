<?php


class CustomsAction extends BasisAction
{
    public function customs_list()
    {
        $this->display();
    }

    // 重推接口
    public function repush()
    {
        try {
            $params = $this->getParams();
            $this->checkRePushData($params);
            $customs = new CustomsService();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['msg'] = $customs->repushOrder($params);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxSuccess($res);
    }
    public function checkRePushData($params)
    {
        if (empty($params['orderId'])) {
            throw new Exception('订单ID不可为空');
        }
        if (empty($params['platCd'])) {
            throw new Exception('订单ID不可为空');
        }
    }
    /**
     * 列表
     */
    public function lists(){
        import('ORG.Util.Page');
        $params = $this->getParams();
        $customs = new CustomsService();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($params['pages']) && !empty($params['pages']['per_page']) && !empty($params['pages']['current_page'])){
            $pages = array(
                'per_page' =>$params['pages']['per_page'],
                'current_page' => $params['pages']['current_page']
            );
        }
        list($list, $count) = $customs->getList($params['search'],$pages);
        if (empty($count)) {
            $list = [];
        }
        $data = ['data' => $list, 'page' => ['total_rows' => $count]];
        $this->ajaxSuccess($data);
    }

    /**
     *  导出
     */
    public function export()
    {
        session_write_close();
        $post_data = json_decode($_POST['post_data'], true);
        $customs = new CustomsService();
        list($list,$xlsCell,$xlsName) = $customs->getData($post_data['search']);
        $width   = ['size' => '25'];
        $number_fields = [
            'cost_price','PAY_TOTAL_PRICE_DOLLAR','pre_amount_freight',
            'amount_freight','carry_tariff','insurance_fee','vat_fee',
            'league_fee','plat_fee','collection_fee','other_fee','platform_discount_price'
        ];
        $this->exportExcel($xlsName, $xlsCell, $list, $width, $number_fields);
    }


}