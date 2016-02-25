<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = \yii\helpers\ArrayHelper::merge(
    ['id' => 'unit'],
    ['basePath' => __DIR__],
    [
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'pgsql:host=localhost;dbname=yii2_sitemap',
                'username' => 'postgres',
                'password' => '',
                'charset' => 'utf8',
            ],
        ],
    ]
);

new \yii\web\Application($config);
