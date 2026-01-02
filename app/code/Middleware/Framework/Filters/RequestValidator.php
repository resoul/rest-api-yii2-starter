<?php
namespace Middleware\Framework\Filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

class RequestValidator extends ActionFilter
{
    public int $maxBodySize = 10485760; // 10MB
    public array $allowedContentTypes = ['application/json'];

    public function beforeAction($action): bool
    {
        $request = Yii::$app->request;

        $contentType = $request->contentType;
        if ($contentType && !in_array($contentType, $this->allowedContentTypes)) {
            throw new BadRequestHttpException('Unsupported content type');
        }

        $contentLength = $request->headers->get('Content-Length', 0);
        if ($contentLength > $this->maxBodySize) {
            throw new BadRequestHttpException('Request body too large');
        }

        if ($contentType === 'application/json') {
            $body = $request->rawBody;
            if ($body && json_decode($body) === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException('Invalid JSON: ' . json_last_error_msg());
            }
        }

        return parent::beforeAction($action);
    }
}