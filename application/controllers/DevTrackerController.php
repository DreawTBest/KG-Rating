<?php

	class DevTrackerController {
		public function treat($parameters) {
			$game = new DreawGame();

			if (isset($parameters[2])) {
				if ($parameters[2] == 'add') {
					// add issue
					$tags = array(
						'title' => 'KG-Rating | Add Issue',
						'bootstrap' => URL . '/common/libs/bootstrap',
						'datables' => URL . '/common/libs/dataTables',
						'url' => URL,
						'url_data' => URL . '/common/libs/template',
						'perm' => $_SESSION['perm_logged'],
						'uid_logged' => $_SESSION['uid_logged']
					);

					$tp = new DreawTemplateParser('tracker_add', 'application/views/');
					$tp->addTags($tags);
					$tp->parseTemplate();

					if (isset($_POST['tracker_add_submit'])) {
						$game->addIssue($_POST['message_text'], $_POST['category'], $_FILES['ufile'], 0);
					}
				}
				else {
					// dev tracker

					$tags = array(
						'title' => 'KG Rating | DevTracker',
						'bootstrap' => URL . '/common/libs/bootstrap',
						'datables' => URL . '/common/libs/dataTables',
						'url' => URL,
						'url_data' => URL . '/common/libs/template',
						'perm' => $_SESSION['perm_logged'],
						'uid_logged' => $_SESSION['uid_logged']
					);

					$tp = new DreawTemplateParser('dev_tracker', 'application/views');
					$tp->addTags($tags);
					$tp->addDBCycleTags([
						'issues' => $game->getDevTracker()
					]);
					$tp->parseTemplate();
				}
			}
			else {
				header('Location: /app/');
				exit(0);
			}
		}
	}