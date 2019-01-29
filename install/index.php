<?php

/**
 * This is a install script
 * copy from gebi.
 */
try {
    if (!is_readable('sastfinance.lite.sql')) {
        exit('无读文件权限');
    }
    $_sql = file_get_contents('sastfinance.lite.sql');
    $_arr = explode(';', $_sql);
    $CONFIG = require '../protected/config.php';
    $mysql_config = $CONFIG['mysql'];
    $dsn = 'mysql:host='.$mysql_config['MYSQL_HOST'].';charset='.$mysql_config['MYSQL_CHARSET'].';port='.$mysql_config['MYSQL_PORT'];
    $db = new PDO($dsn, $mysql_config['MYSQL_USER'], $mysql_config['MYSQL_PASS']);

    foreach ($_arr as $_value) {
        $sql = str_replace('<{DB_NAME}>', $mysql_config['MYSQL_DB'], $_value);
        $db->query($sql.';');
    }
} catch (Exception $e) {
    exit($e->getMessage());
}
exit('数据库初始化完毕');
