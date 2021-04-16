<?php  
trace('--test--','find vo');
// mark file/db ------ 1 day 7:00  once
$hour = date('H');
if($hour>9 and $hour<24){
	//  just send email once 
	//  now 1 server  ,  if not  , need change to use db mark.
	$filenamepath = RUNTIME_PATH.'Mails/resume_email_mark/';
	if(!is_dir($filenamepath)) mkdir($filenamepath, 0777, true);
	$filename = $filenamepath.date('Ymd').'.log';   //用文件以时间命名判断是否过了一天
	$check_is_write = is_writable($filenamepath);
	if(!$check_is_write){
	 	return null;
	}
	// if not have , do this send coding
	if(!file_exists($filename)){
		/*if ($res = D('TbHrResume')->sendMail()) {    //发送邮件成功
			echo "本周有面试安排,邮件已自动发送";
		}else{
			echo "无面试安排";
		}*/
			file_put_contents($filename,'huanzhu',LOCK_EX);
	}
}


