<?php

/**
 * User: yangsu
 * Date: 19/08/12
 * Time: 11:31
 */


/**
 * Class B2bReceivableService
 */
class B2bReceivableService extends Service
{
    /**
     * @var B2bReceivableRepository
     */
    protected $repository;

    /**
     * B2bReceivableService constructor.
     *
     * @param null $external_model
     */
    public function __construct($external_model = null)
    {
        $this->repository = new B2bReceivableRepository($external_model);
    }

    public function getList($data, $is_excel = false)
    {
        $where = [];
        if ($data['search']['from_today']) {
            $min = min($data['search']['from_today']);
            $max = max($data['search']['from_today']);
            if ($min == $max) {
                $where['tb_report_b2b_receivable_aggregation.from_today'] = ['BETWEEN', $this->assignTodayInterval($min)];
            } else {
                $min_interval = $this->assignTodayInterval($min);
                $max_interval = $this->assignTodayInterval($max);
                $mixing = array_merge($min_interval, $max_interval);
                $where['tb_report_b2b_receivable_aggregation.from_today'] = ['BETWEEN', [min($mixing), max($mixing)]];
            }
        }
        if ($data['search']['order_number']['value']) {
            switch ($data['search']['order_number']['type']) {
                case 'b2b':
                    $where['tb_b2b_info.PO_ID'] = $data['search']['order_number']['value'];
                    break;
                case 'po':
                    $where['tb_b2b_info.THR_PO_ID'] = $data['search']['order_number']['value'];
                    break;
                case 'ship':
                    $where['tb_report_b2b_receivable_aggregation.ship_id'] = $data['search']['order_number']['value'];
                    break;
            }
        }
        unset($data['search']['order_number'], $data['search']['from_today']);
        $search_map = [
            "from_today" => 'tb_report_b2b_receivable_aggregation.from_today',
            "client_name" => 'tb_b2b_info.CLIENT_NAME',
            "sales_team" => 'tb_b2b_info.SALES_TEAM',
            "order_id" => 'tb_report_b2b_receivable_aggregation.order_id',
            "po_user" => 'tb_b2b_info.PO_USER',
            "our_company" => 'tb_b2b_info.our_company',
        ];
        list($where, $limit) = WhereModel::joinSearchTemp($data, $search_map, $where, ['PO_ID', 'THR_PO_ID', 'ship_id']);
        $where['tb_report_b2b_receivable_aggregation.remaining_receivabl'] = ['gt', 0];
        return $this->repository->getList($where, $limit, $is_excel);
    }

    public function updateFromToday()
    {
        $this->repository->updateFromToday();
    }

    public function addLevelOne($data)
    {
        $data['created_by'] = $data['updated_by'] = DataModel::userNamePinyin();
        $this->repository->addLevelOne($data);
        $this->updateLevelSecond($data['order_id']);
    }

    public function updateLevelSecond($order_id)
    {
        $this->repository->deleteLevelSecond($order_id);
        $b2b_receivable = $this->repository->getB2bReceivable($order_id);
        $level_seconds = $this->assemblyLevelSecond($b2b_receivable);
        $save_level_seconds = $this->assemblySaveLevelSeconds($level_seconds);
        if (empty($save_level_seconds)) {
            return false;
        }
        $this->repository->addLevelSecond($save_level_seconds);
    }

    private function assemblySaveLevelSeconds($data)
    {
        $now_date = new DateTime(date('Y-m-d'));
        foreach ($data as &$datum) {
            $datum['remaining_receivabl_cny'] = $datum['remaining_receivabl'] * ExchangeRateModel::conversion($datum['po_currency'], 'N000590300', $datum['due_date']);
            $datum['remaining_receivabl_usd'] = $datum['remaining_receivabl'] * ExchangeRateModel::conversion($datum['po_currency'], 'N000590100', $datum['due_date']);
            $temp_date = new DateTime(date('Y-m-d', strtotime($datum['due_date'])));
            $datum['from_today'] = $now_date->diff($temp_date)->days;
            $datum['created_by'] = $datum['updated_by'] = DataModel::userNamePinyin();
            unset($datum['po_currency']);
        }
        return $data;
    }

