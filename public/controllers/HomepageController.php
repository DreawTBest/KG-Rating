<?php

	class HomepageController extends RouterController {
		public function treat($parameters) {

			header('Location: /login');
			exit(0);

			$remote = new DreawRemote();

			$tags = array(
				'title' => 'KG-Rating | Homepage',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template'
			);

			$tp = new DreawTemplateParser('homepage', 'public/views/');
			$tp->addTags($tags);
			$tp->parseTemplate();

		}
	}

?>