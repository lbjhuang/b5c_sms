<?php
/**
 * User: yuanshixiao
 * Date: 2018/4/10
 * Time: 17:30
 */
import('ORG.Net.Http');
class FileAction extends Action
{
    public function template_download() {
        $name = I('get.name');
        import('ORG.Net.Http');
        $filename = TMPL_PATH.'Common/File/'.$name;
        Http::download($filename, $filename);
    }

    /**
     * 下载文件
     */
    public function download() {
        $download = new FileDownloadModel();
        $download->fname = I('get.file');
        $download->downloadFile();
    }

    /**
     * 下载多个文件并打包成zip
     */
    public function downloadZip() {

        $download = new FileDownloadModel();
        $request_data = DataModel::getData();
        $download->files = $request_data['files'];
        $download->file_name = $request_data['save_name'];
        $download->downloadZip();
    }

    /**
     * wangEditor编辑器上传图片接口
     */
    public function editor_file_upload() {
        $fd = new FileUploadModel();
        $res = $fd->uploadFileArr();
        if(!$res) {
            $this->ajaxReturn(['errno'=>1,'msg'=>$fd->error]);
        }else {
            $img_addr_arr = [];
            foreach ($res as $v) {
                $img_addr_arr[] = U('download',['file'=>$v['savename']],true,false,true);
            }
            $this->ajaxReturn(['errno'=>0,'data'=>$img_addr_arr]);
        }
    }

    public function file_upload() {
        $fd = new FileUploadModel();
        $res = $fd->uploadFileArr();
        if ($res) {
            $info = $res;
            $this->ajaxReturn(1,$info,1);
        } else {
            $this->error($fd->error, '', true);
        }
    }


    /**
     * 下载文件
     */
    function downloadAttachment() {
        $file = I('get.file');
        Http::download($file);
    }
}