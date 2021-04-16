<?php
/**
 * User: yangsu
 * Date: 18/7/3
 * Time: 14:09
 */


class OrderSkuAction extends BasisAction
{

    public function searchSku()
    {
        $request_data = DataModel::getData(true);
        try {
            if (empty($request_data['SKU_ID']) && empty($request_data['GUDS_NM'])) {
                throw new Exception(L('查询数据为空'));
            }
            $this->model = M();
            $request_data = $this->gudsNmExcSku($request_data);
            $batch_arr = $this->joinBatchData($request_data);
            $response_data = $this->joinResponseData($request_data, $batch_arr);
            $res = DataModel::$success_return;
            $res['data'] = $response_data;
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
            if ($exception->getCode()) {
                $res['code'] = $exception->getCode();
            }
        }
        $this->ajaxReturn($res);

    }

    public function giftAdd()
    {
        $request_data = DataModel::getData(true)['data'];
        $redisKey = $request_data[0]['ORDER_ID'] . '_' . $request_data[0]['PLAT_CD'];
        $ORDER_ITEM_ID = $request_data[0]['ORDER_ITEM_ID'];
        try {

            if (!$this->isMayOperationOrders(array( 'ORDER_ID'=>$request_data[0]['ORDER_ID'] ,'PLAT_CD'=>$request_data[0]['PLAT_CD']),true)){
                throw new Exception(L('代销售订单禁止删除操作'));
            }

            $this->model = M();
            if (!RedisLock::lock($redisKey))
                throw new \Exception(L('订单锁获取失败'));
            Logs($request_data, __FUNCTION__, __CLASS__);
            $this->addGudsNmExcSku($request_data);
            $this->checkGiftAddStatus($request_data);
            $this->checkGiftAddData($request_data);
            $request_data = $this->joinCustomsPrice($request_data);
            $add_arr = $this->joinAddData($request_data,$ORDER_ITEM_ID);
            $res_data = $this->model->table('tb_op_order_guds')->addAll($add_arr);
            if (!$res_data) {
                throw  new Exception(L('未完成全部新增'), 500);
            }
            $res = DataModel::$success_return;
            $OrderLog = new OrderLogModel();
            $OrderLog->addLog($request_data[0]['ORDER_ID'], $request_data[0]['PLAT_CD'], L('添加赠品 ') . $request_data[0]['B5C_SKU_ID']);
            $res['info'] = "新增成功";
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            if ($this->error_msg) {
                $res['data'] = $this->error_msg;
            }
            $res['info'] = $exception->getMessage();
            if ($exception->getCode()) {
                $res['code'] = $exception->getCode();
            }
        }
        RedisLock::unlock();
        $this->ajaxReturn($res);
    }

    protected function addGudsNmExcSku($request_data)
    {
        foreach ($request_data as $val) {
            $request_data['SKU_ID'] = $val['B5C_SKU_ID'];
            $this->gudsNmExcSku($request_data);
        }
    }

    public function checkGiftAddStatus($data)
    {
        $Order = new OrderAction();
        foreach ($data as $value) {
            if (!$Order->searchPatchOrderStatus($value['ORDER_ID'], $value['PLAT_CD'])) {
                throw new Exception(L('订单状态非待派单订单'));
            }
        }
    }

    public function checkGiftAddData($data)
    {
        foreach ($data as $key => $value) {
            $rules = [
                $key . '.ORDER_ID' => 'required|string|min:1',
                $key . '.PLAT_CD' => 'required|string|min:10|max:10',
                $key . '.B5C_SKU_ID' => 'required|string|min:10|max:10',
                $key . '.ITEM_COUNT' => 'required|integer',
            ];
        }
        if (!ValidatorModel::validate($rules, $data)) {
            $this->error_msg = ValidatorModel::getMessage();
            throw new Exception(L('请求数据异常'));
        } else {
            $this->checkGiftExist($data);
        }
    }

    public function checkGiftExist($data)
    {
        $where['B5C_SKU_ID'] = array('IN', array_column($data, 'B5C_SKU_ID'));
        $where['ORDER_ID'] = $data[0]['ORDER_ID'];
        $where['PLAT_CD'] = $data[0]['PLAT_CD'];
        $where['guds_type'] = 1;
        $res_count = $this->model->table('tb_op_order_guds')
            ->field('ID')
            ->where($where)
            ->count();
        if ($res_count) {
            throw new Exception(L('新增赠品 SKU 重复'));
        }
    }

    /**
     *
     */
    public function giftDel()
    {
        $request_data = DataModel::getData(true)['data'];
        if ($this->checkOrderStatus($request_data[0]['sku_id'])) {
            $Model = new Model();
            $where['tb_op_order_guds.id'] = $request_data[0]['sku_id'];
            $where['tb_op_order_guds.guds_type'] = 1;
            $del_key_db = $Model->table('tb_op_order_guds')
                ->where($where)
                ->find();
            $OrderLogModel = new OrderLogModel();
            $OrderLogModel->addLog($del_key_db['ORDER_ID'], $del_key_db['PLAT_CD'], L('删除赠品 ' . $del_key_db['B5C_SKU_ID']));
            $delete_res = $this->deleteData(
                $request_data,
                'sku_id',
                'tb_op_order_guds',
                'guds_type = 1',
                null,
                'id'
            );
        } else {
            $delete_res = DataModel::$error_return;
            $delete_res['msg'] = $delete_res['info'] = '订单状态不支持删除';
        }
        $this->ajaxReturn($delete_res);
    }