    private function assemblyLevelSecond($data)
    {
        $comp_list = 0;
        $save_list = [];
        foreach ($data as $datum) {
            if ($datum['amount'] > 0) {
                $add_list[] = $datum;
            } else {
                $comp_list += $datum['amount'];
            }
        }
        foreach ($add_list as $value) {
            if (0 === $comp_list) {
                $save_list[] = [
                    'order_id' => $value['order_id'],
                    'ship_id' => $value['ship_id'],
                    'initial_receivabl' => $value['amount'],
                    'remaining_receivabl' => $value['amount'],
                    'due_date' => $value['created_at'],
                    'po_currency' => $value['po_currency'],
                ];
                continue;
            }
            $comp_list += $value['amount'];
            if (0 < $comp_list) {
                $save_list[] = [
                    'order_id' => $value['order_id'],
                    'ship_id' => $value['ship_id'],
                    'initial_receivabl' => $value['amount'],
                    'remaining_receivabl' => $comp_list,
                    'due_date' => $value['created_at'],
                    'po_currency' => $value['po_currency'],
                ];
                $comp_list = 0;
            }
        }
        return $save_list;
    }

    public function updateAllB2bOrder()
    {
        $order_ids = $this->repository->getAllOrderId();
        foreach ($order_ids as $order_id) {
            $this->updateWholeOrder($order_id['id']);
        }
    }

    public function updateWholeOrder($order_id)
    {
        $this->repository->deleteLevelOne($order_id);
        $this->addShip($order_id);
        $this->addWarehouse($order_id);
        $this->addReturn($order_id);
        $this->addClaim($order_id);
        $this->addReceipt($order_id);
        $this->updateLevelSecond($order_id);
    }
    private function addShip($order_id)
    {
        $ship_list = $this->repository->getThisShipList($order_id);
        return $this->repository->addLevelOneAll($ship_list);
    }

    private function addWarehouse($order_id)
    {
        $res_db = $this->repository->getThisWarehouseList($order_id);
        $res_data = [];
        foreach ($res_db as $value) {
            if (0 != $value['amount']) {
                $value['amount'] = -$value['amount'];
                if (empty($value['created_at'])) {
                    unset($value['created_at']);
                }
                $res_data[] = $value;
            }
        }
        return $this->repository->addLevelOneAll($res_data);
    }

    private function addReturn($order_id)
    {
        $res_db = $this->repository->getThisReturnList($order_id);
        $res_data = [];
        foreach ($res_db as $value) {
            if (0 != $value['amount']) {
                $value['amount'] = -$value['amount'];
                $res_data[] = $value;
            }
        }
        return $this->repository->addLevelOneAll($res_data);
    }

    private function addClaim($order_id)
    {
        $res_db = $this->repository->getThisClaimList($order_id);
        $res_data = [];
        foreach ($res_db as $value) {
            if (0 != $value['amount']) {
                $value['amount'] = -$value['amount'];
                $res_data[] = $value;
            }
        }
        return $this->repository->addLevelOneAll($res_data);
    }

    private function addReceipt($order_id)
    {
        $res_db = $this->repository->getThisReceiptList($order_id);
        $res_data = [];
        foreach ($res_db as $value) {
            if (0 != $value['amount']) {
                $value['amount'] = -$value['amount'];
                $res_data[] = $value;
            }
        }
        return $this->repository->addLevelOneAll($res_data);
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function assignTodayInterval($data)
    {
        $date = [];
        switch ($data) {
            case '2':
                $date = [0, 30];
                break;
            case '3':
                $date = [30, 59];
                break;
            case '4':
                $date = [60, 89];
                break;
            case '5':
                $date = [90, 120];
                break;
            case '6':
                $date = [120, 9999];
                break;
        }
        return $date;
    }
}