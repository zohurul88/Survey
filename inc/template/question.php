<?php 
	$questions=$qa->surveyQuestions($this->current_survey);
	foreach($questions as $question):
	//pr($question);
	$id=$question->id; 
	$rand_id=rand(00000,99999);
	$hasAnswer=$qa->question($question->id)->hasAnswer();
	?>
<li class="question <?php echo $hasAnswer==true?'has-answer':''; ?>">
		<form id="qa-<?php echo empty($id)?$rand_id:$id; ?>" action="post" data-qid="<?php echo !empty($id)?$id:'rand'; ?>">
			<input name="qid" type="hidden" value="<?php echo empty($id)?$rand_id:$id; ?>" /> 
			<input name="sid" class="survey-id" type="hidden" value="<?php echo $this->current_survey; ?>" /> 
			<input name="order" class="question-order" type="hidden" value="<?php echo empty($id)?$rand_id:-1; ?>" /> 
			<input name="response" type="hidden" value="json" /> 
			<?php wp_nonce_field('_survey-question','token'); ?>
			<?php if(empty($id)): ?>
				<input type="hidden" name="random" value="<?php echo $rand_id; ?>" />
			<?php endif; ?> 
			<input type="hidden" class="data-action" name="action" value="" />
			<div class="question-head">
				<h3 class="question-title">
					<span><?php echo !empty($question->title)?$question->title:'Untitle' ?></span>
				</h3>
				<div class="button-group">
					<button type="button" data-action="edit-question" class="question-action button q-edit">Edit</button>
					<button type="button" data-action="remove-question" class="question-action button q-remove">Remove</button>
					<button type="button" class="button q-collapse">Collapse</button>
				</div>
			</div>
			<div class="q-section">
				<div class="ajax-inside"> 
					<!--<div class="q-inside">   
						<p>
						<input placeholder="Question Title" class="q-textbox q-title" value="<?php echo $question->title; ?>" name="title" type="text">
						</p>
						<p>
							<textarea placeholder="Question Description" class="" name="desc"><?php echo $question->q_desc; ?></textarea>
						</p>
						<button data-action="save-question" type="button" data-target="qa-<?php echo $rand_id; ?>" class="button button-primary right question-action">Update & Add Answer</button> 
						<div class="clear"></div>
					</div>--> 
					<h4> <span>Answer List</span> </h4>
						<ul class="answer-list">
						<?php if($hasAnswer): ?>
							<li class="answer">
							<div class="answer-head">
							<h3 class="answer-title"> <span>First Answer</span> </h3>
							<div class="button-group">
								<button data-action="edit-answer" type="button" class="answer-action answer-edit">Edit</button>
								<button data-action="remove-answer" class="answer-action answer-remove">Remove</button>
								<button type="button" class="answer-collapse">Collapse</button>
							</div>
							</div>
							<div class="answer-inside">
								<div class="form-control"><input type="text" name="name"></div>
								<div class="form-control"><input type="text" name="murk"></div>
							</div>
						</li>
					<?php endif; ?>
					</ul>
				<button class="button button-primary new-answer answer-action" data-action="new-form-request" type="button">+New Question</button>
				</div>
				<div class="actions">
					<span class="left">
						<span><strong>Show As: </strong></span>
						<?php 
						$show_as=array('default'=>'Default','rating'=>'Rating', 'random'=>'Random');
						foreach($show_as as $index=>$show):
							$checked=($question->ans_show==$index)?'checked=="checked"':'';
						 ?>
						<span><input <?php echo $checked; ?> name="show_as" id="show-<?php echo $question->id; ?>-<?php echo $index; ?>" value="<?php echo $index; ?>" type="radio"><label for="show-<?php echo $question->id; ?>-<?php echo $index; ?>"><?php echo $show; ?></label></span>
					<?php endforeach; ?>
					</span>
					<span class="right">
						<span><strong>Multiple Answer: </strong></span>
						<?php 
						$show_as=array('no'=>'No','yes'=>'Yes');
						foreach($show_as as $index=>$mans):
							$checked=($question->multi_ans==$index)?'checked=="checked"':'';
						 ?>
						<span><input <?php echo $checked; ?> name="multi_ans" id="multi-<?php echo $question->id.'-'.$index; ?>" value="<?php echo $index; ?>" type="radio"><label for="multi-<?php echo $question->id.'-'.$index; ?>"><?php echo $mans; ?></label></span> 
					<?php endforeach; ?>
					</span>
				</div>
			</div>	
		</form>
</li>
<?php endforeach; ?>