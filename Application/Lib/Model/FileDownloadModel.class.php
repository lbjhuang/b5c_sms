<?php

/**
 * 文件下载工具类
 * 
 */  
class FileDownloadModel extends BaseModel
{
    public $path = ATTACHMENT_DIR_IMG;
    
    public $fname = '';

    public $file_name = '';

    public $files = [];

    public $depr = '/';
    
    private $file = '';

    public $origin_file_name = '';//原始文件名
    
    public function __construct()
    {
        import('ORG.Net.Http');
    }
        
    public function downloadFile()
    {
       
        if ($this->checkFileExists()) {
            Http::download($this->file, $this->origin_file_name);
        }

        return false;
    }

    public function downloadPathFile()
    {
        if (file_exists($this->file)) {
            Http::download($this->file);
        }
        return false;
    }

    public function downloadZip()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $zip = new ZipArchive();
        //$fileNameArr = ['aaa.jpg', 'BR_SYS_FILE_20201022143109_2452.rar', 'test.txt', 'BR_SYS_FILE_20201022141917_7297.xls', '标准核销导入模板.xlsx', '调拨2.1需求文档.pdf'];
        $fileNameArr = $this->files;
        //$filename = "付款单：附件.zip";
        $filename = $this->file_name;
        $zip->open($filename, ZipArchive::CREATE);   //打开压缩包
        foreach ($fileNameArr as $file => $origin_file) {
            // 如果文件名是中文名则先转英文再添加到压缩包
            $filePath = ATTACHMENT_DIR_IMG . $file;
            $zip->addFile(iconv('UTF-8','GBK',$filePath), iconv('UTF-8','GBK',basename($origin_file)));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        $file = fopen($filename, "r");
        //输出压缩文件提供下载
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); //
        header('Content-Length: ' . filesize($filename)); //

        while (!feof($file)) {
            fpassthru($file); // 输出至浏览器
            fclose($file);
            unlink($filename); //删除临时文件
            exit;
        }
    }

    public function transcoding()
    {
        $this->fname = iconv("utf-8", "gb2312", $this->fname);
    }
    
    public function checkFileExists()
    {
        
        if(count(explode($this->depr,$this->fname)) > 1) {
            $this->file = $this->fname;
        }else {
           
            $this->file = $this->path . $this->depr . $this->fname;
        }
        $this->file = iconv('UTF-8', 'GB2312', $this->file);
       
        if (!file_exists($this->file)) return false;
        return true;
    }

    public function getFilePath($fname = '') {
        return $this->path . $this->depr . ($fname ? $fname : $this->fname);
    }
}