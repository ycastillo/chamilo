<?php //$id: $
/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Initialisations
 */
$debug = 0;
if($debug>0) error_log('New LP -+- Entered lp_controller.php -+-',0);
$langFile[] = "scormdocument";
$langFile[] = "scorm";
$langFile[] = "learnpath";

//include class definitions before session_start() to ensure availability when touching
//session vars containing learning paths
require_once('learnpath.class.php');
if($debug>0) error_log('New LP - Included learnpath',0);
require_once('learnpathItem.class.php');
if($debug>0) error_log('New LP - Included learnpathItem',0);
require_once('scorm.class.php');
if($debug>0) error_log('New LP - Included scorm',0);
require_once('scormItem.class.php');
if($debug>0) error_log('New LP - Included scormItem',0);
require_once('aicc.class.php');
if($debug>0) error_log('New LP - Included aicc',0);
require_once('aiccItem.class.php');
if($debug>0) error_log('New LP - Included aiccItem',0);
require_once('temp.lib.php');
if($debug>0) error_log('New LP - Included temp',0);


require_once('back_compat.inc.php');
if($debug>0) error_log('New LP - Included back_compat',0);
api_protect_course_script();
//TODO @TODO define tool, action and task to give as parameters to:
//$is_allowed_to_edit = api_is_allowed_to_edit();

