<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'TAKP-6PqJIrhgth_FzQon47U65TSyryP',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser',
            ],
        ],
        'response' => [
            // ...
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    // ...
                ],
            ],
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                switch ($response->statusCode) {
                    case 401:
                        return $response->data = [
                            'data' => [
                                'success' => false,
                                'code' => 401,
                                'message' => 'Login failed',
                            ]
                        ];
                        break;
                    case 403:
                        return $response->data = [
                            'data' => [
                                'success' => false,
                                'code' => 403,
                                'message' => 'Forbidden for you',
                            ]
                        ];
                        break;
                }
            },
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'OPTIONS api/<controller>/<action>' => '<controller>/options',
                'OPTIONS api/<controller>/<action>/<detail>' => '<controller>/options',
                'OPTIONS api/<action>' => 'user/options',    

                'GET api/search' => 'pet/search', // Быстрый поиск по объявлениям
                'GET api/pets' => 'pet/last-find', // Карточки найденных животных
                'POST api/subscription' => 'user/subscription', // Подписка на новости
                'POST api/register' => 'user/register', // Регистрация
                'POST api/login' => 'user/login', // аутентификация
                'GET api/districts' => 'district/district', // Список районов
                'GET api/kinds' => 'kind/kind', // Список видов животных
                'GET api/users' => 'user/info', // Информация о пользователе
                [
                    'pluralize' => true,
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'pet',
                    'prefix' => 'api',
                    'extraPatterns' => [
                        'GET slider' => 'slider', // Слайдер с объявлениями с животными, у которых были найдены хозяева,
                        'GET <pet_id>' => 'show', // Страница с карточкой одного животного,
                        'POST new' => 'new', // Страница добавления нового объявления,
                        'PATCH <order_id>' => 'update-order', // Редактирование пользователем объявления,
                    ],
                ],
                [
                    'pluralize' => true,
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'user',
                    'prefix' => 'api',
                    'extraPatterns' => [
                        'PATCH phone' => 'change-phone', // Изменение номера телефона
                        'PATCH email' => 'change-email', // Изменение адреса электронной почты
                        'GET orders' => 'order', // Изменение адреса электронной почты
                        'DELETE orders/<order_id>' => 'delete-order' // Удаление пользователем объявления
                    ],
                ],
            ],
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
