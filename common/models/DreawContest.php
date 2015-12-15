<?php

	class DreawContest {
		protected $_DB, $_SESS;

		public function __construct() {
			$this->_DB = new DreawDB();
			$this->_SESS = new DreawSession();
		}

		public function set() {
			$data = file_get_contents('http://www.kongregate.com/contests?haref=hp_devcontest');
			$dom = new SimpleHtmlDom();
			$dom->load($data);

			$tables = [];
			$temp = [];

			foreach ($dom->find('table.contests') as $element) {
				foreach ($element->find('tr.js-game-hover') as $tr) {
					$temp[] = trim($tr->plaintext);
				}

				$tables[] = $temp;
				$temp = '';
			}

			$this->_DB->query('INSERT INTO contests (data, snap_date) VALUES (:data, :snap_date)');
			$this->_DB->bind([
				'data' => json_encode($tables),
				'snap_date' => date('Y-m-d H:i:s'),
			]);
			$this->_DB->execute();

			return $tables;
		}
	}