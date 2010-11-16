<?php
/**
 * This script is included by main/admin/settings.lib.php and generally 
 * includes things to execute in the main database (settings_current table)
 */
$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
$t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
$sql = "INSERT INTO $t_settings
  (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) 
  VALUES
  ('bbb_plugin', '', 'radio', 'Extra', 'false', 'BigBlueButtonEnableTitle','BigBlueButtonEnableComment',NULL,NULL, 1)";
Database::query($sql);
$sql = "INSERT INTO $t_options (variable, value, display_text) VALUES ('bbb_plugin', 'true', 'Yes')";
Database::query($sql);
$sql = "INSERT INTO $t_options (variable, value, display_text) VALUES ('bbb_plugin', 'false', 'No')";
Database::query($sql);
$sql = "INSERT INTO $t_settings
  (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) 
  VALUES
  ('bbb_plugin_host', '', 'textfield', 'Extra', '192.168.0.100', 'BigBlueButtonHostTitle','BigBlueButtonHostComment',NULL,NULL, 1)";
Database::query($sql);
$sql = "INSERT INTO $t_settings
  (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) 
  VALUES
  ('bbb_plugin_salt', '', 'textfield', 'Extra', '', 'BigBlueButtonSecuritySaltTitle','BigBlueButtonSecuritySaltComment',NULL,NULL, 1)";
Database::query($sql);
// update existing courses to add conference settings
$t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT id, code, db_name FROM $t_courses ORDER BY id";
$res = Database::query($sql);
while ($row = Database::fetch_assoc($res)) {
  $t_course = Database::get_course_table(TABLE_COURSE_SETTING,$row['db_name']);
  $sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_meeting_name','','plugins')";
  $r = Database::query($sql_course);
  $sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_attendee_password','','plugins')";
  $r = Database::query($sql_course);
  $sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_moderator_password','','plugins')";
  $r = Database::query($sql_course);
  $sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_welcome_message','','plugins')";
  $r = Database::query($sql_course);
}
