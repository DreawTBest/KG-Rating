<?php
	class RouterController {
		protected $controller;

        public function Treat($parameters) {
            $parseURL = $this->ParseURL($parameters[0]);

            if (empty($parseURL[0])) {
            	$this->controller = new HomepageController();
            }

            else {
            	$controller = self::CamelNotation($parseURL[0]).'Controller';
         		file_exists(BASE_DIR."public/controllers/{$controller}.php") ? $this->controller = new $controller : $this->redirect('error');
            }

            $this->controller->treat($parseURL);
        }

        private function ParseURL($url) {

	     	$parseURL = parse_url($url);
	     	$parseURL["path"] = ltrim($parseURL["path"], "/");
	     	$parseURL["path"] = trim($parseURL["path"]);
	     	$dividedPath = explode("/", $parseURL["path"]);

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