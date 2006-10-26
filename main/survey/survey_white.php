<?php // $Id: index.php,v 1.44 2005/07/01 10:03:36 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Index of the admin tools
*
*	@package dokeos.admin
==============================================================================
*/

$langFile='survey';
$cidReset=true;
require_once ('../inc/global.inc.php');
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");


$nbQuestionsPerPage = 5;

$db_name = stripslashes($_REQUEST['db_name']);
$surveyid = intval($_REQUEST['surveyid']);
$temp = stripslashes($_REQUEST['temp']);

$group_offset = $group_offset_dist = empty($_REQUEST['group_offset']) ? 0 : intval($_REQUEST['group_offset']);
$question_offset = $question_offset_dist = empty($_REQUEST['question_offset']) ? 0 : intval($_REQUEST['question_offset']);
$indiceQuestion = $indiceQuestionDist = empty($_REQUEST['indiceQuestion']) ? 1 : intval($_REQUEST['indiceQuestion']);

$group_offset_back = empty($_REQUEST['group_offset_back']) ? 0 : intval($_REQUEST['group_offset_back']);
$question_offset_back = empty($_REQUEST['question_offset_back']) ? 0 : intval($_REQUEST['question_offset_back']);
$indiceQuestionBack = empty($_REQUEST['indiceQuestionBack']) ? 1 : intval($_REQUEST['indiceQuestionBack']);


$sql = 'select * from '.$db_name.'.survey where survey_id='.$surveyid;

$rs = api_sql_query($sql,__FILE__,__LINE__);
$o_survey = mysql_fetch_object($rs);
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
$tool_name = get_lang('preview');
Display::display_header($tool_name);
api_display_tool_title("Survey Name : ".$o_survey->title);


// write the css template
//echo '<link href="../css/survey_'.$template.'.css" rel="stylesheet" type="text/css">';


// select the groups
$sql = 'SELECT DISTINCT survey_group.* 
		FROM '.$db_name.'.survey_group 		
		INNER JOIN '.$db_name.'.questions
		ON  survey_group.group_id = questions.gid
		AND questions.survey_id = survey_group.survey_id
		WHERE survey_group.survey_id='.$surveyid.'
		ORDER BY sortby ASC LIMIT '.$group_offset.',1';

$rsGroups = api_sql_query($sql);

$sql = 'SELECT COUNT(DISTINCT survey_group.group_id) 
		FROM '.$db_name.'.survey_group 
		INNER JOIN '.$db_name.'.questions
		ON  survey_group.group_id = questions.gid
		AND questions.survey_id = survey_group.survey_id
		WHERE survey_group.survey_id='.$surveyid;

$rscount = api_sql_query($sql, __FILE__, __LINE__);
list($nbGroups) = mysql_fetch_array($rscount);



echo '<table width="600" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
		<tr><td>';

