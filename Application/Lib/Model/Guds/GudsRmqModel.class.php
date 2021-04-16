<?php

class GudsRmqModel extends Model
{
    Protected $autoCheckFields = false;
    public static $amqpConn = null;
    public $routeKey;
    public $queueName;
    public $exchangeName;
    public $contentEncoding;
    public $data = null;
    private $_setData;
    private $_error = null;
    private $_config;

    public function __construct($options = null)
    {
        parent::__construct();
    }

    /**
     * 创建连接
     * 单例模式
     *
     */
    public function amqp_connection()
    {

        if (self::$amqpConn) return self::$amqpConn;
        $connection = new AMQPConnection($this->setConnConf());
        $connection->connect();

        if (!$connection->isConnected()) {
            throw new \Exception("Cannot connect to the broker");
            //echo "Cannot connect to the broker";
        }

        return self::$amqpConn = $connection;
    }

    /**
     * 设置连接mq服务器参数
     *
     */
    public function setConnConf()
    {
        return $this->_config['CONNECT'];
    }

    public function getConfig($name = 'product_rabbit_mq_config')
    {
        $this->_config = C($name);
        $this->routeKey = $this->_config['ROUTEKEY'];
        $this->queueName = $this->_config['QUEUENAME'];
        $this->exchangeName = $this->_config['EXCHANGENAME'];
        $this->contentEncoding = $this->_config['CONTENTENCODING'];
    }

    /**
     * 创建信道
     *
     */
    public function createChannel()
    {
        $channel = new AMQPChannel(self::$amqpConn);
        return $channel;
    }

    /**
     * 创建交换机
     *
     */
    public function createExchange()
    {
        $exchange = new AMQPExchange($this->createChannel());//创建exchange
        $exchange->setName($this->exchangeName);             //创建名字
        $exchange->setType(AMQP_EX_TYPE_DIRECT);             //类型
        $exchange->setFlags(AMQP_DURABLE);                   //持久化
        return $exchange;
    }

    /**
     * 创建队列
     *
     */
    public function createQueue()
    {
        $queue = new AMQPQueue($this->createChannel());
        $queue->setName($this->queueName);                   //创建队列名，不存在则新建
        $queue->setFlags(AMQP_DURABLE);
        $queue->bind($this->exchangeName, $this->routeKey);  //队列绑定routeKey
    }

    /**
     * 设置数据
     * 对json中的json数据进行转义，方便java读取
     * 4-19停止转义，满足规范
     */
    public function setData($data)
    {
        $this->_setData = $data;
        return $this->data = json_encode($data);
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * 释放链接
     *
     */
    public function disConnect()
    {
        self::$amqpConn->disconnect();
    }

    /**
     * 提交
     *
     */
    public function submit($close = true)
    {
        try {
            // 创建连接
            $this->amqp_connection();
            // 创建交换机
            $exchange = $this->createExchange();
            // 创建信道
            $channel = $this->createChannel();
            // 开始事物
            $channel->startTransaction();
            // 创建队列
            $this->createQueue();
            // 消息推送
            $isok = $exchange->publish($this->getData(), $this->routeKey, AMQP_NOPARAM, ['content_encoding' => $this->contentEncoding]);
            // 提交事物
            $channel->commitTransaction();
            if ($close) $this->disConnect();
            $this->_catchMe($isok);
            return $isok;
        } catch (\Exception $e) {
            $this->_setError($e->getMessage());
            $this->_catchMe();
            return false;
        }
    }

    private function _setError($err)
    {
        $this->_error = $err;
    }

    public function getErr()
    {
        return $this->_error;
    }

    /**
     * 记录接口日志
     */
    private function _catchMe($isok = false)
    {
        $isok = $isok ? "SUCCESS" : "FAIL";
        $logFilePath = RUNTIME_PATH . 'Logs/';
        // 日志对象
        $log = new \stdClass();
        $trace = debug_backtrace(0);
        $logName = date('Y-m-d') . @$trace[2]['function'] . '.log';
        $txt = "\n------------------------------------------------------------------";
        $txt .= "\n@@@时间：" . $log->datetime = date('Y-m-d H:i:s');
        $txt .= "\n@@@来源：" . $log->ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $txt .= "\n@@@方法：" . $log->method = $_SERVER["REQUEST_METHOD"];
        $txt .= "\n@@@目标：" . $log->url = $this->setConnConf()['host'];//$_SERVER["REQUEST_URI"];
        $txt .= "\n@@@调用：" . $log->callback = sprintf('%s::%s (line:%s)', @$trace[2]['class'], @$trace[2]['function'], @$trace[1]['line']);
        $txt .= "\n@@@成功：" . $log->apiIsok = 'SUCCESS';
        $txt .= "\n@@@页面变量(GET)：\n" . $log->varGet = print_r($_GET, true);
        $txt .= "\n@@@页面变量(POST)：\n" . $log->varPost = print_r($_POST, true);
        $txt .= "\n@@@MQ地址" . $this->setConnConf()['host'] . "请求(Request)数据：\n" . var_export($this->_setData, true);
        $txt .= "\n@@@queueName:" . $this->queueName;
        $txt .= "\n@@@routeKey:" . $this->routeKey;
        $txt .= "\n@@@exchangeName:" . $this->exchangeName;
        $txt .= "\n@@@IsSuccess:" . $isok;
        $txt .= "\n@@@Error:" . $this->getErr();
        $txt .= "\n------------------------------------------------------------------";
        // 保存到日志文件
        $file = $logFilePath . $logName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        $old = fread($_fo, 1024 * 1024);
        fclose($_fo);
        file_put_contents($file, $txt . $old);
    }
}