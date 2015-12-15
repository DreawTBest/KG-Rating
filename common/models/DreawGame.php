<?php

	class DreawGame extends DreawLocalization {
		protected $_DB, $_SESS, $_REMOTE, $_DOM;

		public function __construct() {
			$this->_DB = new DreawDB();
			$this->_SESS = new DreawSession();
			$this->_REMOTE = new DreawRemote();
			$this->_DOM = new SimpleHtmlDom();
		}

		public function add($json, $url) {
			$json = $this->_parseJsonMetrics($json);
			$data = $this->_parseNameAndAuthor($url);

			if ($data['developer'] == $_SESSION['uid_logged'] || $_SESSION['uid_logged'] == 'TBest') {
				if (!$this->_isDuplicate($data['name'])) {
					$this->_DB->query('INSERT INTO games (name, code_represent, developer, url, actual_gameplays_count, actual_rating_count, actual_favorites_count, total_ratings_count, adder, date_add) VALUES (:name, :code_represent, :developer, :url, :actual_gameplays_count, :actual_rating_count, :actual_favorites_count, :total_ratings_count, :adder, :date_add)');
					$this->_DB->bind([
						'name' => $data['name'],
						'code_represent' => $data['code_represent'],
						'developer' => $data['developer'],
						'url' => $url,
						'actual_gameplays_count' => $json['gameplays_count'],
						'actual_rating_count' => $json['rating'],
						'actual_favorites_count' => $json['favorites_count'],
						'total_ratings_count' => $json['average_rating_with_count'],
						'adder' => $this->_SESS->getSess('uid_logged'),
						'date_add' => date('Y-m-d')
					]);
					$this->_DB->execute();

					$this->_SESS->setSess([
						'success' => 'Your game has been successfully added to the database.'
					]);

					return true;
				}

				else {
					$this->_SESS->setSess([
						'error' => 'This game is already in the database!'
					]);

					return false;
				}
			}
			else {
				$this->_SESS->setSess([
					'error' => 'You do not have permission to add this game.'
				]);

				return false;
			}
		}

		public function view() {
			$this->_DB->query('SELECT * FROM games ORDER BY id');
			$this->_DB->execute();
			$games_data = $this->_DB->fetchAll();

			return $games_data;
		}

		public function myGames() {
			$this->_DB->query('SELECT * FROM games WHERE developer = :developer ORDER BY id');
			$this->_DB->bind([
				'developer' => $this->_SESS->getSess('uid_logged')
			]);
			$this->_DB->execute();
			$games_data = $this->_DB->fetchAll();

			return $games_data;
		}

		public function history($name) {
			$this->_DB->query('SELECT id, code_represent FROM games WHERE code_represent = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $name
			]);
			$this->_DB->execute();
			$id_data = $this->_DB->fetchAll();

			$this->_DB->query('SELECT * FROM progress WHERE game_id = :game_id');
			$this->_DB->bind([
				'game_id' => $id_data[0]['id']
			]);
			$this->_DB->execute();
			$history_data = $this->_DB->fetchAll();

			return $history_data;
		}

    	public function customHistory($name, $from, $to) {
		    $from = date('Y-m-d', $from);
		    $to = date('Y-m-d', $to);

	      	$game_id = $this->_getGameId($name);

			$this->_DB->query('SELECT * FROM progress WHERE (game_id = :game_id) AND (snap_date BETWEEN :from AND :to)');
			$this->_DB->bind([
				'game_id' => $game_id,
		        'from' => $from,
		        'to' => $to
			]);
			$this->_DB->execute();
			$history_data = $this->_DB->fetchAll();

			return $history_data;
		}

		public function totalGrowth($name) {
			$game_id = $this->_getGameId($name);

			$this->_DB->query('SELECT * FROM progress WHERE (game_id = :game_id) ORDER BY snap_date DESC LIMIT 1');
			$this->_DB->bind([
				'game_id' => $game_id
			]);
			$this->_DB->execute();
			$newestRecord = $this->_DB->fetchAll();

			$this->_DB->query('SELECT * FROM progress WHERE (game_id = :game_id) ORDER BY snap_date LIMIT 1');
			$this->_DB->bind([
				'game_id' => $game_id
			]);
			$this->_DB->execute();
			$oldestRecord = $this->_DB->fetchAll();

			$gameplaysCountTotal = '+ ' . ($newestRecord[0]['gameplays_count'] - $oldestRecord[0]['gameplays_count']);
			$ratingsCountTotal = $oldestRecord[0]['rating_count'] . ' - ' . $newestRecord[0]['rating_count'];
			$favoritesCountTotal = '+ ' . ($newestRecord[0]['total_ratings_count'] - $oldestRecord[0]['total_ratings_count']);
			$totalRatingsCountTotal = '+ ' . ($newestRecord[0]['favorites_count'] - $oldestRecord[0]['favorites_count']);

			return $data = [
				'game_plays_count_total' => $gameplaysCountTotal,
				'rating_count_total' => $ratingsCountTotal,
				'favorites_count_total' => $favoritesCountTotal,
				'total_ratings_count_total' => $totalRatingsCountTotal
			];
		}

		public function customTotalGrowth($name,$from,$to) {
			/* set right format of 'from'  & 'to' */
		    $from_date = date('Y-m-d', strtotime($from));
		    $to_date = date('Y-m-d', strtotime($to));

			// get game id
			$game_id = $this->_getGameId($name);

			// select newest record in db
			$this->_DB->query('SELECT * FROM progress WHERE (game_id = :game_id) AND (snap_date LIKE :to_date) ORDER BY snap_date DESC LIMIT 1');
			$this->_DB->bind([
				'game_id' => $game_id,
				'to_date' => '%'.$to_date.'%'
			]);
			$this->_DB->execute();
			$newestRecord = $this->_DB->fetchAll();

			// select oldest record in db
			$this->_DB->query('SELECT * FROM progress WHERE (game_id = :game_id) AND (snap_date LIKE :from_date) ORDER BY snap_date LIMIT 1');
			$this->_DB->bind([
				'game_id' => $game_id,
				'from_date' => '%'.$from_date.'%'
			]);
			$this->_DB->execute();
			$oldestRecord = $this->_DB->fetchAll();

			// Total gamePlays - total growth
			$gameplaysCountTotal = $newestRecord[0]['gameplays_count'] - $oldestRecord[0]['gameplays_count'];

			// Average Rating - total growth
			$ratingsCountTotal = $newestRecord[0]['rating_count'] - $oldestRecord[0]['rating_count'];

			// Total ratings - total growth
			$favoritesCountTotal = $newestRecord[0]['total_ratings_count'] - $oldestRecord[0]['total_ratings_count'];

			// Total favorites - total growth
			$totalRatingsCountTotal = $newestRecord[0]['favorites_count'] - $oldestRecord[0]['favorites_count'];

			// return data
			$data = array($gameplaysCountTotal,$ratingsCountTotal,$favoritesCountTotal,$totalRatingsCountTotal);
			return $data;
		}

		public function dailyGrowth($gameplays_data) {
			$daily_stats = [];
			$oldest_date = date('Y-m-d', strtotime($gameplays_data[0]['snap_date']));
			$oldest = 0;

			foreach ($gameplays_data as $progress => $progress_arr) {
				if (date('Y-m-d', strtotime($progress_arr['snap_date'])) != $oldest_date) {
					$daily_stats[date('Y-m-d', strtotime($progress_arr['snap_date']))] = [
						'first' => $gameplays_data[$oldest]['gameplays_count'],
						'last' => $gameplays_data[$progress - 1]['gameplays_count'],
						'growth' => $gameplays_data[$progress - 1]['gameplays_count'] - $gameplays_data[$oldest]['gameplays_count']
					];

					$oldest = $progress;
					$oldest_date = date('Y-m-d', strtotime($progress_arr['snap_date']));
				}
			}

			return $daily_stats;
		}

		public function viewSingleGame($game_name) {
			$id = $this->_getGameId($game_name);

			if ($this->_hasHistory($id)) {
				$this->_DB->query('SELECT * FROM games WHERE code_represent = :name LIMIT 1');
				$this->_DB->bind([
					'name' => $game_name
				]);
				$this->_DB->execute();
				$single_game_data = $this->_DB->fetchAll();

				$single_game_data[] = $this->_gameGrowth($single_game_data);

				$single_game_data[0]['actual_gameplays_count'] = number_format($single_game_data[0]['actual_gameplays_count'], 0, '.', ' ');
				$single_game_data[0]['actual_rating_count'] = number_format($single_game_data[0]['actual_rating_count'], 5, '.', ' ');
				$single_game_data[0]['total_ratings_count'] = number_format($single_game_data[0]['total_ratings_count'], 0, '.', ' ');
				$single_game_data[0]['actual_favorites_count'] = number_format($single_game_data[0]['actual_favorites_count'], 0, '.', ' ');

				$single_game_data[0]['date_add'] = $this->getDateFormat($single_game_data[0]['date_add']);

				return [
					'status' => 1,
					'data' => $single_game_data
				];
			}

			return [
				'status' => 'history_err',
				'data' => ''
			];
		}

		public function validate($url) {
			$url = htmlspecialchars($url, ENT_QUOTES);

			if (filter_var($url, FILTER_VALIDATE_URL) !== FALSE) {
				if (strstr($url, 'kongregate.com/games/')) {
					if (strstr($url, '?')) {
						$url = explode('?', $url)[0];
					}

					if ($this->_REMOTE->remoteFileExists($url . '/metrics.json')) {
						$getted = file_get_contents($url . '/metrics.json');

						$this->add($getted, $url);
					}

					else {
						$this->_SESS->setSess([
							'error' => 'Game does not exists!'
						]);

						return false;
					}
				}

				else {
					$this->_SESS->setSess([
						'error' => 'Your URL is not a valid Kongregate URL!'
					]);

					return false;
				}
			}

			else {
				$this->_SESS->setSess([
					'error' => 'Your URL is not a valid URL!'
				]);

				return false;
			}
		}

		public function toGraph($data, $options = array()) {
			$graph_formed = [];

			if (!empty($options)) {
				if ($options['y-data'] == 'gameplays') {
					foreach ($data as $key => $value) {
						$graph_formed[$key][] = (int) strtotime($value['snap_date']) * 1000;
						$graph_formed[$key][] = (int) $value['gameplays_count'];
					}
				}

				else if ($options['y-data'] == 'rating') {
					foreach ($data as $key => $value) {
						$graph_formed[$key][] = (int) strtotime($value['snap_date']) * 1000;
						$graph_formed[$key][] = (float) $value['rating_count'];
					}
				}

				else if ($options['y-data'] == 'daily') {
					$data = $this->dailyGrowth($data);

					$i = 0;
					foreach ($data as $key => $value) {
						$graph_formed[$i][] = (int) strtotime($key) * 1000;
						$graph_formed[$i][] = (float) $value['growth'];
						$i++;
					}
				}
			}

			return $graph_formed;
		}

		public function getActualStats($name) {
			$this->_DB->query('SELECT url FROM games WHERE name = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $name
			]);
			$this->_DB->execute();
			$url_data = $this->_DB->fetchAll();

			return $this->_getActualStatsFromKG($url_data[0]['url']);
		}

		public function getDevTracker() {
			$this->_DB->query('SELECT * FROM posts WHERE parent = :parent ORDER BY id DESC');
			$this->_DB->bind([
				'parent' => 0
			]);
			$this->_DB->execute();
			$tracker_data = $this->_DB->fetchAll();

			$colors = [
				'bug' => 'danger',
				'idea' => 'success',
				'note' => 'warning'
			];

  			foreach ($tracker_data as $issue => $issue_arr) {
  				// replace issue status
  				$trans = array("0" => "under review", "1" => "processing", "2" => "solved");
  				$tracker_data[$issue]['status'] = strtr($tracker_data[$issue]['status'], $trans);

  				$files = json_decode($tracker_data[$issue]['file'], true);
  				$user_id = $this->_getDevId($tracker_data[$issue]['developer_name']);

  				$tracker_data[$issue]['file_1'] = '';
  				$tracker_data[$issue]['file_2'] = '';
  				$tracker_data[$issue]['file_3'] = '';

  				if (is_array($files)) {
  					$i = 0;
  					foreach($files as $file) {
  						$i++;
  						$path = realpath('common/libs/dev_data/' . $user_id . '/tracker/' . $tracker_data[$issue]['id'] . '_' . $i . '.' . $file);
  						
  						if (file_exists($path)) {
  							$file_path = '/app/secure-img/' . $tracker_data[$issue]['id'] . '_' . $i . '/' . $file . '/' . $user_id;
  							$name = 'file_' . $i;
  							$tracker_data[$issue][$name] = $file_path;
  						}
  					}
  				}

  				if ($tracker_data[$issue]['category'] == 0) {
  					$tracker_data[$issue]['category_color'] = $colors['bug'];
  					$tracker_data[$issue]['category'] = 'bug';
  				}
  				elseif ($tracker_data[$issue]['category'] == 1) {
  					$tracker_data[$issue]['category_color'] = $colors['idea'];  					
  					$tracker_data[$issue]['category'] = 'idea';
  				}
  				elseif ($tracker_data[$issue]['category'] == 2) {
  					$tracker_data[$issue]['category_color'] = $colors['note'];	
  					$tracker_data[$issue]['category'] = 'note';
  				}
    		}

			return $tracker_data;
		}

		public function processedTracker($id) {
			$this->_DB->query("UPDATE posts SET status = :status WHERE id = :id");
			$this->_DB->bind([
				'status' => '2',
				'id' => $id
			]);
			$this->_DB->execute();
		}

		public function getAdminTracker() {
			$this->_DB->query('SELECT * FROM posts WHERE (status = :first) OR (status = :second) ORDER BY id DESC');
			$this->_DB->bind([
				'first' => '0',
				'second' => '1'
			]);
			$this->_DB->execute();
			$tracker_data = $this->_DB->fetchAll();


			$colors = [
				'bug' => 'danger',
				'idea' => 'success',
				'note' => 'warning'
			];

  			foreach ($tracker_data as $issue => $issue_arr) {
  				// replace issue status
  				$trans = array("0" => "under review", "1" => "processing", "2" => "solved");
  				$tracker_data[$issue]['status'] = strtr($tracker_data[$issue]['status'], $trans);

  				$files = json_decode($tracker_data[$issue]['file'], true);
  				$user_id = $this->_getDevId($tracker_data[$issue]['developer_name']);

  				$tracker_data[$issue]['file_1'] = '';
  				$tracker_data[$issue]['file_2'] = '';
  				$tracker_data[$issue]['file_3'] = '';

  				// set viewed
  				$this->_DB->query('UPDATE posts SET status = :status WHERE id = :id');
  				$this->_DB->bind([
  					'status' => '1',
  					'id' => $tracker_data[$issue]['id']
  				]);
  				$this->_DB->execute();

  				if ($tracker_data[$issue]['category'] == 0) {
  					$tracker_data[$issue]['category_color'] = $colors['bug'];
  					$tracker_data[$issue]['category'] = 'bug';
  				}
  				elseif ($tracker_data[$issue]['category'] == 1) {
  					$tracker_data[$issue]['category_color'] = $colors['idea'];  					
  					$tracker_data[$issue]['category'] = 'idea';
  				}
  				elseif ($tracker_data[$issue]['category'] == 2) {
  					$tracker_data[$issue]['category_color'] = $colors['note'];	
  					$tracker_data[$issue]['category'] = 'note';
  				}
    		}

			return $tracker_data;
		}

		public function addIssue($text, $category, $file, $parent) {
			if (!empty($_POST['message_text'])) {
				$date = date('Y:m:d H:i:s');

				if ($category == "bug") {
					$category = '0';
				}
				elseif ($category == "idea") {
					$category = '1';
				}
				elseif ($category == "note") {
					$category = '2';
				}

				$this->_DB->query('INSERT INTO posts (developer_name, text, date, category, parent) VALUES (:developer_name, :text, :date, :category, :parent)');
				$this->_DB->bind([
					'developer_name' => $this->_SESS->getSess('uid_logged'),
					'text' => $text,
					'date' => $date,
					'category' => $category,
					'parent' => $parent
				]);
				$this->_DB->execute();

				// get issue id
				$this->_DB->query('SELECT id FROM posts WHERE (developer_name = :dev) AND (text = :text) AND (date = :date) LIMIT 1');
				$this->_DB->bind([
					'dev' => $this->_SESS->getSess('uid_logged'),
					'text' => $text,
					'date' => $date
				]);
				$this->_DB->execute();

				$issue_id = $this->_DB->fetchAll();
				$issue_id = $issue_id[0]['id'];

				// update files
				if (!empty($file['name'])) {
					$file_ary = $this->_reArrayFiles($file);

					$file_string = array();

					$i = 0;

					foreach ($file_ary as $single_file) {
						$i++;

						$file_type = explode('.', $single_file['name']);

						if (isset($file_type[1])) {
							$file_type = $file_type[1];

							array_push($file_string, $file_type);
						}

						$authorize = new DreawAuthorize();
						$user_id = $authorize->getDeveloperId();

						$foo = new Upload($single_file); 
						$foo->file_new_name_body = $issue_id . '_' . $i;
   						$foo->file_overwrite = false;
   						$foo->allowed = array('image/*', 'application/pdf', 'application/msword', 'application/rar', 'application/zip');
   						$foo->Process('common/libs/dev_data/'.$user_id.'/tracker/');

   						if ($foo->processed) {
     						$this->_SESS->setSess([
								'success' => 'Your issue has been succesfully added.'
							]);
     						$foo->Clean();
   						}
					}

					// update file name
					$file_string = json_encode($file_string);
					$this->_DB->query('UPDATE posts SET file = :file WHERE id = :id');
					$this->_DB->bind([
						'file' => $file_string,
						'id' => $issue_id
					]);
					$this->_DB->execute();
				}
			}
		}

		public function lastAddedGames() {
			$this->_DB->query('SELECT * FROM games LIMIT 5');
			$this->_DB->execute();

			$games_data = $this->_DB->fetchAll();
			return $games_data;
		}

		public function addedGames($developer) {
			$this->_DB->query('SELECT * FROM games WHERE developer = :developer ORDER BY id DESC LIMIT 1');
			$this->_DB->bind([
				'developer' => $developer
			]);
			$this->_DB->execute();
			$games_data = $this->_DB->fetchAll();

			return $games_data;
		}

		public function setProfileImage($file) {
			$foo = new Upload($file); 

   			// get user id
   			$this->_DB->query("SELECT id FROM users WHERE uid = :dev LIMIT 1");
			$this->_DB->bind([
				'dev' => $this->_SESS->getSess('uid_logged'),
			]);
			$this->_DB->execute();
			$user_id = $this->_DB->fetchAll();
			$user_id = $user_id[0]['id'];
   				
 			// save uploaded image with a new name
   			$foo->file_new_name_body = 'profile_image';
   			$foo->file_overwrite = true;
   			$foo->allowed = array('image/*');
   			$foo->image_resize = true;
   			$foo->image_x = 48;
   			$foo->image_y = 48;
   			$foo->Process('common/libs/dev_data/'.$user_id.'/images/');
   				
   			// succeded?
   			if ($foo->processed) {
     			$this->_SESS->setSess([
					'success' => 'Profile image changed.'
				]);
     			$foo->Clean();
   			}
   			else {
     			$this->_SESS->setSess([
					'error' => $foo->error
				]);
   			} 
		}

		public function getDevDescription() {
			$this->_DB->query("SELECT description FROM users WHERE uid = :uid LIMIT 1");
			$this->_DB->bind([
				'uid' => $_SESSION['uid_logged']
			]);
			$this->_DB->execute();

			$data = $this->_DB->fetchAll();
			$description = $data[0]['description'];

			return $description;
		}

		public function setDevDescription($text) {
			$this->_DB->query('UPDATE users SET description = :description WHERE uid = :uid');
			$this->_DB->bind([
				'description' => $text,
				'uid' => $_SESSION['uid_logged']
			]);
			$this->_DB->execute();
		}

		public function addChangeLog($message) {
			if (!empty($message)) {
				$this->_DB->query("INSERT INTO changelog (text, date) VALUES (:text, :date)");
				$this->_DB->bind([
					'text' => $message,
					'date' => date('Y-m-d H:i:s')
				]);
				$this->_DB->execute();

				$this->_SESS->setSess([
					'success' => 'Your changelog has been successfully added.'
				]);
			}
			else {
				$this->_SESS->setSess([
					'error' => 'You must fill in box!'
				]);
			}		
		}

		public function getEveryChangeLog() {
			$this->_DB->query("SELECT * FROM changelog ORDER BY id DESC");
			$this->_DB->execute();

			$return_data = $this->_DB->fetchAll();
			return $return_data;
		}

		public function getNewestChangeLog() {
			// get users last viewed changelog
			$this->_DB->query("SELECT changelog_viewed FROM users WHERE uid = :uid LIMIT 1");
			$this->_DB->bind([
				'uid' => $this->_SESS->getSess('uid_logged')
			]);
			$this->_DB->execute();

			$changelog_viewed = $this->_DB->fetchAll();
			$changelog_viewed = $changelog_viewed[0]['changelog_viewed'];

			// get newest changelog
			$this->_DB->query("SELECT * FROM changelog ORDER BY id DESC LIMIT 1");
			$this->_DB->execute();

			$newest_changelog = $this->_DB->fetchAll();
			
			if ($newest_changelog[0]['id'] != $changelog_viewed) {
				$data = array(
					'view' => 'true',
					'text' => $newest_changelog[0]['text']
				);

				// update changelog_viewed
				
				// get user id
				$authorize = new DreawAuthorize();
				$user_id = $authorize->getDeveloperId();
				
				$this->_DB->query('UPDATE users SET changelog_viewed = :changelog_viewed WHERE id = :id');
				$this->_DB->bind([
					'changelog_viewed' => $newest_changelog[0]['id'],
					'id' => $user_id
				]);
				$this->_DB->execute();

			}
			else {
				$data = array(
					'view' => 'false',
					'text' => ''
				);
			}

			return $data;
		}

			protected function _getActualstatsFromKG($url) {
				$content = file_get_contents($url . '/metrics.json'); // + metrics.json
				$content = json_decode($content, true);

				foreach ($content as $key => $value) {
					if ($key == 'block_game_js' || $key == 'game_statistics' || $key == 'quicklinks_user_rating') {
						$content[$key] = '';
					}

					else {
						$content[$key] = htmlspecialchars($value, ENT_QUOTES);
					}
				}

				return json_encode([
					'gameplays_count' => $content['gameplays_count'],
					'favorites_count' => $content['favorites_count'],
					'rating' => $content['rating']
				]);
			}

		protected function _isDuplicate($name) {
			$this->_DB->query('SELECT name FROM games WHERE name = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $name
			]);
			$this->_DB->execute();
			$duplicate_data = $this->_DB->fetchAll();

			if (empty($duplicate_data)) {
				return false;
			}

			return true;
		}

		protected function _parseJsonMetrics($metrics_json) {
			$metrics = json_decode($metrics_json, true);

			foreach ($metrics as $key => $value) {
				if ($key == 'block_game_js' || $key == 'game_statistics' || $key == 'quicklinks_user_rating') {
					unset($metrics[$key]);
				}

				else if ($key == 'average_rating_with_count') {
					$metrics[$key] = $this->_parseTotalRatings($value);
				}

				else {
					$content[$key] = htmlspecialchars($value, ENT_QUOTES);
				}
			}

			return $metrics;
		}

			protected function _parseTotalRatings($average_rating_with_count) {
				$total_rating = $this->_command($average_rating_with_count, 'of ', ' ratings');
				$exploded = explode(',', $total_rating);

				$finally_total_rating = '';

				foreach ($exploded as $key => $value) {
					$finally_total_rating .= $value;
				}


				return $finally_total_rating;
			}

		protected function _parseNameAndAuthor($url) {
			$exploded = explode('/', $url);
			$exploded = array_reverse($exploded);

			$this->_DOM->load(file_get_contents($url));

			foreach ($this->_DOM->find('[itemprop=name]') as $element) {
				$name = $element->plaintext;
			}

			foreach ($this->_DOM->find('[itemprop=creator]') as $element) {
				$developer = $element->plaintext;
			}

			return [
				'name' => $name,
				'developer' => $developer,
				'code_represent' => $exploded[0]
			];
		}

		protected function _hasHistory($id) {
			$this->_DB->query('SELECT id FROM progress WHERE game_id = :id LIMIT 2');
			$this->_DB->bind([
				'id' => $id
			]);
			$this->_DB->execute();
			$history_data = $this->_DB->fetchAll();

			if (isset($history_data[0]) && isset($history_data[1])) {
				return true;
			}

			return false;
		}

		protected function _gameGrowth($data) {
			$prev_day = date('Y-m-d H:i:s', strtotime('-1 day'));
			$today = date('Y-m-d');

			$this->_DB->query('SELECT gameplays_count, rating_count, favorites_count, total_ratings_count, snap_date FROM progress WHERE snap_date > :prev_day AND snap_date < :today AND game_id = :game_id LIMIT 24');
			$this->_DB->bind([
				'prev_day' => $prev_day,
				'today' => $today,
				'game_id' => $data[0]['id']
			]);
			$this->_DB->execute();
			$prev_day_data = $this->_DB->fetchAll();

			$prev_day_data = end($prev_day_data);

			$diff = [];

			$diff['gameplays_count_daily'] = number_format($data[0]['actual_gameplays_count'] - $prev_day_data['gameplays_count'], 0, '.', ' ');
			$diff['gameplays_count_daily_sign'] = $this->_getSign($diff['gameplays_count_daily']);

			$diff['rating_count_daily'] = number_format($data[0]['actual_rating_count'] - $prev_day_data['rating_count'], 5, '.', ' ');
			$diff['rating_count_daily_sign'] = $this->_getSign($diff['rating_count_daily']);

			$diff['favorites_count_daily'] = number_format($data[0]['actual_favorites_count'] - $prev_day_data['favorites_count'], 0, '.', ' ');
			$diff['favorites_count_daily_sign'] = $this->_getSign($diff['favorites_count_daily']);

			$diff['total_ratings_count_daily'] = number_format($data[0]['total_ratings_count'] - $prev_day_data['total_ratings_count'], 0, '.', ' ');
			$diff['total_ratings_count_daily_sign'] = $this->_getSign($diff['total_ratings_count_daily']);

			return $diff;
		}

		protected function _getGameId($name) {
			$this->_DB->query('SELECT id, name FROM games WHERE code_represent = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $name
			]);
			$this->_DB->execute();
			$id_data = $this->_DB->fetchAll();

			if (empty($id_data)) {
				return false;
			}

			return $id_data[0]['id'];
		}

		protected function _getDevId($dev) {
			$this->_DB->query('SELECT id FROM users WHERE uid = :dev LIMIT 1');
    		$this->_DB->bind([
    			'dev' => $dev
    		]);
    		$this->_DB->execute();
    		$user_data = $this->_DB->fetchAll();

    		if (empty($user_data)) {
    			return false;
    		}

    		return $user_data[0]['id'];
		}

		protected function _getSign($n) {
    		if ($n < 0) {
    			return '-';
    		}

    		else {
    			return '+';
    		}
		}

		protected function _command($string, $start, $end) {
            $string = ' ' . $string;
            $ini = strpos($string, $start);

            if ($ini == 0) return '';

            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;

            return substr($string, $ini, $len);
        }

        protected function _reArrayFiles($file_post) {
        	$file_ary = array();
    		$file_count = count($file_post['name']);
    		$file_keys = array_keys($file_post);

    		for ($i=0; $i<$file_count; $i++) {
        		foreach ($file_keys as $key) {
            		$file_ary[$i][$key] = $file_post[$key][$i];
        		}
    		}

    		return $file_ary;
		}
	}