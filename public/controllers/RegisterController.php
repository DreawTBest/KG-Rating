<?php

    class RegisterController extends RouterController {
        public function treat($parameters) {
            if (isset($_POST['register_submit'])) {
                $register = new DreawAuthorize();
                $register->register($_POST['dev'], $_POST['email'], $_POST['pw'], $_POST['verify_pw'], $_POST['dreaw_token']);

                $developer = $_POST['dev'];
                $email = $_POST['email'];
            }
            else {
                $developer = '';
                $email = '';
            }

            $tags = array(
                'title' => 'KG-Rating | Register',
                'url_data' => URL . '/common/libs/template',
                'bootstrap' => URL . '/common/libs/bootstrap',
                'datatables' => URL . '/common/libs/dataTables',

                'developer_value' => $developer,
                'email_value' => $email
            );

            $tp = new DreawTemplateParser('register', 'public/views');
            $tp->addTags($tags);
            $tp->parseTemplate();
        }
    }