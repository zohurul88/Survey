<?php

class AjaxHandler
{

    private $qa;
    private $errorList = array();
    private $hasError  = false;
    private $jsonList  = false;

    public function __construct()
    {
        $this->qa = new SurveyQuestionAns();
    }

    private function errorPush($message, $target = null)
    {
        $this->errorList[] = array('msg' => $message, 'target' => $target);
        $this->hasError    = true;
    }

    private function jsonPush($index, $data)
    {
        $this->jsonList[$index] = $data;
        $this->hasError         = false;
    }

    public function addNewQuestion($req)
    {

    }

    public function json()
    {
        $array = array('status' => false);
        if ($this->hasError) {
            $array['error'] = $this->errorList;
        } else {
            $array['status']   = true;
            $array['response'] = $this->jsonList;
        }
        $this->hasError  = false;
        $this->errorList = array();
        $this->jsonList  = array();
        return json_encode($array);
    }

    public function saveQuestions($req)
    {
        $qa = $this->qa;
        if (isset($req->random)) {
            if (empty($req->title)) {
                $this->errorPush('Title Missing', '.q-title');
                echo $this->json();
                return;
            }
            $arg = array(
                $qa->title     => $req->title,
                $qa->q_desc    => $req->desc,
                $qa->sid       => $req->sid,
                $qa->multi_ans => $req->multi_ans,
                $qa->ans_show  => $req->show_as,
                $qa->ans_list  => json_encode(array()),
            );
            $arg[$qa->q_order] = ($req->order == -1) ? 1 : $req->order;
            $result            = $qa->newQuestions($arg);
            if ($result->rows_affected) {
                $question = $qa->question($result->insert);
                $this->jsonPush('action', 'save');
                $this->jsonPush('html', $this->addInitAnswerHTML(null,$result->insert));
                $this->jsonPush('question', (array) $question->last_result[0]);
                $this->jsonPush('msg', 'New Question Added Successfully');
            } else {
                $this->errorPush($result->last_error);
            }
            echo $this->json();
            return;
        } else {
            $result = $qa->question($req->qid);
            if (!$result->num_rows) {
                $this->errorPush('There is some error occur! please refresh page and try again!');
                echo $this->json();
                return;
            }
            $result = $result->last_result[0];
            $data   = array();
            if ($req->title != $result->title && !empty($req->title)) {
                $data[$qa->title] = $req->title;
            }

            if ($req->desc != $result->q_desc  && !empty($req->title)) {
                $data[$qa->q_desc] = $req->desc;
            }

            if ($req->multi_ans != $result->multi_ans) {
                $data[$qa->multi_ans] = $req->multi_ans;
            }

            if ($req->show_as != $result->ans_show) {
                $data[$qa->ans_show] = $req->show_as;
            }

            if ($req->order != $result->q_order) {
                $data[$qa->q_order] = $req->order;
            }

            //if($req->ans_list!=$result->ans_list) $data[$qa->ans_list]=$req->ans_list;
            $up = $qa->updateQuestions($data, $req->qid);
            if ($up->rows_affected) {
                $thisQ = $qa->question($req->qid);
                $this->jsonPush('action', 'update');
                $this->jsonPush('question', (array) $thisQ->last_result[0]);
                if ($thisQ->hasAnswer()) {
                    $this->jsonPush('html', $this->answerList($thisQ->last_result[0]->ans_list), $req->qid); //$this->answerList()
                    $this->jsonPush('answers', json_decode($thisQ->last_result[0]->ans_list, true));
                    $this->jsonPush('has-answer', true);
                    $this->jsonPush('msg', 'Question Updated Successfully');
                } else {
                    $this->jsonPush('html', $this->addInitAnswerHTML(null,$req->qid));
                    $this->jsonPush('has-answer', false);
                    $this->jsonPush('msg', 'Question Updated Successfully');
                }
                echo $this->json();
                return;
            } else {
                $this->jsonPush('action', 'nochange');
                $this->jsonPush('msg', 'No Change Made!');
                echo $this->json();
                return;
            }
        }
    }

    public function questionOrderChanges($req)
    {
        $updatlist = array();
        foreach ($req->order as $qid => $order) {
            $up = $this->qa->updateOrder($qid, $order);
            if ($up->rows_affected) {
                $updatlist[$qid] = true;
            }

        }
        $this->jsonPush('update', $updatlist);
        $this->jsonPush('action','order-updated');
        $this->jsonPush('msg','Questions Order Updated');
        echo $this->json();
    }

    public function removeQuestion($req)
    {
        $qa  = $this->qa;
        $rmv = $qa->removeQuestion($req->qid);
        if ($rmv->rows_affected) {
            $this->jsonPush('action', 'remove');
            $this->jsonPush('msg', 'Questions Successfully Remove');
            echo $this->json();
        } else {
            $this->errorPush('Question Not Deleted. Last Error: ' . $rmv->last_error);
            echo $this->json();
        }
    }

