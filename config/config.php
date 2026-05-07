<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

const APP_ENV = 'local';
const APP_NAME = 'Zouetech Finance';
const SESSION_NAME = 'zouetech_admin_session';

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'zouetech_finance';
const DB_USER = 'root';
const DB_PASS = '';

const LOG_FILE = __DIR__ . '/../logs/app.log';

if (!function_exists('app_url')) {
    function app_url(string $path = '/'): string
    {
        $normalizedPath = '/' . ltrim($path, '/');
        return $normalizedPath;
    }
}
