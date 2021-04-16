<?php

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class MyPhpWordModel extends Model
{


    public function exportWord($params)
    {
        $PHPWord = new \PhpOffice\PhpWord\PhpWord();
        // New portrait section
        $section= $PHPWord->createSection();
        // Add text elements
        $trademark_name = $params['trademark_name'];
        $company_name = $params['company_name'];
        $authorize_body = $params['authorize_body'];
        $authorize_period_start = $params['authorize_period_start'];
        $authorize_period_end = $params['authorize_period_end'];
        $record_created_time = $params['created_at'];

        //时间格式处理
        $authorize_period_start_year = date('Y',strtotime($authorize_period_start));
        $authorize_period_start_month = date('m',strtotime($authorize_period_start));
        $authorize_period_start_day = date('d',strtotime($authorize_period_start));
        $authorize_period_end_year = date('Y',strtotime($authorize_period_end));
        $authorize_period_end_month = date('m',strtotime($authorize_period_end));
        $authorize_period_end_day = date('d',strtotime($authorize_period_end));

        $record_year = date('Y',strtotime($record_created_time));
        $record_month = date('m',strtotime($record_created_time));
        $record_day = date('d',strtotime($record_created_time));

        $str1= "授权方：{$company_name}";
        $str2= "被授权方：{$authorize_body}";
        $str3= "        兹授权【{$authorize_body}】为【{$company_name}】【{$trademark_name}】品牌的产品销售商，并许可被授权方使用【{$trademark_name}】商标进行品牌、产品的展示和宣传。";
        $str4= "        授权渠道：被授权方于【 】平台（【网址】）开设的店铺，店铺ID：【 】。";
        $str5= "        授权期限（授权期限）：【{$authorize_period_start_year}】年【{$authorize_period_start_month}】月【{$authorize_period_start_day}】日至【{$authorize_period_end_year}】年【{$authorize_period_end_month}】月【{$authorize_period_end_day}】日。";
        $str6 = "       授权方保留于授权期间内随时撤销上述授权的权利。若授权方希望撤销授权，应当提前30天以书面形式通知被授权方。本授权书于授权方盖章后生效。";
        $str7 = "       特此授权。";
        $str8 = "                                                                                     授权方盖章：";
        $str9 = "                                                                      日期：【{$record_year}】年【{$record_month}】月【{$record_day}】日";
        $title ='授权书';
        $section->addText($title,'titleFont','titleStyle');
        $section->addTextBreak(2);
        $section->addText($str1,'contentFont','contentStyle');
        $section->addText($str2,'contentFont','contentStyle');
        $section->addTextBreak(2);
        $section->addText($str3,'contentFont','contentStyle');
        $section->addText($str4,'contentFont','contentStyle');
        $section->addText($str5,'contentFont','contentStyle');
        $section->addText($str6,'contentFont','contentStyle');
        $section->addText($str7,'contentFont','contentStyle');
        $section->addTextBreak(5);
        $section->addText($str8,'contentFont','contentStyle');
        $section->addTextBreak(2);
        $section->addText($str9,'contentFont','contentStyle');

        //样式设置
        $PHPWord->addFontStyle('titleFont', array('bold' => true, 'italic' => false,'size' => 16, 'align'=> 'center'));
        $PHPWord->addFontStyle('contentFont', array('bold'=> false,'size' => 11));
        $PHPWord->addFontStyle('colorStyle1', array('size' => 10, 'Color'=>'f20505'));

        $PHPWord->addParagraphStyle('titleStyle', array('align' =>'center','spaceAfter' => 100));
        $PHPWord->addParagraphStyle('contentStyle', array('spaceAfter' => 150, 'spacing'=>120));

        $objWriter = IOFactory::createWriter($PHPWord, 'Word2007');
        $file_name = 'auth_book'. date("YmdHis").'.docx';
        $objWriter->save($file_name);

        // 下载
        header("Content-Disposition: attachment; filename={$file_name}");
        readfile($file_name); // or echo file_get_contents($temp_file);
        unlink($file_name);  // remove temp file
    }

}