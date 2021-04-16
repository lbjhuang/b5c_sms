<?php
/**
 * EPR 订单中心服务类
 */

class OrderCenterService extends Service 
{
    /**
     * 处理多条形码数据
     * @param array $data
     * @param bool $type
     * @return array
     * @author Redbo He
     * @date 2020/10/13 11:32
     */
    public function parseUpcMoreData($data, $type = 0)
    {
    
        if (is_bool($type) && $type) {
            $data_list = array_values($data['data']);
        } else if ($type == 1)
        {
            $data_list = isset($data['data']['pageData']) ? $data['data']['pageData'] : [];
        }
        else if ($type == 2) {
            $data_list = isset($data['data']['pageData']) ? $data['data']['pageData'] : [];
            $data_list = array_values($data_list);
        }
        $result = [];
        if($data_list) {
            # 多sku 数据处理
            $product_sku_model = D('Pms/PmsProductSku');
            if((is_bool($type) && $type) || $type == 2 ) {
                $sku_id_arr = array_map(function($v) {
                    return array_keys($v);
                }, $data_list);
                $sku_ids = [];
                foreach ($sku_id_arr as $sku_id) {
                    $sku_ids = array_merge($sku_ids, $sku_id);
                }
                $sku_ids = array_filter($sku_ids);
            }
            else
            {
                $sku_ids = array_column($data_list, 'skuId');
            }
            $where = [ 'sku_id' => ['in', $sku_ids]];
            $product_skus = $product_sku_model->where($where)->field(['sku_id','spu_id','upc_id','upc_more'])->select();
            $product_skus = array_column($product_skus,NULL,'sku_id');
            foreach ($data_list as $k =>  &$item) {
                if(((is_bool($type) && $type) || $type == 2 ) && is_array($item)) {
                    foreach ($item as $kk =>  &$vv) {
                        if(isset($product_skus[$kk]) && $product_skus[$kk]['upc_more']) {
                            $product_sku = $product_skus[$kk];
                            $upc_more_arr = explode(',', $product_sku['upc_more']);
                            array_unshift($upc_more_arr, $product_sku['upc_id']);
                            $vv['gudsOptUpcId'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
                        }
                        $result[$vv['ordId']][$kk] = $vv;
                    }
                }
                else
                {
                    $sku_id = $item['skuId'];
                    if(isset($product_skus[$sku_id]) && $product_skus[$sku_id]['upc_more']) {
                        $product_sku = $product_skus[$sku_id];
                        $upc_more_arr = explode(',', $product_sku['upc_more']);
                        array_unshift($upc_more_arr, $product_sku['upc_id']);
                        $item['gudsOptUpcId'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行
                    }
                    $result[$k] = $item;
                }
            }
            if((is_bool($type) && $type)) {
                $data['data'] = $result;
            }
            else
            {
                $data['data']['pageData'] = $result;
            }
        }
        return $data;
    }
} 