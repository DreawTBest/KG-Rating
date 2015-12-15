<?php

	class GamesController {
		public function treat($parameters) {
			$game = new DreawGame();
			$sess = new DreawSession();

			/* AJAX REQUEST OF ALL GRAPHS */

				if (isset($_POST['graph_data']) && !empty($_POST['graph_data'])) {
					$graph_data = htmlspecialchars($_POST['graph_data'], ENT_QUOTES);
          			$game_history = $game->history($parameters[2]);
					$game_history = $game->toGraph($game_history, ['y-data' => $graph_data]);

          			die(json_encode($game_history));
				}

			/* /AJAX REQUEST OF ALL GRAPHS */

			/* AJAX REQUEST OF RANGE GRAPHS */

      			if (isset($_POST['action']) && $_POST['action'] == 'range_update') {
			        $game_history = $game->customHistory($parameters[2], $_POST['from_date'], $_POST['to_date']);

			        $gameplays_range = $game->toGraph($game_history, ['y-data' => 'gameplays']);
			        $daily_range = $game->toGraph($game_history, ['y-data' => 'daily']);
			        $rating_range = $game->toGraph($game_history, ['y-data' => 'rating']);

        			die(json_encode([[$gameplays_range], [$daily_range], [$rating_range]]));
      			}

      		/* /AJAX REQUEST OF RANGE GRAPHS */

      		/* AJAX request of get actual game stats */

				if (isset($_POST['get_actual_data'])) {
					$name = htmlspecialchars($_POST['get_actual_data'], ENT_QUOTES);

					die($game->getActualStats($name));
				}

			/* /AJAX request of get actual game stats */

			/* AJAX -> changelog */
				if(isset($_POST['action']) && $_POST['action'] == 'changelog') {
					// get newest changelog and return it
					$data = $game->getNewestChangeLog();
      				die(json_encode($data));
      			}

			/* /AJAX -> changelog */

			$tags = array(
				'title' => 'KG-Rating | Games',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
        		'date_actual' => date('Y/m/d'),
        		'perm' => $_SESSION['perm_logged'],

				'uid_logged' => $_SESSION['uid_logged']
			);

			$tags_single = array(
				'title' => 'KG-Rating | SingleGame',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
        		'date_actual' => date('d/m/Y'),
        		'perm' => $_SESSION['perm_logged'],

				'uid_logged' => $_SESSION['uid_logged']
			);

			if (!isset($parameters[2])) {
				$tp = new DreawTemplateParser('games', 'application/views/');
				$tp->addTags($tags);
				$tp->addDBCycleTags([
					'games' => $game->view()
				]);
				$tp->parseTemplate();
			}

			else if (!empty($parameters[2])) {
				$verify = new DreawAuthorize();
				$isDeveloper = $verify->isDeveloper($parameters[2]);
				$developerName = $sess->getSess('uid_logged');

				if ((!$isDeveloper) AND ($developerName != 'JZikes') AND ($developerName != 'TBest') AND ($developerName != 'MBezdek')) {
					$session = new DreawSession();

					$session->setSess([
						'error' => 'You do not have permissions to see this page!'
					]);

					header('Location: ../games/');
					exit(0);
				}

				$single_game = $game->viewSingleGame($parameters[2]);

				if ($single_game['status'] == 1) {
          			$tp = new DreawTemplateParser('game_single', 'application/views/');
          			$tp ->addTags($tags_single)
						->addDBTags($single_game['data'])
						->addDBCycleTags([
							'history' => $game->history($parameters[2])
						])
						->parseTemplate();
				}

				else {
					die('Game has not any history, please wait 1 hour!');
				}
			}

			else {
				$this->redirect('error');
			}
		}
	}