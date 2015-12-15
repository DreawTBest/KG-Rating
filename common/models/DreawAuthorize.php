<?php

	class DreawAuthorize extends DreawLocalization {
		protected $_DB, $_SESS, $_GAME;

		public function __construct() {
			$this->_DB = new DreawDB();
			$this->_SESS = new DreawSession();
			$this->_GAME = new DreawGame();
		}

		public function isAuthorized() {
			if (isset($_SESSION['logged']) && $_SESSION['logged'] == true && !empty($_SESSION['uid_logged'])) {
				$this->_DB->query('SELECT uid FROM users WHERE uid = :uid_logged LIMIT 1');
				$this->_DB->bind([
					'uid_logged' => $this->_SESS->getSess('uid_logged')
				]);
				$this->_DB->execute();
				$logged_data = $this->_DB->fetchAll();

				if (!empty($logged_data)) {
					return true;
				}

				$this->_SESS->setSess([
					'error' => 'Unauthorized request'
				]);

				header('Location: /login');
				exit(0);
			}

			else {
				$this->_SESS->setSess([
					'error' => 'You do not have permissions to see this page!'
				]);

				header('Location: /login');
				exit(0);
			}
		}

		public function logIn($uid, $pass, $token) {
			if (!empty($uid)) {
				if (!empty($pass)) {
					if (!empty($token) && $token == $this->_SESS->getSess('dreaw_token')) {
						$this->_DB->query('SELECT id, pass, mail, last_logged, perm, verified FROM users WHERE uid = :uid LIMIT 1');
						$this->_DB->bind([
							'uid' => $uid
						]);
						$this->_DB->execute();
						$user_data = $this->_DB->fetchAll();

						if (!empty($user_data)) {
							if ($user_data[0]['verified'] == 1) {
								if (password_verify($pass, $user_data[0]['pass'])) {
									$this->_SESS->setSess([
										'logged' => true,
										'uid_logged' => $uid,
										'mail_logged' => $user_data[0]['mail'],
										'last_logged' => $user_data[0]['last_logged'],
										'perm_logged' => $user_data[0]['perm']
									]);

									// update last_logged
									$this->_DB->query('UPDATE users SET last_logged = :last_logged WHERE id = :id');
									$this->_DB->bind([
										'last_logged' => date('Y-m-d H:i:s'),
										'id' => $user_data[0]['id']

									]);
									$this->_DB->execute();

									// game to redirect
									$game = new DreawGame();
									$games = $game->addedGames($_SESSION['uid_logged']);

									if (isset($games[1])) {
										header('Location: /app/myGames/');
										exit(0);
									}
									elseif (isset($games[0])) {
										$url = '/app/games/'.$games[0]['code_represent'];
										header('Location: '.$url.'');
										exit(0);
									}
									else {
										header('Location: /app/gameAdd');
										exit(0);
									}
									exit(0);
								}

								else {
									$this->_SESS->setSess([
										'error' => 'You have entered an invalid login data. Please check your credentials and try again.'
									]);

									return false;
								}
							}

							else {
								$this->_SESS->setSess([
									'error' => 'Your account is not verified yet.'
								]);
							}
						}

						else {
							$this->_SESS->setSess([
								'error' => 'You have entered an invalid login data. Please check your credentials and try again.'
							]);

							return false;
						}
					}

					else {
						$this->_SESS->setSess([
							'error' => 'Validity of this page has expired. Please reload this page.'
						]);

						return false;
					}
				}

				else {
					$this->_SESS->setSess([
						'error' => 'You have entered an invalid login data. Please check your credentials and try again.'
					]);

					return false;
				}
			}

			else {
				$this->_SESS->setSess([
					'error' => 'You have entered an invalid login data. Please check your credentials and try again.'
				]);

				return false;
			}
		}

		public function logOut() {
			$this->_SESS->delSess('uid_logged');
			$this->_SESS->delSess('mail_logged');
			$this->_SESS->delSess('last_logged');

			$this->_SESS->setSess([
				'logged' => false,
				'success' => 'You have been successfully logged out.'
			]);

			header('Location: /login');
			exit(0);
		}

		public function register($developer, $email, $pw, $verifyPw, $token) {
			// check register data
			if (!empty($developer)) {
				if (!empty($email)) {
					if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
						if (!empty($pw)) {
							if ($pw == $verifyPw) {
								if ((strlen($pw) < 65) & (strlen($pw) > 7)) {
									if (!$this->isDeveloperExist($developer)) {
										if (!empty($token) && $token == $this->_SESS->getSess('dreaw_token')) {
											// everythink alright -> register user

											// hash password
											$pw = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 10]);

											// create record in db
											$this->_DB->query("INSERT INTO users(uid, pass, mail, verified, created) VALUES(:dev, :pass, :mail, :verified, :created)");
											$this->_DB->bind([
												'dev' => $developer,
												'pass' => $pw,
												'mail' => $email,
												'verified' => 0,
												'created' => date('Y-m-d H:i:s')
											]);
											$this->_DB->execute();

											// inform user of successful registration
											$this->_SESS->setSess([
												'success' => 'You have been successfully registrated.'
											]);

											// create dir for developer's images
											$this->_DB->query("SELECT id FROM users WHERE (uid = :dev) AND (pass = :pass) LIMIT 1");
											$this->_DB->bind([
												'dev' => $developer,
												'pass' => $pw
											]);
											$this->_DB->execute();
											$user_id = $this->_DB->fetchAll();

											$user_id = $user_id[0]['id'];

											mkdir('common/libs/dev_data/'.$user_id, 0777);
											mkdir('common/libs/dev_data/'.$user_id.'/images', 0777);

											require 'common/libs/mailer/class.phpmailer.php';

											$mailer = new PHPMailer();

											$mailer->IsHTML(true);
											$mailer->CharSet = "utf-8";

											$mailer->From = 'noreply@kg-rating.com';
											$mailer->FromName = 'KG-Rating Application';

											$mailer->Subject = 'You have been registered successfully!';
											$mailer->Body = '<p>Hello,</p>

															<p>thank you for registering to our application KG-Rating. At the end of this email you will find your generated key, which you have to send to user "KG_Rating" on Kongregate via PM. By this we can verify your identity. Unfortunately we do not have an easier way how to do this, because we have not received any help from Kongregate on this subject.</p>

															<p>Here is your key: <b>' . $this->generateRandomString(8) . '</b></p>

															<p>Thanks</p>';
											$mailer->AddAddress($email);

											if (!$mailer->send()) {
												die($mailer->ErrorInfo);
											}

											return true;
										}
										else {
											$this->_SESS->setSess([
												'error' => 'Token is invalid. Please reload a page'
											]);
										}
									}

									else {
										$this->_SESS->setSess([
											'error' => 'This developer already exists!'
										]);
									}
								}
								else {
									$this->_SESS->setSess([
										'error' => 'The length of your password is incorrect [8 - 65] characters'
									]);
								}
							}
							else {
								$this->_SESS->setSess([
									'error' => 'Your passwords are not same. Please try it again.'
								]);
							}
						}
						else {
							$this->_SESS->setSess([
								'error' => 'You have empty password. Please try it again.'
							]);
						}
					}
					else {
						$this->_SESS->setSess([
							'error' => 'Your email address is incorrect. Please try it again.'
						]);
					}
				}
				else {
					$this->_SESS->setSess([
						'error' => 'You have empty email address. Please try it again.'
					]);
				}
			}
			else {
				$this->_SESS->setSess([
					'error' => 'You have empty developer name. Please try it again.'
				]);
			}
		}

		public function isDeveloper($game) {
			$this->_DB->query('SELECT developer FROM games WHERE code_represent = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $game
			]);
			$this->_DB->execute();
			$developer_data = $this->_DB->fetchAll();

			if ($this->_SESS->getSess('uid_logged') == $developer_data[0]['developer']) {
				return true;
			}
			else {
				return false;
			}
		}

		public function isDeveloperExist($name) {
			$this->_DB->query('SELECT id FROM users WHERE uid = :name LIMIT 1');
			$this->_DB->bind([
				'name' => $name
			]);
			$this->_DB->execute();
			$developer_data = $this->_DB->fetchAll();

			if (empty($developer_data)) {
				return false;
			}

			return true;
		}

		public function getDeveloperId() {
			$this->_DB->query('SELECT id FROM users WHERE uid = :dev LIMIT 1');
			$this->_DB->bind([
				'dev' => $this->_SESS->getSess('uid_logged')
			]);
			$this->_DB->execute();

			$id = $this->_DB->fetchAll();
			$id = $id[0]['id'];
			return $id;
		}

		public function getDevelopers($num = false) {
			if ($num != false && is_numeric($num)) {
				$this->_DB->query('SELECT * FROM users WHERE perm = 1 ORDER BY id LIMIT :limit');
				$this->_DB->bind([
					'limit' => $num
				]);
			}

			else {
				$this->_DB->query('SELECT * FROM users WHERE perm = 1');
			}

			$this->_DB->execute();
			$developers_data = $this->_DB->fetchAll();

			foreach ($developers_data as $developer => $developer_arr) {
				$developers_data[$developer]['created'] = $this->getDateFormat($developer_arr['created']);
			}

			return $developers_data;
		}

		public function generateRandomString($length = 8) {
	    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    	$charactersLength = strlen($characters);
	    	$randomString = '';

		    for ($i = 0; $i < $length; $i++) {
		        $randomString .= $characters[rand(0, $charactersLength - 1)];
		    }

		    return $randomString;
		}
	}