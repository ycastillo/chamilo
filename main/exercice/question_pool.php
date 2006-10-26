<?php // $Id: question_pool.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
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
*	QUESTION POOL
*
*	This script allows administrators to manage questions and add them
*	into their exercises.
*
*	One question can be in several exercises.
*
*	@author Olivier Brouckaert
*	@package dokeos.exercise
==============================================================================
*/

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

$langFile='exercice';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$is_allowedToEdit=$is_courseAdmin;

$TBL_EXERCICE_QUESTION = $_course['dbNameGlu'].'quiz_rel_question';
$TBL_EXERCICES         = $_course['dbNameGlu'].'quiz';
$TBL_QUESTIONS         = $_course['dbNameGlu'].'quiz_question';
$TBL_REPONSES          = $_course['dbNameGlu'].'quiz_answer';

if ( empty ( $delete ) ) {
    $delete = $_GET['delete'];
}
if ( empty ( $recup ) ) {
    $recup = $_GET['recup'];
}
if ( empty ( $fromExercise ) ) {
    $fromExercise = $_GET['fromExercise'];
}

// maximum number of questions on a same page
$limitQuestPage=50;

// document path
$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath=$documentPath.'/images';

if($is_allowedToEdit)
{
	// deletes a question from the data base and all exercises
	if($delete)
	{
		// construction of the Question object
		$objQuestionTmp=new Question();

		// if the question exists
		if($objQuestionTmp->read($delete))
		{
			// deletes the question from all exercises
			$objQuestionTmp->delete();
		}

		// destruction of the Question object
		unset($objQuestionTmp);
	}
	// gets an existing question and copies it into a new exercise
	elseif($recup && $fromExercise)
	{
		// construction of the Question object
		$objQuestionTmp=new Question();

		// if the question exists
		if($objQuestionTmp->read($recup))
		{
			// adds the exercise ID represented by $fromExercise into the list of exercises for the current question
			$objQuestionTmp->addToList($fromExercise);
		}

		// destruction of the Question object
		unset($objQuestionTmp);

		// adds the question ID represented by $recup into the list of questions for the current exercise
		$objExercise->addToList($recup);

		api_session_register('objExercise');

		header("Location: admin.php?editQuestion=$recup");
		exit();
	}
}

$nameTools=get_lang('QuestionPool');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

