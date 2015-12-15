<?php

	class DreawCron extends DreawGame {
		protected $_DB, $_REMOTE;

		public function __construct() {
			$this->_DB = new DreawDB();
			$this->_REMOTE = new DreawRemote();
		}

		public function takeSnapshot() {
			$this->_DB->query('SELECT id, url FROM games ORDER BY id');
			$this->_DB->execute();
			$all_games = $this->_DB->fetchAll();

			foreach ($all_games as $game) {
				if ($this->_REMOTE->remoteFileExists($game['url'])) {
					$getted = file_get_contents($game['url'] . '/metrics.json');
					$this->_addGameProgress($getted, $game['id']);
				}

				else {
					// if game not exists
				}
			}
		}

		protected function _addGameProgress($metrics_json, $game_id) {
			$metrics = $this->_parseJsonMetrics($metrics_json);

			$this->_DB->query('INSERT INTO progress (game_id, gameplays_count, rating_count, total_ratings_count, favorites_count, snap_date) VALUES (:game_id, :gameplays_count, :rating_count, :total_ratings_count, :favorites_count, :snap_date)');
			$this->_DB->bind([
				'game_id' => $game_id,
				'gameplays_count' => $metrics['gameplays_count'],
				'rating_count' => $metrics['rating'],
				'total_ratings_count' => $metrics['average_rating_with_count'],
				'favorites_count' => $metrics['favorites_count'],
				'snap_date' => date('Y-m-d H:i:s')
			]);
			$this->_DB->execute();

			/* UPDATE MAIN TABLE GAMES */

			$this->_DB->query('UPDATE games SET actual_gameplays_count = :actual_gameplays_count, actual_rating_count = :actual_rating_count, actual_favorites_count = :actual_favorites_count, total_ratings_count = :total_ratings_count WHERE id = :game_id');
			$this->_DB->bind([
				'actual_gameplays_count' => $metrics['gameplays_count'],
				'actual_rating_count' => $metrics['rating'],
				'actual_favorites_count' => $metrics['favorites_count'],
				'total_ratings_count' => $metrics['average_rating_with_count'],
				'game_id' => $game_id
			]);
			$this->_DB->execute();

			return true;
		}
	}