<?php
/**
 * view-source: HOSTS/test/getCode.php
 */
header('Content-Type: text/html; charset=utf-8');
//$dir = __DIR__ . '\..\Application\Lib\Action\Warehouse';
$dir = '/Users/amy/Code/insight-backend/src/main/java/com/b5m/dashboard/controller/erp/gpDashboard';
set_time_limit(0);
$files = getFilePath($dir);
//打印所有文件名
ob_start();
if ($files) {
    foreach ($files as $file) {
        echo file_get_contents($file) . PHP_EOL;
        flush();
    }
} else {
    echo "don't have file data";
}
$files = [];
function getFilePath($dir, $i = 0)
{
    global $files;
    $handler = opendir($dir);
    while (($filename = readdir($handler)) !== false && $i <= 20) {
        $file_path = $dir . '/' . $filename;
        if ($filename !== "." && $filename !== "..") {
            if (is_file($file_path)) {
                $i++;
                $files[] = $file_path;
            }
            if (is_dir($file_path)) {
//                getFilePath($file_path, $i);
            }
        }
    }
    closedir($handler);
    return $files;
}