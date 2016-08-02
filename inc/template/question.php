<?php
$questions = $qa->surveyQuestions($this->current_survey, "ASC");
foreach ($questions as $question):
    //pr($question);
    $id        = $question->id;
    $rand_id   = rand(00000, 99999);
    $hasAnswer = $qa->question($question->id)->hasAnswer();
    //var_dump($hasAnswer);
    ?>
			<li data-previndex="<?php echo $question->q_order; ?>" data-qid="<?php echo $id; ?>" class="question <?php echo $hasAnswer == true ? 'has-answer' : ''; ?>">
					<form id="qa-<?php echo empty($id) ? $rand_id : $id; ?>" action="post" data-qid="<?php echo !empty($id) ? $id : 'rand'; ?>">
						<input name="qid" class="inp-qid" type="hidden" value="<?php echo empty($id) ? $rand_id : $id; ?>" />
						<input name="sid" class="survey-id" type="hidden" value="<?php echo $this->current_survey; ?>" />
						<input name="order" class="question-order" type="hidden" value="<?php echo $question->q_order; ?>" />
						<input name="response" type="hidden" value="json" />
						<?php wp_nonce_field('_survey-question', 'token');?>
						<?php if (empty($id)): ?>
							<input type="hidden" name="random" value="<?php echo $rand_id; ?>" />
						<?php endif;?>
			<input type="hidden" class="data-action" name="action" value="" />
			<div class="question-head">
				<h3 class="question-title" data-title="<?php echo $question->title; ?>"><span class="drag-point">Drag</span>
					<span class="title-span"><?php echo $id;
echo " ";
echo !empty($question->title) ? $question->title : 'Untitle' ?></span>
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
						<ul class="answer-list" data-qid="<?php echo $question->id; ?>">
						<?php
						if ($hasAnswer):
						$answers=$qa->sortAnswerList(json_decode($question->ans_list,true)); 
					//p_r($answers);
						foreach($answers as $answer):
							$answer=(object) $answer;
						?>
							<li id="answer<?php echo $question->id.'-'.$answer->id; ?>" class="answer" data-qid="<?php echo $question->id; ?>" data-answer="<?php echo $answer->id; ?>" data-murk="<?php echo $answer->murk; ?>" data-order="<?php echo $answer->order; ?>" data-title="<?php echo htmlspecialchars($answer->title); ?>">
					    <div class="answer-head">
					        <div class="ans-drag-point">
					            Drag <?php echo $answer->id; ?> 
					        </div>
					        <div class="answer-inside">
					            <div class="form-control">
					                <input class="answer-textbox" value="<?php echo htmlspecialchars($answer->title); //str_replace('"',"'",$answer->title); ?>" name="name" placeholder="Answer Title" type="text"/>
					            </div>
					            <div class="form-control small-control">
					                <input class="answer-murk" value="<?php echo $answer->murk; ?>" name="mark" placeholder="Answer Marks" type="text"/>
					            </div>
					        </div>
					        <div class="button-group">
					            <button class="button button-primary save-answer answer-action" data-action="save-answer" type="button">
					                Save
					            </button>
					            <button class="answer-action answer-remove button" data-action="remove-answer">
					                Remove
					            </button>
					        </div>
					    </div>
					</li>
					<?php endforeach; endif;?>
					</ul>
				<button class="button button-primary new-answer answer-action" data-action="new-form-request" type="button">+New Answer</button>
				</div> 
			<div class="actions">
					<span class="left">
						<span><strong>Show As: </strong></span>
						<?php
$show_as = array('default' => 'Default', 'rating' => 'Rating', 'random' => 'Random');
foreach ($show_as as $index => $show):
    $checked = ($question->ans_show == $index) ? 'checked=="checked"' : '';
    ?>
									<span><input <?php echo $checked; ?> name="show_as" id="show-<?php echo $question->id; ?>-<?php echo $index; ?>" value="<?php echo $index; ?>" type="radio"><label for="show-<?php echo $question->id; ?>-<?php echo $index; ?>"><?php echo $show; ?></label></span>
								<?php endforeach;?>
					</span>
					<span class="right">
						<span><strong>Multiple Answer: </strong></span>
						<?php
$show_as = array('no' => 'No', 'yes' => 'Yes');
foreach ($show_as as $index => $mans):
    $checked = ($question->multi_ans == $index) ? 'checked=="checked"' : '';
    ?>
									<span><input <?php echo $checked; ?> name="multi_ans" id="multi-<?php echo $question->id . '-' . $index; ?>" value="<?php echo $index; ?>" type="radio"><label for="multi-<?php echo $question->id . '-' . $index; ?>"><?php echo $mans; ?></label></span>
								<?php endforeach;?>
					</span>
					<span>
						<button data-action="save-question" data-response="notupdateanswer" type="button" data-target="qa-<?php echo $question->id; ?>" class="button button-primary right question-action">Update</button>
					</span>
				</div>
		</form>
</li>
<?php endforeach;?>