<?php
/**
 *  ERP订单列表相关服务类 
 *  
 */
class OrderListService extends Service
{

    /**
     * 创建订单发票任务记录
     * @param array $orderIds
     * @param array $types
     * @author Redbo He
     * @date 2020/9/18 19:30
     */
    public function saveOrderInvoiceTask(array $orderIds, array $types)
    {
        if (empty($orderIds)) return false;
        $orderModel = M("OpOrder", 'tb_');
        $orders     = $orderModel->field([
            'tb_op_order.ID', 'tb_op_order.ORDER_ID', 'tb_op_order.STORE_ID',
            'tb_op_order.PLAT_CD', 'tb_op_order.ADDRESS_USER_COUNTRY_ID',
            'tb_op_order.PLAT_NAME',"tb_op_order.ORDER_CREATE_TIME",
            'tb_ms_store.ID as STORE_ID','tb_ms_store.STORE_NAME',
            "tb_ms_store.MERCHANT_ID", "tb_ms_store.PLAT_CD"
        ])
            ->join("tb_ms_store ON tb_op_order.STORE_ID = tb_ms_store.ID")
            ->where([
                'tb_op_order.ID' => ['in', $orderIds]
            ])
            ->select();
        if (empty($orders)) {
            return ['status' => false, 'msg' => "订单数据不存在"];
        }
        $order_invoice_store_ids = C('order_list_export_invoice_store_ids');
        $result               = array_map(function ($v) use ($order_invoice_store_ids) {
            if (in_array($v['STORE_ID'], $order_invoice_store_ids)) {
                return $v;
            }
        }, $orders);
        $result = array_filter($result);
        if (empty($result)) {
            return ['status' => false, 'msg' => "所选订单数据无法创建发票"];
        }
        $store_ids = array_column($orders,'STORE_ID');
        $store_counter_map = C('invoice_store_counter_map');
        $order_list_counter_store_ids = array_keys($store_counter_map);//需计数店铺id
        $intersection = array_intersect($store_ids, $order_list_counter_store_ids);
        $model = D("B5cInvoiceTask");
        if($intersection)
        {
            # 查询最大的结果
//            $count_start_num = C('order_list_export_counter_start' ) ?:0 ;
            //分别记录店铺最大计数值
            foreach ($order_list_counter_store_ids as $store_id) {
                $counter_last_record[$store_id] = $model
                    ->where([
                        'store_id' => $store_id,
                        'invoice_counter' =>  ['exp','is not null']
                    ])
                    ->max('invoice_counter');
            }
//            if($counter_last_record) {
//                $count_start_num = $counter_last_record['invoice_counter'];
//            }

        }

        // 创建订单数据
        $country_ids     = array_unique(array_column($result, "ADDRESS_USER_COUNTRY_ID"));

        $user_area_model = M("MsUserArea", "tb_");
        $countries       = $user_area_model->field([
            'id', 'zh_name', 'area_no', 'two_char',

        ])
            ->where([
                'id' => ['in', $country_ids]
            ])
            ->select();
        $countries       = array_column($countries, NULL, 'id');
        $insert_data = [];
    
        foreach ($result as $k => $order) {
            $invoice_counter = NULL;
            $prefix_number = $store_counter_map[$order['STORE_ID']]['prefix_number'];//前缀数字
            if(in_array($order['STORE_ID'], $order_list_counter_store_ids) && isset($prefix_number)) {
                $old_invoice_counter = $model->where(['order_inc_id'=>$order['ID'], 'store_id'=>$order['STORE_ID']])->order('id desc')->getField('invoice_counter');
                //德国乐天1店和3店
                if (empty($old_invoice_counter)) {
                    $count_start_num = $counter_last_record[$order['STORE_ID']] ?: 0;
                    $invoice_counter = $count_start_num + $k + 1;
                    if (substr($invoice_counter, 0, 1) != $prefix_number) {
                        //判断到前缀数字有变化，位数增加一位，前缀保持不变，
                        $counter_last_record[$order['STORE_ID']] =  $prefix_number. '1'. substr($invoice_counter, 1);
                        $invoice_counter = $counter_last_record[$order['STORE_ID']];
                    }
                } else {
                    $invoice_counter = $old_invoice_counter;
                }
            } else if(in_array($order['STORE_ID'], $order_list_counter_store_ids) && !isset($prefix_number)) {
                //德国乐天2店
                $count_start_num = $counter_last_record[$order['STORE_ID']] ?: 0;
                $invoice_counter = $store_counter_map[$order['STORE_ID']]['start_counter'] + $count_start_num + $k + 1;
            }
            $tmp_data = [
                'order_inc_id'  => $order['ID'],
                'order_id'      => $order['ORDER_ID'],
                'platform_cd'   => $order['PLAT_CD'],
                'store_id'      => $order['STORE_ID'],
                'country_id'    => $order['ADDRESS_USER_COUNTRY_ID'],
                'country_name'  => isset($countries[$order['ADDRESS_USER_COUNTRY_ID']]) ? $countries[$order['ADDRESS_USER_COUNTRY_ID']]['zh_name'] : '',
                'platform_name' => $order['PLAT_NAME'],
                'store_name'    => $order['STORE_NAME'],
                "created_at"    => date("Y-m-d H:i:s"),
                "updated_at"    => date("Y-m-d H:i:s"),
                "invoice_created_at" => date("Y-m-d H:i:s"),#发票生成时间设置为发起申请时间
                'created_by'    => $_SESSION['m_loginname'],
                'order_created_at'=> $order['ORDER_CREATE_TIME'],
                'download_count' => 0,
                "status" => B5cInvoiceTaskModel::STATUS_NOT_FINISH,
                'invoice_counter' => $invoice_counter
            ];
            foreach ($types as $type) {
                $tmp_data['type']         = $type;
                $ext_name                 = B5cInvoiceTaskModel::$type_str_map[$type];
                $tmp_data['invoice_name'] = 'invoice' .'_' .  $tmp_data['order_inc_id'] .'_' .  $tmp_data['store_name'] .'_' . $tmp_data['store_id'].'_' .date('ymdHis') . '.' . $ext_name;
                $insert_data[]            = $tmp_data;
            }
        }
        $ids = [];
        $res = true;
        try
        {
            M()->startTrans();
            foreach ($insert_data as $item)
            {
                $res = $model->add($item);
                if($res)
                {
                    $ids[] = $res;
                }
                else
                {
                    throw new \Exception("数据插入异常");
                }
            }
            M()->commit();
        } catch (\Exception $e) {
            $res = false;
            Log::record("[订单发票数据插入异常]" . $e->__toString(), Log::ERR);
            M()->rollback();
        }
        if ($res)
        {
            #事务结束  请求Java接口后直接断开 不处理返回值
            
            $requestData['orders'] = [];
            $reply = [];
            foreach($insert_data as $value){
                if(!in_array($value['order_id'],$reply)){
                    $tmpOrder = [];
                    $tmpOrder['orderId'] = $value['order_id'];
                    $tmpOrder['platCd'] = $value['platform_cd'];
                    array_push($requestData['orders'], $tmpOrder);
                    array_push($reply, $value['order_id']);

                }
                
            }
            Logs(['url'=> THIRD_DELIVER_GOODS.'/op/invoice/execute','data'=> $requestData],__FUNCTION__, 'orderListCreateInvoice');
            HttpTool::Curl_post(THIRD_DELIVER_GOODS.'/op/invoice/execute', $requestData, 1, 1);
            
            return ['status' => true, 'msg' => "数据创建成功", 'data' => ['ids' => $ids]];
        }
        else
        {
            return ['status' => false, 'msg' => "数据创建失败，请稍后再试"];
        }

    }

    /**
     * 判断订单
     * @param array $orders
     * @author Redbo He
     * @date 2020/9/21 10:02
     */
    public function checkOrderListCanExportInvoice(array $orders)
    {
        if($orders)
        {
           foreach ($orders as &$order) {
               $order_invoice_store_ids = C('order_list_export_invoice_store_ids');
               $order['can_export_invoice'] = 0;
               if(in_array($order['STORE_ID'], $order_invoice_store_ids))
               {
                   $order['can_export_invoice'] = 1;
               }
           }
        }
        return $orders;
    }





}