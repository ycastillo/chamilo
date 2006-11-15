<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) Denes Nagy
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
* Deletes the web-chat request form the user table
* 
============================================================================== 
*/

$langFile = "index";

include_once('./main/inc/global.inc.php');

$track_user_table = Database::get_main_table(MAIN_USER_TABLE);

$sql="update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='DENIED' where (user_id = ".$_user['user_id'].")";
$result=api_sql_query($sql,__FILE__,__LINE__);

Display::display_header();

$message=get_lang("RequestDenied")."<br><br><a href='javascript:history.back()'>".get_lang("Back")."</a>";
Display::display_normal_message($message);

/*
============================================================================== 
		FOOTER 
============================================================================== 
*/ 

Display::display_footer();
?>
