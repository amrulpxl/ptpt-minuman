<?php
define('APP_NAME', 'Kalkulator Patungan Minuman');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'Amrul');

define('DEFAULT_TAX_RATE', 10);
define('DEFAULT_SERVICE_CHARGE', 5);
define('DEFAULT_CURRENCY', 'IDR');
define('DEFAULT_ROUNDING_MODE', 'up');
define('DEFAULT_LANGUAGE', 'id');

define('MAX_HISTORY_ITEMS', 10);
define('SUPPORTED_CURRENCIES', ['IDR', 'USD', 'EUR']);

define('ROUNDING_MODES', [
    'up' => 'Pembulatan Ke Atas',
    'down' => 'Pembulatan Ke Bawah',
    'normal' => 'Pembulatan Normal'
]);

define('CURRENCY_SYMBOLS', [
    'IDR' => 'Rp',
    'USD' => '$',
    'EUR' => '€'
]);
?>
