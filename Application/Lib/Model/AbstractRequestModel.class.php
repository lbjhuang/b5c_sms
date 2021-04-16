<?php
/**
 * 调拨抽象类
 */
abstract class AbstractRequestModel
{
    private $_response;
    private $_responseData;
    private $_request;
    private $_requestData;
    private $_serviceName;
    private $_error;
    private $_errorCode;
    private $_errorMessage;
    public $limiter = '/';
    public $requestData;
    public $host;
    private $_url;

    public function __construct()
    {
        $this->_request = new \stdClass();
        $this->_response = new \stdClass();
    }

    /**
     * 提交请求
     * @param string $serviceName 接口服务名称
     * @param mixed $requestData  接口请求的data部分数据
     * @return static the object itself
     * @throws InvalidConfigException 如果未设置接口服务名
     */
    public function submitRequest($serviceName, $requestData)
    {
        try {
            is_null($serviceName) or $this->setServiceName($serviceName);
            is_null($requestData) or $this->setRequestData($requestData);

            if ($this->_serviceName == null) {
                throw new \InvalidArgumentException('The "serviceName" property must be set.');
            }
            if ($this->host)
                $requestAddress = $this->host . $this->limiter . $this->_serviceName;
            else
                $requestAddress = HOST_URL_API . $this->limiter . $this->_serviceName;

            $this->_url = $requestAddress;
            $this->_setRequest();
            $result = curl_get_json($requestAddress, json_encode($this->getRequestData()));
            Logs([$requestAddress,json_encode($this->getRequestData()),$result],'submitRequest','Abstract');
            $this->_setResponse(json_decode($result));
        } catch (\Exception $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();
        }

        $this->_catchMe();

        return $this;
    }

    /**
     * 接口request部分
     */
    public function getRequest()
    {
        return $this->_request;
    }

    public function getRequestData()
    {
        return $this->_requestData;
    }

    public function setRequestData($data)
    {
        $this->_requestData = $data;
    }

    private function _setRequest()
    {
        $this->_request->data = $this->getRequestData();
    }

    /**
     * 接口response部分
     */
    public function getResponse()
    {
        return $this->_response;
    }

    public function getResponseData()
    {
        return $this->_responseData;
    }

    public function getResponseSuccess()
    {
        return isset($this->_response->code) and $this->_response->code == 2000;
    }

    public function getResponseErrorCode()
    {
        if ($this->_error) return 1000;
        return isset($this->_response->code)?$this->_response->code:null;
    }

    public function getResponseErrorMessage()
    {
        if ($this->_error) return $this->_error;
        return isset($this->_response->msg)?$this->_response->msg:null;
    }

    private function _setResponse($result)
    {
        if (is_object($result)) {
            $this->_response = $result;
            $this->_responseData = $this->_response->data;
            if ($this->_response->code != 2000) {
                $this->_errorCode = $this->_response->code;
                $this->_errorMessage = $this->_response->msg;
            }
        }
    }

    /**
     * 服务名称，资源地址名
     */
    public function setServiceName($name)
    {
        $this->_serviceName = $name;
    }

    public function getServiceName()
    {
        return $this->_serviceName;
    }

    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * 返回错误信息数组对象
     */
    public function getError()
    {
        return (object)['code' => $this->getErrorCode(), 'message' => $this->getErrorMessage()];
    }

    public function getRequestUrl()
    {
        return $this->_url;
    }

    /**
     * 记录接口日志
     */
    public function _catchMe()
    {
        $filePath = '/opt/logs/logstash/';
        $fileName = 'logstash_' . date('Ymd') . '_erp_json.log';
        $a = parse_url($_SERVER["REQUEST_URI"]);
        parse_str($a["query"], $s);
        $a = $s['a'];
        $m1 =  $s['m'];
        $m = M('');
        //获取操作日志
        $action = $s ['a'];
        $data ['uId']           = create_guid();
        $data ['noteType']      = 'N001940200';
        $data ['source']        = 'N001950500';
        $data ['ip']            = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
        $data ['space']         = null;
        $data ['cTime']         = date('Y-m-d H:i:s');
        $data ['cTimeStamp']    = time();
        $data ['action']        = $s ['a'];
        $data ['model']         = $s ['m'];
        $data ['msg']           = json_encode([
            'model' => MODULE_NAME,
            'msg'   => [
                'GET' => $_GET,
                'POST'=> $_POST,
                'action' => $s ['m'],
                'operation' => $s ['a'],
                'uri' => $_SERVER["REQUEST_URI"],
                'request_data' => $this->getRequest(),
                'response_data' => $this->getResponse(),
                'request_url' => $this->getRequestUrl(),
            ]
        ]);
        $data ['user'] = $_SESSION['m_loginname'];
        $data ['msg'] = json_decode($data['msg']);
        $txt = json_encode($data);
        $file = $filePath.$fileName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        fclose($_fo);
        file_put_contents($file, $txt . "\n", FILE_APPEND);
    }
}