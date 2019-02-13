<?php

date_default_timezone_set('PRC');
require 'model/CONFIG.php';
$config = array(
    'rewrite' => array(
        'admin/<uid>/usermanage' => 'admin/usermanage',
        'admin/<a>' => 'admin/<a>',
        'admin' => 'admin/index',
        'ajax/<a>' => 'ajax/<a>',
        'reimbursement/edit/<rid>' => 'reimbursement/edit',
        'reimbursement/view/<rid>' => 'reimbursement/view',
        'reimbursement/<a>' => 'reimbursement/<a>',
        'reimbursement' => 'reimbursement/statisticstotality',
        'account/<a>' => 'account/<a>',
        'account' => 'account/index',
        '<a>' => 'main/<a>',
        '/' => 'main/index',
    ),
);

$domain = array(
    '127.0.0.1' => array( // 调试配置
        'debug' => 1,
        'maintain' => 0,
        'mysql' => array(
            'MYSQL_HOST' => CONFIG::GET('DEBUG_MYSQL_HOST'),
            'MYSQL_PORT' => CONFIG::GET('DEBUG_MYSQL_PORT'),
            'MYSQL_USER' => CONFIG::GET('DEBUG_MYSQL_USER'),
            'MYSQL_DB' => CONFIG::GET('DEBUG_MYSQL_DB'),
            'MYSQL_PASS' => CONFIG::GET('DEBUG_MYSQL_PASS'),
            'MYSQL_CHARSET' => CONFIG::GET('DEBUG_MYSQL_CHARSET'),
        ),
    ),

    'finance.winter.mundb.xyz' => array( // 生产配置
        'debug' => 0,
        'maintain' => 0,
        'mysql' => array(
            'MYSQL_HOST' => CONFIG::GET('PRO_MYSQL_HOST'),
            'MYSQL_PORT' => CONFIG::GET('PRO_MYSQL_PORT'),
            'MYSQL_USER' => CONFIG::GET('PRO_MYSQL_USER'),
            'MYSQL_DB' => CONFIG::GET('PRO_MYSQL_DB'),
            'MYSQL_PASS' => CONFIG::GET('PRO_MYSQL_PASS'),
            'MYSQL_CHARSET' => CONFIG::GET('PRO_MYSQL_CHARSET'),
        ),
    ),

    'cwxt' => array( // 调试配置
        'debug' => 1,
        'maintain' => 0,
        'mysql' => array(
            'MYSQL_HOST' => CONFIG::GET('DEBUG_MYSQL_HOST'),
            'MYSQL_PORT' => CONFIG::GET('DEBUG_MYSQL_PORT'),
            'MYSQL_USER' => CONFIG::GET('DEBUG_MYSQL_USER'),
            'MYSQL_DB' => CONFIG::GET('DEBUG_MYSQL_DB'),
            'MYSQL_PASS' => CONFIG::GET('DEBUG_MYSQL_PASS'),
            'MYSQL_CHARSET' => CONFIG::GET('DEBUG_MYSQL_CHARSET'),
        ),
    ),
);
// 为了避免开始使用时会不正确配置域名导致程序错误，加入判断
if (empty($domain[$_SERVER['HTTP_HOST']])) {
    die('配置域名不正确，请确认'.$_SERVER['HTTP_HOST'].'的配置是否存在！');
}

return $domain[$_SERVER['HTTP_HOST']] + $config;
