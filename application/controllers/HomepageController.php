<?php

	class HomepageController {
		public function treat($parameters) {
			$game = new DreawGame();
			$auth = new DreawAuthorize();

			/*$contest = new DreawContest();
			$contest->set();*/

			// output
			$tags = array(
				'title' => 'KG-Rating | Homepage',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
				'uid_logged' => $_SESSION['uid_logged'],
				'perm' => $_SESSION['perm_logged'],
				'games_number' => count($game->lastAddedGames())
			);

			// processed?
			if (isset($_GET['action']) && $_GET['action'] == "processed") {
				$game->processedTracker($_GET['id']);
			}

			if (($_SESSION['uid_logged'] == 'JZikes') OR ($_SESSION['uid_logged'] == 'TBest')) {
				//$seo = new DreawSEO('http://www.dreaw.cz');
				//$seo->get();

				$tp = new DreawTemplateParser('homepage_stats', 'application/views');
				$tp->addTags($tags);
				$tp->addDBCycleTags([
					'last_games' => $game->lastAddedGames(),
					'last_developers' => $auth->getDevelopers(5),
					'issues' => $game->getAdminTracker()
				]);
				$tp->parseTemplate();
			}

			else {
				$tp = new DreawTemplateParser('homepage', 'application/views/');
				$tp->addTags($tags);
				$tp->parseTemplate();
			}
		}
	}