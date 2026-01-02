<?php
namespace Middleware\Framework\Rest;

use Middleware\Framework\Filters\RateLimiter;
use Middleware\Framework\Filters\RequestValidator;
use Yii;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use yii\rest\Controller as BaseController;

/**
 * Base REST Controller with common behaviors
 */
class Controller extends BaseController
{
    public bool $enableAuth = false;
    public array $optionalAuth = [];

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => $this->getAllowedOrigins(),
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => ['X-Pagination-Total-Count', 'X-Pagination-Page-Count'],
            ],
        ];

        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::class,
            'maxRequests' => 100,
            'window' => 60,
        ];

        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['requestValidator'] = [
            'class' => RequestValidator::class,
        ];

        if ($this->enableAuth) {
            $behaviors['authenticator'] = [
                'class' => HttpBearerAuth::class,
                'optional' => $this->optionalAuth,
            ];
        }

        $behaviors['verbFilter'] = [
            'class' => VerbFilter::class,
            'actions' => $this->verbs(),
        ];

        return $behaviors;
    }

    protected function getAllowedOrigins(): array
    {
        if (YII_ENV_PROD) {
            return Yii::$app->params['allowedOrigins'] ?? ['*'];
        }
        return ['*'];
    }

    protected function verbs(): array
    {
        return [];
    }

    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        if (is_array($result) && !isset($result['meta'])) {
            $result = [
                'data' => $result,
                'meta' => [
                    'timestamp' => time(),
                    'version' => Yii::$app->params['apiVersion'] ?? '1.0',
                ],
            ];
        }

        return $result;
    }

    protected function getQueryParams(): array
    {
        $request = Yii::$app->request;
        return [
            'page' => (int) $request->get('page', 1),
            'per_page' => min((int) $request->get('per_page', 20), 100),
            'sort' => $request->get('sort', '-id'),
            'filter' => $request->get('filter', []),
        ];
    }
}
