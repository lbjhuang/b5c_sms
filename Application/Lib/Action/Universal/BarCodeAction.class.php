<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/11/9
 * Time: 16:31
 */
class BarCodeAction extends BaseAction{
    
    private $typeList = [
        'BCGcodabar',
        'BCGcode11',
        'BCGcode39',
        'BCGcode39extended',
        'BCGcode93',
        'BCGcode128',
        'BCGean8',
        'BCGean13',
        'BCGgs1128',
        'BCGisbn',
        'BCGi25',
        'BCGs25',
        'BCGmsi',
        'BCGupca',
        'BCGupce',
        'BCGupcext2',
        'BCGupcext5',
        'BCGpostnet',
        'BCGintelligentmail',
        'BCGothercode'
    ];
    
    public function _initialize()
    {
        vendor('Barcode.BCGColor');
        vendor('Barcode.BCGBarcode');
        vendor('Barcode.BCGDrawing');
        vendor('Barcode.BCGFontFile');
    }
    
    /**
     * 根据指定的码类型和码值，生成条码图片
     */
    public function getCode(){
        $codeNumber = I('get.codeNumber');
        $codeType = I('get.codeType');
        if (!preg_match('/^[A-Za-z0-9(+-_)*]+$/', $codeNumber)) {
            die(L('INVALID_PARAMS'));
        }
        
        if (!in_array($codeType, $this->typeList)){
            echo 'Method ERROR';
        }
        $drawException = null;
        $font = new BCGFontFile(VENDOR_PATH.'Barcode/arial.ttf', 12);
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);
        try{
            vendor('Barcode.'. $codeType,'','.Barcode.php');
            $code = new $codeType();
            $code->setScale(1);
            $code->setThickness(30);
            $code->setForegroundColor($colorFront);
            $code->setBackgroundColor($colorBack);
            $code->setFont($font);
            $code->parse($codeNumber);
        } catch (Exception $e){
            $drawException = $e;
        }
    
        // Drawing Part
        $drawing = new BCGDrawing('', $colorBack);
        if($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            $drawing->setDPI(300);
            $drawing->draw();
        }
        
        header('Content-Type: image/png');
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }
    
    /**
     * 商品条形码
     */
    public function getEan13Code()
    {
        $codeNumber = I('get.codeNumber');
        if (empty($codeNumber) || !preg_match('/^[A-Za-z0-9(+-_)*]+$/', $codeNumber)){
            die(L('INVALID_PARAMS'));
        }
    
        $font = new BCGFontFile(VENDOR_PATH.'Barcode/arial.ttf', 18);
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);

        // Barcode Part
        vendor('Barcode.BCGean13','','.barcode.php');
        $code = new BCGean13();
        $code->setScale(2);
        $code->setThickness(30);
        $code->setForegroundColor($colorFront);
        $code->setBackgroundColor($colorBack);
        $code->setFont($font);
        $code->parse($codeNumber);

        // Drawing Part
        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
    
        header('Content-Type: image/png');
    
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }
    
    /**
     * GS1-128CODE码又叫做 EAN-128码
     * 通常用与物流编码
     */
    public function getGs1128Code()
    {
        $codeNumber = I('get.codeNumber');
        if (empty($codeNumber)){
            die(L('INVALID_PARAMS'));
        }
        
        $font = new BCGFontFile(VENDOR_PATH.'Barcode/arial.ttf', 18);
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);

        // Barcode Part
        vendor('Barcode.BCGgs1128','','.barcode.php');
        $code = new BCGgs1128();
        $code->setScale(2);
        $code->setThickness(30);
        $code->setForegroundColor($colorFront);
        $code->setBackgroundColor($colorBack);
        $code->setFont($font);
        $code->setStrictMode(true);
        //$code->parse('011234567891234');
        try{
            $code->parse($codeNumber);
        } catch (Exception $e){
            die(L('INVALID_PARAMS'));
        }

        // Drawing Part
        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
    
        header('Content-Type: image/png');
    
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }
    
    /**
     * 物流单上的条码 39Code码格式图片
     */
    public function getCode128()
    {
        $codeNumber = I('get.codeNumber');
        if (empty($codeNumber)){
            die(L('INVALID_PARAMS'));
        }
        
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);
        $font = new BCGFontFile(VENDOR_PATH.'Barcode/arial.ttf', 18);
        
        vendor('Barcode.BCGcode128','','.barcode.php');
        $code = new BCGcode128();
        $code->setScale(2);
        $code->setThickness(30);
        $code->setForegroundColor($colorFront);
        $code->setBackgroundColor($colorBack);
        $code->setFont($font);
        $code->setStart(NULL);
        $code->setTilde(true);
        try{
            $code->parse($codeNumber);
        } catch (Exception $e){
            die(L('INVALID_PARAMS'));
        }

        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
    
        header('Content-Type: image/png');
    
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }
    
    /**
     * 物流单号 39码
     */
    public function getCode39()
    {
        $codeNumber = I('get.codeNumber');
        if (empty($codeNumber)){
            die(L('INVALID_PARAMS'));
        }
        
        $colorFront = new BCGColor(0, 0, 0);
        $colorBack = new BCGColor(255, 255, 255);
        $font = new BCGFontFile(VENDOR_PATH.'Barcode/arial.ttf', 18);

        // Barcode Part
        vendor('Barcode.BCGcode39extended','','.barcode.php');
        $code = new BCGcode39extended();
        $code->setScale(1);
        $code->setThickness(30);
        $code->setForegroundColor($colorFront);
        $code->setBackgroundColor($colorBack);
        $code->setFont($font);
        $code->setOffsetX(0);
        $code->setChecksum(false);
    
        try{
            $code->parse($codeNumber);
        } catch (Exception $e){
            die(L('INVALID_PARAMS'));
        }
        
        // Drawing Part
        $drawing = new BCGDrawing('', $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
    
        header('Content-Type: image/png');
    
        $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    }
    
}

