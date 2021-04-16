<?php
/**
 * User: yangsu
 * Date: 19/12/24
 * Time: 11:31
 */

/**
 * Class ProductKeyService
 */
class ProductKeyService extends Service
{
    protected $repository;

    public $product_key;
    public $sku_id;
    public $batch_code;
    public $key_no;
    public $purchase_order_no;

    public function __construct()
    {
        $this->repository = new ProductKeyRepository();
    }

    /**
     * @param $product_key
     */
    public function initData($product_key)
    {
        $this->repository->product_key = $this->product_key = $product_key;
        list($this->sku_id,
            $this->batch_code,
            $this->key_no) = explode('-', $product_key);
        $this->repository->sku_id = $this->sku_id;
        $this->repository->batch_code = $this->batch_code;
        $this->repository->key_no = $this->key_no;
    }

    public function search($product_key)
    {
        $this->initData($product_key);
        $res = [
            'info' => $this->getInfo(),
            'profit_analysis' => $this->getProfitAnalysis(),
            'purchasing_information' => $this->getPurchasingInformation(),
            'sales_information' => $this->getSalesInformation(),
            'related_payment_keys' => $this->getRelatedPaymentKeys(),
        ];
        return $res;
    }

    private function getInfo()
    {
        $sku_info = SkuModel::getSkusInfo([$this->sku_id], ['spu_name', 'attributes', 'image_url', 'product_sku']);
        $stock = $this->repository->getStock();
        $upc_id = $sku_info['product_sku'][$this->sku_id]['upc_id'];
        if($sku_info['product_sku'][$this->sku_id]['upc_more']) {
            $upc_more_arr = explode(',', $sku_info['product_sku'][$this->sku_id]['upc_more']);
            array_unshift($upc_more_arr, $sku_info['product_sku'][$this->sku_id]['upc_id']);
            $upc_id = implode(",\r\n", $upc_more_arr);
        }
        $response = [
            "product_key" => $this->product_key,
            "sku_id" => $this->sku_id,
            "upc_id" => $upc_id,
            "spu_name" => $sku_info["spu_name"][$this->sku_id],
            "attr" => $sku_info["attributes"][$this->sku_id],
            "image_url" => $sku_info["image_url"][$sku_info['product_sku'][$this->sku_id]['spu_id']],

            "init_num" => $stock["all_total_inventory"],
            "in_stock_num" => $stock["total_inventory"],
            "available_sale_num" => $stock["available_for_sale_num"],
            "occupy_num" => $stock["occupied"],
            "lock_num" => $stock["locking"],
            "sold_num" => $stock['all_total_inventory'] - $stock["total_inventory"],
        ];
        return $response;
    }

    public function getProfitAnalysis()
    {
        $cost_price = $this->repository->getCostPrice();
        $request_data = [
            'sku_upc_id' => $this->sku_id,
            'batch_code' => $this->batch_code,
            'page' => 1,
            'page_size' => 500,
        ];
        $logic = D('Report/Income', 'Logic');
        $logic->listData($request_data);
        $incomes = $logic->data['list'];
        $now_date = date('Y-m-d');
        foreach ($incomes as $income) {
            if (!isset($sum_unit_price)) {
                $sum_unit_price = 0;
            }
            if (!isset($count)) {
                $count = 0;
            }
            $sum_unit_price += $income['sale_amount_no_tax']
                * ExchangeRateModel::conversion(
                    $income['currency'],
                    'USD',
                    $now_date,
                    $is_code = false);;
            $count += $income['send_num'];
        }
        $average_sales_price = $sum_unit_price / $count;
        if (empty($average_sales_price) || false == $average_sales_price) {
            $average_sales_price = $gross_profit = $gross_profit_margin = '暂无';
        } else {
            $gross_profit = (double)$average_sales_price - (double)$cost_price['cost_price'];
            $gross_profit_margin = $gross_profit / $average_sales_price * 100;
        }
        $response = [
            'cost_price' => $cost_price['cost_price'],
            'average_sales_price' => $average_sales_price,
            'gross_profit' => $gross_profit,
            'gross_profit_margin' => $gross_profit_margin,
        ];
        $response = DataModel::toNumberFormat($response);
        if ('暂无' != $response['gross_profit_margin']) {
            $response['gross_profit_margin'] .= '%';
        }
        return $response;
    }

    public function getPurchasingInformation($from_batch = null)
    {
        $bill = $this->repository->getBill($from_batch);
        if ($bill['from_batch']) {
            $this->getPurchasingInformation($bill['from_batch']);
        }
        $bill['purchased_by'] = DataModel::getUserNameById($bill['purchased_by']);
        $bill['from_today'] = round((strtotime('today') - strtotime(date('Y-m-d', strtotime($bill['created_at'])))) / 3600 / 24);
        $bill['purchase_order_no'] and $this->purchase_order_no = $bill['purchase_order_no'];
        $bill['action_url'] = '/index.php?m=order_detail&a=purchase_order_detail&relevance_id=' . $bill['relevance_id'];
        return $bill;
    }

    public function getSalesInformation()
    {
        $response = $this->repository->getSalesInformation();
        foreach ($response as $key => $value) {
            $act_str = substr($value['order_no'], 0, 2);
            $response[$key]['sales_by'] = DataModel::getUserNameById($response[$key]['create_user_id']);
            switch ($act_str) {
                case 'RN':
                    $response[$key]['order_type'] = 'B2B';
                    $response[$key]['order_no'] = explode('_', $response[$key]['order_no'])[0];
                    $response[$key]['action_url'] = "/index.php?m=b2b&a=order_list&order_id={$value['b2b_order_id']}#/b2bsend";
                    break;
                default:
                    $response[$key]['order_type'] = 'B2C';
                    $response[$key]['action_url'] = "/index.php?g=OMS&m=Order&a=orderDetail&order_no={$value['op_order_no']}&thrId={$value['order_id']}&platCode={$value['plat_cd']}";
            }

        }
        return $response;
    }


    public function getRelatedPaymentKeys()
    {
        $allocate_response = $this->repository->getRelatedPaymentKeys();
        foreach ($allocate_response as $key => $value) {
            $allocate_response[$key]['action_url'] = '/index.php?g=logistics&m=Expensebill&a=fee_detail&fee_id=' . $value['id'];
        }
        $handle_response = $this->repository->getHandle($this->purchase_order_no);
        unset($key, $value);
        foreach ($handle_response as $key => $value) {
            $handle_response[$key]['action_url'] = '/index.php?m=order_detail&a=payable_detail&id=' . $value['id'];
        }
        return array_merge((array)$handle_response, (array)$allocate_response);
    }

}