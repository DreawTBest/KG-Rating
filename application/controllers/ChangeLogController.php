<?php

	class ChangeLogController {
		public function treat($parameters) {
			$game = new DreawGame();

			// view
			$tags =  array(
				'title' => 'KG-Rating | ChangeLog',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template',
				'perm' => $_SESSION['perm_logged'],
				'uid_logged' => $_SESSION['uid_logged']
			);

			if ($_SESSION['perm_logged'] == "0") {
				if (isset($_POST['changelog_add_submit'])) {
					$game->addChangeLog($_POST['changelog_msg']);
				}

				$tp = new DreawTemplateParser('changelog_private', 'application/views');
				$tp->addTags($tags);
				$tp->addDBCycleTags([
					'logs' => $game->getEveryChangeLog()
				]);
				$tp->parseTemplate();
			}
			else {
				$tp = new DreawTemplateParser('changelog', 'application/views/');
				$tp->addTags($tags);
				$tp->addDBCycleTags([
					'logs' => $game->getEveryChangeLog()
				]);
				$tp->parseTemplate();
			}
		}
	}