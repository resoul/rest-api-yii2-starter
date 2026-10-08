<?php
/**
 * @var $env array
 */

use Middleware\Framework\Rest\ErrorHandler;
use yii\log\FileTarget;
use yii\web\JsonParser;
use yii\web\Response;
use yii\web\UrlManager;

$env = require(ENV_PATH);
$config = require(COMMON_CONFIG_PATH);

$config['controllerNamespace'] = 'Middleware\\Framework\\Controllers';

$config['components']['request'] = [
    'baseUrl' => '',
    'cookieValidationKey' => $env['cookie.validation.key'],
    'parsers' => [
        'application/json' => JsonParser::class,
    ],
    'enableCsrfValidation' => false,
];

$config['components']['response'] = [
    'class' => Response::class,
    'format' => Response::FORMAT_JSON,
    'charset' => 'UTF-8',
    'on beforeSend' => function ($event) {
        /**@var $response Response */
        $response = $event->sender;

        if ($response->data !== null && Yii::$app->request->get('suppress_response_code')) {
            $response->data = [
                'success' => $response->isSuccessful,
                'data' => $response->data
            ];
            $response->statusCode = 200;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        if (YII_ENV_PROD) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
];

$config['components']['urlManager'] = [
    'class' => UrlManager::class,
    'enablePrettyUrl' => true,
    'enableStrictParsing' => true,
    'showScriptName' => false,
    'rules' => [
        ['class' => 'yii\rest\UrlRule', 'controller' => ['v1/updates']],
        'GET /' => 'updates/get-version',
        'GET /health' => 'health/check',
    ]
];

$config['components']['errorHandler'] = [
    'class' => ErrorHandler::class,
];

$config['components']['log'] = [
    'traceLevel' => YII_DEBUG ? 3 : 0,
    'targets' => [
        [
            'class' => FileTarget::class,
            'levels' => ['error', 'warning'],
            'logFile' => RUNTIME_PATH . '/logs/api.log',
            'maxFileSize' => 10240, // 10MB
            'maxLogFiles' => 5,
            'logVars' => ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION'],
        ],
    ]
];

return $config;
