<?php
	$nsv=new SurveyTblSchema();
	$activeCount=$nsv->activeSurvey()->num_rows;
	$inactiveCount=$nsv->inActiveSurvey()->num_rows;
	$arg=$ext=null; if(isset($_GET['state']))	$arg=array('state'=>$_GET['state']);
	if(isset($_GET['order_by'])) $ext['order_by']=$_GET['order_by']; 
	if(isset($_GET['order'])) $ext['order']=$_GET['order']; 
	if(isset($_GET['limit'])) $ext['limit']=$_GET['limit']; 
	$survey_result=$nsv->all($arg,$ext);
	$total=$activeCount+$inactiveCount;
	$surveys=$survey_result->last_result;
 ?>
<div id="wpbody-content" aria-label="Main content" tabindex="0">
  <div class="wrap">
    <h1><?php echo SCSURVEY_TITLE_P; ?> <a href="<?php echo survey_url($this->submenu_slug); ?>" class="page-title-action">Add New</a></h1>
    <h2 class="screen-reader-text">Filter posts list</h2>
    <ul class="subsubsub">
      <li class="all"> <a class="<?php echo !isset($_GET['state'])?'current':''; ?>"  href="<?php echo survey_url($this->menu_slug); ?>" class="current">All <span class="count">(<?php echo $total; ?>)</span></a></li>
      <?php if(!empty($activeCount)): ?>
      <li> | <a  class="<?php echo (isset($_GET['state']) && $_GET['state']=='active')?'current':''; ?>" href="<?php echo survey_url($this->menu_slug.'&'.$nsv->state.'=active'); ?>">Active <span class="count">(<?php echo $activeCount; ?>)</span></a></li>
    <?php endif;  if(!empty($inactiveCount)): ?>
      <li > | <a class="<?php echo (isset($_GET['state']) && $_GET['state']=='inactive')?'current':''; ?>" href="<?php echo survey_url($this->menu_slug.'&'.$nsv->state.'=inactive'); ?>">Inactive <span class="count">(<?php echo $inactiveCount; ?>)</span></a></li>
    <?php endif; ?>
    </ul>
    <form id="posts-filter" method="get">
      <!-- <p class="search-box">
        <label class="screen-reader-text" for="post-search-input">Search <?php echo SCSURVEY_TITLE_P; ?>:</label>
        <input id="post-search-input" name="s" value="" type="search">
        <input id="search-submit" class="button" value="Search <?php echo SCSURVEY_TITLE_P; ?>" type="submit">
      </p> -->
      <input name="post_status" class="post_status_page" value="all" type="hidden">
      <input name="post_type" class="post_type_page" value="post" type="hidden">
      <div class="tablenav top">
        <!-- <div class="alignleft actions bulkactions">
          <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
          <select name="action" id="bulk-action-selector-top">
            <option value="-1">Bulk Actions</option>
            <option value="delete">Delete</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <input id="doaction" class="button action" value="Apply" type="submit">
        </div> -->
        <div class="tablenav-pages one-page"><span class="displaying-num"><?php echo $survey_result->num_rows ?> item</span> </div>
        <br class="clear">
      </div>
      <h2 class="screen-reader-text"><?php echo SCSURVEY_TITLE_P; ?> list</h2>
      <table class="wp-list-table widefat fixed striped posts">
        <thead>
          <tr>
            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label>
              <input id="cb-select-all-1" type="checkbox"></td>
            <th scope="col" id="title" class="manage-column column-title column-primary sortable <?php echo ((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->title)?'desc':'asc'); ?>"> <a href="<?php echo survey_url($this->menu_slug.'&order_by='.$nsv->title.'&order='.((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->title)?'desc':'asc')); ?>"><span>Title</span><span class="sorting-indicator"></span></a> </th>
            <th scope="col" id="sortcode" class="manage-column column-sortcode "> <a href="#scode"><span>Sortcode</span></a> </th>
            <th scope="col" id="date" class="manage-column column-date sortable <?php echo ((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->active)?'desc':'asc'); ?>"><a href="<?php echo survey_url($this->menu_slug.'&order_by='.$nsv->active.'&order='.((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->active)?'desc':'asc')); ?>"><span>Date</span><span class="sorting-indicator"></span></a> </th>
          </tr>
        </thead>
        <tbody id="the-list">
        <?php 
        	foreach($surveys as $survey):
        		$nsv->setIt($survey);
        		//p_r($survey);
        		$curlink=survey_url($this->submenu_slug.'&survey='.$nsv->id);
        		$qlink=$curlink.'&action=questions';
         ?>
          <tr id="post-<?php echo $nsv->id; ?>" class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry category-uncategorized">
            <th scope="row" class="check-column"> <label class="screen-reader-text" for="cb-select-1">Select <?php echo $nsv->title; ?></label>
              <input id="cb-select-<?php echo $nsv->id; ?>" name="survey[]" value="1" type="checkbox">
              <div class="locked-indicator"></div>
            </th>
            <td class="title column-title has-row-actions column-primary page-title" data-colname="Title"><strong><a class="row-title" href="<?php echo $curlink.'&action=edit'; ?>"><?php echo $nsv->title; ?></a></strong>
              <div class="row-actions"> 
	              <span class="edit"><a href="<?php echo $curlink.'&action=edit'; ?>">Edit</a> | </span> 
	              <span class="trash"><a href="<?php echo $curlink.'&action=delete'; ?>" class="submitdelete" onClick="return confirm('are you sure want to delete it?')" aria-label="<?php echo $nsv->title; ?>">Delete</a> | </span> 
	              <span class="questions"><a href="<?php echo $qlink; ?>" rel="permalink">Questions</a> | </span>
	              <span class="state"><a href="<?php echo $curlink.'&action=changestate' ?>" rel="permalink"><?php echo ($nsv->state=='active'?'Inactive':'Active'); ?></a></span>
	            </div>
              </td>
            <td class="date column-date" data-colname="Date"><input type="text" class="sc-sortcode" <?php echo $nsv->state=="active"?'readonly':'disabled'; ?> value="[<?php echo $this->menu_slug; ?> id='<?php echo $nsv->id; ?>']"></td>
            <td class="date column-date" data-colname="Date">Published<br>
              <abbr title="<?php echo $nsv->active; ?>"><?php echo date("d M, Y",strtotime($nsv->active)); ?></abbr></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label>
              <input id="cb-select-all-1" type="checkbox"></td>
            <th scope="col" id="title" class="manage-column column-title column-primary sortable <?php echo ((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->title)?'desc':'asc'); ?>"> <a href="<?php echo survey_url($this->menu_slug.'&order_by='.$nsv->title.'&order='.((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->title)?'desc':'asc')); ?>"><span>Title</span><span class="sorting-indicator"></span></a> </th>
            <th scope="col" id="sortcode" class="manage-column column-sortcode "> <a href="#scode"><span>Sortcode</span></a> </th>
            <th scope="col" id="date" class="manage-column column-date sortable <?php echo ((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->active)?'desc':'asc'); ?>"><a href="<?php echo survey_url($this->menu_slug.'&order_by='.$nsv->active.'&order='.((isset($_GET['order']) && $_GET['order']=='asc' && $_GET['order_by']==$nsv->active)?'desc':'asc')); ?>"><span>Date</span><span class="sorting-indicator"></span></a> </th>
          </tr>
        </tfoot>
      </table>
      <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
          <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
          <select name="action2" id="bulk-action-selector-top">
            <option value="-1">Bulk Actions</option>
            <option value="delete">Delete</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <input id="doaction2" class="button action" value="Apply" type="submit">
        </div>
        <div class="alignleft actions"> </div>
        <div class="tablenav-pages one-page"><span class="displaying-num">1 item</span> <span class="pagination-links"><span class="tablenav-pages-navspan" aria-hidden="true">«</span> <span class="tablenav-pages-navspan" aria-hidden="true">‹</span> <span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input">1 of <span class="total-pages">1</span></span> <span class="tablenav-pages-navspan" aria-hidden="true">›</span> <span class="tablenav-pages-navspan" aria-hidden="true">»</span></span></div>
        <br class="clear">
      </div>
    </form>
    <br class="clear">
  </div>
  <div class="clear"></div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$(".sc-sortcode").focus(function(){
			$(this).select();
		})
	});
</script>
