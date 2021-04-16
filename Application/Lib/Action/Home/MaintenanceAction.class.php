<?php

/**
 * Alone API class
 * 2017
 * author: huaxin
 */
class MaintenanceAction extends Action
{
    public function on()
    {
        $states = @file_put_contents('/opt/logs/logstash/erp//maintenance.ini', 'true');
        if ($states) {
            echo 'on,Maintenance mode is on';
        } else {
            echo 'error';
        }
    }

    public function off()
    {
        $states = @file_put_contents('/opt/logs/logstash/erp//maintenance.ini', 'false');
        if ($states) {
            echo 'off,Maintenance mode is off';
        } else {
            echo 'error';
        }
    }

}
