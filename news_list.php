<?php
/* For licensing terms, see /license.txt */

// name of the language file that needs to be included
$language_file = array ('courses', 'index');

// including necessary files
require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';

$tool_name = get_lang('SystemAnnouncements');
Display::display_header($tool_name);

$start = isset($_GET['start']) ? (int)$_GET['start'] : $start = 0;

if (isset($_user['user_id'])) {
	$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
	SystemAnnouncementManager :: display_all_announcements($visibility, $announcement, $start, $_user['user_id']);
} else {
	SystemAnnouncementManager :: display_all_announcements(VISIBLE_GUEST, $announcement, $start);
}

Display::display_footer();