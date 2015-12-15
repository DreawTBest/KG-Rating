<?php

    class ProfileController {
        public function treat($parameters) {
            $game = new DreawGame();

            if (isset($parameters[2])) {
                // Are you realy this developer?
                if ($parameters[2] == $_SESSION['uid_logged']) {
                    // calculate games
                    $games = $game->addedGames($_SESSION['uid_logged']);
                    if (isset($games[0])) {
                        $count_games = count($games);
                    }
                    else {
                        $count_games = '0';
                    }
                }
                else {
                    header ('Location: /app/');
                }
            }
            else {
                header ('Location: /app/');
            }

            // Set new profile image
            if (isset($_POST['set_profile'])) {
                if (isset($_FILES['image_field'])) {
                    $game->setProfileImage($_FILES['image_field']);
                }
            }

            // Change profile description
            if (isset($_POST['action']) && $_POST['action'] == 'change_description') {
                if(!empty($_POST['text'])) {
                    $game->setDevDescription($_POST['text']);
                }
                $data = $game->getDevDescription();
                die(json_encode($data));
            }

            // output
            $tags = array(
                'title' => 'KG-Rating | Profile',
                'bootstrap' => URL . '/common/libs/bootstrap',
                'datatables' => URL . '/common/libs/dataTables',
                'url' => URL,
                'url_data' => URL . '/common/libs/template',
                'uid_logged' => $_SESSION['uid_logged'],
                'mail_logged' => $_SESSION['mail_logged'],
                'perm' => $_SESSION['perm_logged'],
                'games_number' => $count_games,
                'description' => $game->getDevDescription()
            );

            $tp = new DreawTemplateParser('profile', 'application/views/');
            $tp->addTags($tags);
            $tp->parseTemplate();

        }
    }