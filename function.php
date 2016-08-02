<?php
/*
Plugin Name: WP SC Survey
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: This describes my plugin in a short sentence
Version:     1.0.0
Author:      John Smith
Author URI:  http://URI_Of_The_Plugin_Author
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: quize-quize
 */
define(SCSURVEY_NAME, 'WP SC Survey');
define(SCSURVEY_TITLE, 'Survey');
define(SCSURVEY_TITLE_P, 'Surveys');
define(SCSURVEY_SLUG, 'wp-sc-survey');
define(SCSURVEY_VERSION, '1.0.0');
define(SCSURVEY_PATH, plugin_dir_path(__FILE__));
define(SCSURVEY_URI, plugin_dir_url(__FILE__));
define(REMOVE_TABLE_ON_DEACTIVE, false);
if (!class_exists('ScSurvey')) {
    require SCSURVEY_PATH . '/inc/extfunc.php';
    class ScSurvey
    {
        public $svdb;
        public $svadmin;
        private $registerSchema  = array();
        private $resticted_pages = array();
        private $_nonce          = 'survey_page_resticted';
        private $nonce;
        private $pageCookie    = array();
        const PAGE_LIST_COOKIE = 'ALLOWED_PAGES';
        public function ScSurvey()
        {
            $this->svadmin           = new SurveyAdminHandler();
            $this->registerSchema[0] = new SurveyTblSchema();
            $this->registerSchema[1] = new SurveyQuestionAns();
            $this->nonce             = wp_create_nonce($this->_nonce);
            if (isset($_COOKIE[self::PAGE_LIST_COOKIE])) {
                $this->pageCookie = array_filter(json_decode($_COOKIE[self::PAGE_LIST_COOKIE], true));
            }
            $this->init($this->registerSchema[0]);
            add_shortcode(SCSURVEY_SLUG, array($this, 'wp_sc_survey'));
        }

        protected function init($survey)
        {

            $survey_lists = $survey->all()->last_result;
            foreach ($survey_lists as $survey_list) {
                if ($this->is_survey_page($survey_list->result_txt)) {
                    $this->resticted_pages[] = $survey_list->result_txt;
                }
            }
            add_action('wp', array($this, 'force_404_survey_pages'));
            add_action('init', array($this, '_page_init_'));
        }

        public function _page_init_()
        {
            if (!isset($_GET['survey_result']) && !isset($_GET['survey'])) {
                return null;
            }

            $result = $this->survey_result_decreption($_GET['survey_result']);
            if (wp_verify_nonce($result['nonce'], $this->_nonce)) {
                $page_id = $this->registerSchema[0]->getSurveyPageByResult($_GET['survey'], $result['result']);
                if (array_search($page_id, $this->pageCookie) === false) {
                    $this->pageCookie[] = $page_id;
                }

                if (!isset($_COOKIE[self::PAGE_LIST_COOKIE])) {
                    $path = parse_url(get_option('siteurl'), PHP_URL_PATH);
                    $host = parse_url(get_option('siteurl'), PHP_URL_HOST);
                    setcookie(self::PAGE_LIST_COOKIE, json_encode($this->pageCookie), 0, $path, $host);
                } else {
                    $_COOKIE[self::PAGE_LIST_COOKIE] = json_encode($this->pageCookie);
                }
                $next = get_permalink($page_id);
                wp_redirect($next);
                die();
            }
        }

        private function is_survey_page($id)
        {
            if (is_numeric($id) && !empty(get_post($id)) && get_post($id)->post_type = "page") {
                return true;
            }
            return false;
        }

        public function force_404_survey_pages()
        {
            global $wp_query;
            if (in_array($wp_query->post->ID, $this->resticted_pages) && !in_array($wp_query->post->ID, $this->pageCookie)) {
                $wp_query->set_404();
                status_header(404);
            }
        }

        public function plugin_active()
        {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            foreach ($this->registerSchema as $tbl) {
                dbDelta($tbl->generateTblSql());
            }

        }

        public function plugin_deactivation()
        {
            if (!REMOVE_TABLE_ON_DEACTIVE) {
                return null;
            }

            global $wpdb;
            $droplist = array();
            foreach ($this->registerSchema as $tbl) {
                $droplist[] = $tbl->distroyTbl();
            }

            $sql = "DROP TABLE " . implode(' , ', array_reverse($droplist));
            $wpdb->query($sql);
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        public function wp_sc_survey($atts)
        {
            extract(shortcode_atts(array(
                'style'              => 'ui',
                'title'              => '0',
                'survey'             => -1,
                'start_button'       => "Begin Survey",
                'question_title_tag' => "h3",
            ), $atts));
            if (isset($_GET['survey_id']) && !empty($_GET['survey_id'])) {
                $survey = $_GET['survey_id'];
            }

            if ($survey == -1) {
                return null;
            }
            $survey_id = $survey;

            $sv     = $this->registerSchema[0];
            $survey = $sv->findID($survey);
            if (!$survey) {
                return false;
            }

            $outHTML = '';
            if ($survey->state == "inactive" || (!empty($survey->inactive) && strtotime('today') > strtotime($survey->inactive))) {
                if (!empty($survey->inactive_txt)) {
                    $outHTML = sprintf('<div class="%s">%s</div>', 'survey survey-inactive', wpautop(htmlspecialchars_decode($survey->inactive_txt)));
                }
                return $outHTML;
            }
            $surveySettings = json_decode($survey->settings, true);
            $outHTML        = sprintf('<h1 class="%s"><span>%s</span></h1>', 'survey-title', $survey->title);
            $outHTML        = sprintf('<div class="%s">%s</div>', 'survey-head', $outHTML);
            if (isset($_GET['survey_id']) && !empty($_GET['survey_id'])) {
                $qAns    = $sv->surveyQuestions($survey, $this->registerSchema[1]);
                $qansTxt = '';
                if ($qAns) {
                    foreach ($qAns as $qa) {
                        $answers    = $this->prepareAnswer($qa->ans_list, $qa->ans_show);
                        $ansFullTxt = '';
                        if (!empty($answers)) {
                            $ansTxt = '';
                            $i=1;
                            foreach ($answers as $ans) {
                                $ans  = (object) $ans;
                                $mark=$qa->ans_show=="rating"?$i++:$ans->murk;
                                $type = ($qa->multi_ans == "no") ? 'radio' : 'checkbox';
                                $id   = 'ans' . $qa->id . '-' . $ans->id;
                                $name = 'answer[' . $qa->id . '][]';
                                $ansTxt .= sprintf('<p><input id="%s" value="%s" type="%s" name="%s"/><label for="%s">%s</label></p>', $id,$mark, $type, $name, $id, $ans->title);
                            }
                            $ansFullTxt .= sprintf('<div class="%s">%s</div>', 'answers question-' . $qa->id, $ansTxt);
                        }
                        $qansTxt .= sprintf('<div class="%s"><%s class="%s">Q: <span class="%s">%s</span></%s>%s</div>', 'question', $question_title_tag, 'q-title', 'q-title-span', $qa->title, $question_title_tag, $ansFullTxt);
                    }
                    $qansTxt = sprintf('<div class="%s">%s</div>', 'questions question-list', $qansTxt);
                }
                $outHTML = sprintf('%s <div class="%s">%s</div>', $outHTML, 'survey-questions', $qansTxt);
                if ($this->is_survey_page($survey->result_txt)) { 
                    $url = '?survey_result=' . $this->survey_result_encreption(56);
                    $url .= '&survey=' . $survey_id;
                }
                $outHTML = sprintf('%s <a id="survey-finish-button" href="%s" class="%s">%s</a>', $outHTML, $url, 'survey-finish-button', $surveySettings['finish-button-txt']);
                return sprintf('<div class="%s">%s</div>', 'survey survey-' . $survey_id, $outHTML);
            }
            if ($survey->short_desc) {
                $outHTML = sprintf('%s <div class="%s">%s</div>', $outHTML, 'sort-description', $survey->short_desc);
            }

            if ($survey->intor_txt) {
                $outHTML = sprintf('%s <div class="%s">%s</div>', $outHTML, 'intro-text', wpautop(htmlspecialchars_decode($survey->intor_txt)));
            }
            $outHTML = sprintf('%s <a class="%s" href="?survey_id=%s">%s</a>', $outHTML, 'button button-primary', $survey->id, $start_button);
            return sprintf('<div class="%s">%s</div>', 'survey survey-' . $survey_id, $outHTML);
        }
        protected function prepareAnswer($ansJson, $ans_type)
        {
            $answers = json_decode($ansJson, true);
            if ($ans_type == 'default') {
                return $answers;
            } elseif ($ans_type == 'random') {
                $newans = array();
                foreach ($answers as $answer) {
                    $id       = array_rand($answers);
                    $newans[] = $answers[$id];
                    unset($answers[$id]);
                }
                return $newans;
            }

        }
        private function survey_result_encreption($number, $percent = false)
        {
            $code = $this->nonce . $number;
            if ($percent) {
                return urlencode("%" . md5($code));
            }

            return md5($code);
        }
        private function survey_result_decreption($code, $full_marks = 100, $percent = false)
        {
            if ($percent) {
                $full_marks = 100;
                $code       = str_replace("%", "", urldecode($code));
            }
            for ($i = 0; $i <= $full_marks; $i++) { 
                if ($code == md5($this->nonce . $i)) {
                    $out = array('nonce' => $this->nonce, 'result' => $i);
                    return $out;
                }
            }
            return false;
        }
    }
    $scsv = new ScSurvey();
    register_activation_hook(__FILE__, array($scsv, 'plugin_active'));
    register_deactivation_hook(__FILE__, array($scsv, 'plugin_deactivation'));
}
