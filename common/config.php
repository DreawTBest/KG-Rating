<?php
	ini_set('session.use_trans_sid', false);
	ini_set('session.cookie_httponly', 1);
	ini_set('session.use_strict_mode', 1);

	session_start();
	ob_start();

	header('Content-Type: text/html; charset=utf-8');
	header('X-Frame-Options: DENY'); /* Clicjacking prevent */

	defined('INCLUDE') or die('Neoprávněný přístup.');

	define('NUMBERS_OF_OUT_LINES', 10);
	define('DEBUG_MODE', 1);
	define('LOG_FILE_WARNING', 1);
	define('LOG_FILE_FATAL', 1);
	define('LOG_FILE_OWN', 1);
	define('LOG_FILE', BASE_DIR . 'common/logs/log.txt');

	define('DEFAULT_URL', 'public/views');
    define('DEFAULT_SUFFIX', '.phtml');
    define('DEFAULT_REPLACE_ONE', false);
    define('DEFAULT_VIEW_TAG_ERROR', false);

    define('REQ_URI', $_SERVER['REQUEST_URI']);
    define('URL', 'https://'.$_SERVER['HTTP_HOST']);

    define('DB_HOST', 'foobar');
    define('DB_USER', 'foobar');
    define('DB_PASS', 'foobar');
    define('DB_NAME', 'foobar');

    if(!file_exists(BASE_DIR.'common/models/DreawErrorHandler.php')) {
		throw new Exception("Chybí soubor pro zachytávání chyb.");
	} else {
		require_once(BASE_DIR.'common/models/DreawErrorHandler.php');
	}

	$Handler = new DreawErrorHandler(null, 0);



?>
