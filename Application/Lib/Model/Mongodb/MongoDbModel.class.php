<?php
/**
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/10/06
 * Time: 22:38
 */

class MongoDbModel
{
    static $instance;

    public static function client()
    {
        try {
            if (!self::$instance) {
                $mongodb_config = C('MONGODB');
                $url = 'mongodb://' . $mongodb_config['HOST'] . ':' . $mongodb_config['PORT'];
                self::$instance = (new MongoDB\Client($url))->selectDatabase($mongodb_config['DATABASE']);
            }
            return new static();

        } catch (Exception $exception) {
            @SentinelModel::addAbnormal('mongodb instance failed', $exception->getMessage(), [ $exception->getMessage()],'mongodb_group');
        }
    }

    public function insertOne ($collection_name, $data)
    {
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return $collection->insertOne($data);
    }

    public function find ($collection_name, $where = [], $option = ['limit'=>9999, 'skip'=>0])
    {
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return json_decode(json_encode($collection->find($where, $option)->toArray()), true);
    }
    public function count($collection_name, $where = [])
    {
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return $collection->count($where);
    }
    public function findOne($collection_name, $where = [], $sort = [])
    {
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return json_decode(json_encode($collection->findOne($where, ['sort' => $sort])), true);
    }
    public function insertAll($collection_name, $data){
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return $collection->insertMany($data);
    }
    public function deleteMany($collection_name, $where)
    {
        if (empty($collection_name)) {
            return false;
        }
        $collection = self::$instance->selectCollection($collection_name);
        return $collection->deleteMany($where);
    }
}