<?php

	class SecureImgController {
		public function treat($parameters) {
			if (isset($parameters[2])) {
				// get user id
				$authorized = new DreawAuthorize();
				$user_id = $authorized->getDeveloperId();

				// get path to image
				if ($parameters[2] == 'profile_image') {
					if (isset($parameters[3])) {
						$user_id = $parameters[3];
					}

					$path = realpath('common/libs/dev_data/' . $user_id . '/images/' . $parameters[2] . '.jpg');
			    	header('Content-type: image/jpeg');

			    	if (file_exists($path)) {
			    		readfile($path);
			    		exit(0);
    				}
    				else {
    					readfile('common/libs/template/img/profile_small.jpg');
    					exit(0);
    				}
				}
				else {
					if (isset($parameters[3]) & isset($parameters[4])) {
						$path = realpath('common/libs/dev_data/' . $parameters[4] . '/tracker/' . $parameters[2] . '.' . $parameters[3]);
						header('Content-Type: application/octet-stream');
						header("Content-Transfer-Encoding: Binary");
						header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");

						readfile($path);
						exit(0);
					}
				}

    			// end code
    			exit(0);
			}
			else {
				header('Location: /app/');
				exit(0);
			}
		}
	}