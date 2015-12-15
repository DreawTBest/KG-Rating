<?php

	class LogOutController {
		public function treat($parameters) {
			$auth = new DreawAuthorize();
			$auth->logOut();
		}
	}