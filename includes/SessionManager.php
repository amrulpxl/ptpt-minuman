<?php
require_once __DIR__ . '/../config/app.php';

class SessionManager {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function initSession() {
        if (!isset($_SESSION['calculation_history'])) {
            $_SESSION['calculation_history'] = [];
        }
        
        if (!isset($_SESSION['user_preferences'])) {
            $_SESSION['user_preferences'] = [
                'default_tax_rate' => DEFAULT_TAX_RATE,
                'default_service_charge' => DEFAULT_SERVICE_CHARGE,
                'default_currency' => DEFAULT_CURRENCY,
                'rounding_mode' => DEFAULT_ROUNDING_MODE,
                'language' => DEFAULT_LANGUAGE
            ];
        }
    }
    
    public function addToHistory($data) {
        $history_item = [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        
        array_unshift($_SESSION['calculation_history'], $history_item);
        
        if (count($_SESSION['calculation_history']) > MAX_HISTORY_ITEMS) {
            array_pop($_SESSION['calculation_history']);
        }
    }
    
    public function getHistory() {
        return $_SESSION['calculation_history'] ?? [];
    }
    
    public function clearHistory() {
        $_SESSION['calculation_history'] = [];
    }
    
    public function getPreferences() {
        return $_SESSION['user_preferences'] ?? [];
    }
    
    public function updatePreferences($preferences) {
        $_SESSION['user_preferences'] = array_merge($_SESSION['user_preferences'], $preferences);
    }
}
?>
