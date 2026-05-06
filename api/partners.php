<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $page = max(1, inputInt($_GET, 'page'));
        $limit = max(1, min(100, inputInt($_GET, 'limit') ?: 20));
        jsonResponse(true, 'Partners loaded', [
            'partners' => $service->listPartners($page, $limit),
            'total_percentage' => $service->partnerPercentageTotal(),
            'page' => $page,
            'limit' => $limit
        ]);
    }

    if ($method === 'POST') {
        $name = sanitize((string) ($_POST['name'] ?? ''));
        $percentage = inputFloat($_POST, 'percentage');
        if ($name === '' || $percentage <= 0) {
            jsonResponse(false, 'Name and valid percentage are required', [], 422);
        }

        $newTotal = $service->partnerPercentageTotal() + $percentage;
        if (round($newTotal, 2) > 100.00) {
            jsonResponse(false, 'Total partner percentage cannot exceed 100%', [], 422);
        }

        $id = $service->addPartner($name, $percentage);
        jsonResponse(true, 'Partner added', ['id' => $id]);
    }

    if ($method === 'DELETE') {
        parse_str(file_get_contents('php://input'), $payload);
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1) {
            jsonResponse(false, 'Invalid partner id', [], 422);
        }
        $service->deletePartner($id);
        jsonResponse(true, 'Partner deleted');
    }

    jsonResponse(false, 'Method not allowed', [], 405);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to process partners', [], 500);
}
