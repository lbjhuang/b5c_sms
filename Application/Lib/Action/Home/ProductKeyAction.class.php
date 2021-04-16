<?php

/**
 * Class ProductKeyAction
 */
class ProductKeyAction extends BaseAction
{
    protected $service;

    public function search()
    {
        try {
            $product_key = I('product_key');
            $this->checkProductKey($product_key);
            $this->service = new ProductKeyService();
            $response_data = $this->service->search($product_key);
            $this->ajaxSuccess($response_data);
        } catch (Exception $exception) {
            $this->ajaxError($exception->getMessage(), $exception->getMessage());
        }
    }

    public function checkProductKey($product_key)
    {
        if (24 != strlen($product_key)) {
            throw new Exception('错误 key 长度');
        }
        if (22 != strlen(str_replace('-', '', $product_key))) {
            throw new Exception('错误 key 格式');
        }
    }
}

