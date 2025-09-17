<?php
require_once __DIR__ . '/../includes/ErrorHandler.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/SessionManager.php';

header('Content-Type: application/json');

$sessionManager = SessionManager::getInstance();
$sessionManager->initSession();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $preferences = $sessionManager->getPreferences();
    echo json_encode(['success' => true, 'preferences' => $preferences]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $sessionManager->updatePreferences($input);
    echo json_encode(['success' => true, 'message' => 'Settings saved']);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
