<?php
/* For licensing terms, see /license.txt */

/**
 *  This script displays the Chamilo header.
 *  @package chamilo.include
 */

/*  HEADERS SECTION */

header('Content-Type: text/html; charset='.api_get_system_encoding());
//show the X-Powered-By header so that parsers can find it
global $_configuration, $this_section;
header('X-Powered-By: '.$_configuration['software_name'].' '.substr($_configuration['system_version'],0,1));

$navigator_info = api_get_navigator();
//ie6 fix
if ($navigator_info['name'] == 'Internet Explorer' &&  $navigator_info['version'] == '6') {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix_tilebg.js" type="text/javascript" language="javascript"></script>';
}

// Include here the script ASCIIMathML.js if you want to show mathematical formulas and graphics
// not only in the "Documents" tool, but elsewhere in the system. This setting is related to the
// online editor's plugins 'asciimath' and 'asciisvg'.
if (api_get_setting('include_asciimathml_script') == 'true') {
    $htmlHeadXtra[] = api_get_js('asciimath/ASCIIMathML.js');
}

if (isset($httpHeadXtra) && $httpHeadXtra) {
    foreach ($httpHeadXtra as & $thisHttpHead) {
        header($thisHttpHead);
    }
}

// Get language iso-code for this page - ignore errors
$document_language = api_get_language_isocode();

$course_title = $_course['name'];
$title_list[] = api_get_setting('Institution');
$title_list[] = api_get_setting('siteName');
if (!empty($course_title)) {
    $title_list[] = $course_title;
}

if ($nameTools != '') {
    $title_list[] = $nameTools;
}

$title_string = '';
for($i=0; $i<count($title_list);$i++) {
    $title_string .=$title_list[$i];
    if (isset($title_list[$i+1])) {
        $item = trim($title_list[$i+1]);
        if (!empty($item))
            $title_string .=' - ';
    }    
}

/*
 * HTML HEADER
 */
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<title>
<?php
echo Security::remove_XSS($title_string);
?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php

$platform_theme = api_get_setting('stylesheets');
$my_style = api_get_visual_theme();

global $show_learn_path;

if ($show_learn_path) {
    $htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CSS_PATH).$my_style.'/learnpath.css"/>';
}
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/dtree/dtree.css" />'; //will be moved
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/dtree/dtree.js" type="text/javascript"></script>'; //will be moved

//Base CSS
echo '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';
//Default CSS
echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/default.css";';
//Course CSS
echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/course.css";';

// enabling page to load extra css from correct theme 
if (isset($htmlCSSXtra) && $htmlCSSXtra) {
	foreach ($htmlCSSXtra as & $css) 
		echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/'.$css.'";'."\n";
	
}

if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
    echo 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } ';
}

?>
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
<?php
  echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/print.css";';
?>
/*]]>*/
</style>

<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/jquery.min.js" type="text/javascript" ></script>
<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/chosen/chosen.jquery.min.js" type="text/javascript" ></script>

<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/thickbox.js" type="text/javascript" ></script>
<link rel="stylesheet" href="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/thickbox.css" type="text/css" media="projection, screen" />
<link rel="stylesheet" href="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/chosen/chosen.css" type="text/css" media="projection, screen" />

<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'), ENT_QUOTES); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'), ENT_QUOTES); ?>" />
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="Author" />
<link href="http://www.chamilo.org" rel="Copyright" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<meta name="Generator" content="<?php echo $_configuration['software_name'].' '.substr($_configuration['system_version'],0,1);?>" />
<script src= "<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/jquery.menu.js" type="text/javascript"></script>

<?php if ((api_get_setting('allow_global_chat') == 'true' && !api_is_anonymous()) { ?>
	<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/chat/js/chat.js" type="text/javascript" ></script>
	<link rel="stylesheet" href="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/chat/css/chat.css" type="text/css" media="projection, screen" />
	<link rel="stylesheet" href="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/chat/css/screen.css" type="text/css" media="projection, screen" />
<?php } ?>

<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
if ( ( navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf( 'opera' ) == -1 ) ) {
    window.attachEvent( 'onunload', function() {
            window['__flash__removeCallback'] = function ( instance, name ) {
                try {
                    if ( instance ) {
                        instance[name] = null ;
                    }
                } catch ( flashEx ) {
                }
            } ;
	});
}

/* Global chat variables */
var ajax_url = '<?php echo api_get_path(WEB_AJAX_PATH).'chat.ajax.php'; ?>';
var online_button = '<?php  echo Security::remove_XSS(Display::return_icon('online.png')); ?>';
var offline_button = '<?php echo Security::remove_XSS(Display::return_icon('offline.png')); ?>';

var	connect_lang = '<?php echo get_lang('ChatConnected');?>';
var	disconnect_lang = '<?php echo get_lang('ChatDisconnected');?>';


//]]>
</script>
<?php
if (api_get_setting('accessibility_font_resize') == 'true') {
    echo '<script src= "'.api_get_path(WEB_LIBRARY_PATH).'javascript/fontresize.js" type="text/javascript"></script>';
}

if (isset($htmlHeadXtra) && $htmlHeadXtra) {
    foreach ($htmlHeadXtra as & $this_html_head) {
        echo $this_html_head;
    }
}
if (isset($htmlIncHeadXtra) && $htmlIncHeadXtra) {
    foreach ($htmlIncHeadXtra as & $this_html_head) {
        include($this_html_head);
    }
}
// The following include might be subject to a setting proper to the course or platform.
include api_get_path(LIBRARY_PATH).'javascript/email_links.lib.js.php';

$favico = '<link rel="shortcut icon" href="'.api_get_path(WEB_PATH).'favicon.ico" type="image/x-icon" />';
if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        $homep            = api_get_path(REL_PATH).'home/'.$clean_url; //homep for Home Path               
        //we create the new dir for the new sites
        if (is_file($homep.'favicon.ico')) {
            $favico = '<link rel="shortcut icon" href="'.$homep.'favicon.ico" type="image/x-icon" />';
        }
    }
}
echo $favico;
if (!api_is_platform_admin()) {
	$extra_header = trim(api_get_setting('header_extra_content'));
	if (!empty($extra_header)) {
		echo $extra_header;
	}		
}
?>
</head>
<body dir="<?php echo api_get_text_direction(); ?>" <?php
if (defined('CHAMILO_HOMEPAGE') && CHAMILO_HOMEPAGE) {
  echo 'onload="javascript: if(document.formLogin) { document.formLogin.login.focus(); }"';
}
echo 'class="'.(!empty($this_section)?$this_section:'').'"';?>>
<div class="skip">
<ul>
<li><a href="#menu"><?php echo get_lang('WCAGGoMenu'); ?></a></li>
<li><a href="#content" accesskey="2"><?php echo get_lang('WCAGGoContent'); ?></a></li>
</ul>
</div>
<?php

// Banner
require_once api_get_path(INCLUDE_PATH).'banner.inc.php';
