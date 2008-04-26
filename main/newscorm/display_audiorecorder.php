<?php //$id: $
// This file is part of the Dokeos software - See license and credits in the documentation/ directory
/**
 * Script opened in an iframe and containing the learning path's table of contents
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Script
 */

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
require_once('aicc.class.php');

$mylpid = 0;
$mylpitemid = 0;
if(isset($_SESSION['lpobject']))
{
	//if($debug>0) error_log('New LP - in lp_toc.php - SESSION[lpobject] is defined',0);
	$oLP = unserialize($_SESSION['lpobject']);
	if(is_object($oLP)){
		$_SESSION['oLP'] = $oLP;
		$mylpid = $oLP->get_id();
		$mylpitemid = $oLP->get_current_item_id();
	}else{
		//error_log('New LP - in lp_toc.php - SESSION[lpobject] is not object - dying',0);
		die('Could not instanciate lp object');
	}
}
$charset = $_SESSION['oLP']->encoding;
//$lp_id = $_SESSION['oLP']->get_id();

echo '<html>
		<body>';

echo '<div id="audiorecorder">	';
	

$audio_recorder_studentview = 'true';


$audio_recorder_item_id = $_SESSION['oLP']->current;
if(api_get_setting('service_visio','active')=='true'){
	include('audiorecorder.inc.php');
}
// end of audiorecorder include

echo '</div></body></html>';
?>