<?php

/**
 * User: yangsu
 * Date: Tue, 14 Aug 2018 04:52:45 +0000.
 */

namespace Application\Lib\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ORM
 */
class ORM extends Model
{
    protected $capsule;

    public function __construct()
    {
        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver' => C('DB_TYPE'),
            'host' => C('DB_HOST'),
            'database' => C('DB_NAME'),
            'username' => C('DB_USER'),
            'password' => C('DB_PWD'),
            'charset' => C('DB_CHARSET'),
            'collation' => C('DB_COLLATION'),
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        parent::__construct();
        $this->capsule = $capsule->getConnection();
        $this->capsule->enableQueryLog();

    }

    public function __destruct()
    {
        $queryLog = $this->capsule->getQueryLog();
        if ($queryLog) {
            $json_encode = json_encode($queryLog);
            Logs($json_encode,
                'Model ' . $this->getTable(),
                'ORM');
        }
    }
}
