<?php

	class ErrorController extends RouterController {
		public function treat($parameters) {
			$tags = array(
				'title' => 'KG-Rating | Error',
				'bootstrap' => URL . '/common/libs/bootstrap',
				'datatables' => URL . '/common/libs/dataTables',
				'url' => URL,
				'url_data' => URL . '/common/libs/template'
			);

			$tp = new DreawTemplateParser('error', 'public/views');
			$tp->addTags($tags);
			$tp->parseTemplate();
		}
	}