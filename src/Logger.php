<?php

namespace Idaratech\Integrations;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class Logger
{
    /**
     * Resolve the underlying logger instance.
     */
    protected static function getLogger(): LoggerInterface
    {
        $channel = config('integrations.logging.channel');

        try {
            if ($channel && method_exists(Log::class, 'channel')) {
                return Log::channel($channel);
            }

            return Log::getLogger();
        } catch (\Throwable $e) {
            // Fallback: use a default single file or errorlog
            return Log::build([
                'driver' => 'errorlog',
            ]);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, self::safeContext($context));
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, self::safeContext($context));
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, self::safeContext($context));
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, self::safeContext($context));
    }

    protected static function safeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'access_token', 'secret', 'authorization', 'api_key'];

        $filtered = [];
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $filtered[$key] = '******';
            } elseif (is_array($value)) {
                $filtered[$key] = self::safeContext($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
