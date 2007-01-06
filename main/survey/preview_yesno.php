<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: preview_yesno.php 10603 2007-01-06 17:01:47Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_survey 	= Database :: get_main_table(TABLE_MAIN_SURVEY);
$table_group 	= Database :: get_main_table(TABLE_MAIN_GROUP);
$table_question = Database :: get_main_table(TABLE_MAIN_SURVEYQUESTION);


$tool_name = get_lang('ViewQuestions');
$header1 = get_lang('SurveyName');
$header2 = get_lang('GroupName');
$header3 = get_lang('Type');
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('Survey'));
$questionid = '1';
$surveyid = $_REQUEST['surveyid'];
$groupid = $_REQUEST['groupid'];
$qid = 'Yes/No';
if(isset($_REQUEST['back']))
{
 $surveyid = $_REQUEST['surveyid'];
 $groupid = $_REQUEST['groupid'];
 header("location:mcma.php?groupid=$groupid&surveyid=$surveyid");
 exit;
}
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
/*
==============================================================================
		MAIN CODE
==============================================================================
*/

Display::display_header($tool_name);
$ques_id = $_GET['qid'];
$gname=surveymanager::ques_id_group_name($ques_id);
$ques_type = $_GET['qtype'];
?>


<form name="question" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="add_question">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<table>
<tr>
  <td><?php api_display_tool_title($header1) ?></td>
  <?php $surveyname = surveymanager::get_surveyname($surveyid); ?>
  <td><?php api_display_tool_title($surveyname)?></td>
  </tr>
  <tr>
  <td><?php  api_display_tool_title($header2); ?></td>
   <?php $groupname = surveymanager::get_groupname($groupid); ?>
  <td><?php api_display_tool_title($groupname); ?></td>
  </tr>
  <tr>
  <td><?php api_display_tool_title($header3); ?></td>
   <td><?php api_display_tool_title($qid); ?></td>
  </tr>
<tr>
  <td><?php echo get_lang('Question'); ?></td>
  </tr>
<tr>
<td><textarea  cols="50" rows="6" name="questions"> <?echo $enterquestion;?></textarea></td>
</tr>
<tr>
  <td></br><?php echo get_lang('Answer'); ?></td>
  </tr>
   <tr>
  <?
	for($i=1;$i<=2;$i++)
	{	
		?><tr><td><textarea cols="50" rows="3" name="yes"><?echo $mutlichkboxtext[$i];?></textarea></td></tr>
<?		
	}  
  ?>
<tr>
  <td></br><input type="submit" name="back" value="<?php  echo get_lang('Back'); ?> "></td>
 <!-- <td></br><input type="submit" value="<?php  echo get_lang('Import'); ?>"></td>-->
</tr>
</table>
</form>	
<?php
//<textarea  rows="4" name="comment"></textarea>
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>