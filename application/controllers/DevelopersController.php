<?php

	class DevelopersController {
		public function treat($parameters) {
			$game = new DreawGame();
			$auth = new DreawAuthorize();

			/* AJAX GET DEVELOPER INFO */

				if (isset($_POST['action']) && $_POST['action'] == 'developer_detail') {
					$tp = new DreawTemplateParser('developer_detail', 'application/views/ajax/templates');
					$tp->addTags([
						'empty' => false
					]);
					$tp->addDBCycleTags([
						'games' => $game->addedGames($_POST['uid'])
					]);
					$tp->parseTemplate();

					die();
				}

			/* AJAX GET DEVELOPER INFO */

			$tags = array(
				'title' => 'KG-Rating | Developers',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
				'perm' => $_SESSION['perm_logged'],
				'uid_logged' => $_SESSION['uid_logged']
			);

			$tp = new DreawTemplateParser('developers', 'application/views/');
			$tp->addTags($tags);
			$tp->addDBCycleTags([
				'developers' => $auth->getDevelopers()
			]);
			$tp->parseTemplate();
		}
	}