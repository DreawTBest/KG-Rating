<?php

	define('BASE_DIR', __DIR__ . '/');
	define('INCLUDE', 1);

	header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

	ini_set('session.cookie_lifetime', '0');

	if(!file_exists('common/config.php')) {
		throw new Exception("Zaváděcí soubor neexistuje.");
	}

	else {
		require_once('common/config.php');
	}

	function AutoLoadFunction($class) {
		$class = ucfirst($class);
		$parse_url = array($_SERVER['REQUEST_URI']);
		$path = parse_url($parse_url[0]);

		$parts = explode("/", substr($path['path'], 1));

		if ($parts[0] == 'app') {
			(preg_match('/Controller$/', $class)) ? require("application/controllers/{$class}.php") : require("common/models/{$class}.php");
		}

		else { // get form public folder in otherwise ...
			(preg_match('/Controller$/', $class)) ? require("public/controllers/{$class}.php") : require("common/models/{$class}.php");
		}
	}


	spl_autoload_register("AutoloadFunction");

	$controller = new RouterController();
	$controller->Treat(array(REQ_URI));

?>