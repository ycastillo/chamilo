<?php
/* For licensing terms, see /license.txt */

/*	EXTRA FUNCTIONS FOR DOCUMENTS TOOL */

/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 *
 * @param array 	An array containing the folders we want to be able to select
 * @param string	The current folder (path inside of the "document" directory, including the prefix "/")
 * @param string	Group directory, if empty, prevents documents to be uploaded (because group documents cannot be uploaded in root)
 * @param	boolean	Whether to change the renderer (this will add a template <span> to the QuickForm object displaying the form)
 * @return string html form
 */
function build_directory_selector($folders, $curdirpath, $group_dir = '', $change_renderer = false) {
	$folder_titles = array();
	if (api_get_setting('use_document_title') == 'true') {
		if (is_array($folders)) {
			$escaped_folders = array();
			foreach ($folders as $key => & $val) {
				$escaped_folders[$key] = Database::escape_string($val);
			}
			$folder_sql = implode("','", $escaped_folders);
			$doc_table = Database::get_course_table(TABLE_DOCUMENT);
			$sql = "SELECT * FROM $doc_table WHERE filetype='folder' AND path IN ('".$folder_sql."')";
			$res = Database::query($sql);
			$folder_titles = array();
			while ($obj = Database::fetch_object($res)) {
				$folder_titles[$obj->path] = $obj->title;
			}
		}
	} else {
		if (is_array($folders)) {
			foreach ($folders as & $folder) {
				$folder_titles[$folder] = basename($folder);
			}
		}
	}

	require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
	$form = new FormValidator('selector', 'POST', api_get_self());

	$parent_select = $form->addElement('select', 'curdirpath', get_lang('CurrentDirectory'), '', 'onchange="javascript: document.selector.submit();"');

	if ($change_renderer) {
		$renderer = $form->defaultRenderer();
		$renderer->setElementTemplate('<span>{label} : {element}</span> ','curdirpath');
	}

	// Group documents cannot be uploaded in the root
	if (empty($group_dir)) {
		$parent_select -> addOption(get_lang('HomeDirectory'), '/');
		if (is_array($folders)) {
			foreach ($folders as & $folder) {
				$selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
				$path_parts = explode('/', $folder);
				$folder_titles[$folder] = cut($folder_titles[$folder], 80);
				$label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$folder_titles[$folder];
				$parent_select -> addOption($label, $folder);
				if ($selected != '') {
					$parent_select->setSelected($folder);
				}
			}
		}
	} else {
		foreach ($folders as & $folder) {
			$selected = ($curdirpath==$folder) ? ' selected="selected"' : '';
			$label = $folder_titles[$folder];
			if ($folder == $group_dir) {
				$label = '/ ('.get_lang('HomeDirectory').')';
			} else {
				$path_parts = explode('/', str_replace($group_dir, '', $folder));
				$label = cut($label, 80);
				$label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
			}
			$parent_select -> addOption($label, $folder);
			if ($selected != '') {
				$parent_select->setSelected($folder);
			}
		}
	}

	$form = $form->toHtml();

	return $form;
}

/**
 * Create a html hyperlink depending on if it's a folder or a file
 *
 * @param string $www
 * @param string $title
 * @param string $path
 * @param string $filetype (file/folder)
 * @param int $visibility (1/0)
 * @param int $show_as_icon - if it is true, only a clickable icon will be shown
 * @return string url
 */
