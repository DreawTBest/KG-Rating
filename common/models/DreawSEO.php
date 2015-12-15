<?php
	class DreawSEO {
		protected $_DOM, $_URL;

		private $_title, $_description, $_keywords, $_author, $_robots, $_sitemap_xml, $_open_graph, $_inline_styles, $_favicon, $_language;

		public function __construct($url) {
			if ($this->_urlExist($url)) {
				$this->_URL = $url;

				$this->_DOM = new SimpleHtmlDom();
				$source = file_get_contents($this->_URL);

				$this->_DOM->load($source);
			}

			else {
				die('URL IS INVALID');
			}
		}

		public function get() {
			$data = [];

			$data['title'] = $this->_titleMeta();
			$data['description'] = $this->_descriptionMeta();
			$data['url'] = $this->_url();
			$data['keywords'] = $this->_keywordsMeta();
			$data['author'] = $this->_authorMeta();
			$data['robots'] = $this->_robotsMeta();
			$data['robots_txt'] = $this->_robotsTxt();
			$data['sitemap_xml'] = $this->_sitemapXml();
			$data['open_graph'] = $this->_openGraph();
			$data['inline_styles'] = $this->_inlineStyles();
			$data['favicon'] = $this->_favicon();
			$data['language'] = $this->_language();

			print '<pre>';
			print_r($data);
			print '</pre>';

			die();
		}

		protected function _titleMeta() { // complete
			foreach ($this->_DOM->find('title') as $element) {
				$this->_title = $element->plaintext;
			}

			return [
				'title' => (!empty($title)) ? $title : 0,
				'valid' => $this->_validTitleMeta()
			];
		}

			protected function _validTitleMeta() {
				if (strlen($this->_titleMeta) < 75) {
					return 1;
				}

				return 0;
			}

		protected function _descriptionMeta() {
			$description = '';

			foreach ($this->_DOM->find('meta[name=description]') as $element) {
				$description = $element->content;
			}

			return [
				'description' => (!empty($description)) ? $description : 0,
				'valid' => $this->_validDescriptionMeta()
			];
		}

			protected function _validDescriptionMeta() {
				if (strlen($this->_descriptionMeta) < 160) {
					return 1;
				}

				return 0;
			}

		protected function _url() {
			return [
				'url' => $this->_url,
				'valid' => $this->_validUrlrl()
			];
		}

			protected function _validUrl() {
				if (strlen($this->_url) < 90) {
					return 1;
				}

				return 0;
			}

		protected function _keywordsMeta() {
			$keywords = '';

			foreach ($this->_DOM->find('meta[name=keywords]') as $element) {
				$keywords = $element->content;
			}

			return [
				'keywords' => (!empty($keywords)) ? $keywords : 0,
				'valid' => $this->_validKeywordsMeta()
			];
		}

			protected function _validKeywordsMeta() {
				$number_of = explode(',', $this->_keywords);

				if (count($number_of) < 11) {
					return 1;
				}

				else if (count($number_of) == 0) {
					return 0;
				}

				return 0;
			}

		protected function _authorMeta() {
			$author = '';

			foreach ($this->_DOM->find('meta[name=author]') as $element) {
				$author = $element->content;
			}

			return [
				'author' => (!empty($author)) ? $author : 0,
				'valid' => (!empty($author)) ? 1 : 0
			];
		}

		protected function _robotsMeta() {
			$robots = '';

			foreach ($this->_DOM->find('meta[name=robots]') as $element) {
				$robots = $element->content;
			}

			return [
				'robots' => (!empty($robots)) ? $robots : 0,
				'valid' => $this->_validRobotsMeta()
			];
		}

			protected function _validRobotsMeta() {
				$number_of = explode(',', $this->_robotsMeta);

				if (count($number_of) == 0) {
					return 0;
				}

				else {
					if (in_array('index', $number_of) && in_array('follow', $number_of)) {
						return 1;
					}

					return 0;
				}
			}

		protected function _robotsTxt() {
			$response = $this->_remoteFileExist('robots.txt');

			if ($response['status'] == true) {
				return [
					'robots_txt' => $response['file_content'], // content
					'valid' => true // check syntax etc ..
				];
			}

			else {
				return [
					'robots_txt' => 'Not found!',// content
					'valid' => 0,
					'valid_reason' => $response
				];
			}
		}

			protected function _validRobotsTxt() {
				if(strstr($this->_robotsTxt, '/')) {
					// sitemap in robots
					return 1;
				}

				return 0;
			}

		protected function _sitemapXml() {
			$response = $this->_remoteFileExist('sitemap.xml');

			if ($response['status'] == true) {
				return [
					//'sitemap_xml' => $response['file_content'], // content
					'valid' => 1
				];
			}

			else {
				return [
					'valid' => 0,
					'valid_reason' => $response
				];
			}
		}

		protected function _openGraph() {
			$open_graph = [];

			foreach ($this->_DOM->find('meta[property*=og:]') as $element) {
				$open_graph[$element->property] = $element->content;
			}

			if (!empty($open_graph)) {
				return [
					'open_graph' => $open_graph,
					'valid' => 1
				];
			}

			return [
				'open_graph' => 'Missing',
				'valid' => 0
			];
			// brute switch ... bla bla bla
		}

		protected function _language() {
			$lang = '';

			foreach ($this->_DOM->find('html') as $element) {
				$lang = $element->lang;
			}

			if (!empty($lang)) {
				return [
					'lang' => $lang,
					'valid' => 1
				];
			}

			return [
				'lang' => 'Missing!',
				'valid' => 0
			];
		}

		protected function _charset() {

		}

		protected function _css() {

		}

		protected function _nonSematics() {

		}

		protected function _inlineStyles() {
			$styles = [];

			foreach ($this->_DOM->find('[style]') as $element) {
				$styles[] = $element->style;
			}

			if (empty($styles)) {
				return [
					'inline_styles' => 'None!',
					'valid' => 1
				];
			}

			return [
				'inline_styles' => $styles,
				'valid' => 0
			];
		}

		protected function _alternativeContent() {

		}

		protected function _linkFollowing() {

		}

		protected function _favicon() {
			$favicon = '';

			foreach ($this->_DOM->find('link[rel*=icon]') as $element) {
				$favicon = $element->href;
			}

			$response = $this->_remoteFileExist($favicon);

			if ($response['status'] == true) {
				return [
					'favicon' => $favicon,
					'valid' => 1
				];
			}

			else {
				return [
					'favicon' => 'Missing!',
					'valid' => 0
				];
			}
		}

		protected function _js() {

		}

		protected function _doctype() {

		}

		protected function _flash() {

		}

		protected function _mediaQueries() {

		}

		protected function _frames() {

		}

		/* HELP METHODS */

		protected function _urlExist($url) {
		    if (!$fp = curl_init($url)) {
		    	return false;
		    }

		    return true;
		}

		private function _remoteFileExist($path) {
			$curl = curl_init($this->_URL . DIRECTORY_SEPARATOR . $path);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			curl_exec($curl);

			$res_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			if ($res_code == '200' || $res_code = '301' || $res_code = '302') {
				return [
					'status' => true,
					'code' => $res_code,
					'file_content' => file_get_contents($this->_URL . DIRECTORY_SEPARATOR . $path)
				];
			}

			return [
				'status' => false,
				'code' => $res_code
			];
		}
	}
?>