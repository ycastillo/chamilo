<?php // $Id: configure_homepage.php 9246 2006-09-25 13:24:53 +0000 (lun., 25 sept. 2006) bmol $
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

$langFile='admin';

$cidReset=true;

include('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tbl_settings_services = Database::get_main_table(MAIN_SETTINGS_SERVICE_TABLE);
$message = '';

if(isset($_POST['activeExtension'])){
	
	switch ($_POST['extension_code']){
		
		case 'visio' :
			$sql = 'UPDATE '.$tbl_settings_services.' SET
					value="true"
					WHERE variable="active"
					AND code_service="visio"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);			
			if(mysql_affected_rows()>0){
				$message = get_lang('ServiceActivated');
			}
			
			// select all the courses and insert the tool inside
			$sql = 'SELECT db_name FROM '.Database::get_main_table(MAIN_COURSE_TABLE);
			echo $sql;
			$rs = api_sql_query($sql);
			while($row = mysql_fetch_array($rs)){
				
				$sql = 'INSERT INTO '.$row['db_name'].'.'.TOOL_LIST_TABLE.' SET 
						name="visio",
						link="conf/",
						image="",
						visibility="1",
						admin="0",
						address="squaregrey.gif",
						target="_self",
						category="authoring"';
				
				api_sql_query($sql);

			}
			
			break;	
			
		case 'ppt2lp' :
			$sql = 'UPDATE '.$tbl_settings_services.' SET
					value="true"
					WHERE variable="active"
					AND code_service="ppt2lp"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			if(mysql_affected_rows()>0){
				$message = get_lang('ServiceActivated');
			}
			break;		
	}
	
}


$listActiveServices = array();

// get the list of active services
$sql = 'SELECT code_service FROM '.$tbl_settings_services.' WHERE variable="active" and value="true"';
$rs = api_sql_query($sql, __FILE__, __LINE__);
while($row = mysql_fetch_array($rs)){
	$listActiveServices[] = $row['code_service'];
}


$javascript_service_displayed = '';
if(isset($_GET['display'])){
	$javascript_service_displayed = 'document.getElementById("extension_content_'.$_GET['display'].'").style.display = "block"';
}