if ($is_allowed_in_course == false){
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

require_once(api_get_path(LIBRARY_PATH) . "/fckeditor.lib.php");
$lpfound = false;

$myrefresh = 0;
$myrefresh_id = 0;
if(!empty($_SESSION['refresh']) && $_SESSION['refresh']==1){
	//check if we should do a refresh of the oLP object (for example after editing the LP)
	//if refresh is set, we regenerate the oLP object from the database (kind of flush)
	api_session_unregister('refresh');
	$myrefresh = 1;
	if($debug>0) error_log('New LP - Refresh asked',0);
}
if($debug>0) error_log('New LP - Passed refresh check',0);

if(!empty($_REQUEST['dialog_box'])){
	$dialog_box = learnpath::escape_string(urldecode($_REQUEST['dialog_box']));
}

$lp_controller_touched = 1;

if(isset($_SESSION['lpobject']))
{
	if($debug>0) error_log('New LP - SESSION[lpobject] is defined',0);
	$oLP = unserialize($_SESSION['lpobject']);
	if(is_object($oLP)){
		if($debug>0) error_log('New LP - oLP is object',0);
		if($myrefresh == 1 OR $oLP->cc != api_get_course_id()){
			if($debug>0) error_log('New LP - Course has changed, discard lp object',0);
			if($myrefresh == 1){$myrefresh_id = $oLP->get_id();}
			$oLP = null;
			api_session_unregister('oLP');
			api_session_unregister('lpobject');
		}else{
			$_SESSION['oLP'] = $oLP;
			$lp_found = true;
		}
	}
}
if($debug>0) error_log('New LP - Passed data remains check',0);

if($lp_found == false 
	|| ($_SESSION['oLP']->get_id() != $_REQUEST['lp_id'])
	)
{
	if($debug>0) error_log('New LP - oLP is not object, has changed or refresh been asked, getting new',0);		
	//regenerate a new lp object? Not always as some pages don't need the object (like upload?)
	if(!empty($_REQUEST['lp_id']) || !empty($myrefresh_id)){
		if($debug>0) error_log('New LP - lp_id is defined',0);
		//select the lp in the database and check which type it is (scorm/dokeos/aicc) to generate the
		//right object
		$lp_table = Database::get_course_table('lp');
		if(!empty($_REQUEST['lp_id'])){
			$lp_id = escape_txt($_REQUEST['lp_id']);
		}else{
			$lp_id = $myrefresh_id;
		}
		$sel = "SELECT * FROM $lp_table WHERE id = $lp_id";
		if($debug>0) error_log('New LP - querying '.$sel,0);
		$res = api_sql_query($sel);
		if(Database::num_rows($res))
		{
			$row = Database::fetch_array($res);
			$type = $row['lp_type'];
			if($debug>0) error_log('New LP - found row - type '.$type. ' - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
			switch($type){
				case 1:
					$oLP = new learnpath(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
				case 2:
					$oLP = new scorm(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
				default:
					$oLP = new learnpath(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
			}
		}
	}else{
		if($debug>0) error_log('New LP - Request[lp_id] and refresh_id were empty',0);
	}
	if($lp_found)
	{
		$_SESSION['oLP'] = $oLP;
	}
}
if($debug>0) error_log('New LP - Passed oLP creation check',0);


/**
 * Actions switching
 */
$_SESSION['oLP']->update_queue = array(); //reinitialises array used by javascript to update items in the TOC
$_SESSION['oLP']->message = ''; //should use ->clear_message() method but doesn't work
switch($_REQUEST['action'])
{
	case 'admin_view':
		if($debug>0) error_log('New LP - admin_view action triggered',0);
		$_SESSION['refresh'] = 1;
		require('lp_admin_view.php');
		break;
	case 'upload':
		if($debug>0) error_log('New LP - upload action triggered',0);
		$cwdir = getcwd();
		require('lp_upload.php');
		//reinit current working directory as many functions in upload change it
		chdir($cwdir);
		require('lp_list.php');
		break;
	case 'export':
		if($debug>0) error_log('New LP - export action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for export',0); require('lp_list.php'); }
		else{
			if($_SESSION['oLP']->get_type()==2){
				$_SESSION['oLP']->export_zip();
			}
			//require('lp_list.php'); 
		}
		break;
	case 'delete':
		if($debug>0) error_log('New LP - delete action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for delete',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->delete(null,null,'remove');
			api_session_unregister('oLP');
			//require('lp_delete.php');
			require('lp_list.php');
		}
		break;
	case 'toggle_visible': //change lp visibility
		if($debug>0) error_log('New LP - publish action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for publish',0); require('lp_list.php'); }
		else{
			learnpath::toggle_visibility($_REQUEST['lp_id'],$_REQUEST['new_status']);
			require('lp_list.php');
		}
		break;
	case 'edit':
		if($debug>0) error_log('New LP - edit action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for edit',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			require('lp_edit.php');
			//require('lp_admin_view.php');
		}
		break;
	case 'update_lp':
		if($debug>0) error_log('New LP - update_lp action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for edit',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->set_name($_REQUEST['lp_name']);
			$_SESSION['oLP']->set_encoding($_REQUEST['lp_encoding']);
			$_SESSION['oLP']->set_maker($_REQUEST['lp_maker']);
			$_SESSION['oLP']->set_proximity($_REQUEST['lp_proximity']);
			require('lp_list.php');
		}	
		break;
	case 'add_lp':
		if($debug>0) error_log('New LP - add_lp action triggered',0);
		
		//call learnpath creation abstract method with course_id, learnpath_name, learnpath_description, type_of_lp, origin_of_creation, file_name
		if(!empty($_REQUEST['learnpath_name'])){
			$_SESSION['refresh'] = 1;
			$new_lp_id = learnpath::add_lp(api_get_course_id(),$_REQUEST['learnpath_name'],$_REQUEST['learnpath_description'],'dokeos','manual','');
			//TODO maybe create a first module directly to avoid bugging the user with useless queries
			$_SESSION['oLP'] = new learnpath(api_get_course_id(),$new_lp_id,api_get_user_id());
			$_SESSION['oLP']->add_item(0,-1,'dokeos_chapter',$_REQUEST['path'],'Default');			
		}
		require('lp_list.php');
		
		
		break;
			
	case 'add_item':
		if($debug>0) error_log('New LP - add item action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for add item',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			if(!empty($_REQUEST['submit_button']) && !empty($_REQUEST['title'])){
				$_SESSION['oLP']->add_item($_REQUEST['parent'],$_REQUEST['previous'],$_REQUEST['type'],$_REQUEST['path'],$_REQUEST['title']);
			}
			require('lp_admin_view.php');
		}
		break;
	
	case 'add_module':
		if($debug > 0)
			error_log('New LP - add item action triggered', 0);
		
		if(!$lp_found)
		{
			error_log('New LP - No learnpath given for add item', 0);
			
			require('lp_list.php');
		}
		else
		{
			//$_SESSION['refresh'] = 1;
			
			if(isset($_POST['cmdSubmit']))
			{
				$_SESSION['oLP']->add_item(0, -1, 'dokeos_module', '', $_POST['txtTitle']);
			}
		
			require('lp_view.php');
		}
		
		break;
	case 'add_document':
		if($debug > 0)
			error_log('New LP - add item action triggered', 0);
		
		if(!$lp_found)
		{
			error_log('New LP - No learnpath given for add item', 0);
			
			require('lp_list.php');
		}
		else
		{
			//$_SESSION['refresh'] = 1;
			
			if(isset($_POST['cmdSubmit']))
			{
				$_SESSION['oLP']->add_item(0, -1, 'dokeos_document', '', $_POST['txtTitle']);
			}
		
			require('lp_view.php');
		}
		
		break;
		
	case 'add_sub_item': //add an item inside a chapter
		if($debug>0) error_log('New LP - add sub item action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for add sub item',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			if(!empty($_REQUEST['parent_item_id'])){
				$_SESSION['from_learnpath']='yes';
				$_SESSION['origintoolurl'] = 'lp_controller.php?action=admin_view&lp_id='.$_REQUEST['lp_id'];
				require('resourcelinker.php');
				//$_SESSION['oLP']->add_sub_item($_REQUEST['parent_item_id'],$_REQUEST['previous'],$_REQUEST['type'],$_REQUEST['path'],$_REQUEST['title']);
			}else{
				require('lp_admin_view.php');
			}
		}
		break;
	case 'deleteitem':
	case 'delete_item':
		if($debug>0) error_log('New LP - delete item action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for delete item',0); require('lp_list.php'); }
		else{
			$_SESSION['refresh'] = 1;
			if(!empty($_REQUEST['id'])){
				$_SESSION['oLP']->delete_item($_REQUEST['id']);
			}
			require('lp_admin_view.php');
		}
		break;
	case 'edititem':
	case 'edit_item':
		if($debug>0) error_log('New LP - edit item action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for edit item',0); require('lp_list.php'); }
		else{
			if(!empty($_REQUEST['id']) && !empty($_REQUEST['submit_item'])){
				$_SESSION['refresh'] = 1;
				$_SESSION['oLP']->edit_item($_REQUEST['id'], $_REQUEST['title']);
			}
			require('lp_admin_view.php');
		}
		break;
	case 'edititemprereq':
	case 'edit_item_prereq':
		if($debug>0) error_log('New LP - edit item prereq action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for edit item prereq',0); require('lp_list.php'); }
		else{
			if(!empty($_REQUEST['id']) && !empty($_REQUEST['submit_item'])){
				$_SESSION['refresh'] = 1;
				$_SESSION['oLP']->edit_item_prereq($_REQUEST['id'],$_REQUEST['prereq']);
			}
			require('lp_admin_view.php');
		}
		break;
	case 'restart':
		if($debug>0) error_log('New LP - restart action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for restart',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->restart();
			require('lp_view.php');
		}
		break;
	case 'last':
		if($debug>0) error_log('New LP - last action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for last',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->last();
			require('lp_view.php');
		}
		break;
	case 'first':
		if($debug>0) error_log('New LP - first action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for first',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->first();
			require('lp_view.php');
		}
		break;
	case 'next':
		if($debug>0) error_log('New LP - next action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for next',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->next();
			require('lp_view.php');
		}
		break;
	case 'previous':
		if($debug>0) error_log('New LP - previous action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for previous',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->previous();
			require('lp_view.php');
		}
		break;
	case 'content':
		if($debug>0) error_log('New LP - content action triggered',0);
		if($debug>0) error_log('New LP - Item id is '.$_GET['item_id'],0);
		if(!$lp_found){ error_log('New LP - No learnpath given for content',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->set_current_item($_GET['item_id']); 
			$_SESSION['oLP']->start_current_item();
			require('lp_content.php'); 
		}
		break;	
	case 'view':
		if($debug > 0)
			error_log('New LP - view action triggered', 0);
		
		if(!$lp_found)
		{
			error_log('New LP - No learnpath given for view', 0);
			
			require('lp_list.php');
		}
		else
		{
			if($debug > 0)
				error_log('New LP - trying to set current item to ' . $_REQUEST['item_id'], 0);
			
			$_SESSION['oLP']->set_current_item($_REQUEST['item_id']);
			
			require('lp_view.php');
		}
		
		break;		
	case 'save':
		if($debug>0) error_log('New LP - save action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for save',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->save_item();
			require('lp_save.php');
		}
		break;		
	case 'stats':
		if($debug>0) error_log('New LP - stats action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for stats',0); require('lp_list.php'); }
		else{
			$_SESSION['oLP']->save_current();
			$_SESSION['oLP']->save_last();
			require('lp_stats.php');
		}
		break;
	case 'list':
		if($debug>0) error_log('New LP - list action triggered',0);
		if($lp_found){
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->save_last();
		}
		require('lp_list.php');
		break;
	case 'mode':
		//switch between fullscreen and embedded mode
		if($debug>0) error_log('New LP - mode change triggered',0);
		$mode = $_REQUEST['mode'];
		if($mode == 'fullscreen'){
			$_SESSION['oLP']->mode = 'fullscreen';
		}else{
			$_SESSION['oLP']->mode = 'embedded';		
		}
		require('lp_view.php');
		break;
	case 'switch_view_mode':
		if($debug>0) error_log('New LP - switch_view_mode action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->update_default_view_mode();		
		require('lp_list.php');
		break;
	case 'switch_force_commit':
		if($debug>0) error_log('New LP - switch_force_commit action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->update_default_scorm_commit();
		require('lp_list.php');
		break;
	case 'switch_reinit':
		if($debug>0) error_log('New LP - switch_reinit action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->update_reinit();
		require('lp_list.php');
		break;		
	case 'switch_scorm_debug':
		if($debug>0) error_log('New LP - switch_scorm_debug action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->update_scorm_debug();
		require('lp_list.php');
		break;		
	case 'intro_cmdAdd':
		if($debug>0) error_log('New LP - intro_cmdAdd action triggered',0);
		//add introduction section page
		break;
	case 'moveitem':
	case 'move_item':
		if($debug > 0)
			error_log('New LP - move_item action triggered', 0);
		
		if(!$lp_found)
		{
			error_log('New LP - No learnpath given for move_item', 0);
			
			require('lp_list.php');
		}
		
		if(!empty($_REQUEST['direction']) && !empty($_REQUEST['id']))
		{
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->move_item($_REQUEST['id'], $_REQUEST['direction']);
		}
		
		require('lp_admin_view.php');
		
		break;	
	case 'js_api_refresh':
		if($debug>0) error_log('New LP - js_api_refresh action triggered',0);
		if(!$lp_found){ error_log('New LP - No learnpath given for js_api_refresh',0); require('lp_message.php'); }
		if(isset($_REQUEST['item_id'])){
			$htmlHeadXtra[] = $_SESSION['oLP']->get_js_info($_REQUEST['item_id']);
		}
		require('lp_message.php');
		break;	
		
	default:
		if($debug>0) error_log('New LP - default action triggered',0);
		//$_SESSION['refresh'] = 1;
		require('lp_list.php');
		break;
}
if(!empty($_SESSION['oLP'])){
	$_SESSION['lpobject'] = serialize($_SESSION['oLP']);
	if($debug>0) error_log('New LP - lpobject is serialized in session',0);
}
?>