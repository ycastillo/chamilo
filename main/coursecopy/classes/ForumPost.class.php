<?php // $Id: ForumPost.class.php 3305 2005-02-03 12:44:01Z bmol $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
require_once('Resource.class.php');
/**
 * A forum-post
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class ForumPost extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The text
	 */
	var $text;
	/**
	 * The time
	 */
	var $post_time;
	/**
	 * Poster-ip
	 */
	var $poster_ip;
	/**
	 * Poster firstname
	 */
	 var $firstname;
	 /**
	  * Poster lastname
	  */
	 var $lastname;
	 /**
	  * Topic notify
	  */
	 var $topic_notify;
	 /**
	  * Parent post
	  */
	 var $parent_post_id;
	 /**
	  * Topic id
	  */
	 var $topic_id;
	/**
	 * Create a new ForumPost
	 */
	function ForumPost($id,$title,$text,$post_time,$poster_ip,$firstname,$lastname,$topic_notify,$parent_post_id,$topic_id)
	{
		parent::Resource($id,RESOURCE_FORUMPOST);
		$this->title = $title;
		$this->text = $text;
		$this->post_time = $post_time;
		$this->poster_ip = $poster_ip;
		$this->firstname = $firstname;
		$this->lastname = $lastname;
		$this->topic_notify = $topic_notify;
		$this->parent_post_id = $parent_post_id;
		$this->topic_id = $topic_id;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->firstname.' '.$this->lastname.', '.$this->post_time.')';	
	}
}
?>