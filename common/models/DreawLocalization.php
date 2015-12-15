<?php

	class DreawLocalization {
		private $_DATE;
		private $_LANG = 'en'; // DEFAULT
		private $_SUPPORTED_LANGS = [
			'en'
		];

		public function __construct($lang) {
			if (in_array($lang, $this->_SUPPORTED_LANGS)) {
				$this->_LANG = $lang;
			}
		}

		public function getDateFormat($date) {
			return date('d/m/Y', strtotime($date));
		}

		public function handleLang() {
			/*switch (variable) {
				case 'value':
					# code...
				break;

				default:
					# code...
				break;
			}*/
		}

		public function en_lang() {
			define('add_1', 'Your game has been successfully added to the database.');
			define('add_2', 'This game is already in the database!');

			//define('view_1', );
		}
	}