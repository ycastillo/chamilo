<?php
/* For licensing terms, see /chamilo_license.txt */

// name of the language file that needs to be included
$language_file = array('registration','messages','userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

api_block_anonymous_users();
if (isset($_GET['messages_page_nr'])) {
	if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
		header('Location:../social/index.php?pager="'.Security::remove_XSS($_GET['messages_page_nr']).'"&remote=2#remote-tab-2');
	}
}
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
$htmlHeadXtra[]='<script language="javascript">
<!--
function enviar(miforma)
{
	if(confirm("'.get_lang("SureYouWantToDeleteSelectedMessages").'"))
		miforma.submit();
}
function select_all(formita)
{
   for (i=0;i<formita.elements.length;i++)
	{
      		if(formita.elements[i].type == "checkbox")
				formita.elements[i].checked=1
	}
}
function deselect_all(formita)
{
   for (i=0;i<formita.elements.length;i++)
	{
      		if(formita.elements[i].type == "checkbox")
				formita.elements[i].checked=0
	}
}
//-->
</script>';

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$nameTools = get_lang('Messages');
$request=api_is_xml_http_request();
if (isset($_GET['form_reply']) || isset($_GET['form_delete'])) {
	/***********************************************/
	$info_reply=array();
	$info_delete=array();
	/***********************************************/
	if ( isset($_GET['form_reply']) ) {
		//allow to insert messages
		$info_reply=explode(base64_encode('&%ff..x'),$_GET['form_reply']);
		$count_reply=count($info_reply);
		$button_sent=urldecode($info_reply[4]);
	}
	/***********************************************/
	if ( isset($_GET['form_delete']) ) {
		//allow to delete messages
		$info_delete=explode(',',$_GET['form_delete']);
		$count_delete=(count($info_delete)-1);
	}
	/***********************************************/

	if ( isset($button_sent) ) {
		$title     = api_convert_encoding(urldecode($info_reply[0]),'UTF-8',$charset);
		$content   = api_convert_encoding(str_replace("\\","",urldecode($info_reply[1])),'UTF-8',$charset);
		$title     = Security::remove_XSS($title);
		$content   = Security::remove_XSS($content,COURSEMANAGERLOWSECURITY);

		$user_reply= $info_reply[2];
		$user_email_base=str_replace(')','(',$info_reply[5]);
		$user_email_prepare=explode('(',$user_email_base);
		if (count($user_email_prepare)==1) {
			$user_email=trim($user_email_prepare[0]);
		} elseif (count($user_email_prepare)==3) {
			$user_email=trim($user_email_prepare[1]);
		}
		$user_id_by_email=MessageManager::get_user_id_by_email($user_email);

		if ($info_reply[6]=='save_form') {
			$user_id_by_email=$info_reply[2];
		}
		if ( isset($user_reply) && !is_null($user_id_by_email) && strlen($info_reply[0]) >0) {
			MessageManager::send_message($user_id_by_email, $title, $content);
			MessageManager::display_success_message($user_id_by_email);
			inbox_display();
			exit;
		} elseif (is_null($user_id_by_email)) {
			$message_box=get_lang('ErrorSendingMessage');
			Display::display_error_message(api_xml_http_response_encode($message_box),false);
			inbox_display();
			exit;
		}
	} elseif (trim($info_delete[0])=='delete' ) {
		for ($i=1;$i<=$count_delete;$i++) {
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $info_delete[$i]);
		}
			$message_box=get_lang('SelectedMessagesDeleted');
			Display::display_normal_message(api_xml_http_response_encode($message_box),false);
		   	inbox_display();
		    exit;
	}
}


$link_ref="new_message.php";
$table_message = Database::get_main_table(TABLE_MESSAGE);


//api_display_tool_title(api_xml_http_response_encode(get_lang('Inbox')));
if ($_GET['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'outbox.php','name' => get_lang('Inbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'outbox.php','name' => get_lang('Inbox'));
}

Display::display_header('');

if ($_GET['f']=='social') {
	require_once api_get_path(LIBRARY_PATH).'social.lib.php';
	SocialManager::show_social_menu();
	echo '<div class="actions-title">';
	echo get_lang('Messages');
	echo '</div>';
} else {
	//comes from normal profile
	echo '<div class=actions>';
		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',api_xml_http_response_encode(get_lang('Inbox'))).api_xml_http_response_encode(get_lang('Inbox')).'</a>';
		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',api_xml_http_response_encode(get_lang('ComposeMessage'))).api_xml_http_response_encode(get_lang('ComposeMessage')).'</a>';
		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',api_xml_http_response_encode(get_lang('Outbox'))).api_xml_http_response_encode(get_lang('Outbox')).'</a>';
	echo '</div>';	
}
	

if (!isset($_GET['del_msg'])) {	
	inbox_display();
} else {
	$num_msg = intval($_POST['total']);
	for ($i=0;$i<$num_msg;$i++) {
		if($_POST[$i]) {
			//the user_id was necesarry to delete a message??
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_POST['_'.$i]);
		}
	}
	inbox_display();
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($request===false) {
	Display::display_footer();
}
?>