// javascript to handle accordion behaviour
$javascript_message = '';
if(!empty($message)){
	$javascript_message = 
	'
	document.getElementById("message").style.display = "block";
	var timer = setTimeout(hideMessage,5000);
	';
}
$htmlHeadXtra[]= '
<script type="text/javascript">
var listeDiv;
var extensionsHeader = new Array();
var extensionsContent = new Array();
window.onload = loadTables;
function loadTables(){
	'.$javascript_message.'
	var listeDiv = document.getElementsByTagName("div");
	
	// fill extensionsHeader and extensionsContent
	for(var i=0 ; i < listeDiv.length ; i++){
		if(listeDiv[i].id.indexOf(\'extension_header\')!=-1){
			listeDiv[i].onclick = afficheContent;
			extensionsHeader.push(listeDiv[i]);		
		}
		if(listeDiv[i].id.indexOf("extension_content")!=-1){
			extensionsContent.push(listeDiv[i]);
		}
	}
'.$javascript_service_displayed.'
}

function hideMessage(){
	document.getElementById("message").style.display = "none";
}

function afficheContent(event){	
	var id = this.id.replace("header","content");
	switch(document.getElementById(id).style.display){
		case "block" : 			
			document.getElementById(id).style.display = "none";
			break;
		case "none" :
			document.getElementById(id).style.display = "block";
			for(var i=0 ; i < extensionsContent.length ; i++){
				if(extensionsContent[i].id != id)
					extensionsContent[i].style.display = "none";				
			}
			break;
	}
}
</script>';

$nameTool = get_lang('ConfigureExtensions');
Display::display_header($nameTool);

?>


<div id="message" style="display: none">
	<?php 
	if(!empty($message))
		Display::display_normal_message($message) 
	?>
</div>

<div id="content" align="center">



<!-- INSTRUCTIONS TO ADD AN EXTENSION HERE
- copy paste a "main_*" div 
- set the names of the subdiv to extension_header_yourextension and extension_content_yourextension
- extension_content_yourextension is the hidden div where you have to put your form / activation process
- extension_header_yourextension is the name of your extension
- you do not need to add javascript to display / hide your divs
- please fill free to improve the global display of the document
-->
	<!-- VISIOCONFERENCE -->
	<div id="main_visio">
		<div id="extension_header_visio" class="accordion_header">
			<a href="#"><?php echo get_lang('Visioconf') ?></a>
		</div>
		<div id="extension_content_visio" style="display:none" class="accordion_content">		
			<?php echo get_lang('VisioconfDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td>
						<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_conf.jpg' ?>" />
					</td>
					<td align="center" width="50%">
						<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<?php 
						if(in_array('visio',$listActiveServices)){
							echo get_lang('ExtensionActivedButNotYetOperational');
						}
						else {
								echo '
									<input type="hidden" name="extension_code" value="visio" />
									<input type="submit" name="activeExtension" value="'.get_lang('ActiveExtension').'" />';
						}
						?>
						</form>
					</td>
				</tr>
			</table>
		</div>
	</div>
	
	<!-- PPT2LP -->
	<div id="main_ppt2lp">
		<div id="extension_header_ppt2lp" class="accordion_header">
			<a href="#"><?php echo get_lang('Ppt2lp') ?></a>
		</div>
		<div id="extension_content_ppt2lp" style="display:none" class="accordion_content">		
			<?php echo get_lang('Ppt2lpDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td width="50%">
						<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_ppt2lp.jpg' ?>" />
					</td>
					<td align="center" width="50%">
						<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<?php 
						if(in_array('ppt2lp',$listActiveServices)){
							echo get_lang('ExtensionActivedButNotYetOperational');
						}
						else {
								echo '
									<input type="hidden" name="extension_code" value="ppt2lp" />
									<input type="submit" name="activeExtension" value="'.get_lang('ActiveExtension').'" />';
						}
						?>
						</form>
					</td>
				</tr>
			</table>
		</div>
	</div>
	
	<!-- SEARCH -->
	<div id="main_search">
		<div id="extension_header_search" class="accordion_header">
			<a href="#"><?php echo get_lang('SearchEngine') ?></a>
		</div>
		<div id="extension_content_search" style="display:none" class="accordion_content">		
			<?php echo get_lang('SearchEngineDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td width="50%">
						<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_search.jpg' ?>" />
					</td>
					<td align="center" width="50%">
						<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="extension_code" value="search" />
						<input type="submit" name="activeExtension" value="<?php echo get_lang('ActiveExtension') ?>" />
						</form>
					</td>
				</tr>
			</table>
		</div>
	</div>
	
	<!-- SERVER STATS -->
	<div id="main_serverstats">
		<div id="extension_header_serverstats" class="accordion_header">
			<a href="#"><?php echo get_lang('ServerStatistics') ?></a>
		</div>
		<div id="extension_content_serverstats" style="display:none" class="accordion_content">		
			<?php echo get_lang('ServerStatisticsDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td width="50%">
						<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_serverstats.jpg' ?>" />
					</td>
					<td align="center" width="50%">
						<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="extension_code" value="serverstats" />
						<input type="submit" name="activeExtension" value="<?php echo get_lang('ActiveExtension') ?>" />
						</form>
					</td>
				</tr>
			</table>
		</div>
	</div>
	
	<!-- BANDWIDTH STATS -->
	<div id="main_bandwidthstats">
		<div id="extension_header_bandwidthstats" class="accordion_header">
			<a href="#"><?php echo get_lang('BandWidthStatistics') ?></a>
		</div>
		<div id="extension_content_bandwidthstats" style="display:none" class="accordion_content">		
			<?php echo get_lang('BandWidthStatisticsDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td width="50%">
						<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_bandwidth.jpg' ?>" />
					</td>
					<td align="center" width="50%">
						<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="extension_code" value="bandwidthstats" />
						<input type="submit" name="activeExtension" value="<?php echo get_lang('ActiveExtension') ?>" />
						</form>
					</td>
				</tr>
			</table>
		</div>
	</div>
	
</div><!-- /content -->
	

<?php

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