function create_document_link($www, $title, $path, $filetype, $size, $visibility, $show_as_icon = false) {
	global $dbl_click_id;
	if (isset($_SESSION['_gid'])) {
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	} else {
		$req_gid = '';
	}
	$url_path = urlencode($path);
	// Add class="invisible" on invisible files
	$visibility_class = ($visibility == 0) ? ' class="invisible"' : '';

	if (!$show_as_icon) {
		// Build download link (icon)
		$forcedownload_link = ($filetype == 'folder') ? api_get_self().'?'.api_get_cidreq().'&action=downloadfolder&amp;path='.$url_path.$req_gid : api_get_self().'?'.api_get_cidreq().'&amp;action=download&amp;id='.$url_path.$req_gid;
		// Folder download or file download?
		$forcedownload_icon = ($filetype == 'folder') ? 'folder_zip.gif' : 'filesave.gif';
		// Prevent multiple clicks on zipped folder download
		$prevent_multiple_click = ($filetype == 'folder') ? " onclick=\"javascript: if(typeof clic_$dbl_click_id == 'undefined' || !clic_$dbl_click_id) { clic_$dbl_click_id=true; window.setTimeout('clic_".($dbl_click_id++)."=false;',10000); } else { return false; }\"":'';
	}

	$target = '_self';
	if ($filetype == 'file') {
		// Check the extension
		$ext = explode('.', $path);
		$ext = strtolower($ext[sizeof($ext) - 1]);
		// "htmlfiles" are shown in a frameset
		if ($ext == 'htm' || $ext == 'html' || $ext == 'gif' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'pdf' || $ext == 'swf' || $ext == 'mp3') {
			$url = 'showinframes.php?'.api_get_cidreq().'&amp;file='.$url_path.$req_gid;
		} else {
			// url-encode for problematic characters (we may not call them dangerous characters...)
			$path = str_replace('%2F', '/',$url_path).'?'.api_get_cidreq();
			$url = $www.$path;
		}
		// Files that we want opened in a new window
		if ($ext == 'txt' || $ext == 'log' || $ext == 'css' || $ext == 'js') { // Add here
			$target = '_blank';
		}
	} else {
		$url = api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$url_path.$req_gid;
	}

	// The little download icon
	//$tooltip_title = str_replace('?cidReq='.$_GET['cidReq'], '', basename($path));
	$tooltip_title = explode('?', basename($path));
	$tooltip_title = $tooltip_title[0];

	$tooltip_title_alt = $tooltip_title;
	if ($tooltip_title == 'shared_folder') {
		$tooltip_title_alt = get_lang('SharedFolder');
	}elseif(strstr($tooltip_title, 'shared_folder_session_')) {
		$tooltip_title_alt = get_lang('SharedFolder').' ('.api_get_session_name($current_session_id).')';
	}elseif(strstr($tooltip_title, 'sf_user_')) {
		$userinfo = Database::get_user_info_from_id(substr($tooltip_title, 8));
		$tooltip_title_alt = get_lang('SharedFolder').' ('.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).')';
	}


	if (!$show_as_icon) {
		if ($filetype == 'folder') {
			if (api_is_allowed_to_edit() || api_is_platform_admin() || api_get_setting('students_download_folders') == 'true') {
				//filter when I am into shared folder, I can show for donwload only my shared folder
				$current_session_id=api_get_session_id();
				if(is_shared_folder($_GET['curdirpath'],$current_session_id))
				{
					if (preg_match('/shared_folder\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link))|| preg_match('/shared_folder_session_'.$current_session_id.'\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin())
					{
					  $force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array('height'=>'16', 'width' => '16')).'</a>';
					}
				}
				elseif(!preg_match('/shared_folder/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin())
				{
					$force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array('height'=>'16', 'width' => '16')).'</a>';
				}
			}
		} else {
			$force_download_html = ($size==0)?'':'<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array('height'=>'16', 'width' => '16')).'</a>';
		}
		return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.$title.'</a>'.$force_download_html;
	} else {
		if(preg_match('/shared_folder/', urldecode($url)) && preg_match('/shared_folder$/', urldecode($url))==false){
			return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $tooltip_title).Display::return_icon('shared.png', get_lang('ResourceShared'), array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22)).'</a>';
		}
		else
		{
		return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $tooltip_title).'</a>';
		}
	}
}

/**
 * Builds an img html tag for the filetype
 *
 * @param string $type (file/folder)
 * @param string $path
 * @return string img html tag
 */
function build_document_icon_tag($type, $path) {
	$basename = basename($path);
	$current_session_id = api_get_session_id();
	$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

	if ($type == 'file') {
		$icon = choose_image($basename);
	} else {
		if ($basename == 'shared_folder') {
			$icon = 'shared_folder.gif';
			if ($is_allowed_to_edit) {
				$basename = get_lang('HelpSharedFolder');
			} else {
				$basename = get_lang('SharedFolder');
			}
		}elseif(strstr($basename, 'shared_folder_session_')) {
			if ($is_allowed_to_edit) {
				$basename = '***('.api_get_session_name($current_session_id).')*** '.get_lang('HelpSharedFolder');
			} else {
				$basename = get_lang('SharedFolder').' ('.api_get_session_name($current_session_id).')';
			}
			$icon = 'shared_folder.gif';
		}elseif(strstr($basename, 'sf_user_')) {
			$userinfo = Database::get_user_info_from_id(substr($basename, 8));
			$image_path = UserManager::get_user_picture_path_by_id(substr($basename, 8), 'web', false, true);

			if ($image_path['file'] == 'unknown.jpg') {
				$icon = $image_path['file'];
			} else {
				$icon = '../upload/users/'.substr($basename, 8).'/'.$image_path['file'];
			}

			$basename = get_lang('SharedFolder').' ('.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).')';

		} else {
			if (($basename =='audio' || $basename =='flash' || $basename =='images' || $basename =='video') && api_is_allowed_to_edit()) {
				$basename = get_lang('HelpDefaultDirDocuments');
			}
			$icon = 'folder_document.gif';
		}
	}

	return Display::return_icon($icon, $basename, array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22));
}

