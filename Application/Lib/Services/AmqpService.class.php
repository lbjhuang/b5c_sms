<?php

/**
 * User: mark.zhong
 * Date: 2020/07/07
 * Time: 14:16
 *
 * RabbitMQ 连接服务
 *
 */

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpService
{
    private $connection;

    private $max = 50;

    public function __construct($default_config = 'default')
    {
        $config   = C('amqp_config')[$default_config];
        $host     = $config['host'];
        $port     = $config['port'];
        $user     = $config['user'];
        $password = $config['password'];
        $vhost    = $config['vhost'];
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
//        if (!$this->connection){
//            @SentinelModel::addAbnormal('amqp connection failed', [], 'kyriba_notice');
//        }
    }

    /**
     * amqp生产者
     * @param $exchange_name 交换机名
     * @param $queue_name 队列名
     * @param $route_key_name 路由名
     * @param $data 要生成的数据
     * @return bool
     */
    public function publisher($exchange_name, $queue_name, $route_key_name, $data)
    {
        try {
            if (empty($data)) {
                throw new \Exception('生产消息不能为空');
            }
            if (!$this->connection) {
                throw new \Exception('MQ连接失败');
            }
            $channel = $this->connection->channel();
            $channel->queue_declare($queue_name, false, true, false, false);
            $channel->exchange_declare($exchange_name, 'direct', false, true, false);
            $channel->queue_bind($queue_name, $exchange_name); // 队列和交换器绑定
            foreach ($data as $item) {
                $message_body = json_encode($item);
                $message = new AMQPMessage($message_body, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
                $channel->basic_publish($message, $exchange_name, $route_key_name); // 推送消息
                Logs("发送消息内容 ：". $message_body, __FUNCTION__, __CLASS__);
            }
            $channel->close();
            $this->connection->close();
            return true;
        } catch (\Exception $exception) {
            @SentinelModel::addAbnormal('amqp publisher failed', '', [$exception->getMessage()], 'kyriba_notice');
            $channel->close();
            $this->connection->close();
            return false;
        }
    }

    /***
     * 消费者
     */
    public function consumer($exchange_name, $queue_name, $route_key_name)
    {
        try {
            if (empty($exchange_name) || empty($queue_name) || empty($route_key_name)) {
                throw new \Exception("交换机名 ：" . $exchange_name . " 队列名 ：" . $queue_name . "路由key ：" . $route_key_name . " 不能为空");
            }
            if (!$this->connection) {
                throw new \Exception('MQ连接失败');
            }
            //在连接内创建一个通道
            $channel = $this->connection->channel();
            $channel->queue_bind($queue_name, $exchange_name, $route_key_name);
            $this->queueConsumer($channel,$queue_name);

            $channel->close();
            $this->connection->close();
//            exit;
        } catch (\Exception $exception) {
            @SentinelModel::addAbnormal('amqp consumer failed', $exception->getMessage(), [$exception->getMessage()], 'kyriba_notice');
            $channel->close();
            $this->connection->close();
            return false;
        }
    }

    private function queueConsumer($channel,$queue_name)
    {
        $service = new KyribaService();
        for ($i = 0; $i < $this->max; $i++) {
            $message = $channel->basic_get($queue_name); //取出消息
            if (!empty($message)) {
                Logs("kyriba消费者信息 ：". $message->body, __FUNCTION__, __CLASS__);
                $data = json_decode($message->body, true);
                if ($service->receiveHandle($data)) {
                }
                $channel->basic_ack($message->delivery_info['delivery_tag']); //set ack ok
            } else {
                break;
            }
        }
        return true;
    }
}
