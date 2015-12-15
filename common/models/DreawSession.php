<?php

	class DreawSession {

		private $_DB;

		public function __construct() {
			$this->_DB = new DreawDB();
		}

		public function getSess($sessionName, $del = 0) {
			if (isset($_SESSION[$sessionName])) {
				$sess = $_SESSION[$sessionName];
				if ($del == 1) {
					unset($_SESSION[$sessionName]);
				}

				return $sess;
			}

			return '';

		}

		public function setSess($parameters) {
			foreach ($parameters as $sessionName => $sessionValue) {
				$_SESSION[$sessionName] = $sessionValue;
			}

			return true;
		}

		public function getMess() {
			$error 		= $this->getSess('error');
			$warning 	= $this->getSess('warning');
			$success 	= $this->getSess('success');

			if(!empty($error)) {
				$message = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
				$this->delSess('error');
			} else {
				if(!empty($warning)) {
					$message = '<div class="alert alert-warning" role="alert">' . htmlspecialchars($warning) . '</div>';
					$this->delSess('warning');
				} else {
					if(!empty($success)) {
						$message = '<div class="alert alert-success" role="alert">' . htmlspecialchars($success) . '</div>';
						$this->delSess('success');
					} else {
						$message = null;
					}
				}
			}

			$this->setSess([
				'error' => ''
			]);
			$this->setSess([
				'warning' => ''
			]);
			$this->setSess([
				'success' => ''
			]);

			return $message;
		}

		public function delSess($sessionName) {
			unset($_SESSION[$sessionName]);

			return true;
		}
	}