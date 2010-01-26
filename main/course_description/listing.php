<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* Template (view in MVC pattern) used for listing course descriptions  
* @package dokeos.course_description
* @author Christian Fasanando <christian1827@gmail.com>
*/

api_protect_course_script(true);

// display messages
if ($messages['edit'] || $messages['add']) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionUpdated'));
} else if ($messages['destroy']) {
	Display :: display_confirmation_message(get_lang('CourseDescriptionDeleted'));
}

// display actions menu
if (api_is_allowed_to_edit(null,true)) {
	$categories = array ();
	foreach ($default_description_titles as $id => $title) {
		$categories[$id] = $title;
	}
	$categories[ADD_BLOCK] = get_lang('NewBloc');

	$i=1;
	echo '<div class="actions" style="margin-bottom:30px">';
	ksort($categories);
	foreach ($categories as $id => $title) {
		if ($i==ADD_BLOCK) {
			echo '<a href="index?'.api_get_cidreq().'&action=add">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>';
			break;
		} else {
			echo '<a href="index.php?action=edit&'.api_get_cidreq().'&description_type='.$id.'">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>&nbsp;&nbsp;';
			$i++;
		}
	}
	echo '</div>';
}

// display course description list

if ($history) {
	echo '<div><table width="100%"><tr><td><h3>'.get_lang('ThematicAdvanceHistory').'</h3></td><td align="right"><a href="index.php?action=listing">'.Display::return_icon('info.gif',get_lang('BackToCourseDesriptionList'),array('style'=>'vertical-align:middle;')).' '.get_lang('BackToCourseDesriptionList').'</a></td></tr></table></div>';
}
if (isset($descriptions) && count($descriptions) > 0) {
	foreach ($descriptions as $id => $description) {
		echo '<div class="sectiontitle">';
		
		if (api_is_allowed_to_edit(null,true) && !$history) {
			
			//delete
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete&description_type='.$description['description_type'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">';
			echo Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
			echo '</a> ';
			
			//edit
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=edit&&description_type='.$description['description_type'].'">';
			echo Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'));
			echo '</a> ';
			
			if ($description['description_type'] == THEMATIC_ADVANCE) {
				// thematic advance history link				
				echo '<a href="index?action=history&description_type='.$description['description_type'].'">';
				echo Display::return_icon('lp_dir.png',get_lang('ThematicAdvanceHistory'),array('style'=>'vertical-align:middle;float:right;padding-right:4px;'));
				echo '</a> ';					
			}
		}
		
		if ($description['description_type'] == THEMATIC_ADVANCE) {
			$progress_icon = (isset($description['progress_icon'])?$description['progress_icon']:'');
			echo get_lang('ThematicAdvance').' : '.$description['title'].' - '.$progress_icon;
		} else {
			echo $description['title'];
		}	
		
		if ($history) {
			echo ' ('.$description['lastedit_date'].') ';
		}

		echo '</div>';
		echo '<div class="sectioncomment">';
		echo text_filter($description['content']);
		echo '</div>';
	}
} else {
	echo '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
}
?>