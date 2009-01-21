<?php // $Id: new_message.php 17903 2009-01-21 19:50:57Z juliomontoya $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)		

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

    Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
    Mail: info@dokeos.com
==============================================================================
*/
/**
* This script shows a compose area (wysiwyg editor if supported, otherwise
* a simple textarea) where the user can type a message.
* There are three modes
* - standard: type a message, select a user to send it to, press send
* - reply on message (when pressing reply when viewing a message)
* - send to specific user (when pressing send message in the who is online list)
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file= 'messages';
$cidReset=true;
include_once('../inc/global.inc.php');

api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}


require_once(api_get_path(LIBRARY_PATH).'message.lib.php');
require_once(api_get_path(LIBRARY_PATH).'/text.lib.php');
require_once(api_get_path(LIBRARY_PATH).'/formvalidator/FormValidator.class.php');
$table_message = Database::get_course_table(TABLE_MESSAGE);

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$htmlHeadXtra[]='
<script language="javascript">
function validate(form,list)
{
	if(list.selectedIndex<0)
	{
    	alert("Please select someone to send the message to.")
    	return false
	}
	else
    	return true
}

</script>';
$nameTools = get_lang('ComposeMessage');

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* Shows the compose area + a list of users to select from.
*/
function show_compose_to_any($user_id)
{
	$online_user_list = get_online_user_list($user_id);
	$default['user_list'] = $user_id;
	manage_form($default, $online_user_list);
}

function show_compose_reply_to_message($message_id, $receiver_id)
{
	global $table_message;
	$query = "SELECT * FROM $table_message WHERE user_receiver_id=".$receiver_id." AND id='".$message_id."';";
	$result = api_sql_query($query,__FILE__,__LINE__);
	$row = Database::fetch_array($result);

	if(!isset($row[1]))
	{
		echo get_lang('InvalidMessageId');
		die();
	}
	
	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($row[1]).'</strong>';
	
	$default['title'] =get_lang('EnterTitle');
	$default['user_list'] = $row[1];

	manage_form($default);
}

function show_compose_to_user($receiver_id)
{
	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($receiver_id).'</strong>';
	$default['title'] = get_lang('EnterTitle');
	$default['user_list'] = $receiver_id;
	manage_form($default);
}

function manage_form($default, $select_from_user_list = null)
{
	global $table_message;
	
	$form = new FormValidator('compose_message');
	if (isset($select_from_user_list))
	{
		$form->addElement('select', 'user_list', get_lang('SendMessageTo'), $select_from_user_list);
	} else {
		$form->addElement('hidden', 'user_list');
	}
	$form->add_textfield('title', get_lang('Title'));
	$form->add_html_editor('content', '',false,false);
	$form->addElement('submit', 'compose', get_lang('Send'));
	$form->setDefaults($default);

	if($form->validate()) {
		$values = $form->exportValues();
		$receiver_user_id = $values['user_list'];
		$title = $values['title'];
		$content = $values['content'];
		//all is well, send the message
		send_message($receiver_user_id, $title, $content);
		display_success_message($receiver_user_id);
	} else {
		$form->display();
	}
}

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/
$interbreadcrumb[] = array ('url' => 'inbox.php', 'name' => get_lang('Messages'));
Display::display_header($nameTools, get_lang('ComposeMessage'));
api_display_tool_title($nameTools);
if(!isset($_POST['compose'])) {
	if(isset($_GET['re_id'])) {
		$message_id = $_GET['re_id'];
		$receiver_id = api_get_user_id();
		show_compose_reply_to_message($message_id, $receiver_id);
	} elseif(isset($_GET['send_to_user'])) {
		show_compose_to_user($_GET['send_to_user']);
	} else {
		show_compose_to_any($_user['user_id']);
  	}
} else {
	if(api_get_user_id() && isset($_POST['user_list']) && isset($_POST['content']))	{
		$default['title'] = $_POST['title'];
		$default['user_list'] = $_POST['user_list'];
		manage_form($default);
	} else {
		Display::display_error_message(get_lang('ErrorSendingMessage'));
	}
}


/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>