    private function checkOrderStatus($sku_id)
    {
        if (empty($sku_id)) {
            return false;
        }
        $Model = new Model();
        $b5c_order_no = $Model->table('(tb_op_order,tb_op_order_guds)')
            ->where(" tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order_guds.ID = {$sku_id} ")
            ->getField('tb_op_order.B5C_ORDER_NO');
        if ($b5c_order_no) {
            return false;
        }
        return true;
    }

    /**
     * @param $request_data
     *
     * @return mixed
     */
    private function joinCustomsPrice($request_data)
    {
        if (!class_exists('OrdersAction')) {
            include_once APP_PATH . 'Lib/Action/Home/OrdersAction.class.php';
        }
        $OrdersAction = new OrdersAction();
        $request_data = $OrdersAction->customsInfoInsert($request_data);
        return $request_data;
    }

    /**
     * @code $temp_data['ORDER_ID'] = $value['ORDER_ID'];
     * $temp_data['PLAT_CD'] = $value['PLAT_CD'];
     * $temp_data['B5C_SKU_ID'] = $value['B5C_SKU_ID'];
     * $temp_data['ITEM_COUNT'] = $value['ITEM_COUNT'];
     * $temp_data['CUSTOMS_PRICE'] = $value['CUSTOMS_PRICE'];
     *
     * @param $request_data
     * @param $temp_data
     * @param $add_arr
     *
     * @return array
     */
    private function joinAddData($request_data,$ORDER_ITEM_ID)
    {
        $testConnAction = new OrdersAction();
        foreach ($request_data as $value) {
            $temp_data = $value;
            $temp_data['SKU_ID'] = $temp_data['B5C_SKU_ID'];
            $temp_data['ITEM_PRICE'] = $temp_data['declare_the_price'] = $temp_data['third_party_agreement_price'] = $temp_data['third_party_sales_price'] = 0;
            $guds_all_data = [
                [
                    'SKU_ID' => $temp_data['B5C_SKU_ID'],
                ]
            ];
            $temp_data['CUSTOMS_PRICE'] = $testConnAction->customsInfoInsert($guds_all_data, 'SKU_ID')[0]['CUSTOMS_PRICE'];
            $temp_data['guds_type'] = 1;
            $temp_data['CREATE_AT'] = $temp_data['UPDATE_AT'] = DateModel::now();
            $temp_data['CREATE_USER'] = $temp_data['UPDATE_USER_LAST'] = DataModel::userNamePinyin();
            $temp_data['ORDER_ITEM_ID'] = $ORDER_ITEM_ID;
            $add_arr[] = $temp_data;
        }
        return $add_arr;
    }

    /**
     * @param $request_data
     * @param $Model
     *
     * @throws Exception
     */
    private function gudsNmExcSku($request_data)
    {
        $model = new PmsBaseModel();
        if (empty($request_data['SKU_ID']) && $request_data['GUDS_NM']) {
            $where_guds_nm['product_detail.spu_name'] = $request_data['GUDS_NM'];
            $request_data['SKU_ID'] = $model->table('product_detail')
                ->join('left join product_sku pk on pk.spu_id=product_detail.spu_id')
                ->where($where_guds_nm)
                ->limit(1)
                ->getField('pk.sku_id');
            if (empty($request_data['SKU_ID'])) {
                throw new Exception(L('无对应商品名称数据'));
            }
        } else {
            $where_guds_nm['product_sku.sku_id'] = $request_data['SKU_ID'];
            $where_guds_nm['pd.language'] = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920100';
            $spu_name = $model->table('product_sku')
                ->field('if(count(*),pd.spu_name,(select spu_name from product_detail where spu_id=product_sku.spu_id and language="N000920200")) as spu_name')
                ->join('left join product_detail pd on product_sku.spu_id=pd.spu_id')
                ->where($where_guds_nm)
                ->limit(1)
                ->find();
            $request_data['GUDS_NM'] = $spu_name['spu_name'];
            if (empty($request_data['GUDS_NM'])) {
                throw new Exception(L('无对应 SKU 数据'));
            }
        }
        return $request_data;
    }

    /**
     * @param $request_data
     * @param $batch_arr
     *
     * @return mixed
     */
    private function joinResponseData($request_data, $batch_arr)
    {
        $data['SKU_ID'] = $request_data['SKU_ID'];
        $data['GUDS_NM'] = $request_data['GUDS_NM'];
        $sku_arr[] = $request_data;
        $img_arr = SkuModel::getInfo([['sku' => $data['SKU_ID']]], 'sku', ['image_url']);
        $data['guds_img_cdn_addr'] = $img_arr[0]['image_url'];
        $data['cost']['min'] = min($batch_arr) ? min($batch_arr) : 0;
        $data['cost']['max'] = max($batch_arr) ? max($batch_arr) : 0;
        return $data;
    }

    /**
     * @param $request_data
     *
     * @return array
     */
    private function joinBatchData($request_data)
    {

        $sku_data['mixedCode'] = $request_data['SKU_ID'];
        // $sku_data['channel'] = 'N000830100';
        $sku_data['showAll'] = true;

        $model = new StandingExistingModel();
        $batch_data = $model->getBatchData($sku_data);

        if ($batch_data) {
            // throw new Exception(L('SKU 数据异常'));
        }
        $batch_arr = array_column($batch_data, 'unitPrice');
        return $batch_arr;
    }


}