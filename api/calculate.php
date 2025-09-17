<?php
require_once __DIR__ . '/../includes/ErrorHandler.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/SessionManager.php';
require_once __DIR__ . '/../includes/Calculator.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$inputSize = strlen(file_get_contents('php://input'));
if ($inputSize > 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Request too large']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$errors = Calculator::validateInput($input);

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$sessionManager = SessionManager::getInstance();
$sessionManager->initSession();

try {
    $result = Calculator::calculateExpense(
        $input['items'],
        $input['tax_rate'],
        $input['service_charge'],
        $input['discount_rate'],
        $input['tips_rate'],
        $input['number_of_people'],
        $input['rounding_mode']
    );

    $sessionManager->addToHistory($result);

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    error_log('Calculate API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