Display::display_header($nameTools,"Exercise");
// if admin of course
if($is_allowedToEdit)
{
?>

<h3>
  <?php echo $nameTools; ?>
</h3>

<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<input type="hidden" name="fromExercise" value="<?php echo $fromExercise; ?>">
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<tr>
  <td colspan="<?php echo $fromExercise?2:3; ?>" align="right">
	<?php echo get_lang('Filter'); ?> : <select name="exerciseId">
	<option value="0">-- <?php echo get_lang('AllExercises'); ?> --</option>
	<option value="-1" <?php if($exerciseId == -1) echo 'selected="selected"'; ?>>-- <?php echo get_lang('OrphanQuestions'); ?> --</option>

<?php
	$sql="SELECT id,title FROM `$TBL_EXERCICES` WHERE id<>'$fromExercise' ORDER BY id";
	$result=api_sql_query($sql,__FILE__,__LINE__);

	// shows a list-box allowing to filter questions
	while($row=mysql_fetch_array($result))
	{
?>

	<option value="<?php echo $row['id']; ?>" <?php if($exerciseId == $row['id']) echo 'selected="selected"'; ?>><?php echo $row['title']; ?></option>

<?php
	}
?>

    </select> <input type="submit" value="<?php echo get_lang('Ok'); ?>">
  </td>
</tr>

<?php
	$from=$page*$limitQuestPage;

	// if we have selected an exercise in the list-box 'Filter'
	if($exerciseId > 0)
	{
		$sql="SELECT id,question,type FROM `$TBL_EXERCICE_QUESTION`,`$TBL_QUESTIONS` WHERE question_id=id AND exercice_id='$exerciseId' ORDER BY position LIMIT $from,".($limitQuestPage+1);
		$result=api_sql_query($sql,__FILE__,__LINE__);
	}
	// if we have selected the option 'Orphan questions' in the list-box 'Filter'
	elseif($exerciseId == -1)
	{
		$sql="SELECT id,question,type FROM `$TBL_QUESTIONS` LEFT JOIN `$TBL_EXERCICE_QUESTION` ON question_id=id WHERE exercice_id IS NULL ORDER BY question LIMIT $from,".($limitQuestPage+1);
		$result=api_sql_query($sql,__FILE__,__LINE__);
	}
	// if we have not selected any option in the list-box 'Filter'
	else
	{
		$sql="SELECT id,question,type FROM `$TBL_QUESTIONS` LEFT JOIN `$TBL_EXERCICE_QUESTION` ON question_id=id WHERE exercice_id IS NULL OR exercice_id<>'$fromExercise' GROUP BY id ORDER BY question LIMIT $from,".($limitQuestPage+1);
		$result=api_sql_query($sql,__FILE__,__LINE__);

		// forces the value to 0
		$exerciseId=0;
	}

	$nbrQuestions=mysql_num_rows($result);
?>

<tr>
  <td colspan="<?php echo $fromExercise?2:3; ?>">
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
	  <td>

<?php
	if($fromExercise)
	{
?>

		<a href="admin.php">&lt;&lt; <?php echo get_lang('GoBackToEx'); ?></a>

<?php
	}
	else
	{
?>

		<a href="admin.php?newQuestion=yes"><?php echo get_lang('NewQu'); ?></a>

<?php
	}
?>

	  </td>
	  <td align="right">

<?php
	if($page)
	{
?>

	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?exerciseId=<?php echo $exerciseId; ?>&fromExercise=<?php echo $fromExercise; ?>&page=<?php echo ($page-1); ?>">&lt;&lt; <?php echo get_lang('PreviousPage'); ?></a> |

<?php
	}
	elseif($nbrQuestions > $limitQuestPage)
	{
?>

	&lt;&lt; <?php echo get_lang('PreviousPage'); ?> |

<?php
	}

	if($nbrQuestions > $limitQuestPage)
	{
?>

	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?exerciseId=<?php echo $exerciseId; ?>&fromExercise=<?php echo $fromExercise; ?>&page=<?php echo ($page+1); ?>"><?php echo get_lang('NextPage'); ?> &gt;&gt;</a>

<?php
	}
	elseif($page)
	{
?>

	<?php echo get_lang('NextPage'); ?> &gt;&gt;

<?php
	}
?>

	  </td>
	</tr>
	</table>
  </td>
</tr>
<tr bgcolor="#E6E6E6">

<?php
	if($fromExercise)
	{
?>

  <td width="80%" align="center"><?php echo get_lang('Question'); ?></td>
  <td width="20%" align="center"><?php echo get_lang('Reuse'); ?></td>

<?php
	}
	else
	{
?>

  <td width="60%" align="center"><?php echo get_lang('Question'); ?></td>
  <td width="20%" align="center"><?php echo get_lang('Modify'); ?></td>
  <td width="20%" align="center"><?php echo get_lang('Delete'); ?></td>

<?php
	}
?>

</tr>

<?php
	$i=1;

	while($row=mysql_fetch_array($result))
	{
		// if we come from the exercise administration to get a question, doesn't show the question already used by that exercise
		if(!$fromExercise || !$objExercise->isInList($row[id]))
		{
?>

<tr>
  <td><a href="admin.php?editQuestion=<?php echo $row[id]; ?>&fromExercise=<?php echo $fromExercise; ?>"><?php echo $row[question]; ?></a></td>
  <td align="center">

<?php
			if(!$fromExercise)
			{
?>

	<a href="admin.php?editQuestion=<?php echo $row[id]; ?>"><img src="../img/edit.gif" border="0" alt="<?php echo get_lang('Modify'); ?>"></a>

<?php
			}
			else
			{
?>

	<a href="<?php echo $phpSelf; ?>?recup=<?php echo $row[id]; ?>&fromExercise=<?php echo $fromExercise; ?>"><img src="../img/enroll.gif" border="0" alt="<?php echo get_lang('Reuse'); ?>"></a>

<?php
			}
?>

  </td>

<?php
			if(!$fromExercise)
			{
?>

  <td align="center">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?exerciseId=<?php echo $exerciseId; ?>&delete=<?php echo $row[id]; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="<?php echo get_lang('Delete'); ?>"></a>
  </td>

<?php
			}
?>

</tr>

<?php
			// skips the last question, that is only used to know if we have or not to create a link "Next page"
			if($i == $limitQuestPage)
			{
				break;
			}

			$i++;
		}
	}

	if(!$nbrQuestions)
	{
?>

<tr>
  <td colspan="<?php echo $fromExercise?2:3; ?>"><?php echo get_lang('NoQuestion'); ?></td>
</tr>

<?php
	}
?>

</table>
</form>

<?php
}
// if not admin of course
else
{
	api_not_allowed();
}

Display::display_footer();
?>
