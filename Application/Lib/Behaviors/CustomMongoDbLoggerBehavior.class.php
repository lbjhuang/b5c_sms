<?php
use Snowair\Think\Logger;
use Monolog\Handler\MongoDBHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;

class CustomMongoDbLoggerBehavior extends Behavior
{

    public function run( &$params )
    {
        try {
            if (!isLoadedMonoLogDriver()) {
                return;
            }
            $logger = Logger::getLogger();
            $handler = $logger->popHandler();
            $handler->setLevel(Logger::CRITICAL);// 重设其日志级别
            $logger->pushHandler($handler);

            $logger->pushProcessor(new WebProcessor($_SERVER));
            $logger->pushProcessor(new MemoryUsageProcessor());
            $logger->pushProcessor(new MemoryPeakUsageProcessor());

            $mongodb_config = C('MONGODB');
            $url = 'mongodb://'. $mongodb_config['HOST']. ':'. $mongodb_config['PORT'];
            $database = $mongodb_config['DATABASE'];
            $mongodb = new MongoDBHandler(new MongoDB\Client($url), $database, "b5c_log", Logger::INFO);
            $logger->pushHandler($mongodb);
        } catch (Exception $exception) {
            @SentinelModel::addAbnormal('mongodb connection failed', $exception->getMessage(), [ $exception->getMessage()],'mongodb_group');
        }
    }
}