while($o_group = mysql_fetch_object($rsGroups) ){
	
	$intro = strip_tags($o_group->introduction);
	if($o_group->groupname!='No Group' && !empty($intro))
	{
		echo "<tr><td><br><table cellpadding='2' border='0' style='border: 1px solid'><tr><td align='center'>";
		echo $o_group->introduction;
		echo "</td></tr></table><br><br>";
	}
	
	$sql = 'SELECT * 
			FROM '.$db_name.'.questions 
			WHERE survey_id='.$surveyid.' 
			AND gid='.intval($o_group->group_id).'
			ORDER BY sortby
			LIMIT '.$question_offset.',999';
	
	$rsQuestions = api_sql_query($sql);
	$nbQuestions = mysql_num_rows($rsQuestions);
	$iQuestions = 0;
	while($o_question = mysql_fetch_object($rsQuestions)){
		
		if($iQuestions<$nbQuestionsPerPage || $o_group->groupname!='No Group'){
			
			// select the previous answer the user did
			$sql = '	SELECT answer 
						FROM '.$db_name.'.survey_report 
						WHERE user_id='.$_uid.' AND survey_id='.$surveyid.' AND qid='.$o_question->qid;
			
			$rsAttempt = api_sql_query($sql, __FILE__, __LINE__);
			list($answer) = mysql_fetch_array($rsAttempt);
			
			// remove the f*** <p></p> and other boring and stupid styles
			$o_question->caption = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*) (style=.""[^>]*)(>)','\\1\\3', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $o_question->caption);
		
			echo '<table><tr><td valign="top">'.$indiceQuestion.'- </td><td valign="top">'.stripslashes($o_question->caption).'</td></tr></table>';
			
			$sel1 = $sel2 = "";
			switch ($o_question -> qtype) {
				
				
				case 'Yes/No' :
					if($answer=='a1'){
						$sel1="checked";
					}
					else if($answer=='a2'){
						$sel2="checked";
					}
					echo "&nbsp;<input type=radio value='a1' $sel1 name='q[".$o_question->qid."]'>".stripslashes($o_question->a1)."
						  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $sel2 name='q[".$o_question->qid."]'>".stripslashes($o_question->a2)."<BR><BR>";
				break;	
				
				
				
				
				case 'Open Answer' :
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea cols='50' rows='6' name='q[".$o_question->qid."]'>".stripslashes($o_question->a1).
						stripslashes($answer)."</textarea><br><br>";		
				break;
				
				
				
				case 'Multiple Choice (multiple answer)' :
					$answer = explode(',',$answer);
					
					$break = '';
					if($o_question->alignment=='vertical')
					{
						$break= "<br>";
					}
					echo '<table cellpadding="7" cellspacing="0"><tr><td>';
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						$checked = '';
						if(in_array($current,$answer)){
							$checked = 'checked';
						}
						if(!empty($o_question->$current)){
							if($i!=1 || $o_question->alignment=='vertical')
								echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							echo '<input value="'.$current.'" '.$checked.' type="checkbox" name="q['.$o_question->qid.'][]">'.stripslashes($o_question->$current.$break);
						}
					}
					echo "</td></tr></table><br><br>";	
				break;
				
				
				case 'Multiple Choice (single answer)' :
					
					$break = '';
					if($o_question->alignment=='vertical')
					{
						$break= "<br>";
					}
					echo '<table cellpadding="7" cellspacing="0"><tr><td>';
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						$checked = '';
						if($current == $answer){
							$checked = 'checked';
						}
						if(!empty($o_question->$current)){
							if($i!=1 || $o_question->alignment=='vertical')
								echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							echo '<input value="'.$current.'" '.$checked.' type="radio" name="q['.$o_question->qid.']">'.stripslashes($o_question->$current.$break);
						}
					}
					echo "</td></tr></table><br><br>";	
				
				
				break;
				
				
			
				case 'Numbered' :
				
					$answer = explode(',',$answer);
					
					
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						if(!empty($o_question->$current)){
							echo '&nbsp;&nbsp;&nbsp;<select name="q['.$o_question->qid.'][]">';							
							for($j=0;$j<=10;$j++){
								$selected="";
								if($answer[$i]==$j){
									$selected="selected";
								}
								echo "<option value=$j $selected>".stripslashes($j)."</option>";
							}
							echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.stripslashes($o_question->$current).'<br><br>';

						}
					}
					
				break;
			}
			
			
			$iQuestions++;
			$indiceQuestion++;
		}
		
	}
	if($o_group->groupname!='No Group' || $nbQuestions <= $iQuestions){
		$group_offset++;
		$question_offset=0;
	}
	else {
		$question_offset += $iQuestions;
	}
}


echo '</td></tr></table>';

?>
<br /><br />
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
			  <input type="hidden" name="surveyid" value="<?=$surveyid;?>">
		      <input type="hidden" name="db_name" value="<?=$db_name;?>">		  
		      <input type="hidden" name="temp" value="<?=$temp;?>">
		      <input type="hidden" name="cidReq" value="<?=$_GET['cidReq'];?>">
		      <input type="hidden" name="group_offset" value="<?=$group_offset;?>">
		      <input type="hidden" name="question_offset" value="<?=$question_offset;?>">
		      <input type="hidden" name="indiceQuestion" value="<?=$indiceQuestion;?>">
			  
			  <table width="100%"  border="0" cellpadding="0" cellspacing="0">

				  <tr>
				 
					<td align="center">
					<?php
					echo "<input type=\"button\" name=\"Back\" value=\"Back\" onclick=\"history.back();\">";
					
					if($group_offset < $nbGroups)
						echo '<input type="submit" name="submit" value="Next">&nbsp;';
					else
						echo '<input type="button" value="Finish" onclick="document.location=\'survey_list.php?cidReq='.$cidReq.'\'">&nbsp;';
					?>
					<input type="button" value="Print" onclick="window.print()"></td>
					
				  </tr>
		</table>
	  </form>
	  
	  
<?php

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>