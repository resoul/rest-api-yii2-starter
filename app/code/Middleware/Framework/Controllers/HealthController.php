<?php
namespace Middleware\Framework\Controllers;

use Middleware\Framework\Rest\Controller;

/**
 * Basic application health endpoint.
 */
class HealthController extends Controller
{
    /**
     * Report that the HTTP application is responding.
     *
     * @return array
     */
    public function actionCheck(): array
    {
        return [
            'status' => 'ok',
        ];
    }
}
