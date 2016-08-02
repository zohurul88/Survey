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
function datepicker($name,$label='Untitled',$value="",$container=null,$data=null,$ui=null)
{
	textbox($name,$label,$value,false,$container,$data,'datepicker '.$ui);
}

function textbox($name,$label='Untitled',$value="",$multiline=false,$container=null,$data=null,$ui=null)
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
	do_action('before_texteditor_'.$name);
	wp_editor( 
		htmlspecialchars_decode($value), 
		$name , 
		array('textarea_name'=> $name)
		);
	do_action('after_texteditor_'.$name);
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
add_action("before_texteditor_result_txt",'another_option_before_reslut_box');
function another_option_before_reslut_box()
{
    $pages = get_pages();
    $option='';
    foreach ($pages as $page) {
        $option.=sprintf('<option value="%s">%s</option>',$page->ID,$page->post_title);
    }
    printf('<div class="result-page"><select id="result_page" name="result_page"><option value="0">--select one--</option>%s</select></div>',$option);
    printf('<div style="margin-bottom:15px;"></div>');
    ?>
    <script>
        jQuery(document).ready(function($){
            $(document).on("change","#result_page",function(){
                if($(this).val()!=0)
                {
                    $("#wp-result_txt-wrap").fadeOut();
                }
                else
                {
                     $("#wp-result_txt-wrap").fadeIn();
                }
            });
        });
    </script>
    <?php 
}

function survey_remove_txt_add_page($req)
{
    if(!empty($req['result_page'])) $req['result_txt']=$req['result_page'];
    unset($req['result_page']);
    return $req;
}

/*
<input checked="" id="show-question-one" type="radio" value="no" name="show-question">
<label for="show-question-one">One Page</label><input id="show-question-mul"  type="radio" value="yes" name="show-question"><label for="show-question-mul">Multiple Page</label> 
					</div>*/