<?php

	class GamesController extends RouterController {
		public function treat($parameters) {
			$game = new DreawGame();

			$tags = array(
				'title' => 'KG-Rating | Games',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template'
			);

			$tp = new DreawTemplateParser('games', 'public/views/');
			$tp->addTags($tags);
			$tp->addDBCycleTags([
				'games' => $game->view()
			]);
			$tp->parseTemplate();
		}
	}