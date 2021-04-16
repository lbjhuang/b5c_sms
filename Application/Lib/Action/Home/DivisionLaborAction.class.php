<?php
/**
 * User: yangsu
 * Date: 19/5/9
 * Time: 13:12
 */


class DivisionLaborAction extends BaseAction
{
    protected $success_code = 200;

    /**
     * DivisionLaborAction constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new DivisionLaborService();
    }

    /**
     *
     */
    public function ourCompanysIndex()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkOurCompanysIndex($request_data);
            $response_data = $this->service->ourCompanysIndex($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkOurCompanysIndex($data)
    {

    }

    /**
     *
     */
    public function ourCompanysUpdate()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr('data');
            $this->checkOurCompanysUpdate($request_data);
            $response_data = $this->service->ourCompanysUpdate($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkOurCompanysUpdate($data)
    {

    }

    /**
     *
     */
    public function clientsIndex()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkClientsIndex($request_data);
            $response_data = $this->service->clientsIndex($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkClientsIndex($data)
    {

    }

    /**
     *
     */
    public function clientsUpdate()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr('data');
            $this->checkClientsUpdate($request_data);
            $response_data = $this->service->clientsUpdate($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkClientsUpdate($data)
    {

    }

    /**
     *
     */
    public function warehousesIndex()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->checkWarehousesIndex($request_data);
            $response_data = $this->service->warehousesIndex($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkWarehousesIndex($data)
    {

    }

    /**
     *
     */
    public function warehousesUpdate()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr('data');
            $this->checkWarehousesUpdate($request_data);
            $response_data = $this->service->warehousesUpdate($request_data);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($response_data, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     */
    private function checkWarehousesUpdate($data)
    {

    }

    public function todoIndex()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr('show_features');
            $this->checkWarehousesIndex($request_data);
            $response_data = $this->service->todoIndex($request_data);
            $res = [
                'code' => $this->success_code,
                'msg' => 'success',
                'is_show' => false,
                'user_name' => DataModel::userNamePinyin(),
                'user_id' => DataModel::userId(),
                'data' => $response_data,
            ];
            if (0 < array_sum(array_column($response_data, 'count'))) {
                $res['is_show'] = true;
            }
        } catch (Exception $exception) {
            $res = [
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
                'user_name' => DataModel::userNamePinyin(),
                'user_id' => DataModel::userId(),
                'is_show' => false,
                'data' => $response_data,
            ];
        }
        $this->ajaxReturn($res);
    }
}
