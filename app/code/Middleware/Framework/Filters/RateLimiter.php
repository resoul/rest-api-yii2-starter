<?php
namespace Middleware\Framework\Filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

class RateLimiter extends ActionFilter
{
    public int $maxRequests = 60;
    public int $window = 60;
    public string $cacheKey = 'rate_limit';

    public function beforeAction($action): bool
    {
        $identifier = $this->getIdentifier();
        $key = "{$this->cacheKey}:{$identifier}";

        $cache = Yii::$app->cache;
        $requests = (int) $cache->get($key) ?: 0;

        if ($requests >= $this->maxRequests) {
            throw new TooManyRequestsHttpException('Rate limit exceeded');
        }

        $cache->set($key, $requests + 1, $this->window);

        Yii::$app->response->headers->set('X-RateLimit-Limit', $this->maxRequests);
        Yii::$app->response->headers->set('X-RateLimit-Remaining', max(0, $this->maxRequests - $requests - 1));

        return parent::beforeAction($action);
    }

    protected function getIdentifier(): string
    {
        $user = Yii::$app->user;
        if (!$user->isGuest) {
            return "user:{$user->id}";
        }
        return "ip:" . Yii::$app->request->userIP;
    }
}