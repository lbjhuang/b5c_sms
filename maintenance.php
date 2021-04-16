<?php
$maintenance_mode = file_get_contents('/opt/logs/logstash/erp//maintenance.ini');

if ('false' == $maintenance_mode) {
    header("Location: http://{$_SERVER ['HTTP_HOST']}/index.php");
    die();
}
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>维护模式</title>
</head>
<body>
<div>
    <p>网站进行更新中，请稍等</p>
</div>
</body>
</html>