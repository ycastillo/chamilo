<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$language_file = array('userInfo');
$cidReset = true;
require '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

//jquery thickbox already called from main/inc/header.inc.php
$htmlHeadXtra[] = '<script type="text/javascript">
        
function show_icon_edit(element_html) { 
    ident="#edit_image";
    $(ident).show();
}       

function hide_icon_edit(element_html)  {
    ident="#edit_image";
    $(ident).hide();
}       
        
</script>';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[] = array('url' => 'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[] = array('url' => '#','name' => get_lang('MemberList'));
api_block_anonymous_users();

$group_id   = intval($_GET['id']);
$topic_id   = intval($_GET['topic_id']);

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
    api_not_allowed(true);
} else {
    $group_info = GroupPortalManager::get_group_data($group_id);
    if (empty($group_info)) {
        api_not_allowed(true);
    }
    $user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $group_id);
    if (!in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR, GROUP_USER_PERMISSION_READER))) {
        api_not_allowed(true);      
    }
}

Display :: display_header($tool_name, 'Groups');

// save message group
if (isset($_POST['token']) && $_POST['token'] === $_SESSION['sec_token']) {

    if (isset($_POST['action'])) {        
        $title        = $_POST['title'];
        $content      = $_POST['content'];
        $group_id     = intval($_POST['group_id']);
        $parent_id    = intval($_POST['parent_id']);
        
        if ($_POST['action'] == 'reply_message_group') {
            $title = cut($content, 50);
        }
        if ($_POST['action'] == 'edit_message_group') {
            $edit_message_id =  intval($_POST['message_id']);
            $res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id, $edit_message_id);
        } else {
            $res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id);
        }

        // display error messages
        if (is_string($res)) {
            Display::display_error_message($res);
        }

        if (!empty($res)) {
            $groups_user = GroupPortalManager::get_users_by_group($group_id);
            $group_info  = GroupPortalManager::get_group_data($group_id);
            $admin_user_info = api_get_user_info(1);
            $sender_name = api_get_person_name($admin_user_info['firstName'], $admin_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_email = $admin_user_info['mail'];
            $subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'),$group_info['name']);
            $link = api_get_path(WEB_PATH).'main/social/groups.php?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
            $text_link = '<a href="'.$link.'">'.get_lang('ClickHereToSeeMessageGroup')."</a><br />\r\n<br />\r\n".get_lang('OrCopyPasteTheFollowingUrl')." <br />\r\n ".$link;

            $message = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $group_info['name'])."<br />$text_link";

            foreach ($groups_user as $group_user) {
                if ($group_user == $current_user) continue;
                $group_user_info    = api_get_user_info($group_user['user_id']);
                $recipient_name     = api_get_person_name($group_user_info['firstName'], $group_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
                $recipient_email    = $group_user_info['mail'];
                @api_mail_html($recipient_name, $recipient_email, stripslashes($subject), $message, $sender_name, $sender_email);
            }
        }        
        $topic_id = intval($_GET['topic_id']);
        if ($_POST['action'] == 'add_message_group') {
            $topic_id = $res;    
        }        
    }
}


echo '<div id="social-content">';
    echo '<div id="social-content-left">';  
    //this include the social menu div
    SocialManager::show_social_menu('member_list', $group_id);
    echo '</div>';
    echo '<div id="social-content-right">';    
         echo '<h1><a href="groups.php?id='.$group_id.'">'.$group_info['name'].'</a></h1>';            
        if (!empty($show_message)){
            Display::display_confirmation_message($show_message);
        }
        $content = MessageManager::display_message_for_group($group_id, $topic_id);
        echo $content;
    echo '</div>';
echo '</div>';

Display :: display_footer();