/**
 * Creates the row of edit icons for a file/folder
 *
 * @param string $curdirpath current path (cfr open folder)
 * @param string $type (file/folder)
 * @param string $path dbase path of file/folder
 * @param int $visibility (1/0)
 * @param int $id dbase id of the document
 * @return string html img tags with hyperlinks
 */
function build_edit_icons($curdirpath, $type, $path, $visibility, $id, $is_template, $is_read_only = 0, $session_id = 0) {
	if (isset($_SESSION['_gid'])) {
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	} else {
		$req_gid = '';
	}
	// Build URL-parameters for table-sorting
	$sort_params = array();
	if (isset($_GET['column'])) {
		$sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
	}
	if (isset($_GET['page_nr'])) {
		$sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
	}
	if (isset($_GET['per_page'])) {
		$sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
	}
	if (isset($_GET['direction'])) {
		$sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
	}
	$sort_params = implode('&amp;', $sort_params);
	$visibility_icon = ($visibility == 0) ? 'invisible' : 'visible';
	$visibility_command = ($visibility == 0) ? 'set_visible' : 'set_invisible';
	$curdirpath = urlencode($curdirpath);

	$is_certificate_mode = DocumentManager::is_certificate_mode($path);
	$modify_icons = '';
	$cur_ses = api_get_session_id();
	// If document is read only *or* we're in a session and the document
	// is from a non-session context, hide the edition capabilities
	if ($is_read_only /*or ($session_id!=$cur_ses)*/) {
		$modify_icons = Display::return_icon('edit_na.gif', get_lang('Modify'));
		$modify_icons .= '&nbsp;'.Display::return_icon('delete_na.gif', get_lang('Delete'));
		$modify_icons .= '&nbsp;'.Display::return_icon('deplacer_fichier_na.gif', get_lang('Move'));
		$modify_icons .= '&nbsp;'.Display::return_icon($visibility_icon.'_na.gif', get_lang('VisibilityCannotBeChanged'));
	} else {
		if ($is_certificate_mode) {
			$modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;file='.urlencode($path).$req_gid.'&selectcat='.$gradebook_category.'"><img src="../img/edit.gif" border="0" title="'.get_lang('Modify').'" alt="" /></a>';
		} else {
			$modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;file='.urlencode($path).$req_gid.'"><img src="../img/edit.gif" border="0" title="'.get_lang('Modify').'" alt="" /></a>';
		}

        if (in_array($path, array('/audio', '/flash', '/images', '/shared_folder', '/video', '/chat_files', '/certificates'))) {
        	$modify_icons .= '&nbsp;'.Display::return_icon('delete_na.gif',get_lang('ThisFolderCannotBeDeleted'));
        } else {

			if (isset($_GET['curdirpath']) && $_GET['curdirpath']=='/certificates' && DocumentManager::get_default_certificate_id(api_get_course_id())==$id) {

        		$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'delete_certificate_id='.$id.'&selectcat='.$gradebook_category.' " onclick="return confirmation(\''.basename($path).'\');"><img src="../img/delete.gif" border="0" title="'.get_lang('Delete').'" alt="" /></a>';
			} else {
				if ($is_certificate_mode) {
        			$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'&selectcat='.$gradebook_category.'" onclick="return confirmation(\''.basename($path).'\');"><img src="../img/delete.gif" border="0" title="'.get_lang('Delete').'" alt="" /></a>';
				} else {
					$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'" onclick="return confirmation(\''.basename($path).'\');"><img src="../img/delete.gif" border="0" title="'.get_lang('Delete').'" alt="" /></a>';
				}
			}
        }

        if ($is_certificate_mode) {
        	$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;move='.urlencode($path).$req_gid.'&selectcat='.$gradebook_category.'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="" /></a>';
        	$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;'.$visibility_command.'='.$id.$req_gid.'&amp;'.$sort_params.'&selectcat='.$gradebook_category.'"><img src="../img/'.$visibility_icon.'.gif" border="0" title="'.get_lang('Visible').'" alt="" /></a>';
        } else {
        	$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;move='.urlencode($path).$req_gid.'"><img src="../img/deplacer_fichier.gif" border="0" title="'.get_lang('Move').'" alt="" /></a>';
        	$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;'.$visibility_command.'='.$id.$req_gid.'&amp;'.$sort_params.'"><img src="../img/'.$visibility_icon.'.gif" border="0" title="'.get_lang('Visible').'" alt="" /></a>';
        }
	}

	if ($type == 'file' && pathinfo($path, PATHINFO_EXTENSION) == 'html') {
		if ($is_template == 0) {
			if ((isset($_GET['curdirpath']) && $_GET['curdirpath']<>'/certificates') || !isset($_GET['curdirpath'])) {
					$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;add_as_template='.$id.$req_gid.'&amp;'.$sort_params.'"><img src="../img/wizard_small.gif" border="0" title="'.get_lang('AddAsTemplate').'" alt="'.get_lang('AddAsTemplate').'" /></a>';
				}
				if (isset($_GET['curdirpath']) && $_GET['curdirpath']=='/certificates') {//allow attach certificate to course
					  $visibility_icon_certificate='nocertificate';
					  if (DocumentManager::get_default_certificate_id(api_get_course_id())==$id) {
					  	$visibility_icon_certificate='certificate';
					  	$certificate=get_lang('DefaultCertificate');
					  	$preview=get_lang('PreviewCertificate');
						$is_preview=true;
					  } else {
					  	$is_preview=false;
					  	$certificate=get_lang('NoDefaultCertificate');
					  }
					  if (isset($_GET['selectcat'])) {
					  	$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;selectcat='.Security::remove_XSS($_GET['selectcat']).'&amp;set_certificate='.$id.$req_gid.'&amp;'.$sort_params.'"><img src="../img/'.$visibility_icon_certificate.'.png" border="0" title="'.$certificate.'" alt="" /></a>';
						if ($is_preview) {
						 	$modify_icons .= '&nbsp;<a target="_blank"  href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;set_preview='.$id.$req_gid.'&amp;'.$sort_params.'" ><img src="../img/search.gif" border="0" title="'.$preview.'" alt="" /></a>';
							}
					  }
				}
		} else {
			$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;remove_as_template='.$id.$req_gid.'&amp;'.$sort_params.'"><img src="../img/wizard_gray_small.gif" border="0" title="'.get_lang('RemoveAsTemplate').'" alt=""'.get_lang('RemoveAsTemplate').'" /></a>';
		}
	}
	return $modify_icons;
}

