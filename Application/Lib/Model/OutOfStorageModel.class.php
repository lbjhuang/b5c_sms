<?php
/**
 * 出库，用于已生成我方订单的第三方订单出库
 * User: b5m
 * Date: 2018/3/19
 * Time: 16:28
 */
class OutOfStorageModel extends BaseModel
{
    /**
     * 主函数
     * @param $ordId
     */
    public function main($ordId)
    {

    }

    /**
     * 生成出库单
     */
    public function generateOutStorageBill()
    {

    }

    /**
     * 生成出库单子数据
     */
    public function generateOutStorageChildBill()
    {

    }

    /**
     * @param $requestData
     * @param $type
     * @return bool
     */
    public function sendRequest($requestData, $type)
    {
        $type == '-' ? $url = HOST_URL_API . '/batch/export.json' : $url = HOST_URL_API . '/batch/update_total.json';
        if ($requestData) {
            $responseData = json_decode(curl_get_json($url, json_encode($requestData)), true);
            $this->_catchMe($requestData, $responseData);
            $this->setReponseData($responseData);
            if ($responseData ['code'] == 2000) {
                if ($this->parseResponseBillOperationBatch($responseData['data']['export'])) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * @param $response Array 接口返回数据
     * @return bool 成功与否
     */
    public function parseResponseBillOperationBatch($response)
    {
        $saveData = [];
        foreach ($response as $key => $value) {
            $saveData [$value ['data']['billId']] [] = $value ['data']['exportDetail'];
        }
        $model = M('_wms_bill', 'tb_');
        if ($saveData) {
            foreach ($saveData as $key => $value) {
                $data = [];
                $data ['batch_ids'] = json_encode($value);
                if (!$model->where('id = ' . $key)->save($data)) {
                    return false;
                }
            }
        }
        return ture;
    }
}