<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/1
 * Time: 16:12
 */
include_once './ThinkPHP/Extend/Vendor/PHPMailer/class.phpmailer.php';

class SMSEmail extends PHPMailer
{

    public function __construct($exceptions = true)
    {
        parent::__construct($exceptions);
        $this->isSMTP();
        $this->SMTPAuth     = true;
        $this->Host         = C('email_host');
        $this->Port         = C('email_port');
        $this->Username     = C('email_address');
        $this->Password     = C('email_password');
        $this->From         = C('email_address');
        $this->FromName     = C('email_user');
        $this->SMTPSecure   = 'ssl';
        $this->CharSet      = 'UTF-8';
    }

    /**
     * @param $address mixed 收件人
     * @param $title string 邮件标题
     * @param $content string 邮件内容
     * @param null $cc 抄送
     * @param null $attachment 附件
     * @return bool
     */
    public function sendEmail($address,$title,$content,$cc = null,$attachment = null) {
        $to = [];
        try {
            $this->isHTML(true);
            //收件人
            if(is_array($address)) {
                foreach ($address as $v) {
                    $to[] = $v;
                    $this->addAddress($v);
                }
            }else {
                $to[] = $address;
                $this->addAddress($address);
            }
            //抄送
            if($cc){
                if(is_array($cc)) {
                    foreach ($cc as $v){
                        $to[] = $v;
                        $this->addCC($v);
                    }
                }else {
                    $to[] = $cc;
                    $this->addCC($cc);
                }
            }
            //附件
            if($attachment) {
                if(is_array($attachment)) {
                    foreach ($attachment as $v) {
                        $this->AddAttachment($v);
                    }
                }else {
                    $this->AddAttachment($attachment);
                }
            }
            $this->Subject = $title;
            $this->Body = $content;
            $this->Send();
            $this->ClearAllRecipients();
            $this->addLog($to,$content);
            return true;
        } catch (phpmailerException $e) {
            $this->error = $e->getMessage();
            ELog::add(
                [
                    'message'=>'邮件发送失败'.$this->getError(),
                    'info'=>[
                        'address'=>$address,
                        'title'=>$title,
                        'content'=>$content,
                        'cc'=>$cc,
                        'attachment'=>$attachment
                    ]
                ],
                ELog::ERR
            );
            return false;
        }
    }

    public function addLog($to,$content) {
        $email_log['from']          = C('email_address');
        $email_log['create_user']   = $_SESSION['m_loginname'];
        $email_log['send_time']     = date('Y-m-d H:i:s');
        $email_log['to']            = implode(',',$to);
        $email_log['content']       = $content;
        $email_log['title']       = $this->Subject;
        M('email','tb_')->add($email_log);
    }

    public function getError() {
        return $this->error;
    }
}