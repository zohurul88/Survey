jQuery(document).ready(function($){
	questionDraggable();
	answerShortable();
function answerShortable()
{
	$( ".sortable" ).sortable({
	 start: function(event, ui) {
			console.log(ui.item.index())
		},
		update: function (event, ui) {
		    console.log("Up:"+ui.item.index())
		},
	});
} 
function questionDraggable(){
$(".question" ).draggable({
	revert : function(event, ui) { 
        var orginalPos=$( '.question[data-qid="'+ $(this).data('qid')+'"]').data("uiDraggable").position;
        $(this).data("uiDraggable").position = {
            top : orginalPos.top,
            left : orginalPos.left
        }; 
        return !event; 
    },
    helper: function(e){
    	 var original = $(e.target).hasClass("ui-draggable") ? $(e.target) :  $(e.target).closest(".ui-draggable");
    	 var cloneNode=original.clone();
    		cloneNode.html(original.find('span').html()); 
	    return cloneNode.css({
	      width: original.width(),
	      zIndex: 999
	    });    
    },
    cancel: '.nodrag'
});
}
$( "#answer" ).droppable({
  hoverClass: 'ui-state-active',
  accept: '.question',
  drop: function (event, ui) {
      var $this = $(this),
      curId=ui.draggable.data('qid');
      console.log(); 
      ui.draggable.prevAll().removeClass("nodrag active");
      ui.draggable.nextAll().removeClass("nodrag active");
      ui.draggable.addClass("nodrag active");
      $("#add-answer").show();
      $("#answers-save").data('qid',curId);
      $(this).find(".ans-title").text(ui.draggable.find("span").text());
  } 
});
	
$("#new-question").click(function(e){
	e.preventDefault();
	$(this).toggleClass("active");
	$("#inp-question").slideToggle(100);
	if($(this).hasClass('active')) $(this).text("Close");
	else $(this).text("+Add")
});
	
$(document).on('click','.question-edit',function(e){
	e.preventDefault(); 
	var self=$(this);
	self.parents("li").addClass('nodrag')
	if($(this).hasClass('active')) 
		{
			var qval=self.parents("li").find("textarea").val(),qaction=self.attr('href').replace('#',""),dataID=self.data('qid');
			data={question_txt:qval,action:qaction,qid:dataID};
			jQuery.post(ajax_url, data, function(response) {
			self.parents("li").find("span").html(response);
			self.text("Edit");
			self.parents("li").removeClass('nodrag')
			});
			$(this).toggleClass("active");
			return false;
		}
	else $(this).text("Update")
	var elm=$(this).parents("li").find("span");
	var elmVal=elm.text();
	elm.html('<textarea name="question_txt"></textarea>');
	elm.children("textarea").val(elmVal);
	elm.children("textarea").focus();
	$(this).toggleClass("active");
});
var waitingDelete=false;
$(document).on('click','.question-rmv',function(e){
	e.preventDefault(); 
	var self=$(this);
	var parent=$(this).parents("li");
	self.toggleClass("active");
	if($(this).hasClass('active'))
	{
		self.text('undo');
		parent.addClass("removing nodrag");
		var qaction=self.attr('href').replace('#',""),dataID=self.data('qid');
			data={action:qaction,qid:dataID};

		waitingDelete=setTimeout(function(){ 
				theAjaxReq=jQuery.post(ajax_url, data, function(response) {
				if(response=='done') parent.fadeOut().remove();
				}); 
		},3000);
		return false;
	}
	if(waitingDelete!==false)
		{clearTimeout(waitingDelete);waitingDelete=false;}
	parent.removeClass("removing nodrag");
	self.text("Remove");
});

$("#inp-question").on('keypress',function(e){
	if(e.keyCode==13)
	{
		var qtxt=$(this).attr('name'),qval=$(this).val(),qid=$(this).attr('id'),self=$(this);
		data={question_txt:qval,action:qid};
		jQuery.post(ajax_url, data, function(response) {
			$("#questions").html(response);
			self.slideToggle(100);
			self.val(""); 
			$("#new-question").text("+Add");
			questionDraggable();
		});
	
	}
});
$("#add-answer").click(function(e){
	e.preventDefault();
	$(this).toggleClass("active");
	$("#new-answer-control").slideToggle(100);
	if($(this).hasClass('active')) $(this).text("Close");
	else $(this).text("+Add")
});

$("#answers-save").click(function(e){
	e.preventDefault();
	var ansElm=$("#new-answer");
	$(this).data('answers_text',ansElm.val());
	var elmData=$(this).data(); 
	answerListAjax(elmData);
	ansElm.val("");
	$("#new-answer-control").slideUp(100);
	$("#add-answer").text('+Add')
	$(".answers").fadeIn(100);
})

function answerListAjax(elmData)
{
	jQuery.post(ajax_url, elmData, function(response) {
		$("#answer-area").html(response);
		answerShortable();
	});
}

})