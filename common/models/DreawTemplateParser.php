<?php
    /**
    * Třída šablonování
    */

    define('KAT_INCLUDED', 1);

    define('TAG_MISS', 'Tento tag nebyl poslán do Parseru!');


    class DreawTemplateParser {

        /**
        * Výstup stránky
        */
        public $_template;

        /**
        * Mezipamět pro cyklický výpis
        */
        private $_cache;

        /**
         * Načtené tagy k nahrazení
         */
        private $_tags = array();

        /**
         * Data z DB
         */
        private $_DBTags = array();

        /**
         * Data z DB určené pro cyklický výpis
         */
        private $_DBCycleTags = array();

        /**
         * Chybové hlášky k nahrazení
         */
        private $_errors = array();

        /**
         * Defaultní cesta k šabloně nastavená v configu
         */
        private $_url = DEFAULT_URL;

        /**
         * Koncovka šablony nastavená v configu
         */
        private $_suffix = DEFAULT_SUFFIX;

        /**
         * Možnost nahrazení pouze jedné stejné značky při NECYKLICKÉM výpisu
         */
        private $_replaceOne = false;

        /**
         * Zobrazení chyb tagů v parseru
         */
        private $_viewTagError = false;

        /**
         * Nastaví cestu k šablonám
         * @param $url
         */
        public function setURL($url) {
            $this->_url = $url;
        }

        /**
         * Nastaví koncovku šablon - například .html
         * @param $suffix
         */
        public function setSuffix($suffix) {
            $this->_suffix = $suffix;
        }

        /**
         * Nastaví možnost přepisování více značek
         * @param bool
         */
        public function setReplaceOne($bool) {
            $this->_replaceOne = $bool;
        }

        public function viewTagError($bool) {
            $this->_viewTagError = $bool;
        }

        /**
        * Zjistí, jestli šablona existuje a její obsah načte do proměnné $_template
        * @param string $templateFile - soubor šablony
        * @return None
        */
        public function __construct($templateFile, $alternative_url = null) {

            if ($alternative_url != null) {
                $this->_url = $alternative_url;
            }

            /* Zjistíme, jestli zadaná šablona existuje */
            if (file_exists($this->_url . '/' . $templateFile . $this->_suffix)) {
                $this->_template = file_get_contents($this->_url . '/' .  $templateFile . $this->_suffix);
            }
            else {
                die('Chyba: Šablona ' . $templateFile . $this->_suffix . ' nebyla nalezena');
            }

            return $this;
        }

        /**
        * Zpracuje šablonu a nahradí značky
        * @return void
        */
        public function parseTemplate($view = 1) {

            /* Ověří se, jestli je nějaký tok dat do Parseru */
            if (count($this->_tags) > 0 || count($this->_DBTags) > 0 || count($this->_DBCycleTags) > 0) {

                /* Poskládání šablony */
                $this->buildTemplate();

                /* Získání message */
                $this->getMessage();

                /* Získání Tokenu */
                $this->getToken();

                /* Security for user input */
                $this->toSess();

                /* Pokud jsou nadefinované nějaké tagy, tak se začne provádět nahrazování */

                if (count($this->_DBCycleTags) > 0) {
                    foreach ($this->_DBCycleTags as $key => $data_value) {
                        if (strpos($this->_template, $key)) { // if is tag in template
                            $repeat = $this->command($this->_template, '{' . $key . '|foreach}', '{' . $key . '|/foreach}');
                            $foreach_in_template = '{' . $key . '|foreach}' . $repeat . '{' . $key . '|/foreach}';

                            foreach ($data_value as $row) {
                                $loop_row = $repeat;

                                foreach ($row as $data => $value) {
                                    $loop_row = $this->commandEvaluation($data, $value, $loop_row);
                                }

                                $this->_cache .= $loop_row;
                            }

                            $this->_template = str_replace($foreach_in_template, $this->_cache, $this->_template);
                        }

                        $this->_cache = '';
                    }
                }

                /* Hodnoty po refreshi */
                $this->value();

                /* Pokud jsou nadefinované nějaké tagy, tak se začne provádět nahrazování */
                if (count($this->_tags) > 0) {
                    foreach ($this->_tags as $tag => $data) {

                        /* Zjistíme, jestli se má nahradit jen první nalezená značka v šabloně pod stejným názvem, nebo všechny nalezené značky v šabloně pod stejným názvem */
                        if ($this->_replaceOne == true) {
                            $this->_template = $this->str_replace_first('{' . $tag . '}', $data, $this->_template);
                        }

                        /* Pro zjištění možného příkazu uvedeného za značkou zavoláme k tomu určenou funkci */
                        $this->_template = $this->commandEvaluation($tag, $data, $this->_template);
                    }
                }

                /* Pokud jsou nadefinované nějaké tagy, tak se začne provádět nahrazování */
                if (count($this->_DBTags) > 0) {
                    foreach ($this->_DBTags as $tags => $tag) {
                        foreach ($tag as $key => $value) {
                            $this->_template = $this->commandEvaluation($key, $value, $this->_template);
                        }
                    }
                }

                //die($this->_template);

                /* Podmínka */
                $this->condition();

                /* Annoucementy */
                $this->annoucementsEvaluate();
            }

            else {

                /* Pokud nebyly do Parseru odeslány žádné tagy */
                die('Chyba: Do Parseru nebyly odeslány žádné data');
            }

            /* Pokud se chyby v šabloně nevyskytují, odstraníme značku {errors} ze šablony, aby nedocházelo k nenalezení tohoto tagu */
            $this->_template = preg_replace('#({errors})#', '', $this->_template);

            /* Chybová hláška, pokud je v šabloně nějaká značka, ke které není ekvivalentní tag */
            $this->_template = preg_replace('#({).*?(})#', ($this->_viewTagError) ? TAG_MISS : '', $this->_template);

            ($view != 1) ? $this->getParsedTemplate() : $this->viewTemplate();

            return $this;
        }

        /**
         * Vyhodnotí příkaz
         * @param string $tag - název tagu zastupující značku v šabloně
         * @param string $data - hodnota tagu zastupující hodnotu značky v šabloně
         * @return string $source - výstup
         */
        protected function commandEvaluation($tag, $data, $source) {

            /* Pokud značka obsahuje příkaz empty="" */
            if (strstr($source, '{' . $tag . '|empty')) {

                /* Vytáhneme text v hodnotě empty="" */
                $empty_string = $this->command($source, '{' . $tag . '|empty="', '"}');

                /* Zjistíme, jestli je tag doopravdy prázdný */
                if (empty($tag)) {

                    /* A přepíšeme značku hodnotou */
                    $source = str_replace('{' . $tag . '|empty="' . $empty_string . '"}', $empty_string, $source);
                }

                else {

                    /* Pokud tag není prázdný, tak značku nahradíme právě tímto tagem */
                    $source = str_replace('{' . $tag . '|empty="' . $empty_string . '"}', $data, $source);
                }
            }

            else if (strstr($source, '{' . $tag . '|date-format')) {
                $format = $this->command($source, '{' . $tag . '|date-format="', '"');

                try {
                    $date = new DateTime($data);
                } catch(Exception $e) {
                    new DreawErrorHandler('Nesprávný formát data');
                }

                $source = str_replace('{' . $tag . '|date-format="' . $format . '"}', $date->format($format), $source);
            }

            else if (strstr($source, '{' . $tag . '|date-ago')) {

                try {
                    $ago = new DreawTimeInterval();
                } catch(Exception $e) {
                    new DreawErrorHandler('Nesprávný formát data');
                }

                $source = str_replace('{' . $tag . '|date-ago}', $ago->intervalString($data), $source);
            }

            else if (strstr($source, '{' . $tag . '|excerpt')) {
                $limit = (int)$this->command($source, '{' . $tag . '|excerpt="', '"');

                if($limit < 3) {
                    $limit = 3;
                }

                if(strlen($data) > $limit) {
                  $content = substr($data, 0, $limit - 3) . ' ...';
                } else {
                  $content = $data;
                }

                $source = str_replace('{' . $tag . '|excerpt="' . $limit . '"}', $content, $source);
            }

            /* Pokud značka obsahuje příkaz if=""replace="" */
            else if (strstr($source, '{' . $tag . '|if="')) {

                /* Vytáhneme text v hodnotě if="" */
                $if_string = $this->command($source, '{' . $tag . '|if="', '"');
                $if_string_replaced = null;

                if (strstr($if_string, '{')) {
                    $getted = $this->command($if_string, '{', '}');

                    $if_string_replaced = $this->simpleTagReplace($getted);
                }

                /* Vytáhneme text v hodnotě replace="" */
                $replace_string = $this->command($source, '{' . $tag . '|if="' . $if_string . '"replace="', '"');

                if (strstr($source, '{' . $tag . '|if="' . $if_string . '"replace="' . $replace_string . '"else="')) {
                    $else_string = $this->command($source, '{' . $tag . '|if="' . $if_string . '"replace="' . $replace_string . '"else="', '"}');

                    $else = 'else="' . $else_string . '"';
                    $else_bool = true;
                }

                else {
                    $else = '';
                    $else_bool = false;
                }

                //print(htmlspecialchars('{' . $tag . '|if="' . $if_string . '"replace="' . $replace_string . '"' . $else . '}', ENT_QUOTES) . '<br />' . $source);

                /* Pokud se hodnota v if="" rovná hodnotě $tag */
                if ($data == $if_string OR $data == $if_string_replaced) {

                    /* Nahradíme značku hodnotou zadanou v replace="" */
                    $source = str_replace('{' . $tag . '|if="' . $if_string . '"replace="' . $replace_string . '"' . $else . '}', $replace_string, $source);
                }

                else {

                    /* Pokud se hodnoty nerovnají, nahradíme značku hodnotou proměnné $tag */

                    $source = str_replace('{' . $tag . '|if="' . $if_string . '"replace="' . $replace_string . '"' . $else . '}', ($else_bool) ? $else_string : $data, $source);
                }

                $source = $this->commandEvaluation($tag, $data, $source);
            }

            $source = str_replace('{' . $tag . '}', $data, $source);

            return $source;
        }

        public function multipleCondition() {
            if (strstr($this->_template, '{multiple-condition}')) {
                $getted_condition = $this->_command($this->_template, '{multiple-condition}', '{multiple-condition}');

                if (strstr($getted_condition, '{if="')) {
                    $main_cond = $this->_command($getted_condition, '{if="', '"');

                }
            }
        }

        /* Využívá se výhradně interně */

        protected function simpleTagReplace($getted_tag) {

            if (count($this->_DBTags > 0)) {
                foreach ($this->_DBTags as $tags => $tag) {
                    foreach ($tag as $key => $value) {
                        if (strstr('{' . $getted_tag . '}', '{' . $key . '}')) {
                            return $value;
                        }
                    }
                }
            }

            if (count($this->_tags) > 0) {
                foreach ($this->_tags as $key => $value) {
                    if (strstr('{' . $getted_tag . '}', '{' . $key . '}')) {
                        return $value;
                    }
                }
            }

            return $getted_tag; // prevent is already replaced
        }

        /**
         * Zajistí připojení ostatních šablon k hlavní šabloně
         * @return bool
         */
        protected function buildTemplate() {
            if(strstr($this->_template, '|include')) {
                $exploded = explode('|include}', $this->_template);

                $reversed = strrev($exploded[0]);
                $reversed = explode('{', $reversed);
                $original = strrev($reversed[0]);

                $getted = file_get_contents($this->_url . '/' .  $original . $this->_suffix);

                $this->_template = str_replace('{' . $original . '|include}', $getted, $this->_template);

                return $this->buildTemplate();
            }

            else {
                return false;
            }
        }

        protected function getMessage() {
            if (strstr($this->_template, '{getMessage}')) {
                $sess = new DreawSession();

                $this->_template = str_replace('{getMessage}', $sess->getMess(), $this->_template);
            }

            return $this;
        }

        /**
         * Do sessionu uloží generovaný token, který následně vrátí jako výsledek ve formátu inputu
         * Tento token se po každém uložení formuláře musí kontrolovat - obrata proti CSFR
         */
        protected function getToken() {
            if (strstr($this->_template, '{getToken}')) {
                $sess = new DreawSession();
                $sess->setSess([
                    'dreaw_token' => sha1(uniqid($sess->getSess('name'), true))
                ]);

                session_regenerate_id();

                $this->_template = str_replace('{getToken}', $sess->getSess('dreaw_token'), $this->_template);
            }

            return $this;
        }

        /* HELP METHOD FOR AUTOMATIC FORM FILLING AFTER REFRESH */

        protected function value() {
            if (strstr($this->_template, '|value}')) {
                $exploded = explode('|value}', $this->_template);

                $reversed = strrev($exploded[0]);
                $reversed = explode('{', $reversed);

                $name = strrev($reversed[0]);

                if (isset($_POST[$name])) {
                    $this->_template = str_replace('{' . $name . '|value}', htmlspecialchars($_POST[$name], ENT_QUOTES), $this->_template);
                    $this->value();
                }

            }

            else if (strstr($this->_template, '|value="')) {
                $exploded = explode('|value="', $this->_template);

                $reversed = strrev($exploded[0]);
                $reversed = explode('{', $reversed);
                $name = strrev($reversed[0]);

                $inner = $this->command($this->_template, '|value="', '"}');

                if ($inner == 'checkbox') {
                    if (isset($_POST[$name])) {
                        $this->_template = str_replace('{' . $name . '|value="' . $inner . '"}', 'checked', $this->_template);
                        unset($_POST[$name]);
                    }
                    $this->_template = str_replace('{' . $name . '|value="' . $inner . '"}', '', $this->_template);
                    $this->value();
                }

                else if($inner = 'option') {
                    if (isset($_POST[$name])) {
                        $this->_template = str_replace('{' . $name . '|value="' . $inner . '"}>' . htmlspecialchars($_POST[$name], ENT_QUOTES) . '</option>', 'selected>' . htmlspecialchars($_POST[$name], ENT_QUOTES) . '</option>', $this->_template);
                        $this->_template = str_replace('{' . $name . '|value="' . $inner . '"}', '', $this->_template);
                    }

                    unset($_POST[$name]);
                    $this->value();
                }
            }

            return $this;
        }

        protected function annoucementsEvaluate() {
            if (strstr($this->_template, '{annoucements_evaluate}')) {
                $full_eval = $this->command($this->_template, '{annoucements_evaluate}', '{/annoucements_evaluate}');

                $exploded = explode('{divider}', $full_eval, -1);

                $full_return = '';

                foreach ($exploded as $key => $value) {
                    $urgency = $this->command($exploded[$key], 'data-urgency="', '"');
                    $text = $this->command($exploded[$key], '>', '</p>');
                    $time = $this->command($exploded[$key], 'data-time="', '"');

                    if ($urgency == '1') {
                        $return = '<div class="panel">
                                        <div class="panel-body no_padd_top">
                                            <div class="row">
                                                <div class="col-sm-9">
                                                    <p class="urgency_normal global_parse">' . $text . '</p>
                                                </div>

                                                <div class="col-sm-3">
                                                    <h5 class="pull-right">
                                                        <i class="fa fa-clock-o fa-1"></i>
                                                        <span class="time">' . $time . '</span>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                    }

                    else if ($urgency == '2') {
                        $return = '<div class="panel">
                                        <div class="panel-body no_padd_top">
                                            <div class="row">
                                                <div class="col-sm-9">
                                                    <p class="urgency_important global_parse">' . $text . '</p>
                                                </div>

                                                <div class="col-sm-3">
                                                    <h5 class="pull-right">
                                                        <i class="fa fa-clock-o fa-1"></i>
                                                        <span class="time">' . $time . '</span>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                    }

                    else if ($urgency == '3') {
                        $return = '<div class="panel">
                                        <div class="panel-body no_padd_top">
                                            <div class="row">
                                                <div class="col-sm-9">
                                                    <p class="urgency_critical global_parse">' . $text . '</p>
                                                </div>

                                                <div class="col-sm-3">
                                                    <h5 class="pull-right">
                                                        <i class="fa fa-clock-o fa-1"></i>
                                                        <span class="time">' . $time . '</span>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                    }

                    $full_return .= $return;
                }

                $this->_template = str_replace($full_eval, $full_return, $this->_template);

                return true;
            }
        }

        /* Podmínka if */

        /* {if="{tag}"|isEmpty}
            ***
            {elseif|isBool}
            *****
            {else}
            ***
            {endif}
        */
        protected $_condition_cache;
        protected $_i = 0;
        protected function condition($source = null) {

            $stock = false;
            $this->_i++;

            if ($source == null) {
                $source = $this->_template;
                $stock = true;
            }

            if (strstr($source, '{endif}')) { // ale to jen tak O :D Kamčo, já jsem rád, že jsi to udělal geniálně, akorát ostatní je v piči :D :D :D Dělám si srandu :D :D No, každopádně, mě volají naši ať jdu za nima, takže tě tu chvíli neechám a pak mi napíšeš na FB jak budeš cokoliv potřebovat nebo bude změna statusu? :D .. hměna jak statusu? změna stavu z V PIči NA funkční ... jasné, řeknu, teď jen pudu na WC, hned jsem tu :D Dobře :) jasan a ahooj :D zatím :D
                $exploded_condition = explode('{endif}', $source);

                $reversed_condition = strrev($exploded_condition[0]);

                $exploded_condition_again = explode('"=fi{', $reversed_condition, 2);
                $condition_for_evaluation = '{if="' . strrev($exploded_condition_again[0]) . '{endif}';

                //die($condition_for_evaluation);

                $if_what =        $this->command($condition_for_evaluation, '{if="', '"|');
                $if_method =      $this->command($condition_for_evaluation, '{if="' . $if_what . '"|', '}');
                $if_condition =   $this->command($condition_for_evaluation, '{if="' . $if_what . '"|' . $if_method . '}', '{else');
                $full_condition = $this->command($condition_for_evaluation, '{if="' . $if_what . '"|' . $if_method . '}', '{endif}');

                $elseif_in_array = $this->getElseif($full_condition . '{endif}');

                if ($this->$if_method($if_what) == true OR $this->$if_method($if_what) == 1) {
                    $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $if_condition, $condition_for_evaluation);

                    if ($stock == true) {
                        $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $if_condition, $condition_for_evaluation);
                        $source = str_replace($condition_for_evaluation, $condition_for_evaluation_replaced, $source);
                        $this->_template = $source;

                        $this->_elseif = null;
                        $this->condition();
                    }

                    else {
                        $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $if_condition, $condition_for_evaluation);
                        $source = str_replace($condition_for_evaluation, $condition_for_evaluation_replaced, $source);

                        return $source;
                    }

                    //$source = str_replace($condition_for_evaluation, $condition_for_evaluation_replaced, $source);
                    //$this->_template = $source;

                    //$this->condition();
                }

                if (is_array($elseif_in_array) AND !empty($elseif_in_array)) {
                    foreach ($elseif_in_array as $array => $values) {
                        if ($this->$values['condition']($if_what) == true OR $this->$values['condition']($if_what) == 1) {

                            $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $values['full'], $condition_for_evaluation);
                        }

                        continue;
                    }
                }

                $this->_elseif = null;

                $exploded = explode('{else}', $full_condition);

                if ($stock == true) {

                    $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $exploded[1], $condition_for_evaluation);
                    $source = str_replace($condition_for_evaluation, $condition_for_evaluation_replaced, $source);
                    $this->_template = $source;

                    $this->_elseif = null;
                    $this->condition();
                }

                else {
                    $condition_for_evaluation_replaced = str_replace($condition_for_evaluation, $exploded[1], $condition_for_evaluation);

                    $source = str_replace($condition_for_evaluation, $condition_for_evaluation_replaced, $source);

                    return $source;
                }
            }

            return $source;
        }

        protected function isInnerCondition($source) {
            if (strstr($source, '{if="')) {
                return true;
            }

            return false;
        }

        protected $_elseif = array();
        protected $_elseif_full;

        protected function getElseif($string) {
            $this->_elseif_full = $string;

            if (strstr($this->_elseif_full, '{elseif|')) {

                $elseif_condition = $this->command($string, '{elseif|', '}');
                $elseif_string    = $this->command($string, '{elseif|' . $elseif_condition . '}', '{');

                $this->_elseif_full = str_replace('{elseif|' . $elseif_condition . '}' . $elseif_string, '', $this->_elseif_full);

                $this->_elseif[] = [
                    'condition' => $elseif_condition,
                    'full'      => $elseif_string
                ];

                $this->getElseif($this->_elseif_full);
            }

            return $this->_elseif;
        }

        protected function isEmpty($what) {
            if (count($this->_DBCycleTags) > 0) {
                if (empty($this->_DBCycleTags[$what])) {
                    return true;
                }

                return false;
            }
            return true;
        }

        protected function isEmptyTag($what) {

            if (strstr($what, '{') && strstr($what, '}')) {
                $what = explode('{', $what);
                $what = explode('}', $what[1]);
                $what = $what[0];
            }

            if (!empty($this->_DBTags[0][$what])) {
                return false;
            }

            return true;
        }

        protected function Urgency($urgency) {
            if ($urgency == '1') {
                return true;
            }

            else if ($urgency == '2') {

            }

            else if ($urgency == '3') {

            }

            else {
                //die('Unexcepted error in urgency!');
            }

            return false;
        }

        protected function beScared($qwe) {
            return false;
        }

        protected function beNormal($qwe) {
            return false;
        }

        protected $_DB;

        protected function visibility($sample_id) {
            $sample_id = $this->simpleTagReplace($sample_id);

            $this->_DB = new DreawDB();

            $this->_DB->query('SELECT id FROM samples WHERE id = :sample_id AND (uid = :uid OR responsible = :uid) LIMIT 1');
            $this->_DB->bind([
                'sample_id' => $sample_id,
                'uid' => $_SESSION['uid_logged']
            ]);
            $this->_DB->execute();
            $visibility_data = $this->_DB->fetchAll();

            if (!empty($visibility_data)) {
                return true;
            }

            return false;
        }

        protected function toSess() {
            if(strstr($this->_template, '|toSess}')) {
                $exploded = explode('|toSess}', $this->_template);

                $reversed = strrev($exploded[0]);
                $reversed = explode('{', $reversed);

                $name = strrev($reversed[0]);
                $value = $this->simpleTagReplace($name);

                if (!empty($value)) {
                    $_SESSION[$name] = $value;
                    $this->_template = str_replace('{' . $name . '|toSess}', '', $this->_template);
                }
                else {
                    $_SESSION[$name] = '';
                    $this->_template = str_replace('{' . $name . '|toSess}', '', $this->_template);
                }

                $this->toSess();
            }

            else if(strstr($this->_template, '|fromSess}')) {
                $exploded = explode('|fromSess}', $this->_template);

                $reversed = strrev($exploded[0]);
                $reversed = explode('{', $reversed);

                $name = strrev($reversed[0]);

                if (isset($_SESSION[$name])) {
                    $this->_template = str_replace('{' . $name . '|fromSess}', $_SESSION[$name], $this->_template);
                }

                else {
                    $this->_template = str_replace('{' . $name . '|fromSess}', 'nope', $this->_template);
                }

                $this->toSess();
            }

            else {
                return false;
            }
        }

        protected static $_increment_start;
        protected static $_actual_number = 0;

        protected function numInc($source) {
            if (self::$_actual_number < 1) {
                if (strstr($source, '|valueIncrement}')) {
                    $exploded = explode('|valueIncrement', $source);

                    $reversed = strrev($exploded[0]);
                    $reversed = explode('{', $reversed);

                    $value = strrev($reversed[0]);

                    self::$_increment_start = (int)$value;

                    $source = str_replace('{' . $value . '|valueIncrement}', self::$_actual_number, $source);
                }

            }

            $source = str_replace('{' . self::$_increment_start . '|valueIncrement}', self::$_actual_number, $source);
            self::$_actual_number++;

            return $source;
        }

        /**
         * Přidá do parseru tagy k nahrazení
         * @param array $tags - tagy k nahrazení
         * @return None
         */
        public function addTags($tags) {
            $this->_tags = array_map(array($this, 'attackProtection'), $tags);

            return $this;
        }

        /**
         * Přidá do parseru k nahrazení tagy z DB
         * @param array $tags - tagy k nahrazení
         * @return None
         */
        public function addDBTags($tags) {
            foreach ($tags as $keys => $values) {
                foreach ($values as $key => $value) {
                    $this->_DBTags[$keys][$key] = $this->attackProtection($value);
                }
            }

            return $this;
        }

        /**
         * Přidá výpis tagů k nahrazení z DB
         * @param array DBCycleTags
         * @return None
         */
        public function addDBCycleTags($tags) {
            foreach ($tags as $keys => $values) {
                foreach ($values as $key => $value) {
                    foreach ($value as $key_str => $value_str) {
                        $this->_DBCycleTags[$keys][$key][$key_str] = $this->attackProtection($value_str);
                    }
                }
            }

            return $this;
        }

        /**
         * Zjistí hodnotu v dynamickém příkazu
         * @param string $string - řetězec, kde se má hledaný výraz vyskytovat
         * @param string $start - znak/y před hledaným výrazem
         * @param string $end - znak/y za hledaným výrazem
         * @return string - hledaný výraz
         */
        protected function command($string, $start, $end) {
            $string = ' ' . $string;
            $ini = strpos($string, $start);

            if ($ini == 0) return '';

            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;

            return substr($string, $ini, $len);
        }

        /**
         * Nahradí v šabloně jen první značku stejného názvu jako tag, metoda použita kvůli cyklickému výpisu
         * @param string $search - hledaný řetězec k nahrazení
         * @param string $replace - řetězec, který bude nahrazen za řetězec $search
         * @param string $subject - šablona/Kus šablony, ve které se bude opearce provádět
         * @return string $subject - nahrazená/ý šablona/kus šablony
         */
        protected function str_replace_first($search, $replace, $subject) {
            $pos = strpos($subject, $search);

            if ($pos !== false) {
                $subject = substr_replace($subject, $replace, $pos, strlen($search));
            }

            return $subject;
        }

        protected function attackProtection($unsafe) {
            return htmlspecialchars($unsafe, ENT_QUOTES);
        }

        /**
        * Vrátí nahrazenou šablonu
        * @return string
        */
        public function viewTemplate() {
            print $this->_template;
        }

        public function getParsedTemplate() {
            return $this->_template;
        }

        /* #################################### DEBUG #################################### */

        public $parseTime;
        public $fullTime;

        public function debugStart () {
                $this->parseTime = microtime(true);
        }

        public function debugEnd () {
                die('<p><strong>Doba potřebná k vykreslení parserem: ' . number_format(microtime(true) - $this->parseTime, 5) . ' vteřin při celkových (' . count($this->_DBTags + $this->_DBCycleTags, COUNT_RECURSIVE) . ' záznamech)</strong></p>');
        }
    }
?>