function build_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '') {
	$form = '<form name="move_to" action="'.api_get_self().'" method="post">'."\n";
	$form .= '<input type="hidden" name="move_file" value="'.$move_file.'" />'."\n";

	$form .= '<div class="row">';
	$form .= '	<div class="label">';
	$form .= get_lang('MoveTo');
	$form .= '	</div>';
	$form .= '	<div class="formw">';

	$form .= ' <select name="move_to">'."\n";

	// Group documents cannot be uploaded in the root
	if ($group_dir == '') {
		if ($curdirpath != '/') {
			$form .= '<option value="/">/ ('.get_lang('HomeDirectory').')</option>';
		}
		if (is_array($folders)) {
			foreach ($folders as & $folder) {
				// You cannot move a file to:
				// 1. current directory
				// 2. inside the folder you want to move
				// 3. inside a subfolder of the folder you want to move
				if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
					$path_displayed = $folder;
					// If document title is used, we have to display titles instead of real paths...
					if (api_get_setting('use_document_title')) {
						$path_displayed = get_titles_of_path($folder);
					}
					$form .= '<option value="'.$folder.'">'.$path_displayed.'</option>'."\n";
				}
			}
		}
	} else {
		foreach ($folders as $folder) {
			if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) { // Cannot copy dir into his own subdir
				if (api_get_setting('use_document_title')) {
					$path_displayed = get_titles_of_path($folder);
				}
				$display_folder = substr($path_displayed,strlen($group_dir));
				$display_folder = ($display_folder == '') ? '/ ('.get_lang('HomeDirectory').')' : $display_folder;
				$form .= '<option value="'.$folder.'">'.$display_folder.'</option>'."\n";
			}
		}
	}

	$form .= '		</select>'."\n";
	$form .= '	</div>';

	$form .= '<div class="row">';
	$form .= '	<div class="label"></div>';
	$form .= '	<div class="formw">';
	$form .= '		<button type="submit" class="next" name="move_file_submit">'.get_lang('MoveElement').'</button>'."\n";
	$form .= '	</div>';
	$form .= '</div>';

	$form .= '</form>';

	$form .= '<div style="clear: both; margin-bottom: 10px;"></div>';

	return $form;
}

/**
 * Gets the path translated with title of docs and folders
 * @param string the real path
 * @return the path which should be displayed
 */
