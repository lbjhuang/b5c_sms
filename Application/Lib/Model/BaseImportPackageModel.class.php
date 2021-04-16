<?php
/**
 * 压缩包导入基类
 * User: fuming
 * Date: 2018/12/23
 */
class BaseImportPackageModel extends BaseImportExcelModel
{
    public $unpack_path = '/opt/b5c-disk/unpack/';//解压路径

    public $package_path;//压缩包保存路径
    
    public function unPack() {
        $this->uploadPackage();//上传压缩包
           //$this->unpack_path = 'F:/opt/b5c-disk/unpack/';
        $archive = new PHPZIP();
        if (!file_exists($this->unpack_path)) {
            mkdir($this->unpack_path);
        }
        $this->unpack_path = $this->unpack_path. uniqid(). '/';
        if (!file_exists($this->unpack_path)) {
            mkdir($this->unpack_path);
        }
        $content = $archive->unzip($this->package_path, $this->unpack_path);
        foreach ($content as $k => $v) {
            if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $k, $match)) {
                throw new Exception('文件夹和文件不能包含中文');
            }
        }
        return $content;
    }
    
    public function uploadPackage() {
        $fd = new FileUploadModel();
        $upload_info = $fd->uploadFileArr();
        if (!$upload_info) {
            throw new Exception($fd->error);
        }
        $this->package_path = $upload_info[0]['savepath']. $upload_info[0]['savename'];
    }
    
}
