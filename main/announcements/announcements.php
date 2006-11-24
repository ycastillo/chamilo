<?php //$Id: announcements.php 10190 2006-11-24 00:23:20Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
 * @author Frederik Vermeire <frederik.vermeire@pandora.be>, UGent Internship
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: code cleaning
 * @abstract The task of the internship was to integrate the 'send messages to specific users' with the
 *			 Announcements tool and also add the resource linker here. The database also needed refactoring
 *			 as there was no title field (the title was merged into the content field)
 * @package dokeos.announcements
 * @todo make AWACS out of the configuration settings
 * @todo this file is 1200+ lines without any functions -> needs to be split into
 * multiple functions
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// setting the language file
$langFile[] = "announcements";
$langFile[] = "group";
$langFile[] = "survey";

// setting the global file that gets the general configuration, the databases, the languages, ...
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$nameTools = get_lang('Announcement');


//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = $_GET['id_session'];

/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script();

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
// Configuration settings
$display_announcement_list = true;
$display_form             = false;
$display_title_list 		 = true;

// Maximum title messages to display
$maximum 	= '12';

// Length of the titles
$length 	= '36';

// Database variables
$tbl_course_user   		= Database::get_main_table(MAIN_COURSE_USER_TABLE);
$tbl_user          		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_courses			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_sessions			= Database::get_main_table(MAIN_SESSION_TABLE);
$tbl_session_course_user= Database::get_main_table(MAIN_SESSION_COURSE_USER_TABLE);
$tbl_group     			= Database::get_course_table(GROUP_TABLE);
$tbl_groupUser  		= Database::get_course_table(GROUP_USER_TABLE);
$tbl_announcement		= Database::get_course_table(ANNOUNCEMENT_TABLE);
$tbl_item_property  = Database::get_course_table(ITEM_PROPERTY_TABLE);

/*
-----------------------------------------------------------
	Resource linker
-----------------------------------------------------------
*/
$_SESSION['source_type']="Ad_Valvas";
include('../resourcelinker/resourcelinker.inc.php');

if ($_POST['addresources']) // When the "Add Resource" button is clicked we store all the form data into a session
{
	include('announcements.inc.php');

    $form_elements= array ('emailTitle'=>stripslashes($emailTitle), 'newContent'=>stripslashes($newContent), 'id'=>$id, 'to'=>$selectedform, 'emailoption'=>$email_ann);
    $_SESSION['formelements']=$form_elements;

    if($id) // this is to correctly handle edits
	{
		  $action="edit";
    }else
    {
		  $action="add";
    }

	// ============== //
	// 7 = Ad_Valvas	//
	// ============== //
	if($surveyid)
	{
		header("Location: ../resourcelinker/resourcelinker.php?source_id=7&action=$action&id=$id&originalresource=no&publish_survey=$surveyid&db_name=$db_name&cidReq=$cidReq");
		exit;
	}
	else
	{
		header("Location: ../resourcelinker/resourcelinker.php?source_id=7&action=$action&id=$id&originalresource=no");
		exit;
	}
	exit;
}

