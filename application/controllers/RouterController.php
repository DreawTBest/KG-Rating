<?php

	class RouterController {
		protected $controller;

        public function Treat($parameters) {
        	$auth = new DreawAuthorize();
        	$auth->isAuthorized();

            $parseURL = $this->ParseURL($parameters[0]);

            $repre_names = [
            	'dev-tracker' => 'devTracker',
            	'secure-img' => 'secureImg'
            ];

            // $parseURL[0] = 'app';

            if ($parseURL[0] != 'app') { // Unveliable to get true in this statement -> application error
            	$this->redirect('error');
            }
            elseif (!isset($parseURL[1])) {
            	file_exists(BASE_DIR . "application/controllers/HomepageController.php") ? $this->controller = new HomepageController : $this->redirect('error');
            }
            else {
            	$controller = self::CamelNotation(strtr($parseURL[1], $repre_names)) . 'Controller';
         		file_exists(BASE_DIR . "application/controllers/{$controller}.php") ? $this->controller = new $controller : $this->redirect('error');
            }

            $this->controller->treat($parseURL);
        }

        private function ParseURL($url) {

	     	$parseURL = parse_url($url);
	     	$parseURL["path"] = ltrim($parseURL["path"], "/");
	     	$parseURL["path"] = trim($parseURL["path"]);
	     	$dividedPath = explode("/", $parseURL["path"]);

	     	if (empty(end($dividedPath))) {
	     		array_pop($dividedPath);
	     	}

	     	return $dividedPath;
		}

		private function CamelNotation($text) {
	     	$string = str_replace('-', ' ', $text);
	     	$string = ucwords($string);
	     	$string = str_replace(' ', '', $string);

	     	return $string;
		}

		static function Redirect($url) {
		    header("Location: /{$url}");
		    header("Connection: close");
		    exit();
		}
	}

?>