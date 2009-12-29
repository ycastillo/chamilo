<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This class provides methods for messages management.
*	Include/require it in your code to use its features.
*
*	@package dokeos.library
==============================================================================
*/


require_once api_get_path(LIBRARY_PATH).'online.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

/* 
 * @todo use constants!
 */
define('MESSAGE_STATUS_NEW',				'0');
define('MESSAGE_STATUS_UNREAD',				'1');
//2 ??
define('MESSAGE_STATUS_DELETED',			'3');
define('MESSAGE_STATUS_OUTBOX',				'4');
define('MESSAGE_STATUS_INVITATION_PENDING',	'5');
define('MESSAGE_STATUS_INVITATION_ACCEPTED','6');
define('MESSAGE_STATUS_INVITATION_DENIED',	'7');

class MessageManager
{
	public static function get_online_user_list($current_user_id) {
		$min=30;
		global $_configuration;
		$userlist = WhoIsOnline($current_user_id,$_configuration['statistics_database'],$min);
		foreach($userlist as $row) {
			$receiver_id = $row[0];
			$online_user_list[$receiver_id] = GetFullUserName($receiver_id).($current_user_id==$receiver_id?("&nbsp;(".get_lang('Myself').")"):(""));
		}
		return $online_user_list;
	}

	/**
	* Displays info stating that the message is sent successfully.
	*/
	public static function display_success_message($uid) {
			global $charset;
		if ($_SESSION['social_exist']===true) {
			$redirect="#remote-tab-2";
			if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>";
			}else {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>";
			}
		} else {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>";
		}
		Display::display_confirmation_message(api_xml_http_response_encode($success), false);
	}

	/**
	* Displays the wysiwyg html editor.
	*/
	public static function display_html_editor_area($name, $resp) {
		api_disp_html_area($name, get_lang('TypeYourMessage'), '', '', null, array('ToolbarSet' => 'Messages', 'Width' => '95%', 'Height' => '250'));
	}

	/**
	* Get the new messages for the current user from the database.
	*/
	public static function get_new_messages() {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		if (!api_get_user_id()) {
			return false;
		}
		$i=0;
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".api_get_user_id()." AND msg_status=".MESSAGE_STATUS_UNREAD;
		$result = Database::query($query,__FILE__,__LINE__);
		$i = Database::num_rows($result);
		return $i;
	}

	/**
	* Get the list of user_ids of users who are online.
	*/
	public static function users_connected_by_id() {
		global $_configuration, $_user;
		$minute=30;
		$user_connect = WhoIsOnline($_user['user_id'],$_configuration['statistics_database'],$minute);
		for ($i=0; $i<count($user_connect); $i++) {
			$user_id_list[$i]=$user_connect[$i][0];
		}
		return $user_id_list;
	}

	/**
	 * Gets the total number of messages, used for the inbox sortable table
	 */
	public static function get_number_of_messages () {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE msg_status IN (0,1) AND user_receiver_id=".api_get_user_id();
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}

	/**
	 * Gets information about some messages, used for the inbox sortable table
	 * @param int $from
	 * @param int $number_of_items
	 * @param string $direction
	 */
	public static function get_message_data ($from, $number_of_items, $column, $direction) {
		global $charset;
		$from = intval($from);
		$number_of_items = intval($number_of_items);
		$column = intval($column);
		if (!in_array($direction, array('ASC', 'DESC')))
			$direction = 'ASC';

		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, msg_status as col4 FROM $table_message " .
					 "WHERE user_receiver_id=".api_get_user_id()." AND msg_status IN (0,1)" .
					 "ORDER BY send_date desc, col$column $direction LIMIT $from,$number_of_items";
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="id[]">';
			 } else {
				$message[0] = ($result[0]);
			 }

			if ($request===true) {
				if($result[4]==0) {
					$message[1] = Display::return_icon('mail_open.png',get_lang('AlreadyReadMessage'));//Message already read
				} else {
					$message[1] = Display::return_icon('mail.png',get_lang('UnReadMessage'));//Message without reading
				}

				$message[2] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[1]).'</a>';
				$message[3] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '<a onclick="reply_to_messages(\'show\','.$result[0].',\'\')" href="javascript:void(0)">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a onclick="delete_one_message('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				if($result[4]==1) {
					$class = 'class = "unread"';
				} else {
					$class = 'class = "read"';
				}
				
				$link = '';
				
				if ($_GET['f']=='social') {
					$link = '&f=social';
				}

				$message[2] = '<a '.$class.' href="view_message.php?id='.$result[0].$link.'">'.GetFullUserName(($result[1])).'</a>';;
				$message[3] = '<a '.$class.' href="view_message.php?id='.$result[0].$link.'">'.$result[2].'</a>';
				$message[5] = '<a href="new_message.php?re_id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a delete_one_message('.$result[0].') href="inbox.php?action=deleteone&id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			}
			$message[4] = ($result[3]); //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = api_xml_http_response_encode($value);
			}
			$message_list[] = $message;

			$i++;
		}
		return $message_list;
	}

	/**
	 * save message for social network 
	 * @param int 	  receiver user id
	 * @param string  subject
	 * @param string  content
	 * @param array   attachment files array($_FILES) (optional)
	 * @param array   comments about attachment files (optional)
	 * @param int     group id (optional)
	 * @param int     parent id (optional)
	 * @param int 	  message id for updating the message (optional)
	 * @return bool
	 */	 
	public static function send_message ($receiver_user_id, $subject, $content, $file_attachments = array(), $file_comments = array(), $group_id = 0, $parent_id = 0, $edit_message_id = 0) {	
        global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$group_id = intval($group_id);
        $receiver_user_id = intval($receiver_user_id);
        $parent_id = intval($parent_id);
		$user_sender_id = api_get_user_id();
		
		$total_filesize = 0;
		if (is_array($file_attachments)) {
			foreach ($file_attachments as $file_attach) {
				$total_filesize += $file_attach['size'];
			}
		}
		
		// validating fields
		if (empty($subject)) {
			return get_lang('YouShouldWriteASubject');
		} else if ($total_filesize > intval(api_get_setting('message_max_upload_filesize'))) {			
			return sprintf(get_lang("FilesSizeExceedsX"),format_file_size(api_get_setting('message_max_upload_filesize')));
		}
		
        if (!empty($receiver_user_id) || !empty($group_id)) {
        	// message for user friend
	        $subject = api_convert_encoding($subject,$charset);
	        $subject = Database::escape_string($subject);
	        $content = api_convert_encoding($content,$charset);
	        $content = Database::escape_string($content);
			
			//useless query
			//echo $sql = "SELECT COUNT(*) as count FROM $table_message WHERE user_sender_id = ".$user_sender_id." AND user_receiver_id='$receiver_user_id' AND title = '$title' AND content ='$content' AND group_id = '$group_id' AND parent_id = '$parent_id'";		
			//$res_exist = Database::query($sql,__FILE__,__LINE__);
			//$row_exist = Database::fetch_array($res_exist,'ASSOC');
			
			//We should ALWAYS sent emails
			//if ($row_exist['count'] == 0) {
			
			//message in inbox for user friend			
			if ($edit_message_id) {				
				$query = " UPDATE $table_message SET update_date = '".date('Y-m-d H:i:s')."', title = '$subject', content = '$content' WHERE id = '$edit_message_id' ";				 
				$result = Database::query($query,__FILE__,__LINE__);				
				$inbox_last_id = $edit_message_id;					
			} else {
				$query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
					 " VALUES ('$user_sender_id', '$receiver_user_id', '1', '".date('Y-m-d H:i:s')."','$subject','$content','$group_id','$parent_id', '".date('Y-m-d H:i:s')."')";
				$result = Database::query($query,__FILE__,__LINE__);				
				$inbox_last_id = Database::insert_id();	
			}
						
			// save attachment file for inbox messages
			if (is_array($file_attachments)) {
				$i = 0;
				foreach ($file_attachments as $file_attach) {						
					if ($file_attach['error'] == 0) {
						self::save_message_attachment_file($file_attach,$file_comments[$i],$inbox_last_id,null,$receiver_user_id,$group_id);
					}
					$i++;	
				}
			}
				
			if (empty($group_id)) {	
				//message in outbox for user friend or group
				$sql = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
						 " VALUES ('$user_sender_id', '$receiver_user_id', '4', '".date('Y-m-d H:i:s')."','$subject','$content', '$group_id', '$parent_id', '".date('Y-m-d H:i:s')."')";
				$rs = Database::query($sql,__FILE__,__LINE__);
				$outbox_last_id = Database::insert_id();
				
				// save attachment file for outbox messages
				if (is_array($file_attachments)) {
					$o = 0;
					foreach ($file_attachments as $file_attach) {						
						if ($file_attach['error'] == 0) {
							self::save_message_attachment_file($file_attach,$file_comments[$o],$outbox_last_id,$user_sender_id);
						}
						$o++;
					}
				}
			}
			return $result;
        }
		return false;
	}

	/**
	 * Update parent ids for other receiver user from current message in groups
	 * @author Christian Fasanando Flores 
	 * @param  int	parent id
	 * @param  int	receiver user id
	 * @param  int	message id
	 * @return void
	 */
	public static function update_parent_ids_from_reply($parent_id,$receiver_user_id,$message_id) {
		
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$parent_id = intval($parent_id);		
		$receiver_user_id = intval($receiver_user_id);
		$message_id = intval($message_id);
		// first get data from message id (parent)
		$sql_message= "SELECT * FROM $table_message WHERE id = '$parent_id'";	
		$rs_message	= Database::query($sql_message,__FILE__,__LINE__);
		$row_message= Database::fetch_array($rs_message);
		
		// get message id from data found early for other receiver user
		$sql_msg_id	= " SELECT id FROM $table_message WHERE user_sender_id ='{$row_message[user_sender_id]}' 
				 		AND title='{$row_message[title]}' AND content='{$row_message[content]}' AND group_id='{$row_message[group_id]}' AND user_receiver_id='$receiver_user_id'";									
		$rs_msg_id	= Database::query($sql_msg_id,__FILE__,__LINE__);
		$row = Database::fetch_array($rs_msg_id);			
		
		// update parent_id for other user receiver
		$sql_upd = "UPDATE $table_message SET parent_id = '{$row[id]}' WHERE id = '$message_id'";		
		Database::query($sql_upd,__FILE__,__LINE__);			
	}
	
	public static function delete_message_by_user_receiver ($user_receiver_id,$id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		if ($id != strval(intval($id))) return false;
		$user_receiver_id = intval($user_receiver_id);
		$id = Database::escape_string($id);
		$sql="SELECT * FROM $table_message WHERE id=".$id." AND msg_status<>4;";
		$rs=Database::query($sql,__FILE__,__LINE__);
		
		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_receiver_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_receiver_id=".$user_receiver_id." AND id=".$id;
			//$query = "DELETE FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".$id;
			$result = Database::query($query,__FILE__,__LINE__);			
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * Set status deleted
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @return array
	 */
	public static function delete_message_by_user_sender ($user_sender_id,$id) {
		if ($id != strval(intval($id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		
		$id = intval($id);
		$user_sender_id = intval($user_sender_id);
		
		$sql="SELECT * FROM $table_message WHERE id='$id'";
		$rs=Database::query($sql,__FILE__,__LINE__);
		
		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_sender_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_sender_id='$user_sender_id' AND id='$id'";
			//$query = "DELETE FROM $table_message WHERE user_sender_id='$user_sender_id' AND id='$id'";
			$result = Database::query($query,__FILE__,__LINE__);					
			return $result;		
		}				
		return false;
	}
	
	/**
	 * Saves a message attachment files 
	 * @param  array 	$_FILES['name']
	 * @param  string  	a comment about the uploaded file
	 * @param  int		message id
	 * @param  int		receiver user id (optional)
	 * @param  int		sender user id (optional)
	 * @param  int		group id (optional) 
	 * @return void
	 */
	public static function save_message_attachment_file($file_attach,$file_comment,$message_id,$receiver_user_id=0,$sender_user_id=0,$group_id=0) {

		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

		// Try to add an extension to the file if it hasn't one
		$new_file_name = add_ext_on_mime(stripslashes($file_attach['name']), $file_attach['type']);
		
		// user's file name
		$file_name =$file_attach['name'];
		if (!filter_extension($new_file_name))  {
			Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
		} else {
			$new_file_name = uniqid('');						

			$message_user_id = '';
			if (!empty($receiver_user_id)) {
				$message_user_id = $receiver_user_id;
			} else {
				$message_user_id = $sender_user_id;
			}			
			
			// User-reserved directory where photos have to be placed.
			
			if (!empty($group_id)) {
				$path_user_info = GroupPortalManager::get_group_picture_path_by_id($group_id, 'system', true);
			} else {
				$path_user_info = UserManager::get_user_picture_path_by_id($message_user_id, 'system', true);	
			}

			$path_message_attach = $path_user_info['dir'].'message_attachments/';
					
			// If this directory does not exist - we create it.
			if (!file_exists($path_message_attach)) {
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm) ? $perm : '0770');
				@mkdir($path_message_attach, $perm, true);
			}				
			$new_path=$path_message_attach.$new_file_name;						
			if (is_uploaded_file($file_attach['tmp_name'])) {
				$result= @copy($file_attach['tmp_name'], $new_path);
			}							
			$safe_file_comment= Database::escape_string($file_comment);
			$safe_file_name = Database::escape_string($file_name);
			$safe_new_file_name = Database::escape_string($new_file_name);						
			// Storing the attachments if any			
			$sql="INSERT INTO $tbl_message_attach(filename,comment, path,message_id,size)
				  VALUES ( '$safe_file_name', '$safe_file_comment', '$safe_new_file_name' , '$message_id', '".$file_attach['size']."' )";
			$result=Database::query($sql, __LINE__, __FILE__);	
		}	
	}

	/**
	 * Delete message attachment files (logically updating the row with a suffix _DELETE_id)
	 * @param  int	message id
	 * @param  int	message user id (receiver user id or sender user id) 
	 * @param  int	group id (optional) 
	 * @return void
	 */
	public static function delete_message_attachment_file($message_id,$message_uid,$group_id=0) {

		$message_id = intval($message_id);		
		$message_uid = intval($message_uid);
		$table_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		
		$sql= "SELECT * FROM $table_message_attach WHERE message_id = '$message_id'";
		$rs	= Database::query($sql,__FILE__,__LINE__);		
		$new_paths = array();
		while ($row = Database::fetch_array($rs)) {
			$path 		= $row['path'];
			$attach_id  = $row['id'];			
			$new_path 	= $path.'_DELETED_'.$attach_id;
			
			if (!empty($group_id)) {
				$path_user_info = GroupPortalManager::get_group_picture_path_by_id($group_id, 'system', true);
			} else {
				$path_user_info = UserManager::get_user_picture_path_by_id($message_uid, 'system', true);	
			}

			$path_message_attach = $path_user_info['dir'].'message_attachments/';					
			if (is_file($path_message_attach.$path)) {				
				if(rename($path_message_attach.$path, $path_message_attach.$new_path)) {					
					$sql_upd = "UPDATE $table_message_attach set path='$new_path' WHERE id ='$attach_id'";
					$rs_upd = Database::query($sql_upd,__FILE__,__LINE__);					
				} 							
			}						
		}				
	}	
	
	/**
	 * update messages by user id and message id  
	 * @param  int		user id
	 * @param  int		message id
	 * @return resource
	 */
	public static function update_message ($user_id, $message_id) {
		if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "UPDATE $table_message SET msg_status = '0' WHERE msg_status<>4 AND user_receiver_id=".intval($user_id)." AND id='".intval($message_id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
	}

	/**
	 * get messages by user id and message id  
	 * @param  int		user id
	 * @param  int		message id
	 * @return array
	 */
	 public static function get_message_by_user ($user_id,$message_id) {
	 	if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".intval($user_id)." AND id='".intval($message_id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
		return $row = Database::fetch_array($result);
	}
	
	/**
	 * get messages by group id  
	 * @param  int		group id
	 * @return array
	 */
	public static function get_messages_by_group($group_id) {	 	
		if ($group_id != strval(intval($group_id))) return false;		
	 	$table_message = Database::get_main_table(TABLE_MESSAGE);
	 	$current_uid = api_get_user_id();
	 	$group_id = intval($group_id);	 			
		$query = "SELECT * FROM $table_message WHERE group_id=$group_id AND msg_status <> ".MESSAGE_STATUS_OUTBOX." ORDER BY id";		
		$rs = Database::query($query,__FILE__,__LINE__);		
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$data[] = $row;
			}		
		}
		return $data;
	}
	
	/**
	 * get messages by parent id optionally with limit  
	 * @param  int		parent id
	 * @param  int		group id (optional)
	 * @param  int		offset (optional)
	 * @param  int		limit (optional) 
	 * @return array
	 */
	public static function get_messages_by_parent($parent_id,$group_id = '',$offset = 0,$limit = 0) {
		if ($parent_id != strval(intval($parent_id))) return false;		
	 	$table_message = Database::get_main_table(TABLE_MESSAGE);
	 	$current_uid = api_get_user_id();
	 	$parent_id = intval($parent_id);	 			
	 	
	 	$condition_group_id = "";
	 	if ($group_id !== '') {
	 		$group_id = intval($group_id);
	 		$condition_group_id = " AND group_id = '$group_id' ";
	 	}

	 	$condition_limit = "";	 	
	 	if ($offset && $limit) {
	 		$offset = ($offset - 1) * $limit;  		
	 		$condition_limit = " LIMIT $offset,$limit ";	
	 	}
	 	
		$query = "SELECT * FROM $table_message WHERE parent_id='$parent_id' AND msg_status <> ".MESSAGE_STATUS_OUTBOX." $condition_group_id ORDER BY send_date DESC $condition_limit ";
		$rs = Database::query($query,__FILE__,__LINE__);		
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$data[$row['id']] = $row;
			}		
		}
		return $data;
	}
	
	/**
	 * Gets information about if exist messages
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @return boolean
	 */
	 public static function exist_message ($user_id, $id) {
	 	if ($id != strval(intval($id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT id FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
		$num = Database::num_rows($result);
		if ($num>0)
			return true;
		else
			return false;
	}
	/**
	 * Gets information about messages sent
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @param  string
	 * @return array
	 */
	 public static function get_message_data_sent ($from, $number_of_items, $column, $direction) {
	 	global $charset;

	 	$from = intval($from);
		$number_of_items = intval($number_of_items);
		$column = intval($column);
		if (!in_array($direction, array('ASC', 'DESC')))
			$direction = 'ASC';

		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, user_receiver_id as col4, msg_status as col5 FROM $table_message " .
					 "WHERE user_sender_id=".api_get_user_id()." AND msg_status=".MESSAGE_STATUS_OUTBOX." " .
					 "ORDER BY col$column $direction LIMIT $from,$number_of_items";

		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="out[]">';
			 } else {
				$message[0] = ($result[0]);
			 }
			 
			$class = 'class = "read"';
			
			if ($request===true) {
			   if ($result[5]==4) {
			   		$message[1] = Display::return_icon('mail_send.png',get_lang('MessageSent'));//Message Sent
			   }
			   
				$message[2] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '&nbsp;&nbsp;<a onclick="delete_one_message_outbox('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				$link = '';
				if ($_GET['f']=='social') {
					$link = '&f=social';
				}
				
				$message[2] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].$link.'">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].$link.'">'.$result[2].'</a>';
				$message[5] = '<a href="outbox.php?action=deleteone&id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			}
			$message[4] = $result[3]; //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = $value;
			}
			$message_list[] = $message;
			$i++;
		}
		return $message_list;
	}
	/**
	 * Gets information about number messages sent
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param void
	 * @return integer
	 */
	 public static function get_number_of_messages_sent () {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE msg_status=".MESSAGE_STATUS_OUTBOX." AND user_sender_id=".api_get_user_id();
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}
	
	/**
	 * display message box in the inbox 
	 * @param int the message id
	 * @param string inbox or outbox strings are available
	 * @return string html with the message content
	 */
	public static function show_message_box($message_id, $source = 'inbox') {
		$table_message 		= Database::get_main_table(TABLE_MESSAGE);
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		$message_id = intval($message_id);	
		
		if ($source == 'outbox') {
			if (isset($message_id) && is_numeric($message_id)) {
				$query	= "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".$message_id." AND msg_status=4;";
				$result = Database::query($query,__FILE__,__LINE__);
			    $path	= 'outbox.php';		    
			}
		} else {	
			if (is_numeric($message_id) && !empty($message_id)) {
				$query = "UPDATE $table_message SET msg_status = '".MESSAGE_STATUS_NEW."' WHERE user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query,__FILE__,__LINE__);
				
				$query = "SELECT * FROM $table_message WHERE msg_status<>4 AND user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query,__FILE__,__LINE__);
			}			
			$path='inbox.php';			
		}

		$row = Database::fetch_array($result);
		
		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id,$source);
		
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;

		$message_content =  '
		<table class="message_view_table" >
		    <tr>
		      <td width=10>&nbsp; </td>
		      <td vAlign=top width="100%">
		      	<table>
		            <tr>
		              <td width="100%">
		               <h1>'.str_replace("\\","",$row[5]).'</h1>
		              </td>
		              <tr>';
			if (api_get_setting('allow_social_tool') == 'true') {
				$user_image = '';
				/*	@todo add user image
				$user_image = UserManager::get_user_picture_path_by_id($row[1],'web', true,false);				
				$user_image = UserManager::get_picture_user($row[1], $user_image['file'],'40');
				$user_image = '<img src="'.$user_image['file'].'" style="'.$user_image['style'].'" >';
				*/
				if ($source == 'outbox') {
					$message_content .='<td>'.get_lang('From').' '.$user_image.'<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$row[1].'">'.GetFullUserName($row[1]).'</a> '.api_strtolower(get_lang('To')).'&nbsp;<b>'.GetFullUserName($row[2]).'</b> </TD>';
				} else {
					$message_content .='<td>'.get_lang('From').' '.$user_image.'<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$row[1].'">'.GetFullUserName($row[1]).'</a> '.api_strtolower(get_lang('To')).'&nbsp;<b>'.get_lang('Me').'</b> </TD>';
				}
				
			} else {
				if ($source == 'outbox') {
					$message_content .='<td>'.get_lang('From').'&nbsp;'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).' <b>'.GetFullUserName($row[2]).'</b> </TD>';
				} else {
					$message_content .='<td>'.get_lang('From').'&nbsp;'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).' <b>'.get_lang('Me').'</b> </TD>';	
				}
			}
		
		 $message_content .='</tr>
		              <tr>
		              <td>'.get_lang('Date').'&nbsp; '.$row[4].'</TD>
		              </tr>
		            </tr>
		        </table>
		        <br />
		        <table height=209 width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top class="view-message-content">'.str_replace("\\","",$row[6]).'</TD>
		            </TR>
		          </TBODY>
		        </table>
		        <div id="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>				        		
		        <DIV class=HT style="PADDING-BOTTOM: 5px">';
		    $social_link = '';
		    if ($_GET['f'] == 'social') {
		    	$social_link = 'f=social';
		    }
		    if ($source == 'outbox') {
		    	$message_content .= '<a href="outbox.php?'.$social_link.'">'.Display::return_icon('back.png',get_lang('ReturnToOutbox')).get_lang('ReturnToOutbox').'</a> &nbsp';
		    } else {		    	
		    	$message_content .= '<a href="inbox.php?'.$social_link.'">'.Display::return_icon('back.png',get_lang('ReturnToInbox')).get_lang('ReturnToInbox').'</a> &nbsp';
		    	$message_content .= '<a href="new_message.php?re_id='.$message_id.'&'.$social_link.'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).get_lang('ReplyToMessage').'</a> &nbsp';
		    }			
			$message_content .= '<a href="inbox.php?action=deleteone&id='.$message_id.'&'.$social_link.'" >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).''.get_lang('DeleteMessage').'</a>&nbsp';

			$message_content .='</DIV></TD>
		      <TD width=10></TD>
		    </TR>
		</table>';
		return $message_content;
	}
	
	
	/**
	 * display message box sent showing it into outbox 
	 * @return void
	 */
	public static function show_message_box_sent () {		
		global $charset;		
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		
		$message_id = '';
		if (is_numeric($_GET['id_send'])) {
			$query = "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".intval(Database::escape_string($_GET['id_send']))." AND msg_status=4;";
			$result = Database::query($query,__FILE__,__LINE__);
			$message_id = intval($_GET['id_send']);
		}
		$path='outbox.php';

		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id,'outbox');
	
		$row = Database::fetch_array($result);
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;
		echo '<div class=actions>';
		echo '<a onclick="close_and_open_outbox()" href="javascript:void(0)">'.Display::return_icon('folder_up.gif',api_xml_http_response_encode(get_lang('BackToOutbox'))).api_xml_http_response_encode(get_lang('BackToOutbox')).'</a>';
		echo '<a onclick="delete_one_message_outbox('.$row[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',api_xml_http_response_encode(get_lang('DeleteMessage'))).api_xml_http_response_encode(get_lang('DeleteMessage')).'</a>';
		echo '</div><br />';
		echo '
		<table class="message_view_table" >
		    <TR>
		      <TD width=10>&nbsp; </TD>
		      <TD vAlign=top width="100%">
		      	<TABLE>
		            <TR>
		              <TD width="100%">
		                    <TR> <h1>'.str_replace("\\","",api_xml_http_response_encode($row[5])).'</h1></TR>
		              </TD>
		              <TR>
		              	<TD>'.api_xml_http_response_encode(get_lang('From').'&nbsp;<b>'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).'&nbsp;  <b>'.GetFullUserName($row[2])).'</b> </TD>
		              </TR>
		              <TR>
		              <TD >'.api_xml_http_response_encode(get_lang('Date').'&nbsp; '.$row[4]).'</TD>
		              </TR>
		            </TR>
		        </TABLE>
		        <br />
		        <TABLE height=209 width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top>'.str_replace("\\","",api_xml_http_response_encode($row[6])).'</TD>
		            </TR>
		          </TBODY>
		        </TABLE>
		        <div id="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>		
		        <DIV class=HT style="PADDING-BOTTOM: 5px"> </DIV></TD>
		      <TD width=10>&nbsp;</TD>
		    </TR>
		</TABLE>';
	}
	
	/**
	 * get user id by user email
	 * @param string $user_email
	 * @return int user id
	 */
	public static function get_user_id_by_email ($user_email) {
		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql='SELECT user_id FROM '.$tbl_user.' WHERE email="'.Database::escape_string($user_email).'";';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs,'ASSOC');
		if (isset($row['user_id'])) {
			return $row['user_id'];
		} else {
			return null;
		}
	}
	
	/**
	 * Displays messages of a group with nested view 
	 * @param int group id
	 */
	public static function display_messages_for_group($group_id) {
						
		$rows = self::get_messages_by_group($group_id);						
		$rows = self::calculate_children($rows);
		$group_info = GroupPortalManager::get_group_data($group_id);
		$current_user_id = api_get_user_id();		
		$count=0;
		$html = '';
		if (is_array($rows) && count($rows)> 0) {

				// prepare array for topics with its items
				$topics = array();
				$x = 0;		
				foreach ($rows as $index=>$value) {					
					if (empty($value['parent_id'])) {
						$x = $index; 						
						$topics[$x] = $value;											
					} else {
						$topics[$x]['items'][] = $value;
					}
				}			
				uasort($topics,array('MessageManager','order_desc_date'));

				// pager
				$page  = isset($_GET['page_nr'])?intval($_GET['page_nr']):1;
				$total_topics = count($topics);
				$topics_per_page = 5;
				$pager = self::get_pager_for_message_group($group_id,$page,$total_topics,$topics_per_page);			
				
				
				// topics and items
				$parents = array_keys(self::get_messages_by_parent(0,$group_id,$page,$topics_per_page));
				$html .= '<div id="boxmyGroupMessages">';
				$html .= '	<div class="pager">
							<table width="690px">
							<tr><td style="width:25%">&nbsp;</td><td style="text-align:center">'.$pager['details'].'</td><td style="text-align:right;width:25%">'.$pager['links'].'</td></tr></table></div>';	
				foreach ($topics as $index => $topic) {
					
					if (!in_array($index,$parents)) continue;	
					
					// topics
					$indent	= 0;
					$user_sender_info = UserManager::get_user_info_by_id($topic['user_sender_id']);
					$files_attachments = self::get_links_message_attachment_files($topic['id']);
					$name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);
					
					$html .= '<div class="groupPost" >';
						$html .= '<div>'.Display::return_icon('content-post-group1.jpg').'</div>';
						$html .= '<div class="contentPostGroup">';
						$html .= '<a href="#" class="head" id="head_'.$topic['id'].'">																
									<span class="message-group-title-topic">'.((isset($_GET['div_id']) && $_GET['div_id'] == 'content_'.$topic['id'])?Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align: middle')):
									Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align: middle'))).'
									'.$topic['title'].'</span>';						
						$html .= '</a>';
						
						if ($topic['send_date']!=$topic['update_date']) {
							if (!empty($topic['update_date']) && $topic['update_date'] != '0000-00-00 00:00:00' ) {						
								$html .= '<span> ('.get_lang('LastUpdate').' '.date_to_str_ago($topic['update_date']).')</span>';
							}	
						} else {
								$html .= '<span> ('.get_lang('Created').' '.date_to_str_ago($topic['send_date']).')</span>';
						}			
				 						 				
						$html .= '<div id="content_'.$topic['id'].'" >';
							$html .= '<a name="content_'.$topic['id'].'"></a>';
							$html.= '<div style="margin-bottom:10px">';
								$html.= '<div id="message-reply-link" style="margin-right:10px">
										<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=reply_message_group&div_id=content_'.$topic['id'].'&page_nr='.Security::remove_XSS($_GET['page_nr']).'&page_item_nr='.Security::remove_XSS($_GET['page_item_nr']).'" class="thickbox" title="'.get_lang('Reply').'">'.Display :: return_icon('forumthread_new.gif', get_lang('Reply')).'</a>';								
								if ($topic['user_sender_id'] == $current_user_id) {
									$html.= '&nbsp;&nbsp;<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=edit_message_group&div_id=content_'.$topic['id'].'&page_nr='.Security::remove_XSS($_GET['page_nr']).'&page_item_nr='.Security::remove_XSS($_GET['page_item_nr']).'" class="thickbox" title="'.get_lang('Edit').'">'.Display :: return_icon('edit.gif', get_lang('Edit')).'</a>';
								}								
								$html.=	'</div>';
								$html.= '<br />';											
								$html.= '<div class="message-group-author">'.get_lang('From').'&nbsp;<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp;</a></div>';		
								$html.= '<div class="message-group-content">'.$topic['content'].'</div>';													
								$html.= '<div class="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>';						
						$html.= '</div>';
							
					// items												
					if (is_array($topic['items'])) {

						// pager items						
						$page_item  = isset($_GET['page_item_nr'])?intval($_GET['page_item_nr']):1;
						$total_items = count($topic['items']);
						$items_per_page = 3;
						$div_content = 'content_'.$topic['id'];
						$pager_items = self::get_pager_for_message_group($group_id,$page_item,$total_items,$items_per_page,true,$div_content);			
						$html .= '	<div class="pager">
									<table width="100%">
									<tr><td style="width:25%">&nbsp;</td><td style="text-align:center">'.$pager_items['details'].'</td><td style="text-align:right;width:25%">'.$pager_items['links'].'</td></tr></table></div>';

						$topic_slice['items'] = array_slice($topic['items'],($page_item-1)*($items_per_page),$items_per_page);						
								
						foreach ($topic_slice['items'] as $item) {
							
							$indent	= $item['indent_cnt']*'15';
							$user_sender_info = UserManager::get_user_info_by_id($item['user_sender_id']);
							$files_attachments = self::get_links_message_attachment_files($item['id']);
							$name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);
							
							
							//$html.= '<div class="message-item" style="margin-left: '.$indent.'px;">';
							$html.= '<div id="contentPostReply" >';	
								$html .= '<div>'.Display::return_icon('content-post-reply01.jpg').'</div>';
								$html .= '<div class="contentPostReplybg">';
								$html.= '<div id="message-reply-link">
										<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&message_id='.$item['id'].'&action=reply_message_group&div_id=content_'.$topic['id'].'&page_nr='.Security::remove_XSS($_GET['page_nr']).'&page_item_nr='.Security::remove_XSS($_GET['page_item_nr']).'" class="thickbox" title="'.get_lang('Reply').'">'.Display :: return_icon('forumthread_new.gif', get_lang('Reply')).'</a>';
								if ($item['user_sender_id'] == $current_user_id) {
									$html.= '&nbsp;&nbsp;<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$item['id'].'&action=edit_message_group&div_id=content_'.$topic['id'].'&page_nr='.Security::remove_XSS($_GET['page_nr']).'&page_item_nr='.Security::remove_XSS($_GET['page_item_nr']).'" class="thickbox" title="'.get_lang('Edit').'">'.Display :: return_icon('edit.gif', get_lang('Edit')).'</a>';
								} 	
								$html.= '</div>';
								$html.= '<div class="message-group-title">'.$item['title'].'&nbsp;</div>';												
								$html.= '<div class="message-group-author">'.get_lang('From').'&nbsp;<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$item['user_sender_id'].'">'.$name.'&nbsp;</a></div>';		
								$html.= '<div class="message-group-content">'.$item['content'].'</div>';
								
								if ($item['send_date'] != $item['update_date']) {
									if (!empty($item['update_date']) && $item['update_date'] != '0000-00-00 00:00:00' ) {						
										$html .= '<span class="message-group-date"> '.get_lang('LastUpdate').' '.date_to_str_ago($item['update_date']).'</span>';
									}	
								} else {
									$html .= '<span class="message-group-date"> '.get_lang('Created').' '.date_to_str_ago($item['send_date']).'</span>';
								}	
								$html.= '<div class="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>';
								$html.= '</div>';						
							$html.= '</div>';																
						}	
						
						/*
						// pager items
						$html .= '	<div class="pager">
							<table width="100%">
							<tr><td style="width:25%">&nbsp;</td><td>&nbsp;</td><td style="text-align:right;width:25%">'.$pager_items['links'].'</td></tr></table></div>';
						*/
																	
					}
							$html .= '</div>';
						$html .= '</div>';	
					$html .= '</div>';	
				}
				$html .= '</div>';
				
				/*
				// pager
				$html .= '	<div class="pager">
							<table width="700px">
							<tr><td style="width:25%">&nbsp;</td><td>&nbsp;</td><td style="text-align:right;width:25%">'.$pager['links'].'</td></tr></table></div>';
				*/
		}
		return $html;
	}

	/**
	 *  Get pager for messages of group
	 *  @param int 		group id
	 *  @param int 		current page
	 *  @param int 		total rows
	 *  @param int 		rows per page
	 *  @return array 	pager with details and links
	 */
	public static function get_pager_for_message_group($group_id,$page,$num_rows,$rows_per_page,$pager_items = false,$div_content='') {
		
		$link 		= '';
		$details	= '';
		$pager 		= array();
		$group_id 	= intval($group_id);
		$page 		= intval($page);
		$num_rows 	= intval($num_rows);
		$first_page = 1;
		$last_page 	= ceil($num_rows/$rows_per_page);
		
		// get details		
		if ($page == $first_page) {
			$details = $page.' - '.($page*$rows_per_page).' / '.$num_rows;	
		} else if ($page > $first_page && $page < $last_page) {
			$details = ((($page-1)*$rows_per_page)+1).' - '.($page*$rows_per_page).' / '.$num_rows;
		} else {
			$details = ((($page-1)*$rows_per_page)+1).' - '.($num_rows).' / '.$num_rows;
		}		
		$pager['details'] = $details;
		
		
		// get links for pager	
		$href = api_get_path(WEB_CODE_PATH).'social/groups.php?id='.$group_id;
		
		if ($pager_items) {
			$page_topic  = isset($_GET['page_nr'])?intval($_GET['page_nr']):1;
			$param_name_page_nr = '&page_nr='.$page_topic.'&div_id='.$div_content.'&page_item_nr=';
		} else {
			$param_name_page_nr = '&page_nr=';
		}
		
		if ($page > 1) {									
			$link .= '<a title="'.get_lang('FirstPage').'" href="'.$href.$param_name_page_nr.$first_page.'">';
			$link .= Display::return_icon('first.png',get_lang('FirstPage'),array('style'=>'vertical-align: middle'));
			$link .= '</a>';
			$link .= '<a title="'.get_lang('PreviousPage').'" href="'.$href.$param_name_page_nr.($page-1).'">';
			$link .= Display::return_icon('prev.png',get_lang('PreviousPage'),array('style'=>'vertical-align: middle'));
	 		$link .= '</a>';	
		}
		$link .= $page.'/'.$last_page;
		
		if ($page < $last_page) {
			$link .= '<a title="'.get_lang('NextPage').'" href="'.$href.$param_name_page_nr.($page+1).'">';
			$link .= Display::return_icon('next.png',get_lang('NextPage'),array('style'=>'vertical-align: middle'));
			$link .= '</a>';
			$link .= '<a title="'.get_lang('LastPage').'" href="'.$href.$param_name_page_nr.$last_page.'">';
			$link .= Display::return_icon('last.png',get_lang('PreviousPage'),array('style'=>'vertical-align: middle'));
	 		$link .= '</a>';	
		}
		$pager['links'] = $link;
 		return $pager;
	}

	
	/**
	 * Add children to messages by id is used for nested view messages  
	 * @param array  rows of messages 
	 * @return array new list adding the item children
	 */	
	public static function calculate_children($rows) {

		foreach($rows as $row) {
			$rows_with_children[$row["id"]]=$row;
			$rows_with_children[$row["parent_id"]]["children"][]=$row["id"];
		}		
		$rows=$rows_with_children;
		$sorted_rows=array(0=>array());
		self::message_recursive_sort($rows, $sorted_rows);
		unset($sorted_rows[0]);
		return $sorted_rows;
	}
	
	/**
	 * Sort recursively the messages, is used for for nested view messages   
	 * @param array  original rows of messages
	 * @param array  list recursive of messages
	 * @param int   seed for calculate the indent
	 * @param int   indent for nested view 
	 * @return void
	 */	
	public static function message_recursive_sort($rows, &$messages, $seed=0, $indent=0) {
		if($seed>0) {
			$messages[$rows[$seed]["id"]]=$rows[$seed];
			$messages[$rows[$seed]["id"]]["indent_cnt"]=$indent;
			$indent++;
		}	
		if(isset($rows[$seed]["children"])) {
			foreach($rows[$seed]["children"] as $child) {
				self::message_recursive_sort($rows, $messages, $child, $indent);
			}
		}
	}
	
	/**
	 * Sort date by desc from a multi-dimensional array    
	 * @param array1  first array to compare
	 * @param array2  second array to compare 
	 * @return bool
	 */	
	public function order_desc_date($array1,$array2) {
		return strcmp($array2['send_date'],$array1['send_date']);	
	}
	
	/**
	 * Get array of links (download) for message attachment files    
	 * @param int  		message id 
	 * @param string	type message list (inbox/outbox)
	 * @return array 
	 */	
	public static function get_links_message_attachment_files($message_id,$type='') {
		
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		$message_id = intval($message_id);
		
		// get file attachments by message id
		$links_attach_file = array();
		if (!empty($message_id)) {
			
			$sql = "SELECT * FROM $tbl_message_attach WHERE message_id = '$message_id'";		
				
			$rs_file = Database::query($sql,__FILE__,__LINE__);				
			if (Database::num_rows($rs_file) > 0) {
				$attach_icon = Display::return_icon('attachment.gif');

				$archiveURL=api_get_path(WEB_CODE_PATH).'messages/download.php?type='.$type.'&file=';				
				
				while ($row_file = Database::fetch_array($rs_file)) {
					$archiveFile= $row_file['path'];
					$filename 	= $row_file['filename'];
					$filesize 	= format_file_size($row_file['size']);
					$filecomment= $row_file['comment'];
					$links_attach_file[] = $attach_icon.'&nbsp;<a href="'.$archiveURL.$archiveFile.'">'.$filename.'</a>&nbsp;('.$filesize.')'.(!empty($filecomment)?'&nbsp;-&nbsp;'.$filecomment:''); 
				}
			}						
		}		
		return $links_attach_file;		
	}
	
	/**
	 * Get message list by id    
	 * @param int  message id 
	 * @return array  
	 */	
	public static function get_message_by_id($message_id) {
		$tbl_message = Database::get_main_table(TABLE_MESSAGE);
		$message_id = intval($message_id);				
		$sql = "SELECT * FROM $tbl_message WHERE id = '$message_id'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$item = array(); 
		if (Database::num_rows($res)>0) {
			$item = Database::fetch_array($res,'ASSOC');
		}
		return $item;
	}	
}


