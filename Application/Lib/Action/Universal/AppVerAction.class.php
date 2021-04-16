<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2018/1/12
 * Time: 10:12
 */
use Endroid\QrCode\QrCode;
class AppVerAction extends BaseAction{

    private $filePath;
    public function _initialize()
    {
        parent::_initialize();
        $filePath = !is_dir(ATTACHMENT_DIR_IMG) ? sys_get_temp_dir() .'app/' :  ATTACHMENT_DIR_IMG . 'app/';
        $this->filePath = $filePath;
    }

    public function index()
    {
        $appVersionModel = new AppVersionModel();
        $data = $appVersionModel->getVersionList([]);
        foreach ($data as $key => $ver){
            $ver['download'] = urldecode($ver['download']);
            $ver['qrUrl'] = base64_encode($ver['download']);
            $data[$key] = $ver;
        }

        $domain = ($_SERVER['HTTP_HOST'] == 'erp.stage.com') ? 'erp.stage.com' : C('redirect_audit_addr');
        $this->assign('domain', $domain);
        $this->assign('appList', $data);
        $this->display('index');
    }

    public function getAppVersion()
    {
        $type = I('get.type');
        $system = I('get.system');
        $version = I('get.version');

        if (empty($type) || empty($system) || empty($version)){
            $result = ['code' => 4001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }

        $appVersionModel = new AppVersionModel();
        $data = $appVersionModel->getNewVersion(['type' => $type, 'system' => $system, 'version' => $version]);
        if (empty($data)) {
            $result = ['code' => 4001, 'msg' => 'Have not any version info.', 'data' => null];
        }

        $result = ['code' => 200, 'msg' => 'success', 'data'=> array_pop($data)];
        $this->jsonOut($result);
    }


    /**
     * 上传Apk包
     */
    public function upload(){
        $appId = I('get.appId');
        $uploadModel = new FileUploadModel();
        $uploadModel->filePath = $this->filePath;

        $uploadModel->maxSize = 50 * 1024 * 1024;
        array_push($uploadModel->fileExts, 'apk');
        $result = $uploadModel->uploadFile();
        $fileName = $uploadModel->save_name;
        if (!$result){
            $this->jsonOut(array('code' => 4001, 'msg' => $uploadModel->error, 'data' => ''));
        }

        if($_SERVER['HTTP_HOST'] == 'erp.stage.com'){
            $domain = 'http://erp.stage.com';
        } else {
            $domain = 'http://' . C('redirect_audit_addr');
        }
        $update['download'] = $domain . '/index.php?g=universal&m=appDownload&a=download';
        $update['file_name'] = $fileName;
        //$appVersionMode = new AppVersionModel();
        //$appVersionMode->updateVersion($update, ['id' => $appId]);
        $this->jsonOut(array('code' => 2000, 'msg' => 'success', 'data' => $update));
    }

    /**
     * 添加版本信息
     */
    public function add()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        if (empty($params)){
            $this->jsonOut(['code' => 4001, 'msg' => L('INVALID_PARAMS'), 'data' => null]);
        }

        if (empty(trim($params['name'])) || strlen($params['name']) > 20){
            $this->jsonOut(['code' => 4001, 'msg' => 'The length of name should not overflow 20.', 'data' => null]);
        }

        $versionModel = new AppVersionModel();
        $isExist = $versionModel->checkExist($params);
        if ($isExist){
            $this->jsonOut(['code' => 200, 'msg' => L('HAS_EXIST'), 'data' => $isExist]);
        }

        $res = $versionModel->addVersion($params);
        $this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $res]);
    }

    /**
     * 修改版本信息
     */
    public function edit()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);

        if (empty($params)){
            $this->jsonOut(['code' => 4001, 'msg' => L('INVALID_PARAMS'), 'data' => null]);
        }

        if (empty(trim($params['name'])) || strlen($params['name']) > 20){
            $this->jsonOut(['code' => 4001, 'msg' => 'The length of name should not overflow 20.', 'data' => null]);
        }

        $versionModel = new AppVersionModel();
        $updateRes = $versionModel->updateVersion($params, ['id'=>$params['appId']]);
        $this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $updateRes]);
    }


    public function delete(){

    }

    /**
     * 指定文本，生成QR CODE图片。
     */
    public function makeQrCode()
    {
        $str = I('get.text');
        $encode = I('get.code', 'base64');

        //解码字符串，如果URL请务必使用 base64
        $text = $encode == 'base64' ? base64_decode($str) : ($encode == 'urlencode' ? urldecode($str) : $str);
        try {
        $qrCode = new QrCode();
        $qrCode->setText($text)
            ->setSize(150)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(['r' => 230, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
            ->setImageType(QrCode::IMAGE_TYPE_PNG);

            // now we can directly output the qrcode
            header('Content-Type: '.$qrCode->getContentType());
            $qrCode->render();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}