    public function editFormHtml($data)
    {
        return '<div class="q-inside">
                <p>
                    <input value="' . $data->title . '" placeholder="Question Title" class="q-textbox q-title" name="title" type="text">
                </p>
                <p>
                    <textarea placeholder="Question Description" class="" name="desc">' . $data->q_desc . '</textarea>
                </p>
                <button data-action="save-question" type="button" data-target="qa-' . $data->id . '" class="button button-primary right question-action">Update</button>
                <div class="clear"></div>
            </div>';
    }

    public function editFormRequest($req)
    {
        $data = $this->qa->question($req->qid)->last_result[0];
        $this->jsonPush('action', 'edit');
        $this->jsonPush('html', $this->editFormHtml($data));
        echo $this->json();
    }

    public function addQuestionHTML($req)
    {
        $rand_id = rand(00000, 99999);
        echo '<div><li class="question disable-sort-item collapse" data-previndex="" data-qid="rand">
                                <form id="qa-' . $rand_id . '" action="post" data-qid="rand">
                                    <input name="qid" class="inp-qid" type="hidden" value="' . $rand_id . '" />
                                    <input name="sid" class="survey-id" type="hidden" value="" />
                                    <input name="order" class="question-order" type="hidden" value="" />
                                    <input name="response" type="hidden" value="json" />';
        wp_nonce_field('_survey-question', 'token');
        echo '<input type="hidden" class="inp-random" name="random" value="' . $rand_id . '" />
                                        <input type="hidden" class="data-action" name="action" value="" />
                                    <div class="question-head">
                                        <h3 class="question-title" data-title=""><span class="drag-point">Drag</span><span class="title-span">Untitle</span></h3>
                                        <div class="button-group">
                                            <button data-action="edit-question" disabled="" type="button" class="button question-action q-edit">Edit</button>
                                            <button type="button" data-action="remove-question" class="button remove-unsaved-question q-remove">Remove</button>
                                            <button type="button" class="button q-collapse">Collapse</button>
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
                                                <button data-action="save-question" type="button" data-target="qa-' . $rand_id . '" class="button button-primary right question-action">Save & Add Answer</button>
                                                <div class="clear"></div>
                                            </div>
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
                                            <span>
                                            <button data-action="save-question" data-response="notupdateanswer" type="button" data-target="qa-' . $rand_id . '" class="button button-primary right question-action">Update</button>
                                            </span>
                                         
                                    </div>
                                </form>
                                </li></div>';
    }
    public function answerList($answers)
    {
        $answerHTML = '';
        $answers    = $this->qa->sortAnswerList(json_decode($answers, true));
        foreach ($answers as $answer) {
            $answer = (object) $answer;
            $answerHTML .= '<li id="answer' . $question->id . '-' . $answer->id . '" class="answer" data-qid="' . $question->id . '" data-answer="' . $answer->id . '" data-murk="' . $answer->murk . '" data-order="' . $answer->order . '" data-title="' . $answer->title . '"><div class="answer-head"><div class="ans-drag-point">Drag</div><div class="answer-inside"><div class="form-control"><input class="answer-textbox" value="' . str_replace('"', "'", $answer->title) . '" name="name" placeholder="Answer Title" type="text"/></div><div class="form-control small-control"><input class="answer-murk" value="' . $answer->murk . '" name="murk" placeholder="Answer marks" type="text"/></div></div><div class="button-group"><button class="button button-primary save-answer answer-action" data-action="save-answer" type="button">Save</button><button class="answer-action answer-remove button" data-action="remove-answer">Remove</button></div></div></li>';
        }
        return $answerHTML;
    }

    public function answerFormHTML($req)
    {
        $this->jsonPush('action', 'new-answer');
        $this->jsonPush('html', '<div><li id="answer-' . rand(0000, 9999) . '" class="answer new-answer-unsaved" data-qid="' . $req->qid . '" data-answer="rand" data-murk="0" data-order="0" data-title=""><div class="answer-head"><div class="ans-drag-point">Drag</div><div class="answer-inside"><div class="form-control"><input class="answer-textbox" name="name" placeholder="Answer Title" type="text"/></div><div class="form-control small-control"><input class="answer-murk" name="murk" placeholder="Answer Mark" type="text"/></div></div><div class="button-group"><button class="button button-primary save-answer answer-action" data-action="save-answer" type="button">Save</button><button class="answer-action answer-remove button" data-action="remove-answer">Remove</button></div></div></li></div>');
        echo $this->json();
    }

