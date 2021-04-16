<?php
/**
 * User: yuanshixiao
 * Date: 2018/4/20
 * Time: 17:18
 */

class CommonBaseAction extends Action
{
    public function ajaxSuccess($data = [], $msg = 'success') {
        $this->ajaxReturn(['data'=>$data,'msg'=>$msg,'code'=>2000]);
    }

    public function ajaxError($data = [], $msg = 'error', $code = 3000) {
        $this->ajaxReturn(['data'=>$data,'msg'=>$msg,'code'=>$code]);
    }

    public function test() {
        set_time_limit(0);
        $fileName = '在途金额' . time() . '.csv';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        print(chr(0xEF) . chr(0xBB) . chr(0xBF));
        $fp = fopen('php://output', 'a');
        $head = ['采购单号','供应商','付款账期','具体节点','采购团队','采购同事','采购单创建时间'];
        fputcsv($fp, $head);
        $payment_type = [0=>'指定时间付款',1=>'按实际情况付款'];
        $order = M('relevance_order','tb_pur_')
            ->field('a.procurement_number,a.supplier_id,a.payment_type,a.payment_info,a.payment_company,t.prepared_by,t.prepared_time')
            ->alias('t')
            ->join('tb_pur_order_detail a on a.order_id=t.order_id')
            ->select();
        foreach ($order as $v) {
            $payment_period_arr = json_decode($v['payment_info'],true);
            $payment_period     = '';
            foreach ($payment_period_arr as $val) {
                if($v['payment_type'] == 0) {
                    $payment_period .= "预计付款时间：{$val['payment_date']},付款比例{$val['payment_percent']}%\n";
                }else {
                    $payment_period .= cdVal($val['payment_node'])."{$val['payment_days']}天，付款{$val['payment_percent']}%，预估时间{$val['payment_date_estimate']}\n";
                }
            }
            $arr = [$v['procurement_number'],$v['supplier_id'],$payment_type[$v['payment_type']],trim($payment_period),cdVal($v['payment_company']),$v['prepared_by'],$v['prepared_time']];
            fputcsv($fp,$arr);
        }
    }
}