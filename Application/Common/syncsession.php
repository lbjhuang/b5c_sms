<?php
/**
 * User: yangsu
 * Date: 18/1/10
 * Time: 12:12
 */
register_shutdown_function(function(){
    $SyncSessionPush = new SyncSessionPushBehavior();
    $SyncSessionPush->run();
});