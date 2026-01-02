<?php
namespace Middleware\Framework\Helpers;

use Yii;

class ResponseHelper
{
    public static function success($data = [], ?string $message = null, int $code = 200): array
    {
        Yii::$app->response->statusCode = $code;

        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [
                'timestamp' => time(),
            ],
        ];
    }

    public static function error(string $message, int $code = 400, array $errors = []): array
    {
        Yii::$app->response->statusCode = $code;

        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ];

        if (!empty($errors)) {
            $response['error']['details'] = $errors;
        }

        return $response;
    }

    public static function paginated(array $items, int $total, int $page = 1, int $perPage = 20): array
    {
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
            'meta' => [
                'timestamp' => time(),
            ],
        ];
    }

    public static function created($data = [], ?string $message = null, ?string $location = null): array
    {
        if ($location) {
            Yii::$app->response->headers->set('Location', $location);
        }
        return self::success($data, $message, 201);
    }

    public static function noContent(): array
    {
        Yii::$app->response->statusCode = 204;
        return [];
    }

    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::error($message, 404);
    }

    public static function validationError(array $errors): array
    {
        return self::error('Validation failed', 422, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized'): array
    {
        Yii::$app->response->headers->set('WWW-Authenticate', 'Bearer');
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): array
    {
        return self::error($message, 403);
    }
}