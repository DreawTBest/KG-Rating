<?php

	class MyGamesController {
		public function treat($parameters) {
			$game = new DreawGame();
			$verify = new DreawAuthorize();

			/* AJAX -> changelog */
				if(isset($_POST['action']) && $_POST['action'] == 'changelog') {
					// get newest changelog and return it
					$data = $game->getNewestChangeLog();
      				die(json_encode($data));
      			}

			/* /AJAX -> changelog */

			// view
			$tags = array(
				'title' => 'KG-Rating | MyGames',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
				'perm' => $_SESSION['perm_logged'],
				'uid_logged' => $_SESSION['uid_logged']
			);

			$tp = new DreawTemplateParser('games_my', 'application/views/');
			$tp->addTags($tags);
			$tp->addDBCycleTags([
				'games' => $game->myGames()
			]);
			$tp->parseTemplate();
		}
	}