    public function addInitAnswerHTML($inier_html = null, $qid = null)
    {
        return '<h4><span>Answer List</span> </h4><ul data-qid="' . $qid . '" class="answer-list">' . $inier_html . '</ul><button class="button button-primary new-answer answer-action" data-action="new-form-request" type="button">+New Question</button>';
    }

    public function saveAnswer($req)
    {
        $qa = $this->qa;
        if (empty($req->dataset['qid'])) {
            $this->errorPush("No question has been selected!");
            echo $this->json();
            return;
        }
        $answerList = array();
        $question   = $qa->question($req->dataset['qid']);
        if ($question->hasAnswer()) {
            $answerList = json_decode($question->last_result[0]->ans_list, true);
        }
        $count = count($answerList);
        $count++;
        $currentAnswer = ($req->dataset['answer'] == "rand") ? $count : $req->dataset['answer'];
        if ($req->dataset['answer'] == "rand") {

            $answerList[$currentAnswer] = $qa->newAnswerFormat(
                array(
                    $currentAnswer,
                    $req->dataset['title'],
                    $req->dataset['murk'],
                    $req->dataset['order'],
                )
            );
            $this->jsonPush('action', 'answer-save');
            $this->jsonPush('msg', 'New Answer Added');
        } elseif (isset($answerList[$currentAnswer])) {
            $answerList[$currentAnswer] = $qa->newAnswerFormat(array(
                $currentAnswer,
                $req->dataset['title'],
                $req->dataset['murk'],
                $req->dataset['order'],
            ));
            $this->jsonPush('action', 'answer-update');
            $this->jsonPush('msg', 'Answer Updated ');
        } else {
            $this->errorPush("No Answer found to update");
            echo $this->json();
            return;
        }

        $up = $qa->updateQuestions(array($qa->ans_list => json_encode($answerList)), $req->dataset['qid']);
        if ($up->rows_affected) {
            $this->jsonPush('answer', $answerList[$currentAnswer]);  
            echo $this->json();
        } else {
            $this->jsonPush('action', 'answer-nochange');
            $this->jsonPush("No Change Made!");
            echo $this->json();
        }
    }

    public function updateAnswerOrder($req)
    {
        $qa       = $this->qa;
        $question = $qa->question($req->qid);
        if ($question->hasAnswer()) {
            $answers = json_decode($question->last_result[0]->ans_list, true);
            foreach ($req->order as $ans => $new_order) {
                $answers[$ans]['order'] = $new_order;
            }
            $up = $qa->updateQuestions(array($qa->ans_list => json_encode($answers)), $req->qid);
            if ($up->rows_affected) {
                $this->jsonPush('action', 'order-updated');
                echo $this->json();
            } else {
                $this->errorPush("Not Update Ordering" . $up->last_query);
                echo $this->json();
            }
        }
    }

    public function removeAnAnswer($req)
    {
        if ($this->qa->removeAnswer($req->dataset['answer'], $req->dataset['qid'])) {
            $this->jsonPush('action', 'ans-remove');
            $this->jsonPush('msg', 'Answer removed from question');
            $this->jsonPush('remove-id', $req->dataset['answer']);
        } else {
            $this->errorPush("No Answer Removed!");
        }
        echo $this->json();
    }
}

class AjaxRequestHandler
{
    private $request;
    private $sv;
    private $qa;
    const ajaxHeader  = 'survey-question';
    const _ajaxHeader = '_survey-question';

    public function __construct($sv, $qa)
    {
        add_action('wp_ajax_question-form', array($this, 'addQuestionHTML'));
        add_action('wp_ajax_save-question', array($this, 'saveQuestions'));
        add_action('wp_ajax_remove-question', array($this, 'removeQuestion'));
        add_action('wp_ajax_edit-question', array($this, 'editFormRequest'));
        add_action('wp_ajax_new-form-request', array($this, 'answerFormHTML'));
        add_action('wp_ajax_order-question-changed', array($this, 'questionOrderChanges'));
        add_action('wp_ajax_save-answer', array($this, 'saveAnswer'));
        add_action('wp_ajax_order-answer-changed', array($this, 'updateAnswerOrder'));
        add_action('wp_ajax_remove-answer', array($this, 'removeAnAnswer'));
        $this->request = $_REQUEST;
        $this->sv      = $sv;
        $this->qa      = $qa;
    }

    public function __call($name, $arg)
    {
        check_ajax_referer('_survey-question', 'token');
        $ajax = new AjaxHandler();
        if (method_exists($ajax, $name)) {
            if (isset($this->request['response']) && $this->request['response'] == 'json') {
                header("Content-Type: text/json");
            } else {
                header("Content-Type: text/html");
            }

            $ajax->$name((object) $this->request, $this->sv, $this->qa);
        } else {echo 'Method not found! :(';}
        die();
    }

}
