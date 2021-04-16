<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 10:56
 */

class LogAction extends ScmBaseAction
{
    private $info_list = ['提交需求', '重选方案'/*, '需求审批'*/, '创建报价', '提交报价', '选择报价', '提交方案', '采购确认', '提交方案', '销售领导审批', '现金效率审批', '需求侧-上传po','需求侧-保存法务审批意见','需求侧-生成带水印PO', '需求侧-法务审批通知邮件', '报价侧-上传po','报价申请提前','同意报价提前申请','取消报价提前申请','报价侧-保存法务审批意见','报价侧-生成带水印PO', '报价侧-法务审批通知邮件', '需求侧-法务审批', '报价侧-法务审批', '需求侧-归档PO', '报价测-归档PO', '创建订单', '创建采购订单', '撤回到需求草稿', '放弃需求', '删除报价', '处理采购申请-允许所有采购修改报价', '处理采购申请-不同意', '重发审批提醒邮件'];

    public function log_list() {
        import('ORG.Util.Page');
        $param  = $this->params();
        $count  = D('ActionLog')->logCount($param);
        $_GET['p'] = $_REQUEST['p'];
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = D('ActionLog')->logList($param, $page->firstRow.','.$page->listRows);
        $this->ajaxSuccess(['list'=>$list,'page'=>['total_rows'=>$count]]);
    }

    public function log_detail() {
        $data     = I('request.');
        $demand_l   = D('Scm/Demand','Logic');
        $demand_l->logDetail($data);
        $this->ajaxReturn($demand_l->getRet());
    }

    public function log_info_list()
    {
        $this->ajaxReturn(['code' => 2000, 'msg' => 'success', 'data' => $this->info_list]);
    }

    public function log_data($id)
    {
        header('Content-Type:text/html; charset=utf-8');
        $record = D('ActionLog')->find($id);
        $record['request'] = gzinflate($record['request']);
        printr($record);
    }

    public function log_set($id)
    {
        header('Content-Type:text/html; charset=utf-8');
        $record = D('ActionLog')->field('id,user,info,action_name')
            ->where(['demand_id' => $id])
            ->select();
        printr($record);
    }

    public function logs($date = '', $name = 'checkover') {
        header('Content-Type:text/html; charset=utf-8');
        //checkover_{date}
        //err_{date}
        //snd_{date}
        strlen($date) != 4 or $date = date('Y') . $date;
        $date or $date = date('Ymd');
        $logFile = '/opt/logs/logstash/scm/' . $name . '_' . $date . '.log';
        $file = fopen($logFile, 'r');
        $content = fread($file, filesize($logFile));
        $content = htmlspecialchars($content);
        //iconv('utf-8', 'GBK//IGNORE', $content)
        /*$encode = mb_detect_encoding($content, array('ASCII','GB2312','GBK','UTF-8'));
        if ($encode != 'UTF-8') {
            $content = iconv($encode, 'utf-8', $content);
        }*/
        $content = str_replace( PHP_EOL, '</br>', $content);
        fclose($file);
        die($content);
    }
}