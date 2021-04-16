<?php

/**
 * 所有的操作，通过实现MeBYModel::$oci
 * eg:
 *   查询-> MeBYModel::$oci->query($sql)
 *   关闭-> MeBYModel::$oci->close()
 *   事物-> MeBYModel::$oci->startTrans()
 *   提交-> MeBYModel::$oci->commit()
 *   回滚-> MeBYModel::$oci->rollback()
 * 注意事项：
 *   表名需要跟模型名：ECOLOGY
 */
class MeBYModel
{
    protected $table_prefix = 'ECOLOGY';
    
    public $config = [
        'database' => "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 10.8.6.21)(PORT = 1521)))(CONNECT_DATA=(SID=ECOLOGY)))",
        'username' => 'ecology',
        'password' => 'ecology'
    ];
    
    public static $oci;
    
    public function __construct()
    {
        if (is_resource(static::$oci)) return static::$oci;
        $oci = new DbOracle($this->config);
        
        return static::$oci = $oci;
    }

    /**
     * test query
     * 默认返回HR所有信息
     */
    public function testQuery($sql = 'SELECT * FROM ECOLOGY.HRMRESOURCE')
    {
        // 获取所有hr信息
        $ret = static::$oci->query($sql);
        
        return $ret;
    }
    
    public function query($sql)
    {
        return static::$oci->query($sql);
    }
}