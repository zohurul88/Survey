<?php 
 
	class AjaxHandler{

		private $qa; 
		private $errorList=array();
		private $hasError=false;
		private $jsonList=false;

		function __construct(){
			$this->qa=new SurveyQuestionAns();
		}

		private function errorPush($message,$target=null)
		{
			$this->errorList[]=array('msg'=>$message,'target'=>$target);
			$this->hasError=true;
		}


		private function jsonPush($index,$data)
		{
			$this->jsonList[$index]=$data;
			$this->hasError=false;
		}

		function addNewQuestion($req)
		{

		}


		function json()
		{
			$array=array('status'=>false);
			if($this->hasError)
			{
				$array['error']=$this->errorList;
			}
			else
			{
				$array['status']=true;
				$array['response']=$this->jsonList;
			}
			$this->hasError=false;
			$this->errorList=array();
			$this->jsonList=array();
			return json_encode($array);
		}

		function saveQuestions($req)
		{
			if(empty($req->title)){
				$this->errorPush('Title Missing','.q-title');
				echo $this->json();
				return ;
			}
			$qa=$this->qa;
			if(isset($req->random))
			{
				$arg=array(
					$qa->title=>$req->title,
					$qa->q_desc=>$req->desc, 
					$qa->sid=>$req->sid,
					$qa->multi_ans=>$req->multi_ans,
					$qa->ans_show=>$req->show_as,
					$qa->ans_list=>json_encode(array())
				);
				$arg[$qa->q_order]=($req->order==-1)?1:$req->order;
				$result=$qa->newQuestions($arg);
				if($result->rows_affected)
				{
					$this->jsonPush('action','save');
					$this->jsonPush('html',$this->addAnswerHTML());
					echo $this->json();
				}else
				{
					$this->errorPush($result->last_error);
					echo $this->json();
				}
			}else{
				$result=$qa->question($req->qid); 
				if(!$result->num_rows)
				{
					$this->errorPush('There is some error occur! please refresh page and try again!');
					echo $this->json();
					return;
				} 
				$result=$result->last_result[0];
				$data=array();
				if($req->title!=$result->title) $data[$qa->title]=$req->title;
				if($req->desc!=$result->q_desc) $data[$qa->q_desc]=$req->desc;
				if($req->multi_ans!=$result->multi_ans) $data[$qa->multi_ans]=$req->multi_ans;
				if($req->show_as!=$result->ans_show) $data[$qa->ans_show]=$req->show_as;
				if($req->order!=$result->q_order) $data[$qa->q_order]=$req->order;
				//if($req->ans_list!=$result->ans_list) $data[$qa->ans_list]=$req->ans_list;
				$up=$qa->updateQuestions($data,$req->qid);
				if($up->rows_affected)
				{
					$thisQ=$qa->question($req->qid);
					$this->jsonPush('action','update');
					$this->jsonPush('question',(array)$thisQ->last_result[0]);
					if($thisQ->hasAnswer())
					{
						$this->jsonPush('html',$this->answerList());
						$this->jsonPush('answers',json_decode($thisQ->last_result[0]->ans_list,true));
					}else{
						$this->jsonPush('html',$this->addAnswerHTML());
					}
					echo $this->json();
					return;
				}else 
				{
					$this->errorPush('Nothing Change!');
					echo $this->json();
					return;
				}
			}
		}

		function addAnswerHTML($inier_html)
		{
			return '<ul class="answ"'; 
		}

		//function fun

		function addQuestionHTML($req)
		{
			$rand_id=rand(00000,99999);
			echo '<div><li class="question collapse">
								<form id="qa-'.$rand_id.'" action="post" data-qid="rand">
									<input name="qid" type="hidden" value="'.$rand_id.'" /> 
									<input name="sid" class="survey-id" type="hidden" value="" /> 
									<input name="order" class="question-order" type="hidden" value="" /> 
									<input name="response" type="hidden" value="json" />';
									wp_nonce_field('_survey-question','token');
				echo '<input type="hidden" name="random" value="'.$rand_id.'" />
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
								</li></div>';
		}
	}


	class AjaxRequestHandler{
		private $request;
		private $sv;
		private $qa;
		const ajaxHeader='survey-question';
		const _ajaxHeader='_survey-question';

		function __construct($sv,$qa)
		{ 
			add_action( 'wp_ajax_question-form', array($this,'addQuestionHTML'));
			add_action( 'wp_ajax_save-question', array($this,'saveQuestions'));
			$this->request=$_REQUEST; 
			$this->sv=$sv;
			$this->qa=$qa;
		}

		function __call($name,$arg)
		{
			check_ajax_referer('_survey-question', 'token');
			$ajax=new AjaxHandler();
			if(method_exists($ajax, $name)) 
				{
					if(isset($this->request['response']) && $this->request['response']=='json') 
						header("Content-Type: text/json");
					else  header("Content-Type: text/html");
					$ajax->$name((object)$this->request,$this->sv,$this->qa);
				}
			die();
		}

	}