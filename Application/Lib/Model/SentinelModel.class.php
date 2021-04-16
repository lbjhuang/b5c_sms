<?php

/**
 * User: yangsu
 * Date: 19/3/1
 * Time: 10:03
 */

class SentinelModel extends Model
{
    /**
     * @param $key
     * @param $msg
     * @param null $content
     * @param string $noticed_by
     */
    public static function addAbnormal($key, $msg, $content = null, $noticed_by = 'default', $notice_type = 'ERP')
    {
        $SentinelService = new SentinelService();
        $SentinelService->addAbnormal($key, $msg, $content, $noticed_by, $notice_type);
    }

}




