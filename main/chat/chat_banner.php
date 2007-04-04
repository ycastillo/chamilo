<?php // $Id: chat_banner.php,v 1.3 2005/06/06 13:01:23 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
*	Dokeos banner
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

$language_file = array ('chat');

include('../inc/global.inc.php');


$interbreadcrumb[] = array ("url" => "chat.php", "name" => get_lang("Chat"));
$noPHP_SELF=true;
$shortBanner=false;

Display::display_header(null,"Chat");
?>

</body>
</html>