/*
-----------------------------------------------------------
	Tracking
-----------------------------------------------------------
*/
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
event_access_tool(TOOL_ANNOUNCEMENT);

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once('announcements.inc.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once(api_get_path(INCLUDE_PATH).'conf/mail.conf.php');
require_once(api_get_path(LIBRARY_PATH).'debug.lib.inc.php');

/*
-----------------------------------------------------------
	POST TO
-----------------------------------------------------------
*/
if ($_POST['To'])
{
	$display_form = true;

	$form_elements= array ('emailTitle'=>stripslashes($emailTitle), 'newContent'=>stripslashes($newContent), 'id'=>$id, 'emailoption'=>$email_ann);
    $_SESSION['formelements']=$form_elements;

    $form_elements            	= $_SESSION['formelements'];

	$title_to_modify            = $form_elements["emailTitle"];

	$content_to_modify          = $form_elements["newContent"];

	$announcement_to_modify     = $form_elements["id"];
}

/*
-----------------------------------------------------------
	Show/hide user/group form
-----------------------------------------------------------
*/

$setting_select_groupusers=true;
if (!$_POST['To'] and !$_SESSION['select_groupusers'])
{
	$_SESSION['select_groupusers']="hide";
}
$select_groupusers_status=$_SESSION['select_groupusers'];
if ($_POST['To'] and ($select_groupusers_status=="hide"))
{
	$_SESSION['select_groupusers']="show";
}
if ($_POST['To'] and ($select_groupusers_status=="show"))
{
	$_SESSION['select_groupusers']="hide";
}

/*
-----------------------------------------------------------
	Action handling
-----------------------------------------------------------
*/

// display the form
if (($_GET['action'] == 'add' && $_GET['origin'] == "") || $_GET['action'] == 'edit' || $_POST['To'])
{
	$display_form = true;
}

// clear all resources
if ($originalresource!=="no" and $action=="add")
{
	$_SESSION["formelements"]=null;
	unset_session_resources();
}

/*
-----------------------------------------------------------
	Javascript
-----------------------------------------------------------
*/
// this is a quick and dirty hack that fixes a bug http://www.dokeos.com/forum/viewtopic.php?t=5263
// when you edit an announcement that was sent to specific users/groups
if ($_SESSION['select_groupusers'] =="show" or $_GET['action']=='modify')
{
	// this javascript should only be loaded when we show the forms to send messages to individual users/groups
	// because otherwise it produces a bug (=> year is set to 2009 on submit due to the javascript selectAll
	$htmlHeadXtra[] = to_javascript();
}

/*
-----------------------------------------------------------
	Filter user/group
-----------------------------------------------------------
*/

/*	if ($_GET['user'] or $_GET['group'])
		{
			$_SESSION['user']=$_GET['user'];
			$_SESSION['group']=$_GET['group'];
		}
	if ($_GET['user']=="none" or $_GET['group']=="none")
		{
			api_session_unregister("user");
			api_session_unregister("group");
		}
	if ($_GET['isStudentView']=="false")
		{
			api_session_unregister("user");
			api_session_unregister("group");
		}
*/

if(!empty($_GET['toolgroup'])){
	if($_GET['toolgroup'] == strval(intval($_GET['toolgroup']))){ //check is integer
		$toolgroup = $_GET['toolgroup'];
		$_SESSION['select_groupusers'] = 'hide';
	}else{
		$toolgroup = 0;
	}
	api_session_register("toolgroup");
}


/*
-----------------------------------------------------------
	Sessions
-----------------------------------------------------------
*/
if ($_SESSION['formelements'] and $_GET['originalresource'] == 'no')
{
	$form_elements			= $_SESSION['formelements'];
	$title_to_modify		= $form_elements['emailTitle'];
	$content_to_modify		= $form_elements['newContent'];
	$announcement_to_modify	= $form_elements['id'];
	$to						= $form_elements['to'];
	//load_edit_users('announcement',$announcement_to_modify);

	$email_ann				= $form_elements['emailoption'];
}
/*
-----------------------------------------------------------
	Survey
-----------------------------------------------------------
*/
$surveyid=$_REQUEST['publish_survey'];
$cidReq=$_REQUEST['cidReq'];
if($surveyid)
{
$db_name=$_REQUEST['db_name'];
$sql_temp = "SELECT * FROM $db_name.survey WHERE survey_id='$surveyid'";
$res_temp = api_sql_query($sql_temp, __FILE__, __LINE__);
$obj=@mysql_fetch_object($res_temp);
$template=$obj->template;
}
if($surveyid)
{
$interbreadcrumb[] = array ("url" => "../survey/survey_list.php?cidReq=$cidReq", "name" => get_lang('Survey'));
$nameTools = get_lang('PublishSurvey');
}
else
$nameTools = get_lang('Announcement');

$nameTools12 = get_lang('PublishSurvey');



/*
-----------------------------------------------------------
	Learning path & css
-----------------------------------------------------------
*/
// showing the header if we are not in the learning path, if we are in
// the learning path, we do not include the banner so we have to explicitly
// include the stylesheet, which is normally done in the header
if ($_GET['origin'] !== 'learnpath')
{
	//we are not in the learning path
	Display::Display_header($nameTools,"Announcements");
}
else
{
	//we are in the learning path, only display central data and change css
	$display_title_list = false;
	$display_announcement_list = false;
	$display_specific_announcement = true;
	$announcement_id = $_REQUEST['ann_id'];
	?> <link rel="stylesheet" type="text/css" href="<?php echo $clarolineRepositoryWeb ?>css/default.css">
	<!-- css file for announcements -->
	<link href="../css/announcements.css" rel="stylesheet" type="text/css">
	<?php
}

// inserting an anchor (top) so one can jump back to the top of the page
echo "<a name=\"top\"></a>";

/*=============================================
			  ACTION HANDLING
=============================================*/

if (api_is_allowed_to_edit() OR api_get_course_setting('allow_user_edit_announcement'))
{
	/*
	-----------------------------------------------------------
		Change visibility of announcement
	-----------------------------------------------------------
	*/
	// $_GET['isStudentView']<>"false" is added to prevent that the visibility
	// is changed after you do the following:
	// change visibility -> studentview -> course manager view
	if ($_GET['isStudentView']<>"false")
	{
		if (isset($_GET['id']) AND $_GET['id'] AND isset($_GET['action']) AND $_GET['action']=="showhide")
		{
			$id=intval(addslashes($_GET['id']));
			change_visibility(TOOL_ANNOUNCEMENT,$id);
			$message = get_lang("Visible");
		}
	}

	/*
	-----------------------------------------------------------
		Delete announcement
	-----------------------------------------------------------
	*/
	if ($_GET['action']=="delete" AND isset($_GET['id']))
	{
		//api_sql_query("DELETE FROM  $tbl_announcement WHERE id='$delete'",__FILE__,__LINE__);
		$id=intval(addslashes($_GET['id']));

		// tooledit : visibility = 2 : only visibile for platform administrator
		api_sql_query("UPDATE $tbl_item_property SET visibility='2' WHERE tool='".TOOL_ANNOUNCEMENT."' and ref='".$id."'",__FILE__,__LINE__);

		delete_added_resource("Ad_Valvas", $delete);

		$id = null;
		$emailTitle = null;
		$newContent = null;

		$message = get_lang("AnnouncementDeleted");
	}

	/*
	-----------------------------------------------------------
		Delete all announcements
	-----------------------------------------------------------
	*/
	if ($_GET['action']=="delete_all")
	{

		//api_sql_query("DELETE FROM $tbl_announcement",__FILE__,__LINE__);

		api_sql_query("UPDATE $tbl_item_property SET visibility='2' WHERE tool='".TOOL_ANNOUNCEMENT."'",__FILE__,__LINE__);

		delete_all_resources_type("Ad_Valvas");

		$id = null;
		$emailTitle = null;
		$newContent = null;

		$message = get_lang("AnnouncementDeletedAll");
	}

	/*
	-----------------------------------------------------------
		Modify announcement
	-----------------------------------------------------------
	*/
	if ($_GET['action']=="modify" AND isset($_GET['id']))
	{
		$display_form = true;

		// RETRIEVE THE CONTENT OF THE ANNOUNCEMENT TO MODIFY
		$sql="SELECT * FROM  $tbl_announcement WHERE id='".$_GET['id']."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$myrow = mysql_fetch_array($result);

		if ($myrow)
		{
			$announcement_to_modify 	= $myrow['id'];
			$content_to_modify 		= $myrow['content'];

			$title_to_modify 			= $myrow['title'];

			if ($originalresource!=="no") // and !addresources)
			{
				//unset_session_resources();
				edit_added_resources("Ad_Valvas", $announcement_to_modify);
				$to=load_edit_users("announcement", $announcement_to_modify);
			}

			$display_announcement_list = false;
		}

		if ($to=="everyone" OR !empty($_SESSION['toolgroup']))
		{
			$_SESSION['select_groupusers']="hide";
		}
		else
		{
			$_SESSION['select_groupusers']="show";
		}

	}

	/*
	-----------------------------------------------------------
		Move announcement up/down
	-----------------------------------------------------------
	*/
	if ($_GET['down'])
	{
		$thisAnnouncementId = $_GET['down'];
		$sortDirection = "DESC";
	}

	if ($_GET['up'])
	{
		$thisAnnouncementId = $_GET['up'];
		$sortDirection = "ASC";
	}

	if ($sortDirection)
	{
		if (!in_array(trim(strtoupper($sortDirection)), array('ASC', 'DESC')))
		{
			$sortDirection='ASC';
		}
		$my_sql = "SELECT announcement.id, announcement.display_order " .
				"FROM $tbl_announcement announcement, " .
				"$tbl_item_property itemproperty " .
				"WHERE itemproperty.ref=announcement.id " .
				"AND itemproperty.tool='".TOOL_ANNOUNCEMENT."' " .
				"AND itemproperty.visibility<>2 " .
				"ORDER BY display_order $sortDirection";
		$result = api_sql_query($my_sql,__FILE__,__LINE__);

		while (list ($announcementId, $announcementOrder) = mysql_fetch_row($result))
		{
			// STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
			//          COMMIT ORDER SWAP ON THE DB

			if (isset ($thisAnnouncementOrderFound) && $thisAnnouncementOrderFound == true)
			{
				$nextAnnouncementId = $announcementId;
				$nextAnnouncementOrder = $announcementOrder;
				api_sql_query("UPDATE $tbl_announcement " .
						"SET display_order = '$nextAnnouncementOrder' " .
						"WHERE id =  '$thisAnnouncementId'",__FILE__,__LINE__);
				api_sql_query("UPDATE $tbl_announcement " .
						"SET display_order = '$thisAnnouncementOrder' " .
						"WHERE id =  '$nextAnnouncementId.'",__FILE__,__LINE__);

				break;
			}

			// STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT

			if ($announcementId == $thisAnnouncementId)
			{
				$thisAnnouncementOrder = $announcementOrder;
				$thisAnnouncementOrderFound = true;
			}
		}
		// show message
		$message = get_lang('AnnouncementMoved');
	}

	/*
	-----------------------------------------------------------
		Submit announcement
	-----------------------------------------------------------
	*/
	if (api_is_allowed_to_edit() OR api_get_course_setting('allow_user_edit_announcement'))
	{

		$emailTitle=$_POST['emailTitle'];
		$newContent=$_POST['newContent'];
		$submitAnnouncement=isset($_POST['submitAnnouncement'])?$_POST['submitAnnouncement']:0;

		$id=intval($_POST['id']);

		if ($submitAnnouncement)
		{
			
			if(isset($id)&&$id) // there is an Id => the announcement already exists => update mode
			{
				$edit_id = edit_advalvas_item($id,$emailTitle,$newContent,$selectedform);
				if(!$delete)
				{
				    update_added_resources("Ad_Valvas", $id);
				}
				$message = get_lang('AnnouncementModified');
			}
			else //insert mode
			{
				if(!$surveyid){
					$result = api_sql_query("SELECT MAX(display_order) FROM $tbl_announcement",__FILE__,__LINE__);
	
					list($orderMax) = mysql_fetch_row($result);
					$order = $orderMax + 1;
	
					if(!empty($_SESSION['toolgroup'])){
						$insert_id=store_advalvas_item($_POST['emailTitle'],$_POST['newContent'],$order,array('GROUP:'.$_SESSION['toolgroup']));
					}else{
						$insert_id=store_advalvas_item($_POST['emailTitle'],$_POST['newContent'],$order,$_POST['selectedform']);
					}
	
				    store_resources($_SESSION['source_type'],$insert_id);
	
				    $_SESSION['select_groupusers']="hide";
	
				    $message = get_lang('AnnouncementAdded');
				}

/*============================================================
MAIL WHEN USER COMES FROM SURVEY
======================================================= */

if($_POST['emailsAdd'])
{

	 $to_email_address =$_POST['emailsAdd'];
	 $to_email_to = explode(',', $to_email_address);
	 $to_email = array_unique($to_email_to);
	 $db_name = $_REQUEST['db_name'];
	for($i=0;$i<count($to_email);$i++)
   {

			$to= trim($to_email[$i]);
			$db_name = $_REQUEST['db_name'];
			$newContentone=str_replace("#page#","choose_language.php",$newContent);
			$newContenttwo=str_replace("#temp#",$template,$newContentone);
			$newContentthree=str_replace("#sid#",$surveyid,$newContenttwo);
			$newContentfour=str_replace("#mail#",$to,$newContentthree);
            $newContentfive=str_replace("#db_name#",$db_name,$newContentfour);
			$newContentsix=str_replace("#uid#","",$newContentfive);

if(eregi('^[0-9a-z_\.-]+@(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,3})$', $to ))
	{
	  $subject=stripslashes($emailTitle);
	 $message=stripslashes($newContentsix);

    $sender_name = $_SESSION['_user']['lastName'].' '.$_SESSION['_user']['firstName'];
    $email = $_SESSION['_user']['mail'];
	$headers="From:$sender_name\r\nReply-to: $email\r\nContent-type: text/html; charset=iso-8859-15";
	//@mail($to,$subject,$message,$headers);
	//api_send_mail($to,$subject,$message,$headers);
	api_mail('',$to,$subject,$message,$sender_name,$email,$headers);
	$sql_date="SELECT * FROM $db_name.survey WHERE survey_id='$surveyid'";
	$res_date=api_sql_query($sql_date);
	$obj_date=mysql_fetch_object($res_date);
	$end_date=$obj_date->avail_till;
	$table_reminder = Database :: get_main_table(MAIN_SURVEY_REMINDER_TABLE);
	if($_REQUEST['reminder']=="1")
	{
		$time=getdate();
		$time = $time['yday'];
		$time = $time+7;
		$sql_insert="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$to','".addslashes($subject)."','".addslashes($message)."','1','$time','$end_date')";
		api_sql_query($sql_insert);
	}
	else if($_REQUEST['reminder']=="2")
	{
		$time=getdate();
		$time = $time['yday'];
		$time = $time+14;
		$sql_insert="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$to','".addslashes($subject)."','".addslashes($message)."','1','$time','$end_date')";
		api_sql_query($sql_insert);
	}
	else if($_REQUEST['reminder']=="3")
	{
		$time=getdate();
		$time = $time['yday'];
		$time = $time+30;
		$sql_insert="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$to','".addslashes($subject)."','".addslashes($message)."','1','$time','$end_date')";
		api_sql_query($sql_insert);
	}
  }
 }
}

				/*===================================================================
				    							MAIL FUNCTION
				===================================================================*/

				if ($_POST['email_ann'] && empty($_POST['onlyThoseMails']))
				{
				  	$sent_to=sent_to("announcement", $insert_id);

				    $userlist   = $sent_to['users'];
				    $grouplist  = $sent_to['groups'];

				        // groepen omzetten in users
				        if ($grouplist)
						{

							$grouplist = "'".implode("', '",$grouplist)."'";	//protect individual elements with surrounding quotes
							$sql = "SELECT user_id
									FROM $tbl_groupUser gu
									WHERE gu.group_id IN (".$grouplist.")";


							$groupMemberResult = api_sql_query($sql,__FILE__,__LINE__);


							if ($groupMemberResult)
							{
								while ($u = mysql_fetch_array($groupMemberResult))
								{
									$userlist [] = $u [user_id]; // complete the user id list ...
								}
							}
						}


					    if(is_array($userlist))
					    {
					    	$userlist = "'".implode("', '", array_unique($userlist) )."'";

					    	// send to the created 'userlist'
						    $sqlmail = "SELECT user_id, lastname, firstname, email
							       					 FROM $tbl_user
							       					 WHERE user_id IN (".$userlist.")";
					    }
				    	else if(empty($_POST['not_selected_form']))
				    	{
				    		// send to everybody
				    		$sqlmail = "SELECT user.user_id, user.email, user.lastname, user.firstname
								                     FROM $tbl_course_user, $tbl_user
								                     WHERE course_code='".mysql_real_escape_string($_course['sysCode'])."'
								                     AND course_rel_user.user_id = user.user_id";

				    	}

						if($sqlmail!=''){
							$result = api_sql_query($sqlmail,__FILE__,__LINE__);

					    	/*=================================================================================
								    				send email one by one to avoid antispam
						    =================================================================================*/


							while ($myrow = mysql_fetch_array($result))
							{
								/*    Header : Bericht van uw lesgever - GES ($_cid)

									  Body :   John Doe (prenom + nom) <john_doe@hotmail.com> (email)

									  		   Morgen geen les!! (emailTitle)

									  		   Morgen is er geen les, de les wordt geschrapt wegens vergadering (newContent)
							    */

								$emailsubjbericht = get_lang('professorMessage');
								$emailSubject = $emailsubjbericht. " - ".$_course['official_code'];

	                            $db_name = $_REQUEST['db_name'];



	                            if($surveyid) {
	                            	$newContentone=str_replace("#page#","choose_language.php",$newContent);
									$newContenttwo=str_replace("#temp#",$template,$newContentone);
									$newContentthree=str_replace("#sid#",$surveyid,$newContenttwo);
									$newContentfour=str_replace("#mail#",$myrow["email"],$newContentthree);
		                            $newContentfive=str_replace("#db_name#",$db_name,$newContentfour);
									$newContentsix=str_replace("#uid#",$myrow["user_id"],$newContentfive);
	                    			$message=stripslashes($newContentsix);

								    $sender_name = $_SESSION['_user']['lastName'].' '.$_SESSION['_user']['firstName'];
								    $email = $_SESSION['_user']['mail'];


									$headers="From:$sender_name\r\nReply-to: $email\r\nContent-type: text/html; charset=iso-8859-15";
									//@mail($myrow["email"],stripslashes($emailTitle),$message,$headers);
									api_mail('',$myrow["email"],stripslashes($emailTitle),$message,$sender_name,$email);
	                            }
	                            else{
									$mail_body = $myrow["lastname"]." ".$myrow["firstname"]." <".$myrow["email"]."><br /> \n\n".stripslashes($emailTitle)."<br />".trim(stripslashes($newContentsix))." <br /><br />-- <br />";
									$mail_body .= $_user['firstName'].' '.$_user['lastName']." ";
									$mail_body .= "<".$_user['mail'].">\n";
									$mail_body .= $_course['official_code'].' '.$_course['name'];

									//set the charset and use it for the encoding of the email - small fix, not really clean (should check the content encoding origin first)
									//here we use the encoding used for the webpage where the text is encoded (ISO-8859-1 in this case)

									//$to_email_address =$_POST['emailsAdd'];
									//$mail_body;

		                          	if(empty($charset)){$charset='ISO-8859-1';}
									$encoding = 'Content-Type: text/html; charset='. $charset;
			                        $mailid=$myrow["email"];

									$newmail = api_mail($myrow["lastname"].' '.$myrow["firstname"], $myrow["email"],    $emailSubject, $mail_body, $_SESSION['_user']['lastName'].' '.$_SESSION['_user']['firstName'], $_SESSION['_user']['mail'],$encoding);
	                            }
								$sql_date="SELECT * FROM $db_name.survey WHERE survey_id='$surveyid'";
								$res_date=api_sql_query($sql_date);
								$obj_date=mysql_fetch_object($res_date);
								$end_date=$obj_date->avail_till;
								$table_reminder = Database :: get_main_table(MAIN_SURVEY_REMINDER_TABLE);
								if($_REQUEST['reminder']=="1")
								{
									$time=getdate();
									$time = $time['yday'];
									$time = $time+7;
									$sql="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$mailid','".addslashes($emailSubject)."','".addslashes($mail_body)."','1','$time','$end_date')";
									api_sql_query($sql);
								}
								else if($_REQUEST['reminder']=="2")
								{
									$time=getdate();
									$time = $time['yday'];
									$time = $time+14;
									$sql="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$mailid','".addslashes($emailSubject)."','".addslashes($mail_body)."','1','$time','$end_date')";
									api_sql_query($sql);

								}
								else if($_REQUEST['reminder']=="3")
								{
									$time=getdate();
									$time = $time['yday'];
									$time = $time+30;
									$sql="INSERT INTO $table_reminder(sid,db_name,email,subject,content,reminder_choice,reminder_time,avail_till) values('$surveyid','$db_name','$mailid','".addslashes($emailSubject)."','".addslashes($mail_body)."','1','$time','$end_date')";
									api_sql_query($sql);
								}



							}

							update_mail_sent($insert_id);
							$message = $added_and_sent;
						}

					} // $email_ann*/

				}	// isset


			// UNSET VARIABLES

				unset_session_resources();
				unset($form_elements);
				$_SESSION['formelements']=null;

				$newContent = null;
				$emailTitle = null;

				unset($emailTitle);
				unset($newContent);
				unset($content_to_modify);
				unset($title_to_modify);



if($_REQUEST['publish_survey'])
{

 $surveyid=$_REQUEST['surveyid'];
 $cidReq = $_REQUEST['cidReq'];
 ?>
<script>
	window.location.href="../survey/survey_list.php?cidReq=<?php echo  $cidReq; ?>&published=published&surveyid=<?php echo $_REQUEST['publish_survey']; ?>";
</script>
<?php
}

	}	// if $submit Announcement





  }

}

/*====================================================
		     		Tool Title
====================================================*/
if ($_GET['origin'] !== 'learnpath')
{
	//api_display_tool_title($nameTools);
	//Display::display_introduction_section(TOOL_ANNOUNCEMENT, $is_allowed);
}

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

/*=====================================================================================
                                          MAIN CODE     HTML
=====================================================================================*/

	// The commands below will change these display settings if they need it


		if ($_GET['origin'] !== 'learnpath')
		{
			echo "\n\n<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			echo "\t<tr>\n";

	    	echo "\t\t<td width=\"20%\" valign=\"top\">\n";
		}
      /*======================================================================
                              DISPLAY LEFT COLUMN
      ======================================================================*/
		if(api_is_allowed_to_edit() OR api_get_course_setting('allow_user_edit_announcement')) // check teacher status
		{
	      	if ($_GET['origin'] !== 'learnpath')
				{

					$sql="SELECT
							announcement.*, toolitemproperties.*
							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
							WHERE announcement.id = toolitemproperties.ref
							AND toolitemproperties.tool='announcement'
							AND toolitemproperties.visibility<>'2'
							GROUP BY toolitemproperties.ref
							ORDER BY display_order DESC
							LIMIT 0,$maximum";
				}
		}
		else 	// students only get to see the visible announcements
		{
			if ($_GET['origin'] !== 'learnpath')
				{
					$group_memberships=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);

					// the user is member of several groups => display personal announcements AND his group announcements AND the general announcements
					if (is_array($group_memberships))
					{
						$sql="SELECT
							announcement.*, toolitemproperties.*
							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
							WHERE announcement.id = toolitemproperties.ref
							AND toolitemproperties.tool='announcement'
							AND toolitemproperties.visibility='1'
							AND	( toolitemproperties.to_user_id='".$_user['user_id']."'" .
								"OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
							GROUP BY toolitemproperties.ref
							ORDER BY display_order DESC
							LIMIT 0,$maximum";
					}
					// the user is not member of any group
					else
					{
						// this is an identified user => show the general announcements AND his personal announcements
						if ($_user['user_id'])
						{
							$sql="SELECT
								announcement.*, toolitemproperties.*
								FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
								WHERE announcement.id = toolitemproperties.ref
								AND toolitemproperties.tool='announcement'
								AND toolitemproperties.visibility='1'
								AND ( toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id='0')
								GROUP BY toolitemproperties.ref
								ORDER BY display_order DESC
								LIMIT 0,$maximum";
						}
						// the user is not identiefied => show only the general announcements
						else
						{
							$sql="SELECT
								announcement.*, toolitemproperties.*
								FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
								WHERE announcement.id = toolitemproperties.ref
								AND toolitemproperties.tool='announcement'
								AND toolitemproperties.visibility='1'
								AND toolitemproperties.to_group_id='0'
								GROUP BY toolitemproperties.ref
								ORDER BY display_order DESC
								LIMIT 0,$maximum";
						}
					}
				}
		}


$result = api_sql_query($sql,__FILE__,__LINE__);

$announcement_number = mysql_num_rows($result);

/*----------------------------------------------------
				ADD ANNOUNCEMENT / DELETE ALL
----------------------------------------------------*/
if(!$surveyid)
{
		if ((api_is_allowed_to_edit() OR api_get_course_setting('allow_user_edit_announcement')) and ($_GET['origin'] !== 'learnpath'))
		{

			echo "<a href='".$_SERVER['PHP_SELF']."?action=add&origin=".$_GET['origin']."'><img src=\"../img/valves_add.gif\"> ".get_lang("AddAnnouncement")."</a><br/>";
			if ($announcement_number > 1)
			{
				echo "<a href=\"".$_SERVER['PHP_SELF']."?action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang("ConfirmYourChoice")."')) return false;\"><img src=\"../img/valves_delete.gif\"/> ".get_lang("AnnouncementDeleteAll")."</a>\n";
			}	// if announcementNumber > 1
			echo "<hr noshade size=\"1\">";
		}
}

/*----------------------------------------------------
				ANNOUNCEMENTS LIST
----------------------------------------------------*/
if(!$surveyid)
{
	
if ($display_title_list == true)
{
	echo "\t\t\t<table>\n";
	while ($myrow = mysql_fetch_array($result))
	{
			$title = $myrow['title'];

			echo "\t\t\t\t<tr>\n";
			echo "\t\t\t\t\t<td width=\"15%\">\n";
			if ($myrow['visibility']==0)
				{ $class="class=\"invisible\"";}
			else
			{ $class="";}
			echo "\t\t\t\t\t\t<a style=\"text-decoration:none\" href=\"announcements.php#".$myrow['id']."\" ".$class.">" . api_trunc_str($title,$length) . "</a>\n";
			echo "\t\t\t\t\t</td>\n\t\t\t\t</tr>\n";
	}
	echo "\t\t\t</table>\n";
} // end $display_title_list == true
}

if ($_GET['origin'] !== 'learnpath')
{
	echo   "\t\t</td>\n";
	echo "\t\t<td width=\"20\" background=\"../img/verticalruler.gif\">&nbsp;</td>\n";
	// START RIGHT PART
	echo	"\t\t<td valign=\"top\">\n";
}

/*=======================================
	        DISPLAY ACTION MESSAGE
=======================================*/

if ($message == true)
{
	Display::display_normal_message($message);
	$display_announcement_list = true;
	$display_form             = false;
}

/*==================================================================================
		   						DISPLAY FORM
==================================================================================*/


	if ($display_form == true)
	{

		// DISPLAY ADD ANNOUNCEMENT COMMAND

		echo "<form method=\"post\" name=\"f1\" action=\"".$_SERVER['PHP_SELF']."?publish_survey=$surveyid&id=$_GET[id]&db_name=$db_name&cidReq=$_GET[cidReq]&action=$_GET[action]\" style=\"margin:0px;\">\n";

		//this variable defines if the course administrator can send a message to a specific user / group
		// or not
		//echo "sessiewaarde: ".$_SESSION['select_groupusers'];
	if(empty($_SESSION['toolgroup'])){
		if ($_SESSION['select_groupusers']=="hide")
		{
			echo "<table><tr><td>";
			echo get_lang("SentTo").": ";
			echo "</td><td>";
			echo get_lang("Everybody");
			echo "</td><td>";
			echo "<input type=\"submit\" name=\"To\" value=\"".get_lang("SelectGroupsUsers")."\" style=\"float:left\">" ;
			echo "</td></tr></table>";
		}

		if ($_SESSION['select_groupusers']=="show")
		{
			echo "<table><tr><td>";
			echo get_lang("SentTo").": ";
			echo "</td><td>";
			echo get_lang('SelectedUsersGroups');
			echo '</td><td>';
			echo "<input type=\"submit\" name=\"To\" value=\"".get_lang("SelectEverybody")."\" style=\"float:left\">" ;
			echo "</td></tr></table>";
			show_to_form($to);
		}

		echo "<br /><br />";

		if (!isset($announcement_to_modify) ) $announcement_to_modify ="";
		if ($announcement_to_modify=='')
		{
			($email_ann=='1')?$checked='checked':$checked='';
			echo "<input class=\"checkbox\" type=checkbox value=\"1\" name=\"email_ann\" $checked> ".get_lang('EmailOption')," : ",
			"<br /><br />";
		}
	}else{
			if (!isset($announcement_to_modify) ) $announcement_to_modify ="";
			if ($announcement_to_modify=='')
			{
				($email_ann=='1' || !empty($surveyid))?$checked='checked':$checked='';
				//echo "<input class=\"checkbox\" type=checkbox value=\"1\" name=\"email_ann\" $checked> ".get_lang('EmailOption')," : ".get_lang('MyGroup'),
				"<br><br>";
			}
		}
		if($surveyid){
			echo	get_lang('EmailAddress').": <input type=\"text\" name=\"emailsAdd\" value=\"".$emails_add."\" size=\"52\">(Comma separated for multiple)<br>";
			echo	get_lang('OnlyThoseAddresses').": <input type=\"checkbox\" name=\"onlyThoseMails\"><br>";
		}
		echo	get_lang('EmailTitle').": <input type=\"text\" name=\"emailTitle\" value=\"".$title_to_modify."\" size=\"52\"><br />";

				unset($title_to_modify);
		    	$title_to_modify = null;


		if (!isset($announcement_to_modify) ) $announcement_to_modify ="";
		if (!isset($content_to_modify) ) 		$content_to_modify ="";
		if (!isset($title_to_modify)) 		$title_to_modify = "";

	    echo	"<br />\n<input type=\"hidden\" name=\"id\" value=\"".$announcement_to_modify."\">";
		if($surveyid){
			$content_to_modify='<br /><a href="'.api_get_path(WEB_CODE_PATH).'/survey/#page#?temp=#temp#&surveyid=#sid#&uid=#uid#&mail=#mail#&db_name=#db_name">'.get_lang('ClickHereToOpenSurvey').'</a><br />
											'.get_lang('OrCopyPasteUrl').' <br />
											'.api_get_path(WEB_CODE_PATH).'/survey/#page#?temp=#temp#&surveyid=#sid#&uid=#uid#&mail=#mail#&db_name=#db_name&nbsp';
		}
	            api_disp_html_area('newContent',$content_to_modify,'250px');

				  echo "<br /><table>",
			           "<tr>",
			           "<td colspan=7>";
			            if ($_SESSION['select_groupusers']=="show")
					   	{
							show_addresource_button("onClick=\"selectAll(this.form.elements[4],true)\"");
					   	}
						else
						{
							show_addresource_button();
						}

					  // sessies
					  $form_elements=$_SESSION['formelements'];

					  echo "</td>",
			               "</tr>";

			          if ($_SESSION['addedresource']){
			          	if(!$surveyid){
				            echo "<tr>";
				            echo "<td colspan='7'>";
				            echo display_resources(0);
				            $test=$_SESSION['addedresource'];
				            echo "</td></tr>";
			          	}
			          }
					  echo "</table>";

		?>
                <br /><input type="Submit" name="submitAnnouncement" value="<?php echo get_lang('Ok') ?>" onclick="selectAll(this.form.elements[4],true)" /><br /><br />

        <?php

				"</form><br />\n";
    } // displayform



/*===============================================
	          DISPLAY ANNOUNCEMENT LIST
===============================================*/


	if ($display_announcement_list && !$surveyid)
	{
		// by default we use the id of the current user. The course administrator can see the announcement of other users by using the user / group filter
		$user_id=$_user['user_id'];
		if ($_SESSION['user']!==null)
		{
			$user_id=$_SESSION['user'];
		}
		if ($_SESSION['group']!==null)
		{
			$group_id=$_SESSION['group'];
		}

		//$group_memberships=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);
		$group_memberships=GroupManager::get_group_ids($_course['dbName'],$_user['user_id']);

		if (api_is_allowed_to_edit() OR api_get_course_setting('allow_user_edit_announcement'))
		{
			// A.1. you are a course admin with a USER filter
			// => see only the messages of this specific user + the messages of the group (s)he is member of.

			if ($_SESSION['user']!==null)
			{
				if (is_array($group_memberships))
				{
					$sql="SELECT
						announcement.*, toolitemproperties.*
						FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
						WHERE announcement.id = toolitemproperties.ref
						AND toolitemproperties.tool='announcement'
						AND	(toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
						ORDER BY display_order DESC";

				}
				else
				{
					$sql="SELECT
						announcement.*, toolitemproperties.*
						FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
						WHERE announcement.id = toolitemproperties.ref
						AND toolitemproperties.tool='announcement'
						AND (toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id='0')
						AND toolitemproperties.visibility='1'
						ORDER BY display_order DESC";

				}
			}

			// A.2. you are a course admin with a GROUP filter
			// => see only the messages of this specific group
			elseif ($_SESSION['group']!==null)
			{
				$sql="SELECT
					announcement.*, toolitemproperties.*
					FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
					WHERE announcement.id = toolitemproperties.ref
					AND toolitemproperties.tool='announcement'
					AND (toolitemproperties.to_group_id=$group_id OR toolitemproperties.to_group_id='0')
					GROUP BY toolitemproperties.ref
					ORDER BY display_order DESC";
			}

			// A.3 you are a course admin without any group or user filter
			else
			{
				// A.3.a you are a course admin without user or group filter but WITH studentview
				// => see all the messages of all the users and groups without editing possibilities

				if ($isStudentView=="true")
				{

					$sql="SELECT
						announcement.*, toolitemproperties.*
						FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
						WHERE announcement.id = toolitemproperties.ref
						AND toolitemproperties.tool='announcement'
						AND toolitemproperties.visibility='1'
						GROUP BY toolitemproperties.ref
						ORDER BY display_order DESC";
				}

				// A.3.a you are a course admin without user or group filter and WTIHOUT studentview (= the normal course admin view)
				// => see all the messages of all the users and groups with editing possibilities
				else
				{
					$sql="SELECT
						announcement.*, toolitemproperties.*
						FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
						WHERE announcement.id = toolitemproperties.ref
						AND toolitemproperties.tool='announcement'
						AND (toolitemproperties.visibility='0' or toolitemproperties.visibility='1')
						GROUP BY toolitemproperties.ref
						ORDER BY display_order DESC";

				}
			}

	}
	else  //STUDENT
	{
		if (is_array($group_memberships))
		{

			$sql="SELECT
				announcement.*, toolitemproperties.*
				FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
				WHERE announcement.id = toolitemproperties.ref
				AND toolitemproperties.tool='announcement'
				AND	(toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
				AND toolitemproperties.visibility='1'
				ORDER BY display_order DESC";

		}
		else
		{
			if ($_user['user_id'])
			{
				$sql="SELECT
					announcement.*, toolitemproperties.*
					FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
					WHERE announcement.id = toolitemproperties.ref
					AND toolitemproperties.tool='announcement'
					AND (toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id='0')
					AND toolitemproperties.visibility='1'
					ORDER BY display_order DESC";

			}
			else
			{
				$sql="SELECT
					announcement.*, toolitemproperties.*
					FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
					WHERE announcement.id = toolitemproperties.ref
					AND toolitemproperties.tool='announcement'
					AND toolitemproperties.to_group_id='0'
					AND toolitemproperties.visibility='1'";

			}
		}
	}


		$result = api_sql_query($sql,__FILE__,__LINE__);

		$num_rows = mysql_num_rows($result);


		/*=================================================
		               DISPLAY: NO ITEMS
 		=================================================*/

		if ($num_rows == 0)
		{
			echo "<table><tr><td>".get_lang('NoAnnouncements')."</td></tr></table>";
		}

		$iterator = 1;

		$bottomAnnouncement = $announcement_number;


		echo "\t\t\t<table width=\"100%\" border=\"1\" cellpadding=\"5\" cellspacing=\"0\"  id=\"agenda_list\">\n";

		$displayed=array();

		while ($myrow = mysql_fetch_array($result))
		{
			if (!in_array($myrow['id'], $displayed))
			{
				$title		 = $myrow['title'];
				$content	 = $myrow['content'];

				$content     = make_clickable($content);
				$content     = text_filter($content);


				/*================================================
								       DATE
				================================================*/

				$last_post_datetime = $myrow['end_date'];

				list($last_post_date, $last_post_time) = split(" ", $last_post_datetime);
				list($year, $month, $day) = explode("-", $last_post_date);
				list($hour, $min) = explode(":", $last_post_time);
				$announceDate = mktime($hour, $min, 0, $month, $day, $year);

				// the styles
				if ($myrow['visibility']=='0')
				{
					$style="data_hidden";
					$stylenotbold="datanotbold_hidden";
					$text_style="text_hidden";
				}
				else
				{
					$style="data";
					$stylenotbold="datanotbold";
					$text_style="text";
				}

				echo	"\t\t\t\t<tr class=\"".$style."\">";


				/*===================================================================
											THE ICONS
				===================================================================*/
				echo "\t\t\t\t\t<td>\n";
				// anchoring
				echo "<a name=\"".(int)($myrow["id"])."\"></a>\n";
				// User or group icon
				if ($myrow['to_group_id']!== '0' and $myrow['to_group_id']!== 'NULL')
				{
					echo "\t\t\t\t\t\t<img alt=\"$alt_ug\" src='../img/group.gif'>\n";
				}
				// the email icon
				if ($myrow['email_sent'] == '1')
				{
					echo "\t\t\t\t\t\t<img alt=\"$alt_mail\" src='../img/email.png'>\n";
				}
				echo "\t\t\t\t\t</td>\n";
				/*==================================================================
											TITLE
				==================================================================*/

				echo "\t\t\t\t\t<td>".$title."</td>\n";


				/*==================================================================
											SENT TO
				===================================================================*/

				echo "\t\t\t\t\t<td class=\"".$stylenotbold."\">" . get_lang("SentTo") . " : &nbsp; ";
				$sent_to=sent_to("announcement", $myrow['id']);
				// echo $myrow['id'];

				$sent_to_form=sent_to_form($sent_to);
				echo $sent_to_form,

				"\t\t\t\t\t</td>\n",
				"\t\t\t\t</tr>\n";


				/*=========================================================
											TITLE
				=========================================================*/
				echo "\t\t\t\t<tr>\n",
				"\t\t\t\t\t<td class=\"announcements_datum\" colspan=\"3\">",

				get_lang('AnnouncementPublishedOn')," : ",ucfirst(format_locale_date($dateFormatLong,strtotime($last_post_date))),



				"</td>\n",
				"\t\t\t\t</tr>\n",


				/*=========================================================
										CONTENT
				=========================================================*/

				"\t\t\t\t<tr class=\"$text_style\">\n",
				"\t\t\t\t\t<td colspan=\"3\">\n",

				$content,

				"\t\t\t\t\t</td>\n",
				"\t\t\t\t</tr>\n",


				/*========================================================
										RESOURCES
				========================================================*/

				"<tr>\n",
				"<td colspan=\"3\">\n";


				if (check_added_resources("Ad_Valvas", $myrow["id"]))
				{
					echo "<i>".get_lang('AddedResources')."</i><br />";
					display_added_resources("Ad_Valvas", $myrow["id"]);
				}

				echo   "<br />";


				if(api_is_allowed_to_edit())
				{
					/*=====================================================================
												SHOW MOD/DEL/VIS FUNCTIONS
					=====================================================================*/
					echo "<table><tr>";
					echo	"<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?action=modify&id=".$myrow['id']."\">",
							"<img src=\"../img/edit.gif\" title=\"",get_lang('Modify'),"\" border=\"0\" align=\"absmiddle\">",
							"</a></td>";


					if (api_is_allowed_to_edit()) echo "<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?action=delete&id=".$myrow['id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."')) return false;\">",
							"<img src=\"../img/delete.gif\" title=\"",get_lang('Delete'),"\" border=\"0\" align=\"absmiddle\">",
							"</a></td>";

							if ($myrow['visibility']==1)
							{
									$image_visibility="visible";
							}
							else
							{
									$image_visibility="invisible";
							}

							echo 	"<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?origin=".$_GET['origin']."&action=showhide&id=".$myrow['id']."\">",
									"<img src=\"../img/".$image_visibility.".gif\" border=\"0\" alt=\"".get_lang('Visible')."\"/></a></td>";




							// DISPLAY MOVE UP COMMAND only if it is not the top announcement
							if($iterator != 1)
							{

							echo	"<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?up=",$myrow["id"],"\">",
									"<img src=../img/up.gif border=0 title=\"".get_lang('Up')."\" align=\"absmiddle\">",
									"</a></td>";
							}


							if($iterator < $bottomAnnouncement)
							{

							echo	"<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?down=".$myrow["id"]."\">",
									"<img src=\"../img/down.gif\" border=\"0\" title=\"".get_lang('Down')."\" align=\"absmiddle\">",
									"</a></td>";
							}

							echo "</tr></table>";



					echo	"</td>\n",
							"</tr>\n";

					$iterator ++;
				} // is_allowed_to_edit

				echo "<tr><td width=\"100%\" colspan=\"3\"><a href=\"#top\"><img src=\"../img/top.gif\" border=\"0\" alt=\"To top\" align=\"right\"></a></td></tr>";
			}
			$displayed[]=$myrow['id'];
		}	// end while ($myrow = mysql_fetch_array($result))

		echo "</table>";

}	// end: if ($displayAnnoucementList)

echo "</table>";

if ($display_specific_announcement) display_announcement($announcement_id);

/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($_GET['origin'] !== 'learnpath')
{
	//we are not in learnpath tool
	Display::display_footer();
}
?>