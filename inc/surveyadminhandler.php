<?php

class SurveyAdminHandler
{

    private $submenu_slug = '-new';
    private $menu_slug    = SCSURVEY_SLUG;
    private $var_slug     = SCSURVEY_SLUG;
    private $page_title   = 'New Survey';
    protected $settings;
    protected $nonce = SCSURVEY_SLUG . 'new-survey-nonce';
    protected $sv;
    protected $qa;
    protected $ajax;
    protected $current_survey;

    protected $need_hook = array();

    public function SurveyAdminHandler()
    {
        $this->var_slug       = str_replace("-", "_", $this->menu_slug);
        $this->sv             = new SurveyTblSchema();
        $this->qa             = new SurveyQuestionAns();
        $this->ajax           = new AjaxRequestHandler($this->sv, $this->qa);
        $this->submenu_slug   = SCSURVEY_SLUG . $this->submenu_slug;
        $this->current_survey = isset($_GET['survey']) ? $_GET['survey'] : null;
        $this->init();
        $this->saveSurvey($this->sv);
        add_action('admin_menu', array($this, 'qz_register_settings_page'));
        add_action('admin_init', array($this, 'priority_checking'));
        add_action('admin_enqueue_scripts', array($this, 'admin_requiered_scripts'));
    }
    public function init()
    {
        //Default Settings
        $this->settings = array(
            'show-question'     => 'no',
            'question-order'    => 'custom',
            'question-per-page' => '',
            'answer-order'      => 'default',
            'show-intro'        => 'yes',
            'result-global'     => 'no',
            'show-title'        => 'yes',
            'show-desc'         => 'yes',
            'finish-button-txt' => 'Finish Survey',
        );

        if (!empty($this->current_survey)) {
            $this->page_title = $this->initSurveyData($this->current_survey)->title;
        }

    }

