<?php 

function allQuestionLoop($questions,$obj){
foreach($questions as $question)
	{
		printf('<li class="question q-item item-%s" data-qid="%s"><span>%s</span><div class="q-action"><a class="question-edit" data-qid="%s" href="#edit-quistion">Edit</a> | <a class="question-rmv" href="#rmv-quistion" data-qid="%s">Remove</a><div></li>',$question[$obj->id],$question[$obj->id],$question[$obj->question],$question[$obj->id],$question[$obj->id]);
	}
}

function __autoload($name)
{
	$ignorelist=array('AjaxRequestHandler','MasterDB','SurveyAdminHandler','SurveyQuestionAns','SurveyTblSchema');
	if(!in_array($name,$ignorelist)) return ;
	$name=strtolower($name);
	require_once(SCSURVEY_PATH.'/inc/'.$name.'.php');
}
if(!function_exists('p_r')){
function p_r($a)
{
	echo "<pre>";
	foreach(func_get_args() as $a)print_r($a);
	echo "</pre>";
}
}

function getClassVars($cls,$type=null)
{
	if(!class_exists($cls))return false;
		$ref = new ReflectionClass($cls);
	if($type==1 || $type=="public" || $type==null)
		$return=$ref->getProperties(ReflectionProperty::IS_PUBLIC);
	elseif($type==2 || $type=="protected")
		$return=$ref->getProperties(ReflectionProperty::IS_PROTECTED);
	else return flase;
	$out= new stdClass();
	foreach($return as $value) {
		$out->{$value->name}=$value->name;
	}
	return $out;
}
// sc-title
function datepicker($name,$label='Untitle',$value=null,$container=null,$data=null,$ui=null)
{
	textbox($name,$label,$value,false,$container,$data,'datepicker '.$ui);
}

function textbox($name,$label='Untitle',$value=null,$multiline=false,$container=null,$data=null,$ui=null)
{
	if(empty($name)) return null; 
	$textbox='';
	if($label)
	{
		$lbcls=(is_array($label) && isset($label['class']))?'label '.$label['class'] : 'label';
		$title=(is_array($label) && isset($label['title']))? $label['title'] : $label;
		$textbox.=sprintf('<label class="%s" for="%s">%s</label>',$lbcls,$name,$title);
	} 
	if(!$multiline) $textbox.=sprintf('<input id="%s" name="%s" type="text" class="%s" value="%s" />',$name,$name,$ui,$value);
	else $textbox.=sprintf('<textarea id="%s" name="%s" type="text" class="%s">%s</textarea>',$name,$name,$ui,$value);
	if($container!==false) 
	{
		$container='control '.$container;
		$textbox=sprintf('<div class="%s" %s >%s</div>',$container,$data,$textbox);
	}
	echo $textbox;
}

function texteditor($name,$label='Untitle',$value=null,$container=null,$data=null)
{
	if(empty($name)) return null; 
	$textbox='';
	if($container!==false) 
	{
		$container='control editor '.$container;
		printf('<div class="%s" %s ><div id="post-%s" class="postbox ">',$container,$data,$name);
	}
	if($label!==false)
	{
		$lbcls=(is_array($label) && isset($label['class']))?'label '.$label['class'] : 'label';
		$title=(is_array($label) && isset($label['title']))? $label['title'] : $label;
		printf('<h2 class="hndle %s" id="lb-%s"><span>%s</span></h2>',$lbcls,$name,$title);
	} 

	echo '<div class="inside">';
	wp_editor( 
		htmlspecialchars_decode($value), 
		$name , 
		array('textarea_name'=> $name)
		);
	echo '</div>';
	if($container!==false) echo '</div></div>';	 
}
function radioList($name,$label='Untitle',$list=array(),$val=null,$container=null,$data=null)
{
	if($container!==false){
		$container='control '.$container;
		printf('<div class="%s" %s>',$container,$data);
	}
	if($label!==false)
	{
		$lbcls=(is_array($label) && isset($label['class']))?'label '.$label['class'] : 'label';
		$title=(is_array($label) && isset($label['title']))? $label['title'] : $label;
		printf('<span class="%s">%s</span>',$lbcls,$title);
	} 
	if(!empty($list))
	{
		foreach ($list as $key => $lbl) {
			$checked=($key==$val)?'checked="checked"':null;
			$id=$name.'-'.$key;
			printf('<input %s id="%s" type="radio" value="%s" name="%s"><label for="%s">%s</label>',$checked,$id,$key,$name,$id,$lbl);
		}
	}
	if($container!==false) echo '</div>';
}

function survey_url($ext)
{
	 return admin_url('admin.php?page='.$ext);
}

/*
<input checked="" id="show-question-one" type="radio" value="no" name="show-question">
<label for="show-question-one">One Page</label><input id="show-question-mul"  type="radio" value="yes" name="show-question"><label for="show-question-mul">Multiple Page</label> 
					</div>*/