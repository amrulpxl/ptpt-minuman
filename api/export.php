<?php
require_once __DIR__ . '/../includes/ErrorHandler.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/SessionManager.php';
require_once __DIR__ . '/../includes/ExportManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type']) || !isset($input['data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

switch ($input['type']) {
    case 'pdf':
        try {
            ExportManager::generatePDF($input['data']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
        }
        break;
        
    case 'qr':
        $qr_url = ExportManager::generateQRCode($input['data']);
        echo json_encode(['success' => true, 'qr_url' => $qr_url]);
        break;
        
    case 'whatsapp':
        $message = ExportManager::generateWhatsAppMessage($input['data']);
        echo json_encode(['success' => true, 'message' => $message]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported export type']);
}
?>
