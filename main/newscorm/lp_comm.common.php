<?php //$id: $
/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general, so the code might be a bit unsettling.
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
require_once('back_compat.inc.php');
require_once('learnpath.class.php');

//the library is included in global.inc.php
//require('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax(api_get_path(WEB_CODE_PATH).'newscorm/lp_comm.server.php');
$xajax->registerFunction("save_item");
$xajax->registerFunction("switch_item_details");
$xajax->registerFunction("backup_item_details");
?>