<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function jsonResponse(bool $success, string $message, array $data = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sanitize(string $value): string
{
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function inputFloat(array $source, string $key): float
{
    return isset($source[$key]) ? (float) $source[$key] : 0.0;
}

function inputInt(array $source, string $key): int
{
    return isset($source[$key]) ? (int) $source[$key] : 0;
}

function normalizeFilterRange(string $range, array $allowed = ['all', 'daily', 'weekly', 'monthly', 'yearly']): string
{
    $normalized = strtolower(trim($range));
    return in_array($normalized, $allowed, true) ? $normalized : 'all';
}

function parseFilterRange(string $range, ?string $from, ?string $to): array
{
    $range = strtolower(trim($range));
    $today = new DateTimeImmutable('today');
    $start = null;
    $end = null;

    switch ($range) {
        case 'all':
            $start = null;
            $end = null;
            break;
        case 'daily':
            $start = $today->format('Y-m-d 00:00:00');
            $end = $today->format('Y-m-d 23:59:59');
            break;
        case 'weekly':
            $start = $today->modify('monday this week')->format('Y-m-d 00:00:00');
            $end = $today->modify('sunday this week')->format('Y-m-d 23:59:59');
            break;
        case 'monthly':
            $start = $today->modify('first day of this month')->format('Y-m-d 00:00:00');
            $end = $today->modify('last day of this month')->format('Y-m-d 23:59:59');
            break;
        case 'yearly':
            $start = $today->modify('first day of january this year')->format('Y-m-d 00:00:00');
            $end = $today->modify('last day of december this year')->format('Y-m-d 23:59:59');
            break;
        case 'custom':
            if ($from && $to) {
                $start = (new DateTimeImmutable($from))->format('Y-m-d 00:00:00');
                $end = (new DateTimeImmutable($to))->format('Y-m-d 23:59:59');
            }
            break;
    }

    return [$start, $end];
}

function logError(Throwable $e): void
{
    $line = sprintf(
        "[%s] %s in %s:%d%s",
        date('c'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        PHP_EOL
    );
    error_log($line, 3, LOG_FILE);
}