    public function priority_checking()
    {
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'changestate') {
                if ($this->sv->flipState($this->current_survey)) {
                    wp_redirect(survey_url($this->menu_slug));
                }

            } elseif ($_GET['action'] == 'delete') {
                if ($this->sv->removeSurvey($this->current_survey)) {
                    wp_redirect(survey_url($this->menu_slug));
                }

            }
        }
    }

    public function saveSurvey($sv)
    {
        $this->hook_before_insert($sv);
        $request = $this->requestProcess($_POST, $sv);
        if ($request) {
            $obj = $sv->save(array_filter($request), $this->current_survey);
            if ($obj->insert) {
                wp_redirect(survey_url($this->submenu_slug . '&action=questions&survey=' . $obj->insert));
            }

        }
    }

    public function admin_requiered_scripts()
    {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_register_script(SCSURVEY_SLUG . '-scripts', SCSURVEY_URI . '/assets/js/admin-scripts.js', true, SCSURVEY_VERSION);
        wp_enqueue_script(SCSURVEY_SLUG . '-scripts');
        $localize = array('no_once' => wp_create_nonce('_survey-question'), 'ajax_url' => admin_url('admin-ajax.php'));
        wp_localize_script(SCSURVEY_SLUG . '-scripts', 'local', $localize);

        wp_register_style(SCSURVEY_SLUG . '-style', SCSURVEY_URI . '/assets/css/admin-style.css', true, SCSURVEY_VERSION);
        wp_enqueue_style(SCSURVEY_SLUG . '-style');
    }

    public function qz_register_settings_page()
    {
        add_menu_page(
            __(SCSURVEY_NAME, SCSURVEY_SLUG),
            'Survey',
            'manage_options',
            SCSURVEY_SLUG,
            array($this, 'qz_register_settings_page_html'),
            SCSURVEY_URI . '/images/icon.png',
            6
        );
        add_submenu_page(
            SCSURVEY_SLUG,
            $this->page_title,
            'New Survey',
            'manage_options',
            $this->submenu_slug,
            array($this, 'survey_callback')
        );
    }
    public function survey_callback()
    {
        if (!isset($_GET['action'])) {
            $this->new_survey_callback();
            return;
        }
        switch ($_GET['action']) {
            case 'edit':
                $this->update_survey_callback();
                break;

            case 'questions':
                $this->add_survey_questions();
                break;

            default:
                $this->new_survey_callback();
                break;
        }
        return $func;
    }

    public function qz_register_settings_page_html()
    {
        $sv = $this->sv;
        include_once SCSURVEY_PATH . '/inc/template/survey.php';
    }

    public function contentEncode($content)
    {
        return htmlspecialchars($content);
    }

    public function dateFilter($date)
    {
        if (empty($date)) {
            return null;
        }

        return (string) date("Y-m-d H:i:s", strtotime($date));
    }

    public function update_survey_callback()
    {
        $this->new_survey_callback($this->current_survey);
    }

    public function hook_before_insert($sv)
    {
        $this->need_hook[$sv->intor_txt]    = array($this, 'contentEncode');
        $this->need_hook[$sv->result_txt]   = array($this, 'contentEncode');
        $this->need_hook[$sv->inactive_txt] = array($this, 'contentEncode');
        $this->need_hook[$sv->active]       = array($this, 'dateFilter');
        $this->need_hook[$sv->inactive]     = array($this, 'dateFilter');
    }

    public function initSurveyData($id = null)
    {
        $nsvObj = new SurveyTblSchema();
        $nsvObj->findID($id);
        if (empty($nsvObj->last_result)) {
            $nsvObj->title        = "";
            $nsvObj->id           = "";
            $nsvObj->title        = "";
            $nsvObj->short_desc   = "";
            $nsvObj->state        = "";
            $nsvObj->inactive_txt = "";
            $nsvObj->result_txt   = "";
            $nsvObj->intor_txt    = "";
        }
        if ($nsvObj->settings == 0) {
            $nsvObj->settings = $this->settings;
        } else {
            $nsvObj->settings = (array) $nsvObj->settings;
        }

        if (!empty($nsvObj->active)) {
            $nsvObj->active = date('m/d/y', strtotime($nsvObj->active));
        }

        if (!empty($nsvObj->inactive)) {
            $nsvObj->inactive = ($nsvObj->inactive != '0000-00-00') ? date('m/d/y', strtotime($nsvObj->inactive)) : null;
        }

        return $nsvObj;
    }

    public function add_survey_questions()
    {
        if (empty($this->current_survey)) {
            return;
        }

        $qa   = $this->qa;
        $sv   = $this->sv;
        $data = $this->initSurveyData($this->current_survey);
        ?>
			<div class="survey col-full <?php echo __FUNCTION__; ?> new-survey-area">
				<div id="" class="postbox ">
					<h2 class="sv-title"><strong>Survey Set: </strong><span><?php echo $data->{$sv->title} ?></span>
					<span class=""><a id="question-form" href="#question-form" class="button button-primary right">Add a Question</a></span></h2>
						<div class="inside">
							<h3>Question List</h3>
							<ul id="questions" class="question-lists" data-survey_id="<?php echo $this->current_survey; ?>">
								<?php include_once SCSURVEY_PATH . '/inc/template/question.php';?>
							</ul>
							<a id="question-form-1" href="#question-form" class="button button-primary">Add a Question</a>
							<div class="clear"></div>
						</div>
				</div>
			</div>
			<?php
}

    public function new_survey_callback($id = null)
    {
        $sv = $this->sv;
        ?>
<div class="survey col-full <?php echo __FUNCTION__ ?> new-survey-area">
	<form action="" method="post">
		<?php
$data = $this->initSurveyData($id);
        wp_nonce_field($this->nonce, '_' . $this->nonce);
        ?>
		<div class="three-fourth">
		<?php
textbox($sv->title, array('title' => 'New Survey', 'class' => 'sc-title'), $data->{$sv->title});
        textbox($sv->short_desc, 'Short Descriptions', $data->{$sv->short_desc}, true);
        texteditor($sv->intor_txt, 'Introduction Content', $data->{$sv->intor_txt}, 'condition open intro-content');
        texteditor($sv->result_txt, 'Thank You / Result Content', $data->{$sv->result_txt}, 'condition result-content ' . (!empty($data->{$sv->result_txt}) ? 'open' : ''));
        texteditor($sv->inactive_txt, 'Inactive Content', $data->{$sv->inactive_txt}, 'condition inactive-content ' . (!empty($data->{$sv->inactive_txt}) ? 'open' : ''));
        ?>
		</div>
		<div class="one-fourth survey-aside">
			<div id="" class="postbox ">
			<h2 class="hndle"><span>Survey Settings</span></h2>
				<div class="inside">
					<?php
radioList('show-question', 'Show Questions in', array('no' => 'One Page', 'yes' => 'Multiple Page'), $data->settings['show-question'], 'cond-chekbox', 'data-target="question-per-page"');
        textbox('question-per-page', 'Questions Per Page', $data->settings['question-per-page'], false, 'condition question-per-page no-control' . (!empty($data->settings['question-per-page']) ? ' open' : ''));
        //Questions Order
        radioList('question-order', 'Questions Order', array('custom' => 'Custom', 'random' => 'Random'), $data->settings['question-order']);
        //Answer Shows as
        radioList('answer-order', 'Answer Shows as',
            array('default' => 'Default', 'rating' => 'Rating', 'random' => 'Random'), $data->settings['answer-order']);
        //Show Introduction
        radioList('show-intro', 'Show Introduction', array('yes' => 'Yes', 'no' => 'No'), $data->settings['show-intro'], 'cond-chekbox', 'data-target="intro-content"');
        //Show Global Result
        radioList('result-global', 'Show Global Result', array('yes' => 'Yes', 'no' => 'No'), $data->settings['result-global'], 'cond-chekbox', 'data-target="result-content"');
        //Show Title
        radioList('show-title', 'Show Title', array('yes' => 'Yes', 'no' => 'No'), $data->settings['show-title']);
        //Show Short Descriptions
        radioList('show-desc', 'Show Short Descriptions', array('yes' => 'Yes', 'no' => 'No'), $data->settings['show-desc']);
        textbox('finish-button-txt', 'Finished Button Text', $data->settings['finish-button-txt']);
        datepicker($sv->active, 'Active Date', $data->{$sv->active});
        datepicker($sv->inactive, 'Deactivate Date', $data->{$sv->inactive}, 'cond-textbox', ' data-target="inactive-content"');
        ?>
				</div>
			</div>
		</div>
				<div class="clear"></div>
				<div class="col-full">
					<button type="submit" class="button button-primary">Save And Proccess</button>
				</div>
				</form>
			</div>
			<?php
}

    protected function requestProcess($req, $parent = null)
    {
        require_once ABSPATH . 'wp-includes/pluggable.php';
        if (!isset($req['_' . $this->nonce]) || !wp_verify_nonce($req['_' . $this->nonce], $this->nonce)) {
            return false;
        }

        unset($req['_' . $this->nonce]);unset($req['_wp_http_referer']);
        $settings = array();
        foreach ($this->settings as $setting => $val) {
            $settings[$setting] = $req[$setting];
            unset($req[$setting]);
        }
        $req[$parent->settings] = json_encode($settings);
        foreach ($this->need_hook as $field => $func) {
            $req[$field] = call_user_func_array($func, array($req[$field]));
        }
        return $req;
    }

}