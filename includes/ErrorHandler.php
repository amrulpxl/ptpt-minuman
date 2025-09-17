<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

function handleError($errno, $errstr, $errfile, $errline) {
    $error_message = "Error [$errno]: $errstr in $errfile on line $errline";
    error_log($error_message);
    
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => $error_message
        ]);
    }
    
    return true;
}

function handleException($exception) {
    $error_message = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($error_message);
    
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => $error_message
        ]);
    }
}

set_error_handler('handleError');
set_exception_handler('handleException');

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>