function get_titles_of_path($path) {

	global $tmp_folders_titles;

	$nb_slashes = substr_count($path, '/');
	$tmp_path = '';
	$current_slash_pos = 0;
	$path_displayed = '';
	for ($i = 0; $i < $nb_slashes; $i++) {
		// For each folder of the path, retrieve title.
		$current_slash_pos = strpos($path, '/', $current_slash_pos + 1);
		$tmp_path = substr($path, strpos($path, '/', 0), $current_slash_pos);

		if (empty($tmp_path)) {
			// If empty, then we are in the final part of the path
			$tmp_path = $path;
		}

		if (!empty($tmp_folders_titles[$tmp_path])) {
			// If this path has soon been stored here we don't need a new query
			$path_displayed .= $tmp_folders_titles[$tmp_path];
		} else {
			$sql = 'SELECT title FROM '.Database::get_course_table(TABLE_DOCUMENT).' WHERE path LIKE BINARY "'.$tmp_path.'"';
			$rs = Database::query($sql);
			$tmp_title = '/'.Database::result($rs, 0, 0);
			$path_displayed .= $tmp_title;
			$tmp_folders_titles[$tmp_path] = $tmp_title;
		}
	}
	return $path_displayed;
}

/**
 * This function displays the name of the user and makes the link tothe user tool.
 *
 * @param $user_id
 * @param $name
 * @return a link to the userInfo.php
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_user_link_document($user_id, $name) {
	if ($user_id != 0) {
		return '<a href="../user/userInfo.php?uInfo='.$user_id.'">'.$name.'</a>';
	} else {
		return get_lang('Anonymous');
	}
}
/**
 * Creates form that asks for the directory name.
 * @return string	html-output text for the form
 */
function create_dir_form() {

	$new_folder_text = '<form action="'.api_get_self().'" method="post">';
	$new_folder_text .= '<input type="hidden" name="curdirpath" value="'.Security::remove_XSS($_GET['curdirpath']).'" />';

	// Form title
	$new_folder_text .= '<div class="row"><div class="form_header">'.get_lang('CreateDir').'</div></div>';

	// Folder field
	$new_folder_text .= '<div class="row">';
	$new_folder_text .= '<div class="label"><span class="form_required">*</span>'.get_lang('NewDir').'</div>';
	$new_folder_text .= '<div class="formw"><input type="text" name="dirname" /></div>';
	$new_folder_text .= '</div>';

	// Submit button
	$new_folder_text .= '<div class="row">';
	$new_folder_text .= '<div class="label">&nbsp;</div>';
	$new_folder_text .= '<div class="formw"><button type="submit" class="add" name="create_dir">'.get_lang('CreateFolder').'</button></div>';
	$new_folder_text .= '</div>';
	$new_folder_text .= '</form>';
	$new_folder_text .= '<div style="clear: both; margin-bottom: 10px;"></div>';

	return $new_folder_text;
}


/**
 * Checks whether the user is in shared folder
 * @return return bool Return true when user is into shared folder
 */
function is_shared_folder($curdirpath, $current_session_id) {
	$clean_curdirpath = Security::remove_XSS($curdirpath);
	if($clean_curdirpath== '/shared_folder'){
		return true;
	}
	elseif($clean_curdirpath== '/shared_folder_session_'.$current_session_id){
		return true;
	}
	else{
		return false;
	}
}

/**
 * Checks whether the user is into any user shared folder
 * @return return bool Return true when user is in any user shared folder
 */
function is_any_user_shared_folder($path, $current_session_id) {
	$clean_path = Security::remove_XSS($path);
	if(strpos($clean_path,'shared_folder/sf_user_')){
		return true;
	}
	elseif(strpos($clean_path, 'shared_folder_session_'.$current_session_id.'/sf_user_')){
		return true;
	}
	else{
		return false;
	}
}

/**
 * Checks whether the user is into his shared folder
 * @return return bool Return true when user is in his user shared folder
 */
function is_my_shared_folder($user_id, $path, $current_session_id) {
	$clean_path = Security::remove_XSS($path);
	if($clean_path == '/shared_folder/sf_user_'.$user_id){
		return true;
	}
	elseif($clean_path == '/shared_folder_session_'.$current_session_id.'/sf_user_'.$user_id){
		return true;
	}
	else{
		return false;
	}
}

/**
 * Check if the file name or folder searched exist
 * @return return bool Return true when exist
 */
function search_keyword($document_name, $keyword) {
	if (api_strripos($document_name, $keyword) !== false){
		return true;
	} else {
		return false;
	}
}
?>