//@todo this functions should be in the message class

function inbox_display() {
	global $charset;	
//	$charset = api_get_setting('platform_charset');
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();
	if ($_SESSION['social_exist']===true) {
		$redirect="#remote-tab-2";
		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
			$success= get_lang('SelectedMessagesDeleted');
		} else {
			$success= get_lang('SelectedMessagesDeleted');
		}
	} else {
		$success= get_lang('SelectedMessagesDeleted');
	}
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
    			$number_of_selected_messages = count($_POST['id']);
    			foreach ($_POST['id'] as $index => $message_id) {
    				MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
    			}
    			Display::display_normal_message(api_xml_http_response_encode($success),false);
    			break;
			case 'deleteone' :
    			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
    			Display::display_confirmation_message(api_xml_http_response_encode($success),false);
    			echo '<br />';
    			break;
		}
	}
	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages_mask', 'get_message_data_mask', 3, get_number_of_messages_mask(),'DESC');
	$table->set_header(0, '', false,array ('style' => 'width:20px;'));
	$title=api_xml_http_response_encode(get_lang('Title'));
	$action=api_xml_http_response_encode(get_lang('Actions'));
	$table->set_header(1,api_xml_http_response_encode(get_lang('Status')),false,array('style' => 'width:30px;'));
	$table->set_header(2,api_xml_http_response_encode(get_lang('From')),false);
	$table->set_header(3,$title,false);
	$table->set_header(4,api_xml_http_response_encode(get_lang('Date')),false,array('style' => 'width:150px;'));
	$table->set_header(5,$action,false,array ('style' => 'width:100px;'));
	
	if ($_REQUEST['f']=='social') {
		$parameters['f'] = 'social';
		$table->set_additional_parameters($parameters);
	}	
	
    echo '<div id="div_content_table_data">';
	if ($request===true) {
		echo '<form name="form_send" id="form_send" action="" method="post">';
		echo '<input type="hidden" name="action" value="delete" />';
		$table->display();
		echo '</form>';
		if (get_number_of_messages_mask() > 0) {
			echo '<a href="javascript:void(0)" onclick="selectall_cheks()">'.api_xml_http_response_encode(get_lang('SelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			echo '<a href="javascript:void(0)" onclick="unselectall_cheks()">'.api_xml_http_response_encode(get_lang('UnSelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			echo '<button class="save" name="delete" type="button" value="'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'" onclick="submit_form(\'inbox\')">'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'</button>';

		}
	} else {
		$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
		$table->display();
	}
    echo '</div>';
}
function get_number_of_messages_mask() {
	return MessageManager::get_number_of_messages();
}
function get_message_data_mask($from, $number_of_items, $column, $direction) {
	$column='3';
	$direction='desc';
	//non set by SortableTable ?
	$number_of_items=get_number_of_messages_mask();
	return MessageManager::get_message_data($from, $number_of_items, $column, $direction);
}
function outbox_display() {
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();
	global $charset;
	
	$social_link = false;
	if ($_REQUEST['f']=='social') {
		$social_link ='f=social';
	}
	
	
	if ($_SESSION['social_exist']===true) {	
		
		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
			$success= get_lang('SelectedMessagesDeleted')."&nbsp<br><a href=\""."../social/index.php?$social_link\">".get_lang('BackToOutbox')."</a>";
		}else {
			$success=get_lang('SelectedMessagesDeleted')."&nbsp<br><a href=\""."../social/index.php?$social_link\">".get_lang('BackToOutbox')."</a>";
		}
	} else {
		$success= get_lang('SelectedMessagesDeleted').'&nbsp</b><br /><a href="outbox.php?'.$social_link.'">'.get_lang('BackToOutbox').'</a>';
	}
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
			$number_of_selected_messages = count($_POST['id']);
			if ($number_of_selected_messages!=0) {
				foreach ($_POST['id'] as $index => $message_id) {
					MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
				}
			}
			Display::display_normal_message(api_xml_http_response_encode($success),false);
			break;
			case 'deleteone' :
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
			Display::display_confirmation_message(api_xml_http_response_encode($success),false);
			echo '<br/>';
			break;
		}
	}

			
	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages_send_mask', 'get_message_data_send_mask', 3, get_number_of_messages_send_mask(), 'DESC');
	$title=api_xml_http_response_encode(get_lang('Title'));
	$action=api_xml_http_response_encode(get_lang('Actions'));
	$table->set_header(0, '', false,array ('style' => 'width:20px;'));
	$table->set_header(1, api_xml_http_response_encode(get_lang('Status')),false,array ('style' => 'width:30px;'));
	$table->set_header(2, api_xml_http_response_encode(get_lang('To')),false);
	$table->set_header(3, $title,false);
	$table->set_header(4, api_xml_http_response_encode(get_lang('Date')),false,array ('style' => 'width:150px;'));
	$table->set_header(5,$action, false,array ('style' => 'width:100px;'));

		
	if ($_REQUEST['f']=='social') {
		$parameters['f'] = 'social';
		$table->set_additional_parameters($parameters);
	}	
	echo '<div id="div_content_table_data_sent">';
	
		if ($request===true) {
			echo '<form name="form_send_out" id="form_send_out" action="" method="post">';
			
			echo '<input type="hidden" name="action" value="delete" />';
				
			$table->display();
			echo '</form>';
			if (get_number_of_messages_send_mask() > 0) {
				echo '<a href="javascript:void(0)" onclick="selectall_cheks()">'.api_xml_http_response_encode(get_lang('SelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
				echo '<a href="javascript:void(0)" onclick="unselectall_cheks()">'.api_xml_http_response_encode(get_lang('UnSelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
				echo '<button class="save" name="delete" type="button" value="'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'" onclick="submit_form(\'outbox\')">'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'</button>';
			}
		} else {
			$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
			$table->display();
		}
	echo '</div>';
}
function get_number_of_messages_send_mask() {
	return MessageManager::get_number_of_messages_sent();
}
function get_message_data_send_mask($from, $number_of_items, $column, $direction) {
	$column='3';
	$direction='desc';
	//non set by SortableTable ?
	$number_of_items=get_number_of_messages_send_mask();
	return MessageManager::get_message_data_sent($from, $number_of_items, $column, $direction);
}
?>
