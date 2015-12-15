<?php

	class GameAddController {
		public function treat($parameters) {
			$game = new DreawGame();

			/* AJAX -> changelog */
				if(isset($_POST['action']) && $_POST['action'] == 'changelog') {
					// get newest changelog and return it
					$data = $game->getNewestChangeLog();
      				die(json_encode($data));
      			}

			/* /AJAX -> changelog */
			
			if (isset($_POST['game_submit'])) {
				$game->validate($_POST['game_url']);
			}

			$tags = array(
				'title' => 'KG-Rating | AddGame',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
				'perm' => $_SESSION['perm_logged'],
				'uid_logged' => $_SESSION['uid_logged']
			);

			$tp = new DreawTemplateParser('game_add', 'application/views/');
			$tp ->addTags($tags)
				->parseTemplate();

		}
	}