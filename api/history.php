<?php
require_once __DIR__ . '/../includes/ErrorHandler.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/SessionManager.php';

header('Content-Type: application/json');

$sessionManager = SessionManager::getInstance();
$sessionManager->initSession();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $history = $sessionManager->getHistory();
        $itemId = $_GET['id'];
        $item = array_filter($history, function($h) use ($itemId) {
            return $h['id'] === $itemId;
        });
        
        if (!empty($item)) {
            echo json_encode(['success' => true, 'data' => reset($item)['data']]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'History item not found']);
        }
    } else {
        $history = $sessionManager->getHistory();
        echo json_encode(['success' => true, 'history' => $history]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $sessionManager->clearHistory();
    echo json_encode(['success' => true, 'message' => 'History cleared']);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
