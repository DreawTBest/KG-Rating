<?php

	class LoginController {
		public function treat($parameters) {

			if (isset($_POST['login_submit'])) {
				$auth = new DreawAuthorize();
				$auth->logIn($_POST['uid'], $_POST['pass'], $_POST['dreaw_token']);
			}

			$tags = array(
				'title' => 'KG-Rating | Login',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template'
			);

			$tp = new DreawTemplateParser('login', 'public/views/');
			$tp ->addTags($tags)
				->parseTemplate();
		}
	}