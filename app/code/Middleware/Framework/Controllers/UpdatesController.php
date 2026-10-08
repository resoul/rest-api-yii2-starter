<?php
namespace Middleware\Framework\Controllers;

use Yii;
use Middleware\Framework\Rest\Controller;

/**
 * API metadata endpoints.
 */
class UpdatesController extends Controller
{
    /**
     * Return the configured API version.
     *
     * @return array
     */
    public function actionGetVersion(): array
    {
        return [
            'version' => Yii::$app->params['apiVersion'] ?? '1.0',
        ];
    }
}
