<?php
// Course tools
// Course introduction

// The toolbar set that is visible when the editor has "normal" size.
$config['ToolbarSets']['Introduction'] = array(
	array('NewPage','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar'),
	array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
	'/',
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight')
);

/*
// The toolbar set that is visible when the editor is maximized.
// If it has not been defined, then the toolbar set for the "normal" size is used.
$config['ToolbarSets']['IntroductionMaximized'] = array(
	array('FitWindow','-') // ...
);
*/

// Sets whether the toolbar can be collapsed/expanded or not.
// Possible values: true , false
//$config['ToolbarCanCollapse'] = true;

// Sets how the editor's toolbar should start - expanded or collapsed.
// Possible values: true , false
//$config['ToolbarStartExpanded'] = true;

//This option sets the location of the toolbar.
// Possible values: 'In' , 'None' , 'Out:[TargetId]' , 'Out:[TargetWindow]([TargetId])'
//$config['ToolbarLocation'] = 'In';

// A setting for blocking copy/paste functions of the editor.
// This setting activates on leaners only. For users with other statuses there is no blocking copy/paste.
// Possible values: true , false
//$config['BlockCopyPaste'] = false;

// Here new width and height of the editor may be set.
// Possible values, examples: 300 , '250' , '100%' , ...
//$config['Width'] = '100%';
//$config['Height'] = '300';
