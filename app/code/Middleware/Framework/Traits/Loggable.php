<?php
namespace Middleware\Framework\Traits;

use Yii;
use Psr\Log\LogLevel;
use yii\log\Logger;

/**
 * Loggable Trait
 *
 * Provides convenient logging methods for any class
 *
 * Usage:
 * ```php
 * class MyService
 * {
 *     use Loggable;
 *
 *     public function doSomething()
 *     {
 *         $this->logInfo('Starting process');
 *         try {
 *             // ... code
 *             $this->logInfo('Process completed', ['result' => $result]);
 *         } catch (\Exception $e) {
 *             $this->logException($e);
 *         }
 *     }
 * }
 * ```
 */
trait Loggable
{
    /**
     * @var string|null Custom log category
     */
    protected ?string $logCategory = null;

    /**
     * @var bool Whether to include context in logs
     */
    protected bool $logContext = true;

    /**
     * @var array Additional context to include in all logs
     */
    protected array $additionalContext = [];

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Log notice message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logNotice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logCritical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Log alert message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logAlert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Log emergency message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logEmergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Log exception
     *
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    protected function logException(\Throwable $exception, array $context = []): void
    {
        $context = array_merge($context, [
            'exception_class' => get_class($exception),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($exception->getPrevious()) {
            $context['previous_exception'] = get_class($exception->getPrevious());
        }

        $this->logError($exception->getMessage(), $context);
    }

    /**
     * Main log method
     *
     * @param string $level PSR-3 log level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $yiiLevel = $this->mapLogLevel($level);
        $category = $this->getLogCategory();

        // Merge contexts
        if ($this->logContext) {
            $context = array_merge($this->additionalContext, $context, $this->getDefaultContext());
        }

        // Log message
        Yii::getLogger()->log($message, $yiiLevel, $category);

        // Log context if not empty
        if (!empty($context)) {
            $contextString = $this->formatContext($context);
            Yii::getLogger()->log($contextString, $yiiLevel, $category);
        }
    }

    /**
     * Map PSR-3 log level to Yii2 log level
     *
     * @param string $level
     * @return int
     */
    protected function mapLogLevel(string $level): int
    {
        return match($level) {
            LogLevel::EMERGENCY => Logger::LEVEL_ERROR,
            LogLevel::ALERT => Logger::LEVEL_ERROR,
            LogLevel::CRITICAL => Logger::LEVEL_ERROR,
            LogLevel::ERROR => Logger::LEVEL_ERROR,
            LogLevel::WARNING => Logger::LEVEL_WARNING,
            LogLevel::NOTICE => Logger::LEVEL_INFO,
            LogLevel::INFO => Logger::LEVEL_INFO,
            LogLevel::DEBUG => Logger::LEVEL_TRACE,
            default => Logger::LEVEL_INFO,
        };
    }

    /**
     * Get log category
     *
     * @return string
     */
    protected function getLogCategory(): string
    {
        if ($this->logCategory !== null) {
            return $this->logCategory;
        }

        return static::class;
    }

    /**
     * Get default context
     *
     * @return array
     */
    protected function getDefaultContext(): array
    {
        $context = [
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Add request context if available
        if (Yii::$app->has('request') && !Yii::$app->request->isConsoleRequest) {
            $request = Yii::$app->request;
            $context['request'] = [
                'method' => $request->method,
                'url' => $request->absoluteUrl,
                'ip' => $request->userIP,
                'user_agent' => $request->userAgent,
            ];

            // Add user context if available
            if (Yii::$app->has('user') && !Yii::$app->user->isGuest) {
                $context['user_id'] = Yii::$app->user->id;
            }
        }

        // Add memory usage
        $context['memory_usage'] = $this->formatBytes(memory_get_usage(true));
        $context['memory_peak'] = $this->formatBytes(memory_get_peak_usage(true));

        return $context;
    }

    /**
     * Format context for logging
     *
     * @param array $context
     * @return string
     */
    protected function formatContext(array $context): string
    {
        try {
            return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            return print_r($context, true);
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Set custom log category
     *
     * @param string $category
     * @return static
     */
    protected function setLogCategory(string $category): static
    {
        $this->logCategory = $category;
        return $this;
    }

    /**
     * Add additional context for all logs
     *
     * @param array $context
     * @return static
     */
    protected function addLogContext(array $context): static
    {
        $this->additionalContext = array_merge($this->additionalContext, $context);
        return $this;
    }

    /**
     * Enable/disable context logging
     *
     * @param bool $enabled
     * @return static
     */
    protected function setLogContext(bool $enabled): static
    {
        $this->logContext = $enabled;
        return $this;
    }

    /**
     * Log query execution
     *
     * @param string $sql
     * @param float $duration
     * @param array $params
     * @return void
     */
    protected function logQuery(string $sql, float $duration, array $params = []): void
    {
        $this->logInfo('Database query executed', [
            'sql' => $sql,
            'duration' => round($duration, 4) . 's',
            'params' => $params,
        ]);
    }

    /**
     * Log API request
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param int|null $statusCode
     * @param float|null $duration
     * @return void
     */
    protected function logApiRequest(
        string $method,
        string $url,
        array $data = [],
        ?int $statusCode = null,
        ?float $duration = null
    ): void {
        $context = [
            'method' => $method,
            'url' => $url,
        ];

        if (!empty($data)) {
            $context['data'] = $data;
        }

        if ($statusCode !== null) {
            $context['status_code'] = $statusCode;
        }

        if ($duration !== null) {
            $context['duration'] = round($duration, 4) . 's';
        }

        $level = ($statusCode >= 400) ? LogLevel::ERROR : LogLevel::INFO;
        $this->log($level, "API request: {$method} {$url}", $context);
    }
}