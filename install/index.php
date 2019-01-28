<?php

/**
 * This is a install script
 * copy from gebi.
 */
if (file_exists('.installed')) {
    header('Location: ../'); //Successfully Installed
}

try {
    if (!is_readable('sastfinance.lite.sql')) {
        exit('无读文件权限');
    }
    $_sql = file_get_contents('sastfinance.lite.sql');
    $_arr = explode(';', $_sql);
    $CONFIG = require '../protected/config.php';

    $dsn = 'mysql:host='.$CONFIG['mysql']['MYSQL_HOST'].';charset='.$CONFIG['mysql']['MYSQL_CHARSET'].';port='.$CONFIG['mysql']['MYSQL_PORT'];
    $db = new PDO($dsn, $CONFIG['mysql']['MYSQL_USER'], $CONFIG['mysql']['MYSQL_PASS']);
    foreach ($_arr as $_value) {
        $sql = str_replace('<{DB_NAME}>', $CONFIG['mysql']['MYSQL_DB'], $_value);
        $db->query($sql.';');
    }
} catch (Exception $e) {
    exit('数据库初始化错误');
}

if (!is_writeable('../')) {
    exit('无写文件权限');
}
file_put_contents('.installed', "don't dare me.");
exit('数据库初始化完毕');
