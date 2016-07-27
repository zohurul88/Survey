<?php 
	 
	 class SurveyAdminHandler{

	 	private $submenu_slug='-new';
	 	private $menu_slug=SCSURVEY_SLUG;
	 	private $var_slug=SCSURVEY_SLUG;
	 	private $page_title='New Survey';
	 	protected $settings;
	 	protected $nonce=SCSURVEY_SLUG.'new-survey-nonce';
	 	protected $sv;
	 	protected $qa;
	 	protected $ajax; 
	 	protected $current_survey; 

	 	protected $need_hook=array();

	 	function SurveyAdminHandler()
	 	{
	 		$this->var_slug=str_replace("-", "_", $this->menu_slug);
	 		$this->sv=new SurveyTblSchema();
	 		$this->qa=new SurveyQuestionAns();
	 		$this->ajax=new AjaxRequestHandler($this->sv,$this->qa);
	 		$this->submenu_slug=SCSURVEY_SLUG.$this->submenu_slug;
	 		$this->current_survey=isset($_GET['survey'])?$_GET['survey']:null;
	 		$this->init();
	 		$this->saveSurvey($this->sv);
			add_action( 'admin_menu', array($this,'qz_register_settings_page'));
			add_action( 'admin_enqueue_scripts', array($this,'admin_requiered_scripts') );
	 	}
	 	function init()
	 	{
	 		//Default Settings
	 		$this->settings=array( 
	 			'show-question'=>'no',
	 			'question-order'=>'custom',
	 			'question-per-page'=>'',
	 			'answer-order'=>'default',
	 			'show-intro'=>'yes',
	 			'result-global'=>'no',
	 			'show-title'=>'yes',
	 			'show-desc'=>'yes',
	 			'finish-button-txt'=>'Finish Survey'
	 		);
	 		if(!empty($this->current_survey)) 
	 			$this->page_title=$this->initSurveyData($this->current_survey)->title; 
	 	}

	 	function saveSurvey($sv)
	 	{
	 		$this->hook_before_insert($sv);
			$request=$this->requestProcess($_POST,$sv);
			if($request) {  
					$obj=$sv->save(array_filter($request),$this->current_survey);
					if($obj->insert) 
						wp_redirect(survey_url($this->submenu_slug.'&action=questions&survey='.$obj->insert));
				}
	 	}

	 	function admin_requiered_scripts()
	 	{
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_register_script( SCSURVEY_SLUG.'-scripts', SCSURVEY_URI.'/assets/js/admin-scripts.js', true, SCSURVEY_VERSION);
			wp_enqueue_script( SCSURVEY_SLUG.'-scripts' ); 
			$localize=array('no_once'=>wp_create_nonce('_survey-question'),'ajax_url'=>admin_url('admin-ajax.php'));
			wp_localize_script( SCSURVEY_SLUG.'-scripts', 'local', $localize);

			wp_register_style( SCSURVEY_SLUG.'-style', SCSURVEY_URI.'/assets/css/admin-style.css', true, SCSURVEY_VERSION);
			wp_enqueue_style( SCSURVEY_SLUG.'-style' );
	 	}

	 	function qz_register_settings_page() {
		    add_menu_page(
		        __( SCSURVEY_NAME, SCSURVEY_SLUG ),
		        'Survey',
		        'manage_options',
		        SCSURVEY_SLUG,
		        array($this,'qz_register_settings_page_html'),
		        SCSURVEY_URI.'/images/icon.png',
		        6
		    );
			add_submenu_page( 
				SCSURVEY_SLUG, 
				$this->page_title,
				'New Survey',
				'manage_options',
				$this->submenu_slug,
				array($this,'survey_callback')
			);
		}
		function survey_callback()
		{
			if(!isset($_GET['action'])) 
				{
					$this->new_survey_callback();
					return ;
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

		function qz_register_settings_page_html()
		{
			$sv=$this->sv;
			include_once(SCSURVEY_PATH.'/inc/template/survey.php');
		}

		function contentEncode($content)
		{ 
			return htmlspecialchars($content);
		}

		function dateFilter($date)
		{
			if(empty($date)) return null;
			return (string)date("Y-m-d H:i:s",strtotime($date));
		}


		function update_survey_callback()
		{
			$this->new_survey_callback($this->current_survey);
		}

		function hook_before_insert($sv)
		{
			$this->need_hook[$sv->intor_txt]=array($this,'contentEncode');
			$this->need_hook[$sv->result_txt]=array($this,'contentEncode');
			$this->need_hook[$sv->inactive_txt]=array($this,'contentEncode'); 
			$this->need_hook[$sv->active]=array($this,'dateFilter'); 
			$this->need_hook[$sv->inactive]=array($this,'dateFilter'); 
		}

		function initSurveyData($id=null)
		{
			$nsvObj=new SurveyTblSchema();
			$nsvObj->findID($id);;
			if($nsvObj->settings==0) $nsvObj->settings=$this->settings;
			else $nsvObj->settings=(array) $nsvObj->settings;
			if(!empty($nsvObj->active))$nsvObj->active=date('m/d/y',strtotime($nsvObj->active));
			if(!empty($nsvObj->inactive))$nsvObj->inactive=($nsvObj->inactive!='0000-00-00')?date('m/d/y',strtotime($nsvObj->inactive)):null;
			return $nsvObj;
		}


		function add_survey_questions()
		{
			if(empty($this->current_survey)) return ;
			$qa=$this->qa; 
			$sv=$this->sv;  
			$data=$this->initSurveyData($this->current_survey);
			?>
			<div class="survey col-full <?php echo __FUNCTION__; ?> new-survey-area">
				<div id="" class="postbox ">
					<h2 class="sv-title"><strong>Survey Set: </strong><span><?php echo $data->{$sv->title} ?></span>
					<span class=""><a id="question-form" href="#question-form" class="button button-primary right">Add a Question</a></span></h2>
						<div class="inside">
							<h3>Question List</h3>
							<ul id="questions" class="question-lists" data-survey_id="<?php echo $this->current_survey; ?>">
								<?php include_once(SCSURVEY_PATH.'/inc/template/question.php'); ?>
							</ul>
						</div>
				</div>			
			</div>
			<?php
		}


		function new_survey_callback($id=null)
		{
			$sv=$this->sv;
			?> 
<div class="survey col-full <?php echo __FUNCTION__ ?> new-survey-area">
	<form action="" method="post">
		<?php 
			$data=$this->initSurveyData($id);
			wp_nonce_field($this->nonce,'_'.$this->nonce);
		 ?>
		<div class="three-fourth">
		<?php 
			textbox($sv->title,array('title'=>'New Survey','class'=>'sc-title'),$data->{$sv->title});
			textbox($sv->short_desc,'Short Descriptions',$data->{$sv->short_desc},true);
			texteditor($sv->intor_txt,'Introduction Content',$data->{$sv->intor_txt},'condition open intro-content');
			texteditor($sv->result_txt,'Thank You / Result Content',$data->{$sv->result_txt},'condition result-content '.(!empty($data->{$sv->result_txt})?'open':''));
			texteditor($sv->inactive_txt,'Inactive Content',$data->{$sv->inactive_txt},'condition inactive-content '.(!empty($data->{$sv->inactive_txt})?'open':''));
		?>
		</div>
		<div class="one-fourth survey-aside">
			<div id="" class="postbox ">
			<h2 class="hndle"><span>Survey Settings</span></h2>
				<div class="inside">
					<?php  
						radioList('show-question','Show Questions in',array('no'=>'One Page','yes'=>'Multiple Page'),$data->settings['show-question'],'cond-chekbox','data-target="question-per-page"');
						textbox('question-per-page','Questions Per Page',$data->settings['question-per-page'],false,'condition question-per-page no-control'.(!empty($data->settings['question-per-page'])?' open':''));
						//Questions Order
						radioList('question-order','Questions Order',array('custom'=>'Custom','random'=>'Random'),$data->settings['question-order']);
						//Answer Shows as
						radioList('answer-order','Answer Shows as',
							array('default'=>'Default','rating'=>'Rating','random'=>'Random'),$data->settings['answer-order']);
						//Show Introduction
						radioList('show-intro','Show Introduction',array('yes'=>'Yes','no'=>'No'),$data->settings['show-intro'],'cond-chekbox','data-target="intro-content"');
						//Show Global Result
						radioList('result-global','Show Global Result',array('yes'=>'Yes','no'=>'No'),$data->settings['result-global'],'cond-chekbox','data-target="result-content"');
						//Show Title
						radioList('show-title','Show Title',array('yes'=>'Yes','no'=>'No'),$data->settings['show-title']);
						//Show Short Descriptions
						radioList('show-desc','Show Short Descriptions',array('yes'=>'Yes','no'=>'No'),$data->settings['show-desc']);
						textbox('finish-button-txt','Finished Button Text',$data->settings['finish-button-txt']);
						datepicker($sv->active,'Active Date',$data->{$sv->active});
						datepicker($sv->inactive,'Deactivate Date',$data->{$sv->inactive},'cond-textbox',' data-target="inactive-content"');
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

		protected function requestProcess($req,$parent=null)
		{
			require_once(ABSPATH .'wp-includes/pluggable.php');
			if(!isset($req['_'.$this->nonce]) || !wp_verify_nonce( $req['_'.$this->nonce], $this->nonce)) return false; 
			unset($req['_'.$this->nonce]);unset($req['_wp_http_referer']); 
			$settings=array();
			foreach ($this->settings as $setting=>$val) {
				$settings[$setting]=$req[$setting];
				unset($req[$setting]);
			}
			$req[$parent->settings]=json_encode($settings);
			foreach ($this->need_hook as $field => $func) {
				$req[$field]=call_user_func_array($func, array($req[$field]));
			}
			return $req;
		}

	 }

/*
<li class="question">
								<form id="qa-<?php echo $rand_id; ?>" action="post" data-qid="<?php echo !empty($id)?$id:'rand'; ?>">
									<input name="qid" type="hidden" value="<?php echo $rand_id; ?>" /> 
									<input name="sid" type="hidden" value="<?php echo $this->current_survey; ?>" /> 
									<input name="order" type="hidden" value="-1" /> 
									<input name="response" type="hidden" value="json" /> 
									<?php wp_nonce_field('_survey-question','token'); ?>
									<?php if(empty($id)): ?>
										<input type="hidden" name="random" value="<?php echo $rand_id; ?>" />
									<?php endif; ?> 
										<input type="hidden" class="data-action" name="action" value="" />
									<div class="question-head">
										<h3 class="question-title"><span>Untitle</span></h3>
										<div class="button-group">
											<button data-action="edit-question" type="button" class="question-action q-edit">Edit</button>
											<button data-action="remove-question" class="question-action q-remove">Remove</button>
											<button type="button" class="q-collapse">Collapse</button>
										</div>
									</div>
									<div class="q-section">
										<div class="ajax-inside">
										<div class="q-inside">   
												<p>
												<input placeholder="Question Title" class="q-textbox q-title" name="title" type="text">
												</p>
												<p>
													<textarea placeholder="Question Description" class="" name="desc"></textarea>
												</p>
												<button data-action="save-question" type="button" data-target="qa-<?php echo $rand_id; ?>" class="button button-primary right question-action">Save & Add Answer</button> 
												<div class="clear"></div>
											</div>
											<!--<ul id="answer-1" class="answer-list">
												<li class="answer">
													<div class="answer-head">
														<h3 class="answer-title"><span>Untitle</span></h3>
														<div class="button-group">
															<button data-action="edit-answer" type="button" class="answer-action q-edit">Edit</button>
															<button data-action="remove-answer" class="answer-action q-remove">Remove</button>
															<button type="button" class="q-collapse">Collapse</button>
														</div>
													</div>
												</li>
											</ul>-->
										</div>
										<!--<div class="q-inside">   
												<p>
												<input placeholder="Question Title" class="q-textbox q-title" name="title" type="text">
												</p>
												<p>
													<textarea placeholder="Question Description" class="" name="desc"></textarea>
												</p>
												<button data-action="save-question" type="button" data-target="qa-<?php echo $rand_id; ?>" class="button button-primary right question-action">Save & Add Answer</button> 
												<div class="clear"></div>
											</div>-->
										<div class="actions">
											<span class="left">
												<span><strong>Show As: </strong></span>
												<span><input checked name="show_as" id="show-as-default" value="default" type="radio"><label for="show-as-default">Default</label></span>
												<span><input name="show_as" id="show-as-rating" value="rating" type="radio"><label for="show-as-rating">Rating</label></span>
												<span><input name="show_as" id="show-as-random" value="random" type="radio"><label for="show-as-random">Random</label></span>
											</span>
											<span class="right">
												<span><strong>Multiple Answer: </strong></span>
												<span><input checked name="multi_ans" id="multi-ans-no" value="no" type="radio"><label for="multi-ans-no">No</label></span>
												<span><input name="multi_ans" id="multi-ans-yes" value="yes" type="radio"><label for="multi-ans-yes">Yes</label></span>
											</span>
										</div>
									</div>	
								</form>
								</li>
*/