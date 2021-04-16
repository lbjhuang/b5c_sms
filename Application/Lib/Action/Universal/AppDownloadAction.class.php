<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2018/2/11
 * Time: 17:06
 */
class  AppDownloadAction extends BaseAction {
    private $filePath;

    public function _initialize()
    {
        $filePath = !is_dir(ATTACHMENT_DIR_IMG) ? sys_get_temp_dir() .'app/' :  ATTACHMENT_DIR_IMG . 'app/';
        $this->filePath = $filePath;
    }

    /**
     * 下载APK包
     */
    public function download(){
        $id = I('get.id');

        $appVersionModel = new AppVersionModel();
        $data = $appVersionModel->getVersionById($id);
        import('ORG.Net.Http');
        $filename = $this->filePath . $data['file_name'];
        Http::download($filename, $filename);
    }
}